<template>
  <div class="space-y-6">
    <!-- REAL-TIME METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- ACTIVE CONSUMPTION -->
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Consumo Ativo Hoje</p>
            <p class="text-2xl font-bold mt-1">{{ formatNumber(todayConsumption) }} unidades</p>
          </div>
          <div class="relative">
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="h-12 w-12 rounded-full bg-white/20 animate-pulse"></div>
            </div>
            <BeakerIcon class="h-8 w-8 relative z-10" />
          </div>
        </div>
        <div class="mt-4">
          <div class="h-2 bg-white/20 rounded-full overflow-hidden">
            <div 
              class="h-full bg-white rounded-full transition-all duration-500"
              :style="{ width: dailyProgress + '%' }"
            ></div>
          </div>
          <div class="flex justify-between text-xs mt-2 opacity-80">
            <span>Progresso</span>
            <span>{{ dailyProgress.toFixed(0) }}%</span>
          </div>
        </div>
      </div>

      <!-- LOW STOCK ALERTS -->
      <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Itens com Baixo Nível de Estoque</p>
            <p class="text-2xl font-bold mt-1">{{ lowStockCount }}</p>
          </div>
          <BellAlertIcon class="h-8 w-8" />
        </div>
        <div class="mt-4 space-y-1">
          <div 
            v-for="alert in lowStockAlerts"
            :key="alert.id"
            class="flex items-center justify-between text-xs hover:bg-white/10 px-2 py-1 rounded transition-colors"
          >
            <span class="truncate">{{ alert.item_name }}</span>
            <span class="font-semibold">{{ alert.current_stock }}</span>
          </div>
        </div>
      </div>

      <!-- EXPIRING SOON -->
      <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencendo em Breve</p>
            <p class="text-2xl font-bold mt-1">{{ expiringSoonCount }}</p>
          </div>
          <ClockIcon class="h-8 w-8" />
        </div>
        <div class="mt-4 space-y-1">
          <div 
            v-for="item in expiringItems"
            :key="item.id"
            class="flex items-center justify-between text-xs hover:bg-white/10 px-2 py-1 rounded transition-colors"
          >
            <span class="truncate">{{ item.name }}</span>
            <span class="font-semibold">{{ item.days_to_expiry }} dias</span>
          </div>
        </div>
      </div>

      <!-- CALIBRATION DUE -->
      <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Calibração Pendente</p>
            <p class="text-2xl font-bold mt-1">{{ calibrationDueCount }}</p>
          </div>
          <WrenchScrewdriverIcon class="h-8 w-8" />
        </div>
        <div class="mt-4 space-y-1">
          <div 
            v-for="item in calibrationDueItems"
            :key="item.id"
            class="flex items-center justify-between text-xs hover:bg-white/10 px-2 py-1 rounded transition-colors"
          >
            <span class="truncate">{{ item.name }}</span>
            <span class="font-semibold">{{ item.days_to_calibration }} dias</span>
          </div>
        </div>
      </div>
    </div>

    <!-- REAL-TIME CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- REAGENT CONSUMPTION MONITOR -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Monitoramento de Consumo em Tempo Real</h3>
            <p class="text-sm text-gray-500">Últimas 24 horas</p>
            </div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 text-xs font-medium">
              <div class="h-2 w-2 rounded-full bg-blue-900"></div>
              Consumo
            </span>
          </div>
        </div>
        <apexchart
          type="area"
          height="250"
          :options="realtimeChartOptions"
          :series="realtimeSeries"
        />
      </div>

      <!-- STOCK LEVEL GAUGE -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Monitor de Nível de Estoque</h3>
            <p class="text-sm text-gray-500">Saúde Geral do Estoque</p>
          </div>
          <div class="flex items-center gap-2">
            <span 
              :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                stockHealthClass
              ]"
            >
              {{ stockHealthLabel }}
            </span>
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div 
            v-for="gauge in stockGauges"
            :key="gauge.label"
            class="text-center"
          >
            <apexchart
              type="radialBar"
              height="150"
              :options="gauge.options"
              :series="gauge.series"
            />
            <p class="text-sm font-medium text-gray-900 mt-2">{{ gauge.label }}</p>
            <p class="text-xs text-gray-500">{{ gauge.value }}  itens</p>
          </div>
        </div>
      </div>
    </div>

    <!-- RECENT ACTIVITY -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Actividade Recente</h3>
          <button
            @click="refreshActivity"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
            :disabled="refreshing"
          >
            <ArrowPathIcon class="h-3 w-3" />
            {{ refreshing ? 'Atualizando...' : 'Atualizar' }}
          </button>
        </div>
      </div>
      <div class="divide-y divide-gray-200">
        <div
          v-for="activity in recentActivity"
          :key="activity.id"
          class="px-6 py-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-start gap-3">
            <div :class="[
              'flex h-8 w-8 items-center justify-center rounded-full',
              getActivityColor(activity.type)
            ]">
              <component
                :is="getActivityIcon(activity.type)"
                class="h-4 w-4 text-white"
              />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900">
                  {{ activity.description }}
                </p>
                <span class="text-xs text-gray-500">
                  {{ formatTimeAgo(activity.timestamp) }}
                </span>
              </div>
              <p class="text-sm text-gray-500 mt-1">
                {{ activity.details }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <div v-if="recentActivity.length === 0" class="p-12 text-center">
        <BellAlertIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">Nenhuma atividade recente</h3>
        <p class="mt-2 text-sm text-gray-500">A atividade aparecerá aqui assim que acontecer</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  BeakerIcon,
  BellAlertIcon,
  ClockIcon,
  WrenchScrewdriverIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  PlusIcon,
  MinusIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { getEcho } from '@/lib/echo'

// Realtime data
const todayConsumption = ref(0)
const dailyProgress = ref(0)
const lowStockCount = ref(0)
const lowStockAlerts = ref([])
const expiringSoonCount = ref(0)
const expiringItems = ref([])
const calibrationDueCount = ref(0)
const calibrationDueItems = ref([])
const recentActivity = ref([])
const refreshing = ref(false)

// Chart data
const realtimeSeries = ref([{
  name: 'Consumo',
  data: []
}])

const stockGauges = ref([
  {
    label: 'Reagentes',
    value: 0,
    series: [0],
    options: {
      chart: {
        type: 'radialBar',
        height: 150,
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '50%',
          },
          dataLabels: {
            name: {
              fontSize: '16px',
            },
            value: {
              fontSize: '20px',
              fontWeight: 'bold',
            },
          },
        },
      },
      colors: ['#1e3a8a'],
      labels: ['Nível de Estoque'],
    },
  },
  {
    label: 'Equipmento',
    value: 0,
    series: [0],
    options: {
      chart: {
        type: 'radialBar',
        height: 150,
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '50%',
          },
        },
      },
      colors: ['#10b981'],
      labels: ['Nível de Estoque'],
    },
  },
  {
    label: 'Consumíveis',
    value: 0,
    series: [0],
    options: {
      chart: {
        type: 'radialBar',
        height: 150,
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '50%',
          },
        },
      },
      colors: ['#f59e0b'],
      labels: ['Nível de Estoque'],
    },
  },
])

