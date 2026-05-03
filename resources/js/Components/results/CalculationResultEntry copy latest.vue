<!-- resources/js/Components/results/CalculationResultEntry.vue -->
<template>
  <div class="result-entry-modal space-y-8">
    <!-- MODAL HEADER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CalculatorIcon class="h-7 w-7 text-blue-900" />
            Resultados de Parâmetros Calculados
          </h1>
          <p class="mt-2 text-gray-600">
            Modo: <span class="font-semibold text-blue-900">{{ calculationMode === 'single' ? 'Individual' : 'Em Lote' }}</span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button @click="calculationMode = 'single'"
                  :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                    calculationMode === 'single'
                      ? 'bg-blue-900 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  ]">
            <DocumentPlusIcon class="h-4 w-4" />
            Individual
          </button>
          <button @click="calculationMode = 'batch'"
                  :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                    calculationMode === 'batch'
                      ? 'bg-blue-900 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  ]">
            <DocumentTextIcon class="h-4 w-4" />
            Em Lote
          </button>
        </div>
      </div>
    </div>

    <!-- SINGLE PARAMETER CALCULATION MODE -->
    <div v-if="calculationMode === 'single'" class="space-y-6">
      <!-- PARAMETER SELECTION -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Calcular Parâmetro Individual
        </h3>
        
        <div class="space-y-4">
          <!-- Parameter Selector -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Selecione um parâmetro para calcular
            </label>
            <select v-model="selectedSingleParameter"
                    class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
              <option value="">Selecione um parâmetro</option>
              <option v-for="param in availableSingleParameters" 
                      :key="param.parameter_id.code"
                      :value="param.parameter_id.code"
                      
                      >
                {{ param.parameter_id.code }} - {{ param.parameter_id.name }}
                <!-- <span v-if="isCalculated(param.parameter_id.code)">(✓ {{ results[param.parameter_id.code] }})</span> -->
                 <span v-if="results[param.parameter_id.code]">(✓ {{ results[param.parameter_id.code] }})</span>
              </option>
            </select>
          </div>

          <!-- Calculation Status -->
          <div v-if="selectedSingleParameter" class="p-4 bg-blue-50 rounded-lg">

            <div class="flex items-center gap-3">
              <div class="flex-1">
                <h4 class="font-semibold text-gray-900">{{ selectedSingleParameterObj?.parameter_id?.name }}</h4>
                <p class="text-sm text-gray-600">{{ selectedSingleParameterObj?.parameter_id?.code }}</p>
              </div>
              <span class="text-sm text-blue-900 font-medium">
                {{ getCalculationStatus(selectedSingleParameterObj).complete ? 'Pronto para calcular' : 'Aguardando variáveis' }}
              </span>
            </div>
            
          </div>

        </div>
      </div>

      <!-- INPUT VARIABLES SECTION -->
      <div v-if="selectedSingleParameterObj" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h3 class="text-lg font-semibold text-white flex items-center gap-2">
            <VariableIcon class="h-5 w-5" />
            Variáveis de Entrada
          </h3>
        </div>
        
        <div class="p-6">
          <div v-if="singleParameterInputs.length === 0" class="py-8 text-center">
            <VariableIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              Nenhuma variável necessária
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              Este parâmetro não requer variáveis de entrada
            </p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="variableName in singleParameterInputs" :key="variableName"
                class="group bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 p-4">
              
              <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                <HashtagIcon class="h-4 w-4 text-blue-900" />
                {{ variableName }}
                <span class="text-xs text-gray-500 ml-1">(Variável de Fórmula)</span>
              </label>
              
              <div class="relative">
                <input
                  v-model="singleCalcInputs[variableName]" 
                  type="number" 
                  step="0.0001"
                  @input="calculateSingleParameter"
                  class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  :class="{
                    'ring-orange-500 bg-orange-50': !singleCalcInputs[variableName] || singleCalcInputs[variableName] === '',
                    'ring-green-500 bg-green-50': singleCalcInputs[variableName] && singleCalcInputs[variableName] !== ''
                  }"
                  placeholder="Insira o valor..."
                />
                <div v-if="singleCalcInputs[variableName] && singleCalcInputs[variableName] !== ''"
                    class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <CheckCircleIcon class="h-5 w-5 text-green-600" />
                </div>
              </div>
              
              <!-- Existing value from results -->
              <div v-if="results[variableName] && singleCalcInputs[variableName] !== results[variableName]"
                   class="mt-2 text-xs text-gray-500">
                Valor existente: {{ results[variableName] }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CALCULATION RESULT -->
      <div v-if="selectedSingleParameterObj" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Resultado</h3>
          
          <!-- Override Toggle -->
          <label class="flex items-center gap-2 text-sm">
            <input 
              v-model="singleParameterOverride" 
              type="checkbox" 
              class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
            >
            <span>Sobrescrever valor calculado</span>
          </label>
        </div>
        
        <!-- Manual Input when overridden -->
        <div v-if="singleParameterOverride" class="mb-6">
          <input 
            v-model="singleCalcManualValue"
            type="text"
            class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
            placeholder="Insira valor manual"
          />
        </div>
        
        <!-- Calculated Result Display -->
        <div v-else-if="singleCalculationResult" 
             class="p-6 bg-gradient-to-r from-green-50 to-green-100 border border-green-200 rounded-lg mb-6">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-2">Resultado Calculado</p>
            <p class="text-3xl font-bold text-green-900">
              {{ singleCalculationResult }}
              <span v-if="selectedSingleParameterObj.formula?.output_unit" 
                    class="text-xl font-normal text-gray-600">
                {{ selectedSingleParameterObj.formula.output_unit }}
              </span>
            </p>
          </div>
          
          <!-- Formula Display -->
          <div class="mt-4 pt-4 border-t border-green-200">
            <p class="text-xs text-gray-500 mb-2">Fórmula aplicada:</p>
            <code class="text-sm font-mono bg-white p-2 rounded border border-gray-200 block">
              {{ selectedSingleParameterObj.formula?.expression }}
            </code>
          </div>
        </div>
        
        <!-- Missing Inputs Warning -->
        <div v-if="getMissingInputs(selectedSingleParameterObj).length > 0 && !singleParameterOverride"
             class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-6">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 mt-0.5" />
            <div>
              <p class="text-sm font-medium text-yellow-900">Aguardando variáveis</p>
              <p class="text-sm text-yellow-700 mt-1">
                Insira valores para: {{ getMissingInputs(selectedSingleParameterObj).join(', ') }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
          <button @click="clearSingleCalculation"
                  class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2">
            Limpar
          </button>
          
          <button @click="applySingleCalculation"
                  :disabled="!canApplySingleCalculation"
                  :class="[
                    'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    !canApplySingleCalculation
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : 'bg-blue-900 text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                  ]">
            <CheckIcon class="h-5 w-5" />
            Aplicar Resultado
          </button>
        </div>
      </div>

      <!-- NEXT PARAMETER SUGGESTION -->
      <div v-if="nextAvailableParameter" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-900">Próximo parâmetro sugerido</h3>
            <p class="text-sm text-gray-600">{{ nextAvailableParameter.parameter_id.name }}</p>
          </div>
          <button @click="selectNextParameter"
                  class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
            Calcular Agora
          </button>
        </div>
      </div>
    </div>

    <!-- BATCH CALCULATION MODE (Existing) -->
    <div v-else class="space-y-6">
      <!-- ... Existing batch calculation content ... -->
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full" 
                 :class="canSave ? 'bg-green-500' : 'bg-yellow-500'"></div>
            <span>
              {{ calculationMode === 'single' ? '1 parâmetro selecionado' : `${calculatedCount} de ${allCalculatedParameters.length} calculados` }}
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="$emit('close')"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <XMarkIcon class="h-5 w-5" />
          Fechar
        </button>
        
        <button v-if="calculationMode === 'batch'"
                @click="saveResults" 
                :disabled="!canSave"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                  !canSave
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-blue-900 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
        >
          <CheckIcon class="h-5 w-5" />
          Salvar Todos
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { 
  CalculatorIcon,
  VariableIcon,
  HashtagIcon,
  CheckCircleIcon,
  XMarkIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  DocumentPlusIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
  parameters: Array,
  existingResults: Object,
  action: String
})

const emit = defineEmits(['calculatedResults', 'close'])

// State for calculation mode
const calculationMode = ref('single') // 'single' or 'batch'
const selectedSingleParameter = ref('')
const singleCalcInputs = ref({})
const singleCalculationResult = ref(null)
const singleParameterOverride = ref(false)
const singleCalcManualValue = ref('')

// Existing state for batch mode
const results = ref({})
const overrides = ref({})
const uncertaintyValues = ref({})
const insertionNotes = ref({})
const verificationNotes = ref({})
const approvalNotes = ref({})
const calculationInProgress = ref(false)
const minRefValues = ref({})
const maxRefValues = ref({})

const allCalculatedParameters = computed(() => props.parameters.filter(p => p.parameter_id.active))

// Single parameter mode computed properties
const availableSingleParameters = computed(() => {
  return allCalculatedParameters.value.filter(param => 
    !overrides.value[param.parameter_id.code]
  )
})

const selectedSingleParameterObj = computed(() => {
  return allCalculatedParameters.value.find(
    param => param.parameter_id.code === selectedSingleParameter.value
  )
})

const singleParameterInputs = computed(() => {
  if (!selectedSingleParameterObj.value) return []
  return getCalculationRequirements(selectedSingleParameterObj.value)
})

const canApplySingleCalculation = computed(() => {
  if (singleParameterOverride.value) {
    return singleCalcManualValue.value && singleCalcManualValue.value.trim() !== ''
  }
  
  if (!selectedSingleParameter.value) return false
  if (!selectedSingleParameterObj.value) return false
  
  const missing = getMissingInputs(selectedSingleParameterObj.value)
  if (missing.length > 0) return false
  
  return singleCalculationResult.value !== null
})

const nextAvailableParameter = computed(() => {
  const currentIndex = availableSingleParameters.value.findIndex(
    p => p.parameter_id.code === selectedSingleParameter.value
  )
  
  if (currentIndex < availableSingleParameters.value.length - 1) {
    return availableSingleParameters.value[currentIndex + 1]
  }
  return null
})

// Batch mode computed properties
const allRequiredVariables = computed(() => {
  const variables = new Set()
  
  allCalculatedParameters.value.forEach(parameter => {
    if (parameter.formula?.variables) {
      parameter.formula.variables.forEach(variable => {
        variables.add(variable.name)
      })
    }
  })
  
  return Array.from(variables)
})

const isCalculated = (parameterCode) => {
  return results.value[parameterCode] !== undefined && 
         results.value[parameterCode] !== null &&
         !isNaN(results.value[parameterCode])
}

const calculatedCount = computed(() =>
  allCalculatedParameters.value.filter(p => isCalculated(p.parameter_id.code)).length
)

const canCalculateParameter = (parameter) => {
  if (!parameter.formula) return false
  
  const requiredInputs = getCalculationRequirements(parameter)
  const availableInputs = requiredInputs.filter(input => 
    results.value[input] !== undefined && 
    results.value[input] !== null &&
    results.value[input] !== ''
  )
  
  return availableInputs.length === requiredInputs.length
}

const getCalculationRequirements = (parameter) => {
  if (!parameter.formula) return []
  return parameter.formula.variables?.map(v => v.name) || []
}

const getMissingInputs = (parameter) => {
  if (!parameter || !parameter.formula) return []
  
  const required = getCalculationRequirements(parameter)
  const missing = required.filter(input => 
    singleCalcInputs.value[input] === undefined || 
    singleCalcInputs.value[input] === null ||
    singleCalcInputs.value[input] === ''
  )
  
  return missing
}

const getCalculationStatus = (parameter) => {
  const missing = getMissingInputs(parameter)
  
  return {
    complete: missing.length === 0,
    missing: missing
  }
}

const readyCalculations = computed(() => {
  return allCalculatedParameters.value.filter(param => 
    getCalculationStatus(param).complete
  ).length
})

const canSave = computed(() => {
  const totalParameters = allCalculatedParameters.value.length
  const enteredResults = Object.keys(results.value).filter(key => 
    results.value[key] !== undefined && 
    results.value[key] !== null &&
    results.value[key] !== '' &&
    allCalculatedParameters.value.some(p => p.parameter_id.code === key)
  ).length
  return enteredResults >= totalParameters
})

// Single parameter calculation methods
const calculateSingleParameter = async () => {
  if (!selectedSingleParameterObj.value || singleParameterOverride.value) return
  
  const param = selectedSingleParameterObj.value
  
  // Check if all required inputs are available
  const required = getCalculationRequirements(param)
  const missing = required.filter(input => 
    !singleCalcInputs.value[input] || singleCalcInputs.value[input] === ''
  )
  
  if (missing.length > 0) {
    singleCalculationResult.value = null
    return
  }
  
  calculationInProgress.value = true
  
  try {
    const vars = {}
    required.forEach(input => {
      const value = parseFloat(singleCalcInputs.value[input])
      vars[input] = isNaN(value) ? 0 : value
    })

    const context = {
      ...vars,
      Math: {
        sqrt: Math.sqrt,
        log: Math.log,
        log10: (x) => Math.log10(x),
        exp: Math.exp,
        abs: Math.abs,
        round: Math.round,
        ceil: Math.ceil,
        floor: Math.floor,
        max: Math.max,
        min: Math.min,
        pow: Math.pow,
        PI: Math.PI,
        E: Math.E
      }
    }

    const functionBody = `return ${param.formula.expression}`
    const safeEval = new Function(...Object.keys(context), functionBody)
    const numericValue = safeEval(...Object.values(context))

    if (isNaN(numericValue) || !isFinite(numericValue)) {
      throw new Error('Invalid calculation result')
    }

    const decimalPlaces = param.formula.decimal_places || 2
    singleCalculationResult.value = parseFloat(numericValue).toFixed(decimalPlaces)
    
  } catch (error) {
    console.error(`❌ Error calculating ${param.parameter_id.code}:`, error)
    singleCalculationResult.value = null
  } finally {
    calculationInProgress.value = false
  }
}

const applySingleCalculation = () => {
  if (!selectedSingleParameter.value) return
  
  const paramCode = selectedSingleParameter.value
  
  // Update the main results object
  if (singleParameterOverride.value) {
    results.value[paramCode] = singleCalcManualValue.value
    overrides.value[paramCode] = true
  } else {
    results.value[paramCode] = singleCalculationResult.value
    overrides.value[paramCode] = false
  }
  
  // Also update input variables in the main results
  Object.keys(singleCalcInputs.value).forEach(key => {
    if (singleCalcInputs.value[key]) {
      results.value[key] = singleCalcInputs.value[key]
    }
  })
  
  // Clear for next calculation
  clearSingleCalculation()
  
  // If there's a next parameter, auto-select it
  if (nextAvailableParameter.value) {
    selectNextParameter()
  }
}

const clearSingleCalculation = () => {
  selectedSingleParameter.value = ''
  singleCalcInputs.value = {}
  singleCalculationResult.value = null
  singleParameterOverride.value = false
  singleCalcManualValue.value = ''
}

const selectNextParameter = () => {
  if (nextAvailableParameter.value) {
    selectedSingleParameter.value = nextAvailableParameter.value.parameter_id.code
  }
}

// Watch for parameter selection changes
watch(selectedSingleParameter, (newParamCode) => {
  if (newParamCode && selectedSingleParameterObj.value) {
    const param = selectedSingleParameterObj.value
    const required = getCalculationRequirements(param)
    
    // Pre-fill inputs from existing results
    required.forEach(variable => {
      if (results.value[variable]) {
        singleCalcInputs.value[variable] = results.value[variable]
      }
    })
    
    // If all inputs are available, calculate immediately
    if (required.every(v => results.value[v])) {
      calculateSingleParameter()
    }
  } else {
    singleCalcInputs.value = {}
    singleCalculationResult.value = null
    singleParameterOverride.value = false
  }
})

// Initialize with existing data
watch(() => props.existingResults, (newResults) => {
  if (newResults) {
    results.value = { ...newResults }
    
    // Initialize overrides
    props.parameters.forEach(param => {
      const code = param.parameter_id?.code
      if (code && param.manual_override) { 
        overrides.value[code] = true
      }

      uncertaintyValues.value[code] = param.uncertainty_value || null
      minRefValues.value[code] = param.min_ref_value || 0
      maxRefValues.value[code] = param.max_ref_value || 0
      insertionNotes.value[code] = param.insertion_notes || null
      verificationNotes.value[code] = param.verification_notes || null
      approvalNotes.value[code] = param.approval_notes || null
    })
  }
}, { immediate: true })

// Batch calculation methods (existing)
const onInputChange = async (inputName) => {
  const dependentParams = allCalculatedParameters.value.filter(p => 
    p.formula && getCalculationRequirements(p).includes(inputName)
  )
  
  for (const depParam of dependentParams) {
    await calculateParameter(depParam)
  }
}

const calculateParameter = async (parameter) => {
  if (overrides.value[parameter.parameter_id.code]) {
    return
  }

  if (!parameter.formula) {
    console.error(`No formula found for parameter: ${parameter.parameter_id.code}`)
    return
  }

  const requiredInputs = getCalculationRequirements(parameter)
  if (requiredInputs.length !== requiredInputs.filter(input => results.value[input] !== undefined && results.value[input] !== null && results.value[input] !== '').length) {
    return
  }

  calculationInProgress.value = true

  try {
    const vars = {}
    requiredInputs.forEach(input => {
      const value = parseFloat(results.value[input])
      vars[input] = isNaN(value) ? 0 : value
    })

    const context = {
      ...vars,
      Math: {
        sqrt: Math.sqrt,
        log: Math.log,
        log10: (x) => Math.log10(x),
        exp: Math.exp,
        abs: Math.abs,
        round: Math.round,
        ceil: Math.ceil,
        floor: Math.floor,
        max: Math.max,
        min: Math.min,
        pow: Math.pow,
        PI: Math.PI,
        E: Math.E
      }
    }

    const functionBody = `return ${parameter.formula.expression}`
    const safeEval = new Function(...Object.keys(context), functionBody)
    const numericValue = safeEval(...Object.values(context))

    if (isNaN(numericValue) || !isFinite(numericValue)) {
      throw new Error('Invalid calculation result')
    }

    const decimalPlaces = parameter.formula.decimal_places || 2
    results.value[parameter.parameter_id.code] = parseFloat(numericValue).toFixed(decimalPlaces)
    
  } catch (error) {
    console.error(`❌ Error calculating ${parameter.parameter_id.code}:`, error)
    results.value[parameter.parameter_id.code] = null
  } finally {
    calculationInProgress.value = false
  }
}

const calculateAll = async () => {
  for (const parameter of allCalculatedParameters.value) {
    if (!isCalculated(parameter.parameter_id.code) && !overrides.value[parameter.parameter_id.code] && canCalculateParameter(parameter)) {
      await calculateParameter(parameter)
    }
  }
}

const generateCalculationMetadata = () => {
    const metadata = {};

    allCalculatedParameters.value.forEach(param => {
        const paramCode = param.parameter_id.code;
        
        if (isCalculated(paramCode) || overrides.value[paramCode]) {
            
            const inputValues = {}
            
            const requiredVariables = param.formula?.variables?.map(v => v.name) || [];

            requiredVariables.forEach(variableName => {
                inputValues[variableName] = results.value[variableName]
            })
            
            metadata[paramCode] = {
                inputs: inputValues, 
                formula: {
                    id: param.formula?.id,
                    expression: param.formula?.expression,
                    name: param.formula?.name
                },
                calculated_at: new Date().toISOString(),
                calculation_method: overrides.value[paramCode] ? 'manual' : 'automated',
                manual_override: overrides.value[paramCode] || false
            }
        }
    });

    return metadata;
}

const saveResults = () => {
  calculateAll();

  const calculationMetadata = generateCalculationMetadata();

  const comprehensivePayload = {
      results: Object.keys(results.value).reduce((acc, key) => {
          acc[key] = results.value[key];
          
          const isCalculatedParam = allCalculatedParameters.value.some(p => p.parameter_id.code === key);
          if (isCalculatedParam) {
              acc[`${key}_uncertainty_value`] = uncertaintyValues.value[key] || null;
              acc[`${key}_min_ref_value`] = minRefValues.value[key] || 0;
              acc[`${key}_max_ref_value`] = maxRefValues.value[key] || 0;
              acc[`${key}_insertion_notes`] = insertionNotes.value[key] || null;
              acc[`${key}_verification_notes`] = verificationNotes.value[key] || null;
              acc[`${key}_approval_notes`] = approvalNotes.value[key] || null;
          }
          return acc;
      }, {}),
      overrides: overrides.value, 
      metadata: calculationMetadata, 
  };

  emit('calculatedResults', comprehensivePayload);
}
</script>

<style scoped>
.result-entry-modal {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.result-entry-modal::-webkit-scrollbar {
  width: 8px;
}

.result-entry-modal::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.result-entry-modal::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.result-entry-modal::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>