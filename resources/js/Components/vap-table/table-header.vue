<template>
  <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <tr>
      <!-- Checkbox Header -->
      <th scope="col" class="relative px-6 py-3.5">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            :checked="allSelected" 
            @change="toggleSelectAll"
            :class="[
              'h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-900 dark:text-primary-500 focus:ring-2 focus:ring-primary-900/20 dark:focus:ring-primary-500/20 focus:ring-offset-0 transition-colors duration-200',
              allSelected ? 'bg-primary-900 dark:bg-primary-500 border-primary-900 dark:border-primary-500' : 'bg-white dark:bg-gray-700'
            ]"
            :aria-label="allSelected ? $t('Deselect all') : $t('Select all')"
          />
        </div>
      </th>
      
      <!-- Column Headers -->
      <th 
        v-for="column in columns" 
        :key="column.field" 
        @click="column.sortable ? changeSort(column.field) : null"
        :class="[
          'px-6 py-3.5 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider group',
          column.sortable ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150' : '',
          sortField === column.field ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-900 dark:text-primary-400 active-sort-col' : 'text-gray-900 dark:text-gray-300'
        ]"
        :title="column.sortable ? $t('Click to sort') : ''"
      >
        <div class="flex items-center justify-between">
          <!-- Column Label -->
          <span class="truncate">
            {{ $t(column.label) }}
          </span>
          
          <!-- Sort Indicators -->
          <div v-if="column.sortable" class="flex items-center gap-1 ml-2">
            <span v-if="sortField === column.field" class="flex items-center">
              <ArrowLongUpIcon 
                v-if="sortDirection === 'asc'" 
                class="h-4 w-4 text-primary-900 dark:text-primary-400"
                aria-hidden="true"
              />
              <ArrowLongDownIcon 
                v-else 
                class="h-4 w-4 text-primary-900 dark:text-primary-400"
                aria-hidden="true"
              />
            </span>
            <span v-else class="opacity-0 group-hover:opacity-100 transition-opacity duration-150">
              <ArrowLongUpIcon class="h-3 w-3 text-gray-400" aria-hidden="true" />
            </span>
          </div>
          
          <!-- Filter Indicator -->
          <div 
            v-if="column.filterable && column.hasActiveFilter" 
            class="ml-2 flex h-2 w-2 rounded-full bg-gradient-to-r from-primary-900 to-primary-800 dark:from-primary-600 dark:to-primary-500"
            :title="$t('Filter active for') + ' ' + $t(column.label)"
          >
            <span class="sr-only">{{ $t('Filter active') }}</span>
          </div>
        </div>
      </th>
      
      <th scope="col" class="relative px-6 py-3.5 text-right">
        <span class="text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
          {{ $t('gestlab.actions.action') }}
        </span>
      </th>
    </tr>
  </thead>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';
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

<style scoped>
/* Smooth transitions for table header */
th {
  transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
}

/* Custom checkbox focus state - handled by tailwind classes */

/* Column header hover effect - handled by tailwind classes */

/* Sort indicator animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-2px); }
  to { opacity: 1; transform: translateY(0); }
}

.group:hover span:last-child {
  animation: fadeIn 0.2s ease-in-out;
}

/* Active sort column styling */
th.active-sort-col {
  position: relative;
}

th.active-sort-col::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(to bottom, var(--color-primary-900, #1e3a8a), var(--color-primary-700, #1d4ed8));
}

:global(.dark) th.active-sort-col::before {
  background: linear-gradient(to bottom, var(--color-primary-500, #3b82f6), var(--color-primary-400, #60a5fa));
}
</style>