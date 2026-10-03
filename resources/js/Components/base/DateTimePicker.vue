<script setup>
import { CalendarDays as CalendarDaysIcon, Clock as ClockIcon, X as XMarkIcon } from '@lucide/vue'
import { DatePicker as VDatePicker } from 'v-calendar'
import { computed, onBeforeUnmount, onMounted, ref, useAttrs, useId } from 'vue'
import 'v-calendar/dist/style.css'

defineOptions({
  inheritAttrs: false,
})

const attrs = useAttrs()
const generatedId = useId()

const props = defineProps({
  modelValue: {
    type: [String, Date],
    default: '',
  },
  type: {
    type: String,
    default: 'date',
    validator: (value) => ['date', 'datetime-local', 'time'].includes(value),
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  clearable: {
    type: Boolean,
    default: true,
  },
  min: {
    type: [String, Date],
    default: null,
  },
  max: {
    type: [String, Date],
    default: null,
  },
  locale: {
    type: String,
    default: 'pt-PT',
  },
})

const emit = defineEmits(['update:modelValue', 'change'])
const hasDarkClass = ref(false)
let darkModeObserver

const controlId = computed(() => attrs.id || attrs.name || `date-field-${generatedId}`)
const hintId = computed(() => `${controlId.value}-hint`)
const errorId = computed(() => `${controlId.value}-error`)
const describedBy = computed(() => props.error ? errorId.value : (props.hint ? hintId.value : undefined))
const pickerMode = computed(() => props.type === 'datetime-local' ? 'dateTime' : props.type)
const pickerIcon = computed(() => props.type === 'date' ? CalendarDaysIcon : ClockIcon)
const inputMask = computed(() => ({
  date: 'DD/MM/YYYY',
  'datetime-local': 'DD/MM/YYYY HH:mm',
  time: 'HH:mm',
}[props.type]))
const resolvedPlaceholder = computed(() => props.placeholder || ({
  date: 'dd/mm/aaaa',
  'datetime-local': 'dd/mm/aaaa hh:mm',
  time: 'hh:mm',
}[props.type]))

function parseModelValue(value) {
  if (!value) {
    return null
  }

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value
  }

  if (props.type === 'time') {
    const match = String(value).match(/^(\d{2}):(\d{2})/)

    if (!match) {
      return null
    }

    const date = new Date()
    date.setHours(Number(match[1]), Number(match[2]), 0, 0)

    return date
  }

  const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/)

  if (!match) {
    return null
  }

  const date = new Date(
    Number(match[1]),
    Number(match[2]) - 1,
    Number(match[3]),
    Number(match[4] || 0),
    Number(match[5] || 0),
  )

  return Number.isNaN(date.getTime()) ? null : date
}

function pad(value) {
  return String(value).padStart(2, '0')
}

function formatModelValue(value) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    return ''
  }

  const date = `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`
  const time = `${pad(value.getHours())}:${pad(value.getMinutes())}`

  if (props.type === 'time') {
    return time
  }

  return props.type === 'datetime-local' ? `${date}T${time}` : date
}

const dateValue = computed({
  get: () => parseModelValue(props.modelValue),
  set: (value) => {
    const formattedValue = formatModelValue(value)
    emit('update:modelValue', formattedValue)
    emit('change', formattedValue)
  },
})

const minDate = computed(() => parseModelValue(props.min))
const maxDate = computed(() => parseModelValue(props.max))

function clearSelection() {
  if (props.disabled) {
    return
  }

  emit('update:modelValue', '')
  emit('change', '')
}

function syncDarkClass() {
  hasDarkClass.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  syncDarkClass()
  darkModeObserver = new MutationObserver(syncDarkClass)
  darkModeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  })
})

onBeforeUnmount(() => darkModeObserver?.disconnect())
</script>

<template>
  <div class="ds-field-group">
    <label v-if="label" :for="controlId" class="ds-field-label">
      {{ label }}
      <span v-if="required" class="ds-field-required" aria-hidden="true">*</span>
    </label>

    <VDatePicker
      v-model="dateValue"
      :mode="pickerMode"
      :locale="locale"
      :is-dark="hasDarkClass"
      :is-required="required"
      :min-date="minDate"
      :max-date="maxDate"
      :disabled="disabled"
      :is24hr="true"
      :time-accuracy="2"
      :masks="{ input: inputMask }"
      :popover="{ visibility: 'focus', placement: 'bottom-start' }"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="relative">
          <component
            :is="pickerIcon"
            class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-[var(--ds-text-soft)]"
            aria-hidden="true"
          />
          <input
            v-bind="attrs"
            :id="controlId"
            :value="inputValue"
            :placeholder="resolvedPlaceholder"
            :disabled="disabled"
            :required="required"
            :aria-invalid="Boolean(error)"
            :aria-describedby="describedBy"
            autocomplete="off"
            class="ds-field pl-10 pr-10 tabular-nums"
            v-on="inputEvents"
          />
          <button
            v-if="clearable && modelValue && !disabled"
            type="button"
            class="ds-date-time-clear"
            title="Limpar valor"
            @click.stop="clearSelection"
          >
            <XMarkIcon class="h-4 w-4" aria-hidden="true" />
            <span class="sr-only">Limpar valor</span>
          </button>
        </div>
      </template>
    </VDatePicker>

    <p v-if="hint && !error" :id="hintId" class="ds-field-hint">{{ hint }}</p>
    <p v-if="error" :id="errorId" class="ds-field-error" role="alert">{{ error }}</p>
  </div>
</template>
