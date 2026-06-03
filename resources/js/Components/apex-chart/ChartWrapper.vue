<template>
  <div class="rounded-[1.5rem] border border-[#e8ddcd] bg-[#fffdf7]/70 p-3 shadow-inner shadow-white/40 dark:border-[#25443c] dark:bg-[#07110f]/40 dark:shadow-none">
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
    foreColor: isDarkMode.value ? '#d7e2dd' : '#31413b',
    fontFamily: 'inherit',
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
  colors: props.options.colors ?? ['#143d37', '#1f7a68', '#d6a43a', '#b54747', '#5b6f63', '#6aa99b'],
  grid: {
    borderColor: isDarkMode.value ? '#25443c' : '#e8ddcd',
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
      fontWeight: 600,
      colors: [isDarkMode.value ? '#f7f1e7' : '#15231f'],
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
        colors: isDarkMode.value ? '#a9bbb4' : '#5f6f68',
        fontSize: '12px',
        fontWeight: 400
      }
    },
    ...props.options.xaxis
  },
  yaxis: {
    labels: {
      style: {
        colors: isDarkMode.value ? '#a9bbb4' : '#5f6f68',
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
      colors: isDarkMode.value ? '#d7e2dd' : '#31413b',
      ...props.options.legend?.labels,
    },
    markers: {
      radius: 12
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
