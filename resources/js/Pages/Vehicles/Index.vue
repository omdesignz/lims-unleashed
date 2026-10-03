<script setup>
import Combobox from "@/Components/combobox.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  Building2 as BuildingOffice2Icon,
  Check as CheckIcon,
  IdCard as IdentificationIcon,
  Truck as TruckIcon,
} from "@lucide/vue";
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
const representedDepartments = computed(() => new Set(props.record.data.map((vehicle) => vehicle.department_id).filter(Boolean)).size);
const drawerTitle = computed(() => form.id ? `Editar viatura ${form.number_plate}` : "Nova viatura");
const drawerDescription = computed(() => form.id
  ? "Actualize a afectação operacional e a classificação da viatura."
  : "Registe uma viatura usada em recolhas, transporte de amostras ou operações de campo.");
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
    number_plate: "",
    category_id: null,
    department_id: null,
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
    number_plate: record.number_plate ?? "",
    category_id: record.category_id ? { value: record.category_id, label: record.category } : null,
    department_id: record.department_id ? { value: record.department_id, label: record.department } : null,
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
    form.put(route("vehicles.update", { vehicle: form.id }), options);
    return;
  }

  form.post(route("vehicles.store"), options);
}

async function loadDepartments(query, setOptions) {
  const response = await fetch(`/departments/getDepartment?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((department) => ({ value: department.id, label: department.name })));
}

async function loadCategories(query, setOptions) {
  const response = await fetch(`/transportcategories/getTransportCategory?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((category) => ({ value: category.id, label: category.name })));
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

  const routeName = actionId.value === "restore" ? "vehicles.restore" : "vehicles.destroy";

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
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <TruckIcon class="h-5 w-5" aria-hidden="true" />
          </span>
          <div>
            <p class="ds-kicker">Logística de campo</p>
            <h1 class="ds-heading mt-1 text-2xl">Viaturas operacionais</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Frota autorizada para recolhas, transporte de amostras e deslocações técnicas com responsabilidade departamental.</p>
          </div>
        </div>

        <div class="grid w-full grid-cols-2 overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] lg:w-auto lg:min-w-72">
          <div class="border-r border-[var(--ds-border)] px-4 py-3">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Viaturas</p>
            <p class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ totalRecords }}</p>
          </div>
          <div class="px-4 py-3">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Departamentos</p>
            <p class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ representedDepartments }}</p>
          </div>
        </div>
      </div>
    </section>

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
        <div class="space-y-7 px-6 py-6">
          <div>
            <p class="ds-kicker">Identificação da frota</p>
            <h2 class="ds-heading mt-1 text-base">Matrícula e afectação</h2>
          </div>

          <div>
            <label for="vehicle-number-plate" class="ds-field-label mb-2 block">Matrícula</label>
            <div class="relative">
              <IdentificationIcon class="pointer-events-none absolute left-3 top-3.5 h-5 w-5 text-[var(--ds-text-soft)]" aria-hidden="true" />
              <BaseInput id="vehicle-number-plate" v-model="form.number_plate" type="text" class="ds-field pl-10 font-mono uppercase" autocomplete="off" autofocus />
            </div>
            <p v-if="form.errors.number_plate" class="ds-field-error mt-2">{{ form.errors.number_plate }}</p>
          </div>

          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <Combobox
                v-model="form.category_id"
                title-label="Categoria de transporte"
                placeholder="Pesquisar categoria"
                :has-error="Boolean(form.errors.category_id)"
                :load-options="loadCategories"
              />
              <p v-if="form.errors.category_id" class="ds-field-error mt-2">{{ form.errors.category_id }}</p>
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
          </div>

          <div>
            <label for="vehicle-description" class="ds-field-label mb-2 block">Descrição operacional</label>
            <textarea id="vehicle-description" v-model="form.description" rows="5" class="ds-field min-h-32 resize-y" placeholder="Finalidade, capacidade ou restrições relevantes"></textarea>
            <p v-if="form.errors.description" class="ds-field-error mt-2">{{ form.errors.description }}</p>
          </div>

          <div class="flex items-start gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 text-sm text-[var(--ds-text-muted)]">
            <BuildingOffice2Icon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
            A afectação departamental identifica a unidade responsável pela disponibilidade e utilização da viatura.
          </div>
        </div>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closeDrawer">Cancelar</button>
          <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty" @click="submit">
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            {{ form.processing ? "A guardar..." : (form.id ? "Actualizar viatura" : "Guardar viatura") }}
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
