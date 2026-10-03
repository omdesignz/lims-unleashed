<script setup>
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue';
import { useRecordArchive } from '@/Composables/useRecordArchive';
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { usePermission } from '@/Composables/usePermissions';
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleHero from '@/Components/base/ModuleHero.vue'
import DocumentShareModal from '@/Components/documents/DocumentShareModal.vue'
import { FileDown as DocumentArrowDownIcon, Mail as EnvelopeIcon } from '@lucide/vue'


const { hasPermission } = usePermission();
const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

const actionId = ref(null);
const selectedReceipt = ref(null);
const shareOpen = ref(false);

const openShare = (receipt) => {
  selectedReceipt.value = receipt;
  shareOpen.value = true;
}


const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})


const actions = computed(() => [
  { id: null, label: 'gestlab.actions.bulk_actions_text' },
  ...(hasPermission('delete_receipts') ? [{ id: 'delete', label: 'gestlab.actions.delete' }] : []),
  ...(hasPermission('restore_receipts') ? [{ id: 'restore', label: 'gestlab.actions.restore' }] : []),
]);

const handleEdit = () => {
  router.get(route('receipts.create'));
}

const showDeleteConfirmation = ref(false);


const pendingIDs = ref([]);
const archive = useRecordArchive({
  destroyUrl: () => route('receipts.destroy'),
  restoreUrl: () => route('receipts.restore'),
  onSuccess: () => {
    pendingIDs.value = [];
    actionId.value = null;
    props.record.data.forEach(record => { record.selected = false; });
  },
});

function requestArchive(operation) {
  if (archive.processing.value || !['delete', 'restore'].includes(operation)
    || !hasPermission((operation === 'delete' ? 'delete_' : 'restore_') + 'receipts')) return;
  const ids = props.record.data.filter(record => record.selected).map(record => record.id);
  if (!ids.length) return;
  pendingIDs.value = [...ids];
  actionId.value = operation;
  showDeleteConfirmation.value = true;
}

function archiveRecord(operation, ids) {
  if (!hasPermission((operation === 'delete' ? 'delete_' : 'restore_') + 'receipts')) return;
  archive.submit(operation, ids);
}

function confirmAction() {
  if (archive.processing.value) return;
  showDeleteConfirmation.value = false;
  archiveRecord(actionId.value, pendingIDs.value);
}
</script>
<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
<ModuleHero
  :eyebrow="$t('gestlab.general.labels.commercial_documents.treasury_area')"
  :title="$t('gestlab.general.labels.receipts.page_title')"
  :description="$t('gestlab.general.labels.receipts.index_description')"
>
  <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <article class="ds-card bg-[var(--ds-panel-raised)] p-4">
      <p class="ds-kicker text-[0.64rem]">
        {{ $t('gestlab.general.labels.commercial_documents.records') }}
      </p>
      <p class="mt-2 text-2xl font-black tabular-nums text-[var(--ds-text)]">
        {{ props.record?.total ?? props.record?.data?.length ?? 0 }}
      </p>
    </article>
    <article class="ds-card bg-[var(--ds-panel-raised)] p-4">
      <p class="ds-kicker text-[0.64rem]">
        {{ $t('gestlab.general.labels.commercial_documents.flow') }}
      </p>
      <p class="mt-2 text-sm font-black text-[var(--ds-text)]">
        {{ $t('gestlab.general.labels.receipts.index_flow') }}
      </p>
    </article>
  </div>
</ModuleHero>

<ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
<records-table :action-processing="archive.processing.value" :archive-handler="archiveRecord" :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="requestArchive" @create-record="handleEdit">
  <template #actions="slotProps">
    <a :href="slotProps.data.links.pdf_path" target="_blank" rel="noopener" class="ds-table-action" title="Abrir PDF">
      <DocumentArrowDownIcon class="h-5 w-5" />
    </a>
    <button type="button" class="ds-table-action" title="Enviar por email" @click="openShare(slotProps.data)">
      <EnvelopeIcon class="h-5 w-5" />
    </button>
  </template>
</records-table>

<DocumentShareModal
  v-if="selectedReceipt"
  :open="shareOpen"
  document-type="receipt"
  :document-id="selectedReceipt.id"
  document-label="Recibo"
  :document-number="selectedReceipt.rec_no"
  :default-recipients="[
    selectedReceipt.warehouse_id?.invoicing_email,
    selectedReceipt.warehouse_id?.email,
    selectedReceipt.warehouse_id?.focal_point_email,
  ].filter(Boolean)"
  @close="shareOpen = false"
/>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />
</div>
</template>
