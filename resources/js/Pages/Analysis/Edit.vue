<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { router, useForm, usePage } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon, MinusIcon, PlusIcon } from "@heroicons/vue/24/outline";
// Removed unused imports: useConfidenceInterval, calculateMicrobialDispersion, useCfuCalculator
import CalculationResultEntry from '@/Components/CalculationResultEntry.vue';
import axios from 'axios'; // Ensure axios is imported if used directly

defineOptions({
  layout: Layout
});

const props = defineProps({
    action: String,
    record: Object,
    parameters: Array // Still exists, but likely unused in this component now
});

// The results array holds ALL parameters (calculated, microbiology, manual)
const results = ref([]);
const showCalculationEntry = ref(false);
const calculationParameters = ref([]);

const form = useForm({
    action: props.action,
    id: props.record?.id,
    cl: props.record?.cl,
    cl_id: props.record?.cl_id,
    profile_id: props.record?.profile_id,
    sample_id: props.record?.sample_id,
    department_id: props.record?.department_id,
    type_id: props.record?.type_id,
    results: [] // This holds the main data structure
});

onMounted(() => {
  loadResultParameters();
});

// --- Calculation Data Mapping & Management ---

/**
 * Maps the flat form.results array into an object containing all inputs (variables) 
 * and existing calculated outputs. This is the crucial data structure for the child component.
 * Keys are: {variable_name: value, parameter_code: value}
 */
const existingCalculationResults = computed(() => {
  const data = {};
  
  // 1. Collect values for all calculated parameters (their output)
  form.results.forEach(p => {
    if (p.requires_calculation && p.parameter_id?.code && p.inserted_value) {
      data[p.parameter_id.code] = p.inserted_value;
    }
  });

  // 2. Collect all raw inputs (variables) needed for calculation
  calculationParameters.value.forEach(calcParam => {
    // Assuming 'formula' and 'variables' are available on the item from the API call
    const requiredVariables = calcParam.formula?.variables?.map(v => v.name) || [];
    
    // For each required variable name (e.g., 'LENGTH', 'WIDTH'), check if it exists in form.results
    // NOTE: This assumes raw input variables are stored in form.results with the variable name as the key/code.
    // Since we don't know the exact structure of the input variables, we can only pull them from form.results 
    // IF the variable name matches a code in the array. Since the component only handles calculated parameters, 
    // we'll primarily rely on the child component's input mapping. 
    
    // TEMPORARY SOLUTION: Since the input variables might not exist as a parameter in form.results, 
    // we assume for now the variables are stored as properties on the result object OR 
    // the variable names are also included in form.results (e.g., if 'LENGTH' is a parameter code).
    // Given the component structure, we MUST pass the input values that have already been entered into the form.

    // A more robust solution requires knowing where the raw inputs are stored. 
    // Since the child component creates the raw input fields based on `allRequiredVariables`, 
    // we assume any existing data matching those variable names is also stored in `form.results`.

    // For demonstration, we just return the currently saved values (parameter codes and known variables).
  });
  
  // The child component will use the keys that match the required variable names for its inputs.
  return data;
});

// --- Utility Functions ---

// FIXED: Consistent CFU calculation function
function calculateCFU(result) {
  const { cfu1, cfu2, d1, d2, volume = 1 } = result;
  const sumC = Number(cfu1) + Number(cfu2);
  const d = Math.min(Number(d1), Number(d2));
  
  if (d === 0) return 0; // Avoid division by zero

  const N = sumC / (volume * 1.1 * d);
  return N.toExponential(2);
}

// NEW: Open calculation entry for calculated parameters
const openCalculationEntry = () => {
  // Filter parameters that require calculation
  // NOTE: Assuming `active` and `requires_calculation` properties are available directly on the result object.
  calculationParameters.value = form.results?.filter(p =>
    p.requires_calculation && p.active
  ) || [];
  
  if (calculationParameters.value.length > 0) {
    showCalculationEntry.value = true;
  } else {
    alert("Nenhum parâmetro calculado definido para esta amostra.");
  }
};

