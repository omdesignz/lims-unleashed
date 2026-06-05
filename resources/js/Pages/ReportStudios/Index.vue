<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import reportStudioWorkbench from '@/Components/report-studio/studio-workbench.vue'
import DialogModal from '@/Components/dialog-modal.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { previewReplacementsByType } from '@/Support/report-studio-preview-html.mjs'
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'

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
  systemPresets: {
    type: Array,
    default: () => [],
  },
})

const editingTemplate = ref(null)
const archiveTemplate = ref(null)

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
  { key: 'canva', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.canva', value: props.summary.canva, accent: 'bg-[#d9b05f]' },
  { key: 'chrome', labelKey: 'gestlab.general.labels.vap_report_studios.index.document_types.chrome', value: props.summary.chrome, accent: 'bg-orange-500' },
])

const rendererLabel = (renderer) => {
  return {
    internal: 'mPDF interno',
    chrome: 'Chrome PDF',
    browsershot: 'Browsershot',
    canva: 'Canva',
  }[renderer] || renderer
}

const defaultLayoutSchema = {
  first_page_header_html: '<div style="border-bottom:1px solid #143d37; padding-bottom:10px;"><h2 style="margin:0;color:#143d37;">{{lab_name}}</h2><p style="margin-top:6px;color:#475a53;">{{document_code}}</p></div>',
  default_header_html: '<div style="font-size:10px;color:#475a53;">{{document_code}} · {{issue_date}}</div>',
  footer_html: '<div style="font-size:9px; color:#475a53;">Documento controlado · Página {PAGENO}/{nbpg}</div>',
  body_html: '<h1>{report_title}</h1><p><strong>Código:</strong> {certificate_code}</p><p><strong>Cliente:</strong> {customer_name}</p><p><strong>Entrada de amostra:</strong> {sample_entry_code}</p><p><strong>Código laboratorial:</strong> {lab_code}</p><section style="margin:18px 0;">{sample_details}</section><section style="margin:18px 0;">{collection_details}</section><section style="margin:18px 0;">{analytical_scope}</section><section style="margin:18px 0;">{results_table}</section><section style="margin:18px 0;">{analysis_chart_card}</section><p>{uncertainty_statement}</p><p>{decision_rule}</p><div style="margin-top:24px;">{signature_block}</div>',
  styles_css: 'table { width: 100%; border-collapse: collapse; } th, td { padding: 6px; border: 1px solid #ded3bf; }',
  sections: [
    { key: 'summary', label: 'Resumo', visible: true },
    { key: 'results', label: 'Tabela de resultados', visible: true },
    { key: 'interpretation', label: 'Interpretação', visible: true },
  ],
  variable_catalog: [],
  canvas_blocks: [],
  document_font_family: 'Manrope, DejaVu Sans, sans-serif',
  background_image_path: '',
  background_size: 'cover',
  background_position: 'center center',
  background_repeat: 'no-repeat',
  table_header_background: '#143d37',
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
    description: 'Certificado multi-página com amostra, recepção, escopo, resultados, incerteza, decisão e assinatura.',
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
    description: 'Proposta multipágina com escopo técnico, condições comerciais, aceite do cliente e assinatura.',
    layout_schema: studioPresetLayout('proposal'),
    export_settings: studioPresetExportSettings(),
    body_html: '<section style="padding:30px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.78;">Proposta técnica-comercial</div><h1 style="margin:12px 0 0; font-size:28px;">{proposal_number}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{customer_name} · {service_location} · válida até {expiry_date}</p></section><section style="margin:18px 0;">{proposal_content}</section><section style="margin:20px 0;">{items_table}</section><section style="margin:20px 0;">{summary_table}</section><section style="margin-top:20px;">{banking_details}</section><section style="margin-top:20px;"><table class="document-summary-table studio-avoid-break"><tr><td class="document-summary-cell" style="width:50%; vertical-align:top;">{proposal_acceptance_evidence}</td><td class="document-summary-cell" style="width:50%; vertical-align:top;">{proposal_authenticity}</td></tr></table></section><section style="margin-top:18px;">{document_keywords}</section><section style="margin-top:24px;">{signature_block}</section>',
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
    description: 'Recibo com rastreio de liquidação, confirmação de recebimento e assinatura.',
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
    margin_top: 20,
    margin_bottom: 24,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 58,
  },
  executive: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 20,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 42,
  },
  export_certificate: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 20,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 52,
  },
  import_certificate: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 20,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 52,
  },
  quote: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 22,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 56,
  },
  invoice: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 22,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 56,
  },
  receipt: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 22,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 56,
  },
  credit_note: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 22,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 56,
  },
  proposal: {
    paper_size: 'A4',
    custom_page_width: null,
    custom_page_height: null,
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 22,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 56,
  },
}

