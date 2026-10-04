<script setup>
import NotificationAdminHeader from '@/Components/notifications/NotificationAdminHeader.vue'
import Pagination from '@/Components/pagination.vue'
import {
  formatNotificationDate,
  notificationPriorityClasses,
  notificationPriorityLabel,
  notificationTypeClasses,
  notificationTypeLabel,
} from '@/Composables/useNotificationPresentation'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import {
  Download as ArrowDownTrayIcon,
  RefreshCw as ArrowPathIcon,
  Check as CheckIcon,
  Eye as EyeIcon,
  Funnel as FunnelIcon,
  Search as MagnifyingGlassIcon,
  Send as PaperAirplaneIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import { computed } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  notifications: { type: Object, required: true },
  users: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  notificationTypes: { type: Object, default: () => ({}) },
})

const filterForm = useForm({
  search: props.filters.search || '',
  type: props.filters.type || '',
  read_status: props.filters.read_status || '',
  user_id: props.filters.user_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
})

const hasFilters = computed(() => Object.values(filterForm.data()).some((value) => value !== ''))
const unreadOnPage = computed(() => props.notifications.data.filter((notification) => !notification.read_at).length)

const applyFilters = () => {
  filterForm.get(route('admin.notifications.index'), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const resetFilters = () => {
  filterForm.defaults({ search: '', type: '', read_status: '', user_id: '', date_from: '', date_to: '' })
  filterForm.reset()
  applyFilters()
}

const markAsRead = (notification) => {
  router.post(route('notifications.read', { notification: notification.id }), {}, { preserveScroll: true })
}

const markAsUnread = (notification) => {
  router.post(route('notifications.unread', { notification: notification.id }), {}, { preserveScroll: true })
}
</script>

<template>
  <div class="pl-page space-y-5">
    <Head title="Registo de notificações" />
    <NotificationAdminHeader
      title="Registo de notificações"
      :description="`${notifications.total} mensagens auditáveis no histórico de comunicação.`"
    >
      <template #actions>
        <Link :href="route('admin.notifications.export')" :data="filterForm.data()" class="ds-button ds-button-secondary">
          <ArrowDownTrayIcon class="h-4 w-4" />
          Exportar
        </Link>
        <Link :href="route('admin.notifications.create')" class="ds-button ds-button-primary">
          <PaperAirplaneIcon class="h-4 w-4" />
          Nova mensagem
        </Link>
      </template>
    </NotificationAdminHeader>

    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="ds-kicker">Pesquisa operacional</p>
          <h2 class="ds-heading mt-1 flex items-center gap-2 text-base"><FunnelIcon class="h-4 w-4" /> Filtros do registo</h2>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]">
          <span class="ds-badge bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-1 ring-inset ring-[var(--ds-border)]">{{ notifications.data.length }} nesta página</span>
          <span class="ds-badge bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300">{{ unreadOnPage }} por ler</span>
        </div>
      </header>

      <form class="p-5" @submit.prevent="applyFilters">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="ds-field-group xl:col-span-2">
            <label for="notification-search" class="ds-field-label">Pesquisar</label>
            <div class="relative">
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput id="notification-search" v-model="filterForm.search" type="search" class="ds-field pl-9" placeholder="Título, mensagem, utilizador ou correio electrónico" />
            </div>
          </div>
          <div class="ds-field-group">
            <label for="notification-type" class="ds-field-label">Tipo</label>
            <BaseSelect id="notification-type" v-model="filterForm.type" class="ds-field">
              <option value="">Todos os tipos</option>
              <option v-for="(type, key) in notificationTypes" :key="key" :value="key">{{ type.label }}</option>
            </BaseSelect>
          </div>
          <div class="ds-field-group">
            <label for="notification-status" class="ds-field-label">Estado de leitura</label>
            <BaseSelect id="notification-status" v-model="filterForm.read_status" class="ds-field">
              <option value="">Todos os estados</option>
              <option value="read">Lidas</option>
              <option value="unread">Por ler</option>
            </BaseSelect>
          </div>
          <div class="ds-field-group md:col-span-2">
            <label for="notification-user" class="ds-field-label">Destinatário</label>
            <BaseSelect id="notification-user" v-model="filterForm.user_id" class="ds-field">
              <option value="">Todos os utilizadores</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
            </BaseSelect>
          </div>
          <div class="ds-field-group">
            <label for="notification-from" class="ds-field-label">Desde</label>
            <DateTimePicker id="notification-from" v-model="filterForm.date_from" type="date" class="ds-field" />
          </div>
          <div class="ds-field-group">
            <label for="notification-to" class="ds-field-label">Até</label>
            <DateTimePicker id="notification-to" v-model="filterForm.date_to" type="date" class="ds-field" />
          </div>
        </div>

        <div class="mt-5 flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] pt-4 sm:flex-row sm:justify-end">
          <button v-if="hasFilters" type="button" class="ds-button ds-button-ghost" @click="resetFilters">
            <XMarkIcon class="h-4 w-4" /> Limpar
          </button>
          <button type="submit" class="ds-button ds-button-primary" :disabled="filterForm.processing">
            <ArrowPathIcon v-if="filterForm.processing" class="h-4 w-4 animate-spin" />
            <FunnelIcon v-else class="h-4 w-4" />
            Aplicar filtros
          </button>
        </div>
      </form>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="overflow-x-auto">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left">
          <thead class="bg-[var(--ds-panel-subtle)]">
            <tr>
              <th class="px-5 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Mensagem</th>
              <th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Destinatário</th>
              <th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Classificação</th>
              <th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Emissão</th>
              <th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Estado</th>
              <th class="px-5 py-3 text-right text-xs font-bold uppercase text-[var(--ds-text-soft)]">Acções</th>
            </tr>
          </thead>
          <tbody v-if="notifications.data.length" class="divide-y divide-[var(--ds-border)]">
            <tr v-for="notification in notifications.data" :key="notification.id" class="hover:bg-[var(--ds-panel-subtle)]">
              <td class="max-w-sm px-5 py-4">
                <Link :href="route('admin.notifications.show', notification.id)" class="block truncate text-sm font-bold text-[var(--ds-text)] hover:text-[rgb(var(--primary-700-rgb))]">{{ notification.title }}</Link>
                <p class="mt-1 line-clamp-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ notification.message }}</p>
              </td>
              <td class="px-4 py-4">
                <p class="whitespace-nowrap text-sm font-bold text-[var(--ds-text)]">{{ notification.user_name }}</p>
                <p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ notification.user_email }}</p>
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-col items-start gap-1.5">
                  <span class="ds-badge ring-1 ring-inset" :class="notificationTypeClasses(notification.type)">{{ notificationTypeLabel(notification.type) }}</span>
                  <span class="ds-badge ring-1 ring-inset" :class="notificationPriorityClasses(notification.priority)">{{ notificationPriorityLabel(notification.priority) }}</span>
                </div>
              </td>
              <td class="whitespace-nowrap px-4 py-4 text-xs font-semibold text-[var(--ds-text-muted)]">{{ formatNotificationDate(notification.created_at) }}</td>
              <td class="px-4 py-4">
                <span class="ds-badge ring-1 ring-inset" :class="notification.read_at ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300'">
                  {{ notification.read_at ? 'Lida' : 'Por ler' }}
                </span>
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-1">
                  <button v-if="notification.read_at" type="button" class="ds-icon-button" title="Marcar como por ler" @click="markAsUnread(notification)"><ArrowPathIcon class="h-4 w-4" /></button>
                  <button v-else type="button" class="ds-icon-button" title="Marcar como lida" @click="markAsRead(notification)"><CheckIcon class="h-4 w-4" /></button>
                  <Link :href="route('admin.notifications.show', notification.id)" class="ds-icon-button" title="Abrir detalhes"><EyeIcon class="h-4 w-4" /></Link>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="!notifications.data.length" class="px-5 py-14 text-center">
        <MagnifyingGlassIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhuma notificacao encontrada</p>
        <p class="ds-copy mt-1 text-sm">Ajuste os filtros ou emita uma nova mensagem.</p>
      </div>

      <div v-if="notifications.data.length" class="border-t border-[var(--ds-border)]">
        <Pagination v-bind="notifications" />
      </div>
    </section>
  </div>
</template>
