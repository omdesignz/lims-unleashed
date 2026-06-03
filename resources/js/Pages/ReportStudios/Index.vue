<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import reportStudioWorkbench from '@/Components/report-studio/studio-workbench.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

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

const studioSummaryCards = computed(() => [
  {
    label: 'Modelos activos',
    value: props.summary.total,
    hint: 'Modelos documentais controlados',
    tone: 'from-primary-950 to-primary-700',
  },
  {
    label: 'Relatórios laboratoriais',
    value: (props.summary.analysis || 0) + (props.summary.executive || 0),
    hint: 'Saída técnica e executiva',
    tone: 'from-primary-800 to-primary-600',
  },
  {
    label: 'Documentos comerciais',
    value: (props.summary.proposal || 0) + (props.summary.quote || 0) + (props.summary.invoice || 0) + (props.summary.receipt || 0) + (props.summary.credit_note || 0),
    hint: 'Propostas, facturas e recibos',
    tone: 'from-emerald-700 to-teal-700',
  },
  {
    label: 'Media reutilizável',
    value: props.studioAssets.length,
    hint: 'Galeria, assinaturas e marcas',
    tone: 'from-amber-600 to-orange-700',
  },
])

const documentTypeCards = computed(() => [
  { label: 'Análises', value: props.summary.analysis, accent: 'bg-[rgb(var(--primary-500-rgb))]' },
  { label: 'Executivos', value: props.summary.executive, accent: 'bg-[rgb(var(--accent-400-rgb))]' },
  { label: 'Propostas', value: props.summary.proposal, accent: 'bg-emerald-500' },
  { label: 'Exportação', value: props.summary.export_certificate, accent: 'bg-[rgb(var(--primary-600-rgb))]' },
  { label: 'Importação', value: props.summary.import_certificate, accent: 'bg-[rgb(var(--primary-400-rgb))]' },
  { label: 'Proformas', value: props.summary.quote, accent: 'bg-teal-500' },
  { label: 'Facturas', value: props.summary.invoice, accent: 'bg-[rgb(var(--accent-500-rgb))]' },
  { label: 'Recibos', value: props.summary.receipt, accent: 'bg-lime-500' },
  { label: 'Notas crédito', value: props.summary.credit_note, accent: 'bg-rose-500' },
  { label: 'Canva', value: props.summary.canva, accent: 'bg-[#d9b05f]' },
  { label: 'Chrome PDF', value: props.summary.chrome, accent: 'bg-orange-500' },
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
  body_html: '<h1>{report_title}</h1><p><strong>Código:</strong> {certificate_code}</p><p><strong>Cliente:</strong> {customer_name}</p><p><strong>Entrada de amostra:</strong> {sample_entry_code}</p><p><strong>Código laboratorial:</strong> {lab_code}</p><section style="margin:18px 0;">{sample_details}</section><section style="margin:18px 0;">{collection_details}</section><section style="margin:18px 0;">{analytical_scope}</section><section style="margin:18px 0;">{results_table}</section><p>{uncertainty_statement}</p><p>{decision_rule}</p><div style="margin-top:24px;">{signature_block}</div>',
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
      chart_title: 'Desempenho do ciclo técnico',
      chart_caption: 'Indicador visual preparado para PDF Chrome e mPDF.',
      chart_type: 'line',
      chart_labels: 'Recepção, Validação, Emissão',
      chart_values: '18, 12, 9',
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
    name: 'Relatório analítico acreditável',
    category: 'analysis',
    theme_preset: 'compliance',
    description: 'Certificado multi-página com amostra, recepção, escopo, resultados, incerteza, decisão e assinatura.',
    layout_schema: studioPresetLayout('analysis'),
    export_settings: studioPresetExportSettings({ margin_bottom: 24, first_page_margin_top: 58 }),
    body_html: '<section style="padding:30px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.78;">{lab_name}</div><h1 style="margin:12px 0 0; font-size:28px;">{report_title}</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{certificate_code} · {customer_name} · Entrada {sample_entry_code}</p></section><section style="margin:18px 0;">{sample_details}</section><section style="margin:18px 0;">{collection_details}</section><section style="margin:18px 0;">{analytical_scope}</section><section style="margin:20px 0;">{results_table}</section><section style="margin:18px 0; border-left:4px solid #d9b05f; background:#fffaf0; padding:16px; border-radius:16px;">{uncertainty_statement}<br />{decision_rule}</section><section style="margin-top:24px;">{signature_block}</section>',
  },
  {
    slug: 'executive-board',
    name: 'Resumo executivo com gráficos',
    category: 'executive',
    theme_preset: 'corporate',
    description: 'Pacote de direcção com KPIs, gráficos SVG no PDF, clientes activos e leitura de risco.',
    layout_schema: studioPresetLayout('executive'),
    export_settings: studioPresetExportSettings({ first_page_margin_top: 42 }),
    body_html: '<section style="padding:30px; border-radius:24px; background:linear-gradient(135deg,#07110f,#143d37); color:#ffffff; margin-bottom:22px;"><div style="font-size:11px; letter-spacing:0.18em; text-transform:uppercase; opacity:0.78;">{{lab_name}} · {{issue_date}}</div><h1 style="margin:12px 0 0; font-size:28px;">Resumo executivo</h1><p style="margin:12px 0 0; font-size:14px; opacity:0.88;">{executive_summary}</p></section>{executive_kpis}<section style="margin:18px 0;">{executive_charts}</section><section style="margin-top:18px;"><h2 style="font-size:16px; color:#143d37;">Clientes com maior actividade recente</h2>{top_customers_table}</section>',
  },
  {
    slug: 'proposal-bridge',
    name: 'Proposta técnica-comercial',
    category: 'proposal',
    theme_preset: 'corporate',
    description: 'Proposta multipágina com escopo técnico, condições comerciais, aceite do cliente e assinatura.',
    layout_schema: studioPresetLayout('proposal'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'export-certificate',
    name: 'Certificado de exportação',
    category: 'export_certificate',
    theme_preset: 'field',
    description: 'Certificado de exportação com composição logística, produtos, expedição e assinatura.',
    layout_schema: studioPresetLayout('export_certificate'),
    export_settings: studioPresetExportSettings({ first_page_margin_top: 52 }),
  },
  {
    slug: 'import-certificate',
    name: 'Certificado de importação',
    category: 'import_certificate',
    theme_preset: 'field',
    description: 'Certificado de importação com composição logística, lotes, validade e assinatura técnica.',
    layout_schema: studioPresetLayout('import_certificate'),
    export_settings: studioPresetExportSettings({ first_page_margin_top: 52 }),
  },
  {
    slug: 'quote-commercial',
    name: 'Proforma comercial',
    category: 'quote',
    theme_preset: 'corporate',
    description: 'Proforma comercial com capa editorial, tabela de itens, resumo financeiro e assinatura.',
    layout_schema: studioPresetLayout('quote'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'invoice-fiscal',
    name: 'Factura fiscal',
    category: 'invoice',
    theme_preset: 'corporate',
    description: 'Factura com capa fiscal, tabela de itens, resumo financeiro e paginação premium.',
    layout_schema: studioPresetLayout('invoice'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'receipt-treasury',
    name: 'Recibo de tesouraria',
    category: 'receipt',
    theme_preset: 'corporate',
    description: 'Recibo com rastreio de liquidação, confirmação de recebimento e assinatura.',
    layout_schema: studioPresetLayout('receipt'),
    export_settings: studioPresetExportSettings(),
  },
  {
    slug: 'credit-note-finance',
    name: 'Nota de crédito financeira',
    category: 'credit_note',
    theme_preset: 'corporate',
    description: 'Nota de crédito com motivo de rectificação, impacto financeiro e validação.',
    layout_schema: studioPresetLayout('credit_note'),
    export_settings: studioPresetExportSettings(),
  },
]

const localPresetByCategory = computed(() => {
  return new Map(studioPresets.map((preset) => [preset.category, preset]))
})

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
      description: preset.description || localPreset.description || '',
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

const previewReplacementsByType = {
  analysis: {
    '{report_title}': 'Relatório Analítico de Ensaios',
    '{certificate_code}': 'QC-2026-0142',
    '{document_code}': 'QC-2026-0142',
    '{customer_name}': 'Cliente Exemplo, Lda.',
    '{lab_name}': 'Laboratório Central',
    '{issue_date}': '04/05/2026',
    '{lab_code}': 'LAB-2026-0087',
    '{warehouse_name}': 'Recepção Técnica',
    '{sample_entry_code}': 'SMP-2026-ROT-00142',
    '{sample_code}': 'SMP-2026-ROT-00142',
    '{sample_name}': 'Farinha de trigo lote A',
    '{sample_type}': 'ROTINA',
    '{sample_product}': 'Farinha de trigo',
    '{sample_matrix}': 'Alimentos secos',
    '{sample_lot}': 'LT-2026-044',
    '{sample_origin}': 'Luanda',
    '{sampling_plan_ref}': 'PL-AM-2026-09',
    '{collection_date}': '03/05/2026',
    '{received_at}': '03/05/2026 09:40',
    '{sample_details}': '<table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px; text-align:left;">Produto<br><span class="bilingual-label">Product</span></th><td style="border:1px solid #cbd5e1; padding:6px;">Farinha de trigo</td></tr><tr><th style="border:1px solid #cbd5e1; padding:6px; text-align:left;">Matriz<br><span class="bilingual-label">Matrix</span></th><td style="border:1px solid #cbd5e1; padding:6px;">Alimentos secos</td></tr><tr><th style="border:1px solid #cbd5e1; padding:6px; text-align:left;">Lote<br><span class="bilingual-label">Lot</span></th><td style="border:1px solid #cbd5e1; padding:6px;">LT-2026-044</td></tr></table>',
    '{collection_details}': '<table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px; text-align:left;">Receção<br><span class="bilingual-label">Reception</span></th><td style="border:1px solid #cbd5e1; padding:6px;">03/05/2026 · embalagem íntegra · ambiente</td></tr><tr><th style="border:1px solid #cbd5e1; padding:6px; text-align:left;">Plano<br><span class="bilingual-label">Sampling plan</span></th><td style="border:1px solid #cbd5e1; padding:6px;">PL-AM-2026-09</td></tr></table>',
    '{analytical_scope}': '<table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px; text-align:left;">Perfis<br><span class="bilingual-label">Profiles</span></th><td style="border:1px solid #cbd5e1; padding:6px;">Físico-química · Segurança alimentar</td></tr></table>',
    '{validated_by}': 'Direcção Técnica',
    '{results_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Parâmetro</th><th style="border:1px solid #cbd5e1; padding:6px;">Resultado</th><th style="border:1px solid #cbd5e1; padding:6px;">Unidade</th><th style="border:1px solid #cbd5e1; padding:6px;">Incerteza</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">Humidade</td><td style="border:1px solid #cbd5e1; padding:6px;">12,4</td><td style="border:1px solid #cbd5e1; padding:6px;">%</td><td style="border:1px solid #cbd5e1; padding:6px;">±0,3</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Cinzas</td><td style="border:1px solid #cbd5e1; padding:6px;">2,1</td><td style="border:1px solid #cbd5e1; padding:6px;">%</td><td style="border:1px solid #cbd5e1; padding:6px;">±0,1</td></tr></tbody></table>',
    '{uncertainty_statement}': 'A incerteza expandida foi estimada com k = 2 e nível de confiança de aproximadamente 95%.',
    '{decision_rule}': 'A decisão de conformidade considera a regra de decisão definida contratualmente e o contexto ISO/IEC 17025 aplicável.',
    '{conclusion}': 'A amostra analisada apresenta conformidade com os critérios definidos para o ensaio solicitado.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Técnica</strong><br />Validação técnica do relatório</div>',
  },
  executive: {
    '{document_code}': 'EXEC-2026-05',
    '{issue_date}': '04/05/2026',
    '{lab_name}': 'GestLab Analytics',
    '{customer_name}': 'Comité Executivo',
  },
  export_certificate: {
    '{certificate_number}': 'EXP-2026-0041',
    '{lab_name}': 'Laboratório Central',
    '{exporter_name}': 'Exportadora Exemplo, Lda.',
    '{origin_country}': 'Angola',
    '{destination_country}': 'Namíbia',
    '{origin_city}': 'Luanda',
    '{destination_city}': 'Windhoek',
    '{transport_type}': 'Rodoviário',
    '{authorized_personnel}': 'Direcção Técnica',
    '{issue_date}': '04/05/2026',
    '{expedition_date}': '04/05/2026',
    '{expedition_location}': 'Porto de Luanda',
    '{products_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Produto</th><th style="border:1px solid #cbd5e1; padding:6px;">Quantidade</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">Milho</td><td style="border:1px solid #cbd5e1; padding:6px;">250 sacos</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Farinha</td><td style="border:1px solid #cbd5e1; padding:6px;">120 caixas</td></tr></tbody></table>',
    '{remarks}': 'Pré-visualização do certificado de exportação com composição editorial, dados logísticos e assinatura.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Técnica</strong><br />Validação do certificado de exportação</div>',
  },
  import_certificate: {
    '{certificate_number}': 'IMP-2026-0038',
    '{lab_name}': 'Laboratório Central',
    '{importer_name}': 'Importadora Exemplo, Lda.',
    '{exporter_name}': 'Fornecedor Internacional GmbH',
    '{destination_country}': 'Angola',
    '{port_entry}': 'Porto de Luanda',
    '{port_exit}': 'Porto de Hamburgo',
    '{transport_type}': 'Marítimo',
    '{authorized_personnel}': 'Direcção Técnica',
    '{issue_date}': '04/05/2026',
    '{items_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Produto</th><th style="border:1px solid #cbd5e1; padding:6px;">Lote</th><th style="border:1px solid #cbd5e1; padding:6px;">Validade</th><th style="border:1px solid #cbd5e1; padding:6px;">Quantidade</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">Aditivo alimentar</td><td style="border:1px solid #cbd5e1; padding:6px;">LT-8841</td><td style="border:1px solid #cbd5e1; padding:6px;">12/2027</td><td style="border:1px solid #cbd5e1; padding:6px;">48 caixas</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Reagente técnico</td><td style="border:1px solid #cbd5e1; padding:6px;">RG-2017</td><td style="border:1px solid #cbd5e1; padding:6px;">08/2028</td><td style="border:1px solid #cbd5e1; padding:6px;">20 bidões</td></tr></tbody></table>',
    '{remarks}': 'Pré-visualização do certificado de importação com lotes, validade e assinatura técnica.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Técnica</strong><br />Validação do certificado de importação</div>',
  },
  quote: {
    '{quote_number}': 'PP 05/2026/0048',
    '{document_number}': 'PP 05/2026/0048',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente Exemplo, Lda.',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{expiry_date}': '19/05/2026',
    '{items_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Serviço</th><th style="border:1px solid #cbd5e1; padding:6px;">Qtd.</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">Ensaios microbiológicos</td><td style="border:1px solid #cbd5e1; padding:6px;">1</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 52.500,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Metais pesados</td><td style="border:1px solid #cbd5e1; padding:6px;">1</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 45.000,00</td></tr></tbody></table>',
    '{summary_table}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="border:1px solid #cbd5e1; padding:6px;">Subtotal</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right;">AOA 97.500,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">IVA</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right;">AOA 13.650,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px; font-weight:700;">Total</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right; font-weight:700;">AOA 111.150,00</td></tr></table>',
    '{banking_details}': '<div style="line-height:1.7;">Banco: Banco de referência<br />Titular: Laboratório Central<br />IBAN: AO06 0000 0000 0000 0000 0000 0</div>',
    '{observations}': 'Pré-visualização de proforma com composição editorial, resumo financeiro e paginação premium.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Comercial</strong><br />Validação e emissão da proforma</div>',
  },
  invoice: {
    '{document_number}': 'FT 05/2026/0091',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente Exemplo, Lda.',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{due_date}': '03/06/2026',
    '{items_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Item</th><th style="border:1px solid #cbd5e1; padding:6px;">Qtd.</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">Ensaios microbiológicos</td><td style="border:1px solid #cbd5e1; padding:6px;">1</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 52.500,00</td></tr></tbody></table>',
    '{summary_table}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="border:1px solid #cbd5e1; padding:6px;">Subtotal</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right;">AOA 52.500,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">IVA</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right;">AOA 7.350,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px; font-weight:700;">Total</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right; font-weight:700;">AOA 59.850,00</td></tr></table>',
    '{banking_details}': '<div style="line-height:1.7;">Banco: Banco de referência<br />Titular: Laboratório Central<br />IBAN: AO06 0000 0000 0000 0000 0000 0</div>',
    '{observations}': 'Pré-visualização de factura com composição fiscal e paginação premium.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Financeira</strong><br />Emissão da factura</div>',
  },
  receipt: {
    '{document_number}': 'RG 05/2026/0042',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente Exemplo, Lda.',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{payment_type}': 'Transferência bancária',
    '{items_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Factura</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor pago</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">FT 05/2026/0091</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 59.850,00</td></tr></tbody></table>',
    '{summary_table}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="border:1px solid #cbd5e1; padding:6px; font-weight:700;">Total recebido</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right; font-weight:700;">AOA 59.850,00</td></tr></table>',
    '{banking_details}': '<div style="line-height:1.7;">Banco: Banco de referência<br />Titular: Laboratório Central<br />IBAN: AO06 0000 0000 0000 0000 0000 0</div>',
    '{observations}': 'Pré-visualização de recibo com rastreabilidade de recebimento e validação financeira.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Tesouraria</strong><br />Confirmação do recebimento</div>',
  },
  credit_note: {
    '{document_number}': 'NC 05/2026/0017',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente Exemplo, Lda.',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{reason_label}': 'Rectificação comercial',
    '{items_table}': '<table style="width:100%; border-collapse:collapse;"><thead><tr><th style="border:1px solid #cbd5e1; padding:6px;">Item</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px;">Rectificação de preço</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 7.500,00</td></tr></tbody></table>',
    '{summary_table}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="border:1px solid #cbd5e1; padding:6px; font-weight:700;">Total da nota</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right; font-weight:700;">AOA 7.500,00</td></tr></table>',
    '{banking_details}': '<div style="line-height:1.7;">Banco: Banco de referência<br />Titular: Laboratório Central<br />IBAN: AO06 0000 0000 0000 0000 0000 0</div>',
    '{observations}': 'Pré-visualização de nota de crédito com motivo, impacto financeiro e validação.',
    '{signature_block}': '<div style="margin-top:28px; border-top:1px solid #0f172a; padding-top:10px;"><strong>Direcção Financeira</strong><br />Emissão da nota de crédito</div>',
  },
  proposal: {
    '{proposal_number}': 'PROP-2026-001',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente Exemplo, Lda.',
    '{customer_details}': 'Cliente Exemplo, Lda.<br>NIF: 5000000000<br>Luanda',
    '{lab_details}': 'Laboratório Central<br>NIF: 5000000001<br>Luanda',
    '{service_location}': 'Luanda',
    '{issue_date}': '04/05/2026',
    '{expiry_date}': '19/05/2026',
    '{proposal_content}': '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7;"><strong>Escopo técnico e condições</strong><br>O cliente confirma o escopo, métodos, prazos, regra de decisão e condições comerciais antes da execução.</section>',
    '{parsed_content}': '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7;"><strong>Escopo técnico e condições</strong><br>O cliente confirma o escopo, métodos, prazos, regra de decisão e condições comerciais antes da execução.</section>',
    '{items_table}': '<table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px;">Serviço</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Ensaios laboratoriais</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 25.000,00</td></tr></table>',
    '{summary_table}': '<table style="width:100%; border-collapse:collapse;"><tr><td style="border:1px solid #cbd5e1; padding:6px;">Subtotal</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right;">AOA 25.000,00</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Total</td><td style="border:1px solid #cbd5e1; padding:6px; text-align:right; font-weight:700;">AOA 25.000,00</td></tr></table>',
    '{banking_details}': '<div style="line-height:1.7;">Banco: Banco de referência<br />Titular: Laboratório Central<br />IBAN: AO06 0000 0000 0000 0000 0000 0</div>',
    '{decision_rule}': 'Regra de decisão definida na proposta e aceite pelo cliente.',
    '{observations}': 'Pré-visualização de proposta com escopo, condições comerciais, dados bancários e aceite.',
    '{document_keywords}': '<div style="font-size:9px; color:#6b7b74;"><strong style="color:#143d37;">Palavras-chave / Keywords:</strong> proposta, laboratório, ISO 17025</div>',
    '{signature_block}': '<section style="margin-top:24px;"><table style="width:100%; border-collapse:collapse;"><tr><td style="width:48%; padding-top:26px; border-top:1px solid #143d37;"><strong>Direcção Técnica</strong><br>Validação técnica / comercial</td><td style="width:4%;"></td><td style="width:48%; padding-top:26px; border-top:1px solid #143d37;"><strong>Representante do Cliente</strong><br>Aceitação da proposta</td></tr></table></section>',
  },
}

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
  if (!window.confirm(`Arquivar o template "${template.name}"?`)) {
    return
  }

  form.delete(route('report-studios.destroy', template.id), {
    preserveScroll: true,
    onSuccess: () => {
      if (editingTemplate.value?.id === template.id) {
        resetForm()
      }
    },
  })
}

const formatDate = (value) => {
  if (!value) {
    return 'agora'
  }

  return new Date(value).toLocaleString('pt-PT')
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
              Estúdios de saída
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
              Documentos laboratoriais com canvas, camadas e PDF de produção.
            </h1>
            <p class="mt-4 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf] sm:text-base">
              Desenhe cabeçalhos, rodapés, fundos, assinaturas, carimbos, QR codes e blocos posicionáveis. Para composições com CSS moderno, use um renderizador Chromium para máxima fidelidade ao canvas.
            </p>
          </div>
          <div class="flex flex-wrap gap-3">
            <a
              v-if="previewPdfHref"
              :href="previewPdfHref"
              target="_blank"
              class="inline-flex items-center justify-center rounded-2xl border border-[#ded3bf] bg-white/75 px-4 py-3 text-sm font-bold text-[#15231f] shadow-sm transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-white dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:hover:border-[rgb(var(--primary-400-rgb)/0.55)]"
            >
              Pré-visualizar PDF
            </a>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-3 text-sm font-bold text-white shadow-[0_18px_45px_rgb(var(--primary-900-rgb)/0.18)] transition hover:bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--accent-300-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--accent-200-rgb))]"
              @click="resetForm"
            >
              Novo modelo
            </button>
          </div>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <article
            v-for="card in studioSummaryCards"
            :key="card.label"
            class="rounded-3xl border border-[#ded3bf]/70 bg-white/65 p-2 shadow-sm ring-1 ring-white/70 backdrop-blur dark:border-[#25443c] dark:bg-[#10231f]/70 dark:ring-white/10"
          >
            <div class="flex items-center justify-between gap-3 px-4 pb-3 pt-2">
              <h3 class="text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ card.label }}</h3>
              <span class="h-2 w-2 rounded-full bg-[rgb(var(--accent-300-rgb))] shadow-[0_0_20px_rgb(var(--accent-300-rgb)/0.55)]" />
            </div>
            <div class="rounded-2xl bg-[#fffdf7] p-4 text-[#15231f] shadow-sm dark:bg-[#07110f] dark:text-[#f7f1e7]">
              <div class="text-3xl font-black">{{ card.value }}</div>
              <div class="mt-1 text-xs font-semibold text-[#6b7b74] dark:text-[#83978d]">{{ card.hint }}</div>
            </div>
          </article>
        </div>
      </div>

      <div class="grid gap-px bg-[#ded3bf]/80 p-px dark:bg-[#25443c] sm:grid-cols-2 lg:grid-cols-5">
        <article
          v-for="card in documentTypeCards"
          :key="card.label"
          class="flex items-center justify-between gap-3 bg-[#fffaf0]/88 px-5 py-4 text-sm font-semibold text-[#475a53] dark:bg-[#10231f]/88 dark:text-[#cbd8cf]"
        >
          <span class="inline-flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full" :class="card.accent" />
            {{ card.label }}
          </span>
          <span class="font-black text-[#15231f] dark:text-[#f7f1e7]">{{ card.value || 0 }}</span>
        </article>
      </div>
    </section>

    <section class="rounded-[2rem] border border-[#ded3bf] bg-[#f4efe4]/80 p-2 shadow-[0_18px_55px_rgba(20,61,55,0.06)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f]/70 dark:ring-white/10">
      <div class="flex flex-col gap-3 px-4 pb-4 pt-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-lg font-bold text-[#15231f] dark:text-[#f7f1e7]">Modelos guardados</h2>
          <p class="mt-1 text-sm font-medium text-[#475a53] dark:text-[#cbd8cf]">Os modelos abaixo já controlam a saída PDF em produção e podem ser refinados directamente no canvas.</p>
        </div>
        <div class="rounded-full border border-[#ded3bf] bg-[#fffdf7] px-3 py-1.5 text-xs font-bold text-[#475a53] shadow-sm dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#cbd8cf]">
          {{ templates.length }} templates na biblioteca
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
                <span v-if="template.is_default" class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">padrão</span>
              </div>
              <h3 class="text-lg font-bold text-[#15231f] transition group-hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#f7f1e7] dark:group-hover:text-[rgb(var(--primary-200-rgb))]">{{ template.name }}</h3>
              <p class="text-sm leading-6 text-[#475a53] dark:text-[#cbd8cf]">{{ template.description || 'Sem descrição registada.' }}</p>
              <div class="text-xs font-medium text-[#84958d] dark:text-[#83978d]">
                Actualizado em {{ formatDate(template.updated_at) }}{{ template.updated_by ? ` · ${template.updated_by}` : '' }}
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <a
                :href="template.preview_pdf_path"
                target="_blank"
                class="rounded-xl border border-[rgb(var(--primary-200-rgb))] bg-[rgb(var(--primary-50-rgb))] px-3 py-2 text-sm font-bold text-[rgb(var(--primary-800-rgb))] hover:bg-[rgb(var(--primary-100-rgb))] dark:border-[rgb(var(--primary-400-rgb)/0.25)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.20)]"
              >
                Pré-visualizar PDF
              </a>
              <button type="button" class="rounded-xl border border-[#ded3bf] bg-[#fffdf7] px-3 py-2 text-sm font-bold text-[#475a53] hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[#f8f4ea] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#cbd8cf] dark:hover:bg-[#10231f]" @click="editTemplate(template)">
                Editar
              </button>
              <button type="button" class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-500/30 dark:bg-slate-950 dark:text-rose-300 dark:hover:bg-rose-500/10" @click="destroyTemplate(template)">
                Arquivar
              </button>
            </div>
          </div>
        </article>
      </div>
      <div v-else class="rounded-[1.45rem] border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
        Ainda não existem modelos de estúdio configurados.
      </div>
    </section>

    <report-studio-workbench
      title="Studio de relatórios"
      intro="Monte relatórios analíticos, executivos ou especiais com canvas editorial, blocos posicionáveis, headers e footers por página e preview PDF alinhado com o builder final."
      :form="form"
      :layout-schema="form.layout_schema"
      :export-settings="form.export_settings"
      :placeholders="Object.keys(previewReplacements)"
      :presets="baseModelPresets"
      :preview-replacements="previewReplacements"
      :preview-pdf-href="previewPdfHref"
      :asset-library="props.studioAssets"
      :renderer-capabilities="props.rendererCapabilities"
      :initial-draft-label="editingTemplate?.name ? `A editar: ${editingTemplate.name}` : ''"
      :back-href="route('report-studios.index')"
      back-label="Voltar ao topo"
      submit-label="Guardar estúdio"
      @submit="submit"
      @update:studio-type="onStudioTypeUpdate"
    />
  </div>
</template>
