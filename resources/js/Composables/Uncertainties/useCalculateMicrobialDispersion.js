export function calculateMicrobialDispersion({ sumC, volume, n1, n2, dilution }) {
    if (!sumC || !volume || !n1 || !n2 || !dilution) {
      return 'Valores em Falta';
    }
  
    const parsedDilution = parseFloat(dilution);
    if (isNaN(parsedDilution)) {
      return 'Diluição Inválida';
    }
  
    const B = volume * (n1 + 0.1 * n2);  // B = V * (n1 + 0.1 * n2)
    const delta = (sumC / B) * (1 / parsedDilution);  // δ = (ΣC / B) * (1 / d)
  
    return delta.toFixed(4);  // Return result rounded to 4 decimal places
  }