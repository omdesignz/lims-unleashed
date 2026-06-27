<template>
  <div class="ds-field-group">
    <label v-if="label" :for="controlId" class="ds-field-label">
      {{ label }}
      <span v-if="required" class="ds-field-required" aria-hidden="true">*</span>
    </label>
    <textarea
      v-bind="$attrs"
      :id="controlId"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :aria-invalid="Boolean(error || hasError)"
      :aria-describedby="describedBy"
      class="ds-field min-h-28 resize-y"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hint && !error" :id="hintId" class="ds-field-hint">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="ds-field-error" role="alert">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, useAttrs } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const attrs = useAttrs()

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  rows: {
    type: [Number, String],
    default: 3,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  hasError: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue'])

const controlId = computed(() => attrs.id || attrs.name || undefined)
const hintId = computed(() => controlId.value ? `${controlId.value}-hint` : undefined)
const errorId = computed(() => controlId.value ? `${controlId.value}-error` : undefined)
const describedBy = computed(() => props.error ? errorId.value : (props.hint ? hintId.value : undefined))
</script>
