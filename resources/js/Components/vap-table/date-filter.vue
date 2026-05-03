<template>
    <div>
        <DatePicker
            v-model.range.string="dateFilter.value"
            :select-attribute="selectDragAttribute"
            :drag-attribute="selectDragAttribute"
            @drag="dragValue = $event"
            :locale="$page.props.auth?.user?.language === 'en' ? 'en-US' : 'pt-PT'"
            color="primary"
            :is-dark="$page.props.darkMode ?? false"
            mode="date"
            @update:model-value="onDateChange"
            :masks="masks"
            >
            <template #day-popover="{ format }">
                <div class="text-xs">
                    {{ dragValue ? dragValue.start : dateFilter.value.start }}
                    -
                    {{ dragValue ? dragValue.end : dateFilter.value.end }}
                </div>
            </template>
            <template #default="{ togglePopover }">
                <div class="flex rounded-md shadow-sm">
                <div class="relative flex flex-grow items-stretch focus-within:z-10">
                    <select v-model="dateFilter.field" id="field" name="field" autocomplete="field" class="block w-full rounded-none rounded-l-md border-0 py-1 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-900 dark:focus:ring-primary-500 sm:text-sm sm:leading-6" @change="onChange">
                        <option v-for="column in columns.filter(column => column.type === 'date')" :key="column.id" :value="column.field">{{ $t(column.label) }}</option>
                    </select>
                </div>
                <button type="button" class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-md px-2 py-1 text-sm font-semibold text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700" @click="togglePopover">
                    <CalendarDateRangeIcon class="-ml-1 mr-2 h-5 w-5 text-primary-900 dark:text-primary-400" aria-hidden="true" /> {{ dateFilter.value ? `${dateFilter.value.start} - ${dateFilter.value.end}` : '-' }} 
                    
                </button>
                </div>
                
            </template>
            <template #footer>
                <div class="w-full px-3 pb-3" v-if="dateFilter.value !== null">
                    <button
                    class="inline-flex items-center justify-center bg-primary-900 dark:bg-primary-600 hover:bg-primary-800 dark:hover:bg-primary-500 text-white font-medium w-full px-3 py-1 rounded-full transition-colors duration-200"
                    @click="dateFilter.value = null, dateFilter.field = null"
                    >
                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                    {{ $t("gestlab.general.buttons.clear") }}
                    </button>
                </div>
            </template>
        </DatePicker>
    </div>
  </template>
  
  <script setup>
  import { defineProps, defineEmits, computed, ref } from 'vue';
  import { DatePicker } from 'v-calendar'
  import {
  CalendarDateRangeIcon,
} from "@heroicons/vue/24/outline";
  
  const props = defineProps({
    modelValue: [String, Date, Object],
    columns: Array,
    dateFilter: Object,
  });
  
  const emit = defineEmits(['date-change', 'update:modelValue']);
  
  const onDateChange = (selected) => {
    emit('date-change');
    emit("update:modelValue", selected);
  };

const dragValue = ref(null);

const selectDragAttribute = computed(() => ({
  popover: {
    visibility: 'hover',
    isInteractive: false,
  },
}));

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});
  </script>
  
