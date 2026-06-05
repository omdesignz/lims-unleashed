import assert from 'node:assert/strict'
import test from 'node:test'
import { chartSvgPalette, normalizeStudioChartList, normalizedHexColor, sanitizeStudioChartSvg, splitStudioChartList } from '../../resources/js/Support/report-studio-chart-palette.mjs'
import { scopeReportStudioPreviewCss } from '../../resources/js/Support/report-studio-css.mjs'
import { previewReplacementsByType } from '../../resources/js/Support/report-studio-preview-html.mjs'
import { escapePreviewHtmlAttribute, escapePreviewHtmlText, safePreviewCssUrl, safePreviewMediaUrl } from '../../resources/js/Support/report-studio-preview-safety.mjs'
import { buildReportStudioPreviewCss } from '../../resources/js/Support/report-studio-preview-styles.mjs'
import {
  proposalTemplatePreviewMarginStyle,
  proposalTemplatePreviewPageDimensions,
  proposalTemplatePreviewPageFormatLabel,
} from '../../resources/js/Support/proposal-template-preview-geometry.mjs'

test('scopes document root aliases and complex selector lists', () => {
  const scoped = scopeReportStudioPreviewCss(`
body.pdf-document,
.pdf-document table,
h1:is(.title,.subtitle) {
  color: #143d37;
}
`)

  assert.match(scoped, /\.studio-preview-document,/)
  assert.match(scoped, /\.studio-preview-document table,/)
  assert.match(scoped, /\.studio-preview-document h1:is\(\.title,.subtitle\)/)
  assert.doesNotMatch(scoped, /\.studio-preview-document \.pdf-document/)
})

