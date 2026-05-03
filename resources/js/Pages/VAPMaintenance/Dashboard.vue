<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-7 w-7 text-blue-900" />
            Gestão de Manutenção e Calibração
          </h1>
          <p class="mt-2 text-gray-600">
            Monitorize e gerencie todas as atividades de manutenção e calibração
            <span v-if="stats.overdue > 0" class="font-semibold text-red-600">
              {{ stats.overdue }} tarefas atrasadas
            </span>
            <span v-else class="font-semibold text-green-600">
              Todas as tarefas em dia
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_tasks }} tarefas totais
          </span>
          <Link
            :href="route('vap-maintenance.tasks.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PlusIcon class="h-5 w-5" />
            Nova Tarefa
          </Link>
        </div>
      </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- OVERDUE CARD -->
      <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Atrasadas</p>
            <p class="text-2xl font-bold mt-1">{{ stats.overdue }}</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Tarefas com data ultrapassada</p>
      </div>

      <!-- DUE SOON CARD -->
      <div class="bg-gradient-to-r from-orange-600 to-orange-500 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencendo em Breve</p>
            <p class="text-2xl font-bold mt-1">{{ stats.due_soon }}</p>
          </div>
          <ClockIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Dentro dos próximos 30 dias</p>
      </div>

      <!-- PLANNED CARD -->
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Planeada</p>
            <p class="text-2xl font-bold mt-1">{{ stats.planned }}</p>
          </div>
          <CalendarIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Tarefas programadas</p>
      </div>

      <!-- EXECUTED CARD -->
      <div class="bg-gradient-to-r from-green-600 to-green-500 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Executadas</p>
            <p class="text-2xl font-bold mt-1">{{ stats.executed }}</p>
          </div>
          <CheckCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Tarefas concluídas</p>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CATEGORY FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <select
            v-model="filters.category_id"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- STATUS FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <CheckCircleIcon class="h-4 w-4 inline mr-1" />
            Estado
          </label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Estados</option>
            <option value="overdue">Atrasadas</option>
            <option value="due_soon">Vencendo em Breve</option>
            <option value="executed">Executadas</option>
            <option value="planned">Planeadas</option>
          </select>
        </div>

        <!-- SORT BY -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
            Ordenar Por
          </label>
          <select
            v-model="filters.sort_by"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="due_date">Data de Vencimento</option>
            <option value="name">Nome da Tarefa</option>
            <option value="created_at">Data de Criação</option>
            <option value="cost">Custo</option>
          </select>
        </div>

        <!-- SORT DIRECTION -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
            Direção
          </label>
          <select
            v-model="filters.sort_direction"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="asc">Ascendente</option>
            <option value="desc">Descendente</option>
          </select>
        </div>
      </div>
    </div>

    <!-- RECENT TASKS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
            Tarefas Recentes
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ tasks.data.length }} itens)
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <Link
              :href="route('vap-maintenance.tasks')"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <ArrowRightIcon class="h-4 w-4" />
              Ver Todas
            </Link>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Tarefa
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Equipamento
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Datas
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Acções
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="task in tasks.data"
              :key="task.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div :class="[
                    'flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center',
                    getTaskColor(task).bg
                  ]">
                    <WrenchScrewdriverIcon :class="['h-6 w-6', getTaskColor(task).text]" />
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ task.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ task.maintenance_task_no || 'Sem número' }}
                    </div>
                    <div class="text-xs text-gray-400">
                      {{ task.category?.name || 'Sem categoria' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-1">
                  <div class="text-sm font-medium text-gray-900">
                    {{ task.equipment?.name || 'Equipamento não encontrado' }}
                  </div>
                  <div v-if="task.equipment" class="text-xs text-gray-500">
                    {{ task.equipment.internal_code || 'Sem código' }}
                    <span v-if="task.equipment.model" class="ml-2">• {{ task.equipment.model }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-1">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Vencimento:</span>
                    <span :class="getDueDateColor(task)">
                      {{ formatDate(task.due_date) }}
                    </span>
                  </div>
                  <div v-if="task.previous_date" class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Anterior:</span>
                    <span class="text-gray-900">{{ formatDate(task.previous_date) }}</span>
                  </div>
                  <div v-if="task.next_date" class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Próximo:</span>
                    <span class="text-gray-900">{{ formatDate(task.next_date) }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClasses(task)">
                  {{ getStatusText(task) }}
                </span>
                <div v-if="task.is_planned" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                    <CalendarIcon class="mr-1 h-3 w-3" />
                    Planeada
                  </span>
                </div>
                <div v-if="task.cost > 0" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                    <CurrencyEuroIcon class="mr-1 h-3 w-3" />
                    {{ formatCurrency(task.cost) }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('vap-maintenance.tasks.show', task.id)"
                    class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    Ver
                  </Link>
                  <button
                    v-if="!task.is_executed"
                    @click="markAsExecuted(task)"
                    class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-sm font-medium text-green-900 hover:bg-green-100"
                  >
                    <CheckCircleIcon class="h-4 w-4 mr-1" />
                    Concluir
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div v-if="tasks.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <Pagination :links="tasks.links" :from="tasks.from" :to="tasks.to" :total="tasks.total" :current_page="tasks.current_page" :last_page="tasks.last_page" />
      </div>

      <!-- EMPTY STATE -->
      <div v-if="tasks.data.length === 0" class="p-12 text-center">
        <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhuma tarefa encontrada
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Não foram encontradas tarefas correspondentes aos seus filtros
        </p>
        <Link
          :href="route('vap-maintenance.tasks.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
        >
          <PlusIcon class="h-5 w-5" />
          Criar Primeira Tarefa
        </Link>
      </div>
    </div>

    <!-- SIMPLIFIED CHARTS SECTION (Working Version) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- TASK STATUS CHART -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            Distribuição de Estado de Tarefas
          </h3>
          <select
            v-model="chartPeriod"
            @change="loadChartData"
            class="text-sm rounded-lg border border-gray-300 px-3 py-1.5 focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="month">Último Mês</option>
            <option value="quarter">Último Trimestre</option>
            <option value="year">Último Ano</option>
          </select>
        </div>
        
        <!-- Simple chart using CSS bars -->
        <!-- <div v-if="chartData.status_stats" class="space-y-4">
          <div v-for="(status, index) in statusItems" :key="index" class="flex items-center">
            <div class="w-32 text-sm text-gray-600">{{ status.label }}</div>
            <div class="flex-1 ml-4">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-900">
                  {{ chartData.status_stats[status.key] || 0 }}
                </span>
                <span class="text-xs text-gray-500">{{ getPercentage(status.key) }}%</span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  :class="['h-full rounded-full', status.color]"
                  :style="{ width: getPercentage(status.key) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="h-64 flex items-center justify-center text-gray-400">
          Loading chart data...
        </div> -->

        <!-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"> -->
        <!-- TASK STATUS CHART -->
        <!-- <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"> -->
            
            <simpleChart
            v-if="chartData.status_stats"
            type="bar"
            :height="300"
            :chart-data="chartData"
            />
            <div v-else class="h-64 flex items-center justify-center text-gray-400">
            A carregar dados...
            </div>

            <!-- </div> -->
            
        <!-- </div> -->

      </div>

      <!-- QUICK ACTIONS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <BoltIcon class="h-5 w-5 text-blue-900" />
          Acções Rápidas
        </h3>
        <div class="space-y-3">
          <button
            @click="generateReport('overdue')"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-red-100 p-2">
                <ExclamationTriangleIcon class="h-5 w-5 text-red-600" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Relatório de Atrasos</div>
                <div class="text-sm text-gray-500">Gerar lista de tarefas atrasadas</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </button>

          <Link
            :href="route('vap-maintenance.categories')"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-blue-100 p-2">
                <TagIcon class="h-5 w-5 text-blue-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Gerir Categorias</div>
                <div class="text-sm text-gray-500">Configurar tipos de manutenção</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </Link>

          <button
            @click="exportSchedule"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-green-100 p-2">
                <ArrowDownTrayIcon class="h-5 w-5 text-green-600" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Exportar Agenda</div>
                <div class="text-sm text-gray-500">Exportar para PDF/Excel</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </button>
        </div>
      </div>
    </div>

    <!-- COST ANALYSIS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <CurrencyEuroIcon class="h-5 w-5 text-blue-900" />
          Análise de Custo
        </h3>
        <div class="text-right">
          <div class="text-2xl font-bold text-blue-900">
            {{ formatCurrency(chartData.total_cost || 0) }}
          </div>
          <div class="text-sm text-gray-500">Custo Total de Manutenção</div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Custo Mensal</div>
          <div class="text-xl font-bold text-blue-900 mt-1">
            {{ formatCurrency(chartData.monthly_cost || 0) }}
          </div>
        </div>
        <div class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Custo Médio da Tarefa</div>
          <div class="text-xl font-bold text-green-900 mt-1">
            {{ formatCurrency(chartData.avg_cost || 0) }}
          </div>
        </div>
        <div class="bg-gradient-to-r from-purple-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Tarefas com Custo</div>
          <div class="text-xl font-bold text-purple-900 mt-1">
            {{ chartData.tasks_with_cost || 0 }}
          </div>
        </div>
        <div class="bg-gradient-to-r from-orange-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Categoria de Custo Mais Alto</div>
          <div class="text-xl font-bold text-orange-900 mt-1">
            {{ chartData.highest_cost_category?.name || 'N/A' }}
          </div>
          <div class="text-sm text-gray-500">
            {{ formatCurrency(chartData.highest_cost_category?.cost || 0) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  CalendarIcon,
  CheckCircleIcon,
  TagIcon,
  ArrowsUpDownIcon,
  ClipboardDocumentListIcon,
  ArrowRightIcon,
  EyeIcon,
  CurrencyEuroIcon,
  BoltIcon,
  ChevronRightIcon,
  ArrowDownTrayIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'
import simpleChart from '@/Components/apex-chart/simple-chart.vue'

const props = defineProps({
  tasks: Object,
  categories: Array,
  filters: Object,
  stats: Object,
  initialChartData: Object,
})

const chartPeriod = ref('month')

// Chart data state
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

// Status items for the simplified chart
const statusItems = [
  { key: 'overdue', label: 'Overdue', color: 'bg-red-600' },
  { key: 'due_soon', label: 'Due Soon', color: 'bg-orange-500' },
  { key: 'scheduled', label: 'Scheduled', color: 'bg-blue-600' },
  { key: 'executed', label: 'Executed', color: 'bg-green-600' },
]

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount)
}

const getTaskColor = (task) => {
  if (task.is_executed) {
    return { bg: 'bg-green-100', text: 'text-green-900' }
  }
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) {
    return { bg: 'bg-red-100', text: 'text-red-900' }
  } else if (daysDiff <= 7) {
    return { bg: 'bg-orange-100', text: 'text-orange-900' }
  } else if (daysDiff <= 30) {
    return { bg: 'bg-yellow-100', text: 'text-yellow-900' }
  } else {
    return { bg: 'bg-blue-100', text: 'text-blue-900' }
  }
}

const getDueDateColor = (task) => {
  if (task.is_executed) return 'text-green-900'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'text-red-900'
  if (daysDiff <= 7) return 'text-orange-900'
  if (daysDiff <= 30) return 'text-yellow-900'
  return 'text-blue-900'
}

const getStatusClasses = (task) => {
  if (task.is_executed) {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (daysDiff <= 7) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (daysDiff <= 30) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800'
  }
}

const getStatusText = (task) => {
  if (task.is_executed) return 'Concluída'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'Atrasada'
  if (daysDiff <= 7) return 'Vencendo em Breve'
  if (daysDiff <= 30) return 'Próxima'
  return 'Agendada'
}

const getPercentage = (statusKey) => {
  const stats = chartData.value.status_stats || {}
  const total = Object.values(stats).reduce((sum, val) => sum + val, 0)
  if (total === 0) return 0
  return Math.round((stats[statusKey] || 0) / total * 100)
}

const loadChartData = async () => {
  try {
    const response = await axios.get(route('vap-maintenance.stats'), {
      params: { period: chartPeriod.value }
    })
    chartData.value = response.data
  } catch (error) {
    console.error('Error loading chart data:', error)
  }
}

const applyFilters = debounce(() => {
  router.get(route('vap-maintenance.dashboard'), props.filters, {
    preserveState: true,
    replace: true,
  })
}, 300)

const markAsExecuted = async (task) => {
  if (confirm('Marcar esta tarefa como concluída?')) {
    await router.put(route('vap-maintenance.tasks.update', task.id), {
      is_executed: true,
      result: 'Concluído via dashboard'
    })
  }
}

const generateReport = (type) => {
  window.open(route('vap-maintenance.report.generate', {
    report_type: type,
    format: 'pdf'
  }), '_blank')
}

const exportSchedule = () => {
  const params = new URLSearchParams(props.filters)
  window.open(route('vap-maintenance.report.generate', {
    report_type: 'schedule',
    format: 'excel',
    filters: JSON.stringify(props.filters)
  }), '_blank')
}

// Initialize charts with initial data
onMounted(() => {
  if (!props.initialChartData) {
    loadChartData()
  }
})
</script>