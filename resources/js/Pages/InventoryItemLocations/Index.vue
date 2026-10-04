<script setup>
import Combobox from "@/Components/combobox.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Building2 as BuildingOffice2Icon, Check as CheckIcon } from "@lucide/vue";
import { router, useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});

const actionId = ref(null);
const isDrawerOpen = ref(false);
const showActionConfirmation = ref(false);
const totalRecords = computed(() => props.record.meta?.total ?? props.record.data.length);
const drawerTitle = computed(() => form.id ? "Editar localização" : "Nova localização");
const drawerDescription = computed(() => form.id
  ? "Actualize a responsabilidade organizacional e a identificação física desta área."
  : "Defina uma área física controlada antes de criar armazéns ou zonas de conservação.");
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function initialFormData() {
  return {
    id: null,
    department_id: null,
    name: "",
    address: "",
    description: "",
  };
}

const form = useForm(initialFormData());

function resetForm() {
  form.defaults(initialFormData());
  form.reset();
  form.clearErrors();
}

function openCreateDrawer() {
  resetForm();
  isDrawerOpen.value = true;
}

function openEditDrawer(record) {
  form.defaults({
    id: record.id,
    department_id: record.department_id ? { value: record.department_id, label: record.department } : null,
    name: record.name ?? "",
    address: record.address ?? "",
    description: record.description ?? "",
  });
  form.reset();
  form.clearErrors();
  isDrawerOpen.value = true;
}

function closeDrawer() {
  isDrawerOpen.value = false;
  resetForm();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: closeDrawer,
  };

  if (form.id) {
    form.put(route("ilocations.update", { ilocation: form.id }), options);
    return;
  }

  form.post(route("ilocations.store"), options);
}

async function loadDepartments(query, setOptions) {
  const response = await fetch(`/departments/getDepartment?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((department) => ({ value: department.id, label: department.name })));
}

function requestBulkAction(selectedActionId) {
  actionId.value = selectedActionId;
  showActionConfirmation.value = true;
}

function confirmAction() {
  const recordIds = props.record.data.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !actionId.value) {
    showActionConfirmation.value = false;
    return;
  }

  const routeName = actionId.value === "restore" ? "ilocations.restore" : "ilocations.destroy";

  router.get(route(routeName), { recordIds }, {
    preserveState: false,
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      actionId.value = null;
    },
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Localizações de inventário" lede="Áreas físicas sob responsabilidade departamental usadas para organizar armazéns, equipamentos e zonas de existências.">
      <template #actions>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-bold text-[var(--ds-text-muted)]">
          <BuildingOffice2Icon class="h-4 w-4" aria-hidden="true" />
          {{ totalRecords }} localizações
        </span>
      </template>
    </PageHeader>

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      @execute-action="requestBulkAction"
      @create-record="openCreateDrawer"
      @slideover-on="openEditDrawer"
    />

    <SlideOver v-if="isDrawerOpen" :title="drawerTitle" :description="drawerDescription" @close="closeDrawer">
      <template #content>
        <div class="space-y-6 px-6 py-6">
          <div>
            <p class="ds-kicker">Hierarquia da instalação</p>
            <h2 class="ds-heading mt-1 text-base">Responsabilidade e endereço</h2>
          </div>

          <div>
            <Combobox
              v-model="form.department_id"
              title-label="Departamento responsável"
              placeholder="Pesquisar departamento"
              :has-error="Boolean(form.errors.department_id)"
              :load-options="loadDepartments"
            />
            <p v-if="form.errors.department_id" class="ds-field-error mt-2">{{ form.errors.department_id }}</p>
          </div>

          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <label for="location-name" class="ds-field-label mb-2 block">Nome da localização</label>
              <BaseInput id="location-name" v-model="form.name" type="text" class="ds-field" autofocus />
              <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
            </div>

            <div>
              <label for="location-address" class="ds-field-label mb-2 block">Endereço interno</label>
              <BaseInput id="location-address" v-model="form.address" type="text" class="ds-field" placeholder="Edifício, piso, sala ou zona" />
              <p v-if="form.errors.address" class="ds-field-error mt-2">{{ form.errors.address }}</p>
            </div>
          </div>

          <div>
            <label for="location-description" class="ds-field-label mb-2 block">Descrição operacional</label>
            <textarea id="location-description" v-model="form.description" rows="5" class="ds-field min-h-32 resize-y" placeholder="Finalidade, restrições de acesso ou condições relevantes"></textarea>
            <p v-if="form.errors.description" class="ds-field-error mt-2">{{ form.errors.description }}</p>
          </div>
        </div>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closeDrawer">Cancelar</button>
          <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty" @click="submit">
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            {{ form.processing ? "A guardar..." : (form.id ? "Actualizar localização" : "Guardar localização") }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Confirmar"
      cancel="Cancelar"
      @canceled="showActionConfirmation = false"
      @close="showActionConfirmation = false"
      @confirmed="confirmAction"
    />
  </div>
</template>
