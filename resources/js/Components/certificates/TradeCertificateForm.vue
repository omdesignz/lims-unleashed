<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import FinancialObservationForm from "@/Components/documents/FinancialObservationForm.vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  BanknotesIcon,
  BuildingOfficeIcon,
  CheckBadgeIcon,
  CubeIcon,
  DocumentCheckIcon,
  GlobeAltIcon,
  MapPinIcon,
  PlusIcon,
  ShieldCheckIcon,
  TrashIcon,
  TruckIcon,
} from "@heroicons/vue/24/outline";
import { computed, ref, watch } from "vue";

const props = defineProps({
  kind: { type: String, required: true, validator: (value) => ["import", "export"].includes(value) },
  record: { type: Object, default: null },
});

const source = props.record?.data || props.record || {};
const isImport = computed(() => props.kind === "import");
const isEditing = computed(() => Boolean(source.id));
const observationsOnly = computed(() => isEditing.value && Boolean(source.invoice_id || source.invoiced));
const config = computed(() => isImport.value
  ? {
      title: "Certificado de importação",
      kicker: "Controlo de entrada transfronteiriça",
      routePrefix: "importcertificates",
      routeParameter: "importcertificate",
      icon: ShieldCheckIcon,
    }
  : {
      title: "Certificado de exportação",
      kicker: "Controlo de saída transfronteiriça",
      routePrefix: "exportcertificates",
      routeParameter: "exportcertificate",
      icon: GlobeAltIcon,
    });

function option(value, label, fallback) {
  return value ? { value, label: label || `${fallback} #${value}` } : null;
}

function emptyItem() {
  return isImport.value
    ? { product_id: null, qty: 0, origin: "", validity: "", lot: "", bl_no: "" }
    : { product_id: null, qty: 0 };
}

const existingItems = (source.items || []).map((item) => ({
  ...item,
  product_id: option(item.product_id, item.product, "Produto"),
}));

const form = useForm(isImport.value
  ? {
      date: source.date || "",
      trans_type_id: option(source.trans_type_id, source.trans_type, "Transporte"),
      port_exit: source.port_exit || "",
      port_entry: source.port_entry || "",
      destination_country_id: option(source.destination_country_id, source.destination_country, "País"),
      importer_id: option(source.importer_id, source.importer, "Importador"),
      importer_warehouse_id: option(source.importer_warehouse_id, source.importer_warehouse, "Armazém"),
      exporter_id: option(source.exporter_id, source.exporter, "Exportador"),
      exporter_warehouse_id: option(source.exporter_warehouse_id, source.exporter_warehouse, "Armazém"),
      currency_id: option(source.currency_id, source.currency, "Moeda"),
      cost_freight: source.cost_freight ?? 0,
      cost_insurance: source.cost_insurance ?? 0,
      cost_final: source.cost_final ?? 0,
      vat: source.vat ?? 0,
      vat_cost: source.vat_cost ?? 0,
      authorized_personnel: source.authorized_personnel || "",
      obs: source.obs || "",
      items: existingItems.length ? existingItems : [emptyItem()],
    }
  : {
      date: source.date || "",
      expedition_date: source.expedition_date || "",
      exporter_id: option(source.exporter_id, source.exporter, "Exportador"),
      exporter_warehouse_id: option(source.exporter_warehouse_id, source.exporter_warehouse, "Armazém"),
      trans_type_id: option(source.trans_type_id, source.trans_type, "Transporte"),
      country_origin_id: option(source.country_origin_id, source.country_origin, "País"),
      origin_city: source.origin_city || "",
      country_destination_id: option(source.country_destination_id, source.country_destination, "País"),
      destination_city: source.destination_city || "",
      expedition_location: source.expedition_location || "",
      authorized_personnel: source.authorized_personnel || "",
      obs: source.obs || "",
      items: existingItems.length ? existingItems : [emptyItem()],
    });

