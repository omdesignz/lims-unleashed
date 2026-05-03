import { ref, computed } from 'vue';

export function useEccentricityCalculation() {
  const readings = ref({
    topLeft: null,
    topRight: null,
    center: null,
    bottomLeft: null,
    bottomRight: null,
  });

  const platformShape = ref('circle'); // Default shape

  const averageReading = computed(() => {
    const values = Object.values(readings.value).filter(val => val !== null);
    return values.length ? values.reduce((a, b) => a + b, 0) / values.length : 0;
  });

  const eccentricity = computed(() => {
    const values = Object.values(readings.value).filter(val => val !== null);
    return values.length ? Math.max(...values) - Math.min(...values) : 0;
  });

  const eccentricityPercentage = computed(() => {
    return averageReading.value ? (eccentricity.value / averageReading.value) * 100 : 0;
  });
  

  // Static positions for each platform shape
//   const diagramPoints = computed(() => {
//     switch (platformShape.value) {
//       case 'square':
//         return [
//           { label: 'Top Left', x: 1, y: 1, value: readings.value.topLeft },
//           { label: 'Top Right', x: 5, y: 1, value: readings.value.topRight },
//           { label: 'Center', x: 3, y: 3, value: readings.value.center },
//           { label: 'Bottom Left', x: 1, y: 5, value: readings.value.bottomLeft },
//           { label: 'Bottom Right', x: 5, y: 5, value: readings.value.bottomRight },
//         ];
//       case 'rectangle':
//         return [
//           { label: 'Top Left', x: 1, y: 1, value: readings.value.topLeft },
//           { label: 'Top Right', x: 6, y: 1, value: readings.value.topRight },
//           { label: 'Center', x: 3.5, y: 3, value: readings.value.center },
//           { label: 'Bottom Left', x: 1, y: 5, value: readings.value.bottomLeft },
//           { label: 'Bottom Right', x: 6, y: 5, value: readings.value.bottomRight },
//         ];
//       case 'circle':
//       default:
//         return [
//           { label: 'Top Left', x: 2, y: 1, value: readings.value.topLeft },
//           { label: 'Top Right', x: 4, y: 1, value: readings.value.topRight },
//           { label: 'Center', x: 3, y: 3, value: readings.value.center },
//           { label: 'Bottom Left', x: 2, y: 5, value: readings.value.bottomLeft },
//           { label: 'Bottom Right', x: 4, y: 5, value: readings.value.bottomRight },
//         ];
//     }
//   });

  // Adjusted diagram points positioned slightly inward
  const adjustedDiagramPoints = computed(() => [
    { label: 'topLeft', x: 1.3, y: 1.3, value: readings.value.topLeft },
    { label: 'topRight', x: 4.7, y: 1.3, value: readings.value.topRight },
    { label: 'center', x: 3, y: 3, value: readings.value.center },
    { label: 'bottomLeft', x: 1.3, y: 4.7, value: readings.value.bottomLeft },
    { label: 'bottomRight', x: 4.7, y: 4.7, value: readings.value.bottomRight },
  ]);

  function updateReading(point, value) {
    readings.value[point] = value;
  }

  return {
    readings,
    averageReading,
    eccentricity,
    eccentricityPercentage,
    // diagramPoints,
    platformShape,
    adjustedDiagramPoints,
    updateReading,
  };
}
