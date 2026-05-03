<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
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
          <a
            :href="route('vap-inventory.reagents.consumption.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PlusIcon class="h-5 w-5" />
            Registrar Consumo
          </a>
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

        <!-- Reagent Filter -->
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

        <!-- User Filter -->
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

      <!-- Search -->
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
            <p class="text-sm font-medium text-gray-600">Média Diária de Consumo</p>
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

    <!-- CONSUMPTION TABLE -->
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

      <!-- LOADING STATE -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
        <p class="mt-4 text-sm text-gray-500">Carregando...</p>
      </div>

      <!-- CONSUMPTIONS TABLE -->
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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acções</th>
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
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <UserIcon class="h-4 w-4 text-gray-600" />
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ consumption.used_by }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                <div class="truncate">{{ consumption.remarks || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <a 
                  :href="route('vap-inventory.reagents.consumption.show', consumption.id)"
                  class="text-blue-900 hover:text-blue-700"
                >
                  Visualizar
                </a>
                <button 
                  @click="deleteConsumption(consumption)"
                  class="ml-4 text-red-600 hover:text-red-800"
                >
                  Excluir
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-else class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum registro de consumo encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Tente ajustar seus filtros ou critérios de pesquisa
        </p>
      </div>

      <!-- PAGINATION -->
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
                consumptions.prev_page_url 
                  ? 'text-gray-700 hover:bg-gray-100' 
                  : 'text-gray-400 cursor-not-allowed'
              ]"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="!consumptions.next_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                consumptions.next_page_url 
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

    <!-- SUMMARY BY ITEM -->
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
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ item.reagent_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                {{ item.total_consumption }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ item.usage_count }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ item.avg_per_use }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SUMMARY BY USER -->
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
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ user.used_by }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                {{ user.total_consumption }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ user.usage_count }}
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
  FireIcon,
  ArrowDownTrayIcon,
  CalendarIcon,
  BeakerIcon,
  BuildingStorefrontIcon,
  UserIcon,
  TableCellsIcon,
  TrophyIcon,
  UsersIcon,
  ChartBarIcon,
  PlusIcon
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

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function applyFilters() {
  filters.get(route('vap-inventory.reagents.consumption.index'), {
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

function deleteConsumption(consumption) {
  if (confirm('Are you sure you want to delete this consumption record? This will restore the stock.')) {
    router.delete(route('vap-inventory.reagents.consumption.destroy', consumption.id), {
      preserveScroll: true,
      preserveState: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false
    })
  }
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