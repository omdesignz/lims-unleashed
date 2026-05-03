<!-- resources/js/Components/results/IndividualResultEntry.vue -->
<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ actionText }} Individual
          </h3>
          <button @click="$emit('close')" 
                  class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="space-y-6">
          <!-- Parameter Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Selecionar Parâmetro
            </label>
            <select v-model="selectedParameterId" 
                    class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
              <option value="">Selecione um parâmetro</option>
              <option v-for="param in filteredParameters" 
                      :key="getParameterUniqueId(param)"
                      :value="getParameterUniqueId(param)"
                      :disabled="param.inserted_value && props.action === 'analyze'">
                {{ param.parameter_id?.code || 'N/A' }} - {{ param.parameter_id?.name || 'Sem nome' }}
                <span v-if="hasExistingValue(param)" class="text-gray-500">
                  ({{ getExistingValueText(param) }})
                </span>
              </option>
            </select>
            <p v-if="selectedParameter?.requires_calculation" class="mt-2 text-sm text-purple-600 flex items-center gap-1">
              <CalculatorIcon class="h-4 w-4" />
              Este parâmetro requer cálculo automático
            </p>
          </div>

          <!-- Result Input -->
          <div v-if="selectedParameter" class="space-y-4">
            <!-- Parameter Info Card -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
              <div class="flex justify-between items-start">
                <div>
                  <h4 class="font-semibold text-gray-900">{{ selectedParameter.parameter_id?.name }}</h4>
                  <p class="text-sm text-gray-600">{{ selectedParameter.parameter_id?.code }}</p>
                  
                  <!-- Reference Range -->
                  <div v-if="selectedParameter.min_ref_value || selectedParameter.max_ref_value"
                       class="mt-2 text-xs text-blue-600 flex items-center gap-1">
                    <ScaleIcon class="h-3 w-3" />
                    <span>
                      Referência: 
                      <template v-if="selectedParameter.min_ref_value && selectedParameter.max_ref_value">
                        {{ selectedParameter.min_ref_value }} - {{ selectedParameter.max_ref_value }}
                      </template>
                      <template v-else-if="selectedParameter.min_ref_value">
                        ≥ {{ selectedParameter.min_ref_value }}
                      </template>
                      <template v-else-if="selectedParameter.max_ref_value">
                        ≤ {{ selectedParameter.max_ref_value }}
                      </template>
                      <span v-if="selectedParameter.unit_label">{{ selectedParameter.unit_label }}</span>
                    </span>
                  </div>
                </div>
                
                <span v-if="selectedParameter.requires_calculation" 
                      class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                  <CalculatorIcon class="h-3 w-3" />
                  Calculado
                </span>
              </div>
              
              <!-- Unit Info -->
              <div v-if="selectedParameter.unit_label" class="mt-2 text-sm text-gray-500">
                Unidade: {{ selectedParameter.unit_label }}
              </div>
            </div>

            <!-- Value Input Section -->
            <div class="space-y-4">
              <!-- Value Input -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  {{ valueLabel }}
                  <span v-if="selectedParameter.unit_label" class="text-gray-500 ml-1">
                    ({{ selectedParameter.unit_label }})
                  </span>
                </label>
                <div class="relative">
                  <input v-model="resultValue"
                         type="text"
                         :disabled="selectedParameter.requires_calculation"
                         class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6 disabled:bg-gray-100 disabled:text-gray-500"
                         :placeholder="selectedParameter.requires_calculation ? 'Valor calculado automaticamente' : `Insira valor para ${selectedParameter.parameter_id?.code}`">
                  <div v-if="selectedParameter.requires_calculation" 
                       class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <CalculatorIcon class="h-5 w-5 text-purple-600" />
                  </div>
                </div>
                <p v-if="selectedParameter.requires_calculation" class="mt-1 text-xs text-purple-600">
                  Use a calculadora para determinar este valor
                </p>
              </div>

              <!-- Uncertainty Input -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Incerteza (±)
                  <span v-if="selectedParameter.unit_label" class="text-gray-500 ml-1">
                    ({{ selectedParameter.unit_label }})
                  </span>
                </label>
                <input v-model="uncertaintyValue"
                       type="text"
                       class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                       placeholder="Ex: 0.1">
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Observações
                </label>
                <textarea v-model="notes"
                          rows="3"
                          class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                          :placeholder="`Observações sobre ${selectedParameter.parameter_id?.code}...`"></textarea>
              </div>

              <!-- Calculation Button -->
              <div v-if="selectedParameter.requires_calculation" class="pt-2">
                <button @click="openCalculationForParameter"
                        type="button"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2">
                  <CalculatorIcon class="h-5 w-5" />
                  Calcular Parâmetro
                </button>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
            <button @click="$emit('close')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2">
              Cancelar
            </button>
            
            <button @click="saveIndividualResult"
                    :disabled="!canSave"
                    :class="[
                      'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                      !canSave
                        ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                        : actionButtonClass
                    ]">
              <CheckIcon class="h-5 w-5" />
              {{ saveButtonText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { 
  CheckIcon,
  CalculatorIcon,
  ScaleIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
  sampleId: Number,
  parameters: Array,
  action: String,
  existingResults: Object
})

const emit = defineEmits(['close', 'saved', 'open-calculation'])

// State
const selectedParameterId = ref('')
const resultValue = ref('')
const uncertaintyValue = ref('')
const notes = ref('')

// Computed properties
const actionText = computed(() => {
  switch (props.action) {
    case 'analyze': return 'Inserção'
    case 'verify': return 'Verificação'
    case 'approve': return 'Aprovação'
    default: return 'Edição'
  }
})

const valueLabel = computed(() => {
  switch (props.action) {
    case 'analyze': return 'Resultado'
    case 'verify': return 'Valor Verificado'
    case 'approve': return 'Valor Aprovado'
    default: return 'Valor'
  }
})

const saveButtonText = computed(() => {
  switch (props.action) {
    case 'analyze': return 'Inserir Resultado'
    case 'verify': return 'Verificar Resultado'
    case 'approve': return 'Aprovar Resultado'
    default: return 'Salvar'
  }
})

const actionButtonClass = computed(() => {
  switch (props.action) {
    case 'analyze': return 'bg-blue-900 text-white hover:bg-blue-800 focus:ring-blue-900'
    case 'verify': return 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-600'
    case 'approve': return 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-600'
    default: return 'bg-blue-900 text-white hover:bg-blue-800 focus:ring-blue-900'
  }
})

const filteredParameters = computed(() => {
  if (!props.parameters) return []
  
  return props.parameters.filter(param => {
    // For analyze: show parameters without inserted value
    if (props.action === 'analyze') {
      return !param.inserted_value || param.inserted_value === '' || param.requires_calculation
    }
    // For verify: show parameters with inserted value but without verification
    if (props.action === 'verify') {
      return param.inserted_value && (!param.verified_value || param.verified_value === '')
    }
    // For approve: show parameters with verification but without approval
    if (props.action === 'approve') {
      return param.verified_value && (!param.approved_value || param.approved_value === '')
    }
    return true
  })
})

const selectedParameter = computed(() => {
  if (!selectedParameterId.value) return null
  return props.parameters.find(param => getParameterUniqueId(param) === selectedParameterId.value)
})

const hasExistingValue = (param) => {
  switch (props.action) {
    case 'analyze': return param.inserted_value
    case 'verify': return param.verified_value
    case 'approve': return param.approved_value
    default: return false
  }
}

const getExistingValueText = (param) => {
  const value = hasExistingValue(param)
  if (!value) return ''
  return `${value} ${param.unit_label || ''}`.trim()
}

const canSave = computed(() => {
  if (!selectedParameterId.value) return false
  
  // For calculated parameters, value can be empty initially
  if (selectedParameter.value?.requires_calculation) {
    return true
  }
  
  return resultValue.value.trim() !== ''
})

// Methods
const getParameterUniqueId = (param) => {
  return param.result_id || param.id || param.parameter_id?.value || param.parameter_id?.code
}

const openCalculationForParameter = () => {
  if (!selectedParameter.value) return
  
  // Emit to parent to open calculation for this specific parameter
  emit('open-calculation', selectedParameter.value)
}

const saveIndividualResult = () => {
  if (!selectedParameter.value || (!canSave.value && !selectedParameter.value.requires_calculation)) return

  // Format parameter_id correctly
  const parameterId = selectedParameter.value.parameter_id?.value || 
                     selectedParameter.value.parameter_id?.id ||
                     selectedParameter.value.parameter_id

  const resultData = {
    result_id: selectedParameter.value.result_id,
    sample_id: props.sampleId,
    parameter_id: parameterId,
    parameter_label: selectedParameter.value.parameter_label || selectedParameter.value.parameter_id?.name,
    code_id: selectedParameter.value.code_id?.value || selectedParameter.value.code_id,
    code_label: selectedParameter.value.code_label,
    product_id: selectedParameter.value.product_id?.value || selectedParameter.value.product_id,
    product_label: selectedParameter.value.product_label,
    profile_id: selectedParameter.value.profile_id,
    unit_id: selectedParameter.value.unit_id?.value || selectedParameter.value.unit_id,
    unit_label: selectedParameter.value.unit_label,
    type_id: selectedParameter.value.type_id?.value || selectedParameter.value.type_id,
    category_label: selectedParameter.value.category_label,
    
    // Set value based on action
    ...(props.action === 'analyze' && { 
      inserted_value: resultValue.value,
      inserted_date: new Date().toISOString(),
      inserted_by: 'current_user',
      inserted_by_id: 1,
      insertion_notes: notes.value || null
    }),
    
    ...(props.action === 'verify' && { 
      verified_value: resultValue.value,
      verified_date: new Date().toISOString(),
      verified_by: 'current_user',
      verified_by_id: 1,
      verification_notes: notes.value || null
    }),
    
    ...(props.action === 'approve' && { 
      approved_value: resultValue.value,
      approved_date: new Date().toISOString(),
      approved_by: 'current_user',
      approved_by_id: 1,
      approval_notes: notes.value || null
    }),
    
    // Common fields
    uncertainty_value: uncertaintyValue.value || null,
    min_ref_value: selectedParameter.value.min_ref_value,
    max_ref_value: selectedParameter.value.max_ref_value,
    ref_val_origin: selectedParameter.value.ref_val_origin,
    requires_calculation: selectedParameter.value.requires_calculation || false,
    calculation_metadata: selectedParameter.value.calculation_metadata || null,
    is_calculated: selectedParameter.value.requires_calculation || false,
    insertion_method: 'individual',
    
    // Microbiology fields
    sumC: selectedParameter.value.sumC || 0,
    volume: selectedParameter.value.volume || 1,
    n1: selectedParameter.value.n1 || 0,
    n2: selectedParameter.value.n2 || 0,
    dilution: selectedParameter.value.dilution || 0,
    d1: selectedParameter.value.d1 || 0,
    d2: selectedParameter.value.d2 || 0,
    cfu1: selectedParameter.value.cfu1 || 0,
    cfu2: selectedParameter.value.cfu2 || 0,
  }

  console.log('Saving individual result:', resultData)
  emit('saved', resultData)
  emit('close')
}

// Initialize with existing value if available
watch(selectedParameter, (newParam) => {
  if (newParam) {
    switch (props.action) {
      case 'analyze':
        resultValue.value = newParam.inserted_value || ''
        uncertaintyValue.value = newParam.uncertainty_value || ''
        notes.value = newParam.insertion_notes || ''
        break
      case 'verify':
        resultValue.value = newParam.verified_value || newParam.inserted_value || ''
        uncertaintyValue.value = newParam.uncertainty_value || ''
        notes.value = newParam.verification_notes || ''
        break
      case 'approve':
        resultValue.value = newParam.approved_value || newParam.verified_value || ''
        uncertaintyValue.value = newParam.uncertainty_value || ''
        notes.value = newParam.approval_notes || ''
        break
    }
  } else {
    resultValue.value = ''
    uncertaintyValue.value = ''
    notes.value = ''
  }
}, { immediate: true })
</script>