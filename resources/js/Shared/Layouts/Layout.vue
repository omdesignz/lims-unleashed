<template>
  <div class="lims-app-shell min-h-dvh bg-[var(--ds-canvas)]" :style="brandingCssVariables" :data-theme-preset="themePreset">
    <backend-modal />
    <ToastList />

    <div v-if="impersonation" class="ds-impersonation-banner relative z-[60] flex flex-wrap items-center justify-center gap-3 px-4 py-2.5 text-sm font-medium">
      <span><strong>{{ trans('gestlab.general.labels.impersonation.title') }}</strong> {{ trans('gestlab.general.labels.impersonation.description') }} {{ auth?.user?.name }}.</span>
      <button type="button" class="rounded-md bg-white/15 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/25" @click="router.get(route('users.stopimpersonating'), {}, { preserveState: false, replace: true })">
        {{ trans('gestlab.general.buttons.leave_impersonation') }}
      </button>
    </div>

    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog as="div" class="relative z-50 lg:hidden" @close="sidebarOpen = false">
        <TransitionChild as="template" enter="transition-opacity ease-linear duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-slate-950/55" />
        </TransitionChild>
        <div class="fixed inset-0 flex">
          <TransitionChild as="template" enter="transition ease-out duration-200 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in duration-150 transform" leave-from="translate-x-0" leave-to="-translate-x-full">
            <DialogPanel class="flex w-full max-w-72 flex-col border-r border-[var(--ds-border)] bg-[var(--ds-panel)] shadow-xl">
              <div class="flex h-16 shrink-0 items-center gap-3 border-b border-[var(--ds-border)] px-4">
                <Link :href="route('dashboard')" class="flex min-w-0 flex-1 items-center gap-3" @click="sidebarOpen = false">
                  <span v-if="!settings?.logo_url" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--brand-secondary)] text-xs font-bold text-white">{{ brandInitials }}</span>
                  <img v-else class="max-h-9 max-w-32 object-contain" :src="settings.logo_url" :alt="settings?.app_name || ''" />
                  <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ settings?.app_name }}</span>
                    <span class="block truncate text-xs text-[var(--ds-text-soft)]">{{ settings?.lab_name }}</span>
                  </span>
                </Link>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]" @click="sidebarOpen = false">
                  <span class="sr-only">Fechar navegação</span>
                  <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </div>
              <side-nav class="py-5" @navigate="sidebarOpen = false" @open-command-palette="openCommandPaletteFromMobile" />
              <div class="mt-auto border-t border-[var(--ds-border)] p-3">
                <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                  <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[rgb(var(--primary-100-rgb))] text-sm font-semibold text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.14)] dark:text-white">{{ auth?.user?.name?.charAt(0) }}</span>
                  <span class="min-w-0"><span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ auth?.user?.name }}</span><span class="block truncate text-xs text-[var(--ds-text-soft)]">{{ auth?.user?.email }}</span></span>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <aside :class="desktopSidebarOpen ? 'lg:w-64' : 'lg:w-[4.5rem]'" class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-[var(--ds-border)] bg-[var(--ds-panel)] transition-[width] duration-200 lg:flex">
      <div class="flex h-16 shrink-0 items-center border-b border-[var(--ds-border)]" :class="desktopSidebarOpen ? 'px-4' : 'justify-center px-2'">
        <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3" :title="!desktopSidebarOpen ? settings?.app_name : undefined">
          <span v-if="!settings?.logo_url" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--brand-secondary)] text-xs font-bold text-white">{{ brandInitials }}</span>
          <span v-else class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[var(--ds-border)] bg-white p-1.5"><img class="max-h-full max-w-full object-contain" :src="settings.logo_url" :alt="settings?.app_name || ''" /></span>
          <span v-if="desktopSidebarOpen" class="min-w-0">
            <span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ settings?.app_name }}</span>
            <span class="block truncate text-xs text-[var(--ds-text-soft)]">{{ settings?.lab_name }}</span>
          </span>
        </Link>
      </div>
      <side-nav :collapsed="!desktopSidebarOpen" class="py-5" @open-command-palette="openCommandPalette" />
      <div class="mt-auto border-t border-[var(--ds-border)] p-2">
        <div :class="desktopSidebarOpen ? 'gap-3 px-2' : 'justify-center px-1'" class="flex min-h-12 items-center rounded-lg">
          <img v-if="auth?.user?.profile_photo_url" :src="auth.user.profile_photo_url" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover" />
          <span v-else class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[rgb(var(--primary-100-rgb))] text-sm font-semibold text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.14)] dark:text-white">{{ auth?.user?.name?.charAt(0) }}</span>
          <span v-if="desktopSidebarOpen" class="min-w-0"><span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ auth?.user?.name }}</span><span class="block truncate text-xs text-[var(--ds-text-soft)]">{{ auth?.user?.email }}</span></span>
        </div>
      </div>
    </aside>

    <div :class="desktopSidebarOpen ? 'lg:pl-64' : 'lg:pl-[4.5rem]'" class="min-h-dvh transition-[padding] duration-200">
      <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel)] px-4 sm:px-6 lg:px-5">
        <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)] lg:hidden" @click="sidebarOpen = true">
          <span class="sr-only">Abrir navegação</span><Bars3Icon class="h-5 w-5" aria-hidden="true" />
        </button>
        <button type="button" class="hidden h-9 w-9 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)] lg:grid" :title="desktopSidebarOpen ? 'Recolher navegação' : 'Expandir navegação'" @click="toggleDesktopSidebar">
          <Bars3Icon class="h-5 w-5" aria-hidden="true" />
        </button>

        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-[var(--ds-text)]">{{ moduleFamilyLabel }}</p>
          <p class="hidden truncate text-xs text-[var(--ds-text-soft)] sm:block">{{ settings?.lab_name }}</p>
        </div>

        <button type="button" class="ml-auto hidden h-9 min-w-64 max-w-xl flex-1 items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 text-left text-sm text-[var(--ds-text-muted)] transition hover:border-[var(--ds-border-strong)] md:flex" @click="openCommandPalette">
          <MagnifyingGlassIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
          <span class="truncate">Pesquisar amostras, documentos e modulos</span>
          <kbd class="ml-auto rounded border border-[var(--ds-border)] bg-[var(--ds-panel)] px-1.5 py-0.5 font-mono text-[0.62rem] text-[var(--ds-text-soft)]">⌘K</kbd>
        </button>
        <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)] md:hidden" @click="openCommandPalette"><span class="sr-only">Pesquisar</span><MagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" /></button>

        <Link prefetch :href="route('notifications.index')" class="relative grid h-9 w-9 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">
          <span class="sr-only">Ver notificações</span><BellIcon class="h-5 w-5" aria-hidden="true" />
          <span v-if="auth?.user?.unread_notifications?.length" class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-[var(--ds-panel)]" />
        </Link>

        <Menu as="div" class="relative">
          <MenuButton class="flex h-9 items-center gap-2 rounded-lg p-1 text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)] sm:pr-2">
            <img v-if="auth?.user?.profile_photo_url" :src="auth.user.profile_photo_url" alt="" class="h-7 w-7 rounded-md object-cover" />
            <span v-else class="grid h-7 w-7 place-items-center rounded-md bg-[var(--brand-secondary)] text-xs font-semibold text-white">{{ auth?.user?.name?.charAt(0) }}</span>
            <span class="hidden max-w-28 truncate text-sm font-semibold xl:block">{{ auth?.user?.name }}</span>
            <ChevronDownIcon class="hidden h-4 w-4 text-[var(--ds-text-soft)] sm:block" aria-hidden="true" />
          </MenuButton>
          <transition enter-active-class="transition ease-out duration-100" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <MenuItems class="ds-floating-panel absolute right-0 z-20 mt-2 w-64 origin-top-right p-2 focus:outline-none">
              <div class="border-b border-[var(--ds-border)] px-3 py-2">
                <p class="truncate text-sm font-semibold text-[var(--ds-text)]">{{ auth?.user?.name }}</p><p class="truncate text-xs text-[var(--ds-text-soft)]">{{ auth?.user?.email }}</p>
              </div>
              <div class="space-y-1 py-2">
                <MenuItem v-slot="{ active }"><Link :href="profileHref" :class="[active ? 'bg-[var(--ds-panel-subtle)] text-[var(--ds-text)]' : 'text-[var(--ds-text-muted)]', 'block rounded-lg px-3 py-2 text-sm font-semibold']">Perfil e seguranca</Link></MenuItem>
                <MenuItem v-slot="{ active }"><Link :href="route('users.help')" :class="[active ? 'bg-[var(--ds-panel-subtle)] text-[var(--ds-text)]' : 'text-[var(--ds-text-muted)]', 'block rounded-lg px-3 py-2 text-sm font-semibold']">Manual do utilizador</Link></MenuItem>
                <MenuItem v-slot="{ active }"><button type="button" :class="[active ? 'bg-[var(--ds-panel-subtle)] text-[var(--ds-text)]' : 'text-[var(--ds-text-muted)]', 'flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold']" @click="toggleTheme"><span>{{ isDark ? 'Modo claro' : 'Modo escuro' }}</span><SunIcon v-if="isDark" class="h-4 w-4" /><MoonIcon v-else class="h-4 w-4" /></button></MenuItem>
              </div>
              <div v-if="$page.props.languages?.data?.length > 1" class="border-y border-[var(--ds-border)] py-2">
                <button v-for="language in $page.props.languages.data" :key="language.value" type="button" :class="[language.value === $page.props.language ? 'bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-white' : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)]', 'block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold']" @click="switchLanguage(language.value)">{{ language.label }}</button>
              </div>
              <div class="pt-2"><Link :href="route('logout')" method="post" as="button" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10">Terminar sessão</Link></div>
            </MenuItems>
          </transition>
        </Menu>
      </header>

      <TransitionRoot as="template" :show="commandPaletteOpen">
        <Dialog as="div" class="relative z-[70]" @close="commandPaletteOpen = false">
          <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-slate-950/50" /></TransitionChild>
          <div class="fixed inset-0 z-[70] overflow-y-auto p-4 sm:p-8 md:p-20">
            <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="ease-in duration-100" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
              <DialogPanel class="ds-command-palette mx-auto max-w-2xl overflow-hidden">
                <div class="flex items-center gap-3 border-b border-[var(--ds-border)] px-4"><MagnifyingGlassIcon class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" /><BaseInput ref="commandPaletteInput" v-model="commandPaletteQuery" type="search" class="h-14 min-w-0 flex-1 border-0 bg-transparent text-sm font-semibold text-[var(--ds-text)] outline-none placeholder:text-[var(--ds-text-soft)] focus:ring-0" placeholder="Pesquisar modulos e registos..." @keydown.enter.prevent="activateFirstCommandPaletteResult" /><kbd class="rounded border border-[var(--ds-border)] px-2 py-1 font-mono text-[0.65rem] text-[var(--ds-text-soft)]">ESC</kbd></div>
                <div class="max-h-[70vh] overflow-y-auto p-2 sm:max-h-[32rem]">
                  <div v-if="filteredCommandGroups.length" class="space-y-3">
                    <section v-for="group in filteredCommandGroups" :key="group.label">
                      <p class="px-3 py-2 font-mono text-[0.68rem] font-semibold uppercase text-[var(--ds-text-soft)]">{{ group.label }}</p>
                      <button v-for="command in group.items" :key="`${group.label}-${command.href}-${command.label}`" type="button" class="ds-command-palette-item group" @click="visitCommand(command)"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-soft)]"><component :is="command.icon" class="h-4 w-4" /></span><span class="min-w-0 flex-1 text-left"><span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ command.label }}</span><span class="block truncate font-mono text-[0.68rem] uppercase text-[var(--ds-text-soft)]">{{ command.path }}</span></span><ChevronRightIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" /></button>
                    </section>
                  </div>
                  <div v-else class="px-6 py-14 text-center"><MagnifyingGlassIcon class="mx-auto h-5 w-5 text-[var(--ds-text-soft)]" /><p class="mt-4 text-sm font-semibold text-[var(--ds-text)]">Nenhum modulo encontrado</p></div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </Dialog>
      </TransitionRoot>

      <main class="min-h-[calc(100vh-4rem)] py-5 lg:py-6">
        <div class="lims-backoffice-content px-4 sm:px-6 lg:px-8" :data-module-family="moduleFamily">
          <breadcrumbs v-if="$page.props.breadcrumbs?.length" :pages="$page.props.breadcrumbs" class="mb-4" />
          <confirm-dialog v-if="showSessionModal" :open="showSessionModal" :title="$t('Session Expiring Soon')" :description="$t('You will be logged out due to inactivity')" variant="warning" :hide-buttons="true" size="sm:max-w-xl" @canceled="showSessionModal = false">
            <p class="mt-4 text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('For your security, this session will end in :seconds seconds.', { seconds: remainingTime }) }} {{ $t('Move your mouse or press any key to continue working.') }}</p>
          </confirm-dialog>
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch, onMounted, onUnmounted } from 'vue'
import { useIdle, useCounter } from '@vueuse/core'
import sideNav from '../Navigation/side-nav.vue'
import ToastList from '@/Components/toast-list.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import breadcrumbs from '@/Components/breadcrumbs.vue'
import {
  Dialog,
  DialogPanel,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  Bars3Icon,
  BellIcon,
  HomeIcon,
  ShieldCheckIcon,
  MegaphoneIcon,
  UsersIcon,
  FolderOpenIcon,
  BanknotesIcon,
  Square3Stack3DIcon,
  DocumentTextIcon,
  RectangleStackIcon,
  UserGroupIcon,
  UserIcon,
  FingerPrintIcon,
  StopIcon,
  WrenchScrewdriverIcon,
  ServerIcon,
  InboxStackIcon,
  ExclamationTriangleIcon,
  SwatchIcon,
  Cog6ToothIcon,
  BeakerIcon,
  ArrowsRightLeftIcon,
  ArrowDownTrayIcon,
  ChevronRightIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
import { router, usePage } from '@inertiajs/vue3'
import { usePermission } from '@/Composables/usePermissions'
import { useTheme } from '@/Composables/useTheme'
import { trans, loadLanguageAsync } from 'laravel-vue-i18n'
import backendModal from '@/Components/backend-modal.vue'
import { getEcho } from '@/lib/echo'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'

const { hasPermission } = usePermission()

const props = defineProps({
  auth: Object,
  impersonation: Boolean,
})

const { isDark, toggle: toggleTheme } = useTheme(props.auth?.user?.theme, Boolean(props.auth?.user))
const page = usePage()
const settings = computed(() => page.props?.settings ?? {})
const brandingCssVariables = computed(() => buildBrandingCssVariables(settings.value))
const themePreset = computed(() => settings.value.theme_preset || 'corporate')
const brandInitials = computed(() => String(settings.value.app_name || settings.value.lab_name || 'Espaço laboratorial')
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word.charAt(0))
  .join('')
  .toUpperCase())
