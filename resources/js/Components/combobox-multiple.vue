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
import {CheckIcon, ChevronUpDownIcon} from "@heroicons/vue/20/solid";

const emit = defineEmits(["update:modelValue", "remove:modelValue"]);

const props = defineProps({
  modelValue: [String, Number, Array, Object],
  titleLabel: {
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
  loadOptions: Function,
  createOption: Function,
  multiple: Boolean,
});


// const options = ref(props.options);
const options = ref([]);
const isLoading = ref(false);

const removeOption = (index) => {
  props.modelValue.splice(index, 1)

  emit('remove:modelValue');
}

const queryOption = computed(() => {
  return query.value === ""
    ? null
    : {
        missing: true,
        label: query.value,
      };
});

let query = ref('');
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
  query.value === ''
    ? options.value
    : options.value.filter(option =>
        option?.label
          ?.toLowerCase()
          ?.replace(/\s+/g, '')
          .includes(query?.value?.toLowerCase()?.replace(/\s+/g, ''))
      )
);

let filteredOptions1 = computed(() => {

  if(query.value === '' || query.value === null) {
    return options.value;
  }

  return options.value.filter(option => {

    if (Array.isArray(option)) {
        return option?.label?.toLowerCase()
      }
     return option.label
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query?.value?.toLowerCase()?.replace(/\s+/g, ''))
      })

  

      
}

);

function handleUpdateModelValue(selected) {
  emit('update:modelValue', selected);

  if (props.createOption && selected?.missing) {
    isLoading.value = true;
    props.createOption(selected, option => {
      emit('update:modelValue', option);
      isLoading.value = false;
    });
  }
}
</script>

<template>
  <Combobox
    by='value'
    :model-value="props.modelValue"
    @update:model-value="handleUpdateModelValue"
    :multiple="props.multiple"
  >
  <ComboboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{ props.titleLabel }}</ComboboxLabel>
  
    <div class="relative mt-1">
    <div>
      <div
        class="flex flex-col relative overflow-hidden rounded-md bg-white shadow-sm border-gray-300 sm:text-sm border rounded-md text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-300"
      >
      <div class="flex flex-auto flex-wrap">
      <div
    v-for="(option, index) in props.modelValue"
              as="template"
              :key="option.value"
              :value="option"
        class="flex justify-center items-center m-1 font-medium py-1 px-2 bg-blue-900 rounded-full text-white border border-blue-900"
    >
        <div class="text-xs font-normal leading-none max-w-full flex-initial">
        {{option.label}}
        </div>
        <div class="flex flex-auto flex-row-reverse">
        <div @click="removeOption(index)">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" 
            class="feather feather-x cursor-pointer hover:text-indigo-400 rounded-full w-4 h-4 ml-2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>
        <div>

        </div>
        </div>
    </div>
      <div class="flex-1">
        <ComboboxInput
          class="bg-transparent rounded-xl focus:border-transparent focus:ring-0 border-0 py-2.5 pl-3.5 pr-10 text-sm text-gray-900 dark:text-gray-100"
          :displayValue="option => option.label"
          @change="query = $event.target.value"
        />
        </div>
        <ComboboxButton
          class="absolute inset-y-0 right-0 flex items-center px-2 focus:outline-none"
        >
          <ChevronUpDownIcon
            class="h-5 w-5 text-gray-400"
            aria-hidden="true"
          />
        </ComboboxButton>
        
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
          class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
          <div
            v-if="
              filteredOptions.length === 0 &&
              !isLoading &&
              !queryOption &&
              !props.createOption
            "
            class="relative cursor-pointer select-none py-2 px-4 text-gray-700"
          >
            {{ props.noResultsLabel }}
          </div>

          <div
            v-if="isLoading"
            class="relative cursor-pointer select-none py-2 px-4 text-gray-700"
          >
            {{ props.loadingLabel }}
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
                class="relative cursor-pointer select-none py-2 pl-10 pr-4"
                :class="{
                  'bg-blue-900 text-white': active,
                  'text-gray-900': !active,
                }"
              >
                Create "{{ queryOption.label }}"
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
                class="relative cursor-pointer select-none py-2 pl-10 pr-4"
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
</template>