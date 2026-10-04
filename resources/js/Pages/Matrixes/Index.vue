<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Eye as EyeIcon,
  Plus as PlusIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";
import { trans } from "laravel-vue-i18n";

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
  const recordIds = pageRecords.value.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    showActionConfirmation.value = false;
    return;
  }

  router.get(route(`matrixes.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      selectedAction.value = null;
    },
  });
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", { style: "currency", currency: "AOA" }).format(Number(value || 0));
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Matrizes" lede="Âmbitos comerciais que combinam perfis analíticos compativeis por departamento, preço e regra fiscal.">
      <template #actions>
        <Link v-if="hasPermission('add_matrixes')" :href="route('matrixes.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Nova matriz
        </Link>
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
      @create-record="router.visit(route('matrixes.create'))"
    >
      <template #actions="{ data }">
        <Link :href="route('matrixes.show', { matrix: data.id })" class="ds-table-action" title="Ver matriz">
          <EyeIcon class="h-4 w-4" />
          <span class="sr-only">Ver matriz</span>
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
