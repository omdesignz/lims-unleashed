<template>
  <section class="pl-panel">
    <header v-if="title || description || $slots.actions" class="pl-panel-head h-auto min-h-11 flex-wrap py-2.5">
      <div class="min-w-0">
        <h2 v-if="title" class="pl-k">{{ title }}</h2>
        <p v-if="description" class="mt-1 text-[13px] text-[var(--pl-muted)]">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </header>
    <div class="p-4">
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
