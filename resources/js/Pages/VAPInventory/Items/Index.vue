<template>
  <div class="pl-page" data-template="queue">
    <PageHeader :crumbs="crumbs" :title="listTitle" :lede="lede">
      <template #actions>
        <button
          v-if="canExport && localFilters.archive_state !== 'archived'"
          type="button"
          class="ds-button ds-button-secondary"
          @click="exportItems"
        >
          <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
          Exportar XLSX
        </button>
        <Link v-if="canCreate" :href="createItemUrl" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" aria-hidden="true" />
          Adicionar item
        </Link>
      </template>
    </PageHeader>

    <form class="pl-filter" role="search" @submit.prevent="applyFilters">
      <label for="inventory-item-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput
        id="inventory-item-search"
        v-model="localFilters.search"
        type="search"
        data-bare
        class="pl-filter-input"
        maxlength="100"
        placeholder="nome, código, código de barras, série, marca ou modelo"
      />
      <button v-if="hasActiveFilters" class="ds-chip" type="button" @click="clearFilters">
        Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </form>

    <div class="pl-filter" role="group" aria-label="Filtrar catálogo">
      <label class="flex min-w-[12rem] flex-1 items-center gap-3">
        <span class="pl-k pl-muted">Categoria</span>
        <span class="block min-w-0 flex-1">
          <comboboxEnhanced
            v-model="selectedCategory"
            :hasError="false"
            :options="categories.map((category) => ({ value: category.id, label: category.name }))"
            placeholder="Todas"
          />
        </span>
      </label>
      <label class="flex min-w-[12rem] flex-1 items-center gap-3">
        <span class="pl-k pl-muted">Tipo</span>
        <span class="block min-w-0 flex-1">
          <comboboxEnhanced
            v-model="selectedType"
            :hasError="false"
            :options="types.map((type) => ({ value: type.id, label: type.name }))"
            placeholder="Todos"
          />
        </span>
      </label>
      <label class="flex min-w-[12rem] flex-1 items-center gap-3">
        <span class="pl-k pl-muted">Estado</span>
        <span class="block min-w-0 flex-1">
          <comboboxEnhanced
            v-model="selectedStatus"
            :hasError="false"
            :options="statuses.map((status) => ({ value: status.id, label: status.name }))"
            placeholder="Todos"
          />
        </span>
      </label>
      <label class="flex items-center gap-3">
        <span class="pl-k pl-muted">Arquivo</span>
        <select v-model="localFilters.archive_state" class="ds-field" :disabled="archive.processing.value">
          <option value="active">Itens activos</option>
          <option value="archived">Itens arquivados</option>
        </select>
      </label>
    </div>

    <section class="pl-panel" :aria-label="listTitle">
      <div class="pl-panel-head">
        <h2 class="pl-k">{{ localFilters.archive_state === 'archived' ? 'Itens arquivados' : 'Itens registados' }}</h2>
        <span class="pl-k pl-faint">{{ items.total || 0 }} {{ (items.total || 0) === 1 ? 'item' : 'itens' }}</span>
      </div>

      <DataTable v-if="itemRows.length">
        <thead>
          <tr>
            <th scope="col">Item</th>
            <th scope="col">Categoria</th>
            <th scope="col" class="text-right">Existências</th>
            <th scope="col">Estado</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in itemRows" :key="item.id">
            <td>
              <Link v-if="!item.is_archived" :href="route('vap-inventory.items.show', item.id)" class="font-medium hover:text-[var(--pl-accent-text)]">{{ item.name }}</Link>
              <span v-else class="font-medium">{{ item.name }}</span>
              <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">
                {{ item.code || 'Sem código' }}<template v-if="item.internal_code"> · {{ item.internal_code }}</template><template v-if="item.barcode"> · {{ item.barcode }}</template>
              </span>
            </td>
            <td>
              {{ item.category?.name || '—' }}
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.type?.name || 'Sem tipo' }}</span>
            </td>
            <td class="text-right">
              <span class="pl-num font-medium">{{ item.inventory_sum_qty_available || 0 }}</span>
              <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">Reposição {{ item.reorder_qty > 0 ? item.reorder_qty : '—' }}</span>
            </td>
            <td>
              <div class="flex max-w-sm flex-wrap gap-1.5">
                <StatusChip v-if="item.status" :tone="statusTone(item.status)">{{ item.status.name }}</StatusChip>
                <StatusChip v-if="item.is_reagent && item.is_expired" tone="bad">Vencido</StatusChip>
                <StatusChip v-else-if="item.is_reagent && item.days_to_expiry <= 30" tone="wait">Vence em {{ item.days_to_expiry }} dias</StatusChip>
                <StatusChip v-if="item.metrology_status && item.metrology_status !== 'not_required'" :tone="metrologyTone(item.metrology_status)">
                  {{ getMetrologyText(item.metrology_status) }}
                </StatusChip>
              </div>
            </td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link v-if="item.can_edit" :href="route('vap-inventory.items.edit', item.id)" class="ds-table-action" :aria-label="`Modificar ${item.name}`">
                  <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
                <button
                  v-if="item.can_delete"
                  type="button"
                  class="ds-table-action ds-table-action-danger"
                  :disabled="archive.processing.value"
                  @click="confirmDelete(item)"
                >
                  Arquivar
                </button>
                <button v-if="item.can_restore" type="button" class="ds-table-action" :disabled="archive.processing.value" @click="requestArchive(item, 'restore')">
                  Restaurar
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>

      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ localFilters.archive_state === 'archived' ? 'Nenhum item arquivado' : (hasActiveFilters ? 'Nenhum item neste filtro' : 'Ainda não há itens') }}</span>
        <p class="text-sm text-[var(--pl-muted)]">
          {{ localFilters.archive_state === 'archived' ? 'Ajuste os filtros ou consulte os itens activos. Os registos arquivados permanecem preservados.' : 'Ajuste os filtros ou adicione o primeiro item com existências, validade e rastreabilidade.' }}
        </p>
        <Link v-if="canCreate && localFilters.archive_state !== 'archived'" :href="createItemUrl" class="ds-button ds-button-primary mt-2">Adicionar item</Link>
      </div>

      <Pagination
        v-if="itemRows.length"
        :links="items.links"
        :from="items.from"
        :to="items.to"
        :total="items.total"
        :current_page="items.current_page"
        :last_page="items.last_page"
      />
    </section>

    <ConfirmationModal
      v-if="showDeleteModal"
      :title="pendingOperation === 'restore' ? 'Restaurar item' : 'Arquivar item'"
      :description="`${pendingOperation === 'restore' ? 'Restaurar' : 'Arquivar'} ${itemToDelete?.name}? Os registos, existências e documentos serão preservados.`"
      :confirm="pendingOperation === 'restore' ? 'Restaurar item' : 'Arquivar item'"
      :variant="pendingOperation === 'restore' ? 'info' : 'warning'"
      :disabled="archive.processing.value"
      keep-open-on-confirm
      @canceled="cancelArchive"
      @confirmed="deleteItem"
    >
      <ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="refreshArchive" />
    </ConfirmationModal>
    <ArchiveMutationFeedback v-else :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="refreshArchive" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import {
  Download as ArrowDownTrayIcon,
  SquarePen as PencilSquareIcon,
  Plus as PlusIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import Pagination from '@/Components/pagination.vue'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { useRecordArchive } from '@/Composables/useRecordArchive'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'

/**
 * Inventory catalogue (Plano queue). The navigation opens it per category
 * (1 = equipamentos, 2 = reagentes e consumíveis) or by explicit kind, so the
 * title and path follow the server-side filter. Items are archived, never destroyed.
 */
const props = defineProps({
  canCreate: { type: Boolean, default: false },
  canExport: { type: Boolean, default: false },
  items: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  types: {
    type: Array,
    default: () => [],
  },
  statuses: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    required: true,
  },
})

