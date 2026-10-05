<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import reportStudioWorkbench from '@/Components/report-studio/studio-workbench.vue'
import DialogModal from '@/Components/dialog-modal.vue'
import { previewReplacementsByType } from '@/Support/report-studio-preview-html.mjs'
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
  FilePlus as DocumentPlusIcon,
  FolderOpen as FolderOpenIcon,
  Search as MagnifyingGlassIcon,
  Image as PhotoIcon,
} from '@lucide/vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  templates: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({ total: 0, analysis: 0, executive: 0, proposal: 0, export_certificate: 0, import_certificate: 0, quote: 0, invoice: 0, receipt: 0, credit_note: 0, canva: 0, chrome: 0 }),
  },
  rendererCapabilities: {
    type: Object,
    default: () => ({}),
  },
  studioAssets: {
    type: Array,
    default: () => [],
  },
  documentBaseCss: {
    type: String,
    default: '',
  },
  systemPresets: {
    type: Array,
    default: () => [],
  },
  canvasSampleValues: {
    type: Object,
    default: () => ({ shared: {}, types: {} }),
  },
})

const editingTemplate = ref(null)
const archiveTemplate = ref(null)
const studioWorkspaceView = ref(props.templates.length ? 'library' : 'editor')
const templateSearch = ref('')
const templateTypeFilter = ref('')

const studioSummaryCards = computed(() => [
  {
    key: 'active_models',
    labelKey: 'gestlab.general.labels.vap_report_studios.index.summary.active_models.label',
    value: props.summary.total,
    hintKey: 'gestlab.general.labels.vap_report_studios.index.summary.active_models.hint',
    tone: 'from-primary-950 to-primary-700',
  },
  {
    key: 'lab_reports',
    labelKey: 'gestlab.general.labels.vap_report_studios.index.summary.lab_reports.label',
    value: (props.summary.analysis || 0) + (props.summary.executive || 0),
    hintKey: 'gestlab.general.labels.vap_report_studios.index.summary.lab_reports.hint',
    tone: 'from-primary-800 to-primary-600',
  },
  {
    key: 'commercial_documents',
    labelKey: 'gestlab.general.labels.vap_report_studios.index.summary.commercial_documents.label',
    value: (props.summary.proposal || 0) + (props.summary.quote || 0) + (props.summary.invoice || 0) + (props.summary.receipt || 0) + (props.summary.credit_note || 0),
    hintKey: 'gestlab.general.labels.vap_report_studios.index.summary.commercial_documents.hint',
    tone: 'from-emerald-700 to-teal-700',
  },
  {
    key: 'reusable_media',
    labelKey: 'gestlab.general.labels.vap_report_studios.index.summary.reusable_media.label',
    value: props.studioAssets.length,
    hintKey: 'gestlab.general.labels.vap_report_studios.index.summary.reusable_media.hint',
    tone: 'from-amber-600 to-orange-700',
  },
])

