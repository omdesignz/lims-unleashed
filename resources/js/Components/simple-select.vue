<template>
  <Listbox as="div" :model-value="props.modelValue" @update:model-value="value => emit('update:modelValue', value)">
    <ListboxLabel v-if="props.label" class="ds-field-label block">
      {{ $t(props.label) }}
    </ListboxLabel>
    <div class="relative mt-2">
      <ListboxButton class="ds-combobox-control grid cursor-pointer grid-cols-1 py-3 pl-4 pr-11 text-left text-sm font-semibold focus:outline-none">
        <span class="col-start-1 row-start-1 truncate pr-6">
          {{ props.modelValue ? selectedLabel : $t(props.placeholder) }}
        </span>
        <ChevronUpDownIcon class="col-start-1 row-start-1 size-5 self-center justify-self-end text-[var(--ds-text-soft)]" aria-hidden="true" />
      </ListboxButton>

      <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <ListboxOptions class="ds-floating-panel absolute z-50 mt-2 max-h-72 w-full overflow-auto p-2 text-sm focus:outline-none">
          <div v-if="props.options.length === 0" class="rounded-xl px-4 py-3 text-sm text-[var(--ds-text-soft)]">
            {{ $t('gestlab.general.messages.no_items') }}
          </div>
          <ListboxOption as="template" v-for="option in props.options" :key="option.value ?? option.id" :value="option" v-slot="{ active, selected }">
            <li class="ds-option ds-option-compact pr-9" :class="{ 'ds-option-active': active }">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                {{ option.label ?? option.name }}
              </span>

              <span v-if="selected" class="absolute inset-y-0 right-0 flex items-center pr-4" :class="active ? 'text-white' : 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]'">
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
