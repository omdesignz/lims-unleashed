<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import {
  DocumentTextIcon,
  Squares2X2Icon,
  CheckBadgeIcon,
  ClockIcon,
  ArrowRightIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  worksheets: {
    type: Array,
    default: () => [],
  },
});

const worksheetCards = computed(() =>
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
  const total = worksheetCards.value.length;
  const complete = worksheetCards.value.filter((worksheet) => worksheet.status === "complete").length;
  const partial = worksheetCards.value.filter((worksheet) => worksheet.status === "partial").length;
  const pending = total - complete - partial;

  return {
    total,
    complete,
    partial,
    pending,
  };
});

const getStatusClasses = (status) => {
  if (status === "complete") {
    return "bg-emerald-100 text-emerald-800 ring-emerald-700/10 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20";
  }

  if (status === "partial") {
    return "bg-amber-100 text-amber-800 ring-amber-700/10 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20";
  }

  return "bg-slate-100 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600/20";
};

const formatDate = (date) => {
  if (!date) {
    return "-";
  }

  return new Date(date).toLocaleString("pt-PT", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>

<template>
  <div class="worksheet-index space-y-8" :class="commercialDocumentThemeClasses">
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--primary-900-rgb))] text-white shadow-lg shadow-[rgb(var(--primary-900-rgb)/0.2)] dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[#07110f]">
            <DocumentTextIcon class="h-6 w-6" />
          </div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            Worksheets laboratoriais
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            Gere folhas de trabalho operacionais com contexto analítico, controlo de escopo e progresso de execução sempre visível.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-[rgb(var(--primary-50-rgb))] px-3 py-1 text-sm font-medium text-[rgb(var(--primary-900-rgb))] ring-1 ring-inset ring-[rgb(var(--primary-700-rgb)/0.12)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))] dark:ring-[rgb(var(--primary-300-rgb)/0.2)]">
            {{ summary.total }} worksheets
          </span>
          <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-700/10 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
            {{ summary.complete }} completas
          </span>
        </div>
      </div>

      <div class="mt-6 grid gap-4 border-t border-slate-200 pt-6 md:grid-cols-4 dark:border-slate-800">
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Total</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ summary.total }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Completas</p>
          <p class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ summary.complete }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Parciais</p>
          <p class="mt-2 text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ summary.partial }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Pendentes</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ summary.pending }}</p>
        </article>
      </div>
    </div>

    <div
      v-if="worksheetCards.length"
      class="grid gap-5 xl:grid-cols-2 2xl:grid-cols-3"
    >
      <article
        v-for="worksheet in worksheetCards"
        :key="worksheet.id"
        class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 p-5 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] transition-transform duration-200 hover:-translate-y-0.5 dark:border-slate-800 dark:bg-slate-950/85"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
              Worksheet #{{ worksheet.id }}
            </p>
            <h2 class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">
              {{ worksheet.name || "Worksheet sem nome" }}
            </h2>
          </div>

          <span
            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
            :class="getStatusClasses(worksheet.status)"
          >
            {{ worksheet.statusLabel }}
          </span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
          <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Sheets</p>
            <p class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ worksheet.sheetCount }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Escopo</p>
            <p class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ worksheet.expectedCount }}</p>
          </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/70">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 dark:text-slate-400">Com resultado</span>
            <span class="font-semibold text-slate-900 dark:text-white">{{ worksheet.completedCount }}</span>
          </div>
          <div class="mt-2 flex items-center justify-between text-sm">
            <span class="text-slate-500 dark:text-slate-400">Em falta</span>
            <span class="font-semibold text-slate-900 dark:text-white">{{ worksheet.missingCount }}</span>
          </div>
          <div class="mt-2 flex items-center justify-between text-sm">
            <span class="text-slate-500 dark:text-slate-400">Origem</span>
            <span class="font-medium text-slate-700 dark:text-slate-200">
              {{ worksheet.generatedFrom === "analysis_scope" ? "Análise controlada" : "Manual" }}
            </span>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
          <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <ClockIcon class="h-4 w-4" />
            {{ formatDate(worksheet.updated_at) }}
          </div>

          <Link
            :href="route('worksheets.show', worksheet.id)"
            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-[rgb(var(--primary-800-rgb))] hover:to-[rgb(var(--primary-600-rgb))]"
          >
            Abrir worksheet
            <ArrowRightIcon class="h-4 w-4" />
          </Link>
        </div>
      </article>
    </div>

    <div
      v-else
      class="rounded-[26px] border border-dashed border-slate-300 bg-white/90 p-12 text-center shadow-sm dark:border-slate-700 dark:bg-slate-950/70"
    >
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
        <Squares2X2Icon class="h-7 w-7" />
      </div>
      <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">
        Ainda não existem worksheets disponíveis
      </h2>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        As worksheets serão geradas a partir do fluxo analítico ou criadas manualmente conforme a operação laboratorial.
      </p>
    </div>
  </div>
</template>
