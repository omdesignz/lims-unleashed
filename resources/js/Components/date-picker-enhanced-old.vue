<template>
  <div class="w-full">
    <!-- Range Date Picker -->
    <VDatePicker
      v-if="props.range"
      v-model="dateValue"
      :is-range="true"
      :locale="props.locale"
      :color="props.color"
      :is-dark="props.isDark"
      :disabled="props.disabled"
      :min-date="props.minDate"
      :max-date="props.maxDate"
      :masks="dateMasks"
      :model-config="modelConfig"
      :popover="{ visibility: 'click' }"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="space-y-1">
          <label 
            v-if="props.label" 
            class="block text-sm font-medium leading-6 text-gray-900 mb-1"
          >
            {{ props.label }}
            <span v-if="props.required" class="text-red-500">*</span>
          </label>
          
          <div class="flex items-center gap-2">
            <!-- Start Date Input -->
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
                </div>
                <input
                  :value="inputValue.start"
                  v-on="inputEvents.start"
                  :disabled="props.disabled"
                  :placeholder="props.startPlaceholder || $t('gestlab.general.calendar_input_start_placeholder')"
                  :class="[
                    'block w-full rounded-md border-0 py-1.5 pl-10 pr-3 text-gray-900 shadow-sm ring-1 ring-inset sm:text-sm sm:leading-6',
                    props.disabled 
                      ? 'bg-gray-50 text-gray-500 cursor-not-allowed' 
                      : 'bg-white',
                    props.hasError 
                      ? 'ring-red-300 placeholder:text-red-300 focus:ring-2 focus:ring-inset focus:ring-red-500' 
                      : 'ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900'
                  ]"
                  :aria-invalid="props.hasError"
                  :aria-describedby="props.hasError ? `${props.id}-error` : undefined"
                  readonly
                />
              </div>
              <p v-if="props.startError" class="mt-1 text-xs text-red-600">
                {{ props.startError }}
              </p>
            </div>

            <!-- Separator -->
            <div class="flex-shrink-0">
              <ArrowLongRightIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
            </div>

            <!-- End Date Input -->
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
                </div>
                <input
                  :value="inputValue.end"
                  v-on="inputEvents.end"
                  :disabled="props.disabled"
                  :placeholder="props.endPlaceholder || $t('gestlab.general.calendar_input_end_placeholder')"
                  :class="[
                    'block w-full rounded-md border-0 py-1.5 pl-10 pr-3 text-gray-900 shadow-sm ring-1 ring-inset sm:text-sm sm:leading-6',
                    props.disabled 
                      ? 'bg-gray-50 text-gray-500 cursor-not-allowed' 
                      : 'bg-white',
                    props.hasError 
                      ? 'ring-red-300 placeholder:text-red-300 focus:ring-2 focus:ring-inset focus:ring-red-500' 
                      : 'ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900'
                  ]"
                  :aria-invalid="props.hasError"
                  :aria-describedby="props.hasError ? `${props.id}-error` : undefined"
                  readonly
                />
              </div>
              <p v-if="props.endError" class="mt-1 text-xs text-red-600">
                {{ props.endError }}
              </p>
            </div>

            <!-- Clear Button -->
            <div v-if="props.showClear && dateValue && ((props.range && (dateValue.start || dateValue.end)) || (!props.range && dateValue))" class="flex-shrink-0">
              <button
                type="button"
                @click.stop="clearSelection"
                :disabled="props.disabled"
                :class="[
                  'inline-flex items-center justify-center p-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
                  props.disabled
                    ? 'text-gray-400 cursor-not-allowed'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:ring-blue-900'
                ]"
                :title="$t('gestlab.general.buttons.clear')"
                aria-label="Clear date selection"
              >
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- General Error Message -->
          <p v-if="props.hasError && !props.startError && !props.endError" class="mt-1 text-xs text-red-600">
            {{ props.errorMessage }}
          </p>
        </div>
      </template>
    </VDatePicker>

    <!-- Single Date Picker -->
    <VDatePicker
      v-else
      v-model="dateValue"
      :locale="props.locale"
      :color="props.color"
      :is-dark="props.isDark"
      :disabled="props.disabled"
      :min-date="props.minDate"
      :max-date="props.maxDate"
      :masks="dateMasks"
      :model-config="modelConfig"
      :popover="{ visibility: 'click' }"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="space-y-1">
          <label 
            v-if="props.label" 
            class="block text-sm font-medium leading-6 text-gray-900 mb-1"
          >
            {{ props.label }}
            <span v-if="props.required" class="text-red-500">*</span>
          </label>
          
          <div class="flex items-center gap-2">
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
                </div>
                <input
                  :value="inputValue"
                  v-on="inputEvents"
                  :disabled="props.disabled"
                  :placeholder="props.placeholder || $t('gestlab.general.calendar_input_placeholder')"
                  :class="[
                    'block w-full rounded-md border-0 py-1.5 pl-10 pr-10 text-gray-900 shadow-sm ring-1 ring-inset sm:text-sm sm:leading-6',
                    props.disabled 
                      ? 'bg-gray-50 text-gray-500 cursor-not-allowed' 
                      : 'bg-white',
                    props.hasError 
                      ? 'ring-red-300 placeholder:text-red-300 focus:ring-2 focus:ring-inset focus:ring-red-500' 
                      : 'ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900'
                  ]"
                  :aria-invalid="props.hasError"
                  :aria-describedby="props.hasError ? `${props.id}-error` : undefined"
                  readonly
                />
                
                <!-- Clear Button for single date -->
                <button
                  v-if="props.showClear && dateValue"
                  type="button"
                  @click.stop="clearSelection"
                  :disabled="props.disabled"
                  class="absolute inset-y-0 right-0 flex items-center pr-3"
                  :class="props.disabled ? 'text-gray-400' : 'text-gray-400 hover:text-gray-600'"
                  :title="$t('gestlab.general.buttons.clear')"
                  aria-label="Clear date"
                >
                  <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                </button>
              </div>
              
              <p v-if="props.hasError" class="mt-1 text-xs text-red-600">
                {{ props.errorMessage }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </VDatePicker>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { DatePicker as VDatePicker } from 'v-calendar';
import 'v-calendar/dist/style.css';
import { CalendarIcon, ArrowLongRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: [String, Date, Object, Array],
  label: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  startPlaceholder: {
    type: String,
    default: ''
  },
  endPlaceholder: {
    type: String,
    default: ''
  },
  range: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  hasError: {
    type: Boolean,
    default: false
  },
  errorMessage: {
    type: String,
    default: ''
  },
  startError: {
    type: String,
    default: ''
  },
  endError: {
    type: String,
    default: ''
  },
  showClear: {
    type: Boolean,
    default: true
  },
  // V-Calendar specific props
  locale: {
    type: String,
    default: 'pt'
  },
  color: {
    type: String,
    default: 'blue'
  },
  isDark: {
    type: Boolean,
    default: false
  },
  masks: {
    type: Object,
    default: () => ({})
  },
  minDate: {
    type: [Date, String],
    default: null
  },
  maxDate: {
    type: [Date, String],
    default: null
  }
});

