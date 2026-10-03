<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  CalendarDaysIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  CircleStackIcon,
  DocumentMagnifyingGlassIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  TrashIcon,
  UserGroupIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], links: [] }) },
  logNameOptions: { type: Array, default: () => [] },
  causerOptions: { type: Array, default: () => [] },
  subjectOptions: { type: Array, default: () => [] },
  eventOptions: { type: Array, default: () => [] },
  propertiesOptions: { type: Array, default: () => [] },
});

const { hasPermission } = usePermission();
const pageUrl = usePage().url;
const initialQuery = new URLSearchParams(pageUrl.includes("?") ? pageUrl.split("?")[1] : "");
const showFilters = ref(initialQuery.size > 0);
const selectedActivity = ref(null);
const deleteMode = ref(null);
const showDeleteConfirmation = ref(false);
const isLoading = ref(false);
const detailedActivity = ref(null);
const detailedProperties = ref(null);
const showDetailsModal = ref(false);
const isLoadingDetails = ref(false);
const detailsError = ref("");

function selectedOption(options, key) {
  const value = initialQuery.get(key);

  return options.find((option) => String(option.value) === String(value)) ?? null;
}

const filters = useForm({
  log_name: selectedOption(props.logNameOptions, "log_name"),
  causer_id: selectedOption(props.causerOptions, "causer_id"),
  subject_type: selectedOption(props.subjectOptions, "subject_type"),
  event: selectedOption(props.eventOptions, "event"),
  property: selectedOption(props.propertiesOptions, "property"),
  description: initialQuery.get("description") || "",
  start_date: initialQuery.get("start_date") || "",
  end_date: initialQuery.get("end_date") || "",
  batch_uuid: initialQuery.get("batch_uuid") || "",
  per_page: Number(initialQuery.get("per_page") || 25),
});

const rows = computed(() => props.record?.data ?? []);
const totalActivities = computed(() => props.record?.total ?? 0);
const pageStatistics = computed(() => {
  const today = new Date().toDateString();
  const actors = new Set();
  let todayCount = 0;
  let exceptionCount = 0;

  for (const activity of rows.value) {
    if (activity.created_at && new Date(activity.created_at).toDateString() === today) {
      todayCount += 1;
    }

    if (activity.causer_id) {
      actors.add(String(activity.causer_id));
    }

    if (activityLevel(activity) === "danger" || activityLevel(activity) === "warning") {
      exceptionCount += 1;
    }
  }

  return {
    today: todayCount,
    actors: actors.size,
    exceptions: exceptionCount,
  };
});
const metrics = computed(() => [
  { label: "Total", value: totalActivities.value, detail: "eventos no registo", icon: CircleStackIcon },
  { label: "Nesta página", value: rows.value.length, detail: "eventos carregados", icon: DocumentMagnifyingGlassIcon },
  { label: "Hoje", value: pageStatistics.value.today, detail: "eventos visíveis", icon: CalendarDaysIcon },
  { label: "Atenção", value: pageStatistics.value.exceptions, detail: "avisos ou falhas", icon: ExclamationTriangleIcon },
  { label: "Atores", value: pageStatistics.value.actors, detail: "utilizadores distintos", icon: UserGroupIcon },
]);
const activeFilters = computed(() => {
  const labels = {
    log_name: "Log",
    causer_id: "Ator",
    subject_type: "Entidade",
    event: "Evento",
    property: "Propriedade",
    description: "Descrição",
    start_date: "Desde",
    end_date: "Até",
    batch_uuid: "Lote",
  };

  return Object.entries(filters.data())
    .filter(([key, value]) => key !== "per_page" && value !== null && value !== "")
    .map(([key, value]) => ({
      key,
      label: labels[key],
      value: typeof value === "object" ? value.label : value,
    }));
});
const hasActiveFilters = computed(() => activeFilters.value.length > 0);
const exportUrl = computed(() => {
  const params = buildQueryParams();
  params.delete("per_page");

  return route("systemactivity.export") + "?" + params.toString();
});

function simpleValue(value) {
  if (value && typeof value === "object") {
    return value.value ?? value.id ?? null;
  }

  return value;
}

function buildQueryParams() {
  const params = new URLSearchParams();

  for (const [key, value] of Object.entries(filters.data())) {
    const normalized = simpleValue(value);

    if (normalized !== null && normalized !== "") {
      params.set(key, normalized);
    }
  }

  return params;
}

function applyFilters() {
  router.get(route("systemactivity.index"), Object.fromEntries(buildQueryParams()), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => {
      isLoading.value = true;
    },
    onFinish: () => {
      isLoading.value = false;
    },
  });
}

