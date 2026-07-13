<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import OccurrenceImportForm from "@/Pages/Occurrences/occurrences-import-form.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowPathIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentMagnifyingGlassIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  PlusIcon,
} from "@heroicons/vue/24/outline";
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

const pageRecords = computed(() => props.record?.data || []);
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const metrics = computed(() => [
  {
    label: "Ocorrências",
    value: totalRecords.value,
    detail: "registos no sistema",
    icon: DocumentMagnifyingGlassIcon,
  },
  {
    label: "Em tratamento",
    value: pageRecords.value.filter((occurrence) => !occurrence.date_resolved && !occurrence.date_closed).length,
    detail: "sem data de conclusão",
    icon: ClockIcon,
  },
  {
    label: "Prazo excedido",
    value: pageRecords.value.filter((occurrence) => occurrence.implementation_date_overdue && !occurrence.date_closed).length,
    detail: "ação requer atenção",
    icon: ExclamationTriangleIcon,
  },
  {
    label: "Encerradas",
    value: pageRecords.value.filter((occurrence) => occurrence.date_closed).length,
    detail: "com fecho registado",
    icon: CheckCircleIcon,
  },
]);

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
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  const recordIds = pageRecords.value
    .filter((occurrence) => occurrence.selected)
    .map((occurrence) => occurrence.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.get(route(`occurrences.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: closeActionConfirmation,
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Qualidade e conformidade</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <DocumentMagnifyingGlassIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">{{ $t("gestlab.general.labels.occurrences.page_title") }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">
                Registo, triagem e acompanhamento de desvios, reclamações e não conformidades.
              </p>
            </div>
          </div>
        </div>

        <Link
          v-if="hasPermission('add_occurrences')"
          :href="route('occurrences.create')"
          class="ds-button ds-button-primary whitespace-nowrap"
        >
          <PlusIcon class="h-4 w-4" />
          Nova ocorrência
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component
              :is="metric.icon"
              class="h-5 w-5 shrink-0"
              :class="metric.label === 'Prazo excedido' && metric.value ? 'text-amber-600 dark:text-amber-300' : 'text-[var(--ds-text-soft)]'"
            />
          </div>
        </div>
      </dl>
    </section>

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
      @execute-action="requestBulkAction"
    >
      <template #actions="{ id, data }">
        <Link
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
