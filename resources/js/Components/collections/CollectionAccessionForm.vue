<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ComboboxMultipleEnhanced from "@/Components/combobox-multiple-enhanced.vue";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  BeakerIcon,
  CalendarDaysIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  CubeIcon,
  MapPinIcon,
  PlusIcon,
  ShieldCheckIcon,
  TrashIcon,
  TruckIcon,
  UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { computed, ref, watch } from "vue";

const props = defineProps({
  kind: { type: String, required: true, validator: (value) => ["direct", "programmed"].includes(value) },
  record: { type: Object, default: null },
  entrypoint: { type: Object, default: () => ({}) },
});

const source = props.record || {};
const isScheduled = computed(() => props.kind === "programmed");
const isEditing = computed(() => Boolean(source.id));
const config = computed(() => isScheduled.value
  ? {
      title: "Colheita programada",
      kicker: "Planeamento e cadeia de custódia",
      routePrefix: "programmedcollections",
      icon: CalendarDaysIcon,
    }
  : {
      title: "Colheita directa",
      kicker: "Recepção e cadeia de custódia",
      routePrefix: "directcollections",
      icon: ClipboardDocumentCheckIcon,
    });

function emptyProduct() {
  return {
    product_id: null,
    temperature_id: null,
    collection_id: null,
    pack_id: null,
    result_id: null,
    owner_id: null,
    vehicle_id: null,
    invoice_id: null,
    comercial_brand: "",
    du_no: "",
    temperature_value: "",
    term_no: "",
    container_no: "",
    recollection: false,
    obs: "",
    sample_status: "",
    sampling_plan_ref: "",
    customer_submitted_info: "",
    processed: false,
    collected_by_lab: false,
    expiry_date: "",
    production_date: "",
    collection_date: "",
    qty: "",
    origin: "",
    location: "",
    collected_qty: "",
    lot: "",
    bl: "",
    invoiced: false,
    status: false,
  };
}

const form = useForm(isEditing.value
  ? {
      ...emptyProduct(),
      ...source,
      collaborations: source.collaborations || [],
      collectionreasons: source.collectionreasons || [],
      collection_location: source.collection_location || "",
      vehicle_reference: source.vehicle_reference || "",
    }
  : {
      customer_id: null,
      warehouse_id: null,
      vehicle_id: null,
      vehicle_reference: "",
      collaborations: [],
      collectionreasons: [],
      collection_date: "",
      collection_location: "",
      products: [emptyProduct()],
    });

const loadingWarehouses = ref(false);
const specimens = computed(() => isEditing.value ? [form] : form.products);
const selectedCustomer = computed(() => form.customer_id?.label || "Cliente não seleccionado");
const totalRequestedQuantity = computed(() => specimens.value.reduce((total, product) => total + (Number.parseFloat(product.qty) || 0), 0));
const entrypointUrl = computed(() => props.entrypoint?.create_sample_url
  || route("vap_samples.index", { collection_type: props.kind }));

watch(() => form.customer_id?.value, (customerId, previousCustomerId) => {
  if (!customerId || customerId === previousCustomerId) {
    return;
  }

  loadingWarehouses.value = true;
  loadSelectOptions(
    "/warehouses/getWarehouse",
    "",
    (warehouses) => {
      form.warehouse_id = warehouses[0] || null;
      loadingWarehouses.value = false;
    },
    optionMappers.address,
    { customer_id: customerId },
  );
});

watch(() => form.collection_date, (collectionDate) => {
  if (!isEditing.value) {
    form.products.forEach((product) => {
      product.collection_date = collectionDate;
    });
  }
});

function loadCustomers(query, setOptions) {
  return loadSelectOptions("/customers/getCustomer", query, setOptions, optionMappers.name);
}

function loadWarehouses(query, setOptions) {
  return loadSelectOptions("/warehouses/getWarehouse", query, setOptions, optionMappers.address, {
    customer_id: form.customer_id?.value,
  });
}

