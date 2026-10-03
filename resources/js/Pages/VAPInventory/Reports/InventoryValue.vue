<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Relatórios' }, { title: 'Valor' }]"
      title="Valor do inventário"
      :lede="lede"
    >
      <template #actions>
        <InventoryReportExportButton report-type="inventory_value" :filters="filters" />
      </template>
    </PageHeader>

    <form class="pl-filter" role="search" aria-label="Filtrar posições valorizadas" @submit.prevent>
      <label for="inventory-value-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="inventory-value-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="nome ou código do item" />
      <div class="w-52">
        <BaseSelect v-model="filters.category_id" aria-label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-52">
        <BaseSelect v-model="filters.warehouse_id" aria-label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-44">
        <BaseSelect v-model="filters.sort_direction" aria-label="Ordenação por quantidade">
          <option value="desc">Maior primeiro</option>
          <option value="asc">Menor primeiro</option>
        </BaseSelect>
      </div>
      <span v-if="loading" class="pl-k pl-faint" role="status">A actualizar…</span>
      <button v-if="hasActiveFilters" type="button" class="ds-chip" @click="clearFilters">
        Limpar filtros
        <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </form>

    <dl class="pl-panel pl-facts pl-facts-2 mb-8" aria-label="Resumo da valorização">
      <div v-for="card in summaryCards" :key="card.label" class="pl-fact">
        <dt>{{ card.label }}</dt>
        <dd>
          <span class="pl-num font-medium">{{ card.value }}</span>
          <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ card.detail }}</span>
        </dd>
      </div>
    </dl>

    <div class="mb-8 grid gap-6 xl:grid-cols-2">
      <section class="pl-panel min-w-0 xl:row-span-2" aria-labelledby="value-category-chart">
        <div class="pl-panel-head">
          <h2 id="value-category-chart" class="pl-k">Valor por categoria</h2>
          <span class="pl-k pl-faint">{{ categoryValueTotal }} categorias</span>
        </div>
        <div class="min-h-72 p-4">
          <apexchart type="bar" height="288" :options="categoryValueChartOptions" :series="categoryValueChartSeries" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="value-warehouse-chart">
        <div class="pl-panel-head">
          <h2 id="value-warehouse-chart" class="pl-k">Exposição por armazém</h2>
          <span class="pl-k pl-faint">{{ warehouseValueTotal }} locais</span>
        </div>
        <div class="min-h-64 p-4">
          <apexchart type="donut" height="256" :options="warehouseValueChartOptions" :series="warehouseValueChartSeries" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="value-top-chart">
        <div class="pl-panel-head">
          <h2 id="value-top-chart" class="pl-k">Itens de maior valor</h2>
        </div>
        <div class="min-h-56 p-4">
          <apexchart type="bar" height="224" :options="topItemValueChartOptions" :series="topItemValueChartSeries" />
        </div>
      </section>
    </div>

    <div class="mb-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="pl-panel min-w-0" aria-labelledby="value-positions" :aria-busy="loading">
        <div class="pl-panel-head">
          <h2 id="value-positions" class="pl-k">Posições de inventário</h2>
          <span class="pl-k pl-faint">{{ inventory.total || inventoryRows.length }} registos</span>
        </div>
        <div v-if="loading" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6" role="status">
          <span class="pl-k">A actualizar o relatório…</span>
        </div>
        <DataTable v-else-if="inventoryRows.length">
          <thead>
            <tr>
              <th scope="col">Item</th>
              <th scope="col">Categoria</th>
              <th scope="col">Armazém</th>
              <th scope="col" class="text-right">Quantidade</th>
              <th scope="col" class="text-right">Valor estimado</th>
              <th scope="col"><span class="sr-only">Acções</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="position in inventoryRows" :key="position.id">
              <td>
                <Link :href="route('vap-inventory.items.show', position.item_id)" class="font-medium hover:text-[var(--pl-accent-text)]">{{ position.item?.name || 'Item sem identificação' }}</Link>
                <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ position.item?.code || 'Sem código' }}</span>
              </td>
              <td>{{ position.item?.category?.name || 'Sem categoria' }}</td>
              <td>
                {{ position.warehouse?.name || 'N/D' }}
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ position.warehouse?.location?.name || 'Sem localização' }}</span>
              </td>
              <td class="pl-num text-right">{{ position.qty_available }} {{ position.item?.unit?.code || 'sem unidade' }}</td>
              <td class="text-right">
                <span class="pl-num block font-medium">{{ positionValue(position) }}</span>
                <span class="block text-[12px] text-[var(--pl-muted)]">{{ unitCost(position) === null ? 'Custo não definido' : `${formatCurrency(unitCost(position))} / ${position.item?.unit?.code || 'unidade'}` }}</span>
              </td>
              <td class="text-right">
                <Link :href="route('vap-inventory.items.show', position.item_id)" class="ds-table-action" :aria-label="`Abrir item ${position.item?.name || position.item_id}`">
                  <EyeIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
              </td>
            </tr>
          </tbody>
        </DataTable>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem posições valorizadas</span>
          <p class="text-sm text-[var(--pl-muted)]">Ajuste os filtros ou confirme que há existências disponíveis.</p>
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
        <section class="pl-panel" aria-labelledby="value-basis">
          <div class="pl-panel-head">
            <h2 id="value-basis" class="pl-k">Base de cálculo</h2>
          </div>
          <p class="px-4 py-3 text-[13px] leading-6 text-[var(--pl-muted)]">
            O valor estimado usa o custo padrão de cada item ou, na sua ausência, o último preço de compra. Itens sem custo registado contribuem com zero; confirme-os antes do fecho contabilístico.
          </p>
        </section>

        <section class="pl-panel" aria-labelledby="value-top-categories">
          <div class="pl-panel-head">
            <h2 id="value-top-categories" class="pl-k">Categorias com maior exposição</h2>
          </div>
          <ol v-if="summaryByCategory.length">
            <li v-for="(category, index) in summaryByCategory.slice(0, 6)" :key="category.category_name || index" class="pl-row">
              <span class="flex min-w-0 items-center gap-3">
                <span class="pl-num pl-faint w-5 shrink-0 text-[12px]">{{ String(index + 1).padStart(2, '0') }}</span>
                <span class="min-w-0">
                  <span class="block truncate font-medium">{{ category.category_name || 'Sem categoria' }}</span>
                  <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ category.unique_items }} itens</span>
                </span>
              </span>
              <span class="pl-num text-[12.5px]">{{ formatCurrency(category.total_value) }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por categoria.</p>
        </section>

        <section class="pl-panel" aria-labelledby="value-top-items">
          <div class="pl-panel-head">
            <h2 id="value-top-items" class="pl-k">Posições de maior valor</h2>
          </div>
          <ol v-if="topValuableItems.length">
            <li v-for="item in topValuableItems.slice(0, 6)" :key="item.item_id" class="pl-row">
              <span class="min-w-0">
                <span class="block truncate font-medium">{{ item.item?.name || `Item #${item.item_id}` }}</span>
                <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ item.item?.code || 'Sem código' }}</span>
              </span>
              <span class="pl-num text-[12.5px]">{{ formatCurrency(item.total_value) }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem posições valorizadas.</p>
        </section>
      </aside>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <section class="pl-panel min-w-0" aria-labelledby="value-by-category">
        <div class="pl-panel-head">
          <h2 id="value-by-category" class="pl-k">Resumo por categoria</h2>
          <span class="pl-k pl-faint">Reconciliação</span>
        </div>
        <DataTable v-if="summaryByCategory.length">
          <thead>
            <tr>
              <th scope="col">Categoria</th>
              <th scope="col" class="text-right">Itens</th>
              <th scope="col" class="text-right">Valor</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in summaryByCategory" :key="category.category_name">
              <td class="font-medium">{{ category.category_name || 'Sem categoria' }}</td>
              <td class="pl-num text-right">{{ category.unique_items }}</td>
              <td class="pl-num text-right">{{ formatCurrency(category.total_value) }}</td>
            </tr>
          </tbody>
        </DataTable>
        <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por categoria.</p>
      </section>

      <section class="pl-panel min-w-0" aria-labelledby="value-by-warehouse">
        <div class="pl-panel-head">
          <h2 id="value-by-warehouse" class="pl-k">Resumo por armazém</h2>
          <span class="pl-k pl-faint">Reconciliação</span>
        </div>
        <DataTable v-if="summaryByWarehouse.length">
          <thead>
            <tr>
              <th scope="col">Armazém</th>
              <th scope="col" class="text-right">Itens</th>
              <th scope="col" class="text-right">Valor</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="warehouse in summaryByWarehouse" :key="warehouse.warehouse_name">
              <td class="font-medium">{{ warehouse.warehouse_name || 'Sem armazém' }}</td>
              <td class="pl-num text-right">{{ warehouse.unique_items }}</td>
              <td class="pl-num text-right">{{ formatCurrency(warehouse.total_value) }}</td>
            </tr>
          </tbody>
        </DataTable>
        <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por armazém.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/pagination.vue'
import InventoryReportExportButton from '@/Components/vap-inventory/InventoryReportExportButton.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { Eye as EyeIcon, X as XMarkIcon } from '@lucide/vue'

const props = defineProps({
  inventory: { type: Object, default: () => ({ data: [] }) },
  summaryByCategory: { type: Array, default: () => [] },
  summaryByWarehouse: { type: Array, default: () => [] },
  topValuableItems: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
})

const loading = ref(false)
const isDarkMode = ref(false)
let themeObserver

const filters = reactive({
  category_id: props.filters?.category_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: 'qty_available',
  sort_direction: props.filters?.sort_direction ?? 'desc',
})

const inventoryRows = computed(() => props.inventory?.data || [])
const chartTextColor = computed(() => isDarkMode.value ? '#d7dbe0' : '#6b7482')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#eef0f3')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const summaryCards = computed(() => [
  {
    label: 'Valor estimado',
    value: formatCurrency(props.stats?.total_value),
    detail: 'Saldo financeiro de referência',
  },
  {
    label: 'Itens únicos',
    value: props.stats?.unique_items || 0,
    detail: 'Referências com existências',
  },
  {
    label: 'Valor médio',
    value: formatCurrency(props.stats?.avg_item_value),
    detail: 'Por referência única',
  },
  {
    label: 'Maior categoria',
    value: props.stats?.highest_value_category?.category_name || 'Sem dados',
    detail: formatCurrency(props.stats?.highest_value_category?.total_value),
  },
])

