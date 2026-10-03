<template>
  <Dialog :open="isOpen" @close="close" class="relative z-50">
    <div class="fixed inset-0 bg-slate-950/55 backdrop-blur-sm" aria-hidden="true" />
    <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
      <div class="flex min-h-full items-center justify-center">
        <DialogPanel
          class="flex h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
          role="dialog"
          aria-labelledby="version-history-title"
        >
          <div class="border-b border-slate-200 bg-slate-50/90 px-6 py-4 dark:border-slate-700 dark:bg-slate-800/90">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <DialogTitle
                  class="truncate text-lg font-semibold text-slate-900 dark:text-slate-100"
                  id="version-history-title"
                >
                  Histórico de versões
                </DialogTitle>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  {{ file?.name || 'Ficheiro' }}. Consulte revisões, restaure versões anteriores e compare alterações.
                </p>
              </div>
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                @click="close"
                aria-label="Fechar histórico"
              >
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-auto bg-slate-100/80 p-4 dark:bg-slate-950/70 sm:p-6">
            <p v-if="comparisonError" role="alert" class="mb-4 text-sm font-semibold text-rose-700 dark:text-rose-300">
              {{ comparisonError }}
            </p>
            <div v-if="sortedVersions.length" class="space-y-4">
              <article
                v-for="version in sortedVersions"
                :key="version.id"
                class="rounded-[24px] border p-5 shadow-sm transition"
                :class="version.id === file?.currentVersionId
                  ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/40'
                  : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'"
              >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                      <span
                        v-if="version.id === file?.currentVersionId"
                        class="inline-flex items-center rounded-full bg-blue-900 px-3 py-1 text-xs font-semibold text-white"
                      >
                        Versão actual
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                      >
                        Revisão anterior
                      </span>
                    </div>
                    <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 sm:grid-cols-2">
                      <p>
                        <span class="font-medium text-slate-700 dark:text-slate-200">Criada em:</span>
                        {{ formatDate(version.createdAt) }}
                      </p>
                      <p>
                        <span class="font-medium text-slate-700 dark:text-slate-200">Por:</span>
                        {{ version.createdBy }}
                      </p>
                    </div>
                    <p v-if="version.comment" class="text-sm leading-6 text-slate-600 dark:text-slate-300">
                      {{ version.comment }}
                    </p>
                  </div>

                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      v-if="previousVersion(version) && canCompareVersions(version, previousVersion(version)!)"
                      type="button"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                      :disabled="isComparing"
                      @click="compareVersions(version, previousVersion(version)!)"
                    >
                      {{ isComparing ? 'A comparar…' : 'Comparar' }}
                    </button>
                    <button
                      v-if="version.id !== file?.currentVersionId"
                      type="button"
                      class="rounded-xl bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800"
                      @click="restoreVersion(version.id)"
                    >
                      Restaurar versão
                    </button>
                  </div>
                </div>
              </article>
            </div>

            <div v-else class="flex min-h-[50vh] flex-col items-center justify-center text-center">
              <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 dark:bg-slate-800">
                <ClockIcon class="h-10 w-10 text-slate-400 dark:text-slate-500" />
              </div>
              <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.vap_filemanager.no_versions_title') }}
              </h3>
              <p class="mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                O histórico passará a aparecer aqui assim que o ficheiro tiver novas versões registadas.
              </p>
            </div>
          </div>

          <Dialog :open="showComparison" @close="closeComparison" class="relative z-[60]">
            <div class="fixed inset-0 bg-slate-950/55 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
              <div class="flex min-h-full items-center justify-center">
                <DialogPanel
                  class="flex h-[88vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                  role="dialog"
                  aria-labelledby="version-comparison-title"
                >
                  <div class="border-b border-slate-200 bg-slate-50/90 px-6 py-4 dark:border-slate-700 dark:bg-slate-800/90">
                    <div class="flex items-start justify-between gap-4">
                      <div>
                        <DialogTitle
                          class="text-lg font-semibold text-slate-900 dark:text-slate-100"
                          id="version-comparison-title"
                        >
                          Comparação entre versões
                        </DialogTitle>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                          Veja o conteúdo anterior, o novo conteúdo e o diff textual para auditoria.
                        </p>
                      </div>
                      <button
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        @click="closeComparison"
                        aria-label="Fechar comparação"
                      >
                        <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                      </button>
                    </div>
                  </div>

                  <div class="flex-1 overflow-auto bg-slate-100/80 p-4 dark:bg-slate-950/70 sm:p-6">
                    <div class="grid gap-4 xl:grid-cols-2">
                      <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                          Versão anterior
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                          {{ formatDate(comparisonVersions.older?.createdAt) }}
                        </p>
                        <pre class="mt-4 max-h-[48vh] overflow-auto rounded-2xl bg-slate-50 p-4 text-sm text-slate-700 dark:bg-slate-950 dark:text-slate-200">{{ oldContent }}</pre>
                      </section>

                      <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                          Versão nova
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                          {{ formatDate(comparisonVersions.newer?.createdAt) }}
                        </p>
                        <pre class="mt-4 max-h-[48vh] overflow-auto rounded-2xl bg-slate-50 p-4 text-sm text-slate-700 dark:bg-slate-950 dark:text-slate-200">{{ newContent }}</pre>
                      </section>
                    </div>

                    <section class="mt-4 rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                        Diferenças detectadas
                      </h3>
                      <div class="mt-4 overflow-auto rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                        <pre
                          v-for="(change, index) in diffResult"
                          :key="index"
                          class="whitespace-pre-wrap text-sm leading-6"
                          :class="{
                            'text-red-600 dark:text-red-400': change.removed,
                            'text-emerald-700 dark:text-emerald-400': change.added,
                            'text-slate-600 dark:text-slate-300': !change.added && !change.removed,
                          }"
                        >{{ change.value }}</pre>
                      </div>
                    </section>
                  </div>
                </DialogPanel>
              </div>
            </div>
          </Dialog>
        </DialogPanel>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { Clock as ClockIcon, X as XMarkIcon } from '@lucide/vue'
