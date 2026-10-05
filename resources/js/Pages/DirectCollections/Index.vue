<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import VapTable from "@/Components/vap-table/table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  Archive as ArchiveBoxIcon,
  Download as ArrowDownTrayIcon,
  Repeat as ArrowPathRoundedSquareIcon,
  FlaskConical as BeakerIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  ClipboardList as ClipboardDocumentListIcon,
  FileDown as DocumentArrowDownIcon,
  Eye as EyeIcon,
  Pencil as PencilIcon,
  Plus as PlusIcon,
  QrCode as QrCodeIcon,
  Tag as TagIcon,
  Trash2 as TrashIcon,
} from "@lucide/vue";
import { Link, router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  stats: { type: Array, default: () => [] },
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  trashedFilter: { type: Boolean, default: false },
  trashedOptions: { type: Object, default: () => ({}) },
  initialFilters: { type: Object, default: () => ({}) },
  initialSortField: { type: String, default: "" },
  initialSortDirection: { type: String, default: "asc" },
  initialIncludes: { type: Array, default: () => [] },
  entrypoint: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});

const { hasPermission } = usePermission();
const selectedIds = ref([]);
const pendingAction = ref(null);
const pendingActionType = ref("bulk");
const pendingRow = ref(null);
const showActionConfirmation = ref(false);
const isSubmitting = ref(false);
const activeCategory = computed(() => props.query?.category ?? "pending");
const rows = computed(() => props.record?.data ?? []);
const selectedCount = computed(() => selectedIds.value.length);
const statIcons = [ClipboardDocumentListIcon, ClipboardDocumentCheckIcon, BeakerIcon, QrCodeIcon];
const metrics = computed(() => props.stats.map((stat, index) => ({ ...stat, icon: statIcons[index] ?? BeakerIcon })));
const columns = props.fields.map((field) => ({
  field: field.value,
  filter_field: field.filter_field,
  label: field.name,
  visible: true,
  filterable: field.filterable,
  type: field.type,
  format: field.format,
  filter: field.filter,
  options: field.options ?? [],
  config: field.config ?? {},
}));
const filters = [
  { id: null, label: trans("gestlab.filter.none") },
  { id: "trashed", label: trans("gestlab.filter.excluded") },
];
const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];
const scopeDashboard = computed(() => {
  const scopedRows = rows.value.filter((row) => Number(row.scope_control?.required_parameter_count ?? 0) > 0);
  const restrictedRows = scopedRows.filter((row) => row.scope_control?.conditioning_status === "restricted");

  return {
    scopedCount: scopedRows.length,
    restrictedCount: restrictedRows.length,
    requiredParameterTotal: scopedRows.reduce((total, row) => total + Number(row.scope_control?.required_parameter_count ?? 0), 0),
    pendingScopedCount: scopedRows.filter((row) => Number(row.pending_analysis ?? 0) > 0).length,
    attentionRows: scopedRows
      .filter((row) => row.scope_control?.conditioning_status === "restricted" || Number(row.pending_analysis ?? 0) > 0)
      .sort((left, right) => {
        const leftWeight = (left.scope_control?.conditioning_status === "restricted" ? 10 : 0) + Number(left.pending_analysis ?? 0);
        const rightWeight = (right.scope_control?.conditioning_status === "restricted" ? 10 : 0) + Number(right.pending_analysis ?? 0);
        return rightWeight - leftWeight;
      })
      .slice(0, 5),
  };
});
const conditioningLabels = {
  accepted: "Aceite",
  restricted: "Aceite com restrições",
  rejected: "Rejeitado",
};
const confirmationTitle = computed(() => pendingAction.value === "restore" ? "Restaurar colheita?" : "Arquivar colheita?");
const confirmationDescription = computed(() => pendingAction.value === "restore"
  ? "A colheita e a entrada de amostra voltarão à fila. Os dados analíticos serão mantidos."
  : "A colheita e a entrada de amostra sairão da fila. Poderá restaurá-las; análises, resultados e assinaturas serão preservados.");

