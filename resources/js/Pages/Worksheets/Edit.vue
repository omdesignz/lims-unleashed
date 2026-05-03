<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
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
  <div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Worksheet</p>
          <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ form.name }}</h1>
          <p class="mt-2 text-sm text-slate-600">
            Planilha operacional baseada no escopo analítico controlado da amostra.
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            @click="addSheet"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Adicionar sheet
          </button>
          <button
            type="button"
            @click="removeActiveSheet"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Remover ativa
          </button>
          <button
            type="button"
            @click="saveWorksheet"
            :disabled="form.processing"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ form.processing ? 'A guardar...' : 'Guardar worksheet' }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="scopeControl.expected_count || scopeControl.missing_count || scopeControl.status_label"
      class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Escopo controlado</p>
          <h2 class="mt-1 text-lg font-semibold text-slate-900">Estado operacional da worksheet</h2>
          <p class="mt-2 text-sm text-slate-600">
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
        <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Esperados</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ scopeControl.expected_count || 0 }}</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Com resultado</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ scopeControl.completed_count || 0 }}</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Em falta</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ scopeControl.missing_count || 0 }}</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Condicionamento</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ scopeControl.conditioning_status || 'N/A' }}</p>
        </article>
      </div>

      <div v-if="scopeControl.missing_parameters?.length" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-sm font-semibold text-amber-900">Parâmetros ainda em falta no fluxo</p>
        <ul class="mt-3 space-y-2 text-sm text-amber-800">
          <li v-for="parameter in scopeControl.missing_parameters" :key="parameter.id">
            {{ parameter.code || 'N/D' }} · {{ parameter.name }}
          </li>
        </ul>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[280px,minmax(0,1fr)]">
      <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm font-semibold text-slate-900">Sheets</p>
        <div class="mt-4 space-y-2">
          <button
            v-for="(sheet, index) in form.worksheets.sheets"
            :key="sheet.id"
            type="button"
            @click="activeSheetIndex = index"
            :class="[
              'w-full rounded-xl border px-4 py-3 text-left text-sm transition',
              activeSheetIndex === index
                ? 'border-slate-900 bg-slate-900 text-white'
                : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'
            ]"
          >
            {{ sheet.name }}
          </button>
        </div>
      </aside>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <input
            v-if="activeSheet"
            v-model="activeSheet.name"
            type="text"
            class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 lg:max-w-sm"
          />

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              @click="addRow"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Adicionar linha
            </button>
            <button
              type="button"
              @click="addColumn"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
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
                  class="border border-slate-200 bg-white p-0"
                >
                  <input
                    v-model="activeSheet.data[rowIndex][columnIndex]"
                    type="text"
                    class="w-full min-w-[140px] border-0 px-3 py-2 text-sm text-slate-900 focus:ring-2 focus:ring-slate-900/10"
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
