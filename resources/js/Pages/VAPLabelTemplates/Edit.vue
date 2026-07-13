<script setup>
import LabelTemplateForm from '@/Pages/VAPLabelTemplates/LabelTemplateForm.vue'

defineProps({
  template: {
    type: Object,
    required: true,
  },
  categories: {
    type: Object,
    default: () => ({}),
  },
})
</script>

<template>
  <LabelTemplateForm :template="template" :categories="categories" />
</template>
