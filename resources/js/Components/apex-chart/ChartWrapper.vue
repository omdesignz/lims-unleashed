<template>
  <div class="ds-card overflow-hidden p-3">
    <ApexChart
      :type="type"
      :height="height"
      :width="width"
      :series="series"
      :options="mergedOptions"
      :key="chartKey"
    />
  </div>
</template>

<script setup>
import { categoricalPalette, chartFontFamily, chartTokens } from '@/Support/charts'

const chartPalette = categoricalPalette.light
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref, watch } from 'vue'

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
const isDarkMode = ref(false)

let themeObserver = null

const syncDarkMode = () => {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  syncDarkMode()

  themeObserver = new MutationObserver(syncDarkMode)
  themeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
  })
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})

// Force re-render when options change
watch(() => props.options, () => {
  chartKey.value += 1
}, { deep: true })

watch(isDarkMode, () => {
  chartKey.value += 1
})

// Merge default options with custom options
const mergedOptions = computed(() => ({
  ...props.options,
  chart: {
    background: 'transparent',
    foreColor: isDarkMode.value ? '#f3f5f7' : '#111827',
    fontFamily: 'inherit',
    toolbar: { show: false },
    fontFamily: chartFontFamily,
    animations: {
      enabled: true,
      speed: 420,
      animateGradually: {
        enabled: false,
      },
      dynamicAnimation: {
        enabled: true,
        speed: 240
      }
    },
    ...props.options.chart
  },
  colors: props.options.colors ?? chartPalette,
  grid: {
    borderColor: chartTokens(isDarkMode.value).grid,
    strokeDashArray: 3,
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
      fontWeight: 500,
      colors: [chartTokens(isDarkMode.value).text],
    },
    ...props.options.dataLabels
  },
  stroke: {
    curve: 'smooth',
    width: 2,
    ...props.options.stroke
  },
  xaxis: {
    labels: {
      style: {
        colors: chartTokens(isDarkMode.value).muted,
        fontSize: '12px',
        fontWeight: 400
      }
    },
    ...props.options.xaxis
  },
  yaxis: {
    labels: {
      style: {
        colors: chartTokens(isDarkMode.value).muted,
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
    theme: props.options.tooltip?.theme ?? (isDarkMode.value ? 'dark' : 'light'),
    ...props.options.tooltip
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '12px',
    labels: {
      colors: chartTokens(isDarkMode.value).text,
      ...props.options.legend?.labels,
    },
    markers: {
      size: 5,
      shape: 'circle'
    },
    itemMargin: {
      horizontal: 10,
      vertical: 5
    },
    ...props.options.legend
  },
  theme: {
    mode: isDarkMode.value ? 'dark' : 'light',
    ...props.options.theme,
  },
}))
</script>
