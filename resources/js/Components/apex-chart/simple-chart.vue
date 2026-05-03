<template>
  <div>
    <apexchart 
      v-if="options && series"
      :type="type"
      :height="height"
      :options="options"
      :series="series"
    />
    <div v-else class="flex items-center justify-center h-64 text-gray-500">
      A carregar dados...
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

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

const initializeChart = () => {
  if (!props.chartData || Object.keys(props.chartData).length === 0) {
    return
  }

  // Example for status distribution chart
  if (props.type === 'bar' && props.chartData.status_stats) {
    const stats = props.chartData.status_stats
    series.value = [{
      name: 'Tarefas',
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
      colors: ['#dc2626', '#f59e0b', '#3b82f6', '#10b981'],
      dataLabels: {
        enabled: true,
        style: {
          fontSize: '12px',
          fontWeight: 'bold'
        }
      },
      xaxis: {
        categories: ['Atrasada', 'Vencida', 'Agendada', 'Concluída'],
        labels: {
          style: {
            fontSize: '12px',
            fontWeight: 600
          }
        }
      },
      yaxis: {
        title: {
          text: 'Número de Tarefas',
          style: {
            fontSize: '12px',
            fontWeight: 400
          }
        },
        min: 0
      },
      grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4
      }
    }
  }
}

// Watch for chartData changes
watch(() => props.chartData, () => {
  initializeChart()
}, { immediate: true, deep: true })
</script>