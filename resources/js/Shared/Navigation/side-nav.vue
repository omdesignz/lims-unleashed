<script setup>
import { computed, ref, useId, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { AnimatePresence, motion } from 'motion-v'
import { ChevronDown } from '@lucide/vue'
import { easeOut, springPlane } from '@/Support/motion'

const props = defineProps({
  area: { type: Object, default: null },
})

const emit = defineEmits(['open-command-palette', 'navigate'])
const page = usePage()
const planeId = `pl-side-plane-${useId()}`

const currentPath = computed(() => String(page.url || '').split(/[?#]/)[0])
const matches = (path) => Boolean(path) && (currentPath.value === path || currentPath.value.startsWith(`${path}/`))

// The deepest matching entry wins, so a list page and its sub-pages never light up together.
const activeHref = computed(() => (props.area?.sections ?? [])
  .flatMap((section) => section.items)
  .filter((item) => matches(item.path))
  .sort((first, second) => second.path.length - first.path.length)[0]?.href ?? null)

// Catalogues and configuration fold away; they open by themselves when you are inside them.
const holdsActive = (section) => section.items.some((item) => item.href === activeHref.value)
const closed = ref(new Set())
const isOpen = (section) => !section.collapsible || holdsActive(section) || !closed.value.has(section.key)

function resetClosed() {
  closed.value = new Set((props.area?.sections ?? []).filter((section) => section.collapsible).map((section) => section.key))
}

function toggle(section) {
  const next = new Set(closed.value)

  if (next.has(section.key)) {
    next.delete(section.key)
  } else {
    next.add(section.key)
  }

  closed.value = next
}

watch(() => props.area?.key, resetClosed, { immediate: true })
</script>

<template>
  <nav class="pl-side-nav" :aria-label="props.area?.label || 'Navegação'">
    <section v-for="section in props.area?.sections ?? []" :key="section.key" class="pl-side-group">
      <button
        v-if="section.collapsible"
        type="button"
        class="pl-side-head pl-side-head-toggle"
        :aria-expanded="isOpen(section)"
        @click="toggle(section)"
      >
        <span>{{ section.label }}</span><ChevronDown aria-hidden="true" />
      </button>
      <p v-else-if="section.label" class="pl-side-head">{{ section.label }}</p>
      <AnimatePresence :initial="false">
        <motion.div
          v-if="isOpen(section)"
          class="pl-side-links"
          :initial="{ height: 0, opacity: 0 }"
          :animate="{ height: 'auto', opacity: 1 }"
          :exit="{ height: 0, opacity: 0, transition: { duration: 0.1, ease: easeOut } }"
          :transition="{ duration: 0.16, ease: easeOut }"
        >
          <Link
            v-for="item in section.items"
            :key="item.href"
            :href="item.href"
            class="pl-side-link"
            :aria-current="activeHref === item.href ? 'page' : undefined"
            @click="emit('navigate')"
          >
            <motion.span v-if="activeHref === item.href" :layout-id="planeId" class="pl-side-plane" :transition="springPlane" aria-hidden="true" />
            <span class="pl-side-link-label">{{ item.label }}</span>
            <span v-if="item.count" class="pl-side-count">{{ item.count > 99 ? '99+' : item.count }}</span>
          </Link>
        </motion.div>
      </AnimatePresence>
    </section>
  </nav>
</template>
