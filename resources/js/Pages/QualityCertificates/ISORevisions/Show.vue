<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RestoreRevisionModal from "./Partials/RestoreRevisionModal.vue";
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  BeakerIcon,
  ClipboardDocumentListIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  FolderOpenIcon,
  UserIcon,
  UsersIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  certificate: {
    type: Object,
    default: () => ({}),
  },
  revision: {
    type: Object,
    default: () => ({}),
  },
  snapshot: {
    type: Object,
    default: () => ({}),
  },
  differences: {
    type: Array,
    default: () => [],
  },
  relatedActivityLogs: {
    type: Array,
    default: () => [],
  },
  approvers: {
    type: Array,
    default: () => [],
  },
});

const activeTab = ref("certificate");
const restoreModalOpen = ref(false);

const certificateData = computed(() => props.snapshot?.certificate ?? {});
const relations = computed(() => props.snapshot?.relations ?? {});

const currentRevisionId = computed(() => {
  return (
    props.certificate?.current_revision?.id ||
    props.certificate?.current_revision_id ||
    (props.revision?.is_current ? props.revision.id : null)
  );
});

const revisionMetrics = computed(() => [
  {
    label: "Versão",
    value: `v${props.revision?.version || "-"}`,
    note: `revisão ${props.revision?.revision_number ?? "-"}`,
  },
  {
    label: "Data efectiva",
    value: formatDate(props.revision?.effective_date),
    note: props.revision?.is_current ? "versão corrente" : "versão historica",
  },
  {
    label: "Criado por",
    value: props.revision?.created_by?.name || "Sistema",
    note: formatDateTime(props.revision?.created_at),
  },
  {
    label: "Aprovado por",
    value: props.revision?.approved_by?.name || "Pendente",
    note: props.revision?.approved_at
      ? formatDateTime(props.revision.approved_at)
      : "sem aprovação registada",
  },
]);

const snapshotTabs = computed(() => [
  {
    id: "certificate",
    label: "Certificado",
    count: Object.keys(certificateData.value).length,
    icon: DocumentTextIcon,
  },
  {
    id: "collection",
    label: "Colheita",
    count: relationObject("collection") ? 1 : 0,
    icon: FolderOpenIcon,
  },
  {
    id: "results",
    label: "Resultados",
    count: relationResults.value.length,
    icon: BeakerIcon,
  },
  {
    id: "customer",
    label: "Cliente",
    count: relationObject("customer") ? 1 : 0,
    icon: UsersIcon,
  },
  {
    id: "product",
    label: "Produto",
    count: relationObject("product") ? 1 : 0,
    icon: DocumentDuplicateIcon,
  },
]);

const relationResults = computed(() => {
  return Array.isArray(relations.value.results) ? relations.value.results : [];
});

const activeObjectEntries = computed(() => {
  if (activeTab.value === "certificate") {
    return Object.entries(certificateData.value).filter(([key]) => key !== "obs");
  }

  if (activeTab.value === "results") {
    return [];
  }

  const relation = relationObject(activeTab.value);
  return relation && typeof relation === "object" ? Object.entries(relation) : [];
});

const revisionMetadata = computed(() => [
  {
    label: "Tipo de alteração",
    value: changeTypeLabel(props.revision?.change_type),
  },
  {
    label: "Secção ISO",
    value: props.revision?.compliance_metadata?.iso_section || "Não indicada",
  },
  {
    label: "Categoria",
    value: props.revision?.compliance_metadata?.change_category || "Não indicada",
  },
  {
    label: "Risco",
    value: props.revision?.compliance_metadata?.risk_assessment || "Não avaliado",
  },
]);

function relationObject(type) {
  if (type === "collection") {
    return relations.value.collection ?? relations.value.collection_product ?? null;
  }

  return relations.value[type] ?? null;
}

function hasDataForTab(tabId) {
  if (tabId === "certificate") {
    return Object.keys(certificateData.value).length > 0;
  }

  if (tabId === "results") {
    return relationResults.value.length > 0;
  }

  return Boolean(relationObject(tabId));
}

function changeTypeLabel(changeType) {
  const labels = {
    CREATED: "Criação",
    UPDATED: "Atualizacao",
    CORRECTED: "Correcao",
    REISSUED: "Reemissao",
    WITHDRAWN: "Retirada",
  };

  return labels[changeType] || changeType || "Alteração";
}

function changeTypeDot(changeType) {
  const tones = {
    CREATED: "lims-status-dot-release",
    UPDATED: "lims-status-dot-instrument",
    CORRECTED: "lims-status-dot-hold",
    REISSUED: "lims-status-dot-instrument",
    WITHDRAWN: "lims-status-dot-critical",
  };

  return tones[changeType] || "lims-status-dot-instrument";
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
  });
}

