<template>
  <section class="ds-card p-5">
    <h3 class="text-lg font-bold text-[var(--ds-text)]">Registrar uso</h3>

    <div class="mt-4">
      <label class="ds-field-group">
        <span class="ds-field-label">Escanear código de reagente / lote</span>
        <span class="mt-1 flex gap-2">
          <BaseInput
            v-model="barcode"
            type="text"
            class="ds-field"
            placeholder="Scan or type batch ID..."
            @keyup.enter="findBatch"
          />
          <button type="button" class="ds-button ds-button-primary" @click="findBatch">Localizar</button>
        </span>
      </label>
    </div>

    <div v-if="activeItem" class="mt-5 space-y-4">
      <div class="ds-card p-4">
        <p class="font-bold text-[var(--ds-text)]">{{ activeItem.name }}</p>
        <p class="text-sm font-semibold text-[var(--ds-text-muted)]">Número de lote: {{ activeItem.total_qty }}</p>
      </div>

      <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Lotes disponíveis (mais antigos primeiro)</p>
      <button
        v-for="batch in activeItem.batches"
        :key="batch.id"
        type="button"
        :class="[
          'flex w-full items-center justify-between rounded-lg border p-3 text-left',
          batch.id === selectedBatchId ? 'border-[rgb(var(--primary-500-rgb))] bg-[rgb(var(--primary-50-rgb))]' : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)]',
        ]"
        @click="selectedBatchId = batch.id"
      >
        <span>
          <span class="font-mono font-bold text-[var(--ds-text)]">{{ batch.batch_number }}</span>
          <span class="block text-xs font-semibold text-[var(--ds-text-soft)]">Validade: {{ batch.expiry_date }}</span>
        </span>
        <span class="text-right text-sm font-bold text-[var(--ds-text)]">{{ batch.qty_remaining }} restantes</span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

const barcode = ref('')
const activeItem = ref(null)
const selectedBatchId = ref(null)

function findBatch() {
  selectedBatchId.value = null
}
</script>
