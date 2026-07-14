<!-- src/components/UncertaintyCalculator.vue -->
<template>
    <div>
      <h1>Calculadora de incerteza</h1>

      <!-- Input Fields with v-model bindings -->
      <label for="nominalValue">Valor nominal (por exemplo, 10 kg ou 5 g):</label>
      <BaseInput v-model="nominalValue" type="text" placeholder="Introduza o valor nominal" />

      <label for="conversion">Conversão (por exemplo, +2900 mg ou -1950 mg):</label>
      <BaseInput v-model="conversion" type="text" placeholder="Introduza o valor da conversão" />

      <label for="uncertainty">Incerteza (por exemplo, ±166,67 mg):</label>
      <BaseInput v-model="uncertainty" type="text" placeholder="Introduza a incerteza" />

      <br><br>

      <h2>Valor convertido: <span>{{ convertedValue }} {{ nominalUnit }}</span></h2>
      <h2>Incerteza: <span>±{{ uncertaintyValue }} mg</span></h2>

      <h3>Resultado da fórmula: <span>{{ formulaResult }}</span></h3>
    </div>
  </template>

  <script>
  import { computed } from 'vue';
  import { useUncertaintyCalculation } from '@/Composables/Calibrations/useUncertaintyCalculations';

  export default {
    name: 'UncertaintyCalculator',
    setup() {
      // Use the composable
      const {
        nominalValue,
        conversion,
        uncertainty,
        calculateConvertedValue,
        calculatedUncertainty
      } = useUncertaintyCalculation();

      // Reactive values for the output
      const convertedValue = computed(() => calculateConvertedValue.value);
      const uncertaintyValue = computed(() => calculatedUncertainty.value);
      const formulaResult = computed(() => `${convertedValue.value} ±${uncertaintyValue.value}`);

      // The unit from the nominal value (e.g., kg, g)
      const nominalUnit = computed(() => {
        const unitMatch = nominalValue.value.match(/[a-zA-Z]+/);
        return unitMatch ? unitMatch[0] : '';
      });

      return {
        nominalValue,
        conversion,
        uncertainty,
        convertedValue,
        uncertaintyValue,
        formulaResult,
        nominalUnit
      };
    }
  };
  </script>

  <style scoped>
  /* Styling as needed */
  </style>

