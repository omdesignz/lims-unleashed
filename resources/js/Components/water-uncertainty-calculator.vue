<template>

<div class="border-2 border-blue-900 pb-4 rounded-md">

<div class="space-y-4">
  <h2 class="text-base font-semibold leading-6 text-gray-900 m-2">Cálculo de Incerteza Operacional | Amostras de Água</h2>
      <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10 ml-2">

        <div class="sm:col-span-2 sm:col-start-1">
          <label for="constantDvar" class="block text-sm font-medium leading-6 text-gray-900">Constante para dvar</label>
          <div class="mt-2">
            <input v-model.number="constantDvar" type="number" name="constantDvar" id="constantDvar" autocomplete="constantDvar" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
          </div>
        </div>

        <div class="sm:col-span-4">
          <label for="constantUncertainty" class="block text-sm font-medium leading-6 text-gray-900">Constante para Incerteza Operacional Estimada</label>
          <div class="mt-2">
            <input v-model="constantUncertainty" type="text" name="constantUncertainty" id="constantUncertainty" autocomplete="constantUncertainty" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
          </div>
        </div>

        <div class="sm:col-span-2">
          <div class="mt-2">
          <!-- <label for="button" class="block text-sm font-medium leading-6 text-gray-900"></label> -->
            <button @click="performCalculation" class="inline-flex justify-center mt-6 rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.calculate') }}</button>
          </div>
        </div>

      </div>


    <div class="border-b border-gray-900/10 pb-4">
      <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center m-2">
        <!-- Colony counts -->
        <button @click="addSeries1Value" class="rounded-full bg-blue-900 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900 ml-auto w-24 flex items-center">
          1ª Série<PlusCircleIcon class="h-5 w-5 ml-1" />
        </button>
        <button @click="addSeries2Value" class="rounded-full bg-blue-900 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900 ml-4 w-24 flex items-center">
          2ª Série<PlusCircleIcon class="h-5 w-5 ml-1" />
        </button>
      </h2>

    </div>

  </div>


  <div class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8 pb-4">
    <div class="-my-3 divide-y divide-gray-100 px-2 py-4 text-sm leading-6">
        <h3>1ª Série</h3>
        <div class="mt-2 flex rounded-md shadow-sm" v-for="(value, index) in series1" :key="'series1-' + index">
        <div class="relative flex flex-grow items-stretch focus-within:z-10">
            <input v-model.number="series1[index]" type="number" class="block w-full rounded-none rounded-l-md border-0 py-1.5 pl-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="John Smith" />
        </div>
        <button @click="removeSeries1Value(index)" type="button" class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-md px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <TrashIcon class="-ml-0.5 h-5 w-5 text-red-500" aria-hidden="true" />
        </button>
        </div>

    </div>
    <div class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
        <h3>2ª Série</h3>
        <div class="mt-2 flex rounded-md shadow-sm" v-for="(value, index) in series2" :key="'series2-' + index">
        <div class="relative flex flex-grow items-stretch focus-within:z-10">
            <input v-model.number="series2[index]" type="number" class="block w-full rounded-none rounded-l-md border-0 py-1.5 pl-2 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="John Smith" />
        </div>
        <button @click="removeSeries2Value(index)" type="button" class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-md px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <TrashIcon class="-ml-0.5 h-5 w-5 text-red-500" aria-hidden="true" />
        </button>
        </div>
    </div>
  </div>

  <!-- Display Results -->
  <div v-if="results" class="results">
    <h3 class="text-xl font-semibold mb-2">Resultados:</h3>
    <p><strong>Variância média de reprodutibilidade:</strong> {{ results.avgReproducibilityVariance.toFixed(4) }}</p>
    <p><strong>Valor médio da linha:</strong> {{ results.avgRowAverage.toFixed(4) }}</p>
    <p><strong>Média ovar:</strong> {{ results.avgOvar.toFixed(4) }}</p>
    <p><strong>Média dvar:</strong> {{ results.avgDvar.toFixed(4) }}</p>
    <p><strong>Incerteza Operacional Estimada:</strong> {{ results.estimatedOperationalUncertainty.toFixed(4) }}</p>
    <p><strong>Incerteza Operacional Relativa:</strong> {{ results.relativeOperationalUncertainty.toFixed(4) }}</p>
    <p><strong>Incerteza Operacional Relativa (%):</strong> {{ results.relativeOperationalUncertaintyPercentage.toFixed(2) }}%</p>
  </div>

</div>

  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { useUncertaintyCalculator } from '@/Composables/useWaterUncertaintyCalculator';
  import { PlusCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';

  const series1 = ref([0]);
      const series2 = ref([0]);
      const results = ref(null);
      
      // Configurable constants
      const constantDvar = ref(0.1886);
      const constantUncertainty = ref(5.302);
  
      const { calculateUncertainty } = useUncertaintyCalculator();
  
      const addSeries1Value = () => {
        series1.value.push(0);
      };
  
      const removeSeries1Value = (index) => {
        series1.value.splice(index, 1);
      };
  
      const addSeries2Value = () => {
        series2.value.push(0);
      };
  
      const removeSeries2Value = (index) => {
        series2.value.splice(index, 1);
      };
  
      const performCalculation = () => {
        if (series1.value.length && series2.value.length && series1.value.length === series2.value.length) {
          results.value = calculateUncertainty(
            series1.value,
            series2.value,
            constantDvar.value,
            constantUncertainty.value
          );
        } else {
          alert('Please ensure both series have the same number of values.');
        }
      };
  
  </script>
  
  <style scoped>
  .input-field {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-family: monospace;
  }
  
  .btn {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  
  .btn:hover {
    background-color: #0056b3;
  }
  
  .btn-secondary {
    background-color: #dc3545;
  }
  
  .btn-secondary:hover {
    background-color: #c82333;
  }
  
  .results {
    margin-top: 16px;
  }
  </style>
  