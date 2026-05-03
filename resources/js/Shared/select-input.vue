<template>
    <Multiselect
        :id="id"
        :mode="mode"
        :trackBy="trackBy"
        :label="label"
        :searchable="searchable"
        :placeholder="placeholder"
        :filterResults="filterResults"
        :minChars="minChars"
        :resolveOnLoad="resolveOnLoad"
        :delay="delay"
        :options="options"
        :ref="ref"
        :classes="classes">
        <template v-slot:afterlist="{ option }">
            <div class="flex text-base leading-snug py-2 px-3 bg-ft-orange bg-opacity-10" @click="$emit('add-new-record')">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-flex items-center" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z" />
                </svg>
                <span class="inline-flex items-center">
                        Add Item
                    </span>

            </div>
        </template>

    </Multiselect>
</template>

<script setup>
import Multiselect from '@vueform/multiselect'
import {ref, computed} from 'vue'

defineProps({
    id: String,
    ref: String,
    mode: String,
    searchable: Boolean,
    trackBy: String,
    label: String,
    placeholder: String,
    options: [Array, Object, Function],
    classes: Object,
    filterResults: Boolean,
    minChars: Number,
    resolveOnLoad: Boolean,
    delay: Number
});

// const emit = defineEmits(['add-new-record']);

let classes = computed(() => {
    return {
        container: 'relative max-w-lg w-full flex items-center justify-end cursor-pointer border-gray-300 rounded-md bg-white text-base leading-snug outline-none sm:max-w-xs sm:text-sm',
        containerDisabled: 'cursor-default bg-gray-100',
        containerOpen: 'rounded-b-none',
        containerOpenTop: 'rounded-t-none',
        containerActive: '',
        singleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
        multipleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
        search: 'max-w-lg w-full absolute shadow-sm border-gray-300 text-base font-sans bg-white rounded-md pl-3.5 sm:text-sm',
        tags: 'flex-grow flex-shrink flex flex-wrap items-center mt-1 pl-2',
        tag: 'bg-ft-orange text-white text-sm font-semibold py-0.5 pl-2 rounded mr-1 mb-1 flex items-center whitespace-nowrap',
        tagDisabled: 'pr-2 opacity-50',
        tagRemove: 'flex items-center justify-center p-1 mx-0.5 rounded-sm hover:bg-black hover:bg-opacity-10 group',
        tagRemoveIcon: 'bg-multiselect-remove bg-center bg-no-repeat opacity-30 inline-block w-3 h-3 group-hover:opacity-60',
        tagsSearchWrapper: 'inline-block relative mx-1 mb-1 flex-grow flex-shrink h-full',
        tagsSearch: 'absolute inset-0 border-0 outline-none appearance-none p-0 text-base font-sans box-border w-full',
        tagsSearchCopy: 'invisible whitespace-pre-wrap inline-block h-px',
        placeholder: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5 text-gray-400',
        caret: 'bg-multiselect-caret bg-center bg-no-repeat w-2.5 h-4 py-px box-content mr-3.5 relative z-10 opacity-40 flex-shrink-0 flex-grow-0 transition-transform transform pointer-events-none',
        caretOpen: 'rotate-180 pointer-events-auto',
        clear: 'pr-3.5 relative z-10 opacity-40 transition duration-300 flex-shrink-0 flex-grow-0 flex hover:opacity-80',
        clearIcon: 'bg-multiselect-remove bg-center bg-no-repeat w-2.5 h-4 py-px box-content inline-block',
        spinner: 'bg-multiselect-spinner bg-center bg-no-repeat w-4 h-4 z-10 mr-3.5 animate-spin flex-shrink-0 flex-grow-0',
        dropdown: 'max-h-60 absolute -left-px -right-px bottom-0 transform translate-y-full border border-gray-300 -mt-px overflow-y-scroll z-50 bg-white flex flex-col rounded-b',
        dropdownTop: '-translate-y-full top-px bottom-auto flex-col-reverse rounded-b-none rounded-t',
        dropdownHidden: 'hidden',
        options: 'flex flex-col p-0 m-0 list-none',
        optionsTop: 'flex-col-reverse',
        group: 'p-0 m-0',
        groupLabel: 'flex text-sm box-border items-center justify-start text-left py-1 px-3 font-semibold bg-gray-200 cursor-default leading-normal',
        groupLabelPointable: 'cursor-pointer',
        groupLabelPointed: 'bg-gray-300 text-gray-700',
        groupLabelSelected: 'bg-green-600 text-white',
        groupLabelDisabled: 'bg-gray-100 text-gray-300 cursor-not-allowed',
        groupLabelSelectedPointed: 'bg-green-600 text-white opacity-90',
        groupLabelSelectedDisabled: 'text-green-100 bg-green-600 bg-opacity-50 cursor-not-allowed',
        groupOptions: 'p-0 m-0',
        option: 'flex items-center justify-start box-border text-left cursor-pointer text-base leading-snug py-2 px-3',
        optionPointed: 'text-gray-800 bg-gray-100',
        optionSelected: 'text-white bg-ft-orange',
        optionDisabled: 'text-gray-300 cursor-not-allowed',
        optionSelectedPointed: 'text-white bg-ft-orange opacity-90',
        optionSelectedDisabled: 'text-green-100 bg-ft-orange bg-opacity-50 cursor-not-allowed',
        noOptions: 'py-2 px-3 text-gray-600 bg-white',
        noResults: 'py-2 px-3 text-gray-600 bg-white',
        fakeInput: 'bg-transparent absolute left-0 right-0 -bottom-px w-full h-px border-0 p-0 appearance-none outline-none text-transparent',
        spacer: 'h-9 py-px box-content',
    }
});

</script>

<style scoped>

</style>