test('flattens print rules and preserves nested capability rules', () => {
  const scoped = scopeReportStudioPreviewCss(`
@media print {
  .report-table { border: 1px solid #ded3bf; }
  @supports (display: grid) {
    body .summary-grid { display: grid; }
  }
}
`)

  assert.doesNotMatch(scoped, /@media print/)
  assert.match(scoped, /\.studio-preview-document \.report-table\s*\{\s*border:/)
  assert.match(scoped, /@supports \(display: grid\)\s*\{\s*\.studio-preview-document \.summary-grid/)
})

test('does not scope keyframes, font faces, or page rules', () => {
  const css = `
@keyframes rise { from { opacity: 0; } to { opacity: 1; } }
@font-face { font-family: Lab; src: url("data:font/woff2;base64,abc"); }
@page { margin: 10mm; }
`
  const scoped = scopeReportStudioPreviewCss(css)

  assert.match(scoped, /@keyframes rise\s*\{\s*from\s*\{\s*opacity: 0;/)
  assert.match(scoped, /@font-face\s*\{\s*font-family: Lab;/)
  assert.match(scoped, /@page\s*\{\s*margin: 10mm;/)
  assert.doesNotMatch(scoped, /@keyframes rise\s*\{\s*\.studio-preview-document/)
})

test('preserves quoted braces and data urls inside declarations', () => {
  const scoped = scopeReportStudioPreviewCss(`
.watermark::before {
  content: "{document_code}";
  background-image: url("data:image/svg+xml,<svg>{mark}</svg>");
}
`)

  assert.match(scoped, /\.studio-preview-document \.watermark::before/)
  assert.match(scoped, /content: "\{document_code\}";/)
  assert.match(scoped, /data:image\/svg\+xml,<svg>\{mark\}<\/svg>/)
})

test('studio preview snippets use the production document table system', () => {
  const serialized = JSON.stringify(previewReplacementsByType)

  assert.doesNotMatch(serialized, /#cbd5e1/)
  assert.doesNotMatch(serialized, /Cliente Exemplo|Exportadora Exemplo|Importadora Exemplo/)
  assert.match(previewReplacementsByType.analysis['{sample_details}'], /document-summary-table/)
  assert.match(previewReplacementsByType.analysis['{results_table}'], /class="report-table studio-avoid-break"/)
  assert.match(previewReplacementsByType.proposal['{summary_table}'], /document-financial-summary/)
  assert.equal(previewReplacementsByType.proposal['{bank_account_number}'], '0011223344')
  assert.equal(previewReplacementsByType.proposal['{bank_swift}'], 'BFAAAOLU')
  assert.equal(previewReplacementsByType.invoice['{bank_iban}'], 'AO06 0000 0000 0000 0000 0000 0')
  assert.equal(previewReplacementsByType.receipt['{bank_details}'], 'Pagamento por transferência bancária com referência do documento.')
  assert.match(previewReplacementsByType.invoice['{items_table}'], /class="report-table studio-avoid-break"/)
})

test('studio preview table controls preserve summary-card tables', () => {
  const css = buildReportStudioPreviewCss('.pdf-document .custom-note{color:#143d37;}')

  assert.match(css, /\.studio-preview-document table:not\(\.document-summary-table\)\{border-collapse:collapse !important;\}/)
  assert.match(css, /\.studio-preview-document \.document-summary-table\{border-collapse:separate !important;border-spacing:0 10px !important;\}/)
  assert.match(css, /\.studio-preview-document \.document-summary-cell\{background:var\(--studio-table-summary-bg\) !important;border:1px solid var\(--studio-table-border-color\) !important;/)
  assert.match(css, /\.studio-preview-document \.document-summary-cell \.value\{display:block;color:var\(--studio-table-summary-text-color\) !important;/)
  assert.match(css, /\.studio-preview-document \.document-financial-summary td\{color:var\(--studio-table-summary-text-color\);\}/)
  assert.match(css, /\.studio-preview-document \.custom-note\{color:#143d37;\}/)
})

test('studio chart preview palette keeps dark chart backgrounds readable', () => {
  assert.equal(normalizedHexColor('#143d37'), '#143d37')
  assert.equal(normalizedHexColor('navy', '#f8f4ea'), '#f8f4ea')

  assert.deepEqual(chartSvgPalette('#07110f'), {
    ink: '#fffdf7',
    muted: '#d7e3dc',
    grid: '#48615a',
    markerStroke: '#07110f',
  })

  assert.deepEqual(chartSvgPalette('#f8f4ea'), {
    ink: '#0f172a',
    muted: '#64748b',
    grid: '#cbd5e1',
    markerStroke: '#fffaf0',
  })
})

test('studio chart list parsing preserves decimal-comma values', () => {
  assert.deepEqual(splitStudioChartList('18, 12, 9'), ['18', '12', '9'])
  assert.deepEqual(splitStudioChartList("1,5; 2,75\n3,25"), ['1,5', '2,75', '3,25'])
  assert.deepEqual(splitStudioChartList('#143d37, #d9b05f; #0f766e'), ['#143d37', '#d9b05f', '#0f766e'])
  assert.deepEqual(splitStudioChartList('Recepção, Validação, Emissão'), ['Recepção', 'Validação', 'Emissão'])
  assert.deepEqual(normalizeStudioChartList(['Recepção; Validação', 'Emissão\nAprovação']), ['Recepção', 'Validação', 'Emissão', 'Aprovação'])
  assert.deepEqual(normalizeStudioChartList(['1,5; 2,75', 3]), ['1,5', '2,75', '3'])
})

test('studio chart svg sanitizer preserves chart markup and strips executable svg content', () => {
  const sanitized = sanitizeStudioChartSvg('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)" viewBox="0 0 120 60"><script>alert(1)</script><foreignObject><body>unsafe</body></foreignObject><a href="javascript:alert(1)"><text onclick="alert(2)">Clique</text></a><rect width="120" height="60" fill="#143d37" style="background-image:url(javascript:alert(3))"/></svg>')

  assert.match(sanitized, /^<svg xmlns="http:\/\/www\.w3\.org\/2000\/svg"/)
  assert.match(sanitized, /<rect width="120" height="60" fill="#143d37"/)
  assert.doesNotMatch(sanitized, /<script|foreignObject|onload=|onclick=|javascript:/i)
  assert.equal(sanitizeStudioChartSvg('<div>not svg</div>'), '')
  assert.equal(sanitizeStudioChartSvg('<svg><rect /></svg><script>alert(1)</script>'), '<svg><rect /></svg>')
  assert.equal(sanitizeStudioChartSvg('<svg><rect /></svg><p>fora do svg</p>'), '<svg><rect /></svg>')
})

test('studio preview media helpers reject executable urls and escape attributes', () => {
  assert.equal(safePreviewMediaUrl('/storage/report-studio/logo.svg'), '/storage/report-studio/logo.svg')
  assert.equal(safePreviewMediaUrl('https://lims-unleashed.test/storage/media/stamp.png'), 'https://lims-unleashed.test/storage/media/stamp.png')
  assert.equal(safePreviewMediaUrl('data:image/png;base64,aGVsbG8='), 'data:image/png;base64,aGVsbG8=')
  assert.equal(safePreviewMediaUrl('javascript:alert(1)'), '')
  assert.equal(safePreviewMediaUrl('data:text/html;base64,PHNjcmlwdD4='), '')
  assert.equal(safePreviewMediaUrl('/storage/media/logo.png\nonerror=alert(1)'), '')
  assert.equal(escapePreviewHtmlAttribute('Selo "Aprovado" <svg>'), 'Selo &quot;Aprovado&quot; &lt;svg&gt;')
  assert.equal(escapePreviewHtmlText('Legenda <strong>não HTML</strong> & segura'), 'Legenda &lt;strong&gt;não HTML&lt;/strong&gt; &amp; segura')
  assert.equal(safePreviewCssUrl('/storage/media/bg "assinada".png'), 'url("/storage/media/bg \\"assinada\\".png")')
  assert.equal(safePreviewCssUrl('vbscript:msgbox(1)'), '')
})

test('proposal template preview geometry mirrors custom PDF page settings', () => {
  const exportSettings = {
    paper_size: 'custom',
    custom_page_width: 250,
    custom_page_height: 180,
    orientation: 'L',
    margin_top: 18,
    margin_right: 12,
    margin_bottom: 16,
    margin_left: 10,
    first_page_margin_top: 48,
  }

  assert.deepEqual(proposalTemplatePreviewPageDimensions(exportSettings), {
    width: 250,
    height: 180,
  })
  assert.equal(proposalTemplatePreviewPageFormatLabel(exportSettings), '250 × 180 mm · Paisagem')
  assert.deepEqual(proposalTemplatePreviewMarginStyle(exportSettings, 1), {
    top: '26.666666666666668%',
    right: '4.8%',
    bottom: '8.88888888888889%',
    left: '4%',
  })
  assert.deepEqual(proposalTemplatePreviewMarginStyle(exportSettings, 2), {
    top: '10%',
    right: '4.8%',
    bottom: '8.88888888888889%',
    left: '4%',
  })
})
