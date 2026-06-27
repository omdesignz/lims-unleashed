<template>
  <section class="ds-card relative overflow-hidden p-5 transition sm:p-6">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top_right,rgb(var(--primary-300-rgb)/0.10),transparent_38%)]" />
    <div class="relative">
    <div v-if="title || description || $slots.actions" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 v-if="title" class="ds-heading text-lg">{{ title }}</h2>
        <p v-if="description" class="ds-copy mt-1 text-sm">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
    <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
})
</script>
