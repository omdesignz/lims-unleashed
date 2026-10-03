<template>
  <div class="grid w-full gap-6">
    <section class="pl-panel" aria-labelledby="tag-manager-active">
      <div class="pl-panel-head">
        <h3 id="tag-manager-active" class="pl-k">Etiquetas activas</h3>
        <span class="pl-k pl-faint">{{ tags.length }} {{ tags.length === 1 ? 'etiqueta' : 'etiquetas' }}</span>
      </div>
      <div class="flex min-h-16 flex-wrap items-center gap-2 p-4">
        <span v-for="tag in tags" :key="tag" class="ds-chip">
          {{ tag }}
          <button type="button" class="inline-flex" :aria-label="`Remover etiqueta ${tag}`" @click="removeTag(tag)">
            <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
        </span>
        <p v-if="!tags.length" class="text-sm text-[var(--pl-muted)]">
          {{ $t('gestlab.general.labels.vap_filemanager.tags_empty') }}
        </p>
      </div>
    </section>

    <form class="grid gap-3" @submit.prevent="addTag">
      <BaseInput
        v-model="newTag"
        type="text"
        class="ds-field"
        label="Adicionar nova etiqueta"
        hint="Evite duplicados e prefira nomes curtos e claros."
        :placeholder="$t('gestlab.general.labels.vap_filemanager.tag_placeholder')"
      />
      <div>
        <button type="submit" class="ds-button ds-button-secondary" :disabled="!newTag.trim()">
          <PlusIcon class="h-4 w-4" aria-hidden="true" />
          Adicionar
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { X as XMarkIcon, Plus as PlusIcon } from '@lucide/vue'
import { useFileStore } from '../../Stores/fileStore'

const props = defineProps<{
  fileId: string
}>()

const fileStore = useFileStore()
const tags = ref<string[]>([])
const newTag = ref('')

watch(() => props.fileId, () => {
  const file = fileStore.files.find((currentFile) => currentFile.id === props.fileId)

  if (file) {
    tags.value = file.tags || []
  }
}, { immediate: true })

function addTag(): void {
  const normalizedTag = newTag.value.trim()

  if (!normalizedTag || tags.value.includes(normalizedTag)) {
    return
  }

  tags.value.push(normalizedTag)
  fileStore.updateFileTags(props.fileId, tags.value)
  newTag.value = ''
}

function removeTag(tag: string): void {
  tags.value = tags.value.filter((currentTag) => currentTag !== tag)
  fileStore.updateFileTags(props.fileId, tags.value)
}
</script>
