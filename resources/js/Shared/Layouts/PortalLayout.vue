<template>
  <div class="lims-app-shell ds-app-canvas" :style="brandingCssVariables" :data-theme-preset="themePreset">
    <Head :title="pageTitle" />
    <header class="sticky top-0 z-40 border-b border-[var(--pl-line)] bg-[var(--pl-bg)]">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
          <button type="button" class="ds-icon-button lg:hidden" :aria-label="labels.navigation" @click="sidebarOpen = true">
            <Bars3Icon class="h-5 w-5" aria-hidden="true" />
          </button>
          <Link :href="route('portal.home')" class="flex items-center gap-3">
            <img v-if="brandLogoUrl" class="h-6 w-auto max-w-16 object-contain sm:max-w-40" :src="brandLogoUrl" :alt="brandAppName">
            <BrandMark v-else :width="48" :data-initials="brandInitials" />
            <span class="hidden h-5 w-px bg-[var(--pl-line)] sm:block" aria-hidden="true" />
            <span class="sr-only sm:not-sr-only">
              <span class="block text-[15px] font-bold leading-tight tracking-[-0.02em] text-[var(--pl-fg)]">{{ brandAppName }}</span>
              <span class="pl-k pl-faint mt-1 block">{{ labels.portalArea }}</span>
            </span>
          </Link>
        </div>

        <div class="flex items-center gap-2">
          <div class="relative">
            <button type="button" class="ds-button ds-button-ghost" :aria-expanded="languageMenuOpen" @click="languageMenuOpen = !languageMenuOpen">
              <span>{{ activeLanguageLabel }}</span>
              <ChevronDownIcon class="h-4 w-4 text-[var(--ds-text-soft)]" aria-hidden="true" />
            </button>

            <div v-if="languageMenuOpen" class="ds-floating-panel absolute right-0 z-10 mt-1.5 w-44 p-1.5">
              <button
                v-for="language in page.props?.languages?.data ?? []"
                :key="language.value"
                type="button"
                class="app-menu-item hover:bg-[var(--ds-panel-muted)]"
                @click="switchLanguage(language.value)"
              >
                <span class="flex-1">{{ language.label }}</span>
                <span v-if="language.value === page.props?.language" class="h-1.5 w-1.5 bg-[var(--pl-accent-text)]" aria-hidden="true" />
              </button>
            </div>
          </div>

          <Link :href="route('portal.requests.index', { new: 1 })" class="ds-button ds-button-primary hidden md:inline-flex">
            {{ labels.newRequest }}
          </Link>

          <div class="relative">
            <button type="button" class="ds-button ds-button-secondary gap-2.5 py-1 pl-1 pr-2.5" :aria-expanded="profileMenuOpen" @click="profileMenuOpen = !profileMenuOpen">
              <span class="pl-avatar h-7 w-7">{{ portalAccount?.name?.charAt(0)?.toUpperCase() || 'C' }}</span>
              <span class="hidden max-w-40 truncate md:block">{{ portalAccount?.name || labels.customer }}</span>
              <ChevronDownIcon class="h-4 w-4 text-[var(--ds-text-soft)]" aria-hidden="true" />
            </button>

            <div v-if="profileMenuOpen" class="ds-floating-panel absolute right-0 mt-1.5 w-60 p-1.5">
              <p class="truncate px-2.5 pb-1.5 pt-1 text-xs text-[var(--ds-text-soft)]">{{ portalAccount.email }}</p>
              <Link :href="route('portal.profile')" class="app-menu-item hover:bg-[var(--ds-panel-muted)]" @click="profileMenuOpen = false">
                <UserCircleIcon aria-hidden="true" />
                {{ labels.portalProfile }}
              </Link>
              <Link :href="route('portal.security')" class="app-menu-item hover:bg-[var(--ds-panel-muted)]" @click="profileMenuOpen = false">
                <ShieldCheckIcon aria-hidden="true" />
                {{ labels.portalSecurity }}
              </Link>
              <div class="mt-1 border-t border-[var(--ds-border)] pt-1">
                <button type="button" class="app-menu-item app-menu-item-danger hover:bg-[var(--ds-panel-muted)]" @click="logout">
                  <PowerIcon aria-hidden="true" />
                  {{ labels.logout }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <Dialog as="div" class="lg:hidden" :open="sidebarOpen" @close="sidebarOpen = false">
      <div class="fixed inset-0 z-50">
        <div class="ds-modal-backdrop fixed inset-0" @click="sidebarOpen = false" />
        <DialogPanel class="ds-sidebar-panel fixed inset-y-0 left-0 flex w-full max-w-xs flex-col rounded-none border-0 border-r">
          <div class="flex items-center justify-between px-4 py-3">
            <div>
              <DialogTitle class="text-sm font-semibold text-[var(--ds-text)]">{{ labels.navigation }}</DialogTitle>
              <div class="text-xs text-[var(--ds-text-soft)]">{{ labels.portalArea }}</div>
            </div>
            <button type="button" class="ds-icon-button" :aria-label="labels.closeNavigation" @click="sidebarOpen = false">
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <nav class="grid flex-1 content-start gap-0.5 overflow-y-auto py-2">
            <Link
              v-for="item in navigation"
              :key="item.href"
              :href="item.href"
              class="ds-nav-item"
              :class="isActive(item) ? 'ds-nav-item-active' : ''"
              :aria-current="isActive(item) ? 'page' : undefined"
              @click="sidebarOpen = false"
            >
              {{ labels[item.key] }}
            </Link>
          </nav>
        </DialogPanel>
      </div>
    </Dialog>

    <div class="mx-auto flex max-w-7xl gap-9 px-4 pb-16 pt-9 sm:px-6 lg:px-8">
      <aside class="hidden w-60 shrink-0 lg:block">
        <div class="sticky top-20">
          <div class="flex items-center gap-2.5 px-2.5 pb-3">
            <span class="pl-avatar">{{ portalAccount?.name?.charAt(0)?.toUpperCase() || 'C' }}</span>
            <span class="min-w-0">
              <span class="block truncate text-[0.8125rem] font-medium leading-tight text-[var(--ds-text)]">{{ portalAccount.name || labels.customer }}</span>
              <span class="block truncate text-xs leading-tight text-[var(--ds-text-soft)]">{{ portalAccount.customer || portalAccount.email }}</span>
            </span>
          </div>

          <nav class="grid gap-0.5 border-y border-[var(--pl-line)] py-2" :aria-label="labels.navigation">
            <Link
              v-for="item in navigation"
              :key="item.href"
              :href="item.href"
              class="ds-nav-item"
              :class="isActive(item) ? 'ds-nav-item-active' : ''"
              :aria-current="isActive(item) ? 'page' : undefined"
            >
              {{ labels[item.key] }}
            </Link>
          </nav>
        </div>
      </aside>

      <main class="min-w-0 flex-1">
        <ToastList />
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import ToastList from '@/Components/toast-list.vue'
import { loadLanguageAsync } from 'laravel-vue-i18n'
import BrandMark from '@/Components/brand/BrandMark.vue'
import {
  Archive as ArchiveBoxIcon,
  Share as ArrowUpOnSquareIcon,
  Menu as Bars3Icon,
  Banknote as BanknotesIcon,
  FlaskConical as BeakerIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  ChevronDown as ChevronDownIcon,
  FileText as DocumentTextIcon,
  House as HomeModernIcon,
  Power as PowerIcon,
  CircleHelp as QuestionMarkCircleIcon,
  ShieldCheck as ShieldCheckIcon,
  Truck as TruckIcon,
  CircleUser as UserCircleIcon,
  Wrench as WrenchScrewdriverIcon,
  X as XMarkIcon,
} from '@lucide/vue'

const page = usePage()
const portalAccount = computed(() => page.props?.auth?.user?.data ?? page.props?.auth?.user ?? {})
const sidebarOpen = ref(false)
const profileMenuOpen = ref(false)
const languageMenuOpen = ref(false)

const dictionary = {
  en: {
    dashboard: 'Overview',
    services: 'Services',
    requests: 'Requests',
    collections: 'Collections',
    certificates: 'Certificates',
    invoices: 'Invoices',
    receipts: 'Receipts',
    quotes: 'Quotes',
    creditNotes: 'Credit notes',
    contractGuides: 'Contract guides',
    faq: 'FAQ',
    profile: 'Perfil',
    security: 'Segurança',
    newRequest: 'Novo pedido',
    portalProfile: 'Perfil do portal',
    portalSecurity: 'Definições de segurança',
    logout: 'Terminar sessão',
    navigation: 'Navigation',
    closeNavigation: 'Close navigation',
    portalArea: 'Portal do cliente',
    customer: 'Cliente',
  },
  pt: {
    dashboard: 'Resumo',
    services: 'Serviços',
    requests: 'Pedidos',
    collections: 'Colheitas',
    certificates: 'Certificados',
    invoices: 'Facturas',
    receipts: 'Recibos',
    quotes: 'Cotações',
    creditNotes: 'Notas de crédito',
    contractGuides: 'Guias contratuais',
    faq: 'FAQ',
    profile: 'Perfil',
    security: 'Segurança',
    newRequest: 'Novo pedido',
    portalProfile: 'Perfil do portal',
    portalSecurity: 'Definições de segurança',
    logout: 'Terminar sessão',
    navigation: 'Navegação',
    closeNavigation: 'Fechar navegação',
    portalArea: 'Área do cliente',
    customer: 'Cliente',
  },
}

const labels = computed(() => dictionary[page.props?.language] ?? dictionary.pt)
const settings = computed(() => page.props?.settings ?? {})
const themePreset = computed(() => settings.value.theme_preset || settings.value.app_theme_preset || 'corporate')
const brandLogoUrl = computed(() => settings.value.logo_url || settings.value.app_logo_url || null)
const brandAppName = computed(() => settings.value.app_name || 'Espaço laboratorial')
const brandInitials = computed(() => brandAppName.value
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word.charAt(0).toUpperCase())
  .join('') || 'LW')
