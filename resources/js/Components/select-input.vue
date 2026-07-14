<template>
  <Listbox
    as="div"
    :model-value="selectedOption"
    :disabled="disabled"
    @update:model-value="updateValue"
  >
    <ListboxLabel v-if="label" :for="controlId" class="ds-field-label block">
      {{ label }}
      <span v-if="required" class="ds-field-required" aria-hidden="true">*</span>
    </ListboxLabel>
    <div class="relative" :class="label ? 'mt-1.5' : ''">
      <ListboxButton
        :id="controlId"
        class="ds-combobox-control group flex min-h-12 w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm font-semibold focus:outline-none"
        :data-invalid="Boolean(error)"
        :data-disabled="disabled"
        :aria-invalid="Boolean(error)"
        :aria-describedby="describedBy"
      >
        <span class="block min-w-0 flex-1 truncate" :class="selectedOption ? 'text-[var(--ds-text)]' : 'text-[var(--ds-text-soft)]'">
          {{ selectedOption?.label || placeholder }}
        </span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5">
          <ChevronUpDownIcon class="h-5 w-5 text-[var(--ds-text-soft)] transition group-hover:text-[var(--ds-text-muted)]" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="scale-95 opacity-0"
        enter-to-class="scale-100 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="scale-100 opacity-100"
        leave-to-class="scale-95 opacity-0"
      >
        <ListboxOptions class="ds-floating-panel absolute z-50 mt-2 max-h-72 w-full origin-top overflow-auto p-1.5 text-sm focus:outline-none">
          <li v-if="!options.length" class="px-3 py-3 text-sm font-semibold text-[var(--ds-text-soft)]">
            Sem opções disponíveis
          </li>
          <ListboxOption as="template" v-for="option in options" :key="option.value" :value="option" v-slot="{ active, selected }">
            <li class="ds-option ds-option-compact" :class="{ 'ds-option-active': active }">
              <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ option.label }}</span>
              <span v-if="selected" class="ml-auto flex items-center" :class="active ? 'text-current' : 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-300-rgb))]'">
                <CheckIcon class="h-5 w-5" aria-hidden="true" />
              </span>
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>
    <p v-if="hint && !error" :id="hintId" class="ds-field-hint mt-1.5">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="ds-field-error mt-1.5" role="alert">{{ error }}</p>
  </Listbox>
</template>

<script setup>
import { computed, useAttrs, useId } from 'vue'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { ChevronUpDownIcon } from '@heroicons/vue/16/solid'
import { CheckIcon } from '@heroicons/vue/20/solid'

const emit = defineEmits(['update:modelValue'])
const attrs = useAttrs()
const generatedId = useId()

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
    type: [Object, String, Number],
    default: null,
  },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Seleccione uma opção' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const selectedOption = computed(() => {
  const value = props.selected ?? props.modelValue

  if (value && typeof value === 'object') {
    return value
  }

  return props.options.find((option) => String(option.value) === String(value)) ?? null
})
const controlId = computed(() => attrs.id || attrs.name || `select-field-${generatedId}`)
const hintId = computed(() => `${controlId.value}-hint`)
const errorId = computed(() => `${controlId.value}-error`)
const describedBy = computed(() => props.error ? errorId.value : (props.hint ? hintId.value : undefined))

function updateValue(value) {
  emit('update:modelValue', value)
}
</script>
