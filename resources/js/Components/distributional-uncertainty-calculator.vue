<template>
    <div>
      <h2>Uncertainty Calculator with Distributional Uncertainty</h2>
      <div v-for="(value, index) in data" :key="index">
        <input
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
        />
      </div>
      <button @click="addMeasurement">Add Measurement</button>
      <input
        type="number"
        v-model.number="distributionalUncertainty"
        placeholder="Distributional Uncertainty"
      />
      <p>Combined Uncertainty: {{ calculateCombinedUncertainty() }}</p>
    </div>
  </template>
  
  <script setup>
  import { useDistributionalUncertainty } from '@/Composables/Uncertainties/useDistributionalUncertainty.js';

  const {
        data,
        distributionalUncertainty,
        addMeasurement,
        updateMeasurement,
        calculateCombinedUncertainty,
      } = useDistributionalUncertainty();
  
  </script>
  