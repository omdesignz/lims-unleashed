<template>
  <div class="pl-page" data-template="queue">
    <PageHeader :crumbs="[{ title: 'Inventário' }, { title: 'Necessidades' }]" title="Necessidades de laboratório" :lede="lede">
      <template #actions>
        <Link :href="route('vap-inventory.orders.index')" class="ds-button ds-button-quiet">Pedidos de compra</Link>
        <Link :href="route('vap-inventory.needs.create')" class="ds-button ds-button-primary">Nova necessidade</Link>
      </template>
    </PageHeader>

    <StateCells class="mb-10" :items="cells" :model-value="localFilters.status" label="Filtrar estado das necessidades" @update:model-value="applyStatus($event)" />

    <section v-if="procurementQueue?.length" class="pl-panel mb-10" aria-labelledby="needs-queue-title">
      <div class="pl-panel-head">
        <h2 id="needs-queue-title" class="pl-k">Aprovadas sem pedido de compra</h2>
        <span class="pl-k pl-faint">{{ procurementQueue.length }} em fila</span>
      </div>
      <Link v-for="need in procurementQueue" :key="`queue-${need.id}`" :href="route('vap-inventory.needs.show', need.id)" class="pl-row">
        <span class="grid min-w-0 gap-1">
          <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="pl-num font-medium">{{ need.reference }}</span>
            <span>{{ need.department?.name || 'Departamento por definir' }}<template v-if="need.lab"> · {{ need.lab.name }}</template></span>
            <StatusChip :tone="urgencyTone(need)">{{ queueUrgencyLabel(need) }}</StatusChip>
            <StatusChip :tone="readinessTone(need.supplier_readiness)">{{ readinessLabel(need.supplier_readiness) }}</StatusChip>
          </span>
          <span class="text-[12.5px] text-[var(--pl-muted)]">
            {{ need.items_count }} {{ need.items_count === 1 ? 'item' : 'itens' }} · até {{ formatDate(need.needed_by_date) }} · {{ need.requested_by?.name || 'Solicitante por identificar' }}<template v-if="supplierSummaryText(need)"> · {{ supplierSummaryText(need) }}</template>
          </span>
        </span>
        <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
      </Link>
    </section>

    <form class="pl-filter" role="search" @submit.prevent="applyFilters">
      <label for="needs-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="needs-search" v-model="localFilters.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="referência, laboratório ou justificação" />
      <div class="w-56">
        <BaseSelect v-model="localFilters.department_id" aria-label="Departamento" @update:model-value="applyFilters">
          <option value="">Todos os departamentos</option>
          <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
        </BaseSelect>
      </div>
      <button class="ds-button ds-button-quiet" type="submit">Procurar</button>
      <button v-if="hasFilters" class="ds-chip" type="button" @click="resetFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>

    <section class="pl-panel" aria-label="Necessidades">
      <DataTable v-if="needs.data.length">
        <thead>
          <tr>
            <th scope="col">Necessidade</th>
            <th scope="col">Âmbito</th>
            <th scope="col">Estado</th>
            <th scope="col">Solicitante</th>
            <th scope="col" class="text-right">Prazo</th>
            <th scope="col"><span class="sr-only">Abrir</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="need in needs.data" :key="need.id">
            <td class="!whitespace-normal">
              <Link :href="route('vap-inventory.needs.show', need.id)" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">{{ need.reference }}</Link>
              <span class="block max-w-72 truncate text-[12.5px] text-[var(--pl-muted)]">{{ need.justification || 'Sem justificação adicional.' }}</span>
            </td>
            <td>
              {{ need.department?.name || 'Departamento por definir' }}
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ need.lab?.name || 'Laboratório não definido' }} · {{ need.items_count }} {{ need.items_count === 1 ? 'item' : 'itens' }}</span>
            </td>
            <td>
              <StatusChip :tone="statusTone(need.status)">{{ formatStatus(need.status) }}</StatusChip>
              <span v-if="need.inventory_order" class="pl-num block pt-1 text-[12px] text-[var(--pl-muted)]">{{ need.inventory_order.reference || `Pedido #${need.inventory_order.id}` }}</span>
            </td>
            <td>{{ need.requested_by?.name || '—' }}</td>
            <td class="pl-num text-right">{{ formatDate(need.needed_by_date) }}</td>
            <td class="text-right"><Link :href="route('vap-inventory.needs.show', need.id)" class="pl-k pl-acc" :aria-label="`Abrir necessidade ${need.reference}`">Abrir →</Link></td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ hasFilters ? 'Nenhuma necessidade neste filtro' : 'Ainda não há necessidades' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ hasFilters ? 'Experimente outro termo, estado ou departamento.' : 'Registe uma necessidade para a submeter à aprovação e, depois, convertê-la em pedido de compra.' }}</p>
        <Link v-if="!hasFilters" :href="route('vap-inventory.needs.create')" class="ds-button ds-button-primary mt-2">Nova necessidade</Link>
      </div>
      <Pagination
        v-if="needs.data.length"
        :links="needs.links"
        :from="needs.from"
        :to="needs.to"
        :total="needs.total"
        :current_page="needs.current_page"
        :last_page="needs.last_page"
      />
    </section>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import { ArrowRight as ArrowRightIcon, X as XMarkIcon } from '@lucide/vue'

