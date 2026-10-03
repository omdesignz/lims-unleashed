<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Relatórios' }, { title: 'Movimentos' }]"
      title="Movimento de existências"
      :lede="lede"
    >
      <template #actions>
        <InventoryReportExportButton report-type="stock_movement" :filters="filters" />
      </template>
      <div class="pl-tabs mb-8" role="tablist" aria-label="Modo do relatório">
        <button type="button" role="tab" class="pl-tab" :aria-selected="filters.view === 'detailed'" @click="setView('detailed')">Movimentos</button>
        <button type="button" role="tab" class="pl-tab" :aria-selected="filters.view === 'summary'" @click="setView('summary')">Resumo diário</button>
      </div>
    </PageHeader>

    <form class="pl-filter" role="search" aria-label="Filtrar movimentos" @submit.prevent>
      <label for="stock-movement-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="stock-movement-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="item, código ou utilizador" />
      <span v-if="loading" class="pl-k pl-faint" role="status">A actualizar…</span>
      <button v-if="hasActiveFilters" type="button" class="ds-chip" @click="clearFilters">
        Limpar filtros
        <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </form>
    <div class="grid gap-4 border border-b-0 border-[var(--pl-line)] p-4 md:grid-cols-2 xl:grid-cols-4">
      <BaseInput v-model="filters.date_from" type="date" label="Data inicial" />
      <BaseInput v-model="filters.date_to" type="date" label="Data final" />
      <div class="ds-field-group xl:col-span-2">
        <span class="ds-field-label">Item de inventário</span>
        <ComboboxEnhanced
          :model-value="selectedItem"
          :options="itemOptions"
          placeholder="Pesquisar item por nome ou código"
          @update:model-value="selectItem"
        />
      </div>
      <BaseSelect v-model="filters.warehouse_id" label="Armazém">
        <option value="">Todos os armazéns</option>
        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
      </BaseSelect>
      <BaseSelect v-model="filters.sort_by" label="Ordenar por">
        <option value="created_at">Data e hora</option>
        <option value="qty">Quantidade</option>
      </BaseSelect>
      <BaseSelect v-model="filters.sort_direction" label="Direcção">
        <option value="desc">Descendente</option>
        <option value="asc">Ascendente</option>
      </BaseSelect>
    </div>

    <dl class="pl-panel pl-facts pl-facts-2 mb-8" aria-label="Resumo dos movimentos">
      <div v-for="card in summaryCards" :key="card.label" class="pl-fact">
        <dt>{{ card.label }}</dt>
        <dd>
          <span class="pl-num font-medium">{{ card.value }}</span>
          <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ card.detail }}</span>
        </dd>
      </div>
    </dl>

    <div class="mb-8 grid gap-6 xl:grid-cols-2">
      <section class="pl-panel min-w-0 xl:row-span-2" aria-labelledby="movement-daily-chart">
        <div class="pl-panel-head">
          <h2 id="movement-daily-chart" class="pl-k">Actividade diária</h2>
          <span class="pl-k pl-faint">{{ dailyActivityDays }} dias · {{ filterPeriod || 'Período completo' }}</span>
        </div>
        <div class="min-h-72 p-4">
          <apexchart type="line" height="288" :options="dailyActivityChartOptions" :series="dailyActivityChartSeries" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="movement-mix-chart">
        <div class="pl-panel-head">
          <h2 id="movement-mix-chart" class="pl-k">Tipos de movimento</h2>
          <span class="pl-k pl-faint">{{ typeMixTotal }} eventos</span>
        </div>
        <div class="min-h-64 p-4">
          <apexchart type="donut" height="256" :options="typeMixChartOptions" :series="typeMixChartSeries" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="movement-balance-chart">
        <div class="pl-panel-head">
          <h2 id="movement-balance-chart" class="pl-k">Balanço do período</h2>
          <span class="pl-k pl-faint">Entradas, saídas e saldo</span>
        </div>
        <div class="min-h-56 p-4">
          <apexchart type="bar" height="224" :options="directionBreakdownChartOptions" :series="directionBreakdownChartSeries" />
        </div>
      </section>
    </div>

    <div v-if="filters.view === 'detailed'" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="pl-panel min-w-0" aria-labelledby="movement-ledger" :aria-busy="loading">
        <div class="pl-panel-head">
          <h2 id="movement-ledger" class="pl-k">Livro de movimentos</h2>
          <span class="pl-k pl-faint">{{ transactions.total || transactionRows.length }} registos</span>
        </div>
        <div v-if="loading" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6" role="status">
          <span class="pl-k">A actualizar movimentos…</span>
        </div>
        <DataTable v-else-if="transactionRows.length">
          <thead>
            <tr>
              <th scope="col">Data e hora</th>
              <th scope="col">Item</th>
              <th scope="col">Armazém</th>
              <th scope="col">Tipo</th>
              <th scope="col" class="text-right">Quantidade</th>
              <th scope="col">Operador</th>
              <th scope="col">Observação</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transaction in transactionRows" :key="transaction.id">
              <td class="pl-num">{{ formatDateTime(transaction.created_at) }}</td>
              <td>
                <span class="font-medium">{{ transaction.item?.name || 'Item não identificado' }}</span>
                <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ transaction.item?.code || 'Sem código' }}</span>
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ transaction.item?.category?.name || 'Sem categoria' }}</span>
              </td>
              <td>
                {{ transaction.warehouse?.name || 'N/D' }}
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ transaction.warehouse?.location?.name || 'Sem localização' }}</span>
              </td>
              <td class="whitespace-nowrap"><StatusChip :tone="transactionTypeTone(transaction.type?.code)">{{ transaction.type?.name || 'Movimento' }}</StatusChip></td>
              <td :class="['pl-num whitespace-nowrap text-right', quantityTone(transaction.type?.code)]">{{ quantityLabel(transaction) }}</td>
              <td>{{ transaction.user?.name || 'N/D' }}</td>
              <td class="max-w-xs text-[12.5px] text-[var(--pl-muted)]">{{ transaction.notes || 'Sem observações.' }}</td>
            </tr>
          </tbody>
        </DataTable>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem movimentos encontrados</span>
          <p class="text-sm text-[var(--pl-muted)]">Ajuste o período ou os filtros de rastreabilidade.</p>
        </div>
        <Pagination
          v-if="transactionRows.length"
          :links="transactions.links"
          :total="transactions.total"
          :from="transactions.from"
          :to="transactions.to"
          :last_page="transactions.last_page"
          :current_page="transactions.current_page"
        />
      </section>

      <aside class="grid content-start gap-6">
        <section class="pl-panel" aria-labelledby="movement-most-active">
          <div class="pl-panel-head">
            <h2 id="movement-most-active" class="pl-k">Maior actividade</h2>
          </div>
          <dl class="pl-facts">
            <div class="pl-fact">
              <dt>Item</dt>
              <dd>{{ stats.most_active_item?.item?.name || 'Sem dados' }}<span class="block text-[12.5px] text-[var(--pl-muted)]">{{ stats.most_active_item?.transaction_count || 0 }} transacções</span></dd>
            </div>
            <div class="pl-fact">
              <dt>Operador</dt>
              <dd>{{ stats.most_active_user?.user?.name || 'Sem dados' }}<span class="block text-[12.5px] text-[var(--pl-muted)]">{{ stats.most_active_user?.transaction_count || 0 }} movimentos</span></dd>
            </div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="movement-types">
          <div class="pl-panel-head">
            <h2 id="movement-types" class="pl-k">Composição</h2>
          </div>
          <ol v-if="typeMixRows.length">
            <li v-for="row in typeMixRows" :key="row.label" class="pl-row">
              <span class="min-w-0 truncate">{{ row.label }}</span>
              <span class="pl-num text-[12.5px]">{{ row.value }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem movimentos no período.</p>
        </section>
      </aside>
    </div>

    <section v-else class="pl-panel min-w-0" aria-labelledby="movement-daily-summary" :aria-busy="loading">
      <div class="pl-panel-head">
        <h2 id="movement-daily-summary" class="pl-k">Resumo por dia</h2>
        <span class="pl-k pl-faint">{{ summaryRows.length }} dias</span>
      </div>
      <div v-if="loading" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6" role="status">
        <span class="pl-k">A consolidar o período…</span>
      </div>
      <DataTable v-else-if="summaryRows.length">
        <thead>
          <tr>
            <th scope="col">Data</th>
            <th scope="col" class="text-right">Transacções</th>
            <th scope="col" class="text-right">Entradas</th>
            <th scope="col" class="text-right">Saídas</th>
            <th scope="col" class="text-right">Saldo</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="day in summaryRows" :key="day.date">
            <td class="pl-num">{{ formatDate(day.date) }}</td>
            <td class="pl-num text-right">{{ day.total_transactions }}</td>
            <td class="pl-num text-right text-[var(--pl-ok)]">+{{ formatQuantity(day.total_in) }}</td>
            <td class="pl-num text-right text-[var(--pl-bad)]">-{{ formatQuantity(day.total_out) }}</td>
            <td :class="['pl-num text-right', netTone(day)]">{{ netLabel(day) }}</td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">Sem actividade diária</span>
        <p class="text-sm text-[var(--pl-muted)]">Seleccione outro período para gerar a reconciliação.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Pagination from '@/Components/pagination.vue'
import InventoryReportExportButton from '@/Components/vap-inventory/InventoryReportExportButton.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { X as XMarkIcon } from '@lucide/vue'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [] }) },
  summary: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
})

