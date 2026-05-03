<template>
    <div class="grid grid-cols-4 gap-6">
      <div
        v-for="file in props.files"
        :key="file.name"
        class="flex flex-col items-center"
      >
        <div class="bg-white shadow-md rounded-lg p-4 w-full flex justify-center items-center">
          <svg v-if="file.type === 'folder'" class="h-10 w-10 text-blue-500">
            <!-- Folder Icon -->
          </svg>
          <svg v-else class="h-10 w-10 text-yellow-500">
            <!-- File Icon -->
          </svg>
        </div>
        <span class="mt-2 text-sm font-medium text-gray-600">{{ file.name }}</span>
      </div>
    </div>
  </template>
  
  <script setup>
  const props = defineProps({
    files: {
      type: Array,
      required: true,
      default: () => [],
      },
  });
  </script>
  