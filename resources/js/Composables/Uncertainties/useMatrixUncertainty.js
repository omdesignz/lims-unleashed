import { ref } from 'vue';

export function useMatrixUncertainty() {
  const data = ref([0]);
  const distributionalUncertainty = ref(0);
  const technicalUncertainty = ref(0);
  const confirmationUncertainty = ref(0);
  const environmentalUncertainty = ref(0);
  const matrixUncertainty = ref(0); // New matrix uncertainty state

  const addMeasurement = () => {
    data.value.push(0);
  };

  const updateMeasurement = (index, value) => {
    if (data.value[index] !== undefined) {
      data.value[index] = value;
    }
  };

  const calculateStandardDeviation = () => {
    if (data.value.length === 0) return 0;
    const mean = data.value.reduce((a, b) => a + b, 0) / data.value.length;
    const variance = data.value.reduce((sum, value) => sum + (value - mean) ** 2, 0) / data.value.length;
    return Math.sqrt(variance);
  };

  const calculateCombinedUncertainty = () => {
    const standardDeviation = calculateStandardDeviation();
    return Math.sqrt(
      standardDeviation ** 2 +
      distributionalUncertainty.value ** 2 +
      technicalUncertainty.value ** 2 +
      confirmationUncertainty.value ** 2 +
      environmentalUncertainty.value ** 2 +
      matrixUncertainty.value ** 2 // Matrix uncertainty contribution
    );
  };

  return {
    data,
    distributionalUncertainty,
    technicalUncertainty,
    confirmationUncertainty,
    environmentalUncertainty,
    matrixUncertainty, // Export the matrix uncertainty state
    addMeasurement,
    updateMeasurement,
    calculateCombinedUncertainty,
  };
}
