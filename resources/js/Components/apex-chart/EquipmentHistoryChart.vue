<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <WrenchScrewdriverIcon class="h-5 w-5 text-blue-900" />
        Equipment Maintenance History
      </h3>
      <div class="flex items-center gap-3">
        <select
          v-model="timeRange"
          @change="onTimeRangeChange"
          class="text-sm rounded-lg border border-gray-300 px-3 py-1.5 focus:border-blue-900 focus:ring-blue-900"
        >
          <option value="6months">Last 6 Months</option>
          <option value="1year">Last Year</option>
          <option value="2years">Last 2 Years</option>
          <option value="all">All Time</option>
        </select>
      </div>
    </div>
    
    <div v-if="loading" class="h-64 flex items-center justify-center">
      <Spinner class="h-8 w-8 text-blue-900" />
      <span class="ml-2 text-gray-500">Loading history...</span>
    </div>
    
    <div v-else-if="hasData">
      <ChartWrapper
        type="line"
        :height="300"
        :series="series"
        :options="options"
      />
      
      <!-- Summary Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-900">{{ stats.totalTasks }}</div>
          <div class="text-sm text-gray-600">Total Tasks</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-900">{{ formatCurrency(stats.avgCost) }}</div>
          <div class="text-sm text-gray-600">Avg. Cost per Task</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-orange-900">{{ stats.completionRate }}%</div>
          <div class="text-sm text-gray-600">Completion Rate</div>
        </div>
      </div>
    </div>
    
    <div v-else class="h-64 flex flex-col items-center justify-center text-gray-400">
      <WrenchScrewdriverIcon class="h-12 w-12 mb-3" />
      <p>No maintenance history available</p>
      <p class="text-sm mt-1">for the selected time period</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import ChartWrapper from './ChartWrapper.vue'
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'
import Spinner from '@/Components/Spinner.vue'

const props = defineProps({
  equipmentId: {
    type: [String, Number],
    required: true
  },
  initialData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['timeRangeChange'])

const timeRange = ref('6months')
const loading = ref(false)
const historyData = ref(props.initialData)
const stats = ref({
  totalTasks: 0,
  avgCost: 0,
  completionRate: 0
})

const hasData = computed(() => {
  return historyData.value?.tasks?.length > 0
})

const series = computed(() => {
  if (!hasData.value) return []
  
  const tasks = historyData.value.tasks
  const costs = tasks.map(task => task.cost || 0)
  const statuses = tasks.map(task => task.is_executed ? 1 : 0)
  
  return [
    {
      name: 'Cost (AOA)',
      type: 'column',
      data: costs
    },
    {
      name: 'Status (Completed)',
      type: 'line',
      data: statuses
    }
  ]
})

const options = computed(() => ({
  chart: {
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
    }
  },
  colors: ['#3b82f6', '#10b981'],
  stroke: {
    width: [0, 3],
    curve: 'smooth'
  },
  plotOptions: {
    bar: {
      borderRadius: 4,
      columnWidth: '60%'
    }
  },
  xaxis: {
    categories: historyData.value?.tasks?.map(task => 
      new Date(task.due_date).toLocaleDateString('en-GB', { month: 'short', year: '2-digit' })
    ) || [],
    labels: {
      rotate: -45,
      style: {
        fontSize: '11px'
      }
    }
  },
  yaxis: [
    {
      title: {
        text: 'Cost (AOA)',
        style: {
          fontSize: '12px',
          fontWeight: 400
        }
      },
      labels: {
        formatter: function(val) {
          return 'AOA' + val.toFixed(0)
        }
      }
    },
    {
      opposite: true,
      title: {
        text: 'Status',
        style: {
          fontSize: '12px',
          fontWeight: 400
        }
      },
      min: 0,
      max: 1,
      labels: {
        formatter: function(val) {
          return val === 1 ? 'Completed' : 'Pending'
        }
      }
    }
  ],
  tooltip: {
    shared: true,
    intersect: false,
    y: [
      {
        formatter: function(val) {
          return 'AOA' + val.toFixed(2)
        }
      },
      {
        formatter: function(val) {
          return val === 1 ? 'Completed' : 'Pending'
        }
      }
    ]
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left'
  }
}))

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount)
}

const onTimeRangeChange = () => {
  emit('timeRangeChange', timeRange.value)
}

const loadHistoryData = async () => {
  loading.value = true
  try {
    const response = await axios.get(route('vap-maintenance.equipment.history', props.equipmentId), {
      params: { range: timeRange.value }
    })
    historyData.value = response.data
    updateStats()
  } catch (error) {
    console.error('Error loading equipment history:', error)
  } finally {
    loading.value = false
  }
}

const updateStats = () => {
  if (!hasData.value) return
  
  const tasks = historyData.value.tasks
  const executedTasks = tasks.filter(task => task.is_executed).length
  
  stats.value = {
    totalTasks: tasks.length,
    avgCost: tasks.reduce((sum, task) => sum + (task.cost || 0), 0) / tasks.length,
    completionRate: tasks.length > 0 ? Math.round((executedTasks / tasks.length) * 100) : 0
  }
}

// Watch for equipmentId changes
watch(() => props.equipmentId, () => {
  loadHistoryData()
})

// Watch for initialData prop changes
watch(() => props.initialData, (newData) => {
  if (newData) {
    historyData.value = newData
    updateStats()
  }
})

onMounted(() => {
  if (!props.initialData) {
    loadHistoryData()
  } else {
    updateStats()
  }
})
</script>