function resetFilters() {
  filters.log_name = null;
  filters.causer_id = null;
  filters.subject_type = null;
  filters.event = null;
  filters.property = null;
  filters.description = "";
  filters.start_date = "";
  filters.end_date = "";
  filters.batch_uuid = "";
  filters.per_page = 25;
  applyFilters();
}

function clearFilter(key) {
  filters[key] = "";

  if (["log_name", "causer_id", "subject_type", "event", "property"].includes(key)) {
    filters[key] = null;
  }

  applyFilters();
}

function localOptions(options, query, setOptions) {
  const term = String(query || "").toLocaleLowerCase();
  setOptions(options.filter((option) => option.label.toLocaleLowerCase().includes(term)).slice(0, 25));
}

function loadLogNames(query, setOptions) {
  localOptions(props.logNameOptions, query, setOptions);
}

function loadCausers(query, setOptions) {
  localOptions(props.causerOptions, query, setOptions);
}

function loadSubjects(query, setOptions) {
  localOptions(props.subjectOptions, query, setOptions);
}

function loadEvents(query, setOptions) {
  localOptions(props.eventOptions, query, setOptions);
}

function loadProperties(query, setOptions) {
  localOptions(props.propertiesOptions, query, setOptions);
}

function activityLevel(activity) {
  const text = ((activity.event || "") + " " + (activity.description || "") + " " + (activity.log_name || "")).toLowerCase();

  if (text.includes("error") || text.includes("failed") || text.includes("failure") || text.includes("deleted")) {
    return "danger";
  }

  if (text.includes("warning") || text.includes("blocked")) {
    return "warning";
  }

  if (text.includes("created") || text.includes("approved") || text.includes("completed")) {
    return "success";
  }

  if (text.includes("updated") || text.includes("viewed")) {
    return "info";
  }

  return "neutral";
}

function activityBadgeClass(activity) {
  return {
    danger: "ds-badge ds-badge-danger",
    warning: "ds-badge ds-badge-warning",
    success: "ds-badge ds-badge-success",
    info: "ds-badge ds-badge-info",
    neutral: "ds-badge ds-badge-neutral",
  }[activityLevel(activity)];
}

