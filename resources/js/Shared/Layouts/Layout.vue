<template>
  <div :style="brandingCssVariables" :data-theme-preset="themePreset">
    <backend-modal />
    <ToastList />

    <!-- Impersonation banner -->
    <div
      v-if="impersonation"
      class="relative isolate flex items-center gap-x-6 overflow-hidden bg-gray-50 px-6 py-2.5 sm:px-3.5 sm:before:flex-1"
    >
      <div
        class="absolute left-[max(-7rem,calc(50%-52rem))] top-1/2 -z-10 -translate-y-1/2 transform-gpu blur-2xl"
        aria-hidden="true"
      >
        <div
          class="aspect-[577/310] w-[36.0625rem] bg-gradient-to-r from-[#ff80b5] to-[#9089fc] opacity-30"
          style="clip-path: polygon(74.8% 41.9%, 97.2% 73.2%, 100% 34.9%, 92.5% 0.4%, 87.5% 0%, 75% 28.6%, 58.5% 54.6%, 50.1% 56.8%, 46.9% 44%, 48.3% 17.4%, 24.7% 53.9%, 0% 27.9%, 11.9% 74.2%, 24.9% 54.1%, 68.6% 100%, 74.8% 41.9%)"
        />
      </div>
      <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <p class="text-sm leading-6 text-gray-900">
          <strong class="font-semibold">{{ trans('gestlab.general.labels.impersonation.title') }}</strong>
          <svg viewBox="0 0 2 2" class="mx-2 inline h-0.5 w-0.5 fill-current" aria-hidden="true">
            <circle cx="1" cy="1" r="1" />
          </svg>
          {{ trans('gestlab.general.labels.impersonation.description') }} {{ auth?.user?.name }}.
        </p>
        <Link
          as="button"
          @click="router.get(route('users.stopimpersonating'), {}, { preserveState: false, replace: true })"
          class="flex-none rounded-full bg-gray-900 px-3.5 py-1 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900"
        >
          {{ trans('gestlab.general.buttons.leave_impersonation') }}
          <span aria-hidden="true">&rarr;</span>
        </Link>
      </div>
    </div>

    <!-- Mobile sidebar -->
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog as="div" class="relative z-50 lg:hidden" @close="sidebarOpen = false">
        <TransitionChild
          as="template"
          enter="transition-opacity ease-linear duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-900/80" />
        </TransitionChild>

        <div class="fixed inset-0 flex">
          <TransitionChild
            as="template"
            enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
              <TransitionChild
                as="template"
                enter="ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
              >
                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                  <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                    <span class="sr-only">Fechar menu lateral</span>
                    <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                  </button>
                </div>
              </TransitionChild>

              <div
                class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-900 px-4 pb-4 scrollbar-thin"
              >
                <div class="flex h-16 shrink-0 items-center">
