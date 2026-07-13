<script setup>
import NotificationAdminHeader from '@/Components/notifications/NotificationAdminHeader.vue'
import {
  notificationIndicatorClasses,
  notificationTypeClasses,
  notificationTypeLabel,
} from '@/Composables/useNotificationPresentation'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link } from '@inertiajs/vue3'
import {
  ArrowRightIcon,
  BellAlertIcon,
  BellIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ClockIcon,
  EyeIcon,
  PaperAirplaneIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { computed } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  recentNotifications: { type: Array, default: () => [] },
  notificationTypes: { type: Object, default: () => ({}) },
})

const metrics = computed(() => [
  {
    label: 'Total emitido',
    value: props.stats.total ?? 0,
    context: `${props.stats.unread ?? 0} por ler`,
    icon: BellIcon,
  },
  {
    label: 'Taxa de leitura',
    value: `${props.stats.read_rate ?? 0}%`,
    context: 'Mensagens confirmadas',
    icon: CheckCircleIcon,
  },
  {
    label: 'Enviadas hoje',
    value: props.stats.today ?? 0,
    context: `${props.stats.this_week ?? 0} nesta semana`,
    icon: ClockIcon,
  },
  {
    label: 'Este mes',
    value: props.stats.this_month ?? 0,
    context: 'Acumulado do periodo',
    icon: CalendarDaysIcon,
  },
])

const readRate = computed(() => Math.min(Number(props.stats.read_rate ?? 0), 100))
</script>

<template>
  <div class="space-y-5">
    <NotificationAdminHeader
      title="Visao geral"
      description="Acompanhe alcance, leitura e distribuicao das mensagens operacionais do laboratorio."
    >
      <template #actions>
        <Link :href="route('admin.notifications.create')" class="ds-button ds-button-primary">
          <PaperAirplaneIcon class="h-4 w-4" />
          Nova mensagem
        </Link>
      </template>
    </NotificationAdminHeader>

    <section class="ds-panel overflow-hidden">
      <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <article v-for="metric in metrics" :key="metric.label" class="p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</p>
              <p class="mt-2 text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ metric.value }}</p>
            </div>
            <span class="grid h-9 w-9 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <component :is="metric.icon" class="h-4 w-4" />
            </span>
          </div>
          <p class="mt-4 border-t border-[var(--ds-border)] pt-3 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.context }}</p>
        </article>
      </div>
      <div class="border-t border-[var(--ds-border)] px-5 py-4">
        <div class="flex items-center justify-between gap-3 text-xs font-bold text-[var(--ds-text-muted)]">
          <span>Leitura global</span>
          <span class="tabular-nums">{{ readRate }}%</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
          <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${readRate}%` }" />
        </div>
      </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(18rem,0.85fr)]">
      <section class="ds-panel overflow-hidden">
        <header class="flex items-center justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4">
          <div>
            <p class="ds-kicker">Atividade recente</p>
            <h2 class="ds-heading mt-1 text-base">Ultimas mensagens emitidas</h2>
          </div>
          <Link :href="route('admin.notifications.index')" class="ds-button ds-button-ghost">
            Ver registo
            <ArrowRightIcon class="h-4 w-4" />
          </Link>
        </header>

        <div v-if="recentNotifications.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="notification in recentNotifications" :key="notification.id" class="flex items-start gap-3 px-5 py-4 hover:bg-[var(--ds-panel-subtle)]">
            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="notificationIndicatorClasses(notification.type)" />
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate text-sm font-bold text-[var(--ds-text)]">{{ notification.title }}</h3>
                <span class="ds-badge ring-1 ring-inset" :class="notificationTypeClasses(notification.type)">
                  {{ notificationTypeLabel(notification.type) }}
                </span>
                <span class="ds-badge ring-1 ring-inset" :class="notification.read_at ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300'">
                  {{ notification.read_at ? 'Lida' : 'Por ler' }}
                </span>
              </div>
              <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ notification.user_name }} <span class="text-[var(--ds-text-soft)]">/ {{ notification.created_at }}</span>
              </p>
            </div>
            <Link :href="route('admin.notifications.show', notification.id)" class="ds-icon-button" title="Abrir detalhes">
              <EyeIcon class="h-4 w-4" />
            </Link>
          </article>
        </div>

        <div v-else class="px-5 py-12 text-center">
          <BellAlertIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem notificacoes recentes</p>
          <p class="ds-copy mt-1 text-sm">As mensagens emitidas serao apresentadas aqui.</p>
        </div>
      </section>

      <aside class="space-y-5">
        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Volume por emissor</p>
            <h2 class="ds-heading mt-1 flex items-center gap-2 text-base"><UserGroupIcon class="h-4 w-4" /> Principais emissores</h2>
          </header>
          <ol v-if="stats.top_senders?.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="(sender, index) in stats.top_senders" :key="sender.name" class="flex items-center gap-3 px-5 py-3.5">
              <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-xs font-black text-[var(--ds-text)]">{{ index + 1 }}</span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-[var(--ds-text)]">{{ sender.name }}</p>
                <p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ sender.count }} mensagens</p>
              </div>
            </li>
          </ol>
          <p v-else class="px-5 py-8 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem dados de emissores.</p>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Fluxos rapidos</p>
            <h2 class="ds-heading mt-1 text-base">Operacoes</h2>
          </header>
          <div class="divide-y divide-[var(--ds-border)]">
            <Link :href="route('admin.notifications.create')" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)]">
              <PaperAirplaneIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" /> Compor mensagem <ArrowRightIcon class="ml-auto h-4 w-4" />
            </Link>
            <Link :href="route('admin.notifications.index', { read_status: 'unread' })" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)]">
              <BellIcon class="h-4 w-4 text-amber-600" /> Rever por ler <ArrowRightIcon class="ml-auto h-4 w-4" />
            </Link>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>
