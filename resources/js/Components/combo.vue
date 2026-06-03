<template>
  <Combobox :model-value="props.modelValue" multiple nullable @update:model-value="value => emit('update:modelValue', value)">
    <div class="relative rounded-2xl border border-slate-300/90 bg-white/95 p-2 shadow-sm ring-1 ring-white/50 transition focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-slate-800/60">
      <div class="flex flex-auto flex-wrap gap-1.5">
        <div
          v-for="option in selectedOptions"
          :key="option.value ?? option.id"
          class="flex items-center rounded-full border border-primary-200 bg-primary-100 px-2.5 py-1 text-xs font-medium text-primary-900 dark:border-primary-500/25 dark:bg-primary-500/15 dark:text-primary-100"
        >
          {{ option.label ?? option.name }}
        </div>
        <ComboboxInput
          class="min-w-32 flex-1 rounded-xl border-0 bg-transparent p-1.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
          :placeholder="props.placeholder"
          @change="query = $event.target.value"
        />
      </div>
      <ComboboxOptions class="absolute left-0 right-0 z-50 mt-3 max-h-72 overflow-auto rounded-2xl border border-slate-200 bg-white/98 p-2 text-sm shadow-2xl ring-1 ring-slate-900/5 backdrop-blur-sm focus:outline-none dark:border-slate-700 dark:bg-slate-900/98 dark:ring-slate-100/5">
        <div v-if="filteredOptions.length === 0" class="rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
          {{ $t('gestlab.general.messages.no_items') }}
        </div>
        <ComboboxOption v-for="option in filteredOptions" :key="option.value ?? option.id" :value="option" as="template" v-slot="{ active, selected }">
          <li :class="[active ? 'bg-primary-900 text-white dark:bg-primary-700' : 'text-slate-900 dark:text-slate-100', 'relative cursor-pointer select-none rounded-xl py-2.5 pl-10 pr-4']">
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
