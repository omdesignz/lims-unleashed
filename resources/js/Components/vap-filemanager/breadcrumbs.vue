<template>
    <nav class="flex" aria-label="Breadcrumb">
      <ol class="flex items-center space-x-2 m-2 px-4 text-xs">
        <li>
          <button
            @click="navigateToFolder(null)"
            class="text-gray-500 hover:text-gray-700"
            :class="{ 'font-medium text-gray-900': !fileStore.currentFolder }"
          >
            <HomeIcon class="h-4 w-4" />
          </button>
        </li>
        
        <li v-for="(folder, index) in fileStore.breadcrumbs" :key="folder.id" class="flex items-center">
            <ChevronRightIcon class="h-4 w-4 text-gray-400" />
            <button
              @click="navigateToFolder(folder.id)"
              class="ml-2 text-gray-500 hover:text-gray-700"
              :class="{ 'font-medium text-gray-900': index === fileStore.breadcrumbs.length - 1 }"
              :disabled="index === fileStore.breadcrumbs.length - 1"
            >
              {{ folder.name }}
            </button>
          </li>
        
        <li v-if="fileStore.isLoading && fileStore.currentFolder" class="flex items-center">
          <ChevronRightIcon class="h-4 w-4 text-gray-400" />
          <span class="ml-2 text-gray-400">Loading...</span>
        </li>
      </ol>
    </nav>
</template>
  
  <script setup lang="ts">
  import { computed } from 'vue'
  import { ChevronRightIcon } from '@heroicons/vue/24/outline'
  import { useFileStore } from '../../Stores/fileStore'
  import { 
  HomeIcon,
} from '@heroicons/vue/24/outline'

  
const fileStore = useFileStore();

// 💡 The problematic line 'const breadcrumbs = fileStore.breadcrumbs;' is removed.
// The template will now correctly use the reactive property directly via fileStore.breadcrumbs.

async function navigateToFolder(folderId: string | null) {
  // The store's navigateToFolder is correctly implemented to call loadBreadcrumbs
  await fileStore.navigateToFolder(folderId)

  // This log should now show the correct array after navigation:
  console.log('Breadcrumbs after navigation:', fileStore.breadcrumbs); 
}

  </script>