const profileHref = computed(() => props.auth?.user?.id
  ? route('users.edit', props.auth.user.id)
  : route('dashboard'))
const moduleFamily = computed(() => {
  const url = page.url || ''

  if (url.startsWith('/integration-hub')) {
    return 'integrations'
  }

  if (
    url.startsWith('/vap-inventory')
    || url.startsWith('/inventory')
    || url.startsWith('/itemcategories')
    || url.startsWith('/equipmentcategories')
    || url.startsWith('/itemstatuses')
    || url.startsWith('/iunits')
    || url.startsWith('/itypes')
    || url.startsWith('/ilocations')
    || url.startsWith('/ideliveries')
    || url.startsWith('/isuppliers')
    || url.startsWith('/supplier-assessments')
    || url.startsWith('/iwarehouses')
  ) {
    return 'inventory'
  }

  if (
    url.startsWith('/laboratory-workflow')
    || url.startsWith('/samples')
    || url.startsWith('/vap-samples')
    || url.startsWith('/directcollections')
    || url.startsWith('/programmedcollections')
    || url.startsWith('/analysis')
    || url.startsWith('/counter-analysis')
    || url.startsWith('/parameters')
    || url.startsWith('/analysiscategories')
    || url.startsWith('/profiles')
    || url.startsWith('/matrixes')
    || url.startsWith('/protocols')
    || url.startsWith('/standards')
    || url.startsWith('/nwps')
    || url.startsWith('/units')
    || url.startsWith('/temperatures')
    || url.startsWith('/environmental-conditions')
    || url.startsWith('/qualitycertificates')
    || url.startsWith('/import-certificates')
    || url.startsWith('/export-certificates')
    || url.startsWith('/occurrence')
    || url.startsWith('/vap-labs')
  ) {
    return 'sample-lifecycle'
  }

  if (
    url.startsWith('/invoices')
    || url.startsWith('/quotes')
    || url.startsWith('/creditnotes')
    || url.startsWith('/receipts')
    || url.startsWith('/currencies')
    || url.startsWith('/paymentcategories')
    || url.startsWith('/discountcategories')
    || url.startsWith('/taxtypes')
    || url.startsWith('/taxexemptions')
    || url.startsWith('/invoicecategories')
    || url.startsWith('/vap-proposals')
    || url.startsWith('/customers')
    || url.startsWith('/customercategories')
    || url.startsWith('/contactcategories')
    || url.startsWith('/warehouses')
  ) {
    return 'commercial'
  }

  if (
    url.startsWith('/products')
    || url.startsWith('/phytosanitary-products')
    || url.startsWith('/paid-services')
    || url.startsWith('/transportcategories')
    || url.startsWith('/vehicles')
    || url.startsWith('/faq')
    || url.startsWith('/contractguides')
    || url.startsWith('/collection')
    || url.startsWith('/packagingcategories')
    || url.startsWith('/customerrequest')
    || url.startsWith('/countries')
  ) {
    return 'operations'
  }

  if (
    url.startsWith('/file-manager')
    || url.startsWith('/users')
    || url.startsWith('/departments')
    || url.startsWith('/general-settings')
    || url.startsWith('/roles')
    || url.startsWith('/permissions')
    || url.startsWith('/security')
    || url.startsWith('/system-activity')
    || url.startsWith('/system-backups')
  ) {
    return 'admin'
  }

  return 'general'
})

