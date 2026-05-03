<template>
    <div class="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Calculadora dinâmica de séries</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Organize medições por série e acompanhe a incerteza combinada de cada conjunto.</p>
      </div>
      <div v-for="(seriesData, seriesIndex) in series" :key="seriesIndex" class="space-y-4 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Série {{ seriesIndex + 1 }}</h3>
        <div class="grid gap-3 md:grid-cols-2">
          <input
            v-for="(value, dataIndex) in seriesData.data"
            :key="dataIndex"
            type="number"
            v-model.number="seriesData.data[dataIndex]"
            @input="updateMeasurement(seriesIndex, dataIndex, seriesData.data[dataIndex])"
            :placeholder="`Medição ${dataIndex + 1}`"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
        </div>
        <button @click="addMeasurementToSeries(seriesIndex)" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
          Adicionar medição
        </button>

        <div class="grid gap-3 md:grid-cols-2">
          <input
            type="number"
            v-model.number="seriesData.technicalUncertainty"
            placeholder="Incerteza técnica"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
          <input
            type="number"
            v-model.number="seriesData.confirmationUncertainty"
            placeholder="Incerteza de confirmação"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
          <input
            type="number"
            v-model.number="seriesData.environmentalUncertainty"
            placeholder="Incerteza ambiental"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
          <input
            type="number"
            v-model.number="seriesData.matrixUncertainty"
            placeholder="Incerteza de matriz"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
        </div>

        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          Incerteza combinada da série {{ seriesIndex + 1 }}: {{ combinedUncertainties[seriesIndex] }}
        </div>
      </div>
      <button @click="addSeries" class="rounded-2xl bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-800">
        Adicionar série
      </button>
    </div>
  </template>
  
  <script setup>
  import { useDynamicSeriesAutoCalculatedUncertainty } from '@/Composables/Uncertainties/useDynamicSeriesAutoCalculatedUncertainty.js';
  
  const {
        series,
        addSeries,
        addMeasurementToSeries,
        updateMeasurement,
        combinedUncertainties,
      } = useDynamicSeriesAutoCalculatedUncertainty();
      
  </script>
  
