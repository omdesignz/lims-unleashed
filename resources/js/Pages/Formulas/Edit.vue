<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  TrashIcon,
  PlusCircleIcon,
  ArrowLeftIcon,
} from "@heroicons/vue/24/outline";
import { trans } from "laravel-vue-i18n";
import confirmDialog from "@/Components/confirm-dialog.vue";
import FormulaDisplay from "@/Components/formula-display.vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: Object, // The formula to edit
});

const form = useForm({
  id: props.record?.id,  
  name: props.record?.data?.name || "",
  code: props.record?.data?.code || "",
  category: props.record?.data?.category || "general",
  expression: props.record?.data?.expression || null,
  formula_expression: props.record?.data?.formula_expression || "",
  variables: props.record?.data?.variables || [],
  output_unit: props.record?.data?.output_unit || "",
  decimal_places: props.record?.data?.decimal_places || 2,
  description: props.record?.data?.description || "",
  is_active: props.record?.data?.is_active ?? true,
});

const latexExpression = ref("");
const result = ref("");
const letters = ref([]);
const showDeleteConfirmation = ref(false);
const showDeleteFormulaConfirmation = ref(false);

// Enhanced variables with better structure (used only for final submission payload)
const enhancedVariables = computed(() => {
  return form.variables.map(variable => ({
    ...variable,
    // Ensure all required fields exist for the backend
    label: variable.label || variable.name,
    unit: variable.unit || "",
    type: variable.type || "number"
  }));
});

onMounted(() => {
  // Initialize with the formula data
  if (props.record) {
    latexExpression.value = props.record?.data?.expression || "";
    // Extract letters from existing variables
    letters.value = props.record?.data?.variables?.map(v => v.name) || [];
  }
});

// FIXED: Extract variables only when expression changes significantly
const extractVariablesFromExpression = (expression) => {
  if (!expression) return [];
  
  // Extract variable names (words that start with a letter)
  const variablePattern = /[a-zA-Z_][a-zA-Z0-9_]*/g;
  const matches = expression.match(variablePattern) || [];
  
  // Remove duplicates and Math functions
  const mathFunctions = ['sqrt', 'log', 'log10', 'exp', 'abs', 'round', 'ceil', 'floor', 'max', 'min', 'pow', 'PI', 'E'];
  const uniqueVars = [...new Set(matches)].filter(v => 
    !mathFunctions.includes(v) && 
    !v.match(/^[0-9]/) // Don't include things that start with numbers
  );
  
  return uniqueVars;
};

// NEW: Manual variable extraction button
const extractVariables = () => {
  if (form.expression) {
    const extractedVars = extractVariablesFromExpression(form.expression);
    letters.value = extractedVars;
    
    extractedVars.forEach((variableName) => {
      if (!form.variables.some(v => v.name === variableName)) {
        form.variables.push({
          name: variableName,
          label: variableName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
          value: "",
          unit: "",
          type: "number",
          description: "",
        });
      }
    });
    
    alert(`Foram encontradas ${extractedVars.length} variáveis: ${extractedVars.join(', ')}`);
  } else {
    alert("Introduza primeiro a expressão da fórmula.");
  }
};

// Safe JavaScript evaluation
const evaluateFormula = () => {
  // Build variables object
  const vars = {};
  form.variables.forEach((variable) => {
    if (variable.name && variable.value !== "" && variable.value !== null) {
      const numValue = parseFloat(variable.value);
      vars[variable.name] = isNaN(numValue) ? 0 : numValue;
    } else {
      vars[variable.name] = 0;
    }
  });

  try {
    // Validate expression
    if (!form.expression || form.expression.trim() === "") {
      throw new Error("Introduza uma expressão de fórmula.");
    }

    // Check for required variables in expression
    const variableNames = form.variables.map(v => v.name).filter(name => name);
    const expressionVars = form.expression.match(/[a-zA-Z_][a-zA-Z0-9_]*/g) || [];
    // Filter out Math functions and constants from the expression variables check
    const mathFunctionsAndConstants = ['sqrt', 'log', 'log10', 'exp', 'abs', 'round', 'ceil', 'floor', 'max', 'min', 'pow', 'PI', 'E'];
    const requiredVars = expressionVars.filter(v => !mathFunctionsAndConstants.includes(v));
    
    const missingVars = requiredVars.filter(varName => !variableNames.includes(varName));
    
    if (missingVars.length > 0) {
      throw new Error(`Missing variables in definition: ${missingVars.join(', ')}`);
    }

    // Create safe evaluation context
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
    };

    // Create safe evaluation function
    const functionBody = `return ${form.expression}`;
    const safeEval = new Function(...Object.keys(context), functionBody);
    const numericValue = safeEval(...Object.values(context));
    
    if (isNaN(numericValue)) {
      throw new Error("Calculation resulted in NaN - check variable values");
    }
    
    if (!isFinite(numericValue)) {
      throw new Error("Calculation resulted in infinite value - check for division by zero");
    }
    
    // Format result
    result.value = parseFloat(numericValue).toFixed(form.decimal_places);
  } catch (error) {
    result.value = "Error: " + error.message;
  }
};

