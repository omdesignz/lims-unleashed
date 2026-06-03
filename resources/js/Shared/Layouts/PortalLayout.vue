<template>
  <div class="min-h-screen bg-[#f4efe4] text-[#15231f] dark:bg-[#07110f] dark:text-[#f7f1e7]" :style="brandingCssVariables" :data-theme-preset="themePreset">
    <header class="sticky top-0 z-40 border-b border-[#ded3bf]/80 bg-[#fffaf0]/92 shadow-[0_18px_45px_rgba(20,61,55,0.07)] backdrop-blur dark:border-[#25443c]/80 dark:bg-[#0c1714]/92 dark:shadow-black/20">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
          <button
            type="button"
            class="rounded-xl border border-[#ded3bf] bg-[#fffdf7] p-2 text-slate-600 lg:hidden dark:border-[#25443c] dark:bg-[#10231f] dark:text-slate-200"
            @click="sidebarOpen = true"
          >
            <Bars3Icon class="h-5 w-5" />
          </button>
          <Link :href="route('portal.home')" class="flex items-center gap-3">
            <img
              v-if="brandLogoUrl"
              class="h-10 w-auto max-w-40 object-contain"
              :src="brandLogoUrl"
              :alt="brandAppName"
            >
            <img
              v-else
              class="h-10 w-auto max-w-40 object-contain"
              src="../../../images/sncqa_logo.svg"
              :alt="brandAppName"
            >
            <div>
              <div class="text-sm font-extrabold uppercase tracking-[0.2em] text-primary-700 dark:text-accent-200">{{ brandAppName }}</div>
              <div class="text-sm text-slate-500 dark:text-slate-400">{{ labels.portalArea }}</div>
            </div>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative">
            <button
              type="button"
              class="flex items-center gap-2 rounded-2xl border border-[#ded3bf] bg-[#fffdf7] px-3 py-2 text-sm font-bold text-[#15231f] transition hover:border-primary-300 dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7]"
              @click="languageMenuOpen = !languageMenuOpen"
            >
              <span>{{ activeLanguageLabel }}</span>
              <ChevronDownIcon class="h-4 w-4 text-slate-400" />
            </button>

            <div v-if="languageMenuOpen" class="absolute right-0 z-10 mt-2 w-40 rounded-2xl border border-[#ded3bf] bg-[#fffaf0] p-2 shadow-[0_20px_55px_rgba(20,61,55,0.16)] dark:border-[#25443c] dark:bg-[#0c1714]">
              <button
                v-for="language in page.props?.languages?.data ?? []"
                :key="language.value"
                type="button"
                class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition"
                :class="language.value === page.props?.language ? 'bg-primary-50 text-primary-800 dark:bg-primary-950/60 dark:text-primary-200' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800'"
                @click="switchLanguage(language.value)"
              >
                <span>{{ language.label }}</span>
                <span v-if="language.value === page.props?.language" class="h-2 w-2 rounded-full bg-primary-600" />
              </button>
            </div>
          </div>

          <Link
            :href="route('portal.requests.index', { new: 1 })"
            class="hidden rounded-2xl bg-primary-700 px-4 py-2.5 text-sm font-bold text-white shadow-[0_16px_40px_rgba(20,61,55,0.18)] transition hover:bg-primary-800 md:inline-flex"
          >
            {{ labels.newRequest }}
          </Link>

          <div class="relative">
            <button
              type="button"
              class="flex items-center gap-3 rounded-2xl border border-[#ded3bf] bg-[#fffdf7] px-3 py-2 transition hover:border-primary-300 dark:border-[#25443c] dark:bg-[#10231f]"
              @click="profileMenuOpen = !profileMenuOpen"
            >
              <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white">
                {{ page.props?.auth?.user?.name?.charAt(0)?.toUpperCase() || 'C' }}
              </div>
              <div class="hidden text-left md:block">
                <div class="text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ page.props?.auth?.user?.name || labels.customer }}</div>
                <div class="text-xs text-slate-500">{{ page.props?.auth?.user?.email }}</div>
              </div>
              <ChevronDownIcon class="h-4 w-4 text-slate-400" />
            </button>

            <div v-if="profileMenuOpen" class="absolute right-0 mt-2 w-56 rounded-2xl border border-[#ded3bf] bg-[#fffaf0] p-2 shadow-[0_20px_55px_rgba(20,61,55,0.16)] dark:border-[#25443c] dark:bg-[#0c1714]">
              <Link
                :href="route('portal.profile')"
                class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#ede5d6] dark:text-slate-200 dark:hover:bg-[#10231f]"
                @click="profileMenuOpen = false"
              >
                <UserCircleIcon class="h-4 w-4" />
                {{ labels.portalProfile }}
              </Link>
              <Link
                :href="route('portal.security')"
                class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#ede5d6] dark:text-slate-200 dark:hover:bg-[#10231f]"
                @click="profileMenuOpen = false"
              >
                <ShieldCheckIcon class="h-4 w-4" />
                {{ labels.portalSecurity }}
              </Link>
              <button
                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40"
                @click="logout"
              >
                <PowerIcon class="h-4 w-4" />
                {{ labels.logout }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <Dialog as="div" class="lg:hidden" :open="sidebarOpen" @close="sidebarOpen = false">
      <div class="fixed inset-0 z-50">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="sidebarOpen = false" />
        <DialogPanel class="fixed inset-y-0 left-0 flex w-full max-w-xs flex-col border-r border-[#ded3bf] bg-[#fffaf0] shadow-xl dark:border-[#25443c] dark:bg-[#07110f]">
          <div class="flex items-center justify-between border-b border-[#ded3bf] px-5 py-4 dark:border-[#25443c]">
            <div>
              <div class="text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ labels.navigation }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ labels.portalArea }}</div>
            </div>
            <button type="button" class="rounded-xl border border-[#ded3bf] bg-[#fffdf7] p-2 text-slate-600 dark:border-[#25443c] dark:bg-[#10231f] dark:text-slate-300" @click="sidebarOpen = false">
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
            <Link
              v-for="item in navigation"
              :key="item.href"
              :href="item.href"
              class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
              :class="isActive(item) ? 'bg-primary-50 text-primary-800 dark:bg-primary-400/10 dark:text-accent-100' : 'text-slate-700 hover:bg-[#ede5d6] dark:text-slate-200 dark:hover:bg-[#10231f]'"
              @click="sidebarOpen = false"
            >
              <component :is="item.icon" class="h-5 w-5" />
              {{ labels[item.key] }}
            </Link>
          </nav>
        </DialogPanel>
      </div>
    </Dialog>

    <div class="mx-auto flex max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <aside class="hidden w-72 shrink-0 lg:block">
        <div class="sticky top-24 rounded-[2rem] border border-[#ded3bf] bg-[#fffaf0]/92 p-4 shadow-[0_24px_70px_rgba(20,61,55,0.10)] backdrop-blur dark:border-[#25443c] dark:bg-[#0c1714]/92 dark:shadow-black/25">
          <div class="rounded-[1.5rem] bg-gradient-to-br from-primary-700 to-primary-950 p-4 text-white shadow-[0_18px_45px_rgba(20,61,55,0.22)]">
            <div class="text-sm font-bold">{{ page.props?.auth?.user?.name || labels.customer }}</div>
            <div class="mt-1 text-xs text-slate-300">{{ page.props?.auth?.user?.customer || page.props?.auth?.user?.email }}</div>
          </div>

          <nav class="mt-4 space-y-1">
            <Link
              v-for="item in navigation"
              :key="item.href"
              :href="item.href"
              class="flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition"
              :class="isActive(item) ? 'bg-primary-50 text-primary-800 dark:bg-primary-400/10 dark:text-accent-100' : 'text-slate-700 hover:bg-[#ede5d6] dark:text-slate-200 dark:hover:bg-[#10231f]'"
            >
              <component :is="item.icon" class="h-5 w-5" />
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
import { Dialog, DialogPanel } from '@headlessui/vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import ToastList from '@/Components/toast-list.vue'
import { loadLanguageAsync } from 'laravel-vue-i18n'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'
import {
  ArchiveBoxIcon,
  ArrowUpOnSquareIcon,
  Bars3Icon,
  BanknotesIcon,
  BeakerIcon,
  ClipboardDocumentCheckIcon,
  ChevronDownIcon,
  DocumentTextIcon,
  HomeModernIcon,
  PowerIcon,
  QuestionMarkCircleIcon,
  ShieldCheckIcon,
  TruckIcon,
  UserCircleIcon,
  WrenchScrewdriverIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
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
    contractGuides: 'Contract guides',
    faq: 'FAQ',
    profile: 'Profile',
    security: 'Security',
    newRequest: 'New request',
    portalProfile: 'Portal profile',
    portalSecurity: 'Security settings',
    logout: 'Sign out',
    navigation: 'Navigation',
    portalArea: 'Customer portal',
    customer: 'Customer',
  },
  pt: {
    dashboard: 'Resumo',
    services: 'Serviços',
    requests: 'Solicitações',
    collections: 'Colheitas',
    certificates: 'Certificados',
    invoices: 'Faturas',
    receipts: 'Recibos',
    contractGuides: 'Guias contratuais',
    faq: 'FAQ',
    profile: 'Perfil',
    security: 'Segurança',
    newRequest: 'Nova solicitação',
    portalProfile: 'Perfil do portal',
    portalSecurity: 'Definições de segurança',
    logout: 'Terminar sessão',
    navigation: 'Navegação',
    portalArea: 'Área do cliente',
    customer: 'Cliente',
  },
}

const labels = computed(() => dictionary[page.props?.language] ?? dictionary.pt)
const settings = computed(() => page.props?.settings ?? {})
const brandingCssVariables = computed(() => buildBrandingCssVariables(settings.value))
const themePreset = computed(() => settings.value.theme_preset || settings.value.app_theme_preset || 'corporate')
const brandLogoUrl = computed(() => settings.value.logo_url || settings.value.app_logo_url || null)
const brandAppName = computed(() => settings.value.app_name || 'LIMS Unleashed')
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
  { href: route('portal.contractguides'), key: 'contractGuides', icon: ClipboardDocumentCheckIcon, match: '/portal/contractguides' },
  { href: route('portal.faqs'), key: 'faq', icon: QuestionMarkCircleIcon, match: '/portal/faqs' },
  { href: route('portal.profile'), key: 'profile', icon: UserCircleIcon, match: '/portal/profile' },
  { href: route('portal.security'), key: 'security', icon: ShieldCheckIcon, match: '/portal/security' },
]

const isActive = (item) => page.url.startsWith(item.match)

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
