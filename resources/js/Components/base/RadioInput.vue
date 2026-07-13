<template>
  <input
    ref="inputElement"
    v-bind="controlAttrs"
    type="radio"
    :value="value"
    :checked="isChecked"
    :disabled="disabled"
    :class="inputClasses"
    @change="handleChange"
  />
</template>

<script setup>
import { computed, ref, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
const inputElement = ref(null)

const props = defineProps({
  modelValue: {
    type: [Boolean, String, Number, Object],
    default: undefined,
  },
  value: {
    type: [Boolean, String, Number, Object],
    default: '',
  },
  checked: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const controlAttrs = computed(() => Object.fromEntries(
  Object.entries(attrs).filter(([key]) => !['class', 'type'].includes(key)),
))
const inputClasses = computed(() => [
  'ds-radio',
  ...String(attrs.class ?? '').split(/\s+/).filter((className) => className && className !== 'ds-radio'),
])
const isChecked = computed(() => props.modelValue === undefined ? props.checked : Object.is(props.modelValue, props.value))

function handleChange(event) {
  emit('update:modelValue', props.value)
  emit('change', event)
}

defineExpose({
  click: () => inputElement.value?.click(),
  focus: () => inputElement.value?.focus(),
})
</script>
