<template>
    <Combobox as="div" v-model="internalValue" multiple>
        <ComboboxLabel class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
            {{ $t(label) }}
        </ComboboxLabel>

        <div class="relative mt-1">
            <ComboboxInput
                class="w-full rounded-2xl border border-slate-300/90 bg-white/95 py-3 pl-4 pr-11 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-white/50 transition focus:border-[#1f7a68] focus:outline-none focus:ring-2 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800/60"
                :display-value="displaySelection"
                :placeholder="$t(placeholder)"
                @change="query = $event.target.value"
            />
            <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-2xl px-3 text-slate-400 transition hover:text-[#143d37] dark:text-slate-500 dark:hover:text-[#f1d78b]">
                <ChevronUpDownIcon class="h-5 w-5" aria-hidden="true" />
            </ComboboxButton>

            <ComboboxOptions
                v-if="filteredOptions.length > 0"
                class="absolute z-50 mt-2 max-h-72 w-full overflow-auto rounded-2xl border border-slate-200 bg-white/98 p-2 text-sm shadow-2xl ring-1 ring-slate-900/5 backdrop-blur-sm focus:outline-none dark:border-slate-700 dark:bg-slate-900/98 dark:ring-slate-100/5"
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
                            'relative cursor-pointer select-none rounded-xl py-2.5 pl-4 pr-10 transition',
                            active ? 'bg-[#143d37] text-white dark:bg-[#1f7a68]' : 'text-slate-900 dark:text-slate-100',
                        ]"
                    >
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                            {{ option.name }}
                        </span>

                        <span
                            v-if="selected"
                            :class="[
                                'absolute inset-y-0 right-0 flex items-center pr-3',
                                active ? 'text-white' : 'text-[#143d37] dark:text-[#f1d78b]',
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
        default: 'gestlab.general.labels.responsibles',
    },
    placeholder: {
        type: String,
        default: 'gestlab.general.placeholders.search_responsibles',
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
