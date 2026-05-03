<template>
  <div class="space-y-6">
    <!-- HEADER CARD -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <TableCellsIcon class="h-7 w-7 text-primary-900 dark:text-primary-400" />
            {{ $t('gestlab.general.titles.records_list') }}
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            {{ $t('gestlab.general.labels.records_found') }}
            <span class="font-semibold text-primary-900 dark:text-primary-400">{{ props.pagination.total }}</span>
            {{ $t('gestlab.general.labels.records') }}
          </p>
        </div>
        
        <div class="flex items-center gap-3">
          <!-- Items per page -->
          <div class="relative">
            <select 
              v-model="props.pagination.per_page" 
              @change="changePerPage"
              :class="[
                'block rounded-lg border py-2.5 pl-3 pr-10 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:border-primary-900 dark:hover:border-primary-500'
              ]"
            >
              <option value="10" :selected="props.pagination.per_page == 10">{{ $t('10 per page') }}</option>
              <option value="25" :selected="props.pagination.per_page == 25">{{ $t('25 per page') }}</option>
              <option value="50" :selected="props.pagination.per_page == 50">{{ $t('50 per page') }}</option>
              <option value="100" :selected="props.pagination.per_page == 100">{{ $t('100 per page') }}</option>
            </select>
          </div>
          
          <!-- Create Record Button -->
          <button
            v-if="props.createAction && hasPermission('add_' + props.model)"
            @click="$emit('create-record')"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-primary-900 to-primary-800 dark:from-primary-600 dark:to-primary-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-primary-800 hover:to-primary-700 dark:hover:from-primary-500 dark:hover:to-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200"
          >
            <SquaresPlusIcon class="h-5 w-5" />
            {{ $t("gestlab.general.buttons.new_record") }}
          </button>
        </div>
      </div>
    </div>

    <!-- SEARCH AND FILTERS CARD -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <!-- Search Bar -->
      <div class="mb-6">
        <div class="relative max-w-full sm:max-w-md">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
          </div>
          <input
            v-model="filters.globalFilter"
            type="search"
            :placeholder="$t('gestlab.general.search_input_placeholder')"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 py-2.5 pl-10 pr-3 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-primary-900 dark:focus:border-primary-500 focus:ring-2 focus:ring-primary-900/20 dark:focus:ring-primary-500/20 focus:outline-none transition-colors duration-200"
            @input="updateQuery"
          />
        </div>
      </div>

      <!-- Quick Filters -->
      <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
        <!-- Filter Toggles -->
        <div class="flex flex-wrap gap-2">
          <button
            v-for="column in visibleFilterableColumns"
            :key="column.field"
            @click="toggleFilter(column.filter_field)"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
              isFilterActive(column.filter_field)
                ? 'bg-gradient-to-r from-primary-900 to-primary-800 dark:from-primary-600 dark:to-primary-500 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            <FunnelIcon class="h-3 w-3" />
            {{ $t(column.label) }}
          </button>
          
          <!-- Trashed Filter -->
          <button
            v-if="props.trashedFilter"
            @click="toggleTrashedFilter"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
              filters.trashed
                ? 'bg-gradient-to-r from-red-600 to-red-500 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            <TrashIcon class="h-3 w-3" />
            {{ $t('gestlab.general.labels.trashed') }}
          </button>
        </div>
        
        <!-- Column Visibility Toggle -->
        <ColumnVisibilityToggle 
          :columns="columns" 
          @update-columns="updateColumns"
          class="xl:ml-auto"
        />
      </div>

      <!-- Active Filters -->
      <div class="mt-4 flex flex-wrap gap-2">
        <div 
          v-for="(column, index) in visibleFilterableColumns"
          :key="column.field"
          v-show="isFilterActive(column.filter_field) && filters[column.filter_field]"
          class="inline-flex items-center gap-2 rounded-full bg-primary-50 dark:bg-primary-900/30 px-3 py-1.5 text-xs border border-primary-100 dark:border-primary-800"
        >
          <span class="font-medium text-primary-900 dark:text-primary-300">{{ $t(column.label) }}:</span>
          <span class="text-gray-700 dark:text-gray-300">
            {{ formatFilterValue(column, filters[column.filter_field]) }}
          </span>
          <button
            @click="removeFilter(column.filter_field)"
            class="text-primary-700 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-200 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800 rounded-full p-0.5"
            :title="$t('gestlab.general.buttons.clear')"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>
        </div>
      </div>

      <!-- Detailed Filters Section -->
      <div v-if="hasActiveFilters" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
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
            <label :for="column.field" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t(column.label) }}
              <span v-if="column.required" class="text-red-500 ml-0.5">*</span>
            </label>

            <!-- String Filter -->
            <input
              v-if="column.type === 'string'"
              v-model="filters[column.filter_field]"
              :id="column.field"
              :name="column.field"
              type="text"
              @input="updateQuery"
              :placeholder="$t('gestlab.general.search_input_placeholder')"
              class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-2.5 px-3 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-primary-900 dark:focus:border-primary-500 focus:ring-2 focus:ring-primary-900/20 dark:focus:ring-primary-500/20 focus:outline-none transition-colors duration-200"
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
                  <input
                    :value="formatDateRange(column)"
                    type="text"
                    readonly
                    @click="togglePopover"
                    :placeholder="$t('gestlab.general.calendar_input_placeholder')"
                    class="block w-full cursor-pointer rounded-2xl border border-slate-300/90 bg-white/95 py-2.5 pl-3.5 pr-11 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition-all duration-200 placeholder:text-slate-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:placeholder:text-slate-400 dark:ring-slate-800 dark:focus:border-primary-400 dark:focus:ring-primary-400/20"
                  />
                  <CalendarIcon class="absolute right-3 top-2.5 h-5 w-5 text-primary-900 dark:text-primary-400" />
                </div>
              </template>
            </DatePicker>

            <!-- Boolean Filter -->
            <div v-if="column.type === 'boolean'" class="pt-1">
              <button
                @click="toggleBooleanFilter(column.filter_field)"
                :class="[
                  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                  filters[column.filter_field] 
                    ? 'bg-gradient-to-r from-primary-900 to-primary-800 dark:from-primary-600 dark:to-primary-500' 
                    : 'bg-gray-300 dark:bg-gray-600'
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
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
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
            <label for="trashed" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
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
        
        <!-- Custom Filters Slot -->
        <div class="mt-6">
          <slot name="specific-filters" />
        </div>
      </div>
    </div>

    <!-- DATA TABLE CARD -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <!-- Table Header -->
      <div class="bg-gradient-to-r from-primary-900 to-primary-800 dark:from-gray-800 dark:to-gray-800 dark:border-b dark:border-gray-700 px-4 py-4 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-lg font-semibold text-white dark:text-gray-100 flex items-center gap-2">
            <TableCellsIcon class="h-5 w-5" />
            {{ $t('gestlab.general.titles.data_table') }}
            <span class="text-sm font-normal text-primary-100 dark:text-gray-400 ml-2">
              ({{ selectedRows.length }} de {{ props.data.length }} selecionados)
            </span>
          </h2>
          <BulkActions 
            v-if="allSelected || selectedRows.length"
            :actions="props.actions"
            @bulk-action="handleBulkAction"
            class="text-sm self-start sm:self-auto"
          />
        </div>
      </div>

      <!-- Table Content -->
      <div class="md:hidden" v-if="props.data.length && visibleColumns.length">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
          <article
            v-for="row in props.data"
            :key="row.id"
            class="space-y-4 px-5 py-4 transition-colors duration-150"
            :class="selectedRows.includes(row.id) ? 'bg-primary-50/40 dark:bg-primary-900/20' : 'bg-white dark:bg-gray-800'"
          >
            <div class="flex items-start justify-between gap-3">
              <label class="flex items-center gap-3">
                <input
                  type="checkbox"
                  :value="row.id"
                  :checked="selectedRows.includes(row.id)"
                  class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-900 dark:text-primary-500 focus:ring-2 focus:ring-primary-900/20 dark:focus:ring-primary-500/20 dark:bg-gray-700"
                  @change="toggleSelectRow"
                />
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ row[visibleColumns[0]?.field] ?? `#${row.id}` }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">ID {{ row.id }}</p>
                </div>
              </label>
            </div>

            <dl class="grid grid-cols-1 gap-3">
              <div
                v-for="column in visibleColumns"
                :key="`${row.id}-${column.field}`"
                class="rounded-xl bg-gray-50 dark:bg-gray-700/50 px-3 py-2"
              >
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                  {{ column.label }}
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-200 break-words">
                  <slot :name="`column-${column.field}`" :row="row">
                    {{ row[column.field] }}
                  </slot>
                </dd>
              </div>
            </dl>
          </article>
        </div>
      </div>

      <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" v-if="props.data.length && visibleColumns.length">
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
        </table>
        
        <!-- Empty State -->
        <div v-if="!props.data.length" class="p-12 text-center">
          <TableCellsIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $t('gestlab.general.titles.no_records') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ $t('gestlab.general.titles.start_creating') }}
          </p>
          <button
            v-if="props.createAction && hasPermission('add_' + props.model)"
            @click="$emit('create-record')"
            type="button"
            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-primary-900 to-primary-800 dark:from-primary-600 dark:to-primary-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-primary-800 hover:to-primary-700 dark:hover:from-primary-500 dark:hover:to-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-900 dark:focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors duration-200"
          >
            <SquaresPlusIcon class="h-5 w-5" />
            {{ $t("gestlab.general.buttons.new_record") }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="props.data.length" class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
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
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue';
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  SquaresPlusIcon,
  CalendarIcon,
  FunnelIcon,
  TrashIcon,
  TableCellsIcon
} from "@heroicons/vue/24/outline";
import debounce from "lodash/debounce";
import { useForm, usePage, router } from "@inertiajs/vue3";