const emit = defineEmits(["update:modelValue"]);

// Configure masks to always use YYYY-MM-DD format
const dateMasks = computed(() => {
  const defaultMasks = {
    input: 'YYYY-MM-DD', // Display format in input
    modelValue: 'YYYY-MM-DD', // Internal format
    data: 'YYYY-MM-DD', // Data format
  };
  
  // Merge with any custom masks provided
  return { ...defaultMasks, ...props.masks };
});

// Model config to ensure YYYY-MM-DD format
const modelConfig = {
  type: 'string',
  mask: 'YYYY-MM-DD',
};

// Process date values to ensure YYYY-MM-DD format
const formatDateToYYYYMMDD = (date) => {
  if (!date) return props.range ? { start: null, end: null } : null;
  
  if (props.range) {
    // Handle range date
    if (typeof date === 'string') {
      // If it's a string, assume it's already formatted or try to parse
      try {
        // Try to parse as ISO string or other format
        const parsedDate = new Date(date);
        if (!isNaN(parsedDate.getTime())) {
          return formatRangeDate({ start: parsedDate, end: parsedDate });
        }
      } catch (e) {
        console.warn('Could not parse date string:', date);
      }
      return { start: null, end: null };
    }
    
    // If it's an object with start/end properties
    if (date.start || date.end) {
      return formatRangeDate(date);
    }
    
    return { start: null, end: null };
  } else {
    // Handle single date
    if (typeof date === 'string') {
      // If already in YYYY-MM-DD format, return as-is
      if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
      }
      
      // Try to parse and format
      try {
        const parsedDate = new Date(date);
        if (!isNaN(parsedDate.getTime())) {
          return formatSingleDate(parsedDate);
        }
      } catch (e) {
        console.warn('Could not parse date string:', date);
      }
      return null;
    }
    
    if (date instanceof Date) {
      return formatSingleDate(date);
    }
    
    return null;
  }
};