// Quick test with random values
const quickTest = () => {
  form.variables.forEach(variable => {
    if (variable.type === "number" && variable.name) {
      // Generate reasonable test values based on context
      let testValue;
      if (variable.name.includes('colony')) {
        testValue = Math.floor(Math.random() * 200) + 10; // 10-210 colonies
      } else if (variable.name.includes('mass') || variable.name.includes('mp') || variable.name.includes('ma')) {
        testValue = (Math.random() * 50 + 5).toFixed(3); // 5-55g with 3 decimals
      } else {
        testValue = (Math.random() * 100).toFixed(2); // Generic 0-100 with 2 decimals
      }
      variable.value = testValue;
    }
  });
  evaluateFormula();
};

// FIXED: Only update variables when expression meaningfully changes
const updateExpression = (expression) => {
  form.expression = expression;
  // Auto-generate user-friendly expression
  form.formula_expression = expression.replace(/([a-zA-Z_][a-zA-Z0-9_]*)/g, '{$1}');
  
  // Only extract variables when we have a substantial expression
  if (expression && expression.length > 2) {
    const extractedVars = extractVariablesFromExpression(expression);
    letters.value = extractedVars;
    
    // Only add new variables that don't exist
    extractedVars.forEach((variableName) => {
      if (!form.variables.some(v => v.name === variableName)) {
        form.variables.push({
          name: variableName,
          label: variableName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), // Better default label
          value: "",
          unit: "",
          type: "number",
          description: "",
        });
      }
    });
    
    // Remove variables that are no longer in the expression
    form.variables = form.variables.filter(v => 
      extractedVars.includes(v.name) || v.name === "" // Keep empty ones for manual entry
    );
  }
};



// Add variable
const addVariable = () => {
  form.variables.push({
    name: "",
    label: "",
    value: "",
    unit: "",
    type: "number",
    description: "",
  });
};

// Remove variable
const removeVariable = (index) => {
  form.variables.splice(index, 1);
};

// Auto-generate code from name (only if code is empty)
watch(() => form.name, (newName) => {
  if (newName && !form.code) {
    form.code = newName
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/(^_|_$)/g, '');
  }
});

// Validate before submission
const validateFormula = () => {
  if (!form.name || !form.code || !form.expression) {
    alert("Preencha todos os campos obrigatórios: nome, código e expressão.");
    return false;
  }

  if (form.variables.length === 0) {
    alert("Defina pelo menos uma variável para a fórmula.");
    return false;
  }

  // Test evaluation
  try {
    const testVars = {};
    form.variables.forEach(variable => {
      testVars[variable.name] = 1;
    });

    const context = { ...testVars, Math };
    const functionBody = `return ${form.expression}`;
    // Using try/catch within evaluateFormula is better, but this static test is a quick check
    new Function(...Object.keys(context), functionBody)(...Object.values(context));
    
    return true;
  } catch (error) {
    alert("A validação da fórmula falhou: " + error.message);
    return false;
  }
};

// Update formula
const updateFormula = async () => {
  const isValid = await validateFormula();
  if (!isValid) return;

  // ✅ CORRECTION: Use form.transform to correctly structure the payload before sending.
  // This ensures the 'variables' array contains the final structure expected by the backend.
  form
    .transform(data => ({
        ...data,
        variables: enhancedVariables.value, 
    }))
    .put(route("formulas.update", { formula: props.record?.data?.id }), { 
      // Do not use the `data` option here, only Inertia options
      preserveScroll: true,
      onSuccess: () => {},
    });
};

