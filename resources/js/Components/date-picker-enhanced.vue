<template>
  <div class="">
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
      :input-debounce="600"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="space-y-2">
          <label v-if="props.label" class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
          </label>
          
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" />
                </div>
                <input
                  v-maska="'####-##-##'"
                  :value="inputValue.start"
                  v-on="inputEvents.start"
                  @input="validateManualInput($event.target.value, 'start')"
                  :disabled="props.disabled"
                  :placeholder="$t('gestlab.general.labels.date_placeholder')"
                  :class="inputClasses(props.hasError || props.startError || manualErrors.start)"
                />
              </div>
              <p v-if="manualErrors.start" class="mt-1 text-xs font-semibold text-red-500">{{ $t('gestlab.general.labels.invalid_date') }}</p>
              <p v-else-if="props.startError" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ props.startError }}</p>
            </div>

            <ArrowLongRightIcon class="hidden h-5 w-5 text-[#8d9b94] dark:text-[#657970] sm:block" />

            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" />
                </div>
                <input
                  v-maska="'####-##-##'"
                  :value="inputValue.end"
                  v-on="inputEvents.end"
                  @input="validateManualInput($event.target.value, 'end')"
                  :disabled="props.disabled"
                  :placeholder="$t('gestlab.general.labels.date_placeholder')"
                  :class="inputClasses(props.hasError || props.endError || manualErrors.end)"
                />
                <button
                  v-if="props.showClear && (inputValue.start || inputValue.end)"
                  type="button"
                  @click.stop="clearSelection"
                  class="absolute inset-y-0 right-0 flex items-center pr-3 text-[#8d9b94] transition-colors duration-200 hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#657970] dark:hover:text-[rgb(var(--primary-200-rgb))]"
                >
                  <XMarkIcon class="h-5 w-5" />
                </button>
              </div>
              <p v-if="manualErrors.end" class="mt-1 text-xs font-semibold text-red-500">{{ $t('gestlab.general.labels.invalid_date') }}</p>
              <p v-else-if="props.endError" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ props.endError }}</p>
            </div>
          </div>
        </div>
      </template>
    </VDatePicker>

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
      :input-debounce="600"
    >
      <template #default="{ inputValue, inputEvents }">
        <div class="space-y-2">
          <label v-if="props.label" class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
          </label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" />
            </div>
            <input
              v-maska="'####-##-##'"
              :value="inputValue"
              v-on="inputEvents"
              @input="validateManualInput($event.target.value, 'single')"
              :disabled="props.disabled"
              :placeholder="$t('gestlab.general.labels.date_placeholder')"
              :class="inputClasses(props.hasError || manualErrors.single)"
            />
            <button
              v-if="props.showClear && inputValue"
              type="button"
              @click.stop="clearSelection"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-[#8d9b94] transition-colors duration-200 hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#657970] dark:hover:text-[rgb(var(--primary-200-rgb))]"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
          <p v-if="manualErrors.single" class="mt-1 text-xs font-semibold text-red-500">{{ $t('gestlab.general.labels.invalid_date') }}</p>
          <p v-else-if="props.hasError" class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ props.errorMessage }}</p>
        </div>
      </template>
    </VDatePicker>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { DatePicker as VDatePicker } from 'v-calendar';
import { vMaska } from "maska/vue"
import 'v-calendar/dist/style.css';
import { CalendarIcon, ArrowLongRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: [String, Date, Object, Array],
  label: String,
  range: Boolean,
  required: Boolean,
  disabled: Boolean,
  hasError: Boolean,
  errorMessage: String,
  startError: String,
  endError: String,
  showClear: { type: Boolean, default: true },
  locale: { type: String, default: 'pt-PT' },
  color: { type: String, default: 'primary' },
  isDark: Boolean,
  masks: { type: Object, default: () => ({}) },
  minDate: [Date, String],
  maxDate: [Date, String]
});

const emit = defineEmits(["update:modelValue"]);

const manualErrors = reactive({ start: false, end: false, single: false });

const dateMasks = computed(() => ({
  input: 'YYYY-MM-DD',
  modelValue: 'YYYY-MM-DD',
  ...props.masks
}));

const modelConfig = { type: 'string', mask: 'YYYY-MM-DD' };

// --- FIX: Logic to handle dates locally without UTC shifts ---

