<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="relative isolate p-6">
        <div class="absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-r from-primary-600/15 via-sky-400/10 to-emerald-400/10 dark:from-primary-500/20 dark:via-sky-500/10 dark:to-emerald-500/10"></div>
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-700 dark:text-primary-300">ISO 17025 · Melhoria contínua</p>
        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Avaliações de processos e serviços</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
              Acompanhe a satisfação interna e de clientes para identificar oportunidades de melhoria, risco operacional e evidências de feedback.
            </p>
          </div>
          <Link
            :href="route('rating.create', { rateableType: 'service' })"
            class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
          >
            Avaliar serviço geral
          </Link>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ metric.label }}</p>
        <p class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">{{ metric.value }}</p>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Tendência de feedback</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Volume mensal de avaliações registadas.</p>
        <ChartWrapper class="mt-4" type="area" height="300" :series="monthlySeries" :options="monthlyOptions" />
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Canal</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Origem interna ou portal do cliente.</p>
        <ChartWrapper class="mt-4" type="donut" height="300" :series="channelSeries" :options="channelOptions" />
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Processos avaliados</h2>
        <ChartWrapper class="mt-4" type="bar" height="280" :series="typeSeries" :options="typeOptions" />
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Distribuição de pontuações</h2>
        <ChartWrapper class="mt-4" type="bar" height="280" :series="scoreSeries" :options="scoreOptions" />
      </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h2 class="text-base font-semibold text-slate-950 dark:text-white">Registos recentes</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-950/60">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Canal</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pontuação média</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Comentário</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="rating in ratings.data" :key="rating.id" class="text-sm">
              <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ rating.rateable_type }} #{{ rating.rateable_id }}</td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ rating.channel || 'internal' }}</td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ averageRating(rating.criteria) }}</td>
              <td class="max-w-md px-6 py-4 text-slate-600 dark:text-slate-300">{{ rating.review || 'Sem comentário' }}</td>
              <td class="px-6 py-4 text-slate-500">{{ formatDate(rating.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="ratings.links" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <Pagination :links="ratings.links" />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link } from '@inertiajs/vue3'
import ChartWrapper from '@/Components/apex-chart/ChartWrapper.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  ratings: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const metrics = computed(() => [
  { label: 'Total', value: props.stats.total },
  { label: 'Portal', value: props.stats.portal },
  { label: 'Interno', value: props.stats.internal },
  { label: 'Média', value: props.stats.average },
])

const baseOptions = {
  chart: { foreColor: '#64748b' },
  grid: { borderColor: '#e2e8f0' },
  tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
}

const monthlySeries = computed(() => props.charts.monthly?.series || [])
const monthlyOptions = computed(() => ({
  ...baseOptions,
  xaxis: { categories: props.charts.monthly?.categories || [] },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
}))

const channelSeries = computed(() => props.charts.by_channel?.series || [])
const channelOptions = computed(() => ({ ...baseOptions, labels: props.charts.by_channel?.labels || [], legend: { position: 'bottom' } }))

const typeSeries = computed(() => [{ name: 'Avaliações', data: props.charts.by_type?.series || [] }])
const typeOptions = computed(() => ({ ...baseOptions, xaxis: { categories: props.charts.by_type?.labels || [] }, plotOptions: { bar: { borderRadius: 8 } } }))

const scoreSeries = computed(() => [{ name: 'Pontuações', data: props.charts.score_distribution?.series || [] }])
const scoreOptions = computed(() => ({ ...baseOptions, xaxis: { categories: props.charts.score_distribution?.labels || [] }, plotOptions: { bar: { borderRadius: 8, columnWidth: '45%' } } }))

function averageRating(criteria) {
  const values = Object.values(criteria || {}).map((value) => Number(value)).filter(Boolean)
  if (!values.length) return '0.00'
  return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(2)
}

function formatDate(value) {
  if (!value) return '--'
  return new Date(value).toLocaleDateString('pt-PT')
}
</script>