<Link :href="route('dashboard')">
                <img
                  v-if="$page.props.settings?.logo_url"
                  class="h-12 w-auto"
                  :src="$page.props.settings.logo_url"
                  :alt="$page.props.settings?.app_name ?? ''"
                />
                <img
                  v-else
                  class="h-12 w-auto"
                  src="../../../images/sncqa_logo.svg"
                  alt=""
                />
            </Link>
                </div>
                <nav class="flex flex-1 flex-col">
                  <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                      <ul role="list" class="-mx-2 space-y-1">
                        <li v-for="item in navigation" :key="item.title">
                          <!-- Single link -->
                          <Link
                            prefetch
                            v-if="!item.children && item.show"
                            :href="item.href"
                            :class="[
                              $page.url === item.name
                                ? 'bg-primary-900 text-white dark:bg-primary-800/60 dark:text-white shadow-sm'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',
                              'group flex gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',
                            ]"
                          >
                            <component
                              :is="item.icon"
                              :class="[
                                $page.url === item.name
                                  ? 'text-white/90'
                                  : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500',
                                'h-5 w-5 shrink-0 transition-colors duration-150',
                              ]"
                              aria-hidden="true"
                            />
                            {{ $t(item.title) }}
                          </Link>

                          <!-- Expandable group -->
                          <Disclosure
                            as="div"
                            v-if="item.children && item.show"
                            :default-open="hasActiveChild(item)"
                            v-slot="{ open }"
                          >
                            <DisclosureButton
                              :class="[
                                hasActiveChild(item)
                                  ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50',
                                'group flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150',
                              ]"
                            >
                              <component
                                :is="item.icon"
                                :class="[
                                  hasActiveChild(item)
                                    ? 'text-primary-600 dark:text-primary-400'
                                    : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500',
                                  'h-5 w-5 shrink-0 transition-colors duration-150',
                                ]"
                                aria-hidden="true"
                              />
                              <span class="flex-1 text-left">{{ $t(item.title) }}</span>
                              <ChevronRightIcon
                                :class="[
                                  open ? 'rotate-90 text-gray-500' : 'text-gray-300 dark:text-gray-600',
                                  'h-4 w-4 shrink-0 transition-transform duration-200',
                                ]"
                              />
                            </DisclosureButton>

                            <DisclosurePanel as="ul" class="mt-1 space-y-0.5">
                              <li
                                v-for="subItem in item.children"
                                :key="subItem.title"
                              >
                                <Link
                                  prefetch
                                  v-if="subItem.show"
                                  :href="subItem.href"
                                  :class="[
                                    $page.url === subItem.name
                                      ? 'text-primary-700 bg-primary-50 font-semibold dark:text-primary-300 dark:bg-primary-900/20 border-l-2 border-primary-600'
                                      : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-800/30 border-l-2 border-transparent',
                                    'block w-full rounded-r-lg pl-10 pr-3 py-1.5 text-sm transition-all duration-150',
                                  ]"
                                >
                                  {{ $t(subItem.title) }}
                                </Link>
                              </li>
                            </DisclosurePanel>
                          </Disclosure>
                        </li>
                      </ul>
                    </li>
                    <!-- Logout -->
                    <li class="mt-auto">
                      <Link
                        :href="route('logout')"
                        class="group flex gap-x-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/10 transition-all duration-150"
                        method="post"
                        as="button"
                      >
                        <PowerIcon
                          class="h-5 w-5 shrink-0 text-gray-400 group-hover:text-red-500 dark:group-hover:text-red-400 transition-colors duration-150"
                          aria-hidden="true"
                        />
                        {{ $t('gestlab.menu.logout') }}
                      </Link>
                    </li>
                  </ul>
                </nav>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Desktop sidebar -->
    <div
      :class="[
        desktopSidebarOpen ? 'translate-x-0 opacity-100' : '-translate-x-[110%] opacity-0 pointer-events-none',
        'hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-80 lg:flex-col lg:px-4 lg:py-4 lg:transition-all lg:duration-300',
      ]"
    >
      <div
        class="flex grow flex-col gap-y-4 overflow-y-auto rounded-[2rem] border border-gray-200/80 bg-white/95 px-4 pb-4 pt-2 shadow-xl shadow-slate-900/5 backdrop-blur-md dark:border-gray-700/50 dark:bg-gray-900/95 scrollbar-thin"
      >
        <div class="flex h-16 shrink-0 items-center px-2">
