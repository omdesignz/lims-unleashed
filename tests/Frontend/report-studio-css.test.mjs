import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import {
  chartSvgPalette,
  generatedStudioChartSvg,
  normalizeStudioChartList,
  normalizedHexColor,
  sanitizeStudioChartSvg,
  splitStudioChartList,
} from '../../resources/js/Support/report-studio-chart-palette.mjs'
import { scopeReportStudioPreviewCss } from '../../resources/js/Support/report-studio-css.mjs'
import {
  interpolateStudioPreviewHtml,
  isStudioConditionalPlaceholderKey,
  normaliseStudioPreviewHexColor,
  normaliseStudioPreviewPlaceholderToken,
  normalizeStudioPreviewReplacements,
  previewReplacementsByType,
  studioPreviewValueIsTruthy,
  studioThemeColorReplacements,
} from '../../resources/js/Support/report-studio-preview-html.mjs'
import { escapePreviewHtmlAttribute, escapePreviewHtmlText, safePreviewCssUrl, safePreviewMediaUrl } from '../../resources/js/Support/report-studio-preview-safety.mjs'
import { buildReportStudioPreviewCss } from '../../resources/js/Support/report-studio-preview-styles.mjs'
import { collectFlowBlocks, flowPages, paginateFlow } from '../../resources/js/Support/report-studio-pagination.mjs'
import {
  generatedStudioQrCodeDataUri,
  studioQrCodeErrorCorrectionLevel,
  studioQrCodeMargin,
} from '../../resources/js/Support/report-studio-qr-code.mjs'
import { imagePositionCoordinates, imagePositionStringFromCoordinates } from '../../resources/js/Support/report-studio-image-position.mjs'
import { uploadedStudioAssetKind } from '../../resources/js/Support/report-studio-media-assets.mjs'
import {
  proposalTemplatePreviewMarginStyle,
  proposalTemplatePreviewPageDimensions,
  proposalTemplatePreviewPageFormatLabel,
} from '../../resources/js/Support/proposal-template-preview-geometry.mjs'

