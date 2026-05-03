<!-- src/components/UncertaintyCalculator.vue -->
<template>
    <div>
      <h1>Uncertainty Calculator</h1>
  
      <!-- Input Fields with v-model bindings -->
      <label for="nominalValue">Nominal Value (e.g., 10 kg, 5 g):</label>
      <input v-model="nominalValue" type="text" placeholder="Enter nominal value" />
  
      <label for="conversion">Conversion (e.g., +2900 mg, -1950 mg):</label>
      <input v-model="conversion" type="text" placeholder="Enter conversion value" />
  
      <label for="uncertainty">Uncertainty (e.g., ±166.67 mg):</label>
      <input v-model="uncertainty" type="text" placeholder="Enter uncertainty" />
  
      <br><br>
  
      <h2>Converted Value: <span>{{ convertedValue }} {{ nominalUnit }}</span></h2>
      <h2>Uncertainty: <span>±{{ uncertaintyValue }} mg</span></h2>
  
      <h3>Formula Result: <span>{{ formulaResult }}</span></h3>
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
  