<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import combobox from "@/Components/combobox.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

let form = useForm({
    name: '',
    address: '',
    description: '',
    department_id: '',
    id: null,
});

const actionId = ref(null);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.description');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
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
];

const close = () => {
    openslideover.value = false;
    form.clearErrors();
    // form.reset();
}

const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.name = data.name;
    form.address = data.address;
    form.description = data.description;
    form.department_id = {
      value: data.department_id,
      label: data.department,
    }
    
}

let submit = () => {

    if(!form.id) {
      form.post(route('ilocations.store'), {
          preserveScroll: true,
          preserveState: false,
          onError: () => {
            showDeleteConfirmationSlideover.value = false
            openslideover.value = true
          },
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.put(route('ilocations.update',{ilocation: form.id}), {
          preserveScroll: true,
          preserveState: false,
          onError: () => {
            showDeleteConfirmationSlideover.value = false
            openslideover.value = true
          },
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    }
    
  }


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':
      router.get(route('ilocations.destroy'), {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(route('ilocations.restore'), {
          recordIds: recordIds
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
  }
}

// function loadDepartments() {
//     fetch('/departments/getDepartment?q=' + query)
//     .then(response => response.json())
//     .then(results => {
//         setOptions(
//         results.map(result => {
//             return {
//             value: result.id,
//             label: result.name, 
//             };
//         })
//         );
//     });
// }

function loadDepartments(query, setOptions) {
    fetch('/departments/getDepartment?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.name,
        }))
        );
    });
}
</script>
<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.ilocations.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="openslideover=true" @slideover-on="openSlideoverWithData"/> <br>

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">

              <!-- Department -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="department_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.ilocations.department_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.department_id" v-model="form.department_id" :load-options="loadDepartments"/>
                  <p v-if="form.errors.department_id" class="mt-2 text-sm text-red-600" id="department_id-error">{{ form.errors.department_id }}</p>
                </div>
              </div>
              <!-- Name -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.ilocations.name') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.name" type="text" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.name" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.name }}</p>
                </div>
              </div>

              <!-- Address -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="address" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.ilocations.address') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.address" type="text" name="address" id="address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.address ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.address" class="mt-2 text-sm text-red-600" id="address-error">{{ form.errors.address }}</p>
                </div>
              </div>

              <!-- Description -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="description" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.ilocations.description') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <textarea v-model="form.description" name="description" id="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.description" class="mt-2 text-sm text-red-600" id="description-error">{{ form.errors.description }}</p>
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

<confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmationSlideover=false" @close="showDeleteConfirmationSlideover=false" @confirmed="submit" v-if="showDeleteConfirmationSlideover" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.ilocations.department_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.department_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.ilocations.name') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.name }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.ilocations.address') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.address}}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.ilocations.description') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.description }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</div>
</template>
