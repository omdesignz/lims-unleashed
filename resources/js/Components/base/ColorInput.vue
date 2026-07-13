<template>
  <ColorPicker
    v-bind="$attrs"
    :pure-color="resolvedValue"
    :disabled="disabled"
    format="hex"
    shape="square"
    picker-type="chrome"
    :disable-alpha="true"
    :disable-history="true"
    @update:pure-color="updateValue"
  />
</template>

<script setup>
import { ColorPicker } from 'vue3-colorpicker'
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: {
    type: String,
    default: undefined,
  },
  value: {
    type: String,
    default: '#000000',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'input', 'change'])

const resolvedValue = computed(() => props.modelValue ?? props.value)

function updateValue(value) {
  const event = { target: { value } }

  emit('update:modelValue', value)
  emit('input', event)
  emit('change', event)
}
</script>
