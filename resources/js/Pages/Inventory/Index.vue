<script setup>
import { useRecordArchive } from '@/Composables/useRecordArchive'
import { useInventoryCatalogueOptions } from '@/Composables/useInventoryCatalogueOptions'
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Combobox from "@/Components/combobox.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import {
  ExternalLink as ArrowTopRightOnSquareIcon,
  ArrowUp as ArrowUpIcon,
  Eye as EyeIcon,
} from "@lucide/vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

defineOptions({ layout: Layout });

/**
 * Stock positions register (Plano queue): one row per item and warehouse. Balances
 * change only through movements recorded on the item dossier; this register sets the
 * replenishment limits and archives positions.
 */

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
const confirmationDialogTitle = computed(() => (selectedAction.value === "restore" ? "Restaurar posições?" : "Arquivar posições?"));
const confirmationDialogDescription = computed(() => (selectedAction.value === "restore"
  ? "As posições seleccionadas voltam ao registo activo com os saldos e limites preservados."
  : "As posições seleccionadas saem do registo activo até serem restauradas. Saldos e movimentos ficam preservados."));
const lede = computed(() => {
  if (!props.canView) {
    return "Acesso limitado à operação solicitada. A listagem de existências requer permissão de consulta.";
  }

  const positions = `${totalRecords.value} ${totalRecords.value === 1 ? "posição" : "posições"} de item por armazém.`;
  if (!lowStockCount.value && !outOfStockCount.value) {
    return `${positions} Nesta página nenhuma está no ponto de reposição.`;
  }

  return `${positions} Nesta página: ${lowStockCount.value} no ponto de reposição ou abaixo, ${outOfStockCount.value} sem existências.`;
});
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
  <div class="pl-page" data-template="queue">
    <PageHeader :crumbs="[{ title: 'Inventário' }, { title: 'Existências por armazém' }]" title="Existências por armazém" :lede="lede">
      <template #actions>
        <button v-if="!canView && !editorOpen" type="button" class="ds-button ds-button-secondary" @click="applyEditorContext">Reabrir operação</button>
        <Link v-if="hasPermission('view_itransactions') && hasCatalogueView" :href="route('itransactions.index')" class="ds-button ds-button-quiet">
          <ArrowUpIcon class="h-4 w-4" aria-hidden="true" />
          Ver movimentos
        </Link>
      </template>
    </PageHeader>

    <p v-if="archiveMessage" :role="archiveFailed ? 'alert' : 'status'" class="mb-4 text-sm" :class="archiveFailed ? 'text-[var(--pl-bad)]' : 'text-[var(--pl-muted)]'">{{ archiveMessage }}</p>

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
        <form id="inventory-position-form" class="divide-y divide-[var(--pl-line)]" @submit.prevent="submit">
          <section class="space-y-5 px-6 py-6">
            <p v-if="form.errors.request" class="ds-field-error" role="alert">{{ form.errors.request }}</p>
            <p v-if="!form.id && !canSelectPosition" class="text-sm text-[var(--pl-muted)]" role="status">A selecção exige permissão de consulta de materiais ou equipamentos e de armazéns. Peça esses acessos ao administrador; criar existências não concede essas permissões.</p>
            <h2 class="pl-d3">Item e localização</h2>
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
              <p v-if="form.errors.item_id" class="ds-field-error mt-2" role="alert">{{ form.errors.item_id }}</p>
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
              <p v-if="form.errors.warehouse_id" class="ds-field-error mt-2" role="alert">{{ form.errors.warehouse_id }}</p>
            </div>
            <p v-if="form.id" id="stock-position-identity-help" class="text-sm text-[var(--pl-muted)]">Item e armazém identificam esta posição e não podem ser substituídos. Actualize apenas os limites de reposição.</p>
          </section>

          <section class="space-y-5 px-6 py-6">
            <h2 class="pl-d3">Saldo e reposição</h2>
            <div v-if="!form.id">
              <label for="qty_available" class="ds-field-label mb-2 block">Quantidade disponível</label>
              <BaseInput id="qty_available" v-model="form.qty_available" type="number" min="0" step="0.0001" class="ds-field" />
              <p v-if="form.errors.qty_available" class="ds-field-error mt-2" role="alert">{{ form.errors.qty_available }}</p>
            </div>
            <div v-else class="border border-[var(--pl-line)] p-4">
              <p class="pl-k pl-muted">Quantidade disponível</p>
              <p class="pl-num mt-2 text-xl font-semibold">{{ form.qty_available }}</p>
              <p class="mt-2 text-sm text-[var(--pl-muted)]">Para alterar este saldo, registe um ajuste com motivo no dossier do item.</p>
              <Link v-if="form.item_id?.can_open_item" :href="route('vap-inventory.items.show', { item: form.item_id?.value })" class="ds-button ds-button-secondary mt-3">Ajustar existências</Link>
            </div>
            <div v-if="tracksThresholds" class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="min_stock_level" class="ds-field-label mb-2 block">Nível mínimo</label>
                <BaseInput id="min_stock_level" v-model="form.min_stock_level" type="number" min="0" step="0.0001" class="ds-field" />
                <p v-if="form.errors.min_stock_level" class="ds-field-error mt-2" role="alert">{{ form.errors.min_stock_level }}</p>
              </div>
              <div>
                <label for="reorder_point" class="ds-field-label mb-2 block">Ponto de reposição</label>
                <BaseInput id="reorder_point" v-model="form.reorder_point" type="number" min="0" step="0.0001" class="ds-field" />
                <p v-if="form.errors.reorder_point" class="ds-field-error mt-2" role="alert">{{ form.errors.reorder_point }}</p>
              </div>
            </div>
            <p class="text-sm leading-6 text-[var(--pl-muted)]">Cada posição representa um único item num único armazém. Os limites alimentam alertas e decisões de reposição.</p>
          </section>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" :disabled="form.processing" @click="closeEditor">Cancelar</button>
          <button type="submit" form="inventory-position-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty || (!form.id && !canSelectPosition)">
            {{ form.processing ? "A guardar…" : (form.id ? "Actualizar posição" : "Criar posição") }}
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
      :confirm="selectedAction === 'restore' ? 'Restaurar' : 'Arquivar'"
      cancel="Manter"
      @canceled="closeActionConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
