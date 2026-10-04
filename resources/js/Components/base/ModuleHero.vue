<template>
  <PageHeader :trail="eyebrow ? [{ title: eyebrow }, { title }] : []" :title="title" :lede="description">
    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>
    <slot />
  </PageHeader>
</template>

<script setup>
import PageHeader from '@/Components/plano/PageHeader.vue'

/**
 * The header of a module screen. It is the Plano page header: `eyebrow` names
 * the section in the path line, and anything in the default slot (tabs, state
 * cells) follows the title.
 */
defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
})
</script>