const inboundCodes = ['stock_in', 'stock_adjustment_add', 'consumption_reversal']
const outboundCodes = ['stock_out', 'stock_adjustment_remove', 'consumption']
const loading = ref(false)
const isDarkMode = ref(false)
let themeObserver

const filters = reactive({
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
  item_id: props.filters?.item_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  search: props.filters?.search ?? '',
  view: props.filters?.view === 'summary' ? 'summary' : 'detailed',
  sort_by: ['created_at', 'qty'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'created_at',
  sort_direction: props.filters?.sort_direction === 'asc' ? 'asc' : 'desc',
})

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.code ? ` · ${item.code}` : ''}`,
})))
const selectedItem = ref(itemOptions.value.find((option) => String(option.value) === String(filters.item_id)) || null)
const transactionRows = computed(() => props.transactions?.data || [])
const summaryRows = computed(() => props.summary || [])
const chartTextColor = computed(() => isDarkMode.value ? '#d7dbe0' : '#6b7482')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#eef0f3')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const summaryCards = computed(() => [
  {
    label: 'Transacções',
    value: formatQuantity(props.stats?.total_transactions),
    detail: `${formatQuantity(props.stats?.avg_daily_transactions)} por dia no período`,
  },
  {
    label: 'Entradas',
    value: formatQuantity(props.stats?.total_in),
    detail: 'Unidades adicionadas',
  },
  {
    label: 'Saídas',
    value: formatQuantity(props.stats?.total_out),
    detail: 'Unidades removidas',
  },
  {
    label: 'Saldo líquido',
    value: signedNumber(props.stats?.net_movement),
    detail: 'Entradas menos saídas',
  },
])

const lede = computed(() => {
  const movements = Number(props.transactions?.total ?? transactionRows.value.length)

  return `${formatQuantity(movements)} ${movements === 1 ? 'movimento' : 'movimentos'} neste âmbito. Cada linha guarda o item, o local, o tipo, a quantidade, o operador e o momento do movimento.`
})

const filterPeriod = computed(() => {
  if (filters.date_from && filters.date_to) return `${formatDate(filters.date_from)} - ${formatDate(filters.date_to)}`
  if (filters.date_from) return `Desde ${formatDate(filters.date_from)}`
  if (filters.date_to) return `Até ${formatDate(filters.date_to)}`
  return ''
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filterPeriod.value) pills.push(filterPeriod.value)
  if (filters.item_id) pills.push(`Item: ${selectedItem.value?.label || 'Seleccionado'}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