function formatDateTime(value) {
  if (!value) {
    return "—";
  }

  return new Date(value).toLocaleString("pt-PT", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

function formatRelativeTime(value) {
  if (!value) {
    return "";
  }

  const difference = Date.now() - new Date(value).getTime();
  const minutes = Math.floor(difference / 60000);
  const hours = Math.floor(difference / 3600000);
  const days = Math.floor(difference / 86400000);

  if (minutes < 1) {
    return "Agora";
  }

  if (minutes < 60) {
    return minutes + " min";
  }

  if (hours < 24) {
    return hours + " h";
  }

  if (days < 7) {
    return days + " d";
  }

  return new Date(value).toLocaleDateString("pt-PT");
}

function subjectName(activity) {
  if (!activity.subject_type) {
    return "Sistema";
  }

  return String(activity.subject_type).split("\\").pop();
}

function formatProperties(properties) {
  if (!properties) {
    return "Sem propriedades registadas.";
  }

  if (typeof properties === "string") {
    try {
      return JSON.stringify(JSON.parse(properties), null, 2);
    } catch {
      return properties;
    }
  }

  return JSON.stringify(properties, null, 2);
}

async function viewActivityDetails(activity) {
  showDetailsModal.value = true;
  isLoadingDetails.value = true;
  detailsError.value = "";
  detailedActivity.value = null;
  detailedProperties.value = null;

  try {
    const response = await fetch(route("systemactivity.show", { activity: activity.id }), {
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok) {
      throw new Error(response.status === 403 ? "Sem permissão para consultar este evento." : "Não foi possível carregar os detalhes.");
    }

    const payload = await response.json();
    detailedActivity.value = payload.activity;
    detailedProperties.value = payload.properties_formatted;
  } catch (error) {
    detailsError.value = error.message || "Não foi possível carregar os detalhes.";
  } finally {
    isLoadingDetails.value = false;
  }
}

function closeDetails() {
  showDetailsModal.value = false;
  detailedActivity.value = null;
  detailedProperties.value = null;
  detailsError.value = "";
}

function requestDelete(activity = null) {
  if (activity?.is_retained) return;
  deleteMode.value = activity ? "single" : "all";
  selectedActivity.value = activity;
  showDeleteConfirmation.value = true;
}

function closeDeleteConfirmation() {
  deleteMode.value = null;
  selectedActivity.value = null;
  showDeleteConfirmation.value = false;
}

function confirmDelete() {
  const routeName = deleteMode.value === "all" ? "systemactivity.destroyAll" : "systemactivity.destroy";
  const routeParameters = deleteMode.value === "single" ? { activity: selectedActivity.value.id } : undefined;

  router.delete(route(routeName, routeParameters), {
    preserveScroll: true,
    onStart: () => {
      isLoading.value = true;
    },
    onFinish: () => {
      isLoading.value = false;
      closeDeleteConfirmation();
    },
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <DocumentMagnifyingGlassIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Auditoria do sistema</p>
            <h1 class="ds-heading mt-1 text-2xl">Registo de actividade</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Eventos técnicos e administrativos para investigação, segurança e rastreabilidade.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <button type="button" class="ds-button ds-button-secondary" @click="showFilters = !showFilters">
            <FunnelIcon class="h-4 w-4" />
            Filtros
            <span v-if="hasActiveFilters" class="ds-badge ds-badge-info">{{ activeFilters.length }}</span>
            <ChevronUpIcon v-if="showFilters" class="h-4 w-4" />
            <ChevronDownIcon v-else class="h-4 w-4" />
          </button>
          <a v-if="hasPermission('export_activity_log')" :href="exportUrl" class="ds-button ds-button-secondary">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar
          </a>
          <button v-if="hasPermission('delete_activity_log')" type="button" class="ds-button ds-button-danger" @click="requestDelete()">
            <TrashIcon class="h-4 w-4" />
            Limpar registo
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 lg:grid-cols-5">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-4 sm:border-r lg:border-b-0 lg:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div>
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-1 text-xl font-bold tabular-nums text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <section v-if="showFilters" class="ds-command-surface p-4 sm:p-5">
      <div class="flex flex-col gap-4 border-b border-[var(--ds-border)] pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p class="ds-kicker">Pesquisa estruturada</p>
          <h2 class="ds-heading mt-2 text-base">Filtrar eventos</h2>
        </div>
        <div v-if="hasActiveFilters" class="flex flex-wrap gap-2">
          <span v-for="filter in activeFilters" :key="filter.key" class="ds-badge ds-badge-info">
            {{ filter.label }}: {{ filter.value }}
            <button type="button" class="ml-1" :title="'Remover filtro ' + filter.label" @click="clearFilter(filter.key)">
              <XMarkIcon class="h-3.5 w-3.5" />
            </button>
          </span>
        </div>
      </div>

      <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div>
          <label class="ds-field-label">Nome do log</label>
          <ComboboxEnhanced v-model="filters.log_name" class="mt-2" :load-options="loadLogNames" placeholder="Todos os logs" />
        </div>
        <div>
          <label class="ds-field-label">Ator</label>
          <ComboboxEnhanced v-model="filters.causer_id" class="mt-2" :load-options="loadCausers" placeholder="Todos os utilizadores" />
        </div>
        <div>
          <label class="ds-field-label">Tipo de entidade</label>
          <ComboboxEnhanced v-model="filters.subject_type" class="mt-2" :load-options="loadSubjects" placeholder="Todas as entidades" />
        </div>
        <div>
          <label class="ds-field-label">Evento</label>
          <ComboboxEnhanced v-model="filters.event" class="mt-2" :load-options="loadEvents" placeholder="Todos os eventos" />
        </div>
        <div>
          <label class="ds-field-label">Propriedade</label>
          <ComboboxEnhanced v-model="filters.property" class="mt-2" :load-options="loadProperties" placeholder="Qualquer propriedade" />
        </div>
        <div>
          <label for="activity-description" class="ds-field-label">Descrição</label>
          <BaseInput id="activity-description" v-model="filters.description" type="search" class="ds-field mt-2" placeholder="Pesquisar texto do evento" />
        </div>
        <div>
          <label for="activity-start-date" class="ds-field-label">Data inicial</label>
          <DateTimePicker id="activity-start-date" v-model="filters.start_date" type="date" class="ds-field mt-2" />
        </div>
        <div>
          <label for="activity-end-date" class="ds-field-label">Data final</label>
          <DateTimePicker id="activity-end-date" v-model="filters.end_date" type="date" class="ds-field mt-2" />
        </div>
        <div class="md:col-span-2">
          <label for="activity-batch" class="ds-field-label">UUID do lote</label>
          <BaseInput id="activity-batch" v-model="filters.batch_uuid" type="text" class="ds-field mt-2 font-mono" placeholder="Identificador exacto do lote" />
        </div>
        <div>
          <label for="activity-page-size" class="ds-field-label">Registos por página</label>
          <BaseSelect id="activity-page-size" v-model.number="filters.per_page" class="ds-field mt-2">
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </BaseSelect>
        </div>
      </div>

      <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-[var(--ds-border)] pt-4">
        <button type="button" class="ds-button ds-button-secondary" @click="resetFilters">
          <XMarkIcon class="h-4 w-4" />
          Limpar
        </button>
        <button type="button" class="ds-button ds-button-primary" :disabled="isLoading" @click="applyFilters">
          <ArrowPathIcon v-if="isLoading" class="h-4 w-4 animate-spin" />
          <MagnifyingGlassIcon v-else class="h-4 w-4" />
          Aplicar filtros
        </button>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">Trilho de auditoria</p>
          <h2 class="ds-heading mt-2 text-lg">Eventos registados</h2>
        </div>
        <span class="ds-badge ds-badge-neutral">{{ props.record.from || 0 }}–{{ props.record.to || 0 }} de {{ totalActivities }}</span>
      </div>

      <div class="overflow-x-auto">
        <DataTable class="min-w-full">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-heading px-5 py-3 text-left">Evento</th>
              <th class="ds-table-heading px-4 py-3 text-left">Descrição</th>
              <th class="ds-table-heading px-4 py-3 text-left">Ator</th>
              <th class="ds-table-heading px-4 py-3 text-left">Entidade</th>
              <th class="ds-table-heading px-4 py-3 text-left">Data</th>
              <th class="ds-table-heading px-5 py-3 text-right"><span class="sr-only">Acções</span></th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="activity in rows" :key="activity.id" class="ds-table-row">
              <td class="ds-table-cell whitespace-nowrap px-5 py-4">
                <span :class="activityBadgeClass(activity)">{{ activity.event || "registo" }}</span>
                <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-muted)]">{{ activity.log_name || "system" }}</p>
              </td>
              <td class="ds-table-cell max-w-lg px-4 py-4">
                <p class="line-clamp-2 text-sm font-bold text-[var(--ds-text)]">{{ activity.description }}</p>
                <p v-if="activity.batch_uuid" class="mt-1 truncate font-mono text-xs text-[var(--ds-text-muted)]">{{ activity.batch_uuid }}</p>
              </td>
              <td class="ds-table-cell px-4 py-4">
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ activity.causer?.name || "Sistema" }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ activity.causer?.email || "Evento automático" }}</p>
              </td>
              <td class="ds-table-cell whitespace-nowrap px-4 py-4">
                <p class="text-sm font-semibold text-[var(--ds-text)]">{{ subjectName(activity) }}</p>
                <p class="mt-1 font-mono text-xs text-[var(--ds-text-muted)]">{{ activity.subject_id || "—" }}</p>
              </td>
              <td class="ds-table-cell whitespace-nowrap px-4 py-4">
                <p class="text-sm font-semibold text-[var(--ds-text)]">{{ formatDateTime(activity.created_at) }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ formatRelativeTime(activity.created_at) }}</p>
              </td>
              <td class="ds-table-cell px-5 py-4">
                <div class="flex justify-end gap-1">
                  <button type="button" class="ds-icon-button" title="Ver detalhes" @click="viewActivityDetails(activity)">
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <span v-if="activity.is_retained" class="ds-badge" title="Este histórico permanece conservado">Conservado</span>
                  <button v-if="hasPermission('delete_activity_log') && !activity.is_retained" type="button" class="ds-icon-button hover:!text-red-600" title="Eliminar evento" @click="requestDelete(activity)">
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="6" class="px-5 py-12 text-center">
                <DocumentMagnifyingGlassIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
                <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum evento encontrado</p>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste os filtros para alargar a pesquisa.</p>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="props.record.links?.length > 3" class="flex flex-col gap-3 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-[var(--ds-text-muted)]">Página {{ props.record.current_page }} de {{ props.record.last_page }}</p>
        <nav class="flex flex-wrap gap-1" aria-label="Paginação">
          <template v-for="link in props.record.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              preserve-scroll
              class="ds-button min-h-9 px-3 py-1.5"
              :class="link.active ? 'ds-button-primary' : 'ds-button-ghost'"
              v-html="link.label"
            />
            <span v-else class="ds-button min-h-9 cursor-not-allowed px-3 py-1.5 opacity-40" v-html="link.label" />
          </template>
        </nav>
      </div>
    </section>

    <TransitionRoot :show="showDetailsModal" as="template">
      <Dialog as="div" class="relative z-50" @close="closeDetails">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/45" />
        </TransitionChild>
        <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6">
          <div class="flex min-h-full items-center justify-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-2" enter-to="opacity-100 translate-y-0" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0" leave-to="opacity-0 translate-y-2">
              <DialogPanel class="ds-floating-panel w-full max-w-4xl overflow-hidden">
                <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
                  <div>
                    <p class="ds-kicker">Evidência de auditoria</p>
                    <DialogTitle class="ds-heading mt-2 text-lg">Detalhes do evento</DialogTitle>
                  </div>
                  <button type="button" class="ds-icon-button" title="Fechar" @click="closeDetails">
                    <XMarkIcon class="h-4 w-4" />
                  </button>
                </div>

                <div v-if="isLoadingDetails" class="flex items-center justify-center gap-3 px-6 py-16 text-sm font-semibold text-[var(--ds-text-muted)]">
                  <ArrowPathIcon class="h-5 w-5 animate-spin" />
                  A carregar detalhes...
                </div>
                <div v-else-if="detailsError" class="px-6 py-12 text-center">
                  <ExclamationTriangleIcon class="mx-auto h-8 w-8 text-red-600" />
                  <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">{{ detailsError }}</p>
                </div>
                <div v-else-if="detailedActivity" class="max-h-[72vh] space-y-6 overflow-y-auto p-5 sm:p-6">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <span :class="activityBadgeClass(detailedActivity)">{{ detailedActivity.event || "registo" }}</span>
                      <p class="mt-3 text-base font-bold text-[var(--ds-text)]">{{ detailedActivity.description }}</p>
                    </div>
                    <p class="font-mono text-xs font-semibold text-[var(--ds-text-muted)]">#{{ detailedActivity.id }}</p>
                  </div>

                  <dl class="grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 lg:grid-cols-4">
                    <div class="border-b border-[var(--ds-border)] p-4 sm:border-r">
                      <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Log</dt>
                      <dd class="mt-2 font-mono text-sm font-bold text-[var(--ds-text)]">{{ detailedActivity.log_name || "system" }}</dd>
                    </div>
                    <div class="border-b border-[var(--ds-border)] p-4 lg:border-r">
                      <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Registado em</dt>
                      <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDateTime(detailedActivity.created_at) }}</dd>
                    </div>
                    <div class="border-b border-[var(--ds-border)] p-4 sm:border-r sm:border-b-0">
                      <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Entidade</dt>
                      <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ subjectName(detailedActivity) }} · {{ detailedActivity.subject_id || "—" }}</dd>
                    </div>
                    <div class="p-4">
                      <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Lote</dt>
                      <dd class="mt-2 break-all font-mono text-xs font-bold text-[var(--ds-text)]">{{ detailedActivity.batch_uuid || "Sem lote" }}</dd>
                    </div>
                  </dl>

                  <section>
                    <p class="ds-kicker">Ator</p>
                    <div class="mt-3 flex items-center gap-3 border-y border-[var(--ds-border)] py-4">
                      <UserGroupIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
                      <div>
                        <p class="text-sm font-bold text-[var(--ds-text)]">{{ detailedActivity.causer?.name || "Sistema" }}</p>
                        <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ detailedActivity.causer?.email || "Evento gerado automaticamente" }}</p>
                      </div>
                    </div>
                  </section>

                  <section v-if="detailedActivity.subject">
                    <p class="ds-kicker">Objecto afectado</p>
                    <pre class="mt-3 max-h-64 overflow-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 text-xs leading-6 text-[var(--ds-text)]">{{ formatProperties(detailedActivity.subject) }}</pre>
                  </section>

                  <section>
                    <p class="ds-kicker">Propriedades registadas</p>
                    <pre class="mt-3 max-h-80 overflow-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 text-xs leading-6 text-[var(--ds-text)]">{{ formatProperties(detailedProperties) }}</pre>
                  </section>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <ConfirmDialog
      v-if="showDeleteConfirmation"
      :title="deleteMode === 'all' ? 'Limpar eventos não conservados?' : 'Eliminar este evento?'"
      :description="deleteMode === 'all' ? 'Remove os eventos elegíveis para limpeza. O histórico conservado de contas, qualificações e adesões permanece disponível.' : selectedActivity?.description"
      variant="danger"
      confirm="Eliminar"
      cancel="Cancelar"
      @canceled="closeDeleteConfirmation"
      @confirmed="confirmDelete"
    />
  </div>
</template>
