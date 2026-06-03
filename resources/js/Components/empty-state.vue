<template>
    <div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-[#fffdf7]/80 px-6 py-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.16)] dark:text-[rgb(var(--primary-200-rgb))]">
        <SquaresPlusIcon class="h-6 w-6" aria-hidden="true" />
      </div>
      <h3 class="mt-4 text-base font-black tracking-tight text-slate-950 dark:text-slate-100">
          {{ name }}
      </h3>
      <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">
        {{ description }}
      </p>
      <div class="mt-6">
        <button type="button" @click="$emit('create-record')" class="inline-flex items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 dark:focus:ring-offset-slate-950">
          <SquaresPlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          Novo registo
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { SquaresPlusIcon } from '@heroicons/vue/24/outline';
  const emit = defineEmits(['create-record']);
  
  defineProps({
      name: String,
      description: String,
  });
  
  </script>
