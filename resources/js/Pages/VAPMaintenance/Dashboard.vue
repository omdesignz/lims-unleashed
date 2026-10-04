<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <Head title="Manutenção e calibração" />
    <p v-if="completionError || completionRequest.hasErrors || downloadError" class="ds-field-error" role="alert">{{ completionError || Object.values(completionRequest.errors).flat().join(' ') || downloadError }}</p>
    <p v-if="downloading" class="ds-copy text-sm" role="status">A preparar o ficheiro…</p>
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:flex sm:items-start sm:justify-between sm:gap-6 lg:px-6">
        <div class="min-w-0">
          <p class="ds-kicker">Metrologia e manutenção</p>
          <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="ds-heading text-2xl">Gestão de manutenção e calibração</h1>
            <span
              :class="[
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1',
                stats.overdue > 0
                  ? 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20'
                  : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
              ]"
            >
              {{ stats.overdue > 0 ? `${stats.overdue} atrasadas` : 'Agenda em dia' }}
            </span>
          </div>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            Monitorize calibrações, manutenção preventiva, custos e equipamentos críticos com uma vista operacional densa e auditável.
          </p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 sm:mt-0 sm:justify-end">
          <span class="ds-chip">
            {{ stats.total_tasks }} tarefas totais
          </span>
          <Link
            v-if="can.create"
            :href="route('vap-maintenance.tasks.create')"
            class="ds-button ds-button-primary"
          >
            <PlusIcon class="h-4 w-4" />
            Nova tarefa
          </Link>
        </div>
      </div>

      <div class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="card in statCards"
          :key="card.label"
          class="bg-[var(--ds-panel)] p-5"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 text-3xl font-bold text-[var(--ds-text)]">{{ card.value }}</p>
              <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">{{ card.caption }}</p>
            </div>
            <span :class="['inline-flex h-10 w-10 items-center justify-center rounded-lg ring-1', card.tone]">
              <component :is="card.icon" class="h-5 w-5" />
            </span>
          </div>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-4">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="ds-field-group">
          <span class="ds-field-label">
            <TagIcon class="mr-1 inline h-4 w-4" />
            Categoria
          </span>
          <BaseSelect v-model="filterState.category_id" class="ds-field" @change="applyFilters">
            <option value="">Todas as categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </BaseSelect>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">
            <CheckCircleIcon class="mr-1 inline h-4 w-4" />
            Estado
          </span>
          <BaseSelect v-model="filterState.status" class="ds-field" @change="applyFilters">
            <option value="">Todos os estados</option>
            <option value="overdue">Atrasadas</option>
            <option value="due_soon">Vencendo em breve</option>
            <option value="executed">Executadas</option>
            <option value="planned">Planeadas</option>
          </BaseSelect>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">
            <ArrowsUpDownIcon class="mr-1 inline h-4 w-4" />
            Ordenar por
          </span>
          <BaseSelect v-model="filterState.sort_by" class="ds-field" @change="applyFilters">
            <option value="due_date">Data de vencimento</option>
            <option value="name">Nome da tarefa</option>
            <option value="created_at">Data de criação</option>
            <option value="cost">Custo</option>
          </BaseSelect>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">
            <ArrowsUpDownIcon class="mr-1 inline h-4 w-4" />
            Direcção
          </span>
          <BaseSelect v-model="filterState.sort_direction" class="ds-field" @change="applyFilters">
            <option value="asc">Ascendente</option>
            <option value="desc">Descendente</option>
          </BaseSelect>
        </label>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <ClipboardDocumentListIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Tarefas recentes
          </h2>
          <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">
            {{ taskItems.length }} tarefas nesta vista, com prioridade por vencimento e execução.
          </p>
        </div>
        <Link :href="route('vap-maintenance.tasks')" class="ds-button ds-button-secondary">
          <ArrowRightIcon class="h-4 w-4" />
          Ver todas
        </Link>
      </div>

      <div v-if="taskItems.length" class="min-w-full overflow-x-auto">
        <DataTable class="min-w-full align-middle text-sm">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-heading px-5 py-3 text-left">Tarefa</th>
              <th class="ds-table-heading px-5 py-3 text-left">Equipamento</th>
              <th class="ds-table-heading px-5 py-3 text-left">Datas</th>
              <th class="ds-table-heading px-5 py-3 text-left">Estado</th>
              <th class="ds-table-heading px-5 py-3 text-right">Acções</th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="task in taskItems" :key="task.id" class="ds-table-row">
              <td class="px-5 py-4">
                <div class="flex items-start gap-3">
                  <span :class="['inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1', getTaskColor(task).bg]">
                    <WrenchScrewdriverIcon :class="['h-5 w-5', getTaskColor(task).text]" />
                  </span>
                  <div class="min-w-0">
                    <p class="font-bold text-[var(--ds-text)]">{{ task.name }}</p>
                    <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                      {{ task.maintenance_task_no || 'Sem número' }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-[var(--ds-text-soft)]">
                      {{ task.category?.name || 'Sem categoria' }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="ds-table-cell px-5 py-4">
                <p class="font-bold text-[var(--ds-text)]">
                  {{ task.equipment?.name || 'Equipamento não encontrado' }}
                </p>
                <p v-if="task.equipment" class="mt-1 text-xs text-[var(--ds-text-soft)]">
                  {{ task.equipment.internal_code || 'Sem código' }}
                  <span v-if="task.equipment.model" class="ml-2">{{ task.equipment.model }}</span>
                </p>
              </td>
              <td class="px-5 py-4">
                <dl class="grid gap-1 text-xs">
                  <div class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-[var(--ds-text-soft)]">Vencimento</dt>
                    <dd :class="['font-bold', getDueDateColor(task)]">{{ formatDate(task.due_date) }}</dd>
                  </div>
                  <div v-if="task.previous_date" class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-[var(--ds-text-soft)]">Anterior</dt>
                    <dd class="font-bold text-[var(--ds-text-muted)]">{{ formatDate(task.previous_date) }}</dd>
                  </div>
                  <div v-if="task.next_date" class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-[var(--ds-text-soft)]">Próximo</dt>
                    <dd class="font-bold text-[var(--ds-text-muted)]">{{ formatDate(task.next_date) }}</dd>
                  </div>
                </dl>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap gap-2">
                  <span :class="getStatusClasses(task)">
                    {{ getStatusText(task) }}
                  </span>
                  <span v-if="task.is_planned" class="inline-flex items-center rounded-full bg-sky-50 px-2 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-400/20">
                    <CalendarIcon class="mr-1 h-3 w-3" />
                    Planeada
                  </span>
                  <span v-if="task.cost > 0" class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                    <CurrencyEuroIcon class="mr-1 h-3 w-3" />
                    {{ formatCurrency(task.cost) }}
                  </span>
                </div>
              </td>
              <td class="px-5 py-4 text-right">
                <div class="inline-flex items-center gap-1">
                  <Link :href="route('vap-maintenance.tasks.show', task.id)" class="ds-table-action">
                    <EyeIcon class="mr-1 h-4 w-4" />
                    Ver
                  </Link>
                  <button v-if="can.edit && !task.is_executed" type="button" class="ds-table-action" :disabled="completionRequest.processing" :aria-busy="completionRequest.processing" @click="markAsExecuted(task)">
                    <CheckCircleIcon class="mr-1 h-4 w-4" />
                    Concluir
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-else class="p-6">
        <div class="ds-empty-state px-6 py-10 text-center">
          <ClipboardDocumentListIcon class="mx-auto h-10 w-10 text-[var(--ds-text-soft)]" />
          <h3 class="mt-4 text-sm font-bold text-[var(--ds-text)]">Nenhuma tarefa encontrada</h3>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">
            Não foram encontradas tarefas correspondentes aos filtros activos.
          </p>
          <Link v-if="can.create" :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-primary mt-5">
            <PlusIcon class="h-4 w-4" />
            Criar primeira tarefa
          </Link>
        </div>
      </div>

      <div v-if="taskItems.length" class="border-t border-[var(--ds-border)] px-5 py-4">
        <Pagination
          :links="tasks.links"
          :from="tasks.from"
          :to="tasks.to"
          :total="tasks.total"
          :current_page="tasks.current_page"
          :last_page="tasks.last_page"
        />
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="ds-card p-5">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <ChartBarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
              Distribuição de tarefas
            </h3>
            <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Estado actual das tarefas criadas no período, com os filtros desta página.</p>
          </div>
          <BaseSelect v-model="chartPeriod" class="ds-field w-48" @change="loadChartData">
            <option value="month">Este mês</option>
            <option value="quarter">Últimos 3 meses</option>
            <option value="year">Últimos 12 meses</option>
          </BaseSelect>
        </div>

        <p v-if="chartError" class="ds-field-error" role="alert">{{ chartError }}</p>
        <simpleChart
          v-else-if="!chartLoading && chartData.status_stats"
          type="bar"
          :height="300"
          :chart-data="chartData"
        />
        <div v-else class="ds-empty-state flex h-64 items-center justify-center text-sm font-semibold text-[var(--ds-text-muted)]" role="status">
          A carregar dados...
        </div>
      </article>

      <article class="ds-card p-5">
        <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
          <BoltIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
          Acções rápidas
        </h3>
        <div class="mt-5 grid gap-3">
          <button v-if="can.export" type="button" :disabled="downloading" :aria-busy="downloading" class="ds-card flex items-center justify-between gap-4 p-4 text-left transition hover:border-[rgb(var(--primary-300-rgb)/0.8)]" @click="generateReport('overdue')">
            <span class="flex items-center gap-3">
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20">
                <ExclamationTriangleIcon class="h-5 w-5" />
              </span>
              <span>
                <span class="block text-sm font-bold text-[var(--ds-text)]">Relatório de atrasos</span>
                <span class="mt-0.5 block text-xs font-medium text-[var(--ds-text-muted)]">Gerar lista de tarefas vencidas.</span>
              </span>
            </span>
            <ChevronRightIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </button>

          <Link :href="route('vap-maintenance.categories')" class="ds-card flex items-center justify-between gap-4 p-4 transition hover:border-[rgb(var(--primary-300-rgb)/0.8)]">
            <span class="flex items-center gap-3">
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-400/20">
                <TagIcon class="h-5 w-5" />
              </span>
              <span>
                <span class="block text-sm font-bold text-[var(--ds-text)]">Gerir categorias</span>
                <span class="mt-0.5 block text-xs font-medium text-[var(--ds-text-muted)]">Configurar tipos de manutenção.</span>
              </span>
            </span>
            <ChevronRightIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </Link>

          <button v-if="can.export" type="button" :disabled="downloading" :aria-busy="downloading" class="ds-card flex items-center justify-between gap-4 p-4 text-left transition hover:border-[rgb(var(--primary-300-rgb)/0.8)]" @click="exportSchedule">
            <span class="flex items-center gap-3">
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                <ArrowDownTrayIcon class="h-5 w-5" />
              </span>
              <span>
                <span class="block text-sm font-bold text-[var(--ds-text)]">Exportar agenda</span>
                <span class="mt-0.5 block text-xs font-medium text-[var(--ds-text-muted)]">Descarregar agenda em Excel.</span>
              </span>
            </span>
            <ChevronRightIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </button>
        </div>
      </article>
    </section>

    <section class="ds-panel p-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <CurrencyEuroIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Análise de custo
          </h3>
          <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Impacto financeiro das intervenções e calibrações registadas.</p>
        </div>
        <div class="text-right">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Custo total</p>
          <p class="mt-1 text-2xl font-bold text-[var(--ds-text)]">{{ chartLoading || chartError ? '—' : formatCurrency(chartData.total_cost || 0) }}</p>
        </div>
      </div>

      <div v-if="!chartLoading && !chartError" class="mt-5 grid gap-px overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-border)] md:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in costCards" :key="card.label" class="bg-[var(--ds-panel)] p-4">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
          <p class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ card.value }}</p>
          <p v-if="card.caption" class="mt-1 text-xs font-medium text-[var(--ds-text-muted)]">{{ card.caption }}</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Head, Link, router, useHttp } from '@inertiajs/vue3'
