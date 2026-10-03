<template>
  <nav class="min-w-0" aria-label="Pasta actual">
    <ol class="flex min-w-0 flex-wrap items-center gap-1.5 text-[13px]">
      <li>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 text-[var(--pl-muted)] hover:text-[var(--pl-fg)]"
          :class="{ 'font-medium text-[var(--pl-fg)]': !fileStore.currentFolder }"
          :aria-current="!fileStore.currentFolder ? 'page' : undefined"
          @click="navigateToFolder(null)"
        >
          <HomeIcon class="h-4 w-4" aria-hidden="true" />
          <span class="pl-k">Raiz</span>
        </button>
      </li>
      <li v-for="(folder, index) in fileStore.breadcrumbs" :key="folder.id" class="flex min-w-0 items-center gap-1.5">
        <span class="text-[var(--pl-faint)]" aria-hidden="true">/</span>
        <button
          type="button"
          class="truncate text-[var(--pl-muted)] hover:text-[var(--pl-fg)] disabled:cursor-default"
          :class="{ 'font-medium text-[var(--pl-fg)]': index === fileStore.breadcrumbs.length - 1 }"
          :aria-current="index === fileStore.breadcrumbs.length - 1 ? 'page' : undefined"
          :disabled="index === fileStore.breadcrumbs.length - 1"
          @click="navigateToFolder(folder.id)"
        >
          {{ folder.name }}
        </button>
      </li>
      <li v-if="fileStore.isLoading && fileStore.currentFolder" class="flex items-center gap-1.5 text-[var(--pl-faint)]" role="status">
        <span aria-hidden="true">/</span>
        A carregar…
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { House as HomeIcon } from '@lucide/vue'
import { useFileStore } from '../../Stores/fileStore'

const fileStore = useFileStore()

async function navigateToFolder(folderId: string | null) {
  await fileStore.navigateToFolder(folderId)
}
</script>
