<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ArrowsRightLeftIcon class="h-7 w-7 text-blue-900" />
            Relatório de Movimento de Estoque
          </h1>
          <p class="mt-2 text-gray-600">
            Monitore todos os movimentos de estoque, incluindo a entrada e saída, ajustes e transferências
            <span v-if="filterPeriod" class="font-semibold text-blue-900">
              {{ filterPeriod }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_transactions }} transações
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
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Date Range -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <CalendarIcon class="h-4 w-4" />
            Intervalo de Datas
          </label>
          <div class="flex gap-2">
            <input
              type="date"
              v-model="filters.date_from"
              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
            />
            <input
              type="date"
              v-model="filters.date_to"
              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
            />
          </div>
        </div>

        <!-- Item Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <CubeIcon class="h-4 w-4" />
            Item
          </label>
          <select
            v-model="filters.item_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Itens</option>
            <option v-for="item in items" :key="item.id" :value="item.id">
              {{ item.name }} ({{ item.code }})
            </option>
          </select>
        </div>

        <!-- Warehouse Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <BuildingStorefrontIcon class="h-4 w-4" />
            Armazém
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

        <!-- View Type -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <EyeIcon class="h-4 w-4" />
            Visualizar
          </label>
          <select
            v-model="filters.view"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
            @change="applyFilters"
          >
            <option value="detailed">Visão Detalhada</option>
            <option value="summary">Visão Resumida</option>
          </select>
        </div>
      </div>

      <!-- Search -->
      <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Pesquisar
        </label>
        <input
          type="text"
          v-model="filters.search"
          placeholder="Search by item name, code, or user..."
          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          @keyup.enter="applyFilters"
        />
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

    <!-- SUMMARY VIEW -->
    <div v-if="filters.view === 'summary' && summary" class="space-y-6">
      <!-- SUMMARY STATS -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total de Entrada</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.total_in }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <ArrowDownIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total de Saída</p>
              <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.total_out }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
              <ArrowUpIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Movimento Total</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ stats.net_movement }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <ArrowsRightLeftIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Transações Diárias Médias</p>
              <p class="mt-2 text-3xl font-bold text-purple-600">{{ stats.avg_daily_transactions }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
              <ChartBarIcon class="h-6 w-6 text-purple-600" />
            </div>
          </div>
        </div>
      </div>

      <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Ritmo diário</h2>
              <p class="mt-1 text-sm text-slate-500">
                Evolução diária de entradas, saídas e volume operacional.
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Dias monitorizados</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ dailyActivityTotal }}</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="line" height="300" :options="dailyActivityChartOptions" :series="dailyActivityChartSeries" />
          </div>
        </article>

        <div class="grid gap-6">
          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">Direção do movimento</h2>
                <p class="mt-1 text-sm text-slate-500">Entradas, saídas e saldo líquido no período.</p>
              </div>
            </div>

            <div class="mt-6">
              <apexchart type="bar" height="250" :options="directionBreakdownChartOptions" :series="directionBreakdownChartSeries" />
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">Mix operacional</h2>
                <p class="mt-1 text-sm text-slate-500">Peso relativo entre entradas, saídas, consumo e transferências.</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
                {{ typeMixTotal }} movimentos
              </span>
            </div>

            <div class="mt-6">
              <apexchart type="donut" height="250" :options="typeMixChartOptions" :series="typeMixChartSeries" />
            </div>
          </article>
        </div>
      </section>

      <!-- SUMMARY TABLE -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <TableCellsIcon class="h-5 w-5 text-blue-900" />
            Resumo Diário
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transações</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entrada</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saída</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Movimento Total</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="day in summary" :key="day.date" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(day.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ day.total_transactions }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                  +{{ day.total_in }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                  -{{ day.total_out }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="day.total_in - day.total_out >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ day.total_in - day.total_out >= 0 ? '+' : '' }}{{ day.total_in - day.total_out }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- DETAILED VIEW -->
    <div v-else>
      <!-- MOVEMENTS TABLE -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <ListBulletIcon class="h-5 w-5 text-blue-900" />
              Movimentos de Estoque
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ transactions.total }} registros)
              </span>
            </h2>
            <div class="flex items-center gap-4">
              <div class="text-sm text-gray-600">
                Ordenar por:
                <select v-model="filters.sort_by" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                  <option value="created_at">Data</option>
                  <option value="qty">Quantidade</option>
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

        <!-- TRANSACTIONS TABLE -->
        <div v-else-if="transactions.data && transactions.data.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data & Hora</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Armazém</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo de Transação</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Observações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="transaction in transactions.data" 
                :key="transaction.id"
                class="hover:bg-gray-50"
              >
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDateTime(transaction.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-8 w-8">
                      <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <CubeIcon class="h-4 w-4 text-blue-900" />
                      </div>
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ transaction.item?.name }}</div>
                      <div class="text-xs text-gray-500">{{ transaction.item?.code }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ transaction.item?.category?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ transaction.warehouse?.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    getTransactionTypeClass(transaction.type?.code)
                  ]">
                    {{ transaction.type?.name }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold" :class="getQuantityClass(transaction.type?.code, transaction.qty)">
                  {{ getQuantitySign(transaction.type?.code) }}{{ transaction.qty }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ transaction.user?.name }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                  <div class="truncate">{{ transaction.notes }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- EMPTY STATE -->
        <div v-else class="p-12 text-center">
          <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            Nenhum movimento de estoque encontrado
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            Tente ajustar seus filtros ou critérios de pesquisa
          </p>
        </div>

        <!-- PAGINATION -->
        <div v-if="transactions.data && transactions.data.length > 0" class="border-t border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
              Mostrando {{ transactions.from }} a {{ transactions.to }} de {{ transactions.total }} registros
            </div>
            <div class="flex gap-2">
              <button
                @click="previousPage"
                :disabled="!transactions.prev_page_url"
                :class="[
                  'rounded-lg px-3 py-2 text-sm font-medium',
                  transactions.prev_page_url 
                    ? 'text-gray-700 hover:bg-gray-100' 
                    : 'text-gray-400 cursor-not-allowed'
                ]"
              >
                Anterior
              </button>
              <button
                @click="nextPage"
                :disabled="!transactions.next_page_url"
                :class="[
                  'rounded-lg px-3 py-2 text-sm font-medium',
                  transactions.next_page_url 
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
    </div>

    <!-- ADDITIONAL STATS -->
    <div v-if="filters.view === 'detailed'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Itens Mais Activos</p>
            <p v-if="stats.most_active_item" class="mt-2 text-lg font-bold text-blue-900">
              {{ stats.most_active_item.item?.name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-gray-500">
              {{ stats.most_active_item?.transaction_count || 0 }} transações
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <StarIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Usuários Mais Activos</p>
            <p v-if="stats.most_active_user" class="mt-2 text-lg font-bold text-green-900">
              {{ stats.most_active_user.user?.name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-gray-500">
              {{ stats.most_active_user?.transaction_count || 0 }} transações
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
            <UserIcon class="h-6 w-6 text-green-900" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Transações Diárias Médias</p>
            <p class="mt-2 text-3xl font-bold text-purple-600">{{ stats.avg_daily_transactions }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
            <ChartBarIcon class="h-6 w-6 text-purple-600" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  CalendarIcon,
  CubeIcon,
  BuildingStorefrontIcon,
  EyeIcon,
  ListBulletIcon,
  DocumentMagnifyingGlassIcon,
  ArrowDownIcon,
  ArrowUpIcon,
  ArrowsRightLeftIcon,
  ChartBarIcon,
  TableCellsIcon,
  StarIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  transactions: {
    type: Object,
    default: () => ({ data: [], links: {} })
  },
  summary: {
    type: Array,
    default: () => []
  },
  items: {
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
  date_from: '',
  date_to: '',
  item_id: '',
  warehouse_id: '',
  search: '',
  view: 'detailed',
  sort_by: 'created_at',
  sort_direction: 'desc'
})

const filterPeriod = computed(() => {
  if (filters.date_from && filters.date_to) {
    return `${filters.date_from} to ${filters.date_to}`
  }
  return ''
})

const directionBreakdownChartSeries = computed(() => props.charts?.direction_breakdown?.series || [])
const typeMixChartSeries = computed(() => props.charts?.type_mix?.series || [])
const typeMixTotal = computed(() => (props.charts?.type_mix?.series || []).reduce((total, value) => total + Number(value || 0), 0))
const dailyActivityChartSeries = computed(() => props.charts?.daily_activity?.series || [])
const dailyActivityTotal = computed(() => props.charts?.daily_activity?.labels?.length || 0)

const directionBreakdownChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#1e3a8a'],
  dataLabels: { enabled: false },
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '50%',
    },
  },
  xaxis: {
    categories: props.charts?.direction_breakdown?.labels || [],
  },
  legend: { show: false },
}))

const typeMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: props.charts?.type_mix?.labels || [],
  colors: ['#15803d', '#dc2626', '#ea580c', '#2563eb'],
  legend: {
    position: 'bottom',
  },
  dataLabels: {
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  stroke: {
    width: 0,
  },
}))

const dailyActivityChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#15803d', '#dc2626', '#1e3a8a'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: [3, 3, 2],
  },
  xaxis: {
    categories: props.charts?.daily_activity?.labels || [],
    labels: {
      rotate: -20,
      trim: true,
    },
  },
}))

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getTransactionTypeClass(typeCode) {
  const classMap = {
    'stock_in': 'bg-green-100 text-green-800',
    'stock_adjustment_add': 'bg-green-100 text-green-800',
    'stock_out': 'bg-red-100 text-red-800',
    'stock_adjustment_remove': 'bg-red-100 text-red-800',
    'consumption': 'bg-red-100 text-red-800',
    'transfer': 'bg-blue-100 text-blue-800'
  }
  return classMap[typeCode] || 'bg-gray-100 text-gray-800'
}

function getQuantityClass(typeCode, qty) {
  if (['stock_in', 'stock_adjustment_add'].includes(typeCode)) {
    return 'text-green-600'
  } else if (['stock_out', 'stock_adjustment_remove', 'consumption'].includes(typeCode)) {
    return 'text-red-600'
  }
  return 'text-gray-600'
}

function getQuantitySign(typeCode) {
  if (['stock_in', 'stock_adjustment_add'].includes(typeCode)) {
    return '+'
  } else if (['stock_out', 'stock_adjustment_remove', 'consumption'].includes(typeCode)) {
    return '-'
  }
  return ''
}

function applyFilters() {
  filters.get(route('vap-inventory.reports.stock-movement'), {
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
    report_type: 'stock_movement',
    format: 'pdf',
    filters: filters.data()
  }
  
  router.post(route('vap-inventory.reports.export'), exportFilters)
}

function previousPage() {
  if (props.transactions.prev_page_url) {
    router.visit(props.transactions.prev_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false
    })
  }
}

function nextPage() {
  if (props.transactions.next_page_url) {
    router.visit(props.transactions.next_page_url, {
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
