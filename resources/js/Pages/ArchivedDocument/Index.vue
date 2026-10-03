<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Archive as ArchiveBoxIcon,
  FileCheck as DocumentCheckIcon,
  File as DocumentIcon,
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
const totalRecords = computed(() => props.record?.meta?.total ?? rows.value.length);
const metrics = computed(() => [
  { label: "Arquivados", value: totalRecords.value, detail: "registos de retenção", icon: ArchiveBoxIcon },
  { label: "Com descrição", value: rows.value.filter((document) => document.description).length, detail: "contexto preservado", icon: DocumentCheckIcon },
  { label: "Com ficheiro", value: rows.value.filter((document) => document.file_path).length, detail: "evidência anexada", icon: DocumentIcon },
]);
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
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <ArchiveBoxIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Controlo documental</p>
            <h1 class="ds-heading mt-1 text-2xl">Documentos arquivados</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Evidência histórica preservada para retenção, recuperação e auditoria.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link :href="route('file-manager')" class="ds-button ds-button-secondary">
            <FolderOpenIcon class="h-4 w-4" />
            Gestor documental
          </Link>
          <Link v-if="hasPermission('add_archived_documents')" :href="route('archived_documents.create')" class="ds-button ds-button-primary">
            <PlusIcon class="h-4 w-4" />
            Novo documento
          </Link>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r sm:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div>
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

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
