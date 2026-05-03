<template>
  <header class="absolute inset-x-0 top-0 z-50 flex h-16 border-b border-gray-900/10">
    <ToastList/>

      <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex flex-1 items-center gap-x-6">
      <Link :href="route('portal.home')">
        <img class="h-12 w-auto" src="../../../images/vap_light.svg" alt="" />
      </Link>        
</div>
        
        <div class="flex flex-1 items-center justify-end gap-x-8">
          <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
            <span class="sr-only">View notifications</span>
            <BellIcon class="h-6 w-6" aria-hidden="true" />
          </button>
          <button type="button" class="-m-3 p-3 hidden:md" @click="open = !open">
            <span class="sr-only">Open main menu</span>
            <Bars3Icon class="h-5 w-5 text-gray-900" aria-hidden="true" />
          </button>
          <a href="#" class="-m-1.5 p-1.5">
            <span class="sr-only">Your profile</span>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-900">
              <span class="text-xl font-medium leading-none text-white">{{ $page?.props?.auth?.user?.name?.charAt(0) }}</span>
            </span>
          </a>
        </div>
      </div>
      <Dialog as="div" class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
        <div class="fixed inset-0 z-50" />
        <DialogPanel class="fixed inset-y-0 left-0 z-50 w-full overflow-y-auto bg-white px-4 pb-6 sm:max-w-sm sm:px-6 sm:ring-1 sm:ring-gray-900/10">
          <div class="-ml-0.5 flex h-16 items-center gap-x-6">
            <button type="button" class="-m-2.5 p-2.5 text-gray-700" @click="mobileMenuOpen = false">
              <span class="sr-only">Close menu</span>
              <XMarkIcon class="h-6 w-6" aria-hidden="true" />
            </button>
            <div class="-ml-0.5">
              <Link :href="route('portal.home')" class="-m-1.5 block p-1.5">
                <span class="sr-only">Your Company</span>
                <img class="h-12 w-auto" src="../../../images/vap_light.svg" alt="" />
              </Link>
            </div>
          </div>
          <div class="mt-2 space-y-2">
            <a v-for="item in navigation" :key="item.name" :href="item.href" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">{{ item.name }}</a>
          </div>
        </DialogPanel>
      </Dialog>
    </header>
  <div>
      <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
          <!-- Your content -->
          <slot />
        </div>
      </main>
    </div>

    <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-50" @close="open = false">
      <div class="fixed inset-0" />

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 left-0 flex max-w-full pr-10">
            <TransitionChild as="template" enter="transform transition ease-in-out duration-100 sm:duration-300" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-100 sm:duration-300" leave-from="translate-x-0" leave-to="-translate-x-full">
              <DialogPanel class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl rounded-br-full">
                  <div class="bg-blue-700 px-4 py-6 sm:px-6">
                    <div class="flex items-center justify-between">
                      <DialogTitle class="text-base font-semibold leading-6 text-white">{{ $t('gestlab.portal_menu.title') }}</DialogTitle>
                      <div class="ml-3 flex h-7 items-center">
                        <button type="button" class="relative rounded-md bg-blue-700 text-blue-200 hover:text-white focus:outline-none" @click="open = false">
                          <span class="absolute -inset-2.5" />
                          <span class="sr-only">Close panel</span>
                          <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                    <div class="mt-1">
                      <p class="text-sm text-blue-300">{{ $t('gestlab.portal_menu.description') }}</p>
                    </div>
                  </div>
                  <div class="relative flex-1 px-4 py-6 sm:px-6">
                    <nav class="flex flex-1 gap-y-7" aria-label="Sidebar">
                      <ul role="list" class="mx-2 space-y-1">
                        <li>
                <div class="text-xs font-semibold leading-6 text-gray-400"></div>

                <ul role="list" class="-mx-2 space-y-1">
                  <li v-for="item in navigation" :key="item.name">

                        <Link v-if="!item.children" :href="item.href" :class="[$page.url === item.name ? 'bg-blue-800 text-white' : 'text-gray-700 hover:text-white hover:bg-blue-800', 'group flex gap-x-3 rounded-full p-2 text-sm leading-6 font-semibold']">
                            <component :is="item.icon" :class="[$page.url === item.name ? 'text-white' : 'text-gray-400 group-hover:text-white', 'h-6 w-6 shrink-0']" aria-hidden="true" />
                            {{ $t(item.title) }}
                        </Link>
                        <Disclosure as="div" v-else class="space-y-1" v-slot="{ open }">
                            <DisclosureButton :class="[item.current ? 'bg-blue-800 text-white' : 'text-gray-700 hover:text-white hover:bg-blue-800', 'group flex gap-x-3 rounded-full p-2 text-sm leading-6 font-semibold']">
                            <component :is="item.icon" :class="[$page.url === item.name ? 'text-white' : 'text-gray-400 group-hover:text-white', 'h-6 w-6 shrink-0']" aria-hidden="true" />
                            <span class="flex-1">
                              {{ $t(item.title) }}
                            </span>
                            
                            <svg xmlns="http://www.w3.org/2000/svg" :class="[open ? 'text-gray-400 rotate-90' : 'text-gray-300', 'ml-3 flex-shrink-0 h-5 w-5 transform group-hover:text-white transition-colors ease-in-out duration-150']" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            </DisclosureButton>
                            <DisclosurePanel class="space-y-1">
                            <Link v-for="subItem in item.children" :key="subItem.title" as="a" :href="subItem.href" class="group w-full flex items-center pl-11 pr-2 py-2 text-sm font-medium text-gray-700 rounded-md hover:text-white hover:bg-blue-800 transform transition-all duration-200 hover:scale-125 focus:outline-none focus:ring-2 focus:ring-blue-800" :class="[$page.url === subItem.name ? 'text-blue-800 border-blue-800 border-l-4' : 'text-gray-400 group-hover:text-gray-500', 'mr-4 flex-shrink-0 h-6 w-6']">
                              {{ $t(subItem.title) }}
                            </Link>
                            </DisclosurePanel>
                        </Disclosure>
                      </li>
                      <hr>
                      <li>
                        <Link @click="logout" class="text-gray-700 hover:text-white hover:bg-blue-800 group flex gap-x-3 rounded-full p-2 text-sm leading-6 font-semibold">
                              <component :is="PowerIcon" class="text-gray-400 group-hover:text-white h-6 w-6 shrink-0" aria-hidden="true" />
                              {{ $t('gestlab.portal_menu.logout') }}
                        </Link>
                      </li>
                    </ul>
                  </li>
                        
                      </ul>
                    </nav>

                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { useNow, useDateFormat } from '@vueuse/core'
