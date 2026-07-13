<script setup>
import KnowledgeRegistry from "@/Pages/FAQs/KnowledgeRegistry.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});
</script>

<template>
  <KnowledgeRegistry kind="question" :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" />
</template>
