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

  loadOptions: Function,
  createOption: Function,
})

const options = ref([...props.options])
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
  (q) => {
    if (props.loadOptions) {
      loadRemoteOptions(q)
    }
  },
  { immediate: true }
)

watch(
  () => props.options,
  (value) => {
    if (!props.loadOptions) {
      options.value = Array.isArray(value) ? value : []
    }
  },
  { deep: true }
)

const filteredOptions = computed(() =>
  query.value === ''
    ? options.value
    : options.value.filter(option =>
        String(option?.label ?? '')
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query.value.toLowerCase().replace(/\s+/g, ''))
      )
)

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
  <Combobox
    by="value"
    :model-value="props.modelValue"
    @update:model-value="handleUpdateModelValue"
  >
    <ComboboxLabel v-if="props.titleLabel" class="mb-2 block text-sm font-semibold leading-6 text-[#31413b] dark:text-[#d7e2dd]">
      {{ props.titleLabel }}
    </ComboboxLabel>
    <div class="relative mt-0">
      <div
        class="relative w-full cursor-default overflow-hidden rounded-2xl border border-slate-300/90 bg-white/95 text-left shadow-sm ring-1 ring-white/50 transition-all duration-200 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-slate-800/60 sm:text-sm"
      >
        <ComboboxInput
          class="w-full border-0 bg-transparent py-3 pl-4 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
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
            class="h-4.5 w-4.5 cursor-pointer text-slate-400 transition-colors hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
            aria-hidden="true"
            @click.stop="clear"
          />
          <ChevronUpDownIcon
            class="h-5 w-5 text-slate-400 dark:text-slate-500"
            aria-hidden="true"
          />
        </ComboboxButton>
      </div>
      <TransitionRoot
        leave="transition ease-in duration-100"
        leaveFrom="opacity-100"
        leaveTo="opacity-0"
        @after-leave="query = ''"
      >
        <ComboboxOptions
          class="absolute z-50 mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-slate-200 bg-white/98 p-2 text-sm shadow-2xl ring-1 ring-slate-900/5 backdrop-blur-sm focus:outline-none dark:border-slate-700 dark:bg-slate-900/98 dark:ring-slate-100/5"
        >
          <div
            v-if="
              filteredOptions.length === 0 &&
              !isLoading &&
              !queryOption &&
              !props.createOption
            "
            class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400"
          >
            {{ $t('gestlab.general.messages.no_items') }}
          </div>

          <div
            v-if="isLoading"
            class="relative flex cursor-default select-none items-center gap-2 rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400"
          >
            {{ $t('gestlab.general.buttons.searching') }}
          </div>

          <template v-if="!isLoading">
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
                  'bg-primary-900 text-white dark:bg-primary-700': active,
                  'text-slate-900 dark:text-slate-100': !active,
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
                  'bg-primary-900 text-white dark:bg-primary-700': active,
                  'text-slate-900 dark:text-slate-100': !active,
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
                  :class="{'text-white': active, 'text-primary-700 dark:text-primary-300': !active}"
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
</template>
