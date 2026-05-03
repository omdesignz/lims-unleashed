export function useCfuCalculator({ cfu1, cfu2, d1, d2, volume = 1 }) {
    console.log('Youre Here: ' + cfu1 + ' ' + cfu2 + ' ' + d1 + ' ' + d2 + ' ' + volume);

    const calculateCFU = ({ cfu1, cfu2, d1, d2, volume = 1 }) => {

        console.log(cfu1, cfu2, d1, d2, volume);

      const sumC = cfu1 + cfu2;
      const d = Math.min(d1, d2); // use the lower dilution (it's a must)
      const N = sumC / (volume * 1.1 * d);
      return N;
    };
  
    return {
      calculateCFU
    };
  }