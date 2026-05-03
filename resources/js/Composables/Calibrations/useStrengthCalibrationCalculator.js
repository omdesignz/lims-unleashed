import { ref, computed } from 'vue';

export function useStrengthCalibrationCalculator() {
  // User inputs (initial values can be set to null or empty strings if needed)
  const Nr1 = ref(0);
  const Nr2 = ref(0);
  const Nr3 = ref(0);
  const F1i = ref(0);
  const F2i = ref(0);
  const F3i = ref(0);
  const ResP = ref(0);
  const ResI = ref(0);
  const Fzero = ref(0);
  const Fca = ref(0);
  const Fda = ref(0);
  const K = ref(1);  // Assuming K has a default value of 1 if not provided

  // Calculations
  const FM = computed(() => ((3315.6 * (Nr1.value + Nr2.value + Nr3.value) / 3)) + 1.2166);

  const F1p = computed(() => 3315.6 * Nr1.value + 1.2166);
  const F2p = computed(() => 3315.6 * Nr2.value + 1.2166);
  const F3p = computed(() => 3315.6 * Nr3.value + 1.2166);

  const Fmaxp = computed(() => Math.max(F1p.value, F2p.value, F3p.value));
  const FminP = computed(() => Math.min(F1p.value, F2p.value, F3p.value));
  const Fmaxi = computed(() => Math.max(F1i.value, F2i.value, F3i.value));
  const Fmini = computed(() => Math.min(F1i.value, F2i.value, F3i.value));
  const FmedP = computed(() => (F1p.value + F2p.value + F3p.value) / 3);
  const FmedI = computed(() => (F1i.value + F2i.value + F3i.value) / 3);

  const A = computed(() => (ResP.value !== 0 ? (ResI.value * 100) / ResP.value : 0));
  const UresSquared = computed(() => (A.value ** 2) / 12);

  const B = computed(() => (Fmaxi.value !== 0 ? (Fzero.value * 100) / Fmaxi.value : 0));
  const UzeroSquared = computed(() => (B.value ** 2) / 12);

  const C = computed(() => (FmedI.value !== 0 ? ((Fmaxi.value - Fmini.value) * 100) / FmedI.value : 0));
  const UrepSquared = computed(() => (C.value ** 2) / 8);

  const ErroAB = computed(() => FmedI.value - FmedP.value);
  const ErroRE = computed(() => (FmedP.value !== 0 ? (ErroAB.value * 100) / FmedP.value : 0));
  const UerroRSquared = computed(() => (ErroRE.value ** 2) / 12);

  const D = computed(() => (FmedP.value !== 0 ? ((FmedI.value - FmedP.value) * 100) / FmedP.value : 0));
  const UConplSquared = computed(() => (D.value ** 2) / 8);

  const E = computed(() => (FmedP.value !== 0 ? ((Fca.value - Fda.value) * 100) / FmedP.value : 0));
  const URevSquared = computed(() => (E.value ** 2) / 12);

  const UComb = computed(() => (
    Math.sqrt(UresSquared.value + UzeroSquared.value + UrepSquared.value + UerroRSquared.value + UConplSquared.value)
  ));

  const Veff = computed(() => (
    UComb.value ** 4 * 50 / (
      UresSquared.value ** 4 + UzeroSquared.value ** 4 + UrepSquared.value ** 4 +
      UerroRSquared.value ** 4 + UConplSquared.value ** 4
    )
  ));

  const Iexp = computed(() => UComb.value * K.value);

  return {
    // Input values
    Nr1, Nr2, Nr3,
    F1i, F2i, F3i,
    ResP, ResI,
    Fzero, Fca, Fda, K,

    // Calculated values
    FM,
    F1p, F2p, F3p,
    Fmaxp, FminP, Fmaxi, Fmini,
    FmedP, FmedI,
    A, UresSquared,
    B, UzeroSquared,
    C, UrepSquared,
    ErroAB, ErroRE, UerroRSquared,
    D, UConplSquared,
    E, URevSquared,
    UComb, Veff, Iexp
  };
}
