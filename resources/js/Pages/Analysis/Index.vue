<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import SelectInput from "@/Components/select-input.vue";
import VapTable from "@/Components/vap-table/table.vue";
import { usePermission } from "@/Composables/usePermissions";
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
  ArchiveBoxIcon,
  ArrowPathRoundedSquareIcon,
  BeakerIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  DocumentCheckIcon,
  DocumentMagnifyingGlassIcon,
  DocumentPlusIcon,
  PencilSquareIcon,
  TrashIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    default: () => ({ data: [], meta: {} }),
  },
  departments: {
    type: Array,
    default: () => [],
  },
  fields: {
    type: Array,
    default: () => [],
  },
  model: String,
  abilities: {
    type: Array,
    default: () => [],
  },
  query: {
    type: Object,
    default: () => ({}),
  },
  trashedFilter: {
    type: Boolean,
    default: false,
  },
  trashedOptions: {
    type: Object,
    default: () => ({}),
  },
  initialFilters: {
    type: Object,
    default: () => ({}),
  },
  initialSortField: {
    type: String,
    default: "",
  },
  initialSortDirection: {
    type: String,
    default: "asc",
  },
  initialIncludes: {
    type: Array,
    default: () => [],
  },
  initialGlobalFilter: {
    type: String,
    default: "",
  },
  entrypoint: {
    type: Object,
    default: () => ({}),
  },
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

const { hasPermission } = usePermission();
const page = usePage();

const department = ref(props.query?.department ?? null);
const showConfirmation = ref(false);
const pendingAction = ref(null);
const pendingActionType = ref(null);
const pendingRecord = ref(null);
const pendingUrl = ref(null);
const selectedIDs = ref([]);

const resultActions = [
  {
    value: "insert",
    title: "gestlab.general.labels.analysis.insert_result_title",
    description: "Resultados por lançar",
    icon: DocumentPlusIcon,
    dot: "lims-status-dot-hold",
  },
  {
    value: "verify",
    title: "gestlab.general.labels.analysis.verify_result_title",
    description: "Revisão técnica",
    icon: DocumentMagnifyingGlassIcon,
    dot: "lims-status-dot-instrument",
  },
  {
    value: "approve",
    title: "gestlab.general.labels.analysis.approve_result_title",
    description: "Decisão final",
    icon: DocumentCheckIcon,
    dot: "lims-status-dot-release",
  },
  {
    value: "archived",
    title: "gestlab.general.labels.analysis.completed_result_title",
    description: "Dossiês concluídos",
    icon: ArchiveBoxIcon,
    dot: "lims-status-dot-neutral",
  },
];

const selectedResultAction = computed(() => {
  return resultActions.find((item) => item.value === props.query?.category) ?? resultActions[0];
});

const selectedDepartmentLabel = computed(() => {
  const selectedValue = department.value?.value ?? department.value;
  return props.departments.find((item) => String(item.value) === String(selectedValue))?.label ?? "Todos";
});

const queueMetrics = computed(() => [
  {
    label: "Na fila",
    value: props.record?.meta?.total ?? props.record?.data?.length ?? 0,
    note: selectedResultAction.value.description,
  },
  {
    label: "Etapa ativa",
    value: selectedResultAction.value.value.toUpperCase(),
    note: "estado do fluxo",
  },
  {
    label: "Departamento",
    value: selectedDepartmentLabel.value,
    note: "escopo operacional",
  },
  {
    label: "Nesta página",
    value: props.record?.data?.length ?? 0,
    note: "registos carregados",
  },
]);

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

const entryLineageColumn = {
  field: "sample_entry",
  filter_field: "sample_entry",
  label: "gestlab.general.labels.analysis.sample_entry",
  visible: true,
  filterable: false,
  type: "custom",
  format: "text",
  filter: "",
  options: [],
  config: {},
};

