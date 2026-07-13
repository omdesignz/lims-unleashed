<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--ds-text-soft)]">
            Sample Entry Code
          </p>
          <div class="mt-3 flex flex-wrap items-start gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ClipboardDocumentListIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">
                {{ sample.name }}
              </h1>
              <p class="mt-1 max-w-4xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Vista transversal da rececao, cadeia de custodia, analises, contra-analises, decisao CQ e certificado.
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="ds-chip">Codigo {{ sample.code }}</span>
                <span class="ds-chip">{{ statusLabel(sample.status) }}</span>
                <span class="ds-chip">{{ sampleTypeLabel(sample.sample_type) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap xl:max-w-xl xl:justify-end">
          <Link :href="route('vap_samples.index')" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar
          </Link>
          <a :href="route('vap_samples.samples.pdf', sample.id)" target="_blank" class="ds-button ds-button-primary">
            <DocumentArrowDownIcon class="h-4 w-4" />
            PDF da entrada
          </a>
          <a v-if="sample.collection_product?.workflow_url" :href="sample.collection_product.workflow_url" class="ds-button ds-button-secondary">
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
            Fluxo normal
          </a>
          <Link v-if="sample.workflow_links?.analysis_queue_url" :href="sample.workflow_links.analysis_queue_url" class="ds-button ds-button-secondary">
            <ClipboardDocumentListIcon class="h-4 w-4" />
            Resultados
          </Link>
          <Link v-if="workflowSummary.counter_analysis_count && sample.workflow_links?.counter_analysis_url" :href="sample.workflow_links.counter_analysis_url" class="ds-button ds-button-secondary">
            <ClipboardDocumentListIcon class="h-4 w-4" />
            Contra-analises
          </Link>
          <a v-if="sample.quality_certificate?.pdf_url" :href="sample.quality_certificate.pdf_url" target="_blank" class="ds-button ds-button-secondary">
            <ShieldCheckIcon class="h-4 w-4" />
            PDF certificado
          </a>
        </div>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <article
          v-for="card in summaryCards"
          :key="card.label"
          class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4"
        >
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
            {{ card.label }}
          </p>
          <p class="mt-3 text-lg font-black text-[var(--ds-text)]">
            {{ card.value }}
          </p>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ card.detail }}
          </p>
        </article>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
      <div class="space-y-6">
        <section class="ds-card p-5">
          <h2 class="text-base font-black text-[var(--ds-text)]">
            Rececao e enquadramento
          </h2>
          <dl class="mt-5 grid gap-3 sm:grid-cols-2">
            <div v-for="field in receptionFields" :key="field.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                {{ field.label }}
              </dt>
              <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ field.value }}
              </dd>
            </div>
          </dl>
          <div v-if="sample.obs" class="mt-5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
            {{ sample.obs }}
          </div>
        </section>

        <section class="ds-card p-5">
          <h2 class="text-base font-black text-[var(--ds-text)]">
            Rastreabilidade e retencao
          </h2>
          <dl class="mt-5 grid gap-3 sm:grid-cols-2">
            <div v-for="field in retentionFields" :key="field.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                {{ field.label }}
              </dt>
              <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ field.value }}
              </dd>
            </div>
          </dl>

          <div v-if="sample.discards?.length" class="mt-5 space-y-3">
            <h3 class="text-sm font-black text-[var(--ds-text)]">
              Historico de descarte
            </h3>
            <article
              v-for="discard in sample.discards"
              :key="discard.id"
              class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-sm"
            >
              <div class="flex items-center justify-between gap-3">
                <span class="font-bold text-[var(--ds-text)]">{{ discard.discard_method }}</span>
                <span class="font-semibold text-[var(--ds-text-muted)]">{{ formatDateTime(discard.discarded_at) }}</span>
              </div>
              <p class="mt-1 font-semibold text-[var(--ds-text-muted)]">
                {{ discard.qty }} · {{ discard.discarded_by || 'Sem operador' }}
              </p>
            </article>
          </div>
        </section>

        <section class="ds-card p-5">
          <h2 class="text-base font-black text-[var(--ds-text)]">
            Origem e contexto submetido
          </h2>
          <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                Origem
              </p>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ sample.client_submitted_info?.request_origin || 'client' }}
              </p>
            </div>
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                Pedido portal
              </p>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ sample.portal_request?.reference || 'Sem pedido portal' }}
              </p>
            </div>
          </div>
          <pre class="mt-5 max-h-80 overflow-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ formattedClientInfo }}</pre>
        </section>
      </div>

      <div class="space-y-6">
        <section class="ds-card p-5">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-base font-black text-[var(--ds-text)]">
                Fluxo ligado
              </h2>
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">
                Rececao -> colheita -> analises -> contra-analise -> certificado
              </p>
            </div>
            <span class="ds-chip">{{ linkedSampleIds.length }} sample IDs internos</span>
          </div>

          <div class="mt-5 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                Collection product
              </p>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ sample.collection_product?.id || 'Pendente' }}
              </p>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ sample.collection_product?.product || 'Ainda nao integrado no fluxo normal.' }}
              </p>
            </div>
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                Certificado
              </p>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ sample.quality_certificate?.code || 'Ainda nao emitido' }}
              </p>
              <div class="mt-2 flex flex-wrap gap-2">
                <a v-if="sample.quality_certificate?.show_url" :href="sample.quality_certificate.show_url" class="ds-table-action">
                  Abrir
                  <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
                </a>
                <a v-if="sample.quality_certificate?.pdf_url" :href="sample.quality_certificate.pdf_url" target="_blank" class="ds-table-action">
                  PDF
                  <DocumentArrowDownIcon class="h-3.5 w-3.5" />
                </a>
              </div>
            </div>
          </div>

          <div class="ds-command-surface mt-5 p-4">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
                  Proxima acao operacional
                </p>
                <h3 class="mt-2 text-base font-black text-[var(--ds-text)]">
                  {{ workflowSummary.next_action?.label || 'Abrir fila operacional' }}
                </h3>
                <p class="mt-1 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                  {{ workflowSummary.next_action?.description || 'Continue o processo a partir da fila de resultados.' }}
                </p>
              </div>
              <Link v-if="workflowSummary.next_action?.url" :href="workflowSummary.next_action.url" class="ds-button ds-button-primary">
                Abrir
                <ArrowTopRightOnSquareIcon class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </section>

        <section class="ds-table-shell">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
                Analises
              </p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">
                Fluxo tecnico
              </h2>
            </div>
            <span class="ds-chip">{{ analyses.length }} registos</span>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--ds-border)]">
              <thead class="ds-table-head">
                <tr>
                  <th class="px-4 py-3 text-left ds-table-heading">Analise</th>
                  <th class="px-4 py-3 text-left ds-table-heading">Perfil</th>
                  <th class="px-4 py-3 text-left ds-table-heading">Departamento</th>
                  <th class="px-4 py-3 text-left ds-table-heading">Resultado</th>
                  <th class="px-4 py-3 text-left ds-table-heading">Contra-analise</th>
                </tr>
              </thead>
              <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
                <tr v-if="!analyses.length" class="ds-table-row">
                  <td colspan="5" class="px-4 py-8 text-center text-sm font-semibold text-[var(--ds-text-muted)]">
                    Ainda nao ha analises ligadas a esta entrada de amostra.
                  </td>
                </tr>
                <tr v-for="analysis in analyses" :key="analysis.id" class="ds-table-row">
                  <td class="px-4 py-4 text-sm">
                    <a :href="analysis.analysis_url" class="font-black text-[rgb(var(--primary-800-rgb))] hover:text-[rgb(var(--primary-600-rgb))] dark:text-cyan-100">
                      #{{ analysis.id }}
                    </a>
                  </td>
                  <td class="ds-table-cell px-4 py-4">{{ analysis.profile || 'Sem perfil' }}</td>
                  <td class="ds-table-cell px-4 py-4">{{ analysis.department || 'Sem departamento' }}</td>
                  <td class="px-4 py-4 text-sm font-semibold text-[var(--ds-text-muted)]">
                    <div class="space-y-1">
                      <p>{{ analysis.result_id ? `#${analysis.result_id}` : 'Pendente' }}</p>
                      <p class="text-xs">{{ resultStageLabel(analysis.workflow_stage) }}</p>
                      <p v-if="analysis.results_summary?.total" class="text-xs">
                        {{ analysis.results_summary.approved }}/{{ analysis.results_summary.total }} aprovados · {{ analysis.results_summary.with_uncertainty }} c/ incerteza
                      </p>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-sm">
                    <div v-if="analysis.counter_analysis_items?.length" class="space-y-2">
                      <div
                        v-for="counterAnalysis in analysis.counter_analysis_items"
                        :key="`${analysis.id}-${counterAnalysis.result_id}`"
                      >
                        <a v-if="counterAnalysis.counter_analysis_url" :href="counterAnalysis.counter_analysis_url" class="font-black text-amber-700 hover:text-amber-600 dark:text-amber-200">
                          #{{ counterAnalysis.counter_analysis_id }}
                        </a>
                        <span v-else class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-black text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100">
                          Solicitada
                        </span>
                        <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                          {{ counterAnalysis.parameter || `Resultado #${counterAnalysis.result_id}` }}
                        </p>
                      </div>
                    </div>
                    <span v-else class="font-semibold text-[var(--ds-text-muted)]">Nao aberta</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-if="isInternalQcSample" class="ds-card p-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-200">
                CQ interno
              </p>
              <h2 class="mt-2 text-base font-black text-[var(--ds-text)]">
                {{ qualityControlPath.name || 'Controlo interno de materia-prima' }}
              </h2>
              <p class="mt-1 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Fluxo sem proposta comercial, com rastreabilidade para resultados, verificacao, aprovacao e decisao operacional.
              </p>
            </div>
            <span class="ds-chip">{{ disciplineLabel(sample.client_submitted_info?.analysis_discipline) }}</span>
          </div>

          <div :class="['mt-5 rounded-lg border p-4', releaseGatePanelClass(releaseGate.status)]">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] opacity-80">Gate de liberacao</p>
                <h3 class="mt-1 text-base font-black">{{ releaseGate.label || 'Aguardar decisao' }}</h3>
                <p class="mt-1 text-sm font-medium leading-6">{{ releaseGate.message || 'A decisao operacional sera apresentada quando houver dados suficientes.' }}</p>
              </div>
              <span :class="['inline-flex w-fit rounded-full px-3 py-1 text-xs font-black', releaseGateBadgeClass(releaseGate.status)]">
                {{ releaseGateStatusLabel(releaseGate.status) }}
              </span>
            </div>

            <dl class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              <div v-for="metric in releaseGateMetrics" :key="metric.label" class="rounded-lg border border-white/50 bg-white/55 px-3 py-2 dark:border-white/10 dark:bg-white/5">
                <dt class="text-[11px] font-black uppercase tracking-[0.16em] opacity-75">{{ metric.label }}</dt>
                <dd class="mt-1 text-sm font-black">{{ metric.value }}</dd>
              </div>
            </dl>

            <div v-if="latestReleaseDecision" class="mt-4 rounded-lg border border-white/50 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <p class="text-xs font-black uppercase tracking-[0.14em] opacity-75">Ultima decisao registada</p>
                  <p class="mt-1 text-sm font-black">{{ finalQcDecisionLabel(latestReleaseDecision.decision) }}</p>
                  <p v-if="latestReleaseDecision.notes" class="mt-2 text-sm font-medium leading-6 opacity-80">{{ latestReleaseDecision.notes }}</p>
                </div>
                <p class="text-xs font-black opacity-75">
                  {{ latestReleaseDecision.decided_by_name || 'Utilizador' }} · {{ formatDateTime(latestReleaseDecision.decided_at) }}
                </p>
              </div>
            </div>

            <form class="mt-4 rounded-lg border border-white/50 bg-white/75 p-4 dark:border-white/10 dark:bg-white/5" @submit.prevent="submitQcDecision">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <p class="text-sm font-black">Registar decisao operacional</p>
                  <p class="mt-1 text-xs font-semibold leading-5 opacity-75">
                    Use depois de validar resultados, incerteza e eventual contra-analise.
                  </p>
                </div>
                <button type="submit" :disabled="qcDecisionForm.processing || releaseDecisionBlocked" class="ds-button ds-button-primary">
                  {{ qcDecisionForm.processing ? 'A registar...' : 'Guardar decisao' }}
                </button>
              </div>

              <div class="mt-4 grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
                <label class="ds-field-group">
                  <span class="ds-field-label">Decisao final</span>
                  <select v-model="qcDecisionForm.decision" class="ds-field">
                    <option v-for="option in releaseDecisionOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                  <span v-if="qcDecisionForm.errors.decision" class="ds-field-error">{{ qcDecisionForm.errors.decision }}</span>
                  <span v-else-if="releaseDecisionBlocked" class="ds-field-error">
                    A liberacao fica bloqueada ate os resultados estarem aprovados e sem revisao pendente.
                  </span>
                </label>
                <label class="ds-field-group">
                  <span class="ds-field-label">Notas de decisao</span>
                  <textarea
                    v-model="qcDecisionForm.notes"
                    rows="3"
                    placeholder="Ex.: lote retido para investigacao, liberado para producao, ou registado apenas para tendencia..."
                    class="ds-field min-h-28"
                  />
                  <span v-if="qcDecisionForm.errors.notes" class="ds-field-error">{{ qcDecisionForm.errors.notes }}</span>
                </label>
              </div>
            </form>
          </div>

          <dl class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="field in qcFields" :key="field.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                {{ field.label }}
              </dt>
              <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                {{ field.value }}
              </dd>
            </div>
          </dl>
        </section>
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  ArrowTopRightOnSquareIcon,
  ClipboardDocumentListIcon,
  DocumentArrowDownIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

