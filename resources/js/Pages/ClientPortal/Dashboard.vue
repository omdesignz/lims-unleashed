<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="grid gap-6 bg-[radial-gradient(circle_at_top_right,_rgba(14,116,144,0.18),_transparent_40%),linear-gradient(135deg,#0f172a,#1e293b_55%,#0f766e)] px-6 py-8 text-white md:grid-cols-[1.3fr_0.7fr] md:px-8">
        <div class="space-y-4">
          <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100">
            Portal do cliente
          </span>
          <div class="space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">
              {{ auth?.user?.name || 'Cliente' }}
            </h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-200">
              Acompanhe documentos, acompanhe o estado dos pedidos e abra novas solicitações de análise, colheita e apoio operacional sem sair do portal.
            </p>
          </div>
          <div class="flex flex-wrap gap-3 text-sm text-slate-100">
            <span v-if="auth?.user?.address" class="rounded-full bg-white/10 px-3 py-1.5">
              {{ auth.user.address }}
            </span>
            <span v-if="auth?.user?.email" class="rounded-full bg-white/10 px-3 py-1.5">
              {{ auth.user.email }}
            </span>
            <span v-if="auth?.user?.primary_phone" class="rounded-full bg-white/10 px-3 py-1.5">
              {{ auth.user.primary_phone }}
            </span>
          </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-1">
          <Link
            :href="route('portal.requests.index', { request_type: 'analysis_request', new: 1 })"
            class="rounded-2xl border border-white/15 bg-white/10 p-4 transition hover:bg-white/15"
          >
            <div class="text-sm font-semibold">Nova solicitação de análise</div>
            <div class="mt-1 text-xs text-slate-200">Perfis analíticos, matriz, prioridade e recolha associada.</div>
          </Link>
          <Link
            :href="route('portal.requests.index', { request_type: 'collection_request', new: 1 })"
            class="rounded-2xl border border-white/15 bg-white/10 p-4 transition hover:bg-white/15"
          >
            <div class="text-sm font-semibold">Nova solicitação de colheita</div>
            <div class="mt-1 text-xs text-slate-200">Local, janela horária, contacto no local e itens a recolher.</div>
          </Link>
        </div>
      </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="card in metricCards"
        :key="card.label"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
        <p class="mt-2 text-xs text-slate-500">{{ card.caption }}</p>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Ritmo de pedidos</h2>
            <p class="mt-1 text-sm text-slate-500">
              Evolução dos pedidos de análise, colheita e apoio nos últimos 6 meses.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total monitorizado</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ requestTrendTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart
            type="line"
            height="320"
            :options="requestTrendChartOptions"
            :series="requestTrendChartSeries"
          />
        </div>
      </article>

      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Estado do atendimento</h2>
            <p class="mt-1 text-sm text-slate-500">Onde estão os seus pedidos neste momento.</p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pedidos</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ requestStatusTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart
            type="donut"
            height="320"
            :options="requestStatusChartOptions"
            :series="requestStatusChartSeries"
          />
        </div>
      </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Documentos e ativos disponíveis</h2>
          <p class="mt-1 text-sm text-slate-500">
            Visibilidade imediata sobre o que já está pronto para consulta dentro do portal.
          </p>
        </div>
        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Itens publicados</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ assetVisibilityTotal }}</p>
        </div>
      </div>

      <div class="mt-6">
        <apexchart
          type="bar"
          height="300"
          :options="assetVisibilityChartOptions"
          :series="assetVisibilityChartSeries"
        />
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Serviços disponíveis</h2>
            <p class="mt-1 text-sm text-slate-500">Abra pedidos estruturados para os serviços mais usados.</p>
          </div>
          <Link
            :href="route('portal.services')"
            class="text-sm font-medium text-cyan-700 hover:text-cyan-800"
          >
            Ver catálogo
          </Link>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <Link
            v-for="service in services"
            :key="service.type"
            :href="route('portal.requests.index', { request_type: service.type, new: 1, title: service.title })"
            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-cyan-300 hover:bg-cyan-50"
          >
            <div class="flex items-center justify-between gap-4">
              <div>
                <h3 class="font-semibold text-slate-900">{{ service.title }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ service.description }}</p>
              </div>
              <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700">
                Novo
              </span>
            </div>
          </Link>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Solicitações recentes</h2>
            <p class="mt-1 text-sm text-slate-500">Últimas interações do seu armazém com a equipa do laboratório.</p>
          </div>
          <Link
            :href="route('portal.requests.index')"
            class="text-sm font-medium text-cyan-700 hover:text-cyan-800"
          >
            Gerir pedidos
          </Link>
        </div>

        <div v-if="recentRequestItems.length" class="mt-6 space-y-3">
          <article
            v-for="request in recentRequestItems"
            :key="request.id"
            class="rounded-2xl border border-slate-200 p-4"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {{ request.reference || `REQ-${request.id}` }}
                </p>
                <h3 class="mt-1 font-semibold text-slate-900">{{ request.title || 'Solicitação' }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ request.description }}</p>
              </div>
              <span :class="statusBadgeClass(request.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">
                {{ statusLabel(request.status) }}
              </span>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
              <span>{{ typeLabel(request.request_type) }}</span>
              <span v-if="request.priority">Prioridade: {{ priorityLabel(request.priority) }}</span>
              <span>{{ formatDate(request.submitted_at || request.created_at) }}</span>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
          Ainda não há solicitações registadas para este armazém.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layouts/PortalLayout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineOptions({
  layout: Layout,
})

