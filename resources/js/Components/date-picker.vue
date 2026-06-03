<template>
  <DatePicker :model-value="props.modelValue" @update:model-value="handleUpdateModelValue" v-if="props.range" color="primary" :is-dark="isDark">
    <template #default="{ inputValue, inputEvents }">
      <label v-if="label" class="text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">{{ label }}</label>
      <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative flex-1 rounded-2xl shadow-sm">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
          </div>
          <input
            ref="startInput"
            :value="inputValue.start"
            v-on="inputEvents.start"
            class="block w-full rounded-2xl border border-[#d8cbb8] bg-white py-3 pl-10 pr-3 text-sm font-medium text-[#15231f] shadow-sm transition placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
            :placeholder="$t('gestlab.general.calendar_input_start_placeholder')"
          />
        </div>
        <ArrowLongRightIcon class="hidden h-5 w-5 shrink-0 text-[#8d9b94] dark:text-[#657970] sm:block" />
        <div class="relative flex-1 rounded-2xl shadow-sm">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
          </div>
          <input
            ref="endInput"
            :value="inputValue.end"
            v-on="inputEvents.end"
            class="block w-full rounded-2xl border border-[#d8cbb8] bg-white py-3 pl-10 pr-3 text-sm font-medium text-[#15231f] shadow-sm transition placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
            :placeholder="$t('gestlab.general.calendar_input_end_placeholder')"
          />
        </div>
      </div>
    </template>
    <template #footer>
      <div v-if="hasValue" class="w-full px-3 pb-3">
        <button
          class="inline-flex w-full items-center justify-center rounded-full bg-[rgb(var(--primary-800-rgb))] px-3 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))]"
          @click="clearDate"
        >
          <XMarkIcon class="h-4 w-4 mr-1.5" aria-hidden="true" />
          {{ $t('gestlab.general.buttons.clear') }}
        </button>
      </div>
    </template>
  </DatePicker>

  <DatePicker v-else :model-value="props.modelValue" @update:model-value="handleUpdateModelValue" color="primary" :is-dark="isDark">
    <template #default="{ inputValue, inputEvents }">
      <label v-if="label" class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">{{ label }}</label>
      <div class="relative mt-1 rounded-2xl shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
        </div>
        <input
          :value="inputValue"
          v-on="inputEvents"
          class="block w-full rounded-2xl border border-[#d8cbb8] bg-white py-3 pl-10 pr-3 text-sm font-medium text-[#15231f] shadow-sm transition placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
          :placeholder="$t('gestlab.general.calendar_input_placeholder')"
        />
      </div>
    </template>
  </DatePicker>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'
import { CalendarIcon, ArrowLongRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: [String, Date, Object, Array],
  label: {
    type: String,
    default: '',
  },
  range: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const page = usePage()
const hasDarkClass = ref(false)
let observer

const syncDarkClass = () => {
  hasDarkClass.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  syncDarkClass()
  observer = new MutationObserver(syncDarkClass)
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
  observer?.disconnect()
})

const hasValue = computed(() => {
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.length > 0
  }

  if (props.modelValue && typeof props.modelValue === 'object') {
    return Object.values(props.modelValue).some(value => value !== null && value !== undefined && value !== '')
  }

  return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const isDark = computed(() => Boolean(page.props?.darkMode || hasDarkClass.value))

function handleUpdateModelValue(selected) {
  emit('update:modelValue', selected)
}

function clearDate() {
  emit('update:modelValue', props.range ? { start: null, end: null } : null)
}
</script>

<style>
.vc-container,
.vc-popover-content {
  border-radius: 1.5rem;
  border-color: #ded3bf;
  box-shadow: 0 24px 80px rgb(7 17 15 / 0.16);
  overflow: hidden;
}

.vc-pane-container,
.vc-popover-content {
  background: rgba(255, 252, 246, 0.98);
  color: #17231f;
}

.vc-title,
.vc-nav-title {
  font-weight: 800;
  color: #17231f;
}

.vc-weekday {
  color: #5d746c;
  font-weight: 700;
}

.vc-day-content {
  border-radius: 9999px;
  font-weight: 650;
}

.vc-day-content:hover,
.vc-focus:focus-within {
  background-color: #eef7f3;
  color: #143d37;
}

.vc-highlight {
  background-color: var(--color-primary-600, rgb(20 86 75));
}

.vc-nav-arrow {
  border-radius: 9999px;
  color: #143d37;
}

.vc-nav-arrow:hover {
  background-color: #eef7f3;
}

.dark .vc-container,
.dark .vc-pane-container,
.dark .vc-popover-content {
  background-color: #07110f;
  border-color: #25443c;
  color: #f7f1e7;
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
  background-color: #10231f;
  border-color: #25443c;
  color: #f7f1e7;
}

.dark .vc-highlight {
  background-color: var(--color-primary-500, rgb(31 122 104));
}

.dark .vc-day-content:hover,
.dark .vc-focus:focus-within {
  background-color: #10231f;
  color: #f7f1e7;
}

.dark .vc-weekday {
  color: #9fb8ae;
}

.dark .vc-nav-arrow:hover {
  background-color: #10231f;
}
</style>
