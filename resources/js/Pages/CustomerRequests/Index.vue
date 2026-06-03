<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import combobox from "@/Components/combobox.vue";
import { trans } from 'laravel-vue-i18n';


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
    email: '',
    contact: '',
    description: '',
    category_id: '',
    category: '',
    customer_id: '',
    customer: '',
    warehouse_id: '',
    warehouse: '',
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
    form.email = data.email;
    form.contact = data.contact;
    form.description = data.description;
    form.category_id = {
      value: data.category_id,
      label: data.category
    };
    form.customer_id = {
      value: data.customer_id,
      label: data.customer
    };
    form.warehouse_id = {
      value: data.warehouse_id,
      label: data.warehouse
    };     
}

let submit = () => {

    if(!form.id) {
      form.post(route('customerrequests.store'), {
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
      form.put(route('customerrequests.update',{request: form.id}), {
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
      router.get(route('customerrequests.destroy'), {
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
        router.get(route('customerrequests.restore'), {
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

function loadWarehouses(query, setOptions) {
    fetch('/warehouses/getWarehouse?q=' + query + (form.customer_id ? '&customer_id=' + form.customer_id.value : ''))
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.address,
            };
        })
        );
    });
}

function loadCategories(query, setOptions) {
    fetch('/customerrequestcategories/getCustomerRequestCategory?q=' + query)
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
</script>
<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="openslideover=true" @slideover-on="openSlideoverWithData"/> <br>

<slide-over v-if="openslideover" :class="commercialDocumentThemeClasses" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">

              <!-- Category -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="category_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_requests.category_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.category_id" v-model="form.category_id" :load-options="loadCategories"/>
                  <p v-if="form.errors.category_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.category_id }}</p>
                </div>
              </div>

              <!-- Customer -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_requests.customer_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers"/>
                  <p v-if="form.errors.customer_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.customer_id }}</p>
                </div>
              </div>

              <!-- Warehouse -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_requests.warehouse_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
                  <p v-if="form.errors.warehouse_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.warehouse_id }}</p>
                </div>
              </div>

              <!-- Description -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="description" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_requests.description') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <textarea v-model="form.description" name="description" id="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.description" class="mt-2 text-sm text-red-600" id="description-error">{{ form.errors.description }}</p>
                </div>
              </div>

              <!-- Contact -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="contact" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_requests.contact') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.contact" type="text" name="contact" id="contact" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.contact ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.contact" class="mt-2 text-sm text-red-600" id="contact-error">{{ form.errors.contact }}</p>
                </div>
              </div>

              <!-- Email -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="email" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.customer_requests.email') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.email" type="email" name="email" id="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.email" class="mt-2 text-sm text-red-600" id="email-error">{{ form.errors.email }}</p>
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.category_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.category_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.customer_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.warehouse_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.description') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.description }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.contact') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.contact }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customer_requests.email') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.email }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>
