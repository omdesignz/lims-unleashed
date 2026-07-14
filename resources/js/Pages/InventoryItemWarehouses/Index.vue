<script setup>
import Combobox from "@/Components/combobox.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  CheckIcon,
  CubeIcon,
  MapPinIcon,
  BeakerIcon,
  Squares2X2Icon,
} from "@heroicons/vue/24/outline";
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
const drawerTitle = computed(() => form.id ? "Editar armazém" : "Novo armazém");
const drawerDescription = computed(() => form.id
  ? "Actualize a zona física e as condições ambientais declaradas para este armazém."
  : "Crie uma zona de existências com condições de conservação claramente identificadas.");
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const storageConditions = [
  { key: "is_refrigerated", label: "Refrigeração", description: "A zona dispõe de controlo de frio.", icon: BeakerIcon },
  { key: "is_ventilated", label: "Ventilação", description: "Existe renovação de ar na área.", icon: Squares2X2Icon },
  { key: "has_air_exhaustion", label: "Extração de ar", description: "A zona dispõe de exaustão dedicada.", icon: CubeIcon },
];

function initialFormData() {
  return {
    id: null,
    location_id: null,
    name: "",
    is_refrigerated: false,
    is_ventilated: false,
    has_air_exhaustion: false,
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
    location_id: record.location_id ? { value: record.location_id, label: record.location } : null,
    name: record.name ?? "",
    is_refrigerated: Boolean(record.is_refrigerated),
    is_ventilated: Boolean(record.is_ventilated),
    has_air_exhaustion: Boolean(record.has_air_exhaustion),
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
    form.put(route("iwarehouses.update", { iwarehouse: form.id }), options);
    return;
  }

  form.post(route("iwarehouses.store"), options);
}

async function loadLocations(query, setOptions) {
  const response = await fetch(`/ilocations/getInventoryItemLocation?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((location) => ({
    value: location.id,
    label: location.address ? `${location.name} · ${location.address}` : location.name,
  })));
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

  const routeName = actionId.value === "restore" ? "iwarehouses.restore" : "iwarehouses.destroy";

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
            <CubeIcon class="h-5 w-5" aria-hidden="true" />
          </span>
          <div>
            <p class="ds-kicker">Conservação de materiais</p>
            <h1 class="ds-heading mt-1 text-2xl">Armazéns e zonas de existências</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Áreas controladas onde lotes e existências são mantidos com condições ambientais identificadas.</p>
          </div>
        </div>

        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-bold text-[var(--ds-text-muted)]">
          <MapPinIcon class="h-4 w-4" aria-hidden="true" />
          {{ totalRecords }} armazéns
        </span>
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
            <p class="ds-kicker">Identificação da zona</p>
            <h2 class="ds-heading mt-1 text-base">Localização e condições</h2>
          </div>

          <div>
            <Combobox
              v-model="form.location_id"
              title-label="Localização física"
              placeholder="Pesquisar localização"
              :has-error="Boolean(form.errors.location_id)"
              :load-options="loadLocations"
            />
            <p v-if="form.errors.location_id" class="ds-field-error mt-2">{{ form.errors.location_id }}</p>
          </div>

          <div>
            <label for="warehouse-name" class="ds-field-label mb-2 block">Nome do armazém</label>
            <BaseInput id="warehouse-name" v-model="form.name" type="text" class="ds-field" autofocus placeholder="Ex.: Câmara fria de reagentes" />
            <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
          </div>

          <fieldset>
            <legend class="ds-field-label">Condições ambientais disponíveis</legend>
            <div class="mt-3 divide-y divide-[var(--ds-border)] overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]">
              <label v-for="condition in storageConditions" :key="condition.key" class="flex cursor-pointer items-start gap-3 px-4 py-4">
                <CheckboxInput v-model="form[condition.key]" type="checkbox" class="ds-checkbox mt-0.5" />
                <component :is="condition.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-semibold text-[var(--ds-text)]">{{ condition.label }}</span>
                  <span class="mt-0.5 block text-xs leading-5 text-[var(--ds-text-muted)]">{{ condition.description }}</span>
                </span>
              </label>
            </div>
          </fieldset>
        </div>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closeDrawer">Cancelar</button>
          <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty" @click="submit">
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            {{ form.processing ? "A guardar..." : (form.id ? "Actualizar armazém" : "Guardar armazém") }}
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
