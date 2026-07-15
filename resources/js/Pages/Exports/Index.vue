<script setup>
import BaseInput from "@/Components/base/BaseInput.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  ArrowTopRightOnSquareIcon,
  BanknotesIcon,
  BeakerIcon,
  BuildingOffice2Icon,
  CircleStackIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeIcon,
  DocumentChartBarIcon,
  DocumentMagnifyingGlassIcon,
  FunnelIcon,
  MapPinIcon,
  ReceiptPercentIcon,
  ReceiptRefundIcon,
  ShieldCheckIcon,
  Squares2X2Icon,
  TruckIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import { computed, reactive, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  datasets: { type: Array, default: () => [] },
  selectedDataset: { type: String, default: "overview" },
  selectedCount: { type: Number, default: null },
  filterOptions: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
});

const catalogSearch = ref("");
const catalogCategory = ref("all");
const selected = computed(() => props.datasets.find((dataset) => dataset.key === props.selectedDataset) || null);
const filterGroup = computed(() => selected.value?.filter_group || "");
const directDatasets = computed(() => props.datasets.filter((dataset) => dataset.mode === "download").map((dataset) => dataset.key));
const isDirectDataset = computed(() => directDatasets.value.includes(props.selectedDataset));
const totalRows = computed(() => props.datasets.reduce((total, dataset) => total + Number(dataset.count || 0), 0));
const categories = computed(() => [...new Set(props.datasets.map((dataset) => dataset.category))].sort((a, b) => a.localeCompare(b, "pt")));
const filteredDatasets = computed(() => {
  const search = catalogSearch.value.trim().toLocaleLowerCase("pt");

  return props.datasets.filter((dataset) => {
    const matchesCategory = catalogCategory.value === "all" || dataset.category === catalogCategory.value;
    const haystack = `${dataset.title} ${dataset.description} ${dataset.category}`.toLocaleLowerCase("pt");

    return matchesCategory && (!search || haystack.includes(search));
  });
});

const form = reactive({
  search: props.filters.search || "",
  status: props.filters.status || (props.selectedDataset === "activity_log" ? "all" : "active"),
  date_from: props.filters.date_from || "",
  date_to: props.filters.date_to || "",
  log_name: props.filters.log_name || "",
  event: props.filters.event || "",
  causer_id: props.filters.causer_id ? String(props.filters.causer_id) : "",
  subject_type: props.filters.subject_type || "",
  subject_id: props.filters.subject_id || "",
  property: props.filters.property || "",
  batch_uuid: props.filters.batch_uuid || "",
  category_id: props.filters.category_id ? String(props.filters.category_id) : "",
  analysis_category_id: props.filters.analysis_category_id ? String(props.filters.analysis_category_id) : "",
  request_category_id: props.filters.request_category_id ? String(props.filters.request_category_id) : "",
  occurrence_category_id: props.filters.occurrence_category_id ? String(props.filters.occurrence_category_id) : "",
  occurrence_status_id: props.filters.occurrence_status_id ? String(props.filters.occurrence_status_id) : "",
  occurrence_origin_id: props.filters.occurrence_origin_id ? String(props.filters.occurrence_origin_id) : "",
  department_id: props.filters.department_id ? String(props.filters.department_id) : "",
  province: props.filters.province || "",
  has_primary_site: props.filters.has_primary_site || "all",
  matrix_id: props.filters.matrix_id ? String(props.filters.matrix_id) : "",
  tax_status: props.filters.tax_status || "all",
  withholding: props.filters.withholding || "all",
  enabled: props.filters.enabled || "all",
  result_type: props.filters.result_type || "",
  min_price: props.filters.min_price ?? "",
  max_price: props.filters.max_price ?? "",
  customer_id: props.filters.customer_id ? String(props.filters.customer_id) : "",
  warehouse_id: props.filters.warehouse_id ? String(props.filters.warehouse_id) : "",
  product_id: props.filters.product_id ? String(props.filters.product_id) : "",
  payment_type_id: props.filters.payment_type_id ? String(props.filters.payment_type_id) : "",
  transport_type_id: props.filters.transport_type_id ? String(props.filters.transport_type_id) : "",
  payment_status: props.filters.payment_status || "all",
  converted: props.filters.converted || "all",
  invoiced: props.filters.invoiced || "all",
  validation_status: props.filters.validation_status || "all",
  reason: props.filters.reason || "",
  workflow_status: props.filters.workflow_status || "",
  priority: props.filters.priority || "",
  min_total: props.filters.min_total ?? "",
  max_total: props.filters.max_total ?? "",
});

