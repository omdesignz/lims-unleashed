<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { useVueTable, FlexRender, getCoreRowModel, getSortedRowModel, getPaginationRowModel } from '@tanstack/vue-table';
import {
  ArrowLongDownIcon,
  MagnifyingGlassIcon,
  ArrowLongUpIcon,
  ChevronDownIcon,
EyeIcon,
CalendarDaysIcon,
FunnelIcon,
SquaresPlusIcon,
} from "@heroicons/vue/24/outline";
import { trans } from "laravel-vue-i18n";
import debounce from "lodash/debounce";
import { useForm, router, usePage } from "@inertiajs/vue3";
import Pagination from "@/Components/pagination.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { DatePicker } from 'v-calendar'
import { usePermission } from "@/Composables/usePermissions";

const { hasRole, hasPermission } = usePermission();
const page = usePage();

const props = defineProps({
    data: Object,
    columns: Array,
    actions: Array,
    query: Object,
    filters: Array,
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

const rowSelection = ref({});

const columnVisibility = ref({});

const columnOrder = ref([]);

// const sorting = ref([]);
const sorting = ref(null);

const INITIAL_PAGE_INDEX = 0;

const goToPageNumber = ref(INITIAL_PAGE_INDEX + 1)
const pageSizes = [2, 10, 20, 30, 40, 50]

const table = useVueTable({
    data: props.data,
    columns: props.columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    state: {
        get rowSelection() {
            return rowSelection.value
        },
        get columnVisibility() {
            return columnVisibility.value
        },
        get columnOrder() {
            return columnOrder.value
        },
        // get sorting() {
        //     return sorting.value
        // },
        // get sorting() {
        //     return sorting.value 
        // },
        sorting
    },
    onColumnOrderChange: order => {
        columnOrder.value = order
    },
    // onSortingChange: updaterOrValue => {
    //     sorting.value =
    //     typeof updaterOrValue === 'function'
    //         ? updaterOrValue(sorting.value)
    //         : updaterOrValue
    // },
    // onSortingChange: updaterOrValue => {
    //     sorting.value =
    //     typeof updaterOrValue === 'function'
    //         ? updaterOrValue(sorting.value)
    //         : updaterOrValue

    //     console.log(table.getSortedRowModel())    
    // },
    onSortingChange: () => console.log('changed sorting'),
    enableRowSelection: true,
    onRowSelectionChange: updateOrValue => {
        rowSelection.value =
        typeof updateOrValue === 'function'
            ? updateOrValue(rowSelection.value)
            : updateOrValue
    }, 
    getRowId: (row) => row.id,
    // getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),

});

const query = reactive({
  search: props.query?.search,
  filter: props.query?.filter,
  orderBy: props.query?.orderBy,
  date: props.query?.date,
  page: null,
});

const emit = defineEmits(["execute-action", "slideover-on", "create-record", "update-selected-ids"]);

function executeAction(selected) {
  emit("execute-action", selected);
  emit("update-selected-ids", table.getSelectedRowModel().rows.map(e => e.id));

  console.log(table.getSelectedRowModel().rows.map(e => e.id));
}

watch(
  query,
  debounce(function (value) {
    router.get(router.page.url, value, {
      preserveState: false,
      preserveScroll: true,
      replace: true,
    });
  }, 300),
);

const filters = props.filters;

function toggleColumnVisibility(column) {
  columnVisibility.value = {
    ...columnVisibility.value,
    [column.id]: !column.getIsVisible(),
  }
}

function toggleAllColumnsVisibility() {
  table.getAllLeafColumns().forEach(column => {
    toggleColumnVisibility(column)
  })
}

function handleGoToPage(e) {
  const page = e.target.value ? Number(e.target.value) - 1 : 0
  goToPageNumber.value = page + 1
  table.setPageIndex(page)
}

function handlePageSizeChange(e) {
  table.setPageSize(Number(e.target.value))
}

const dragValue = ref(null);
const selectDragAttribute = computed(() => ({
  popover: {
    visibility: 'hover',
    isInteractive: false,
  },
}));

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
  // month: "MMM",
  // input: 'YYYY-MM-DD',
});

const updateRange = (e) => {
  query.date = e;
};

const isDark = computed(() => page.props?.darkMode ?? false);

