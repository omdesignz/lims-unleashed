<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  reviews: Object,
  stats: Object,
});
</script>

<template>
  <div class="space-y-6 p-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Revisões Pela Gestão</h1>
      <p class="mt-1 text-sm text-gray-600">Planeamento, decisões e acompanhamento das revisões de topo.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="text-sm text-gray-500">Planeadas</div>
        <div class="mt-2 text-2xl font-semibold text-sky-700">{{ stats.planned }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="text-sm text-gray-500">Em curso</div>
        <div class="mt-2 text-2xl font-semibold text-amber-700">{{ stats.in_progress }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="text-sm text-gray-500">Concluídas</div>
        <div class="mt-2 text-2xl font-semibold text-emerald-700">{{ stats.completed }}</div>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Referência</th>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Data</th>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Estado</th>
            <th class="px-4 py-3 text-left font-medium text-gray-600">Âmbito</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="review in reviews.data" :key="review.id">
            <td class="px-4 py-3 text-gray-900">{{ review.reference }}</td>
            <td class="px-4 py-3 text-gray-700">{{ review.review_date }}</td>
            <td class="px-4 py-3 text-gray-700">{{ review.status }}</td>
            <td class="px-4 py-3 text-gray-700">{{ review.scope }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
