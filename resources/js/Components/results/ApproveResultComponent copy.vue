<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { CheckCircleIcon, XCircleIcon, ShieldCheckIcon, PencilIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    form: Object,
    record: Object,
    action: String,
    separatedResults: Object
});

const emit = defineEmits(['submit', 'open-calculation']);

// Track which results are being edited
const editingResults = ref({});
const editMode = ref(false);
const editedValues = ref({}); // ✅ NEW: Stores temporary edits { value: '...', uncertainty: '...' }
const isLoading = ref(true);
const errorMessage = ref('');

// Group results for approval view
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

// ✅ NEW: Computed property to check if any results exist
const hasResultsToDisplay = computed(() => {
    return (groupedResults.value.calculated.length > 0 || groupedResults.value.manual.length > 0);
});

// Initialize approval form
const initializeApproval = () => {
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
            result.original_value = result.verified_value || '';
        }
        
        // Ensure verified_value is set
        if (!result.verified_value) {
            result.verified_value = result.verified_value || '';
        }

        if (!result.uncertainty_value) {
            result.uncertainty_value = result.uncertainty_value || null;
        }
    });
    
    isLoading.value = false;
    errorMessage.value = '';
    console.log('Verification initialized with', props.form.results.length, 'results');
};

// Toggle edit mode for a specific result
// const toggleEditMode = (resultId) => {
//     editingResults.value[resultId] = !editingResults.value[resultId];
//     if (!editingResults.value[resultId]) {
//         editMode.value = false;
//     } else {
//         editMode.value = true;
//     }
// };

const toggleEditMode = (result) => { // Accept the full result object now
    const resultId = result.id;
    
    // Close any other open edit modes (best practice)
    closeAllEditModes();
    
    // Toggle the current result's edit state
    editingResults.value[resultId] = !editingResults.value[resultId];
    
    if (editingResults.value[resultId]) {
        editMode.value = true;
        
        // ✅ NEW: Populate editedValues with both fields
        editedValues.value[resultId] = {
            approved_value: result.approved_value,
            uncertainty_value: result.uncertainty_value
        };
    } else {
        editMode.value = false;
        // The saveEdit or cancelEdit will handle cleanup
    }
};

// Save edited result
// const saveEdit = (resultId) => {
//     const index = props.form.results.findIndex(r => r.id === resultId);
//     if (index !== -1) {
//         // Mark as edited during approval
//         props.form.results[index].was_edited_in_approval = true;
//         props.form.results[index].edited_in_approval_at = new Date().toISOString();
//         props.form.results[index].edited_in_approval_by = 'current_user_id';
        
//         // Update the main form as well
//         const mainIndex = props.form.results.findIndex(r => r.id === resultId);
//         if (mainIndex !== -1) {
//             props.form.results[mainIndex].inserted_value = props.form.results[index].approved_value;
//         }
        
//         toggleEditMode(resultId);
//     }
// };

const saveEdit = (resultId) => {
    const index = props.form.results.findIndex(r => r.id === resultId);
    
    if (index !== -1 && editedValues.value[resultId]) {
        const result = props.form.results[index];
        const edited = editedValues.value[resultId];
        
        // 1. Update the result values
        result.approved_value = edited.approved_value;
        result.uncertainty_value = edited.uncertainty_value || null; // ✅ NEW
        
        // 2. Mark as edited during approval
        result.was_edited_in_approval = true;
        result.edited_in_approval_at = new Date().toISOString();
        result.edited_in_approval_by = 'current_user_id';
        
        // 3. CRUCIAL: Update the main form data (`props.form`) 
        // We update the FINAL source fields that the next stage will use.
        const mainIndex = props.form.results.findIndex(r => r.id === resultId);
        if (mainIndex !== -1) {
            props.form.results[mainIndex].inserted_value = result.approved_value;
            props.form.results[mainIndex].uncertainty_value = result.uncertainty_value; // ✅ NEW
        }
        
        toggleEditMode(result); // Exit edit mode
        delete editedValues.value[resultId]; // Clean up temp state
    }
};