// Delete formula
const deleteFormula = () => {
  if (confirm("Tem a certeza de que pretende eliminar esta fórmula? Esta acção não pode ser anulada.")) {
    router.get(route("formulas.destroy", { recordIds: [props.record.data.id] }), {
      preserveScroll: true,
      onSuccess: () => {
        // Redirect to formulas index or show success message
        router.visit(route('formulas.index'));
      },
    });
  }
};

// Reset form to original values
const resetForm = () => {
  if (props.record) {
    form.name = props.record?.data?.name;
    form.code = props.record?.data?.code;
    form.category = props.record?.data?.category;
    form.expression = props.record?.data?.expression;
    form.formula_expression = props.record?.data?.formula_expression;
    // Deep clone the variables array to break reference
    form.variables = JSON.parse(JSON.stringify(props.record?.data?.variables || []));
    form.output_unit = props.record?.data?.output_unit;
    form.decimal_places = props.record?.data?.decimal_places;
    form.description = props.record?.data?.description;
    form.is_active = props.record?.data?.is_active;
    
    latexExpression.value = props.record?.data?.expression || "";
    letters.value = props.record?.data?.variables?.map(v => v.name) || [];
    result.value = "";
  }
};

// Check if form has changes
const hasChanges = computed(() => {
  return form.isDirty;
});

// Go back to formulas list
const goBack = () => {
  router.visit(route('formulas.index'));
};
</script>

