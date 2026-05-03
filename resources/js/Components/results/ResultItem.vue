<script setup>
import { computed } from "vue";
import Combobox from '@/Components/combobox.vue';
import { TrashIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    result: Object,
    index: Number,
    form: Object,
    record: Object,
    isInputVariable: Boolean,
    isReadOnly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['remove', 'update']);

// Load functions (you'll need to import these or pass them as props)
function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
            results.map(result => ({
                value: result.id,
                label: result.code,
                name: result.name,
                code: result.code,
                requires_calculation: result.requires_calculation
            }))
        );
    });
}

function loadUnits(query, setOptions) {
    fetch('/units/getUnit?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
            results.map(result => ({
                value: result.id,
                label: result.code,
                name: result.name
            }))
        );
    });
}

// Determine if result is out of range
const isOutOfRange = computed(() => {
    const value = parseFloat(props.result.inserted_value);
    if (isNaN(value)) return false;
    
    if (props.result.min_ref_value !== null && value < props.result.min_ref_value) return true;
    if (props.result.max_ref_value !== null && value > props.result.max_ref_value) return true;
    
    return false;
});

// Handle value change
const handleValueChange = (value) => {
    props.result.inserted_value = value;
    emit('update', props.index, props.result);
};

// Handle parameter selection
const handleParameterSelect = (selected) => {
    props.result.parameter_id = selected;
    
    // You might want to auto-fill unit or other fields based on parameter
    if (selected && selected.unit_id) {
        props.result.unit_id = selected.unit_id;
    }
    
    emit('update', props.index, props.result);
};
</script>

<template>
<div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
     :class="{
         'bg-blue-50 border-blue-200': isInputVariable,
         'bg-red-50 border-red-200': isOutOfRange && !isInputVariable,
         'bg-gray-50': isReadOnly
     }">
    <div class="flex items-start justify-between mb-3">
        <div class="flex items-center">
            <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-sm font-medium text-gray-700">
                {{ index + 1 }}
            </div>
            <span v-if="isInputVariable" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                Variável de Entrada
            </span>
            <span v-if="result.requires_calculation" class="ml-2 px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">
                Calculado
            </span>
        </div>
        
        <button v-if="!isReadOnly" 
                @click="$emit('remove', index)"
                class="text-gray-400 hover:text-red-500 transition-colors">
            <TrashIcon class="h-5 w-5" />
        </button>
    </div>

    <!-- Parameter Selection -->
    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Parâmetro
        </label>
        <Combobox v-if="!isReadOnly"
                  :hasError="form.errors[`results.${index}.parameter_id`]"
                  v-model="result.parameter_id"
                  :load-options="loadParameters"
                  @update:model-value="handleParameterSelect"
                  :disabled="result.requires_calculation"
                  :placeholder="isInputVariable ? 'Seleccione a variável de entrada' : 'Seleccione o parâmetro'"
        />
        <div v-else class="p-2 bg-gray-100 rounded text-sm">
            {{ result.parameter_id?.code }} - {{ result.parameter_id?.name }}
        </div>
        <p v-if="form.errors[`results.${index}.parameter_id`]" 
           class="mt-1 text-xs text-red-600">
            {{ form.errors[`results.${index}.parameter_id`] }}
        </p>
    </div>

    <!-- Unit Selection -->
    <div class="mb-3" v-if="!isInputVariable && !result.requires_calculation">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Unidade
        </label>
        <Combobox v-if="!isReadOnly"
                  :hasError="form.errors[`results.${index}.unit_id`]"
                  v-model="result.unit_id"
                  :load-options="loadUnits"
                  placeholder="Seleccione a unidade"
        />
        <div v-else class="p-2 bg-gray-100 rounded text-sm">
            {{ result.unit_id?.code || '-' }}
        </div>
    </div>

    <!-- Value Input -->
    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Resultado
            <span v-if="isInputVariable" class="text-xs text-blue-600 ml-1">
                (Variável para cálculo)
            </span>
            <span v-if="result.requires_calculation" class="text-xs text-purple-600 ml-1">
                (Calculado automaticamente)
            </span>
        </label>
        
        <div v-if="!isReadOnly">
            <input 
                v-model="result.inserted_value"
                @input="handleValueChange($event.target.value)"
                type="text"
                :disabled="result.requires_calculation"
                :class="[
                    'block w-full rounded-md border-0 py-2 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-sm',
                    'disabled:bg-gray-100 disabled:text-gray-500 disabled:ring-gray-200',
                    isOutOfRange ? 'ring-red-300 text-red-900 placeholder-red-300 focus:ring-red-500' :
                    isInputVariable ? 'ring-blue-300 focus:ring-blue-500' :
                    'ring-gray-300 focus:ring-blue-500'
                ]"
                :placeholder="result.requires_calculation ? 'Será calculado automaticamente' : 'Introduza o resultado'"
            />
            
            <!-- Reference Range Warning -->
            <div v-if="isOutOfRange" class="mt-1 text-xs text-red-600 flex items-center">
                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Fora do intervalo de referência
            </div>
            
            <!-- Reference Range Display -->
            <div v-if="!isInputVariable && (result.min_ref_value || result.max_ref_value)"
                 class="mt-1 text-xs text-gray-500">
                Referência: 
                <span v-if="result.min_ref_value && result.max_ref_value">
                    {{ result.min_ref_value }} - {{ result.max_ref_value }} {{ result.unit_id?.code }}
                </span>
                <span v-else-if="result.min_ref_value">
                    ≥ {{ result.min_ref_value }} {{ result.unit_id?.code }}
                </span>
                <span v-else-if="result.max_ref_value">
                    ≤ {{ result.max_ref_value }} {{ result.unit_id?.code }}
                </span>
            </div>
        </div>
        
        <div v-else class="p-2 bg-gray-100 rounded text-sm font-medium"
             :class="{ 'text-red-600': isOutOfRange }">
            {{ result.inserted_value || '-' }} {{ result.unit_id?.code }} 
        </div>
    </div>

    <!-- Uncertainty Input -->

    <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1" :for="`item-${index}-error`">Incerteza</label>

            <input
                v-model="result.uncertainty_value"
                type="text"
                :name="`item-${index}-error`"
                :id="`item-${index}-error`"
                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
            
       
    </div>

    <!-- Error Display -->
    <div v-if="form.errors[`results.${index}.inserted_value`]" 
         class="mt-2 text-xs text-red-600">
        {{ form.errors[`results.${index}.inserted_value`] }}
    </div>
</div>
</template>
