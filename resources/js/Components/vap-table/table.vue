<template>
  <div class="space-y-6">
    <!-- TABLE COMMAND SURFACE -->
    <section class="ds-command-surface overflow-hidden">
      <div class="px-4 py-4 sm:px-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0">
            <h2 class="ds-heading text-base">
              {{ $t('gestlab.general.titles.records_list') }}
            </h2>
            <p class="mt-0.5 text-[0.8125rem] text-[var(--ds-text-soft)]">
              {{ props.pagination.total ?? props.data.length }} {{ $t('gestlab.general.labels.records') }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <span class="ds-badge ds-badge-neutral">
              {{ resultSummary }}
            </span>

            <button
              v-if="props.createAction && hasPermission('add_' + props.model)"
              type="button"
              class="ds-button ds-button-primary"
              @click="$emit('create-record')"
            >
              <SquaresPlusIcon class="h-5 w-5" />
              {{ $t("gestlab.general.buttons.new_record") }}
            </button>
          </div>
        </div>

        <div class="mt-3">
          <div class="grid gap-2 xl:grid-cols-[minmax(18rem,1fr)_auto] xl:items-center">
          <div class="relative min-w-0">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
              <MagnifyingGlassIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
            </div>
            <BaseInput
              v-model="filters.globalFilter"
              type="search"
              :placeholder="$t('gestlab.general.search_input_placeholder')"
              class="ds-field pl-10 pr-3"
              @input="updateQuery"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <button
              type="button"
              class="ds-button h-10"
              :class="showFilterPanel
                ? 'ds-button-primary'
                : 'ds-button-secondary'"
              :aria-expanded="showFilterPanel"
              @click="showFilterPanel = !showFilterPanel"
            >
              <FunnelIcon class="h-4 w-4" />
              {{ $t('gestlab.filter.filters') }}
              <span
                v-if="activeFilterCount"
                class="inline-flex min-w-6 items-center justify-center rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-black text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-950-rgb)/0.8)] dark:text-white"
              >
                {{ activeFilterCount }}
              </span>
            </button>

            <ColumnVisibilityToggle
              compact
              :columns="columns"
              @update-columns="updateColumns"
            />

            <div class="relative">
              <BaseSelect
                v-model="perPage"
                @change="changePerPage"
                class="ds-field h-10 min-w-28 py-0"
                aria-label="Registos por página"
              >
                <option value="10" :selected="props.pagination.per_page == 10">10 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
                <option value="25" :selected="props.pagination.per_page == 25">25 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
                <option value="50" :selected="props.pagination.per_page == 50">50 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
                <option value="100" :selected="props.pagination.per_page == 100">100 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
              </BaseSelect>
            </div>
          </div>
          </div>
        </div>

        <!-- Active Filters -->
        <div v-if="activeFilterChips.length" class="mt-4 flex flex-wrap items-center gap-2">
          <div
            v-for="column in activeFilterChips"
            :key="column.field"
            class="inline-flex items-center gap-2 rounded-full border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.75)] px-3 py-1.5 text-xs dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)]"
          >
            <span class="font-semibold text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-100-rgb))]">{{ $t(column.label) }}:</span>
            <span class="font-medium text-[var(--ds-text-muted)]">
              {{ formatFilterValue(column, filters[column.filter_field]) }}
            </span>
            <button
              @click="removeFilter(column.filter_field)"
              class="rounded-full p-0.5 text-[rgb(var(--primary-700-rgb))] hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)] dark:text-[rgb(var(--primary-200-rgb))]"
              :title="$t('gestlab.general.buttons.clear')"
            >
              <XMarkIcon class="h-3 w-3" />
            </button>
          </div>

          <button
            type="button"
            @click="clearActiveFilters"
            class="ds-badge ds-badge-neutral gap-1.5 transition hover:border-[rgb(var(--primary-400-rgb))] hover:text-[var(--ds-text)]"
          >
            <XMarkIcon class="h-3 w-3" />
            {{ $t('gestlab.general.buttons.clear') }}
          </button>
        </div>

        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-show="showFilterPanel"
            class="mt-4 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:p-5"
          >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="ds-table-heading">
                  {{ $t('gestlab.filter.available_filters') }}
                </p>
                <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">
                  {{ visibleFilterableColumns.length }} {{ $t('gestlab.filter.filters') }}
                </p>
              </div>

              <button
                v-if="hasActiveFilters"
                type="button"
                class="ds-button ds-button-secondary min-h-9 rounded-full px-3 py-2 text-xs"
                @click="clearActiveFilters"
              >
                <XMarkIcon class="h-3.5 w-3.5" />
                {{ $t('gestlab.general.buttons.clear') }}
              </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <button
                v-for="column in visibleFilterableColumns"
                :key="column.field"
                @click="toggleFilter(column.filter_field)"
                :class="[
                  'inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                  isFilterActive(column.filter_field)
                    ? 'border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-700-rgb))] text-white dark:border-[rgb(var(--primary-400-rgb))] dark:bg-[rgb(var(--primary-400-rgb))] dark:text-[rgb(var(--primary-950-rgb))]'
                    : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'
                ]"
              >
                <FunnelIcon class="h-3 w-3" />
                {{ $t(column.label) }}
              </button>

              <button
                v-if="props.trashedFilter"
                @click="toggleTrashedFilter"
                :class="[
                  'inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-rose-500/25',
                  filters.trashed
                    ? 'border-rose-600 bg-rose-600 text-white'
                    : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)] hover:text-[var(--ds-text)]'
                ]"
              >
                <TrashIcon class="h-3 w-3" />
                {{ $t('gestlab.general.labels.trashed') }}
              </button>
            </div>

            <div v-if="hasActiveFilters" class="mt-5 border-t border-[var(--ds-border)] pt-5">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <!-- Dynamic Filters -->
                <div
                  v-for="column in visibleFilterableColumns"
                  :key="column.field"
                  v-show="isFilterActive(column.filter_field)"
                  :class="[
                    'space-y-2',
                    column.type === 'remote_select_multiple' ? 'md:col-span-2 lg:col-span-3' : ''
                  ]"
                >
                  <label :for="column.field" class="ds-field-label block">
                    {{ $t(column.label) }}
                    <span v-if="column.required" class="text-red-500 ml-0.5">*</span>
                  </label>

                  <!-- String Filter -->
                  <BaseInput
                    v-if="column.type === 'string'"
                    v-model="filters[column.filter_field]"
                    :id="column.field"
                    :name="column.field"
                    type="text"
                    @input="updateQuery"
                    :placeholder="$t('gestlab.general.search_input_placeholder')"
                    class="ds-field"
                  />

                  <!-- Date Filter -->
                  <DatePicker
                    v-if="column.type === 'date'"
                    v-model.range.string="filters[column.filter_field]"
                    :select-attribute="selectDragAttribute"
                    :drag-attribute="selectDragAttribute"
                    @drag="dragValue = $event"
                    :is-dark="$page.props.darkMode ?? false"
                    :locale="$page.props.auth?.user?.language === 'en' ? 'en-US' : 'pt-PT'"
                    color="primary"
                    mode="date"
                    @update:model-value="updateQuery"
                    :masks="masks"
                  >
                    <template #default="{ togglePopover }">
                      <div class="relative">
                        <BaseInput
                          :value="formatDateRange(column)"
                          type="text"
                          readonly
                          @click="togglePopover"
                          :placeholder="$t('gestlab.general.calendar_input_placeholder')"
                          class="ds-field cursor-pointer pr-11"
                        />
                        <CalendarIcon class="absolute right-3 top-3 h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" />
                      </div>
                    </template>
                  </DatePicker>

                  <!-- Boolean Filter -->
                  <div v-if="column.type === 'boolean'" class="pt-1">
                    <button
                      @click="toggleBooleanFilter(column.filter_field)"
                      :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                        filters[column.filter_field]
                          ? 'bg-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb))]'
                          : 'bg-[var(--ds-border-strong)]'
                      ]"
                    >
                      <span
                        :class="[
                          'inline-block h-5 w-5 transform rounded-full bg-white transition duration-200',
                          filters[column.filter_field] ? 'translate-x-6' : 'translate-x-1'
                        ]"
                      />
                      <span class="sr-only">{{ column.label }}</span>
                    </button>
                    <span class="ml-3 text-sm font-medium text-[var(--ds-text-muted)]">
                      {{ filters[column.filter_field] ? 'Activo' : 'Inactivo' }}
                    </span>
                  </div>

                  <!-- Local Select Filter -->
                  <combobox
                    v-if="column.type === 'select'"
                    :name="column.field"
                    :hasError="false"
                    v-model="filters[column.filter_field]"
                    :options="column.options"
                    @update:model-value="updateQuery"
                    class="w-full"
                  />

                  <!-- Remote Select Filter -->
                  <combobox
                    v-if="column.type === 'remote_select'"
                    :name="column.field"
                    :hasError="false"
                    v-model="filters[column.filter_field]"
                    :load-options="(query, setOptions) => fetchSelectOptions(query, setOptions, column)"
                    @update:model-value="updateQuery"
                    class="w-full"
                  />

                  <!-- Multiple Remote Select Filter -->
                  <comboboxMultiple
                    v-if="column.type === 'remote_select_multiple'"
                    :name="column.field"
                    v-model="filters[column.filter_field]"
                    :multiple="true"
                    :load-options="(query, setOptions) => fetchSelectOptions(query, setOptions, column)"
                    @update:modelValue="updateQuery"
                    class="w-full"
                  />
                </div>

                <!-- Trashed Filter -->
                <div v-if="props.trashedFilter && isFilterActive('trashed')" class="space-y-2">
                  <label for="trashed" class="ds-field-label block">
                    {{ $t('gestlab.general.labels.trashed') }}
                  </label>
                  <combobox
                    name="trashed"
                    :hasError="false"
                    v-model="filters.trashed"
                    :options="props.trashedOptions || []"
                    @update:model-value="updateQuery"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Custom Filters Slot -->
            <div v-if="$slots['specific-filters']" class="mt-5">
              <slot name="specific-filters" />
            </div>
          </div>
        </transition>
      </div>
    </section>

    <!-- DATA TABLE CARD -->
    <DataTableShell :show-summary="Boolean(props.actions?.length && (allSelected || selectedRows.length))">
      <!-- Table Header -->
      <template #summary>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text)]">
            <TableCellsIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.selected_records') }}
            <span class="ds-badge ds-badge-neutral">
              {{ selectedRows.length }} de {{ props.data.length }}
            </span>
          </h2>
          <BulkActions 
            :actions="props.actions"
            :processing="props.actionProcessing"
            @bulk-action="handleBulkAction"
            class="text-sm self-start sm:self-auto"
          />
        </div>
      </template>

      <!-- Table Content -->
      <div class="md:hidden" v-if="props.data.length && visibleColumns.length">
        <div class="divide-y divide-[var(--ds-border)]">
          <article
            v-for="row in props.data"
            :key="row.id"
            class="space-y-4 px-5 py-5 transition-colors duration-150"
            :class="isRowSelected(row.id) ? 'bg-[rgb(var(--primary-50-rgb)/0.6)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)]' : 'bg-[var(--ds-panel)]'"
          >
            <div class="flex items-start justify-between gap-3">
              <label class="flex min-w-0 items-center gap-3">
                <CheckboxInput
                  type="checkbox"
                  :value="row.id"
                  :checked="isRowSelected(row.id)"
                  class="ds-checkbox"
                  @change="toggleSelectRow"
                />
                <div class="min-w-0">
                  <p class="break-words text-sm font-semibold text-[var(--ds-text)]">
                    {{ recordTitle(row, visibleColumns, props.rowTitleField) }}
                  </p>
                  <p class="text-xs font-medium text-[var(--ds-text-soft)]">ID {{ row.id }}</p>
                </div>
              </label>
            </div>

            <dl class="grid grid-cols-1 gap-3">
              <div
                v-for="column in visibleColumns"
                :key="`${row.id}-${column.field}`"
                class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2"
              >
                <dt class="ds-table-heading">
                  {{ column.label }}
                </dt>
                <dd class="mt-1 break-words text-sm font-medium text-[var(--ds-text)]">
                  <slot :name="`column-${column.field}`" :row="row">
                    {{ row[column.field] }}
                  </slot>
                </dd>
              </div>
            </dl>
          </article>
        </div>
      </div>

      <div v-if="!props.data.length" class="p-10 text-center md:hidden">
        <TableCellsIcon class="mx-auto h-11 w-11 text-[var(--ds-text-soft)]" />
        <h3 class="mt-4 text-sm font-semibold text-[var(--ds-text)]">
          {{ $t('gestlab.general.titles.no_records') }}
        </h3>
        <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">
          {{ $t('gestlab.general.titles.start_creating') }}
        </p>
      </div>

      <div class="hidden overflow-x-auto md:block">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)]" v-if="props.data.length && visibleColumns.length">
          <TableHeader
            :columns="visibleColumns"
            :sortField="sortField"
            :sortDirection="sortDirection"
            :allSelected="allSelected"
            @toggle-select-all="toggleSelectAll"
            @change-sort="changeSort"
          />
          <TableBody
            :rows="props.data"
            :columns="visibleColumns"
            :selectedRows="selectedRows"
            @toggle-select-row="toggleSelectRow"
            @single-action="handleSingleAction"
          >
            <template v-for="column in visibleColumns" v-slot:[`column-${column.field}`]="{ row }">
              <slot :name="`column-${column.field}`" :row="row">
                {{ row[column.field] }}
              </slot>
            </template>
          </TableBody>
        </DataTable>
        
        <!-- Empty State -->
        <div v-if="!props.data.length" class="p-12 text-center">
          <TableCellsIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
          <h3 class="mt-4 text-sm font-semibold text-[var(--ds-text)]">
            {{ $t('gestlab.general.titles.no_records') }}
          </h3>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">
            {{ $t('gestlab.general.titles.start_creating') }}
          </p>
          <button
            v-if="props.createAction && hasPermission('add_' + props.model)"
            @click="$emit('create-record')"
            type="button"
            class="ds-button ds-button-primary mt-6"
          >
            <SquaresPlusIcon class="h-5 w-5" />
            {{ $t("gestlab.general.buttons.new_record") }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="props.data.length" class="border-t border-[var(--ds-border)] px-6 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <Pagination
            :links="props.pagination.links"
            :from="props.pagination.from"
            :to="props.pagination.to"
            :total="props.pagination.total"
            :current_page="props.pagination.current_page"
            :last_page="props.pagination.last_page"
          />
        </div>
      </div>
    </DataTableShell>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
  Search as MagnifyingGlassIcon,
  X as XMarkIcon,
  Grid2x2Plus as SquaresPlusIcon,
  Calendar as CalendarIcon,
  Funnel as FunnelIcon,
  Trash2 as TrashIcon,
  Table as TableCellsIcon,
} from "@lucide/vue";
import debounce from "lodash/debounce";
import { usePage, router } from "@inertiajs/vue3";

