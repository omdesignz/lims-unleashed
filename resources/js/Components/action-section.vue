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

    <div class="ds-card p-5 sm:p-6 lg:col-span-2">
      <slot name="content" />
    </div>
  </div>
</template>

<script setup>
import SectionTitle from './section-title.vue'
</script>
