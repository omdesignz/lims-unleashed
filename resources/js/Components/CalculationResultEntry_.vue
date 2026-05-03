<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  sample: Object,
  parameters: Array, // All parameters for this sample type (with formula relationships)
  existingResults: Object
})

const results = ref({})
const overrides = ref({})
const calculationInProgress = ref(false)

// Separate parameters by type
const inputParameters = computed(() => 
//   props.parameters.filter(p => !p.parameter_id.requires_calculation && p.parameter_id.active)
  props.parameters.filter(p => !p.parameter_id.requires_calculation && p.parameter_id.active)
)

const calculatedParameters = computed(() =>
  props.parameters.filter(p => p.parameter_id.requires_calculation && p.parameter_id.active)
)

// Track calculation status
const isCalculated = (parameterCode) => {
  return results.value[parameterCode] !== undefined && 
         results.value[parameterCode] !== null &&
         !isNaN(results.value[parameterCode])
}

const calculatedCount = computed(() =>
  calculatedParameters.value.filter(p => isCalculated(p.parameter_id.code)).length
)

const canCalculateAny = computed(() =>
  calculatedParameters.value.some(p => !isCalculated(p.parameter_id.code) && canCalculateParameter(p))
)

const canSave = computed(() => {
  const totalParameters = props.parameters.filter(p => p.parameter_id.active).length
  const enteredResults = Object.keys(results.value).filter(key => 
    results.value[key] !== undefined && 
    results.value[key] !== null &&
    results.value[key] !== ''
  ).length
  return enteredResults >= totalParameters
})

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

// Get calculation requirements for a parameter
const getCalculationRequirements = (parameter) => {
  if (!parameter.formula) return []
  return parameter.formula.variables?.map(v => v.name) || []
}

// Get missing inputs for a parameter
const getMissingInputs = (parameterCode) => {
  const parameter = calculatedParameters.value.find(p => p.parameter_id.code === parameterCode)
  if (!parameter || !parameter.formula) return []
  
  const required = getCalculationRequirements(parameter)
  const missing = required.filter(input => 
    results.value[input] === undefined || 
    results.value[input] === null ||
    results.value[input] === ''
  )
  
  return missing.map(input => {
    const param = props.parameters.find(p => p.code === input)
    return param ? param.name : input
  })
}

// Get dependent calculations for an input parameter
const getDependentCalculations = (inputCode) => {
  return calculatedParameters.value
    .filter(p => p.formula && getCalculationRequirements(p).includes(inputCode))
    .map(p => p.name)
}

