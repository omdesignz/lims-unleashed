<script setup>
import { computed, reactive, watch } from 'vue'
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
 * Calibration schedule (Plano queue). Equipment with a next calibration date,
 * nearest first; the state cells are laboratory-wide counts that filter the
 * list. Overdue equipment stays out of use until a documented technical review.
 */
const props = defineProps({
  items: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
})

const filters = reactive({
  status: props.filters?.status ?? '',
  type_id: props.filters?.type_id ?? '',
  category_id: props.filters?.category_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: ['next_calibration_date', 'last_calibration_date', 'name'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'next_calibration_date',
  sort_direction: props.filters?.sort_direction === 'desc' ? 'desc' : 'asc',
})

const itemRows = computed(() => props.items?.data || [])
const upcomingCount = computed(() => Math.max(Number(props.stats?.total_scheduled || 0) - Number(props.stats?.total_due || 0) - Number(props.stats?.due_soon || 0), 0))

const cells = computed(() => [
  { key: '', label: 'Todos', value: props.stats?.total_scheduled || 0 },
  { key: 'overdue', label: 'Atrasados', value: props.stats?.total_due || 0, tone: props.stats?.total_due ? 'bad' : undefined },
  { key: 'due_soon', label: 'Até 30 dias', value: props.stats?.due_soon || 0 },
  { key: 'upcoming', label: 'Em dia', value: upcomingCount.value },
])

const lede = computed(() => {
  const overdue = Number(props.stats?.total_due || 0)
  const window = `Entre 31 e 90 dias: ${formatNumber(props.stats?.due_31_90 || 0)}.`

  return overdue
    ? `${formatNumber(overdue)} ${overdue === 1 ? 'equipamento atrasado fica' : 'equipamentos atrasados ficam'} fora de uso até revisão técnica documentada. ${window}`
    : `Nenhum equipamento com calibração atrasada. ${window}`
})

const hasActiveFilters = computed(() => Boolean(filters.status || filters.type_id || filters.category_id || filters.search))

function daysToCalibration(item) {
  if (Number.isFinite(Number(item.days_to_calibration))) return Number(item.days_to_calibration)
  if (!item.next_calibration_date) return null
  const next = new Date(`${String(item.next_calibration_date).slice(0, 10)}T00:00:00Z`)
  const today = new Date()
  const todayUtc = Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate())
  return Math.round((next.getTime() - todayUtc) / 86400000)
}

function statusLabel(item) {
  const days = daysToCalibration(item)
  if (days === null) return 'Sem data'
  if (days < 0) return 'Atrasado'
  if (days <= 30) return 'Prioridade'
  if (days <= 90) return 'Planeado'
  return 'Programado'
}

function statusTone(item) {
  const days = daysToCalibration(item)
  if (days === null || days < 0) return 'bad'
  if (days <= 30) return 'wait'
  return 'ok'
}

function statusInstruction(item) {
  const days = daysToCalibration(item)
  if (days === null) return 'Completar calendário metrológico.'
  if (days < 0) return 'Bloquear uso e documentar decisão técnica.'
  if (days <= 30) return 'Executar calibração ou confirmar data externa.'
  if (days <= 90) return 'Preparar tarefa e disponibilidade do equipamento.'
  return 'Manter monitorização periódica.'
}

function daysLabel(item) {
  const days = daysToCalibration(item)
  if (days === null) return 'Sem data'
  if (days < 0) return `${formatNumber(Math.abs(days))} dias de atraso`
  if (days === 0) return 'Hoje'
  return `${formatNumber(days)} dias`
}

function calibrationUtilization(item) {
  if (!item.last_calibration_date || !item.next_calibration_date) return 0
  const last = new Date(item.last_calibration_date).getTime()
  const next = new Date(item.next_calibration_date).getTime()
  const total = next - last
  if (total <= 0) return 100
  return Math.min(Math.max(((Date.now() - last) / total) * 100, 0), 100)
}

function metrologyLabel(status) {
  if (status === 'hold') return 'Bloqueado'
  if (status === 'incomplete') return 'Incompleto'
  if (status === 'review_due') return 'Revisão em breve'
  if (status === 'validated') return 'Validado'
  return 'Não aplicável'
}

function metrologyTextClass(status) {
  if (status === 'hold' || status === 'incomplete') return 'text-[var(--pl-bad)]'
  if (status === 'review_due') return 'text-[var(--pl-warn)]'
  return 'text-[var(--pl-muted)]'
}

function formatDate(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 1 }).format(Number(value || 0))
}

