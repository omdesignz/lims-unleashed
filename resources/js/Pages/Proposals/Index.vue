<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, h } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar'
import combobox from "@/Components/combobox.vue";
import { TrashIcon, PencilIcon, ArrowPathRoundedSquareIcon, EyeIcon } from '@heroicons/vue/24/outline';
import VapTable from '@/Components/vap-table/table.vue';
import { usePermission } from "@/Composables/usePermissions";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
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
    id: 'show',
    label: 'gestlab.actions.show'
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

let submit = () => {

    if(!form.id) {
      form.post(route('proposals.store'), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.put(route('proposals.update',{proposal: form.id}), {
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
      router.get(route('proposals.destroy'), {
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
        router.get(route('proposals.restore'), {
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
            },
          },
        );
        showDeleteConfirmation.value = false;
        break;

      case "edit_slide":
        openSlideoverWithData(record)

        showDeleteConfirmation.value = false;
        break;

      case "show":
        router.get(route("proposals.show", { id: record.id }))
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


</script>
<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.page_title') }}</h3>
</div>


<vap-table
    :model="props.model" 
    :abilities="props.abilities" 
    :data="props.record.data" 
    :columns="columns"
    :create-action="true"
    :query="props.query" 
    :filters="filters" 
    :initialFilters="props.initialFilters"
    :initialSortField=props.initialSortField
    :initialSortDirection=props.initialSortDirection
    :initialIncludes="props.initialIncludes"
    :trashedFilter="props.trashedFilter"
    :trashedOptions="props.trashedOptions"
    @create-record="router.get(route('proposals.create'))"
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
          <strong class="text-blue-900">{{ row.name }}</strong>
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
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <PencilIcon class="h-4 w-4" />

        </button>
        <button
          type="button"
          @click="
            () => {
              record = row;
              actionType = 'single';
              action = 'show';
              recordUrl = row.links.show_path;
              showDeleteConfirmation = true;
            }
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
          v-if="
            !row.deleted && hasPermission('delete_' + props.model)
          "
        >
        <EyeIcon class="h-4 w-4" />

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

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">

            <!-- Proposal -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="proposal_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposals.proposal_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.proposal_id" v-model="form.proposal_id" :load-options="loadProposals"/>
                  <p v-if="form.errors.proposal_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.proposal_id }}</p>
                </div>
              </div>

              <!-- Confidentiality -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="confidentiality" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposals.confidentiality') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.confidentiality" type="checkbox" name="confidentiality" id="confidentiality" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
                  <p v-if="form.errors.confidentiality" class="mt-2 text-sm text-red-600" id="confidentiality-error">{{ form.errors.confidentiality }}</p>
                </div>
              </div>


                <!-- Impartiality -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="impartiality" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposals.impartiality') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.impartiality" type="checkbox" name="impartiality" id="impartiality" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
                  <p v-if="form.errors.impartiality" class="mt-2 text-sm text-red-600" id="impartiality-error">{{ form.errors.impartiality }}</p>
                </div>
              </div>

            <!-- Non Disclosure -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="nondisclosure" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposals.nondisclosure') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.nondisclosure" type="checkbox" name="nondisclosure" id="nondisclosure" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
                  <p v-if="form.errors.nondisclosure" class="mt-2 text-sm text-red-600" id="nondisclosure-error">{{ form.errors.nondisclosure }}</p>
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
        <button v-if="form.isDirty" @click="showDeleteConfirmationSlideover = true" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />

</div>
</template>
