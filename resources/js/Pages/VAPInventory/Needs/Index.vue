<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Procurement control</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument" />
              Necessidades de laboratório
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">Necessidades de laboratório e departamento</h1>
          <p class="ds-copy mt-2 text-sm">
            Consolide requisições por laboratório, acompanhe aprovação, bloqueios de fornecedor e conversão para pedidos de compra rastreáveis.
          </p>
        </div>

        <Link :href="route('vap-inventory.needs.create')" class="ds-button ds-button-primary shrink-0">
          <PlusIcon class="h-4 w-4" />
          Nova necessidade
        </Link>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in statsCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-2xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="ds-panel overflow-hidden">
        <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
          <div class="flex items-start gap-3">
            <ChartBarSquareIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Estado das necessidades</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                Submissão, aprovação, aquisição e backlog sem pedido.
              </p>
            </div>
          </div>
          <span class="ds-chip">{{ statusOverviewTotal }} registos</span>
        </div>

        <div class="p-4">
          <apexchart type="bar" height="300" :options="statusOverviewChartOptions" :series="statusOverviewChartSeries" />
        </div>
      </article>

      <div class="grid gap-4">
        <article class="ds-panel overflow-hidden">
          <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
            <div>
              <h2 class="ds-heading text-base">Prontidão da fila</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Risco de fornecedor dentro da fila.</p>
            </div>
            <span class="ds-chip">
              {{ queueReadinessTotal }} necessidades
            </span>
          </div>

          <div class="p-4">
            <apexchart type="donut" height="300" :options="queueReadinessChartOptions" :series="queueReadinessChartSeries" />
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Pressão de procurement</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Fila, urgência, atraso e prontidão operacional.</p>
          </div>

          <div class="p-4">
            <apexchart type="bar" height="250" :options="procurementPressureChartOptions" :series="procurementPressureChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <FunnelIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
          <div>
            <h2 class="ds-heading text-base">Filtros de necessidades</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Refine por referência, estado e departamento.</p>
          </div>
        </div>
        <span class="ds-chip">{{ needs.total || 0 }} registos</span>
      </div>

      <div class="grid gap-4 p-5 md:grid-cols-4">
        <BaseInput v-model="localFilters.search" type="search" placeholder="Pesquisar por referência, laboratório ou justificação" />
        <BaseSelect v-model="localFilters.status">
          <option value="">Todos os estados</option>
          <option value="submitted">Submetida</option>
          <option value="approved">Aprovada</option>
          <option value="rejected">Rejeitada</option>
          <option value="ordered">Convertida em pedido</option>
        </BaseSelect>
        <BaseSelect v-model="localFilters.department_id">
          <option value="">Todos os departamentos</option>
          <option v-for="department in departments" :key="department.id" :value="department.id">
            {{ department.name }}
          </option>
        </BaseSelect>
        <div class="flex gap-2">
          <button type="button" class="ds-button ds-button-secondary flex-1" @click="resetFilters">
            Limpar
          </button>
          <button type="button" class="ds-button ds-button-primary flex-1" @click="applyFilters">
            <FunnelIcon class="h-4 w-4" />
            Filtrar
          </button>
        </div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
        <div class="flex items-start gap-3">
          <QueueListIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
          <div>
            <h2 class="ds-heading text-base">Fila de procurement</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Necessidades aprovadas ainda sem pedido de compra associado.</p>
          </div>
        </div>
        <span class="ds-chip">{{ procurementQueue?.length || 0 }} em fila</span>
      </div>

      <div v-if="procurementQueue?.length" class="grid gap-3 border-b border-[color:var(--ds-border)] p-5 md:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="need in procurementQueue"
          :key="`queue-${need.id}`"
          class="ds-card border-l-4 p-4"
          :class="queueCardBorderClass(need)"
        >
          <div class="flex items-center justify-between gap-3">
            <span class="font-mono text-xs font-bold text-primary-800 dark:text-primary-200">{{ need.reference }}</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="queueUrgencyDotClass(need)" />
              {{ queueUrgencyLabel(need) }}
            </span>
          </div>
          <div class="mt-3 flex flex-wrap gap-2">
            <span class="ds-chip">
              <span class="lims-status-dot" :class="readinessDotClass(need.supplier_readiness)" />
              {{ readinessLabel(need.supplier_readiness) }}
            </span>
          </div>
          <h3 class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">{{ need.department?.name }}<span v-if="need.lab"> · {{ need.lab.name }}</span></h3>
          <p class="mt-2 line-clamp-3 text-sm text-[color:var(--ds-text-muted)]">{{ need.justification || 'Sem justificação adicional.' }}</p>
          <div class="mt-3 space-y-1 text-xs text-[color:var(--ds-text-soft)]">
            <div>Itens: <span class="font-bold text-[color:var(--ds-text)]">{{ need.items_count }}</span></div>
            <div>Necessário até: <span class="font-bold text-[color:var(--ds-text)]">{{ formatDate(need.needed_by_date) }}</span></div>
            <div>Solicitante: <span class="font-bold text-[color:var(--ds-text)]">{{ need.requested_by?.name || '—' }}</span></div>
          </div>
          <div class="mt-3 space-y-1 text-xs text-[color:var(--ds-text-soft)]">
            <div v-if="need.supplier_summary?.blocked_supplier_count">Fornecedores bloqueados: <span class="font-semibold text-rose-700">{{ need.supplier_summary.blocked_supplier_count }}</span></div>
            <div v-if="need.supplier_summary?.missing_supplier_count">Itens sem fornecedor: <span class="font-semibold text-amber-700">{{ need.supplier_summary.missing_supplier_count }}</span></div>
            <div v-if="need.supplier_summary?.unassessed_supplier_count">Sem avaliação: <span class="font-semibold text-amber-700">{{ need.supplier_summary.unassessed_supplier_count }}</span></div>
            <div v-if="need.supplier_summary?.conditional_supplier_count">Acompanhamento reforçado: <span class="font-semibold text-cyan-700">{{ need.supplier_summary.conditional_supplier_count }}</span></div>
          </div>
          <Link :href="route('vap-inventory.needs.show', need.id)" class="ds-button ds-button-secondary mt-4 w-full">
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
            Abrir necessidade
          </Link>
        </article>
      </div>

      <div v-if="needs.data.length" class="ds-table-shell overflow-x-auto">
        <DataTable class="min-w-[58rem]">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-cell text-left">Necessidade</th>
              <th class="ds-table-cell text-left">Escopo</th>
              <th class="ds-table-cell text-left">Estado</th>
              <th class="ds-table-cell text-left">Solicitante</th>
              <th class="ds-table-cell text-left">Prazo</th>
              <th class="ds-table-cell text-left">Ação</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="need in needs.data" :key="need.id" class="ds-table-row">
              <td class="ds-table-cell align-top">
                <p class="font-mono text-xs font-bold text-primary-800 dark:text-primary-200">{{ need.reference }}</p>
                <p class="mt-1 max-w-72 text-sm font-bold text-[color:var(--ds-text)]">{{ need.justification || 'Sem justificação adicional.' }}</p>
                <span v-if="need.inventory_order" class="ds-chip mt-2">{{ need.inventory_order.reference }}</span>
              </td>
              <td class="ds-table-cell align-top">
                <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ need.department?.name || 'Departamento N/A' }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ need.lab?.name || 'Laboratório não definido' }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ need.items_count }} itens</p>
              </td>
              <td class="ds-table-cell align-top">
                <span class="ds-chip">
                  <span class="lims-status-dot" :class="statusDotClass(need.status)" />
                  {{ formatStatus(need.status) }}
                </span>
              </td>
              <td class="ds-table-cell align-top text-xs text-[color:var(--ds-text)]">{{ need.requested_by?.name || '—' }}</td>
              <td class="ds-table-cell align-top text-xs text-[color:var(--ds-text)]">{{ formatDate(need.needed_by_date) }}</td>
              <td class="ds-table-cell align-top">
                <Link :href="route('vap-inventory.needs.show', need.id)" class="ds-table-action">
                  Abrir detalhe
                </Link>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>
      <div v-else class="p-5">
        <div class="ds-empty-state p-6 text-center">
          <QueueListIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
          <p class="mt-2 text-xs text-[color:var(--ds-text-soft)]">Ainda não existem necessidades registadas para os filtros atuais.</p>
        </div>
      </div>

      <div v-if="needs.data.length" class="ds-table-summary px-5 py-4">
        <p class="text-xs text-[color:var(--ds-text-soft)]">Mostrando {{ needs.from }}-{{ needs.to }} de {{ needs.total }}</p>
        <div class="flex gap-2">
          <Link v-if="needs.prev_page_url" :href="needs.prev_page_url" preserve-scroll preserve-state class="ds-button ds-button-secondary">Anterior</Link>
          <span v-else class="ds-button ds-button-secondary opacity-50">Anterior</span>
          <Link v-if="needs.next_page_url" :href="needs.next_page_url" preserve-scroll preserve-state class="ds-button ds-button-secondary">Próxima</Link>
          <span v-else class="ds-button ds-button-secondary opacity-50">Próxima</span>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import {
  ArrowTopRightOnSquareIcon,
  ChartBarSquareIcon,
  FunnelIcon,
  PlusIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })

const props = defineProps({
  needs: Object,
  departments: Array,
  filters: Object,
  stats: Object,
  procurementQueue: Array,
  charts: {
    type: Object,
    default: () => ({})
  },
})

const statsCards = computed(() => {
  const stats = props.stats || {}

  return [
    {
      label: 'Total',
      value: stats.total || 0,
      dotClass: 'lims-status-dot-instrument',
      valueClass: 'text-[color:var(--ds-text)]',
    },
    {
      label: 'Submetidas',
      value: stats.submitted || 0,
      dotClass: 'lims-status-dot-hold',
      valueClass: 'text-amber-700 dark:text-amber-300',
    },
    {
      label: 'Aprovadas',
      value: stats.approved || 0,
      dotClass: 'lims-status-dot-release',
      valueClass: 'text-emerald-700 dark:text-emerald-300',
    },
    {
      label: 'Em aquisição',
      value: stats.ordered || 0,
      dotClass: 'lims-status-dot-instrument',
      valueClass: 'text-primary-800 dark:text-primary-200',
    },
    {
      label: 'À espera de pedido',
      value: stats.awaiting_order || 0,
      dotClass: 'lims-status-dot-hold',
      valueClass: 'text-amber-700 dark:text-amber-300',
    },
    {
      label: 'Em atraso',
      value: stats.overdue_procurement || 0,
      dotClass: 'lims-status-dot-critical',
      valueClass: 'text-rose-700 dark:text-rose-300',
    },
  ]
})

