<template>
  <div class="space-y-6">
    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Centro de trabalho</p>
          <div class="mt-2 flex items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))]">
              <BellIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-xl sm:text-2xl">Caixa de notificações</h1>
              <p class="ds-copy mt-1 text-sm">Alertas de trabalho, decisões pendentes e atualizações do laboratório.</p>
            </div>
          </div>
        </div>

        <button
          type="button"
          class="ds-button ds-button-primary shrink-0"
          :disabled="unreadCount === 0 || isBulkProcessing"
          @click="markAllAsRead"
        >
          <ArrowPathIcon v-if="isBulkProcessing" class="h-4 w-4 animate-spin" />
          <CheckIcon v-else class="h-4 w-4" />
          Marcar todas como lidas
        </button>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div class="px-5 py-4 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Total registado</dt>
          <dd class="mt-1 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ totalNotifications }}</dd>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Não lidas nesta página</dt>
          <dd class="mt-1 flex items-baseline gap-2">
            <span class="text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ unreadCount }}</span>
            <span class="text-xs font-semibold text-[var(--ds-text-soft)]">de {{ notifications.length }}</span>
          </dd>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Recebidas hoje</dt>
          <dd class="mt-1 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ todayCount }}</dd>
        </div>
      </dl>
    </section>

    <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_17rem]">
      <div class="min-w-0 space-y-4">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] p-4 sm:p-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
              <div class="flex min-w-0 gap-1 overflow-x-auto" aria-label="Filtrar notificações">
                <button
                  v-for="filter in filters"
                  :key="filter.id"
                  type="button"
                  class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-xs font-bold transition-colors"
                  :class="activeFilter === filter.id
                    ? 'bg-[rgb(var(--primary-700-rgb))] text-white'
                    : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]'"
                  :aria-pressed="activeFilter === filter.id"
                  @click="activeFilter = filter.id"
                >
                  <component :is="filter.icon" class="h-4 w-4" />
                  {{ filter.label }}
                  <span
                    class="rounded-md px-1.5 py-0.5 tabular-nums"
                    :class="activeFilter === filter.id ? 'bg-[rgb(255_255_255/0.15)] text-white' : 'bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]'"
                  >
                    {{ filter.count }}
                  </span>
                </button>
              </div>

              <label class="relative block w-full xl:w-72">
                <span class="sr-only">Pesquisar notificações</span>
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
                <BaseInput
                  v-model="searchQuery"
                  type="search"
                  class="ds-field h-10 pl-9"
                  placeholder="Título, mensagem ou remetente" />
              </label>
            </div>
          </div>

          <div v-if="filteredNotifications.length" class="divide-y divide-[var(--ds-border)]">
            <article
              v-for="notification in filteredNotifications"
              :key="notification.id"
              class="group relative grid gap-3 px-4 py-4 transition-colors hover:bg-[var(--ds-panel-subtle)] sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-start sm:px-5"
              :class="!notification.read_at ? 'bg-[rgb(var(--primary-50-rgb)/0.42)] dark:bg-[rgb(var(--primary-400-rgb)/0.06)]' : ''"
            >
              <div
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border"
                :class="notificationType(notification).iconClass"
              >
                <component :is="notificationType(notification).icon" class="h-4.5 w-4.5" />
              </div>

              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="truncate text-sm font-bold text-[var(--ds-text)]">
                    {{ notificationTitle(notification) }}
                  </h2>
                  <span v-if="!notification.read_at" class="inline-flex items-center gap-1 text-[0.7rem] font-bold uppercase text-amber-700 dark:text-amber-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                    Nova
                  </span>
                  <span class="rounded-md border px-1.5 py-0.5 text-[0.68rem] font-bold uppercase" :class="notificationType(notification).badgeClass">
                    {{ notificationType(notification).label }}
                  </span>
                </div>
                <p class="mt-1 line-clamp-2 text-sm leading-6 text-[var(--ds-text-muted)]">
                  {{ notificationMessage(notification) }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                  <span class="inline-flex items-center gap-1.5">
                    <ClockIcon class="h-3.5 w-3.5" />
                    <time :datetime="notification.created_at">{{ formatRelativeTime(notification.created_at) }}</time>
                  </span>
                  <span v-if="notificationSender(notification)" class="inline-flex min-w-0 items-center gap-1.5">
                    <UserIcon class="h-3.5 w-3.5 shrink-0" />
                    <span class="truncate">{{ notificationSender(notification) }}</span>
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-1 sm:justify-self-end">
                <button
                  v-if="getNotificationTargetUrl(notification)"
                  type="button"
                  class="ds-icon-button"
                  :title="notificationTargetLabel(notification)"
                  :disabled="processingId === notification.id"
                  @click="openNotificationTarget(notification)"
                >
                  <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="ds-icon-button"
                  :title="notification.read_at ? 'Marcar como não lida' : 'Marcar como lida'"
                  :disabled="processingId === notification.id"
                  @click="notification.read_at ? markAsUnread(notification) : markAsRead(notification)"
                >
                  <ArrowPathIcon v-if="processingId === notification.id" class="h-4 w-4 animate-spin" />
                  <EnvelopeIcon v-else-if="notification.read_at" class="h-4 w-4" />
                  <EnvelopeOpenIcon v-else class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  class="ds-icon-button hover:!text-red-600"
                  title="Apagar notificação"
                  :disabled="processingId === notification.id"
                  @click="requestConfirmation('delete', notification)"
                >
                  <TrashIcon class="h-4 w-4" />
                </button>
              </div>
            </article>
          </div>

          <div v-else class="p-5 sm:p-8">
            <div class="ds-empty-state grid min-h-52 place-items-center px-6 py-10 text-center">
              <div>
                <BellSlashIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
                <h2 class="ds-heading mt-3 text-base">{{ emptyStateTitle }}</h2>
                <p class="ds-copy mt-1 text-sm">{{ emptyStateDescription }}</p>
                <button
                  v-if="activeFilter !== 'all' || searchQuery"
                  type="button"
                  class="ds-button ds-button-secondary mt-4"
                  @click="resetFilters"
                >
                  Repor filtros
                </button>
              </div>
            </div>
          </div>
        </section>

        <Pagination v-if="pagination.last_page > 1" v-bind="pagination" />
      </div>

      <aside class="space-y-4">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <h2 class="ds-heading text-sm">Composição da página</h2>
            <p class="mt-1 text-xs font-medium text-[var(--ds-text-soft)]">Distribuição por severidade.</p>
          </div>
          <dl v-if="notificationTypes.length" class="divide-y divide-[var(--ds-border)]">
            <div v-for="type in notificationTypes" :key="type.id" class="flex items-center justify-between gap-3 px-4 py-3">
              <dt class="flex min-w-0 items-center gap-2 text-xs font-semibold text-[var(--ds-text-muted)]">
                <span class="h-2 w-2 shrink-0 rounded-full" :class="type.dotClass" />
                <span class="truncate">{{ type.label }}</span>
              </dt>
              <dd class="text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ type.count }}</dd>
            </div>
          </dl>
          <p v-else class="px-4 py-5 text-xs font-medium text-[var(--ds-text-soft)]">Sem notificações nesta página.</p>
        </section>

        <section class="ds-command-surface p-4">
          <p class="ds-kicker">Gestão</p>
          <h2 class="ds-heading mt-2 text-sm">Higiene da caixa</h2>
          <p class="ds-copy mt-1 text-xs">Remova registos concluídos sem afetar os alertas ainda por tratar.</p>
          <div class="mt-4 grid gap-2">
            <button
              type="button"
              class="ds-button ds-button-secondary w-full justify-start"
              :disabled="readCount === 0 || isBulkProcessing"
              @click="requestConfirmation('clearRead')"
            >
              <ArchiveBoxXMarkIcon class="h-4 w-4" />
              Limpar notificações lidas
            </button>
            <button
              type="button"
              class="ds-button w-full justify-start border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"
              :disabled="notifications.length === 0 || isBulkProcessing"
              @click="requestConfirmation('clearAll')"
            >
              <TrashIcon class="h-4 w-4" />
              Limpar toda a caixa
            </button>
          </div>
        </section>
      </aside>
    </div>

    <ConfirmDialog
      v-if="pendingConfirmation"
      :title="confirmationCopy.title"
      :description="confirmationCopy.description"
      :confirm="confirmationCopy.confirm"
      @confirmed="performConfirmedAction"
      @canceled="pendingConfirmation = null"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArchiveBoxXMarkIcon,
  ArrowPathIcon,
  ArrowTopRightOnSquareIcon,
  BellAlertIcon,
  BellIcon,
  BellSlashIcon,
  CheckBadgeIcon,
  CheckCircleIcon,
  CheckIcon,
  ClockIcon,
  EnvelopeIcon,
  EnvelopeOpenIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  MagnifyingGlassIcon,
  TrashIcon,
  UserIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import Pagination from '@/Components/pagination.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  notifications: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({
      links: [],
      from: 0,
      to: 0,
      total: 0,
      current_page: 1,
      last_page: 1,
      per_page: 20,
    }),
  },
})

