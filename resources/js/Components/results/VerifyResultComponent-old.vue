<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { CheckIcon, XMarkIcon, DocumentMagnifyingGlassIcon, PencilIcon, CalculatorIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    form: Object,
    record: Object,
    action: String,
    separatedResults: Object
});

const emit = defineEmits(['open-calculation', 'submit']);

// State management
const editingResults = ref({}); // Tracks which results are in edit mode: { resultId: true/false }
const editedValues = ref({}); // Stores the edited values temporarily
const editMode = ref(false); // Global edit mode flag
const isLoading = ref(true);
const errorMessage = ref('');

// Group results for better organization
const groupedResults = computed(() => {
    if (!props.separatedResults) return {};
    
    return {
        calculated: props.separatedResults.calculatedParams || [],
        inputVariables: props.separatedResults.inputVariables || [],
        manual: props.separatedResults.manualParams || []
    };
});

// Get a unique ID for each result (critical for proper state management)
const getResultUniqueId = (result) => {
    if (result.id) return result.id.toString();
    if (result.parameter_id?.id) return `param-${result.parameter_id.id}`;
    if (result.parameter_id?.value) return `param-${result.parameter_id.value}`;
    if (result.parameter_id?.code) return `code-${result.parameter_id.code}`;
    return `temp-${Math.random().toString(36).substr(2, 9)}`;
};

// Check if a specific result is in edit mode
const isEditing = (result) => {
    const resultId = getResultUniqueId(result);
    return !!editingResults.value[resultId];
};

// Initialize verification data
const initializeVerification = () => {
    console.log('Initializing verification...');
    console.log('Form results count:', props.form.results?.length);
    
    if (!props.form.results || props.form.results.length === 0) {
        errorMessage.value = 'Nenhum resultado encontrado para verificação.';
        isLoading.value = false;
        return;
    }
    
    // Ensure each result has the proper structure
    props.form.results.forEach((result, index) => {
        // Ensure we have a unique ID
        if (!result._uniqueId) {
            result._uniqueId = getResultUniqueId(result);
        }
        
        // Ensure we have verification fields
        if (!result.verification_status) {
            result.verification_status = 'pending';
        }
        
        // Store original value for comparison
        if (!result.original_value) {
            result.original_value = result.inserted_value || '';
        }
        
        // Ensure verified_value is set
        if (!result.verified_value) {
            result.verified_value = result.inserted_value || '';
        }

        if (!result.uncertainty_value) {
            result.uncertainty_value = result.uncertainty_value || null;
        }
    });
    
    isLoading.value = false;
    errorMessage.value = '';
    console.log('Verification initialized with', props.form.results.length, 'results');
};

// Toggle edit mode for a SPECIFIC result
const toggleEditMode = (result) => {
    const resultId = getResultUniqueId(result);
    const currentlyEditing = editingResults.value[resultId];
    
    console.log('Toggling edit for result:', resultId, 'Currently editing:', currentlyEditing);
    
    if (currentlyEditing) {
        // We're exiting edit mode - save changes
        saveEdit(resultId);
    } else {
        // FIRST: Close any other open edit modes
        closeAllEditModes();
        
        // NOW enter edit mode for this specific result
        editingResults.value[resultId] = true;
        
        // Store the current value for editing
        const currentValue = result.verified_value || result.inserted_value || '';
        const currentUncertainty = result.uncertainty_value || ''; // ✅ NEW: Capture uncertainty

        // Store both in editedValues
        editedValues.value[resultId] = {
            value: currentValue,
            uncertainty: currentUncertainty // ✅ NEW: Store uncertainty separately
        };
        
        // Update global edit mode flag
        editMode.value = true;
    }
};

// Close all edit modes (ensure only one result can be edited at a time)
const closeAllEditModes = () => {
    Object.keys(editingResults.value).forEach(resultId => {
        if (editingResults.value[resultId]) {
            // Save any pending changes before closing
            saveEdit(resultId);
        }
    });
    
    // Clear all edit states
    editingResults.value = {};
    editMode.value = false;
};

// Save edited value for a specific result
// const saveEdit = (resultId) => {
//     const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === resultId);
    
//     if (resultIndex === -1) {
//         console.error('Result not found for ID:', resultId);
//         return;
//     }
    