function loadProducts(query, setOptions) {
  return loadSelectOptions("/products/getProduct", query, setOptions, optionMappers.name);
}

function loadVehicles(query, setOptions) {
  return loadSelectOptions("/vehicles/getVehicle", query, setOptions, optionMappers.numberPlate);
}

function loadPackagingCategories(query, setOptions) {
  return loadSelectOptions("/packagingcategories/getPackagingCategory", query, setOptions, optionMappers.name);
}

function loadEndResults(query, setOptions) {
  return loadSelectOptions("/collectionendresults/getCollectionEndResult", query, setOptions, optionMappers.name);
}

function loadCollectionCollaborations(query, setOptions) {
  return loadSelectOptions("/collectioncollaborations/getCollectionCollaboration", query, setOptions, optionMappers.name);
}

function loadCollectionReasons(query, setOptions) {
  return loadSelectOptions("/collectionreasons/getCollectionReason", query, setOptions, optionMappers.name);
}

function loadTemperatures(query, setOptions) {
  return loadSelectOptions("/temperatures/getTemperature", query, setOptions, optionMappers.name);
}

function loadUsers(query, setOptions) {
  return loadSelectOptions("/users/getUser", query, setOptions, optionMappers.name);
}

function addProduct() {
  if (!isEditing.value && form.products.length < 5) {
    form.products.push({ ...emptyProduct(), collection_date: form.collection_date });
  }
}

function removeProduct(index) {
  if (!isEditing.value && form.products.length > 1) {
    form.products.splice(index, 1);
  }
}

function fieldError(index, field) {
  return isEditing.value ? form.errors[field] : form.errors[`products.${index}.${field}`];
}

function productTitle(product, index) {
  return product.product_id?.label || `Amostra ${index + 1}`;
}

function formatNumber(value) {
  return new Intl.NumberFormat("pt-PT", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.parseFloat(value) || 0);
}

function submit() {
  const options = {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      if (!isEditing.value) {
        form.reset();
      }
    },
  };

  if (isEditing.value) {
    form.put(route(`${config.value.routePrefix}.update`, { collection: form.id }), options);
    return;
  }

  form.post(route(`${config.value.routePrefix}.store`), options);
}
</script>