const activeLanguageLabel = computed(() => {
  const active = page.props?.languages?.data?.find((item) => item.value === page.props?.language)

  return active?.label ?? String(page.props?.language || 'PT').toUpperCase()
})

const navigation = [
  { href: route('portal.home'), key: 'dashboard', icon: HomeModernIcon, match: '/portal/home' },
  { href: route('portal.services'), key: 'services', icon: WrenchScrewdriverIcon, match: '/portal/services' },
  { href: route('portal.requests.index'), key: 'requests', icon: ArrowUpOnSquareIcon, match: '/portal/requests' },
  { href: route('portal.collections'), key: 'collections', icon: TruckIcon, match: '/portal/collections' },
  { href: route('portal.qualitycertificates'), key: 'certificates', icon: BeakerIcon, match: '/portal/qualitycertificates' },
  { href: route('portal.invoices'), key: 'invoices', icon: BanknotesIcon, match: '/portal/invoices' },
  { href: route('portal.receipts'), key: 'receipts', icon: DocumentTextIcon, match: '/portal/receipts' },
  { href: route('portal.quotes'), key: 'quotes', icon: DocumentTextIcon, match: '/portal/quotes' },
  { href: route('portal.creditnotes'), key: 'creditNotes', icon: DocumentTextIcon, match: '/portal/creditnotes' },
  { href: route('portal.contractguides'), key: 'contractGuides', icon: ClipboardDocumentCheckIcon, match: '/portal/contractguides' },
  { href: route('portal.faqs'), key: 'faq', icon: QuestionMarkCircleIcon, match: '/portal/faqs' },
  { href: route('portal.profile'), key: 'profile', icon: UserCircleIcon, match: '/portal/profile' },
  { href: route('portal.security'), key: 'security', icon: ShieldCheckIcon, match: '/portal/security' },
]

const isActive = (item) => page.url.startsWith(item.match)
const pageTitle = computed(() => labels.value[navigation.find(isActive)?.key] || labels.value.portalArea)

const logout = () => {
  useForm({}).post(route('portal.logout'))
}

const switchLanguage = async (language) => {
  await loadLanguageAsync(language)
  languageMenuOpen.value = false

  router.post(route('language.store'), { language }, {
    preserveState: false,
    preserveScroll: true,
    replace: true,
  })
}

const closeMenus = (event) => {
  if (!event.target.closest('.relative')) {
    profileMenuOpen.value = false
    languageMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenus)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenus)
})
</script>