import ColumnVisibilityToggle from "@/Components/vap-table/column-visibility-toggle.vue";
import BulkActions from "@/Components/vap-table/bulk-actions.vue";
import DataTableShell from "@/Components/tables/DataTableShell.vue";
import TableHeader from "@/Components/vap-table/table-header.vue";
import TableBody from "@/Components/vap-table/table-body.vue";
import Pagination from "@/Components/pagination.vue";
import { usePermission } from "@/Composables/usePermissions";
import { loadSelectOptions } from "@/Utils/selectOptions";
import { recordTitle } from "@/Utils/recordTitle";
import { DatePicker } from 'v-calendar'
import combobox from '@/Components/vap-table/combobox.vue';
import comboboxMultiple from '@/Components/vap-table/combobox-multiple.vue';
import 'v-calendar/dist/style.css';

const { hasPermission } = usePermission();

const page = usePage();

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    default: () => [],
  },
  rowTitleField: {
    type: String,
    default: "",
  },
  actions: {
    type: Array,
    default: () => [],
  },
  actionProcessing: Boolean,
  query: Object,
  filters: Array,
  trashedFilter: Boolean,
  trashedOptions: Array,
  initialFilters: Object,
  initialSortField: String,
  initialSortDirection: String,
  initialIncludes: Array,
  initialGlobalFilter: String,
  pagination: {
    type: Object,
    default: () => ({}),
  },
  slideOverEdit: Boolean,
  model: {
    type: String,
    default: "",
  },
  abilities: {
    type: Array,
    default: [],
  },
  createAction: {
    type: Boolean,
    default: true,
  }
});

