<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeft as ArrowLeftIcon,
  CircleCheck as CheckCircleIcon,
  Clock as ClockIcon,
  FileCheck as DocumentCheckIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Minus as MinusIcon,
  Plus as PlusIcon,
  Rows3 as QueueListIcon,
  Table as TableCellsIcon,
  Trash2 as TrashIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  worksheet: {
    type: Object,
    required: true,
  },
  can_edit: { type: Boolean, default: false },
});

const form = useForm({
  name: props.worksheet.name || "",
  worksheets: {
    sheets: Array.isArray(props.worksheet.worksheets?.sheets) && props.worksheet.worksheets.sheets.length
      ? props.worksheet.worksheets.sheets
      : [{ id: "sheet-1", name: "Sheet 1", data: [[""]] }],
  },
});

const activeSheetIndex = ref(0);
const activeSheet = computed(() => form.worksheets.sheets[activeSheetIndex.value] || null);
const scopeControl = computed(() => props.worksheet.worksheets?.scope_control || {});
const canMutate = computed(() => props.can_edit && !form.processing);
const rowCount = computed(() => activeSheet.value?.data?.length || 0);
const columnCount = computed(() => {
  const rows = activeSheet.value?.data || [];
  return Math.max(1, ...rows.map((row) => row.length));
});

const scopeSummary = computed(() => [
  { label: "Esperados", value: scopeControl.value.expected_count || 0 },
  { label: "Com resultado", value: scopeControl.value.completed_count || 0 },
  { label: "Em falta", value: scopeControl.value.missing_count || 0 },
  { label: "Condicionamento", value: scopeControl.value.conditioning_status || "N/A" },
]);

function addSheet() {
  if (!canMutate.value) return;
  form.worksheets.sheets.push({
    id: `sheet-${crypto.randomUUID()}`,
    name: `Folha ${form.worksheets.sheets.length + 1}`,
    data: [[""]],
  });
  activeSheetIndex.value = form.worksheets.sheets.length - 1;
}

function removeActiveSheet() {
  if (!canMutate.value || form.worksheets.sheets.length <= 1) {
    return;
  }

  form.worksheets.sheets.splice(activeSheetIndex.value, 1);
  activeSheetIndex.value = Math.max(0, activeSheetIndex.value - 1);
}

function addRow() {
  if (!canMutate.value) return;
  activeSheet.value?.data.push(Array.from({ length: columnCount.value }, () => ""));
}

function removeLastRow() {
  if (canMutate.value && rowCount.value > 1) {
    activeSheet.value?.data.pop();
  }
}

function addColumn() {
  if (!canMutate.value) return;
  const nextColumnCount = columnCount.value + 1;

  activeSheet.value?.data.forEach((row) => {
    while (row.length < nextColumnCount) {
      row.push("");
    }
  });
}

function removeLastColumn() {
  if (!canMutate.value || columnCount.value <= 1) {
    return;
  }

  const removedColumnIndex = columnCount.value - 1;

  activeSheet.value?.data.forEach((row) => {
    if (row.length > removedColumnIndex) {
      row.splice(removedColumnIndex, 1);
    }
  });
}

function saveWorksheet() {
  if (!canMutate.value || !form.isDirty) return;
  form.put(route("worksheets.update", props.worksheet.id), {
    preserveScroll: true,
    onSuccess: () => form.defaults(),
  });
}

