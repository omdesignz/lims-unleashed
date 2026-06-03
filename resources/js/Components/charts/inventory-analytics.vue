<template>
  <div class="space-y-6">
    <!-- FILTER CONTROLS -->
    <div :class="panelClass">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- DATE RANGE -->
        <div class="space-y-2">
          <label :class="labelClass">
            <CalendarIcon class="h-4 w-4 inline mr-1" />
            Intervalo de Datas
          </label>
          <select
            v-model="filters.dateRange"
            @change="updateCharts"
            :class="inputClass"
          >
            <option value="7d">Últimos 7 Dias</option>
            <option value="30d">Últimos 30 Dias</option>
            <option value="90d">Últimos 90 Dias</option>
            <option value="1y">Último Ano</option>
            <option value="custom">Intervalo Personalizado</option>
          </select>
        </div>

        <!-- CATEGORY FILTER -->
        <div class="space-y-2">
          <label :class="labelClass">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <select
            v-model="filters.categoryId"
            @change="updateCharts"
            :class="inputClass"
          >
            <option value="">Todas</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- WAREHOUSE FILTER -->
        <div class="space-y-2">
          <label :class="labelClass">
            <BuildingLibraryIcon class="h-4 w-4 inline mr-1" />
            Armazém
          </label>
          <select
            v-model="filters.warehouseId"
            @change="updateCharts"
            :class="inputClass"
          >
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </select>
        </div>

        <!-- CUSTOM DATE RANGE -->
        <div v-if="filters.dateRange === 'custom'" class="space-y-2">
          <label :class="labelClass">
            Período Personalizado
          </label>
          <div class="grid grid-cols-2 gap-2">
            <input
              v-model="filters.startDate"
              type="date"
              @change="updateCharts"
              :class="inputClass"
            />
            <input
              v-model="filters.endDate"
              type="date"
              @change="updateCharts"
              :class="inputClass"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- CHARTS GRID -->
    <div :class="['grid grid-cols-1 lg:grid-cols-2 gap-6 transition-opacity duration-300', isLoading ? 'opacity-50 pointer-events-none' : 'opacity-100']">
      <!-- REAGENT CONSUMPTION TREND -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ArrowTrendingUpIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              Tendência de Consumo de Reagentes
            </h3>
            <p :class="mutedClass">Tendência de consumo de reagentes por dia</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="downloadChart('consumption')"
              :class="smallButtonClass"
            >
              <ArrowDownTrayIcon class="h-3 w-3" />
              Exportar
            </button>
          </div>
        </div>
        <apexchart
          v-if="consumptionSeries?.length > 0"
          type="line"
          height="300"
          :options="consumptionChartOptions"
          :series="consumptionSeries"
        />
        <div v-else class="flex h-[300px] items-center justify-center text-slate-400 dark:text-slate-500">
          Nenhuma informação de consumo disponível
        </div>
      </div>

      <!-- STOCK LEVELS BY CATEGORY -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ChartPieIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              Distribuição de Estoque por Categoria
            </h3>
            <p :class="mutedClass">Níveis de estoque atuais em todas as categorias</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="toggleStockChartType"
              :class="smallButtonClass"
            >
              <ArrowsRightLeftIcon class="h-3 w-3" />
              {{ stockChartType === 'pie' ? 'Visão de Barras' : 'Visão de Pizza' }}
            </button>
          </div>
        </div>
        <apexchart
          v-if="stockSeries?.length > 0"
          :key="stockChartType"
          :type="stockChartType"
          height="300"
          :options="stockChartOptions"
          :series="stockSeries"
        />
        <div v-else class="flex h-[300px] items-center justify-center text-slate-400 dark:text-slate-500">
          Sem Dados Disponíveis
        </div>
      </div>

      <div :class="panelClass">

        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ArchiveBoxIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              Desempenho do Fornecedor
            </h3>
            <p :class="mutedClass">
                Desempenho de Entrega do Fornecedor ao longo do tempo
            </p>
          </div>
        </div>
        
            <div v-if="supplierSeries[0]?.data.length > 0">
                <apexchart
                    type="bar"
                    height="350"
                    :options="supplierChartOptions"
                    :series="supplierSeries"
                />
            </div>
            <div v-else class="flex h-[350px] flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                <ChartBarIcon class="h-12 w-12 mb-2 opacity-20" />
                <p class="text-sm">Nenhuma entrega encontrada para os filtros selecionados.</p>
            </div>
        </div>

      <!-- MONTHLY CONSUMPTION COMPARISON -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ChartBarIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              Comparação Mensal de Consumo
            </h3>
            <p :class="mutedClass">Padrões de uso mensais</p>
          </div>
        </div>
        <apexchart
          v-if="monthlySeries?.length > 0"
          type="bar"
          height="300"
          :options="monthlyChartOptions"
          :series="monthlySeries"
        />
        <div v-else class="flex h-[300px] items-center justify-center text-slate-400 dark:text-slate-500">
          Sem Dados Disponíveis
        </div>
      </div>

      <!-- TOP CONSUMED REAGENTS -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <TrophyIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              Reagentos Mais Consumidos
            </h3>
            <p :class="mutedClass">Reagentos mais frequentemente consumidos</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="toggleTopReagentsLimit"
              :class="smallButtonClass"
            >
              {{ topReagentsLimit === 5 ? 'Mostrar 10' : 'Mostrar 5' }}
            </button>
          </div>
        </div>
        <apexchart
          v-if="topReagentsSeries?.length > 0"
          type="bar"
          height="300"
          :options="topReagentsChartOptions"
          :series="topReagentsSeries"
        />
        <div v-else class="flex h-[300px] items-center justify-center text-slate-400 dark:text-slate-500">
          Sem Dados Disponíveis
        </div>
      </div>
    </div>

    <!-- ADDITIONAL METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- TOTAL CONSUMPTION -->
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Consumo Total</p>
            <p class="text-2xl font-bold mt-1">{{ formatNumber(totalConsumption) }} unidades</p>
          </div>
          <BeakerIcon class="h-8 w-8 opacity-50" />
        </div>
        <div class="mt-4 text-sm opacity-80">
          <div class="flex justify-between">
            <span>Este mês:</span>
            <span class="font-semibold">{{ formatNumber(monthlyConsumption) }} unidades</span>
          </div>
          <div class="flex justify-between mt-1">
            <span>Média Diária:</span>
            <span class="font-semibold">{{ formatNumber(dailyAverage) }} unidades</span>
          </div>
        </div>
      </div>

      <div class="bg-gradient-to-r from-green-900 to-green-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
            <p class="text-sm font-medium opacity-90">Uso Médio Diário</p>
            <p class="text-2xl font-bold mt-1">{{ formatNumber(dailyAverage) }}</p>
            </div>
            <ArrowTrendingUpIcon class="h-8 w-8 opacity-50" />
        </div>
        <div class="mt-4 text-sm">
            <span :class="['font-semibold px-2 py-0.5 rounded', usageChange >= 0 ? 'bg-red-500/30' : 'bg-green-500/30']">
            {{ usageChange >= 0 ? '↑' : '↓' }} {{ Math.abs(usageChange) }}%
            </span>
            <span class="opacity-70 ml-2">vs Período Anterior</span>
        </div>
        </div>

      <div :class="[
        'rounded-xl shadow-sm p-6 text-white relative group transition-all duration-300',
        criticalAlerts > 0 ? 'bg-gradient-to-r from-red-900 to-red-800' : 'bg-gradient-to-r from-orange-900 to-orange-800'
        ]">
        <div class="flex items-center justify-between">
            <div>
            <p class="text-sm font-medium opacity-90">Alertas de Inventário</p>
            <p class="text-2xl font-bold mt-1">{{ reorderAlerts + expiringAlerts + criticalAlerts }}</p>
            </div>
            <BellAlertIcon class="h-8 w-8 opacity-50" />
        </div>

        <div class="mt-4 text-sm opacity-80">
            <div class="flex justify-between">
            <span>Crítico / Expirado:</span>
            <span class="font-semibold">{{ criticalAlerts }}</span>
            </div>
            <div class="flex justify-between mt-1">
            <span>Baixo Estoque:</span>
            <span class="font-semibold">{{ reorderAlerts }}</span>
            </div>
            <div class="flex justify-between mt-1">
            <span>Expirando < 30d:</span>
            <span class="font-semibold">{{ expiringAlerts }}</span>
            </div>
        </div>

        <div class="invisible absolute top-0 left-full z-50 ml-4 w-64 rounded-2xl border border-slate-200 bg-white p-4 text-slate-900 shadow-xl transition-all group-hover:visible dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Atenção de Prioridade</h4>
            
            <div v-if="alertDetails.critical.length" class="mb-3">
            <p class="text-[10px] font-bold text-red-600 uppercase mb-1">Crítico / Sem Estoque</p>
            <ul class="text-xs space-y-1">
                <li v-for="item in alertDetails.critical" :key="item" class="truncate">• {{ item }}</li>
            </ul>
            </div>

            <div v-if="alertDetails.reorder.length" class="mb-3">
            <p class="text-[10px] font-bold text-orange-600 uppercase mb-1">Baixo Nível de Estoque</p>
            <ul class="text-xs space-y-1">
                <li v-for="item in alertDetails.reorder" :key="item" class="truncate">• {{ item }}</li>
            </ul>
            </div>

            <div v-if="alertDetails.expiring.length">
            <p class="text-[10px] font-bold text-blue-600 uppercase mb-1">Expirando em Breve</p>
            <ul class="text-xs space-y-1">
                <li v-for="item in alertDetails.expiring" :key="item" class="truncate">• {{ item }}</li>
            </ul>
            </div>
            
            <p v-if="reorderAlerts + expiringAlerts + criticalAlerts === 0" class="text-xs italic text-slate-500 dark:text-slate-400">
            Todos os níveis de estoque estão saudáveis.
            </p>

            <button 
                @click="generateDrafts"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-white shadow-sm transition-colors hover:bg-blue-800 dark:bg-blue-500 dark:hover:bg-blue-400"
                >
                <ShoppingCartIcon class="h-4 w-4" />
                Gerar Rascunhos de Reabastecimento
            </button>
        </div>

        </div>

      <!-- INVENTORY VALUE -->
      <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Valor de Inventário</p>
            <p class="text-2xl font-bold mt-1">${{ formatNumber(inventoryValue) }}</p>
          </div>
          <CurrencyDollarIcon class="h-8 w-8 opacity-50" />
        </div>
        <div class="mt-4 text-sm opacity-80">
          <div class="flex justify-between">
            <span>Reagentes:</span>
            <span class="font-semibold">${{ formatNumber(reagentsValue) }}</span>
          </div>
          <div class="flex justify-between mt-1">
            <span>Equipmentos:</span>
            <span class="font-semibold">${{ formatNumber(equipmentValue) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- DETAILED CONSUMPTION TABLE -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
          <TableCellsIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
          Histórico de Consumo Detalhado
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-900/80">
            <tr>
              <th :class="tableHeadClass">
                Data
              </th>
              <th :class="tableHeadClass">
                Reagente
              </th>
              <th :class="tableHeadClass">
                Quantidade Usada
              </th>
              <th :class="tableHeadClass">
                Usado Por
              </th>
              <th :class="tableHeadClass">
                Armazém
              </th>
              <th :class="tableHeadClass">
                Observações
              </th>

              <th :class="tableHeadClass">
                Saúde do Estoque
                </th>

                <th :class="tableHeadClass">7-Dias Tendência</th>
                <th :class="tableHeadClass">
                Est. de Desgaste
                </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr
              v-for="consumption in consumptionHistory"
              :key="consumption.id"
              class="hover:bg-blue-50/50 dark:hover:bg-blue-950/20"
              :class="{'bg-red-50/50 dark:bg-red-950/20': isUrgent(consumption)}"
            >
              <td :class="tableCellClass">
                {{ formatDate(consumption.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-slate-950 dark:text-white">{{ consumption.reagent_name }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ consumption.item?.code || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ consumption.quantity_used }} unidades
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ consumption.used_by || 'N/A' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ consumption.warehouse?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ consumption.remarks || '-' }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <div class="w-full max-w-[120px]">
                    <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">
                        {{ consumption.current_stock }} / {{ consumption.min_level * 2 }} 
                    </span>
                    <span :class="[
                        'text-[10px] font-bold px-1 rounded',
                        getStockStatusColor(consumption)
                    ]">
                        {{ getStockStatusLabel(consumption) }}
                    </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-slate-200 dark:bg-slate-800">
                    <div 
                        :class="['h-1.5 rounded-full transition-all duration-500', getProgressBarColor(consumption)]"
                        :style="{ width: getStockPercentage(consumption) + '%' }"
                    ></div>
                    </div>
                </div>
                </td>

                <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-10 w-24">
                    <apexchart
                    type="area"
                    height="40"
                    width="100"
                    :options="sparklineOptions"
                    :series="[{ data: consumption.sparkline }]"
                    />
                </div>
                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div v-if="consumption.days_remaining !== null" class="flex flex-col">
                    <span :class="[
                    'font-bold',
                    consumption.days_remaining < 7 ? 'text-red-600 dark:text-red-300' : 'text-slate-900 dark:text-slate-100'
                    ]">
                    {{ consumption.days_remaining }} dias restantes
                    </span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">
                    Est: {{ formatDate(consumption.predicted_out_date) }}
                    </span>
                </div>
                <div v-else class="text-xs italic text-slate-400 dark:text-slate-500">
                    Nenhuma utilização recente
                </div>
                </td>

            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="consumptionHistory?.length === 0" class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-950 dark:text-white">Nenhum registro de consumo encontrado</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Comece a usar reagentes para ver o histórico de consumo</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  CalendarIcon,
  TagIcon,
  BuildingLibraryIcon,
  ArrowDownTrayIcon,
  ArrowTrendingUpIcon,
  ChartPieIcon,
  ChartBarIcon,
  ArrowsRightLeftIcon,
  TrophyIcon,
  BeakerIcon,
  BellAlertIcon,
  CurrencyDollarIcon,
  TableCellsIcon,
  ShoppingCartIcon,
  ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  initialData: Object,
  categories: Array,
  warehouses: Array,
})

