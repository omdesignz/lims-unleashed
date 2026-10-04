<script setup>
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue';
import { useRecordArchive } from '@/Composables/useRecordArchive';
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { usePermission } from '@/Composables/usePermissions';
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Eye as EyeIcon } from "@lucide/vue";
import ModuleHero from '@/Components/base/ModuleHero.vue'


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


const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})


const actions = computed(() => [
  { id: null, label: 'gestlab.actions.bulk_actions_text' },
  ...(hasPermission('delete_quotes') ? [{ id: 'delete', label: 'gestlab.actions.delete' }] : []),
  ...(hasPermission('restore_quotes') ? [{ id: 'restore', label: 'gestlab.actions.restore' }] : []),
]);

const handleEdit = () => {
  router.get(route('quotes.create'));
}

const showDeleteConfirmation = ref(false);


const pendingIDs = ref([]);
const archive = useRecordArchive({
  destroyUrl: () => route('quotes.destroy'),
  restoreUrl: () => route('quotes.restore'),
  onSuccess: () => {
    pendingIDs.value = [];
    actionId.value = null;
    props.record.data.forEach(record => { record.selected = false; });
  },
});

function requestArchive(operation) {
  if (archive.processing.value || !['delete', 'restore'].includes(operation)
    || !hasPermission((operation === 'delete' ? 'delete_' : 'restore_') + 'quotes')) return;
  const ids = props.record.data.filter(record => record.selected).map(record => record.id);
  if (!ids.length) return;
  pendingIDs.value = [...ids];
  actionId.value = operation;
  showDeleteConfirmation.value = true;
}

function archiveRecord(operation, ids) {
  if (!hasPermission((operation === 'delete' ? 'delete_' : 'restore_') + 'quotes')) return;
  archive.submit(operation, ids);
}

function confirmAction() {
  if (archive.processing.value) return;
  showDeleteConfirmation.value = false;
  archiveRecord(actionId.value, pendingIDs.value);
}
</script>
<template>
<div class="pl-page space-y-6" :class="commercialDocumentThemeClasses">
<Head title="Proformas" />
<ModuleHero
  :eyebrow="$t('gestlab.general.labels.commercial_documents.commercial_area')"
  :title="$t('gestlab.general.labels.quotes.page_title')"
  :description="$t('gestlab.general.labels.quotes.index_description')"
/>

<ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
<records-table :action-processing="archive.processing.value" :archive-handler="archiveRecord" :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="requestArchive" @create-record="handleEdit">
  <template #actions="{ id }">
      <Link
                    :href="route('quotes.show', {quote: id})"
                    class="ds-table-action"
                  >
                  <EyeIcon class="h-5 w-5" />
            </Link>
    </template>
</records-table>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />
</div>
</template>
