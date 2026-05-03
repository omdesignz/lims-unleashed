<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { ResultsDataService } from '@/Services/ResultsDataService.js';
import InsertResultComponent from '@/Components/results/InsertResultComponent.vue';
import VerifyResultComponent from '@/Components/results/VerifyResultComponent.vue';
import ApproveResultComponent from '@/Components/results/ApproveResultComponent.vue';
import CalculationModal from '@/Components/results/CalculationModal.vue';
import axios from 'axios';

defineOptions({
  layout: Layout
});

const props = defineProps({
    action: String,
    record: Object,
    parameters: Array
});

// Reactive state
const showCalculationModal = ref(false);
const calculationParameters = ref([]);
const existingCalculationData = ref({});

// Main form
const form = useForm({
    action: props.action,
    id: props.record?.id,
    cl_id: props.record?.cl_id,
    sample_id: props.record?.sample_id,
    department_id: props.record?.department_id,
    type_id: props.record?.type_id,
    results: []
});

// Computed properties
const workflowComponents = {
    analyze: InsertResultComponent,
    verify: VerifyResultComponent,
    approve: ApproveResultComponent
};

const CurrentComponent = computed(() => workflowComponents[props.action] || InsertResultComponent);

const hasCalculatedParameters = computed(() => {
    return form.results?.some(p => p.requires_calculation && p.active);
});

// Separate results by type for better organization
const separatedResults = computed(() => {
    return ResultsDataService.separateCalculatedParameters(form.results);
});

// Load data
onMounted(async () => {
    await loadResultParameters();
});

async function loadResultParameters() {
    try {
        const response = await axios.get('/results/getDefaultResultsData', {
            params: {
                action: props.action,
                sample_id: props.record?.sample_id?.value
            }
        });
        
        form.results = ResultsDataService.normalizeResults(response.data);
        
        // Prepare calculation data if needed
        if (hasCalculatedParameters.value) {
            calculationParameters.value = separatedResults.value.calculatedParams;
            existingCalculationData.value = ResultsDataService.prepareForCalculation(
                calculationParameters.value,
                form.results
            );
        }
        
    } catch (error) {
        console.error('Error loading results:', error);
        form.results = [];
    }
}

// Open calculation modal
const openCalculationModal = () => {
    calculationParameters.value = separatedResults.value.calculatedParams;
    existingCalculationData.value = ResultsDataService.prepareForCalculation(
        calculationParameters.value,
        form.results,
        props.action
    );
    
    if (calculationParameters.value.length > 0) {
        showCalculationModal.value = true;
    } else {
        alert("Nenhum parâmetro calculado definido para esta amostra.");
    }
};

// Handle calculation results
const handleCalculatedResults = (calculationPayload) => {
    // console.log(calculationPayload);

    form.results = ResultsDataService.mergeCalculationResults(
        form.results,
        calculationPayload
    );
    showCalculationModal.value = false;
};

const handleResultsUpdate = () => {
    // This is triggered when child components update the results
    // You can add any additional logic here if needed
    console.log('Results updated from child component');
};

// Submit results
const submitResults = () => {
    form.post(route('results.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

// Expose to child components
defineExpose({
    form,
    openCalculationModal,
    submitResults,
    hasCalculatedParameters
});
</script>

<template>
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ action === 'analyze' ? 'Inserção de Resultados' : 
                           action === 'verify' ? 'Verificação de Resultados' : 
                           'Validação de Resultados' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ record?.cl_id?.label }} - {{ record?.department_id?.label }}
                    </p>
                </div>
                
                <!-- Calculation Button (only for analyze) -->
                <button v-if="action === 'analyze' && hasCalculatedParameters"
                        @click="openCalculationModal"
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 font-medium">
                    <ClipboardDocumentCheckIcon class="h-5 w-5 mr-2" />
                    Calcular Parâmetros
                </button>
            </div>
        </div>

        <!-- Workflow Component -->
        <component :is="CurrentComponent"
                   :form="form"
                   :record="record"
                   :action="action"
                   :separated-results="separatedResults"
                   @open-calculation="openCalculationModal"
                   @submit="submitResults"
                   @update-results="handleResultsUpdate" />
    </div>

    <!-- Calculation Modal -->
    <CalculationModal
        v-if="showCalculationModal"
        :sample-id="record?.sample_id"
        :parameters="calculationParameters"
        :existing-results="existingCalculationData"
        :action="action"
        @close="showCalculationModal = false"
        @calculated="handleCalculatedResults"
    />
</div>
</template>