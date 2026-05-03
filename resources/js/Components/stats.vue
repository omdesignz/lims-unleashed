<script setup>
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    data: Array
});
const stats = [
  { name: 'gestlab.stats.collections.total_number_collections', value: '405' },
  { name: 'gestlab.stats.collections.total_number_analysed_collections', value: '189' },
  { name: 'gestlab.stats.collections.total_number_pending_collections', value: '216' },
  { name: 'gestlab.stats.collections.average_response_time', value: '3.65', unit: 'mins' },
]
</script>

<template>
    <div class="bg-blue-900 rounded-md mt-2">
        <div class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-px sm:grid-cols-2 lg:grid-cols-4 rounded-lg overflow-hidden">
            <div v-for="stat in props.data" :key="stat.name" class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t(stat.name) }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ stat.value }}</span>
                <span v-if="stat.unit" class="text-sm text-white">{{ stat.unit }}</span>
            </p>
            </div>
        </div>
        </div>
    </div>
</template>