function columnLabel(index) {
  let value = index + 1;
  let label = "";

  while (value > 0) {
    value -= 1;
    label = String.fromCharCode(65 + (value % 26)) + label;
    value = Math.floor(value / 26);
  }

  return label;
}

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
      <nav aria-label="Breadcrumb" class="mb-5">
        <Link :href="route('worksheets.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" />
          Folhas de trabalho laboratoriais
        </Link>
      </nav>

      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Folha de trabalho #{{ worksheet.id }}</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <TableCellsIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading break-words text-2xl">{{ form.name || "Folha de trabalho sem nome" }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm"> Folha de bancada baseada no âmbito controlado da análise e preparada para registo técnico rastreável. </p>
              <ul class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold text-[var(--ds-text-muted)]">
                <li class="inline-flex items-center gap-1.5">
                  <ClockIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  {{ formatDate(worksheet.updated_at) }}
                </li>
                <li class="inline-flex items-center gap-1.5">
                  <QueueListIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  {{ form.worksheets.sheets.length }} folhas
                </li>
                <li>
                  <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold" :class="statusClasses(scopeControl.status)">
                    {{ scopeControl.status_label || "Sem estado" }}
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 lg:justify-end">
          <button type="button" class="ds-button ds-button-secondary" :disabled="!canMutate" @click="addSheet">
            <PlusIcon class="h-4 w-4" />
            Nova folha
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary"
            :disabled="!canMutate || form.worksheets.sheets.length <= 1"
            @click="removeActiveSheet"
          >
            <TrashIcon class="h-4 w-4" />
            Remover folha
          </button>
          <button type="button" class="ds-button ds-button-primary" :disabled="!canMutate || !form.isDirty" :aria-busy="form.processing" @click="saveWorksheet">
            <DocumentCheckIcon class="h-4 w-4" />
            {{ form.processing ? "A guardar..." : form.isDirty ? "Guardar alterações" : "Sem alterações" }}
          </button>
        </div>
      </div>

      <dl
        v-if="scopeControl.expected_count || scopeControl.missing_count || scopeControl.status_label"
        class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4"
      >
        <div
          v-for="item in scopeSummary"
          :key="item.label"
          class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0"
        >
          <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ item.label }}</dt>
          <dd class="mt-2 text-lg font-black text-[var(--ds-text)]">{{ item.value }}</dd>
        </div>
      </dl>
    </section>

    <p v-if="!can_edit" class="ds-copy" role="status">Só leitura. Não tem autorização para alterar esta folha de trabalho.</p>
    <section v-if="form.hasErrors" class="ds-field-error" role="alert">
      <p>Não foi possível guardar. Corrija os campos indicados; as alterações continuam nesta página.</p>
      <ul><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
    </section>

    <section
      v-if="scopeControl.missing_parameters?.length"
      class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 dark:border-amber-400/20 dark:bg-amber-500/10"
      role="status"
    >
      <div class="flex items-start gap-3">
        <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-200" />
        <div class="min-w-0">
          <h2 class="text-sm font-black text-amber-900 dark:text-amber-100">Parâmetros ainda em falta no fluxo</h2>
          <p class="mt-1 text-sm font-semibold text-amber-800 dark:text-amber-200">
            Confirme estes parâmetros antes da conclusão técnica da folha de trabalho.
          </p>
          <ul class="mt-3 flex flex-wrap gap-2">
            <li
              v-for="parameter in scopeControl.missing_parameters"
              :key="parameter.id"
              class="rounded-md border border-amber-200 bg-[var(--ds-panel-raised)] px-2.5 py-1 text-xs font-bold text-amber-900 dark:border-amber-400/20 dark:text-amber-100"
            >
              {{ parameter.code || "N/D" }} · {{ parameter.name }}
            </li>
          </ul>
        </div>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[16rem_minmax(0,1fr)]">
      <aside class="ds-card self-start p-3 xl:sticky xl:top-24">
        <div class="border-b border-[var(--ds-border)] px-2 pb-4">
          <label for="worksheet-name" class="ds-field-label">Nome da folha de trabalho</label>
          <BaseInput id="worksheet-name" v-model="form.name" type="text" class="ds-field mt-2 min-h-10" :disabled="!canMutate" :error="form.errors.name" />
          <p class="ds-field-hint mt-2">Identificação visível na fila e nos registos de bancada.</p>
        </div>

        <div class="px-2 pt-4">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-black text-[var(--ds-text)]">Folhas</h2>
            <span class="ds-chip">{{ form.worksheets.sheets.length }}</span>
          </div>

          <nav class="mt-3 flex gap-2 overflow-x-auto pb-1 xl:flex-col xl:overflow-visible" aria-label="Folhas da folha de trabalho">
            <button
              v-for="(sheet, index) in form.worksheets.sheets"
              :key="sheet.id"
              type="button"
              class="ds-settings-tab min-w-44 shrink-0 xl:min-w-0"
              :class="{ 'ds-settings-tab-active': activeSheetIndex === index }"
              @click="activeSheetIndex = index"
            >
              <TableCellsIcon class="h-4 w-4 shrink-0" />
              <span class="min-w-0 flex-1 truncate text-left">{{ sheet.name || `Folha ${index + 1}` }}</span>
              <CheckCircleIcon v-if="activeSheetIndex === index" class="h-4 w-4 shrink-0" />
            </button>
          </nav>
        </div>
      </aside>

      <section class="ds-table-shell min-w-0">
        <div class="ds-table-summary flex-col items-stretch px-4 py-4 lg:flex-row lg:items-center">
          <div class="min-w-0 flex-1">
            <label for="active-sheet-name" class="ds-field-label">Folha activa</label>
            <BaseInput
              v-if="activeSheet"
              id="active-sheet-name"
              v-model="activeSheet.name"
              :disabled="!canMutate"
              type="text"
              class="ds-field mt-2 min-h-10 lg:max-w-md"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1">
              <button type="button" class="ds-icon-button" title="Adicionar linha" aria-label="Adicionar linha" :disabled="!canMutate" @click="addRow">
                <PlusIcon class="h-4 w-4" />
              </button>
              <span class="min-w-16 px-2 text-center text-xs font-bold text-[var(--ds-text-muted)]">{{ rowCount }} linhas</span>
              <button
                type="button"
                class="ds-icon-button"
                title="Remover ultima linha"
                aria-label="Remover ultima linha"
                :disabled="!canMutate || rowCount <= 1"
                @click="removeLastRow"
              >
                <MinusIcon class="h-4 w-4" />
              </button>
            </div>

            <div class="flex items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1">
              <button type="button" class="ds-icon-button" title="Adicionar coluna" aria-label="Adicionar coluna" :disabled="!canMutate" @click="addColumn">
                <PlusIcon class="h-4 w-4" />
              </button>
              <span class="min-w-20 px-2 text-center text-xs font-bold text-[var(--ds-text-muted)]">{{ columnCount }} colunas</span>
              <button
                type="button"
                class="ds-icon-button"
                title="Remover ultima coluna"
                aria-label="Remover ultima coluna"
                :disabled="!canMutate || columnCount <= 1"
                @click="removeLastColumn"
              >
                <MinusIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <DataTable v-if="activeSheet" class="min-w-full border-separate border-spacing-0 text-sm">
            <thead class="ds-table-head">
              <tr>
                <th class="sticky left-0 z-20 w-12 border-b border-r border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2 py-2 text-center ds-table-heading">#</th>
                <th
                  v-for="columnIndex in columnCount"
                  :key="`column-${columnIndex}`"
                  class="min-w-40 border-b border-r border-[var(--ds-border)] px-3 py-2 text-center ds-table-heading last:border-r-0"
                >
                  {{ columnLabel(columnIndex - 1) }}
                </th>
              </tr>
            </thead>
            <tbody class="ds-table-body">
              <tr v-for="(row, rowIndex) in activeSheet.data" :key="`row-${rowIndex}`" class="ds-table-row">
                <th class="sticky left-0 z-10 w-12 border-b border-r border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2 py-2 text-center text-xs font-black text-[var(--ds-text-soft)]">
                  {{ rowIndex + 1 }}
                </th>
                <td
                  v-for="columnIndex in columnCount"
                  :key="`cell-${rowIndex}-${columnIndex}`"
                  class="min-w-40 border-b border-r border-[var(--ds-border)] bg-[var(--ds-panel)] p-0 last:border-r-0"
                >
                  <BaseInput
                    v-model="activeSheet.data[rowIndex][columnIndex - 1]"
                    :disabled="!canMutate"
                    :error="form.errors[`worksheets.sheets.${activeSheetIndex}.data.${rowIndex}.${columnIndex - 1}`]"
                    type="text"
                    class="min-h-10 w-full border-0 bg-transparent px-3 py-2 text-sm font-semibold text-[var(--ds-text)] outline-none transition focus:bg-[rgb(var(--primary-50-rgb)/0.7)] focus:ring-2 focus:ring-inset focus:ring-[rgb(var(--primary-500-rgb)/0.45)] dark:focus:bg-[rgb(var(--primary-400-rgb)/0.08)]"
                    :aria-label="`Linha ${rowIndex + 1}, coluna ${columnLabel(columnIndex - 1)}`"
                  />
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <footer class="flex flex-col gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 text-xs font-semibold text-[var(--ds-text-muted)] sm:flex-row sm:items-center sm:justify-between">
          <span>{{ activeSheet?.name || "Sem folha activa" }} · {{ rowCount }} x {{ columnCount }}</span>
          <span :class="form.isDirty ? 'text-amber-700 dark:text-amber-200' : 'text-emerald-700 dark:text-emerald-200'">
            {{ form.isDirty ? "Alterações por guardar" : "Worksheet sincronizada" }}
          </span>
        </footer>
      </section>
    </div>
  </div>
</template>
