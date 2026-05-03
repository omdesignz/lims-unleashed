import { ref } from 'vue';
import { useDynamicSeriesUncertainty } from './useDynamicSeriesUncertainty.js';
import { useMatrixUncertainty } from './useMatrixUncertainty.js';
import { useDistributionalUncertainty } from './useDistributionalUncertainty.js';
import { useConfirmationUncertainty } from './useConfirmationUncertainty.js';
import { useTechnicalUncertainty } from './useTechnicalUncertainty.js';

export function useCombinedUncertainty() {
  const combinedUncertainty = ref(null);

  const { seriesList, calculateCombinedUncertainty: calculateSeriesUncertainty } = useDynamicSeriesUncertainty();
  const { matrixUncertainty } = useMatrixUncertainty();
  const { distributionalUncertainty } = useDistributionalUncertainty();
  const { confirmationUncertainty } = useConfirmationUncertainty();
  const { technicalUncertainty } = useTechnicalUncertainty();

  const calculateCombinedUncertainty = () => {
    const dynamicSeriesUncertainty = calculateSeriesUncertainty();
    combinedUncertainty.value = Math.sqrt(
      dynamicSeriesUncertainty ** 2 +
      matrixUncertainty.value ** 2 +
      distributionalUncertainty.value ** 2 +
      confirmationUncertainty.value ** 2 +
      technicalUncertainty.value ** 2
    );
  };

  return {
    combinedUncertainty,
    calculateCombinedUncertainty,
  };
}
