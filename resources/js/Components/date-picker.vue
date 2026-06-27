<template>
  <DatePicker :model-value="props.modelValue" @update:model-value="handleUpdateModelValue" v-if="props.range" color="primary" :is-dark="isDark">
    <template #default="{ inputValue, inputEvents }">
      <label v-if="label" class="ds-field-label">{{ label }}</label>
      <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative flex-1">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" aria-hidden="true" />
          </div>
          <input
            ref="startInput"
            :value="inputValue.start"
            v-on="inputEvents.start"
            class="ds-field pl-10"
            :placeholder="$t('gestlab.general.calendar_input_start_placeholder')"
          />
        </div>
        <ArrowLongRightIcon class="hidden h-5 w-5 shrink-0 text-[var(--ds-text-soft)] sm:block" />
        <div class="relative flex-1">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" aria-hidden="true" />
          </div>
          <input
            ref="endInput"
            :value="inputValue.end"
            v-on="inputEvents.end"
            class="ds-field pl-10"
            :placeholder="$t('gestlab.general.calendar_input_end_placeholder')"
          />
        </div>
      </div>
    </template>
    <template #footer>
      <div v-if="hasValue" class="w-full px-3 pb-3">
        <button
          type="button"
          class="ds-button ds-button-secondary w-full"
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
      <label v-if="label" class="ds-field-label block">{{ label }}</label>
      <div class="relative mt-1">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" aria-hidden="true" />
        </div>
        <input
          :value="inputValue"
          v-on="inputEvents"
          class="ds-field pl-10"
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