<template>
  <form class="min-w-0 space-y-6 overflow-x-clip" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-ghost px-0">
        <ArrowLeftIcon class="h-4 w-4" />
        Voltar à fila
      </Link>

      <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <component :is="config.icon" class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">{{ config.kicker }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <h1 class="ds-heading text-2xl">{{ isEditing ? `Editar ${config.title.toLowerCase()}` : `Nova ${config.title.toLowerCase()}` }}</h1>
              <span v-if="source.code" class="ds-badge ds-badge-info font-mono">{{ source.code }}</span>
            </div>
            <p class="ds-copy mt-1 max-w-3xl text-sm">{{ selectedCustomer }}</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link v-if="!isEditing" :href="entrypointUrl" class="ds-button ds-button-secondary">
            <BeakerIcon class="h-4 w-4" />
            Abrir entrada de amostra
          </Link>
          <span class="ds-badge" :class="form.isDirty ? 'ds-badge-warning' : 'ds-badge-neutral'">
            {{ form.isDirty ? "Alterações por guardar" : "Sem alterações" }}
          </span>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Modo</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ isEditing ? "Revisão de amostra" : "Entrada em lote" }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Amostras</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ specimens.length }} registos</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Quantidade solicitada</dt>
          <dd class="mt-2 text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ formatNumber(totalRequestedQuantity) }}</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Contexto da colheita</h2>
            <p class="ds-copy mt-1 text-xs">Cliente, instalação, data e equipa responsável.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-4">
        <div class="ds-field-group">
          <label for="collection-date" class="ds-field-label">Data da colheita</label>
          <DateTimePicker id="collection-date" v-model="form.collection_date" type="date" class="ds-field" :aria-invalid="Boolean(form.errors.collection_date)" />
          <p v-if="form.errors.collection_date" class="ds-field-error">{{ form.errors.collection_date }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label">Cliente <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.customer_id" :has-error="form.errors.customer_id" :load-options="loadCustomers" placeholder="Seleccionar cliente" />
          <p v-if="form.errors.customer_id" class="ds-field-error">{{ form.errors.customer_id }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label">Instalação <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.warehouse_id" :disable-input="!form.customer_id || loadingWarehouses" :loading="loadingWarehouses" :has-error="form.errors.warehouse_id" :load-options="loadWarehouses" placeholder="Seleccionar instalação" />
          <p v-if="form.errors.warehouse_id" class="ds-field-error">{{ form.errors.warehouse_id }}</p>
        </div>
        <div v-if="isScheduled" class="ds-field-group">
          <label for="collection-location" class="ds-field-label">Local da colheita</label>
          <BaseInput id="collection-location" v-model="form.collection_location" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.collection_location)" />
          <p v-if="form.errors.collection_location" class="ds-field-error">{{ form.errors.collection_location }}</p>
        </div>
        <div v-if="isScheduled && !isEditing" class="ds-field-group">
          <label class="ds-field-label">Viatura planeada</label>
          <ComboboxEnhanced v-model="form.vehicle_id" :has-error="form.errors.vehicle_id" :load-options="loadVehicles" placeholder="Seleccionar viatura" />
          <p v-if="form.errors.vehicle_id" class="ds-field-error">{{ form.errors.vehicle_id }}</p>
        </div>
        <div v-if="isScheduled && !isEditing" class="ds-field-group">
          <label for="vehicle-reference" class="ds-field-label">Referência da viatura</label>
          <BaseInput id="vehicle-reference" v-model="form.vehicle_reference" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.vehicle_reference)" />
          <p v-if="form.errors.vehicle_reference" class="ds-field-error">{{ form.errors.vehicle_reference }}</p>
        </div>
        <div class="ds-field-group sm:col-span-2">
          <label class="ds-field-label">Colaboradores</label>
          <ComboboxMultipleEnhanced v-model="form.collaborations" :load-options="loadCollectionCollaborations" multiple />
          <p v-if="form.errors.collaborations" class="ds-field-error">{{ form.errors.collaborations }}</p>
        </div>
        <div class="ds-field-group sm:col-span-2">
          <label class="ds-field-label">Motivos da colheita</label>
          <ComboboxMultipleEnhanced v-model="form.collectionreasons" :load-options="loadCollectionReasons" multiple />
          <p v-if="form.errors.collectionreasons" class="ds-field-error">{{ form.errors.collectionreasons }}</p>
        </div>
      </div>
    </section>

    <section class="ds-panel min-w-0 overflow-hidden">
      <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
          <CubeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Amostras e cadeia de custódia</h2>
            <p class="ds-copy mt-1 text-xs">Identificação, conservação, transporte e rastreabilidade.</p>
          </div>
        </div>
        <button v-if="!isEditing" type="button" class="ds-button ds-button-secondary" :disabled="form.products.length >= 5" @click="addProduct">
          <PlusIcon class="h-4 w-4" />
          Adicionar amostra
        </button>
      </header>

      <p v-if="form.errors.products" class="ds-field-error px-5 pt-4 sm:px-6">{{ form.errors.products }}</p>

      <div class="divide-y divide-[var(--ds-border)]">
        <details v-for="(product, index) in specimens" :key="product.id || index" class="group" :open="index === 0">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 bg-[var(--ds-panel-raised)] px-5 py-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]">
                <BeakerIcon class="h-4 w-4" />
              </span>
              <div class="min-w-0">
                <h3 class="truncate text-sm font-bold text-[var(--ds-text)]">{{ productTitle(product, index) }}</h3>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ product.lot ? `Lote ${product.lot}` : "Lote não registado" }} · {{ formatNumber(product.qty) }} un.</p>
              </div>
            </div>
            <button v-if="!isEditing" type="button" class="ds-icon-button shrink-0 text-rose-600" title="Remover amostra" :disabled="form.products.length === 1" @click.prevent="removeProduct(index)">
              <TrashIcon class="h-4 w-4" />
            </button>
          </summary>

          <div class="space-y-6 border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
            <div>
              <div class="flex items-center gap-2">
                <BeakerIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Identificação da amostra</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="ds-field-group sm:col-span-2">
                  <label class="ds-field-label">Produto <span class="ds-field-required">*</span></label>
                  <ComboboxEnhanced v-model="product.product_id" :has-error="fieldError(index, 'product_id')" :load-options="loadProducts" placeholder="Seleccionar produto" />
                  <p v-if="fieldError(index, 'product_id')" class="ds-field-error">{{ fieldError(index, "product_id") }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Resultado final <span class="ds-field-required">*</span></label>
                  <ComboboxEnhanced v-model="product.result_id" :has-error="fieldError(index, 'result_id')" :load-options="loadEndResults" placeholder="Seleccionar resultado" />
                  <p v-if="fieldError(index, 'result_id')" class="ds-field-error">{{ fieldError(index, "result_id") }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Embalagem <span v-if="isEditing && !isScheduled" class="ds-field-required">*</span></label>
                  <ComboboxEnhanced v-model="product.pack_id" :has-error="fieldError(index, 'pack_id')" :load-options="loadPackagingCategories" placeholder="Seleccionar embalagem" />
                  <p v-if="fieldError(index, 'pack_id')" class="ds-field-error">{{ fieldError(index, "pack_id") }}</p>
                </div>
                <div class="ds-field-group">
                  <label for="sample-origin" class="ds-field-label">Origem</label>
                  <BaseInput :id="`sample-origin-${index}`" v-model="product.origin" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'origin'))" />
                  <p v-if="fieldError(index, 'origin')" class="ds-field-error">{{ fieldError(index, "origin") }}</p>
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-brand-${index}`" class="ds-field-label">Marca comercial</label>
                  <BaseInput :id="`sample-brand-${index}`" v-model="product.comercial_brand" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'comercial_brand'))" />
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-qty-${index}`" class="ds-field-label">Quantidade solicitada</label>
                  <BaseInput :id="`sample-qty-${index}`" v-model="product.qty" type="number" min="0" step="0.01" class="ds-field text-right tabular-nums" :aria-invalid="Boolean(fieldError(index, 'qty'))" />
                  <p v-if="fieldError(index, 'qty')" class="ds-field-error">{{ fieldError(index, "qty") }}</p>
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-collected-qty-${index}`" class="ds-field-label">Quantidade colhida</label>
                  <BaseInput :id="`sample-collected-qty-${index}`" v-model="product.collected_qty" type="number" min="0" step="0.01" class="ds-field text-right tabular-nums" :aria-invalid="Boolean(fieldError(index, 'collected_qty'))" />
                  <p v-if="fieldError(index, 'collected_qty')" class="ds-field-error">{{ fieldError(index, "collected_qty") }}</p>
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-production-date-${index}`" class="ds-field-label">Produção</label>
                  <DateTimePicker :id="`sample-production-date-${index}`" v-model="product.production_date" type="date" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'production_date'))" />
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-expiry-date-${index}`" class="ds-field-label">Validade</label>
                  <DateTimePicker :id="`sample-expiry-date-${index}`" v-model="product.expiry_date" type="date" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'expiry_date'))" />
                </div>
                <div class="ds-field-group sm:col-span-2">
                  <label :for="`sample-location-${index}`" class="ds-field-label">Localização / ponto de colheita</label>
                  <BaseInput :id="`sample-location-${index}`" v-model="product.location" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'location'))" />
                </div>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="flex items-center gap-2">
                <TruckIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Conservação e cadeia de custódia</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="ds-field-group">
                  <label class="ds-field-label">Viatura</label>
                  <ComboboxEnhanced v-model="product.vehicle_id" :has-error="fieldError(index, 'vehicle_id')" :load-options="loadVehicles" placeholder="Seleccionar viatura" />
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Condição de temperatura</label>
                  <ComboboxEnhanced v-model="product.temperature_id" :has-error="fieldError(index, 'temperature_id')" :load-options="loadTemperatures" placeholder="Seleccionar condição" />
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-temperature-${index}`" class="ds-field-label">Temperatura observada</label>
                  <BaseInput :id="`sample-temperature-${index}`" v-model="product.temperature_value" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'temperature_value'))" />
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Responsável</label>
                  <ComboboxEnhanced v-model="product.owner_id" :has-error="fieldError(index, 'owner_id')" :load-options="loadUsers" placeholder="Seleccionar responsável" />
                </div>
                <label class="sm:col-span-2 xl:col-span-4 flex items-start gap-3 border-t border-[var(--ds-border)] pt-4 text-sm font-semibold text-[var(--ds-text-muted)]">
                  <CheckboxInput v-model="product.collected_by_lab" type="checkbox" class="ds-checkbox mt-0.5" />
                  Colhida pela equipa do laboratório
                </label>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="flex items-center gap-2">
                <ShieldCheckIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Rastreabilidade do lote</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div v-for="field in [
                  { key: 'lot', label: 'Lote' },
                  { key: 'bl', label: 'BL' },
                  { key: 'du_no', label: 'DU' },
                  { key: 'term_no', label: 'Termo' },
                  { key: 'container_no', label: 'Contentor' },
                ]" :key="field.key" class="ds-field-group">
                  <label :for="`sample-${field.key}-${index}`" class="ds-field-label">{{ field.label }}</label>
                  <BaseInput :id="`sample-${field.key}-${index}`" v-model="product[field.key]" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, field.key))" />
                  <p v-if="fieldError(index, field.key)" class="ds-field-error">{{ fieldError(index, field.key) }}</p>
                </div>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="flex items-center gap-2">
                <UserGroupIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Contexto de qualidade</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="ds-field-group">
                  <label :for="`sample-status-${index}`" class="ds-field-label">Estado da amostra</label>
                  <BaseInput :id="`sample-status-${index}`" v-model="product.sample_status" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'sample_status'))" />
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-plan-${index}`" class="ds-field-label">Plano de amostragem</label>
                  <BaseInput :id="`sample-plan-${index}`" v-model="product.sampling_plan_ref" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'sampling_plan_ref'))" />
                </div>
                <div class="ds-field-group">
                  <label :for="`sample-customer-info-${index}`" class="ds-field-label">Informação do cliente</label>
                  <BaseInput :id="`sample-customer-info-${index}`" v-model="product.customer_submitted_info" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(index, 'customer_submitted_info'))" />
                </div>
                <div class="ds-field-group sm:col-span-2 xl:col-span-3">
                  <label :for="`sample-notes-${index}`" class="ds-field-label">Observações</label>
                  <textarea :id="`sample-notes-${index}`" v-model="product.obs" rows="3" class="ds-field resize-y" :aria-invalid="Boolean(fieldError(index, 'obs'))"></textarea>
                </div>
              </div>
            </div>
          </div>
        </details>
      </div>
    </section>

    <section class="ds-panel flex flex-col-reverse gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ specimens.length }} amostras · {{ formatNumber(totalRequestedQuantity) }} unidades solicitadas</p>
      <div class="flex flex-col-reverse gap-2 sm:flex-row">
        <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || (isEditing && !form.isDirty)">
          <CheckBadgeIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : isEditing ? "Guardar alterações" : "Registar colheita" }}
        </button>
      </div>
    </section>
  </form>
</template>