<Link :href="route('dashboard')" class="flex items-center gap-3 transition-opacity hover:opacity-80">
                <img
                  v-if="$page.props.settings?.logo_url"
                  class="h-10 w-auto"
                  :src="$page.props.settings.logo_url"
                  :alt="$page.props.settings?.app_name ?? ''"
                />
                <img
                  v-else
                  class="h-10 w-auto"
                  src="../../../images/sncqa_logo.svg"
                  alt=""
                />
            </Link>
        </div>
        <side-nav />
      </div>
    </div>

    <!-- Main content area -->
    <div :class="desktopSidebarOpen ? 'lg:pl-80' : 'lg:pl-0'" class="transition-all duration-300">
      <!-- Topbar -->
      <div
        class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200/80 dark:border-gray-700/50 bg-white/95 backdrop-blur-md px-4 sm:gap-x-6 sm:px-6 lg:px-8 dark:bg-gray-900/95"
      >
        <button type="button" class="-m-2.5 p-2.5 text-gray-400 lg:hidden" @click="sidebarOpen = true">
          <span class="sr-only">Abrir menu lateral</span>
          <Bars3Icon class="h-6 w-6" aria-hidden="true" />
        </button>

        <div class="h-6 w-px bg-gray-200/80 lg:hidden dark:bg-gray-600" aria-hidden="true" />

        <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
          <div class="relative flex flex-1 items-center">
            <button
              type="button"
              class="hidden lg:inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-500 dark:hover:text-primary-300"
              :title="desktopSidebarOpen ? 'Ocultar menu lateral' : 'Mostrar menu lateral'"
              @click="toggleDesktopSidebar"
            >
              <Bars3Icon class="h-4 w-4" aria-hidden="true" />
              <span>{{ desktopSidebarOpen ? 'Ocultar menu' : 'Mostrar menu' }}</span>
            </button>

            <!-- Clock -->
            <div class="hidden lg:block ml-4">
              <div
                class="font-medium inline-flex items-center gap-1.5 px-3 py-1 leading-4 text-xs rounded-full text-primary-700 bg-primary-50 ring-1 ring-primary-200/50 dark:bg-primary-900/20 dark:text-primary-300 dark:ring-primary-700/30"
              >
                <p class="text-xs">{{ clockTime }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-x-4 lg:gap-x-6">
            <!-- Session timer -->
            <div class="hidden lg:block ml-4">
              <div
                v-if="!showSessionModal"
                class="font-medium inline-flex items-center gap-1.5 px-3 py-1 leading-4 text-xs rounded-full text-gray-600 bg-gray-100 ring-1 ring-gray-200/50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700/30"
              >
                <p class="text-xs">{{ 'Sessão Expira Em: ' + formattedTime }}</p>
              </div>
            </div>

            <!-- Notifications -->
            <Link
              prefetch
              :href="route('notifications.index')"
              class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500 relative"
            >
              <span class="sr-only">Ver notificações</span>
              <BellIcon class="h-6 w-6" aria-hidden="true" />
              <span
                v-if="auth?.user?.unread_notifications?.length"
                class="absolute top-0 right-0 flex h-3 w-3"
              >
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red opacity-75" />
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red" />
              </span>
            </Link>

            <!-- Theme toggle -->
            <button
              type="button"
              class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-150"
              :title="isDark ? 'Mudar para modo claro' : 'Mudar para modo escuro'"
              @click="toggleTheme"
            >
              <span class="sr-only">{{ isDark ? 'Mudar para modo claro' : 'Mudar para modo escuro' }}</span>
              <MoonIcon v-if="isDark" class="h-6 w-6" aria-hidden="true" />
              <SunIcon v-else class="h-6 w-6" aria-hidden="true" />
            </button>

            <!-- Language switcher -->
            <Dropdown>
              <template #icon>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-6 h-6 text-primary-800 dark:text-gray-400"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802"
                  />
                </svg>
              </template>
              <template #options>
                <MenuItem
                  v-for="language in $page.props.languages.data"
                  :key="language.value"
                  v-slot="{ active }"
                  as="a"
                >
                  <button
                    type="button"
                    @click="switchLanguage(language.value)"
                    :class="[
                      language.value === $page.props.language
                        ? 'bg-primary-900 text-white dark:bg-gray-700 dark:text-white'
                        : 'text-gray-700 dark:text-gray-400',
                      'block px-4 py-2 text-sm w-full text-left',
                    ]"
                  >
                    {{ language.label }}
                  </button>
                </MenuItem>
              </template>
            </Dropdown>

            <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true" />

            <!-- Profile dropdown -->
            <Menu as="div" class="relative">
              <MenuButton class="-m-1.5 flex items-center p-1.5">
                <span class="sr-only">Open user menu</span>
                <img
                  v-if="auth?.user?.profile_photo_url"
                  :src="auth?.user?.profile_photo_url"
                  alt=""
                  class="h-8 w-8 rounded-full bg-primary-800"
                />
                <span
                  v-else
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary-800 to-primary-900 ring-2 ring-white/80 dark:ring-gray-700 dark:from-primary-700 dark:to-primary-800"
                >
                  <span class="text-sm font-semibold leading-none text-white">
                    {{ auth?.user?.name?.charAt(0) }}
                  </span>
                </span>
                <span class="hidden lg:flex lg:items-center">
                  <span
                    class="ml-4 text-sm font-semibold leading-6 text-gray-900 dark:text-gray-400"
                    aria-hidden="true"
                  >
                    {{ auth?.user?.name }}
                  </span>
                  <ChevronDownIcon class="ml-2 h-5 w-5 text-gray-400" aria-hidden="true" />
                </span>
              </MenuButton>
              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems
                  class="absolute right-0 z-10 mt-2.5 w-40 origin-top-right rounded-xl bg-white dark:bg-gray-800 py-1.5 shadow-lg ring-1 ring-gray-900/5 dark:ring-gray-700/50 focus:outline-none"
                >
                  <MenuItem
                    v-for="item in userNavigation"
                    :key="item.name"
                    v-slot="{ active }"
                  >
                    <Link
                      :href="item.href"
                      as="button"
                      :class="[
                        active
                          ? 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-white'
                          : 'text-gray-700 dark:text-gray-300',
                        'block w-full text-left px-3 py-2 text-sm rounded-lg mx-1 transition-colors duration-150',
                      ]"
                      :method="item.method"
                    >
                      {{ $t(item.name) }}
                    </Link>
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </div>

