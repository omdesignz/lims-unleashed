<script setup>
import Pagination from "@/Components/pagination.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
  Download as ArrowDownTrayIcon,
  CalendarDays as CalendarDaysIcon,
  BadgeCheck as CheckBadgeIcon,
  Clock as ClockIcon,
  CircleDollarSign as CurrencyDollarIcon,
  FileText as DocumentTextIcon,
  Search as MagnifyingGlassIcon,
  Plus as PlusIcon,
  X as XMarkIcon,
} from "@lucide/vue";
import { computed, ref, watch } from "vue";

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
  title: { type: String, required: true },
  kicker: { type: String, default: "Documentos do cliente" },
  description: { type: String, required: true },
  entityLabel: { type: String, default: "documento" },
  icon: { type: [Object, Function], required: true },
  downloadRoute: { type: String, required: true },
  supportType: { type: String, default: "document_request" },
  supportTitle: { type: String, required: true },
  mapRecord: { type: Function, required: true },
  valueMetricLabel: { type: String, default: "Valor nesta página" },
  valueMetricDetail: { type: String, default: "soma dos documentos visíveis" },
});

const page = usePage();
const search = ref(props.query?.search || "");
const pageRecords = computed(() => props.record?.data || []);
const documents = computed(() => pageRecords.value.map((record) => props.mapRecord(record)));
const totalRecords = computed(() => props.record?.meta?.total ?? documents.value.length);
const recentRecords = computed(() => {
  const threshold = new Date();
  threshold.setDate(threshold.getDate() - 30);

  return documents.value.filter((document) => document.date && new Date(document.date) >= threshold).length;
});
const attentionRecords = computed(() => documents.value.filter((document) => ["warning", "danger"].includes(document.tone)).length);
const pageValue = computed(() => documents.value.reduce((total, document) => total + Number(document.total || 0), 0));
const hasMonetaryValues = computed(() => documents.value.some((document) => document.total !== null && document.total !== undefined));

const metrics = computed(() => [
  { label: "Total", value: totalRecords.value, detail: `${props.entityLabel}(s) no histórico`, icon: DocumentTextIcon },
  { label: "Últimos 30 dias", value: recentRecords.value, detail: "emissoes recentes nesta página", icon: CalendarDaysIcon },
  { label: "Requer atencao", value: attentionRecords.value, detail: "pendente, expirado ou em revisão", icon: ClockIcon },
  hasMonetaryValues.value
    ? { label: props.valueMetricLabel, value: formatCurrency(pageValue.value), detail: props.valueMetricDetail, icon: CurrencyDollarIcon }
    : { label: "Disponíveis", value: documents.value.length - attentionRecords.value, detail: "prontos para consulta", icon: CheckBadgeIcon },
]);

watch(() => props.query?.search, (value) => {
  search.value = value || "";
});

function submitSearch() {
  const query = { ...props.query, search: search.value.trim() || undefined };
  delete query.page;

  router.get(page.url.split("?")[0], query, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  });
}

function clearSearch() {
  search.value = "";
  submitSearch();
}

function downloadUrl(document) {
  return route(props.downloadRoute, { id: document.id });
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "AOA",
    minimumFractionDigits: 2,
  }).format(Number(value || 0));
}

function formatDate(value) {
  if (!value) {
    return "Data não definida";
  }

  return new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium" }).format(new Date(value));
}

function toneClass(tone) {
  return {
    success: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20",
    warning: "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20",
    danger: "bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20",
    info: "bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20",
  }[tone] || "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]";
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="title" :lede="description">
      <template #actions>
        <Link :href="route('portal.requests.index', { request_type: supportType, new: 1, title: supportTitle })" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Pedir apoio
        </Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>

    <section class="ds-card overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-center sm:justify-between sm:gap-4 sm:px-6">
        <div>
          <h2 class="text-base font-bold text-[var(--ds-text)]">Biblioteca</h2>
          <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Pesquise pela referência e abra o PDF oficial.</p>
        </div>

        <form class="mt-4 flex min-w-0 gap-2 sm:mt-0 sm:w-full sm:max-w-md" role="search" @submit.prevent="submitSearch">
          <div class="relative min-w-0 flex-1">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" />
            <BaseInput v-model="search" type="search" class="ds-field pl-10" :placeholder="`Pesquisar ${entityLabel}`" />
          </div>
          <button type="submit" class="ds-button ds-button-secondary">Pesquisar</button>
          <button v-if="search" type="button" class="ds-icon-button" title="Limpar pesquisa" @click="clearSearch">
            <XMarkIcon class="h-4 w-4" />
            <span class="sr-only">Limpar pesquisa</span>
          </button>
        </form>
      </header>

      <div v-if="documents.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="document in documents" :key="document.id" class="px-5 py-5 sm:px-6">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
              <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]">
                <component :is="icon" class="h-5 w-5" />
              </span>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="break-words font-mono text-sm font-bold text-[var(--ds-text)]">{{ document.reference || `#${document.id}` }}</h3>
                  <span :class="['ds-chip', toneClass(document.tone)]">{{ document.status || "Disponível" }}</span>
                </div>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ formatDate(document.date) }}</p>
                <p v-if="document.description" class="mt-2 break-words text-sm font-semibold text-[var(--ds-text-muted)]">{{ document.description }}</p>
              </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:justify-end">
              <dl v-if="document.details?.length" class="grid min-w-0 gap-x-5 gap-y-2 sm:grid-cols-2">
                <div v-for="detail in document.details" :key="detail.label" class="min-w-0">
                  <dt class="text-[0.65rem] font-bold uppercase text-[var(--ds-text-soft)]">{{ detail.label }}</dt>
                  <dd class="mt-0.5 break-words text-xs font-bold text-[var(--ds-text)]">{{ detail.value || "Não definido" }}</dd>
                </div>
              </dl>
              <div v-if="document.total !== null && document.total !== undefined" class="sm:min-w-32 sm:text-right">
                <p class="text-[0.65rem] font-bold uppercase text-[var(--ds-text-soft)]">Valor</p>
                <p class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ formatCurrency(document.total) }}</p>
              </div>
              <a :href="downloadUrl(document)" target="_blank" rel="noopener" class="ds-button ds-button-secondary shrink-0">
                <ArrowDownTrayIcon class="h-4 w-4" />
                PDF
              </a>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="ds-empty-state m-5 py-12 text-center sm:m-6">
        <component :is="icon" class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
        <h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum {{ entityLabel }} encontrado</h3>
        <p class="ds-copy mx-auto mt-1 max-w-md text-sm">Ajuste a pesquisa ou abra um pedido para obter apoio documental.</p>
      </div>

      <Pagination v-if="record?.meta" v-bind="record.meta" />
    </section>
  </div>
</template>
