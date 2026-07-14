<script setup>
import Pagination from "@/Components/pagination.vue";
import PortalRequestForm from "@/Components/portal/PortalRequestForm.vue";
import SlideOver from "@/Components/slide-over.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  BeakerIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  QueueListIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import debounce from "lodash/debounce";
import { computed, reactive, ref, watch } from "vue";

defineOptions({ layout: PortalLayout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  request_categories: { type: Array, default: () => [] },
  service_catalog: { type: Array, default: () => [] },
  analysis_profiles: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
  matrixes: { type: Array, default: () => [] },
  packaging_categories: { type: Array, default: () => [] },
  warehouse: { type: Object, default: () => ({}) },
  query: { type: Object, default: () => ({}) },
  prefill: { type: Object, default: () => ({}) },
});

const isPanelOpen = ref(Boolean(props.prefill?.open_form));
const filters = reactive({
  search: props.query?.search || "",
  status_filter: props.query?.status_filter || "",
  request_type: props.query?.request_type || props.prefill?.request_type || "",
});

function buildDefaultDetails() {
  return {
    sample_name: "",
    matrix: "",
    matrix_id: null,
    product_name: "",
    product_id: null,
    lot: "",
    packaging: "",
    packaging_id: null,
    quantity: "",
    notes: "",
    collection_required: false,
    requested_profiles: [],
    samples: [],
    collection_location: "",
    collection_address: "",
    collection_contact_name: "",
    collection_contact_phone: "",
    preferred_time_window: "",
    items: [{ name: "", quantity: 1, lot: "" }],
    document_reference: "",
    document_type: "",
    invoice_reference: "",
    certificate_reference: "",
  };
}

function initialFormData(type = props.prefill?.request_type || props.service_catalog[0]?.type || "general_support") {
  return {
    request_type: type,
    title: props.prefill?.title || "",
    description: "",
    email: props.warehouse?.email || "",
    contact: props.warehouse?.primary_phone || props.warehouse?.alternative_phone || "",
    category_id: null,
    priority: "normal",
    preferred_date: "",
    details: buildDefaultDetails(),
  };
}

const form = useForm(initialFormData());
const requests = computed(() => props.record?.data || []);
const selectedService = computed(() => props.service_catalog.find((service) => service.type === form.request_type));
const totalRecords = computed(() => props.record?.meta?.total ?? requests.value.length);
const metrics = computed(() => [
  { label: "Pedidos", value: totalRecords.value, detail: "histórico da conta", icon: QueueListIcon },
  { label: "Pendentes", value: requests.value.filter((request) => request.status === "pending").length, detail: "nesta página", icon: ClockIcon },
  { label: "Em tratamento", value: requests.value.filter((request) => request.status === "in_progress").length, detail: "nesta página", icon: BeakerIcon },
  { label: "Concluídas", value: requests.value.filter((request) => request.status === "completed").length, detail: "nesta página", icon: CheckCircleIcon },
]);

const cleanFilters = computed(() => Object.fromEntries(
  Object.entries(filters).filter(([, value]) => value !== "" && value !== null && value !== undefined),
));
const exportUrl = computed(() => route("portal.request.export", cleanFilters.value));