defineOptions({ layout: Layout })

const props = defineProps({
  sample: { type: Object, required: true },
  analyses: { type: Array, default: () => [] },
  linkedSampleIds: { type: Array, default: () => [] },
  workflowSummary: { type: Object, default: () => ({}) },
})

const formattedClientInfo = computed(() => JSON.stringify(props.sample.client_submitted_info || {}, null, 2))
const qualityControlPath = computed(() => props.sample.client_submitted_info?.quality_control_path || {})
const releaseGate = computed(() => props.workflowSummary?.quality_control_release || {})
const latestReleaseDecision = computed(() => releaseGate.value?.current_decision || null)
const isInternalQcSample = computed(() => (
  props.sample.client_submitted_info?.request_origin === 'internal'
  && ['MATERIA_PRIMA', 'RAW_MATERIAL'].includes(props.sample.sample_type)
))

const summaryCards = computed(() => [
  {
    label: 'Estado',
    value: statusLabel(props.sample.status),
    detail: 'Estado da entrada',
  },
  {
    label: 'Lab code',
    value: props.workflowSummary.linked_lab_code || props.sample.collection_product?.code || 'Pendente',
    detail: 'Codigo operacional',
  },
  {
    label: 'Amostras ligadas',
    value: props.workflowSummary.linked_sample_count || 0,
    detail: 'IDs internos',
  },
  {
    label: 'Analises',
    value: props.workflowSummary.analysis_count || props.analyses.length,
    detail: 'Ensaios associados',
  },
  {
    label: 'Certificado',
    value: props.workflowSummary.quality_certificate_ready ? 'Emitido' : 'Ainda nao',
    detail: 'Saida documental',
  },
])

