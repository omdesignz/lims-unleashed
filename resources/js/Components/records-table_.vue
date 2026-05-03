<template>
  <div class="space-y-6">
    <!-- HEADER SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Filters Section -->
        <div class="flex items-center gap-3">
          <!-- Status Filter -->
          <select-filter :filters="filters" @execute="changeFilter" />
          
          <!-- Date Range Picker -->
          <date-picker-enhanced
            v-model.range.string="query.date"
            locale="pt-PT"
            color="blue"
            mode="date"
            range
            :input-debounce="500"
            @update:model-value="updateRange" 
            :masks="masks"
            class="z-10"
          />
          
          <!-- Bulk Actions -->
          <select-action
            :record-ids="
              props.record.data
                .filter((record) => record.selected)
                .map((record) => record.id)
            "
            :actions="actions"
            @execute="executeAction"
          />
        </div>
        
        <!-- Create Button -->
        <div class="flex items-center gap-3">
          <button
            v-if="props.createAction && hasPermission('add_' + props.model)"
            type="button"
            @click="$emit('create-record')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <SquaresPlusIcon class="h-5 w-5" />
            {{ $t("gestlab.general.buttons.new_record") }}
          </button>
        </div>
      </div>
    </div>

    <!-- SEARCH SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="relative max-w-md">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
        </div>
        <input
          v-model="query.search"
          type="search"
          :placeholder="$t('gestlab.general.search_input_placeholder')"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
        />
      </div>
    </div>

    <!-- DATA TABLE SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- Table Header -->
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white">
          {{ $t('gestlab.general.titles.records_list') }}
          <span class="text-sm font-normal text-blue-100 ml-2">
            ({{ props.record.meta.total }} {{ $t('gestlab.general.labels.records') }})
          </span>
        </h2>
      </div>

      <!-- Empty State -->
      <empty-state
        v-if="!props.record.data.length"
        @create-record="$emit('create-record')"
        :name="$t('gestlab.general.labels.no_records')"
        :description="$t('gestlab.general.labels.start_creating')"
        class="p-12"
      />

      <!-- Data Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <!-- Checkbox Column -->
              <th scope="col" class="px-6 py-3.5 text-left">
                <span class="sr-only">{{ $t('gestlab.general.labels.select') }}</span>
              </th>
              
              <!-- QR Code Column -->
              <th
                v-if="props.hasQr"
                scope="col"
                class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider"
              >
                {{ $t('gestlab.general.labels.qr_code') }}
              </th>
              
              <!-- Dynamic Fields -->
              <th
                scope="col"
                class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider"
                v-for="field in props.fields"
              >
                {{ field.name }}
              </th>
              
              <!-- Actions Column -->
              <th scope="col" class="relative px-6 py-3.5">
                <span class="sr-only">{{ $t('gestlab.general.buttons.actions') }}</span>
                <span class="text-xs font-semibold text-gray-900 uppercase tracking-wider">
                  {{ $t('gestlab.general.buttons.actions') }}
                </span>
              </th>
            </tr>
          </thead>
          
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr
              v-for="(record, recordIdx) in props.record.data"
              :key="record.id"
              :class="recordIdx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
              class="hover:bg-blue-50/30 transition-colors duration-150"
            >
              <!-- Checkbox Cell -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <input
                    v-model="record.selected"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:ring-offset-0 transition-colors duration-200"
                  />
                </div>
              </td>
              
              <!-- QR Code Cell -->
              <td v-if="props.hasQr" class="px-6 py-4 whitespace-nowrap">
                <slot name="qr" :data="record" class="w-16 h-16"></slot>
              </td>
              
              <!-- Dynamic Field Cells -->
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                v-for="field in props.fields"
              >
                <div class="font-medium">
                  {{ record[field.value] }}
                </div>
              </td>
              
              <!-- Actions Cell -->
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <!-- Restore Button -->
                  <button
                    v-if="record.deleted && hasPermission('restore_' + props.model)"
                    @click="openConfirmation('restore', record)"
                    type="button"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1"
                    :title="$t('gestlab.actions.restore')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                  </button>
                  
                  <!-- Edit Button -->
                  <button
                    v-if="!record.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                    @click="openConfirmation('edit', record)"
                    type="button"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1"
                    :title="$t('gestlab.actions.edit')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  
                  <!-- Place Analysis Button -->
                  <Link
                    v-if="!record.deleted && hasPermission('add_' + props.model) && !record?.placed_analysis && record.links.collection_type == 'programmed'"
                    :href="record.links.place_analysis_path"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1"
                    :title="$t('gestlab.actions.place_analysis')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                    </svg>
                  </Link>
                  
                  <!-- Slideover Edit Button -->
                  <button
                    v-if="!record.deleted && hasPermission('edit_' + props.model) && props.slideOverEdit"
                    @click="openConfirmation('edit_slide', record)"
                    type="button"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1"
                    :title="$t('gestlab.actions.edit')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  
                  <!-- Delete Button -->
                  <button
                    v-if="!record.deleted && hasPermission('delete_' + props.model)"
                    @click="openConfirmation('delete', record)"
                    type="button"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-1"
                    :title="$t('gestlab.actions.delete')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                  
                  <!-- PDF View Button -->
                  <a
                    v-if="!record.deleted && hasPermission('view_' + props.model) && record?.links?.pdf_path"
                    target="_blank"
                    :href="record.links.pdf_path"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1"
                    :title="$t('gestlab.actions.view_pdf')"
                  >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z" />
                      <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" />
                    </svg>
                  </a>
                  
                  <!-- Collection Term PDF Button -->
                  <a
                    v-if="!record.deleted && hasPermission('view_' + props.model) && record?.links?.pdf_collection_term"
                    target="_blank"
                    :href="record.links.pdf_collection_term"
                    class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-blue-900 hover:bg-blue-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-1"
                    :title="$t('gestlab.actions.view_collection_term')"
                  >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                  </a>
                  
                  <!-- Custom Actions Slot -->
                  <slot
                    name="actions"
                    :id="record.id"
                    :is-active="record.is_active"
                    :data="record"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="props.record.data.length" class="border-t border-gray-200 px-6 py-4">
        <Pagination
          :links="props.record.meta.links"
          :from="props.record.meta.from"
          :to="props.record.meta.to"
          :total="props.record.meta.total"
          :current_page="props.record.meta.current_page"
          :last_page="props.record.meta.last_page"
        />
      </div>
    </div>

    <!-- CONFIRMATION DIALOG -->
    <confirm-dialog
      @canceled="showDeleteConfirmation = false"
      @close="showDeleteConfirmation = false"
      @confirmed="confirmAction"
      v-if="showDeleteConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Sim"
      cancel="Não"
    />
  </div>