const moduleFamilyLabels = {
  integrations: 'Integrações laboratoriais',
  inventory: 'Controlo de inventário',
  'sample-lifecycle': 'Ciclo de vida das amostras',
  commercial: 'Commercial ops',
  operations: 'Field operations',
  admin: 'Controlo do sistema',
  general: 'Operações laboratoriais',
}

const moduleFamilyLabel = computed(() => moduleFamilyLabels[moduleFamily.value] || moduleFamilyLabels.general)

// --- Session timeout ---
const timerDuration = 1500
const { count: countdown, dec, reset } = useCounter(timerDuration, { step: -1 })
const { idle } = useIdle({ timeout: 1000, emitOnIdle: true })
const showSessionModal = ref(false)
const remainingTime = ref(0)
let countdownInterval = null
const lastActive = ref(Date.now())

const startCountdown = () => {
  reset()
  if (countdownInterval) clearInterval(countdownInterval)
  countdownInterval = setInterval(() => {
    if (countdown.value <= 0) {
      clearInterval(countdownInterval)
    } else if (countdown.value <= 15) {
      showSessionModal.value = true
      remainingTime.value = countdown.value
    } else {
      showSessionModal.value = false
    }
    dec()
  }, 1000)
}

const resetTimerOnActivity = () => {
  reset()
  startCountdown()
  lastActive.value = Date.now()
}

