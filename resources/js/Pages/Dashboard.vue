<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-badge ds-badge-info">
              <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
              Operação laboratorial
            </span>
            <span class="ds-badge ds-badge-neutral">ISO/IEC 17025</span>
          </div>
          <h1 class="ds-heading mt-4 text-2xl sm:text-3xl">Visão geral do laboratório</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            Acompanhe a carga técnica, a liberação de resultados, o controlo documental e os sinais de qualidade que exigem decisão.
          </p>
        </div>

        <div class="flex min-w-0 items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 lg:w-64">
          <UserCircleIcon class="h-6 w-6 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Sessão ativa</p>
            <p class="mt-1 truncate text-sm font-bold text-[var(--ds-text)]">{{ $page?.props?.auth?.user?.name || "Utilizador" }}</p>
          </div>
        </div>
      </div>

      <nav class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4" aria-label="Ações principais">
        <a
          v-for="action in dashboardPrimaryActions"
          :key="action.label"
          :href="action.href"
          class="group flex min-h-28 items-start gap-3 border-b border-[var(--ds-border)] p-4 transition-colors hover:bg-[var(--ds-panel-subtle)] sm:border-r xl:border-b-0 xl:last:border-r-0"
        >
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <component :is="action.icon" class="h-4 w-4" />
          </span>
          <span class="min-w-0 flex-1">
            <span class="flex items-start justify-between gap-3">
              <span class="text-sm font-bold text-[var(--ds-text)]">{{ action.label }}</span>
              <ChevronRightIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)] transition-transform group-hover:translate-x-0.5" />
            </span>
            <span class="mt-1 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ action.hint }}</span>
          </span>
        </a>
      </nav>

      <dl class="grid bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div
          v-for="signal in dashboardSignalCards"
          :key="signal.label"
          class="flex items-start justify-between gap-4 border-t border-[var(--ds-border)] px-5 py-4 sm:border-r sm:last:border-r-0"
        >
          <div>
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ signal.label }}</dt>
            <dd class="mt-1 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ signal.value }}</dd>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ signal.hint }}</p>
          </div>
          <span :class="['mt-1 h-2 w-2 shrink-0 rounded-full', signal.dot]" aria-hidden="true" />
        </div>
      </dl>
    </section>

    <section class="ds-panel p-5 sm:p-6">
      <div>
        <p class="ds-kicker">Pulso operacional</p>
        <h2 class="ds-heading mt-2 text-lg">Registos no sistema</h2>
      </div>
      <quick-stats :stats="props.stats" />
    </section>

    <section class="ds-panel p-5 sm:p-6">
      <div>
        <p class="ds-kicker">Navegação operacional</p>
        <h2 class="ds-heading mt-2 text-lg">Módulos do laboratório</h2>
      </div>
      <quick-menu :default-open="false" />
    </section>

    <template v-if="props.executive">
      <section class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
          <div class="max-w-3xl">
            <p class="ds-kicker">Gestão executiva</p>
            <h2 class="ds-heading mt-2 text-xl">Controlo de desempenho e risco</h2>
            <p class="ds-copy mt-2 text-sm">Indicadores consolidados de operação, procurement, fornecedores e conformidade.</p>
          </div>
          <div class="flex flex-wrap gap-3">
            <a :href="exportUrl('pdf')" class="ds-button ds-button-primary">
              <ArrowDownTrayIcon class="h-4 w-4" />
              PDF executivo
            </a>
            <a :href="exportUrl('csv')" class="ds-button ds-button-secondary">
              <TableCellsIcon class="h-4 w-4" />
              Exportar CSV
            </a>
          </div>
        </div>
        <dl class="grid border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
          <div class="px-5 py-4 sm:border-r sm:border-[var(--ds-border)]">
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Âmbito</dt>
            <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">Operações, qualidade e compras</dd>
          </div>
          <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-r sm:border-t-0">
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Horizonte</dt>
            <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">Últimos 6 meses e risco atual</dd>
          </div>
          <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0">
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Referencial</dt>
            <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">Direção e coordenação técnica</dd>
          </div>
        </dl>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Indicadores críticos</p>
          <h2 class="ds-heading mt-2 text-lg">Estado consolidado</h2>
        </div>
        <dl class="grid sm:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="kpi in props.executive.kpis"
            :key="kpi.label"
            class="border-b border-[var(--ds-border)] px-5 py-5 sm:border-r xl:nth-[n+5]:border-b-0 xl:nth-[4n]:border-r-0"
          >
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t(kpi.label) }}</dt>
            <dd class="mt-2 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ kpi.value }}</dd>
            <p class="mt-2 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ $t(kpi.hint) }}</p>
          </div>
        </dl>
      </section>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(20rem,0.75fr)]">
        <section class="ds-panel p-5 sm:p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Tendência</p>
              <h2 class="ds-heading mt-2 text-lg">Ritmo operacional</h2>
              <p class="ds-copy mt-1 text-sm">Propostas aceites, amostras concluídas e certificados emitidos.</p>
            </div>
            <span class="ds-badge ds-badge-neutral">{{ throughputTotal }} eventos</span>
          </div>
          <div class="mt-5 min-h-80">
            <apexchart type="line" height="320" :options="throughputChartOptions" :series="throughputChartSeries" />
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Carga técnica</p>
              <h2 class="ds-heading mt-2 text-lg">Pipeline laboratorial</h2>
              <p class="ds-copy mt-1 text-sm">Distribuição atual das amostras por estado.</p>
            </div>
            <span class="ds-badge ds-badge-warning">{{ sampleStatusTotal }} amostras</span>
          </div>
          <div class="mt-5 min-h-80">
            <apexchart type="donut" height="320" :options="sampleStatusChartOptions" :series="sampleStatusChartSeries" />
          </div>
        </section>
      </div>

      <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Portal</p>
              <h2 class="ds-heading mt-2 text-base">Pedidos por estado</h2>
            </div>
            <span class="ds-badge ds-badge-info">{{ portalRequestTotal }}</span>
          </div>
          <div class="mt-4 min-h-72">
            <apexchart type="bar" height="288" :options="portalRequestsChartOptions" :series="portalRequestsChartSeries" />
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Procurement</p>
              <h2 class="ds-heading mt-2 text-base">Prontidão da fila</h2>
            </div>
            <span class="ds-badge ds-badge-success">{{ procurementReadinessTotal }}</span>
          </div>
          <div class="mt-4 min-h-72">
            <apexchart type="donut" height="288" :options="procurementReadinessChartOptions" :series="procurementReadinessChartSeries" />
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6 lg:col-span-2 xl:col-span-1">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Risco externo</p>
              <h2 class="ds-heading mt-2 text-base">Fornecedores e receção</h2>
            </div>
            <span class="ds-badge ds-badge-danger">{{ supplierRiskTotal }}</span>
          </div>
          <div class="mt-4 space-y-4">
            <apexchart type="bar" height="144" :options="supplierRiskChartOptions" :series="supplierRiskChartSeries" />
            <apexchart type="bar" height="144" :options="receivingNcChartOptions" :series="receivingNcChartSeries" />
          </div>
        </section>
      </div>

      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Carteira</p>
            <h2 class="ds-heading mt-2 text-lg">Clientes com atividade recente</h2>
          </div>
          <span class="ds-badge ds-badge-neutral">{{ props.executive.top_customers?.length || 0 }} clientes</span>
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-3 text-left">Cliente</th>
                <th class="ds-table-heading px-4 py-3 text-left">Código</th>
                <th class="ds-table-heading px-5 py-3 text-right">Locais registados</th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr v-for="customer in props.executive.top_customers" :key="customer.id" class="ds-table-row">
                <td class="ds-table-cell px-5 py-4 font-bold text-[var(--ds-text)]">{{ customer.name }}</td>
                <td class="ds-table-cell px-4 py-4 font-mono">{{ customer.code || "Sem código" }}</td>
                <td class="ds-table-cell px-5 py-4 text-right tabular-nums">{{ customer.warehouses_count }}</td>
              </tr>
              <tr v-if="!props.executive.top_customers?.length">
                <td colspan="3" class="px-5 py-10 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem atividade recente de clientes.</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </section>

      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Qualificação externa</p>
            <h2 class="ds-heading mt-2 text-lg">Fornecedores sob observação</h2>
            <p class="ds-copy mt-1 text-sm">Risco elevado, estado condicionado ou revisão próxima.</p>
          </div>
          <a :href="route('supplier-assessments.index')" class="ds-button ds-button-secondary">
            Abrir avaliações
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
          </a>
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-3 text-left">Fornecedor</th>
                <th class="ds-table-heading px-4 py-3 text-left">Estado</th>
                <th class="ds-table-heading px-4 py-3 text-left">Risco</th>
                <th class="ds-table-heading px-4 py-3 text-left">Área</th>
                <th class="ds-table-heading px-4 py-3 text-right">Score</th>
                <th class="ds-table-heading px-5 py-3 text-right">Próxima revisão</th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr v-for="assessment in props.executive.supplier_watchlist" :key="assessment.id" class="ds-table-row">
                <td class="ds-table-cell px-5 py-4 font-bold text-[var(--ds-text)]">{{ assessment.supplier_name }}</td>
                <td class="ds-table-cell px-4 py-4"><span :class="supplierStatusClass(assessment.status)">{{ supplierStatusLabel(assessment.status) }}</span></td>
                <td class="ds-table-cell px-4 py-4"><span :class="supplierRiskClass(assessment.risk_level)">{{ supplierRiskLabel(assessment.risk_level) }}</span></td>
                <td class="ds-table-cell px-4 py-4">{{ assessment.department_name || "Cobertura transversal" }}</td>
                <td class="ds-table-cell px-4 py-4 text-right font-bold tabular-nums">{{ assessment.total_score }}/100</td>
                <td class="ds-table-cell whitespace-nowrap px-5 py-4 text-right">{{ assessment.next_review_at ? formatShortDate(assessment.next_review_at) : "Sem revisão" }}</td>
              </tr>
              <tr v-if="!props.executive.supplier_watchlist?.length">
                <td colspan="6" class="px-5 py-10 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Nenhum fornecedor exige observação neste momento.</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </section>

      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Abastecimento</p>
            <h2 class="ds-heading mt-2 text-lg">Necessidades à espera de compra</h2>
            <p class="ds-copy mt-1 text-sm">Fila aprovada ainda sem pedido de compra associado.</p>
          </div>
          <a :href="route('vap-inventory.needs.index', { status: 'approved' })" class="ds-button ds-button-secondary">
            Abrir procurement
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
          </a>
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-3 text-left">Referência</th>
                <th class="ds-table-heading px-4 py-3 text-left">Área solicitante</th>
                <th class="ds-table-heading px-4 py-3 text-left">Prontidão</th>
                <th class="ds-table-heading px-4 py-3 text-left">Prazo</th>
                <th class="ds-table-heading px-4 py-3 text-right">Itens</th>
                <th class="ds-table-heading px-5 py-3 text-right"><span class="sr-only">Abrir</span></th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr v-for="need in props.executive.procurement_queue" :key="need.id" class="ds-table-row">
                <td class="ds-table-cell px-5 py-4">
                  <p class="font-mono text-sm font-bold text-[var(--ds-text)]">{{ need.reference }}</p>
                  <p class="mt-1 max-w-md line-clamp-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ need.justification || "Sem justificação adicional" }}</p>
                </td>
                <td class="ds-table-cell px-4 py-4">
                  <p class="font-bold text-[var(--ds-text)]">{{ need.department_name }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ need.lab_name || need.requested_by_name || "Sem laboratório" }}</p>
                </td>
                <td class="ds-table-cell px-4 py-4"><span :class="procurementReadinessClass(need.supplier_readiness)">{{ procurementReadinessLabel(need.supplier_readiness) }}</span></td>
                <td class="ds-table-cell whitespace-nowrap px-4 py-4">
                  <span :class="procurementUrgencyClass(need)">{{ procurementUrgencyLabel(need) }}</span>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ formatShortDate(need.needed_by_date) }}</p>
                </td>
                <td class="ds-table-cell px-4 py-4 text-right font-bold tabular-nums">{{ need.items_count }}</td>
                <td class="ds-table-cell px-5 py-4 text-right">
                  <a :href="route('vap-inventory.needs.show', need.id)" class="ds-icon-button" title="Abrir necessidade">
                    <ChevronRightIcon class="h-4 w-4" />
                  </a>
                </td>
              </tr>
              <tr v-if="!props.executive.procurement_queue?.length">
                <td colspan="6" class="px-5 py-10 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Não existem necessidades aprovadas pendentes de compra.</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </section>

      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Controlo de receção</p>
            <h2 class="ds-heading mt-2 text-lg">Receções com desvio formal</h2>
            <p class="ds-copy mt-1 text-sm">Não conformidades abertas registadas no recebimento de encomendas.</p>
          </div>
          <a :href="route('vap_non_conformities.index', { category: 'quality' })" class="ds-button ds-button-secondary">
            Abrir NCs
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
          </a>
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-3 text-left">Não conformidade</th>
                <th class="ds-table-heading px-4 py-3 text-left">Severidade</th>
                <th class="ds-table-heading px-4 py-3 text-left">Estado</th>
                <th class="ds-table-heading px-4 py-3 text-left">Departamento</th>
                <th class="ds-table-heading px-4 py-3 text-left">Lote</th>
                <th class="ds-table-heading px-5 py-3 text-right">Registada em</th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr v-for="record in props.executive.receiving_non_conformities" :key="record.id" class="ds-table-row">
                <td class="ds-table-cell px-5 py-4">
                  <p class="font-bold text-[var(--ds-text)]">{{ record.title }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-muted)]">{{ record.nc_number }}</p>
                </td>
                <td class="ds-table-cell px-4 py-4"><span :class="receptionSeverityClass(record.severity)">{{ receptionSeverityLabel(record.severity) }}</span></td>
                <td class="ds-table-cell px-4 py-4"><span class="ds-badge ds-badge-neutral">{{ record.status }}</span></td>
                <td class="ds-table-cell px-4 py-4">{{ record.department_name || "Sem departamento" }}</td>
                <td class="ds-table-cell px-4 py-4 font-mono">{{ record.batch_number || "Sem referência" }}</td>
                <td class="ds-table-cell whitespace-nowrap px-5 py-4 text-right">{{ record.reported_at ? formatShortDate(record.reported_at) : "—" }}</td>
              </tr>
              <tr v-if="!props.executive.receiving_non_conformities?.length">
                <td colspan="6" class="px-5 py-10 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Nenhuma receção tem não conformidade aberta.</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import QuickMenu from "@/Components/quick-menu.vue";
