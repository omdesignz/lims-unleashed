<script setup>
import BaseInput from "@/Components/base/BaseInput.vue";
import Pagination from "@/Components/pagination.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Download as ArrowDownTrayIcon,
  FlaskConical as BeakerIcon,
  BadgeCheck as CheckBadgeIcon,
  ClipboardList as ClipboardDocumentListIcon,
  Funnel as FunnelIcon,
  Search as MagnifyingGlassIcon,
  ShieldCheck as ShieldCheckIcon,
  X as XMarkIcon,
} from "@lucide/vue";
import { computed, reactive } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  records: {
    type: Object,
    default: () => ({ data: [], total: 0 }),
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  departments: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  canViewPending: Boolean,
  canViewAudit: Boolean,
});

const filterState = reactive({
  view: props.filters.view || (props.canViewPending ? "pending" : "audit"),
  stage: props.filters.stage || "all",
  department_id: props.filters.department_id ? String(props.filters.department_id) : "",
  date_from: props.filters.date_from || "",
  date_to: props.filters.date_to || "",
  search: props.filters.search || "",
  per_page: Number(props.filters.per_page || 25),
});

const isAudit = computed(() => filterState.view === "audit");

const exportUrl = computed(() => route("analysis.data-exports.download", {
  view: filterState.view,
  stage: filterState.stage,
  department_id: filterState.department_id || undefined,
  date_from: filterState.date_from || undefined,
  date_to: filterState.date_to || undefined,
  search: filterState.search || undefined,
  per_page: filterState.per_page,
}));

const metrics = computed(() => {
  if (isAudit.value) {
    return [
      { label: "Resultados registados", value: props.summary.total || 0, note: "histórico filtrado" },
      { label: "Inseridos", value: props.summary.inserted || 0, note: "aguardam verificação" },
      { label: "Verificados", value: props.summary.verified || 0, note: "aguardam aprovação" },
      { label: "Aprovados", value: props.summary.approved || 0, note: "decisão final" },
    ];
  }

  return [
    { label: "Trabalho pendente", value: props.summary.tasks || 0, note: "parâmetros por processar" },
    { label: "Amostras", value: props.summary.samples || 0, note: "na fila analítica" },
    { label: "Análises", value: props.summary.analyses || 0, note: "perfis em curso" },
    { label: "Departamentos", value: props.summary.departments || 0, note: "com trabalho pendente" },
  ];
});

function queryPayload(overrides = {}) {
  return {
    view: filterState.view,
    stage: filterState.stage,
    department_id: filterState.department_id || undefined,
    date_from: filterState.date_from || undefined,
    date_to: filterState.date_to || undefined,
    search: filterState.search || undefined,
    per_page: filterState.per_page,
    page: 1,
    ...overrides,
  };
}

function visit(payload) {
  router.get(route("analysis.data-exports.index"), payload, {
    preserveScroll: true,
    preserveState: false,
    replace: true,
  });
}

function changeView(view) {
  filterState.view = view;
  filterState.stage = "all";
  visit(queryPayload({ view, stage: "all" }));
}

function applyFilters() {
  visit(queryPayload());
}

function clearFilters() {
  filterState.stage = "all";
  filterState.department_id = "";
  filterState.date_from = "";
  filterState.date_to = "";
  filterState.search = "";
  visit(queryPayload());
}

function stageClass(stage) {
  return {
    inserted: "lims-status-dot-hold",
    verified: "lims-status-dot-instrument",
    approved: "lims-status-dot-release",
  }[stage] || "lims-status-dot-neutral";
}