function formatFieldLabel(field) {
  const labels = {
    code: "Código do certificado",
    status: "Estado",
    validated_by: "Validado por",
    validated_at: "Data de validação",
    obs: "Observações",
    created_at: "Criado em",
    updated_at: "Actualizado em",
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

  if (Array.isArray(value)) {
    return value.length ? value.join(", ") : "Sem valores";
  }

  if (typeof value === "object") {
    return JSON.stringify(value);
  }

  return String(value);
}

function compareWithCurrent() {
  if (!currentRevisionId.value || currentRevisionId.value === props.revision.id) {
    return;
  }

  router.visit(
    route("qualitycertificates.iso-revisions.compare-two", {
      certificate: props.certificate.id,
      revision_a: props.revision.id,
      revision_b: currentRevisionId.value,
    }),
  );
}

function handleRevisionRestored() {
  restoreModalOpen.value = false;
  router.reload();
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
            <p class="ds-kicker">Captura imutável</p>
            <span class="ds-chip">
              <span
                :class="['lims-status-dot', revision.is_current ? 'lims-status-dot-release' : 'lims-status-dot-instrument']"
              />
              {{ revision.is_current ? "Versão actual" : "Versão historica" }}
            </span>
            <span class="ds-chip">
              <span :class="['lims-status-dot', changeTypeDot(revision.change_type)]" />
              {{ changeTypeLabel(revision.change_type) }}
            </span>
          </div>
          <h1 class="ds-heading mt-2 break-words text-2xl">
            Revisão v{{ revision.version }} - {{ certificate.code || "Certificado" }}
          </h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm"> Conteúdo, relações e metadados preservados no momento desta revisão. </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <a
            :href="route('qualitycertificates.iso-revisions.snapshot', { certificate: certificate.id, revision: revision.id })"
            target="_blank"
            rel="noopener noreferrer"
            class="ds-button ds-button-secondary"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Abrir captura JSON
          </a>
          <button
            v-if="currentRevisionId && currentRevisionId !== revision.id"
            type="button"
            class="ds-button ds-button-primary"
            @click="compareWithCurrent"
          >
            <ArrowsRightLeftIcon class="h-4 w-4" />
            Comparar com actual
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in revisionMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 truncate text-sm" :title="metric.value">
            {{ metric.value }}
          </dd>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-soft)]" :title="metric.note">
            {{ metric.note }}
          </p>
        </div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
      <div class="space-y-6">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <p class="ds-kicker">Conteúdo preservado</p>
            <h2 class="ds-heading mt-2 text-lg">Dados da captura</h2>
            <p class="ds-copy mt-1 text-sm"> Consulte cada grupo sem perder o contexto da versão. </p>
          </div>

          <nav class="overflow-x-auto border-b border-[var(--ds-border)]" aria-label="Secções da captura">
            <div class="flex min-w-max px-3 sm:px-5">
              <button
                v-for="tab in snapshotTabs"
                :key="tab.id"
                type="button"
                :class="[
                  '-mb-px flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)]',
                  activeTab === tab.id
                    ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--primary-300-rgb))] dark:text-[rgb(var(--primary-200-rgb))]'
                    : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]',
                ]"
                @click="activeTab = tab.id"
              >
                <component :is="tab.icon" class="h-4 w-4" />
                {{ tab.label }}
                <span class="ds-chip min-w-6 justify-center px-1.5 py-0.5">{{ tab.count }}</span>
              </button>
            </div>
          </nav>

          <div v-if="hasDataForTab(activeTab)" class="px-5 py-5 sm:px-6">
            <div v-if="activeTab !== 'results'" class="grid sm:grid-cols-2">
              <dl class="contents">
                <div
                  v-for="([key, value]) in activeObjectEntries"
                  :key="key"
                  class="border-b border-[var(--ds-border)] px-1 py-4 sm:px-4"
                >
                  <dt class="ds-table-heading">{{ formatFieldLabel(key) }}</dt>
                  <dd class="mt-2 break-words text-sm font-semibold text-[var(--ds-text)]">
                    {{ formatValue(value) }}
                  </dd>
                </div>
              </dl>
            </div>

            <div v-if="activeTab === 'certificate' && certificateData.obs" class="ds-command-toolbar mt-5 p-4">
              <p class="ds-table-heading">Observações</p>
              <p class="ds-copy mt-2 whitespace-pre-line text-sm">{{ certificateData.obs }}</p>
            </div>

            <div v-if="activeTab === 'results'" class="ds-table-shell overflow-x-auto">
              <DataTable class="min-w-full">
                <thead class="ds-table-head">
                  <tr>
                    <th class="ds-table-heading px-4 py-3 text-left">Parâmetro</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Resultado</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Unidade</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Tipo</th>
                  </tr>
                </thead>
                <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
                  <tr v-for="result in relationResults" :key="result.id" class="ds-table-row">
                    <td class="px-4 py-3 text-sm font-bold text-[var(--ds-text)]">
                      {{ result.parameter_label || result.parameter_id || "-" }}
                    </td>
                    <td class="ds-table-cell px-4 py-3">
                      {{ result.approved_value || result.verified_value || result.inserted_value || "-" }}
                    </td>
                    <td class="ds-table-cell px-4 py-3">{{ result.unit_label || "-" }}</td>
                    <td class="ds-table-cell px-4 py-3">{{ result.type_label || "-" }}</td>
                  </tr>
                </tbody>
              </DataTable>
            </div>
          </div>

          <div v-else class="ds-empty-state m-5 p-8 text-center">
            <DocumentDuplicateIcon class="mx-auto h-6 w-6 text-[var(--ds-text-soft)]" />
            <p class="mt-2 text-sm font-semibold text-[var(--ds-text-muted)]"> Sem dados preservados nesta secção. </p>
          </div>
        </section>

        <section v-if="differences.length" class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <p class="ds-kicker">Desvio da versão actual</p>
            <h2 class="ds-heading mt-2 text-lg">{{ differences.length }} alteração(oes)</h2>
          </div>
          <div class="divide-y divide-[var(--ds-border)]">
            <article
              v-for="difference in differences"
              :key="difference.field || difference.category"
              class="px-5 py-4 sm:px-6"
            >
              <div class="flex items-center justify-between gap-3">
                <h3 class="ds-heading text-sm">
                  {{ difference.label || formatFieldLabel(difference.field) }}
                </h3>
                <span class="ds-chip">
                  <span class="lims-status-dot lims-status-dot-hold" />
                  Alterado
                </span>
              </div>
              <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="ds-command-toolbar p-3">
                  <p class="ds-table-heading">Nesta revisão</p>
                  <p class="mt-2 break-words text-sm font-semibold text-[var(--ds-text)]">
                    {{ formatValue(difference.revision_value ?? difference.old_value) }}
                  </p>
                </div>
                <div class="ds-command-toolbar p-3">
                  <p class="ds-table-heading">Valor actual</p>
                  <p class="mt-2 break-words text-sm font-semibold text-[var(--ds-text)]">
                    {{ formatValue(difference.current_value ?? difference.new_value) }}
                  </p>
                </div>
              </div>
            </article>
          </div>
        </section>
      </div>

      <aside class="space-y-6">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Metadados ISO</p>
            <h2 class="ds-heading mt-2 text-base">Classificação da revisão</h2>
          </div>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div
              v-for="item in revisionMetadata"
              :key="item.label"
              class="flex items-start justify-between gap-4 px-5 py-3"
            >
              <dt class="text-xs font-bold text-[var(--ds-text-muted)]">{{ item.label }}</dt>
              <dd class="max-w-[12rem] break-words text-right text-xs font-bold text-[var(--ds-text)]">
                {{ item.value }}
              </dd>
            </div>
          </dl>
          <div class="border-t border-[var(--ds-border)] px-5 py-4">
            <p class="ds-table-heading">Motivo da mudanca</p>
            <p class="ds-copy mt-2 text-xs">{{ revision.change_reason || "Não registado." }}</p>
          </div>
        </section>

        <section class="ds-card p-5">
          <p class="ds-kicker">Comandos</p>
          <h2 class="ds-heading mt-2 text-base">Acções da revisão</h2>
          <div class="mt-4 grid gap-2">
            <button
              v-if="currentRevisionId && currentRevisionId !== revision.id"
              type="button"
              class="ds-button ds-button-secondary justify-start"
              @click="compareWithCurrent"
            >
              <ArrowsRightLeftIcon class="h-4 w-4" />
              Comparar com actual
            </button>
            <button
              v-if="!revision.is_current"
              type="button"
              class="ds-button ds-button-secondary justify-start"
              @click="restoreModalOpen = true"
            >
              <ArrowPathIcon class="h-4 w-4" /> Repor esta versão </button>
            <Link
              :href="route('qualitycertificates.iso-revisions.index', certificate.id)"
              class="ds-button ds-button-secondary justify-start"
            >
              <ClipboardDocumentListIcon class="h-4 w-4" />
              Histórico de revisões
            </Link>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Rastreabilidade</p>
            <h2 class="ds-heading mt-2 text-base">Actividade relacionada</h2>
          </div>
          <ol v-if="relatedActivityLogs.length" class="divide-y divide-[var(--ds-border)]">
            <li
              v-for="activity in relatedActivityLogs"
              :key="activity.id"
              class="flex gap-3 px-5 py-4"
            >
              <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
                <UserIcon class="h-4 w-4" />
              </span>
              <div class="min-w-0">
                <p class="text-xs font-bold text-[var(--ds-text)]">
                  {{ activity.description || activity.action || "Evento registado" }}
                </p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                  {{ activity.causer?.name || "Sistema" }} - {{ formatDateTime(activity.created_at) }}
                </p>
              </div>
            </li>
          </ol>
          <div v-else class="ds-empty-state m-5 p-5 text-center">
            <p class="text-xs font-semibold text-[var(--ds-text-muted)]">Sem actividade associada.</p>
          </div>
        </section>
      </aside>
    </div>

    <RestoreRevisionModal
      :show="restoreModalOpen"
      :revision="revision"
      :certificate="certificate"
      :approvers="approvers"
      @close="restoreModalOpen = false"
      @restored="handleRevisionRestored"
    />
  </div>
</template>
