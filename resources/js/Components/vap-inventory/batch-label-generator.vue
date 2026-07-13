<template>
  <section class="ds-table-shell overflow-hidden">
    <div class="ds-table-summary px-5 py-4">
      <div>
        <p class="ds-kicker">Label control</p>
        <h3 class="mt-1 text-base font-bold text-[var(--ds-text)]">Novos lotes de reagentes</h3>
      </div>
      <button type="button" class="ds-button ds-button-primary" :disabled="selectedIds.length === 0" @click="printSelectedLabels">
        <PrinterIcon class="h-4 w-4" />
        Imprimir selecionados
      </button>
    </div>

    <div class="overflow-x-auto">
      <DataTable class="min-w-[48rem] text-left">
        <thead class="ds-table-head">
          <tr>
            <th class="ds-table-cell w-10">
              <CheckboxInput v-model="selectAll" type="checkbox" class="ds-checkbox" />
            </th>
            <th class="ds-table-cell">Item / Nome</th>
            <th class="ds-table-cell">Número do lote</th>
            <th class="ds-table-cell">Qtd restante</th>
            <th class="ds-table-cell">Validade</th>
            <th class="ds-table-cell">Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="batch in batches" :key="batch.id" class="ds-table-row">
            <td class="ds-table-cell">
              <CheckboxInput v-model="selectedIds" type="checkbox" :value="batch.id" class="ds-checkbox" />
            </td>
            <td class="ds-table-cell">
              <div class="font-bold text-[var(--ds-text)]">{{ batch.item_name }}</div>
              <div class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ batch.internal_code }}</div>
            </td>
            <td class="ds-table-cell font-mono text-sm">{{ batch.batch_number }}</td>
            <td class="ds-table-cell">
              <span class="font-bold">{{ batch.qty_remaining }}</span> {{ batch.unit_name }}
            </td>
            <td class="ds-table-cell text-sm">{{ formatDate(batch.expiry_date) }}</td>
            <td class="ds-table-cell">
              <span :class="getStatusClass(batch)">{{ batch.status }}</span>
            </td>
          </tr>
        </tbody>
      </DataTable>
    </div>
  </section>
</template>

<script setup>
import { PrinterIcon } from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

const batches = ref([])
const selectedIds = ref([])

const selectAll = computed({
  get: () => batches.value.length > 0 && selectedIds.value.length === batches.value.length,
  set: (value) => {
    selectedIds.value = value ? batches.value.map((batch) => batch.id) : []
  },
})

function printSelectedLabels() {
  if (selectedIds.value.length === 0) {
    return
  }

  window.location.assign(route('printBatchLabels', { ids: selectedIds.value.join(',') }))
}

function formatDate(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function getStatusClass(batch) {
  if (batch.is_expired) {
    return 'ds-chip border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100'
  }

  if (batch.qty_remaining <= 0) {
    return 'ds-chip'
  }

  return 'ds-chip border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-100'
}
</script>
