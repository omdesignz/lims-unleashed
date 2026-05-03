import { ref, reactive, computed } from 'vue';

// Função para calcular o desvio padrão amostral
function desvioPadraoAmostral(array) {
  const n = array.length;
  const media = array.reduce((acc, val) => acc + val, 0) / n;
  const somaQuadrados = array.reduce((acc, val) => acc + Math.pow(val - media, 2), 0);
  return Math.sqrt(somaQuadrados / (n - 1));
}

// Função para contar valores não vazios (equivalente ao CONT.VALORES no Excel)
function contarValores(array) {
  return array.filter(val => val !== null && val !== undefined && val !== "").length;
}

// Função que implementa a fórmula original
function calcularFormula(array) {
  const desvioPadrao = desvioPadraoAmostral(array);
  const contagem = contarValores(array);
  return Math.pow(desvioPadrao, 2) / contagem;
}

// Get the nearest K value

function getKValue(degreeOfFreedom) {
  // Table data for effective degrees of freedom and their corresponding K values
  const kValuesTable = [
    { degree: 1, k: 13.97 },
    { degree: 2, k: 4.53 },
    { degree: 3, k: 3.31 },
    { degree: 4, k: 2.87 },
    { degree: 5, k: 2.65 },
    { degree: 6, k: 2.52 },
    { degree: 7, k: 2.43 },
    { degree: 8, k: 2.37 },
    { degree: 10, k: 2.28 },
    { degree: 20, k: 2.13 },
    { degree: 50, k: 2.05 },
    { degree: Infinity, k: 2 }
  ];

  // Find the nearest degree of freedom in the table. Anything above 50 is considered infinite.
  let nearest = kValuesTable.reduce((prev, curr) => {
    return (Math.abs(curr.degree - degreeOfFreedom) < Math.abs(prev.degree - degreeOfFreedom) ? curr : prev);
  });

  // Return the corresponding K value
  return degreeOfFreedom <= 50 ? nearest.k : kValuesTable.find(item => item.degree === Infinity).k;
}

export function useScaleCalibrationCalculator() {
  const rows = reactive([]);

    // Function to add a new row with default values
    const addRow = () => {
      const row = {
        VConv: ref(0),
        Ind1: ref(0),
        Ind2: ref(0),
        Ind3: ref(0),
        Ind4: ref(0),
        Ind5: ref(0),
        UpLref: ref(0),
        ResV: ref(0),
        IPadcLAV: ref(0),
        IPadcLref: ref(0),
        K1: ref(2),
        K2: ref(2),
        XmlbvActual: ref(0),
        XmlbvAnterior: ref(0),
        UImpuxo: ref(0),
        UdesvSquared: ref(0),
        // K: ref(2),
  
        // Calculated values
        VMedio: computed(() => ((row.Ind1.value + row.Ind2.value + row.Ind3.value + row.Ind4.value + row.Ind5.value)) / 5),
        ErroValor: computed(() => {
          return row.VMedio.value - row.VConv.value;
        }),
        UmedSquared: computed(() => {
          // Calculate standard deviation squared of the values Ind1, Ind2, Ind3, Ind4, Ind5
          const values = [row.Ind1.value, row.Ind2.value, row.Ind3.value, row.Ind4.value, row.Ind5.value];
          // const mean = values.reduce((a, b) => a + b, 0) / values.length;
          // const variance = values.reduce((sum, value) => sum + Math.pow(value - mean, 2), 0) / values.length;

          // return ((variance / values.length) ** 2) / values.length;

          return calcularFormula(values) || 0;

        }),
        UResSquared: computed(() => {
          return Math.pow(row.ResV.value, 2) / 12;
        }),
        Upad: computed(() => {
          return row.IPadcLAV.value / row.K1.value;
        }),
        UftSquared: computed(() => {
          return Math.pow(row.XmlbvActual.value - row.XmlbvAnterior.value, 2) / 18;
        }),
        UComb: computed(() => {
          return (Math.sqrt(
            Math.pow(row.UpLref.value, 2) +
            row.UdesvSquared.value +
            row.UmedSquared.value +
            row.UResSquared.value +
            Math.pow(row.Upad.value, 2) +
            row.UftSquared.value
          )) || 0;
        }),
        GL: computed(() => {
          const values = [row.Ind1.value, row.Ind2.value, row.Ind3.value, row.Ind4.value, row.Ind5.value];

          return values.length - 1;
        }),
        VEF: computed(() => {
          // return ((Math.pow((row.UComb.value / row.UmedSquared.value), 4)) * row.GL.value) || 0;
          return ((Math.pow(row.UComb.value, 4) / Math.pow(row.UmedSquared.value, 2)) * row.GL.value) || 0;
        }),
        K: computed(() => {
          return getKValue(row.VEF.value);
        }),
        Uexp: computed(() => {
          return row.UComb.value * row.K.value;
        })
  
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