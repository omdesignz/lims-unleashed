<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { motion } from 'motion-v'
import { Bell, BookOpen, Check, CircleUser, Languages, LogOut, Menu as MenuIcon, Moon, Palette, Plus, Search, Sun } from '@lucide/vue'
import { usePermission } from '@/Composables/usePermissions'
import { springPlane } from '@/Support/motion'

const props = defineProps({
  areas: { type: Array, default: () => [] },
  activeAreaKey: { type: String, default: null },
  unreadCount: { type: Number, default: 0 },
  isDark: { type: Boolean, default: false },
  canManageBranding: { type: Boolean, default: false },
})

const emit = defineEmits(['open-command-palette', 'open-menu', 'toggle-theme', 'switch-language', 'open-branding'])

const page = usePage()
const { hasPermission } = usePermission()
const underlineId = `pl-area-underline-${useId()}`

const settings = computed(() => page.props?.settings ?? {})
const user = computed(() => page.props?.auth?.user ?? null)
const languages = computed(() => page.props.languages?.data ?? [])
const appName = computed(() => settings.value.app_name || 'VAP LIMS')
const profileHref = computed(() => user.value?.id ? route('users.edit', user.value.id) : route('dashboard'))
const unreadLabel = computed(() => props.unreadCount > 99 ? '99+' : String(props.unreadCount))
const canReceiveSamples = computed(() => hasPermission('add_samples'))

const initials = (name) => String(name || '')
  .replace(/\[[^\]]*\]/g, ' ')
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word.charAt(0))
  .join('')
  .toUpperCase() || '·'

function logout() {
  router.post(route('logout'))
}

// Below 1280px the eight areas can outgrow the bar: the strip scrolls, keeps the
// current area in view and marks the edge that has more areas behind it, which
// the stylesheet fades (`data-more-before` / `data-more-after`).
const bar = ref(null)
const areaStrip = () => bar.value?.querySelector('.pl-areas') ?? null
let stripObserver = null

function measureAreaStrip() {
  const strip = areaStrip()

  if (!strip) {
    return
  }

  strip.toggleAttribute('data-more-before', strip.scrollLeft > 1)
  strip.toggleAttribute('data-more-after', strip.scrollLeft + strip.clientWidth < strip.scrollWidth - 1)
}

function revealActiveArea() {
  const strip = areaStrip()
  const current = strip?.querySelector('[aria-current="page"]')

  if (strip && current && strip.scrollWidth > strip.clientWidth) {
    const offset = current.getBoundingClientRect().left - strip.getBoundingClientRect().left
    strip.scrollLeft += offset - (strip.clientWidth - current.offsetWidth) / 2
  }

  measureAreaStrip()
}

onMounted(() => {
  const strip = areaStrip()
  revealActiveArea()
  strip?.addEventListener('scroll', measureAreaStrip, { passive: true })

  if (typeof ResizeObserver !== 'undefined' && strip) {
    stripObserver = new ResizeObserver(measureAreaStrip)
    stripObserver.observe(strip)
  }
})

onBeforeUnmount(() => {
  areaStrip()?.removeEventListener('scroll', measureAreaStrip)
  stripObserver?.disconnect()
})

watch(() => props.activeAreaKey, () => nextTick(revealActiveArea))
</script>