</template>

<script setup>
import { ref, watch, reactive, computed } from "vue";
import Pagination from "@/Components/pagination.vue";
import selectFilter from "@/Components/select-filter.vue";
import emptyState from "@/Components/empty-state.vue";
import DatePickerEnhanced from "@/Components/date-picker-enhanced.vue";
import selectAction from "@/Components/select-action.vue";
import debounce from "lodash/debounce";
import { useForm, router, usePage } from "@inertiajs/vue3";
import {
  MagnifyingGlassIcon,
  SquaresPlusIcon,
} from "@heroicons/vue/24/outline";
import { usePermission } from "@/Composables/usePermissions";
import { trans } from "laravel-vue-i18n";
import confirmDialog from "@/Components/confirm-dialog.vue";

const { hasRole, hasPermission } = usePermission();

const props = defineProps({
  record: Object,
  createAction: {
    type: Boolean,
    default: true,
  },
  hasQr: {
    type: Boolean,
    default: false,
  },
  slideOverEdit: Boolean,
  fields: {
    type: Array,
    default: [],
  },
  actions: Array,
  model: {
    type: String,
    default: "",
  },
  abilities: {
    type: Array,
    default: [],
  },
  query: Object,
});

const query = reactive({
  search: props.query?.search,
  filter: props.query?.filter,
  date: props.query?.date,
  page: null,
});

const actionId = ref(null);
const recordId = ref(null);
const recordUrl = ref(null);
const showDeleteConfirmation = ref(false);

const confirmationDialogTitle = computed(() => {
  return trans("gestlab.actions.confirmation_dialog_title." + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return trans(
    "gestlab.actions.confirmation_dialog_description." + actionId.value,
  );
});

// Helper function to open confirmation dialog
const openConfirmation = (action, record) => {
  actionId.value = action;
  recordId.value = record.id;
  recordUrl.value = getRecordUrl(action, record);
  showDeleteConfirmation.value = true;
};

// Helper function to get appropriate URL based on action
const getRecordUrl = (action, record) => {
  switch (action) {
    case 'delete':
      return record.links.delete_path;
    case 'edit':
      return record.links.edit_path;
    case 'restore':
      return record.links.restore_path;
    default:
      return null;
  }
};

const confirmAction = () => {
  switch (actionId.value) {
    case "delete":
      router.get(
        recordUrl.value,
        {
          recordIds: [recordId.value],
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            resetConfirmation();
          },
        },
      );
      break;

    case "edit":
      router.get(
        recordUrl.value,
        {
          recordIds: [recordId.value],
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            resetConfirmation();
          },
        },
      );
      break;

    case "edit_slide":
      emit("slideover-on", recordId.value);
      resetConfirmation();
      break;

    case "restore":
      router.get(
        recordUrl.value,
        {
          recordIds: [recordId.value],
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            resetConfirmation();
          },
        },
      );
      break;
  }
};

const resetConfirmation = () => {
  showDeleteConfirmation.value = false;
  actionId.value = null;
  recordId.value = null;
  recordUrl.value = null;
};

// Watch query changes with debounce
watch(
  query,
  debounce(function (value) {
    router.get(usePage().url, value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }, 300),
);

const filters = [
  {
    id: null,
    label: "gestlab.filter.filter",
  },
  {
    id: "trashed",
    label: "gestlab.filter.excluded",
  },
];

let changeFilter = (filterId) => {
  query.filter = filterId;
};

const range = ref({
  start: null,
  end: null,
});

const updateRange = (e) => {
  query.date = e;
};

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});

const emit = defineEmits(["execute-action", "slideover-on", "create-record"]);

function executeAction(selected) {
  emit("execute-action", selected);
}
</script>

<style scoped>
/* Custom styles for transitions */
button, a {
  transition: all 0.2s ease-in-out;
}

/* Row hover effect */
tr {
  transition: background-color 0.15s ease-in-out;
}

/* Smooth checkbox transitions */
input[type="checkbox"] {
  transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

/* Table cell animations */
td {
  transition: background-color 0.15s ease-in-out;
}
</style>