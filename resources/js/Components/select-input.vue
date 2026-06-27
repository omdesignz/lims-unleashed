<template>
  <Listbox as="div" :modelValue="props.modelValue" @update:modelValue="value => emit('update:modelValue', value)">
    <ListboxLabel class="ds-field-label block">{{ label }}</ListboxLabel>
    <div class="relative mt-1.5">
      <ListboxButton class="ds-field relative cursor-pointer py-3 pl-4 pr-11 text-left">
        <span class="block truncate">{{ selectedOption?.label || '-' }}</span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5">
          <ChevronUpDownIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <ListboxOptions class="ds-card absolute z-50 mt-2 max-h-72 w-full overflow-auto p-2 text-sm backdrop-blur-sm focus:outline-none">
          <ListboxOption as="template" v-for="option in options" :key="option.value" :value="option" v-slot="{ active, selected }">
            <li :class="[active ? 'bg-primary-900 text-white dark:bg-primary-700' : 'text-slate-900 dark:text-slate-100', 'relative cursor-pointer select-none rounded-xl py-2.5 pl-3.5 pr-9 transition-colors duration-100']">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ option.label }}</span>
              <span v-if="selected" :class="[active ? 'text-white' : 'text-primary-700 dark:text-primary-300', 'absolute inset-y-0 right-0 flex items-center pr-3.5']">
                <CheckIcon class="h-5 w-5" aria-hidden="true" />
              </span>
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>
  </Listbox>
</template>

<script setup>
import { computed } from 'vue'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { ChevronUpDownIcon } from '@heroicons/vue/16/solid'
import { CheckIcon } from '@heroicons/vue/20/solid'

const emit = defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: {
    type: [Object, String, Number],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  selected: {
    type: Object,
    default: null,
  },
  label: { type: String, default: '' }
})

const selectedOption = computed(() => props.selected ?? props.modelValue ?? null)
</script>
