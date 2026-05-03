<template>
  <DatePicker :model-value="props.modelValue" @update:model-value="handleUpdateModelValue" v-if="props.range" color="primary" :is-dark="isDark">
    <template #default="{ inputValue, inputEvents }">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ label }}</label>
      <div class="mt-1 flex items-center gap-2">
        <div class="flex-1 rounded-xl shadow-sm relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <CalendarIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" aria-hidden="true" />
          </div>
          <input
            ref="startInput"
            :value="inputValue.start"
            v-on="inputEvents.start"
            class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-10 pr-3 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 shadow-sm transition-colors duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
            :placeholder="$t('gestlab.general.calendar_input_start_placeholder')"
          />
        </div>
        <ArrowLongRightIcon class="h-5 w-5 shrink-0 text-gray-400" />
        <div class="flex-1 rounded-xl shadow-sm relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <CalendarIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" aria-hidden="true" />
          </div>
          <input
            ref="endInput"
            :value="inputValue.end"
            v-on="inputEvents.end"
            class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-10 pr-3 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 shadow-sm transition-colors duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
            :placeholder="$t('gestlab.general.calendar_input_end_placeholder')"
          />
        </div>
      </div>
    </template>
    <template #footer>
      <div class="w-full px-3 pb-3" v-if="props.modelValue !== null">
        <button
          class="inline-flex items-center justify-center bg-primary-800 dark:bg-primary-600 hover:bg-primary-700 dark:hover:bg-primary-500 text-white font-medium w-full px-3 py-2 rounded-xl text-sm transition-colors duration-150"
          @click="props.modelValue = null"
        >
          <XMarkIcon class="h-4 w-4 mr-1.5" aria-hidden="true" />
          {{ $t('gestlab.general.buttons.clear') }}
        </button>
      </div>
    </template>
  </DatePicker>

  <DatePicker v-else :model-value="props.modelValue" @update:model-value="handleUpdateModelValue" color="primary" :is-dark="isDark">
    <template #default="{ inputValue, inputEvents }">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ label }}</label>
      <div class="mt-1 rounded-xl shadow-sm relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <CalendarIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" aria-hidden="true" />
        </div>
        <input
          :value="inputValue"
          v-on="inputEvents"
          class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-10 pr-3 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 shadow-sm transition-colors duration-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
        />
      </div>
    </template>
  </DatePicker>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'
import { CalendarIcon, ArrowLongRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: [String, Date, Object],
  label: String,
  range: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const page = usePage()
const isDark = computed(() => page.props?.darkMode ?? false)

function handleUpdateModelValue(selected) {
  emit('update:modelValue', selected)
}
</script>

<style>
.dark .vc-container,
.dark .vc-pane-container,
.dark .vc-popover-content {
  background-color: #111827;
  border-color: #374151;
  color: #f3f4f6;
}

.dark .vc-header,
.dark .vc-weekdays,
.dark .vc-weeks,
.dark .vc-title,
.dark .vc-nav-title,
.dark .vc-nav-arrow,
.dark .vc-day-content {
  color: #f3f4f6;
}

.dark .vc-nav-popover-container,
.dark .vc-day-popover-container {
  background-color: #111827;
  border-color: #374151;
  color: #f3f4f6;
}

.dark .vc-highlight {
  background-color: #2563eb;
}

.dark .vc-day-content:hover,
.dark .vc-focus:focus-within {
  background-color: #1f2937;
}
</style>