//     const newValue = editedValues.value[resultId];
//     const result = props.form.results[resultIndex];
    
//     console.log('Saving edit for result:', resultId, 'New value:', newValue, 'Old value:', result.inserted_value);
    
//     // Update both fields
//     result.verified_value = newValue;
//     result.inserted_value = newValue;
    
//     // Mark as edited
//     result.was_edited = true;
//     result.edited_at = new Date().toISOString();
//     result.edited_by = 'current_user_id'; // Replace with actual user ID
    
//     // Exit edit mode for this result
//     editingResults.value[resultId] = false;
    
//     // Update global edit mode flag
//     editMode.value = Object.values(editingResults.value).some(v => v);
// };

const saveEdit = (resultId) => {
    const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === resultId);
    
    if (resultIndex === -1) {
        console.error('Result not found for ID:', resultId);
        return;
    }

    // ✅ NEW: Read values from the structured editedValues
    const newValue = editedValues.value[resultId]?.value;
    const newUncertainty = editedValues.value[resultId]?.uncertainty;
    const result = props.form.results[resultIndex];
    
    console.log('Saving edit for result:', resultId, 'New verified value:', newValue, 'Original inserted value:', result.inserted_value);
    
    // 🛑 CRITICAL FIX: ONLY update verified_value.
    // Update the result fields
    result.verified_value = newValue;
    result.uncertainty_value = newUncertainty === '' ? null : newUncertainty; // ✅ NEW: Update uncertainty
    
    // Mark as edited
    result.was_edited = true;
    result.edited_at = new Date().toISOString();
    result.edited_by = 'current_user_id'; // Replace with actual user ID
    
    // Exit edit mode for this result
    editingResults.value[resultId] = false;
    
    // Update global edit mode flag
    editMode.value = Object.values(editingResults.value).some(v => v);
};

// Cancel edit for a specific result
// const cancelEdit = (resultId) => {
//     const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === resultId);
    
//     if (resultIndex !== -1) {
//         const result = props.form.results[resultIndex];
//         const originalValue = result.original_value || '';
        
//         // Restore original values
//         result.verified_value = originalValue;
//         result.inserted_value = originalValue;
        
//         console.log('Cancelled edit for result:', resultId, 'Restored to:', originalValue);
//     }
    
//     // Clear edited value
//     delete editedValues.value[resultId];
    
//     // Exit edit mode
//     editingResults.value[resultId] = false;
    
//     // Update global edit mode flag
//     editMode.value = Object.values(editingResults.value).some(v => v);
// };

// Handle input change in edit mode
const handleInputChange = (resultId, field, value) => {
    // Ensure the structure exists
    if (!editedValues.value[resultId]) {
        editedValues.value[resultId] = { value: '', uncertainty: '' };
    }

    if (field === 'value') {
        editedValues.value[resultId].value = value;
    } else if (field === 'uncertainty') {
        editedValues.value[resultId].uncertainty = value;
    }
};

const cancelEdit = (resultId) => {
    const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === resultId);
    
    if (resultIndex !== -1) {
        const result = props.form.results[resultIndex];
        const originalValue = result.original_value || '';
        const originalUncertainty = result.uncertainty_value || ''; // ✅ NEW: Get original uncertainty
        
        // Restore only the verified_value to the original value
        result.verified_value = originalValue;
        result.uncertainty_value = originalUncertainty; // ✅ NEW: Restore uncertainty
        
        console.log('Cancelled edit for result:', resultId, 'Restored verified_value to:', originalValue);
    }
    
    // Clear edited value
    delete editedValues.value[resultId];
    
    // Exit edit mode
    editingResults.value[resultId] = false;
    
    // Update global edit mode flag
    editMode.value = Object.values(editingResults.value).some(v => v);
};

// Toggle verification status for a specific result
const toggleVerification = (result, status) => {
    const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === getResultUniqueId(result));
    
    if (resultIndex === -1) return;
    
    props.form.results[resultIndex].verification_status = status;
    props.form.results[resultIndex].verified_at = new Date().toISOString();
    
    if (status === 'rejected') {
        props.form.results[resultIndex].verification_notes = 
            props.form.results[resultIndex].verification_notes || 'Resultado rejeitado na verificação.';
    }
    
    console.log('Toggled verification for result:', getResultUniqueId(result), 'Status:', status);
};

