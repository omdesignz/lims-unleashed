<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowTopRightOnSquareIcon,
  BeakerIcon,
  ClipboardDocumentCheckIcon,
  ExclamationTriangleIcon,
  LinkIcon,
} from "@heroicons/vue/24/outline";
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
  entrypoint: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});

const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const rows = computed(() => props.record?.data ?? []);
const totalRecords = computed(() => props.record?.meta?.total ?? rows.value.length);
const linkedToEntry = computed(() => rows.value.filter((row) => row.sample_entry || row.entry_origin?.is_sample_entry_first || row.entry_lineage).length);
const requiringAttention = computed(() => rows.value.filter((row) => !["approved", "completed", "concluded"].includes(String(row.status ?? "").toLowerCase())).length);
const metrics = computed(() => [
  { label: "Contra-análises", value: totalRecords.value, detail: "registos técnicos", icon: BeakerIcon },
  { label: "Com linhagem", value: linkedToEntry.value, detail: "nesta página", icon: LinkIcon },
  { label: "Em acompanhamento", value: requiringAttention.value, detail: "decisão pendente", icon: ExclamationTriangleIcon },
]);
const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];
const confirmationTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

function openResults() {
  router.visit(props.entrypoint?.analysis_url || route("analysis.index", { category: "insert" }));
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  const recordIds = rows.value.filter((row) => row.selected).map((row) => row.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeConfirmation();
    return;
  }

  router.get(route(`counteranalysis.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: closeConfirmation,
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <ClipboardDocumentCheckIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Confirmação técnica</p>
            <h1 class="ds-heading mt-1 text-2xl">Contra-análises</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Repetições e confirmações ligadas ao resultado original, amostra, parâmetro, incerteza e decisão técnica.</p>
          </div>
        </div>
        <Link :href="entrypoint.analysis_url || route('analysis.index', { category: 'insert' })" class="ds-button ds-button-primary">
          <ArrowTopRightOnSquareIcon class="h-4 w-4" /> Solicitar nos resultados
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r sm:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <section class="flex gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
      <LinkIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
      <div><p class="text-sm font-semibold text-[var(--ds-text)]">Pedido iniciado no resultado original</p><p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">Este ponto de entrada mantém a cadeia de rastreabilidade e impede contra-análises sem contexto técnico.</p></div>
    </section>

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      @execute-action="requestBulkAction"
      @create-record="openResults"
    />

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationTitle"
      :description="confirmationDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="closeConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
