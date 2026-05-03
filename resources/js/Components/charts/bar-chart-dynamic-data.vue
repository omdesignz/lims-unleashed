<!-- BarChart.vue -->
<template>
    <div class="w-full h-96">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </template>
  
  <script setup>
  import { useChart } from '@/Composables/useChart';
  import axios from 'axios';
  import { onMounted } from 'vue';
  
  const { chartCanvas, setupChart } = useChart('bar');
  
  async function fetchChartData() {
    try {
      const response = await axios.get('/api/chart-data/bar');
      const data = response.data;
      const options = data.options || {};
  
      setupChart(data, options);
    } catch (error) {
      console.error('Error fetching chart data:', error);
    }
  }
  
  onMounted(() => {
    fetchChartData();
    });
  </script>
  