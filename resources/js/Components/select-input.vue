<template>
  <Listbox as="div" :modelValue="props.modelValue" @update:modelValue="value => emit('update:modelValue', value)">
    <ListboxLabel class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ label }}</ListboxLabel>
    <div class="relative mt-1.5">
      <ListboxButton class="relative w-full cursor-pointer rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-3.5 pr-10 text-left text-sm text-gray-900 dark:text-gray-100 shadow-sm transition-colors duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
        <span class="block truncate">{{ selected?.label || '-' }}</span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5">
          <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <ListboxOptions class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white dark:bg-gray-800 py-1.5 shadow-lg ring-1 ring-gray-900/5 dark:ring-gray-700 focus:outline-none text-sm">
          <ListboxOption as="template" v-for="option in options" :key="option.value" :value="option" v-slot="{ active, selected }">
            <li :class="[active ? 'bg-primary-50 dark:bg-primary-500/10 text-primary-900 dark:text-primary-200' : 'text-gray-700 dark:text-gray-300', 'relative cursor-pointer select-none py-2 pl-3.5 pr-9 transition-colors duration-100']">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ option.label }}</span>
              <span v-if="selected" :class="[active ? 'text-primary-700' : 'text-primary-600', 'absolute inset-y-0 right-0 flex items-center pr-3.5']">
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
import { ref } from 'vue'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { ChevronUpDownIcon } from '@heroicons/vue/16/solid'
import { CheckIcon } from '@heroicons/vue/20/solid'

const emit = defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: Object,
  options: Array,
  selected: Object,
  label: { type: String, default: '' }
})

const selected = ref(props.selected)
</script>