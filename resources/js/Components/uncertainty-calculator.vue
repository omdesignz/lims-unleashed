<template>
    <div>
      <h2>Dynamic Uncertainty Calculator</h2>

      <p>Series List:</p>
      <pre>{{ seriesList }}</pre>
  
      <!-- Dynamic Series Input -->
      <div v-for="(series, seriesIndex) in seriesList" :key="seriesIndex">
        <h3>Series {{ seriesIndex + 1 }}</h3>
        <div v-for="(value, dataIndex) in series.data" :key="dataIndex">
          <input
            type="number"
            v-model.number="series.data[dataIndex]"
            @input="updateSeriesData(seriesIndex, dataIndex, series.data[dataIndex])"
          />
        </div>
        <button @click="addMeasurementToSeries(seriesIndex)">Add Measurement to Series {{ seriesIndex + 1 }}</button>
      </div>
      <button @click="addNewSeries">Add New Series</button>
  
      <!-- Calculate Combined Uncertainty -->
      <div>
        <button @click="calculateCombinedUncertainty">Calculate Combined Uncertainty</button>
      </div>
  
      <!-- Display Combined Uncertainty -->
      <div v-if="combinedUncertainty !== null">
        <p>Combined Uncertainty: {{ combinedUncertainty }}</p>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { useDynamicSeriesUncertainty } from '@/Composables/Uncertainties/useDynamicSeriesUncertainty.js';
  import { useCombinedUncertainty } from '@/Composables/Uncertainties/useCombinedUncertainty.js';

  const {
        seriesList,
        addNewSeries,
        updateSeriesData,
        addMeasurementToSeries,
      } = useDynamicSeriesUncertainty();
  
      const { combinedUncertainty, calculateCombinedUncertainty } = useCombinedUncertainty();
  
//   export default {
//     setup() {
//       const {
//         seriesList,
//         addNewSeries,
//         updateSeriesData,
//         addMeasurementToSeries,
//       } = useDynamicSeriesUncertainty();
  
//       const { combinedUncertainty, calculateCombinedUncertainty } = useCombinedUncertainty();
  
//       return {
//         seriesList,
//         combinedUncertainty,
//         addNewSeries,
//         addMeasurementToSeries,
//         updateSeriesData,
//         calculateCombinedUncertainty,
//       };
//     },
//   };
  </script>
  