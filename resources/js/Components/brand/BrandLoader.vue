<template>
  <span class="inline-flex flex-col items-center gap-3" role="status">
    <img class="vap-loader brand-mark-light" src="/brand/motion/VAP_Loader.svg" alt="" :style="{ width: `${width}px` }" />
    <img class="vap-loader brand-mark-dark" src="/brand/motion/VAP_Loader_White.svg" alt="" :style="{ width: `${width}px` }" />
    <span :class="label ? 'text-sm text-[var(--ds-text-muted)]' : 'sr-only'">{{ label || 'A carregar…' }}</span>
  </span>
</template>

<script setup>
defineProps({
  width: {
    type: Number,
    default: 56,
  },
  label: {
    type: String,
    default: '',
  },
})
</script>