import QuickStats from "@/Components/quick-stats.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowDownTrayIcon,
  ArrowTopRightOnSquareIcon,
  BeakerIcon,
  ChevronRightIcon,
  ClipboardDocumentCheckIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
  TableCellsIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  auth: Object,
  items: Array,
  record: Object,
  stats: { type: Object, default: () => ({}) },
  executive: { type: Object, default: null },
  query: Object,
});

const formatShortDate = (value) => (value ? new Date(value).toLocaleDateString("pt-PT") : "—");
const executiveCharts = computed(() => props.executive?.charts || {});
const safeRoute = (name, params = undefined, fallback = "#") => {
  if (typeof route === "function") {
    return route(name, params);
  }

  return fallback;
};
const exportUrl = (format) => {
  const url = props.executive?.export_url || safeRoute("dashboard.export", undefined, "/dashboard/export");

  return url + "?format=" + format;
};

const dashboardPrimaryActions = computed(() => [
  {
    label: "Receber amostras",
    hint: "Entrada, identificação e triagem.",
    href: safeRoute("vap_samples.index", undefined, "/vap-samples"),
    icon: BeakerIcon,
  },
  {
    label: "Inserir resultados",
    hint: "Execução, verificação e aprovação.",
    href: safeRoute("analysis.index", undefined, "/analysis"),
    icon: ClipboardDocumentCheckIcon,
  },
  {
    label: "Emitir documentos",
    hint: "Certificados e relatórios controlados.",
    href: safeRoute("report-studios.index", undefined, "/report-studios"),
    icon: DocumentTextIcon,
  },
  {
    label: "Controlar o SGQ",
    hint: "Desvios, evidências e melhoria.",
    href: safeRoute("qms.index", undefined, "/qms"),
    icon: ShieldCheckIcon,
  },
]);

