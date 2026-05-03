<!-- resources/js/Components/results/CalculationResultEntry.vue - Updated with additional inputs -->
<template>
  <div class="result-entry-modal space-y-8">
    <!-- MODAL HEADER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CalculatorIcon class="h-7 w-7 text-blue-900" />
            Resultados de Parâmetros Calculados
            <span class="text-lg font-normal text-gray-600">({{ calculationMode === 'single' ? 'Individual' : 'Em Lote' }})</span>
          </h1>
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
              Seleccione um parâmetro para calcular
            </label>
            <select v-model="selectedSingleParameter"
                    @change="onSingleParameterChange"
                    class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
              <option value="">Seleccione um parâmetro</option>
              <option v-for="param in availableSingleParameters" 
                      :key="param.parameter_id.code"
                      :value="param.parameter_id.code">
                {{ param.parameter_id.code }} - {{ param.parameter_id.name }}
                <span v-if="results[param.parameter_id.code]">(✓ {{ results[param.parameter_id.code] }})</span>
              </option>
            </select>
          </div>

          <!-- Selected Parameter Info -->
          <div v-if="selectedSingleParameterObj" class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-semibold text-gray-900">{{ selectedSingleParameterObj.parameter_id.name }}</h4>
                <p class="text-sm text-gray-600">{{ selectedSingleParameterObj.parameter_id.code }}</p>
                <div v-if="selectedSingleParameterObj.formula?.output_unit" class="mt-1 text-sm text-blue-700">
                  Unidade: {{ selectedSingleParameterObj.formula.output_unit }}
                </div>
              </div>
              <span class="text-sm text-blue-900 font-medium">
                {{ getSingleCalculationStatus().complete ? 'Pronto para calcular' : 'A aguardar variáveis' }}
              </span>
            </div>
            
            <!-- Formula Display -->
            <div v-if="selectedSingleParameterObj.formula" class="mt-3 pt-3 border-t border-blue-200">
              <p class="text-xs text-gray-600 mb-1">Fórmula:</p>
              <code class="text-sm font-mono bg-white p-2 rounded border border-gray-300 block">
                {{ selectedSingleParameterObj.formula.expression }}
              </code>
            </div>
          </div>
        </div>
      </div>

      <!-- INPUT VARIABLES SECTION -->
      <div v-if="selectedSingleParameterObj && singleParameterInputs.length > 0" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h3 class="text-lg font-semibold text-white flex items-center gap-2">
            <VariableIcon class="h-5 w-5" />
            Variáveis de Entrada
            <span class="text-sm font-normal">({{ getSingleCalculationStatus().complete ? `${singleParameterInputs.length}/${singleParameterInputs.length}` : `${filledInputCount}/${singleParameterInputs.length}` }})</span>
          </h3>
        </div>
        
        <div class="p-6 space-y-4">
          <div v-for="variableName in singleParameterInputs" :key="variableName"
              class="group bg-gray-50 rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 p-4">
            
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
              <HashtagIcon class="h-4 w-4 text-blue-900" />
              {{ variableName }}
            </label>
            
            <div class="relative">
              <input
                v-model="singleCalcInputs[variableName]" 
                type="number" 
                step="0.0001"
                @input="onSingleInputChange"
                class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :class="{
                  'ring-orange-500 bg-orange-50': !singleCalcInputs[variableName] || singleCalcInputs[variableName] === '',
                  'ring-green-500 bg-green-50': singleCalcInputs[variableName] && singleCalcInputs[variableName] !== ''
                }"
                placeholder="Introduza o valor..."
              />
              <div v-if="singleCalcInputs[variableName] && singleCalcInputs[variableName] !== ''"
                  class="absolute inset-y-0 right-0 flex items-center pr-3">
                <CheckCircleIcon class="h-5 w-5 text-green-600" />
              </div>
            </div>
            
            <!-- Show existing value from main results -->
            <div v-if="results[variableName] && singleCalcInputs[variableName] !== results[variableName]"
                 class="mt-2 text-xs text-gray-500 flex items-center gap-1">
              <InformationCircleIcon class="h-3 w-3" />
              Valor pré-existente: {{ results[variableName] }}
            </div>
          </div>
        </div>
      </div>

      <!-- ADDITIONAL PARAMETER FIELDS -->
      <div v-if="selectedSingleParameterObj" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h3 class="text-lg font-semibold text-white flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5" />
            Configurações do Parâmetro
          </h3>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Uncertainty Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
              <ScaleIcon class="h-4 w-4 text-blue-900" />
              Incerteza do Resultado
              <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-gray-500">
                ({{ selectedSingleParameterObj.formula.output_unit }})
              </span>
            </label>
            <div class="relative">
              <input 
                v-model="singleParameterUncertainty"
                type="number"
                step="0.0001"
                class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                placeholder="Ex.: 0,1"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <span class="text-gray-500">±</span>
              </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              Incerteza padrão associada ao resultado calculado
            </p>
          </div>

          <!-- Reference Range Section -->
          <div class="space-y-4">
            <h4 class="text-sm font-medium text-gray-900 flex items-center gap-2">
              <ChartBarIcon class="h-4 w-4 text-blue-900" />
              Valores de Referência
            </h4>
            
            <!-- Min Reference Value -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Valor Mínimo de Referência
                <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-gray-500">
                  ({{ selectedSingleParameterObj.formula.output_unit }})
                </span>
              </label>
              <div class="relative">
                <input 
                  v-model="singleParameterMinRef"
                  type="number"
                  step="0.0001"
                  class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="Valor mínimo aceitável"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <span class="text-gray-500">≥</span>
                </div>
              </div>
            </div>

            <!-- Max Reference Value -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Valor Máximo de Referência
                <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-gray-500">
                  ({{ selectedSingleParameterObj.formula.output_unit }})
                </span>
              </label>
              <div class="relative">
                <input 
                  v-model="singleParameterMaxRef"
                  type="number"
                  step="0.0001"
                  class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="Valor máximo aceitável"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <span class="text-gray-500">≤</span>
                </div>
              </div>
            </div>

            <!-- Reference Range Display -->
            <div v-if="singleParameterMinRef || singleParameterMaxRef" 
                 class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <p class="text-sm font-medium text-gray-700 mb-1">Intervalo de Referência:</p>
              <p class="text-lg font-bold text-blue-900">
                <span v-if="singleParameterMinRef && singleParameterMaxRef">
                  {{ singleParameterMinRef }} - {{ singleParameterMaxRef }}
                </span>
                <span v-else-if="singleParameterMinRef">
                  ≥ {{ singleParameterMinRef }}
                </span>
                <span v-else-if="singleParameterMaxRef">
                  ≤ {{ singleParameterMaxRef }}
                </span>
                <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-sm font-normal">
                  {{ selectedSingleParameterObj.formula.output_unit }}
                </span>
              </p>
            </div>
          </div>

          <!-- Notes Section -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
              <ChatBubbleLeftRightIcon class="h-4 w-4 text-blue-900" />
              Observações
            </label>
            <textarea 
              v-model="singleParameterNotes"
              rows="3"
              class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :placeholder="`Observações sobre ${selectedSingleParameterObj.parameter_id.code}...`"
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">
              Observações sobre o cálculo ou resultado
            </p>
          </div>
        </div>
      </div>

      <!-- CALCULATION RESULT -->
      <div v-if="selectedSingleParameterObj" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Resultado Final</h3>
          
          <!-- Override Toggle -->
          <label class="flex items-center gap-2 text-sm">
            <input 
              v-model="singleParameterOverride" 
              type="checkbox" 
              @change="onOverrideToggle"
              class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
            >
            <span>Inserir valor manualmente</span>
          </label>
        </div>
        
        <!-- Manual Input when overridden -->
        <div v-if="singleParameterOverride" class="mb-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Valor Manual
              <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-gray-500">
                ({{ selectedSingleParameterObj.formula.output_unit }})
              </span>
            </label>
            <input 
              v-model="singleCalcManualValue"
              type="text"
              class="block w-full rounded-md border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              placeholder="Introduza o valor manual"
            />
          </div>
        </div>
        
        <!-- Calculated Result Display -->
        <div v-else-if="singleCalculationResult !== null" 
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
            
            <!-- Uncertainty Display -->
            <div v-if="singleParameterUncertainty" 
                 class="mt-3 inline-flex items-center gap-2 px-3 py-1 bg-green-200 text-green-900 rounded-full">
              <span class="text-sm">± {{ singleParameterUncertainty }}</span>
            </div>
            
            <!-- Reference Range Check -->
            <div v-if="singleParameterMinRef || singleParameterMaxRef" class="mt-4">
              <div v-if="isWithinReferenceRange()"
                   class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-900 rounded-full">
                <CheckCircleIcon class="h-4 w-4" />
                <span class="text-sm">Dentro do intervalo de referência</span>
              </div>
              <div v-else
                   class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-900 rounded-full">
                <ExclamationTriangleIcon class="h-4 w-4" />
                <span class="text-sm">Fora do intervalo de referência</span>
              </div>
            </div>
            
            <div v-if="selectedSingleParameterObj.formula" class="mt-4 text-xs text-gray-500">
              Usando fórmula: {{ selectedSingleParameterObj.formula.expression }}
            </div>
          </div>
        </div>
        
        <!-- Missing Inputs Warning -->
        <div v-if="getSingleCalculationStatus().missing.length > 0 && !singleParameterOverride"
             class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-6">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 mt-0.5" />
            <div>
              <p class="text-sm font-medium text-yellow-900">A aguardar variáveis</p>
              <p class="text-sm text-yellow-700 mt-1">
                Introduza valores para: {{ getSingleCalculationStatus().missing.join(', ') }}
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
            {{ singleParameterOverride ? 'Aplicar Valor Manual' : 'Aplicar Resultado Calculado' }}
          </button>
        </div>
      </div>

      <!-- NEXT PARAMETER SUGGESTION -->
      <div v-if="nextAvailableParameter && selectedSingleParameter" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-900">Próximo parâmetro sugerido</h3>
            <p class="text-sm text-gray-600">{{ nextAvailableParameter.parameter_id.name }} ({{ nextAvailableParameter.parameter_id.code }})</p>
          </div>
          <button @click="selectNextParameter"
                  class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
            Calcular Agora
          </button>
        </div>
      </div>
    </div>

    <!-- BATCH CALCULATION MODE -->
    <div v-else class="space-y-6">
      <!-- Batch mode content remains the same -->
      <!-- ... -->
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full" 
                 :class="canSave ? 'bg-green-500' : 'bg-yellow-500'"></div>
            <span>
              {{ calculationMode === 'single' ? 'Parâmetro individual' : `${calculatedCount} de ${allCalculatedParameters.length} calculados` }}
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="closeModal"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <XMarkIcon class="h-5 w-5" />
          Fechar
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
  DocumentPlusIcon,
  InformationCircleIcon,
  ScaleIcon,
  Cog6ToothIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon
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

