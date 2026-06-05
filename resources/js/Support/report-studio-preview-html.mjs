const reportTable = (columns, rows, emptyMessage = 'Sem dados registados.') => {
  const headerHtml = columns
    .map(({ label, translation = '', align = 'left' }) => {
      const translationHtml = translation
        ? `<br><span class="bilingual-label">${translation}</span>`
        : ''

      return `<th style="text-align:${align};">${label}${translationHtml}</th>`
    })
    .join('')

  const bodyHtml = rows.length
    ? rows
        .map((row) => {
          const cells = columns
            .map(({ align = 'left' }, index) => `<td style="text-align:${align};">${row[index] ?? ''}</td>`)
            .join('')

          return `<tr>${cells}</tr>`
        })
        .join('')
    : `<tr><td colspan="${Math.max(columns.length, 1)}" class="muted">${emptyMessage}</td></tr>`

  return `<table class="report-table studio-avoid-break" style="width:100%; border-collapse:collapse;"><thead><tr>${headerHtml}</tr></thead><tbody>${bodyHtml}</tbody></table>`
}

const summaryCards = (cards) => {
  const rows = []

  for (let index = 0; index < cards.length; index += 2) {
    rows.push(cards.slice(index, index + 2))
  }

  const rowHtml = rows
    .map((row) => {
      const cells = row
        .map(({ label, value, hint = '' }) => `<td class="document-summary-cell" style="width:50%;"><span class="label">${label}</span><span class="value">${value}</span>${hint ? `<span class="muted">${hint}</span>` : ''}</td>`)
        .join('')

      return `<tr>${cells}${row.length === 1 ? '<td style="width:50%;"></td>' : ''}</tr>`
    })
    .join('')

  return `<table class="document-summary-table studio-avoid-break">${rowHtml}</table>`
}

const financialSummary = (rows) => {
  const bodyHtml = rows
    .map(({ label, value, emphasis = false }) => {
      const emphasisStyle = emphasis ? 'font-weight:800; color:#143d37;' : ''
      const valueStyle = emphasis ? 'font-weight:800; color:#143d37;' : 'font-weight:700;'

      return `<tr><td style="${emphasisStyle}">${label}</td><td style="text-align:right; ${valueStyle}">${value}</td></tr>`
    })
    .join('')

  return `<table class="report-table document-financial-summary studio-avoid-break" style="width:100%; border-collapse:collapse;"><tbody>${bodyHtml}</tbody></table>`
}

const bankingDetails = '<div style="line-height:1.7;">Banco: Banco principal do laboratório<br />Titular: Laboratório Central<br />IBAN: AO06 0000 0000 0000 0000 0000 0</div>'
const bankPlaceholders = {
  '{bank_name}': 'Banco principal do laboratório',
  '{bank_account_name}': 'Laboratório Central',
  '{bank_account_number}': '0011223344',
  '{bank_iban}': 'AO06 0000 0000 0000 0000 0000 0',
  '{bank_swift}': 'BFAAAOLU',
  '{bank_details}': 'Pagamento por transferência bancária com referência do documento.',
}

const signatureBlock = (leftTitle = 'Direcção técnica', rightTitle = 'Representante autorizado') => `<section style="margin-top:24px;"><table class="document-summary-table studio-avoid-break"><tr><td class="document-summary-cell" style="width:48%; padding-top:26px;"><strong>${leftTitle}</strong><br><span class="muted">Validação técnica e documental</span></td><td style="width:4%;"></td><td class="document-summary-cell" style="width:48%; padding-top:26px;"><strong>${rightTitle}</strong><br><span class="muted">Assinatura, data e evidência de aceite</span></td></tr></table></section>`

const commercialItemsTable = reportTable(
  [
    { label: 'Serviço', translation: 'Service' },
    { label: 'Qtd.', translation: 'Qty', align: 'center' },
    { label: 'Valor', translation: 'Amount', align: 'right' },
  ],
  [
    ['Ensaios microbiológicos', '1', 'AOA 52.500,00'],
    ['Metais pesados', '1', 'AOA 45.000,00'],
  ]
)