// Cancel edit
// const cancelEdit = (resultId) => {
//     const index = props.form.results.findIndex(r => r.id === resultId);
//     if (index !== -1) {
//         props.form.results[index].approved_value = props.form.results[index].original_value;
//     }
//     toggleEditMode(resultId);
// };

const cancelEdit = (resultId) => {
    const index = props.form.results.findIndex(r => r.id === resultId);
    if (index !== -1) {
        const result = props.form.results[index];
        // Restore both values
        result.approved_value = result.original_value;
        result.uncertainty_value = result.uncertainty_value; // ✅ NEW
    }
    
    delete editedValues.value[resultId]; // Clean up temp state
    toggleEditMode(props.form.results[index]); // Exit edit mode
};

// Toggle approval for a specific result
const toggleApproval = (resultId, status) => { // ✅ Change: Accepts resultId
    // Find the index in the main array
    const index = props.form.results.findIndex(r => r.id === resultId);
    
    if (index === -1) return;
    
    props.form.results[index].approval_status = status;
    props.form.results[index].approved_at = new Date().toISOString();
    
    if (status === 'rejected') {
        props.form.results[index].approval_notes = 
            props.form.results[index].approval_notes || 'Resultado rejeitado na validação.';
    }
};

// Check if result has passed verification
const hasPassedVerification = (result) => {
    return result.verification_status === 'approved';
};

// Check if value was changed during approval
const valueWasChanged = (result) => {
    const valueChanged = result.approved_value !== result.original_value;
    
    // ✅ NEW: Check if uncertainty changed
    const uncertaintyChanged = (result.uncertainty_value || '') !== (result.original_uncertainty_value || '');
    
    return valueChanged || uncertaintyChanged;
};

// Submit approval
const submitApproval = () => {
    // Check if all results have passed verification
    const allVerified = props.form.results.every(r => 
        hasPassedVerification(r)
    );
    
    if (!allVerified) {
        alert('Não é possível validar resultados que não passaram na verificação.');
        return;
    }
    
    // Calculate overall approval status
    const allApproved = props.form.results.every(r => 
        r.approval_status === 'approved'
    );
    
    props.form.approval_status = allApproved ? 'approved' : 'rejected';
    props.form.approved_at = new Date().toISOString();
    
    // Prepare results for submission
    const submissionResults = props.form.results.map(result => ({
        id: result.id,
        approved_value: result.approved_value,
        uncertainty_value: result.uncertainty_value, // ✅ NEW
        approval_status: result.approval_status,
        approval_notes: result.approval_notes,
        was_edited_in_approval: result.was_edited_in_approval || false,
        edited_in_approval_by: result.edited_in_approval_by,
        edited_in_approval_at: result.edited_in_approval_at
    }));
    
    props.form.results = submissionResults;
    
    props.form.post(route('results.approve'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submit');
        }
    });
};

// Open calculation for verification
const openCalculationForApproval = () => {
    emit('open-calculation');
};

// Initialize on mount
onMounted(() => {
    console.log('VerifyResultComponent mounted');
    setTimeout(() => {
        initializeApproval();
    }, 100);
});

// Watch for changes in form results
watch(() => props.form.results, (newResults) => {
    if (newResults && newResults.length > 0) {
        initializeApproval();
    }
}, { deep: true });
</script>

