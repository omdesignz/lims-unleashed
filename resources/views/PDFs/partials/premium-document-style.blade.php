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

    // The laboratory's primary colour, darkened if needed to be read on white paper.
    $documentPrimaryColor = \App\Support\BrandTheme::documentAccent($documentBrandSettings);
    $documentSecondaryColor = $resolveDocumentColor($documentBrandSettings?->app_secondary_color, '#17202a');
    $documentAccentColor = $resolveDocumentColor($documentBrandSettings?->app_accent_color, '#0e7490');
@endphp

{{--
    Controlled-document language shared by every generated PDF.

    One sober system: white page, near-black text, hairline rules, compact tables.
    The laboratory's primary colour is used only for the title rule and section
    numbers. Layout uses tables so mPDF and Chrome draw the same page.
--}}
body.pdf-document {
    font-family: DejaVu Sans, Arial, sans-serif;
    color: #111827;
    background: #ffffff;
    font-size: 8.6pt;
    line-height: 1.4;
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
    color: #111827;
}

.pdf-document h1 {
    font-size: 15pt;
    font-weight: bold;
    letter-spacing: 0;
    line-height: 1.15;
}

.pdf-document h2 {
    font-size: 11pt;
    font-weight: bold;
    letter-spacing: 0;
    line-height: 1.2;
}

.pdf-document h3 {
    font-size: 9.4pt;
    font-weight: bold;
    line-height: 1.25;
}

.pdf-document h6 {
    font-size: 7pt;
    font-weight: bold;
    letter-spacing: 0.06em;
    line-height: 1.25;
    text-transform: uppercase;
}

/* ---- Document control: letterhead, running header, footer ---- */

.doc-letterhead {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 0.5mm solid {{ $documentPrimaryColor }};
}

.doc-letterhead td {
    border: 0;
    padding: 0 0 3mm 0;
    vertical-align: top;
}

.doc-letterhead .doc-letterhead-logo {
    width: 1%;
    vertical-align: middle;
    white-space: nowrap;
}

.doc-letterhead .doc-letterhead-logo img {
    max-width: 38mm;
    max-height: 18mm;
    margin-right: 4mm;
}

.doc-lab-name {
    color: #111827;
    font-size: 11.5pt;
    font-weight: bold;
    line-height: 1.2;
}

.doc-lab-lines {
    margin-top: 1mm;
    color: #374151;
    font-size: 7.4pt;
    line-height: 1.45;
}

.doc-letterhead .doc-letterhead-control {
    width: 64mm;
    padding-left: 4mm;
}

.doc-letterhead .doc-letterhead-qr {
    width: 24mm;
    padding-left: 3mm;
    text-align: right;
}

.doc-letterhead .doc-letterhead-qr img {
    width: 21mm;
    height: auto;
    max-width: none;
    aspect-ratio: 1 / 1;
}

.doc-qr-caption {
    margin-top: 0.6mm;
    color: #4b5563;
    font-size: 5.8pt;
    line-height: 1.2;
    text-align: center;
}

.doc-control {
    width: 100%;
    border-collapse: collapse;
    border: 0.25mm solid #6b7280;
}

.doc-control td {
    border: 0.2mm solid #9ca3af;
    padding: 1.1mm 1.8mm;
    font-size: 7.6pt;
    line-height: 1.25;
    vertical-align: middle;
}

.doc-control .doc-control-title {
    background: #f3f4f6;
    color: #111827;
    font-size: 9.6pt;
    font-weight: bold;
    letter-spacing: 0.02em;
    text-align: center;
    text-transform: uppercase;
}

.doc-control .doc-control-label {
    width: 38%;
    color: #4b5563;
    font-size: 6.8pt;
    text-transform: uppercase;
}

.doc-control .doc-control-value {
    color: #111827;
    font-weight: bold;
}

.doc-running {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 0.2mm solid #9ca3af;
}

.doc-running td {
    border: 0;
    padding: 0 0 1.2mm 0;
    color: #4b5563;
    font-size: 7pt;
    line-height: 1.3;
    vertical-align: bottom;
}

.doc-footer {
    width: 100%;
    border-collapse: collapse;
    border-top: 0.2mm solid #9ca3af;
}

.doc-footer td {
    border: 0;
    padding: 1.2mm 0 0 0;
    color: #4b5563;
    font-size: 6.6pt;
    line-height: 1.35;
    vertical-align: top;
}

.doc-right {
    text-align: right;
}