const panelClass = 'rounded-3xl border border-slate-200 bg-white p-6 shadow-sm ring-1 ring-slate-900/5 dark:border-slate-800 dark:bg-slate-950 dark:ring-white/10'
const labelClass = 'block text-sm font-semibold text-slate-700 dark:text-slate-200'
const inputClass = 'w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-400'
const titleClass = 'flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white'
const mutedClass = 'mt-1 text-sm text-slate-500 dark:text-slate-400'
const smallButtonClass = 'inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-200'
const tableHeadClass = 'px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400'
const tableCellClass = 'whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-100'

const isDarkMode = ref(false)
let themeObserver = null

const syncDarkMode = () => {
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

const chartTheme = computed(() => ({
  theme: {
    mode: isDarkMode.value ? 'dark' : 'light',
  },
  chart: {
    background: 'transparent',
    foreColor: isDarkMode.value ? '#cbd5e1' : '#475569',
  },
  grid: {
    borderColor: isDarkMode.value ? '#1e293b' : '#e2e8f0',
    strokeDashArray: 5,
  },
  tooltip: {
    theme: isDarkMode.value ? 'dark' : 'light',
  },
}))

const filters = ref({
  dateRange: '30d',
  categoryId: '',
  warehouseId: '',
  startDate: '',
  endDate: '',
})

const alertDetails = ref({
  reorder: [],
  expiring: [],
  critical: []
})

const isUrgent = (item) => {
  return item.days_remaining !== null && item.days_remaining <= 5;
};

// Chart data
const consumptionSeries = ref([])
const stockSeries = ref([])
const stockLabels = ref([])
const monthlyLabels = ref([])
const monthlySeries = ref([])
const topReagentsSeries = ref([])
const consumptionHistory = ref([])
const isLoading = ref(false)
const supplierLabels = ref([]);
const supplierSeries = ref([]);

// Chart options
const stockChartType = ref('pie')
const topReagentsLimit = ref(5)

// Metrics
const totalConsumption = ref(0)
const monthlyConsumption = ref(0)
const dailyAverage = ref(0)
const usageChange = ref(0)
const reorderAlerts = ref(0)
const criticalAlerts = ref(0)
const expiringAlerts = ref(0)
const inventoryValue = ref(0)
const reagentsValue = ref(0)
const equipmentValue = ref(0)

// Chart Options
const consumptionChartOptions = computed(() => ({
  ...chartTheme.value,
  chart: {
    ...chartTheme.value.chart,
    type: 'line',
    height: 300,
    toolbar: {
      show: true,
      tools: {
        download: true,
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true
      }
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  colors: ['#1e3a8a', '#10b981', '#f59e0b', '#dc2626'],
  stroke: {
    curve: 'smooth',
    width: 3
  },
  markers: {
    size: 5,
    hover: {
      size: 7
    }
  },
  xaxis: {
    type: 'datetime',
    labels: {
      datetimeFormatter: {
        year: 'yyyy',
        month: "MMM 'yy",
        day: 'dd MMM',
        hour: 'HH:mm'
      }
    }
  },
  yaxis: {
    title: {
      text: 'Quantity (units)'
    },
    min: 0
  },
  tooltip: {
    x: {
      format: 'dd MMM yyyy'
    }
  },
  grid: {
    ...chartTheme.value.grid,
    strokeDashArray: 5
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3,
      stops: [0, 90, 100]
    }
  }
}))


const stockChartOptions = computed(() => ({
  ...chartTheme.value,
  chart: {
    ...chartTheme.value.chart,
    type: stockChartType.value,
    height: 300,
    toolbar: { show: true }
  },
  colors: ['#1e3a8a', '#1e40af', '#1d4ed8', '#3b82f6', '#60a5fa', '#93c5fd'],
  
  // This is used by the PIE chart
  labels: stockLabels.value, 
  
  dataLabels: {
    enabled: stockChartType.value === 'pie',
    formatter: (val) => val.toFixed(1) + "%"
  },
  
  xaxis: {
    // This is used by the BAR chart
    categories: stockLabels.value, 
    labels: {
      show: stockChartType.value === 'bar', // only show x-axis labels if it's a bar
      rotate: -45
    }
  },
  
  yaxis: {
    title: {
      text: stockChartType.value === 'bar' ? 'Quantidade' : ''
    }
  },
  
  tooltip: {
    y: {
      formatter: (val) => val + " unidades"
    }
  },
  plotOptions: {
    pie: { donut: { size: '65%' } },
    bar: {
      borderRadius: 4,
      horizontal: false,
      columnWidth: '55%',
    }
  }
}))


const monthlyChartOptions = computed(() => ({
  ...chartTheme.value,
  chart: {
    ...chartTheme.value.chart,
    type: 'bar',
    height: 300,
    toolbar: { show: true }
  },
  colors: ['#1e3a8a', '#94a3b8'], // High contrast colors for years
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '55%',
      borderRadius: 4
    }
  },
  dataLabels: { enabled: false },
  xaxis: {
    // FIX: Use the month names for categories
    categories: monthlyLabels.value, 
    labels: { rotate: -45 }
  },
  yaxis: {
    title: { text: 'Quantidade (unidades)' }
  },
  tooltip: {
    y: { formatter: (val) => val + " unidades" }
  }
}))

