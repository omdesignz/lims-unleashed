<template>
    <Combobox as="div" v-model="internalValue" multiple>
        <ComboboxLabel class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ label }}
        </ComboboxLabel>

        <div class="relative mt-1">
            <ComboboxInput
                class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-3 pr-10 text-sm text-gray-900 shadow-sm transition focus:border-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-900/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                :display-value="displaySelection"
                :placeholder="placeholder"
                @change="query = $event.target.value"
            />
            <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <ChevronUpDownIcon class="h-5 w-5" aria-hidden="true" />
            </ComboboxButton>

            <ComboboxOptions
                v-if="filteredOptions.length > 0"
                class="absolute z-10 mt-2 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-2 text-sm shadow-xl ring-1 ring-black/5 focus:outline-none dark:border-gray-700 dark:bg-gray-900"
            >
                <ComboboxOption
                    v-for="option in filteredOptions"
                    :key="option.id"
                    :value="option"
                    as="template"
                    v-slot="{ active, selected }"
                >
                    <li
                        :class="[
                            'relative cursor-pointer select-none py-2 pl-3 pr-9 transition',
                            active ? 'bg-primary-900 text-white dark:bg-primary-600' : 'text-gray-900 dark:text-gray-100',
                        ]"
                    >
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                            {{ option.name }}
                        </span>

                        <span
                            v-if="selected"
                            :class="[
                                'absolute inset-y-0 right-0 flex items-center pr-3',
                                active ? 'text-white' : 'text-primary-900 dark:text-primary-400',
                            ]"
                        >
                            <CheckIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                    </li>
                </ComboboxOption>
            </ComboboxOptions>
        </div>
    </Combobox>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
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
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Seleccionar responsáveis',
    },
    placeholder: {
        type: String,
        default: 'Pesquisar e seleccionar responsáveis',
    },
})

const emit = defineEmits(['update:modelValue'])

const query = ref('')
const internalValue = ref(props.modelValue)

watch(
    () => props.modelValue,
    (value) => {
        internalValue.value = value
    }
)

watch(internalValue, (value) => {
    emit('update:modelValue', value)
})

const filteredOptions = computed(() => {
    if (query.value === '') {
        return props.options
    }

    return props.options.filter((option) => {
        return option.name.toLowerCase().includes(query.value.toLowerCase())
    })
})

const displaySelection = (selectedItems) => {
    if (!Array.isArray(selectedItems) || selectedItems.length === 0) {
        return ''
    }

    return selectedItems.map((item) => item.name).join(', ')
}
</script>
