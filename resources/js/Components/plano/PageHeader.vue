<script setup>
import { Link } from '@inertiajs/vue3'

/**
 * Plano page header: path, a large title, one sentence about what needs
 * attention, secondary actions on the right. Tabs or state cells follow
 * through the default slot.
 */
defineProps({
  crumbs: { type: Array, default: () => [] },
  title: { type: String, required: true },
  lede: { type: String, default: '' },
})
</script>

<template>
  <header class="pl-page-header">
    <nav v-if="crumbs.length" class="pl-crumbs" aria-label="Localização">
      <template v-for="(crumb, index) in crumbs" :key="`${crumb.title}-${index}`">
        <span v-if="index" aria-hidden="true">/</span>
        <Link v-if="crumb.url" :href="crumb.url">{{ crumb.title }}</Link>
        <span v-else :aria-current="index === crumbs.length - 1 ? 'page' : undefined">{{ crumb.title }}</span>
      </template>
    </nav>
    <div class="pl-page-head">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
          <h1 class="pl-d1 min-w-0 break-words">{{ title }}</h1>
          <slot name="badges" />
        </div>
        <p v-if="lede || $slots.lede" class="pl-lede"><slot name="lede">{{ lede }}</slot></p>
      </div>
      <div v-if="$slots.actions" class="pl-page-actions"><slot name="actions" /></div>
    </div>
    <slot />
  </header>
</template>