import ColumnVisibilityToggle from "@/Components/vap-table/column-visibility-toggle.vue";
import BulkActions from "@/Components/vap-table/bulk-actions.vue";
import TableHeader from "@/Components/vap-table/table-header.vue";
import TableBody from "@/Components/vap-table/table-body.vue";
import Pagination from "@/Components/pagination.vue";
import { usePermission } from "@/Composables/usePermissions";
import { DatePicker } from 'v-calendar'
import combobox from '@/Components/vap-table/combobox.vue';
import comboboxMultiple from '@/Components/vap-table/combobox-multiple.vue';
import 'v-calendar/dist/style.css';

const { hasRole, hasPermission } = usePermission();

const page = usePage();

const props = defineProps({
  data: Object,
  columns: Array,
  actions: Array,
  query: Object,
  filters: Array,
  trashedFilter: Boolean,
  trashedOptions: Array,
  initialFilters: Object,
  initialSortField: String,
  initialSortDirection: String,
  initialIncludes: Array,
  initialGlobalFilter: String,
  pagination: Object,
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

const columns = ref(props.columns);
const filters = ref(props.initialFilters || {});
const sortField = ref(props.initialSortField || '');
const sortDirection = ref(props.initialSortDirection || 'asc');
const includes = ref(props.initialIncludes || []);
const selectedRows = ref([]);
const activeFilters = ref([]);
const dragValue = ref(null);

const visibleColumns = computed(() => columns.value.filter(column => column.visible));
const filterableColumns = computed(() => columns.value.filter(column => column.filterable));
const visibleFilterableColumns = computed(() => visibleColumns.value.filter(column => column.filterable));
const allSelected = computed(() => props.data && props.data.length && selectedRows.value.length === props.data.length);
const hasActiveFilters = computed(() => activeFilters.value.length > 0);

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
  try {
    const response = await fetch(`${column.config.url}?q=${query}`);
    const results = await response.json();
    setOptions(
      results.map(result => ({
        value: result[column.config.value],
        label: result[column.config.label],
      }))
    );
  } catch (error) {
    console.error('Error fetching select options:', error);
    setOptions([]);
  }
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
    per_page: props.pagination.per_page
  }, {
    preserveScroll: false,
    preserveState: true,
    replace: true
  });
};

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedRows.value = props.data.map(item => item.id);
  } else {
    selectedRows.value = [];
  }
  emit("update-selected-ids", selectedRows.value);
};

