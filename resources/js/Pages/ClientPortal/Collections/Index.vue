<script setup>
import Pagination from "@/Components/pagination.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  BeakerIcon,
  CheckBadgeIcon,
  ClockIcon,
  DocumentArrowDownIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  TruckIcon,
} from "@heroicons/vue/24/outline";
import debounce from "lodash/debounce";
import { computed, reactive, watch } from "vue";

defineOptions({ layout: PortalLayout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
  summary: { type: Object, default: () => ({}) },
});

const filters = reactive({
  search: props.query?.search || "",
  status_filter: props.query?.status_filter || "",
  type_filter: props.query?.type_filter || "",
  date_filter: props.query?.date_filter || "all",
});

const collections = computed(() => props.record?.data || []);
const metrics = computed(() => [
  { label: "Colheitas", value: props.summary.total || 0, detail: "histórico da conta", icon: TruckIcon },
  { label: "Últimos 30 dias", value: props.summary.recent || 0, detail: "actividade recente", icon: ClockIcon },
  { label: "Certificado emitido", value: props.summary.certificate_ready || 0, detail: "pronto para consulta", icon: CheckBadgeIcon },
  { label: "Em análise", value: props.summary.in_progress || 0, detail: `${props.summary.analysis_pending || 0} em fila`, icon: BeakerIcon },
]);
const exportUrl = computed(() => {
  const query = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value && value !== "all") {
      query.set(key, value);
    }
  });

  return `${route("portal.collections.export")}?${query.toString()}`;
});

watch(filters, debounce((value) => {
  router.get(route("portal.collections"), value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}, 350), { deep: true });

function formatDate(value) {
  return value ? new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium" }).format(new Date(value)) : "Não definida";
}

function trackingClass(status) {
  return {
    certificate_ready: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20",
    analysis_completed: "bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20",
    analysis_in_progress: "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20",
    analysis_queued: "bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-200 dark:ring-violet-400/20",
    sample_registered: "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]",
    awaiting_collection: "bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20",
  }[status] || "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]";
}

function collectionTypeLabel(type) {
  return type === "programmed" ? "Programada" : type === "direct" ? "Directa" : type || "Não definido";
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><TruckIcon class="h-5 w-5" /></span><div><p class="ds-kicker">Rastreabilidade de amostras</p><h1 class="ds-heading mt-1 text-2xl">Colheitas e análises</h1><p class="ds-copy mt-1 max-w-3xl text-sm">Acompanhe recolha, entrada, execução analítica e disponibilidade do certificado.</p></div></div>
          <div class="flex flex-wrap gap-2"><a :href="exportUrl" class="ds-button ds-button-secondary"><ArrowDownTrayIcon class="h-4 w-4" />Exportar folha</a><Link :href="route('portal.requests.index', { new: 1, request_type: 'collection_request' })" class="ds-button ds-button-primary"><PlusIcon class="h-4 w-4" />Solicitar colheita</Link></div>
        </div>
      </div>
      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4"><div v-for="metric in metrics" :key="metric.label" class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-3 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div><component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div></dl>
    </section>

    <section class="ds-card overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="text-base font-bold text-[var(--ds-text)]">Pesquisa e filtros</h2><p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Encontre uma colheita por código, produto, marca ou lote.</p></header>
      <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-4">
        <div class="ds-field-group sm:col-span-2 xl:col-span-1"><label class="ds-field-label">Pesquisa</label><div class="relative"><MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" /><BaseInput v-model="filters.search" type="search" class="ds-field pl-10" placeholder="Código, produto, marca ou lote" /></div></div>
        <div class="ds-field-group"><label class="ds-field-label">Estado</label><BaseSelect v-model="filters.status_filter" class="ds-field"><option value="">Todos</option><option value="certificate_ready">Certificado emitido</option><option value="analysis_pending">Aguarda análise</option><option value="in_progress">Em análise</option></BaseSelect></div>
        <div class="ds-field-group"><label class="ds-field-label">Tipo</label><BaseSelect v-model="filters.type_filter" class="ds-field"><option value="">Todos</option><option value="direct">Directa</option><option value="programmed">Programada</option></BaseSelect></div>
        <div class="ds-field-group"><label class="ds-field-label">Período</label><BaseSelect v-model="filters.date_filter" class="ds-field"><option value="all">Todo o histórico</option><option value="last_week">Última semana</option><option value="last_month">Último mes</option><option value="last_quarter">Último trimestre</option></BaseSelect></div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:px-6"><div><h2 class="text-base font-bold text-[var(--ds-text)]">Acompanhamento operacional</h2><p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Estado mais recente por colheita.</p></div><span class="ds-chip mt-3 sm:mt-0">{{ record.meta?.total || collections.length }} registo(s)</span></header>

      <div v-if="collections.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="collection in collections" :key="collection.id" class="px-5 py-5 sm:px-6">
          <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2"><h3 class="break-words font-mono text-sm font-bold text-[var(--ds-text)]">{{ collection.cl || `#${collection.id}` }}</h3><span :class="['ds-chip', trackingClass(collection.tracking?.status)]">{{ collection.tracking?.label || "Pendente" }}</span><span class="ds-chip">{{ collectionTypeLabel(collection.type) }}</span></div>
              <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="ds-field-label">Produto</dt><dd class="mt-1.5 break-words text-sm font-bold text-[var(--ds-text)]">{{ collection.product || "Não definido" }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ collection.comercial_brand || "Sem marca" }}</p></div>
                <div><dt class="ds-field-label">Lote / quantidade</dt><dd class="mt-1.5 break-words text-sm font-bold text-[var(--ds-text)]">{{ collection.lot || "Sem lote" }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ collection.qty || 0 }} unidade(s)</p></div>
                <div><dt class="ds-field-label">Data de colheita</dt><dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(collection.collection_date) }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ collection.tracking?.total_samples || 0 }} amostra(s)</p></div>
                <div><dt class="ds-field-label">Progresso analítico</dt><dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ collection.tracking?.completed_analysis || 0 }} concluída(s)</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ collection.tracking?.pending_analysis || 0 }} pendente(s) / {{ collection.tracking?.in_progress_analysis || 0 }} em curso</p></div>
              </dl>
            </div>
            <div class="flex flex-wrap gap-2 xl:w-64 xl:justify-end">
              <a v-if="collection.links?.pdf_quality_certificate" :href="collection.links.pdf_quality_certificate" target="_blank" rel="noopener" class="ds-button ds-button-secondary"><CheckBadgeIcon class="h-4 w-4" />Certificado</a>
              <a v-if="collection.links?.pdf_path" :href="collection.links.pdf_path" target="_blank" rel="noopener" class="ds-button ds-button-secondary"><DocumentArrowDownIcon class="h-4 w-4" />PDF analítico</a>
              <a v-if="collection.links?.xlsx_path" :href="collection.links.xlsx_path" class="ds-button ds-button-secondary"><ArrowDownTrayIcon class="h-4 w-4" />XLSX</a>
              <Link :href="route('portal.requests.index', { new: 1, request_type: 'certificate_support', title: `Seguimento ${collection.cl || ''}`.trim() })" class="ds-button ds-button-secondary">Pedir apoio</Link>
            </div>
          </div>
        </article>
      </div>
      <div v-else class="ds-empty-state m-5 py-12 text-center sm:m-6"><TruckIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" /><h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem colheitas encontradas</h3><p class="ds-copy mx-auto mt-1 max-w-md text-sm">Ajuste os filtros ou solicite uma nova colheita.</p></div>
      <Pagination v-if="record.meta" v-bind="record.meta" />
    </section>
  </div>
</template>
