<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CurrencyDollarIcon class="h-7 w-7 text-blue-900" />
            Relatório de Valor de Inventário
          </h1>
          <p class="mt-2 text-gray-600">
            Veja o valor de inventário por categoria, armazém e item
            <span class="font-semibold text-blue-900">
              {{ totalValueFormatted }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_items }} itens totais
          </span>
          <button
            @click="exportReport"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowDownTrayIcon class="h-5 w-5" />
            Exportar
          </button>
        </div>
      </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Category Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <RectangleStackIcon class="h-4 w-4" />
            Categoria
          </label>
          <select
            v-model="filters.category_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- Warehouse Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <BuildingStorefrontIcon class="h-4 w-4" />
            Armazéns
          </label>
          <select
            v-model="filters.warehouse_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </select>
        </div>

        <!-- Search -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Pesquisar Itens
          </label>
          <input
            type="text"
            v-model="filters.search"
            placeholder="Search by item name or code..."
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
            @keyup.enter="applyFilters"
          />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
        <button
          @click="resetFilters"
          class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
        >
          Redefinir
        </button>
        <button
          @click="applyFilters"
          class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
        >
          Aplicar Filtros
        </button>
      </div>
    </div>

    <!-- SUMMARY STATS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Valor de Inventário Totais</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ totalValueFormatted }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <CurrencyDollarIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Itens Únicos</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.unique_items }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
            <CubeIcon class="h-6 w-6 text-green-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Valor Médio de Itens</p>
            <p class="mt-2 text-3xl font-bold text-purple-600">{{ avgItemValueFormatted }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
            <CalculatorIcon class="h-6 w-6 text-purple-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Categoria com Valor Mais Alto</p>
            <p v-if="stats.highest_value_category" class="mt-2 text-lg font-bold text-yellow-600 truncate">
              {{ stats.highest_value_category.category_name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-gray-500">
              {{ stats.highest_value_category?.total_value_formatted || 'AOA 0.00' }}
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <TrophyIcon class="h-6 w-6 text-yellow-600" />
          </div>
        </div>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Valor por categoria</h2>
            <p class="mt-1 text-sm text-slate-500">
              Onde o capital do inventário está mais concentrado entre famílias de itens.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Categorias monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ categoryValueTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="categoryValueChartOptions" :series="categoryValueChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Valor por armazém</h2>
              <p class="mt-1 text-sm text-slate-500">Distribuição do valor imobilizado pelos armazéns ativos.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
              {{ warehouseValueTotal }} armazéns
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="warehouseValueChartOptions" :series="warehouseValueChartSeries" />
          </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Itens de maior valor</h2>
              <p class="mt-1 text-sm text-slate-500">Top itens que mais pesam no valor total disponível.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="topItemValueChartOptions" :series="topItemValueChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <!-- INVENTORY TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <TableCellsIcon class="h-5 w-5 text-blue-900" />
            Itens de Inventário
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ inventory.total }} items)
            </span>
          </h2>
          <div class="flex items-center gap-4">
            <div class="text-sm text-gray-600">
              Ordenar por:
              <select v-model="filters.sort_by" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                <option value="qty_available">Quantidade</option>
                <option value="total_value">Valor</option>
              </select>
              <select v-model="filters.sort_direction" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                <option value="desc">Desc</option>
                <option value="asc">Asc</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- LOADING STATE -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
        <p class="mt-4 text-sm text-gray-500">Carregando...</p>
      </div>

      <!-- INVENTORY TABLE -->
      <div v-else-if="inventory.data && inventory.data.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Armazém</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Unitário</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Total</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="item in inventory.data" 
              :key="item.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <CubeIcon class="h-4 w-4 text-blue-900" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ item.item?.name }}</div>
                    <div class="text-xs text-gray-500">{{ item.item?.code }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ item.item?.category?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ item.warehouse?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-900">
                  {{ item.qty_available }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                AOA XX.XX
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-900">
                {{ formatCurrency(item.qty_available * 100) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  getStatusClass(item.status)
                ]">
                  {{ item.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-else class="p-12 text-center">
        <CurrencyDollarIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum item de inventário encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Tente ajustar seus filtros ou critérios de pesquisa
        </p>
      </div>

      <!-- PAGINATION -->
      <div v-if="inventory.data && inventory.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Mostrando {{ inventory.from }} a {{ inventory.to }} de {{ inventory.total }} itens
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="!inventory.prev_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                inventory.prev_page_url 
                  ? 'text-gray-700 hover:bg-gray-100' 
                  : 'text-gray-400 cursor-not-allowed'
              ]"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="!inventory.next_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                inventory.next_page_url 
                  ? 'text-gray-700 hover:bg-gray-100' 
                  : 'text-gray-400 cursor-not-allowed'
              ]"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SUMMARY BY CATEGORY -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            Valor por Categoria
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Itens Únicos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Total</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="category in summaryByCategory" :key="category.category_name" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ category.category_name || 'Sem Categoria' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ category.total_quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ category.unique_items }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-900">
                  {{ formatCurrency(category.total_value) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <BuildingStorefrontIcon class="h-5 w-5 text-blue-900" />
            Valor por Armazém
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Armazém</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Itens Únicos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Total</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="warehouse in summaryByWarehouse" :key="warehouse.warehouse_name" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ warehouse.warehouse_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ warehouse.total_quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ warehouse.unique_items }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-900">
                  {{ formatCurrency(warehouse.total_value) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TOP VALUABLE ITEMS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <StarIcon class="h-5 w-5 text-blue-900" />
          Top 10 Dos Mais Valiosos
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor Total</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in topValuableItems" :key="item.item_id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <CubeIcon class="h-4 w-4 text-blue-900" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ item.item?.name }}</div>
                    <div class="text-xs text-gray-500">{{ item.item?.code }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ item.item?.category?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ item.total_quantity }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-900">
                {{ formatCurrency(item.total_value) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  CurrencyDollarIcon,
  ArrowDownTrayIcon,
  RectangleStackIcon,
  BuildingStorefrontIcon,
  TableCellsIcon,
  CubeIcon,
  CalculatorIcon,
  TrophyIcon,
  ChartBarIcon,
  StarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  inventory: {
    type: Object,
    default: () => ({ data: [], links: {} })
  },
  summaryByCategory: {
    type: Array,
    default: () => []
  },
  summaryByWarehouse: {
    type: Array,
    default: () => []
  },
  topValuableItems: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  charts: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  }
})

const loading = ref(false)
const filters = useForm({
  category_id: '',
  warehouse_id: '',
  search: '',
  sort_by: 'total_value',
  sort_direction: 'desc'
})

const totalValueFormatted = computed(() => {
  return formatCurrency(props.stats.total_value || 0)
})

const avgItemValueFormatted = computed(() => {
  return formatCurrency(props.stats.avg_item_value || 0)
})

const categoryValueChartSeries = computed(() => props.charts?.category_value_breakdown?.series || [])
const categoryValueTotal = computed(() => props.charts?.category_value_breakdown?.labels?.length || 0)

const warehouseValueChartSeries = computed(() => {
  const series = props.charts?.warehouse_value_breakdown?.series?.[0]?.data || []
  return series.map((value) => Number(value || 0))
})
const warehouseValueTotal = computed(() => props.charts?.warehouse_value_breakdown?.labels?.length || 0)

const topItemValueChartSeries = computed(() => props.charts?.top_item_value?.series || [])

const categoryValueChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  plotOptions: {
    bar: {
      borderRadius: 6,
      horizontal: true,
    },
  },
  xaxis: {
    categories: props.charts?.category_value_breakdown?.labels || [],
    labels: {
      formatter: (value) => formatCurrency(value),
    },
  },
  yaxis: {
    labels: {
      maxWidth: 220,
    },
  },
  tooltip: {
    y: {
      formatter: (value) => formatCurrency(value),
    },
  },
  legend: { show: false },
}))

const warehouseValueChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: props.charts?.warehouse_value_breakdown?.labels || [],
  colors: ['#1d4ed8', '#0f766e', '#7c3aed', '#ea580c', '#be123c', '#0891b2'],
  legend: {
    position: 'bottom',
  },
  dataLabels: {
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  tooltip: {
    y: {
      formatter: (value) => formatCurrency(value),
    },
  },
  stroke: {
    width: 0,
  },
}))

const topItemValueChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#1e3a8a'],
  dataLabels: { enabled: false },
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '48%',
    },
  },
  xaxis: {
    categories: props.charts?.top_item_value?.labels || [],
    labels: {
      rotate: -25,
      trim: true,
    },
  },
  yaxis: {
    labels: {
      formatter: (value) => formatCurrency(value),
    },
  },
  tooltip: {
    y: {
      formatter: (value) => formatCurrency(value),
    },
  },
  legend: { show: false },
}))

function formatCurrency(value) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'AOA',
    minimumFractionDigits: 2
  }).format(value)
}

function getStatusClass(status) {
  const statusMap = {
    'AVAILABLE': 'bg-green-100 text-green-800',
    'IN_USE': 'bg-blue-100 text-blue-800',
    'MAINTENANCE': 'bg-yellow-100 text-yellow-800',
    'CALIBRATION_DUE': 'bg-orange-100 text-orange-800',
    'EXPIRED': 'bg-red-100 text-red-800'
  }
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

function applyFilters() {
  filters.get(route('vap-inventory.reports.inventory-value'), {
    preserveScroll: true,
    preserveState: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false
  })
}

function resetFilters() {
  filters.reset()
  applyFilters()
}

function exportReport() {
  const exportFilters = {
    report_type: 'inventory_value',
    format: 'pdf',
    filters: filters.data()
  }
  
  router.post(route('vap-inventory.reports.export'), exportFilters)
}

function previousPage() {
  if (props.inventory.prev_page_url) {
    router.visit(props.inventory.prev_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false
    })
  }
}

function nextPage() {
  if (props.inventory.next_page_url) {
    router.visit(props.inventory.next_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false
    })
  }
}

onMounted(() => {
  // Initialize filters from props
  if (props.filters) {
    Object.keys(props.filters).forEach(key => {
      if (filters.hasOwnProperty(key)) {
        filters[key] = props.filters[key]
      }
    })
  }
})
</script>