const formatSingleDate = (date) => {
  if (!date || !(date instanceof Date) || isNaN(date.getTime())) return null;
  
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  
  return `${year}-${month}-${day}`;
};

const formatRangeDate = (range) => {
  const result = { start: null, end: null };
  
  if (range.start) {
    if (range.start instanceof Date) {
      result.start = formatSingleDate(range.start);
    } else if (typeof range.start === 'string' && range.start.trim() !== '') {
      // If it's already in YYYY-MM-DD format, use it directly
      if (/^\d{4}-\d{2}-\d{2}$/.test(range.start)) {
        result.start = range.start;
      } else {
        // Try to parse and format
        const parsedDate = new Date(range.start);
        if (!isNaN(parsedDate.getTime())) {
          result.start = formatSingleDate(parsedDate);
        }
      }
    }
  }
  
  if (range.end) {
    if (range.end instanceof Date) {
      result.end = formatSingleDate(range.end);
    } else if (typeof range.end === 'string' && range.end.trim() !== '') {
      // If it's already in YYYY-MM-DD format, use it directly
      if (/^\d{4}-\d{2}-\d{2}$/.test(range.end)) {
        result.end = range.end;
      } else {
        // Try to parse and format
        const parsedDate = new Date(range.end);
        if (!isNaN(parsedDate.getTime())) {
          result.end = formatSingleDate(parsedDate);
        }
      }
    }
  }
  
  return result;
};

// Create a computed property to handle date formatting
const dateValue = computed({
  get() {
    return formatDateToYYYYMMDD(props.modelValue);
  },
  set(value) {
    // v-calendar will already give us dates in the format specified by masks
    // but we ensure they're properly formatted before emitting
    if (props.range) {
      // For range dates
      const formattedValue = {
        start: value?.start ? formatSingleDate(new Date(value.start)) : null,
        end: value?.end ? formatSingleDate(new Date(value.end)) : null,
      };
      emit("update:modelValue", formattedValue);
    } else {
      // For single dates
      const formattedValue = value ? formatSingleDate(new Date(value)) : null;
      emit("update:modelValue", formattedValue);
    }
  }
});

function clearSelection() {
  if (props.disabled) return;
  
  if (props.range) {
    // For range, clear both dates
    dateValue.value = { start: null, end: null };
  } else {
    // For single date
    dateValue.value = null;
  }
}

// Watch for external changes to ensure format consistency
watch(() => props.modelValue, (newValue) => {
  // This ensures that if the parent component sends a date in a different format,
  // we normalize it to YYYY-MM-DD
  const formatted = formatDateToYYYYMMDD(newValue);
  
  // Only update if the formatted value is different from current
  if (JSON.stringify(formatted) !== JSON.stringify(dateValue.value)) {
    if (props.range) {
      dateValue.value = formatted;
    } else {
      dateValue.value = formatted;
    }
  }
}, { immediate: true });
</script>