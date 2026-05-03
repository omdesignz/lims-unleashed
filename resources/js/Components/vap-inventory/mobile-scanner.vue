<template>
  <div class="max-w-md mx-auto p-4 min-h-screen bg-gray-50">
    <header class="mb-6 flex justify-between items-center">
      <h1 class="text-xl font-bold text-gray-900">VAP LabScan v1.1</h1>
      <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
    </header>

    <div v-if="!scannedBatch" class="bg-white rounded-3xl p-8 shadow-sm border-2 border-dashed border-gray-200 text-center">
      <div class="mb-4">
        <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto">
          <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
        </div>
      </div>
      <p class="text-gray-600 mb-6">Posicione o Código QR na moldura ou use um scanner de hardware</p>
      
      <input 
        ref="barcodeInput"
        v-model="manualInput"
        @keyup.enter="handleManualScan"
        class="absolute opacity-0 pointer-events-none"
        autofocus
      />
      
      <button @click="initCamera" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200">
        Abrir Câmera
      </button>
    </div>

    <div v-else class="space-y-4 animate-in fade-in slide-in-from-bottom-5">
      <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h2 class="text-lg font-bold text-gray-900">{{ scannedBatch.item_name }}</h2>
            <p class="text-sm text-gray-500">Lot: {{ scannedBatch.batch_number }}</p>
          </div>
          <button @click="resetScan" class="p-2 bg-gray-100 rounded-full">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div class="bg-blue-50 p-3 rounded-2xl">
            <span class="text-[10px] text-blue-600 font-bold uppercase">Disponível</span>
            <p class="text-xl font-bold text-blue-900">{{ scannedBatch.qty_remaining }}</p>
          </div>
          <div :class="['p-3 rounded-2xl', scannedBatch.is_expired ? 'bg-red-50' : 'bg-green-50']">
            <span class="text-[10px] font-bold uppercase" :class="scannedBatch.is_expired ? 'text-red-600' : 'text-green-600'">Validade</span>
            <p class="text-sm font-bold" :class="scannedBatch.is_expired ? 'text-red-900' : 'text-green-900'">{{ scannedBatch.expiry_date }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label class="text-xs font-bold text-gray-400 uppercase">Quantidade a Remover</label>
            <input v-model="form.qty" type="number" step="0.01" class="w-full text-2xl font-bold border-0 border-b-2 border-gray-200 focus:ring-0 focus:border-blue-600" />
          </div>

          <div class="grid grid-cols-2 gap-2">
            <button 
              @click="submitAction('consumption')" 
              :disabled="loading"
              class="py-4 bg-orange-500 text-white rounded-2xl font-bold flex flex-col items-center"
            >
              <span>Registrar Uso</span>
              <span class="text-[10px] opacity-80">Consumo de Reagentes</span>
            </button>
            <button 
              @click="submitAction('transfer')" 
              :disabled="loading"
              class="py-4 bg-blue-900 text-white rounded-2xl font-bold flex flex-col items-center"
            >
              <span>Transferir</span>
              <span class="text-[10px] opacity-80">Mover Armazém</span>
            </button>
            <button @click="openAction('audit')" class="h-32 bg-white border-2 border-green-600 rounded-2xl flex flex-col items-center justify-center gap-2">
            <CheckBadgeIcon class="h-8 w-8 text-green-600" />
            <span class="font-bold text-green-900">Auditar Estoque</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification'; // Optional: for alerts
import { CheckBadgeIcon } from '@heroicons/vue/24/outline';

const toast = useToast();
const manualInput = ref('');
const scannedBatch = ref(null);
const loading = ref(false);
const form = reactive({
  qty: 1,
  batch_id: null,
});

const handleManualScan = async () => {
  try {
    const response = await axios.get(route('inventory.batches.lookup', { id: manualInput.value }));
    scannedBatch.value = response.data;
    form.batch_id = response.data.id;
    manualInput.value = '';
  } catch (err) {
    toast.error("Batch not found or barcode invalid");
    manualInput.value = '';
  }
};

const submitAction = async (type) => {
  loading.value = true;
  try {
    await axios.post(route('inventory.batches.mobile-action'), {
      ...form,
      type: type, // 'consumption' or 'transfer'
    });
    toast.success("Transaction recorded!");
    resetScan();
  } catch (err) {
    toast.error("Error processing transaction");
  } finally {
    loading.value = false;
  }
};

const resetScan = () => {
  scannedBatch.value = null;
  form.qty = 1;
  form.batch_id = null;
};

const submitAudit = async () => {
  try {
    await axios.post(route('inventory.batches.audit'), {
        batch_id: scannedBatch.value.id,
        physical_qty: form.qty, // The amount actually seen on the shelf
        system_qty: scannedBatch.value.qty_remaining
    });
    toast.success("Audit completed. Inventory adjusted.");
    resetScan();
  } catch (err) {
    toast.error("Audit failed to save.");
  }
};
</script>