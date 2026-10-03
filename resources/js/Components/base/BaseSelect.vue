<template>
  <Listbox
    as="div"
    :model-value="selectedOptions"
    :disabled="disabled"
    :multiple="multiple"
    class="ds-field-group min-w-0"
    @update:model-value="updateValue"
  >
    <ListboxLabel v-if="label" :for="controlId" class="ds-field-label">
      {{ label }}
      <span v-if="required" class="ds-field-required" aria-hidden="true">*</span>
    </ListboxLabel>

    <div class="relative" :class="label ? 'mt-1.5' : ''">
      <ListboxButton
        v-bind="controlAttrs"
        :id="controlId"
        class="ds-combobox-control group flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm focus:outline-none"
        :class="attrs.class"
        :data-invalid="Boolean(error)"
        :data-disabled="disabled"
        :aria-invalid="Boolean(error)"
        :aria-describedby="describedBy"
      >
        <span class="block min-w-0 flex-1 truncate" :class="hasSelection ? 'text-[var(--ds-text)]' : 'text-[var(--ds-text-soft)]'">
          {{ selectionLabel }}
        </span>
        <ChevronUpDownIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)] transition group-hover:text-[var(--ds-text-muted)]" aria-hidden="true" />
      </ListboxButton>

      <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="scale-[0.97] opacity-0"
        enter-to-class="scale-100 opacity-100"
        leave-active-class="transition duration-100 ease-out"
        leave-from-class="scale-100 opacity-100"
        leave-to-class="scale-[0.97] opacity-0"
      >
        <ListboxOptions class="ds-floating-panel absolute z-50 mt-1.5 max-h-72 w-full min-w-52 origin-top overflow-auto p-1.5 text-sm focus:outline-none">
          <li v-if="!normalizedOptions.length" class="px-3 py-3 text-sm text-[var(--ds-text-soft)]">
            {{ emptyText }}
          </li>
          <ListboxOption
            v-for="option in normalizedOptions"
            :key="option.key"
            v-slot="{ active, selected }"
            as="template"
            :value="option"
            :disabled="option.disabled"
          >
            <li
              class="ds-option ds-option-compact"
              :class="[
                active ? 'ds-option-active' : '',
                option.disabled ? 'cursor-not-allowed opacity-50' : '',
              ]"
            >
              <span class="block min-w-0 flex-1 truncate" :class="selected ? 'font-medium' : ''">
                {{ option.label }}
              </span>
              <CheckIcon v-if="selected" class="h-4 w-4 shrink-0 text-[rgb(var(--primary-700-rgb))]" aria-hidden="true" />
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>

    <input
      v-if="attrs.name && !multiple"
      :name="attrs.name"
      :value="modelValue ?? ''"
      :required="required"
      :disabled="disabled"
      type="hidden"
    />
    <p v-if="hint && !error" :id="hintId" class="ds-field-hint">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="ds-field-error" role="alert">{{ error }}</p>
  </Listbox>
</template>

<script setup>
import { Check as CheckIcon, ChevronsUpDown as ChevronUpDownIcon } from '@lucide/vue'
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { Fragment, computed, useAttrs, useId, useSlots } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const attrs = useAttrs()
const slots = useSlots()
const generatedId = useId()

const props = defineProps({
  modelValue: {
    type: [Array, Object, String, Number, Boolean],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Seleccione uma opção',
  },
  emptyText: {
    type: String,
    default: 'Sem opções disponíveis',
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  modelModifiers: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const controlId = computed(() => attrs.id || attrs.name || `field-${generatedId}`)
const hintId = computed(() => `${controlId.value}-hint`)
const errorId = computed(() => `${controlId.value}-error`)
const describedBy = computed(() => props.error ? errorId.value : (props.hint ? hintId.value : undefined))
const controlAttrs = computed(() => Object.fromEntries(
  Object.entries(attrs).filter(([key]) => !['class', 'id', 'name'].includes(key)),
))

const slotOptions = computed(() => extractOptions(slots.default?.() ?? []))
const normalizedOptions = computed(() => {
  const source = props.options.length ? props.options : slotOptions.value

  return source.map((option, index) => {
    if (option && typeof option === 'object' && Object.prototype.hasOwnProperty.call(option, 'value')) {
      return {
        key: option.key ?? `${String(option.value)}-${index}`,
        value: option.value,
        label: String(option.label ?? option.value ?? ''),
        disabled: Boolean(option.disabled),
      }
    }

    return {
      key: `${String(option)}-${index}`,
      value: option,
      label: String(option ?? ''),
      disabled: false,
    }
  })
})

const selectedOptions = computed(() => {
  if (props.multiple) {
    const values = Array.isArray(props.modelValue) ? props.modelValue : []

    return normalizedOptions.value.filter((option) => values.some((value) => valuesMatch(option.value, value)))
  }

  return normalizedOptions.value.find((option) => valuesMatch(option.value, props.modelValue)) ?? null
})

const hasSelection = computed(() => props.multiple ? selectedOptions.value.length > 0 : selectedOptions.value !== null)
const selectionLabel = computed(() => {
  if (props.multiple && selectedOptions.value.length) {
    return selectedOptions.value.map((option) => option.label).join(', ')
  }

  return selectedOptions.value?.label || props.placeholder
})

function extractOptions(nodes) {
  return nodes.flatMap((node, index) => {
    if (!node) {
      return []
    }

    if (node.type === Fragment || Array.isArray(node.children)) {
      const nestedOptions = extractOptions(Array.isArray(node.children) ? node.children : [])

      if (node.type !== 'option') {
        return nestedOptions
      }
    }

    if (node.type !== 'option') {
      return []
    }

    const label = vnodeText(node.children)

    return [{
      key: node.key ?? `${String(node.props?.value ?? label)}-${index}`,
      value: node.props?.value ?? label,
      label,
      disabled: Boolean(node.props?.disabled),
    }]
  })
}

function vnodeText(children) {
  if (typeof children === 'string' || typeof children === 'number') {
    return String(children).trim()
  }

  if (!Array.isArray(children)) {
    return ''
  }

  return children.map((child) => {
    if (typeof child === 'string' || typeof child === 'number') {
      return String(child)
    }

    return vnodeText(child?.children)
  }).join('').trim()
}

function valuesMatch(optionValue, modelValue) {
  if (Object.is(optionValue, modelValue)) {
    return true
  }

  if (optionValue === null || optionValue === undefined || modelValue === null || modelValue === undefined) {
    return false
  }

  return typeof optionValue !== 'object' && typeof modelValue !== 'object' && String(optionValue) === String(modelValue)
}

function normalizeValue(value) {
  if (!props.modelModifiers.number || value === '' || value === null) {
    return value
  }

  const numberValue = Number(value)

  return Number.isNaN(numberValue) ? value : numberValue
}

function updateValue(selection) {
  const value = props.multiple
    ? selection.map((option) => normalizeValue(option.value))
    : normalizeValue(selection?.value ?? '')

  emit('update:modelValue', value)
  emit('change', { target: { value } })
}
</script>
