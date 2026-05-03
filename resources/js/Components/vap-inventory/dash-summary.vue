<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 mt-4">
    <div @click="goTo('critical')" class="cursor-pointer bg-white border-l-4 border-red-600 p-4 shadow-sm hover:shadow-md transition-shadow">
      <div class="flex items-center">
        <div class="p-2 bg-red-100 rounded-lg">
          <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500 uppercase">Problemas Críticos</p>
          <p class="text-2xl font-bold text-gray-900">{{ summary.critical }} <span class="text-sm font-normal text-gray-400">Itens</span></p>
        </div>
      </div>
      <p class="mt-2 text-xs text-red-600 font-semibold">Lotes de Estoque ou Reagentes Expirados →</p>
    </div>

    <div @click="goTo('reorder')" class="cursor-pointer bg-white border-l-4 border-orange-500 p-4 shadow-sm hover:shadow-md transition-shadow">
      <div class="flex items-center">
        <div class="p-2 bg-orange-100 rounded-lg">
          <ShoppingCartIcon class="h-6 w-6 text-orange-500" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500 uppercase">Pedidos Pendentes</p>
          <p class="text-2xl font-bold text-gray-900">{{ summary.toOrder }} <span class="text-sm font-normal text-gray-400">Baixo de Estoque</span></p>
        </div>
      </div>
      <p class="mt-2 text-xs text-orange-600 font-semibold">Gerar rascunhos agora →</p>
    </div>

    <div @click="goTo('orders')" class="cursor-pointer bg-white border-l-4 border-blue-600 p-4 shadow-sm hover:shadow-md transition-shadow">
      <div class="flex items-center">
        <div class="p-2 bg-blue-100 rounded-lg">
          <TruckIcon class="h-6 w-6 text-blue-600" />
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500 uppercase">Envios Atrasados</p>
          <p class="text-2xl font-bold text-gray-900">{{ summary.delayed }} <span class="text-sm font-normal text-gray-400">Pedidos</span></p>
        </div>
      </div>
      <p class="mt-2 text-xs text-blue-600 font-semibold">Seguir atrasado com os fornecedores →</p>
    </div>
  </div>
</template>

<script setup>
import { ExclamationTriangleIcon, ShoppingCartIcon, TruckIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
// Import your icons...

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({})
    }
});

const summary = ref(props.summary);

const goTo = (type) => {
  const routes = {
    critical: route('vap-inventory.items.index', { filter: 'critical' }),
    reorder: route('vap-inventory.items.index', { filter: 'low_stock' }),
    orders: route('vap-inventory.orders.index', { status: 'delayed' })
  };
  router.visit(routes[type]);
};

const fetchSummary = () => {
  fetch(route('vap-inventory.analytics.summary'))
    .then(response => response.json())
    .then(results => {
      summary.value = results;
    })
    .catch(error => {
      console.error('Error fetching summary:', error);
    });
};

onMounted(() => {
  fetchSummary();
});
</script>