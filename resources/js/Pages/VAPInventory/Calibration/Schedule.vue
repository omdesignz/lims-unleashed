<template>
  <div class="calibration-schedule-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Metrology control"
      title="Agenda de Calibração de Equipamento"
      :description="`Monitore e gerencie a agenda de calibração de equipamento. ${stats.total_due} itens atrasados para calibração.`"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <button
            @click="exportSchedule"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar Agenda
          </button>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            
            Voltar ao Inventário
          </Link>
        </div>
      </template>
    </ModuleHero>

    <!-- FILTERS -->
    <ModuleCard title="Filtros de calibração" description="Priorize por estado metrológico, tipo, categoria e data alvo.">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- STATUS FILTER -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <CheckCircleIcon class="h-4 w-4 inline mr-1" />
            Estado da Calibração
          </label>
          <BaseSelect v-model="filters.status">
            <option value="">Todos os Estados</option>
            <option value="overdue">Atrasado</option>
            <option value="due_soon">Vencido em Breve (≤ 30 dias)</option>
            <option value="upcoming">Em Breve (> 30 dias)</option>
          </BaseSelect>
        </div>

        <!-- TYPE FILTER -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <CogIcon class="h-4 w-4 inline mr-1" />
            Tipo de Equipamento
          </label>
          <BaseSelect v-model="filters.type_id">
            <option value="">Todos os Tipos</option>
            <option v-for="type in types" :key="type.id" :value="type.id">
              {{ type.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- CATEGORY FILTER -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <BaseSelect v-model="filters.category_id">
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- SORT BY -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
            Ordenar Por
          </label>
          <BaseSelect v-model="filters.sort_by">
            <option value="next_calibration_date">Data de Calibração</option>
            <option value="days_to_calibration">Dias para Calibração</option>
            <option value="name">Nome do Equipamento</option>
            <option value="last_calibration_date">Última Calibração</option>
          </BaseSelect>
        </div>
      </div>
    </ModuleCard>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Atrasado</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_due }}</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Data de Calibração Ultrapassada</p>
      </div>

      <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencendo em Breve</p>
            <p class="text-2xl font-bold mt-1">{{ stats.due_soon }}</p>
          </div>
          <ClockIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Dentro de 30 dias</p>
      </div>

      <div class="bg-gradient-to-r from-green-900 to-green-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Em Breve</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_scheduled - (stats.total_due + stats.due_soon) }}</p>
          </div>
          <CalendarIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Mais de 30 dias</p>
      </div>

      <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Agenda de Calibração</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_scheduled }}</p>
          </div>
          <WrenchScrewdriverIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Com datas de calibração</p>
      </div>
    </div>

    <!-- EQUIPMENT TABLE -->
    <ModuleCard class="overflow-hidden" title="Agenda de Calibração">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">
            Agenda de Calibração
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ items.data.length }} itens)
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <button
              @click="scheduleCalibrations"
              class="inline-flex items-center gap-2 rounded-lg bg-purple-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-800"
            >
              <CalendarIcon class="h-4 w-4" />
              Agendar Calibrações
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Detalhes do Equipamento
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Datas de Calibração
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Detalhes Técnicos
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
              v-for="item in items.data"
              :key="item.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div :class="[
                      'h-10 w-10 rounded-lg flex items-center justify-center',
                      getCalibrationColor(item).bg
                    ]">
                      <WrenchScrewdriverIcon :class="['h-6 w-6', getCalibrationColor(item).text]" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ item.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ item.internal_code || 'Sem Código' }}
                    </div>
                    <div class="text-xs text-gray-400">
                      {{ item.category?.name || 'Sem Categoria' }}
                      <span v-if="item.type" class="ml-2">• {{ item.type.name }}</span>
                    </div>
                    <div v-if="item.model" class="text-xs text-gray-400">
                      Modelo: {{ item.model }}
                      <span v-if="item.brand" class="ml-2">• {{ item.brand }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Última Calibração:</span>
                    <span class="font-semibold text-gray-900">
                      {{ formatDate(item.last_calibration_date) || 'Nunca' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Próxima Calibração:</span>
                    <span class="font-semibold" :class="getNextCalibrationColor(item)">
                      {{ formatDate(item.next_calibration_date) }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Dias Restantes:</span>
                    <span class="font-semibold" :class="getDaysColor(item.days_to_calibration)">
                      {{ item.days_to_calibration }}
                    </span>
                  </div>
                  <div class="mt-2">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        :class="['h-full rounded-full', getCalibrationBarColor(item)]"
                        :style="{ width: getCalibrationPercentage(item) + '%' }"
                      ></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                      <span>Última Calibração</span>
                      <span>{{ getCalibrationPercentage(item).toFixed(0) }}% usado</span>
                      <span>Próximo Vencimento</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-1">
                  <div v-if="item.serial_number" class="text-sm">
                    <span class="text-gray-600">Número de Série:</span>
                    <span class="ml-1 font-medium">{{ item.serial_number }}</span>
                  </div>
                  <div v-if="item.internal_code" class="text-sm">
                    <span class="text-gray-600">Código Interno:</span>
                    <span class="ml-1 font-medium">{{ item.internal_code }}</span>
                  </div>
                  <div v-if="item.location" class="text-sm">
                    <span class="text-gray-600">Localização:</span>
                    <span class="ml-1 font-medium">{{ item.location }}</span>
                  </div>
                  <div v-if="item.firmware" class="text-sm">
                    <span class="text-gray-600">Firmware:</span>
                    <span class="ml-1 font-medium">{{ item.firmware }}</span>
                  </div>
                  <div v-if="item.software" class="text-sm">
                    <span class="text-gray-600">Software:</span>
                    <span class="ml-1 font-medium">{{ item.software }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClasses(item)">
                  {{ getStatusText(item) }}
                </span>
                <div v-if="item.needs_calibration" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                    <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                    Requer Calibração
                  </span>
                </div>
                <div v-if="item.days_to_calibration <= 30 && item.days_to_calibration > 0" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800">
                    <ClockIcon class="mr-1 h-3 w-3" />
                    Vencendo em Breve
                  </span>
                </div>
                <div v-if="item.has_safety_documentation" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                    <DocumentCheckIcon class="mr-1 h-3 w-3" />
                    Documentos de Segurança Disponíveis
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('vap-inventory.items.show', item.id)"
                    class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    
                    Visualizar
                  </Link>
                  <button
                    @click="recordCalibration(item)"
                    class="inline-flex items-center rounded-lg bg-purple-50 px-3 py-1.5 text-sm font-medium text-purple-900 hover:bg-purple-100"
                  >
                    <CheckCircleIcon class="h-4 w-4 mr-1" />
                    Registrar Calibração
                  </button>
                  <button
                    @click="rescheduleCalibration(item)"
                    class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                  >
                    <CalendarIcon class="h-4 w-4 mr-1" />
                    Reagendar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="items.data.length === 0" class="p-12 text-center">
        <WrenchScrewdriverIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum equipamento encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Nenhum equipamento com datas de calibração encontrado correspondendo aos seus filtros
        </p>
        <Link
          :href="route('vap-inventory.items.index')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          
          Voltar ao Inventário
        </Link>
      </div>

      <!-- PAGINATION -->
      <div v-if="items.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <Pagination :links="items.links" :from="items.from" :to="items.to" :total="items.total" :current_page="items.current_page" :last_page="items.last_page" />
      </div>
    </ModuleCard>

    <!-- CALIBRATION TIMELINE -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- CALENDAR VIEW -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Calendário de Calibração (Próximos 90 Dias)
        </h3>
        <div class="space-y-4">
          <div
            v-for="month in calibrationTimeline"
            :key="month.month"
            class="border-l-4 border-purple-900 pl-4 py-2"
          >
            <div class="flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900">{{ month.month }}</div>
                <div class="text-sm text-gray-500">{{ month.count }} calibrações previstas</div>
              </div>
              <div class="text-sm font-semibold" :class="month.color">
                {{ month.percentage }}%
              </div>
            </div>
            <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full"
                :class="month.barColor"
                :style="{ width: month.percentage + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Gestão de Calibração
        </h3>
        <div class="space-y-3">
          <button
            @click="generateCalibrationReport"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-purple-100 p-2">
                <DocumentTextIcon class="h-5 w-5 text-purple-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Relatório de Calibração</div>
                <div class="text-sm text-gray-500">Gerar certificados de calibração</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </button>

          <button
            @click="sendCalibrationAlerts"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-orange-100 p-2">
                <BellAlertIcon class="h-5 w-5 text-orange-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Enviar Lembretes</div>
                <div class="text-sm text-gray-500">Notificar técnicos sobre os vencimentos de calibração</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </button>

          <Link
            :href="route('vap-inventory.orders.create')"
            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-blue-100 p-2">
                <UserGroupIcon class="h-5 w-5 text-blue-900" />
              </div>
              <div>
                <div class="font-medium text-gray-900">Atribuir Técnicos</div>
                <div class="text-sm text-gray-500">Atribuir tarefas de calibração a membros da equipe</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-gray-400" />
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  CogIcon,
  TagIcon,
  ArrowsUpDownIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  CalendarIcon,
  EyeIcon,
  DocumentCheckIcon,
  DocumentTextIcon,
  BellAlertIcon,
  UserGroupIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  items: Object,
  filters: Object,
  categories: Array,
  types: Array,
  stats: Object,
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getCalibrationColor = (item) => {
  if (item.needs_calibration) {
    return { bg: 'bg-red-100', text: 'text-red-900' }
  } else if (item.days_to_calibration <= 30) {
    return { bg: 'bg-orange-100', text: 'text-orange-900' }
  } else if (item.days_to_calibration <= 90) {
    return { bg: 'bg-yellow-100', text: 'text-yellow-900' }
  } else {
    return { bg: 'bg-green-100', text: 'text-green-900' }
  }
}

const getNextCalibrationColor = (item) => {
  if (item.needs_calibration) return 'text-red-900'
  if (item.days_to_calibration <= 30) return 'text-orange-900'
  if (item.days_to_calibration <= 90) return 'text-yellow-900'
  return 'text-green-900'
}

const getDaysColor = (days) => {
  if (days <= 0) return 'text-red-900'
  if (days <= 30) return 'text-orange-900'
  if (days <= 90) return 'text-yellow-900'
  return 'text-green-900'
}

const getCalibrationBarColor = (item) => {
  if (item.needs_calibration) return 'bg-red-900'
  const percentage = getCalibrationPercentage(item)
  if (percentage > 80) return 'bg-orange-900'
  if (percentage > 50) return 'bg-yellow-900'
  return 'bg-green-900'
}

const getCalibrationPercentage = (item) => {
  if (!item.last_calibration_date || !item.next_calibration_date) return 0
  
  const lastCal = new Date(item.last_calibration_date)
  const nextCal = new Date(item.next_calibration_date)
  const today = new Date()
  
  const totalPeriod = nextCal - lastCal
  const elapsed = today - lastCal
  
  if (totalPeriod <= 0) return 100
  return Math.min(100, (elapsed / totalPeriod) * 100)
}

const getStatusClasses = (item) => {
  if (item.needs_calibration) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (item.days_to_calibration <= 30) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (item.days_to_calibration <= 90) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
}

const getStatusText = (item) => {
  if (item.needs_calibration) return 'Atrasado'
  if (item.days_to_calibration <= 30) return 'Vence em breve'
  if (item.days_to_calibration <= 90) return 'Próximo'
  return 'Agendado'
}

const calibrationTimeline = computed(() => {
  const months = []
  const today = new Date()
  
  for (let i = 0; i < 3; i++) {
    const monthDate = new Date(today.getFullYear(), today.getMonth() + i, 1)
    const monthName = monthDate.toLocaleDateString('pt-PT', { month: 'long', year: 'numeric' })
    
    // Count calibrations due in this month
    const count = props.items.data.filter(item => {
      if (!item.next_calibration_date) return false
      const calDate = new Date(item.next_calibration_date)
      return calDate.getMonth() === monthDate.getMonth() && 
             calDate.getFullYear() === monthDate.getFullYear()
    }).length
    
    const percentage = Math.min(100, (count / props.items.data.length) * 100)
    
    let color = 'text-green-900'
    let barColor = 'bg-green-900'
    if (i === 0) {
      color = 'text-orange-900'
      barColor = 'bg-orange-900'
    } else if (i === 1) {
      color = 'text-yellow-900'
      barColor = 'bg-yellow-900'
    }
    
    months.push({
      month: monthName,
      count,
      percentage: percentage.toFixed(1),
      color,
      barColor
    })
  }
  
  return months
})

const exportSchedule = () => {
  const params = new URLSearchParams(props.filters)
  window.open(route('vap-inventory.reports.export', {
    report_type: 'calibration',
    format: 'pdf',
    filters: JSON.stringify(props.filters)
  }), '_blank')
}

const scheduleCalibrations = () => {
  router.visit(route('vap-inventory.calibration.schedule-bulk'))
}

const recordCalibration = (item) => {
  router.visit(route('vap-inventory.calibration.record', item.id))
}

const rescheduleCalibration = (item) => {
  const newDate = prompt('Indique a nova data de calibração (YYYY-MM-DD):',
    new Date(item.next_calibration_date).toISOString().split('T')[0])

  if (newDate) {
    router.post(route('vap-inventory.calibration.reschedule', item.id), {
      next_calibration_date: newDate
    })
  }
}

const generateCalibrationReport = () => {
  window.open(route('vap-inventory.reports.export', {
    report_type: 'calibration',
    format: 'pdf',
    filters: JSON.stringify(props.filters)
  }), '_blank')
}

const sendCalibrationAlerts = async () => {
  try {
    await router.post(route('vap-inventory.calibration.send-alerts'), {
      days_threshold: 30
    })
    alert('Lembretes de calibração enviados com sucesso.')
  } catch (error) {
    console.error('Error sending alerts:', error)
  }
}

// Watch filters
watch(
  () => props.filters,
  debounce((value) => {
    router.get(route('vap-inventory.calibration.schedule'), value, {
      preserveState: true,
      replace: true,
    })
  }, 300),
  { deep: true }
)
</script>

<style scoped>
.calibration-schedule-shell :deep(.bg-white.rounded-xl),
.calibration-schedule-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(226 232 240);
  border-radius: 1.5rem;
  background: rgb(255 255 255);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
}

.calibration-schedule-shell :deep(thead.bg-gradient-to-r) {
  background: linear-gradient(90deg, rgb(248 250 252), rgb(241 245 249));
}

.calibration-schedule-shell :deep(.bg-gray-50),
.calibration-schedule-shell :deep(.bg-gray-100) {
  border-color: rgb(226 232 240);
  background: rgb(248 250 252 / 0.84);
}

.calibration-schedule-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-900, 30 58 138));
}

.calibration-schedule-shell :deep(.bg-blue-50) {
  background-color: rgb(var(--color-primary-50, 239 246 255));
}

.calibration-schedule-shell :deep(.bg-blue-100) {
  background-color: rgb(var(--color-primary-100, 219 234 254));
}

.calibration-schedule-shell :deep(.border-gray-200),
.calibration-schedule-shell :deep(.border-gray-300),
.calibration-schedule-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(226 232 240);
}