const localFilters = reactive({
  archive_state: props.filters.archive_state || 'active',
  inventory_type: props.filters.inventory_type || '',
  search: props.filters.search || '',
  category_id: props.filters.category_id || '',
  type_id: props.filters.type_id || '',
  status_id: props.filters.status_id || '',
})

const createItemUrl = computed(() => route('vap-inventory.items.create',
  localFilters.inventory_type ? { inventory_type: localFilters.inventory_type } : {}))

const selectedCategory = ref(null)
const selectedType = ref(null)
const selectedStatus = ref(null)
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const pendingOperation = ref(null)
const archive = useRecordArchive({
  destroyUrl: ids => route('vap-inventory.items.destroy', ids[0]),
  restoreUrl: ids => route('vap-inventory.items.restore', ids[0]),
  onSuccess: () => {
    showDeleteModal.value = false
    itemToDelete.value = null
    pendingOperation.value = null
  },
})

const itemRows = computed(() => props.items?.data ?? [])

/** The navigation's category entries (1 and 2) keep their menu names; other categories use their own. */
const navigationCategoryTitles = { 1: 'Equipamentos', 2: 'Reagentes e consumíveis' }
const inventoryTypeTitles = { equipment: 'Equipamentos', material: 'Materiais e reagentes' }

const scopeTitle = computed(() => {
  const categoryId = Number(props.filters.category_id || 0)

  if (navigationCategoryTitles[categoryId]) {
    return navigationCategoryTitles[categoryId]
  }

  const category = props.categories.find(row => Number(row.id) === categoryId)

  return category?.name || inventoryTypeTitles[props.filters.inventory_type] || ''
})

const listTitle = computed(() => scopeTitle.value || 'Itens de inventário')

const crumbs = computed(() => (scopeTitle.value
  ? [{ title: 'Inventário' }, { title: 'Itens', url: route('vap-inventory.items.index') }, { title: scopeTitle.value }]
  : [{ title: 'Inventário' }, { title: 'Itens' }]))

