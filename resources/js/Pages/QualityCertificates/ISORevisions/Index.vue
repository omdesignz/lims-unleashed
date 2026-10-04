<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Pagination from "@/Components/pagination.vue";
import RestoreRevisionModal from "./Partials/RestoreRevisionModal.vue";
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
  Download as ArrowDownTrayIcon,
  RefreshCw as ArrowPathIcon,
  ArrowLeftRight as ArrowsRightLeftIcon,
  CircleCheck as CheckCircleIcon,
  ClipboardList as ClipboardDocumentListIcon,
  Clock as ClockIcon,
  FileCheck as DocumentCheckIcon,
  Copy as DocumentDuplicateIcon,
  FilePlus as DocumentPlusIcon,
  Eye as EyeIcon,
  User as UserIcon,
} from "@lucide/vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  certificate: {
    type: Object,
    default: () => ({}),
  },
  revisions: {
    type: Object,
    default: () => ({ data: [], links: [], total: 0 }),
  },
  currentRevision: {
    type: Object,
    default: null,
  },
  activityLogs: {
    type: Array,
    default: () => [],
  },
  complianceStats: {
    type: Object,
    default: () => ({}),
  },
  approvers: {
    type: Array,
    default: () => [],
  },
});

const selectedRevisions = ref([]);
const selectedRevision = ref(null);
const restoreModalOpen = ref(false);

const revisionRows = computed(() => props.revisions?.data ?? []);

const activeRevision = computed(() => {
  return (
    props.currentRevision ??
    props.certificate?.current_revision ??
    revisionRows.value.find((revision) => revision.is_current) ??
    revisionRows.value[0] ??
    null
  );
});

const revisionMetrics = computed(() => [
  {
    label: "Revisões controladas",
    value: props.revisions?.total ?? revisionRows.value.length,
    note: "histórico do certificado",
  },
  {
    label: "Conformidade documental",
    value: `${props.complianceStats?.compliance_rate ?? 100}%`,
    note: "eventos com metadados ISO",
  },
  {
    label: "Eventos auditáveis",
    value: props.complianceStats?.activity_count ?? props.activityLogs.length,
    note: "registos de actividade",
  },
  {
    label: "Versão efectiva",
    value: `v${activeRevision.value?.version ?? "1.0"}`,
    note: activeRevision.value?.is_current ? "versão corrente" : "versão registada",
  },
]);

const currentRevisionDetails = computed(() => [
  {
    label: "Versão",
    value: `v${activeRevision.value?.version ?? "1.0"}`,
  },
  {
    label: "Número da revisão",
    value: activeRevision.value?.revision_number ?? "1",
  },
  {
    label: "Data efectiva",
    value: formatDate(
      activeRevision.value?.effective_date || props.certificate?.validated_at,
    ),
  },
  {
    label: "Aprovado por",
    value:
      activeRevision.value?.approved_by?.name ||
      props.certificate?.validated_by ||
      "Aprovação pendente",
  },
]);

const selectedCountLabel = computed(() => {
  if (!selectedRevisions.value.length) {
    return "Seleccione duas revisões para comparar";
  }

  return `${selectedRevisions.value.length}/2 revisões seleccionadas`;
});

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

function toggleRevision(revisionId) {
  const selectedIndex = selectedRevisions.value.indexOf(revisionId);

  if (selectedIndex >= 0) {
    selectedRevisions.value.splice(selectedIndex, 1);
    return;
  }

  if (selectedRevisions.value.length < 2) {
    selectedRevisions.value.push(revisionId);
  }
}

function compareSelected() {
  if (selectedRevisions.value.length !== 2) {
    return;
  }

  router.visit(
    route("qualitycertificates.iso-revisions.compare-two", {
      certificate: props.certificate.id,
      revision_a: selectedRevisions.value[0],
      revision_b: selectedRevisions.value[1],
    }),
  );
}

function compareWithCurrent(revision) {
  if (!activeRevision.value?.id || revision.id === activeRevision.value.id) {
    return;
  }

  router.visit(
    route("qualitycertificates.iso-revisions.compare-two", {
      certificate: props.certificate.id,
      revision_a: revision.id,
      revision_b: activeRevision.value.id,
    }),
  );
}