const isValidDate = (dateStr) => {
  if (!dateStr || dateStr.length < 10) return true; 
  const [year, month, day] = dateStr.split('-').map(Number);
  const date = new Date(year, month - 1, day); // Creates local date
  return (
    date.getFullYear() === year &&
    date.getMonth() === month - 1 &&
    date.getDate() === day
  );
};

const formatSingleDate = (val) => {
  if (!val) return null;
  
  // If it's already a correct string, don't touch it (avoids UTC parsing trap)
  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
    return val;
  }

  const d = new Date(val);
  if (isNaN(d.getTime())) return null;
  
  // Use LOCAL getters instead of toISOString()
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const validateManualInput = (val, type) => {
  if (!val || val.length < 10) {
    manualErrors[type] = false;
    return;
  }
  manualErrors[type] = !isValidDate(val);
};

const dateValue = computed({
  get() { return props.modelValue; },
  set(value) {
    if (props.range) {
      const start = value?.start ? formatSingleDate(value.start) : null;
      const end = value?.end ? formatSingleDate(value.end) : null;
      emit("update:modelValue", { start, end });
    } else {
      emit("update:modelValue", value ? formatSingleDate(value) : null);
    }
  }
});

const inputClasses = (error) => [
  'block w-full rounded-2xl border py-3 pl-10 pr-10 text-sm font-medium shadow-sm ring-1 transition-all duration-200 placeholder:text-[#8d9b94] focus:outline-none focus:ring-2 focus:ring-offset-0 dark:placeholder:text-[#657970]',
  props.disabled ? 'cursor-not-allowed border-[#ddd2bf] bg-[#ede5d6] text-slate-500 ring-[#ddd2bf] dark:border-[#25443c] dark:bg-[#10231f]/70 dark:text-slate-400 dark:ring-[#25443c]' : 'border-[#d8cbb8] bg-[#fffdf7] text-[#15231f] ring-white/70 dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:ring-white/10',
  error ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/20 dark:border-danger-500' : 'focus:border-[rgb(var(--primary-500-rgb))] focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:focus:border-[rgb(var(--primary-300-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb)/0.2)]'
];

function clearSelection() {
  if (props.disabled) return;
  manualErrors.start = false;
  manualErrors.end = false;
  manualErrors.single = false;
  emit("update:modelValue", props.range ? { start: null, end: null } : null);
}
</script>

<style>
.vc-container {
  border: 1px solid #ded3bf !important;
  border-radius: 1.5rem !important;
  background: rgb(255 253 247 / 0.98) !important;
  box-shadow: 0 28px 90px rgb(7 17 15 / 0.18) !important;
  color: #15231f !important;
  font-family: "Manrope", sans-serif !important;
  overflow: hidden;
}

.vc-popover-content-wrapper {
  z-index: 9999 !important;
}

.vc-header,
.vc-weekdays,
.vc-weeks {
  padding-inline: 0.75rem !important;
}

.vc-title,
.vc-weekday {
  color: #31413b !important;
  font-weight: 800 !important;
}

.vc-arrow {
  border-radius: 9999px !important;
  color: #31413b !important;
  transition: background-color 150ms ease, color 150ms ease;
}

.vc-arrow:hover,
.vc-title:hover {
  background: #eef7f3 !important;
  color: rgb(var(--primary-800-rgb)) !important;
}

.vc-day-content {
  border-radius: 0.9rem !important;
  font-weight: 700 !important;
}

.vc-day-content:hover {
  background: rgb(var(--primary-100-rgb)) !important;
  color: rgb(var(--primary-900-rgb)) !important;
}

.vc-highlight,
.vc-highlight-bg-solid {
  background: rgb(var(--primary-800-rgb)) !important;
}

.vc-highlight-content-solid,
.vc-highlight-content-light {
  color: #fffdf7 !important;
}

.dark .vc-container {
  border-color: #25443c !important;
  background: rgb(7 17 15 / 0.98) !important;
  box-shadow: 0 28px 90px rgb(0 0 0 / 0.44) !important;
  color: #f7f1e7 !important;
}

.dark .vc-title,
.dark .vc-weekday,
.dark .vc-arrow {
  color: #d7e2dd !important;
}

.dark .vc-arrow:hover,
.dark .vc-title:hover,
.dark .vc-day-content:hover {
  background: #10231f !important;
  color: #f1d78b !important;
}
</style>
