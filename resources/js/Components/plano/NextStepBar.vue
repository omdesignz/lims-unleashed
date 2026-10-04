<script setup>
/**
 * The fixed next-step bar of dossiers and forms: what is left to do, what blocks
 * it, and the single primary action (with its shortcut) on the right.
 */
defineProps({
  label: { type: String, default: 'Próximo passo' },
})
</script>

<template>
  <div class="pl-nextbar" role="region" :aria-label="label">
    <span class="pl-k pl-prompt pl-nextbar-label">{{ label }}</span>
    <p class="pl-nextbar-text"><slot /></p>
    <div v-if="$slots.actions" class="pl-nextbar-actions"><slot name="actions" /></div>
  </div>
</template>
