<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Stock movement"
      title="Relatório de Movimento de Estoque"
      :description="filterPeriod ? `Monitore entradas, saídas, ajustes e transferências · ${filterPeriod}` : 'Monitore entradas, saídas, ajustes e transferências com rastreabilidade operacional.'"
    >
      <template #actions>
        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-sm font-semibold text-blue-900 ring-1 ring-blue-700/10 dark:bg-white/10 dark:text-blue-100 dark:ring-white/10">
          {{ stats.total_transactions }} transações
        </span>
        <button
          @click="exportReport"
          class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
        >
          <ArrowDownTrayIcon class="h-5 w-5" />
          Exportar
        </button>
      </template>
    </ModuleHero>

    <!-- FILTERS SECTION -->
    <ModuleCard title="Filtros de movimento" description="Combine período, item, armazém e visualização para auditar o fluxo de stock.">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Date Range -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <CalendarIcon class="h-4 w-4" />
            Intervalo de Datas
          </label>
          <div class="flex gap-2">
            <BaseInput
              type="date"
              v-model="filters.date_from"
            />
            <BaseInput
              type="date"
              v-model="filters.date_to"
            />
          </div>
        </div>

        <!-- Item Filter -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <CubeIcon class="h-4 w-4" />
            Item
          </label>
          <BaseSelect
            v-model="filters.item_id"
          >
            <option value="">Todos os Itens</option>
            <option v-for="item in items" :key="item.id" :value="item.id">
              {{ item.name }} ({{ item.code }})
            </option>
          </BaseSelect>
        </div>

        <!-- Warehouse Filter -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <BuildingStorefrontIcon class="h-4 w-4" />
            Armazém
          </label>
          <BaseSelect
            v-model="filters.warehouse_id"
          >
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- View Type -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <EyeIcon class="h-4 w-4" />
            Visualizar
          </label>
          <BaseSelect
            v-model="filters.view"
            @change="applyFilters"
          >
            <option value="detailed">Visão Detalhada</option>
            <option value="summary">Visão Resumida</option>
          </BaseSelect>
        </div>
      </div>

      <!-- Search -->
      <div class="mt-4">
        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
          Pesquisar
        </label>
        <BaseInput
          type="text"
          v-model="filters.search"
          placeholder="Pesquisar por item, código ou utilizador..."
          @keyup.enter="applyFilters"
        />
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
        <button
          @click="resetFilters"
          class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
        >
          Redefinir
        </button>
        <button
          @click="applyFilters"
          class="rounded-2xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          Aplicar Filtros
        </button>
      </div>
    </ModuleCard>

    <!-- SUMMARY VIEW -->
    <div v-if="filters.view === 'summary' && summary" class="space-y-6">
      <!-- SUMMARY STATS -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total de Entrada</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.total_in }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <ArrowDownIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total de Saída</p>
              <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.total_out }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
              <ArrowUpIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Movimento Total</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ stats.net_movement }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <ArrowsRightLeftIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Transações Diárias Médias</p>
              <p class="mt-2 text-3xl font-bold text-purple-600">{{ stats.avg_daily_transactions }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
              <ChartBarIcon class="h-6 w-6 text-purple-600" />
            </div>
          </div>
        </div>
      </div>

      <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ritmo diário</h2>
              <p class="mt-1 text-sm text-slate-500">
                Evolução diária de entradas, saídas e volume operacional.
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-white/5">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Dias monitorizados</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ dailyActivityTotal }}</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="line" height="300" :options="dailyActivityChartOptions" :series="dailyActivityChartSeries" />
          </div>
        </article>

        <div class="grid gap-6">
          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Direção do movimento</h2>
                <p class="mt-1 text-sm text-slate-500">Entradas, saídas e saldo líquido no período.</p>
              </div>
            </div>

            <div class="mt-6">
              <apexchart type="bar" height="250" :options="directionBreakdownChartOptions" :series="directionBreakdownChartSeries" />
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Mix operacional</h2>
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
      <ModuleCard class="overflow-hidden" title="Resumo Diário">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-900/80">
              <tr>
                <th :class="tableHeadClass">Data</th>
                <th :class="tableHeadClass">Transações</th>
                <th :class="tableHeadClass">Entrada</th>
                <th :class="tableHeadClass">Saída</th>
                <th :class="tableHeadClass">Movimento Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
              <tr v-for="day in summary" :key="day.date" class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20">
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ formatDate(day.date) }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
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
      </ModuleCard>
    </div>

    <!-- DETAILED VIEW -->
    <div v-else>
      <!-- MOVEMENTS TABLE -->
      <ModuleCard class="overflow-hidden" title="Movimentos de Estoque">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2 dark:text-white">
              <ListBulletIcon class="h-5 w-5 text-blue-900" />
              Movimentos de Estoque
              <span class="text-sm font-normal text-slate-500 ml-2 dark:text-slate-400">
                ({{ transactions.total }} registros)
              </span>
            </h2>
            <div class="flex items-center gap-4">
              <div class="text-sm text-slate-600 dark:text-slate-300">
                Ordenar por:
                <BaseSelect v-model="filters.sort_by" @change="applyFilters" class="ml-2 inline-block w-auto min-w-32 text-sm">
                  <option value="created_at">Data</option>
                  <option value="qty">Quantidade</option>
                </BaseSelect>
                <BaseSelect v-model="filters.sort_direction" @change="applyFilters" class="ml-2 inline-block w-auto min-w-24 text-sm">
                  <option value="desc">Desc</option>
                  <option value="asc">Asc</option>
                </BaseSelect>
              </div>
            </div>
          </div>
        </div>

        <!-- LOADING STATE -->
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
          <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando...</p>
        </div>

        <!-- TRANSACTIONS TABLE -->
        <div v-else-if="transactions.data && transactions.data.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-900/80">
              <tr>
                <th :class="tableHeadClass">Data & Hora</th>
                <th :class="tableHeadClass">Item</th>
                <th :class="tableHeadClass">Categoria</th>
                <th :class="tableHeadClass">Armazém</th>
                <th :class="tableHeadClass">Tipo de Transação</th>
                <th :class="tableHeadClass">Quantidade</th>
                <th :class="tableHeadClass">Usuário</th>
                <th :class="tableHeadClass">Observações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
              <tr 
                v-for="transaction in transactions.data" 
                :key="transaction.id"
                class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20"
              >
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ formatDateTime(transaction.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-8 w-8">
                      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                        <CubeIcon class="h-4 w-4 text-blue-900 dark:text-blue-300" />
                      </div>
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-slate-900 dark:text-white">{{ transaction.item?.name }}</div>
                      <div class="text-xs text-slate-500 dark:text-slate-400">{{ transaction.item?.code }}</div>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                  {{ transaction.item?.category?.name || 'N/A' }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
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
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ transaction.user?.name }}
                </td>
                <td class="max-w-xs px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                  <div class="truncate">{{ transaction.notes }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- EMPTY STATE -->
        <div v-else class="p-12 text-center">
          <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
          <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
            Nenhum movimento de estoque encontrado
          </h3>
          <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Tente ajustar seus filtros ou critérios de pesquisa
          </p>
        </div>

        <!-- PAGINATION -->
        <div v-if="transactions.data && transactions.data.length > 0" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-500 dark:text-slate-400">
              Mostrando {{ transactions.from }} a {{ transactions.to }} de {{ transactions.total }} registros
            </div>
            <div class="flex gap-2">
              <button
                @click="previousPage"
                :disabled="!transactions.prev_page_url"
                :class="[
                  'rounded-lg px-3 py-2 text-sm font-medium',
                  transactions.prev_page_url
                    ? 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                    : 'text-slate-400 cursor-not-allowed dark:text-slate-600'
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
                    ? 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                    : 'text-slate-400 cursor-not-allowed dark:text-slate-600'
                ]"
              >
                Próxima
              </button>
            </div>
          </div>
        </div>
      </ModuleCard>
    </div>

    <!-- ADDITIONAL STATS -->
    <div v-if="filters.view === 'detailed'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Itens Mais Activos</p>
            <p v-if="stats.most_active_item" class="mt-2 text-lg font-bold text-blue-900">
              {{ stats.most_active_item.item?.name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              {{ stats.most_active_item?.transaction_count || 0 }} transações
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <StarIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Usuários Mais Activos</p>
            <p v-if="stats.most_active_user" class="mt-2 text-lg font-bold text-green-900">
              {{ stats.most_active_user.user?.name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              {{ stats.most_active_user?.transaction_count || 0 }} transações
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
            <UserIcon class="h-6 w-6 text-green-900" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Transações Diárias Médias</p>
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
import { ref, computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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

const tableHeadClass = 'px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300'
const isDarkMode = ref(false)
let themeObserver

const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const syncDarkMode = () => {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

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
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#1e3a8a'],
  dataLabels: { enabled: false },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '50%',
    },
  },
  xaxis: {
    categories: props.charts?.direction_breakdown?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: {
      style: { colors: chartTextColor.value },
    },
  },
  yaxis: {
    labels: {
      style: { colors: chartTextColor.value },
    },
  },
  tooltip: {
    theme: chartTooltipTheme.value,
  },
  legend: { show: false },
}))

const typeMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.type_mix?.labels || [],
  colors: ['#15803d', '#dc2626', '#ea580c', '#2563eb'],
  legend: {
    position: 'bottom',
    labels: {
      colors: chartTextColor.value,
    },
  },
  dataLabels: {
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  stroke: {
    width: 0,
  },
  tooltip: {
    theme: chartTooltipTheme.value,
  },
}))

const dailyActivityChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#15803d', '#dc2626', '#1e3a8a'],
  dataLabels: { enabled: false },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  stroke: {
    curve: 'smooth',
    width: [3, 3, 2],
  },
  xaxis: {
    categories: props.charts?.daily_activity?.labels || [],
    labels: {
      rotate: -20,
      trim: true,
      style: { colors: chartTextColor.value },
    },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: {
    labels: {
      style: { colors: chartTextColor.value },
    },
  },
  tooltip: {
    theme: chartTooltipTheme.value,
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
    'stock_in': 'bg-green-100 text-green-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    'stock_adjustment_add': 'bg-green-100 text-green-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    'stock_out': 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
    'stock_adjustment_remove': 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
    'consumption': 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
    'transfer': 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200'
  }
  return classMap[typeCode] || 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
}

function getQuantityClass(typeCode, qty) {
  if (['stock_in', 'stock_adjustment_add'].includes(typeCode)) {
    return 'text-green-600'
  } else if (['stock_out', 'stock_adjustment_remove', 'consumption'].includes(typeCode)) {
    return 'text-red-600'
  }
  return 'text-slate-600 dark:text-slate-300'
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
  syncDarkMode()

  if (typeof MutationObserver !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }

  // Initialize filters from props
  if (props.filters) {
    Object.keys(props.filters).forEach(key => {
      if (filters.hasOwnProperty(key)) {
        filters[key] = props.filters[key]
      }
    })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
