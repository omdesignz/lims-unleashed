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
            <div class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold leading-snug text-primary-900 transition hover:bg-primary-50 dark:text-primary-200 dark:hover:bg-primary-500/10" @click="$emit('add-new-record')">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-flex items-center" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z" />
                </svg>
                <span class="inline-flex items-center">
                        {{ $t('gestlab.general.buttons.add_item') }}
                    </span>

            </div>
        </template>

    </Multiselect>
</template>

<script setup>
import Multiselect from '@vueform/multiselect'
import { computed } from 'vue'

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
        container: 'relative w-full flex items-center justify-end cursor-pointer rounded-2xl border border-[#d8cbb8] bg-[#fffdf7] text-base leading-snug shadow-sm ring-1 ring-white/70 outline-none transition focus-within:border-[#1f7a68] focus-within:ring-2 focus-within:ring-[#1f7a68]/20 dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:ring-white/10 sm:text-sm',
        containerDisabled: 'cursor-default bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-500',
        containerOpen: 'rounded-b-none',
        containerOpenTop: 'rounded-t-none',
        containerActive: '',
        singleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
        multipleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
        search: 'w-full absolute rounded-2xl border-[#d8cbb8] bg-[#fffdf7] pl-3.5 font-sans text-base text-[#15231f] shadow-sm focus:border-[#1f7a68] focus:ring-2 focus:ring-[#1f7a68]/20 dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] sm:text-sm',
        tags: 'grow shrink flex flex-wrap items-center mt-1 pl-2',
        tag: 'bg-[#143d37] text-white text-sm font-semibold py-0.5 pl-2 rounded-full mr-1 mb-1 flex items-center whitespace-nowrap dark:bg-[#1f7a68]',
        tagDisabled: 'pr-2 opacity-50',
        tagRemove: 'flex items-center justify-center p-1 mx-0.5 rounded-sm hover:bg-black/10 group',
        tagRemoveIcon: 'bg-multiselect-remove bg-center bg-no-repeat opacity-30 inline-block w-3 h-3 group-hover:opacity-60',
        tagsSearchWrapper: 'inline-block relative mx-1 mb-1 grow shrink h-full',
        tagsSearch: 'absolute inset-0 border-0 outline-none appearance-none p-0 text-base font-sans box-border w-full',
        tagsSearchCopy: 'invisible whitespace-pre-wrap inline-block h-px',
        placeholder: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5 text-slate-400 dark:text-slate-500',
        caret: 'bg-multiselect-caret bg-center bg-no-repeat w-2.5 h-4 py-px box-content mr-3.5 relative z-10 opacity-40 shrink-0 grow-0 transition-transform transform pointer-events-none',
        caretOpen: 'rotate-180 pointer-events-auto',
        clear: 'pr-3.5 relative z-10 opacity-40 transition duration-300 shrink-0 grow-0 flex hover:opacity-80',
        clearIcon: 'bg-multiselect-remove bg-center bg-no-repeat w-2.5 h-4 py-px box-content inline-block',
        spinner: 'bg-multiselect-spinner bg-center bg-no-repeat w-4 h-4 z-10 mr-3.5 animate-spin shrink-0 grow-0',
        dropdown: 'max-h-72 absolute -left-px -right-px bottom-0 transform translate-y-full border border-[#ded3bf] -mt-px overflow-y-auto z-50 bg-[#fffdf7]/98 flex flex-col rounded-b-2xl shadow-[0_24px_80px_rgb(20_61_55/0.18)] ring-1 ring-white/70 backdrop-blur-sm dark:border-[#25443c] dark:bg-[#07110f]/98 dark:ring-white/10',
        dropdownTop: '-translate-y-full top-px bottom-auto flex-col-reverse rounded-b-none rounded-t',
        dropdownHidden: 'hidden',
        options: 'flex flex-col p-0 m-0 list-none',
        optionsTop: 'flex-col-reverse',
        group: 'p-0 m-0',
        groupLabel: 'flex text-sm box-border items-center justify-start text-left py-1.5 px-3 font-bold bg-slate-100 text-slate-700 cursor-default leading-normal dark:bg-slate-800 dark:text-slate-200',
        groupLabelPointable: 'cursor-pointer',
        groupLabelPointed: 'bg-[#eef7f3] text-[#143d37] dark:bg-slate-700 dark:text-white',
        groupLabelSelected: 'bg-[#143d37] text-white',
        groupLabelDisabled: 'bg-slate-100 text-slate-300 cursor-not-allowed dark:bg-slate-800 dark:text-slate-600',
        groupLabelSelectedPointed: 'bg-[#176452] text-white',
        groupLabelSelectedDisabled: 'text-[#d8ece5] bg-[#143d37]/50 cursor-not-allowed',
        groupOptions: 'p-0 m-0',
        option: 'flex items-center justify-start box-border text-left cursor-pointer text-base leading-snug py-2.5 px-3 text-[#15231f] dark:text-[#f7f1e7]',
        optionPointed: 'text-[#143d37] bg-[#eef7f3] dark:bg-slate-800 dark:text-white',
        optionSelected: 'text-white bg-[#143d37] dark:bg-[#1f7a68]',
        optionDisabled: 'text-slate-300 cursor-not-allowed dark:text-slate-600',
        optionSelectedPointed: 'text-white bg-[#176452]',
        optionSelectedDisabled: 'text-[#d8ece5] bg-[#143d37]/50 cursor-not-allowed',
        noOptions: 'py-2 px-3 text-slate-500 bg-white dark:bg-slate-900 dark:text-slate-400',
        noResults: 'py-2 px-3 text-slate-500 bg-white dark:bg-slate-900 dark:text-slate-400',
        fakeInput: 'bg-transparent absolute left-0 right-0 -bottom-px w-full h-px border-0 p-0 appearance-none outline-none text-transparent',
        spacer: 'h-9 py-px box-content',
    }
});

</script>

<style scoped>

</style>
