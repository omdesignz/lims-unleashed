@php
    try {
        $documentBrandSettings = $settings ?? app(\App\Settings\GeneralSettings::class);
    } catch (\Throwable) {
        $documentBrandSettings = null;
    }

    $resolveDocumentColor = static function (?string $color, string $fallback): string {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1
            ? $color
            : $fallback;
    };

    $documentPrimaryColor = $resolveDocumentColor($documentBrandSettings?->app_primary_color, '#0f766e');
    $documentSecondaryColor = $resolveDocumentColor($documentBrandSettings?->app_secondary_color, '#17202a');
    $documentAccentColor = $resolveDocumentColor($documentBrandSettings?->app_accent_color, '#0e7490');
@endphp

body.pdf-document {
    font-family: DejaVu Sans, Arial, sans-serif;
    color: {{ $documentSecondaryColor }};
    background: #ffffff;
    font-size: 9pt;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.pdf-document * {
    box-sizing: border-box;
}

.pdf-document img,
.pdf-document svg,
.pdf-document canvas {
    max-width: 100%;
    height: auto;
}

.pdf-document h1,
.pdf-document h2,
.pdf-document h3,
.pdf-document h4,
.pdf-document h5,
.pdf-document h6,
.pdf-document p {
    margin: 0;
}

.pdf-document h1,
.pdf-document h2,
.pdf-document h3,
.pdf-document h4,
.pdf-document h5,
.pdf-document h6 {
    color: {{ $documentPrimaryColor }};
}

.pdf-document h1 {
    font-size: 21pt;
    font-weight: 900;
    letter-spacing: 0;
    line-height: 1.05;
}

.pdf-document h2 {
    font-size: 14pt;
    font-weight: 900;
    letter-spacing: 0;
    line-height: 1.18;
}

.pdf-document h3 {
    font-size: 10.5pt;
    font-weight: 850;
    line-height: 1.25;
}

.pdf-document h6 {
    font-size: 7.3pt;
    font-weight: 800;
    letter-spacing: 0.08em;
    line-height: 1.25;
    text-transform: uppercase;
}

.pdf-document .document-shell {
    border: 0.25mm solid #cbd5e1;
    border-radius: 2.2mm;
    background: #ffffff;
    padding: 8mm;
}

.pdf-document .document-hero {
    border: 0.25mm solid {{ $documentPrimaryColor }};
    border-radius: 2.6mm;
    background: {{ $documentPrimaryColor }};
    color: #ffffff;
    padding: 7mm;
}

.pdf-document .document-hero h1,
.pdf-document .document-hero h2,
.pdf-document .document-hero h3,
.pdf-document .document-hero .value {
    color: #ffffff;
}

.pdf-document .document-hero .muted,
.pdf-document .document-hero .small-text,
.pdf-document .document-hero .label {
    color: #dbeafe;
}

.pdf-document .document-hero .document-kicker,
.pdf-document .document-hero .manual-eyebrow,
.pdf-document .document-hero .studio-kicker {
    color: #a7f3d0;
}

.pdf-document .document-kicker,
.pdf-document .manual-eyebrow,
.pdf-document .studio-kicker {
    color: {{ $documentAccentColor }};
    font-size: 7.5pt;
    font-weight: 900;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.pdf-document .document-subtitle,
.pdf-document .manual-lead,
.pdf-document .studio-lead {
    color: #475569;
    font-size: 10pt;
    line-height: 1.58;
}

.pdf-document .document-hero .document-subtitle,
.pdf-document .document-hero .manual-lead,
.pdf-document .document-hero .studio-lead {
    color: #e2e8f0;
}

.pdf-document .pdf-card,
.pdf-document .info-section,
.pdf-document .report-card,
.pdf-document .metric-card,
.pdf-document .manual-card {
    background: #ffffff;
    border: 0.25mm solid #cbd5e1;
    border-radius: 2.2mm;
}

.pdf-document .metric-card {
    background: #ffffff;
}

.pdf-document .section-header,
.pdf-document .pdf-band,
.pdf-document .report-band {
    background: {{ $documentPrimaryColor }};
    color: #ffffff;
    font-weight: 800;
    letter-spacing: 0.06em;
}

.pdf-document .section-title,
.pdf-document .document-title {
    color: {{ $documentPrimaryColor }};
    font-weight: 900;
    letter-spacing: 0;
}

.pdf-document .small-text,
.pdf-document .muted,
.pdf-document .label {
    color: #64748b;
    overflow-wrap: anywhere;
}

.pdf-document .value,
.pdf-document .highlight-value {
    color: {{ $documentPrimaryColor }};
    font-weight: 800;
    overflow-wrap: anywhere;
}

.pdf-document .document-meta-table,
.pdf-document .document-summary-table,
.pdf-document .signature-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 2mm;
    margin-left: 0;
    margin-right: 0;
}

.pdf-document .document-meta-cell,
.pdf-document .document-summary-cell {
    border: 0.22mm solid #cbd5e1;
    border-radius: 2mm;
    background: #ffffff;
    padding: 3.2mm;
    vertical-align: top;
}

.pdf-document .document-meta-cell .label,
.pdf-document .document-summary-cell .label {
    display: block !important;
    font-size: 7pt;
    font-weight: 900;
    letter-spacing: 0.12em;
    line-height: 1.25;
    text-transform: uppercase;
}

.pdf-document .document-meta-cell .value,
.pdf-document .document-summary-cell .value {
    display: block !important;
    margin-top: 1.3mm;
    font-size: 9.2pt;
    line-height: 1.25;
}

.pdf-document .document-meta-cell .muted,
.pdf-document .document-summary-cell .muted {
    display: block !important;
    line-height: 1.45;
    margin-top: 1.5mm;
}

.pdf-document table {
    border-collapse: collapse;
    max-width: 100%;
    overflow-wrap: anywhere;
}

.pdf-document thead {
    display: table-header-group;
}

.pdf-document tfoot {
    display: table-row-group;
}

.pdf-document tr,
.pdf-document td,
.pdf-document th {
    page-break-inside: avoid;
    break-inside: avoid;
}

.pdf-document td,
.pdf-document th {
    min-width: 0;
}

.pdf-document .data-table,
.pdf-document .worksheet-table,
.pdf-document .report-table,
.pdf-document .tg {
    width: 100%;
    border-collapse: collapse;
    border: 0.25mm solid #cbd5e1;
    margin-top: 3mm;
}

.pdf-document .report-chart,
.pdf-document .report-chart-svg,
.pdf-document .apexcharts-canvas,
.pdf-document .apexcharts-svg {
    display: block;
    max-width: 100%;
    page-break-inside: avoid;
    break-inside: avoid;
}

.pdf-document .report-chart-svg {
    width: 100%;
    height: auto;
}

.pdf-document .data-table th,
.pdf-document .worksheet-table th,
.pdf-document .report-table th,
.pdf-document .tg thead th {
    background: {{ $documentPrimaryColor }} !important;
    color: #ffffff !important;
    border: 0.2mm solid {{ $documentPrimaryColor }} !important;
    padding: 2.5mm 2.2mm !important;
    font-size: 8.2pt !important;
    font-weight: 900 !important;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.pdf-document .data-table td,
.pdf-document .worksheet-table td,
.pdf-document .report-table td,
.pdf-document .tg td {
    border: 0.2mm solid #cbd5e1 !important;
    padding: 2.2mm 2.1mm !important;
    font-size: 8.3pt !important;
    color: #1e293b !important;
    vertical-align: top;
}

.pdf-document .data-table tbody tr:nth-child(even) td,
.pdf-document .worksheet-table tbody tr:nth-child(even) td,
.pdf-document .report-table tbody tr:nth-child(even) td,
.pdf-document .tg tbody tr:nth-child(even) td {
    background: #f8fafc !important;
}

.pdf-document .tg thead tr:nth-child(2) th {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-color: #cbd5e1 !important;
    font-size: 7.2pt !important;
    font-style: italic;
    font-weight: 650 !important;
    letter-spacing: 0;
    text-transform: none;
}

.pdf-document .tg thead tr:nth-child(3) td {
    background: #ffffff !important;
    color: #475569 !important;
    font-size: 7.4pt !important;
}

.pdf-document .bilingual-label,
.pdf-document .tg small {
    display: block;
    color: #64748b;
    font-size: 7.1pt;
    font-weight: 500;
    font-style: italic;
    text-transform: none;
}

.pdf-document .total-box,
.pdf-document .signature-box,
.pdf-document .authenticity-box,
.pdf-document .document-callout {
    border: 0.25mm solid #cbd5e1;
    border-radius: 2mm;
    background: #f8fafc;
}

.pdf-document .document-callout {
    border-left: 1.3mm solid {{ $documentAccentColor }};
    padding: 4mm 5mm;
    color: #475569;
}

.pdf-document .status-badge {
    border-radius: 2mm;
    padding: 1mm 2.5mm;
    font-size: 7.3pt;
    font-weight: 900;
}

.pdf-document .signature-box {
    min-height: 22mm;
    padding: 4mm;
}

.pdf-document .signature-line {
    border-top: 0.22mm solid #8fa096;
    margin-top: 12mm;
    padding-top: 2mm;
    color: #475569;
    font-size: 7.8pt;
}

.pdf-document .page-break {
    page-break-before: always;
}

.pdf-document .keep-together,
.pdf-document .pdf-card,
.pdf-document .info-section,
.pdf-document .signature-box,
.pdf-document .document-shell,
.pdf-document .document-hero,
.pdf-document .document-callout,
.pdf-document .document-summary-table,
.pdf-document .report-chart {
    page-break-inside: avoid;
    break-inside: avoid;
}