const directionBreakdownChartSeries = computed(() => props.charts?.direction_breakdown?.series || [])
const typeMixChartSeries = computed(() => props.charts?.type_mix?.series || [])
const typeMixTotal = computed(() => typeMixChartSeries.value.reduce((total, value) => total + Number(value || 0), 0))
const dailyActivityChartSeries = computed(() => props.charts?.daily_activity?.series || [])
const dailyActivityDays = computed(() => props.charts?.daily_activity?.labels?.length || 0)
const typeMixRows = computed(() => (props.charts?.type_mix?.labels || []).map((label, index) => ({
  label,
  value: props.charts?.type_mix?.series?.[index] || 0,
})))

const directionBreakdownChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#14a3a8'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 0, columnWidth: '52%' } },
  xaxis: {
    categories: props.charts?.direction_breakdown?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const typeMixChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.type_mix?.labels || [],
  colors: ['#22a45d', '#e5484d', '#e0902b', '#14a3a8'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value },
}))

const dailyActivityChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#22a45d', '#e5484d', '#14a3a8'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  stroke: { curve: 'straight', width: [3, 3, 2] },
  markers: { size: 2 },
  xaxis: {
    categories: props.charts?.daily_activity?.labels || [],
    labels: { rotate: -20, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
}))

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatDateTime(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

function formatQuantity(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 4 }).format(Number(value || 0))
}

function signedNumber(value) {
  const number = Number(value || 0)
  return `${number > 0 ? '+' : ''}${formatQuantity(number)}`
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function selectItem(option) {
  selectedItem.value = option
  filters.item_id = option?.value ?? ''
}

function setView(view) {
  filters.view = view
}

function transactionTypeTone(code) {
  if (inboundCodes.includes(code)) return 'ok'
  if (outboundCodes.includes(code)) return 'bad'
  if (code === 'transfer') return 'run'
  return 'neutral'
}

function quantityTone(code) {
  if (inboundCodes.includes(code)) return 'text-[var(--pl-ok)]'
  if (outboundCodes.includes(code)) return 'text-[var(--pl-bad)]'
  return ''
}

function quantityLabel(transaction) {
  const code = transaction.type?.code
  const sign = inboundCodes.includes(code) ? '+' : outboundCodes.includes(code) ? '-' : ''
  return `${sign}${formatQuantity(Math.abs(Number(transaction.qty || 0)))}`
}

function netValue(day) {
  return Number(day.total_in || 0) - Number(day.total_out || 0)
}

function netLabel(day) {
  return signedNumber(netValue(day))
}

function netTone(day) {
  return netValue(day) >= 0 ? 'text-[var(--pl-ok)]' : 'text-[var(--pl-bad)]'
}

function clearFilters() {
  selectedItem.value = null
  Object.assign(filters, {
    date_from: '',
    date_to: '',
    item_id: '',
    warehouse_id: '',
    search: '',
    sort_by: 'created_at',
    sort_direction: 'desc',
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.stock-movement'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onStart: () => { loading.value = true },
      onFinish: () => { loading.value = false },
    })
  }, 350),
  { deep: true },
)

onMounted(() => {
  syncDarkMode()
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
