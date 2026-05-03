<template>
  <div>
    <!-- Display a message if no data exists -->
    <div v-if="!worksheets.length || !worksheets[activeSheetIndex]?.data">
      <p>No data to display. Add a new sheet or load saved worksheets.</p>
      <button @click="addWorksheet" class="bg-blue-500 text-white px-4 py-2 rounded">
        Add Worksheet
      </button>
    </div>

    <!-- Display the table when data exists -->
    <table v-else class="table-auto border-collapse border border-gray-300">
      <thead>
        <tr>
          <th v-for="(col, colIndex) in columnCount" :key="colIndex" class="border border-gray-300 px-4 py-2 bg-gray-100">
            {{ String.fromCharCode(65 + colIndex) }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, rowIndex) in rowCount" :key="rowIndex">
          <td v-for="colIndex in columnCount" :key="colIndex" class="border border-gray-300 px-4 py-2">
            <!-- Input for raw data -->
            <input
              type="text"
              class="w-full px-2 py-1 border border-gray-300 rounded"
              :value="worksheets[activeSheetIndex]?.data[rowIndex]?.[colIndex - 1] || ''"
              @input="updateCell(rowIndex, colIndex - 1, $event.target.value)"
            />
            <!-- Display evaluated value -->
            <div class="text-sm text-gray-500">
              {{ getComputedValue(rowIndex, colIndex - 1) }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </template>
  
  
  <script setup>
  import { useDynamicWorksheets } from "@/Composables/useDynamicWorksheets";

  const {
        worksheets,
        addWorksheet,
        updateCell,
        getComputedValue,
      } = useDynamicWorksheets();

  </script>