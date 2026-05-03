<template>
    <div class="file-manager">
      <h2>Hello</h2>

      <ul ref="parent">
        <li v-for="tape in tapes" :key="tape" class="cassette">
          {{ tape.name }}
        </li>
        <li id="no-drag">I am not draggable</li>
      </ul>
      <!-- <ul ref="parent">
        <li v-for="folder in tapes" :key="folder" class="cassette">
          {{ folder }}
        </li>
        <li id="no-drag1">I am not draggable</li>
      </ul> -->
      <!-- Folder Structure -->
      <!-- <div
      v-for="folder in folders"
      :key="folder.id"
      class="folder"
      @dragover.prevent
      @drop="onDrop(folder)"
    > -->
      <!-- Folder Header for dragging -->
      <!-- <div
        class="folder-header"
        @dragstart="onDragStart(folder, 'folder')"
        draggable="true"
      >
        <span>{{ folder.name }}</span>
      </div> -->

      <!-- Files within the folder -->
      <!-- <div class="folder-contents">
        <div
          v-for="file in folder.files"
          :key="file.id"
          class="file"
          @dragstart="onDragStart(file, 'file')"
          draggable="true"
        >
          <span>{{ file.name }}</span>
        </div>
      </div> -->
      <!-- </div> -->
    </div>
  </template>
  
  <script setup>
//   import Layout from "@/Shared/Layouts/Layout.vue";
  import { ref, onMounted } from 'vue'
  import { useDragAndDrop } from "@formkit/drag-and-drop/vue";
  import axios from 'axios'
import { handleEnd } from '@formkit/drag-and-drop';

  const folders = ref([]) // Loaded from the API
  const draggedItem = ref(null)
  const draggedType = ref('') // 'file' or 'folder'

  // const [parent, tapes] = useDragAndDrop(
  //   [
  //     'One',
  //     'Two',
  //     'Three'
  //   ],
  //   {
  //     draggable: (el) => {
  //       return el.id !== 'no-drag';
  //     },
  //   });

  const [parent, tapes] = useDragAndDrop(folders, {
    
    draggable: (el) => {
          return el.id !== "no-drag";
        },

    handleEnd: (event) => {
      console.log('Drag end', event);
    },

    handleDragstart: (event) => {
      console.log('Drag start', event);
    },

    group: 'folders',

    });

//   defineOptions({
//     layout: Layout
//   });

  const loadFolders = async () => {
    const response = await axios.get('/folders/folders-list')
    folders.value = response.data.folders
  }

  onMounted(async () => {
    await loadFolders()
  })
 
  </script>
  
  <style>
  .file-manager {
    display: flex;
    flex-direction: column;
  }
  .folder-header {
    font-weight: bold;
    cursor: pointer;
  }
  .folder-contents {
    padding-left: 20px;
  }
  .file {
    cursor: pointer;
    padding: 5px 0;
  }
  </style>
  