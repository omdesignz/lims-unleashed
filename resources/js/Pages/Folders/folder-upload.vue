<template>
    <div>
      <h2>Upload Folder</h2>
      <form @submit.prevent="uploadFolder">
        <input type="file" webkitdirectory multiple @change="handleFiles" />
        <button type="submit">Upload Folder</button>
      </form>

      <div v-if="progress > 0">
        <progress :value="progress" max="100">{{ progress }}%</progress>
        </div>

      <div v-if="folderStructure.length">
        <h3>Folder Contents2:</h3>
        <ul>
          <li v-for="file in folderStructure" :key="file.relativePath">{{ file.relativePath }}</li>
        </ul>
      </div>
      
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { useForm, router } from "@inertiajs/vue3";

  const folderStructure = ref([]);
  const progress = ref(0);
  const uploading = ref(false);

  const handleFiles = (event) => {
    const files = event.target.files;
    folderStructure.value = Array.from(files).map(file => ({
      file,
      relativePath: file.webkitRelativePath, // This provides the folder structure
    }));
  };

  const uploadFolder = async () => {
    const formData = new FormData();
    
    // Append each file along with its relative path to FormData
    folderStructure.value.forEach(item => {
      formData.append('files[]', item.file);
      formData.append('relative_paths[]', item.relativePath); // Send the relative path for correct folder structure
    });

    try {

        // Set the progress to 0 before uploading
        uploading.value = true;
        progress.value = 0;
      // Submit the form data to the server
      await router.post('/files/upload-folder', formData, {
        onProgress: (event) => {
          if(event.lengthComputable) {
            progress.value = Math.round((event.loaded / event.total) * 100);
          }
        },
      });

      // Reset the progress and uploading state
      uploading.value = false;
      progress.value = 0;
      
    } catch (error) {
      console.error('Error uploading folder:', error);
    }
  };

  </script>
  