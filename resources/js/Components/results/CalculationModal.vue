<script setup>
import { ref, watch, onMounted } from "vue";
import CalculationResultEntry from '@/Components/results/CalculationResultEntry.vue';

const props = defineProps({
    sampleId: Object,
    parameters: Array,
    existingResults: Object,
    action: String
});

const emit = defineEmits(['close', 'calculated']);

// Handle the comprehensive payload from CalculationResultEntry
// const handleCalculatedResults = (comprehensivePayload) => {

//     // console.log(comprehensivePayload);

//     emit('calculated', comprehensivePayload);
//     emit('close');
// };

const handleCalculatedResults = (comprehensivePayload) => {
    console.log('CalculationModal received results:', comprehensivePayload)
    
    // Add action to payload if not present
    if (!comprehensivePayload.action) {
        comprehensivePayload.action = props.action
    }
    
    emit('calculated', comprehensivePayload);
    
    // Don't close immediately - let the parent decide
    // The parent should close after processing
};

const closeModal = () => {
    emit('close');
};

onMounted(() => {
  console.log('CalculationModal mounted with action:', props.action);
  console.log('Parameters:', props.parameters);
  console.log('Existing results:', props.existingResults);
});
</script>

<template>
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Calculadora de Parâmetros</h3>
                <button @click="$emit('close')" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <CalculationResultEntry
                :sample-id="sampleId"
                :parameters="parameters"
                :existing-results="existingResults"
                @calculated-results="handleCalculatedResults"
                @close="closeModal"
                :action="action"
            />
        </div>
    </div>
</div>
</template>