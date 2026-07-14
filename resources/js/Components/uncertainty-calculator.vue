<template>
    <div>
      <h2>Calculadora dinâmica de incerteza</h2>

      <p>Lista de séries:</p>
      <pre>{{ seriesList }}</pre>

      <!-- Dynamic Series Input -->
      <div v-for="(series, seriesIndex) in seriesList" :key="seriesIndex">
        <h3>Série {{ seriesIndex + 1 }}</h3>
        <div v-for="(value, dataIndex) in series.data" :key="dataIndex">
          <BaseInput
            type="number"
            v-model.number="series.data[dataIndex]"
            @input="updateSeriesData(seriesIndex, dataIndex, series.data[dataIndex])"
          />
        </div>
        <button @click="addMeasurementToSeries(seriesIndex)">Adicionar medição à série {{ seriesIndex + 1 }}</button>
      </div>
      <button @click="addNewSeries">Adicionar série</button>

      <!-- Calculate Combined Uncertainty -->
      <div>
        <button @click="calculateCombinedUncertainty">Calcular incerteza combinada</button>
      </div>

      <!-- Display Combined Uncertainty -->
      <div v-if="combinedUncertainty !== null">
        <p>Incerteza combinada: {{ combinedUncertainty }}</p>
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

