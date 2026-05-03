<template>
<div class="border-2 border-blue-900 pb-4 rounded-md">
    <div class="space-y-4">
    <h2 class="text-base font-semibold leading-6 text-gray-900 m-2">Cálculo de Dispersão Microbiana</h2>

  <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10 m-2 ml-2">

<div class="sm:col-span-2 sm:col-start-1">
  <label for="sumC" class="block text-sm font-medium leading-6 text-gray-900">Soma de Colônias (ΣC)</label>
  <div class="mt-2">
    <input v-model.number="sumC" type="number" id="sumC" placeholder="Soma de Colônias (ΣC)" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
  </div>
</div>

<div class="sm:col-span-3">
  <label for="volume" class="block text-sm font-medium leading-6 text-gray-900">Volume de Inoculamento (V em mL)</label>
  <div class="mt-2">
    <input v-model.number="volume" type="number" id="volume" placeholder="Volume" name="volume" autocomplete="volume" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
  </div>
</div>

<div class="sm:col-span-2">
  <label for="n1" class="block text-sm font-medium leading-6 text-gray-900">Nº de Pratos na 1ª Diluição (n1)</label>
  <div class="mt-2">
    <input v-model.number="n1" type="number" name="n1" id="n1" placeholder="n1" autocomplete="n1" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
  </div>
</div>

<div class="sm:col-span-2">
  <label for="n2" class="block text-sm font-medium leading-6 text-gray-900">Nº de Pratos na 2ª Diluição (n2)</label>
  <div class="mt-2">
    <input v-model.number="n2" type="number" name="n2" id="n2" placeholder="n2" autocomplete="n2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
  </div>
</div>

<div class="sm:col-span-2">
  <label for="dilution" class="block text-sm font-medium leading-6 text-gray-900"> 1ª Diluição (d)</label>
  <div class="mt-2">
    <input v-model="dilution" type="text" name="dilution" id="dilution" placeholder="d" autocomplete="dilution" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
  </div>
</div>

<div class="sm:col-span-2">
  <div class="mt-2">
    <button @click="calculateResult" class="inline-flex justify-center mt-6 rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.calculate') }}</button>
  </div>
</div>

<div class="sm:col-span-2">
    <div class="mt-2 block text-sm font-medium leading-6 text-gray-900">
        <p>Intervalo de Confiança</p>
        <p class="mt-1 text-sm leading-6 text-gray-600 font-black">{{ result }}</p>
    </div>
</div>

</div>
</div>
</div>

  </template>
  
  <script>
  import { useConfidenceInterval } from '@/Composables/Uncertainties/useConfidenceInterval';
  
  export default {
    setup() {
      // Use the composable
      const { sumC, volume, n1, n2, dilution, result, calculateResult } = useConfidenceInterval();
  
      return {
        sumC,
        volume,
        n1,
        n2,
        dilution,
        result,
        calculateResult,
      };
    },
  };
  </script>
  
  <style scoped>
  input {
    margin: 0.5rem;
    padding: 0.5rem;
    width: 250px;
  }
  
  button {
    margin: 1rem 0;
    padding: 0.5rem 1rem;
  }
  </style>
  