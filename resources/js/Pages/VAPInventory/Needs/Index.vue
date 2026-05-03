<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700">Procurement workflow</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Necessidades de laboratório e departamento</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Consolide requisições de laboratórios, submeta para aprovação e transforme apenas necessidades aprovadas em pedidos de compra rastreáveis.
          </p>
        </div>
        <Link :href="route('vap-inventory.needs.create')" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
          Nova necessidade
        </Link>
      </div>

      <div class="mt-6 grid gap-4 md:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Total</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.total }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Submetidas</div>
          <div class="mt-2 text-2xl font-semibold text-amber-700">{{ stats.submitted }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Aprovadas</div>
          <div class="mt-2 text-2xl font-semibold text-emerald-700">{{ stats.approved }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Em aquisição</div>
          <div class="mt-2 text-2xl font-semibold text-cyan-700">{{ stats.ordered }}</div>
        </article>
      </div>

      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
          <div class="text-sm text-amber-800">Aprovadas à espera de pedido</div>
          <div class="mt-2 text-2xl font-semibold text-amber-900">{{ stats.awaiting_order }}</div>
        </article>
        <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
          <div class="text-sm text-rose-800">Procurement em atraso</div>
          <div class="mt-2 text-2xl font-semibold text-rose-900">{{ stats.overdue_procurement }}</div>
        </article>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Estado das necessidades</h2>
            <p class="mt-1 text-sm text-slate-500">
              Panorama rápido entre submissão, aprovação, aquisição e backlog sem pedido.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Registos monitorizados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ statusOverviewTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="statusOverviewChartOptions" :series="statusOverviewChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Prontidão da fila</h2>
              <p class="mt-1 text-sm text-slate-500">Leitura do risco de fornecedor dentro da fila de procurement.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-sm font-medium text-cyan-700">
              {{ queueReadinessTotal }} necessidades
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="queueReadinessChartOptions" :series="queueReadinessChartSeries" />
          </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Pressão de procurement</h2>
              <p class="mt-1 text-sm text-slate-500">Tamanho da fila e concentração de necessidades urgentes ou em atraso.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="procurementPressureChartOptions" :series="procurementPressureChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="grid gap-4 md:grid-cols-4">
        <input v-model="localFilters.search" type="search" placeholder="Pesquisar por referência, laboratório ou justificação" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
        <select v-model="localFilters.status" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
          <option value="">Todos os estados</option>
          <option value="submitted">Submetida</option>
          <option value="approved">Aprovada</option>
          <option value="rejected">Rejeitada</option>
          <option value="ordered">Convertida em pedido</option>
        </select>
        <select v-model="localFilters.department_id" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
          <option value="">Todos os departamentos</option>
          <option v-for="department in departments" :key="department.id" :value="department.id">
            {{ department.name }}
          </option>
        </select>
        <div class="flex gap-2">
          <button type="button" class="flex-1 rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="resetFilters">
            Limpar
          </button>
          <button type="button" class="flex-1 rounded-2xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800" @click="applyFilters">
            Filtrar
          </button>
        </div>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="mb-5 flex items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Fila de procurement</h2>
          <p class="mt-1 text-sm text-slate-500">Necessidades já aprovadas e ainda sem pedido de compra associado.</p>
        </div>
      </div>

      <div v-if="procurementQueue?.length" class="mb-8 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="need in procurementQueue"
          :key="`queue-${need.id}`"
          class="rounded-2xl border p-4"
          :class="queueCardClass(need)"
        >
          <div class="flex items-center justify-between gap-3">
            <span class="rounded-full bg-white/70 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-700">{{ need.reference }}</span>
            <span class="text-xs font-medium">{{ queueUrgencyLabel(need) }}</span>
          </div>
          <div class="mt-3 flex flex-wrap gap-2">
            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-semibold', readinessClass(need.supplier_readiness)]">
              {{ readinessLabel(need.supplier_readiness) }}
            </span>
          </div>
          <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ need.department?.name }}<span v-if="need.lab"> · {{ need.lab.name }}</span></h3>
          <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ need.justification || 'Sem justificação adicional.' }}</p>
          <div class="mt-3 space-y-1 text-xs text-slate-600">
            <div>Itens: <span class="font-semibold text-slate-900">{{ need.items_count }}</span></div>
            <div>Necessário até: <span class="font-semibold text-slate-900">{{ formatDate(need.needed_by_date) }}</span></div>
            <div>Solicitante: <span class="font-semibold text-slate-900">{{ need.requested_by?.name || '—' }}</span></div>
          </div>
          <div class="mt-3 text-xs text-slate-600">
            <div v-if="need.supplier_summary?.blocked_supplier_count">Fornecedores bloqueados: <span class="font-semibold text-rose-700">{{ need.supplier_summary.blocked_supplier_count }}</span></div>
            <div v-if="need.supplier_summary?.missing_supplier_count">Itens sem fornecedor: <span class="font-semibold text-amber-700">{{ need.supplier_summary.missing_supplier_count }}</span></div>
            <div v-if="need.supplier_summary?.unassessed_supplier_count">Sem avaliação: <span class="font-semibold text-amber-700">{{ need.supplier_summary.unassessed_supplier_count }}</span></div>
            <div v-if="need.supplier_summary?.conditional_supplier_count">Acompanhamento reforçado: <span class="font-semibold text-cyan-700">{{ need.supplier_summary.conditional_supplier_count }}</span></div>
          </div>
          <Link :href="route('vap-inventory.needs.show', need.id)" class="mt-4 inline-flex items-center text-sm font-semibold text-cyan-700 hover:text-cyan-800">
            Abrir necessidade
          </Link>
        </article>
      </div>

      <div v-if="needs.data.length" class="space-y-4">
        <article v-for="need in needs.data" :key="need.id" class="rounded-2xl border border-slate-200 p-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
              <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ need.reference }}</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(need.status)">{{ formatStatus(need.status) }}</span>
                <span v-if="need.inventory_order" class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700">
                  {{ need.inventory_order.reference }}
                </span>
              </div>
              <h2 class="text-lg font-semibold text-slate-900">{{ need.department?.name }}<span v-if="need.lab"> · {{ need.lab.name }}</span></h2>
              <p class="text-sm leading-6 text-slate-600">{{ need.justification || 'Sem justificação adicional.' }}</p>
              <div class="grid gap-2 text-sm text-slate-500 md:grid-cols-3">
                <div>Solicitante: {{ need.requested_by?.name || '—' }}</div>
                <div>Itens: {{ need.items_count }}</div>
                <div>Necessário até: {{ formatDate(need.needed_by_date) }}</div>
              </div>
            </div>
            <Link :href="route('vap-inventory.needs.show', need.id)" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Abrir detalhe
            </Link>
          </div>
        </article>
      </div>
      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-500">
        Ainda não existem necessidades registadas para os filtros atuais.
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'

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
      borderRadius: 10,
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
      borderRadius: 10,
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

