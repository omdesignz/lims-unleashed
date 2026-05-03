<template>
        <Datepicker :model-value="props.modelValue" :locale="locale" :cancel-text="cancelText" :select-text="selectText" @update:modelValue="value => emit('update:modelValue', value)" :range="props.range" :multi-dates="multiDates" ref="dp" :dark="$page.props.darkMode ?? false">
            <template #dp-input="{ value, onInput, onEnter, onTab, onClear }">
                <div>
                    <label :for="props.name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ props.label }}</label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <CalendarDaysIcon class="h-6 w-6 text-gray-400 dark:text-gray-500" aria-hidden="true" />
                        </div>
                        <input type="string" :value="value" :name="props.name" :id="props.id" class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 font-sans py-2.5 pl-10 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 sm:text-sm transition-colors duration-200" :placeholder="props.placeholder" />
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
                    <button @click="selectDate" type="button" class="relative inline-flex items-center rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition-colors duration-200">
                      <span class="sr-only">Select</span>
                      <CheckIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                    <button @click="closeMenu" type="button" class="relative -ml-px inline-flex items-center rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 transition-colors duration-200">
                      <span class="sr-only">Cancel</span>
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
import {onMounted, ref} from "vue";
import { XMarkIcon, ClockIcon, CheckIcon, CalendarIcon, CalendarDaysIcon, ChevronDownIcon, ChevronRightIcon, ChevronUpIcon, ChevronLeftIcon } from "@heroicons/vue/24/outline";
let date = ref(null);

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

    // const startDate = new Date();
    // const endDate = new Date(new Date().setDate(startDate.getDate() + 7));
    // date.value = [startDate, endDate];
});
const dp = ref();
const selectDate = () => {
    dp.value.selectDate();
}

const closeMenu = () => {
    dp.value.closeMenu();
}

</script>

<style>
.dp__calendar_wrap {
    font-family: -apple-system,blinkmacsystemfont,"Segoe UI",roboto,oxygen,ubuntu,cantarell,"Open Sans","Sans","Helvetica Neue",sans-serif;
}
.dp__month_year_select {
    font-family: -apple-system,blinkmacsystemfont,"Segoe UI",roboto,oxygen,ubuntu,cantarell,"Open Sans","Sans","Helvetica Neue",sans-serif;
}

/* Base theme modifications for @vuepic/vue-datepicker */
.dp__theme_light {
   --dp-primary-color: var(--color-primary-600, #2563eb);
   --dp-primary-text-color: #ffffff;
   --dp-hover-color: var(--color-primary-50, #f3f4f6);
   --dp-hover-text-color: var(--color-primary-900, #111827);
   --dp-hover-icon-color: var(--color-primary-700, #111827);
   --dp-primary-disabled-color: var(--color-primary-300, #93c5fd);
}

.dark .dp__theme_light {
   --dp-background-color: var(--color-gray-800, #1f2937);
   --dp-text-color: var(--color-gray-100, #f3f4f6);
   --dp-hover-color: var(--color-gray-700, #374151);
   --dp-hover-text-color: var(--color-gray-100, #f3f4f6);
   --dp-hover-icon-color: var(--color-gray-100, #f3f4f6);
   --dp-primary-color: var(--color-primary-600, #2563eb);
   --dp-primary-disabled-color: var(--color-primary-800, #1e40af);
   --dp-primary-text-color: #ffffff;
   --dp-secondary-color: var(--color-gray-500, #6b7280);
   --dp-border-color: var(--color-gray-600, #4b5563);
   --dp-menu-border-color: var(--color-gray-600, #4b5563);
   --dp-border-color-hover: var(--color-gray-500, #6b7280);
   --dp-disabled-color: var(--color-gray-700, #374151);
   --dp-scroll-bar-background: var(--color-gray-700, #374151);
   --dp-scroll-bar-color: var(--color-gray-500, #6b7280);
   --dp-success-color: #10b981;
   --dp-success-color-disabled: #059669;
   --dp-icon-color: var(--color-gray-400, #9ca3af);
   --dp-danger-color: #ef4444;
}
</style>
