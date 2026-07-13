<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  BeakerIcon,
  BuildingOffice2Icon,
  ClipboardDocumentCheckIcon,
  CurrencyDollarIcon,
  EyeIcon,
  PlusIcon,
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
const categories = computed(() => new Set(pageRecords.value.map((record) => record.category).filter(Boolean)).size);
const pageValue = computed(() => pageRecords.value.reduce((total, record) => total + Number(record.price || 0), 0));

const metrics = computed(() => [
  { label: "Perfis", value: totalRecords.value, detail: "catalogo total", icon: ClipboardDocumentCheckIcon },
  { label: "Ativos nesta pagina", value: activeRecords.value, detail: "disponiveis para amostras", icon: BeakerIcon },
  { label: "Categorias", value: categories.value, detail: "escopos analiticos", icon: BuildingOffice2Icon },
  { label: "Valor nesta pagina", value: `${pageValue.value.toLocaleString('pt-PT')} AOA`, detail: "composicao dos ensaios", icon: CurrencyDollarIcon },
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

  router.get(route(`profiles.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      selectedAction.value = null;
    },
  });
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
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">{{ $t('gestlab.general.labels.profiles.page_title') }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Pacotes analiticos controlados por categoria, departamento, metodos, faixas de referencia e preco composto.</p>
            </div>
          </div>
        </div>

        <Link v-if="hasPermission('add_profiles')" :href="route('profiles.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Novo perfil
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 break-words text-xl font-black text-[var(--ds-text)]">{{ metric.value }}</dd>
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
      @create-record="router.visit(route('profiles.create'))"
    >
      <template #actions="{ data }">
        <Link :href="route('profiles.show', { profile: data.id })" class="ds-table-action" title="Ver perfil">
          <EyeIcon class="h-4 w-4" />
          <span class="sr-only">Ver perfil</span>
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