const notifications = ref([...props.notifications])
const activeFilter = ref('all')
const searchQuery = ref('')
const processingId = ref(null)
const isBulkProcessing = ref(false)
const pendingConfirmation = ref(null)

watch(() => props.notifications, (value) => {
  notifications.value = [...value]
}, { deep: true })

const totalNotifications = computed(() => props.pagination.total ?? notifications.value.length)
const unreadCount = computed(() => notifications.value.filter((notification) => !notification.read_at).length)
const readCount = computed(() => notifications.value.filter((notification) => notification.read_at).length)
const todayCount = computed(() => {
  const today = new Date().toDateString()

  return notifications.value.filter((notification) => new Date(notification.created_at).toDateString() === today).length
})

const importantCount = computed(() => notifications.value.filter((notification) => (
  notification.data?.priority === 'high' || notification.data?.type === 'alert'
)).length)

const filters = computed(() => [
  { id: 'all', label: 'Todas', icon: BellIcon, count: notifications.value.length },
  { id: 'unread', label: 'Não lidas', icon: BellAlertIcon, count: unreadCount.value },
  { id: 'read', label: 'Lidas', icon: CheckCircleIcon, count: readCount.value },
  { id: 'today', label: 'Hoje', icon: ClockIcon, count: todayCount.value },
  { id: 'important', label: 'Críticas', icon: ExclamationTriangleIcon, count: importantCount.value },
])