const topReagentsChartOptions = computed(() => ({
  ...chartTheme.value,
  chart: {
    ...chartTheme.value.chart,
    type: 'bar',
    height: 300,
    toolbar: {
      show: true
    }
  },
  colors: ['#1e3a8a'],
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
    }
  },
  dataLabels: {
    enabled: true,
    formatter: function (val) {
      return val.toFixed(1) + " unidades"
    },
    offsetX: 20,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  xaxis: {
    categories: topReagentsSeries.value?.length > 0 ? topReagentsSeries.value[0].data?.map(item => item.x) : [],
    title: {
      text: 'Quantidade Consumida'
    }
  },
//   yaxis: {
//     reversed: true,
//     labels: {
//       maxWidth: 200
//     }
//   },
  yaxis: {
    reversed: false,
    labels: {
        show: true,
        maxWidth: 160, // Prevents names from taking up half the screen
        style: {
        fontSize: '12px',
        },
        // This helps if names are extremely long
        formatter: (value) => {
        if (typeof value === 'string' && value.length > 20) {
            return value.substring(0, 17) + '...';
        }
        return value;
        }
    }
    },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " unidades consumidas"
      }
    }
  }
}))

// const supplierChartOptions = computed(() => ({
//   chart: { 
//     type: 'bar', 
//     height: 350,
//     animations: { enabled: true } 
//   },
//   plotOptions: {
//     bar: {
//       horizontal: true,
//       barHeight: '70%', // Ensure this isn't 0%
//       distributed: true,
//     }
//   },
//   xaxis: {
//     categories: supplierLabels.value,
//     min: 0,
//     max: 100,
//     labels: { show: true }
//   },
//   // Ensure colors are defined, otherwise bars might be white on white
//   colors: ['#1e3a8a', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'], 
// }));

