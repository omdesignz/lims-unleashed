<template>
    <div class="ds-empty-state px-6 py-10 text-center">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.16)] dark:text-[rgb(var(--primary-200-rgb))]">
        <SquaresPlusIcon class="h-6 w-6" aria-hidden="true" />
      </div>
      <h3 class="ds-heading mt-4 text-base">
          {{ name }}
      </h3>
      <p class="ds-copy mx-auto mt-2 max-w-md text-sm">
        {{ description }}
      </p>
      <div class="mt-6">
        <button type="button" @click="$emit('create-record')" class="ds-button ds-button-primary">
          <SquaresPlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          {{ $t('gestlab.general.buttons.new_record') }}
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