const filteredNotifications = computed(() => {
  let records = notifications.value

  if (activeFilter.value === 'unread') {
    records = records.filter((notification) => !notification.read_at)
  } else if (activeFilter.value === 'read') {
    records = records.filter((notification) => notification.read_at)
  } else if (activeFilter.value === 'today') {
    const today = new Date().toDateString()
    records = records.filter((notification) => new Date(notification.created_at).toDateString() === today)
  } else if (activeFilter.value === 'important') {
    records = records.filter((notification) => notification.data?.priority === 'high' || notification.data?.type === 'alert')
  }

  const query = searchQuery.value.trim().toLocaleLowerCase('pt-PT')

  if (!query) {
    return records
  }

  return records.filter((notification) => [
    notificationTitle(notification),
    notificationMessage(notification),
    notificationSender(notification),
  ].some((value) => value.toLocaleLowerCase('pt-PT').includes(query)))
})

const notificationTypeDefinitions = {
  success: {
    label: 'Sucesso',
    icon: CheckBadgeIcon,
    iconClass: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
    badgeClass: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
    dotClass: 'bg-emerald-500',
  },
  error: {
    label: 'Erro',
    icon: XCircleIcon,
    iconClass: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300',
    badgeClass: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300',
    dotClass: 'bg-red-500',
  },
  warning: {
    label: 'Aviso',
    icon: ExclamationTriangleIcon,
    iconClass: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
    badgeClass: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
    dotClass: 'bg-amber-500',
  },
  alert: {
    label: 'Alerta',
    icon: BellAlertIcon,
    iconClass: 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300',
    badgeClass: 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300',
    dotClass: 'bg-orange-500',
  },
  email: {
    label: 'Mensagem',
    icon: EnvelopeIcon,
    iconClass: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-300',
    badgeClass: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-300',
    dotClass: 'bg-violet-500',
  },
  info: {
    label: 'Informação',
    icon: InformationCircleIcon,
    iconClass: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300',
    badgeClass: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300',
    dotClass: 'bg-sky-500',
  },
}

const notificationTypes = computed(() => {
  const counts = new Map()

  notifications.value.forEach((notification) => {
    const type = notificationTypeKey(notification)
    counts.set(type, (counts.get(type) ?? 0) + 1)
  })

  return [...counts.entries()].map(([id, count]) => ({
    id,
    count,
    ...notificationTypeDefinitions[id],
  }))
})

const emptyStateTitle = computed(() => {
  if (searchQuery.value.trim()) {
    return 'Nenhuma correspondência'
  }

  if (activeFilter.value !== 'all') {
    return 'Nenhuma notificação neste filtro'
  }

  return 'Caixa de notificações vazia'
})

const emptyStateDescription = computed(() => {
  if (searchQuery.value.trim()) {
    return 'Altere os termos da pesquisa ou reponha os filtros.'
  }

  if (activeFilter.value !== 'all') {
    return 'Selecione outro estado para consultar os restantes registos.'
  }

  return 'Os novos alertas operacionais serão apresentados aqui.'
})

const confirmationCopy = computed(() => {
  if (pendingConfirmation.value?.action === 'delete') {
    return {
      title: 'Apagar notificação',
      description: `A notificação “${notificationTitle(pendingConfirmation.value.notification)}” será removida permanentemente.`,
      confirm: 'Apagar',
    }
  }

  if (pendingConfirmation.value?.action === 'clearRead') {
    return {
      title: 'Limpar notificações lidas',
      description: 'Todas as notificações já tratadas serão removidas da sua caixa.',
      confirm: 'Limpar lidas',
    }
  }

  return {
    title: 'Limpar toda a caixa',
    description: 'Todas as notificações, incluindo as ainda não lidas, serão removidas permanentemente.',
    confirm: 'Limpar caixa',
  }
})