const statusOverviewChartSeries = computed(() => [
  {
    name: 'Necessidades',
    data: props.charts?.status_overview?.series || []
  }
])

const statusOverviewTotal = computed(() =>
  (props.charts?.status_overview?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
)

const queueReadinessChartSeries = computed(() => props.charts?.queue_readiness?.series || [])

const queueReadinessTotal = computed(() =>
  queueReadinessChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0)
)

const procurementPressureChartSeries = computed(() => [
  {
    name: 'Fila',
    data: props.charts?.procurement_pressure?.series || []
  }
])

const statusOverviewChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  plotOptions: {
    bar: {
      borderRadius: 6,
      distributed: true,
      columnWidth: '48%'
    }
  },
  colors: ['#f59e0b', '#16a34a', '#0891b2', '#7c3aed'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.status_overview?.labels || [],
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const queueReadinessChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  labels: props.charts?.queue_readiness?.labels || [],
  colors: ['#16a34a', '#0891b2', '#f59e0b', '#dc2626'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`
  },
  legend: {
    position: 'bottom'
  },
  stroke: {
    colors: ['#ffffff']
  }
}))

const procurementPressureChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  plotOptions: {
    bar: {
      borderRadius: 6,
      distributed: true,
      columnWidth: '52%'
    }
  },
  colors: ['#334155', '#dc2626', '#f59e0b', '#0f766e'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.procurement_pressure?.labels || [],
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const localFilters = reactive({
  search: props.filters?.search ?? '',
  status: props.filters?.status ?? '',
  department_id: props.filters?.department_id ?? '',
})

const applyFilters = () => {
  router.get(route('vap-inventory.needs.index'), localFilters, { preserveState: true, preserveScroll: true })
}

const resetFilters = () => {
  localFilters.search = ''
  localFilters.status = ''
  localFilters.department_id = ''
  applyFilters()
}

const formatStatus = (status) => ({
  draft: 'Rascunho',
  submitted: 'Submetida',
  approved: 'Aprovada',
  rejected: 'Rejeitada',
  ordered: 'Convertida em pedido',
  partially_fulfilled: 'Parcialmente satisfeita',
  fulfilled: 'Satisfeita',
}[status] ?? status)

const statusDotClass = (status) => ({
  draft: 'lims-status-dot-instrument',
  submitted: 'lims-status-dot-hold',
  approved: 'lims-status-dot-release',
  rejected: 'lims-status-dot-critical',
  ordered: 'lims-status-dot-instrument',
  partially_fulfilled: 'lims-status-dot-instrument',
  fulfilled: 'lims-status-dot-release',
}[status] ?? 'lims-status-dot-instrument')

const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'

const queueUrgencyLabel = (need) => {
  if (!need?.needed_by_date) {
    return 'Sem prazo'
  }

  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const neededDate = new Date(need.needed_by_date)
  neededDate.setHours(0, 0, 0, 0)
  const diffDays = Math.round((neededDate.getTime() - today.getTime()) / 86400000)

  if (diffDays < 0) {
    return 'Em atraso'
  }

  if (diffDays <= 3) {
    return 'Urgente'
  }

  if (diffDays <= 10) {
    return 'Próximo'
  }

  return 'Planeado'
}

const queueUrgencyDotClass = (need) => {
  const label = queueUrgencyLabel(need)

  if (label === 'Em atraso') {
    return 'lims-status-dot-critical'
  }

  if (label === 'Urgente') {
    return 'lims-status-dot-hold'
  }

  if (label === 'Próximo') {
    return 'lims-status-dot-instrument'
  }

  return 'lims-status-dot-release'
}

const queueCardBorderClass = (need) => ({
  'Em atraso': 'border-rose-500',
  Urgente: 'border-amber-500',
  Próximo: 'border-primary-500',
  Planeado: 'border-emerald-500',
  'Sem prazo': 'border-primary-500',
}[queueUrgencyLabel(need)] ?? 'border-primary-500')

const readinessLabel = (value) => ({
  ready: 'Pronta para compra',
  attention: 'Exige acompanhamento',
  incomplete: 'Dados de fornecedor incompletos',
  blocked: 'Bloqueada por fornecedor',
}[value] ?? 'Sem avaliação')

const readinessDotClass = (value) => ({
  ready: 'lims-status-dot-release',
  attention: 'lims-status-dot-instrument',
  incomplete: 'lims-status-dot-hold',
  blocked: 'lims-status-dot-critical',
}[value] ?? 'lims-status-dot-instrument')
</script>
