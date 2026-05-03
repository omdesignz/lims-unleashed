<template>
    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Name
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Last Updated
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="file in props.files" :key="file.name">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              <svg v-if="file.type === 'folder'" class="inline-block h-5 w-5 text-blue-500">
                <!-- Folder Icon -->
              </svg>
              <svg v-else class="inline-block h-5 w-5 text-yellow-500">
                <!-- File Icon -->
              </svg>
              {{ file.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.updated }}</td>
          </tr>
        </tbody>
      </table>
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
  