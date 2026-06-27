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
      <ComboboxLabel v-if="props.titleLabel" class="ds-field-label mb-2 block">
        {{ props.titleLabel }}
      </ComboboxLabel>
      
      <div class="relative w-full">
        <div class="relative min-h-[38px]">
          <div
            class="ds-combobox-control cursor-default text-left"
            :data-invalid="props.hasError"
            :data-disabled="props.disableInput"
            @click="focusInput"
          >
            <div v-if="props.multiple && Array.isArray(props.modelValue) && props.modelValue.length > 0" 
                 class="flex items-center pl-3 pr-10 py-1 gap-1 z-10 overflow-x-auto whitespace-nowrap"> 
                <div
                v-for="(option, index) in props.modelValue.filter(item => !item.missing)"
                :key="option.value"
                class="ds-chip group"
              >
                <span class="truncate max-w-[120px]">{{ option.label }}</span>
                <button
                  type="button"
                  @click.stop="removeOption(index)"
                  class="flex-shrink-0 text-current opacity-70 transition-opacity hover:opacity-100 focus:outline-none"
                  :aria-label="$t('gestlab.general.buttons.remove')"
                >
                  <XMarkIcon class="w-3 h-3" />
                </button>
              </div>
              
              <ComboboxInput
                ref="inputRef"
                class="min-w-[50px] flex-shrink border-0 bg-transparent px-1 py-1 text-sm font-semibold text-[var(--ds-text)] placeholder:text-[var(--ds-text-soft)] focus:border-transparent focus:outline-none focus:ring-0"
                :displayValue="() => ''"
                @change="query = $event.target.value"
                :placeholder="props.modelValue.length > 0 ? '' : props.placeholder"
                :disabled="props.disableInput"
              />
            </div>

            <template v-if="!props.multiple || !Array.isArray(props.modelValue) || props.modelValue.length === 0">
              <ComboboxInput
                ref="inputRef"
                class="w-full border-0 bg-transparent py-3 pl-4 pr-10 text-sm font-semibold text-[var(--ds-text)] placeholder:text-[var(--ds-text-soft)] focus:outline-none focus:ring-0"
                :displayValue="(option) => option?.label || ''"
                @change="query = $event.target.value"
                :placeholder="props.placeholder"
                :disabled="props.disableInput"
              />
            </template>

            <button
              v-if="!props.multiple && props.modelValue"
              @click.stop="clear"
              class="absolute inset-y-0 right-6 flex items-center px-2 text-[var(--ds-text-soft)] transition-colors hover:text-[rgb(var(--primary-700-rgb))] focus:outline-none"
              type="button"
              :aria-label="$t('gestlab.general.buttons.clear')"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>

            <ComboboxButton
              class="absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 focus:outline-none"
            >
              <ChevronUpDownIcon
                class="h-5 w-5 text-[var(--ds-text-soft)]"
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
            class="ds-floating-panel absolute z-50 mt-2 max-h-72 w-full overflow-auto p-2 text-sm focus:outline-none"
          >
            <div
              v-if="
                availableOptions.length === 0 &&
                !internalLoading &&
                !props.loading &&
                !queryOption &&
                !props.createOption
              "
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm font-semibold text-[var(--ds-text-muted)]"
            >
              {{ props.noResultsLabel || $t('gestlab.general.messages.no_items') }}
            </div>

            <div
              v-if="internalLoading || props.loading"
              class="relative flex cursor-default select-none items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-[var(--ds-text-muted)]"
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
                  class="ds-option"
                  :class="{ 'ds-option-active': active }"
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
                  class="ds-option"
                  :class="{ 'ds-option-active': active }"
                >
                  <div class="flex items-center justify-between">
                    <span
                      class="block truncate"
                      :class="{'font-medium': isSelected(option), 'font-normal': !isSelected(option)}"
                    >
                      {{ option.label }}
                    </span>
                    <span v-if="isSelected(option)" class="text-current">
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
