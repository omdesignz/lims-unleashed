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
          <label v-if="props.label" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
          </label>
          
          <div class="flex items-center gap-3">
            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-5 w-5 text-primary-900/70 dark:text-primary-400" />
                </div>
                <input
                  v-maska="'####-##-##'"
                  :value="inputValue.start"
                  v-on="inputEvents.start"
                  @input="validateManualInput($event.target.value, 'start')"
                  :disabled="props.disabled"
                  placeholder="AAAA-MM-DD"
                  :class="inputClasses(props.hasError || props.startError || manualErrors.start)"
                />
              </div>
              <p v-if="manualErrors.start" class="mt-1 text-xs text-red-500 font-medium">Data inválida</p>
              <p v-else-if="props.startError" class="mt-1 text-xs text-red-600">{{ props.startError }}</p>
            </div>

            <ArrowLongRightIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" />

            <div class="flex-1">
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <CalendarIcon class="h-5 w-5 text-primary-900/70 dark:text-primary-400" />
                </div>
                <input
                  v-maska="'####-##-##'"
                  :value="inputValue.end"
                  v-on="inputEvents.end"
                  @input="validateManualInput($event.target.value, 'end')"
                  :disabled="props.disabled"
                  placeholder="AAAA-MM-DD"
                  :class="inputClasses(props.hasError || props.endError || manualErrors.end)"
                />
                <button
                  v-if="props.showClear && (inputValue.start || inputValue.end)"
                  type="button"
                  @click.stop="clearSelection"
                  class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-primary-900 dark:hover:text-primary-400 transition-colors duration-200"
                >
                  <XMarkIcon class="h-5 w-5" />
                </button>
              </div>
              <p v-if="manualErrors.end" class="mt-1 text-xs text-red-500 font-medium">Data inválida</p>
              <p v-else-if="props.endError" class="mt-1 text-xs text-red-600">{{ props.endError }}</p>
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
          <label v-if="props.label" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ props.label }}
            <span v-if="props.required" class="text-red-500 ml-0.5">*</span>
          </label>
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <CalendarIcon class="h-5 w-5 text-primary-900/70 dark:text-primary-400" />
            </div>
            <input
              v-maska="'####-##-##'"
              :value="inputValue"
              v-on="inputEvents"
              @input="validateManualInput($event.target.value, 'single')"
              :disabled="props.disabled"
              placeholder="AAAA-MM-DD"
              :class="inputClasses(props.hasError || manualErrors.single)"
            />
            <button
              v-if="props.showClear && inputValue"
              type="button"
              @click.stop="clearSelection"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-primary-900 dark:hover:text-primary-400 transition-colors duration-200"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
          <p v-if="manualErrors.single" class="mt-1 text-xs text-red-500 font-medium">Data inválida</p>
          <p v-else-if="props.hasError" class="mt-1 text-xs text-red-600">{{ props.errorMessage }}</p>
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
  'block w-full rounded-2xl border py-2.5 pl-10 pr-10 text-sm shadow-sm ring-1 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
  props.disabled ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-500 ring-slate-200 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400 dark:ring-slate-700' : 'border-slate-300/90 bg-white/95 text-slate-900 ring-slate-200/80 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800',
  error ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/20 dark:border-danger-500' : 'focus:border-primary-500 focus:ring-primary-500/20 dark:focus:border-primary-400 dark:focus:ring-primary-400/20'
];

function clearSelection() {
  if (props.disabled) return;
  manualErrors.start = false;
  manualErrors.end = false;
  manualErrors.single = false;
  emit("update:modelValue", props.range ? { start: null, end: null } : null);
}
</script>
