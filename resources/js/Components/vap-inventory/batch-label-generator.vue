<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
      <h3 class="font-bold text-gray-800">Novas Lotes de Reagentes</h3>
      <div class="flex gap-2">
         <button @click="printSelectedLabels" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
           <PrinterIcon class="w-4 h-4" /> Imprimir Lotes Selecionados
         </button>
      </div>
    </div>

    <table class="w-full text-left">
      <thead class="bg-gray-50 text-[11px] uppercase text-gray-500 font-bold">
        <tr>
          <th class="px-6 py-3 w-10"><input type="checkbox" v-model="selectAll" /></th>
          <th class="px-6 py-3">Item / Nome</th>
          <th class="px-6 py-3">Número da Lote</th>
          <th class="px-6 py-3">Qtd Restante</th>
          <th class="px-6 py-3">Validade</th>
          <th class="px-6 py-3">Estado</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="batch in batches" :key="batch.id" class="hover:bg-gray-50 transition-colors">
          <td class="px-6 py-4"><input type="checkbox" :value="batch.id" v-model="selectedIds" /></td>
          <td class="px-6 py-4">
            <div class="font-bold text-gray-900">{{ batch.item_name }}</div>
            <div class="text-xs text-gray-400">{{ batch.internal_code }}</div>
          </td>
          <td class="px-6 py-4 font-mono text-sm">{{ batch.batch_number }}</td>
          <td class="px-6 py-4">
            <span class="font-bold">{{ batch.qty_remaining }}</span> {{ batch.unit_name }}
          </td>
          <td class="px-6 py-4 text-sm">
            {{ formatDate(batch.expiry_date) }}
          </td>
          <td class="px-6 py-4">
            <span :class="getStatusClass(batch)">{{ batch.status }}</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const batches = ref([]); // Fetch from your controller
const selectedIds = ref([]);

const printSelectedLabels = () => {
  if (selectedIds.value.length === 0) return;
  // Redirect to the PDF generation route we built earlier
//   window.open(`/reports/labels/batches?ids=${selectedIds.value.join(',')}`, '_blank');

  window.open(route('printBatchLabels', { ids: selectedIds.value.join(',') }), '_blank');
};

const getStatusClass = (batch) => {
  if (batch.is_expired) return 'bg-red-100 text-red-700 px-2 py-1 rounded text-[10px] font-bold';
  if (batch.qty_remaining <= 0) return 'bg-gray-100 text-gray-700 px-2 py-1 rounded text-[10px] font-bold'; 
  return 'bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold';
};
</script>