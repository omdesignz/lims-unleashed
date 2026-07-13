<template>
  <Combobox
    as="div"
    :model-value="props.modelValue"
    :multiple="props.multiple"
    @update:modelValue="value => emit('update:modelValue', value)"
  >
    <ComboboxLabel class="ds-field-label">{{ props.label }}</ComboboxLabel>
    <div class="relative mt-1">
      <ComboboxInput
        class="ds-field pr-11"
        :placeholder="props.placeholder"
        :display-value="() => label"
        @change="query = $event.target.value"
      />
      <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-[var(--ds-text-soft)] transition hover:text-[rgb(var(--primary-700-rgb)/1)] focus:outline-none dark:hover:text-cyan-100">
        <ChevronUpDownIcon class="h-5 w-5" aria-hidden="true" />
      </ComboboxButton>

      <ComboboxOptions class="ds-floating-panel absolute z-50 mt-2 max-h-72 w-full overflow-auto p-2 text-sm focus:outline-none">
        <div v-if="filteredOptions.length === 0" class="rounded-lg px-4 py-3 text-sm font-medium text-[var(--ds-text-muted)]">
          {{ $t('gestlab.general.messages.no_items') }}
        </div>
        <ComboboxOption
          v-for="option in filteredOptions"
          :key="option.label"
          :value="option.value"
          as="template"
          v-slot="{ active, selected }"
        >
          <li :class="['ds-option', active ? 'ds-option-active' : '']">
            <span v-if="selected" class="ds-option-check">
              <CheckIcon class="h-5 w-5" aria-hidden="true" />
            </span>
            <span :class="['block truncate', selected && 'font-bold']">
              {{ option.label }}
            </span>
          </li>
        </ComboboxOption>
      </ComboboxOptions>
    </div>
    <div v-if="props.error" class="ds-field-error mt-1">
      {{ props.error }}
    </div>
  </Combobox>
</template>

<script setup>
import { computed, ref } from 'vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'
import {
  Combobox,
  ComboboxButton,
  ComboboxInput,
  ComboboxLabel,
  ComboboxOption,
  ComboboxOptions,
} from '@headlessui/vue'

const props = defineProps({
  options: {
    type: Array,
    default: () => [],
  },
  modelValue: [String, Number, Array],
  placeholder: {
    type: String,
    default: 'Seleccione uma opção',
  },
  multiple: Boolean,
  error: String,
  label: String,
})

const emit = defineEmits([
  'update:modelValue',
])

const label = computed(() => {
  return props.options.filter((option) => {
    if (Array.isArray(props.modelValue)) {
      return props.modelValue.includes(option.value)
    }

    return props.modelValue === option.value
  })
    .map((option) => option.label)
    .join(', ')
})

const query = ref('')

const filteredOptions = computed(() =>
  query.value === ''
    ? props.options
    : props.options.filter((option) => {
      return option?.label?.toLowerCase().includes(query.value.toLowerCase())
    }),
)
</script>
