<template>
    <div>
      <h2>Calculadora de incerteza da distribuição</h2>
      <div v-for="(value, index) in data" :key="index">
        <BaseInput
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
        />
      </div>
      <button @click="addMeasurement">Adicionar medição</button>
      <BaseInput
        type="number"
        v-model.number="distributionalUncertainty"
        placeholder="Incerteza da distribuição"
      />
      <p>Incerteza combinada: {{ calculateCombinedUncertainty() }}</p>
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

