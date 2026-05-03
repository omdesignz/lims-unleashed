<template>

<div class="border-2 border-blue-900 pb-4 rounded-md">
  <h2 class="text-base font-semibold leading-6 text-gray-900 m-2">Unidades Formadoras de Colónias (UFC)</h2>

  <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10 m-2">

<div class="sm:col-span-2 sm:col-start-1">
  <label for="dilutionFactor" class="block text-sm font-medium leading-6 text-gray-900">Factor de Diluição</label>
  <div class="mt-2">
    <input v-model.number="dilutionFactor" type="number" name="dilutionFactor" id="dilutionFactor" autocomplete="dilutionFactor" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" @input="updateDilutionFactor" />
  </div>
</div>

<div class="sm:col-span-3">
  <label for="colonyCount" class="block text-sm font-medium leading-6 text-gray-900">Nº de Colónias</label>
  <div class="mt-2">
    <input v-model="colonyCount" type="text" name="colonyCount" id="colonyCount" autocomplete="colonyCount" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" @input="updateColonyCount" />
  </div>
</div>

<div class="sm:col-span-3">
  <label for="volume" class="block text-sm font-medium leading-6 text-gray-900">Volume (mL or g)</label>
  <div class="mt-2">
    <input v-model="volume" type="text" name="volume" id="volume" autocomplete="volume" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" @input="updateVolume" />
  </div>
</div>

<div class="sm:col-span-2">
  <div class="mt-2">
  <!-- <label for="button" class="block text-sm font-medium leading-6 text-gray-900"></label> -->
    <button @click="calculate" class="inline-flex justify-center mt-6 rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.calculate') }}</button>
  </div>
</div>

      <div v-if="ufc !== null" class="sm:col-span-4">
        <p>UFC: {{ ufc }} / mL ou g</p>
      </div>

</div>
</div>

  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { useUFCCalculation } from '@/Composables/useUFCCalculation';

  const { dilutionFactor, colonyCount, volume, setDilutionFactor, setColonyCount, setVolume, calculateUFC } = useUFCCalculation();
  
    const ufc = ref(null);

    const updateDilutionFactor = (event) => setDilutionFactor(Number(event.target.value));
    const updateColonyCount = (event) => setColonyCount(Number(event.target.value));
    const updateVolume = (event) => setVolume(Number(event.target.value));

    const calculate = () => {
    ufc.value = calculateUFC();
    };
  
  </script>
  