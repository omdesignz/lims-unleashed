<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  Archive as ArchiveBoxIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  FileDown as DocumentArrowDownIcon,
  Plus as PlusIcon,
  Truck as TruckIcon,
} from "@lucide/vue";
import { Link, router } from "@inertiajs/vue3";
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
  { label: "Guias", value: totalRecords.value, detail: "registos controlados", icon: TruckIcon },
  { label: "Com cliente", value: rows.value.filter((guide) => guide.customer).length, detail: "destino identificado", icon: ClipboardDocumentCheckIcon },
  { label: "Arquivadas", value: rows.value.filter((guide) => guide.deleted).length, detail: "fora do circuito activo", icon: ArchiveBoxIcon },
]);
const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];
const confirmationDialogTitle = computed(() => trans("gestlab.actions.confirmation_dialog_title." + selectedAction.value));
const confirmationDialogDescription = computed(() => trans("gestlab.actions.confirmation_dialog_description." + selectedAction.value));

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  const recordIds = rows.value.filter((guide) => guide.selected).map((guide) => guide.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.get(route("contractguides." + selectedAction.value), { recordIds }, {
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
            <DocumentArrowDownIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Controlo de circulação</p>
            <h1 class="ds-heading mt-1 text-2xl">Guias de contrato</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Rastreabilidade de produtos, destino e referências documentais de transporte.</p>
          </div>
        </div>
        <Link v-if="hasPermission('add_contract_guides')" :href="route('contractguides.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Nova guia
        </Link>
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
