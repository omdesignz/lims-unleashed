<!-- resources/js/Components/results/IndividualResultEntry.vue -->
<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-auto rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_30px_120px_rgba(7,17,15,0.45)] dark:border-[#25443c] dark:bg-[#07110f]">
      <div class="p-6">
        <div class="mb-6 flex items-center justify-between gap-4 border-b border-[#ded3bf] pb-4 dark:border-[#25443c]">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">Resultado individual</p>
            <h3 class="mt-1 text-xl font-bold text-[#17231f] dark:text-[#f7f1e7]">
              {{ actionText }} Individual
            </h3>
          </div>
          <button @click="$emit('close')" 
                  class="rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="space-y-6">
          <!-- Parameter Selection -->
          <div>
            <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
              Selecionar Parâmetro
            </label>
            <select v-model="selectedParameterId" 
                    class="block w-full rounded-2xl border border-slate-300/90 bg-white/95 py-3 pl-4 pr-10 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-white/50 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800/60">
              <option value="">Selecione um parâmetro</option>
              <option v-for="param in filteredParameters" 
                      :key="getParameterUniqueId(param)"
                      :value="getParameterUniqueId(param)"
                      :disabled="param.inserted_value && props.action === 'analyze'">
                {{ param.parameter_id?.code || 'N/A' }} - {{ param.parameter_id?.name || 'Sem nome' }}
                <span v-if="hasExistingValue(param)" class="text-slate-500 dark:text-slate-400">
                  ({{ getExistingValueText(param) }})
                </span>
              </option>
            </select>
            <p v-if="selectedParameter?.requires_calculation" class="mt-2 flex items-center gap-1 text-sm font-medium text-amber-700 dark:text-amber-300">
              <CalculatorIcon class="h-4 w-4" />
              Este parâmetro requer cálculo automático
            </p>
          </div>

          <!-- Result Input -->
          <div v-if="selectedParameter" class="space-y-4">
            <!-- Parameter Info Card -->
            <div class="rounded-2xl border border-slate-200 bg-white/70 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h4 class="font-semibold text-[#17231f] dark:text-[#f7f1e7]">{{ selectedParameter.parameter_id?.name }}</h4>
                  <p class="text-sm text-slate-600 dark:text-slate-400">{{ selectedParameter.parameter_id?.code }}</p>
                  
                  <!-- Reference Range -->
                  <div v-if="selectedParameter.min_ref_value || selectedParameter.max_ref_value"
                       class="mt-2 flex items-center gap-1 text-xs font-medium text-primary-700 dark:text-primary-300">
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
                      class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-500/15 dark:text-amber-100">
                  <CalculatorIcon class="h-3 w-3" />
                  Calculado
                </span>
              </div>
              
              <!-- Unit Info -->
              <div v-if="selectedParameter.unit_label" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Unidade: {{ selectedParameter.unit_label }}
              </div>
            </div>

            <!-- Value Input Section -->
            <div class="space-y-4">
              <!-- Value Input -->
              <div>
                <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                  {{ valueLabel }}
                  <span v-if="selectedParameter.unit_label" class="ml-1 text-slate-500 dark:text-slate-400">
                    ({{ selectedParameter.unit_label }})
                  </span>
                </label>
                <div class="relative">
                  <input v-model="resultValue"
                         type="text"
                         :disabled="selectedParameter.requires_calculation"
                         class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 disabled:bg-slate-100 disabled:text-slate-500 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500 dark:disabled:bg-slate-800"
                         :placeholder="selectedParameter.requires_calculation ? 'Valor calculado automaticamente' : `Insira valor para ${selectedParameter.parameter_id?.code}`">
                  <div v-if="selectedParameter.requires_calculation" 
                       class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <CalculatorIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                  </div>
                </div>
                <p v-if="selectedParameter.requires_calculation" class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                  Use a calculadora para determinar este valor
                </p>
              </div>

              <!-- Uncertainty Input -->
              <div>
                <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                  Incerteza (±)
                  <span v-if="selectedParameter.unit_label" class="ml-1 text-slate-500 dark:text-slate-400">
                    ({{ selectedParameter.unit_label }})
                  </span>
                </label>
                <input v-model="uncertaintyValue"
                       type="text"
                       class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
                       placeholder="Ex: 0.1">
              </div>

              <!-- Notes -->
              <div>
                <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                  Observações
                </label>
                <textarea v-model="notes"
                          rows="3"
                          class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
                          :placeholder="`Observações sobre ${selectedParameter.parameter_id?.code}...`"></textarea>
              </div>

              <!-- Calculation Button -->
              <div v-if="selectedParameter.requires_calculation" class="pt-2">
                <button @click="openCalculationForParameter"
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2 dark:focus:ring-offset-[#07110f]">
                  <CalculatorIcon class="h-5 w-5" />
                  Calcular Parâmetro
                </button>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end gap-3 border-t border-[#ded3bf] pt-6 dark:border-[#25443c]">
            <button @click="$emit('close')"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-[#07110f]">
              Cancelar
            </button>
            
            <button @click="saveIndividualResult"
                    :disabled="!canSave"
                    :class="[
                      'inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                      !canSave
                        ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
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
    case 'analyze': return 'rounded-full bg-primary-900 text-white hover:bg-primary-800 focus:ring-primary-600'
    case 'verify': return 'rounded-full bg-amber-600 text-white hover:bg-amber-700 focus:ring-amber-600'
    case 'approve': return 'rounded-full bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-600'
    default: return 'rounded-full bg-primary-900 text-white hover:bg-primary-800 focus:ring-primary-600'
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
