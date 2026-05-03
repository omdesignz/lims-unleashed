<template>
    <div>
      <button @click="addRow">Add Row</button>
      <table>
        <thead>
          <tr>
            <th></th>
            <th>Nr1</th>
            <th>Nr2</th>
            <th>Nr3</th>
            <th>FM</th>
            <th>F1p</th>
            <th>F2p</th>
            <th>F3p</th>
            <th>F1i</th>
            <th>F2i</th>
            <th>F3i</th>
            <th>FmaxP</th>
            <th>FminP</th>
            <th>FmaxI</th>
            <th>FminI</th>
            <th>FmedP</th>
            <th>FmedI</th>
            <th>ResP</th>
            <th>ResI</th>
            <th>A</th>
            <th>Ures</th>
            <th>Fzero</th>
            <th>B</th>
            <th>Uzero</th>
            <th>C</th>
            <th>Urep</th>
            <th>ErroAB</th>
            <th>ErroRE</th>
            <th>UerroRS</th>
            <th>D</th>
            <th>UConpl</th>
            <th>E</th>
            <th>URev</th>
            <th>UComb</th>
            <th>Veff</th>
            <th>Fca</th>
            <th>Fda</th>
            <th>Iexp</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in rows" :key="index">
            <td><button @click="removeRow(index)">Remove</button></td>
            <td><input v-model.number="row.Nr1" type="number" /></td>
            <td><input v-model.number="row.Nr2" type="number" /></td>
            <td><input v-model.number="row.Nr3" type="number" /></td>
            <td>{{ row.FM }}</td>
            <td>{{ row.F1p }}</td>
            <td>{{ row.F2p }}</td>
            <td>{{ row.F3p }}</td>
            <td><input v-model.number="row.F1i" type="number" /></td>
            <td><input v-model.number="row.F2i" type="number" /></td>
            <td><input v-model.number="row.F3i" type="number" /></td>
            <td>{{ row.Fmaxp }}</td>
            <td>{{ row.FminP }}</td>
            <td>{{ row.Fmaxi }}</td>
            <td>{{ row.Fmini }}</td>
            <td>{{ row.FmedP }}</td>
            <td>{{ row.FmedI }}</td>
            <td><input v-model.number="row.ResP" type="number" /></td>
            <td><input v-model.number="row.ResI" type="number" /></td>
            <td>{{ row.A }}</td>
            <td>{{ row.UresSquared }}</td>
            <td><input v-model.number="row.Fzero" type="number" /></td>
            <td>{{ row.B }}</td>
            <td>{{ row.UzeroSquared }}</td>
            <td>{{ row.C }}</td>
            <td>{{ row.UrepSquared }}</td>
            <td>{{ row.ErroAB }}</td>
            <td>{{ row.ErroRE }}</td>
            <td>{{ row.UerroRSquared }}</td>
            <td>{{ row.D }}</td>
            <td>{{ row.UConplSquared }}</td>
            <td>{{ row.E }}</td>
            <td>{{ row.URevSquared }}</td>
            <td>{{ row.UComb }}</td>
            <td>{{ row.Veff }}</td>
            <td><input v-model.number="row.Fca" type="number" /></td>
            <td><input v-model.number="row.Fda" type="number" /></td>
            <td>{{ row.Iexp }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </template>
  
  <script>
  import { useStrengthCalibrationCalculatorMultiple } from '@/Composables/Calibrations/useStrengthCalibrationCalculatorMultiple';
  
  export default {
    setup() {
      const { rows, addRow, removeRow } = useStrengthCalibrationCalculatorMultiple();
  
      return {
        rows,
        addRow,
        removeRow
      };
    }
  };
  </script>
  