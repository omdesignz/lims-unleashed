<template>
    <div>
      <h2>Calculadora de desvio-padrão</h2>
      <div v-for="(value, index) in data" :key="index">
        <BaseInput
          type="number"
          v-model.number="data[index]"
          @input="updateMeasurement(index, data[index])"
        />
      </div>
      <button @click="addMeasurement">Adicionar medição</button>
      <p>Desvio-padrão: {{ calculateStandardDeviation() }}</p>
    </div>
  </template>

  <script setup>
  import { useStandardDeviation } from '@/Composables/Uncertainties/useStandardDeviation.js';

  const { data, addMeasurement, updateMeasurement, calculateStandardDeviation } = useStandardDeviation();

  </script>

