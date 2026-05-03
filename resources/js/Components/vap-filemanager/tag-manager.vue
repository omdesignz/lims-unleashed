<template>
  <div class="w-full">
    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/70">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            Etiquetas activas
          </h3>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Use etiquetas consistentes para facilitar pesquisa, classificação e rastreabilidade.
          </p>
        </div>
        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">
          {{ tags.length }} {{ tags.length === 1 ? 'etiqueta' : 'etiquetas' }}
        </span>
      </div>

      <div class="mt-4 flex min-h-16 flex-wrap gap-2">
        <span
          v-for="tag in tags"
          :key="tag"
          class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1.5 text-sm font-medium text-blue-800 ring-1 ring-blue-200 dark:bg-blue-950/50 dark:text-blue-200 dark:ring-blue-800"
        >
          {{ tag }}
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full text-blue-700 transition hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100"
            @click="removeTag(tag)"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </span>

        <div
          v-if="!tags.length"
          class="flex w-full items-center rounded-2xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400"
        >
          Ainda não existem etiquetas atribuídas a este ficheiro.
        </div>
      </div>
    </div>

    <div class="mt-4 rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <label class="block text-sm font-semibold text-slate-900 dark:text-slate-100">
        Adicionar nova etiqueta
      </label>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Evite duplicados e prefira nomes curtos e claros.
      </p>
      <div class="mt-4 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
          <input
            v-model="newTag"
            type="text"
            @keydown.enter.prevent="addTag"
            placeholder="Ex.: procedimento, certificado, revisão anual"
            class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-blue-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-900/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-blue-500 dark:focus:bg-slate-900 dark:focus:ring-blue-500/20"
          />
        </div>
        <button
          v-if="newTag"
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800"
          @click="addTag"
        >
          <PlusIcon class="h-4 w-4" />
          Adicionar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { XMarkIcon, PlusIcon } from '@heroicons/vue/24/outline'
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