// --- Language ---
const switchLanguage = async (language) => {
  await loadLanguageAsync(language)
  router.post(route('language.store'), { language }, { preserveState: false, preserveScroll: true, replace: true })
}

// --- Navigation (shared with side-nav) ---
const exportHubPermissions = [
  'export_activity_log', 'export_customers', 'export_warehouses', 'export_products', 'export_parameters',
  'export_profiles', 'export_matrixes', 'export_invoices', 'export_quotes', 'export_credit_notes',
  'export_receipts', 'export_contract_guides', 'export_import_certificates', 'export_export_certificates',
  'export_quality_certificates', 'export_customer_requests', 'export_occurrences',
]
const canUseExportHub = exportHubPermissions.some((permission) => hasPermission(permission))
  || ['view_analysis', 'view_results', 'view_samples', 'view_inventory', 'view_maintenance_tasks'].some((permission) => hasPermission(permission))

const navigation = [
  { title: 'gestlab.menu.dashboard', name: '/dashboard', href: route('dashboard'), icon: HomeIcon, show: true },
  { title: 'gestlab.menu.notifications', name: '/notifications', href: route('notifications.index'), icon: BellIcon, show: true },
  {
    title: 'gestlab.menu.admin_processes', name: 'Processos ADM.', icon: FolderOpenIcon, show: true,
    children: [
      { title: 'gestlab.menu.products', name: '/products', href: route('products.index'), show: hasPermission('view_products') },
      { title: 'gestlab.menu.phytosanitary_products', name: '/phytosanitary-products', href: route('phytosanitary_products.index'), show: hasPermission('view_phytosanitary_products') },
      { title: 'gestlab.menu.paid_services', name: '/paid-services', href: route('paidservices.index'), show: hasPermission('view_paid_services') },
      { title: 'gestlab.menu.trans_types', name: '/transportcategories', href: route('transportcategories.index'), show: hasPermission('view_trans_types') },
      { title: 'gestlab.menu.vehicles', name: '/vehicles', href: route('vehicles.index'), show: hasPermission('view_vehicles') },
      { title: 'gestlab.menu.faq_categories', name: '/faqcategories', href: route('faqcategories.index'), show: hasPermission('view_faq_categories') },
      { title: 'gestlab.menu.faqs', name: '/faqs', href: route('faqs.index'), show: hasPermission('view_faqs') },
      { title: 'gestlab.menu.faq_answers', name: '/faqanswers', href: route('faqanswers.index'), show: hasPermission('view_faq_answers') },
      { title: 'gestlab.menu.contract_guides', name: '/contractguides', href: route('contractguides.index'), show: hasPermission('view_contract_guides') },
      { title: 'gestlab.menu.direct_collections', name: '/directcollections', href: route('directcollections.index'), show: hasPermission('view_direct_collections') },
      { title: 'gestlab.menu.programmed_collections', name: '/programmedcollections', href: route('programmedcollections.index'), show: hasPermission('view_programmed_collections') },
      { title: 'gestlab.menu.collection_reasons', name: '/collectionreasons', href: route('collectionreasons.index'), show: hasPermission('view_collection_reasons') },
      { title: 'gestlab.menu.result_categories', name: '/resultcategories', href: route('resultcategories.index'), show: hasPermission('view_result_categories') },
      { title: 'gestlab.menu.collaboration_categories', name: '/collectioncollaborations', href: route('collectioncollaborations.index'), show: hasPermission('view_collaboration_categories') },
      { title: 'gestlab.menu.packaging_types', name: '/packagingcategories', href: route('packagingcategories.index'), show: hasPermission('view_packaging_types') },
      { title: 'gestlab.menu.request_categories', name: '/customerrequestcategories', href: route('customerrequestcategories.index'), show: hasPermission('view_request_categories') },
      { title: 'gestlab.menu.customer_requests', name: '/customerrequests', href: route('customerrequests.index'), show: hasPermission('view_customer_requests') },
      { title: 'gestlab.menu.collection_end_results', name: '/collectionendresults', href: route('collectionendresults.index'), show: hasPermission('view_collection_end_results') },
      { title: 'gestlab.menu.countries', name: '/countries', href: route('countries.index'), show: hasPermission('view_countries') },
    ],
  },
  {
    title: 'gestlab.menu.customers', name: 'customers', icon: UserGroupIcon, show: true,
    children: [
      { title: 'gestlab.menu.customer_categories', name: '/customercategories', href: route('customercategories.index'), show: hasPermission('view_customer_categories') },
      { title: 'gestlab.menu.contact_categories', name: '/contactcategories', href: route('contactcategories.index'), show: hasPermission('view_contact_categories') },
      { title: 'gestlab.menu.customers', name: '/customers', href: route('customers.index'), show: hasPermission('view_customers') },
      { title: 'gestlab.menu.warehouses', name: '/warehouses', href: route('warehouses.index'), show: hasPermission('view_warehouses') },
    ],
  },
  {
    title: 'gestlab.menu.invoicing', name: 'Invoicing', icon: BanknotesIcon, show: true,
    children: [
      { title: 'gestlab.menu.invoice_categories', name: '/invoicecategories', href: route('invoicecategories.index'), show: hasPermission('view_invoice_categories') },
      { title: 'gestlab.menu.proposal_templates', name: '/vap-proposals/templates', href: route('vap-proposals.templates.index'), show: hasPermission('view_proposal_templates') },
      { title: 'gestlab.menu.proposals', name: '/vap-proposals', href: route('vap-proposals.index'), show: hasPermission('view_proposals') },
      { title: 'gestlab.menu.invoices', name: '/invoices', href: route('invoices.index'), show: hasPermission('view_invoices') },
      { title: 'gestlab.menu.quotes', name: '/quotes', href: route('quotes.index'), show: hasPermission('view_quotes') },
      { title: 'gestlab.menu.credit_notes', name: '/creditnotes', href: route('creditnotes.index'), show: hasPermission('view_credit_notes') },
      { title: 'gestlab.menu.receipts', name: '/receipts', href: route('receipts.index'), show: hasPermission('view_receipts') },
      { title: 'gestlab.menu.currencies', name: '/currencies', href: route('currencies.index'), show: hasPermission('view_currencies') },
      { title: 'gestlab.menu.payment_categories', name: '/paymentcategories', href: route('paymentcategories.index'), show: hasPermission('view_payment_categories') },
      { title: 'gestlab.menu.discount_categories', name: '/discountcategories', href: route('discountcategories.index'), show: hasPermission('view_discount_categories') },
      { title: 'gestlab.menu.tax_types', name: '/taxtypes', href: route('taxtypes.index'), show: hasPermission('view_tax_types') },
      { title: 'gestlab.menu.tax_exemptions', name: '/taxexemptions', href: route('taxexemptions.index'), show: hasPermission('view_tax_exemptions') },
    ],
  },
  {
    title: 'gestlab.menu.tax_authority', name: 'AGT', icon: SwatchIcon, show: true,
    children: [
      { title: 'gestlab.menu.tax_exemptions', name: '/taxexemptions', href: route('taxexemptions.index'), show: hasPermission('view_tax_exemptions') },
      { title: 'Consulta de NIF', name: '/customers/tax-identification', href: route('customers.taxIdentification'), show: hasPermission('view_tax_exemptions') },
    ],
  },
  {
    title: 'gestlab.menu.analytical_processes', name: 'Processos Analíticos', icon: Square3Stack3DIcon, show: true,
    children: [
      { title: 'Fluxo laboratorial', name: '/laboratory-workflow', href: route('laboratory-workflow.index'), show: hasPermission('view_proposals') || hasPermission('view_samples') || hasPermission('view_analysis') || hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.parameters', name: '/parameters', href: route('parameters.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.analysis', name: '/analysis', href: route('analysis.index'), show: hasPermission('view_analysis') },
      { title: 'Dados laboratoriais', name: '/analysis/data-exports', href: route('analysis.data-exports.index'), show: hasPermission('view_analysis') || hasPermission('view_results') },
      { title: 'gestlab.menu.analysis_categories', name: '/analysiscategories', href: route('analysiscategories.index'), show: hasPermission('view_analysis_categories') },
      { title: 'gestlab.menu.pending_samples', name: '/vap-samples', href: route('vap_samples.index'), show: hasPermission('view_samples') },
      { title: 'gestlab.menu.sample_reports', name: '/vap-samples/reports', href: route('vap_samples.reports'), show: hasPermission('view_samples') },
      { title: 'gestlab.menu.internal_quality_control', name: '/vap-samples/reports', href: route('vap_samples.reports', { sample_scope: 'internal_qc' }), show: hasPermission('view_samples') },
      { title: 'gestlab.menu.counter_analysis', name: '/counter-analysis', href: route('counteranalysis.index'), show: hasPermission('view_counter_analysis') },
      { title: 'gestlab.menu.profiles', name: '/profiles', href: route('profiles.index'), show: hasPermission('view_profiles') },
      { title: 'gestlab.menu.matrixes', name: '/matrixes', href: route('matrixes.index'), show: hasPermission('view_matrixes') },
      { title: 'gestlab.menu.protocols', name: '/protocols', href: route('protocols.index'), show: hasPermission('view_protocols') },
      { title: 'gestlab.menu.standards', name: '/standards', href: route('standards.index'), show: hasPermission('view_standards') },
      { title: 'gestlab.menu.nwps', name: '/nwps', href: route('nwps.index'), show: hasPermission('view_nwps') },
      { title: 'gestlab.menu.units', name: '/units', href: route('units.index'), show: hasPermission('view_units') },
      { title: 'gestlab.menu.temperatures', name: '/temperatures', href: route('temperatures.index'), show: hasPermission('view_temperatures') },
      { title: 'Condições Ambientais', name: '/environmental-conditions', href: route('environmental-conditions.index'), show: hasPermission('view_temperatures') },
    ],
  },
  {
    title: 'gestlab.menu.analysis_reports', name: 'Boletins', icon: DocumentTextIcon, show: true,
    children: [
      { title: 'gestlab.menu.quality_certificates', name: '/qualitycertificates', href: route('qualitycertificates.index'), show: hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.import_certificates', name: '/import-certificates', href: route('importcertificates.index'), show: hasPermission('view_import_certificates') },
      { title: 'gestlab.menu.export_certificates', name: '/export-certificates', href: route('exportcertificates.index'), show: hasPermission('view_export_certificates') },
      { title: 'gestlab.menu.report_studios', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings') },
    ],
  },
  {
    title: 'gestlab.menu.occurrences', name: 'Ocorrências', icon: ExclamationTriangleIcon, show: true,
    children: [
      { title: 'gestlab.menu.occurrence_categories', name: '/occcurrencecategories', href: route('occurrencecategories.index'), show: hasPermission('view_occurrence_categories') },
      { title: 'gestlab.menu.occurrence_origins', name: '/occcurrenceorigins', href: route('occurrenceorigins.index'), show: hasPermission('view_occurrence_origins') },
      { title: 'gestlab.menu.occurrence_statuses', name: '/occurrencestatuses', href: route('occurrencestatuses.index'), show: hasPermission('view_occurrence_statuses') },
      { title: 'gestlab.menu.occurrences', name: '/occurrences', href: route('occurrences.index'), show: hasPermission('view_occurrences') },
      { title: 'Não conformidades laboratoriais', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') || hasPermission('view_activity_log') },
    ],
  },
  {
    title: 'gestlab.menu.inventory', name: 'Inventário', icon: InboxStackIcon, show: true,
    children: [
      { title: 'gestlab.menu.inventory', name: '/vap-inventory/items', href: route('vap-inventory.items.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.reagent_consumption', name: '/vap-inventory/reagents/consumption', href: route('vap-inventory.reagents.consumption.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.iequipments', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 1 }), show: hasPermission('view_iequipments') },
      { title: 'Integration Hub', name: '/integration-hub', href: route('integration-hub.index'), icon: ArrowsRightLeftIcon, show: hasPermission('view_iequipments') || hasPermission('view_settings') },
      { title: 'gestlab.menu.iitems', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 2 }), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.item_categories', name: '/itemcategories', href: route('itemcategories.index'), show: hasPermission('view_item_categories') },
      { title: 'gestlab.menu.equipment_categories', name: '/equipmentcategories', href: route('equipmentcategories.index'), show: hasPermission('view_equipment_categories') },
      { title: 'gestlab.menu.item_statuses', name: '/itemstatuses', href: route('itemstatuses.index'), show: hasPermission('view_item_statuses') },
      { title: 'gestlab.menu.iunits', name: '/iunits', href: route('iunits.index'), show: hasPermission('view_iunits') },
      { title: 'gestlab.menu.itypes', name: '/itypes', href: route('itypes.index'), show: hasPermission('view_itypes') },
      { title: 'gestlab.menu.ilocations', name: '/ilocations', href: route('ilocations.index'), show: hasPermission('view_ilocations') },
      { title: 'gestlab.menu.ideliveries', name: '/ideliveries', href: route('ideliveries.index'), show: hasPermission('view_ideliveries') },
      { title: 'gestlab.menu.iorders', name: '/vap-inventory/orders', href: route('vap-inventory.orders.index'), show: hasPermission('view_iorders') },
      { title: 'gestlab.menu.lab_needs', name: '/vap-inventory/needs', href: route('vap-inventory.needs.index'), show: hasPermission('view_iorders') },
      { title: 'gestlab.menu.isuppliers', name: '/isuppliers', href: route('isuppliers.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.itransfers', name: '/vap-inventory/transfers', href: route('vap-inventory.transfers.index'), show: hasPermission('view_itransfers') },
      { title: 'gestlab.menu.iwarehouses', name: '/iwarehouses', href: route('iwarehouses.index'), show: hasPermission('view_iwarehouses') },
      { title: 'gestlab.menu.inventory_analytics', name: '/vap-inventory/analytics', href: route('vap-inventory.analytics.index'), show: hasPermission('view_inventory') },
    ],
  },
  {
    title: 'gestlab.menu.maintenance_tasks', name: 'Manutenção', icon: WrenchScrewdriverIcon, show: true,
    children: [
      { title: 'gestlab.menu.maintenance_categories', name: '/maintenance/categories', href: route('vap-maintenance.categories'), show: hasPermission('view_maintenance_categories') },
      { title: 'gestlab.menu.maintenance_tasks', name: '/maintenance/tasks', href: route('vap-maintenance.tasks'), show: hasPermission('view_maintenance_tasks') },
    ],
  },
  {
    title: 'gestlab.menu.quality_compliance', name: 'qualidade', icon: ShieldCheckIcon, show: true,
    children: [
      { title: 'gestlab.menu.qms', name: '/qms', href: route('qms.index'), show: hasPermission('view_activity_log') },
      { title: 'gestlab.menu.staff_competence', name: '/users', href: route('users.index'), show: hasPermission('view_users') },
      { title: 'gestlab.menu.supplier_assessments', name: '/supplier-assessments', href: route('supplier-assessments.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.lab_non_conformities', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') || hasPermission('view_activity_log') },
      { title: 'gestlab.menu.responsibility_matrix', name: '/responsibility-matrix', href: route('responsibility-matrix.index'), show: hasPermission('view_users') },
      { title: 'gestlab.menu.uncertainty_sources', name: '/uncertainty-sources', href: route('uncertainty-sources.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.proficiency_tests', name: '/proficiency-tests', href: route('proficiency_tests.index'), show: hasPermission('view_analysis') },
      { title: 'gestlab.menu.report_studios', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings') },
    ],
  },
  {
    title: 'gestlab.menu.lab_operations', name: 'operacoes-laboratoriais', icon: BeakerIcon, show: true,
    children: [
      { title: 'gestlab.menu.labs', name: '/vap-labs/labs', href: route('vap-labs.labs.index'), show: hasPermission('view_departments') },
      { title: 'gestlab.menu.labels', name: '/vap-labels/labels', href: route('vap_labels.labels.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.document_manager', name: '/file-manager', href: route('file-manager'), show: hasPermission('view_documents') || hasPermission('view_activity_log') },
    ],
  },
  { title: 'gestlab.menu.users', name: '/users', href: route('users.index'), icon: UsersIcon, show: hasPermission('view_users') },
  { title: 'gestlab.menu.departments', name: '/departments', href: route('departments.index'), icon: RectangleStackIcon, show: hasPermission('view_departments') },
  { title: 'gestlab.menu.adverts', name: '/announcements', href: '#', icon: MegaphoneIcon, show: hasPermission('view_announcements') },
  { title: 'gestlab.menu.settings', name: '/general-settings', href: route('generalsettings.index'), icon: Cog6ToothIcon, show: hasPermission('view_settings') },
  { title: 'gestlab.menu.roles', name: '/roles', href: route('roles.index'), icon: UserIcon, show: hasPermission('view_roles') },
  { title: 'gestlab.menu.permissions', name: '/permissions', href: route('permissions.index'), icon: FingerPrintIcon, show: hasPermission('view_permissions') },
  { title: 'gestlab.menu.security', name: '/security', href: route('security'), icon: ShieldCheckIcon, show: true },
  { title: 'gestlab.menu.activity_log', name: '/system-activity', href: route('systemactivity.index'), icon: StopIcon, show: hasPermission('view_activity_log') },
  { title: 'Central de exportações', name: '/exports', href: route('exports.index'), icon: ArrowDownTrayIcon, show: canUseExportHub },
  { title: 'gestlab.menu.backups', name: '/system-backups/backups', href: route('systembackups.backups'), icon: ServerIcon, show: hasPermission('view_backups') },
]

const sidebarOpen = ref(false)
const desktopSidebarOpen = ref(true)
const commandPaletteOpen = ref(false)
const commandPaletteQuery = ref('')
const commandPaletteInput = ref(null)

const navLabel = (item) => {
  if (!item?.title) {
    return ''
  }

  return item.title.startsWith('gestlab.') ? trans(item.title) : item.title
}

const visibleChildren = (item) => (item.children || []).filter((child) => child.show && child.href && child.href !== '#')

const commandGroups = computed(() => navigation
  .filter((item) => item.show)
  .map((item) => {
    const children = visibleChildren(item)
    const items = children.length
      ? children
      : item.href && item.href !== '#'
        ? [item]
        : []

    return {
      label: navLabel(item),
      icon: item.icon,
      items: items.map((command) => ({
        href: command.href,
        icon: command.icon || item.icon,
        label: navLabel(command),
        path: command.name || item.name || '',
      })),
    }
  })
  .filter((group) => group.items.length > 0))

const normalizeSearchValue = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()

const filteredCommandGroups = computed(() => {
  const query = normalizeSearchValue(commandPaletteQuery.value.trim())

  return commandGroups.value
    .map((group) => {
      const items = query
        ? group.items.filter((command) => [
          command.label,
          command.path,
          group.label,
        ].some((value) => normalizeSearchValue(value).includes(query)))
        : group.items

      return {
        ...group,
        items,
      }
    })
    .filter((group) => group.items.length > 0)
})

const firstCommandPaletteResult = computed(() => filteredCommandGroups.value[0]?.items?.[0] ?? null)

const openCommandPalette = () => {
  commandPaletteOpen.value = true
  commandPaletteQuery.value = ''

  nextTick(() => {
    commandPaletteInput.value?.focus()
  })
}

const openCommandPaletteFromMobile = () => {
  sidebarOpen.value = false
  nextTick(openCommandPalette)
}

const visitCommand = (command) => {
  if (!command?.href || command.href === '#') {
    return
  }

  commandPaletteOpen.value = false
  commandPaletteQuery.value = ''
  router.visit(command.href)
}

const activateFirstCommandPaletteResult = () => {
  if (firstCommandPaletteResult.value) {
    visitCommand(firstCommandPaletteResult.value)
  }
}

const handleCommandPaletteShortcut = (event) => {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    openCommandPalette()
  }
}

const toggleDesktopSidebar = () => {
  desktopSidebarOpen.value = !desktopSidebarOpen.value
  window.localStorage.setItem('desktop-sidebar-open', desktopSidebarOpen.value ? '1' : '0')
}

onMounted(() => {
  const savedSidebarState = window.localStorage.getItem('desktop-sidebar-open')
  if (savedSidebarState !== null) {
    desktopSidebarOpen.value = savedSidebarState === '1'
  }

  window.addEventListener('keydown', handleCommandPaletteShortcut)

  startCountdown()

  watch(idle, (newIdleState) => {
    if (!newIdleState) resetTimerOnActivity()
  })

  const echo = getEcho()
  if (!echo) return
  const userId = usePage().props?.auth?.user?.id
  if (!userId) return

  echo.private(`users.${userId}`)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleCommandPaletteShortcut)

  if (countdownInterval) clearInterval(countdownInterval)
})
</script>
