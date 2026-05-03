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
            Análise de amostra: 
            <span v-if="sample" class="font-semibold text-blue-900">
              {{ sample.name }} ({{ sample.type }})
            </span>
            <span v-else class="text-gray-500">Nenhuma amostra selecionada</span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ allCalculatedParameters.length }} parâmetros
          </span>
          <span v-if="sample" class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-900 ring-1 ring-inset ring-green-700/10">
            Amostra #{{ sample.id }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- LEFT COLUMN - INPUT VARIABLES -->
      <div class="space-y-6">
        <!-- VARIABLES CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <VariableIcon class="h-5 w-5" />
              Variáveis de Entrada
              <span v-if="allRequiredVariables.length > 0" class="text-sm font-normal ml-2">
                ({{ allRequiredVariables.length }} variáveis)
              </span>
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div v-if="allRequiredVariables.length === 0" class="py-8 text-center">
              <VariableIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">
                Nenhuma variável necessária
              </h3>
              <p class="mt-2 text-sm text-gray-500">
                Estes parâmetros não requerem variáveis de entrada
              </p>
            </div>

            <div v-else class="space-y-4">
              <div v-for="variableName in allRequiredVariables" :key="variableName"
                  class="group bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 p-4">
                
                <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                  <HashtagIcon class="h-4 w-4 text-blue-900" />
                  {{ variableName }}
                  <span class="text-xs text-gray-500 ml-1">(Variável de Fórmula)</span>
                </label>
                
                <div class="relative">
                  <input
                    v-model="results[variableName]" 
                    type="number" 
                    step="0.0001"
                    @input="onInputChange(variableName)"
                    class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="{
                      'ring-orange-500 bg-orange-50': results[variableName] === undefined || results[variableName] === '',
                      'ring-green-500 bg-green-50': results[variableName] !== undefined && results[variableName] !== ''
                    }"
                    placeholder="Insira o valor..."
                  />
                  <div v-if="results[variableName] !== undefined && results[variableName] !== ''"
                      class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <CheckCircleIcon class="h-5 w-5 text-green-600" />
                  </div>
                </div>
                
                <p class="mt-2 text-xs text-gray-500">
                  Alterar este valor recalculará todos os parâmetros dependentes
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- CALCULATION STATUS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <LightBulbIcon class="h-5 w-5 text-blue-900" />
            Status dos Cálculos
          </h3>
          
          <div class="space-y-4">
            <!-- PROGRESS -->
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Progresso dos cálculos</span>
              <span class="text-sm font-semibold text-blue-900">
                {{ calculatedCount }} / {{ allCalculatedParameters.length }}
              </span>
            </div>
            
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
              <div 
                class="h-full rounded-full bg-gradient-to-r from-blue-900 to-blue-800"
                :style="{ width: `${(calculatedCount / allCalculatedParameters.length) * 100}%` }"
              ></div>
            </div>

            <!-- CALCULATION BUTTON -->
            <div class="pt-4 border-t border-gray-200">
              <button 
                @click="calculateAll" 
                :disabled="readyCalculations === 0 || calculationInProgress"
                :class="[
                  'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                  readyCalculations === 0 || calculationInProgress
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
              >
                <template v-if="calculationInProgress">
                  <ArrowPathIcon class="h-5 w-5 animate-spin" />
                  Calculando...
                </template>
                <template v-else>
                  <CalculatorIcon class="h-5 w-5" />
                  Calcular Todos ({{ readyCalculations }} prontos)
                </template>
              </button>
            </div>

            <!-- READY CALCULATIONS LIST -->
            <div v-if="allCalculatedParameters.length > 0" class="space-y-2">
              <div v-for="param in allCalculatedParameters" :key="param.parameter_id.code" 
                  class="p-3 rounded-lg border transition-colors duration-200"
                  :class="{
                    'border-green-200 bg-green-50': getCalculationStatus(param).complete,
                    'border-yellow-200 bg-yellow-50': !getCalculationStatus(param).complete
                  }">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-gray-900">{{ param.parameter_id.code }}</span>
                  <span :class="[
                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                    getCalculationStatus(param).complete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    <template v-if="getCalculationStatus(param).complete">
                      <CheckCircleIcon class="h-3 w-3" />
                      Pronto
                    </template>
                    <template v-else>
                      <ClockIcon class="h-3 w-3" />
                      Aguardando
                    </template>
                  </span>
                </div>
                <div v-if="!getCalculationStatus(param).complete" class="mt-1 text-xs text-yellow-600">
                  Faltam: {{ getMissingInputs(param).join(', ') }}
                </div>
              </div>
            </div>
            
          </div>
        </div>

      </div>

      <!-- RIGHT COLUMN - CALCULATED PARAMETERS -->
      <div class="space-y-6">
        <!-- PARAMETERS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <BeakerIcon class="h-5 w-5" />
              Parâmetros Calculados
              <span class="text-sm font-normal ml-2">
                ({{ allCalculatedParameters.length }} parâmetros)
              </span>
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div v-if="allCalculatedParameters.length === 0" class="py-12 text-center">
              <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">
                Nenhum parâmetro calculado
              </h3>
              <p class="mt-2 text-sm text-gray-500">
                Não há parâmetros configurados para cálculo
              </p>
            </div>

            <div v-else class="space-y-4">
              <div v-for="parameter in allCalculatedParameters" :key="parameter.parameter_id.code"
                   class="group bg-white rounded-xl border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
                   v-motion
                   :initial="{ opacity: 0, y: 20 }"
                   :enter="{ opacity: 1, y: 0 }">
                
                <!-- PARAMETER HEADER -->
                <div class="bg-gradient-to-r from-gray-50 to-white px-4 py-3 border-b border-gray-200">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold text-xs">
                        {{ parameter.parameter_id.code.substring(0, 3) }}
                      </div>
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                          {{ parameter.parameter_id.code }}
                        </h3>
                        <p class="text-xs text-gray-500">
                          {{ parameter.parameter_id.name }}
                        </p>
                      </div>
                    </div>
                    <span :class="[
                      'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                      overrides[parameter.parameter_id.code] ? 'bg-blue-100 text-blue-800' :
                      isCalculated(parameter.parameter_id.code) ? 'bg-green-100 text-green-800' :
                      'bg-yellow-100 text-yellow-800'
                    ]">
                      <template v-if="overrides[parameter.parameter_id.code]">
                        <PencilIcon class="h-3 w-3" />
                        Manual
                      </template>
                      <template v-else-if="isCalculated(parameter.parameter_id.code)">
                        <CheckCircleIcon class="h-3 w-3" />
                        Calculado
                      </template>
                      <template v-else>
                        <ClockIcon class="h-3 w-3" />
                        Pendente
                      </template>
                    </span>
                  </div>
                </div>
                
                <!-- PARAMETER CONTENT -->
                <div class="p-4">
                  <!-- RESULT DISPLAY -->
                  <div v-if="isCalculated(parameter.parameter_id.code)" 
                       class="mb-4 p-3 rounded-lg border"
                       :class="overrides[parameter.parameter_id.code] ? 'border-blue-200 bg-blue-50' : 'border-green-200 bg-green-50'">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-xs text-gray-500">Resultado</p>
                        <p :class="[
                          'text-lg font-bold',
                          overrides[parameter.parameter_id.code] ? 'text-blue-900' : 'text-green-900'
                        ]">
                          {{ formatResult(parameter, results[parameter.parameter_id.code]) }}
                        </p>
                      </div>
                      <div v-if="parameter.formula?.output_unit" class="text-sm text-gray-600">
                        {{ parameter.formula.output_unit }}
                      </div>
                    </div>
                  </div>

                  <!-- OVERRIDE TOGGLE -->
                  <div class="flex items-center justify-between mb-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                      <input 
                        v-model="overrides[parameter.parameter_id.code]" 
                        type="checkbox" 
                        class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                      >
                      <span>Sobrescrever valor calculado</span>
                    </label>
                  </div>

                  <!-- MANUAL INPUT (when overridden) -->
                  <div v-if="overrides[parameter.parameter_id.code]" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Valor Manual
                    </label>
                    <input 
                      v-model="results[parameter.parameter_id.code]"
                      type="text"
                      :step="parameter.formula?.decimal_places > 0 ? 0.0001 : 1"
                      class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                      placeholder="Insira valor manual"
                    />
                  </div>

                  <!-- UNCERTAINTY FIELD -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                      <ScaleIcon class="h-4 w-4" />
                      Incerteza
                    </label>
                    <div class="relative">
                      <input 
                        v-model="uncertaintyValues[parameter.parameter_id.code]"
                        type="number"
                        step="0.0001"
                        class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                        placeholder="Ex: 0.1"
                      />
                      <div v-if="uncertaintyValues[parameter.parameter_id.code]" 
                          class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <ExclamationCircleIcon class="h-5 w-5 text-blue-900" />
                      </div>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                      <MinusCircleIcon class="h-4 w-4" />
                      Valor Mínimo de Referência
                    </label>
                    <div class="relative">
                      <input 
                        v-model="minRefValues[parameter.parameter_id.code]"
                        type="number"
                        step="0.0001"
                        class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                        placeholder="Ex: 0.1"
                      />
                      <div v-if="minRefValues[parameter.parameter_id.code]"
                          class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <ExclamationCircleIcon class="h-5 w-5 text-blue-900" />
                      </div>
                    </div>
                  </div>

                  <!-- MAX REF VALUE -->
                  
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
                      <PlusCircleIcon class="h-4 w-4" />
                      Valor Máximo de Referência
                    </label>
                    <div class="relative">
                      <input 
                        v-model="maxRefValues[parameter.parameter_id.code]"
                        type="number"
                        step="0.0001"
                        class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                        placeholder="Ex: 0.1"
                      />
                      <div v-if="maxRefValues[parameter.parameter_id.code]"
                          class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <ExclamationCircleIcon class="h-5 w-5 text-blue-900" />
                      </div>
                    </div>
                  </div>

                  <!-- INSERTION NOTES -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      Observações de Inserção
                    </label>
                    <textarea 
                      v-model="insertionNotes[parameter.parameter_id.code]"
                      rows="3"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                      placeholder="Adicione observações gerais sobre a inserção (opcional)..."
                    />
                    <p class="text-xs text-gray-500">
                      Estas observações serão registradas no histórico da amostra.
                    </p>
                  </div>

                  <!-- VERIFICATION NOTES -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      Observações de Verificação
                    </label>
                    <textarea 
                      v-model="verificationNotes[parameter.parameter_id.code]"
                      rows="3"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                      placeholder="Adicione observações gerais sobre a verificação (opcional)..."
                    />
                    <p class="text-xs text-gray-500">
                      Estas observações serão registradas no histórico da amostra.
                    </p>
                  </div>

                  <!-- APPROVAL NOTES -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      Observações de Aprovação
                    </label>
                    <textarea 
                      v-model="approvalNotes[parameter.parameter_id.code]"
                      rows="3"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                      placeholder="Adicione observações gerais sobre a aprovação (opcional)..."
                    />
                    <p class="text-xs text-gray-500">
                      Estas observações serão registradas no histórico da amostra.
                    </p>
                  </div>

                  <!-- FORMULA DISPLAY -->
                  <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-xs text-gray-500 space-y-2">
                      <div>
                        <p class="text-xs font-medium text-gray-700 mb-1">Fórmula</p>
                        <div class="font-mono bg-gray-50 p-3 rounded-lg border border-gray-200 text-sm">
                          {{ getFormulaExpression(parameter) }}
                        </div>
                      </div>
                      
                      <!-- MISSING INPUTS WARNING -->
                      <div v-if="!getCalculationStatus(parameter).complete && !overrides[parameter.parameter_id.code]" 
                          class="flex items-start gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200">
                        <ExclamationTriangleIcon class="h-4 w-4 text-yellow-600 mt-0.5" />
                        <div class="text-xs text-yellow-700">
                          <p class="font-medium">Aguardando variáveis:</p>
                          <p class="mt-1">{{ getMissingInputs(parameter).join(', ') }}</p>
                        </div>
                      </div>

                      <!-- FORMULA DESCRIPTION -->
                      <div v-if="parameter.formula?.description" class="text-gray-400 italic">
                        {{ parameter.formula.description }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full" 
                 :class="canSave ? 'bg-green-500' : 'bg-yellow-500'"></div>
            <span>{{ calculatedCount }} de {{ allCalculatedParameters.length }} parâmetros calculados</span>
          </div>
          <div class="flex items-center gap-2">
            <CheckCircleIcon class="h-4 w-4 text-gray-400" />
            <span>{{ readyCalculations }} cálculos prontos</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="$emit('close')"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <XMarkIcon class="h-5 w-5" />
          Cancelar
        </button>
        
        <button 
          @click="saveResults" 
          :disabled="!canSave"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
            !canSave
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
          ]"
        >
          <CheckIcon class="h-5 w-5" />
          {{ !canSave ? 'Preencha todos os parâmetros' : 'Guardar Resultados' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from "@inertiajs/vue3";

// Heroicons imports
import {
  CalculatorIcon,
  BeakerIcon,
  VariableIcon,
  HashtagIcon,
  CheckCircleIcon,
  XMarkIcon,
  CheckIcon,
  PencilIcon,
  ClockIcon,
  ArrowPathIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  ScaleIcon,
  LightBulbIcon,
  MinusCircleIcon,
  PlusCircleIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
  parameters: Array, // Only calculated parameters
  existingResults: Object, // Combined input variables and existing calculated values 
  action: String
})

const emit = defineEmits(['calculatedResults', 'close'])

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

onMounted(() => {
  console.log(props.existingResults);
});

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

// Initialize with existing data
watch(() => props.existingResults, (newResults) => {
  if (newResults) {
    results.value = { ...newResults }; 
    
    // Initialize overrides
    props.parameters.forEach(param => {
      const code = param.parameter_id?.code;
      if (code && param.manual_override) { 
        overrides.value[code] = true;
      }

      uncertaintyValues.value[code] = param.uncertainty_value || null;

      minRefValues.value[code] = param.min_ref_value || 0;
      maxRefValues.value[code] = param.max_ref_value || 0;

      insertionNotes.value[code] = param.insertion_notes || null;
      verificationNotes.value[code] = param.verification_notes || null;
      approvalNotes.value[code] = param.approval_notes || null;
    });
  }
}, { immediate: true });

// Rest of your existing calculation logic remains the same...
// (keep all your calculation functions, status tracking, etc.)

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

const saveResults = () => {
  const form = useForm({
    sample_id: props?.sample?.id,
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
      results: Object.keys(results.value).reduce((acc, key) => {
          // Store the main value
          acc[key] = results.value[key];
          
          // ✅ CORRECTION: If this key corresponds to a calculated parameter, 
          // we add the 'uncertainty_value' field directly alongside the main value.
          const isCalculatedParam = allCalculatedParameters.value.some(p => p.parameter_id.code === key);
          if (isCalculatedParam) {
              // Note: The LIMS expects the uncertainty value to be on the result object.
              // In this flattened structure, we attach it as a separate property on the same level.
              // The parent component *must* know how to merge this back into the full result object.
              acc[`${key}_uncertainty_value`] = uncertaintyValues.value[key] || null;

              acc[`${key}_min_ref_value`] = minRefValues.value[key] || 0;
              acc[`${key}_max_ref_value`] = maxRefValues.value[key] || 0;
              acc[`${key}_insertion_notes`] = insertionNotes.value[key] || null;
              acc[`${key}_verification_notes`] = verificationNotes.value[key] || null;
              acc[`${key}_approval_notes`] = approvalNotes.value[key] || null;
          }
          return acc;
      }, {}),
      // 2. The overrides status
      overrides: overrides.value, 
      // 3. The generated metadata (using the logic fixed in the previous step)
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

/* Custom scrollbar for modal */
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