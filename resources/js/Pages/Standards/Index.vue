<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import StandardForm from "@/Components/standards/StandardForm.vue";
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
});

const { hasPermission } = usePermission();
const isPanelOpen = ref(false);
const showActionConfirmation = ref(false);
const selectedAction = ref(null);

const emptyForm = () => ({ code: "", description: "", id: null });
const form = useForm(emptyForm());

const panelTitle = computed(() => form.id ? `Editar norma ${form.code}` : "Nova referência normativa");
const panelDescription = computed(() => form.id
  ? "Actualize a identificação e o âmbito de aplicabilidade desta referência."
  : "Adicione uma norma para utilização nos perfis e métodos do laboratório.");

const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

const pageRecords = computed(() => props.record?.data || []);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function openCreatePanel() {
  form.defaults(emptyForm());
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function openEditPanel(data) {
  form.defaults({
    code: data.code || "",
    description: data.description || "",
    id: data.id,
  });
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
      form.defaults(emptyForm());
      form.reset();
    },
  };

  if (form.id) {
    form.put(route("standards.update", { standard: form.id }), options);
    return;
  }

  form.post(route("standards.store"), options);
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
    route(`standards.${selectedAction.value}`),
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
    <PageHeader :title="$t('gestlab.general.labels.standards.page_title')" lede="Catálogo controlado das referências normativas usadas em perfis, métodos e evidências laboratoriais.">
      <template #actions>
        <button v-if="hasPermission('add_standards')" type="button" class="ds-button ds-button-primary" @click="openCreatePanel">
          <PlusIcon class="h-4 w-4" /> Nova referência </button>
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
        <form id="standard-form" @submit.prevent="submit">
          <StandardForm :form="form" />
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closePanel">{{ $t('gestlab.general.buttons.cancel') }}</button>
          <button type="submit" form="standard-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : form.id ? "Guardar alterações" : "Adicionar referência" }}
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
