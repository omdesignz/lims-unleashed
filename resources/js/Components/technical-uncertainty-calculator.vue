<template>
    <div class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Calculadora de incerteza técnica</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Registe as medições e as contribuições para obter a incerteza combinada.</p>
      </div>
      <div class="grid gap-3">
        <div v-for="(value, index) in data" :key="index">
          <input
            type="number"
            v-model.number="data[index]"
            @input="updateMeasurement(index, data[index])"
            :placeholder="`Medição ${index + 1}`"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
        </div>
      </div>
      <button @click="addMeasurement" class="rounded-2xl bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-800">
        Adicionar medição
      </button>
      <div class="grid gap-3 md:grid-cols-2">
        <input
          type="number"
          v-model.number="distributionalUncertainty"
          placeholder="Incerteza distribucional"
          class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
        />
        <input
          type="number"
          v-model.number="technicalUncertainty"
          placeholder="Incerteza técnica"
          class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
        />
      </div>
      <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
        Incerteza combinada: {{ calculateCombinedUncertainty() }}
      </div>
    </div>
  </template>
  
  <script setup>
  import { useTechnicalUncertainty } from '@/Composables/Uncertainties/useTechnicalUncertainty.js';

  const {
        data,
        distributionalUncertainty,
        technicalUncertainty,
        addMeasurement,
        updateMeasurement,
        calculateCombinedUncertainty,
      } = useTechnicalUncertainty();
  
  </script>  
