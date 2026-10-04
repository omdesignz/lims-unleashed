<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Controlo de aprovisionamento</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument" />
              Pedidos e recepções
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">Pedidos de inventário e compra</h1>
          <p class="ds-copy mt-2 text-sm">
            Faça a gestão de pedidos de compra, recepções parciais, avaliação de fornecedores e evidências de não conformidade na recepção.
          </p>
        </div>

        <Link :href="route('vap-inventory.orders.create')" class="ds-button ds-button-primary shrink-0">
          <PlusIcon class="h-4 w-4" />
          Novo pedido
        </Link>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in statsCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <FunnelIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
          <div>
            <h2 class="ds-heading text-base">Filtros de procurement</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Refine por estado, fornecedor, datas e referência antes de executar recepções ou revisões.</p>
          </div>
        </div>
        <span class="ds-chip">
          <span class="lims-status-dot" :class="activeFilterCount ? 'lims-status-dot-hold' : 'lims-status-dot-release'" />
          {{ activeFilterCount }} filtros activos
        </span>
      </div>

      <div class="grid gap-4 p-5 lg:grid-cols-5">
        <BaseSelect v-model="filters.status" label="Estado">
          <option value="">Todos os estados</option>
          <option value="pending">Pendentes</option>
          <option value="approved">Aprovados</option>
          <option value="ordered">Ordenados</option>
          <option value="partially_received">Recebidos parcialmente</option>
          <option value="received">Recebidos</option>
          <option value="cancelled">Cancelados</option>
        </BaseSelect>

        <div class="lg:col-span-2">
          <label class="ds-field-label">Fornecedor</label>
          <comboboxEnhanced
            v-model="selectedSupplierFilter"
            :options="supplierOptions"
            placeholder="Todos os fornecedores"
          />
        </div>

        <BaseInput v-model="filters.date_from" type="date" label="Data inicial" />
        <BaseInput v-model="filters.date_to" type="date" label="Data final" />
      </div>

      <div class="grid gap-4 border-t border-[color:var(--ds-border)] px-5 py-4 lg:grid-cols-[1fr_auto]">
        <BaseInput
          v-model="filters.search"
          type="search"
          label="Pesquisa"
          placeholder="Pesquisar por pedido, referência ou item"
          @keyup.enter="applyFilters"
        >
          <template #leading>
            <MagnifyingGlassIcon class="h-4 w-4" />
          </template>
        </BaseInput>

        <div class="flex items-end gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="filters.processing" @click="resetFilters">
            Redefinir
          </button>
          <button type="button" class="ds-button ds-button-primary" :disabled="filters.processing" @click="applyFilters">
            <FunnelIcon class="h-4 w-4" />
            Aplicar filtros
          </button>
        </div>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1fr_22rem]">
      <article class="ds-panel overflow-hidden">
        <div class="ds-table-summary px-5 py-4">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-3">
              <ListBulletIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Lista de pedidos</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                  {{ orders.total || 0 }} pedidos com fornecedor, recepção e risco associados.
                </p>
              </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
              <BaseSelect v-model="filters.sort_by" label="Ordenar por" @change="applyFilters">
                <option value="created_at">Criação</option>
                <option value="date">Data de pedido</option>
                <option value="reference">Referência</option>
              </BaseSelect>
              <BaseSelect v-model="filters.sort_direction" label="Direcção" @change="applyFilters">
                <option value="desc">Mais recentes</option>
                <option value="asc">Mais antigos</option>
              </BaseSelect>
            </div>
          </div>
        </div>

        <div v-if="loading" class="p-8">
          <div class="ds-empty-state p-6 text-center">
            <ArrowPathIcon class="mx-auto h-8 w-8 animate-spin text-[color:var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-semibold text-[color:var(--ds-text-soft)]">A carregar pedidos...</p>
          </div>
        </div>

        <div v-else-if="orderRows.length" class="ds-table-shell overflow-x-auto">
          <DataTable class="min-w-[76rem]">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-cell text-left">Pedido</th>
                <th class="ds-table-cell text-left">Fornecedor</th>
                <th class="ds-table-cell text-left">Itens</th>
                <th class="ds-table-cell text-left">Estado</th>
                <th class="ds-table-cell text-left">Datas</th>
                <th class="ds-table-cell text-left">Acções</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orderRows" :key="order.id" class="ds-table-row">
                <td class="ds-table-cell align-top">
                  <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)] text-primary-700 dark:text-primary-300">
                      <ShoppingCartIcon class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                      <p class="font-mono text-xs font-bold text-primary-800 dark:text-primary-200">{{ order.seq || `ORD-${order.id}` }}</p>
                      <p class="mt-1 max-w-64 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ order.reference || 'Sem referência' }}</p>
                      <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ formatDate(order.date) }}</p>
                    </div>
                  </div>
                </td>

                <td class="ds-table-cell align-top">
                  <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ order.supplier?.name || 'N/A' }}</p>
                  <div v-if="order.supplier?.latest_assessment" class="mt-2 flex flex-wrap gap-2">
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="supplierStatusDotClass(order.supplier.latest_assessment.status)" />
                      {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                    </span>
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="supplierRiskDotClass(order.supplier.latest_assessment.risk_level)" />
                      {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                    </span>
                  </div>
                  <div v-else class="mt-2">
                    <span class="ds-chip">
                      <span class="lims-status-dot lims-status-dot-hold" />
                      Sem avaliação
                    </span>
                  </div>
                  <div v-if="order.reception_non_conformity_summary?.open_count" class="mt-2">
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="receptionSeverityDotClass(order.reception_non_conformity_summary.latest_severity)" />
                      {{ order.reception_non_conformity_summary.open_count }} NC(s) abertas
                    </span>
                  </div>
                </td>

                <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text)]">
                  <div><span class="font-bold">{{ formatQuantity(order.items_count) }}</span> itens</div>
                  <div class="mt-1 text-xs font-semibold text-[color:var(--ds-text)]">{{ formatCurrency(order.total_amount || 0) }}</div>
                </td>

                <td class="ds-table-cell align-top">
                  <span class="ds-chip">
                    <span class="lims-status-dot" :class="statusDotClass(order.status)" />
                    {{ formatStatus(order.status) }}
                  </span>
                </td>

                <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text)]">
                  <div>Pedido: <span class="font-bold">{{ formatDate(order.date) }}</span></div>
                  <div class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                    Previsão: <span class="font-bold text-[color:var(--ds-text)]">{{ order.earliest_expected_date ? formatDate(order.earliest_expected_date) : 'Sem data prevista' }}</span>
                  </div>
                </td>

                <td class="ds-table-cell align-top">
                  <div class="flex flex-wrap gap-3">
                    <Link :href="route('vap-inventory.orders.show', order.id)" class="ds-table-action">
                      Visualizar
                    </Link>
                    <Link v-if="canEdit(order)" :href="route('vap-inventory.orders.edit', order.id)" class="ds-table-action">
                      Modificar
                    </Link>
                    <button v-if="canReceive(order)" type="button" class="ds-table-action" @click="receiveOrder(order)">
                      Receber
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div v-else class="p-5">
          <div class="ds-empty-state p-6 text-center">
            <ShoppingCartIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
            <h3 class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">Nenhum pedido encontrado</h3>
            <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">Ajuste os filtros ou registe um novo pedido para começar.</p>
            <Link :href="route('vap-inventory.orders.create')" class="ds-button ds-button-primary mt-4">
              <PlusIcon class="h-4 w-4" />
              Registar primeiro pedido
            </Link>
          </div>
        </div>

        <div v-if="orderRows.length" class="border-t border-[color:var(--ds-border)] px-5 py-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-[color:var(--ds-text-soft)]">
              Mostrando {{ orders.from }} a {{ orders.to }} de {{ orders.total }} pedidos
            </p>
            <div class="flex gap-2">
              <button type="button" class="ds-button ds-button-secondary" :disabled="!orders.prev_page_url" @click="previousPage">
                Anterior
              </button>
              <button type="button" class="ds-button ds-button-secondary" :disabled="!orders.next_page_url" @click="nextPage">
                Próxima
              </button>
            </div>
          </div>
        </div>
      </article>

      <aside class="space-y-4">
        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Fila operacional</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Sinais de recepção, risco e itens em aberto.</p>
          </div>
          <div class="grid gap-3 p-5">
            <div v-for="signal in procurementSignals" :key="signal.label" class="ds-card p-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ signal.label }}</p>
                  <p class="mt-2 text-2xl font-bold" :class="signal.valueClass">{{ signal.value }}</p>
                  <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ signal.caption }}</p>
                </div>
                <span class="lims-status-dot mt-1" :class="signal.dotClass" />
              </div>
            </div>
          </div>
        </article>

        <article class="ds-command-surface p-5">
          <h2 class="ds-heading text-base">Próximas acções</h2>
          <div class="mt-4 grid gap-2">
            <Link :href="route('vap-inventory.orders.create')" class="ds-button ds-button-primary w-full">
              <PlusIcon class="h-4 w-4" />
              Novo pedido
            </Link>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="resetFilters">
              Redefinir filtros
            </button>
          </div>
        </article>
      </aside>
    </section>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import {
  RefreshCw as ArrowPathIcon,
  Funnel as FunnelIcon,
  List as ListBulletIcon,
  Search as MagnifyingGlassIcon,
  Plus as PlusIcon,
  ShoppingCart as ShoppingCartIcon,
} from '@lucide/vue'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  orders: {
    type: Object,
    default: () => ({ data: [], links: {} }),
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  nonConformitiesAvailable: {
    type: Boolean,
    default: false,
  },
  receivingAbilities: { type: Object, default: () => ({ receive: false, register_non_conformity: false }) },
})

