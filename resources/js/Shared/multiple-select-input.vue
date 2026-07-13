<template>
  <Combobox as="div" v-model="internalValue" multiple>
    <ComboboxLabel class="ds-field-label">
      {{ $t(label) }}
    </ComboboxLabel>

    <div class="relative mt-1">
      <ComboboxInput
        class="ds-field pr-11"
        :display-value="displaySelection"
        :placeholder="$t(placeholder)"
        @change="query = $event.target.value"
      />
      <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-[var(--ds-text-soft)] transition hover:text-[rgb(var(--primary-700-rgb)/1)] focus:outline-none dark:hover:text-cyan-100">
        <ChevronUpDownIcon class="h-5 w-5" aria-hidden="true" />
      </ComboboxButton>

      <ComboboxOptions
        v-if="filteredOptions.length > 0"
        class="ds-floating-panel absolute z-50 mt-2 max-h-72 w-full overflow-auto p-2 text-sm focus:outline-none"
      >
        <ComboboxOption
          v-for="option in filteredOptions"
          :key="option.id"
          :value="option"
          as="template"
          v-slot="{ active, selected }"
        >
          <li :class="['ds-option', active ? 'ds-option-active' : '']">
            <span v-if="selected" class="ds-option-check">
              <CheckIcon class="h-5 w-5" aria-hidden="true" />
            </span>
            <span :class="['block truncate', selected ? 'font-bold' : 'font-semibold']">
              {{ option.name }}
            </span>
          </li>
        </ComboboxOption>
      </ComboboxOptions>
    </div>
  </Combobox>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
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
  modelValue: {
    type: Array,
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: 'gestlab.general.labels.responsibles',
  },
  placeholder: {
    type: String,
    default: 'gestlab.general.placeholders.search_responsibles',
  },
})

const emit = defineEmits(['update:modelValue'])

const query = ref('')
const internalValue = ref(props.modelValue)

watch(
  () => props.modelValue,
  (value) => {
    internalValue.value = value
  },
)

watch(internalValue, (value) => {
  emit('update:modelValue', value)
})

const filteredOptions = computed(() => {
  if (query.value === '') {
    return props.options
  }

  return props.options.filter((option) => {
    return option.name.toLowerCase().includes(query.value.toLowerCase())
  })
})

const displaySelection = (selectedItems) => {
  if (!Array.isArray(selectedItems) || selectedItems.length === 0) {
    return ''
  }

  return selectedItems.map((item) => item.name).join(', ')
}
</script>
