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
          <SimpleSelect
            v-model="selectedDateRange"
            :options="dateRangeOptions"
            label=""
            placeholder="Seleccione um intervalo"
          />
        </div>

        <!-- CATEGORY FILTER -->
        <div class="space-y-2">
          <label :class="labelClass">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <SimpleSelect
            v-model="selectedCategory"
            :options="categoryOptions"
            label=""
            placeholder="Todas"
          />
        </div>

        <!-- WAREHOUSE FILTER -->
        <div class="space-y-2">
          <label :class="labelClass">
            <BuildingLibraryIcon class="h-4 w-4 inline mr-1" />
            Armazém
          </label>
          <SimpleSelect
            v-model="selectedWarehouse"
            :options="warehouseOptions"
            label=""
            placeholder="Todos os Armazéns"
          />
        </div>

        <!-- CUSTOM DATE RANGE -->
        <div v-if="filters.dateRange === 'custom'" class="space-y-2">
          <label :class="labelClass">
            Período Personalizado
          </label>
          <DatePickerEnhanced
            :model-value="{ start: filters.startDate, end: filters.endDate }"
            range
            @update:model-value="updateCustomDateRange"
          />
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
              <ArrowTrendingUpIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
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
        <div v-else class="ds-empty-state min-h-[300px]">
          Nenhuma informação de consumo disponível
        </div>
      </div>

      <!-- STOCK LEVELS BY CATEGORY -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ChartPieIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
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
        <div v-else class="ds-empty-state min-h-[300px]">
          Sem Dados Disponíveis
        </div>
      </div>

      <div :class="panelClass">

        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ArchiveBoxIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
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
            <div v-else class="ds-empty-state min-h-[350px]">
                <ChartBarIcon class="h-12 w-12 mb-2 opacity-20" />
                <p class="text-sm">Nenhuma entrega encontrada para os filtros selecionados.</p>
            </div>
        </div>

      <!-- MONTHLY CONSUMPTION COMPARISON -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <ChartBarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
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
        <div v-else class="ds-empty-state min-h-[300px]">
          Sem Dados Disponíveis
        </div>
      </div>

      <!-- TOP CONSUMED REAGENTS -->
      <div :class="panelClass">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 :class="titleClass">
              <TrophyIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
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
        <div v-else class="ds-empty-state min-h-[300px]">
          Sem Dados Disponíveis
        </div>
      </div>
    </div>

    <!-- ADDITIONAL METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- TOTAL CONSUMPTION -->
      <div class="ds-card relative overflow-hidden border-t-4 border-t-[rgb(var(--primary-600-rgb))] p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="ds-kicker">Consumo Total</p>
            <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">{{ formatNumber(totalConsumption) }} unidades</p>
          </div>
          <div class="rounded-2xl bg-[rgb(var(--primary-50-rgb))] p-3 text-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:text-[rgb(var(--accent-200-rgb))]">
            <BeakerIcon class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-5 space-y-2 text-sm text-[var(--ds-text-muted)]">
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

      <div class="ds-card relative overflow-hidden border-t-4 border-t-emerald-500 p-5">
        <div class="flex items-center justify-between">
            <div>
            <p class="ds-kicker">Uso Médio Diário</p>
            <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">{{ formatNumber(dailyAverage) }}</p>
            </div>
            <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
              <ArrowTrendingUpIcon class="h-6 w-6" />
            </div>
        </div>
        <div class="mt-5 text-sm text-[var(--ds-text-muted)]">
            <span :class="['rounded-full px-2 py-1 font-bold', usageChange >= 0 ? 'bg-red-500/10 text-red-700 dark:text-red-300' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300']">
            {{ usageChange >= 0 ? '↑' : '↓' }} {{ Math.abs(usageChange) }}%
            </span>
            <span class="ml-2">vs Período Anterior</span>
        </div>
        </div>

      <div :class="[
        'ds-card relative group overflow-visible border-t-4 p-5 transition-all duration-300',
        criticalAlerts > 0 ? 'border-t-red-500' : 'border-t-amber-500'
        ]">
        <div class="flex items-center justify-between">
            <div>
            <p class="ds-kicker">Alertas de Inventário</p>
            <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">{{ reorderAlerts + expiringAlerts + criticalAlerts }}</p>
            </div>
            <div class="rounded-2xl bg-amber-50 p-3 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
              <BellAlertIcon class="h-6 w-6" />
            </div>
        </div>

        <div class="mt-5 space-y-2 text-sm text-[var(--ds-text-muted)]">
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

        <div class="ds-floating-panel invisible absolute left-0 top-full z-50 mt-3 w-full p-4 opacity-0 transition-all group-hover:visible group-hover:opacity-100 sm:left-auto sm:right-0 sm:w-80">
            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-[var(--ds-text-soft)]">Atenção de Prioridade</h4>
            
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
            <p class="mb-1 text-[10px] font-bold uppercase text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">Expirando em Breve</p>
            <ul class="text-xs space-y-1">
                <li v-for="item in alertDetails.expiring" :key="item" class="truncate">• {{ item }}</li>
            </ul>
            </div>
            
            <p v-if="reorderAlerts + expiringAlerts + criticalAlerts === 0" class="text-xs italic text-[var(--ds-text-muted)]">
            Todos os níveis de estoque estão saudáveis.
            </p>

            <button 
                @click="generateDrafts"
                class="ds-button ds-button-primary mt-3 w-full text-xs"
                >
                <ShoppingCartIcon class="h-4 w-4" />
                Gerar Rascunhos de Reabastecimento
            </button>
        </div>

        </div>

      <!-- INVENTORY VALUE -->
      <div class="ds-card relative overflow-hidden border-t-4 border-t-[rgb(var(--accent-400-rgb))] p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="ds-kicker">Valor de Inventário</p>
            <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">${{ formatNumber(inventoryValue) }}</p>
          </div>
          <div class="rounded-2xl bg-[rgb(var(--accent-100-rgb)/0.72)] p-3 text-[rgb(var(--accent-700-rgb))] dark:bg-[rgb(var(--accent-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
            <CurrencyDollarIcon class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-5 space-y-2 text-sm text-[var(--ds-text-muted)]">
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
    <div class="ds-table-shell">
      <div class="ds-table-summary">
        <h3 class="flex items-center gap-2 text-lg font-extrabold text-[var(--ds-text)]">
          <TableCellsIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
          Histórico de Consumo Detalhado
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="ds-table-head">
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
          <tbody class="ds-table-body">
            <tr
              v-for="consumption in consumptionHistory"
              :key="consumption.id"
              class="ds-table-row"
              :class="{'bg-red-50/50 dark:bg-red-950/20': isUrgent(consumption)}"
            >
              <td :class="tableCellClass">
                {{ formatDate(consumption.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-[var(--ds-text)]">{{ consumption.reagent_name }}</div>
                <div class="text-sm text-[var(--ds-text-muted)]">{{ consumption.item?.code || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="ds-chip">
                  {{ consumption.quantity_used }} unidades
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-[var(--ds-text-muted)]">
                {{ consumption.used_by || 'N/A' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-[var(--ds-text-muted)]">
                {{ consumption.warehouse?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 text-sm text-[var(--ds-text-muted)]">
                {{ consumption.remarks || '-' }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                <div class="w-full max-w-[120px]">
                    <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-medium text-[var(--ds-text-muted)]">
                        {{ consumption.current_stock }} / {{ consumption.min_level * 2 }} 
                    </span>
                    <span :class="[
                        'text-[10px] font-bold px-1 rounded',
                        getStockStatusColor(consumption)
                    ]">
                        {{ getStockStatusLabel(consumption) }}
                    </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-[var(--ds-panel-muted)]">
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
                    consumption.days_remaining < 7 ? 'text-red-600 dark:text-red-300' : 'text-[var(--ds-text)]'
                    ]">
                    {{ consumption.days_remaining }} dias restantes
                    </span>
                    <span class="text-[10px] text-[var(--ds-text-muted)]">
                    Est: {{ formatDate(consumption.predicted_out_date) }}
                    </span>
                </div>
                <div v-else class="text-xs italic text-[var(--ds-text-soft)]">
                    Nenhuma utilização recente
                </div>
                </td>

            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="consumptionHistory?.length === 0" class="ds-empty-state m-5 min-h-52">
        <BeakerIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
        <h3 class="mt-4 text-sm font-bold text-[var(--ds-text)]">Nenhum registro de consumo encontrado</h3>
        <p class="mt-2 text-sm text-[var(--ds-text-muted)]">Comece a usar reagentes para ver o histórico de consumo</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import DatePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import SimpleSelect from '@/Components/simple-select.vue'
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

const panelClass = 'ds-panel p-5 sm:p-6'
const labelClass = 'ds-field-label flex items-center gap-1.5'
const titleClass = 'flex items-center gap-2 text-base font-extrabold text-[var(--ds-text)]'
const mutedClass = 'mt-1 text-sm text-[var(--ds-text-muted)]'
const smallButtonClass = 'ds-button ds-button-secondary min-h-0 px-3 py-2 text-xs'
const tableHeadClass = 'ds-table-heading px-6 py-3 text-left'
const tableCellClass = 'ds-table-cell whitespace-nowrap px-6 py-4'

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
    foreColor: isDarkMode.value ? '#d7e2dd' : '#31413b',
  },
  grid: {
    borderColor: isDarkMode.value ? '#25443c' : '#ded3bf',
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

const dateRangeOptions = [
  { value: '7d', label: 'Últimos 7 Dias' },
  { value: '30d', label: 'Últimos 30 Dias' },
  { value: '90d', label: 'Últimos 90 Dias' },
  { value: '1y', label: 'Último Ano' },
  { value: 'custom', label: 'Intervalo Personalizado' },
]

const categoryOptions = computed(() => [
  { value: '', label: 'Todas' },
  ...(props.categories ?? []).map(category => ({
    value: category.id,
    label: category.name,
  })),
])

const warehouseOptions = computed(() => [
  { value: '', label: 'Todos os Armazéns' },
  ...(props.warehouses ?? []).map(warehouse => ({
    value: warehouse.id,
    label: warehouse.name,
  })),
])

const selectedDateRange = computed({
  get: () => dateRangeOptions.find(option => option.value === filters.value.dateRange) ?? dateRangeOptions[1],
  set: option => {
    filters.value.dateRange = option?.value ?? '30d'
    updateCharts()
  },
})

const selectedCategory = computed({
  get: () => categoryOptions.value.find(option => String(option.value) === String(filters.value.categoryId)) ?? categoryOptions.value[0],
  set: option => {
    filters.value.categoryId = option?.value ?? ''
    updateCharts()
  },
})

const selectedWarehouse = computed({
  get: () => warehouseOptions.value.find(option => String(option.value) === String(filters.value.warehouseId)) ?? warehouseOptions.value[0],
  set: option => {
    filters.value.warehouseId = option?.value ?? ''
    updateCharts()
  },
})

const updateCustomDateRange = value => {
  filters.value.startDate = value?.start ?? ''
  filters.value.endDate = value?.end ?? ''

  if (filters.value.startDate && filters.value.endDate) {
    updateCharts()
  }
}

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
  colors: ['#143d37', '#1f7a68', '#d6a43a', '#b54747'],
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
  colors: ['#143d37', '#1f7a68', '#4f9688', '#7ab8aa', '#d6a43a', '#ecd89e'],
  
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
  colors: ['#143d37', '#d6a43a'],
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
  colors: ['#1f7a68'],
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
//   colors: ['#143d37', '#1f7a68', '#10b981', '#d6a43a', '#ef4444'],
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
  colors: ['#143d37', '#1f7a68', '#10b981', '#d6a43a', '#ef4444'],
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
  colors: ['#1f7a68'],
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

    
    const data = response.data ?? {}
    const metrics = data.metrics ?? {}
    const months = Array.isArray(data.monthlyComparison) ? data.monthlyComparison : []
    const stockData = Array.isArray(data.stockDistribution) ? data.stockDistribution : []

    // const performance = data.metrics.supplierPerformance || [];

    const supplierPerformance = Array.isArray(metrics.supplierPerformance)
      ? metrics.supplierPerformance
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
    alertDetails.value = {
      reorder: Array.isArray(metrics.alertDetails?.reorder) ? metrics.alertDetails.reorder : [],
      expiring: Array.isArray(metrics.alertDetails?.expiring) ? metrics.alertDetails.expiring : [],
      critical: Array.isArray(metrics.alertDetails?.critical) ? metrics.alertDetails.critical : [],
    }
    
    // Update basic metric counts
    reorderAlerts.value = Number(metrics.reorderAlerts ?? 0)
    criticalAlerts.value = Number(metrics.criticalAlerts ?? 0)
    expiringAlerts.value = Number(metrics.expiringAlerts ?? 0)


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
    consumptionHistory.value = Array.isArray(data.consumptionHistory) ? data.consumptionHistory : []
    
    // Update metrics
    totalConsumption.value = Number(metrics.totalConsumption ?? 0)
    monthlyConsumption.value = Number(metrics.monthlyConsumption ?? 0)
    dailyAverage.value = Number(metrics.dailyAverage ?? 0)
    usageChange.value = Number(metrics.usageChange ?? 0)
    reorderAlerts.value = Number(metrics.reorderAlerts ?? 0)
    criticalAlerts.value = Number(metrics.criticalAlerts ?? 0)
    expiringAlerts.value = Number(metrics.expiringAlerts ?? 0)
    inventoryValue.value = Number(metrics.inventoryValue ?? 0)
    reagentsValue.value = Number(metrics.reagentsValue ?? 0)
    equipmentValue.value = Number(metrics.equipmentValue ?? 0)
    
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
