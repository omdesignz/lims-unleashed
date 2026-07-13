<template>
  <input
    ref="inputElement"
    v-bind="controlAttrs"
    type="checkbox"
    :value="value"
    :checked="isChecked"
    :disabled="disabled"
    :class="inputClasses"
    @change="handleChange"
  />
</template>

<script setup>
import { computed, onMounted, ref, useAttrs, watch } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()
const inputElement = ref(null)

const props = defineProps({
  modelValue: {
    type: [Array, Boolean, String, Number, Object],
    default: undefined,
  },
  value: {
    type: [Boolean, String, Number, Object],
    default: true,
  },
  checked: {
    type: Boolean,
    default: false,
  },
  trueValue: {
    type: [Boolean, String, Number, Object],
    default: true,
  },
  falseValue: {
    type: [Boolean, String, Number, Object],
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  indeterminate: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const controlAttrs = computed(() => Object.fromEntries(
  Object.entries(attrs).filter(([key]) => !['class', 'type'].includes(key)),
))
const inputClasses = computed(() => [
  'ds-checkbox',
  ...String(attrs.class ?? '').split(/\s+/).filter((className) => className && className !== 'ds-checkbox'),
])
const isChecked = computed(() => {
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.some((item) => Object.is(item, props.value))
  }

  if (props.modelValue === undefined) {
    return props.checked
  }

  return Object.is(props.modelValue, props.trueValue)
})

function syncIndeterminate() {
  if (inputElement.value) {
    inputElement.value.indeterminate = props.indeterminate
  }
}

function handleChange(event) {
  if (Array.isArray(props.modelValue)) {
    const nextValue = [...props.modelValue]
    const existingIndex = nextValue.findIndex((item) => Object.is(item, props.value))

    if (event.target.checked && existingIndex === -1) {
      nextValue.push(props.value)
    } else if (!event.target.checked && existingIndex !== -1) {
      nextValue.splice(existingIndex, 1)
    }

    emit('update:modelValue', nextValue)
  } else {
    emit('update:modelValue', event.target.checked ? props.trueValue : props.falseValue)
  }

  emit('change', event)
}

onMounted(syncIndeterminate)
watch(() => props.indeterminate, syncIndeterminate)

defineExpose({
  click: () => inputElement.value?.click(),
  focus: () => inputElement.value?.focus(),
})
</script>