// When input parameters change, trigger calculations
const onParameterChange = async (parameter) => {
  // Find calculated parameters that depend on this input
  const dependentParams = calculatedParameters.value.filter(p => 
    p.formula && getCalculationRequirements(p).includes(parameter.parameter_id.code)
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
  const availableInputs = requiredInputs.filter(input => 
    results.value[input] !== undefined && 
    results.value[input] !== null &&
    results.value[input] !== ''
  )

  if (availableInputs.length !== requiredInputs.length) {
    // Not all inputs available yet
    return
  }

  calculationInProgress.value = true

  try {
    // Build variables object
    const vars = {}
    requiredInputs.forEach(input => {
      const value = parseFloat(results.value[input])
      vars[input] = isNaN(value) ? 0 : value
    })

    // Safe JavaScript evaluation (same as FormulaEdit)
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

    // Format with formula's decimal places
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
  for (const parameter of calculatedParameters.value) {
    if (!isCalculated(parameter.parameter_id.code) && !overrides.value[parameter.parameter_id.code] && canCalculateParameter(parameter)) {
      await calculateParameter(parameter)
    }
  }
}

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

// Initialize with existing results
watch(() => props.existingResults, (newResults) => {
  if (newResults) {
    results.value = { ...newResults }
    
    // Recalculate any formulas that have all required inputs
    setTimeout(() => {
      calculatedParameters.value.forEach(parameter => {
        if (canCalculateParameter(parameter)) {
          calculateParameter(parameter)
        }
      })
    }, 100)
  }
}, { immediate: true })

// Watch for parameter changes and auto-calculate
watch(results, (newResults, oldResults) => {
  // Check if any input values changed that might affect calculations
  const changedInputs = Object.keys(newResults).filter(key => 
    newResults[key] !== oldResults[key] && 
    inputParameters.value.some(p => p.code === key)
  )
  
  if (changedInputs.length > 0) {
    changedInputs.forEach(inputCode => {
      const dependentParams = calculatedParameters.value.filter(p => 
        p.formula && getCalculationRequirements(p).includes(inputCode)
      )
      dependentParams.forEach(param => {
        if (!overrides.value[param.code]) {
          calculateParameter(param)
        }
      })
    })
  }
}, { deep: true })

// Save results
const saveResults = () => {
  const form = useForm({
    sample_id: props.sample.id,
    results: results.value,
    overrides: overrides.value,
    calculated_at: new Date().toISOString()
  })

  form.post('/samples/results', {
    onSuccess: () => {
      // Show success message
      alert('Resultados guardados com sucesso!')
    },
    onError: (errors) => {
      alert('Erro ao guardar resultados: ' + Object.values(errors).join(', '))
    }
  })
}

// Save as draft
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
    <!-- Sample Information -->
    <div class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gray-900">Amostra #{{ sample.id }}</h2>
      <p class="text-gray-600 mt-1">{{ sample.name }} - {{ sample.type }}</p>
      <div class="mt-2 text-sm text-gray-500">
        <span>Parâmetros: {{ props.parameters.filter(p => p.active).length }} total</span>
        <span class="mx-2">•</span>
        <span>Calculados: {{ calculatedParameters.length }}</span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Input Parameters -->
      <div class="space-y-4">
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Parâmetros de Entrada</h3>
          
          <div v-for="parameter in calculatedParameters" :key="parameter.parameter_id.value"
               class="mb-4 p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ parameter.parameter_id?.code }} 
              <span v-if="parameter.unit_label" class="text-gray-500">({{ parameter.unit_label }})</span>
            </label>
            
            <input 
              v-model="results[parameter.parameter_id.code]" 
              type="number" 
              :step="parameter.parameter_id.decimal_places > 0 ? 0.0001 : 1"
              @input="onParameterChange(parameter)"
              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="results[parameter.parameter_id.code] === undefined ? 'border-orange-300 bg-orange-50' : 'border-gray-300'"
            />
            
            <!-- Show which calculated parameters depend on this input -->
            <div v-if="getDependentCalculations(parameter.parameter_id.code).length > 0" 
                 class="mt-2 text-xs text-blue-600 flex items-center">
              <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
              Afeta: {{ getDependentCalculations(parameter.parameter_id.code).join(', ') }}
            </div>
          </div>

          <div v-if="calculatedParameters.length === 0" class="text-center py-8 text-gray-500">
            Nenhum parâmetro de entrada definido para esta amostra.
          </div>
        </div>
      </div>

      <!-- Calculated Parameters -->
      <div class="space-y-4">
        <div class="bg-white p-6 rounded-lg shadow">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Parâmetros Calculados</h3>
          
          <div v-for="parameter in calculatedParameters" :key="parameter.parameter_id.value"
               class="mb-4 p-4 border rounded-lg transition-colors"
               :class="{
                 'border-green-200 bg-green-50': isCalculated(parameter.parameter_id.code) && !overrides[parameter.parameter_id.code],
                 'border-yellow-200 bg-yellow-50': !isCalculated(parameter.parameter_id.code),
                 'border-blue-200 bg-blue-50': overrides[parameter.parameter_id.code]
               }">
            
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">
                  {{ parameter.parameter_id.code }}
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

            <!-- Display calculated value -->
            <div v-if="isCalculated(parameter.parameter_id.code)" 
                 class="text-lg font-bold mb-2"
                 :class="overrides[parameter.parameter_id.code] ? 'text-blue-600' : 'text-green-600'">
              {{ formatResult(parameter, results[parameter.parameter_id.code]) }}
            </div>

            <!-- Manual override option -->
            <div v-if="isCalculated(parameter.parameter_id.code)" class="mt-2 flex items-center">
              <label class="flex items-center text-sm text-gray-600">
                <input 
                  v-model="overrides[parameter.parameter_id.code]" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="ml-2">Sobrescrever valor calculado</span>
              </label>
            </div>

            <!-- Manual input when overriding -->
            <input v-if="overrides[parameter.parameter_id.code]"
                   v-model="results[parameter.parameter_id.code]"
                   type="number"
                   :step="parameter.formula?.decimal_places > 0 ? 0.0001 : 1"
                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm mt-2"
                   placeholder="Insira valor manual">

            <!-- Formula information -->
            <div class="mt-3 text-xs text-gray-500 space-y-1">
              <div class="font-mono bg-gray-100 p-2 rounded border">
                {{ getFormulaExpression(parameter) }}
              </div>
              
              <div v-if="!isCalculated(parameter.parameter_id.code) && !overrides[parameter.parameter_id.code]" 
                   class="text-orange-600 flex items-center mt-2">
                <span class="w-2 h-2 bg-orange-600 rounded-full mr-2"></span>
                Aguardando: {{ getMissingInputs(parameter.parameter_id.code).join(', ') }}
              </div>

              <div v-if="parameter.formula?.description" class="text-gray-400 italic mt-1">
                {{ parameter.formula.description }}
              </div>
            </div>
          </div>

          <div v-if="calculatedParameters.length === 0" class="text-center py-8 text-gray-500">
            Nenhum parâmetro calculado definido para esta amostra.
          </div>
        </div>

        <!-- Calculation Status -->
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="text-sm text-blue-700">
                <span class="font-medium">{{ calculatedCount }}</span> de 
                <span class="font-medium">{{ calculatedParameters.length }}</span> parâmetros calculados
              </div>
              
              <div v-if="calculationInProgress" class="flex items-center text-blue-600">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                Calculando...
              </div>
            </div>
            
            <button @click="calculateAll" 
                    :disabled="!canCalculateAny || calculationInProgress"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-sm font-medium">
              Calcular Tudo
            </button>
          </div>
          
          <div v-if="calculatedParameters.length > 0" class="mt-2 text-xs text-blue-600">
            <span v-if="canCalculateAny">
              {{ calculatedParameters.filter(p => canCalculateParameter(p) && !isCalculated(p.code)).length }} 
              parâmetros prontos para cálculo
            </span>
            <span v-else>
              Todos os cálculos possíveis foram realizados
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
      <button @click="saveDraft" 
              class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium">
        Guardar Rascunho
      </button>
      
      <button @click="saveResults" 
              :disabled="!canSave"
              class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-medium">
        {{ canSave ? 'Guardar Resultados' : 'Preencha todos os parâmetros' }}
      </button>
    </div>

    <!-- Progress Indicator -->
    <div v-if="props.parameters.length > 0" class="bg-white p-4 rounded-lg shadow">
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600">Progresso do preenchimento</span>
        <span class="font-medium text-gray-900">
          {{ Object.keys(results).filter(key => results[key] !== undefined && results[key] !== null && results[key] !== '').length }} 
          / 
          {{ props.parameters.filter(p => p.active).length }}
        </span>
      </div>
      <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
        <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
             :style="{ width: `${(Object.keys(results).filter(key => results[key] !== undefined && results[key] !== null && results[key] !== '').length / props.parameters.filter(p => p.active).length) * 100}%` }">
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.result-entry-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Smooth transitions for calculated values */
.calculated-value {
  transition: all 0.3s ease;
}

/* Loading animation */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.calculating {
  animation: pulse 1.5s ease-in-out infinite;
}
</style>