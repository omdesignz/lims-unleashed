<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Inventory valuation"
      title="Relatório de Valor de Inventário"
      :description="`Acompanhe valor por categoria, armazém e item com visão financeira consolidada · ${totalValueFormatted}`"
    >
      <template #actions>
        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-sm font-semibold text-blue-900 ring-1 ring-blue-700/10 dark:bg-white/10 dark:text-blue-100 dark:ring-white/10">
          {{ stats.total_items }} itens totais
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
    <ModuleCard title="Filtros de valorização" description="Filtre o valor de inventário por categoria, armazém e pesquisa operacional.">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Category Filter -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <RectangleStackIcon class="h-4 w-4" />
            Categoria
          </label>
          <BaseSelect
            v-model="filters.category_id"
          >
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- Warehouse Filter -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <BuildingStorefrontIcon class="h-4 w-4" />
            Armazéns
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

        <!-- Search -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Pesquisar Itens
          </label>
          <BaseInput
            type="text"
            v-model="filters.search"
            placeholder="Pesquisar por nome ou código do item..."
            @keyup.enter="applyFilters"
          />
        </div>
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

    <!-- SUMMARY STATS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Valor de Inventário Totais</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ totalValueFormatted }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <CurrencyDollarIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Itens Únicos</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.unique_items }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
            <CubeIcon class="h-6 w-6 text-green-600" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Valor Médio de Itens</p>
            <p class="mt-2 text-3xl font-bold text-purple-600">{{ avgItemValueFormatted }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
            <CalculatorIcon class="h-6 w-6 text-purple-600" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Categoria com Valor Mais Alto</p>
            <p v-if="stats.highest_value_category" class="mt-2 text-lg font-bold text-yellow-600 truncate">
              {{ stats.highest_value_category.category_name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
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
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valor por categoria</h2>
            <p class="mt-1 text-sm text-slate-500">
              Onde o capital do inventário está mais concentrado entre famílias de itens.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-white/5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Categorias monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ categoryValueTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="categoryValueChartOptions" :series="categoryValueChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Valor por armazém</h2>
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

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Itens de maior valor</h2>
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
    <ModuleCard class="overflow-hidden" title="Itens de Inventário">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <div class="flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <TableCellsIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            Itens de Inventário
            <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
              ({{ inventory.total }} items)
            </span>
          </h2>
          <div class="flex items-center gap-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">
              Ordenar por:
              <BaseSelect v-model="filters.sort_by" @change="applyFilters" class="ml-2 inline-block w-auto min-w-32 text-sm">
                <option value="qty_available">Quantidade</option>
                <option value="total_value">Valor</option>
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

      <!-- INVENTORY TABLE -->
      <div v-else-if="inventory.data && inventory.data.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-900/80">
            <tr>
              <th :class="tableHeadClass">Item</th>
              <th :class="tableHeadClass">Categoria</th>
              <th :class="tableHeadClass">Armazém</th>
              <th :class="tableHeadClass">Quantidade</th>
              <th :class="tableHeadClass">Valor Unitário</th>
              <th :class="tableHeadClass">Valor Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr 
              v-for="item in inventory.data" 
              :key="item.id"
              class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                      <CubeIcon class="h-4 w-4 text-blue-900 dark:text-blue-300" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.item?.name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ item.item?.code }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ item.item?.category?.name || 'N/A' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                {{ item.warehouse?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-900">
                  {{ item.qty_available }}
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                AOA XX.XX
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-blue-900 dark:text-blue-300">
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
        <CurrencyDollarIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          Nenhum item de inventário encontrado
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Tente ajustar seus filtros ou critérios de pesquisa
        </p>
      </div>

      <!-- PAGINATION -->
      <div v-if="inventory.data && inventory.data.length > 0" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <div class="flex items-center justify-between">
          <div class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando {{ inventory.from }} a {{ inventory.to }} de {{ inventory.total }} itens
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="!inventory.prev_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                inventory.prev_page_url
                  ? 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                  : 'text-slate-400 cursor-not-allowed dark:text-slate-600'
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

    <!-- SUMMARY BY CATEGORY -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <ChartBarIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            Valor por Categoria
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-900/80">
              <tr>
                <th :class="tableHeadClass">Categoria</th>
                <th :class="tableHeadClass">Quantidade</th>
                <th :class="tableHeadClass">Itens Únicos</th>
                <th :class="tableHeadClass">Valor Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
              <tr v-for="category in summaryByCategory" :key="category.category_name" class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20">
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">
                  {{ category.category_name || 'Sem Categoria' }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ category.total_quantity }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ category.unique_items }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-blue-900 dark:text-blue-300">
                  {{ formatCurrency(category.total_value) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <BuildingStorefrontIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            Valor por Armazém
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-900/80">
              <tr>
                <th :class="tableHeadClass">Armazém</th>
                <th :class="tableHeadClass">Quantidade</th>
                <th :class="tableHeadClass">Itens Únicos</th>
                <th :class="tableHeadClass">Valor Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
              <tr v-for="warehouse in summaryByWarehouse" :key="warehouse.warehouse_name" class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20">
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">
                  {{ warehouse.warehouse_name }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ warehouse.total_quantity }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                  {{ warehouse.unique_items }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-blue-900 dark:text-blue-300">
                  {{ formatCurrency(warehouse.total_value) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TOP VALUABLE ITEMS -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
          <StarIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
          Top 10 Dos Mais Valiosos
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-900/80">
            <tr>
              <th :class="tableHeadClass">Item</th>
              <th :class="tableHeadClass">Categoria</th>
              <th :class="tableHeadClass">Quantidade</th>
              <th :class="tableHeadClass">Valor Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr v-for="item in topValuableItems" :key="item.item_id" class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                      <CubeIcon class="h-4 w-4 text-blue-900 dark:text-blue-300" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.item?.name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ item.item?.code }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ item.item?.category?.name || 'N/A' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                {{ item.total_quantity }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-blue-900 dark:text-blue-300">
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
import { ref, computed, onBeforeUnmount, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  plotOptions: {
    bar: {
      borderRadius: 6,
      horizontal: true,
    },
  },
  xaxis: {
    categories: props.charts?.category_value_breakdown?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: {
      formatter: (value) => formatCurrency(value),
      style: { colors: chartTextColor.value },
    },
  },
  yaxis: {
    labels: {
      maxWidth: 220,
      style: { colors: chartTextColor.value },
    },
  },
  tooltip: {
    theme: chartTooltipTheme.value,
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
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.warehouse_value_breakdown?.labels || [],
  colors: ['#1d4ed8', '#0f766e', '#7c3aed', '#ea580c', '#be123c', '#0891b2'],
  legend: {
    position: 'bottom',
    labels: {
      colors: chartTextColor.value,
    },
  },
  dataLabels: {
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  tooltip: {
    theme: chartTooltipTheme.value,
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
      columnWidth: '48%',
    },
  },
  xaxis: {
    categories: props.charts?.top_item_value?.labels || [],
    labels: {
      rotate: -25,
      trim: true,
      style: { colors: chartTextColor.value },
    },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: {
    labels: {
      formatter: (value) => formatCurrency(value),
      style: { colors: chartTextColor.value },
    },
  },
  tooltip: {
    theme: chartTooltipTheme.value,
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
    'AVAILABLE': 'bg-green-100 text-green-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    'IN_USE': 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200',
    'MAINTENANCE': 'bg-yellow-100 text-yellow-800 dark:bg-amber-500/10 dark:text-amber-200',
    'CALIBRATION_DUE': 'bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-200',
    'EXPIRED': 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200'
  }
  return statusMap[status] || 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
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
