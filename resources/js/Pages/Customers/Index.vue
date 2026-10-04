<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Download as ArrowDownTrayIcon,
  Eye as EyeIcon,
  Plus as PlusIcon,
} from "@lucide/vue";
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

const { hasPermission } = usePermission();
const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const pageRecords = computed(() => props.record?.data || []);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function executeBulkAction() {
  const recordIds = pageRecords.value.filter((customer) => customer.selected).map((customer) => customer.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    showActionConfirmation.value = false;
    return;
  }

  router.get(route(`customers.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      selectedAction.value = null;
    },
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Clientes" lede="Contas ligadas a propostas, locais de recolha, amostras, certificados e cobranca.">
      <template #actions>
        <Link v-if="hasPermission('export_customers')" :href="route('exports.index', { dataset: 'customers' })" class="ds-button ds-button-secondary">
          <ArrowDownTrayIcon class="h-4 w-4" />
          Exportar
        </Link>
        <Link v-if="hasPermission('add_customers')" :href="route('customers.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Novo cliente
        </Link>
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
      :create-action="false"
      @execute-action="requestBulkAction"
      @create-record="router.visit(route('customers.create'))"
    >
      <template #actions="{ data }">
        <Link :href="route('customers.show', { customer: data.id })" class="ds-table-action" title="Abrir dossier do cliente">
          <EyeIcon class="h-4 w-4" />
          <span class="sr-only">Abrir cliente</span>
        </Link>
      </template>
    </RecordsTable>

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
