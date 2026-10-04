<script setup>
import { computed } from 'vue'

/**
 * A status chip. Tone is information, never decoration:
 * run = in progress (accent), wait = waiting (amber), ok = conforming (green),
 * bad = out / blocked (red), done = closed (ink), neutral = not started.
 */
const props = defineProps({
  tone: { type: String, default: 'neutral' },
})

const toneClass = computed(() => ({
  run: 'pl-chip-run',
  wait: 'pl-chip-wait',
  ok: 'pl-chip-ok',
  bad: 'pl-chip-bad',
  done: 'pl-chip-done',
}[props.tone] ?? ''))
</script>

<template>
  <span class="pl-chip" :class="toneClass"><slot /></span>
</template>
