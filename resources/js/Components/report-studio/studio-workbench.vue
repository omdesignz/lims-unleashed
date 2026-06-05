<script setup>
import fancyTextarea from '@/Components/fancy-textarea.vue'
import { chartSvgPalette, normalizeStudioChartList, normalizedHexColor, sanitizeStudioChartSvg } from '@/Support/report-studio-chart-palette.mjs'
import { escapePreviewHtmlAttribute, escapePreviewHtmlText, safePreviewCssUrl, safePreviewMediaUrl } from '@/Support/report-studio-preview-safety.mjs'
import { buildReportStudioPreviewCss } from '@/Support/report-studio-preview-styles.mjs'
import { uploadedStudioAssetKind } from '@/Support/report-studio-media-assets.mjs'
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { create as createQrCode } from 'qrcode'
import { trans } from 'laravel-vue-i18n'
import {
  ArrowUturnLeftIcon,
  DocumentDuplicateIcon,
  EyeIcon,
  EyeSlashIcon,
  LockClosedIcon,
  LockOpenIcon,
  PaintBrushIcon,
  PhotoIcon,
  RectangleGroupIcon,
  SparklesIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  intro: {
    type: String,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  layoutSchema: {
    type: Object,
    required: true,
  },
  exportSettings: {
    type: Object,
    required: true,
  },
  placeholders: {
    type: Array,
    default: () => [],
  },
  presets: {
    type: Array,
    default: () => [],
  },
  backHref: {
    type: String,
    required: true,
  },
  backLabel: {
    type: String,
    default: 'Voltar',
  },
  submitLabel: {
    type: String,
    default: 'Guardar modelo',
  },
  initialDraftLabel: {
    type: String,
    default: '',
  },
  previewReplacements: {
    type: Object,
    default: () => ({}),
  },
  previewPdfHref: {
    type: String,
    default: '',
  },
  draftPreviewHref: {
    type: String,
    default: '',
  },
  assetLibrary: {
    type: Array,
    default: () => [],
  },
  rendererCapabilities: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['submit', 'update:studio-type'])

const contentLength = computed(() => props.layoutSchema.body_html?.length ?? 0)
const mediaAssetUrl = ref(props.layoutSchema.background_image_path || '')
const snippetTarget = ref('content')
const previewMode = ref('first-page')
const previewDisplayMode = ref('paged')
const activePreviewPage = ref(1)
const selectedCanvasBlockId = ref(null)
const interactionState = ref(null)
const focalDragState = ref(null)
const alignmentGuides = ref({ x: [], y: [] })
const showCanvasGrid = ref(booleanLayoutSetting('show_canvas_grid', true))
const showSafeArea = ref(booleanLayoutSetting('page_safe_area', true))
const showCanvasRulers = ref(booleanLayoutSetting('show_canvas_rulers', true))
const snapToGrid = ref(booleanLayoutSetting('snap_to_grid', true))
const gridSize = ref(numericLayoutSetting('snap_grid_size', 4, 1, 24))
const mediaPickerOpen = ref(false)
const mediaPickerTarget = ref({ scope: 'asset-url', field: 'image_url' })
const mediaPickerSearch = ref('')
const mediaPickerKind = ref('all')
const mediaPickerManualUrl = ref('')
const activeStudioPane = ref('setup')
const localAssetLibrary = ref([...props.assetLibrary])
const backgroundUploadInput = ref(null)
const backgroundUploadDragging = ref(false)
const backgroundUploadBusy = ref(false)
const backgroundUploadProgress = ref(0)
const backgroundUploadError = ref('')
const editorRailMode = ref('layers')
const editorInspectorMode = ref('layout')
const canvasZoom = ref(0.92)
const mediaPickerUploadInput = ref(null)
const mediaPickerUploadDragging = ref(false)
const mediaPickerUploadBusy = ref(false)
const mediaPickerUploadProgress = ref(0)
const mediaPickerUploadError = ref('')
const pdfWorkspaceSection = ref('surfaces')
const advancedCompositionOpen = ref(false)
const draftPreviewBusy = ref(false)
const draftPreviewError = ref('')

function studioCopy(key, replacements = {}, fallback = '') {
  const translationKey = `gestlab.general.labels.vap_report_studios.studio.${key}`
  const translated = trans(translationKey, replacements)

  return translated === translationKey ? fallback : translated
}

const canvasZoomOptions = [
  { value: 0.72, label: '72%' },
  { value: 0.86, label: '86%' },
  { value: 0.92, label: studioCopy('zoom.fit', {}, 'Ajustar') },
  { value: 1, label: '100%' },
  { value: 1.12, label: '112%' },
]

const studioPanes = [
  { value: 'setup', label: studioCopy('panes.setup.label', {}, 'Configurar'), description: studioCopy('panes.setup.description', {}, 'Modelo, preset, tipo e tema') },
  { value: 'compose', label: studioCopy('panes.compose.label', {}, 'Compor'), description: studioCopy('panes.compose.description', {}, 'Conteúdo, blocos e multimédia') },
  { value: 'pdf', label: studioCopy('panes.pdf.label', {}, 'PDF'), description: studioCopy('panes.pdf.description', {}, 'Páginas, margens e saída') },
  { value: 'preview', label: studioCopy('panes.preview.label', {}, 'Pré-visualizar'), description: studioCopy('panes.preview.description', {}, 'Conferir saída antes de guardar') },
]

const pdfWorkspaceSections = [
  { value: 'surfaces', label: studioCopy('pdf_sections.surfaces.label', {}, 'Estrutura'), description: studioCopy('pdf_sections.surfaces.description', {}, 'Cabeçalhos, rodapé e fundo') },
  { value: 'tables', label: studioCopy('pdf_sections.tables.label', {}, 'Tabelas'), description: studioCopy('pdf_sections.tables.description', {}, 'Leitura e densidade técnica') },
  { value: 'page', label: studioCopy('pdf_sections.page.label', {}, 'Página'), description: studioCopy('pdf_sections.page.description', {}, 'Formato, orientação e margens') },
  { value: 'output', label: studioCopy('pdf_sections.output.label', {}, 'Saída'), description: studioCopy('pdf_sections.output.description', {}, 'Renderizador e validação final') },
]

const studioStatusOptions = [
  { value: 'draft', label: studioCopy('status.draft.label', {}, 'Rascunho'), description: studioCopy('status.draft.description', {}, 'Ainda em construção e indisponível como modelo activo.') },
  { value: 'active', label: studioCopy('status.active.label', {}, 'Activo'), description: studioCopy('status.active.description', {}, 'Disponível para gerar novos documentos deste tipo.') },
  { value: 'archived', label: studioCopy('status.archived.label', {}, 'Arquivado'), description: studioCopy('status.archived.description', {}, 'Preservado para histórico, sem utilização em novos documentos.') },
]

const editorInspectorModes = [
  { value: 'layout', label: studioCopy('inspector_modes.layout', {}, 'Layout') },
  { value: 'style', label: studioCopy('inspector_modes.style', {}, 'Estilo') },
  { value: 'content', label: studioCopy('inspector_modes.content', {}, 'Conteúdo') },
  { value: 'media', label: studioCopy('inspector_modes.media', {}, 'Multimédia') },
]

const studioLabels = computed(() => ({
  analysis: {
    singular: studioCopy('document_labels.analysis.singular', {}, 'relatório analítico'),
    plural: studioCopy('document_labels.analysis.plural', {}, 'relatórios analíticos'),
    badge: studioCopy('document_labels.analysis.badge', {}, 'Studio analítico multi-página'),
  },
  executive: {
    singular: studioCopy('document_labels.executive.singular', {}, 'relatório executivo'),
    plural: studioCopy('document_labels.executive.plural', {}, 'relatórios executivos'),
    badge: studioCopy('document_labels.executive.badge', {}, 'Studio executivo multi-página'),
  },
  export_certificate: {
    singular: studioCopy('document_labels.export_certificate.singular', {}, 'certificado de exportação'),
    plural: studioCopy('document_labels.export_certificate.plural', {}, 'certificados de exportação'),
    badge: studioCopy('document_labels.export_certificate.badge', {}, 'Studio de exportação multi-página'),
  },
  import_certificate: {
    singular: studioCopy('document_labels.import_certificate.singular', {}, 'certificado de importação'),
    plural: studioCopy('document_labels.import_certificate.plural', {}, 'certificados de importação'),
    badge: studioCopy('document_labels.import_certificate.badge', {}, 'Studio de importação multi-página'),
  },
  quote: {
    singular: studioCopy('document_labels.quote.singular', {}, 'proforma'),
    plural: studioCopy('document_labels.quote.plural', {}, 'proformas'),
    badge: studioCopy('document_labels.quote.badge', {}, 'Studio comercial multi-página'),
  },
  invoice: {
    singular: studioCopy('document_labels.invoice.singular', {}, 'factura'),
    plural: studioCopy('document_labels.invoice.plural', {}, 'facturas'),
    badge: studioCopy('document_labels.invoice.badge', {}, 'Studio fiscal multi-página'),
  },
  receipt: {
    singular: studioCopy('document_labels.receipt.singular', {}, 'recibo'),
    plural: studioCopy('document_labels.receipt.plural', {}, 'recibos'),
    badge: studioCopy('document_labels.receipt.badge', {}, 'Studio de recebimento multi-página'),
  },
  credit_note: {
    singular: studioCopy('document_labels.credit_note.singular', {}, 'nota de crédito'),
    plural: studioCopy('document_labels.credit_note.plural', {}, 'notas de crédito'),
    badge: studioCopy('document_labels.credit_note.badge', {}, 'Studio de rectificação multi-página'),
  },
  proposal: {
    singular: studioCopy('document_labels.proposal.singular', {}, 'proposta'),
    plural: studioCopy('document_labels.proposal.plural', {}, 'propostas'),
    badge: studioCopy('document_labels.proposal.badge', {}, 'Studio interno multi-página'),
  },
}[props.form.studio_type] || {
  singular: studioCopy('document_labels.default.singular', {}, 'documento'),
  plural: studioCopy('document_labels.default.plural', {}, 'documentos'),
  badge: studioCopy('document_labels.default.badge', {}, 'Studio multi-página'),
}))

function studioTypeLabel(type) {
  return {
    analysis: studioCopy('document_types.analysis', {}, 'Relatório analítico'),
    executive: studioCopy('document_types.executive', {}, 'Relatório executivo'),
    proposal: studioCopy('document_types.proposal', {}, 'Proposta'),
    export_certificate: studioCopy('document_types.export_certificate', {}, 'Certificado de exportação'),
    import_certificate: studioCopy('document_types.import_certificate', {}, 'Certificado de importação'),
    quote: studioCopy('document_types.quote', {}, 'Proforma'),
    invoice: studioCopy('document_types.invoice', {}, 'Factura'),
    receipt: studioCopy('document_types.receipt', {}, 'Recibo'),
    credit_note: studioCopy('document_types.credit_note', {}, 'Nota de crédito'),
  }[type] || studioCopy('document_types.default', {}, 'Documento')
}

const snippetTargetOptions = [
  { value: 'content', label: studioCopy('surfaces.content', {}, 'Corpo do documento') },
  { value: 'first_page_header_html', label: studioCopy('surfaces.first_page_header_html', {}, 'Cabeçalho da primeira página') },
  { value: 'default_header_html', label: studioCopy('surfaces.default_header_html', {}, 'Cabeçalho padrão') },
  { value: 'footer_html', label: studioCopy('surfaces.footer_html', {}, 'Rodapé') },
  { value: 'styles_css', label: studioCopy('surfaces.styles_css', {}, 'CSS adicional') },
]

const backgroundFitOptions = [
  { value: 'cover', label: studioCopy('media_fit.cover', {}, 'Cobrir página') },
  { value: 'contain', label: studioCopy('media_fit.contain', {}, 'Conter') },
  { value: 'auto', label: studioCopy('media_fit.auto', {}, 'Tamanho original') },
]

const backgroundPositionOptions = [
  { value: 'center center', label: studioCopy('media_position.center_center', {}, 'Centro') },
  { value: 'top center', label: studioCopy('media_position.top_center', {}, 'Topo ao centro') },
  { value: 'top left', label: studioCopy('media_position.top_left', {}, 'Topo à esquerda') },
  { value: 'top right', label: studioCopy('media_position.top_right', {}, 'Topo à direita') },
  { value: 'bottom center', label: studioCopy('media_position.bottom_center', {}, 'Base ao centro') },
  { value: '0% 100%', label: studioCopy('media_position.bottom_left', {}, 'Base à esquerda') },
  { value: '100% 100%', label: studioCopy('media_position.bottom_right', {}, 'Base à direita') },
]

const imagePositionOptions = [
  { value: 'center center', label: studioCopy('media_position.center_center', {}, 'Centro') },
  { value: 'top center', label: studioCopy('media_position.top', {}, 'Topo') },
  { value: 'bottom center', label: studioCopy('media_position.bottom', {}, 'Base') },
  { value: 'center left', label: studioCopy('media_position.left', {}, 'Esquerda') },
  { value: 'center right', label: studioCopy('media_position.right', {}, 'Direita') },
  { value: '0% 0%', label: studioCopy('media_position.top_left_corner', {}, 'Canto superior esquerdo') },
  { value: '100% 0%', label: studioCopy('media_position.top_right_corner', {}, 'Canto superior direito') },
  { value: '0% 100%', label: studioCopy('media_position.bottom_left_corner', {}, 'Canto inferior esquerdo') },
  { value: '100% 100%', label: studioCopy('media_position.bottom_right_corner', {}, 'Canto inferior direito') },
]

const backgroundRepeatOptions = [
  { value: 'no-repeat', label: studioCopy('media_repeat.no_repeat', {}, 'Sem repetição') },
  { value: 'repeat-x', label: studioCopy('media_repeat.repeat_x', {}, 'Repetir horizontalmente') },
  { value: 'repeat-y', label: studioCopy('media_repeat.repeat_y', {}, 'Repetir verticalmente') },
  { value: 'repeat', label: studioCopy('media_repeat.repeat', {}, 'Repetir em mosaico') },
]

const themePresetOptions = [
  { value: 'corporate', label: studioCopy('themes.corporate', {}, 'Corporativo premium') },
  { value: 'compliance', label: studioCopy('themes.compliance', {}, 'Conformidade / ISO') },
  { value: 'field', label: studioCopy('themes.field', {}, 'Recolha e operações') },
  { value: 'minimal', label: studioCopy('themes.minimal', {}, 'Editorial minimalista') },
]

const studioFontOptions = [
  { value: 'Manrope, DejaVu Sans, sans-serif', label: studioCopy('fonts.brand.label', {}, 'Marca GestLab / Manrope'), description: studioCopy('fonts.brand.description', {}, 'A fonte principal da aplicação, limpa e séria para documentos premium.') },
  { value: 'Century Gothic, CenturyGothic, AppleGothic, DejaVu Sans, sans-serif', label: studioCopy('fonts.century_gothic.label', {}, 'Century Gothic'), description: studioCopy('fonts.century_gothic.description', {}, 'Geometria limpa para relatórios, propostas e capas com presença editorial.') },
  { value: 'DejaVu Sans, sans-serif', label: studioCopy('fonts.pdf_safe.label', {}, 'PDF seguro / DejaVu Sans'), description: studioCopy('fonts.pdf_safe.description', {}, 'Fallback mais compatível com mPDF e servidores sem fontes instaladas.') },
  { value: 'Georgia, serif', label: studioCopy('fonts.editorial_serif.label', {}, 'Editorial serifado'), description: studioCopy('fonts.editorial_serif.description', {}, 'Bom para capas institucionais e documentos mais formais.') },
  { value: 'Arial, sans-serif', label: studioCopy('fonts.universal.label', {}, 'Compatibilidade universal'), description: studioCopy('fonts.universal.description', {}, 'Opção conservadora para ambientes PDF antigos.') },
]

const allowedBackgroundMimeTypes = [
  'image/svg+xml',
  'image/png',
  'image/jpeg',
  'image/webp',
  'image/gif',
  'image/avif',
]

const pageSizeCatalog = {
  A4: { width: 210, height: 297 },
  Letter: { width: 216, height: 279 },
  Legal: { width: 216, height: 356 },
}

const pageFormatOptions = [
  { value: 'A4', label: 'A4', description: studioCopy('page_formats.a4.description', {}, 'Relatórios técnicos, certificados e propostas padrão.'), dimensions: pageSizeCatalog.A4 },
  { value: 'Letter', label: 'Letter', description: studioCopy('page_formats.letter.description', {}, 'Clientes internacionais que exigem papel norte-americano.'), dimensions: pageSizeCatalog.Letter },
  { value: 'Legal', label: 'Legal', description: studioCopy('page_formats.legal.description', {}, 'Contratos, anexos e propostas comerciais mais longas.'), dimensions: pageSizeCatalog.Legal },
  { value: 'custom', label: studioCopy('page_formats.custom.label', {}, 'Personalizado'), description: studioCopy('page_formats.custom.description', {}, 'Etiquetas, formatos especiais e documentos com medidas próprias.'), dimensions: null },
]

const orientationOptions = [
  { value: 'P', label: studioCopy('orientation.portrait', {}, 'Vertical') },
  { value: 'L', label: studioCopy('orientation.landscape', {}, 'Horizontal') },
]

const marginProfileOptions = [
  {
    key: 'controlled',
    label: studioCopy('margin_profiles.controlled.label', {}, 'Controlado'),
    description: studioCopy('margin_profiles.controlled.description', {}, 'Cabeçalho forte, rodapé com paginação e corpo técnico confortável.'),
    values: { margin_top: 20, margin_right: 14, margin_bottom: 22, margin_left: 14, first_page_margin_top: 56 },
  },
  {
    key: 'executive',
    label: studioCopy('margin_profiles.executive.label', {}, 'Executivo'),
    description: studioCopy('margin_profiles.executive.description', {}, 'Mais respiro para capas, destaques, assinaturas e documentos comerciais.'),
    values: { margin_top: 24, margin_right: 18, margin_bottom: 24, margin_left: 18, first_page_margin_top: 64 },
  },
  {
    key: 'technical-dense',
    label: studioCopy('margin_profiles.technical_dense.label', {}, 'Técnico denso'),
    description: studioCopy('margin_profiles.technical_dense.description', {}, 'Mais área útil para tabelas longas sem eliminar protecção de página.'),
    values: { margin_top: 16, margin_right: 10, margin_bottom: 18, margin_left: 10, first_page_margin_top: 44 },
  },
  {
    key: 'full-bleed',
    label: studioCopy('margin_profiles.full_bleed.label', {}, 'Capa / fundo total'),
    description: studioCopy('margin_profiles.full_bleed.description', {}, 'Margens mínimas para páginas com fundo visual ou capa editorial.'),
    values: { margin_top: 8, margin_right: 8, margin_bottom: 10, margin_left: 8, first_page_margin_top: 12 },
  },
]

const rendererOptions = computed(() => [
  {
    value: 'internal',
    label: studioCopy('renderers.internal.label', {}, 'mPDF interno'),
    badge: 'CSS 2.1',
    available: true,
    description: studioCopy('renderers.internal.description', {}, 'Seguro para documentos clássicos, cabeçalhos, rodapés e tabelas. Não é 1:1 com o browser quando usa grid, flex avançado, filtros, transformações, sombras complexas ou CSS moderno.'),
  },
  {
    value: 'chrome',
    label: studioCopy('renderers.chrome.label', {}, 'Spatie Laravel PDF · Chrome'),
    badge: props.rendererCapabilities?.chrome?.available ? studioCopy('renderers.badges.available', {}, 'Disponível') : studioCopy('renderers.badges.check_server', {}, 'Verificar servidor'),
    available: Boolean(props.rendererCapabilities?.chrome?.available),
    description: props.rendererCapabilities?.chrome?.description || studioCopy('renderers.chrome.description', {}, 'Renderização Chromium para maior fidelidade ao canvas. Requer chrome-php/chrome e Chrome/Chromium no servidor.'),
    binaryPath: props.rendererCapabilities?.chrome?.binary_path || '',
    binaryConfigured: Boolean(props.rendererCapabilities?.chrome?.binary_configured),
    binaryExecutable: Boolean(props.rendererCapabilities?.chrome?.binary_executable),
  },
  {
    value: 'browsershot',
    label: studioCopy('renderers.browsershot.label', {}, 'Spatie Laravel PDF · Browsershot'),
    badge: props.rendererCapabilities?.browsershot?.available ? studioCopy('renderers.badges.available', {}, 'Disponível') : studioCopy('renderers.badges.requires_driver', {}, 'Requer driver'),
    available: Boolean(props.rendererCapabilities?.browsershot?.available),
    description: props.rendererCapabilities?.browsershot?.description || studioCopy('renderers.browsershot.description', {}, 'Renderização Chromium via Puppeteer/Browsershot. Requer spatie/browsershot, Node e Chrome/Chromium no servidor.'),
  },
  {
    value: 'canva',
    label: studioCopy('renderers.canva.label', {}, 'Ligado ao Canva'),
    badge: studioCopy('renderers.badges.reference', {}, 'Referência'),
    available: true,
    description: studioCopy('renderers.canva.description', {}, 'Mantém uma referência externa de design. Não substitui a geração PDF interna do studio.'),
  },
])

const selectedRendererOption = computed(() => rendererOptions.value.find((option) => option.value === props.form.renderer) || rendererOptions.value[0])
const rendererSelectionUnavailable = computed(() => selectedRendererOption.value && !selectedRendererOption.value.available)

const canvasSurfaceOptions = [
  { value: 'content', label: studioCopy('surfaces.content', {}, 'Corpo do documento') },
  { value: 'first_page_header_html', label: studioCopy('surfaces.first_page_header_html', {}, 'Cabeçalho da primeira página') },
  { value: 'default_header_html', label: studioCopy('surfaces.default_header_html', {}, 'Cabeçalho padrão') },
  { value: 'footer_html', label: studioCopy('surfaces.footer_html', {}, 'Rodapé') },
]

const canvasContentScopeOptions = [
  { value: 'first', label: studioCopy('page_scopes.first', {}, 'Primeira página') },
  { value: 'all', label: studioCopy('page_scopes.all', {}, 'Todas as páginas') },
  { value: 'following', label: studioCopy('page_scopes.following', {}, 'Páginas seguintes') },
  { value: 'specific', label: studioCopy('page_scopes.specific', {}, 'Página específica') },
]

const blockKindOptions = [
  { value: 'rich_text', label: studioCopy('block_kinds.rich_text', {}, 'Conteúdo livre') },
  { value: 'image', label: studioCopy('block_kinds.image', {}, 'Imagem') },
  { value: 'chart_snapshot', label: studioCopy('block_kinds.chart_snapshot', {}, 'Gráfico / captura') },
  { value: 'stamp', label: studioCopy('block_kinds.stamp', {}, 'Carimbo / selo') },
  { value: 'signature', label: studioCopy('block_kinds.signature', {}, 'Assinatura') },
  { value: 'qr_code', label: studioCopy('block_kinds.qr_code', {}, 'Código QR') },
]

const chartTypeOptions = [
  { value: 'bar', label: studioCopy('chart_types.bar', {}, 'Barras') },
  { value: 'line', label: studioCopy('chart_types.line', {}, 'Linha') },
  { value: 'doughnut', label: studioCopy('chart_types.doughnut', {}, 'Anel') },
]

const defaultChartPalette = ['#143d37', '#d9b05f', '#0f766e', '#475569', '#7c2d12', '#3f6f58']

const tableStylePresets = [
  {
    key: 'iso-controlled',
    label: studioCopy('table_presets.iso_controlled.label', {}, 'ISO controlado'),
    description: studioCopy('table_presets.iso_controlled.description', {}, 'Cabeçalho institucional, contraste alto e bordas sóbrias para relatórios técnicos.'),
    values: {
      table_header_background: '#143d37',
      table_header_text_color: '#fffaf0',
      table_border_color: '#ded3bf',
      table_font_size: 10,
      table_cell_padding: 8,
      table_summary_background: '#fffdf7',
      table_summary_text_color: '#15231f',
      table_summary_muted_color: '#64748b',
    },
  },
  {
    key: 'analysis-bilingual',
    label: studioCopy('table_presets.analysis_bilingual.label', {}, 'Análise bilingue'),
    description: studioCopy('table_presets.analysis_bilingual.description', {}, 'Densidade adequada para tabelas com título em português e legenda técnica em inglês.'),
    values: {
      table_header_background: '#0f4a44',
      table_header_text_color: '#f8f4ea',
      table_border_color: '#b8d7d1',
      table_font_size: 9,
      table_cell_padding: 7,
      table_summary_background: '#f6fbf8',
      table_summary_text_color: '#123b35',
      table_summary_muted_color: '#54736b',
    },
  },
  {
    key: 'commercial-clean',
    label: studioCopy('table_presets.commercial_clean.label', {}, 'Comercial limpo'),
    description: studioCopy('table_presets.commercial_clean.description', {}, 'Mais respiro para propostas, facturas e documentos que precisam de leitura executiva.'),
    values: {
      table_header_background: '#241c15',
      table_header_text_color: '#fffaf0',
      table_border_color: '#e6dccb',
      table_font_size: 10,
      table_cell_padding: 10,
      table_summary_background: '#fffaf0',
      table_summary_text_color: '#241c15',
      table_summary_muted_color: '#7a6a56',
    },
  },
  {
    key: 'traceability-compact',
    label: studioCopy('table_presets.traceability_compact.label', {}, 'Rastreio compacto'),
    description: studioCopy('table_presets.traceability_compact.description', {}, 'Linhas mais densas para anexos, cadeias de custódia e listas longas sem perder legibilidade.'),
    values: {
      table_header_background: '#1f2937',
      table_header_text_color: '#ffffff',
      table_border_color: '#cbd5e1',
      table_font_size: 8,
      table_cell_padding: 5,
      table_summary_background: '#f8fafc',
      table_summary_text_color: '#1f2937',
      table_summary_muted_color: '#64748b',
    },
  },
]

const canvasLayoutPresets = [
  {
    key: 'hero',
    label: studioCopy('canvas_layout_presets.hero.label', {}, 'Hero'),
    description: studioCopy('canvas_layout_presets.hero.description', {}, 'Faixa superior ampla para capa.'),
    values: { x: 0, y: 0, width: 100, min_height: 140, padding: 24, border_radius: 28 },
  },
  {
    key: 'badge',
    label: studioCopy('canvas_layout_presets.badge.label', {}, 'Selo'),
    description: studioCopy('canvas_layout_presets.badge.description', {}, 'Chip ou badge destacado no canto.'),
    values: { x: 68, y: 6, width: 26, min_height: 24, padding: 10, border_radius: 999 },
  },
  {
    key: 'sidebar',
    label: studioCopy('canvas_layout_presets.sidebar.label', {}, 'Lateral'),
    description: studioCopy('canvas_layout_presets.sidebar.description', {}, 'Bloco vertical lateral para destaques.'),
    values: { x: 68, y: 16, width: 28, min_height: 180, padding: 18, border_radius: 20 },
  },
  {
    key: 'callout',
    label: studioCopy('canvas_layout_presets.callout.label', {}, 'Destaque'),
    description: studioCopy('canvas_layout_presets.callout.description', {}, 'Destaque intermédio dentro do conteúdo.'),
    values: { x: 8, y: 18, width: 84, min_height: 96, padding: 18, border_radius: 22 },
  },
]

const canvasShadowPresets = [
  { value: 'none', label: studioCopy('shadow_presets.none', {}, 'Sem sombra'), css: 'none' },
  { value: 'soft', label: studioCopy('shadow_presets.soft', {}, 'Sombra suave'), css: '0 14px 32px rgba(15, 23, 42, 0.10)' },
  { value: 'elevated', label: studioCopy('shadow_presets.elevated', {}, 'Elevado premium'), css: '0 22px 55px rgba(15, 23, 42, 0.18)' },
  { value: 'stamp', label: studioCopy('shadow_presets.stamp', {}, 'Carimbo impresso'), css: '0 10px 22px rgba(20, 61, 55, 0.22)' },
  { value: 'glow', label: studioCopy('shadow_presets.glow', {}, 'Realce dourado'), css: '0 0 0 6px rgba(217, 176, 95, 0.16), 0 20px 45px rgba(15, 23, 42, 0.16)' },
]

const themeCatalog = {
  corporate: {
    accent: 'from-primary-950 via-primary-800 to-primary-600',
    badge: 'Operacional',
    description: 'Visual executivo com capa forte, contraste alto e tabelas comerciais mais sóbrias.',
    styles: [
      'body{font-family:Manrope,DejaVu Sans,sans-serif;color:#15231f;font-size:11px;line-height:1.6;}',
      'h1{font-size:28px;line-height:1.15;color:#15231f;margin:0 0 18px;font-weight:700;}',
      'h2{font-size:16px;color:#15231f;margin:24px 0 10px;font-weight:700;}',
      'p{margin:0 0 10px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th{background:#143d37;color:#fffaf0;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;}',
      'th,td{border:1px solid #ded3bf;padding:8px;vertical-align:top;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #143d37;padding-bottom:12px;"><div><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#6b7b74;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;color:#15231f;">{{document_code}}</h2></div><div style="text-align:right;font-size:10px;color:#6b7b74;"><div>{{customer_name}}</div><div>{{issue_date}}</div></div></div>',
    defaultHeaderHtml: '<div style="display:flex;justify-content:space-between;font-size:10px;color:#475a53;"><span>{{document_code}}</span><span>{{customer_name}}</span></div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#475a53;border-top:1px solid #ded3bf;padding-top:6px;"><span>Documento controlado</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
  compliance: {
    accent: 'from-primary-950 via-primary-800 to-primary-600',
    badge: 'ISO 17025',
    description: 'Ênfase na decisão, rastreabilidade, incerteza e linguagem mais regulatória.',
    styles: [
      'body{font-family:Manrope,DejaVu Sans,sans-serif;color:#052e16;font-size:11px;line-height:1.65;}',
      'h1{font-size:26px;color:#14532d;margin:0 0 18px;font-weight:700;}',
      'h2{font-size:15px;color:#14532d;margin:22px 0 10px;font-weight:700;}',
      'p{margin:0 0 10px;}',
      'ul{padding-left:18px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th{background:#14532d;color:#ffffff;font-size:10px;letter-spacing:0.05em;text-transform:uppercase;}',
      'th,td{border:1px solid #bbf7d0;padding:8px;vertical-align:top;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="border-bottom:2px solid #14532d;padding-bottom:12px;"><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#166534;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;color:#14532d;">{{document_code}}</h2><p style="margin:8px 0 0;font-size:10px;color:#166534;">Decisão, conformidade e rastreabilidade documental</p></div>',
    defaultHeaderHtml: '<div style="display:flex;justify-content:space-between;font-size:10px;color:#166534;"><span>{{document_code}}</span><span>{{customer_name}}</span></div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#166534;border-top:1px solid #86efac;padding-top:6px;"><span>Regra de decisão e rastreabilidade</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
  field: {
    accent: 'from-amber-950 via-amber-700 to-orange-600',
    badge: 'Campo e logística',
    description: 'Melhor para recolha, cadeia de custódia, recepção e operação fora do laboratório.',
    styles: [
      'body{font-family:Manrope,DejaVu Sans,sans-serif;color:#7c2d12;font-size:11px;line-height:1.65;}',
      'h1{font-size:26px;color:#9a3412;margin:0 0 18px;font-weight:700;}',
      'h2{font-size:15px;color:#9a3412;margin:22px 0 10px;font-weight:700;}',
      'p{margin:0 0 10px;}',
      'ul{padding-left:18px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th{background:#9a3412;color:#ffffff;font-size:10px;letter-spacing:0.05em;text-transform:uppercase;}',
      'th,td{border:1px solid #fdba74;padding:8px;vertical-align:top;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #9a3412;padding-bottom:12px;"><div><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#c2410c;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;color:#9a3412;">Operação de recolha {{document_code}}</h2></div><div style="text-align:right;font-size:10px;color:#c2410c;"><div>{{service_location}}</div><div>{{issue_date}}</div></div></div>',
    defaultHeaderHtml: '<div style="display:flex;justify-content:space-between;font-size:10px;color:#c2410c;"><span>{{document_code}}</span><span>Recolha / logística</span></div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#9a3412;border-top:1px solid #fdba74;padding-top:6px;"><span>Cadeia de custódia</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
  minimal: {
    accent: 'from-primary-950 via-primary-700 to-accent-400',
    badge: 'Editorial',
    description: 'Superfícies limpas, muito espaço em branco e menos ruído visual.',
    styles: [
      'body{font-family:Manrope,DejaVu Sans,sans-serif;color:#15231f;font-size:11px;line-height:1.7;}',
      'h1{font-size:30px;color:#15231f;margin:0 0 22px;font-weight:600;}',
      'h2{font-size:15px;color:#15231f;margin:24px 0 10px;font-weight:600;}',
      'p{margin:0 0 10px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th,td{border-bottom:1px solid #ded3bf;padding:8px 4px;vertical-align:top;}',
      'th{font-size:10px;color:#6b7b74;letter-spacing:0.06em;text-transform:uppercase;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="padding-bottom:12px;border-bottom:1px solid #ded3bf;"><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#6b7b74;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;color:#15231f;">{{document_code}}</h2></div>',
    defaultHeaderHtml: '<div style="font-size:10px;color:#6b7b74;">{{document_code}} · {{customer_name}}</div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#6b7b74;border-top:1px solid #ded3bf;padding-top:6px;"><span>Documento controlado</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
}

const surfaceBindings = computed(() => ({
  content: {
    get value() {
      return props.layoutSchema.body_html || ''
    },
    set value(value) {
      props.layoutSchema.body_html = value
    },
  },
  first_page_header_html: {
    get value() {
      return props.layoutSchema.first_page_header_html || ''
    },
    set value(value) {
      props.layoutSchema.first_page_header_html = value
    },
  },
  default_header_html: {
    get value() {
      return props.layoutSchema.default_header_html || ''
    },
    set value(value) {
      props.layoutSchema.default_header_html = value
    },
  },
  footer_html: {
    get value() {
      return props.layoutSchema.footer_html || ''
    },
    set value(value) {
      props.layoutSchema.footer_html = value
    },
  },
  styles_css: {
    get value() {
      return props.layoutSchema.styles_css || ''
    },
    set value(value) {
      props.layoutSchema.styles_css = value
    },
  },
}))

const surfaceLabel = computed(() => {
  return snippetTargetOptions.find((option) => option.value === snippetTarget.value)?.label ?? 'Superfície'
})

const placeholderLabels = {
  '{document_code}': 'Código do documento',
  '{{document_code}}': 'Código do documento',
  '{certificate_number}': 'Número do certificado',
  '{proposal_number}': 'Número da proposta',
  '{document_number}': 'Número do documento',
  '{report_title}': 'Título do relatório',
  '{period_label}': 'Período analisado',
  '{issue_date}': 'Data de emissão',
  '{{issue_date}}': 'Data de emissão',
  '{customer_name}': 'Cliente',
  '{{customer_name}}': 'Cliente',
  '{lab_name}': 'Laboratório',
  '{{lab_name}}': 'Laboratório',
  '{lab_details}': 'Dados do laboratório',
  '{customer_details}': 'Dados do cliente',
  '{document_keywords}': 'Palavras-chave do documento',
  '{certificate_code}': 'Código do certificado',
  '{warehouse_name}': 'Armazém',
  '{service_location}': 'Local do serviço',
  '{sample_entry_code}': 'Código de entrada da amostra',
  '{sample_code}': 'Código da amostra',
  '{sample_name}': 'Nome da amostra',
  '{sample_type}': 'Tipo de amostra',
  '{sample_product}': 'Produto da amostra',
  '{sample_matrix}': 'Matriz da amostra',
  '{sample_lot}': 'Lote da amostra',
  '{sample_origin}': 'Origem da amostra',
  '{sampling_plan_ref}': 'Plano de amostragem',
  '{collection_date}': 'Data de recolha',
  '{received_at}': 'Data de receção',
  '{sample_details}': 'Detalhes da amostra',
  '{collection_details}': 'Receção e cadeia de custódia',
  '{analytical_scope}': 'Âmbito analítico',
  '{validated_by}': 'Responsável pela validação',
  '{conclusion}': 'Conclusão técnica',
  '{executive_summary}': 'Resumo executivo',
  '{executive_kpis}': 'Indicadores executivos',
  '{executive_charts}': 'Gráficos executivos',
  '{executive_chart_title}': 'Título do gráfico executivo',
  '{executive_chart_labels}': 'Etiquetas do gráfico executivo',
  '{executive_chart_values}': 'Valores do gráfico executivo',
  '{executive_chart_caption}': 'Legenda do gráfico executivo',
  '{top_customers_table}': 'Clientes com maior actividade',
  '{quote_number}': 'Número da proforma',
  '{expiry_date}': 'Data de validade',
  '{due_date}': 'Data de vencimento',
  '{payment_type}': 'Forma de pagamento',
  '{reason_label}': 'Motivo da rectificação',
  '{items_table}': 'Tabela de itens',
  '{summary_table}': 'Resumo financeiro',
  '{results_table}': 'Tabela de resultados',
  '{analysis_chart_title}': 'Título do gráfico de resultados',
  '{analysis_chart_labels}': 'Etiquetas do gráfico de resultados',
  '{analysis_chart_values}': 'Valores do gráfico de resultados',
  '{analysis_chart_caption}': 'Legenda do gráfico de resultados',
  '{analysis_chart_card}': 'Cartão visual dos resultados',
  '{decision_rule}': 'Regra de decisão',
  '{uncertainty_statement}': 'Declaração de incerteza',
  '{signature_block}': 'Bloco de assinatura',
  '{banking_details}': 'Dados bancários',
  '{bank_name}': 'Banco',
  '{bank_account_name}': 'Titular da conta',
  '{bank_account_number}': 'Número da conta',
  '{bank_iban}': 'IBAN',
  '{bank_swift}': 'SWIFT/BIC',
  '{bank_details}': 'Observações bancárias',
  '{verification_url}': 'Ligação de verificação',
  '{proposal_authenticity}': 'QR e autenticidade da proposta',
  '{proposal_acceptance_evidence}': 'Evidência de aceite da proposta',
  '{observations}': 'Observações',
  '{exporter_name}': 'Exportador',
  '{importer_name}': 'Importador',
  '{origin_country}': 'País de origem',
  '{destination_country}': 'País de destino',
  '{origin_city}': 'Cidade de origem',
  '{destination_city}': 'Cidade de destino',
  '{transport_type}': 'Tipo de transporte',
  '{authorized_personnel}': 'Responsável autorizado',
  '{expedition_date}': 'Data de expedição',
  '{expedition_location}': 'Local de expedição',
  '{port_entry}': 'Porto de entrada',
  '{port_exit}': 'Porto de saída',
  '{products_table}': 'Tabela de produtos',
  '{remarks}': 'Observações do certificado',
}

function placeholderLabelFor(placeholder, fallbackLabel = '') {
  return fallbackLabel
    || placeholderLabels[placeholder]
    || String(placeholder).replace(/[{}]/g, '').replaceAll('_', ' ')
}

const translatedPlaceholders = computed(() => {
  const entries = []
  const seen = new Set()
  const addPlaceholder = (value, label = '') => {
    const placeholder = String(value || '').trim()

    if (!placeholder || seen.has(placeholder)) {
      return
    }

    seen.add(placeholder)
    entries.push({
      value: placeholder,
      label: placeholderLabelFor(placeholder, label),
    })
  }

  if (Array.isArray(props.layoutSchema.variable_catalog)) {
    props.layoutSchema.variable_catalog.forEach((item) => {
      if (typeof item === 'string') {
        addPlaceholder(item)

        return
      }

      addPlaceholder(item?.value, item?.label)
    })
  }

  props.placeholders.forEach((placeholder) => addPlaceholder(placeholder))

  return entries
})

watch(() => props.assetLibrary, (assets) => {
  localAssetLibrary.value = [...assets]
}, { deep: true })

watch(() => props.layoutSchema, () => {
  syncEditorPreferencesFromLayout()
})

watch(() => props.exportSettings.paper_size, (paperSize) => {
  if (paperSize === 'custom') {
    ensureCustomPageDefaults()
  }
})

watch(showCanvasGrid, (value) => {
  props.layoutSchema.show_canvas_grid = Boolean(value)
}, { immediate: true })

watch(showSafeArea, (value) => {
  props.layoutSchema.page_safe_area = Boolean(value)
}, { immediate: true })

watch(showCanvasRulers, (value) => {
  props.layoutSchema.show_canvas_rulers = Boolean(value)
}, { immediate: true })

watch(snapToGrid, (value) => {
  props.layoutSchema.snap_to_grid = Boolean(value)
}, { immediate: true })

watch(gridSize, (value) => {
  const nextValue = clamp(Math.round(Number(value) || 4), 1, 24)
  props.layoutSchema.snap_grid_size = nextValue

  if (Number(value) !== nextValue) {
    gridSize.value = nextValue
  }
}, { immediate: true })

const documentFontFamily = computed(() => props.layoutSchema.document_font_family || 'Manrope, DejaVu Sans, sans-serif')

const signatureAssets = computed(() => localAssetLibrary.value.filter((asset) => asset.kind === 'profile_signature' || String(asset.source || '').toLowerCase().includes('assinatura')))

const mediaKindLabelMap = {
  all: studioCopy('media_picker.kinds.all'),
  gallery_image: studioCopy('media_picker.kinds.gallery_image'),
  profile_signature: studioCopy('media_picker.kinds.profile_signature'),
  uploaded_asset: studioCopy('media_picker.kinds.uploaded_asset'),
  uploaded_background: studioCopy('media_picker.kinds.uploaded_background'),
  uploaded_chart: studioCopy('media_picker.kinds.uploaded_chart'),
  uploaded_image: studioCopy('media_picker.kinds.uploaded_image'),
  uploaded_signature: studioCopy('media_picker.kinds.uploaded_signature'),
  uploaded_stamp: studioCopy('media_picker.kinds.uploaded_stamp'),
}

function mediaKindValue(asset) {
  const kind = String(asset.kind || '').trim()

  if (kind) {
    return kind
  }

  const source = String(asset.source || '').toLowerCase()

  if (source.includes('assinatura') || source.includes('signature')) {
    return 'profile_signature'
  }

  if (source.includes('fundo') || source.includes('background')) {
    return 'uploaded_background'
  }

  if (source.includes('gráfico') || source.includes('grafico') || source.includes('chart')) {
    return 'uploaded_chart'
  }

  if (source.includes('carimbo') || source.includes('selo') || source.includes('stamp')) {
    return 'uploaded_stamp'
  }

  return source ? source.replace(/\s+/g, '_') : 'file'
}

function mediaKindLabel(kind) {
  return mediaKindLabelMap[kind] || String(kind || 'Ficheiro')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const mediaKindOptions = computed(() => [
  { value: 'all', label: studioCopy('media_picker.kinds.all'), count: localAssetLibrary.value.length },
  ...[...new Set(localAssetLibrary.value.map((asset) => mediaKindValue(asset)))]
    .map((kind) => ({
      value: kind,
      label: mediaKindLabel(kind),
      count: localAssetLibrary.value.filter((asset) => mediaKindValue(asset) === kind).length,
    })),
])

function syncAdvancedCompositionState(event) {
  advancedCompositionOpen.value = Boolean(event?.target?.open)
}

const filteredMediaAssets = computed(() => {
  const query = mediaPickerSearch.value.trim().toLowerCase()

  return localAssetLibrary.value.filter((asset) => {
    const kindMatches = mediaPickerKind.value === 'all'
      || mediaKindValue(asset) === mediaPickerKind.value

    if (!kindMatches) {
      return false
    }

    if (!query) {
      return true
    }

    return [
      asset.label,
      asset.source,
      asset.author,
      asset.mime_type,
      asset.pdf_url,
    ].some((value) => String(value || '').toLowerCase().includes(query))
  })
})

const mediaPickerTargetLabel = computed(() => {
  if (mediaPickerTarget.value.scope === 'document-background') {
    return studioCopy('media_picker.target.document_background')
  }

  if (mediaPickerTarget.value.scope === 'asset-url') {
    return studioCopy('media_picker.target.asset_url')
  }

  if (mediaPickerTarget.value.scope === 'new-canvas-block') {
    return studioCopy('media_picker.target.new_canvas_block', {
      type: blockKindLabel(mediaPickerTarget.value.blockKind || 'image'),
    })
  }

  if (mediaPickerTarget.value.scope === 'selected-block') {
    return studioCopy('media_picker.target.selected_block', {
      field: mediaFieldLabel(mediaPickerTarget.value.field),
    })
  }

  return studioCopy('media_picker.target.selected')
})

const editorCanvasPage = computed(() => ({
  pageNumber: currentPreviewPage.value,
  content: previewPages.value[currentPreviewPage.value - 1] ?? '',
}))

const selectedThemePreset = computed(() => {
  return themeCatalog[props.form.theme_preset] ?? themeCatalog.corporate
})

const canvasBlocks = computed(() => {
  if (!Array.isArray(props.layoutSchema.canvas_blocks)) {
    props.layoutSchema.canvas_blocks = []
  }

  return props.layoutSchema.canvas_blocks
})

const selectedCanvasBlock = computed(() => {
  return canvasBlocks.value.find((block) => block.id === selectedCanvasBlockId.value) ?? null
})

const visibleCanvasBlocks = computed(() => canvasBlocks.value.filter((block) => !canvasBlockIsHidden(block)))

const selectedCanvasBlockKindLabel = computed(() => {
  if (!selectedCanvasBlock.value) {
    return 'Bloco'
  }

  return blockKindLabel(selectedCanvasBlock.value.block_kind)
})

const canvasBlockGroups = computed(() => {
  return canvasSurfaceOptions.map((surface) => ({
    ...surface,
    blocks: canvasBlocks.value
      .filter((block) => block.surface === surface.value)
      .sort((left, right) => Number(right.z_index || 0) - Number(left.z_index || 0)),
  })).filter((group) => group.blocks.length > 0)
})

const previewReplacementMap = computed(() => ({
  '{document_code}': 'DOC-2026-001',
  '{issue_date}': '04/05/2026',
  '{lab_name}': 'Laboratório Central',
  '{customer_name}': 'Cliente industrial de referência',
  ...props.previewReplacements,
}))

const placeholderTokenPattern = /{{\s*[\w.:/-]+\s*}}|{\s*[\w.:/-]+\s*}/g
const pageBreakTagPattern = /<pagebreak\b[^>]*\/?>/i
const paginationPlaceholderKeys = new Set(['PAGENO', 'nbpg'])

function normalisePlaceholderToken(token) {
  return String(token || '').replace(/^\{+|\}+$/g, '').trim()
}

const normalisedPreviewReplacements = computed(() => {
  const replacements = {}

  Object.entries(previewReplacementMap.value).forEach(([placeholder, replacement]) => {
    const key = normalisePlaceholderToken(placeholder)

    if (!key) {
      return
    }

    replacements[key] = replacement ?? ''
  })

  return replacements
})

function interpolatePreviewHtml(html, scopedReplacements = {}) {
  const replacements = {
    ...normalisedPreviewReplacements.value,
    ...scopedReplacements,
  }

  return String(html || '').replace(placeholderTokenPattern, (token) => {
    const key = normalisePlaceholderToken(token)

    return Object.prototype.hasOwnProperty.call(replacements, key)
      ? replacements[key]
      : token
  })
}

const contentPreview = computed(() => {
  return interpolatePreviewHtml(props.layoutSchema.body_html || '')
})

const previewPages = computed(() => {
  return contentPreview.value
    .split(pageBreakTagPattern)
    .map((segment) => segment.trim())
    .filter((segment) => segment.length > 0)
})

const currentPreviewPage = computed(() => {
  return clamp(activePreviewPage.value, 1, Math.max(previewPages.value.length, 1))
})

const visiblePreviewPages = computed(() => {
  if (previewDisplayMode.value === 'all') {
    return previewPages.value.map((content, index) => ({
      pageNumber: index + 1,
      content,
    }))
  }

  const pageNumber = currentPreviewPage.value

  return [
    {
      pageNumber,
      content: previewPages.value[pageNumber - 1] ?? '',
    },
  ]
})

const previewPageStyle = computed(() => {
  const image = safePreviewCssUrl(props.layoutSchema.background_image_path)

  return image
    ? {
        backgroundImage: image,
        backgroundSize: safeCssImageFitValue(props.layoutSchema.background_size, 'cover'),
        backgroundPosition: safeCssPositionValue(props.layoutSchema.background_position, 'center center'),
        backgroundRepeat: safeCssRepeatValue(props.layoutSchema.background_repeat, 'no-repeat'),
      }
    : {}
})

function numericSetting(value, fallback) {
  const number = Number(value)

  return Number.isFinite(number) ? number : fallback
}

const previewPageDimensions = computed(() => {
  const selectedSize = props.exportSettings.paper_size === 'custom'
    ? {
        width: numericSetting(props.exportSettings.custom_page_width, pageSizeCatalog.A4.width),
        height: numericSetting(props.exportSettings.custom_page_height, pageSizeCatalog.A4.height),
      }
    : (pageSizeCatalog[props.exportSettings.paper_size] || pageSizeCatalog.A4)

  if (props.exportSettings.orientation === 'L') {
    return {
      width: Math.max(selectedSize.width, selectedSize.height),
      height: Math.min(selectedSize.width, selectedSize.height),
    }
  }

  return {
    width: Math.min(selectedSize.width, selectedSize.height),
    height: Math.max(selectedSize.width, selectedSize.height),
  }
})

const previewPageFormatLabel = computed(() => {
  const paperSize = props.exportSettings.paper_size === 'custom'
    ? `${previewPageDimensions.value.width} × ${previewPageDimensions.value.height} mm`
    : String(props.exportSettings.paper_size || 'A4').toUpperCase()
  const orientation = props.exportSettings.orientation === 'L' ? 'Paisagem' : 'Retrato'

  return `${paperSize} · ${orientation}`
})

const previewPageMaxWidth = computed(() => props.exportSettings.orientation === 'L' ? 1080 : 860)

const previewPageFrameStyle = computed(() => {
  return {
    width: '100%',
    maxWidth: `${previewPageMaxWidth.value}px`,
    aspectRatio: `${previewPageDimensions.value.width} / ${previewPageDimensions.value.height}`,
    minHeight: 'auto',
    padding: '0',
    ...previewPageStyle.value,
    ...previewStyleVariables.value,
  }
})

const editorPageFrameStyle = computed(() => ({
  ...previewPageFrameStyle.value,
  maxWidth: `${Math.round(previewPageMaxWidth.value * Number(canvasZoom.value || 1))}px`,
}))

function stripHtml(value) {
  return String(value || '')
    .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, ' ')
    .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

function blockQualityFields(block) {
  const fields = [
    'content_html',
    'image_url',
    'background_image',
    'signature_label',
    'signature_name',
    'signature_title',
    'signature_date_label',
    'qr_content',
    'qr_label',
    'chart_title',
    'chart_caption',
    'chart_svg',
    'chart_image_url',
  ]

  return fields
    .filter((field) => block[field])
    .map((field) => ({
      label: `${block.title || blockKindLabel(block.block_kind)} · ${field.replaceAll('_', ' ')}`,
      pane: 'compose',
      value: block[field],
    }))
}

const studioSurfaceSources = computed(() => {
  const sources = [
    { label: studioCopy('surfaces.content', {}, 'Corpo do documento'), pane: 'compose', value: props.layoutSchema.body_html || '' },
    { label: studioCopy('surfaces.first_page_header_html', {}, 'Cabeçalho da primeira página'), pane: 'pdf', value: props.layoutSchema.first_page_header_html || '' },
    { label: studioCopy('surfaces.default_header_html', {}, 'Cabeçalho padrão'), pane: 'pdf', value: props.layoutSchema.default_header_html || '' },
    { label: studioCopy('surfaces.footer_html', {}, 'Rodapé'), pane: 'pdf', value: props.layoutSchema.footer_html || '' },
    { label: studioCopy('surfaces.styles_css', {}, 'CSS adicional'), pane: 'pdf', value: props.layoutSchema.styles_css || '' },
    { label: studioCopy('surfaces.document_background', {}, 'Fundo do documento'), pane: 'pdf', value: props.layoutSchema.background_image_path || '' },
  ]

  canvasBlocks.value.forEach((block) => {
    sources.push(...blockQualityFields(block))
  })

  return sources
})

const unresolvedPlaceholders = computed(() => {
  const replacements = normalisedPreviewReplacements.value
  const unresolved = new Map()

  studioSurfaceSources.value.forEach((source) => {
    const matches = String(source.value || '').match(placeholderTokenPattern) || []

    matches.forEach((token) => {
      const key = normalisePlaceholderToken(token)

      if (!key || paginationPlaceholderKeys.has(key) || Object.prototype.hasOwnProperty.call(replacements, key)) {
        return
      }

      if (!unresolved.has(key)) {
        unresolved.set(key, {
          key,
          token,
          label: token,
          source: source.label,
          pane: source.pane,
        })
      }
    })
  })

  return Array.from(unresolved.values())
})

const rendererCssRiskCatalog = [
  { key: 'grid', label: 'CSS Grid', pattern: /display\s*:\s*grid/i },
  { key: 'flex', label: studioCopy('css_risks.flex', {}, 'Flexbox avançado'), pattern: /display\s*:\s*(inline-)?flex/i },
  { key: 'filter', label: studioCopy('css_risks.filter', {}, 'Filtros visuais'), pattern: /\b(backdrop-)?filter\s*:/i },
  { key: 'transform', label: studioCopy('css_risks.transform', {}, 'Transformações'), pattern: /\btransform\s*:/i },
  { key: 'sticky', label: studioCopy('css_risks.sticky', {}, 'Posicionamento sticky'), pattern: /position\s*:\s*sticky/i },
  { key: 'gradient', label: studioCopy('css_risks.gradient', {}, 'Gradientes complexos'), pattern: /(linear|radial)-gradient\(/i },
  { key: 'shadow', label: studioCopy('css_risks.shadow', {}, 'Sombras CSS'), pattern: /\bbox-shadow\s*:/i },
]

const canvasRendererRiskSource = computed(() => {
  return visibleCanvasBlocks.value
    .map((block) => {
      const riskTokens = []

      if (Math.abs(Number(block.rotation_deg || 0)) > 0) {
        riskTokens.push('transform: rotate();')
      }

      if ((block.shadow_preset || 'soft') !== 'none') {
        riskTokens.push('box-shadow: canvas-shadow;')
      }

      if (String(block.background_color || '').includes('gradient(') || String(block.overlay_color || '').includes('gradient(')) {
        riskTokens.push('linear-gradient();')
      }

      return riskTokens.join('\n')
    })
    .filter(Boolean)
    .join('\n')
})

const rendererCssRisks = computed(() => {
  if (props.form.renderer !== 'internal') {
    return []
  }

  const documentSource = studioSurfaceSources.value
    .map((source) => source.value)
    .concat(canvasRendererRiskSource.value)
    .join('\n')

  return rendererCssRiskCatalog.filter((risk) => risk.pattern.test(documentSource))
})

const documentHasBodyContent = computed(() => {
  return stripHtml(props.layoutSchema.body_html).length > 0
    || visibleCanvasBlocks.value.some((block) => block.surface === 'content')
})

const documentHasHeader = computed(() => {
  return stripHtml(props.layoutSchema.first_page_header_html).length > 0
    || stripHtml(props.layoutSchema.default_header_html).length > 0
    || visibleCanvasBlocks.value.some((block) => ['first_page_header_html', 'default_header_html'].includes(block.surface))
})

const documentHasFooter = computed(() => {
  return stripHtml(props.layoutSchema.footer_html).length > 0
    || visibleCanvasBlocks.value.some((block) => block.surface === 'footer_html')
})

const pdfSurfaceCards = computed(() => [
  {
    surface: 'first_page_header_html',
    label: studioCopy('pdf_surface_cards.first_page_header.label', {}, 'Abertura da primeira página'),
    description: studioCopy('pdf_surface_cards.first_page_header.description', {}, 'Identidade, título e contexto documental para a página de entrada.'),
    pageNumber: 1,
  },
  {
    surface: 'default_header_html',
    label: studioCopy('pdf_surface_cards.default_header.label', {}, 'Cabeçalho das páginas seguintes'),
    description: studioCopy('pdf_surface_cards.default_header.description', {}, 'Referência compacta que acompanha documentos multi-página.'),
    pageNumber: Math.min(2, Math.max(previewPages.value.length, 1)),
  },
  {
    surface: 'footer_html',
    label: studioCopy('pdf_surface_cards.footer.label', {}, 'Rodapé e paginação'),
    description: studioCopy('pdf_surface_cards.footer.description', {}, 'Controlo documental, autenticação, versão e número de página.'),
    pageNumber: 1,
  },
].map((card) => {
  const objectCount = visibleCanvasBlocks.value.filter((block) => block.surface === card.surface).length
  const hasHtml = stripHtml(props.layoutSchema[card.surface] || '').length > 0

  return {
    ...card,
    objectCount,
    isConfigured: hasHtml || objectCount > 0,
  }
}))

const configuredPdfSurfaceCount = computed(() => {
  return pdfSurfaceCards.value.filter((surface) => surface.isConfigured).length
})

const documentHasSignature = computed(() => {
  return String(props.layoutSchema.body_html || '').includes('{signature_block}')
    || String(props.layoutSchema.body_html || '').includes('{{signature_block}}')
    || visibleCanvasBlocks.value.some((block) => block.block_kind === 'signature')
})

const documentBenefitsFromSignature = computed(() => {
  return ['analysis', 'export_certificate', 'import_certificate', 'quote', 'invoice', 'receipt', 'credit_note', 'proposal'].includes(props.form.studio_type)
})

const studioQualityIssues = computed(() => {
  const issues = []

  if (!documentHasBodyContent.value) {
    issues.push({
      key: 'empty-body',
      tone: 'critical',
      title: studioCopy('quality.issues.empty_body.title', {}, 'Documento sem corpo útil'),
      description: studioCopy('quality.issues.empty_body.description', {}, 'Adicione texto, tabelas ou blocos ao corpo antes de exportar.'),
      pane: 'compose',
    })
  }

  if (unresolvedPlaceholders.value.length) {
    const preview = unresolvedPlaceholders.value.slice(0, 3).map((placeholder) => placeholder.label).join(', ')

    issues.push({
      key: 'unresolved-placeholders',
      tone: 'critical',
      title: studioCopy('quality.issues.unresolved_placeholders.title', {
        count: unresolvedPlaceholders.value.length,
      }, `${unresolvedPlaceholders.value.length} variável${unresolvedPlaceholders.value.length === 1 ? '' : 's'} sem dados de pré-visualização`),
      description: studioCopy('quality.issues.unresolved_placeholders.description', {
        preview: `${preview}${unresolvedPlaceholders.value.length > 3 ? '…' : ''}`,
      }, `Revise ${preview}${unresolvedPlaceholders.value.length > 3 ? '…' : ''} para evitar PDF com texto cru.`),
      pane: unresolvedPlaceholders.value[0]?.pane || 'compose',
    })
  }

  if (rendererCssRisks.value.length) {
    issues.push({
      key: 'renderer-risk',
      tone: 'warning',
      title: studioCopy('quality.issues.renderer_risk.title', {}, 'mPDF pode não reproduzir o design 1:1'),
      description: studioCopy('quality.issues.renderer_risk.description', {
        risks: rendererCssRisks.value.map((risk) => risk.label).slice(0, 3).join(', '),
      }, `${rendererCssRisks.value.map((risk) => risk.label).slice(0, 3).join(', ')} exigem Chrome PDF para fidelidade superior.`),
      pane: 'pdf',
      action: 'chrome',
    })
  }

  if (!documentHasHeader.value) {
    issues.push({
      key: 'missing-header',
      tone: 'warning',
      title: studioCopy('quality.issues.missing_header.title', {}, 'Cabeçalhos por página ainda vazios'),
      description: studioCopy('quality.issues.missing_header.description', {}, 'Configure cabeçalho da primeira página e cabeçalho padrão para documentos multi-página.'),
      pane: 'pdf',
    })
  }

  if (!documentHasFooter.value) {
    issues.push({
      key: 'missing-footer',
      tone: 'warning',
      title: studioCopy('quality.issues.missing_footer.title', {}, 'Rodapé ou paginação não configurados'),
      description: studioCopy('quality.issues.missing_footer.description', {}, 'Inclua paginação, código do documento ou trilha de emissão no rodapé.'),
      pane: 'pdf',
    })
  }

  if (documentBenefitsFromSignature.value && !documentHasSignature.value) {
    issues.push({
      key: 'missing-signature',
      tone: 'advisory',
      title: studioCopy('quality.issues.missing_signature.title', {}, 'Assinatura ainda não configurada'),
      description: studioCopy('quality.issues.missing_signature.description', {}, 'Adicione bloco de assinatura ou variável {signature_block} para emissão formal.'),
      pane: 'compose',
    })
  }

  return issues
})

const studioQualityStatus = computed(() => {
  const criticalCount = studioQualityIssues.value.filter((issue) => issue.tone === 'critical').length
  const warningCount = studioQualityIssues.value.filter((issue) => issue.tone === 'warning').length

  if (criticalCount > 0) {
    return {
      label: studioCopy('quality.status.requires_review.label', {}, 'Requer revisão'),
      description: studioCopy('quality.status.requires_review.description', {}, 'Há conteúdo que pode sair incompleto no PDF.'),
      class: 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
    }
  }

  if (warningCount > 0) {
    return {
      label: studioCopy('quality.status.almost_ready.label', {}, 'Quase pronto'),
      description: studioCopy('quality.status.almost_ready.description', {}, 'O documento exporta, mas ainda há decisões de fidelidade ou paginação.'),
      class: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
    }
  }

  return {
    label: studioCopy('quality.status.ready.label', {}, 'Pronto para PDF'),
    description: studioCopy('quality.status.ready.description', {}, 'Variáveis, estrutura e renderizador estão coerentes para exportação.'),
    class: 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100',
  }
})

const studioQualityMetrics = computed(() => [
  { label: studioCopy('quality.metrics.pages', {}, 'Páginas'), value: String(previewPages.value.length || 1) },
  { label: studioCopy('quality.metrics.open_variables', {}, 'Variáveis abertas'), value: String(unresolvedPlaceholders.value.length) },
  { label: studioCopy('quality.metrics.css_risks', {}, 'Riscos CSS'), value: String(rendererCssRisks.value.length) },
])

function focusQualityIssue(issue) {
  activeStudioPane.value = issue?.pane || 'compose'
}

function preferChromeRenderer() {
  if (props.rendererCapabilities?.chrome?.available) {
    props.form.renderer = 'chrome'
  }

  activeStudioPane.value = 'pdf'
}

function pdfSurfacePreviewHtml(surface) {
  return interpolatePreviewHtml(props.layoutSchema[surface] || '', {
    PAGENO: surface === 'default_header_html' ? '2' : '1',
    nbpg: String(Math.max(previewPages.value.length, 1)),
  })
}

function editPdfSurfaceInCanvas(surface, pageNumber = 1) {
  snippetTarget.value = surface
  activePreviewPage.value = clamp(pageNumber, 1, Math.max(previewPages.value.length, 1))
  previewMode.value = surface === 'default_header_html' ? 'following-page' : 'first-page'
  activeStudioPane.value = 'compose'
}

function previewMarginPercent(value, axisSize, fallback = 0) {
  const margin = numericSetting(value, fallback)

  if (axisSize <= 0) {
    return '0%'
  }

  return `${clamp((margin / axisSize) * 100, 0, 45)}%`
}

function previewMarginStyleForPage(pageNumber) {
  const dimensions = previewPageDimensions.value
  const topMargin = Number(pageNumber) === 1
    ? numericSetting(props.exportSettings.first_page_margin_top, props.exportSettings.margin_top)
    : numericSetting(props.exportSettings.margin_top, 20)

  return {
    top: previewMarginPercent(topMargin, dimensions.height),
    right: previewMarginPercent(props.exportSettings.margin_right, dimensions.width, 14),
    bottom: previewMarginPercent(props.exportSettings.margin_bottom, dimensions.height, 20),
    left: previewMarginPercent(props.exportSettings.margin_left, dimensions.width, 14),
  }
}

function previewContentPaddingStyleForPage(pageNumber) {
  return {
    ...previewMarginStyleForPage(pageNumber),
  }
}

const previewPageSummary = computed(() => {
  const dimensions = previewPageDimensions.value
  const format = props.exportSettings.paper_size === 'custom'
    ? studioCopy('page_formats.custom.label', {}, 'Personalizado')
    : props.exportSettings.paper_size || 'A4'
  const orientation = props.exportSettings.orientation === 'L'
    ? studioCopy('orientation.landscape_lower', {}, 'horizontal')
    : studioCopy('orientation.portrait_lower', {}, 'vertical')

  return `${format} · ${Math.round(dimensions.width)} x ${Math.round(dimensions.height)} mm · ${orientation}`
})

const previewMarginSummary = computed(() => {
  const top = numericSetting(props.exportSettings.margin_top, 20)
  const right = numericSetting(props.exportSettings.margin_right, 14)
  const bottom = numericSetting(props.exportSettings.margin_bottom, 20)
  const left = numericSetting(props.exportSettings.margin_left, 14)
  const firstTop = numericSetting(props.exportSettings.first_page_margin_top, top)

  return studioCopy('page_summaries.margins', {
    top,
    right,
    bottom,
    left,
    firstTop,
  }, `Margens ${top}/${right}/${bottom}/${left} mm · primeira página ${firstTop} mm no topo`)
})

const selectedPageFormatOption = computed(() => {
  return pageFormatOptions.find((option) => option.value === props.exportSettings.paper_size) || pageFormatOptions[0]
})

const printableAreaDimensions = computed(() => {
  const dimensions = previewPageDimensions.value
  const top = numericSetting(props.exportSettings.margin_top, 20)
  const right = numericSetting(props.exportSettings.margin_right, 14)
  const bottom = numericSetting(props.exportSettings.margin_bottom, 22)
  const left = numericSetting(props.exportSettings.margin_left, 14)

  return {
    width: Math.max(0, dimensions.width - left - right),
    height: Math.max(0, dimensions.height - top - bottom),
  }
})

const printableAreaSummary = computed(() => {
  const area = printableAreaDimensions.value

  return studioCopy('page_summaries.printable_area', {
    width: Math.round(area.width),
    height: Math.round(area.height),
  }, `${Math.round(area.width)} x ${Math.round(area.height)} mm úteis`)
})

const activeMarginProfileKey = computed(() => {
  return marginProfileOptions.find((profile) => {
    return Object.entries(profile.values).every(([key, value]) => {
      return Number(props.exportSettings[key]) === Number(value)
    })
  })?.key || null
})

const exportSetupIssues = computed(() => {
  const issues = []
  const dimensions = previewPageDimensions.value
  const area = printableAreaDimensions.value
  const top = numericSetting(props.exportSettings.margin_top, 20)
  const bottom = numericSetting(props.exportSettings.margin_bottom, 22)
  const left = numericSetting(props.exportSettings.margin_left, 14)
  const right = numericSetting(props.exportSettings.margin_right, 14)
  const firstTop = numericSetting(props.exportSettings.first_page_margin_top, top)

  if (props.exportSettings.paper_size === 'custom') {
    const width = numericSetting(props.exportSettings.custom_page_width, 0)
    const height = numericSetting(props.exportSettings.custom_page_height, 0)

    if (width < 50 || height < 50) {
      issues.push({
        tone: 'critical',
        title: studioCopy('export_issues.custom_size.title', {}, 'Tamanho personalizado incompleto'),
        description: studioCopy('export_issues.custom_size.description', {}, 'Defina largura e altura reais em milímetros para o Chrome e o mPDF gerarem a mesma página.'),
      })
    }
  }

  if (left + right >= dimensions.width * 0.5) {
    issues.push({
      tone: 'critical',
      title: studioCopy('export_issues.side_margins.title', {}, 'Margens laterais excessivas'),
      description: studioCopy('export_issues.side_margins.description', {}, 'A largura útil está demasiado reduzida para tabelas, assinaturas e blocos posicionáveis.'),
    })
  }

  if (top + bottom >= dimensions.height * 0.55) {
    issues.push({
      tone: 'critical',
      title: studioCopy('export_issues.vertical_margins.title', {}, 'Margens verticais excessivas'),
      description: studioCopy('export_issues.vertical_margins.description', {}, 'O corpo do documento ficará comprimido e pode paginar de forma inesperada.'),
    })
  }

  if (area.width < 120) {
    issues.push({
      tone: 'warning',
      title: studioCopy('export_issues.narrow_area.title', {}, 'Área útil estreita'),
      description: studioCopy('export_issues.narrow_area.description', {}, 'Tabelas analíticas e documentos bilingues podem quebrar linhas em excesso.'),
    })
  }

  if (firstTop < top) {
    issues.push({
      tone: 'warning',
      title: studioCopy('export_issues.first_top_small.title', {}, 'Primeira página com topo menor que o padrão'),
      description: studioCopy('export_issues.first_top_small.description', {}, 'O cabeçalho especial da primeira página pode ficar próximo demais do conteúdo.'),
    })
  }

  if (firstTop > dimensions.height * 0.35) {
    issues.push({
      tone: 'warning',
      title: studioCopy('export_issues.first_top_large.title', {}, 'Primeira página com cabeçalho muito alto'),
      description: studioCopy('export_issues.first_top_large.description', {}, 'Capas e cabeçalhos longos devem ser intencionais para não expulsar conteúdo útil.'),
    })
  }

  return issues
})

const exportSetupStatus = computed(() => {
  if (exportSetupIssues.value.some((issue) => issue.tone === 'critical')) {
    return {
      label: studioCopy('export_status.requires_adjustment.label', {}, 'Requer ajuste'),
      description: studioCopy('export_status.requires_adjustment.description', {}, 'As dimensões podem comprometer o PDF final.'),
      class: 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
    }
  }

  if (exportSetupIssues.value.length) {
    return {
      label: studioCopy('export_status.review_before_export.label', {}, 'Rever antes de exportar'),
      description: studioCopy('export_status.review_before_export.description', {}, 'A página é válida, mas há riscos de paginação.'),
      class: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
    }
  }

  return {
    label: studioCopy('export_status.safe.label', {}, 'Configuração segura'),
    description: studioCopy('export_status.safe.description', {}, 'Página, margens e área útil estão coerentes.'),
    class: 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100',
  }
})

const previewStyleVariables = computed(() => ({
  '--studio-document-font': documentFontFamily.value,
  '--studio-table-header-bg': tableStyleSettings.value.table_header_background,
  '--studio-table-header-color': tableStyleSettings.value.table_header_text_color,
  '--studio-table-border-color': tableStyleSettings.value.table_border_color,
  '--studio-table-font-size': `${tableStyleSettings.value.table_font_size}px`,
  '--studio-table-cell-padding': `${tableStyleSettings.value.table_cell_padding}px`,
  '--studio-table-summary-bg': tableStyleSettings.value.table_summary_background,
  '--studio-table-summary-text-color': tableStyleSettings.value.table_summary_text_color,
  '--studio-table-summary-muted-color': tableStyleSettings.value.table_summary_muted_color,
}))

const previewScopedCss = computed(() => buildReportStudioPreviewCss(props.layoutSchema.styles_css))

const previewHeaderHtml = computed(() => {
  const html = previewMode.value === 'first-page'
    ? props.layoutSchema.first_page_header_html
    : props.layoutSchema.default_header_html

  return interpolatePreviewHtml(html)
})

const previewPageKind = computed(() => {
  if (previewDisplayMode.value === 'all') {
    return previewMode.value
  }

  return currentPreviewPage.value === 1 ? 'first-page' : 'following-page'
})

const previewMeta = computed(() => {
  return previewPageKind.value === 'first-page'
    ? {
        title: studioCopy('preview_meta.first_page.title', {}, 'Primeira página'),
        subtitle: studioCopy('preview_meta.first_page.subtitle', {}, 'Capa, hero e enquadramento inicial.'),
      }
    : {
        title: studioCopy('preview_meta.following_pages.title', {}, 'Páginas seguintes'),
        subtitle: studioCopy('preview_meta.following_pages.subtitle', {}, 'Continuação com cabeçalho padrão e paginação recorrente.'),
    }
})

const previewSurfaceKey = computed(() => {
  return previewMode.value === 'first-page' ? 'first_page_header_html' : 'default_header_html'
})

const previewHeaderBlocks = computed(() => {
  return visibleCanvasBlocks.value.filter((block) => block.surface === previewSurfaceKey.value)
})

const previewContentBlocks = computed(() => {
  return visibleCanvasBlocks.value.filter((block) => block.surface === 'content')
})

const previewFooterBlocks = computed(() => {
  return visibleCanvasBlocks.value.filter((block) => block.surface === 'footer_html')
})

function normalizeContentPageScope(block) {
  return block.page_scope || 'first'
}

function shouldRenderContentBlockOnPage(block, pageNumber) {
  const scope = normalizeContentPageScope(block)

  if (scope === 'all') {
    return true
  }

  if (scope === 'first') {
    return pageNumber === 1
  }

  if (scope === 'following') {
    return pageNumber > 1
  }

  if (scope === 'specific') {
    return Number(block.page_number || 0) === pageNumber
  }

  return pageNumber === 1
}

function previewContentBlocksForPage(pageNumber) {
  return previewContentBlocks.value.filter((block) => shouldRenderContentBlockOnPage(block, pageNumber))
}

const snippetLibraries = {
  analysis: [
    {
      label: 'Capa analítica',
      description: 'Abre o relatório com título, cliente e contexto de emissão.',
      html: `
<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;">
  <div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">{lab_name}</div>
  <h1 style="margin:12px 0 0; font-size:28px;">{report_title}</h1>
  <p style="margin:12px 0 0; font-size:14px; opacity:0.88;">Certificado {certificate_code} · Cliente {customer_name}</p>
</section>`.trim(),
    },
    {
      label: 'Resumo técnico',
      description: 'Bloco com certificado, amostra e data de emissão.',
      html: `
<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;">
  <div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Certificado</div><div style="margin-top:6px; font-weight:700;">{certificate_code}</div></div>
  <div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Código laboratorial</div><div style="margin-top:6px; font-weight:700;">{lab_code}</div></div>
  <div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Emissão</div><div style="margin-top:6px; font-weight:700;">{issue_date}</div></div>
</section>`.trim(),
    },
    { label: 'Tabela de resultados', description: 'Insere a tabela analítica dinâmica.', html: '<section style="margin:20px 0;">{results_table}</section>' },
    { label: 'Incerteza e decisão', description: 'Insere a incerteza expandida e a regra de decisão.', html: '<section style="margin:20px 0;">{uncertainty_statement}<br />{decision_rule}</section>' },
    { label: 'Conclusão', description: 'Bloco para interpretação final ou conclusão técnica.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;">{conclusion}</section>' },
    { label: 'Assinatura técnica', description: 'Insere a assinatura técnica do relatório.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  executive: [
    { label: 'Hero executivo', description: 'Capa com título e narrativa de gestão.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><h1 style="margin:0; font-size:28px;">Resumo Executivo</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">KPIs, risco, capacidade e contexto operacional.</p></section>' },
    { label: 'Resumo KPI', description: 'Quadro de indicadores principais.', html: '<section style="margin:20px 0;"><table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px;">Indicador</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Receita</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 12.500.000</td></tr></table></section>' },
    { label: 'Callout de risco', description: 'Área destacada para risco e observações.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;"><p style="margin:0;">Use este espaço para risco, desvios e decisões executivas.</p></section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  proposal: [
    { label: 'Hero institucional', description: 'Capa editorial com proposta, cliente e subtítulo.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">{{lab_name}}</div><h1 style="margin:12px 0 0; font-size:28px;">Proposta {{proposal_number}}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">Âmbito preparado para {{customer_name}} com enquadramento técnico, comercial e documental.</p></section>' },
    { label: 'Resumo executivo', description: 'Quadro com cliente, local e validade.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Cliente</div><div style="margin-top:6px; font-weight:700;">{{customer_name}}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Local</div><div style="margin-top:6px; font-weight:700;">{{service_location}}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Validade</div><div style="margin-top:6px; font-weight:700;">{{expiry_date}}</div></div></section>' },
    { label: 'Tabela de serviços', description: 'Insere a tabela dinâmica de itens da proposta.', html: '<section style="margin:20px 0;">{items_table}</section>' },
    { label: 'Resumo financeiro', description: 'Insere a tabela de subtotal, desconto, imposto e total.', html: '<section style="margin:20px 0;">{summary_table}</section>' },
    { label: 'Assinaturas', description: 'Linhas de validação para laboratório e cliente.', html: '<section style="margin-top:36px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:24px;"><div><div style="border-top:1px solid #0f172a; padding-top:10px; font-size:11px; color:#475569;">Assinatura do laboratório</div></div><div><div style="border-top:1px solid #0f172a; padding-top:10px; font-size:11px; color:#475569;">Aceitação do cliente</div></div></section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  export_certificate: [
    { label: 'Capa de exportação', description: 'Abertura do certificado com exportador e contexto logístico.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">{origin_country} → {destination_country}</div><h1 style="margin:12px 0 0; font-size:28px;">Certificado de Exportação {certificate_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{exporter_name} · {transport_type}</p></section>' },
    { label: 'Resumo logístico', description: 'Mostra origem, destino e local de expedição.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Origem</div><div style="margin-top:6px; font-weight:700;">{origin_city}, {origin_country}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Destino</div><div style="margin-top:6px; font-weight:700;">{destination_city}, {destination_country}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Expedição</div><div style="margin-top:6px; font-weight:700;">{expedition_location}</div></div></section>' },
    { label: 'Tabela de produtos', description: 'Insere os produtos e quantidades exportadas.', html: '<section style="margin:20px 0;">{products_table}</section>' },
    { label: 'Observações de expedição', description: 'Callout para remarks e conformidade logística.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;">{remarks}</section>' },
    { label: 'Assinatura técnica', description: 'Insere o bloco de validação final.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  import_certificate: [
    { label: 'Capa de importação', description: 'Abertura do certificado com importador e contexto logístico.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">Destino {destination_country}</div><h1 style="margin:12px 0 0; font-size:28px;">Certificado de Importação {certificate_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{importer_name} · {transport_type}</p></section>' },
    { label: 'Resumo logístico', description: 'Mostra portos, destino e operador logístico.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Importador</div><div style="margin-top:6px; font-weight:700;">{importer_name}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Porto de saída</div><div style="margin-top:6px; font-weight:700;">{port_exit}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Porto de entrada</div><div style="margin-top:6px; font-weight:700;">{port_entry}</div></div></section>' },
    { label: 'Tabela de lotes', description: 'Insere os produtos, lotes, validade e quantidade.', html: '<section style="margin:20px 0;">{items_table}</section>' },
    { label: 'Observações técnicas', description: 'Callout para remarks e enquadramento da importação.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;">{remarks}</section>' },
    { label: 'Assinatura técnica', description: 'Insere o bloco de validação final.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  quote: [
    { label: 'Capa comercial', description: 'Abertura editorial da proforma com cliente e validade.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">Proposta comercial</div><h1 style="margin:12px 0 0; font-size:28px;">Proforma {quote_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{customer_name} · Validade até {expiry_date}</p></section>' },
    { label: 'Resumo executivo', description: 'Mostra cliente, local e validade.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Cliente</div><div style="margin-top:6px; font-weight:700;">{customer_name}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Local</div><div style="margin-top:6px; font-weight:700;">{service_location}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Validade</div><div style="margin-top:6px; font-weight:700;">{expiry_date}</div></div></section>' },
    { label: 'Tabela de itens', description: 'Insere os itens e valores da proforma.', html: '<section style="margin:20px 0;">{items_table}</section>' },
    { label: 'Resumo financeiro', description: 'Subtotal, IVA e total.', html: '<section style="margin:20px 0;">{summary_table}</section>' },
    { label: 'Observações', description: 'Callout com condições comerciais.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;">{observations}</section>' },
    { label: 'Assinatura comercial', description: 'Validação final da emissão.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  invoice: [
    { label: 'Capa fiscal', description: 'Abertura editorial da factura com cliente e vencimento.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">Documento fiscal</div><h1 style="margin:12px 0 0; font-size:28px;">Factura {document_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{customer_name} · Vencimento {due_date}</p></section>' },
    { label: 'Resumo comercial', description: 'Mostra cliente, local e vencimento.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Cliente</div><div style="margin-top:6px; font-weight:700;">{customer_name}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Local</div><div style="margin-top:6px; font-weight:700;">{service_location}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Vencimento</div><div style="margin-top:6px; font-weight:700;">{due_date}</div></div></section>' },
    { label: 'Tabela de itens', description: 'Itens facturados.', html: '<section style="margin:20px 0;">{items_table}</section>' },
    { label: 'Resumo financeiro', description: 'Subtotal, IVA e total.', html: '<section style="margin:20px 0;">{summary_table}</section>' },
    { label: 'Observações', description: 'Callout com condições fiscais/comerciais.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;">{observations}</section>' },
    { label: 'Assinatura financeira', description: 'Validação da emissão.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  receipt: [
    { label: 'Capa de recibo', description: 'Abertura editorial do recibo.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">Comprovativo de recebimento</div><h1 style="margin:12px 0 0; font-size:28px;">Recibo {document_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{customer_name} · {payment_type}</p></section>' },
    { label: 'Resumo de recebimento', description: 'Mostra cliente, local e forma de pagamento.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Cliente</div><div style="margin-top:6px; font-weight:700;">{customer_name}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Local</div><div style="margin-top:6px; font-weight:700;">{service_location}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Pagamento</div><div style="margin-top:6px; font-weight:700;">{payment_type}</div></div></section>' },
    { label: 'Tabela de liquidações', description: 'Facturas e valores pagos.', html: '<section style="margin:20px 0;">{items_table}</section>' },
    { label: 'Resumo do recebido', description: 'Total recebido.', html: '<section style="margin:20px 0;">{summary_table}</section>' },
    { label: 'Observações', description: 'Notas de tesouraria.', html: '<section style="border-left:4px solid #d9b05f; background:#fffaf0; padding:18px 20px; border-radius:18px; margin:20px 0;">{observations}</section>' },
    { label: 'Assinatura de tesouraria', description: 'Confirmação final do recebimento.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
  credit_note: [
    { label: 'Capa de rectificação', description: 'Abertura editorial da nota de crédito.', html: '<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#7f1d1d,#dc2626); color:#ffffff; margin-bottom:24px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">Rectificação comercial</div><h1 style="margin:12px 0 0; font-size:28px;">Nota de Crédito {document_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{customer_name} · {reason_label}</p></section>' },
    { label: 'Resumo da rectificação', description: 'Cliente, local e motivo.', html: '<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;"><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Cliente</div><div style="margin-top:6px; font-weight:700;">{customer_name}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Local</div><div style="margin-top:6px; font-weight:700;">{service_location}</div></div><div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;"><div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Motivo</div><div style="margin-top:6px; font-weight:700;">{reason_label}</div></div></section>' },
    { label: 'Tabela de rectificação', description: 'Itens rectificados.', html: '<section style="margin:20px 0;">{items_table}</section>' },
    { label: 'Resumo da nota', description: 'Total da rectificação.', html: '<section style="margin:20px 0;">{summary_table}</section>' },
    { label: 'Observações', description: 'Notas de rectificação.', html: '<section style="border-left:4px solid #dc2626; background:#fef2f2; padding:18px 20px; border-radius:18px; margin:20px 0;">{observations}</section>' },
    { label: 'Assinatura financeira', description: 'Validação da nota.', html: '<section style="margin-top:24px;">{signature_block}</section>' },
    { label: 'Quebra de página', description: 'Inicia explicitamente uma nova página no preview e no PDF.', html: '<pagebreak />' },
  ],
}

const snippetLibrary = computed(() => {
  return snippetLibraries[props.form.studio_type] ?? snippetLibraries.analysis
})

function appendSnippet(html) {
  const binding = surfaceBindings.value[snippetTarget.value]

  if (!binding) {
    return
  }

  binding.value = `${binding.value || ''}\n\n${html}`.trim()
}

function createCanvasBlock(overrides = {}) {
  return {
    id: `block-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    title: 'Novo bloco',
    block_kind: 'rich_text',
    surface: snippetTarget.value === 'styles_css' ? 'content' : snippetTarget.value,
    asset_id: '',
    asset_label: '',
    asset_source: '',
    asset_mime_type: '',
    asset_size: null,
    content_html: '<p>Novo conteúdo editorial</p>',
    image_url: '',
    image_alt: '',
    image_fit: 'contain',
    image_position: 'center center',
    qr_content: '{{document_code}}',
    qr_label: '',
    qr_foreground_color: '#0f172a',
    qr_background_color: '#ffffff',
    qr_error_correction: 'low',
    qr_margin: 8,
    chart_title: '',
    chart_caption: '',
    chart_svg: '',
    chart_image_url: '',
    chart_type: 'bar',
    chart_labels: '',
    chart_values: '',
    chart_colors: defaultChartPalette.join(', '),
    chart_primary_color: '#143d37',
    chart_background_color: '#f8f4ea',
    chart_show_values: true,
    x: 0,
    y: 0,
    width: 100,
    min_height: 0,
    z_index: 10,
    padding: 12,
    background_color: '',
    background_image: '',
    background_image_fit: 'cover',
    background_image_position: 'center center',
    overlay_color: '',
    overlay_opacity: 0.35,
    text_color: '',
    border_width: 0,
    border_color: '',
    border_radius: 16,
    opacity: 1,
    rotation_deg: 0,
    shadow_preset: 'soft',
    text_align: 'left',
    font_size: null,
    line_height: null,
    signature_label: '',
    signature_name: '',
    signature_title: '',
    signature_image: '',
    signature_image_fit: 'contain',
    signature_image_position: 'center center',
    signature_image_width: 180,
    signature_image_height: 72,
    signature_line_style: 'solid',
    signature_align: 'left',
    signature_show_date: false,
    signature_date_label: '',
    page_scope: snippetTarget.value === 'content' ? 'first' : 'all',
    page_number: null,
    is_locked: false,
    is_hidden: false,
    ...overrides,
  }
}

function addCanvasBlock(overrides = {}) {
  const block = createCanvasBlock(overrides)
  canvasBlocks.value.push(block)
  selectedCanvasBlockId.value = block.id

  return block
}

function currentCanvasSurface() {
  return snippetTarget.value === 'styles_css' ? 'content' : snippetTarget.value
}

function nextCanvasZIndex(surface = currentCanvasSurface(), minimum = 10) {
  const zIndexes = canvasBlocks.value
    .filter((block) => block.surface === surface)
    .map((block) => Number(block.z_index || 0))

  if (!zIndexes.length) {
    return minimum
  }

  return clamp(Math.max(...zIndexes) + 1, minimum, 999)
}

function canvasBlockAssetMetadata(asset) {
  return {
    asset_id: asset.id || '',
    asset_label: asset.label || '',
    asset_source: asset.source || '',
    asset_mime_type: asset.mime_type || '',
    asset_size: asset.size || null,
  }
}

function mediaAssetDocumentUrl(asset) {
  return asset?.pdf_url || asset?.url || ''
}

function canvasBlockKindForAsset(asset, preferredKind = null) {
  if (preferredKind && blockKindOptions.some((option) => option.value === preferredKind)) {
    return preferredKind
  }

  const descriptor = `${asset.kind || ''} ${asset.source || ''} ${asset.label || ''}`.toLowerCase()

  if (descriptor.includes('assinatura') || descriptor.includes('signature')) {
    return 'signature'
  }

  if (descriptor.includes('carimbo') || descriptor.includes('selo') || descriptor.includes('stamp')) {
    return 'stamp'
  }

  if (descriptor.includes('chart') || descriptor.includes('gráfico') || descriptor.includes('grafico')) {
    return 'chart_snapshot'
  }

  return 'image'
}

function addMediaCanvasBlockFromAsset(asset, options = {}) {
  const assetUrl = mediaAssetDocumentUrl(asset)

  if (!assetUrl) {
    return null
  }

  const surface = options.surface || currentCanvasSurface()
  const blockKind = canvasBlockKindForAsset(asset, options.blockKind)
  const pageNumber = currentPreviewPage.value
  const pageScope = surface === 'content'
    ? (pageNumber > 1 ? 'specific' : 'first')
    : 'all'
  const shared = {
    ...canvasBlockAssetMetadata(asset),
    surface,
    page_scope: pageScope,
    page_number: pageScope === 'specific' ? pageNumber : null,
    z_index: nextCanvasZIndex(surface, blockKind === 'stamp' ? 40 : 10),
  }

  if (blockKind === 'signature') {
    return addCanvasBlock({
      ...shared,
      title: `Assinatura · ${asset.label || 'media'}`,
      block_kind: 'signature',
      content_html: '',
      signature_label: 'Assinatura autorizada',
      signature_name: asset.label || '{{lab_name}}',
      signature_title: asset.source || 'Documento',
      signature_image: assetUrl,
      x: 52,
      y: surface === 'content' ? 64 : 18,
      width: 40,
      min_height: 128,
      padding: 16,
      border_radius: 24,
      background_color: 'rgba(255,255,255,0.92)',
      border_width: 1,
      border_color: 'rgba(148,163,184,0.28)',
      shadow_preset: 'soft',
    })
  }

  if (blockKind === 'stamp') {
    return addCanvasBlock({
      ...shared,
      title: `Carimbo · ${asset.label || 'media'}`,
      block_kind: 'stamp',
      content_html: '',
      image_url: assetUrl,
      image_alt: asset.label || 'Carimbo',
      x: 70,
      y: 8,
      width: 24,
      min_height: 128,
      padding: 0,
      border_radius: 999,
      background_color: 'transparent',
      opacity: 0.9,
      rotation_deg: -8,
      shadow_preset: 'stamp',
      image_fit: 'contain',
      image_position: 'center center',
    })
  }

  if (blockKind === 'chart_snapshot') {
    return addCanvasBlock({
      ...shared,
      title: `Gráfico · ${asset.label || 'media'}`,
      block_kind: 'chart_snapshot',
      content_html: '',
      chart_title: asset.label || 'Gráfico do relatório',
      chart_caption: asset.source || 'Imagem carregada no studio.',
      chart_image_url: assetUrl,
      x: 8,
      y: 18,
      width: 60,
      min_height: 220,
      padding: 16,
      background_color: 'rgba(255,255,255,0.96)',
      border_width: 1,
      border_color: 'rgba(148,163,184,0.28)',
      border_radius: 24,
      shadow_preset: 'elevated',
    })
  }

  return addCanvasBlock({
    ...shared,
    title: `Imagem · ${asset.label || 'media'}`,
    block_kind: 'image',
    content_html: '',
    image_url: assetUrl,
    image_alt: asset.label || 'Imagem do documento',
    image_fit: 'contain',
    image_position: 'center center',
    x: 8,
    y: 14,
    width: 42,
    min_height: 200,
    padding: 0,
    border_radius: 22,
    background_color: 'transparent',
    shadow_preset: 'none',
  })
}

function addSnippetAsCanvasBlock(snippet) {
  addCanvasBlock({
    title: snippet.label,
    content_html: snippet.html,
  })
}

function addTextCanvasBlock() {
  addCanvasBlock({
    title: 'Texto editorial',
    block_kind: 'rich_text',
    content_html: '<p>Escreva aqui o conteúdo editorial do documento.</p>',
    width: 46,
    min_height: 120,
    padding: 18,
    border_radius: 24,
    background_color: 'rgba(255,255,255,0.92)',
    border_width: 1,
    border_color: 'rgba(148,163,184,0.28)',
    shadow_preset: 'soft',
  })
}

function addCalloutCanvasBlock() {
  addCanvasBlock({
    title: 'Destaque técnico',
    block_kind: 'rich_text',
    content_html: '<p><strong>Destaque técnico</strong></p><p>Use este bloco para observações, decisão, risco ou notas de auditoria.</p>',
    x: 8,
    y: 18,
    width: 58,
    min_height: 150,
    padding: 22,
    border_radius: 28,
    background_color: '#f7f1e7',
    border_width: 1,
    border_color: 'rgba(217,176,95,0.55)',
    shadow_preset: 'soft',
  })
}

function addSignatureCanvasBlock(type = 'lab') {
  const defaults = {
    lab: {
      title: 'Assinatura do laboratório',
      signature_label: 'Assinatura do laboratório',
      signature_name: '{{lab_name}}',
      signature_title: 'Direção técnica',
    },
    client: {
      title: 'Aceitação do cliente',
      signature_label: 'Aceitação do cliente',
      signature_name: '{{customer_name}}',
      signature_title: 'Cliente / representante',
    },
    approval: {
      title: 'Validação formal',
      signature_label: 'Validação formal',
      signature_name: '{{lab_name}}',
      signature_title: 'Aprovação e compromisso',
    },
  }

  const preset = defaults[type] || defaults.lab

  addCanvasBlock({
    block_kind: 'signature',
    content_html: '',
    width: 42,
    min_height: 120,
    padding: 18,
    border_radius: 24,
    background_color: 'rgba(255,255,255,0.92)',
    border_width: 1,
    border_color: 'rgba(148,163,184,0.28)',
    text_align: 'left',
    shadow_preset: 'soft',
    signature_line_style: 'solid',
    signature_align: type === 'client' ? 'right' : 'left',
    ...preset,
  })
}

function addImageCanvasBlock() {
  addCanvasBlock({
    title: 'Imagem livre',
    block_kind: 'image',
    content_html: '',
    image_url: mediaAssetUrl.value || '',
    width: 36,
    min_height: 160,
    padding: 0,
    background_color: 'transparent',
    border_radius: 18,
    shadow_preset: 'none',
    image_fit: 'contain',
    image_position: 'center center',
  })
}

function addChartSnapshotCanvasBlock() {
  addCanvasBlock({
    title: 'Gráfico do relatório',
    block_kind: 'chart_snapshot',
    content_html: '',
    chart_title: 'Gráfico do relatório',
    chart_caption: 'Indicador visual gerado pelo estúdio ou substituído por captura exportada.',
    chart_image_url: mediaAssetUrl.value || '',
    chart_type: 'bar',
    chart_labels: ['Recepção', 'Validação', 'Emissão'],
    chart_values: [18, 12, 9],
    chart_colors: defaultChartPalette,
    chart_primary_color: '#143d37',
    chart_background_color: '#f8f4ea',
    chart_show_values: true,
    width: 60,
    min_height: 220,
    padding: 16,
    background_color: 'rgba(255,255,255,0.96)',
    border_width: 1,
    border_color: 'rgba(148,163,184,0.28)',
    border_radius: 24,
    shadow_preset: 'elevated',
  })
}

function addStampCanvasBlock() {
  addCanvasBlock({
    title: 'Carimbo sobreposto',
    block_kind: 'stamp',
    content_html: '',
    width: 24,
    min_height: 120,
    padding: 0,
    z_index: 40,
    background_color: 'transparent',
    border_radius: 999,
    opacity: 0.86,
    rotation_deg: -8,
    shadow_preset: 'stamp',
    image_fit: 'contain',
    image_position: 'center center',
  })
}

function addQrCanvasBlock() {
  addCanvasBlock({
    title: 'QR de validação',
    block_kind: 'qr_code',
    content_html: '',
    width: 18,
    min_height: 130,
    padding: 10,
    z_index: 35,
    background_color: 'rgba(255,255,255,0.92)',
    border_width: 1,
    border_color: 'rgba(148,163,184,0.28)',
    border_radius: 20,
    shadow_preset: 'soft',
    qr_content: '{{document_code}} · {{customer_name}} · {{issue_date}}',
    qr_label: 'Verificar autenticidade',
    qr_foreground_color: '#0f172a',
    qr_background_color: '#ffffff',
    qr_error_correction: 'low',
    qr_margin: 8,
  })
}

function addSavedSignatureCanvasBlock(asset) {
  addSignatureCanvasBlock('approval')

  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value.title = `Assinatura guardada · ${asset.label}`
  selectedCanvasBlock.value.signature_label = 'Assinatura autorizada'
  selectedCanvasBlock.value.signature_name = asset.label || '{{lab_name}}'
  selectedCanvasBlock.value.signature_image = mediaAssetDocumentUrl(asset)
  selectedCanvasBlock.value.signature_image_fit = selectedCanvasBlock.value.signature_image_fit || 'contain'
  selectedCanvasBlock.value.signature_image_position = selectedCanvasBlock.value.signature_image_position || 'center center'
  selectedCanvasBlock.value.signature_image_width = selectedCanvasBlock.value.signature_image_width || 180
  selectedCanvasBlock.value.signature_image_height = selectedCanvasBlock.value.signature_image_height || 72
}

function applyAssetToSelectedBlock(assetUrl, field = 'image_url') {
  if (!selectedCanvasBlock.value || !assetUrl) {
    return
  }

  selectedCanvasBlock.value[field] = assetUrl
}

function mediaFieldLabel(field) {
  return {
    background_image: 'fundo do bloco',
    chart_image_url: 'imagem do gráfico',
    image_url: 'imagem principal',
    signature_image: 'assinatura',
  }[field] || 'media'
}

function imagePositionCoordinates(position = 'center center') {
  const value = String(position || 'center center').trim().toLowerCase()
  const percentages = [...value.matchAll(/(-?\d+(?:\.\d+)?)%/g)].map((match) => Number(match[1]))

  if (percentages.length >= 2) {
    return {
      x: clamp(Math.round(percentages[0]), 0, 100),
      y: clamp(Math.round(percentages[1]), 0, 100),
    }
  }

  const tokens = value.split(/\s+/).filter(Boolean)
  let x = 50
  let y = 50

  tokens.forEach((token) => {
    if (token === 'left') {
      x = 0
    }

    if (token === 'right') {
      x = 100
    }

    if (token === 'top') {
      y = 0
    }

    if (token === 'bottom') {
      y = 100
    }
  })

  return {
    x,
    y,
  }
}

function imageObjectPosition(block) {
  return safeCssPositionValue(block?.image_position, 'center center')
}

function mediaObjectFit(value = 'contain') {
  return {
    cover: 'cover',
    auto: 'scale-down',
  }[value] || 'contain'
}

function imageObjectFit(block) {
  return mediaObjectFit(block?.image_fit || 'contain')
}

function safeCssColorValue(value, fallback = '') {
  const color = String(value || '').trim()

  if (!color) {
    return fallback
  }

  if (/^#[0-9a-f]{3,8}$/i.test(color)) {
    return color
  }

  if (/^(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+-]+\)$/i.test(color)) {
    return color
  }

  if (/^[a-z][a-z0-9-]{2,32}$/i.test(color)) {
    return color
  }

  return fallback
}

function safeCssImageFitValue(value, fallback = 'cover') {
  const fit = String(value || '').trim()

  return ['cover', 'contain', 'auto'].includes(fit) ? fit : fallback
}

function safeCssRepeatValue(value, fallback = 'no-repeat') {
  const repeat = String(value || '').trim()

  return ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'].includes(repeat) ? repeat : fallback
}

function safeCssPositionValue(value, fallback = 'center center') {
  const position = String(value || '').trim()

  if (!position) {
    return fallback
  }

  const positionToken = '(?:left|right|top|bottom|center|(?:100|[1-9]?\\d)(?:\\.\\d{1,2})?%)'
  const positionPattern = new RegExp(`^${positionToken}(?:\\s+${positionToken})?$`, 'i')

  return positionPattern.test(position) ? position : fallback
}

function imageFocalPointStyle(block) {
  const coordinates = imagePositionCoordinates(block?.image_position)

  return {
    left: `${coordinates.x}%`,
    top: `${coordinates.y}%`,
  }
}

function setImageFocalCoordinate(block, axis, value) {
  if (!block) {
    return
  }

  const coordinates = imagePositionCoordinates(block.image_position)
  coordinates[axis] = clamp(Math.round(Number(value) || 0), 0, 100)
  block.image_position = `${coordinates.x}% ${coordinates.y}%`
}

function setSelectedImageFocalCoordinate(axis, value) {
  setImageFocalCoordinate(selectedCanvasBlock.value, axis, value)
}

function setSelectedImageFocalPreset(x, y) {
  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value.image_position = `${x}% ${y}%`
}

function documentBackgroundFocalPointStyle() {
  const coordinates = imagePositionCoordinates(props.layoutSchema.background_position)

  return {
    left: `${coordinates.x}%`,
    top: `${coordinates.y}%`,
  }
}

function setDocumentBackgroundFocalCoordinate(axis, value) {
  const coordinates = imagePositionCoordinates(props.layoutSchema.background_position)
  coordinates[axis] = clamp(Math.round(Number(value) || 0), 0, 100)
  props.layoutSchema.background_position = `${coordinates.x}% ${coordinates.y}%`
}

function applyFocalTargetCoordinates(target, x, y) {
  if (target?.type === 'document-background') {
    props.layoutSchema.background_position = `${x}% ${y}%`

    return
  }

  if (target?.block) {
    target.block.image_position = `${x}% ${y}%`
  }
}

function updateFocalTargetFromPointer(target, rect, event) {
  if (!rect?.width || !rect?.height) {
    return
  }

  const x = clamp(Math.round(((event.clientX - rect.left) / rect.width) * 100), 0, 100)
  const y = clamp(Math.round(((event.clientY - rect.top) / rect.height) * 100), 0, 100)

  applyFocalTargetCoordinates(target, x, y)
}

function updateFocalDrag(event) {
  if (!focalDragState.value) {
    return
  }

  updateFocalTargetFromPointer(
    focalDragState.value.target,
    focalDragState.value.rect,
    event
  )
}

function stopFocalDrag() {
  focalDragState.value = null
  window.removeEventListener('pointermove', updateFocalDrag)
  window.removeEventListener('pointerup', stopFocalDrag)
}

function startFocalDrag(target, event) {
  event.preventDefault()
  event.stopPropagation()

  focalDragState.value = {
    target,
    rect: event.currentTarget.getBoundingClientRect(),
  }

  updateFocalDrag(event)
  window.addEventListener('pointermove', updateFocalDrag)
  window.addEventListener('pointerup', stopFocalDrag, { once: true })
}

function startImageFocalDrag(block, event) {
  startFocalDrag({ type: 'block-image', block }, event)
}

function startDocumentBackgroundFocalDrag(event) {
  startFocalDrag({ type: 'document-background' }, event)
}

function selectedBlockDefaultMediaField(block = selectedCanvasBlock.value) {
  if (!block) {
    return 'image_url'
  }

  if (block.block_kind === 'signature') {
    return 'signature_image'
  }

  if (block.block_kind === 'chart_snapshot') {
    return 'chart_image_url'
  }

  return 'image_url'
}

function openSelectedBlockMediaPicker(field = null) {
  if (!selectedCanvasBlock.value) {
    return
  }

  openMediaPicker('selected-block', field || selectedBlockDefaultMediaField())
}

function applyAssetToCanvasTarget(asset, target = 'document-background') {
  const assetUrl = mediaAssetDocumentUrl(asset)

  if (!assetUrl) {
    return
  }

  if (target === 'document-background') {
    applyAssetToDocumentBackground(assetUrl)

    return
  }

  if (target === 'new-canvas-block' || !selectedCanvasBlock.value) {
    addMediaCanvasBlockFromAsset(asset)

    return
  }

  if (target === 'selected-block-background') {
    applyAssetToSelectedBlock(assetUrl, 'background_image')

    return
  }

  applyAssetToSelectedBlock(assetUrl, selectedBlockDefaultMediaField())
}

function openMediaPicker(scope = 'selected-block', field = 'image_url', options = {}) {
  mediaPickerTarget.value = { scope, field, ...options }
  mediaPickerSearch.value = ''
  mediaPickerKind.value = 'all'
  mediaPickerManualUrl.value = ''
  mediaPickerUploadError.value = ''
  mediaPickerUploadProgress.value = 0
  mediaPickerOpen.value = true
}

function applyMediaPickerAsset(asset) {
  const assetUrl = mediaAssetDocumentUrl(asset)

  if (!assetUrl) {
    return
  }

  if (mediaPickerTarget.value.scope === 'asset-url') {
    mediaAssetUrl.value = assetUrl
  }

  if (mediaPickerTarget.value.scope === 'document-background') {
    applyAssetToDocumentBackground(assetUrl)
  }

  if (mediaPickerTarget.value.scope === 'new-canvas-block') {
    addMediaCanvasBlockFromAsset(asset, {
      blockKind: mediaPickerTarget.value.blockKind,
    })
  }

  if (mediaPickerTarget.value.scope === 'selected-block') {
    applyAssetToSelectedBlock(assetUrl, mediaPickerTarget.value.field)
  }

  mediaPickerManualUrl.value = ''
  mediaPickerOpen.value = false
}

function applyMediaPickerManualUrl() {
  const assetUrl = mediaPickerManualUrl.value.trim()

  if (!assetUrl) {
    return
  }

  applyMediaPickerAsset({
    url: assetUrl,
    label: studioCopy('media_picker.manual_url_label'),
    source: studioCopy('media_picker.manual_source'),
  })
}

function applyAssetToDocumentBackground(assetUrl) {
  if (!assetUrl) {
    return
  }

  props.layoutSchema.background_image_path = assetUrl
  props.layoutSchema.background_size = props.layoutSchema.background_size || 'cover'
  props.layoutSchema.background_position = props.layoutSchema.background_position || 'center center'
  props.layoutSchema.background_repeat = props.layoutSchema.background_repeat || 'no-repeat'
  mediaAssetUrl.value = assetUrl
}

function pickBackgroundUpload() {
  backgroundUploadInput.value?.click()
}

function handleBackgroundUploadInput(event) {
  const [file] = Array.from(event.target.files || [])

  if (event.target) {
    event.target.value = ''
  }

  uploadBackgroundAsset(file)
}

function handleBackgroundDrop(event) {
  backgroundUploadDragging.value = false
  const [file] = Array.from(event.dataTransfer?.files || [])

  uploadBackgroundAsset(file)
}

async function uploadBackgroundAsset(file) {
  await uploadStudioAsset(file, {
    status: 'background',
    target: { scope: 'document-background', field: 'background_image' },
    onSuccess(asset) {
      applyAssetToDocumentBackground(mediaAssetDocumentUrl(asset))
    },
  })
}

function pickMediaPickerUpload() {
  mediaPickerUploadInput.value?.click()
}

function handleMediaPickerUploadInput(event) {
  const [file] = Array.from(event.target.files || [])

  if (event.target) {
    event.target.value = ''
  }

  uploadMediaPickerAsset(file)
}

function handleMediaPickerDrop(event) {
  mediaPickerUploadDragging.value = false
  const [file] = Array.from(event.dataTransfer?.files || [])

  uploadMediaPickerAsset(file)
}

async function uploadMediaPickerAsset(file) {
  await uploadStudioAsset(file, {
    status: 'media-picker',
    target: { ...mediaPickerTarget.value },
    onSuccess(asset) {
      applyMediaPickerAsset(asset)
    },
  })
}

function uploadStatusRefs(status = 'background') {
  if (status === 'media-picker') {
    return {
      busy: mediaPickerUploadBusy,
      progress: mediaPickerUploadProgress,
      error: mediaPickerUploadError,
    }
  }

  return {
    busy: backgroundUploadBusy,
    progress: backgroundUploadProgress,
    error: backgroundUploadError,
  }
}

function uploadedAssetKind(target = mediaPickerTarget.value) {
  return uploadedStudioAssetKind(target)
}

function uploadedAssetSource(kind) {
  return {
    uploaded_asset: studioCopy('media_picker.sources.uploaded_asset'),
    uploaded_background: studioCopy('media_picker.sources.uploaded_background'),
    uploaded_chart: studioCopy('media_picker.sources.uploaded_chart'),
    uploaded_stamp: studioCopy('media_picker.sources.uploaded_stamp'),
    uploaded_signature: studioCopy('media_picker.sources.uploaded_signature'),
    uploaded_image: studioCopy('media_picker.sources.uploaded_image'),
  }[kind] || studioCopy('media_picker.sources.default')
}

function normalizeUploadedAsset(data, file, target = mediaPickerTarget.value) {
  const backendAsset = data?.asset || {}
  const kind = uploadedAssetKind(target)

  return {
    id: backendAsset.id || `uploaded-${data?.id || Date.now()}`,
    label: backendAsset.label || file.name,
    kind,
    source: backendAsset.source || uploadedAssetSource(kind),
    author: backendAsset.author || studioCopy('media_picker.current_session'),
    mime_type: backendAsset.mime_type || file.type,
    file_type: backendAsset.file_type || (String(file.type || '').startsWith('image/') ? 'image' : 'other'),
    size: backendAsset.size || file.size,
    url: backendAsset.url || data?.preview_url || '',
    pdf_url: backendAsset.pdf_url || backendAsset.url || data?.preview_url || '',
  }
}

function resolveStudioRoute(name, fallback) {
  if (typeof window !== 'undefined' && typeof window.route === 'function') {
    return window.route(name)
  }

  if (typeof route === 'function') {
    return route(name)
  }

  return fallback
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

async function uploadStudioAsset(file, options = {}) {
  const uploadStatus = uploadStatusRefs(options.status)
  uploadStatus.error.value = ''

  if (!file) {
    return
  }

  if (!allowedBackgroundMimeTypes.includes(file.type)) {
    uploadStatus.error.value = studioCopy('media_picker.allowed_types_error')

    return
  }

  const formData = new FormData()
  formData.append('studio_asset_context', 'report_studio')
  formData.append('studio_asset_kind', uploadedAssetKind(options.target))
  formData.append('file', file)
  uploadStatus.busy.value = true
  uploadStatus.progress.value = 0

  try {
    const { data } = await axios.post(resolveStudioRoute('media.store', '/media'), formData, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
      },
      onUploadProgress(progressEvent) {
        if (!progressEvent.total) {
          return
        }

        uploadStatus.progress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      },
    })

    const uploadedAsset = normalizeUploadedAsset(data, file, options.target)

    localAssetLibrary.value = [
      uploadedAsset,
      ...localAssetLibrary.value.filter((asset) => asset.id !== uploadedAsset.id),
    ]
    options.onSuccess?.(uploadedAsset)
    mediaPickerManualUrl.value = ''
    uploadStatus.progress.value = 100
  } catch (error) {
    uploadStatus.error.value = error?.response?.data?.message || studioCopy('media_picker.upload_error')
  } finally {
    uploadStatus.busy.value = false
  }
}

function removeCanvasBlock(blockId) {
  props.layoutSchema.canvas_blocks = canvasBlocks.value.filter((block) => block.id !== blockId)

  if (selectedCanvasBlockId.value === blockId) {
    selectedCanvasBlockId.value = props.layoutSchema.canvas_blocks[0]?.id ?? null
  }
}

function duplicateCanvasBlock(block) {
  addCanvasBlock({
    ...block,
    title: `${block.title} (cópia)`,
    x: Number(block.x || 0) + 2,
    y: Number(block.y || 0) + 2,
    is_locked: false,
    is_hidden: false,
  })
}

function canvasBlockShadow(block) {
  return canvasShadowPresets.find((preset) => preset.value === (block.shadow_preset || 'soft'))?.css || canvasShadowPresets[1].css
}

function canvasBlockStyle(block) {
  const blockBackgroundColor = safeCssColorValue(block.background_color, 'rgba(255,255,255,0.88)')
  const blockTextColor = safeCssColorValue(block.text_color, '')
  const blockBorderColor = safeCssColorValue(block.border_color, 'rgba(148,163,184,0.4)')
  const blockBackgroundImage = safePreviewCssUrl(interpolatePreviewHtml(block.background_image || ''))

  return {
    position: 'absolute',
    left: `${Number(block.x || 0)}%`,
    top: `${Number(block.y || 0)}%`,
    width: `${Number(block.width || 100)}%`,
    minHeight: `${Number(block.min_height || 0)}px`,
    zIndex: Number(block.z_index || 10),
    padding: `${Number(block.padding || 0)}px`,
    borderRadius: `${Number(block.border_radius || 0)}px`,
    background: blockBackgroundColor,
    backgroundImage: blockBackgroundImage || undefined,
    backgroundSize: blockBackgroundImage ? safeCssImageFitValue(block.background_image_fit, 'cover') : undefined,
    backgroundPosition: blockBackgroundImage ? safeCssPositionValue(block.background_image_position, 'center center') : undefined,
    backgroundRepeat: blockBackgroundImage ? 'no-repeat' : undefined,
    border: Number(block.border_width || 0) > 0
      ? `${Number(block.border_width || 0)}px solid ${blockBorderColor}`
      : 'none',
    opacity: clamp(Number(block.opacity ?? 1), 0.05, 1),
    color: blockTextColor || undefined,
    textAlign: block.text_align || 'left',
    fontSize: block.font_size ? `${Number(block.font_size)}px` : undefined,
    lineHeight: block.line_height ? String(block.line_height) : undefined,
    transform: `rotate(${clamp(Number(block.rotation_deg || 0), -45, 45)}deg)`,
    transformOrigin: 'center center',
    boxShadow: canvasBlockShadow(block),
  }
}

function canvasBlockOverlayStyle(block) {
  const overlayColor = safeCssColorValue(block.overlay_color, '')

  if (!overlayColor) {
    return null
  }

  return {
    position: 'absolute',
    inset: '0',
    borderRadius: `${Number(block.border_radius || 0)}px`,
    background: overlayColor,
    opacity: clamp(Number(block.overlay_opacity ?? 0.35), 0, 1),
    pointerEvents: 'none',
  }
}

function qrCodeErrorCorrectionLevel(value) {
  return {
    high: 'H',
    low: 'L',
    medium: 'M',
    quartile: 'Q',
  }[value] || 'L'
}

function qrCodePreviewDataUri(block) {
  const qrContent = interpolatePreviewHtml(block.qr_content || block.content_html || '{document_code}').trim()

  if (!qrContent) {
    return ''
  }

  try {
    const qrCode = createQrCode(qrContent, {
      errorCorrectionLevel: qrCodeErrorCorrectionLevel(block.qr_error_correction),
    })
    const quietZone = clamp(Math.round(Number(block.qr_margin ?? 8)), 0, 32)
    const moduleCount = qrCode.modules.size
    const viewBoxSize = moduleCount + (quietZone * 2)
    const foregroundColor = colorInputValue(block.qr_foreground_color, '#0f172a')
    const backgroundColor = colorInputValue(block.qr_background_color, '#ffffff')
    const modulePath = []

    for (let row = 0; row < moduleCount; row += 1) {
      for (let column = 0; column < moduleCount; column += 1) {
        if (qrCode.modules.get(row, column)) {
          modulePath.push(`M${column + quietZone} ${row + quietZone}h1v1h-1z`)
        }
      }
    }

    const svg = [
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${viewBoxSize} ${viewBoxSize}" shape-rendering="crispEdges">`,
      `<rect width="100%" height="100%" fill="${backgroundColor}"/>`,
      `<path d="${modulePath.join('')}" fill="${foregroundColor}"/>`,
      '</svg>',
    ].join('')

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`
  } catch {
    return ''
  }
}

function chartListValue(value) {
  return normalizeStudioChartList(value)
}

function chartNumericValues(block) {
  return chartListValue(block.chart_values ?? block.chart_series)
    .map((value) => Number(String(value).replace(',', '.')))
    .filter((value) => Number.isFinite(value))
    .slice(0, 12)
}

function chartLabelValues(block, values) {
  const labels = chartListValue(block.chart_labels).slice(0, 12)

  if (labels.length) {
    return labels
  }

  return values.map((_, index) => `S${index + 1}`)
}

function chartColorValues(block) {
  const configuredColors = chartListValue(block.chart_colors)
    .filter((value) => /^#[0-9a-fA-F]{6}$/.test(value))

  return configuredColors.length ? configuredColors : defaultChartPalette
}

function escapeSvgText(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}

function generatedChartSvg(block) {
  const values = chartNumericValues(block)

  if (!values.length) {
    return ''
  }

  const labels = chartLabelValues(block, values)
  const colors = chartColorValues(block)
  const maxValue = Math.max(...values, 1)
  const type = ['bar', 'line', 'doughnut'].includes(block.chart_type) ? block.chart_type : 'bar'
  const title = escapeSvgText(interpolatePreviewHtml(block.chart_title || block.title || 'Gráfico'))
  const backgroundColor = colorInputValue(block.chart_background_color, '#f8f4ea')
  const primaryColor = colorInputValue(block.chart_primary_color, colors[0] || '#143d37')
  const showValues = block.chart_show_values !== false
  const palette = chartSvgPalette(backgroundColor)

  if (type === 'doughnut') {
    const total = values.reduce((sum, value) => sum + Math.max(value, 0), 0) || 1
    const circumference = 251.2
    let offset = 0
    const rings = values.map((value, index) => {
      const segment = (Math.max(value, 0) / total) * circumference
      const ring = `<circle cx="130" cy="128" r="40" fill="none" stroke="${colors[index % colors.length]}" stroke-width="18" stroke-dasharray="${segment.toFixed(2)} ${(circumference - segment).toFixed(2)}" stroke-dashoffset="${(-offset).toFixed(2)}" transform="rotate(-90 130 128)" />`
      offset += segment

      return ring
    }).join('')
    const legend = labels.map((label, index) => {
      const y = 70 + (index * 26)

      return `<g><rect x="250" y="${y - 10}" width="12" height="12" rx="3" fill="${colors[index % colors.length]}"/><text x="272" y="${y}" font-size="12" fill="${palette.muted}">${escapeSvgText(interpolatePreviewHtml(label))}</text><text x="520" y="${y}" font-size="12" font-weight="700" fill="${palette.ink}" text-anchor="end">${values[index]}</text></g>`
    }).join('')

    return `<svg class="report-chart-svg" data-chart-type="${type}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="${title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="${backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="${palette.ink}">${title}</text><circle cx="130" cy="128" r="40" fill="none" stroke="${palette.grid}" stroke-width="18"/>${rings}<text x="130" y="126" text-anchor="middle" font-size="18" font-weight="800" fill="${palette.ink}">${total}</text><text x="130" y="144" text-anchor="middle" font-size="10" fill="${palette.muted}">total</text>${legend}</svg>`
  }

  if (type === 'line') {
    const points = values.map((value, index) => {
      const x = 58 + (index * (470 / Math.max(values.length - 1, 1)))
      const y = 202 - ((value / maxValue) * 132)

      return `${x.toFixed(1)},${y.toFixed(1)}`
    }).join(' ')
    const markers = values.map((value, index) => {
      const x = 58 + (index * (470 / Math.max(values.length - 1, 1)))
      const y = 202 - ((value / maxValue) * 132)

      return `<g><circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="5" fill="${colors[index % colors.length]}" stroke="${palette.markerStroke}" stroke-width="2"/>${showValues ? `<text x="${x.toFixed(1)}" y="${(y - 12).toFixed(1)}" text-anchor="middle" font-size="10" font-weight="700" fill="${palette.ink}">${value}</text>` : ''}<text x="${x.toFixed(1)}" y="230" text-anchor="middle" font-size="10" fill="${palette.muted}">${escapeSvgText(interpolatePreviewHtml(labels[index] || `S${index + 1}`))}</text></g>`
    }).join('')

    return `<svg class="report-chart-svg" data-chart-type="${type}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="${title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="${backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="${palette.ink}">${title}</text><line x1="52" y1="202" x2="532" y2="202" stroke="${palette.grid}"/><line x1="52" y1="70" x2="52" y2="202" stroke="${palette.grid}"/><polyline points="${points}" fill="none" stroke="${primaryColor}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>${markers}</svg>`
  }

  const slot = 470 / Math.max(values.length, 1)
  const bars = values.map((value, index) => {
    const height = Math.max(6, (value / maxValue) * 132)
    const x = 58 + (index * slot) + Math.max(6, slot * 0.15)
    const y = 202 - height
    const width = Math.max(18, slot * 0.7)

    return `<g><rect x="${x.toFixed(1)}" y="${y.toFixed(1)}" width="${width.toFixed(1)}" height="${height.toFixed(1)}" rx="10" fill="${colors[index % colors.length]}"/>${showValues ? `<text x="${(x + width / 2).toFixed(1)}" y="${(y - 10).toFixed(1)}" text-anchor="middle" font-size="11" font-weight="700" fill="${palette.ink}">${value}</text>` : ''}<text x="${(x + width / 2).toFixed(1)}" y="230" text-anchor="middle" font-size="10" fill="${palette.muted}">${escapeSvgText(interpolatePreviewHtml(labels[index] || `S${index + 1}`))}</text></g>`
  }).join('')

  return `<svg class="report-chart-svg" data-chart-type="${type}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="${title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="${backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="${palette.ink}">${title}</text><line x1="52" y1="202" x2="532" y2="202" stroke="${palette.grid}"/><line x1="52" y1="70" x2="52" y2="202" stroke="${palette.grid}"/>${bars}</svg>`
}

function canvasBlockContentHtml(block) {
  if (block.block_kind === 'signature') {
    const alignClass = {
      left: 'items-start text-left',
      center: 'items-center text-center',
      right: 'items-end text-right',
    }[block.signature_align || 'left']

    const lineStyle = block.signature_line_style === 'dashed'
      ? 'border-t border-dashed border-slate-500/70'
      : 'border-t border-slate-500/70'

    const signatureImageUrl = safePreviewMediaUrl(interpolatePreviewHtml(block.signature_image || ''))
    const signatureImageFit = mediaObjectFit(block.signature_image_fit || 'contain')
    const signatureImagePosition = safeCssPositionValue(block.signature_image_position || 'center center', 'center center')
    const signatureImageWidth = clamp(Number(block.signature_image_width || 180), 24, 360)
    const signatureImageHeight = clamp(Number(block.signature_image_height || 72), 16, 240)
    const signatureImage = signatureImageUrl
      ? `<img src="${escapePreviewHtmlAttribute(signatureImageUrl)}" alt="Assinatura" style="display:block; width:${signatureImageWidth}px; max-width:100%; height:${signatureImageHeight}px; object-fit:${signatureImageFit}; object-position:${signatureImagePosition};" />`
      : ''
    const signatureLabel = escapePreviewHtmlText(interpolatePreviewHtml(block.signature_label || ''))
    const signatureName = escapePreviewHtmlText(interpolatePreviewHtml(block.signature_name || ''))
    const signatureTitle = escapePreviewHtmlText(interpolatePreviewHtml(block.signature_title || ''))

    const dateLabel = block.signature_show_date
      ? `<div class="mt-2 text-[11px] text-slate-500">${escapePreviewHtmlText(interpolatePreviewHtml(block.signature_date_label || 'Data: ____ / ____ / ______'))}</div>`
      : ''

    return `
      <div class="flex h-full flex-col justify-end gap-3 ${alignClass}">
        ${signatureImage ? `<div>${signatureImage}</div>` : ''}
        <div class="w-full ${lineStyle}"></div>
        <div class="space-y-1">
          ${signatureLabel ? `<div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">${signatureLabel}</div>` : ''}
          ${signatureName ? `<div class="text-sm font-semibold text-slate-900">${signatureName}</div>` : ''}
          ${signatureTitle ? `<div class="text-xs text-slate-600">${signatureTitle}</div>` : ''}
          ${dateLabel}
        </div>
      </div>
    `.trim()
  }

  if (['image', 'stamp'].includes(block.block_kind)) {
    const imageUrl = safePreviewMediaUrl(interpolatePreviewHtml(block.image_url || block.background_image || ''))

    if (!imageUrl) {
      return '<div class="flex h-full min-h-24 items-center justify-center rounded-2xl border border-dashed border-slate-300 text-xs text-slate-500">Selecione uma imagem da galeria</div>'
    }

    return `<img src="${escapePreviewHtmlAttribute(imageUrl)}" alt="${escapePreviewHtmlAttribute(interpolatePreviewHtml(block.image_alt || block.title || 'Imagem'))}" style="display:block; width:100%; height:100%; min-height:inherit; object-fit:${mediaObjectFit(block.image_fit || 'contain')}; object-position:${imageObjectPosition(block)};" />`
  }

  if (block.block_kind === 'chart_snapshot') {
    const sanitizedChartSvg = sanitizeStudioChartSvg(interpolatePreviewHtml(block.chart_svg || ''))
    const chartTitle = block.chart_title
      ? `<div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">${escapePreviewHtmlText(interpolatePreviewHtml(block.chart_title))}</div>`
      : ''
    const chartCaption = block.chart_caption
      ? `<div class="mt-3 text-xs leading-relaxed text-slate-500">${escapePreviewHtmlText(interpolatePreviewHtml(block.chart_caption))}</div>`
      : ''
    const chartImageUrl = safePreviewMediaUrl(interpolatePreviewHtml(block.chart_image_url || ''))
    const chartGraphic = sanitizedChartSvg
      ? `<div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3">${sanitizedChartSvg}</div>`
      : chartImageUrl
        ? `<img src="${escapePreviewHtmlAttribute(chartImageUrl)}" alt="${escapePreviewHtmlAttribute(interpolatePreviewHtml(block.chart_title || block.title || 'Gráfico'))}" style="display:block; margin-top:12px; width:100%; min-height:140px; object-fit:contain;" />`
        : generatedChartSvg(block)
          ? `<div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3">${generatedChartSvg(block)}</div>`
          : '<div class="mt-3 flex min-h-36 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-center text-xs text-slate-500">Defina rótulos e valores para gerar o gráfico ou use SVG/imagem exportada.</div>'

    return `
      <div class="report-chart studio-avoid-break">
        ${chartTitle}
        ${chartGraphic}
        ${chartCaption}
      </div>
    `.trim()
  }

  if (block.block_kind === 'qr_code') {
    const qrDataUri = qrCodePreviewDataUri(block)

    if (!qrDataUri) {
      return '<div class="flex h-full min-h-24 items-center justify-center rounded-2xl border border-dashed border-slate-300 text-center text-xs text-slate-500">Defina o conteúdo do QR para gerar a pré-visualização.</div>'
    }

    return `
      <div class="flex h-full min-h-24 flex-col items-center justify-center gap-2">
        <img src="${qrDataUri}" alt="QR code" style="display:block; width:100%; max-width:148px; height:auto;" />
        ${block.qr_label ? `<div class="text-center text-[11px] text-slate-500">${escapePreviewHtmlText(interpolatePreviewHtml(block.qr_label))}</div>` : ''}
      </div>
    `.trim()
  }

  return interpolatePreviewHtml(block.content_html || '')
}

function selectCanvasBlock(blockId) {
  selectedCanvasBlockId.value = blockId
}

function blockSurfaceLabel(surface) {
  return canvasSurfaceOptions.find((option) => option.value === surface)?.label ?? surface
}

function blockKindLabel(kind) {
  return blockKindOptions.find((option) => option.value === kind)?.label ?? kind
}

function canvasBlockIsLocked(block) {
  return Boolean(block.is_locked)
}

function canvasBlockIsHidden(block) {
  return Boolean(block.is_hidden)
}

function patchSelectedCanvasBlock(values) {
  if (!selectedCanvasBlock.value) {
    return
  }

  Object.assign(selectedCanvasBlock.value, values)
}

function applyCanvasPreset(preset) {
  patchSelectedCanvasBlock({
    ...preset.values,
  })
}

function presetBodyHtml(studioType) {
  const snippets = snippetLibraries[studioType] || snippetLibraries.analysis

  return snippets
    .filter((snippet) => !snippet.html.includes('<pagebreak'))
    .map((snippet) => snippet.html)
    .join('\n\n')
}

function normalisePresetCanvasBlocks(preset, layoutPreset) {
  const sourceBlocks = Array.isArray(layoutPreset.canvas_blocks)
    ? layoutPreset.canvas_blocks
    : Array.isArray(preset.canvas_blocks)
      ? preset.canvas_blocks
      : []

  return structuredClone(sourceBlocks).map((block, index) => createCanvasBlock({
    ...block,
    id: block.id || `preset-${preset.slug || preset.category || 'studio'}-${index + 1}`,
    surface: block.surface || 'content',
    page_scope: block.page_scope || (block.surface === 'content' ? 'first' : 'all'),
  }))
}

function replaceReactiveObject(target, values) {
  Object.keys(target).forEach((key) => {
    delete target[key]
  })

  Object.assign(target, values)
}

function themeDefaultsForPreset(themeKey) {
  const theme = themeCatalog[themeKey] || themeCatalog.corporate

  return {
    first_page_header_html: theme.firstPageHeaderHtml,
    default_header_html: theme.defaultHeaderHtml,
    footer_html: theme.footerHtml,
    styles_css: theme.styles,
  }
}

function normalisePresetLayout(studioType, preset, layoutPreset, presetCanvasBlocks) {
  const themeKey = preset.theme_preset || (studioType === 'analysis' ? 'compliance' : 'corporate')
  const themeDefaults = themeDefaultsForPreset(themeKey)

  return {
    first_page_header_html: layoutPreset.first_page_header_html || themeDefaults.first_page_header_html || '',
    default_header_html: layoutPreset.default_header_html || themeDefaults.default_header_html || '',
    footer_html: layoutPreset.footer_html || themeDefaults.footer_html || '',
    body_html: preset.body_html || layoutPreset.body_html || presetBodyHtml(studioType),
    styles_css: layoutPreset.styles_css || themeDefaults.styles_css || '',
    sections: Array.isArray(layoutPreset.sections) ? structuredClone(layoutPreset.sections) : [],
    variable_catalog: Array.isArray(layoutPreset.variable_catalog) ? structuredClone(layoutPreset.variable_catalog) : [],
    canvas_blocks: presetCanvasBlocks,
    document_font_family: layoutPreset.document_font_family || 'Manrope, DejaVu Sans, sans-serif',
    background_image_path: layoutPreset.background_image_path || preset.background_image_path || '',
    background_size: layoutPreset.background_size || preset.background_size || 'cover',
    background_position: layoutPreset.background_position || preset.background_position || 'center center',
    background_repeat: layoutPreset.background_repeat || preset.background_repeat || 'no-repeat',
    table_header_background: layoutPreset.table_header_background || '#143d37',
    table_header_text_color: layoutPreset.table_header_text_color || '#ffffff',
    table_border_color: layoutPreset.table_border_color || '#ded3bf',
    table_font_size: numericSetting(layoutPreset.table_font_size, 10),
    table_cell_padding: numericSetting(layoutPreset.table_cell_padding, 8),
    show_canvas_grid: typeof layoutPreset.show_canvas_grid === 'boolean' ? layoutPreset.show_canvas_grid : true,
    show_canvas_rulers: typeof layoutPreset.show_canvas_rulers === 'boolean' ? layoutPreset.show_canvas_rulers : true,
    snap_to_grid: typeof layoutPreset.snap_to_grid === 'boolean' ? layoutPreset.snap_to_grid : true,
    snap_grid_size: clamp(Math.round(numericSetting(layoutPreset.snap_grid_size, 4)), 1, 24),
    page_safe_area: typeof layoutPreset.page_safe_area === 'boolean' ? layoutPreset.page_safe_area : true,
  }
}

function normalisePresetExportSettings(exportPreset = {}) {
  return {
    paper_size: exportPreset.paper_size || 'A4',
    custom_page_width: exportPreset.custom_page_width ?? null,
    custom_page_height: exportPreset.custom_page_height ?? null,
    orientation: exportPreset.orientation || 'P',
    margin_top: numericSetting(exportPreset.margin_top, 20),
    margin_bottom: numericSetting(exportPreset.margin_bottom, 22),
    margin_left: numericSetting(exportPreset.margin_left, 14),
    margin_right: numericSetting(exportPreset.margin_right, 14),
    first_page_margin_top: numericSetting(exportPreset.first_page_margin_top, 56),
  }
}

function ensureCustomPageDefaults() {
  if (!Number.isFinite(Number(props.exportSettings.custom_page_width)) || Number(props.exportSettings.custom_page_width) < 50) {
    props.exportSettings.custom_page_width = pageSizeCatalog.A4.width
  }

  if (!Number.isFinite(Number(props.exportSettings.custom_page_height)) || Number(props.exportSettings.custom_page_height) < 50) {
    props.exportSettings.custom_page_height = pageSizeCatalog.A4.height
  }
}

function applyPageFormat(option) {
  props.exportSettings.paper_size = option.value

  if (option.value === 'custom') {
    ensureCustomPageDefaults()
  }
}

function applyMarginProfile(profile) {
  Object.assign(props.exportSettings, profile.values)
}

function rendererIsAvailable(renderer) {
  if (!renderer || ['internal', 'canva'].includes(renderer)) {
    return true
  }

  return Boolean(props.rendererCapabilities?.[renderer]?.available)
}

function preferredRendererForPreset(preset) {
  if (preset.renderer && rendererIsAvailable(preset.renderer)) {
    return preset.renderer
  }

  if (props.rendererCapabilities?.chrome?.available) {
    return 'chrome'
  }

  return 'internal'
}

function applyBaseModelPreset(preset) {
  const studioType = preset.category || 'analysis'
  const layoutPreset = structuredClone(preset.layout_schema || {})
  const exportPreset = structuredClone(preset.export_settings || {})
  const presetCanvasBlocks = normalisePresetCanvasBlocks(preset, layoutPreset)
  const nextLayout = normalisePresetLayout(studioType, preset, layoutPreset, presetCanvasBlocks)
  const nextExportSettings = normalisePresetExportSettings(exportPreset)

  props.form.name = preset.name
  props.form.studio_type = studioType
  props.form.renderer = preferredRendererForPreset(preset)
  props.form.theme_preset = preset.theme_preset || (studioType === 'analysis' ? 'compliance' : 'corporate')
  props.form.description = preset.description
  props.form.status = props.form.status || 'draft'

  replaceReactiveObject(props.layoutSchema, nextLayout)
  replaceReactiveObject(props.exportSettings, nextExportSettings)
  syncEditorPreferencesFromLayout()
  mediaAssetUrl.value = props.layoutSchema.background_image_path || ''
  activePreviewPage.value = 1
  selectedCanvasBlockId.value = props.layoutSchema.canvas_blocks.find((block) => !block.is_locked)?.id
    || props.layoutSchema.canvas_blocks[0]?.id
    || null
  activeStudioPane.value = 'compose'
}

function alignCanvasBlock(horizontal) {
  if (!selectedCanvasBlock.value) {
    return
  }

  const width = Number(selectedCanvasBlock.value.width || 100)

  if (horizontal === 'left') {
    patchSelectedCanvasBlock({ x: 0 })
  }

  if (horizontal === 'center') {
    patchSelectedCanvasBlock({ x: clamp((100 - width) / 2, 0, 100) })
  }

  if (horizontal === 'right') {
    patchSelectedCanvasBlock({ x: clamp(100 - width, 0, 100) })
  }
}

function alignCanvasBlockVertical(vertical) {
  if (!selectedCanvasBlock.value) {
    return
  }

  if (vertical === 'top') {
    patchSelectedCanvasBlock({ y: 0 })
  }

  if (vertical === 'middle') {
    patchSelectedCanvasBlock({ y: 35 })
  }

  if (vertical === 'bottom') {
    patchSelectedCanvasBlock({ y: 72 })
  }
}

function applyCanvasWidth(widthMode) {
  if (!selectedCanvasBlock.value) {
    return
  }

  if (widthMode === 'full') {
    patchSelectedCanvasBlock({ width: 100, x: 0 })
  }

  if (widthMode === 'wide') {
    patchSelectedCanvasBlock({ width: 84, x: 8 })
  }

  if (widthMode === 'half') {
    patchSelectedCanvasBlock({ width: 48, x: 0 })
  }

  if (widthMode === 'third') {
    patchSelectedCanvasBlock({ width: 31, x: 0 })
  }
}

function toggleSelectedCanvasBlockLock() {
  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value.is_locked = !canvasBlockIsLocked(selectedCanvasBlock.value)
}

function toggleSelectedCanvasBlockVisibility() {
  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value.is_hidden = !canvasBlockIsHidden(selectedCanvasBlock.value)
}

function toggleCanvasBlockVisibility(block) {
  block.is_hidden = !canvasBlockIsHidden(block)
}

function shiftSelectedCanvasBlockLayer(direction) {
  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value.z_index = clamp(Number(selectedCanvasBlock.value.z_index || 10) + direction, 0, 999)
}

function moveSelectedCanvasBlockToEdge(edge) {
  if (!selectedCanvasBlock.value) {
    return
  }

  const siblingBlocks = canvasBlocks.value
    .filter((block) => block.surface === selectedCanvasBlock.value.surface && block.id !== selectedCanvasBlock.value.id && !canvasBlockIsHidden(block))
    .map((block) => Number(block.z_index || 0))

  if (!siblingBlocks.length) {
    selectedCanvasBlock.value.z_index = 10

    return
  }

  if (edge === 'front') {
    selectedCanvasBlock.value.z_index = Math.min(999, Math.max(...siblingBlocks) + 1)

    return
  }

  selectedCanvasBlock.value.z_index = Math.max(0, Math.min(...siblingBlocks) - 1)
}

function setSurfaceLockState(surface, locked) {
  canvasBlocks.value
    .filter((block) => block.surface === surface)
    .forEach((block) => {
      block.is_locked = locked
    })
}

function previewHeaderHtmlForPage(pageNumber) {
  const html = pageNumber === 1
    ? props.layoutSchema.first_page_header_html
    : props.layoutSchema.default_header_html

  return interpolatePreviewHtml(html)
}

function previewFooterHtmlForPage(pageNumber, totalPages) {
  return interpolatePreviewHtml(props.layoutSchema.footer_html || '', {
    PAGENO: String(pageNumber),
    nbpg: String(totalPages),
  })
}

function previewHeaderBlocksForPage(pageNumber) {
  const surface = pageNumber === 1 ? 'first_page_header_html' : 'default_header_html'

  return visibleCanvasBlocks.value.filter((block) => block.surface === surface)
}

function setPreviewPage(pageNumber) {
  activePreviewPage.value = clamp(pageNumber, 1, Math.max(previewPages.value.length, 1))
}

function showPreviewScope(scope) {
  previewMode.value = scope
  previewDisplayMode.value = 'paged'

  if (scope === 'first-page') {
    setPreviewPage(1)

    return
  }

  setPreviewPage(Math.max(2, currentPreviewPage.value))
}

function stepPreviewPage(direction) {
  setPreviewPage(currentPreviewPage.value + direction)
}

function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value))
}

function booleanLayoutSetting(key, fallback = true) {
  return typeof props.layoutSchema[key] === 'boolean' ? props.layoutSchema[key] : fallback
}

function numericLayoutSetting(key, fallback, min, max) {
  return clamp(Math.round(Number(props.layoutSchema[key] || fallback)), min, max)
}

function syncEditorPreferencesFromLayout() {
  showCanvasGrid.value = booleanLayoutSetting('show_canvas_grid', true)
  showSafeArea.value = booleanLayoutSetting('page_safe_area', true)
  showCanvasRulers.value = booleanLayoutSetting('show_canvas_rulers', true)
  snapToGrid.value = booleanLayoutSetting('snap_to_grid', true)
  gridSize.value = numericLayoutSetting('snap_grid_size', 4, 1, 24)
}

function snapPercent(value) {
  if (!snapToGrid.value) {
    return value
  }

  const step = Number(gridSize.value || 0)

  if (step <= 0) {
    return value
  }

  return Math.round(value / step) * step
}

function snapPixels(value) {
  if (!snapToGrid.value) {
    return value
  }

  const step = Math.max(4, Number(gridSize.value || 0) * 4)

  return Math.round(value / step) * step
}

function blockHeightPercent(block, containerRect) {
  if (!containerRect?.height) {
    return 0
  }

  return (Number(block.min_height || 0) / containerRect.height) * 100
}

function snapToCandidates(value, candidates, threshold) {
  let snappedValue = value
  let closestDistance = threshold + 1

  for (const candidate of candidates) {
    const distance = Math.abs(value - candidate)

    if (distance <= threshold && distance < closestDistance) {
      snappedValue = candidate
      closestDistance = distance
    }
  }

  return snappedValue
}

function collectGuidePositions(value, candidates, threshold) {
  return candidates.filter((candidate) => Math.abs(value - candidate) <= threshold)
}

function applyNeighborSnap(block, nextValues, mode, containerRect) {
  const siblingBlocks = canvasBlocks.value.filter((candidate) => {
    return candidate.id !== block.id && candidate.surface === block.surface && !canvasBlockIsHidden(candidate)
  })

  if (!siblingBlocks.length) {
    return nextValues
  }

  const thresholdPercent = Math.max(1.5, Number(gridSize.value || 4) / 2)
  const thresholdPixels = Math.max(8, Number(gridSize.value || 4) * 4)
  const currentWidth = Number(nextValues.width ?? block.width ?? 100)
  const currentX = Number(nextValues.x ?? block.x ?? 0)
  const currentY = Number(nextValues.y ?? block.y ?? 0)
  const currentHeightPixels = Number(nextValues.min_height ?? block.min_height ?? 0)
  const currentHeightPercent = blockHeightPercent({ min_height: currentHeightPixels }, containerRect)
  const guideState = { x: [], y: [] }

  if (mode === 'move') {
    const xCandidates = siblingBlocks.flatMap((candidate) => {
      const siblingX = Number(candidate.x || 0)
      const siblingWidth = Number(candidate.width || 100)

      return [
        siblingX,
        siblingX + siblingWidth - currentWidth,
        siblingX + (siblingWidth / 2) - (currentWidth / 2),
      ]
    })

    const yCandidates = siblingBlocks.flatMap((candidate) => {
      const siblingY = Number(candidate.y || 0)
      const siblingHeightPercent = blockHeightPercent(candidate, containerRect)

      return [
        siblingY,
        siblingY + siblingHeightPercent - currentHeightPercent,
        siblingY + (siblingHeightPercent / 2) - (currentHeightPercent / 2),
      ]
    })

    guideState.x = collectGuidePositions(currentX, xCandidates, thresholdPercent)
    guideState.y = collectGuidePositions(currentY, yCandidates, thresholdPercent)
    nextValues.x = snapToCandidates(currentX, xCandidates, thresholdPercent)
    nextValues.y = snapToCandidates(currentY, yCandidates, thresholdPercent)
  }

  if (mode === 'resize') {
    const widthCandidates = siblingBlocks.flatMap((candidate) => {
      const siblingX = Number(candidate.x || 0)
      const siblingWidth = Number(candidate.width || 100)

      return [
        siblingX - currentX,
        (siblingX + siblingWidth) - currentX,
      ]
    }).filter((candidate) => candidate >= 5 && candidate <= 100)

    const currentTopPixels = containerRect.height > 0
      ? (currentY / 100) * containerRect.height
      : 0

    const heightCandidates = siblingBlocks.flatMap((candidate) => {
      const siblingTopPixels = containerRect.height > 0
        ? (Number(candidate.y || 0) / 100) * containerRect.height
        : 0
      const siblingBottomPixels = siblingTopPixels + Number(candidate.min_height || 0)

      return [
        siblingTopPixels - currentTopPixels,
        siblingBottomPixels - currentTopPixels,
      ]
    }).filter((candidate) => candidate >= 0 && candidate <= 4000)

    guideState.x = collectGuidePositions(currentWidth, widthCandidates, thresholdPercent).map((candidate) => currentX + candidate)
    guideState.y = collectGuidePositions(currentHeightPixels, heightCandidates, thresholdPixels).map((candidate) => currentY + ((candidate / containerRect.height) * 100))
    nextValues.width = snapToCandidates(currentWidth, widthCandidates, thresholdPercent)
    nextValues.min_height = snapToCandidates(currentHeightPixels, heightCandidates, thresholdPixels)
  }

  alignmentGuides.value = {
    x: [...new Set(guideState.x.map((value) => clamp(Number(value), 0, 100)).filter((value) => Number.isFinite(value)))],
    y: [...new Set(guideState.y.map((value) => clamp(Number(value), 0, 100)).filter((value) => Number.isFinite(value)))],
  }

  return nextValues
}

const previewGridStyle = computed(() => {
  if (!showCanvasGrid.value) {
    return {}
  }

  const cellSize = Math.max(12, Number(gridSize.value || 4) * 6)

  return {
    backgroundImage: 'linear-gradient(to right, rgba(148, 163, 184, 0.14) 1px, transparent 1px), linear-gradient(to bottom, rgba(148, 163, 184, 0.14) 1px, transparent 1px)',
    backgroundSize: `${cellSize}px ${cellSize}px`,
  }
})

const canvasRulerMarks = computed(() => [0, 25, 50, 75, 100])

const tableStyleSettings = computed(() => ({
  table_header_background: safeCssColorValue(props.layoutSchema.table_header_background, '#0f172a'),
  table_header_text_color: safeCssColorValue(props.layoutSchema.table_header_text_color, '#ffffff'),
  table_border_color: safeCssColorValue(props.layoutSchema.table_border_color, '#cbd5e1'),
  table_font_size: props.layoutSchema.table_font_size || 10,
  table_cell_padding: props.layoutSchema.table_cell_padding || 8,
  table_summary_background: safeCssColorValue(props.layoutSchema.table_summary_background, '#fffdf7'),
  table_summary_text_color: safeCssColorValue(props.layoutSchema.table_summary_text_color, '#15231f'),
  table_summary_muted_color: safeCssColorValue(props.layoutSchema.table_summary_muted_color, '#64748b'),
}))

const tablePreviewHeaderStyle = computed(() => ({
  backgroundColor: tableStyleSettings.value.table_header_background,
  borderColor: tableStyleSettings.value.table_border_color,
  color: tableStyleSettings.value.table_header_text_color,
  fontSize: `${tableStyleSettings.value.table_font_size}px`,
  padding: `${tableStyleSettings.value.table_cell_padding}px`,
}))

const tablePreviewCellStyle = computed(() => ({
  borderColor: tableStyleSettings.value.table_border_color,
  fontSize: `${tableStyleSettings.value.table_font_size}px`,
  padding: `${tableStyleSettings.value.table_cell_padding}px`,
}))

const tablePreviewSummaryStyle = computed(() => ({
  backgroundColor: tableStyleSettings.value.table_summary_background,
  borderColor: tableStyleSettings.value.table_border_color,
  color: tableStyleSettings.value.table_summary_text_color,
}))

const tablePreviewSummaryMutedStyle = computed(() => ({
  color: tableStyleSettings.value.table_summary_muted_color,
}))

const activeTableStylePresetKey = computed(() => {
  return tableStylePresets.find((preset) => tableStylePresetIsActive(preset))?.key || null
})

function colorInputValue(value, fallback = '#0f172a') {
  return normalizedHexColor(value, fallback)
}

function setSelectedBlockColor(field, value) {
  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value[field] = value
}

function normalizeTableStyleValue(value) {
  return String(value ?? '').trim().toLowerCase()
}

function tableStylePresetIsActive(preset) {
  return Object.entries(preset.values).every(([key, value]) => {
    return normalizeTableStyleValue(props.layoutSchema[key]) === normalizeTableStyleValue(value)
  })
}

function applyTableStylePreset(preset) {
  Object.assign(props.layoutSchema, preset.values)
  syncTableStylesCss()
}

function syncTableStylesCss() {
  const settings = tableStyleSettings.value
  const generatedCss = [
    '/* studio-table-controls:start */',
    'table{width:100% !important;border-collapse:collapse !important;}',
    `th{background:${settings.table_header_background} !important;color:${settings.table_header_text_color} !important;font-size:${settings.table_font_size}px !important;letter-spacing:0.04em !important;text-transform:uppercase !important;}`,
    `th,td{border:1px solid ${settings.table_border_color} !important;padding:${settings.table_cell_padding}px !important;vertical-align:top !important;}`,
    '.document-summary-table{border-collapse:separate !important;border-spacing:0 8px !important;}',
    '.document-summary-table td{border:0 !important;padding:4px !important;}',
    `.document-summary-cell{background:${settings.table_summary_background} !important;border:1px solid ${settings.table_border_color} !important;border-radius:18px !important;padding:14px !important;vertical-align:top !important;}`,
    `.document-summary-cell .label{color:${settings.table_summary_muted_color} !important;}`,
    `.document-summary-cell .value{color:${settings.table_summary_text_color} !important;}`,
    `.document-summary-cell .muted{color:${settings.table_summary_muted_color} !important;}`,
    `.document-financial-summary td{color:${settings.table_summary_text_color};}`,
    '/* studio-table-controls:end */',
  ].join('\n')

  props.layoutSchema.styles_css = String(props.layoutSchema.styles_css || '')
    .replace(/\/\* studio-table-controls:start \*\/[\s\S]*?\/\* studio-table-controls:end \*\//, '')
    .trim()

  props.layoutSchema.styles_css = `${props.layoutSchema.styles_css}\n\n${generatedCss}`.trim()
}

function syncDocumentStylesCss() {
  const generatedCss = [
    '/* studio-document-controls:start */',
    `body{font-family:${documentFontFamily.value};}`,
    '/* studio-document-controls:end */',
  ].join('\n')

  props.layoutSchema.styles_css = String(props.layoutSchema.styles_css || '')
    .replace(/\/\* studio-document-controls:start \*\/[\s\S]*?\/\* studio-document-controls:end \*\//, '')
    .trim()

  props.layoutSchema.styles_css = `${generatedCss}\n\n${props.layoutSchema.styles_css}`.trim()
}

function updateInteraction(event) {
  if (!interactionState.value) {
    return
  }

  const { block, mode, containerRect, startX, startY, origin } = interactionState.value
  const deltaX = event.clientX - startX
  const deltaY = event.clientY - startY
  const deltaXPercent = containerRect.width > 0 ? (deltaX / containerRect.width) * 100 : 0
  const deltaYPercent = containerRect.height > 0 ? (deltaY / containerRect.height) * 100 : 0

  if (mode === 'move') {
    const nextValues = applyNeighborSnap(block, {
      x: clamp(snapPercent(Number(origin.x) + deltaXPercent), 0, 100),
      y: clamp(snapPercent(Number(origin.y) + deltaYPercent), 0, 100),
    }, mode, containerRect)

    block.x = clamp(nextValues.x, 0, 100)
    block.y = clamp(nextValues.y, 0, 100)
  }

  if (mode === 'resize') {
    const nextValues = applyNeighborSnap(block, {
      width: clamp(snapPercent(Number(origin.width) + deltaXPercent), 5, 100),
      min_height: clamp(snapPixels(Number(origin.minHeight) + deltaY), 0, 4000),
    }, mode, containerRect)

    block.width = clamp(nextValues.width, 5, 100)
    block.min_height = clamp(nextValues.min_height, 0, 4000)
  }
}

function stopInteraction() {
  interactionState.value = null
  alignmentGuides.value = { x: [], y: [] }
  window.removeEventListener('mousemove', updateInteraction)
  window.removeEventListener('mouseup', stopInteraction)
}

function startInteraction(event, block, mode) {
  if (canvasBlockIsLocked(block)) {
    return
  }

  const surfaceElement = event.currentTarget.closest('[data-canvas-surface]')

  if (!surfaceElement) {
    return
  }

  selectedCanvasBlockId.value = block.id
  interactionState.value = {
    block,
    mode,
    containerRect: surfaceElement.getBoundingClientRect(),
    startX: event.clientX,
    startY: event.clientY,
    origin: {
      x: Number(block.x || 0),
      y: Number(block.y || 0),
      width: Number(block.width || 100),
      minHeight: Number(block.min_height || 0),
    },
  }

  window.addEventListener('mousemove', updateInteraction)
  window.addEventListener('mouseup', stopInteraction)
}

function startDrag(event, block) {
  event.preventDefault()
  event.stopPropagation()
  startInteraction(event, block, 'move')
}

function startResize(event, block) {
  event.preventDefault()
  event.stopPropagation()
  startInteraction(event, block, 'resize')
}

function isTypingTarget(target) {
  if (!(target instanceof HTMLElement)) {
    return false
  }

  return target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
}

function handleCanvasKeyboard(event) {
  if (!selectedCanvasBlock.value || isTypingTarget(event.target)) {
    return
  }

  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'd') {
    event.preventDefault()
    duplicateCanvasBlock(selectedCanvasBlock.value)

    return
  }

  if (event.key.toLowerCase() === 'l') {
    event.preventDefault()
    toggleSelectedCanvasBlockLock()

    return
  }

  if (event.key === '[') {
    event.preventDefault()
    shiftSelectedCanvasBlockLayer(-1)

    return
  }

  if (event.key === ']') {
    event.preventDefault()
    shiftSelectedCanvasBlockLayer(1)

    return
  }

  if (canvasBlockIsLocked(selectedCanvasBlock.value)) {
    return
  }

  const moveStep = snapToGrid.value ? Math.max(1, Number(gridSize.value || 4)) : 1
  const resizeStep = snapToGrid.value ? Math.max(8, Number(gridSize.value || 4) * 4) : 8
  const handledKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown']

  if (!handledKeys.includes(event.key)) {
    return
  }

  event.preventDefault()

  if (event.shiftKey) {
    if (event.key === 'ArrowLeft') {
      selectedCanvasBlock.value.width = clamp(Number(selectedCanvasBlock.value.width || 100) - moveStep, 5, 100)
    }

    if (event.key === 'ArrowRight') {
      selectedCanvasBlock.value.width = clamp(Number(selectedCanvasBlock.value.width || 100) + moveStep, 5, 100)
    }

    if (event.key === 'ArrowUp') {
      selectedCanvasBlock.value.min_height = clamp(Number(selectedCanvasBlock.value.min_height || 0) - resizeStep, 0, 4000)
    }

    if (event.key === 'ArrowDown') {
      selectedCanvasBlock.value.min_height = clamp(Number(selectedCanvasBlock.value.min_height || 0) + resizeStep, 0, 4000)
    }

    return
  }

  if (event.key === 'ArrowLeft') {
    selectedCanvasBlock.value.x = clamp(Number(selectedCanvasBlock.value.x || 0) - moveStep, 0, 100)
  }

  if (event.key === 'ArrowRight') {
    selectedCanvasBlock.value.x = clamp(Number(selectedCanvasBlock.value.x || 0) + moveStep, 0, 100)
  }

  if (event.key === 'ArrowUp') {
    selectedCanvasBlock.value.y = clamp(Number(selectedCanvasBlock.value.y || 0) - moveStep, 0, 100)
  }

  if (event.key === 'ArrowDown') {
    selectedCanvasBlock.value.y = clamp(Number(selectedCanvasBlock.value.y || 0) + moveStep, 0, 100)
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleCanvasKeyboard)
})

onBeforeUnmount(() => {
  stopInteraction()
  stopFocalDrag()
  window.removeEventListener('keydown', handleCanvasKeyboard)
})

function insertPlaceholder(placeholder) {
  appendSnippet(placeholder)
}

function insertImageSnippet() {
  if (!mediaAssetUrl.value) {
    return
  }

  addCanvasBlock({
    title: 'Imagem posicionável',
    block_kind: 'image',
    content_html: '',
    image_url: mediaAssetUrl.value,
    image_alt: 'Imagem do documento',
    image_fit: 'cover',
    image_position: 'center center',
    width: 44,
    min_height: 180,
    padding: 0,
    border_radius: 22,
    background_color: 'transparent',
  })
}

function applyBackgroundImage() {
  applyAssetToDocumentBackground(mediaAssetUrl.value)
}

function applyThemePreset(themeKey) {
  const theme = themeCatalog[themeKey]

  if (!theme) {
    return
  }

  props.form.theme_preset = themeKey
  props.layoutSchema.styles_css = theme.styles
  props.layoutSchema.document_font_family = props.layoutSchema.document_font_family || 'Manrope, DejaVu Sans, sans-serif'

  if (!props.layoutSchema.first_page_header_html || props.layoutSchema.first_page_header_html.includes('{{lab_name}}')) {
    props.layoutSchema.first_page_header_html = theme.firstPageHeaderHtml
  }

  if (!props.layoutSchema.default_header_html || props.layoutSchema.default_header_html.includes('{{document_code}}')) {
    props.layoutSchema.default_header_html = theme.defaultHeaderHtml
  }

  if (!props.layoutSchema.footer_html || props.layoutSchema.footer_html.includes('{PAGENO}/{nbpg}')) {
    props.layoutSchema.footer_html = theme.footerHtml
  }
}

function syncAssetUrlFromBackground() {
  mediaAssetUrl.value = props.layoutSchema.background_image_path || ''
}

function studioPayload() {
  return {
    name: props.form.name || '',
    studio_type: props.form.studio_type,
    renderer: props.form.renderer,
    status: props.form.status || 'draft',
    is_default: Boolean(props.form.is_default),
    theme_preset: props.form.theme_preset || 'corporate',
    canva_design_url: props.form.canva_design_url || '',
    description: props.form.description || '',
    layout_schema: props.layoutSchema,
    export_settings: props.exportSettings,
  }
}

function filenameFromResponse(response, fallback = 'report-studio-draft-preview.pdf') {
  const contentDisposition = response?.headers?.['content-disposition'] || ''
  const match = contentDisposition.match(/filename="?([^";]+)"?/i)

  return match?.[1] || fallback
}

function downloadBlob(blob, filename) {
  const objectUrl = window.URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = objectUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(objectUrl)
}

async function previewErrorMessage(error) {
  const fallback = studioCopy('draft_preview.error', {}, 'Não foi possível gerar a pré-visualização PDF deste rascunho.')
  const payload = error?.response?.data

  if (payload instanceof Blob) {
    const text = await payload.text()

    try {
      const json = JSON.parse(text)
      const firstError = Object.values(json.errors || {}).flat()[0]

      return json.message || firstError || fallback
    } catch {
      return text || fallback
    }
  }

  return error?.response?.data?.message || error?.message || fallback
}

async function previewDraftPdf() {
  draftPreviewError.value = ''

  if (!props.draftPreviewHref) {
    draftPreviewError.value = studioCopy('draft_preview.missing_route', {}, 'A rota de pré-visualização do rascunho não está disponível.')

    return
  }

  if (rendererSelectionUnavailable.value) {
    draftPreviewError.value = studioCopy('draft_preview.renderer_unavailable', {}, 'Este renderizador precisa de um driver instalado no servidor antes de pré-visualizar.')

    return
  }

  syncTableStylesCss()
  syncDocumentStylesCss()
  props.layoutSchema.variable_catalog = translatedPlaceholders.value
  draftPreviewBusy.value = true

  try {
    const response = await axios.post(props.draftPreviewHref, studioPayload(), {
      responseType: 'blob',
    })

    downloadBlob(response.data, filenameFromResponse(response))
  } catch (error) {
    draftPreviewError.value = await previewErrorMessage(error)
  } finally {
    draftPreviewBusy.value = false
  }
}

function submit() {
  if (rendererSelectionUnavailable.value) {
    if (typeof props.form.setError === 'function') {
      props.form.setError('renderer', 'Este renderizador precisa de um driver instalado no servidor antes de poder ser guardado.')
    }

    return
  }

  syncTableStylesCss()
  syncDocumentStylesCss()
  props.layoutSchema.variable_catalog = translatedPlaceholders.value
  emit('submit')
}
</script>

<template>
  <Head :title="props.title" />

  <div class="report-studio-shell space-y-8 font-sans">
    <component :is="'style'" v-text="previewScopedCss" />

    <div class="relative isolate overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-6 text-[#15231f] shadow-[0_26px_80px_rgba(20,61,55,0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:ring-white/10 sm:p-8">
      <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-200-rgb)/0.38),transparent_34%),radial-gradient(circle_at_85%_12%,rgb(var(--accent-300-rgb)/0.25),transparent_26%),linear-gradient(135deg,rgb(255_253_247/0.98),rgb(244_239_228/0.9))] dark:bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-500-rgb)/0.18),transparent_34%),radial-gradient(circle_at_85%_12%,rgb(var(--accent-300-rgb)/0.14),transparent_28%),linear-gradient(135deg,#07110f,#10231f)]" />
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-4xl">
          <div class="inline-flex rounded-full border border-[rgb(var(--accent-300-rgb)/0.5)] bg-[rgb(var(--accent-50-rgb)/0.8)] px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-[rgb(var(--primary-900-rgb))] shadow-sm dark:border-[rgb(var(--accent-300-rgb)/0.24)] dark:bg-[rgb(var(--accent-300-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
            Estúdio documental
          </div>
          <h1 class="mt-4 text-3xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7] sm:text-4xl">{{ props.title }}</h1>
          <p class="mt-3 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            {{ props.intro }}
          </p>
          <p v-if="props.initialDraftLabel" class="mt-4 inline-flex rounded-full border border-[#ded3bf] bg-white/70 px-3 py-1 text-xs font-bold text-[#15231f] shadow-sm dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7]">
            {{ props.initialDraftLabel }}
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link
            :href="props.backHref"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#ded3bf] bg-white/75 px-4 py-3 text-sm font-bold text-[#15231f] shadow-sm transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-white dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:hover:border-[rgb(var(--primary-400-rgb)/0.55)]"
          >
            <ArrowUturnLeftIcon class="h-4 w-4" />
            {{ props.backLabel }}
          </Link>
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#ded3bf] bg-white/75 px-4 py-3 text-sm font-bold text-[#15231f] shadow-sm transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-white disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:hover:border-[rgb(var(--primary-400-rgb)/0.55)]"
            :disabled="draftPreviewBusy || props.form.processing"
            @click="previewDraftPdf"
          >
            <EyeIcon class="h-4 w-4" />
            {{ draftPreviewBusy ? studioCopy('draft_preview.generating', {}, 'A gerar PDF...') : studioCopy('draft_preview.action', {}, 'Pré-visualizar PDF') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-3 text-sm font-bold text-white shadow-[0_18px_45px_rgb(var(--primary-900-rgb)/0.18)] transition hover:bg-[rgb(var(--primary-700-rgb))] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-[rgb(var(--accent-300-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--accent-200-rgb))]"
            :disabled="props.form.processing"
            @click="submit"
          >
            {{ props.form.processing ? 'A guardar...' : props.submitLabel }}
          </button>
        </div>
      </div>
      <p v-if="draftPreviewError" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">
        {{ draftPreviewError }}
      </p>
    </div>

    <nav class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7]/92 p-2 shadow-[0_18px_55px_rgba(20,61,55,0.07)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f]/92 dark:ring-white/10">
      <div class="grid gap-2 md:grid-cols-4">
        <button
          v-for="pane in studioPanes"
          :key="pane.value"
          type="button"
          class="rounded-[1.4rem] px-4 py-3 text-left transition"
          :class="activeStudioPane === pane.value
            ? 'bg-[rgb(var(--primary-800-rgb))] text-white shadow-[0_16px_38px_rgb(var(--primary-900-rgb)/0.18)] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]'
            : 'text-[#475a53] hover:bg-[#f4efe4] hover:text-[#15231f] dark:text-[#cbd8cf] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]'"
          @click="activeStudioPane = pane.value"
        >
          <span class="block text-sm font-black">{{ pane.label }}</span>
          <span class="mt-1 block text-xs leading-5" :class="activeStudioPane === pane.value ? 'text-white/75 dark:text-[#07110f]/70' : 'text-[#6b7b74] dark:text-[#83978d]'">
            {{ pane.description }}
          </span>
        </button>
      </div>
    </nav>

    <section class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-5 shadow-[0_18px_55px_rgba(20,61,55,0.06)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div class="inline-flex rounded-full border px-3 py-1 text-xs font-black uppercase tracking-[0.18em]" :class="studioQualityStatus.class">
              {{ studioQualityStatus.label }}
            </div>
            <h2 class="mt-3 text-xl font-black text-[#15231f] dark:text-[#f7f1e7]">Controlo de qualidade do modelo</h2>
            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
              {{ studioQualityStatus.description }} O estúdio valida variáveis, estrutura de páginas e riscos de renderização antes da geração do PDF.
            </p>
          </div>
          <div class="grid grid-cols-3 gap-2 rounded-[1.5rem] border border-[#ded3bf] bg-[#f8f4ea] p-2 dark:border-[#25443c] dark:bg-[#10231f]">
            <div v-for="metric in studioQualityMetrics" :key="metric.label" class="rounded-[1.15rem] bg-[#fffdf7] px-3 py-2 text-center shadow-sm dark:bg-[#07110f]">
              <div class="text-lg font-black text-[#15231f] dark:text-[#f7f1e7]">{{ metric.value }}</div>
              <div class="mt-0.5 text-[10px] font-black uppercase tracking-[0.14em] text-[#6b7b74] dark:text-[#83978d]">{{ metric.label }}</div>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div v-if="studioQualityIssues.length" class="space-y-2">
            <button
              v-for="issue in studioQualityIssues.slice(0, 4)"
              :key="issue.key"
              type="button"
              class="w-full rounded-[1.35rem] border px-4 py-3 text-left transition hover:-translate-y-0.5 hover:shadow-[0_14px_36px_rgba(20,61,55,0.10)]"
              :class="issue.tone === 'critical'
                ? 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100'
                : issue.tone === 'warning'
                  ? 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100'
                  : 'border-[#ded3bf] bg-[#f8f4ea] text-[#475a53] dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#cbd8cf]'"
              @click="focusQualityIssue(issue)"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-black">{{ issue.title }}</div>
                  <p class="mt-1 text-xs font-medium leading-5 opacity-80">{{ issue.description }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-white/70 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em] dark:bg-black/20">
                  {{ issue.pane === 'pdf' ? 'PDF' : 'Compor' }}
                </span>
              </div>
            </button>
          </div>
          <div v-else class="rounded-[1.35rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
            Sem bloqueios detectados para a pré-visualização actual.
          </div>
          <button
            v-if="rendererCssRisks.length"
            type="button"
            class="inline-flex w-full items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-3 text-sm font-black text-white shadow-[0_16px_40px_rgb(var(--primary-900-rgb)/0.14)] transition hover:bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--accent-300-rgb))] dark:text-[#07110f]"
            @click="preferChromeRenderer"
          >
            {{ props.rendererCapabilities?.chrome?.available ? 'Usar Chrome PDF para fidelidade' : 'Rever renderizador PDF' }}
          </button>
        </div>
      </div>
    </section>

    <div v-if="props.presets.length" v-show="activeStudioPane === 'setup'" class="rounded-3xl border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-[0_18px_55px_rgba(20,61,55,0.06)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <h2 class="text-lg font-bold text-[#15231f] dark:text-[#f7f1e7]">Modelos base</h2>
      <p class="mt-1 text-sm font-medium text-[#475a53] dark:text-[#cbd8cf]">Use um preset como ponto de partida e depois refine o canvas abaixo.</p>
      <swiper-container
        class="report-studio-swiper mt-5 block"
        :slides-per-view="1.05"
        :space-between="16"
        :breakpoints="{ 768: { slidesPerView: 2.1 }, 1280: { slidesPerView: 3.1 } }"
        navigation="true"
        pagination="true"
      >
        <swiper-slide
          v-for="preset in props.presets"
          :key="preset.slug"
        >
          <button
            type="button"
            class="min-h-44 w-full rounded-2xl border border-[#ded3bf] bg-[#f8f4ea]/85 p-4 text-left transition hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[#fffdf7] hover:shadow-[0_20px_50px_rgb(var(--primary-900-rgb)/0.10)] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.25)] dark:border-[#25443c] dark:bg-[#10231f]/70 dark:hover:border-[rgb(var(--primary-400-rgb)/0.55)] dark:hover:bg-[#10231f]"
            @click="applyBaseModelPreset(preset)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ preset.name }}</div>
                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-[#6b7b74] dark:text-[#83978d]">{{ studioTypeLabel(preset.category) }}</div>
              </div>
              <span class="rounded-full bg-[rgb(var(--accent-50-rgb))] px-2.5 py-1 text-[11px] font-black text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--accent-300-rgb)/0.14)] dark:text-[rgb(var(--accent-200-rgb))]">
                Usar
              </span>
            </div>
            <p class="mt-3 text-sm leading-6 text-[#475a53] dark:text-[#cbd8cf]">{{ preset.description }}</p>
          </button>
        </swiper-slide>
      </swiper-container>
    </div>

    <div
      v-show="activeStudioPane !== 'preview'"
      class="grid gap-8 xl:grid-cols-1"
    >
      <section v-show="activeStudioPane !== 'pdf'" class="space-y-6">
        <div v-show="activeStudioPane === 'setup'" class="space-y-5">
          <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(310px,0.65fr)]">
            <section class="studio-output-panel">
              <div class="studio-output-eyebrow">Identidade do modelo</div>
              <h2 class="studio-output-title">Defina o propósito antes de desenhar</h2>
              <p class="studio-output-description">O nome, o tipo documental e a descrição orientam a equipa e determinam as variáveis disponíveis no estúdio.</p>
              <div class="mt-6 grid gap-4 md:grid-cols-2">
                <label class="studio-setup-field">Nome do modelo
                  <input v-model="props.form.name" type="text" placeholder="Ex.: Relatório analítico de rotina" />
                  <span v-if="props.form.errors.name" class="studio-setup-field__error">{{ props.form.errors.name }}</span>
                </label>
                <label class="studio-setup-field">Tipo documental
                  <select v-model="props.form.studio_type" @change="emit('update:studio-type', props.form.studio_type)">
                    <option value="analysis">Relatório analítico</option>
                    <option value="executive">Relatório executivo</option>
                    <option value="export_certificate">Certificado de exportação</option>
                    <option value="import_certificate">Certificado de importação</option>
                    <option value="quote">Proforma / orçamento</option>
                    <option value="invoice">Factura</option>
                    <option value="receipt">Recibo</option>
                    <option value="credit_note">Nota de crédito</option>
                    <option value="proposal">Proposta</option>
                  </select>
                </label>
                <label class="studio-setup-field md:col-span-2">Descrição operacional
                  <textarea v-model="props.form.description" rows="4" placeholder="Explique quando este modelo deve ser utilizado e que decisões suporta." />
                </label>
              </div>
            </section>

            <aside class="studio-lifecycle-card">
              <div class="studio-output-eyebrow">Ciclo de vida</div>
              <h3 class="mt-2 text-lg font-black text-[#15231f] dark:text-[#f7f1e7]">Disponibilidade do modelo</h3>
              <div class="mt-5 space-y-2">
                <button
                  v-for="option in studioStatusOptions"
                  :key="`studio-status-${option.value}`"
                  type="button"
                  class="studio-lifecycle-option"
                  :class="{ 'studio-lifecycle-option--active': props.form.status === option.value }"
                  @click="props.form.status = option.value"
                >
                  <span class="studio-lifecycle-option__mark" />
                  <span>
                    <span class="block text-sm font-black">{{ option.label }}</span>
                    <span class="mt-1 block text-xs font-medium leading-5 opacity-70">{{ option.description }}</span>
                  </span>
                </button>
              </div>
              <label class="studio-default-toggle mt-4">
                <input v-model="props.form.is_default" type="checkbox" />
                <span>
                  <span class="block text-sm font-black">Modelo padrão</span>
                  <span class="mt-1 block text-xs font-medium leading-5 opacity-70">Pré-seleccionar para novos {{ studioLabels.plural }}.</span>
                </span>
              </label>
            </aside>
          </div>

          <section class="studio-output-panel">
            <div class="studio-output-panel__heading">
              <div>
                <div class="studio-output-eyebrow">Direcção visual</div>
                <h3 class="studio-output-title">Uma linguagem coerente para o documento</h3>
                <p class="studio-output-description">{{ selectedThemePreset.description }}</p>
              </div>
              <span class="studio-theme-badge">{{ selectedThemePreset.badge }}</span>
            </div>
            <div class="mt-6 grid gap-4 lg:grid-cols-4">
              <button
                v-for="option in themePresetOptions"
                :key="`studio-theme-${option.value}`"
                type="button"
                class="studio-theme-card"
                :class="{ 'studio-theme-card--active': props.form.theme_preset === option.value }"
                @click="applyThemePreset(option.value)"
              >
                <span class="studio-theme-card__specimen">
                  <span class="studio-theme-card__accent bg-gradient-to-r" :class="themeCatalog[option.value].accent" />
                  <span class="studio-theme-card__line studio-theme-card__line--strong" />
                  <span class="studio-theme-card__line" />
                  <span class="studio-theme-card__table">
                    <span /><span /><span />
                  </span>
                </span>
                <span class="mt-4 block text-sm font-black">{{ option.label }}</span>
                <span class="mt-2 block text-xs font-medium leading-5 opacity-70">{{ themeCatalog[option.value].description }}</span>
              </button>
            </div>
          </section>

          <section class="studio-output-panel">
            <div class="studio-output-panel__heading">
              <div>
                <div class="studio-output-eyebrow">Estratégia de saída</div>
                <h3 class="studio-output-title">Motor de renderização</h3>
                <p class="studio-output-description">Escolha o motor de acordo com a complexidade visual. Esta decisão pode ser refinada novamente antes da emissão.</p>
              </div>
              <button type="button" class="studio-output-action" @click="activeStudioPane = 'pdf'; pdfWorkspaceSection = 'output'">Configurar saída</button>
            </div>
            <div class="mt-6 grid gap-4 lg:grid-cols-3">
              <button
                v-for="option in rendererOptions.filter((renderer) => renderer.value !== 'canva')"
                :key="`setup-renderer-${option.value}`"
                type="button"
                class="studio-renderer-card"
                :class="{ 'studio-renderer-card--active': props.form.renderer === option.value }"
                :disabled="!option.available"
                @click="props.form.renderer = option.value"
              >
                <span class="flex items-start justify-between gap-4">
                  <span>
                    <span class="block text-sm font-black">{{ option.label }}</span>
                    <span class="mt-2 block text-xs font-medium leading-5 opacity-75">{{ option.description }}</span>
                  </span>
                  <span class="studio-renderer-card__badge">{{ option.badge }}</span>
                </span>
              </button>
            </div>
            <p v-if="props.form.errors.renderer" class="mt-3 text-xs font-black text-rose-600 dark:text-rose-300">{{ props.form.errors.renderer }}</p>
            <details class="studio-advanced-panel mt-5">
              <summary>Referência externa e diagnóstico técnico</summary>
              <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(280px,0.7fr)]">
                <label class="studio-code-field">Referência externa de design
                  <input v-model="props.form.canva_design_url" type="url" placeholder="https://www.canva.com/design/..." />
                </label>
                <div class="rounded-[1.2rem] border border-[#ded3bf] bg-[#fffdf7] p-4 text-xs font-medium leading-5 text-[#6b7b74] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#a9bcb2]">
                  <div class="font-black text-[#15231f] dark:text-[#f7f1e7]">{{ selectedRendererOption.label }} · {{ selectedRendererOption.badge }}</div>
                  <p class="mt-2">{{ selectedRendererOption.description }}</p>
                  <p v-if="selectedRendererOption.value === 'chrome' && selectedRendererOption.binaryPath" class="mt-2 font-mono text-[11px]">Chrome: {{ selectedRendererOption.binaryPath }}</p>
                </div>
              </div>
            </details>
          </section>
        </div>

        <div v-show="activeStudioPane === 'compose'" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Conteúdo do documento</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">Caracteres: {{ contentLength }}</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
              <SparklesIcon class="h-4 w-4" />
              {{ studioLabels.badge }}
            </div>
          </div>

          <fancy-textarea v-model="props.layoutSchema.body_html" />
          <p v-if="props.form.errors.layout_schema" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ props.form.errors.layout_schema }}</p>
        </div>

        <div v-show="activeStudioPane === 'compose'" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Canvas de composição</h2>
              <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Escolha a superfície de destino e injete blocos prontos para capas, KPIs, resultados, assinaturas, imagens e tabelas.
              </p>
            </div>
            <div class="lg:w-72">
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Superfície alvo</label>
              <select v-model="snippetTarget" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <option v-for="option in snippetTargetOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>
          </div>

          <div class="mt-4 rounded-2xl border border-primary-100 bg-primary-50/70 px-4 py-3 text-sm text-primary-900 dark:border-primary-900/30 dark:bg-primary-950/30 dark:text-primary-200">
            A editar agora: <span class="font-semibold">{{ surfaceLabel }}</span>
          </div>

          <div class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-[#f7f1e7] shadow-inner dark:border-slate-700 dark:bg-slate-950">
            <div class="grid min-h-[760px] 2xl:grid-cols-[300px_minmax(0,1fr)_340px]">
              <aside class="border-b border-slate-200 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-900/80 2xl:border-b-0 2xl:border-r">
                <div class="flex rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-bold dark:border-slate-700 dark:bg-slate-950">
                  <button
                    type="button"
                    class="flex-1 rounded-full px-3 py-2 transition"
                    :class="editorRailMode === 'layers' ? 'bg-[#143d37] text-white shadow-sm dark:bg-[#d9b05f] dark:text-[#07110f]' : 'text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white'"
                    @click="editorRailMode = 'layers'"
                  >
                    Objectos
                  </button>
                  <button
                    type="button"
                    class="flex-1 rounded-full px-3 py-2 transition"
                    :class="editorRailMode === 'assets' ? 'bg-[#143d37] text-white shadow-sm dark:bg-[#d9b05f] dark:text-[#07110f]' : 'text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white'"
                    @click="editorRailMode = 'assets'"
                  >
                    Media
                  </button>
                </div>

                <div v-show="editorRailMode === 'layers'" class="mt-4 space-y-3">
                  <div class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950/60">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Objectos do documento</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Os objectos no topo aparecem por cima no canvas. Controle visibilidade, bloqueio, ordem visual e âmbito por página.</p>
                  </div>
                  <template v-for="group in canvasBlockGroups" :key="`editor-${group.value}`">
                    <div class="rounded-3xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950/60">
                      <div class="px-2 text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ group.label }}</div>
                      <div class="mt-3 space-y-2">
                        <button
                          v-for="block in group.blocks"
                          :key="`editor-layer-${block.id}`"
                          type="button"
                          class="w-full rounded-2xl border px-3 py-3 text-left transition"
                          :class="selectedCanvasBlockId === block.id
                            ? 'border-[#d9b05f] bg-[#f8f0dd] text-[#143d37] dark:border-[#d9b05f]/70 dark:bg-[#d9b05f]/10 dark:text-[#f7f1e7]'
                            : canvasBlockIsHidden(block)
                              ? 'border-slate-200 bg-slate-50/60 text-slate-400 opacity-70 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-500 dark:hover:border-slate-600'
                              : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600'"
                          @click="selectCanvasBlock(block.id)"
                        >
                          <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold">{{ block.title || 'Bloco sem título' }}</span>
                            <span class="inline-flex items-center gap-1">
                              <EyeSlashIcon v-if="canvasBlockIsHidden(block)" class="h-4 w-4 shrink-0 text-slate-400" />
                              <LockClosedIcon v-if="canvasBlockIsLocked(block)" class="h-4 w-4 shrink-0 text-amber-500" />
                            </span>
                          </div>
                          <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                            <span>{{ blockKindLabel(block.block_kind) }} · z {{ Number(block.z_index || 10) }}</span>
                            <span class="rounded-full bg-white px-2 py-0.5 font-bold text-slate-500 shadow-sm dark:bg-slate-950 dark:text-slate-400">
                              {{ canvasBlockIsHidden(block) ? 'Oculto' : 'Visível' }}
                            </span>
                          </div>
                        </button>
                      </div>
                    </div>
                  </template>
                  <div v-if="!canvasBlocks.length" class="rounded-3xl border border-dashed border-slate-300 p-5 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Adicione um bloco ou insira um snippet como objecto para começar a compor visualmente.
                  </div>
                </div>

                <div v-show="editorRailMode === 'assets'" class="mt-4 space-y-4">
                  <input
                    ref="backgroundUploadInput"
                    type="file"
                    class="hidden"
                    accept="image/svg+xml,image/png,image/jpeg,image/webp,image/gif,image/avif"
                    @change="handleBackgroundUploadInput"
                  />
                  <button
                    type="button"
                    class="group w-full rounded-[1.8rem] border border-dashed p-5 text-left transition"
                    :class="backgroundUploadDragging
                      ? 'border-primary-500 bg-primary-50 dark:border-primary-300 dark:bg-primary-500/10'
                      : 'border-slate-300 bg-white hover:border-primary-400 hover:bg-primary-50/60 dark:border-slate-700 dark:bg-slate-950/60 dark:hover:border-primary-500/60 dark:hover:bg-primary-500/10'"
                    @click="pickBackgroundUpload"
                    @dragenter.prevent="backgroundUploadDragging = true"
                    @dragover.prevent="backgroundUploadDragging = true"
                    @dragleave.prevent="backgroundUploadDragging = false"
                    @drop.prevent="handleBackgroundDrop"
                  >
                    <div class="flex items-start gap-3">
                      <div class="rounded-2xl bg-primary-900 p-3 text-white shadow-lg shadow-primary-950/10 dark:bg-primary-500">
                        <PhotoIcon class="h-5 w-5" />
                      </div>
                      <div>
                        <div class="text-sm font-black text-slate-950 dark:text-white">{{ studioCopy('background_upload.title') }}</div>
                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ studioCopy('background_upload.description') }}</p>
                      </div>
                    </div>
                    <div v-if="backgroundUploadBusy" class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                      <div class="h-full rounded-full bg-primary-700 transition-all dark:bg-primary-400" :style="{ width: `${backgroundUploadProgress}%` }" />
                    </div>
                    <p v-if="backgroundUploadError" class="mt-3 text-xs font-semibold text-red-600 dark:text-red-300">{{ backgroundUploadError }}</p>
                  </button>

                  <button
                    type="button"
                    @click="openMediaPicker('document-background')"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-900 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-100"
                  >
                    <PaintBrushIcon class="h-4 w-4" />
                    {{ studioCopy('background_upload.choose_from_gallery') }}
                  </button>

                  <button
                    type="button"
                    @click="openMediaPicker('new-canvas-block', 'image_url', { blockKind: 'image' })"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
                  >
                    <PhotoIcon class="h-4 w-4" />
                    Criar camada com media
                  </button>

                  <div v-if="localAssetLibrary.length" class="grid grid-cols-2 gap-3">
                    <div
                      v-for="asset in localAssetLibrary.slice(0, 6)"
                      :key="`editor-asset-${asset.id}`"
                      class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-left transition hover:-translate-y-0.5 hover:border-primary-300 dark:border-slate-800 dark:bg-slate-900"
                    >
                      <div class="flex h-24 items-center justify-center bg-slate-100 dark:bg-slate-950">
                        <img :src="asset.url" :alt="asset.label" class="h-full w-full object-contain p-2" />
                      </div>
                      <div class="truncate px-3 py-2 text-[11px] font-semibold text-slate-700 dark:text-slate-300">{{ asset.label }}</div>
                      <div class="grid grid-cols-3 gap-1 px-2 pb-2">
                        <button
                          type="button"
                          class="rounded-xl bg-primary-900 px-2 py-1.5 text-[10px] font-black uppercase tracking-[0.1em] text-white transition hover:bg-primary-800 dark:bg-primary-600"
                          @click="applyAssetToCanvasTarget(asset, 'document-background')"
                        >
                          Fundo
                        </button>
                        <button
                          type="button"
                          class="rounded-xl border border-primary-200 bg-primary-50 px-2 py-1.5 text-[10px] font-black uppercase tracking-[0.1em] text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-100"
                          @click="applyAssetToCanvasTarget(asset, 'new-canvas-block')"
                        >
                          Camada
                        </button>
                        <button
                          type="button"
                          class="rounded-xl border border-slate-200 px-2 py-1.5 text-[10px] font-black uppercase tracking-[0.1em] text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                          :disabled="!selectedCanvasBlock"
                          @click="applyAssetToCanvasTarget(asset, 'selected-block-media')"
                        >
                          Bloco
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </aside>

              <section class="relative overflow-auto bg-[radial-gradient(circle_at_top_left,rgba(20,61,55,0.12),transparent_34%),linear-gradient(135deg,#f7f1e7,#ebe2d2)] p-4 dark:bg-none dark:bg-slate-950 sm:p-6 xl:p-8">
                <div class="mx-auto flex max-w-6xl flex-col gap-4">
                  <div class="studio-canvas-toolbar">
                    <div class="min-w-0">
                      <div class="text-[10px] font-black uppercase tracking-[0.24em] text-[#6b7b74] dark:text-[#a9bcb2]">Prancheta activa</div>
                      <div class="mt-1 truncate text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ previewPageSummary }}</div>
                      <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-bold text-[#6b7b74] dark:text-[#a9bcb2]">
                        <span class="studio-toolbar-status">{{ previewPageFormatLabel }}</span>
                        <span class="studio-toolbar-status">{{ visibleCanvasBlocks.length }} objecto{{ visibleCanvasBlocks.length === 1 ? '' : 's' }} {{ visibleCanvasBlocks.length === 1 ? 'visível' : 'visíveis' }}</span>
                      </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                      <details class="studio-toolbar-menu relative">
                        <summary class="studio-toolbar-button studio-toolbar-button--primary">
                          <RectangleGroupIcon class="h-4 w-4" />
                          Adicionar
                        </summary>
                        <div class="studio-toolbar-menu-panel">
                          <div class="px-2 pb-2 text-[10px] font-black uppercase tracking-[0.2em] text-[#6b7b74] dark:text-[#a9bcb2]">Novo objecto</div>
                          <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="studio-toolbar-menu-action" @click="addTextCanvasBlock">
                              <SparklesIcon class="h-4 w-4" />
                              Texto
                            </button>
                            <button type="button" class="studio-toolbar-menu-action" @click="addCalloutCanvasBlock">
                              <RectangleGroupIcon class="h-4 w-4" />
                              Destaque
                            </button>
                            <button type="button" class="studio-toolbar-menu-action" @click="addImageCanvasBlock">
                              <PhotoIcon class="h-4 w-4" />
                              Imagem
                            </button>
                            <button type="button" class="studio-toolbar-menu-action" @click="addSignatureCanvasBlock('lab')">
                              <DocumentDuplicateIcon class="h-4 w-4" />
                              Assinatura
                            </button>
                            <button type="button" class="studio-toolbar-menu-action" @click="addStampCanvasBlock">
                              <PaintBrushIcon class="h-4 w-4" />
                              Carimbo
                            </button>
                            <button type="button" class="studio-toolbar-menu-action" @click="addQrCanvasBlock">
                              <RectangleGroupIcon class="h-4 w-4" />
                              Código QR
                            </button>
                          </div>
                        </div>
                      </details>

                      <div v-if="previewPages.length > 1" class="studio-toolbar-group">
                        <button
                          type="button"
                          class="studio-toolbar-icon-button"
                          title="Página anterior"
                          :disabled="currentPreviewPage <= 1"
                          @click="stepPreviewPage(-1)"
                        >
                          ‹
                        </button>
                        <span class="min-w-14 px-1 text-center text-[11px] font-black tracking-[0.08em] text-[#475a53] dark:text-[#cbd8cf]">{{ currentPreviewPage }}/{{ previewPages.length }}</span>
                        <button
                          type="button"
                          class="studio-toolbar-icon-button"
                          title="Página seguinte"
                          :disabled="currentPreviewPage >= previewPages.length"
                          @click="stepPreviewPage(1)"
                        >
                          ›
                        </button>
                      </div>

                      <label class="studio-toolbar-group gap-2 pl-3 text-[10px] font-black uppercase tracking-[0.14em] text-[#6b7b74] dark:text-[#a9bcb2]">
                        Zoom
                        <select v-model.number="canvasZoom" class="studio-toolbar-select">
                          <option v-for="option in canvasZoomOptions" :key="`zoom-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>

                      <details class="studio-toolbar-menu relative">
                        <summary class="studio-toolbar-button">
                          <EyeIcon class="h-4 w-4" />
                          Vista
                        </summary>
                        <div class="studio-toolbar-menu-panel studio-toolbar-menu-panel--compact">
                          <div class="px-1 pb-2 text-[10px] font-black uppercase tracking-[0.2em] text-[#6b7b74] dark:text-[#a9bcb2]">Assistentes do canvas</div>
                          <label class="studio-view-option">
                            <input v-model="showCanvasGrid" type="checkbox" />
                            <span>Mostrar grelha</span>
                          </label>
                          <label class="studio-view-option">
                            <input v-model="showCanvasRulers" type="checkbox" />
                            <span>Mostrar réguas</span>
                          </label>
                          <label class="studio-view-option">
                            <input v-model="showSafeArea" type="checkbox" />
                            <span>Mostrar área segura</span>
                          </label>
                          <label class="studio-view-option">
                            <input v-model="snapToGrid" type="checkbox" />
                            <span>Alinhamento inteligente</span>
                          </label>
                        </div>
                      </details>
                    </div>
                  </div>

                  <div v-if="selectedCanvasBlock" class="studio-context-bar">
                    <div class="min-w-0">
                      <div class="truncate text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ selectedCanvasBlock.title || 'Objecto sem título' }}</div>
                      <div class="mt-1 flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-[#6b7b74] dark:text-[#a9bcb2]">
                        <span>{{ selectedCanvasBlockKindLabel }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ blockSurfaceLabel(selectedCanvasBlock.surface) }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ Number(selectedCanvasBlock.x || 0) }}% × {{ Number(selectedCanvasBlock.y || 0) }}% · {{ Number(selectedCanvasBlock.width || 0) }}% largura</span>
                      </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1">
                      <button type="button" class="studio-context-action" :title="canvasBlockIsHidden(selectedCanvasBlock) ? 'Mostrar objecto' : 'Ocultar objecto'" @click="toggleSelectedCanvasBlockVisibility()">
                        <EyeIcon v-if="canvasBlockIsHidden(selectedCanvasBlock)" class="h-4 w-4" />
                        <EyeSlashIcon v-else class="h-4 w-4" />
                      </button>
                      <button type="button" class="studio-context-action" :title="canvasBlockIsLocked(selectedCanvasBlock) ? 'Desbloquear objecto' : 'Bloquear objecto'" @click="toggleSelectedCanvasBlockLock()">
                        <LockClosedIcon v-if="canvasBlockIsLocked(selectedCanvasBlock)" class="h-4 w-4" />
                        <LockOpenIcon v-else class="h-4 w-4" />
                      </button>
                      <button type="button" class="studio-context-action studio-context-action--text" title="Enviar uma posição para trás" @click="shiftSelectedCanvasBlockLayer(-1)">Atrás</button>
                      <button type="button" class="studio-context-action studio-context-action--text" title="Trazer uma posição para a frente" @click="shiftSelectedCanvasBlockLayer(1)">Frente</button>
                      <button type="button" class="studio-context-action" title="Duplicar objecto" @click="duplicateCanvasBlock(selectedCanvasBlock)">
                        <DocumentDuplicateIcon class="h-4 w-4" />
                      </button>
                      <button type="button" class="studio-context-action studio-context-action--danger" title="Apagar objecto" @click="removeCanvasBlock(selectedCanvasBlock.id)">
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>

                  <div
                    class="studio-preview-document relative mx-auto w-full rounded-[32px] border border-slate-200 bg-white shadow-2xl shadow-primary-950/15 dark:border-slate-700 dark:bg-slate-900"
                    :style="editorPageFrameStyle"
                  >
                    <div v-if="showCanvasRulers" class="pointer-events-none absolute left-10 right-10 top-10 z-20 h-5 border-t border-slate-200/80 dark:border-slate-700/80">
                      <span
                        v-for="mark in canvasRulerMarks"
                        :key="`top-ruler-${mark}`"
                        class="absolute top-0 flex -translate-x-1/2 flex-col items-center gap-0.5 text-[9px] font-bold text-slate-400 dark:text-slate-500"
                        :style="{ left: `${mark}%` }"
                      >
                        <span class="h-2 w-px bg-slate-300 dark:bg-slate-600" />
                        {{ mark }}%
                      </span>
                    </div>
                    <div v-if="showCanvasRulers" class="pointer-events-none absolute bottom-10 left-8 top-16 z-20 w-5 border-l border-slate-200/80 dark:border-slate-700/80">
                      <span
                        v-for="mark in canvasRulerMarks"
                        :key="`left-ruler-${mark}`"
                        class="absolute left-0 flex -translate-y-1/2 items-center gap-1 text-[9px] font-bold text-slate-400 dark:text-slate-500"
                        :style="{ top: `${mark}%` }"
                      >
                        <span class="h-px w-2 bg-slate-300 dark:bg-slate-600" />
                        {{ mark }}%
                      </span>
                    </div>
                    <div class="pointer-events-none absolute left-5 right-5 top-4 z-20 flex items-center justify-between text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
                      <span>{{ editorCanvasPage.pageNumber === 1 ? 'Primeira página' : 'Página contínua' }}</span>
                      <span>{{ editorCanvasPage.pageNumber }}/{{ previewPages.length || 1 }}</span>
                    </div>
                    <div
                      v-if="showSafeArea"
                      class="pointer-events-none absolute rounded-[26px] border border-dashed border-primary-300/70 dark:border-primary-500/40"
                      :style="previewMarginStyleForPage(editorCanvasPage.pageNumber)"
                    />
                    <div
                      v-for="guideX in alignmentGuides.x"
                      :key="`editor-guide-x-${guideX}`"
                      class="pointer-events-none absolute top-8 bottom-8 z-10 w-px bg-primary-400/70 dark:bg-primary-300/60 xl:top-10 xl:bottom-10"
                      :style="{ left: `${guideX}%` }"
                    />
                    <div
                      v-for="guideY in alignmentGuides.y"
                      :key="`editor-guide-y-${guideY}`"
                      class="pointer-events-none absolute left-8 right-8 z-10 h-px bg-primary-400/70 dark:bg-primary-300/60 xl:left-10 xl:right-10"
                      :style="{ top: `${guideY}%` }"
                    />
                    <div class="absolute z-0 flex flex-col gap-5 overflow-hidden rounded-[22px]" :style="previewContentPaddingStyleForPage(editorCanvasPage.pageNumber)">
                      <div data-canvas-surface="header" class="relative rounded-2xl border border-dashed border-slate-300 bg-white/80 p-5 dark:border-slate-600 dark:bg-slate-900/70" :style="[previewGridStyle, { minHeight: '104px' }]">
                        <div class="pointer-events-none absolute right-4 top-3 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.14em] text-slate-400 shadow-sm dark:bg-slate-950/80 dark:text-slate-500">Cabeçalho</div>
                        <div v-html="previewHeaderHtmlForPage(editorCanvasPage.pageNumber)" />
                        <div
                          v-for="block in previewHeaderBlocksForPage(editorCanvasPage.pageNumber)"
                          :key="`editor-header-${block.id}`"
                          class="group overflow-hidden border border-white/40 transition"
                          :class="selectedCanvasBlockId === block.id ? 'ring-2 ring-primary-400/80' : 'hover:ring-2 hover:ring-primary-300/70'"
                          :style="canvasBlockStyle(block)"
                          @click.stop="selectCanvasBlock(block.id)"
                        >
                          <div v-if="canvasBlockOverlayStyle(block)" :style="canvasBlockOverlayStyle(block)" />
                          <button
                            type="button"
                            class="absolute left-2 top-2 z-20 inline-flex items-center rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                            :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-700/90' : 'cursor-move'"
                            @mousedown="startDrag($event, block)"
                          >
                            {{ canvasBlockIsLocked(block) ? 'bloqueado' : 'mover' }}
                          </button>
                          <div class="relative z-10" v-html="canvasBlockContentHtml(block)" />
                          <button
                            type="button"
                            class="absolute bottom-2 right-2 z-20 h-4 w-4 rounded-full border border-white/70 bg-primary-500 opacity-0 shadow transition group-hover:opacity-100"
                            :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-500' : 'cursor-se-resize'"
                            @mousedown="startResize($event, block)"
                          />
                        </div>
                      </div>
                      <div data-canvas-surface="content" class="relative flex-1 rounded-2xl border border-slate-200 bg-white/85 p-6 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/80 xl:p-7" :style="previewGridStyle">
                        <div class="pointer-events-none absolute right-4 top-3 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.14em] text-slate-400 shadow-sm dark:bg-slate-950/80 dark:text-slate-500">Corpo</div>
                        <div class="prose max-w-none dark:prose-invert" v-html="editorCanvasPage.content" />
                        <div
                          v-for="block in previewContentBlocksForPage(editorCanvasPage.pageNumber)"
                          :key="`editor-content-${block.id}`"
                          class="group overflow-hidden border border-white/40 transition"
                          :class="selectedCanvasBlockId === block.id ? 'ring-2 ring-primary-400/80' : 'hover:ring-2 hover:ring-primary-300/70'"
                          :style="canvasBlockStyle(block)"
                          @click.stop="selectCanvasBlock(block.id)"
                        >
                          <div v-if="canvasBlockOverlayStyle(block)" :style="canvasBlockOverlayStyle(block)" />
                          <button
                            type="button"
                            class="absolute left-2 top-2 z-20 inline-flex items-center rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                            :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-700/90' : 'cursor-move'"
                            @mousedown="startDrag($event, block)"
                          >
                            {{ canvasBlockIsLocked(block) ? 'bloqueado' : 'mover' }}
                          </button>
                          <div class="relative z-10" v-html="canvasBlockContentHtml(block)" />
                          <button
                            type="button"
                            class="absolute bottom-2 right-2 z-20 h-4 w-4 rounded-full border border-white/70 bg-primary-500 opacity-0 shadow transition group-hover:opacity-100"
                            :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-500' : 'cursor-se-resize'"
                            @mousedown="startResize($event, block)"
                          />
                        </div>
                      </div>
                      <div data-canvas-surface="footer" class="relative rounded-2xl border border-dashed border-slate-300 bg-white/80 p-5 text-xs dark:border-slate-600 dark:bg-slate-900/70" :style="[previewGridStyle, { minHeight: '96px' }]">
                        <div class="pointer-events-none absolute right-4 top-3 z-10 rounded-full bg-white/90 px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.14em] text-slate-400 shadow-sm dark:bg-slate-950/80 dark:text-slate-500">Rodapé</div>
                        <div v-html="previewFooterHtmlForPage(editorCanvasPage.pageNumber, previewPages.length || 1)" />
                        <div
                          v-for="block in previewFooterBlocks"
                          :key="`editor-footer-${block.id}`"
                          class="group overflow-hidden border border-white/40 transition"
                          :class="selectedCanvasBlockId === block.id ? 'ring-2 ring-primary-400/80' : 'hover:ring-2 hover:ring-primary-300/70'"
                          :style="canvasBlockStyle(block)"
                          @click.stop="selectCanvasBlock(block.id)"
                        >
                          <div v-if="canvasBlockOverlayStyle(block)" :style="canvasBlockOverlayStyle(block)" />
                          <button
                            type="button"
                            class="absolute left-2 top-2 z-20 inline-flex items-center rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                            :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-700/90' : 'cursor-move'"
                            @mousedown="startDrag($event, block)"
                          >
                            {{ canvasBlockIsLocked(block) ? 'bloqueado' : 'mover' }}
                          </button>
                          <div class="relative z-10" v-html="canvasBlockContentHtml(block)" />
                          <button
                            type="button"
                            class="absolute bottom-2 right-2 z-20 h-4 w-4 rounded-full border border-white/70 bg-primary-500 opacity-0 shadow transition group-hover:opacity-100"
                            :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-500' : 'cursor-se-resize'"
                            @mousedown="startResize($event, block)"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <aside class="border-t border-slate-200 bg-white/85 p-4 dark:border-slate-800 dark:bg-slate-900/85 2xl:border-l 2xl:border-t-0">
                <div class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950/60">
                  <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Documento</div>
                  <label class="mt-4 block text-sm font-semibold text-slate-800 dark:text-slate-200">Fonte editorial
                    <select v-model="props.layoutSchema.document_font_family" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in studioFontOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    {{ studioFontOptions.find((option) => option.value === documentFontFamily)?.description || 'Fonte usada no preview e persistida no CSS do PDF.' }}
                  </p>
                  <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Fundo activo</div>
                    <div class="mt-2 truncate text-xs font-semibold text-slate-700 dark:text-slate-300">{{ props.layoutSchema.background_image_path || 'Nenhum fundo definido' }}</div>
                    <div v-if="props.layoutSchema.background_image_path" class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                      <div
                        class="relative h-32 cursor-crosshair overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-900"
                        title="Arraste o ponto para definir o foco do fundo"
                        @pointerdown="startDocumentBackgroundFocalDrag"
                      >
                        <img
                          :src="props.layoutSchema.background_image_path"
                          alt="Fundo do documento"
                          class="h-full w-full select-none"
                          draggable="false"
                          :style="{ objectFit: mediaObjectFit(props.layoutSchema.background_size || 'cover'), objectPosition: props.layoutSchema.background_position || 'center center' }"
                        />
                        <span class="pointer-events-none absolute h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary-600 shadow-lg ring-2 ring-primary-900/20" :style="documentBackgroundFocalPointStyle()" />
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 grid gap-3">
                    <label class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Ajuste
                      <select v-model="props.layoutSchema.background_size" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm normal-case tracking-normal text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option v-for="option in backgroundFitOptions" :key="`inspector-fit-${option.value}`" :value="option.value">{{ option.label }}</option>
                      </select>
                    </label>
                    <label class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Posição
                      <select v-model="props.layoutSchema.background_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm normal-case tracking-normal text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                        <option v-for="option in backgroundPositionOptions" :key="`inspector-position-${option.value}`" :value="option.value">{{ option.label }}</option>
                      </select>
                    </label>
                    <div v-if="props.layoutSchema.background_image_path" class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                      <label class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Foco horizontal
                        <input :value="imagePositionCoordinates(props.layoutSchema.background_position).x" type="range" min="0" max="100" step="1" class="mt-1 w-full accent-primary-700" @input="setDocumentBackgroundFocalCoordinate('x', $event.target.value)" />
                      </label>
                      <label class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Foco vertical
                        <input :value="imagePositionCoordinates(props.layoutSchema.background_position).y" type="range" min="0" max="100" step="1" class="mt-1 w-full accent-primary-700" @input="setDocumentBackgroundFocalCoordinate('y', $event.target.value)" />
                      </label>
                    </div>
                    <button
                      v-if="props.layoutSchema.background_image_path"
                      type="button"
                      class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-950/30"
                      @click="props.layoutSchema.background_image_path = ''; mediaAssetUrl = ''"
                    >
                      Remover fundo
                    </button>
                  </div>
                </div>

                <div class="mt-4 rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950/60">
                  <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Inspector</div>
                  <div v-if="selectedCanvasBlock" class="mt-4 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                      <input
                        v-model="selectedCanvasBlock.title"
                        type="text"
                        class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm font-black text-slate-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                      />
                      <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ blockSurfaceLabel(selectedCanvasBlock.surface) }} · {{ selectedCanvasBlockKindLabel }}</div>
                    </div>
                    <div class="grid grid-cols-4 gap-1 rounded-full border border-slate-200 bg-slate-50 p-1 text-[11px] font-black dark:border-slate-800 dark:bg-slate-900">
                      <button
                        v-for="mode in editorInspectorModes"
                        :key="`inspector-mode-${mode.value}`"
                        type="button"
                        class="rounded-full px-2 py-2 transition"
                        :class="editorInspectorMode === mode.value
                          ? 'bg-[#143d37] text-white shadow-sm dark:bg-[#d9b05f] dark:text-[#07110f]'
                          : 'text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white'"
                        @click="editorInspectorMode = mode.value"
                      >
                        {{ mode.label }}
                      </button>
                    </div>
                    <div v-show="editorInspectorMode === 'layout'" class="grid grid-cols-2 gap-2 text-xs">
                      <button type="button" @click="alignCanvasBlock('left')" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">Esquerda</button>
                      <button type="button" @click="alignCanvasBlock('center')" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">Centro</button>
                      <button type="button" @click="applyCanvasWidth('full')" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">Largura total</button>
                      <button type="button" @click="toggleSelectedCanvasBlockLock()" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">{{ canvasBlockIsLocked(selectedCanvasBlock) ? 'Desbloquear' : 'Bloquear' }}</button>
                      <button type="button" @click="toggleSelectedCanvasBlockVisibility()" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">
                        <EyeIcon v-if="canvasBlockIsHidden(selectedCanvasBlock)" class="h-4 w-4" />
                        <EyeSlashIcon v-else class="h-4 w-4" />
                        {{ canvasBlockIsHidden(selectedCanvasBlock) ? 'Mostrar' : 'Ocultar' }}
                      </button>
                      <button type="button" @click="shiftSelectedCanvasBlockLayer(-1)" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">Atrás</button>
                      <button type="button" @click="shiftSelectedCanvasBlockLayer(1)" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">À frente</button>
                      <button type="button" @click="duplicateCanvasBlock(selectedCanvasBlock)" class="rounded-2xl border border-slate-200 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">Duplicar</button>
                      <button type="button" @click="removeCanvasBlock(selectedCanvasBlock.id)" class="rounded-2xl border border-red-200 px-3 py-2 font-semibold text-red-700 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-950/30">Apagar</button>
                    </div>
                    <details v-show="editorInspectorMode === 'layout'" class="studio-advanced-panel">
                      <summary>Medidas exactas</summary>
                      <div class="mt-3 grid grid-cols-2 gap-2">
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">X
                          <input v-model.number="selectedCanvasBlock.x" type="number" min="0" max="100" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Y
                          <input v-model.number="selectedCanvasBlock.y" type="number" min="0" max="100" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Largura
                          <input v-model.number="selectedCanvasBlock.width" type="number" min="1" max="100" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Altura
                          <input v-model.number="selectedCanvasBlock.min_height" type="number" min="0" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                      </div>
                    </details>
                    <div v-if="['image', 'stamp'].includes(selectedCanvasBlock.block_kind)" v-show="editorInspectorMode === 'media'" class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                      <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Imagem / recorte</div>
                      <div class="mt-3 grid gap-2">
                        <select v-model="selectedCanvasBlock.image_fit" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                          <option v-for="option in backgroundFitOptions" :key="`selected-image-fit-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                        <select v-model="selectedCanvasBlock.image_position" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                          <option v-for="option in imagePositionOptions" :key="`selected-image-position-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </div>
                      <div v-if="selectedCanvasBlock.image_url || selectedCanvasBlock.background_image" class="mt-3 overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950">
                        <div
                          class="relative h-28 cursor-crosshair overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-900"
                          title="Arraste o ponto para ajustar o recorte da imagem"
                          @pointerdown="startImageFocalDrag(selectedCanvasBlock, $event)"
                        >
                          <img
                            :src="selectedCanvasBlock.image_url || selectedCanvasBlock.background_image"
                            :alt="selectedCanvasBlock.image_alt || selectedCanvasBlock.title || 'Imagem'"
                            class="h-full w-full select-none"
                            draggable="false"
                            :style="{ objectFit: imageObjectFit(selectedCanvasBlock), objectPosition: imageObjectPosition(selectedCanvasBlock) }"
                          />
                          <span class="pointer-events-none absolute h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary-600 shadow-lg ring-2 ring-primary-900/20" :style="imageFocalPointStyle(selectedCanvasBlock)" />
                        </div>
                        <div class="mt-3 grid gap-2">
                          <label class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Foco horizontal
                            <input :value="imagePositionCoordinates(selectedCanvasBlock.image_position).x" type="range" min="0" max="100" step="1" class="mt-1 w-full accent-primary-700" @input="setSelectedImageFocalCoordinate('x', $event.target.value)" />
                          </label>
                          <label class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Foco vertical
                            <input :value="imagePositionCoordinates(selectedCanvasBlock.image_position).y" type="range" min="0" max="100" step="1" class="mt-1 w-full accent-primary-700" @input="setSelectedImageFocalCoordinate('y', $event.target.value)" />
                          </label>
                        </div>
                      </div>
                    </div>
                    <button v-show="editorInspectorMode === 'media'" type="button" @click="openSelectedBlockMediaPicker()" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500">
                      <PhotoIcon class="h-4 w-4" />
                      Aplicar media ao bloco
                    </button>

                    <div v-show="editorInspectorMode === 'layout'" class="grid grid-cols-2 gap-2">
                      <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Tipo
                        <select v-model="selectedCanvasBlock.block_kind" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in blockKindOptions" :key="`inspector-kind-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Superfície
                        <select v-model="selectedCanvasBlock.surface" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in canvasSurfaceOptions" :key="`inspector-surface-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label v-if="selectedCanvasBlock.surface === 'content'" class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Âmbito
                        <select v-model="selectedCanvasBlock.page_scope" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in canvasContentScopeOptions" :key="`inspector-scope-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label v-if="selectedCanvasBlock.surface === 'content' && selectedCanvasBlock.page_scope === 'specific'" class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Página
                        <input v-model.number="selectedCanvasBlock.page_number" type="number" min="1" max="999" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                      </label>
                    </div>

                    <div v-show="editorInspectorMode === 'style'" class="space-y-3">
                      <div class="grid grid-cols-2 gap-2">
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Padding
                          <input v-model.number="selectedCanvasBlock.padding" type="number" min="0" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Raio
                          <input v-model.number="selectedCanvasBlock.border_radius" type="number" min="0" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Borda
                          <input v-model.number="selectedCanvasBlock.border_width" type="number" min="0" max="40" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Opacidade
                          <input v-model.number="selectedCanvasBlock.opacity" type="number" min="0.05" max="1" step="0.05" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                      </div>
                      <label class="block text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Cor de fundo
                        <div class="mt-1 flex gap-2">
                          <input :value="colorInputValue(selectedCanvasBlock.background_color, '#ffffff')" type="color" @input="setSelectedBlockColor('background_color', $event.target.value)" class="h-11 w-12 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                          <input v-model="selectedCanvasBlock.background_color" type="text" placeholder="#ffffff ou rgba(...)" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </div>
                      </label>
                      <label class="block text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Cor do texto
                        <div class="mt-1 flex gap-2">
                          <input :value="colorInputValue(selectedCanvasBlock.text_color, '#0f172a')" type="color" @input="setSelectedBlockColor('text_color', $event.target.value)" class="h-11 w-12 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                          <input v-model="selectedCanvasBlock.text_color" type="text" placeholder="#0f172a" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </div>
                      </label>
                      <label class="block text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Cor da borda
                        <div class="mt-1 flex gap-2">
                          <input :value="colorInputValue(selectedCanvasBlock.border_color, '#94a3b8')" type="color" @input="setSelectedBlockColor('border_color', $event.target.value)" class="h-11 w-12 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                          <input v-model="selectedCanvasBlock.border_color" type="text" placeholder="#94a3b8 ou rgba(...)" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </div>
                      </label>
                      <div class="grid grid-cols-2 gap-2">
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Texto
                          <input v-model.number="selectedCanvasBlock.font_size" type="number" min="8" max="72" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                        <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Entrelinha
                          <input v-model.number="selectedCanvasBlock.line_height" type="number" min="0.8" max="3" step="0.1" class="mt-1 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm normal-case tracking-normal text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </label>
                      </div>
                    </div>

                    <div v-show="editorInspectorMode === 'content'" class="space-y-3">
                      <textarea
                        v-if="selectedCanvasBlock.block_kind === 'rich_text'"
                        v-model="selectedCanvasBlock.content_html"
                        rows="8"
                        class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        placeholder="<p>Conteúdo do bloco...</p>"
                      />
                      <div v-if="selectedCanvasBlock.block_kind === 'qr_code'" class="space-y-2">
                        <textarea v-model="selectedCanvasBlock.qr_content" rows="4" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="{{document_code}} · {{customer_name}}" />
                        <input v-model="selectedCanvasBlock.qr_label" type="text" placeholder="Legenda do QR" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        <div class="grid grid-cols-2 gap-2">
                          <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">QR
                            <input :value="colorInputValue(selectedCanvasBlock.qr_foreground_color, '#0f172a')" type="color" @input="setSelectedBlockColor('qr_foreground_color', $event.target.value)" class="mt-1 h-10 w-full rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                          </label>
                          <label class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Fundo
                            <input :value="colorInputValue(selectedCanvasBlock.qr_background_color, '#ffffff')" type="color" @input="setSelectedBlockColor('qr_background_color', $event.target.value)" class="mt-1 h-10 w-full rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                          </label>
                        </div>
                      </div>
                      <div v-if="selectedCanvasBlock.block_kind === 'signature'" class="space-y-2">
                        <input v-model="selectedCanvasBlock.signature_label" type="text" placeholder="Legenda da assinatura" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        <input v-model="selectedCanvasBlock.signature_name" type="text" placeholder="Nome / variável" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        <input v-model="selectedCanvasBlock.signature_title" type="text" placeholder="Cargo / função" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                      </div>
                      <div v-if="selectedCanvasBlock.block_kind === 'chart_snapshot'" class="space-y-2">
                        <input v-model="selectedCanvasBlock.chart_title" type="text" placeholder="Título do gráfico" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        <select v-model="selectedCanvasBlock.chart_type" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in chartTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <textarea v-model="selectedCanvasBlock.chart_labels" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="Recepção, Validação, Emissão" />
                        <textarea v-model="selectedCanvasBlock.chart_values" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="18, 12, 9" />
                        <textarea v-model="selectedCanvasBlock.chart_caption" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="Legenda ou leitura executiva do gráfico" />
                        <details class="studio-advanced-panel">
                          <summary>SVG avançado do gráfico</summary>
                          <textarea v-model="selectedCanvasBlock.chart_svg" rows="5" class="mt-3 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-3 font-mono text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="Cole aqui um SVG exportado do ApexCharts" />
                        </details>
                      </div>
                    </div>

                    <div v-show="editorInspectorMode === 'media'" class="space-y-3">
                      <button type="button" @click="openSelectedBlockMediaPicker()" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500">
                        <PhotoIcon class="h-4 w-4" />
                        Aplicar {{ mediaFieldLabel(selectedBlockDefaultMediaField()) }}
                      </button>
                      <button type="button" @click="openSelectedBlockMediaPicker('background_image')" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-900 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-100">
                        <PaintBrushIcon class="h-4 w-4" />
                        Aplicar fundo ao bloco
                      </button>
                      <div v-if="selectedCanvasBlock.image_url || selectedCanvasBlock.signature_image || selectedCanvasBlock.chart_image_url || selectedCanvasBlock.background_image" class="grid grid-cols-2 gap-2">
                        <div v-if="selectedCanvasBlock.image_url" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                          <img :src="selectedCanvasBlock.image_url" alt="Imagem principal" class="h-28 w-full object-contain p-2" />
                        </div>
                        <div v-if="selectedCanvasBlock.signature_image" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                          <img :src="selectedCanvasBlock.signature_image" alt="Assinatura" class="h-28 w-full object-contain p-2" />
                        </div>
                        <div v-if="selectedCanvasBlock.chart_image_url" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                          <img :src="selectedCanvasBlock.chart_image_url" alt="Gráfico" class="h-28 w-full object-contain p-2" />
                        </div>
                        <div v-if="selectedCanvasBlock.background_image" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                          <img :src="selectedCanvasBlock.background_image" alt="Fundo do bloco" class="h-28 w-full object-contain p-2" />
                        </div>
                      </div>
                      <details class="studio-advanced-panel">
                        <summary>Origem avançada da media</summary>
                        <div class="mt-3 space-y-2">
                          <input v-model="selectedCanvasBlock.image_url" type="text" placeholder="URL da imagem principal" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                          <input v-model="selectedCanvasBlock.background_image" type="text" placeholder="URL do fundo do bloco" class="block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                        </div>
                      </details>
                    </div>
                  </div>
                  <p v-else class="mt-4 text-sm leading-6 text-slate-500 dark:text-slate-400">Selecione um objecto no canvas ou na lista para editar posição, media, assinatura e aparência.</p>
                </div>
              </aside>
            </div>
          </div>

          <details class="studio-composition-drawer mt-6" @toggle="syncAdvancedCompositionState">
            <summary>
              <span class="min-w-0">
                <span class="studio-composition-drawer__eyebrow">{{ studioCopy('advanced_composition.eyebrow', {}, 'Ferramentas avançadas') }}</span>
                <span class="studio-composition-drawer__title">{{ studioCopy('advanced_composition.title', {}, 'Biblioteca, variáveis e edição técnica') }}</span>
                <span class="studio-composition-drawer__description">
                  {{ studioCopy('advanced_composition.description', {}, 'Use esta área apenas quando precisar de snippets HTML, variáveis rápidas ou ajustes técnicos fora do canvas principal.') }}
                </span>
              </span>
              <span class="studio-composition-drawer__action">
                {{ advancedCompositionOpen ? studioCopy('advanced_composition.close', {}, 'Fechar') : studioCopy('advanced_composition.action', {}, 'Abrir') }}
              </span>
            </summary>

            <div class="studio-composition-drawer__content">
          <swiper-container
            class="report-studio-swiper mt-5 block"
            :slides-per-view="1.05"
            :space-between="16"
            :breakpoints="{ 768: { slidesPerView: 2.1 }, 1280: { slidesPerView: 3.05 } }"
            navigation="true"
            pagination="true"
          >
            <swiper-slide
              v-for="snippet in snippetLibrary"
              :key="snippet.label"
            >
              <div class="min-h-48 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 transition dark:border-slate-700 dark:bg-slate-950/40">
                <div class="flex items-start gap-3">
                  <div class="rounded-2xl bg-primary-50 p-2 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    <RectangleGroupIcon class="h-5 w-5" />
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ snippet.label }}</div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ snippet.description }}</p>
                  </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                  <button
                    type="button"
                    @click="appendSnippet(snippet.html)"
                    class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                  >
                    Inserir no HTML
                  </button>
                  <button
                    type="button"
                    @click="addSnippetAsCanvasBlock(snippet)"
                    class="inline-flex items-center rounded-2xl bg-primary-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
                  >
                    Criar bloco posicionável
                  </button>
                </div>
              </div>
            </swiper-slide>
          </swiper-container>

          <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Blocos de assinatura</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                  Monte assinaturas formais no próprio canvas para laboratório, cliente, validação e aprovação documental.
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  @click="addImageCanvasBlock"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  Imagem livre
                </button>
                <button
                  type="button"
                  @click="addChartSnapshotCanvasBlock"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  Gráfico
                </button>
                <button
                  type="button"
                  @click="addStampCanvasBlock"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  Carimbo
                </button>
                <button
                  type="button"
                  @click="addQrCanvasBlock"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  QR code
                </button>
                <button
                  type="button"
                  @click="addSignatureCanvasBlock('lab')"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  Assinatura laboratório
                </button>
                <button
                  type="button"
                  @click="addSignatureCanvasBlock('client')"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  Aceitação cliente
                </button>
                <button
                  type="button"
                  @click="addSignatureCanvasBlock('approval')"
                  class="inline-flex items-center rounded-2xl bg-primary-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
                >
                  Bloco de validação
                </button>
              </div>
            </div>
            <div v-if="signatureAssets.length" class="mt-4 flex flex-wrap gap-2">
              <button
                v-for="asset in signatureAssets"
                :key="`saved-signature-${asset.id}`"
                type="button"
                @click="addSavedSignatureCanvasBlock(asset)"
                class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"
              >
                Usar assinatura guardada: {{ asset.label }}
              </button>
            </div>
          </div>

          <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto]">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Imagem / ficheiro do layout</label>
              <input
                v-model="mediaAssetUrl"
                type="text"
                placeholder="/storage/report-studios/backgrounds/documento-hero.png"
                class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
              <select
                v-if="localAssetLibrary.length"
                class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                @change="mediaAssetUrl = $event.target.value"
              >
                <option value="">Selecionar da galeria ou assinaturas</option>
                <option v-for="asset in localAssetLibrary" :key="asset.id" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
              </select>
              <button
                type="button"
                @click="openMediaPicker('asset-url')"
                class="mt-2 inline-flex items-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200"
              >
                <PhotoIcon class="h-4 w-4" />
                Abrir media picker
              </button>
            </div>
            <div class="flex flex-wrap items-end gap-3">
              <button
                type="button"
                @click="insertImageSnippet"
                class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
              >
                <PhotoIcon class="h-4 w-4" />
                Inserir imagem
              </button>
              <button
                type="button"
                @click="applyBackgroundImage"
                class="inline-flex items-center gap-2 rounded-2xl bg-primary-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
              >
                <PaintBrushIcon class="h-4 w-4" />
                Usar como fundo
              </button>
            </div>
          </div>

          <div class="mt-6 flex flex-wrap gap-2">
            <button
              v-for="placeholder in translatedPlaceholders"
              :key="placeholder.value"
              type="button"
              @click="insertPlaceholder(placeholder.value)"
              :title="placeholder.value"
              class="rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/40 dark:bg-primary-950/40 dark:text-primary-300"
            >
              <DocumentDuplicateIcon class="mr-1 inline h-3.5 w-3.5" />
              {{ placeholder.label }}
            </button>
          </div>

          <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-950/50">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Blocos posicionáveis</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                  Use blocos para compor faixas, selos, destaques, imagens e overlays por superfície.
                </p>
              </div>
              <button
                type="button"
                @click="addCanvasBlock()"
                class="inline-flex items-center justify-center rounded-2xl bg-primary-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
              >
                Novo bloco
              </button>
            </div>

                <div class="mt-5 grid gap-5 2xl:grid-cols-[minmax(320px,0.82fr)_minmax(0,1.18fr)]">
                  <div class="space-y-3">
                    <template v-for="group in canvasBlockGroups" :key="group.value">
                      <div class="rounded-2xl border border-slate-200 bg-white/90 p-3 dark:border-slate-700 dark:bg-slate-900/60">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                          <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ group.label }}</div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ group.blocks.length }} bloco<span v-if="group.blocks.length !== 1">s</span> nesta superfície</div>
                          </div>
                          <div class="flex flex-wrap gap-2">
                            <button
                              type="button"
                              @click="setSurfaceLockState(group.value, true)"
                              class="rounded-full border border-slate-300 px-3 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                              Bloquear tudo
                            </button>
                            <button
                              type="button"
                              @click="setSurfaceLockState(group.value, false)"
                              class="rounded-full border border-slate-300 px-3 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                              Desbloquear
                            </button>
                          </div>
                        </div>

                        <div class="mt-3 space-y-2">
                          <button
                            v-for="block in group.blocks"
                            :key="block.id"
                            type="button"
                            @click="selectCanvasBlock(block.id)"
                            class="w-full rounded-2xl border px-4 py-3 text-left transition"
                            :class="selectedCanvasBlockId === block.id
                              ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-500/10'
                              : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-slate-600'"
                          >
                            <div class="flex items-start justify-between gap-3">
                              <div>
                                <div class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                                  <LockClosedIcon v-if="canvasBlockIsLocked(block)" class="h-4 w-4 text-amber-500" />
                                  <span>{{ block.title || 'Bloco sem título' }}</span>
                                </div>
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                  {{ blockSurfaceLabel(block.surface) }}
                                  <span v-if="block.surface === 'content'" class="ml-1">· {{ canvasContentScopeOptions.find((option) => option.value === normalizeContentPageScope(block))?.label || 'Primeira página' }}</span>
                                </div>
                              </div>
                              <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                                <div>Ordem {{ Number(block.z_index || 10) }}</div>
                                <div class="mt-1">{{ Number(block.x || 0) }}% / {{ Number(block.y || 0) }}%</div>
                              </div>
                            </div>
                          </button>
                        </div>
                      </div>
                    </template>
                    <div v-if="!canvasBlocks.length" class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                      Ainda não existem blocos posicionáveis neste template.
                    </div>
                  </div>

              <div v-if="selectedCanvasBlock" class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/70">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Editar bloco</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">Posicionamento, âmbito e estilo persistidos no layout.</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="toggleSelectedCanvasBlockLock()"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-300 text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                      <LockClosedIcon v-if="canvasBlockIsLocked(selectedCanvasBlock)" class="h-4 w-4" />
                      <LockOpenIcon v-else class="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      @click="duplicateCanvasBlock(selectedCanvasBlock)"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-300 text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                      <DocumentDuplicateIcon class="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      @click="removeCanvasBlock(selectedCanvasBlock.id)"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-red-200 text-red-600 transition hover:bg-red-50 dark:border-red-900/40 dark:text-red-300 dark:hover:bg-red-950/30"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                  <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Presets rápidos</div>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Aplique composições típicas para hero, selo, sidebar ou callout.</p>
                  <div class="mt-3 grid gap-2 md:grid-cols-2">
                    <button
                      v-for="preset in canvasLayoutPresets"
                      :key="preset.key"
                      type="button"
                      @click="applyCanvasPreset(preset)"
                      class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left transition hover:border-primary-400 hover:bg-primary-50 dark:border-slate-700 dark:bg-slate-900/70 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10"
                    >
                      <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ preset.label }}</div>
                      <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ preset.description }}</div>
                    </button>
                  </div>
                </div>

                <div class="mt-4 rounded-2xl border border-primary-100 bg-primary-50/70 px-4 py-3 text-xs leading-5 text-primary-900 dark:border-primary-900/30 dark:bg-primary-950/30 dark:text-primary-200">
                  Setas movem o bloco seleccionado. <span class="font-semibold">Shift + setas</span> redimensiona. <span class="font-semibold">Ctrl/Cmd + D</span> duplica. <span class="font-semibold">L</span> bloqueia ou desbloqueia. <span class="font-semibold">[</span> e <span class="font-semibold">]</span> ajustam a ordem visual. O snap agora também tenta alinhar com blocos vizinhos da mesma superfície.
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                  <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Alinhamento horizontal</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <button type="button" @click="alignCanvasBlock('left')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Esquerda</button>
                      <button type="button" @click="alignCanvasBlock('center')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Centro</button>
                      <button type="button" @click="alignCanvasBlock('right')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Direita</button>
                    </div>
                  </div>

                  <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Alinhamento vertical</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <button type="button" @click="alignCanvasBlockVertical('top')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Topo</button>
                      <button type="button" @click="alignCanvasBlockVertical('middle')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Meio</button>
                      <button type="button" @click="alignCanvasBlockVertical('bottom')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Base</button>
                    </div>
                  </div>

                  <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Largura rápida</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <button type="button" @click="applyCanvasWidth('full')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Total</button>
                      <button type="button" @click="applyCanvasWidth('wide')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Ampla</button>
                      <button type="button" @click="applyCanvasWidth('half')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">1/2</button>
                      <button type="button" @click="applyCanvasWidth('third')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">1/3</button>
                    </div>
                  </div>

                  <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ordem visual</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <button type="button" @click="shiftSelectedCanvasBlockLayer(-10)" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Muito atrás</button>
                      <button type="button" @click="shiftSelectedCanvasBlockLayer(-1)" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Atrás</button>
                      <button type="button" @click="shiftSelectedCanvasBlockLayer(1)" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">À frente</button>
                      <button type="button" @click="shiftSelectedCanvasBlockLayer(10)" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Muito à frente</button>
                      <button type="button" @click="moveSelectedCanvasBlockToEdge('back')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Enviar para trás</button>
                      <button type="button" @click="moveSelectedCanvasBlockToEdge('front')" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900">Trazer para frente</button>
                    </div>
                  </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Título
                    <input v-model="selectedCanvasBlock.title" type="text" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Tipo de bloco
                    <select v-model="selectedCanvasBlock.block_kind" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="rich_text">Conteúdo livre</option>
                      <option value="image">Imagem</option>
                      <option value="chart_snapshot">Gráfico / captura</option>
                      <option value="stamp">Carimbo / selo</option>
                      <option value="signature">Assinatura</option>
                      <option value="qr_code">QR code</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Superfície
                    <select v-model="selectedCanvasBlock.surface" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in canvasSurfaceOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Posição X (%)
                    <input v-model="selectedCanvasBlock.x" type="number" min="0" max="100" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Posição Y (%)
                    <input v-model="selectedCanvasBlock.y" type="number" min="0" max="100" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Largura (%)
                    <input v-model="selectedCanvasBlock.width" type="number" min="1" max="100" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Altura mínima (px)
                    <input v-model="selectedCanvasBlock.min_height" type="number" min="0" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Padding (px)
                    <input v-model="selectedCanvasBlock.padding" type="number" min="0" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Raio (px)
                    <input v-model="selectedCanvasBlock.border_radius" type="number" min="0" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor de fundo
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.background_color, '#ffffff')" type="color" @input="setSelectedBlockColor('background_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.background_color" type="text" placeholder="#ffffff ou rgba(...)" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Imagem de fundo do bloco
                    <input v-model="selectedCanvasBlock.background_image" type="text" placeholder="/storage/report-studios/blocks/hero-cover.png" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <select v-if="localAssetLibrary.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'background_image')">
                      <option value="">Aplicar ficheiro como fundo do bloco</option>
                      <option v-for="asset in localAssetLibrary" :key="`bg-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'background_image')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Escolher da galeria</button>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Ajuste da imagem
                    <select v-model="selectedCanvasBlock.background_image_fit" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="cover">Cobrir</option>
                      <option value="contain">Conter</option>
                      <option value="auto">Original</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Posição da imagem
                    <select v-model="selectedCanvasBlock.background_image_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="center center">Centro</option>
                      <option value="top center">Topo ao centro</option>
                      <option value="top left">Topo à esquerda</option>
                      <option value="top right">Topo à direita</option>
                      <option value="bottom center">Base ao centro</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor do overlay
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.overlay_color, '#0f172a')" type="color" @input="setSelectedBlockColor('overlay_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.overlay_color" type="text" placeholder="#0f172a ou rgba(...)" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Opacidade do overlay
                    <input v-model="selectedCanvasBlock.overlay_opacity" type="number" min="0" max="1" step="0.05" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor do texto
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.text_color, '#0f172a')" type="color" @input="setSelectedBlockColor('text_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.text_color" type="text" placeholder="#0f172a" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Espessura da borda (px)
                    <input v-model="selectedCanvasBlock.border_width" type="number" min="0" max="40" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor da borda
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.border_color, '#94a3b8')" type="color" @input="setSelectedBlockColor('border_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.border_color" type="text" placeholder="#94a3b8 ou rgba(...)" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Ordem visual
                    <input v-model="selectedCanvasBlock.z_index" type="number" min="0" max="999" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Opacidade
                    <input v-model="selectedCanvasBlock.opacity" type="number" min="0.05" max="1" step="0.05" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Rotação
                    <input v-model.number="selectedCanvasBlock.rotation_deg" type="range" min="-45" max="45" step="1" class="mt-3 w-full accent-primary-800 dark:accent-primary-300" />
                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ selectedCanvasBlock.rotation_deg || 0 }}°</span>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Sombra
                    <select v-model="selectedCanvasBlock.shadow_preset" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in canvasShadowPresets" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Alinhamento do texto
                    <select v-model="selectedCanvasBlock.text_align" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="left">Esquerda</option>
                      <option value="center">Centro</option>
                      <option value="right">Direita</option>
                      <option value="justify">Justificado</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Tamanho do texto (px)
                    <input v-model="selectedCanvasBlock.font_size" type="number" min="8" max="72" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Entrelinha
                    <input v-model="selectedCanvasBlock.line_height" type="number" min="0.8" max="3" step="0.1" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                    <input v-model="selectedCanvasBlock.is_locked" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
                    Bloco bloqueado para mover e redimensionar
                  </label>
                  <label v-if="selectedCanvasBlock.surface === 'content'" class="text-sm text-slate-700 dark:text-slate-300">Âmbito no PDF
                    <select v-model="selectedCanvasBlock.page_scope" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in canvasContentScopeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label v-if="selectedCanvasBlock.surface === 'content' && selectedCanvasBlock.page_scope === 'specific'" class="text-sm text-slate-700 dark:text-slate-300">Página específica
                    <input v-model="selectedCanvasBlock.page_number" type="number" min="1" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                </div>

                <div v-if="selectedCanvasBlock.block_kind === 'signature'" class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Etiqueta
                    <input v-model="selectedCanvasBlock.signature_label" type="text" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Nome / placeholder
                    <input v-model="selectedCanvasBlock.signature_name" type="text" placeholder="{{customer_name}}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Função / cargo
                    <input v-model="selectedCanvasBlock.signature_title" type="text" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Imagem da assinatura
                    <input v-model="selectedCanvasBlock.signature_image" type="text" placeholder="/storage/signatures/director.png" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <select v-if="localAssetLibrary.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'signature_image')">
                      <option value="">Usar assinatura/ficheiro guardado</option>
                      <option v-for="asset in localAssetLibrary" :key="`sig-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'signature_image')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Escolher no media picker</button>
                  </label>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900 sm:col-span-2">
                    <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Imagem impressa da assinatura</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                      <label class="text-sm text-slate-700 dark:text-slate-300">Encaixe
                        <select v-model="selectedCanvasBlock.signature_image_fit" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in backgroundFitOptions" :key="`signature-fit-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label class="text-sm text-slate-700 dark:text-slate-300">Foco / recorte
                        <select v-model="selectedCanvasBlock.signature_image_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in imagePositionOptions" :key="`signature-position-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label class="text-sm text-slate-700 dark:text-slate-300">Largura impressa
                        <input v-model.number="selectedCanvasBlock.signature_image_width" type="number" min="24" max="360" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                      </label>
                      <label class="text-sm text-slate-700 dark:text-slate-300">Altura impressa
                        <input v-model.number="selectedCanvasBlock.signature_image_height" type="number" min="16" max="240" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                      </label>
                    </div>
                    <div v-if="selectedCanvasBlock.signature_image" class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950">
                      <img
                        :src="selectedCanvasBlock.signature_image"
                        alt="Pré-visualização da assinatura"
                        class="mx-auto max-w-full rounded-xl bg-white/80"
                        :style="{
                          width: `${clamp(Number(selectedCanvasBlock.signature_image_width || 180), 24, 360)}px`,
                          height: `${clamp(Number(selectedCanvasBlock.signature_image_height || 72), 16, 240)}px`,
                          objectFit: mediaObjectFit(selectedCanvasBlock.signature_image_fit || 'contain'),
                          objectPosition: safeCssPositionValue(selectedCanvasBlock.signature_image_position || 'center center', 'center center'),
                        }"
                      />
                    </div>
                  </div>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Estilo da linha
                    <select v-model="selectedCanvasBlock.signature_line_style" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="solid">Contínua</option>
                      <option value="dashed">Tracejada</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Alinhamento da assinatura
                    <select v-model="selectedCanvasBlock.signature_align" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="left">Esquerda</option>
                      <option value="center">Centro</option>
                      <option value="right">Direita</option>
                    </select>
                  </label>
                  <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                    <input v-model="selectedCanvasBlock.signature_show_date" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
                    Mostrar campo de data
                  </label>
                  <label v-if="selectedCanvasBlock.signature_show_date" class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Texto do campo de data
                    <input v-model="selectedCanvasBlock.signature_date_label" type="text" placeholder="Data: ____ / ____ / ______" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                </div>

                <div v-if="['image', 'stamp'].includes(selectedCanvasBlock.block_kind)" class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Imagem / carimbo
                    <input v-model="selectedCanvasBlock.image_url" type="text" placeholder="/storage/media/stamp.png" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <select v-if="localAssetLibrary.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'image_url')">
                      <option value="">Selecionar da galeria/assinaturas</option>
                      <option v-for="asset in localAssetLibrary" :key="`image-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'image_url')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Escolher no media picker</button>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Texto alternativo
                    <input v-model="selectedCanvasBlock.image_alt" type="text" placeholder="Carimbo de aprovação" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Ajuste
                    <select v-model="selectedCanvasBlock.image_fit" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="contain">Conter</option>
                      <option value="cover">Cobrir</option>
                      <option value="auto">Original</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Foco / recorte
                    <select v-model="selectedCanvasBlock.image_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in imagePositionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <div v-if="selectedCanvasBlock.image_url || selectedCanvasBlock.background_image" class="sm:col-span-2 rounded-[1.6rem] border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                    <div class="flex flex-col gap-4 lg:flex-row">
                      <div class="lg:w-72">
                        <div
                          class="relative h-44 cursor-crosshair overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900"
                          title="Arraste o ponto para ajustar o recorte da imagem"
                          @pointerdown="startImageFocalDrag(selectedCanvasBlock, $event)"
                        >
                          <img
                            :src="selectedCanvasBlock.image_url || selectedCanvasBlock.background_image"
                            :alt="selectedCanvasBlock.image_alt || selectedCanvasBlock.title || 'Imagem'"
                            class="h-full w-full select-none"
                            draggable="false"
                            :style="{ objectFit: imageObjectFit(selectedCanvasBlock), objectPosition: imageObjectPosition(selectedCanvasBlock) }"
                          />
                          <span class="pointer-events-none absolute h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-primary-600 shadow-lg ring-4 ring-primary-900/15" :style="imageFocalPointStyle(selectedCanvasBlock)" />
                        </div>
                        <div class="mt-2 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                          Posição exportada: {{ imageObjectPosition(selectedCanvasBlock) }}
                        </div>
                      </div>
                      <div class="min-w-0 flex-1 space-y-4">
                        <div>
                          <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Foco visual</div>
                          <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Ajuste o ponto que deve permanecer visível quando a imagem estiver em modo cobrir. O PDF usa a mesma posição.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                          <label class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Horizontal
                            <input :value="imagePositionCoordinates(selectedCanvasBlock.image_position).x" type="range" min="0" max="100" step="1" class="mt-2 w-full accent-primary-700" @input="setSelectedImageFocalCoordinate('x', $event.target.value)" />
                          </label>
                          <label class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">Vertical
                            <input :value="imagePositionCoordinates(selectedCanvasBlock.image_position).y" type="range" min="0" max="100" step="1" class="mt-2 w-full accent-primary-700" @input="setSelectedImageFocalCoordinate('y', $event.target.value)" />
                          </label>
                        </div>
                        <div class="flex flex-wrap gap-2">
                          <button type="button" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900" @click="setSelectedImageFocalPreset(50, 50)">Centro</button>
                          <button type="button" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900" @click="setSelectedImageFocalPreset(50, 0)">Topo</button>
                          <button type="button" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900" @click="setSelectedImageFocalPreset(50, 100)">Base</button>
                          <button type="button" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900" @click="setSelectedImageFocalPreset(0, 50)">Esquerda</button>
                          <button type="button" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900" @click="setSelectedImageFocalPreset(100, 50)">Direita</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="rounded-2xl border border-primary-200 bg-primary-50 p-4 text-xs leading-relaxed text-primary-900 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">
                    Para pôr um carimbo sobre uma assinatura, coloque o objecto de carimbo acima da assinatura e use uma ordem visual maior.
                  </div>
                </div>

                <div v-if="selectedCanvasBlock.block_kind === 'qr_code'" class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Conteúdo do QR
                    <input v-model="selectedCanvasBlock.qr_content" type="text" placeholder="{{document_code}} · {{customer_name}} · {{issue_date}}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Legenda do QR
                    <input v-model="selectedCanvasBlock.qr_label" type="text" placeholder="Verificação digital" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor do QR
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.qr_foreground_color, '#0f172a')" type="color" @input="setSelectedBlockColor('qr_foreground_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.qr_foreground_color" type="text" placeholder="#0f172a" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor de fundo do QR
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.qr_background_color, '#ffffff')" type="color" @input="setSelectedBlockColor('qr_background_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.qr_background_color" type="text" placeholder="#ffffff" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Tolerância de leitura
                    <select v-model="selectedCanvasBlock.qr_error_correction" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="low">Baixa · QR mais simples</option>
                      <option value="medium">Média · uso geral</option>
                      <option value="quartile">Alta · impressão exigente</option>
                      <option value="high">Máxima · maior redundância</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Margem de segurança
                    <input v-model.number="selectedCanvasBlock.qr_margin" type="number" min="0" max="32" step="1" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <p class="text-xs leading-5 text-slate-500 dark:text-slate-400 sm:col-span-2">
                    Use uma URL pública de validação ou variáveis do documento. A pré-visualização codifica o mesmo conteúdo usado no PDF final.
                  </p>
                </div>

                <div v-if="selectedCanvasBlock.block_kind === 'chart_snapshot'" class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Título do gráfico
                    <input v-model="selectedCanvasBlock.chart_title" type="text" placeholder="Tendência de ensaios por mês" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Tipo de gráfico
                    <select v-model="selectedCanvasBlock.chart_type" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in chartTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Rótulos
                    <textarea v-model="selectedCanvasBlock.chart_labels" rows="4" placeholder="Recepção, Validação, Emissão" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <span class="mt-2 block text-xs text-slate-500 dark:text-slate-400">Separe por vírgulas, ponto-e-vírgula ou linhas.</span>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Valores
                    <textarea v-model="selectedCanvasBlock.chart_values" rows="4" placeholder="18, 12, 9" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <span class="mt-2 block text-xs text-slate-500 dark:text-slate-400">Os valores são convertidos em SVG no preview e no PDF.</span>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Paleta
                    <input v-model="selectedCanvasBlock.chart_colors" type="text" placeholder="#143d37, #d9b05f, #0f766e" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor principal
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.chart_primary_color, '#143d37')" type="color" @input="setSelectedBlockColor('chart_primary_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.chart_primary_color" type="text" placeholder="#143d37" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Fundo do gráfico
                    <div class="mt-2 flex gap-2">
                      <input :value="colorInputValue(selectedCanvasBlock.chart_background_color, '#f8f4ea')" type="color" @input="setSelectedBlockColor('chart_background_color', $event.target.value)" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                      <input v-model="selectedCanvasBlock.chart_background_color" type="text" placeholder="#f8f4ea" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                    <input v-model="selectedCanvasBlock.chart_show_values" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
                    Mostrar valores no gráfico
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Imagem exportada / ficheiro
                    <input v-model="selectedCanvasBlock.chart_image_url" type="text" placeholder="/storage/report-studios/charts/trend.png" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <select v-if="localAssetLibrary.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'chart_image_url')">
                      <option value="">Selecionar gráfico da galeria</option>
                      <option v-for="asset in localAssetLibrary" :key="`chart-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'chart_image_url')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Escolher no media picker</button>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Legenda / leitura executiva
                    <input v-model="selectedCanvasBlock.chart_caption" type="text" placeholder="Inclui somente resultados validados no período selecionado." class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">SVG do gráfico
                    <textarea v-model="selectedCanvasBlock.chart_svg" rows="7" placeholder="<svg ...> exportado do ApexCharts</svg>" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <span class="mt-2 block text-xs leading-relaxed text-slate-500 dark:text-slate-400">Opcional. Se colar SVG ou escolher uma imagem, ela substitui o gráfico gerado pelo studio. O driver Chrome preserva melhor gráficos, sombras, cores e paginação.</span>
                  </label>
                </div>

                <div v-if="!['signature', 'image', 'stamp', 'qr_code', 'chart_snapshot'].includes(selectedCanvasBlock.block_kind)" class="mt-4">
                  <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Conteúdo do bloco</label>
                  <textarea v-model="selectedCanvasBlock.content_html" rows="8" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                </div>
              </div>
            </div>
          </div>
            </div>
          </details>
        </div>
      </section>

      <aside v-show="activeStudioPane === 'pdf'" class="space-y-5">
        <section class="studio-output-command">
          <div class="relative z-10 flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
              <div class="text-[11px] font-black uppercase tracking-[0.24em] text-[#d9b05f]">Finalização documental</div>
              <h2 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">Prepare a versão que será emitida</h2>
              <p class="mt-3 max-w-2xl text-sm font-medium leading-6 text-[#cbd8cf]">
                Estruture superfícies recorrentes, proteja a área útil e seleccione o renderizador adequado antes de guardar ou emitir o PDF.
              </p>
            </div>
            <div class="grid grid-cols-3 gap-2">
              <div class="studio-output-metric">
                <span class="studio-output-metric__value">{{ configuredPdfSurfaceCount }}/3</span>
                <span class="studio-output-metric__label">superfícies</span>
              </div>
              <div class="studio-output-metric">
                <span class="studio-output-metric__value">{{ previewPages.length || 1 }}</span>
                <span class="studio-output-metric__label">páginas</span>
              </div>
              <div class="studio-output-metric">
                <span class="studio-output-metric__value">{{ rendererCssRisks.length }}</span>
                <span class="studio-output-metric__label">riscos CSS</span>
              </div>
            </div>
          </div>
        </section>

        <nav class="studio-pdf-navigation" aria-label="Configuração da saída PDF">
          <button
            v-for="section in pdfWorkspaceSections"
            :key="section.value"
            type="button"
            class="studio-pdf-navigation__item"
            :class="{ 'studio-pdf-navigation__item--active': pdfWorkspaceSection === section.value }"
            @click="pdfWorkspaceSection = section.value"
          >
            <span class="studio-pdf-navigation__label">{{ section.label }}</span>
            <span class="studio-pdf-navigation__description">{{ section.description }}</span>
          </button>
        </nav>

        <section v-show="pdfWorkspaceSection === 'surfaces'" class="studio-output-panel">
          <div class="studio-output-panel__heading">
            <div>
              <div class="studio-output-eyebrow">Estrutura multi-página</div>
              <h3 class="studio-output-title">Superfícies que acompanham o documento</h3>
              <p class="studio-output-description">Configure visualmente a abertura, o cabeçalho contínuo e o rodapé. O código fica disponível apenas no modo avançado.</p>
            </div>
            <button type="button" class="studio-output-action" @click="activeStudioPane = 'preview'">
              <EyeIcon class="h-4 w-4" />
              Rever páginas
            </button>
          </div>

          <div class="mt-6 grid gap-4 xl:grid-cols-3">
            <article v-for="surface in pdfSurfaceCards" :key="surface.surface" class="studio-surface-card">
              <div class="studio-surface-card__preview">
                <div class="studio-surface-card__paper">
                  <div
                    class="studio-surface-card__content"
                    :class="surface.surface === 'footer_html' ? 'studio-surface-card__content--footer' : ''"
                    v-html="pdfSurfacePreviewHtml(surface.surface)"
                  />
                </div>
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h4 class="text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ surface.label }}</h4>
                    <p class="mt-1 text-xs font-medium leading-5 text-[#6b7b74] dark:text-[#a9bcb2]">{{ surface.description }}</p>
                  </div>
                  <span class="studio-surface-status" :class="{ 'studio-surface-status--ready': surface.isConfigured }">
                    {{ surface.isConfigured ? 'Configurado' : 'Em falta' }}
                  </span>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3">
                  <span class="text-[11px] font-black uppercase tracking-[0.14em] text-[#6b7b74] dark:text-[#83978d]">
                    {{ surface.objectCount }} objecto{{ surface.objectCount === 1 ? '' : 's' }}
                  </span>
                  <button type="button" class="studio-surface-edit" @click="editPdfSurfaceInCanvas(surface.surface, surface.pageNumber)">
                    Editar no canvas
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
            <div class="studio-background-card">
              <div class="studio-output-panel__heading">
                <div>
                  <div class="studio-output-eyebrow">Plano de fundo</div>
                  <h4 class="mt-2 text-lg font-black text-[#15231f] dark:text-[#f7f1e7]">Identidade aplicada à página</h4>
                  <p class="mt-2 text-sm font-medium leading-6 text-[#6b7b74] dark:text-[#a9bcb2]">Escolha uma imagem da galeria e controle o enquadramento sem introduzir ligações manualmente.</p>
                </div>
                <button type="button" class="studio-output-action" @click="openMediaPicker('document-background')">
                  <PhotoIcon class="h-4 w-4" />
                  Escolher media
                </button>
              </div>
              <div class="mt-5 grid gap-4 md:grid-cols-[220px_minmax(0,1fr)]">
                <div class="studio-background-card__preview">
                  <img
                    v-if="props.layoutSchema.background_image_path"
                    :src="props.layoutSchema.background_image_path"
                    alt="Fundo activo do documento"
                    class="h-full w-full"
                    :style="{ objectFit: mediaObjectFit(props.layoutSchema.background_size || 'cover'), objectPosition: props.layoutSchema.background_position || 'center center' }"
                  />
                  <div v-else class="flex h-full flex-col items-center justify-center gap-2 px-6 text-center">
                    <PhotoIcon class="h-7 w-7 text-[#d9b05f]" />
                    <span class="text-xs font-black uppercase tracking-[0.16em] text-[#6b7b74] dark:text-[#a9bcb2]">Sem fundo aplicado</span>
                  </div>
                </div>
                <div class="grid content-start gap-3 sm:grid-cols-3">
                  <label class="studio-compact-field">Ajuste
                    <select v-model="props.layoutSchema.background_size">
                      <option v-for="option in backgroundFitOptions" :key="`pdf-fit-${option.value}`" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label class="studio-compact-field">Posição
                    <select v-model="props.layoutSchema.background_position">
                      <option v-for="option in backgroundPositionOptions" :key="`pdf-position-${option.value}`" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label class="studio-compact-field">Repetição
                    <select v-model="props.layoutSchema.background_repeat">
                      <option v-for="option in backgroundRepeatOptions" :key="`pdf-repeat-${option.value}`" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <button
                    v-if="props.layoutSchema.background_image_path"
                    type="button"
                    class="studio-danger-action sm:col-span-3"
                    @click="props.layoutSchema.background_image_path = ''; mediaAssetUrl = ''"
                  >
                    Remover fundo
                  </button>
                </div>
              </div>
            </div>

            <div class="studio-background-card">
              <div class="studio-output-eyebrow">Tipografia documental</div>
              <h4 class="mt-2 text-lg font-black text-[#15231f] dark:text-[#f7f1e7]">Leitura consistente em todas as páginas</h4>
              <label class="studio-compact-field mt-5">Família tipográfica
                <select v-model="props.layoutSchema.document_font_family">
                  <option v-for="option in studioFontOptions" :key="`pdf-font-${option.value}`" :value="option.value">{{ option.label }}</option>
                </select>
              </label>
              <p class="mt-3 text-xs font-medium leading-5 text-[#6b7b74] dark:text-[#a9bcb2]">
                {{ studioFontOptions.find((option) => option.value === documentFontFamily)?.description }}
              </p>
              <div class="mt-5 rounded-[1.25rem] border border-[#ded3bf] bg-[#fffdf7] p-4 dark:border-[#25443c] dark:bg-[#07110f]">
                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-[#d9b05f]">Amostra tipográfica</div>
                <div class="mt-3 text-xl font-black text-[#15231f] dark:text-[#f7f1e7]" :style="{ fontFamily: documentFontFamily }">Relatório técnico controlado</div>
                <p class="mt-2 text-xs leading-5 text-[#6b7b74] dark:text-[#a9bcb2]" :style="{ fontFamily: documentFontFamily }">Resultado, incerteza, método e decisão apresentados com hierarquia consistente.</p>
              </div>
            </div>
          </div>

          <details class="studio-advanced-panel mt-5">
            <summary>Código e integrações avançadas</summary>
            <div class="mt-4 grid gap-4 xl:grid-cols-2">
              <label class="studio-code-field">Cabeçalho da primeira página
                <textarea v-model="props.layoutSchema.first_page_header_html" rows="6" />
              </label>
              <label class="studio-code-field">Cabeçalho das páginas seguintes
                <textarea v-model="props.layoutSchema.default_header_html" rows="6" />
              </label>
              <label class="studio-code-field">Rodapé
                <textarea v-model="props.layoutSchema.footer_html" rows="6" />
              </label>
              <label class="studio-code-field">CSS adicional
                <textarea v-model="props.layoutSchema.styles_css" rows="6" />
              </label>
              <label class="studio-code-field xl:col-span-2">Ligação directa do fundo
                <input v-model="props.layoutSchema.background_image_path" type="text" @change="syncAssetUrlFromBackground" />
              </label>
            </div>
          </details>
        </section>

        <section v-show="pdfWorkspaceSection === 'tables'" class="studio-output-panel">
          <div class="studio-output-panel__heading">
            <div>
              <div class="studio-output-eyebrow">Sistema de tabelas</div>
              <h3 class="studio-output-title">Resultados legíveis, mesmo em documentos densos</h3>
              <p class="studio-output-description">Aplique um preset profissional e refine cor, densidade e hierarquia. As alterações são persistidas no CSS do PDF.</p>
            </div>
            <button type="button" class="studio-output-action studio-output-action--primary" @click="syncTableStylesCss">Aplicar ao documento</button>
          </div>
          <div class="mt-6 grid gap-5 xl:grid-cols-[minmax(280px,0.75fr)_minmax(0,1.25fr)]">
            <div class="space-y-3">
              <button
                v-for="preset in tableStylePresets"
                :key="`output-${preset.key}`"
                type="button"
                class="studio-preset-card"
                :class="{ 'studio-preset-card--active': activeTableStylePresetKey === preset.key }"
                @click="applyTableStylePreset(preset)"
              >
                <span class="flex items-center justify-between gap-3">
                  <span class="text-sm font-black">{{ preset.label }}</span>
                  <span class="h-5 w-5 rounded-full border-2 border-white shadow-sm" :style="{ backgroundColor: preset.values.table_header_background }" />
                </span>
                <span class="mt-2 block text-xs font-medium leading-5 opacity-75">{{ preset.description }}</span>
              </button>
            </div>
            <div class="studio-table-stage">
              <div class="grid gap-3 sm:grid-cols-3">
                <label class="studio-color-field">Cabeçalho
                  <input v-model="props.layoutSchema.table_header_background" type="color" />
                </label>
                <label class="studio-color-field">Texto
                  <input v-model="props.layoutSchema.table_header_text_color" type="color" />
                </label>
                <label class="studio-color-field">Bordas
                  <input v-model="props.layoutSchema.table_border_color" type="color" />
                </label>
                <label class="studio-color-field">Cartões
                  <input v-model="props.layoutSchema.table_summary_background" type="color" />
                </label>
                <label class="studio-color-field">Texto dos cartões
                  <input v-model="props.layoutSchema.table_summary_text_color" type="color" />
                </label>
                <label class="studio-color-field">Texto auxiliar
                  <input v-model="props.layoutSchema.table_summary_muted_color" type="color" />
                </label>
                <label class="studio-compact-field">Tamanho da fonte
                  <input v-model.number="props.layoutSchema.table_font_size" type="number" min="8" max="16" />
                </label>
                <label class="studio-compact-field sm:col-span-2">Espaçamento interno · {{ tableStyleSettings.table_cell_padding }} px
                  <input v-model.number="props.layoutSchema.table_cell_padding" type="range" min="2" max="24" />
                </label>
              </div>
              <div class="mt-5 overflow-hidden rounded-[1.6rem] border border-[#ded3bf] bg-[#fffdf7] shadow-sm dark:border-[#25443c] dark:bg-[#07110f]">
                <div class="flex items-center justify-between border-b border-[#ded3bf] px-5 py-4 dark:border-[#25443c]">
                  <div>
                    <div class="studio-output-eyebrow">Pré-visualização real</div>
                    <div class="mt-1 text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">Tabela analítica bilingue</div>
                  </div>
                  <span class="studio-toolbar-status text-xs font-black">{{ tableStyleSettings.table_font_size }} px</span>
                </div>
                <table class="w-full border-collapse">
                  <thead>
                    <tr>
                      <th class="border text-left uppercase tracking-[0.12em]" :style="tablePreviewHeaderStyle">Parâmetro</th>
                      <th class="border text-left uppercase tracking-[0.12em]" :style="tablePreviewHeaderStyle">Resultado</th>
                      <th class="border text-left uppercase tracking-[0.12em]" :style="tablePreviewHeaderStyle">Unidade</th>
                    </tr>
                  </thead>
                  <tbody class="text-[#475a53] dark:text-[#cbd8cf]">
                    <tr>
                      <td class="border" :style="tablePreviewCellStyle">Proteína<span class="block text-[0.8em] opacity-65">Protein</span></td>
                      <td class="border font-black" :style="tablePreviewCellStyle">12,4 ± 0,3</td>
                      <td class="border" :style="tablePreviewCellStyle">g/100g</td>
                    </tr>
                    <tr>
                      <td class="border" :style="tablePreviewCellStyle">Humidade<span class="block text-[0.8em] opacity-65">Moisture</span></td>
                      <td class="border font-black" :style="tablePreviewCellStyle">8,1 ± 0,2</td>
                      <td class="border" :style="tablePreviewCellStyle">%</td>
                    </tr>
                  </tbody>
                </table>
                <div class="grid gap-3 border-t border-[#ded3bf] p-4 dark:border-[#25443c] sm:grid-cols-2">
                  <div class="rounded-2xl border p-4" :style="tablePreviewSummaryStyle">
                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.18em]" :style="tablePreviewSummaryMutedStyle">Amostra / Sample</span>
                    <span class="mt-1 block text-sm font-black">SE-2026-0142</span>
                    <span class="mt-2 block text-xs leading-5" :style="tablePreviewSummaryMutedStyle">Cartão de identificação persistido no PDF.</span>
                  </div>
                  <div class="rounded-2xl border p-4" :style="tablePreviewSummaryStyle">
                    <span class="block text-[0.68rem] font-black uppercase tracking-[0.18em]" :style="tablePreviewSummaryMutedStyle">Validação / Validation</span>
                    <span class="mt-1 block text-sm font-black">Direcção Técnica</span>
                    <span class="mt-2 block text-xs leading-5" :style="tablePreviewSummaryMutedStyle">Mesmas cores no preview e na exportação.</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section v-show="pdfWorkspaceSection === 'page'" class="studio-output-panel">
          <div class="studio-output-panel__heading">
            <div>
              <div class="studio-output-eyebrow">Geometria de emissão</div>
              <h3 class="studio-output-title">Página, orientação e área útil</h3>
              <p class="studio-output-description">Escolha uma configuração segura e use medidas exactas apenas quando o documento exigir um formato especializado.</p>
            </div>
            <div class="rounded-[1.25rem] border px-4 py-3 text-sm" :class="exportSetupStatus.class">
              <div class="font-black">{{ exportSetupStatus.label }}</div>
              <div class="mt-1 text-xs opacity-75">{{ printableAreaSummary }}</div>
            </div>
          </div>

          <div class="mt-6">
            <div class="studio-output-eyebrow">Formato da página</div>
            <div class="mt-3 grid gap-3 md:grid-cols-4">
              <button
                v-for="option in pageFormatOptions"
                :key="`output-page-${option.value}`"
                type="button"
                class="studio-format-card"
                :class="{ 'studio-format-card--active': props.exportSettings.paper_size === option.value }"
                @click="applyPageFormat(option)"
              >
                <span class="studio-format-card__paper" :class="{ 'studio-format-card__paper--landscape': props.exportSettings.orientation === 'L' }" />
                <span>
                  <span class="block text-sm font-black">{{ option.label }}</span>
                  <span class="mt-1 block text-xs font-medium leading-5 opacity-70">{{ option.description }}</span>
                </span>
              </button>
            </div>
          </div>

          <div class="mt-6 grid gap-5 xl:grid-cols-[minmax(0,1fr)_310px]">
            <div>
              <div class="flex items-center justify-between gap-3">
                <div class="studio-output-eyebrow">Perfis de margem</div>
                <span class="studio-toolbar-status text-xs font-black">{{ printableAreaSummary }}</span>
              </div>
              <div class="mt-3 grid gap-3 md:grid-cols-2">
                <button
                  v-for="profile in marginProfileOptions"
                  :key="`output-margin-${profile.key}`"
                  type="button"
                  class="studio-preset-card"
                  :class="{ 'studio-preset-card--active': activeMarginProfileKey === profile.key }"
                  @click="applyMarginProfile(profile)"
                >
                  <span class="text-sm font-black">{{ profile.label }}</span>
                  <span class="mt-2 block text-xs font-medium leading-5 opacity-75">{{ profile.description }}</span>
                </button>
              </div>
            </div>
            <div class="studio-page-specimen">
              <div class="studio-page-specimen__sheet" :class="{ 'studio-page-specimen__sheet--landscape': props.exportSettings.orientation === 'L' }">
                <div class="studio-page-specimen__safe" />
              </div>
              <div class="mt-4 text-center">
                <div class="text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ previewPageSummary }}</div>
                <div class="mt-1 text-xs font-medium text-[#6b7b74] dark:text-[#a9bcb2]">{{ previewMarginSummary }}</div>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-2">
                <button
                  v-for="option in orientationOptions"
                  :key="`output-orientation-${option.value}`"
                  type="button"
                  class="studio-orientation-button"
                  :class="{ 'studio-orientation-button--active': props.exportSettings.orientation === option.value }"
                  @click="props.exportSettings.orientation = option.value"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>
          </div>

          <details class="studio-advanced-panel mt-5">
            <summary>Medidas exactas da página e margens</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
              <label v-if="props.exportSettings.paper_size === 'custom'" class="studio-compact-field">Largura (mm)
                <input v-model.number="props.exportSettings.custom_page_width" type="number" min="50" max="2000" step="1" />
              </label>
              <label v-if="props.exportSettings.paper_size === 'custom'" class="studio-compact-field">Altura (mm)
                <input v-model.number="props.exportSettings.custom_page_height" type="number" min="50" max="2000" step="1" />
              </label>
              <label class="studio-compact-field">Topo (mm)
                <input v-model.number="props.exportSettings.margin_top" type="number" min="0" max="200" step="1" />
              </label>
              <label class="studio-compact-field">Direita (mm)
                <input v-model.number="props.exportSettings.margin_right" type="number" min="0" max="200" step="1" />
              </label>
              <label class="studio-compact-field">Base (mm)
                <input v-model.number="props.exportSettings.margin_bottom" type="number" min="0" max="200" step="1" />
              </label>
              <label class="studio-compact-field">Esquerda (mm)
                <input v-model.number="props.exportSettings.margin_left" type="number" min="0" max="200" step="1" />
              </label>
              <label class="studio-compact-field">Topo inicial (mm)
                <input v-model.number="props.exportSettings.first_page_margin_top" type="number" min="0" max="250" step="1" />
              </label>
            </div>
          </details>

          <div v-if="exportSetupIssues.length" class="mt-5 grid gap-3 md:grid-cols-2">
            <div v-for="issue in exportSetupIssues" :key="`output-${issue.title}`" class="studio-output-warning">
              <div class="text-sm font-black">{{ issue.title }}</div>
              <div class="mt-1 text-xs font-medium leading-5 opacity-75">{{ issue.description }}</div>
            </div>
          </div>
        </section>

        <section v-show="pdfWorkspaceSection === 'output'" class="studio-output-panel">
          <div class="studio-output-panel__heading">
            <div>
              <div class="studio-output-eyebrow">Motor de emissão</div>
              <h3 class="studio-output-title">Escolha a fidelidade adequada ao documento</h3>
              <p class="studio-output-description">Chrome é recomendado para composição moderna, gráficos e posicionamento. mPDF continua disponível para documentos clássicos e ambientes conservadores.</p>
            </div>
            <span class="rounded-full border px-3 py-1 text-xs font-black uppercase tracking-[0.14em]" :class="studioQualityStatus.class">{{ studioQualityStatus.label }}</span>
          </div>

          <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <button
              v-for="option in rendererOptions.filter((renderer) => renderer.value !== 'canva')"
              :key="`output-renderer-${option.value}`"
              type="button"
              class="studio-renderer-card"
              :class="{ 'studio-renderer-card--active': props.form.renderer === option.value }"
              :disabled="!option.available"
              @click="props.form.renderer = option.value"
            >
              <span class="flex items-start justify-between gap-4">
                <span>
                  <span class="block text-sm font-black">{{ option.label }}</span>
                  <span class="mt-2 block text-xs font-medium leading-5 opacity-75">{{ option.description }}</span>
                </span>
                <span class="studio-renderer-card__badge">{{ option.badge }}</span>
              </span>
            </button>
          </div>

          <div class="mt-6 grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div>
              <div class="studio-output-eyebrow">Validação antes de emitir</div>
              <div v-if="studioQualityIssues.length" class="mt-3 grid gap-3 md:grid-cols-2">
                <button
                  v-for="issue in studioQualityIssues"
                  :key="`output-quality-${issue.key}`"
                  type="button"
                  class="studio-quality-card"
                  @click="focusQualityIssue(issue)"
                >
                  <span class="text-sm font-black">{{ issue.title }}</span>
                  <span class="mt-1 block text-xs font-medium leading-5 opacity-75">{{ issue.description }}</span>
                </button>
              </div>
              <div v-else class="mt-3 rounded-[1.4rem] border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm font-black text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
                O documento passou as verificações estruturais do estúdio.
              </div>
            </div>
            <div class="studio-emission-card">
              <div class="studio-output-eyebrow">Resumo de emissão</div>
              <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4"><dt class="text-[#6b7b74] dark:text-[#a9bcb2]">Página</dt><dd class="text-right font-black text-[#15231f] dark:text-[#f7f1e7]">{{ selectedPageFormatOption.label }}</dd></div>
                <div class="flex items-start justify-between gap-4"><dt class="text-[#6b7b74] dark:text-[#a9bcb2]">Área útil</dt><dd class="text-right font-black text-[#15231f] dark:text-[#f7f1e7]">{{ printableAreaSummary }}</dd></div>
                <div class="flex items-start justify-between gap-4"><dt class="text-[#6b7b74] dark:text-[#a9bcb2]">Renderizador</dt><dd class="text-right font-black text-[#15231f] dark:text-[#f7f1e7]">{{ selectedRendererOption.label }}</dd></div>
              </dl>
              <button type="button" class="studio-output-action studio-output-action--primary mt-5 w-full justify-center" :disabled="draftPreviewBusy || props.form.processing" @click="previewDraftPdf">
                <EyeIcon class="h-4 w-4" />
                {{ draftPreviewBusy ? studioCopy('draft_preview.generating', {}, 'A gerar PDF...') : studioCopy('draft_preview.action', {}, 'Pré-visualizar PDF') }}
              </button>
              <a v-if="props.previewPdfHref" :href="props.previewPdfHref" target="_blank" class="studio-output-action mt-3 w-full justify-center">
                <EyeIcon class="h-4 w-4" />
                {{ studioCopy('draft_preview.saved_preview', {}, 'Abrir PDF guardado') }}
              </a>
              <button type="button" class="studio-output-action studio-output-action--primary mt-3 w-full justify-center" :disabled="props.form.processing" @click="submit">
                {{ props.form.processing ? 'A guardar…' : props.submitLabel }}
              </button>
              <p v-if="draftPreviewError" class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold leading-5 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">
                {{ draftPreviewError }}
              </p>
            </div>
          </div>

          <details class="studio-advanced-panel mt-5">
            <summary>Notas técnicas do renderizador</summary>
            <p class="mt-3 text-xs font-medium leading-6 text-[#6b7b74] dark:text-[#a9bcb2]">
              O mPDF usa principalmente CSS 2.1. Para flex/grid, filtros, transforms, gráficos exportados como SVG ou imagem e posicionamento moderno, seleccione Chrome PDF. No Chrome, cabeçalhos, rodapés e paginação usam templates nativos.
            </p>
          </details>
        </section>
      </aside>
    </div>

    <div v-show="activeStudioPane === 'preview'" class="studio-preview-workspace">
      <div class="studio-preview-header">
        <div class="min-w-0">
          <div class="studio-output-eyebrow">Prova editorial</div>
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">{{ previewMeta.title }}</h2>
            <span class="studio-toolbar-status text-[10px] font-black uppercase tracking-[0.12em]">{{ previewPageFormatLabel }}</span>
            <span class="studio-toolbar-status text-[10px] font-black uppercase tracking-[0.12em]">{{ printableAreaSummary }}</span>
          </div>
          <p class="mt-2 max-w-3xl text-xs font-medium leading-5 text-[#6b7b74] dark:text-[#a9bcb2]">{{ previewMeta.subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div class="studio-preview-toggle">
            <button type="button" :class="{ 'studio-preview-toggle__item--active': previewPageKind === 'first-page' }" class="studio-preview-toggle__item" @click="showPreviewScope('first-page')">Primeira</button>
            <button type="button" :class="{ 'studio-preview-toggle__item--active': previewPageKind === 'following-page' }" class="studio-preview-toggle__item" @click="showPreviewScope('following-page')">Seguintes</button>
          </div>
          <div class="studio-preview-toggle">
            <button type="button" :class="{ 'studio-preview-toggle__item--active': previewDisplayMode === 'paged' }" class="studio-preview-toggle__item" @click="previewDisplayMode = 'paged'">Uma página</button>
            <button type="button" :class="{ 'studio-preview-toggle__item--active': previewDisplayMode === 'all' }" class="studio-preview-toggle__item" @click="previewDisplayMode = 'all'">Documento</button>
          </div>
          <details class="studio-toolbar-menu relative">
            <summary class="studio-toolbar-button">
              <EyeIcon class="h-4 w-4" />
              Assistentes
            </summary>
            <div class="studio-toolbar-menu-panel studio-toolbar-menu-panel--compact">
              <label class="studio-view-option"><input v-model="showCanvasGrid" type="checkbox" /><span>Grelha</span></label>
              <label class="studio-view-option"><input v-model="showCanvasRulers" type="checkbox" /><span>Réguas</span></label>
              <label class="studio-view-option"><input v-model="showSafeArea" type="checkbox" /><span>Área segura</span></label>
              <label class="studio-view-option"><input v-model="snapToGrid" type="checkbox" /><span>Alinhamento inteligente</span></label>
              <label class="studio-compact-field mt-2">Passo da grelha (%)
                <input v-model="gridSize" type="number" min="1" max="24" />
              </label>
            </div>
          </details>
        </div>
      </div>

      <div v-if="previewPages.length > 1" class="studio-preview-pagebar">
        <div class="studio-toolbar-group">
          <button type="button" class="studio-toolbar-icon-button" :disabled="currentPreviewPage <= 1 || previewDisplayMode === 'all'" aria-label="Página anterior" @click="stepPreviewPage(-1)">‹</button>
          <span class="min-w-16 px-2 text-center text-[11px] font-black tracking-[0.08em] text-[#475a53] dark:text-[#cbd8cf]">{{ currentPreviewPage }}/{{ previewPages.length }}</span>
          <button type="button" class="studio-toolbar-icon-button" :disabled="currentPreviewPage >= previewPages.length || previewDisplayMode === 'all'" aria-label="Página seguinte" @click="stepPreviewPage(1)">›</button>
        </div>
        <div class="flex min-w-0 flex-1 gap-2 overflow-x-auto py-1">
          <button
            v-for="pageNumber in previewPages.length"
            :key="`preview-nav-${pageNumber}`"
            type="button"
            class="studio-preview-page-chip"
            :class="{ 'studio-preview-page-chip--active': currentPreviewPage === pageNumber && previewDisplayMode !== 'all' }"
            @click="setPreviewPage(pageNumber)"
          >
            <span class="studio-preview-page-chip__paper" />
            Página {{ pageNumber }}
          </button>
        </div>
      </div>

      <div class="studio-preview-stage">
        <div
          v-for="previewPage in visiblePreviewPages"
          :key="`preview-page-${previewPage.pageNumber}`"
          class="studio-preview-document relative mx-auto w-full rounded-[32px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
          :style="previewPageFrameStyle"
        >
          <div v-if="showCanvasRulers" class="pointer-events-none absolute left-10 right-10 top-10 z-20 h-5 border-t border-slate-200/80 dark:border-slate-700/80">
            <span
              v-for="mark in canvasRulerMarks"
              :key="`preview-top-ruler-${previewPage.pageNumber}-${mark}`"
              class="absolute top-0 flex -translate-x-1/2 flex-col items-center gap-0.5 text-[9px] font-bold text-slate-400 dark:text-slate-500"
              :style="{ left: `${mark}%` }"
            >
              <span class="h-2 w-px bg-slate-300 dark:bg-slate-600" />
              {{ mark }}%
            </span>
          </div>
          <div v-if="showCanvasRulers" class="pointer-events-none absolute bottom-10 left-8 top-16 z-20 w-5 border-l border-slate-200/80 dark:border-slate-700/80">
            <span
              v-for="mark in canvasRulerMarks"
              :key="`preview-left-ruler-${previewPage.pageNumber}-${mark}`"
              class="absolute left-0 flex -translate-y-1/2 items-center gap-1 text-[9px] font-bold text-slate-400 dark:text-slate-500"
              :style="{ top: `${mark}%` }"
            >
              <span class="h-px w-2 bg-slate-300 dark:bg-slate-600" />
              {{ mark }}%
            </span>
          </div>
          <div class="pointer-events-none absolute left-5 right-5 top-4 z-20 flex items-center justify-between text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
            <span>{{ previewPage.pageNumber === 1 ? 'Primeira página' : 'Página contínua' }}</span>
            <span>{{ previewPage.pageNumber }}/{{ previewPages.length }}</span>
          </div>
          <div
            v-if="showSafeArea"
            class="pointer-events-none absolute rounded-[26px] border border-dashed border-primary-300/70 dark:border-primary-500/40"
            :style="previewMarginStyleForPage(previewPage.pageNumber)"
          />
          <div
            v-for="guideX in alignmentGuides.x"
            :key="`guide-x-${previewPage.pageNumber}-${guideX}`"
            class="pointer-events-none absolute top-8 bottom-8 z-10 w-px bg-primary-400/70 dark:bg-primary-300/60 xl:top-10 xl:bottom-10"
            :style="{ left: `${guideX}%` }"
          />
          <div
            v-for="guideY in alignmentGuides.y"
            :key="`guide-y-${previewPage.pageNumber}-${guideY}`"
            class="pointer-events-none absolute left-8 right-8 z-10 h-px bg-primary-400/70 dark:bg-primary-300/60 xl:left-10 xl:right-10"
            :style="{ top: `${guideY}%` }"
          />
          <div class="absolute z-0 flex flex-col gap-5 overflow-hidden rounded-[22px]" :style="previewContentPaddingStyleForPage(previewPage.pageNumber)">
            <div data-canvas-surface="header" class="relative rounded-2xl border border-dashed border-slate-300 bg-white/80 p-5 dark:border-slate-600 dark:bg-slate-900/70" :style="[previewGridStyle, { minHeight: '104px' }]">
              <div v-html="previewHeaderHtmlForPage(previewPage.pageNumber)" />
              <div
                v-for="block in previewHeaderBlocksForPage(previewPage.pageNumber)"
                :key="block.id"
                class="group overflow-hidden border border-white/40 transition"
                :class="selectedCanvasBlockId === block.id ? 'ring-2 ring-primary-400/80' : 'hover:ring-2 hover:ring-primary-300/70'"
                :style="canvasBlockStyle(block)"
                @click.stop="selectCanvasBlock(block.id)"
              >
                <div v-if="canvasBlockOverlayStyle(block)" :style="canvasBlockOverlayStyle(block)" />
                <button
                  type="button"
                  class="absolute left-2 top-2 z-20 inline-flex items-center rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                  :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-700/90' : 'cursor-move'"
                  @mousedown="startDrag($event, block)"
                >
                  {{ canvasBlockIsLocked(block) ? 'bloqueado' : 'mover' }}
                </button>
                <div class="relative z-10" v-html="canvasBlockContentHtml(block)" />
                <button
                  type="button"
                  class="absolute bottom-2 right-2 z-20 h-4 w-4 rounded-full border border-white/70 bg-primary-500 opacity-0 shadow transition group-hover:opacity-100"
                  :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-500' : 'cursor-se-resize'"
                  @mousedown="startResize($event, block)"
                />
              </div>
            </div>
            <div data-canvas-surface="content" class="relative flex-1 rounded-2xl border border-slate-200 bg-white/85 p-6 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/80 xl:p-7" :style="previewGridStyle">
              <div class="prose max-w-none dark:prose-invert" v-html="previewPage.content" />
              <div
                v-if="!previewContentBlocksForPage(previewPage.pageNumber).length && previewContentBlocks.length"
                class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-3 text-xs text-slate-500 dark:border-slate-600 dark:bg-slate-950/40 dark:text-slate-400"
              >
                Nenhum bloco do corpo está configurado para esta página. Ajuste o âmbito do bloco para primeira página, páginas seguintes, todas ou uma página específica.
              </div>
              <div
                v-for="block in previewContentBlocksForPage(previewPage.pageNumber)"
                :key="block.id"
                class="group overflow-hidden border border-white/40 transition"
                :class="selectedCanvasBlockId === block.id ? 'ring-2 ring-primary-400/80' : 'hover:ring-2 hover:ring-primary-300/70'"
                :style="canvasBlockStyle(block)"
                @click.stop="selectCanvasBlock(block.id)"
              >
                <div v-if="canvasBlockOverlayStyle(block)" :style="canvasBlockOverlayStyle(block)" />
                <button
                  type="button"
                  class="absolute left-2 top-2 z-20 inline-flex items-center rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                  :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-700/90' : 'cursor-move'"
                  @mousedown="startDrag($event, block)"
                >
                  {{ canvasBlockIsLocked(block) ? 'bloqueado' : 'mover' }}
                </button>
                <div class="relative z-10" v-html="canvasBlockContentHtml(block)" />
                <button
                  type="button"
                  class="absolute bottom-2 right-2 z-20 h-4 w-4 rounded-full border border-white/70 bg-primary-500 opacity-0 shadow transition group-hover:opacity-100"
                  :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-500' : 'cursor-se-resize'"
                  @mousedown="startResize($event, block)"
                />
              </div>
            </div>
            <div data-canvas-surface="footer" class="relative rounded-2xl border border-dashed border-slate-300 bg-white/80 p-5 text-xs dark:border-slate-600 dark:bg-slate-900/70" :style="[previewGridStyle, { minHeight: '96px' }]">
              <div v-html="previewFooterHtmlForPage(previewPage.pageNumber, previewPages.length)" />
              <div
                v-for="block in previewFooterBlocks"
                :key="block.id"
                class="group overflow-hidden border border-white/40 transition"
                :class="selectedCanvasBlockId === block.id ? 'ring-2 ring-primary-400/80' : 'hover:ring-2 hover:ring-primary-300/70'"
                :style="canvasBlockStyle(block)"
                @click.stop="selectCanvasBlock(block.id)"
              >
                <div v-if="canvasBlockOverlayStyle(block)" :style="canvasBlockOverlayStyle(block)" />
                <button
                  type="button"
                  class="absolute left-2 top-2 z-20 inline-flex items-center rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                  :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-700/90' : 'cursor-move'"
                  @mousedown="startDrag($event, block)"
                >
                  {{ canvasBlockIsLocked(block) ? 'bloqueado' : 'mover' }}
                </button>
                <div class="relative z-10" v-html="canvasBlockContentHtml(block)" />
                <button
                  type="button"
                  class="absolute bottom-2 right-2 z-20 h-4 w-4 rounded-full border border-white/70 bg-primary-500 opacity-0 shadow transition group-hover:opacity-100"
                  :class="canvasBlockIsLocked(block) ? 'cursor-not-allowed bg-amber-500' : 'cursor-se-resize'"
                  @mousedown="startResize($event, block)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <p class="mt-4 text-center text-xs font-medium text-[#6b7b74] dark:text-[#a9bcb2]">
        Esta pré-visualização serve para a composição editorial. No PDF final, cabeçalhos, rodapés, margens, orientação, fundo, paginação e o âmbito por página dos blocos do corpo são respeitados.
      </p>
    </div>

    <div v-if="mediaPickerOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" @click.self="mediaPickerOpen = false">
      <div class="max-h-[86vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/10 bg-white shadow-2xl dark:bg-slate-950">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ studioCopy('media_picker.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ studioCopy('media_picker.description') }}</p>
            <div class="mt-2 inline-flex rounded-full bg-primary-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-primary-800 dark:bg-primary-950/40 dark:text-primary-200">
              {{ studioCopy('media_picker.target_label', { target: mediaPickerTargetLabel }) }}
            </div>
          </div>
          <button type="button" @click="mediaPickerOpen = false" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">
            {{ studioCopy('media_picker.close') }}
          </button>
        </div>
        <div class="max-h-[68vh] overflow-y-auto p-6">
          <input
            ref="mediaPickerUploadInput"
            type="file"
            class="hidden"
            accept="image/svg+xml,image/png,image/jpeg,image/webp,image/gif,image/avif"
            @change="handleMediaPickerUploadInput"
          />
          <button
            type="button"
            class="mb-5 w-full rounded-[1.8rem] border border-dashed p-5 text-left transition"
            :class="mediaPickerUploadDragging
              ? 'border-primary-500 bg-primary-50 dark:border-primary-300 dark:bg-primary-500/10'
              : 'border-slate-300 bg-slate-50 hover:border-primary-400 hover:bg-primary-50/60 dark:border-slate-700 dark:bg-slate-900/70 dark:hover:border-primary-500/60 dark:hover:bg-primary-500/10'"
            @click="pickMediaPickerUpload"
            @dragenter.prevent="mediaPickerUploadDragging = true"
            @dragover.prevent="mediaPickerUploadDragging = true"
            @dragleave.prevent="mediaPickerUploadDragging = false"
            @drop.prevent="handleMediaPickerDrop"
          >
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="flex items-start gap-3">
                <div class="rounded-2xl bg-primary-900 p-3 text-white shadow-lg shadow-primary-950/10 dark:bg-primary-500">
                  <PhotoIcon class="h-5 w-5" />
                </div>
                <div>
                  <div class="text-sm font-black text-slate-950 dark:text-white">{{ studioCopy('media_picker.add_title') }}</div>
                  <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    {{ studioCopy('media_picker.add_description') }}
                  </p>
                </div>
              </div>
              <span class="rounded-full bg-white px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.14em] text-primary-800 shadow-sm dark:bg-slate-950 dark:text-primary-200">
                {{ studioCopy('media_picker.allowed_badge') }}
              </span>
            </div>
            <div v-if="mediaPickerUploadBusy" class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
              <div class="h-full rounded-full bg-primary-700 transition-all dark:bg-primary-400" :style="{ width: `${mediaPickerUploadProgress}%` }" />
            </div>
            <p v-if="mediaPickerUploadError" class="mt-3 text-xs font-semibold text-red-600 dark:text-red-300">{{ mediaPickerUploadError }}</p>
          </button>

          <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/70">
            <input
              v-model="mediaPickerSearch"
              type="search"
              :placeholder="studioCopy('media_picker.search_placeholder')"
              class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
            />

            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="option in mediaKindOptions"
                :key="option.value"
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-black uppercase tracking-[0.12em] transition"
                :class="mediaPickerKind === option.value
                  ? 'border-primary-700 bg-primary-900 text-white shadow-lg shadow-primary-950/10 dark:border-primary-400 dark:bg-primary-500 dark:text-slate-950'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-primary-300 hover:text-primary-800 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-primary-500/70 dark:hover:text-primary-100'"
                @click="mediaPickerKind = option.value"
              >
                <span>{{ option.label }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-500 dark:bg-slate-800 dark:text-slate-300" :class="mediaPickerKind === option.value ? '!bg-white/20 !text-white dark:!bg-slate-950/20 dark:!text-slate-950' : ''">
                  {{ option.count }}
                </span>
              </button>
            </div>

            <div class="mt-3 grid gap-2 rounded-[1.4rem] border border-slate-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-950 sm:grid-cols-[minmax(0,1fr)_150px]">
              <input
                v-model="mediaPickerManualUrl"
                type="url"
                :placeholder="studioCopy('media_picker.manual_url_placeholder')"
                class="block w-full rounded-2xl border-0 bg-transparent px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
              />
              <button
                type="button"
                class="rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-45 dark:bg-primary-600 dark:hover:bg-primary-500"
                :disabled="!mediaPickerManualUrl.trim()"
                @click="applyMediaPickerManualUrl"
              >
                {{ studioCopy('media_picker.apply_url') }}
              </button>
            </div>
          </div>

          <swiper-container
            v-if="filteredMediaAssets.length"
            class="report-studio-swiper mt-5 block"
            :slides-per-view="1.05"
            :space-between="16"
            :breakpoints="{ 640: { slidesPerView: 2.05 }, 1280: { slidesPerView: 3.05 } }"
            navigation="true"
            pagination="true"
          >
            <swiper-slide
              v-for="asset in filteredMediaAssets"
              :key="`picker-${asset.id}`"
            >
              <button
                type="button"
                @click="applyMediaPickerAsset(asset)"
                class="group w-full overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 text-left transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900"
              >
                <div class="flex h-44 items-center justify-center bg-slate-100 dark:bg-slate-900/80">
                  <img :src="asset.url" :alt="asset.label" class="h-full w-full object-contain p-4 transition group-hover:scale-105" />
                </div>
                <div class="p-4">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="truncate text-sm font-semibold text-slate-950 dark:text-white">{{ asset.label }}</div>
                      <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ asset.source }}</div>
                    </div>
                    <span class="shrink-0 rounded-full bg-primary-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-primary-800 dark:bg-primary-950/40 dark:text-primary-200">
                      {{ mediaKindLabel(mediaKindValue(asset)) }}
                    </span>
                  </div>
                  <div v-if="asset.author" class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ asset.author }}</div>
                </div>
              </button>
            </swiper-slide>
          </swiper-container>

          <div v-else class="mt-5 rounded-3xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            {{ studioCopy('media_picker.empty') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.report-studio-swiper {
  --swiper-theme-color: rgb(var(--primary-700-rgb));
  --swiper-navigation-size: 12px;
  padding-bottom: 2.5rem;
}

.report-studio-shell {
  --studio-accent: rgb(var(--accent-400-rgb));
  --studio-border: #ded3bf;
  --studio-border-dark: #25443c;
  --studio-ink: #15231f;
  --studio-muted: #475a53;
  --studio-soft: #f8f4ea;
  --studio-surface: #fffdf7;
  --studio-surface-strong: #fffaf0;
  --studio-surface-dark: #07110f;
  --studio-surface-dark-soft: #10231f;
  color: #15231f;
  font-family: var(--font-sans);
}

.dark .report-studio-shell {
  color: #f7f1e7;
}

.studio-canvas-toolbar {
  align-items: center;
  background: color-mix(in srgb, #fffdf7 92%, white);
  border: 1px solid color-mix(in srgb, #ded3bf 82%, white);
  border-radius: 1.6rem;
  box-shadow: 0 18px 45px rgb(20 61 55 / 0.08);
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
  padding: 0.9rem 1rem;
}

.dark .studio-canvas-toolbar {
  background: color-mix(in srgb, #10231f 92%, #07110f);
  border-color: #25443c;
  box-shadow: 0 18px 45px rgb(0 0 0 / 0.22);
}

.studio-toolbar-status {
  align-items: center;
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 999px;
  display: inline-flex;
  padding: 0.28rem 0.58rem;
}

.dark .studio-toolbar-status {
  background: #07110f;
  border-color: #25443c;
}

.studio-toolbar-group {
  align-items: center;
  background: color-mix(in srgb, #fffdf7 90%, white);
  border: 1px solid #ded3bf;
  border-radius: 999px;
  display: inline-flex;
  min-height: 2.65rem;
  padding: 0.25rem;
}

.dark .studio-toolbar-group {
  background: #07110f;
  border-color: #25443c;
}

.studio-toolbar-button {
  align-items: center;
  background: color-mix(in srgb, #fffdf7 90%, white);
  border: 1px solid #ded3bf;
  border-radius: 999px;
  color: #475a53;
  cursor: pointer;
  display: inline-flex;
  font-size: 0.75rem;
  font-weight: 800;
  gap: 0.5rem;
  min-height: 2.65rem;
  padding: 0.55rem 0.9rem;
  transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
  user-select: none;
}

.studio-toolbar-button::-webkit-details-marker {
  display: none;
}

.studio-toolbar-button:hover,
.studio-toolbar-menu[open] > .studio-toolbar-button {
  border-color: #d9b05f;
  box-shadow: 0 12px 30px rgb(20 61 55 / 0.12);
  color: #143d37;
  transform: translateY(-1px);
}

.studio-toolbar-button--primary {
  background: #143d37;
  border-color: #143d37;
  color: white;
}

.studio-toolbar-button--primary:hover,
.studio-toolbar-menu[open] > .studio-toolbar-button--primary {
  background: #0f302b;
  color: white;
}

.dark .studio-toolbar-button {
  background: #07110f;
  border-color: #25443c;
  color: #cbd8cf;
}

.dark .studio-toolbar-button:hover,
.dark .studio-toolbar-menu[open] > .studio-toolbar-button {
  border-color: #d9b05f;
  color: #f7f1e7;
}

.dark .studio-toolbar-button--primary {
  background: #d9b05f;
  border-color: #d9b05f;
  color: #07110f;
}

.studio-toolbar-menu-panel {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1.25rem;
  box-shadow: 0 24px 60px rgb(20 61 55 / 0.18);
  margin-top: 0.55rem;
  padding: 0.75rem;
  position: absolute;
  right: 0;
  width: min(18rem, calc(100vw - 3rem));
  z-index: 40;
}

.studio-toolbar-menu-panel--compact {
  width: min(16rem, calc(100vw - 3rem));
}

.dark .studio-toolbar-menu-panel {
  background: #10231f;
  border-color: #25443c;
  box-shadow: 0 24px 60px rgb(0 0 0 / 0.4);
}

.studio-toolbar-menu-action {
  align-items: center;
  background: #f8f4ea;
  border: 1px solid transparent;
  border-radius: 0.9rem;
  color: #475a53;
  display: inline-flex;
  font-size: 0.72rem;
  font-weight: 800;
  gap: 0.45rem;
  justify-content: flex-start;
  padding: 0.7rem 0.75rem;
  transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
}

.studio-toolbar-menu-action:hover {
  background: #fffaf0;
  border-color: #d9b05f;
  color: #143d37;
}

.dark .studio-toolbar-menu-action {
  background: #07110f;
  color: #cbd8cf;
}

.dark .studio-toolbar-menu-action:hover {
  background: #143d37;
  border-color: #d9b05f;
  color: #f7f1e7;
}

.studio-toolbar-icon-button {
  align-items: center;
  border-radius: 999px;
  color: #143d37;
  display: inline-flex;
  font-size: 1.15rem;
  font-weight: 900;
  height: 2.1rem;
  justify-content: center;
  transition: background-color 150ms ease, color 150ms ease;
  width: 2.1rem;
}

.studio-toolbar-icon-button:hover:not(:disabled) {
  background: #143d37;
  color: white;
}

.studio-toolbar-icon-button:disabled {
  cursor: not-allowed;
  opacity: 0.3;
}

.dark .studio-toolbar-icon-button {
  color: #f7f1e7;
}

.dark .studio-toolbar-icon-button:hover:not(:disabled) {
  background: #d9b05f;
  color: #07110f;
}

.studio-toolbar-select {
  appearance: none;
  background: transparent;
  border: 0;
  border-radius: 999px;
  color: #143d37;
  cursor: pointer;
  font-size: 0.72rem;
  font-weight: 900;
  min-height: 2rem;
  padding: 0.25rem 1.8rem 0.25rem 0.45rem;
}

.dark .studio-toolbar-select {
  color: #f7f1e7;
}

.studio-view-option {
  align-items: center;
  border-radius: 0.85rem;
  color: #475a53;
  cursor: pointer;
  display: flex;
  font-size: 0.75rem;
  font-weight: 800;
  gap: 0.65rem;
  padding: 0.65rem 0.75rem;
  transition: background-color 150ms ease, color 150ms ease;
}

.studio-view-option:hover {
  background: #f8f4ea;
  color: #143d37;
}

.studio-view-option input {
  accent-color: #143d37;
}

.dark .studio-view-option {
  color: #cbd8cf;
}

.dark .studio-view-option:hover {
  background: #07110f;
  color: #f7f1e7;
}

.dark .studio-view-option input {
  accent-color: #d9b05f;
}

.studio-context-bar {
  align-items: center;
  background: color-mix(in srgb, #f8f4ea 88%, white);
  border: 1px solid #ded3bf;
  border-radius: 1.35rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  justify-content: space-between;
  padding: 0.7rem 0.8rem 0.7rem 1rem;
}

.dark .studio-context-bar {
  background: #10231f;
  border-color: #25443c;
}

.studio-context-action {
  align-items: center;
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 0.8rem;
  color: #475a53;
  display: inline-flex;
  font-size: 0.68rem;
  font-weight: 900;
  height: 2.2rem;
  justify-content: center;
  min-width: 2.2rem;
  padding: 0 0.65rem;
  transition: border-color 150ms ease, color 150ms ease, transform 150ms ease;
}

.studio-context-action:hover {
  border-color: #d9b05f;
  color: #143d37;
  transform: translateY(-1px);
}

.studio-context-action--text {
  min-width: auto;
}

.studio-context-action--danger {
  color: #be123c;
}

.studio-context-action--danger:hover {
  border-color: #fda4af;
  color: #9f1239;
}

.dark .studio-context-action {
  background: #07110f;
  border-color: #25443c;
  color: #cbd8cf;
}

.dark .studio-context-action:hover {
  border-color: #d9b05f;
  color: #f7f1e7;
}

.dark .studio-context-action--danger {
  color: #fda4af;
}

.studio-advanced-panel {
  background: color-mix(in srgb, #f8f4ea 88%, white);
  border: 1px solid #ded3bf;
  border-radius: 1rem;
  color: #475a53;
  padding: 0.7rem 0.8rem;
}

.studio-advanced-panel > summary {
  cursor: pointer;
  font-size: 0.68rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  list-style: none;
  text-transform: uppercase;
}

.studio-advanced-panel > summary::-webkit-details-marker {
  display: none;
}

.studio-advanced-panel > summary::after {
  color: #d9b05f;
  content: "+";
  float: right;
  font-size: 0.9rem;
  line-height: 1;
}

.studio-advanced-panel[open] > summary::after {
  content: "−";
}

.dark .studio-advanced-panel {
  background: #07110f;
  border-color: #25443c;
  color: #cbd8cf;
}

.studio-composition-drawer {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1.8rem;
  box-shadow: 0 18px 55px rgb(20 61 55 / 0.08);
  color: #475a53;
  overflow: hidden;
}

.studio-composition-drawer > summary {
  align-items: center;
  cursor: pointer;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  list-style: none;
  padding: 1.1rem 1.25rem;
}

.studio-composition-drawer > summary::-webkit-details-marker {
  display: none;
}

.studio-composition-drawer__eyebrow,
.studio-composition-drawer__title,
.studio-composition-drawer__description {
  display: block;
}

.studio-composition-drawer__eyebrow {
  color: #b98a34;
  font-size: 0.62rem;
  font-weight: 900;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.studio-composition-drawer__title {
  color: #15231f;
  font-size: 1rem;
  font-weight: 900;
  letter-spacing: -0.01em;
  margin-top: 0.35rem;
}

.studio-composition-drawer__description {
  color: #6b7b74;
  font-size: 0.78rem;
  font-weight: 650;
  line-height: 1.55;
  margin-top: 0.25rem;
  max-width: 52rem;
}

.studio-composition-drawer__action {
  align-items: center;
  background: #143d37;
  border-radius: 999px;
  color: #fffaf0;
  display: inline-flex;
  flex-shrink: 0;
  font-size: 0.72rem;
  font-weight: 900;
  justify-content: center;
  min-width: 5rem;
  padding: 0.65rem 1rem;
  text-transform: uppercase;
}

.studio-composition-drawer__content {
  border-top: 1px solid #ded3bf;
  padding: 0 1.25rem 1.25rem;
}

.dark .studio-composition-drawer {
  background: #07110f;
  border-color: #25443c;
  box-shadow: 0 18px 55px rgb(0 0 0 / 0.24);
  color: #cbd8cf;
}

.dark .studio-composition-drawer__eyebrow {
  color: #d9b05f;
}

.dark .studio-composition-drawer__title {
  color: #f7f1e7;
}

.dark .studio-composition-drawer__description {
  color: #a9bcb2;
}

.dark .studio-composition-drawer__action {
  background: #d9b05f;
  color: #07110f;
}

.dark .studio-composition-drawer__content {
  border-color: #25443c;
}

.studio-output-command {
  background:
    radial-gradient(circle at 88% 8%, rgb(217 176 95 / 0.2), transparent 30%),
    linear-gradient(135deg, #07110f, #143d37);
  border: 1px solid #25443c;
  border-radius: 2rem;
  box-shadow: 0 26px 70px rgb(20 61 55 / 0.2);
  overflow: hidden;
  padding: 1.5rem;
  position: relative;
}

.studio-output-command::after {
  border: 1px solid rgb(255 255 255 / 0.08);
  border-radius: 999px;
  content: "";
  height: 18rem;
  position: absolute;
  right: -7rem;
  top: -10rem;
  width: 18rem;
}

.studio-output-metric {
  align-items: center;
  background: rgb(255 255 255 / 0.07);
  border: 1px solid rgb(255 255 255 / 0.12);
  border-radius: 1.2rem;
  display: flex;
  flex-direction: column;
  min-width: 5.5rem;
  padding: 0.8rem 0.7rem;
}

.studio-output-metric__value {
  color: #fffaf0;
  font-size: 1.05rem;
  font-weight: 900;
}

.studio-output-metric__label {
  color: #a9bcb2;
  font-size: 0.58rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  margin-top: 0.2rem;
  text-transform: uppercase;
}

.studio-pdf-navigation {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1.6rem;
  box-shadow: 0 16px 45px rgb(20 61 55 / 0.07);
  display: grid;
  gap: 0.45rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  padding: 0.45rem;
}

.dark .studio-pdf-navigation {
  background: #07110f;
  border-color: #25443c;
  box-shadow: 0 16px 45px rgb(0 0 0 / 0.24);
}

.studio-pdf-navigation__item {
  border: 1px solid transparent;
  border-radius: 1.2rem;
  color: #6b7b74;
  padding: 0.8rem 0.9rem;
  text-align: left;
  transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, transform 150ms ease;
}

.studio-pdf-navigation__item:hover {
  background: #f8f4ea;
  color: #143d37;
}

.studio-pdf-navigation__item--active {
  background: #143d37;
  border-color: #143d37;
  box-shadow: 0 12px 30px rgb(20 61 55 / 0.16);
  color: #fffaf0;
}

.dark .studio-pdf-navigation__item {
  color: #a9bcb2;
}

.dark .studio-pdf-navigation__item:hover {
  background: #10231f;
  color: #f7f1e7;
}

.dark .studio-pdf-navigation__item--active {
  background: #d9b05f;
  border-color: #d9b05f;
  color: #07110f;
}

.studio-pdf-navigation__label,
.studio-pdf-navigation__description {
  display: block;
}

.studio-pdf-navigation__label {
  font-size: 0.78rem;
  font-weight: 900;
}

.studio-pdf-navigation__description {
  font-size: 0.62rem;
  font-weight: 700;
  line-height: 1.35;
  margin-top: 0.2rem;
  opacity: 0.72;
}

.studio-output-panel {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 2rem;
  box-shadow: 0 20px 55px rgb(20 61 55 / 0.07);
  padding: 1.5rem;
}

.dark .studio-output-panel {
  background: #07110f;
  border-color: #25443c;
  box-shadow: 0 20px 55px rgb(0 0 0 / 0.24);
}

.studio-output-panel__heading {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.studio-output-eyebrow {
  color: #b98a34;
  font-size: 0.62rem;
  font-weight: 900;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}

.dark .studio-output-eyebrow {
  color: #d9b05f;
}

.studio-output-title {
  color: #15231f;
  font-size: 1.25rem;
  font-weight: 900;
  letter-spacing: -0.02em;
  margin-top: 0.55rem;
}

.dark .studio-output-title {
  color: #f7f1e7;
}

.studio-output-description {
  color: #6b7b74;
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.6;
  margin-top: 0.45rem;
  max-width: 44rem;
}

.dark .studio-output-description {
  color: #a9bcb2;
}

.studio-output-action {
  align-items: center;
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 999px;
  color: #143d37;
  display: inline-flex;
  flex-shrink: 0;
  font-size: 0.72rem;
  font-weight: 900;
  gap: 0.45rem;
  min-height: 2.6rem;
  padding: 0.65rem 1rem;
  transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
}

.studio-output-action:hover {
  border-color: #d9b05f;
  box-shadow: 0 12px 28px rgb(20 61 55 / 0.12);
  transform: translateY(-1px);
}

.studio-output-action--primary {
  background: #143d37;
  border-color: #143d37;
  color: white;
}

.dark .studio-output-action {
  background: #10231f;
  border-color: #25443c;
  color: #f7f1e7;
}

.dark .studio-output-action--primary {
  background: #d9b05f;
  border-color: #d9b05f;
  color: #07110f;
}

.studio-surface-card {
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 1.5rem;
  overflow: hidden;
  transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
}

.studio-surface-card:hover {
  border-color: #d9b05f;
  box-shadow: 0 18px 42px rgb(20 61 55 / 0.1);
  transform: translateY(-2px);
}

.dark .studio-surface-card {
  background: #10231f;
  border-color: #25443c;
}

.studio-surface-card__preview {
  background:
    linear-gradient(rgb(20 61 55 / 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgb(20 61 55 / 0.04) 1px, transparent 1px),
    #f1ecdf;
  background-size: 14px 14px;
  padding: 1rem;
}

.dark .studio-surface-card__preview {
  background-color: #07110f;
}

.studio-surface-card__paper {
  aspect-ratio: 1.65 / 1;
  background: white;
  border: 1px solid #e9e0d1;
  border-radius: 0.8rem;
  box-shadow: 0 10px 28px rgb(20 61 55 / 0.1);
  overflow: hidden;
  padding: 1rem;
  position: relative;
}

.studio-surface-card__content {
  color: #15231f;
  font-size: 0.45rem;
  line-height: 1.35;
  max-height: 100%;
  overflow: hidden;
  transform-origin: top left;
}

.studio-surface-card__content--footer {
  bottom: 0.8rem;
  left: 1rem;
  position: absolute;
  right: 1rem;
}

.studio-surface-status {
  background: #ede7dc;
  border-radius: 999px;
  color: #7c6850;
  flex-shrink: 0;
  font-size: 0.55rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  padding: 0.35rem 0.55rem;
  text-transform: uppercase;
}

.studio-surface-status--ready {
  background: #dcebe5;
  color: #143d37;
}

.dark .studio-surface-status {
  background: #07110f;
  color: #a9bcb2;
}

.dark .studio-surface-status--ready {
  background: rgb(217 176 95 / 0.16);
  color: #d9b05f;
}

.studio-surface-edit {
  color: #143d37;
  font-size: 0.68rem;
  font-weight: 900;
}

.studio-surface-edit:hover {
  color: #9a6e23;
}

.dark .studio-surface-edit {
  color: #d9b05f;
}

.studio-background-card,
.studio-table-stage,
.studio-emission-card {
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 1.6rem;
  padding: 1.2rem;
}

.dark .studio-background-card,
.dark .studio-table-stage,
.dark .studio-emission-card {
  background: #10231f;
  border-color: #25443c;
}

.studio-background-card__preview {
  aspect-ratio: 1.25 / 1;
  background:
    linear-gradient(45deg, rgb(20 61 55 / 0.04) 25%, transparent 25%, transparent 75%, rgb(20 61 55 / 0.04) 75%),
    linear-gradient(45deg, rgb(20 61 55 / 0.04) 25%, transparent 25%, transparent 75%, rgb(20 61 55 / 0.04) 75%),
    #fffdf7;
  background-position: 0 0, 10px 10px;
  background-size: 20px 20px;
  border: 1px solid #ded3bf;
  border-radius: 1.25rem;
  overflow: hidden;
}

.dark .studio-background-card__preview {
  background-color: #07110f;
  border-color: #25443c;
}

.studio-compact-field,
.studio-code-field,
.studio-color-field {
  color: #6b7b74;
  display: block;
  font-size: 0.62rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.dark .studio-compact-field,
.dark .studio-code-field,
.dark .studio-color-field {
  color: #a9bcb2;
}

.studio-compact-field :where(input, select),
.studio-code-field :where(input, textarea) {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1rem;
  color: #15231f;
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: normal;
  margin-top: 0.5rem;
  min-height: 2.8rem;
  padding: 0.65rem 0.8rem;
  text-transform: none;
  width: 100%;
}

.studio-code-field textarea {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.7rem;
  line-height: 1.55;
}

.studio-compact-field input[type="range"] {
  min-height: 1rem;
  padding: 0;
}

.dark .studio-compact-field :where(input, select),
.dark .studio-code-field :where(input, textarea) {
  background: #07110f;
  border-color: #25443c;
  color: #f7f1e7;
}

.studio-color-field input {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1rem;
  display: block;
  height: 2.8rem;
  margin-top: 0.5rem;
  padding: 0.3rem;
  width: 100%;
}

.dark .studio-color-field input {
  background: #07110f;
  border-color: #25443c;
}

.studio-danger-action {
  border: 1px solid #fecdd3;
  border-radius: 1rem;
  color: #be123c;
  font-size: 0.7rem;
  font-weight: 900;
  min-height: 2.8rem;
  padding: 0.65rem 0.8rem;
}

.studio-danger-action:hover {
  background: #fff1f2;
}

.dark .studio-danger-action {
  border-color: rgb(244 63 94 / 0.3);
  color: #fda4af;
}

.studio-preset-card,
.studio-renderer-card,
.studio-quality-card {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1.25rem;
  color: #475a53;
  padding: 1rem;
  text-align: left;
  transition: border-color 150ms ease, box-shadow 150ms ease, color 150ms ease, transform 150ms ease;
  width: 100%;
}

.studio-preset-card:hover,
.studio-renderer-card:hover:not(:disabled),
.studio-quality-card:hover {
  border-color: #d9b05f;
  box-shadow: 0 14px 32px rgb(20 61 55 / 0.08);
  color: #143d37;
  transform: translateY(-1px);
}

.studio-preset-card--active,
.studio-renderer-card--active {
  background: #143d37;
  border-color: #143d37;
  color: #fffaf0;
}

.dark .studio-preset-card,
.dark .studio-renderer-card,
.dark .studio-quality-card {
  background: #07110f;
  border-color: #25443c;
  color: #cbd8cf;
}

.dark .studio-preset-card--active,
.dark .studio-renderer-card--active {
  background: #d9b05f;
  border-color: #d9b05f;
  color: #07110f;
}

.studio-renderer-card:disabled {
  cursor: not-allowed;
  opacity: 0.46;
}

.studio-renderer-card__badge {
  background: rgb(217 176 95 / 0.18);
  border-radius: 999px;
  color: #9a6e23;
  flex-shrink: 0;
  font-size: 0.55rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  padding: 0.35rem 0.55rem;
  text-transform: uppercase;
}

.studio-renderer-card--active .studio-renderer-card__badge {
  background: rgb(255 255 255 / 0.14);
  color: inherit;
}

.studio-format-card {
  align-items: center;
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 1.3rem;
  color: #475a53;
  display: flex;
  gap: 0.9rem;
  padding: 0.9rem;
  text-align: left;
  transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
}

.studio-format-card:hover,
.studio-format-card--active {
  border-color: #d9b05f;
  box-shadow: 0 14px 30px rgb(20 61 55 / 0.08);
  transform: translateY(-1px);
}

.studio-format-card--active {
  color: #143d37;
}

.dark .studio-format-card {
  background: #10231f;
  border-color: #25443c;
  color: #cbd8cf;
}

.dark .studio-format-card--active {
  border-color: #d9b05f;
  color: #f7f1e7;
}

.studio-format-card__paper {
  background: white;
  border: 1px solid #ded3bf;
  border-radius: 0.22rem;
  box-shadow: 0 4px 10px rgb(20 61 55 / 0.1);
  flex-shrink: 0;
  height: 2.8rem;
  width: 2rem;
}

.studio-format-card__paper--landscape {
  height: 2rem;
  width: 2.8rem;
}

.studio-page-specimen {
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 1.6rem;
  padding: 1.2rem;
}

.dark .studio-page-specimen {
  background: #10231f;
  border-color: #25443c;
}

.studio-page-specimen__sheet {
  aspect-ratio: 210 / 297;
  background: white;
  border: 1px solid #ded3bf;
  border-radius: 0.8rem;
  box-shadow: 0 16px 40px rgb(20 61 55 / 0.12);
  margin: 0 auto;
  max-height: 15rem;
  position: relative;
}

.studio-page-specimen__sheet--landscape {
  aspect-ratio: 297 / 210;
  margin-top: 2.5rem;
}

.studio-page-specimen__safe {
  border: 1px dashed #d9b05f;
  border-radius: 0.5rem;
  inset: 12%;
  position: absolute;
}

.studio-orientation-button {
  border: 1px solid #ded3bf;
  border-radius: 999px;
  color: #6b7b74;
  font-size: 0.68rem;
  font-weight: 900;
  padding: 0.65rem;
}

.studio-orientation-button--active {
  background: #143d37;
  border-color: #143d37;
  color: white;
}

.dark .studio-orientation-button {
  border-color: #25443c;
  color: #a9bcb2;
}

.dark .studio-orientation-button--active {
  background: #d9b05f;
  border-color: #d9b05f;
  color: #07110f;
}

.studio-output-warning {
  background: #fff8e7;
  border: 1px solid #ead9ae;
  border-radius: 1.3rem;
  color: #7c5b1e;
  padding: 1rem;
}

.dark .studio-output-warning {
  background: rgb(217 176 95 / 0.1);
  border-color: rgb(217 176 95 / 0.28);
  color: #f1d89e;
}

.studio-setup-field {
  color: #6b7b74;
  display: block;
  font-size: 0.62rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.studio-setup-field :where(input, select, textarea) {
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 1.1rem;
  color: #15231f;
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: normal;
  margin-top: 0.55rem;
  min-height: 3.1rem;
  padding: 0.75rem 0.9rem;
  text-transform: none;
  width: 100%;
}

.studio-setup-field textarea {
  line-height: 1.6;
  resize: vertical;
}

.studio-setup-field :where(input, textarea)::placeholder {
  color: #93a099;
  font-weight: 600;
}

.dark .studio-setup-field {
  color: #a9bcb2;
}

.dark .studio-setup-field :where(input, select, textarea) {
  background: #10231f;
  border-color: #25443c;
  color: #f7f1e7;
}

.studio-setup-field__error {
  color: #be123c;
  display: block;
  font-size: 0.65rem;
  letter-spacing: normal;
  margin-top: 0.45rem;
  text-transform: none;
}

.studio-lifecycle-card {
  background:
    radial-gradient(circle at top right, rgb(217 176 95 / 0.18), transparent 38%),
    #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 2rem;
  box-shadow: 0 20px 55px rgb(20 61 55 / 0.07);
  padding: 1.5rem;
}

.dark .studio-lifecycle-card {
  background:
    radial-gradient(circle at top right, rgb(217 176 95 / 0.1), transparent 38%),
    #10231f;
  border-color: #25443c;
}

.studio-lifecycle-option,
.studio-default-toggle {
  align-items: flex-start;
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 1.2rem;
  color: #475a53;
  display: flex;
  gap: 0.8rem;
  padding: 0.9rem;
  text-align: left;
  transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
  width: 100%;
}

.studio-lifecycle-option:hover,
.studio-lifecycle-option--active {
  border-color: #d9b05f;
  box-shadow: 0 12px 28px rgb(20 61 55 / 0.08);
  transform: translateY(-1px);
}

.studio-lifecycle-option--active {
  color: #143d37;
}

.dark .studio-lifecycle-option,
.dark .studio-default-toggle {
  background: #07110f;
  border-color: #25443c;
  color: #cbd8cf;
}

.dark .studio-lifecycle-option--active {
  border-color: #d9b05f;
  color: #f7f1e7;
}

.studio-lifecycle-option__mark {
  border: 2px solid #cfc3ae;
  border-radius: 999px;
  flex-shrink: 0;
  height: 0.9rem;
  margin-top: 0.2rem;
  position: relative;
  width: 0.9rem;
}

.studio-lifecycle-option--active .studio-lifecycle-option__mark {
  border-color: #143d37;
}

.studio-lifecycle-option--active .studio-lifecycle-option__mark::after {
  background: #d9b05f;
  border-radius: inherit;
  content: "";
  inset: 2px;
  position: absolute;
}

.dark .studio-lifecycle-option--active .studio-lifecycle-option__mark {
  border-color: #d9b05f;
}

.studio-default-toggle {
  cursor: pointer;
}

.studio-default-toggle input {
  accent-color: #143d37;
  flex-shrink: 0;
  height: 1rem;
  margin-top: 0.2rem;
  width: 1rem;
}

.dark .studio-default-toggle input {
  accent-color: #d9b05f;
}

.studio-theme-badge {
  background: rgb(217 176 95 / 0.16);
  border: 1px solid rgb(217 176 95 / 0.4);
  border-radius: 999px;
  color: #8a641f;
  flex-shrink: 0;
  font-size: 0.62rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  padding: 0.5rem 0.75rem;
  text-transform: uppercase;
}

.dark .studio-theme-badge {
  color: #f1d89e;
}

.studio-theme-card {
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 1.4rem;
  color: #475a53;
  padding: 0.85rem;
  text-align: left;
  transition: border-color 150ms ease, box-shadow 150ms ease, color 150ms ease, transform 150ms ease;
}

.studio-theme-card:hover,
.studio-theme-card--active {
  border-color: #d9b05f;
  box-shadow: 0 16px 36px rgb(20 61 55 / 0.09);
  color: #143d37;
  transform: translateY(-2px);
}

.dark .studio-theme-card {
  background: #10231f;
  border-color: #25443c;
  color: #cbd8cf;
}

.dark .studio-theme-card--active {
  border-color: #d9b05f;
  color: #f7f1e7;
}

.studio-theme-card__specimen {
  background: white;
  border: 1px solid #e7dece;
  border-radius: 0.95rem;
  box-shadow: 0 10px 24px rgb(20 61 55 / 0.08);
  display: block;
  min-height: 7.5rem;
  padding: 0.8rem;
}

.studio-theme-card__accent {
  border-radius: 999px;
  display: block;
  height: 0.45rem;
  width: 58%;
}

.studio-theme-card__line {
  background: #e7e0d5;
  border-radius: 999px;
  display: block;
  height: 0.3rem;
  margin-top: 0.55rem;
  width: 74%;
}

.studio-theme-card__line--strong {
  background: #aab9b2;
  height: 0.45rem;
  margin-top: 0.8rem;
  width: 48%;
}

.studio-theme-card__table {
  border: 1px solid #ded3bf;
  border-radius: 0.35rem;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 0.85rem;
  overflow: hidden;
}

.studio-theme-card__table span {
  border-right: 1px solid #ded3bf;
  display: block;
  height: 1.6rem;
}

.studio-theme-card__table span:last-child {
  border-right: 0;
}

.studio-preview-workspace {
  background: #fffdf7;
  border: 1px solid #ded3bf;
  border-radius: 2rem;
  box-shadow: 0 22px 65px rgb(20 61 55 / 0.09);
  padding: 1.25rem;
}

.dark .studio-preview-workspace {
  background: #07110f;
  border-color: #25443c;
  box-shadow: 0 22px 65px rgb(0 0 0 / 0.3);
}

.studio-preview-header,
.studio-preview-pagebar {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.studio-preview-header {
  border-bottom: 1px solid #ded3bf;
  padding: 0.35rem 0.35rem 1rem;
}

.dark .studio-preview-header {
  border-color: #25443c;
}

.studio-preview-toggle {
  align-items: center;
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 999px;
  display: inline-flex;
  padding: 0.25rem;
}

.dark .studio-preview-toggle {
  background: #10231f;
  border-color: #25443c;
}

.studio-preview-toggle__item {
  border-radius: 999px;
  color: #6b7b74;
  font-size: 0.66rem;
  font-weight: 900;
  min-height: 2.1rem;
  padding: 0.45rem 0.7rem;
  transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease;
}

.studio-preview-toggle__item:hover {
  color: #143d37;
}

.studio-preview-toggle__item--active {
  background: #143d37;
  box-shadow: 0 8px 20px rgb(20 61 55 / 0.16);
  color: white;
}

.dark .studio-preview-toggle__item {
  color: #a9bcb2;
}

.dark .studio-preview-toggle__item--active {
  background: #d9b05f;
  color: #07110f;
}

.studio-preview-pagebar {
  border-bottom: 1px solid #ded3bf;
  padding: 0.8rem 0.35rem;
}

.dark .studio-preview-pagebar {
  border-color: #25443c;
}

.studio-preview-page-chip {
  align-items: center;
  background: #f8f4ea;
  border: 1px solid #ded3bf;
  border-radius: 999px;
  color: #6b7b74;
  display: inline-flex;
  flex-shrink: 0;
  font-size: 0.62rem;
  font-weight: 900;
  gap: 0.45rem;
  min-height: 2.1rem;
  padding: 0.4rem 0.7rem;
  transition: border-color 150ms ease, color 150ms ease, transform 150ms ease;
}

.studio-preview-page-chip:hover,
.studio-preview-page-chip--active {
  border-color: #d9b05f;
  color: #143d37;
  transform: translateY(-1px);
}

.dark .studio-preview-page-chip {
  background: #10231f;
  border-color: #25443c;
  color: #a9bcb2;
}

.dark .studio-preview-page-chip--active {
  border-color: #d9b05f;
  color: #f7f1e7;
}

.studio-preview-page-chip__paper {
  background: white;
  border: 1px solid #ded3bf;
  border-radius: 0.12rem;
  height: 0.9rem;
  width: 0.65rem;
}

.studio-preview-stage {
  background:
    linear-gradient(rgb(20 61 55 / 0.035) 1px, transparent 1px),
    linear-gradient(90deg, rgb(20 61 55 / 0.035) 1px, transparent 1px),
    #f1ecdf;
  background-size: 20px 20px;
  border: 1px solid #ded3bf;
  border-radius: 1.6rem;
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.65);
  display: grid;
  gap: 1.25rem;
  margin-top: 1rem;
  overflow: auto;
  padding: clamp(1rem, 2.4vw, 2rem);
}

.dark .studio-preview-stage {
  background:
    linear-gradient(rgb(217 176 95 / 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgb(217 176 95 / 0.04) 1px, transparent 1px),
    #10231f;
  background-size: 20px 20px;
  border-color: #25443c;
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 0.04);
}

@media (max-width: 767px) {
  .studio-canvas-toolbar,
  .studio-context-bar {
    align-items: stretch;
    flex-direction: column;
  }

  .studio-pdf-navigation {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .studio-output-panel__heading {
    align-items: stretch;
    flex-direction: column;
  }

  .studio-preview-header,
  .studio-preview-pagebar {
    align-items: stretch;
    flex-direction: column;
  }

  .studio-output-action {
    justify-content: center;
    width: 100%;
  }
}

.report-studio-shell :where(input, select, textarea, button) {
  font-family: inherit;
}

.report-studio-shell :where([class*="border-slate-"], [class*="border-gray-"]):not(.studio-preview-document, .studio-preview-document *) {
  border-color: var(--studio-border) !important;
}

.dark .report-studio-shell :where([class*="border-slate-"], [class*="border-gray-"]):not(.studio-preview-document, .studio-preview-document *) {
  border-color: var(--studio-border-dark) !important;
}

.report-studio-shell :where(.bg-white, [class*="bg-white/"]):not(.studio-preview-document, .studio-preview-document *) {
  background-color: color-mix(in srgb, var(--studio-surface) 92%, white) !important;
}

.report-studio-shell :where([class*="bg-slate-50"], [class*="bg-slate-100"], [class*="bg-gray-50"], [class*="bg-gray-100"]):not(.studio-preview-document, .studio-preview-document *) {
  background-color: var(--studio-soft) !important;
}

.dark .report-studio-shell :where(.bg-white, [class*="bg-white/"], [class*="bg-slate-50"], [class*="bg-slate-100"], [class*="bg-slate-900"], [class*="bg-slate-950"], [class*="bg-gray-50"], [class*="bg-gray-100"]):not(.studio-preview-document, .studio-preview-document *) {
  background-color: var(--studio-surface-dark-soft) !important;
}

.report-studio-shell :where([class*="text-slate-950"], [class*="text-slate-900"], [class*="text-slate-800"], [class*="text-gray-900"]):not(.studio-preview-document, .studio-preview-document *) {
  color: var(--studio-ink) !important;
}

.report-studio-shell :where([class*="text-slate-700"], [class*="text-slate-600"], [class*="text-slate-500"], [class*="text-slate-400"], [class*="text-gray-700"], [class*="text-gray-600"], [class*="text-gray-500"], [class*="text-gray-400"]):not(.studio-preview-document, .studio-preview-document *) {
  color: var(--studio-muted) !important;
}

.dark .report-studio-shell :where([class*="text-slate-950"], [class*="text-slate-900"], [class*="text-slate-800"], [class*="text-slate-700"], [class*="text-slate-600"], [class*="text-slate-500"], [class*="text-slate-400"], [class*="text-gray-900"], [class*="text-gray-700"], [class*="text-gray-600"], [class*="text-gray-500"], [class*="text-gray-400"]):not(.studio-preview-document, .studio-preview-document *) {
  color: #f7f1e7 !important;
}

.report-studio-shell :where(button[class*="border-slate-"], button[class*="border-gray-"], a[class*="border-slate-"], a[class*="border-gray-"]):not(.studio-preview-document, .studio-preview-document *) {
  background: color-mix(in srgb, var(--studio-surface) 88%, white) !important;
  box-shadow: 0 12px 28px rgb(var(--primary-900-rgb) / 0.06);
}

.report-studio-shell :where(button[class*="border-slate-"], button[class*="border-gray-"], a[class*="border-slate-"], a[class*="border-gray-"]):not(.studio-preview-document, .studio-preview-document *):hover {
  border-color: rgb(var(--primary-300-rgb)) !important;
  background: var(--studio-surface-strong) !important;
}

.dark .report-studio-shell :where(button[class*="border-slate-"], button[class*="border-gray-"], a[class*="border-slate-"], a[class*="border-gray-"]):not(.studio-preview-document, .studio-preview-document *) {
  background: var(--studio-surface-dark-soft) !important;
  box-shadow: 0 12px 28px rgb(0 0 0 / 0.22);
}

.report-studio-shell :where(.rounded-3xl, .rounded-\[2rem\]):not(.studio-preview-document, .studio-preview-document *) {
  backdrop-filter: saturate(1.08);
}

.report-studio-shell :where(input, select, textarea):not(.studio-preview-document *) {
  border-color: #ded3bf;
  background-color: #fffdf7;
  color: #15231f;
}

.dark .report-studio-shell :where(input, select, textarea):not(.studio-preview-document *) {
  border-color: #25443c;
  background-color: #10231f;
  color: #f7f1e7;
}

.report-studio-shell :where(input, select, textarea):not(.studio-preview-document *):focus {
  border-color: rgb(var(--primary-500-rgb));
  box-shadow: 0 0 0 3px rgb(var(--primary-500-rgb) / 0.18);
}

.report-studio-shell .studio-toolbar-select {
  background-color: transparent;
  border: 0;
  box-shadow: none;
  color: #143d37;
}

.report-studio-shell .studio-toolbar-select:focus {
  border: 0;
  box-shadow: none;
}

.dark .report-studio-shell .studio-toolbar-select {
  background-color: transparent;
  color: #f7f1e7;
}

.report-studio-swiper::part(button-prev),
.report-studio-swiper::part(button-next) {
  background: transparent;
  border: 0;
  box-shadow: none;
  color: rgb(var(--primary-800-rgb));
  display: grid;
  font-size: 12px;
  height: 2rem;
  line-height: 1;
  place-items: center;
  transition: color 150ms ease, opacity 150ms ease, transform 150ms ease;
  width: 2rem;
}

.report-studio-swiper::part(button-prev):hover,
.report-studio-swiper::part(button-next):hover {
  color: rgb(var(--primary-950-rgb));
  transform: scale(1.08);
}

.report-studio-swiper::part(button-prev)::after,
.report-studio-swiper::part(button-next)::after {
  font-size: 12px;
  font-weight: 800;
  line-height: 1;
}

.dark .report-studio-swiper::part(button-prev),
.dark .report-studio-swiper::part(button-next) {
  background: transparent;
  color: #f7f1e7;
}

.dark .report-studio-swiper::part(button-prev):hover,
.dark .report-studio-swiper::part(button-next):hover {
  color: #ffffff;
}

.report-studio-swiper::part(pagination) {
  bottom: 0;
}
</style>