const plural = (count, singular, pluralForm) => `${count} ${count === 1 ? singular : pluralForm}`

const lede = computed(() => {
  const stats = props.stats ?? {}
  const summary = `${plural(stats.total_items ?? 0, 'item activo', 'itens activos')}: ${stats.equipment_count ?? 0} equipamentos, ${stats.reagents_count ?? 0} reagentes e ${stats.consumables_count ?? 0} consumíveis.`
  const alerts = [
    stats.items_on_metrology_hold ? plural(stats.items_on_metrology_hold, 'em bloqueio metrológico', 'em bloqueio metrológico') : null,
    stats.expired_reagents ? plural(stats.expired_reagents, 'reagente vencido', 'reagentes vencidos') : null,
    stats.items_needing_calibration ? plural(stats.items_needing_calibration, 'calibração nos próximos 30 dias', 'calibrações nos próximos 30 dias') : null,
  ].filter(Boolean)

  return alerts.length ? `${summary} Atenção: ${alerts.join(', ')}.` : `${summary} Sem bloqueios metrológicos nem reagentes vencidos.`
})

watch(selectedCategory, (newValue) => {
  localFilters.category_id = newValue?.value || ''
})

watch(selectedType, (newValue) => {
  localFilters.type_id = newValue?.value || ''
})

watch(selectedStatus, (newValue) => {
  localFilters.status_id = newValue?.value || ''
})

const hasActiveFilters = computed(() => Boolean(localFilters.search || selectedCategory.value || selectedType.value
  || selectedStatus.value || localFilters.archive_state === 'archived'))

const statusTones = {
  Active: 'ok',
  Inactive: 'neutral',
  Maintenance: 'wait',
  'Calibration Due': 'wait',
  Expired: 'bad',
  'Out of Stock': 'bad',
  'Low Stock': 'wait',
}

const statusTone = status => statusTones[status?.name] ?? 'neutral'

const metrologyTone = (status) => {
  if (status === 'hold') {
    return 'bad'
  }

  if (status === 'incomplete' || status === 'review_due') {
    return 'wait'
  }

  return 'ok'
}

const getMetrologyText = (status) => {
  if (status === 'hold') {
    return 'Metrologia bloqueada'
  }

  if (status === 'incomplete') {
    return 'Metrologia incompleta'
  }

  if (status === 'review_due') {
    return 'Revisão metrológica'
  }

  return 'Metrologia validada'
}

function requestArchive(item, operation) {
  if (archive.processing.value || showDeleteModal.value || !['delete', 'restore'].includes(operation)
      || !item?.[operation === 'delete' ? 'can_delete' : 'can_restore']) return
  itemToDelete.value = { id: item.id, name: item.name }
  pendingOperation.value = operation
  showDeleteModal.value = true
}

const confirmDelete = item => requestArchive(item, 'delete')

function deleteItem() {
  if (archive.processing.value || !itemToDelete.value) return
  const current = itemRows.value.find(item => item.id === itemToDelete.value.id)
  if (!current?.[pendingOperation.value === 'delete' ? 'can_delete' : 'can_restore']) {
    archive.failed.value = true
    archive.message.value = 'O registo ou a autorização já não está disponível. Actualize a lista.'
    return
  }
  archive.submit(pendingOperation.value, [itemToDelete.value.id])
}

function cancelArchive() {
  if (archive.processing.value) return
  showDeleteModal.value = false
  itemToDelete.value = null
  pendingOperation.value = null
}

function refreshArchive() {
  if (archive.processing.value) return
  cancelArchive()
  router.reload()
}

const clearFilters = () => {
  localFilters.archive_state = 'active'
  localFilters.search = ''
  localFilters.category_id = ''
  localFilters.type_id = ''
  localFilters.status_id = ''
  selectedCategory.value = null
  selectedType.value = null
  selectedStatus.value = null
}

const applyFilters = debounce(() => {
  router.get(route('vap-inventory.items.index'), { ...localFilters }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 350)

watch(localFilters, applyFilters, { deep: true })

const exportItems = () => {
  const params = new URLSearchParams()

  Object.entries(localFilters).forEach(([key, value]) => {
    if (value) {
      params.set(key, value)
    }
  })

  const query = params.toString()
  const url = route('vap-inventory.items.export.inventory')
  window.location.assign(query ? `${url}?${query}` : url)
}

onMounted(() => {
  if (props.filters.category_id) {
    const category = props.categories.find((item) => item.id == props.filters.category_id)

    if (category) {
      selectedCategory.value = { value: category.id, label: category.name }
    }
  }

  if (props.filters.type_id) {
    const type = props.types.find((item) => item.id == props.filters.type_id)

    if (type) {
      selectedType.value = { value: type.id, label: type.name }
    }
  }

  if (props.filters.status_id) {
    const status = props.statuses.find((item) => item.id == props.filters.status_id)

    if (status) {
      selectedStatus.value = { value: status.id, label: status.name }
    }
  }
})
</script>
