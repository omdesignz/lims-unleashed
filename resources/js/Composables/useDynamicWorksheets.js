import { ref, computed, onMounted, onUnmounted } from "vue";
import { HyperFormula } from "hyperformula";

export function useDynamicWorksheets() {
  const worksheets = ref([{ name: "Sheet1", data: [["", "", ""], ["", "", ""]] }]);
  const activeSheetIndex = ref(0); // Default active sheet index
  let hfInstance = null;

  // const initializeHF = async () => {
  //   try {
  //     hfInstance = await HyperFormula.buildEmpty({
  //       licenseKey: 'gpl-v3',
  //     });
  //     hfInstance.addSheet(worksheets.value[0].name);
  //     updateSheetDataInHF();
  //     console.log("HyperFormula instance initialized");
  //   } catch (error) {
  //     console.error("Error initializing HyperFormula:", error);
  //   }
  // };

  const initializeHF = async (savedWorksheets = []) => {
    try {
      hfInstance = await HyperFormula.buildEmpty({
        licenseKey: 'gpl-v3',
      });
  
      if (savedWorksheets.length > 0) {
        worksheets.value = savedWorksheets;
        savedWorksheets.forEach((sheet) => {
          const sheetId = hfInstance.addSheet(sheet.name);
          hfInstance.setSheetContent(sheetId, sheet.data || []);
        });
      } else {
        // Add a default sheet
        const defaultSheetName = "Sheet1";
        const sheetId = hfInstance.addSheet(defaultSheetName);
        worksheets.value.push({
          name: defaultSheetName,
          data: [[""]], // Initialize with one empty row and column
        });
      }
  
      activeSheetIndex.value = 0; // Default to the first sheet
      console.log("HyperFormula initialized:", hfInstance.getSheetNames());
    } catch (error) {
      console.error("Error during HyperFormula initialization:", error);
    }
  };

  const updateSheetDataInHF = () => {
    worksheets.value.forEach((sheet) => {
      const sheetId = hfInstance.getSheetId(sheet.name);
      if (sheetId === undefined) {
        hfInstance.addSheet(sheet.name);
      } else {
        hfInstance.setSheetContent(sheetId, sheet.data);
      }
    });
  };

  // const addSheet = () => {
  //   const newSheetName = `Sheet${worksheets.value.length + 1}`;
  //   worksheets.value.push({ name: newSheetName, data: [["", "", ""]] });
  //   if (hfInstance) {
  //     hfInstance.addSheet(newSheetName);
  //   }
  // };

  const addSheet = (sheetName) => {
    if (!hfInstance) {
      console.error("Cannot add sheet. HyperFormula instance is null.");
      return;
    }
  
    const sheetId = hfInstance.addSheet(sheetName);
    console.log(`Added sheet: ${sheetName}, ID: ${sheetId}`);
  };

  const addWorksheet = () => {
    console.log("Adding new worksheet");
    const newSheetName = `Sheet${worksheets.value.length + 1}`;
    const sheetId = hfInstance.addSheet(newSheetName);
  
    worksheets.value.push({
      name: newSheetName,
      data: [[""]], // Start with an empty row and column
    });
  
    activeSheetIndex.value = worksheets.value.length - 1; // Switch to the new sheet
  };

  // const updateCell = (row, col, value) => {
  //   const currentSheetName = worksheets.value[activeSheetIndex.value].name;
  //   const sheetId = hfInstance.getSheetId(currentSheetName);
  
  //   if (sheetId !== undefined) {
  //     hfInstance.setCellContents({ sheet: sheetId, col, row }, [[value || ""]]);
  //     worksheets.value[activeSheetIndex.value].data[row][col] = value;
  //   }
  // };

  const updateCell = (row, col, value) => {
    if (!hfInstance) {
      console.error("Cannot update cell. HyperFormula instance is null.");
      return;
    }
  
    const currentSheetName = worksheets.value[activeSheetIndex.value]?.name;
    if (!currentSheetName) {
      console.error("Cannot update cell. Current sheet name is invalid.");
      return;
    }
  
    const sheetId = hfInstance.getSheetId(currentSheetName);
    if (sheetId === undefined) {
      console.error(`Cannot update cell. Sheet ID not found for ${currentSheetName}.`);
      return;
    }
  
    hfInstance.setCellContents({ sheet: sheetId, col, row }, [[value || ""]]);
    worksheets.value[activeSheetIndex.value].data[row][col] = value;
    console.log(`Cell updated at row ${row}, col ${col} with value "${value}".`);
  };
  
  // const getComputedValue = (row, col) => {
  //   const currentSheetName = worksheets.value[activeSheetIndex.value].name;
  //   const sheetId = hfInstance.getSheetId(currentSheetName);
  
  //   if (sheetId !== undefined) {
  //     const cellValue = hfInstance.getCellValue({ sheet: sheetId, col, row });
  //     if (cellValue instanceof Error) {
  //       return `Error: ${cellValue.message}`;
  //     }
  //     return cellValue || "";
  //   }
  
  //   return "";
  // };

  const getComputedValue = (row, col) => {
    if (!hfInstance) {
      console.error("HyperFormula instance is null in getComputedValue.");
      return "Error: HyperFormula not initialized.";
    }
  
    const currentSheetName = worksheets.value[activeSheetIndex.value]?.name;
    if (!currentSheetName) {
      console.error("Current sheet name is undefined or invalid.");
      return "Error: No active sheet.";
    }
  
    const sheetId = hfInstance.getSheetId(currentSheetName);
    if (sheetId === undefined) {
      console.error(`Sheet ID not found for sheet name: ${currentSheetName}`);
      return `Error: Sheet '${currentSheetName}' does not exist.`;
    }
  
    try {
      const cellValue = hfInstance.getCellValue({ sheet: sheetId, col, row });
      return cellValue !== null ? cellValue : "";
    } catch (error) {
      console.error("Error in getComputedValue:", error);
      return `Error: ${error.message}`;
    }
  };

  const rowCount = computed(() => {
    return worksheets.value[activeSheetIndex]?.data?.length || 0;
  });
  
  const columnCount = computed(() => {
    const rows = worksheets.value[activeSheetIndex]?.data || [];
    return Math.max(...rows.map((row) => row.length), 0);
  });

  onMounted(() => initializeHF());
  onUnmounted(() => {
    if (hfInstance) {
      hfInstance.destroy();
    }
  });

  return {
    worksheets,
    activeSheetIndex,
    addSheet,
    addWorksheet,
    updateCell,
    getComputedValue,
  };
}