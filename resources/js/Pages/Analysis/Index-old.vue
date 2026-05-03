<script setup>
import { RadioGroup, RadioGroupOption } from '@headlessui/vue'
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, reactive, computed, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n';
import { usePermission } from '@/Composables/usePermissions'
import { TrashIcon, PencilIcon, ArrowPathRoundedSquareIcon, CheckCircleIcon, DocumentPlusIcon, DocumentMagnifyingGlassIcon, DocumentCheckIcon } from '@heroicons/vue/24/outline';
import VapTable from '@/Components/vap-table/table.vue';
import sampleStatus from '@/Components/sample-status.vue';
import { usePage } from '@inertiajs/vue3'
import selectInput from '@/Components/select-input.vue'

const { hasRole, hasPermission } = usePermission();

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
  { value: 'insert', title: 'gestlab.general.labels.analysis.insert_result_title', description: 'gestlab.general.labels.analysis.insert_result_description'},
  { value: 'verify', title: 'gestlab.general.labels.analysis.verify_result_title', description: 'gestlab.general.labels.analysis.verify_result_description'},
  { value: 'approve', title: 'gestlab.general.labels.analysis.approve_result_title', description: 'gestlab.general.labels.analysis.approve_result_description'},
  { value: 'archived', title: 'gestlab.general.labels.analysis.completed_result_title', description: 'gestlab.general.labels.analysis.completed_result_description'}
]);

const selectedResultAction = computed(() => {
  
  switch (props.query.category) {
    case 'insert':
      return resultActions.value[0];
      break;
    case 'verify':
      return resultActions.value[1];
      break;
    case 'approve':
      return resultActions.value[2];
      break;
    case 'archived':
      return resultActions.value[3];
      break;
    default:
      return resultActions.value[0];
  }

});

onMounted(() => {
  changeAnalysisCategory();
});

// select option where value = category.value


// const selectedResultAction = () => {
//   return resultActions.value.find(action => action.value == category.value);
// }

const changeAnalysisCategory = (category='insert') => {
  category = category;

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


const columns = props.fields.map(field => {
    return {
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
    };
});

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

const updateRange = (e) => {
  range.value = e;
}

const actionId = ref(null);

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  // input: 'YYYY-MM-DD',
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + action.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + action.value);
})


let actions = [
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

const actionType = ref(null);
const action = ref(null);
const record = ref(null);
const recordUrl = ref(null);
const selectedIDs = ref([]);

const handleEdit = () => {
  router.get(route('analysis.create'));
}

const showDeleteConfirmation = ref(false);


const confirmAction = () => {

if(actionType.value === 'bulk') {
  executeBulkAction(action.value);
} else {
  executeSingleAction(action.value, record.value);
}
}

const executeBulkAction = (action) => {
    
    if(!selectedIDs.value.length) return;

    switch (action) {
    case 'delete':
      router.get(route('analysis.destroy'), {
          recordIds: selectedIDs.value
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            action = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(route('analysis.restore'), {
          recordIds: selectedIDs.value
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                action = null;
            }
        });
        showDeleteConfirmation.value = false;
    }
  };

  const executeSingleAction = (action, record ) => {
    
    switch (action) {
      case "delete":
        router.get(
          recordUrl.value,
          {
            recordIds: [record.id],
          },
          {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
              showDeleteConfirmation.value = false;
              action = null;
              record = null;
              recordUrl.value = null;
            },
          },
        );
        showDeleteConfirmation.value = false;
        break;

      case "edit":
        router.get(
          recordUrl.value,
          {
            recordIds: [record.id],
          },
          {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
              showDeleteConfirmation.value = false;
              action = null;
              record = null;
              recordUrl.value = null;
            },
          },
        );
        showDeleteConfirmation.value = false;
        break;

      case "edit_slide":
        openSlideoverWithData(record)

        showDeleteConfirmation.value = false;
        break;

      case "restore":
        router.get(
          recordUrl.value,
          {
            recordIds: [record.id],
          },
          {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
              showDeleteConfirmation.value = false;
              action = null;
              record = null;
              recordUrl.value = null;
            },
          },
        );
        showDeleteConfirmation.value = false;
    }

  };
