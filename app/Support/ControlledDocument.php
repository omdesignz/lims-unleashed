<?php

namespace App\Support;

use App\Actions\SaveDocumentLogo;
use App\Settings\GeneralSettings;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Building blocks of a controlled laboratory document.
 *
 * Every generated document carries the same control elements, so a reader finds
 * them in the same place: the issuing laboratory, the document title and unique
 * number, its revision and issue date, the page of the total on every page, and
 * a marked end. The blocks are plain tables and paragraphs styled by
 * `PDFs.partials.premium-document-style`, so mPDF and Chrome draw the same page.
 */
class ControlledDocument
{
    /** Shown where a value was not recorded; never a guess. */
    public const NOT_RECORDED = '—';

    /** The box the laboratory's logo is fitted into on the letterhead. */
    public const LOGO_MAX_WIDTH_MM = 38;

    public const LOGO_MAX_HEIGHT_MM = 18;

    public const VERIFICATION_CODE_MM = 21;

    /**
     * The first-page letterhead: laboratory identity, the document control box
     * and, when given, a verification code.
     *
     * Values are template text: `{{tokens}}` are resolved by the caller.
     *
     * @param  array<int, array{0: string, 1: string}>  $controlRows  label and value of each control line
     */
    public static function letterhead(string $title, array $controlRows, bool $withVerificationCode = true): string
    {
        $rows = collect($controlRows)
            ->map(fn (array $row): string => '<tr><td class="doc-control-label">'.$row[0].'</td><td class="doc-control-value">'.$row[1].'</td></tr>')
            ->implode('');
        $verification = $withVerificationCode
            ? '<td class="doc-letterhead-qr" style="width:24mm;">{{verification_qr}}</td>'
            : '';

        return <<<HTML
<table class="doc-letterhead doc-plain">
    <tr>
        <td class="doc-letterhead-logo">{{lab_logo}}</td>
        <td class="doc-letterhead-lab" style="width:40%;">
            <div class="doc-lab-name">{{lab_name}}</div>
            <div class="doc-lab-lines">{{lab_identity}}</div>
        </td>
        <td class="doc-letterhead-control" style="width:66mm;">
            <table class="doc-control doc-plain">
                <tr><td colspan="2" class="doc-control-title">{$title}</td></tr>
                {$rows}
            </table>
        </td>
        {$verification}
    </tr>
</table>
HTML;
    }

    /**
     * The three page furnishings of a document that is not built from a
     * template: letterhead, running header and footer, with every token filled.
     *
     * @param  array<int, array{0: string, 1: string}>  $controlRows  label and value, plain text
     * @return array{letterhead: string, running: string, footer: string}
     */
    public static function furnishings(
        GeneralSettings $settings,
        string $title,
        string $number,
        array $controlRows,
        string $issueDate,
        string $notice = '',
        ?string $verification = null,
        string $revision = '0'
    ): array {
        $tokens = [
            '{{lab_name}}' => e(self::laboratoryName($settings)),
            '{{lab_logo}}' => self::laboratoryLogoHtml($settings),
            '{{lab_identity}}' => self::laboratoryIdentityHtml($settings),
            '{{verification_qr}}' => $verification === null ? '' : self::verificationCodeHtml($verification),
            '{{document_revision}}' => e($revision),
            '{{issue_date}}' => e($issueDate),
        ];
        $rows = array_map(fn (array $row): array => [e($row[0]), e($row[1])], $controlRows);

        return [
            'letterhead' => strtr(self::letterhead(e($title), $rows, $verification !== null), $tokens),
            'running' => strtr(self::runningHeader(e($title), e($number)), $tokens),
            'footer' => strtr(self::footer(e($number), e($notice !== '' ? $notice : self::laboratoryName($settings))), $tokens),
        ];
    }

    /** The line that heads every continuation page. */
    public static function runningHeader(string $title, string $numberToken): string
    {
        return <<<HTML
<table class="doc-running doc-plain"><tr>
    <td>{{lab_name}}</td>
    <td class="doc-right">{$title} n.º {$numberToken}</td>
</tr></table>
HTML;
    }