watch(filters, debounce(() => {
  router.get(route("portal.requests.index"), cleanFilters.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}, 350), { deep: true });

function openRequestPanel(type = form.request_type) {
  form.request_type = type;

  if (!form.title) {
    form.title = props.service_catalog.find((service) => service.type === type)?.title || "";
  }

  form.clearErrors();
  isPanelOpen.value = true;
}

function closeRequestPanel() {
  isPanelOpen.value = false;
  form.clearErrors();
}

function resetFilters() {
  filters.search = "";
  filters.status_filter = "";
  filters.request_type = "";
}

function handleRequestTypeChange() {
  const items = form.details.items?.length ? form.details.items : [{ name: "", quantity: 1, lot: "" }];
  const samples = form.details.samples?.length ? form.details.samples : [];
  form.details = { ...buildDefaultDetails(), items, samples };
}

function submitRequest() {
  form.transform((data) => ({
    ...data,
    details: {
      ...data.details,
      items: (data.details.items || []).filter((item) => item.name || item.lot),
      samples: (data.details.samples || []).filter(isMeaningfulBatchSample),
    },
  })).post(route("portal.request.store"), {
    preserveScroll: true,
    onSuccess: () => {
      const defaults = initialFormData();
      form.defaults(defaults);
      form.reset();
      closeRequestPanel();
    },
  });
}

function isMeaningfulBatchSample(sample) {
  return Boolean(sample?.sample_name || sample?.product_name || sample?.matrix || sample?.lot || sample?.packaging || sample?.quantity || sample?.notes);
}

function statusLabel(status) {
  return { pending: "Pendente", in_progress: "Em tratamento", completed: "Concluída", cancelled: "Cancelada" }[status] || "Pendente";
}

function statusClass(status) {
  return {
    pending: "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20",
    in_progress: "bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20",
    completed: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20",
    cancelled: "bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20",
  }[status] || "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]";
}

function priorityLabel(priority) {
  return { low: "Baixa", normal: "Normal", high: "Alta" }[priority] || "Normal";
}

function typeLabel(type) {
  return {
    analysis_request: "Análises",
    collection_request: "Colheita",
    certificate_support: "Certificados",
    document_request: "Documentos",
    billing_support: "Facturação",
    general_support: "Suporte geral",
  }[type] || "Serviço";
}

function formatDate(value, includeTime = false) {
  if (!value) {
    return "Não definida";
  }

  return new Intl.DateTimeFormat("pt-PT", includeTime
    ? { dateStyle: "medium", timeStyle: "short" }
    : { dateStyle: "medium" }).format(new Date(value));
}

function responseTime(value) {
  if (!Number.isFinite(value)) {
    return "Aguarda resposta";
  }

  return value >= 24 ? `${Math.round(value / 24)} dia(s)` : `${value} hora(s)`;
}

function requestDetailLines(request) {
  const details = request.extra_data || {};
  const lines = [];

  if (details.sample_name) lines.push(`Amostra: ${details.sample_name}`);
  if (details.product_name) lines.push(`Produto: ${details.product_name}`);
  if (details.matrix) lines.push(`Matriz: ${details.matrix}`);
  if (details.lot) lines.push(`Lote: ${details.lot}`);
  if (details.document_type) lines.push(`Documento: ${details.document_type}`);
  if (details.document_reference) lines.push(`Referência: ${details.document_reference}`);
  if (details.invoice_reference) lines.push(`Facturação: ${details.invoice_reference}`);
  if (details.certificate_reference) lines.push(`Certificado: ${details.certificate_reference}`);
  if (details.collection_location) lines.push(`Local: ${details.collection_location}`);
  if (Array.isArray(details.requested_profiles) && details.requested_profiles.length) lines.push(`Perfis: ${details.requested_profiles.length}`);
  if (Number(details.sample_count) > 0) lines.push(`Amostras em lote: ${details.sample_count}`);
  if (Array.isArray(details.items) && details.items.length) lines.push(`Itens de colheita: ${details.items.length}`);

  return lines.slice(0, 6);
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <QueueListIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="ds-kicker">Central de pedidos</p>
              <h1 class="ds-heading mt-1 text-2xl">Pedidos do cliente</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Submeta necessidades estruturadas e acompanhe a triagem, execução e conclusão pela equipa do laboratório.</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <a :href="exportUrl" class="ds-button ds-button-secondary"><ArrowDownTrayIcon class="h-4 w-4" />Exportar CSV</a>
            <button type="button" class="ds-button ds-button-primary" @click="openRequestPanel()"><PlusIcon class="h-4 w-4" />Nova pedido</button>
          </div>
        </div>
      </div>
      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="bg-[var(--ds-panel)] p-5">
          <div class="flex items-start justify-between gap-3"><div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-3 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div><component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" /></div>
        </div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
      <aside class="space-y-6 self-start">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="text-sm font-bold text-[var(--ds-text)]">Serviços disponíveis</h2><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Inicie o formulario com o contexto certo.</p></header>
          <div class="divide-y divide-[var(--ds-border)]">
            <button v-for="service in service_catalog" :key="service.type" type="button" class="group block w-full px-5 py-4 text-left hover:bg-[var(--ds-panel-subtle)]" @click="openRequestPanel(service.type)">
              <span class="flex items-start justify-between gap-3"><span class="min-w-0"><span class="block text-sm font-bold text-[var(--ds-text)]">{{ service.title }}</span><span class="ds-copy mt-1 block text-xs">{{ service.description }}</span></span><PlusIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)] group-hover:text-[rgb(var(--primary-700-rgb))]" /></span>
            </button>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="flex items-start justify-between gap-3 border-b border-[var(--ds-border)] px-5 py-4"><div><h2 class="text-sm font-bold text-[var(--ds-text)]">Filtros</h2><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Refine o histórico.</p></div><button v-if="filters.search || filters.status_filter || filters.request_type" type="button" class="ds-icon-button" title="Limpar filtros" @click="resetFilters"><XMarkIcon class="h-4 w-4" /><span class="sr-only">Limpar filtros</span></button></header>
          <div class="space-y-4 px-5 py-5">
            <div class="ds-field-group"><label class="ds-field-label">Pesquisa</label><div class="relative"><MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" /><BaseInput v-model="filters.search" type="search" class="ds-field pl-10" placeholder="Referência ou título" /></div></div>
            <div class="ds-field-group"><label class="ds-field-label">Estado</label><BaseSelect v-model="filters.status_filter" class="ds-field"><option value="">Todos</option><option value="pending">Pendente</option><option value="in_progress">Em tratamento</option><option value="completed">Concluída</option><option value="cancelled">Cancelada</option></BaseSelect></div>
            <div class="ds-field-group"><label class="ds-field-label">Tipo</label><BaseSelect v-model="filters.request_type" class="ds-field"><option value="">Todos</option><option v-for="service in service_catalog" :key="service.type" :value="service.type">{{ service.title }}</option></BaseSelect></div>
          </div>
        </section>
      </aside>

      <section class="ds-card min-w-0 overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:gap-4 sm:px-6"><div><h2 class="text-base font-bold text-[var(--ds-text)]">Registo de pedidos</h2><p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Estado, prioridade, contacto e dados técnicos de cada pedido.</p></div><span class="ds-chip mt-3 sm:mt-0">{{ totalRecords }} registo(s)</span></header>

        <div v-if="requests.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="request in requests" :key="request.id" class="px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-bold text-[var(--ds-text-muted)]">{{ request.reference || `REQ-${request.id}` }}</span><span :class="['ds-chip', statusClass(request.status)]">{{ statusLabel(request.status) }}</span><span class="ds-chip">{{ typeLabel(request.request_type) }}</span><span class="ds-chip">Prioridade {{ priorityLabel(request.priority) }}</span></div>
                <h3 class="mt-3 break-words text-base font-bold text-[var(--ds-text)]">{{ request.title || "Pedido sem título" }}</h3>
                <p class="ds-copy mt-1 max-w-3xl text-sm">{{ request.description }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Link v-if="request.status !== 'completed'" :href="route('portal.request.markAsDone', { id: request.id })" class="ds-button ds-button-secondary"><CheckCircleIcon class="h-4 w-4" />Concluir</Link>
                <Link v-if="request.status !== 'cancelled'" :href="route('portal.request.destroy', { id: request.id })" class="ds-button ds-button-secondary text-rose-700 dark:text-rose-200"><XMarkIcon class="h-4 w-4" />Cancelar</Link>
              </div>
            </div>

            <dl class="mt-5 grid gap-x-6 gap-y-4 border-t border-[var(--ds-border)] pt-4 sm:grid-cols-2 xl:grid-cols-4">
              <div><dt class="ds-field-label">Submetido</dt><dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(request.submitted_at || request.created_at, true) }}</dd></div>
              <div><dt class="ds-field-label">Data preferencial</dt><dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(request.preferred_date) }}</dd></div>
              <div><dt class="ds-field-label">Contacto</dt><dd class="mt-1.5 break-words text-sm font-bold text-[var(--ds-text)]">{{ request.contact || request.email || "Não indicado" }}</dd></div>
              <div><dt class="ds-field-label">Tempo de resposta</dt><dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ responseTime(request.response_time) }}</dd></div>
            </dl>

            <div v-if="requestDetailLines(request).length" class="mt-4 border-l-2 border-[rgb(var(--primary-300-rgb))] pl-4"><p class="ds-field-label">Dados técnicos</p><ul class="mt-2 grid gap-x-5 gap-y-1 text-sm font-semibold text-[var(--ds-text-muted)] sm:grid-cols-2"><li v-for="line in requestDetailLines(request)" :key="line">{{ line }}</li></ul></div>
          </article>
        </div>
        <div v-else class="ds-empty-state m-5 py-12 text-center sm:m-6"><DocumentTextIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" /><h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem pedidos encontrados</h3><p class="ds-copy mx-auto mt-1 max-w-md text-sm">Ajuste os filtros ou registe uma nova pedido.</p><button type="button" class="ds-button ds-button-primary mt-5" @click="openRequestPanel()"><PlusIcon class="h-4 w-4" />Nova pedido</button></div>
        <Pagination v-if="record.meta" v-bind="record.meta" />
      </section>
    </div>

    <SlideOver v-if="isPanelOpen" title="Nova pedido" :description="selectedService?.description || 'Descreva a necessidade para triagem pela equipa do laboratório.'" @close="closeRequestPanel">
      <template #content>
        <form id="portal-request-form" @submit.prevent="submitRequest">
          <div v-if="form.errors.duplicate_submission" class="m-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 sm:m-6 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200">{{ form.errors.duplicate_submission }}</div>
          <PortalRequestForm :form="form" :services="service_catalog" :categories="request_categories" :profiles="analysis_profiles" :products="products" :matrixes="matrixes" :packaging-categories="packaging_categories" :warehouse="warehouse" @request-type-change="handleRequestTypeChange" />
        </form>
      </template>
      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" class="ds-button ds-button-secondary" @click="closeRequestPanel">Cancelar</button><button type="submit" form="portal-request-form" class="ds-button ds-button-primary" :disabled="form.processing">{{ form.processing ? "A submeter..." : "Submeter pedido" }}</button></div>
      </template>
    </SlideOver>
  </div>
</template>
