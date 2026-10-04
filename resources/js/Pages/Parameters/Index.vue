<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import ParameterForm from "@/Components/parameters/ParameterForm.vue";
import { createEmptyParameterData, createParameterDataFromRecord } from "@/Components/parameters/parameterFormData";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  Plus as PlusIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";
import { trans } from "laravel-vue-i18n";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    default: () => ({ data: [], meta: {} }),
  },
  fields: {
    type: Array,
    default: () => [],
  },
  model: String,
  abilities: {
    type: Array,
    default: () => [],
  },
  query: {
    type: Object,
    default: () => ({}),
  },
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
  formulas: {
    type: Array,
    default: () => [],
  },
});

const { hasPermission } = usePermission();
const isPanelOpen = ref(false);
const showActionConfirmation = ref(false);
const selectedAction = ref(null);
const form = useForm(createEmptyParameterData());

const panelTitle = computed(() => form.id ? `Editar parâmetro ${form.code || form.name}` : "Novo parâmetro analítico");
const panelDescription = computed(() => form.id
  ? "Actualize a definição técnica, comercial e de cálculo deste parâmetro."
  : "Configure um parâmetro reutilizável em perfis, worksheets e resultados.");

const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

const pageRecords = computed(() => props.record?.data || []);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function openCreatePanel() {
  form.defaults(createEmptyParameterData());
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function openEditPanel(record) {
  form.defaults(createParameterDataFromRecord(record));
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function closePanel() {
  isPanelOpen.value = false;
  form.clearErrors();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      closePanel();
      form.defaults(createEmptyParameterData());
      form.reset();
    },
  };

  if (form.id) {
    form.put(route("parameters.update", { parameter: form.id }), options);
    return;
  }

  form.post(route("parameters.store"), options);
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function executeBulkAction() {
  const recordIds = pageRecords.value.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    showActionConfirmation.value = false;
    return;
  }

  router.get(
    route(`parameters.${selectedAction.value}`),
    { recordIds },
    {
      preserveScroll: true,
      onFinish: () => {
        showActionConfirmation.value = false;
        selectedAction.value = null;
      },
    },
  );
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.parameters.page_title')" lede="Catálogo controlado de mensurandos, prazos, fiscalidade e regras de apresentacao dos resultados.">
      <template #actions>
        <button v-if="hasPermission('add_parameters')" type="button" class="ds-button ds-button-primary" @click="openCreatePanel">
          <PlusIcon class="h-4 w-4" /> Novo parâmetro </button>
      </template>
    </PageHeader>

    <RecordsTable
      :record="props.record"
      :model="props.model"
      :abilities="props.abilities"
      :fields="props.fields"
      :slide-over-edit="props.slideOverEdit"
      :query="props.query"
      :actions="actions"
      :create-action="false"
      @execute-action="requestBulkAction"
      @create-record="openCreatePanel"
      @slideover-on="openEditPanel"
    />

    <SlideOver v-if="isPanelOpen" :title="panelTitle" :description="panelDescription" @close="closePanel">
      <template #content>
        <form id="parameter-form" @submit.prevent="submit">
          <ParameterForm :form="form" :formulas="props.formulas" />
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closePanel">{{ $t('gestlab.general.buttons.cancel') }}</button>
          <button type="submit" form="parameter-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : form.id ? "Guardar alterações" : "Adicionar parâmetro" }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="showActionConfirmation = false"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
