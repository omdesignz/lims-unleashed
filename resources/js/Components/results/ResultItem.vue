<script setup>
import Combobox from "@/Components/combobox.vue";
import { ResultsDataService } from "@/Services/ResultsDataService.js";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { computed } from "vue";
import {
  Calculator as CalculatorIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Trash2 as TrashIcon,
  Variable as VariableIcon,
} from "@lucide/vue";

const props = defineProps({
  result: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  record: {
    type: Object,
    default: () => ({}),
  },
  isInputVariable: Boolean,
  isReadOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["remove", "update"]);

const isOutOfRange = computed(() => {
  const value = Number.parseFloat(props.result.inserted_value);
  if (Number.isNaN(value)) {
    return false;
  }

  if (props.result.min_ref_value !== null && value < props.result.min_ref_value) {
    return true;
  }

  return (
    props.result.max_ref_value !== null &&
    value > props.result.max_ref_value
  );
});

const isQualitative = computed(() => {
  return ResultsDataService.isQualitativeResult(props.result);
});

const qualitativeOptions = computed(() => {
  return ResultsDataService.getQualitativeOptions(props.result);
});

const formattedInsertedValue = computed(() => {
  return ResultsDataService.formatResultValue(
    props.result.inserted_value,
    props.result,
  );
});

const displayFormatLabel = computed(() => {
  return ResultsDataService.getDisplayFormat(props.result) === "scientific"
    ? "Notação normal"
    : "Notação científica";
});

function normalizeCalculationParameters(value) {
  if (Array.isArray(value)) {
    return value;
  }

  if (!value) {
    return [];
  }

  try {
    const parsed = JSON.parse(value);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function loadParameters(query, setOptions) {
  return loadSelectOptions(
    "/parameters/getParameter",
    query,
    setOptions,
    (result) => ({
      value: result.id,
      label: result.code,
      name: result.name,
      code: result.code,
      result_is_qualitative: result.result_is_qualitative,
      result_options: result.result_is_qualitative ? ["Presença", "Ausência"] : [],
      decimal_places: result.decimal_places,
      result_type: result.result_type,
      requires_calculation: result.requires_calculation,
      formula_id: result.formula_id,
      formula_expression: result.formula_expression,
      calculation_parameters: normalizeCalculationParameters(
        result.calculation_parameters,
      ),
    }),
  );
}

function loadUnits(query, setOptions) {
  return loadSelectOptions("/units/getUnit", query, setOptions, (result) => ({
    ...optionMappers.code(result),
    name: result.name,
  }));
}

function handleValueChange(value) {
  props.result.inserted_value = value;
  emit("update", props.index, props.result);
}

function applyQualitativeOption(value) {
  handleValueChange(value);
}

function toggleDisplayFormat() {
  ResultsDataService.setDisplayFormat(
    props.result,
    ResultsDataService.getDisplayFormat(props.result) === "scientific"
      ? "standard"
      : "scientific",
  );
  emit("update", props.index, props.result);
}

function handleParameterSelect(selected) {
  props.result.parameter_id = selected;
  props.result.result_is_qualitative = Boolean(selected?.result_is_qualitative);
  props.result.result_options =
    selected?.result_options ||
    (selected?.result_is_qualitative ? ["Presença", "Ausência"] : []);
  props.result.decimal_places =
    selected?.decimal_places ?? props.result.decimal_places;
  props.result.result_type = selected?.result_type ?? props.result.result_type;
  props.result.requires_calculation = Boolean(selected?.requires_calculation);

  if (selected?.unit_id) {
    props.result.unit_id = selected.unit_id;
  }

  emit("update", props.index, props.result);
}
</script>

<template>
  <article
    :class="[
      'rounded-lg border p-4',
      isOutOfRange
        ? 'border-red-300 bg-red-50/60 dark:border-red-500/30 dark:bg-red-500/10'
        : isInputVariable
          ? 'border-[rgb(var(--primary-300-rgb))] bg-[var(--ds-panel-subtle)]'
          : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)]',
    ]"
  >
    <div class="flex items-start justify-between gap-4">
      <div class="flex flex-wrap items-center gap-2">
        <span class="grid h-7 w-7 place-items-center rounded-full border border-[var(--ds-border-strong)] text-xs font-bold text-[var(--ds-text)]">
          {{ index + 1 }}
        </span>
        <span v-if="isInputVariable" class="ds-chip">
          <VariableIcon class="h-3.5 w-3.5" />
          Variável de entrada
        </span>
        <span v-if="result.requires_calculation" class="ds-chip">
          <CalculatorIcon class="h-3.5 w-3.5" />
          Calculado
        </span>
      </div>
      <button
        v-if="!isReadOnly"
        type="button"
        class="ds-icon-button text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/10"
        title="Remover parâmetro"
        @click="emit('remove', index)"
      >
        <TrashIcon class="h-4 w-4" />
        <span class="sr-only">Remover parâmetro</span>
      </button>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <div class="ds-field-group">
        <label class="ds-field-label">Parâmetro</label>
        <Combobox
          v-if="!isReadOnly"
          v-model="result.parameter_id"
          class="mt-2"
          :has-error="form.errors['results.' + index + '.parameter_id']"
          :load-options="loadParameters"
          :disable-input="result.requires_calculation"
          :placeholder="isInputVariable ? 'Seleccione a variável de entrada' : 'Seleccione o parâmetro'"
          @update:model-value="handleParameterSelect"
        />
        <p v-else class="mt-2 text-sm font-bold text-[var(--ds-text)]">
          {{ result.parameter_id?.code }} - {{ result.parameter_id?.name }}
        </p>
        <p
          v-if="form.errors['results.' + index + '.parameter_id']"
          class="ds-field-error"
        >
          {{ form.errors["results." + index + ".parameter_id"] }}
        </p>
      </div>

      <div
        v-if="!isInputVariable && !result.requires_calculation"
        class="ds-field-group"
      >
        <label class="ds-field-label">Unidade</label>
        <Combobox
          v-if="!isReadOnly"
          v-model="result.unit_id"
          class="mt-2"
          :has-error="form.errors['results.' + index + '.unit_id']"
          :load-options="loadUnits"
          placeholder="Seleccione a unidade"
        />
        <p v-else class="mt-2 text-sm font-bold text-[var(--ds-text)]">
          {{ result.unit_id?.code || "-" }}
        </p>
      </div>

      <div class="ds-field-group" :class="{ 'lg:col-span-2': isInputVariable || result.requires_calculation }">
        <label class="ds-field-label">
          Resultado
          <span v-if="isInputVariable" class="font-semibold text-[var(--lims-instrument)]">
            / variável de cálculo
          </span>
        </label>

        <template v-if="!isReadOnly">
          <div v-if="isQualitative" class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="option in qualitativeOptions"
              :key="option"
              type="button"
              :class="[
                'ds-button',
                result.inserted_value === option
                  ? 'ds-button-primary'
                  : 'ds-button-secondary',
              ]"
              @click="applyQualitativeOption(option)"
            >
              {{ option }}
            </button>
          </div>

          <div v-else class="mt-2 flex gap-2">
            <BaseInput
              v-model="result.inserted_value"
              class="ds-field"
              :class="{ 'border-red-400 text-red-800 dark:text-red-200': isOutOfRange }"
              :disabled="result.requires_calculation"
              :placeholder="result.requires_calculation ? 'Calculado automaticamente' : 'Introduza o resultado'"
              @input="handleValueChange($event.target.value)"
            />
            <button
              type="button"
              class="ds-button ds-button-secondary shrink-0"
              @click="toggleDisplayFormat"
            >
              {{ displayFormatLabel }}
            </button>
          </div>
        </template>
        <p v-else class="mt-2 text-sm font-bold text-[var(--ds-text)]">
          {{ formattedInsertedValue || "-" }} {{ result.unit_id?.code }}
        </p>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
          <p
            v-if="result.inserted_value && formattedInsertedValue !== result.inserted_value"
            class="ds-field-hint"
          >
            Visualização: {{ formattedInsertedValue }}
          </p>
          <p
            v-if="!isInputVariable && (result.min_ref_value || result.max_ref_value)"
            class="text-xs font-semibold text-[var(--ds-text-muted)]"
          >
            Referência:
            <template v-if="result.min_ref_value && result.max_ref_value">
              {{ result.min_ref_value }} - {{ result.max_ref_value }}
            </template>
            <template v-else-if="result.min_ref_value">≥ {{ result.min_ref_value }}</template>
            <template v-else>≤ {{ result.max_ref_value }}</template>
            {{ result.unit_id?.code }}
          </p>
        </div>

        <p
          v-if="isOutOfRange"
          class="mt-2 flex items-center gap-2 text-xs font-bold text-[var(--lims-critical)]"
        >
          <ExclamationTriangleIcon class="h-4 w-4" />
          Fora do intervalo de referência
        </p>
      </div>

      <div class="ds-field-group lg:col-span-2">
        <label class="ds-field-label" :for="'item-' + index + '-uncertainty'">
          Incerteza
        </label>
        <BaseInput
          :id="'item-' + index + '-uncertainty'"
          v-model="result.uncertainty_value"
          class="ds-field mt-2"
          :disabled="isReadOnly"
          inputmode="decimal"
        />
      </div>
    </div>

    <p
      v-if="form.errors['results.' + index + '.inserted_value']"
      class="ds-field-error mt-3"
    >
      {{ form.errors["results." + index + ".inserted_value"] }}
    </p>
  </article>
</template>
