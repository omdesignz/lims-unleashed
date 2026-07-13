<script setup>
import Modal from "@/Components/Modal.vue";
import { ResultsDataService } from "@/Services/ResultsDataService.js";
import { computed, ref, watch } from "vue";
import {
  CalculatorIcon,
  CheckIcon,
  ScaleIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  sampleId: {
    type: Number,
    default: null,
  },
  parameters: {
    type: Array,
    default: () => [],
  },
  action: {
    type: String,
    default: "analyze",
  },
  existingResults: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close", "saved", "open-calculation"]);

const selectedParameterId = ref("");
const resultValue = ref("");
const uncertaintyValue = ref("");
const notes = ref("");

const actionText = computed(() => {
  const labels = {
    analyze: "Inserção",
    verify: "Verificação",
    approve: "Aprovação",
  };

  return labels[props.action] || "Edição";
});

const valueLabel = computed(() => {
  const labels = {
    analyze: "Resultado",
    verify: "Valor verificado",
    approve: "Valor aprovado",
  };

  return labels[props.action] || "Valor";
});

const saveButtonText = computed(() => {
  const labels = {
    analyze: "Inserir resultado",
    verify: "Verificar resultado",
    approve: "Aprovar resultado",
  };

  return labels[props.action] || "Guardar";
});

const filteredParameters = computed(() => {
  return props.parameters.filter((parameter) => {
    if (props.action === "analyze") {
      return (
        !ResultsDataService.hasResultValue(parameter.inserted_value) ||
        parameter.requires_calculation ||
        getParameterUniqueId(parameter) === selectedParameterId.value
      );
    }

    if (props.action === "verify") {
      return (
        ResultsDataService.hasResultValue(parameter.inserted_value) &&
        !ResultsDataService.hasResultValue(parameter.verified_value)
      );
    }

    if (props.action === "approve") {
      return (
        ResultsDataService.hasResultValue(parameter.verified_value) &&
        !ResultsDataService.hasResultValue(parameter.approved_value)
      );
    }

    return true;
  });
});

const selectedParameter = computed(() => {
  return props.parameters.find(
    (parameter) => getParameterUniqueId(parameter) === selectedParameterId.value,
  ) || null;
});

const selectedParameterIsQualitative = computed(() => {
  return ResultsDataService.isQualitativeResult(selectedParameter.value);
});

const selectedParameterQualitativeOptions = computed(() => {
  return ResultsDataService.getQualitativeOptions(selectedParameter.value);
});

const formattedResultValue = computed(() => {
  return ResultsDataService.formatResultValue(resultValue.value, selectedParameter.value);
});

const displayFormatLabel = computed(() => {
  return ResultsDataService.getDisplayFormat(selectedParameter.value) === "scientific"
    ? "Notação normal"
    : "Notação científica";
});

const canSave = computed(() => {
  if (!selectedParameterId.value) {
    return false;
  }

  if (selectedParameter.value?.requires_calculation) {
    return true;
  }

  return String(resultValue.value).trim() !== "";
});

watch(
  () => props.existingResults,
  (result) => {
    if (result) {
      selectedParameterId.value = getParameterUniqueId(result);
    }
  },
  { immediate: true },
);

watch(
  selectedParameter,
  (parameter) => {
    if (!parameter) {
      resultValue.value = "";
      uncertaintyValue.value = "";
      notes.value = "";
      return;
    }

    if (props.action === "analyze") {
      resultValue.value = parameter.inserted_value ?? "";
      notes.value = parameter.insertion_notes || "";
    } else if (props.action === "verify") {
      resultValue.value = parameter.verified_value ?? parameter.inserted_value ?? "";
      notes.value = parameter.verification_notes || "";
    } else {
      resultValue.value = parameter.approved_value ?? parameter.verified_value ?? "";
      notes.value = parameter.approval_notes || "";
    }

    uncertaintyValue.value = parameter.uncertainty_value || "";
  },
  { immediate: true },
);

function getParameterUniqueId(parameter) {
  return String(
    parameter?.result_id ||
      parameter?.id ||
      parameter?.parameter_id?.value ||
      parameter?.parameter_id?.code ||
      "",
  );
}

function applyQualitativeOption(value) {
  resultValue.value = value;
}

function toggleDisplayFormat() {
  if (!selectedParameter.value) {
    return;
  }

  ResultsDataService.setDisplayFormat(
    selectedParameter.value,
    ResultsDataService.getDisplayFormat(selectedParameter.value) === "scientific"
      ? "standard"
      : "scientific",
  );
}

function openCalculationForParameter() {
  if (selectedParameter.value) {
    emit("open-calculation", selectedParameter.value);
  }
}

function saveIndividualResult() {
  if (!selectedParameter.value || !canSave.value) {
    return;
  }

  const parameter = selectedParameter.value;
  const resultData = {
    ...parameter,
    result_id: parameter.result_id,
    sample_id: props.sampleId,
    parameter_id:
      parameter.parameter_id?.value ||
      parameter.parameter_id?.id ||
      parameter.parameter_id,
    parameter_label: parameter.parameter_label || parameter.parameter_id?.name,
    code_id: parameter.code_id?.value || parameter.code_id,
    product_id: parameter.product_id?.value || parameter.product_id,
    unit_id: parameter.unit_id?.value || parameter.unit_id,
    type_id: parameter.type_id?.value || parameter.type_id,
    result_is_qualitative: ResultsDataService.isQualitativeResult(parameter),
    result_options: ResultsDataService.getQualitativeOptions(parameter),
    display_format: ResultsDataService.getDisplayFormat(parameter),
    extra_data: {
      ...(parameter.extra_data || {}),
      display_format: ResultsDataService.getDisplayFormat(parameter),
    },
    uncertainty_value: uncertaintyValue.value || null,
    insertion_method: "individual",
  };

  if (props.action === "analyze") {
    Object.assign(resultData, {
      inserted_value: resultValue.value,
      inserted_date: new Date().toISOString(),
      insertion_notes: notes.value || null,
    });
  } else if (props.action === "verify") {
    Object.assign(resultData, {
      verified_value: resultValue.value,
      verified_date: new Date().toISOString(),
      verification_notes: notes.value || null,
    });
  } else {
    Object.assign(resultData, {
      approved_value: resultValue.value,
      approved_date: new Date().toISOString(),
      approval_notes: notes.value || null,
    });
  }

  emit("saved", resultData);
  emit("close");
}
</script>

<template>
  <Modal :show="true" max-width="2xl" @close="emit('close')">
    <div class="min-w-0">
      <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">Resultado individual</p>
          <h2 class="ds-heading mt-2 text-lg">{{ actionText }} individual</h2>
          <p class="ds-copy mt-1 text-xs">Trabalhe num único parâmetro sem perder o contexto da amostra.</p>
        </div>
        <button type="button" class="ds-icon-button" title="Fechar" @click="emit('close')">
          <XMarkIcon class="h-5 w-5" />
          <span class="sr-only">Fechar</span>
        </button>
      </header>

      <div class="max-h-[75vh] space-y-6 overflow-y-auto p-5 sm:p-6">
        <div class="ds-field-group">
          <label class="ds-field-label" for="individual-parameter">Parâmetro</label>
          <BaseSelect id="individual-parameter" v-model="selectedParameterId" class="ds-field mt-2">
            <option value="">Selecione um parâmetro</option>
            <option
              v-for="parameter in filteredParameters"
              :key="getParameterUniqueId(parameter)"
              :value="getParameterUniqueId(parameter)"
            >
              {{ parameter.parameter_id?.code || "N/A" }} - {{ parameter.parameter_id?.name || "Sem nome" }}
            </option>
          </BaseSelect>
        </div>

        <template v-if="selectedParameter">
          <section class="ds-command-surface overflow-hidden">
            <div class="flex items-start justify-between gap-4 p-4">
              <div class="min-w-0">
                <p class="font-mono text-sm font-bold text-[var(--ds-text)]">
                  {{ selectedParameter.parameter_id?.code || "N/D" }}
                </p>
                <h3 class="ds-heading mt-1 text-sm">{{ selectedParameter.parameter_id?.name }}</h3>
                <p v-if="selectedParameter.unit_label" class="ds-copy mt-1 text-xs">
                  Unidade: {{ selectedParameter.unit_label }}
                </p>
              </div>
              <span v-if="selectedParameter.requires_calculation" class="ds-chip">
                <CalculatorIcon class="h-3.5 w-3.5" />
                Calculado
              </span>
            </div>
            <div
              v-if="selectedParameter.min_ref_value || selectedParameter.max_ref_value"
              class="flex items-center gap-2 border-t border-[var(--ds-border)] px-4 py-3 text-xs font-semibold text-[var(--ds-text-muted)]"
            >
              <ScaleIcon class="h-4 w-4" />
              Referência:
              <template v-if="selectedParameter.min_ref_value && selectedParameter.max_ref_value">
                {{ selectedParameter.min_ref_value }} - {{ selectedParameter.max_ref_value }}
              </template>
              <template v-else-if="selectedParameter.min_ref_value">≥ {{ selectedParameter.min_ref_value }}</template>
              <template v-else>≤ {{ selectedParameter.max_ref_value }}</template>
              {{ selectedParameter.unit_label }}
            </div>
          </section>

          <div class="ds-field-group">
            <label class="ds-field-label">{{ valueLabel }}</label>
            <div v-if="selectedParameterIsQualitative" class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="option in selectedParameterQualitativeOptions"
                :key="option"
                type="button"
                :class="[
                  'ds-button',
                  resultValue === option ? 'ds-button-primary' : 'ds-button-secondary',
                ]"
                @click="applyQualitativeOption(option)"
              >
                {{ option }}
              </button>
            </div>
            <div v-else class="mt-2 flex gap-2">
              <BaseInput
                v-model="resultValue"
                class="ds-field"
                :disabled="selectedParameter.requires_calculation"
                :placeholder="selectedParameter.requires_calculation ? 'Valor calculado automaticamente' : 'Introduza o resultado'"
              />
              <button
                type="button"
                class="ds-button ds-button-secondary shrink-0"
                @click="toggleDisplayFormat"
              >
                {{ displayFormatLabel }}
              </button>
            </div>
            <p
              v-if="resultValue && formattedResultValue !== resultValue"
              class="ds-field-hint"
            >
              Visualização: {{ formattedResultValue }}
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="ds-field-group">
              <label class="ds-field-label" for="individual-uncertainty">Incerteza</label>
              <BaseInput
                id="individual-uncertainty"
                v-model="uncertaintyValue"
                class="ds-field mt-2"
                inputmode="decimal"
                placeholder="Ex.: 0.1"
              />
            </div>
            <div class="ds-field-group">
              <label class="ds-field-label" for="individual-notes">Observações</label>
              <textarea
                id="individual-notes"
                v-model="notes"
                class="ds-field mt-2 min-h-20"
                placeholder="Registe o contexto da medição."
              />
            </div>
          </div>

          <button
            v-if="selectedParameter.requires_calculation"
            type="button"
            class="ds-button ds-button-secondary w-full"
            @click="openCalculationForParameter"
          >
            <CalculatorIcon class="h-4 w-4" />
            Calcular parâmetro
          </button>
        </template>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" class="ds-button ds-button-secondary" @click="emit('close')">
          Cancelar
        </button>
        <button
          type="button"
          class="ds-button ds-button-primary"
          :disabled="!canSave"
          @click="saveIndividualResult"
        >
          <CheckIcon class="h-4 w-4" />
          {{ saveButtonText }}
        </button>
      </footer>
    </div>
  </Modal>
</template>
