<script setup>
import {ref, computed, watch} from "vue";
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
});

const options = ref(props.options);

// ADD THIS WATCHER - this fixes the issue!
watch(() => props.options, (newOptions) => {
  console.log('🔄 comboboxEnhanced: props.options changed, updating internal options')
  options.value = newOptions
  
  // Also check if current modelValue exists in new options
  if (props.modelValue && !newOptions.some(o => o.value === props.modelValue?.value)) {
    console.log('⚠️ Current selection not in new options, might need to reset')
  }
}, { deep: true })


const isLoading = ref(false);

const queryOption = computed(() => {
  return query.value === ""
    ? null
    : {
        missing: true,
        label: query.value,
      };
});

let query = ref("");
watch(
  query,
  q => {
    if (props.loadOptions) {
      isLoading.value = true;
      props.loadOptions(q, results => {
        options.value = results;

        if (
          props.modelValue &&
          !options.value.some(o => {
            return o.value === props.modelValue?.value;
          })
        ) {
          options.value.unshift(props.modelValue);
        }
        isLoading.value = false;
      });
    }
  },
  {immediate: true}
);

let filteredOptions = computed(() =>
  query.value === ""
    ? options.value
    : options.value.filter(option =>
        option.label
          .toLowerCase()
          .replace(/\s+/g, "")
          .includes(query.value.toLowerCase().replace(/\s+/g, ""))
      )
);

function handleUpdateModelValue(selected) {
  emit("update:modelValue", selected);

  if (props.createOption && selected?.missing) {
    isLoading.value = true;
    props.createOption(selected, option => {
      emit("update:modelValue", option);
      isLoading.value = false;
    });
  }
}

function clear() {
  emit("update:modelValue", null);
  query.value = "";
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
      <ComboboxLabel v-if="props.titleLabel" class="block text-sm font-medium leading-6 text-gray-900">{{ props.titleLabel }}</ComboboxLabel>
      <div class="relative mt-1">
        <div
          class="relative w-full cursor-default overflow-hidden rounded-md bg-white text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
        >
          <ComboboxInput
            class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
            :class="[props.hasError ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']"
            :displayValue="option => option?.label"
            @change="query = $event.target.value"
            :placeholder="props.placeholder"
            :disabled="props.disableInput"
          />
          <ComboboxButton
            class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none"
          >
            <XMarkIcon
              v-if="props.modelValue"
              class="h-5 w-5 text-gray-400 hover:text-gray-600"
              aria-hidden="true"
              @click.stop="clear"
            />
            <ChevronUpDownIcon
              class="h-5 w-5 text-gray-400"
              aria-hidden="true"
            />
          </ComboboxButton>
          <!-- Loading indicator -->
          <div v-if="props.loading" class="absolute inset-y-0 right-8 flex items-center pr-2 pointer-events-none">
            <svg class="animate-spin h-4 w-4 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
          >
            <div
              v-if="
                filteredOptions.length === 0 &&
                !isLoading &&
                !props.loading &&
                !queryOption &&
                !props.createOption
              "
              class="relative cursor-default select-none py-2 px-4 text-gray-700"
            >
              Não encontramos nenhum registro.
            </div>

            <div
              v-if="isLoading || props.loading"
              class="relative cursor-default select-none py-2 px-4 text-gray-700 flex items-center gap-2"
            >
              <svg class="animate-spin h-4 w-4 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              A processar...
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
                  class="relative cursor-default select-none py-2 pl-10 pr-4"
                  :class="{
                    'bg-blue-900 text-white': active,
                    'text-gray-900': !active,
                  }"
                >
                  Criar "{{ queryOption.label }}"
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
                  class="relative cursor-default select-none py-2 pl-10 pr-4"
                  :class="{
                    'bg-blue-900 text-white': active,
                    'text-gray-900': !active,
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
                    :class="{'text-white': active, 'text-blue-900': !active}"
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