function notificationTypeKey(notification) {
  const key = notification.data?.type ?? 'info'

  return notificationTypeDefinitions[key] ? key : 'info'
}

function notificationType(notification) {
  return notificationTypeDefinitions[notificationTypeKey(notification)]
}

function notificationTitle(notification) {
  return String(notification.data?.title || 'Notificação sem título')
}

function notificationMessage(notification) {
  return String(notification.data?.message || notification.data?.body || 'Sem descrição adicional.')
}

function notificationSender(notification) {
  const sender = notification.data?.sender

  if (sender && typeof sender === 'object') {
    return String(sender.name || sender.email || '')
  }

  return sender ? String(sender) : ''
}

function formatRelativeTime(dateString) {
  const elapsed = Math.max(0, Date.now() - new Date(dateString).getTime())
  const minutes = Math.floor(elapsed / 60000)
  const hours = Math.floor(elapsed / 3600000)
  const days = Math.floor(elapsed / 86400000)

  if (minutes < 1) return 'Agora'
  if (minutes < 60) return `Há ${minutes} min`
  if (hours < 24) return `Há ${hours} h`
  if (days === 1) return 'Ontem'
  if (days < 7) return `Há ${days} dias`

  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(dateString))
}

function getNotificationTargetUrl(notification) {
  return notification.data?.need_url
    || notification.data?.analysis_url
    || notification.data?.collection_url
    || notification.data?.worksheet_url
    || null
}

function notificationTargetLabel(notification) {
  if (notification.data?.need_url) return 'Abrir necessidade'
  if (notification.data?.analysis_url) return 'Abrir análise'
  if (notification.data?.collection_url) return 'Abrir colheita'
  if (notification.data?.worksheet_url) return 'Abrir folha de trabalho'

  return 'Abrir registo relacionado'
}

function markAsRead(notification, afterSuccess = null) {
  if (notification.read_at) {
    afterSuccess?.()
    return
  }

  processingId.value = notification.id
  router.post(route('notifications.read', { notification: notification.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      notification.read_at = new Date().toISOString()
      afterSuccess?.()
    },
    onFinish: () => {
      processingId.value = null
    },
  })
}

function markAsUnread(notification) {
  if (!notification.read_at) return

  processingId.value = notification.id
  router.post(route('notifications.unread', { notification: notification.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      notification.read_at = null
    },
    onFinish: () => {
      processingId.value = null
    },
  })
}

function markAllAsRead() {
  if (unreadCount.value === 0) return

  isBulkProcessing.value = true
  router.post(route('notifications.read-all'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      const readAt = new Date().toISOString()
      notifications.value.forEach((notification) => {
        notification.read_at = notification.read_at || readAt
      })
    },
    onFinish: () => {
      isBulkProcessing.value = false
    },
  })
}

function openNotificationTarget(notification) {
  const targetUrl = getNotificationTargetUrl(notification)

  if (!targetUrl) return

  markAsRead(notification, () => router.visit(targetUrl))
}

function requestConfirmation(action, notification = null) {
  pendingConfirmation.value = { action, notification }
}

function performConfirmedAction() {
  const pending = pendingConfirmation.value
  pendingConfirmation.value = null

  if (pending.action === 'delete') {
    deleteNotification(pending.notification)
  } else if (pending.action === 'clearRead') {
    clearReadNotifications()
  } else {
    clearAllNotifications()
  }
}

function deleteNotification(notification) {
  processingId.value = notification.id
  router.delete(route('notifications.delete', { notification: notification.id }), {
    preserveScroll: true,
    onSuccess: () => {
      notifications.value = notifications.value.filter((record) => record.id !== notification.id)
    },
    onFinish: () => {
      processingId.value = null
    },
  })
}

function clearReadNotifications() {
  if (readCount.value === 0) return

  isBulkProcessing.value = true
  router.delete(route('notifications.clear-read'), {
    preserveScroll: true,
    onSuccess: () => {
      notifications.value = notifications.value.filter((notification) => !notification.read_at)
    },
    onFinish: () => {
      isBulkProcessing.value = false
    },
  })
}

function clearAllNotifications() {
  if (notifications.value.length === 0) return

  isBulkProcessing.value = true
  router.delete(route('notifications.clear-all'), {
    preserveScroll: true,
    onSuccess: () => {
      notifications.value = []
    },
    onFinish: () => {
      isBulkProcessing.value = false
    },
  })
}

function resetFilters() {
  activeFilter.value = 'all'
  searchQuery.value = ''
}
</script>
