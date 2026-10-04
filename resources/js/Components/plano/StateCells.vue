<script setup>
import { nextTick, onMounted, ref, useId, watch } from 'vue'
import { motion } from 'motion-v'
import { springPlane } from '@/Support/motion'

/**
 * Segmented state cells: each one is a count that filters the view. The chosen
 * cell is the accent plane, which slides to its new position.
 */
const props = defineProps({
  items: { type: Array, required: true },
  modelValue: { type: [String, Number, null], default: null },
  label: { type: String, default: 'Filtrar por estado' },
})

const emit = defineEmits(['update:modelValue'])
const planeId = `pl-cell-plane-${useId()}`
const pad = (value) => String(value ?? 0)

// On phones the cells scroll as one strip: keep the chosen one in view.
const strip = ref(null)

function revealChosen() {
  const group = strip.value
  const chosen = group?.querySelector('[aria-pressed="true"]')

  if (group && chosen && group.scrollWidth > group.clientWidth) {
    group.scrollLeft += chosen.getBoundingClientRect().left - group.getBoundingClientRect().left - 16
  }
}

onMounted(revealChosen)
watch(() => props.modelValue, () => nextTick(revealChosen))
</script>

<template>
  <div ref="strip" class="pl-cells" role="group" :aria-label="props.label">
    <button
      v-for="item in props.items"
      :key="item.key"
      type="button"
      class="pl-cell relative"
      :class="{ 'pl-cell-bad': item.tone === 'bad' }"
      :aria-pressed="props.modelValue === item.key"
      @click="emit('update:modelValue', item.key)"
    >
      <motion.span v-if="props.modelValue === item.key" :layout-id="planeId" class="absolute inset-0 bg-[var(--pl-accent)]" :transition="springPlane" aria-hidden="true" />
      <span class="pl-k relative">{{ item.label }}</span>
      <span class="pl-cell-value relative">{{ pad(item.value) }}</span>
    </button>
  </div>
</template>