<!-- Page content -->
  <main class="py-6 lg:py-8 animate-fade-in dark:bg-gray-900">
    <!-- Dashboard header image -->

    <!-- Dashboard header image -->
        <div v-if="route().current('dashboard')" class="-mt-12 pb-2">
          <div class="relative isolate overflow-hidden">
            <div class="bg-transparent dark:bg-gray-800">
              <div
                class="group relative overflow-hidden focus-within:ring-4 focus-within:ring-primary-500 focus-within:ring-opacity-50 focus-within:ring-offset-2 focus:outline-none focus:ring-4 focus:ring-primary-500 focus:ring-opacity-50 focus:ring-offset-2 dark:ring-offset-gray-900"
                tabindex="0"
              >
                <img
                  v-if="auth?.user?.dashboard_header_image"
                  :src="auth?.user?.dashboard_header_image"
                  alt=""
                  class="rounded-br-full object-cover w-full h-48"
                  loading="lazy"
                />
                <img
                  v-else
                  src="../../../../public/images/fruit3.jpg"
                  alt=""
                  class="rounded-br-full object-cover w-full h-48"
                  loading="lazy"
                />

                <div
                  class="absolute inset-0 flex -translate-y-full flex-col items-center justify-center bg-primary-800/80 dark:bg-gray-700/80 rounded-br-full opacity-0 transition duration-300 ease-out group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:translate-y-0 group-hover:opacity-100 group-focus:translate-y-0 group-focus:opacity-100"
                >
                  <div class="text-center">
                    <h4 class="text-lg font-semibold text-white">
                      {{ $t('gestlab.general.labels.dashboard.change_header_image') }}
                    </h4>
                    <p v-if="form.errors.photo" class="mt-2 text-sm text-red-600">
                      {{ form.errors.photo }}
                    </p>
                    <input ref="photoInput" type="file" class="hidden" @change="updateHeaderImage" />
                    <button @click="selectNewPhoto" class="mt-2">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="inline-block h-12 w-12 opacity-50 text-white"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"
                        />
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"
                        />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="px-4 sm:px-6 lg:px-8">
          <breadcrumbs
            v-if="$page.props.breadcrumbs?.length"
            :pages="$page.props.breadcrumbs"
            class="mb-4"
          />
          <!-- Session expiry modal -->
          <confirm-dialog
            v-if="showSessionModal"
            :open="showSessionModal"
            :title="$t('Session Expiring Soon')"
            :description="$t('You will be logged out due to inactivity')"
            variant="warning"
            :hide-buttons="true"
            size="sm:max-w-xl"
            @canceled="showSessionModal = false"
          >
            <div class="mt-4">
              <div
                class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-gray-700 sm:text-xs dark:bg-gray-700 dark:text-gray-400"
              >
                <p class="text-sm">
                  {{ $t('For your security, this session will end in :seconds seconds.', { seconds: remainingTime }) }}
                  {{ $t('Move your mouse or press any key to continue working.') }}
                </p>
              </div>
            </div>
          </confirm-dialog>

          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { useDateFormat, useTimestamp, useIdle, useCounter } from '@vueuse/core'