    /**
     * The foot of every page: what the document is, the notice that travels
     * with it, and the page of the total.
     */
    public static function footer(string $numberToken, string $notice): string
    {
        return <<<HTML
<table class="doc-footer doc-plain"><tr>
    <td style="width:26%;">{$numberToken} · Rev. {{document_revision}}<br>Emitido em {{issue_date}}</td>
    <td class="doc-center" style="width:52%;">{$notice}</td>
    <td class="doc-right" style="width:22%;">Página {PAGENO} de {nbpg}</td>
</tr></table>
HTML;
    }

    /**
     * A section, numbered unless the number is empty. `$html` is trusted markup
     * built by the caller. A short section can be kept on one page with its heading.
     */
    public static function section(int|string $number, string $title, string $html, bool $keepTogether = false): string
    {
        if (trim($html) === '') {
            return '';
        }

        $title = e($title);
        $class = $keepTogether ? 'doc-section doc-keep' : 'doc-section';
        $numberHtml = (string) $number === '' ? '' : '<span class="doc-section-number">'.e((string) $number).'.</span> ';

        return <<<HTML
<div class="{$class}">
    <div class="doc-section-title">{$numberHtml}{$title}</div>
    {$html}
</div>
HTML;
    }

    /**
     * Sections numbered in order, leaving out the ones with nothing recorded so
     * the numbering never skips.
     *
     * @param  array<int, array{0: string, 1: string, 2?: bool}>  $sections  title, markup and whether to keep it on one page
     */
    public static function sections(array $sections): string
    {
        $number = 0;
        $html = '';

        foreach ($sections as $section) {
            if (trim($section[1]) !== '') {
                $html .= self::section(++$number, $section[0], $section[1], $section[2] ?? false);
            }
        }

        return $html;
    }

    /**
     * Label and value pairs, two pairs to a line. Empty values are left out, so
     * the document never prints a label with nothing recorded against it.
     *
     * @param  array<int, array{label: string, value: mixed, wide?: bool, flag?: string, raw?: bool}>  $rows
     */
    public static function keyValueGrid(array $rows): string
    {
        $rows = array_values(array_filter($rows, fn (array $row): bool => filled($row['value'] ?? null)));

        if ($rows === []) {
            return '';
        }

        $lines = [];
        $pending = null;

        foreach ($rows as $row) {
            $cell = self::keyValueCells($row);

            if (! empty($row['wide'])) {
                if ($pending !== null) {
                    $lines[] = $pending.'<td class="doc-kv-label"></td><td class="doc-kv-value"></td>';
                    $pending = null;
                }
                $lines[] = $cell;

                continue;
            }

            if ($pending === null) {
                $pending = $cell;

                continue;
            }

            $lines[] = $pending.$cell;
            $pending = null;
        }

        if ($pending !== null) {
            $lines[] = $pending.'<td class="doc-kv-label"></td><td class="doc-kv-value"></td>';
        }

        return '<table class="doc-kv doc-plain">'.implode('', array_map(fn (string $line): string => '<tr>'.$line.'</tr>', $lines)).'</table>';
    }

    /**
     * @param  array{label: string, value: mixed, wide?: bool, flag?: string, raw?: bool}  $row
     */
    private static function keyValueCells(array $row): string
    {
        $value = empty($row['raw']) ? nl2br(e((string) $row['value']), false) : (string) $row['value'];
        $flag = filled($row['flag'] ?? null) ? ' <span class="doc-flag">('.e((string) $row['flag']).')</span>' : '';
        $wide = empty($row['wide']) ? '' : ' colspan="3"';
        $valueClass = empty($row['wide']) ? 'doc-kv-value' : 'doc-kv-value doc-kv-wide';

        return '<td class="doc-kv-label">'.e($row['label']).$flag.'</td><td class="'.$valueClass.'"'.$wide.'>'.$value.'</td>';
    }

    /**
     * A boxed notice at the head of the body: a draft warning, an amendment.
     */
    public static function notice(string $title, string $text): string
    {
        return '<div class="doc-notice"><span class="doc-notice-title">'.e($title).'</span> '.nl2br(e($text), false).'</div>';
    }

