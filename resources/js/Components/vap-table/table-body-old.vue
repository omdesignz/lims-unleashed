<template>
    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
        <tr v-for="row in rows" :key="row.id">
            <td class="relative px-7 sm:w-12 sm:px-6">
                <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded-full border-blue-900 dark:border-gray-700 text-blue-900 dark:text-gray-400 focus:ring-blue-900 dark:focus:ring-gray-700 cursor-pointer" :value="row.id" v-model="props.selectedRows" @change="toggleSelectRow" />
            </td>
            <td class="whitespace-nowrap py-4 text-sm text-gray-500 dark:text-gray-400" v-for="column in columns" :key="column.field">
                <template v-if="column.visible">
                    <slot :name="`column-${column.field}`" :row="row">
                        {{ row[column.field] }}
                    </slot>
                </template>
            </td>
        </tr>
    </tbody>
  </template>
  
  <script setup>
  import { defineProps, defineEmits, computed } from 'vue';
  
  const props = defineProps({
    rows: Array,
    columns: Array,
    selectedRows: Array
  });
  const emit = defineEmits(['single-action', 'toggle-single-row']);
  
  const hasActions = computed(() => props.columns.some(column => column.field === 'actions'));
  
  const emitAction = (action, id) => {
    emit('single-action', { action, id });
  };

  const toggleSelectRow = (event) => {
    emit('toggle-select-row', event);
  };
  </script>  