.calibration-schedule-shell :deep(.hover\:bg-gray-50:hover),
.calibration-schedule-shell :deep(.hover\:bg-gray-100:hover),
.calibration-schedule-shell :deep(tr:hover) {
  background: rgb(var(--color-primary-50, 239 246 255) / 0.58);
}

:global(.dark) .calibration-schedule-shell :deep(.bg-white.rounded-xl),
:global(.dark) .calibration-schedule-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(30 41 59);
  background:
    radial-gradient(circle at top right, rgb(var(--color-primary-500, 59 130 246) / 0.1), transparent 30%),
    rgb(2 6 23);
}

:global(.dark) .calibration-schedule-shell :deep(.bg-white),
:global(.dark) .calibration-schedule-shell :deep(tbody.bg-white) {
  background: rgb(2 6 23);
}

:global(.dark) .calibration-schedule-shell :deep(thead.bg-gradient-to-r) {
  background: linear-gradient(90deg, rgb(15 23 42), rgb(30 41 59));
}

:global(.dark) .calibration-schedule-shell :deep(.bg-gray-50),
:global(.dark) .calibration-schedule-shell :deep(.bg-gray-100),
:global(.dark) .calibration-schedule-shell :deep(.hover\:bg-gray-50:hover),
:global(.dark) .calibration-schedule-shell :deep(.hover\:bg-gray-100:hover),
:global(.dark) .calibration-schedule-shell :deep(tr:hover) {
  border-color: rgb(51 65 85);
  background: rgb(15 23 42 / 0.72);
}