import { useFileDownload } from '@/Composables/useFileDownload'
import {
  Wrench as WrenchScrewdriverIcon,
  Plus as PlusIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Clock as ClockIcon,
  Calendar as CalendarIcon,
  CircleCheck as CheckCircleIcon,
  Tag as TagIcon,
  ArrowUpDown as ArrowsUpDownIcon,
  ClipboardList as ClipboardDocumentListIcon,
  ArrowRight as ArrowRightIcon,
  Eye as EyeIcon,
  Euro as CurrencyEuroIcon,
  Zap as BoltIcon,
  ChevronRight as ChevronRightIcon,
  Download as ArrowDownTrayIcon,
  ChartColumn as ChartBarIcon,
} from '@lucide/vue'
import Pagination from '@/Components/pagination.vue'
import { debounce } from 'lodash'
import simpleChart from '@/Components/apex-chart/simple-chart.vue'

const props = defineProps({
  tasks: Object,
  categories: Array,
  filters: Object,
  stats: Object,
  initialChartData: Object,
  can: { type: Object, default: () => ({}) },
})

const chartPeriod = ref('month')
const chartLoading = ref(false)
const chartError = ref('')
let chartRequest = null
const completionRequest = useHttp({ task_ids: [], action: 'mark_executed' })
const completionError = ref('')
const { download, processing: downloading, error: downloadError } = useFileDownload()

