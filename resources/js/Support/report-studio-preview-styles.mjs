import { scopeReportStudioPreviewCss } from './report-studio-css.mjs'

export function buildReportStudioPreviewCss(layoutStylesCss = '') {
  return `
.studio-preview-document{background-color:var(--studio-page-background-color);font-family:var(--studio-document-font);}
.studio-preview-document table{width:100% !important;font-size:var(--studio-table-font-size) !important;}
.studio-preview-document table:not(.document-summary-table){border-collapse:collapse !important;}
.studio-preview-document table:not(.document-summary-table) th{background:var(--studio-table-header-bg) !important;color:var(--studio-table-header-color) !important;font-size:var(--studio-table-font-size) !important;letter-spacing:.04em !important;text-transform:uppercase !important;}
.studio-preview-document table:not(.document-summary-table) th,
.studio-preview-document table:not(.document-summary-table) td{border:1px solid var(--studio-table-border-color) !important;padding:var(--studio-table-cell-padding) !important;vertical-align:top !important;}
.studio-preview-document .document-summary-table{border-collapse:separate !important;border-spacing:0 10px !important;}
.studio-preview-document .document-summary-table td{border:0 !important;padding:4px !important;}
.studio-preview-document .document-summary-cell{background:var(--studio-table-summary-bg) !important;border:1px solid var(--studio-table-border-color) !important;border-radius:18px !important;padding:14px !important;vertical-align:top !important;}
.studio-preview-document .document-summary-cell .label{display:block;color:var(--studio-table-summary-muted-color) !important;font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;}
.studio-preview-document .document-summary-cell .value{display:block;color:var(--studio-table-summary-text-color) !important;font-size:12px;font-weight:800;margin-top:4px;}
.studio-preview-document .document-summary-cell .muted{display:block;color:var(--studio-table-summary-muted-color) !important;font-size:10px;line-height:1.55;margin-top:6px;}
.studio-preview-document .document-financial-summary td{color:var(--studio-table-summary-text-color);}
.studio-preview-document .bilingual-label{display:block;margin-top:2px;font-size:9px;font-weight:500;letter-spacing:.02em;text-transform:none;opacity:.72;}
${scopeReportStudioPreviewCss(layoutStylesCss)}
`
}
