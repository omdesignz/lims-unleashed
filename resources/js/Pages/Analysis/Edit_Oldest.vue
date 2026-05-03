<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { router, useForm, usePage } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon, MinusIcon, PlusIcon } from "@heroicons/vue/24/outline";
import { useConfidenceInterval } from '@/Composables/Uncertainties/useConfidenceInterval';
import { calculateMicrobialDispersion } from '@/Composables/Uncertainties/useCalculateMicrobialDispersion';
import { useCfuCalculator } from '@/Composables/useMicrobialCount';
import CalculationResultEntry from '@/Components/CalculationResultEntry.vue';

defineOptions({
  layout: Layout
});

const props = defineProps({
    action: String,
    record: Object,
    parameters: Array
});

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
    results: []
});

onMounted(() => {
  loadResultParameters();
});

// FIXED: Consistent CFU calculation function
function calculateCFU(result) {
  const { cfu1, cfu2, d1, d2, volume = 1 } = result;
  const sumC = Number(cfu1) + Number(cfu2);
  const d = Math.min(Number(d1), Number(d2));
  
  if (d === 0) return 0; // Avoid division by zero

  const N = sumC / (volume * 1.1 * d);
  return N.toExponential(2);
}

// FIXED: Enhanced result calculation that handles both calculated and manual parameters
const calculateResults = () => {
  form.results.forEach(item => {
    // Only calculate if it's a microbiology parameter with CFU data
    if (item.cfu1 !== undefined && item.cfu2 !== undefined) {
      item.inserted_value = calculateCFU(item);
    }
    // For calculated parameters, they will be handled by CalculationResultEntry
  });
};

// NEW: Check if parameter requires calculation
const requiresCalculation = (parameterId) => {
  const parameter = form.results?.find(p => p.parameter_id?.value === parameterId?.value);
  return parameter?.requires_calculation || false;
};

// NEW: Get formula for parameter
const getParameterFormula = (parameterId) => {
  const parameter = form.results?.find(p => p.parameter_id?.value === parameterId?.value);
  return parameter?.formula || null;
};

// NEW: Open calculation entry for calculated parameters
const openCalculationEntry = () => {
  // Filter parameters that require calculation
  calculationParameters.value = form.results?.filter(p =>
    p.requires_calculation && p.active
  ) || [];
  
  if (calculationParameters.value.length > 0) {
    showCalculationEntry.value = true;
  } else {
    alert("Nenhum parâmetro calculado definido para esta amostra.");
  }
};

// NEW: Handle calculated results from CalculationResultEntry
const handleCalculatedResults = (calculatedResults) => {
  // Update form results with calculated values
  Object.keys(calculatedResults).forEach(parameterCode => {
    const resultIndex = form.results.findIndex(r => 
      r.parameter_id?.value && getParameterByCode(r.parameter_id.value)?.code === parameterCode
    );
    
    if (resultIndex !== -1) {
      form.results[resultIndex].inserted_value = calculatedResults[parameterCode];
    }
  });
  
  showCalculationEntry.value = false;
};

// NEW: Helper to get parameter by ID
const getParameterByCode = (parameterId) => {
  return form.results?.find(p => p.parameter_id?.value === parameterId);
};

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
        volume: 1
    });
}

const removeResult = (index) => {
    form.results.splice(index, 1);
}

const actionText = ref({
  analyze: 'Inserção de Resultados',
  verify: 'Verificação de Resultados',
  approve: 'Validação de Resultados',
});

// FIXED: Enhanced data loading
async function loadResultParameters() {
  try {
    const response = await axios.get('/results/getDefaultResultsData?action=' + props.action + '&sample_id=' + props.record?.sample_id?.value);
    console.log('Loaded results data:', response.data);

    form.results = response.data.map(result => ({
      ...result,
      // Ensure all calculation fields exist
      cfu1: result.cfu1 || null,
      cfu2: result.cfu2 || null,
      d1: result.d1 || null,
      d2: result.d2 || null,
      volume: result.volume || 1
    }));
  } catch (error) {
    console.error('Error loading results:', error);
    form.results = [];
  }
}

// FIXED: Your existing load functions remain the same
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

