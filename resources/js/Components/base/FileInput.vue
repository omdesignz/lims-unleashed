<template>
  <input
    ref="inputElement"
    v-bind="controlAttrs"
    type="file"
    :disabled="disabled"
    :class="inputClasses"
    @input="emit('input', $event)"
    @change="emit('change', $event)"
  />
</template>

<script setup>
import { computed, ref, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
const inputElement = ref(null)

defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['input', 'change'])

const controlAttrs = computed(() => Object.fromEntries(
  Object.entries(attrs).filter(([key]) => !['class', 'type'].includes(key)),
))
const inputClasses = computed(() => {
  const classes = String(attrs.class ?? '')

  return classes.includes('hidden') || classes.includes('sr-only')
    ? classes
    : ['ds-field', ...classes.split(/\s+/).filter((className) => className && className !== 'ds-field')]
})

defineExpose({
  click: () => inputElement.value?.click(),
  focus: () => inputElement.value?.focus(),
  get files() {
    return inputElement.value?.files ?? null
  },
})
</script>
