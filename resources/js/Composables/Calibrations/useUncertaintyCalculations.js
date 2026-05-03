// src/composables/useUncertaintyCalculation.js
import { ref, computed } from 'vue';

export function useUncertaintyCalculation() {
    // Reactive state for the input values
    const nominalValue = ref('');
    const conversion = ref('');
    const uncertainty = ref('');

    // Function to handle the conversion of units (to convert mg to g, etc.)
    const calculateConvertedValue = computed(() => {
        const nominal = parseFloat(nominalValue.value);
        let conversionValue = parseFloat(conversion.value);
        let conversionUnit = conversion.value.replace(/[0-9.\-]/g, '').trim();
        let nominalUnit = nominalValue.value.replace(/[0-9.\-]/g, '').trim();

        // Convert units if needed
        if (nominalUnit === 'kg' && conversionUnit === 'mg') {
            conversionValue /= 1000000; // Convert mg to kg (divide by 1,000,000)
        } else if (nominalUnit === 'g' && conversionUnit === 'mg') {
            conversionValue /= 1000; // Convert mg to g (divide by 1,000)
        } else if (nominalUnit === 'g' && conversionUnit === 'kg') {
            conversionValue *= 1000; // Convert kg to g (multiply by 1,000)
        }

        return nominal + conversionValue;
    });

    // Handle uncertainty, assuming it's provided directly
    const calculatedUncertainty = computed(() => {
        const uncertaintyValue = parseFloat(uncertainty.value.replace(/[^\d.\-]/g, ''));
        return uncertaintyValue;
    });

    return {
        nominalValue,
        conversion,
        uncertainty,
        calculateConvertedValue,
        calculatedUncertainty
    };
}
