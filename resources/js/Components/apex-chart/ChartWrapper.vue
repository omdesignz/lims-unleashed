<template>
  <div>
    <ApexChart
      :type="type"
      :height="height"
      :width="width"
      :series="series"
      :options="options"
      :key="chartKey"
    />
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue'

const ApexChart = defineAsyncComponent(async () => (await import('vue3-apexcharts')).default)

const props = defineProps({
  type: {
    type: String,
    default: 'line'
  },
  height: {
    type: [String, Number],
    default: 300
  },
  width: {
    type: [String, Number],
    default: '100%'
  },
  series: {
    type: Array,
    default: () => []
  },
  options: {
    type: Object,
    default: () => ({})
  }
})

const chartKey = ref(0)

// Force re-render when options change
watch(() => props.options, () => {
  chartKey.value += 1
}, { deep: true })

// Merge default options with custom options
const mergedOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      },
      dynamicAnimation: {
        enabled: true,
        speed: 350
      }
    },
    ...props.options.chart
  },
  colors: ['#1e3a8a', '#10b981', '#f59e0b', '#dc2626', '#8b5cf6', '#06b6d4'],
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
    padding: {
      top: 0,
      right: 20,
      bottom: 0,
      left: 20
    },
    ...props.options.grid
  },
  dataLabels: {
    enabled: false,
    style: {
      fontSize: '12px',
      fontWeight: 600
    },
    ...props.options.dataLabels
  },
  stroke: {
    curve: 'smooth',
    width: 3,
    ...props.options.stroke
  },
  xaxis: {
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 400
      }
    },
    ...props.options.xaxis
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 400
      },
      formatter: (value) => {
        return props.options.yaxis?.formatter?.(value) || value
      }
    },
    ...props.options.yaxis
  },
  tooltip: {
    shared: true,
    intersect: false,
    theme: 'light',
    ...props.options.tooltip
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '12px',
    markers: {
      radius: 12
    },
    itemMargin: {
      horizontal: 10,
      vertical: 5
    },
    ...props.options.legend
  },
  ...props.options
}))
</script>
