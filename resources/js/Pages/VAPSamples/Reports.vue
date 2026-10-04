<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="title || 'Relatórios de amostras'" lede="Recepção, ciclo analítico, descartes, tempos de resposta e controlo interno, para gestão e auditoria laboratorial.">
      <template #badges>
        <span class="ds-chip">Gerado em {{ formatDateTime(generatedAt) }}</span>
      </template>
      <template #actions>
        <a :href="sampleExportUrl" class="ds-button ds-button-secondary">Exportar amostras<ArrowDownTrayIcon aria-hidden="true" /></a>
        <a :href="discardExportUrl" class="ds-button ds-button-secondary">Exportar descartes<DocumentArrowDownIcon aria-hidden="true" /></a>
      </template>
    </PageHeader>

    <!-- The filters scope every number and chart below them. -->
    <section class="pl-panel" aria-labelledby="report-filters">
      <header class="pl-panel-head">
        <h2 id="report-filters" class="pl-k">Recorte</h2>
        <span class="pl-k pl-faint">{{ samples.total || 0 }} amostras</span>
      </header>
      <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-5">
        <combobox-enhanced v-model="sampleScopeSelection" title-label="Âmbito" placeholder="Todas as origens" :options="sampleScopeFilterOptions" :load-options="loadSampleScopes" />
        <combobox-enhanced v-model="qcReleaseSelection" title-label="Decisão CQ" placeholder="Todas as decisões" :options="qcReleaseFilterOptions" :load-options="loadQcReleaseStatuses" />
        <date-picker-enhanced v-model="form.date_from" label="Data inicial" :is-dark="isDark" />
        <date-picker-enhanced v-model="form.date_to" label="Data final" :is-dark="isDark" />
        <combobox-enhanced v-model="statusSelection" title-label="Estado" placeholder="Todos os estados" :options="statusFilterOptions" :load-options="loadStatuses" />
        <combobox-enhanced v-model="sampleTypeSelection" title-label="Tipo de amostra" placeholder="Todos os tipos" :options="sampleTypeOptions" :load-options="loadSampleTypes" />
        <combobox-enhanced v-model="customerSelection" title-label="Cliente" placeholder="Todos os clientes" :options="customerOptions" :load-options="loadCustomers" />
        <combobox-enhanced v-model="labSelection" title-label="Laboratório" placeholder="Todos os laboratórios" :options="labOptions" :load-options="loadLabs" />
        <combobox-enhanced v-model="departmentSelection" title-label="Departamento" placeholder="Todos os departamentos" :options="departmentOptions" :load-options="loadDepartments" />
        <combobox-enhanced v-model="discardMethodSelection" title-label="Método de descarte" placeholder="Todos os métodos" :options="discardMethodOptions" :load-options="loadDiscardMethods" />
      </div>
      <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--pl-line)] px-4 py-3">
        <button type="button" class="ds-button ds-button-quiet" @click="resetFilters">Redefinir</button>
        <button type="button" class="ds-button ds-button-primary" @click="applyFilters">Aplicar filtros<FunnelIcon aria-hidden="true" /></button>
      </div>
    </section>

    <dl class="pl-cells">
      <div v-for="metric in summaryCards" :key="metric.label" class="pl-cell" :class="{ 'pl-cell-bad': metric.bad }">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
        <dd v-if="metric.note" class="text-xs text-[var(--pl-muted)]">{{ metric.note }}</dd>
      </div>
    </dl>

    <section class="grid gap-6 xl:grid-cols-3" aria-label="Indicadores do recorte">
      <article class="pl-panel min-w-0">
        <header class="pl-panel-head">
          <h2 class="pl-k">Estados do ciclo</h2>
          <span class="pl-k pl-faint">{{ statusChartTotal }} amostras</span>
        </header>
        <div class="p-4">
          <PlanoChart kind="donut" label="Amostras por estado do ciclo" :categories="statusChartLabels" :series="[{ name: 'Amostras', data: statusChartSeries }]" :tones="statusTones" />
        </div>
      </article>

      <article class="pl-panel min-w-0">
        <header class="pl-panel-head">
          <h2 class="pl-k">Recepção no período</h2>
          <span class="pl-k pl-faint">{{ sampleTimeline.length }} dias</span>
        </header>
        <div class="p-4">
          <PlanoChart kind="column" label="Amostras recebidas por dia" :categories="sampleTimeline.map((item) => formatDate(item.date))" :series="[{ name: 'Amostras recebidas', data: sampleTimeline.map((item) => Number(item.total) || 0) }]" />
        </div>
      </article>

      <article class="pl-panel min-w-0">
        <header class="pl-panel-head">
          <h2 class="pl-k">Descarte por método</h2>
          <span class="pl-k pl-faint">{{ discardChartTotal }} registos</span>
        </header>
        <div class="p-4">
          <PlanoChart kind="bar" label="Descartes por método" :categories="discardMethodBreakdown.map((item) => item.label)" :series="[{ name: 'Descartes', data: discardMethodBreakdown.map((item) => Number(item.total) || 0) }]" empty-text="Sem descartes neste recorte." />
        </div>
      </article>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-4 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <BeakerIcon class="mt-0.5 h-5 w-5 text-emerald-700 dark:text-emerald-300" />
          <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
              <span class="ds-kicker">ISO 17025</span>
              <span class="ds-chip">
                <span class="lims-status-dot lims-status-dot-release" />
                CQ interno
              </span>
            </div>
            <h2 class="ds-heading mt-2 text-lg">Matéria-prima em controlo interno</h2>
            <p class="ds-copy mt-2 text-xs">
              Amostras internas seguem o mesmo ciclo de recepção, código, análise, verificação e aprovação, preservando rastreabilidade sem exigir proposta comercial.
            </p>
          </div>
        </div>
        <button type="button" class="ds-button ds-button-secondary shrink-0" @click="showInternalQcOnly">
          <CheckCircleIcon class="h-4 w-4" />
          Ver apenas CQ interno
        </button>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in internalQcMetrics" :key="metric.label" class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ metric.label }}</dt>
          <dd class="mt-2 text-xl font-bold" :class="metric.tone">{{ metric.value }}</dd>
        </div>
      </dl>

      <div class="grid border-t border-[color:var(--ds-border)] xl:grid-cols-[0.8fr_1fr_1.5fr]">
        <section class="border-b border-[color:var(--ds-border)] p-5 xl:border-b-0 xl:border-r">
          <h3 class="ds-heading text-sm">Disciplinas</h3>
          <dl class="mt-3 divide-y divide-[color:var(--ds-border)]">
            <div v-for="item in disciplineBreakdown" :key="item.label" class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ item.label }}</dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ item.value }}</dd>
            </div>
          </dl>
        </section>

        <section class="border-b border-[color:var(--ds-border)] p-5 xl:border-b-0 xl:border-r">
          <h3 class="ds-heading text-sm">Decisão final</h3>
          <dl class="mt-3 divide-y divide-[color:var(--ds-border)]">
            <div v-for="item in releaseDecisionBreakdown" :key="item.value" class="flex items-center justify-between gap-3 py-3">
              <dt class="flex items-center gap-2 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                <span class="lims-status-dot" :class="qcReleaseDotClass(item.value)" />
                {{ item.label }}
              </dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ item.total }}</dd>
            </div>
          </dl>
        </section>

        <section class="p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="ds-heading text-sm">Últimas entradas de CQ</h3>
              <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Amostras internas mais recentes.</p>
            </div>
            <span class="ds-chip">{{ internalQcSamples.length }} recentes</span>
          </div>
          <div v-if="internalQcSamples.length" class="mt-3 divide-y divide-[color:var(--ds-border)] border-y border-[color:var(--ds-border)]">
            <article v-for="sample in internalQcSamples" :key="sample.id" class="py-3">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                  <p class="truncate font-mono text-xs font-bold text-primary-800 dark:text-primary-200">{{ sample.code }}</p>
                  <p class="mt-1 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ sample.name }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                    {{ disciplineLabel(sample.quality_control?.discipline) }} · {{ qcPurposeLabel(sample.quality_control?.purpose) }}
                  </p>
                </div>
                <span class="ds-chip shrink-0">
                  <span class="lims-status-dot" :class="statusDotClass(sample.status)" />
                  {{ getStatusLabel(sample.status) }}
                </span>
              </div>
              <p class="mt-2 text-xs text-[color:var(--ds-text-soft)]">
                Lote {{ sample.quality_control?.lot || 'N/A' }} · {{ sample.quality_control?.supplier_name || 'Fornecedor N/A' }} · {{ qcDecisionLabel(sample.quality_control?.decision) }}
              </p>
              <p class="mt-2 flex items-center gap-2 text-xs font-bold text-[color:var(--ds-text)]">
                <span class="lims-status-dot" :class="qcReleaseDotClass(sample.quality_control?.final_decision)" />
                {{ qcReleaseLabel(sample.quality_control?.final_decision) }}
              </p>
            </article>
          </div>
          <div v-else class="ds-empty-state mt-3 p-5 text-center">
            <p class="text-xs text-[color:var(--ds-text-soft)]">Sem amostras internas neste recorte.</p>
          </div>
        </section>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
      <article v-for="group in breakdownGroups" :key="group.title" class="ds-panel overflow-hidden">
        <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
          <h2 class="ds-heading text-base">{{ group.title }}</h2>
          <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ group.description }}</p>
        </div>
        <dl v-if="group.items.length" class="divide-y divide-[color:var(--ds-border)] px-5">
          <div v-for="item in group.items" :key="item.label" class="flex items-center justify-between gap-3 py-3">
            <dt class="flex min-w-0 items-center gap-2 text-xs font-semibold text-[color:var(--ds-text-soft)]">
              <span class="lims-status-dot shrink-0" :class="item.dotClass" />
              <span class="truncate">{{ item.label }}</span>
            </dt>
            <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ item.value }}</dd>
          </div>
        </dl>
        <div v-else class="p-5">
          <div class="ds-empty-state p-5 text-center">
            <p class="text-xs text-[color:var(--ds-text-soft)]">Sem dados para o filtro actual.</p>
          </div>
        </div>
      </article>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
        <div>
          <h2 class="ds-heading text-base">Linha temporal da recepção</h2>
          <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Volume diário no período seleccionado.</p>
        </div>
        <span class="ds-chip">{{ sampleTimeline.length }} pontos</span>
      </div>
      <div v-if="sampleTimeline.length" class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
        <div v-for="item in sampleTimeline" :key="item.date" class="px-4 py-4">
          <p class="text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ formatDate(item.date) }}</p>
          <p class="mt-2 text-xl font-bold text-primary-800 dark:text-primary-200">{{ item.total }}</p>
        </div>
      </div>
      <div v-else class="p-5">
        <div class="ds-empty-state p-6 text-center">
          <p class="text-xs text-[color:var(--ds-text-soft)]">Sem movimentação no período seleccionado.</p>
        </div>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
      <article class="ds-panel overflow-hidden">
        <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
          <div>
            <h2 class="ds-heading text-base">Amostras</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ samples.total || 0 }} registos filtrados.</p>
          </div>
          <QueueListIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
        </div>

        <div v-if="samples.data.length" class="ds-table-shell overflow-x-auto">
          <DataTable class="min-w-[48rem]">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-cell text-left">Amostra</th>
                <th class="ds-table-cell text-left">Âmbito</th>
                <th class="ds-table-cell text-left">Cliente</th>
                <th class="ds-table-cell text-left">Estado</th>
                <th class="ds-table-cell text-left">Recebida</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sample in samples.data" :key="sample.id" class="ds-table-row">
                <td class="ds-table-cell align-top">
                  <Link :href="route('vap_samples.show', sample.id)" class="font-mono text-xs font-bold text-primary-800 hover:underline dark:text-primary-200">{{ sample.code }}</Link>
                  <p class="mt-1 max-w-48 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ sample.name }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ sample.sample_type || 'Tipo N/A' }}</p>
                  <span v-if="isInternalQcSample(sample)" class="ds-chip mt-2">CQ interno · {{ disciplineLabel(sample.quality_control?.discipline) }}</span>
                </td>
                <td class="ds-table-cell align-top">
                  <p class="text-xs font-semibold text-[color:var(--ds-text)]">{{ sample.lab?.name || 'Lab N/A' }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ sample.department?.name || 'Departamento N/A' }}</p>
                  <p v-if="isInternalQcSample(sample)" class="mt-2 text-xs text-[color:var(--ds-text-soft)]">Lote {{ sample.quality_control?.lot || 'N/A' }}</p>
                </td>
                <td class="ds-table-cell align-top text-xs text-[color:var(--ds-text)]">{{ sample.customer?.name || 'N/A' }}</td>
                <td class="ds-table-cell align-top">
                  <span class="ds-chip">
                    <span class="lims-status-dot" :class="statusDotClass(sample.status)" />
                    {{ getStatusLabel(sample.status) }}
                  </span>
                  <p v-if="isInternalQcSample(sample)" class="mt-2 flex items-center gap-2 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                    <span class="lims-status-dot" :class="qcReleaseDotClass(sample.quality_control?.final_decision)" />
                    {{ qcReleaseLabel(sample.quality_control?.final_decision) }}
                  </p>
                </td>
                <td class="ds-table-cell align-top text-xs text-[color:var(--ds-text)]">{{ formatDateTime(sample.received_at) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
        <div v-else class="p-5">
          <div class="ds-empty-state p-6 text-center">
            <QueueListIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
            <p class="mt-2 text-xs text-[color:var(--ds-text-soft)]">Nenhuma amostra encontrada.</p>
          </div>
        </div>

        <div v-if="samples.data.length" class="ds-table-summary flex items-center justify-between gap-3 px-5 py-4">
          <p class="text-xs text-[color:var(--ds-text-soft)]">Mostrando {{ samples.from }}-{{ samples.to }} de {{ samples.total }}</p>
          <div class="flex gap-2">
            <button type="button" class="ds-button ds-button-secondary" :disabled="!samples.prev_page_url" @click="visitPage(samples.prev_page_url)">Anterior</button>
            <button type="button" class="ds-button ds-button-secondary" :disabled="!samples.next_page_url" @click="visitPage(samples.next_page_url)">Próxima</button>
          </div>
        </div>
      </article>

      <article class="ds-panel overflow-hidden">
        <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
          <div>
            <h2 class="ds-heading text-base">Descartes</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ discards.total || 0 }} registos filtrados.</p>
          </div>
          <TrashIcon class="h-5 w-5 text-rose-700 dark:text-rose-300" />
        </div>

        <div v-if="discards.data.length" class="ds-table-shell overflow-x-auto">
          <DataTable class="min-w-[42rem]">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-cell text-left">Amostra</th>
                <th class="ds-table-cell text-left">Método</th>
                <th class="ds-table-cell text-left">Qtd.</th>
                <th class="ds-table-cell text-left">Responsável / âmbito</th>
                <th class="ds-table-cell text-left">Data</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="discard in discards.data" :key="discard.id" class="ds-table-row">
                <td class="ds-table-cell align-top">
                  <p class="font-mono text-xs font-bold text-primary-800 dark:text-primary-200">{{ discard.sample?.code || 'N/A' }}</p>
                  <p class="mt-1 max-w-44 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ discard.sample?.name || 'Amostra removida' }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ discard.sample?.customer?.name || 'Sem cliente' }}</p>
                </td>
                <td class="ds-table-cell align-top text-xs font-semibold text-[color:var(--ds-text)]">{{ discard.discard_method || 'N/A' }}</td>
                <td class="ds-table-cell align-top text-sm font-bold text-rose-700 dark:text-rose-300">{{ discard.qty }}</td>
                <td class="ds-table-cell align-top">
                  <p class="text-xs font-semibold text-[color:var(--ds-text)]">{{ discard.discarded_by?.name || 'N/A' }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ discard.lab?.name || 'Lab N/A' }} · {{ discard.department?.name || 'Dept. N/A' }}</p>
                </td>
                <td class="ds-table-cell align-top text-xs text-[color:var(--ds-text)]">{{ formatDateTime(discard.discarded_at) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
        <div v-else class="p-5">
          <div class="ds-empty-state p-6 text-center">
            <TrashIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
            <p class="mt-2 text-xs text-[color:var(--ds-text-soft)]">Nenhum descarte encontrado.</p>
          </div>
        </div>

        <div v-if="discards.data.length" class="ds-table-summary flex items-center justify-between gap-3 px-5 py-4">
          <p class="text-xs text-[color:var(--ds-text-soft)]">Mostrando {{ discards.from }}-{{ discards.to }} de {{ discards.total }}</p>
          <div class="flex gap-2">
            <button type="button" class="ds-button ds-button-secondary" :disabled="!discards.prev_page_url" @click="visitPage(discards.prev_page_url)">Anterior</button>
            <button type="button" class="ds-button ds-button-secondary" :disabled="!discards.next_page_url" @click="visitPage(discards.next_page_url)">Próxima</button>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import PlanoChart from '@/Components/plano/PlanoChart.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'
import {
  Download as ArrowDownTrayIcon,
  FlaskConical as BeakerIcon,
  CircleCheck as CheckCircleIcon,
  FileDown as DocumentArrowDownIcon,
  Funnel as FunnelIcon,
  Rows3 as QueueListIcon,
  Trash2 as TrashIcon,
} from '@lucide/vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  title: {
    type: String,
    default: 'Relatórios de Amostras',
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  samples: {
    type: Object,
    default: () => ({ data: [] }),
  },
  discards: {
    type: Object,
    default: () => ({ data: [] }),
  },
  internalQualityControl: {
    type: Object,
    default: () => ({
      total: 0,
      in_progress: 0,
      completed: 0,
      waiting_release: 0,
      pending_release_decision: 0,
      released: 0,
      quarantined: 0,
      rejected: 0,
      investigation_required: 0,
      linked_to_normal_flow: 0,
      by_discipline: {},
      by_release_decision: {},
      samples: [],
    }),
  },
  statusBreakdown: {
    type: Array,
    default: () => [],
  },
  sampleTypeBreakdown: {
    type: Array,
    default: () => [],
  },
  discardMethodBreakdown: {
    type: Array,
    default: () => [],
  },
  sampleTimeline: {
    type: Array,
    default: () => [],
  },
  customers: {
    type: Array,
    default: () => [],
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  discardMethods: {
    type: Array,
    default: () => [],
  },
  sampleTypes: {
    type: Array,
    default: () => [],
  },
  generatedAt: {
    type: String,
    default: '',
  },
})

const { isDark } = useTheme()

const statusChartLabels = computed(() => props.statusBreakdown.map((item) => getStatusLabel(item.label)))
const statusChartSeries = computed(() => props.statusBreakdown.map((item) => Number(item.total) || 0))
const statusChartTotal = computed(() => statusChartSeries.value.reduce((total, value) => total + value, 0))
const discardChartTotal = computed(() => props.discardMethodBreakdown.reduce((total, item) => total + (Number(item.total) || 0), 0))
// Sample states carry meaning: their slices wear the status tones.
const statusTones = computed(() => Object.fromEntries(props.statusBreakdown.map((item) => [getStatusLabel(item.label), { POR_INICIAR: 'neutral', EN_PROGRESO: 'accent', EN_PAUSA: 'warn', COMPLETADO: 'ok', CANCELADO: 'bad' }[item.label]]).filter(([, tone]) => tone)))


const statusOptions = [
  { value: 'POR_INICIAR', label: 'Por iniciar' },
  { value: 'EN_PROGRESO', label: 'Em progresso' },
  { value: 'COMPLETADO', label: 'Completado' },
  { value: 'CANCELADO', label: 'Cancelado' },
  { value: 'EN_PAUSA', label: 'Em pausa' },
]

const summaryCards = computed(() => {
  const summary = props.summary || {}

  return [
    { label: 'Amostras', value: summary.total_samples || 0 },
    { label: 'Concluídas', value: summary.completed_samples || 0 },
    { label: 'Por iniciar', value: summary.pending_samples || 0 },
    { label: 'Em análise', value: summary.in_progress_samples || 0 },
    { label: 'Descartes', value: summary.total_discards || 0, note: `Taxa de descarte: ${summary.discard_rate || 0}%` },
    { label: 'Tempo médio', value: `${summary.avg_turnaround_hours || 0} h`, note: 'Da recepção à conclusão' },
    { label: 'CQ interno', value: summary.internal_qc_samples || 0, note: 'Matéria-prima no fluxo normal' },
  ]
})

const sampleScopeFilterOptions = computed(() => [
  { value: '', label: 'Todas as origens' },
  { value: 'internal_qc', label: 'CQ interno / matéria-prima' },
  { value: 'internal', label: 'Amostras internas' },
  { value: 'client', label: 'Cliente / portal' },
])
const qcReleaseFilterOptions = computed(() => [
  { value: '', label: 'Todas as decisões' },
  { value: 'pending', label: 'Sem decisão final' },
  { value: 'released', label: 'Liberada para uso' },
  { value: 'quarantined', label: 'Em quarentena' },
  { value: 'investigation_required', label: 'Investigação requerida' },
  { value: 'rejected', label: 'Rejeitada' },
  { value: 'trend_recorded', label: 'Registada para tendência' },
])
const statusFilterOptions = computed(() => statusOptions)
const sampleTypeOptions = computed(() => props.sampleTypes.map((type) => ({ value: type, label: type })))
const discardMethodOptions = computed(() => props.discardMethods.map((method) => ({ value: method, label: method })))
const customerOptions = computed(() => props.customers.map((customer) => ({ value: String(customer.id), label: customer.name })))
const labOptions = computed(() => props.labs.map((lab) => ({ value: String(lab.id), label: lab.name })))
const departmentOptions = computed(() => props.departments.map((department) => ({ value: String(department.id), label: department.name })))
const internalQc = computed(() => props.internalQualityControl || {})
const internalQcSamples = computed(() => internalQc.value.samples || [])
const internalQcMetrics = computed(() => [
  { label: 'Amostras CQ', value: internalQc.value.total || 0, tone: 'text-emerald-700 dark:text-emerald-300' },
  { label: 'Em análise', value: internalQc.value.in_progress || 0, tone: 'text-primary-800 dark:text-primary-300' },
  { label: 'Sem decisão final', value: internalQc.value.pending_release_decision || 0, tone: 'text-amber-700 dark:text-amber-300' },
  { label: 'Liberadas', value: internalQc.value.released || 0, tone: 'text-emerald-700 dark:text-emerald-300' },
  { label: 'Quarentena', value: internalQc.value.quarantined || 0, tone: 'text-amber-800 dark:text-amber-300' },
  { label: 'Ligadas ao fluxo', value: internalQc.value.linked_to_normal_flow || 0, tone: 'text-primary-800 dark:text-primary-300' },
])
const disciplineBreakdown = computed(() => [
  { label: 'Microbiologia', value: internalQc.value.by_discipline?.microbiology || 0 },
  { label: 'Química', value: internalQc.value.by_discipline?.chemistry || 0 },
  { label: 'Micro + Química', value: internalQc.value.by_discipline?.microbiology_and_chemistry || 0 },
])
const releaseDecisionBreakdown = computed(() => [
  { value: 'pending', label: 'Sem decisão final', total: internalQc.value.by_release_decision?.pending || 0 },
  { value: 'released', label: 'Liberada para uso', total: internalQc.value.by_release_decision?.released || 0 },
  { value: 'quarantined', label: 'Em quarentena', total: internalQc.value.by_release_decision?.quarantined || 0 },
  { value: 'investigation_required', label: 'Investigação requerida', total: internalQc.value.by_release_decision?.investigation_required || 0 },
  { value: 'rejected', label: 'Rejeitada', total: internalQc.value.by_release_decision?.rejected || 0 },
  { value: 'trend_recorded', label: 'Registada para tendência', total: internalQc.value.by_release_decision?.trend_recorded || 0 },
])

const breakdownGroups = computed(() => [
  {
    title: 'Distribuição por estado',
    description: 'Amostras por etapa do ciclo.',
    items: props.statusBreakdown.map((item) => ({
      label: getStatusLabel(item.label),
      value: item.total,
      dotClass: statusDotClass(item.label),
    })),
  },
  {
    title: 'Tipos de amostra',
    description: 'Origem e natureza das amostras.',
    items: props.sampleTypeBreakdown.map((item) => ({
      label: item.label,
      value: item.total,
      dotClass: 'lims-status-dot-instrument',
    })),
  },
  {
    title: 'Métodos de descarte',
    description: 'Saídas por método usado.',
    items: props.discardMethodBreakdown.map((item) => ({
      label: item.label,
      value: item.total,
      dotClass: 'lims-status-dot-critical',
    })),
  },
])

const form = useForm({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  status: props.filters.status || '',
  sample_type: props.filters.sample_type || '',
  sample_scope: props.filters.sample_scope || '',
  qc_release: props.filters.qc_release || '',
  customer_id: props.filters.customer_id || '',
  lab_id: props.filters.lab_id || '',
  department_id: props.filters.department_id || '',
  discard_method: props.filters.discard_method || '',
})

function bindSelection(options, field) {
  return computed({
    get() {
      return options.value.find((option) => String(option.value) === String(form[field])) ?? null
    },
    set(option) {
      form[field] = option?.value ? String(option.value) : ''
    },
  })
}

function buildLocalLoader(options) {
  return (query, setOptions) => {
    const normalizedQuery = String(query || '').trim().toLowerCase()
    const filtered = !normalizedQuery
      ? options.value
      : options.value.filter((option) => option.label.toLowerCase().includes(normalizedQuery))

    setOptions(filtered)
  }
}

const statusSelection = bindSelection(statusFilterOptions, 'status')
const sampleTypeSelection = bindSelection(sampleTypeOptions, 'sample_type')
const sampleScopeSelection = bindSelection(sampleScopeFilterOptions, 'sample_scope')
const qcReleaseSelection = bindSelection(qcReleaseFilterOptions, 'qc_release')
const customerSelection = bindSelection(customerOptions, 'customer_id')
const labSelection = bindSelection(labOptions, 'lab_id')
const departmentSelection = bindSelection(departmentOptions, 'department_id')
const discardMethodSelection = bindSelection(discardMethodOptions, 'discard_method')

const loadStatuses = buildLocalLoader(statusFilterOptions)
const loadSampleTypes = buildLocalLoader(sampleTypeOptions)
const loadSampleScopes = buildLocalLoader(sampleScopeFilterOptions)
const loadQcReleaseStatuses = buildLocalLoader(qcReleaseFilterOptions)
const loadCustomers = buildLocalLoader(customerOptions)
const loadLabs = buildLocalLoader(labOptions)
const loadDepartments = buildLocalLoader(departmentOptions)
const loadDiscardMethods = buildLocalLoader(discardMethodOptions)

const sampleExportUrl = computed(() => buildUrl(route('vap_samples.samples.export'), {
  start_date: form.date_from,
  end_date: form.date_to,
  status: form.status,
  sample_type: form.sample_type,
  sample_scope: form.sample_scope,
  qc_release: form.qc_release,
  customer_id: form.customer_id,
  lab_id: form.lab_id,
  department_id: form.department_id,
}))

const discardExportUrl = computed(() => buildUrl(route('vap_samples.discards.export'), {
  start_date: form.date_from,
  end_date: form.date_to,
  method: form.discard_method,
}))

function applyFilters() {
  router.get(route('vap_samples.reports'), normalizeFilters(), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  form.reset()
  applyFilters()
}

function visitPage(url) {
  if (!url) return

  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  })
}