.doc-center {
    text-align: center;
}

/* ---- Body: notices, numbered sections, key-value grids ---- */

.doc-notice {
    margin: 3mm 0 0 0;
    border: 0.3mm solid #111827;
    padding: 1.8mm 2.4mm;
    color: #111827;
    font-size: 8pt;
    line-height: 1.4;
}

.doc-notice-title {
    font-weight: bold;
    text-transform: uppercase;
}

.doc-section {
    margin: 3.2mm 0 0 0;
}

.doc-keep {
    page-break-inside: avoid;
    break-inside: avoid;
}

.doc-section-title {
    margin: 0 0 1.2mm 0;
    border-bottom: 0.25mm solid #111827;
    padding: 0 0 0.8mm 0;
    color: #111827;
    font-size: 8.4pt;
    font-weight: bold;
    letter-spacing: 0.03em;
    line-height: 1.25;
    text-transform: uppercase;
}

.doc-section-number {
    color: {{ $documentPrimaryColor }};
}

.doc-kv {
    width: 100%;
    border-collapse: collapse;
}

.doc-kv td {
    border: 0;
    border-bottom: 0.15mm solid #d1d5db;
    padding: 0.8mm 1.6mm 0.8mm 0;
    font-size: 8.2pt;
    line-height: 1.3;
    vertical-align: top;
}

.doc-kv .doc-kv-label {
    width: 19%;
    color: #4b5563;
    font-size: 7.2pt;
}

.doc-kv .doc-kv-value {
    width: 31%;
    color: #111827;
}

.doc-kv .doc-kv-wide {
    width: 81%;
}

.doc-subheading {
    margin: 2mm 0 0.8mm 0;
    color: #111827;
    font-size: 7.8pt;
    font-weight: bold;
}

.doc-flag {
    color: #4b5563;
    font-size: 6.4pt;
    vertical-align: super;
}

/* ---- Parties and split blocks (commercial documents, certificates) ---- */

.doc-parties {
    width: 100%;
    border-collapse: collapse;
    margin: 3.2mm 0 0 0;
}

.doc-parties .doc-party {
    width: 50%;
    border: 0.2mm solid #9ca3af;
    padding: 2mm 2.6mm;
    vertical-align: top;
}

.doc-party-label {
    color: #4b5563;
    font-size: 6.8pt;
    font-weight: bold;
    letter-spacing: 0.06em;
    line-height: 1.25;
    text-transform: uppercase;
}

.doc-party-lines {
    margin-top: 0.8mm;
    color: #111827;
    font-size: 8.2pt;
    line-height: 1.45;
}

.doc-split {
    width: 100%;
    border-collapse: collapse;
    margin: 3.2mm 0 0 0;
    page-break-inside: avoid;
    break-inside: avoid;
}

.doc-split td {
    border: 0;
    padding: 0;
    vertical-align: top;
}

.doc-split .doc-split-notes {
    width: 54%;
    padding-right: 6mm;
}

.doc-split .doc-split-totals {
    width: 46%;
}

.doc-signature {
    margin: 9mm 0 0 0;
    width: 46%;
    font-size: 8pt;
    line-height: 1.4;
}

/* ---- Results ---- */

.doc-results {
    width: 100%;
    border-collapse: collapse;
    border-top: 0.3mm solid #111827;
    border-bottom: 0.3mm solid #111827;
}

.doc-results th {
    border: 0;
    border-bottom: 0.25mm solid #111827;
    background: #f3f4f6;
    padding: 1.5mm 1.6mm;
    color: #111827;
    font-size: 7.2pt;
    font-weight: bold;
    line-height: 1.25;
    text-align: left;
    vertical-align: bottom;
}

.doc-results td {
    border: 0;
    border-bottom: 0.15mm solid #d1d5db;
    padding: 1.1mm 1.6mm;
    color: #111827;
    font-size: 8.2pt;
    line-height: 1.3;
    vertical-align: top;
}

