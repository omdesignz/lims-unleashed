<template>
    <div class="absolute left-14 top-0 flex h-12 items-center space-x-3 bg-white dark:bg-gray-900 sm:left-12">
    <button @click="bulkAction(action.id)" type="button" class="inline-flex items-center rounded bg-primary-900 dark:bg-gray-800 px-2 py-1 text-sm font-semibold text-white dark:text-gray-400 shadow-sm ring-1 ring-inset ring-primary-900 dark:ring-gray-700 hover:bg-primary-800 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-primary-900 dark:hover:text-white" 
        v-for="(action, index) in props.actions" 
        :key="index"
                v-motion
                :initial="{ opacity: 0, x: 100 }"
                :enter="{ opacity: 1, x: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
        {{ $t(action.label) }}
    </button>
    </div>
  </template>
  
  <script setup>
  import { defineEmits, defineProps } from 'vue';

  const props = defineProps({
    actions: Array,
  });
  
  const emit = defineEmits(['bulk-action']);
  
  const bulkAction = (action) => {
    emit('bulk-action', action);
  };
  </script>
  