const dashboardSignalCards = computed(() => [
  {
    label: "Análises",
    value: props.stats?.analysis || 0,
    hint: "Carga técnica registada",
    dot: "bg-cyan-600",
  },
  {
    label: "Certificados",
    value: props.stats?.certificates || 0,
    hint: "Documentos controlados",
    dot: "bg-emerald-600",
  },
  {
    label: "Clientes",
    value: props.stats?.customers || 0,
    hint: "Contas laboratoriais",
    dot: "bg-amber-500",
  },
]);

const throughputChartSeries = computed(() => executiveCharts.value.throughput?.series || []);
const throughputTotal = computed(() => throughputChartSeries.value.reduce((sum, series) => sum + series.data.reduce((inner, value) => inner + value, 0), 0));
const sampleStatusChartSeries = computed(() => executiveCharts.value.sample_status?.series || []);
const sampleStatusTotal = computed(() => executiveCharts.value.sample_status?.total || 0);
const portalRequestsChartSeries = computed(() => [{
  name: "Pedidos",
  data: executiveCharts.value.portal_requests?.series || [],
}]);
const portalRequestTotal = computed(() => executiveCharts.value.portal_requests?.total || 0);
const procurementReadinessChartSeries = computed(() => executiveCharts.value.procurement_readiness?.series || []);
const procurementReadinessTotal = computed(() => executiveCharts.value.procurement_readiness?.total || 0);
const supplierRiskChartSeries = computed(() => [{
  name: "Fornecedores",
  data: executiveCharts.value.supplier_risk?.series || [],
}]);
const supplierRiskTotal = computed(() => executiveCharts.value.supplier_risk?.total || 0);
const receivingNcChartSeries = computed(() => [{
  name: "NCs abertas",
  data: executiveCharts.value.receiving_nc_severity?.series || [],
}]);

