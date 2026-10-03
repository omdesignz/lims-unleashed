<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import Pagination from "@/Components/pagination.vue";
import { computed, reactive, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Download as ArrowDownTrayIcon,
  ArrowLeft as ArrowLeftIcon,
  RefreshCw as ArrowPathIcon,
  ChevronDown as ChevronDownIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  Clock as ClockIcon,
  Funnel as FunnelIcon,
  User as UserIcon,
  X as XMarkIcon,
} from "@lucide/vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  certificate: {
    type: Object,
    default: () => ({}),
  },
  logs: {
    type: Object,
    default: () => ({ data: [], links: [], total: 0 }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const expandedLogs = ref({});
const localFilters = reactive({
  date_from: props.filters?.date_from || "",
  date_to: props.filters?.date_to || "",
  causer_id: props.filters?.causer_id || "",
  change_type: props.filters?.change_type || "",
  entity_type: "",
});

const logRows = computed(() => props.logs?.data ?? []);

const uniqueUsers = computed(() => {
  const users = new Map();
  logRows.value.forEach((log) => {
    if (log.causer?.id) {
      users.set(log.causer.id, log.causer);
    }
  });
  return Array.from(users.values());
});

const uniqueActions = computed(() => {
  return Array.from(
    new Set(logRows.value.map((log) => log.action).filter(Boolean)),
  ).sort();
});

const uniqueEntityTypes = computed(() => {
  return Array.from(
    new Set(logRows.value.map((log) => log.subject_type).filter(Boolean)),
  ).sort();
});

const filteredLogs = computed(() => {
  const fromDate = localFilters.date_from
    ? new Date(`${localFilters.date_from}T00:00:00`)
    : null;
  const toDate = localFilters.date_to
    ? new Date(`${localFilters.date_to}T23:59:59`)
    : null;

  return logRows.value.filter((log) => {
    const loggedAt = log.created_at ? new Date(log.created_at) : null;
    const matchesUser =
      !localFilters.causer_id ||
      String(log.causer?.id ?? "") === String(localFilters.causer_id);
    const matchesAction =
      !localFilters.change_type || log.action === localFilters.change_type;
    const matchesEntity =
      !localFilters.entity_type || log.subject_type === localFilters.entity_type;
    const matchesFrom = !fromDate || (loggedAt && loggedAt >= fromDate);
    const matchesTo = !toDate || (loggedAt && loggedAt <= toDate);

    return matchesUser && matchesAction && matchesEntity && matchesFrom && matchesTo;
  });
});

const hasActiveFilters = computed(() => {
  return Object.values(localFilters).some(Boolean);
});

const activeFilterCount = computed(() => {
  return Object.values(localFilters).filter(Boolean).length;
});

const complianceRate = computed(() => {
  if (!logRows.value.length) {
    return 100;
  }

  const compliantLogs = logRows.value.filter((log) => {
    return Boolean(
      log.properties?.change_reason ||
      (log.properties?.iso_section && log.properties?.risk_assessment),
    );
  }).length;

  return Math.round((compliantLogs / logRows.value.length) * 100);
});

const auditPeriod = computed(() => {
  const dates = logRows.value
    .map((log) => new Date(log.created_at))
    .filter((date) => !Number.isNaN(date.getTime()));

  if (!dates.length) {
    return "Sem período";
  }

  const oldest = new Date(Math.min(...dates));
  const newest = new Date(Math.max(...dates));
  return `${formatDate(oldest)} - ${formatDate(newest)}`;
});

const auditMetrics = computed(() => [
  {
    label: "Eventos no trilho",
    value: props.logs?.total ?? logRows.value.length,
    note: "registos auditáveis",
  },
  {
    label: "Conformidade",
    value: `${complianceRate.value}%`,
    note: "metadados ISO presentes",
  },
  {
    label: "Utilizadores",
    value: uniqueUsers.value.length,
    note: "intervenientes nesta página",
  },
  {
    label: "Período visível",
    value: auditPeriod.value,
    note: "intervalo carregado",
  },
]);

function actionLabel(action) {
  const labels = {
    CREATED: "Criado",
    UPDATED: "Actualizado",
    DELETED: "Eliminado",
    RESTORED: "Reposto",
    APPROVED: "Aprovado",
    REJECTED: "Rejeitado",
    REVISION_CREATED: "Revisão criada",
    REVISION_RESTORE: "Revisão reposta",
  };

  return labels[action] || action || "Evento";
}

function actionDot(action) {
  const tones = {
    CREATED: "lims-status-dot-release",
    UPDATED: "lims-status-dot-instrument",
    DELETED: "lims-status-dot-critical",
    RESTORED: "lims-status-dot-hold",
    APPROVED: "lims-status-dot-release",
    REJECTED: "lims-status-dot-critical",
    REVISION_CREATED: "lims-status-dot-instrument",
    REVISION_RESTORE: "lims-status-dot-hold",
  };

  return tones[action] || "lims-status-dot-instrument";
}

function entityLabel(subjectType) {
  const entities = {
    "App\\Models\\QualityCertificate": "Certificado",
    "App\\Models\\QualityCertificateRevision": "Revisão",
    "App\\Models\\CollectionProduct": "Colheita",
    "App\\Models\\Result": "Resultado",
  };

  return entities[subjectType] || subjectType?.split("\\").pop() || "Entidade";
}

function formatDate(date) {
  if (!date) {
    return "Não registada";
  }

  return new Date(date).toLocaleDateString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function formatDateTime(date) {
  if (!date) {
    return "Não registada";
  }

  return new Date(date).toLocaleString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
}

function formatFieldLabel(field) {
  const labels = {
    status: "Estado",
    obs: "Observações",
    validated_by: "Validado por",
    validated_at: "Data de validação",
    change_reason: "Motivo da mudanca",
    iso_section: "Secção ISO",
    risk_assessment: "Avaliacao de risco",
  };

  return (
    labels[field] ||
    String(field)
      .replaceAll("_", " ")
      .replace(/\b\w/g, (letter) => letter.toUpperCase())
  );
}

function formatValue(value) {
  if (value === null || value === undefined || value === "") {
    return "Não registado";
  }

  if (typeof value === "boolean") {
    return value ? "Sim" : "Não";
  }

  if (typeof value === "object") {
    return JSON.stringify(value);
  }

  return String(value);
}

function propertyEntries(properties) {
  if (!properties || typeof properties !== "object") {
    return [];
  }

  return Object.entries(properties).filter(
    ([key]) => !["old", "attributes"].includes(key),
  );
}

function changedFieldEntries(log) {
  const before = log.properties?.old ?? {};
  const after = log.properties?.attributes ?? {};
  const fields = new Set([...Object.keys(before), ...Object.keys(after)]);

  return Array.from(fields).map((field) => ({
    field,
    before: before[field],
    after: after[field],
  }));
}

function toggleLogDetails(logId) {
  expandedLogs.value[logId] = !expandedLogs.value[logId];
}

function resetFilters() {
  Object.keys(localFilters).forEach((key) => {
    localFilters[key] = "";
  });
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="route('qualitycertificates.iso-revisions.index', certificate.id)"
            class="ds-table-action -ml-2 mb-3"
          >
            <ArrowLeftIcon class="h-4 w-4" /> Voltar ao histórico </Link>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Evidência ISO/IEC 17025</p>
            <span class="ds-chip font-mono">{{ certificate.code || "Sem código" }}</span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">Trilho de auditoria</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm"> Sequencia cronologica de alterações, utilizadores e metadados que sustentam a integridade do certificado. </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button type="button" class="ds-button ds-button-secondary" @click="router.reload()">
            <ArrowPathIcon class="h-4 w-4" />
            Actualizar
          </button>
          <a
            :href="route('qualitycertificates.iso-revisions.export', certificate.id)"
            class="ds-button ds-button-primary"
          >
            <ArrowDownTrayIcon class="h-4 w-4" /> Exportar histórico </a>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in auditMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 truncate text-lg" :title="String(metric.value)">
            {{ metric.value }}
          </dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.note }}</p>
        </div>
      </dl>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
          <p class="ds-kicker">Filtros locais</p>
          <h2 class="ds-heading mt-2 text-lg">Refinar eventos carregados</h2>
          <p class="ds-copy mt-1 text-sm">
            {{ filteredLogs.length }} de {{ logRows.length }} evento(s) visível(is) </p>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="activeFilterCount" class="ds-chip">
            <FunnelIcon class="h-3.5 w-3.5" />
            {{ activeFilterCount }} filtro(s)
          </span>
          <button
            v-if="hasActiveFilters"
            type="button"
            class="ds-button ds-button-secondary"
            @click="resetFilters"
          >
            <XMarkIcon class="h-4 w-4" />
            Limpar
          </button>
        </div>
      </div>

      <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="ds-field-group">
          <label class="ds-field-label" for="audit-date-from">Desde</label>
          <DateTimePicker id="audit-date-from" v-model="localFilters.date_from" type="date" class="ds-field" />
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="audit-date-to">Até</label>
          <DateTimePicker id="audit-date-to" v-model="localFilters.date_to" type="date" class="ds-field" />
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="audit-user">Utilizador</label>
          <BaseSelect id="audit-user" v-model="localFilters.causer_id" class="ds-field">
            <option value="">Todos</option>
            <option v-for="user in uniqueUsers" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </BaseSelect>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="audit-action">Acção</label>
          <BaseSelect id="audit-action" v-model="localFilters.change_type" class="ds-field">
            <option value="">Todas</option>
            <option v-for="action in uniqueActions" :key="action" :value="action">
              {{ actionLabel(action) }}
            </option>
          </BaseSelect>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="audit-entity">Entidade</label>
          <BaseSelect id="audit-entity" v-model="localFilters.entity_type" class="ds-field">
            <option value="">Todas</option>
            <option v-for="entity in uniqueEntityTypes" :key="entity" :value="entity">
              {{ entityLabel(entity) }}
            </option>
          </BaseSelect>
        </div>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">Sequencia de eventos</p>
          <h2 class="ds-heading mt-2 text-base">Actividade auditável</h2>
        </div>
        <span class="ds-chip">{{ filteredLogs.length }} evento(s)</span>
      </div>

      <div v-if="filteredLogs.length">
        <div class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="log in filteredLogs" :key="log.id" class="px-5 py-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center gap-2 text-xs font-bold text-[var(--ds-text)]">
                    <span :class="['lims-status-dot', actionDot(log.action)]" />
                    {{ actionLabel(log.action) }}
                  </span>
                  <span class="ds-chip">{{ entityLabel(log.subject_type) }}</span>
                </div>
                <p class="ds-heading mt-3 text-sm">
                  {{ log.description || "Evento registado" }}
                </p>
                <p class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-[var(--ds-text-muted)]">
                  <UserIcon class="h-3.5 w-3.5" />
                  {{ log.causer?.name || "Sistema" }}
                </p>
                <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-[var(--ds-text-muted)]">
                  <ClockIcon class="h-3.5 w-3.5" />
                  {{ formatDateTime(log.created_at) }}
                </p>
              </div>
              <button
                type="button"
                class="ds-icon-button"
                :aria-expanded="Boolean(expandedLogs[log.id])"
                title="Ver detalhes"
                @click="toggleLogDetails(log.id)"
              >
                <ChevronDownIcon :class="['h-5 w-5 transition-transform', expandedLogs[log.id] ? 'rotate-180' : '']" />
              </button>
            </div>

            <div v-if="expandedLogs[log.id]" class="mt-4 border-t border-[var(--ds-border)] pt-4">
              <div v-if="changedFieldEntries(log).length" class="grid gap-2">
                <div
                  v-for="change in changedFieldEntries(log)"
                  :key="change.field"
                  class="ds-command-toolbar p-3"
                >
                  <p class="ds-table-heading">{{ formatFieldLabel(change.field) }}</p>
                  <p class="mt-2 text-xs font-semibold text-[var(--ds-text-muted)]">
                    {{ formatValue(change.before) }} to {{ formatValue(change.after) }}
                  </p>
                </div>
              </div>
              <dl v-else class="grid gap-2">
                <div
                  v-for="([key, value]) in propertyEntries(log.properties)"
                  :key="key"
                  class="ds-command-toolbar p-3"
                >
                  <dt class="ds-table-heading">{{ formatFieldLabel(key) }}</dt>
                  <dd class="mt-2 break-words text-xs font-semibold text-[var(--ds-text)]">
                    {{ formatValue(value) }}
                  </dd>
                </div>
              </dl>
            </div>
          </article>
        </div>

        <div class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-4 text-left">Evento</th>
                <th class="ds-table-heading px-4 py-4 text-left">Entidade</th>
                <th class="ds-table-heading px-4 py-4 text-left">Utilizador</th>
                <th class="ds-table-heading px-4 py-4 text-left">Data</th>
                <th class="ds-table-heading px-5 py-4 text-right">Detalhes</th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <template v-for="log in filteredLogs" :key="log.id">
                <tr class="ds-table-row">
                  <td class="max-w-md px-5 py-4">
                    <div class="flex items-center gap-2">
                      <span :class="['lims-status-dot', actionDot(log.action)]" />
                      <span class="text-xs font-bold text-[var(--ds-text)]">{{ actionLabel(log.action) }}</span>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-[var(--ds-text-muted)]">
                      {{ log.description || "Evento registado" }}
                    </p>
                  </td>
                  <td class="ds-table-cell px-4 py-4">{{ entityLabel(log.subject_type) }}</td>
                  <td class="ds-table-cell px-4 py-4">{{ log.causer?.name || "Sistema" }}</td>
                  <td class="ds-table-cell whitespace-nowrap px-4 py-4">{{ formatDateTime(log.created_at) }}</td>
                  <td class="px-5 py-4 text-right">
                    <button
                      type="button"
                      class="ds-table-action"
                      :aria-expanded="Boolean(expandedLogs[log.id])"
                      @click="toggleLogDetails(log.id)"
                    >
                      {{ expandedLogs[log.id] ? "Ocultar" : "Abrir" }}
                      <ChevronDownIcon :class="['h-4 w-4 transition-transform', expandedLogs[log.id] ? 'rotate-180' : '']" />
                    </button>
                  </td>
                </tr>
                <tr v-if="expandedLogs[log.id]" class="ds-table-row">
                  <td colspan="5" class="px-5 py-4">
                    <div v-if="changedFieldEntries(log).length" class="grid gap-3 xl:grid-cols-2">
                      <div
                        v-for="change in changedFieldEntries(log)"
                        :key="change.field"
                        class="ds-command-toolbar p-3"
                      >
                        <p class="ds-table-heading">{{ formatFieldLabel(change.field) }}</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                          <p class="break-words text-xs font-semibold text-[var(--ds-text-muted)]">
                            Antes: {{ formatValue(change.before) }}
                          </p>
                          <p class="break-words text-xs font-semibold text-[var(--ds-text)]">
                            Depois: {{ formatValue(change.after) }}
                          </p>
                        </div>
                      </div>
                    </div>
                    <dl v-else class="grid gap-3 xl:grid-cols-2">
                      <div
                        v-for="([key, value]) in propertyEntries(log.properties)"
                        :key="key"
                        class="ds-command-toolbar p-3"
                      >
                        <dt class="ds-table-heading">{{ formatFieldLabel(key) }}</dt>
                        <dd class="mt-2 break-words text-xs font-semibold text-[var(--ds-text)]">
                          {{ formatValue(value) }}
                        </dd>
                      </div>
                    </dl>
                  </td>
                </tr>
              </template>
            </tbody>
          </DataTable>
        </div>
      </div>

      <div v-else class="ds-empty-state m-5 p-10 text-center">
        <ClipboardDocumentCheckIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" />
        <h3 class="ds-heading mt-3 text-sm">Nenhum evento corresponde aos filtros</h3>
        <p class="ds-copy mt-1 text-xs">Limpe os filtros para rever o trilho carregado.</p>
      </div>

      <div v-if="logRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
        <Pagination :links="logs.links" />
      </div>
    </section>
  </div>
</template>
