<script setup>
import { computed, ref, watch } from "vue";
import {
  CalculatorIcon,
  CheckCircleIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  ScaleIcon,
  VariableIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  parameters: {
    type: Array,
    default: () => [],
  },
  existingResults: {
    type: Object,
    default: () => ({}),
  },
  action: {
    type: String,
    default: "analyze",
  },
});

const emit = defineEmits(["calculatedResults", "close"]);

const selectedParameterCode = ref("");
const calculationInputs = ref({});
const calculationResult = ref(null);
const calculationError = ref("");
const manualOverride = ref(false);
const manualValue = ref("");
const uncertaintyValue = ref("");
const minimumReference = ref("");
const maximumReference = ref("");
const notes = ref("");
const results = ref({});
const overrides = ref({});
const uncertaintyValues = ref({});
const minimumReferences = ref({});
const maximumReferences = ref({});
const notesValues = ref({});
const calculationInProgress = ref(false);

const calculatedParameters = computed(() => {
  return props.parameters.filter((parameter) => parameter.parameter_id?.active !== false);
});

const selectedParameter = computed(() => {
  return calculatedParameters.value.find(
    (parameter) => parameter.parameter_id?.code === selectedParameterCode.value,
  ) || null;
});

const requiredVariables = computed(() => {
  return getCalculationRequirements(selectedParameter.value);
});

const filledInputCount = computed(() => {
  return requiredVariables.value.filter((variable) => hasInputValue(variable)).length;
});

const missingVariables = computed(() => {
  return requiredVariables.value.filter((variable) => !hasInputValue(variable));
});

const nextParameter = computed(() => {
  const currentIndex = calculatedParameters.value.findIndex(
    (parameter) => parameter.parameter_id?.code === selectedParameterCode.value,
  );

  return currentIndex >= 0
    ? calculatedParameters.value[currentIndex + 1] || null
    : null;
});

const canApply = computed(() => {
  if (manualOverride.value) {
    return String(manualValue.value).trim() !== "";
  }

  return Boolean(
    selectedParameter.value &&
      !missingVariables.value.length &&
      calculationResult.value !== null,
  );
});

const referenceStatus = computed(() => {
  if (calculationResult.value === null) {
    return null;
  }

  const value = Number.parseFloat(calculationResult.value);
  const minimum = minimumReference.value === ""
    ? null
    : Number.parseFloat(minimumReference.value);
  const maximum = maximumReference.value === ""
    ? null
    : Number.parseFloat(maximumReference.value);

  if (Number.isNaN(value)) {
    return false;
  }

  if (minimum !== null && value < minimum) {
    return false;
  }

  if (maximum !== null && value > maximum) {
    return false;
  }

  return true;
});

watch(
  () => props.existingResults,
  (newResults) => {
    results.value = { ...(newResults || {}) };

    props.parameters.forEach((parameter) => {
      const code = parameter.parameter_id?.code;
      if (!code) {
        return;
      }

      uncertaintyValues.value[code] = parameter.uncertainty_value || null;
      minimumReferences.value[code] = parameter.min_ref_value || null;
      maximumReferences.value[code] = parameter.max_ref_value || null;
      notesValues.value[code] =
        parameter.insertion_notes ||
        parameter.verification_notes ||
        parameter.approval_notes ||
        null;
      overrides.value[code] = Boolean(parameter.manual_override);
    });

    if (!selectedParameterCode.value && calculatedParameters.value.length === 1) {
      selectedParameterCode.value =
        calculatedParameters.value[0].parameter_id?.code || "";
      initializeSelectedParameter();
    }
  },
  { immediate: true },
);

watch(
  calculationInputs,
  () => {
    if (selectedParameter.value && !manualOverride.value) {
      calculateParameter();
    }
  },
  { deep: true },
);

function hasInputValue(variable) {
  const value = calculationInputs.value[variable];
  return value !== undefined && value !== null && value !== "";
}

function getCalculationRequirements(parameter) {
  return parameter?.formula?.variables?.map((variable) => variable.name) || [];
}

