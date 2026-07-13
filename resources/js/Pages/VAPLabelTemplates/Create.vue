<script setup>
import LabelTemplateForm from '@/Pages/VAPLabelTemplates/LabelTemplateForm.vue'

defineProps({
  categories: {
    type: Object,
    default: () => ({}),
  },
  labs: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <LabelTemplateForm :categories="categories" :labs="labs" />
</template>
