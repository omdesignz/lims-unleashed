<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Check, ChevronsUpDown, X } from '@lucide/vue'
import sideNav from './side-nav.vue'
import { contrastingText } from '@/Utils/brandingPalette'

/**
 * The area column: text only. The active laboratory sits on top with its own
 * colour on its seal (the only place a laboratory colour appears), then the
 * area's pages grouped under mono headings.
 */
const props = defineProps({
  mobile: { type: Boolean, default: false },
  areas: { type: Array, default: () => [] },
  activeAreaKey: { type: String, default: null },
})

const emit = defineEmits(['navigate', 'close'])

const page = usePage()
const laboratory = computed(() => page.props.laboratory ?? { labs: [], active_lab: null })
const activeLab = computed(() => laboratory.value.active_lab)
const activeArea = computed(() => props.areas.find((area) => area.key === props.activeAreaKey) ?? props.areas[0] ?? null)

const initials = (name) => String(name || '')
  .replace(/\[[^\]]*\]/g, ' ')
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word.charAt(0))
  .join('')
  .toUpperCase() || '·'

const labMarkStyle = (lab) => (lab?.primary_color ? { background: lab.primary_color, color: contrastingText(lab.primary_color) } : {})

function switchLab(lab) {
  if (lab.id === activeLab.value?.id) {
    return
  }

  emit('navigate')
  router.post(route('lab-context.switch', lab.id))
}
</script>

<template>
  <div class="pl-side-inner">
    <div v-if="props.mobile" class="pl-side-mobile-head">
      <span class="pl-k">{{ activeArea?.label || 'Menu' }}</span>
      <button type="button" class="ds-icon-button" aria-label="Fechar menu" @click="emit('close')"><X aria-hidden="true" /></button>
    </div>

    <nav v-if="props.mobile" class="pl-side-areas" aria-label="Áreas">
      <Link
        v-for="area in props.areas"
        :key="area.key"
        :href="area.href"
        class="pl-side-area"
        :aria-current="area.key === activeArea?.key ? 'page' : undefined"
        @click="emit('navigate')"
      >{{ area.label }}</Link>
    </nav>

    <Menu v-if="activeLab" as="div" class="relative">
      <MenuButton class="pl-lab">
        <span class="pl-lab-mark" :style="labMarkStyle(activeLab)" aria-hidden="true">{{ initials(activeLab.name) }}</span>
        <span class="min-w-0 flex-1 text-left">
          <span class="pl-lab-name">{{ activeLab.name }}</span>
          <span class="pl-lab-caption">{{ activeLab.network_name || 'Laboratório activo' }}</span>
        </span>
        <ChevronsUpDown class="pl-lab-chevron" aria-hidden="true" />
      </MenuButton>
      <transition
        enter-active-class="transition-[opacity,translate] duration-160 ease-[cubic-bezier(0.23,1,0.32,1)]"
        enter-from-class="-translate-y-1 opacity-0"
        leave-active-class="transition-opacity duration-100 ease-out"
        leave-to-class="opacity-0"
      >
        <MenuItems class="ds-floating-panel absolute inset-x-4 z-30 mt-1 origin-top focus:outline-none">
          <p class="pl-menu-heading">Os seus laboratórios</p>
          <MenuItem v-for="lab in laboratory.labs" :key="lab.id" v-slot="{ active }">
            <button type="button" class="pl-menu-item" :data-active="active" @click="switchLab(lab)">
              <span class="pl-lab-mark pl-lab-mark-sm" :style="labMarkStyle(lab)" aria-hidden="true">{{ initials(lab.name) }}</span>
              <span class="min-w-0 flex-1 truncate text-left">{{ lab.name }}</span>
              <Check v-if="lab.id === activeLab.id" class="text-[var(--pl-accent-text)]" aria-hidden="true" />
            </button>
          </MenuItem>
        </MenuItems>
      </transition>
    </Menu>

    <side-nav :area="activeArea" @navigate="emit('navigate')" />
  </div>
</template>
