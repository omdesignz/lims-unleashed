<template>
  <span class="brand-mark" :style="{ width: `${width}px` }">
    <img class="brand-mark-light" :src="`/brand/svg/${source}.svg`" :alt="alt" :width="width" :height="height" />
    <img class="brand-mark-dark" :src="`/brand/svg/${source}_White.svg`" :alt="alt" :width="width" :height="height" />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  width: {
    type: Number,
    default: 52,
  },
  alt: {
    type: String,
    default: 'VAP Sistemas',
  },
})

// The brand kit ships an optically refined geometry for rendered widths below 32px.
const source = computed(() => props.width < 32 ? 'VAP_Small' : 'VAP_Master')
const height = computed(() => Math.round(props.width * 385 / 910))
</script>

<style>
.brand-mark {
  display: inline-block;
  flex: 0 0 auto;
  line-height: 0;
}

.brand-mark img {
  display: block;
  width: 100%;
  height: auto;
}

.brand-mark .brand-mark-dark,
.dark .brand-mark .brand-mark-light {
  display: none;
}

.dark .brand-mark .brand-mark-dark {
  display: block;
}
</style>
