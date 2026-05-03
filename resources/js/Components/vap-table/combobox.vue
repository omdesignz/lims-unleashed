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
  multiple: Boolean,
});

const options = ref(props.options);
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
</script>

<template>
  <Combobox
    by="value"
    :model-value="props.modelValue"
    @update:model-value="handleUpdateModelValue"
    :multiple="props.multiple"
  >
  <!-- <ComboboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{ props.titleLabel }}</ComboboxLabel> -->
    <div class="">
      <div
        class=""
      >
        <ComboboxInput
          class="w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-3.5 pr-10 text-sm text-gray-900 dark:text-gray-100 shadow-sm transition-colors duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
          :displayValue="option => option?.label"
          @change="query = $event.target.value"
          :placeholder="props.placeholder"
          :disabled="props.disableInput"
        />
        <ComboboxButton
          class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3 focus:outline-none"
        >
          <ChevronUpDownIcon
            class="h-5 w-5 text-gray-400 dark:text-gray-500"
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
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-gray-700 dark:text-gray-400"
          >
            {{ $t('Nenhum registo encontrado.') }}
          </div>

          <div
            v-if="isLoading"
              class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-gray-700 dark:text-gray-400"
          >
            {{ $t('A processar...') }}
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
                class="relative cursor-pointer select-none rounded-xl py-2.5 pl-10 pr-4 text-sm"
                :class="{
                  'bg-primary-900 dark:bg-gray-700 text-white dark:text-gray-400': active,
                  'text-gray-900 dark:text-gray-400': !active,
                }"
              >
                {{ $t('Criar') }} "{{ queryOption.label }}"
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
                class="relative cursor-pointer select-none rounded-xl py-2.5 pl-10 pr-4 text-sm"
                :class="{
                  'bg-primary-900 dark:bg-gray-700 text-white dark:text-gray-400': active,
                  'text-gray-900 dark:text-gray-400': !active,
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
                  :class="{'text-white': active, 'text-primary-900': !active}"
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