const filterState = reactive({
  category_id: props.filters?.category_id ?? '',
  status: props.filters?.status ?? '',
  sort_by: props.filters?.sort_by ?? 'due_date',
  sort_direction: props.filters?.sort_direction ?? 'asc',
})

watch(
  () => props.filters,
  (filters) => {
    filterState.category_id = filters?.category_id ?? ''
    filterState.status = filters?.status ?? ''
    filterState.sort_by = filters?.sort_by ?? 'due_date'
    filterState.sort_direction = filters?.sort_direction ?? 'asc'
    loadChartData()
  },
  { deep: true },
)

const chartData = ref(props.initialChartData || {
  status_stats: {},
  monthly_trend: [],
  cost_stats: {},
  months: [],
  total_cost: 0,
  monthly_cost: 0,
  avg_cost: 0,
  tasks_with_cost: 0,
  highest_cost_category: null,
})

const taskItems = computed(() => props.tasks?.data ?? [])

const statCards = computed(() => [
  {
    label: 'Atrasadas',
    value: props.stats?.overdue ?? 0,
    caption: 'Tarefas com data ultrapassada.',
    icon: ExclamationTriangleIcon,
    tone: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
  },
  {
    label: 'Vencendo em breve',
    value: props.stats?.due_soon ?? 0,
    caption: 'Dentro dos próximos 30 dias.',
    icon: ClockIcon,
    tone: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
  },
  {
    label: 'Planeadas',
    value: props.stats?.planned ?? 0,
    caption: 'Intervenções programadas.',
    icon: CalendarIcon,
    tone: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-400/20',
  },
  {
    label: 'Executadas',
    value: props.stats?.executed ?? 0,
    caption: 'Tarefas concluídas e registadas.',
    icon: CheckCircleIcon,
    tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
  },
])

