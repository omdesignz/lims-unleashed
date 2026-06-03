<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import slideOver from '@/Components/slide-over.vue';
import { computed, ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
  record: Object,
  fields: Array,
  model: String,
  abilities: Array,
  query: Object,
  formulas: {
    type: Array,
    default: () => [],
  },
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

defineOptions({
  layout: Layout,
});

const form = useForm({
  name: '',
  value: '',
  formula_id: null,
  id: null,
});

const actionId = ref(null);
const openslideover = ref(false);
const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text',
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete',
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore',
  },
];

const slideOverDescription = computed(() => {
  return !form.id
    ? trans('gestlab.slideover.creating.description') + form.name
    : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.description');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
});

const close = () => {
  openslideover.value = false;
  form.clearErrors();
};

const openSlideoverWithData = (data) => {
  openslideover.value = true;
  form.id = data.id;
  form.name = data.name;
  form.value = data.value;
  form.formula_id = data.formula_id?.value ?? null;
};

const submit = () => {
  if (!form.id) {
    form.post(route('variables.store'), {
      preserveScroll: true,
      preserveState: false,
      onError: () => {
        showDeleteConfirmationSlideover.value = false;
        openslideover.value = true;
      },
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  } else {
    form.put(route('variables.update', { variableVariable: form.id }), {
      preserveScroll: true,
      preserveState: false,
      onError: () => {
        showDeleteConfirmationSlideover.value = false;
        openslideover.value = true;
      },
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  }
};

const confirmAction = () => {
  executeAction(actionId.value);
};

const executeAction = (selectedActionId) => {
  const recordIds = props.record.data.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length) {
    return;
  }

  switch (selectedActionId) {
    case 'delete':
      router.get(route('variables.destroy'), {
        recordIds,
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
          showDeleteConfirmation.value = false;
          actionId.value = null;
        },
      });
      break;
    case 'restore':
      router.get(route('variables.restore'), {
        recordIds,
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
          showDeleteConfirmation.value = false;
          actionId.value = null;
        },
      });
      break;
  }

  showDeleteConfirmation.value = false;
};
</script>

<template>
  <div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.variables.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">
      Defina variáveis reutilizáveis para fórmulas e cálculos laboratoriais.
    </p>
  </div>

  <records-table
    :record="props.record"
    :model="props.model"
    :abilities="props.abilities"
    :fields="props.fields"
    :slideOverEdit="props.slideOverEdit"
    :query="props.query"
    :actions="actions"
    @execute-action="($event) => { showDeleteConfirmation = true; actionId = $event; }"
    @create-record="openslideover = true"
    @slideover-on="openSlideoverWithData"
  />
  <br>

  <slide-over v-if="openslideover" :class="commercialDocumentThemeClasses" :title="slideOverTitle" :description="slideOverDescription" @close="close">
    <template #content>
      <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">
        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
          <div>
            <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.variables.name') }}</label>
          </div>
          <div class="sm:col-span-2">
            <input id="name" v-model="form.name" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>
        </div>

        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
          <div>
            <label for="value" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.variables.value') }}</label>
          </div>
          <div class="sm:col-span-2">
            <input id="value" v-model="form.value" type="text" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
            <p v-if="form.errors.value" class="mt-2 text-sm text-red-600">{{ form.errors.value }}</p>
          </div>
        </div>

        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
          <div>
            <label for="formula_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.variables.formula_id') }}</label>
          </div>
          <div class="sm:col-span-2">
            <select id="formula_id" v-model="form.formula_id" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
              <option :value="null">Selecionar fórmula</option>
              <option v-for="formula in props.formulas" :key="formula.value" :value="formula.value">
                {{ formula.label }}
              </option>
            </select>
            <p v-if="form.errors.formula_id" class="mt-2 text-sm text-red-600">{{ form.errors.formula_id }}</p>
          </div>
        </div>
      </div>
    </template>

    <template #action_buttons>
      <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="openslideover = false; form.reset()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <button v-if="form.isDirty" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800" :disabled="form.processing" @click="showDeleteConfirmationSlideover = true">
          {{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}
        </button>
      </div>
    </template>
  </slide-over>

  <confirm-dialog v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" @canceled="showDeleteConfirmation = false" @close="showDeleteConfirmation = false" @confirmed="confirmAction" />

  <confirm-dialog
    v-if="showDeleteConfirmationSlideover"
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    :title="$t('gestlab.actions.confirmation_dialog_title.default')"
    :description="$t('gestlab.actions.confirmation_dialog_description.default')"
    confirm="Sim"
    cancel="Não"
    @canceled="showDeleteConfirmationSlideover = false"
    @close="showDeleteConfirmationSlideover = false"
    @confirmed="submit"
  >
    <div class="mt-4">
      <div class="mb-2 inline-flex rounded-full bg-blue-900 px-2 py-1 text-xs font-semibold text-white">
        <p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p>
      </div>
      <div class="mt-6 border-t border-gray-100">
        <dl class="divide-y divide-gray-100">
          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.variables.name') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.name }}</dd>
          </div>
          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.variables.value') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.value }}</dd>
          </div>
          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.variables.formula_id') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
              {{ props.formulas.find((formula) => formula.value === form.formula_id)?.label || 'Sem fórmula' }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </confirm-dialog>
</template>
