<template>
    <div>
      <input type="file" @change="uploadFile" />
    </div>
  </template>
  
  <script setup>
import { useForm, router } from "@inertiajs/vue3";

const props = defineProps({
    folder: Object,
});

const uploadFile = (event) => {
    let file = event.target.files[0];
    let formData = new FormData();
    formData.append('file', file);
    router.post(route('files.store', { folder_id: props.folder.id }), formData);
};
  </script>
  