const receptionFields = computed(() => [
  { label: 'Tipo', value: sampleTypeLabel(props.sample.sample_type) },
  { label: 'Recebida em', value: formatDateTime(props.sample.received_at) },
  { label: 'Cliente', value: props.sample.customer?.name || 'Sem cliente' },
  { label: 'Recebida por', value: props.sample.received_by?.name || 'Sem registo' },
  { label: 'Laboratorio', value: props.sample.lab?.name || 'Sem laboratorio' },
  { label: 'Departamento', value: props.sample.department?.name || 'Sem departamento' },
  { label: 'Armazem', value: props.sample.warehouse?.name || 'Sem armazem' },
  { label: 'Embalagem', value: props.sample.packaging?.name || 'Sem embalagem' },
])

const retentionFields = computed(() => [
  { label: 'Periodo de retencao', value: `${props.sample.retention_period_days || 0} dias` },
  { label: 'Estado de retencao', value: retentionLabel(props.sample.retention_status) },
  { label: 'Prazo de retencao', value: props.sample.retention_due_at || 'N/D' },
  { label: 'Descarte previsto', value: props.sample.discard_scheduled_at || 'N/D' },
])

const releaseGateMetrics = computed(() => [
  {
    label: 'Resultados',
    value: `${releaseGate.value.totals?.approved || 0}/${releaseGate.value.totals?.results || 0} aprovados`,
  },
  {
    label: 'Incerteza',
    value: `${releaseGate.value.totals?.with_uncertainty || 0} resultados`,
  },
  {
    label: 'Contra-analise',
    value: releaseGate.value.totals?.counter_analysis_requested || 0,
  },
  {
    label: 'Decisao configurada',
    value: qcDecisionLabel(releaseGate.value.decision),
  },
])

