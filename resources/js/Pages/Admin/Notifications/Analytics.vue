<script setup>
import NotificationAdminHeader from '@/Components/notifications/NotificationAdminHeader.vue'
import {
  notificationIndicatorClasses,
  notificationTypeClasses,
  notificationTypeLabel,
} from '@/Composables/useNotificationPresentation'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ChartBarSquareIcon,
  CheckCircleIcon,
  ClockIcon,
  PaperAirplaneIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  period: { type: String, default: 'week' },
  dateRange: { type: Array, default: () => [] },
})

const selectedPeriod = ref(props.period)

const periodOptions = [
  { value: 'week', label: 'Ultimos 7 dias' },
  { value: 'month', label: 'Ultimos 30 dias' },
  { value: 'quarter', label: 'Ultimos 3 meses' },
  { value: 'year', label: 'Ultimo ano' },
]

const deliveryTrend = computed(() => Object.entries(props.stats.delivery_trend || {}).map(([date, values]) => ({ date, ...values })))
const maxTrendValue = computed(() => Math.max(1, ...deliveryTrend.value.flatMap((entry) => [Number(entry.sent), Number(entry.read)])))
const typeDistribution = computed(() => Object.entries(props.stats.notification_types || {}).sort(([, first], [, second]) => second - first))
const totalTypes = computed(() => typeDistribution.value.reduce((total, [, count]) => total + Number(count), 0))

const metrics = computed(() => [
  { label: 'Mensagens emitidas', value: props.stats.total_sent ?? 0, context: 'No periodo selecionado', icon: ChartBarSquareIcon },
  { label: 'Mensagens lidas', value: props.stats.total_read ?? 0, context: 'Confirmacoes registadas', icon: CheckCircleIcon },
  { label: 'Taxa de leitura', value: `${props.stats.read_rate ?? 0}%`, context: 'Alcance confirmado', icon: UserGroupIcon },
  { label: 'Tempo medio', value: props.stats.avg_read_time ?? 'N/A', context: 'Da emissao a leitura', icon: ClockIcon },
])

const formatDate = (value) => new Intl.DateTimeFormat('pt-PT', { day: '2-digit', month: 'short' }).format(new Date(value))
const formatRangeDate = (value) => value ? new Intl.DateTimeFormat('pt-PT', { dateStyle: 'medium' }).format(new Date(value)) : '-'
const percentage = (value, total) => total ? Math.round((Number(value) / Number(total)) * 100) : 0

const updatePeriod = () => {
  router.get(route('admin.notifications.analytics'), { period: selectedPeriod.value }, { preserveState: true, replace: true })
}
</script>

