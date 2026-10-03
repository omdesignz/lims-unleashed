<template>
  <section class="ds-card relative p-5 sm:p-6">
    <div class="relative">
    <div v-if="title || description || $slots.actions" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 v-if="title" class="ds-heading text-base">{{ title }}</h2>
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
