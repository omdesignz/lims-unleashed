<template>
    <div>
      <h2>Comprehensive Uncertainty Calculator</h2>
      <!-- Inputs for Measurements -->
      <div v-for="(value, index) in data" :key="index">
        <BaseInput
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
          placeholder="Measurement"
        />
      </div>
      <button @click="addMeasurement">Add Measurement</button>
  
      <!-- Inputs for Each Uncertainty Type -->
      <BaseInput
        type="number"
        v-model.number="distributionalUncertainty"
        placeholder="Distributional Uncertainty"
      />
      <BaseInput
        type="number"
        v-model.number="technicalUncertainty"
        placeholder="Technical Uncertainty"
      />
      <BaseInput
        type="number"
        v-model.number="confirmationUncertainty"
        placeholder="Confirmation Uncertainty"
      />
      <BaseInput
        type="number"
        v-model.number="environmentalUncertainty"
        placeholder="Environmental Uncertainty"
      />
      <BaseInput
        type="number"
        v-model.number="matrixUncertainty"
        placeholder="Matrix Uncertainty"
      />
  
      <!-- Displaying the Calculated Combined Uncertainty -->
      <p>Combined Uncertainty: {{ calculateCombinedUncertainty() }}</p>
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
  