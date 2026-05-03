<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ShoppingCartIcon class="h-7 w-7 text-blue-900" />
            Pedidos de Inventário / Compra
          </h1>
          <p class="mt-2 text-gray-600">
            Gerenciar pedidos de compra para itens de inventário
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_orders || 0 }} pedidos
          </span>
          <a
            :href="route('vap-inventory.orders.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PlusIcon class="h-5 w-5" />
            Novo Pedido
          </a>
        </div>
      </div>
    </div>

    <!-- FILTERS SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Status Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <CheckCircleIcon class="h-4 w-4" />
            Estado
          </label>
          <select
            v-model="filters.status"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Estados</option>
            <option value="pending">Pendentes</option>
            <option value="approved">Aprovados</option>
            <option value="ordered">Ordenados</option>
            <option value="partially_received">Recebidos Parcialmente</option>
            <option value="received">Recebidos</option>
            <option value="cancelled">Cancelados</option>
          </select>
        </div>

        <!-- Supplier Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <TruckIcon class="h-4 w-4" />
            Fornecedor
          </label>
          <select
            v-model="filters.supplier_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Fornecedores</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
              {{ supplier.name }}
            </option>
          </select>
        </div>

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

        <!-- Search -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Pesquisar
          </label>
          <input
            type="text"
            v-model="filters.search"
            placeholder="Pesquisar por número de pedido, referência ou item..."
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
            @keyup.enter="applyFilters"
          />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
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

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Pedidos Pendentes</p>
            <p class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.pending_orders || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <ClockIcon class="h-6 w-6 text-yellow-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Pedidos Hoje</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ stats.orders_today || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
            <CalendarDaysIcon class="h-6 w-6 text-blue-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Valor Total</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ formatCurrency(stats.total_value || 0) }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
            <CurrencyDollarIcon class="h-6 w-6 text-green-600" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Itens Abertos</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.open_items || 0 }}</p>
          </div>
          <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
            <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- ORDERS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ListBulletIcon class="h-5 w-5 text-blue-900" />
            Lista de Pedidos
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ orders.total || 0 }} pedidos)
            </span>
          </h2>
          <div class="flex flex-col gap-2 text-sm text-gray-600 sm:flex-row sm:items-center sm:gap-4">
            <div class="text-sm text-gray-600">
              Ordenar por:
              <select v-model="filters.sort_by" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                <option value="created_at">Data</option>
                <option value="date">Data de Pedido</option>
                <option value="reference">Referência</option>
              </select>
              <select v-model="filters.sort_direction" @change="applyFilters" class="ml-2 rounded border-gray-300 text-sm">
                <option value="desc">Mais recentes</option>
                <option value="asc">Mais antigos</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- LOADING STATE -->
      <div v-if="loading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
        <p class="mt-4 text-sm text-gray-500">A carregar pedidos...</p>
      </div>

      <!-- ORDERS TABLE -->
      <div v-else-if="orders.data && orders.data.length > 0" class="space-y-4">
        <div class="grid gap-4 px-4 pt-4 md:hidden">
          <article v-for="order in orders.data" :key="`mobile-${order.id}`" class="rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ order.seq || 'ORD-' + order.id }}</p>
                <p class="text-xs text-gray-500">{{ order.reference || 'Sem referência' }}</p>
              </div>
              <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getStatusClass(order.status)]">
                {{ formatStatus(order.status) }}
              </span>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-2 text-sm">
              <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-500">Fornecedor</dt>
                <dd class="mt-1 text-gray-900">{{ order.supplier?.name || 'Sem fornecedor associado' }}</dd>
                <div v-if="order.supplier?.latest_assessment" class="mt-2 flex flex-wrap gap-2">
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold', supplierStatusClass(order.supplier.latest_assessment.status)]">
                    {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                  </span>
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold', supplierRiskClass(order.supplier.latest_assessment.risk_level)]">
                    {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                  </span>
                </div>
                <div v-if="order.reception_non_conformity_summary?.open_count" class="mt-2">
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold', receptionSeverityClass(order.reception_non_conformity_summary.latest_severity)]">
                    {{ order.reception_non_conformity_summary.open_count }} NC(s) de recepção aberta(s)
                  </span>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div class="rounded-lg bg-gray-50 px-3 py-2">
                  <dt class="text-xs uppercase tracking-wide text-gray-500">Data</dt>
                  <dd class="mt-1 text-gray-900">{{ formatDate(order.date) }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2">
                  <dt class="text-xs uppercase tracking-wide text-gray-500">Previsão</dt>
                  <dd class="mt-1 text-gray-900">{{ order.earliest_expected_date ? formatDate(order.earliest_expected_date) : 'Sem data prevista' }}</dd>
                </div>
              </div>
              <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-500">Resumo</dt>
                <dd class="mt-1 text-gray-900">{{ order.items_count || 0 }} itens · {{ order.total_quantity || 0 }} unidades</dd>
              </div>
            </dl>
            <div class="mt-4 flex flex-wrap gap-2">
              <a :href="route('vap-inventory.orders.show', order.id)" class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100">Abrir</a>
              <a v-if="order.status === 'pending' || order.status === 'approved'" :href="route('vap-inventory.orders.edit', order.id)" class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Editar</a>
              <button v-if="canReceive(order)" @click="receiveOrder(order)" class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100">Registar recepção</button>
            </div>
          </article>
        </div>
        <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido #</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fornecedor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Itens</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Prevista</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acções</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="order in orders.data" 
              :key="order.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <ShoppingCartIcon class="h-4 w-4 text-blue-900" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">
                      {{ order.seq || 'ORD-' + order.id }}
                    </div>
                    <div class="text-xs text-gray-500">{{ order.reference || 'Sem referência' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(order.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <TruckIcon class="h-4 w-4 text-gray-600" />
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ order.supplier?.name || 'N/A' }}</div>
                    <div v-if="order.supplier?.latest_assessment" class="mt-1 flex flex-wrap gap-1">
                      <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold', supplierStatusClass(order.supplier.latest_assessment.status)]">
                        {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                      </span>
                      <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold', supplierRiskClass(order.supplier.latest_assessment.risk_level)]">
                        {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                      </span>
                    </div>
                    <div v-if="order.reception_non_conformity_summary?.open_count" class="mt-1">
                      <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold', receptionSeverityClass(order.reception_non_conformity_summary.latest_severity)]">
                        {{ order.reception_non_conformity_summary.open_count }} NC(s) de recepção aberta(s)
                      </span>
                    </div>
                    <div v-else class="text-xs text-amber-700">Sem avaliação registada</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ order.items_count || 0 }} itens</div>
                <div class="text-xs text-gray-500">{{ order.total_quantity || 0 }} unidades</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  getStatusClass(order.status)
                ]">
                  {{ formatStatus(order.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div v-if="order.earliest_expected_date">
                  {{ formatDate(order.earliest_expected_date) }}
                </div>
                <div v-else class="text-gray-400">Sem data prevista</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-3">
                  <a 
                    :href="route('vap-inventory.orders.show', order.id)"
                    class="text-blue-900 hover:text-blue-700"
                  >
                    Visualizar
                  </a>
                  <a 
                    :href="route('vap-inventory.orders.edit', order.id)"
                    class="text-green-900 hover:text-green-700"
                    v-if="order.status === 'pending' || order.status === 'approved'"
                  >
                    Modificar
                  </a>
                  <button 
                    @click="receiveOrder(order)"
                    class="text-purple-900 hover:text-purple-700"
                    v-if="canReceive(order)"
                  >
                    Receber
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-else class="p-12 text-center">
        <ShoppingCartIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum pedido encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Ajuste os filtros ou registe um novo pedido para começar
        </p>
        <a
          :href="route('vap-inventory.orders.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusIcon class="h-5 w-5" />
          Registar primeiro pedido
        </a>
      </div>

      <!-- PAGINATION -->
      <div v-if="orders.data && orders.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-sm text-gray-500">
            Mostrando {{ orders.from }} a {{ orders.to }} de {{ orders.total }} pedidos
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="!orders.prev_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                orders.prev_page_url 
                  ? 'text-gray-700 hover:bg-gray-100' 
                  : 'text-gray-400 cursor-not-allowed'
              ]"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="!orders.next_page_url"
              :class="[
                'rounded-lg px-3 py-2 text-sm font-medium',
                orders.next_page_url 
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ShoppingCartIcon,
  PlusIcon,
  CheckCircleIcon,
  TruckIcon,
  CalendarIcon,
  ClockIcon,
  CalendarDaysIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  ListBulletIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  orders: {
    type: Object,
    default: () => ({ data: [], links: {} })
  },
  suppliers: {
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
  status: '',
  supplier_id: '',
  date_from: '',
  date_to: '',
  search: '',
  sort_by: 'created_at',
  sort_direction: 'desc'
})

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA',
    minimumFractionDigits: 2
  }).format(value)
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatStatus(status) {
  const statusMap = {
    'PENDING': 'Pendente',
    'APPROVED': 'Aprovado',
    'ORDERED': 'Pedido',
    'PARTIALLY_RECEIVED': 'Recebido Parcialmente',
    'RECEIVED': 'Recebido',
    'CANCELLED': 'Cancelado',
    'COMPLETED': 'Concluído',
  }
  return statusMap[status] || status
}

function getStatusClass(status) {
  const classMap = {
    'PENDING': 'bg-yellow-100 text-yellow-800',
    'APPROVED': 'bg-blue-100 text-blue-800',
    'ORDERED': 'bg-purple-100 text-purple-800',
    'PARTIALLY_RECEIVED': 'bg-orange-100 text-orange-800',
    'RECEIVED': 'bg-green-100 text-green-800',
    'CANCELLED': 'bg-red-100 text-red-800',
    'COMPLETED': 'bg-green-100 text-green-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

function supplierStatusLabel(status) {
  const map = {
    approved: 'Aprovado',
    conditional: 'Condicional',
    suspended: 'Suspenso',
    rejected: 'Rejeitado',
  }

  return map[status] || status || 'Sem avaliação'
}

function supplierRiskLabel(risk) {
  const map = {
    low: 'Risco baixo',
    medium: 'Risco médio',
    high: 'Risco elevado',
    critical: 'Risco crítico',
  }

  return map[risk] || risk || 'Sem classificação'
}

function receptionSeverityClass(severity) {
  const classMap = {
    low: 'bg-emerald-100 text-emerald-800',
    medium: 'bg-amber-100 text-amber-800',
    high: 'bg-orange-100 text-orange-800',
    critical: 'bg-rose-100 text-rose-800',
  }

  return classMap[severity] || 'bg-slate-100 text-slate-700'
}

function supplierStatusClass(status) {
  const map = {
    approved: 'bg-emerald-100 text-emerald-800',
    conditional: 'bg-amber-100 text-amber-800',
    suspended: 'bg-orange-100 text-orange-800',
    rejected: 'bg-rose-100 text-rose-800',
  }

  return map[status] || 'bg-gray-100 text-gray-700'
}

function supplierRiskClass(risk) {
  const map = {
    low: 'bg-emerald-100 text-emerald-800',
    medium: 'bg-sky-100 text-sky-800',
    high: 'bg-amber-100 text-amber-800',
    critical: 'bg-rose-100 text-rose-800',
  }

  return map[risk] || 'bg-gray-100 text-gray-700'
}

function canReceive(order) {
  return ['ORDERED', 'PARTIALLY_RECEIVED'].includes(order.status)
}

function receiveOrder(order) {
  router.visit(route('vap-inventory.orders.show', order.id))
}

function applyFilters() {
  filters.get(route('vap-inventory.orders.index'), {
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

function previousPage() {
  if (props.orders.prev_page_url) {
    router.visit(props.orders.prev_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false
    })
  }
}

function nextPage() {
  if (props.orders.next_page_url) {
    router.visit(props.orders.next_page_url, {
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
