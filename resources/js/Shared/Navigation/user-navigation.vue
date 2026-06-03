<template>
  <div class="mt-3 space-y-1 px-2">
    <Link
      v-for="item in userNavigation"
      :key="item.name"
      :href="item.href"
      :method="item.method"
      :as="item.method ? 'button' : undefined"
      class="block w-full rounded-xl px-3 py-2 text-left text-base font-semibold text-slate-800 transition hover:bg-[#ede5d6] hover:text-primary-800 dark:text-slate-200 dark:hover:bg-[#10231f] dark:hover:text-accent-100"
    >
      {{ item.name }}
    </Link>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const userNavigation = computed(() => {
  const userId = page.props?.auth?.user?.id

  return [
    { name: 'Perfil e segurança', href: userId ? route('users.edit', userId) : route('dashboard') },
    { name: 'Terminar sessão', href: route('logout'), method: 'post' },
  ]
})
</script>