// NEW: Check if there are calculated parameters
const hasCalculatedParameters = computed(() => {
  return form.results?.some(p => p.requires_calculation && p.active);
});
</script>

<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Análises</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">A modificar a análise pertencente ao CL: {{ form.cl }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="cl_id" class="block text-sm font-medium leading-6 text-gray-900">Código</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.cl_id" v-model="form.cl_id" :load-options="loadLabCodes"/>
            </div>
            <p v-if="form.errors.cl_id" class="mt-2 text-xs text-red-600" id="cl_id-error">{{ form.errors.cl_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="department_id" class="block text-sm font-medium leading-6 text-gray-900">Departamento</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.department_id" v-model="form.department_id" :load-options="loadDepartments"/>
            </div>
            <p v-if="form.errors.department_id" class="mt-2 text-xs text-red-600" id="department_id-error">{{ form.errors.department_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="type_id" class="block text-sm font-medium leading-6 text-gray-900">Tipo de Análise</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.type_id" v-model="form.type_id" :load-options="loadAnalysisCategories"/>
            </div>
            <p v-if="form.errors.type_id" class="mt-2 text-xs text-red-600" id="type_id-error">{{ form.errors.type_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="sample_id" class="block text-sm font-medium leading-6 text-gray-900">Amostra</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.sample_id" v-model="form.sample_id" :load-options="loadSamples"/>
            </div>
            <p v-if="form.errors.sample_id" class="mt-2 text-xs text-red-600" id="sample_id-error">{{ form.errors.sample_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="profile_id" class="block text-sm font-medium leading-6 text-gray-900">Perfil</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.profile_id" v-model="form.profile_id" :load-options="loadProfiles"/>
            </div>
            <p v-if="form.errors.profile_id" class="mt-2 text-xs text-red-600" id="profile_id-error">{{ form.errors.profile_id }}</p>
          </div>

          <div class="sm:col-span-full inline-flex items-center justify-end">
            <button v-if="form.isDirty" @click="submit" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Modificar</button>
          </div>
        </div>

        <hr>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4" v-if="props.action === 'analyze' && hasCalculatedParameters">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-blue-900">Parâmetros Calculados</h3>
              <p class="text-sm text-blue-700 mt-1">
                {{ calculationParameters.length }} parâmetro(s) que requerem cálculo automático
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
          <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
            {{ actionText[props.action] }}
            <button @click="addResult" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <PlusCircleIcon class="h-5 w-5" />
            </button>
          </h2>
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
            
            <!-- NEW: Calculation indicator -->
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

            <!-- FIXED: Microbiology calculation section -->
            <div class="gap-x-4 py-3" v-if="props.record?.type_id?.value === 2 && !result.requires_calculation">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <div class="mt-2">
                    <dl class="space-y-6 divide-y divide-gray-900/10">
                      <Disclosure as="div" class="pt-6" v-slot="{ open }">
                        <dt>
                          <DisclosureButton class="flex w-full items-start justify-between text-left text-gray-900">
                            <span class="text-sm font-normal leading-7">Cálculo de Dispersão Microbiana</span>
                            <span class="ml-6 flex h-7 items-center text-blue-900 text-bold transform transition-all duration-200 hover:scale-150">
                              <PlusIcon v-if="!open" class="h-4 w-4" aria-hidden="true" />
                              <MinusIcon v-else class="h-4 w-4" aria-hidden="true" />
                            </span>
                          </DisclosureButton>
                        </dt>
                        <DisclosurePanel as="dd" class="mt-2">
                          <div class="mt-2">
                            <label :for="`${index}-d1`" class="block text-sm font-medium leading-6 text-gray-900">D1</label>
                            <div class="mt-2">
                              <input @keyup="result.inserted_value = calculateCFU(result)" v-model.number="result.d1" type="number" :id="`${index}-d1`" placeholder="D1" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-xs sm:leading-6" />
                            </div>
                          </div>

                          <div class="mt-2">
                            <label :for="`${index}-cfu1`" class="block text-sm font-medium leading-6 text-gray-900">UFC1</label>
                            <div class="mt-2">
                              <input @keyup="result.inserted_value = calculateCFU(result)" v-model.number="result.cfu1" type="number" :id="`${index}-cfu1`" placeholder="CFU1" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-xs sm:leading-6" />
                            </div>
                          </div>

                          <div class="mt-2">
                            <label :for="`${index}-d2`" class="block text-sm font-medium leading-6 text-gray-900">D2</label>
                            <div class="mt-2">
                              <input @keyup="result.inserted_value = calculateCFU(result)" v-model.number="result.d2" type="number" :id="`${index}-d2`" placeholder="D2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-xs sm:leading-6" />
                            </div>
                          </div>

                          <div class="mt-2">
                            <label :for="`${index}-cfu2`" class="block text-sm font-medium leading-6 text-gray-900">UFC2</label>
                            <div class="mt-2">
                              <input @keyup="result.inserted_value = calculateCFU(result)" v-model.number="result.cfu2" type="number" :id="`${index}-cfu2`" placeholder="CFU2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-xs sm:leading-6" />
                            </div>
                          </div>

                          <div class="mt-2">
                            <label :for="`${index}-volume`" class="block text-sm font-medium leading-6 text-gray-900">VOLUME</label>
                            <div class="mt-2">
                              <input @keyup="result.inserted_value = calculateCFU(result)" v-model.number="result.volume" type="number" :id="`${index}-volume`" placeholder="VOLUME" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-xs sm:leading-6" />
                            </div>
                          </div>
                        </DisclosurePanel>
                      </Disclosure>
                    </dl>
                  </div>
                </div>
              </dd>
            </div>

            <!-- FIXED: Result input fields with proper conditional rendering -->
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

            <!-- FIXED: Verification and approval fields -->
            <div class="gap-x-4 py-3" v-if="props.action === 'verify'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="verified_value" class="block text-sm font-medium leading-6 text-gray-900">
                    {{ props.record?.type_id?.value === 2 ? 'Resultado Ver Micro' : 'Resultado Ver' }}
                  </label>
                  <div class="mt-2">
                    <input v-model="result.verified_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': result.verified_value < result.min_ref_value || result.verified_value > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.verified_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.verified_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action === 'approve'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="approved_value" class="block text-sm font-medium leading-6 text-gray-900">
                    {{ props.record?.type_id?.value === 2 ? 'Resultado Apro Micro' : 'Resultado Apro' }}
                  </label>
                  <div class="mt-2">
                    <input v-model="result.approved_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': result.approved_value < result.min_ref_value || result.approved_value > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.approved_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.approved_value`] }}</p>
                </div>
              </dd>
            </div>

            <!-- FIXED: Uncertainty field for all actions -->
            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="uncertainty_value" class="block text-sm font-medium leading-6 text-gray-900">Incerteza</label>
                  <div class="mt-2">
                    <input v-model="result.uncertainty_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.uncertainty_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.uncertainty_value`] }}</p>
                </div>
              </dd>
            </div>

            <!-- Your existing unit, protocol, standard, nwp, reference value fields remain the same -->
            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="unit_id" class="block text-sm font-medium leading-6 text-gray-900">Unidade</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`results.${index}.unit_id`]" v-model="result.unit_id" :load-options="loadUnits"/>
                  </div>
                  <p v-if="form.errors[`results.${index}.unit_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.unit_id`] }}</p>
                </div>
              </dd>
            </div>

            <!-- ... rest of your existing fields (protocol, standard, nwp, reference values) ... -->

          </dl>
        </li>
      </ul>
      <p v-if="form.errors.results" class="mt-2 text-xs text-red-600">{{ form.errors.results }}</p>
    
    </div>
    
    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button v-if="!form.results?.some((item) => { return item['requested_counter_analysis'] === true}) && form.results?.length > 0"
              @click="submitResults" 
              type="button" 
              :disabled="form.processing" 
              class="inline-flex justify-center rounded-md px-3 py-2 text-sm font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
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

  <!-- NEW: Calculation Result Entry Modal -->
  <div v-if="showCalculationEntry" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
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
          :existing-results="{}"
          @calculated-results="handleCalculatedResults"
        />
      </div>
    </div>
  </div>

</template>