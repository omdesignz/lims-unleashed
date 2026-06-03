<template>
  <Menu as="div" class="relative ml-4 flex-shrink-0">
    <MenuButton class="flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-2 py-1.5 text-sm font-bold text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70">
      <span class="sr-only">Abrir menu do utilizador</span>
      <img
        v-if="user?.profile_photo_url"
        class="h-8 w-8 rounded-full object-cover"
        :src="user.profile_photo_url"
        alt=""
      />
      <span v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-[#fffaf0] text-sm font-extrabold text-primary-800">
        {{ userInitial }}
      </span>
    </MenuButton>
    <transition leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
      <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-[1.4rem] border border-[#ded3bf] bg-[#fffaf0] p-2 shadow-[0_24px_70px_rgba(20,61,55,0.20)] ring-1 ring-[#143d37]/5 focus:outline-none dark:border-[#25443c] dark:bg-[#0c1714] dark:ring-white/10">
        <div class="px-3 py-2">
          <p class="truncate text-sm font-extrabold text-[#15231f] dark:text-[#f7f1e7]">{{ user?.name || 'Utilizador' }}</p>
          <p class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ user?.email }}</p>
        </div>
        <MenuItem v-slot="{ active }">
          <Link
            :href="profileHref"
            :class="[
              active ? 'bg-[#ede5d6] text-primary-800 dark:bg-[#10231f] dark:text-accent-100' : 'text-slate-700 dark:text-slate-300',
              'block rounded-xl px-3 py-2 text-sm font-semibold transition',
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
              active ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-200' : 'text-rose-700 dark:text-rose-300',
              'block w-full rounded-xl px-3 py-2 text-left text-sm font-semibold transition',
            ]"
          >
            Terminar sessão
          </Link>
        </MenuItem>
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
