<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Etiquetas" :lede="$t('gestlab.general.labels.vap_labels.index.filters_title')">
      <template #actions>
        <Link :href="route('vap_labels.label-templates.index')" class="ds-button ds-button-secondary">
          <DocumentDuplicateIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
        </Link>
        <Link :href="route('vap_labels.labels.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.vap_labels.create_label') }}
        </Link>
      </template>
    </PageHeader>

    <section class="pl-panel">
      <header class="pl-panel-head flex-wrap gap-3">
        <div class="relative min-w-[14rem] flex-1">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--pl-faint)]" aria-hidden="true" />
          <BaseInput v-model="filters.search" type="search" class="ds-field pl-9" :placeholder="$t('gestlab.general.labels.vap_labels.search_placeholder')" :aria-label="$t('gestlab.general.labels.vap_labels.search_placeholder')" />
        </div>
        <div class="pl-segmented" role="radiogroup" aria-label="Tipo de etiqueta">
          <button v-for="option in typeOptions" :key="option.value" type="button" role="radio" :aria-checked="filters.type === option.value" @click="filters.type = option.value">
            {{ option.label }}<span v-if="option.count !== null" class="ml-1.5 text-[var(--pl-faint)]">{{ option.count }}</span>
          </button>
        </div>
        <div class="pl-segmented" role="radiogroup" aria-label="Estado">
          <button v-for="option in statusOptions" :key="option.value" type="button" role="radio" :aria-checked="filters.status === option.value" @click="filters.status = option.value">
            {{ option.label }}
          </button>
        </div>
      </header>

      <ul v-if="labelRows.length" class="grid sm:grid-cols-2 xl:grid-cols-3" :aria-label="resultSummary">
        <li v-for="label in labelRows" :key="label.id" class="flex flex-col border-b border-r border-[var(--pl-line)]">
          <Link :href="route('vap_labels.labels.show', label.id)" class="label-stage h-44 p-4" :aria-label="label.name">
            <LabelPreview :label="label" :values="exampleValues" :scale="thumbnailScale(label)" :title="label.name" />
          </Link>
          <div class="flex flex-1 flex-col gap-3 border-t border-[var(--pl-line)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <Link :href="route('vap_labels.labels.show', label.id)" class="block truncate text-sm font-bold text-[var(--pl-fg)] hover:text-[var(--pl-accent-text)]">{{ label.name }}</Link>
                <p class="mt-1 text-xs text-[var(--pl-muted)]">
                  {{ $t(`gestlab.general.labels.vap_labels.types.${label.type}`) }} · {{ Number(label.width) }} × {{ Number(label.height) }} mm
                  <template v-if="label.has_qr_code"> · QR</template>
                  <template v-if="label.has_barcode"> · Código de barras</template>
                </p>
              </div>
              <StatusChip :tone="label.is_active ? 'ok' : 'neutral'">{{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}</StatusChip>
            </div>
            <div class="mt-auto flex flex-wrap gap-1">
              <Link :href="route('vap_labels.labels.edit', label.id)" class="ds-table-action" :title="$t('gestlab.general.labels.vap_labels.buttons.edit')">
                <PencilIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ $t('gestlab.general.labels.vap_labels.buttons.edit') }}</span>
              </Link>
              <button type="button" class="ds-table-action" title="Duplicar" @click="duplicateLabel(label)">
                <CopyIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Duplicar</span>
              </button>
              <button type="button" class="ds-table-action" :title="label.is_active ? 'Desactivar' : 'Activar'" @click="toggleStatus(label)">
                <PowerIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ label.is_active ? 'Desactivar' : 'Activar' }}</span>
              </button>
              <button type="button" class="ds-table-action ds-table-action-danger ml-auto" :title="$t('gestlab.general.labels.vap_labels.delete_label')" @click="confirmDelete(label)">
                <TrashIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ $t('gestlab.general.labels.vap_labels.delete_label') }}</span>
              </button>
            </div>
          </div>
        </li>
      </ul>

      <div v-else class="px-5 py-14 text-center">
        <p class="text-sm font-bold text-[var(--pl-fg)]">{{ hasActiveFilters ? 'Nenhuma etiqueta corresponde aos filtros.' : 'Ainda não há etiquetas.' }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-[var(--pl-muted)]">
          {{ hasActiveFilters ? 'Altere a pesquisa ou os filtros.' : 'Comece por um modelo aprovado ou crie uma etiqueta à medida do seu rolo de impressão.' }}
        </p>
        <button v-if="hasActiveFilters" type="button" class="ds-button ds-button-secondary mt-4" @click="clearFilters">Limpar filtros</button>
      </div>

      <footer v-if="paginationLinks.length > 3" class="border-t border-[var(--pl-line)] px-4 py-3">
        <Pagination :links="paginationLinks" />
      </footer>
    </section>

    <confirm-dialog
      v-if="showDeleteConfirmation"
      :title="$t('gestlab.general.labels.vap_labels.delete_label')"
      :description="$t('gestlab.general.labels.vap_labels.confirm_delete_label_irreversible')"
      :cancel="$t('gestlab.general.buttons.cancel')"
      :confirm="$t('gestlab.general.labels.vap_labels.buttons.delete_label')"
      variant="danger"
      @confirmed="deleteLabel"
      @canceled="resetDeleteConfirmation"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { debounce } from 'lodash'
import {
  Copy as CopyIcon,
  Files as DocumentDuplicateIcon,
  Search as MagnifyingGlassIcon,
  Pencil as PencilIcon,
  Plus as PlusIcon,
  Power as PowerIcon,
  Trash2 as TrashIcon,
} from '@lucide/vue'
import BaseInput from '@/Components/base/BaseInput.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import LabelPreview from '@/Components/labels/LabelPreview.vue'
import { labelExampleValues } from '@/Support/label-codes.mjs'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

const props = defineProps({
  labels: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  labs: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
})

const filters = ref({
  search: props.filters?.search ?? '',
  type: props.filters?.type ?? '',
  status: props.filters?.status ?? '',
})

const showDeleteConfirmation = ref(false)
const labelPendingDelete = ref(null)

const labelRows = computed(() => Array.isArray(props.labels?.data) ? props.labels.data : [])
const paginationLinks = computed(() => Array.isArray(props.labels?.links) ? props.labels.links : [])
const totalRecords = computed(() => Number(props.labels?.total ?? labelRows.value.length))
const hasActiveFilters = computed(() => Object.values(filters.value).some((value) => String(value ?? '').trim() !== ''))
const resultSummary = computed(() => trans('gestlab.general.labels.vap_labels.index.result_summary', { count: totalRecords.value }))

const typeCounts = computed(() => Object.fromEntries((props.stats?.by_type ?? []).map((row) => [row.type, Number(row.count)])))
const typeOptions = computed(() => [
  { value: '', label: trans('gestlab.general.labels.vap_labels.all_types'), count: Number(props.stats?.total ?? 0) },
  ...['sample', 'equipment', 'material', 'custom'].map((type) => ({
    value: type,
    label: trans(`gestlab.general.labels.vap_labels.types.${type}`),
    count: typeCounts.value[type] ?? 0,
  })),
])
const statusOptions = [
  { value: '', label: 'Todas' },
  { value: 'active', label: 'Activas' },
  { value: 'inactive', label: 'Inactivas' },
]

// Each card shows the label itself, fitted to the card, with example values.
const exampleValues = labelExampleValues()

const thumbnailScale = (label) => Math.max(0.5, Math.min(240 / (Number(label.width) || 50), 130 / (Number(label.height) || 25), 4))

const confirmDelete = (label) => {
  labelPendingDelete.value = label
  showDeleteConfirmation.value = true
}

const resetDeleteConfirmation = () => {
  showDeleteConfirmation.value = false
  labelPendingDelete.value = null
}

const deleteLabel = () => {
  if (!labelPendingDelete.value?.id) {
    resetDeleteConfirmation()
    return
  }

  router.delete(route('vap_labels.labels.destroy', labelPendingDelete.value.id), {
    preserveScroll: true,
    onFinish: resetDeleteConfirmation,
  })
}

const duplicateLabel = (label) => {
  router.post(route('vap_labels.duplicate', label.id), {}, { preserveScroll: true })
}

const toggleStatus = (label) => {
  router.post(route('vap_labels.toggle-status', label.id), {}, { preserveScroll: true })
}

const applyFilters = debounce(() => {
  router.get(route('vap_labels.labels.index'), filters.value, { preserveState: true, preserveScroll: true, replace: true })
}, 300)

const clearFilters = () => {
  filters.value = { search: '', type: '', status: '' }
}

watch(filters, applyFilters, { deep: true })
</script>
