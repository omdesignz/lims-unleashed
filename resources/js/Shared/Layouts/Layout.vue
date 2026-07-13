<template>
  <div class="lims-app-shell ds-app-canvas" :style="brandingCssVariables" :data-theme-preset="themePreset">
    <backend-modal />
    <ToastList />

    <!-- Impersonation banner -->
    <div
      v-if="impersonation"
      class="ds-impersonation-banner relative isolate flex items-center gap-x-6 px-6 py-2.5 sm:px-3.5 sm:before:flex-1"
    >
      <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <p class="text-sm font-medium leading-6">
          <strong class="font-semibold">{{ trans('gestlab.general.labels.impersonation.title') }}</strong>
          <svg viewBox="0 0 2 2" class="mx-2 inline h-0.5 w-0.5 fill-current" aria-hidden="true">
            <circle cx="1" cy="1" r="1" />
          </svg>
          {{ trans('gestlab.general.labels.impersonation.description') }} {{ auth?.user?.name }}.
        </p>
        <Link
          as="button"
          @click="router.get(route('users.stopimpersonating'), {}, { preserveState: false, replace: true })"
          class="ds-button ds-button-primary min-h-0 flex-none rounded-full px-3.5 py-1.5"
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
                class="ds-sidebar-panel flex grow flex-col gap-y-5 overflow-y-auto rounded-none border-l-0 border-y-0 px-4 pb-4 scrollbar-thin"
              >
                <div class="flex h-16 shrink-0 items-center">
                  <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3">
                    <span v-if="!$page.props.settings?.logo_url" class="lims-brand-mark">
                      {{ settings?.app_name?.slice(0, 2).toUpperCase() || 'LU' }}
                    </span>
                    <img
                      v-else
                      class="max-h-9 max-w-36 rounded bg-white/95 object-contain px-2 py-1"
                      :src="$page.props.settings.logo_url"
                      :alt="$page.props.settings?.app_name ?? ''"
                    />
                    <span class="min-w-0">
                      <span class="block truncate text-sm font-semibold text-white">{{ settings?.app_name || 'LIMS Unleashed' }}</span>
                      <span class="block truncate font-mono text-[0.65rem] uppercase text-slate-400">ISO 17025 workspace</span>
                    </span>
                  </Link>
                </div>
                <side-nav class="relative" />
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
        class="ds-sidebar-panel relative flex grow flex-col gap-y-4 overflow-y-auto px-4 pb-4 pt-2 scrollbar-thin"
      >
        <div class="flex h-16 shrink-0 items-center px-2">
          <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3 transition-opacity hover:opacity-90">
            <span v-if="!$page.props.settings?.logo_url" class="lims-brand-mark">
              {{ settings?.app_name?.slice(0, 2).toUpperCase() || 'LU' }}
            </span>
            <img
              v-else
              class="max-h-9 max-w-36 rounded bg-white/95 object-contain px-2 py-1"
              :src="$page.props.settings.logo_url"
              :alt="$page.props.settings?.app_name ?? ''"
            />
            <span class="min-w-0">
              <span class="block truncate text-sm font-semibold text-white">{{ settings?.app_name || 'LIMS Unleashed' }}</span>
              <span class="block truncate font-mono text-[0.65rem] uppercase text-slate-400">ISO 17025 workspace</span>
            </span>
          </Link>
        </div>
        <div class="lims-sidebar-meta mx-2 grid grid-cols-2 gap-2 p-3 text-xs">
          <div>
            <p class="font-mono text-[0.62rem] uppercase tracking-[0.12em] text-slate-500">Scope</p>
            <p class="mt-1 truncate font-semibold text-slate-200">{{ moduleFamilyLabel }}</p>
          </div>
          <div>
            <p class="font-mono text-[0.62rem] uppercase tracking-[0.12em] text-slate-500">Session</p>
            <p class="mt-1 font-semibold text-slate-200">{{ formattedTime }}</p>
          </div>
        </div>
        <side-nav class="relative" />
      </div>
    </div>

    <!-- Main content area -->
    <div :class="desktopSidebarOpen ? 'lg:pl-80' : 'lg:pl-0'" class="min-h-screen transition-all duration-300">
      <!-- Topbar -->
      <div
        class="ds-topbar sticky top-0 z-40 mx-0 flex min-h-16 shrink-0 items-center gap-x-4 border-b px-4 py-2 sm:gap-x-6 sm:px-6 lg:top-3 lg:mx-4 lg:rounded-[0.75rem] lg:border lg:px-4"
      >
        <button type="button" class="-m-2.5 p-2.5 text-gray-400 lg:hidden" @click="sidebarOpen = true">
          <span class="sr-only">Abrir menu lateral</span>
          <Bars3Icon class="h-6 w-6" aria-hidden="true" />
        </button>

        <div class="h-6 w-px bg-gray-200/80 lg:hidden dark:bg-gray-600" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 gap-x-4 self-stretch lg:gap-x-5">
          <div class="relative flex min-w-0 flex-1 items-center gap-3">
            <button
              type="button"
              class="ds-button ds-button-secondary hidden min-h-0 px-2.5 py-1.5 text-xs lg:inline-flex"
              :title="desktopSidebarOpen ? 'Ocultar menu lateral' : 'Mostrar menu lateral'"
              @click="toggleDesktopSidebar"
            >
              <Bars3Icon class="h-4 w-4" aria-hidden="true" />
            </button>

            <button
              type="button"
              class="hidden min-w-0 flex-1 items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-2 text-left shadow-[var(--ds-shadow-control)] transition hover:border-[var(--ds-border-strong)] hover:bg-[var(--ds-panel-subtle)] focus:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)] xl:flex"
              @click="openCommandPalette"
            >
              <MagnifyingGlassIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
              <span class="truncate text-sm font-medium text-[var(--ds-text-muted)]">
                Buscar amostras, certificados, instrumentos ou clientes
              </span>
              <span class="ml-auto rounded border border-[var(--ds-border)] px-1.5 py-0.5 font-mono text-[0.63rem] font-semibold uppercase text-[var(--ds-text-soft)]">
                cmd k
              </span>
            </button>

            <span class="lims-module-chip hidden md:inline-flex">
              <span class="lims-status-dot lims-status-dot-instrument" aria-hidden="true" />
              {{ moduleFamilyLabel }}
            </span>

            <!-- Clock -->
            <div class="hidden lg:block">
              <div
                class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-2.5 py-1.5 font-mono text-xs font-semibold leading-4 text-[var(--ds-text-muted)]"
              >
                <p class="text-xs">{{ clockTime }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-x-4 lg:gap-x-6">
            <!-- Session timer -->
            <div class="hidden lg:block">
              <div
                v-if="!showSessionModal"
                class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-2.5 py-1.5 font-mono text-xs font-semibold leading-4 text-[var(--ds-text-muted)]"
              >
                <p class="text-xs">{{ formattedTime }}</p>
              </div>
            </div>

            <!-- Notifications -->
            <Link
              prefetch
              :href="route('notifications.index')"
              class="relative rounded-lg p-2.5 text-[var(--ds-text-soft)] transition hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]"
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
              class="rounded-lg p-2.5 text-[var(--ds-text-soft)] transition-colors duration-150 hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]"
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
                  class="h-6 w-6 text-[var(--ds-text-soft)]"
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
                        ? 'bg-[rgb(var(--primary-700-rgb))] text-white dark:bg-[rgb(var(--primary-400-rgb)/0.18)] dark:text-white'
                        : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]',
                      'block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold transition',
                    ]"
                  >
                    {{ language.label }}
                  </button>
                </MenuItem>
              </template>
            </Dropdown>

            <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-[var(--ds-border)]" aria-hidden="true" />

            <!-- Profile dropdown -->
            <Menu as="div" class="relative">
              <MenuButton class="-m-1.5 flex items-center p-1.5">
                <span class="sr-only">Abrir menu do utilizador</span>
                <img
                  v-if="auth?.user?.profile_photo_url"
                  :src="auth?.user?.profile_photo_url"
                  alt=""
                  class="h-8 w-8 rounded-full bg-primary-800"
                />
                <span
                  v-else
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[rgb(var(--primary-800-rgb))] ring-2 ring-white/80 dark:bg-[rgb(var(--primary-400-rgb)/0.18)] dark:ring-[var(--ds-border)]"
                >
                  <span class="text-sm font-semibold leading-none text-white">
                    {{ auth?.user?.name?.charAt(0) }}
                  </span>
                </span>
                <span class="hidden lg:flex lg:items-center">
                  <span
                    class="ml-4 text-sm font-bold leading-6 text-[var(--ds-text)]"
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
                  class="ds-floating-panel absolute right-0 z-10 mt-2.5 w-48 origin-top-right p-1.5 focus:outline-none"
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
                          ? 'bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.14)] dark:text-white'
                          : 'text-[var(--ds-text-muted)]',
                        'block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold transition-colors duration-150',
                      ]"
                      :method="item.method"
                    >
                      {{ item.label || $t(item.name) }}
                    </Link>
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </div>

      <TransitionRoot as="template" :show="commandPaletteOpen">
        <Dialog as="div" class="relative z-[70]" @close="commandPaletteOpen = false">
          <TransitionChild
            as="template"
            enter="ease-out duration-200"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="ease-in duration-150"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <div class="fixed inset-0 bg-slate-950/55 backdrop-blur-sm" />
          </TransitionChild>

          <div class="fixed inset-0 z-[70] overflow-y-auto p-4 sm:p-6 md:p-20">
            <TransitionChild
              as="template"
              enter="ease-out duration-200"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="ease-in duration-150"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="ds-command-palette mx-auto max-w-2xl overflow-hidden">
                <div class="flex items-center gap-3 border-b border-[var(--ds-border)] px-4">
                  <MagnifyingGlassIcon class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
                  <input
                    ref="commandPaletteInput"
                    v-model="commandPaletteQuery"
                    type="search"
                    class="h-14 min-w-0 flex-1 border-0 bg-transparent text-sm font-semibold text-[var(--ds-text)] outline-none placeholder:text-[var(--ds-text-soft)] focus:ring-0"
                    placeholder="Buscar amostras, boletins, instrumentos, clientes..."
                    @keydown.enter.prevent="activateFirstCommandPaletteResult"
                  />
                  <kbd class="hidden rounded border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2 py-1 font-mono text-[0.65rem] font-semibold uppercase text-[var(--ds-text-soft)] sm:inline-flex">
                    esc
                  </kbd>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-2 sm:max-h-[32rem]">
                  <div v-if="filteredCommandGroups.length" class="space-y-3">
                    <section v-for="group in filteredCommandGroups" :key="group.label">
                      <div class="flex items-center justify-between px-3 py-2">
                        <p class="font-mono text-[0.68rem] font-bold uppercase text-[var(--ds-text-soft)]">
                          {{ group.label }}
                        </p>
                        <span class="rounded-full bg-[var(--ds-panel-muted)] px-2 py-0.5 font-mono text-[0.62rem] font-semibold text-[var(--ds-text-soft)]">
                          {{ group.items.length }}
                        </span>
                      </div>
                      <button
                        v-for="command in group.items"
                        :key="`${group.label}-${command.href}-${command.label}`"
                        type="button"
                        class="ds-command-palette-item group"
                        @click="visitCommand(command)"
                      >
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-soft)] group-hover:border-[rgb(var(--primary-300-rgb))] group-hover:text-[rgb(var(--primary-700-rgb))] dark:group-hover:border-[rgb(var(--primary-400-rgb)/0.35)] dark:group-hover:text-[rgb(var(--primary-200-rgb))]">
                          <component :is="command.icon" class="h-4 w-4" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1 text-left">
                          <span class="block truncate text-sm font-semibold text-[var(--ds-text)]">
                            {{ command.label }}
                          </span>
                          <span class="block truncate font-mono text-[0.68rem] uppercase text-[var(--ds-text-soft)]">
                            {{ command.path }}
                          </span>
                        </span>
                        <ChevronRightIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)] transition group-hover:translate-x-0.5 group-hover:text-[var(--ds-text-muted)]" aria-hidden="true" />
                      </button>
                    </section>
                  </div>

                  <div v-else class="px-6 py-14 text-center">
                    <div class="mx-auto grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
                      <MagnifyingGlassIcon class="h-5 w-5 text-[var(--ds-text-soft)]" aria-hidden="true" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-[var(--ds-text)]">Nenhum módulo encontrado</p>
                    <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">
                      Tente pesquisar por amostras, inventário, boletins, clientes ou qualidade.
                    </p>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 font-mono text-[0.68rem] font-semibold uppercase text-[var(--ds-text-soft)]">
                  <span class="lims-status-dot lims-status-dot-instrument" aria-hidden="true" />
                  {{ filteredCommandGroups.length }} grupos disponíveis
                  <span class="ml-auto hidden sm:inline">Enter para abrir</span>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </Dialog>
      </TransitionRoot>

