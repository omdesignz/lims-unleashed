<template>
  <section class="ds-panel relative isolate overflow-hidden p-6 sm:p-8">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-300-rgb)/0.16),transparent_36%),radial-gradient(circle_at_bottom_right,rgb(var(--accent-300-rgb)/0.10),transparent_42%)]" />
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
      <div class="max-w-4xl">
        <p v-if="eyebrow" class="ds-kicker">
          {{ eyebrow }}
        </p>
        <h1 class="ds-heading mt-3 text-3xl sm:text-4xl">
          {{ title }}
        </h1>
        <p v-if="description" class="ds-copy mt-4 max-w-3xl text-sm">
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
