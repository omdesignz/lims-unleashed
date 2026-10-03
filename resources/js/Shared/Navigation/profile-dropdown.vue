<template>
  <Menu as="div" class="relative ml-4 shrink-0">
    <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-2 py-1.5 text-sm font-bold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-200/50">
      <span class="sr-only">Abrir menu do utilizador</span>
      <img
        v-if="user?.profile_photo_url"
        class="h-8 w-8 rounded-lg object-cover"
        :src="user.profile_photo_url"
        alt=""
      />
      <span v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-100 text-sm font-extrabold text-slate-950">
        {{ userInitial }}
      </span>
      <span class="hidden max-w-32 truncate text-left text-xs font-semibold text-slate-200 sm:block">
        {{ user?.name || 'Utilizador' }}
      </span>
    </MenuButton>

    <transition leave-active-class="transition ease-out duration-100" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-[0.97]">
      <MenuItems class="ds-floating-panel absolute right-0 z-10 mt-2 w-60 origin-top-right p-2 focus:outline-none">
        <div class="border-b border-[var(--ds-border)] px-3 py-2">
          <p class="truncate text-sm font-bold text-[var(--ds-text)]">{{ user?.name || 'Utilizador' }}</p>
          <p class="truncate text-xs font-medium text-[var(--ds-text-muted)]">{{ user?.email }}</p>
        </div>

        <div class="mt-2 space-y-1">
          <MenuItem v-slot="{ active }">
            <Link
              :href="profileHref"
              :class="[
                active ? 'bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-800-rgb)/1)] dark:text-cyan-100' : 'text-[var(--ds-text-muted)]',
                'block rounded-lg px-3 py-2 text-sm font-bold transition',
              ]"
            >
              Perfil e segurança
            </Link>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              :class="[
                active ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200' : 'text-rose-700 dark:text-rose-300',
                'block w-full rounded-lg px-3 py-2 text-left text-sm font-bold transition',
              ]"
            >
              Terminar sessão
            </Link>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

const page = usePage()
const user = computed(() => page.props?.auth?.user ?? null)
const userInitial = computed(() => user.value?.name?.charAt(0)?.toUpperCase() || 'U')
const profileHref = computed(() => {
  if (user.value?.id) {
    return route('users.edit', user.value.id)
  }

  return route('dashboard')
})
</script>