const loading = ref(false)

const filters = useForm({
  status: props.filters.status ?? '',
  supplier_id: props.filters.supplier_id ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  search: props.filters.search ?? '',
  sort_by: props.filters.sort_by ?? 'created_at',
  sort_direction: props.filters.sort_direction ?? 'desc',
})

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
  value: supplier.id,
  label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
})))

const selectedSupplierFilter = ref(initialSupplierOption())
const orderRows = computed(() => props.orders.data || [])

watch(selectedSupplierFilter, (supplier) => {
  filters.supplier_id = supplier?.value || ''
})

const activeFilterCount = computed(() => [
  filters.status,
  filters.supplier_id,
  filters.date_from,
  filters.date_to,
  filters.search,
].filter(Boolean).length)

const receivableOrdersCount = computed(() => orderRows.value.filter((order) => canReceive(order)).length)
const openReceptionNonConformities = computed(() => orderRows.value.reduce((sum, order) => (
  sum + Number(order.reception_non_conformity_summary?.open_count || 0)
), 0))
const unassessedSupplierOrders = computed(() => orderRows.value.filter((order) => !order.supplier?.latest_assessment).length)

const statsCards = computed(() => [
  {
    label: 'Total',
    value: formatQuantity(props.stats.total_orders),
    caption: 'Pedidos registados',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Pendentes',
    value: formatQuantity(props.stats.pending_orders),
    caption: 'Aguardam decisão',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-amber-700 dark:text-amber-300',
  },
  {
    label: 'Hoje',
    value: formatQuantity(props.stats.orders_today),
    caption: 'Pedidos criados hoje',
    dotClass: 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Valor',
    value: formatCurrency(props.stats.total_value || 0),
    caption: 'Pedidos não cancelados',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Itens abertos',
    value: formatQuantity(props.stats.open_items),
    caption: 'Pendentes ou parciais',
    dotClass: 'lims-status-dot-critical',
    valueClass: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Na página',
    value: formatQuantity(orderRows.value.length),
    caption: 'Registos visíveis',
    dotClass: 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

const procurementSignals = computed(() => [
  {
    label: 'Recepção aberta',
    value: formatQuantity(receivableOrdersCount.value),
    caption: 'Pedidos ordenados ou parciais',
    dotClass: receivableOrdersCount.value ? 'lims-status-dot-hold' : 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Não conformidades',
    value: props.nonConformitiesAvailable ? formatQuantity(openReceptionNonConformities.value) : '—',
    caption: props.nonConformitiesAvailable ? 'NCs de recepção em aberto' : 'Registo indisponível',
    dotClass: !props.nonConformitiesAvailable ? 'lims-status-dot-hold' : openReceptionNonConformities.value ? 'lims-status-dot-critical' : 'lims-status-dot-release',
    valueClass: props.nonConformitiesAvailable && openReceptionNonConformities.value ? 'text-rose-700 dark:text-rose-300' : 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Sem avaliação',
    value: formatQuantity(unassessedSupplierOrders.value),
    caption: 'Fornecedores sem avaliação na página',
    dotClass: unassessedSupplierOrders.value ? 'lims-status-dot-hold' : 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

function initialSupplierOption() {
  if (!props.filters.supplier_id) {
    return null
  }

  const supplier = props.suppliers.find((supplierItem) => supplierItem.id == props.filters.supplier_id)

  if (!supplier) {
    return null
  }

  return {
    value: supplier.id,
    label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
  }
}

function normalizeStatus(status) {
  return String(status || '').toUpperCase()
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA',
    minimumFractionDigits: 2,
  }).format(Number(value || 0))
}

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 2 }).format(numericValue)
}

function formatDate(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function formatStatus(status) {
  const statusMap = {
    PENDING: 'Pendente',
    APPROVED: 'Aprovado',
    ORDERED: 'Pedido',
    PARTIALLY_RECEIVED: 'Recebido parcialmente',
    RECEIVED: 'Recebido',
    CANCELLED: 'Cancelado',
    COMPLETED: 'Concluído',
  }

  return statusMap[normalizeStatus(status)] || status || 'Sem estado'
}

function statusDotClass(status) {
  const classMap = {
    PENDING: 'lims-status-dot-hold',
    APPROVED: 'lims-status-dot-instrument',
    ORDERED: 'lims-status-dot-instrument',
    PARTIALLY_RECEIVED: 'lims-status-dot-hold',
    RECEIVED: 'lims-status-dot-release',
    COMPLETED: 'lims-status-dot-release',
    CANCELLED: 'lims-status-dot-critical',
  }

  return classMap[normalizeStatus(status)] || 'lims-status-dot-instrument'
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

function receptionSeverityDotClass(severity) {
  const classMap = {
    low: 'lims-status-dot-release',
    medium: 'lims-status-dot-hold',
    high: 'lims-status-dot-critical',
    critical: 'lims-status-dot-critical',
  }

  return classMap[severity] || 'lims-status-dot-hold'
}

function supplierStatusDotClass(status) {
  const map = {
    approved: 'lims-status-dot-release',
    conditional: 'lims-status-dot-hold',
    suspended: 'lims-status-dot-critical',
    rejected: 'lims-status-dot-critical',
  }

  return map[status] || 'lims-status-dot-hold'
}

function supplierRiskDotClass(risk) {
  const map = {
    low: 'lims-status-dot-release',
    medium: 'lims-status-dot-instrument',
    high: 'lims-status-dot-hold',
    critical: 'lims-status-dot-critical',
  }

  return map[risk] || 'lims-status-dot-hold'
}

function canEdit(order) {
  return ['PENDING', 'APPROVED'].includes(normalizeStatus(order.status))
}

function canReceive(order) {
  return props.receivingAbilities.receive && ['ORDERED', 'PARTIALLY_RECEIVED'].includes(normalizeStatus(order.status))
}

function receiveOrder(order) {
  if (!canReceive(order)) return
  router.visit(route('vap-inventory.orders.show', order.id))
}

function applyFilters() {
  filters.get(route('vap-inventory.orders.index'), {
    preserveScroll: true,
    preserveState: true,
    onStart: () => {
      loading.value = true
    },
    onFinish: () => {
      loading.value = false
    },
  })
}

function resetFilters() {
  filters.status = ''
  filters.supplier_id = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.search = ''
  filters.sort_by = 'created_at'
  filters.sort_direction = 'desc'
  selectedSupplierFilter.value = null

  applyFilters()
}

function previousPage() {
  if (props.orders.prev_page_url) {
    router.visit(props.orders.prev_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => {
        loading.value = true
      },
      onFinish: () => {
        loading.value = false
      },
    })
  }
}

function nextPage() {
  if (props.orders.next_page_url) {
    router.visit(props.orders.next_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => {
        loading.value = true
      },
      onFinish: () => {
        loading.value = false
      },
    })
  }
}
</script>
