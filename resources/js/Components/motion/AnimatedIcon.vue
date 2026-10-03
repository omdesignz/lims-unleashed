<template>
  <component :is="props.icon" ref="root" aria-hidden="true" />
</template>

<script setup>
import { ref } from 'vue'
import { animate } from 'motion-v'
import { prefersReducedMotion } from '@/Support/motion'

/**
 * A Lucide icon with a short, purposeful gesture — played by the control that
 * owns it (on hover or activation), never on its own.
 */
const props = defineProps({
  icon: { type: [Object, Function], required: true },
  animation: { type: String, default: 'pop' },
})

const root = ref(null)
let controls = null

const gestures = {
  pop: [{ scale: [1, 1.14, 1] }, { duration: 0.32, ease: [0.23, 1, 0.32, 1] }],
  ring: [{ rotate: [0, -14, 11, -7, 4, 0] }, { duration: 0.6, ease: 'easeInOut' }],
  spin: [{ rotate: [0, 90] }, { duration: 0.45, ease: [0.23, 1, 0.32, 1] }],
  wobble: [{ rotate: [0, -10, 8, -4, 0] }, { duration: 0.5, ease: 'easeInOut' }],
  nudge: [{ x: [0, 3, 0] }, { duration: 0.32, ease: [0.23, 1, 0.32, 1] }],
  lift: [{ y: [0, -2.5, 0] }, { duration: 0.34, ease: [0.23, 1, 0.32, 1] }],
}

function play() {
  const element = root.value?.$el ?? root.value

  if (!element || prefersReducedMotion()) {
    return
  }

  controls?.stop()

  if (props.animation === 'draw') {
    const strokes = element.querySelectorAll('path, line, polyline, circle, rect')
    controls = animate(strokes, { pathLength: [0, 1], opacity: [0.4, 1] }, { duration: 0.5, ease: [0.4, 0, 0.2, 1] })

    return
  }

  const [keyframes, options] = gestures[props.animation] ?? gestures.pop
  element.style.transformOrigin = props.animation === 'ring' ? '50% 10%' : '50% 50%'
  controls = animate(element, keyframes, options)
}

defineExpose({ play })
</script>
