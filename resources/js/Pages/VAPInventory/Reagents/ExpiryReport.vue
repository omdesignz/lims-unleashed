<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { Eye as EyeIcon, SquarePen as PencilSquareIcon, X as XMarkIcon } from '@lucide/vue'

/**
 * Reagent expiry report (Plano page). Reagents are listed first-expired,
 * first-out; the state cells are laboratory-wide counts that filter the list.
 */
const props = defineProps({
  reagents: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
})

const loading = ref(false)
const exporting = ref(false)
const exportError = ref('')
const filters = reactive({
  status: props.filters?.status ?? '',
  category_id: props.filters?.category_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: ['expiry_date', 'name', 'current_stock'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'expiry_date',
  sort_direction: props.filters?.sort_direction === 'desc' ? 'desc' : 'asc',
})

const reagentRows = computed(() => props.reagents?.data || [])
const goodCount = computed(() => Math.max(Number(props.stats?.total_reagents || 0) - Number(props.stats?.expired || 0) - Number(props.stats?.expiring_soon || 0), 0))

const cells = computed(() => [
  { key: '', label: 'Todos', value: props.stats?.total_reagents || 0 },
  { key: 'expired', label: 'Expirados', value: props.stats?.expired || 0, tone: props.stats?.expired ? 'bad' : undefined },
  { key: 'expiring_soon', label: 'Até 60 dias', value: props.stats?.expiring_soon || 0 },
  { key: 'good', label: 'Mais de 60 dias', value: goodCount.value },
])

const lede = computed(() => {
  const expired = Number(props.stats?.expired || 0)
  const windows = `Próximos 30 dias: ${props.stats?.expiring_30 || 0} · 31 a 60: ${props.stats?.expiring_31_60 || 0} · 61 a 90: ${props.stats?.expiring_61_90 || 0}.`

  return expired
    ? `${expired} ${expired === 1 ? 'reagente expirado deve' : 'reagentes expirados devem'} sair de uso e ser segregados até à disposição. ${windows}`
    : `Nenhum reagente expirado. ${windows}`
})

const hasActiveFilters = computed(() => Boolean(filters.status || filters.category_id || filters.warehouse_id || filters.search))

function daysToExpiry(reagent) {
  if (Number.isFinite(Number(reagent.days_to_expiry))) return Number(reagent.days_to_expiry)
  if (!reagent.reagent_expiry_date) return null
  const expiry = new Date(`${String(reagent.reagent_expiry_date).slice(0, 10)}T00:00:00Z`)
  const today = new Date()
  const todayUtc = Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate())
  return Math.round((expiry.getTime() - todayUtc) / 86400000)
}

function isExpired(reagent) {
  return reagent.is_expired === true || Number(daysToExpiry(reagent)) < 0
}

function statusLabel(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null) return 'Sem data'
  if (days < 0) return 'Expirado'
  if (days <= 30) return 'Prioridade FEFO'
  if (days <= 60) return 'Revisão próxima'
  return 'Conforme'
}

function statusTone(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null || days < 0) return 'bad'
  if (days <= 60) return 'wait'
  return 'ok'
}

function statusInstruction(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null) return 'Completar dados de validade.'
  if (days < 0) return 'Segregar e iniciar revisão de disposição.'
  if (days <= 30) return 'Consumir primeiro ou planear substituição.'
  if (days <= 60) return 'Confirmar plano de utilização do lote.'
  return 'Manter monitorização FEFO.'
}

function daysLabel(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null) return 'Sem data'
  if (days < 0) return `${formatNumber(Math.abs(days))} dias vencido`
  if (days === 0) return 'Expira hoje'
  return `${formatNumber(days)} dias`
}

function shelfLifePercentage(reagent) {
  if (!reagent.reagent_open_date || !reagent.reagent_expiry_date) return 0
  const opened = new Date(reagent.reagent_open_date).getTime()
  const expiry = new Date(reagent.reagent_expiry_date).getTime()
  const total = expiry - opened
  if (total <= 0) return 100
  return Math.min(Math.max(((Date.now() - opened) / total) * 100, 0), 100)
}

function warehouseNames(reagent) {
  const names = (reagent.inventory || []).map((position) => position.warehouse?.name).filter(Boolean)
  return names.length ? [...new Set(names)].join(', ') : 'Sem posição de existências'
}

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 1 }).format(Number(value || 0))
}

function clearFilters() {
  Object.assign(filters, {
    status: '',
    category_id: '',
    warehouse_id: '',
    search: '',
    sort_by: 'expiry_date',
    sort_direction: 'asc',
  })
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

async function exportReport() {
  exporting.value = true
  exportError.value = ''

  try {
    const response = await fetch(route('vap-inventory.analytics.report'), {
      method: 'POST',
      headers: {
        Accept: 'application/pdf',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({ reportType: 'expiry', format: 'pdf', dateRange: '1y' }),
    })
    if (!response.ok) throw new Error('Não foi possível gerar o relatório de validade.')
    const blob = await response.blob()
    const objectUrl = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = objectUrl
    anchor.download = `expiry_report_${new Date().toISOString().split('T')[0]}.pdf`
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(objectUrl)
  } catch (error) {
    exportError.value = error instanceof Error ? error.message : 'Não foi possível gerar o relatório.'
  } finally {
    exporting.value = false
  }
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.items.reagents.expiry'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onStart: () => { loading.value = true },
      onFinish: () => { loading.value = false },
    })
  }, 350),
  { deep: true },
)
</script>