const partyFilterGroups = ["warehouses", "invoices", "quotes", "credit_notes", "receipts", "contract_guides", "trade_certificates", "quality_certificates", "customer_requests"];
const financialFilterGroups = ["invoices", "quotes", "credit_notes", "receipts"];
const priceFilterGroups = ["products", "parameters", "matrixes"];
const activeFilterCount = computed(() => Object.entries(queryPayload()).filter(([key, value]) => {
  if (key === "dataset") return false;
  if (["status", "has_primary_site", "tax_status", "withholding", "enabled", "payment_status", "converted", "invoiced", "validation_status"].includes(key)) {
    return !["all", "active"].includes(String(value));
  }

  return value !== undefined;
}).length);
const exportUrl = computed(() => route("exports.download", queryPayload()));

function datasetIcon(key) {
  if (["parameters", "profiles", "matrixes", "pending_analysis", "results_audit"].includes(key)) return BeakerIcon;
  if (["invoices", "quotes"].includes(key)) return BanknotesIcon;
  if (key === "credit_notes") return ReceiptRefundIcon;
  if (key === "receipts") return ReceiptPercentIcon;
  if (["contract_guides", "import_certificates", "export_certificates"].includes(key)) return TruckIcon;
  if (["quality_certificates", "occurrences", "nonconformity_register"].includes(key)) return ShieldCheckIcon;

  return {
    activity_log: DocumentMagnifyingGlassIcon,
    customers: BuildingOffice2Icon,
    warehouses: MapPinIcon,
    products: CubeIcon,
    customer_requests: ClipboardDocumentListIcon,
    sample_register: ClipboardDocumentListIcon,
    inventory_register: CircleStackIcon,
    maintenance_register: Squares2X2Icon,
  }[key] || DocumentChartBarIcon;
}

function queryPayload() {
  const common = {
    dataset: props.selectedDataset,
    search: form.search || undefined,
    status: form.status,
    date_from: form.date_from || undefined,
    date_to: form.date_to || undefined,
  };
  const byGroup = {
    activity: { log_name: form.log_name || undefined, event: form.event || undefined, causer_id: form.causer_id || undefined, subject_type: form.subject_type || undefined, subject_id: form.subject_id || undefined, property: form.property || undefined, batch_uuid: form.batch_uuid || undefined },
    customers: { category_id: form.category_id || undefined, province: form.province || undefined, has_primary_site: form.has_primary_site },
    warehouses: { customer_id: form.customer_id || undefined, province: form.province || undefined },
    products: { matrix_id: form.matrix_id || undefined, tax_status: form.tax_status, withholding: form.withholding, min_price: numericValue(form.min_price), max_price: numericValue(form.max_price) },
    parameters: { enabled: form.enabled, result_type: form.result_type || undefined, tax_status: form.tax_status, withholding: form.withholding, min_price: numericValue(form.min_price), max_price: numericValue(form.max_price) },
    profiles: { analysis_category_id: form.analysis_category_id || undefined },
    matrixes: { tax_status: form.tax_status, withholding: form.withholding, min_price: numericValue(form.min_price), max_price: numericValue(form.max_price) },
    invoices: { ...partyPayload(), ...totalPayload(), payment_status: form.payment_status },
    quotes: { ...partyPayload(), ...totalPayload(), converted: form.converted },
    credit_notes: { ...partyPayload(), ...totalPayload(), reason: form.reason || undefined },
    receipts: { ...partyPayload(), ...totalPayload(), payment_type_id: form.payment_type_id || undefined },
    contract_guides: partyPayload(),
    trade_certificates: { ...partyPayload(), transport_type_id: form.transport_type_id || undefined, invoiced: form.invoiced },
    quality_certificates: { ...partyPayload(), product_id: form.product_id || undefined, validation_status: form.validation_status },
    customer_requests: { ...partyPayload(), request_category_id: form.request_category_id || undefined, workflow_status: form.workflow_status || undefined, priority: form.priority || undefined },
    occurrences: { occurrence_status_id: form.occurrence_status_id || undefined, occurrence_category_id: form.occurrence_category_id || undefined, occurrence_origin_id: form.occurrence_origin_id || undefined, department_id: form.department_id || undefined },
  };

  return { ...common, ...(byGroup[filterGroup.value] || {}) };
}

