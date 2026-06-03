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
      <ComboboxLabel v-if="props.titleLabel" class="mb-2 block text-sm font-semibold leading-6 text-[#31413b] dark:text-[#d7e2dd]">{{ props.titleLabel }}</ComboboxLabel>
      <div class="relative mt-1">
        <div
          class="relative w-full cursor-default overflow-hidden rounded-2xl border border-[#d8cbb8] bg-[#fffdf7] text-left shadow-sm ring-1 ring-white/70 transition-all duration-200 focus-within:border-[rgb(var(--primary-500-rgb))] focus-within:ring-2 focus-within:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:ring-white/10 sm:text-sm"
        >
          <ComboboxInput
            class="w-full border-0 bg-transparent py-3 pl-4 pr-14 text-sm font-medium text-[#15231f] placeholder:text-[#8d9b94] focus:ring-0 dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
            :class="[props.hasError ? 'text-danger-700 placeholder-danger-300 dark:text-danger-300' : '']"
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
              class="h-4.5 w-4.5 text-[#8d9b94] transition-colors hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#657970] dark:hover:text-[rgb(var(--primary-200-rgb))]"
              aria-hidden="true"
              @click.stop="clear"
            />
            <ChevronUpDownIcon
              class="h-5 w-5 text-[#8d9b94] dark:text-[#657970]"
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
            class="absolute z-[9999] mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-[#ded3bf] bg-[#fffdf7]/98 p-2 text-sm shadow-[0_24px_80px_rgb(20_61_55/0.18)] ring-1 ring-white/70 backdrop-blur-sm focus:outline-none dark:border-[#25443c] dark:bg-[#07110f]/98 dark:ring-white/10"
          >
            <div
              v-if="
                filteredOptions.length === 0 &&
                !isLoading &&
                !props.loading &&
                !queryOption &&
                !props.createOption
              "
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]"
            >
              {{ $t('gestlab.general.messages.no_items') }}
            </div>

            <div
              v-if="isLoading || props.loading"
              class="relative flex cursor-default select-none items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]"
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
                  class="relative cursor-default select-none rounded-xl py-2.5 pl-10 pr-4 text-sm"
                  :class="{
                    'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]': active,
                    'text-[#15231f] dark:text-[#f7f1e7]': !active,
                  }"
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
                  class="relative cursor-default select-none rounded-xl py-2.5 pl-10 pr-4 text-sm"
                  :class="{
                    'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]': active,
                    'text-[#15231f] dark:text-[#f7f1e7]': !active,
                  }"
                >
                  <span
                    class="block truncate"
                    :class="{'font-medium': selected, 'font-normal': !selected}"
                  >
                    {{ option.label }}
                  </span>
                  <span
                    v-if="selected"
                    class="absolute inset-y-0 left-0 flex items-center pl-3"
                    :class="{'text-white dark:text-[#07110f]': active, 'text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]': !active}"
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