function valueOrDash(value) {
  return value === null || value === undefined || value === "" ? "-" : value;
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Operação e rastreabilidade</p>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="isAudit ? 'lims-status-dot-release' : 'lims-status-dot-hold'" />
              {{ isAudit ? "Registo controlado" : "Fila diária" }}
            </span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">Dados laboratoriais</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            {{ isAudit
              ? "Resultados inseridos, verificados e aprovados com valores, responsáveis e datas de decisão."
              : "Amostras e parâmetros ainda por processar, organizados para execução na bancada." }}
          </p>
        </div>

        <a :href="exportUrl" class="ds-button ds-button-primary shrink-0">
          <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
          Exportar XLSX
        </a>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="min-w-0 border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 text-xl tabular-nums">{{ metric.value }}</dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.note }}</p>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-4 sm:px-5">
        <nav class="-mb-px grid grid-cols-2 gap-0 sm:flex sm:gap-6 sm:overflow-x-auto" aria-label="Conjuntos de dados laboratoriais">
          <button
            v-if="canViewPending"
            type="button"
            :class="[
              'flex min-w-0 items-center justify-center gap-2 border-b-2 px-2 py-4 text-center text-xs font-bold leading-5 transition sm:shrink-0 sm:justify-start sm:px-1 sm:text-sm',
              !isAudit
                ? 'border-[rgb(var(--primary-600-rgb))] text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-300-rgb))]'
                : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]',
            ]"
            @click="changeView('pending')"
          >
            <ClipboardDocumentListIcon class="h-4 w-4 shrink-0" />
            Folha de análises pendentes
          </button>
          <button
            v-if="canViewAudit"
            type="button"
            :class="[
              'flex min-w-0 items-center justify-center gap-2 border-b-2 px-2 py-4 text-center text-xs font-bold leading-5 transition sm:shrink-0 sm:justify-start sm:px-1 sm:text-sm',
              isAudit
                ? 'border-[rgb(var(--primary-600-rgb))] text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-300-rgb))]'
                : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]',
            ]"
            @click="changeView('audit')"
          >
            <ShieldCheckIcon class="h-4 w-4 shrink-0" />
            Auditoria de resultados
          </button>
        </nav>
      </div>

      <form class="border-b border-[var(--ds-border)] p-5" @submit.prevent="applyFilters">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
          <label class="block xl:col-span-2">
            <span class="ds-field-label">Pesquisa</span>
            <span class="relative mt-1 block">
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput
                v-model="filterState.search"
                type="search"
                class="ds-field w-full pl-9"
                placeholder="Amostra, código, cliente ou parâmetro"
              />
            </span>
          </label>

          <label class="block">
            <span class="ds-field-label">Departamento</span>
            <BaseSelect v-model="filterState.department_id" class="ds-field mt-1 w-full">
              <option value="">Todos</option>
              <option v-for="department in departments" :key="department.value" :value="String(department.value)">
                {{ department.label }}
              </option>
            </BaseSelect>
          </label>

          <label v-if="isAudit" class="block">
            <span class="ds-field-label">Estado</span>
            <BaseSelect v-model="filterState.stage" class="ds-field mt-1 w-full">
              <option value="all">Todos os estados</option>
              <option value="inserted">Inseridos</option>
              <option value="verified">Verificados</option>
              <option value="approved">Aprovados</option>
            </BaseSelect>
          </label>

          <label class="block">
            <span class="ds-field-label">Desde</span>
            <BaseInput v-model="filterState.date_from" type="date" class="ds-field mt-1 w-full" />
          </label>

          <label class="block">
            <span class="ds-field-label">Até</span>
            <BaseInput v-model="filterState.date_to" type="date" class="ds-field mt-1 w-full" />
          </label>

          <label class="block" :class="isAudit ? '' : 'xl:col-start-6'">
            <span class="ds-field-label">Linhas</span>
            <BaseSelect v-model.number="filterState.per_page" class="ds-field mt-1 w-full">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </BaseSelect>
          </label>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
          <span class="ds-chip">
            <FunnelIcon class="h-4 w-4" />
            {{ records.total || 0 }} linhas no conjunto actual
          </span>
          <div class="flex items-center gap-2">
            <button type="button" class="ds-icon-button" title="Limpar filtros" @click="clearFilters">
              <XMarkIcon class="h-4 w-4" />
              <span class="sr-only">Limpar filtros</span>
            </button>
            <button type="submit" class="ds-button ds-button-secondary">
              <FunnelIcon class="h-4 w-4" />
              Aplicar filtros
            </button>
          </div>
        </div>
      </form>

      <div class="overflow-x-auto">
        <DataTable v-if="records.data?.length" class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
          <thead class="bg-[var(--ds-panel-subtle)]">
            <tr v-if="!isAudit">
              <th class="ds-table-heading px-5 py-3">Amostra</th>
              <th class="ds-table-heading px-5 py-3">Cliente / produto</th>
              <th class="ds-table-heading px-5 py-3">Análise</th>
              <th class="ds-table-heading px-5 py-3">Método</th>
              <th class="ds-table-heading px-5 py-3">Departamento</th>
              <th class="ds-table-heading px-5 py-3">Entrada</th>
              <th class="ds-table-heading px-5 py-3 text-right">Acção</th>
            </tr>
            <tr v-else>
              <th class="ds-table-heading px-5 py-3">Resultado</th>
              <th class="ds-table-heading px-5 py-3">Amostra</th>
              <th class="ds-table-heading px-5 py-3">Análise</th>
              <th class="ds-table-heading px-5 py-3">Inserção</th>
              <th class="ds-table-heading px-5 py-3">Verificação</th>
              <th class="ds-table-heading px-5 py-3">Aprovação</th>
              <th class="ds-table-heading px-5 py-3">Estado</th>
            </tr>
          </thead>

          <tbody v-if="!isAudit" class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel)]">
            <tr v-for="row in records.data" :key="`${row.analysis_id}-${row.parameter_id}`" class="align-top hover:bg-[var(--ds-panel-subtle)]">
              <td class="px-5 py-4">
                <p class="font-mono text-xs font-bold text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]">{{ valueOrDash(row.laboratory_code) }}</p>
                <p class="mt-1 font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.sample_code) }}</p>
                <Link v-if="row.sample_entry_url" :href="row.sample_entry_url" class="mt-1 block text-xs font-semibold text-[rgb(var(--primary-700-rgb))] hover:underline">
                  {{ valueOrDash(row.sample_entry_code) }}
                </Link>
              </td>
              <td class="px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.customer) }}</p>
                <p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ valueOrDash(row.product) }}</p>
              </td>
              <td class="min-w-64 px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.parameter) }}</p>
                <p class="mt-1 font-mono text-xs text-[var(--ds-text-muted)]">{{ valueOrDash(row.parameter_code) }} · {{ valueOrDash(row.profile) }}</p>
                <p v-if="row.optimal_analysis_time" class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-300">Tempo óptimo: {{ row.optimal_analysis_time }}</p>
              </td>
              <td class="min-w-56 px-5 py-4 text-xs text-[var(--ds-text-muted)]">
                <p><strong class="text-[var(--ds-text)]">Protocolo:</strong> {{ valueOrDash(row.protocol) }}</p>
                <p class="mt-1"><strong class="text-[var(--ds-text)]">PNT:</strong> {{ valueOrDash(row.nwp) }}</p>
                <p class="mt-1"><strong class="text-[var(--ds-text)]">Unidade:</strong> {{ valueOrDash(row.unit) }}</p>
              </td>
              <td class="px-5 py-4 font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.department) }}</td>
              <td class="px-5 py-4 text-[var(--ds-text-muted)]">{{ valueOrDash(row.work_date) }}</td>
              <td class="px-5 py-4 text-right">
                <Link :href="row.analysis_url" class="ds-button ds-button-secondary whitespace-nowrap">
                  <BeakerIcon class="h-4 w-4" />
                  Abrir análise
                </Link>
              </td>
            </tr>
          </tbody>

          <tbody v-else class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel)]">
            <tr v-for="row in records.data" :key="row.result_id" class="align-top hover:bg-[var(--ds-panel-subtle)]">
              <td class="px-5 py-4">
                <p class="font-mono text-xs font-bold text-[var(--ds-text)]">#{{ row.result_id }}</p>
                <p class="mt-1 font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.effective_value) }}</p>
                <p v-if="row.uncertainty_value" class="mt-1 text-xs text-[var(--ds-text-muted)]">± {{ row.uncertainty_value }}</p>
              </td>
              <td class="px-5 py-4">
                <p class="font-mono text-xs font-bold text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]">{{ valueOrDash(row.laboratory_code) }}</p>
                <p class="mt-1 font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.sample_code) }}</p>
                <Link v-if="row.sample_entry_url" :href="row.sample_entry_url" class="mt-1 block text-xs font-semibold text-[rgb(var(--primary-700-rgb))] hover:underline">
                  {{ valueOrDash(row.sample_entry_code) }}
                </Link>
              </td>
              <td class="min-w-64 px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.parameter) }}</p>
                <p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ valueOrDash(row.product) }} · {{ valueOrDash(row.department) }}</p>
                <p class="mt-1 font-mono text-xs text-[var(--ds-text-soft)]">{{ valueOrDash(row.protocol) }} · {{ valueOrDash(row.unit) }}</p>
              </td>
              <td class="min-w-44 px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.inserted_value) }}</p>
                <p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ valueOrDash(row.inserted_by) }}</p>
                <p class="mt-1 text-xs tabular-nums text-[var(--ds-text-soft)]">{{ valueOrDash(row.inserted_date_display) }}</p>
              </td>
              <td class="min-w-44 px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.verified_value) }}</p>
                <p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ valueOrDash(row.verified_by) }}</p>
                <p class="mt-1 text-xs tabular-nums text-[var(--ds-text-soft)]">{{ valueOrDash(row.verified_date_display) }}</p>
              </td>
              <td class="min-w-44 px-5 py-4">
                <p class="font-semibold text-[var(--ds-text)]">{{ valueOrDash(row.approved_value) }}</p>
                <p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ valueOrDash(row.approved_by) }}</p>
                <p class="mt-1 text-xs tabular-nums text-[var(--ds-text-soft)]">{{ valueOrDash(row.approved_date_display) }}</p>
              </td>
              <td class="px-5 py-4">
                <span class="ds-chip whitespace-nowrap">
                  <span class="lims-status-dot" :class="stageClass(row.stage)" />
                  {{ row.stage_label }}
                </span>
                <p class="mt-2 text-xs tabular-nums text-[var(--ds-text-soft)]">{{ valueOrDash(row.effective_date) }}</p>
              </td>
            </tr>
          </tbody>
        </DataTable>

        <div v-else class="px-6 py-16 text-center">
          <component :is="isAudit ? ShieldCheckIcon : ClipboardDocumentListIcon" class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h2 class="ds-heading mt-4 text-base">Nenhum registo encontrado</h2>
          <p class="ds-copy mt-1 text-sm">O conjunto actual não contém linhas para os filtros aplicados.</p>
        </div>
      </div>

      <div v-if="records.data?.length" class="border-t border-[var(--ds-border)]">
        <Pagination v-bind="records" />
      </div>
    </section>

    <section v-if="isAudit" class="lims-status-strip p-4">
      <div class="flex items-start gap-3">
        <CheckBadgeIcon class="h-5 w-5 shrink-0 text-[var(--lims-release)]" />
        <div>
          <h2 class="ds-heading text-sm">Registo de auditoria somente leitura</h2>
          <p class="ds-copy mt-1 text-xs">Os valores e responsáveis apresentados correspondem às etapas persistidas no fluxo laboratorial.</p>
        </div>
      </div>
    </section>
  </div>
</template>