const supplierChartOptions = computed(() => ({
  ...chartTheme.value,
  chart: { 
    ...chartTheme.value.chart,
    type: 'bar', 
    height: 350 
  },
  annotations: {
    xaxis: [{
      x: 90,
      borderColor: '#ef4444', // Red for the target line
      label: {
        borderColor: '#ef4444',
        style: {
          color: '#fff',
          background: '#ef4444',
        },
        text: '90% Meta'
      }
    }]
  },
  plotOptions: {
    bar: {
      horizontal: true,
      distributed: true,
      dataLabels: { position: 'top' }
    }
  },
  dataLabels: {
    enabled: true,
    formatter: (val) => val + "%",
    offsetX: -6,
    style: { fontSize: '12px', colors: ['#fff'] }
  },
  xaxis: {
    categories: supplierLabels.value,
    max: 100,
  },
  colors: ['#1e3a8a', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
  legend: { show: false },
  grid: chartTheme.value.grid,
  tooltip: chartTheme.value.tooltip,
}));

const sparklineOptions = computed(() => ({
  ...chartTheme.value,
  chart: {
    ...chartTheme.value.chart,
    type: 'area',
    sparkline: { enabled: true },
    animations: { enabled: false } // Disable for smoother table scrolling
  },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.05,
    }
  },
  colors: ['#3b82f6'], // Matching your blue theme
  tooltip: { ...chartTheme.value.tooltip, fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
}));

