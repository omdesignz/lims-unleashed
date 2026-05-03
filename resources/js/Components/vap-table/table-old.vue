<template>
    <div class="container mx-auto py-8 border border-gray-200 dark:border-gray-700 rounded-md mt-2 mb-2">
        <div class="px-4 sm:px-6 lg:px-8">

            <!-- Formatted and Aligned Rows -->

            <!-- <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10"> -->

                <div class="flex justify-end gap-x-3 border-b border-gray-900/10 pb-2">
                <div class="mt-2">
                    
                <select class="block w-full rounded-full border-0 py-0.5 pl-3 pr-10 text-white bg-blue-900 dark:bg-gray-900 ring-1 ring-inset ring-blue-900 dark:ring-gray-700 focus:ring-2 focus:ring-blue-900 dark:focus:ring-gray-700 sm:text-sm sm:leading-6 cursor-pointer dark:hover:text-white"
                        v-model="props.pagination.per_page" 
                        id="per_page" 
                        name="per_page" 
                        @change="() => router.get(page.url, {page: 1, per_page: props.pagination.per_page}, {
                        preserveScroll: false,
                        preserveState: true,
                        replace: true
                    })">
                    <option value="1" :selected="props.pagination.per_page == 1">1</option>
                    <option value="2" :selected="props.pagination.per_page == 2">2</option>
                    <option value="50" :selected="props.pagination.per_page == 50">50</option>
                    <option value="100" :selected="props.pagination.per_page == 100">100</option>
                </select>

                </div>
                <div class="mt-2">
                    <Filters 
                                :filters="visibleFilterableColumns"
                                :trashedFilter="props.trashedFilter"
                                :activeFilters="activeFilters" 
                                @update-filters="updateFilters"
                                @clear-filter-input="clearFilterInput" 
                            />
                </div>
                <div class="mt-2">
                    <button v-if="props.createAction && hasPermission('add_' + props.model)"  @click="$emit('create-record')" type="button" class="inline-flex items-center rounded-full bg-blue-900 dark:bg-gray-900 px-3 py-1 text-sm font-normal text-white dark:text-gray-400 shadow-sm ring-1 ring-inset ring-blue-900 dark:ring-gray-700 hover:bg-blue-800 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-blue-900 dark:disabled:hover:bg-gray-700 dark:hover:text-white">
                                <SquaresPlusIcon
                                    class="-ml-1 mr-2 h-5 w-5 text-white dark:text-gray-400 group-hover:text-white group-hover:animate-bounce"
                                    aria-hidden="true"
                                />
                                {{ $t("gestlab.general.buttons.new_record") }}
                            </button>
                </div>
                <div class="mt-2 mr-2">
                    <ColumnVisibilityToggle :columns="columns" @update-columns="updateColumns" />
                </div>
                </div>


            <div class="sm:flex sm:items-center mt-2">
                <div class="sm:flex-auto">
                    
                    <form class="w-full flex md:ml-0" action="#" method="GET">
                        <label for="mobile-search-field" class="sr-only">{{
                            $t("gestlab.general.search_input_placeholder")
                        }}</label>
                        <label for="desktop-search-field" class="sr-only">{{
                            $t("gestlab.general.search_input_placeholder")
                        }}</label>
                        <div class="relative w-full text-gray-500 dark:text-gray-400 focus-within:text-blue-900 dark:focus-within:text-gray-400">
                            <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center ml-2"
                            >
                            <MagnifyingGlassIcon
                                class="flex-shrink-0 h-5 w-5 animate-bounce"
                                aria-hidden="true"
                            />
                            </div>
                            <input
                            v-model="filters.globalFilter"
                            name="mobile-search-field"
                            id="mobile-search-field"
                            class="h-full w-full py-2 pl-8 rounded-full pr-3 border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-base text-gray-500 dark:text-gray-400 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none ring-blue-900 dark:ring-gray-700 focus:ring-1 focus:border-blue-900 dark:focus:border-gray-700 focus:placeholder-gray-400 dark:focus:placeholder-gray-400 sm:hidden"
                            :placeholder="$t('gestlab.general.search_input_placeholder')"
                            type="search"
                            @input="updateQuery"
                            />
                            <input
                            v-model="filters.globalFilter"
                            name="desktop-search-field"
                            id="desktop-search-field"
                            class="hidden h-full w-full border-gray-300 dark:bg-gray-900 dark:border-gray-700 rounded-full py-2 pl-8 pr-3 text-sm text-gray-500 dark:text-gray-400 placeholder-gray-500 dark:placehoper-gray-400 focus:outline-none ring-blue-900 dark:ring-gray-700 focus:ring-1 focus:placeholder-gray-400 sm:blockhidden h-full w-full border-gray-300 dark:border-gray-700 rounded-full py-2 pl-8 pr-3 text-sm text-gray-500 dark:text-gray-400 placeholder-gray-500 dark:placehoper-gray-400 focus:border-blue-900 dark:focus:border-gray-700 focus:placeholder-gray-400 dark:focus:placeholder-gray-400 sm:block"
                            :placeholder="$t('gestlab.general.search_input_placeholder')"
                            type="search"
                            @input="updateQuery"
                            />
                        </div>
                    </form>
                </div>

                </div>

                <!-- Filters Start --> 
                <div class="space-y-12">
      
                    <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-12">

                        <div class="sm:col-span-full">
                            <slot name="specific-filters" />
                        </div>


                        <div class="sm:col-span-3" v-for="(column, index) in visibleFilterableColumns" :key="column.field" v-show="isFilterActive(column.filter_field)" :class="{ 'sm:col-span-full': column.type === 'remote_select_multiple', 'sm:col-start-1': index === 0 }">
                            
                            
                            <div class="relative">
                                <label :for="column.field" class="absolute -top-2 left-2 inline-block bg-white dark:bg-gray-800 px-1 text-xs font-medium text-gray-900 dark:text-gray-400">{{ column.label }}</label>

                                <!-- String Filter -->

                                <input type="text" class="block w-full rounded-md dark:bg-gray-800 border-0 py-1 text-gray-900 dark:text-gray-400 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:focus:ring-gray-700 sm:text-sm sm:leading-6"
                                    :name="column.field" 
                                    :id="column.field"
                                    v-model="filters[column.filter_field]"
                                    @input="updateQuery"
                                    v-if="column.type === 'string'"
                                />

                                <!-- Date Filter -->

                                <DatePicker
                                    v-model.range.string="filters[column.filter_field]"
                                    :select-attribute="selectDragAttribute"
                                    :drag-attribute="selectDragAttribute"
                                    @drag="dragValue = $event"
                                    locale="pt-PT"
                                    color="blue"
                                    mode="date"
                                    @update:model-value="(e) => column.value = e, updateQuery"
                                    v-if="column.type === 'date'"
                                    :masks="masks"
                                    @change=""
                                    >
                                    <template #day-popover="{ format }">
                                        <div class="text-xs">
                                            {{ dragValue ? dragValue.start : column.value.start }}
                                            -
                                            {{ dragValue ? dragValue.end : column.value.end }}
                                        </div>
                                    </template>
                                    <template #default="{ togglePopover }">

                                        <span class="inline-flex rounded-md shadow-sm w-full -ml-px">
                                            <button disabled type="button" class="w-full inline-flex items-center gap-x-1.5 rounded-l-md bg-white dark:bg-gray-800 px-3 py-1 text-sm text-gray-900 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10 sm:text-sm sm:leading-6">
                                                {{ column.value ? `${column.value.start} - ${column.value.end}` : '-' }}
                                            </button>
                                            <button @click="togglePopover" type="button" class="-ml-px inline-flex items-center rounded-r-md bg-white dark:bg-gray-800 px-3 text-sm font-semibold text-gray-900 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10">
                                                <CalendarDateRangeIcon class="h-5 w-5 text-blue-900 dark:text-gray-400" aria-hidden="true" />
                                            </button>
                                        </span>
                                        
                                    </template>
                                    <template #footer>
                                        <div class="w-full px-3 pb-3" v-if="column.value !== null">
                                            <button
                                            class="inline-flex items-center justify-center bg-blue-900 hover:bg-blue-800 text-white font-medium w-full px-3 py-1 rounded-full"
                                            @click="removeFilter(column.field)"
                                            >
                                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                                            {{ $t("gestlab.general.buttons.clear") }}
                                            </button>
                                        </div>
                                    </template>
                                </DatePicker>

                                <!-- Boolean Filter -->

                                <span class="inline-flex rounded-md shadow-sm w-full -ml-px py-1 justify-end ring-1 ring-gray-300 px-2" v-if="column.type === 'boolean'">
                                    
                                        <button type="button" @click="() => toggleBooleanFilter(column.filter_field)" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="filters[column.field]" :class="{ 'bg-blue-900': filters[column.field], 'bg-gray-200': !filters[column.field] }">
                                            <span class="sr-only">Toggle Value</span>
                                            <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                                            <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': filters[column.filter_field], 'translate-x-0': !filters[column.filter_field] }">
                                                <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                                                <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!filters[column.filter_field]" :class="{ 'opacity-0 duration-100 ease-out': filters[column.filter_field], 'opacity-100 duration-200 ease-in': !filters[column.filter_field] }">
                                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                </span>
                                                <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                                                <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="filters[column.filter_field]" :class="{ 'opacity-100 duration-200 ease-in': filters[column.filter_field], 'opacity-0 duration-100 ease-out': !filters[column.filter_field] }">
                                                <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                                                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                                </svg>
                                                </span>
                                            </span>
                                        </button>
                                    
                                </span>


                                <!-- Select Filter: Local Select Options -->
                                <combobox 
                                    v-if="column.type === 'select'"
                                    :name="column.field"
                                    :hasError="false" 
                                    v-model="filters[column.filter_field]"
                                    :options="column.options"
                                    
                                    @update:model-value="updateQuery"
                                />


                                <!-- Select Filter: Remote Select Options -->

                                    <combobox 
                                        v-if="column.type === 'remote_select'"
                                        :name="column.field"
                                        :hasError="false" 
                                        v-model="filters[column.filter_field]"
                                        :load-options="(query, setOptions) => fetchSelectOptions(query, setOptions, column)"
                                        @update:model-value="updateQuery"
                                    />

                                    <!-- Select Filter: Multiple Remote Select Options -->

                                    <comboboxMultiple
                                    v-if="column.type === 'remote_select_multiple'"
                                    :name="column.field" 
                                    v-model="filters[column.filter_field]"
                                    :multiple="true"
                                    :load-options="(query, setOptions) => fetchSelectOptions(query, setOptions, column)"
                                    @update:modelValue="(e) => updateQuery(e, column.filter_field)"
                                    />
                                    

                            </div>
                            
                        </div>

                        <div class="sm:col-span-3" v-show="isFilterActive('trashed')" :class="visibleFilterableColumns.length < 1 ? 'sm:col-start-1' : ''">
                            
                            <div class="relative">
                                <label for="trashed" class="absolute -top-2 left-2 inline-block bg-white px-1 text-xs font-medium text-gray-900">{{ $t('gestlab.general.labels.trashed') }}</label>

                                <!-- Select Trashed Filter -->

                                <Combobox as="div" v-model="filters['trashed']" @update:modelValue="updateQuery" name="trashed" :nullable="false">
                                    <!-- <ComboboxLabel class="block text-sm font-medium leading-6 text-gray-900">Assigned to</ComboboxLabel> -->
                                    <div class="">
                                    <ComboboxInput class="w-full rounded-md border-0 bg-white py-1 pl-3 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :display-value="(option = 'only') => option === null ? '' : $t('gestlab.general.labels.trashed_' + option)" />
                                    <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none">
                                        <ChevronUpDownIcon class="h-5 w-5 text-blue-900" aria-hidden="true" />
                                    </ComboboxButton>

                                    <ComboboxOptions v-if="props.trashedOptions" class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                        <ComboboxOption v-for="option in props.trashedOptions" :key="option.value" :value="option.value" as="template" v-slot="{ active, selected }">
                                        <li :class="['relative cursor-pointer select-none py-2 pl-8 pr-4', active ? 'bg-blue-900 text-white' : 'text-gray-900']">
                                            <span :class="['block truncate', selected && 'font-semibold']">
                                            {{ option.text }}
                                            </span>

                                            <span v-if="selected" :class="['absolute inset-y-0 left-0 flex items-center pl-1.5', active ? 'text-white' : 'text-blue-900']">
                                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                        </ComboboxOption>
                                    </ComboboxOptions>
                                    </div>
                                </Combobox>

                            </div>

                        </div>
                    </div>
                </div>

                <!-- Filters End -->

            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8 border-b border-gray-200 mb-2">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="relative">
                            <BulkActions :actions="props.actions" @bulk-action="handleBulkAction" v-if="allSelected || selectedRows.length" />
                            <table class="min-w-full table-fixed divide-y divide-gray-300 dark:divide-gray-700 dark:bg-gray-900" v-if="props.data.length && visibleColumns.length">
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
                            
                        </div>
                    </div>
                </div>

                <!-- Real Pagination -->

                <Pagination
                                    v-if="props.data.length"
                                    :links="props.pagination.links"
                                    :from="props.pagination.from"
                                    :to="props.pagination.to"
                                    :total="props.pagination.total"
                                    :current_page="props.pagination.current_page"
                                    :last_page="props.pagination.last_page"
                                    class="mt-2"
                                />
            </div>
        </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, watch, reactive, defineProps } from 'vue';
  import {
    MagnifyingGlassIcon,
    CheckIcon,
    ChevronUpDownIcon,
    XMarkIcon,
    SquaresPlusIcon,
    CalendarDateRangeIcon
    } from "@heroicons/vue/24/outline";
    import {
  Combobox,
  ComboboxButton,
  ComboboxInput,
  ComboboxLabel,
  ComboboxOption,
  ComboboxOptions,
} from '@headlessui/vue'
    import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
    import debounce from "lodash/debounce";
    import { useForm, usePage, router } from "@inertiajs/vue3";

  import ColumnVisibilityToggle from "@/Components/vap-table/column-visibility-toggle.vue";
  import Filters from "@/Components/vap-table/filters.vue";
  import DateFilter from "@/Components/vap-table/date-filter.vue";
  import BulkActions from "@/Components/vap-table/bulk-actions.vue";
  import TableHeader from "@/Components/vap-table/table-header.vue";
  import TableBody from "@/Components/vap-table/table-body.vue";
  import Pagination from "@/Components/pagination.vue";
  import { usePermission } from "@/Composables/usePermissions";
  import { DatePicker } from 'v-calendar'
  import combobox from '@/Components/vap-table/combobox.vue';
  import comboboxMultiple from '@/Components/vap-table/combobox-multiple.vue';

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
const filters = ref(props.initialFilters);
const sortField = ref(props.initialSortField);
const sortDirection = ref(props.initialSortDirection);
const includes = ref(props.initialIncludes);
const selectedRows = ref([]);
const dateFilter = ref({ field: '', value: '' });
const globalFilter = ref(props.initialFilters['globalFilter']);
const activeFilters = ref([]);

