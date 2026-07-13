<template>
    <div>
      <h2>Uncertainty Calculator with Matrix Uncertainty</h2>
      <div v-for="(value, index) in data" :key="index">
        <BaseInput
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
        />
      </div>
      <button @click="addMeasurement">Add Measurement</button>
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
      <p>Combined Uncertainty: {{ calculateCombinedUncertainty() }}</p>
    </div>
  </template>
  
  <script setup>
  import { useMatrixUncertainty } from '@/Composables/Uncertainties/useMatrixUncertainty.js';

  const {
        data,
        distributionalUncertainty,
        technicalUncertainty,
        confirmationUncertainty,
        environmentalUncertainty,
        matrixUncertainty, // Using matrix uncertainty
        addMeasurement,
        updateMeasurement,
        calculateCombinedUncertainty,
      } = useMatrixUncertainty();
  
  </script>
  