<script setup>
import { ref, computed } from 'vue';

// Composable function
function calculateCFU({ cfu1, cfu2, d1, d2, volume = 1 }) {
  const sumC = cfu1 + cfu2;
  const d = Math.min(d1, d2); // Use the lowest dilution factor
  const N = sumC / (volume * 1.1 * d);
  return N;
}

// Reactive state for input rows
const samples = ref([
  { d1: 1e-6, ufc1: 168, d2: 1e-7, ufc2: 14 },
  { d1: 1e-3, ufc1: 192, d2: 1e-2, ufc2: 42 },
]);

// Compute CFU/mL for each sample
const cfuResults = computed(() =>
  samples.value.map(sample => ({
    ...sample,
    cfu: calculateCFU(sample).toExponential(2) // Convert to scientific notation
  }))
);

// Add a new row dynamically
const addSample = () => {
  samples.value.push({ d1: 1e-6, ufc1: 0, d2: 1e-7, ufc2: 0 });
};

// Remove a sample row
const removeSample = (index) => {
  samples.value.splice(index, 1);
};
</script>

<template>
  <div class="container">
    <h2 class="title">Microbial Count Calculator</h2>

    <table>
      <thead>
        <tr>
          <th>D1</th>
          <th>Ufc 1</th>
          <th>D2</th>
          <th>Ufc 2</th>
          <th>Σ ufc</th>
          <th>N (CFU/mL)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(sample, index) in cfuResults" :key="index">
          <td><input type="number" v-model="sample.d1" step="any" /></td>
          <td><input type="number" v-model="sample.ufc1" /></td>
          <td><input type="number" v-model="sample.d2" step="any" /></td>
          <td><input type="number" v-model="sample.ufc2" /></td>
          <td>{{ sample.ufc1 + sample.ufc2 }}</td>
          <td class="result">{{ sample.cfu }}</td>
          <td>
            <button class="delete-btn" @click="removeSample(index)">❌</button>
          </td>
        </tr>
      </tbody>
    </table>

    <button class="add-btn" @click="addSample">➕ Add Sample</button>
  </div>
</template>

<style scoped>
.container {
  max-width: 800px;
  margin: auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}
.title {
  text-align: center;
  margin-bottom: 10px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}
.result {
  font-weight: bold;
  color: #2c3e50;
}
.add-btn, .delete-btn {
  cursor: pointer;
  border: none;
  padding: 5px 10px;
  margin-top: 10px;
  border-radius: 5px;
}
.add-btn {
  background-color: #2ecc71;
  color: white;
}
.delete-btn {
  background-color: #e74c3c;
  color: white;
}
</style>
