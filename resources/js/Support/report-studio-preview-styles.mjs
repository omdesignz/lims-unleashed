import { scopeReportStudioPreviewCss } from './report-studio-css.mjs'

/**
 * What a PDF renderer gives plain markup by default and the application's CSS reset
 * takes away. Zero-specificity selectors, so any document style wins over them.
 */
const printDefaultsCss = [
  '.studio-preview-document :where(.studio-preview-body) :where(ul){list-style:disc;margin:1em 0;padding-left:40px;}',
  '.studio-preview-document :where(.studio-preview-body) :where(ol){list-style:decimal;margin:1em 0;padding-left:40px;}',
  '.studio-preview-document :where(.studio-preview-body) :where(h4,h5,h6){font-weight:bold;margin:1.33em 0;}',
  '.studio-preview-document :where(.studio-preview-body) :where(blockquote){margin:1em 40px;}',
].join('\n')

/**
 * The stylesheet of the on-screen document preview: print defaults, the shared
 * controlled-document styles the server prints with (`documentBaseCss`), the
 * studio's table controls, then the template's own styles. The document is shown
 * with these alone; the application's own typography stays out of it.
 */
export function buildReportStudioPreviewCss(layoutStylesCss = '', documentBaseCss = '') {
  return `
${printDefaultsCss}
${scopeReportStudioPreviewCss(documentBaseCss)}
.studio-preview-document{background-color:var(--studio-page-background-color);font-family:var(--studio-document-font);}
.studio-preview-document table{width:100% !important;font-size:var(--studio-table-font-size) !important;}
.studio-preview-document table:not(.document-summary-table){border-collapse:collapse !important;}
.studio-preview-document table:not(.document-summary-table):not(.doc-plain) th{background:var(--studio-table-header-bg) !important;color:var(--studio-table-header-color) !important;border:0 !important;border-bottom:1px solid var(--studio-table-header-color) !important;padding:var(--studio-table-cell-padding) !important;font-weight:bold !important;}
.studio-preview-document table:not(.document-summary-table):not(.doc-plain) td{border:0 !important;border-bottom:1px solid var(--studio-table-border-color) !important;padding:var(--studio-table-cell-padding) !important;vertical-align:top !important;}
.studio-preview-document .document-summary-table{border-collapse:collapse !important;}
.studio-preview-document .document-summary-table td{border:0 !important;padding:0 !important;}
.studio-preview-document .document-summary-cell{background:var(--studio-table-summary-bg) !important;border:1px solid var(--studio-table-border-color) !important;padding:8px 10px !important;vertical-align:top !important;}
.studio-preview-document .document-summary-cell .label{display:block;color:var(--studio-table-summary-muted-color) !important;font-size:9px;font-weight:bold;letter-spacing:.06em;text-transform:uppercase;}
.studio-preview-document .document-summary-cell .value{display:block;color:var(--studio-table-summary-text-color) !important;font-size:12px;font-weight:bold;margin-top:3px;}
.studio-preview-document .document-summary-cell .muted{display:block;color:var(--studio-table-summary-muted-color) !important;font-size:10px;line-height:1.45;margin-top:3px;}
.studio-preview-document .document-financial-summary td{color:var(--studio-table-summary-text-color);}
.studio-preview-document .bilingual-label{display:block;margin-top:2px;font-size:9px;font-weight:500;letter-spacing:.02em;text-transform:none;opacity:.72;}
${scopeReportStudioPreviewCss(layoutStylesCss)}
`
}
