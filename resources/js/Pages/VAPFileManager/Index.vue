<script setup lang="ts">
import { computed, ref } from "vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import FileList from "@/Components/vap-filemanager/file-list.vue";
import ArchivedItems from "@/Components/vap-filemanager/archived-items.vue";
import WorkflowPanel from "@/Components/vap-filemanager/workflow-panel.vue";
import DocumentCompliancePanel from "@/Components/vap-filemanager/document-compliance-panel.vue";
import { useFileStore } from "@/Stores/fileStore";
import {
  ArchiveBoxIcon,
  CheckBadgeIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  FolderIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";

defineOptions({
  layout: Layout,
});

const fileStore = useFileStore();
const showArchivedItems = ref(false);
const activeSidePanel = ref<"compliance" | "workflow" | null>(null);

const activeFiles = computed(() => {
  return fileStore.files.filter((file) => !file.archived);
});

const activeFileCount = computed(() => {
  return activeFiles.value.length;
});

const archivedFileCount = computed(() => {
  return fileStore.files.filter((file) => file.archived).length;
});

const controlledFiles = computed(() => {
  return activeFiles.value.filter((file) => file.type === "file" && file.is_controlled);
});

const controlledDocumentCount = computed(() => {
  return controlledFiles.value.length;
});

const effectiveDocumentCount = computed(() => {
  return activeFiles.value.filter((file) => file.type === "file" && file.status === "effective")
    .length;
});

const pendingApprovalCount = computed(() => {
  return activeFiles.value.filter((file) => ["draft", "in_review", "approved"].includes(file.status || ""))
    .length;
});

const overdueReviewCount = computed(() => {
  const now = Date.now();

  return activeFiles.value.filter((file) => {
    if (!file.review_due_at) {
      return false;
    }

    return new Date(file.review_due_at).getTime() < now && file.status !== "obsolete";
  }).length;
});

const restrictedAccessCount = computed(() => {
  return activeFiles.value.filter((file) =>
    file.type === "file" && ["confidential", "restricted"].includes(file.confidentiality_level || ""),
  ).length;
});

const selectedFile = computed(() => {
  const selectedIds = Array.from(fileStore.selectedItems);

  if (selectedIds.length !== 1) {
    return null;
  }

  return fileStore.files.find((file) => file.id === selectedIds[0]) ?? null;
});

const selectedFileSignals = computed(() => {
  if (!selectedFile.value) {
    return [];
  }

  const signals = [];

  if (selectedFile.value.is_controlled) {
    signals.push({
      label: "Documento controlado",
      tone: "emerald",
    });
  }

  if (selectedFile.value.review_due_at && new Date(selectedFile.value.review_due_at).getTime() < Date.now()) {
    signals.push({
      label: "Revisão em atraso",
      tone: "amber",
    });
  }

  if (selectedFile.value.confidentiality_level && ["confidential", "restricted"].includes(selectedFile.value.confidentiality_level)) {
    signals.push({
      label: "Acesso restrito",
      tone: "rose",
    });
  }

  if (selectedFile.value.status) {
    signals.push({
      label: `Estado ${selectedFile.value.status}`,
      tone: "slate",
    });
  }

  return signals;
});

const dashboardCards = computed(() => {
  return [
    {
      label: "Itens activos",
      value: activeFileCount.value,
      caption: "Base documental visível no espaço actual.",
      icon: FolderIcon,
    },
    {
      label: "Documentos controlados",
      value: controlledDocumentCount.value,
      caption: "Registos sujeitos a revisão, retenção e aprovação.",
      icon: ShieldCheckIcon,
    },
    {
      label: "Documentos eficazes",
      value: effectiveDocumentCount.value,
      caption: "Versões actualmente válidas para uso operacional.",
      icon: CheckBadgeIcon,
    },
    {
      label: "Arquivo",
      value: archivedFileCount.value,
      caption: "Itens fora de circulação mas ainda rastreáveis.",
      icon: ArchiveBoxIcon,
    },
  ];
});

const attentionCards = computed(() => {
  return [
    {
      label: "Pendentes de decisão",
      value: pendingApprovalCount.value,
      description: "Draft, revisão ou aprovação ainda em aberto.",
      icon: DocumentTextIcon,
      tone: "blue",
    },
    {
      label: "Revisões em atraso",
      value: overdueReviewCount.value,
      description: "Documentos que já excederam a data de revisão.",
      icon: ClockIcon,
      tone: "amber",
    },
    {
      label: "Acesso sensível",
      value: restrictedAccessCount.value,
      description: "Conteúdo confidencial ou restrito sob controlo.",
      icon: LockClosedIcon,
      tone: "rose",
    },
  ];
});

function signalClass(tone: string): string {
  if (tone === "emerald") {
    return "ds-badge-success";
  }

  if (tone === "amber") {
    return "ds-badge-warning";
  }

  if (tone === "rose") {
    return "ds-badge-danger";
  }

  return "ds-badge-neutral";
}

function formatDate(value?: string | null): string {
  if (!value) {
    return "Não definido";
  }

  return new Intl.DateTimeFormat("pt-PT", {
    dateStyle: "medium",
  }).format(new Date(value));
}

function openSidePanel(panel: "compliance" | "workflow"): void {
  activeSidePanel.value = panel;
}

function closeSidePanel(): void {
  activeSidePanel.value = null;
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr] xl:items-start">
        <div class="space-y-6">
          <div class="flex flex-wrap items-center gap-3">
            <span class="ds-badge ds-badge-info">ISO 17025 · controlo documental</span>
            <span class="ds-badge ds-badge-neutral">
              Aprovação, retenção, obsolescência e arquivo no mesmo fluxo
            </span>
          </div>

          <div class="max-w-4xl">
            <h1 class="ds-heading text-2xl">
              {{ $t("gestlab.general.labels.vap_filemanager.page_title") }}
            </h1>
            <p class="ds-copy mt-2 max-w-3xl text-sm">
              Um centro de controlo documental pensado para operação real: localizar rapidamente, decidir o estado do documento,
              acompanhar revisões e manter evidência auditável sem espalhar a tarefa por ecrãs paralelos.
            </p>
          </div>

          <dl class="grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] md:grid-cols-2 xl:grid-cols-4">
            <div
              v-for="card in dashboardCards"
              :key="card.label"
              class="border-b border-[var(--ds-border)] px-4 py-3 md:[&:nth-child(odd)]:border-r md:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ card.label }}</dt>
                  <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ card.value }}</dd>
                </div>
                <div class="grid h-9 w-9 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
                  <component :is="card.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
                </div>
              </div>
              <p class="mt-2 text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.caption }}</p>
            </div>
          </dl>
        </div>

        <div class="space-y-4">
          <section class="border-l-4 border-amber-400/70 pl-5">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="ds-kicker">Fila de atenção</p>
                <h2 class="ds-heading mt-1 text-base">O que exige acção agora</h2>
              </div>
              <ExclamationTriangleIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
            </div>

            <div class="mt-4 divide-y divide-[var(--ds-border)]">
              <article
                v-for="card in attentionCards"
                :key="card.label"
                class="py-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-bold text-[var(--ds-text)]">{{ card.label }}</p>
                    <p class="ds-copy mt-1 text-sm">{{ card.description }}</p>
                  </div>
                  <div class="min-w-[4.25rem] text-right">
                    <component :is="card.icon" class="ml-auto h-4 w-4 text-[var(--ds-text-soft)]" />
                    <p class="mt-1 text-xl font-bold text-[var(--ds-text)]">{{ card.value }}</p>
                  </div>
                </div>
              </article>
            </div>

            <button
              type="button"
              class="ds-button ds-button-secondary mt-4 w-full"
              @click="showArchivedItems = true"
            >
              <ArchiveBoxIcon class="h-5 w-5" />
              <span>{{ $t("gestlab.general.labels.vap_filemanager.view_archived_items") }}</span>
            </button>
          </section>

          <section class="border-t border-[var(--ds-border)] pt-4">
            <p class="ds-kicker">Documento seleccionado</p>
            <div v-if="selectedFile" class="mt-4 space-y-4">
              <div>
                <h3 class="ds-heading text-base">{{ selectedFile.name }}</h3>
                <p class="ds-copy mt-1 text-sm">
                  {{ selectedFile.document_number || $t("gestlab.general.labels.vap_filemanager.missing_document_number") }} •
                  {{ selectedFile.revision_code || $t("gestlab.general.labels.vap_filemanager.missing_revision") }}
                </p>
              </div>

              <div class="flex flex-wrap gap-2">
                <span
                  v-for="signal in selectedFileSignals"
                  :key="signal.label"
                  class="ds-badge"
                  :class="signalClass(signal.tone)"
                >
                  {{ signal.label }}
                </span>
              </div>

              <dl class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
                  <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Próxima revisão</dt>
                  <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(selectedFile.review_due_at) }}</dd>
                </div>
                <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
                  <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Confidencialidade</dt>
                  <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ selectedFile.confidentiality_level || "internal" }}</dd>
                </div>
              </dl>
            </div>

            <div v-else class="ds-empty-state mt-4 px-4 py-5 text-sm">
              {{ $t("gestlab.general.labels.vap_filemanager.select_single_document_hint") }}
            </div>
          </section>
        </div>
      </div>
    </section>

    <main class="space-y-4">
      <div class="ds-command-surface flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="ds-kicker">Biblioteca operacional</p>
          <h2 class="ds-heading mt-1 text-base">Workspace documental</h2>
          <p class="ds-copy mt-1 text-sm">
            A lista ocupa toda a largura. Abra os painéis laterais apenas quando precisar de controlo ou workflow.
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="openSidePanel('compliance')"
          >
            <ShieldCheckIcon class="h-4 w-4" />
            Controlo documental
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="openSidePanel('workflow')"
          >
            <CheckBadgeIcon class="h-4 w-4" />
            Workflow e tarefas
          </button>
        </div>
      </div>

      <div class="min-w-0">
        <FileList />
      </div>
    </main>

    <ArchivedItems
      :is-open="showArchivedItems"
      @close="showArchivedItems = false"
    />

    <TransitionRoot as="template" :show="Boolean(activeSidePanel)">
      <Dialog class="relative z-50" @close="closeSidePanel">
        <TransitionChild
          as="template"
          enter="ease-out duration-200"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-150"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-hidden">
          <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-4 sm:pl-6">
              <TransitionChild
                as="template"
                enter="transform transition ease-out duration-300"
                enter-from="translate-x-full"
                enter-to="translate-x-0"
                leave="transform transition ease-in duration-200"
                leave-from="translate-x-0"
                leave-to="translate-x-full"
              >
                <DialogPanel class="pointer-events-auto w-screen max-w-2xl">
                  <div class="ds-slideover-panel flex h-full flex-col overflow-y-auto border-l">
                    <div class="ds-slideover-header border-b px-5 py-4 sm:px-6">
                      <div class="flex items-start justify-between gap-4">
                        <div>
                          <p class="ds-kicker">Painel lateral</p>
                          <DialogTitle class="ds-heading mt-1 text-xl">
                            {{ activeSidePanel === 'compliance' ? 'Controlo documental' : 'Workflow documental' }}
                          </DialogTitle>
                          <p class="ds-copy mt-1 text-sm">
                            {{ activeSidePanel === 'compliance'
                              ? 'Metadados ISO, revisão, retenção e efetividade do documento seleccionado.'
                              : 'Estado operacional, tarefas e seguimento do fluxo documental.' }}
                          </p>
                        </div>
                        <button
                          type="button"
                          class="ds-icon-button"
                          @click="closeSidePanel"
                          title="Fechar painel"
                        >
                          <XMarkIcon class="h-5 w-5" />
                        </button>
                      </div>

                      <div class="mt-4 inline-flex rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-1">
                        <button
                          type="button"
                          class="rounded-md px-3 py-1.5 text-xs font-bold transition"
                          :class="activeSidePanel === 'compliance' ? 'bg-[var(--ds-panel-raised)] text-[var(--ds-text)] shadow-sm' : 'text-[var(--ds-text-muted)] hover:text-[var(--ds-text)]'"
                          @click="openSidePanel('compliance')"
                        >
                          Controlo documental
                        </button>
                        <button
                          type="button"
                          class="rounded-md px-3 py-1.5 text-xs font-bold transition"
                          :class="activeSidePanel === 'workflow' ? 'bg-[var(--ds-panel-raised)] text-[var(--ds-text)] shadow-sm' : 'text-[var(--ds-text-muted)] hover:text-[var(--ds-text)]'"
                          @click="openSidePanel('workflow')"
                        >
                          Workflow e tarefas
                        </button>
                      </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                      <DocumentCompliancePanel v-if="activeSidePanel === 'compliance'" />
                      <WorkflowPanel v-else />
                    </div>
                  </div>
                </DialogPanel>
              </TransitionChild>
            </div>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>
