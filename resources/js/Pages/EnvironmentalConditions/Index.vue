<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import Pagination from "@/Components/pagination.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  BeakerIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
} from "@heroicons/vue/24/outline";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  conditions: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const editingId = ref(null);
const pendingDelete = ref(null);
const isEditing = computed(() => editingId.value !== null);
const canEditForm = computed(() => isEditing.value ? hasPermission("edit_temperatures") : hasPermission("add_temperatures"));
const filterForm = useForm({
  search: props.filters?.search ?? "",
  status: props.filters?.status ?? "",
});
const metricItems = computed(() => [
  { label: "Total", value: props.stats.total ?? 0, detail: "medições registadas", icon: BeakerIcon },
  { label: "Fora dos limites", value: props.stats.critical ?? 0, detail: "requerem ação", icon: ExclamationTriangleIcon },
  { label: "Conformes", value: props.stats.within_limits ?? 0, detail: "dentro dos limites", icon: CheckCircleIcon },
  { label: "Hoje", value: props.stats.today ?? 0, detail: "registos do dia", icon: CalendarDaysIcon },
]);
const measurementFields = [
  { key: "temperature_c", label: "Temperatura", unit: "°C" },
  { key: "humidity_percent", label: "Humidade", unit: "%" },
  { key: "pressure_kpa", label: "Pressão", unit: "kPa" },
  { key: "co2_ppm", label: "CO2", unit: "ppm" },
];
const limitFields = [
  { key: "temperature_min_c", label: "Temperatura mínima", unit: "°C" },
  { key: "temperature_max_c", label: "Temperatura máxima", unit: "°C" },
  { key: "humidity_min_percent", label: "Humidade mínima", unit: "%" },
  { key: "humidity_max_percent", label: "Humidade máxima", unit: "%" },
];

function initialFormData() {
  return {
    area: "",
    location: "",
    recorded_at: new Date().toISOString().slice(0, 16),
    temperature_c: "",
    humidity_percent: "",
    pressure_kpa: "",
    co2_ppm: "",
    temperature_min_c: "",
    temperature_max_c: "",
    humidity_min_percent: "",
    humidity_max_percent: "",
    notes: "",
  };
}

const form = useForm(initialFormData());

function resetForm() {
  editingId.value = null;
  form.defaults(initialFormData());
  form.reset();
  form.clearErrors();
}

function editCondition(condition) {
  editingId.value = condition.id;
  form.defaults({
    area: condition.area ?? "",
    location: condition.location ?? "",
    recorded_at: String(condition.recorded_at ?? "").slice(0, 16),
    temperature_c: condition.temperature_c ?? "",
    humidity_percent: condition.humidity_percent ?? "",
    pressure_kpa: condition.pressure_kpa ?? "",
    co2_ppm: condition.co2_ppm ?? "",
    temperature_min_c: condition.temperature_min_c ?? "",
    temperature_max_c: condition.temperature_max_c ?? "",
    humidity_min_percent: condition.humidity_min_percent ?? "",
    humidity_max_percent: condition.humidity_max_percent ?? "",
    notes: condition.notes ?? "",
  });
  form.reset();
  form.clearErrors();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: resetForm,
  };

  if (isEditing.value) {
    form.put(route("environmental-conditions.update", editingId.value), options);
    return;
  }

  form.post(route("environmental-conditions.store"), options);
}

