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
    <div class="relative">
      <div
        class="ds-combobox-control flex flex-col text-left sm:text-sm sm:leading-6"
      >
      <div class="flex flex-auto flex-wrap gap-1.5 px-2 py-2">
      <div
    v-for="(option, index) in selectedOptions"
              as="template"
              :key="option.value"
              :value="option"
        class="ds-chip m-1"
    >
        <div class="text-xs font-normal leading-none max-w-full flex-initial">
        {{ $t(option.label) }}
        </div>
        <div class="flex flex-auto flex-row-reverse">
        <button
          type="button"
          class="rounded-full transition-colors hover:text-[rgb(var(--primary-600-rgb))] focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)] dark:hover:text-[rgb(var(--accent-100-rgb))]"
          :aria-label="`${$t('gestlab.general.buttons.remove')} ${$t(option.label)}`"
          @click.stop="removeOption(index)"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" 
            class="ml-1 h-4 w-4">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        </div>
    </div>
      <div class="flex-1">
        <ComboboxInput
          class="min-w-28 rounded-xl border-0 bg-transparent py-1.5 pl-2 pr-10 text-sm text-[var(--ds-text)] placeholder:text-[var(--ds-text-soft)] focus:border-transparent focus:ring-0"
          :displayValue="option => option?.label"
          @change="query = $event.target.value"
          :placeholder="props.placeholder"
        />
        </div>
        <ComboboxButton
          class="absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 focus:outline-none"
        >
          <ChevronUpDownIcon
            class="h-5 w-5 text-[var(--ds-text-soft)]"
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
          class="ds-floating-panel absolute z-50 mt-2 max-h-72 w-full overflow-auto p-2 text-sm focus:outline-none"
        >
          <div
            v-if="
              filteredOptions.length === 0 &&
              !isLoading &&
              !queryOption &&
              !props.createOption
            "
            class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-[var(--ds-text-soft)]"
          >
            {{ props.noResultsLabel || $t('gestlab.general.messages.no_items') }}
          </div>

          <div
            v-if="isLoading"
            class="relative cursor-default select-none rounded-xl px-4 py-3 text-sm text-[var(--ds-text-soft)]"
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
                  {{ $t(option.label) }}
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
</template>
