<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  FolderOpen as FolderOpenIcon,
  Plus as PlusIcon,
} from "@lucide/vue";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const rows = computed(() => props.record?.data ?? []);
const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  const recordIds = rows.value.filter((document) => document.selected).map((document) => document.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.get(route(`archived_documents.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: closeActionConfirmation,
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Documentos arquivados" lede="Evidência histórica preservada para retenção, recuperação e auditoria.">
      <template #actions>
        <Link :href="route('file-manager')" class="ds-button ds-button-secondary">
          <FolderOpenIcon class="h-4 w-4" />
          Gestor documental
        </Link>
        <Link v-if="hasPermission('add_archived_documents')" :href="route('archived_documents.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Novo documento
        </Link>
      </template>
    </PageHeader>

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="false"
      :query="query"
      :actions="actions"
      :create-action="false"
      @execute-action="requestBulkAction"
    />

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="closeActionConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