const costCards = computed(() => [
  {
    label: 'Custo mensal',
    value: formatCurrency(chartData.value.monthly_cost || 0),
  },
  {
    label: 'Custo médio da tarefa',
    value: formatCurrency(chartData.value.avg_cost || 0),
  },
  {
    label: 'Tarefas com custo',
    value: chartData.value.tasks_with_cost || 0,
  },
  {
    label: 'Categoria de custo mais alto',
    value: chartData.value.highest_cost_category?.name || 'N/A',
    caption: formatCurrency(chartData.value.highest_cost_category?.cost || 0),
  },
])

const formatDate = (dateString) => {
  if (!dateString) {
    return ''
  }

  const date = new Date(`${dateString}T12:00:00`)

  return date.toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA',
  }).format(amount)
}

const getTaskColor = (task) => {
  if (task.is_executed) {
    return {
      bg: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:ring-emerald-400/20',
      text: 'text-emerald-700 dark:text-emerald-200',
    }
  }

  const daysDiff = getDaysUntilDue(task)

  if (daysDiff < 0) {
    return {
      bg: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:ring-rose-400/20',
      text: 'text-rose-700 dark:text-rose-200',
    }
  }

  if (daysDiff <= 7) {
    return {
      bg: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-400/20',
      text: 'text-amber-700 dark:text-amber-200',
    }
  }

  if (daysDiff <= 30) {
    return {
      bg: 'bg-yellow-50 text-yellow-700 ring-yellow-200 dark:bg-yellow-500/10 dark:ring-yellow-400/20',
      text: 'text-yellow-700 dark:text-yellow-200',
    }
  }

  return {
    bg: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:ring-sky-400/20',
    text: 'text-sky-700 dark:text-sky-200',
  }
}

