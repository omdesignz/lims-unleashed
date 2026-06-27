<template>
  <div class="ds-card overflow-hidden p-4">
    <apexchart 
      :key="chartThemeKey"
      v-if="options && series"
      :type="type"
      :height="height"
      :options="options"
      :series="series"
    />
    <div v-else class="ds-empty-state min-h-64">
      {{ $t('gestlab.general.chart.loading') }}
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
  type: {
    type: String,
    default: 'bar'
  },
  height: {
    type: [String, Number],
    default: 300
  },
  chartData: {
    type: Object,
    default: () => ({})
  }
})

const series = ref([])
const options = ref(null)
const isDark = ref(false)
let darkModeObserver

const syncDarkMode = () => {
  isDark.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  syncDarkMode()
  darkModeObserver = new MutationObserver(syncDarkMode)
  darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
  darkModeObserver?.disconnect()
})

const chartThemeKey = computed(() => `${props.type}-${isDark.value ? 'dark' : 'light'}`)

const initializeChart = () => {
  if (!props.chartData || Object.keys(props.chartData).length === 0) {
    options.value = null
    series.value = []
    return
  }

  if (props.type === 'bar' && props.chartData.status_stats) {
    const stats = props.chartData.status_stats
    series.value = [{
      name: trans('gestlab.general.chart.tasks'),
      data: [
        stats.overdue || 0,
        stats.due_soon || 0,
        stats.scheduled || 0,
        stats.executed || 0
      ]
    }]

    options.value = {
      chart: {
        type: 'bar',
        height: props.height,
        background: 'transparent',
        foreColor: isDark.value ? '#d7e2dd' : '#31413b',
        toolbar: {
          show: false
        }
      },
      plotOptions: {
        bar: {
          borderRadius: 4,
          columnWidth: '60%',
        }
      },
      colors: ['#dc2626', '#d89f39', '#1f7a68', '#10b981'],
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '12px',
          fontWeight: 'bold'
        }
      },
      xaxis: {
        categories: [
          trans('gestlab.general.chart.statuses.overdue'),
          trans('gestlab.general.chart.statuses.due_soon'),
          trans('gestlab.general.chart.statuses.scheduled'),
          trans('gestlab.general.chart.statuses.executed')
        ],
        labels: {
          style: {
            colors: isDark.value ? '#d7e2dd' : '#31413b',
            fontSize: '12px',
            fontWeight: 600
          }
        }
      },
      yaxis: {
        title: {
          text: trans('gestlab.general.chart.task_count'),
          style: {
            color: isDark.value ? '#d7e2dd' : '#31413b',
            fontSize: '12px',
            fontWeight: 400
          }
        },
        min: 0
      },
      grid: {
        borderColor: isDark.value ? '#25443c' : '#ded3bf',
        strokeDashArray: 4
      },
      tooltip: {
        theme: isDark.value ? 'dark' : 'light'
      },
      theme: {
        mode: isDark.value ? 'dark' : 'light'
      }
    }
  }
}

watch(() => props.chartData, () => {
  initializeChart()
}, { immediate: true, deep: true })

watch(isDark, () => {
  initializeChart()
})
</script>
