<template>
    <div>
        <!-- <breadcrumbs :pages="props.breadcrumbs" />
        <folderItem v-for="folder in props.folders" :key="folder.id" :folder="folder" /> -->

        <Filemanager :folders="props.folders" :breadcrumbs="props.breadcrumbs" />
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { router } from '@inertiajs/vue3';
  import axios from 'axios';
  import folderItem from '@/Pages/ModernFolders/modern-folder-item.vue';
  import breadcrumbs from '@/Components/breadcrumbs.vue';
  import Filemanager from "@/Components/file-manager/manager.vue";


const props = defineProps({
    folders: Array,
    breadcrumbs: Array,
});


  const folders = ref([]);
  const files = ref([]);

  const fetchFilesAndFolders = async (parentFolderId = null) => {
    try {
      const response = await axios.get('/folders/folders-list', { params: { parent_id: parentFolderId } });
      folders.value = response.data.folders;
      files.value = response.data.files;
    } catch (error) {
      console.error('Error fetching folders and files:', error);
    }
  };

  const navigateToFolder = (folder) => {
    router.get(route('folders.show', folder.id));
  };

  onMounted(() => {
    fetchFilesAndFolders();
  });
  
  </script>
  