<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import Pagination from "@/Components/Pagination.vue";
import RestoreRevisionModal from "./Partials/RestoreRevisionModal.vue";
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  DocumentCheckIcon,
  DocumentDuplicateIcon,
  DocumentPlusIcon,
  EyeIcon,
  UserIcon,
} from "@heroicons/vue/24/outline";

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
    label: "Revisoes controladas",
    value: props.revisions?.total ?? revisionRows.value.length,
    note: "historico do certificado",
  },
  {
    label: "Conformidade documental",
    value: `${props.complianceStats?.compliance_rate ?? 100}%`,
    note: "eventos com metadados ISO",
  },
  {
    label: "Eventos auditaveis",
    value: props.complianceStats?.activity_count ?? props.activityLogs.length,
    note: "registos de atividade",
  },
  {
    label: "Versao efetiva",
    value: `v${activeRevision.value?.version ?? "1.0"}`,
    note: activeRevision.value?.is_current ? "versao corrente" : "versao registada",
  },
]);

const currentRevisionDetails = computed(() => [
  {
    label: "Versao",
    value: `v${activeRevision.value?.version ?? "1.0"}`,
  },
  {
    label: "Numero da revisao",
    value: activeRevision.value?.revision_number ?? "1",
  },
  {
    label: "Data efetiva",
    value: formatDate(
      activeRevision.value?.effective_date || props.certificate?.validated_at,
    ),
  },
  {
    label: "Aprovado por",
    value:
      activeRevision.value?.approved_by?.name ||
      props.certificate?.validated_by ||
      "Aprovacao pendente",
  },
]);

const selectedCountLabel = computed(() => {
  if (!selectedRevisions.value.length) {
    return "Selecione duas revisoes para comparar";
  }

  return `${selectedRevisions.value.length}/2 revisoes selecionadas`;
});

function formatDate(date) {
  if (!date) {
    return "Nao registada";
  }

  return new Date(date).toLocaleDateString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function formatDateTime(date) {
  if (!date) {
    return "Nao registada";
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
    CREATED: "Criacao",
    UPDATED: "Atualizacao",
    CORRECTED: "Correcao",
    REISSUED: "Reemissao",
    WITHDRAWN: "Retirada",
  };

  return labels[changeType] || changeType || "Alteracao";
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
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="route('qualitycertificates.show', { certificate: certificate.id })"
            class="ds-table-action -ml-2 mb-3"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao certificado
          </Link>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">ISO/IEC 17025</p>
            <span class="ds-chip font-mono">{{ certificate.code || "Sem codigo" }}</span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">Controlo de revisoes</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            Historico imutavel de alteracoes, aprovacao, comparacao e reposicao
            do certificado para auditoria e rastreabilidade.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <a
            :href="route('qualitycertificates.iso-revisions.export', certificate.id)"
            class="ds-button ds-button-secondary"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar historico
          </a>
          <Link
            :href="route('qualitycertificates.iso-revisions.create', certificate.id)"
            class="ds-button ds-button-primary"
          >
            <DocumentPlusIcon class="h-4 w-4" />
            Nova revisao
          </Link>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in revisionMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 text-lg">{{ metric.value }}</dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
            {{ metric.note }}
          </p>
        </div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
      <div class="space-y-6">
        <section class="ds-command-surface overflow-hidden">
          <div class="flex items-start gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
              <DocumentCheckIcon class="h-5 w-5" />
            </div>
            <div>
              <p class="ds-kicker">Versao efetiva</p>
              <h2 class="ds-heading mt-2 text-base">
                v{{ activeRevision?.version || "1.0" }}
              </h2>
              <p class="ds-copy mt-1 text-xs">
                {{ activeRevision?.change_reason || "Emissao inicial do certificado." }}
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
              <p class="ds-kicker">Historico controlado</p>
              <h2 class="ds-heading mt-2 text-base">Revisoes do certificado</h2>
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
              Comparar selecionadas
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
                        Revisao {{ revision.revision_number }} - {{ formatDate(revision.effective_date) }}
                      </span>
                    </span>
                  </button>
                  <span v-if="revision.is_current" class="ds-chip">Atual</span>
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
                    <th class="ds-table-heading px-4 py-4 text-left">Versao</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Alteracao</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Responsaveis</th>
                    <th class="ds-table-heading px-4 py-4 text-left">Data efetiva</th>
                    <th class="ds-table-heading px-5 py-4 text-right">Acoes</th>
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
                        :aria-label="`Selecionar versao ${revision.version}`"
                        @change="toggleRevision(revision.id)"
                      />
                    </td>
                    <td class="px-4 py-4">
                      <div class="flex items-center gap-2">
                        <span class="ds-heading font-mono text-sm">v{{ revision.version }}</span>
                        <span v-if="revision.is_current" class="ds-chip">Atual</span>
                      </div>
                      <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                        Revisao {{ revision.revision_number }}
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
                        {{ revision.approved_by?.name || "Aprovacao pendente" }}
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
                          title="Abrir revisao"
                        >
                          <EyeIcon class="h-4 w-4" />
                          <span class="sr-only">Abrir revisao</span>
                        </Link>
                        <button
                          v-if="activeRevision?.id && revision.id !== activeRevision.id"
                          type="button"
                          class="ds-table-action"
                          title="Comparar com a versao atual"
                          @click="compareWithCurrent(revision)"
                        >
                          <ArrowsRightLeftIcon class="h-4 w-4" />
                          <span class="sr-only">Comparar com a versao atual</span>
                        </button>
                        <button
                          v-if="!revision.is_current"
                          type="button"
                          class="ds-table-action"
                          title="Repor esta revisao"
                          @click="openRestoreModal(revision)"
                        >
                          <ArrowPathIcon class="h-4 w-4" />
                          <span class="sr-only">Repor esta revisao</span>
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
            <h3 class="ds-heading mt-3 text-sm">Nenhuma revisao registada</h3>
            <p class="ds-copy mt-1 text-xs">Crie a primeira revisao controlada deste certificado.</p>
            <Link
              :href="route('qualitycertificates.iso-revisions.create', certificate.id)"
              class="ds-button ds-button-primary mt-4"
            >
              <DocumentPlusIcon class="h-4 w-4" />
              Criar revisao
            </Link>
          </div>

          <div v-if="revisionRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
            <Pagination :links="revisions.links" />
          </div>
        </section>
      </div>

      <aside class="space-y-6">
        <section class="ds-card p-5">
          <p class="ds-kicker">Comandos de auditoria</p>
          <h2 class="ds-heading mt-2 text-base">Evidencia e rastreabilidade</h2>
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
            <p class="ds-kicker">Atividade recente</p>
            <h2 class="ds-heading mt-2 text-base">Ultimos eventos</h2>
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