const lede = computed(() => {
  const positions = Number(props.inventory?.total ?? inventoryRows.value.length)

  return `${formatCurrency(props.stats?.total_value)} estimados em ${positions} ${positions === 1 ? 'posição' : 'posições'} de existências, pelo custo padrão ou pelo último preço de compra.`
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filters.category_id) pills.push(`Categoria: ${categoryName(filters.category_id)}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
const categoryValueChartSeries = computed(() => props.charts?.category_value_breakdown?.series || [])
const categoryValueTotal = computed(() => props.charts?.category_value_breakdown?.labels?.length || 0)
const warehouseValueChartSeries = computed(() => (props.charts?.warehouse_value_breakdown?.series?.[0]?.data || []).map((value) => Number(value || 0)))
const warehouseValueTotal = computed(() => props.charts?.warehouse_value_breakdown?.labels?.length || 0)
const topItemValueChartSeries = computed(() => props.charts?.top_item_value?.series || [])

const categoryValueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 0, horizontal: true } },
  xaxis: {
    categories: props.charts?.category_value_breakdown?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { formatter: (value) => compactCurrency(value), style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { maxWidth: 220, style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value, y: { formatter: formatCurrency } },
  legend: { show: false },
}))

const warehouseValueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.warehouse_value_breakdown?.labels || [],
  colors: ['#14a3a8', '#0f766e', '#7c5ce0', '#e0902b', '#e5484d', '#6b7482'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value, y: { formatter: formatCurrency } },
}))

const topItemValueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#1d4ed8'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 0, columnWidth: '48%' } },
  xaxis: {
    categories: props.charts?.top_item_value?.labels || [],
    labels: { rotate: -25, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { formatter: compactCurrency, style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value, y: { formatter: formatCurrency } },
  legend: { show: false },
}))

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

function compactCurrency(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value || 0))
}

function unitCost(position) {
  const value = position.item?.standard_cost ?? position.item?.last_purchase_price
  return value === null || value === undefined ? null : Number(value)
}

function positionValue(position) {
  const cost = unitCost(position)
  return cost === null ? 'Não valorizado' : formatCurrency(Number(position.qty_available || 0) * cost)
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function clearFilters() {
  Object.assign(filters, {
    category_id: '',
    warehouse_id: '',
    search: '',
    sort_by: 'qty_available',
    sort_direction: 'desc',
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.inventory-value'), value, {
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
