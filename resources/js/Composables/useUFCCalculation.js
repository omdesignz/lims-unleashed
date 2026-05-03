import { ref } from 'vue';

export function useUFCCalculation() {
  const dilutionFactor = ref(1);
  const colonyCount = ref(0);
  const volume = ref(1); // Volume in mL or g

  const setDilutionFactor = (factor) => (dilutionFactor.value = factor);
  const setColonyCount = (count) => (colonyCount.value = count);
  const setVolume = (vol) => (volume.value = vol);

  const calculateUFC = () => {
    return (colonyCount.value * dilutionFactor.value) / volume.value;
  };

  return {
    dilutionFactor,
    colonyCount,
    volume,
    setDilutionFactor,
    setColonyCount,
    setVolume,
    calculateUFC,
  };
}