const commercialSummaryTable = financialSummary([
  { label: 'Subtotal', value: 'AOA 97.500,00' },
  { label: 'IVA', value: 'AOA 13.650,00' },
  { label: 'Total', value: 'AOA 111.150,00', emphasis: true },
])

export const previewReplacementsByType = {
  analysis: {
    '{report_title}': 'Relatório Analítico de Ensaios',
    '{certificate_code}': 'BA-2026-0142',
    '{document_code}': 'BA-2026-0142',
    '{customer_name}': 'Cliente laboratorial de referência',
    '{lab_name}': 'Laboratório Central',
    '{issue_date}': '04/05/2026',
    '{lab_code}': 'LAB-2026-0087',
    '{warehouse_name}': 'Recepção Técnica',
    '{sample_entry_code}': 'SE-2026-0142',
    '{sample_code}': 'SE-2026-0142',
    '{sample_name}': 'Farinha de trigo lote A',
    '{sample_type}': 'Rotina',
    '{sample_product}': 'Farinha de trigo',
    '{sample_matrix}': 'Cereais e derivados',
    '{sample_lot}': 'LT-2026-044',
    '{sample_origin}': 'Luanda',
    '{sampling_plan_ref}': 'PL-AM-2026-09',
    '{collection_date}': '03/05/2026',
    '{received_at}': '03/05/2026 09:40',
    '{sample_details}': summaryCards([
      { label: 'Produto / Product', value: 'Farinha de trigo', hint: 'Produto recebido com rastreio de lote e origem.' },
      { label: 'Matriz / Matrix', value: 'Cereais e derivados', hint: 'Matriz associada ao âmbito analítico.' },
      { label: 'Lote / Lot', value: 'LT-2026-044', hint: 'Código usado para rastreabilidade e emissão.' },
      { label: 'Sample Entry', value: 'SE-2026-0142', hint: 'Entrada vinculada ao fluxo de análise.' },
    ]),
    '{collection_details}': summaryCards([
      { label: 'Receção / Reception', value: '03/05/2026 · embalagem íntegra', hint: 'Triagem registada na cadeia de custódia.' },
      { label: 'Plano / Sampling plan', value: 'PL-AM-2026-09', hint: 'Plano usado para recolha e validação.' },
    ]),
    '{analytical_scope}': summaryCards([
      { label: 'Perfis / Profiles', value: 'Físico-química · Segurança alimentar', hint: 'Âmbito técnico aprovado para a amostra.' },
      { label: 'Decisão / Decision rule', value: 'Critério contratual ISO/IEC 17025', hint: 'Regra aplicada na interpretação.' },
    ]),
    '{validated_by}': 'Direcção Técnica',
    '{results_table}': reportTable(
      [
        { label: 'Parâmetro', translation: 'Parameter' },
        { label: 'Método', translation: 'Method' },
        { label: 'Resultado', translation: 'Result' },
        { label: 'Unidade', translation: 'Unit' },
        { label: 'Incerteza', translation: 'Uncertainty' },
        { label: 'Estado', translation: 'Status' },
      ],
      [
        ['Humidade', 'ISO 712', '12,4', '%', '±0,3', 'Aprovado'],
        ['Cinzas', 'ISO 2171', '2,1', '%', '±0,1', 'Verificado'],
      ]
    ),
    '{analysis_chart_title}': 'Estado dos resultados analíticos',
    '{analysis_chart_labels}': 'Aprovados, Verificados, Inseridos, Pendentes, Contra-análise',
    '{analysis_chart_values}': '2, 1, 1, 0, 1',
    '{analysis_chart_caption}': 'Estados típicos de inserção, verificação, aprovação e contra-análise.',
    '{analysis_chart_card}': '<section class="studio-avoid-break" style="border:1px solid #ded3bf; border-radius:18px; background:#fffaf0; padding:14px 16px;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Estado dos resultados analíticos</div><div style="display:flex; gap:10px; align-items:flex-end; min-height:86px; margin-top:12px;"><div style="height:72px; width:18%; background:#143d37; border-radius:10px 10px 4px 4px;"></div><div style="height:44px; width:18%; background:#3f6f58; border-radius:10px 10px 4px 4px;"></div><div style="height:44px; width:18%; background:#d9b05f; border-radius:10px 10px 4px 4px;"></div><div style="height:12px; width:18%; background:#94a3b8; border-radius:10px 10px 4px 4px;"></div><div style="height:44px; width:18%; background:#9f1d1d; border-radius:10px 10px 4px 4px;"></div></div><p style="margin:10px 0 0; color:#475a53;">Estados típicos de inserção, verificação, aprovação e contra-análise.</p></section>',
    '{uncertainty_statement}': 'A incerteza expandida foi estimada com k = 2 e nível de confiança de aproximadamente 95%.',
    '{decision_rule}': 'A decisão de conformidade considera a regra de decisão definida contratualmente e o contexto ISO/IEC 17025 aplicável.',
    '{conclusion}': 'A amostra analisada apresenta conformidade com os critérios definidos para o ensaio solicitado.',
    '{signature_block}': signatureBlock('Direcção Técnica', 'Responsável pela emissão'),
  },
  executive: {
    '{document_code}': 'EXEC-2026-05',
    '{issue_date}': '04/05/2026',
    '{lab_name}': 'GestLab Analytics',
    '{customer_name}': 'Comité Executivo',
    '{period_label}': 'Maio de 2026',
    '{executive_summary}': 'Leitura executiva de capacidade, prazos, risco técnico e pressão operacional para decisão da direcção.',
    '{executive_kpis}': summaryCards([
      { label: 'Amostras activas', value: '142', hint: 'Processos em curso no período.' },
      { label: 'No prazo', value: '91%', hint: 'Cumprimento operacional dos prazos.' },
      { label: 'Risco', value: '8', hint: 'Ocorrências que exigem atenção.' },
    ]),
    '{executive_charts}': '<section class="studio-avoid-break" style="border:1px solid #ded3bf; border-radius:18px; padding:16px; background:#fffaf0;"><strong>Pressão operacional</strong><div style="margin-top:12px; display:flex; gap:8px; align-items:flex-end; height:92px;"><span style="height:80px; flex:1; background:#143d37; border-radius:10px 10px 0 0;"></span><span style="height:62px; flex:1; background:#3f6f58; border-radius:10px 10px 0 0;"></span><span style="height:52px; flex:1; background:#d9b05f; border-radius:10px 10px 0 0;"></span><span style="height:38px; flex:1; background:#475569; border-radius:10px 10px 0 0;"></span></div></section>',
    '{executive_chart_title}': 'Capacidade técnica por etapa',
    '{executive_chart_labels}': 'Recepção, Preparação, Ensaio, Verificação, Emissão',
    '{executive_chart_values}': '24, 21, 18, 16, 12',
    '{executive_chart_caption}': 'Dados provenientes da série executiva do período seleccionado.',
    '{top_customers_table}': reportTable(
      [
        { label: 'Cliente', translation: 'Customer' },
        { label: 'Amostras', translation: 'Samples', align: 'center' },
        { label: 'Estado', translation: 'Status' },
      ],
      [
        ['Cliente industrial de referência', '42', 'No prazo'],
        ['Cliente exportador alimentar', '31', 'Atenção'],
      ]
    ),
  },
  export_certificate: {
    '{certificate_number}': 'EXP-2026-0041',
    '{lab_name}': 'Laboratório Central',
    '{exporter_name}': 'Exportador alimentar certificado',
    '{origin_country}': 'Angola',
    '{destination_country}': 'Namíbia',
    '{origin_city}': 'Luanda',
    '{destination_city}': 'Windhoek',
    '{transport_type}': 'Rodoviário',
    '{authorized_personnel}': 'Direcção Técnica',
    '{issue_date}': '04/05/2026',
    '{expedition_date}': '04/05/2026',
    '{expedition_location}': 'Porto de Luanda',
    '{products_table}': reportTable(
      [
        { label: 'Produto', translation: 'Product' },
        { label: 'Quantidade', translation: 'Quantity', align: 'right' },
      ],
      [
        ['Milho', '250 sacos'],
        ['Farinha', '120 caixas'],
      ]
    ),
    '{remarks}': 'Certificado preparado com dados logísticos, produto, origem, destino e assinatura técnica.',
    '{signature_block}': signatureBlock('Direcção Técnica', 'Entidade requerente'),
  },
  import_certificate: {
    '{certificate_number}': 'IMP-2026-0038',
    '{lab_name}': 'Laboratório Central',
    '{importer_name}': 'Importador alimentar certificado',
    '{exporter_name}': 'Fornecedor internacional homologado',
    '{destination_country}': 'Angola',
    '{port_entry}': 'Porto de Luanda',
    '{port_exit}': 'Porto de Hamburgo',
    '{transport_type}': 'Marítimo',
    '{authorized_personnel}': 'Direcção Técnica',
    '{issue_date}': '04/05/2026',
    '{items_table}': reportTable(
      [
        { label: 'Produto', translation: 'Product' },
        { label: 'Lote', translation: 'Lot' },
        { label: 'Validade', translation: 'Expiry' },
        { label: 'Quantidade', translation: 'Quantity', align: 'right' },
      ],
      [
        ['Aditivo alimentar', 'LT-8841', '12/2027', '48 caixas'],
        ['Reagente técnico', 'RG-2017', '08/2028', '20 bidões'],
      ]
    ),
    '{remarks}': 'Certificado preparado com lotes, validade, logística e assinatura técnica.',
    '{signature_block}': signatureBlock('Direcção Técnica', 'Entidade requerente'),
  },
  quote: {
    '{quote_number}': 'PP 05/2026/0048',
    '{document_number}': 'PP 05/2026/0048',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente industrial de referência',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{expiry_date}': '19/05/2026',
    '{items_table}': commercialItemsTable,
    '{summary_table}': commercialSummaryTable,
    '{banking_details}': bankingDetails,
    ...bankPlaceholders,
    '{observations}': 'Proforma preparada com composição editorial, resumo financeiro e paginação controlada.',
    '{signature_block}': signatureBlock('Direcção Comercial', 'Cliente'),
  },
  invoice: {
    '{document_number}': 'FT 05/2026/0091',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente industrial de referência',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{due_date}': '03/06/2026',
    '{items_table}': commercialItemsTable,
    '{summary_table}': financialSummary([
      { label: 'Subtotal', value: 'AOA 52.500,00' },
      { label: 'IVA', value: 'AOA 7.350,00' },
      { label: 'Total', value: 'AOA 59.850,00', emphasis: true },
    ]),
    '{banking_details}': bankingDetails,
    ...bankPlaceholders,
    '{observations}': 'Factura preparada com composição fiscal, resumo financeiro e paginação controlada.',
    '{signature_block}': signatureBlock('Direcção Financeira', 'Cliente'),
  },
  receipt: {
    '{document_number}': 'RG 05/2026/0042',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente industrial de referência',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{payment_type}': 'Transferência bancária',
    '{items_table}': reportTable(
      [
        { label: 'Factura', translation: 'Invoice' },
        { label: 'Valor pago', translation: 'Paid amount', align: 'right' },
      ],
      [['FT 05/2026/0091', 'AOA 59.850,00']]
    ),
    '{summary_table}': financialSummary([{ label: 'Total recebido', value: 'AOA 59.850,00', emphasis: true }]),
    '{banking_details}': bankingDetails,
    ...bankPlaceholders,
    '{observations}': 'Recibo preparado com rastreabilidade de recebimento e validação financeira.',
    '{signature_block}': signatureBlock('Tesouraria', 'Cliente'),
  },
  credit_note: {
    '{document_number}': 'NC 05/2026/0017',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente industrial de referência',
    '{service_location}': 'Luanda · Unidade Industrial',
    '{issue_date}': '04/05/2026',
    '{reason_label}': 'Rectificação comercial',
    '{items_table}': reportTable(
      [
        { label: 'Item', translation: 'Item' },
        { label: 'Valor', translation: 'Amount', align: 'right' },
      ],
      [['Rectificação de preço', 'AOA 7.500,00']]
    ),
    '{summary_table}': financialSummary([{ label: 'Total da nota', value: 'AOA 7.500,00', emphasis: true }]),
    '{banking_details}': bankingDetails,
    ...bankPlaceholders,
    '{observations}': 'Nota de crédito preparada com motivo, impacto financeiro e validação.',
    '{signature_block}': signatureBlock('Direcção Financeira', 'Cliente'),
  },
  proposal: {
    '{proposal_number}': 'PROP-2026-001',
    '{lab_name}': 'Laboratório Central',
    '{customer_name}': 'Cliente industrial de referência',
    '{customer_details}': 'Cliente industrial de referência<br>NIF: 5000000000<br>Luanda',
    '{lab_details}': 'Laboratório Central<br>NIF: 5000000001<br>Luanda',
    '{service_location}': 'Luanda',
    '{issue_date}': '04/05/2026',
    '{expiry_date}': '19/05/2026',
    '{proposal_content}': '<section class="document-callout studio-avoid-break"><strong>Âmbito técnico e condições</strong><br>O cliente confirma âmbito, métodos, prazos, regra de decisão e condições comerciais antes da execução.</section>',
    '{parsed_content}': '<section class="document-callout studio-avoid-break"><strong>Âmbito técnico e condições</strong><br>O cliente confirma âmbito, métodos, prazos, regra de decisão e condições comerciais antes da execução.</section>',
    '{items_table}': reportTable(
      [
        { label: 'Serviço', translation: 'Service' },
        { label: 'Valor', translation: 'Amount', align: 'right' },
      ],
      [['Ensaios laboratoriais', 'AOA 25.000,00']]
    ),
    '{summary_table}': financialSummary([
      { label: 'Subtotal', value: 'AOA 25.000,00' },
      { label: 'Total', value: 'AOA 25.000,00', emphasis: true },
    ]),
    '{banking_details}': bankingDetails,
    ...bankPlaceholders,
    '{decision_rule}': 'Regra de decisão definida na proposta e aceite pelo cliente.',
    '{observations}': 'Proposta preparada com âmbito, condições comerciais, dados bancários e aceite.',
    '{document_keywords}': '<div style="font-size:9px; color:#6b7b74;"><strong style="color:#143d37;">Palavras-chave / Keywords:</strong> proposta, laboratório, ISO 17025</div>',
    '{verification_url}': 'https://lims-unleashed.test/vap-proposals/proposal/preview-prop-2026-001',
    '{proposal_authenticity}': '<section class="document-callout studio-avoid-break"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Verificação da proposta</div><p style="margin:8px 0 0; color:#475a53;">Documento verificável por QR e ligação pública segura.</p><p style="margin:8px 0 0; font-size:9px; color:#6b7b74;">https://lims-unleashed.test/vap-proposals/proposal/preview-prop-2026-001</p></section>',
    '{proposal_acceptance_evidence}': '<section class="document-callout studio-avoid-break"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Evidência de aceite</div><div style="margin-top:8px; font-weight:800; color:#9a7a2f;">Aceite pendente</div><p style="margin:6px 0 0; color:#475a53;">A proposta aguarda validação do cliente no portal.</p></section>',
    '{signature_block}': signatureBlock('Direcção Técnica', 'Representante do cliente'),
  },
}
