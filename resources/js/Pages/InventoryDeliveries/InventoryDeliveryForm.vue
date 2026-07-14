<script setup>
import Combobox from "@/Components/combobox.vue";
import {
  ArrowLeftIcon,
  CalendarDaysIcon,
  CheckIcon,
  CubeIcon,
  PlusIcon,
  TrashIcon,
  TruckIcon,
  UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
  form: { type: Object, required: true },
  mode: { type: String, default: "create" },
});

const emit = defineEmits(["submit"]);
let lineSequence = 0;

const isEditing = computed(() => props.mode === "edit");
const pageTitle = computed(() => isEditing.value ? "Editar entrega" : "Nova entrega");
const pageDescription = computed(() => isEditing.value
  ? "Revise o destinatário e as linhas expedidas mantendo a rastreabilidade da saída."
  : "Registe uma saída de materiais com destino, quantidade, armazém e datas operacionais.");
const totalQuantity = computed(() => props.form.items.reduce((total, item) => total + (Number(item.qty) || 0), 0));

function createLine() {
  lineSequence += 1;

  return {
    client_key: `delivery-line-${Date.now()}-${lineSequence}`,
    item_id: null,
    qty: 1,
    expected_date: "",
    actual_date: "",
    warehouse_id: null,
  };
}

function addItem() {
  props.form.items.push(createLine());
}

function removeItem(index) {
  props.form.items.splice(index, 1);
}

async function loadCustomers(query, setOptions) {
  const response = await fetch(`/customers/getCustomer?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((customer) => ({ value: customer.id, label: customer.name })));
}

async function loadWarehouses(query, setOptions) {
  const response = await fetch(`/iwarehouses/getInventoryItemWarehouse?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((warehouse) => ({ value: warehouse.id, label: warehouse.name })));
}