<template>
  <div class="space-y-5">
    <NotificationAdminHeader
      title="Analitica de comunicacao"
      :description="`Desempenho entre ${formatRangeDate(dateRange[0])} e ${formatRangeDate(dateRange[1])}.`"
    >
      <template #actions>
        <Link
          :href="route('admin.notifications.export')"
          :data="{ start_date: dateRange[0], end_date: dateRange[1] }"
          class="ds-button ds-button-secondary"
        >
          <ArrowDownTrayIcon class="h-4 w-4" /> Exportar
        </Link>
        <Link :href="route('admin.notifications.create')" class="ds-button ds-button-primary">
          <PaperAirplaneIcon class="h-4 w-4" /> Nova mensagem
        </Link>
      </template>
    </NotificationAdminHeader>

    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="ds-kicker">Janela de analise</p>
          <h2 class="ds-heading mt-1 text-base">Indicadores de leitura</h2>
        </div>
        <div class="flex items-center gap-2">
          <label for="analytics-period" class="sr-only">Periodo</label>
          <select id="analytics-period" v-model="selectedPeriod" class="ds-field min-w-48" @change="updatePeriod">
            <option v-for="option in periodOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <article v-for="metric in metrics" :key="metric.label" class="p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</p>
              <p class="mt-2 truncate text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ metric.value }}</p>
            </div>
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><component :is="metric.icon" class="h-4 w-4" /></span>
          </div>
          <p class="mt-4 border-t border-[var(--ds-border)] pt-3 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.context }}</p>
        </article>
      </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(19rem,0.8fr)]">
      <section class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4">
          <p class="ds-kicker">Evolucao temporal</p>
          <h2 class="ds-heading mt-1 text-base">Emissao e leitura por dia</h2>
        </header>
        <div v-if="deliveryTrend.length" class="overflow-x-auto p-5">
          <div class="min-w-[40rem] space-y-3">
            <div class="flex justify-end gap-4 text-xs font-bold text-[var(--ds-text-muted)]">
              <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[rgb(var(--primary-600-rgb))]" /> Emitidas</span>
              <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500" /> Lidas</span>
            </div>
            <div v-for="entry in deliveryTrend" :key="entry.date" class="grid grid-cols-[5rem_minmax(0,1fr)_3rem] items-center gap-3">
              <span class="text-xs font-bold text-[var(--ds-text-muted)]">{{ formatDate(entry.date) }}</span>
              <div class="space-y-1.5">
                <div class="h-2 rounded-full bg-[var(--ds-panel-muted)]"><div class="h-full rounded-full bg-[rgb(var(--primary-600-rgb))]" :style="{ width: `${(entry.sent / maxTrendValue) * 100}%` }" /></div>
                <div class="h-2 rounded-full bg-[var(--ds-panel-muted)]"><div class="h-full rounded-full bg-emerald-500" :style="{ width: `${(entry.read / maxTrendValue) * 100}%` }" /></div>
              </div>
              <span class="text-right text-xs font-black tabular-nums text-[var(--ds-text)]">{{ entry.sent }}/{{ entry.read }}</span>
            </div>
          </div>
        </div>
        <p v-else class="px-5 py-12 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem atividade no periodo selecionado.</p>
      </section>

      <section class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4">
          <p class="ds-kicker">Composicao</p>
          <h2 class="ds-heading mt-1 text-base">Distribuicao por tipo</h2>
        </header>
        <div v-if="typeDistribution.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="([type, count]) in typeDistribution" :key="type" class="px-5 py-4">
            <div class="flex items-center gap-3">
              <span class="h-2.5 w-2.5 rounded-full" :class="notificationIndicatorClasses(type)" />
              <span class="ds-badge ring-1 ring-inset" :class="notificationTypeClasses(type)">{{ notificationTypeLabel(type) }}</span>
              <span class="ml-auto text-sm font-black tabular-nums text-[var(--ds-text)]">{{ count }}</span>
            </div>
            <div class="mt-3 h-1.5 rounded-full bg-[var(--ds-panel-muted)]"><div class="h-full rounded-full" :class="notificationIndicatorClasses(type)" :style="{ width: `${percentage(count, totalTypes)}%` }" /></div>
            <p class="mt-2 text-right text-xs font-bold text-[var(--ds-text-muted)]">{{ percentage(count, totalTypes) }}%</p>
          </article>
        </div>
        <p v-else class="px-5 py-12 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem classificacoes no periodo.</p>
      </section>
    </div>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4">
        <p class="ds-kicker">Destinatarios</p>
        <h2 class="ds-heading mt-1 text-base">Utilizadores com maior volume</h2>
      </header>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[var(--ds-border)] text-left">
          <thead class="bg-[var(--ds-panel-subtle)]"><tr><th class="px-5 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Utilizador</th><th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Recebidas</th><th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Lidas</th><th class="px-5 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Taxa</th></tr></thead>
          <tbody v-if="stats.top_users?.length" class="divide-y divide-[var(--ds-border)]">
            <tr v-for="user in stats.top_users" :key="user.user_id" class="hover:bg-[var(--ds-panel-subtle)]">
              <td class="px-5 py-4"><p class="text-sm font-bold text-[var(--ds-text)]">{{ user.user_name }}</p><p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ user.user_email }}</p></td>
              <td class="px-4 py-4 text-sm font-black tabular-nums text-[var(--ds-text)]">{{ user.notification_count }}</td>
              <td class="px-4 py-4 text-sm font-black tabular-nums text-[var(--ds-text)]">{{ user.read_count }}</td>
              <td class="px-5 py-4"><div class="flex min-w-32 items-center gap-3"><div class="h-2 flex-1 rounded-full bg-[var(--ds-panel-muted)]"><div class="h-full rounded-full bg-emerald-500" :style="{ width: `${user.read_rate}%` }" /></div><span class="w-12 text-right text-xs font-black tabular-nums text-[var(--ds-text)]">{{ user.read_rate }}%</span></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="!stats.top_users?.length" class="px-5 py-12 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem utilizadores no periodo selecionado.</p>
    </section>
  </div>
</template>
