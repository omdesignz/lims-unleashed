<template>
  <Dialog :open="isOpen" @close="close" class="relative z-50">
    <div class="fixed inset-0 bg-slate-950/55 backdrop-blur-sm" aria-hidden="true" />
    <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
      <div class="flex min-h-full items-center justify-center">
        <DialogPanel class="flex h-[88vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
          <div class="border-b border-slate-200 bg-slate-50/90 px-6 py-4 dark:border-slate-700 dark:bg-slate-800/90">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <DialogTitle class="truncate text-lg font-semibold text-slate-900 dark:text-slate-100">
                  {{ file?.name }}
                </DialogTitle>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  Pré-visualização do ficheiro para validação rápida antes de descarregar ou partilhar.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                  @click="downloadFile"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  Transferir
                </button>
                <button
                  type="button"
                  class="rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                  @click="close"
                >
                  <XMarkIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto bg-slate-100/80 p-4 dark:bg-slate-950/70 sm:p-6">
            <div class="flex min-h-full items-center justify-center rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <img
                v-if="isImage"
                :src="objectUrl"
                class="max-h-[72vh] w-auto max-w-full rounded-2xl object-contain"
                alt="Pré-visualização de imagem"
              />

              <iframe
                v-else-if="isPdf"
                :src="objectUrl"
                class="h-[72vh] w-full rounded-2xl"
                type="application/pdf"
              />

              <pre
                v-else-if="isText"
                class="w-full max-w-full overflow-auto rounded-2xl bg-slate-50 p-5 font-mono text-sm leading-6 text-slate-700 dark:bg-slate-950 dark:text-slate-200"
              >{{ textContent }}</pre>

              <iframe
                v-else-if="isOffice"
                :src="officePreviewUrl"
                class="h-[72vh] w-full rounded-2xl"
                frameborder="0"
              />

              <video
                v-else-if="isVideo"
                :src="objectUrl"
                controls
                class="max-h-[72vh] w-auto max-w-full rounded-2xl"
              />

              <audio
                v-else-if="isAudio"
                :src="objectUrl"
                controls
                class="w-full max-w-2xl"
              />

              <div
                v-else
                class="flex min-h-[50vh] flex-col items-center justify-center text-center"
              >
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 dark:bg-slate-800">
                  <DocumentIcon class="h-10 w-10 text-slate-400 dark:text-slate-500" />
                </div>
                <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-slate-100">
                  Pré-visualização indisponível
                </h3>
                <p class="mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                  Este formato não pode ser apresentado directamente aqui. Pode descarregar o ficheiro para o abrir localmente.
                </p>
                <button
                  type="button"
                  @click="downloadFile"
                  class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  Transferir ficheiro
                </button>
              </div>
            </div>
          </div>
        </DialogPanel>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { XMarkIcon, DocumentIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import { saveAs } from 'file-saver'

const props = defineProps<{
  isOpen: boolean
  file: {
    name: string
    content: ArrayBuffer
    mimeType?: string
  } | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const objectUrl = ref<string>('')
const textContent = ref<string>('')

const isImage = computed(() => props.file?.mimeType?.startsWith('image/'))
const isPdf = computed(() => props.file?.mimeType === 'application/pdf')
const isText = computed(() => props.file?.mimeType?.startsWith('text/'))
const isVideo = computed(() => props.file?.mimeType?.startsWith('video/'))
const isAudio = computed(() => props.file?.mimeType?.startsWith('audio/'))
const isOffice = computed(() => [
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  'application/msword',
  'application/vnd.ms-excel',
  'application/vnd.ms-powerpoint',
].includes(props.file?.mimeType || ''))

const officePreviewUrl = computed(() => {
  if (!props.file || !objectUrl.value) {
    return ''
  }

  return `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(objectUrl.value)}`
})

watch(() => props.file, async (newFile) => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = ''
  }

  textContent.value = ''

  if (newFile?.content) {
    const blob = new Blob([newFile.content], { type: newFile.mimeType })
    objectUrl.value = URL.createObjectURL(blob)

    if (isText.value) {
      textContent.value = await blob.text()
    }
  }
})

function close(): void {
  emit('close')
}

function downloadFile(): void {
  if (!props.file) {
    return
  }

  const blob = new Blob([props.file.content], { type: props.file.mimeType })
  saveAs(blob, props.file.name)
}

onUnmounted(() => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value)
  }
})
</script>
