<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import ApproveResultComponent from "@/Components/results/ApproveResultComponent.vue";
import CalculationModal from "@/Components/results/CalculationModal.vue";
import InsertResultComponent from "@/Components/results/InsertResultComponent.vue";
import VerifyResultComponent from "@/Components/results/VerifyResultComponent.vue";
import { ResultsDataService } from "@/Services/ResultsDataService.js";
import { computed, onMounted, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowTopRightOnSquareIcon,
  BeakerIcon,
  CalculatorIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  QueueListIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  action: String,
  record: {
    type: Object,
    default: () => ({}),
  },
  parameters: {
    type: Array,
    default: () => [],
  },
  results_summary: {
    type: Object,
    default: () => ({
      total: 0,
      pending_insertion: 0,
      pending_verification: 0,
      pending_approval: 0,
      approved: 0,
    }),
  },
  report_studio: {
    type: Object,
    default: null,
  },
  scope_audit: {
    type: Object,
    default: () => ({}),
  },
  worksheet_brief: {
    type: Object,
    default: null,
  },
  can_insert: {
    type: Boolean,
    default: false,
  },
  can_verify: {
    type: Boolean,
    default: false,
  },
  can_approve: {
    type: Boolean,
    default: false,
  },
  result_data_url: {
    type: String,
    default: null,
  },
  store_results_url: {
    type: String,
    default: null,
  },
  workflow_kind: {
    type: String,
    default: "analysis",
  },
  allow_worksheet_draft: {
    type: Boolean,
    default: false,
  },
});

const showCalculationModal = ref(false);
const calculationParameters = ref([]);
const existingCalculationData = ref({});
const resultsLoadError = ref("");
const workflowNotice = ref("");
const worksheetForm = useForm({});

const form = useForm({
  action: props.action,
  id: props.record?.id,
  cl_id: props.record?.cl_id,
  sample_id: props.record?.sample_id,
  department_id: props.record?.department_id,
  type_id: props.record?.type_id,
  results: [],
  notes: "",
  status: "pending",
  performed_by: null,
  performed_at: null,
});

const workflowComponents = {
  analyze: InsertResultComponent,
  verify: VerifyResultComponent,
  approve: ApproveResultComponent,
};

const workflowActionIndex = {
  analyze: 0,
  verify: 1,
  approve: 2,
  completed: 3,
};

const CurrentComponent = computed(() => {
  return workflowComponents[props.action] || null;
});

const workflowTitle = computed(() => {
  const titles = {
    analyze: "Inserção de resultados",
    verify: "Verificação técnica",
    approve: "Aprovação de resultados",
    completed: "Análise concluída",
  };

  return titles[props.action] || "Fluxo de resultados";
});

const workflowLabel = computed(() => {
  const labels = {
    analyze: "Inserção",
    verify: "Verificação",
    approve: "Aprovação",
    completed: "Concluída",
  };

  return labels[props.action] || "Em curso";
});

const workflowKindLabel = computed(() => {
  return props.workflow_kind === "counter_analysis" ? "Contra-análise" : "Análise";
});

const workflowSteps = computed(() => {
  const activeIndex = workflowActionIndex[props.action] ?? 0;

  return [
    { key: "analyze", label: "Inserção", permission: props.can_insert },
    { key: "verify", label: "Verificação", permission: props.can_verify },
    { key: "approve", label: "Aprovação", permission: props.can_approve },
    { key: "completed", label: "Arquivo", permission: true },
  ].map((step, index) => ({
    ...step,
    state: index < activeIndex ? "complete" : index === activeIndex ? "current" : "upcoming",
  }));
});

const resultDataUrl = computed(() => {
  return props.result_data_url || route("results.getDefaultResultsData");
});

const storeResultsUrl = computed(() => {
  return props.store_results_url || route("results.store");
});

const workflowOriginLabel = computed(() => {
  return props.record?.entry_origin?.is_sample_entry_first
    ? "Entrada de amostra controlada"
    : "Registo legado";
});

