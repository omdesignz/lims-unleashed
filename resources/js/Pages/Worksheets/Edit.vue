<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  worksheet: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  name: props.worksheet.name || "",
  worksheets: {
    ...(props.worksheet.worksheets || {}),
    sheets: Array.isArray(props.worksheet.worksheets?.sheets) && props.worksheet.worksheets.sheets.length
      ? props.worksheet.worksheets.sheets
      : [{ id: "sheet-1", name: "Sheet 1", data: [[""]] }],
  },
});

const activeSheetIndex = ref(0);
const activeSheet = computed(() => form.worksheets.sheets[activeSheetIndex.value] || null);
const scopeControl = computed(() => form.worksheets.scope_control || {});

const addSheet = () => {
  form.worksheets.sheets.push({
    id: `sheet-${Date.now()}`,
    name: `Sheet ${form.worksheets.sheets.length + 1}`,
    data: [[""]],
  });
  activeSheetIndex.value = form.worksheets.sheets.length - 1;
};

const removeActiveSheet = () => {
  if (form.worksheets.sheets.length <= 1) {
    return;
  }

  form.worksheets.sheets.splice(activeSheetIndex.value, 1);
  activeSheetIndex.value = Math.max(0, activeSheetIndex.value - 1);
};

const addRow = () => {
  const columnCount = activeSheet.value?.data?.[0]?.length || 1;
  activeSheet.value?.data.push(Array.from({ length: columnCount }, () => ""));
};

const addColumn = () => {
  activeSheet.value?.data.forEach((row) => row.push(""));
};

const saveWorksheet = () => {
  form.put(route("worksheets.update", props.worksheet.id), {
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="worksheet-editor space-y-8" :class="commercialDocumentThemeClasses">
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Worksheet</p>
          <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ form.name }}</h1>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Planilha operacional baseada no escopo analítico controlado da amostra.
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            @click="addSheet"
            class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            Adicionar sheet
          </button>
          <button
            type="button"
            @click="removeActiveSheet"
            class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            Remover ativa
          </button>
          <button
            type="button"
            @click="saveWorksheet"
            :disabled="form.processing"
            class="rounded-2xl bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] px-4 py-2 text-sm font-medium text-white transition hover:from-[rgb(var(--primary-800-rgb))] hover:to-[rgb(var(--primary-600-rgb))] disabled:cursor-not-allowed disabled:opacity-60 dark:from-[rgb(var(--primary-300-rgb))] dark:to-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]"
          >
            {{ form.processing ? 'A guardar...' : 'Guardar worksheet' }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="scopeControl.expected_count || scopeControl.missing_count || scopeControl.status_label"
      class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Escopo controlado</p>
          <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Estado operacional da worksheet</h2>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Resume se o conjunto controlado de parâmetros já foi totalmente refletido no fluxo analítico.
          </p>
        </div>
        <span
          :class="[
            'rounded-full px-3 py-1 text-xs font-semibold ring-1',
            scopeControl.status === 'complete'
              ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
              : scopeControl.status === 'partial'
                ? 'bg-amber-50 text-amber-800 ring-amber-200'
                : 'bg-slate-100 text-slate-700 ring-slate-200'
          ]"
        >
          {{ scopeControl.status_label || 'Sem estado' }}
        </span>
      </div>

      <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Esperados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeControl.expected_count || 0 }}</p>
          </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Com resultado</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeControl.completed_count || 0 }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Em falta</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeControl.missing_count || 0 }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Condicionamento</p>
          <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ scopeControl.conditioning_status || 'N/A' }}</p>
        </article>
      </div>

      <div v-if="scopeControl.missing_parameters?.length" class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-500/10">
        <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Parâmetros ainda em falta no fluxo</p>
        <ul class="mt-3 space-y-2 text-sm text-amber-800 dark:text-amber-300">
          <li v-for="parameter in scopeControl.missing_parameters" :key="parameter.id">
            {{ parameter.code || 'N/D' }} · {{ parameter.name }}
          </li>
        </ul>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[280px,minmax(0,1fr)]">
      <aside class="rounded-[26px] border border-slate-200 bg-white/95 p-4 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
        <p class="text-sm font-semibold text-slate-900 dark:text-white">Sheets</p>
        <div class="mt-4 space-y-2">
          <button
            v-for="(sheet, index) in form.worksheets.sheets"
            :key="sheet.id"
            type="button"
            @click="activeSheetIndex = index"
            :class="[
              'w-full rounded-xl border px-4 py-3 text-left text-sm transition',
              activeSheetIndex === index
                ? 'border-[rgb(var(--primary-900-rgb))] bg-[rgb(var(--primary-900-rgb))] text-white dark:border-[rgb(var(--primary-300-rgb))] dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[#07110f]'
                : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800'
            ]"
          >
            {{ sheet.name }}
          </button>
        </div>
      </aside>

      <section class="rounded-[26px] border border-slate-200 bg-white/95 p-4 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <input
            v-if="activeSheet"
            v-model="activeSheet.name"
            type="text"
            class="w-full rounded-2xl border border-[#d8cbb8] bg-[#fffdf7] px-4 py-2 text-sm text-[#15231f] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.18)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] lg:max-w-sm"
          />

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              @click="addRow"
              class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              Adicionar linha
            </button>
            <button
              type="button"
              @click="addColumn"
              class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              Adicionar coluna
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full border-collapse">
            <tbody v-if="activeSheet">
              <tr v-for="(row, rowIndex) in activeSheet.data" :key="`row-${rowIndex}`">
                <td
                  v-for="(_, columnIndex) in row"
                  :key="`cell-${rowIndex}-${columnIndex}`"
                  class="border border-slate-200 bg-white p-0 dark:border-slate-800 dark:bg-slate-950"
                >
                  <input
                    v-model="activeSheet.data[rowIndex][columnIndex]"
                    type="text"
                    class="w-full min-w-[140px] border-0 bg-transparent px-3 py-2 text-sm text-slate-900 focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.18)] dark:text-white"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.worksheet-editor :deep(input),
.worksheet-editor :deep(textarea),
.worksheet-editor :deep(select) {
  color-scheme: light dark;
}

.worksheet-editor table input:focus {
  outline: none;
}
</style>
