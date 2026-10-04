<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowRight as ArrowRightIcon,
  BadgeCheck as CheckBadgeIcon,
  Clock as ClockIcon,
  FileText as DocumentTextIcon,
  Search as MagnifyingGlassIcon,
  LayoutGrid as Squares2X2Icon,
} from "@lucide/vue";
import { computed, ref } from "vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  worksheets: {
    type: Array,
    default: () => [],
  },
  trashed: { type: String, default: "" },
  can_restore: { type: Boolean, default: false },
});

const searchTerm = ref("");
const statusFilter = ref("all");
const restoreForm = useForm({ recordIds: [] });

function restoreWorksheet(worksheet) {
  if (!props.can_restore || restoreForm.processing || !worksheet?.deleted_at || !worksheet.id) return;
  restoreForm.recordIds = [worksheet.id];
  restoreForm.post(route("worksheets.restore"), {
    preserveScroll: true,
    onSuccess: () => restoreForm.reset("recordIds"),
  });
}

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
  <div class="pl-page space-y-6">
    <PageHeader title="Worksheets laboratoriais" lede="Folhas de bancada geradas a partir do âmbito analítico, com progresso e lacunas técnicas visíveis.">
      <template #badges>
        <span class="ds-chip">
          <Squares2X2Icon class="h-3.5 w-3.5" />
          {{ summary.total }} folhas
        </span>
        <span class="ds-chip">
          <CheckBadgeIcon class="h-3.5 w-3.5" />
          {{ summary.completionRate }}% completas
        </span>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Total</dt>
        <dd class="pl-cell-value">{{ summary.total }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Pendentes</dt>
        <dd class="pl-cell-value">{{ summary.pending }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Parciais</dt>
        <dd class="pl-cell-value">{{ summary.partial }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Completas</dt>
        <dd class="pl-cell-value">{{ summary.complete }}</dd>
      </div>
    </dl>

    <section class="ds-table-shell">
      <nav class="flex flex-wrap gap-2 border-b border-[var(--ds-border)] px-4 py-3" aria-label="Arquivo das folhas de trabalho">
        <Link :href="route('worksheets.index')" preserve-scroll preserve-state class="ds-button ds-button-secondary" :aria-current="!trashed ? 'page' : undefined">
          Activas
        </Link>
        <Link :href="route('worksheets.index', { trashed: 'only' })" preserve-scroll preserve-state class="ds-button ds-button-secondary" :aria-current="trashed === 'only' ? 'page' : undefined">
          Arquivo
        </Link>
      </nav>
      <div v-if="restoreForm.hasErrors" class="ds-field-error px-4 py-3" role="alert">
        <p v-for="(message, field) in restoreForm.errors" :key="field">{{ message }}</p>
      </div>
      <div class="ds-table-summary flex-col items-stretch px-4 py-4 lg:flex-row lg:items-center">
        <div>
          <p class="ds-kicker">Fila de worksheets</p>
          <h2 class="ds-heading mt-1 text-base">Âmbito e progresso operacional</h2>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="relative min-w-0 sm:w-72">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              v-model="searchTerm"
              type="search"
              class="ds-field min-h-10 pl-9"
              placeholder="Pesquisar nome, ID ou análise"
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
              <th class="ds-table-heading px-4 py-3 text-left">Folha de trabalho</th>
              <th class="ds-table-heading px-4 py-3 text-left">Estado</th>
              <th class="ds-table-heading px-4 py-3 text-left">Âmbito</th>
              <th class="ds-table-heading px-4 py-3 text-left">Folhas</th>
              <th class="ds-table-heading px-4 py-3 text-left">Origem</th>
              <th class="ds-table-heading px-4 py-3 text-left">Atualizacao</th>
              <th class="ds-table-heading px-4 py-3 text-right"><span class="sr-only">Acções</span></th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="worksheet in filteredWorksheets" :key="worksheet.id" class="ds-table-row">
              <td class="px-4 py-4">
                <p class="text-sm font-black text-[var(--ds-text)]">{{ worksheet.name || "Folha de trabalho sem nome" }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                  #{{ worksheet.id }}<template v-if="worksheet.analysisId"> · Análise #{{ worksheet.analysisId }}</template>
                </p>
                <p v-if="worksheet.deleted_at" class="ds-copy mt-1 text-xs">Arquivada</p>
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
                {{ worksheet.generatedFrom === "analysis_scope" ? "Análise controlada" : "Manual" }}
              </td>
              <td class="ds-table-cell px-4 py-4">
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                  <ClockIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  {{ formatDate(worksheet.updated_at) }}
                </span>
              </td>
              <td class="px-4 py-4 text-right">
                <Link v-if="!worksheet.deleted_at" :href="route('worksheets.show', worksheet.id)" class="ds-table-action whitespace-nowrap">
                  Abrir
                  <ArrowRightIcon class="h-3.5 w-3.5" />
                </Link>
                <button
                  v-else-if="can_restore"
                  type="button"
                  class="ds-button ds-button-secondary whitespace-nowrap"
                  :disabled="restoreForm.processing"
                  :aria-busy="restoreForm.processing && restoreForm.recordIds.includes(worksheet.id)"
                  :aria-label="`Restaurar ${worksheet.name || 'folha de trabalho'}`"
                  @click="restoreWorksheet(worksheet)"
                >
                  {{ restoreForm.processing && restoreForm.recordIds.includes(worksheet.id) ? 'A restaurar...' : 'Restaurar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-else class="ds-empty-state m-4 px-6 py-12 text-center">
        <DocumentTextIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <h3 class="ds-heading mt-3 text-sm">Nenhuma folha de trabalho encontrada</h3>
        <p class="ds-copy mt-1 text-sm">
          {{ trashed === 'only' ? 'Não há folhas arquivadas que correspondam aos filtros.' : 'Ajuste a pesquisa ou o filtro de estado para voltar a ver a fila operacional.' }}
        </p>
      </div>
    </section>
  </div>
</template>
