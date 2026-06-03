<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-900/5 dark:border-slate-800 dark:bg-slate-950 dark:ring-white/10">
      <div class="bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.18),_transparent_34%),linear-gradient(135deg,_#f8fafc,_#ffffff)] p-6 dark:bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.28),_transparent_34%),linear-gradient(135deg,_#020617,_#0f172a)]">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <div class="mb-3 inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-900 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
            ISO 17025 · Inventory Intelligence
          </div>
          <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-950 dark:text-white">
            <span class="rounded-2xl bg-blue-900 p-2 text-white shadow-lg shadow-blue-900/20 dark:bg-blue-500">
              <ChartBarIcon class="h-6 w-6" />
            </span>
            Relatório de Análise de Estoque
          </h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            Monitore padrões de consumo, níveis de estoque, validade e alertas operacionais com relatórios rastreáveis
            <span class="font-semibold text-blue-900 dark:text-blue-300">
              em tempo real
            </span>
          </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <button
            @click="generateReport('pdf')"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-200"
          >
            <DocumentArrowDownIcon class="h-4 w-4" />
            Exportar PDF
          </button>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-900/20 transition hover:bg-blue-800 dark:bg-blue-500 dark:hover:bg-blue-400"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar para Inventário
          </Link>
        </div>
      </div>
      </div>
    </div>

    <!-- QUICK REPORT CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Link
        :href="route('vap-inventory.reports.low-stock')"
        class="group rounded-3xl border border-orange-200 bg-gradient-to-br from-orange-50 via-white to-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-orange-500/20 dark:from-orange-500/10 dark:via-slate-950 dark:to-slate-900"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Relatório de Estoque Baixo</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Itens com Estoque Baixo</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 text-orange-900 dark:text-orange-300" />
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold text-orange-900 dark:text-orange-200">{{ metrics.reorderAlerts || 0 }}</div>
          <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ metrics.criticalAlerts || 0 }} itens críticos
          </div>
        </div>
      </Link>

      <Link
        :href="route('vap-inventory.items.reagents.expiry')"
        class="group rounded-3xl border border-red-200 bg-gradient-to-br from-red-50 via-white to-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-red-500/20 dark:from-red-500/10 dark:via-slate-950 dark:to-slate-900"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Relatório de Validade de Reagentes</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Reagentes próximos a validade</p>
          </div>
          <CalendarIcon class="h-8 w-8 text-red-900 dark:text-red-300" />
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold text-red-900 dark:text-red-200">{{ metrics.expiringAlerts || 0 }}</div>
          <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Validade dentro de 60 dias
          </div>
        </div>
      </Link>

      <Link
        :href="route('vap-inventory.items.calibration.schedule')"
        class="group rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-blue-500/20 dark:from-blue-500/10 dark:via-slate-950 dark:to-slate-900"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Agenda de Calibração</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Manutenção de Equipamento</p>
          </div>
          <CogIcon class="h-8 w-8 text-blue-900 dark:text-blue-300" />
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold text-blue-900 dark:text-blue-200">{{ calibrationDue || 0 }}</div>
          <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Equipamentos com atraso de calibração
          </div>
        </div>
      </Link>
    </div>

    <!-- MAIN ANALYTICS COMPONENT -->
    <InventoryAnalytics
      :initial-data="initialData"
      :categories="categories"
      :warehouses="warehouses"
    />

    <!-- QUICK STATS PANEL -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950">
      <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
        <ChartPieIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
        Estatísticas Rápidas
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-900 dark:text-blue-300">{{ formatNumber(totalItems) }}</div>
          <div class="text-sm text-slate-600 dark:text-slate-400">Total de Itens</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-900 dark:text-emerald-300">{{ formatNumber(totalConsumption) }}</div>
          <div class="text-sm text-slate-600 dark:text-slate-400">Total de Consumo</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-900 dark:text-violet-300">AOA {{ formatNumber(inventoryValue) }}</div>
          <div class="text-sm text-slate-600 dark:text-slate-400">Valor de Inventário</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-900 dark:text-orange-300">{{ formatNumber(dailyAverage) }}</div>
          <div class="text-sm text-slate-600 dark:text-slate-400">Uso Médio Diário</div>
        </div>
      </div>
    </div>

    <!-- REPORT GENERATION MODAL -->
    <ConfirmationModal
      :show="showReportModal"
      @close="showReportModal = false"
      @confirm="confirmReportGeneration"
    >
      <template #title>
        Gerar Relatório
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Tipo de Relatório
            </label>
            <select v-model="reportType" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
              <option value="consumption">Relatório de Consumo</option>
              <option value="stock">Relatório de Estoque</option>
              <option value="expiry">Relatório de Validade de Reagentes</option>
              <option value="calibration">Relatório de Agenda de Calibração</option>
              <option value="comprehensive">Relatório de Compromisso</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Formato
            </label>
            <select v-model="reportFormat" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
              <option value="pdf">Documento PDF</option>
              <option value="excel">Planilha de Cálculo Excel</option>
              <option value="csv">Arquivo CSV</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Intervalo de Datas
            </label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="reportStartDate" type="date" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
              <input v-model="reportEndDate" type="date" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
          </div>
        </div>
      </template>
      <template #confirmButton>
        <button
          type="button"
          :disabled="generatingReport"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm',
            generatingReport
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
          ]"
          @click="confirmReportGeneration"
        >
          <DocumentArrowDownIcon class="h-4 w-4" />
          {{ generatingReport ? 'Gerando...' : 'Gerar Relatório' }}
        </button> 
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link } from '@inertiajs/vue3'
import {
  ChartBarIcon,
  DocumentArrowDownIcon,
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  CogIcon,
  ChartPieIcon,
} from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import axios from 'axios'

