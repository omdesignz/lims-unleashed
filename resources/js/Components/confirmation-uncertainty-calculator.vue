<template>
    <div>
      <h2>Calculadora de incerteza com confirmação</h2>
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
      <p>Incerteza combinada: {{ calculateCombinedUncertainty() }}</p>
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
