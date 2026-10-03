<script setup>
import { useRecordArchive } from '@/composables/useRecordArchive'
import { useInventoryCatalogueOptions } from '@/Composables/useInventoryCatalogueOptions'
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Combobox from "@/Components/combobox.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ExternalLink as ArrowTopRightOnSquareIcon,
  ArrowUp as ArrowUpIcon,
  Store as BuildingStorefrontIcon,
  Box as CubeIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Eye as EyeIcon,
  MapPin as MapPinIcon,
} from "@lucide/vue";
import { Link, useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, ref, watch } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: true },
  canView: { type: Boolean, default: true },
  openCreate: { type: Boolean, default: false },
  initialRecord: { type: Object, default: null },
});

const { hasPermission } = usePermission();
const hasCatalogueView = computed(() => hasPermission('view_iitems') || hasPermission('view_iequipments'));
const canSelectPosition = computed(() => hasCatalogueView.value && hasPermission('view_iwarehouses'));
const editorOpen = ref(false);
const selectedAction = ref(null);
const bulkRecordIds = ref([]);
const { processing: archiveProcessing, message: archiveMessage, failed: archiveFailed, submit: submitArchive } = useRecordArchive({
  destroyUrl: () => route('inventory.destroy'),
  restoreUrl: () => route('inventory.restore'),
  onSuccess: closeActionConfirmation,
});
const showActionConfirmation = ref(false);
const rows = computed(() => props.record?.data ?? []);
const totalRecords = computed(() => props.record?.meta?.total ?? rows.value.length);
const lowStockCount = computed(() => rows.value.filter((row) => Number(row.qty_available) <= Number(row.reorder_point)).length);
const outOfStockCount = computed(() => rows.value.filter((row) => Number(row.qty_available) <= 0).length);
const tracksThresholds = computed(() => form.item_id?.inventory_type === 'material');
const editorTitle = computed(() => form.id ? "Editar posição de existências" : "Nova posição de existências");
const editorDescription = computed(() => form.id
  ? "Actualize os limites de reposição desta combinação de item e armazém."
  : "Associe um item a um local de armazenamento e defina os limites operacionais.");
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));
const metrics = computed(() => [
  { label: "Posições", value: totalRecords.value, detail: "item por localização", icon: CubeIcon },
  { label: "Em reposição", value: lowStockCount.value, detail: "nesta página", icon: ExclamationTriangleIcon },
  { label: "Sem existências", value: outOfStockCount.value, detail: "acção imediata", icon: BuildingStorefrontIcon },
]);
const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const form = useForm({
  qty_available: 0,
  min_stock_level: 0,
  reorder_point: 0,
  warehouse_id: null,
  item_id: null,
  name: "",
  category_id: null,
  id: null,
});

function openCreate() {
  if (form.processing) return;
  form.clearErrors();
  form.defaults({
    qty_available: 0,
    min_stock_level: 0,
    reorder_point: 0,
    warehouse_id: null,
    item_id: null,
    name: "",
    category_id: null,
    id: null,
  });
  form.reset();
  editorOpen.value = true;
}

function openEdit(data) {
  if (form.processing) return;
  form.clearErrors();
  form.defaults({
    qty_available: Number(data.qty_available ?? 0),
    min_stock_level: Number(data.min_stock_level ?? 0),
    reorder_point: Number(data.reorder_point ?? 0),
    warehouse_id: { value: data.warehouse_id, label: data.warehouse },
    item_id: { value: data.item_id, label: data.item, category_id: data.category_id,
      inventory_type: data.inventory_type ?? null, can_open_item: data.can_open_item === true },
    name: data.item ?? "",
    category_id: data.category_id ?? null,
    id: data.id,
  });
  form.reset();
  editorOpen.value = true;
}

function closeEditor() {
  if (form.processing) return;
  editorOpen.value = false;
  form.clearErrors();
}

function selectItem(option) {
  if (form.id || form.processing) return;
  form.item_id = option;
  form.category_id = option?.category_id ?? null;
  form.name = option?.label ?? "";

  if (option?.inventory_type === 'equipment') {
    form.min_stock_level = 0;
    form.reorder_point = 0;
  }
}

function applyEditorContext() {
  if (props.initialRecord?.data?.id) {
    openEdit(props.initialRecord.data);
  } else if (props.openCreate) {
    openCreate();
  }
}