function normalizeFilters() {
  return Object.fromEntries(
    Object.entries({
      date_from: form.date_from,
      date_to: form.date_to,
      status: form.status,
      sample_type: form.sample_type,
      sample_scope: form.sample_scope,
      qc_release: form.qc_release,
      customer_id: form.customer_id,
      lab_id: form.lab_id,
      department_id: form.department_id,
      discard_method: form.discard_method,
    }).filter(([, value]) => value !== '' && value !== null && value !== undefined)
  )
}

function buildUrl(baseUrl, params) {
  const search = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      search.set(key, value)
    }
  })

  const query = search.toString()

  return query ? `${baseUrl}?${query}` : baseUrl
}

function formatDate(value) {
  if (!value) return '-'

  return new Date(value).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatDateTime(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getStatusLabel(status) {
  return statusOptions.find((option) => option.value === status)?.label || status || 'N/A'
}

function statusDotClass(status) {
  const map = {
    COMPLETADO: 'lims-status-dot-release',
    EN_PROGRESO: 'lims-status-dot-instrument',
    POR_INICIAR: 'lims-status-dot-hold',
    CANCELADO: 'lims-status-dot-critical',
    EN_PAUSA: 'lims-status-dot-hold',
  }

  return map[status] || 'lims-status-dot-instrument'
}

function showInternalQcOnly() {
  form.sample_scope = 'internal_qc'
  applyFilters()
}

function isInternalQcSample(sample) {
  return Boolean(sample?.quality_control?.is_internal_qc)
}

function disciplineLabel(value) {
  const map = {
    microbiology: 'Microbiologia',
    chemistry: 'Química',
    microbiology_and_chemistry: 'Microbiologia + Química',
  }

  return map[value] || value || 'Não definido'
}

function qcPurposeLabel(value) {
  const map = {
    raw_material_release: 'Libertação de matéria-prima',
    supplier_qualification: 'Qualificação de fornecedor',
    process_validation: 'Validação de processo',
    stability_follow_up: 'Seguimento de estabilidade',
    investigation: 'Investigação',
    other: 'Outro',
  }

  return map[value] || value || 'Não definido'
}

function qcDecisionLabel(value) {
  const map = {
    hold_until_release: 'Reter até libertação',
    release_if_compliant: 'Liberar se conforme',
    investigate_before_release: 'Investigar antes de liberar',
    trend_only: 'Apenas tendência',
  }

  return map[value] || value || 'Não definido'
}

function qcReleaseLabel(value) {
  const map = {
    pending: 'Sem decisão final',
    released: 'Liberada para uso',
    quarantined: 'Em quarentena',
    investigation_required: 'Investigação requerida',
    rejected: 'Rejeitada',
    trend_recorded: 'Registada para tendência',
  }

  return map[value || 'pending'] || value || 'Sem decisão final'
}

function qcReleaseDotClass(value) {
  const map = {
    released: 'lims-status-dot-release',
    quarantined: 'lims-status-dot-critical',
    investigation_required: 'lims-status-dot-hold',
    rejected: 'lims-status-dot-critical',
    trend_recorded: 'lims-status-dot-instrument',
    pending: 'lims-status-dot-hold',
  }

  return map[value] || 'lims-status-dot-hold'
}
</script>

<style scoped>
:deep(.apexcharts-tooltip),
:deep(.apexcharts-menu) {
  border: 1px solid var(--ds-border) !important;
  border-radius: var(--ds-radius-control) !important;
  background: var(--ds-panel) !important;
  color: var(--ds-text) !important;
  box-shadow: var(--ds-shadow-panel) !important;
}

:deep(.apexcharts-tooltip-title) {
  border-bottom: 1px solid var(--ds-border) !important;
  background: var(--ds-panel-subtle) !important;
  color: var(--ds-text) !important;
}
</style>