const loadingImporterWarehouses = ref(false);
const loadingExporterWarehouses = ref(false);
const totalQuantity = computed(() => form.items.reduce((total, item) => total + (Number.parseFloat(item.qty) || 0), 0));
const totalCost = computed(() => isImport.value
  ? [form.cost_freight, form.cost_insurance, form.cost_final, form.vat_cost]
      .reduce((total, value) => total + (Number.parseFloat(value) || 0), 0)
  : 0);

watch(() => [form.cost_final, form.vat], () => {
  if (!isImport.value) {
    return;
  }

  const taxableValue = Number.parseFloat(form.cost_final) || 0;
  const vatPercentage = Number.parseFloat(form.vat) || 0;
  form.vat_cost = (taxableValue * vatPercentage / 100).toFixed(2);
});

watch(() => form.importer_id?.value, (customerId, previousCustomerId) => {
  if (isImport.value && customerId && customerId !== previousCustomerId) {
    selectFirstWarehouse(customerId, "importer");
  }
});

watch(() => form.exporter_id?.value, (customerId, previousCustomerId) => {
  if (customerId && customerId !== previousCustomerId) {
    selectFirstWarehouse(customerId, "exporter");
  }
});

async function fetchRecords(path) {
  try {
    const response = await fetch(path, { headers: { Accept: "application/json" } });
    return response.ok ? await response.json() : [];
  } catch {
    return [];
  }
}

function queryUrl(path, query, extra = {}) {
  const params = new URLSearchParams({ q: query || "", ...extra });
  return `${path}?${params.toString()}`;
}

async function selectFirstWarehouse(customerId, party) {
  const loading = party === "importer" ? loadingImporterWarehouses : loadingExporterWarehouses;
  loading.value = true;

  const records = await fetchRecords(queryUrl("/warehouses/getWarehouse", "", { customer_id: customerId }));
  const firstWarehouse = records[0] ? { value: records[0].id, label: records[0].address } : null;

  if (party === "importer") {
    form.importer_warehouse_id = firstWarehouse;
  } else {
    form.exporter_warehouse_id = firstWarehouse;
  }

  loading.value = false;
}

async function loadCustomers(type, query, setOptions) {
  const records = await fetchRecords(queryUrl("/customers/getCustomer", query, { type }));
  setOptions(records.map((record) => ({ value: record.id, label: record.name })));
}

function loadImporters(query, setOptions) {
  return loadCustomers("importer", query, setOptions);
}

function loadExporters(query, setOptions) {
  return loadCustomers("exporter", query, setOptions);
}

async function loadWarehouses(party, query, setOptions) {
  const customerId = party === "importer" ? form.importer_id?.value : form.exporter_id?.value;

  if (!customerId) {
    setOptions([]);
    return;
  }

  const records = await fetchRecords(queryUrl("/warehouses/getWarehouse", query, { customer_id: customerId }));
  setOptions(records.map((record) => ({ value: record.id, label: record.address })));
}

function loadImporterWarehouses(query, setOptions) {
  return loadWarehouses("importer", query, setOptions);
}

function loadExporterWarehouses(query, setOptions) {
  return loadWarehouses("exporter", query, setOptions);
}

async function loadTransportTypes(query, setOptions) {
  const records = await fetchRecords(queryUrl("/transportcategories/getTransportCategory", query));
  setOptions(records.map((record) => ({ value: record.id, label: record.name })));
}

async function loadCountries(query, setOptions) {
  const records = await fetchRecords(queryUrl("/countries/getCountry", query));
  setOptions(records.map((record) => ({ value: record.id, label: record.name })));
}

async function loadCurrencies(query, setOptions) {
  const records = await fetchRecords(queryUrl("/currencies/getCurrency", query));
  setOptions(records.map((record) => ({
    value: record.id,
    label: [record.code, record.name].filter(Boolean).join(" · "),
  })));
}

async function loadProducts(query, setOptions) {
  const records = await fetchRecords(queryUrl("/phytosanitary-products/getPhytosanitaryProduct", query));
  setOptions(records.map((record) => ({ value: record.id, label: record.name })));
}

function addItem() {
  form.items.push(emptyItem());
}

function removeItem(index) {
  form.items.splice(index, 1);
}