    /**
     * The statements a document carries, as a lettered list.
     *
     * @param  array<int, string>  $statements  plain text
     */
    public static function statements(array $statements): string
    {
        $statements = array_values(array_filter($statements, fn (string $statement): bool => trim($statement) !== ''));

        if ($statements === []) {
            return '';
        }

        return '<ol class="doc-statements" type="a">'
            .implode('', array_map(fn (string $statement): string => '<li>'.nl2br(e($statement), false).'</li>', $statements))
            .'</ol>';
    }

    /**
     * Who authorised the document: signature, name, function and date.
     *
     * @param  array<int, array{name: ?string, role?: ?string, date?: ?string, signature?: ?string, caption?: ?string}>  $signatories
     */
    public static function authorisation(array $signatories): string
    {
        $signatories = array_values(array_filter($signatories, fn (array $signatory): bool => filled($signatory['name'] ?? null) || filled($signatory['caption'] ?? null)));

        if ($signatories === []) {
            return '';
        }

        $signRow = [];
        $nameRow = [];

        foreach ($signatories as $signatory) {
            $signature = filled($signatory['signature'] ?? null)
                ? '<img src="'.e((string) $signatory['signature']).'" alt="" style="max-height:14mm;">'
                : '&nbsp;';
            $lines = array_filter([
                filled($signatory['name'] ?? null) ? '<span class="doc-auth-name">'.e((string) $signatory['name']).'</span>' : null,
                filled($signatory['role'] ?? null) ? '<span class="doc-auth-role">'.e((string) $signatory['role']).'</span>' : null,
                filled($signatory['caption'] ?? null) ? '<span class="doc-auth-role">'.e((string) $signatory['caption']).'</span>' : null,
                filled($signatory['date'] ?? null) ? '<span class="doc-auth-role">'.e((string) $signatory['date']).'</span>' : null,
            ]);
            $signRow[] = '<td class="doc-auth-sign" style="width:45%;">'.$signature.'</td>';
            $nameRow[] = '<td class="doc-auth-lines" style="width:45%;">'.implode('<br>', $lines).'</td>';
        }

        if (count($signatories) === 1) {
            $signRow[] = '<td style="width:45%;"></td>';
            $nameRow[] = '<td style="width:45%;"></td>';
        }

        $spacer = '<td class="doc-auth-gap" style="width:10%;"></td>';

        return '<table class="doc-auth doc-plain"><tr>'.implode($spacer, $signRow).'</tr><tr>'.implode($spacer, $nameRow).'</tr></table>';
    }

    /** The marked end of the document. */
    public static function endMark(string $documentName): string
    {
        return '<div class="doc-end">*** Fim do '.e($documentName).' ***</div>';
    }

    /**
     * The laboratory's identity lines under its name: address, contacts, tax number.
     */
    public static function laboratoryIdentityHtml(GeneralSettings $settings): string
    {
        $contacts = array_filter([
            filled($settings->app_client_contact ?: $settings->app_contact) ? 'Tel. '.($settings->app_client_contact ?: $settings->app_contact) : null,
            $settings->app_client_email ?: $settings->app_email,
        ]);
        $address = self::laboratoryAddress($settings);
        $province = filled($settings->app_client_lab_province) && ! str_contains((string) $address, (string) $settings->app_client_lab_province)
            ? $settings->app_client_lab_province
            : null;
        $lines = array_filter([
            self::legalEntity($settings),
            implode(', ', array_filter([$address, $province])),
            implode(' · ', $contacts),
            filled($settings->app_client_nif ?: $settings->app_nif) ? 'NIF '.($settings->app_client_nif ?: $settings->app_nif) : null,
        ]);

        return implode('<br>', array_map(fn (string $line): string => e($line), $lines));
    }

    /** Where the laboratory works; the organisation's address when the laboratory has none of its own. */
    public static function laboratoryAddress(GeneralSettings $settings): ?string
    {
        $address = trim((string) ($settings->app_client_lab_address ?: $settings->app_client_address));

        return $address === '' ? null : $address;
    }

    /**
     * The laboratory's accreditation as a report states it ("Acreditado por
     * IPAC, certificado n.º L0001"), or null when no certificate number is
     * configured: without it the laboratory makes no claim of accreditation.
     */
    public static function accreditation(GeneralSettings $settings): ?string
    {
        $number = trim((string) $settings->app_client_lab_accreditation_number);

        if ($number === '') {
            return null;
        }

        $body = trim((string) $settings->app_client_lab_accreditation_body);

        return 'Acreditado'.($body !== '' ? ' por '.$body : '').', certificado n.º '.$number;
    }

