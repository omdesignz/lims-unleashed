<template>
  <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
    <tr 
      v-for="row in rows" 
      :key="row.id" 
      :class="[
        'transition-colors duration-150',
        isSelected(row.id) 
          ? 'bg-primary-50/50 dark:bg-primary-900/20 hover:bg-primary-100/50 dark:hover:bg-primary-900/40' 
          : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
      ]"
    >
      <!-- Checkbox Cell -->
      <td class="relative px-6 py-4">
        <div class="flex items-center">
          <input 
            type="checkbox" 
            :value="row.id" 
            :checked="isSelected(row.id)"
            @change="toggleSelectRow"
            :class="[
              'h-4 w-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-primary-900 dark:text-primary-500 focus:ring-2 focus:ring-primary-900/20 dark:focus:ring-primary-500/20 focus:ring-offset-0 transition-colors duration-200',
              isSelected(row.id) ? 'bg-primary-900 dark:bg-primary-500 border-primary-900 dark:border-primary-500' : 'bg-white dark:bg-gray-700'
            ]"
            :aria-label="isSelected(row.id) ? $t('Deselect record :id', { id: row.id }) : $t('Select record :id', { id: row.id })"
          />
        </div>
      </td>
      
      <!-- Data Cells -->
      <td 
        v-for="column in visibleColumns" 
        :key="column.field" 
        class="px-6 py-4 whitespace-nowrap text-sm"
        :class="[
          'text-gray-900 dark:text-gray-100',
          column.align === 'right' ? 'text-right' : 'text-left',
          column.nowrap === false ? 'whitespace-normal' : 'whitespace-nowrap'
        ]"
      >
        <div class="flex items-center" v-if="column.type === 'badge'">
          <span 
            :class="[
              'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
              getBadgeClass(row[column.field])
            ]"
          >
            {{ getBadgeText(row[column.field]) }}
          </span>
        </div>
        
        <div v-else-if="column.type === 'date'" class="font-medium text-gray-900 dark:text-gray-100">
          {{ formatDate(row[column.field]) }}
        </div>
        
        <div v-else-if="column.type === 'currency'" class="font-medium text-gray-900 dark:text-gray-100">
          {{ formatCurrency(row[column.field]) }}
        </div>
        
        <div v-else-if="column.type === 'status'" class="flex items-center gap-2">
          <span 
            class="inline-block h-2 w-2 rounded-full"
            :class="getStatusColor(row[column.field])"
          />
          <span class="font-medium text-gray-900 dark:text-gray-100">
            {{ row[column.field] }}
          </span>
        </div>
        
        <div v-else-if="column.type === 'avatar'" class="flex items-center">
          <div class="flex-shrink-0">
            <img 
              v-if="row[column.avatar_field]" 
              :src="row[column.avatar_field]" 
              :alt="row[column.field]"
              class="h-8 w-8 rounded-full"
            />
            <div 
              v-else 
              class="h-8 w-8 rounded-full bg-gradient-to-r from-primary-900 to-primary-800 dark:from-primary-600 dark:to-primary-500 flex items-center justify-center"
            >
              <span class="text-xs font-semibold text-white uppercase">
                {{ getInitials(row[column.field]) }}
              </span>
            </div>
          </div>
          <div class="ml-3">
            <div class="font-medium text-gray-900 dark:text-gray-100">
              {{ row[column.field] }}
            </div>
            <div v-if="column.subtitle_field" class="text-sm text-gray-500 dark:text-gray-400">
              {{ row[column.subtitle_field] }}
            </div>
          </div>
        </div>
        
        <!-- Slot for custom content -->
        <slot 
          v-else
          :name="`column-${column.field}`" 
          :row="row"
        >
          <div class="font-medium text-gray-900 dark:text-gray-100">
            {{ row[column.field] }}
          </div>
        </slot>
        
        <!-- Subtitle for the cell if defined -->
        <div 
          v-if="column.subtitle_field" 
          class="mt-1 text-xs text-gray-500 dark:text-gray-400"
        >
          {{ row[column.subtitle_field] }}
        </div>
      </td>
      
      <!-- Actions Cell -->
      
    </tr>
  </tbody>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import {
  EyeIcon,
  PencilSquareIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  rows: Array,
  columns: Array,
  selectedRows: Array
});

const emit = defineEmits(['single-action', 'toggle-select-row']);

const visibleColumns = computed(() => 
  props.columns.filter(column => column.visible !== false)
);

const isSelected = (id) => props.selectedRows.includes(id);

const emitAction = (action, id) => {
  emit('single-action', { action, id });
};

const toggleSelectRow = (event) => {
  emit('toggle-select-row', event);
};

// Helper functions for different column types
const getBadgeClass = (value) => {
  const badgeClasses = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    published: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  };
  return badgeClasses[value] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getBadgeText = (value) => {
  const badgeTexts = {
    active: trans('Ativo'),
    inactive: trans('Inativo'),
    pending: trans('Pendente'),
    completed: trans('Concluído'),
    cancelled: trans('Cancelado'),
    draft: trans('Rascunho'),
    published: trans('Publicado'),
  };
  return badgeTexts[value] || value;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    const locale = usePage().props.auth?.user?.language === 'en' ? 'en-US' : 'pt-PT';
    return date.toLocaleDateString(locale, {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return dateString;
  }
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return '-';
  const locale = usePage().props.auth?.user?.language === 'en' ? 'en-US' : 'pt-PT';
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'AOA'
  }).format(value);
};

const getStatusColor = (status) => {
  const statusColors = {
    active: 'bg-green-500',
    inactive: 'bg-gray-500',
    pending: 'bg-yellow-500',
    completed: 'bg-blue-500',
    cancelled: 'bg-red-500',
  };
  return statusColors[status] || 'bg-gray-400';
};

const getInitials = (name) => {
  if (!name) return '??';
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase();
};
</script>

<style scoped>
/* Smooth row hover transitions */
tr {
  transition: background-color 0.15s ease-in-out;
}

/* Checkbox animation */
input[type="checkbox"] {
  transition: all 0.2s ease-in-out;
}

/* Action button animations */
button {
  transition: all 0.2s ease-in-out;
}

/* Custom focus styles for accessibility */
button:focus-visible {
  outline: 2px solid var(--color-primary-900, #1e3a8a);
  outline-offset: 2px;
}

/* Avatar image fallback styling */
img {
  object-fit: cover;
}

/* Status dot animation */
span.inline-block {
  transition: background-color 0.3s ease-in-out;
}

/* Cell content truncation for long text */
.whitespace-nowrap {
  overflow: hidden;
  text-overflow: ellipsis;
}

.whitespace-normal {
  word-wrap: break-word;
  max-width: 200px;
}
</style>
