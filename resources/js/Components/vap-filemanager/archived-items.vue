<template>
  <Dialog :open="isOpen" @close="close" class="relative z-50">
    <div class="fixed inset-0 bg-slate-950/55 backdrop-blur-sm" aria-hidden="true" />
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <DialogPanel class="flex h-[86vh] w-full max-w-6xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
        <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.14),_transparent_34%),linear-gradient(135deg,_#f8fafc,_#eef6ff)] px-6 py-5 dark:border-slate-700 dark:bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.18),_transparent_34%),linear-gradient(135deg,_#0f172a,_#111827)]">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">Retenção e arquivo</p>
              <DialogTitle class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.vap_filemanager.archived_items.page_title') }}
              </DialogTitle>
              <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                Recupere documentos ainda válidos, confirme retenção e elimine apenas quando a evidência já não for necessária.
              </p>
            </div>
            <button @click="close" class="rounded-2xl border border-slate-200 bg-white p-2 text-slate-500 transition hover:text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <article class="rounded-2xl border border-white bg-white px-4 py-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Itens arquivados</p>
              <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ archivedItems.length }}</p>
            </article>
            <article class="rounded-2xl border border-white bg-white px-4 py-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Documentos</p>
              <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ archivedFilesCount }}</p>
            </article>
            <article class="rounded-2xl border border-white bg-white px-4 py-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Pastas</p>
              <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ archivedFoldersCount }}</p>
            </article>
          </div>
        </div>

        <div class="flex-1 overflow-auto bg-slate-50/60 p-6 dark:bg-slate-950/70">
          <div v-if="archivedItems.length" class="grid gap-4 xl:grid-cols-2">
            <article
              v-for="item in archivedItems"
              :key="item.id"
              class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-start gap-3">
                  <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                    <FolderIcon
                      v-if="item.type === 'folder'"
                      class="h-5 w-5 text-slate-600 dark:text-slate-300"
                    />
                    <DocumentIcon
                      v-else
                      class="h-5 w-5 text-slate-600 dark:text-slate-300"
                    />
                  </div>
                  <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-slate-900 dark:text-slate-100">{{ item.name }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                      {{ item.type === 'folder' ? $t('gestlab.general.labels.vap_filemanager.type_folder') : $t('gestlab.general.labels.vap_filemanager.type_file') }}
                    </p>
                  </div>
                </div>

                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                  Arquivado
                </span>
              </div>

              <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                  <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Última alteração</dt>
                  <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(item.modifiedAt) }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                  <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Recuperação</dt>
                  <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">Disponível imediatamente</dd>
                </div>
              </dl>

              <div class="mt-5 flex flex-wrap gap-3">
                <button
                  class="rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                  @click="restoreItem(item.id)"
                >
                  Restaurar
                </button>
                <button
                  class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                  @click="permanentlyDelete(item)"
                >
                  Eliminar permanentemente
                </button>
              </div>
            </article>
          </div>

          <div v-else class="flex h-full items-center justify-center">
            <div class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-8 py-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $t('gestlab.general.labels.vap_filemanager.archived_items.no_items_found') }}</p>
              <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                O arquivo ainda não tem documentos retidos ou removidos do fluxo activo.
              </p>
            </div>
          </div>
        </div>

        <Dialog :open="showDeleteDialog" @close="showDeleteDialog = false" class="relative z-50">
          <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" aria-hidden="true" />
          <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-md rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
              <DialogTitle class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.delete_permanently') }}
              </DialogTitle>
              <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ $t('gestlab.general.labels.vap_filemanager.prompts.delete_permanently') + ' - ' + itemToDelete?.name }}?
                {{ $t('gestlab.general.labels.vap_filemanager.prompts.action_cannot_be_undone') }}
              </p>
              <div class="mt-6 flex justify-end gap-3">
                <button
                  class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                  @click="showDeleteDialog = false"
                >
                  {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
                </button>
                <button
                  class="rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700"
                  @click="confirmDelete"
                >
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
import { XMarkIcon, FolderIcon, DocumentIcon } from '@heroicons/vue/24/outline'
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