function partyPayload() {
  return { customer_id: form.customer_id || undefined, warehouse_id: form.warehouse_id || undefined };
}

function totalPayload() {
  return { min_total: numericValue(form.min_total), max_total: numericValue(form.max_total) };
}

function numericValue(value) {
  return value === "" || value === null ? undefined : value;
}

function applyFilters() {
  router.get(route("exports.index"), queryPayload(), { preserveScroll: true, preserveState: false, replace: true });
}

function resetFilters() {
  Object.assign(form, {
    search: "", status: props.selectedDataset === "activity_log" ? "all" : "active", date_from: "", date_to: "",
    log_name: "", event: "", causer_id: "", subject_type: "", subject_id: "", property: "", batch_uuid: "",
    category_id: "", analysis_category_id: "", request_category_id: "", occurrence_category_id: "", occurrence_status_id: "", occurrence_origin_id: "", department_id: "",
    province: "", has_primary_site: "all", matrix_id: "", tax_status: "all", withholding: "all", enabled: "all", result_type: "", min_price: "", max_price: "",
    customer_id: "", warehouse_id: "", product_id: "", payment_type_id: "", transport_type_id: "", payment_status: "all", converted: "all", invoiced: "all", validation_status: "all",
    reason: "", workflow_status: "", priority: "", min_total: "", max_total: "",
  });
  applyFilters();
}

