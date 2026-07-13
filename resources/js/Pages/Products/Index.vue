<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { BeakerIcon, CubeIcon, ReceiptPercentIcon } from "@heroicons/vue/24/outline";
import { router } from "@inertiajs/vue3";
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
const showActionConfirmation = ref(false);
const totalRecords = computed(() => props.record.meta?.total ?? props.record.data.length);
const taxableRecords = computed(() => props.record.data.filter((product) => product.charge_tax).length);
const matrixCount = computed(() => new Set(props.record.data.map((product) => product.matrix_id).filter(Boolean)).size);
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function openCreateForm() {
  router.get(route("products.create"));
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

  const routeName = actionId.value === "restore" ? "products.restore" : "products.destroy";

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
            <p class="ds-kicker">Portefólio analítico</p>
            <h1 class="ds-heading mt-1 text-2xl">Produtos laboratoriais</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Ofertas comerciais ligadas a matrizes, preços e regras fiscais para propostas, guias e faturação.</p>
          </div>
        </div>

        <dl class="grid w-full grid-cols-3 overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] lg:w-auto lg:min-w-[24rem]">
          <div class="border-r border-[var(--ds-border)] px-4 py-3">
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Produtos</dt>
            <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ totalRecords }}</dd>
          </div>
          <div class="border-r border-[var(--ds-border)] px-4 py-3">
            <dt class="flex items-center gap-1 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><BeakerIcon class="h-3.5 w-3.5" /> Matrizes</dt>
            <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ matrixCount }}</dd>
          </div>
          <div class="px-4 py-3">
            <dt class="flex items-center gap-1 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><ReceiptPercentIcon class="h-3.5 w-3.5" /> Tributados</dt>
            <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ taxableRecords }}</dd>
          </div>
        </dl>
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
      @create-record="openCreateForm"
    />

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
