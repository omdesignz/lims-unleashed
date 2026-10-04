<script setup>
import { computed, inject, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'
import { planoShellKey } from '@/Support/planoShell'

/**
 * Plano page header: path, a large title, one sentence about what needs
 * attention, secondary actions on the right. Tabs or state cells follow
 * through the default slot.
 *
 * Without `crumbs` the header shows the path the shell worked out for the page
 * (its area, then the trail the server sent). `trail` keeps the shell's area and
 * replaces only what follows it.
 */
const props = defineProps({
  crumbs: { type: Array, default: () => [] },
  trail: { type: Array, default: () => [] },
  title: { type: String, required: true },
  lede: { type: String, default: '' },
})

const shell = inject(planoShellKey, null)
shell?.claim()
onBeforeUnmount(() => shell?.release())

const path = computed(() => {
  if (props.crumbs.length) return props.crumbs

  const shellPath = shell?.crumbs.value ?? []
  const root = shellPath.slice(0, 1)

  // A trail that opens by naming the area again would repeat it.
  return props.trail.length
    ? [...root, ...props.trail.filter((crumb, index) => index > 0 || crumb.title !== root[0]?.title)]
    : shellPath
})
</script>

<template>
  <header class="pl-page-header">
    <nav v-if="path.length" class="pl-crumbs" aria-label="Localização">
      <template v-for="(crumb, index) in path" :key="`${crumb.title}-${index}`">
        <span v-if="index" aria-hidden="true">/</span>
        <Link v-if="crumb.url && index < path.length - 1" :href="crumb.url">{{ crumb.title }}</Link>
        <span v-else :aria-current="index === path.length - 1 ? 'page' : undefined">{{ crumb.title }}</span>
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
