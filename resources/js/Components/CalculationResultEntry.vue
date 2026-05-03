<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  sample: Object,
  parameters: Array, // ASSUMED: This array ONLY contains parameters that require calculation.
  existingResults: Object
})

const emit = defineEmits(['calculatedResults']) // Defined emit

const results = ref({})
const overrides = ref({})
const calculationInProgress = ref(false)

// All parameters are now treated as "calculated parameters" for the purpose of the UI display,
// but their required inputs (variables) must still be determined.
const allCalculatedParameters = computed(() => props.parameters.filter(p => p.parameter_id.active))

// --- Variable Dependency Logic ---

// Get ALL unique variable names needed for ALL calculations
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

// --- Status Tracking ---

// Track calculation status
const isCalculated = (parameterCode) => {
  return results.value[parameterCode] !== undefined && 
         results.value[parameterCode] !== null &&
         !isNaN(results.value[parameterCode])
}

const calculatedCount = computed(() =>
  allCalculatedParameters.value.filter(p => isCalculated(p.parameter_id.code)).length
)

// Check if a parameter can be calculated
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

// Get calculation requirements (the list of variable names) for a parameter
const getCalculationRequirements = (parameter) => {
  if (!parameter.formula) return []
  return parameter.formula.variables?.map(v => v.name) || []
}

// Get missing inputs for a parameter (returns variable names)
const getMissingInputs = (parameter) => {
  if (!parameter || !parameter.formula) return []
  
  const required = getCalculationRequirements(parameter)
  const missing = required.filter(input => 
    results.value[input] === undefined || 
    results.value[input] === null ||
    results.value[input] === ''
  )
  
  // Note: We cannot easily map the variable name back to a human-readable parameter name 
  // because the input parameter object itself is not in props.parameters. 
  // We return the raw variable name.
  return missing
}

// Check if all variables for a calculation are available
const getCalculationStatus = (parameter) => {
  const missing = getMissingInputs(parameter)
  
  return {
    complete: missing.length === 0,
    missing: missing
  }
}

// Get the count of ready calculations
const readyCalculations = computed(() => {
  return allCalculatedParameters.value.filter(param => 
    getCalculationStatus(param).complete
  ).length
})

const canSave = computed(() => {
  const totalParameters = allCalculatedParameters.value.length
  // Check if ALL calculated parameters have a result (calculated or overridden)
  const enteredResults = Object.keys(results.value).filter(key => 
    results.value[key] !== undefined && 
    results.value[key] !== null &&
    results.value[key] !== '' &&
    // Only count if it's actually one of the calculated parameters
    allCalculatedParameters.value.some(p => p.parameter_id.code === key)
  ).length
  return enteredResults >= totalParameters
})

// --- Calculation Logic ---

// This function now tracks changes ONLY to the input variables (which are stored in results.value)
// and recalculates dependent calculated parameters.
const onInputChange = async (inputName) => {
  // Find calculated parameters that depend on this input variable name
  const dependentParams = allCalculatedParameters.value.filter(p => 
    p.formula && getCalculationRequirements(p).includes(inputName)
  )
  
  // Recalculate dependent parameters
  for (const depParam of dependentParams) {
    await calculateParameter(depParam)
  }
}

// Calculate a specific parameter
const calculateParameter = async (parameter) => {
  if (overrides.value[parameter.parameter_id.code]) {
    return // Skip if manually overridden
  }

  if (!parameter.formula) {
    console.error(`No formula found for parameter: ${parameter.parameter_id.code}`)
    return
  }

  // Check if we have all required inputs
  const requiredInputs = getCalculationRequirements(parameter)
  if (requiredInputs.length !== requiredInputs.filter(input => results.value[input] !== undefined && results.value[input] !== null && results.value[input] !== '').length) {
    return // Not all inputs available yet
  }

  calculationInProgress.value = true

  try {
    const vars = {}
    requiredInputs.forEach(input => {
      const value = parseFloat(results.value[input])
      vars[input] = isNaN(value) ? 0 : value
    })

    // Safe JavaScript evaluation
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
    
    console.log(`✅ Calculated ${parameter.parameter_id.code}:`, results.value[parameter.parameter_id.code])
    
  } catch (error) {
    console.error(`❌ Error calculating ${parameter.parameter_id.code}:`, error)
    results.value[parameter.parameter_id.code] = null
  } finally {
    calculationInProgress.value = false
  }
}

// Calculate all possible parameters
const calculateAll = async () => {
  for (const parameter of allCalculatedParameters.value) {
    if (!isCalculated(parameter.parameter_id.code) && !overrides.value[parameter.parameter_id.code] && canCalculateParameter(parameter)) {
      await calculateParameter(parameter)
    }
  }
}

// --- Display Helpers ---

// Format result for display
const formatResult = (parameter, value) => {
  if (value === null || value === undefined || value === '') return '-'
  
  const decimalPlaces = parameter.formula?.decimal_places || 2
  const formattedValue = parseFloat(value).toFixed(decimalPlaces)
  const unit = parameter.formula?.output_unit || parameter.unit_label || ''
  
  return unit ? `${formattedValue} ${unit}` : formattedValue
}

