import { ref, computed } from 'vue';

export function useDynamicSeriesAutoCalculatedUncertainty() {
  const series = ref([
    { 
      data: [0], 
      technicalUncertainty: 0, 
      confirmationUncertainty: 0, 
      environmentalUncertainty: 0, 
      matrixUncertainty: 0 
    }
  ]);

  const addSeries = () => {
    series.value.push({
      data: [0],
      technicalUncertainty: 0,
      confirmationUncertainty: 0,
      environmentalUncertainty: 0,
      matrixUncertainty: 0,
    });
  };

  const addMeasurementToSeries = (seriesIndex) => {
    series.value[seriesIndex].data.push(0);
  };

  const updateMeasurement = (seriesIndex, dataIndex, value) => {
    if (series.value[seriesIndex] && series.value[seriesIndex].data[dataIndex] !== undefined) {
      series.value[seriesIndex].data[dataIndex] = value;
    }
  };

  // Automatically calculate standard deviation as distributional uncertainty
  const calculateStandardDeviation = (seriesIndex) => {
    const data = series.value[seriesIndex]?.data || [];
    if (data.length === 0) return 0;
    const mean = data.reduce((a, b) => a + b, 0) / data.length;
    const variance = data.reduce((sum, value) => sum + (value - mean) ** 2, 0) / data.length;
    return Math.sqrt(variance);
  };

  const calculateCombinedUncertainty = (seriesIndex) => {
    const s = series.value[seriesIndex];
    const distributionalUncertainty = calculateStandardDeviation(seriesIndex); // Auto-calculated

    return Math.sqrt(
      distributionalUncertainty ** 2 +
      s.technicalUncertainty ** 2 +
      s.confirmationUncertainty ** 2 +
      s.environmentalUncertainty ** 2 +
      s.matrixUncertainty ** 2
    );
  };

  const combinedUncertainties = computed(() =>
    series.value.map((_, index) => calculateCombinedUncertainty(index))
  );

  return {
    series,
    addSeries,
    addMeasurementToSeries,
    updateMeasurement,
    combinedUncertainties,
  };
}
