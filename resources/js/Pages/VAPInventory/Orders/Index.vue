<template>
  <div class="pl-page" data-template="queue">
    <PageHeader :crumbs="[{ title: 'Inventário' }, { title: 'Pedidos de compra' }]" title="Pedidos de compra" :lede="lede">
      <template #actions>
        <Link :href="route('vap-inventory.needs.index')" class="ds-button ds-button-quiet">Necessidades</Link>
        <Link :href="route('vap-inventory.orders.create')" class="ds-button ds-button-primary">Novo pedido</Link>
      </template>
    </PageHeader>

    <StateCells class="mb-10" :items="cells" :model-value="filters.status" label="Filtrar estado dos pedidos" @update:model-value="applyStatus($event)" />

    <form class="pl-filter" role="search" @submit.prevent="applyFilters">
      <label for="orders-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="orders-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="pedido, referência ou item" />
      <button class="ds-button ds-button-quiet" type="submit" :disabled="filters.processing">{{ filters.processing ? 'A procurar…' : 'Procurar' }}</button>
      <button v-if="activeFilterCount" class="ds-chip" type="button" @click="resetFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>
    <div class="pl-filter">
      <div class="w-full sm:w-72">
        <comboboxEnhanced v-model="selectedSupplierFilter" title-label="Fornecedor" :options="supplierOptions" placeholder="Todos os fornecedores" />
      </div>
      <div class="w-40"><BaseInput id="orders-date-from" v-model="filters.date_from" type="date" label="Desde" @update:model-value="applyFilters" /></div>
      <div class="w-40"><BaseInput id="orders-date-to" v-model="filters.date_to" type="date" label="Até" @update:model-value="applyFilters" /></div>
      <div class="ml-auto flex flex-wrap items-end gap-2">
        <div class="w-44">
          <BaseSelect id="orders-sort-by" v-model="filters.sort_by" label="Ordenar por" @update:model-value="applyFilters">
            <option value="created_at">Criação</option>
            <option value="date">Data de pedido</option>
            <option value="reference">Referência</option>
          </BaseSelect>
        </div>
        <div class="w-40">
          <BaseSelect id="orders-sort-direction" v-model="filters.sort_direction" label="Direcção" @update:model-value="applyFilters">
            <option value="desc">Mais recentes</option>
            <option value="asc">Mais antigos</option>
          </BaseSelect>
        </div>
      </div>
    </div>

    <section class="pl-panel" aria-label="Pedidos de compra" :aria-busy="filters.processing">
      <DataTable v-if="orderRows.length">
        <thead>
          <tr>
            <th scope="col">Pedido</th>
            <th scope="col">Fornecedor</th>
            <th scope="col" class="text-right">Linhas</th>
            <th scope="col" class="text-right">Valor</th>
            <th scope="col">Estado</th>
            <th scope="col" class="text-right">Pedido em</th>
            <th scope="col" class="text-right">Previsão</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orderRows" :key="order.id">
            <td>
              <Link :href="route('vap-inventory.orders.show', order.id)" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">{{ order.seq || `ORD-${order.id}` }}</Link>
              <span class="block max-w-64 truncate text-[12.5px] text-[var(--pl-muted)]">{{ order.reference || 'Sem referência' }}</span>
            </td>
            <td class="!whitespace-normal">
              {{ order.supplier?.name || 'Sem fornecedor' }}
              <span class="block text-[12.5px] text-[var(--pl-muted)]">
                <template v-if="order.supplier?.latest_assessment">{{ supplierStatusLabel(order.supplier.latest_assessment.status) }} · {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}</template>
                <template v-else>Sem avaliação</template>
              </span>
              <StatusChip v-if="order.reception_non_conformity_summary?.open_count" class="mt-1" :tone="receptionSeverityTone(order.reception_non_conformity_summary.latest_severity)">
                {{ order.reception_non_conformity_summary.open_count }} NC abertas
              </StatusChip>
            </td>
            <td class="pl-num text-right">{{ formatQuantity(order.items_count) }}</td>
            <td class="pl-num text-right">{{ formatCurrency(order.total_amount || 0) }}</td>
            <td><StatusChip :tone="statusTone(order.status)">{{ formatStatus(order.status) }}</StatusChip></td>
            <td class="pl-num text-right">{{ formatDate(order.date) }}</td>
            <td class="pl-num text-right">{{ order.earliest_expected_date ? formatDate(order.earliest_expected_date) : '—' }}</td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link v-if="canReceive(order)" :href="route('vap-inventory.orders.show', order.id)" class="ds-table-action" :aria-label="`Receber pedido ${order.seq || order.id}`">Receber</Link>
                <Link v-if="canEdit(order)" :href="route('vap-inventory.orders.edit', order.id)" class="ds-table-action" :aria-label="`Modificar pedido ${order.seq || order.id}`"><PencilIcon class="h-4 w-4" aria-hidden="true" /></Link>
                <Link :href="route('vap-inventory.orders.show', order.id)" class="ds-table-action" :aria-label="`Abrir pedido ${order.seq || order.id}`"><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ activeFilterCount ? 'Nenhum pedido neste filtro' : 'Ainda não há pedidos de compra' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ activeFilterCount ? 'Experimente outro termo, estado, fornecedor ou intervalo de datas.' : 'Registe um pedido ou converta uma necessidade aprovada para começar.' }}</p>
        <Link v-if="!activeFilterCount" :href="route('vap-inventory.orders.create')" class="ds-button ds-button-primary mt-2">Registar primeiro pedido</Link>
      </div>
      <Pagination
        v-if="orderRows.length"
        :links="orders.links"
        :from="orders.from"
        :to="orders.to"
        :total="orders.total"
        :current_page="orders.current_page"
        :last_page="orders.last_page"
      />
    </section>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowRight as ArrowRightIcon, Pencil as PencilIcon, X as XMarkIcon } from '@lucide/vue'