const props = defineProps({
  auth: Object,
  stats: Object,
  charts: {
    type: Object,
    default: () => ({}),
  },
  services: {
    type: Array,
    default: () => [],
  },
  recentRequests: {
    type: [Array, Object],
    default: () => [],
  },
})

const recentRequestItems = computed(() => {
  if (Array.isArray(props.recentRequests)) {
    return props.recentRequests
  }

  return props.recentRequests?.data || []
})

const portalCharts = computed(() => props.charts || {})

const metricCards = computed(() => [
  {
    label: 'Pedidos em aberto',
    value: props.stats?.open_requests ?? 0,
    caption: 'Solicitações pendentes ou em curso.',
  },
  {
    label: 'Pedidos de análise',
    value: props.stats?.analysis_requests ?? 0,
    caption: 'Pedidos estruturados para novas análises laboratoriais.',
  },
  {
    label: 'Pedidos de colheita',
    value: props.stats?.collection_requests ?? 0,
    caption: 'Agendamentos e recolhas pendentes para o seu local.',
  },
  {
    label: 'Certificados emitidos',
    value: props.stats?.qualitycertificates ?? 0,
    caption: 'Certificados disponíveis para consulta no portal.',
  },
])

const requestTrendChartSeries = computed(() => portalCharts.value.request_trend?.series || [])
const requestTrendTotal = computed(() => requestTrendChartSeries.value.reduce(
  (total, series) => total + (series?.data || []).reduce((sum, value) => sum + value, 0),
  0,
))

const requestStatusChartSeries = computed(() => portalCharts.value.request_status?.series || [])
const requestStatusTotal = computed(() => requestStatusChartSeries.value.reduce((sum, value) => sum + value, 0))

const assetVisibilityChartSeries = computed(() => [
  {
    name: 'Disponível',
    data: portalCharts.value.asset_visibility?.series || [],
  },
])
const assetVisibilityTotal = computed(() => assetVisibilityChartSeries.value[0]?.data?.reduce((sum, value) => sum + value, 0) || 0)

const requestTrendChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'inherit',
  },
  colors: ['#0891b2', '#0f766e', '#f59e0b'],
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  markers: {
    size: 4,
    strokeWidth: 0,
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: portalCharts.value.request_trend?.categories || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: '#64748b' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: '#64748b' },
    },
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
    labels: { colors: '#334155' },
  },
  tooltip: {
    shared: true,
    intersect: false,
  },
}))

const requestStatusChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: portalCharts.value.request_status?.labels || [],
  colors: ['#f59e0b', '#0ea5e9', '#10b981', '#f43f5e'],
  legend: {
    position: 'bottom',
    labels: { colors: '#334155' },
  },
  stroke: {
    colors: ['#ffffff'],
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Pedidos',
            formatter: () => `${requestStatusTotal.value}`,
          },
        },
      },
    },
  },
}))

const assetVisibilityChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: portalCharts.value.asset_visibility?.labels || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: '#64748b' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: '#64748b' },
    },
  },
  plotOptions: {
    bar: {
      borderRadius: 10,
      columnWidth: '52%',
    },
  },
  tooltip: {
    y: {
      formatter: (value) => `${value} disponível`,
    },
  },
  legend: { show: false },
}))

const formatDate = (value) => {
  if (!value) {
    return 'Sem data'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

const statusLabel = (status) => ({
  pending: 'Pendente',
  in_progress: 'Em tratamento',
  completed: 'Concluída',
  cancelled: 'Cancelada',
}[status] || 'Pendente')

const priorityLabel = (priority) => ({
  low: 'Baixa',
  normal: 'Normal',
  high: 'Alta',
}[priority] || 'Normal')

const typeLabel = (type) => ({
  analysis_request: 'Análise',
  collection_request: 'Colheita',
  certificate_support: 'Certificados',
  document_request: 'Documentos',
  billing_support: 'Faturação',
  general_support: 'Suporte geral',
}[type] || 'Serviço')

const statusBadgeClass = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  in_progress: 'bg-sky-100 text-sky-800',
  completed: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-rose-100 text-rose-800',
}[status] || 'bg-slate-100 text-slate-700')
</script>