const sampleEntryMetaCards = computed(() => [
  {
    label: "Entrada de amostra",
    value: props.record?.sample_entry?.code || props.record?.sample_entry?.name || "Não vinculada",
  },
  {
    label: "Código laboratorial",
    value: props.record?.cl_id?.label || props.record?.code || "N/D",
  },
  {
    label: "Amostra",
    value: props.record?.sample?.code || props.record?.sample_id?.label || "N/D",
  },
  {
    label: "Departamento",
    value: props.record?.department_id?.label || "N/D",
  },
]);

const workflowStatusCards = computed(() => [
  {
    label: "Por inserir",
    value: props.results_summary?.pending_insertion ?? 0,
    dot: "lims-status-dot-hold",
  },
  {
    label: "Por verificar",
    value: props.results_summary?.pending_verification ?? 0,
    dot: "lims-status-dot-instrument",
  },
  {
    label: "Por aprovar",
    value: props.results_summary?.pending_approval ?? 0,
    dot: "lims-status-dot-critical",
  },
  {
    label: "Aprovados",
    value: props.results_summary?.approved ?? 0,
    dot: "lims-status-dot-release",
  },
]);

const conditioningLabels = {
  accepted: "Aceite",
  restricted: "Aceite com restrições",
  rejected: "Rejeitado / quarentena",
};

const scopeSummaryCards = computed(() => [
  { label: "Perfil analítico", value: props.scope_audit?.expected_count ?? 0 },
  { label: "Planeado na recepção", value: props.scope_audit?.reception_count ?? 0 },
  { label: "Resultados lançados", value: props.scope_audit?.results_count ?? 0 },
  { label: "Faltam no fluxo", value: props.scope_audit?.missing_from_results?.length ?? 0 },
]);

const hasScopeDrift = computed(() => {
  return Boolean(
    (props.scope_audit?.scope_drift?.reception_only?.length ?? 0) ||
      (props.scope_audit?.scope_drift?.profile_only?.length ?? 0) ||
      (props.scope_audit?.outside_profile_scope?.length ?? 0),
  );
});

const scopeDriftSummary = computed(() => [
  {
    label: "Na recepção, fora do perfil",
    items: props.scope_audit?.scope_drift?.reception_only ?? [],
  },
  {
    label: "No perfil, fora da recepção",
    items: props.scope_audit?.scope_drift?.profile_only ?? [],
  },
  {
    label: "Resultados fora do perfil",
    items: props.scope_audit?.outside_profile_scope ?? [],
  },
]);

const separatedResults = computed(() => {
  return ResultsDataService.separateCalculatedParameters(form.results);
});

const hasCalculatedParameters = computed(() => {
  return form.results?.some((result) => result.requires_calculation && result.active);
});

const calculationReadiness = computed(() => {
  return (form.results ?? [])
    .filter((result) => result.requires_calculation && result.active)
    .map((result) => ({
      code: result.parameter_id?.code || result.parameter_label,
      name: result.parameter_id?.name || result.parameter_label,
      ...ResultsDataService.getCalculationReadiness(result, form.results, props.action),
    }));
});

const readyCalculatedParameters = computed(() => {
  return calculationReadiness.value.filter((item) => item.ready);
});

const blockingCalculatedParameters = computed(() => {
  return calculationReadiness.value.filter((item) => !item.ready);
});

const executionControlCards = computed(() => [
  {
    label: "Manuais",
    value: separatedResults.value.manualParams?.length ?? 0,
  },
  {
    label: "Variáveis",
    value: separatedResults.value.inputVariables?.length ?? 0,
  },
  {
    label: "Calculados",
    value: separatedResults.value.calculatedParams?.length ?? 0,
  },
  {
    label: "Bloqueados",
    value: blockingCalculatedParameters.value.length,
  },
]);

onMounted(loadResultParameters);

