<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClockIcon class="h-7 w-7 text-red-900" />
            Relatório de Validade de Reagentes
          </h1>
          <p class="mt-2 text-gray-600">
            Monitore a data de validade de reagentes e gerencie a rotação de estoque
            <span class="font-semibold text-red-900">
              {{ stats.expired }} itens vencidos
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="exportReport"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar Relatório
          </button>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            
            Voltar ao Inventário
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- STATUS FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <CheckCircleIcon class="h-4 w-4 inline mr-1" />
            Estado
          </label>
          <select
            v-model="filters.status"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Estados</option>
            <option value="expired">Vencido</option>
            <option value="expiring_soon">Vencendo em Breve (≤ 60 dias)</option>
            <option value="good">Bom (> 60 dias)</option>
          </select>
        </div>

        <!-- CATEGORY FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <select
            v-model="filters.category_id"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- WAREHOUSE FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <BuildingLibraryIcon class="h-4 w-4 inline mr-1" />
            Armazém
          </label>
          <select
            v-model="filters.warehouse_id"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </select>
        </div>

        <!-- SORT BY -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
            Ordenar Por
          </label>
          <select
            v-model="filters.sort_by"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="expiry_date">Data de Validade</option>
            <option value="days_to_expiry">Dias para Vencimento</option>
            <option value="name">Nome do Reagente</option>
            <option value="current_stock">Estoque Actual</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencido</p>
            <p class="text-2xl font-bold mt-1">{{ stats.expired }}</p>
          </div>
          <XCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Past expiry date</p>
      </div>

      <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencendo em Breve</p>
            <p class="text-2xl font-bold mt-1">{{ stats.expiring_soon }}</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Dentro de 60 dias</p>
      </div>

      <div class="bg-gradient-to-r from-yellow-900 to-yellow-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Bom</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_reagents - (stats.expired + stats.expiring_soon) }}</p>
          </div>
          <CheckCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Mais de 60 dias</p>
      </div>

      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Total de Reagentes</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_reagents }}</p>
          </div>
          <BeakerIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Com datas de validade</p>
      </div>
    </div>

    <!-- REAGENTS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">
            Detalhes de Validade de Reagentes
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ reagents.data.length }} itens)
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <button
              @click="markAllExpired"
              class="inline-flex items-center gap-2 rounded-lg bg-red-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-800"
            >
              <ExclamationTriangleIcon class="h-4 w-4" />
              Marcar Todos como Descartados
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Detalhes do Reagente
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Informações de Validade
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Informações de Estoque
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Acções
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="reagent in reagents.data"
              :key="reagent.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div :class="[
                      'h-10 w-10 rounded-lg flex items-center justify-center',
                      getExpiryColor(reagent).bg
                    ]">
                      <BeakerIcon :class="['h-6 w-6', getExpiryColor(reagent).text]" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ reagent.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ reagent.internal_code || 'Sem Código Interno' }}
                    </div>
                    <div class="text-xs text-gray-400">
                      {{ reagent.category?.name || 'Sem Categoria' }}
                      <span v-if="reagent.supplier" class="ml-2">
                        • {{ reagent.supplier.name }}
                      </span>
                    </div>
                    <div v-if="reagent.lot" class="text-xs text-gray-400">
                      Lote: {{ reagent.lot }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Data de Validade:</span>
                    <span class="font-semibold" :class="getExpiryDateColor(reagent)">
                      {{ formatDate(reagent.reagent_expiry_date) }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Dias para Vencimento:</span>
                    <span class="font-semibold" :class="getDaysColor(reagent.days_to_expiry)">
                      {{ reagent.days_to_expiry }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Data de Abertura:</span>
                    <span class="font-semibold text-gray-900">
                      {{ formatDate(reagent.reagent_open_date) || 'Not opened' }}
                    </span>
                  </div>
                  <div v-if="reagent.reagent_open_date" class="mt-2">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        :class="['h-full rounded-full', getShelfLifeBarColor(reagent)]"
                        :style="{ width: getShelfLifePercentage(reagent) + '%' }"
                      ></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                      <span>Opened</span>
                      <span>{{ getShelfLifePercentage(reagent).toFixed(0) }}% usado</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Total de Estoque:</span>
                    <span class="font-semibold text-blue-900">{{ reagent.total_stock || 0 }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Armazéns:</span>
                    <span class="font-semibold text-gray-900">{{ reagent.warehouse_count || 1 }}</span>
                  </div>
                  <div v-if="reagent.refrigerated" class="mt-2">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                      <SunIcon class="mr-1 h-3 w-3" />
                      Requer Refrigeração
                    </span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClasses(reagent)">
                  {{ getStatusText(reagent) }}
                </span>
                <div v-if="reagent.is_expired" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                    <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                    Requer Descarte
                  </span>
                </div>
                <div v-if="reagent.days_to_expiry <= 30 && reagent.days_to_expiry > 0" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800">
                    <ClockIcon class="mr-1 h-3 w-3" />
                    Prioridade de Uso
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('vap-inventory.items.show', reagent.id)"
                    class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    
                    Visualizar
                  </Link>
                  <Link
                    :href="route('vap-inventory.items.edit', reagent.id)"
                    class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                  >
                    <PencilSquareIcon class="h-4 w-4 mr-1" />
                    Modificar
                  </Link>
                  <button
                    v-if="reagent.is_expired"
                    @click="markDisposed(reagent)"
                    class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-900 hover:bg-red-100"
                  >
                    <TrashIcon class="h-4 w-4 mr-1" />
                    Descartar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="reagents.data.length === 0" class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum reagente encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Nenhum reagente com datas de validade encontrado correspondendo aos seus filtros
        </p>
        <Link
          :href="route('vap-inventory.items.index')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          
          Voltar ao Inventário
        </Link>
      </div>

      <!-- PAGINATION -->
      <div v-if="reagents.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <Pagination :links="reagents.links" />
      </div>
    </div>

    <!-- UPCOMING EXPIRIES -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- EXPIRY TIMELINE -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Linha do Tempo de Validade (Próximos 90 Dias)
        </h3>
        <div class="space-y-4">
          <div
            v-for="month in expiryTimeline"
            :key="month.month"
            class="border-l-4 border-blue-900 pl-4 py-2"
          >
            <div class="flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900">{{ month.month }}</div>
                <div class="text-sm text-gray-500">{{ month.count }} itens vencendo em breve</div>
              </div>
              <div class="text-sm font-semibold" :class="month.color">
                {{ month.percentage }}%
              </div>
            </div>
            <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full"
                :class="month.barColor"
                :style="{ width: month.percentage + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Acções Rápidas
        </h3>
        <div class="space-y-3">
          <button
            @click="generateDisposalReport"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-red-100 p-2">
                <DocumentTextIcon class="h-5 w-5 text-red-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Relatório de Descarte</div>
                <div class="text-sm text-gray-500">Gerar documentação de descarte</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </button>

          <button
            @click="sendExpiryAlerts"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-orange-100 p-2">
                <BellAlertIcon class="h-5 w-5 text-orange-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Enviar Alertas</div>
                <div class="text-sm text-gray-500">Notificar o time sobre reagentes vencendo em breve</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </button>

          <Link
            :href="route('vap-inventory.orders.create')"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-green-100 p-2">
                <ShoppingCartIcon class="h-5 w-5 text-green-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Reabastecer Vencimento</div>
                <div class="text-sm text-gray-500">Criar pedidos de compra para substituições</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ClockIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  TagIcon,
  BuildingLibraryIcon,
  ArrowsUpDownIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  BeakerIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
  SunIcon,
  DocumentTextIcon,
  BellAlertIcon,
  ShoppingCartIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  reagents: Object,
  filters: Object,
  categories: Array,
  warehouses: Array,
  stats: Object,
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getExpiryColor = (reagent) => {
  if (reagent.is_expired) {
    return { bg: 'bg-red-100', text: 'text-red-900' }
  } else if (reagent.days_to_expiry <= 30) {
    return { bg: 'bg-orange-100', text: 'text-orange-900' }
  } else if (reagent.days_to_expiry <= 60) {
    return { bg: 'bg-yellow-100', text: 'text-yellow-900' }
  } else {
    return { bg: 'bg-green-100', text: 'text-green-900' }
  }
}

const getExpiryDateColor = (reagent) => {
  if (reagent.is_expired) return 'text-red-900'
  if (reagent.days_to_expiry <= 30) return 'text-orange-900'
  if (reagent.days_to_expiry <= 60) return 'text-yellow-900'
  return 'text-green-900'
}

const getDaysColor = (days) => {
  if (days <= 0) return 'text-red-900'
  if (days <= 30) return 'text-orange-900'
  if (days <= 60) return 'text-yellow-900'
  return 'text-green-900'
}

const getShelfLifeBarColor = (reagent) => {
  if (reagent.is_expired) return 'bg-red-900'
  const percentage = getShelfLifePercentage(reagent)
  if (percentage > 80) return 'bg-orange-900'
  if (percentage > 50) return 'bg-yellow-900'
  return 'bg-green-900'
}

const getShelfLifePercentage = (reagent) => {
  if (!reagent.reagent_open_date || !reagent.reagent_expiry_date) return 0
  
  const openDate = new Date(reagent.reagent_open_date)
  const expiryDate = new Date(reagent.reagent_expiry_date)
  const today = new Date()
  
  const totalShelfLife = expiryDate - openDate
  const usedShelfLife = today - openDate
  
  if (totalShelfLife <= 0) return 100
  return Math.min(100, (usedShelfLife / totalShelfLife) * 100)
}

const getStatusClasses = (reagent) => {
  if (reagent.is_expired) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (reagent.days_to_expiry <= 30) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (reagent.days_to_expiry <= 60) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
}

const getStatusText = (reagent) => {
  if (reagent.is_expired) return 'Vencido'
  if (reagent.days_to_expiry <= 30) return 'Vencendo em Breve'
  if (reagent.days_to_expiry <= 60) return 'Vencendo em Curto Prazo'
  return 'Bom'
}

const expiryTimeline = computed(() => {
  const months = []
  const today = new Date()
  
  for (let i = 0; i < 3; i++) {
    const monthDate = new Date(today.getFullYear(), today.getMonth() + i, 1)
    const monthName = monthDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    
    // Count reagents expiring in this month
    const count = props.reagents.data.filter(reagent => {
      if (!reagent.reagent_expiry_date) return false
      const expiryDate = new Date(reagent.reagent_expiry_date)
      return expiryDate.getMonth() === monthDate.getMonth() && 
             expiryDate.getFullYear() === monthDate.getFullYear()
    }).length
    
    const percentage = Math.min(100, (count / props.reagents.data.length) * 100)
    
    let color = 'text-green-900'
    let barColor = 'bg-green-900'
    if (i === 0) {
      color = 'text-orange-900'
      barColor = 'bg-orange-900'
    } else if (i === 1) {
      color = 'text-yellow-900'
      barColor = 'bg-yellow-900'
    }
    
    months.push({
      month: monthName,
      count,
      percentage: percentage.toFixed(1),
      color,
      barColor
    })
  }
  
  return months
})

const exportReport = () => {
  const params = new URLSearchParams(props.filters)
  window.open(route('vap-inventory.reports.export', {
    report_type: 'expiry',
    format: 'pdf',
    filters: JSON.stringify(props.filters)
  }), '_blank')
}

const markAllExpired = async () => {
  if (confirm('Tem a certeza que deseja marcar todos os reagentes vencidos como descartados? Esta ação não pode ser desfeita.')) {
    try {
      await router.post(route('vap-inventory.reagents.mark-disposed'), {
        reagent_ids: props.reagents.data
          .filter(r => r.is_expired)
          .map(r => r.id)
      })
      router.reload()
    } catch (error) {
      console.error('Error marking reagents as disposed:', error)
    }
  }
}

const markDisposed = async (reagent) => {
  if (confirm(`Deseja marcar ${reagent.name} como descartado? Isso removerá ele do inventário.`)) {
    try {
      await router.post(route('vap-inventory.reagents.dispose', reagent.id))
      router.reload()
    } catch (error) {
      console.error('Error marking reagent as disposed:', error)
    }
  }
}

const generateDisposalReport = () => {
  window.open(route('vap-inventory.reports.export', {
    report_type: 'expiry',
    format: 'pdf',
    filters: JSON.stringify({ ...props.filters, status: 'expired' })
  }), '_blank')
}

const sendExpiryAlerts = async () => {
  try {
    await router.post(route('vap-inventory.reagents.send-alerts'), {
      days_threshold: 30
    })
    alert('Alertas de validade enviados com sucesso!')
  } catch (error) {
    console.error('Error sending alerts:', error)
  }
}

// Watch filters
watch(
  () => props.filters,
  debounce((value) => {
    router.get(route('vap-inventory.reagents.expiry'), value, {
      preserveState: true,
      replace: true,
    })
  }, 300),
  { deep: true }
)
</script>