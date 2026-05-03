<template>
  <div class="bg-white p-6 rounded-xl shadow-sm border">
    <h3 class="text-lg font-bold mb-4">Registrar Uso</h3>
    
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700">Escanear Código de Reagente / Lote</label>
      <div class="mt-1 flex gap-2">
        <input 
          v-model="barcode" 
          @keyup.enter="findBatch"
          type="text" 
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
          placeholder="Scan or type batch ID..."
        />
        <button @click="findBatch" class="bg-blue-600 text-white px-4 py-2 rounded-md">Localizar</button>
      </div>
    </div>

    <div v-if="activeItem" class="space-y-4">
      <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
        <p class="text-blue-800 font-bold">{{ activeItem.name }}</p>
        <p class="text-sm text-blue-600">Número de Lote: {{ activeItem.total_qty }}</p>
      </div>

      <p class="text-xs font-bold text-gray-400 uppercase">Lotes Disponíveis (Mais Antigos Primeiro)</p>
      <div v-for="batch in activeItem.batches" :key="batch.id" 
           :class="['p-3 border rounded-lg flex justify-between items-center', 
                   batch.id === selectedBatchId ? 'border-blue-600 bg-blue-50' : 'border-gray-200']"
           @click="selectedBatchId = batch.id">
        <div>
          <span class="font-mono font-bold">{{ batch.batch_number }}</span>
          <p class="text-xs text-gray-500">Validade: {{ batch.expiry_date }}</p>
        </div>
        <div class="text-right">
          <span class="text-sm font-bold">{{ batch.qty_remaining }} restantes</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

</script>