import sideNav from '../Navigation/side-nav.vue'
import toast from '@/Stores/toast'
import ToastList from '@/Components/toast-list.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import Dropdown from '@/Components/dropdown.vue'
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
  PowerIcon,
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
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { usePermission } from '@/Composables/usePermissions'
import { useTheme } from '@/Composables/useTheme'
import { trans, loadLanguageAsync } from 'laravel-vue-i18n'
import backendModal from '@/Components/backend-modal.vue'
import { getEcho } from '@/lib/echo'

const { hasPermission } = usePermission()

const props = defineProps({
  auth: Object,
  impersonation: Boolean,
})

const { isDark, toggle: toggleTheme } = useTheme(props.auth?.user?.theme)
const page = usePage()
const settings = computed(() => page.props?.settings ?? {})
const brandingCssVariables = computed(() => ({
  '--brand-primary': settings.value.primary_color || '#1f87e8',
  '--brand-secondary': settings.value.secondary_color || '#0f172a',
  '--brand-accent': settings.value.accent_color || '#14b8a6',
}))
const themePreset = computed(() => settings.value.theme_preset || 'corporate')

// --- Session timeout ---
const timerDuration = 1500
const { count: countdown, dec, reset } = useCounter(timerDuration, { step: -1 })
const { idle } = useIdle({ timeout: 1000, emitOnIdle: true })
const showSessionModal = ref(false)
const remainingTime = ref(0)
let countdownInterval = null
const lastActive = ref(Date.now())

const formattedTime = computed(() => {
  const h = String(Math.floor(countdown.value / 3600)).padStart(2, '0')
  const m = String(Math.floor((countdown.value % 3600) / 60)).padStart(2, '0')
  const s = String(countdown.value % 60).padStart(2, '0')
  return `${h}:${m}:${s}`
})

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

// --- Clock ---
const clockTime = useDateFormat(useTimestamp({ interval: 1000 }), 'HH:mm:ss', {
  locales: 'pt-BR',
})

// --- Header image ---
const photoInput = ref(null)
const form = useForm({ photo: null })
const selectNewPhoto = () => photoInput.value.click()
const updateHeaderImage = () => {
  if (photoInput.value) form.photo = photoInput.value.files[0]
  form.post(route('users.setDashboardHeader'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      if (photoInput.value?.value) photoInput.value.value = null
    },
  })
}

// --- Language ---
const switchLanguage = async (language) => {
  await loadLanguageAsync(language)
  router.post(route('language.store'), { language }, { preserveState: false, preserveScroll: true, replace: true })
}