import { diffLines, type Change } from 'diff'
import { useFileStore, type FileVersion } from '../../Stores/fileStore'

const props = defineProps<{
  isOpen: boolean
  fileId: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const fileStore = useFileStore()
const showComparison = ref(false)
const comparisonVersions = ref<{
  newer?: FileVersion
  older?: FileVersion
}>({})
const diffResult = ref<Change[]>([])
const oldContent = ref('')
const newContent = ref('')
const comparisonError = ref('')
const isComparing = ref(false)

const file = computed(() => fileStore.files.find((currentFile) => currentFile.id === props.fileId))

const sortedVersions = computed(() => {
  return [...(file.value?.versions || [])].sort((a, b) => {
    const revisionOrder = (b.revision_code || '').localeCompare(a.revision_code || '', undefined, { numeric: true })

    return revisionOrder || b.createdAt.getTime() - a.createdAt.getTime()
  })
})

function formatDate(date?: Date): string {
  if (!date) {
    return ''
  }

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

function close(): void {
  emit('close')
}

function previousVersion(version: FileVersion): FileVersion | null {
  const index = sortedVersions.value.findIndex((currentVersion) => currentVersion.id === version.id)

  return index < sortedVersions.value.length - 1 ? sortedVersions.value[index + 1] : null
}

function canCompareVersions(newer: FileVersion, older: FileVersion): boolean {
  const isText = (version: FileVersion): boolean => {
    const mimeType = version.mime_type || ''

    return mimeType.startsWith('text/') || ['application/json', 'application/xml', 'application/javascript'].includes(mimeType)
  }

  return isText(newer) && isText(older)
}

async function compareVersions(newer: FileVersion, older: FileVersion): Promise<void> {
  if (isComparing.value) return

  comparisonError.value = ''
  isComparing.value = true

  try {
    const response = await fetch(route('files.versions.compare', {
      file: props.fileId,
      older: older.id,
      newer: newer.id,
    }), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
      comparisonError.value = response.status === 413
        ? 'Estas versões são demasiado grandes para comparação textual.'
        : 'Não foi possível comparar estas versões. Tente novamente.'
      return
    }

    const comparison = await response.json()
    comparisonVersions.value = { newer, older }
    oldContent.value = comparison.older.text
    newContent.value = comparison.newer.text
    diffResult.value = diffLines(oldContent.value, newContent.value)
    showComparison.value = true
  } catch {
    comparisonError.value = 'Não foi possível comparar estas versões. Tente novamente.'
  } finally {
    isComparing.value = false
  }
}

function closeComparison(): void {
  showComparison.value = false
  comparisonVersions.value = {}
  diffResult.value = []
  oldContent.value = ''
  newContent.value = ''
}

function restoreVersion(versionId: string): void {
  if (props.fileId) {
    fileStore.restoreVersion(props.fileId, versionId)
  }
}
</script>
