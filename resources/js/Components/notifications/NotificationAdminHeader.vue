<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import PageHeader from '@/Components/plano/PageHeader.vue'

/**
 * The header shared by the notification centre screens: the page header, then
 * the centre's sections as tabs.
 */
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
  { label: 'Visão geral', route: 'admin.notifications.dashboard' },
  { label: 'Registo', route: 'admin.notifications.index' },
  { label: 'Nova mensagem', route: 'admin.notifications.create' },
  { label: 'Modelos', route: 'admin.notification-templates.index' },
  { label: 'Analítica', route: 'admin.notifications.analytics' },
]

const isCurrent = (item) => page.url.split('?')[0] === route(item.route, {}, false)
</script>

<template>
  <PageHeader :trail="[{ title: 'Notificações', url: route('admin.notifications.dashboard') }, { title }]" :title="title" :lede="description">
    <template v-if="$slots.actions" #actions>
      <slot name="actions" />
    </template>

    <nav class="pl-tabs mb-8" aria-label="Navegação de notificações">
      <Link
        v-for="item in navigation"
        :key="item.route"
        :href="route(item.route)"
        class="pl-tab"
        :aria-current="isCurrent(item) ? 'page' : undefined"
      >
        {{ item.label }}
      </Link>
    </nav>
  </PageHeader>
</template>