// --- Navigation (shared with side-nav) ---
const hasActiveChild = (item) => {
  if (!item.children) return false
  return item.children.some((child) => page.url === child.name || page.url.startsWith(child.name + '/'))
}

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
      { title: 'gestlab.menu.proposal_templates', name: '/proposaltemplates', href: route('proposaltemplates.index'), show: hasPermission('view_proposal_templates') },
      { title: 'gestlab.menu.proposals', name: '/proposals', href: route('proposals.index'), show: hasPermission('view_proposals') },
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
      { title: 'gestlab.menu.parameters', name: '/parameters', href: route('parameters.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.analysis', name: '/analysis', href: route('analysis.index'), show: hasPermission('view_analysis') },
      { title: 'gestlab.menu.analysis_categories', name: '/analysiscategories', href: route('analysiscategories.index'), show: hasPermission('view_analysis_categories') },
      { title: 'gestlab.menu.pending_samples', name: '/samples', href: route('samples.index'), show: hasPermission('view_samples') },
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
      { title: 'gestlab.menu.quality_certificates', name: '/quality-certificates', href: route('qualitycertificates.index'), show: hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.import_certificates', name: '/import-certificates', href: route('importcertificates.index'), show: hasPermission('view_import_certificates') },
      { title: 'gestlab.menu.export_certificates', name: '/export-certificates', href: route('exportcertificates.index'), show: hasPermission('view_export_certificates') },
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
      { title: 'gestlab.menu.iitems', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 2 }), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.item_categories', name: '/itemcategories', href: route('itemcategories.index'), show: hasPermission('view_item_categories') },
      { title: 'gestlab.menu.equipment_categories', name: '/equipmentcategories', href: route('equipmentcategories.index'), show: hasPermission('view_equipment_categories') },
      { title: 'gestlab.menu.item_statuses', name: '/itemstatuses', href: route('itemstatuses.index'), show: hasPermission('view_item_statuses') },
      { title: 'gestlab.menu.iunits', name: '/iunits', href: route('iunits.index'), show: hasPermission('view_iunits') },
      { title: 'gestlab.menu.itypes', name: '/itypes', href: route('itypes.index'), show: hasPermission('view_itypes') },
      { title: 'gestlab.menu.ilocations', name: '/ilocations', href: route('ilocations.index'), show: hasPermission('view_ilocations') },
      { title: 'gestlab.menu.ideliveries', name: '/ideliveries', href: route('ideliveries.index'), show: hasPermission('view_ideliveries') },
      { title: 'gestlab.menu.iorders', name: '/vap-inventory/orders', href: route('vap-inventory.orders.index'), show: hasPermission('view_iorders') },
      { title: 'gestlab.menu.isuppliers', name: '/isuppliers', href: route('isuppliers.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.itransfers', name: '/vap-inventory/transfers', href: route('vap-inventory.transfers.index'), show: hasPermission('view_itransfers') },
      { title: 'gestlab.menu.iwarehouses', name: '/iwarehouses', href: route('iwarehouses.index'), show: hasPermission('view_iwarehouses') },
    ],
  },
  {
    title: 'gestlab.menu.maintenance_tasks', name: 'Manutenção', icon: WrenchScrewdriverIcon, show: true,
    children: [
      { title: 'gestlab.menu.maintenance_categories', name: '/maintenance/categories', href: route('vap-maintenance.categories'), show: hasPermission('view_maintenance_categories') },
      { title: 'gestlab.menu.maintenance_tasks', name: '/maintenance/tasks', href: route('vap-maintenance.tasks'), show: hasPermission('view_maintenance_tasks') },
    ],
  },
  { title: 'QMS', name: '/qms', href: route('qms.index'), icon: ShieldCheckIcon, show: hasPermission('view_activity_log') },
  { title: 'Competência do pessoal', name: '/users', href: route('users.index'), icon: UsersIcon, show: hasPermission('view_users') },
  { title: 'Avaliação de fornecedores', name: '/supplier-assessments', href: route('supplier-assessments.index'), icon: InboxStackIcon, show: hasPermission('view_isuppliers') },
  { title: 'gestlab.menu.users', name: '/users', href: route('users.index'), icon: UsersIcon, show: hasPermission('view_users') },
  { title: 'gestlab.menu.departments', name: '/departments', href: route('departments.index'), icon: RectangleStackIcon, show: hasPermission('view_departments') },
  { title: 'gestlab.menu.adverts', name: '/announcements', href: '#', icon: MegaphoneIcon, show: hasPermission('view_announcements') },
  { title: 'gestlab.menu.settings', name: '/general-settings', href: route('generalsettings.index'), icon: Cog6ToothIcon, show: hasPermission('view_settings') },
  { title: 'gestlab.menu.roles', name: '/roles', href: route('roles.index'), icon: UserIcon, show: hasPermission('view_roles') },
  { title: 'gestlab.menu.permissions', name: '/permissions', href: route('permissions.index'), icon: FingerPrintIcon, show: hasPermission('view_permissions') },
  { title: 'gestlab.menu.security', name: '/security', href: route('security'), icon: ShieldCheckIcon, show: true },
  { title: 'gestlab.menu.activity_log', name: '/system-activity', href: route('systemactivity.index'), icon: StopIcon, show: hasPermission('view_activity_log') },
  { title: 'gestlab.menu.backups', name: '/system-backups/backups', href: route('systembackups.backups'), icon: ServerIcon, show: hasPermission('view_backups') },
]

const userNavigation = [
  { name: 'gestlab.menu.logout', href: route('logout'), method: 'POST' },
]

const sidebarOpen = ref(false)
const desktopSidebarOpen = ref(true)

const toggleDesktopSidebar = () => {
  desktopSidebarOpen.value = !desktopSidebarOpen.value
  window.localStorage.setItem('desktop-sidebar-open', desktopSidebarOpen.value ? '1' : '0')
}

onMounted(() => {
  const savedSidebarState = window.localStorage.getItem('desktop-sidebar-open')
  if (savedSidebarState !== null) {
    desktopSidebarOpen.value = savedSidebarState === '1'
  }

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
  if (countdownInterval) clearInterval(countdownInterval)
})
</script>
