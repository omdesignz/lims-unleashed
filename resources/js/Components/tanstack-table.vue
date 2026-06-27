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
XMarkIcon,
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
    onSortingChange: updaterOrValue => {
        sorting.value =
        typeof updaterOrValue === 'function'
            ? updaterOrValue(sorting.value)
            : updaterOrValue
    },
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
}

watch(
  query,
  debounce(function (value) {
    router.get(page.url, value, {
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
    <div class="space-y-6">
        
        <section class="ds-command-surface px-4 py-5 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="sm:flex-auto">
                <form class="flex w-full md:ml-0" action="#" method="GET">
                    <label for="mobile-search-field" class="sr-only">{{
                        $t("gestlab.general.search_input_placeholder")
                    }}</label>
                    <label for="desktop-search-field" class="sr-only">{{
                        $t("gestlab.general.search_input_placeholder")
                    }}</label>
                    <div class="relative w-full text-[var(--ds-text-soft)] focus-within:text-[rgb(var(--primary-700-rgb))]">
                        <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                        >
                        <MagnifyingGlassIcon
                            class="h-5 w-5 shrink-0"
                            aria-hidden="true"
                        />
                        </div>
                        <input
                        v-model="query.search"
                        name="mobile-search-field"
                        id="mobile-search-field"
                        class="ds-field pl-11 text-base sm:hidden"
                        :placeholder="$t('gestlab.general.search_input_placeholder')"
                        type="search"
                        />
                        <input
                        v-model="query.search"
                        name="desktop-search-field"
                        id="desktop-search-field"
                        class="ds-field hidden pl-11 sm:block"
                        :placeholder="$t('gestlab.general.search_input_placeholder')"
                        type="search"
                        />
                    </div>
                    </form>
            </div>
            <div class="sm:flex-none">
                <div class="relative right top-0 flex flex-wrap items-center gap-3 sm:right">
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
                            type="button"
                            class="ds-button ds-button-secondary"
                            @click="togglePopover"
                        >
                            <CalendarDaysIcon class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
                            {{ query.date ? `${query.date.start} - ${query.date.end}` : $t('gestlab.general.labels.select_period') }}
                        </button>
                        </template>
                        <template #footer>
                        <div class="w-full px-3 pb-3" v-if="query.date !== null">
                            <button
                            type="button"
                            class="ds-button ds-button-secondary w-full"
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
                            <MenuButton class="ds-button ds-button-secondary">
                                
                                <FunnelIcon class="-ml-1 mr-2 h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
                                {{ $t('gestlab.general.buttons.filters') }}
                            </MenuButton>
                            </div>

                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="ds-floating-panel absolute right-0 z-50 mt-2 w-64 origin-top-right focus:outline-none">
                                <div class="px-4 py-3 mb-2">
                                    <p class="ds-heading text-sm">
                                        {{ $t('gestlab.filter.available_filters') }}
                                    </p>
                                </div>
                                <MenuItem as="div" class="px-4 mb-2">
                                    <button v-for="(filter, index) in filters" :key="index" @click="() => { query.filter =  filter.id}"
                                    type="button"
                                    class="ds-button ds-button-secondary mt-2 w-full justify-start text-left">
                                    <p class="text-sm">
                                        {{ $t(filter.label) }}
                                    </p>
                                </button> <br>
                                    
                                </MenuItem>
                            
                            </MenuItems>
                            </transition>
                        </Menu>
                    <button v-if="props.createAction && hasPermission('add_' + props.model)"  @click="$emit('create-record')" type="button" class="ds-button ds-button-primary">
                        <SquaresPlusIcon
                            class="-ml-1 mr-2 h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]"
                            aria-hidden="true"
                        />
                        {{ $t("gestlab.general.buttons.new_record") }}
                    </button>
                    <button type="button" class="ds-button ds-button-secondary">{{ $t('gestlab.general.labels.all') }}</button>
                    <Menu as="div" class="relative inline-block text-left">
                            <div>
                            <MenuButton class="ds-button ds-button-secondary">
                                
                                <EyeIcon class="-ml-1 mr-2 h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
                                {{ $t('gestlab.general.labels.columns') }}
                            </MenuButton>
                            </div>

                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="ds-floating-panel absolute right-0 z-50 mt-2 w-64 origin-top-right focus:outline-none">
                                <div class="px-4 py-3">
                                <p class="text-sm text-[var(--ds-text-muted)]">
                                <label class="inline-flex items-center gap-2 font-semibold">
                                    <input
                                    class="ds-checkbox"
                                        type="checkbox"
                                        :checked="table.getIsAllColumnsVisible()"
                                        @input="toggleAllColumnsVisibility"
                                    />
                                    {{ $t('gestlab.general.labels.toggle_column_visibility') }}
                                    </label>
                                </p>
                                </div>
                                <div class="border-t border-[var(--ds-border)] py-1">
                                <MenuItem as="div" class="px-4 py-3">
                                    <div v-for="column in table.getAllLeafColumns()"
                                    class="py-1"
                                    :key="column.id">
                                    <p class="text-sm text-[var(--ds-text-muted)]">
                                    <label class="inline-flex items-center gap-2">
                                        <input
                                        class="ds-checkbox"
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
        </section>
        <section class="ds-table-shell flow-root">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                <div class="relative">
                    <div class="absolute left-14 top-0 z-10 flex h-12 items-center gap-2 rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-2 shadow-sm sm:left-12" v-if="table.getIsAllPageRowsSelected() || table.getIsSomePageRowsSelected()">
                    <button @click="executeAction(action.id)" v-for="(action, index) in props.actions" :key="index" type="button" class="ds-button ds-button-secondary min-h-0 rounded-xl px-3 py-1.5">{{ $t(action.label) }}</button>
                    </div>
                    <table class="min-w-full table-fixed overflow-hidden rounded-[1.5rem]">
                    <thead class="ds-table-head">
                        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <th scope="col" class="relative px-7 sm:w-12 sm:px-6">
                                <input type="checkbox" class="ds-checkbox absolute left-4 top-1/2 -mt-2" :checked="table.getIsAllPageRowsSelected()" :indeterminate="table.getIsSomePageRowsSelected()" :onChange="table.getToggleAllPageRowsSelectedHandler()" />
                            </th>
                            <th v-for="header in headerGroup.headers" :key="header.id" :class="header.column.getCanSort() ? 'cursor-pointer select-none' : ''" class="ds-table-heading min-w-[12rem] py-3.5 pr-3 text-left" @click="header.column.getToggleSortingHandler()?.($event)">
                                <FlexRender
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />

                                <span v-if="header.column.getCanSort()" class="inline-flex items-center justify-center text-xs font-medium text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]">
                                    <!-- <ArrowLongUpIcon v-if="header.column.getIsSorted() === 'asc'" class="w-4 h-4" />
                                    <ArrowLongDownIcon v-if="header.column.getIsSorted() === 'desc'" class="w-4 h-4" /> -->
                                </span>
                                
                            </th>
                            <th scope="col" class="ds-table-heading min-w-[12rem] py-3.5 pr-3 text-left">
                                {{ $t('gestlab.general.labels.actions') }}
                            </th>
                        </tr>
                        
                        
                    </thead>
                    <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
                        <tr v-for="row in table.getRowModel().rows" :key="row.id" class="ds-table-row">
                            <td class="relative px-7 sm:w-12 sm:px-6">
                                <input type="checkbox" class="ds-checkbox absolute left-4 top-1/2 -mt-2" :value="row.id" :checked="row.getIsSelected()" :onChange="row.getToggleSelectedHandler()" :disabled="!row.getCanSelect()" />
                            </td>
                            <td class="ds-table-cell whitespace-nowrap py-4" v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </td>
                            <td class="ds-table-cell whitespace-nowrap py-4">
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
        </section>

    </div>
</template>
