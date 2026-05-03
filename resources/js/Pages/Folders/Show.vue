<template>
    <div>
      <h2>{{ folder.name }}</h2>
      <!-- <button @click="newFolder">New Folder</button> -->
      <button @click="toggleNewFolderForm">New Folder</button>

      <button @click="toggleRenameFolderForm">Rename Folder</button>

      <div v-if="showRenameFolderForm">
        <form @submit.prevent="renameFolder">
            <input v-model="renameFolderName" placeholder="New Folder Name" />
            <button type="submit">Rename</button>
            <button @click="toggleRenameFolderForm">Cancel</button>
        </form>
      </div>

      <div v-if="showNewFolderForm">
        <form @submit.prevent="createFolder">
            <input v-model="newFolderName" placeholder="Folder Name" />
            <button type="submit">Create</button>
            <button @click="toggleNewFolderForm">Cancel</button>
        </form>
      </div>

      <!-- Display Subfolders -->
    <ul>
      <li v-for="subfolder in folder.subfolders" :key="subfolder.id">
        <a @click="openFolder(subfolder)">{{ subfolder.name }}</a>
      </li>
    </ul>

    <!-- Display Files -->

    <ul>
        <li v-for="file in folder.files" :key="file.id">
        <a :href="`/storage/${file.path}`">{{ file.name }}</a>

        <button @click="toggleRenameFileForm(file)">Rename</button>

        <div v-if="showRenameFileForm && renamingFileId === file.id">
            <form @submit.prevent="renameFile(file)">
            <input v-model="renameFileName" :placeholder="file.name" />
            <button type="submit">Rename</button>
            <button @click="toggleRenameFileForm(file)">Cancel</button>
            </form>
        </div>

        <button @click="toggleVersionHistory(file)">View Versions</button>

        <div v-if="file.showVersions">
            <ul>
            <li v-for="version in file.versions" :key="version.id">
                <span>{{ version.name }} ({{ version.created_at }})</span>
                <button @click="revertToVersion(file, version)">Revert</button>
            </li>
            </ul>
        </div>

        <button @click="toggleMoveFileForm(file)">Move</button>

        <div v-if="showMoveFileForm && movingFileId === file.id">
          <form @submit.prevent="moveFile(file)">
            <select v-model="destinationFolderId">
              <option value="" disabled>Select Destination Folder</option>
              <option v-for="folder in availableFolders" :key="folder.id" :value="folder.id">
                {{ folder.name }}
              </option>
            </select>
            <button type="submit">Move</button>
            <button @click="toggleMoveFileForm(file)">Cancel</button>
          </form>
        </div>

        </li>
    </ul>
    </div>
  </template>
  
  <script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref } from 'vue';
import { useForm, router } from "@inertiajs/vue3";
import UploadFolder from "@/Pages/Folders/folder-upload.vue";


  const props = defineProps({
    folder: Object,
    availableFolders: Array,
  });

  const showMoveFileForm = ref(false);
  const movingFileId = ref(null);
  const destinationFolderId = ref('');

  const toggleMoveFileForm = (file) => {
   if(movingFileId.value === file.id) {
      showMoveFileForm.value = false;
      movingFileId.value = null;
    } else {
      showMoveFileForm.value = true;
      movingFileId.value = file.id;
      destinationFolderId.value = null;
    }
  };

  const moveFile = (file) => {
    router.put(route('files.move', file.id), {
      folder_id: destinationFolderId.value,
    }, {
        onSuccess: () => {
          showMoveFileForm.value = false;
          movingFileId.value = null;
        },
    });
  };

  const fileWithVersions = ref([]);

  const toggleVersionHistory = (file) => {
    file.showVersions = !file.showVersions;

    if(!file.versions) {
      fetchFileVersions(file);
    }

  };

  const fetchFileVersions = (file) => {
    router.get(route('files.getFileVersions', file.id), {
      onSuccess: (response) => {
        file.versions = response.data.versions;
      },
    });
  };

  const revertToVersion = (file, version) => {
    if (confirm('Are you sure you want to revert to this version?')) {
        this.$inertia.patch(route('files.versions.revert', { file: file.id, version: version.id }));

        router.put(route('files.revertToVersion', file.id, version.id), {
          onSuccess: () => {},
        });
      }
  };

  const showNewFolderForm = ref(false);
  const newFolderName = ref('');
  const toggleNewFolderForm = () => {
    showNewFolderForm.value = !showNewFolderForm.value;
  };

  const showRenameFolderForm = ref(false);
  const renameFolderName = ref('');

  const toggleRenameFolderForm = () => {
    showRenameFolderForm.value = !showRenameFolderForm.value;
  };

  const renameFolder = () => {
    // Handle renaming the folder
    router.put(route('folders.update', props.folder.id), {
      name: renameFolderName.value,
    }, {
        onSuccess: () => {
          showRenameFolderForm.value = false;
          renameFolderName.value = '';
          toggleRenameFolderForm();
        },
    });
  };

  const showRenameFileForm = ref(false);
  const renameFileName = ref('');
  const renamingFileId = ref(null);
  const toggleRenameFileForm = (file) => {
    showRenameFileForm.value = !showRenameFileForm.value;
    renameFileName.value = file.name;
    renamingFileId.value = file.id;
  };

  const renameFile = (file) => {
    // Handle renaming the file
    router.put(route('files.update', file.id), {
      name: renameFileName.value,
    }, {
        onSuccess: () => {
          showRenameFileForm.value = false;
          renameFileName.value = '';
          renamingFileId.value = null;
          toggleRenameFileForm(file);
        },
    });
  };

  const createFolder = () => {
    // Handle creating a new folder
    router.post(route('folders.store'), {
      name: newFolderName.value,
      parent_id: props.folder.id,
    }, {
        onSuccess: () => {
          showNewFolderForm.value = false;
          newFolderName.value = '';
          toggleNewFolderForm();
        },
    });

    toggleNewFolderForm();
  };

  const openFolder = (folder) => {
    router.get(route('folders.show', folder.id));
  };

defineOptions({
  layout: Layout
});
  </script>
  