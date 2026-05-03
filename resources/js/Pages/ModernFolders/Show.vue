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
    <!-- <div>
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">
            {{ props.folder.name }}
        </h1>

        <breadcrumbs :pages="props.breadcrumbs" />

        <folderItem v-for="child in props.folder.children" :key="child.id" :folder="child" />
    </div> -->
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import Layout from "@/Shared/Layouts/Layout.vue";
  import FilemanagerLayout from "@/Shared/Layouts/FilemanagerLayout.vue";
  import { router } from '@inertiajs/vue3';
  import axios from 'axios';
  import breadcrumbs from '@/Components/breadcrumbs.vue';
  import folderItem from '@/Pages/Folders/folder-item.vue';

  defineOptions({
    layout: [Layout, FilemanagerLayout]
  });


const props = defineProps({
    folder: Object,
    breadcrumbs: Array,
});


//   const folders = ref([]);
//   const files = ref([]);

//   const fetchFilesAndFolders = async (parentFolderId = null) => {
//     try {
//       const response = await axios.get('/folders/folders-list', { params: { parent_id: parentFolderId } });
//       folders.value = response.data.folders;
//       files.value = response.data.files;
//     } catch (error) {
//       console.error('Error fetching folders and files:', error);
//     }
//   };

//   const navigateToFolder = (folder) => {
//     router.get(route('folders.show', folder.id));
//   };

  onMounted(() => {
    fetchFilesAndFolders();
  });
  
  </script>
  