// Check if value was changed from original
const valueWasChanged = (result) => {
    const currentValue = result.verified_value || result.inserted_value || '';
    const originalValue = result.original_value || '';
    return currentValue !== originalValue;
};

// Prepare data for submission
const prepareSubmissionData = () => {
    return props.form.results.map(result => ({
        // id: result.id,
        // parameter_id: result.parameter_id?.value || result.parameter_id?.id || result.parameter_id,
        // parameter_code: result.parameter_id?.code,
        // inserted_value: result.inserted_value,
        // verified_value: result.verified_value,
        // uncertainty_value: result.uncertainty_value, // ✅ NEW: Include uncertainty in the payload
        // unit_id: result.unit_id?.value || result.unit_id?.id || result.unit_id,
        // verification_status: result.verification_status || 'pending',
        // verification_notes: result.verification_notes || '',
        // was_edited: result.was_edited || false,
        // edited_at: result.edited_at,
        // edited_by: result.edited_by,
        // // Include other necessary fields
        // min_ref_value: result.min_ref_value,
        // max_ref_value: result.max_ref_value,
        // cfu1: result.cfu1,
        // cfu2: result.cfu2,
        // d1: result.d1,
        // d2: result.d2,
        // volume: result.volume,
        // calculation_metadata: result.calculation_metadata,
        // manual_override: result.manual_override

        approved_by: result.approved_by,
        result_id: result.result_id,
        approved_by_id: result.approved_by_id,
        verified_by_id: result.verified_by_id,
        approved_value: result.approved_value,
        approved_date: result.approved_date,
        approval_notes: result.approval_notes,
        collection_id: result.collection_id,
        count: result.count,
        inserted_by: result.inserted_by,
        inserted_by_id: result.inserted_by_id,
        inserted_date: result.inserted_date,
        inserted_value: result.inserted_value,
        insertion_notes: result.insertion_notes,
        verified_by: result.verified_by,
        verified_value: result.verified_value,
        verification_notes: result.verification_notes,
        matrix_id: result.matrix_id,
        max_ref_value: result.max_ref_value,
        min_ref_value: result.min_ref_value,
        parameter_id: result.parameter_id,
        product_id: result.product_id,
        protocol_id: result.protocol_id,
        profile_id: result.profile_id,
        unit_id: result.unit_id,
        unit_label: result.unit_label,
        standard_id: result.standard_id,
        code_id: result.code_id,
        nwp_id: result.nwp_id,
        requested_counter_analysis: result.requested_counter_analysis,
        sample_id: result.sample_id,
        status: result.status,
        type_id: result.type_id,
        category_label: result.category_label,
        uncertainty_value: result.uncertainty_value,
        sumC: result.sumC,
        volume: result.volume,
        n1: result.n1,
        n2: result.n2,
        dilution: result.dilution,
        d1: result.d1,
        d2: result.d2,
        cfu1: result.cfu1,
        cfu2: result.cfu2,
        is_calculated: result.is_calculated,
        calculation_metadata: result.calculation_metadata ?? null,
        is_override: result.manual_override ?? false,
    }));
};

// Submit verification
const submitVerification = () => {
    if (isLoading.value) {
        alert('Aguarde o carregamento dos resultados.');
        return;
    }
    
    if (!props.form.results || props.form.results.length === 0) {
        alert('Nenhum resultado encontrado para verificar.');
        return;
    }
    
    // Save any pending edits
    closeAllEditModes();
    
    // Check for empty values
    const emptyResults = props.form.results.filter(r => 
        !r.inserted_value && r.inserted_value !== 0
    );
    
    if (emptyResults.length > 0) {
        const paramNames = emptyResults.map(r => 
            r.parameter_id?.code || r.parameter_id?.name || 'Parâmetro desconhecido'
        ).join(', ');
        
        const proceed = confirm(
            `${emptyResults.length} resultado(s) estão vazios: ${paramNames}\n` +
            'Deseja prosseguir mesmo assim?'
        );
        
        if (!proceed) return;
    }
    
    // Prepare submission data
    const submissionData = prepareSubmissionData();
    
    // Update form data
    props.form.results = submissionData;
    props.form.status = 'verified';
    props.form.performed_at = new Date().toISOString();
    
    // Emit submit event to parent
    emit('submit');
};

