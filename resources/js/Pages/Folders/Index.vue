<template>
    <!-- <div>
       <h2>Drag and Drop Filemanager</h2>
       <Filemanager />
       <br>
       <br>
      <h2>Folders</h2>
      <ul>
        <folder-item
          v-for="folder in folders"
          :key="folder.id"
          :folder="folder"
          @navigate="navigateToFolder"
        />
      </ul>
  
      <h3>Files</h3>
      <ul>
        <li v-for="file in files" :key="file.id">
          {{ file.name }}
        </li>
      </ul>
    </div> -->
    <div>
        <Filemanager />
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { router } from '@inertiajs/vue3';
  import axios from 'axios';
//   import FolderItem from './folder-item.vue';
//   import Filemanager from './dd-filemanager.vue';
import Filemanager from "@/Components/file-manager/manager.vue";


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
  