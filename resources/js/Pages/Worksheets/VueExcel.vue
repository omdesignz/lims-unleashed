// Spreadsheet Component
<template>
  <div class="min-h-screen bg-gray-100 p-4">
    <div class="overflow-auto bg-white shadow-lg rounded-md">
      <div class="mb-4 flex justify-between items-center">
        <button @click="undo" class="bg-blue-500 text-white px-4 py-2 rounded">Undo</button>
        <button @click="redo" class="bg-green-500 text-white px-4 py-2 rounded">Redo</button>
      </div>
      <table class="border-collapse border border-gray-300 w-full">
        <thead>
          <tr>
            <th class="border border-gray-300 bg-gray-200 p-2">&nbsp;</th>
            <th v-for="col in columns" :key="col" class="border border-gray-300 bg-gray-200 p-2 text-center">
              {{ col }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
            <td class="border border-gray-300 bg-gray-200 p-2 text-center">{{ rowIndex + 1 }}</td>
            <td v-for="(col, colIndex) in columns" :key="col" class="border border-gray-300 p-2">
              <div class="relative">
                <input
                  type="text"
                  class="w-full h-full bg-transparent border-none focus:ring-0"
                  v-model="grid[rowIndex][colIndex]"
                  @focus="onCellFocus()"
                  @blur="onCellBlur(rowIndex, colIndex)"
                  @keydown.enter="evaluateFormula(rowIndex, colIndex)"
                  :data-cell="`${rowIndex},${colIndex}`"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { reactive, ref } from 'vue';
import HyperFormula from 'hyperformula';

export default {
  name: 'Spreadsheet',
  setup() {
    const rows = 20;
    const columns = Array.from({ length: 10 }, (_, i) => String.fromCharCode(65 + i));

    const grid = reactive(Array.from({ length: rows }, () => Array(columns.length).fill('')));
    const history = ref([]);
    const future = ref([]);

    const hfInstance = HyperFormula.buildEmpty({
      licenseKey: 'gpl-v3',
    });

    // Ensure the "Sheet1" exists during initialization
        const sheetName = 'Sheet1';
        let sheetId = hfInstance.doesSheetExist(sheetName)
        ? hfInstance.getSheetId(sheetName)
        : hfInstance.addSheet(sheetName);

        const ensureSheetExists = () => {
        if (!hfInstance.doesSheetExist(sheetName)) {
            sheetId = hfInstance.addSheet(sheetName);
        } else {
            sheetId = hfInstance.getSheetId(sheetName);
        }
        };

        // Synchronize the initial grid data with HyperFormula
        Array.from({ length: rows }).forEach((_, rowIndex) => {
        columns.forEach((_, colIndex) => {
            const cellAddress = { sheet: sheetId, col: colIndex, row: rowIndex };
            const cellValue = grid[rowIndex][colIndex];
            if (cellValue) {
            try {
                hfInstance.setCellContents(cellAddress, cellValue);
            } catch (error) {
                console.error(`Error syncing cell (${rowIndex}, ${colIndex}) with HyperFormula:`, error.message);
            }
            }
        });
        });

    const persistData = async () => {
      const response = await fetch('/api/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(grid),
      });
      return response.json();
    };

    // const onCellFocus = () => {
    //   if (!hfInstance.doesSheetExist(sheetName)) {
    //     sheetId = hfInstance.addSheet(sheetName);
    //   }
    // };

    const onCellFocus = () => {
  ensureSheetExists(); // Make sure the sheet exists on focus
};

const syncGridWithHyperFormula = () => {
  if (!hfInstance.doesSheetExist(sheetName)) {
    sheetId = hfInstance.addSheet(sheetName);
  }

  try {
    const data = grid.map(row => row.map(cell => cell || null));
    hfInstance.setSheetContent(sheetId, data);
  } catch (error) {
    console.error('Error syncing grid with HyperFormula:', error.message);
  }
};

const evaluateFormula = (rowIndex, colIndex) => {
  const rawValue = grid[rowIndex][colIndex]?.trim() || '';
  const cellAddress = { sheet: sheetId, col: colIndex, row: rowIndex };

  if (!hfInstance.doesSheetExist(sheetName)) {
    sheetId = hfInstance.addSheet(sheetName);
  }

  try {
    if (rawValue.startsWith('=')) {
      hfInstance.setCellContents(cellAddress, rawValue);
      const evaluatedValue = hfInstance.getCellValue(cellAddress);
      grid[rowIndex][colIndex] = evaluatedValue || '';
    } else if (rawValue === '') {
      hfInstance.setCellContents(cellAddress, null);
    } else {
      hfInstance.setCellContents(cellAddress, rawValue);
    }
  } catch (error) {
    console.error(`Error syncing cell (${rowIndex}, ${colIndex}) with HyperFormula:`, error.message);
  }
};

const syncCellWithHyperFormula = (rowIndex, colIndex) => {
  const value = grid[rowIndex][colIndex];
  const cellAddress = { sheet: sheetId, col: colIndex, row: rowIndex };

  try {
    hfInstance.setCellContents(cellAddress, value ? [value] : null); // Use `null` to clear the cell if empty
  } catch (error) {
    console.error('Error syncing cell with HyperFormula:', error.message);
  }
};

const onCellBlur = (rowIndex, colIndex) => {
  syncCellWithHyperFormula(rowIndex, colIndex);
};

    const undo = () => {
      if (history.value.length > 0) {
        future.value.push(JSON.parse(JSON.stringify(grid)));
        const previousState = history.value.pop();
        Object.assign(grid, previousState);
      }
    };

    const redo = () => {
      if (future.value.length > 0) {
        history.value.push(JSON.parse(JSON.stringify(grid)));
        const nextState = future.value.pop();
        Object.assign(grid, nextState);
      }
    };

    return {
      columns,
      rows: Array.from({ length: rows }),
      grid,
      history,
      future,
      onCellFocus,
      onCellBlur,
      evaluateFormula,
      undo,
      redo,
    };
  },
};
</script>

<style scoped>
input:focus {
  outline: 2px solid #2563eb;
}
</style>
