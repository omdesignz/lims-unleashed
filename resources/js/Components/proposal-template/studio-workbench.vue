<script setup>
import fancyTextarea from '@/Components/fancy-textarea.vue'
import {
  proposalTemplatePageSizeCatalog,
  proposalTemplatePreviewMarginStyle,
  proposalTemplatePreviewPageDimensions,
  proposalTemplatePreviewPageFormatLabel,
} from '@/Support/proposal-template-preview-geometry.mjs'
import { generatedStudioChartSvg, normalizedHexColor, sanitizeStudioChartSvg } from '@/Support/report-studio-chart-palette.mjs'
import { imagePositionCoordinates, imagePositionStringFromCoordinates } from '@/Support/report-studio-image-position.mjs'
import { uploadedStudioAssetKind } from '@/Support/report-studio-media-assets.mjs'
import { interpolateStudioPreviewHtml, studioThemeColorReplacements } from '@/Support/report-studio-preview-html.mjs'
import { escapePreviewHtmlAttribute, escapePreviewHtmlText, safePreviewCssUrl, safePreviewMediaUrl } from '@/Support/report-studio-preview-safety.mjs'
import { generatedStudioQrCodeDataUri } from '@/Support/report-studio-qr-code.mjs'
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
  ArrowUturnLeftIcon,
  DocumentDuplicateIcon,
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
  presetActionLabel: {
    type: String,
    default: '',
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
  assetLibrary: {
    type: Array,
    default: () => [],
  },
  draftPreviewHref: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['submit', 'select-preset'])
const page = usePage()

const contentLength = computed(() => props.form.content?.length ?? 0)
const mediaAssetUrl = ref(props.layoutSchema.background_image_path || '')
const snippetTarget = ref('content')
const previewMode = ref('first-page')
const previewDisplayMode = ref('paged')
const activePreviewPage = ref(1)
const selectedCanvasBlockId = ref(null)
const interactionState = ref(null)
const focalDragState = ref(null)
const alignmentGuides = ref({ x: [], y: [] })
const showCanvasGrid = ref(true)
const showSafeArea = ref(true)
const snapToGrid = ref(true)
const gridSize = ref(4)
const mediaPickerOpen = ref(false)
const mediaPickerTarget = ref({ scope: 'asset-url', field: 'image_url' })
const mediaPickerSearch = ref('')
const mediaPickerKind = ref('all')
const localAssetLibrary = ref([...props.assetLibrary])
const mediaPickerUploadInput = ref(null)
const mediaPickerUploadBusy = ref(false)
const mediaPickerUploadDragging = ref(false)
const mediaPickerUploadProgress = ref(0)
const mediaPickerUploadError = ref('')
const draftPreviewBusy = ref(false)
const draftPreviewError = ref('')

const allowedMediaMimeTypes = [
  'image/svg+xml',
  'image/png',
  'image/jpeg',
  'image/webp',
  'image/gif',
  'image/avif',
]

const defaultChartPalette = ['#143d37', '#d9b05f', '#0f766e', '#475569', '#7c2d12', '#3f6f58']

const chartTypeOptions = [
  { value: 'bar', label: 'Barras' },
  { value: 'line', label: 'Linha' },
  { value: 'doughnut', label: 'Anel' },
]

const snippetTargetOptions = [
  { value: 'content', label: 'Corpo da proposta' },
  { value: 'first_page_header_html', label: 'Cabeçalho da primeira página' },
  { value: 'default_header_html', label: 'Cabeçalho padrão' },
  { value: 'footer_html', label: 'Rodapé' },
  { value: 'styles_css', label: 'CSS adicional' },
]

const backgroundFitOptions = [
  { value: 'cover', label: 'Cobrir página' },
  { value: 'contain', label: 'Conter' },
  { value: 'auto', label: 'Tamanho original' },
]

const backgroundPositionOptions = [
  { value: 'center center', label: 'Centro' },
  { value: 'top center', label: 'Topo ao centro' },
  { value: 'top left', label: 'Topo à esquerda' },
  { value: 'top right', label: 'Topo à direita' },
  { value: 'bottom center', label: 'Base ao centro' },
]

const imagePositionOptions = [
  { value: 'center center', label: 'Centro' },
  { value: 'top center', label: 'Topo' },
  { value: 'bottom center', label: 'Base' },
  { value: 'center left', label: 'Esquerda' },
  { value: 'center right', label: 'Direita' },
]

const backgroundRepeatOptions = [
  { value: 'no-repeat', label: 'Sem repetição' },
  { value: 'repeat-x', label: 'Repetir horizontalmente' },
  { value: 'repeat-y', label: 'Repetir verticalmente' },
  { value: 'repeat', label: 'Repetir em mosaico' },
]

const themePresetOptions = [
  { value: 'corporate', label: 'Corporativo premium' },
  { value: 'compliance', label: 'Conformidade / ISO' },
  { value: 'field', label: 'Recolha e operações' },
  { value: 'minimal', label: 'Editorial minimalista' },
]

const presetCategoryLabels = {
  compliance: 'Conformidade ISO 17025',
  'field-services': 'Recolha e logística',
  chemical: 'Química',
  microbiology: 'Microbiologia',
  physical: 'Física',
  environmental: 'Ambiental',
  food: 'Alimentos',
  general: 'Geral',
}

const pageSizeCatalog = proposalTemplatePageSizeCatalog

const pageFormatOptions = [
  { value: 'A4', label: 'A4' },
  { value: 'Letter', label: 'Letter' },
  { value: 'Legal', label: 'Legal' },
  { value: 'custom', label: 'Personalizado' },
]

const orientationOptions = [
  { value: 'P', label: 'Vertical' },
  { value: 'L', label: 'Horizontal' },
]

const documentFontOptions = [
  { value: '"Century Gothic", DejaVu Sans, sans-serif', label: 'Century Gothic' },
  { value: 'Aptos, DejaVu Sans, sans-serif', label: 'Aptos' },
  { value: 'Avenir Next, DejaVu Sans, sans-serif', label: 'Avenir Next' },
  { value: 'Georgia, DejaVu Serif, serif', label: 'Georgia editorial' },
  { value: 'DejaVu Sans, sans-serif', label: 'DejaVu Sans' },
]

const canvasSurfaceOptions = [
  { value: 'content', label: 'Corpo da proposta' },
  { value: 'first_page_header_html', label: 'Cabeçalho da primeira página' },
  { value: 'default_header_html', label: 'Cabeçalho padrão' },
  { value: 'footer_html', label: 'Rodapé' },
]

const canvasContentScopeOptions = [
  { value: 'first', label: 'Primeira página' },
  { value: 'all', label: 'Todas as páginas' },
  { value: 'following', label: 'Páginas seguintes' },
  { value: 'specific', label: 'Página específica' },
]

const canvasLayoutPresets = [
  {
    key: 'hero',
    label: 'Capa',
    description: 'Faixa superior ampla para capa.',
    values: { x: 0, y: 0, width: 100, min_height: 140, padding: 24, border_radius: 28 },
  },
  {
    key: 'badge',
    label: 'Selo',
    description: 'Chip ou badge destacado no canto.',
    values: { x: 68, y: 6, width: 26, min_height: 24, padding: 10, border_radius: 999 },
  },
  {
    key: 'sidebar',
    label: 'Lateral',
    description: 'Bloco vertical lateral para destaques.',
    values: { x: 68, y: 16, width: 28, min_height: 180, padding: 18, border_radius: 20 },
  },
  {
    key: 'callout',
    label: 'Destaque',
    description: 'Destaque intermédio dentro do conteúdo.',
    values: { x: 8, y: 18, width: 84, min_height: 96, padding: 18, border_radius: 22 },
  },
]

