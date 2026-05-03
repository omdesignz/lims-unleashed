<script setup>
import { computed } from "vue";
import { TrashIcon, PlusIcon, CalculatorIcon } from "@heroicons/vue/24/outline";
import ResultItem from '@/Components/results/ResultItem.vue';

const props = defineProps({
    form: Object,
    record: Object,
    action: String,
    separatedResults: Object
});

const emit = defineEmits(['open-calculation', 'submit', 'update-results']);

// Group results by type for better UI organization
const groupedResults = computed(() => {
    return {
        calculated: props.separatedResults?.calculatedParams || [],
        inputVariables: props.separatedResults?.inputVariables || [],
        manual: props.separatedResults?.manualParams || []
    };
});

const addResult = () => {
    props.form.results.push({
        parameter_id: '',
        unit_id: '',
        inserted_value: '',
        uncertainty_value: '',
        active: true,
        requires_calculation: false,
    });
};

const removeResult = (index) => {
    props.form.results.splice(index, 1);
};

const handleCalculatedResults = (payload) => {
    // The payload structure is assumed to be:
    // { 
    //   results: { 'CODE_A': value, 'CODE_A_uncertainty_value': uncertainty, ... }, 
    //   overrides: { 'CODE_A': true/false, ... }, 
    //   metadata: { 'CODE_A': { ... calculation_metadata } }, 
    // }
    
    // 1. Iterate over the results in the main form array
    props.form.results.forEach(result => {
        const code = result.parameter_id?.code;

        if (code && payload.results[code] !== undefined) {
            // 2. Update the value and uncertainty for calculated/input variables
            
            // Update the main value (calculated output or input variable)
            result.inserted_value = payload.results[code];

            // Update uncertainty if provided (only for calculated outputs typically)
            const uncertaintyKey = `${code}_uncertainty_value`;
            if (payload.results[uncertaintyKey] !== undefined) {
                result.uncertainty_value = payload.results[uncertaintyKey];
            }

            // 3. Update calculation-specific metadata fields
            
            // ✅ CRITICAL: Add calculation_metadata
            if (payload.metadata[code]) {
                result.calculation_metadata = payload.metadata[code];
            }
            
            // Update manual_override status
            result.is_override = payload.overrides[code] || false;
            
            // Note: This assumes the 'code' is unique and used as the key in the payload.
        }
    });

    console.log('Results updated with calculation metadata:', props.form.results);
    
    // If the parent component needs to know the form has changed, you can emit:
    // emit('update-results'); 
};
</script>

<template>
<div class="space-y-8">
    <!-- Calculated Parameters Section -->
    <div v-if="groupedResults.calculated.length > 0" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-medium text-purple-900">
                    Parâmetros Calculados
                </h3>
                <p class="text-sm text-purple-700">
                    {{ groupedResults.calculated.length }} parâmetro(s) que serão calculados automaticamente
                </p>
            </div>
            <button @click="$emit('open-calculation')"
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                <CalculatorIcon class="h-5 w-5 mr-2" />
                Abrir Calculadora
            </button>
        </div>
        
        <!-- Display calculated parameters (read-only in this view) -->
        <div class="space-y-2">
            <div v-for="(result, index) in groupedResults.calculated" 
                 :key="index"
                 class="flex items-center justify-between p-3 bg-white rounded border border-purple-100">
                <div>
                    <span class="font-medium">{{ result.parameter_id?.code }}</span>
                    <span class="text-sm text-gray-600 ml-2">{{ result.parameter_id?.name }}</span>
                </div>
                <div class="text-right">
                    <div v-if="result.inserted_value" 
                         class="text-green-600 font-medium">
                        {{ result.inserted_value }} {{ result.unit_id?.code }}
                        <span v-if="result.uncertainty_value" 
                            class="text-xs text-gray-500 ml-2">
                            (± {{ result.uncertainty_value }})
                        </span>
                    </div>

                    <div v-else class="text-yellow-600 text-sm">
                        Aguardando cálculo
                    </div>
                    <div v-if="result.manual_override" 
                         class="text-xs text-blue-600">
                        (Sobrescrito manualmente)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Input Variables Section -->
    <div v-if="groupedResults.inputVariables.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-lg font-medium text-blue-900 mb-4">
            Variáveis de Entrada para Cálculo
        </h3>
        <div class="space-y-4">
            <ResultItem
                v-for="(result, index) in groupedResults.inputVariables"
                :key="`input-${index}`"
                :result="result"
                :index="index"
                :form="form"
                :record="record"
                :is-input-variable="true"
                @remove="removeResult"
            />
        </div>
    </div>

    <!-- Manual Parameters Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                Parâmetros Manuais
                <span class="text-sm text-gray-500 ml-2">
                    ({{ groupedResults.manual.length }} parâmetros)
                </span>
            </h3>
        </div>
        
        <div class="p-6 space-y-6">
            <div v-if="groupedResults.manual.length === 0" 
                 class="text-center py-8 text-gray-500">
                Nenhum parâmetro manual definido.
            </div>
            
            <div v-else class="space-y-6">
                <ResultItem
                    v-for="(result, index) in groupedResults.manual"
                    :key="`manual-${index}`"
                    :result="result"
                    :index="form.results.findIndex(r => r.id === result.id || r.parameter_id?.code === result.parameter_id?.code)"
                    :form="form"
                    :record="record"
                    :is-input-variable="false"
                    @remove="removeResult"
                />
            </div>
            
            <!-- Add Result Button -->
            <div class="pt-4 border-t">
                <button @click="addResult"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Adicionar Parâmetro Manual
                </button>
            </div>
        </div>
    </div>

    <!-- Submit Section -->
    <div class="flex justify-end pt-6">
        <button @click="$emit('submit')"
                :disabled="form.processing"
                type="button"
                :class="[
                    'inline-flex justify-center rounded-md px-6 py-3 text-sm font-medium shadow-sm',
                    'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                    action === 'analyze' ? 'bg-blue-600 text-white hover:bg-blue-700' : '',
                    form.processing ? 'opacity-50 cursor-not-allowed' : ''
                ]">
            {{ form.processing ? 'Processando...' : 
               action === 'analyze' ? 'Inserir Resultados' :
               action === 'verify' ? 'Verificar Resultados' :
               'Validar Resultados' }}
        </button>
    </div>
</div>
</template>