// Load results manually if needed
const loadResultsManually = () => {
    console.log('Manual load requested');
    errorMessage.value = 'Funcionalidade de recarregamento não implementada.';
    // Implement your API call here if needed
};

// Initialize on mount
onMounted(() => {
    console.log('VerifyResultComponent mounted');
    setTimeout(() => {
        initializeVerification();
    }, 100);
});

// Watch for changes in form results
watch(() => props.form.results, (newResults) => {
    if (newResults && newResults.length > 0) {
        initializeVerification();
    }
}, { deep: true });

// Watch for edit mode changes to update UI
watch(editMode, (newValue) => {
    console.log('Edit mode changed to:', newValue);
});
</script>

<template>
<div class="space-y-8">
    <!-- Debug panel (remove in production) -->
    <div v-if="false" class="bg-gray-100 p-4 rounded-lg text-xs">
        <div class="font-semibold mb-2">Debug Info:</div>
        <div>Results: {{ form.results?.length || 0 }}</div>
        <div>Edit mode: {{ editMode }}</div>
        <div>Editing results: {{ Object.keys(editingResults).filter(k => editingResults[k]).length }}</div>
        <div>Loading: {{ isLoading }}</div>
    </div>

    <!-- Verification Header -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <DocumentMagnifyingGlassIcon class="h-8 w-8 text-yellow-600" />
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-medium text-yellow-800">
                    Verificação de Resultados
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>Amostra: <strong>{{ record?.sample_id?.label || record?.code || form.sample_id || 'N/A' }}</strong></p>
                    <p>Total de resultados: <strong>{{ form.results?.length || 0 }}</strong></p>
                </div>
                
                <!-- Loading state -->
                <div v-if="isLoading" class="mt-3 p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                        <span class="text-blue-700">Carregando resultados...</span>
                    </div>
                </div>
                
                <!-- Error state -->
                <div v-if="errorMessage && !isLoading" class="mt-3 p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="text-red-700">{{ errorMessage }}</div>
                        <button @click="loadResultsManually"
                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                            Recarregar
                        </button>
                    </div>
                </div>
                
                <!-- Edit mode warning -->
                <div v-if="editMode" class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center">
                        <PencilIcon class="h-5 w-5 text-blue-600 mr-2" />
                        <span class="text-blue-700 font-medium">Modo de edição ativo</span>
                        <span class="ml-2 text-blue-600 text-sm">(apenas um resultado pode ser editado por vez)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600">Carregando resultados para verificação...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="!form.results || form.results.length === 0" class="text-center py-12">
        <div class="mx-auto h-12 w-12 text-gray-400">
            <DocumentMagnifyingGlassIcon class="h-full w-full" />
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Nenhum resultado encontrado</h3>
        <p class="mt-2 text-sm text-gray-600">
            Não há resultados para verificar nesta amostra.
        </p>
        <div class="mt-6">
            <button @click="loadResultsManually"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Tentar novamente
            </button>
        </div>
    </div>

    <!-- Results List (when loaded) -->
    <div v-else class="space-y-6">
        <!-- For each result group -->
        <template v-for="(resultGroup, groupName) in groupedResults" :key="groupName">
            <div v-if="resultGroup.length > 0" class="bg-white rounded-lg shadow">
                
                <!-- Group header -->
                <div class="px-6 py-4 border-b border-gray-200" 
                     :class="{
                         'bg-purple-50': groupName === 'calculated',
                         'bg-blue-50': groupName === 'manual',
                         'bg-green-50': groupName === 'inputVariables'
                     }">
                    <h4 class="text-sm font-semibold uppercase tracking-wider"
                        :class="{
                            'text-purple-900': groupName === 'calculated',
                            'text-blue-900': groupName === 'manual',
                            'text-green-900': groupName === 'inputVariables'
                        }">
                        {{ 
                            groupName === 'calculated' ? 'Parâmetros Calculados' :
                            groupName === 'manual' ? 'Parâmetros Manuais' :
                            'Variáveis de Entrada'
                        }}
                        <span class="ml-2 font-normal">({{ resultGroup.length }})</span>
                    </h4>
                </div>
                
                <!-- Results in this group -->
                <div class="divide-y divide-gray-200">
                    <div v-for="result in resultGroup" 
                         :key="getResultUniqueId(result)"
                         class="p-6 hover:bg-gray-50 transition-colors duration-150">
                        
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <!-- Parameter info -->
                                <div class="flex items-center mb-3">
                                    <span class="font-medium text-gray-900">
                                        {{ result.parameter_id?.code || 'N/A' }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-600">
                                        {{ result.parameter_id?.name || '' }}
                                    </span>
                                    
                                    <!-- Edit badge -->
                                    <span v-if="valueWasChanged(result)"
                                          class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                        Editado
                                    </span>
                                    
                                    <!-- Verification status badge -->
                                    <span v-if="result.verification_status === 'approved'"
                                          class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                        Aprovado
                                    </span>
                                    <span v-else-if="result.verification_status === 'rejected'"
                                          class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                        Rejeitado
                                    </span>
                                </div>
                                
                                <!-- Value display/edit section -->
                                <div class="mt-2">
                                    <!-- DISPLAY MODE (when NOT editing this specific result) -->
                                    <div v-if="!isEditing(result)" class="flex items-center">
                                        <!-- Current value -->
                                        <div class="text-lg font-semibold"
                                             :class="{
                                                 'text-green-600': result.verification_status === 'approved',
                                                 'text-red-600': result.verification_status === 'rejected',
                                                 'text-gray-900': !result.verification_status || result.verification_status === 'pending'
                                             }">
                                            {{ result.verified_value || result.inserted_value || '-' }}
                                            <span v-if="result.unit_id?.code" 
                                                  class="text-sm font-normal text-gray-600 ml-1">
                                                {{ result.unit_id.code }}
                                            </span>

                                            <span v-if="result.uncertainty_value" 
                                                class="text-sm font-normal text-gray-600 ml-2">
                                                (± {{ result.uncertainty_value }})
                                            </span>
                                        </div>
                                        
                                        <!-- Edit button (only shows when not already editing) -->
                                        <button v-if="groupName !== 'calculated'" @click="toggleEditMode(result)"
                                                class="ml-3 p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                                :title="`Editar ${result.parameter_id?.code}`">
                                            <PencilIcon class="h-4 w-4" />
                                        </button>

                                        <button v-if="groupName === 'calculated'" @click="emit('open-calculation')"
                                                class="ml-3 p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                                :title="`Editar ${result.parameter_id?.code}`">
                                            <CalculatorIcon class="h-4 w-4" />
                                        </button>
                                        
                                        <!-- Show original value if changed -->
                                        <div v-if="valueWasChanged(result)" 
                                             class="ml-4 text-sm text-gray-500">
                                            <span class="line-through">{{ result.original_value || '-' }}</span>
                                            <span v-if="result.unit_id?.code" class="ml-1">{{ result.unit_id.code }}</span>
                                            <span class="ml-2 text-xs">(original)</span>
                                        </div>
                                    </div>
                                    
                                    <!-- EDIT MODE (only for THIS specific result) -->
                                    <div v-if="isEditing(result)" class="space-y-3">
                                        <!-- Edit input -->
                                        <div class="flex items-center space-x-3">
                                            <input :value="editedValues[getResultUniqueId(result)]?.value || result.verified_value || result.inserted_value || ''"
                                                   @input="handleInputChange(getResultUniqueId(result), 'value', $event.target.value)"
                                                   type="text"
                                                   class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2"
                                                   :placeholder="`Editar ${result.parameter_id?.code}`"
                                                   autofocus>

                                            <div class="flex items-center space-x-2 w-1/2">
                                                <span class="text-sm font-semibold text-gray-600">±</span>
                                                <input :value="editedValues[getResultUniqueId(result)]?.uncertainty || result.uncertainty_value || ''"
                                                    @input="handleInputChange(getResultUniqueId(result), 'uncertainty', $event.target.value)"
                                                    type="text"
                                                    class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2"
                                                    placeholder="Incerteza">
                                            </div>      

                                            <span class="text-sm text-gray-600 whitespace-nowrap">
                                                {{ result.unit_id?.code || '' }}
                                            </span>
                                        </div>

                                        
                                        
                                        <!-- Edit action buttons -->
                                        <div class="flex space-x-2">
                                            <button @click="saveEdit(getResultUniqueId(result))"
                                                    class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 font-medium transition-colors">
                                                Salvar
                                            </button>
                                            <button @click="cancelEdit(getResultUniqueId(result))"
                                                    class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300 font-medium transition-colors">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                
                                <!-- Reference range -->
                                <div v-if="result.min_ref_value || result.max_ref_value"
                                     class="mt-2 text-xs text-gray-500">
                                    <span class="font-medium">Referência:</span>
                                    <span class="ml-1">
                                        <template v-if="result.min_ref_value && result.max_ref_value">
                                            {{ result.min_ref_value }} - {{ result.max_ref_value }}
                                        </template>
                                        <template v-else-if="result.min_ref_value">
                                            ≥ {{ result.min_ref_value }}
                                        </template>
                                        <template v-else-if="result.max_ref_value">
                                            ≤ {{ result.max_ref_value }}
                                        </template>
                                        <span v-if="result.unit_id?.code" class="ml-1">{{ result.unit_id.code }}</span>
                                    </span>
                                </div>
                                
                                <!-- Verification notes (if rejected) -->
                                <div v-if="result.verification_status === 'rejected' && result.verification_notes"
                                     class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs">
                                    <span class="font-medium text-red-800">Justificação:</span>
                                    <span class="ml-2 text-red-700">{{ result.verification_notes }}</span>
                                </div>
                            </div>
                            
                            <!-- Verification action buttons -->
                            <div class="ml-4 flex items-center space-x-2">
                                <!-- Approve button -->
                                <button @click="toggleVerification(result, 'approved')"
                                        :class="[
                                            'p-2 rounded-full transition-colors',
                                            result.verification_status === 'approved' 
                                                ? 'bg-green-100 text-green-600 ring-2 ring-green-300' 
                                                : 'bg-gray-100 text-gray-400 hover:bg-green-50 hover:text-green-600'
                                        ]"
                                        :disabled="isEditing(result)"
                                        :title="`Aprovar ${result.parameter_id?.code}`">
                                    <CheckIcon class="h-5 w-5" />
                                </button>
                                
                                <!-- Reject button -->
                                <button @click="toggleVerification(result, 'rejected')"
                                        :class="[
                                            'p-2 rounded-full transition-colors',
                                            result.verification_status === 'rejected' 
                                                ? 'bg-red-100 text-red-600 ring-2 ring-red-300' 
                                                : 'bg-gray-100 text-gray-400 hover:bg-red-50 hover:text-red-600'
                                        ]"
                                        :disabled="isEditing(result)"
                                        :title="`Rejeitar ${result.parameter_id?.code}`">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Global notes -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                    Observações Gerais da Verificação
                </h4>
                <textarea v-model="form.notes"
                          rows="4"
                          class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-3"
                          placeholder="Adicione observações gerais sobre a verificação (opcional)..."></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Estas observações serão registradas no histórico da amostra.
                </p>
            </div>
        </div>
    </div>

    <!-- Action buttons (only show when we have results) -->
    <div v-if="!isLoading && form.results && form.results.length > 0" 
         class="flex justify-between items-center pt-6 border-t border-gray-200">
        <div class="text-sm text-gray-600">
            <span class="font-medium">Status:</span>
            <span class="ml-2 capitalize" :class="{
                'text-green-600': form.status === 'verified',
                'text-red-600': form.status === 'rejected',
                'text-yellow-600': !form.status || form.status === 'pending'
            }">
                {{ form.status || 'pendente' }}
            </span>
            <div v-if="editMode" class="text-xs text-blue-600 mt-1">
                ⚠️ Finalize a edição antes de submeter
            </div>
        </div>
        
        <div class="flex space-x-3">
            <button @click="closeAllEditModes"
                    v-if="editMode"
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium">
                Cancelar Edições
            </button>
            
            <button @click="submitVerification"
                    :disabled="form.processing || editMode"
                    class="px-6 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                {{ form.processing ? 'Processando...' : 'Confirmar Verificação' }}
            </button>
        </div>
    </div>
</div>
</template>

<style scoped>
/* Custom styles for better visual feedback */
button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

input:focus {
    outline: none;
    ring-width: 2px;
}

.transition-colors {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>