<template>
    <nav class="flex" aria-label="Breadcrumb">
      <ol class="m-2 flex items-center space-x-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-xs shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <li>
          <button
            @click="navigateToFolder(null)"
            class="text-gray-500 transition hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-100"
            :class="{ 'font-medium text-gray-900 dark:text-slate-100': !fileStore.currentFolder }"
          >
            <HomeIcon class="h-4 w-4" />
          </button>
        </li>
        
        <li v-for="(folder, index) in fileStore.breadcrumbs" :key="folder.id" class="flex items-center">
            <ChevronRightIcon class="h-4 w-4 text-gray-400 dark:text-slate-600" />
            <button
              @click="navigateToFolder(folder.id)"
              class="ml-2 text-gray-500 transition hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-100"
              :class="{ 'font-medium text-gray-900 dark:text-slate-100': index === fileStore.breadcrumbs.length - 1 }"
              :disabled="index === fileStore.breadcrumbs.length - 1"
            >
              {{ folder.name }}
            </button>
          </li>
        
        <li v-if="fileStore.isLoading && fileStore.currentFolder" class="flex items-center">
          <ChevronRightIcon class="h-4 w-4 text-gray-400 dark:text-slate-600" />
          <span class="ml-2 text-gray-400 dark:text-slate-500">A carregar...</span>
        </li>
      </ol>
    </nav>
</template>
  
  <script setup lang="ts">
  import { ChevronRightIcon } from '@heroicons/vue/24/outline'
  import { useFileStore } from '../../Stores/fileStore'
  import { 
  HomeIcon,
} from '@heroicons/vue/24/outline'

  
const fileStore = useFileStore();

// 💡 The problematic line 'const breadcrumbs = fileStore.breadcrumbs;' is removed.
// The template will now correctly use the reactive property directly via fileStore.breadcrumbs.

async function navigateToFolder(folderId: string | null) {
  await fileStore.navigateToFolder(folderId)
}

  </script>