function formatDate(value) {
  if (!value) return "Sem actividade";
  return new Date(value).toLocaleString("pt-PT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit" });
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><ArrowDownTrayIcon class="h-5 w-5" /></span>
          <div class="min-w-0">
            <p class="ds-kicker">Governação e portabilidade</p>
            <h1 class="ds-heading mt-1 text-2xl">Central de exportações</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Registos mestres, comerciais e operacionais com filtros, permissões e estrutura Excel consistente.</p>
          </div>
        </div>
        <span class="ds-chip shrink-0"><span class="lims-status-dot lims-status-dot-release" />{{ datasets.length }} conjuntos autorizados</span>
      </div>
      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:border-b-0 sm:border-r"><dt class="ds-table-heading">Conjuntos disponíveis</dt><dd class="ds-heading mt-2 text-xl tabular-nums">{{ datasets.length }}</dd></div>
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:border-b-0 sm:border-r"><dt class="ds-table-heading">Linhas catalogadas</dt><dd class="ds-heading mt-2 text-xl tabular-nums">{{ totalRows.toLocaleString("pt-PT") }}</dd></div>
        <div class="px-5 py-4"><dt class="ds-table-heading">Selecção actual</dt><dd class="mt-2 truncate text-sm font-bold text-[var(--ds-text)]">{{ selected?.title || "Visão geral" }}</dd></div>
      </dl>
    </section>

    <div class="grid min-w-0 gap-6 xl:grid-cols-5">
      <section class="ds-panel min-w-0 overflow-hidden xl:col-span-2">
        <div class="space-y-3 border-b border-[var(--ds-border)] px-5 py-4">
          <div><p class="ds-kicker">Catálogo</p><h2 class="ds-heading mt-2 text-base">Conjuntos de dados</h2></div>
          <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
            <BaseInput v-model="catalogSearch" type="search" class="ds-field w-full" placeholder="Pesquisar conjunto" />
            <select v-model="catalogCategory" class="ds-field w-full"><option value="all">Todas as áreas</option><option v-for="category in categories" :key="category" :value="category">{{ category }}</option></select>
          </div>
          <p class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ filteredDatasets.length }} de {{ datasets.length }} conjuntos</p>
        </div>

        <div v-if="filteredDatasets.length" class="divide-y divide-[var(--ds-border)]">
          <Link v-for="dataset in filteredDatasets" :key="dataset.key" :href="dataset.href" :class="['group flex min-w-0 items-start gap-3 border-l-2 px-5 py-4 transition', selectedDataset === dataset.key ? 'border-l-[rgb(var(--primary-600-rgb))] bg-[rgb(var(--primary-50-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.08)]' : 'border-l-transparent hover:bg-[var(--ds-panel-subtle)]']">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]"><component :is="datasetIcon(dataset.key)" class="h-4 w-4" /></span>
            <span class="min-w-0 flex-1">
              <span class="flex flex-wrap items-center justify-between gap-2"><span class="font-bold text-[var(--ds-text)]">{{ dataset.title }}</span><span class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ Number(dataset.count || 0).toLocaleString("pt-PT") }}</span></span>
              <span class="mt-1 block text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ dataset.category }}</span>
              <span class="ds-copy mt-2 block text-xs">{{ dataset.description }}</span>
              <span class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-[var(--ds-text-soft)]"><ClockIcon class="h-3.5 w-3.5" />{{ dataset.mode === "workspace" ? "Área operacional" : formatDate(dataset.updated_at) }}</span>
            </span>
            <ArrowTopRightOnSquareIcon v-if="dataset.mode === 'workspace'" class="mt-1 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
          </Link>
        </div>
        <div v-else class="px-5 py-10 text-center"><p class="text-sm font-bold text-[var(--ds-text)]">Nenhum conjunto encontrado</p><p class="ds-copy mt-1 text-xs">Ajuste a pesquisa ou a área seleccionada.</p></div>
      </section>

      <section class="ds-panel min-w-0 overflow-hidden xl:col-span-3">
        <template v-if="isDirectDataset && selected">
          <div class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div class="min-w-0"><p class="ds-kicker">Preparar ficheiro</p><h2 class="ds-heading mt-2 text-lg">{{ selected.title }}</h2><p class="ds-copy mt-1 text-sm">{{ selected.description }}</p></div>
            <span class="ds-chip shrink-0">{{ Number(selectedCount || 0).toLocaleString("pt-PT") }} linhas</span>
          </div>

          <form class="p-5 sm:p-6" @submit.prevent="applyFilters">
            <div class="grid gap-4 md:grid-cols-2">
              <label class="block md:col-span-2"><span class="ds-field-label">Pesquisa</span><BaseInput v-model="form.search" type="search" class="ds-field mt-1 w-full" placeholder="Número, código, nome, referência ou descrição" /></label>
              <div v-if="filterGroup !== 'activity'" class="md:col-span-2">
                <span class="ds-field-label">Estado do registo</span>
                <div class="mt-1 grid grid-cols-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-1">
                  <button v-for="option in [{ value: 'active', label: 'Activos' }, { value: 'archived', label: 'Arquivados' }, { value: 'all', label: 'Todos' }]" :key="option.value" type="button" :class="['min-w-0 rounded-md px-2 py-2 text-xs font-bold transition', form.status === option.value ? 'bg-[var(--ds-panel)] text-[rgb(var(--primary-700-rgb))] shadow-sm' : 'text-[var(--ds-text-muted)] hover:text-[var(--ds-text)]']" @click="form.status = option.value">{{ option.label }}</button>
                </div>
              </div>

              <template v-if="filterGroup === 'activity'">
                <label class="block"><span class="ds-field-label">Nome do log</span><select v-model="form.log_name" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.log_names || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Evento</span><select v-model="form.event" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.events || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Actor</span><select v-model="form.causer_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.actors || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Tipo de entidade</span><select v-model="form.subject_type" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.subject_types || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">ID da entidade</span><BaseInput v-model="form.subject_id" type="number" min="1" class="ds-field mt-1 w-full" /></label>
                <label class="block"><span class="ds-field-label">Propriedade</span><BaseInput v-model="form.property" type="text" class="ds-field mt-1 w-full" placeholder="Ex.: attributes, old, ip_address" /></label>
                <label class="block md:col-span-2"><span class="ds-field-label">UUID do lote</span><BaseInput v-model="form.batch_uuid" type="text" class="ds-field mt-1 w-full font-mono" /></label>
              </template>

              <template v-if="filterGroup === 'customers'">
                <label class="block"><span class="ds-field-label">Categoria</span><select v-model="form.category_id" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.categories || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Província</span><select v-model="form.province" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.provinces || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label class="block md:col-span-2"><span class="ds-field-label">Local principal</span><select v-model="form.has_primary_site" class="ds-field mt-1 w-full"><option value="all">Com ou sem local</option><option value="yes">Com local principal</option><option value="no">Sem local principal</option></select></label>
              </template>

              <template v-if="partyFilterGroups.includes(filterGroup)">
                <label class="block"><span class="ds-field-label">Cliente</span><select v-model="form.customer_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.customers || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Local</span><select v-model="form.warehouse_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.warehouses || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
              </template>
              <label v-if="filterGroup === 'warehouses'" class="block md:col-span-2"><span class="ds-field-label">Província</span><select v-model="form.province" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.provinces || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>

              <template v-if="filterGroup === 'products'">
                <label class="block md:col-span-2"><span class="ds-field-label">Matriz</span><select v-model="form.matrix_id" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.matrixes || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
              </template>
              <template v-if="['products', 'parameters', 'matrixes'].includes(filterGroup)">
                <label class="block"><span class="ds-field-label">Tributação</span><select v-model="form.tax_status" class="ds-field mt-1 w-full"><option value="all">Todos</option><option value="taxable">Tributados</option><option value="exempt">Isentos</option></select></label>
                <label class="block"><span class="ds-field-label">Retenção</span><select v-model="form.withholding" class="ds-field mt-1 w-full"><option value="all">Todos</option><option value="yes">Com retenção</option><option value="no">Sem retenção</option></select></label>
              </template>
              <template v-if="filterGroup === 'parameters'">
                <label class="block"><span class="ds-field-label">Disponibilidade</span><select v-model="form.enabled" class="ds-field mt-1 w-full"><option value="all">Todos</option><option value="yes">Activos</option><option value="no">Inactivos</option></select></label>
                <label class="block"><span class="ds-field-label">Tipo de resultado</span><select v-model="form.result_type" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.result_types || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
              </template>
              <label v-if="filterGroup === 'profiles'" class="block md:col-span-2"><span class="ds-field-label">Categoria analítica</span><select v-model="form.analysis_category_id" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.analysis_categories || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>

              <div v-if="priceFilterGroups.includes(filterGroup)" class="grid grid-cols-2 gap-3 md:col-span-2"><label class="block"><span class="ds-field-label">Preço mínimo</span><BaseInput v-model="form.min_price" type="number" min="0" step="0.01" class="ds-field mt-1 w-full" /></label><label class="block"><span class="ds-field-label">Preço máximo</span><BaseInput v-model="form.max_price" type="number" min="0" step="0.01" class="ds-field mt-1 w-full" /></label></div>
              <div v-if="financialFilterGroups.includes(filterGroup)" class="grid grid-cols-2 gap-3 md:col-span-2"><label class="block"><span class="ds-field-label">Valor mínimo</span><BaseInput v-model="form.min_total" type="number" min="0" step="0.01" class="ds-field mt-1 w-full" /></label><label class="block"><span class="ds-field-label">Valor máximo</span><BaseInput v-model="form.max_total" type="number" min="0" step="0.01" class="ds-field mt-1 w-full" /></label></div>

              <label v-if="filterGroup === 'invoices'" class="block md:col-span-2"><span class="ds-field-label">Pagamento</span><select v-model="form.payment_status" class="ds-field mt-1 w-full"><option value="all">Todos</option><option value="paid">Pagas</option><option value="unpaid">Não pagas</option><option value="canceled">Anuladas</option></select></label>
              <label v-if="filterGroup === 'quotes'" class="block md:col-span-2"><span class="ds-field-label">Conversão</span><select v-model="form.converted" class="ds-field mt-1 w-full"><option value="all">Todas</option><option value="yes">Convertidas em factura</option><option value="no">Não convertidas</option></select></label>
              <label v-if="filterGroup === 'credit_notes'" class="block md:col-span-2"><span class="ds-field-label">Motivo</span><select v-model="form.reason" class="ds-field mt-1 w-full"><option value="">Todos</option><option value="R">Rectificação</option><option value="A">Anulação</option></select></label>
              <label v-if="filterGroup === 'receipts'" class="block md:col-span-2"><span class="ds-field-label">Meio de pagamento</span><select v-model="form.payment_type_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.payment_types || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>

              <template v-if="filterGroup === 'trade_certificates'">
                <label class="block"><span class="ds-field-label">Transporte</span><select v-model="form.transport_type_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.transport_types || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Facturação</span><select v-model="form.invoiced" class="ds-field mt-1 w-full"><option value="all">Todos</option><option value="yes">Facturados</option><option value="no">Não facturados</option></select></label>
              </template>
              <template v-if="filterGroup === 'quality_certificates'">
                <label class="block"><span class="ds-field-label">Produto</span><select v-model="form.product_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.products || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Validação</span><select v-model="form.validation_status" class="ds-field mt-1 w-full"><option value="all">Todos</option><option value="validated">Validados</option><option value="pending">Pendentes</option></select></label>
              </template>
              <template v-if="filterGroup === 'customer_requests'">
                <label class="block"><span class="ds-field-label">Categoria</span><select v-model="form.request_category_id" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.request_categories || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Estado do fluxo</span><select v-model="form.workflow_status" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.workflow_statuses || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label class="block md:col-span-2"><span class="ds-field-label">Prioridade</span><select v-model="form.priority" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.priorities || []" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
              </template>
              <template v-if="filterGroup === 'occurrences'">
                <label class="block"><span class="ds-field-label">Estado</span><select v-model="form.occurrence_status_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.occurrence_statuses || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Categoria</span><select v-model="form.occurrence_category_id" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.occurrence_categories || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Origem</span><select v-model="form.occurrence_origin_id" class="ds-field mt-1 w-full"><option value="">Todas</option><option v-for="option in filterOptions.occurrence_origins || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
                <label class="block"><span class="ds-field-label">Departamento</span><select v-model="form.department_id" class="ds-field mt-1 w-full"><option value="">Todos</option><option v-for="option in filterOptions.departments || []" :key="option.value" :value="String(option.value)">{{ option.label }}</option></select></label>
              </template>

              <label class="block"><span class="ds-field-label">{{ selected.date_label || "Data" }} desde</span><BaseInput v-model="form.date_from" type="date" class="ds-field mt-1 w-full" /></label>
              <label class="block"><span class="ds-field-label">{{ selected.date_label || "Data" }} até</span><BaseInput v-model="form.date_to" type="date" class="ds-field mt-1 w-full" /></label>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-2"><button type="button" class="ds-icon-button" title="Limpar filtros" @click="resetFilters"><XMarkIcon class="h-4 w-4" /><span class="sr-only">Limpar filtros</span></button><button type="submit" class="ds-button ds-button-secondary"><FunnelIcon class="h-4 w-4" />Aplicar filtros<span v-if="activeFilterCount" class="ds-badge ds-badge-info">{{ activeFilterCount }}</span></button></div>
              <a :href="exportUrl" class="ds-button ds-button-primary justify-center"><ArrowDownTrayIcon class="h-4 w-4" />Exportar XLSX</a>
            </div>
          </form>
        </template>
        <div v-else class="grid min-h-72 place-items-center p-8 text-center"><div class="max-w-md"><CircleStackIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" /><h2 class="ds-heading mt-4 text-lg">Catálogo de exportação</h2><p class="ds-copy mt-2 text-sm">Seleccione um conjunto autorizado para preparar o ficheiro ou abrir a respectiva área operacional.</p></div></div>
      </section>
    </div>
  </div>
</template>
