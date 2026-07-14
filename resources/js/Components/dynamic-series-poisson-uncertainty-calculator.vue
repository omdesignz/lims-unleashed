<template>
    <div>
      <h2>Calculadora de incerteza por séries dinâmicas (distribuição de Poisson)</h2>
      <div v-for="(seriesData, seriesIndex) in series" :key="seriesIndex">
        <h3>Série {{ seriesIndex + 1 }}</h3>
        <div>
          <label>
            <CheckboxInput
              type="checkbox"
              v-model="seriesData.isPoisson"
            />
            Utilizar distribuição de Poisson
          </label>
        </div>
        <div v-for="(value, dataIndex) in seriesData.data" :key="dataIndex">
          <BaseInput
            type="number"
            v-model.number="seriesData.data[dataIndex]"
            @input="updateMeasurement(seriesIndex, dataIndex, seriesData.data[dataIndex])"
            placeholder="Medição"
          />
        </div>
        <button @click="addMeasurementToSeries(seriesIndex)">Adicionar medição</button>

        <!-- Uncertainty Inputs for the Series -->
        <BaseInput
          type="number"
          v-model.number="seriesData.technicalUncertainty"
          placeholder="Incerteza técnica"
        />
        <BaseInput
          type="number"
          v-model.number="seriesData.confirmationUncertainty"
          placeholder="Incerteza de confirmação"
        />
        <BaseInput
          type="number"
          v-model.number="seriesData.environmentalUncertainty"
          placeholder="Incerteza ambiental"
        />
        <BaseInput
          type="number"
          v-model.number="seriesData.matrixUncertainty"
          placeholder="Incerteza da matriz"
        />

        <p>Incerteza combinada da série {{ seriesIndex + 1 }}: {{ combinedUncertainties[seriesIndex] }}</p>
      </div>
      <button @click="addSeries">Adicionar série</button>
    </div>
  </template>

  <script setup>
  import { useDynamicSeriesWithPoissonUncertainty } from '@/Composables/Uncertainties/useDynamicSeriesWithPoissonUncertainty';

  const {
        series,
        addSeries,
        addMeasurementToSeries,
        updateMeasurement,
        togglePoisson,
        combinedUncertainties,
      } = useDynamicSeriesWithPoissonUncertainty();
  </script>