const qcFields = computed(() => [
  { label: 'Objetivo', value: qcPurposeLabel(props.sample.client_submitted_info?.quality_control_purpose) },
  { label: 'Decisao', value: qcDecisionLabel(props.sample.client_submitted_info?.qc_decision) },
  { label: 'Lote', value: props.sample.client_submitted_info?.lot || 'N/D' },
  { label: 'Fornecedor', value: props.sample.client_submitted_info?.supplier_name || 'N/D' },
])

const releaseDecisionOptions = [
  { value: 'released', label: 'Liberada para uso' },
  { value: 'quarantined', label: 'Manter em quarentena' },
  { value: 'investigation_required', label: 'Abrir investigacao' },
  { value: 'rejected', label: 'Rejeitada' },
  { value: 'trend_recorded', label: 'Registar para tendencia' },
]

const qcDecisionForm = useForm({
  decision: latestReleaseDecision.value?.decision || 'released',
  notes: '',
})

const releaseDecisionBlocked = computed(() => qcDecisionForm.decision === 'released' && !releaseGate.value?.can_release)

const submitQcDecision = () => {
  if (releaseDecisionBlocked.value) {
    return
  }

  qcDecisionForm.patch(
    route('vap_samples.samples.internal-quality-control-decision', { sampleEntry: props.sample.id }),
    {
      preserveScroll: true,
      onSuccess: () => qcDecisionForm.reset('notes'),
    },
  )
}

