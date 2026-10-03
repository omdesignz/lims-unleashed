<template>
  <span class="tabular-nums">{{ display }}</span>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { animate } from 'motion-v'
import { shouldSkipEntrance } from '@/Support/motion'

const props = defineProps({
  value: {
    type: Number,
    default: 0,
  },
  format: {
    type: Function,
    default: (value) => String(value),
  },
  duration: {
    type: Number,
    default: 0.7,
  },
})

const display = ref(props.format(props.value))
let controls = null

function run(from, to) {
  controls?.stop()

  if (from === to || shouldSkipEntrance()) {
    display.value = props.format(to)

    return
  }

  controls = animate(from, to, {
    duration: props.duration,
    ease: [0.23, 1, 0.32, 1],
    onUpdate: (latest) => { display.value = props.format(Math.round(latest)) },
  })
}

onMounted(() => run(0, props.value))
watch(() => props.value, (to, from) => run(from ?? 0, to))
onBeforeUnmount(() => controls?.stop())
</script>
