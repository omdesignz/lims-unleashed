<script setup>
defineProps({
  id: {
    type: String,
    required: true,
  },
  modelValue: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: "",
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <label class="group flex cursor-pointer flex-row-reverse items-center justify-between gap-4 px-4 py-3" :for="id">
    <input
      :id="id"
      :checked="modelValue"
      :disabled="disabled"
      type="checkbox"
      class="peer sr-only"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span
      class="relative h-6 w-10 shrink-0 rounded-full bg-[var(--ds-border-strong)] transition peer-checked:bg-[rgb(var(--primary-700-rgb))] peer-focus-visible:ring-4 peer-focus-visible:ring-[var(--ds-focus)] peer-disabled:cursor-not-allowed peer-disabled:opacity-50 before:absolute before:left-1 before:top-1 before:h-4 before:w-4 before:rounded-full before:bg-[var(--ds-panel-raised)] before:shadow before:transition-transform before:content-[''] peer-checked:before:translate-x-4 dark:peer-checked:bg-[rgb(var(--primary-400-rgb))]"
      aria-hidden="true"
    />
    <span class="min-w-0">
      <span class="block text-sm font-bold text-[var(--ds-text)]">{{ label }}</span>
      <span v-if="description" class="mt-0.5 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ description }}</span>
    </span>
  </label>
</template>
