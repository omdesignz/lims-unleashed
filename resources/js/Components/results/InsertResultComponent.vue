<script setup>
import CalculationModal from "@/Components/results/CalculationModal.vue";
import IndividualResultEntry from "@/Components/results/IndividualResultEntry.vue";
import ResultItem from "@/Components/results/ResultItem.vue";
import { ResultsDataService } from "@/Services/ResultsDataService.js";
import { computed, ref } from "vue";
import {
  BeakerIcon,
  CalculatorIcon,
  CheckCircleIcon,
  CheckIcon,
  ClockIcon,
  DocumentIcon,
  DocumentPlusIcon,
  DocumentTextIcon,
  InformationCircleIcon,
  PencilIcon,
  PlusCircleIcon,
  VariableIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  record: {
    type: Object,
    default: () => ({}),
  },
  action: {
    type: String,
    default: "analyze",
  },
  separatedResults: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["open-calculation", "submit", "update-results"]);

const workflowMode = ref("batch");
const showIndividualEntry = ref(false);
const selectedIndividualParameter = ref(null);
const showCalculationModal = ref(false);
const calculationParameters = ref([]);
const existingCalculationData = ref({});

const groupedResults = computed(() => ({
  calculated: props.separatedResults?.calculatedParams || [],
  inputVariables: props.separatedResults?.inputVariables || [],
  manual: props.separatedResults?.manualParams || [],
}));

const totalResults = computed(() => props.form.results?.length || 0);

const insertedCount = computed(() => {
  return (props.form.results || []).filter((result) => {
    const value = getResultDisplayValue(result);
    return value !== null && value !== undefined && String(value).trim() !== "";
  }).length;
});

const pendingCount = computed(() => totalResults.value - insertedCount.value);

const calculatedCount = computed(() => {
  return (props.form.results || []).filter((result) => result.is_calculated).length;
});

const progressPercent = computed(() => {
  if (!totalResults.value) {
    return 0;
  }

  return Math.round((insertedCount.value / totalResults.value) * 100);
});

const individualEntryCount = computed(() => {
  return (props.form.results || []).filter(
    (result) => result.insertion_method === "individual",
  ).length;
});

const hasIndividualEntries = computed(() => individualEntryCount.value > 0);

const hasCalculatedParameters = computed(() => {
  return props.form.results?.some(
    (parameter) => parameter.requires_calculation && parameter.active,
  ) || false;
});

const insertionMetrics = computed(() => [
  { label: "Total", value: totalResults.value, dot: "lims-status-dot-neutral" },
  { label: "Inseridos", value: insertedCount.value, dot: "lims-status-dot-release" },
  { label: "Pendentes", value: pendingCount.value, dot: "lims-status-dot-hold" },
  { label: "Calculados", value: calculatedCount.value, dot: "lims-status-dot-instrument" },
]);

function getResultDisplayValue(result) {
  return ResultsDataService.getResultValue(result, props.action);
}

function hasResultDisplayValue(result) {
  return ResultsDataService.hasResultValue(getResultDisplayValue(result));
}

function getFormattedResultDisplayValue(result) {
  return ResultsDataService.formatResultValue(getResultDisplayValue(result), result);
}

function getCalculationReadiness(result) {
  return ResultsDataService.getCalculationReadiness(
    result,
    props.form.results,
    props.action,
  );
}

function getResultKey(result, index) {
  return result.result_id || result.id || "temp-" + index;
}

function switchWorkflowMode(mode) {
  workflowMode.value = mode;
}

function openIndividualEntryForResult(result) {
  selectedIndividualParameter.value = result;
  showIndividualEntry.value = true;
}

function handleIndividualResultSaved(resultData) {
  const index = props.form.results.findIndex((result) => {
    return (
      result.result_id === resultData.result_id ||
      (result.parameter_id?.value === resultData.parameter_id?.value && !result.result_id)
    );
  });

  const updatedResult = {
    ...(index >= 0 ? props.form.results[index] : {}),
    ...resultData,
    insertion_method: "individual",
  };

  if (index >= 0) {
    props.form.results[index] = updatedResult;
  } else {
    props.form.results.push(updatedResult);
  }

  showIndividualEntry.value = false;
  selectedIndividualParameter.value = null;
  emit("update-results");
}

