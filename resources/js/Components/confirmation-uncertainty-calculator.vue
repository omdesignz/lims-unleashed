<template>
    <div>
      <h2>Uncertainty Calculator with Confirmation Uncertainty</h2>
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
      <p>Combined Uncertainty: {{ calculateCombinedUncertainty() }}</p>
    </div>
  </template>
  
  <script setup>
  import { useConfirmationUncertainty } from '@/Composables/Uncertainties/useConfirmationUncertainty.js';
  
  const {
        data,
        distributionalUncertainty,
        technicalUncertainty,
        confirmationUncertainty,
        addMeasurement,
        updateMeasurement,
        calculateCombinedUncertainty,
      } = useConfirmationUncertainty();
      
  </script>  