// Helper functions
const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// const toggleStockChartType = () => {
//   stockChartType.value = stockChartType.value === 'pie' ? 'bar' : 'pie'
// }

const toggleStockChartType = () => {
  stockChartType.value = stockChartType.value === 'pie' ? 'bar' : 'pie'
  updateCharts() // This triggers the re-formatting of stockSeries
}

const toggleTopReagentsLimit = () => {
  topReagentsLimit.value = topReagentsLimit.value === 5 ? 10 : 5
  updateCharts()
}

const downloadChart = async (chartType) => {
  try {
    const chartData = {
      chartType,
      filters: filters.value,
      format: 'pdf'
    }
    
    const response = await axios.post(route('vap-inventory.analytics.export'), chartData, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${chartType}_chart_${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Error downloading chart:', error)
  }
}

const updateCharts = async () => {

  isLoading.value = true // Start loading  
  try {
    const response = await axios.get(route('vap-inventory.analytics.data'), {
      params: filters.value
    })

    
    const data = response.data
    const months = data.monthlyComparison || []
    const stockData = data.stockDistribution || [];

    // const performance = data.metrics.supplierPerformance || [];

    const supplierPerformance = Array.isArray(data.metrics?.supplierPerformance)
      ? data.metrics.supplierPerformance
      : []
    const sortedPerformance = [...supplierPerformance].sort((a, b) => b.on_time_rate - a.on_time_rate);

    supplierLabels.value = sortedPerformance.map(s => s.supplier);
    supplierSeries.value = [{
        name: 'Taxa de Entrega à Tempo %',
        data: sortedPerformance.map(s => Number(s.on_time_rate))
    }];

    // if (performance.length > 0) {
    //     supplierLabels.value = performance.map(s => s.supplier);
        
    //     // Ensure every value is explicitly cast to a Number
    //     const numericSeriesData = performance.map(s => {
    //         const val = parseFloat(s.on_time_rate);
    //         return isNaN(val) ? 0 : val; // Fallback to 0 if data is corrupt
    //     });

    //     supplierSeries.value = [{
    //         name: 'On-Time Delivery %',
    //         data: numericSeriesData
    //     }];
    // }


    // Update the new alert details
    alertDetails.value = data.metrics.alertDetails || { reorder: [], expiring: [], critical: [] }
    
    // Update basic metric counts
    reorderAlerts.value = data.metrics.reorderAlerts
    criticalAlerts.value = data.metrics.criticalAlerts
    expiringAlerts.value = data.metrics.expiringAlerts


    // 1. Save the labels (Category Names) for both chart types
    // stockLabels.value = data.stockDistribution?.map(item => item.category) || []
    stockLabels.value = stockData.map(item => item.category);

    if (stockChartType.value === 'pie') {
        // Pie wants: [number, number, number]
        stockSeries.value = stockData.map(item => Number(item.quantity));
    } else {
        // Bar wants: [{ name: '...', data: [number, number, number] }]
        stockSeries.value = [{
            name: 'Quantidade de Estoque',
            data: stockData.map(item => Number(item.quantity))
        }];
    }

    const stockValues = stockData.map(item => item.quantity);
    
    // Update consumption trend chart
    consumptionSeries.value = [{
      name: 'Tendência de Consumo de Reagentes',
      data: data.consumptionTrend?.map(item => ({
        x: new Date(item.date).getTime(),
        y: item.quantity
      }))
    }]
    
    // Update stock distribution chart
    // if (stockChartType.value === 'pie') {
    //   stockSeries.value = data.stockDistribution?.map(item => item.quantity)
    // } else {
    //   stockSeries.value = [{
    //     name: 'Stock Quantity',
    //     data: data.stockDistribution?.map(item => ({
    //       x: item.category,
    //       y: item.quantity
    //     }))
    //   }]
    // }

    // 2. Update stock distribution series based on type
    // if (stockChartType.value === 'pie') {
    //   // Pie expects a flat array of numbers: [10, 20, 30]
    //   stockSeries.value = data.stockDistribution?.map(item => item.quantity) || []
    // } else {
    //   // Bar expects an array of objects: [{ name: 'Stock', data: [{x: 'Cat', y: 10}] }]
    //   stockSeries.value = [{
    //     name: 'Stock Quantity',
    //     data: data.stockDistribution?.map(item => ({
    //       x: item.category,
    //       y: item.quantity
    //     })) || []
    //   }]
    // }

    if (stockChartType.value === 'pie') {
        // Pie: [10, 20, 30]
        stockSeries.value = stockValues;
    } else {
        // Bar: [{ name: 'Stock', data: [10, 20, 30] }]
        stockSeries.value = [{
            name: 'Quantidade de Estoque',
            data: stockValues
        }];
    }

    // 1. Set the X-Axis labels (Jan, Feb, Mar...)
    monthlyLabels.value = months.map(m => m.month)
    
    // Update monthly comparison chart
    // monthlySeries.value = data.monthlyComparison?.map(month => ({
    //   name: month.month,
    //   data: [month.current, month.previous]
    // }))

    monthlySeries.value = [
    {
        name: 'Ano Actual',
        data: months.map(m => m.current)
    },
    {
        name: 'Ano Anterior',
        data: months.map(m => m.previous)
    }
    ]
    
    // Update top reagents chart
    const topReagents = Array.isArray(data.topReagents)
      ? data.topReagents.slice(0, topReagentsLimit.value)
      : []
    // topReagentsSeries.value = [{
    //   name: 'Consumption',
    //   data: topReagents?.map(item => ({
    //     x: item.name,
    //     y: item.consumption
    //   }))
    // }]

    topReagentsSeries.value = [{
    name: 'Consumo',
    data: topReagents.map(item => ({
        x: item.name,
        y: item.consumption
    }))
    }]
    
    // Update consumption history
    consumptionHistory.value = data.consumptionHistory
    
    // Update metrics
    totalConsumption.value = data.metrics.totalConsumption
    monthlyConsumption.value = data.metrics.monthlyConsumption
    dailyAverage.value = data.metrics.dailyAverage
    usageChange.value = data.metrics.usageChange
    reorderAlerts.value = data.metrics.reorderAlerts
    criticalAlerts.value = data.metrics.criticalAlerts
    expiringAlerts.value = data.metrics.expiringAlerts
    inventoryValue.value = data.metrics.inventoryValue
    reagentsValue.value = data.metrics.reagentsValue
    equipmentValue.value = data.metrics.equipmentValue
    
  } catch (error) {
    console.error('Error updating charts:', error)
  } finally {
    isLoading.value = false // Stop loading
  }
  
}

const getStockPercentage = (item) => {
  // We use 2x min_level as the "Full" benchmark for the bar
  const max = item.min_level * 2;
  const percent = (item.current_stock / max) * 100;
  return Math.min(percent, 100); // Cap at 100%
};

const getProgressBarColor = (item) => {
  if (item.current_stock <= 0) return 'bg-red-600';
  if (item.current_stock <= item.min_level) return 'bg-orange-500';
  return 'bg-green-500';
};

const getStockStatusColor = (item) => {
  if (item.current_stock <= 0) return 'text-red-700 bg-red-100';
  if (item.current_stock <= item.min_level) return 'text-orange-700 bg-orange-100';
  return 'text-green-700 bg-green-100';
};

const getStockStatusLabel = (item) => {
  if (item.current_stock <= 0) return 'SEM ESTOQUE';
  if (item.current_stock <= item.min_level) return 'BAIXO';
  return 'SAUDÁVEL';
};

onMounted(() => {
  syncDarkMode()
  themeObserver = new MutationObserver(syncDarkMode)
  themeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  })

  // Set default date range
  const endDate = new Date()
  const startDate = new Date()
  startDate.setDate(startDate.getDate() - 30)
  
  filters.value.startDate = startDate.toISOString().split('T')[0]
  filters.value.endDate = endDate.toISOString().split('T')[0]
  
  updateCharts()
})

onUnmounted(() => {
  themeObserver?.disconnect()
})

const generateDrafts = () => {
  if (confirm('Criar rascunhos de reabastecimento para todos os itens sem estoque?')) {
    router.post(route('vap-inventory.analytics.restock'), {}, {
      onSuccess: () => {
        // Optionally redirect to the orders index
        router.visit(route('iorders.index'));
      }
    });
  }
};

</script>