</script>
<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Análises</h3>

    <!-- <sampleStatus /> -->

    <!-- create a div that places the select input on the right side of the page -->
      <div class="flex flex-col items-end">
        <select-input
          :options="props.departments"
          v-model="department"
          :selected="department"
          @update:modelValue="v => changeAnalysisDepartment(v)"
          label="Seleccione um Departamento"
        />
      </div>
    <fieldset>
      <legend class="text-sm font-semibold leading-6 text-gray-900">Seleccione uma acção:</legend>
      <RadioGroup :modelValue="selectedResultAction" class="mt-6 grid grid-cols-1 gap-y-6 sm:grid-cols-4 sm:gap-x-4" @update:modelValue="value => changeAnalysisCategory(value.value)" name="analysis-category">
        <RadioGroupOption as="template" v-for="action in resultActions" :key="action.value" :value="action" :aria-label="action.title" :aria-description="`${action.description}`" v-slot="{ active, checked }">
          <div :class="[active ? 'border-blue-900 ring-2 ring-blue-900' : 'border-gray-300', 'relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none']">
            <span class="flex flex-1">
              <span class="flex flex-col">
                <span class="block text-sm font-medium text-gray-900">{{ $t(action.title) }}</span>
                <span class="mt-1 flex items-center text-sm text-gray-500">{{ $t(action.description) }}</span>
                <span class="mt-6 text-sm font-medium text-gray-900">
                  <svg v-if="action.value == 'insert'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="-ml-0.5 h-5 w-5 text-gray-400 hover:text-orange-500 transform transition-all duration-200 hover:scale-150" :class="[props.query.category == 'insert' ? 'text-orange-500' : '']">
                    <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875zM12.75 12a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V18a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V12z" clip-rule="evenodd" />
                    <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                  </svg>

                  <svg v-if="action.value == 'verify'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="-ml-0.5 h-5 w-5 text-gray-400 hover:text-blue-500 transform transition-all duration-200 hover:scale-150" :class="[props.query.category == 'verify' ? 'text-sky-500' : '']">
                    <path d="M11.625 16.5a1.875 1.875 0 100-3.75 1.875 1.875 0 000 3.75z" />
                    <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875zm6 16.5c.66 0 1.277-.19 1.797-.518l1.048 1.048a.75.75 0 001.06-1.06l-1.047-1.048A3.375 3.375 0 1011.625 18z" clip-rule="evenodd" />
                    <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                  </svg>

                  <svg v-if="action.value == 'approve'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="-ml-0.5 h-5 w-5 text-gray-400 hover:text-green-500 transform transition-all duration-200 hover:scale-150" :class="[props.query.category == 'approve' ? 'text-green-500' : '']">
                    <path fill-rule="evenodd" d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5zm6.61 10.936a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 14.47a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" />
                  </svg>

                  <svg v-if="action.value == 'archived'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="-ml-0.5 h-5 w-5 text-gray-400 hover:text-indigo-500 transform transition-all duration-200 hover:scale-150" :class="[props.query.category == 'archived' ? 'text-indigo-500' : '']">
                    <path d="M21 6.375c0 2.692-4.03 4.875-9 4.875S3 9.067 3 6.375 7.03 1.5 12 1.5s9 2.183 9 4.875Z" />
                    <path d="M12 12.75c2.685 0 5.19-.586 7.078-1.609a8.283 8.283 0 0 0 1.897-1.384c.016.121.025.244.025.368C21 12.817 16.97 15 12 15s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.285 8.285 0 0 0 1.897 1.384C6.809 12.164 9.315 12.75 12 12.75Z" />
                    <path d="M12 16.5c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 15.914 9.315 16.5 12 16.5Z" />
                    <path d="M12 20.25c2.685 0 5.19-.586 7.078-1.609a8.282 8.282 0 0 0 1.897-1.384c.016.121.025.244.025.368 0 2.692-4.03 4.875-9 4.875s-9-2.183-9-4.875c0-.124.009-.247.025-.368a8.284 8.284 0 0 0 1.897 1.384C6.809 19.664 9.315 20.25 12 20.25Z" />
                  </svg>
                </span>
              </span>
            </span>
            <CheckCircleIcon :class="[!checked ? 'invisible' : '', 'h-5 w-5 text-blue-900']" aria-hidden="true" />
            <span :class="[active ? 'border' : 'border-2', checked ? 'border-blue-900' : 'border-transparent', 'pointer-events-none absolute -inset-px rounded-lg']" aria-hidden="true" />
          </div>
        </RadioGroupOption>
      </RadioGroup>
    </fieldset>
