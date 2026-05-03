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
    default: 'Loading...'
  },
  noResultsLabel: {
    type: String,
    default: 'Nothing Found...'
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

const options = ref(props.options);
const internalLoading = ref(false); 
const inputRef = ref(null);
const containerRef = ref(null);

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
watch(
  query,
  q => {
    if (props.loadOptions) {
      if (q === "" && options.value.length > 0 && !props.multiple) {
          return; 
      }
      internalLoading.value = true;
      props.loadOptions(q, results => {
        options.value = results;
        internalLoading.value = false;
      });
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
    
    return option.label
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
    return props.modelValue.some(item => item.value === option.value);
  }
  
  return props.modelValue?.value === option.value;
};

const availableOptions = computed(() => {
  if (!props.multiple) return filteredOptions.value;
  
  const selectedValues = Array.isArray(props.modelValue) ? props.modelValue : [];
  
  return filteredOptions.value.filter(option => 
    !selectedValues.some(item => item.value === option.value)
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
      <ComboboxLabel v-if="props.titleLabel" class="mb-1 block text-sm font-medium leading-6 text-slate-900 dark:text-slate-100">
        {{ props.titleLabel }}
      </ComboboxLabel>
      
      <div class="relative w-full">
        <div class="relative min-h-[38px]">
          <div
            class="relative w-full cursor-default overflow-hidden rounded-2xl border border-slate-300/90 bg-white/95 text-left shadow-sm transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500/20 focus-within:ring-offset-2 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-offset-slate-900 sm:text-sm"
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
                class="group inline-flex flex-shrink-0 items-center gap-1 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-500/15 dark:text-primary-200"
              >
                <span class="truncate max-w-[120px]">{{ option.label }}</span>
                <button
                  type="button"
                  @click.stop="removeOption(index)"
                  class="flex-shrink-0 text-primary-600 transition-colors hover:text-primary-800 focus:outline-none dark:text-primary-300 dark:hover:text-primary-100"
                  aria-label="Remove"
                >
                  <XMarkIcon class="w-3 h-3" />
                </button>
              </div>
              
              <ComboboxInput
                ref="inputRef"
                class="min-w-[50px] flex-shrink border-0 bg-transparent px-1 py-1 text-sm text-slate-900 focus:border-transparent focus:outline-none focus:ring-0 dark:text-slate-100"
                :displayValue="() => ''"
                @change="query = $event.target.value"
                :placeholder="props.modelValue.length > 0 ? '' : props.placeholder"
                :disabled="props.disableInput"
              />
            </div>

            <template v-if="!props.multiple || !Array.isArray(props.modelValue) || props.modelValue.length === 0">
              <ComboboxInput
                ref="inputRef"
                class="w-full border-0 bg-transparent py-2.5 pl-3.5 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
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
              class="absolute inset-y-0 right-6 flex items-center px-2 text-slate-400 hover:text-slate-600 focus:outline-none dark:text-slate-500 dark:hover:text-slate-300"
              type="button"
              aria-label="Clear selection"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>

            <ComboboxButton
              class="absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 focus:outline-none"
            >
              <ChevronUpDownIcon
                class="h-5 w-5 text-slate-400 dark:text-slate-500"
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
            class="absolute z-50 mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-slate-200 bg-white/98 p-2 text-sm shadow-2xl ring-1 ring-slate-900/5 backdrop-blur-sm focus:outline-none dark:border-slate-700 dark:bg-slate-900/98 dark:ring-slate-100/5"
          >
            <div
              v-if="
                availableOptions.length === 0 &&
                !internalLoading &&
                !props.loading &&
                !queryOption &&
                !props.createOption
              "
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400"
            >
              {{ props.noResultsLabel }}
            </div>

            <div
              v-if="internalLoading || props.loading"
              class="relative flex cursor-default select-none items-center gap-2 rounded-xl px-4 py-3 text-sm text-slate-500 dark:text-slate-400"
            >
              <svg class="h-4 w-4 animate-spin text-primary-700 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ props.loadingLabel }}
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
                    'bg-primary-900 text-white dark:bg-primary-700': active,
                    'text-slate-900 dark:text-slate-100': !active,
                  }"
                >
                  Criar "{{ queryOption.label }}"
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
                    'bg-primary-900 text-white dark:bg-primary-700': active,
                    'text-slate-900 dark:text-slate-100': !active,
                  }"
                >
                  <div class="flex items-center justify-between">
                    <span
                      class="block truncate"
                      :class="{'font-medium': isSelected(option), 'font-normal': !isSelected(option)}"
                    >
                      {{ option.label }}
                    </span>
                    <span v-if="isSelected(option)" :class="{'text-white': active, 'text-primary-700 dark:text-primary-300': !active}">
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
