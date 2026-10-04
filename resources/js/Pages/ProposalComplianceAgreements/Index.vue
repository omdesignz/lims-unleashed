<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue'
import VapTable from '@/Components/vap-table/table.vue'
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { Repeat as ArrowPathRoundedSquareIcon, Trash2 as TrashIcon } from '@lucide/vue'
import { usePermission } from '@/Composables/usePermissions'
import { useRecordArchive } from '@/Composables/useRecordArchive'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const { hasPermission } = usePermission()
const props = defineProps({
  record: Object, fields: Array, model: String, abilities: Array, query: Object,
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
  ...field, field: field.value, label: field.type === 'actions' ? 'Acções' : field.name, visible: true,
  options: field.options || [], config: field.config || {},
})))
const selectedIDs = ref([])
const pendingIDs = ref([])
const action = ref(null)
const showDeleteConfirmation = ref(false)
const tableRevision = ref(0)
const archive = useRecordArchive({
  destroyUrl: () => route('proposalcomplianceagreements.destroy'),
  restoreUrl: () => route('proposalcomplianceagreements.restore'),
  onSuccess: () => {
    selectedIDs.value = []
    pendingIDs.value = []
    action.value = null
    tableRevision.value++
  },
})
const complianceRecords = computed(() => props.record?.data || [])
const signedCount = computed(() => complianceRecords.value.filter(item => item.acknowledged_at).length)
const completeCount = computed(() => complianceRecords.value.filter(item => item.confidentiality && item.impartiality && item.nondisclosure).length)
const pendingCount = computed(() => Math.max(complianceRecords.value.length - signedCount.value, 0))
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
<div class="pl-page proposal-compliance-page space-y-6" :class="commercialDocumentThemeClasses">
  <PageHeader :title="$t('gestlab.general.labels.proposal_compliance_agreements.page_title')" lede="Monitorize a aceitação formal das propostas, os compromissos de confidencialidade, imparcialidade e não divulgação, mantendo evidência rastreável do consentimento do cliente." />

  <dl class="pl-cells">
    <div class="pl-cell"><dt class="pl-k pl-muted">Registos</dt><dd class="pl-cell-value">{{ complianceRecords.length }}</dd></div>
    <div class="pl-cell"><dt class="pl-k pl-muted">Assinados</dt><dd class="pl-cell-value">{{ signedCount }}</dd></div>
    <div class="pl-cell" :class="{ 'pl-cell-bad': pendingCount > 0 }"><dt class="pl-k pl-muted">Pendentes</dt><dd class="pl-cell-value">{{ pendingCount }}</dd></div>
    <div class="pl-cell"><dt class="pl-k pl-muted">Completos</dt><dd class="pl-cell-value">{{ completeCount }}</dd></div>
  </dl>

<section>
  <ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
  <vap-table
    :key="tableRevision" :model="props.model" :abilities="props.abilities" :data="props.record.data"
    :columns="columns" :create-action="false" :query="props.query" :filters="filters"
    :initialFilters="props.initialFilters" :initialSortField="props.initialSortField"
    :initialSortDirection="props.initialSortDirection" :initialIncludes="props.initialIncludes"
    :trashedFilter="props.trashedFilter" :trashedOptions="props.trashedOptions"
    :pagination="props.record.meta" :actions="actions" :action-processing="archive.processing.value"
    @update-selected-ids="selectedIDs = $event" @execute-bulk-action="requestArchive($event.action, selectedIDs)"
  >
    <template #column-actions="{ row }">
      <button v-if="row.deleted && hasPermission('restore_proposals')" type="button" class="ds-table-action"
        :disabled="archive.processing.value" aria-label="Restaurar acordo" @click="requestArchive('restore', [row.id])">
        <ArrowPathRoundedSquareIcon class="h-4 w-4" />
      </button>
      <button v-if="!row.deleted && hasPermission('delete_proposals')" type="button" class="ds-table-action ds-table-action-danger"
        :disabled="archive.processing.value" aria-label="Arquivar acordo" @click="requestArchive('delete', [row.id])">
        <TrashIcon class="h-4 w-4" />
      </button>
    </template>
  </vap-table>
</section>
<confirm-dialog v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription"
  confirm="Sim" cancel="Não" @canceled="showDeleteConfirmation = false" @confirmed="confirmAction" />
</div>
</template>
<style scoped>
.proposal-compliance-page :deep(.text-blue-900),
.proposal-compliance-page :deep(.hover\:text-blue-900:hover) {
  color: #143d37 !important;
}

.proposal-compliance-page :deep(.bg-blue-900) {
  background-color: #143d37 !important;
}

.proposal-compliance-page :deep(.hover\:bg-blue-800:hover) {
  background-color: #0f302b !important;
}

.proposal-compliance-page :deep(.focus\:ring-blue-900:focus),
.proposal-compliance-page :deep(.focus-visible\:outline-indigo-900:focus-visible) {
  --tw-ring-color: #c79a43 !important;
  outline-color: #c79a43 !important;
}

.proposal-compliance-page :deep(.border-gray-200),
.proposal-compliance-page :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])),
.proposal-compliance-page :deep(.border-gray-100),
.proposal-compliance-page :deep(.divide-gray-100 > :not([hidden]) ~ :not([hidden])) {
  border-color: #ded2bb !important;
}

.proposal-compliance-page :deep(.text-gray-900) {
  color: #10221d !important;
}

.proposal-compliance-page :deep(.text-gray-700),
.proposal-compliance-page :deep(.text-gray-600),
.proposal-compliance-page :deep(.text-ft-gray) {
  color: #59665f !important;
}

.proposal-compliance-page :deep(input:not([type='checkbox'])),
.proposal-compliance-page :deep(select),
.proposal-compliance-page :deep(textarea) {
  background: rgba(255, 255, 255, 0.98);
  color: #18231f;
  border-color: #d8cbb4;
  box-shadow: 0 14px 30px -28px rgba(20, 61, 55, 0.55);
}

.proposal-compliance-page :deep(input[type='checkbox']) {
  border-color: #d8cbb4;
  color: #143d37;
}

:global(.dark) .proposal-compliance-page :deep(.bg-white),
:global(.dark) .proposal-compliance-page :deep(.bg-gray-50) {
  background-color: rgba(15, 23, 42, 0.92) !important;
}

:global(.dark) .proposal-compliance-page :deep(.text-gray-900),
:global(.dark) .proposal-compliance-page :deep(.text-gray-700),
:global(.dark) .proposal-compliance-page :deep(.text-gray-600) {
  color: #f8fafc !important;
}

:global(.dark) .proposal-compliance-page :deep(.border-gray-200),
:global(.dark) .proposal-compliance-page :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])),
:global(.dark) .proposal-compliance-page :deep(.border-gray-100),
:global(.dark) .proposal-compliance-page :deep(.divide-gray-100 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgba(255, 255, 255, 0.12) !important;
}

:global(.dark) .proposal-compliance-page :deep(input:not([type='checkbox'])),
:global(.dark) .proposal-compliance-page :deep(select),
:global(.dark) .proposal-compliance-page :deep(textarea) {
  background: rgba(15, 23, 42, 0.92);
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.12);
}
</style>