// Chart options
const realtimeChartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 250,
    toolbar: {
      show: false,
    },
    animations: {
      enabled: true,
      easing: 'linear',
      dynamicAnimation: {
        speed: 1000,
      },
    },
  },
  colors: ['#1e3a8a'],
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3,
    },
  },
  xaxis: {
    type: 'datetime',
    labels: {
      datetimeFormatter: {
        hour: 'HH:mm',
      },
    },
  },
  yaxis: {
    min: 0,
    title: {
      text: 'Unidades',
    },
  },
  grid: {
    borderColor: '#f1f5f9',
  },
  tooltip: {
    x: {
      format: 'HH:mm',
    },
  },
}))

const stockHealthClass = computed(() => {
  const health = (todayConsumption.value / 100) // Simplified calculation
  if (health > 80) return 'bg-red-100 text-red-800'
  if (health > 60) return 'bg-orange-100 text-orange-800'
  if (health > 40) return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
})

const stockHealthLabel = computed(() => {
  const health = (todayConsumption.value / 100)
  if (health > 80) return 'Alto Consumo'
  if (health > 60) return 'Médio Consumo'
  if (health > 40) return 'Consumo Normal'
  return 'Baixo Consumo'
})

// Helper functions
const formatNumber = (num) => {
  return new Intl.NumberFormat('en-US').format(num.toFixed(1))
}