</div>

<!-- <records-table :record="props.record" :create-action="false" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="handleEdit"/> <br>  -->

  <vap-table
    :model="props.model" 
    :abilities="props.abilities" 
    :data="props.record.data" 
    :columns="[...columns, ...extraColumns]"
    :query="props.query" 
    :filters="filters" 
    :initialFilters="props.initialFilters"
    :initialSortField=props.initialSortField
    :initialSortDirection=props.initialSortDirection
    :initialIncludes="props.initialIncludes"
    :trashedFilter="props.trashedFilter"
    :trashedOptions="props.trashedOptions"
    @create-record="form.reset(), openslideover=true" @slideover-on="openSlideoverWithData" 
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
        <template #column-status="{ row }">
          <button type="button" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="row.status" :class="{ 'bg-blue-900': row.status, 'bg-gray-200': !row.status }">
          <!-- <strong class="text-blue-900">{{ row.status }}</strong> -->
          <span class="sr-only"></span>
            <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
            <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': row.status, 'translate-x-0': !row.status }">
                <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!row.status" :class="{ 'opacity-0 duration-100 ease-out': row.status, 'opacity-100 duration-200 ease-in': !row.status }">
                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </span>
                <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="row.status" :class="{ 'opacity-100 duration-200 ease-in': row.status, 'opacity-0 duration-100 ease-out': !row.status }">
                <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                </svg>
                </span>
            </span>
          </button>
        </template>

        <template #column-actions="{ row }">
          <!-- <strong class="text-blue-900">Actions go here!</strong>
          <button @click="() => {
            showDeleteConfirmation = true;
            actionType = 'single';
            action = 'edit_slide';
            record = row;
          }">Edit Slide</button> -->


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
          v-if="
            row.deleted && hasPermission('restore_' + props.model)
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <ArrowPathRoundedSquareIcon class="h-4 w-4" />
        </button>
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
          v-if="
            !row.deleted &&
            !!!props.slideOverEdit &&
            hasPermission('edit_' + props.model)
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <!-- <PencilIcon class="h-4 w-4" /> -->
        <DocumentPlusIcon class="h-4 w-4 text-orange-500" v-if="props.query.category == 'insert'" />
        <DocumentMagnifyingGlassIcon class="h-4 w-4 text-blue-500" v-if="props.query.category == 'verify'" />
        <DocumentCheckIcon class="h-4 w-4 text-green-500" v-if="props.query.category == 'approve'" />

        </button>
      <button
          @click="
            () => {
              record = row;
              actionType = 'single';
              action = 'edit_slide';
              showDeleteConfirmation = true;
            }
          "
          v-if="
            !row.deleted &&
            hasPermission('edit_' + props.model) &&
            !!props.slideOverEdit
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <!-- <PencilIcon class="h-4 w-4" /> -->
        <DocumentPlusIcon class="h-4 w-4 text-orange-500" v-if="props.query.category == 'insert'" />
        <DocumentMagnifyingGlassIcon class="h-4 w-4 text-blue-500" v-if="props.query.category == 'verify'" />
        <DocumentCheckIcon class="h-4 w-4 text-green-500" v-if="props.query.category == 'approve'" />

        </button>
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
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
          v-if="
            !row.deleted && hasPermission('delete_' + props.model)
          "
        >
        <TrashIcon class="h-4 w-4" />

        </button>
        </template>
</vap-table>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />
</template>