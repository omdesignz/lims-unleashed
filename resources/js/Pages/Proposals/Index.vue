<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue'
import VapTable from '@/Components/vap-table/table.vue'
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { Trash2 as TrashIcon, Pencil as PencilIcon, Repeat as ArrowPathRoundedSquareIcon, Eye as EyeIcon } from '@lucide/vue'
import { usePermission } from '@/Composables/usePermissions'
import { useRecordArchive } from '@/Composables/useRecordArchive'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const { hasPermission } = usePermission()
const props = defineProps({
  record: Object,
  fields: Array,
  model: String,
  abilities: Array,
  query: Object,
  trashedFilter: Boolean,
  trashedOptions: { type: Object, default: () => ({}) },
  initialFilters: { type: Object, default: () => ({}) },
  initialSortField: { type: String, default: '' },
  initialSortDirection: { type: String, default: 'asc' },
  initialIncludes: { type: Array, default: () => [] },
  initialGlobalFilter: { type: String, default: '' },
})
defineOptions({ layout: Layout })

const columns = computed(() => props.fields.map(field => ({
  ...field, field: field.value, label: field.name, visible: true,
  options: field.options || [], config: field.config || {},
})))
const selectedIDs = ref([])
const pendingIDs = ref([])
const action = ref(null)
const showDeleteConfirmation = ref(false)
const tableRevision = ref(0)
const archive = useRecordArchive({
  destroyUrl: () => route('proposals.destroy'),
  restoreUrl: () => route('proposals.restore'),
  onSuccess: () => {
    selectedIDs.value = []
    pendingIDs.value = []
    action.value = null
    tableRevision.value++
  },
})
const actions = computed(() => [
  { id: null, label: 'gestlab.actions.bulk_actions_text' },
  ...(hasPermission('delete_proposals') ? [{ id: 'delete', label: 'gestlab.actions.delete' }] : []),
  ...(hasPermission('restore_proposals') ? [{ id: 'restore', label: 'gestlab.actions.restore' }] : []),
])
const filters = [
  { id: null, label: trans('gestlab.filter.none') },
  { id: 'trashed', label: trans('gestlab.filter.excluded') },
]
const confirmationDialogTitle = computed(() => trans('gestlab.actions.confirmation_dialog_title.' + action.value))
const confirmationDialogDescription = computed(() => trans('gestlab.actions.confirmation_dialog_description.' + action.value))

function requestArchive(operation, ids) {
  if (archive.processing.value || !['delete', 'restore'].includes(operation) || !ids?.length) return
  action.value = operation
  pendingIDs.value = [...ids]
  showDeleteConfirmation.value = true
}

function confirmAction() {
  if (archive.processing.value) return
  showDeleteConfirmation.value = false
  archive.submit(action.value, pendingIDs.value)
}
</script>

<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <div class="border-b border-gray-200 pb-5">
      <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.page_title') }}</h3>
    </div>
    <ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
    <vap-table
      :key="tableRevision" :model="props.model" :abilities="props.abilities" :data="props.record.data"
      :columns="columns" :create-action="true" :query="props.query" :filters="filters"
      :initialFilters="props.initialFilters" :initialSortField="props.initialSortField"
      :initialSortDirection="props.initialSortDirection" :initialIncludes="props.initialIncludes"
      :trashedFilter="props.trashedFilter" :trashedOptions="props.trashedOptions"
      :pagination="props.record.meta" :actions="actions" :action-processing="archive.processing.value"
      @create-record="router.get(route('proposals.create'))" @update-selected-ids="selectedIDs = $event"
      @execute-bulk-action="requestArchive($event.action, selectedIDs)"
    >
      <template #column-name="{ row }"><strong class="text-blue-900">{{ row.name }}</strong></template>
      <template #column-actions="{ row }">
        <button v-if="row.deleted && hasPermission('restore_proposals')" type="button" class="ds-table-action"
          :disabled="archive.processing.value" aria-label="Restaurar proposta" @click="requestArchive('restore', [row.id])">
          <ArrowPathRoundedSquareIcon class="h-4 w-4" />
        </button>
        <button v-if="!row.deleted && hasPermission('edit_proposals')" type="button" class="ds-table-action"
          :disabled="archive.processing.value" aria-label="Editar proposta" @click="router.get(row.links.edit_path)">
          <PencilIcon class="h-4 w-4" />
        </button>
        <button v-if="!row.deleted && hasPermission('view_proposals')" type="button" class="ds-table-action"
          :disabled="archive.processing.value" aria-label="Ver proposta" @click="router.get(row.links.show_path)">
          <EyeIcon class="h-4 w-4" />
        </button>
        <button v-if="row.can_archive && hasPermission('delete_proposals')" type="button" class="ds-table-action ds-table-action-danger"
          :disabled="archive.processing.value" aria-label="Arquivar proposta" @click="requestArchive('delete', [row.id])">
          <TrashIcon class="h-4 w-4" />
        </button>
      </template>
    </vap-table>
    <confirm-dialog v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription"
      confirm="Sim" cancel="Não" @canceled="showDeleteConfirmation = false" @confirmed="confirmAction" />
  </div>
</template>
