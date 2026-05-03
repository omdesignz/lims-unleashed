<template>
    <div>
      <table>
        <tr>
          <th>Input Values</th>
          <th>Calculated Values</th>
        </tr>
        <tr>
          <td>Nr1: <input v-model.number="Nr1" type="number" /></td>
          <td>FM: {{ FM }}</td>
        </tr>
        <tr>
          <td>Nr2: <input v-model.number="Nr2" type="number" /></td>
          <td>F1p: {{ F1p }}</td>
        </tr>
        <tr>
          <td>Nr3: <input v-model.number="Nr3" type="number" /></td>
          <td>F2p: {{ F2p }}</td>
        </tr>
        <tr>
          <td>F1i: <input v-model.number="F1i" type="number" /></td>
          <td>F3p: {{ F3p }}</td>
        </tr>
        <tr>
          <td>F2i: <input v-model.number="F2i" type="number" /></td>
          <td>Fmaxp: {{ Fmaxp }}</td>
        </tr>
        <tr>
          <td>F3i: <input v-model.number="F3i" type="number" /></td>
          <td>FminP: {{ FminP }}</td>
        </tr>
        <!-- Add more rows as needed for each input and calculated value -->
        <tr>
          <td>ResP: <input v-model.number="ResP" type="number" /></td>
          <td>UComb: {{ UComb }}</td>
        </tr>
        <tr>
            <td>FminI</td>
            <td>FminI: {{ Fmini }}</td>
        </tr>  
        <tr>
            <td>FmaxI</td>
            <td>FmaxI: {{ Fmaxi }}</td>
        </tr>
        <tr>
            <td>A</td>
            <td>A: {{ A }}</td>
        </tr>
        <tr>
            <td>Ures</td>
            <td>Ures: {{ UresSquared }}</td>
        </tr>
        <tr>
            <td>B</td>
            <td>B: {{ B }}</td>
        </tr>
        <tr>
            <td>Uzero</td>
            <td>Uzero: {{ UzeroSquared }}</td>
        </tr>
        <tr>
            <td>C</td>
            <td>C: {{ C }}</td>
        </tr>
        <tr>
            <td>Urep</td>
            <td>Urep: {{ UrepSquared }}</td>
        </tr>
        <tr>
            <td>ErroAB</td>
            <td>ErroAB: {{ ErroAB }}</td>
        </tr>
        <tr>
            <td>ErroRE</td>
            <td>ErroRE: {{ ErroRE }}</td>
        </tr>
        <tr>
            <td>UerroRS</td>
            <td>UerroRS: {{ UerroRSquared }}</td>
        </tr>
        <tr>
            <td>D</td>
            <td>D: {{ D }}</td>
        </tr>
        <tr>
            <td>UConpl</td>
            <td>UConpl: {{ UConplSquared }}</td>
        </tr>
        <tr>
            <td>E</td>
            <td>E: {{ E }}</td>
        </tr>
        <tr>
            <td>URev</td>
            <td>URev: {{ URevSquared }}</td>
        </tr>
        <tr>
          <td>ResI: <input v-model.number="ResI" type="number" /></td>
          <td>Veff: {{ Veff }}</td>
        </tr>
        <tr>
          <td>Fzero: <input v-model.number="Fzero" type="number" /></td>
          <td>Iexp: {{ Iexp }}</td>
        </tr>
      </table>
    </div>
  </template>
  
  <script setup>
  import { useStrengthCalibrationCalculator } from '@/Composables/Calibrations/useStrengthCalibrationCalculator';

  const {
        Nr1, Nr2, Nr3, F1i, F2i, F3i, ResP, ResI, Fzero, Fca, Fda, K,
        FM, F1p, F2p, F3p, Fmaxp, FminP, Fmaxi, Fmini,
        FmedP, FmedI, A, UresSquared, B, UzeroSquared, C, UrepSquared,
        ErroAB, ErroRE, UerroRSquared, D, UConplSquared, E, URevSquared,
        UComb, Veff, Iexp
      } = useStrengthCalibrationCalculator();
  
//   export default {
//     setup() {
//       const {
//         Nr1, Nr2, Nr3, F1i, F2i, F3i, ResP, ResI, Fzero, Fca, Fda, K,
//         FM, F1p, F2p, F3p, Fmaxp, FminP, Fmaxi, Fmini,
//         FmedP, FmedI, A, UresSquared, B, UzeroSquared, C, UrepSquared,
//         ErroAB, ErroRE, UerroRSquared, D, UConplSquared, E, URevSquared,
//         UComb, Veff, Iexp
//       } = useCalibrationCalculator();
  
//       return {
//         Nr1, Nr2, Nr3, F1i, F2i, F3i, ResP, ResI, Fzero, Fca, Fda, K,
//         FM, F1p, F2p, F3p, Fmaxp, FminP, Fmaxi, Fmini,
//         FmedP, FmedI, A, UresSquared, B, UzeroSquared, C, UrepSquared,
//         ErroAB, ErroRE, UerroRSquared, D, UConplSquared, E, URevSquared,
//         UComb, Veff, Iexp
//       };
//     }
//   };
  </script>
  