const statusClass = (status) => ({
  'bg-amber-50 text-amber-700': status === 'submitted',
  'bg-emerald-50 text-emerald-700': status === 'approved' || status === 'fulfilled',
  'bg-rose-50 text-rose-700': status === 'rejected',
  'bg-cyan-50 text-cyan-700': status === 'ordered' || status === 'partially_fulfilled',
  'bg-slate-100 text-slate-600': !['submitted', 'approved', 'rejected', 'ordered', 'partially_fulfilled', 'fulfilled'].includes(status),
})

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

const queueCardClass = (need) => {
  const label = queueUrgencyLabel(need)

  if (label === 'Em atraso') {
    return 'border-rose-200 bg-rose-50'
  }

  if (label === 'Urgente') {
    return 'border-amber-200 bg-amber-50'
  }

  if (label === 'Próximo') {
    return 'border-cyan-200 bg-cyan-50'
  }

  return 'border-slate-200 bg-slate-50'
}

const readinessLabel = (value) => ({
  ready: 'Pronta para compra',
  attention: 'Exige acompanhamento',
  incomplete: 'Dados de fornecedor incompletos',
  blocked: 'Bloqueada por fornecedor',
}[value] ?? 'Sem avaliação')

const readinessClass = (value) => ({
  ready: 'bg-emerald-100 text-emerald-800',
  attention: 'bg-cyan-100 text-cyan-800',
  incomplete: 'bg-amber-100 text-amber-800',
  blocked: 'bg-rose-100 text-rose-800',
}[value] ?? 'bg-slate-100 text-slate-700')
</script>