import { useForm, usePage } from '@inertiajs/vue3';
import ToastList from "@/Components/toast-list.vue";
import {
  Dialog,
  DialogPanel,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  Bars3Icon,
  BellIcon,
  XMarkIcon,
  CalendarIcon,
  ChartPieIcon,
  Cog6ToothIcon,
  DocumentDuplicateIcon,
  FolderIcon,
  HomeIcon,
  PresentationChartLineIcon,
  InboxIcon,
  ShieldCheckIcon,
  MegaphoneIcon,
  CheckBadgeIcon,
  SwatchIcon,
  CubeIcon,
  UsersIcon,
  ShoppingCartIcon,
  InformationCircleIcon,
  PowerIcon,
  FolderOpenIcon,
  BanknotesIcon,
  Square3Stack3DIcon,
  DocumentTextIcon,
  RectangleStackIcon,
  UserGroupIcon,
ChatBubbleLeftEllipsisIcon,
QuestionMarkCircleIcon,
ArrowUpOnSquareIcon,
HomeModernIcon,
DocumentIcon,
ClipboardDocumentCheckIcon,
ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import { ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import { router } from '@inertiajs/core';
// import ToastList from "../../Components/toast-list.vue";


const mobileMenuOpen = ref(false)

const sidebarOpen = ref(false)

const formatted = useDateFormat(useNow(), 'YYYY-MM-DD (ddd) HH:mm:ss', { locales: 'pt-BR' })

defineProps({
  auth: Object
  });

  const navigation = [
    { title: 'gestlab.portal_menu.dashboard', name: '/portal/home', href: route('portal.home'), icon: HomeModernIcon },
    { title: 'gestlab.portal_menu.quality_certificates', name: '/portal/qualitycertificates', href: route('portal.qualitycertificates'), icon: DocumentTextIcon },
    { title: 'gestlab.portal_menu.invoices', name: '/portal/invoices', href: route('portal.invoices'), icon: BanknotesIcon },
    { title: 'gestlab.portal_menu.receipts', name: '/portal/receipts', href: route('portal.receipts'), icon: DocumentIcon },
    { title: 'gestlab.portal_menu.quotes', name: '/portal/quotes', href: route('portal.quotes'), icon: DocumentIcon },
    { title: 'gestlab.portal_menu.credit_notes', name: '/portal/creditnotes', href: route('portal.creditnotes'), icon: DocumentIcon },
    { title: 'gestlab.portal_menu.contract_guides', name: '/portal/contractguides', href: route('portal.contractguides'), icon: ClipboardDocumentCheckIcon },
    { title: 'gestlab.portal_menu.collections', name: '/portal/collections', href: route('portal.collections'), icon: ArchiveBoxIcon },
    { title: 'gestlab.portal_menu.requests', name: '', href: route('portal.requests.index'), icon: ArrowUpOnSquareIcon },
    { title: 'gestlab.portal_menu.faqs', name: '/portal/faqs', href: route('portal.faqs'), icon: QuestionMarkCircleIcon },
    // { title: 'Submeter Feedback', name: '', href: '#', icon: ChatBubbleLeftEllipsisIcon },
  ]

  let logout = () => {
    
    useForm({}).post(route('portal.logout'), {
        
    })
}  
const open = ref(false)


</script>