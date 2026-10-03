<script setup>
import CollectionAccessionForm from "@/Components/collections/CollectionAccessionForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  record: { type: Object, required: true },
  ownerOptions: { type: Array, default: () => [] },
});
</script>

<template>
  <CollectionAccessionForm kind="programmed" :record="record" :owner-options="ownerOptions" />
</template>