<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Validade de reagentes' }]"
      title="Validade de reagentes"
      :lede="lede"
    >
      <template #actions>
        <Link :href="route('vap-inventory.needs.create')" class="ds-button ds-button-quiet">Registar necessidade</Link>
        <Link :href="route('vap-inventory.orders.create')" class="ds-button ds-button-quiet">Preparar ordem</Link>
        <button type="button" class="ds-button ds-button-secondary" :disabled="exporting" @click="exportReport">
          {{ exporting ? 'A preparar…' : 'Exportar PDF' }}
        </button>
      </template>
    </PageHeader>

    <p v-if="exportError" class="pl-banner pl-banner-bad mb-6 text-sm" role="alert">{{ exportError }}</p>

    <StateCells v-model="filters.status" class="mb-10" :items="cells" label="Filtrar reagentes por validade" />

    <form class="pl-filter" role="search" @submit.prevent>
      <label for="expiry-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="expiry-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" placeholder="nome, código ou lote" />
      <div class="w-44">
        <BaseSelect v-model="filters.category_id" aria-label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-44">
        <BaseSelect v-model="filters.warehouse_id" aria-label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-40">
        <BaseSelect v-model="filters.sort_by" aria-label="Ordenar por">
          <option value="expiry_date">Validade</option>
          <option value="name">Nome</option>
          <option value="current_stock">Existências</option>
        </BaseSelect>
      </div>
      <div class="w-36">
        <BaseSelect v-model="filters.sort_direction" aria-label="Direcção">
          <option value="asc">Ascendente</option>
          <option value="desc">Descendente</option>
        </BaseSelect>
      </div>
      <button v-if="hasActiveFilters" type="button" class="ds-chip" @click="clearFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>

    <section class="pl-panel" aria-label="Reagentes por prioridade FEFO" :aria-busy="loading">
      <DataTable v-if="reagentRows.length">
        <thead>
          <tr>
            <th scope="col">Reagente</th>
            <th scope="col">Validade</th>
            <th scope="col">Lote e fornecedor</th>
            <th scope="col" class="text-right">Existências</th>
            <th scope="col">Estado</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="reagent in reagentRows" :key="reagent.id">
            <td>
              <Link :href="route('vap-inventory.items.show', reagent.id)" class="font-medium hover:text-[var(--pl-accent-text)]">{{ reagent.name }}</Link>
              <span class="block pl-num text-[12.5px] text-[var(--pl-muted)]">{{ reagent.internal_code || reagent.code || 'Sem código' }}</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ reagent.category?.name || 'Sem categoria' }}</span>
            </td>
            <td>
              <span class="pl-num">{{ formatDate(reagent.reagent_expiry_date) }}</span>
              <span class="block text-[12.5px]" :class="isExpired(reagent) ? 'text-[var(--pl-bad)]' : 'text-[var(--pl-muted)]'">{{ daysLabel(reagent) }}</span>
              <div v-if="reagent.reagent_open_date" class="mt-2 grid min-w-36 gap-1">
                <span class="flex justify-between gap-3 text-[12px] text-[var(--pl-muted)]"><span>Aberto {{ formatDate(reagent.reagent_open_date) }}</span><span class="pl-num">{{ formatNumber(shelfLifePercentage(reagent)) }}%</span></span>
                <span class="pl-bar block" role="img" :aria-label="`Vida útil decorrida: ${formatNumber(shelfLifePercentage(reagent))}%`"><i :class="{ 'pl-bar-late': isExpired(reagent) }" :style="{ width: `${shelfLifePercentage(reagent)}%` }" /></span>
              </div>
            </td>
            <td>
              <span class="pl-num">{{ reagent.lot || 'Sem lote' }}</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ reagent.supplier?.name || 'Sem fornecedor' }}</span>
              <span v-if="reagent.refrigerated" class="block text-[12.5px] text-[var(--pl-accent-text)]">Cadeia de frio</span>
            </td>
            <td class="text-right">
              <span class="pl-num">{{ formatNumber(reagent.total_stock) }} un.</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ reagent.warehouse_count || 0 }} posições</span>
              <span class="ml-auto block max-w-52 text-[12px] text-[var(--pl-faint)]">{{ warehouseNames(reagent) }}</span>
            </td>
            <td>
              <StatusChip :tone="statusTone(reagent)">{{ statusLabel(reagent) }}</StatusChip>
              <span class="block max-w-44 pt-1 text-[12px] text-[var(--pl-muted)]">{{ statusInstruction(reagent) }}</span>
            </td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link :href="route('vap-inventory.items.show', reagent.id)" class="ds-table-action" :aria-label="`Abrir ficha de ${reagent.name}`">
                  <EyeIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
                <Link :href="route('vap-inventory.items.edit', reagent.id)" class="ds-table-action" :aria-label="`Rever validade de ${reagent.name}`">
                  <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ hasActiveFilters ? 'Nenhum reagente neste filtro' : 'Sem reagentes com validade registada' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ hasActiveFilters ? 'Experimente outro estado, categoria ou armazém.' : 'Registe a data de validade na ficha de cada reagente para o acompanhar aqui.' }}</p>
      </div>
      <Pagination
        v-if="reagentRows.length"
        :links="reagents.links"
        :total="reagents.total"
        :from="reagents.from"
        :to="reagents.to"
        :last_page="reagents.last_page"
        :current_page="reagents.current_page"
      />
    </section>
  </div>
</template>