<!-- Page content -->
  <main class="min-h-[calc(100vh-4rem)] py-5 animate-fade-in lg:py-7">
        <div v-if="route().current('dashboard')" class="px-4 pb-4 sm:px-6 lg:px-8">
          <div class="lims-status-strip grid gap-3 px-4 py-3 sm:grid-cols-2 lg:grid-cols-4">
            <div
              v-for="status in operationalStatus"
              :key="status.label"
              class="flex items-center gap-3"
            >
              <span :class="['lims-status-dot', status.dot]" aria-hidden="true" />
              <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ status.label }}</span>
                <span class="block truncate font-mono text-[0.68rem] uppercase text-[var(--ds-text-soft)]">{{ status.caption }}</span>
              </span>
            </div>
          </div>
        </div>

        <div
          class="px-4 sm:px-6 lg:px-8 lims-backoffice-content"
          :data-module-family="moduleFamily"
        >
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
import { computed, nextTick, ref, watch, onMounted, onUnmounted } from 'vue'
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
  BeakerIcon,
  ChevronRightIcon,
  MagnifyingGlassIcon,
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
const moduleFamily = computed(() => {
  const url = page.url || ''

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
    url.startsWith('/samples')
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
  inventory: 'Inventory control',
  'sample-lifecycle': 'Sample lifecycle',
  commercial: 'Commercial ops',
  operations: 'Field operations',
  admin: 'System control',
  general: 'Laboratory ops',
}

const moduleFamilyLabel = computed(() => moduleFamilyLabels[moduleFamily.value] || moduleFamilyLabels.general)

const operationalStatus = computed(() => [
  {
    label: 'Sample intake',
    caption: moduleFamily.value === 'sample-lifecycle' ? 'active queue' : 'ready',
    dot: moduleFamily.value === 'sample-lifecycle' ? 'lims-status-dot-release' : 'lims-status-dot-instrument',
  },
  {
    label: 'Result review',
    caption: 'verification track',
    dot: 'lims-status-dot-instrument',
  },
  {
    label: 'Document control',
    caption: moduleFamily.value === 'admin' ? 'audit mode' : 'released',
    dot: moduleFamily.value === 'admin' ? 'lims-status-dot-hold' : 'lims-status-dot-release',
  },
  {
    label: 'QMS watch',
    caption: 'deviation monitor',
    dot: 'lims-status-dot-critical',
  },
])

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
  locales: 'pt-Pt',
})

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
      { title: 'gestlab.menu.parameters', name: '/parameters', href: route('parameters.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.analysis', name: '/analysis', href: route('analysis.index'), show: hasPermission('view_analysis') },
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
      { title: 'gestlab.menu.quality_certificates', name: '/quality-certificates', href: route('qualitycertificates.index'), show: hasPermission('view_quality_certificates') },
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
  { title: 'gestlab.menu.backups', name: '/system-backups/backups', href: route('systembackups.backups'), icon: ServerIcon, show: hasPermission('view_backups') },
]

const userNavigation = [
  { label: 'Manual do utilizador', name: 'users.help', href: route('users.help') },
  { name: 'gestlab.menu.logout', href: route('logout'), method: 'POST' },
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

const filteredCommandGroups = computed(() => {
  const query = commandPaletteQuery.value.trim().toLowerCase()

  return commandGroups.value
    .map((group) => {
      const items = query
        ? group.items.filter((command) => [
          command.label,
          command.path,
          group.label,
        ].some((value) => String(value || '').toLowerCase().includes(query)))
        : group.items

      return {
        ...group,
        items: items.slice(0, 8),
      }
    })
    .filter((group) => group.items.length > 0)
    .slice(0, 8)
})

const firstCommandPaletteResult = computed(() => filteredCommandGroups.value[0]?.items?.[0] ?? null)

const openCommandPalette = () => {
  commandPaletteOpen.value = true
  commandPaletteQuery.value = ''

  nextTick(() => {
    commandPaletteInput.value?.focus()
  })
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