import { computed, ref, watch } from 'vue'

/**
 * Purchase orders of the active laboratory (Plano queue). The state cells count orders
 * per tracking status and filter the list; the supplier, date range and sort refine it.
 */
const props = defineProps({
  orders: {
    type: Object,
    default: () => ({ data: [], links: [] }),
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
})

const filters = useForm({
  status: normalizeStatus(props.filters.status),
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
  const supplierId = supplier?.value || ''

  if (String(supplierId) === String(filters.supplier_id)) {
    return
  }

  filters.supplier_id = supplierId
  applyFilters()
})

const activeFilterCount = computed(() => [
  filters.status,
  filters.supplier_id,
  filters.date_from,
  filters.date_to,
  filters.search,
].filter(Boolean).length)

const statusCount = (status) => Number(props.stats?.by_status?.[status] ?? 0)

const cells = computed(() => [
  { key: '', label: 'Todos', value: props.stats.total_orders ?? 0 },
  { key: 'PENDING', label: 'Pendentes', value: statusCount('PENDING'), tone: statusCount('PENDING') ? 'bad' : undefined },
  { key: 'APPROVED', label: 'Aprovados', value: statusCount('APPROVED') },
  { key: 'ORDERED', label: 'Encomendados', value: statusCount('ORDERED') },
  { key: 'PARTIALLY_RECEIVED', label: 'Parciais', value: statusCount('PARTIALLY_RECEIVED') },
  { key: 'RECEIVED', label: 'Recebidos', value: statusCount('RECEIVED') },
  { key: 'CANCELLED', label: 'Cancelados', value: statusCount('CANCELLED') },
])

const lede = computed(() => {
  const toReceive = statusCount('ORDERED') + statusCount('PARTIALLY_RECEIVED')
  const pending = statusCount('PENDING')
  const parts = [
    toReceive ? `${toReceive} ${toReceive === 1 ? 'pedido espera' : 'pedidos esperam'} recepção` : 'Nenhum pedido espera recepção',
    pending ? `${pending} por decidir` : null,
    `${formatCurrency(props.stats.total_value || 0)} em pedidos não cancelados`,
  ]

  return `${parts.filter(Boolean).join(' · ')}.`
})

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
    return '—'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '—'
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
    ORDERED: 'Encomendado',
    PARTIALLY_RECEIVED: 'Recebido parcialmente',
    RECEIVED: 'Recebido',
    CANCELLED: 'Cancelado',
    COMPLETED: 'Concluído',
  }

  return statusMap[normalizeStatus(status)] || status || 'Sem estado'
}

function statusTone(status) {
  return {
    PENDING: 'wait',
    APPROVED: 'ok',
    ORDERED: 'run',
    PARTIALLY_RECEIVED: 'run',
    RECEIVED: 'done',
    COMPLETED: 'done',
    CANCELLED: 'bad',
  }[normalizeStatus(status)] || 'neutral'
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
    low: 'risco baixo',
    medium: 'risco médio',
    high: 'risco elevado',
    critical: 'risco crítico',
  }

  return map[risk] || risk || 'sem classificação'
}

function receptionSeverityTone(severity) {
  return ['high', 'critical'].includes(severity) ? 'bad' : 'wait'
}

function canEdit(order) {
  return ['PENDING', 'APPROVED'].includes(normalizeStatus(order.status))
}

function canReceive(order) {
  return ['ORDERED', 'PARTIALLY_RECEIVED'].includes(normalizeStatus(order.status))
}

function applyFilters() {
  filters.get(route('vap-inventory.orders.index'), {
    preserveScroll: true,
    preserveState: true,
  })
}

function applyStatus(status) {
  filters.status = status
  applyFilters()
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
</script>
