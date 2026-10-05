<script setup lang="ts">
import { computed, ref } from "vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import FileList from "@/Components/vap-filemanager/file-list.vue";
import ArchivedItems from "@/Components/vap-filemanager/archived-items.vue";
import WorkflowPanel from "@/Components/vap-filemanager/workflow-panel.vue";
import DocumentCompliancePanel from "@/Components/vap-filemanager/document-compliance-panel.vue";
import FolderTree from "@/Components/vap-filemanager/folder-tree.vue";
import { useFileStore } from "@/Stores/fileStore";
import {
  Archive as ArchiveBoxIcon,
  ChevronDown as ChevronDownIcon,
  CloudUpload as CloudArrowUpIcon,
  Folder as FolderIcon,
  FolderPlus as FolderPlusIcon,
} from "@lucide/vue";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";

defineOptions({
  layout: Layout,
});

/**
 * Document manager (Plano page) in three panes: the folder tree, the library
 * (state cells, filter, register, uploads, versions, sharing; in FileList) and
 * the inspector of the selected document (its control data and its workflow).
 */
const fileStore = useFileStore();
const fileList = ref<InstanceType<typeof FileList> | null>(null);
const showArchivedItems = ref(false);
const inspectorTab = ref<"compliance" | "workflow">("compliance");

const isUploading = computed(() => Boolean(fileList.value?.isUploading));

const activeFiles = computed(() => {
  return fileStore.files.filter((file) => !file.archived);
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

/** The one document the inspector describes: a single selected file. */
const selectedDocument = computed(() => {
  const ids = Array.from(fileStore.selectedItems);

  return ids.length === 1 ? fileStore.files.find((file) => file.id === ids[0] && file.type === "file") ?? null : null;
});
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
        <Menu as="div" class="relative">
          <MenuButton class="ds-button ds-button-secondary">
            Mais acções
            <ChevronDownIcon class="h-4 w-4" aria-hidden="true" />
          </MenuButton>
          <MenuItems class="ds-floating-panel absolute right-0 z-30 mt-1 w-56 origin-top-right focus:outline-none">
            <MenuItem v-slot="{ active }">
              <button type="button" class="pl-menu-item" :data-active="active" @click="fileList?.startCreateFolder()">
                <FolderIcon aria-hidden="true" />
                Criar pasta
              </button>
            </MenuItem>
            <MenuItem v-slot="{ active, disabled }" :disabled="isUploading">
              <button type="button" class="pl-menu-item" :data-active="active" :disabled="disabled" @click="fileList?.triggerFolderUpload()">
                <FolderPlusIcon aria-hidden="true" />
                Importar pasta
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

    <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] 2xl:grid-cols-[15rem_minmax(0,1fr)_24rem]">
      <FolderTree class="lg:self-start lg:sticky lg:top-6" />

      <FileList ref="fileList" />

      <aside class="pl-panel lg:col-start-2 2xl:col-start-auto 2xl:self-start 2xl:sticky 2xl:top-6" aria-label="Documento seleccionado" data-testid="document-inspector">
        <header class="border-b border-[var(--pl-line)] px-4 pt-3">
          <p class="pl-k pl-muted">Documento seleccionado</p>
          <p class="mt-1 truncate text-sm font-bold text-[var(--pl-fg)]">{{ selectedDocument?.name || "Nenhum" }}</p>
          <div class="pl-tabs mt-2 border-b-0" role="tablist" aria-label="Painel do documento">
            <button type="button" role="tab" class="pl-tab" :aria-selected="inspectorTab === 'compliance'" @click="inspectorTab = 'compliance'">Controlo documental</button>
            <button type="button" role="tab" class="pl-tab" :aria-selected="inspectorTab === 'workflow'" @click="inspectorTab = 'workflow'">Fluxo e tarefas</button>
          </div>
        </header>
        <div class="p-4">
          <DocumentCompliancePanel v-if="inspectorTab === 'compliance'" />
          <WorkflowPanel v-else />
        </div>
      </aside>
    </div>

    <ArchivedItems
      :is-open="showArchivedItems"
      @close="showArchivedItems = false"
    />
  </div>
</template>
