<template>
  <Listbox as="div" :model-value="props.modelValue" @update:model-value="value => emit('update:modelValue', value)">
    <ListboxLabel v-if="props.label" class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
      {{ $t(props.label) }}
    </ListboxLabel>
    <div class="relative mt-2">
      <ListboxButton class="grid w-full cursor-pointer grid-cols-1 rounded-2xl border border-slate-300/90 bg-white/95 py-3 pl-4 pr-11 text-left text-sm font-medium text-slate-900 shadow-sm ring-1 ring-white/50 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800/60">
        <span class="col-start-1 row-start-1 truncate pr-6">
          {{ props.modelValue ? selectedLabel : $t(props.placeholder) }}
        </span>
        <ChevronUpDownIcon class="col-start-1 row-start-1 size-5 self-center justify-self-end text-slate-400 dark:text-slate-500" aria-hidden="true" />
      </ListboxButton>

      <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <ListboxOptions class="absolute z-50 mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-slate-200 bg-white/98 p-2 text-sm shadow-2xl ring-1 ring-slate-900/5 backdrop-blur-sm focus:outline-none dark:border-slate-700 dark:bg-slate-900/98 dark:ring-slate-100/5">
          <div v-if="props.options.length === 0" class="rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
            {{ $t('gestlab.general.messages.no_items') }}
          </div>
          <ListboxOption as="template" v-for="option in props.options" :key="option.value ?? option.id" :value="option" v-slot="{ active, selected }">
            <li :class="[active ? 'bg-primary-900 text-white dark:bg-primary-700' : 'text-slate-900 dark:text-slate-100', 'relative cursor-pointer select-none rounded-xl py-2.5 pl-3.5 pr-9 transition-colors']">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                {{ option.label ?? option.name }}
              </span>

              <span v-if="selected" :class="[active ? 'text-white' : 'text-primary-700 dark:text-primary-300', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                <CheckIcon class="size-5" aria-hidden="true" />
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
  label: {
    type: String,
    default: 'gestlab.general.labels.assigned_to',
  },
  placeholder: {
    type: String,
    default: 'gestlab.general.labels.select',
  },
})

const selectedLabel = computed(() => props.modelValue?.label ?? props.modelValue?.name ?? props.modelValue ?? props.placeholder)
</script>