const emit = defineEmits(["execute-bulk-action", "slideover-on", "create-record", "update-selected-ids"]);

const columns = ref([...(props.columns || [])]);
const filters = ref({
  ...(props.initialFilters || {}),
  globalFilter: props.initialGlobalFilter || props.initialFilters?.globalFilter || '',
});
const sortField = ref(props.initialSortField || '');
const sortDirection = ref(props.initialSortDirection || 'asc');
const includes = ref(props.initialIncludes || []);
const selectedRows = ref([]);
const activeFilters = ref([]);
const dragValue = ref(null);
const perPage = ref(props.pagination?.per_page || 10);
const showFilterPanel = ref(false);

const visibleColumns = computed(() => columns.value.filter(column => column.visible));
const visibleFilterableColumns = computed(() => visibleColumns.value.filter(column => column.filterable));
const allSelected = computed(() => props.data.length > 0 && props.data.every(row => isRowSelected(row.id)));
const resultSummary = computed(() => {
  const total = props.pagination?.total ?? props.data.length;
  const from = props.pagination?.from ?? (total ? 1 : 0);
  const to = props.pagination?.to ?? props.data.length;

  return `${from}–${to} de ${total}`;
});
const hasActiveFilters = computed(() => activeFilters.value.length > 0);
const activeFilterCount = computed(() => activeFilters.value.length);
const activeFilterChips = computed(() => visibleFilterableColumns.value.filter(column => {
  return isFilterActive(column.filter_field) && hasFilterValue(filters.value[column.filter_field]);
}));
const selectedRecordIds = computed(() => selectedRows.value.map(selectedRow => {
  const row = props.data.find(item => rowKey(item.id) === selectedRow);

  return row?.id ?? selectedRow;
}));