function initializeSelectedParameter() {
  if (!selectedParameter.value) {
    resetFields(false);
    return;
  }

  const parameterCode = selectedParameter.value.parameter_id.code;
  const nextInputs = {};

  requiredVariables.value.forEach((variable) => {
    nextInputs[variable] =
      calculationInputs.value[variable] ?? results.value[variable] ?? "";
  });
  calculationInputs.value = nextInputs;

  calculationResult.value = results.value[parameterCode] ?? null;
  uncertaintyValue.value = uncertaintyValues.value[parameterCode] || "";
  minimumReference.value = minimumReferences.value[parameterCode] || "";
  maximumReference.value = maximumReferences.value[parameterCode] || "";
  notes.value = notesValues.value[parameterCode] || "";
  manualOverride.value = Boolean(overrides.value[parameterCode]);
  manualValue.value = manualOverride.value
    ? results.value[parameterCode] || ""
    : "";
  calculationError.value = "";

  if (!missingVariables.value.length && !manualOverride.value) {
    calculateParameter();
  }
}

function toggleManualOverride() {
  if (manualOverride.value) {
    manualValue.value = results.value[selectedParameterCode.value] || "";
    return;
  }

  manualValue.value = "";
  calculateParameter();
}

async function calculateParameter() {
  if (!selectedParameter.value || manualOverride.value) {
    calculationResult.value = null;
    calculationError.value = "";
    return;
  }

  if (missingVariables.value.length) {
    calculationResult.value = null;
    calculationError.value = "";
    return;
  }

  calculationInProgress.value = true;
  calculationError.value = "";

  try {
    const variables = Object.fromEntries(
      requiredVariables.value.map((variable) => {
        const value = Number.parseFloat(calculationInputs.value[variable]);
        return [variable, Number.isNaN(value) ? 0 : value];
      }),
    );
    const mathContext = {
      sqrt: Math.sqrt,
      log: Math.log,
      log10: Math.log10,
      exp: Math.exp,
      abs: Math.abs,
      round: Math.round,
      ceil: Math.ceil,
      floor: Math.floor,
      max: Math.max,
      min: Math.min,
      pow: Math.pow,
      PI: Math.PI,
      E: Math.E,
    };
    const context = { ...variables, Math: mathContext };
    const evaluateFormula = new Function(
      ...Object.keys(context),
      "return " + selectedParameter.value.formula.expression,
    );
    const numericValue = evaluateFormula(...Object.values(context));

    if (Number.isNaN(numericValue) || !Number.isFinite(numericValue)) {
      throw new Error("Resultado de cálculo inválido");
    }

    const decimalPlaces = selectedParameter.value.formula.decimal_places || 2;
    calculationResult.value = Number.parseFloat(numericValue).toFixed(decimalPlaces);
  } catch {
    calculationError.value =
      "Não foi possível executar a fórmula. Reveja as variáveis de entrada.";
    calculationResult.value = null;
  } finally {
    calculationInProgress.value = false;
  }
}

function applyCalculation() {
  if (!canApply.value || !selectedParameter.value) {
    return;
  }

  const parameterCode = selectedParameterCode.value;
  const finalValue = manualOverride.value
    ? manualValue.value
    : calculationResult.value;
  const metadata = {
    inputs: Object.fromEntries(
      requiredVariables.value.map((variable) => [
        variable,
        calculationInputs.value[variable],
      ]),
    ),
    formula: {
      id: selectedParameter.value.formula?.id,
      expression: selectedParameter.value.formula?.expression,
      name: selectedParameter.value.formula?.name,
    },
    calculated_at: new Date().toISOString(),
    calculation_method: manualOverride.value ? "manual" : "automated",
    manual_override: manualOverride.value,
  };

  results.value[parameterCode] = finalValue;
  Object.entries(calculationInputs.value).forEach(([key, value]) => {
    if (value !== "") {
      results.value[key] = value;
    }
  });

  uncertaintyValues.value[parameterCode] = uncertaintyValue.value || null;
  minimumReferences.value[parameterCode] = minimumReference.value || null;
  maximumReferences.value[parameterCode] = maximumReference.value || null;
  notesValues.value[parameterCode] = notes.value || null;
  overrides.value[parameterCode] = manualOverride.value;

  emit("calculatedResults", {
    results: {
      [parameterCode]: finalValue,
      [parameterCode + "_uncertainty_value"]: uncertaintyValue.value || null,
      [parameterCode + "_min_ref_value"]: minimumReference.value || null,
      [parameterCode + "_max_ref_value"]: maximumReference.value || null,
      [parameterCode + "_insertion_notes"]: notes.value || null,
      ...Object.fromEntries(
        Object.entries(calculationInputs.value).filter(([, value]) => value !== ""),
      ),
    },
    overrides: {
      [parameterCode]: manualOverride.value,
    },
    metadata: {
      [parameterCode]: metadata,
    },
  });

  if (nextParameter.value) {
    selectedParameterCode.value = nextParameter.value.parameter_id?.code || "";
    initializeSelectedParameter();
    return;
  }

  emit("close");
}