.doc-results .doc-results-group td {
    background: #fafafa;
    border-bottom: 0.2mm solid #9ca3af;
    color: #111827;
    font-size: 7.4pt;
    font-weight: bold;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

/* A grid for records filled in by hand: every cell is ruled. */
.doc-grid th,
.doc-grid td {
    border: 0.2mm solid #6b7280;
}

.doc-not-applicable {
    background: #f3f4f6;
    color: #6b7280;
}

.doc-results .doc-num {
    text-align: right;
    white-space: nowrap;
}

.doc-results .doc-result-value {
    font-weight: bold;
}

.doc-nonconforming {
    font-weight: bold;
}

.doc-notes {
    margin: 1.2mm 0 0 0;
    color: #374151;
    font-size: 6.8pt;
    line-height: 1.45;
}

.doc-statements {
    margin: 0;
    padding: 0 0 0 4mm;
    color: #111827;
    font-size: 7.8pt;
    line-height: 1.45;
}

.doc-statements li {
    margin: 0 0 0.6mm 0;
}

.doc-text {
    color: #111827;
    font-size: 8.2pt;
    line-height: 1.45;
}

/* ---- Authorisation and end mark ---- */

.doc-auth {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: avoid;
    break-inside: avoid;
}

.doc-auth td {
    border: 0;
    padding: 0;
    font-size: 8pt;
    line-height: 1.4;
}

.doc-auth .doc-auth-sign {
    height: 15mm;
    border-bottom: 0.25mm solid #111827;
    vertical-align: bottom;
}

.doc-auth .doc-auth-lines {
    padding-top: 1mm;
    vertical-align: top;
}

.doc-auth-name {
    font-weight: bold;
}

.doc-auth-role {
    color: #4b5563;
    font-size: 7.2pt;
}

.doc-end {
    margin: 4mm 0 0 0;
    color: #374151;
    font-size: 7.4pt;
    letter-spacing: 0.08em;
    text-align: center;
    text-transform: uppercase;
}

/* ---- Totals (commercial documents) ---- */

.doc-totals {
    width: 100%;
    border-collapse: collapse;
}

.doc-totals td {
    border: 0;
    border-bottom: 0.15mm solid #d1d5db;
    padding: 1.2mm 1.6mm;
    font-size: 8.2pt;
}

.doc-totals .doc-totals-grand td {
    border-top: 0.3mm solid #111827;
    border-bottom: 0.3mm solid #111827;
    font-size: 9.2pt;
    font-weight: bold;
}

/* ---- Earlier class names, drawn in the same language ----
   General helper names (.label, .value, .muted) stay scoped to the document root, so a
   document with its own styles keeps them; table classes apply everywhere. */

.pdf-document .document-shell {
    border: 0;
    background: #ffffff;
    padding: 0;
}

.pdf-document .document-hero {
    border: 0.25mm solid {{ $documentPrimaryColor }};
    background: {{ $documentPrimaryColor }};
    color: #ffffff;
    padding: 4mm 5mm;
}

.pdf-document .document-hero h1,
.pdf-document .document-hero h2,
.pdf-document .document-hero h3,
.pdf-document .document-hero .value {
    color: #ffffff;
}

.pdf-document .document-hero .muted,
.pdf-document .document-hero .small-text,
.pdf-document .document-hero .label,
.pdf-document .document-hero .document-kicker,
.pdf-document .document-hero .manual-eyebrow,
.pdf-document .document-hero .studio-kicker,
.pdf-document .document-hero .document-subtitle,
.pdf-document .document-hero .manual-lead,
.pdf-document .document-hero .studio-lead {
    color: #e5e7eb;
}

.pdf-document .document-kicker,
.pdf-document .manual-eyebrow,
.pdf-document .studio-kicker {
    color: #4b5563;
    font-size: 7pt;
    font-weight: bold;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.pdf-document .document-subtitle,
.pdf-document .manual-lead,
.pdf-document .studio-lead {
    color: #374151;
    font-size: 8.8pt;
    line-height: 1.5;
}

.pdf-document .pdf-card,
.pdf-document .info-section,
.pdf-document .report-card,
.pdf-document .metric-card,
.pdf-document .manual-card {
    background: #ffffff;
    border: 0.2mm solid #9ca3af;
    border-radius: 0;
}

.pdf-document .section-header,
.pdf-document .pdf-band,
.pdf-document .report-band {
    background: #f3f4f6;
    border-bottom: 0.25mm solid #111827;
    color: #111827;
    font-weight: bold;
    letter-spacing: 0.03em;
}

.pdf-document .section-title,
.pdf-document .document-title {
    color: #111827;
    font-weight: bold;
    letter-spacing: 0;
}

.pdf-document .small-text,
.pdf-document .muted,
.pdf-document .label {
    color: #4b5563;
    overflow-wrap: anywhere;
}

.pdf-document .value,
.pdf-document .highlight-value {
    color: #111827;
    font-weight: bold;
    overflow-wrap: anywhere;
}

.pdf-document .document-meta-table,
.pdf-document .document-summary-table,
.pdf-document .signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-left: 0;
    margin-right: 0;
}

.pdf-document .document-meta-cell,
.pdf-document .document-summary-cell {
    border: 0.2mm solid #9ca3af;
    border-radius: 0;
    background: #ffffff;
    padding: 2mm 2.4mm;
    vertical-align: top;
}

.pdf-document .document-meta-cell .label,
.pdf-document .document-summary-cell .label {
    display: block !important;
    font-size: 6.8pt;
    font-weight: bold;
    letter-spacing: 0.06em;
    line-height: 1.25;
    text-transform: uppercase;
}

.pdf-document .document-meta-cell .value,
.pdf-document .document-summary-cell .value {
    display: block !important;
    margin-top: 0.8mm;
    font-size: 8.8pt;
    line-height: 1.25;
}

.pdf-document .document-meta-cell .muted,
.pdf-document .document-summary-cell .muted {
    display: block !important;
    line-height: 1.4;
    margin-top: 0.8mm;
}

.pdf-document .pdf-document table {
    border-collapse: collapse;
    max-width: 100%;
    overflow-wrap: anywhere;
}

.pdf-document .pdf-document thead {
    display: table-header-group;
}

.pdf-document .pdf-document tfoot {
    display: table-row-group;
}

.pdf-document .pdf-document tr,
.pdf-document .pdf-document td,
.pdf-document .pdf-document th {
    page-break-inside: avoid;
    break-inside: avoid;
}

.pdf-document .pdf-document td,
.pdf-document .pdf-document th {
    min-width: 0;
}

.data-table,
.worksheet-table,
.report-table,
.tg {
    width: 100%;
    border-collapse: collapse;
    border: 0;
    border-top: 0.3mm solid #111827;
    border-bottom: 0.3mm solid #111827;
    margin-top: 2mm;
}

.report-chart,
.report-chart-svg,
.apexcharts-canvas,
.apexcharts-svg {
    display: block;
    max-width: 100%;
    page-break-inside: avoid;
    break-inside: avoid;
}

.report-chart-svg {
    width: 100%;
    height: auto;
}

.data-table th,
.worksheet-table th,
.report-table th,
.tg thead th {
    background: #f3f4f6 !important;
    color: #111827 !important;
    border: 0 !important;
    border-bottom: 0.25mm solid #111827 !important;
    padding: 1.5mm 1.6mm !important;
    font-size: 7.2pt !important;
    font-weight: bold !important;
    letter-spacing: 0.02em;
    text-transform: none;
}

.data-table td,
.worksheet-table td,
.report-table td,
.tg td {
    border: 0 !important;
    border-bottom: 0.15mm solid #d1d5db !important;
    padding: 1.4mm 1.6mm !important;
    font-size: 8.2pt !important;
    color: #111827 !important;
    vertical-align: top;
}

.tg thead tr:nth-child(2) th {
    background: #ffffff !important;
    color: #4b5563 !important;
    font-size: 6.8pt !important;
    font-style: italic;
    font-weight: normal !important;
    letter-spacing: 0;
    text-transform: none;
}

.tg thead tr:nth-child(3) td {
    background: #ffffff !important;
    color: #4b5563 !important;
    font-size: 7pt !important;
}

.bilingual-label,
.tg small {
    display: block;
    color: #4b5563;
    font-size: 6.6pt;
    font-weight: normal;
    font-style: italic;
    text-transform: none;
}

.pdf-document .total-box,
.pdf-document .signature-box,
.pdf-document .authenticity-box,
.pdf-document .document-callout {
    border: 0.2mm solid #9ca3af;
    border-radius: 0;
    background: #ffffff;
}

.pdf-document .document-callout {
    border-left: 0.8mm solid #111827;
    padding: 2mm 3mm;
    color: #111827;
}

.pdf-document .status-badge {
    border: 0.2mm solid #111827;
    border-radius: 0;
    padding: 0.6mm 1.8mm;
    font-size: 7pt;
    font-weight: bold;
}

.pdf-document .signature-box {
    min-height: 20mm;
    padding: 3mm;
}

.pdf-document .signature-line {
    border-top: 0.25mm solid #111827;
    margin-top: 12mm;
    padding-top: 1.5mm;
    color: #374151;
    font-size: 7.4pt;
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
.report-chart {
    page-break-inside: avoid;
    break-inside: avoid;
}
