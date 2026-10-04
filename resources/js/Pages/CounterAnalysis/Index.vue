<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ExternalLink as ArrowTopRightOnSquareIcon,
  Link as LinkIcon,
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
  entrypoint: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});

const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const isSubmitting = ref(false);
const rows = computed(() => props.record?.data ?? []);
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
  if (isSubmitting.value) return;
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  if (isSubmitting.value) return;
  const recordIds = rows.value.filter((row) => row.selected).map((row) => row.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeConfirmation();
    return;
  }

  isSubmitting.value = true;
  router.post(route(`counteranalysis.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      isSubmitting.value = false;
      closeConfirmation();
    },
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Contra-análises" lede="Repetições e confirmações ligadas ao resultado original, amostra, parâmetro, incerteza e decisão técnica.">
      <template #actions>
        <Link :href="entrypoint.analysis_url || route('analysis.index', { category: 'insert' })" class="ds-button ds-button-primary">
          <ArrowTopRightOnSquareIcon class="h-4 w-4" /> Solicitar nos resultados
        </Link>
      </template>
    </PageHeader>

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
      :action-methods="{ delete: 'post', restore: 'post' }"
      :action-processing="isSubmitting"
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
