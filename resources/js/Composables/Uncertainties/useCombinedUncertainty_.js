import { computed } from 'vue';
import { useTechnicalUncertainty } from './useTechnicalUncertainty';
import { useMatrixUncertainty } from './useMatrixUncertainty';
import { useDistributionalUncertainty } from './useDistributionalUncertainty';

export function useCombinedUncertainty() {
  const { calculateStandardDeviation } = useTechnicalUncertainty();
  const { matrixUncertainty } = useMatrixUncertainty();
  const { calculatePoissonUncertainty } = useDistributionalUncertainty();

  const combinedUncertainty = computed(() => {
    const tech = calculateStandardDeviation();
    const matrix = matrixUncertainty.value;
    const distrib = calculatePoissonUncertainty();

    return Math.sqrt(tech ** 2 + matrix ** 2 + distrib ** 2);
  });

  return {
    combinedUncertainty,
  };
}