// Additional parameter fields
const singleParameterUncertainty = ref('')
const singleParameterMinRef = ref('')
const singleParameterMaxRef = ref('')
const singleParameterNotes = ref('')

// Existing state for batch mode
const results = ref({})
const overrides = ref({})
const uncertaintyValues = ref({})
const minRefValues = ref({})
const maxRefValues = ref({})
const notesValues = ref({})
const calculationInProgress = ref(false)

const allCalculatedParameters = computed(() => props.parameters.filter(p => p.parameter_id.active))

// Single parameter mode computed properties
const availableSingleParameters = computed(() => {
  return allCalculatedParameters.value
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

const filledInputCount = computed(() => {
  return Object.values(singleCalcInputs.value).filter(val => 
    val !== undefined && val !== null && val !== ''
  ).length
})

const getSingleCalculationStatus = () => {
  if (!selectedSingleParameterObj.value) return { complete: false, missing: [] }
  
  const required = getCalculationRequirements(selectedSingleParameterObj.value)
  const missing = required.filter(input => 
    !singleCalcInputs.value[input] || singleCalcInputs.value[input] === ''
  )
  
  return {
    complete: missing.length === 0,
    missing: missing
  }
}

const canApplySingleCalculation = computed(() => {
  if (singleParameterOverride.value) {
    return singleCalcManualValue.value && singleCalcManualValue.value.trim() !== ''
  }
  
  if (!selectedSingleParameter.value) return false
  if (!selectedSingleParameterObj.value) return false
  
  const missing = getSingleCalculationStatus().missing
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

const isWithinReferenceRange = () => {
  if (!singleCalculationResult.value) return false
  
  const result = parseFloat(singleCalculationResult.value)
  if (isNaN(result)) return false
  
  const min = singleParameterMinRef.value ? parseFloat(singleParameterMinRef.value) : null
  const max = singleParameterMaxRef.value ? parseFloat(singleParameterMaxRef.value) : null
  
  if (min !== null && max !== null) {
    return result >= min && result <= max
  } else if (min !== null) {
    return result >= min
  } else if (max !== null) {
    return result <= max
  }
  
  return true // No reference range defined
}

// Batch mode computed properties
const calculatedCount = computed(() =>
  allCalculatedParameters.value.filter(p => results.value[p.parameter_id.code]).length
)

const canSave = computed(() => {
  return calculationMode.value === 'batch' 
    ? Object.keys(results.value).filter(key => 
        results.value[key] && 
        allCalculatedParameters.value.some(p => p.parameter_id.code === key)
      ).length > 0
    : canApplySingleCalculation.value
})

// Single parameter calculation methods
const onSingleParameterChange = () => {
  if (!selectedSingleParameterObj.value) {
    resetSingleParameterFields()
    return
  }
  
  const param = selectedSingleParameterObj.value
  const paramCode = param.parameter_id.code
  const required = getCalculationRequirements(param)
  
  // Initialize inputs with existing values
  required.forEach(variable => {
    // First check if we have existing input from previous session
    if (singleCalcInputs.value[variable] !== undefined) {
      // Keep existing input value
      return
    }
    
    // Then check main results
    if (results.value[variable] !== undefined) {
      singleCalcInputs.value[variable] = results.value[variable]
    } else {
      // Initialize as empty
      singleCalcInputs.value[variable] = ''
    }
  })
  
  // Initialize result with existing value
  if (results.value[paramCode]) {
    singleCalculationResult.value = results.value[paramCode]
  } else {
    singleCalculationResult.value = null
  }
  
  // Initialize additional fields
  singleParameterUncertainty.value = uncertaintyValues.value[paramCode] || ''
  singleParameterMinRef.value = minRefValues.value[paramCode] || ''
  singleParameterMaxRef.value = maxRefValues.value[paramCode] || ''
  singleParameterNotes.value = notesValues.value[paramCode] || ''
  
  // Clear override
  singleParameterOverride.value = overrides.value[paramCode] || false
  singleCalcManualValue.value = ''
  
  // Try to calculate if all inputs are available
  if (required.every(v => singleCalcInputs.value[v] && singleCalcInputs.value[v] !== '')) {
    calculateSingleParameter()
  }
}

const onSingleInputChange = () => {
  calculateSingleParameter()
}

const onOverrideToggle = () => {
  if (singleParameterOverride.value) {
    singleCalcManualValue.value = results.value[selectedSingleParameter.value] || ''
  } else {
    singleCalcManualValue.value = ''
    calculateSingleParameter()
  }
}

const calculateSingleParameter = async () => {
  if (!selectedSingleParameterObj.value || singleParameterOverride.value) {
    singleCalculationResult.value = null
    return
  }
  
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
  const paramObj = selectedSingleParameterObj.value
  
  // Get the final value
  const finalValue = singleParameterOverride.value 
    ? singleCalcManualValue.value 
    : singleCalculationResult.value
  
  // Create metadata for the calculation
  const metadata = {
    inputs: {},
    formula: {
      id: paramObj.formula?.id,
      expression: paramObj.formula?.expression,
      name: paramObj.formula?.name
    },
    calculated_at: new Date().toISOString(),
    calculation_method: singleParameterOverride.value ? 'manual' : 'automated',
    manual_override: singleParameterOverride.value || false
  }
  
  // Store input values in metadata
  const required = getCalculationRequirements(paramObj)
  required.forEach(variable => {
    metadata.inputs[variable] = singleCalcInputs.value[variable]
  })
  
  // Update the main results object
  results.value[paramCode] = finalValue
  
  // Update input variables in the main results
  Object.keys(singleCalcInputs.value).forEach(key => {
    if (singleCalcInputs.value[key] && singleCalcInputs.value[key] !== '') {
      results.value[key] = singleCalcInputs.value[key]
    }
  })
  
  // Update additional fields
  uncertaintyValues.value[paramCode] = singleParameterUncertainty.value || null
  minRefValues.value[paramCode] = singleParameterMinRef.value || null
  maxRefValues.value[paramCode] = singleParameterMaxRef.value || null
  notesValues.value[paramCode] = singleParameterNotes.value || null
  
  // Update overrides
  overrides.value[paramCode] = singleParameterOverride.value
  
  // Create comprehensive payload
  const comprehensivePayload = {
    results: {
      [paramCode]: finalValue,
      // Include uncertainty and reference values with specific keys
      [`${paramCode}_uncertainty_value`]: singleParameterUncertainty.value || null,
      [`${paramCode}_min_ref_value`]: singleParameterMinRef.value || null,
      [`${paramCode}_max_ref_value`]: singleParameterMaxRef.value || null,
      [`${paramCode}_insertion_notes`]: singleParameterNotes.value || null,
      // Also include input variables
      ...Object.keys(singleCalcInputs.value).reduce((acc, key) => {
        if (singleCalcInputs.value[key] && singleCalcInputs.value[key] !== '') {
          acc[key] = singleCalcInputs.value[key]
        }
        return acc
      }, {})
    },
    overrides: {
      [paramCode]: singleParameterOverride.value
    },
    metadata: {
      [paramCode]: metadata
    }
  }
  
  // Emit to parent
  emit('calculatedResults', comprehensivePayload)
  
  // Auto-select next parameter if available
  if (nextAvailableParameter.value) {
    setTimeout(() => {
      selectNextParameter()
    }, 500)
  } else {
    // Close modal after a short delay
    setTimeout(() => {
      closeModal()
    }, 1000)
  }
}

const clearSingleCalculation = () => {
  resetSingleParameterFields()
}

const resetSingleParameterFields = () => {
  selectedSingleParameter.value = ''
  singleCalcInputs.value = {}
  singleCalculationResult.value = null
  singleParameterOverride.value = false
  singleCalcManualValue.value = ''
  singleParameterUncertainty.value = ''
  singleParameterMinRef.value = ''
  singleParameterMaxRef.value = ''
  singleParameterNotes.value = ''
}

const selectNextParameter = () => {
  if (nextAvailableParameter.value) {
    selectedSingleParameter.value = nextAvailableParameter.value.parameter_id.code
    onSingleParameterChange()
  }
}

const closeModal = () => {
  emit('close')
}

// Helper methods
const getCalculationRequirements = (parameter) => {
  if (!parameter.formula) return []
  return parameter.formula.variables?.map(v => v.name) || []
}

// Initialize with existing data
watch(() => props.existingResults, (newResults) => {
  if (newResults) {
    results.value = { ...newResults }
    
    // Initialize additional fields from parameters
    props.parameters.forEach(param => {
      const code = param.parameter_id?.code
      if (code) {
        uncertaintyValues.value[code] = param.uncertainty_value || null
        minRefValues.value[code] = param.min_ref_value || null
        maxRefValues.value[code] = param.max_ref_value || null
        notesValues.value[code] = param.insertion_notes || null
        
        if (param.manual_override) { 
          overrides.value[code] = true
        }
      }
    })
  }
}, { immediate: true })

// Watch for changes in single calculation inputs
watch(singleCalcInputs, () => {
  if (selectedSingleParameter.value && !singleParameterOverride.value) {
    calculateSingleParameter()
  }
}, { deep: true })
</script>

<style scoped>
.result-entry-modal {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}
</style>
