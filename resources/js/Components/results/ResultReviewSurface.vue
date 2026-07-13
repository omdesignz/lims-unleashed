<script setup>
import { ResultsDataService } from "@/Services/ResultsDataService.js";
import { computed, onMounted, ref, watch } from "vue";
import {
  CalculatorIcon,
  CheckIcon,
  DocumentMagnifyingGlassIcon,
  ExclamationTriangleIcon,
  PencilIcon,
  ScaleIcon,
  ShieldCheckIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  mode: {
    type: String,
    validator: (value) => ["verify", "approve"].includes(value),
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
  action: {
    type: String,
    required: true,
  },
  separatedResults: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["open-calculation", "submit"]);

const editingResults = ref({});
const editedValues = ref({});
const isLoading = ref(true);
const errorMessage = ref("");

const isApproval = computed(() => props.mode === "approve");
const valueField = computed(() => (isApproval.value ? "approved_value" : "verified_value"));
const sourceField = computed(() => (isApproval.value ? "verified_value" : "inserted_value"));
const notesField = computed(() => (isApproval.value ? "approval_notes" : "verification_notes"));
const decisionField = computed(() => "verification_status");

const title = computed(() => {
  return isApproval.value ? "Aprovação de resultados" : "Verificação técnica";
});

const description = computed(() => {
  return isApproval.value
    ? "Confirme a decisão final, referências e incerteza antes de libertar o resultado."
    : "Compare cada valor com a entrada original e documente qualquer correção técnica.";
});

const groupedResults = computed(() => [
  {
    key: "manual",
    label: "Parâmetros manuais",
    rows: props.separatedResults?.manualParams || [],
  },
  {
    key: "input",
    label: "Variáveis de entrada",
    rows: props.separatedResults?.inputVariables || [],
  },
  {
    key: "calculated",
    label: "Parâmetros calculados",
    rows: props.separatedResults?.calculatedParams || [],
  },
].filter((group) => group.rows.length));

const totalResults = computed(() => props.form.results?.length || 0);
const acceptedResults = computed(() => {
  return (props.form.results || []).filter(
    (result) => result[decisionField.value] === "approved",
  ).length;
});
const rejectedResults = computed(() => {
  return (props.form.results || []).filter(
    (result) => result[decisionField.value] === "rejected",
  ).length;
});
const editedResults = computed(() => {
  return (props.form.results || []).filter((result) => result.was_edited).length;
});
const pendingResults = computed(() => {
  return totalResults.value - acceptedResults.value - rejectedResults.value;
});
const hasOpenEdit = computed(() => Object.values(editingResults.value).some(Boolean));

const reviewMetrics = computed(() => [
  { label: "Total", value: totalResults.value, dot: "lims-status-dot-neutral" },
  { label: "Aceites", value: acceptedResults.value, dot: "lims-status-dot-release" },
  { label: "Rejeitados", value: rejectedResults.value, dot: "lims-status-dot-critical" },
  { label: "Pendentes", value: pendingResults.value, dot: "lims-status-dot-hold" },
]);

onMounted(initializeReview);

watch(
  () => props.form.results,
  initializeReview,
);

function getResultUniqueId(result, index = 0) {
  return String(
    result._uniqueId ||
      result.result_id ||
      result.id ||
      result.parameter_id?.id ||
      result.parameter_id?.value ||
      result.parameter_id?.code ||
      "row-" + index,
  );
}

function initializeReview() {
  const results = props.form.results || [];
  if (!results.length) {
    isLoading.value = true;
    return;
  }

  results.forEach((result, index) => {
    result._uniqueId = getResultUniqueId(result, index);
    result.original_value ??= result[sourceField.value] ?? "";
    result[valueField.value] ??= result[sourceField.value] ?? "";
    result[decisionField.value] ??= "pending";
    result.uncertainty_value ??= null;
  });

  isLoading.value = false;
  errorMessage.value = "";
}

function isEditing(result) {
  return Boolean(editingResults.value[getResultUniqueId(result)]);
}

function currentValue(result) {
  const resultId = getResultUniqueId(result);
  return editedValues.value[resultId]?.value ?? result[valueField.value] ?? result[sourceField.value] ?? "";
}

function sourceValue(result) {
  return result[sourceField.value] ?? result.inserted_value ?? "";
}

function displayValue(result) {
  return ResultsDataService.formatResultValue(result[valueField.value], result);
}

function formatSourceValue(result) {
  return ResultsDataService.formatResultValue(sourceValue(result), result);
}

function isQualitativeResult(result) {
  return ResultsDataService.isQualitativeResult(result);
}

function qualitativeOptions(result) {
  return ResultsDataService.getQualitativeOptions(result);
}

function displayFormatLabel(result) {
  return ResultsDataService.getDisplayFormat(result) === "scientific"
    ? "Notação normal"
    : "Notação científica";
}

function toggleResultDisplayFormat(result) {
  ResultsDataService.setDisplayFormat(
    result,
    ResultsDataService.getDisplayFormat(result) === "scientific"
      ? "standard"
      : "scientific",
  );
}

function openEdit(result) {
  closeAllEditModes();

  const resultId = getResultUniqueId(result);
  editingResults.value[resultId] = true;
  editedValues.value[resultId] = {
    value: result[valueField.value] ?? result[sourceField.value] ?? "",
    uncertainty: result.uncertainty_value ?? "",
    minRef: result.min_ref_value ?? "",
    maxRef: result.max_ref_value ?? "",
    notes: result[notesField.value] ?? "",
  };
}

function handleInputChange(resultId, field, value) {
  editedValues.value[resultId] ??= {};
  editedValues.value[resultId][field] = value;
}

function applyQualitativeOption(result, value) {
  handleInputChange(getResultUniqueId(result), "value", value);
}

function saveEdit(resultId) {
  const result = props.form.results.find(
    (item) => getResultUniqueId(item) === resultId,
  );

  if (!result) {
    errorMessage.value = "O resultado editado já não está disponível.";
    return;
  }

  const values = editedValues.value[resultId] || {};
  result[valueField.value] = values.value;
  result.uncertainty_value = values.uncertainty === "" ? null : values.uncertainty;
  result[notesField.value] = values.notes || null;

  if (isApproval.value) {
    result.min_ref_value = values.minRef === "" ? null : values.minRef;
    result.max_ref_value = values.maxRef === "" ? null : values.maxRef;
  }

  result.was_edited = String(values.value) !== String(sourceValue(result));
  result.edited_at = new Date().toISOString();
  editingResults.value[resultId] = false;
}

function cancelEdit(resultId) {
  delete editedValues.value[resultId];
  editingResults.value[resultId] = false;
}

function closeAllEditModes() {
  Object.entries(editingResults.value).forEach(([resultId, isOpen]) => {
    if (isOpen) {
      saveEdit(resultId);
    }
  });
}

function setDecision(result, decision) {
  result[decisionField.value] = decision;
  result[notesField.value] =
    decision === "rejected"
      ? result[notesField.value] || "Resultado rejeitado durante a revisão."
      : result[notesField.value];
}

function prepareSubmissionData() {
  return props.form.results.map((result) => ({
    ...result,
    display_format: ResultsDataService.getDisplayFormat(result),
    extra_data: {
      ...(result.extra_data || {}),
      display_format: ResultsDataService.getDisplayFormat(result),
    },
    is_override: result.manual_override ?? false,
  }));
}

function submitReview() {
  errorMessage.value = "";

  if (isLoading.value || !props.form.results?.length) {
    errorMessage.value = "Aguarde pelo carregamento dos resultados.";
    return;
  }

  closeAllEditModes();

  const missingResults = props.form.results.filter((result) => {
    const value = sourceValue(result);
    return value === null || value === undefined || String(value).trim() === "";
  });

  if (missingResults.length) {
    const labels = missingResults
      .map((result) => result.parameter_id?.code || result.parameter_id?.name || "Sem código")
      .join(", ");
    errorMessage.value =
      missingResults.length + " resultado(s) sem valor de origem: " + labels + ".";
    return;
  }

  props.form.results = prepareSubmissionData();
  props.form.status = isApproval.value ? "approved" : "verified";
  props.form.performed_at = new Date().toISOString();
  emit("submit");
}
</script>

<template>
  <div class="min-w-0 space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <div
            class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)]"
            :class="isApproval ? 'text-[var(--lims-release)]' : 'text-[var(--lims-instrument)]'"
          >
            <ShieldCheckIcon v-if="isApproval" class="h-5 w-5" />
            <DocumentMagnifyingGlassIcon v-else class="h-5 w-5" />
          </div>
          <div>
            <p class="ds-kicker">{{ isApproval ? "Decisão final" : "Revisão independente" }}</p>
            <h2 class="ds-heading mt-2 text-base">{{ title }}</h2>
            <p class="ds-copy mt-1 text-xs">{{ description }}</p>
          </div>
        </div>
        <span class="ds-chip font-mono">{{ record?.code || "Sem código" }}</span>
      </div>

      <dl class="grid sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="metric in reviewMetrics"
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
    </section>

    <section v-if="errorMessage" class="lims-status-strip p-4">
      <div class="flex items-start gap-3">
        <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-critical)]" />
        <div>
          <h2 class="ds-heading text-sm">Decisão bloqueada</h2>
          <p class="ds-copy mt-1 text-xs">{{ errorMessage }}</p>
        </div>
      </div>
    </section>

    <section v-if="isLoading" class="ds-panel p-10 text-center">
      <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-[var(--ds-border-strong)] border-t-[rgb(var(--primary-600-rgb))]" />
      <p class="ds-heading mt-4 text-sm">A carregar resultados</p>
    </section>

    <template v-else>
      <section
        v-for="group in groupedResults"
        :key="group.key"
        class="ds-panel overflow-hidden"
      >
        <div class="flex items-center justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4">
          <div>
            <p class="ds-kicker">{{ group.key === "calculated" ? "Cálculo" : "Medição" }}</p>
            <h2 class="ds-heading mt-2 text-base">{{ group.label }}</h2>
          </div>
          <span class="ds-chip">{{ group.rows.length }} resultados</span>
        </div>

        <div class="divide-y divide-[var(--ds-border)]">
          <article
            v-for="(result, index) in group.rows"
            :key="getResultUniqueId(result, index)"
            class="px-5 py-5"
          >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-mono text-sm font-bold text-[var(--ds-text)]">
                    {{ result.parameter_id?.code || "N/D" }}
                  </span>
                  <span v-if="result.requires_calculation" class="ds-chip">
                    <CalculatorIcon class="h-3.5 w-3.5" />
                    Calculado
                  </span>
                  <span v-if="result.was_edited" class="ds-chip">
                    <PencilIcon class="h-3.5 w-3.5" />
                    Corrigido
                  </span>
                </div>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                  {{ result.parameter_id?.name || result.parameter_label || "Parâmetro sem nome" }}
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  :class="[
                    'ds-button',
                    result[decisionField] === 'approved' ? 'ds-button-primary' : 'ds-button-secondary',
                  ]"
                  @click="setDecision(result, 'approved')"
                >
                  <CheckIcon class="h-4 w-4" />
                  Aceitar
                </button>
                <button
                  type="button"
                  :class="[
                    'ds-button',
                    result[decisionField] === 'rejected'
                      ? 'border-red-300 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200'
                      : 'ds-button-secondary',
                  ]"
                  @click="setDecision(result, 'rejected')"
                >
                  <XMarkIcon class="h-4 w-4" />
                  Rejeitar
                </button>
                <button
                  type="button"
                  class="ds-icon-button"
                  title="Editar resultado"
                  @click="openEdit(result)"
                >
                  <PencilIcon class="h-4 w-4" />
                  <span class="sr-only">Editar resultado</span>
                </button>
              </div>
            </div>

            <div v-if="!isEditing(result)" class="mt-4 grid gap-px overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-border)] sm:grid-cols-2 lg:grid-cols-4">
              <div class="bg-[var(--ds-panel-raised)] p-3">
                <p class="ds-table-heading">{{ isApproval ? "Verificado" : "Inserido" }}</p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text-muted)]">{{ formatSourceValue(result) }}</p>
              </div>
              <div class="bg-[var(--ds-panel-raised)] p-3">
                <p class="ds-table-heading">{{ isApproval ? "Aprovado" : "Verificado" }}</p>
                <p class="ds-heading mt-2 text-sm">{{ displayValue(result) }}</p>
              </div>
              <div class="bg-[var(--ds-panel-raised)] p-3">
                <p class="ds-table-heading">Incerteza</p>
                <p class="ds-heading mt-2 text-sm">{{ result.uncertainty_value || "N/A" }}</p>
              </div>
              <div class="bg-[var(--ds-panel-raised)] p-3">
                <p class="ds-table-heading">Referência</p>
                <p class="ds-heading mt-2 text-sm">
                  {{ result.min_ref_value ?? "-" }} / {{ result.max_ref_value ?? "-" }}
                </p>
              </div>
            </div>

            <div v-else class="ds-command-surface mt-4 overflow-hidden">
              <div class="grid gap-4 p-4 md:grid-cols-2">
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label">Valor revisto</label>
                  <div v-if="isQualitativeResult(result)" class="mt-2 flex flex-wrap gap-2">
                    <button
                      v-for="option in qualitativeOptions(result)"
                      :key="option.value ?? option"
                      type="button"
                      :class="[
                        'ds-button',
                        String(currentValue(result)) === String(option.value ?? option)
                          ? 'ds-button-primary'
                          : 'ds-button-secondary',
                      ]"
                      @click="applyQualitativeOption(result, option.value ?? option)"
                    >
                      {{ option.label ?? option }}
                    </button>
                  </div>
                  <div v-else class="mt-2 flex gap-2">
                    <input
                      :value="currentValue(result)"
                      class="ds-field"
                      inputmode="decimal"
                      @input="handleInputChange(getResultUniqueId(result), 'value', $event.target.value)"
                    />
                    <button
                      type="button"
                      class="ds-button ds-button-secondary shrink-0"
                      @click="toggleResultDisplayFormat(result)"
                    >
                      {{ displayFormatLabel(result) }}
                    </button>
                  </div>
                </div>

                <div class="ds-field-group">
                  <label class="ds-field-label">Incerteza</label>
                  <div class="relative mt-2">
                    <ScaleIcon class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
                    <input
                      :value="editedValues[getResultUniqueId(result)]?.uncertainty"
                      class="ds-field pl-9"
                      inputmode="decimal"
                      @input="handleInputChange(getResultUniqueId(result), 'uncertainty', $event.target.value)"
                    />
                  </div>
                </div>

                <template v-if="isApproval">
                  <div class="ds-field-group">
                    <label class="ds-field-label">Referência mínima</label>
                    <input
                      :value="editedValues[getResultUniqueId(result)]?.minRef"
                      class="ds-field mt-2"
                      inputmode="decimal"
                      @input="handleInputChange(getResultUniqueId(result), 'minRef', $event.target.value)"
                    />
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label">Referência máxima</label>
                    <input
                      :value="editedValues[getResultUniqueId(result)]?.maxRef"
                      class="ds-field mt-2"
                      inputmode="decimal"
                      @input="handleInputChange(getResultUniqueId(result), 'maxRef', $event.target.value)"
                    />
                  </div>
                </template>

                <div class="ds-field-group" :class="{ 'md:col-span-2': !isApproval }">
                  <label class="ds-field-label">Notas da decisão</label>
                  <textarea
                    :value="editedValues[getResultUniqueId(result)]?.notes"
                    class="ds-field mt-2 min-h-20"
                    @input="handleInputChange(getResultUniqueId(result), 'notes', $event.target.value)"
                  />
                </div>
              </div>

              <div class="flex justify-end gap-2 border-t border-[var(--ds-border)] px-4 py-3">
                <button
                  type="button"
                  class="ds-button ds-button-secondary"
                  @click="cancelEdit(getResultUniqueId(result))"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  class="ds-button ds-button-primary"
                  @click="saveEdit(getResultUniqueId(result))"
                >
                  Guardar correção
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>

      <section v-if="!groupedResults.length" class="ds-panel p-10 text-center">
        <ExclamationTriangleIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <h2 class="ds-heading mt-3 text-sm">Nenhum resultado disponível</h2>
      </section>
    </template>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4">
        <p class="ds-kicker">Justificação da etapa</p>
        <h2 class="ds-heading mt-2 text-base">Notas globais</h2>
      </div>
      <div class="p-5">
        <textarea
          v-model="form.notes"
          class="ds-field min-h-24"
          :placeholder="isApproval ? 'Registe a fundamentação da aprovação.' : 'Registe observações da verificação técnica.'"
        />
      </div>
    </section>

    <footer class="ds-command-toolbar sticky bottom-4 z-10 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="ds-table-heading">Prontidão da decisão</p>
        <p class="ds-copy mt-1 text-xs">
          {{ editedResults }} corrigidos / {{ pendingResults }} sem decisão individual.
        </p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row">
        <button
          v-if="separatedResults?.calculatedParams?.length"
          type="button"
          class="ds-button ds-button-secondary"
          @click="emit('open-calculation')"
        >
          <CalculatorIcon class="h-4 w-4" />
          Rever cálculos
        </button>
        <button
          type="button"
          class="ds-button ds-button-primary"
          :disabled="form.processing || hasOpenEdit || isLoading"
          @click="submitReview"
        >
          <ShieldCheckIcon v-if="isApproval" class="h-4 w-4" />
          <DocumentMagnifyingGlassIcon v-else class="h-4 w-4" />
          {{ form.processing ? "A processar..." : isApproval ? "Confirmar aprovação" : "Confirmar verificação" }}
        </button>
      </div>
    </footer>
  </div>
</template>
