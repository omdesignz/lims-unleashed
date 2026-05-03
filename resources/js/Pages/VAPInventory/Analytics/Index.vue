<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ChartBarIcon class="h-7 w-7 text-blue-900" />
            Relatório de Análise de Estoque
          </h1>
          <p class="mt-2 text-gray-600">
            Monitore os padrões de consumo, os níveis de estoque e gere relatórios detalhados
            <span class="font-semibold text-blue-900">
              em tempo real
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="generateReport('pdf')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <DocumentArrowDownIcon class="h-4 w-4" />
            Exportar PDF
          </button>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar para Inventário
          </Link>
        </div>
      </div>
    </div>

    <!-- QUICK REPORT CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Link
        :href="route('vap-inventory.reports.low-stock')"
        class="bg-gradient-to-r from-orange-50 to-white rounded-xl border border-orange-200 p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Relatório de Estoque Baixo</h3>
            <p class="text-sm text-gray-600 mt-1">Itens com Estoque Baixo</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 text-orange-900" />
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold text-orange-900">{{ metrics.reorderAlerts || 0 }}</div>
          <div class="text-sm text-gray-500 mt-1">
            {{ metrics.criticalAlerts || 0 }} itens críticos
          </div>
        </div>
      </Link>

      <Link
        :href="route('vap-inventory.items.reagents.expiry')"
        class="bg-gradient-to-r from-red-50 to-white rounded-xl border border-red-200 p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Relatório de Validade de Reagentes</h3>
            <p class="text-sm text-gray-600 mt-1">Reagentes próximos a validade</p>
          </div>
          <CalendarIcon class="h-8 w-8 text-red-900" />
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold text-red-900">{{ metrics.expiringAlerts || 0 }}</div>
          <div class="text-sm text-gray-500 mt-1">
            Validade dentro de 60 dias
          </div>
        </div>
      </Link>

      <Link
        :href="route('vap-inventory.items.calibration.schedule')"
        class="bg-gradient-to-r from-blue-50 to-white rounded-xl border border-blue-200 p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Agenda de Calibração</h3>
            <p class="text-sm text-gray-600 mt-1">Manutenção de Equipamento</p>
          </div>
          <CogIcon class="h-8 w-8 text-blue-900" />
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold text-blue-900">{{ calibrationDue || 0 }}</div>
          <div class="text-sm text-gray-500 mt-1">
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <ChartPieIcon class="h-5 w-5 text-blue-900" />
        Estatísticas Rápidas
      </h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-900">{{ formatNumber(totalItems) }}</div>
          <div class="text-sm text-gray-600">Total de Itens</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-900">{{ formatNumber(totalConsumption) }}</div>
          <div class="text-sm text-gray-600">Total de Consumo</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-900">AOA {{ formatNumber(inventoryValue) }}</div>
          <div class="text-sm text-gray-600">Valor de Inventário</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-900">{{ formatNumber(dailyAverage) }}</div>
          <div class="text-sm text-gray-600">Uso Médio Diário</div>
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
            <label class="block text-sm font-medium text-gray-700">
              Tipo de Relatório
            </label>
            <select v-model="reportType" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
              <option value="consumption">Relatório de Consumo</option>
              <option value="stock">Relatório de Estoque</option>
              <option value="expiry">Relatório de Validade de Reagentes</option>
              <option value="calibration">Relatório de Agenda de Calibração</option>
              <option value="comprehensive">Relatório de Compromisso</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Formato
            </label>
            <select v-model="reportFormat" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
              <option value="pdf">Documento PDF</option>
              <option value="excel">Planilha de Cálculo Excel</option>
              <option value="csv">Arquivo CSV</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Intervalo de Datas
            </label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="reportStartDate" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
              <input v-model="reportEndDate" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" />
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
