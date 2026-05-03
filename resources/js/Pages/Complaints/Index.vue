<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  complaints: Object,
  filters: Object,
  stats: Object,
});
</script>

<template>
  <div class="space-y-6 p-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Reclamações</h1>
      <p class="mt-1 text-sm text-gray-600">Registo, acompanhamento e fecho de reclamações com rastreabilidade.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="text-sm text-gray-500">Total</div>
        <div class="mt-2 text-2xl font-semibold text-gray-900">{{ stats.total }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="text-sm text-gray-500">Em aberto</div>
        <div class="mt-2 text-2xl font-semibold text-amber-700">{{ stats.open }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="text-sm text-gray-500">Resolvidas</div>
        <div class="mt-2 text-2xl font-semibold text-emerald-700">{{ stats.resolved }}</div>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Referência</th>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Título</th>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Estado</th>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Severidade</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="complaint in complaints.data" :key="complaint.id">
            <td class="px-4 py-3 text-gray-900">{{ complaint.reference }}</td>
            <td class="px-4 py-3 text-gray-700">{{ complaint.title }}</td>
            <td class="px-4 py-3 text-gray-700">{{ complaint.status }}</td>
            <td class="px-4 py-3 text-gray-700">{{ complaint.severity }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
