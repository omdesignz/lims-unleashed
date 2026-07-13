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
            <div class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold leading-snug text-[rgb(var(--primary-800-rgb)/1)] transition hover:bg-[var(--ds-panel-subtle)] dark:text-cyan-100" @click="$emit('add-new-record')">
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
        container: 'ds-combobox-control flex cursor-pointer items-center justify-end text-base leading-snug outline-none sm:text-sm',
        containerDisabled: 'cursor-default bg-[var(--ds-panel-muted)] text-[var(--ds-text-soft)] opacity-75',
        containerOpen: 'rounded-b-none',
        containerOpenTop: 'rounded-t-none',
        containerActive: '',
        singleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
        multipleLabel: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5',
        search: 'w-full absolute rounded-lg border-[var(--ds-border)] bg-[var(--ds-panel-raised)] pl-3.5 font-sans text-base text-[var(--ds-text)] shadow-sm focus:border-[rgb(var(--primary-500-rgb)/1)] focus:ring-4 focus:ring-[var(--ds-focus)] sm:text-sm',
        tags: 'grow shrink flex flex-wrap items-center mt-1 pl-2',
        tag: 'ds-chip text-sm py-0.5 pl-2 mr-1 mb-1 flex items-center whitespace-nowrap',
        tagDisabled: 'pr-2 opacity-50',
        tagRemove: 'flex items-center justify-center p-1 mx-0.5 rounded-sm hover:bg-black/10 group',
        tagRemoveIcon: 'bg-multiselect-remove bg-center bg-no-repeat opacity-30 inline-block w-3 h-3 group-hover:opacity-60',
        tagsSearchWrapper: 'inline-block relative mx-1 mb-1 grow shrink h-full',
        tagsSearch: 'absolute inset-0 border-0 outline-none appearance-none p-0 text-base font-sans box-border w-full',
        tagsSearchCopy: 'invisible whitespace-pre-wrap inline-block h-px',
        placeholder: 'flex items-center h-full absolute left-0 top-0 pointer-events-none bg-transparent leading-snug pl-3.5 text-[var(--ds-text-soft)]',
        caret: 'bg-multiselect-caret bg-center bg-no-repeat w-2.5 h-4 py-px box-content mr-3.5 relative z-10 opacity-40 shrink-0 grow-0 transition-transform transform pointer-events-none',
        caretOpen: 'rotate-180 pointer-events-auto',
        clear: 'pr-3.5 relative z-10 opacity-40 transition duration-300 shrink-0 grow-0 flex hover:opacity-80',
        clearIcon: 'bg-multiselect-remove bg-center bg-no-repeat w-2.5 h-4 py-px box-content inline-block',
        spinner: 'bg-multiselect-spinner bg-center bg-no-repeat w-4 h-4 z-10 mr-3.5 animate-spin shrink-0 grow-0',
        dropdown: 'ds-floating-panel max-h-72 absolute -left-px -right-px bottom-0 transform translate-y-full -mt-px overflow-y-auto z-50 flex flex-col rounded-t-none',
        dropdownTop: '-translate-y-full top-px bottom-auto flex-col-reverse rounded-b-none rounded-t',
        dropdownHidden: 'hidden',
        options: 'flex flex-col p-0 m-0 list-none',
        optionsTop: 'flex-col-reverse',
        group: 'p-0 m-0',
        groupLabel: 'flex text-sm box-border items-center justify-start text-left py-1.5 px-3 font-bold bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] cursor-default leading-normal',
        groupLabelPointable: 'cursor-pointer',
        groupLabelPointed: 'bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-800-rgb)/1)] dark:text-cyan-100',
        groupLabelSelected: 'bg-[rgb(var(--primary-800-rgb)/1)] text-white',
        groupLabelDisabled: 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-soft)] cursor-not-allowed',
        groupLabelSelectedPointed: 'bg-[rgb(var(--primary-700-rgb)/1)] text-white',
        groupLabelSelectedDisabled: 'text-cyan-100 bg-[rgb(var(--primary-900-rgb)/0.5)] cursor-not-allowed',
        groupOptions: 'p-0 m-0',
        option: 'flex items-center justify-start box-border text-left cursor-pointer text-base leading-snug py-2.5 px-3 text-[var(--ds-text)]',
        optionPointed: 'bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-800-rgb)/1)] dark:text-cyan-100',
        optionSelected: 'bg-[rgb(var(--primary-800-rgb)/1)] text-white',
        optionDisabled: 'text-[var(--ds-text-soft)] cursor-not-allowed opacity-60',
        optionSelectedPointed: 'bg-[rgb(var(--primary-700-rgb)/1)] text-white',
        optionSelectedDisabled: 'text-cyan-100 bg-[rgb(var(--primary-900-rgb)/0.5)] cursor-not-allowed',
        noOptions: 'py-2 px-3 text-[var(--ds-text-muted)] bg-[var(--ds-panel)]',
        noResults: 'py-2 px-3 text-[var(--ds-text-muted)] bg-[var(--ds-panel)]',
        fakeInput: 'bg-transparent absolute left-0 right-0 -bottom-px w-full h-px border-0 p-0 appearance-none outline-none text-transparent',
        spacer: 'h-9 py-px box-content',
    }
});

</script>

<style scoped>

</style>
