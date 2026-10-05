<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useFileStore } from '@/Stores/fileStore'
import { ChevronRight as ChevronRightIcon, Folder as FolderIcon, FolderOpen as FolderOpenIcon, Library as LibraryIcon } from '@lucide/vue'

/**
 * The library's folders as a tree: where a document lives, and a way to get
 * there in one click from anywhere in the library. The open branch follows
 * the folder being shown.
 */
const fileStore = useFileStore()

interface FolderNode {
  id: string
  name: string
  depth: number
  hasChildren: boolean
  documents: number
}

const folders = computed(() => fileStore.files.filter((file) => file.type === 'folder' && !file.archived))
const childrenOf = computed(() => {
  const map = new Map<string | null, typeof folders.value>()
  folders.value.forEach((folder) => {
    const siblings = map.get(folder.parentId) ?? []
    siblings.push(folder)
    map.set(folder.parentId, siblings)
  })
  map.forEach((siblings) => siblings.sort((a, b) => a.name.localeCompare(b.name)))

  return map
})
const documentCount = computed(() => {
  const counts = new Map<string | null, number>()
  fileStore.files.forEach((file) => {
    if (file.type === 'file' && !file.archived) {
      counts.set(file.parentId, (counts.get(file.parentId) ?? 0) + 1)
    }
  })

  return counts
})

const expanded = ref<Set<string>>(new Set())

// The folder on screen and its ancestors stay open.
watch(() => [fileStore.currentFolder, folders.value.length], () => {
  let id = fileStore.currentFolder
  const next = new Set(expanded.value)
  while (id) {
    next.add(id)
    id = folders.value.find((folder) => folder.id === id)?.parentId ?? null
  }
  expanded.value = next
}, { immediate: true })

const rows = computed<FolderNode[]>(() => {
  const list: FolderNode[] = []
  const walk = (parentId: string | null, depth: number) => {
    (childrenOf.value.get(parentId) ?? []).forEach((folder) => {
      const hasChildren = (childrenOf.value.get(folder.id) ?? []).length > 0
      list.push({ id: folder.id, name: folder.name, depth, hasChildren, documents: documentCount.value.get(folder.id) ?? 0 })
      if (hasChildren && expanded.value.has(folder.id)) {
        walk(folder.id, depth + 1)
      }
    })
  }
  walk(null, 0)

  return list
})

function toggle(id: string): void {
  const next = new Set(expanded.value)
  next.has(id) ? next.delete(id) : next.add(id)
  expanded.value = next
}

function open(id: string | null): void {
  fileStore.navigateToFolder(id)
  fileStore.selectedItems.clear()
}
</script>

<template>
  <nav class="pl-panel" aria-label="Pastas da biblioteca" data-testid="document-folder-tree">
    <header class="pl-panel-head"><h2 class="pl-k">Pastas</h2></header>
    <ul class="max-h-[70vh] overflow-y-auto py-1 text-sm" role="tree">
      <li role="treeitem" :aria-selected="fileStore.currentFolder === null">
        <button
          type="button"
          class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-[var(--pl-layer)]"
          :class="fileStore.currentFolder === null ? 'bg-[var(--pl-layer)] font-bold text-[var(--pl-fg)]' : 'text-[var(--pl-muted)]'"
          @click="open(null)"
        >
          <LibraryIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          <span class="flex-1 truncate">Biblioteca</span>
          <span class="pl-num text-xs text-[var(--pl-faint)]">{{ documentCount.get(null) ?? 0 }}</span>
        </button>
      </li>
      <li v-for="folder in rows" :key="folder.id" role="treeitem" :aria-selected="fileStore.currentFolder === folder.id" :aria-expanded="folder.hasChildren ? expanded.has(folder.id) : undefined">
        <div
          class="flex items-center hover:bg-[var(--pl-layer)]"
          :class="fileStore.currentFolder === folder.id ? 'bg-[var(--pl-layer)]' : ''"
          :style="{ paddingLeft: `${0.5 + folder.depth * 0.9}rem` }"
        >
          <button
            v-if="folder.hasChildren"
            type="button"
            class="grid h-8 w-6 shrink-0 place-items-center text-[var(--pl-faint)] hover:text-[var(--pl-fg)]"
            :aria-label="`${expanded.has(folder.id) ? 'Fechar' : 'Abrir'} ${folder.name}`"
            @click="toggle(folder.id)"
          >
            <ChevronRightIcon class="h-3.5 w-3.5 transition-transform" :class="expanded.has(folder.id) ? 'rotate-90' : ''" aria-hidden="true" />
          </button>
          <span v-else class="w-6 shrink-0" />
          <button
            type="button"
            class="flex min-w-0 flex-1 items-center gap-2 py-2 pr-3 text-left"
            :class="fileStore.currentFolder === folder.id ? 'font-bold text-[var(--pl-fg)]' : 'text-[var(--pl-muted)] hover:text-[var(--pl-fg)]'"
            @click="open(folder.id)"
          >
            <FolderOpenIcon v-if="fileStore.currentFolder === folder.id" class="h-4 w-4 shrink-0" aria-hidden="true" />
            <FolderIcon v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="flex-1 truncate">{{ folder.name }}</span>
            <span v-if="folder.documents" class="pl-num text-xs text-[var(--pl-faint)]">{{ folder.documents }}</span>
          </button>
        </div>
      </li>
    </ul>
    <p v-if="!rows.length" class="border-t border-[var(--pl-line)] px-3 py-3 text-xs text-[var(--pl-muted)]">Sem pastas. Crie uma em «Mais acções».</p>
  </nav>
</template>
