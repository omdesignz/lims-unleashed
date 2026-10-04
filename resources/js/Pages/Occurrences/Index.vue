<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import OccurrenceImportForm from "@/Pages/Occurrences/occurrences-import-form.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Eye as EyeIcon,
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
  slideOverEdit: { type: Boolean, default: false },
});

const { hasPermission } = usePermission();
const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const isSubmitting = ref(false);

const pageRecords = computed(() => props.record?.data || []);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const confirmationDialogTitle = computed(() =>
  trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`),
);
const confirmationDialogDescription = computed(() =>
  trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`),
);

function requestBulkAction(action) {
  if (isSubmitting.value) return;
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  if (isSubmitting.value) return;
  const recordIds = pageRecords.value
    .filter((occurrence) => occurrence.selected)
    .map((occurrence) => occurrence.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  isSubmitting.value = true;
  router.post(route(`occurrences.${selectedAction.value === 'delete' ? 'destroy' : 'restore'}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      isSubmitting.value = false;
      closeActionConfirmation();
    },
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.occurrences.page_title')" lede="Registo, triagem e acompanhamento de desvios, reclamações e não conformidades.">
      <template #actions>
        <Link
          v-if="hasPermission('add_occurrences')"
          :href="route('occurrences.create')"
          class="ds-button ds-button-primary whitespace-nowrap"
        >
          <PlusIcon class="h-4 w-4" />
          Nova ocorrência
        </Link>
      </template>
    </PageHeader>

    <OccurrenceImportForm v-if="hasPermission('add_occurrences')" />

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="false"
      :query="query"
      :actions="actions"
      :create-action="false"
      :filter-options="[{ id: null, label: 'gestlab.filter.filter' }, { id: 'trashed', label: 'gestlab.filter.excluded' }]"
      :action-methods="{ delete: 'post', restore: 'post' }"
      :action-processing="isSubmitting"
      @execute-action="requestBulkAction"
    >
      <template #actions="{ id, data }">
        <Link
          v-if="!data?.deleted"
          :href="route('occurrences.show', { occurrence: id })"
          class="grid h-8 w-8 place-items-center rounded-md text-[var(--ds-text-soft)] transition-colors hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]"
          :class="data?.implementation_date_overdue && !data?.date_closed ? 'text-amber-700 dark:text-amber-300' : ''"
          :title="data?.implementation_date_overdue && !data?.date_closed ? 'Abrir ocorrência com prazo excedido' : 'Abrir dossier da ocorrência'"
          :aria-label="data?.implementation_date_overdue && !data?.date_closed ? 'Abrir ocorrência com prazo excedido' : 'Abrir dossier da ocorrência'"
        >
          <EyeIcon class="h-4 w-4" />
        </Link>
      </template>
    </RecordsTable>

    <p v-if="isSubmitting" role="status" class="ds-copy text-sm">A actualizar ocorrências…</p>

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
