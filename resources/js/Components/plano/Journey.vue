<script setup>
/**
 * The sample's journey on a dossier: each stage shows who acted and when. The
 * current stage is the accent plane; stages still to come are striped.
 */
defineProps({
  steps: { type: Array, required: true },
  label: { type: String, default: 'Percurso da amostra' },
})

const number = (index) => String(index + 1).padStart(2, '0')
</script>

<template>
  <ol class="pl-journey" :aria-label="label">
    <li v-for="(step, index) in steps" :key="step.label" class="pl-step" :data-state="step.state" :aria-current="step.state === 'current' ? 'step' : undefined">
      <span class="pl-k pl-muted">{{ number(index) }} {{ step.label }}</span>
      <span class="pl-step-title">{{ step.title || '—' }}</span>
      <span class="pl-k pl-faint">{{ step.note || '—' }}</span>
    </li>
  </ol>
</template>