function resetFields(clearSelection = true) {
  if (clearSelection) {
    selectedParameterCode.value = "";
  }
  calculationInputs.value = {};
  calculationResult.value = null;
  calculationError.value = "";
  manualOverride.value = false;
  manualValue.value = "";
  uncertaintyValue.value = "";
  minimumReference.value = "";
  maximumReference.value = "";
  notes.value = "";
}

function selectNextParameter() {
  if (!nextParameter.value) {
    return;
  }

  selectedParameterCode.value = nextParameter.value.parameter_id?.code || "";
  initializeSelectedParameter();
}
</script>

<template>
  <div class="min-w-0 space-y-6">
    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-4 border-b border-[var(--ds-border)] p-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 flex-1">
          <label class="ds-field-label" for="calculated-parameter">Parâmetro calculado</label>
          <BaseSelect
            id="calculated-parameter"
            v-model="selectedParameterCode"
            class="ds-field mt-2"
            @change="initializeSelectedParameter"
          >
            <option value="">Seleccione um parâmetro</option>
            <option
              v-for="parameter in calculatedParameters"
              :key="parameter.parameter_id?.code"
              :value="parameter.parameter_id?.code"
            >
              {{ parameter.parameter_id?.code }} - {{ parameter.parameter_id?.name }}
            </option>
          </BaseSelect>
        </div>
        <span class="ds-chip">
          <CalculatorIcon class="h-4 w-4" />
          {{ filledInputCount }}/{{ requiredVariables.length }} variáveis
        </span>
      </div>

      <div v-if="selectedParameter" class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-3">
        <div class="bg-[var(--ds-panel-raised)] p-4">
          <p class="ds-table-heading">Código</p>
          <p class="ds-heading mt-2 font-mono text-sm">{{ selectedParameter.parameter_id?.code }}</p>
        </div>
        <div class="bg-[var(--ds-panel-raised)] p-4 sm:col-span-2">
          <p class="ds-table-heading">Fórmula controlada</p>
          <code class="mt-2 block break-all font-mono text-xs font-bold text-[var(--ds-text)]">
            {{ selectedParameter.formula?.expression || "Fórmula não configurada" }}
          </code>
        </div>
      </div>
    </section>

    <template v-if="selectedParameter">
      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <p class="ds-kicker">Variáveis de entrada</p>
          <h2 class="ds-heading mt-2 text-base">Valores usados no cálculo</h2>
        </div>
        <div
          v-if="requiredVariables.length"
          class="grid gap-4 p-5 sm:grid-cols-2"
        >
          <div v-for="variable in requiredVariables" :key="variable" class="ds-field-group">
            <label class="ds-field-label" :for="'calc-variable-' + variable">
              {{ variable }}
            </label>
            <div class="relative mt-2">
              <VariableIcon class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput
                :id="'calc-variable-' + variable"
                v-model="calculationInputs[variable]"
                class="ds-field pl-9"
                inputmode="decimal"
                placeholder="Introduza o valor"
              />
              <CheckCircleIcon
                v-if="hasInputValue(variable)"
                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[var(--lims-release)]"
              />
            </div>
            <p
              v-if="results[variable] && calculationInputs[variable] !== results[variable]"
              class="ds-field-hint"
            >
              Valor anterior: {{ results[variable] }}
            </p>
          </div>
        </div>
        <div v-else class="p-5">
          <p class="ds-copy text-sm">A fórmula não declara variáveis de entrada.</p>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <p class="ds-kicker">Metadados do resultado</p>
          <h2 class="ds-heading mt-2 text-base">Incerteza, limites e observações</h2>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-2">
          <div class="ds-field-group">
            <label class="ds-field-label" for="calculation-uncertainty">Incerteza</label>
            <div class="relative mt-2">
              <ScaleIcon class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput
                id="calculation-uncertainty"
                v-model="uncertaintyValue"
                class="ds-field pl-9"
                inputmode="decimal"
                placeholder="Ex.: 0.1"
              />
            </div>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label" for="calculation-minimum">Referência mínima</label>
            <BaseInput
              id="calculation-minimum"
              v-model="minimumReference"
              class="ds-field mt-2"
              inputmode="decimal"
            />
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label" for="calculation-maximum">Referência máxima</label>
            <BaseInput
              id="calculation-maximum"
              v-model="maximumReference"
              class="ds-field mt-2"
              inputmode="decimal"
            />
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label" for="calculation-notes">Observações</label>
            <textarea
              id="calculation-notes"
              v-model="notes"
              class="ds-field mt-2 min-h-20"
              placeholder="Documente o contexto do cálculo."
            />
          </div>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-kicker">Resultado final</p>
            <h2 class="ds-heading mt-2 text-base">Revisão antes de aplicar</h2>
          </div>
          <label class="flex items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]">
            <CheckboxInput
              v-model="manualOverride"
              type="checkbox"
              class="h-4 w-4 rounded border-[var(--ds-border-strong)] text-[rgb(var(--primary-600-rgb))] focus:ring-[rgb(var(--primary-500-rgb))]"
              @change="toggleManualOverride"
            />
            Substituição manual
          </label>
        </div>

        <div class="p-5">
          <div v-if="manualOverride" class="ds-field-group">
            <label class="ds-field-label" for="manual-calculation-value">Valor manual</label>
            <BaseInput
              id="manual-calculation-value"
              v-model="manualValue"
              class="ds-field mt-2"
              placeholder="Introduza o valor justificado"
            />
          </div>

          <div v-else-if="calculationResult !== null" class="lims-status-strip p-5 text-center">
            <p class="ds-table-heading">Resultado calculado</p>
            <p class="ds-heading mt-3 text-3xl">
              {{ calculationResult }}
              <span class="text-sm text-[var(--ds-text-muted)]">
                {{ selectedParameter.formula?.output_unit }}
              </span>
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
              <span v-if="uncertaintyValue" class="ds-chip">± {{ uncertaintyValue }}</span>
              <span
                v-if="referenceStatus !== null"
                class="ds-chip"
              >
                <span
                  class="lims-status-dot"
                  :class="referenceStatus ? 'lims-status-dot-release' : 'lims-status-dot-critical'"
                />
                {{ referenceStatus ? "Dentro da referência" : "Fora da referência" }}
              </span>
            </div>
          </div>

          <div v-if="calculationError" class="mt-4 flex items-start gap-3 text-[var(--lims-critical)]">
            <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
            <p class="text-xs font-bold">{{ calculationError }}</p>
          </div>

          <div
            v-if="missingVariables.length && !manualOverride"
            class="mt-4 flex items-start gap-3 text-[var(--lims-hold)]"
          >
            <InformationCircleIcon class="h-5 w-5 shrink-0" />
            <p class="text-xs font-bold">
              Variáveis em falta: {{ missingVariables.join(", ") }}
            </p>
          </div>
        </div>

        <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-between">
          <button type="button" class="ds-button ds-button-secondary" @click="resetFields">
            Limpar
          </button>
          <button
            type="button"
            class="ds-button ds-button-primary"
            :disabled="!canApply || calculationInProgress"
            @click="applyCalculation"
          >
            <CheckIcon class="h-4 w-4" />
            {{ manualOverride ? "Aplicar valor manual" : "Aplicar cálculo" }}
          </button>
        </footer>
      </section>

      <section v-if="nextParameter" class="lims-status-strip p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-table-heading">Próximo parâmetro</p>
            <p class="ds-copy mt-1 text-xs">
              {{ nextParameter.parameter_id?.code }} - {{ nextParameter.parameter_id?.name }}
            </p>
          </div>
          <button type="button" class="ds-button ds-button-secondary" @click="selectNextParameter">
            Continuar
          </button>
        </div>
      </section>
    </template>

    <footer class="flex justify-end border-t border-[var(--ds-border)] pt-4">
      <button type="button" class="ds-button ds-button-secondary" @click="emit('close')">
        <XMarkIcon class="h-4 w-4" />
        Fechar
      </button>
    </footer>
  </div>
</template>
