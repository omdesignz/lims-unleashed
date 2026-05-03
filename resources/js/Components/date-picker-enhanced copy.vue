<template>
  <div class=""> 
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
      :popover="{ visibility: 'click', placement: 'bottom-start' }"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="space-y-2">
          <!-- Label -->
          <label 
            v-if="props.label" 
            class="block text-sm font-medium text-gray-700"
          >
            {{ props.label }}
            <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
          </label>
          
          <!-- Date Range Inputs -->
          <div class="flex items-center gap-3">
            <!-- Start Date Input -->
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  :value="inputValue.start"
                  v-on="inputEvents.start"
                  :disabled="props.disabled"
                  :placeholder="props.startPlaceholder || $t('gestlab.general.calendar_input_start_placeholder')"
                  :class="[
                    'block w-full rounded-lg border py-2.5 pl-10 pr-3 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
                    props.disabled 
                      ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' 
                      : 'bg-white text-gray-900 placeholder:text-gray-500',
                    props.hasError || props.startError
                      ? 'border-red-300 focus:border-red-300 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  :aria-invalid="props.hasError || !!props.startError"
                  :aria-describedby="(props.hasError || props.startError) ? `${props.id}-error-start` : undefined"
                  readonly
                />
              </div>
              <p v-if="props.startError" :id="`${props.id}-error-start`" class="mt-1 text-xs text-red-600">
                {{ props.startError }}
              </p>
            </div>

            <!-- Separator -->
            <div class="flex-shrink-0">
              <ArrowLongRightIcon class="h-5 w-5 text-gray-400" />
            </div>

            <!-- End Date Input -->
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  :value="inputValue.end"
                  v-on="inputEvents.end"
                  :disabled="props.disabled"
                  :placeholder="props.endPlaceholder || $t('gestlab.general.calendar_input_end_placeholder')"
                  :class="[
                    'block w-full rounded-lg border py-2.5 pl-10 pr-10 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
                    props.disabled 
                      ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' 
                      : 'bg-white text-gray-900 placeholder:text-gray-500',
                    props.hasError || props.endError
                      ? 'border-red-300 focus:border-red-300 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  :aria-invalid="props.hasError || !!props.endError"
                  :aria-describedby="(props.hasError || props.endError) ? `${props.id}-error-end` : undefined"
                  readonly
                />
                
                <!-- Clear Button -->
                <button
                  v-if="props.showClear && inputValue.end"
                  type="button"
                  @click.stop="clearSelection"
                  :disabled="props.disabled"
                  :class="[
                    'absolute inset-y-0 right-0 flex items-center pr-3',
                    props.disabled ? 'text-gray-400 cursor-not-allowed' : 'text-gray-400 hover:text-blue-900'
                  ]"
                  :title="$t('gestlab.general.buttons.clear')"
                  aria-label="Clear date range"
                >
                  <XMarkIcon class="h-5 w-5" />
                </button>
              </div>
              <p v-if="props.endError" :id="`${props.id}-error-end`" class="mt-1 text-xs text-red-600">
                {{ props.endError }}
              </p>
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
      :popover="{ visibility: 'click', placement: 'bottom-start' }"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="space-y-2">
          <!-- Label -->
          <label 
            v-if="props.label" 
            class="block text-sm font-medium text-gray-700"
          >
            {{ props.label }}
            <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
          </label>
          
          <!-- Single Date Input -->
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <CalendarIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              :value="inputValue"
              v-on="inputEvents"
              :disabled="props.disabled"
              :placeholder="props.placeholder || $t('gestlab.general.calendar_input_placeholder')"
              :class="[
                'block w-full rounded-lg border py-2.5 pl-10 pr-10 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
                props.disabled 
                  ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' 
                  : 'bg-white text-gray-900 placeholder:text-gray-500',
                props.hasError
                  ? 'border-red-300 focus:border-red-300 focus:ring-red-500/20' 
                  : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
              ]"
              :aria-invalid="props.hasError"
              :aria-describedby="props.hasError ? `${props.id}-error` : undefined"
              readonly
            />
            
            <!-- Clear Button -->
            <button
              v-if="props.showClear && inputValue"
              type="button"
              @click.stop="clearSelection"
              :disabled="props.disabled"
              :class="[
                'absolute inset-y-0 right-0 flex items-center pr-3',
                props.disabled ? 'text-gray-400 cursor-not-allowed' : 'text-gray-400 hover:text-blue-900'
              ]"
              :title="$t('gestlab.general.buttons.clear')"
              aria-label="Clear date"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
          
          <!-- Error Message -->
          <p v-if="props.hasError" :id="`${props.id}-error`" class="mt-1 text-xs text-red-600">
            {{ props.errorMessage }}
          </p>
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
    default: 'pt-PT'
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
    if (typeof date === 'string') {
      try {
        const parsedDate = new Date(date);
        if (!isNaN(parsedDate.getTime())) {
          return formatRangeDate({ start: parsedDate, end: parsedDate });
        }
      } catch (e) {
        console.warn('Could not parse date string:', date);
      }
      return { start: null, end: null };
    }
    
    if (date.start || date.end) {
      return formatRangeDate(date);
    }
    
    return { start: null, end: null };
  } else {
    if (typeof date === 'string') {
      if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
      }
      
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
      if (/^\d{4}-\d{2}-\d{2}$/.test(range.start)) {
        result.start = range.start;
      } else {
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
      if (/^\d{4}-\d{2}-\d{2}$/.test(range.end)) {
        result.end = range.end;
      } else {
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
    if (props.range) {
      const formattedValue = {
        start: value?.start ? formatSingleDate(new Date(value.start)) : null,
        end: value?.end ? formatSingleDate(new Date(value.end)) : null,
      };
      emit("update:modelValue", formattedValue);
    } else {
      const formattedValue = value ? formatSingleDate(new Date(value)) : null;
      emit("update:modelValue", formattedValue);
    }
  }
});

function clearSelection() {
  if (props.disabled) return;
  
  if (props.range) {
    dateValue.value = { start: null, end: null };
  } else {
    dateValue.value = null;
  }
}

// Watch for external changes to ensure format consistency
watch(() => props.modelValue, (newValue) => {
  const formatted = formatDateToYYYYMMDD(newValue);
  
  if (JSON.stringify(formatted) !== JSON.stringify(dateValue.value)) {
    if (props.range) {
      dateValue.value = formatted;
    } else {
      dateValue.value = formatted;
    }
  }
}, { immediate: true });
</script>

<style scoped>
/* Custom calendar popover styling */
:deep(.vc-popover-content) {
  border-radius: 0.75rem !important;
  border: 1px solid #e5e7eb !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}

/* Calendar navigation buttons */
:deep(.vc-arrow),
:deep(.vc-title) {
  color: #1e3a8a !important;
  font-weight: 600 !important;
}

:deep(.vc-arrow:hover) {
  background-color: #f3f4f6 !important;
}

/* Selected dates */
:deep(.vc-highlight-bg-solid) {
  background-color: #1e3a8a !important;
}

/* Today's date */
:deep(.vc-day.is-today) {
  color: #1e3a8a !important;
  font-weight: 600 !important;
}

/* Hover states */
:deep(.vc-day:hover:not(.is-disabled)) {
  background-color: #f3f4f6 !important;
}

/* Disabled dates */
:deep(.vc-day.is-disabled) {
  color: #9ca3af !important;
  cursor: not-allowed !important;
}

/* Focus states for calendar days */
:deep(.vc-day:focus) {
  outline: 2px solid #1e3a8a !important;
  outline-offset: 2px !important;
}
</style>