watch([() => props.openCreate, () => props.initialRecord?.data?.id], applyEditorContext, { immediate: true });

const loadItems = useInventoryCatalogueOptions()

function loadWarehouses(query, setOptions) {
  return fetch(`${route("iwarehouses.getInventoryItemWarehouse")}?q=${encodeURIComponent(query)}`)
    .then((response) => response.ok ? response.json() : [])
    .then((results) => setOptions(results.map((warehouse) => ({
      value: warehouse.id,
      label: warehouse.name,
    }))));
}

function submit() {
  if (form.processing || (!form.id && !canSelectPosition.value)) return;
  form.clearErrors('request');
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      editorOpen.value = false;
      form.clearErrors();
    },
    onHttpException: () => {
      form.setError('request', 'Não foi possível guardar a posição. Verifique as permissões e tente novamente.');
      return false;
    },
    onNetworkError: () => {
      form.setError('request', 'A gravação não foi confirmada. Verifique a ligação antes de tentar novamente.');
      return false;
    },
  };

  if (form.id) {
    form.transform(({ qty_available, ...data }) => data)
      .put(route("inventory.update", { inventory: form.id }), options);
    return;
  }

  form.transform(data => data).post(route("inventory.store"), options);
}

function requestBulkAction(action) {
  if (archiveProcessing.value || form.processing || showActionConfirmation.value || !['delete', 'restore'].includes(action)) return;
  bulkRecordIds.value = rows.value.filter(row => row.selected).map(row => row.id);
  if (!bulkRecordIds.value.length) return;
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  if (archiveProcessing.value || form.processing) return;
  if (!bulkRecordIds.value.length || !['delete', 'restore'].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  submitArchive(selectedAction.value, [...bulkRecordIds.value]);
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <BuildingStorefrontIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Materiais e consumíveis</p>
            <h1 class="ds-heading mt-1 text-2xl">Existências por armazém</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Saldo disponível, níveis mínimos e pontos de reposição por item e localização controlada.</p>
          </div>
        </div>
        <Link v-if="hasPermission('view_itransactions') && hasCatalogueView" :href="route('itransactions.index')" class="ds-button ds-button-secondary">
          <ArrowUpIcon class="h-4 w-4" />
          Ver movimentos
        </Link>
      </div>

      <p v-if="!canView" class="ds-copy mt-4 text-sm">Acesso limitado à operação solicitada. A listagem de existências requer permissão de consulta.</p>
      <button v-if="!canView && !editorOpen" type="button" class="ds-button ds-button-secondary mt-3" @click="applyEditorContext">Reabrir operação</button>
      <dl v-if="canView" class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r sm:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div>
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <p v-if="archiveMessage" :role="archiveFailed ? 'alert' : 'status'" class="text-sm" :class="archiveFailed ? 'text-red-700 dark:text-red-300' : 'text-[var(--ds-text-muted)]'">{{ archiveMessage }}</p>

    <RecordsTable
      v-if="canView"
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      :archive-handler="submitArchive"
      :action-processing="archiveProcessing || form.processing"
      @execute-action="requestBulkAction"
      @create-record="openCreate"
      @slideover-on="openEdit"
    >
      <template #actions="{ id, data }">
        <Link
          v-if="hasPermission('view_inventory')"
          :href="route('inventory.show', { inventory: id })"
          class="ds-icon-button"
          title="Consultar posição"
          aria-label="Consultar posição"
        >
          <EyeIcon class="h-4 w-4" />
        </Link>
        <Link
          v-if="data.can_open_item"
          :href="route('vap-inventory.items.show', { item: data.item_id })"
          class="ds-icon-button"
          title="Abrir fluxo controlado do item"
          aria-label="Abrir fluxo controlado do item"
        >
          <ArrowTopRightOnSquareIcon class="h-4 w-4" />
        </Link>
      </template>
    </RecordsTable>

    <SlideOver v-if="editorOpen" :title="editorTitle" :description="editorDescription" :disabled="form.processing" @close="closeEditor">
      <template #content>
        <form id="inventory-position-form" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
          <section class="space-y-5 px-6 py-6">
            <p v-if="form.errors.request" class="ds-field-error" role="alert">{{ form.errors.request }}</p>
            <p v-if="!form.id && !canSelectPosition" class="ds-copy text-sm" role="status">A selecção exige permissão de consulta de materiais ou equipamentos e de armazéns. Peça esses acessos ao administrador; criar existências não concede essas permissões.</p>
            <div>
              <p class="ds-kicker">Identificação</p>
              <h2 class="ds-heading mt-1 text-base">Item e localização</h2>
            </div>
            <div>
              <template v-if="form.id">
                <label for="stock-position-item" class="ds-field-label mb-2 block">Item de inventário</label>
                <BaseInput id="stock-position-item" :value="form.item_id?.label ?? ''" readonly aria-describedby="stock-position-identity-help" class="ds-field" />
              </template>
              <Combobox
                v-else
                :model-value="form.item_id"
                title-label="Item de inventário"
                placeholder="Pesquisar item"
                :has-error="Boolean(form.errors.item_id)"
                :load-options="loadItems"
                @update:model-value="selectItem"
              />
              <p v-if="form.errors.item_id" class="ds-field-error mt-2">{{ form.errors.item_id }}</p>
            </div>
            <div>
              <template v-if="form.id">
                <label for="stock-position-warehouse" class="ds-field-label mb-2 block">Armazém</label>
                <BaseInput id="stock-position-warehouse" :value="form.warehouse_id?.label ?? ''" readonly aria-describedby="stock-position-identity-help" class="ds-field" />
              </template>
              <Combobox
                v-else
                v-model="form.warehouse_id"
                title-label="Armazém"
                placeholder="Pesquisar localização"
                :has-error="Boolean(form.errors.warehouse_id)"
                :load-options="loadWarehouses"
              />
              <p v-if="form.errors.warehouse_id" class="ds-field-error mt-2">{{ form.errors.warehouse_id }}</p>
            </div>
            <p v-if="form.id" id="stock-position-identity-help" class="ds-copy text-sm">Item e armazém identificam esta posição e não podem ser substituídos. Actualize apenas os limites de reposição.</p>
          </section>

          <section class="space-y-5 px-6 py-6">
            <div>
              <p class="ds-kicker">Controlo de existências</p>
              <h2 class="ds-heading mt-1 text-base">Saldo e reposição</h2>
            </div>
            <div v-if="!form.id">
              <label for="qty_available" class="ds-field-label mb-2 block">Quantidade disponível</label>
              <BaseInput id="qty_available" v-model="form.qty_available" type="number" min="0" step="0.0001" class="ds-field" />
              <p v-if="form.errors.qty_available" class="ds-field-error mt-2">{{ form.errors.qty_available }}</p>
            </div>
            <div v-else class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Quantidade disponível</p>
              <p class="mt-1 text-xl font-bold text-[var(--ds-text)]">{{ form.qty_available }}</p>
              <p class="mt-2 text-sm text-[var(--ds-text-muted)]">Para alterar este saldo, registe um ajuste com motivo no dossier do item.</p>
              <Link v-if="form.item_id?.can_open_item" :href="route('vap-inventory.items.show', { item: form.item_id?.value })" class="ds-button ds-button-secondary mt-3">Ajustar existências</Link>
            </div>
            <div v-if="tracksThresholds" class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="min_stock_level" class="ds-field-label mb-2 block">Nível mínimo</label>
                <BaseInput id="min_stock_level" v-model="form.min_stock_level" type="number" min="0" step="0.0001" class="ds-field" />
                <p v-if="form.errors.min_stock_level" class="ds-field-error mt-2">{{ form.errors.min_stock_level }}</p>
              </div>
              <div>
                <label for="reorder_point" class="ds-field-label mb-2 block">Ponto de reposição</label>
                <BaseInput id="reorder_point" v-model="form.reorder_point" type="number" min="0" step="0.0001" class="ds-field" />
                <p v-if="form.errors.reorder_point" class="ds-field-error mt-2">{{ form.errors.reorder_point }}</p>
              </div>
            </div>
            <div class="flex gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <MapPinIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
              <p class="text-sm font-medium leading-6 text-[var(--ds-text-muted)]">Cada posição representa um único item num único armazém. Os limites alimentam alertas e decisões de reposição.</p>
            </div>
          </section>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" :disabled="form.processing" @click="closeEditor">Cancelar</button>
          <button type="submit" form="inventory-position-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty || (!form.id && !canSelectPosition)">
            {{ form.processing ? "A guardar..." : (form.id ? "Actualizar posição" : "Criar posição") }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      :disabled="archiveProcessing || form.processing"
      confirm="Sim"
      cancel="Não"
      @canceled="closeActionConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
