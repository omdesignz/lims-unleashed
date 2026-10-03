<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Relatórios' }, { title: 'Existências baixas' }]"
      title="Existências baixas"
      :lede="lede"
    >
      <template #actions>
        <InventoryReportExportButton report-type="low_stock" :filters="filters" />
        <button type="button" class="ds-button ds-button-primary" :disabled="!recommendedOrders.length" @click="generateOrder">
          <ShoppingCartIcon class="h-4 w-4" aria-hidden="true" />
          Criar pedido consolidado
        </button>
      </template>
    </PageHeader>

    <form class="pl-filter" role="search" aria-label="Filtrar existências baixas" @submit.prevent>
      <span class="pl-filter-prompt">Filtro://</span>
      <div class="w-52">
        <BaseSelect v-model="filters.warehouse_id" aria-label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-52">
        <BaseSelect v-model="filters.category_id" aria-label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-60">
        <BaseSelect v-model="filters.severity" aria-label="Severidade">
          <option value="">Todos os níveis</option>
          <option value="critical">Crítico / sem existências</option>
          <option value="low">Abaixo do ponto de reposição</option>
        </BaseSelect>
      </div>
      <div class="w-52">
        <BaseSelect v-model="filters.sort_by" aria-label="Ordenar por">
          <option value="severity">Severidade</option>
          <option value="current_stock">Existências actuais</option>
          <option value="reorder_point">Ponto de reposição</option>
          <option value="item_name">Nome do item</option>
        </BaseSelect>
      </div>
      <button v-if="hasActiveFilters" type="button" class="ds-chip ml-auto" @click="clearFilters">
        Limpar filtros
        <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </form>

    <dl class="pl-panel pl-facts pl-facts-2 mb-8" aria-label="Resumo das existências baixas">
      <div v-for="card in summaryCards" :key="card.label" class="pl-fact">
        <dt>{{ card.label }}</dt>
        <dd>
          <span class="pl-num font-medium" :class="{ 'text-[var(--pl-bad)]': card.bad && Number(card.value) > 0 }">{{ card.value }}</span>
          <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ card.detail }}</span>
        </dd>
      </div>
    </dl>

    <div class="mb-8 grid gap-6 xl:grid-cols-2">
      <section class="pl-panel min-w-0 xl:row-span-2" aria-labelledby="low-stock-severity">
        <div class="pl-panel-head">
          <h2 id="low-stock-severity" class="pl-k">Severidade da fila</h2>
          <span class="pl-k pl-faint">{{ severityMixTotal }} itens em atenção</span>
        </div>
        <div class="min-h-72 p-4">
          <apexchart type="bar" height="288" :options="severityMixChartOptions" :series="severityMixChartSeries" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="low-stock-warehouses">
        <div class="pl-panel-head">
          <h2 id="low-stock-warehouses" class="pl-k">Exposição por armazém</h2>
          <span class="pl-k pl-faint">{{ warehouseExposureTotal }} locais</span>
        </div>
        <div class="min-h-64 p-4">
          <apexchart type="donut" height="256" :options="warehouseExposureChartOptions" :series="warehouseExposureChartSeries" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="low-stock-gap">
        <div class="pl-panel-head">
          <h2 id="low-stock-gap" class="pl-k">Falta até à reposição</h2>
          <span class="pl-k pl-faint">Maiores distâncias</span>
        </div>
        <div class="min-h-56 p-4">
          <apexchart type="bar" height="224" :options="replenishmentGapChartOptions" :series="replenishmentGapChartSeries" />
        </div>
      </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="pl-panel min-w-0" aria-labelledby="low-stock-items">
        <div class="pl-panel-head">
          <h2 id="low-stock-items" class="pl-k">Itens com existências baixas</h2>
          <span class="pl-k pl-faint">{{ inventory.total || inventoryRows.length }} registos</span>
        </div>
        <DataTable v-if="inventoryRows.length">
          <thead>
            <tr>
              <th scope="col">Item</th>
              <th scope="col">Armazém</th>
              <th scope="col">Existências</th>
              <th scope="col">Estado</th>
              <th scope="col"><span class="sr-only">Acções</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in inventoryRows" :key="item.id">
              <td>
                <Link :href="route('vap-inventory.items.show', item.item_id)" class="font-medium hover:text-[var(--pl-accent-text)]">{{ item.item?.name || 'Item sem identificação' }}</Link>
                <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ item.item?.internal_code || item.item?.code || 'Sem código' }}</span>
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.item?.category?.name || 'Sem categoria' }}</span>
              </td>
              <td>
                {{ item.warehouse?.name || 'N/D' }}
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.warehouse?.location?.name || 'Sem localização' }}</span>
              </td>
              <td class="min-w-64">
                <dl class="grid grid-cols-3 gap-3">
                  <div><dt class="pl-k pl-faint">Actual</dt><dd class="pl-num mt-1">{{ item.qty_available }}</dd></div>
                  <div><dt class="pl-k pl-faint">Reposição</dt><dd class="pl-num mt-1">{{ item.reorder_point }}</dd></div>
                  <div><dt class="pl-k pl-faint">Falta</dt><dd class="pl-num mt-1 text-[var(--pl-bad)]">{{ reorderGap(item) }}</dd></div>
                </dl>
                <div class="pl-bar mt-3"><i :class="{ 'pl-bar-late': statusTone(item) === 'bad' }" :style="{ width: `${stockPercentage(item)}%` }"></i></div>
              </td>
              <td>
                <StatusChip :tone="statusTone(item)">{{ statusText(item) }}</StatusChip>
                <span v-if="item.item?.needs_calibration" class="block pt-1 text-[12px] text-[var(--pl-muted)]">Calibração necessária</span>
              </td>
              <td class="text-right">
                <div class="flex justify-end gap-1">
                  <Link :href="route('vap-inventory.items.show', item.item_id)" class="ds-table-action" :aria-label="`Abrir ${item.item?.name || 'item'}`">
                    <EyeIcon class="h-4 w-4" aria-hidden="true" />
                  </Link>
                  <Link :href="route('vap-inventory.items.edit', item.item_id)" class="ds-table-action" :aria-label="`Ajustar ${item.item?.name || 'item'}`">
                    <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                  </Link>
                  <button type="button" class="ds-table-action" :aria-label="`Criar pedido para ${item.item?.name || 'item'}`" @click="createOrderForItem(item)">
                    <ShoppingCartIcon class="h-4 w-4" aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem itens com existências baixas</span>
          <p class="text-sm text-[var(--pl-muted)]">Nenhuma rutura ou nível abaixo do ponto de reposição foi encontrado para os filtros actuais.</p>
        </div>
        <Pagination
          v-if="inventoryRows.length"
          :links="inventory.links"
          :total="inventory.total"
          :from="inventory.from"
          :to="inventory.to"
          :last_page="inventory.last_page"
          :current_page="inventory.current_page"
        />
      </section>

      <aside class="grid content-start gap-6">
        <section class="pl-panel" aria-labelledby="low-stock-recommendation">
          <div class="pl-panel-head">
            <h2 id="low-stock-recommendation" class="pl-k">Reposição sugerida</h2>
          </div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Itens sugeridos</dt><dd class="pl-num">{{ recommendedOrders.length }}</dd></div>
            <div class="pl-fact"><dt>Unidades</dt><dd class="pl-num">{{ recommendedUnitTotal }}</dd></div>
            <div class="pl-fact"><dt>Valor estimado</dt><dd class="pl-num">{{ formatMoney(recommendedValueTotal) }}</dd></div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="low-stock-priority">
          <div class="pl-panel-head">
            <h2 id="low-stock-priority" class="pl-k">Itens prioritários</h2>
          </div>
          <ul v-if="topRecommendedOrders.length">
            <li v-for="item in topRecommendedOrders" :key="item.id" class="pl-row">
              <span class="min-w-0">
                <span class="block truncate font-medium">{{ item.name }}</span>
                <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ item.code || 'Sem código' }}</span>
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.supplier?.name || 'Fornecedor por definir' }} · {{ formatMoney(item.recommended_qty * item.unit_price) }}</span>
              </span>
              <span class="pl-num text-[12.5px]">+{{ item.recommended_qty }}</span>
            </li>
          </ul>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem recomendações.</p>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/pagination.vue'
