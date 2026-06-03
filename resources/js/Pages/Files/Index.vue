<template>
    <div :class="commercialDocumentThemeClasses">
      <h2>File and Folder Manager</h2>
  
      <!-- Folder Navigation -->
      <div v-if="currentFolder !== 'uploads/'">
        <button @click="navigateToParent">Go Back</button>
      </div>
  
      <!-- Display Folders -->
      <div>
        <h3>Folders</h3>
        <ul>
          <li v-for="folder in folders" :key="folder">
            <button @click="navigateToFolder(folder)">
              {{ folder.split('/').pop() }}
            </button>
          </li>
        </ul>
      </div>
  
      <!-- Display Files -->
      <div>
        <h3>Files</h3>
        <ul>
          <li v-for="file in files" :key="file">
            {{ file.split('/').pop() }}
            <button @click="downloadFile(file)">Download</button>
          </li>
        </ul>
      </div>
  
      <!-- Upload Section -->
      <upload-folder @folder-uploaded="fetchFilesAndFolders" />
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import axios from 'axios';
//   import UploadFolder from './UploadFolder.vue'; // Component for uploading folders
  import UploadFolder from "@/Pages/Folders/folder-upload.vue";
  import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


  const folders = ref([]);
  const files = ref([]);
  const currentFolder = ref('uploads/');

  const fetchFilesAndFolders = async (folder = 'uploads/') => {
    try {
      const response = await axios.get('/files-folders', {
        params: { folder }
      });
      folders.value = response.data.directories;
      files.value = response.data.files;
      currentFolder.value = folder;
    } catch (error) {
      console.error('Error fetching files and folders:', error);
    }
  };

  const navigateToFolder = (folder) => {
    fetchFilesAndFolders(folder);
  };

  const navigateToParent = () => {
    const parentFolder = currentFolder.value.split('/').slice(0, -2).join('/') + '/';
    fetchFilesAndFolders(parentFolder);
  };

  const downloadFile = (file) => {
    window.location.href = `/files/download-file?file=${file}`;
  };

  onMounted(() => {
    fetchFilesAndFolders();
  });

  </script>
