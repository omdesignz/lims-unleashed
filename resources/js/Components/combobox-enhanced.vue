<script setup>
import { ref, computed, watch } from 'vue'
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxLabel,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon, XMarkIcon } from '@heroicons/vue/20/solid'

const emit = defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: Object,
  titleLabel: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  options: {
    type: Array,
    default: () => [],
  },
  hasError: {
    type: Boolean,
    default: false
  },
  disableInput: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadOptions: Function,
  createOption: Function,
})

// Use a ref for internal options, but watch props.options
const options = ref([...props.options])

// FIX: Watch for props.options changes
watch(() => props.options, (newOptions) => {
  options.value = [...newOptions]
}, { deep: true })

const isLoading = ref(false)

const queryOption = computed(() => {
  return query.value === ''
    ? null
    : {
        missing: true,
        label: query.value,
      }
})

const query = ref('')
function applyLoadedOptions(results = []) {
  options.value = Array.isArray(results) ? results : []

  if (
    props.modelValue &&
    !options.value.some(o => {
      return o.value === props.modelValue?.value
    })
  ) {
    options.value.unshift(props.modelValue)
  }

  isLoading.value = false
}

function loadRemoteOptions(q) {
  isLoading.value = true

  try {
    const maybePromise = props.loadOptions(q, applyLoadedOptions)

    if (maybePromise?.then) {
      maybePromise
        .then(results => {
          if (Array.isArray(results)) {
            applyLoadedOptions(results)
          }
        })
        .catch(() => applyLoadedOptions([]))
    } else if (Array.isArray(maybePromise)) {
      applyLoadedOptions(maybePromise)
    }
  } catch {
    applyLoadedOptions([])
  }
}

watch(
  query,
  q => {
    if (props.loadOptions) {
      loadRemoteOptions(q)
    }
  },
  { immediate: true }
)

let filteredOptions = computed(() => {
  if (query.value === '') {
    return options.value
  }
  
  return options.value.filter(option =>
    String(option?.label ?? '')
      .toLowerCase()
      .replace(/\s+/g, '')
      .includes(query.value.toLowerCase().replace(/\s+/g, ''))
  )
})

function handleUpdateModelValue(selected) {
  emit('update:modelValue', selected)

  if (props.createOption && selected?.missing) {
    isLoading.value = true
    props.createOption(selected, option => {
      emit('update:modelValue', option)
      isLoading.value = false
    })
  }
}

function clear() {
  emit('update:modelValue', null)
  query.value = ''
}
</script>

<template>
  <div>
    <Combobox
      by="value"
      :model-value="props.modelValue"
      @update:model-value="handleUpdateModelValue"
      as="div"
    >
      <ComboboxLabel v-if="props.titleLabel" class="ds-field-label mb-2 block">{{ props.titleLabel }}</ComboboxLabel>
      <div class="relative mt-1">
        <div
          class="ds-combobox-control cursor-default text-left"
          :data-invalid="props.hasError"
          :data-disabled="props.disableInput"
        >
          <ComboboxInput
            class="w-full border-0 bg-transparent py-3 pl-4 pr-14 text-sm font-semibold text-[var(--ds-text)] placeholder:text-[var(--ds-text-soft)] focus:ring-0"
            :displayValue="option => option?.label"
            @change="query = $event.target.value"
            :placeholder="props.placeholder"
            :disabled="props.disableInput"
          />
          <ComboboxButton
            class="absolute inset-y-0 right-0 flex items-center gap-1 rounded-r-2xl px-3 focus:outline-none"
          >
            <XMarkIcon
              v-if="props.modelValue"
              class="h-4.5 w-4.5 text-[var(--ds-text-soft)] transition-colors hover:text-[rgb(var(--primary-700-rgb))]"
              aria-hidden="true"
              @click.stop="clear"
            />
            <ChevronUpDownIcon
              class="h-5 w-5 text-[var(--ds-text-soft)]"
              aria-hidden="true"
            />
          </ComboboxButton>
          <!-- Loading indicator -->
          <div v-if="props.loading" class="absolute inset-y-0 right-8 flex items-center pr-2 pointer-events-none">
            <svg class="h-4 w-4 animate-spin text-primary-700 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </div>

        <TransitionRoot
          leave="transition ease-in duration-100"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
          @after-leave="query = ''"
        >
          <ComboboxOptions
            class="ds-floating-panel absolute z-[9999] mt-2 max-h-72 w-full overflow-auto p-2 text-sm focus:outline-none"
          >
            <div
              v-if="
                filteredOptions.length === 0 &&
                !isLoading &&
                !props.loading &&
                !queryOption &&
                !props.createOption
              "
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm font-semibold text-[var(--ds-text-muted)]"
            >
              {{ $t('gestlab.general.messages.no_items') }}
            </div>

            <div
              v-if="isLoading || props.loading"
              class="relative flex cursor-default select-none items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-[var(--ds-text-muted)]"
            >
              <svg class="h-4 w-4 animate-spin text-primary-700 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('gestlab.general.buttons.searching') }}
            </div>

            <template v-if="!isLoading && !props.loading">
              <ComboboxOption
                v-if="
                  queryOption && !filteredOptions.length && props.createOption
                "
                as="template"
                :value="queryOption"
                v-slot="{active}"
              >
                <li
                  class="ds-option"
                  :class="{ 'ds-option-active': active }"
                >
                  {{ $t('gestlab.general.buttons.create') }} "{{ queryOption.label }}"
                </li>
              </ComboboxOption>
              <ComboboxOption
                v-for="option in filteredOptions"
                as="template"
                :key="option.value"
                :value="option"
                v-slot="{selected, active}"
              >
                <li
                  class="ds-option"
                  :class="{ 'ds-option-active': active }"
                >
                  <span
                    class="block truncate"
                    :class="{'font-medium': selected, 'font-normal': !selected}"
                  >
                    {{ option.label }}
                  </span>
                  <span
                    v-if="selected"
                    class="ds-option-check"
                  >
                    <CheckIcon
                      class="h-5 w-5"
                      aria-hidden="true"
                    />
                  </span>
                </li>
              </ComboboxOption>
            </template>
          </ComboboxOptions>
        </TransitionRoot>
      </div>
    </Combobox>
    
  </div>
</template>