const tableColumns = computed(() => [
  ...columns.filter((column) => column.field !== "actions"),
  entryLineageColumn,
  ...columns.filter((column) => column.field === "actions"),
  {
    field: "category",
    filter_field: "category",
    label: "Categoria",
    visible: false,
    filterable: true,
    type: "select",
    format: "text",
    filter: "text",
    options: [
      { value: "insert", label: "Inserir" },
      { value: "verify", label: "Verificar" },
      { value: "approve", label: "Validar" },
    ],
  },
]);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const confirmationDialogTitle = computed(() => {
  return pendingAction.value === "restore" ? "Repor análise" : "Eliminar análise";
});

const confirmationDialogDescription = computed(() => {
  if (pendingAction.value === "restore") {
    return "A análise regressará à fila operacional e ficará novamente disponível para trabalho.";
  }

  return "A análise será removida da fila ativa. O registo permanece recuperável no arquivo.";
});

function changeAnalysisCategory(category = "insert") {
  router.get(
    page.url,
    {
      ...props.query,
      category,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
}

function changeAnalysisDepartment(value) {
  department.value = value;
  router.get(
    page.url,
    {
      ...props.query,
      department: value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
}

function handleCreateAnalysis() {
  router.get(props.entrypoint?.create_sample_url || route("vap_samples.index"));
}

function openAnalysis(row) {
  if (!row?.links?.edit_path) {
    return;
  }

  router.get(row.links.edit_path);
}

function requestConfirmation(actionName, actionType, row = null, url = null) {
  pendingAction.value = actionName;
  pendingActionType.value = actionType;
  pendingRecord.value = row;
  pendingUrl.value = url;
  showConfirmation.value = true;
}

function resetConfirmation() {
  showConfirmation.value = false;
  pendingAction.value = null;
  pendingActionType.value = null;
  pendingRecord.value = null;
  pendingUrl.value = null;
}

function confirmAction() {
  const isBulk = pendingActionType.value === "bulk";
  const recordIds = isBulk ? selectedIDs.value : [pendingRecord.value?.id].filter(Boolean);

  if (!recordIds.length) {
    resetConfirmation();
    return;
  }

  const destination = isBulk
    ? route(pendingAction.value === "restore" ? "analysis.restore" : "analysis.destroy")
    : pendingUrl.value;

  router.get(
    destination,
    { recordIds },
    {
      preserveState: false,
      preserveScroll: true,
      onFinish: resetConfirmation,
    },
  );
}

function handleBulkAction(event) {
  requestConfirmation(event.action, event.actionType);
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Bancada analítica</p>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="selectedResultAction.dot" />
              {{ selectedResultAction.description }}
            </span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">Fila de análises</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            Registe, verifique e aprove resultados com o departamento, a origem da amostra e a decisão atual sempre visíveis.
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="w-full min-w-0 sm:w-64">
            <label class="ds-field-label" for="analysis-department">Departamento</label>
            <SelectInput
              id="analysis-department"
              v-model="department"
              :options="departments"
              :selected="department"
              class="mt-1 w-full"
              @update:model-value="changeAnalysisDepartment"
            />
          </div>
          <button type="button" class="ds-button ds-button-primary" @click="handleCreateAnalysis">
            <DocumentPlusIcon class="h-4 w-4" aria-hidden="true" />
            Receber amostra
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in queueMetrics"
          :key="metric.label"
          class="min-w-0 border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 truncate text-lg">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.note }}</p>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-4 sm:px-5">
        <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Etapas de análise">
          <button
            v-for="item in resultActions"
            :key="item.value"
            type="button"
            :class="[
              'group flex shrink-0 items-center gap-2 border-b-2 px-1 py-4 text-sm font-bold transition',
              selectedResultAction.value === item.value
                ? 'border-[rgb(var(--primary-600-rgb))] text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-300-rgb))]'
                : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]',
            ]"
            @click="changeAnalysisCategory(item.value)"
          >
            <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
            {{ $t(item.title) }}
          </button>
        </nav>
      </div>

      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="ds-table-heading">Lista de trabalho</p>
          <p class="ds-copy mt-1 text-xs">
            {{ selectedResultAction.description }} no departamento {{ selectedDepartmentLabel }}.
          </p>
        </div>
        <span class="ds-chip">
          <BeakerIcon class="h-4 w-4" />
          {{ props.record?.meta?.total ?? props.record?.data?.length ?? 0 }} registos
        </span>
      </div>

      <div class="min-w-0">
        <VapTable
          :model="model"
          :abilities="abilities"
          :data="record.data"
          :columns="tableColumns"
          :query="query"
          :initial-filters="initialFilters"
          :initial-sort-field="initialSortField"
          :initial-sort-direction="initialSortDirection"
          :initial-includes="initialIncludes"
          :trashed-filter="trashedFilter"
          :trashed-options="trashedOptions"
          :slide-over-edit="slideOverEdit"
          :pagination="record.meta"
          :actions="actions"
          @create-record="handleCreateAnalysis"
          @update-selected-ids="selectedIDs = $event"
          @execute-bulk-action="handleBulkAction"
        >
          <template #column-sample_entry="{ row }">
            <div class="min-w-0">
              <Link
                v-if="row.sample_entry?.show_url"
                :href="row.sample_entry.show_url"
                class="ds-table-action max-w-full"
              >
                <ClipboardDocumentCheckIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                <span class="min-w-0">
                  <span class="block truncate">
                    {{ row.sample_entry.code || row.sample_entry.name || $t("gestlab.general.labels.analysis.sample_entry") }}
                  </span>
                  <span class="block truncate text-[11px] font-semibold text-[var(--ds-text-soft)]">
                    {{ row.sample?.code || row.entry_origin?.label || "Sem código de amostra" }}
                  </span>
                </span>
              </Link>
              <span v-else class="ds-chip">
                <span class="lims-status-dot lims-status-dot-hold" />
                {{ row.entry_origin?.label || $t("gestlab.general.labels.analysis.legacy_record") }}
              </span>
            </div>
          </template>

          <template #column-status="{ row }">
            <span class="ds-chip">
              <span
                class="lims-status-dot"
                :class="row.status ? 'lims-status-dot-release' : 'lims-status-dot-hold'"
              />
              {{ row.status ? "Ativa" : "Pendente" }}
            </span>
          </template>

          <template #column-actions="{ row }">
            <div class="flex items-center justify-end gap-1">
              <button
                v-if="row.deleted && hasPermission('restore_' + model)"
                type="button"
                class="ds-icon-button"
                :title="$t('actions.restore')"
                @click="requestConfirmation('restore', 'single', row, row.links.restore_path)"
              >
                <ArrowPathRoundedSquareIcon class="h-4 w-4" />
                <span class="sr-only">{{ $t("actions.restore") }}</span>
              </button>

              <button
                v-if="!row.deleted && hasPermission('edit_' + model)"
                type="button"
                class="ds-icon-button"
                :title="$t('actions.edit')"
                @click="openAnalysis(row)"
              >
                <PencilSquareIcon class="h-4 w-4" />
                <span class="sr-only">{{ $t("actions.edit") }}</span>
              </button>

              <button
                v-if="!row.deleted && hasPermission('delete_' + model)"
                type="button"
                class="ds-icon-button text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/10"
                :title="$t('actions.delete')"
                @click="requestConfirmation('delete', 'single', row, row.links.delete_path)"
              >
                <TrashIcon class="h-4 w-4" />
                <span class="sr-only">{{ $t("actions.delete") }}</span>
              </button>
            </div>
          </template>
        </VapTable>
      </div>
    </section>

    <section class="lims-status-strip p-4">
      <div class="flex items-start gap-3">
        <CheckBadgeIcon class="h-5 w-5 shrink-0 text-[var(--lims-release)]" />
        <div>
          <h2 class="ds-heading text-sm">Separação de funções preservada</h2>
          <p class="ds-copy mt-1 text-xs">
            A fila mantém inserção, verificação e aprovação como etapas distintas para suportar a rastreabilidade ISO 17025.
          </p>
        </div>
      </div>
    </section>

    <ConfirmDialog
      v-if="showConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Sim"
      cancel="Não"
      @canceled="resetConfirmation"
      @close="resetConfirmation"
      @confirmed="confirmAction"
    />
  </div>
</template>
