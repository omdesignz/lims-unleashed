import { ref, computed, reactive } from 'vue';

export function useStrengthCalibrationCalculatorMultiple() {
  // Array to store rows of input and calculated values
  const rows = reactive([]);

  // Function to add a new row with default values
  const addRow = () => {
    const row = {
      Nr1: ref(0),
      Nr2: ref(0),
      Nr3: ref(0),
      F1i: ref(0),
      F2i: ref(0),
      F3i: ref(0),
      ResP: ref(0),
      ResI: ref(0),
      Fzero: ref(0),
      Fca: ref(0),
      Fda: ref(0),
      K: ref(1),

      // Calculated values
      FM: computed(() => ((3315.6 * (row.Nr1.value + row.Nr2.value + row.Nr3.value) / 3)) + 1.2166),
      F1p: computed(() => 3315.6 * row.Nr1.value + 1.2166),
      F2p: computed(() => 3315.6 * row.Nr2.value + 1.2166),
      F3p: computed(() => 3315.6 * row.Nr3.value + 1.2166),
      Fmaxp: computed(() => Math.max(row.F1p.value, row.F2p.value, row.F3p.value)),
      FminP: computed(() => Math.min(row.F1p.value, row.F2p.value, row.F3p.value)),
      Fmaxi: computed(() => Math.max(row.F1i.value, row.F2i.value, row.F3i.value)),
      Fmini: computed(() => Math.min(row.F1i.value, row.F2i.value, row.F3i.value)),
      FmedP: computed(() => (row.F1p.value + row.F2p.value + row.F3p.value) / 3),
      FmedI: computed(() => (row.F1i.value + row.F2i.value + row.F3i.value) / 3),
      A: computed(() => (row.ResP.value !== 0 ? (row.ResI.value * 100) / row.ResP.value : 0)),
      UresSquared: computed(() => (row.A.value ** 2) / 12),
      B: computed(() => (row.Fmaxi.value !== 0 ? (row.Fzero.value * 100) / row.Fmaxi.value : 0)),
      UzeroSquared: computed(() => (row.B.value ** 2) / 12),
      C: computed(() => (row.FmedI.value !== 0 ? ((row.Fmaxi.value - row.Fmini.value) * 100) / row.FmedI.value : 0)),
      UrepSquared: computed(() => (row.C.value ** 2) / 8),
      ErroAB: computed(() => row.FmedI.value - row.FmedP.value),
      ErroRE: computed(() => (row.FmedP.value !== 0 ? (row.ErroAB.value * 100) / row.FmedP.value : 0)),
      UerroRSquared: computed(() => (row.ErroRE.value ** 2) / 12),
      D: computed(() => (row.FmedP.value !== 0 ? ((row.FmedI.value - row.FmedP.value) * 100) / row.FmedP.value : 0)),
      UConplSquared: computed(() => (row.D.value ** 2) / 8),
      E: computed(() => (row.FmedP.value !== 0 ? ((row.Fca.value - row.Fda.value) * 100) / row.FmedP.value : 0)),
      URevSquared: computed(() => (row.E.value ** 2) / 12),
      UComb: computed(() => (
        Math.sqrt(row.UresSquared.value + row.UzeroSquared.value + row.UrepSquared.value + row.UerroRSquared.value + row.UConplSquared.value)
      )),
      Veff: computed(() => (
        row.UComb.value ** 4 * 50 / (
          row.UresSquared.value ** 4 + row.UzeroSquared.value ** 4 + row.UrepSquared.value ** 4 +
          row.UerroRSquared.value ** 4 + row.UConplSquared.value ** 4
        )
      )),
      Iexp: computed(() => row.UComb.value * row.K.value)
    };

    rows.push(row);
  };

  const removeRow = (index) => {
    rows.splice(index, 1);
  };

  // Initialize with one row
  addRow();

  return {
    rows,
    addRow,
    removeRow
  };
}
