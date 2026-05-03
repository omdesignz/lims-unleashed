<template>
    <div>
      <table>
        <thead>
          <tr>
            <th></th>
            <th>VConv</th>
            <th>Ind1</th>
            <th>Ind2</th>
            <th>Ind3</th>
            <th>Ind4</th>
            <th>Ind5</th>
            <th>VMedio</th>
            <th>ErroValor</th>
            <th>UmedSquared</th>
            <th>UResSquared</th>
            <th>Upad</th>
            <th>UftSquared</th>
            <th>UComb</th>
            <th>GL</th>
            <th>VEF</th>
            <th>K</th>
            <th>Uexp</th>
            <th>IPad Lav</th>
            <th>IPad Ref</th>
            <th>Resolução</th>
            <th>XMLBV Actual</th>
            <th>XMLBV Anterior</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in rows" :key="index">
            <td><button @click="removeRow(index)">Remove</button></td>
            <td><input v-model.number="row.VConv" type="number" /></td>
            <td><input v-model.number="row.Ind1" type="number" /></td>
            <td><input v-model.number="row.Ind2" type="number" /></td>
            <td><input v-model.number="row.Ind3" type="number" /></td>
            <td><input v-model.number="row.Ind4" type="number" /></td>
            <td><input v-model.number="row.Ind5" type="number" /></td>
            <td>{{ row.VMedio }}</td>
            <td>{{ row.ErroValor }}</td>
            <td>{{ row.UmedSquared }}</td>
            <td>{{ row.UResSquared }}</td>
            <td>{{ row.Upad }}</td>
            <td>{{ row.UftSquared }}</td>
            <td>{{ row.UComb }}</td>
            <td>{{ row.GL }}</td>
            <td>{{ row.VEF }}</td>
            <td>{{ row.K }}</td>
            <td>{{ row.Uexp }}</td>
            <td><input v-model.number="row.IPadcLAV" type="number" /></td>
            <td><input v-model.number="row.IPadcLref" type="number" /></td>
            <td><input v-model.number="row.ResV" type="number" /></td>
            <td><input v-model.number="row.XmlbvActual" type="number" /></td>
            <td><input v-model.number="row.XmlbvAnterior" type="number" /></td>
          </tr>
        </tbody>
      </table>
      <button @click="addRow">Add Row</button>
    </div>
  </template>
  
  <script setup>
  import { useScaleCalibrationCalculator } from '@/Composables/Calibrations/useScaleCalibrationCalculator';

  const { rows, addRow, removeRow } = useScaleCalibrationCalculator();
  
  </script>
  
  <style scoped>
  /* Add your styling here */
  </style>
  