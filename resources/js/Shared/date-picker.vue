<template>
        <Datepicker :model-value="props.modelValue" :locale="locale" :cancel-text="cancelText" :select-text="selectText" @update:modelValue="value => emit('update:modelValue', value)" :range="props.range" :multi-dates="multiDates" ref="dp" :dark="isDark">
            <template #dp-input="{ value, onInput, onEnter, onTab, onClear }">
                <div>
                    <label :for="props.name" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ props.label }}</label>
                    <div class="relative mt-1 rounded-2xl shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <CalendarDaysIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" aria-hidden="true" />
                        </div>
                        <input type="text" :value="value" :name="props.name" :id="props.id" class="w-full rounded-2xl border border-slate-300/90 bg-white/95 py-3 pl-10 pr-4 font-sans text-sm font-medium text-slate-900 shadow-sm ring-1 ring-white/50 transition focus:border-[#1f7a68] focus:ring-2 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800/60 dark:placeholder-slate-500" :placeholder="props.placeholder" />
                    </div>
                </div>
            </template>

            <template #clear-icon="{ clear }">
                <XMarkIcon @click="clear" class="h-6 w-6 mt-6 mr-2" aria-hidden="true" />
            </template>

            <template #clock-icon>
                <ClockIcon class="h-6 w-6" aria-hidden="true" />
            </template>

            <template #calendar-icon>
                <CalendarIcon class="h-6 w-6" aria-hidden="true" />
            </template>

            <template #arrow-left>
                <ChevronLeftIcon class="h-6 w-6" aria-hidden="true" />
            </template>

            <template #arrow-right>
                <ChevronRightIcon class="h-6 w-6" aria-hidden="true" />
            </template>

            <template #arrow-up>
                <ChevronUpIcon class="h-6 w-6" aria-hidden="true" />
            </template>

            <template #arrow-down>
                <ChevronDownIcon class="h-6 w-6" aria-hidden="true" />
            </template>

            <template #action-select>
                <span class="isolate inline-flex rounded-md shadow-sm">
                    <button @click="selectDate" type="button" class="relative inline-flex items-center rounded-l-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:bg-[#eef7f3] hover:text-[#143d37] focus:z-10 focus:border-[#1f7a68] focus:outline-none focus:ring-1 focus:ring-[#1f7a68] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                      <span class="sr-only">{{ props.selectText }}</span>
                      <CheckIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                    <button @click="closeMenu" type="button" class="relative -ml-px inline-flex items-center rounded-r-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:bg-[#eef7f3] hover:text-[#143d37] focus:z-10 focus:border-[#1f7a68] focus:outline-none focus:ring-1 focus:ring-[#1f7a68] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                      <span class="sr-only">{{ props.cancelText }}</span>
                      <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                  </span>
            </template>
            <template #day="{ day, date }">
                <p class="font-sans">{{ day }}</p>
            </template>
            <template #calendar-header="{ index, day }">
                <div :class="index === 5 || index === 6 ? 'text-red-500 dark:text-red-400' : 'text-gray-900 dark:text-gray-300'">
                    <p class="font-sans font-semibold">{{ day }}</p>
                </div>
            </template>
        </Datepicker>
</template>

<script setup>
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import {computed, onBeforeUnmount, onMounted, ref} from "vue";
import {usePage} from '@inertiajs/vue3';
import { XMarkIcon, ClockIcon, CheckIcon, CalendarIcon, CalendarDaysIcon, ChevronDownIcon, ChevronRightIcon, ChevronUpIcon, ChevronLeftIcon } from "@heroicons/vue/24/outline";
let date = ref(null);
const page = usePage();
const hasDarkClass = ref(false);
let darkModeObserver;

const props = defineProps({
    range: Boolean,
    multiDates: Boolean,
    name: String,
    id: String,
    placeholder: {
        type: String,
        default: 'Seleccione uma data'
    },
    modelValue: [Date, String, Array],
    classes: String,
    locale: {
        type: String,
        default: 'en-US'
    },
    cancelText: {
        type: String,
        default: 'cancelar'
    },
    selectText: {
        type: String,
        default: 'seleccionar'
    },
    label: {
        type: String,
        default: 'Seleccione uma data'
    }
});

const emit = defineEmits(['update:modelValue']);

onMounted(() => {
    date.value = props.date;
    syncDarkClass();
    darkModeObserver = new MutationObserver(syncDarkClass);
    darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    // const startDate = new Date();
    // const endDate = new Date(new Date().setDate(startDate.getDate() + 7));
    // date.value = [startDate, endDate];
});

onBeforeUnmount(() => {
    darkModeObserver?.disconnect();
});

const syncDarkClass = () => {
    hasDarkClass.value = document.documentElement.classList.contains('dark');
};

const isDark = computed(() => Boolean(page.props?.darkMode || hasDarkClass.value));
const dp = ref();
const selectDate = () => {
    dp.value.selectDate();
}

const closeMenu = () => {
    dp.value.closeMenu();
}

</script>

<style>
.dp__calendar_wrap,
.dp__month_year_select {
    font-family: "Manrope", sans-serif;
}

/* Base theme modifications for @vuepic/vue-datepicker */
.dp__theme_light {
   --dp-font-family: "Manrope", sans-serif;
   --dp-border-radius: 1rem;
   --dp-cell-border-radius: 0.75rem;
   --dp-primary-color: var(--color-primary-600, #1f7a68);
   --dp-primary-text-color: #ffffff;
   --dp-hover-color: var(--color-primary-50, #eef7f3);
   --dp-hover-text-color: var(--color-primary-900, #07110f);
   --dp-hover-icon-color: var(--color-primary-700, #143d37);
   --dp-primary-disabled-color: var(--color-primary-300, #7ebbaa);
   --dp-menu-border-color: #ded3bf;
   --dp-border-color: #d8cfbe;
   --dp-border-color-hover: #1f7a68;
   --dp-box-shadow: 0 24px 80px rgb(7 17 15 / 0.16);
}

.dark .dp__theme_light {
   --dp-background-color: #07110f;
   --dp-text-color: #f7f1e7;
   --dp-hover-color: #10231f;
   --dp-hover-text-color: #f7f1e7;
   --dp-hover-icon-color: #f1d78b;
   --dp-primary-color: var(--color-primary-600, #1f7a68);
   --dp-primary-disabled-color: var(--color-primary-800, #0d2a25);
   --dp-primary-text-color: #ffffff;
   --dp-secondary-color: #94a3b8;
   --dp-border-color: #25443c;
   --dp-menu-border-color: #25443c;
   --dp-border-color-hover: #1f7a68;
   --dp-disabled-color: #10231f;
   --dp-scroll-bar-background: #10231f;
   --dp-scroll-bar-color: #4b635c;
   --dp-success-color: #10b981;
   --dp-success-color-disabled: #059669;
   --dp-icon-color: #cbd5e1;
   --dp-danger-color: #ef4444;
}
</style>