async function loadResultParameters() {
  if (!CurrentComponent.value) {
    resultsLoadError.value = "";
    form.results = [];
    workflowNotice.value = props.action === "completed"
      ? "Os resultados estão aprovados. Esta consulta não modifica a análise."
      : "A etapa desta análise não permite alterações de resultados.";
    return;
  }

  try {
    resultsLoadError.value = "";
    workflowNotice.value = "";

    const params = new URLSearchParams({ action: props.action });
    if (props.record?.sample_id?.value) {
      params.set("sample_id", props.record.sample_id.value);
    }

    const response = await fetch(resultDataUrl.value + "?" + params.toString(), {
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    });

    if (!response.ok) {
      let errorPayload = {};
      try {
        errorPayload = await response.json();
      } catch {
        errorPayload = {};
      }

      throw new Error(
        errorPayload?.message || "Não foi possível carregar os resultados desta amostra.",
      );
    }

    const payload = await response.json();
    form.results = ResultsDataService.normalizeResults(payload);

    if (hasCalculatedParameters.value) {
      calculationParameters.value = separatedResults.value.calculatedParams;
      existingCalculationData.value = ResultsDataService.prepareForCalculation(
        calculationParameters.value,
        form.results,
      );
    }
  } catch (error) {
    resultsLoadError.value =
      error?.message || "Não foi possível carregar os resultados desta amostra.";
    form.results = [];
  }
}

function createWorksheetDraft() {
  if (!props.allow_worksheet_draft || worksheetForm.processing || !props.record?.id) return;
  worksheetForm.post(route("analysis.worksheet-draft", props.record.id), {
    preserveScroll: true,
  });
}

function openCalculationModal() {
  workflowNotice.value = "";
  calculationParameters.value = separatedResults.value.calculatedParams;
  existingCalculationData.value = ResultsDataService.prepareForCalculation(
    calculationParameters.value,
    form.results,
  );

  if (calculationParameters.value.length) {
    showCalculationModal.value = true;
    return;
  }

  workflowNotice.value = "Nenhum parâmetro calculado foi definido para esta amostra.";
}

function handleCalculatedResults(calculationPayload) {
  form.results = ResultsDataService.mergeCalculationResults(
    form.results,
    calculationPayload,
    props.action,
  );
  showCalculationModal.value = false;
}

