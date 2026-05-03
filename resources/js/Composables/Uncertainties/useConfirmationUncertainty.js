import { ref } from 'vue';

export function useConfirmationUncertainty() {
  const data = ref([0]);
  const distributionalUncertainty = ref(0);
  const technicalUncertainty = ref(0);
  const confirmationUncertainty = ref(0);

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
      confirmationUncertainty.value ** 2
    );
  };

  return {
    data,
    distributionalUncertainty,
    technicalUncertainty,
    confirmationUncertainty,
    addMeasurement,
    updateMeasurement,
    calculateCombinedUncertainty,
  };
}