const throughputChartOptions = computed(() => ({
  chart: {
    type: "line",
    toolbar: { show: false },
    zoom: { enabled: false },
  },
  colors: ["#0f766e", "#1d4ed8", "#c2410c"],
  stroke: {
    curve: "smooth",
    width: 3,
  },
  markers: {
    size: 4,
    strokeWidth: 0,
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: "#e5e7eb",
    strokeDashArray: 4,
  },
  xaxis: {
    categories: executiveCharts.value.throughput?.categories || [],
    labels: {
      style: {
        colors: "#64748b",
        fontSize: "12px",
      },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: {
        colors: "#64748b",
        fontSize: "12px",
      },
    },
  },
  tooltip: {
    shared: true,
    intersect: false,
  },
  legend: {
    position: "top",
    horizontalAlign: "left",
  },
}));

const sampleStatusChartOptions = computed(() => ({
  labels: executiveCharts.value.sample_status?.labels || [],
  colors: ["#f59e0b", "#2563eb", "#64748b", "#16a34a", "#dc2626"],
  legend: {
    position: "bottom",
    fontSize: "12px",
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => value.toFixed(0) + "%",
  },
  plotOptions: {
    pie: {
      donut: {
        size: "68%",
        labels: {
          show: true,
          total: {
            show: true,
            label: "Amostras",
            formatter: () => String(sampleStatusTotal.value),
          },
        },
      },
    },
  },
}));

