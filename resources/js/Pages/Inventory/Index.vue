<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import Combobox from "@/Components/combobox.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowTopRightOnSquareIcon,
  BuildingStorefrontIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  MapPinIcon,
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
  slideOverEdit: { type: Boolean, default: true },
});

const { hasPermission } = usePermission();
const editorOpen = ref(false);
const selectedAction = ref(null);
const showActionConfirmation = ref(false);
const rows = computed(() => props.record?.data ?? []);
const totalRecords = computed(() => props.record?.meta?.total ?? rows.value.length);
const lowStockCount = computed(() => rows.value.filter((row) => Number(row.qty_available) <= Number(row.reorder_point)).length);
const outOfStockCount = computed(() => rows.value.filter((row) => Number(row.qty_available) <= 0).length);
const tracksThresholds = computed(() => Number(form.category_id) !== 1);
const editorTitle = computed(() => form.id ? "Editar posição de stock" : "Nova posição de stock");
const editorDescription = computed(() => form.id
  ? "Atualize os limites de reposição desta combinação de item e armazém."
  : "Associe um item a um local de armazenamento e defina os limites operacionais.");
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));
const metrics = computed(() => [
  { label: "Posições", value: totalRecords.value, detail: "item por localização", icon: CubeIcon },
  { label: "Em reposição", value: lowStockCount.value, detail: "nesta página", icon: ExclamationTriangleIcon },
  { label: "Sem stock", value: outOfStockCount.value, detail: "ação imediata", icon: BuildingStorefrontIcon },
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
  form.reset();
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
  editorOpen.value = true;
}

function openEdit(data) {
  form.clearErrors();
  form.defaults({
    qty_available: Number(data.qty_available ?? 0),
    min_stock_level: Number(data.min_stock_level ?? 0),
    reorder_point: Number(data.reorder_point ?? 0),
    warehouse_id: { value: data.warehouse_id, label: data.warehouse },
    item_id: { value: data.item_id, label: data.item, category_id: data.category_id },
    name: data.item ?? "",
    category_id: data.category_id ?? null,
    id: data.id,
  });
  form.reset();
  editorOpen.value = true;
}

function closeEditor() {
  editorOpen.value = false;
  form.clearErrors();
}

function selectItem(option) {
  form.item_id = option;
  form.category_id = option?.category_id ?? null;
  form.name = option?.label ?? "";

  if (Number(form.category_id) === 1) {
    form.min_stock_level = 0;
    form.reorder_point = 0;
  }
}

function loadItems(query, setOptions) {
  return fetch(`${route("iitems.getInventoryItem")}?q=${encodeURIComponent(query)}`)
    .then((response) => response.ok ? response.json() : [])
    .then((results) => setOptions(results.map((item) => ({
      value: item.id,
      label: item.name,
      category_id: item.category_id,
    }))));
}

function loadWarehouses(query, setOptions) {
  return fetch(`${route("iwarehouses.getInventoryItemWarehouse")}?q=${encodeURIComponent(query)}`)
    .then((response) => response.ok ? response.json() : [])
    .then((results) => setOptions(results.map((warehouse) => ({
      value: warehouse.id,
      label: warehouse.name,
    }))));
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: closeEditor,
  };

  if (form.id) {
    form.put(route("inventory.update", { inventory: form.id }), options);
    return;
  }

  form.post(route("inventory.store"), options);
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  const recordIds = rows.value.filter((row) => row.selected).map((row) => row.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.get(route(`inventory.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: closeActionConfirmation,
  });
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
            <h1 class="ds-heading mt-1 text-2xl">Stock por armazém</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Saldo disponível, níveis mínimos e pontos de reposição por item e localização controlada.</p>
          </div>
        </div>
        <Link :href="route('itransactions.index')" class="ds-button ds-button-secondary">
          <ArrowUpIcon class="h-4 w-4" />
          Ver movimentos
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
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

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
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
          v-if="hasPermission('view_iitems')"
          :href="route('vap-inventory.items.show', { item: data.item_id })"
          class="ds-icon-button"
          title="Abrir fluxo controlado do item"
          aria-label="Abrir fluxo controlado do item"
        >
          <ArrowTopRightOnSquareIcon class="h-4 w-4" />
        </Link>
      </template>
    </RecordsTable>

    <SlideOver v-if="editorOpen" :title="editorTitle" :description="editorDescription" @close="closeEditor">
      <template #content>
        <form id="inventory-position-form" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
          <section class="space-y-5 px-6 py-6">
            <div>
              <p class="ds-kicker">Identificação</p>
              <h2 class="ds-heading mt-1 text-base">Item e localização</h2>
            </div>
            <div>
              <Combobox
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
              <Combobox
                v-model="form.warehouse_id"
                title-label="Armazém"
                placeholder="Pesquisar localização"
                :has-error="Boolean(form.errors.warehouse_id)"
                :load-options="loadWarehouses"
              />
              <p v-if="form.errors.warehouse_id" class="ds-field-error mt-2">{{ form.errors.warehouse_id }}</p>
            </div>
          </section>

          <section class="space-y-5 px-6 py-6">
            <div>
              <p class="ds-kicker">Controlo de stock</p>
              <h2 class="ds-heading mt-1 text-base">Saldo e reposição</h2>
            </div>
            <div>
              <label for="qty_available" class="ds-field-label mb-2 block">Quantidade disponível</label>
              <input id="qty_available" v-model.number="form.qty_available" type="number" min="0" class="ds-field">
              <p v-if="form.errors.qty_available" class="ds-field-error mt-2">{{ form.errors.qty_available }}</p>
            </div>
            <div v-if="tracksThresholds" class="grid gap-4 sm:grid-cols-2">
              <div>
                <label for="min_stock_level" class="ds-field-label mb-2 block">Nível mínimo</label>
                <input id="min_stock_level" v-model.number="form.min_stock_level" type="number" min="0" class="ds-field">
                <p v-if="form.errors.min_stock_level" class="ds-field-error mt-2">{{ form.errors.min_stock_level }}</p>
              </div>
              <div>
                <label for="reorder_point" class="ds-field-label mb-2 block">Ponto de reposição</label>
                <input id="reorder_point" v-model.number="form.reorder_point" type="number" min="0" class="ds-field">
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
          <button type="button" class="ds-button ds-button-secondary" @click="closeEditor">Cancelar</button>
          <button type="submit" form="inventory-position-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : (form.id ? "Atualizar posição" : "Criar posição") }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="closeActionConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
