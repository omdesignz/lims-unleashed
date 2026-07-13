<template>
  <div class="space-y-6">
    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Controlo de desempenho</p>
          <div class="mt-2 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))]">
              <ChartBarSquareIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-xl sm:text-2xl">Indicadores laboratoriais</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Volume de amostras, conclusão analítica, tempo de resposta e faturação no período selecionado.</p>
            </div>
          </div>
        </div>

        <Link :href="route('analysis.index')" class="ds-button ds-button-primary shrink-0">
          <BeakerIcon class="h-4 w-4" />
          Abrir fila analítica
        </Link>
      </div>

      <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Período de referência</p>
            <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ periodLabel }}</p>
          </div>
          <div class="w-full sm:w-auto">
            <date-picker
              v-model.range.string="query.date"
              locale="pt-PT"
              color="blue"
              mode="date"
              range
              :input-debounce="500"
              :masks="masks"
              @update:model-value="(value) => query.date = value"
            />
          </div>
        </div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <p class="ds-kicker">Produção</p>
        <h2 class="ds-heading mt-1 text-base">Fluxo de amostras</h2>
      </div>

      <dl class="grid sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div v-for="metric in sampleMetrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-5 py-5 sm:[&:nth-child(odd)]:border-r xl:border-b-0 xl:[&:nth-child(odd)]:border-r-0 sm:px-6">
          <div class="flex items-start justify-between gap-3">
            <div>
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0" :class="metric.iconClass" />
          </div>
        </div>
      </dl>

      <div class="border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex items-center justify-between gap-3 text-sm">
          <span class="font-bold text-[var(--ds-text)]">Conclusão analítica</span>
          <span class="font-bold tabular-nums text-[var(--ds-text)]">{{ completionRate }}%</span>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-[var(--ds-panel-subtle)]">
          <div class="h-full rounded-full bg-emerald-500 transition-[width]" :style="{ width: `${completionRate}%` }"></div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-semibold text-[var(--ds-text-muted)]">
          <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ finalized }} concluídas</span>
          <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ pending }} pendentes</span>
        </div>
      </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="ds-panel overflow-hidden">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Tempo de resposta</p>
            <h2 class="ds-heading mt-1 text-base">Ciclo analítico médio</h2>
          </div>
          <ClockIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <div class="px-5 py-6 sm:px-6">
          <p class="text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ average_response_time || 'Sem dados concluídos' }}</p>
          <p class="ds-copy mt-2 text-sm">Intervalo médio entre o início e o fim da análise das amostras concluídas no período.</p>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Receita emitida</p>
            <h2 class="ds-heading mt-1 text-base">Faturação validada</h2>
          </div>
          <BanknotesIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <div class="px-5 py-6 sm:px-6">
          <p class="text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ formatCurrency(total_invoice_amount) }}</p>
          <p class="ds-copy mt-2 text-sm">Total das faturas emitidas e válidas dentro do período de referência.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import {
  BanknotesIcon,
  BeakerIcon,
  ChartBarSquareIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline'
import datePicker from '@/Components/date-picker.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  total_records: { type: Number, default: 0 },
  total_finalized_records: { type: Number, default: 0 },
  total_to_be_finalized_records: { type: Number, default: 0 },
  average_response_time: { type: String, default: '' },
  total_invoice_amount: { type: Number, default: 0 },
  query: { type: Object, default: () => ({}) },
})

const query = reactive({
  date: props.query?.date || null,
})
const masks = {
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
}

const total = computed(() => Number(props.total_records || 0))
const finalized = computed(() => Number(props.total_finalized_records || 0))
const pending = computed(() => Number(props.total_to_be_finalized_records || 0))
const completionRate = computed(() => total.value ? Math.min(100, Math.round((finalized.value / total.value) * 100)) : 0)
const periodLabel = computed(() => {
  const start = query.date?.start
  const end = query.date?.end

  if (!start && !end) {
    return 'Todo o histórico disponível'
  }

  return `${formatDate(start) || 'Início'} a ${formatDate(end) || 'Hoje'}`
})
const sampleMetrics = computed(() => [
  { label: 'Amostras registadas', value: total.value, detail: 'no período', icon: QueueListIcon, iconClass: 'text-[rgb(var(--primary-700-rgb))]' },
  { label: 'Concluídas', value: finalized.value, detail: `${completionRate.value}% do volume`, icon: CheckCircleIcon, iconClass: 'text-emerald-600 dark:text-emerald-300' },
  { label: 'Pendentes', value: pending.value, detail: 'aguardam conclusão', icon: ExclamationTriangleIcon, iconClass: 'text-amber-600 dark:text-amber-300' },
  { label: 'Diferença de controlo', value: Math.max(0, total.value - finalized.value - pending.value), detail: 'fora dos dois estados', icon: BeakerIcon, iconClass: 'text-[var(--ds-text-soft)]' },
])

function formatDate(value) {
  if (!value) {
    return ''
  }

  return new Intl.DateTimeFormat('pt-PT').format(new Date(`${value}T00:00:00`))
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

watch(query, debounce((value) => {
  router.get(route('metrics.index'), {
    date: value.date || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 300))
</script>
