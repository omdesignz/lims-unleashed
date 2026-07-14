<template>
    <div>
      <h2>Calculadora completa de incerteza</h2>
      <!-- Inputs for Measurements -->
      <div v-for="(value, index) in data" :key="index">
        <BaseInput
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
          placeholder="Medição"
        />
      </div>
      <button @click="addMeasurement">Adicionar medição</button>

      <!-- Inputs for Each Uncertainty Type -->
      <BaseInput
        type="number"
        v-model.number="distributionalUncertainty"
        placeholder="Incerteza da distribuição"
      />
      <BaseInput
        type="number"
        v-model.number="technicalUncertainty"
        placeholder="Incerteza técnica"
      />
      <BaseInput
        type="number"
        v-model.number="confirmationUncertainty"
        placeholder="Incerteza de confirmação"
      />
      <BaseInput
        type="number"
        v-model.number="environmentalUncertainty"
        placeholder="Incerteza ambiental"
      />
      <BaseInput
        type="number"
        v-model.number="matrixUncertainty"
        placeholder="Incerteza da matriz"
      />

      <!-- Displaying the Calculated Combined Uncertainty -->
      <p>Incerteza combinada: {{ calculateCombinedUncertainty() }}</p>
    </div>
  </template>

  <script setup>
  import { useMatrixUncertainty } from '@/Composables/Uncertainties/useMatrixUncertainty';

  const {
        data,
        distributionalUncertainty,
        technicalUncertainty,
        confirmationUncertainty,
        environmentalUncertainty,
        matrixUncertainty,
        addMeasurement,
        updateMeasurement,
        calculateCombinedUncertainty,
      } = useMatrixUncertainty();
  </script>

