<template>
    <div>
      <h2>Uncertainty Calculator with Environmental Uncertainty</h2>
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
      <input
        type="number"
        v-model.number="technicalUncertainty"
        placeholder="Technical Uncertainty"
      />
      <input
        type="number"
        v-model.number="confirmationUncertainty"
        placeholder="Confirmation Uncertainty"
      />
      <input
        type="number"
        v-model.number="environmentalUncertainty"
        placeholder="Environmental Uncertainty"
      />
      <p>Combined Uncertainty: {{ calculateCombinedUncertainty() }}</p>
    </div>
  </template>
  
  <script setup>
  import { useEnvironmentalUncertainty } from '@/Composables/Uncertainties/useEnvironmentalUncertainty.js';

  const {
        data,
        distributionalUncertainty,
        technicalUncertainty,
        confirmationUncertainty,
        environmentalUncertainty,
        addMeasurement,
        updateMeasurement,
        calculateCombinedUncertainty,
      } = useEnvironmentalUncertainty();
  
  </script>  