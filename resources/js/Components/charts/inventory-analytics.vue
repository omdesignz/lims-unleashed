<template>
  <div class="space-y-8">
    <div>
      <form class="pl-filter" role="search" aria-label="Âmbito da análise" @submit.prevent>
        <label for="analytics-period" class="pl-filter-prompt">Filtro://</label>
        <div class="w-48">
          <BaseSelect id="analytics-period" v-model="filters.dateRange" aria-label="Período analítico">
            <option value="7d">Últimos 7 dias</option>
            <option value="30d">Últimos 30 dias</option>
            <option value="90d">Últimos 90 dias</option>
            <option value="1y">Último ano</option>
            <option value="custom">Período personalizado</option>
          </BaseSelect>
        </div>
        <template v-if="filters.dateRange === 'custom'">
          <div class="w-40"><BaseInput v-model="filters.startDate" type="date" aria-label="Data inicial" /></div>
          <div class="w-40"><BaseInput v-model="filters.endDate" type="date" aria-label="Data final" /></div>
        </template>
        <div class="w-52">
          <BaseSelect v-model="filters.categoryId" aria-label="Categoria">
            <option value="">Todas as categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </BaseSelect>
        </div>
        <div class="w-52">
          <BaseSelect v-model="filters.warehouseId" aria-label="Armazém">
            <option value="">Todos os armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
          </BaseSelect>
        </div>
        <span class="pl-k pl-faint ml-auto" role="status">{{ isLoading ? 'A actualizar…' : periodLabel }}</span>
        <button v-if="hasScopedFilters" type="button" class="ds-chip" @click="clearScopedFilters">
          Limpar âmbito
          <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
      </form>

      <dl class="pl-panel pl-facts pl-facts-2" aria-label="Indicadores do período" :aria-busy="isLoading">
        <div v-for="card in metricCards" :key="card.label" class="pl-fact">
          <dt>{{ card.label }}</dt>
          <dd>
            <span class="pl-num font-medium" :class="{ 'text-[var(--pl-bad)]': card.bad }">{{ card.value }}</span>
            <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ card.detail }}</span>
          </dd>
        </div>
      </dl>
      <p v-if="requestError" class="pl-banner pl-banner-bad mt-3 text-sm" role="alert">{{ requestError }}</p>
    </div>

    <slot name="areas" />

    <div class="grid gap-6 xl:grid-cols-2" :class="{ 'opacity-70': isLoading }">
      <section class="pl-panel min-w-0" aria-labelledby="analytics-trend">
        <div class="pl-panel-head">
          <h2 id="analytics-trend" class="pl-k">Tendência de consumo</h2>
          <span class="pl-k pl-faint">Volume diário</span>
        </div>
        <div v-if="consumptionTrend.length" class="min-h-72 p-4">
          <apexchart type="line" height="288" :options="consumptionChartOptions" :series="consumptionChartSeries" />
        </div>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem consumo no período</span>
          <p class="text-sm text-[var(--pl-muted)]">Alargue o período ou retire o filtro de categoria ou armazém.</p>
        </div>
      </section>

      <section class="pl-panel min-w-0" aria-labelledby="analytics-distribution">
        <div class="pl-panel-head">
          <h2 id="analytics-distribution" class="pl-k">Existências por categoria</h2>
          <span class="pl-k pl-faint">{{ stockDistribution.length }} categorias</span>
        </div>
        <div v-if="stockDistribution.length" class="min-h-72 p-4">
          <apexchart type="donut" height="288" :options="stockChartOptions" :series="stockChartSeries" />
        </div>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem existências distribuídas</span>
          <p class="text-sm text-[var(--pl-muted)]">Não há unidades disponíveis neste âmbito.</p>
        </div>
      </section>

      <section class="pl-panel min-w-0" aria-labelledby="analytics-monthly">
        <div class="pl-panel-head">
          <h2 id="analytics-monthly" class="pl-k">Comparação mensal</h2>
          <span class="pl-k pl-faint">Ano actual e anterior</span>
        </div>
        <div v-if="monthlyComparison.length" class="min-h-72 p-4">
          <apexchart type="bar" height="288" :options="monthlyChartOptions" :series="monthlyChartSeries" />
        </div>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem comparação mensal disponível</span>
        </div>
      </section>

      <section class="pl-panel min-w-0" aria-labelledby="analytics-top">
        <div class="pl-panel-head">
          <h2 id="analytics-top" class="pl-k">Reagentes mais consumidos</h2>
          <span class="pl-k pl-faint">Top 8</span>
        </div>
        <div v-if="topReagents.length" class="min-h-72 p-4">
          <apexchart type="bar" height="288" :options="topReagentsChartOptions" :series="topReagentsChartSeries" />
        </div>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem consumo por reagente</span>
        </div>
      </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(22rem,0.75fr)]">
      <section class="pl-panel min-w-0" aria-labelledby="analytics-suppliers">
        <div class="pl-panel-head">
          <h2 id="analytics-suppliers" class="pl-k">Desempenho de fornecedores</h2>
          <span class="pl-k pl-faint">Meta: 90% no prazo</span>
        </div>
        <div v-if="supplierPerformance.length" class="min-h-80 p-4">
          <apexchart type="bar" height="320" :options="supplierChartOptions" :series="supplierChartSeries" />
        </div>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem entregas avaliáveis</span>
          <p class="text-sm text-[var(--pl-muted)]">O indicador aparece quando existirem ordens com datas de entrega.</p>
        </div>
      </section>

      <div class="grid content-start gap-6">
        <section v-for="alert in alertGroups" :key="alert.key" class="pl-panel" :aria-labelledby="`analytics-alert-${alert.key}`">
          <div class="pl-panel-head">
            <h2 :id="`analytics-alert-${alert.key}`" class="pl-k">{{ alert.label }}</h2>
            <span class="pl-k pl-num" :class="alert.count ? 'text-[var(--pl-bad)]' : 'pl-faint'">{{ alert.count }}</span>
          </div>
          <ul v-if="alert.items.length">
            <li v-for="item in alert.items" :key="item" class="pl-row"><span class="min-w-0">{{ item }}</span></li>
          </ul>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem ocorrências nesta categoria.</p>
          <div v-if="alert.key === 'critical' && alert.count > 0" class="border-t border-[var(--pl-line)] px-4 py-3">
            <button type="button" class="ds-button ds-button-secondary" @click="restockDialogOpen = true">
              <ShoppingCartIcon class="h-4 w-4" aria-hidden="true" />
              Criar rascunho de reposição
            </button>
          </div>
        </section>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="pl-panel min-w-0" aria-labelledby="analytics-history">
        <div class="pl-panel-head">
          <h2 id="analytics-history" class="pl-k">Histórico de consumo</h2>
          <span class="pl-k pl-faint">{{ consumptionHistory.length }} eventos</span>
        </div>
        <div v-if="consumptionHistory.length" class="overflow-x-auto">
          <DataTable>
            <thead>
              <tr>
                <th scope="col">Data</th>
                <th scope="col">Reagente</th>
                <th scope="col">Armazém</th>
                <th scope="col" class="text-right">Consumo</th>
                <th scope="col">Operador</th>
                <th scope="col">Existências</th>
                <th scope="col" class="text-right">Cobertura</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="event in consumptionHistory" :key="event.id">
                <td class="pl-num whitespace-nowrap">{{ formatDate(event.date) }}</td>
                <td>
                  <span class="font-medium">{{ event.reagent_name || 'Reagente não identificado' }}</span>
                  <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ event.item?.code || 'Sem código' }}</span>
                  <span class="block max-w-xs text-[12.5px] text-[var(--pl-muted)]">{{ event.remarks || 'Sem observações.' }}</span>
                </td>
                <td>{{ event.warehouse?.name || 'N/D' }}</td>
                <td class="pl-num text-right">{{ formatNumber(event.quantity_used) }}</td>
                <td>{{ event.used_by || 'N/D' }}</td>
                <td class="min-w-44">
                  <div class="flex items-center justify-between gap-3">
                    <StatusChip :tone="stockTone(event)">{{ stockLabel(event) }}</StatusChip>
                    <span class="pl-num text-[12px] text-[var(--pl-muted)]">{{ formatNumber(event.current_stock) }} / {{ formatNumber(Number(event.min_level || 0) * 2) }}</span>
                  </div>
                  <div class="pl-bar mt-2"><i :class="{ 'pl-bar-late': stockTone(event) !== 'ok' }" :style="{ width: `${stockPercentage(event)}%` }"></i></div>
                </td>
                <td class="whitespace-nowrap text-right">
                  <span class="pl-num block" :class="coverageTone(event)">{{ coverageLabel(event) }}</span>
                  <span class="block text-[12px] text-[var(--pl-muted)]">{{ event.predicted_out_date ? `até ${formatDate(event.predicted_out_date)}` : 'sem previsão' }}</span>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem histórico de consumo</span>
          <p class="text-sm text-[var(--pl-muted)]">Ajuste o período ou confirme a existência de registos.</p>
        </div>
      </section>

      <aside class="grid content-start gap-6">
        <section class="pl-panel" aria-labelledby="analytics-depletion">
          <div class="pl-panel-head">
            <h2 id="analytics-depletion" class="pl-k">Cobertura mais curta</h2>
            <span class="pl-k pl-faint">Predição de rutura</span>
          </div>
          <ol v-if="depletionRisks.length">
            <li v-for="(risk, index) in depletionRisks" :key="`${risk.id}-${index}`" class="pl-row">
              <span class="min-w-0">
                <span class="block truncate font-medium">{{ risk.reagent_name || 'Reagente' }}</span>
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ risk.warehouse?.name || 'Sem armazém' }}</span>
              </span>
              <span class="pl-num text-[12.5px]" :class="coverageTone(risk)">{{ coverageLabel(risk) }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem risco calculável no período.</p>
        </section>

        <section class="pl-panel" aria-labelledby="analytics-concentration">
          <div class="pl-panel-head">
            <h2 id="analytics-concentration" class="pl-k">Concentração do consumo</h2>
            <span class="pl-k pl-faint">Top 7</span>
          </div>
          <ol v-if="topReagents.length">
            <li v-for="(reagent, index) in topReagents.slice(0, 7)" :key="reagent.id || index" class="pl-row">
              <span class="flex min-w-0 items-center gap-3">
                <span class="pl-num pl-faint w-5 shrink-0 text-[12px]">{{ String(index + 1).padStart(2, '0') }}</span>
                <span class="truncate font-medium">{{ reagent.name || 'Sem nome' }}</span>
              </span>
              <span class="pl-num text-[12.5px]">{{ formatNumber(reagent.consumption) }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por reagente.</p>
        </section>
      </aside>
    </div>

    <TransitionRoot as="template" :show="restockDialogOpen">
      <Dialog as="div" class="relative z-50" @close="restockDialogOpen = false">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-out duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
        </TransitionChild>
        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:scale-[0.97]" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-out duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:scale-[0.97]">
              <DialogPanel class="ds-modal-panel w-full max-w-lg overflow-hidden text-left transition-all">
                <div class="grid gap-2 px-5 py-5 sm:px-6">
                  <DialogTitle class="pl-d3">Criar rascunho de reposição?</DialogTitle>
                  <p class="text-sm leading-6 text-[var(--pl-muted)]">Será criado um rascunho para os itens actualmente sem existências. A ordem continuará sujeita a revisão e aprovação.</p>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-[var(--pl-line)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                  <button type="button" class="ds-button ds-button-quiet" :disabled="creatingDrafts" @click="restockDialogOpen = false">Cancelar</button>
                  <button type="button" class="ds-button ds-button-primary" :disabled="creatingDrafts" @click="createRestockDraft">
                    <ArrowPathIcon v-if="creatingDrafts" class="h-4 w-4 animate-spin" aria-hidden="true" />
                    <ShoppingCartIcon v-else class="h-4 w-4" aria-hidden="true" />
                    {{ creatingDrafts ? 'A criar…' : 'Criar rascunho' }}
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import {
  RefreshCw as ArrowPathIcon,
  ShoppingCart as ShoppingCartIcon,
  X as XMarkIcon,
} from '@lucide/vue'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
})

const analyticsData = ref(normalizeData(props.initialData))
const isLoading = ref(false)
const requestError = ref('')
const isDarkMode = ref(false)
const restockDialogOpen = ref(false)
const creatingDrafts = ref(false)
let themeObserver
let requestController

const filters = reactive({
  dateRange: '30d',
  categoryId: '',
  warehouseId: '',
  startDate: '',
  endDate: '',
})

const metrics = computed(() => analyticsData.value.metrics ?? {})
const consumptionTrend = computed(() => analyticsData.value.consumptionTrend)
const stockDistribution = computed(() => analyticsData.value.stockDistribution)
const monthlyComparison = computed(() => analyticsData.value.monthlyComparison)
const topReagents = computed(() => analyticsData.value.topReagents)
const consumptionHistory = computed(() => analyticsData.value.consumptionHistory)
const supplierPerformance = computed(() => Array.isArray(metrics.value.supplierPerformance)
  ? [...metrics.value.supplierPerformance].sort((a, b) => Number(b.on_time_rate || 0) - Number(a.on_time_rate || 0))
  : [])

const periodLabel = computed(() => {
  if (filters.dateRange === 'custom') {
    if (filters.startDate && filters.endDate) return `${formatDate(filters.startDate)} - ${formatDate(filters.endDate)}`
    return 'Defina as duas datas'
  }
  return ({ '7d': 'Últimos 7 dias', '30d': 'Últimos 30 dias', '90d': 'Últimos 90 dias', '1y': 'Último ano' })[filters.dateRange] || 'Últimos 30 dias'
})

const hasScopedFilters = computed(() => filters.dateRange !== '30d' || Boolean(filters.categoryId || filters.warehouseId))
const totalAlerts = computed(() => Number(metrics.value.reorderAlerts || 0) + Number(metrics.value.criticalAlerts || 0) + Number(metrics.value.expiringAlerts || 0))
const usageChangeLabel = computed(() => {
  const change = Number(metrics.value.usageChange || 0)
  if (change === 0) return 'Sem variação vs. período anterior'
  return `${change > 0 ? '+' : ''}${formatNumber(change)}% vs. período anterior`
})

const metricCards = computed(() => [
  {
    label: 'Consumo total',
    value: formatNumber(metrics.value.totalConsumption),
    detail: `${formatNumber(metrics.value.monthlyConsumption)} unidades no mês`,
  },
  {
    label: 'Média diária',
    value: formatNumber(metrics.value.dailyAverage),
    detail: usageChangeLabel.value,
  },
  {
    label: 'Alertas activos',
    value: formatNumber(totalAlerts.value),
    detail: `${metrics.value.criticalAlerts || 0} ocorrências críticas`,
    bad: Number(metrics.value.criticalAlerts || 0) > 0,
  },
  {
    label: 'Valor em existências',
    value: formatCurrency(metrics.value.inventoryValue),
    detail: 'Âmbito seleccionado',
  },
])

const alertGroups = computed(() => [
  {
    key: 'critical',
    label: 'Sem existências ou expirado',
    count: Number(metrics.value.criticalAlerts || 0),
    items: arrayValue(metrics.value.alertDetails?.critical),
  },
  {
    key: 'reorder',
    label: 'Abaixo do nível mínimo',
    count: Number(metrics.value.reorderAlerts || 0),
    items: arrayValue(metrics.value.alertDetails?.reorder),
  },
  {
    key: 'expiring',
    label: 'Validade próxima',
    count: Number(metrics.value.expiringAlerts || 0),
    items: arrayValue(metrics.value.alertDetails?.expiring),
  },
])

const depletionRisks = computed(() => consumptionHistory.value
  .filter((event) => event.days_remaining !== null && event.days_remaining !== undefined)
  .sort((a, b) => Number(a.days_remaining) - Number(b.days_remaining))
  .slice(0, 7))

const chartTextColor = computed(() => isDarkMode.value ? '#d7dbe0' : '#6b7482')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#eef0f3')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')
const baseChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  tooltip: { theme: chartTooltipTheme.value },
}))

