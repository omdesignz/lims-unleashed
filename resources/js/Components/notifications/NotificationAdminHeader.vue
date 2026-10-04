<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import {
  BellRing as BellAlertIcon,
  ChartColumnBig as ChartBarSquareIcon,
  Settings as Cog6ToothIcon,
  List as ListBulletIcon,
  Send as PaperAirplaneIcon,
  LayoutGrid as Squares2X2Icon,
} from '@lucide/vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
})

const page = usePage()
const navigation = [
  { label: 'Visão geral', route: 'admin.notifications.dashboard', icon: Squares2X2Icon },
  { label: 'Registo', route: 'admin.notifications.index', icon: ListBulletIcon },
  { label: 'Nova mensagem', route: 'admin.notifications.create', icon: PaperAirplaneIcon },
  { label: 'Modelos', route: 'admin.notification-templates.index', icon: Cog6ToothIcon },
  { label: 'Analítica', route: 'admin.notifications.analytics', icon: ChartBarSquareIcon },
]
</script>

<template>
  <section class="ds-panel overflow-hidden">
    <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <div class="flex min-w-0 items-start gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <BellAlertIcon class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <p class="ds-kicker">Centro de notificações</p>
          <h1 class="ds-heading mt-1 text-xl sm:text-2xl">{{ title }}</h1>
          <p class="ds-copy mt-1 max-w-3xl text-sm">{{ description }}</p>
        </div>
      </div>

      <div class="flex shrink-0 flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </header>

    <nav class="overflow-x-auto px-3" aria-label="Navegação de notificações">
      <div class="flex min-w-max gap-1 py-2">
        <Link
          v-for="item in navigation"
          :key="item.route"
          :href="route(item.route)"
          class="ds-settings-tab w-auto! min-w-0! items-center!"
          :class="page.url.split('?')[0] === route(item.route, {}, false) ? 'ds-settings-tab-active' : ''"
          :aria-current="page.url.split('?')[0] === route(item.route, {}, false) ? 'page' : undefined"
        >
          <component :is="item.icon" class="h-4 w-4" />
          {{ item.label }}
        </Link>
      </div>
    </nav>
  </section>
</template>
