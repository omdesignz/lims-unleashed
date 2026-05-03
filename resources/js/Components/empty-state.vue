<template>
    <div class="text-center">
      <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ name }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ description }}
      </p>
      <div class="mt-6">
        <button type="button" @click="$emit('create-record')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-900 hover:bg-ft-gray focus:outline-none">
          <SquaresPlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          Novo registro
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