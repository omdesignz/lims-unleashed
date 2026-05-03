<!-- AreaChart.vue -->
<template>
    <div class="w-full h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </template>
  
  <script setup>
  import { useChart } from '@/Composables/useChart';
  import { onMounted } from 'vue';
  
  const { chartCanvas, setupChart } = useChart('line'); // Area charts in Chart.js use type "line"
  
  const data = {
    labels: ['January', 'February', 'March', 'April'],
    datasets: [
      {
        label: 'Sales',
        data: [10, 20, 15, 30],
        fill: true, // Required for area effect
      },
    ],
  };
  
  onMounted(() => setupChart(data));
  </script>
  