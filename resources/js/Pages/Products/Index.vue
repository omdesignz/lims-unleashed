<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Download as ArrowDownTrayIcon } from "@lucide/vue";
import { Link, router } from "@inertiajs/vue3";
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
const { hasPermission } = usePermission();
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
  <div class="pl-page space-y-6">
    <PageHeader title="Produtos laboratoriais" lede="Ofertas comerciais ligadas a matrizes, preços e regras fiscais para propostas, guias e facturação.">
      <template #actions>
        <Link v-if="hasPermission('export_products')" :href="route('exports.index', { dataset: 'products' })" class="ds-button ds-button-secondary">
          Exportar<ArrowDownTrayIcon aria-hidden="true" />
        </Link>
      </template>
      <dl class="pl-cells mt-8">
        <div class="pl-cell"><dt class="pl-k pl-muted">Produtos</dt><dd class="pl-cell-value">{{ totalRecords }}</dd></div>
        <div class="pl-cell"><dt class="pl-k pl-muted">Matrizes</dt><dd class="pl-cell-value">{{ matrixCount }}</dd></div>
        <div class="pl-cell"><dt class="pl-k pl-muted">Tributados</dt><dd class="pl-cell-value">{{ taxableRecords }}</dd></div>
      </dl>
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