/**
 * FIX: This function now receives the calculated results (key: code, value: result) and 
 * updates the main form.results array AND stores the raw input variables.
 */
// const handleCalculatedResults = (calculatedData) => {
//   // Separate calculated outputs (parameter codes) from raw inputs (variable names)
//   const calculatedOutputs = {};
//   const inputVariables = {};

//   calculationParameters.value.forEach(p => {
//     const code = p.parameter_id?.code;
//     if (calculatedData[code] !== undefined) {
//       calculatedOutputs[code] = calculatedData[code];
//     }
    
//     // Get the required input variables for this calculated parameter
//     p.formula?.variables?.forEach(v => {
//       const varName = v.name;
//       if (calculatedData[varName] !== undefined) {
//         inputVariables[varName] = calculatedData[varName];
//       }
//     });
//   });

//   // 1. Update form results with CALCULATED OUTPUTS (e.g., AREA)
//   Object.keys(calculatedOutputs).forEach(parameterCode => {
//     const resultIndex = form.results.findIndex(r => 
//       r.parameter_id?.code === parameterCode // Match by code now
//     );
    
//     if (resultIndex !== -1) {
//       // Update the final calculated value
//       form.results[resultIndex].inserted_value = calculatedOutputs[parameterCode];
//     }
//   });

//   // 2. Store RAW INPUT VARIABLES (e.g., LENGTH, WIDTH) in a new structure 
//   //    or find a way to save them alongside the results.
//   //    Since the child component gives us the variable names (which are not parameter codes),
//   //    we must ensure these raw variables are correctly mapped back to form.results 
//   //    or are saved separately to be reloaded later.
  
//   // CRITICAL ASSUMPTION: The raw input variable names (e.g., 'L') are also treated as 
//   // parameter codes in form.results for persistence. If this is not true, 
//   // you must create a separate array/object in `form` to store and load these raw inputs.
//   Object.keys(inputVariables).forEach(variableName => {
//     const resultIndex = form.results.findIndex(r => 
//       r.parameter_id?.code === variableName // Attempt to match variable name as parameter code
//     );
    
//     if (resultIndex !== -1) {
//       form.results[resultIndex].inserted_value = inputVariables[variableName];
//     } else {
//       // If the variable is not a parameter code, we might need to push a new temp item to results 
//       // or store it in the main form object. For simplicity, we'll log it.
//       console.warn(`Input variable ${variableName} does not match any existing parameter code and may not be saved.`);
//       // For now, we will not save it unless it's a known parameter.
//     }
//   });
  
//   showCalculationEntry.value = false;
// };

const handleCalculatedResults = (payload) => {

  const { results: calculatedData, overrides: overridesData, metadata: calculationMetadata } = payload;
  
  // 1. Iterate through the parent's form.results array
  form.results = form.results.map(result => {
    const code = result.parameter_id?.code;
    
    // Check if this result is a calculated parameter
    if (result.requires_calculation && code) {
      
      // Update calculated value and metadata for the final output parameter (e.g., AREA)
      if (calculatedData[code]) {
        return {
          ...result,
          inserted_value: calculatedData[code], // Update the final value
          calculation_metadata: calculationMetadata[code] || null, // Attach metadata
          manual_override: overridesData[code] || false, // Attach override status
        };
      }
    } 
    
    // Check if this result is an input variable parameter (if inputs are also stored as parameters)
    // NOTE: This logic depends on whether raw inputs (like 'LENGTH') are also in form.results.
    if (calculatedData[code]) {
      // If the code matches a variable name we got from the calculation, update the input value.
      return {
          ...result,
          inserted_value: calculatedData[code],
      };
    }
    
    return result; // Return item unchanged if not a calculated/input parameter
  });
  
  showCalculationEntry.value = false;
};


// NEW: Check if there are calculated parameters
const hasCalculatedParameters = computed(() => {
  return form.results?.some(p => p.requires_calculation && p.active);
});

// --- Existing Functions (Adjusted) ---

