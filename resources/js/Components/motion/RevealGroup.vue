<template>
  <component :is="as" ref="root">
    <slot />
  </component>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { animate, stagger } from 'motion-v'
import { shouldSkipEntrance } from '@/Support/motion'

const props = defineProps({
  as: {
    type: String,
    default: 'div',
  },
  selector: {
    type: String,
    default: ':scope > *',
  },
  step: {
    type: Number,
    default: 0.05,
  },
})

const root = ref(null)

// A staged entrance for infrequent, first-look surfaces. Children rise in
// sequence; inline styles are cleared afterwards so nothing is left promoted.
onMounted(() => {
  const element = root.value?.$el ?? root.value
  const items = element ? Array.from(element.querySelectorAll(props.selector)) : []

  if (!items.length || shouldSkipEntrance()) {
    return
  }

  animate(
    items,
    { opacity: [0, 1], transform: ['translateY(8px)', 'translateY(0px)'] },
    { duration: 0.36, ease: [0.23, 1, 0.32, 1], delay: stagger(props.step) },
  ).then(() => {
    items.forEach((item) => {
      item.style.opacity = ''
      item.style.transform = ''
    })
  })
})
</script>
