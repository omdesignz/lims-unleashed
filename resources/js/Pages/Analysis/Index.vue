<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Análises
          </h1>
          <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Gerencie e execute diferentes tipos de análises no sistema
          </p>
        </div>
        
        <!-- DEPARTMENT SELECTOR -->
        <div class="w-full md:w-64">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Departamento
          </label>
          <select-input
            :options="props.departments"
            v-model="department"
            :selected="department"
            @update:modelValue="v => changeAnalysisDepartment(v)"
            class="w-full"
          />
        </div>
      </div>

      <!-- ANALYSIS CATEGORY SELECTOR -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Selecione uma ação
        </h2>
        <RadioGroup :modelValue="selectedResultAction" 
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    @update:modelValue="value => changeAnalysisCategory(value.value)" 
                    name="analysis-category">
          <RadioGroupOption as="template" 
                           v-for="action in resultActions" 
                           :key="action.value" 
                           :value="action" 
                           v-slot="{ active, checked }">
            <div :class="[
              'relative flex cursor-pointer rounded-xl border p-4 focus:outline-none transition-all duration-200',
              active ? 'ring-2 ring-primary-500 border-primary-500 dark:ring-primary-400 dark:border-primary-400' : 'border-gray-200 dark:border-gray-700',
              checked ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'
            ]">
              <span class="flex flex-1">
                <span class="flex flex-col">
                  <!-- ACTION ICON -->
                  <span class="mb-3">
                    <DocumentPlusIcon v-if="action.value === 'insert'" 
                                     class="h-8 w-8 text-orange-500 dark:text-orange-400" />
                    <DocumentMagnifyingGlassIcon v-else-if="action.value === 'verify'" 
                                                 class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                    <DocumentCheckIcon v-else-if="action.value === 'approve'" 
                                       class="h-8 w-8 text-green-600 dark:text-green-400" />
                    <DocumentIcon v-else-if="action.value === 'archived'" 
                                 class="h-8 w-8 text-indigo-600" />
                  </span>
                  
                  <!-- ACTION TITLE -->
                  <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $t(action.title) }}
                  </span>
                  
                  <!-- ACTION DESCRIPTION -->
                  <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t(action.description) }}
                  </span>
                </span>
              </span>
              
              <!-- SELECTED INDICATOR -->
              <CheckCircleIcon :class="[
                'absolute top-3 right-3 h-5 w-5 transition-opacity duration-200',
                checked ? 'opacity-100 text-primary-600 dark:text-primary-400' : 'opacity-0'
              ]" aria-hidden="true" />
              
              <span :class="[
                'absolute -inset-px rounded-xl pointer-events-none',
                active ? 'ring-2 ring-primary-500 dark:ring-primary-400' : '',
                checked ? 'border border-primary-500/50 dark:border-primary-400/50' : ''
              ]" aria-hidden="true" />
            </div>
          </RadioGroupOption>
        </RadioGroup>
      </div>
    </div>

    <!-- ANALYSIS TABLE SECTION -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <vap-table
        :model="props.model" 
        :abilities="props.abilities" 
        :data="props.record.data" 
        :columns="[...columns, ...extraColumns]"
        :query="props.query" 
        :filters="filters" 
        :initialFilters="props.initialFilters"
        :initialSortField="props.initialSortField"
        :initialSortDirection="props.initialSortDirection"
        :initialIncludes="props.initialIncludes"
        :trashedFilter="props.trashedFilter"
        :trashedOptions="props.trashedOptions"
        @create-record="form.reset(), openslideover=true" 
        @slideover-on="openSlideoverWithData" 
        :slideOverEdit="props.slideOverEdit" 
        :pagination="props.record.meta" 
        @update-selected-ids="selectedIDs = $event"
        :actions="actions" 
        @execute-bulk-action="($event) => {
          showDeleteConfirmation = true; 
          action = $event.action; 
          actionType = $event.actionType
        }"
      >
        <!-- STATUS COLUMN TEMPLATE -->
        <template #column-status="{ row }">
          <button 
            type="button" 
            :class="[
              'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out',
              'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
              row.status ? 'bg-primary-600 border-primary-600' : 'bg-gray-200 border-gray-200 dark:bg-gray-700 dark:border-gray-700'
            ]" 
            :aria-checked="row.status" 
            role="switch"
          >
            <span class="sr-only">Toggle status</span>
            <span :class="[
              'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
              row.status ? 'translate-x-5' : 'translate-x-0'
            ]">
              <!-- DISABLED ICON -->
              <span :class="[
                'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity',
                row.status ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in'
              ]" aria-hidden="true">
                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                  <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
              
              <!-- ENABLED ICON -->
              <span :class="[
                'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity',
                row.status ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out'
              ]" aria-hidden="true">
                <svg class="h-3 w-3 text-primary-600" fill="currentColor" viewBox="0 0 12 12">
                  <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                </svg>
              </span>
            </span>
          </button>
        </template>

        <!-- ACTIONS COLUMN TEMPLATE -->
        <template #column-actions="{ row }">
          <div class="flex items-center gap-2">
            <!-- RESTORE ACTION -->
            <button
              type="button"
              @click="
                () => {
                  record = row;
                  actionType = 'single';
                  action = 'restore';
                  recordUrl = row.links.restore_path;
                  showDeleteConfirmation = true;
                }
              "
              v-if="row.deleted && hasPermission('restore_' + props.model)"
              class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-900/20 transition-colors duration-200"
              :title="$t('actions.restore')"
            >
              <ArrowPathRoundedSquareIcon class="h-4 w-4" />
            </button>
            
            <!-- EDIT ACTIONS (NON-SLIDEOVER) -->
            <button
              type="button"
              @click="
                () => {
                  record = row;
                  actionType = 'single';
                  action = 'edit';
                  recordUrl = row.links.edit_path;
                  showDeleteConfirmation = true;
                }
              "
              v-if="!row.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
              :class="[
                'p-1.5 rounded-lg transition-colors duration-200 text-gray-400',
                props.query.category === 'insert' ? 'hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20' : '',
                props.query.category === 'verify' ? 'hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20' : '',
                props.query.category === 'approve' ? 'hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' : ''
              ]"
              :title="$t('actions.edit')"
            >
              <DocumentPlusIcon v-if="props.query.category === 'insert'" class="h-4 w-4" />
              <DocumentMagnifyingGlassIcon v-else-if="props.query.category === 'verify'" class="h-4 w-4" />
              <DocumentCheckIcon v-else-if="props.query.category === 'approve'" class="h-4 w-4" />
            </button>
            
            <!-- EDIT ACTIONS (SLIDEOVER) -->
            <button
              @click="
                () => {
                  record = row;
                  actionType = 'single';
                  action = 'edit_slide';
                  showDeleteConfirmation = true;
                }
              "
              v-if="!row.deleted && hasPermission('edit_' + props.model) && props.slideOverEdit"
              :class="[
                'p-1.5 rounded-lg transition-colors duration-200 text-gray-400',
                props.query.category === 'insert' ? 'hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20' : '',
                props.query.category === 'verify' ? 'hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20' : '',
                props.query.category === 'approve' ? 'hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' : ''
              ]"
              :title="$t('actions.edit')"
            >
              <DocumentPlusIcon v-if="props.query.category === 'insert'" class="h-4 w-4" />
              <DocumentMagnifyingGlassIcon v-else-if="props.query.category === 'verify'" class="h-4 w-4" />
              <DocumentCheckIcon v-else-if="props.query.category === 'approve'" class="h-4 w-4" />
            </button>
            
            <!-- DELETE ACTION -->
            <button
              type="button"
              @click="
                () => {
                  record = row;
                  actionType = 'single';
                  action = 'delete';
                  recordUrl = row.links.delete_path;
                  showDeleteConfirmation = true;
                }
              "
              class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-200"
              v-if="!row.deleted && hasPermission('delete_' + props.model)"
              :title="$t('actions.delete')"
            >
              <TrashIcon class="h-4 w-4" />
            </button>
          </div>
        </template>
      </vap-table>
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
import { RadioGroup, RadioGroupOption } from '@headlessui/vue'
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { usePage } from '@inertiajs/vue3'
import selectInput from '@/Components/select-input.vue'
import confirmDialog from "@/Components/confirm-dialog.vue";
import { usePermission } from '@/Composables/usePermissions'
import { 
  TrashIcon, 
  ArrowPathRoundedSquareIcon, 
  CheckCircleIcon, 
  DocumentPlusIcon, 
  DocumentMagnifyingGlassIcon, 
  DocumentCheckIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline';
import VapTable from '@/Components/vap-table/table.vue';

const { hasPermission } = usePermission();
const page = usePage();

const props = defineProps({
    record: Object,
    departments: Array,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    trashedFilter: { type: Boolean, default: false },
    trashedOptions: { type: Object, default: {} },
    initialFilters: { type: Object, default: {} },
    initialSortField: { type: String, default: '' },
    initialSortDirection: { type: String, default: 'asc' },
    initialIncludes: { type: Array, default: [] },
    initialGlobalFilter: { type: String, default: '' },
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

const category = props.query.category || 'insert';
const department = ref(null);

const resultActions = ref([
  { 
    value: 'insert', 
    title: 'gestlab.general.labels.analysis.insert_result_title', 
    description: 'gestlab.general.labels.analysis.insert_result_description'
  },
  { 
    value: 'verify', 
    title: 'gestlab.general.labels.analysis.verify_result_title', 
    description: 'gestlab.general.labels.analysis.verify_result_description'
  },
  { 
    value: 'approve', 
    title: 'gestlab.general.labels.analysis.approve_result_title', 
    description: 'gestlab.general.labels.analysis.approve_result_description'
  },
  { 
    value: 'archived', 
    title: 'gestlab.general.labels.analysis.completed_result_title', 
    description: 'gestlab.general.labels.analysis.completed_result_description'
  }
]);

const selectedResultAction = computed(() => {
  return resultActions.value.find(action => action.value === props.query.category) || resultActions.value[0];
});

onMounted(() => {
  changeAnalysisCategory();
});

const changeAnalysisCategory = (category = 'insert') => {
  router.get(usePage().url, {
    category: category
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

const changeAnalysisDepartment = (v) => {
  department.value = v;
  router.get(usePage().url, {
    department: v
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

const columns = props.fields.map(field => ({
  field: field.value,
  filter_field: field.filter_field,
  label: field.name,
  visible: true,
  filterable: field.filterable,
  type: field.type,
  format: field.format,
  filter: field.filter,
  options: field.options ? field.options : [],
  config: field.config ? field.config : {}
}));

const extraColumns = ref([
  {
    field: 'category',
    filter_field: 'category',
    label: 'Categoria',
    visible: false,
    filterable: true,
    type: 'select',
    format: 'text',
    filter: 'text',
    options: [
      { value: 'insert', label: 'Inserir' },
      { value: 'verify', label: 'Verificar' },
      { value: 'approve', label: 'Validar' },
    ],
  },
]);

const showDeleteConfirmation = ref(false);
const actionType = ref(null);
const action = ref(null);
const record = ref(null);
const recordUrl = ref(null);
const selectedIDs = ref([]);

const confirmationDialogTitle = computed(() => {
  return 'Confirmar Ação';
});

const confirmationDialogDescription = computed(() => {
  switch (action.value) {
    case 'delete':
      return 'Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.';
    case 'restore':
      return 'Tem certeza que deseja restaurar este registro?';
    case 'edit':
    case 'edit_slide':
      return 'Tem certeza que deseja editar este registro?';
    default:
      return 'Deseja confirmar esta ação?';
  }
});

const actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text'
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete'
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore'
  },
];

const confirmAction = () => {
  if (actionType.value === 'bulk') {
    executeBulkAction(action.value);
  } else {
    executeSingleAction(action.value, record.value);
  }
}

const executeBulkAction = (action) => {
  if (!selectedIDs.value.length) return;

  switch (action) {
    case 'delete':
      router.get(route('analysis.destroy'), {
        recordIds: selectedIDs.value
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
          showDeleteConfirmation.value = false;
          action.value = null;
        }
      });
      break;
    case 'restore':
      router.get(route('analysis.restore'), {
        recordIds: selectedIDs.value
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
          showDeleteConfirmation.value = false;
          action.value = null;
        }
      });
      break;
  }
  showDeleteConfirmation.value = false;
};

const executeSingleAction = (action, record) => {
  switch (action) {
    case "delete":
      router.get(
        recordUrl.value,
        { recordIds: [record.id] },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            action.value = null;
            record.value = null;
            recordUrl.value = null;
          },
        },
      );
      break;
    case "edit":
      router.get(
        recordUrl.value,
        { recordIds: [record.id] },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            record.value = null;
            recordUrl.value = null;
          },
        },
      );
      break;
    case "edit_slide":
      // Handle slideover edit
      showDeleteConfirmation.value = false;
      break;
    case "restore":
      router.get(
        recordUrl.value,
        { recordIds: [record.id] },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            action.value = null;
            record.value = null;
            recordUrl.value = null;
          },
        },
      );
      break;
  }
};
</script>