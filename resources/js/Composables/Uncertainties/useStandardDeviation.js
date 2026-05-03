import { ref } from 'vue';

export function useStandardDeviation() {
  const data = ref([0]);

  const addMeasurement = () => {
    data.value.push(0); // Add a new measurement initialized to 0
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

  return {
    data,
    addMeasurement,
    updateMeasurement,
    calculateStandardDeviation,
  };
}
