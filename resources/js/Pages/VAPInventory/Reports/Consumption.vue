<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Reagent consumption"
      title="Relatório de Consumo de Reagentes"
      :description="filterPeriod ? `Monitore uso, padrões e desvios de consumo · ${filterPeriod}` : 'Monitore uso, padrões e desvios de consumo de reagentes por item, armazém e utilizador.'"
    >
      <template #actions>
        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-sm font-semibold text-blue-900 ring-1 ring-blue-700/10 dark:bg-white/10 dark:text-blue-100 dark:ring-white/10">
          {{ stats.total_uses || 0 }} usos
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

    <ModuleCard title="Filtros de consumo" description="Filtre por período, reagente, armazém, utilizador e texto livre.">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <BeakerIcon class="h-4 w-4" />
            Reagente
          </label>
          <BaseSelect
            v-model="filters.item_id"
          >
            <option value="">Todos os Reagentes</option>
            <option v-for="item in items" :key="item.id" :value="item.id">
              {{ item.name }} ({{ item.code }})
            </option>
          </BaseSelect>
        </div>

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

        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <UserIcon class="h-4 w-4" />
            Usuário
          </label>
          <BaseSelect
            v-model="filters.user_id"
          >
            <option value="">Todos os Usuários</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </BaseSelect>
        </div>
      </div>

      <div class="mt-4">
        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
          Pesquisar
        </label>
        <BaseInput
          type="text"
          v-model="filters.search"
          placeholder="Pesquisar por reagente, utilizador ou observações..."
          @keyup.enter="applyFilters"
        />
      </div>

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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total de Consumo</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.total_consumption || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
            <FireIcon class="h-6 w-6 text-red-600" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Média Diária</p>
            <p class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.avg_daily_consumption || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <ChartBarIcon class="h-6 w-6 text-yellow-600" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Reagente Mais Consumido</p>
            <p v-if="stats.most_consumed_item" class="mt-2 text-lg font-bold text-blue-900 truncate">
              {{ stats.most_consumed_item.reagent_name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              {{ stats.most_consumed_item?.total_consumption || 0 }} unidades
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <TrophyIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Usuário Mais Activo</p>
            <p v-if="stats.most_active_user" class="mt-2 text-lg font-bold text-green-600 truncate">
              {{ stats.most_active_user.used_by }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              {{ stats.most_active_user?.total_consumption || 0 }} unidades
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
            <UsersIcon class="h-6 w-6 text-green-600" />
          </div>
        </div>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Reagentes mais consumidos</h2>
            <p class="mt-1 text-sm text-slate-500">Itens com maior volume absoluto de consumo no período filtrado.</p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-white/5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Reagentes monitorizados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ itemConsumptionTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="itemConsumptionChartOptions" :series="itemConsumptionChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Consumo por utilizador</h2>
              <p class="mt-1 text-sm text-slate-500">Quem concentra maior volume de utilização dos reagentes.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-800">
              {{ userConsumptionTotal }} utilizadores
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="userConsumptionChartOptions" :series="userConsumptionChartSeries" />
          </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ritmo diário de consumo</h2>
              <p class="mt-1 text-sm text-slate-500">Volume consumido por dia para leitura de picos e estabilidade.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="line" height="250" :options="dailyConsumptionChartOptions" :series="dailyConsumptionChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <ModuleCard class="overflow-hidden" title="Detalhes de Consumo">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <div class="flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <TableCellsIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            Detalhes de Consumo
            <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
              ({{ consumptions.total || 0 }} registros)
            </span>
          </h2>
          <div class="flex items-center gap-4">
            <div class="text-sm text-slate-600 dark:text-slate-300">
              Ordenar por:
              <BaseSelect v-model="filters.sort_by" @change="applyFilters" class="ml-2 inline-block w-auto min-w-32 text-sm">
                <option value="date">Data</option>
                <option value="quantity_used">Quantidade</option>
              </BaseSelect>
              <BaseSelect v-model="filters.sort_direction" @change="applyFilters" class="ml-2 inline-block w-auto min-w-24 text-sm">
                <option value="desc">Desc</option>
                <option value="asc">Asc</option>
              </BaseSelect>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando...</p>
      </div>

      <div v-else-if="consumptions.data && consumptions.data.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-900/80">
            <tr>
              <th :class="tableHeadClass">Data</th>
              <th :class="tableHeadClass">Reagente</th>
              <th :class="tableHeadClass">Categoria</th>
              <th :class="tableHeadClass">Armazém</th>
              <th :class="tableHeadClass">Quantidade Usada</th>
              <th :class="tableHeadClass">Usado Por</th>
              <th :class="tableHeadClass">Comentário</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr
              v-for="consumption in consumptions.data"
              :key="consumption.id"
              class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20"
            >
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                {{ formatDate(consumption.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                      <BeakerIcon class="h-4 w-4 text-blue-900 dark:text-blue-300" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ consumption.reagent_name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ consumption.item?.code || 'N/A' }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ consumption.item?.category?.name || 'N/A' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                {{ consumption.warehouse?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 font-semibold text-red-600">
                  {{ consumption.quantity_used }}
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">
                {{ consumption.used_by }}
              </td>
              <td class="max-w-xs px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                <div class="truncate">{{ consumption.remarks || '-' }}</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          Nenhum registro de consumo encontrado
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Tente ajustar seus filtros ou critérios de pesquisa
        </p>
      </div>

      <div v-if="consumptions.data && consumptions.data.length > 0" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <div class="flex items-center justify-between">
          <div class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando {{ consumptions.from }} a {{ consumptions.to }} de {{ consumptions.total }} registros
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="!consumptions.prev_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                consumptions.prev_page_url ? 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' : 'text-slate-400 cursor-not-allowed dark:text-slate-600'
              ]"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="!consumptions.next_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                consumptions.next_page_url ? 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' : 'text-slate-400 cursor-not-allowed dark:text-slate-600'
              ]"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>
    </ModuleCard>

    <div v-if="summaryByItem.length > 0" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
          <ChartBarIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
          Consumo por Reagente
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-900/80">
            <tr>
              <th :class="tableHeadClass">Reagente</th>
              <th :class="tableHeadClass">Total de Consumo</th>
              <th :class="tableHeadClass">Contagem de Uso</th>
              <th :class="tableHeadClass">Média por Uso</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr v-for="item in summaryByItem" :key="item.reagent_id" class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">{{ item.reagent_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">{{ item.total_consumption }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">{{ item.usage_count }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ item.avg_per_use }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="summaryByUser.length > 0" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
          <UsersIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
          Consumo por Usuário
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-900/80">
            <tr>
              <th :class="tableHeadClass">Usuário</th>
              <th :class="tableHeadClass">Total de Consumo</th>
              <th :class="tableHeadClass">Contagem de Uso</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr v-for="user in summaryByUser" :key="user.used_by" class="hover:bg-blue-50/60 dark:hover:bg-blue-950/20">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">{{ user.used_by }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">{{ user.total_consumption }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100">{{ user.usage_count }}</td>
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
  FireIcon,
  ArrowDownTrayIcon,
  CalendarIcon,
  BeakerIcon,
  BuildingStorefrontIcon,
  UserIcon,
  TableCellsIcon,
  TrophyIcon,
  UsersIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  consumptions: {
    type: Object,
    default: () => ({ data: [], links: {} })
  },
  summaryByItem: {
    type: Array,
    default: () => []
  },
  summaryByDate: {
    type: Array,
    default: () => []
  },
  summaryByUser: {
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
  users: {
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
  user_id: '',
  search: '',
  sort_by: 'date',
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

const itemConsumptionChartSeries = computed(() => props.charts?.item_consumption?.series || [])
const itemConsumptionTotal = computed(() => props.charts?.item_consumption?.labels?.length || 0)

const userConsumptionChartSeries = computed(() => props.charts?.user_consumption?.series || [])
const userConsumptionTotal = computed(() => props.charts?.user_consumption?.labels?.length || 0)

const dailyConsumptionChartSeries = computed(() => props.charts?.daily_consumption?.series || [])

const itemConsumptionChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#b91c1c'],
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
    categories: props.charts?.item_consumption?.labels || [],
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

const userConsumptionChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.user_consumption?.labels || [],
  colors: ['#15803d', '#1d4ed8', '#7c3aed', '#ea580c', '#be123c', '#0891b2'],
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

const dailyConsumptionChartOptions = computed(() => ({
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
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  xaxis: {
    categories: props.charts?.daily_consumption?.labels || [],
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
  legend: { show: false },
}))

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function applyFilters() {
  filters.get(route('vap-inventory.reports.consumption'), {
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
    report_type: 'consumption',
    format: 'pdf',
    filters: filters.data()
  }

  router.post(route('vap-inventory.reports.export'), exportFilters)
}

function previousPage() {
  if (props.consumptions.prev_page_url) {
    router.visit(props.consumptions.prev_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false
    })
  }
}

function nextPage() {
  if (props.consumptions.next_page_url) {
    router.visit(props.consumptions.next_page_url, {
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
