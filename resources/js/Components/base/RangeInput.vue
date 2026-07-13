<template>
  <input
    v-bind="controlAttrs"
    type="range"
    :value="resolvedValue"
    :min="min"
    :max="max"
    :step="step"
    :disabled="disabled"
    :class="inputClasses"
    @input="handleInput"
    @change="emit('change', $event)"
  />
</template>

<script setup>
import { computed, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: undefined,
  },
  value: {
    type: [String, Number],
    default: 0,
  },
  min: {
    type: [String, Number],
    default: 0,
  },
  max: {
    type: [String, Number],
    default: 100,
  },
  step: {
    type: [String, Number],
    default: 1,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'input', 'change'])

const resolvedValue = computed(() => props.modelValue === undefined ? props.value : props.modelValue)
const controlAttrs = computed(() => Object.fromEntries(
  Object.entries(attrs).filter(([key]) => !['class', 'type'].includes(key)),
))
const inputClasses = computed(() => [
  'ds-range',
  ...String(attrs.class ?? '').split(/\s+/).filter((className) => className && className !== 'ds-range'),
])

function handleInput(event) {
  const value = event.target.value === '' ? '' : Number(event.target.value)

  emit('update:modelValue', value)
  emit('input', event)
}
</script>
