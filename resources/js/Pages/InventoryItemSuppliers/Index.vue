<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  BuildingStorefrontIcon,
  CheckIcon,
  ClipboardDocumentCheckIcon,
} from "@heroicons/vue/24/outline";
import { Link, router, useForm } from "@inertiajs/vue3";
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
const drawerTitle = computed(() => form.id ? "Editar fornecedor" : "Novo fornecedor");
const drawerDescription = computed(() => form.id
  ? "Atualize os dados cadastrais usados na qualificação e na rastreabilidade de compras."
  : "Registe um fornecedor antes de o associar a itens, encomendas ou avaliações de risco.");
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function initialFormData() {
  return { id: null, name: "", address: "" };
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
    name: record.name ?? "",
    address: record.address ?? "",
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
    form.put(route("isuppliers.update", { isupplier: form.id }), options);
    return;
  }

  form.post(route("isuppliers.store"), options);
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

  const routeName = actionId.value === "restore" ? "isuppliers.restore" : "isuppliers.destroy";

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
            <BuildingStorefrontIcon class="h-5 w-5" aria-hidden="true" />
          </span>
          <div>
            <p class="ds-kicker">Cadeia de fornecimento</p>
            <h1 class="ds-heading mt-1 text-2xl">Fornecedores de inventário</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Diretório controlado de entidades que fornecem reagentes, consumíveis, equipamentos e serviços críticos.</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
          <span class="inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-bold text-[var(--ds-text-muted)]">
            {{ totalRecords }} fornecedores
          </span>
          <Link :href="route('supplier-assessments.index')" class="ds-button ds-button-secondary">
            <ClipboardDocumentCheckIcon class="h-4 w-4" aria-hidden="true" />
            Avaliações
          </Link>
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
        <div class="space-y-6 px-6 py-6">
          <div>
            <p class="ds-kicker">Identificação legal</p>
            <h2 class="ds-heading mt-1 text-base">Dados cadastrais</h2>
          </div>

          <div>
            <label for="supplier-name" class="ds-field-label mb-2 block">Nome do fornecedor</label>
            <input id="supplier-name" v-model="form.name" type="text" class="ds-field" autocomplete="organization" autofocus>
            <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
          </div>

          <div>
            <label for="supplier-address" class="ds-field-label mb-2 block">Morada</label>
            <textarea id="supplier-address" v-model="form.address" rows="4" class="ds-field min-h-28 resize-y" autocomplete="street-address"></textarea>
            <p v-if="form.errors.address" class="ds-field-error mt-2">{{ form.errors.address }}</p>
          </div>

          <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 text-sm leading-6 text-[var(--ds-text-muted)]">
            A aprovação técnica, o nível de risco e a periodicidade de revisão são geridos no registo de avaliações.
          </div>
        </div>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closeDrawer">Cancelar</button>
          <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty" @click="submit">
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            {{ form.processing ? "A guardar..." : (form.id ? "Atualizar fornecedor" : "Guardar fornecedor") }}
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
