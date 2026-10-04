<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { CalendarDays as CalendarDaysIcon } from "@lucide/vue";
import { router } from "@inertiajs/vue3";
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

const actionId = ref(null);
const showActionConfirmation = ref(false);
const totalRecords = computed(() => props.record.meta?.total ?? props.record.data.length);
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function openCreateForm() {
  router.get(route("ideliveries.create"));
}

function requestBulkAction(selectedActionId) {
  actionId.value = selectedActionId;
  showActionConfirmation.value = true;
}

function confirmAction() {
  const recordIds = props.record.data.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !actionId.value) {
    showActionConfirmation.value = false;
    return;
  }

  const routeName = actionId.value === "restore" ? "ideliveries.restore" : "ideliveries.destroy";

  router.get(route(routeName), { recordIds }, {
    preserveState: false,
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      actionId.value = null;
    },
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Entregas de inventário" lede="Registo de saídas para clientes com datas, destinatário e linhas de material rastreáveis.">
      <template #actions>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-bold text-[var(--ds-text-muted)]">
          <CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />
          {{ totalRecords }} entregas
        </span>
      </template>
    </PageHeader>

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      @execute-action="requestBulkAction"
      @create-record="openCreateForm"
    />

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Confirmar"
      cancel="Cancelar"
      @canceled="showActionConfirmation = false"
      @close="showActionConfirmation = false"
      @confirmed="confirmAction"
    />
  </div>
</template>