// Get formula display expression
const getFormulaExpression = (parameter) => {
  return parameter.formula?.formula_expression || parameter.formula_expression || 'No formula defined'
}

// --- Initialization and Watchers ---

// Initialize with existing results
watch(() => props.existingResults, (newResults) => {
  if (newResults) {
    results.value = { ...newResults }
    
    // Recalculate any formulas that have all required inputs
    setTimeout(() => {
      allCalculatedParameters.value.forEach(parameter => {
        if (canCalculateParameter(parameter)) {
          calculateParameter(parameter)
        }
      })
    }, 100)
  }
}, { immediate: true })

// Watch for changes in ANY result entry (input or calculated) and trigger dependency checks
watch(results, (newResults, oldResults) => {
  // Check if any REQUIRED input variable values changed
  const changedInputs = allRequiredVariables.value.filter(key => 
    newResults[key] !== oldResults[key]
  )
  
  if (changedInputs.length > 0) {
    changedInputs.forEach(inputName => {
      onInputChange(inputName)
    })
  }
}, { deep: true })


const generateCalculationMetadata = () => {
    const metadata = {};

    allCalculatedParameters.value.forEach(param => {
        const paramCode = param.parameter_id.code;
        
        // Only generate metadata if it's calculated OR manually overridden
        if (isCalculated(paramCode) || overrides.value[paramCode]) {
            
            const inputValues = {}
            
            // Get required variables from the formula definition
            const requiredVariables = param.formula?.variables?.map(v => v.name) || [];

            requiredVariables.forEach(variableName => {
                // Pull the input value from the results store (the input fields)
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

// --- Form Submission Logic ---

const saveResults = () => {
  const form = useForm({
    sample_id: props.sample.id,
    // Only save the actual calculated parameter results and the input variables required for calculation
    // We filter results to only keep keys that are either a calculated parameter code OR a required variable name.
    results: Object.keys(results.value).reduce((acc, key) => {
        const isCalculatedParam = allCalculatedParameters.value.some(p => p.parameter_id.code === key);
        const isRequiredInput = allRequiredVariables.value.includes(key);
        if (isCalculatedParam || isRequiredInput) {
            acc[key] = results.value[key];
        }
        return acc;
    }, {}),
    overrides: overrides.value,
    calculated_at: new Date().toISOString()
  })

  // 1. First, ensure all calculations are run if possible
    calculateAll();

    // 2. Generate the required metadata
    const calculationMetadata = generateCalculationMetadata(); // 👈 Call the new function

  // Inside the final save/emit logic (e.g., inside saveResults):
  // Prepare the comprehensive payload
  const comprehensivePayload = {
      // 1. All results (calculated outputs and input variables)
      results: results.value, 
      // 2. The overrides status
      overrides: overrides.value, 
      // 3. The generated metadata (using the logic fixed in the previous step)
      metadata: calculationMetadata, 
  };

  emit('calculatedResults', comprehensivePayload);

  // emit('calculatedResults', results.value)

  // form.post('/samples/results', {
  //   onSuccess: () => {
  //     alert('Resultados guardados com sucesso!')
  //   },
  //   onError: (errors) => {
  //     alert('Erro ao guardar resultados: ' + Object.values(errors).join(', '))
  //   }
  // })
}

const saveDraft = () => {
  const form = useForm({
    sample_id: props.sample.id,
    results: results.value,
    is_draft: true
  })

  form.post('/samples/results/draft', {
    onSuccess: () => {
      alert('Rascunho guardado com sucesso!')
    }
  })
}
</script>

<template>
  <div class="result-entry-container space-y-6">
    <div class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-900">Amostra #{{ sample.id }}</h2>
      <p class="text-gray-600 mt-1">{{ sample.name }} - {{ sample.type }}</p>
      <div class="mt-2 text-sm text-gray-500">
        <span>Parâmetros Calculados: {{ allCalculatedParameters.length }}</span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <div class="space-y-4">
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Variáveis de Entrada Necessárias
            <span v-if="allRequiredVariables.length > 0" class="text-sm text-gray-500 ml-2">
              ({{ allRequiredVariables.length }} variáveis)
            </span>
          </h3>
          
          <div v-for="variableName in allRequiredVariables" :key="variableName"
              class="mb-4 p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
            
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ variableName }}
              <span class="text-gray-500">(Variável de Fórmula)</span>
            </label>
            
            <input
              v-model="results[variableName]" 
              type="number" 
              step="0.0001"
              @input="onInputChange(variableName)"
              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{
                'border-orange-300 bg-orange-50': results[variableName] === undefined || results[variableName] === '',
                'border-green-300 bg-green-50': results[variableName] !== undefined && results[variableName] !== ''
              }"
              placeholder="Insira o valor..."
            />
            
            </div>

          <div v-if="allRequiredVariables.length === 0" 
              class="text-center py-8 text-gray-500">
            Nenhuma variável de entrada é necessária para estes parâmetros calculados.
          </div>
        </div>
      </div>


      <div class="space-y-4">
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Parâmetros Calculados</h3>
          
          <div v-for="parameter in allCalculatedParameters" :key="parameter.parameter_id.code"
               class="mb-4 p-4 border rounded-lg transition-colors"
               :class="{
                 'border-green-200 bg-green-50': isCalculated(parameter.parameter_id.code) && !overrides[parameter.parameter_id.code],
                 'border-yellow-200 bg-yellow-50': !isCalculated(parameter.parameter_id.code),
                 'border-blue-200 bg-blue-50': overrides[parameter.parameter_id.code]
               }">
            
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">
                  {{ parameter.parameter_id.code }} - {{ parameter.parameter_id.name }}
                  <span v-if="parameter.formula?.output_unit" class="text-gray-500">
                    ({{ parameter.formula.output_unit }})
                  </span>
                </label>
                <div v-if="parameter.formula" class="text-xs text-blue-600 mt-1">
                  Fórmula: {{ parameter.formula.name }}
                </div>
              </div>
              
              <span class="text-xs px-2 py-1 rounded whitespace-nowrap"
                    :class="{
                      'bg-green-100 text-green-800': isCalculated(parameter.parameter_id.code) && !overrides[parameter.parameter_id.code],
                      'bg-yellow-100 text-yellow-800': !isCalculated(parameter.parameter_id.code),
                      'bg-blue-100 text-blue-800': overrides[parameter.parameter_id.code]
                    }">
                {{ 
                  overrides[parameter.parameter_id.code] ? 'Manual' : 
                  isCalculated(parameter.parameter_id.code) ? 'Calculado' : 'Pendente'
                }}
              </span>
            </div>

            <div v-if="isCalculated(parameter.parameter_id.code)" 
                 class="text-lg font-bold mb-2"
                 :class="overrides[parameter.parameter_id.code] ? 'text-blue-600' : 'text-green-600'">
              {{ formatResult(parameter, results[parameter.parameter_id.code]) }}
            </div>

            <div class="mt-2 flex items-center">
              <label class="flex items-center text-sm text-gray-600">
                <input 
                  v-model="overrides[parameter.parameter_id.code]" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="ml-2">Sobrescrever valor calculado</span>
              </label>
            </div>

            <input v-if="overrides[parameter.parameter_id.code]"
                   v-model="results[parameter.parameter_id.code]"
                   type="number"
                   :step="parameter.formula?.decimal_places > 0 ? 0.0001 : 1"
                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm mt-2"
                   placeholder="Insira valor manual">

            <div class="mt-3 text-xs text-gray-500 space-y-1">
              <div class="font-mono bg-gray-100 p-2 rounded border">
                {{ getFormulaExpression(parameter) }}
              </div>
              
              <div v-if="!getCalculationStatus(parameter).complete && !overrides[parameter.parameter_id.code]" 
                   class="text-orange-600 flex items-center mt-2">
                <span class="w-2 h-2 bg-orange-600 rounded-full mr-2"></span>
                Aguardando: {{ getMissingInputs(parameter).join(', ') }}
              </div>

              <div v-if="parameter.formula?.description" class="text-gray-400 italic mt-1">
                {{ parameter.formula.description }}
              </div>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="text-sm text-blue-700">
                <span class="font-medium">{{ readyCalculations }}</span> de 
                <span class="font-medium">{{ allCalculatedParameters.length }}</span> cálculos prontos
              </div>
              
              <div v-if="calculationInProgress" class="flex items-center text-blue-600">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                Calculando...
              </div>
            </div>
            
            <button @click="calculateAll" 
                    :disabled="readyCalculations === 0 || calculationInProgress"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium">
              Calcular Tudo ({{ readyCalculations }})
            </button>
          </div>
          
          <div v-if="allCalculatedParameters.length > 0" class="mt-3 space-y-2">
            <div v-for="param in allCalculatedParameters" :key="param.parameter_id.code" class="text-xs">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">{{ param.parameter_id.code }}</span>
                <span :class="{
                  'text-green-600': getCalculationStatus(param).complete,
                  'text-orange-600': !getCalculationStatus(param).complete
                }">
                  {{ getCalculationStatus(param).complete ? '✅ Pronto' : '⏳ Aguardando' }}
                </span>
              </div>
              <div v-if="!getCalculationStatus(param).complete" class="text-orange-500 text-xs ml-2">
                Faltam: {{ getCalculationStatus(param).missing.join(', ') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
      <!-- <button @click="saveDraft" 
              class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium">
        Guardar Rascunho
      </button> -->
      
      <button @click="saveResults" 
              :disabled="!canSave"
              class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-medium">
        {{ canSave ? 'Guardar Resultados' : 'Preencha todos os parâmetros' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.result-entry-container {
  max-width: 1200px;
  margin: 0 auto;
}
</style>