const portalRequestsChartOptions = computed(() => ({
  chart: {
    type: "bar",
    toolbar: { show: false },
  },
  colors: ["#0369a1"],
  plotOptions: {
    bar: {
      borderRadius: 3,
      columnWidth: "48%",
    },
  },
  dataLabels: { enabled: true },
  xaxis: {
    categories: executiveCharts.value.portal_requests?.labels || [],
    labels: {
      style: {
        colors: "#64748b",
        fontSize: "12px",
      },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
  },
  grid: {
    borderColor: "#e5e7eb",
    strokeDashArray: 4,
  },
  legend: { show: false },
}));

const procurementReadinessChartOptions = computed(() => ({
  labels: executiveCharts.value.procurement_readiness?.labels || [],
  colors: ["#16a34a", "#0891b2", "#d97706", "#dc2626"],
  legend: {
    position: "bottom",
    fontSize: "12px",
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => value.toFixed(0) + "%",
  },
  plotOptions: {
    pie: {
      donut: {
        size: "68%",
        labels: {
          show: true,
          total: {
            show: true,
            label: "Fila",
            formatter: () => String(procurementReadinessTotal.value),
          },
        },
      },
    },
  },
}));

const supplierRiskChartOptions = computed(() => ({
  chart: {
    type: "bar",
    toolbar: { show: false },
  },
  colors: ["#16a34a", "#0284c7", "#d97706", "#dc2626"],
  plotOptions: {
    bar: {
      horizontal: true,
      distributed: true,
      borderRadius: 3,
      barHeight: "50%",
    },
  },
  dataLabels: { enabled: true },
  xaxis: {
    categories: executiveCharts.value.supplier_risk?.labels || [],
    min: 0,
  },
  legend: { show: false },
  title: {
    text: "Fornecedores ativos por risco",
    align: "left",
    style: {
      fontSize: "13px",
      fontWeight: 600,
    },
  },
}));

const receivingNcChartOptions = computed(() => ({
  chart: {
    type: "bar",
    toolbar: { show: false },
  },
  colors: ["#059669", "#d97706", "#ea580c", "#be123c"],
  plotOptions: {
    bar: {
      borderRadius: 3,
      columnWidth: "50%",
      distributed: true,
    },
  },
  dataLabels: { enabled: true },
  xaxis: {
    categories: executiveCharts.value.receiving_nc_severity?.labels || [],
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
  },
  legend: { show: false },
  title: {
    text: "NCs abertas por severidade",
    align: "left",
    style: {
      fontSize: "13px",
      fontWeight: 600,
    },
  },
}));

const supplierStatusLabel = (value) => ({
  approved: "Aprovado",
  conditional: "Condicional",
  suspended: "Suspenso",
  rejected: "Rejeitado",
}[value] || value);

const supplierRiskLabel = (value) => ({
  low: "Risco baixo",
  medium: "Risco médio",
  high: "Risco elevado",
  critical: "Risco crítico",
}[value] || value);

const supplierStatusClass = (value) => ({
  approved: "ds-badge ds-badge-success",
  conditional: "ds-badge ds-badge-warning",
  suspended: "ds-badge ds-badge-warning",
  rejected: "ds-badge ds-badge-danger",
}[value] || "ds-badge ds-badge-neutral");

const supplierRiskClass = (value) => ({
  low: "ds-badge ds-badge-success",
  medium: "ds-badge ds-badge-info",
  high: "ds-badge ds-badge-warning",
  critical: "ds-badge ds-badge-danger",
}[value] || "ds-badge ds-badge-neutral");

const procurementUrgencyLabel = (need) => {
  if (!need?.needed_by_date) {
    return "Sem prazo";
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const neededDate = new Date(need.needed_by_date);
  neededDate.setHours(0, 0, 0, 0);
  const diffDays = Math.round((neededDate.getTime() - today.getTime()) / 86400000);

  if (diffDays < 0) {
    return "Em atraso";
  }

  if (diffDays <= 3) {
    return "Urgente";
  }

  if (diffDays <= 10) {
    return "Próximo";
  }

  return "Planeado";
};

const procurementUrgencyClass = (need) => ({
  "Em atraso": "ds-badge ds-badge-danger",
  Urgente: "ds-badge ds-badge-warning",
  Próximo: "ds-badge ds-badge-info",
  Planeado: "ds-badge ds-badge-neutral",
  "Sem prazo": "ds-badge ds-badge-neutral",
}[procurementUrgencyLabel(need)]);

const procurementReadinessLabel = (value) => ({
  ready: "Pronta para compra",
  attention: "Exige acompanhamento",
  incomplete: "Dados incompletos",
  blocked: "Bloqueada",
}[value] || "Sem avaliação");

const procurementReadinessClass = (value) => ({
  ready: "ds-badge ds-badge-success",
  attention: "ds-badge ds-badge-info",
  incomplete: "ds-badge ds-badge-warning",
  blocked: "ds-badge ds-badge-danger",
}[value] || "ds-badge ds-badge-neutral");

const receptionSeverityLabel = (value) => ({
  low: "Baixa",
  medium: "Média",
  high: "Alta",
  critical: "Crítica",
}[value] || value || "Acompanhar");

const receptionSeverityClass = (value) => ({
  low: "ds-badge ds-badge-success",
  medium: "ds-badge ds-badge-warning",
  high: "ds-badge ds-badge-warning",
  critical: "ds-badge ds-badge-danger",
}[value] || "ds-badge ds-badge-neutral");
</script>
