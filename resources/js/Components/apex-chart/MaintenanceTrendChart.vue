<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <ChartLineIcon class="h-5 w-5 text-blue-900" />
        {{ title }}
      </h3>
      <slot name="controls"></slot>
    </div>
    <ChartWrapper
      v-if="series.length > 0"
      type="line"
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
import { ChartLineIcon } from '@heroicons/vue/24/outline'
import Spinner from '@/Components/Spinner.vue'

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  },
  title: {
    type: String,
    default: 'Maintenance Trend'
  },
  showLegend: {
    type: Boolean,
    default: true
  }
})

const series = computed(() => {
  if (!props.data || props.data.length === 0) return []
  
  return [
    { 
      name: 'Created', 
      data: props.data.map(item => item.created || 0),
      type: 'line'
    },
    { 
      name: 'Executed', 
      data: props.data.map(item => item.executed || 0),
      type: 'line'
    },
    { 
      name: 'Overdue', 
      data: props.data.map(item => item.overdue || 0),
      type: 'line'
    }
  ]
})

const options = computed(() => ({
  chart: {
    type: 'line',
    height: 300,
    toolbar: {
      show: false
    },
    zoom: {
      enabled: false
    }
  },
  colors: ['#3b82f6', '#10b981', '#dc2626'],
  stroke: {
    width: 3,
    curve: 'smooth',
    lineCap: 'round'
  },
  markers: {
    size: 5,
    strokeWidth: 0,
    hover: {
      size: 7
    }
  },
  xaxis: {
    categories: props.data.map(item => item.month),
    labels: {
      style: {
        fontSize: '11px',
        fontWeight: 400
      },
      rotate: -45
    },
    tooltip: {
      enabled: false
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
    min: 0,
    forceNiceScale: true,
    labels: {
      formatter: function(val) {
        return Math.floor(val)
      }
    }
  },
  legend: props.showLegend ? {
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '12px',
    markers: {
      radius: 12,
      offsetX: -3,
      offsetY: 1
    },
    itemMargin: {
      horizontal: 10,
      vertical: 5
    }
  } : { show: false },
  tooltip: {
    shared: true,
    intersect: false,
    x: {
      show: true,
      formatter: function(val) {
        return `Period: ${val}`
      }
    },
    custom: function({ series, seriesIndex, dataPointIndex, w }) {
      return `<div class="bg-white p-2 rounded-lg shadow-lg border border-gray-200">
        <div class="font-semibold text-gray-900">${w.globals.categoryLabels[dataPointIndex]}</div>
        ${series.map((s, i) => `
          <div class="flex items-center gap-2 mt-1">
            <div class="h-3 w-3 rounded-full" style="background-color: ${w.config.colors[i]}"></div>
            <span class="text-sm">${w.config.series[i].name}: ${s[dataPointIndex]}</span>
          </div>
        `).join('')}
      </div>`
    }
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
    padding: {
      top: 20,
      right: 20,
      bottom: 0,
      left: 20
    }
  }
}))
</script>