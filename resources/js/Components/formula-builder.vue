<template>
  <div class="formula-builder">
    <!-- Formula Editor -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <h3 class="text-lg font-medium mb-4">Construtor de Fórmulas</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Visual Formula Builder -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Construir Fórmula
          </label>
          <div class="space-y-2 mb-4">
            <button v-for="op in operations" :key="op.symbol"
                    @click="insertOperation(op.symbol)"
                    class="px-3 py-1 bg-gray-200 rounded text-sm hover:bg-gray-300">
              {{ op.label }}
            </button>
          </div>
          
          <div class="border rounded-md p-4 bg-gray-50 min-h-[100px]">
            <div class="flex flex-wrap gap-2 items-center">
              <span v-for="(item, index) in formulaParts" :key="index"
                    class="px-2 py-1 bg-blue-100 rounded text-sm">
                {{ item }}
                <button @click="removePart(index)" class="ml-1 text-red-500">×</button>
              </span>
            </div>
          </div>
        </div>

        <!-- Manual Formula Input -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Ou inserir fórmula manualmente
          </label>
          <textarea v-model="manualFormula" rows="4"
                    placeholder="Ex: ({colonies1} + {colonies2}) / {dilution} * 100"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
          
          <div class="mt-2 text-sm text-gray-500">
            Use <code>{nome_variavel}</code> para variáveis. Ex: <code>{temperatura}</code>
          </div>
        </div>
      </div>

      <!-- Variables Definition -->
      <div class="mt-6">
        <h4 class="font-medium mb-3">Variáveis da Fórmula</h4>
        <div v-for="(varName, index) in detectedVariables" :key="index"
             class="flex items-center gap-4 mb-2">
          <input v-model="variables[varName].label" placeholder="Nome para exibir"
                 class="flex-1 border-gray-300 rounded-md shadow-sm">
          <input v-model="variables[varName].unit" placeholder="Unidade (opcional)"
                 class="w-32 border-gray-300 rounded-md shadow-sm">
          <select v-model="variables[varName].type" 
                  class="border-gray-300 rounded-md shadow-sm">
            <option value="number">Número</option>
            <option value="integer">Inteiro</option>
            <option value="decimal">Decimal</option>
          </select>
        </div>
      </div>

      <!-- Validation & Testing -->
      <div class="mt-6 flex gap-4">
        <button @click="validateFormula" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
          Validar Fórmula
        </button>
        
        <button @click="testFormula" 
                :disabled="!validation.valid"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-400">
          Testar Cálculo
        </button>
        
        <button @click="saveFormula" 
                :disabled="!validation.valid"
                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:bg-gray-400">
          Guardar Fórmula
        </button>
      </div>

      <!-- Validation Results -->
      <div v-if="validation.checked" class="mt-4 p-3 rounded-md" 
           :class="validation.valid ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
        {{ validation.message }}
      </div>
    </div>

    <!-- Formula Testing Area -->
    <div v-if="validation.valid" class="bg-white p-6 rounded-lg shadow">
      <h4 class="font-medium mb-4">Testar Fórmula</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="varName in detectedVariables" :key="varName"
             class="border rounded-md p-3">
          <label class="block text-sm font-medium mb-1">
            {{ variables[varName].label || varName }}
          </label>
          <input v-model="testValues[varName]" type="number" step="any"
                 class="w-full border-gray-300 rounded-md shadow-sm">
          <div class="text-xs text-gray-500 mt-1">
            {{ variables[varName].unit }}
          </div>
        </div>
      </div>
      
      <div class="mt-4 p-3 bg-gray-50 rounded-md">
        <div class="flex justify-between items-center">
          <span>Resultado:</span>
          <span class="text-lg font-bold text-blue-600">
            {{ testResult !== null ? testResult : '--' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Saved Formulas Library -->
    <div class="mt-6">
      <h3 class="text-lg font-medium mb-4">Fórmulas Guardadas</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="formula in savedFormulas" :key="formula.id"
             class="border rounded-lg p-4 hover:shadow-md cursor-pointer"
             @click="loadFormula(formula)">
          <div class="font-medium">{{ formula.name }}</div>
          <div class="text-sm text-gray-600 mt-1">{{ formula.formula_expression }}</div>
          <div class="text-xs text-gray-500 mt-2">
            {{ formula.variables.length }} variáveis
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const formulaParts = ref([])
const manualFormula = ref('')
const variables = ref({})
const testValues = ref({})
const testResult = ref(null)
const validation = ref({ checked: false, valid: false, message: '' })
const savedFormulas = ref([])

const operations = [
  { symbol: '+', label: 'Soma' },
  { symbol: '-', label: 'Subtração' },
  { symbol: '*', label: 'Multiplicação' },
  { symbol: '/', label: 'Divisão' },
  { symbol: '(', label: '(' },
  { symbol: ')', label: ')' },
  { symbol: 'sqrt', label: 'Raiz' },
  { symbol: 'round', label: 'Arredondar' }
]

const detectedVariables = computed(() => {
  const formula = manualFormula.value || formulaParts.value.join(' ')
  const matches = formula.match(/\{([^}]+)\}/g) || []
  return [...new Set(matches.map(m => m.slice(1, -1)))]
})

watch(detectedVariables, (newVars) => {
  newVars.forEach(varName => {
    if (!variables.value[varName]) {
      variables.value[varName] = {
        label: varName,
        unit: '',
        type: 'number'
      }
    }
  })
})

const insertOperation = (symbol) => {
  formulaParts.value.push(symbol)
}

const removePart = (index) => {
  formulaParts.value.splice(index, 1)
}

const validateFormula = async () => {
  const formula = manualFormula.value || formulaParts.value.join(' ')
  
  try {
    const response = await axios.post('/api/formulas/validate', {
      formula: formula
    })
    
    validation.value = {
      checked: true,
      valid: response.data.valid,
      message: response.data.valid ? 
        'Fórmula válida!' : 
        `Erros: ${response.data.errors.join(', ')}`
    }
  } catch (error) {
    validation.value = {
      checked: true,
      valid: false,
      message: 'Erro na validação da fórmula'
    }
  }
}

const testFormula = async () => {
  const formula = manualFormula.value || formulaParts.value.join(' ')
  
  try {
    const response = await axios.post('/api/formulas/test', {
      formula: formula,
      variables: testValues.value
    })
    
    testResult.value = response.data.result
  } catch (error) {
    alert('Erro ao testar fórmula: ' + error.response.data.message)
  }
}

const saveFormula = async () => {
  const formula = manualFormula.value || formulaParts.value.join(' ')
  
  const name = prompt('Nome para a fórmula:')
  if (!name) return
  
  try {
    await axios.post('/api/formulas', {
      name: name,
      formula_expression: formula,
      formula_code: formula,
      variables: variables.value,
      output_unit: 'resultado'
    })
    
    alert('Fórmula guardada com sucesso!')
    loadSavedFormulas()
  } catch (error) {
    alert('Erro ao guardar fórmula')
  }
}

const loadFormula = (formula) => {
  manualFormula.value = formula.formula_expression
  variables.value = formula.variables
  validateFormula()
}

const loadSavedFormulas = async () => {
  const response = await axios.get('/api/formulas')
  savedFormulas.value = response.data
}

// Load saved formulas on component mount
loadSavedFormulas()
</script>