const updateQuery = debounce(() => {
  const sort = sortField.value 
    ? (sortDirection.value === 'asc' ? sortField.value : `-${sortField.value}`) 
    : '';

  router.get(usePage().url, {
    filter: filters.value,
    sort,
    includes: includes.value,
    globalFilter: filters.value.globalFilter || ''
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300);

const toggleFilter = (field) => {
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
  const id = event.target.value;
  if (event.target.checked) {
    selectedRows.value.push(id);
  } else {
    selectedRows.value = selectedRows.value.filter(rowId => rowId !== id);
  }
  emit("update-selected-ids", selectedRows.value);
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

const toggleBooleanFilter = (field) => {
  filters.value[field] = !filters.value[field];
  updateQuery();
};

const toggleTrashedFilter = () => {
  filters.value.trashed = !filters.value.trashed;
  if (filters.value.trashed && !isFilterActive('trashed')) {
    activeFilters.value.push('trashed');
  } else if (!filters.value.trashed && isFilterActive('trashed')) {
    activeFilters.value = activeFilters.value.filter(f => f !== 'trashed');
  }
  updateQuery();
};

// Watch for changes
watch(
  [() => filters.value, () => sortField.value, () => sortDirection.value, () => includes.value],
  updateQuery,
  { deep: true }
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
});
</script>

<style scoped>
/* Custom scrollbar for table */
div.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

div.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

div.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

div.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Smooth transitions */
button, input, select {
  transition: all 0.2s ease-in-out;
}

/* Date picker popover styling */
:deep(.vc-popover-content) {
  border-radius: 0.75rem !important;
  border: 1px solid #e5e7eb !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}
</style>
