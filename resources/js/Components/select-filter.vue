<template>
  <div class="flex items-center gap-2">
    <div class="relative flex-1">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <FunnelIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" />
      </div>
      <select
        v-model="filterId"
        @change="$emit('execute', filterId)"
        class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-10 pr-4 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 shadow-sm transition-colors duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
      >
        <option :value="null" disabled class="text-gray-400">
          {{ $t('gestlab.filter.select_filter') }}
        </option>
        <option
          v-for="(filter, index) in filters"
          :value="filter.id"
          :key="index"
        >
          {{ $t(filter.label) }}
        </option>
      </select>

      <div
        v-if="filterId !== null"
        class="absolute -top-1.5 -right-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-primary-800 ring-2 ring-white dark:ring-gray-800"
      >
        <span class="sr-only">{{ $t('gestlab.filter.active') }}</span>
      </div>
    </div>

    <div
      v-if="filterId !== null && filterId !== ''"
      class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 dark:bg-primary-500/10 px-3 py-1.5 text-xs"
    >
      <span class="font-medium text-primary-800 dark:text-primary-300">
        {{ getFilterLabel(filterId) }}
      </span>
      <button
        @click="clearFilter"
        type="button"
        class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 rounded-full p-0.5 transition-colors duration-150"
        :title="$t('gestlab.filter.clear')"
      >
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { FunnelIcon } from '@heroicons/vue/24/outline'

const props = defineProps({ filters: Array })
const emit = defineEmits(['execute'])

const filterId = ref(null)

const getFilterLabel = (id) => {
  const filter = props.filters.find(f => f.id === id)
  return filter ? filter.label : ''
}

const clearFilter = () => {
  filterId.value = null
  emit('execute', null)
}
</script>