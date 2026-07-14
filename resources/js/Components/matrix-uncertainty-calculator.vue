<template>
    <div>
      <h2>Calculadora de incerteza da matriz</h2>
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
      <p>Incerteza combinada: {{ calculateCombinedUncertainty() }}</p>
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