// The server sends a timestamp per day and English month abbreviations (January first).
const monthLabels = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']
const formatTrendDay = (value) => {
  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? String(value ?? '')
    : date.toLocaleDateString('pt-PT', { day: '2-digit', month: 'short', timeZone: 'Africa/Luanda' })
}

const consumptionChartSeries = computed(() => [{
  name: 'Consumo diário',
  data: consumptionTrend.value.map((item) => Number(item.quantity || 0)),
}])
const consumptionChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#e5484d'],
  stroke: { curve: 'straight', width: 3 },
  markers: { size: 3 },
  xaxis: {
    categories: consumptionTrend.value.map((item) => formatTrendDay(item.date)),
    labels: { rotate: -20, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  legend: { show: false },
}))

const stockChartSeries = computed(() => stockDistribution.value.map((item) => Number(item.quantity || 0)))
const stockChartOptions = computed(() => ({
  ...baseChartOptions.value,
  labels: stockDistribution.value.map((item) => item.category || 'Sem categoria'),
  colors: ['#14a3a8', '#22a45d', '#7c5ce0', '#e0902b', '#e5484d', '#6b7482'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
}))

const monthlyChartSeries = computed(() => [
  { name: 'Ano actual', data: monthlyComparison.value.map((month) => Number(month.current || 0)) },
  { name: 'Ano anterior', data: monthlyComparison.value.map((month) => Number(month.previous || 0)) },
])
const monthlyChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#14a3a8', '#7c5ce0'],
  plotOptions: { bar: { borderRadius: 0, columnWidth: '52%' } },
  xaxis: {
    categories: monthlyComparison.value.map((month, index) => monthLabels[index] ?? month.month),
    labels: { style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  legend: { position: 'top', horizontalAlign: 'right', labels: { colors: chartTextColor.value } },
}))

