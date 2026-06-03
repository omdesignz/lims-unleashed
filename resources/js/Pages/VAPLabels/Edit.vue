<template>
  <Create v-bind="props" />
</template>

<script setup>
import Create from './Create.vue'

const props = defineProps({
  templates: {
    type: Array,
    default: () => [],
  },
  selectedTemplateId: Number,
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  defaultSettings: {
    type: Object,
    default: () => ({}),
  },
  label: {
    type: Object,
    required: true,
  },
  sourcePreview: Object,
  sourceOptions: {
    type: Object,
    default: () => ({}),
  },
  supportedPlaceholders: {
    type: Array,
    default: () => ['{name}', '{code}', '{lot}'],
  },
})
</script>
