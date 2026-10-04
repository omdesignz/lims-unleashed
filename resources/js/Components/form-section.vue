<template>
  <div class="grid gap-4 lg:grid-cols-3 lg:gap-8">
    <SectionTitle>
      <template #title>
        <slot name="title" />
      </template>
      <template #description>
        <slot name="description" />
      </template>
    </SectionTitle>

    <form class="ds-card overflow-hidden lg:col-span-2" @submit.prevent="$emit('submitted')">
      <div class="grid grid-cols-1 gap-5 p-5 sm:p-6">
        <slot name="form" />
      </div>

      <div v-if="hasActions" class="flex items-center justify-end gap-3 border-t border-[var(--pl-line)] px-5 py-3.5 sm:px-6">
        <slot name="actions" />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'
import SectionTitle from './section-title.vue'

defineEmits(['submitted'])

const hasActions = computed(() => !! useSlots().actions)
</script>