function submitResults() {
  if (form.processing || !CurrentComponent.value) {
    return;
  }

  workflowNotice.value = "";

  const submissionData = {
    action: form.action,
    sample_id: form.sample_id,
    results: form.results,
    notes: form.notes,
    status: form.status,
    performed_by: form.performed_by,
    performed_at: form.performed_at,
  };

  if (form.action === "verify") {
    submissionData.verification_notes = form.notes;
    submissionData.verification_status = form.status;
    submissionData.verified_by = form.performed_by;
    submissionData.verified_at = form.performed_at;
  } else if (form.action === "approve") {
    submissionData.approval_notes = form.notes;
    submissionData.approval_status = form.status;
    submissionData.approved_by = form.performed_by;
    submissionData.approved_at = form.performed_at;
  }

  form.transform(() => submissionData).post(storeResultsUrl.value, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function formatDateTime(value) {
  if (!value) {
    return "Não registada";
  }

  return new Date(value).toLocaleString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

defineExpose({
  form,
  openCalculationModal,
  submitResults,
  hasCalculatedParameters,
});
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="route('analysis.index', { category: action === 'analyze' ? 'insert' : action })"
            class="ds-table-action -ml-2 mb-3"
          >
            <QueueListIcon class="h-4 w-4" />
            Voltar à fila
          </Link>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">{{ workflowKindLabel }} controlada</p>
            <span class="ds-chip">
              <span
                class="lims-status-dot"
                :class="hasScopeDrift ? 'lims-status-dot-hold' : 'lims-status-dot-release'"
              />
              {{ hasScopeDrift ? "Rever âmbito" : "Âmbito consistente" }}
            </span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">{{ workflowTitle }}</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            Execute a decisão analítica com rastreabilidade da recepção, do perfil, dos cálculos e da folha de trabalho.
          </p>
          <p class="mt-3 font-mono text-xs font-bold text-[var(--ds-text-muted)]">
            {{ record?.cl_id?.label || "Sem código" }} / {{ record?.department_id?.label || "Sem departamento" }}
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button
            v-if="action === 'analyze' && hasCalculatedParameters"
            type="button"
            class="ds-button ds-button-primary"
            @click="openCalculationModal"
          >
            <CalculatorIcon class="h-4 w-4" />
            Calcular parâmetros
          </button>
          <Link
            v-if="record?.links?.sample_entry_show_path"
            :href="record.links.sample_entry_show_path"
            class="ds-button ds-button-secondary"
          >
            <ClipboardDocumentCheckIcon class="h-4 w-4" />
            Entrada de amostra
          </Link>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="card in workflowStatusCards"
          :key="card.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="flex items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]">
            <span class="lims-status-dot" :class="card.dot" />
            {{ card.label }}
          </dt>
          <dd class="ds-heading mt-2 text-xl">{{ card.value }}</dd>
        </div>
      </dl>
    </section>

    <nav class="ds-command-surface overflow-hidden" aria-label="Progresso analítico">
      <ol class="grid sm:grid-cols-4">
        <li
          v-for="(step, index) in workflowSteps"
          :key="step.key"
          class="relative border-b border-[var(--ds-border)] p-4 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
        >
          <div class="flex items-center gap-3">
            <span
              :class="[
                'grid h-7 w-7 shrink-0 place-items-center rounded-full border text-xs font-bold',
                step.state === 'complete'
                  ? 'border-[var(--lims-release)] bg-[var(--lims-release-soft)] text-[var(--lims-release)]'
                  : step.state === 'current'
                    ? 'border-[rgb(var(--primary-600-rgb))] bg-[rgb(var(--primary-600-rgb))] text-white'
                    : 'border-[var(--ds-border-strong)] text-[var(--ds-text-soft)]',
              ]"
            >
              <CheckCircleIcon v-if="step.state === 'complete'" class="h-4 w-4" />
              <span v-else>{{ index + 1 }}</span>
            </span>
            <span class="min-w-0">
              <span class="ds-heading block truncate text-sm">{{ step.label }}</span>
              <span class="mt-0.5 block text-[11px] font-semibold text-[var(--ds-text-soft)]">
                {{ step.state === "current" ? "Etapa actual" : step.permission ? "Disponível" : "Sem permissão" }}
              </span>
            </span>
          </div>
        </li>
      </ol>
    </nav>

    <section v-if="resultsLoadError" class="lims-status-strip p-4">
      <div class="flex items-start gap-3">
        <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-critical)]" />
        <div>
          <h2 class="ds-heading text-sm">Não foi possível carregar a bancada</h2>
          <p class="ds-copy mt-1 text-xs">{{ resultsLoadError }}</p>
        </div>
      </div>
    </section>

    <section v-if="Object.keys(form.errors).length" class="lims-status-strip p-4">
      <div class="flex items-start gap-3">
        <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-critical)]" />
        <div class="min-w-0">
          <h2 class="ds-heading text-sm">Submissão bloqueada pela validação</h2>
          <ul class="mt-2 space-y-1 text-xs font-semibold text-[var(--lims-critical)]">
            <li v-for="(error, key) in form.errors" :key="key">
              {{ Array.isArray(error) ? error.join(", ") : error }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section v-if="workflowNotice" class="lims-status-strip p-4">
      <p class="text-sm font-bold text-[var(--ds-text)]">{{ workflowNotice }}</p>
    </section>

    <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
      <main class="min-w-0 space-y-6">
        <section class="ds-panel overflow-hidden">
          <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="ds-kicker">Controlo de execução</p>
              <h2 class="ds-heading mt-2 text-base">Parâmetros na bancada</h2>
              <p class="ds-copy mt-1 text-xs">Estado de entrada, cálculo e bloqueios antes da decisão.</p>
            </div>
            <span class="ds-chip">
              <span
                class="lims-status-dot"
                :class="blockingCalculatedParameters.length ? 'lims-status-dot-hold' : 'lims-status-dot-release'"
              />
              {{ blockingCalculatedParameters.length ? "Entradas em falta" : "Pronto para cálculo" }}
            </span>
          </div>

          <dl class="grid sm:grid-cols-2 xl:grid-cols-4">
            <div
              v-for="card in executionControlCards"
              :key="card.label"
              class="border-b border-[var(--ds-border)] px-5 py-4 sm:odd:border-r xl:border-r xl:last:border-r-0"
            >
              <dt class="ds-table-heading">{{ card.label }}</dt>
              <dd class="ds-heading mt-2 text-xl">{{ card.value }}</dd>
            </div>
          </dl>

          <div v-if="calculationReadiness.length" class="grid border-t border-[var(--ds-border)] lg:grid-cols-2">
            <div class="border-b border-[var(--ds-border)] p-5 lg:border-r lg:border-b-0">
              <h3 class="ds-table-heading">Cálculos prontos</h3>
              <ul v-if="readyCalculatedParameters.length" class="mt-3 space-y-2">
                <li
                  v-for="item in readyCalculatedParameters"
                  :key="'ready-' + item.code"
                  class="flex items-start gap-2 text-xs font-semibold text-[var(--ds-text)]"
                >
                  <span class="lims-status-dot lims-status-dot-release mt-1" />
                  <span>{{ item.code }} / {{ item.name }}</span>
                </li>
              </ul>
              <p v-else class="ds-copy mt-3 text-xs">Nenhum cálculo está pronto.</p>
            </div>
            <div class="p-5">
              <h3 class="ds-table-heading">Cálculos bloqueados</h3>
              <ul v-if="blockingCalculatedParameters.length" class="mt-3 space-y-3">
                <li v-for="item in blockingCalculatedParameters" :key="'blocked-' + item.code">
                  <p class="text-xs font-bold text-[var(--ds-text)]">{{ item.code }} / {{ item.name }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--lims-hold)]">
                    Faltam: {{ item.missingVariables.join(", ") }}
                  </p>
                </li>
              </ul>
              <p v-else class="ds-copy mt-3 text-xs">Sem bloqueios de cálculo.</p>
            </div>
          </div>
        </section>

        <component
          v-if="CurrentComponent"
          :is="CurrentComponent"
          :form="form"
          :record="record"
          :action="action"
          :separated-results="separatedResults"
          @open-calculation="openCalculationModal"
          @submit="submitResults"
        />

        <section class="ds-panel overflow-hidden">
          <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="ds-kicker">Âmbito controlado</p>
              <h2 class="ds-heading mt-2 text-base">Conferência entre perfil, recepção e resultados</h2>
            </div>
            <span class="ds-chip">
              <span
                class="lims-status-dot"
                :class="hasScopeDrift ? 'lims-status-dot-hold' : 'lims-status-dot-release'"
              />
              {{ hasScopeDrift ? "Deriva detectada" : "Âmbito consistente" }}
            </span>
          </div>

          <dl class="grid sm:grid-cols-2 xl:grid-cols-4">
            <div
              v-for="card in scopeSummaryCards"
              :key="card.label"
              class="border-b border-[var(--ds-border)] px-5 py-4 sm:odd:border-r xl:border-r xl:last:border-r-0"
            >
              <dt class="ds-table-heading">{{ card.label }}</dt>
              <dd class="ds-heading mt-2 text-xl">{{ card.value }}</dd>
            </div>
          </dl>

          <div class="grid border-t border-[var(--ds-border)] lg:grid-cols-2">
            <div class="border-b border-[var(--ds-border)] p-5 lg:border-r lg:border-b-0">
              <h3 class="ds-table-heading">Parâmetros em falta</h3>
              <ul v-if="scope_audit?.missing_from_results?.length" class="mt-3 space-y-2">
                <li
                  v-for="parameter in scope_audit.missing_from_results"
                  :key="parameter.id"
                  class="text-xs font-semibold text-[var(--lims-hold)]"
                >
                  {{ parameter.code || "N/D" }} / {{ parameter.name }}
                </li>
              </ul>
              <p v-else class="ds-copy mt-3 text-xs">Todos os parâmetros esperados estão presentes.</p>
            </div>

            <div class="p-5">
              <h3 class="ds-table-heading">Deriva de âmbito</h3>
              <div v-if="hasScopeDrift" class="mt-3 space-y-4">
                <div v-for="bucket in scopeDriftSummary" :key="bucket.label">
                  <p class="text-xs font-bold text-[var(--ds-text)]">{{ bucket.label }}</p>
                  <ul v-if="bucket.items.length" class="mt-2 space-y-1">
                    <li
                      v-for="parameter in bucket.items"
                      :key="bucket.label + '-' + parameter.id"
                      class="text-xs font-semibold text-[var(--lims-hold)]"
                    >
                      {{ parameter.code || "N/D" }} / {{ parameter.name }}
                    </li>
                  </ul>
                </div>
              </div>
              <p v-else class="ds-copy mt-3 text-xs">Recepção, perfil e resultados coincidem.</p>
            </div>
          </div>
        </section>
      </main>

      <aside class="min-w-0 space-y-4 xl:sticky xl:top-4">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <p class="ds-kicker">Rastreabilidade</p>
            <h2 class="ds-heading mt-2 text-sm">{{ workflowOriginLabel }}</h2>
          </div>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div
              v-for="item in sampleEntryMetaCards"
              :key="item.label"
              class="flex items-start justify-between gap-4 px-4 py-3"
            >
              <dt class="text-xs font-bold text-[var(--ds-text-muted)]">{{ item.label }}</dt>
              <dd class="max-w-40 break-words text-right text-xs font-bold text-[var(--ds-text)]">
                {{ item.value }}
              </dd>
            </div>
          </dl>
          <div class="flex flex-wrap gap-2 border-t border-[var(--ds-border)] p-4">
            <Link
              v-if="record?.links?.collection_show_path"
              :href="record.links.collection_show_path"
              class="ds-table-action"
            >
              Ver colheita
            </Link>
            <Link
              v-if="record?.links?.counter_analysis_index"
              :href="record.links.counter_analysis_index"
              class="ds-table-action"
            >
              Contra-análises
            </Link>
            <Link
              v-if="record?.links?.original_analysis_path"
              :href="record.links.original_analysis_path"
              class="ds-table-action"
            >
              Resultado original
            </Link>
          </div>
        </section>

        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <p class="ds-kicker">Condição ISO 17025</p>
            <h2 class="ds-heading mt-2 text-sm">
              {{ conditioningLabels[scope_audit?.conditioning_status] || "Não avaliada" }}
            </h2>
          </div>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div class="flex items-start justify-between gap-4 px-4 py-3">
              <dt class="text-xs font-bold text-[var(--ds-text-muted)]">Embalagem</dt>
              <dd class="text-right text-xs font-bold text-[var(--ds-text)]">
                {{ scope_audit?.packaging_condition || "N/A" }}
              </dd>
            </div>
            <div class="flex items-start justify-between gap-4 px-4 py-3">
              <dt class="text-xs font-bold text-[var(--ds-text-muted)]">Condição térmica</dt>
              <dd class="text-right text-xs font-bold text-[var(--ds-text)]">
                {{ scope_audit?.temperature_condition || "N/A" }}
              </dd>
            </div>
          </dl>
          <div v-if="scope_audit?.integrity_observations || scope_audit?.chain_of_custody_notes" class="space-y-2 border-t border-[var(--ds-border)] p-4">
            <p v-if="scope_audit?.integrity_observations" class="ds-copy text-xs">
              Integridade: {{ scope_audit.integrity_observations }}
            </p>
            <p v-if="scope_audit?.chain_of_custody_notes" class="ds-copy text-xs">
              Cadeia de custódia: {{ scope_audit.chain_of_custody_notes }}
            </p>
          </div>
        </section>

        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <p class="ds-kicker">Folha de trabalho</p>
            <h2 class="ds-heading mt-2 text-sm">
              {{ worksheet_brief?.exists ? worksheet_brief.name : "Sem folha de trabalho" }}
            </h2>
          </div>
          <div class="p-4">
            <template v-if="worksheet_brief?.exists">
              <p class="ds-copy text-xs">Actualizada {{ formatDateTime(worksheet_brief.updated_at) }}</p>
              <Link :href="route('worksheets.show', worksheet_brief.id)" class="ds-button ds-button-secondary mt-4 w-full">
                <DocumentTextIcon class="h-4 w-4" />
                Abrir folha de trabalho
              </Link>
            </template>
            <template v-else>
              <p class="ds-copy text-xs">Nenhuma folha de trabalho foi vinculada a esta análise.</p>
              <button
                v-if="allow_worksheet_draft"
                type="button"
                :disabled="worksheetForm.processing"
                :aria-busy="worksheetForm.processing"
                class="ds-button ds-button-secondary mt-4 w-full"
                @click="createWorksheetDraft"
              >
                <DocumentTextIcon class="h-4 w-4" />
                {{ worksheetForm.processing ? "A preparar..." : "Criar folha de trabalho" }}
              </button>
            </template>
          </div>
        </section>

        <p v-if="worksheetForm.errors.worksheet" class="ds-field-error" role="alert">{{ worksheetForm.errors.worksheet }}</p>
        <Link v-if="worksheetForm.errors.worksheet" :href="route('worksheets.index', { trashed: 'only' })" class="ds-table-action mt-2">
          Consultar o arquivo de folhas de trabalho
        </Link>

        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <p class="ds-kicker">Relatório</p>
            <h2 class="ds-heading mt-2 text-sm">{{ report_studio?.name || "Studio não configurado" }}</h2>
          </div>
          <div class="p-4">
            <p class="ds-copy text-xs">
              {{ report_studio?.description || "Active um modelo para padronizar a emissão final." }}
            </p>
            <div v-if="report_studio" class="mt-3 flex flex-wrap gap-2">
              <span class="ds-chip">{{ report_studio.renderer === "canva" ? "Canva" : "Interno" }}</span>
              <span class="ds-chip">{{ report_studio.theme_preset || "Tema padrão" }}</span>
            </div>
            <div class="mt-4 flex flex-col gap-2">
              <a
                v-if="report_studio?.canva_design_url"
                :href="report_studio.canva_design_url"
                target="_blank"
                rel="noopener noreferrer"
                class="ds-button ds-button-secondary w-full"
              >
                <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                Abrir modelo
              </a>
              <Link
                v-if="$page.props.auth?.user?.roles?.includes?.('admin')"
                :href="route('report-studios.index')"
                class="ds-table-action justify-center"
              >
                Gerir studios
              </Link>
            </div>
          </div>
        </section>

        <section class="lims-status-strip p-4">
          <div class="flex items-start gap-3">
            <BeakerIcon class="h-5 w-5 shrink-0 text-[var(--lims-instrument)]" />
            <div>
              <h2 class="ds-heading text-sm">Etapa actual: {{ workflowLabel }}</h2>
              <p class="ds-copy mt-1 text-xs">
                {{ props.results_summary?.total ?? form.results.length }} parâmetros no fluxo.
              </p>
            </div>
          </div>
        </section>
      </aside>
    </div>

    <CalculationModal
      v-if="showCalculationModal"
      :parameters="calculationParameters"
      :existing-results="existingCalculationData"
      @close="showCalculationModal = false"
      @calculated="handleCalculatedResults"
    />
  </div>
</template>
