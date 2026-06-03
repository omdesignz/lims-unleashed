<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
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
    name: '',
    code: '',
    symbol: '',
    thousand_separator: '',
    decimal_separator: '',
    swap_currency_symbol: false,
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
    form.code = data.code;
    form.symbol = data.symbol;
    form.thousand_separator = data.thousand_separator;
    form.decimal_separator = data.decimal_separator;
    form.swap_currency_symbol = data.swap_currency_symbol;
    
}

let submit = () => {

    if(!form.id) {
      form.post(route('currencies.store'), {
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
      form.put(route('currencies.update',{currency: form.id}), {
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
      router.get(route('currencies.destroy'), {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId.value = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(route('currencies.restore'), {
          recordIds: recordIds
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId.value = null;
            }
        });
        showDeleteConfirmation.value = false;
  }
}  
</script>
<template>
<div class="mb-6 rounded-[2rem] border border-slate-200 bg-white/90 p-6 shadow-sm ring-1 ring-slate-100 dark:border-slate-800 dark:bg-slate-950/90 dark:ring-slate-800">
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-700 dark:text-primary-300">Configuração comercial</p>
    <h3 class="mt-2 text-2xl font-semibold leading-7 text-slate-950 dark:text-white">{{ $t('gestlab.general.labels.currencies.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-slate-600 dark:text-slate-300">Defina moedas, símbolos e regras de formatação usadas nos documentos comerciais.</p>
</div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="openslideover=true" @slideover-on="openSlideoverWithData"/> <br>

<slide-over v-if="openslideover" :class="commercialDocumentThemeClasses" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">
              <!-- Name -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.currencies.name') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.name" type="text" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.name" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.name }}</p>
                </div>
              </div>

              <!-- Code -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="code" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.currencies.code') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.code" type="text" name="code" id="code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.code ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.code" class="mt-2 text-sm text-red-600" id="code-error">{{ form.errors.code }}</p>
                </div>
              </div>

              <!-- Symbol -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="symbol" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.currencies.symbol') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.symbol" type="text" name="symbol" id="symbol" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.symbol ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.symbol" class="mt-2 text-sm text-red-600" id="symbol-error">{{ form.errors.symbol }}</p>
                </div>
              </div>

              <!-- Thousand Separator -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="thousand_separator" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.currencies.thousand_separator') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.thousand_separator" type="text" name="thousand_separator" id="thousand_separator" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.thousand_separator ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.thousand_separator" class="mt-2 text-sm text-red-600" id="thousand_separator-error">{{ form.errors.thousand_separator }}</p>
                </div>
              </div>

              <!-- Decimal Separator -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="decimal_separator" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.currencies.decimal_separator') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.decimal_separator" type="text" name="decimal_separator" id="decimal_separator" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.decimal_separator ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.decimal_separator" class="mt-2 text-sm text-red-600" id="decimal_separator-error">{{ form.errors.decimal_separator }}</p>
                </div>
              </div>

              <!-- Swap Currency Symbol -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="swap_currency_symbol" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.currencies.swap_currency_symbol') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.swap_currency_symbol" type="checkbox" name="swap_currency_symbol" id="swap_currency_symbol" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.swap_currency_symbol ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.swap_currency_symbol" class="mt-2 text-sm text-red-600" id="swap_currency_symbol-error">{{ form.errors.swap_currency_symbol }}</p>
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

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />

<confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmationSlideover=false" @close="showDeleteConfirmationSlideover=false" @confirmed="submit" v-if="showDeleteConfirmationSlideover" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')">
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.currencies.name') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.name }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.currencies.code') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.code }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.currencies.symbol') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.symbol }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.currencies.thousand_separator') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.thousand_separator }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.currencies.decimal_separator') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.decimal_separator }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.currencies.swap_currency_symbol') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.swap_currency_symbol }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>
</template>
