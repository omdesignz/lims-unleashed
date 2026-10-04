<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import ArchiveMutationFeedback from "@/Components/archive-mutation-feedback.vue";
import { useRecordArchive } from "@/Composables/useRecordArchive";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import { Link, router } from "@inertiajs/vue3";
import {
  Repeat as ArrowPathRoundedSquareIcon,
  FileCheck as DocumentCheckIcon,
  Eye as EyeIcon,
  Globe as GlobeAltIcon,
  ShieldCheck as ShieldCheckIcon,
} from "@lucide/vue";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

const props = defineProps({
  kind: { type: String, required: true, validator: (value) => ["import", "export"].includes(value) },
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});

const { hasPermission } = usePermission();

const config = computed(() => props.kind === "import"
  ? {
      routePrefix: "importcertificates",
      routeParameter: "importcertificate",
      translationPrefix: "gestlab.general.labels.import_certificates",
      permissionKey: "import_certificates",
      icon: ShieldCheckIcon,
      kicker: "Controlo de entrada transfronteiriça",
    }
  : {
      routePrefix: "exportcertificates",
      routeParameter: "exportcertificate",
      translationPrefix: "gestlab.general.labels.export_certificates",
      permissionKey: "export_certificates",
      icon: GlobeAltIcon,
      kicker: "Controlo de saída transfronteiriça",
    });

const selectedAction = ref(null);
const pendingIDs = ref([]);
const showBulkConfirmation = ref(false);
const archive = useRecordArchive({
  destroyUrl: () => route(`${config.value.routePrefix}.destroy`),
  restoreUrl: () => route(`${config.value.routePrefix}.restore`),
  onSuccess: () => {
    pendingIDs.value = [];
    selectedAction.value = null;
    pageRecords.value.forEach(record => { record.selected = false; });
  },
});
const pageRecords = computed(() => props.record?.data || []);
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const selectedRecordIds = computed(() => pageRecords.value.filter((record) => record.selected).map((record) => record.id));
const archivedRecords = computed(() => pageRecords.value.filter((record) => record.deleted).length);
const activeRecords = computed(() => pageRecords.value.length - archivedRecords.value);
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

const actions = computed(() => [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  ...(hasPermission(`delete_${config.value.permissionKey}`) ? [{ id: "delete", label: "gestlab.actions.delete" }] : []),
  ...(hasPermission(`restore_${config.value.permissionKey}`) ? [{ id: "restore", label: "gestlab.actions.restore" }] : []),
]);

function createRecord() {
  router.get(route(`${config.value.routePrefix}.create`));
}

function prepareBulkAction(action) {
  if (archive.processing.value || !['delete', 'restore'].includes(action) || !selectedRecordIds.value.length
    || !hasPermission(`${action === 'delete' ? 'delete' : 'restore'}_${config.value.permissionKey}`)) return;
  pendingIDs.value = [...selectedRecordIds.value];
  selectedAction.value = action;
  showBulkConfirmation.value = true;
}

function resetBulkAction() {
  showBulkConfirmation.value = false;
  selectedAction.value = null;
}

function executeBulkAction() {
  if (archive.processing.value) return;
  showBulkConfirmation.value = false;
  archiveRecord(selectedAction.value, pendingIDs.value);
}

function archiveRecord(operation, ids) {
  if (!hasPermission(`${operation === 'delete' ? 'delete' : 'restore'}_${config.value.permissionKey}`)) return;
  archive.submit(operation, ids);
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="trans(`${config.translationPrefix}.page_title`)" :lede="trans(`${config.translationPrefix}.overview_description`)">
      <template #actions>
        <button v-if="hasPermission(`add_${config.permissionKey}`)" type="button" class="ds-button ds-button-primary" @click="createRecord">
          <DocumentCheckIcon class="h-4 w-4" />
          Novo certificado
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Registos</dt>
        <dd class="pl-cell-value">{{ totalRecords }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Activos nesta página</dt>
        <dd class="pl-cell-value">{{ activeRecords }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Seleccionados</dt>
        <dd class="pl-cell-value">{{ selectedRecordIds.length }}</dd>
      </div>
    </dl>

    <ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
    <RecordsTable
      :action-processing="archive.processing.value"
      :archive-handler="archiveRecord"
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      @execute-action="prepareBulkAction"
      @create-record="createRecord"
    >
      <template #actions="{ data }">
        <div class="flex items-center justify-end gap-2">
          <Link
            v-if="!data.deleted"
            :href="route(`${config.routePrefix}.show`, { [config.routeParameter]: data.id })"
            class="ds-icon-button"
            :title="trans('gestlab.general.buttons.show')"
          >
            <EyeIcon class="h-4 w-4" />
          </Link>
          <span v-if="data.deleted" class="ds-badge ds-badge-warning">
            <ArrowPathRoundedSquareIcon class="h-3.5 w-3.5" />
            Arquivado
          </span>
          <span v-else class="ds-badge ds-badge-info">
            <DocumentCheckIcon class="h-3.5 w-3.5" />
            Certificado controlado
          </span>
        </div>
      </template>
    </RecordsTable>

    <ConfirmDialog
      v-if="showBulkConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      :confirm="trans('gestlab.general.buttons.yes')"
      :cancel="trans('gestlab.general.buttons.no')"
      @canceled="resetBulkAction"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
