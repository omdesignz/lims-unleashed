<script setup>
import {ref, computed, watch, nextTick} from "vue";
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxLabel,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from "@headlessui/vue";
import {CheckIcon, ChevronUpDownIcon, XMarkIcon} from "@heroicons/vue/20/solid";

const emit = defineEmits(["update:modelValue"]);

const props = defineProps({
  modelValue: {
    type: [Array, Object],
    default: () => []
  },
  titleLabel: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  loadingLabel: {
    type: String,
    default: ''
  },
  noResultsLabel: {
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
  multiple: {
    type: Boolean,
    default: false
  },
});

const options = ref([...props.options]);
const internalLoading = ref(false); 
const inputRef = ref(null);
const containerRef = ref(null);

watch(() => props.options, (newOptions) => {
  options.value = Array.isArray(newOptions) ? [...newOptions] : [];
}, { deep: true });

const queryOption = computed(() => {
  return query.value === ""
    ? null
    : {
        missing: true,
        label: query.value,
        // Assuming value is needed for tracking missing options
        value: query.value, 
      };
});

let query = ref("");
function applyLoadedOptions(results = []) {
  options.value = Array.isArray(results) ? results : [];
  internalLoading.value = false;
}

function loadRemoteOptions(q) {
  internalLoading.value = true;

  try {
    const maybePromise = props.loadOptions(q, applyLoadedOptions);

    if (maybePromise?.then) {
      maybePromise
        .then(results => {
          if (Array.isArray(results)) {
            applyLoadedOptions(results);
          }
        })
        .catch(() => applyLoadedOptions([]));
    } else if (Array.isArray(maybePromise)) {
      applyLoadedOptions(maybePromise);
    }
  } catch {
    applyLoadedOptions([]);
  }
}

watch(
  query,
  q => {
    if (props.loadOptions) {
      if (q === "" && options.value.length > 0 && !props.multiple) {
          return; 
      }
      loadRemoteOptions(q);
    }
  },
  {immediate: true}
);

let filteredOptions = computed(() => {
  if (query.value === "") {
    return options.value;
  }
  
  return options.value.filter(option => {
    if (!option || !option.label) return false;
    
    return String(option.label)
      .toLowerCase()
      .replace(/\s+/g, "")
      .includes(query.value.toLowerCase().replace(/\s+/g, ""));
  });
});

function handleUpdateModelValue(selected) {
  if (!selected) return;

  // Handle the "Create New Option" flow
  if (props.createOption && (selected?.missing || (props.multiple && Array.isArray(selected) && selected.some(item => item.missing)))) {
    
    const itemToCreate = props.multiple 
      ? selected.find(item => item.missing) 
      : selected;
    
    if (itemToCreate) {
      internalLoading.value = true;
      
      props.createOption(itemToCreate, createdOption => {
        let newValues;
        
        if (props.multiple) {
          const withoutMissing = selected.filter(item => !item.missing);
          newValues = [...withoutMissing, createdOption];
        } else {
          newValues = createdOption;
        }
        
        emit("update:modelValue", newValues);
        internalLoading.value = false;
      });
    }
  } else {
    // For normal selection/deselection
    emit("update:modelValue", selected);
  }

  // Clear query after selection in all cases
  query.value = "";
}

function removeOption(index) {
  if (!props.multiple || !props.modelValue) return;
  
  const newValues = [...props.modelValue];
  newValues.splice(index, 1);
  emit("update:modelValue", newValues);
  
  nextTick(() => {
    focusInput();
  });
}

function clear() {
  emit("update:modelValue", props.multiple ? [] : null);
  query.value = "";
}

function focusInput() {
  if (containerRef.value) {
    const input = containerRef.value.querySelector('input');
    if (input) {
      input.focus();
    }
  }
}

const isSelected = (option) => {
  if (!props.modelValue) return false;
  
  if (props.multiple && Array.isArray(props.modelValue)) {
    return props.modelValue.some(item => String(item.value) === String(option.value));
  }
  
  return String(props.modelValue?.value) === String(option.value);
};

const availableOptions = computed(() => {
  if (!props.multiple) return filteredOptions.value;
  
  const selectedValues = Array.isArray(props.modelValue) ? props.modelValue : [];
  
  return filteredOptions.value.filter(option => 
    !selectedValues.some(item => String(item.value) === String(option.value))
  );
});
</script>

<template>
  <div class="w-full" ref="containerRef">
    <Combobox
      :model-value="props.modelValue"
      @update:model-value="handleUpdateModelValue"
      :multiple="props.multiple"
      as="div"
      by="value"
    >
      <ComboboxLabel v-if="props.titleLabel" class="mb-1 block text-sm font-semibold leading-6 text-[#31413b] dark:text-[#d7e2dd]">
        {{ props.titleLabel }}
      </ComboboxLabel>
      
      <div class="relative w-full">
        <div class="relative min-h-[38px]">
          <div
            class="relative w-full cursor-default overflow-hidden rounded-2xl border border-[#d8cbb8] bg-[#fffdf7] text-left shadow-sm ring-1 ring-white/70 transition-all duration-200 focus-within:border-[rgb(var(--primary-500-rgb))] focus-within:ring-2 focus-within:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:ring-white/10 sm:text-sm"
            :class="{
              'border-red-300 ring-red-300 dark:border-red-500/50': props.hasError
            }"
            @click="focusInput"
          >
            <div v-if="props.multiple && Array.isArray(props.modelValue) && props.modelValue.length > 0" 
                 class="flex items-center pl-3 pr-10 py-1 gap-1 z-10 overflow-x-auto whitespace-nowrap"> 
                <div
                v-for="(option, index) in props.modelValue.filter(item => !item.missing)"
                :key="option.value"
                class="group inline-flex flex-shrink-0 items-center gap-1 rounded-full bg-[rgb(var(--primary-100-rgb))] px-2 py-0.5 text-xs font-semibold text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.16)] dark:text-[rgb(var(--primary-100-rgb))]"
              >
                <span class="truncate max-w-[120px]">{{ option.label }}</span>
                <button
                  type="button"
                  @click.stop="removeOption(index)"
                  class="flex-shrink-0 text-[rgb(var(--primary-700-rgb))] transition-colors hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none dark:text-[rgb(var(--primary-200-rgb))] dark:hover:text-white"
                  aria-label="Remove"
                >
                  <XMarkIcon class="w-3 h-3" />
                </button>
              </div>
              
              <ComboboxInput
                ref="inputRef"
                class="min-w-[50px] flex-shrink border-0 bg-transparent px-1 py-1 text-sm font-medium text-[#15231f] focus:border-transparent focus:outline-none focus:ring-0 dark:text-[#f7f1e7]"
                :displayValue="() => ''"
                @change="query = $event.target.value"
                :placeholder="props.modelValue.length > 0 ? '' : props.placeholder"
                :disabled="props.disableInput"
              />
            </div>

            <template v-if="!props.multiple || !Array.isArray(props.modelValue) || props.modelValue.length === 0">
              <ComboboxInput
                ref="inputRef"
                class="w-full border-0 bg-transparent py-2.5 pl-3.5 pr-10 text-sm font-medium text-[#15231f] placeholder:text-[#8d9b94] focus:outline-none focus:ring-0 dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
                :class="[
                  props.hasError 
                    ? 'text-red-900 placeholder-red-300 dark:text-red-300 dark:placeholder-red-400/70' 
                    : ''
                ]"
                :displayValue="(option) => option?.label || ''"
                @change="query = $event.target.value"
                :placeholder="props.placeholder"
                :disabled="props.disableInput"
              />
            </template>

            <button
              v-if="!props.multiple && props.modelValue"
              @click.stop="clear"
              class="absolute inset-y-0 right-6 flex items-center px-2 text-[#8d9b94] hover:text-[rgb(var(--primary-800-rgb))] focus:outline-none dark:text-[#657970] dark:hover:text-[rgb(var(--primary-200-rgb))]"
              type="button"
              aria-label="Clear selection"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>

            <ComboboxButton
              class="absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 focus:outline-none"
            >
              <ChevronUpDownIcon
                class="h-5 w-5 text-[#8d9b94] dark:text-[#657970]"
                aria-hidden="true"
              />
            </ComboboxButton>
            
            <div v-if="props.loading || internalLoading" class="absolute inset-y-0 right-8 flex items-center pr-2 pointer-events-none">
              <svg class="h-4 w-4 animate-spin text-primary-700 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <TransitionRoot
          leave="transition ease-in duration-100"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
          @after-leave="query = ''"
        >
          <ComboboxOptions
            class="absolute z-50 mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-[#ded3bf] bg-[#fffdf7]/98 p-2 text-sm shadow-[0_24px_80px_rgb(20_61_55/0.18)] ring-1 ring-white/70 backdrop-blur-sm focus:outline-none dark:border-[#25443c] dark:bg-[#07110f]/98 dark:ring-white/10"
          >
            <div
              v-if="
                availableOptions.length === 0 &&
                !internalLoading &&
                !props.loading &&
                !queryOption &&
                !props.createOption
              "
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]"
            >
              {{ props.noResultsLabel || $t('gestlab.general.messages.no_items') }}
            </div>

            <div
              v-if="internalLoading || props.loading"
              class="relative flex cursor-default select-none items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]"
            >
              <svg class="h-4 w-4 animate-spin text-primary-700 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ props.loadingLabel || $t('gestlab.general.buttons.searching') }}
            </div>

            <template v-if="!internalLoading && !props.loading">
              <ComboboxOption
                v-if="
                  queryOption && 
                  !availableOptions.length && 
                  props.createOption && 
                  (!props.multiple || !props.modelValue.some(item => item.label === queryOption.label))
                "
                as="template"
                :value="queryOption"
                v-slot="{active}"
              >
                <li
                  class="relative cursor-pointer select-none rounded-xl py-2.5 pl-10 pr-4 text-sm"
                  :class="{
                    'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]': active,
                    'text-[#15231f] dark:text-[#f7f1e7]': !active,
                  }"
                >
                  {{ $t('gestlab.general.buttons.create') }} "{{ queryOption.label }}"
                </li>
              </ComboboxOption>
              <ComboboxOption
                v-for="option in availableOptions"
                as="template"
                :key="option.value"
                :value="option"
                v-slot="{active}"
              >
                <li
                  class="relative cursor-pointer select-none rounded-xl py-2.5 pl-10 pr-4 text-sm"
                  :class="{
                    'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]': active,
                    'text-[#15231f] dark:text-[#f7f1e7]': !active,
                  }"
                >
                  <div class="flex items-center justify-between">
                    <span
                      class="block truncate"
                      :class="{'font-medium': isSelected(option), 'font-normal': !isSelected(option)}"
                    >
                      {{ option.label }}
                    </span>
                    <span v-if="isSelected(option)" :class="{'text-white dark:text-[#07110f]': active, 'text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]': !active}">
                      <CheckIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                  </div>
                </li>
              </ComboboxOption>
            </template>
          </ComboboxOptions>
        </TransitionRoot>
      </div>
    </Combobox>
  </div>
</template>
