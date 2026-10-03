<template>
    <div class="ds-empty-state grid justify-items-start gap-2 px-6 py-8">
      <h3 class="pl-k">
          {{ name }}
      </h3>
      <p class="ds-copy max-w-md text-sm">
        {{ description }}
      </p>
      <div class="mt-4">
        <button type="button" @click="$emit('create-record')" class="ds-button ds-button-primary">
          {{ $t('gestlab.general.buttons.new_record') }}
          <SquaresPlusIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
  import { Grid2x2Plus as SquaresPlusIcon } from '@lucide/vue';
  const emit = defineEmits(['create-record']);
  
  defineProps({
      name: String,
      description: String,
  });
  
  </script>