function changeCategory(category) {
  router.get(route("directcollections.index"), { ...props.query, category }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function exportSelectedAnalysisSheet() {
  if (!selectedCount.value) {
    return;
  }

  const params = new URLSearchParams();
  selectedIds.value.forEach((id) => params.append("recordIds[]", id));
  window.location.href = `${route("directcollections.exportParametersToAnalyzeSheet")}?${params.toString()}`;
}

function requestBulkAction(event) {
  if (isSubmitting.value) return;
  pendingAction.value = event.action;
  pendingActionType.value = "bulk";
  pendingRow.value = null;
  showActionConfirmation.value = true;
}

function requestRowAction(action, row) {
  if (isSubmitting.value) return;
  pendingAction.value = action;
  pendingActionType.value = "single";
  pendingRow.value = row;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  pendingAction.value = null;
  pendingRow.value = null;
  showActionConfirmation.value = false;
}

function executeAction() {
  if (isSubmitting.value) return;
  const recordIds = pendingActionType.value === "single" ? [pendingRow.value?.id] : selectedIds.value;

  if (!recordIds.filter(Boolean).length || !["delete", "restore"].includes(pendingAction.value)) {
    closeActionConfirmation();
    return;
  }

  isSubmitting.value = true;
  const endpoint = pendingAction.value === "delete" ? "destroy" : "restore";
  router.post(route(`directcollections.${endpoint}`), { recordIds: recordIds.filter(Boolean) }, {
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
    <PageHeader title="Colheitas directas" lede="Fila técnica de amostras recebidas directamente, com âmbito analítico, condicionamento e documentos operacionais.">
      <template #actions>
        <div class="inline-flex overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1">
          <button type="button" class="ds-button min-h-0 border-0 px-3 py-2 shadow-none" :class="activeCategory === 'pending' ? 'ds-button-primary' : 'ds-button-secondary'" @click="changeCategory('pending')">
            <ClipboardDocumentListIcon class="h-4 w-4" /> Pendentes
          </button>
          <button type="button" class="ds-button min-h-0 border-0 px-3 py-2 shadow-none" :class="activeCategory === 'archived' ? 'ds-button-primary' : 'ds-button-secondary'" @click="changeCategory('archived')">
            <ArchiveBoxIcon class="h-4 w-4" /> Processadas
          </button>
        </div>
        <Link :href="entrypoint.create_sample_url || route('vap_samples.index', { collection_type: 'direct' })" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" /> Nova entrada de amostra
        </Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.name" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.name }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}{{ metric.unit || "" }}</dd>
      </div>
    </dl>

    <section class="ds-command-surface px-5 py-4 sm:px-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="ds-kicker">Bancada</p>
          <h2 class="ds-heading mt-1 text-base">Folha de parâmetros</h2>
          <p class="ds-copy mt-1 text-sm">{{ selectedCount ? `${selectedCount} registo(s) prontos para exportação.` : "Seleccione colheitas na tabela para preparar a folha XLSX." }}</p>
        </div>
        <button type="button" class="ds-button ds-button-secondary" :disabled="!selectedCount" @click="exportSelectedAnalysisSheet">
          <ArrowDownTrayIcon class="h-4 w-4" /> Exportar XLSX
        </button>
      </div>
    </section>

    <section v-if="scopeDashboard.scopedCount" class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(19rem,0.7fr)]">
      <div class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Âmbito controlado</p>
          <h2 class="ds-heading mt-1 text-base">Estado da fila técnica</h2>
        </div>
        <dl class="grid bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
          <div class="border-b border-r border-[var(--ds-border)] p-4"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Com âmbito</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ scopeDashboard.scopedCount }}</dd></div>
          <div class="border-b border-[var(--ds-border)] p-4 xl:border-r"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Com restrições</dt><dd class="mt-2 text-xl font-bold text-amber-700 dark:text-amber-300">{{ scopeDashboard.restrictedCount }}</dd></div>
          <div class="border-r border-[var(--ds-border)] p-4"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Parâmetros</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ scopeDashboard.requiredParameterTotal }}</dd></div>
          <div class="p-4"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Pendentes</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ scopeDashboard.pendingScopedCount }}</dd></div>
        </dl>
      </div>

      <aside class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="ds-heading text-sm">Atenção prioritária</h2></div>
        <div v-if="scopeDashboard.attentionRows.length" class="divide-y divide-[var(--ds-border)]">
          <Link v-for="row in scopeDashboard.attentionRows" :key="row.id" :href="route('directcollections.show', { collection: row.id })" class="block px-5 py-3 transition hover:bg-[var(--ds-panel-subtle)]">
            <div class="flex items-start justify-between gap-3">
              <div><p class="text-sm font-semibold text-[var(--ds-text)]">{{ row.cl }} · {{ row.product }}</p><p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ row.pending_analysis || 0 }} análises pendentes</p></div>
              <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.scope_control?.conditioning_status === 'restricted' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' : 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)]'">{{ conditioningLabels[row.scope_control?.conditioning_status] || "Sem avaliação" }}</span>
            </div>
          </Link>
        </div>
        <p v-else class="px-5 py-6 text-sm text-[var(--ds-text-muted)]">Nenhuma colheita requer atenção prioritária.</p>
      </aside>
    </section>

    <section class="ds-panel overflow-hidden p-4 sm:p-5">
      <VapTable
        has-qr
        row-title-field="cl"
        :model="model"
        :abilities="abilities"
        :data="record.data"
        :columns="columns"
        :query="query"
        :filters="filters"
        :initial-filters="initialFilters"
        :initial-sort-field="initialSortField"
        :initial-sort-direction="initialSortDirection"
        :initial-includes="initialIncludes"
        :trashed-filter="trashedFilter"
        :trashed-options="trashedOptions"
        :slide-over-edit="slideOverEdit"
        :pagination="record.meta"
        :actions="actions"
        @create-record="router.visit(entrypoint.create_sample_url || route('vap_samples.index', { collection_type: 'direct' }))"
        @update-selected-ids="selectedIds = $event"
        @execute-bulk-action="requestBulkAction"
      >
        <template #column-qr="{ row }">
          <img :src="row.qr" :alt="`QR da colheita ${row.cl || row.id}`" class="aspect-square h-16 w-16 shrink-0 object-contain">
        </template>

        <template #column-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <Link v-if="!row.deleted && hasPermission(`view_${model}`)" :href="route('directcollections.show', { collection: row.id })" class="ds-icon-button" title="Consultar colheita" aria-label="Consultar colheita"><EyeIcon class="h-4 w-4" /></Link>
            <a v-if="!row.deleted && hasPermission(`view_${model}`) && row.links?.pdf_path" :href="row.links.pdf_path" target="_blank" class="ds-icon-button" title="Folha de parâmetros" aria-label="Folha de parâmetros"><DocumentArrowDownIcon class="h-4 w-4" /></a>
            <a v-if="!row.deleted && hasPermission(`view_${model}`) && row.links?.pdf_collection_term" :href="row.links.pdf_collection_term" target="_blank" class="ds-icon-button" title="Termo de colheita" aria-label="Termo de colheita"><ClipboardDocumentCheckIcon class="h-4 w-4" /></a>
            <a v-if="!row.deleted && hasPermission(`view_${model}`) && row.links?.pdf_collection_labels" :href="row.links.pdf_collection_labels" target="_blank" class="ds-icon-button" title="Etiquetas" aria-label="Etiquetas"><TagIcon class="h-4 w-4" /></a>
            <Link v-if="!row.deleted && hasPermission(`edit_${model}`)" :href="row.links.edit_path" class="ds-icon-button" title="Editar colheita" aria-label="Editar colheita"><PencilIcon class="h-4 w-4" /></Link>
            <button v-if="row.deleted && hasPermission(`restore_${model}`)" type="button" class="ds-icon-button" :disabled="isSubmitting" title="Restaurar colheita" aria-label="Restaurar colheita" @click="requestRowAction('restore', row)"><ArrowPathRoundedSquareIcon class="h-4 w-4" /></button>
            <button v-if="!row.deleted && hasPermission(`delete_${model}`)" type="button" class="ds-icon-button text-red-600" :disabled="isSubmitting" title="Arquivar colheita" aria-label="Arquivar colheita" @click="requestRowAction('delete', row)"><TrashIcon class="h-4 w-4" /></button>
          </div>
        </template>
      </VapTable>
    </section>

    <p v-if="isSubmitting" role="status" class="ds-copy text-sm">A actualizar o arquivo...</p>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationTitle"
      :description="confirmationDescription"
      :variant="pendingAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="closeActionConfirmation"
      @confirmed="executeAction"
    />
  </div>
</template>