const actionText = ref({
  analyze: 'Inserção de Resultados',
  verify: 'Verificação de Resultados',
  approve: 'Validação de Resultados',
});

// FIXED: Ensure form.results gets the parameter code
async function loadResultParameters() {
  try {
    const response = await axios.get('/results/getDefaultResultsData?action=' + props.action + '&sample_id=' + props.record?.sample_id?.value);

    form.results = response.data.map(result => ({
      ...result,
      // Ensure all calculation fields exist
      cfu1: result.cfu1 || null,
      cfu2: result.cfu2 || null,
      d1: result.d1 || null,
      d2: result.d2 || null,
      volume: result.volume || 1,
      // CRITICAL: Ensure parameter_id includes the code for lookups
      parameter_id: { 
          ...result.parameter_id, 
          code: result.parameter_id?.code // Must be present
      } 
    }));

  // console.log(response.data);

  } catch (error) {
    console.error('Error loading results:', error);
    form.results = [];
  }

}

// Existing data loading functions (omitted for brevity)
// ... loadParameters, loadResultCategories, loadDepartments, etc. ...
function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            name: result.name,
            code: result.code, // Ensure code is returned for mapping
            requires_calculation: result.requires_calculation
            };
        })
        );
    });
}

function loadResultCategories(query, setOptions) {
    fetch('/resultcategories/getResultCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadDepartments(query, setOptions) {
    fetch('/departments/getDepartment?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadProfiles(query, setOptions) {
    fetch('/profiles/getProfile?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadSamples(query, setOptions) {
    fetch('/samples/getCode?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadLabCodes(query, setOptions) {
    fetch('/labcodes/getCode?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadAnalysisCategories(query, setOptions) {
    fetch('/analysiscategories/getAnalysisCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadUnits(query, setOptions) {
    fetch('/units/getUnit?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadProtocols(query, setOptions) {
    fetch('/protocols/getProtocol?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadNwps(query, setOptions) {
    fetch('/nwps/getNwp?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadStandards(query, setOptions) {
    fetch('/standards/getStandard?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('profiles.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)

let requestCounterAnalysis = (data) =>{
  useForm({
    result_id: data
  })
  .post(route('counteranalysis.store'), {
    preserveScroll: true
  })
}

let submit = () => {

if(!form.id) {
  form.post(route('analysis.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('analysis.update',{analysis: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        // form.reset()
      },
  });
}

}

let submitResults = () => {

  form.post(route('results.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });

}

const addResult = () => {
    form.results.push({
        parameter_id: '',
        unit_id: '',
        protocol_id: '',
        standard_id: '',
        nwp_id: '',
        product_id: '',
        count: true,
        min_ref_value: null,
        max_ref_value: null,
        dilutions: null,
        type_id: '',
        inserted_value: '',
        uncertainty_value: '',
        cfu1: null,
        cfu2: null,
        d1: null,
        d2: null,
        volume: 1,
        active: true, // Assuming new results are active
        requires_calculation: false, // Assuming new results are manual by default
        calculation_metadata: null,
    });
}

const removeResult = (index) => {
    form.results.splice(index, 1);
}
</script>

<template>
<div class="border-b border-gray-200 pb-5">
    {{ form.cl_id?.label }} - {{ form.department_id?.label }}
    </div>

<form @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">
            </div>

        <hr>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4" v-if="hasCalculatedParameters">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-blue-900">Parâmetros Calculados</h3>
              <p class="text-sm text-blue-700 mt-1">
                <!-- {{ calculationParameters.length }} parâmetro(s) que requerem cálculo automático -->
              </p>
            </div>
            <button 
              @click="openCalculationEntry"
              type="button"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium"
            >
              Abrir Calculadora
            </button>
          </div>
        </div>

        <div class="border-b border-gray-900/10 pb-6">
            </div>

      <ul role="list" class="grid grid-cols-10 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(result, index) in form.results" :key="index" class="rounded-xl border"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <div class="flex items-center gap-x-4 border-b border-gray-900/5 p-3"
          :class="{
            'bg-blue-900 text-white border-blue-900': props.action === 'analyze',
            'bg-yellow-400 text-gray-900 border-yellow-400': props.action === 'verify',
            'bg-green-600 text-white border-green-600': props.action === 'approve',
          }"
          >
            <div class="text-sm font-medium leading-6"
            :class="{
              'text-white': props.action === 'analyze',
              'text-gray-900': props.action === 'verify',
              'text-white': props.action === 'approve',
            }"
            >
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
            </div>
            {{ index + 1 }}º Resultado
            
            <span v-if="result.requires_calculation"
                  class="ml-2 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
              Calculado
            </span>
            
            <button v-if="props.action !== 'analyze' && !result.requested_counter_analysis" @click="requestCounterAnalysis(result.result_id)" type="button" class="inline-flex justify-center rounded-md bg-orange-900 px-3 py-1.8 text-xs font-semibold text-white shadow-sm hover:bg-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-800">Solicitar CA</button>
              
            <button @click="removeResult(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="parameter_id" class="block text-sm font-medium leading-6 text-gray-900">Parâmetro</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`results.${index}.parameter_id`]" v-model="result.parameter_id" :load-options="loadParameters"/>
                  </div>
                  <p v-if="form.errors[`results.${index}.parameter_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.parameter_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.record?.type_id?.value === 2 && !result.requires_calculation">
              </div>

            <div class="gap-x-4 py-3" v-if="props.action === 'analyze'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="inserted_value" class="block text-sm font-medium leading-6 text-gray-900">
                    Resultado
                    <span v-if="result.requires_calculation" class="text-xs text-purple-600 ml-1">
                      (calculado automaticamente)
                    </span>
                  </label>
                  <div class="mt-2">
                    <input 
                      :disabled="result.requires_calculation || (props.record?.type_id?.value === 2 && !result.requires_calculation)"
                      v-model="result.inserted_value" 
                      type="text" 
                      :name="`item-${index}-error`" 
                      :id="`item-${index}-error`" 
                      class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" 
                      :class="{ 
                        'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': result.inserted_value < result.min_ref_value || result.inserted_value > result.max_ref_value,
                        'bg-gray-100': result.requires_calculation
                      }" 
                    />
                  </div>
                  <p v-if="form.errors[`results.${index}.inserted_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.inserted_value`] }}</p>
                </div>
              </dd>
            </div>

            </dl>
        </li>
      </ul>
      <p v-if="form.errors.results" class="mt-2 text-xs text-red-600">{{ form.errors.results }}</p>
    
    </div>
    
    <!-- <div class="mt-6 flex items-center justify-end gap-x-6">
      </div> -->

      <div class="mt-6 flex items-center justify-end gap-x-6">
            <button v-if="!form.results?.some((item) => { return item['requested_counter_analysis'] === true}) && form.results?.length > 0"
                    @click="submitResults" 
                    type="button" 
                    :disabled="form.processing" 
                    class="nline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                    :class="{
                    'bg-blue-900 text-white hover:bg-blue-800 focus-visible:outline-blue-900': props.action === 'analyze',
                    'bg-yellow-400 text-gray-900 hover:bg-yellow-400 focus-visible:outline-yellow-500': props.action === 'verify',
                    'bg-green-600 text-white hover:bg-green-500 focus-visible:outline-green-600': props.action === 'approve',
                    }"
                    >
                    {{ props.action == 'analyze' ? $t('gestlab.general.labels.results.insert_result') : props.action == 'verify' ? $t('gestlab.general.labels.results.verify_result') : $t('gestlab.general.labels.results.approve_result') }}
                </button>
        </div>

  </form>

  <div v-if="showCalculationEntry" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Calculadora de Parâmetros</h3>
          <button @click="showCalculationEntry = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <CalculationResultEntry
          :sample="record"
          :parameters="calculationParameters"
          :existing-results="existingCalculationResults"
          @calculated-results="handleCalculatedResults"
          :action="form.action"
        />
      </div>
    </div>
  </div>

</template>