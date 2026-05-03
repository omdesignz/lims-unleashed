import { ref } from 'vue';

export function useConfidenceInterval() {
  const sumC = ref(null);       // ΣC (sum of colonies)
  const volume = ref(null);     // V (volume of inoculum)
  const n1 = ref(null);         // n1 (number of dishes at first dilution)
  const n2 = ref(null);         // n2 (number of dishes at second dilution)
  const dilution = ref(null);   // d (first dilution)
  const result = ref(null);     // Store the single final result

  const calculateResult = () => {
    if (!sumC.value || !volume.value || !n1.value || !n2.value || !dilution.value) {
      alert('Queira por favor preencher todos os valores.');
      return;
    }

    // Parse scientific notation input for dilution
    const parsedDilution = parseFloat(dilution.value);

    if (isNaN(parsedDilution)) {
      alert('Queira por favor inserir uma diluição válida em notação científica.');
      return;
    }

    // Calculate B = V(n1 + 0.1 * n2)
    const B = volume.value * (n1.value + 0.1 * n2.value);

    // Calculate δ = ΣC / B * (1 / d)
    const delta = (sumC.value / B) * (1 / dilution.value);

    result.value = delta.toFixed(4); // Round to 4 decimal places
  };

  return {
    sumC,
    volume,
    n1,
    n2,
    dilution,
    result,
    calculateResult,
  };
}