const topReagentsChartSeries = computed(() => [{
  name: 'Consumo',
  data: topReagents.value.slice(0, 8).map((item) => Number(item.consumption || 0)),
}])
const topReagentsChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#e0902b'],
  plotOptions: { bar: { borderRadius: 0, horizontal: true } },
  xaxis: {
    categories: topReagents.value.slice(0, 8).map((item) => item.name || 'Sem nome'),
    labels: { style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { maxWidth: 180, style: { colors: chartTextColor.value } } },
  legend: { show: false },
}))

const supplierChartSeries = computed(() => [{
  name: 'Entregas no prazo',
  data: supplierPerformance.value.map((supplier) => Number(supplier.on_time_rate || 0)),
}])
const supplierChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#22a45d'],
  annotations: {
    xaxis: [{
      x: 90,
      borderColor: '#e5484d',
      label: { text: 'Meta 90%', style: { color: '#fff', background: '#e5484d' } },
    }],
  },
  plotOptions: { bar: { borderRadius: 0, horizontal: true } },
  xaxis: {
    categories: supplierPerformance.value.map((supplier) => supplier.supplier || 'Sem fornecedor'),
    min: 0,
    max: 100,
    labels: { formatter: (value) => `${value}%`, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { maxWidth: 220, style: { colors: chartTextColor.value } } },
  legend: { show: false },
}))