<template>
  <header ref="bar" class="pl-top">
    <button type="button" class="pl-top-menu" aria-controls="area-column" aria-label="Abrir menu da área" @click="emit('open-menu')">
      <MenuIcon aria-hidden="true" /><span>Menu</span>
    </button>

    <Link :href="route('dashboard')" class="pl-brand" :aria-label="`${appName} — Início`">
      <img v-if="settings.logo_url" :src="settings.logo_url" :alt="appName" />
      <template v-else>
        <img src="/brand/svg/VAP_Small.svg" alt="VAP Sistemas" class="pl-logo-light" />
        <img src="/brand/svg/VAP_Small_White.svg" alt="" aria-hidden="true" class="pl-logo-dark" />
      </template>
      <span class="pl-k">LIMS</span>
    </Link>

    <nav class="pl-areas" aria-label="Áreas">
      <Link
        v-for="area in props.areas"
        :key="area.key"
        :href="area.href"
        class="pl-area"
        :aria-current="area.key === props.activeAreaKey ? 'page' : undefined"
      >
        {{ area.label }}
        <motion.span
          v-if="area.key === props.activeAreaKey"
          :layout-id="underlineId"
          class="pl-area-underline"
          :transition="springPlane"
          aria-hidden="true"
        />
      </Link>
    </nav>

    <div class="pl-top-end">
      <button type="button" class="pl-find" aria-label="Procurar módulos e registos" @click="emit('open-command-palette')">
        <Search aria-hidden="true" /><span class="pl-find-label">Procurar</span><kbd class="pl-kbd">⌘K</kbd>
      </button>

      <button
        type="button"
        class="ds-icon-button pl-theme"
        :aria-pressed="props.isDark"
        :aria-label="props.isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro'"
        :title="`${props.isDark ? 'Tema claro' : 'Tema escuro'} (⇧D)`"
        @click="emit('toggle-theme')"
      >
        <Sun v-if="props.isDark" aria-hidden="true" /><Moon v-else aria-hidden="true" />
      </button>

      <Link
        :href="route('notifications.index')"
        class="ds-icon-button pl-bell"
        :aria-label="props.unreadCount ? `Notificações, ${props.unreadCount} por ler` : 'Notificações'"
      >
        <Bell aria-hidden="true" />
        <span v-if="props.unreadCount" class="pl-badge" aria-hidden="true">{{ unreadLabel }}</span>
      </Link>

      <Link v-if="canReceiveSamples" :href="route('vap_samples.index')" class="ds-button ds-button-primary pl-receive">
        <span>Receber amostra</span><Plus aria-hidden="true" />
      </Link>

      <Menu as="div" class="relative">
        <MenuButton class="pl-avatar" :title="user?.name" :aria-label="`Conta de ${user?.name ?? 'utilizador'}`">
          <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" alt="" />
          <template v-else>{{ initials(user?.name) }}</template>
        </MenuButton>
        <transition
          enter-active-class="transition-[opacity,translate] duration-160 ease-[cubic-bezier(0.23,1,0.32,1)]"
          enter-from-class="-translate-y-1 opacity-0"
          leave-active-class="transition-opacity duration-100 ease-out"
          leave-to-class="opacity-0"
        >
          <MenuItems class="ds-floating-panel absolute right-0 z-50 mt-1 w-72 origin-top-right focus:outline-none">
            <div class="pl-menu-head">
              <p class="truncate text-[13.5px] font-semibold text-[var(--pl-fg)]">{{ user?.name }}</p>
              <p class="truncate text-xs text-[var(--pl-faint)]">{{ user?.email }}</p>
            </div>
            <MenuItem v-slot="{ active }">
              <Link :href="profileHref" class="pl-menu-item" :data-active="active"><CircleUser aria-hidden="true" />Perfil e segurança</Link>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <Link :href="route('users.help')" class="pl-menu-item" :data-active="active"><BookOpen aria-hidden="true" />Manual do utilizador</Link>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <button type="button" class="pl-menu-item" :data-active="active" @click="emit('toggle-theme')">
                <Sun v-if="props.isDark" aria-hidden="true" /><Moon v-else aria-hidden="true" />
                <span class="flex-1 text-left">{{ props.isDark ? 'Tema claro' : 'Tema escuro' }}</span>
                <kbd class="pl-kbd">⇧D</kbd>
              </button>
            </MenuItem>
            <MenuItem v-if="props.canManageBranding" v-slot="{ active }">
              <button type="button" class="pl-menu-item" :data-active="active" @click="emit('open-branding')">
                <Palette aria-hidden="true" />Selo do laboratório
              </button>
            </MenuItem>
            <template v-if="languages.length > 1">
              <div class="pl-menu-sep" />
              <MenuItem v-for="language in languages" :key="language.value" v-slot="{ active }">
                <button type="button" class="pl-menu-item" :data-active="active" @click="emit('switch-language', language.value)">
                  <Languages aria-hidden="true" />
                  <span class="flex-1 text-left">{{ language.label }}</span>
                  <Check v-if="language.value === page.props.language" class="text-[var(--pl-accent-text)]" aria-hidden="true" />
                </button>
              </MenuItem>
            </template>
            <div class="pl-menu-sep" />
            <MenuItem v-slot="{ active }">
              <button type="button" class="pl-menu-item pl-menu-item-danger" :data-active="active" @click="logout">
                <LogOut aria-hidden="true" />Terminar sessão
              </button>
            </MenuItem>
          </MenuItems>
        </transition>
      </Menu>
    </div>
  </header>
</template>
