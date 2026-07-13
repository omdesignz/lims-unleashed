<script setup>
import CollectionAccessionForm from "@/Components/collections/CollectionAccessionForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
  products: { type: Array, default: () => [] },
  analysis_categories: { type: Array, default: () => [] },
  entrypoint: { type: Object, default: () => ({}) },
});
</script>

<template>
  <CollectionAccessionForm kind="direct" :entrypoint="props.entrypoint" />
</template>
