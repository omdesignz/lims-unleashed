<template>
    <thead>
        <tr>
            <th scope="col" class="relative px-7 sm:w-12 sm:px-6">
                <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded-full dark:border-gray-700 border-gray-300 text-blue-600 dark:text-gray-400 focus:ring-blue-500 dark:focus:ring-gray-700 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 sm:left-3 cursor-pointer" :checked="allSelected" @change="toggleSelectAll" />
            </th>
            <th v-for="column in columns" :key="column.field" @click="changeSort(column.field)" :class="column.filterable ? 'cursor-pointer select-none' : 'pointer-events-none'" class="min-w-[12rem] py-3.5 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-400">
                {{ column.label }}

                <span v-if="sortField === column.field" class="inline-flex items-center justify-center text-xs font-medium text-blue-900 dark:text-gray-400">
                    <ArrowLongUpIcon v-if="sortDirection === 'asc'" class="w-4 h-4" />
                    <ArrowLongDownIcon v-else class="w-4 h-4" />
                </span>
                
            </th>
        </tr>
    </thead>
  </template>
  
  <script setup>
  import { defineProps, defineEmits, computed } from 'vue';
  import {
  ArrowLongDownIcon,
  ArrowLongUpIcon,
} from "@heroicons/vue/16/solid";
  
  const props = defineProps({
    columns: Array,
    sortField: String,
    sortDirection: String,
    allSelected: Boolean
  });
  const emit = defineEmits(['toggle-select-all', 'change-sort']);
  
  const toggleSelectAll = (event) => {
    emit('toggle-select-all', event);
  };
  
  const changeSort = (field) => {
    emit('change-sort', field);
  };

  </script>
  