const selectDragAttribute = {
  highlight: {
    color: 'primary',
    fillMode: 'light',
    contentClass: 'bg-primary-500 text-white',
  },
  contentStyle: {
    color: 'black',
  },
};

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});

// Helper functions
const rowKey = id => String(id);

const isRowSelected = id => selectedRows.value.includes(rowKey(id));

const emitSelectedRows = () => {
  emit("update-selected-ids", selectedRecordIds.value);
};

const hasFilterValue = value => {
  if (Array.isArray(value)) {
    return value.length > 0;
  }

  if (value && typeof value === 'object') {
    return Object.values(value).some(item => item !== null && item !== undefined && item !== '');
  }

  return value !== null && value !== undefined && value !== '';
};

const currentSort = () => {
  return sortField.value
    ? (sortDirection.value === 'asc' ? sortField.value : `-${sortField.value}`)
    : '';
};

const formatDateRange = (column) => {
  const value = filters.value[column.filter_field];
  if (!value || (!value.start && !value.end)) return '';
  return `${value.start || ''} - ${value.end || ''}`;
};

const formatFilterValue = (column, value) => {
  if (Array.isArray(value)) {
    return value.join(', ');
  }
  if (typeof value === 'object' && value !== null) {
    if (value.start || value.end) {
      return `${value.start || ''} - ${value.end || ''}`;
    }
    return JSON.stringify(value);
  }
  if (typeof value === 'boolean') {
    return value ? 'Sim' : 'Não';
  }
  return value;
};

