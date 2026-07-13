<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
})
</script>

<template>
  <section class="scroll-mt-24 px-5 py-8 sm:px-7 lg:px-8 lg:py-10">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,14rem)_minmax(0,1fr)] lg:gap-10">
      <div>
        <div class="flex items-center gap-2.5">
          <span v-if="$slots.icon" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] ring-1 ring-inset ring-[var(--ds-border)] dark:text-[rgb(var(--accent-200-rgb))]">
            <slot name="icon" />
          </span>
          <h2 class="ds-heading text-base">{{ title }}</h2>
        </div>
        <p v-if="description" class="ds-copy mt-2 text-sm lg:pr-3">{{ description }}</p>
      </div>

      <div class="min-w-0">
        <slot />
      </div>
    </div>
  </section>
</template>