async function loadItems(query, setOptions) {
  const response = await fetch(`/iitems/getInventoryItem?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((item) => ({
    value: item.id,
    label: item.code ? `${item.code} · ${item.name}` : item.name,
  })));
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="emit('submit')">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <TruckIcon class="h-5 w-5" aria-hidden="true" />
          </span>
          <div>
            <p class="ds-kicker">Expedição controlada</p>
            <h1 class="ds-heading mt-1 text-2xl">{{ pageTitle }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">{{ pageDescription }}</p>
          </div>
        </div>

        <Link :href="route('ideliveries.index')" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
          Voltar ao registo
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><CalendarDaysIcon class="h-4 w-4" aria-hidden="true" /> Data</dt>
          <dd class="mt-2 text-sm font-semibold text-[var(--ds-text)]">{{ form.sales_date || "Por definir" }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><CubeIcon class="h-4 w-4" aria-hidden="true" /> Linhas</dt>
          <dd class="mt-2 text-sm font-semibold text-[var(--ds-text)]">{{ form.items.length }} materiais</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><TruckIcon class="h-4 w-4" aria-hidden="true" /> Quantidade</dt>
          <dd class="mt-2 text-sm font-semibold text-[var(--ds-text)]">{{ totalQuantity }} unidades</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <p class="ds-kicker">Cabeçalho da entrega</p>
        <h2 class="ds-heading mt-1 text-base">Destinatário e data de saída</h2>
      </div>

      <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
        <div>
          <label for="delivery-sales-date" class="ds-field-label mb-2 block">Data de entrega</label>
          <DateTimePicker id="delivery-sales-date" v-model="form.sales_date" type="date" class="ds-field" />
          <p v-if="form.errors.sales_date" class="ds-field-error mt-2">{{ form.errors.sales_date }}</p>
        </div>

        <div>
          <Combobox
            v-model="form.customer_id"
            title-label="Cliente destinatário"
            placeholder="Pesquisar cliente"
            :has-error="Boolean(form.errors.customer_id)"
            :load-options="loadCustomers"
          />
          <p v-if="form.errors.customer_id" class="ds-field-error mt-2">{{ form.errors.customer_id }}</p>
        </div>
      </div>
    </section>

    <section class="ds-table-shell overflow-hidden">
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">Materiais expedidos</p>
          <h2 class="ds-heading mt-1 text-base">Linhas da entrega</h2>
        </div>
        <button type="button" class="ds-button ds-button-secondary" @click="addItem">
          <PlusIcon class="h-4 w-4" aria-hidden="true" />
          Adicionar linha
        </button>
      </div>

      <div v-if="form.items.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="(item, index) in form.items" :key="item.client_key ?? index" class="px-5 py-5 sm:px-6">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
              <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-xs font-bold text-[var(--ds-text-muted)]">{{ index + 1 }}</span>
              <div>
                <h3 class="text-sm font-semibold text-[var(--ds-text)]">Linha de material</h3>
                <p class="mt-0.5 text-xs text-[var(--ds-text-muted)]">Item, origem e calendário de entrega</p>
              </div>
            </div>
            <button type="button" class="ds-icon-button text-red-600" title="Remover linha" @click="removeItem(index)">
              <span class="sr-only">Remover linha {{ index + 1 }}</span>
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>

          <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4">
              <Combobox
                v-model="item.item_id"
                title-label="Item de inventário"
                placeholder="Pesquisar código ou designação"
                :has-error="Boolean(form.errors[`items.${index}.item_id`])"
                :load-options="loadItems"
              />
              <p v-if="form.errors[`items.${index}.item_id`]" class="ds-field-error mt-2">{{ form.errors[`items.${index}.item_id`] }}</p>
            </div>

            <div class="lg:col-span-2">
              <label :for="`delivery-quantity-${index}`" class="ds-field-label mb-2 block">Quantidade</label>
              <BaseInput :id="`delivery-quantity-${index}`" v-model.number="item.qty" type="number" min="1" step="1" class="ds-field" />
              <p v-if="form.errors[`items.${index}.qty`]" class="ds-field-error mt-2">{{ form.errors[`items.${index}.qty`] }}</p>
            </div>

            <div class="lg:col-span-3">
              <Combobox
                v-model="item.warehouse_id"
                title-label="Armazém de origem"
                placeholder="Pesquisar armazém"
                :has-error="Boolean(form.errors[`items.${index}.warehouse_id`])"
                :load-options="loadWarehouses"
              />
              <p v-if="form.errors[`items.${index}.warehouse_id`]" class="ds-field-error mt-2">{{ form.errors[`items.${index}.warehouse_id`] }}</p>
            </div>

            <div class="lg:col-span-3">
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div>
                  <label :for="`delivery-expected-date-${index}`" class="ds-field-label mb-2 block">Data prevista</label>
                  <DateTimePicker :id="`delivery-expected-date-${index}`" v-model="item.expected_date" type="date" class="ds-field" />
                  <p v-if="form.errors[`items.${index}.expected_date`]" class="ds-field-error mt-2">{{ form.errors[`items.${index}.expected_date`] }}</p>
                </div>
                <div>
                  <label :for="`delivery-actual-date-${index}`" class="ds-field-label mb-2 block">Data efectiva</label>
                  <DateTimePicker :id="`delivery-actual-date-${index}`" v-model="item.actual_date" type="date" class="ds-field" />
                  <p v-if="form.errors[`items.${index}.actual_date`]" class="ds-field-error mt-2">{{ form.errors[`items.${index}.actual_date`] }}</p>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="m-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-10 text-center sm:m-6">
        <CubeIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" aria-hidden="true" />
        <p class="mt-3 text-sm font-semibold text-[var(--ds-text)]">Nenhuma linha adicionada</p>
        <button type="button" class="ds-button ds-button-secondary mt-4" @click="addItem">
          <PlusIcon class="h-4 w-4" aria-hidden="true" />
          Adicionar material
        </button>
      </div>

      <p v-if="form.errors.items" class="ds-field-error border-t border-[var(--ds-border)] px-5 py-3 sm:px-6">{{ form.errors.items }}</p>
    </section>

    <section class="ds-panel flex flex-col-reverse gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <p class="flex items-center gap-2 text-xs font-semibold text-[var(--ds-text-muted)]">
        <UserGroupIcon class="h-4 w-4" aria-hidden="true" />
        {{ form.customer_id?.label || "Destinatário por seleccionar" }}
      </p>
      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Link :href="route('ideliveries.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          <CheckIcon class="h-4 w-4" aria-hidden="true" />
          {{ form.processing ? "A guardar..." : (isEditing ? "Actualizar entrega" : "Registar entrega") }}
        </button>
      </div>
    </section>
  </form>
</template>
