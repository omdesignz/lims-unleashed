<script setup>
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
</script>

<template>
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="max-h-[90vh] w-full max-w-6xl overflow-auto rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_30px_120px_rgba(7,17,15,0.45)] dark:border-[#25443c] dark:bg-[#07110f]">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between gap-4 border-b border-[#ded3bf] pb-4 dark:border-[#25443c]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">Cálculo técnico</p>
                    <h3 class="mt-1 text-xl font-bold text-[#17231f] dark:text-[#f7f1e7]">Calculadora de Parâmetros</h3>
                </div>
                <button @click="$emit('close')" 
                        class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
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
