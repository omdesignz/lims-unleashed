<script setup>
import { computed, ref, useId } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { motion } from 'motion-v'
import {
  Bell,
  BookOpen,
  Check,
  ChevronsUpDown,
  CircleUser,
  Languages,
  LogOut,
  Moon,
  Plus,
  Search,
  Sun,
  X,
} from '@lucide/vue'
import AnimatedIcon from '@/Components/motion/AnimatedIcon.vue'
import sideNav from './side-nav.vue'
import { usePermission } from '@/Composables/usePermissions'
import { contrastingText } from '@/Utils/brandingPalette'
import { springSnappy } from '@/Support/motion'

const props = defineProps({
  mobile: { type: Boolean, default: false },
  areas: { type: Array, default: () => [] },
  activeAreaKey: { type: String, default: null },
  unreadCount: { type: Number, default: 0 },
  isDark: { type: Boolean, default: false },
})

const emit = defineEmits(['open-command-palette', 'navigate', 'close', 'toggle-theme', 'switch-language'])

const page = usePage()
const { hasPermission } = usePermission()
const pillId = `app-rail-pill-${useId()}`
const railIcons = ref({})

const settings = computed(() => page.props?.settings ?? {})
const user = computed(() => page.props?.auth?.user ?? null)
const laboratory = computed(() => page.props.laboratory ?? { labs: [], active_lab: null })
const activeLab = computed(() => laboratory.value.active_lab)
const languages = computed(() => page.props.languages?.data ?? [])
const appName = computed(() => settings.value.app_name || 'LIMS Unleashed')
const profileHref = computed(() => user.value?.id ? route('users.edit', user.value.id) : route('dashboard'))
const unreadLabel = computed(() => props.unreadCount > 99 ? '99+' : String(props.unreadCount))
const activeArea = computed(() => props.areas.find((area) => area.key === props.activeAreaKey) ?? props.areas[0] ?? null)
const onNotifications = computed(() => String(page.url || '').startsWith('/notifications'))
const canReceiveSamples = computed(() => hasPermission('add_samples') && ['home', 'samples'].includes(activeArea.value?.key))

const initials = (name) => String(name || '')
  .replace(/\[[^\]]*\]/g, ' ')
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word.charAt(0))
  .join('')
  .toUpperCase() || '·'

const labMarkStyle = (lab) => ({ background: lab.primary_color, color: contrastingText(lab.primary_color) })

function switchLab(lab) {
  if (lab.id === activeLab.value?.id) {
    return
  }

  emit('navigate')
  router.post(route('lab-context.switch', lab.id))
}
</script>

