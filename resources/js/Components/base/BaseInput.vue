<template>
  <DateTimePicker
    v-if="isDateTimeField"
    v-bind="controlAttrs"
    :id="controlId"
    :model-value="resolvedValue"
    :type="type"
    :label="label"
    :placeholder="placeholder"
    :error="error"
    :hint="hint"
    :required="required"
    :disabled="disabled"
    :min="min"
    :max="max"
    @update:model-value="updateValue"
  />

  <input
    v-else-if="isBare"
    ref="inputElement"
    v-bind="controlAttrs"
    :id="controlId"
    :value="resolvedValue"
    :type="type"
    :min="min"
    :max="max"
    :step="step"
    :placeholder="placeholder"
    :disabled="disabled"
    :required="required"
    :aria-invalid="Boolean(error)"
    :aria-describedby="describedBy"
    :class="baseInputClasses"
    @input="handleInput"
    @change="handleChange"
  />

  <div v-else class="ds-field-group">
    <label v-if="label" :for="controlId" class="ds-field-label">
      {{ label }}
      <span v-if="required" class="ds-field-required" aria-hidden="true">*</span>
    </label>
    <div class="relative">
      <div v-if="$slots.leading" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[var(--ds-text-soft)]">
        <slot name="leading" />
      </div>
      <input
        ref="inputElement"
        v-bind="controlAttrs"
        :id="controlId"
        :value="resolvedValue"
        :type="type"
        :min="min"
        :max="max"
        :step="step"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :aria-invalid="Boolean(error)"
        :aria-describedby="describedBy"
        :class="[baseInputClasses, $slots.leading ? 'pl-11' : '', $slots.trailing ? 'pr-11' : '']"
        @input="handleInput"
        @change="handleChange"
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
import DateTimePicker from '@/Components/base/DateTimePicker.vue'
import { computed, ref, useAttrs, useId, useSlots } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const attrs = useAttrs()
const slots = useSlots()
const generatedId = useId()
const inputElement = ref(null)

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: undefined,
  },
  value: {
    type: [String, Number],
    default: undefined,
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
  modelModifiers: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue'])

const controlId = computed(() => attrs.id || attrs.name || `field-${generatedId}`)
const hintId = computed(() => `${controlId.value}-hint`)
const errorId = computed(() => `${controlId.value}-error`)
const describedBy = computed(() => props.error ? errorId.value : (props.hint ? hintId.value : undefined))
const isDateTimeField = computed(() => ['date', 'datetime-local', 'time'].includes(props.type))
const isBare = computed(() => !props.label && !props.hint && !props.error && !slots.leading && !slots.trailing)
const resolvedValue = computed(() => props.modelValue === undefined ? (props.value ?? '') : props.modelValue)
const baseInputClasses = computed(() => [
  'ds-field',
  ...String(attrs.class ?? '').split(/\s+/).filter((className) => className && className !== 'ds-field'),
])
const controlAttrs = computed(() => Object.fromEntries(
  Object.entries(attrs).filter(([key]) => key !== 'class'),
))

function normalizeValue(value) {
  if (!props.modelModifiers.number || value === '') {
    return value
  }

  const numericValue = Number(value)

  return Number.isNaN(numericValue) ? value : numericValue
}

function updateValue(value) {
  emit('update:modelValue', normalizeValue(value))
}

function handleInput(event) {
  if (!props.modelModifiers.lazy) {
    updateValue(event.target.value)
  }
}

function handleChange(event) {
  if (props.modelModifiers.lazy) {
    updateValue(event.target.value)
  }
}

defineExpose({
  click: () => inputElement.value?.click(),
  focus: () => inputElement.value?.focus(),
  select: () => inputElement.value?.select(),
})
</script>
