<template>
  <div class="space-y-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <FireIcon class="h-7 w-7 text-blue-900" />
            Relatório de Consumo de Reagentes
          </h1>
          <p class="mt-2 text-gray-600">
            Monitore o uso e padrões de consumo de reagentes
            <span v-if="filterPeriod" class="font-semibold text-blue-900">
              {{ filterPeriod }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_uses || 0 }} usos
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

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <BeakerIcon class="h-4 w-4" />
            Reagente
          </label>
          <select
            v-model="filters.item_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Reagentes</option>
            <option v-for="item in items" :key="item.id" :value="item.id">
              {{ item.name }} ({{ item.code }})
            </option>
          </select>
        </div>

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

        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <UserIcon class="h-4 w-4" />
            Usuário
          </label>
          <select
            v-model="filters.user_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Usuários</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>
      </div>

      <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Pesquisar
        </label>
        <input
          type="text"
          v-model="filters.search"
          placeholder="Search by reagent name, used by, or remarks..."
          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          @keyup.enter="applyFilters"
        />
      </div>

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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total de Consumo</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.total_consumption || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
            <FireIcon class="h-6 w-6 text-red-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Média Diária</p>
            <p class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.avg_daily_consumption || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <ChartBarIcon class="h-6 w-6 text-yellow-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Reagente Mais Consumido</p>
            <p v-if="stats.most_consumed_item" class="mt-2 text-lg font-bold text-blue-900 truncate">
              {{ stats.most_consumed_item.reagent_name }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-gray-500">
              {{ stats.most_consumed_item?.total_consumption || 0 }} unidades
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <TrophyIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Usuário Mais Activo</p>
            <p v-if="stats.most_active_user" class="mt-2 text-lg font-bold text-green-600 truncate">
              {{ stats.most_active_user.used_by }}
            </p>
            <p v-else class="mt-2 text-lg font-bold text-gray-400">Sem dados</p>
            <p class="text-sm text-gray-500">
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
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Reagentes mais consumidos</h2>
            <p class="mt-1 text-sm text-slate-500">Itens com maior volume absoluto de consumo no período filtrado.</p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Reagentes monitorizados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ itemConsumptionTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="itemConsumptionChartOptions" :series="itemConsumptionChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Consumo por utilizador</h2>
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

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Ritmo diário de consumo</h2>
              <p class="mt-1 text-sm text-slate-500">Volume consumido por dia para leitura de picos e estabilidade.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="line" height="250" :options="dailyConsumptionChartOptions" :series="dailyConsumptionChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <TableCellsIcon class="h-5 w-5 text-blue-900" />
            Detalhes de Consumo
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ consumptions.total || 0 }} registros)
            </span>
          </h2>
          <div class="flex items-center gap-4">
            <div class="text-sm text-gray-600">
              Ordenar por:
              <select v-model="filters.sort_by" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                <option value="date">Data</option>
                <option value="quantity_used">Quantidade</option>
              </select>
              <select v-model="filters.sort_direction" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                <option value="desc">Desc</option>
                <option value="asc">Asc</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
        <p class="mt-4 text-sm text-gray-500">Carregando...</p>
      </div>

      <div v-else-if="consumptions.data && consumptions.data.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reagente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Armazém</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade Usada</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usado Por</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comentário</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="consumption in consumptions.data"
              :key="consumption.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(consumption.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <BeakerIcon class="h-4 w-4 text-blue-900" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ consumption.reagent_name }}</div>
                    <div class="text-xs text-gray-500">{{ consumption.item?.code || 'N/A' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ consumption.item?.category?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ consumption.warehouse?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 font-semibold text-red-600">
                  {{ consumption.quantity_used }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ consumption.used_by }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                <div class="truncate">{{ consumption.remarks || '-' }}</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum registro de consumo encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Tente ajustar seus filtros ou critérios de pesquisa
        </p>
      </div>

      <div v-if="consumptions.data && consumptions.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Mostrando {{ consumptions.from }} a {{ consumptions.to }} de {{ consumptions.total }} registros
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="!consumptions.prev_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                consumptions.prev_page_url ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-400 cursor-not-allowed'
              ]"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="!consumptions.next_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                consumptions.next_page_url ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-400 cursor-not-allowed'
              ]"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="summaryByItem.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <ChartBarIcon class="h-5 w-5 text-blue-900" />
          Consumo por Reagente
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reagente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total de Consumo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contagem de Uso</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Média por Uso</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in summaryByItem" :key="item.reagent_id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.reagent_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">{{ item.total_consumption }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.usage_count }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.avg_per_use }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="summaryByUser.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <UsersIcon class="h-5 w-5 text-blue-900" />
          Consumo por Usuário
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuário</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total de Consumo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contagem de Uso</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in summaryByUser" :key="user.used_by" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.used_by }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">{{ user.total_consumption }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.usage_count }}</td>
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
  },
  colors: ['#b91c1c'],
  dataLabels: { enabled: false },
  plotOptions: {
    bar: {
      borderRadius: 6,
      horizontal: true,
    },
  },
  xaxis: {
    categories: props.charts?.item_consumption?.labels || [],
  },
  legend: { show: false },
}))

const userConsumptionChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: props.charts?.user_consumption?.labels || [],
  colors: ['#15803d', '#1d4ed8', '#7c3aed', '#ea580c', '#be123c', '#0891b2'],
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

const dailyConsumptionChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#1e3a8a'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  xaxis: {
    categories: props.charts?.daily_consumption?.labels || [],
    labels: {
      rotate: -20,
      trim: true,
    },
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
  if (props.filters) {
    Object.keys(props.filters).forEach(key => {
      if (filters.hasOwnProperty(key)) {
        filters[key] = props.filters[key]
      }
    })
  }
})
</script>
