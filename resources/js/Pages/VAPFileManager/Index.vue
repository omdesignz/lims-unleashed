<script setup lang="ts">
import { computed, ref } from "vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import FileList from "@/Components/vap-filemanager/file-list.vue";
import ArchivedItems from "@/Components/vap-filemanager/archived-items.vue";
import WorkflowPanel from "@/Components/vap-filemanager/workflow-panel.vue";
import DocumentCompliancePanel from "@/Components/vap-filemanager/document-compliance-panel.vue";
import { useFileStore } from "@/Stores/fileStore";
import {
  Archive as ArchiveBoxIcon,
  ChevronDown as ChevronDownIcon,
  CloudUpload as CloudArrowUpIcon,
  Folder as FolderIcon,
  FolderPlus as FolderPlusIcon,
  X as XMarkIcon,
} from "@lucide/vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";

defineOptions({
  layout: Layout,
});

/**
 * Document manager (Plano page). The library itself (state cells, filter, register,
 * uploads, versions, sharing) lives in FileList; this page owns the header, the
 * register facts, the archive and the compliance/workflow side panels.
 */
const fileStore = useFileStore();
const fileList = ref<InstanceType<typeof FileList> | null>(null);
const showArchivedItems = ref(false);
const activeSidePanel = ref<"compliance" | "workflow" | null>(null);

const isUploading = computed(() => Boolean(fileList.value?.isUploading));

const activeFiles = computed(() => {
  return fileStore.files.filter((file) => !file.archived);
});

const archivedFileCount = computed(() => {
  return fileStore.files.filter((file) => file.archived).length;
});

const controlledDocumentCount = computed(() => {
  return activeFiles.value.filter((file) => file.type === "file" && file.is_controlled).length;
});

const effectiveDocumentCount = computed(() => {
  return activeFiles.value.filter((file) => file.type === "file" && file.status === "effective").length;
});

const pendingApprovalCount = computed(() => {
  return activeFiles.value.filter((file) => ["draft", "in_review", "approved"].includes(file.status || "")).length;
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

/** Register-wide counts. They describe the whole library, so they are facts, not filters. */
const registerFacts = computed(() => [
  { label: "Itens activos", value: activeFiles.value.length },
  { label: "Documentos controlados", value: controlledDocumentCount.value },
  { label: "Documentos efectivos", value: effectiveDocumentCount.value },
  { label: "No arquivo", value: archivedFileCount.value },
]);

const lede = computed(() => {
  const attention = [
    pendingApprovalCount.value ? `${pendingApprovalCount.value} por decidir (rascunho, revisão ou aprovação)` : "",
    overdueReviewCount.value ? `${overdueReviewCount.value} com revisão em atraso` : "",
    restrictedAccessCount.value ? `${restrictedAccessCount.value} de acesso restrito` : "",
  ].filter(Boolean);

  return attention.length
    ? `Documentos que pedem atenção: ${attention.join(", ")}.`
    : "Biblioteca controlada com revisão, aprovação, retenção e rastreabilidade no mesmo registo.";
});

function openSidePanel(panel: "compliance" | "workflow"): void {
  activeSidePanel.value = panel;
}

function closeSidePanel(): void {
  activeSidePanel.value = null;
}
</script>

<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Admin' }, { title: 'Gestor documental' }]"
      title="Gestor documental"
      :lede="lede"
      data-testid="document-manager-overview"
    >
      <template #actions>
        <button type="button" class="ds-button ds-button-quiet" @click="openSidePanel('compliance')">Controlo documental</button>
        <button type="button" class="ds-button ds-button-quiet" @click="openSidePanel('workflow')">Fluxo e tarefas</button>
        <Menu as="div" class="relative">
          <MenuButton class="ds-button ds-button-secondary">
            Mais acções
            <ChevronDownIcon class="h-4 w-4" aria-hidden="true" />
          </MenuButton>
          <MenuItems class="ds-floating-panel absolute right-0 z-30 mt-1 w-56 origin-top-right focus:outline-none">
            <MenuItem v-slot="{ active, disabled }" :disabled="isUploading">
              <button type="button" class="pl-menu-item" :data-active="active" :disabled="disabled" @click="fileList?.triggerFolderUpload()">
                <FolderPlusIcon aria-hidden="true" />
                Importar pasta
              </button>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <button type="button" class="pl-menu-item" :data-active="active" @click="fileList?.startCreateFolder()">
                <FolderIcon aria-hidden="true" />
                Criar pasta
              </button>
            </MenuItem>
            <div class="pl-menu-sep" />
            <MenuItem v-slot="{ active }">
              <button type="button" class="pl-menu-item" :data-active="active" @click="showArchivedItems = true">
                <ArchiveBoxIcon aria-hidden="true" />
                Arquivo
              </button>
            </MenuItem>
          </MenuItems>
        </Menu>
        <button
          type="button"
          class="ds-button ds-button-primary"
          :disabled="isUploading"
          data-testid="upload-files-button"
          @click="fileList?.triggerFileUpload()"
        >
          <CloudArrowUpIcon class="h-4 w-4" aria-hidden="true" />
          {{ isUploading ? $t("gestlab.general.labels.vap_filemanager.uploading") : $t("gestlab.general.labels.vap_filemanager.upload_files") }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-panel pl-facts pl-facts-2 mb-10" aria-label="Registo documental">
      <div v-for="fact in registerFacts" :key="fact.label" class="pl-fact">
        <dt>{{ fact.label }}</dt>
        <dd class="pl-num">{{ fact.value }}</dd>
      </div>
    </dl>

    <FileList ref="fileList" />

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
          leave="ease-out duration-150"
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
                leave="transform transition ease-out duration-150"
                leave-from="translate-x-0"
                leave-to="translate-x-full"
              >
                <DialogPanel class="pointer-events-auto w-screen max-w-2xl">
                  <div class="ds-slideover-panel flex h-full flex-col overflow-y-auto border-l">
                    <div class="border-b border-[var(--pl-line)] px-5 pt-5 sm:px-6">
                      <div class="flex items-start justify-between gap-4">
                        <div class="grid gap-1">
                          <DialogTitle class="pl-d3">
                            {{ activeSidePanel === 'compliance' ? 'Controlo documental' : 'Fluxo e tarefas' }}
                          </DialogTitle>
                          <p class="text-sm text-[var(--pl-muted)]">
                            {{ activeSidePanel === 'compliance'
                              ? 'Metadados ISO, revisão, retenção e efectividade do documento seleccionado.'
                              : 'Estado operacional, tarefas e seguimento do fluxo documental.' }}
                          </p>
                        </div>
                        <button type="button" class="ds-icon-button" aria-label="Fechar painel" @click="closeSidePanel">
                          <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                      </div>

                      <div class="pl-tabs mt-3 border-b-0" role="tablist" aria-label="Painel lateral">
                        <button type="button" role="tab" class="pl-tab" :aria-selected="activeSidePanel === 'compliance'" @click="openSidePanel('compliance')">Controlo documental</button>
                        <button type="button" role="tab" class="pl-tab" :aria-selected="activeSidePanel === 'workflow'" @click="openSidePanel('workflow')">Fluxo e tarefas</button>
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
