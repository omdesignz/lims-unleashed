<template>
  <section class="ds-panel relative p-5 sm:p-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
      <div class="max-w-4xl">
        <p v-if="eyebrow" class="ds-kicker">
          {{ eyebrow }}
        </p>
        <h1 class="mt-2 text-2xl font-medium tracking-[-0.03em] text-[var(--ds-text)] sm:text-[1.75rem] sm:leading-tight">
          {{ title }}
        </h1>
        <p v-if="description" class="ds-copy mt-2 max-w-3xl text-sm">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
    <div v-if="$slots.default" class="mt-6">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
})
</script>
