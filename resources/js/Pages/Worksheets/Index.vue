<template>
  <fancyTextarea />
</template>

<script setup>
import { defineAsyncComponent } from "vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({
  layout: Layout
});

const fancyTextarea = defineAsyncComponent(() => import("@/Components/fancy-textarea.vue"));
</script>

<style>
.prose {
  max-width: 100%;
}
</style>
  
