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
                <div class="flex rounded-2xl border border-slate-300/90 bg-white/95 shadow-sm ring-1 ring-white/50 dark:border-slate-700 dark:bg-slate-900/90 dark:ring-slate-800/60">
                <div class="relative flex min-w-0 flex-grow items-stretch focus-within:z-10">
                    <select v-model="dateFilter.field" id="field" name="field" autocomplete="field" class="block w-full rounded-l-2xl border-0 bg-transparent py-2.5 pl-3 pr-8 text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:text-slate-100 dark:placeholder:text-slate-500" @change="onChange">
                        <option v-for="column in columns.filter(column => column.type === 'date')" :key="column.id" :value="column.field">{{ $t(column.label) }}</option>
                    </select>
                </div>
                <button type="button" class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-2xl border-l border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-primary-50 hover:text-primary-900 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-primary-500/10 dark:hover:text-primary-200" @click="togglePopover">
                    <CalendarDateRangeIcon class="-ml-1 h-5 w-5 text-primary-800 dark:text-primary-300" aria-hidden="true" /> {{ dateFilter.value ? `${dateFilter.value.start} - ${dateFilter.value.end}` : '-' }}
                    
                </button>
                </div>
                
            </template>
            <template #footer>
                <div class="w-full px-3 pb-3" v-if="dateFilter.value !== null">
                    <button
                    class="inline-flex w-full items-center justify-center rounded-full bg-primary-900 px-3 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
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
  XMarkIcon,
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

  const onChange = () => {
    emit('date-change');
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
  
