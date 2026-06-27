<template>
  <Combobox :model-value="props.modelValue" multiple nullable @update:model-value="value => emit('update:modelValue', value)">
    <div class="ds-combobox-control relative p-2">
      <div class="flex flex-auto flex-wrap gap-1.5">
        <div
          v-for="option in selectedOptions"
          :key="option.value ?? option.id"
          class="ds-chip"
        >
          {{ option.label ?? option.name }}
        </div>
        <ComboboxInput
          class="min-w-32 flex-1 rounded-xl border-0 bg-transparent p-1.5 text-sm text-[var(--ds-text)] placeholder:text-[var(--ds-text-soft)] focus:ring-0"
          :placeholder="props.placeholder"
          @change="query = $event.target.value"
        />
      </div>
      <ComboboxOptions class="ds-floating-panel absolute left-0 right-0 z-50 mt-3 max-h-72 overflow-auto p-2 text-sm focus:outline-none">
        <div v-if="filteredOptions.length === 0" class="rounded-xl px-4 py-3 text-sm text-[var(--ds-text-soft)]">
          {{ $t('gestlab.general.messages.no_items') }}
        </div>
        <ComboboxOption v-for="option in filteredOptions" :key="option.value ?? option.id" :value="option" as="template" v-slot="{ active, selected }">
          <li class="ds-option" :class="{ 'ds-option-active': active }">
            <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
              {{ option.label ?? option.name }}
            </span>
          </li>
        </ComboboxOption>
      </ComboboxOptions>
    </div>
  </Combobox>
</template>

<script setup>
import { computed, ref } from 'vue'
import {
  Combobox,
  ComboboxInput,
  ComboboxOptions,
  ComboboxOption,
} from '@headlessui/vue'

const emit = defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: '',
  },
})

const query = ref('')
const selectedOptions = computed(() => Array.isArray(props.modelValue) ? props.modelValue.filter(Boolean) : [])
const filteredOptions = computed(() => {
  if (!query.value) {
    return props.options
  }

  return props.options.filter(option => (option.label ?? option.name ?? '').toLowerCase().includes(query.value.toLowerCase()))
})
</script>