<template>
<div class="space-y-8">
    <!-- Approval Header -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <ShieldCheckIcon class="h-8 w-8 text-green-600" />
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-green-800">
                    Validação Final de Resultados
                </h3>
                <div class="mt-2 text-sm text-green-700">
                    <p>Validação final e correção final de resultados. Esta ação tornará os resultados definitivos.</p>
                    <p class="mt-1 font-medium">Você pode fazer correções finais se necessário.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results List for Approval -->
    <div v-if="hasResultsToDisplay" class="space-y-6">
        <!-- Calculated Parameters -->
        <div v-if="groupedResults.calculated.length > 0" class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-purple-900 uppercase tracking-wider">
                        Parâmetros Calculados para Validação
                    </h4>
                    <button @click="openCalculationForApproval"
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700">
                        Recalcular
                    </button>
                </div>
            </div>
            <div class="divide-y divide-gray-200">
                <div v-for="(result, index) in groupedResults.calculated" 
                     :key="`calc-${index}`"
                     class="p-6 hover:bg-gray-50"
                     :class="{ 'opacity-70': !hasPassedVerification(result) }">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="font-medium text-gray-900">
                                    {{ result.parameter_id?.code }}
                                </span>
                                <span class="ml-2 text-sm text-gray-600">
                                    {{ result.parameter_id?.name }}
                                </span>
                                
                                <!-- Verification Status Badge -->
                                <span v-if="hasPassedVerification(result)"
                                      class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                    Verificado
                                </span>
                                <span v-else
                                      class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                    Não Verificado
                                </span>
                                
                                <!-- Edit Status Badge -->
                                <span v-if="valueWasChanged(result)"
                                      class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full">
                                    Editado
                                </span>
                            </div>
                            
                            <!-- Result Display/Edit -->
                            <div class="mt-2">
                                <div v-if="!editingResults[result.id]" class="flex items-center">
                                    <div class="text-lg font-semibold"
                                         :class="result.approval_status === 'approved' ? 'text-green-600' : 
                                                 result.approval_status === 'rejected' ? 'text-red-600' : 
                                                 'text-gray-900'">
                                        {{ result.approved_value || result.verified_value }}

                                        <span v-if="result.unit_id?.code" class="text-sm font-normal text-gray-600 ml-1">{{ result.unit_id?.code }}</span>
    
                                        <span v-if="result.approved_uncertainty_value" class="text-sm font-normal text-gray-600 ml-2">
                                            (± {{ result.approved_uncertainty_value }})
                                        </span>
                                    </div>
                                    
                                    <!-- Edit Button (only if verified) -->
                                    <button v-if="hasPassedVerification(result)"
                                            @click="toggleEditMode(result.id)"
                                            class="ml-3 p-1 text-gray-400 hover:text-blue-600">
                                        <PencilIcon class="h-4 w-4" />
                                    </button>
                                    
                                    <!-- Show original if changed -->
                                    <div v-if="valueWasChanged(result)" 
                                         class="ml-4 text-sm text-gray-500">
                                        <span class="line-through">{{ result.original_value }} {{ result.unit_id?.code }}</span>
                                        <span class="ml-2 text-xs">(original)</span>
                                    </div>
                                </div>
                                
                                <!-- Edit Mode -->
                                <div v-if="editingResults[result.id]" class="space-y-3">
                                    <div class="flex items-end space-x-3">
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Valor</label>
                                            <input :value="editedValues[result.id]?.approved_value || result.approved_value"
                                                @input="handleInputChange(result.id, 'approved_value', $event.target.value)"
                                                type="text"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                placeholder="Novo valor">
                                        </div>
                                        
                                        <div class="w-1/3">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">± Incerteza</label>
                                            <input :value="editedValues[result.id]?.approved_uncertainty_value || result.approved_uncertainty_value"
                                                @input="handleInputChange(result.id, 'approved_uncertainty_value', $event.target.value)"
                                                type="text"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                placeholder="Incerteza">
                                        </div>
                                        
                                        <span class="text-sm text-gray-600 whitespace-nowrap">{{ result.unit_id?.code }}</span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <button @click="saveEdit(result.id)"
                                                class="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                            Salvar
                                        </button>
                                        <button @click="cancelEdit(result.id)"
                                                class="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Verification info -->
                                <div v-if="result.verified_at" 
                                     class="mt-1 text-xs text-gray-500">
                                    Verificado em: {{ new Date(result.verified_at).toLocaleDateString() }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Approval Actions -->
                        <div class="ml-4 flex items-center space-x-2">
                            <button v-if="hasPassedVerification(result)"
                                    @click="toggleApproval(result.id, 'approved')" :class="[/* ... */]"
                                    :disabled="editingResults[result.id]"
                                    title="Aprovar resultado">
                                <CheckCircleIcon class="h-5 w-5" />
                            </button>
                            
                            <button v-if="hasPassedVerification(result)"
                                    @click="toggleApproval(result.id, 'rejected')" :class="[/* ... */]"
                                    :disabled="editingResults[result.id]"
                                    title="Rejeitar resultado">
                                <XCircleIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- Approval Notes for rejected -->
                    <div v-if="result.approval_status === 'rejected'" 
                         class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                        <label class="block text-sm font-medium text-red-800 mb-1">
                            Justificação da Rejeição na Validação
                        </label>
                        <textarea v-model="result.approval_notes"
                                  rows="2"
                                  class="w-full text-sm border-red-300 rounded-md focus:ring-red-500 focus:border-red-500"
                                  placeholder="Explique porque este resultado foi rejeitado na validação..."></textarea>
                    </div>
                    
                    <!-- Warning if not verified -->
                    <div v-if="!hasPassedVerification(result)" 
                         class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-sm text-yellow-800">
                            Este resultado não foi aprovado na verificação e não pode ser validado.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Parameters -->
        <div v-if="groupedResults.manual.length > 0" class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <h4 class="text-sm font-semibold text-blue-900 uppercase tracking-wider">
                    Parâmetros Manuais para Validação
                </h4>
            </div>
            <div class="divide-y divide-gray-200">
                <div v-for="(result, index) in groupedResults.manual" 
                     :key="`manual-${index}`"
                     class="p-6 hover:bg-gray-50"
                     :class="{ 'opacity-70': !hasPassedVerification(result) }">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="font-medium text-gray-900">
                                    {{ result.parameter_id?.code }}
                                </span>
                                <span class="ml-2 text-sm text-gray-600">
                                    {{ result.parameter_id?.name }}
                                </span>
                                
                                <!-- Verification Status Badge -->
                                <span v-if="hasPassedVerification(result)"
                                      class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                    Verificado
                                </span>
                                <span v-else
                                      class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                    Não Verificado
                                </span>
                                
                                <!-- Edit Status Badge -->
                                <span v-if="valueWasChanged(result)"
                                      class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full">
                                    Editado
                                </span>
                            </div>
                            
                            <!-- Result Display/Edit -->
                            <div class="mt-2">
                                <div v-if="!editingResults[result.id]" class="flex items-center">
                                    <div class="text-lg font-semibold"
                                         :class="result.approval_status === 'approved' ? 'text-green-600' : 
                                                 result.approval_status === 'rejected' ? 'text-red-600' : 
                                                 'text-gray-900'">
                                        {{ result.approved_value || result.verified_value }}

                                        <span v-if="result.unit_id?.code" class="text-sm font-normal text-gray-600 ml-1">{{ result.unit_id?.code }}</span>
    
                                        <span v-if="result.approved_uncertainty_value" class="text-sm font-normal text-gray-600 ml-2">
                                            (± {{ result.approved_uncertainty_value }})
                                        </span>
                                    </div>
                                    
                                    <!-- Edit Button (only if verified) -->
                                    <button v-if="hasPassedVerification(result)"
                                            @click="toggleEditMode(result.id)"
                                            class="ml-3 p-1 text-gray-400 hover:text-blue-600">
                                        <PencilIcon class="h-4 w-4" />
                                    </button>
                                    
                                    <!-- Show original if changed -->
                                    <div v-if="valueWasChanged(result)" 
                                         class="ml-4 text-sm text-gray-500">
                                        <span class="line-through">{{ result.original_value }} {{ result.unit_id?.code }}</span>
                                        <span class="ml-2 text-xs">(original)</span>
                                    </div>
                                </div>
                                
                                <!-- Edit Mode -->
                                <div v-if="editingResults[result.id]" class="space-y-3">
                                    <div class="flex items-end space-x-3">
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Valor</label>
                                            <input :value="editedValues[result.id]?.approved_value || result.approved_value"
                                                @input="handleInputChange(result.id, 'approved_value', $event.target.value)"
                                                type="text"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                placeholder="Novo valor">
                                        </div>
                                        
                                        <div class="w-1/3">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">± Incerteza</label>
                                            <input :value="editedValues[result.id]?.approved_uncertainty_value || result.approved_uncertainty_value"
                                                @input="handleInputChange(result.id, 'approved_uncertainty_value', $event.target.value)"
                                                type="text"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                placeholder="Incerteza">
                                        </div>
                                        
                                        <span class="text-sm text-gray-600 whitespace-nowrap">{{ result.unit_id?.code }}</span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <button @click="saveEdit(result.id)"
                                                class="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                            Salvar
                                        </button>
                                        <button @click="cancelEdit(result.id)"
                                                class="px-3 py-1 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Microbiology Fields -->
                            <MicrobiologyFields 
                                v-if="record?.type_id?.value === 2"
                                :result="result"
                                :is-read-only="!editingResults[result.id]"
                                class="mt-3"
                            />
                        </div>
                        
                        <!-- Approval Actions -->
                        <div class="ml-4 flex items-center space-x-2">
                            <button v-if="hasPassedVerification(result)"
                                    @click="toggleApproval(result.id, 'approved')" :class="[/* ... */]"
                                    :disabled="editingResults[result.id]"
                                    title="Aprovar resultado">
                                <CheckCircleIcon class="h-5 w-5" />
                            </button>
                            
                            <button v-if="hasPassedVerification(result)"
                                    @click="toggleApproval(result.id, 'rejected')" :class="[/* ... */]"
                                    :disabled="editingResults[result.id]"
                                    title="Rejeitar resultado">
                                <XCircleIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- Approval Notes for rejected -->
                    <div v-if="result.approval_status === 'rejected'" 
                         class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                        <label class="block text-sm font-medium text-red-800 mb-1">
                            Justificação da Rejeição na Validação
                        </label>
                        <textarea v-model="result.approval_notes"
                                  rows="2"
                                  class="w-full text-sm border-red-300 rounded-md focus:ring-red-500 focus:border-red-500"
                                  placeholder="Explique porque este resultado foi rejeitado na validação..."></textarea>
                    </div>
                    
                    <!-- Warning if not verified -->
                    <div v-if="!hasPassedVerification(result)" 
                         class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-sm text-yellow-800">
                            Este resultado não foi aprovado na verificação e não pode ser validado.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Approval Notes -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">
                    Observações Gerais da Validação
                </h4>
                <textarea v-model="props.form.approval_notes"
                          rows="4"
                          class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Adicione observações gerais sobre a validação..."></textarea>
            </div>
        </div>
    </div>

    <div v-else class="text-center py-12 bg-white rounded-lg shadow">
        <ShieldCheckIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-lg font-medium text-gray-900">Nenhum Resultado Encontrado</h3>
        <p class="mt-1 text-sm text-gray-500">Não há resultados para validar nesta amostra.</p>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
        <div class="text-sm text-gray-600">
            <span class="font-medium">Status Final:</span>
            <span class="ml-2" :class="{
                'text-green-600': props.form.approval_status === 'approved',
                'text-red-600': props.form.approval_status === 'rejected',
                'text-yellow-600': props.form.approval_status === 'pending'
            }">
                {{ props.form.approval_status === 'approved' ? 'Aprovado' :
                   props.form.approval_status === 'rejected' ? 'Rejeitado' :
                   'Pendente' }}
            </span>
            
            <div v-if="editMode" class="text-xs text-blue-600 mt-1">
                ⚠️ Modo de edição ativo
            </div>
        </div>
        
        <div class="flex space-x-3">
            <button @click="props.form.approval_status = 'rejected'"
                    :disabled="editMode"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium',
                        props.form.approval_status === 'rejected'
                            ? 'bg-red-600 text-white'
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        editMode ? 'opacity-50 cursor-not-allowed' : ''
                    ]">
                Rejeitar Todos
            </button>
            
            <button @click="props.form.approval_status = 'approved'"
                    :disabled="editMode"
                    :class="[
                        'px-4 py-2 rounded-md text-sm font-medium',
                        props.form.approval_status === 'approved'
                            ? 'bg-green-600 text-white'
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        editMode ? 'opacity-50 cursor-not-allowed' : ''
                    ]">
                Aprovar Todos
            </button>
            
            <button @click="submitApproval"
                    :disabled="props.form.processing || editMode || !props.form.results.every(r => hasPassedVerification(r))"
                    class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium disabled:opacity-50">
                {{ props.form.processing ? 'Processando...' : 'Confirmar Validação Final' }}
            </button>
        </div>
    </div>
</div>
</template>