import { ref } from 'vue';

export function useUncertaintyCalculator() {
  // Main function to calculate uncertainties
  const calculateUncertainty = (series1, series2, constantDvar, constantUncertainty) => {
    if (series1.length !== series2.length) {
      throw new Error('Both series must have the same number of records.');
    }

    const logSeries1 = series1.map(value => Math.log10(value));
    const logSeries2 = series2.map(value => Math.log10(value));

    // Step 3: Calculate reproducibility variance for each row and then take the average
    const reproducibilityVariances = logSeries1.map((logVal1, index) => {
      const logVal2 = logSeries2[index];
      return ((logVal1 - logVal2) ** 2) / 2;
    });
    const avgReproducibilityVariance = reproducibilityVariances.reduce((sum, val) => sum + val, 0) / reproducibilityVariances.length;

    // Step 4: Calculate average of each data row
    const rowAverages = series1.map((val1, index) => {
      const val2 = series2[index];
      return (val1 + val2) / 2;
    });
    const avgRowAverage = rowAverages.reduce((sum, val) => sum + val, 0) / rowAverages.length;

    // Step 5: Calculate dvar for each row
    const dvars = rowAverages.map(avg => constantDvar / avg);

    // Step 6: Calculate the average of all dvars
    const avgDvar = dvars.reduce((sum, val) => sum + val, 0) / dvars.length;

    // Step 7: Calculate ovar for each row
    const ovars = reproducibilityVariances.map((reproVar, index) => reproVar - dvars[index]);

    // Step 8: Calculate the average of all ovars
    const avgOvar = ovars.reduce((sum, val) => sum + val, 0) / ovars.length;

    // Step 9: Calculate the estimated operational uncertainty
    const estimatedOperationalUncertainty = constantUncertainty * avgOvar;

    // Step 10: Calculate the relative operational uncertainty
    const relativeOperationalUncertainty = Math.sqrt(estimatedOperationalUncertainty);

    // Step 11: Show the relative operational uncertainty as a percentage
    const relativeOperationalUncertaintyPercentage = relativeOperationalUncertainty * 100;

    return {
      avgReproducibilityVariance,
      avgRowAverage,
      avgDvar,
      ovars,
      avgOvar,
      estimatedOperationalUncertainty,
      relativeOperationalUncertainty,
      relativeOperationalUncertaintyPercentage, // The percentage value calculated in step 11
    };
  };

  return {
    calculateUncertainty,
  };
}