const InventoryAnalytics = defineAsyncComponent(() => import('@/Components/charts/inventory-analytics.vue'))

const props = defineProps({
  initialData: Object,
  categories: Array,
  warehouses: Array,
})

const showReportModal = ref(false)
const generatingReport = ref(false)
const reportType = ref('consumption')
const reportFormat = ref('pdf')
const reportStartDate = ref('')
const reportEndDate = ref('')

const metrics = computed(() => props.initialData?.metrics ?? {})
const totalItems = computed(() => metrics.value?.total_items || 0)
const totalConsumption = computed(() => metrics.value?.totalConsumption || 0)
const inventoryValue = computed(() => metrics.value?.inventoryValue || 0)
const dailyAverage = computed(() => metrics.value?.dailyAverage || 0)
const calibrationDue = computed(() => metrics.value?.itemsNeedingCalibration || 0)

const formatNumber = (num) => {
  const normalized = Number(num ?? 0)

  return new Intl.NumberFormat('en-US').format(
    Number.isFinite(normalized) ? normalized.toFixed(2) : '0.00'
  )
}

const generateReport = (format) => {
  reportFormat.value = format
  reportStartDate.value = new Date().toISOString().split('T')[0]
  reportEndDate.value = new Date().toISOString().split('T')[0]
  showReportModal.value = true
}

const confirmReportGeneration = async () => {
  generatingReport.value = true
  
  try {
    const response = await axios.post(route('vap-inventory.analytics.report'), { 
      reportType: reportType.value,
      format: reportFormat.value,
      startDate: reportStartDate.value,
      endDate: reportEndDate.value,
    }, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${reportType.value}_report_${new Date().toISOString().split('T')[0]}.${reportFormat.value}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    showReportModal.value = false
  } catch (error) {
    console.error('Error generating report:', error)
  } finally {
    generatingReport.value = false
  }
}

onMounted(() => {
  // Set default dates for report
  const endDate = new Date()
  const startDate = new Date()
  startDate.setMonth(startDate.getMonth() - 1)
  
  reportStartDate.value = startDate.toISOString().split('T')[0]
  reportEndDate.value = endDate.toISOString().split('T')[0]
})
</script>
