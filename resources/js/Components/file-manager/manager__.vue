<template>
    <div class="flex h-screen">
      <!-- Sidebar Component -->
      <Sidebar />
  
      <!-- Main Content -->
      <div class="flex-1 bg-gray-100 p-6">

        <!-- Breadcrumbs -->
      <div class="flex items-center space-x-2 text-sm text-gray-600 mb-4">
        <span v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
          <a href="#" class="hover:text-blue-500">{{ crumb }}</a>
          <span v-if="index < breadcrumbs.length - 1" class="mx-1">/</span>
        </span>
      </div>

        <!-- Toolbar -->
        <div class="flex justify-between items-center mb-6">
            <input
            v-model="searchQuery"
            @input="filterFiles"
            type="text"
            placeholder="Search files..."
            class="bg-white border border-gray-300 rounded-md px-4 py-2 w-1/3"
            />

          <!-- <h2 class="text-xl font-semibold text-gray-600">My Files</h2> -->
          <div class="flex items-center space-x-4">
            <button
              class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md"
              @click="uploadFile"
            >
              Upload File
            </button>
            <button
              class="bg-gray-200 hover:bg-gray-300 text-gray-600 font-semibold py-2 px-4 rounded-md"
              @click="toggleView"
            >
              {{ isGridView ? 'Table View' : 'Grid View' }}
            </button>
          </div>
        </div>
  
        <!-- File/Folder View -->
        <div v-if="isGridView">
          <GridView :files="filteredFiles" />
        </div>
        <div v-else>
          <TableView :files="filteredFiles" />
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed } from "vue";
  import Sidebar from "@/Components/file-manager/sidebar.vue";
  import GridView from "@/Components/file-manager/grid-view.vue";
  import TableView from "@/Components/file-manager/table-view.vue";

  const isGridView = ref(true);

  const searchQuery = ref("");
  const breadcrumbs = ref(["My Drive", "Documents"]);
  const files = ref([
        { name: "Documents", type: "folder", icon: "folder", updated: "2024-09-30" },
        { name: "Photos", type: "folder", icon: "folder", updated: "2024-09-28" },
        { name: "Project.pdf", type: "file", icon: "file", updated: "2024-09-27" },
  ]);

  const filteredFiles = computed(() => {
      if (searchQuery.value === "") {
        return files.value;
      }
      return files.value.filter((file) =>
        file.name.toLowerCase().includes(searchQuery.value.toLowerCase())
      );
    });

  const toggleView = () => {
    isGridView.value = !isGridView.value;
  };

  const uploadFile = () => {
    console.log("Uploading file");
  };

  const filterFiles = () => {
      console.log("Filtering files for:", searchQuery.value);
    };
  
  </script>
  