const formatDateTime = (value) => {
  if (!value) {
    return 'N/D'
  }

  return new Date(value).toLocaleString('pt-PT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const statusLabel = (status) => ({
  POR_INICIAR: 'Por iniciar',
  EN_PROGRESO: 'Em progresso',
  EN_PAUSA: 'Em pausa',
  COMPLETADO: 'Completado',
  CANCELADO: 'Cancelado',
}[status] || status || 'N/D')

const sampleTypeLabel = (type) => ({
  ROTINA: 'Rotina',
  MATERIA_PRIMA: 'Materia-prima',
  PRODUTO_ACABADO: 'Produto acabado',
  ESTABILIDADE: 'Estabilidade',
  CONTRAPROVA: 'Contraprova',
  COUNTER_ANALYSIS: 'Contra-analise',
  INTERLABORATORIAL: 'Interlaboratorial',
  RETENCAO: 'Retencao',
}[type] || type || 'N/D')

const disciplineLabel = (discipline) => ({
  microbiology: 'Microbiologia',
  chemistry: 'Quimica / fisico-quimica',
  microbiology_and_chemistry: 'Microbiologia + quimica',
}[discipline] || 'Disciplina nao definida')

const qcPurposeLabel = (purpose) => ({
  raw_material_release: 'Liberacao de materia-prima',
  supplier_qualification: 'Qualificacao de fornecedor',
  process_validation: 'Validacao de processo',
  stability_follow_up: 'Acompanhamento de estabilidade',
  investigation: 'Investigacao interna',
  other: 'Outro',
}[purpose] || 'N/D')

const qcDecisionLabel = (decision) => ({
  hold_until_release: 'Reter ate liberacao',
  release_if_compliant: 'Liberar se conforme',
  investigate_before_release: 'Investigar antes de liberar',
  trend_only: 'Apenas tendencia',
}[decision] || 'N/D')

const finalQcDecisionLabel = (decision) => ({
  released: 'Liberada para uso',
  rejected: 'Rejeitada',
  quarantined: 'Em quarentena',
  investigation_required: 'Investigacao requerida',
  trend_recorded: 'Registada para tendencia',
}[decision] || 'Decisao registada')

const resultStageLabel = (stage) => ({
  pending_results: 'Sem resultados inseridos',
  insertion: 'Insercao pendente',
  verification: 'Verificacao pendente',
  approval: 'Aprovacao pendente',
  approved: 'Resultados aprovados',
}[stage] || 'Estado nao definido')

const releaseGateStatusLabel = (status) => ({
  pending_results: 'Retida',
  awaiting_approval: 'Em validacao',
  requires_review: 'Revisao tecnica',
  ready_for_release: 'Liberavel',
  not_applicable: 'N/A',
}[status] || 'Em avaliacao')

const releaseGatePanelClass = (status) => ({
  pending_results: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100',
  awaiting_approval: 'border-cyan-200 bg-cyan-50 text-cyan-950 dark:border-cyan-400/30 dark:bg-cyan-400/10 dark:text-cyan-100',
  requires_review: 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100',
  ready_for_release: 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-100',
}[status] || 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text)]')

const releaseGateBadgeClass = (status) => ({
  pending_results: 'bg-amber-600 text-white dark:bg-amber-300 dark:text-amber-950',
  awaiting_approval: 'bg-cyan-700 text-white dark:bg-cyan-300 dark:text-cyan-950',
  requires_review: 'bg-rose-700 text-white dark:bg-rose-300 dark:text-rose-950',
  ready_for_release: 'bg-emerald-700 text-white dark:bg-emerald-300 dark:text-emerald-950',
}[status] || 'bg-[var(--ds-panel-muted)] text-[var(--ds-text)]')

const retentionLabel = (status) => ({
  active: 'Ativa',
  due_soon: 'Proxima do descarte',
  overdue: 'Vencida',
}[status] || status || 'N/D')
</script>