<template>
  <div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <div class="flex items-center justify-between">
      <div>
        <button
          @click="goBack"
          class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2"
        >
          <ArrowLeftIcon class="h-4 w-4 mr-1" />
          Voltar para Fórmulas
        </button>
        <h3 class="text-base font-semibold leading-6 text-gray-900">
          Editar Fórmula: {{ props.record?.data?.name }}
        </h3>
      </div>
      <div class="flex space-x-2">
        <button
          @click="resetForm"
          :disabled="!hasChanges"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Redefinir
        </button>
        <button
          @click="showDeleteFormulaConfirmation = true"
          class="inline-flex items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
        >
          Deletar Fórmula
        </button>
      </div>
    </div>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">
      A modificar a fórmula própria. As alterações são guardadas quando você enviar o formulário.
    </p>
  </div>
  
  <form @submit.prevent="updateFormula">
    <div class="space-y-12 mb-2">
      <div class="border-dotted border-2 border-blue-900 rounded-md p-4 flex items-center justify-between">
        <div class="flex-1">
          <FormulaDisplay :formula="latexExpression" />
          <div v-if="letters.length" class="text-sm text-gray-600 mt-2">
            Variáveis: {{ letters.join(', ') }}
          </div>
        </div>
        <div v-if="result !== null && result !== ''" class="text-lg font-bold ml-4" :class="result.includes('Error') ? 'text-red-600' : 'text-green-600'">
          = {{ result }} <span v-if="!result.includes('Error') && form.output_unit">{{ form.output_unit }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-12">
        <div class="sm:col-span-4">
          <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
            Nome *
          </label>
          <div class="mt-2">
            <input
              v-model="form.name"
              type="text"
              name="name"
              id="name"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
              :class="form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500' : ''"
            />
          </div>
          <p v-if="form.errors.name" class="mt-2 text-xs text-red-600">
            {{ form.errors.name }}
          </p>
        </div>

        <div class="sm:col-span-3">
          <label for="code" class="block text-sm font-medium leading-6 text-gray-900">
            Código *
          </label>
          <div class="mt-2">
            <input
              v-model="form.code"
              type="text"
              name="code"
              id="code"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
              :class="form.errors.code ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500' : ''"
            />
          </div>
          <p v-if="form.errors.code" class="mt-2 text-xs text-red-600">
            {{ form.errors.code }}
          </p>
        </div>

        <div class="sm:col-span-3">
          <label for="category" class="block text-sm font-medium leading-6 text-gray-900">
            Categoria
          </label>
          <div class="mt-2">
            <select
              v-model="form.category"
              name="category"
              id="category"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
            >
              <option value="general">Geral</option>
              <option value="microbiology">Microbiología</option>
              <option value="physicochemical">Físico-Química</option>
              <option value="custom">Personalizado</option>
            </select>
          </div>
        </div>

        <div class="sm:col-span-2">
          <label for="decimal_places" class="block text-sm font-medium leading-6 text-gray-900">
            Casas Decimais
          </label>
          <div class="mt-2">
            <input
              v-model="form.decimal_places"
              type="number"
              min="0"
              max="8"
              name="decimal_places"
              id="decimal_places"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
            />
          </div>
        </div>
      </div>

      <div class="sm:col-span-4">
        <label for="output_unit" class="block text-sm font-medium leading-6 text-gray-900">
          Unidade do Resultado
        </label>
        <div class="mt-2">
          <input
            v-model="form.output_unit"
            type="text"
            name="output_unit"
            id="output_unit"
            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
            placeholder="Ex.: %, UFC/g, mg/L"
          />
        </div>
      </div>

      <div class="sm:col-span-full">
        <div class="flex items-center justify-between mb-2">
          <label for="expression" class="block text-sm font-medium leading-6 text-gray-900">
            Expressão *
          </label>
          <button
            @click="extractVariables"
            type="button"
            class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200"
          >
            Extrair Variáveis
          </button>
        </div>
        <div class="mt-2">
          <!-- <MathInput
            v-model:value="latexExpression"
            @update:expression="updateExpression"
            placeholder="Introduza a fórmula (ex.: a + b * c)"
          /> -->

          <input type="text" 
                    class="block w-full rounded-md border-0 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6" 
                    v-model="latexExpression" 
                    placeholder="Introduza a fórmula (ex.: a + b * c)"
                    @keyup="(e) => {
                      updateExpression(e.target.value),

                      evaluateFormula()
                    }"
              />
        </div>
        <div v-if="form.formula_expression" class="mt-2 text-sm text-gray-600">
          <strong>Formato:</strong> <code>{{ form.formula_expression }}</code>
        </div>
      </div>

      <div class="flex justify-end space-x-4">
        <button
          @click="quickTest"
          type="button"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          Teste Rápido
        </button>
        <button
          @click="evaluateFormula"
          type="button"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
        >
          Calcular
        </button>
      </div>

      <div class="sm:col-span-full">
        <label for="description" class="block text-sm font-medium leading-6 text-gray-900">
          Descrição
        </label>
        <div class="mt-2">
          <textarea
            v-model="form.description"
            name="description"
            id="description"
            rows="3"
            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
            placeholder="Descreva o que esta fórmula calcula e como deve ser utilizada..."
          />
        </div>
      </div>

      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          Variáveis ({{ form.variables.length }})
          <button
            @click="addVariable"
            class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto"
          >
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">
          Defina as variáveis de entrada para a fórmula
        </p>

        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(variable, index) in form.variables" 
            :key="index"
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
          >
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-medium text-gray-900">
                Variável {{ index + 1 }}
              </h3>
              <button
                @click="removeVariable(index)"
                class="text-gray-400 hover:text-red-500 transition-colors"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-700">Nome *</label>
                <input
                  v-model="variable.name"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="Ex.: temperatura"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700">Etiqueta</label>
                <input
                  v-model="variable.label"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="Ex.: Temperatura"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700">Tipo</label>
                <select
                  v-model="variable.type"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                  <option value="number">Número</option>
                  <option value="integer">Inteiro</option>
                  <option value="decimal">Decimal</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700">Unidade</label>
                <input
                  v-model="variable.unit"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="Ex.: °C, mg, mL"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700">Valor de Teste</label>
                <input
                  v-model.number="variable.value"
                  :type="variable.type === 'integer' ? 'number' : 'text'"
                  :step="variable.type === 'integer' ? '1' : 'any'"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  placeholder="Introduza um valor para teste"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
      <div class="flex items-center">
        <input
          v-model="form.is_active"
          type="checkbox"
          name="is_active"
          id="is_active"
          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"
        />
        <label for="is_active" class="ml-2 text-sm text-gray-900">
          Activa (disponível para uso)
        </label>
      </div>

      <div class="flex items-center justify-end gap-x-6">
        <button
          type="button"
          @click="goBack"
          class="text-sm font-semibold leading-6 text-gray-900"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="form.processing || !hasChanges"
          class="inline-flex justify-center rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ form.processing ? 'A actualizar...' : 'Actualizar Fórmula' }}
        </button>
      </div>
    </div>
  </form>

  <confirm-dialog
    @canceled="showDeleteFormulaConfirmation = false"
    @close="showDeleteFormulaConfirmation = false"
    @confirmed="deleteFormula"
    v-if="showDeleteFormulaConfirmation"
    title="Eliminar Fórmula"
    description="Tem a certeza de que pretende eliminar esta fórmula? Esta acção não pode ser anulada."
    confirm="Eliminar"
    cancel="Cancelar"
  />
</template>
