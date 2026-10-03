<script setup>
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { AnimatePresence, motion } from 'motion-v'
import { ChevronDown } from '@lucide/vue'
import { easeOut } from '@/Support/motion'

const props = defineProps({
  area: { type: Object, default: null },
})

const emit = defineEmits(['open-command-palette', 'navigate'])
const page = usePage()

const currentPath = computed(() => String(page.url || '').split(/[?#]/)[0])
const matches = (path) => Boolean(path) && (currentPath.value === path || currentPath.value.startsWith(`${path}/`))

// The deepest matching entry wins, so a list page and its sub-pages never light up together.
const activeHref = computed(() => (props.area?.sections ?? [])
  .flatMap((section) => section.items)
  .filter((item) => matches(item.path))
  .sort((first, second) => second.path.length - first.path.length)[0]?.href ?? null)

const closed = ref(new Set())
const isOpen = (section) => !closed.value.has(section.key)

function toggle(section) {
  const next = new Set(closed.value)

  if (next.has(section.key)) {
    next.delete(section.key)
  } else {
    next.add(section.key)
  }

  closed.value = next
}

watch(() => props.area?.key, () => { closed.value = new Set() })
</script>

<template>
  <nav class="app-nav" :aria-label="props.area?.label || 'Navegação'">
    <p v-if="props.area" class="app-nav-title">{{ props.area.label }}</p>
    <section v-for="section in props.area?.sections ?? []" :key="section.key" class="app-nav-group">
      <button
        v-if="section.label"
        type="button"
        class="app-nav-label"
        :aria-expanded="isOpen(section)"
        @click="toggle(section)"
      >
        <ChevronDown aria-hidden="true" />{{ section.label }}
      </button>
      <AnimatePresence :initial="false">
        <motion.div
          v-if="isOpen(section)"
          class="app-nav-group overflow-hidden"
          :initial="{ height: 0, opacity: 0 }"
          :animate="{ height: 'auto', opacity: 1 }"
          :exit="{ height: 0, opacity: 0, transition: { duration: 0.14, ease: easeOut } }"
          :transition="{ duration: 0.2, ease: easeOut }"
        >
          <Link
            v-for="item in section.items"
            :key="item.href"
            :href="item.href"
            class="ds-nav-item app-nav-item"
            :aria-current="activeHref === item.href ? 'page' : undefined"
            @click="emit('navigate')"
          >
            <span class="app-nav-glyph" aria-hidden="true" />
            <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
          </Link>
        </motion.div>
      </AnimatePresence>
    </section>
  </nav>
</template>
