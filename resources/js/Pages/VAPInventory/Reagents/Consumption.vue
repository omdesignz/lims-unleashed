<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Reagent usage"
      title="Relatório de Consumo de Reagentes"
      :description="filterPeriod ? `Monitore o uso e padrões de consumo de reagentes · ${filterPeriod}` : 'Monitore o uso e padrões de consumo de reagentes.'"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-900 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-400/20">
            {{ stats.total_uses || 0 }} usos
          </span>
          <button
            @click="exportReport"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowDownTrayIcon class="h-5 w-5" />
            Exportar
          </button>
          <a
            :href="route('vap-inventory.reagents.consumption.create')"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          >
            <PlusIcon class="h-5 w-5" />
            Registrar Consumo
          </a>
        </div>
      </template>
    </ModuleHero>

    <!-- FILTERS SECTION -->
    <ModuleCard title="Filtros de consumo" description="Refine por período, reagente, armazém, utilizador e texto livre.">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Date Range -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <CalendarIcon class="h-4 w-4" />
            Intervalo de Datas
          </label>
          <div class="flex gap-2">
            <BaseInput v-model="filters.date_from" type="date" />
            <BaseInput v-model="filters.date_to" type="date" />
          </div>
        </div>

        <!-- Reagent Filter -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <BeakerIcon class="h-4 w-4" />
            Reagente
          </label>
          <BaseSelect v-model="filters.item_id">
            <option value="">Todos os Reagentes</option>
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
          <BaseSelect v-model="filters.warehouse_id">
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- User Filter -->
        <div class="space-y-2">
          <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
            <UserIcon class="h-4 w-4" />
            Usuário
          </label>
          <BaseSelect v-model="filters.user_id">
            <option value="">Todos os Usuários</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </BaseSelect>
        </div>
      </div>

      <!-- Search -->
      <div class="mt-4">
        <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
          Pesquisar
        </label>
        <BaseInput v-model="filters.search" placeholder="Pesquisar por reagente, utilizador ou observações..." @keyup.enter="applyFilters" />
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
        <button
          @click="resetFilters"
          class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          Redefinir
        </button>
        <button
          @click="applyFilters"
          class="rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
        >
          Aplicar Filtros
        </button>
      </div>
    </ModuleCard>

    <!-- SUMMARY STATS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total de Consumo</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.total_consumption || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
            <FireIcon class="h-6 w-6 text-red-600" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Média Diária de Consumo</p>
            <p class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.avg_daily_consumption || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <ChartBarIcon class="h-6 w-6 text-yellow-600" />
          </div>
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Reagente Mais Consumido</p>
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
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Usuário Mais Activo</p>
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
    <ModuleCard class="overflow-hidden" title="Detalhes de Consumo">
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
    </ModuleCard>

    <!-- SUMMARY BY ITEM -->
    <ModuleCard v-if="summaryByItem.length > 0" class="overflow-hidden" title="Consumo por Reagente">
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
    </ModuleCard>

    <!-- SUMMARY BY USER -->
    <ModuleCard v-if="summaryByUser.length > 0" class="overflow-hidden" title="Consumo por Usuário">
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
    </ModuleCard>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
