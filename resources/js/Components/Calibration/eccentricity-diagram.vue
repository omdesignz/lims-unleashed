<template>
    <div>
    <h2>Eccentricity Calculation</h2>

    <!-- Platform Selection -->
    <label>
      <input type="radio" value="circle" v-model="platformShape" /> Circle
    </label>
    <label>
      <input type="radio" value="square" v-model="platformShape" /> Square
    </label>
    <label>
      <input type="radio" value="rectangle" v-model="platformShape" /> Rectangle
    </label>

    <!-- Input Table -->
    <table>
      <tr>
        <td>Top Left</td>
        <td><input v-model.number="readings.topLeft" @input="updateReading('topLeft', readings.topLeft)" /></td>
      </tr>
      <tr>
        <td>Top Right</td>
        <td><input v-model.number="readings.topRight" @input="updateReading('topRight', readings.topRight)" /></td>
      </tr>
      <tr>
        <td>Center</td>
        <td><input v-model.number="readings.center" @input="updateReading('center', readings.center)" /></td>
      </tr>
      <tr>
        <td>Bottom Left</td>
        <td><input v-model.number="readings.bottomLeft" @input="updateReading('bottomLeft', readings.bottomLeft)" /></td>
      </tr>
      <tr>
        <td>Bottom Right</td>
        <td><input v-model.number="readings.bottomRight" @input="updateReading('bottomRight', readings.bottomRight)" /></td>
      </tr>
    </table>

    <p>Average Reading: {{ averageReading }}</p>
    <p>Eccentricity: {{ eccentricity }}</p>
    <p>Eccentricity (%): {{ eccentricityPercentage.toFixed(2) }}%</p>

    <!-- SVG Diagram -->
    <svg width="200" height="200" viewBox="0 0 6 6">
      <!-- Conditional rendering of platform border with thinner stroke width -->
      <rect v-if="platformShape === 'square'" x="0.5" y="0.5" width="5" height="5" fill="none" stroke="black" stroke-width="0.05" />
      <rect v-if="platformShape === 'rectangle'" x="0.5" y="1" width="5.5" height="4" fill="none" stroke="black" stroke-width="0.05" />
      <circle v-if="platformShape === 'circle'" cx="3" cy="3" r="2.5" fill="none" stroke="black" stroke-width="0.05" />

      <!-- Static circles for each reading point, adjusted away from borders -->
      <circle v-for="point in adjustedDiagramPoints"
              :key="point.label"
              :cx="point.x"
              :cy="point.y"
              r="0.2"
              fill="blue" />
      <text v-for="point in adjustedDiagramPoints"
            :key="point.label + '-text'"
            :x="point.x"
            :y="point.y - 0.3"
            font-size="0.3"
            text-anchor="middle"
            fill="black">
        {{ point.value }}
      </text>
    </svg>
  </div>
  </template>
  
  <script>
  import { useEccentricityCalculation } from '@/Composables/Calibrations/useEccentricityCalculation';
  
  export default {
    setup() {
      const { readings, averageReading, eccentricity, eccentricityPercentage, adjustedDiagramPoints, platformShape, updateReading } = useEccentricityCalculation();
  
      return {
        readings,
        averageReading,
        eccentricity,
        eccentricityPercentage,
        adjustedDiagramPoints,
        platformShape,
        updateReading
      };
    }
  };
  </script>
  