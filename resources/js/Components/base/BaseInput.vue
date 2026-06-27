<template>
  <div class="ds-field-group">
    <label v-if="label" :for="controlId" class="ds-field-label">
      {{ label }}
      <span v-if="required" class="ds-field-required" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <div v-if="$slots.leading" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[var(--ds-text-soft)]">
        <slot name="leading" />
      </div>
      <input
        v-bind="$attrs"
        :id="controlId"
        :value="modelValue"
        :type="type"
        :min="min"
        :max="max"
        :step="step"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :aria-invalid="Boolean(error)"
        :aria-describedby="describedBy"
        :class="['ds-field', $slots.leading ? 'pl-11' : '', $slots.trailing ? 'pr-11' : '']"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <div v-if="$slots.trailing" class="absolute inset-y-0 right-0 flex items-center pr-4 text-[var(--ds-text-soft)]">
        <slot name="trailing" />
      </div>
    </div>
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
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  min: {
    type: [String, Number],
    default: null,
  },
  max: {
    type: [String, Number],
    default: null,
  },
  step: {
    type: [String, Number],
    default: null,
  },
})

defineEmits(['update:modelValue'])

const controlId = computed(() => attrs.id || attrs.name || undefined)
const hintId = computed(() => controlId.value ? `${controlId.value}-hint` : undefined)
const errorId = computed(() => controlId.value ? `${controlId.value}-error` : undefined)
const describedBy = computed(() => props.error ? errorId.value : (props.hint ? hintId.value : undefined))
</script>