const proposalStudioWorkbenchSource = readFileSync(new URL('../../resources/js/Components/proposal-template/studio-workbench.vue', import.meta.url), 'utf8')
const reportStudioWorkbenchSource = readFileSync(new URL('../../resources/js/Components/report-studio/studio-workbench.vue', import.meta.url), 'utf8')

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
  assert.match(css, /\.studio-preview-document\{background-color:var\(--studio-page-background-color\);font-family:var\(--studio-document-font\);\}/)
  assert.match(css, /\.studio-preview-document \.document-summary-table\{border-collapse:collapse !important;\}/)
  // Layout tables of the controlled document (letterhead, key-value grids) keep their own rules.
  assert.match(css, /\.studio-preview-document table:not\(\.document-summary-table\):not\(\.doc-plain\) td\{border:0 !important;border-bottom:1px solid var\(--studio-table-border-color\) !important;/)
  assert.match(css, /\.studio-preview-document \.document-summary-cell\{background:var\(--studio-table-summary-bg\) !important;border:1px solid var\(--studio-table-border-color\) !important;/)
  assert.match(css, /\.studio-preview-document \.document-summary-cell \.value\{display:block;color:var\(--studio-table-summary-text-color\) !important;/)
  assert.match(css, /\.studio-preview-document \.document-financial-summary td\{color:var\(--studio-table-summary-text-color\);\}/)
  assert.match(css, /\.studio-preview-document \.custom-note\{color:#143d37;\}/)
})

test('the studio preview draws with the shared controlled-document styles, before the template styles', () => {
  const css = buildReportStudioPreviewCss('.doc-section-title{color:#123456;}', '.doc-section-title{font-weight:bold;}\n.pdf-document h1{font-size:15pt;}')

  assert.match(css, /\.studio-preview-document \.doc-section-title\{font-weight:bold;\}/)
  assert.match(css, /\.studio-preview-document h1\{font-size:15pt;\}/)
  assert.ok(css.indexOf('font-weight:bold') < css.indexOf('color:#123456'))
  assert.doesNotMatch(buildReportStudioPreviewCss('.a{color:red;}'), /undefined/)
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

test('studio generated charts interpolate preset placeholders before preview rendering', () => {
  const replacements = {
    '{analysis_chart_title}': 'Estado dos resultados analíticos',
    '{analysis_chart_labels}': 'Recepção, Verificação, Contra-análise',
    '{analysis_chart_values}': '1,5; 2,75\n3,25',
    '{analysis_chart_colors}': '#143d37, #d9b05f, #0f766e',
    '{analysis_chart_background}': '#07110f',
  }
  const interpolate = (value) => interpolateStudioPreviewHtml(value, replacements)
  const svg = generatedStudioChartSvg({
    chart_type: 'line',
    chart_title: '{analysis_chart_title}',
    chart_labels: '{analysis_chart_labels}',
    chart_values: '{analysis_chart_values}',
    chart_colors: '{analysis_chart_colors}',
    chart_background_color: '{analysis_chart_background}',
    chart_primary_color: '#d9b05f',
  }, { interpolate })

  assert.match(svg, /data-chart-type="line"/)
  assert.match(svg, /Estado dos resultados analíticos/)
  assert.match(svg, /Recepção/)
  assert.match(svg, /Contra-análise/)
  assert.match(svg, /#07110f/)
  assert.match(svg, /#fffdf7/)
  assert.match(svg, />1\.5</)
  assert.match(svg, />2\.75</)
  assert.doesNotMatch(svg, /analysis_chart_values|analysis_chart_labels/)
})

test('studio generated charts fall back safely for empty data and invalid colors', () => {
  assert.equal(generatedStudioChartSvg({ chart_values: '{missing_values}' }, { interpolate: () => '' }), '')

  const svg = generatedStudioChartSvg({
    chart_type: 'doughnut',
    chart_title: '<Resumo & risco>',
    chart_labels: ['Amostras', 'Ensaios'],
    chart_values: [3, 5],
    chart_colors: ['gold', '#0f766e'],
    chart_primary_color: 'navy',
    chart_background_color: 'transparent',
    chart_show_values: false,
  })

  assert.match(svg, /data-chart-type="doughnut"/)
  assert.match(svg, /&lt;Resumo &amp; risco&gt;/)
  assert.match(svg, /#0f766e/)
  assert.match(svg, /#f8f4ea/)
  assert.match(svg, /total/)
  assert.doesNotMatch(svg, /gold|navy|transparent/)
})

test('studio preview theme color replacements resolve brand and app placeholders', () => {
  const replacements = studioThemeColorReplacements({
    app_primary_color: '#245f4a',
    app_secondary_color: '#fff4d6',
    app_accent_color: 'invalid',
  })

  assert.deepEqual(replacements, {
    '{brand_primary_color}': '#245f4a',
    '{brand_secondary_color}': '#fff4d6',
    '{brand_accent_color}': '#d9b05f',
    '{app_primary_color}': '#245f4a',
    '{app_secondary_color}': '#fff4d6',
    '{app_accent_color}': '#d9b05f',
  })
  assert.equal(normaliseStudioPreviewHexColor('#123abc', '#000000'), '#123abc')
  assert.equal(normaliseStudioPreviewHexColor('rgb(1 2 3)', '#000000'), '#000000')
  assert.equal(
    interpolateStudioPreviewHtml('<strong style="color:{brand_primary_color}; background:{{ app_secondary_color }};">Marca</strong>', replacements),
    '<strong style="color:#245f4a; background:#fff4d6;">Marca</strong>',
  )
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

test('studio generated qr codes interpolate content and keep print-safe defaults', () => {
  const replacements = {
    '{verification_url}': 'https://lims-unleashed.test/verify/PROP-2026-001',
    '{qr_foreground}': '#143d37',
  }
  const dataUri = generatedStudioQrCodeDataUri({
    qr_content: '{verification_url}',
    qr_foreground_color: '{qr_foreground}',
    qr_background_color: 'transparent',
    qr_error_correction: 'high',
    qr_margin: 99,
  }, {
    interpolate: (value) => interpolateStudioPreviewHtml(value, replacements),
  })
  const svg = decodeURIComponent(dataUri.split(',')[1])

  assert.match(dataUri, /^data:image\/svg\+xml;charset=UTF-8,/)
  assert.match(svg, /^<svg xmlns="http:\/\/www\.w3\.org\/2000\/svg"/)
  assert.match(svg, /shape-rendering="crispEdges"/)
  assert.match(svg, /fill="#143d37"/)
  assert.match(svg, /fill="#ffffff"/)
  assert.equal(studioQrCodeErrorCorrectionLevel('quartile'), 'Q')
  assert.equal(studioQrCodeErrorCorrectionLevel('unknown'), 'L')
  assert.equal(studioQrCodeMargin(99), 32)
  assert.equal(studioQrCodeMargin(-8), 0)
  assert.equal(studioQrCodeMargin('invalid'), 8)
  assert.equal(generatedStudioQrCodeDataUri({ qr_content: '{empty}' }, { interpolate: () => '' }), '')
})

test('studio qr previews are shared and proposal labels are escaped', () => {
  assert.match(proposalStudioWorkbenchSource, /generatedStudioQrCodeDataUri/)
  assert.match(reportStudioWorkbenchSource, /generatedStudioQrCodeDataUri/)
  assert.match(proposalStudioWorkbenchSource, /escapePreviewHtmlText\(interpolatePreviewHtml\(block\.qr_label\)\)/)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /function qrCodePreviewDataUri/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /function qrCodePreviewDataUri/)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /createQrCode/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /createQrCode/)
})

test('proposal template studio uses shared chart and media preview hardening', () => {
  assert.match(proposalStudioWorkbenchSource, /generatedStudioChartSvg/)
  assert.match(proposalStudioWorkbenchSource, /sanitizeStudioChartSvg/)
  assert.match(proposalStudioWorkbenchSource, /safePreviewMediaUrl/)
  assert.match(proposalStudioWorkbenchSource, /safePreviewCssUrl/)
  assert.match(proposalStudioWorkbenchSource, /const sanitizedChartSvg = sanitizeStudioChartSvg/)
  assert.match(proposalStudioWorkbenchSource, /interpolate: interpolatePreviewHtml/)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /function generatedChartSvg/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /function generatedChartSvg/)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /\.split\(\s*\/\\\[\\n,\;\]\+\//)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /\$\{interpolatePreviewHtml\(block\.chart_svg\)\}/)
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

test('studio upload targets resolve to stable media asset roles', () => {
  assert.equal(uploadedStudioAssetKind({ scope: 'document-background' }), 'uploaded_background')
  assert.equal(uploadedStudioAssetKind({ field: 'background_image' }), 'uploaded_background')
  assert.equal(uploadedStudioAssetKind({ scope: 'new-canvas-block', blockKind: 'signature' }), 'uploaded_signature')
  assert.equal(uploadedStudioAssetKind({ scope: 'new-canvas-block', blockKind: 'stamp' }), 'uploaded_stamp')
  assert.equal(uploadedStudioAssetKind({ scope: 'new-canvas-block', blockKind: 'chart_snapshot' }), 'uploaded_chart')
  assert.equal(uploadedStudioAssetKind({ scope: 'new-canvas-block', blockKind: 'image' }), 'uploaded_image')
  assert.equal(uploadedStudioAssetKind({ field: 'signature_image' }), 'uploaded_signature')
  assert.equal(uploadedStudioAssetKind({ field: 'chart_image_url' }), 'uploaded_chart')
  assert.equal(uploadedStudioAssetKind({ scope: 'asset-url' }), 'uploaded_asset')
  assert.equal(uploadedStudioAssetKind({ scope: 'selected-block', field: 'image_url' }), 'uploaded_image')
  assert.equal(uploadedStudioAssetKind(), 'uploaded_image')
})

test('report studio media picker uses the product shell and keeps manual urls advanced', () => {
  const mediaPickerSource = reportStudioWorkbenchSource.slice(
    reportStudioWorkbenchSource.indexOf('<div v-if="mediaPickerOpen"'),
    reportStudioWorkbenchSource.indexOf('<div v-else class="mt-5 rounded-3xl border border-dashed', reportStudioWorkbenchSource.indexOf('<div v-if="mediaPickerOpen"')),
  )

  assert.match(mediaPickerSource, /bg-slate-950\/75/)
  assert.match(mediaPickerSource, /max-w-6xl/)
  assert.match(mediaPickerSource, /border-\[var\(--ds-border\)\]/)
  assert.match(mediaPickerSource, /studioCopy\('media_picker\.eyebrow'\)/)
  assert.match(mediaPickerSource, /studioCopy\('media_picker\.manual_advanced'\)/)
  assert.match(mediaPickerSource, /<details class="mt-3/)
  assert.match(reportStudioWorkbenchSource, /asset\.pdf_url,\n\s+asset\.url,/)
  assert.doesNotMatch(mediaPickerSource, /Galeria documental/)
  assert.doesNotMatch(mediaPickerSource, /Ligação manual avançada/)
  assert.doesNotMatch(mediaPickerSource, /bg-primary|text-primary|border-primary|ring-primary/)
})

test('report studio inspector uses premium product controls for canvas editing', () => {
  assert.match(reportStudioWorkbenchSource, /const textAlignmentOptions = \[/)
  assert.match(reportStudioWorkbenchSource, /const signatureLineStyleOptions = \[/)
  assert.match(reportStudioWorkbenchSource, /const qrErrorCorrectionOptions = \[/)
  assert.match(reportStudioWorkbenchSource, /class="studio-inspector-select/)
  assert.match(reportStudioWorkbenchSource, /class="studio-gallery-select"/)
  assert.match(reportStudioWorkbenchSource, /class="studio-media-picker-button/)
  assert.match(reportStudioWorkbenchSource, /class="studio-inspector-tip"/)
  assert.match(reportStudioWorkbenchSource, /\.studio-inspector-select/)
  assert.match(reportStudioWorkbenchSource, /\.studio-media-picker-button--secondary/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /<select v-model="selectedCanvasBlock[^>]*focus:border-primary-500/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /<select v-model="props\.layoutSchema\.document_font_family"[^>]*focus:border-primary-500/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /<select v-model="snippetTarget"[^>]*focus:border-primary-500/)
})

test('proposal studio media picker is searchable, filtered, and target aware', () => {
  const mediaPickerSource = proposalStudioWorkbenchSource.slice(
    proposalStudioWorkbenchSource.indexOf('<div v-if="mediaPickerOpen"'),
    proposalStudioWorkbenchSource.indexOf('</template>', proposalStudioWorkbenchSource.indexOf('<div v-if="mediaPickerOpen"')),
  )

  assert.match(proposalStudioWorkbenchSource, /const mediaPickerSearch = ref\(''\)/)
  assert.match(proposalStudioWorkbenchSource, /const mediaPickerKind = ref\('all'\)/)
  assert.match(proposalStudioWorkbenchSource, /function mediaAssetDocumentUrl\(asset\)/)
  assert.match(proposalStudioWorkbenchSource, /return asset\?\.pdf_url \|\| asset\?\.url \|\| ''/)
  assert.match(proposalStudioWorkbenchSource, /selectedCanvasBlock\.value\.signature_image = mediaAssetDocumentUrl\(asset\)/)
  assert.match(proposalStudioWorkbenchSource, /const assetUrl = mediaAssetDocumentUrl\(asset\)/)
  assert.match(proposalStudioWorkbenchSource, /:value="mediaAssetDocumentUrl\(asset\)"/)
  assert.match(proposalStudioWorkbenchSource, /asset\.pdf_url,\n\s+asset\.url,/)
  assert.match(proposalStudioWorkbenchSource, /const filteredMediaAssets = computed\(\(\) =>/)
  assert.match(proposalStudioWorkbenchSource, /const mediaPickerTargetLabel = computed\(\(\) =>/)
  assert.match(mediaPickerSource, /bg-\[#06100e\]\/80/)
  assert.match(mediaPickerSource, /max-w-6xl/)
  assert.match(mediaPickerSource, /border-\[var\(--ds-border\)\]/)
  assert.match(mediaPickerSource, /media_picker\.search_placeholder/)
  assert.match(mediaPickerSource, /media_picker\.target_label/)
  assert.match(mediaPickerSource, /v-for="option in mediaKindOptions"/)
  assert.match(mediaPickerSource, /v-for="asset in filteredMediaAssets"/)
  assert.doesNotMatch(mediaPickerSource, /v-for="asset in assetLibraryItems"/)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /:value="asset\.url"/)
})

test('document studios expose page background color as a first-class surface control', () => {
  assert.match(reportStudioWorkbenchSource, /page_background_color/)
  assert.match(reportStudioWorkbenchSource, /--studio-page-background-color/)
  assert.match(reportStudioWorkbenchSource, /Cor de página/)
  assert.match(reportStudioWorkbenchSource, /colorInputValue\(props\.layoutSchema\.page_background_color, '#fffdf7'\)/)
  assert.match(proposalStudioWorkbenchSource, /page_background_color/)
  assert.match(proposalStudioWorkbenchSource, /Cor da página/)
  assert.match(proposalStudioWorkbenchSource, /backgroundColor: safeCssColorValue\(props\.layoutSchema\.page_background_color, '#fffdf7'\)/)
})

test('report studio exposes conditional snippets for optional PDF sections', () => {
  assert.match(reportStudioWorkbenchSource, /const conditionalSnippetLibrary = \[/)
  assert.match(reportStudioWorkbenchSource, /\{if:observations\}/)
  assert.match(reportStudioWorkbenchSource, /\{endif:observations\}/)
  assert.match(reportStudioWorkbenchSource, /\{\{ifnot:observations\}\}/)
  assert.match(reportStudioWorkbenchSource, /\{\{endif:observations\}\}/)
  assert.match(reportStudioWorkbenchSource, /\.\.\.conditionalSnippetLibrary/)
})

test('studio preview interpolation mirrors generated PDF conditional semantics', () => {
  const html = [
    '{if:observations}<p>{observations}</p>{endif:observations}',
    '{if:missing}<p class="hidden">Hidden</p>{endif:missing}',
    '{ifnot:missing}<span>{{proposal_number}}</span>{endif:missing}',
    '{{ifnot:false_value}}<em>{{footer_label}}</em>{{endif:false_value}}',
    '<strong>{{braced_value}}</strong>',
  ].join('')

  const output = interpolateStudioPreviewHtml(html, {
    '{observations}': 'Validação comercial aceite.',
    proposal_number: 'PROP-2026-009',
    false_value: '0',
    footer_label: 'Página 1/2',
    '{{braced_value}}': 'Valor normalizado',
  })

  assert.match(output, /Validação comercial aceite\./)
  assert.match(output, /PROP-2026-009/)
  assert.match(output, /Página 1\/2/)
  assert.match(output, /Valor normalizado/)
  assert.doesNotMatch(output, /class="hidden"|if:observations|ifnot:false_value/)
  assert.equal(normaliseStudioPreviewPlaceholderToken('{{ proposal_number }}'), 'proposal_number')
  assert.deepEqual(normalizeStudioPreviewReplacements({ '{proposal_number}': 'P-1', proposal_status: null }), {
    proposal_number: 'P-1',
    proposal_status: '',
  })
  assert.equal(isStudioConditionalPlaceholderKey('if:observations'), true)
  assert.equal(studioPreviewValueIsTruthy('<strong>não</strong>'), false)
  assert.equal(studioPreviewValueIsTruthy('Aprovado'), true)
})

test('document studios share preview interpolation instead of duplicating conditional parsers', () => {
  assert.match(reportStudioWorkbenchSource, /interpolateStudioPreviewHtml/)
  assert.match(reportStudioWorkbenchSource, /studioPlaceholderTokenPattern/)
  assert.match(reportStudioWorkbenchSource, /isStudioConditionalPlaceholderKey/)
  assert.match(reportStudioWorkbenchSource, /studioThemeColorReplacements\(page\.props\?\.settings \|\| \{\}\)/)
  assert.match(proposalStudioWorkbenchSource, /interpolateStudioPreviewHtml/)
  assert.match(proposalStudioWorkbenchSource, /studioThemeColorReplacements\(page\.props\?\.settings \|\| \{\}\)/)
  assert.doesNotMatch(reportStudioWorkbenchSource, /function resolvePreviewConditionalBlocks/)
  assert.doesNotMatch(proposalStudioWorkbenchSource, /function resolvePreviewConditionalBlocks/)
  assert.match(proposalStudioWorkbenchSource, /previewFooterHtmlForPage\(pageNumber, totalPages\)[\s\S]*PAGENO: String\(pageNumber\)[\s\S]*nbpg: String\(totalPages\)/)
})

test('document studios normalize canvas block placement before PDF preview and save', () => {
  for (const source of [reportStudioWorkbenchSource, proposalStudioWorkbenchSource]) {
    // The report studio counts explicit pages (segments); a flowed page is not a page a block can be pinned to.
    assert.match(source, /function normalizeCanvasBlockPlacement\(block, fallbackPageNumber = current(PreviewSegment|PreviewPage)\.value \|\| 1\)/)
    assert.match(source, /function normalizeSelectedCanvasBlockPlacement/)
    assert.match(source, /function normalizeCanvasBlockPlacements\(\)[\s\S]*canvasBlocks\.value\.forEach\(\(block\) => normalizeCanvasBlockPlacement\(block\)\)/)
    assert.match(source, /canvasSurfaceOptions\.some\(\(option\) => option\.value === block\.surface\)/)
    assert.match(source, /block\.surface = 'content'/)
    assert.match(source, /block\.surface !== 'content'[\s\S]*block\.page_scope = 'all'[\s\S]*block\.page_number = null/)
    assert.match(source, /block\.page_scope === 'specific'[\s\S]*fallbackPageNumber/)
    assert.match(source, /selectedCanvasBlock\.value\?\.surface[\s\S]*selectedCanvasBlock\.value\?\.page_scope[\s\S]*selectedCanvasBlock\.value\?\.page_number/)
    assert.match(source, /normalizeCanvasBlockPlacements\(\)[\s\S]*props\.layoutSchema\.variable_catalog = translatedPlaceholders\.value/)
  }
})

test('studio image focal helpers normalize keywords and percentage coordinates', () => {
  assert.deepEqual(imagePositionCoordinates('center center'), { x: 50, y: 50 })
  assert.deepEqual(imagePositionCoordinates('top left'), { x: 0, y: 0 })
  assert.deepEqual(imagePositionCoordinates('right bottom'), { x: 100, y: 100 })
  assert.deepEqual(imagePositionCoordinates('37% 68%'), { x: 37, y: 68 })
  assert.deepEqual(imagePositionCoordinates('135% -4%'), { x: 50, y: 50 })
  assert.equal(imagePositionStringFromCoordinates(37.4, 68.6), '37% 69%')
  assert.equal(imagePositionStringFromCoordinates(135, -4), '100% 0%')
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

test('a new studio model starts from the system document of its type', () => {
  const page = readFileSync(new URL('../../resources/js/Pages/ReportStudios/Index.vue', import.meta.url), 'utf8')

  // New, reset and edited models all take the server's document for the type as their base.
  assert.match(page, /const systemPresetFor = \(studioType\) => props\.systemPresets\.find\(\(preset\) => preset\.category === studioType\)/)
  assert.match(page, /layout_schema: startingLayoutFor\('analysis'\),\s*export_settings: startingExportSettingsFor\('analysis'\)/)
  assert.match(page, /form\.layout_schema = startingLayoutFor\('analysis'\)\s*form\.export_settings = startingExportSettingsFor\('analysis'\)/)
  assert.match(page, /\.\.\.startingLayoutFor\(template\.studio_type\),\s*\.\.\.\(template\.layout_schema \|\| \{\}\)/)
  assert.match(page, /form\.layout_schema = startingLayoutFor\(studioType\)/)

  // The last-resort layout carries no document of its own for the server to print.
  const fallback = page.slice(page.indexOf('const defaultLayoutSchema = {'), page.indexOf('const studioDocumentTokens = {'))
  for (const surface of ['first_page_header_html', 'default_header_html', 'footer_html', 'body_html', 'styles_css']) {
    assert.match(fallback, new RegExp(`${surface}: '',`), surface)
  }
  assert.doesNotMatch(fallback, /#143d37|#fffdf7|report_title/)
})

test('the studio canvas shows the values the server prints the preview with', () => {
  const page = readFileSync(new URL('../../resources/js/Pages/ReportStudios/Index.vue', import.meta.url), 'utf8')
  const replacements = page.slice(page.indexOf('const previewReplacements = computed('), page.indexOf('const previewPdfHref'))

  // Server values come last, so they win over the local samples.
  assert.ok(replacements.indexOf('previewReplacementsByType[form.studio_type]') < replacements.indexOf('props.canvasSampleValues?.shared'))
  assert.ok(replacements.indexOf('props.canvasSampleValues?.shared') < replacements.indexOf('props.canvasSampleValues?.types?.[form.studio_type]'))

  const merged = normalizeStudioPreviewReplacements({
    ...previewReplacementsByType.analysis,
    '{lab_name}': 'Laboratório do servidor',
    '{customer_block}': '<div class="doc-section">Cliente</div>',
  })
  const html = interpolateStudioPreviewHtml('<b>{{lab_name}}</b>{customer_block}{sample_lot}', merged)
  assert.equal(html, '<b>Laboratório do servidor</b><div class="doc-section">Cliente</div>LT-2026-044')
})

test('application table styling stays out of the studio document canvas', () => {
  const css = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8')
  const tableRules = css.split('\n').filter((line) => line.includes('table:not(.lab-table, [data-bare]):not(:where('))

  assert.equal(tableRules.length, 3)
  for (const rule of tableRules) {
    assert.match(rule, /\.pdf-document, \.studio-preview, \.studio-preview-document, \.report-preview\) \*\)/)
  }
})

test('the studio shows the document body with document styles only', () => {
  const workbench = readFileSync(new URL('../../resources/js/Components/report-studio/studio-workbench.vue', import.meta.url), 'utf8')

  // Application typography forces its own table margins and sizes on a document.
  assert.doesNotMatch(workbench, /class="prose[^"]*" v-html="(editorCanvasPage|previewPage)\.content"/)
  assert.equal(workbench.match(/class="studio-preview-body" :style="flowBodyStyle\((editorCanvasPage|previewPage)\)" v-html="(editorCanvasPage|previewPage)\.content"/g)?.length, 2)

  const css = buildReportStudioPreviewCss('ul{margin:0;}', '.doc-kv td{font-size:8.2pt;}')
  const defaults = css.indexOf(':where(.studio-preview-body) :where(ul){list-style:disc;')
  const base = css.indexOf('.studio-preview-document .doc-kv td')
  const own = css.lastIndexOf('.studio-preview-document ul')

  // Print defaults first, then the shared document styles, then the template's own.
  assert.ok(defaults >= 0 && defaults < base && base < own)
})

test('a studio theme restyles a controlled document without replacing its letterhead', () => {
  const workbench = readFileSync(new URL('../../resources/js/Components/report-studio/studio-workbench.vue', import.meta.url), 'utf8')
  const source = workbench.slice(workbench.indexOf('function isControlledDocumentSurface('), workbench.indexOf('function syncAssetUrlFromBackground('))
  const themeMayReplaceSurface = new Function(`${source.slice(0, source.indexOf('function applyThemePreset('))}; return themeMayReplaceSurface`)()

  const letterhead = '<table class="doc-letterhead doc-plain"><tr><td>{{lab_name}}</td></tr></table>'
  const running = '<table class="doc-running doc-plain"><tr><td>{{document_code}}</td></tr></table>'
  const footer = '<table class="doc-footer doc-plain"><tr><td>Página {PAGENO} de {nbpg}</td></tr></table>'

  // The surfaces that identify, revise and number a controlled document stay.
  assert.equal(themeMayReplaceSurface(letterhead, '{{lab_name}}'), false)
  assert.equal(themeMayReplaceSurface(running, '{{document_code}}'), false)
  assert.equal(themeMayReplaceSurface(footer, '{PAGENO}/{nbpg}'), false)

  // An empty surface, or an earlier theme's own header, is still the theme's to set.
  assert.equal(themeMayReplaceSurface('', '{{lab_name}}'), true)
  assert.equal(themeMayReplaceSurface('<div style="color:#143d37">{{lab_name}}</div>', '{{lab_name}}'), true)
  assert.equal(themeMayReplaceSurface('<div>Cabeçalho próprio</div>', '{{lab_name}}'), false)

  for (const surface of ['first_page_header_html', 'default_header_html', 'footer_html']) {
    assert.match(source, new RegExp(`if \\(themeMayReplaceSurface\\(props\\.layoutSchema\\.${surface}, `), surface)
  }
})

test('QR codes are sized by width alone, so they stay square', () => {
  const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')

  // Studio canvases: the width may give way to the block, the height always follows it.
  for (const path of ['resources/js/Components/report-studio/studio-workbench.vue', 'resources/js/Components/proposal-template/studio-workbench.vue']) {
    const elements = read(path).match(/<img src="\$\{qrDataUri\}"[^>]*>/g) ?? []

    assert.ok(elements.length > 0, path)
    for (const element of elements) {
      assert.match(element, /width:100%; max-width:\d+px; height:auto; aspect-ratio:1 \/ 1;/, path)
    }
  }

  // The letterhead code on the canvas takes the printed rule: no height of its own to be squeezed against.
  const printed = read('resources/views/PDFs/partials/premium-document-style.blade.php').match(/\.doc-letterhead \.doc-letterhead-qr img \{[^}]*\}/)?.[0] ?? ''
  const scoped = buildReportStudioPreviewCss('', printed)
  const rule = scoped.match(/\.studio-preview-document \.doc-letterhead \.doc-letterhead-qr img\s*\{([^}]*)\}/)?.[1] ?? ''

  assert.match(rule, /width: 21mm;/)
  assert.match(rule, /height: auto;/)
  assert.match(rule, /max-width: none;/)
  assert.match(rule, /aspect-ratio: 1 \/ 1;/)

  // Screens that show a record's code keep it undistorted in a tight row.
  for (const path of ['resources/js/Pages/DirectCollections/Index.vue', 'resources/js/Pages/Proposals/Show.vue']) {
    assert.match(read(path), /<img :src="[^"]*qr"[^>]*class="aspect-square h-16 w-16 shrink-0 object-contain"/, path)
  }
})

test('the canvas breaks a long body between blocks, never through one', () => {
  const rows = (count, height, from = 0) => Array.from({ length: count }, (_value, index) => ({ top: from + index * height, bottom: from + (index + 1) * height }))

  // Ten 40px rows, 150px on the first page and 200px after: breaks fall on row edges.
  assert.deepEqual(paginateFlow(rows(10, 40), 400, 150, 200), [0, 120, 320])

  // A body that fits stays on one page, sub-pixel overshoot included.
  assert.deepEqual(paginateFlow(rows(5, 40), 200, 200, 200), [0])
  assert.deepEqual(paginateFlow([{ top: 0, bottom: 200.4 }], 200.4, 200, 200), [0])

  // A heading is not left alone at the foot of a page: it moves on with what it introduces.
  const withHeading = [...rows(3, 40), { top: 120, bottom: 140, heading: true }, { top: 140, bottom: 220 }]
  assert.deepEqual(paginateFlow(withHeading, 220, 180, 200), [0, 120])

  // Only a block taller than a whole page is cut through, at the page edge.
  assert.deepEqual(paginateFlow([{ top: 0, bottom: 30 }, { top: 30, bottom: 530 }], 530, 200, 200), [0, 30, 230, 430])

  // Bare text has nothing to break between: one page per page height.
  assert.deepEqual(paginateFlow([], 450, 200, 200), [0, 200, 400])

  // No room measured yet (frame not laid out): one page, nothing guessed.
  assert.deepEqual(paginateFlow(rows(10, 40), 400, 0, 0), [0])
})

test('explicit page breaks and flowed pages number as one document', () => {
  const pages = flowPages(['<p>A</p>', '<p>B</p>'], [[0, 120, 320], [0]])

  assert.deepEqual(pages.map((page) => [page.content, page.segmentNumber, page.flowIndex, page.offset, page.limit]), [
    ['<p>A</p>', 1, 0, 0, 120],
    ['<p>A</p>', 1, 1, 120, 200],
    ['<p>A</p>', 1, 2, 320, null],
    ['<p>B</p>', 2, 0, 0, null],
  ])

  // Before anything is measured each segment is one page, as it always was.
  assert.deepEqual(flowPages(['<p>A</p>', '<p>B</p>']).map((page) => page.offset), [0, 0])
  assert.deepEqual(flowPages([]), [])
})

test('blocks that must stay whole are measured as one piece', () => {
  const element = (rect, { matches = [], children = [] } = {}) => ({
    children,
    matches: (selector) => selector.split(',').some((part) => matches.includes(part.trim())),
    getBoundingClientRect: () => rect,
  })
  const root = element({ top: 100, bottom: 400, height: 300 }, {
    children: [
      element({ top: 100, bottom: 120, height: 20 }, { matches: ['.doc-section-title'] }),
      element({ top: 120, bottom: 220, height: 100 }, {
        children: [
          element({ top: 120, bottom: 170, height: 50 }, { matches: ['tr'], children: [element({ top: 120, bottom: 170, height: 50 })] }),
          element({ top: 170, bottom: 220, height: 50 }, { matches: ['tr'] }),
        ],
      }),
      element({ top: 220, bottom: 400, height: 180 }, { matches: ['.doc-keep'], children: [element({ top: 220, bottom: 300, height: 80 }, { matches: ['tr'] })] }),
      element({ top: 400, bottom: 400, height: 0 }),
    ],
  })

  assert.deepEqual(collectFlowBlocks(root), [
    { top: 0, bottom: 20, heading: true },
    { top: 20, bottom: 70, heading: false },
    { top: 70, bottom: 120, heading: false },
    { top: 120, bottom: 300, heading: false },
  ])
  assert.deepEqual(collectFlowBlocks(null), [])
})

test('the studio pages the body on both canvases and keeps the footer on the page', () => {
  const workbench = readFileSync(new URL('../../resources/js/Components/report-studio/studio-workbench.vue', import.meta.url), 'utf8')
  const measure = readFileSync(new URL('../../resources/js/Components/report-studio/studio-flow-measure.vue', import.meta.url), 'utf8')

  assert.equal(workbench.match(/<StudioFlowMeasure/g)?.length, 2)
  assert.match(workbench, /@measured="recordFlowBreaks\('editor', \$event\)"/)
  assert.match(workbench, /@measured="recordFlowBreaks\('preview', \$event\)"/)
  assert.match(workbench, /const previewPages = computed\(\(\) => flowPages\(previewSegments\.value, flowBreaks\.value\)\)/)
  assert.equal(workbench.match(/class="studio-preview-flow" :style="flowWindowStyle\((editorCanvasPage|previewPage)\)"/g)?.length, 2)
  assert.equal(workbench.match(/data-canvas-surface="content" class="relative min-h-0 flex-1 /g)?.length, 2)

  // The window always clips, so a page never shows what belongs to the one before.
  assert.match(workbench, /page\.limit === null \? \{ overflow: 'hidden' \}/)

  // The skeleton measures with the spacing the canvas surfaces have.
  assert.match(measure, /headerSurfaceStyle = \{ minHeight: '104px' \}/)
  assert.match(measure, /footerSurfaceStyle = \{ minHeight: '96px' \}/)
  assert.match(measure, /emit\('measured', null\)/)
  for (const [surface, minHeight] of [['header', '104px'], ['footer', '96px']]) {
    assert.match(workbench, new RegExp(`data-canvas-surface="${surface}"[^>]*minHeight: '${minHeight}'`), surface)
  }

  assert.match(buildReportStudioPreviewCss('', ''), /\.studio-preview-document \.studio-preview-body\{display:flow-root;\}/)
})
