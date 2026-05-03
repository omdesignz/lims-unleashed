<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import combobox from "@/Components/combobox.vue";
import { trans } from 'laravel-vue-i18n';
import { EyeIcon } from "@heroicons/vue/24/outline";


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
    obs: '',
    customer_id: '',
    status: true,
    warehouse_id: '',
    invoice_id: '',
    code: '',
    cl_id: '',
    id: null,
});

const actionId = ref(null);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.code : trans('gestlab.slideover.updating.description') + form.code;
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
    form.reset();
}

const showDeleteConfirmation = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.obs = data.obs;
    form.code = data.code;
    form.status = data.status;
    form.customer_id = {
      value: data.customer_id,
      label: data.customer
    };
    form.warehouse_id = {
      value: data.warehouse_id,
      label: data.warehouse
    };
    form.cl_id = {
      value: data.cl_id,
      label: data.lab_code
    };      
}

let submit = () => {

    if(!form.id) {
      form.post(route('qualitycertificates.store'), {
          preserveScroll: true,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.put(route('qualitycertificates.update',{certificate: form.id}), {
          preserveScroll: true,
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
      router.get(route('qualitycertificates.destroy'), {
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
        router.get(route('qualitycertificates.restore'), {
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
    fetch('/warehouses/getWarehouse?q=' + query)
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

function loadLabCodes(query, setOptions) {
    fetch('/labcodes/getCode?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}
</script>
<template>
<div class="space-y-6">
<section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_30%),linear-gradient(135deg,_#ffffff,_#f8fafc)] shadow-sm dark:border-slate-700 dark:bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_30%),linear-gradient(135deg,_#0f172a,_#111827)]">
  <div class="flex flex-col gap-5 px-6 py-6 md:flex-row md:items-end md:justify-between">
    <div class="max-w-3xl">
      <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700 dark:text-sky-300">Emissão final</p>
      <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ $t('gestlab.general.labels.quality_certificates.page_title') }}</h3>
      <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
        Centralize os certificados emitidos, consulte rapidamente o histórico e abra cada documento final com menos fricção.
      </p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-right shadow-sm dark:border-slate-600 dark:bg-slate-900/80">
      <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Registos listados</p>
      <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ props.record?.data?.length ?? 0 }}</p>
    </div>
  </div>
</section>

<section class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
<records-table :createAction="false" :model="props.model" :abilities="props.abilities" :record="props.record" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @slideover-on="openSlideoverWithData">
  <template #actions="{ id }">
      <Link
          :href="route('qualitycertificates.show', {certificate: id})"
          class="text-gray-500 hover:text-primary-900 dark:hover:text-primary-400 transform transition-all duration-200 hover:scale-150"
        >
        <EyeIcon class="h-4 w-4" />
      </Link>
  </template>
</records-table>
</section>

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">

              <!-- Customer -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100 sm:mt-1.5">{{ $t('gestlab.general.labels.quality_certificates.customer_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers"/>
                  <p v-if="form.errors.customer_id" class="mt-2 text-sm text-red-600 dark:text-red-400" id="name-error">{{ form.errors.customer_id }}</p>
                </div>
              </div>

              <!-- Warehouse -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100 sm:mt-1.5">{{ $t('gestlab.general.labels.quality_certificates.warehouse_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
                  <p v-if="form.errors.warehouse_id" class="mt-2 text-sm text-red-600 dark:text-red-400" id="name-error">{{ form.errors.warehouse_id }}</p>
                </div>
              </div>

              <!-- Status -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="status" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100 sm:mt-1.5">{{ $t('gestlab.general.labels.quality_certificates.status') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.status" type="checkbox" name="status" id="status" class="h-5 w-5 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-primary-600 focus:ring-primary-600 sm:text-sm sm:leading-6" :class="[form.errors.status ? 'border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.status" class="mt-2 text-sm text-red-600 dark:text-red-400" id="name-error">{{ form.errors.status }}</p>
                </div>
              </div>


              <!-- Lab Code -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="cl_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100 sm:mt-1.5">{{ $t('gestlab.general.labels.quality_certificates.cl_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.cl_id" v-model="form.cl_id" :load-options="loadLabCodes"/>
                  <p v-if="form.errors.cl_id" class="mt-2 text-sm text-red-600 dark:text-red-400" id="name-error">{{ form.errors.cl_id }}</p>
                </div>
              </div>

              <!-- Obs -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="obs" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100 sm:mt-1.5">{{ $t('gestlab.general.labels.quality_certificates.obs') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <textarea v-model="form.obs" name="obs" id="obs" class="block w-full rounded-xl border-0 py-1.5 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:focus:ring-primary-500 sm:text-sm sm:leading-6" :class="[form.errors.obs ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.obs" class="mt-2 text-sm text-red-600 dark:text-red-400" id="obs-error">{{ form.errors.obs }}</p>
                </div>
              </div>
        </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-xl bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150" @click="openslideover = false; form.reset()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <button v-if="form.isDirty" @click="submit" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-xl bg-primary-900 dark:bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 dark:hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-900 transition-colors duration-150">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />
</div>
</template>
