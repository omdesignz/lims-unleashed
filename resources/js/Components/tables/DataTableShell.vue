<template>
  <section class="ds-table-shell">
    <div v-if="showSummary" class="ds-table-summary px-4 py-3.5 sm:px-5">
      <slot name="summary" />
    </div>

    <slot />

    <div v-if="$slots.pagination" class="border-t border-[var(--ds-border)] px-2 py-1 sm:px-3">
      <slot name="pagination" />
    </div>
  </section>
</template>

<script setup>
defineProps({
  showSummary: {
    type: Boolean,
    default: true,
  },
})
</script>
