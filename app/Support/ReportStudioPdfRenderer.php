<?php

namespace App\Support;

use App\Models\ReportStudioTemplate;
use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Facades\View;
use PDF;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;
use Throwable;

class ReportStudioPdfRenderer
{
    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{content: string, renderer: string}
     */
    public function renderPreview(ReportStudioTemplate $template, array $payload, string $filename): array
    {
        return match ($template->renderer) {
            'chrome' => $this->renderWithChrome($payload, $filename),
            'browsershot' => $this->renderWithBrowsershot($payload, $filename),
            default => $this->renderWithMpdf($template, $payload),
        };
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{content: string, renderer: string}
     */
    public function renderDocument(string $studioType, array $payload, string $filename): array
    {
        $template = ReportStudioTemplate::resolveDefaultFor($studioType)
            ?? ReportStudioDefaultTemplates::make($studioType);

        try {
            return $this->renderPreview($template, $payload, $filename);
        } catch (RuntimeException $exception) {
            if (! in_array($template->renderer, ['chrome', 'browsershot'], true)) {
                throw $exception;
            }

            $renderedPdf = $this->renderWithMpdf($template, $payload);

            return [
                'content' => $renderedPdf['content'],
                'renderer' => 'mpdf-fallback',
            ];
        }
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{content: string, renderer: string}
     */
    private function renderWithMpdf(ReportStudioTemplate $template, array $payload): array
    {
        $payload = $this->withPrintableCanvasGeometry($payload);
        $payload['data'] = $this->dataForMpdf($payload);
        $customPageWidth = (float) data_get($payload, 'data.customPageWidth', 0);
        $customPageHeight = (float) data_get($payload, 'data.customPageHeight', 0);
        $format = $customPageWidth > 0 && $customPageHeight > 0
            ? [$customPageWidth, $customPageHeight]
            : ($payload['data']['format'] ?? 'A4');
        $format = $format === 'custom' ? 'A4' : $format;

        $pdf = PDF::loadView($payload['view'], $payload['data'], [], [
            'format' => $format,
            'orientation' => $payload['data']['orientation'] ?? 'P',
            'margin_top' => data_get($payload, 'data.margins.top', 20),
            'margin_header' => 5,
            'margin_left' => data_get($payload, 'data.margins.left', 14),
            'margin_right' => data_get($payload, 'data.margins.right', 14),
            'margin_bottom' => data_get($payload, 'data.margins.bottom', 24),
            'margin_footer' => 10,
            'title' => $template->name,
            'author' => auth()->user()?->name,
            'display_mode' => 'fullpage',
        ]);

        return [
            'content' => $pdf->output(),
            'renderer' => 'mpdf',
        ];
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{content: string, renderer: string}
     */
    private function renderWithChrome(array $payload, string $filename): array
    {
        if (! class_exists(BrowserFactory::class)) {
            throw new RuntimeException('O renderizador Chrome requer o pacote opcional chrome-php/chrome. Instale com composer require chrome-php/chrome e configure LARAVEL_PDF_DRIVER=chrome ou LARAVEL_PDF_CHROME_BINARY.');
        }

        return $this->renderWithSpatieDriver($payload, $filename, 'chrome');
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{content: string, renderer: string}
     */
    private function renderWithBrowsershot(array $payload, string $filename): array
    {
        if (! class_exists(Browsershot::class)) {
            throw new RuntimeException('O renderizador Browsershot requer o pacote opcional spatie/browsershot e um Chrome/Chromium disponível no servidor.');
        }

        return $this->renderWithSpatieDriver($payload, $filename, 'browsershot');
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{content: string, renderer: string}
     */
    private function renderWithSpatieDriver(array $payload, string $filename, string $driver): array
    {
        $payload = $this->withPrintableCanvasGeometry($payload);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'report-studio-preview-');

        if (! $temporaryPath) {
            throw new RuntimeException('Não foi possível preparar um ficheiro temporário para gerar o PDF.');
        }

        $path = $temporaryPath.'.pdf';

        try {
            unlink($temporaryPath);

            $driverData = $this->dataForDriver($payload, $driver);
            $driverPayload = [
                'view' => $payload['view'],
                'data' => $driverData,
            ];

            $pdfBuilder = SpatiePdf::view(
                $this->viewForDriver($payload, $driver),
                $driverData
            )
                ->driver($driver)
                ->orientation(($payload['data']['orientation'] ?? 'P') === 'L' ? 'Landscape' : 'Portrait');

            $customPageWidth = (float) data_get($payload, 'data.customPageWidth', 0);
            $customPageHeight = (float) data_get($payload, 'data.customPageHeight', 0);

            if ($customPageWidth > 0 && $customPageHeight > 0) {
                $pdfBuilder = $pdfBuilder->paperSize($customPageWidth, $customPageHeight, 'mm');
            } else {
                $format = strtolower((string) ($payload['data']['format'] ?? 'A4'));
                $pdfBuilder = $pdfBuilder->format($format === 'custom' ? 'a4' : $format);
            }

            $pdfBuilder
                ->margins(
                    (float) data_get($payload, 'data.margins.top', 20),
                    (float) data_get($payload, 'data.margins.right', 14),
                    (float) data_get($payload, 'data.margins.bottom', 24),
                    (float) data_get($payload, 'data.margins.left', 14),
                    'mm'
                )
                ->name($filename);

            if ($this->usesBrowserHeaderFooter($driver)) {
                $pdfBuilder
                    ->headerHtml($this->chromeHeaderHtml($driverPayload))
                    ->footerHtml($this->chromeFooterHtml($driverPayload));
            }

            $pdfBuilder->save($path);

            return [
                'content' => file_get_contents($path) ?: '',
                'renderer' => $driver,
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Não foi possível gerar o PDF com o renderizador '.$driver.': '.$exception->getMessage(),
                previous: $exception
            );
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     */
    private function viewForDriver(array $payload, string $driver): string
    {
        if ($this->usesBrowserHeaderFooter($driver) && $payload['view'] === 'PDFs.studios.document') {
            return 'PDFs.studios.chrome-document';
        }

        return $payload['view'];
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array<string, mixed>
     */
    private function dataForDriver(array $payload, string $driver): array
    {
        $data = $this->sanitizeDocumentCssData($payload['data']);

        if (! $this->usesBrowserHeaderFooter($driver)) {
            return $data;
        }

        $backgroundImage = data_get($data, 'backgroundImage');

        $data['resolvedBackgroundImage'] = $backgroundImage
            ? $this->resolveBrowserAssetPath((string) $backgroundImage)
            : ($data['resolvedBackgroundImage'] ?? null);
        $data['browserFirstPageTopOffset'] = max(
            0,
            (float) data_get($data, 'margins.first_top', data_get($data, 'margins.top', 20))
                - (float) data_get($data, 'margins.top', 20)
        );
        $data['fontFamily'] = $this->documentFontFamilyFromPayload([
            'view' => (string) ($payload['view'] ?? 'PDFs.studios.document'),
            'data' => $data,
        ]);

        foreach (['firstPageHeader', 'defaultHeader', 'footerHtml', 'bodyHtml'] as $htmlKey) {
            if (is_string($data[$htmlKey] ?? null)) {
                $data[$htmlKey] = $this->normalizeBrowserAssetReferences($data[$htmlKey]);
            }
        }

        if (is_string($data['styles'] ?? null)) {
            $data['styles'] = $this->normalizeBrowserAssetReferences($data['styles']);
        }

        return $data;
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array<string, mixed>
     */
    private function dataForMpdf(array $payload): array
    {
        $data = $this->sanitizeDocumentCssData($payload['data']);
        $backgroundImage = data_get($data, 'backgroundImage');

        $data['resolvedBackgroundImage'] = $backgroundImage
            ? $this->resolveMpdfAssetPath((string) $backgroundImage)
            : ($data['resolvedBackgroundImage'] ?? null);

        foreach (['firstPageHeader', 'defaultHeader', 'footerHtml', 'bodyHtml'] as $htmlKey) {
            if (is_string($data[$htmlKey] ?? null)) {
                $data[$htmlKey] = $this->normalizeMpdfAssetReferences($data[$htmlKey]);
            }
        }

        if (is_string($data['styles'] ?? null)) {
            $data['styles'] = $this->normalizeMpdfAssetReferences($data['styles']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeDocumentCssData(array $data): array
    {
        $data['backgroundSize'] = $this->safeCssImageFit((string) ($data['backgroundSize'] ?? 'cover'), 'cover');
        $data['backgroundPosition'] = $this->safeCssPosition((string) ($data['backgroundPosition'] ?? 'center center'), 'center center');
        $data['backgroundRepeat'] = $this->safeCssRepeat((string) ($data['backgroundRepeat'] ?? 'no-repeat'), 'no-repeat');

        return $data;
    }

    private function safeCssImageFit(string $value, string $fallback): string
    {
        $value = trim($value);

        return in_array($value, ['cover', 'contain', 'auto'], true) ? $value : $fallback;
    }

    private function safeCssRepeat(string $value, string $fallback): string
    {
        $value = trim($value);

        return in_array($value, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true) ? $value : $fallback;
    }

    private function safeCssPosition(string $value, string $fallback): string
    {
        $value = trim($value);

        if ($value === '') {
            return $fallback;
        }

        $positionToken = '(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)';

        return preg_match('/\A'.$positionToken.'(?:\s+'.$positionToken.')?\z/i', $value) === 1
            ? $value
            : $fallback;
    }

    private function usesBrowserHeaderFooter(string $driver): bool
    {
        return in_array($driver, ['chrome', 'browsershot'], true);
    }

    /**
     * Keep positioned canvas layers anchored to the printable page instead of the
     * variable height of the content fragment that happens to wrap them.
     *
     * @param  array{view: string, data: array<string, mixed>}  $payload
     * @return array{view: string, data: array<string, mixed>}
     */
    private function withPrintableCanvasGeometry(array $payload): array
    {
        $pageHeight = $this->pageHeightInMillimetres($payload);
        $topMargin = $this->marginValue($payload, 'top', 20);
        $bottomMargin = $this->marginValue($payload, 'bottom', 24);
        $firstTopMargin = max(
            0,
            min(250, (float) data_get($payload, 'data.margins.first_top', $topMargin))
        );

        $payload['data']['canvasPageMinHeight'] = max(40, $pageHeight - $topMargin - $bottomMargin);
        $payload['data']['firstCanvasPageMinHeight'] = max(40, $pageHeight - $firstTopMargin - $bottomMargin);

        return $payload;
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     */
    private function pageHeightInMillimetres(array $payload): float
    {
        $customPageWidth = (float) data_get($payload, 'data.customPageWidth', 0);
        $customPageHeight = (float) data_get($payload, 'data.customPageHeight', 0);

        if ($customPageWidth > 0 && $customPageHeight > 0) {
            $dimensions = [$customPageWidth, $customPageHeight];
        } else {
            $dimensions = match (strtoupper((string) data_get($payload, 'data.format', 'A4'))) {
                'A3' => [297.0, 420.0],
                'A5' => [148.0, 210.0],
                'LETTER' => [215.9, 279.4],
                'LEGAL' => [215.9, 355.6],
                default => [210.0, 297.0],
            };
        }

        return data_get($payload, 'data.orientation', 'P') === 'L'
            ? min($dimensions)
            : max($dimensions);
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     */
    private function chromeHeaderHtml(array $payload): string
    {
        return View::make('PDFs.studios.chrome-header', [
            'headerHtml' => $this->normalizeChromePaginationTokens(
                (string) data_get($payload, 'data.defaultHeader', '')
            ),
            'fontFamily' => $this->documentFontFamilyFromPayload($payload),
            'marginLeft' => $this->marginValue($payload, 'left', 14),
            'marginRight' => $this->marginValue($payload, 'right', 14),
        ])->render();
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     */
    private function chromeFooterHtml(array $payload): string
    {
        return View::make('PDFs.studios.chrome-footer', [
            'footerHtml' => $this->normalizeChromePaginationTokens(
                (string) data_get($payload, 'data.footerHtml', '')
            ),
            'fontFamily' => $this->documentFontFamilyFromPayload($payload),
            'marginLeft' => $this->marginValue($payload, 'left', 14),
            'marginRight' => $this->marginValue($payload, 'right', 14),
        ])->render();
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     */
    private function marginValue(array $payload, string $side, float $fallback): float
    {
        $value = data_get($payload, 'data.margins.'.$side, $fallback);

        return max(0, min(200, is_numeric($value) ? (float) $value : $fallback));
    }

    /**
     * @param  array{view: string, data: array<string, mixed>}  $payload
     */
    private function documentFontFamilyFromPayload(array $payload): string
    {
        $styles = (string) data_get($payload, 'data.styles', '');

        if (preg_match('/font-family\s*:\s*([^;{}]+);/i', $styles, $matches) === 1) {
            $fontFamily = trim(str_replace('!important', '', $matches[1]));

            if (preg_match('/\A[-_a-zA-Z0-9\s,"\']+\z/', $fontFamily) === 1) {
                return $fontFamily;
            }
        }

        return 'Arial, DejaVu Sans, sans-serif';
    }

    private function normalizeChromePaginationTokens(string $html): string
    {
        return strtr($html, [
            '{PAGENO}' => '<span class="pageNumber"></span>',
            '{nbpg}' => '<span class="totalPages"></span>',
            '{{page_number}}' => '<span class="pageNumber"></span>',
            '{{total_pages}}' => '<span class="totalPages"></span>',
        ]);
    }

    private function resolveBrowserAssetPath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || str_starts_with($path, '#')) {
            return $path;
        }

        $scheme = parse_url($path, PHP_URL_SCHEME);
        $host = parse_url($path, PHP_URL_HOST);
        $urlPath = parse_url($path, PHP_URL_PATH);
        $publicPath = is_string($urlPath) ? ltrim($urlPath, '/') : '';
        $isPublicAsset = str_starts_with($publicPath, 'storage/')
            || str_starts_with($publicPath, 'images/');

        if (is_string($scheme) && $scheme !== '') {
            if ($this->isSameHostPublicAsset($host, $isPublicAsset)) {
                return 'file://'.public_path($publicPath);
            }

            return $path;
        }

        if (str_starts_with($path, '/') && is_file($path)) {
            return 'file://'.$path;
        }

        return 'file://'.public_path(ltrim($path, '/'));
    }

    private function resolveMpdfAssetPath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || str_starts_with($path, '#')) {
            return $path;
        }

        $scheme = parse_url($path, PHP_URL_SCHEME);
        $host = parse_url($path, PHP_URL_HOST);
        $urlPath = parse_url($path, PHP_URL_PATH);
        $publicPath = is_string($urlPath) ? ltrim($urlPath, '/') : '';
        $isPublicAsset = str_starts_with($publicPath, 'storage/')
            || str_starts_with($publicPath, 'images/');

        if (is_string($scheme) && $scheme !== '') {
            if ($this->isSameHostPublicAsset($host, $isPublicAsset)) {
                return public_path($publicPath);
            }

            return $path;
        }

        if (str_starts_with($path, '/') && is_file($path)) {
            return $path;
        }

        return public_path(ltrim($path, '/'));
    }

    private function isSameHostPublicAsset(?string $host, bool $isPublicAsset): bool
    {
        if (! $isPublicAsset) {
            return false;
        }

        if ($host === null || $host === '') {
            return true;
        }

        $allowedHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            request()->getHost(),
        ]);

        return in_array($host, $allowedHosts, true);
    }

    private function normalizeMpdfAssetReferences(string $html): string
    {
        $html = preg_replace_callback(
            '/\bsrc=([\'"])([^\'"]+)\1/i',
            function (array $matches): string {
                $quote = $matches[1];
                $resolvedPath = e($this->resolveMpdfAssetPath($matches[2]), false);

                return "src={$quote}{$resolvedPath}{$quote}";
            },
            $html
        ) ?? $html;

        return preg_replace_callback(
            '/url\(([\'"]?)([^)\'"]+)\1\)/i',
            function (array $matches): string {
                $quote = $matches[1] !== '' ? $matches[1] : '"';
                $resolvedPath = e($this->resolveMpdfAssetPath($matches[2]), false);

                return "url({$quote}{$resolvedPath}{$quote})";
            },
            $html
        ) ?? $html;
    }

    private function normalizeBrowserAssetReferences(string $html): string
    {
        $html = preg_replace_callback(
            '/\bsrc=([\'"])([^\'"]+)\1/i',
            function (array $matches): string {
                $quote = $matches[1];
                $resolvedPath = e($this->resolveBrowserAssetPath($matches[2]), false);

                return "src={$quote}{$resolvedPath}{$quote}";
            },
            $html
        ) ?? $html;

        return preg_replace_callback(
            '/url\(([\'"]?)([^)\'"]+)\1\)/i',
            function (array $matches): string {
                $quote = $matches[1] !== '' ? $matches[1] : '"';
                $resolvedPath = e($this->resolveBrowserAssetPath($matches[2]), false);

                return "url({$quote}{$resolvedPath}{$quote})";
            },
            $html
        ) ?? $html;
    }
}