const formatTimeAgo = (timestamp) => {
  const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000)
  
  if (seconds < 60) return 'agora'
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m atrás`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h atrás`
  return `${Math.floor(seconds / 86400)}d atrás`
}

const getActivityColor = (type) => {
  const colors = {
    consumption: 'bg-blue-900',
    stock_in: 'bg-green-900',
    stock_out: 'bg-red-900',
    transfer: 'bg-purple-900',
    calibration: 'bg-orange-900',
    expiry: 'bg-red-900',
  }
  return colors[type] || 'bg-gray-900'
}

const getActivityIcon = (type) => {
  const icons = {
    consumption: BeakerIcon,
    stock_in: ArrowDownTrayIcon,
    stock_out: ArrowUpTrayIcon,
    transfer: ArrowPathIcon,
    calibration: WrenchScrewdriverIcon,
    expiry: ExclamationTriangleIcon,
  }
  return icons[type] || BellAlertIcon
}

const refreshActivity = async () => {
  refreshing.value = true
  await fetchRealtimeData()
  refreshing.value = false
}

const fetchRealtimeData = async () => {
  try {
    const response = await axios.get(route('vap-inventory.analytics.realtime'))
    const data = response.data
    
    // Update metrics
    todayConsumption.value = data.todayConsumption
    dailyProgress.value = data.dailyProgress
    lowStockCount.value = data.lowStockCount
    lowStockAlerts.value = data.lowStockAlerts.slice(0, 3)
    expiringSoonCount.value = data.expiringSoonCount
    expiringItems.value = data.expiringItems.slice(0, 3)
    calibrationDueCount.value = data.calibrationDueCount
    calibrationDueItems.value = data.calibrationDueItems.slice(0, 3)
    
    // Update realtime chart
    realtimeSeries.value = [{
      name: 'Consumo',
      data: data.realtimeConsumption?.map(point => ({
        x: new Date(point.timestamp).getTime(),
        y: point.value,
      })),
    }]
    
    // Update stock gauges
    stockGauges.value[0].value = data.reagentStock
    stockGauges.value[0].series = [data.reagentStockLevel]
    stockGauges.value[1].value = data.equipmentStock
    stockGauges.value[1].series = [data.equipmentStockLevel]
    stockGauges.value[2].value = data.consumablesStock
    stockGauges.value[2].series = [data.consumablesStockLevel]
    
    // Update recent activity
    recentActivity.value = data.recentActivity
    
  } catch (error) {
    console.error('Error fetching realtime data:', error)
  }
}

// Setup realtime updates
let echo = null
let refreshInterval = null

const setupRealtime = () => {
  echo = getEcho()

  if (!echo) {
    return
  }
  
  // Listen for inventory events
  echo.channel('inventory')
    .listen('StockUpdated', (event) => {
      fetchRealtimeData()
      // Add to recent activity
      recentActivity.value.unshift({
        id: Date.now(),
        type: 'stock_in',
        description: 'Estoque atualizado',
        details: `${event.item} estoque alterado para ${event.quantity}`,
        timestamp: new Date().toISOString(),
      })
      
      // Keep only last 10 activities
      if (recentActivity.value.length > 10) {
        recentActivity.value = recentActivity.value.slice(0, 10)
      }
    })
    .listen('ReagentConsumed', (event) => {
      fetchRealtimeData()
      // Add to recent activity
      recentActivity.value.unshift({
        id: Date.now(),
        type: 'consumption',
        description: 'Reagente consumido',
        details: `${event.reagent} - ${event.quantity} unidades`,
        timestamp: new Date().toISOString(),
      })
      
      // Keep only last 10 activities
      if (recentActivity.value.length > 10) {
        recentActivity.value = recentActivity.value.slice(0, 10)
      }
    });
  
  // Setup periodic refresh
  refreshInterval = setInterval(fetchRealtimeData, 30000) // Every 30 seconds
}

onMounted(() => {
  fetchRealtimeData()
  setupRealtime()
})

onUnmounted(() => {
  if (echo) {
    echo.disconnect()
  }
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>
