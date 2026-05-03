<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <ChartBarIcon class="h-5 w-5 text-blue-900" />
        Task Status Distribution
      </h3>
      <slot name="controls"></slot>
    </div>
    <ChartWrapper
      v-if="series.length > 0"
      type="bar"
      :height="300"
      :series="series"
      :options="options"
    />
    <div v-else class="h-64 flex items-center justify-center text-gray-400">
      <Spinner class="h-8 w-8 text-blue-900" />
      <span class="ml-2">Loading chart data...</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ChartWrapper from './ChartWrapper.vue'
import { ChartBarIcon } from '@heroicons/vue/24/outline'
import Spinner from '@/Components/Spinner.vue'

const props = defineProps({
  data: {
    type: Object,
    default: () => ({
      overdue: 0,
      due_soon: 0,
      scheduled: 0,
      executed: 0
    })
  },
  title: {
    type: String,
    default: 'Task Status Distribution'
  },
  showAnnotations: {
    type: Boolean,
    default: true
  }
})

const series = computed(() => [{
  name: 'Tasks',
  data: [
    props.data.overdue || 0,
    props.data.due_soon || 0,
    props.data.scheduled || 0,
    props.data.executed || 0,
  ]
}])

const options = computed(() => ({
  chart: {
    type: 'bar',
    height: 300,
    toolbar: {
      show: false
    }
  },
  plotOptions: {
    bar: {
      borderRadius: 4,
      columnWidth: '60%',
      distributed: true,
      dataLabels: {
        position: 'top'
      }
    }
  },
  colors: ['#dc2626', '#f59e0b', '#3b82f6', '#10b981'],
  dataLabels: {
    enabled: true,
    formatter: function(val) {
      return val > 0 ? val : ''
    },
    offsetY: -20,
    style: {
      fontSize: '12px',
      fontWeight: 'bold',
      colors: ['#111827']
    }
  },
  xaxis: {
    categories: ['Overdue', 'Due Soon', 'Scheduled', 'Executed'],
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 600
      }
    },
    axisTicks: {
      show: false
    },
    axisBorder: {
      show: false
    }
  },
  yaxis: {
    title: {
      text: 'Number of Tasks',
      style: {
        fontSize: '12px',
        fontWeight: 400
      }
    },
    labels: {
      formatter: function(val) {
        return Math.floor(val)
      }
    },
    min: 0
  },
  tooltip: {
    y: {
      formatter: function(val) {
        return val + ' tasks'
      }
    }
  },
  annotations: props.showAnnotations ? {
    xaxis: [{
      x: 'Overdue',
      borderColor: '#dc2626',
      label: {
        borderColor: '#dc2626',
        style: {
          color: '#fff',
          background: '#dc2626',
          fontSize: '10px',
          fontWeight: 'bold'
        },
        text: 'Requires Attention'
      }
    }]
  } : {},
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
    padding: {
      top: -20,
      right: 20,
      bottom: 0,
      left: 20
    }
  }
}))
</script>