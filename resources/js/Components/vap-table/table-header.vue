<template>
  <thead class="ds-table-head border-b border-[var(--ds-border)]">
    <tr>
      <!-- Checkbox Header -->
      <th scope="col" class="relative w-14 px-5 py-3.5">
        <div class="flex items-center">
          <CheckboxInput
            type="checkbox"
            :checked="allSelected"
            @change="toggleSelectAll"
            class="ds-checkbox"
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
          'ds-table-heading group px-5 py-3.5 text-left',
          column.sortable ? 'cursor-pointer transition-colors hover:bg-[var(--ds-panel-raised)]' : '',
          sortField === column.field ? 'active-sort-col bg-[rgb(var(--primary-50-rgb)/0.72)] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.1)] dark:text-[rgb(var(--primary-200-rgb))]' : ''
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
              <ArrowLongUpIcon class="h-3 w-3 text-[var(--ds-text-soft)]" aria-hidden="true" />
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
