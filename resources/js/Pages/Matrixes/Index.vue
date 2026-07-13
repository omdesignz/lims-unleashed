<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArchiveBoxIcon,
  BanknotesIcon,
  EyeIcon,
  PlusIcon,
  RectangleGroupIcon,
  ReceiptPercentIcon,
} from "@heroicons/vue/24/outline";
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
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const activeRecords = computed(() => pageRecords.value.filter((record) => !record.deleted).length);
const taxableRecords = computed(() => pageRecords.value.filter((record) => record.charge_tax).length);
const pageValue = computed(() => pageRecords.value.reduce((total, record) => total + Number(record.fixed_price || record.price || 0), 0));

const metrics = computed(() => [
  { label: "Matrizes", value: totalRecords.value, detail: "catalogo total", icon: RectangleGroupIcon },
  { label: "Ativas nesta pagina", value: activeRecords.value, detail: "disponiveis para produtos", icon: ArchiveBoxIcon },
  { label: "Com imposto", value: taxableRecords.value, detail: "regra fiscal configurada", icon: ReceiptPercentIcon },
  { label: "Valor fixo", value: formatCurrency(pageValue.value), detail: "total desta pagina", icon: BanknotesIcon },
]);

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
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Catalogo de servicos</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <RectangleGroupIcon class="h-5 w-5" />
            </span>
            <div>
              <h1 class="ds-heading text-2xl">Matrizes</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Escopos comerciais que combinam perfis analiticos compativeis por departamento, preco e regra fiscal.</p>
            </div>
          </div>
        </div>
        <Link v-if="hasPermission('add_matrixes')" :href="route('matrixes.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Nova matriz
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 break-words text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

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
      cancel="Nao"
      @canceled="showActionConfirmation = false"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
