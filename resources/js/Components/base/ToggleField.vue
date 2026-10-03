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
      class="pl-switch relative h-5 w-9 shrink-0 border border-[var(--pl-muted)] transition-colors peer-checked:border-[var(--pl-accent)] peer-checked:bg-[var(--pl-accent)] peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[var(--pl-accent-text)] peer-disabled:cursor-not-allowed peer-disabled:opacity-50 before:absolute before:left-[2px] before:top-[2px] before:h-3.5 before:w-3.5 before:bg-[var(--pl-muted)] before:transition-transform before:content-[''] peer-checked:before:translate-x-4 peer-checked:before:bg-[var(--pl-accent-ink)]"
      aria-hidden="true"
    />
    <span class="min-w-0">
      <span class="block text-sm font-bold text-[var(--ds-text)]">{{ label }}</span>
      <span v-if="description" class="mt-0.5 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ description }}</span>
    </span>
  </label>
</template>
