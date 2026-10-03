<template>
  <Dialog :open="isOpen" class="relative z-50" @close="close">
    <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <DialogPanel class="ds-modal-panel flex h-[86vh] w-full max-w-5xl flex-col overflow-hidden">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--pl-line)] px-6 py-5">
          <div class="grid gap-1">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.page_title') }}</DialogTitle>
            <p class="max-w-3xl text-sm text-[var(--pl-muted)]">
              {{ archivedItems.length }} {{ archivedItems.length === 1 ? 'item arquivado' : 'itens arquivados' }}: {{ archivedFilesCount }} {{ archivedFilesCount === 1 ? 'documento' : 'documentos' }} e {{ archivedFoldersCount }} {{ archivedFoldersCount === 1 ? 'pasta' : 'pastas' }}.
              Recupere documentos ainda válidos e elimine apenas quando a evidência já não for necessária.
            </p>
          </div>
          <button type="button" class="ds-icon-button" aria-label="Fechar arquivo" @click="close">
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>

        <div class="flex-1 overflow-auto p-6">
          <section v-if="archivedItems.length" class="pl-panel" aria-label="Itens arquivados">
            <DataTable>
              <thead>
                <tr>
                  <th scope="col">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.name') }}</th>
                  <th scope="col">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.type') }}</th>
                  <th scope="col">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.modified') }}</th>
                  <th scope="col"><span class="sr-only">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.actions') }}</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in archivedItems" :key="item.id">
                  <td>
                    <span class="flex min-w-0 items-center gap-2">
                      <FolderIcon v-if="item.type === 'folder'" class="h-4 w-4 shrink-0 text-[var(--pl-accent-text)]" aria-hidden="true" />
                      <DocumentIcon v-else class="h-4 w-4 shrink-0 text-[var(--pl-muted)]" aria-hidden="true" />
                      <span class="truncate font-medium">{{ item.name }}</span>
                    </span>
                  </td>
                  <td>{{ item.type === 'folder' ? $t('gestlab.general.labels.vap_filemanager.type_folder') : $t('gestlab.general.labels.vap_filemanager.type_file') }}</td>
                  <td class="pl-num">{{ formatDate(item.modifiedAt) }}</td>
                  <td class="text-right">
                    <div class="flex justify-end gap-1">
                      <button type="button" class="ds-table-action" @click="restoreItem(item.id)">Restaurar</button>
                      <button type="button" class="ds-table-action ds-table-action-danger" @click="permanentlyDelete(item)">Eliminar permanentemente</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </DataTable>
          </section>

          <div v-else class="ds-empty-state grid justify-items-start gap-2 p-6">
            <span class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.no_items_found') }}</span>
            <p class="text-sm text-[var(--pl-muted)]">O arquivo ainda não tem documentos retidos ou removidos do fluxo activo.</p>
          </div>
        </div>

        <Dialog :open="showDeleteDialog" class="relative z-50" @close="showDeleteDialog = false">
          <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
          <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="ds-modal-panel grid w-full max-w-md gap-4 p-6">
              <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.buttons.delete_permanently') }}</DialogTitle>
              <p class="text-sm leading-6 text-[var(--pl-muted)]">
                {{ $t('gestlab.general.labels.vap_filemanager.prompts.delete_permanently') + ' - ' + itemToDelete?.name }}?
                {{ $t('gestlab.general.labels.vap_filemanager.prompts.action_cannot_be_undone') }}
              </p>
              <div class="flex justify-end gap-3">
                <button type="button" class="ds-button ds-button-quiet" @click="showDeleteDialog = false">
                  {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
                </button>
                <button type="button" class="ds-button ds-button-danger" @click="confirmDelete">
                  {{ $t('gestlab.general.labels.vap_filemanager.buttons.delete_permanently') }}
                </button>
              </div>
            </DialogPanel>
          </div>
        </Dialog>
      </DialogPanel>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { X as XMarkIcon, Folder as FolderIcon, File as DocumentIcon } from '@lucide/vue'
import { useFileStore }  from "../../Stores/fileStore"

defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const fileStore = useFileStore()
const showDeleteDialog = ref(false)
const itemToDelete = ref<{ id: string; name: string } | null>(null)

const archivedItems = computed(() => {
  return fileStore.files.filter(file => file.archived)
})

const archivedFilesCount = computed(() => archivedItems.value.filter((item) => item.type === 'file').length)
const archivedFoldersCount = computed(() => archivedItems.value.filter((item) => item.type === 'folder').length)

function formatDate(date: Date) {
  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

function close() {
  emit('close')
}

function restoreItem(id: string) {
  fileStore.restoreArchivedItem(id)
}

function permanentlyDelete(item: { id: string; name: string }) {
  itemToDelete.value = item
  showDeleteDialog.value = true
}

function confirmDelete() {
  if (itemToDelete.value) {
    fileStore.permanentlyDeleteItem(itemToDelete.value.id)
  }
  showDeleteDialog.value = false
  itemToDelete.value = null
}
</script>