function normalizeData(data = {}) {
  return {
    consumptionTrend: arrayValue(data.consumptionTrend),
    stockDistribution: arrayValue(data.stockDistribution),
    monthlyComparison: arrayValue(data.monthlyComparison),
    topReagents: arrayValue(data.topReagents),
    consumptionHistory: arrayValue(data.consumptionHistory),
    metrics: data.metrics ?? {},
  }
}

function arrayValue(value) {
  return Array.isArray(value) ? value : []
}

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 0 }).format(Number(value || 0))
}

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function clearScopedFilters() {
  Object.assign(filters, {
    dateRange: '30d',
    categoryId: '',
    warehouseId: '',
    startDate: '',
    endDate: '',
  })
}

function queryParameters() {
  const parameters = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) parameters.set(key, value)
  })
  return parameters
}

async function loadAnalytics() {
  if (filters.dateRange === 'custom' && (!filters.startDate || !filters.endDate)) return

  requestController?.abort()
  requestController = new AbortController()
  isLoading.value = true
  requestError.value = ''

  try {
    const response = await fetch(`${route('vap-inventory.analytics.data')}?${queryParameters().toString()}`, {
      headers: { Accept: 'application/json' },
      signal: requestController.signal,
    })
    if (!response.ok) throw new Error('Não foi possível actualizar os indicadores de inventário.')
    analyticsData.value = normalizeData(await response.json())
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') return
    requestError.value = error instanceof Error ? error.message : 'Não foi possível actualizar os indicadores.'
  } finally {
    isLoading.value = false
  }
}