:global(.dark) .calibration-schedule-shell :deep(.bg-gray-200),
:global(.dark) .calibration-schedule-shell :deep(.bg-gray-300) {
  background-color: rgb(30 41 59);
}

:global(.dark) .calibration-schedule-shell :deep(.text-gray-900),
:global(.dark) .calibration-schedule-shell :deep(.text-gray-800),
:global(.dark) .calibration-schedule-shell :deep(.text-gray-700) {
  color: rgb(226 232 240);
}

:global(.dark) .calibration-schedule-shell :deep(.text-gray-600),
:global(.dark) .calibration-schedule-shell :deep(.text-gray-500),
:global(.dark) .calibration-schedule-shell :deep(.text-gray-400) {
  color: rgb(148 163 184);
}

:global(.dark) .calibration-schedule-shell :deep(.border-gray-200),
:global(.dark) .calibration-schedule-shell :deep(.border-gray-300),
:global(.dark) .calibration-schedule-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(30 41 59);
}

:global(.dark) .calibration-schedule-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-200, 191 219 254));
}

:global(.dark) .calibration-schedule-shell :deep(.bg-blue-50),
:global(.dark) .calibration-schedule-shell :deep(.bg-blue-100) {
  background-color: rgb(var(--color-primary-500, 59 130 246) / 0.1);
}

:global(.dark) .calibration-schedule-shell :deep(.text-red-900),
:global(.dark) .calibration-schedule-shell :deep(.text-red-800) {
  color: rgb(252 165 165);
}

:global(.dark) .calibration-schedule-shell :deep(.text-orange-900),
:global(.dark) .calibration-schedule-shell :deep(.text-orange-800),
:global(.dark) .calibration-schedule-shell :deep(.text-yellow-900),
:global(.dark) .calibration-schedule-shell :deep(.text-yellow-800) {
  color: rgb(253 230 138);
}

:global(.dark) .calibration-schedule-shell :deep(.text-green-900),
:global(.dark) .calibration-schedule-shell :deep(.text-green-800) {
  color: rgb(110 231 183);
}

:global(.dark) .calibration-schedule-shell :deep(.bg-red-100) {
  background-color: rgb(239 68 68 / 0.12);
}

:global(.dark) .calibration-schedule-shell :deep(.bg-orange-100),
:global(.dark) .calibration-schedule-shell :deep(.bg-yellow-100) {
  background-color: rgb(245 158 11 / 0.12);
}

:global(.dark) .calibration-schedule-shell :deep(.bg-green-100) {
  background-color: rgb(34 197 94 / 0.12);
}
</style>
