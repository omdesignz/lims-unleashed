<?php

namespace App\Support;

use App\Models\VAPLabel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Str;
use Milon\Barcode\DNS1D;
use Mpdf\Mpdf;
use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;
use Throwable;

class VAPLabelPdfRenderer
{
    /**
     * @return array{chrome: array{available: bool, binary_path: ?string, configured: bool, executable: bool, description: string}, fallback: array{available: bool, description: string}}
     */
    public function capabilities(): array
    {
        $chromeBinary = config('laravel-pdf.chrome.chrome_binary');
        $configuredBinary = is_string($chromeBinary) && $chromeBinary !== '';
        $executableBinary = $configuredBinary && is_executable($chromeBinary);
        $chromeAvailable = class_exists(BrowserFactory::class)
            && (! $configuredBinary || $executableBinary);

        return [
            'chrome' => [
                'available' => $chromeAvailable,
                'binary_path' => $configuredBinary ? $chromeBinary : null,
                'configured' => $configuredBinary,
                'executable' => $executableBinary,
                'description' => $chromeAvailable
                    ? 'Chrome PDF activo para etiquetas com CSS moderno, cortes, QR, imagens e dimensões personalizadas.'
                    : 'Chrome PDF indisponível; o sistema usa mPDF como fallback seguro.',
            ],
            'fallback' => [
                'available' => true,
                'description' => 'Fallback mPDF disponível para manter a impressão operacional caso Chrome falhe.',
            ],
        ];
    }