const documentTypeCards = computed(() => [
  { key: 'analysis', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.analysis', value: props.summary.analysis, accent: 'bg-[rgb(var(--primary-500-rgb))]' },
  { key: 'executive', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.executive', value: props.summary.executive, accent: 'bg-[rgb(var(--accent-400-rgb))]' },
  { key: 'proposal', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.proposal', value: props.summary.proposal, accent: 'bg-emerald-500' },
  { key: 'export_certificate', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.export_certificate', value: props.summary.export_certificate, accent: 'bg-[rgb(var(--primary-600-rgb))]' },
  { key: 'import_certificate', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.import_certificate', value: props.summary.import_certificate, accent: 'bg-[rgb(var(--primary-400-rgb))]' },
  { key: 'quote', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.quote', value: props.summary.quote, accent: 'bg-teal-500' },
  { key: 'invoice', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.invoice', value: props.summary.invoice, accent: 'bg-[rgb(var(--accent-500-rgb))]' },
  { key: 'receipt', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.receipt', value: props.summary.receipt, accent: 'bg-lime-500' },
  { key: 'credit_note', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.credit_note', value: props.summary.credit_note, accent: 'bg-rose-500' },
  { key: 'canva', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.canva', value: props.summary.canva, accent: 'bg-[rgb(var(--accent-500-rgb))]' },
  { key: 'chrome', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.chrome', value: props.summary.chrome, accent: 'bg-orange-500' },
])

const templateTypeOptions = computed(() => {
  return documentTypeCards.value.filter((type) => Number(type.value || 0) > 0)
})

const filteredTemplates = computed(() => {
  const query = templateSearch.value.trim().toLocaleLowerCase()

  return props.templates.filter((template) => {
    const matchesType = !templateTypeFilter.value || template.studio_type === templateTypeFilter.value
    const haystack = `${template.name || ''} ${template.description || ''} ${rendererLabel(template.renderer)}`.toLocaleLowerCase()

    return matchesType && (!query || haystack.includes(query))
  })
})

const rendererLabel = (renderer) => {
  return {
    internal: 'mPDF interno',
    chrome: 'Chrome PDF',
    browsershot: 'Browsershot',
    canva: 'Canva',
  }[renderer] || renderer
}

// Last-resort values, used only if the server sent no system document for a type.
// Empty surfaces are filled by the server from that type's default when printing.
const defaultLayoutSchema = {
  first_page_header_html: '',
  default_header_html: '',
  footer_html: '',
  body_html: '',
  styles_css: '',
  sections: [
    { key: 'identification', label: 'Identificação', visible: true },
    { key: 'technical_scope', label: 'Âmbito técnico', visible: true },
    { key: 'validation', label: 'Validação', visible: true },
  ],
  variable_catalog: [],
  canvas_blocks: [],
  document_font_family: 'DejaVu Sans, sans-serif',
  page_background_color: '#ffffff',
  background_image_path: '',
  background_size: 'cover',
  background_position: 'center center',
  background_repeat: 'no-repeat',
  table_header_background: '#f3f4f6',
  table_header_text_color: '#111827',
  table_border_color: '#d1d5db',
  table_font_size: 10,
  table_cell_padding: 5,
  table_summary_background: '#ffffff',
  table_summary_text_color: '#111827',
  table_summary_muted_color: '#4b5563',
  show_canvas_grid: true,
  show_canvas_rulers: true,
  snap_to_grid: true,
  snap_grid_size: 4,
  page_safe_area: true,
}

const studioDocumentTokens = {
  analysis: { number: '{document_code}', subject: '{customer_name}', title: 'Relatório analítico' },
  executive: { number: '{document_code}', subject: '{lab_name}', title: 'Resumo executivo' },
  proposal: { number: '{proposal_number}', subject: '{customer_name}', title: 'Proposta técnica-comercial' },
  export_certificate: { number: '{certificate_number}', subject: '{exporter_name}', title: 'Certificado de exportação' },
  import_certificate: { number: '{certificate_number}', subject: '{importer_name}', title: 'Certificado de importação' },
  quote: { number: '{document_number}', subject: '{customer_name}', title: 'Proforma comercial' },
  invoice: { number: '{document_number}', subject: '{customer_name}', title: 'Factura fiscal' },
  receipt: { number: '{document_number}', subject: '{customer_name}', title: 'Recibo de tesouraria' },
  credit_note: { number: '{document_number}', subject: '{customer_name}', title: 'Nota de crédito' },
}

const studioPresetExportSettings = (overrides = {}) => ({
  paper_size: 'A4',
  custom_page_width: null,
  custom_page_height: null,
  orientation: 'P',
  margin_top: 20,
  margin_bottom: 22,
  margin_left: 14,
  margin_right: 14,
  first_page_margin_top: 56,
  ...overrides,
})

const studioPresetLayout = (studioType, overrides = {}) => {
  const token = studioDocumentTokens[studioType] || studioDocumentTokens.analysis
  const commercialTypes = ['proposal', 'quote', 'invoice', 'receipt', 'credit_note']
  const certificateTypes = ['export_certificate', 'import_certificate']
  const isCommercial = commercialTypes.includes(studioType)
  const isCertificate = certificateTypes.includes(studioType)
  const accent = overrides.accent || (isCertificate ? '#3f6f58' : '#143d37')
  const gold = '#d9b05f'
  const canvasBlocks = [
    {
      id: `${studioType}-auth-qr`,
      title: 'QR de autenticidade',
      block_kind: 'qr_code',
      surface: 'content',
      page_scope: 'first',
      x: 82,
      y: 4,
      width: 13,
      min_height: 116,
      z_index: 30,
      padding: 10,
      background_color: 'rgba(255,255,255,0.94)',
      border_width: 1,
      border_color: 'rgba(222,211,191,0.95)',
      border_radius: 20,
      shadow_preset: 'soft',
      qr_content: `${token.number} · ${token.subject} · {issue_date}`,
      qr_label: 'Verificação',
      qr_foreground_color: accent,
      qr_background_color: '#ffffff',
      qr_error_correction: 'medium',
      qr_margin: 6,
    },
    {
      id: `${studioType}-signature-block`,
      title: isCommercial ? 'Assinatura e validação' : 'Assinatura técnica',
      block_kind: 'signature',
      surface: 'content',
      page_scope: 'first',
      x: isCommercial ? 56 : 58,
      y: 76,
      width: isCommercial ? 36 : 34,
      min_height: 118,
      z_index: 20,
      padding: 16,
      background_color: 'rgba(255,255,255,0.92)',
      border_width: 1,
      border_color: 'rgba(222,211,191,0.9)',
      border_radius: 24,
      shadow_preset: 'soft',
      signature_label: isCommercial ? 'Validação do documento' : 'Validação técnica',
      signature_name: '{{lab_name}}',
      signature_title: isCommercial ? 'Direcção comercial / financeira' : 'Direcção técnica',
      signature_line_style: 'solid',
      signature_align: 'left',
      signature_show_date: true,
      signature_date_label: 'Data: {issue_date}',
    },
  ]

  if (studioType === 'executive') {
    canvasBlocks.push({
      id: 'executive-studio-chart',
      title: 'Gráfico executivo',
      block_kind: 'chart_snapshot',
      surface: 'content',
      page_scope: 'first',
      x: 7,
      y: 52,
      width: 45,
      min_height: 210,
      z_index: 18,
      padding: 16,
      background_color: 'rgba(255,255,255,0.96)',
      border_width: 1,
      border_color: 'rgba(222,211,191,0.92)',
      border_radius: 26,
      shadow_preset: 'elevated',
      chart_title: '{executive_chart_title}',
      chart_caption: '{executive_chart_caption}',
      chart_type: 'line',
      chart_labels: '{executive_chart_labels}',
      chart_values: '{executive_chart_values}',
      chart_colors: `${accent}, ${gold}, #3f6f58`,
      chart_primary_color: accent,
      chart_background_color: '#f8f4ea',
      chart_show_values: true,
    })
  }

  if (studioType === 'analysis') {
    canvasBlocks.push({
      id: 'analysis-decision-rule-note',
      title: 'Regra de decisão',
      block_kind: 'rich_text',
      surface: 'content',
      page_scope: 'first',
      x: 6,
      y: 72,
      width: 42,
      min_height: 120,
      z_index: 16,
      padding: 18,
      background_color: '#fffaf0',
      border_width: 1,
      border_color: 'rgba(217,176,95,0.68)',
      border_radius: 24,
      shadow_preset: 'soft',
      content_html: '<p style="margin:0 0 6px; font-weight:700; color:#143d37;">Decisão e incerteza</p><p style="margin:0; color:#475a53;">{decision_rule}</p>',
    })
  }

  if (isCommercial) {
    canvasBlocks.push({
      id: `${studioType}-banking-details`,
      title: 'Dados bancários',
      block_kind: 'rich_text',
      surface: 'content',
      page_scope: 'first',
      x: 6,
      y: 76,
      width: 44,
      min_height: 118,
      z_index: 16,
      padding: 18,
      background_color: '#fffaf0',
      border_width: 1,
      border_color: 'rgba(217,176,95,0.68)',
      border_radius: 24,
      shadow_preset: 'soft',
      content_html: '<p style="margin:0 0 8px; font-weight:700; color:#143d37;">Dados bancários</p>{banking_details}',
    })
  }

  if (studioType === 'proposal') {
    canvasBlocks.push({
      id: 'proposal-client-acceptance',
      title: 'Aceitação do cliente',
      block_kind: 'signature',
      surface: 'content',
      page_scope: 'following',
      x: 52,
      y: 64,
      width: 40,
      min_height: 130,
      z_index: 22,
      padding: 16,
      background_color: 'rgba(255,255,255,0.94)',
      border_width: 1,
      border_color: 'rgba(222,211,191,0.9)',
      border_radius: 24,
      shadow_preset: 'soft',
      signature_label: 'Aceitação do cliente',
      signature_name: '{customer_name}',
      signature_title: 'Representante autorizado',
      signature_line_style: 'solid',
      signature_align: 'right',
      signature_show_date: true,
      signature_date_label: 'Data de aceite: ____ / ____ / ______',
    })
  }

  return {
    first_page_header_html: `<div style="border:1px solid #ded3bf; border-radius:22px; padding:14px 18px; background:#fffdf7;"><div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:${gold}; font-weight:700;">${token.title}</div><div style="margin-top:6px; font-size:18px; color:${accent}; font-weight:800;">${token.number}</div><div style="margin-top:4px; font-size:11px; color:#475a53;">${token.subject} · {issue_date}</div></div>`,
    default_header_html: `<div style="font-size:10px; color:#475a53; border-bottom:1px solid #ded3bf; padding-bottom:6px;">${token.title} · ${token.number} · ${token.subject}</div>`,
    footer_html: `<div style="font-size:9px; color:#475a53; border-top:1px solid #ded3bf; padding-top:6px;">Documento controlado · ${token.number} · Página {PAGENO}/{nbpg}</div>`,
    styles_css: 'body { color:#15231f; } .report-table th { background: var(--studio-table-header-background, #143d37); color: var(--studio-table-header-text-color, #ffffff); } .bilingual-label { display:block; margin-top:2px; font-size:8px; letter-spacing:0.08em; color:#64748b; text-transform:uppercase; } .studio-avoid-break { page-break-inside: avoid; break-inside: avoid; }',
    sections: [
      { key: 'cover', label: 'Capa e identificação', visible: true },
      { key: 'details', label: isCommercial ? 'Condições comerciais' : 'Dados técnicos', visible: true },
      { key: 'validation', label: 'Validação e assinatura', visible: true },
    ],
    variable_catalog: [],
    canvas_blocks: canvasBlocks,
    document_font_family: 'Manrope, DejaVu Sans, sans-serif',
    page_background_color: '#fffdf7',
    background_image_path: '',
    background_size: 'cover',
    background_position: 'center center',
    background_repeat: 'no-repeat',
    table_header_background: accent,
    table_header_text_color: '#ffffff',
    table_border_color: '#ded3bf',
    table_font_size: 10,
    table_cell_padding: 8,
    table_summary_background: '#fffdf7',
    table_summary_text_color: '#15231f',
    table_summary_muted_color: '#64748b',
    show_canvas_grid: true,
    show_canvas_rulers: true,
    snap_to_grid: true,
    snap_grid_size: 4,
    page_safe_area: true,
    ...overrides,
  }
}

const studioPresets = [
  {
    slug: 'analysis-core',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.analysis',
    name: 'Relatório analítico acreditável',
    category: 'analysis',
    theme_preset: 'compliance',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.analysis',
    description: 'Certificado multi-página com amostra, recepção, âmbito, resultados, incerteza, decisão e assinatura.',
    layout_schema: studioPresetLayout('analysis'),
    export_settings: studioPresetExportSettings({ margin_bottom: 24, first_page_margin_top: 58 }),
    body_html: '<section style="padding:30px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.78;">{lab_name}</div><h1 style="margin:12px 0 0; font-size:28px;">{report_title}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{certificate_code} · {customer_name} · Entrada {sample_entry_code}</p></section><section style="margin:18px 0;">{sample_details}</section><section style="margin:18px 0;">{collection_details}</section><section style="margin:18px 0;">{analytical_scope}</section><section style="margin:20px 0;">{results_table}</section><section style="margin:20px 0;">{analysis_chart_card}</section><section style="margin:18px 0; border-left:4px solid #d9b05f; background:#fffaf0; padding:16px; border-radius:16px;">{uncertainty_statement}<br />{decision_rule}</section><section style="margin-top:24px;">{signature_block}</section>',
  },
  {
    slug: 'executive-board',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.executive',
    name: 'Resumo executivo com gráficos',
    category: 'executive',
    theme_preset: 'corporate',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.executive',
    description: 'Pacote de direcção com KPIs, gráficos SVG no PDF, clientes activos e leitura de risco.',
    layout_schema: studioPresetLayout('executive'),
    export_settings: studioPresetExportSettings({ first_page_margin_top: 42 }),
    body_html: '<section style="padding:30px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.78;">{{lab_name}} · {{issue_date}}</div><h1 style="margin:12px 0 0; font-size:28px;">Resumo executivo</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{executive_summary}</p></section>{executive_kpis}<section style="margin:18px 0;">{executive_charts}</section><section style="margin-top:18px;"><h2 style="font-size:16px; color:#143d37;">Clientes com maior actividade recente</h2>{top_customers_table}</section>',
  },
  {
    slug: 'proposal-bridge',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.proposal',
    name: 'Proposta técnica-comercial',
    category: 'proposal',
    theme_preset: 'corporate',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.proposal',
    description: 'Proposta multipágina com âmbito técnico, condições comerciais, aceite do cliente e assinatura.',
    layout_schema: studioPresetLayout('proposal'),
    export_settings: studioPresetExportSettings(),
    body_html: '<section style="padding:30px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.78;">Proposta técnica-comercial</div><h1 style="margin:12px 0 0; font-size:28px;">{proposal_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{customer_name} · {service_location} · válida até {expiry_date}</p></section><section style="margin:18px 0;">{proposal_content}</section><section style="margin:20px 0;">{items_table}</section><section style="margin:20px 0;">{summary_table}</section><section style="margin-top:20px;">{banking_details}</section><section style="margin-top:20px;"><DataTable class="document-summary-table studio-avoid-break"><tr><td class="document-summary-cell" style="width:50%; vertical-align:top;">{proposal_acceptance_evidence}</td><td class="document-summary-cell" style="width:50%; vertical-align:top;">{proposal_authenticity}</td></tr></DataTable></section><section style="margin-top:18px;">{document_keywords}</section><section style="margin-top:24px;">{signature_block}</section>',
  },
  {
    slug: 'export-certificate',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.export_certificate',
    name: 'Certificado de exportação',
    category: 'export_certificate',
    theme_preset: 'field',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.export_certificate',
    description: 'Certificado de exportação com composição logística, produtos, expedição e assinatura.',
    layout_schema: studioPresetLayout('export_certificate'),
    export_settings: studioPresetExportSettings({ first_page_margin_top: 52 }),
  },
  {
    slug: 'import-certificate',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.import_certificate',
    name: 'Certificado de importação',
    category: 'import_certificate',
    theme_preset: 'field',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.import_certificate',
    description: 'Certificado de importação com composição logística, lotes, validade e assinatura técnica.',
    layout_schema: studioPresetLayout('import_certificate'),
    export_settings: studioPresetExportSettings({ first_page_margin_top: 52 }),
  },
  {
    slug: 'quote-commercial',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.quote',
    name: 'Proforma comercial',
    category: 'quote',
    theme_preset: 'corporate',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.quote',
    description: 'Proforma comercial com capa editorial, tabela de itens, resumo financeiro e assinatura.',
    layout_schema: studioPresetLayout('quote'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'invoice-fiscal',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.invoice',
    name: 'Factura fiscal',
    category: 'invoice',
    theme_preset: 'corporate',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.invoice',
    description: 'Factura com capa fiscal, tabela de itens, resumo financeiro e paginação premium.',
    layout_schema: studioPresetLayout('invoice'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'receipt-treasury',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.receipt',
    name: 'Recibo de tesouraria',
    category: 'receipt',
    theme_preset: 'corporate',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.receipt',
    description: 'Recibo com rastreio da liquidação, confirmação do pagamento e assinatura.',
    layout_schema: studioPresetLayout('receipt'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'credit-note-finance',
    nameKey: 'gestlab.general.labels.vap_report_studios.presets.names.credit_note',
    name: 'Nota de crédito financeira',
    category: 'credit_note',
    theme_preset: 'corporate',
    descriptionKey: 'gestlab.general.labels.vap_report_studios.presets.descriptions.credit_note',
    description: 'Nota de crédito com motivo de rectificação, impacto financeiro e validação.',
    layout_schema: studioPresetLayout('credit_note'),
    export_settings: studioPresetExportSettings(),
  },
]

const localPresetByCategory = computed(() => {
  return new Map(studioPresets.map((preset) => [preset.category, preset]))
})

const translatedPresetValue = (translationKey, fallback = '') => {
  if (!translationKey) {
    return fallback
  }

  const translated = trans(translationKey)

  return translated === translationKey ? fallback : translated
}

const baseModelPresets = computed(() => {
  const sourcePresets = props.systemPresets.length ? props.systemPresets : studioPresets

  return sourcePresets.map((preset) => {
    const localPreset = localPresetByCategory.value.get(preset.category) || {}
    const bodyHtml = preset.body_html || preset.layout_schema?.body_html || localPreset.body_html || ''
    const backendLayout = preset.layout_schema || {}
    const fallbackLayout = localPreset.layout_schema || {}

    return {
      ...localPreset,
      ...preset,
      slug: preset.slug || localPreset.slug || `system-${preset.category}`,
      name: translatedPresetValue(preset.name_key || preset.nameKey || localPreset.name_key || localPreset.nameKey, preset.name || localPreset.name || ''),
      description: translatedPresetValue(preset.description_key || preset.descriptionKey || localPreset.description_key || localPreset.descriptionKey, preset.description || localPreset.description || ''),
      body_html: bodyHtml,
      layout_schema: {
        ...fallbackLayout,
        ...backendLayout,
        body_html: bodyHtml,
      },
      export_settings: preset.export_settings || localPreset.export_settings || {},
    }
  })
})

const exportSettingsDefaults = {
  analysis: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 16,
    margin_bottom: 20,
    margin_left: 15,
    margin_right: 15,
    first_page_margin_top: 40,
  },
}

// A new model starts from the system document of its type: the one the server
// prints while no template is saved, so the editor never opens on another design.
const plainCopy = (value) => (value ? JSON.parse(JSON.stringify(value)) : {})
const systemPresetFor = (studioType) => props.systemPresets.find((preset) => preset.category === studioType) ?? null
const startingLayoutFor = (studioType) => ({
  ...structuredClone(defaultLayoutSchema),
  ...plainCopy(systemPresetFor(studioType)?.layout_schema),
})
const startingExportSettingsFor = (studioType) => ({
  ...structuredClone(exportSettingsDefaults.analysis),
  ...plainCopy(systemPresetFor(studioType)?.export_settings),
})

const form = useForm({
  name: '',
  studio_type: 'analysis',
  renderer: 'internal',
  status: 'draft',
  is_default: false,
  theme_preset: 'corporate',
  canva_design_url: '',
  description: '',
  layout_schema: startingLayoutFor('analysis'),
  export_settings: startingExportSettingsFor('analysis'),
})

// The layout a new model was given, to tell an untouched model from an edited one.
let untouchedLayout = JSON.stringify(form.layout_schema)

// The canvas fills each token with what the server prints in the preview of this
// type of document; the local samples only cover tokens the server did not send.
const previewReplacements = computed(() => ({
  ...(previewReplacementsByType[form.studio_type] || previewReplacementsByType.analysis),
  ...(props.canvasSampleValues?.shared || {}),
  ...(props.canvasSampleValues?.types?.[form.studio_type] || {}),
}))

const previewPdfHref = computed(() => {
  return editingTemplate.value?.preview_pdf_path || ''
})

const resetForm = () => {
  editingTemplate.value = null
  form.clearErrors()
  form.name = ''
  form.studio_type = 'analysis'
  form.renderer = 'internal'
  form.status = 'draft'
  form.is_default = false
  form.theme_preset = 'corporate'
  form.canva_design_url = ''
  form.description = ''
  form.layout_schema = startingLayoutFor('analysis')
  form.export_settings = startingExportSettingsFor('analysis')
  untouchedLayout = JSON.stringify(form.layout_schema)
}

const startNewTemplate = () => {
  resetForm()
  studioWorkspaceView.value = 'editor'
}

const applyDefaultsForStudio = (studioType) => {
  form.export_settings = startingExportSettingsFor(studioType)

  // Changing the type of a new, untouched model brings that type's document with it.
  if (!editingTemplate.value && JSON.stringify(form.layout_schema) === untouchedLayout) {
    form.layout_schema = startingLayoutFor(studioType)
    untouchedLayout = JSON.stringify(form.layout_schema)
  }
}

const editTemplate = (template) => {
  editingTemplate.value = template
  form.clearErrors()
  form.name = template.name
  form.studio_type = template.studio_type
  form.renderer = template.renderer
  form.status = template.status
  form.is_default = Boolean(template.is_default)
  form.theme_preset = template.theme_preset || 'corporate'
  form.canva_design_url = template.canva_design_url || ''
  form.description = template.description || ''
  form.layout_schema = {
    ...startingLayoutFor(template.studio_type),
    ...(template.layout_schema || {}),
  }
  form.export_settings = {
    ...startingExportSettingsFor(template.studio_type),
    ...(template.export_settings || {}),
  }
  studioWorkspaceView.value = 'editor'
}

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      resetForm()
      studioWorkspaceView.value = 'library'
    },
  }

  if (editingTemplate.value?.id) {
    form.put(route('report-studios.update', editingTemplate.value.id), options)
    return
  }

  form.post(route('report-studios.store'), options)
}

const destroyTemplate = (template) => {
  archiveTemplate.value = template
}

const cancelArchiveTemplate = () => {
  if (form.processing) {
    return
  }

  archiveTemplate.value = null
}

const confirmArchiveTemplate = () => {
  if (!archiveTemplate.value?.id) {
    return
  }

  const template = archiveTemplate.value

  form.delete(route('report-studios.destroy', template.id), {
    preserveScroll: true,
    onSuccess: () => {
      if (editingTemplate.value?.id === template.id) {
        resetForm()
      }

      archiveTemplate.value = null
    },
    onError: () => {
      archiveTemplate.value = template
    },
  })
}

const formatDate = (value) => {
  if (!value) {
    return trans('gestlab.general.labels.vap_report_studios.index.format.now')
  }

  return new Date(value).toLocaleString()
}

const onStudioTypeUpdate = (studioType) => {
  applyDefaultsForStudio(studioType)
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.vap_report_studios.index.hero.title')" :lede="$t('gestlab.general.labels.vap_report_studios.index.hero.description')">
      <template #actions>
        <button type="button" class="ds-button ds-button-primary shrink-0" @click="startNewTemplate">
          <DocumentPlusIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_report_studios.index.hero.new_template') }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="card in studioSummaryCards" :key="card.key" class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t(card.labelKey) }}</dt>
        <dd class="pl-cell-text"><span class="text-xl font-bold text-[var(--ds-text)]">{{ card.value }}</span>
            <span class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ $t(card.hintKey) }}</span></dd>
      </div>
    </dl>

    <nav class="grid grid-cols-2 px-3 sm:flex sm:px-6" aria-label="Áreas do estúdio documental">
      <button
        type="button"
        class="-mb-px inline-flex min-h-12 min-w-0 items-center justify-center gap-2 whitespace-nowrap border-b-2 px-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--primary-500-rgb))] focus-visible:ring-inset sm:shrink-0 sm:px-4 sm:text-sm"
        :class="studioWorkspaceView === 'library' ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--accent-200-rgb))] dark:text-[rgb(var(--accent-100-rgb))]' : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'"
        @click="studioWorkspaceView = 'library'"
      >
        <FolderOpenIcon class="hidden h-4 w-4 sm:block" />
        Biblioteca de modelos
        <span class="ds-badge ds-badge-neutral">{{ templates.length }}</span>
      </button>
      <button
        type="button"
        class="-mb-px inline-flex min-h-12 min-w-0 items-center justify-center gap-2 whitespace-nowrap border-b-2 px-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--primary-500-rgb))] focus-visible:ring-inset sm:shrink-0 sm:px-4 sm:text-sm"
        :class="studioWorkspaceView === 'editor' ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--accent-200-rgb))] dark:text-[rgb(var(--accent-100-rgb))]' : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'"
        @click="studioWorkspaceView = 'editor'"
      >
        <DocumentPlusIcon class="hidden h-4 w-4 sm:block" />
        {{ editingTemplate ? 'Editar modelo' : 'Novo modelo' }}
      </button>
    </nav>

    <section v-if="studioWorkspaceView === 'library'" class="ds-table-shell">
      <div class="ds-table-summary gap-4 px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">Biblioteca controlada</p>
          <h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_report_studios.index.saved.title') }}</h2>
        </div>
        <div class="grid w-full gap-2 sm:w-auto sm:grid-cols-[minmax(14rem,20rem)_12rem]">
          <label class="relative block">
            <span class="sr-only">Pesquisar modelos</span>
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput v-model="templateSearch" type="search" class="ds-field pl-9" placeholder="Pesquisar modelos" />
          </label>
          <BaseSelect v-model="templateTypeFilter" class="ds-field">
            <option value="">Todos os tipos</option>
            <option v-for="type in templateTypeOptions" :key="type.key" :value="type.key">{{ $t(type.labelKey) }}</option>
          </BaseSelect>
        </div>
      </div>

      <div v-if="filteredTemplates.length" class="hidden overflow-x-auto lg:block">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
          <thead class="ds-table-head">
            <tr>
              <th class="px-5 py-3 ds-table-heading">Modelo</th>
              <th class="px-5 py-3 ds-table-heading">Tipo e saída</th>
              <th class="px-5 py-3 ds-table-heading">Estado</th>
              <th class="px-5 py-3 ds-table-heading">Última revisão</th>
              <th class="px-5 py-3 text-right ds-table-heading">Acções</th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="template in filteredTemplates" :key="template.id" class="ds-table-row">
              <td class="px-5 py-4">
                <p class="font-bold text-[var(--ds-text)]">{{ template.name }}</p>
                <p class="mt-1 max-w-md truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ template.description || $t('gestlab.general.labels.vap_report_studios.index.saved.no_description') }}</p>
              </td>
              <td class="px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ $t(documentTypeCards.find((type) => type.key === template.studio_type)?.labelKey || '') }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ rendererLabel(template.renderer) }}</p>
              </td>
              <td class="px-5 py-4">
                <span :class="['ds-badge', template.status === 'active' ? 'ds-badge-success' : template.status === 'archived' ? 'ds-badge-neutral' : 'ds-badge-warning']">{{ template.status }}</span>
                <span v-if="template.is_default" class="ds-badge ds-badge-info ml-1">{{ $t('gestlab.general.labels.vap_report_studios.index.saved.default_badge') }}</span>
              </td>
              <td class="px-5 py-4 text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ formatDate(template.updated_at) }}<span v-if="template.updated_by" class="mt-1 block">{{ template.updated_by }}</span>
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-1">
                  <a :href="template.preview_pdf_path" target="_blank" class="ds-table-action" title="Pré-visualizar PDF"><PhotoIcon class="h-4 w-4" /></a>
                  <button type="button" class="ds-table-action" title="Editar modelo" @click="editTemplate(template)"><DocumentPlusIcon class="h-4 w-4" /></button>
                  <button type="button" class="ds-table-action ds-table-action-danger" title="Arquivar modelo" @click="destroyTemplate(template)">×</button>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="filteredTemplates.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
        <article v-for="template in filteredTemplates" :key="`mobile-${template.id}`" class="space-y-3 p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h3 class="truncate font-bold text-[var(--ds-text)]">{{ template.name }}</h3>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ rendererLabel(template.renderer) }} · {{ formatDate(template.updated_at) }}</p>
            </div>
            <span :class="['ds-badge', template.status === 'active' ? 'ds-badge-success' : 'ds-badge-warning']">{{ template.status }}</span>
          </div>
          <div class="flex flex-wrap gap-2">
            <a :href="template.preview_pdf_path" target="_blank" class="ds-button ds-button-secondary">PDF</a>
            <button type="button" class="ds-button ds-button-secondary" @click="editTemplate(template)">Editar</button>
            <button type="button" class="ds-button ds-button-danger" @click="destroyTemplate(template)">Arquivar</button>
          </div>
        </article>
      </div>

      <div v-if="!filteredTemplates.length" class="ds-empty-state m-5 p-8 text-center text-sm">
        {{ templates.length ? 'Nenhum modelo corresponde aos filtros.' : $t('gestlab.general.labels.vap_report_studios.index.saved.empty') }}
      </div>
    </section>

    <report-studio-workbench
      v-else
      :title="$t('gestlab.general.labels.vap_report_studios.index.workbench.title')"
      :intro="$t('gestlab.general.labels.vap_report_studios.index.workbench.intro')"
      :form="form"
      :layout-schema="form.layout_schema"
      :export-settings="form.export_settings"
      :placeholders="Object.keys(previewReplacements)"
      :presets="baseModelPresets"
      :preview-replacements="previewReplacements"
      :preview-pdf-href="previewPdfHref"
      :draft-preview-href="route('report-studios.preview-draft-pdf')"
      :asset-library="props.studioAssets"
      :document-base-css="props.documentBaseCss"
      :renderer-capabilities="props.rendererCapabilities"
      :initial-draft-label="editingTemplate?.name ? $t('gestlab.general.labels.vap_report_studios.index.workbench.editing_label', { name: editingTemplate.name }) : ''"
      :back-href="route('report-studios.index')"
      :back-label="$t('gestlab.general.labels.vap_report_studios.index.workbench.back_label')"
      :submit-label="$t('gestlab.general.labels.vap_report_studios.index.workbench.submit_label')"
      @submit="submit"
      @update:studio-type="onStudioTypeUpdate"
    />

    <DialogModal :show="Boolean(archiveTemplate)" max-width="lg" @close="cancelArchiveTemplate">
      <template #title>
        {{ $t('gestlab.general.labels.vap_report_studios.index.archive.title') }}
      </template>

      <template #content>
        <div class="space-y-3">
          <p>
            {{ $t('gestlab.general.labels.vap_report_studios.index.archive.message', { name: archiveTemplate?.name || '' }) }}
          </p>
          <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">
            {{ $t('gestlab.general.labels.vap_report_studios.index.archive.warning') }}
          </p>
        </div>
      </template>

      <template #footer>
        <button
          type="button"
          class="ds-button ds-button-secondary"
          :disabled="form.processing"
          @click="cancelArchiveTemplate"
        >
          {{ $t('gestlab.general.labels.vap_report_studios.index.archive.cancel') }}
        </button>
        <button
          type="button"
          class="ds-button ds-button-danger"
          :disabled="form.processing"
          @click="confirmArchiveTemplate"
        >
          {{ $t('gestlab.general.labels.vap_report_studios.index.archive.confirm') }}
        </button>
      </template>
    </DialogModal>
  </div>
</template>
