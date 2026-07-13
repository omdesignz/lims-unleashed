<template>
  <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
    <tr
      v-for="row in rows"
      :key="row.id"
      :class="[
        'ds-table-row transition-colors duration-150',
        isSelected(row.id)
          ? 'bg-[rgb(var(--primary-50-rgb)/0.65)] hover:bg-[rgb(var(--primary-50-rgb)/0.9)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.18)]'
          : 'hover:bg-[var(--ds-panel-subtle)]'
      ]"
    >
      <!-- Checkbox Cell -->
      <td class="relative w-14 px-5 py-4">
        <div class="flex items-center">
          <CheckboxInput
            type="checkbox"
            :value="row.id"
            :checked="isSelected(row.id)"
            @change="toggleSelectRow"
            class="ds-checkbox"
            :aria-label="isSelected(row.id) ? $t('Deselect record :id', { id: row.id }) : $t('Select record :id', { id: row.id })"
          />
        </div>
      </td>

      <!-- Data Cells -->
      <td
        v-for="column in visibleColumns"
        :key="column.field"
        class="ds-table-cell whitespace-nowrap px-5 py-4"
        :class="[
          'text-[var(--ds-text-muted)]',
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

        <div v-else-if="column.type === 'date'" class="font-medium text-[var(--ds-text-muted)]">
          {{ formatDate(row[column.field]) }}
        </div>

        <div v-else-if="column.type === 'currency'" class="font-medium text-[var(--ds-text-muted)]">
          {{ formatCurrency(row[column.field]) }}
        </div>

        <div v-else-if="column.type === 'status'" class="flex items-center gap-2">
          <span
            class="inline-block h-2 w-2 rounded-full"
            :class="getStatusColor(row[column.field])"
          />
          <span class="font-medium text-[var(--ds-text-muted)]">
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
              class="flex h-8 w-8 items-center justify-center rounded-full bg-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb))]"
            >
              <span class="text-xs font-semibold text-white uppercase">
                {{ getInitials(row[column.field]) }}
              </span>
            </div>
          </div>
          <div class="ml-3">
            <div class="font-medium text-[var(--ds-text-muted)]">
              {{ row[column.field] }}
            </div>
            <div v-if="column.subtitle_field" class="text-sm text-[var(--ds-text-soft)]">
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
          <div class="font-medium text-[var(--ds-text-muted)]">
            {{ row[column.field] }}
          </div>
        </slot>

        <!-- Subtitle for the cell if defined -->
        <div
          v-if="column.subtitle_field"
          class="mt-1 text-xs font-medium text-[var(--ds-text-soft)]"
        >
          {{ row[column.subtitle_field] }}
        </div>
      </td>

    </tr>
  </tbody>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    default: () => [],
  },
  selectedRows: {
    type: Array,
    default: () => [],
  }
});

const emit = defineEmits(['single-action', 'toggle-select-row']);

const visibleColumns = computed(() =>
  props.columns.filter(column => column.visible !== false)
);

const isSelected = (id) => props.selectedRows.some(rowId => String(rowId) === String(id));

const toggleSelectRow = (event) => {
  emit('toggle-select-row', event);
};

// Helper functions for different column types
const getBadgeClass = (value) => {
  const badgeClasses = {
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200',
    inactive: 'ds-badge ds-badge-neutral',
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
    completed: 'bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:text-[rgb(var(--primary-100-rgb))]',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-200',
    draft: 'ds-badge ds-badge-neutral',
    published: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200',
  };
  return badgeClasses[value] || 'ds-badge ds-badge-neutral';
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
    completed: 'bg-[rgb(var(--primary-500-rgb))]',
    cancelled: 'bg-red-500',
  };
  return statusColors[status] || 'bg-[var(--ds-text-soft)]';
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

/* Action button animations */
button {
  transition: all 0.2s ease-in-out;
}

/* Custom focus styles for accessibility */
button:focus-visible {
  outline: 2px solid rgb(var(--primary-500-rgb));
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
