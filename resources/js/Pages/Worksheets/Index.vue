<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowRightIcon,
  CheckBadgeIcon,
  ClockIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  Squares2X2Icon,
} from "@heroicons/vue/24/outline";
import { computed, ref } from "vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  worksheets: {
    type: Array,
    default: () => [],
  },
});

const searchTerm = ref("");
const statusFilter = ref("all");

const worksheetRows = computed(() =>
  (props.worksheets || []).map((worksheet) => {
    const sheets = Array.isArray(worksheet.worksheets?.sheets) ? worksheet.worksheets.sheets : [];
    const scopeControl = worksheet.worksheets?.scope_control || {};

    return {
      ...worksheet,
      sheetCount: sheets.length,
      expectedCount: scopeControl.expected_count || 0,
      completedCount: scopeControl.completed_count || 0,
      missingCount: scopeControl.missing_count || 0,
      status: scopeControl.status || "pending",
      statusLabel: scopeControl.status_label || "Pendente",
      generatedFrom: worksheet.worksheets?.generated_from || "manual",
      analysisId: worksheet.worksheets?.analysis_id || null,
    };
  }),
);

const summary = computed(() => {
  const total = worksheetRows.value.length;
  const complete = worksheetRows.value.filter((worksheet) => worksheet.status === "complete").length;
  const partial = worksheetRows.value.filter((worksheet) => worksheet.status === "partial").length;
  const pending = total - complete - partial;
  const completionRate = total ? Math.round((complete / total) * 100) : 0;

  return { total, complete, partial, pending, completionRate };
});

const statusOptions = computed(() => [
  { value: "all", label: "Todas", count: summary.value.total },
  { value: "pending", label: "Pendentes", count: summary.value.pending },
  { value: "partial", label: "Parciais", count: summary.value.partial },
  { value: "complete", label: "Completas", count: summary.value.complete },
]);

const filteredWorksheets = computed(() => {
  const query = searchTerm.value.trim().toLocaleLowerCase("pt-PT");

  return worksheetRows.value.filter((worksheet) => {
    const matchesStatus = statusFilter.value === "all" || worksheet.status === statusFilter.value;
    const searchableText = [worksheet.name, worksheet.id, worksheet.analysisId, worksheet.statusLabel]
      .filter(Boolean)
      .join(" ")
      .toLocaleLowerCase("pt-PT");

    return matchesStatus && (!query || searchableText.includes(query));
  });
});

function statusClasses(status) {
  if (status === "complete") {
    return "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200";
  }

  if (status === "partial") {
    return "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200";
  }

  return "border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]";
}

function formatDate(date) {
  if (!date) {
    return "Sem registo";
  }

  return new Date(date).toLocaleString("pt-PT", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Execucao analitica</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <DocumentTextIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">Worksheets laboratoriais</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">
                Folhas de bancada geradas a partir do escopo analitico, com progresso e lacunas tecnicas visiveis.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <span class="ds-chip">
            <Squares2X2Icon class="h-3.5 w-3.5" />
            {{ summary.total }} folhas
          </span>
          <span class="ds-chip">
            <CheckBadgeIcon class="h-3.5 w-3.5" />
            {{ summary.completionRate }}% completas
          </span>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-r xl:border-b-0">
          <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Total</dt>
          <dd class="mt-2 text-xl font-black text-[var(--ds-text)]">{{ summary.total }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 xl:border-b-0 xl:border-r">
          <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Pendentes</dt>
          <dd class="mt-2 text-xl font-black text-[var(--ds-text)]">{{ summary.pending }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Parciais</dt>
          <dd class="mt-2 text-xl font-black text-amber-700 dark:text-amber-200">{{ summary.partial }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Completas</dt>
          <dd class="mt-2 text-xl font-black text-emerald-700 dark:text-emerald-200">{{ summary.complete }}</dd>
        </div>
      </dl>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary flex-col items-stretch px-4 py-4 lg:flex-row lg:items-center">
        <div>
          <p class="ds-kicker">Fila de worksheets</p>
          <h2 class="ds-heading mt-1 text-base">Escopo e progresso operacional</h2>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="relative min-w-0 sm:w-72">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              v-model="searchTerm"
              type="search"
              class="ds-field min-h-10 pl-9"
              placeholder="Pesquisar nome, ID ou analise"
              aria-label="Pesquisar worksheets"
            />
          </div>

          <div class="flex min-w-max rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1" aria-label="Filtrar por estado">
            <button
              v-for="option in statusOptions"
              :key="option.value"
              type="button"
              class="rounded-md px-2.5 py-1.5 text-xs font-bold transition"
              :class="statusFilter === option.value
                ? 'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[rgb(var(--primary-950-rgb))]'
                : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]'"
              @click="statusFilter = option.value"
            >
              {{ option.label }} <span class="opacity-70">{{ option.count }}</span>
            </button>
          </div>
        </div>
      </div>

      <div v-if="filteredWorksheets.length" class="overflow-x-auto">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-heading px-4 py-3 text-left">Worksheet</th>
              <th class="ds-table-heading px-4 py-3 text-left">Estado</th>
              <th class="ds-table-heading px-4 py-3 text-left">Escopo</th>
              <th class="ds-table-heading px-4 py-3 text-left">Sheets</th>
              <th class="ds-table-heading px-4 py-3 text-left">Origem</th>
              <th class="ds-table-heading px-4 py-3 text-left">Atualizacao</th>
              <th class="ds-table-heading px-4 py-3 text-right"><span class="sr-only">Acoes</span></th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="worksheet in filteredWorksheets" :key="worksheet.id" class="ds-table-row">
              <td class="px-4 py-4">
                <p class="text-sm font-black text-[var(--ds-text)]">{{ worksheet.name || "Worksheet sem nome" }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                  #{{ worksheet.id }}<template v-if="worksheet.analysisId"> · Analise #{{ worksheet.analysisId }}</template>
                </p>
              </td>
              <td class="px-4 py-4">
                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold" :class="statusClasses(worksheet.status)">
                  {{ worksheet.statusLabel }}
                </span>
              </td>
              <td class="px-4 py-4">
                <p class="text-sm font-black text-[var(--ds-text)]">{{ worksheet.completedCount }} / {{ worksheet.expectedCount }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ worksheet.missingCount }} em falta</p>
              </td>
              <td class="ds-table-cell px-4 py-4">{{ worksheet.sheetCount }}</td>
              <td class="ds-table-cell px-4 py-4">
                {{ worksheet.generatedFrom === "analysis_scope" ? "Analise controlada" : "Manual" }}
              </td>
              <td class="ds-table-cell px-4 py-4">
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                  <ClockIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  {{ formatDate(worksheet.updated_at) }}
                </span>
              </td>
              <td class="px-4 py-4 text-right">
                <Link :href="route('worksheets.show', worksheet.id)" class="ds-table-action whitespace-nowrap">
                  Abrir
                  <ArrowRightIcon class="h-3.5 w-3.5" />
                </Link>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-else class="ds-empty-state m-4 px-6 py-12 text-center">
        <DocumentTextIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <h3 class="ds-heading mt-3 text-sm">Nenhuma worksheet encontrada</h3>
        <p class="ds-copy mt-1 text-sm">
          Ajuste a pesquisa ou o filtro de estado para voltar a ver a fila operacional.
        </p>
      </div>
    </section>
  </div>
</template>
