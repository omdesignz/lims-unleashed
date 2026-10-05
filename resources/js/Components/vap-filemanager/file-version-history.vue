<template>
  <Dialog :open="isOpen" class="relative z-50" @close="close">
    <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
    <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
      <div class="flex min-h-full items-center justify-center">
        <DialogPanel
          class="ds-modal-panel flex h-[88vh] w-full max-w-5xl flex-col overflow-hidden"
          role="dialog"
          aria-labelledby="version-history-title"
        >
          <div class="flex items-start justify-between gap-4 border-b border-[var(--pl-line)] px-6 py-4">
            <div class="grid min-w-0 gap-1">
              <DialogTitle id="version-history-title" class="pl-d3 truncate">Histórico de versões</DialogTitle>
              <p class="text-sm text-[var(--pl-muted)]">
                {{ file?.name || 'Ficheiro' }}. Consulte revisões, restaure versões anteriores e compare alterações.
              </p>
            </div>
            <button type="button" class="ds-icon-button" aria-label="Fechar histórico" @click="close">
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>

          <div class="flex-1 overflow-auto p-4 sm:p-6">
            <p v-if="comparisonError" role="alert" class="pl-banner pl-banner-bad mb-4 text-sm">
              {{ comparisonError }}
            </p>

            <ol v-if="sortedVersions.length" class="pl-panel" aria-label="Versões do ficheiro">
              <li
                v-for="version in sortedVersions"
                :key="version.id"
                class="flex flex-col gap-3 border-b border-[var(--pl-line)] px-4 py-4 last:border-b-0 lg:flex-row lg:items-start lg:justify-between"
                :class="{ 'bg-[var(--pl-layer)]': version.id === file?.currentVersionId }"
              >
                <div class="grid min-w-0 gap-2">
                  <div class="flex flex-wrap items-center gap-2">
                    <span v-if="version.revision_code" class="pl-tag">{{ version.revision_code }}</span>
                    <StatusChip :tone="version.id === file?.currentVersionId ? 'ok' : 'neutral'">
                      {{ version.id === file?.currentVersionId ? 'Versão actual' : 'Revisão anterior' }}
                    </StatusChip>
                  </div>
                  <dl class="grid gap-x-6 gap-y-1 text-[12.5px] text-[var(--pl-muted)] sm:grid-cols-2">
                    <div class="flex gap-1"><dt>Criada em:</dt><dd class="pl-num">{{ formatDate(version.createdAt) }}</dd></div>
                    <div class="flex gap-1"><dt>Por:</dt><dd>{{ version.createdBy }}</dd></div>
                  </dl>
                  <p v-if="version.comment" class="text-sm leading-6">{{ version.comment }}</p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                  <button
                    v-if="previousVersion(version) && canCompareVersions(version, previousVersion(version)!)"
                    type="button"
                    class="ds-button ds-button-quiet"
                    :disabled="isComparing"
                    @click="compareVersions(version, previousVersion(version)!)"
                  >
                    {{ isComparing ? 'A comparar…' : 'Comparar' }}
                  </button>
                  <button
                    v-if="version.id !== file?.currentVersionId"
                    type="button"
                    class="ds-button ds-button-secondary"
                    @click="restoreVersion(version.id)"
                  >
                    Restaurar versão
                  </button>
                </div>
              </li>
            </ol>

            <div v-else class="ds-empty-state grid justify-items-start gap-2 p-6">
              <span class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.no_versions_title') }}</span>
              <p class="text-sm text-[var(--pl-muted)]">O histórico passará a aparecer aqui assim que o ficheiro tiver novas versões registadas.</p>
            </div>
          </div>

          <Dialog :open="showComparison" class="relative z-[60]" @close="closeComparison">
            <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
            <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
              <div class="flex min-h-full items-center justify-center">
                <DialogPanel
                  class="ds-modal-panel flex h-[88vh] w-full max-w-6xl flex-col overflow-hidden"
                  role="dialog"
                  aria-labelledby="version-comparison-title"
                >
                  <div class="flex items-start justify-between gap-4 border-b border-[var(--pl-line)] px-6 py-4">
                    <div class="grid gap-1">
                      <DialogTitle id="version-comparison-title" class="pl-d3">Comparação entre versões</DialogTitle>
                      <p class="text-sm text-[var(--pl-muted)]">Veja o conteúdo anterior, o novo conteúdo e as diferenças textuais para auditoria.</p>
                    </div>
                    <button type="button" class="ds-icon-button" aria-label="Fechar comparação" @click="closeComparison">
                      <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                  </div>

                  <div class="grid flex-1 content-start gap-4 overflow-auto p-4 sm:p-6">
                    <div class="grid gap-4 xl:grid-cols-2">
                      <section class="pl-panel min-w-0" aria-labelledby="version-older-title">
                        <div class="pl-panel-head">
                          <h3 id="version-older-title" class="pl-k">Versão anterior</h3>
                          <span class="pl-k pl-faint">{{ formatDate(comparisonVersions.older?.createdAt) }}</span>
                        </div>
                        <pre class="max-h-[48vh] overflow-auto p-4 text-sm">{{ oldContent }}</pre>
                      </section>

                      <section class="pl-panel min-w-0" aria-labelledby="version-newer-title">
                        <div class="pl-panel-head">
                          <h3 id="version-newer-title" class="pl-k">Versão nova</h3>
                          <span class="pl-k pl-faint">{{ formatDate(comparisonVersions.newer?.createdAt) }}</span>
                        </div>
                        <pre class="max-h-[48vh] overflow-auto p-4 text-sm">{{ newContent }}</pre>
                      </section>
                    </div>

                    <section class="pl-panel min-w-0" aria-labelledby="version-diff-title">
                      <div class="pl-panel-head">
                        <h3 id="version-diff-title" class="pl-k">Diferenças detectadas</h3>
                      </div>
                      <div class="overflow-auto p-4">
                        <pre
                          v-for="(change, index) in diffResult"
                          :key="index"
                          class="whitespace-pre-wrap text-sm leading-6"
                          :class="{
                            'text-[var(--pl-bad)]': change.removed,
                            'text-[var(--pl-ok)]': change.added,
                            'text-[var(--pl-muted)]': !change.added && !change.removed,
                          }"
                        >{{ markedDiffLines(change) }}</pre>
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
import { X as XMarkIcon } from '@lucide/vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
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

// Every added or removed line carries its mark, not only the first of a run;
// unchanged lines are indented to stay in column with them.
function markedDiffLines(change: { added?: boolean; removed?: boolean; value: string }): string {
  const mark = change.added ? '+ ' : change.removed ? '- ' : '  '

  return change.value.replace(/\n$/, '').split('\n').map((line) => mark + line).join('\n')
}

function restoreVersion(versionId: string): void {
  if (props.fileId) {
    fileStore.restoreVersion(props.fileId, versionId)
  }
}
</script>
