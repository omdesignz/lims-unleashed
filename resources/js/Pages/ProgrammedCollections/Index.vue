<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  Archive as ArchiveBoxIcon,
  Download as ArrowDownTrayIcon,
  CalendarDays as CalendarDaysIcon,
  ClipboardList as ClipboardDocumentListIcon,
  Eye as EyeIcon,
  Plus as PlusIcon,
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

const { hasPermission } = usePermission();
const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const isSubmitting = ref(false);
const rows = computed(() => props.record?.data ?? []);
const selectedRecordIds = computed(() => rows.value.filter((row) => row.selected).map((row) => row.id));
const activeCategory = computed(() => props.query?.category ?? "pending");
const totalRecords = computed(() => props.record?.meta?.total ?? rows.value.length);
const withScheduledDate = computed(() => rows.value.filter((row) => row.collection_date).length);
const withSampleEntry = computed(() => rows.value.filter((row) => row.sample_entry || row.entry_origin?.is_sample_entry_first).length);
const metrics = computed(() => [
  { label: "Na fila", value: totalRecords.value, detail: activeCategory.value === "pending" ? "planeamentos activos" : "registos processados", icon: ClipboardDocumentListIcon },
  { label: "Com data", value: withScheduledDate.value, detail: "nesta página", icon: CalendarDaysIcon },
  { label: "Via entrada de amostra", value: withSampleEntry.value, detail: "linhagem preservada", icon: ArchiveBoxIcon },
]);
const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];
const actionConfirmation = {
  delete: {
    title: "Arquivar colheita?",
    description: "A colheita e a entrada de amostra sairão da fila. Poderá restaurá-las; análises, resultados e assinaturas serão preservados.",
  },
  restore: {
    title: "Restaurar colheita?",
    description: "A colheita e a entrada de amostra voltarão à fila. Os dados analíticos serão mantidos.",
  },
};
const confirmationTitle = computed(() => actionConfirmation[selectedAction.value]?.title);
const confirmationDescription = computed(() => actionConfirmation[selectedAction.value]?.description);

function changeCategory(category) {
  router.get(route("programmedcollections.index"), { ...props.query, category }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function createViaSampleEntry() {
  router.visit(props.entrypoint?.create_sample_url || route("vap_samples.index", { collection_type: "programmed" }));
}

function exportSelectedAnalysisSheet() {
  if (!selectedRecordIds.value.length) {
    return;
  }

  const params = new URLSearchParams();
  selectedRecordIds.value.forEach((id) => params.append("recordIds[]", id));
  window.location.href = `${route("programmedcollections.exportParametersToAnalyzeSheet")}?${params.toString()}`;
}

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
  if (!selectedRecordIds.value.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  isSubmitting.value = true;
  const endpoint = selectedAction.value === "delete" ? "destroy" : "restore";
  router.post(route(`programmedcollections.${endpoint}`), { recordIds: selectedRecordIds.value }, {
    preserveScroll: true,
    onFinish: () => {
      isSubmitting.value = false;
      closeActionConfirmation();
    },
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <CalendarDaysIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Planeamento de campo</p>
            <h1 class="ds-heading mt-1 text-2xl">Colheitas programadas</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Agenda de colheitas externas com destino, data prevista e linhagem para a recepção laboratorial.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <div class="inline-flex overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1">
            <button type="button" class="ds-button min-h-0 border-0 px-3 py-2 shadow-none" :class="activeCategory === 'pending' ? 'ds-button-primary' : 'ds-button-secondary'" @click="changeCategory('pending')">
              <ClipboardDocumentListIcon class="h-4 w-4" /> Pendentes
            </button>
            <button type="button" class="ds-button min-h-0 border-0 px-3 py-2 shadow-none" :class="activeCategory === 'archived' ? 'ds-button-primary' : 'ds-button-secondary'" @click="changeCategory('archived')">
              <ArchiveBoxIcon class="h-4 w-4" /> Processadas
            </button>
          </div>
          <Link :href="entrypoint.create_sample_url || route('vap_samples.index', { collection_type: 'programmed' })" class="ds-button ds-button-primary">
            <PlusIcon class="h-4 w-4" /> Nova entrada de amostra
          </Link>
        </div>
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

    <section class="ds-command-surface px-5 py-4 sm:px-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="ds-kicker">Preparação de bancada</p>
          <h2 class="ds-heading mt-1 text-base">Folha de parâmetros</h2>
          <p class="ds-copy mt-1 text-sm">{{ selectedRecordIds.length ? `${selectedRecordIds.length} registo(s) seleccionados.` : "Seleccione planeamentos na tabela para exportar o XLSX." }}</p>
        </div>
        <button type="button" class="ds-button ds-button-secondary" :disabled="!selectedRecordIds.length" @click="exportSelectedAnalysisSheet">
          <ArrowDownTrayIcon class="h-4 w-4" /> Exportar XLSX
        </button>
      </div>
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
      :action-confirmation="actionConfirmation"
      @execute-action="requestBulkAction"
      @create-record="createViaSampleEntry"
    >
      <template #actions="{ id, data }">
        <Link v-if="!data.deleted && hasPermission(`view_${model}`)" :href="route('programmedcollections.show', { collection: id })" class="ds-icon-button" title="Consultar planeamento" aria-label="Consultar planeamento">
          <EyeIcon class="h-4 w-4" />
        </Link>
      </template>
    </RecordsTable>

    <p v-if="isSubmitting" role="status" class="ds-copy text-sm">A actualizar o arquivo...</p>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationTitle"
      :description="confirmationDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="closeActionConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
