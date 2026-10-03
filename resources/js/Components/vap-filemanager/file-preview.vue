<template>
  <Dialog :open="isOpen" class="relative z-50" @close="close">
    <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
    <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
      <div class="flex min-h-full items-center justify-center">
        <DialogPanel class="ds-modal-panel flex h-[88vh] w-full max-w-6xl flex-col overflow-hidden">
          <div class="flex items-start justify-between gap-4 border-b border-[var(--pl-line)] px-6 py-4">
            <div class="grid min-w-0 gap-1">
              <DialogTitle class="pl-d3 truncate">{{ file?.name }}</DialogTitle>
              <p class="text-sm text-[var(--pl-muted)]">Pré-visualização do ficheiro para validação rápida antes de transferir ou partilhar.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <button type="button" class="ds-button ds-button-secondary" @click="downloadFile">
                <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                Transferir
              </button>
              <button type="button" class="ds-icon-button" aria-label="Fechar pré-visualização" @click="close">
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
          </div>

          <div class="flex flex-1 items-center justify-center overflow-auto bg-[var(--pl-layer)] p-4 sm:p-6">
            <img
              v-if="isImage"
              :src="objectUrl"
              class="max-h-[72vh] w-auto max-w-full object-contain"
              :alt="`Pré-visualização de ${file?.name ?? 'imagem'}`"
            />

            <iframe
              v-else-if="isPdf"
              :src="objectUrl"
              :title="file?.name ?? 'Documento PDF'"
              class="h-[72vh] w-full border border-[var(--pl-line)] bg-[var(--pl-bg)]"
              type="application/pdf"
            />

            <pre
              v-else-if="isText"
              class="pl-panel w-full max-w-full overflow-auto p-5 font-mono text-sm leading-6"
            >{{ textContent }}</pre>

            <iframe
              v-else-if="isOffice"
              :src="officePreviewUrl"
              :title="file?.name ?? 'Documento'"
              class="h-[72vh] w-full border border-[var(--pl-line)] bg-[var(--pl-bg)]"
              frameborder="0"
            />

            <video
              v-else-if="isVideo"
              :src="objectUrl"
              controls
              class="max-h-[72vh] w-auto max-w-full"
            />

            <audio
              v-else-if="isAudio"
              :src="objectUrl"
              controls
              class="w-full max-w-2xl"
            />

            <div v-else class="ds-empty-state grid max-w-md justify-items-start gap-2 p-6">
              <span class="pl-k">Pré-visualização indisponível</span>
              <p class="text-sm text-[var(--pl-muted)]">Este formato não pode ser apresentado directamente aqui. Transfira o ficheiro para o abrir localmente.</p>
              <button type="button" class="ds-button ds-button-primary mt-2" @click="downloadFile">
                <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                Transferir ficheiro
              </button>
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
import { X as XMarkIcon, Download as ArrowDownTrayIcon } from '@lucide/vue'
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