const getDueDateColor = (task) => {
  if (task.is_executed) {
    return 'text-emerald-700 dark:text-emerald-200'
  }

  const daysDiff = getDaysUntilDue(task)

  if (daysDiff < 0) {
    return 'text-rose-700 dark:text-rose-200'
  }

  if (daysDiff <= 7) {
    return 'text-amber-700 dark:text-amber-200'
  }

  if (daysDiff <= 30) {
    return 'text-yellow-700 dark:text-yellow-200'
  }

  return 'text-sky-700 dark:text-sky-200'
}

const getStatusClasses = (task) => {
  if (task.is_executed) {
    return 'inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
  }

  const daysDiff = getDaysUntilDue(task)

  if (daysDiff < 0) {
    return 'inline-flex items-center rounded-full bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20'
  }

  if (daysDiff <= 7) {
    return 'inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20'
  }

  if (daysDiff <= 30) {
    return 'inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-bold text-yellow-700 ring-1 ring-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-200 dark:ring-yellow-400/20'
  }

  return 'inline-flex items-center rounded-full bg-sky-50 px-2 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-400/20'
}

const getStatusText = (task) => {
  if (task.is_executed) {
    return 'Concluída'
  }

  const daysDiff = getDaysUntilDue(task)

  if (daysDiff < 0) {
    return 'Atrasada'
  }

  if (daysDiff <= 7) {
    return 'Vencendo em breve'
  }

  if (daysDiff <= 30) {
    return 'Próxima'
  }

  return 'Agendada'
}

const getDaysUntilDue = (task) => {
  return task.days_until_due ?? Infinity
}

const loadChartData = async () => {
  chartRequest?.abort()
  const request = new AbortController()
  chartRequest = request
  chartLoading.value = true
  chartError.value = ''
  try {
    const url = new URL(route('vap-maintenance.stats'), window.location.origin)
    url.searchParams.set('period', chartPeriod.value)
    for (const [key, value] of Object.entries(filterState)) {
      if (value !== '') url.searchParams.set(key, value)
    }

    const response = await fetch(url.toString(), {
      signal: request.signal,
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`Unable to load maintenance chart data: ${response.status}`)
    }

    const data = await response.json()
    if (chartRequest === request) chartData.value = data
  } catch (error) {
    if (!request.signal.aborted) chartError.value = 'Não foi possível carregar os indicadores. Altere o período ou actualize a página para tentar novamente.'
  } finally {
    if (chartRequest === request) chartLoading.value = false
  }
}

const applyFilters = debounce(() => {
  router.get(route('vap-maintenance.dashboard'), { ...filterState }, {
    preserveState: true,
    replace: true,
  })
}, 300)

const markAsExecuted = async (task) => {
  if (!props.can.edit || completionRequest.processing || !confirm('Concluir esta tarefa com o resultado já registado?')) return
  completionError.value = ''
  completionRequest.task_ids = [task.id]
  try {
    await completionRequest.post(route('vap-maintenance.tasks.bulk-update'), { onSuccess: () => router.reload() })
  } catch {
    completionError.value = 'Não foi possível concluir a tarefa. O resultado registado foi preservado.'
  }
}

const generateReport = (type) => {
  if (!props.can.export || downloading.value) return
  download(route('vap-maintenance.report.generate', {
    ...filterState,
    report_type: type,
    format: 'pdf',
  }))
}

const exportSchedule = () => {
  if (!props.can.export || downloading.value) return
  download(route('vap-maintenance.export', {
    ...filterState,
    type: 'calendar',
    format: 'excel',
  }))
}

onMounted(() => {
  if (!props.initialChartData) {
    loadChartData()
  }
})
onUnmounted(() => chartRequest?.abort())
</script>
