<script setup lang="ts">
import { computed, ref } from "vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
    return "border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200";
  }

  if (tone === "amber") {
    return "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200";
  }

  if (tone === "rose") {
    return "border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200";
  }

  return "border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200";
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
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.32),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_28%),linear-gradient(135deg,_#020617,_#0f172a_48%,_#0b1120)] p-6 text-white shadow-sm">
      <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr] xl:items-start">
        <div class="space-y-6">
          <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-100">
              ISO 17025 document control
            </span>
            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-200">
              Aprovação, retenção, obsolescência e arquivo no mesmo fluxo
            </span>
          </div>

          <div class="max-w-4xl">
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
              {{ $t("gestlab.general.labels.vap_filemanager.page_title") }}
            </h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
              Um centro de controlo documental pensado para operação real: localizar rapidamente, decidir o estado do documento,
              acompanhar revisões e manter evidência auditável sem espalhar a tarefa por ecrãs paralelos.
            </p>
          </div>

          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="card in dashboardCards"
              :key="card.label"
              class="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur-sm"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">{{ card.label }}</p>
                  <p class="mt-3 text-3xl font-semibold text-white">{{ card.value }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-2.5">
                  <component :is="card.icon" class="h-5 w-5 text-cyan-200" />
                </div>
              </div>
              <p class="mt-3 text-sm leading-6 text-slate-300">{{ card.caption }}</p>
            </article>
          </div>
        </div>

        <div class="space-y-4">
          <section class="rounded-[1.75rem] border border-white/10 bg-white/8 p-5 backdrop-blur-sm">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100">Fila de atenção</p>
                <h2 class="mt-2 text-xl font-semibold text-white">O que exige acção agora</h2>
              </div>
              <ExclamationTriangleIcon class="h-6 w-6 text-amber-300" />
            </div>

            <div class="mt-5 space-y-3">
              <article
                v-for="card in attentionCards"
                :key="card.label"
                class="rounded-2xl border border-white/10 bg-slate-950/35 p-4"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-white">{{ card.label }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-300">{{ card.description }}</p>
                  </div>
                  <div class="min-w-[4.25rem] rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-right">
                    <component :is="card.icon" class="ml-auto h-4 w-4 text-cyan-200" />
                    <p class="mt-2 text-2xl font-semibold text-white">{{ card.value }}</p>
                  </div>
                </div>
              </article>
            </div>

            <button
              type="button"
              class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/15"
              @click="showArchivedItems = true"
            >
              <ArchiveBoxIcon class="h-5 w-5" />
              <span>{{ $t("gestlab.general.labels.vap_filemanager.view_archived_items") }}</span>
            </button>
          </section>

          <section class="rounded-[1.75rem] border border-white/10 bg-slate-950/45 p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-300">Documento seleccionado</p>
            <div v-if="selectedFile" class="mt-4 space-y-4">
              <div>
                <h3 class="text-lg font-semibold text-white">{{ selectedFile.name }}</h3>
                <p class="mt-1 text-sm text-slate-300">
                  {{ selectedFile.document_number || $t("gestlab.general.labels.vap_filemanager.missing_document_number") }} •
                  {{ selectedFile.revision_code || $t("gestlab.general.labels.vap_filemanager.missing_revision") }}
                </p>
              </div>

              <div class="flex flex-wrap gap-2">
                <span
                  v-for="signal in selectedFileSignals"
                  :key="signal.label"
                  class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                  :class="signalClass(signal.tone)"
                >
                  {{ signal.label }}
                </span>
              </div>

              <dl class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                  <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Próxima revisão</dt>
                  <dd class="mt-2 text-sm font-medium text-white">{{ formatDate(selectedFile.review_due_at) }}</dd>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                  <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Confidencialidade</dt>
                  <dd class="mt-2 text-sm font-medium text-white">{{ selectedFile.confidentiality_level || "internal" }}</dd>
                </div>
              </dl>
            </div>

            <div v-else class="mt-4 rounded-2xl border border-dashed border-white/15 bg-white/5 px-4 py-5 text-sm leading-6 text-slate-300">
              {{ $t("gestlab.general.labels.vap_filemanager.select_single_document_hint") }}
            </div>
          </section>
        </div>
      </div>
    </section>

    <main class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Workspace documental</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            A lista ocupa toda a largura. Abra os painéis laterais apenas quando precisar de controlo ou workflow.
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="openSidePanel('compliance')"
          >
            <ShieldCheckIcon class="h-5 w-5 text-sky-700" />
            Controlo documental
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="openSidePanel('workflow')"
          >
            <CheckBadgeIcon class="h-5 w-5 text-emerald-700" />
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
          <div class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm" />
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
                  <div class="flex h-full flex-col overflow-y-auto border-l border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-800 dark:bg-slate-950">
                    <div class="border-b border-slate-200 bg-white px-5 py-4 sm:px-6 dark:border-slate-800 dark:bg-slate-900">
                      <div class="flex items-start justify-between gap-4">
                        <div>
                          <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700 dark:text-sky-300">Painel lateral</p>
                          <DialogTitle class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">
                            {{ activeSidePanel === 'compliance' ? 'Controlo documental' : 'Workflow documental' }}
                          </DialogTitle>
                          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ activeSidePanel === 'compliance'
                              ? 'Metadados ISO, revisão, retenção e efetividade do documento seleccionado.'
                              : 'Estado operacional, tarefas e seguimento do fluxo documental.' }}
                          </p>
                        </div>
                        <button
                          type="button"
                          class="inline-flex rounded-2xl border border-slate-200 bg-white p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                          @click="closeSidePanel"
                        >
                          <XMarkIcon class="h-5 w-5" />
                        </button>
                      </div>

                      <div class="mt-4 flex flex-wrap gap-2">
                        <button
                          type="button"
                          class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                          :class="activeSidePanel === 'compliance' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                          @click="openSidePanel('compliance')"
                        >
                          Controlo documental
                        </button>
                        <button
                          type="button"
                          class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                          :class="activeSidePanel === 'workflow' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
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
