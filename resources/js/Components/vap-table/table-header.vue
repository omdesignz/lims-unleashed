<template>
  <thead class="ds-surface-subtle border-b">
    <tr>
      <!-- Checkbox Header -->
      <th scope="col" class="relative px-6 py-4">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            :checked="allSelected" 
            @change="toggleSelectAll"
            :class="[
              'h-4 w-4 rounded border-[#d8cbb8] text-[rgb(var(--primary-700-rgb))] focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)] focus:ring-offset-0 transition-colors duration-200 dark:border-[#315149] dark:text-[rgb(var(--primary-300-rgb))]',
              allSelected ? 'border-[rgb(var(--primary-800-rgb))] bg-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--primary-500-rgb))] dark:bg-[rgb(var(--primary-500-rgb))]' : 'bg-white dark:bg-[#07110f]'
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
          'group px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em]',
          column.sortable ? 'cursor-pointer transition-colors duration-150 hover:bg-white/80 dark:hover:bg-[#152f29]' : '',
          sortField === column.field ? 'active-sort-col bg-[rgb(var(--primary-50-rgb)/0.75)] text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))]' : 'text-[var(--ds-text-muted)]'
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
                class="h-4 w-4 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-100-rgb))]"
                aria-hidden="true"
              />
              <ArrowLongDownIcon 
                v-else 
                class="h-4 w-4 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-100-rgb))]"
                aria-hidden="true"
              />
            </span>
            <span v-else class="opacity-0 group-hover:opacity-100 transition-opacity duration-150">
              <ArrowLongUpIcon class="h-3 w-3 text-[#8d9b94] dark:text-[#657970]" aria-hidden="true" />
            </span>
          </div>
          
          <!-- Filter Indicator -->
          <div 
            v-if="column.filterable && column.hasActiveFilter" 
            class="ml-2 flex h-2 w-2 rounded-full bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-300-rgb))]"
            :title="$t('Filter active for') + ' ' + $t(column.label)"
          >
            <span class="sr-only">{{ $t('Filter active') }}</span>
          </div>
        </div>
      </th>
    </tr>
  </thead>
</template>

<script setup>
import {
  ArrowLongDownIcon,
  ArrowLongUpIcon,
} from "@heroicons/vue/16/solid";

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
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
  background: linear-gradient(to bottom, rgb(var(--primary-800-rgb)), rgb(var(--primary-500-rgb)));
}

:global(.dark) th.active-sort-col::before {
  background: linear-gradient(to bottom, rgb(var(--primary-300-rgb)), rgb(var(--primary-500-rgb)));
}
</style>