defineOptions({ layout: Layout })

/**
 * Laboratory needs (Plano queue): what waits for approval, what was approved and still
 * has no purchase order, and the full register filtered by state and department.
 * The `charts` prop is still served by the controller (and pinned by its tests); the
 * queue no longer draws it, the state cells carry the same counts.
 */
const props = defineProps({
  needs: Object,
  departments: Array,
  filters: Object,
  stats: Object,
  procurementQueue: Array,
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const localFilters = reactive({
  search: props.filters?.search ?? '',
  status: props.filters?.status ?? '',
  department_id: props.filters?.department_id ?? '',
})

const statusCount = (status) => Number(props.stats?.by_status?.[status] ?? 0)

const cells = computed(() => [
  { key: '', label: 'Todas', value: props.stats?.total ?? 0 },
  { key: 'submitted', label: 'Por aprovar', value: statusCount('submitted'), tone: statusCount('submitted') ? 'bad' : undefined },
  { key: 'approved', label: 'Aprovadas', value: statusCount('approved') },
  { key: 'ordered', label: 'Em pedido', value: statusCount('ordered') },
  { key: 'rejected', label: 'Rejeitadas', value: statusCount('rejected') },
])

const lede = computed(() => {
  const awaiting = Number(props.stats?.awaiting_order || 0)
  const overdue = Number(props.stats?.overdue_procurement || 0)

  if (!awaiting) {
    return 'Nenhuma necessidade aprovada espera pedido de compra. As requisições seguem submissão, aprovação e conversão em pedido.'
  }

  return `${awaiting} ${awaiting === 1 ? 'necessidade aprovada espera' : 'necessidades aprovadas esperam'} pedido de compra${overdue ? `, ${overdue} com o prazo ultrapassado` : ''}.`
})

const hasFilters = computed(() => Boolean(localFilters.search || localFilters.status || localFilters.department_id))

const applyFilters = () => {
  router.get(route('vap-inventory.needs.index'), localFilters, { preserveState: true, preserveScroll: true })
}

const applyStatus = (status) => {
  localFilters.status = status
  applyFilters()
}

const resetFilters = () => {
  localFilters.search = ''
  localFilters.status = ''
  localFilters.department_id = ''
  applyFilters()
}

const formatStatus = (status) => ({
  draft: 'Rascunho',
  submitted: 'Por aprovar',
  approved: 'Aprovada',
  rejected: 'Rejeitada',
  ordered: 'Em pedido',
  partially_fulfilled: 'Parcialmente satisfeita',
  fulfilled: 'Satisfeita',
}[status] ?? status)

const statusTone = (status) => ({
  submitted: 'wait',
  approved: 'ok',
  rejected: 'bad',
  ordered: 'run',
  partially_fulfilled: 'run',
  fulfilled: 'done',
}[status] ?? 'neutral')

const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'

const queueUrgencyLabel = (need) => {
  if (!need?.needed_by_date) {
    return 'Sem prazo'
  }

  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const neededDate = new Date(need.needed_by_date)
  neededDate.setHours(0, 0, 0, 0)
  const diffDays = Math.round((neededDate.getTime() - today.getTime()) / 86400000)

  if (diffDays < 0) {
    return 'Em atraso'
  }

  if (diffDays <= 3) {
    return 'Urgente'
  }

  if (diffDays <= 10) {
    return 'Próximo'
  }

  return 'Planeado'
}

const urgencyTone = (need) => ({
  'Em atraso': 'bad',
  Urgente: 'wait',
  Próximo: 'run',
}[queueUrgencyLabel(need)] ?? 'neutral')

const readinessLabel = (value) => ({
  ready: 'Pronta para compra',
  attention: 'Exige acompanhamento',
  incomplete: 'Dados de fornecedor incompletos',
  blocked: 'Bloqueada por fornecedor',
}[value] ?? 'Sem avaliação')

const readinessTone = (value) => ({
  ready: 'ok',
  attention: 'wait',
  incomplete: 'wait',
  blocked: 'bad',
}[value] ?? 'neutral')

const supplierSummaryText = (need) => {
  const summary = need.supplier_summary || {}

  return [
    summary.blocked_supplier_count ? `${summary.blocked_supplier_count} fornecedor(es) bloqueado(s)` : null,
    summary.missing_supplier_count ? `${summary.missing_supplier_count} item(ns) sem fornecedor` : null,
    summary.unassessed_supplier_count ? `${summary.unassessed_supplier_count} sem avaliação` : null,
    summary.conditional_supplier_count ? `${summary.conditional_supplier_count} em acompanhamento reforçado` : null,
  ].filter(Boolean).join(' · ')
}
</script>