import InventoryReportExportButton from '@/Components/vap-inventory/InventoryReportExportButton.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import {
  Eye as EyeIcon,
  SquarePen as PencilSquareIcon,
  ShoppingCart as ShoppingCartIcon,
  X as XMarkIcon,
} from '@lucide/vue'

const props = defineProps({
  inventory: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const filters = reactive({
  warehouse_id: props.filters?.warehouse_id ?? '',
  category_id: props.filters?.category_id ?? '',
  severity: props.filters?.severity ?? '',
  sort_by: props.filters?.sort_by ?? 'severity',
})

const isDarkMode = ref(false)
let themeObserver

const chartTextColor = computed(() => isDarkMode.value ? '#d7dbe0' : '#6b7482')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#eef0f3')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const inventoryRows = computed(() => props.inventory?.data || [])

const summaryCards = computed(() => [
  {
    label: 'Existências críticas',
    value: props.stats?.critical_stock || 0,
    detail: 'No mínimo ou abaixo dele',
    bad: true,
  },
  {
    label: 'Existências baixas',
    value: Math.max(Number(props.stats?.total_low_stock || 0) - Number(props.stats?.critical_stock || 0), 0),
    detail: 'Abaixo do ponto de reposição',
  },
  {
    label: 'Sem existências',
    value: props.stats?.out_of_stock || 0,
    detail: 'Rutura confirmada',
    bad: true,
  },
  {
    label: 'Itens monitorizados',
    value: props.stats?.total_items || 0,
    detail: 'Base do inventário filtrado',
  },
])

const lede = computed(() => {
  const low = Number(props.stats?.total_low_stock || 0)
  const out = Number(props.stats?.out_of_stock || 0)

  if (!low && !out) {
    return 'Nenhum item abaixo do ponto de reposição. Não há reposição a preparar neste âmbito.'
  }

  return `${low} ${low === 1 ? 'item está' : 'itens estão'} abaixo do ponto de reposição e ${out} ${out === 1 ? 'está' : 'estão'} sem existências. Priorize as ruturas antes de abrir o pedido de compra.`
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.category_id) pills.push(`Categoria: ${categoryName(filters.category_id)}`)
  if (filters.severity) pills.push(`Severidade: ${filters.severity === 'critical' ? 'Crítico' : 'Baixo'}`)
  if (filters.sort_by !== 'severity') pills.push(`Ordem: ${sortLabel(filters.sort_by)}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)

const severityMixChartSeries = computed(() => [{
  name: 'Itens',
  data: props.charts?.severity_mix?.series || [],
}])

const severityMixTotal = computed(() => (props.charts?.severity_mix?.series || []).reduce((total, value) => total + Number(value || 0), 0))
const warehouseExposureChartSeries = computed(() => props.charts?.warehouse_exposure?.series || [])
const warehouseExposureTotal = computed(() => props.charts?.warehouse_exposure?.labels?.length || 0)
const replenishmentGapChartSeries = computed(() => props.charts?.replenishment_gap?.series || [])

const recommendedOrders = computed(() => inventoryRows.value.map((item) => {
  const recommendedQuantity = Math.max(
    Number(item.reorder_point || 0) * 2 - Number(item.qty_available || 0),
    Number(item.min_stock_level || 0) * 3 - Number(item.qty_available || 0),
    1,
  )

  return {
    id: item.item_id,
    name: item.item?.name,
    code: item.item?.code || item.item?.internal_code,
    current_stock: Number(item.qty_available || 0),
    recommended_qty: recommendedQuantity,
    supplier: item.item?.supplier,
    unit_price: Number(item.unit_price || item.item?.unit_price || item.item?.purchase_price || 0),
  }
}))

const topRecommendedOrders = computed(() => recommendedOrders.value.slice(0, 8))
const recommendedUnitTotal = computed(() => recommendedOrders.value.reduce((total, item) => total + item.recommended_qty, 0))
const recommendedValueTotal = computed(() => recommendedOrders.value.reduce((total, item) => total + (item.recommended_qty * item.unit_price), 0))

const severityMixChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#e5484d'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 0, horizontal: true } },
  xaxis: {
    categories: props.charts?.severity_mix?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const warehouseExposureChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.warehouse_exposure?.labels || [],
  colors: ['#e0902b', '#e5484d', '#14a3a8', '#7c5ce0', '#047857', '#6b7482'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value },
}))

const replenishmentGapChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#14a3a8'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 0, columnWidth: '48%' } },
  xaxis: {
    categories: props.charts?.replenishment_gap?.labels || [],
    labels: { rotate: -25, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function statusText(item) {
  if (Number(item.qty_available) <= 0) return 'Sem existências'
  if (Number(item.qty_available) <= Number(item.min_stock_level)) return 'Crítico'
  return 'Baixo'
}

function statusTone(item) {
  return statusText(item) === 'Baixo' ? 'wait' : 'bad'
}

function stockPercentage(item) {
  const maximum = Math.max(Number(item.reorder_point || 0) * 2, Number(item.min_stock_level || 0) * 3, Number(item.qty_available || 0), 1)
  return Math.min(Math.max((Number(item.qty_available || 0) / maximum) * 100, 0), 100)
}

function reorderGap(item) {
  return Math.max(Number(item.reorder_point || 0) - Number(item.qty_available || 0), 0)
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function sortLabel(sortBy) {
  return {
    current_stock: 'Existências actuais',
    reorder_point: 'Ponto de reposição',
    item_name: 'Nome do item',
  }[sortBy] || 'Severidade'
}

function formatMoney(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

function clearFilters() {
  Object.assign(filters, {
    warehouse_id: '',
    category_id: '',
    severity: '',
    sort_by: 'severity',
  })
}

function generateOrder() {
  if (!recommendedOrders.value.length) return
  router.visit(route('vap-inventory.orders.create', {
    items: recommendedOrders.value.map((item) => ({ item_id: item.id, qty: item.recommended_qty })),
  }))
}

function createOrderForItem(item) {
  router.visit(route('vap-inventory.orders.create', {
    items: [{
      item_id: item.item_id,
      qty: Math.max(Number(item.reorder_point || 0) * 2 - Number(item.qty_available || 0), 1),
    }],
  }))
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.low-stock'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
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
