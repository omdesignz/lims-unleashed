<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, h, watch, onMounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar'
import { TrashIcon, PencilIcon, ArrowPathRoundedSquareIcon } from '@heroicons/vue/24/outline';
import VapTable from '@/Components/vap-table/table.vue';
import { usePermission } from "@/Composables/usePermissions";
import combobox from '@/Components/combobox.vue';
// import comboboxMultiple from '@/Components/vap-table/combobox-multiple.vue';
import comboboxMultiple from '@/Components/combobox-multiple.vue';


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
    parameters: Array,
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

const selectedParameters = ref([]);

const toggleSelectedParameter = (parameter, index) => {

  console.log();

  if (selectedParameters.value.includes(parameter)) {
    selectedParameters.value.splice(selectedParameters.indexOf(parameter), 1);
  } else {
    selectedParameters.value.push(parameter);
  }

  router.reload({
    parameters: selectedParameters,
  },{
    // only: ['parameters'],
    preserveState: false,
    preserveScroll: true,
    replace: true,
    onSuccess: () => {
      console.log('reloaded successfully');
    },
  });

};

// Place parameter IDs in parameters query string to filter by them (e.g. ?parameters=1,2,3)


let form = useForm({
    name: '',
    code: '',
    description: '', 
    id: null,
});

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


const date = ref(null);

const actionType = ref(null);
const action = ref(null);
const record = ref(null);
const recordUrl = ref(null);
const selectedIDs = ref([]);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.description');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + action.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + action.value);
})


const openslideover = ref(false);

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
  {
    id: 'multiple_sample_worksheet',
    label: 'gestlab.actions.multiple_sample_worksheet'
  },
];

const filters = [
  {
    id: null,
    label: trans('gestlab.filter.none'),
  },
  {
    id: "trashed",
    label: trans('gestlab.filter.excluded'),
  },
];

const close = () => {
    openslideover.value = false;
    record.value = null;
    action.value = null;
    form.clearErrors();
    // form.reset();
}

const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.name = data.name;
    form.code = data.code;
    form.description = data.description;
    
}

let submit = () => {

    if(!form.id) {
      form.post(route('customercategories.store'), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.put(route('customercategories.update',{category: form.id}), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            // showDeleteConfirmationSlideover.value = false;
            form.reset()
          },
      });
    }
    
  }


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
      router.get(route('customercategories.destroy'), {
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
    
    case 'multiple_sample_worksheet':

    const url = route('directcollections.getMultipleParametersToAnalyzePDF', {
        recordIds: selectedIDs.value
    });

    window.open(url, '_blank');

    router.reload();

      break;

    case 'restore':
        router.get(route('customercategories.restore'), {
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


const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
  // month: "MMM",
  // input: 'YYYY-MM-DD',
});

const updateRange = (e) => {
  date.value = e;
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

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
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

watch(selectedParameters, () => {
    console.log(selectedParameters.value);

    router.get(route('samples.index'), {
      parameters: selectedParameters.value.map(parameter => parameter.value),
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        console.log('reloaded successfully');
      },
    });
})


</script>
<template>
<div class="mb-6 border-b border-gray-200 dark:border-gray-700 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">{{ $t('gestlab.general.labels.samples.page_title') }}</h3>
    <!-- <div class="flex flex-wrap gap-2" v-if="props?.parameters.length">
      <div v-for="parameter in props?.parameters" :key="parameter.value" class="flex items-center gap-2">
        <input type="checkbox" :id="parameter.value" :value="parameter.value" v-model="selectedParameters">
        <label :for="parameter.value" class="text-sm font-medium leading-6 text-gray-900">{{ parameter.label }}</label>
      </div>
    </div> -->
</div>


<vap-table
    :model="props.model" 
    :abilities="props.abilities" 
    :data="props.record.data" 
    :columns="columns" 
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
        <template #column-name="{ row }">
          <strong class="text-primary-900 dark:text-primary-400">{{ row.name }}</strong>
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
          class="text-gray-500 hover:text-primary-900 dark:hover:text-primary-400 transform transition-all duration-200 hover:scale-150"
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
          class="text-gray-500 hover:text-primary-900 dark:hover:text-primary-400 transform transition-all duration-200 hover:scale-150"
        >
        <PencilIcon class="h-4 w-4" />

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
          class="text-gray-500 hover:text-primary-900 dark:hover:text-primary-400 transform transition-all duration-200 hover:scale-150"
        >
        <PencilIcon class="h-4 w-4" />

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
          class="text-gray-500 hover:text-primary-900 dark:hover:text-primary-400 transform transition-all duration-200 hover:scale-150"
          v-if="
            !row.deleted && hasPermission('delete_' + props.model)
          "
        >
        <TrashIcon class="h-4 w-4" />

        </button>
        </template>

        <template #specific-filters>
          
          <!-- Select Filter: Parameters -->

          <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-12">
            <div class="sm:col-span-full">
              <comboboxMultiple
                  name="Parametros" 
                  v-model="selectedParameters"
                  :multiple="true"
                  :load-options="loadParameters"
                  @remove:modelValue="() => router.get(route('samples.index'), { parameters: selectedParameters.map(parameter => parameter.value) }, { preserveState: true, preserveScroll: true, onSuccess: () => console.log('reloaded successfully') })"
                  title-label="Parameters"
              />
            </div>

            <a :href="route('directcollections.getMultipleParametersToAnalyzePDF', {
              recordIds: selectedParameters.map(parameter => parameter.value)
            })"
            target="_blank"
            v-if="selectedParameters.length"
            class="w-96 inline-flex justify-center rounded-xl bg-primary-900 dark:bg-primary-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 dark:hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-900 transition-colors duration-150">{{ $t('gestlab.actions.multiple_sample_worksheet') }}</a>
          </div>
        </template>
</vap-table>

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">
              <!-- Name -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_categories.name') }}</label>
                </div>
                <div class="sm:col-span-full">
                    <comboboxMultiple v-model="selectedParameters" :load-options="loadParameters" @update:model-value="" multiple />
                </div>
              </div>

              <!-- Code -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="code" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_categories.code') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.code" type="text" name="code" id="code" class="block w-full rounded-lg border-0 py-1.5 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:focus:ring-primary-500 sm:text-sm sm:leading-6" :class="[form.errors.code ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.code" class="mt-2 text-sm text-red-600" id="code-error">{{ form.errors.code }}</p>
                </div>
              </div>
        </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="openslideover = false; form.reset()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <!-- <TransitionRoot
            :show="!form.isDirty"
            enter="transition-opacity duration-75"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity duration-150"
            leave-from="opacity-100"
            leave-to="opacity-0"
        >
            I will appear and disappear.
        </TransitionRoot> -->
        <button v-if="form.isDirty" @click="showDeleteConfirmationSlideover = true" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-xl bg-primary-900 dark:bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 dark:hover:bg-primary-500 transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="$t('gestlab.general.buttons.yes')" :cancel="$t('gestlab.general.buttons.no')" />

<confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmationSlideover=false" @close="showDeleteConfirmationSlideover=false" @confirmed="submit" v-if="showDeleteConfirmationSlideover" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" :confirm="$t('gestlab.general.buttons.yes')" :cancel="$t('gestlab.general.buttons.no')">
    <div class="mt-4">
      <div class="font-semibold inline-flex px-3 py-1 leading-4 text-xs rounded-full text-white bg-primary-900 dark:bg-primary-600 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-primary-900 dark:bg-primary-600">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_categories.name') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.name }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_categories.code') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.code }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_categories.description') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.description }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>