</script>
<template>
    <div class="container mx-auto py-8 border border-gray-200 rounded-md mt-2 mb-2">
        
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <!-- <h1 class="text-base font-semibold leading-6 text-gray-900">Users:</h1>
                <p class="mt-2 text-sm text-gray-700">A list of all the users in your account including their name, title, email and role.</p> -->

                <p>Sorting By Query: </p>
                <p>Sorting By Table: {{ sorting }}</p>

                <form class="w-full flex md:ml-0" action="#" method="GET">
                    <label for="mobile-search-field" class="sr-only">{{
                        $t("gestlab.general.search_input_placeholder")
                    }}</label>
                    <label for="desktop-search-field" class="sr-only">{{
                        $t("gestlab.general.search_input_placeholder")
                    }}</label>
                    <div class="relative w-full text-gray-500 focus-within:text-blue-900">
                        <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center"
                        >
                        <MagnifyingGlassIcon
                            class="flex-shrink-0 h-5 w-5 animate-bounce"
                            aria-hidden="true"
                        />
                        </div>
                        <input
                        v-model="query.search"
                        name="mobile-search-field"
                        id="mobile-search-field"
                        class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:hidden"
                        :placeholder="$t('gestlab.general.search_input_placeholder')"
                        type="search"
                        />
                        <input
                        v-model="query.search"
                        name="desktop-search-field"
                        id="desktop-search-field"
                        class="hidden h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:block"
                        :placeholder="$t('gestlab.general.search_input_placeholder')"
                        type="search"
                        />
                    </div>
                    </form>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <div class="relative right top-0 flex h-12 items-center space-x-3 bg-white sm:right">
                    <DatePicker v-model.range.string="query.date"
                        :select-attribute="selectDragAttribute"
                        :drag-attribute="selectDragAttribute"
                        @drag="dragValue = $event"
                        :is-dark="isDark"
                        locale="pt-PT"
                        color="primary"
                        mode="date"
                        :masks="masks"
                        >
                        <template #day-popover="{ format }">
                        <div class="text-xs">
                            <!-- {{ format(dragValue ? dragValue.start : query.date.start, 'MMM D') }} -->
                            {{ dragValue ? dragValue.start : query.date.start }}
                            -
                            <!-- {{ format(dragValue ? dragValue.end : query.date.end, 'MMM D') }} -->
                            {{ dragValue ? dragValue.end : query.date.end }}
                        </div>
                        </template>
                        <template #default="{ togglePopover }">
                        <button
                            class="inline-flex items-center gap-x-2 rounded-2xl border border-slate-300/90 bg-white/95 px-3.5 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition-all duration-200 hover:border-primary-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800 dark:hover:border-primary-500/60 dark:hover:bg-slate-800/90 disabled:cursor-not-allowed disabled:opacity-30"
                            @click="togglePopover"
                        >
                            <CalendarDaysIcon class="h-5 w-5 text-primary-900 dark:text-primary-400" aria-hidden="true" />
                            {{ query.date ? `${query.date.start} - ${query.date.end}` : 'Seleccione o período' }}
                        </button>
                        </template>
                        <template #footer>
                        <div class="w-full px-3 pb-3" v-if="query.date !== null">
                            <button
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-primary-900 to-primary-800 px-3 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:from-primary-800 hover:to-primary-700 dark:from-primary-600 dark:to-primary-500 dark:hover:from-primary-500 dark:hover:to-primary-400"
                            @click="query.date = null"
                            >
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                            {{ $t("gestlab.general.buttons.clear") }}
                            </button>
                        </div>
                        </template>
                    </DatePicker>

                    <Menu as="div" class="relative inline-block text-left">
                            <div>
                            <MenuButton class="inline-flex gap-x-1.5 items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                
                                <FunnelIcon class="-ml-1 mr-2 h-5 w-5 text-blue-900" aria-hidden="true" />
                                Filtrar
                            </MenuButton>
                            </div>

                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="px-4 py-3 mb-2">
                                    <p class="text-sm text-gray-900 font-semibold">
                                        {{ $t('gestlab.filter.available_filters') }}
                                    </p>
                                </div>
                                <MenuItem as="div" class="px-4 mb-2">
                                    <button v-for="(filter, index) in filters" :key="index" @click="() => { query.filter =  filter.id}"
                                    class="w-full mt-2 text-left rounded-full bg-blue-900 px-2.5 py-1 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-blue-900 hover:bg-blue-800">
                                    <p class="text-sm text-white">
                                        {{ $t(filter.label) }}
                                    </p>
                                </button> <br>
                                    
                                </MenuItem>
                            
                            </MenuItems>
                            </transition>
                        </Menu>
                    <button v-if="props.createAction && hasPermission('add_' + props.model)"  @click="$emit('create-record')" type="button" class="inline-flex items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                        <SquaresPlusIcon
                            class="-ml-1 mr-2 h-5 w-5 text-blue-900 group-hover:text-white group-hover:animate-bounce"
                            aria-hidden="true"
                        />
                        {{ $t("gestlab.general.buttons.new_record") }}
                    </button>
                    <button type="button" class="inline-flex items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">All</button>
                    <Menu as="div" class="relative inline-block text-left">
                            <div>
                            <MenuButton class="inline-flex gap-x-1.5 items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">
                                
                                <EyeIcon class="-ml-1 mr-2 h-5 w-5 text-blue-900" aria-hidden="true" />
                                Colunas
                            </MenuButton>
                            </div>

                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="px-4 py-3">
                                <p class="text-sm text-gray-900">
                                <label>
                                    <input
                                    class="text-sm rounded-full border-blue-900 text-blue-900 focus:ring-blue-900"
                                        type="checkbox"
                                        :checked="table.getIsAllColumnsVisible()"
                                        @input="toggleAllColumnsVisibility"
                                    />
                                    Toggle All
                                    </label>
                                </p>
                                </div>
                                <div class="py-1">
                                <MenuItem as="div" class="px-4 py-3">
                                    <div v-for="column in table.getAllLeafColumns()"
                                    class="py-1"
                                    :key="column.id">
                                    <p class="text-sm text-gray-900">
                                    <label>
                                        <input
                                        class="text-sm rounded-full border-blue-900 text-blue-900 focus:ring-blue-900"
                                            type="checkbox"
                                            :checked="column.getIsVisible()"
                                            @input="toggleColumnVisibility(column)"
                                        />
                                        {{ column.columnDef.header }}
                                        </label>
                                    </p>
                                </div>
                                    
                                </MenuItem>
                            
                                </div>
                            </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                
            </div>
            </div>
            <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="relative">
                    <div class="absolute left-14 top-0 flex h-12 items-center space-x-3 bg-white sm:left-12" v-if="table.getIsAllPageRowsSelected() || table.getIsSomePageRowsSelected()">
                    <button @click="executeAction(action.id)" v-for="(action, index) in props.actions" :key="index" type="button" class="inline-flex items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">{{ $t(action.label) }}</button>
                    <!-- <button type="button" class="inline-flex items-center rounded bg-white px-2 py-1 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-white">Delete all</button> -->
                    </div>
                    <table class="min-w-full table-fixed divide-y divide-gray-300">
                    <thead>
                        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <th scope="col" class="relative px-7 sm:w-12 sm:px-6">
                                <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded-full border-blue-900 text-blue-900 focus:ring-blue-900" :checked="table.getIsAllPageRowsSelected()" :indeterminate="table.getIsSomePageRowsSelected()" :onChange="table.getToggleAllPageRowsSelectedHandler()" />
                            </th>
                            <th v-for="header in headerGroup.headers" :key="header.id" :class="header.column.getCanSort() ? 'cursor-pointer select-none' : ''" class="min-w-[12rem] py-3.5 pr-3 text-left text-sm font-semibold text-gray-900" @click="header.column.getToggleSortingHandler()?.($event)">
                                <FlexRender
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />

                                <span v-if="header.column.getCanSort()" class="inline-flex items-center justify-center text-xs font-medium text-blue-900">
                                    <!-- <ArrowLongUpIcon v-if="header.column.getIsSorted() === 'asc'" class="w-4 h-4" />
                                    <ArrowLongDownIcon v-if="header.column.getIsSorted() === 'desc'" class="w-4 h-4" /> -->
                                </span>
                                
                            </th>
                            <th scope="col" class="min-w-[12rem] py-3.5 pr-3 text-left text-sm font-semibold text-gray-900">
                                Actions
                            </th>
                        </tr>
                        
                        
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="row in table.getRowModel().rows" :key="row.id">
                            <td class="relative px-7 sm:w-12 sm:px-6">
                                <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded-full border-blue-900 text-blue-900 focus:ring-blue-900" :value="row.id" :checked="row.getIsSelected()" :onChange="row.getToggleSelectedHandler()" :disabled="!row.getCanSelect()" />
                            </td>
                            <td class="whitespace-nowrap py-4 text-sm text-gray-500" v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </td>
                            <td class="whitespace-nowrap py-4 text-sm text-gray-500">
                                <slot
                                name="actions"
                                :id="row.original.id"
                                :record="row.original"
                            ></slot>
                            </td>
                        </tr>
                        
                    </tbody>
                    </table>

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
            </div>
        </div>

    </div>
</template>