    public static function laboratoryName(GeneralSettings $settings): string
    {
        return (string) ($settings->app_client_lab_name ?: $settings->app_client_name ?: $settings->app_name ?: 'Laboratório');
    }

    /** The owning organisation, when the laboratory is a unit of a larger one. */
    private static function legalEntity(GeneralSettings $settings): ?string
    {
        $entity = $settings->app_client_name;

        return filled($entity) && $entity !== self::laboratoryName($settings) ? (string) $entity : null;
    }

    /**
     * The laboratory's mark, or nothing when none is configured: a document
     * must not print a placeholder where a mark would be.
     */
    public static function laboratoryLogoHtml(GeneralSettings $settings): string
    {
        $uploaded = trim((string) ($settings->app_document_logo ?? ''));

        if ($uploaded !== '' && ! str_contains($uploaded, '..') && Storage::disk(SaveDocumentLogo::DISK)->exists($uploaded)) {
            $html = self::localLogoHtml(Storage::disk(SaveDocumentLogo::DISK)->path($uploaded));

            if ($html !== '') {
                return $html;
            }
        }

        $source = trim((string) ($settings->app_logo_url ?: ''));

        if ($source === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $source) === 1) {
            return '<img src="'.e($source).'" alt="" style="max-width:'.self::LOGO_MAX_WIDTH_MM.'mm; max-height:'.self::LOGO_MAX_HEIGHT_MM.'mm;">';
        }

        $path = is_file($source) ? $source : public_path(ltrim($source, '/'));

        return is_file($path) ? self::localLogoHtml($path) : '';
    }

    /**
     * A local logo embedded as data, its size fitted inside the letterhead
     * box and written on the element: mPDF ignores max-width on images.
     */
    private static function localLogoHtml(string $path): string
    {
        $uri = self::imageDataUri($path);

        if ($uri === null) {
            return '';
        }

        $dimensions = @getimagesize($path);

        if (! is_array($dimensions) || $dimensions[0] < 1 || $dimensions[1] < 1) {
            return '<img src="'.$uri.'" alt="" style="max-width:'.self::LOGO_MAX_WIDTH_MM.'mm; max-height:'.self::LOGO_MAX_HEIGHT_MM.'mm;">';
        }

        $scale = min(self::LOGO_MAX_WIDTH_MM / $dimensions[0], self::LOGO_MAX_HEIGHT_MM / $dimensions[1]);
        $width = round($dimensions[0] * $scale, 1);
        $height = round($dimensions[1] * $scale, 1);

        return '<img src="'.$uri.'" alt="" style="width:'.$width.'mm; height:'.$height.'mm;">';
    }

    /**
     * A local image as a data URI, so the document does not depend on how each
     * renderer resolves file paths. Null for anything that is not a small image.
     */
    public static function imageDataUri(string $path): ?string
    {
        $type = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => null,
        };

        if ($type === null || ! is_readable($path) || filesize($path) > 2 * 1024 * 1024) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : 'data:'.$type.';base64,'.base64_encode($contents);
    }

    /**
     * A QR code of the document's verification text, as an inline image both
     * renderers can draw.
     */
    /**
     * How a QR code is sized wherever one is printed or shown: by its width
     * alone, so its height always follows and the code stays square, whatever
     * room the page or the screen leaves it.
     */
    public static function squareCodeStyle(float $millimetres): string
    {
        return 'width:'.round($millimetres, 1).'mm; height:auto; max-width:none; aspect-ratio:1 / 1;';
    }

    public static function verificationCodeHtml(string $content, string $caption = 'Verificação'): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        try {
            $code = new QrCode(
                data: $content,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 240,
                margin: 0,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            );
            $uri = extension_loaded('gd')
                ? (new PngWriter)->write($code)->getDataUri()
                : (new SvgWriter)->write($code)->getDataUri();
        } catch (Throwable) {
            return '';
        }

        return '<img src="'.$uri.'" alt="'.e($caption).'" style="'.self::squareCodeStyle(self::VERIFICATION_CODE_MM).'"><div class="doc-qr-caption">'.e($caption).'</div>';
    }
}