function applyFilters() {
  router.get(route("environmental-conditions.index"), {
    search: filterForm.search || undefined,
    status: filterForm.status || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function removeCondition() {
  if (!pendingDelete.value) {
    return;
  }

  router.delete(route("environmental-conditions.destroy", pendingDelete.value.id), {
    preserveScroll: true,
    onFinish: () => {
      pendingDelete.value = null;
    },
  });
}

function statusClasses(status) {
  if (status === "critical") {
    return "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300";
  }

  if (status === "pending") {
    return "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300";
  }

  return "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300";
}

function statusLabel(status) {
  return ({ critical: "Fora dos limites", pending: "Pendente", within_limits: "Dentro dos limites" })[status] ?? status;
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "—";
}

watch(() => filterForm.status, applyFilters);
</script>

<template>
  <Head title="Condições ambientais" />

  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <BeakerIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Monitorização laboratorial</p>
            <h1 class="ds-heading mt-1 text-2xl">Condições ambientais</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Temperatura, humidade, pressão e CO2 por área, com limites e avaliação automática de desvios.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link :href="route('temperatures.index')" class="ds-button ds-button-secondary">Catálogo de temperaturas</Link>
          <button v-if="hasPermission('add_temperatures')" type="button" class="ds-button ds-button-primary" @click="resetForm">
            <PlusIcon class="h-4 w-4" /> Novo registo
          </button>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metricItems" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-r xl:border-b-0 xl:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div>
            <component :is="metric.icon" class="h-5 w-5" :class="metric.label === 'Fora dos limites' ? 'text-red-500' : 'text-[var(--ds-text-soft)]'" />
          </div>
        </div>
      </dl>
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(22rem,0.85fr)_minmax(0,1.45fr)]">
      <form v-if="canEditForm" class="ds-panel overflow-hidden" @submit.prevent="submit">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">{{ isEditing ? "Correção" : "Nova leitura" }}</p>
          <h2 class="ds-heading mt-1 text-base">{{ isEditing ? "Atualizar condição" : "Registo ambiental" }}</h2>
          <p class="ds-copy mt-1 text-sm">Defina as leituras e os limites aplicáveis à área monitorizada.</p>
        </div>

        <div class="space-y-6 px-5 py-5 sm:px-6">
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="condition_area" class="ds-field-label mb-2 block">Área</label><input id="condition_area" v-model="form.area" type="text" class="ds-field"><p v-if="form.errors.area" class="ds-field-error mt-2">{{ form.errors.area }}</p></div>
            <div><label for="condition_location" class="ds-field-label mb-2 block">Localização</label><input id="condition_location" v-model="form.location" type="text" class="ds-field"><p v-if="form.errors.location" class="ds-field-error mt-2">{{ form.errors.location }}</p></div>
            <div class="sm:col-span-2"><label for="recorded_at" class="ds-field-label mb-2 block">Data e hora</label><input id="recorded_at" v-model="form.recorded_at" type="datetime-local" class="ds-field"><p v-if="form.errors.recorded_at" class="ds-field-error mt-2">{{ form.errors.recorded_at }}</p></div>
          </div>

          <fieldset>
            <legend class="ds-field-label">Leituras</legend>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
              <div v-for="field in measurementFields" :key="field.key"><label :for="field.key" class="ds-field-label mb-2 block">{{ field.label }} ({{ field.unit }})</label><input :id="field.key" v-model.number="form[field.key]" type="number" step="0.01" class="ds-field"><p v-if="form.errors[field.key]" class="ds-field-error mt-2">{{ form.errors[field.key] }}</p></div>
            </div>
          </fieldset>

          <fieldset>
            <legend class="ds-field-label">Limites de controlo</legend>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
              <div v-for="field in limitFields" :key="field.key"><label :for="field.key" class="ds-field-label mb-2 block">{{ field.label }} ({{ field.unit }})</label><input :id="field.key" v-model.number="form[field.key]" type="number" step="0.01" class="ds-field"><p v-if="form.errors[field.key]" class="ds-field-error mt-2">{{ form.errors[field.key] }}</p></div>
            </div>
          </fieldset>

          <div><label for="condition_notes" class="ds-field-label mb-2 block">Notas</label><textarea id="condition_notes" v-model="form.notes" rows="4" class="ds-field min-h-28 resize-y"></textarea><p v-if="form.errors.notes" class="ds-field-error mt-2">{{ form.errors.notes }}</p></div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
          <button v-if="isEditing" type="button" class="ds-button ds-button-secondary" @click="resetForm">Cancelar</button>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty"><CheckCircleIcon class="h-4 w-4" /> {{ form.processing ? "A guardar..." : (isEditing ? "Atualizar registo" : "Guardar registo") }}</button>
        </div>
      </form>

      <div class="space-y-5" :class="{ 'xl:col-span-2': !canEditForm }">
        <section class="ds-command-surface px-5 py-4 sm:px-6">
          <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
            <div><label for="condition_search" class="ds-field-label mb-2 block">Pesquisar</label><div class="relative"><MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--ds-text-soft)]" /><input id="condition_search" v-model="filterForm.search" type="search" class="ds-field pl-10" placeholder="Área, localização ou notas" @keyup.enter="applyFilters"></div></div>
            <div><label for="condition_status" class="ds-field-label mb-2 block">Estado</label><select id="condition_status" v-model="filterForm.status" class="ds-field"><option value="">Todos</option><option value="within_limits">Dentro dos limites</option><option value="critical">Fora dos limites</option><option value="pending">Pendente</option></select></div>
            <button type="button" class="ds-button ds-button-secondary" @click="applyFilters">Aplicar</button>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><p class="ds-kicker">Histórico</p><h2 class="ds-heading mt-1 text-base">Registos ambientais</h2></div>
          <div v-if="conditions.data.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="condition in conditions.data" :key="condition.id" class="px-5 py-5 sm:px-6">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2"><h3 class="text-base font-semibold text-[var(--ds-text)]">{{ condition.area }}</h3><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClasses(condition.status)">{{ statusLabel(condition.status) }}</span></div>
                  <p class="mt-1 text-sm text-[var(--ds-text-muted)]">{{ condition.location || "Sem localização definida" }} · {{ formatDate(condition.recorded_at) }}</p>
                  <dl class="mt-4 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="field in measurementFields" :key="field.key" class="border-b border-[var(--ds-border)] p-3 sm:border-r xl:border-b-0 xl:last:border-r-0"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ field.label }}</dt><dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ condition[field.key] ?? "—" }} {{ condition[field.key] !== null && condition[field.key] !== undefined ? field.unit : "" }}</dd></div>
                  </dl>
                  <p v-if="condition.notes" class="mt-3 text-sm leading-6 text-[var(--ds-text-muted)]">{{ condition.notes }}</p>
                  <p class="mt-2 text-xs font-semibold text-[var(--ds-text-soft)]">Registado por {{ condition.recorded_by?.name || "Sistema" }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                  <button v-if="hasPermission('edit_temperatures')" type="button" class="ds-button ds-button-secondary min-h-0 px-3 py-2" @click="editCondition(condition)"><PencilSquareIcon class="h-4 w-4" /> Editar</button>
                  <button v-if="hasPermission('delete_temperatures')" type="button" class="ds-button ds-button-danger min-h-0 px-3 py-2" @click="pendingDelete = condition"><TrashIcon class="h-4 w-4" /> Remover</button>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="p-10 text-center"><ExclamationTriangleIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" /><h3 class="mt-3 text-sm font-semibold text-[var(--ds-text)]">Nenhum registo encontrado</h3><p class="mt-1 text-sm text-[var(--ds-text-muted)]">Ajuste os filtros ou registe a primeira leitura desta área.</p></div>
        </section>

        <Pagination
          v-if="conditions.data.length && conditions.last_page > 1"
          :links="conditions.links"
          :from="conditions.from"
          :to="conditions.to"
          :total="conditions.total"
          :current_page="conditions.current_page"
          :last_page="conditions.last_page"
        />
      </div>
    </section>

    <ConfirmDialog
      v-if="pendingDelete"
      title="Remover registo ambiental?"
      description="A leitura deixa de integrar o histórico de monitorização. Esta ação não deve substituir uma correção documentada."
      confirm="Remover"
      cancel="Cancelar"
      @canceled="pendingDelete = null"
      @confirmed="removeCondition"
    />
  </div>
</template>
