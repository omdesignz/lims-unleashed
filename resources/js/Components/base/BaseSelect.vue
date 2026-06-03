<template>
  <div class="space-y-2">
    <label v-if="label" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
      {{ label }}
      <span v-if="required" class="ml-0.5 text-red-500">*</span>
    </label>
    <select
      v-bind="$attrs"
      :value="modelValue"
      :disabled="disabled"
      :class="[
        'block w-full rounded-[1.2rem] border px-4 py-3 text-sm font-medium shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
        disabled
          ? 'cursor-not-allowed border-[#ddd2bf] bg-[#ede5d6] text-slate-500 dark:border-[#25443c] dark:bg-[#10231f]/70 dark:text-slate-400'
          : 'border-[#d8cfbe] bg-[#fffdf7] text-[#15231f] focus:border-primary-500 focus:ring-primary-500/20 dark:border-[#25443c] dark:bg-[#0c1714]/90 dark:text-[#f7f1e7] dark:focus:border-primary-300 dark:focus:ring-primary-300/20',
        error ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20 dark:border-red-500' : '',
      ]"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <slot />
    </select>
    <p v-if="error" class="text-xs font-medium text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
defineOptions({
  inheritAttrs: false,
})

defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  error: {
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
})

defineEmits(['update:modelValue'])
</script>