function handleOpenCalculationForParameter(parameter) {
  calculationParameters.value = [parameter];
  existingCalculationData.value = ResultsDataService.prepareForSingleCalculation(
    parameter,
    props.form.results,
  );
  showCalculationModal.value = true;
  showIndividualEntry.value = false;
}

function handleCalculatedResults(calculationPayload) {
  props.form.results = ResultsDataService.mergeCalculationResults(
    props.form.results,
    calculationPayload,
    props.action,
  );
  showCalculationModal.value = false;
  props.form.results = [...props.form.results];

  if (selectedIndividualParameter.value) {
    showIndividualEntry.value = true;
  }
}

function submitResults() {
  emit("submit");
}

function addResult() {
  props.form.results.push({
    parameter_id: "",
    unit_id: "",
    inserted_value: "",
    uncertainty_value: "",
    active: true,
    requires_calculation: false,
  });
}

function removeResult(index) {
  props.form.results.splice(index, 1);
}
</script>

<template>
  <div class="min-w-0 space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--lims-instrument)]">
            <BeakerIcon class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="ds-kicker">Entrada de dados</p>
            <h2 class="ds-heading mt-2 text-base">
              {{ $t("gestlab.general.labels.results.page_title_insert") }}
            </h2>
            <p class="ds-copy mt-1 truncate text-xs">
              {{ $t("gestlab.general.labels.results.code_id") }} {{ record?.code || "N/D" }}
            </p>
          </div>
        </div>

        <div class="inline-flex rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-1">
          <button
            type="button"
            :class="[
              'flex items-center gap-2 rounded-md px-3 py-2 text-xs font-bold',
              workflowMode === 'batch'
                ? 'bg-[var(--ds-panel-raised)] text-[var(--ds-text)] ring-1 ring-[var(--ds-border)]'
                : 'text-[var(--ds-text-muted)]',
            ]"
            @click="switchWorkflowMode('batch')"
          >
            <DocumentTextIcon class="h-4 w-4" />
            Lote
          </button>
          <button
            type="button"
            :class="[
              'flex items-center gap-2 rounded-md px-3 py-2 text-xs font-bold',
              workflowMode === 'individual'
                ? 'bg-[var(--ds-panel-raised)] text-[var(--ds-text)] ring-1 ring-[var(--ds-border)]'
                : 'text-[var(--ds-text-muted)]',
            ]"
            @click="switchWorkflowMode('individual')"
          >
            <DocumentPlusIcon class="h-4 w-4" />
            Individual
          </button>
        </div>
      </div>

      <dl class="grid sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="metric in insertionMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 sm:odd:border-r xl:border-r xl:last:border-r-0"
        >
          <dt class="flex items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]">
            <span class="lims-status-dot" :class="metric.dot" />
            {{ metric.label }}
          </dt>
          <dd class="ds-heading mt-2 text-xl">{{ metric.value }}</dd>
        </div>
      </dl>

      <div class="border-t border-[var(--ds-border)] px-5 py-4">
        <div class="flex items-center justify-between gap-4 text-xs font-bold text-[var(--ds-text-muted)]">
          <span>Progresso da inserção</span>
          <span>{{ progressPercent }}%</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-[var(--ds-panel-subtle)]">
          <div
            class="h-full rounded-full bg-[rgb(var(--primary-600-rgb))] transition-[width] duration-300"
            :style="{ width: progressPercent + '%' }"
          />
        </div>
      </div>
    </section>

    <section v-if="workflowMode === 'individual'" class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="ds-kicker">Modo individual</p>
          <h2 class="ds-heading mt-2 text-base">Seleccione um parâmetro</h2>
          <p class="ds-copy mt-1 text-xs">Abra apenas o resultado que pretende inserir ou corrigir.</p>
        </div>
        <span v-if="hasIndividualEntries" class="ds-chip">
          <CheckCircleIcon class="h-4 w-4" />
          {{ individualEntryCount }} individuais
        </span>
      </div>

      <div v-if="form.results?.length" class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 lg:grid-cols-3">
        <button
          v-for="(result, index) in form.results"
          :key="getResultKey(result, index)"
          type="button"
          class="group min-w-0 bg-[var(--ds-panel-raised)] p-4 text-left hover:bg-[var(--ds-panel-subtle)]"
          @click="openIndividualEntryForResult(result)"
        >
          <span class="flex items-start justify-between gap-3">
            <span class="min-w-0">
              <span class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm font-bold text-[var(--ds-text)]">
                  {{ result.parameter_id?.code || "N/D" }}
                </span>
                <span v-if="result.requires_calculation" class="ds-chip">
                  <CalculatorIcon class="h-3.5 w-3.5" />
                  Calculado
                </span>
              </span>
              <span class="mt-1 block truncate text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ result.parameter_id?.name || "Parâmetro sem nome" }}
              </span>
            </span>
            <span
              class="lims-status-dot mt-1"
              :class="hasResultDisplayValue(result) ? 'lims-status-dot-release' : 'lims-status-dot-hold'"
            />
          </span>

          <span v-if="result.requires_calculation" class="mt-3 block text-xs font-semibold">
            <span :class="getCalculationReadiness(result).ready ? 'text-[var(--lims-release)]' : 'text-[var(--lims-hold)]'">
              {{ getCalculationReadiness(result).ready ? "Pronto para cálculo" : "Entradas em falta" }}
            </span>
          </span>

          <span v-if="hasResultDisplayValue(result)" class="ds-heading mt-4 block text-lg">
            {{ getFormattedResultDisplayValue(result) }}
            <span class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ result.unit_label }}</span>
          </span>
          <span v-else class="mt-4 flex items-center gap-2 text-xs font-bold text-[var(--lims-hold)]">
            <ClockIcon class="h-4 w-4" />
            Pendente
          </span>

          <span v-if="result.min_ref_value || result.max_ref_value" class="mt-3 block border-t border-[var(--ds-border)] pt-3 text-xs font-semibold text-[var(--ds-text-muted)]">
            Referência:
            <template v-if="result.min_ref_value && result.max_ref_value">
              {{ result.min_ref_value }} - {{ result.max_ref_value }}
            </template>
            <template v-else-if="result.min_ref_value">≥ {{ result.min_ref_value }}</template>
            <template v-else>≤ {{ result.max_ref_value }}</template>
            {{ result.unit_label }}
          </span>
        </button>
      </div>
      <div v-else class="p-10 text-center">
        <DocumentIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <p class="ds-heading mt-3 text-sm">Sem parâmetros disponíveis</p>
      </div>
    </section>

    <div v-else class="space-y-6">
      <section v-if="groupedResults.calculated.length" class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-kicker">Fórmulas controladas</p>
            <h2 class="ds-heading mt-2 text-base">
              {{ $t("gestlab.general.labels.results.calculated_params") }}
            </h2>
          </div>
          <button type="button" class="ds-button ds-button-secondary" @click="emit('open-calculation')">
            <CalculatorIcon class="h-4 w-4" />
            {{ $t("gestlab.general.labels.results.open_calculator") }}
          </button>
        </div>
        <ul class="divide-y divide-[var(--ds-border)]">
          <li
            v-for="result in groupedResults.calculated"
            :key="result.id || result.parameter_id?.code"
            class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <p class="font-mono text-sm font-bold text-[var(--ds-text)]">{{ result.parameter_id?.code }}</p>
              <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ result.parameter_id?.name }}</p>
            </div>
            <div class="text-left sm:text-right">
              <p v-if="hasResultDisplayValue(result)" class="text-sm font-bold text-[var(--lims-release)]">
                {{ getFormattedResultDisplayValue(result) }} {{ result.unit_id?.code }}
              </p>
              <p v-else class="text-xs font-bold text-[var(--lims-hold)]">
                Aguardando {{ getCalculationReadiness(result).missingVariables.join(", ") }}
              </p>
            </div>
          </li>
        </ul>
      </section>

      <section v-if="groupedResults.inputVariables.length" class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <p class="ds-kicker">Dados de entrada</p>
          <h2 class="ds-heading mt-2 flex items-center gap-2 text-base">
            <VariableIcon class="h-5 w-5 text-[var(--lims-instrument)]" />
            {{ $t("gestlab.general.labels.results.input_variables") }}
          </h2>
        </div>
        <div class="space-y-4 p-5">
          <ResultItem
            v-for="(result, index) in groupedResults.inputVariables"
            :key="'input-' + index"
            :result="result"
            :index="index"
            :form="form"
            :record="record"
            :is-input-variable="true"
            @remove="removeResult"
          />
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-kicker">Entrada manual</p>
            <h2 class="ds-heading mt-2 flex items-center gap-2 text-base">
              <PencilIcon class="h-5 w-5 text-[var(--lims-instrument)]" />
              {{ $t("gestlab.general.labels.results.manual_params") }}
            </h2>
          </div>
          <button type="button" class="ds-button ds-button-secondary" @click="addResult">
            <PlusCircleIcon class="h-4 w-4" />
            {{ $t("gestlab.general.labels.results.add_manual_param") }}
          </button>
        </div>

        <div v-if="groupedResults.manual.length" class="space-y-4 p-5">
          <ResultItem
            v-for="(result, index) in groupedResults.manual"
            :key="'manual-' + index"
            :result="result"
            :index="form.results.findIndex((item) => item.id === result.id || item.parameter_id?.code === result.parameter_id?.code)"
            :form="form"
            :record="record"
            :is-input-variable="false"
            @remove="removeResult"
          />
        </div>
        <div v-else class="p-10 text-center">
          <DocumentIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-3 text-sm">{{ $t("gestlab.general.labels.results.empty_state.title") }}</h3>
          <p class="ds-copy mt-1 text-xs">{{ $t("gestlab.general.labels.results.empty_state.manual_params") }}</p>
        </div>
      </section>

      <section class="lims-status-strip p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-3">
            <InformationCircleIcon class="h-5 w-5 shrink-0 text-[var(--lims-instrument)]" />
            <div>
              <h2 class="ds-heading text-sm">Precisa de uma entrada pontual?</h2>
              <p class="ds-copy mt-1 text-xs">Alterne para o modo individual sem perder os valores já lançados.</p>
            </div>
          </div>
          <button type="button" class="ds-button ds-button-secondary" @click="switchWorkflowMode('individual')">
            <DocumentPlusIcon class="h-4 w-4" />
            Modo individual
          </button>
        </div>
      </section>
    </div>

    <footer class="ds-command-toolbar sticky bottom-4 z-10 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="ds-table-heading">Prontidão de submissão</p>
        <p class="ds-copy mt-1 text-xs">
          {{ insertedCount }} de {{ totalResults }} parâmetros com valor.
        </p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row">
        <button
          v-if="workflowMode === 'batch' && hasCalculatedParameters"
          type="button"
          class="ds-button ds-button-secondary"
          @click="emit('open-calculation')"
        >
          <CalculatorIcon class="h-4 w-4" />
          {{ $t("gestlab.general.labels.results.recalculate") }}
        </button>
        <button
          type="button"
          class="ds-button ds-button-primary"
          :disabled="form.processing || (workflowMode === 'individual' && insertedCount === 0)"
          @click="submitResults"
        >
          <CheckIcon class="h-4 w-4" />
          <span v-if="form.processing">{{ $t("gestlab.general.buttons.processing") }}</span>
          <span v-else-if="workflowMode === 'individual'">Finalizar ({{ insertedCount }}/{{ totalResults }})</span>
          <span v-else>Inserir resultados</span>
        </button>
      </div>
    </footer>

    <IndividualResultEntry
      v-if="showIndividualEntry"
      :sample-id="record?.sample_id?.value"
      :parameters="form.results"
      :action="action"
      :existing-results="selectedIndividualParameter"
      @close="showIndividualEntry = false"
      @saved="handleIndividualResultSaved"
      @open-calculation="handleOpenCalculationForParameter"
    />

    <CalculationModal
      v-if="showCalculationModal"
      :sample-id="record?.sample_id?.value"
      :parameters="calculationParameters"
      :existing-results="existingCalculationData"
      :action="action"
      @close="showCalculationModal = false"
      @calculated="handleCalculatedResults"
    />
  </div>
</template>