const form = useForm({
  name: '',
  studio_type: 'analysis',
  renderer: 'internal',
  status: 'draft',
  is_default: false,
  theme_preset: 'corporate',
  canva_design_url: '',
  description: '',
  layout_schema: structuredClone(defaultLayoutSchema),
  export_settings: structuredClone(exportSettingsDefaults.analysis),
})

const previewReplacements = computed(() => {
  return previewReplacementsByType[form.studio_type] || previewReplacementsByType.analysis
})

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
  form.layout_schema = structuredClone(defaultLayoutSchema)
  form.export_settings = structuredClone(exportSettingsDefaults.analysis)
}

const applyDefaultsForStudio = (studioType) => {
  const defaults = exportSettingsDefaults[studioType] || exportSettingsDefaults.analysis
  form.export_settings = structuredClone(defaults)
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
    ...structuredClone(defaultLayoutSchema),
    ...(template.layout_schema || {}),
  }
  form.export_settings = {
    ...structuredClone(exportSettingsDefaults[template.studio_type] || exportSettingsDefaults.analysis),
    ...(template.export_settings || {}),
  }
}

const submit = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => resetForm(),
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
  <div class="space-y-8 font-sans" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_26px_80px_rgba(20,61,55,0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="relative isolate px-6 py-8 text-[#15231f] dark:text-[#f7f1e7] sm:px-8 lg:px-10">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-200-rgb)/0.42),transparent_32%),radial-gradient(circle_at_88%_0%,rgb(var(--accent-300-rgb)/0.28),transparent_30%),linear-gradient(135deg,rgb(255_253_247/0.98),rgb(244_239_228/0.92))] dark:bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-500-rgb)/0.18),transparent_34%),radial-gradient(circle_at_88%_0%,rgb(var(--accent-300-rgb)/0.13),transparent_30%),linear-gradient(135deg,#07110f,#10231f)]" />
        <div class="absolute right-0 top-0 -z-10 h-64 w-64 rounded-full bg-[rgb(var(--primary-500-rgb)/0.12)] blur-3xl dark:bg-[rgb(var(--accent-300-rgb)/0.08)]" />
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-4xl">
            <div class="inline-flex rounded-full border border-[rgb(var(--accent-300-rgb)/0.55)] bg-[rgb(var(--accent-50-rgb)/0.82)] px-3 py-1 text-xs font-black uppercase tracking-[0.28em] text-[rgb(var(--primary-900-rgb))] shadow-sm dark:border-[rgb(var(--accent-300-rgb)/0.25)] dark:bg-[rgb(var(--accent-300-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              {{ $t('gestlab.general.labels.vap_report_studios.index.hero.badge') }}
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
              {{ $t('gestlab.general.labels.vap_report_studios.index.hero.title') }}
            </h1>
            <p class="mt-4 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf] sm:text-base">
              {{ $t('gestlab.general.labels.vap_report_studios.index.hero.description') }}
            </p>
          </div>
          <div class="flex flex-wrap gap-3">
            <a
              v-if="previewPdfHref"
              :href="previewPdfHref"
              target="_blank"
              class="inline-flex items-center justify-center rounded-2xl border border-[#ded3bf] bg-white/75 px-4 py-3 text-sm font-bold text-[#15231f] shadow-sm transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-white dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:hover:border-[rgb(var(--primary-400-rgb)/0.55)]"
            >
              {{ $t('gestlab.general.labels.vap_report_studios.index.hero.preview_pdf') }}
            </a>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-3 text-sm font-bold text-white shadow-[0_18px_45px_rgb(var(--primary-900-rgb)/0.18)] transition hover:bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--accent-300-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--accent-200-rgb))]"
              @click="resetForm"
            >
              {{ $t('gestlab.general.labels.vap_report_studios.index.hero.new_template') }}
            </button>
          </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <article
            v-for="card in studioSummaryCards"
            :key="card.key"
            class="rounded-3xl border border-[#ded3bf]/70 bg-white/65 p-2 shadow-sm ring-1 ring-white/70 backdrop-blur dark:border-[#25443c] dark:bg-[#10231f]/70 dark:ring-white/10"
          >
            <div class="flex items-center justify-between gap-3 px-4 pb-3 pt-2">
              <h3 class="text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ $t(card.labelKey) }}</h3>
              <span class="h-2 w-2 rounded-full bg-[rgb(var(--accent-300-rgb))] shadow-[0_0_20px_rgb(var(--accent-300-rgb)/0.55)]" />
            </div>
            <div class="rounded-2xl bg-[#fffdf7] p-4 text-[#15231f] shadow-sm dark:bg-[#07110f] dark:text-[#f7f1e7]">
              <div class="text-3xl font-black">{{ card.value }}</div>
              <div class="mt-1 text-xs font-semibold text-[#6b7b74] dark:text-[#83978d]">{{ $t(card.hintKey) }}</div>
            </div>
          </article>
        </div>
      </div>

      <div class="grid gap-px bg-[#ded3bf]/80 p-px dark:bg-[#25443c] sm:grid-cols-2 lg:grid-cols-5">
        <article
          v-for="card in documentTypeCards"
          :key="card.key"
          class="flex items-center justify-between gap-3 bg-[#fffaf0]/88 px-5 py-4 text-sm font-semibold text-[#475a53] dark:bg-[#10231f]/88 dark:text-[#cbd8cf]"
        >
          <span class="inline-flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full" :class="card.accent" />
            {{ $t(card.labelKey) }}
          </span>
          <span class="font-black text-[#15231f] dark:text-[#f7f1e7]">{{ card.value || 0 }}</span>
        </article>
      </div>
    </section>

    <section class="rounded-[2rem] border border-[#ded3bf] bg-[#f4efe4]/80 p-2 shadow-[0_18px_55px_rgba(20,61,55,0.06)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f]/70 dark:ring-white/10">
      <div class="flex flex-col gap-3 px-4 pb-4 pt-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-lg font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ $t('gestlab.general.labels.vap_report_studios.index.saved.title') }}</h2>
          <p class="mt-1 text-sm font-medium text-[#475a53] dark:text-[#cbd8cf]">{{ $t('gestlab.general.labels.vap_report_studios.index.saved.description') }}</p>
        </div>
        <div class="rounded-full border border-[#ded3bf] bg-[#fffdf7] px-3 py-1.5 text-xs font-bold text-[#475a53] shadow-sm dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#cbd8cf]">
          {{ $t('gestlab.general.labels.vap_report_studios.index.saved.count', { count: templates.length }) }}
        </div>
      </div>

      <div v-if="templates.length" class="space-y-3 rounded-[1.45rem] bg-[#fffdf7] p-4 shadow-sm shadow-primary-950/5 dark:bg-[#07110f] dark:shadow-none">
        <article v-for="template in templates" :key="template.id" class="group rounded-2xl border border-[#ded3bf]/80 bg-[#fffdf7] p-4 transition hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb))] hover:shadow-[0_20px_50px_rgb(var(--primary-900-rgb)/0.10)] dark:border-[#25443c] dark:bg-[#10231f]/70 dark:hover:border-[rgb(var(--primary-400-rgb)/0.55)]">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-[#f4efe4] px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-[#475a53] dark:bg-[#07110f] dark:text-[#cbd8cf]">{{ template.studio_type }}</span>
                <span class="rounded-full bg-[rgb(var(--primary-50-rgb))] px-2.5 py-1 text-xs font-bold text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:text-[rgb(var(--primary-100-rgb))]">{{ rendererLabel(template.renderer) }}</span>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ template.status }}</span>
                <span v-if="template.is_default" class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ $t('gestlab.general.labels.vap_report_studios.index.saved.default_badge') }}</span>
              </div>
              <h3 class="text-lg font-bold text-[#15231f] transition group-hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#f7f1e7] dark:group-hover:text-[rgb(var(--primary-200-rgb))]">{{ template.name }}</h3>
              <p class="text-sm leading-6 text-[#475a53] dark:text-[#cbd8cf]">{{ template.description || $t('gestlab.general.labels.vap_report_studios.index.saved.no_description') }}</p>
              <div class="text-xs font-medium text-[#84958d] dark:text-[#83978d]">
                {{ $t('gestlab.general.labels.vap_report_studios.index.saved.updated_at', { date: formatDate(template.updated_at) }) }}<span v-if="template.updated_by"> · {{ template.updated_by }}</span>
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <a
                :href="template.preview_pdf_path"
                target="_blank"
                class="rounded-xl border border-[rgb(var(--primary-200-rgb))] bg-[rgb(var(--primary-50-rgb))] px-3 py-2 text-sm font-bold text-[rgb(var(--primary-800-rgb))] hover:bg-[rgb(var(--primary-100-rgb))] dark:border-[rgb(var(--primary-400-rgb)/0.25)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.20)]"
              >
                {{ $t('gestlab.general.labels.vap_report_studios.index.saved.preview_pdf') }}
              </a>
              <button type="button" class="rounded-xl border border-[#ded3bf] bg-[#fffdf7] px-3 py-2 text-sm font-bold text-[#475a53] hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[#f8f4ea] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#cbd8cf] dark:hover:bg-[#10231f]" @click="editTemplate(template)">
                {{ $t('gestlab.general.labels.vap_report_studios.index.saved.edit') }}
              </button>
              <button type="button" class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-500/30 dark:bg-slate-950 dark:text-rose-300 dark:hover:bg-rose-500/10" @click="destroyTemplate(template)">
                {{ $t('gestlab.general.labels.vap_report_studios.index.saved.archive') }}
              </button>
            </div>
          </div>
        </article>
      </div>
      <div v-else class="rounded-[1.45rem] border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
        {{ $t('gestlab.general.labels.vap_report_studios.index.saved.empty') }}
      </div>
    </section>

    <report-studio-workbench
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
          <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-100">
            {{ $t('gestlab.general.labels.vap_report_studios.index.archive.warning') }}
          </p>
        </div>
      </template>

      <template #footer>
        <button
          type="button"
          class="rounded-xl border border-[#ded3bf] bg-[#fffdf7] px-4 py-2.5 text-sm font-bold text-[#475a53] transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[#f8f4ea] disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#cbd8cf] dark:hover:bg-[#10231f]"
          :disabled="form.processing"
          @click="cancelArchiveTemplate"
        >
          {{ $t('gestlab.general.labels.vap_report_studios.index.archive.cancel') }}
        </button>
        <button
          type="button"
          class="rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-black text-white shadow-[0_16px_35px_rgba(190,18,60,0.24)] transition hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-rose-500 dark:text-white dark:hover:bg-rose-400"
          :disabled="form.processing"
          @click="confirmArchiveTemplate"
        >
          {{ $t('gestlab.general.labels.vap_report_studios.index.archive.confirm') }}
        </button>
      </template>
    </DialogModal>
  </div>
</template>
