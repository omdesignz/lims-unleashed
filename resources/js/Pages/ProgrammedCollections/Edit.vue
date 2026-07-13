<script setup>
import CollectionAccessionForm from "@/Components/collections/CollectionAccessionForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  record: { type: Object, required: true },
});
</script>

<template>
  <CollectionAccessionForm kind="programmed" :record="record" />
</template>
