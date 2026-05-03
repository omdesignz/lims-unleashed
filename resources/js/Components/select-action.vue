<template>
  <div class="flex items-center gap-2.5">
    <div class="relative flex-1">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
        </svg>
      </div>
      <select
        :disabled="!recordIds.length"
        v-model="actionId"
        :class="[
          'block w-full rounded-xl py-2.5 pl-10 pr-4 text-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
          recordIds.length
            ? 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500/20'
            : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-gray-400 dark:text-gray-500 cursor-not-allowed',
        ]"
      >
        <option :value="null" disabled class="text-gray-400">
          {{ $t('gestlab.actions.select_action') }}
        </option>
        <option
          v-for="(action, index) in actions"
          :value="action.id"
          :key="index"
          class="text-gray-900 dark:text-gray-100"
        >
          {{ $t(action.label) }}
        </option>
      </select>

      <div
        v-if="recordIds.length"
        class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-primary-800 dark:bg-primary-600 text-xs font-semibold text-white shadow-sm ring-2 ring-white dark:ring-gray-800"
      >
        {{ recordIds.length }}
      </div>
    </div>

    <button
      @click="$emit('execute', actionId)"
      :disabled="!actionId || !recordIds.length"
      :class="[
        'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
        actionId && recordIds.length
          ? 'bg-primary-800 dark:bg-primary-600 text-white hover:bg-primary-700 dark:hover:bg-primary-500'
          : 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 cursor-not-allowed',
      ]"
    >
      <CursorArrowRippleIcon class="h-4 w-4" />
      {{ $t('gestlab.actions.apply') }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { CursorArrowRippleIcon } from '@heroicons/vue/24/outline'

defineProps({
  actions: Array,
  recordIds: { type: Array, default: [] },
})

const emit = defineEmits(['execute'])
const actionId = ref(null)
</script>