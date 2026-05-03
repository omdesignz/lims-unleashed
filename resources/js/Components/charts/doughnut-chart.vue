<!-- BarChart.vue -->
<template>
    <div class="w-full h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </template>
  
  <script setup>
  import { useChart } from '@/Composables/useChart';
  import { onMounted } from 'vue';
  
  const { chartCanvas, setupChart } = useChart('doughnut');
  
  const data = {
    labels: ['Red', 'Blue', 'Yellow', 'Green'],
    datasets: [
      {
        label: 'Votes',
        data: [12, 19, 3, 5],
      },
    ],
  };
  
  onMounted(() => setupChart(data));
  </script>
  