const themeCatalog = {
  corporate: {
    accent: 'from-slate-950 via-primary-900 to-primary-700',
    badge: 'Operacional',
    description: 'Visual executivo com capa forte, contraste alto e tabelas comerciais mais sóbrias.',
    styles: [
      'body{font-family:"Century Gothic",DejaVu Sans,sans-serif;color:#143d37;font-size:11px;line-height:1.6;}',
      'h1{font-size:28px;line-height:1.15;color:#143d37;margin:0 0 18px;font-weight:700;}',
      'h2{font-size:16px;color:#143d37;margin:24px 0 10px;font-weight:700;}',
      'p{margin:0 0 10px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th{background:#143d37;color:#fffdf7;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;}',
      'th,td{border:1px solid #cbd5e1;padding:8px;vertical-align:top;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #143d37;padding-bottom:12px;"><div><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#9a7a2f;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;color:#143d37;">Proposta {{document_code}}</h2></div><div style="text-align:right;font-size:10px;color:#58665f;"><div>{{customer_name}}</div><div>{{issue_date}}</div></div></div>',
    defaultHeaderHtml: '<div style="display:flex;justify-content:space-between;font-size:10px;color:#475569;"><span>{{document_code}}</span><span>{{customer_name}}</span></div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#334155;border-top:1px solid #cbd5e1;padding-top:6px;"><span>Documento controlado</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
  compliance: {
    accent: 'from-emerald-950 via-emerald-800 to-cyan-700',
    badge: 'ISO 17025',
    description: 'Ênfase na decisão, rastreabilidade, incerteza e linguagem mais regulatória.',
    styles: [
      'body{font-family:"Century Gothic",DejaVu Sans,sans-serif;color:#052e16;font-size:11px;line-height:1.65;}',
      'h1{font-size:26px;color:#14532d;margin:0 0 18px;font-weight:700;}',
      'h2{font-size:15px;color:#14532d;margin:22px 0 10px;font-weight:700;}',
      'p{margin:0 0 10px;}',
      'ul{padding-left:18px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th{background:#14532d;color:#ffffff;font-size:10px;letter-spacing:0.05em;text-transform:uppercase;}',
      'th,td{border:1px solid #bbf7d0;padding:8px;vertical-align:top;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="border-bottom:2px solid #14532d;padding-bottom:12px;"><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#166534;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;color:#14532d;">Proposta técnica {{document_code}}</h2><p style="margin:8px 0 0;font-size:10px;color:#166534;">Decisão, conformidade e rastreabilidade documental</p></div>',
    defaultHeaderHtml: '<div style="display:flex;justify-content:space-between;font-size:10px;color:#166534;"><span>{{document_code}}</span><span>{{customer_name}}</span></div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#166534;border-top:1px solid #86efac;padding-top:6px;"><span>Regra de decisão e rastreabilidade</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
  field: {
    accent: 'from-amber-950 via-amber-700 to-orange-600',
    badge: 'Campo e logística',
    description: 'Melhor para recolha, cadeia de custódia, recepção e operação fora do laboratório.',
    styles: [
      'body{font-family:"Century Gothic",DejaVu Sans,sans-serif;color:#7c2d12;font-size:11px;line-height:1.65;}',
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
    accent: 'from-slate-900 via-slate-700 to-slate-500',
    badge: 'Editorial',
    description: 'Superfícies limpas, muito espaço em branco e menos ruído visual.',
    styles: [
      'body{font-family:"Century Gothic",DejaVu Sans,sans-serif;color:#111827;font-size:11px;line-height:1.7;}',
      'h1{font-size:30px;color:#111827;margin:0 0 22px;font-weight:600;}',
      'h2{font-size:15px;color:#111827;margin:24px 0 10px;font-weight:600;}',
      'p{margin:0 0 10px;}',
      'table{width:100%;border-collapse:collapse;}',
      'th,td{border-bottom:1px solid #d1d5db;padding:8px 4px;vertical-align:top;}',
      'th{font-size:10px;color:#6b7280;letter-spacing:0.06em;text-transform:uppercase;}',
    ].join(''),
    firstPageHeaderHtml: '<div style="padding-bottom:12px;border-bottom:1px solid #d1d5db;"><div style="font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#6b7280;">{{lab_name}}</div><h2 style="margin:10px 0 0;font-size:18px;">{{document_code}}</h2></div>',
    defaultHeaderHtml: '<div style="font-size:10px;color:#6b7280;">{{document_code}} · {{customer_name}}</div>',
    footerHtml: '<div style="display:flex;justify-content:space-between;font-size:9px;color:#6b7280;border-top:1px solid #d1d5db;padding-top:6px;"><span>Documento controlado</span><span>Página {PAGENO}/{nbpg}</span></div>',
  },
}

const surfaceBindings = computed(() => ({
  content: {
    get value() {
      return props.form.content || ''
    },
    set value(value) {
      props.form.content = value
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
  '{proposal_number}': 'Número da proposta',
  '{issue_date}': 'Data de emissão',
  '{{issue_date}}': 'Data de emissão',
  '{customer_name}': 'Cliente',
  '{{customer_name}}': 'Cliente',
  '{lab_name}': 'Laboratório',
  '{{lab_name}}': 'Laboratório',
  '{lab_details}': 'Dados do laboratório',
  '{customer_details}': 'Dados do cliente',
  '{banking_details}': 'Dados bancários',
  '{document_keywords}': 'Palavras-chave',
  '{bank_name}': 'Banco',
  '{bank_account_name}': 'Titular da conta',
  '{bank_account_number}': 'Número da conta',
  '{bank_iban}': 'IBAN',
  '{bank_swift}': 'SWIFT/BIC',
  '{bank_details}': 'Observações bancárias',
  '{service_location}': 'Local do serviço',
  '{expiry_date}': 'Validade',
  '{items_table}': 'Tabela de itens',
  '{items_list}': 'Lista de itens',
  '{summary_table}': 'Resumo financeiro',
  '{observations}': 'Observações',
  '{decision_rule}': 'Regra de decisão',
  '{signature_block}': 'Bloco de assinatura',
}

const translatedPlaceholders = computed(() => props.placeholders.map((placeholder) => ({
  value: placeholder,
  label: placeholderLabels[placeholder] ?? placeholder.replace(/[{}]/g, '').replaceAll('_', ' '),
})))

const assetLibraryItems = computed(() => localAssetLibrary.value)

const signatureAssets = computed(() => assetLibraryItems.value.filter((asset) => asset.kind === 'profile_signature' || String(asset.source || '').toLowerCase().includes('assinatura')))

function mediaAssetDocumentUrl(asset) {
  return asset?.pdf_url || asset?.url || ''
}

const mediaKindLabelMap = {
  all: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.all'),
  gallery_image: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.gallery_image'),
  profile_signature: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.profile_signature'),
  uploaded_asset: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.uploaded_asset'),
  uploaded_background: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.uploaded_background'),
  uploaded_chart: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.uploaded_chart'),
  uploaded_image: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.uploaded_image'),
  uploaded_signature: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.uploaded_signature'),
  uploaded_stamp: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.uploaded_stamp'),
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
  return mediaKindLabelMap[kind] || String(kind || trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.file'))
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const mediaKindOptions = computed(() => [
  { value: 'all', label: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.kinds.all'), count: assetLibraryItems.value.length },
  ...[...new Set(assetLibraryItems.value.map((asset) => mediaKindValue(asset)))]
    .map((kind) => ({
      value: kind,
      label: mediaKindLabel(kind),
      count: assetLibraryItems.value.filter((asset) => mediaKindValue(asset) === kind).length,
    })),
])

const filteredMediaAssets = computed(() => {
  const query = mediaPickerSearch.value.trim().toLowerCase()

  return assetLibraryItems.value.filter((asset) => {
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
      asset.url,
    ].some((value) => String(value || '').toLowerCase().includes(query))
  })
})

const mediaFieldLabelMap = {
  image_url: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.fields.image_url'),
  background_image: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.fields.background_image'),
  signature_image: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.fields.signature_image'),
  chart_image_url: trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.fields.chart_image_url'),
}

function mediaFieldLabel(field) {
  return mediaFieldLabelMap[field] || String(field || '').replaceAll('_', ' ')
}

const mediaPickerTargetLabel = computed(() => {
  if (mediaPickerTarget.value.scope === 'document-background') {
    return trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.target.document_background')
  }

  if (mediaPickerTarget.value.scope === 'asset-url') {
    return trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.target.asset_url')
  }

  if (mediaPickerTarget.value.scope === 'selected-block') {
    return trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.target.selected_block', {
      field: mediaFieldLabel(mediaPickerTarget.value.field),
    })
  }

  return trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.target.selected')
})

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

const canvasBlockGroups = computed(() => {
  return canvasSurfaceOptions.map((surface) => ({
    ...surface,
    blocks: canvasBlocks.value
      .filter((block) => block.surface === surface.value)
      .sort((left, right) => Number(right.z_index || 0) - Number(left.z_index || 0)),
  })).filter((group) => group.blocks.length > 0)
})

const basePreviewReplacementMap = {
  bank_iban: 'AO06 0000 0000 0000 0000 0000 0',
  bank_name: 'Banco de referência',
  bank_account_name: 'Laboratório de referência',
  bank_account_number: '0011223344',
  bank_swift: 'BFAAAOLU',
  bank_details: 'Pagamento por transferência bancária com referência da proposta.',
  banking_details: '<strong>Banco de referência</strong><br />IBAN AO06 0000 0000 0000 0000 0000 0',
  customer_code: 'CL-019',
  customer_details: '<strong>Cliente industrial</strong><br />NIF 5000000000 · Luanda',
  customer_name: 'Cliente industrial',
  decision_rule: 'A decisão é emitida de acordo com o âmbito e os critérios acordados.',
  document_code: 'PR-2026-001',
  document_keywords: 'proposta · ISO 17025 · rastreabilidade',
  expiry_date: '03/06/2026',
  issue_date: '04/05/2026',
  items_list: 'Ensaios laboratoriais',
  items_table: '<table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px;">Serviço</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Ensaios laboratoriais</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 25.000,00</td></tr></table>',
  lab_details: '<strong>Laboratório Central</strong><br />Luanda · laboratório de ensaios',
  lab_name: 'Laboratório Central',
  observations: 'Condições comerciais e técnicas acordadas com o cliente.',
  proposal_number: 'PR-2026-001',
  service_location: 'Luanda',
  signature_block: '<div style="margin-top:24px; border-top:1px solid #143d37; padding-top:10px;"><strong>Direcção comercial</strong><br />Validação da proposta</div>',
  summary_table: '<table style="width:100%; border-collapse:collapse;"><tr><td style="border:1px solid #cbd5e1; padding:6px;">Subtotal</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right;">AOA 25.000,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Total</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right; font-weight:700;">AOA 25.000,00</td></tr></table>',
}

const previewThemeColorReplacements = computed(() => studioThemeColorReplacements(page.props?.settings || {}))

const previewReplacementMap = computed(() => ({
  ...basePreviewReplacementMap,
  ...previewThemeColorReplacements.value,
}))

function interpolatePreviewHtml(value, scopedReplacements = {}) {
  return interpolateStudioPreviewHtml(value, {
    ...previewReplacementMap.value,
    ...scopedReplacements,
  })
}

const contentPreview = computed(() => interpolatePreviewHtml(props.form.content))
const pageBreakTagPattern = /<pagebreak\b[^>]*\/?>/i

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

function normalizeCanvasBlockPlacement(block, fallbackPageNumber = currentPreviewPage.value || 1) {
  if (!block) {
    return
  }

  if (!canvasSurfaceOptions.some((option) => option.value === block.surface)) {
    block.surface = 'content'
  }

  if (block.surface !== 'content') {
    block.page_scope = 'all'
    block.page_number = null

    return
  }

  block.page_scope = block.page_scope || 'first'

  if (block.page_scope === 'specific') {
    const pageNumber = Number(block.page_number)
    block.page_number = Number.isInteger(pageNumber) && pageNumber >= 1
      ? pageNumber
      : fallbackPageNumber

    return
  }

  block.page_number = null
}

function normalizeSelectedCanvasBlockPlacement(block = selectedCanvasBlock.value) {
  normalizeCanvasBlockPlacement(block)
}

function normalizeCanvasBlockPlacements() {
  canvasBlocks.value.forEach((block) => normalizeCanvasBlockPlacement(block))
}

watch(() => [
  selectedCanvasBlock.value?.id,
  selectedCanvasBlock.value?.surface,
  selectedCanvasBlock.value?.page_scope,
  selectedCanvasBlock.value?.page_number,
  currentPreviewPage.value,
], () => normalizeSelectedCanvasBlockPlacement(), { immediate: true })

const previewPageStyle = computed(() => {
  const image = safePreviewCssUrl(props.layoutSchema.background_image_path)
  const baseStyle = {
    backgroundColor: safeCssColorValue(props.layoutSchema.page_background_color, '#fffdf7'),
    fontFamily: props.layoutSchema.document_font_family || '"Century Gothic", DejaVu Sans, sans-serif',
  }

  return image
    ? {
        ...baseStyle,
        backgroundImage: image,
        backgroundSize: safeCssImageFitValue(props.layoutSchema.background_size, 'cover'),
        backgroundPosition: safeCssPositionValue(props.layoutSchema.background_position, 'center center'),
        backgroundRepeat: safeCssRepeatValue(props.layoutSchema.background_repeat, 'no-repeat'),
      }
    : baseStyle
})

const previewPageDimensions = computed(() => proposalTemplatePreviewPageDimensions(props.exportSettings))
const previewPageFormatLabel = computed(() => proposalTemplatePreviewPageFormatLabel(props.exportSettings))

const previewPageFrameStyle = computed(() => {
  const dimensions = previewPageDimensions.value
  const maxWidth = props.exportSettings.orientation === 'L' ? 1080 : 860

  return {
    ...previewPageStyle.value,
    maxWidth: `${maxWidth}px`,
    aspectRatio: `${dimensions.width} / ${dimensions.height}`,
  }
})

function previewMarginStyleForPage(pageNumber) {
  return proposalTemplatePreviewMarginStyle(props.exportSettings, pageNumber)
}

const previewHeaderHtml = computed(() => {
  return interpolatePreviewHtml(previewMode.value === 'first-page'
    ? props.layoutSchema.first_page_header_html
    : props.layoutSchema.default_header_html)
})

const previewMeta = computed(() => {
  return previewMode.value === 'first-page'
    ? {
        title: 'Primeira página',
        subtitle: 'Capa, destaque visual e enquadramento inicial.',
      }
    : {
        title: 'Páginas seguintes',
        subtitle: 'Continuação com cabeçalho padrão e paginação recorrente.',
    }
})

const previewSurfaceKey = computed(() => {
  return previewMode.value === 'first-page' ? 'first_page_header_html' : 'default_header_html'
})

const previewHeaderBlocks = computed(() => {
  return canvasBlocks.value.filter((block) => block.surface === previewSurfaceKey.value)
})

const previewContentBlocks = computed(() => {
  return canvasBlocks.value.filter((block) => block.surface === 'content')
})

const previewFooterBlocks = computed(() => {
  return canvasBlocks.value.filter((block) => block.surface === 'footer_html')
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

const snippetLibrary = [
  {
    label: 'Capa institucional',
    description: 'Capa editorial com proposta, cliente e subtítulo.',
    html: `
<section style="padding:32px; border-radius:24px; background:linear-gradient(135deg,#143d37,#d8b85f); color:#fffdf7; margin-bottom:24px;">
  <div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.8;">{{lab_name}}</div>
  <h1 style="margin:12px 0 0; font-size:28px;">Proposta {{proposal_number}}</h1>
  <p style="margin:12px 0 0; font-size:14px; opacity:0.88;">Âmbito preparado para {{customer_name}} com enquadramento técnico, comercial e documental.</p>
</section>`.trim(),
  },
  {
    label: 'Resumo executivo',
    description: 'Quadro com cliente, local e validade.',
    html: `
<section style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px;">
  <div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;">
    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Cliente</div>
    <div style="margin-top:6px; font-weight:700;">{{customer_name}}</div>
  </div>
  <div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;">
    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Local</div>
    <div style="margin-top:6px; font-weight:700;">{{service_location}}</div>
  </div>
  <div style="border:1px solid #cbd5e1; border-radius:18px; padding:14px;">
    <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.12em; color:#64748b;">Validade</div>
    <div style="margin-top:6px; font-weight:700;">{{expiry_date}}</div>
  </div>
</section>`.trim(),
  },
  {
    label: 'Bloco de decisão',
    description: 'Área para regra de decisão, âmbito ou condições de aceitação.',
    html: `
<section style="border-left:4px solid #1d4ed8; background:#eff6ff; padding:18px 20px; border-radius:18px; margin:20px 0;">
  <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.16em; color:#9a7a2f; font-weight:700;">Regra de decisão</div>
  <p style="margin:10px 0 0; color:#0f172a;">Indique aqui os critérios de conformidade, incerteza ou aceitação acordados entre o laboratório e o cliente.</p>
</section>`.trim(),
  },
  {
    label: 'Tabela de serviços',
    description: 'Insere a tabela dinâmica de itens da proposta.',
    html: '<section style="margin:20px 0;">{items_table}</section>',
  },
  {
    label: 'Resumo financeiro',
    description: 'Insere a tabela de subtotal, desconto, imposto e total.',
    html: '<section style="margin:20px 0;">{summary_table}</section>',
  },
  {
    label: 'Assinaturas',
    description: 'Linhas de validação para laboratório e cliente.',
    html: `
<section style="margin-top:36px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:24px;">
  <div>
    <div style="border-top:1px solid #0f172a; padding-top:10px; font-size:11px; color:#475569;">Assinatura do laboratório</div>
  </div>
  <div>
    <div style="border-top:1px solid #0f172a; padding-top:10px; font-size:11px; color:#475569;">Aceitação do cliente</div>
  </div>
</section>`.trim(),
  },
  {
    label: 'Quebra de página',
    description: 'Inicia explicitamente uma nova página no preview e no PDF.',
    html: '<pagebreak />',
  },
]

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
    content_html: '<p>Novo conteúdo editorial</p>',
    image_url: '',
    image_alt: '',
    image_fit: 'contain',
    image_position: 'center center',
    qr_content: '{proposal_number}',
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
    ...overrides,
  }
}

function addCanvasBlock(overrides = {}) {
  const block = createCanvasBlock(overrides)
  canvasBlocks.value.push(block)
  selectedCanvasBlockId.value = block.id
}

function addSnippetAsCanvasBlock(snippet) {
  addCanvasBlock({
    title: snippet.label,
    content_html: snippet.html,
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
    image_fit: 'contain',
    image_position: 'center center',
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
    qr_content: '{proposal_number} · {customer_name} · {issue_date}',
    qr_label: 'Verificar autenticidade',
    qr_foreground_color: '#0f172a',
    qr_background_color: '#ffffff',
    qr_error_correction: 'low',
    qr_margin: 8,
  })
}

function addChartSnapshotCanvasBlock() {
  addCanvasBlock({
    title: 'Resumo visual da proposta',
    block_kind: 'chart_snapshot',
    content_html: '',
    width: 42,
    min_height: 180,
    padding: 14,
    background_color: 'rgba(255,255,255,0.92)',
    border_width: 1,
    border_color: 'rgba(148,163,184,0.28)',
    border_radius: 22,
    chart_title: 'Resumo visual da proposta',
    chart_caption: 'Âmbito, amostras e investimento acordados.',
    chart_type: 'bar',
    chart_labels: ['Âmbito', 'Amostras', 'Serviços'],
    chart_values: [3, 5, 8],
    chart_colors: defaultChartPalette,
    chart_primary_color: '#143d37',
    chart_background_color: '#f8f4ea',
    chart_show_values: true,
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

function presetCategoryLabel(category) {
  return presetCategoryLabels[category] || 'Geral'
}

function applyAssetToSelectedBlock(assetUrl, field = 'image_url') {
  if (!selectedCanvasBlock.value || !assetUrl) {
    return
  }

  selectedCanvasBlock.value[field] = assetUrl
}

function openMediaPicker(scope = 'selected-block', field = 'image_url') {
  mediaPickerTarget.value = { scope, field }
  mediaPickerSearch.value = ''
  mediaPickerKind.value = 'all'
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
    props.layoutSchema.background_image_path = assetUrl
    mediaAssetUrl.value = assetUrl
  }

  if (mediaPickerTarget.value.scope === 'selected-block') {
    applyAssetToSelectedBlock(assetUrl, mediaPickerTarget.value.field)
  }

  mediaPickerOpen.value = false
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

function uploadedAssetKind(target = mediaPickerTarget.value) {
  return uploadedStudioAssetKind(target)
}

function normalizeUploadedAsset(data, file) {
  const backendAsset = data?.asset || {}
  const kind = uploadedAssetKind()

  return {
    id: backendAsset.id || `proposal-studio-upload-${data?.id || Date.now()}`,
    label: backendAsset.label || file.name,
    kind: backendAsset.kind || kind,
    source: backendAsset.source || 'Carregamento no estúdio',
    mime_type: backendAsset.mime_type || file.type,
    file_type: backendAsset.file_type || (String(file.type || '').startsWith('image/') ? 'image' : 'other'),
    size: backendAsset.size || file.size,
    url: backendAsset.url || data?.preview_url || '',
    pdf_url: backendAsset.pdf_url || backendAsset.url || data?.preview_url || '',
  }
}

async function uploadMediaPickerAsset(file) {
  mediaPickerUploadError.value = ''

  if (!file) {
    return
  }

  if (!allowedMediaMimeTypes.includes(file.type)) {
    mediaPickerUploadError.value = trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.allowed_types_error')

    return
  }

  const formData = new FormData()
  formData.append('studio_asset_context', 'proposal_studio')
  formData.append('studio_asset_kind', uploadedAssetKind())
  formData.append('file', file)
  mediaPickerUploadBusy.value = true
  mediaPickerUploadProgress.value = 0

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

        mediaPickerUploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      },
    })

    const uploadedAsset = normalizeUploadedAsset(data, file)

    localAssetLibrary.value = [uploadedAsset, ...localAssetLibrary.value]
    applyMediaPickerAsset(uploadedAsset)
    mediaPickerUploadProgress.value = 100
  } catch (error) {
    mediaPickerUploadError.value = error?.response?.data?.message || trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.upload_error')
  } finally {
    mediaPickerUploadBusy.value = false
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
  })
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
    boxShadow: '0 18px 35px rgba(15, 23, 42, 0.08)',
  }
}

function canvasBlockOverlayStyle(block) {
  if (!block.overlay_color) {
    return null
  }

  return {
    position: 'absolute',
    inset: '0',
    borderRadius: `${Number(block.border_radius || 0)}px`,
    background: block.overlay_color,
    opacity: clamp(Number(block.overlay_opacity ?? 0.35), 0, 1),
    pointerEvents: 'none',
  }
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

    const signatureImageFit = mediaObjectFit(block.signature_image_fit || 'contain')
    const signatureImagePosition = safeCssPositionValue(block.signature_image_position || 'center center', 'center center')
    const signatureImageWidth = clamp(Number(block.signature_image_width || 180), 24, 360)
    const signatureImageHeight = clamp(Number(block.signature_image_height || 72), 16, 240)
    const signatureImageUrl = safePreviewMediaUrl(interpolatePreviewHtml(block.signature_image || ''))
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

    return `<img src="${escapePreviewHtmlAttribute(imageUrl)}" alt="${escapePreviewHtmlAttribute(interpolatePreviewHtml(block.image_alt || block.title || 'Imagem'))}" style="display:block; width:100%; height:100%; min-height:inherit; object-fit:${imageObjectFit(block)}; object-position:${imageObjectPosition(block)};" />`
  }

  if (block.block_kind === 'chart_snapshot') {
    const sanitizedChartSvg = sanitizeStudioChartSvg(interpolatePreviewHtml(block.chart_svg || ''))
    const generatedChartSvg = generatedStudioChartSvg(block, {
      colorInputValue,
      interpolate: interpolatePreviewHtml,
    })
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
        : generatedChartSvg
          ? `<div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3">${generatedChartSvg}</div>`
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
    const qrDataUri = generatedStudioQrCodeDataUri(block, {
      fallbackContent: '{proposal_number}',
      interpolate: interpolatePreviewHtml,
    })

    return `
      <div class="flex h-full min-h-24 flex-col items-center justify-center gap-2">
        ${qrDataUri
          ? `<img src="${qrDataUri}" alt="QR de validação" style="display:block; width:100%; max-width:112px; height:auto;" />`
          : '<div class="grid min-h-24 w-full place-items-center rounded-xl border border-dashed border-slate-300 bg-white/80 px-3 text-center text-[11px] text-slate-500">Defina o conteúdo do QR</div>'}
        ${block.qr_label ? `<div class="text-center text-[11px] text-slate-500">${escapePreviewHtmlText(interpolatePreviewHtml(block.qr_label))}</div>` : ''}
      </div>
    `.trim()
  }

  return interpolatePreviewHtml(block.content_html)
}

function selectCanvasBlock(blockId) {
  selectedCanvasBlockId.value = blockId
}

function blockSurfaceLabel(surface) {
  return canvasSurfaceOptions.find((option) => option.value === surface)?.label ?? surface
}

function canvasBlockIsLocked(block) {
  return Boolean(block.is_locked)
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
    .filter((block) => block.surface === selectedCanvasBlock.value.surface && block.id !== selectedCanvasBlock.value.id)
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
  return interpolatePreviewHtml(pageNumber === 1
    ? props.layoutSchema.first_page_header_html
    : props.layoutSchema.default_header_html)
}

function previewFooterHtmlForPage(pageNumber, totalPages) {
  return interpolatePreviewHtml(props.layoutSchema.footer_html, {
    PAGENO: String(pageNumber),
    nbpg: String(totalPages),
  })
}

function previewHeaderBlocksForPage(pageNumber) {
  const surface = pageNumber === 1 ? 'first_page_header_html' : 'default_header_html'

  return canvasBlocks.value.filter((block) => block.surface === surface)
}

function setPreviewPage(pageNumber) {
  activePreviewPage.value = clamp(pageNumber, 1, Math.max(previewPages.value.length, 1))
}

function stepPreviewPage(direction) {
  setPreviewPage(currentPreviewPage.value + direction)
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

  if (/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test(color)) {
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

function imageObjectPosition(block) {
  return safeCssPositionValue(block?.image_position, 'center center')
}

function imageFocalPointStyle(block) {
  const coordinates = imagePositionCoordinates(block?.image_position)

  return {
    left: `${coordinates.x}%`,
    top: `${coordinates.y}%`,
  }
}

function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value))
}

function setImageFocalCoordinate(block, axis, value) {
  if (!block) {
    return
  }

  const coordinates = imagePositionCoordinates(block.image_position)
  coordinates[axis] = clamp(Math.round(Number(value) || 0), 0, 100)
  block.image_position = imagePositionStringFromCoordinates(coordinates.x, coordinates.y)
}

function setSelectedImageFocalCoordinate(axis, value) {
  setImageFocalCoordinate(selectedCanvasBlock.value, axis, value)
}

function updateFocalDrag(event) {
  if (!focalDragState.value) {
    return
  }

  const { block, rect } = focalDragState.value

  if (!rect?.width || !rect?.height) {
    return
  }

  const x = clamp(Math.round(((event.clientX - rect.left) / rect.width) * 100), 0, 100)
  const y = clamp(Math.round(((event.clientY - rect.top) / rect.height) * 100), 0, 100)

  block.image_position = imagePositionStringFromCoordinates(x, y)
}

function stopFocalDrag() {
  focalDragState.value = null
  window.removeEventListener('pointermove', updateFocalDrag)
  window.removeEventListener('pointerup', stopFocalDrag)
}

function startImageFocalDrag(block, event) {
  event.preventDefault()
  event.stopPropagation()

  focalDragState.value = {
    block,
    rect: event.currentTarget.getBoundingClientRect(),
  }

  updateFocalDrag(event)
  window.addEventListener('pointermove', updateFocalDrag)
  window.addEventListener('pointerup', stopFocalDrag, { once: true })
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
    return candidate.id !== block.id && candidate.surface === block.surface
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

const tableStyleSettings = computed(() => ({
  table_header_background: props.layoutSchema.table_header_background || '#143d37',
  table_header_text_color: props.layoutSchema.table_header_text_color || '#ffffff',
  table_border_color: props.layoutSchema.table_border_color || '#cbd5e1',
  table_font_size: props.layoutSchema.table_font_size || 10,
  table_cell_padding: props.layoutSchema.table_cell_padding || 8,
}))

function colorInputValue(value, fallback = '#143d37') {
  return normalizedHexColor(value, fallback)
}

function setSelectedBlockColor(field, value) {
  if (!selectedCanvasBlock.value) {
    return
  }

  selectedCanvasBlock.value[field] = value
}

function syncTableStylesCss() {
  const settings = tableStyleSettings.value
  const generatedCss = [
    '/* studio-table-controls:start */',
    'table{width:100%;border-collapse:collapse;}',
    `th{background:${settings.table_header_background};color:${settings.table_header_text_color};font-size:${settings.table_font_size}px;letter-spacing:0.04em;text-transform:uppercase;}`,
    `th,td{border:1px solid ${settings.table_border_color};padding:${settings.table_cell_padding}px;vertical-align:top;}`,
    '/* studio-table-controls:end */',
  ].join('\n')

  props.layoutSchema.styles_css = String(props.layoutSchema.styles_css || '')
    .replace(/\/\* studio-table-controls:start \*\/[\s\S]*?\/\* studio-table-controls:end \*\//, '')
    .trim()

  props.layoutSchema.styles_css = `${props.layoutSchema.styles_css}\n\n${generatedCss}`.trim()
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
  stopFocalDrag()
  stopInteraction()
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
    image_alt: 'Imagem da proposta',
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
  props.layoutSchema.background_image_path = mediaAssetUrl.value
}

function applyThemePreset(themeKey) {
  const theme = themeCatalog[themeKey]

  if (!theme) {
    return
  }

  props.form.theme_preset = themeKey
  props.layoutSchema.styles_css = theme.styles

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

function draftPreviewPayload() {
  normalizeCanvasBlockPlacements()
  props.layoutSchema.variable_catalog = translatedPlaceholders.value

  return {
    name: props.form.name || '',
    category: props.form.category || 'general',
    description: props.form.description || '',
    theme_preset: props.form.theme_preset || 'corporate',
    is_active: props.form.is_active ?? true,
    content: props.form.content || '',
    layout_schema: { ...props.layoutSchema },
    export_settings: normalizedExportSettings(),
  }
}

function normalizedExportSettings() {
  return {
    paper_size: props.exportSettings.paper_size || 'A4',
    custom_page_width: props.exportSettings.paper_size === 'custom' ? Number(props.exportSettings.custom_page_width || 0) : null,
    custom_page_height: props.exportSettings.paper_size === 'custom' ? Number(props.exportSettings.custom_page_height || 0) : null,
    orientation: props.exportSettings.orientation || 'P',
    margin_top: Number(props.exportSettings.margin_top || 0),
    margin_right: Number(props.exportSettings.margin_right || 0),
    margin_bottom: Number(props.exportSettings.margin_bottom || 0),
    margin_left: Number(props.exportSettings.margin_left || 0),
    first_page_margin_top: Number(props.exportSettings.first_page_margin_top || 0),
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

watch(() => props.exportSettings.paper_size, (paperSize) => {
  if (paperSize === 'custom') {
    ensureCustomPageDefaults()
  }
}, { immediate: true })

function filenameFromResponse(response, fallback) {
  const disposition = response.headers?.['content-disposition'] || ''
  const match = disposition.match(/filename="?([^"]+)"?/i)

  return match?.[1] || fallback
}

function downloadBlob(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

async function draftPreviewMessage(error) {
  const fallback = trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.error')
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

  return error?.response?.data?.message || fallback
}

async function previewDraftPdf() {
  draftPreviewError.value = ''

  if (!props.draftPreviewHref) {
    draftPreviewError.value = trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.missing_route')

    return
  }

  draftPreviewBusy.value = true

  try {
    syncTableStylesCss()
    const response = await axios.post(props.draftPreviewHref, draftPreviewPayload(), {
      responseType: 'blob',
      headers: {
        Accept: 'application/json, application/pdf',
      },
    })
    const filename = filenameFromResponse(response, 'proposal-template-draft-preview.pdf')

    downloadBlob(response.data, filename)
  } catch (error) {
    draftPreviewError.value = await draftPreviewMessage(error)
  } finally {
    draftPreviewBusy.value = false
  }
}

function submit() {
  normalizeCanvasBlockPlacements()
  props.layoutSchema.variable_catalog = translatedPlaceholders.value
  emit('submit')
}
</script>

<template>
  <Head :title="props.title" />

  <div class="space-y-8">
    <div class="overflow-hidden rounded-[2rem] border border-[#ded2bb] bg-[#fbfaf6] p-6 text-[#15231f] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] ring-1 ring-white/70 dark:border-white/10 dark:bg-slate-950 dark:text-[#f7f1e7] dark:ring-white/10 sm:p-8">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-4xl">
          <div class="inline-flex rounded-full border border-[#c79a43]/35 bg-[#fff7e5] px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-[#143d37] dark:border-[#c79a43]/30 dark:bg-white/10 dark:text-amber-100">
            Canvas de proposta
          </div>
          <h1 class="mt-4 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white sm:text-4xl">{{ props.title }}</h1>
          <p class="mt-3 max-w-3xl text-sm font-medium leading-6 text-[#59665f] dark:text-slate-300">
            {{ props.intro }}
          </p>
          <p v-if="props.initialDraftLabel" class="mt-4 inline-flex rounded-full bg-[#143d37] px-3 py-1 text-xs font-black text-white ring-1 ring-[#143d37]/20 dark:bg-white/10 dark:text-emerald-100 dark:ring-white/15">
            {{ props.initialDraftLabel }}
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link
            :href="props.backHref"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#ded2bb] bg-white/80 px-4 py-3 text-sm font-black text-[#143d37] transition hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
          >
            <ArrowUturnLeftIcon class="h-4 w-4" />
            {{ props.backLabel }}
          </Link>
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-2xl border border-[#ded2bb] bg-white/80 px-4 py-3 text-sm font-black text-[#143d37] transition hover:bg-[#fff7e5] disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
            :disabled="draftPreviewBusy || props.form.processing"
            @click="previewDraftPdf"
          >
            {{ draftPreviewBusy ? trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.generating') : trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.action') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-2xl bg-[#143d37] px-4 py-3 text-sm font-black text-white shadow-[0_18px_42px_-24px_rgba(20,61,55,0.75)] transition hover:bg-[#0f302b] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400"
            :disabled="props.form.processing || draftPreviewBusy"
            @click="submit"
          >
            {{ props.form.processing ? 'A guardar...' : props.submitLabel }}
          </button>
        </div>
      </div>
      <p v-if="draftPreviewError" class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
        {{ draftPreviewError }}
      </p>
    </div>

    <div v-if="props.presets.length" class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
      <h2 class="text-lg font-black text-[#10221d] dark:text-white">Modelos base</h2>
      <p class="mt-1 text-sm font-medium text-[#59665f] dark:text-slate-300">Escolha uma estrutura inicial e refine o canvas abaixo sem sair da página.</p>
      <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <article
          v-for="preset in props.presets"
          :key="preset.slug"
          class="rounded-[24px] border border-[#ded2bb] bg-[#fbfaf6] p-4 transition hover:-translate-y-0.5 hover:border-[#c79a43] hover:bg-[#fff7e5] hover:shadow-[0_20px_48px_-34px_rgba(20,61,55,0.55)] dark:border-white/10 dark:bg-white/5 dark:hover:border-[#c79a43]/60 dark:hover:bg-white/10"
        >
          <div class="text-sm font-black text-[#10221d] dark:text-white">{{ preset.name }}</div>
          <div class="mt-1 text-xs font-black uppercase tracking-[0.18em] text-[#c79a43]">{{ presetCategoryLabel(preset.category) }}</div>
          <p class="mt-3 text-sm font-medium leading-6 text-[#59665f] dark:text-slate-300">{{ preset.description }}</p>
          <button
            v-if="props.presetActionLabel"
            type="button"
            class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-[#143d37] px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43]/30 dark:bg-emerald-500 dark:text-slate-950 dark:hover:bg-emerald-400"
            @click="emit('select-preset', preset)"
          >
            {{ props.presetActionLabel }}
          </button>
        </article>
      </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.72fr)] 2xl:grid-cols-[minmax(0,1.6fr)_minmax(340px,0.68fr)]">
      <section class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="grid gap-5 md:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Nome do modelo</label>
              <input v-model="props.form.name" type="text" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
              <p v-if="props.form.errors.name" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ props.form.errors.name }}</p>
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Categoria</label>
              <input v-model="props.form.category" type="text" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Tema visual</label>
              <select v-model="props.form.theme_preset" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <option v-for="option in themePresetOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>

            <div class="flex items-end">
              <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                <input v-model="props.form.is_active" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
                Modelo activo para novas propostas
              </label>
            </div>
          </div>

          <div class="mt-5">
            <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Descrição operacional</label>
            <textarea v-model="props.form.description" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
          </div>

          <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-950/50">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="inline-flex rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
                  {{ selectedThemePreset.badge }}
                </div>
                <h3 class="mt-3 text-base font-semibold text-slate-900 dark:text-slate-100">Direcção visual</h3>
                <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
                  {{ selectedThemePreset.description }}
                </p>
              </div>
              <button
                type="button"
                @click="applyThemePreset(props.form.theme_preset)"
                class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
              >
                Aplicar tema ao layout
              </button>
            </div>
            <div class="mt-5 grid gap-4 lg:grid-cols-4">
              <button
                v-for="option in themePresetOptions"
                :key="option.value"
                type="button"
                @click="applyThemePreset(option.value)"
                class="group rounded-2xl border px-4 py-4 text-left transition"
                :class="props.form.theme_preset === option.value
                  ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-500/10'
                  : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-slate-600'"
              >
                <div class="h-2.5 rounded-full bg-gradient-to-r" :class="themeCatalog[option.value].accent" />
                <div class="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ option.label }}</div>
                <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-400">{{ themeCatalog[option.value].description }}</p>
              </button>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Conteúdo da proposta</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">Caracteres: {{ contentLength }}</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
              <SparklesIcon class="h-4 w-4" />
              Studio interno multi-página
            </div>
          </div>

          <fancy-textarea v-model="props.form.content" />
          <p v-if="props.form.errors.content" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ props.form.errors.content }}</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Canvas de composição</h2>
              <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Escolha a superfície de destino e injete blocos prontos para capa, decisão, assinaturas, imagens e tabelas.
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

          <div class="mt-5 flex snap-x gap-4 overflow-x-auto pb-3 [scrollbar-width:thin]">
            <div
              v-for="snippet in snippetLibrary"
              :key="snippet.label"
              class="min-w-[280px] snap-start rounded-2xl border border-slate-200 bg-slate-50/80 p-4 transition dark:border-slate-700 dark:bg-slate-950/40 md:min-w-[340px]"
            >
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
          </div>

          <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Blocos de assinatura</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                  Monte assinaturas formais no próprio canvas para laboratório, cliente e validação.
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
                  @click="addChartSnapshotCanvasBlock"
                  class="inline-flex items-center rounded-2xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-900"
                >
                  Gráfico
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
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Imagem do layout</label>
              <input
                v-model="mediaAssetUrl"
                type="text"
                placeholder="/storage/proposals/backgrounds/proposta-hero.png"
                class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
              <select
                v-if="assetLibraryItems.length"
                class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                @change="mediaAssetUrl = $event.target.value"
              >
                <option value="">Selecionar da galeria ou assinaturas</option>
                <option v-for="asset in assetLibraryItems" :key="asset.id" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
              </select>
              <button
                type="button"
                @click="openMediaPicker('asset-url')"
                class="mt-2 inline-flex items-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200"
              >
                <PhotoIcon class="h-4 w-4" />
                Galeria / upload
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
                      Ainda não existem blocos posicionáveis neste modelo.
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
                  <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Composições rápidas</div>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Aplique composições típicas para capa, selo, lateral ou destaque.</p>
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
                      <option value="stamp">Carimbo / selo</option>
                      <option value="signature">Assinatura</option>
                      <option value="qr_code">QR code</option>
                      <option value="chart_snapshot">Gráfico / captura</option>
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
                    <input v-model="selectedCanvasBlock.background_image" type="text" placeholder="/storage/proposals/blocks/hero-cover.png" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <select v-if="assetLibraryItems.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'background_image')">
                      <option value="">Aplicar imagem como fundo do bloco</option>
                      <option v-for="asset in assetLibraryItems" :key="`bg-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'background_image')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Galeria / upload</button>
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
                    <select v-if="assetLibraryItems.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'signature_image')">
                      <option value="">Usar assinatura/imagem guardada</option>
                      <option v-for="asset in assetLibraryItems" :key="`sig-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'signature_image')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Galeria / upload</button>
                  </label>
                  <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/70 sm:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Imagem impressa da assinatura</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Controla a imagem no preview e no PDF final da proposta.</p>
                      </div>
                      <div v-if="selectedCanvasBlock.signature_image" class="rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200 dark:bg-slate-950 dark:ring-slate-700">
                        <img
                          :src="selectedCanvasBlock.signature_image"
                          alt="Preview da assinatura"
                          :style="{
                            width: `${clamp(Number(selectedCanvasBlock.signature_image_width || 180), 24, 360)}px`,
                            maxWidth: '220px',
                            height: `${clamp(Number(selectedCanvasBlock.signature_image_height || 72), 16, 240)}px`,
                            objectFit: mediaObjectFit(selectedCanvasBlock.signature_image_fit || 'contain'),
                            objectPosition: safeCssPositionValue(selectedCanvasBlock.signature_image_position || 'center center', 'center center'),
                          }"
                        />
                      </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                      <label class="text-sm text-slate-700 dark:text-slate-300">Ajuste
                        <select v-model="selectedCanvasBlock.signature_image_fit" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in backgroundFitOptions" :key="`signature-fit-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label class="text-sm text-slate-700 dark:text-slate-300">Foco
                        <select v-model="selectedCanvasBlock.signature_image_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                          <option v-for="option in imagePositionOptions" :key="`signature-position-${option.value}`" :value="option.value">{{ option.label }}</option>
                        </select>
                      </label>
                      <label class="text-sm text-slate-700 dark:text-slate-300">Largura
                        <input v-model.number="selectedCanvasBlock.signature_image_width" type="number" min="24" max="360" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                      </label>
                      <label class="text-sm text-slate-700 dark:text-slate-300">Altura
                        <input v-model.number="selectedCanvasBlock.signature_image_height" type="number" min="16" max="240" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                      </label>
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
                    <select v-if="assetLibraryItems.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'image_url')">
                      <option value="">Selecionar da galeria/assinaturas</option>
                      <option v-for="asset in assetLibraryItems" :key="`image-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'image_url')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Galeria / upload</button>
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
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                      <div>
                        <div
                          class="relative h-52 overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-inner dark:border-slate-700 dark:bg-slate-950"
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
                        <div class="mt-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                          Posição exportada: {{ imageObjectPosition(selectedCanvasBlock) }}
                        </div>
                      </div>
                      <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <div>
                          <div class="flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
                            <span>Eixo X</span>
                            <span>{{ imagePositionCoordinates(selectedCanvasBlock.image_position).x }}%</span>
                          </div>
                          <input :value="imagePositionCoordinates(selectedCanvasBlock.image_position).x" type="range" min="0" max="100" step="1" class="mt-2 w-full accent-primary-700" @input="setSelectedImageFocalCoordinate('x', $event.target.value)" />
                        </div>
                        <div>
                          <div class="flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
                            <span>Eixo Y</span>
                            <span>{{ imagePositionCoordinates(selectedCanvasBlock.image_position).y }}%</span>
                          </div>
                          <input :value="imagePositionCoordinates(selectedCanvasBlock.image_position).y" type="range" min="0" max="100" step="1" class="mt-2 w-full accent-primary-700" @input="setSelectedImageFocalCoordinate('y', $event.target.value)" />
                        </div>
                        <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                          Arraste o ponto sobre a imagem para definir o recorte exacto que será usado no PDF.
                        </p>
                      </div>
                    </div>
                  </div>
                  <div class="rounded-2xl border border-primary-200 bg-primary-50 p-4 text-xs leading-relaxed text-primary-900 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">
                    Para pôr um carimbo sobre uma assinatura, coloque o bloco de carimbo acima da assinatura e use uma camada maior.
                  </div>
                </div>

                <div v-if="selectedCanvasBlock.block_kind === 'qr_code'" class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Conteúdo do QR
                    <input v-model="selectedCanvasBlock.qr_content" type="text" placeholder="{proposal_number} · {customer_name} · {issue_date}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Legenda do QR
                    <input v-model="selectedCanvasBlock.qr_label" type="text" placeholder="Verificação digital" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Cor do QR
                    <div class="mt-2 flex gap-2">
                      <input :value="selectedCanvasBlock.qr_foreground_color || '#0f172a'" type="color" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" @input="selectedCanvasBlock.qr_foreground_color = $event.target.value" />
                      <input v-model="selectedCanvasBlock.qr_foreground_color" type="text" placeholder="#0f172a" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Fundo do QR
                    <div class="mt-2 flex gap-2">
                      <input :value="selectedCanvasBlock.qr_background_color || '#ffffff'" type="color" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" @input="selectedCanvasBlock.qr_background_color = $event.target.value" />
                      <input v-model="selectedCanvasBlock.qr_background_color" type="text" placeholder="#ffffff" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Tolerância de leitura
                    <select v-model="selectedCanvasBlock.qr_error_correction" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option value="low">Baixa · mais compacto</option>
                      <option value="medium">Média</option>
                      <option value="quartile">Reforçada</option>
                      <option value="high">Alta · maior resiliência</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Margem de segurança
                    <input v-model.number="selectedCanvasBlock.qr_margin" type="number" min="0" max="32" step="1" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400 sm:col-span-2">O preview usa o mesmo conteúdo, cores, margem e tolerância de leitura aplicados ao PDF final.</p>
                </div>

                <div v-if="selectedCanvasBlock.block_kind === 'chart_snapshot'" class="mt-4 grid gap-4 sm:grid-cols-2">
                  <label class="text-sm text-slate-700 dark:text-slate-300">Título do gráfico
                    <input v-model="selectedCanvasBlock.chart_title" type="text" placeholder="Resumo visual da proposta" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Tipo
                    <select v-model="selectedCanvasBlock.chart_type" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                      <option v-for="option in chartTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Etiquetas
                    <textarea v-model="selectedCanvasBlock.chart_labels" rows="4" placeholder="Âmbito, Amostras, Serviços" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300">Valores
                    <textarea v-model="selectedCanvasBlock.chart_values" rows="4" placeholder="3, 5, 8" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
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
                  <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Imagem do gráfico
                    <input v-model="selectedCanvasBlock.chart_image_url" type="text" placeholder="/storage/proposals/charts/scope.png" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    <select v-if="assetLibraryItems.length" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @change="applyAssetToSelectedBlock($event.target.value, 'chart_image_url')">
                      <option value="">Selecionar captura da galeria</option>
                      <option v-for="asset in assetLibraryItems" :key="`chart-${asset.id}`" :value="mediaAssetDocumentUrl(asset)">{{ asset.source }} · {{ asset.label }}</option>
                    </select>
                    <button type="button" @click="openMediaPicker('selected-block', 'chart_image_url')" class="mt-2 inline-flex items-center rounded-2xl border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">Escolher no media picker</button>
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Legenda
                    <input v-model="selectedCanvasBlock.chart_caption" type="text" placeholder="Inclui somente itens acordados nesta proposta." class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                  </label>
                  <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">SVG exportado
                    <textarea v-model="selectedCanvasBlock.chart_svg" rows="7" placeholder="<svg ...> exportado do ApexCharts</svg>" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
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
      </section>

      <aside class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Layout multi-página</h2>
          <div class="mt-4 space-y-4">
            <label class="block text-sm text-slate-700 dark:text-slate-300">Fonte do documento
              <select v-model="props.layoutSchema.document_font_family" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <option v-for="option in documentFontOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Cabeçalho da primeira página</label>
              <textarea v-model="props.layoutSchema.first_page_header_html" rows="4" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Cabeçalho padrão</label>
              <textarea v-model="props.layoutSchema.default_header_html" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Rodapé</label>
              <textarea v-model="props.layoutSchema.footer_html" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Cor da página
                <div class="mt-2 flex gap-2">
                  <input :value="colorInputValue(props.layoutSchema.page_background_color, '#fffdf7')" type="color" class="h-12 w-14 rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" @input="props.layoutSchema.page_background_color = $event.target.value" />
                  <input v-model="props.layoutSchema.page_background_color" type="text" placeholder="#fffdf7" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                </div>
              </label>
              <label class="text-sm text-slate-700 dark:text-slate-300">Fundo
                <input v-model="props.layoutSchema.background_image_path" type="text" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
              </label>
              <div class="flex items-end gap-2">
                <button
                  type="button"
                  @click="openMediaPicker('document-background')"
                  class="inline-flex flex-1 items-center justify-center rounded-2xl bg-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-700/15 transition hover:bg-primary-800 dark:bg-primary-500 dark:hover:bg-primary-400"
                >
                  Galeria / upload
                </button>
                <button
                  type="button"
                  @click="syncAssetUrlFromBackground"
                  class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                  Sincronizar
                </button>
              </div>
              <label class="text-sm text-slate-700 dark:text-slate-300">Ajuste do fundo
                <select v-model="props.layoutSchema.background_size" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                  <option v-for="option in backgroundFitOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </label>
              <label class="text-sm text-slate-700 dark:text-slate-300">Posição
                <select v-model="props.layoutSchema.background_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                  <option v-for="option in backgroundPositionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </label>
              <label class="text-sm text-slate-700 dark:text-slate-300">Repetição
                <select v-model="props.layoutSchema.background_repeat" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                  <option v-for="option in backgroundRepeatOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </label>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">CSS adicional</label>
              <textarea v-model="props.layoutSchema.styles_css" rows="5" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Tabelas da proposta</h3>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Personalize cabeçalhos, bordas e densidade. O CSS gerado é persistido e sai no PDF.</p>
                </div>
                <button type="button" @click="syncTableStylesCss" class="rounded-2xl bg-primary-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500">
                  Aplicar
                </button>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-xs font-medium text-slate-600 dark:text-slate-300">Fundo do cabeçalho
                  <input v-model="props.layoutSchema.table_header_background" type="color" class="mt-2 h-11 w-full rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                </label>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-300">Texto do cabeçalho
                  <input v-model="props.layoutSchema.table_header_text_color" type="color" class="mt-2 h-11 w-full rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                </label>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-300">Cor das bordas
                  <input v-model="props.layoutSchema.table_border_color" type="color" class="mt-2 h-11 w-full rounded-2xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" />
                </label>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-300">Tamanho do texto
                  <input v-model="props.layoutSchema.table_font_size" type="number" min="8" max="16" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                </label>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-300 sm:col-span-2">Espaçamento interno
                  <input v-model="props.layoutSchema.table_cell_padding" type="number" min="2" max="24" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Exportação</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-slate-700 dark:text-slate-300">Formato
              <select v-model="props.exportSettings.paper_size" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <option v-for="option in pageFormatOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
            <label v-if="props.exportSettings.paper_size === 'custom'" class="text-sm text-slate-700 dark:text-slate-300">Largura (mm)
              <input v-model.number="props.exportSettings.custom_page_width" type="number" min="50" max="2000" step="1" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label v-if="props.exportSettings.paper_size === 'custom'" class="text-sm text-slate-700 dark:text-slate-300">Altura (mm)
              <input v-model.number="props.exportSettings.custom_page_height" type="number" min="50" max="2000" step="1" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Orientação
              <select v-model="props.exportSettings.orientation" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <option v-for="option in orientationOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem superior
              <input v-model="props.exportSettings.margin_top" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem direita
              <input v-model="props.exportSettings.margin_right" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem inferior
              <input v-model="props.exportSettings.margin_bottom" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem esquerda
              <input v-model="props.exportSettings.margin_left" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Margem superior da primeira página
              <input v-model="props.exportSettings.first_page_margin_top" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
          </div>
          <div class="mt-5 rounded-2xl border border-[#ded2bb] bg-[#fbfaf6] p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-black uppercase tracking-[0.18em] text-[#c79a43]">
              {{ trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.panel_eyebrow') }}
            </div>
            <p class="mt-2 text-sm font-medium leading-6 text-[#59665f] dark:text-slate-300">
              {{ trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.panel_description') }}
            </p>
            <button
              type="button"
              @click="previewDraftPdf"
              :disabled="draftPreviewBusy || props.form.processing"
              class="mt-4 inline-flex w-full items-center justify-center rounded-2xl border border-[#143d37]/20 bg-white px-4 py-3 text-sm font-black text-[#143d37] transition hover:bg-[#fff7e5] disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
            >
              {{ draftPreviewBusy ? trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.generating') : trans('gestlab.general.labels.vap_proposal_templates.studio.draft_preview.action') }}
            </button>
          </div>
        </div>

        <button
          type="button"
          @click="submit"
          :disabled="props.form.processing || draftPreviewBusy"
          class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-primary-900 to-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-primary-800 hover:to-primary-600 disabled:cursor-not-allowed disabled:opacity-60 dark:from-primary-700 dark:to-primary-500"
        >
          {{ props.form.processing ? 'A guardar…' : props.submitLabel }}
        </button>
      </aside>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 lg:p-8">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pré-visualização editorial</h2>
          <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
            O canvas agora ocupa uma superfície própria. Assim fica mais fácil compor headers, overlays, conteúdo e rodapés sem a sensação de coluna apertada.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            @click="previewMode = 'first-page'"
            class="rounded-full px-4 py-2 text-sm font-medium transition"
            :class="previewMode === 'first-page'
              ? 'bg-primary-900 text-white dark:bg-primary-500'
              : 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'"
          >
            Primeira página
          </button>
          <button
            type="button"
            @click="previewMode = 'following-page'"
            class="rounded-full px-4 py-2 text-sm font-medium transition"
            :class="previewMode === 'following-page'
              ? 'bg-primary-900 text-white dark:bg-primary-500'
              : 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'"
          >
            Páginas seguintes
          </button>
          <button
            type="button"
            @click="previewDisplayMode = 'paged'"
            class="rounded-full px-4 py-2 text-sm font-medium transition"
            :class="previewDisplayMode === 'paged'
              ? 'bg-primary-900 text-white dark:bg-primary-500'
              : 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'"
          >
            Página única
          </button>
          <button
            type="button"
            @click="previewDisplayMode = 'all'"
            class="rounded-full px-4 py-2 text-sm font-medium transition"
            :class="previewDisplayMode === 'all'
              ? 'bg-primary-900 text-white dark:bg-primary-500'
              : 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'"
          >
            Todas
          </button>
        </div>
      </div>

      <div class="mt-6 grid gap-4 2xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-700 dark:bg-slate-950/40">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ previewMeta.title }}</div>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ previewMeta.subtitle }}</p>
              <div class="mt-2 text-xs font-medium text-primary-700 dark:text-primary-300">
                {{ previewPages.length }} página<span v-if="previewPages.length !== 1">s</span> no preview
              </div>
              <div class="mt-2 inline-flex rounded-full border border-[#ded2bb] bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.12em] text-[#475a53] dark:border-white/10 dark:bg-white/10 dark:text-[#cbd8cf]">
                {{ previewPageFormatLabel }}
              </div>
            </div>
            <div v-if="previewPages.length > 1" class="text-xs font-medium text-slate-600 dark:text-slate-300">
              Página {{ currentPreviewPage }} de {{ previewPages.length }}
            </div>
          </div>

          <div v-if="previewPages.length > 1" class="mt-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="stepPreviewPage(-1)"
                  :disabled="currentPreviewPage <= 1"
                  class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                  Anterior
                </button>
                <button
                  type="button"
                  @click="stepPreviewPage(1)"
                  :disabled="currentPreviewPage >= previewPages.length"
                  class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                  Seguinte
                </button>
              </div>
            </div>
            <div class="flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="pageNumber in previewPages.length"
                :key="`preview-nav-${pageNumber}`"
                type="button"
                @click="setPreviewPage(pageNumber)"
                class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold transition"
                :class="currentPreviewPage === pageNumber
                  ? 'bg-primary-900 text-white dark:bg-primary-500'
                  : 'border border-slate-300 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'"
              >
                {{ pageNumber }}
              </button>
            </div>
          </div>
        </div>

        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40 sm:grid-cols-2 2xl:grid-cols-1">
          <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
            <input v-model="showCanvasGrid" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
            Mostrar grelha
          </label>
          <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
            <input v-model="showSafeArea" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
            Mostrar safe area
          </label>
          <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
            <input v-model="snapToGrid" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
            Snap activo
          </label>
          <label class="text-sm text-slate-700 dark:text-slate-300">
            Grelha (%)
            <input v-model="gridSize" type="number" min="1" max="20" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
          </label>
        </div>
      </div>

      <div class="mt-6 space-y-5 rounded-[32px] border border-slate-200 bg-slate-100 p-5 shadow-inner dark:border-slate-700 dark:bg-slate-950 lg:p-7">
        <div
          v-for="previewPage in visiblePreviewPages"
          :key="`preview-page-${previewPage.pageNumber}`"
          class="relative mx-auto w-full rounded-[32px] border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:p-10"
          :style="previewPageFrameStyle"
        >
          <div class="mb-5 flex items-center justify-between text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
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
          <div data-canvas-surface="header" class="relative rounded-2xl border border-dashed border-slate-300 bg-white/80 p-5 dark:border-slate-600 dark:bg-slate-900/70" :style="[previewGridStyle, { minHeight: '130px' }]">
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
          <div data-canvas-surface="content" class="relative mt-6 rounded-2xl border border-slate-200 bg-white/85 p-6 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/80 xl:p-7" :style="[previewGridStyle, { minHeight: '360px' }]">
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
          <div data-canvas-surface="footer" class="relative mt-6 rounded-2xl border border-dashed border-slate-300 bg-white/80 p-5 text-xs dark:border-slate-600 dark:bg-slate-900/70" :style="[previewGridStyle, { minHeight: '96px' }]">
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

      <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
        Esta pré-visualização serve para a composição editorial. No PDF final, cabeçalhos, rodapés, margens, orientação, fundo, paginação e o âmbito por página dos blocos do corpo são respeitados.
      </p>
    </div>

    <div v-if="mediaPickerOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#06100e]/80 p-4 backdrop-blur-sm" @click.self="mediaPickerOpen = false">
      <div class="max-h-[88vh] w-full max-w-6xl overflow-hidden rounded-[2.4rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_40px_120px_rgba(6,16,14,0.34)] dark:border-[#29483f] dark:bg-[#07110f]">
        <div class="flex flex-col gap-4 border-b border-[#ded3bf] bg-[linear-gradient(135deg,#fffdf7,#f4efe4)] px-6 py-5 dark:border-[#29483f] dark:bg-[linear-gradient(135deg,#0d1d19,#07110f)] md:flex-row md:items-center md:justify-between">
          <div>
            <div class="text-[10px] font-black uppercase tracking-[0.22em] text-[#d9b05f]">{{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.eyebrow') }}</div>
            <h2 class="mt-1 text-xl font-black tracking-tight text-[#15231f] dark:text-[#fffdf7]">{{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.title') }}</h2>
            <p class="mt-1 max-w-2xl text-sm font-medium leading-6 text-[#6b7b74] dark:text-[#b8c9c0]">{{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.description') }}</p>
            <div class="mt-3 inline-flex rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#143d37] shadow-sm dark:border-[#29483f] dark:bg-[#10231f] dark:text-[#d9b05f]">
              {{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.target_label', { target: mediaPickerTargetLabel }) }}
            </div>
          </div>
          <button type="button" @click="mediaPickerOpen = false" class="rounded-2xl border border-[#ded3bf] bg-white/80 px-4 py-2 text-sm font-black text-[#20332f] transition hover:bg-[#f4efe4] dark:border-[#29483f] dark:bg-[#10231f] dark:text-[#fffdf7] dark:hover:bg-[#143d37]">
            {{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.close') }}
          </button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto p-6">
          <input
            ref="mediaPickerUploadInput"
            type="file"
            accept=".svg,image/svg+xml,image/png,image/jpeg,image/webp,image/gif,image/avif"
            class="sr-only"
            @change="handleMediaPickerUploadInput"
          />
          <button
            type="button"
            :disabled="mediaPickerUploadBusy"
            class="mb-5 w-full rounded-[2rem] border border-dashed p-5 text-left transition"
            :class="mediaPickerUploadDragging
              ? 'border-[#d9b05f] bg-[#fff7e1] dark:border-[#d9b05f] dark:bg-[#d9b05f]/10'
              : 'border-[#d8cbb8] bg-white/70 hover:border-[#d9b05f] hover:bg-[#fffaf0] disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#29483f] dark:bg-[#10231f]/80 dark:hover:border-[#d9b05f]/70 dark:hover:bg-[#d9b05f]/10'"
            @click="pickMediaPickerUpload"
            @dragenter.prevent="mediaPickerUploadDragging = true"
            @dragover.prevent="mediaPickerUploadDragging = true"
            @dragleave.prevent="mediaPickerUploadDragging = false"
            @drop.prevent="handleMediaPickerDrop"
          >
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="flex items-start gap-3">
                <div class="rounded-2xl bg-[#143d37] p-3 text-[#fffdf7] shadow-lg shadow-[#143d37]/20">
                  <PhotoIcon class="h-5 w-5" />
                </div>
                <div>
                  <div class="text-sm font-black text-[#15231f] dark:text-[#fffdf7]">{{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.add_title') }}</div>
                  <p class="mt-1 text-xs font-medium leading-5 text-[#6b7b74] dark:text-[#a9bcb2]">
                    {{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.add_description') }}
                  </p>
                </div>
              </div>
              <span class="rounded-full border border-[#eadfca] bg-[#fffdf7] px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.14em] text-[#9a7a2f] shadow-sm dark:border-[#29483f] dark:bg-[#07110f] dark:text-[#d9b05f]">
                {{ mediaPickerUploadBusy ? trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.upload_progress', { progress: mediaPickerUploadProgress || 0 }) : trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.allowed_badge') }}
              </span>
            </div>
            <div v-if="mediaPickerUploadBusy" class="mt-4 h-2 overflow-hidden rounded-full bg-[#eadfca] dark:bg-[#29483f]">
              <div class="h-full rounded-full bg-[#d9b05f] transition-all" :style="{ width: `${mediaPickerUploadProgress || 8}%` }" />
            </div>
            <p v-if="mediaPickerUploadError" class="mt-3 text-xs font-semibold text-red-600 dark:text-red-300">{{ mediaPickerUploadError }}</p>
            <span class="sr-only">
                {{ mediaPickerUploadBusy ? trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.upload_progress', { progress: mediaPickerUploadProgress || 0 }) : trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.choose_file') }}
            </span>
          </button>

          <div class="rounded-[2rem] border border-[#ded3bf] bg-white/75 p-4 shadow-sm dark:border-[#29483f] dark:bg-[#0d1d19]/80">
            <input
              v-model="mediaPickerSearch"
              type="search"
              :placeholder="trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.search_placeholder')"
              class="block w-full rounded-2xl border border-[#ded3bf] bg-[#fffdf7] px-4 py-3 text-sm font-semibold text-[#15231f] shadow-sm placeholder:text-[#8a9a92] focus:border-[#d9b05f] focus:outline-none focus:ring-2 focus:ring-[#d9b05f]/20 dark:border-[#29483f] dark:bg-[#07110f] dark:text-[#fffdf7] dark:placeholder:text-[#789087]"
            />

            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="option in mediaKindOptions"
                :key="option.value"
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-black uppercase tracking-[0.12em] transition"
                :class="mediaPickerKind === option.value
                  ? 'border-[#143d37] bg-[#143d37] text-[#fffdf7] shadow-lg shadow-[#143d37]/12 dark:border-[#d9b05f] dark:bg-[#d9b05f] dark:text-[#07110f]'
                  : 'border-[#ded3bf] bg-[#fffdf7] text-[#6b7b74] hover:border-[#d9b05f] hover:text-[#143d37] dark:border-[#29483f] dark:bg-[#07110f] dark:text-[#b8c9c0] dark:hover:border-[#d9b05f]/70 dark:hover:text-[#fffdf7]'"
                @click="mediaPickerKind = option.value"
              >
                <span>{{ option.label }}</span>
                <span class="rounded-full bg-[#f4efe4] px-2 py-0.5 text-[10px] text-[#6b7b74] dark:bg-[#10231f] dark:text-[#b8c9c0]" :class="mediaPickerKind === option.value ? '!bg-white/20 !text-white dark:!bg-[#07110f]/20 dark:!text-[#07110f]' : ''">
                  {{ option.count }}
                </span>
              </button>
            </div>
          </div>

          <div v-if="filteredMediaAssets.length" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <button
              v-for="asset in filteredMediaAssets"
              :key="`picker-${asset.id}`"
              type="button"
              @click="applyMediaPickerAsset(asset)"
              class="group overflow-hidden rounded-3xl border border-[#ded3bf] bg-[#fffdf7] text-left transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-lg dark:border-[#29483f] dark:bg-[#10231f]"
            >
              <div class="flex h-44 items-center justify-center bg-[#f4efe4] dark:bg-[#07110f]/80">
                <img :src="asset.url" :alt="asset.label" class="h-full w-full object-contain p-4 transition group-hover:scale-105" />
              </div>
              <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="truncate text-sm font-black text-[#15231f] dark:text-[#fffdf7]">{{ asset.label }}</div>
                    <div class="mt-1 text-xs font-medium text-[#6b7b74] dark:text-[#a9bcb2]">{{ asset.source }}</div>
                  </div>
                  <span class="shrink-0 rounded-full border border-[#eadfca] bg-[#fffaf0] px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-[#9a7a2f] dark:border-[#29483f] dark:bg-[#07110f] dark:text-[#d9b05f]">
                    {{ mediaKindLabel(mediaKindValue(asset)) }}
                  </span>
                </div>
                <div v-if="asset.author" class="mt-1 text-[11px] font-medium text-[#8a9a92] dark:text-[#789087]">{{ asset.author }}</div>
              </div>
            </button>
          </div>
          <div v-else class="mt-5 rounded-3xl border border-dashed border-[#ded3bf] bg-white/60 p-8 text-center text-sm font-semibold text-[#6b7b74] dark:border-[#29483f] dark:bg-[#10231f]/60 dark:text-[#a9bcb2]">
            {{ trans('gestlab.general.labels.vap_proposal_templates.studio.media_picker.empty') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