    /**
     * @return array{content: string, renderer: string}
     */
    public function renderPreview(VAPLabel $label, LabelStudioSourceResolver $resolver): array
    {
        $sourcePreview = $resolver->resolve(
            data_get($label->template_data, 'source_type'),
            data_get($label->template_data, 'source_id')
        );
        $sampleQr = data_get($sourcePreview, 'qr_content', 'LAB-'.strtoupper(Str::random(8)));
        $sampleBarcode = data_get($sourcePreview, 'barcode_content', 'BAR-'.strtoupper(Str::random(6)));
        $sampleText = $sourcePreview
            ? $resolver->renderContent($label->content, $sourcePreview)
            : 'Exemplo de Etiqueta';

        $items = [
            $this->prepareItem($label, [
                'content' => $sampleText,
                'qr_content' => $sampleQr,
                'barcode_content' => $sampleBarcode,
            ]),
        ];

        return $this->renderWithPreferredDriver(
            $label,
            $items,
            [
                'filename' => 'preview-etiqueta.pdf',
                'paper_width' => (float) $label->width + 10,
                'paper_height' => (float) $label->height + 10,
                'margin' => 5,
                'columns' => 1,
                'rows' => 1,
                'spacing' => 0,
                'include_cutouts' => true,
                'debug_label' => $label->name.' | '.$label->width.'x'.$label->height.'mm | Pré-visualização',
            ],
            fn () => $this->renderLegacyPreview($label, $sampleText, $sampleQr, $sampleBarcode)
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array{content: string, renderer: string}
     */
    public function renderLabels(VAPLabel $label, array $data, array $options, LabelStudioSourceResolver $resolver): array
    {
        $sourcePreview = $resolver->resolve(
            data_get($label->template_data, 'source_type'),
            data_get($label->template_data, 'source_id')
        );
        $items = collect($data)
            ->map(function (array $item) use ($label, $resolver, $sourcePreview): array {
                $content = $sourcePreview
                    ? $resolver->renderContent((string) $item['content'], $sourcePreview)
                    : (string) $item['content'];

                return $this->prepareItem($label, [
                    'content' => $content,
                    'qr_content' => $item['qr_content'] ?? data_get($sourcePreview, 'qr_content'),
                    'barcode_content' => $item['barcode_content'] ?? data_get($sourcePreview, 'barcode_content'),
                ]);
            })
            ->values()
            ->all();

        $labelsPerPage = max(1, min(100, (int) data_get($options, 'labels_per_page', 1)));
        $margin = (float) data_get($options, 'margin', 5);

        $paper = $this->paperDimensions($options);

        return $this->renderWithPreferredDriver(
            $label,
            $items,
            [
                'filename' => 'etiquetas-'.Str::slug($label->name).'-'.now()->format('Y-m-d-H-i-s').'.pdf',
                'paper_width' => $paper['width'],
                'paper_height' => $paper['height'],
                'margin' => $margin,
                'columns' => 1,
                'rows' => $labelsPerPage,
                'spacing' => $margin,
                'include_cutouts' => (bool) data_get($options, 'include_cutouts', true),
            ],
            fn () => $this->renderLegacyLabels($label, $items, $options)
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array{content: string, renderer: string}
     */
    public function renderBatch(VAPLabel $label, array $data, array $options): array
    {
        $items = collect($data)
            ->map(fn (array $item): array => $this->prepareItem($label, $item))
            ->values()
            ->all();

        $paper = $this->paperDimensions($options);

        return $this->renderWithPreferredDriver(
            $label,
            $items,
            [
                'filename' => 'etiquetas-lote-'.Str::slug($label->name).'-'.now()->format('Y-m-d-H-i-s').'.pdf',
                'paper_width' => $paper['width'],
                'paper_height' => $paper['height'],
                'margin' => 10,
                'columns' => max(1, min(10, (int) data_get($options, 'columns', 2))),
                'rows' => max(1, min(50, (int) data_get($options, 'rows', 4))),
                'spacing' => (float) data_get($options, 'spacing', 5),
                'include_cutouts' => (bool) data_get($options, 'include_cutouts', true),
            ],
            fn () => $this->renderLegacyBatch($label, $data, $options)
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $options
     * @return array{content: string, renderer: string}
     */
    private function renderWithPreferredDriver(VAPLabel $label, array $items, array $options, callable $fallback): array
    {
        if (! $this->canUseChrome()) {
            return [
                'content' => $fallback(),
                'renderer' => 'mpdf',
            ];
        }

        try {
            return [
                'content' => $this->renderWithChrome($label, $items, $options),
                'renderer' => 'chrome',
            ];
        } catch (Throwable) {
            return [
                'content' => $fallback(),
                'renderer' => 'mpdf-fallback',
            ];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $options
     */
    private function renderWithChrome(VAPLabel $label, array $items, array $options): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'vap-labels-');

        if (! $temporaryPath) {
            throw new RuntimeException('Não foi possível preparar ficheiro temporário para etiquetas.');
        }

        $path = $temporaryPath.'.pdf';

        try {
            unlink($temporaryPath);

            SpatiePdf::view('PDFs.labels.chrome-sheet', [
                'label' => $label,
                'items' => $items,
                'options' => $options,
                'logoSrc' => $this->resolveAssetSource($label->logo_path),
            ])
                ->driver('chrome')
                ->paperSize((float) $options['paper_width'], (float) $options['paper_height'], 'mm')
                ->margins(0, 0, 0, 0, 'mm')
                ->name((string) $options['filename'])
                ->save($path);

            return file_get_contents($path) ?: '';
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function canUseChrome(): bool
    {
        if (! class_exists(BrowserFactory::class)) {
            return false;
        }

        $chromeBinary = config('laravel-pdf.chrome.chrome_binary');

        return ! is_string($chromeBinary) || $chromeBinary === '' || is_executable($chromeBinary);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{width: float, height: float}
     */
    private function paperDimensions(array $options): array
    {
        $sizes = [
            'A3' => ['width' => 297.0, 'height' => 420.0],
            'A4' => ['width' => 210.0, 'height' => 297.0],
            'LETTER' => ['width' => 215.9, 'height' => 279.4],
            'LEGAL' => ['width' => 215.9, 'height' => 355.6],
        ];
        $pageSize = strtoupper((string) data_get($options, 'page_size', 'A4'));
        $paper = $sizes[$pageSize] ?? $sizes['A4'];

        if (data_get($options, 'orientation') === 'landscape') {
            return [
                'width' => $paper['height'],
                'height' => $paper['width'],
            ];
        }

        return $paper;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{content: string, qr_content: ?string, qr_code_image: ?string, barcode_content: ?string, barcode_image: ?string}
     */
    private function prepareItem(VAPLabel $label, array $item): array
    {
        $qrContent = filled($item['qr_content'] ?? null) ? (string) $item['qr_content'] : null;
        $barcodeContent = filled($item['barcode_content'] ?? null) ? (string) $item['barcode_content'] : null;

        return [
            'content' => (string) ($item['content'] ?? ''),
            'qr_content' => $qrContent,
            'qr_code_image' => $label->has_qr_code && $qrContent ? $this->qrCodeImage($qrContent) : null,
            'barcode_content' => $barcodeContent,
            'barcode_image' => $label->has_barcode && $barcodeContent ? $this->barcodeImage($barcodeContent, $label->barcode_type) : null,
        ];
    }

    private function qrCodeImage(string $content): string
    {
        $qrCode = new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $result = (new PngWriter)->write($qrCode);

        return $result->getDataUri();
    }

    private function barcodeImage(string $content, ?string $type): ?string
    {
        if (! class_exists(DNS1D::class)) {
            return null;
        }

        try {
            $barcode = (new DNS1D)->getBarcodePNG($content, $this->normalizeBarcodeType($type));

            return 'data:image/png;base64,'.$barcode;
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeBarcodeType(?string $type): string
    {
        return match (strtoupper((string) $type)) {
            'CODE39', 'C39' => 'C39',
            'EAN13', 'EAN-13' => 'EAN13',
            'EAN8', 'EAN-8' => 'EAN8',
            default => 'C128',
        };
    }

    private function resolveAssetSource(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) || str_starts_with($path, 'data:')) {
            return $path;
        }

        $publicPath = public_path(ltrim($path, '/'));

        return is_file($publicPath) ? 'file://'.$publicPath : $path;
    }

    private function renderLegacyPreview(VAPLabel $label, string $sampleText, string $sampleQr, string $sampleBarcode): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [(float) $label->width + 10, (float) $label->height + 10],
            'default_font_size' => $label->font_size,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        $mpdf->WriteHTML(view('PDFs.labels.preview', [
            'label' => $label,
            'show_cutouts' => true,
            'sample_text' => $sampleText,
            'sample_qr' => $sampleQr,
            'sample_barcode' => $sampleBarcode,
        ])->render());

        return $mpdf->Output('', 'S');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $options
     */
    private function renderLegacyLabels(VAPLabel $label, array $items, array $options): string
    {
        $labelsPerPage = max(1, min(100, (int) data_get($options, 'labels_per_page', 1)));
        $margin = (float) data_get($options, 'margin', 5);
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => $label->font_size,
            'margin_left' => $margin,
            'margin_right' => $margin,
            'margin_top' => $margin,
            'margin_bottom' => $margin,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        foreach ($items as $index => $item) {
            $mpdf->WriteHTML(view('PDFs.labels.generate', [
                'label' => $label,
                'content' => $item['content'],
                'qr_content' => $item['qr_content'],
                'qr_code_image' => $item['qr_code_image'],
                'barcode_content' => $item['barcode_content'],
                'include_cutouts' => (bool) data_get($options, 'include_cutouts', true),
                'item_index' => $index,
                'labels_per_page' => $labelsPerPage,
                'margin' => $margin,
            ])->render());

            if (($index + 1) % $labelsPerPage === 0 && $index < count($items) - 1) {
                $mpdf->AddPage();
            }
        }

        return $mpdf->Output('', 'S');
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  array<string, mixed>  $options
     */
    private function renderLegacyBatch(VAPLabel $label, array $data, array $options): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => $label->font_size,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        $mpdf->WriteHTML(view('PDFs.labels.batch', [
            'label' => $label,
            'data' => $data,
            'columns' => max(1, min(10, (int) data_get($options, 'columns', 2))),
            'rows' => max(1, min(50, (int) data_get($options, 'rows', 4))),
            'spacing' => (float) data_get($options, 'spacing', 5),
            'include_cutouts' => (bool) data_get($options, 'include_cutouts', true),
        ])->render());

        return $mpdf->Output('', 'S');
    }
}