function clearFilters() {
  Object.assign(filters, {
    status: '',
    type_id: '',
    category_id: '',
    search: '',
    sort_by: 'next_calibration_date',
    sort_direction: 'asc',
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.items.calibration.schedule'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 350),
  { deep: true },
)
</script>

<template>
  <div class="pl-page" data-template="queue">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Calibração' }]"
      title="Agenda de calibração"
      :lede="lede"
    >
      <template #actions>
        <Link :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-secondary">Criar tarefa</Link>
      </template>
    </PageHeader>

    <StateCells v-model="filters.status" class="mb-10" :items="cells" label="Filtrar equipamentos por prazo de calibração" />

    <form class="pl-filter" role="search" @submit.prevent>
      <label for="calibration-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="calibration-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" placeholder="nome, código, série, marca ou modelo" />
      <div class="w-44">
        <BaseSelect v-model="filters.type_id" aria-label="Tipo de equipamento">
          <option value="">Todos os tipos</option>
          <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-44">
        <BaseSelect v-model="filters.category_id" aria-label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-48">
        <BaseSelect v-model="filters.sort_by" aria-label="Ordenar por">
          <option value="next_calibration_date">Próxima calibração</option>
          <option value="last_calibration_date">Última calibração</option>
          <option value="name">Nome</option>
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

    <section class="pl-panel" aria-label="Equipamentos por prazo de calibração">
      <DataTable v-if="itemRows.length">
        <thead>
          <tr>
            <th scope="col">Equipamento</th>
            <th scope="col">Próxima calibração</th>
            <th scope="col">Rastreabilidade</th>
            <th scope="col">Estado</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in itemRows" :key="item.id">
            <td>
              <Link :href="route('vap-inventory.items.show', item.id)" class="font-medium hover:text-[var(--pl-accent-text)]">{{ item.name }}</Link>
              <span class="block pl-num text-[12.5px] text-[var(--pl-muted)]">{{ item.internal_code || item.code || 'Sem código' }}</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.category?.name || 'Sem categoria' }}<template v-if="item.type"> · {{ item.type.name }}</template></span>
              <span v-if="item.model || item.brand" class="block text-[12px] text-[var(--pl-faint)]">{{ [item.brand, item.model].filter(Boolean).join(' · ') }}</span>
            </td>
            <td>
              <span class="pl-num">{{ formatDate(item.next_calibration_date) }}</span>
              <span class="block text-[12.5px]" :class="daysToCalibration(item) < 0 ? 'text-[var(--pl-bad)]' : 'text-[var(--pl-muted)]'">{{ daysLabel(item) }}</span>
              <div class="mt-2 grid min-w-40 gap-1">
                <span class="flex justify-between gap-3 text-[12px] text-[var(--pl-muted)]"><span>Última {{ formatDate(item.last_calibration_date) || 'nunca' }}</span><span class="pl-num">{{ formatNumber(calibrationUtilization(item)) }}%</span></span>
                <span class="pl-bar block" role="img" :aria-label="`Ciclo de calibração decorrido: ${formatNumber(calibrationUtilization(item))}%`"><i :class="{ 'pl-bar-late': daysToCalibration(item) < 0 }" :style="{ width: `${calibrationUtilization(item)}%` }" /></span>
              </div>
            </td>
            <td>
              <span class="pl-num">{{ item.serial_number || 'Sem série' }}</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.location || 'Sem localização' }}</span>
              <span v-if="item.metrological_traceability_reference" class="block max-w-52 text-[12px] text-[var(--pl-faint)]">{{ item.metrological_traceability_reference }}</span>
              <span v-if="item.metrological_uncertainty_value" class="block pl-num text-[12px] text-[var(--pl-faint)]">U={{ item.metrological_uncertainty_value }} {{ item.metrological_uncertainty_unit || '' }}</span>
            </td>
            <td>
              <StatusChip :tone="statusTone(item)">{{ statusLabel(item) }}</StatusChip>
              <span class="block max-w-44 pt-1 text-[12px] text-[var(--pl-muted)]">{{ statusInstruction(item) }}</span>
              <span v-if="item.metrology_status" class="block pt-1 text-[12px]" :class="metrologyTextClass(item.metrology_status)">Aptidão: {{ metrologyLabel(item.metrology_status) }}</span>
            </td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link :href="route('vap-inventory.items.show', item.id)" class="ds-table-action" :aria-label="`Abrir ficha de ${item.name}`">
                  <EyeIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
                <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-table-action" :aria-label="`Rever metrologia de ${item.name}`">
                  <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ hasActiveFilters ? 'Nenhum equipamento neste filtro' : 'Sem equipamentos com calibração agendada' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ hasActiveFilters ? 'Experimente outro prazo, tipo ou categoria.' : 'Registe a próxima calibração na ficha do equipamento para o acompanhar aqui.' }}</p>
      </div>
      <Pagination
        v-if="itemRows.length"
        :links="items.links"
        :total="items.total"
        :from="items.from"
        :to="items.to"
        :last_page="items.last_page"
        :current_page="items.current_page"
      />
    </section>
  </div>
</template>
