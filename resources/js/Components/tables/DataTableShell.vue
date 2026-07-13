<template>
  <section class="ds-table-shell">
    <div v-if="showSummary" class="ds-table-summary px-5 py-4 sm:px-7">
      <slot name="summary" />
    </div>

    <slot />

    <div v-if="$slots.pagination" class="border-t border-[var(--ds-border)] px-5 py-4 sm:px-7">
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
