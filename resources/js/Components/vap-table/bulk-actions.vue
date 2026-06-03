<template>
    <div class="static flex min-h-12 flex-wrap items-center gap-2 rounded-2xl border border-[#d8cbb8] bg-[#fffdf7]/95 p-1.5 shadow-sm dark:border-[#315149] dark:bg-[#07110f]/95 sm:absolute sm:left-12 sm:top-0">
    <button @click="bulkAction(action.id)" type="button" class="inline-flex items-center rounded-xl bg-[rgb(var(--primary-800-rgb))] px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-[rgb(var(--primary-800-rgb))] transition hover:bg-[rgb(var(--primary-700-rgb))] disabled:cursor-not-allowed disabled:opacity-30 dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:ring-[rgb(var(--primary-500-rgb))] dark:hover:bg-[rgb(var(--primary-300-rgb))]" 
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
  
