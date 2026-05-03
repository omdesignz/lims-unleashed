<template>
    <div>
      <h2>Standard Deviation Calculator</h2>
      <div v-for="(value, index) in data" :key="index">
        <input
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
        />
      </div>
      <button @click="addMeasurement">Add Measurement</button>
      <p>Standard Deviation: {{ calculateStandardDeviation() }}</p>
    </div>
  </template>
  
  <script setup>
  import { useStandardDeviation } from '@/Composables/Uncertainties/useStandardDeviation.js';

  const { data, addMeasurement, updateMeasurement, calculateStandardDeviation } = useStandardDeviation();

  </script>
  