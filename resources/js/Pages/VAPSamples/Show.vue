<script setup>
import { computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { ArrowRight as ArrowRightIcon, FileDown as DocumentArrowDownIcon } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { usePermission } from '@/Composables/usePermissions'
import PageHeader from '@/Components/plano/PageHeader.vue'
import Journey from '@/Components/plano/Journey.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
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
const gateTone = computed(() => ({ ready_for_release: 'ok', requires_review: 'wait', awaiting_approval: 'run' }[releaseGate.value.status] || 'neutral'))
const chipTones = { received: 'neutral', analysis: 'run', review: 'wait', complete: 'ok', hold: 'done' }
const statusTone = computed(() => chipTones[sampleStatusTones[props.sample.status]] || 'neutral')

// The journey from the sample's own evidence: reception, the slowest analysis stage, the certificate.
const stageOrder = ['pending_results', 'insertion', 'verification', 'approval', 'approved']
const slowestStage = computed(() => props.analyses
  .map((analysis) => analysis.workflow_stage)
  .filter((stage) => stageOrder.includes(stage))
  .sort((first, second) => stageOrder.indexOf(first) - stageOrder.indexOf(second))[0] ?? null)
const journey = computed(() => {
  const certified = Boolean(props.sample.quality_certificate)
  const received = Boolean(props.sample.received_at)
  const position = certified ? 5 : !received ? 0 : ({ verification: 2, approval: 3, approved: 4 }[slowestStage.value] ?? 1)
  const state = (index) => (index < position ? 'done' : index === position ? 'current' : 'todo')
  const counts = resultCounts.value

  return [
    { label: 'Recepção', state: state(0), title: props.sample.received_by?.name || (received ? 'Recebida' : 'Por receber'), note: sampleDate(props.sample.received_at, true) },
    { label: 'Análise', state: state(1), title: props.analyses.length ? `${props.analyses.length} ${props.analyses.length === 1 ? 'análise' : 'análises'}` : 'Por atribuir', note: props.analyses.length ? (resultStages[slowestStage.value] || 'Em curso') : '—' },
    { label: 'Verificação', state: state(2), title: position === 2 ? 'À espera' : position > 2 ? 'Verificada' : 'Verificador', note: '—' },
    { label: 'Aprovação', state: state(3), title: position === 3 ? 'À espera' : position > 3 ? 'Aprovada' : 'Resp. técnico', note: counts.total ? `${counts.approved} / ${counts.total} aprovados` : '—' },
    { label: 'Certificado', state: state(4), title: certified ? (props.sample.quality_certificate.code || 'Emitido') : 'Emissão', note: certified ? 'Disponível' : '—' },
  ]
})
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
  <div class="pl-page" data-template="dossier">
    <Head :title="sample.code || sample.name" />
    <PageHeader
      :crumbs="[{ title: 'Amostras' }, { title: 'Fila', url: route('vap_samples.queue') }, { title: sample.code || 'Por atribuir' }]"
      :title="sample.code || 'Código por atribuir'"
    >
      <template #badges>
        <StatusChip :tone="statusTone">{{ sampleStatusLabels[sample.status] || sample.status }}</StatusChip>
        <StatusChip v-if="isInternalQcSample" :tone="gateTone">{{ releaseGate.label || 'Aguardar avaliação' }}</StatusChip>
      </template>
      <template #lede>{{ sample.customer?.name || 'Cliente por associar' }} — {{ sample.name }}. {{ sample.lab?.name || 'Laboratório por associar' }} · recepção {{ sampleDate(sample.received_at) }}.</template>
      <template #actions>
        <a :href="route('vap_samples.samples.pdf', sample.id)" class="ds-button ds-button-quiet" target="_blank" rel="noopener"><span>PDF da entrada</span><DocumentArrowDownIcon aria-hidden="true" /><span class="sr-only"> (abre noutra janela)</span></a>
      </template>
    </PageHeader>

    <Journey class="mb-10" :steps="journey" />

    <div class="pl-dossier-grid">
      <div class="min-w-0">
        <TabGroup>
          <TabList class="pl-tabs mb-6" aria-label="Detalhes da amostra">
            <Tab v-slot="{ selected }" as="template"><button class="pl-tab" :aria-selected="selected">Análises<span class="pl-num">{{ analyses.length }}</span></button></Tab>
            <Tab v-slot="{ selected }" as="template"><button class="pl-tab" :aria-selected="selected">Recepção</button></Tab>
            <Tab v-slot="{ selected }" as="template"><button class="pl-tab" :aria-selected="selected">Rastreabilidade</button></Tab>
          </TabList>
          <TabPanels>
            <TabPanel>
              <div v-if="analyses.length" class="pl-panel" tabindex="0" aria-label="Análises da amostra">
                <DataTable><thead><tr><th scope="col">Análise / perfil</th><th scope="col">Departamento</th><th scope="col">Resultados</th><th scope="col">Contra-análise</th></tr></thead><tbody>
                  <tr v-for="analysis in analyses" :key="analysis.id">
                    <td><Link :href="analysis.analysis_url" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">#{{ analysis.id }}</Link><span class="block text-[12.5px] text-[var(--pl-muted)]">{{ analysis.profile || 'Perfil por associar' }}</span></td>
                    <td>{{ analysis.department || 'Não associado' }}</td>
                    <td><StatusChip :tone="analysis.workflow_stage === 'approved' ? 'ok' : analysis.workflow_stage === 'pending_results' ? 'neutral' : 'wait'">{{ resultStages[analysis.workflow_stage] || 'Estado por definir' }}</StatusChip><span v-if="analysis.results_summary?.total" class="pl-num mt-1 block text-[12px] text-[var(--pl-muted)]">{{ analysis.results_summary.approved }}/{{ analysis.results_summary.total }} aprovados · {{ analysis.results_summary.with_uncertainty }} com incerteza</span></td>
                    <td><div v-for="item in analysis.counter_analysis_items || []" :key="item.result_id"><Link v-if="item.counter_analysis_url" :href="item.counter_analysis_url" class="pl-num pl-acc">#{{ item.counter_analysis_id }}</Link><StatusChip v-else tone="wait">Solicitada</StatusChip><span class="block text-[12px] text-[var(--pl-muted)]">{{ item.parameter || `Resultado #${item.result_id}` }}</span></div><span v-if="!analysis.counter_analysis_items?.length" class="text-[var(--pl-faint)]">Não solicitada</span></td>
                  </tr>
                </tbody></DataTable>
              </div>
              <div v-else class="ds-empty-state grid justify-items-start gap-2 p-6"><span class="pl-k">A análise começa aqui.</span><p class="text-sm text-[var(--pl-muted)]">Esta entrada ainda não tem análises ligadas. Consulte a recepção e o próximo passo.</p></div>
            </TabPanel>
            <TabPanel class="grid gap-7">
              <section class="pl-panel"><div class="pl-panel-head"><h2 class="pl-k">Recepção e enquadramento</h2></div><dl class="pl-facts pl-facts-2"><div v-for="[label, value] in receptionFields" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ sampleText(value) }}</dd></div></dl><div class="grid gap-2 border-t border-[var(--pl-line)] p-4"><h3 class="pl-k pl-muted">Serviços solicitados</h3><p class="text-sm">{{ sampleText(sample.requested_services, 'Sem serviços registados.') }}</p><h3 class="pl-k pl-muted mt-3">Observações</h3><p class="text-sm">{{ sample.obs || 'Sem observações de recepção.' }}</p></div></section>
              <section class="pl-panel"><div class="pl-panel-head"><h2 class="pl-k">Origem e condições de colheita</h2></div><dl class="pl-facts pl-facts-2"><div v-for="[label, value] in contextFields" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ sampleText(value) }}</dd></div></dl><div v-if="sample.client_submitted_info?.chain_of_custody_notes" class="grid gap-2 border-t border-[var(--pl-line)] p-4"><h3 class="pl-k pl-muted">Cadeia de custódia</h3><p class="text-sm">{{ sample.client_submitted_info.chain_of_custody_notes }}</p></div></section>
            </TabPanel>
            <TabPanel class="grid gap-7">
              <section class="pl-panel"><div class="pl-panel-head"><h2 class="pl-k">Retenção e rastreabilidade</h2></div><dl class="pl-facts pl-facts-2"><div v-for="[label, value] in retentionFields" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ sampleText(value) }}</dd></div></dl></section>
              <section class="pl-panel"><div class="pl-panel-head"><h2 class="pl-k">Histórico de descarte</h2></div><div v-if="sample.discards?.length"><article v-for="discard in sample.discards" :key="discard.id" class="pl-row"><span><strong class="font-semibold">{{ discard.discard_method }}</strong><span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">{{ sampleDate(discard.discarded_at, true) }} · {{ discard.qty }} · {{ discard.discarded_by || 'Operador por registar' }}</span></span><a :href="route('vap_samples.discards.pdf', discard.id)" class="pl-k pl-acc" target="_blank" rel="noopener">Certificado →<span class="sr-only"> (abre noutra janela)</span></a></article></div><p v-else class="p-4 text-sm text-[var(--pl-muted)]">Nenhum descarte registado. A custódia continua acompanhada neste laboratório.</p></section>
            </TabPanel>
          </TabPanels>
        </TabGroup>

        <section v-if="isInternalQcSample" class="pl-panel mt-7" aria-labelledby="qc-title">
          <div class="pl-panel-head"><h2 id="qc-title" class="pl-k">Decisão de qualidade · controlo interno</h2><StatusChip :tone="gateTone">{{ releaseGate.label || 'Aguardar avaliação' }}</StatusChip></div>
          <p class="px-4 pt-4 text-sm text-[var(--pl-muted)]">{{ releaseGate.message || 'Valide os resultados antes de registar a decisão operacional.' }}</p>
          <dl class="pl-facts pl-facts-2 mt-3 border-y border-[var(--pl-line)]"><div class="pl-fact"><dt>Resultados aprovados</dt><dd class="pl-num">{{ releaseGate.totals?.approved || 0 }} / {{ releaseGate.totals?.results || 0 }}</dd></div><div class="pl-fact"><dt>Com incerteza</dt><dd class="pl-num">{{ releaseGate.totals?.with_uncertainty || 0 }}</dd></div><div class="pl-fact"><dt>Contra-análises</dt><dd class="pl-num">{{ releaseGate.totals?.counter_analysis_requested || 0 }}</dd></div><div class="pl-fact"><dt>Lote / fornecedor</dt><dd>{{ sampleText(sample.client_submitted_info?.lot) }} · {{ sampleText(sample.client_submitted_info?.supplier_name) }}</dd></div></dl>
          <div v-if="latestDecision" class="grid gap-1 border-b border-[var(--pl-line)] p-4"><h3 class="pl-k">Última decisão · {{ finalDecisionLabels[latestDecision.decision] || latestDecision.decision }}</h3><p class="text-sm">{{ latestDecision.notes || 'Sem notas adicionais.' }}</p><p class="pl-k pl-faint">{{ latestDecision.decided_by_name || 'Operador' }} · {{ sampleDate(latestDecision.decided_at, true) }}</p></div>
          <form v-if="canEdit" class="grid gap-4 p-4" :aria-busy="qcDecisionForm.processing" @submit.prevent="submitQcDecision">
            <BaseSelect id="sample-qc-decision" v-model="qcDecisionForm.decision" label="Decisão final" placeholder="Seleccionar decisão" required :options="Object.entries(finalDecisionLabels).map(([value, label]) => ({ value, label }))" aria-describedby="sample-qc-decision-help" />
            <p id="sample-qc-decision-help" :class="qcDecisionForm.errors.decision || releaseDecisionBlocked ? 'ds-field-error' : 'ds-field-hint'" :role="qcDecisionForm.errors.decision ? 'alert' : undefined">{{ qcDecisionForm.errors.decision || (releaseDecisionBlocked ? 'A libertação exige resultados aprovados e ausência de revisão pendente.' : 'A decisão fica associada ao seu utilizador no histórico da amostra.') }}</p>
            <div class="ds-field-group"><label for="sample-qc-notes" class="ds-field-label">Notas da decisão</label><textarea id="sample-qc-notes" v-model="qcDecisionForm.notes" class="ds-field" rows="3" maxlength="2000" :aria-invalid="Boolean(qcDecisionForm.errors.notes)" :aria-describedby="qcDecisionForm.errors.notes ? 'sample-qc-notes-error' : undefined" placeholder="Fundamente a decisão e indique as acções necessárias."></textarea></div>
            <p v-if="qcDecisionForm.errors.notes" id="sample-qc-notes-error" class="ds-field-error" role="alert">{{ qcDecisionForm.errors.notes }}</p>
            <div class="flex items-center justify-end gap-4"><p v-if="qcDecisionForm.recentlySuccessful" class="pl-k text-[var(--pl-ok)]" role="status">Decisão registada.</p><button type="submit" class="ds-button ds-button-primary" :disabled="qcDecisionForm.processing || !qcDecisionForm.decision || releaseDecisionBlocked">{{ qcDecisionForm.processing ? 'A registar…' : 'Guardar decisão' }}</button></div>
          </form>
          <p v-else class="p-4 text-sm text-[var(--pl-muted)]">Consulta apenas. É necessária permissão de edição para registar uma decisão.</p>
        </section>
      </div>

      <aside class="grid content-start gap-7" aria-label="Contexto e documentos">
        <section class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">Dossier</h2><span class="pl-k pl-faint">{{ workflowSummary.linked_lab_code || sample.collection_product?.code || 'Sem código' }}</span></div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Cliente</dt><dd>{{ sampleText(sample.customer?.name) }}</dd></div>
            <div class="pl-fact"><dt>Produto</dt><dd>{{ sampleText(sample.collection_product?.product || sample.client_submitted_info?.product_name) }}</dd></div>
            <div class="pl-fact"><dt>Lote</dt><dd>{{ sampleText(sample.client_submitted_info?.lot) }}</dd></div>
            <div class="pl-fact"><dt>Retenção até</dt><dd class="pl-num">{{ sampleDate(sample.retention_due_at) }}</dd></div>
            <div class="pl-fact"><dt>Análises</dt><dd class="pl-num">{{ analyses.length }} · {{ linkedSampleIds.length }} internas</dd></div>
            <div class="pl-fact"><dt>Aprovados</dt><dd class="pl-num">{{ resultCounts.approved }} / {{ resultCounts.total }}</dd></div>
          </dl>
        </section>
        <section class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">Documentos e ligações</h2></div>
          <Link v-if="sample.collection_product?.workflow_url" :href="sample.collection_product.workflow_url" class="pl-row"><span>Fluxo de colheita</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <Link v-if="sample.quality_certificate?.show_url" :href="sample.quality_certificate.show_url" class="pl-row"><span class="pl-num">{{ sample.quality_certificate.code || 'Certificado' }}</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <a v-if="sample.quality_certificate?.pdf_url" :href="sample.quality_certificate.pdf_url" class="pl-row" target="_blank" rel="noopener"><span>PDF do certificado<span class="sr-only"> (abre noutra janela)</span></span><DocumentArrowDownIcon class="h-4 w-4" aria-hidden="true" /></a>
          <Link v-if="workflowSummary.counter_analysis_count && sample.workflow_links?.counter_analysis_url" :href="sample.workflow_links.counter_analysis_url" class="pl-row"><span>Contra-análises</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <p v-if="!sample.quality_certificate" class="p-4 text-sm text-[var(--pl-muted)]">O certificado ficará disponível após a conclusão do fluxo técnico.</p>
        </section>
      </aside>
    </div>

    <NextStepBar v-if="workflowSummary.next_action">
      <strong class="font-semibold">{{ workflowSummary.next_action.label }}.</strong> <span class="text-[var(--pl-muted)]">{{ workflowSummary.next_action.description }}</span>
      <template #actions>
        <Link :href="route('vap_samples.queue')" class="ds-button ds-button-quiet">Voltar à fila</Link>
        <Link v-if="workflowSummary.next_action.url" :href="workflowSummary.next_action.url" class="ds-button ds-button-primary"><span>Continuar</span><ArrowRightIcon aria-hidden="true" /></Link>
      </template>
    </NextStepBar>
    <p v-else class="mt-10"><Link :href="route('vap_samples.queue')" class="pl-k pl-acc">← Todas as amostras</Link></p>
  </div>
</template>
