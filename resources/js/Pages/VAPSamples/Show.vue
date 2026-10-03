<script setup>
import { computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { ArrowLeftIcon, ArrowRightIcon, BeakerIcon, DocumentArrowDownIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
import Layout from '@/Shared/Layouts/Layout.vue'
import { usePermission } from '@/Composables/usePermissions'
import { approvedResultCounts, finalDecisionLabels, sampleDate, sampleStatusLabels, sampleStatusTones, sampleText, sampleTypeLabels } from '@/Utils/samplePresentation'

defineOptions({ layout: Layout })
const props = defineProps({
  sample: { type: Object, required: true },
  analyses: { type: Array, default: () => [] },
  linkedSampleIds: { type: Array, default: () => [] },
  workflowSummary: { type: Object, default: () => ({}) },
})
const { hasPermission } = usePermission()
const canEdit = computed(() => hasPermission('edit_samples'))
const releaseGate = computed(() => props.workflowSummary.quality_control_release || {})
const latestDecision = computed(() => releaseGate.value.current_decision)
const resultCounts = computed(() => approvedResultCounts(props.analyses))
const isInternalQcSample = computed(() => props.sample.client_submitted_info?.request_origin === 'internal'
  && ['MATERIA_PRIMA', 'RAW_MATERIAL'].includes(props.sample.sample_type))
const qcDecisionForm = useForm({ decision: '', notes: '' })
const releaseDecisionBlocked = computed(() => qcDecisionForm.decision === 'released' && !releaseGate.value.can_release)
const gateTone = computed(() => ({ ready_for_release: 'complete', requires_review: 'hold', awaiting_approval: 'analysis' }[releaseGate.value.status] || 'review'))
const resultStages = { pending_results: 'Aguardar resultados', insertion: 'Inserção pendente', verification: 'Verificação pendente', approval: 'Aprovação pendente', approved: 'Resultados aprovados' }
const receptionFields = computed(() => [
  ['Cliente', props.sample.customer?.name], ['Laboratório', props.sample.lab?.name],
  ['Departamento', props.sample.department?.name], ['Local / armazém', props.sample.warehouse?.name || props.sample.warehouse?.address],
  ['Recebida em', sampleDate(props.sample.received_at, true)], ['Recebida por', props.sample.received_by?.name],
  ['Embalagem', props.sample.packaging?.name], ['Tipo de amostra', sampleTypeLabels[props.sample.sample_type] || props.sample.sample_type],
])
const contextFields = computed(() => {
  const info = props.sample.client_submitted_info || {}
  return [
    ['Origem', info.request_origin === 'internal' ? 'Controlo interno' : 'Cliente'],
    ['Pedido do portal', props.sample.portal_request?.reference], ['Proposta', props.sample.proposal?.proposal_no],
    ['Produto', info.product_name || props.sample.collection_product?.product], ['Lote', info.lot], ['Batch', info.batch],
    ['Fornecedor', info.supplier_name], ['Quantidade', info.quantity], ['Temperatura', info.temperature_value],
    ['Local de colheita', info.collection_location || info.location], ['Plano de amostragem', info.sampling_plan_ref],
    ['Condição de recepção', { accepted: 'Aceite', restricted: 'Aceite com restrições', rejected: 'Rejeitada' }[info.conditioning_status]],
    ...(isInternalQcSample.value ? [
      ['Plano de controlo', info.quality_control_path?.name],
      ['Disciplina', { microbiology: 'Microbiologia', chemistry: 'Química / físico-química', microbiology_and_chemistry: 'Microbiologia + química' }[info.analysis_discipline]],
      ['Objectivo do controlo', { raw_material_release: 'Libertação de matéria-prima', supplier_qualification: 'Qualificação de fornecedor', process_validation: 'Validação de processo', stability_follow_up: 'Acompanhamento de estabilidade', investigation: 'Investigação interna', other: 'Outro' }[info.quality_control_purpose]],
      ['Orientação inicial', { hold_until_release: 'Reter até libertação', release_if_compliant: 'Libertar se conforme', investigate_before_release: 'Investigar antes de libertar', trend_only: 'Apenas tendência' }[info.qc_decision]],
    ] : []),
  ]
})
const retentionFields = computed(() => [
  ['Estado', { in_custody: 'Em custódia', active: 'Activa', due_soon: 'Próxima do descarte', overdue: 'Vencida', discarded: 'Descartada' }[props.sample.retention_status] || props.sample.retention_status],
  ['Período', props.sample.retention_period_days == null ? null : `${props.sample.retention_period_days} dias`],
  ['Retenção até', sampleDate(props.sample.retention_due_at)], ['Descarte previsto', sampleDate(props.sample.discard_scheduled_at)],
  ['Início da análise', sampleDate(props.sample.analysis_start_date, true)], ['Conclusão da análise', sampleDate(props.sample.analysis_end_date, true)],
])
watch(() => props.sample.id, () => qcDecisionForm.resetAndClearErrors())

function submitQcDecision() {
  if (!canEdit.value || !qcDecisionForm.decision || qcDecisionForm.processing || releaseDecisionBlocked.value) return
  qcDecisionForm.patch(route('vap_samples.samples.internal-quality-control-decision', { sampleEntry: props.sample.id }), {
    preserveScroll: true,
    onSuccess: () => qcDecisionForm.reset(),
  })
}
</script>

<template>
  <div class="workbench-page lab-sample-detail">
    <Head :title="sample.code || sample.name" />
    <Link :href="route('vap_samples.queue')" class="lab-link lab-detail-back"><ArrowLeftIcon />Todas as amostras</Link>
    <header class="lab-page-head lab-record-top">
      <div class="lab-detail-title"><p class="lab-kicker">Entrada de amostra</p><h1>{{ sample.code || 'Código por atribuir' }}</h1><p>{{ sample.name }}</p></div>
      <a :href="route('vap_samples.samples.pdf', sample.id)" class="lab-btn" target="_blank" rel="noopener"><DocumentArrowDownIcon />PDF da entrada<span class="sr-only"> (abre noutra janela)</span></a>
    </header>
    <div class="lab-record-meta lab-detail-meta"><span class="lab-pill" :data-tone="sampleStatusTones[sample.status]"><span class="lab-dot"></span>{{ sampleStatusLabels[sample.status] || sample.status }}</span><span>{{ sample.lab?.name || 'Laboratório por associar' }}</span><span>Recepção · {{ sampleDate(sample.received_at) }}</span></div>
    <section class="lab-metrics" aria-label="Resumo da amostra">
      <div class="lab-metric"><p class="lab-metric-title"><BeakerIcon />Análises ligadas</p><strong class="lab-metric-value lab-number">{{ analyses.length }}</strong><span class="lab-metric-note">{{ linkedSampleIds.length }} amostras internas</span></div>
      <div class="lab-metric"><p class="lab-metric-title"><ShieldCheckIcon />Resultados aprovados</p><strong class="lab-metric-value lab-number">{{ resultCounts.approved }}<span class="lab-detail-total"> / {{ resultCounts.total }}</span></strong><span class="lab-metric-note">{{ resultCounts.total ? 'Aprovação técnica dos resultados' : 'Sem resultados registados' }}</span></div>
      <div class="lab-metric"><p class="lab-metric-title">Código laboratorial</p><strong class="lab-detail-code">{{ workflowSummary.linked_lab_code || sample.collection_product?.code || 'Por atribuir' }}</strong><span class="lab-metric-note">{{ sample.quality_certificate ? 'Certificado disponível' : 'Certificado ainda não emitido' }}</span></div>
    </section>
    <div class="lab-record-grid">
      <div class="lab-detail-main">
        <section v-if="workflowSummary.next_action" class="lab-panel lab-detail-next" aria-labelledby="next-action-title">
          <div><p class="lab-kicker">Próximo passo</p><h2 id="next-action-title">{{ workflowSummary.next_action.label }}</h2><p class="lab-muted">{{ workflowSummary.next_action.description }}</p></div>
          <Link v-if="workflowSummary.next_action.url" :href="workflowSummary.next_action.url" class="lab-btn lab-primary">Continuar<ArrowRightIcon /></Link>
        </section>
        <TabGroup>
          <TabList class="lab-tabs" aria-label="Detalhes da amostra">
            <Tab v-slot="{ selected }" as="template"><button class="lab-tab" :aria-pressed="selected">Análises<span>{{ analyses.length }}</span></button></Tab>
            <Tab v-slot="{ selected }" as="template"><button class="lab-tab" :aria-pressed="selected">Recepção</button></Tab>
            <Tab v-slot="{ selected }" as="template"><button class="lab-tab" :aria-pressed="selected">Rastreabilidade</button></Tab>
          </TabList>
          <TabPanels>
            <TabPanel>
              <div v-if="analyses.length" class="lab-table-wrap" tabindex="0" aria-label="Análises da amostra">
                <table class="lab-table"><thead><tr><th scope="col">Análise / perfil</th><th scope="col">Departamento</th><th scope="col">Resultados</th><th scope="col">Contra-análise</th></tr></thead><tbody>
                  <tr v-for="analysis in analyses" :key="analysis.id">
                    <td><Link :href="analysis.analysis_url" class="lab-link">#{{ analysis.id }}<ArrowRightIcon /></Link><small>{{ analysis.profile || 'Perfil por associar' }}</small></td>
                    <td>{{ analysis.department || 'Não associado' }}</td>
                    <td>{{ resultStages[analysis.workflow_stage] || 'Estado por definir' }}<small v-if="analysis.results_summary?.total">{{ analysis.results_summary.approved }}/{{ analysis.results_summary.total }} aprovados · {{ analysis.results_summary.with_uncertainty }} com incerteza</small></td>
                    <td><div v-for="item in analysis.counter_analysis_items || []" :key="item.result_id" class="lab-detail-counter"><Link v-if="item.counter_analysis_url" :href="item.counter_analysis_url" class="lab-link">#{{ item.counter_analysis_id }}</Link><span v-else class="lab-pill" data-tone="review">Solicitada</span><small>{{ item.parameter || `Resultado #${item.result_id}` }}</small></div><span v-if="!analysis.counter_analysis_items?.length" class="lab-muted">Não solicitada</span></td>
                  </tr>
                </tbody></table>
              </div>
              <div v-else class="lab-empty"><BeakerIcon class="lab-empty-icon" /><strong>A análise começa aqui.</strong>Esta entrada ainda não tem análises ligadas. Consulte a recepção e o próximo passo.</div>
            </TabPanel>
            <TabPanel class="lab-detail-stack">
              <section class="lab-panel"><h2>Recepção e enquadramento</h2><dl class="lab-detail-list lab-detail-fields"><div v-for="[label, value] in receptionFields" :key="label"><dt>{{ label }}</dt><dd>{{ sampleText(value) }}</dd></div></dl><div class="lab-detail-notes"><h3>Serviços solicitados</h3><p>{{ sampleText(sample.requested_services, 'Sem serviços registados.') }}</p><h3>Observações</h3><p>{{ sample.obs || 'Sem observações de recepção.' }}</p></div></section>
              <section class="lab-panel"><h2>Origem e condições de colheita</h2><dl class="lab-detail-list lab-detail-fields"><div v-for="[label, value] in contextFields" :key="label"><dt>{{ label }}</dt><dd>{{ sampleText(value) }}</dd></div></dl><div v-if="sample.client_submitted_info?.chain_of_custody_notes" class="lab-detail-notes"><h3>Cadeia de custódia</h3><p>{{ sample.client_submitted_info.chain_of_custody_notes }}</p></div></section>
            </TabPanel>
            <TabPanel class="lab-detail-stack">
              <section class="lab-panel"><h2>Retenção e rastreabilidade</h2><dl class="lab-detail-list lab-detail-fields"><div v-for="[label, value] in retentionFields" :key="label"><dt>{{ label }}</dt><dd>{{ sampleText(value) }}</dd></div></dl></section>
              <section class="lab-panel"><h2>Histórico de descarte</h2><div v-if="sample.discards?.length" class="lab-detail-history"><article v-for="discard in sample.discards" :key="discard.id" class="lab-event"><strong>{{ discard.discard_method }}</strong><p>{{ sampleDate(discard.discarded_at, true) }}</p><p>{{ discard.qty }} · {{ discard.discarded_by || 'Operador por registar' }}</p><a :href="route('vap_samples.discards.pdf', discard.id)" class="lab-link" target="_blank" rel="noopener">Certificado de descarte<DocumentArrowDownIcon /><span class="sr-only"> (abre noutra janela)</span></a></article></div><p v-else class="lab-muted lab-detail-copy">Nenhum descarte registado. A custódia continua acompanhada neste laboratório.</p></section>
            </TabPanel>
          </TabPanels>
        </TabGroup>
        <section v-if="isInternalQcSample" class="lab-panel lab-detail-qc" aria-labelledby="qc-title">
          <div class="lab-section-head"><div><p class="lab-kicker">Controlo interno de matéria-prima</p><h2 id="qc-title">Decisão de qualidade</h2></div><span class="lab-pill" :data-tone="gateTone">{{ releaseGate.label || 'Aguardar avaliação' }}</span></div>
          <p class="lab-muted">{{ releaseGate.message || 'Valide os resultados antes de registar a decisão operacional.' }}</p>
          <dl class="lab-detail-list lab-detail-fields"><div><dt>Resultados aprovados</dt><dd>{{ releaseGate.totals?.approved || 0 }} / {{ releaseGate.totals?.results || 0 }}</dd></div><div><dt>Resultados com incerteza</dt><dd>{{ releaseGate.totals?.with_uncertainty || 0 }}</dd></div><div><dt>Contra-análises solicitadas</dt><dd>{{ releaseGate.totals?.counter_analysis_requested || 0 }}</dd></div><div><dt>Lote / fornecedor</dt><dd>{{ sampleText(sample.client_submitted_info?.lot) }} · {{ sampleText(sample.client_submitted_info?.supplier_name) }}</dd></div></dl>
          <div v-if="latestDecision" class="lab-detail-notes"><h3>Última decisão · {{ finalDecisionLabels[latestDecision.decision] || latestDecision.decision }}</h3><p>{{ latestDecision.notes || 'Sem notas adicionais.' }}</p><p class="lab-muted lab-small">{{ latestDecision.decided_by_name || 'Operador' }} · {{ sampleDate(latestDecision.decided_at, true) }}</p></div>
          <form v-if="canEdit" class="lab-detail-form" :aria-busy="qcDecisionForm.processing" @submit.prevent="submitQcDecision">
            <label for="sample-qc-decision">Decisão final<select id="sample-qc-decision" v-model="qcDecisionForm.decision" class="lab-field" required :aria-invalid="Boolean(qcDecisionForm.errors.decision)" aria-describedby="sample-qc-decision-help"><option value="" disabled>Seleccionar decisão</option><option v-for="(label, value) in finalDecisionLabels" :key="value" :value="value">{{ label }}</option></select></label>
            <p id="sample-qc-decision-help" :class="qcDecisionForm.errors.decision || releaseDecisionBlocked ? 'lab-field-error' : 'lab-muted'" :role="qcDecisionForm.errors.decision ? 'alert' : undefined">{{ qcDecisionForm.errors.decision || (releaseDecisionBlocked ? 'A libertação exige resultados aprovados e ausência de revisão pendente.' : 'A decisão fica associada ao seu utilizador no histórico da amostra.') }}</p>
            <label for="sample-qc-notes">Notas da decisão<textarea id="sample-qc-notes" v-model="qcDecisionForm.notes" class="lab-field" rows="3" maxlength="2000" :aria-invalid="Boolean(qcDecisionForm.errors.notes)" :aria-describedby="qcDecisionForm.errors.notes ? 'sample-qc-notes-error' : undefined" placeholder="Fundamente a decisão e indique as acções necessárias."></textarea></label>
            <p v-if="qcDecisionForm.errors.notes" id="sample-qc-notes-error" class="lab-field-error" role="alert">{{ qcDecisionForm.errors.notes }}</p>
            <div class="lab-detail-form-actions"><p v-if="qcDecisionForm.recentlySuccessful" role="status">Decisão registada.</p><button type="submit" class="lab-btn lab-primary" :disabled="qcDecisionForm.processing || !qcDecisionForm.decision || releaseDecisionBlocked">{{ qcDecisionForm.processing ? 'A registar…' : 'Guardar decisão' }}</button></div>
          </form>
          <p v-else class="lab-detail-copy lab-muted">Consulta apenas. É necessária permissão de edição para registar uma decisão.</p>
        </section>
      </div>
      <aside class="lab-record-rail lab-detail-rail" aria-label="Contexto e documentos">
        <section><p class="lab-kicker">Contexto da amostra</p><dl class="lab-detail-list"><div><dt>Cliente</dt><dd>{{ sampleText(sample.customer?.name) }}</dd></div><div><dt>Produto</dt><dd>{{ sampleText(sample.collection_product?.product || sample.client_submitted_info?.product_name) }}</dd></div><div><dt>Lote</dt><dd>{{ sampleText(sample.client_submitted_info?.lot) }}</dd></div><div><dt>Retenção até</dt><dd>{{ sampleDate(sample.retention_due_at) }}</dd></div></dl></section>
        <section class="lab-note"><p class="lab-kicker">Documentos e ligações</p><div class="lab-detail-documents"><Link v-if="sample.collection_product?.workflow_url" :href="sample.collection_product.workflow_url" class="lab-link">Fluxo de colheita<ArrowRightIcon /></Link><Link v-if="sample.quality_certificate?.show_url" :href="sample.quality_certificate.show_url" class="lab-link">{{ sample.quality_certificate.code || 'Certificado' }}<ArrowRightIcon /></Link><a v-if="sample.quality_certificate?.pdf_url" :href="sample.quality_certificate.pdf_url" class="lab-link" target="_blank" rel="noopener">PDF do certificado<DocumentArrowDownIcon /><span class="sr-only"> (abre noutra janela)</span></a><p v-if="!sample.quality_certificate" class="lab-muted">O certificado ficará disponível após a conclusão do fluxo técnico.</p><Link v-if="workflowSummary.counter_analysis_count && sample.workflow_links?.counter_analysis_url" :href="sample.workflow_links.counter_analysis_url" class="lab-link">Contra-análises<ArrowRightIcon /></Link></div></section>
      </aside>
    </div>
  </div>
</template>
