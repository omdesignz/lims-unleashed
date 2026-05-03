<template>
    <div>
      <h2>Dynamic Series Uncertainty Calculator (with Poisson Distribution)</h2>
      <div v-for="(seriesData, seriesIndex) in series" :key="seriesIndex">
        <h3>Series {{ seriesIndex + 1 }}</h3>
        <div>
          <label>
            <input
              type="checkbox"
              v-model="seriesData.isPoisson"
            />
            Use Poisson Distribution
          </label>
        </div>
        <div v-for="(value, dataIndex) in seriesData.data" :key="dataIndex">
          <input
            type="number"
            v-model.number="seriesData.data[dataIndex]"
            @input="updateMeasurement(seriesIndex, dataIndex, seriesData.data[dataIndex])"
            placeholder="Measurement"
          />
        </div>
        <button @click="addMeasurementToSeries(seriesIndex)">Add Measurement</button>
  
        <!-- Uncertainty Inputs for the Series -->
        <input
          type="number"
          v-model.number="seriesData.technicalUncertainty"
          placeholder="Technical Uncertainty"
        />
        <input
          type="number"
          v-model.number="seriesData.confirmationUncertainty"
          placeholder="Confirmation Uncertainty"
        />
        <input
          type="number"
          v-model.number="seriesData.environmentalUncertainty"
          placeholder="Environmental Uncertainty"
        />
        <input
          type="number"
          v-model.number="seriesData.matrixUncertainty"
          placeholder="Matrix Uncertainty"
        />
  
        <p>Combined Uncertainty for Series {{ seriesIndex + 1 }}: {{ combinedUncertainties[seriesIndex] }}</p>
      </div>
      <button @click="addSeries">Add Series</button>
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