function stockPercentage(event) {
  const benchmark = Math.max(Number(event.min_level || 0) * 2, 1)
  return Math.min(Math.max((Number(event.current_stock || 0) / benchmark) * 100, 0), 100)
}

function stockLabel(event) {
  if (Number(event.current_stock || 0) <= 0) return 'Sem existências'
  if (Number(event.current_stock || 0) <= Number(event.min_level || 0)) return 'Existências baixas'
  return 'Saudável'
}

function stockTone(event) {
  if (Number(event.current_stock || 0) <= 0) return 'bad'
  if (Number(event.current_stock || 0) <= Number(event.min_level || 0)) return 'wait'
  return 'ok'
}

function coverageLabel(event) {
  if (event.days_remaining === null || event.days_remaining === undefined) return 'Sem previsão'
  return `${formatNumber(event.days_remaining)} dias`
}

function coverageTone(event) {
  const days = Number(event.days_remaining)
  if (event.days_remaining === null || event.days_remaining === undefined) return 'text-[var(--pl-faint)]'
  if (days <= 7) return 'text-[var(--pl-bad)]'
  if (days <= 30) return 'text-[var(--pl-warn)]'
  return 'text-[var(--pl-ok)]'
}

function createRestockDraft() {
  creatingDrafts.value = true
  router.post(route('vap-inventory.analytics.restock'), {}, {
    preserveScroll: true,
    onSuccess: () => { restockDialogOpen.value = false },
    onFinish: () => { creatingDrafts.value = false },
  })
}

watch(filters, debounce(loadAnalytics, 350), { deep: true })

onMounted(() => {
  syncDarkMode()
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  requestController?.abort()
  themeObserver?.disconnect()
})
</script>