function openRestoreModal(revision) {
  selectedRevision.value = revision;
  restoreModalOpen.value = true;
}

function closeRestoreModal() {
  restoreModalOpen.value = false;
  selectedRevision.value = null;
}

function handleRevisionRestored() {
  closeRestoreModal();
  router.reload();
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :trail="[{ title: 'Certificado', url: route('qualitycertificates.show', { certificate: certificate.id }) }, { title: 'Controlo de revisões' }]" title="Controlo de revisões" lede="Histórico imutável de alterações, aprovação, comparação e reposição do certificado para auditoria e rastreabilidade.">
      <template #badges>
        <span class="ds-chip font-mono">{{ certificate.code || "Sem código" }}</span>
      </template>
      <template #actions>
        <a
          :href="route('qualitycertificates.iso-revisions.export', certificate.id)"
          class="ds-button ds-button-secondary"
        >
          <ArrowDownTrayIcon class="h-4 w-4" /> Exportar histórico </a>
        <Link
          :href="route('qualitycertificates.iso-revisions.create', certificate.id)"
          class="ds-button ds-button-primary"
        >
          <DocumentPlusIcon class="h-4 w-4" /> Nova revisão </Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in revisionMetrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-text">{{ metric.value }}</dd>
      </div>
    </dl>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
      <div class="space-y-6">
        <section class="ds-command-surface overflow-hidden">
          <div class="flex items-start gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
              <DocumentCheckIcon class="h-5 w-5" />
            </div>
            <div>
              <p class="ds-kicker">Versão efectiva</p>
              <h2 class="ds-heading mt-2 text-base">
                v{{ activeRevision?.version || "1.0" }}
              </h2>
              <p class="ds-copy mt-1 text-xs">
                {{ activeRevision?.change_reason || "Emissão inicial do certificado." }}
              </p>
            </div>
          </div>
          <dl class="grid sm:grid-cols-2 lg:grid-cols-4">
            <div
              v-for="detail in currentRevisionDetails"
              :key="detail.label"
              class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 lg:border-b-0"
            >
              <dt class="ds-table-heading">{{ detail.label }}</dt>
              <dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">
                {{ detail.value }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="ds-table-shell">
          <div class="ds-table-summary flex-col px-5 py-4 sm:flex-row sm:px-6">
            <div>
              <p class="ds-kicker">Histórico controlado</p>
              <h2 class="ds-heading mt-2 text-base">Revisões do certificado</h2>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ selectedCountLabel }}
              </p>
            </div>
            <button
              type="button"
              class="ds-button ds-button-secondary"
              :disabled="selectedRevisions.length !== 2"
              @click="compareSelected"
            >
              <ArrowsRightLeftIcon class="h-4 w-4" />
              Comparar seleccionadas
            </button>
          </div>

          <div v-if="revisionRows.length">
            <div class="divide-y divide-[var(--ds-border)] lg:hidden">
              <article
                v-for="revision in revisionRows"
                :key="revision.id"
                class="space-y-4 px-5 py-5"
              >
                <div class="flex items-start justify-between gap-3">
                  <button
                    type="button"
                    class="flex min-w-0 items-start gap-3 text-left"
                    :aria-pressed="selectedRevisions.includes(revision.id)"
                    @click="toggleRevision(revision.id)"
                  >
                    <CheckboxInput
                      type="checkbox"
                      class="ds-checkbox mt-1 pointer-events-none"
                      :checked="selectedRevisions.includes(revision.id)"
                      tabindex="-1"
                    />
                    <span class="min-w-0">
                      <span class="flex flex-wrap items-center gap-2">
                        <span class="ds-heading text-sm">v{{ revision.version }}</span>
                        <span class="ds-chip">
                          <span :class="['lims-status-dot', changeTypeDot(revision.change_type)]" />
                          {{ changeTypeLabel(revision.change_type) }}
                        </span>
                      </span>
                      <span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">
                        Revisão {{ revision.revision_number }} - {{ formatDate(revision.effective_date) }}
                      </span>
                    </span>
                  </button>
                  <span v-if="revision.is_current" class="ds-chip">Actual</span>
                </div>

                <p class="ds-copy text-sm">{{ revision.change_reason || "Sem motivo registado." }}</p>

                <dl class="grid gap-2 sm:grid-cols-2">
                  <div class="ds-command-toolbar px-3 py-2">
                    <dt class="ds-table-heading">Criado por</dt>
                    <dd class="mt-1 text-xs font-bold text-[var(--ds-text)]">
                      {{ revision.created_by?.name || "Sistema" }}
                    </dd>
                  </div>
                  <div class="ds-command-toolbar px-3 py-2">
                    <dt class="ds-table-heading">Aprovado por</dt>
                    <dd class="mt-1 text-xs font-bold text-[var(--ds-text)]">
                      {{ revision.approved_by?.name || "Pendente" }}
                    </dd>
                  </div>
                </dl>

                <div class="flex flex-wrap items-center gap-1 border-t border-[var(--ds-border)] pt-3">
                  <Link
                    :href="route('qualitycertificates.iso-revisions.show', { certificate: certificate.id, revision: revision.id })"
                    class="ds-table-action"
                  >
                    <EyeIcon class="h-4 w-4" />
                    Abrir
                  </Link>
                  <button
                    v-if="activeRevision?.id && revision.id !== activeRevision.id"
                    type="button"
                    class="ds-table-action"
                    @click="compareWithCurrent(revision)"
                  >
                    <ArrowsRightLeftIcon class="h-4 w-4" />
                    Comparar
                  </button>
                  <button
                    v-if="!revision.is_current"
                    type="button"
                    class="ds-table-action"
                    @click="openRestoreModal(revision)"
                  >
                    <ArrowPathIcon class="h-4 w-4" />
                    Repor
                  </button>
                </div>
              </article>
            </div>

            <div class="hidden overflow-x-auto lg:block">
              <DataTable class="min-w-full">
                <thead class="ds-table-head">
                  <tr>
                    <th class="ds-table-heading px-5 py-4 text-left">Comparar</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Versão</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Alteração</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Responsáveis</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Data efectiva</th>
                    <th class="ds-table-heading px-5 py-4 text-right">Acções</th>
                  </tr>
                </thead>
                <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
                  <tr v-for="revision in revisionRows" :key="revision.id" class="ds-table-row">
                    <td class="px-5 py-4">
                      <CheckboxInput
                        type="checkbox"
                        class="ds-checkbox"
                        :checked="selectedRevisions.includes(revision.id)"
                        :disabled="selectedRevisions.length >= 2 && !selectedRevisions.includes(revision.id)"
                        :aria-label="`Seleccionar versão ${revision.version}`"
                        @change="toggleRevision(revision.id)"
                      />
                    </td>
                    <td class="px-4 py-4">
                      <div class="flex items-center gap-2">
                        <span class="ds-heading font-mono text-sm">v{{ revision.version }}</span>
                        <span v-if="revision.is_current" class="ds-chip">Actual</span>
                      </div>
                      <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                        Revisão {{ revision.revision_number }}
                      </p>
                    </td>
                    <td class="max-w-sm px-4 py-4">
                      <span class="inline-flex items-center gap-2 text-xs font-bold text-[var(--ds-text)]">
                        <span :class="['lims-status-dot', changeTypeDot(revision.change_type)]" />
                        {{ changeTypeLabel(revision.change_type) }}
                      </span>
                      <p class="mt-2 line-clamp-2 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">
                        {{ revision.change_reason || "Sem motivo registado." }}
                      </p>
                    </td>
                    <td class="ds-table-cell px-4 py-4">
                      <p class="flex items-center gap-1.5 text-xs">
                        <UserIcon class="h-3.5 w-3.5" />
                        {{ revision.created_by?.name || "Sistema" }}
                      </p>
                      <p class="mt-2 flex items-center gap-1.5 text-xs">
                        <CheckCircleIcon class="h-3.5 w-3.5" />
                        {{ revision.approved_by?.name || "Aprovação pendente" }}
                      </p>
                    </td>
                    <td class="ds-table-cell whitespace-nowrap px-4 py-4">
                      {{ formatDate(revision.effective_date) }}
                    </td>
                    <td class="px-5 py-4">
                      <div class="flex items-center justify-end gap-1">
                        <Link
                          :href="route('qualitycertificates.iso-revisions.show', { certificate: certificate.id, revision: revision.id })"
                          class="ds-table-action"
                          title="Abrir revisão"
                        >
                          <EyeIcon class="h-4 w-4" />
                          <span class="sr-only">Abrir revisão</span>
                        </Link>
                        <button
                          v-if="activeRevision?.id && revision.id !== activeRevision.id"
                          type="button"
                          class="ds-table-action"
                          title="Comparar com a versão actual"
                          @click="compareWithCurrent(revision)"
                        >
                          <ArrowsRightLeftIcon class="h-4 w-4" />
                          <span class="sr-only">Comparar com a versão actual</span>
                        </button>
                        <button
                          v-if="!revision.is_current"
                          type="button"
                          class="ds-table-action"
                          title="Repor esta revisão"
                          @click="openRestoreModal(revision)"
                        >
                          <ArrowPathIcon class="h-4 w-4" />
                          <span class="sr-only">Repor esta revisão</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </DataTable>
            </div>
          </div>

          <div v-else class="ds-empty-state m-5 p-8 text-center">
            <DocumentDuplicateIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" />
            <h3 class="ds-heading mt-3 text-sm">Nenhuma revisão registada</h3>
            <p class="ds-copy mt-1 text-xs">Crie a primeira revisão controlada deste certificado.</p>
            <Link
              :href="route('qualitycertificates.iso-revisions.create', certificate.id)"
              class="ds-button ds-button-primary mt-4"
            >
              <DocumentPlusIcon class="h-4 w-4" /> Criar revisão </Link>
          </div>

          <div v-if="revisionRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
            <Pagination :links="revisions.links" />
          </div>
        </section>
      </div>

      <aside class="space-y-6">
        <section class="ds-card p-5">
          <p class="ds-kicker">Comandos de auditoria</p>
          <h2 class="ds-heading mt-2 text-base">Evidência e rastreabilidade</h2>
          <div class="mt-4 grid gap-2">
            <Link
              :href="route('qualitycertificates.iso-revisions.audit-trail', certificate.id)"
              class="ds-button ds-button-secondary justify-start"
            >
              <ClipboardDocumentListIcon class="h-4 w-4" />
              Ver trilho de auditoria
            </Link>
            <a
              :href="route('qualitycertificates.iso-revisions.export', certificate.id)"
              class="ds-button ds-button-secondary justify-start"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar PDF
            </a>
          </div>
        </section>

        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Actividade recente</p>
            <h2 class="ds-heading mt-2 text-base">Últimos eventos</h2>
          </div>

          <ol v-if="activityLogs.length" class="px-5 py-5">
            <li
              v-for="(activity, index) in activityLogs"
              :key="activity.id"
              class="relative flex gap-3 pb-5 last:pb-0"
            >
              <div class="relative flex w-7 shrink-0 justify-center">
                <span
                  v-if="index !== activityLogs.length - 1"
                  class="absolute bottom-0 top-7 w-px bg-[var(--ds-border-strong)]"
                />
                <span class="grid h-7 w-7 place-items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
                  <ClockIcon class="h-3.5 w-3.5" />
                </span>
              </div>
              <div class="min-w-0 flex-1">
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
            <ClockIcon class="mx-auto h-5 w-5 text-[var(--ds-text-soft)]" />
            <p class="mt-2 text-xs font-semibold text-[var(--ds-text-muted)]">
              Sem eventos recentes.
            </p>
          </div>
        </section>
      </aside>
    </div>

    <RestoreRevisionModal
      :show="restoreModalOpen"
      :revision="selectedRevision"
      :certificate="certificate"
      :approvers="approvers"
      @close="closeRestoreModal"
      @restored="handleRevisionRestored"
    />
  </div>
</template>
