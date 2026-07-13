<template>
  <div class="ds-field-group" :class="wide ? 'md:col-span-2' : ''">
    <label class="ds-field-label" :for="fieldId">{{ label }}</label>

    <textarea
      v-if="editing && multiline"
      :id="fieldId"
      :value="modelValue"
      :rows="rows"
      class="ds-field"
      :class="monospace ? 'font-mono text-xs' : ''"
      :placeholder="placeholder"
      :aria-invalid="Boolean(error)"
      @input="emit('update:modelValue', $event.target.value)"
    ></textarea>

    <input
      v-else-if="editing"
      :id="fieldId"
      :value="modelValue"
      :type="type"
      class="ds-field"
      :class="monospace ? 'font-mono text-xs' : ''"
      :placeholder="placeholder"
      :aria-invalid="Boolean(error)"
      @input="emit('update:modelValue', $event.target.value)"
    />

    <div
      v-else
      class="min-h-11 whitespace-pre-line border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-3 py-2.5 text-sm font-semibold leading-5 text-[color:var(--ds-text)]"
      :class="monospace ? 'break-all font-mono text-xs' : ''"
    >
      {{ displayValue || modelValue || emptyLabel }}
    </div>

    <p v-if="error" class="ds-field-error">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  displayValue: {
    type: [String, Number],
    default: '',
  },
  editing: Boolean,
  multiline: Boolean,
  monospace: Boolean,
  wide: Boolean,
  type: {
    type: String,
    default: 'text',
  },
  rows: {
    type: Number,
    default: 3,
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  emptyLabel: {
    type: String,
    default: 'Não configurado',
  },
})

const emit = defineEmits(['update:modelValue'])

const fieldId = computed(() => `system-setting-${props.label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')}`)
</script>
