<template>
    <div>
      <button @click="shareFolder">Share</button>
      <div v-if="showModal">
        <form @submit.prevent="share">
          <input v-model="email" placeholder="Enter user's email" />
          <button type="submit">Share</button>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
import { useForm, router } from "@inertiajs/vue3";


  const showModal = ref(false);
  const email = ref('');

  const shareFolder = () => {
    showModal.value = true;
  };

  const share = () => {
    showModal.value = false;
    router.post(route('folders.share', folder.id), { email: email.value });
  };
  </script>
  