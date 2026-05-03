<!-- BarChart.vue -->
<template>
    <div class="w-full h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </template>
  
  <script setup>
  import { useChart } from '@/Composables/useChart';
  import { onMounted } from 'vue';
  
  const { chartCanvas, setupChart } = useChart('line');
  
  const data = {
    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    datasets: [
        {
        label: 'Growth Rate',
        data: [2, 4, 6, 8],
        borderColor: '#3B82F6',
        },
    ],
  };

  const options = {
    plugins: {
        legend: {
        position: 'bottom',
        },
    },
    scales: {
        y: {
        beginAtZero: true,
        },
    },
  };
  
  onMounted(() => setupChart(data, options));
  </script>
  