const select = ref();
const test = ref([])

const visibleColumns = computed(() => columns.value.filter(column => column.visible));
const filterableColumns = computed(() => columns.value.filter(column => column.filterable));
const visibleFilterableColumns = computed(() => visibleColumns.value.filter(column => column.filterable));
const allSelected = computed(() => props.data && props.data.length && selectedRows.value.length === props.data.length);


const fetchSelectOptions = async (query, setOptions, column) => {
  
    fetch(column.config.url + '?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
                value: result[column.config.value],
                label: result[column.config.label],
            };
        })
        );
    });

};

function loadCustomers(query, setOptions) {
    fetch('/customers/getCustomer?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

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
//   updateQuery();
};

function serializeFilters(filters) {
    const params = new URLSearchParams();

    // Check if filters is an array, otherwise default to an empty array
    (Array.isArray(filters) ? filters : []).forEach((filter, i) => {
        const { field, value } = filter;

        if (Array.isArray(value)) {
            // If the value is an array, serialize each item individually
            value.forEach((v, j) => {
                params.append(`${field}[${j}]`, v);
            });
        } else if (typeof value === 'object' && value !== null) {
            // If the value is an object, serialize its key-value pairs
            Object.keys(value).forEach((key) => {
                params.append(`${field}[${key}]`, value[key]);
            });
        } else {
            // If the value is a single value, just append it directly
            params.append(field, value);
        }
    });

    return params.toString();
}
  
const toggleSelectAll = (event) => {
    if (event.target.checked) {
      selectedRows.value = props.data.map(product => product.id);
    } else {
      selectedRows.value = [];
    }

    emit("update-selected-ids", selectedRows.value);

  };

  const updateQuery = (e = null, field = null) => {

    let sort = sortField.value ? (sortDirection.value === 'asc' ? sortField.value : `-${sortField.value}`) : '';

    let filter = filters.value ? filters.value : {};

    // const filter = serializeFilters(filters.value);

    router.get(usePage().url, {
      filter: filter,
      sort,
      includes: includes.value,
      globalFilter: globalFilter.value || ''
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true
    });
};

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
    if (event.target.checked) {
      selectedRows.value.push(event.target.value);
    } else {
      selectedRows.value = selectedRows.value.filter(id => id !== event.target.value);
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
    if (action === 'edit') {
      // Implement the edit action here
      console.log('edit: ', id);
    } else if (action === 'delete') {
      // Implement the delete action here
    }
  };
  
  const updateColumns = (updatedColumns) => {
    props.columns.value = updatedColumns;

    updateQuery();
  };
  
  
  onMounted(() => {
    // fetchProducts();
  });


const updateRange = (e) => {
  dateFilter.value = e;
};

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
  input: 'YYYY-MM-DD',
});

const updateDateFilter = (date) => {
  filters.value[date.field] = date.value;
  updateQuery();
};

const updateFilters = (column) => {

//   Prevent the dropdown from closing
event.preventDefault();

if(column.type === 'boolean') {
    toggleBooleanFilter(column.filter_field)
} else {
    toggleFilter(column.filter_field)
}
};

const clearFilterInput = (field) => {
    removeFilter(field)
}

const removeFilter = (field) => {
  filters.value[field] = '';
  updateQuery();
};

const toggleBooleanFilter = (field) => {
if(filters.value[field] === true){
    filters.value[field] = false;   
} else {
    filters.value[field] = true;
}

updateQuery();

};

const toggleTrashedFilter = () => {
  filters.value.trashed = !filters.value.trashed;
  updateQuery();
};

const debouncedUpdateQuery = debounce(updateQuery, 5000); 

watch([filters, sortField, sortDirection, includes, globalFilter], updateQuery, { deep: true });

  </script>