function formatNumber(value) {
  return new Intl.NumberFormat("pt-PT", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.parseFloat(value) || 0);
}

function submit() {
  if (form.processing || observationsOnly.value) return;
  const options = {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (!isEditing.value) {
        form.reset();
      } else {
        form.defaults();
      }
    },
    onNetworkError: () => form.setError('request', 'Ligação interrompida. Os dados foram preservados; tente novamente.'),
    onHttpException: () => form.setError('request', 'Não foi possível guardar. Os dados foram preservados; tente novamente.'),
  };

  try {
    if (isEditing.value) {
      form.put(route(`${config.value.routePrefix}.update`, {
        [config.value.routeParameter]: source.id,
      }), options);
      return;
    }

    form.post(route(`${config.value.routePrefix}.store`), options);
  } catch {
    form.setError('request', 'Não foi possível iniciar o pedido. Os dados foram preservados; tente novamente.');
  }
}
</script>

<template>
  <div>
  <FinancialObservationForm v-if="observationsOnly" :kind="`${kind}_certificate`" :record="record" />
  <form v-else class="min-w-0 space-y-6 overflow-x-clip" @submit.prevent="submit">
    <p v-if="form.errors.request" role="alert" class="ds-field-error">{{ form.errors.request }}</p>
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-ghost px-0">
        <ArrowLeftIcon class="h-4 w-4" />
        Voltar ao registo
      </Link>

      <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <component :is="config.icon" class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">{{ config.kicker }}</p>
            <h1 class="ds-heading mt-1 text-2xl">{{ isEditing ? `Editar ${config.title.toLowerCase()}` : `Novo ${config.title.toLowerCase()}` }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">{{ source.cert_no ? `Referência ${source.cert_no}` : "Registo de partes, percurso e produtos sob controlo documental." }}</p>
          </div>
        </div>
        <span class="ds-badge" :class="form.isDirty ? 'ds-badge-warning' : 'ds-badge-neutral'">
          {{ form.isDirty ? "Alterações por guardar" : "Sem alterações" }}
        </span>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Modo</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ isEditing ? "Revisão" : "Emissão" }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Produtos</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ form.items.length }} linhas</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Quantidade</dt>
          <dd class="mt-2 text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ formatNumber(totalQuantity) }} un.</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <DocumentCheckIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Identificação e responsabilidade</h2>
            <p class="ds-copy mt-1 text-xs">Datas de emissão, transporte e responsável autorizado.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-4">
        <div class="ds-field-group">
          <label for="certificate-date" class="ds-field-label">Data do certificado <span class="ds-field-required">*</span></label>
          <DateTimePicker id="certificate-date" v-model="form.date" type="date" class="ds-field" :aria-invalid="Boolean(form.errors.date)" :required="isImport" />
          <p v-if="form.errors.date" class="ds-field-error">{{ form.errors.date }}</p>
        </div>
        <div v-if="!isImport" class="ds-field-group">
          <label for="certificate-expedition-date" class="ds-field-label">Data de expedição <span class="ds-field-required">*</span></label>
          <DateTimePicker id="certificate-expedition-date" v-model="form.expedition_date" type="date" class="ds-field" :aria-invalid="Boolean(form.errors.expedition_date)" required />
          <p v-if="form.errors.expedition_date" class="ds-field-error">{{ form.errors.expedition_date }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label">Tipo de transporte <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.trans_type_id" :has-error="form.errors.trans_type_id" :load-options="loadTransportTypes" placeholder="Seleccionar transporte" />
          <p v-if="form.errors.trans_type_id" class="ds-field-error">{{ form.errors.trans_type_id }}</p>
        </div>
        <div class="ds-field-group">
          <label for="certificate-authorized-person" class="ds-field-label">Responsável autorizado <span class="ds-field-required">*</span></label>
          <BaseInput id="certificate-authorized-person" v-model="form.authorized_personnel" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.authorized_personnel)" required />
          <p v-if="form.errors.authorized_personnel" class="ds-field-error">{{ form.errors.authorized_personnel }}</p>
        </div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <BuildingOfficeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Partes comerciais</h2>
            <p class="ds-copy mt-1 text-xs">Entidades e instalações associadas ao movimento.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-2">
        <div v-if="isImport" class="grid gap-4 sm:grid-cols-2">
          <div class="ds-field-group">
            <label class="ds-field-label">Importador <span class="ds-field-required">*</span></label>
            <ComboboxEnhanced v-model="form.importer_id" :has-error="form.errors.importer_id" :load-options="loadImporters" placeholder="Seleccionar importador" />
            <p v-if="form.errors.importer_id" class="ds-field-error">{{ form.errors.importer_id }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Armazém do importador <span class="ds-field-required">*</span></label>
            <ComboboxEnhanced v-model="form.importer_warehouse_id" :disable-input="!form.importer_id || loadingImporterWarehouses" :loading="loadingImporterWarehouses" :has-error="form.errors.importer_warehouse_id" :load-options="loadImporterWarehouses" placeholder="Seleccionar armazém" />
            <p v-if="form.errors.importer_warehouse_id" class="ds-field-error">{{ form.errors.importer_warehouse_id }}</p>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2" :class="!isImport ? 'lg:col-span-2' : ''">
          <div class="ds-field-group">
            <label class="ds-field-label">Exportador <span class="ds-field-required">*</span></label>
            <ComboboxEnhanced v-model="form.exporter_id" :has-error="form.errors.exporter_id" :load-options="loadExporters" placeholder="Seleccionar exportador" />
            <p v-if="form.errors.exporter_id" class="ds-field-error">{{ form.errors.exporter_id }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Armazém do exportador <span class="ds-field-required">*</span></label>
            <ComboboxEnhanced v-model="form.exporter_warehouse_id" :disable-input="!form.exporter_id || loadingExporterWarehouses" :loading="loadingExporterWarehouses" :has-error="form.errors.exporter_warehouse_id" :load-options="loadExporterWarehouses" placeholder="Seleccionar armazém" />
            <p v-if="form.errors.exporter_warehouse_id" class="ds-field-error">{{ form.errors.exporter_warehouse_id }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <TruckIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Percurso logístico</h2>
            <p class="ds-copy mt-1 text-xs">Origem, destino e pontos de passagem declarados.</p>
          </div>
        </div>
      </header>

      <div v-if="isImport" class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-3">
        <div class="ds-field-group">
          <label for="certificate-port-exit" class="ds-field-label">Porto de saída <span class="ds-field-required">*</span></label>
          <BaseInput id="certificate-port-exit" v-model="form.port_exit" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.port_exit)" required />
          <p v-if="form.errors.port_exit" class="ds-field-error">{{ form.errors.port_exit }}</p>
        </div>
        <div class="ds-field-group">
          <label for="certificate-port-entry" class="ds-field-label">Porto de entrada <span class="ds-field-required">*</span></label>
          <BaseInput id="certificate-port-entry" v-model="form.port_entry" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.port_entry)" required />
          <p v-if="form.errors.port_entry" class="ds-field-error">{{ form.errors.port_entry }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label">País de destino <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.destination_country_id" :has-error="form.errors.destination_country_id" :load-options="loadCountries" placeholder="Seleccionar país" />
          <p v-if="form.errors.destination_country_id" class="ds-field-error">{{ form.errors.destination_country_id }}</p>
        </div>
      </div>

      <div v-else class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-4">
        <div class="ds-field-group">
          <label class="ds-field-label">País de origem <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.country_origin_id" :has-error="form.errors.country_origin_id" :load-options="loadCountries" placeholder="Seleccionar país" />
          <p v-if="form.errors.country_origin_id" class="ds-field-error">{{ form.errors.country_origin_id }}</p>
        </div>
        <div class="ds-field-group">
          <label for="certificate-origin-city" class="ds-field-label">Cidade de origem <span class="ds-field-required">*</span></label>
          <BaseInput id="certificate-origin-city" v-model="form.origin_city" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.origin_city)" required />
          <p v-if="form.errors.origin_city" class="ds-field-error">{{ form.errors.origin_city }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label">País de destino <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.country_destination_id" :has-error="form.errors.country_destination_id" :load-options="loadCountries" placeholder="Seleccionar país" />
          <p v-if="form.errors.country_destination_id" class="ds-field-error">{{ form.errors.country_destination_id }}</p>
        </div>
        <div class="ds-field-group">
          <label for="certificate-destination-city" class="ds-field-label">Cidade de destino <span class="ds-field-required">*</span></label>
          <BaseInput id="certificate-destination-city" v-model="form.destination_city" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.destination_city)" required />
          <p v-if="form.errors.destination_city" class="ds-field-error">{{ form.errors.destination_city }}</p>
        </div>
        <div class="ds-field-group sm:col-span-2 xl:col-span-4">
          <label for="certificate-expedition-location" class="ds-field-label">Local de expedição <span class="ds-field-required">*</span></label>
          <BaseInput id="certificate-expedition-location" v-model="form.expedition_location" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.expedition_location)" required />
          <p v-if="form.errors.expedition_location" class="ds-field-error">{{ form.errors.expedition_location }}</p>
        </div>
      </div>
    </section>

    <section v-if="isImport" class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <BanknotesIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Valor declarado</h2>
            <p class="ds-copy mt-1 text-xs">Moeda, custos logísticos, mercadoria e imposto.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-3">
        <div class="ds-field-group">
          <label class="ds-field-label">Moeda <span class="ds-field-required">*</span></label>
          <ComboboxEnhanced v-model="form.currency_id" :has-error="form.errors.currency_id" :load-options="loadCurrencies" placeholder="Seleccionar moeda" />
          <p v-if="form.errors.currency_id" class="ds-field-error">{{ form.errors.currency_id }}</p>
        </div>
        <div v-for="field in [
          { key: 'cost_freight', label: 'Frete' },
          { key: 'cost_insurance', label: 'Seguro' },
          { key: 'cost_final', label: 'Valor da mercadoria' },
          { key: 'vat', label: 'IVA (%)' },
        ]" :key="field.key" class="ds-field-group">
          <label :for="`certificate-${field.key}`" class="ds-field-label">{{ field.label }} <span class="ds-field-required">*</span></label>
          <BaseInput :id="`certificate-${field.key}`" v-model.number="form[field.key]" type="number" min="0" step="0.01" class="ds-field text-right tabular-nums" :aria-invalid="Boolean(form.errors[field.key])" required />
          <p v-if="form.errors[field.key]" class="ds-field-error">{{ form.errors[field.key] }}</p>
        </div>
        <div class="ds-field-group">
          <label for="certificate-vat-cost" class="ds-field-label">Valor do IVA</label>
          <BaseInput id="certificate-vat-cost" :value="formatNumber(form.vat_cost)" type="text" class="ds-field text-right tabular-nums" readonly />
          <p v-if="form.errors.vat_cost" class="ds-field-error">{{ form.errors.vat_cost }}</p>
        </div>
      </div>
      <div class="flex items-center justify-between gap-4 border-t border-[var(--ds-border-strong)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <span class="text-sm font-bold text-[var(--ds-text)]">Total declarado</span>
        <span class="text-base font-bold tabular-nums text-[var(--ds-text)]">{{ formatNumber(totalCost) }}</span>
      </div>
    </section>

    <section class="ds-panel min-w-0 overflow-hidden">
      <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
          <CubeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Produtos certificados</h2>
            <p class="ds-copy mt-1 text-xs">{{ form.items.length }} linhas · {{ formatNumber(totalQuantity) }} unidades</p>
          </div>
        </div>
        <button type="button" class="ds-button ds-button-secondary" @click="addItem">
          <PlusIcon class="h-4 w-4" />
          Adicionar produto
        </button>
      </header>

      <p v-if="form.errors.items" class="ds-field-error px-5 pt-4 sm:px-6">{{ form.errors.items }}</p>

      <div v-if="form.items.length" class="overflow-x-auto">
        <DataTable class="ds-table min-w-[58rem]">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-header min-w-64 px-5 py-3 text-left sm:px-6">Produto</th>
              <th class="ds-table-header w-32 px-4 py-3 text-left">Quantidade</th>
              <template v-if="isImport">
                <th class="ds-table-header min-w-44 px-4 py-3 text-left">Origem</th>
                <th class="ds-table-header w-40 px-4 py-3 text-left">Validade</th>
                <th class="ds-table-header min-w-36 px-4 py-3 text-left">Lote</th>
                <th class="ds-table-header min-w-36 px-4 py-3 text-left">BL</th>
              </template>
              <th class="ds-table-header w-16 px-5 py-3 sm:px-6"><span class="sr-only">Acções</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--ds-border)]">
            <tr v-for="(item, index) in form.items" :key="index" class="ds-table-row align-top">
              <td class="ds-table-cell px-5 py-3 sm:px-6">
                <ComboboxEnhanced v-model="item.product_id" :has-error="form.errors[`items.${index}.product_id`]" :load-options="loadProducts" placeholder="Seleccionar produto" />
              </td>
              <td class="ds-table-cell px-4 py-3">
                <BaseInput v-model.number="item.qty" type="number" min="0.01" step="0.01" class="ds-field text-right tabular-nums" required />
              </td>
              <template v-if="isImport">
                <td class="ds-table-cell px-4 py-3"><BaseInput v-model="item.origin" type="text" class="ds-field" required /></td>
                <td class="ds-table-cell px-4 py-3"><DateTimePicker v-model="item.validity" type="date" class="ds-field" required /></td>
                <td class="ds-table-cell px-4 py-3"><BaseInput v-model="item.lot" type="text" class="ds-field" required /></td>
                <td class="ds-table-cell px-4 py-3"><BaseInput v-model="item.bl_no" type="text" class="ds-field" required /></td>
              </template>
              <td class="ds-table-cell px-5 py-3 text-right sm:px-6">
                <button type="button" class="ds-icon-button text-rose-600" title="Remover produto" @click="removeItem(index)">
                  <TrashIcon class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>
      <div v-else class="px-5 py-10 text-center sm:px-6">
        <CubeIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" />
        <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum produto adicionado</p>
        <button type="button" class="ds-button ds-button-secondary mt-4" @click="addItem">
          <PlusIcon class="h-4 w-4" />
          Adicionar produto
        </button>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <CheckBadgeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Facturação e observações</h2>
            <p class="ds-copy mt-1 text-xs">Vínculo financeiro e contexto complementar do certificado.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
        <div class="space-y-4">
          <div>
            <p class="ds-field-label">Estado de facturação</p>
            <p class="mt-2 text-sm font-semibold text-[var(--ds-text)]">
              {{ source.invoice_id ? `Factura associada #${source.invoice_id}` : source.invoiced ? "Facturado · vínculo histórico indisponível" : "Ainda não facturado" }}
            </p>
            <p class="ds-copy mt-2 text-xs">
              {{ source.invoice_id || source.invoiced ? "O vínculo financeiro existente é preservado ao guardar." : "A factura é criada pela acção Emitir factura, após guardar o certificado." }}
            </p>
          </div>
        </div>
        <div class="ds-field-group">
          <label for="certificate-observations" class="ds-field-label">Observações</label>
          <textarea id="certificate-observations" v-model="form.obs" rows="5" class="ds-field min-h-32 resize-y" :aria-invalid="Boolean(form.errors.obs)"></textarea>
          <p v-if="form.errors.obs" class="ds-field-error">{{ form.errors.obs }}</p>
        </div>
      </div>
    </section>

    <section class="ds-panel flex flex-col-reverse gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ form.items.length }} produtos · {{ formatNumber(totalQuantity) }} unidades</p>
      <div class="flex flex-col-reverse gap-2 sm:flex-row">
        <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || (isEditing && !form.isDirty)">
          <CheckBadgeIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : isEditing ? "Guardar alterações" : "Emitir certificado" }}
        </button>
      </div>
    </section>
  </form>
  </div>
</template>
