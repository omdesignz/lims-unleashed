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

const emit = defineEmits(["update:modelValue"]);

const props = defineProps({
  modelValue: [String, Number, Array, Object],
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
  loadOptions: Function,
  createOption: Function,
  multiple: Boolean,
});


const options = ref([...props.options]);
const isLoading = ref(false);

const removeOption = (index) => {
  const nextValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  nextValue.splice(index, 1);
  emit("update:modelValue", nextValue);
}

const selectedOptions = computed(() => Array.isArray(props.modelValue) ? props.modelValue.filter(Boolean) : []);

const queryOption = computed(() => {
  return query.value === ""
    ? null
    : {
        missing: true,
        label: query.value,
      };
});

let query = ref("");
function applyLoadedOptions(results = []) {
  options.value = Array.isArray(results) ? results : [];

  if (
    props.modelValue &&
    !Array.isArray(props.modelValue) &&
    !options.value?.some(o => {
      return o.value === props.modelValue?.value;
    })
  ) {
    options.value.unshift(props.modelValue);
  }

  isLoading.value = false;
}

function loadRemoteOptions(q) {
  isLoading.value = true;

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
      loadRemoteOptions(q);
    }
  },
  {immediate: true}
);

watch(
  () => props.options,
  value => {
    if (!props.loadOptions) {
      options.value = Array.isArray(value) ? value : [];
    }
  },
  {deep: true}
);

let filteredOptions = computed(() =>
  query.value === ""
    ? options.value
    : options.value.filter(option =>
        String(option?.label ?? "")
          .toLowerCase()
          .replace(/\s+/g, "")
          .includes(query?.value?.toLowerCase()?.replace(/\s+/g, ""))
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
</script>

<template>
  <Combobox
    by="value"
    :model-value="props.modelValue"
    @update:model-value="handleUpdateModelValue"
    :multiple="props.multiple"
  >
  <!-- <ComboboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{ props.titleLabel }}</ComboboxLabel> -->
  
    <div class="relative">
      <div
        class="relative flex min-h-12 flex-col overflow-hidden rounded-2xl border border-slate-300/90 bg-white/95 text-left shadow-sm ring-1 ring-white/50 transition-all focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-slate-800/60 sm:text-sm sm:leading-6"
      >
      <div class="flex flex-auto flex-wrap gap-1.5 px-2 py-2">
      <div
    v-for="(option, index) in selectedOptions"
              as="template"
              :key="option.value"
              :value="option"
        class="relative m-1 flex items-center justify-center rounded-full border border-primary-200 bg-primary-100 px-2 py-1 font-medium text-primary-800 dark:border-primary-500/20 dark:bg-primary-500/15 dark:text-primary-200"
    >
        <div class="text-xs font-normal leading-none max-w-full flex-initial">
        {{ $t(option.label) }}
        </div>
        <div class="flex flex-auto flex-row-reverse">
        <div @click="removeOption(index)">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" 
            class="feather feather-x ml-2 h-4 w-4 cursor-pointer rounded-full transition-colors hover:text-primary-500 dark:hover:text-primary-100">
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
          class="min-w-28 rounded-xl border-0 bg-transparent py-1.5 pl-2 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-transparent focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
          :displayValue="option => option?.label"
          @change="query = $event.target.value"
          :placeholder="props.placeholder"
        />
        </div>
        <ComboboxButton
          class="absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 focus:outline-none"
        >
          <ChevronUpDownIcon
            class="h-5 w-5 text-gray-400 dark:text-gray-500"
            aria-hidden="true"
          />
        </ComboboxButton>
        
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
              filteredOptions.length === 0 &&
              !isLoading &&
              !queryOption &&
              !props.createOption
            "
            class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-gray-700 dark:text-gray-400"
          >
            {{ props.noResultsLabel || $t('gestlab.general.messages.no_items') }}
          </div>

          <div
            v-if="isLoading"
            class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-gray-700 dark:text-gray-400"
          >
            {{ props.loadingLabel || $t('gestlab.general.buttons.searching') }}
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
                  {{ $t(option.label) }}
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