<template>
  <div class="contents">
    <nav class="app-rail" aria-label="Áreas">
      <Link :href="route('dashboard')" class="app-rail-brand" :class="settings.logo_url ? 'app-rail-brand-custom' : ''" :aria-label="`${appName} — Visão geral`" @click="emit('navigate')">
        <img v-if="settings.logo_url" :src="settings.logo_url" :alt="appName" />
        <img v-else src="/brand/svg/VAP_Small_White.svg" alt="VAP Sistemas" />
      </Link>

      <div class="app-rail-list">
        <Link
          v-for="area in props.areas"
          :key="area.key"
          :href="area.href"
          class="app-rail-item"
          :title="area.label"
          :aria-label="area.label"
          :aria-current="area.key === activeArea?.key && !onNotifications ? 'true' : undefined"
          @mouseenter="railIcons[area.key]?.play()"
          @click="emit('navigate')"
        >
          <motion.span
            v-if="area.key === activeArea?.key && !onNotifications"
            :layout-id="pillId"
            class="app-rail-pill"
            :transition="springSnappy"
            aria-hidden="true"
          />
          <AnimatedIcon :ref="(component) => { railIcons[area.key] = component }" :icon="area.icon" :animation="area.animation" />
        </Link>
      </div>

      <div class="app-rail-foot">
        <Link
          :href="route('notifications.index')"
          class="app-rail-item"
          title="Notificações"
          :aria-label="props.unreadCount ? `Notificações, ${props.unreadCount} por ler` : 'Notificações'"
          :aria-current="onNotifications ? 'true' : undefined"
          @mouseenter="railIcons.notifications?.play()"
          @click="emit('navigate')"
        >
          <motion.span v-if="onNotifications" :layout-id="pillId" class="app-rail-pill" :transition="springSnappy" aria-hidden="true" />
          <AnimatedIcon :ref="(component) => { railIcons.notifications = component }" :icon="Bell" animation="ring" />
          <span v-if="props.unreadCount" class="app-rail-count" aria-hidden="true">{{ unreadLabel }}</span>
        </Link>

        <Menu as="div" class="relative mt-1">
          <MenuButton class="app-avatar" :title="user?.name" :aria-label="`Conta de ${user?.name ?? 'utilizador'}`">
            <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" alt="" />
            <template v-else>{{ initials(user?.name) }}</template>
          </MenuButton>
          <transition
            enter-active-class="transition-[opacity,scale] duration-150 ease-out"
            enter-from-class="scale-[0.97] opacity-0"
            leave-active-class="transition-opacity duration-100 ease-out"
            leave-to-class="opacity-0"
          >
            <MenuItems class="ds-floating-panel absolute bottom-0 left-full z-40 ml-3 w-64 origin-bottom-left focus:outline-none">
              <div class="px-2.5 pb-2 pt-1.5">
                <p class="truncate text-sm font-semibold text-[var(--ds-text)]">{{ user?.name }}</p>
                <p class="truncate text-xs text-[var(--ds-text-soft)]">{{ user?.email }}</p>
              </div>
              <MenuItem v-slot="{ active }">
                <Link :href="profileHref" class="app-menu-item" :data-active="active" @click="emit('navigate')">
                  <CircleUser aria-hidden="true" />Perfil e segurança
                </Link>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <Link :href="route('users.help')" class="app-menu-item" :data-active="active" @click="emit('navigate')">
                  <BookOpen aria-hidden="true" />Manual do utilizador
                </Link>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button type="button" class="app-menu-item" :data-active="active" @click="emit('toggle-theme')">
                  <Sun v-if="props.isDark" aria-hidden="true" /><Moon v-else aria-hidden="true" />
                  {{ props.isDark ? 'Modo claro' : 'Modo escuro' }}
                </button>
              </MenuItem>
              <div v-if="languages.length > 1" class="my-1 border-t border-[var(--ds-border)] pt-1">
                <MenuItem v-for="language in languages" :key="language.value" v-slot="{ active }">
                  <button type="button" class="app-menu-item" :data-active="active" @click="emit('switch-language', language.value)">
                    <Languages aria-hidden="true" />
                    <span class="flex-1">{{ language.label }}</span>
                    <Check v-if="language.value === page.props.language" class="text-[rgb(var(--primary-600-rgb))]" aria-hidden="true" />
                  </button>
                </MenuItem>
              </div>
              <div class="mt-1 border-t border-[var(--ds-border)] pt-1">
                <MenuItem v-slot="{ active }">
                  <Link :href="route('logout')" method="post" as="button" class="app-menu-item app-menu-item-danger" :data-active="active">
                    <LogOut aria-hidden="true" />Terminar sessão
                  </Link>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>
      </div>
    </nav>

    <aside class="app-column" :aria-label="activeArea?.label || 'Navegação'">
      <div class="flex items-center gap-1">
        <button type="button" class="app-search" aria-label="Pesquisar módulos e registos" @click="emit('open-command-palette')">
          <Search class="h-4 w-4 shrink-0" aria-hidden="true" />
          <span class="flex-1">Pesquisar</span>
          <kbd v-if="!props.mobile" class="app-kbd">⌘K</kbd>
        </button>
        <button v-if="props.mobile" type="button" class="app-topbar-action" @click="emit('close')">
          <span class="sr-only">Fechar navegação</span>
          <X aria-hidden="true" />
        </button>
      </div>

      <Menu v-if="activeLab" as="div" class="relative">
        <MenuButton class="app-lab">
          <span class="app-lab-mark" aria-hidden="true">{{ initials(activeLab.name) }}</span>
          <span class="min-w-0 flex-1">
            <span class="app-lab-name">{{ activeLab.name }}</span>
            <span class="app-lab-caption">{{ activeLab.network_name || 'Laboratório activo' }}</span>
          </span>
          <ChevronsUpDown class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
        </MenuButton>
        <transition
          enter-active-class="transition-[opacity,scale] duration-150 ease-out"
          enter-from-class="scale-[0.97] opacity-0"
          leave-active-class="transition-opacity duration-100 ease-out"
          leave-to-class="opacity-0"
        >
          <MenuItems class="ds-floating-panel absolute left-0 z-30 mt-1 w-64 origin-top-left focus:outline-none">
            <p class="px-2.5 pb-1.5 pt-1 text-xs text-[var(--ds-text-soft)]">Os seus laboratórios</p>
            <MenuItem v-for="lab in laboratory.labs" :key="lab.id" v-slot="{ active }">
              <button type="button" class="app-menu-item" :data-active="active" @click="switchLab(lab)">
                <span class="app-lab-mark h-6 w-6 text-[0.625rem]" :style="labMarkStyle(lab)" aria-hidden="true">{{ initials(lab.name) }}</span>
                <span class="min-w-0 flex-1 truncate">{{ lab.name }}</span>
                <Check v-if="lab.id === activeLab.id" class="text-[rgb(var(--primary-600-rgb))]" aria-hidden="true" />
              </button>
            </MenuItem>
          </MenuItems>
        </transition>
      </Menu>

      <side-nav :area="activeArea" @navigate="emit('navigate')" @open-command-palette="emit('open-command-palette')" />

      <Link v-if="canReceiveSamples" :href="route('vap_samples.index')" class="app-column-action" @click="emit('navigate')">
        <Plus aria-hidden="true" />Receber amostra
      </Link>
    </aside>
  </div>
</template>
