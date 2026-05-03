// src/composables/useChart.js
import { ref, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export function useChart(chartType) {
  const chartRef = ref(null);
  const chartCanvas = ref(null);
  const defaultOptions = ref({
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top',
        labels: {
          color: '#374151', // Tailwind's gray-700
        },
      },
    },
    scales: {
      x: {
        grid: {
          color: '#E5E7EB', // Tailwind's gray-200
        },
      },
      y: {
        grid: {
          color: '#E5E7EB',
        },
      },
    },
  });

  const colors = [
    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
  ];

  const setupChart = (data, options = {}) => {
    if (chartRef.value) chartRef.value.destroy();

    chartRef.value = new Chart(chartCanvas.value, {
      type: chartType,
      data: {
        ...data,
        datasets: data.datasets.map((dataset, index) => ({
          ...dataset,
          backgroundColor: dataset.backgroundColor || colors[index % colors.length],
          borderColor: dataset.borderColor || colors[index % colors.length],
          borderWidth: dataset.borderWidth || 2,
          tension: 0.4,
        })),
      },
      options: { ...defaultOptions.value, ...options },
    });
  };

  watch(
    () => [chartType, defaultOptions.value],
    (newData, oldData) => {
      if (chartRef.value) setupChart(newData);
    },
    { immediate: true, deep: true }
  );

  onMounted(() => {
    if (!chartCanvas.value) throw new Error("Canvas element is missing.");
  });

  return {
    chartCanvas,
    setupChart,
    destroyChart: () => chartRef.value?.destroy(),
  };
}
