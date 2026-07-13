<script setup>
import CalculationResultEntry from "@/Components/results/CalculationResultEntry.vue";
import Modal from "@/Components/Modal.vue";
import { CalculatorIcon, XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  sampleId: {
    type: Object,
    default: null,
  },
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

const emit = defineEmits(["close", "calculated"]);

function handleCalculatedResults(payload) {
  emit("calculated", {
    ...payload,
    action: payload.action || props.action,
  });
}

function closeModal() {
  emit("close");
}
</script>

<template>
  <Modal :show="true" max-width="6xl" @close="closeModal">
    <div class="min-w-0">
      <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex min-w-0 items-start gap-3">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--lims-instrument)]">
            <CalculatorIcon class="h-5 w-5" />
          </div>
          <div>
            <p class="ds-kicker">Cálculo técnico</p>
            <h2 class="ds-heading mt-2 text-lg">Calculadora de parâmetros</h2>
            <p class="ds-copy mt-1 text-xs">
              Resolva fórmulas controladas com as variáveis disponíveis nesta amostra.
            </p>
          </div>
        </div>
        <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
          <XMarkIcon class="h-5 w-5" />
          <span class="sr-only">Fechar</span>
        </button>
      </header>

      <div class="max-h-[78vh] overflow-y-auto p-5 sm:p-6">
        <CalculationResultEntry
          :sample-id="sampleId"
          :parameters="parameters"
          :existing-results="existingResults"
          :action="action"
          @calculated-results="handleCalculatedResults"
          @close="closeModal"
        />
      </div>
    </div>
  </Modal>
</template>
