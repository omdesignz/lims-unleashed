<template>
    <Combobox as="div" :model-value="props.modelValue" @update:modelValue="value => emit('update:modelValue', value)" :multiple="props.multiple">
        <ComboboxLabel class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ props.label }}</ComboboxLabel>
        <div class="relative mt-1">
            <ComboboxInput class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-3 pr-10 text-sm text-gray-900 shadow-sm transition focus:border-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-900/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-primary-500 dark:focus:ring-primary-500/20" :placeholder="props.placeholder" @change="query = $event.target.value" :display-value="() => label" />
            <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
                <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
            </ComboboxButton>

            <ComboboxOptions v-if="filteredOptions.length > 0" class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-2 text-sm shadow-xl ring-1 ring-black/5 focus:outline-none dark:border-gray-700 dark:bg-gray-900">
                <ComboboxOption v-for="option in filteredOptions" :key="option.label" :value="option.value" as="template" v-slot="{ active, selected }">
                    <li :class="['relative cursor-pointer select-none py-2 pl-3 pr-9 transition', active ? 'bg-primary-900 text-white dark:bg-primary-600' : 'text-gray-900 dark:text-gray-100']">
            <span :class="['block truncate', selected && 'font-semibold']">
              {{ option.label }}
            </span>


                        <span v-if="selected" :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']">
              <CheckIcon class="h-5 w-5" aria-hidden="true" />
            </span>
                    </li>
                </ComboboxOption>
            </ComboboxOptions>
        </div>
        <div class="mt-1 text-xs text-red-500 dark:text-red-400" v-if="props.error">
            {{ props.error }}
        </div>
    </Combobox>
</template>

<script setup>
import { computed, ref } from 'vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxLabel,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/vue'

const props = defineProps({
    options: Array,
    modelValue: [String, Number, Array ],
    placeholder: {
        type: String,
        default: 'Seleccione uma opção'
    },
    multiple: Boolean,
    error: String,
    label: String
});

const emit = defineEmits([
    'update:modelValue'
]);

const label = computed(() => {
    return props.options.filter(option => {
         if (Array.isArray(props.modelValue)) {
             return props.modelValue.includes(option.value);
         }

         return props.modelValue === option.value;
    })
        .map(option => option.label)
        .join(', ');
});

const query = ref('')

const filteredOptions = computed(() =>
    query.value === ''
        ? props.options
        : props.options.filter((option) => {
            return option.label.toLowerCase().includes(query.value.toLowerCase())
        })
)
</script>