const fetchSelectOptions = async (query, setOptions, column) => {
  return loadSelectOptions(
    column.config.url,
    query,
    setOptions,
    result => ({
      value: result[column.config.value],
      label: result[column.config.label],
    }),
  );
};

const changeSort = (field) => {
  if (sortField.value === field) {
    if (sortDirection.value === 'asc') {
      sortDirection.value = 'desc';
    } else {
      sortField.value = '';
      sortDirection.value = 'asc';
    }
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
  updateQuery();
};

const changePerPage = () => {
  router.get(page.url, {
    page: 1,
    per_page: perPage.value,
    filter: filters.value,
    sort: currentSort(),
    includes: includes.value,
    globalFilter: filters.value.globalFilter || ''
  }, {
    preserveScroll: false,
    preserveState: true,
    replace: true
  });
};

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedRows.value = props.data.map(item => rowKey(item.id));
  } else {
    selectedRows.value = [];
  }
  emitSelectedRows();
};

const updateQuery = debounce(() => {
  router.get(page.url, {
    filter: filters.value,
    sort: currentSort(),
    includes: includes.value,
    globalFilter: filters.value.globalFilter || '',
    per_page: perPage.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300);

const toggleFilter = (field) => {
  showFilterPanel.value = true;

  if (activeFilters.value.includes(field)) {
    activeFilters.value = activeFilters.value.filter(f => f !== field);
    removeFilter(field);
  } else {
    activeFilters.value.push(field);
  }
};

const isFilterActive = (field) => {
  return activeFilters.value.includes(field);
};

const toggleSelectRow = (event) => {
  const id = rowKey(event.target.value);

  if (event.target.checked) {
    selectedRows.value = [...new Set([...selectedRows.value, id])];
  } else {
    selectedRows.value = selectedRows.value.filter(rowId => rowId !== id);
  }
  emitSelectedRows();
};

const handleBulkAction = (action) => {
  emit("execute-bulk-action", {
    action: action,
    actionType: 'bulk',
  });
};

const handleSingleAction = ({ action, id }) => {
  emit("execute-bulk-action", {
    action: action,
    actionType: 'single',
    id: id
  });
};

const updateColumns = (updatedColumns) => {
  columns.value = updatedColumns;
  updateQuery();
};

const removeFilter = (field) => {
  filters.value[field] = '';
  updateQuery();
  if (field !== 'globalFilter') {
    activeFilters.value = activeFilters.value.filter(f => f !== field);
  }
};

const clearActiveFilters = () => {
  activeFilters.value.forEach(field => {
    filters.value[field] = '';
  });

  activeFilters.value = [];
  updateQuery();
};

const toggleBooleanFilter = (field) => {
  filters.value[field] = !filters.value[field];
  updateQuery();
};

const toggleTrashedFilter = () => {
  showFilterPanel.value = true;
  filters.value.trashed = !filters.value.trashed;
  if (filters.value.trashed && !isFilterActive('trashed')) {
    activeFilters.value.push('trashed');
  } else if (!filters.value.trashed && isFilterActive('trashed')) {
    activeFilters.value = activeFilters.value.filter(f => f !== 'trashed');
  }
  updateQuery();
};

const hydrateActiveFiltersFromValues = () => {
  const filterFields = visibleFilterableColumns.value
    .map(column => column.filter_field)
    .filter(field => hasFilterValue(filters.value[field]));

  if (props.trashedFilter && hasFilterValue(filters.value.trashed)) {
    filterFields.push('trashed');
  }

  activeFilters.value = [...new Set([...activeFilters.value, ...filterFields])];
};

// Watch for changes
watch(
  [() => filters.value, () => sortField.value, () => sortDirection.value, () => includes.value],
  updateQuery,
  { deep: true }
);

watch(
  () => props.pagination?.per_page,
  value => {
    perPage.value = value || perPage.value;
  }
);

// Initialize filters from query
onMounted(() => {
  if (props.query) {
    if (props.query.filter) {
      filters.value = { ...filters.value, ...props.query.filter };
    }
    if (props.query.sort) {
      const sort = props.query.sort;
      if (sort.startsWith('-')) {
        sortField.value = sort.substring(1);
        sortDirection.value = 'desc';
      } else {
        sortField.value = sort;
        sortDirection.value = 'asc';
      }
    }

  }

  hydrateActiveFiltersFromValues();
});
</script>

<style scoped>
/* Custom scrollbar for table */
div.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

div.overflow-x-auto::-webkit-scrollbar-track {
  background: var(--ds-panel-subtle);
  border-radius: 3px;
}

div.overflow-x-auto::-webkit-scrollbar-thumb {
  background: var(--ds-border-strong);
  border-radius: 3px;
}

div.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: rgb(var(--primary-500-rgb));
}

/* Smooth transitions */
button, input, select {
  transition: all 0.2s ease-in-out;
}

/* Date picker popover styling */
:deep(.vc-popover-content) {
  border-radius: var(--ds-radius-card) !important;
  border: 1px solid var(--ds-border) !important;
  background: var(--ds-panel-raised) !important;
  box-shadow: var(--ds-shadow-panel) !important;
}

:global(.dark) :deep(.vc-popover-content) {
  border-color: var(--ds-border) !important;
  background: var(--ds-panel-raised) !important;
}
</style>
