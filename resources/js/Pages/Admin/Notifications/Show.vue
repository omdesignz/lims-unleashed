<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import NotificationAdminHeader from '@/Components/notifications/NotificationAdminHeader.vue'
import {
  formatNotificationDate,
  notificationPriorityClasses,
  notificationPriorityLabel,
  notificationTypeClasses,
  notificationTypeLabel,
} from '@/Composables/useNotificationPresentation'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  ArrowLeft as ArrowLeftIcon,
  RefreshCw as ArrowPathIcon,
  Check as CheckIcon,
  Code as CodeBracketIcon,
  Mail as EnvelopeIcon,
  Info as InformationCircleIcon,
  Send as PaperAirplaneIcon,
  Trash2 as TrashIcon,
  User as UserIcon,
} from '@lucide/vue'
import { ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  notification: { type: Object, required: true },
})

const isProcessing = ref(false)
const showRawData = ref(false)
const showDeleteConfirmation = ref(false)

const visitOptions = {
  preserveScroll: true,
  onStart: () => { isProcessing.value = true },
  onFinish: () => { isProcessing.value = false },
}

const markAsRead = () => {
  router.post(route('notifications.read', { notification: props.notification.id }), {}, visitOptions)
}

const markAsUnread = () => {
  router.post(route('notifications.unread', { notification: props.notification.id }), {}, visitOptions)
}

const deleteNotification = () => {
  showDeleteConfirmation.value = false
  router.delete(route('notifications.delete', { notification: props.notification.id }), {
    onStart: () => { isProcessing.value = true },
    onFinish: () => { isProcessing.value = false },
  })
}
</script>

<template>
  <div class="space-y-5">
    <Head title="Detalhe da notificação" />
    <NotificationAdminHeader
      title="Detalhe da notificacao"
      :description="`Registo auditável ${notification.id} e respectivo estado de leitura.`"
    >
      <template #actions>
        <Link :href="route('admin.notifications.index')" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" /> Registo
        </Link>
        <Link :href="route('admin.notifications.create')" class="ds-button ds-button-primary">
          <PaperAirplaneIcon class="h-4 w-4" /> Nova mensagem
        </Link>
      </template>
    </NotificationAdminHeader>

    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-badge ring-1 ring-inset" :class="notificationTypeClasses(notification.type)">{{ notificationTypeLabel(notification.type) }}</span>
            <span class="ds-badge ring-1 ring-inset" :class="notificationPriorityClasses(notification.priority)">{{ notificationPriorityLabel(notification.priority) }}</span>
            <span class="ds-badge ring-1 ring-inset" :class="notification.read_at ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300'">
              {{ notification.read_at ? 'Lida' : 'Por ler' }}
            </span>
          </div>
          <h2 class="ds-heading mt-3 text-xl sm:text-2xl">{{ notification.title }}</h2>
          <p class="mt-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Emitida {{ notification.created_at_human || formatNotificationDate(notification.created_at) }}</p>
        </div>
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <EnvelopeIcon class="h-5 w-5" />
        </span>
      </header>

      <div class="grid lg:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.75fr)]">
        <div class="border-b border-[var(--ds-border)] p-5 sm:p-6 lg:border-b-0 lg:border-r">
          <p class="ds-kicker">Conteúdo enviado</p>
          <div class="mt-4 whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ notification.message }}</div>

          <div class="mt-8 grid gap-4 border-t border-[var(--ds-border)] pt-5 sm:grid-cols-2">
            <article class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <div class="flex items-center gap-2 text-[var(--ds-text-soft)]"><UserIcon class="h-4 w-4" /><p class="text-xs font-bold uppercase">Emissor</p></div>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ notification.sender_name || 'Sistema' }}</p>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ notification.sender_email || 'Emissão automática' }}</p>
            </article>
            <article class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <div class="flex items-center gap-2 text-[var(--ds-text-soft)]"><EnvelopeIcon class="h-4 w-4" /><p class="text-xs font-bold uppercase">Destinatário</p></div>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ notification.user_name }}</p>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ notification.user_email }}</p>
            </article>
          </div>
        </div>

        <aside class="p-5 sm:p-6">
          <p class="ds-kicker">Rastreabilidade</p>
          <dl class="mt-4 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Identificador</dt>
              <dd class="mt-1 break-all text-sm font-bold text-[var(--ds-text)]">{{ notification.id }}</dd>
            </div>
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Data de emissão</dt>
              <dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ formatNotificationDate(notification.created_at) }}</dd>
            </div>
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Leitura</dt>
              <dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ notification.read_at ? formatNotificationDate(notification.read_at) : 'Ainda não confirmada' }}</dd>
              <dd v-if="notification.read_by?.user" class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Por {{ notification.read_by.user }}</dd>
            </div>
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Origem</dt>
              <dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ notification.is_admin_notification ? 'Administracao' : 'Sistema' }}</dd>
            </div>
          </dl>

          <button type="button" class="ds-button ds-button-ghost mt-4 w-full" @click="showRawData = !showRawData">
            <CodeBracketIcon class="h-4 w-4" /> {{ showRawData ? 'Ocultar dados técnicos' : 'Ver dados técnicos' }}
          </button>
        </aside>
      </div>

      <div v-if="showRawData" class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-5 sm:p-6">
        <div class="flex items-center gap-2"><InformationCircleIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><p class="ds-kicker">Carga recebida</p></div>
        <pre class="mt-3 max-h-80 overflow-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel)] p-4 text-xs leading-6 text-[var(--ds-text-muted)]">{{ JSON.stringify(notification, null, 2) }}</pre>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <button type="button" class="ds-button text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10" :disabled="isProcessing" @click="showDeleteConfirmation = true">
          <TrashIcon class="h-4 w-4" /> Eliminar registo
        </button>
        <button v-if="notification.read_at" type="button" class="ds-button ds-button-secondary" :disabled="isProcessing" @click="markAsUnread">
          <ArrowPathIcon class="h-4 w-4" /> Marcar por ler
        </button>
        <button v-else type="button" class="ds-button ds-button-primary" :disabled="isProcessing" @click="markAsRead">
          <CheckIcon class="h-4 w-4" /> Marcar como lida
        </button>
      </footer>
    </section>

    <ConfirmDialog
      v-if="showDeleteConfirmation"
      title="Eliminar esta notificacao?"
      description="O registo será removido do histórico do destinatário. Esta acção não pode ser anulada."
      confirm="Eliminar"
      cancel="Cancelar"
      variant="danger"
      @confirmed="deleteNotification"
      @canceled="showDeleteConfirmation = false"
    />
  </div>
</template>
