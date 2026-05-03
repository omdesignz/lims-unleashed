<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { router, useForm, usePage } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon, MinusIcon, PlusIcon } from "@heroicons/vue/24/outline";
defineOptions({
  layout: Layout
});

const props = defineProps({
    action: String,
    record: Object,
    parameters: []
});

const resuls = ref([]);

const form = useForm({
    action: props.action,
    id: props.record?.id,
    cl: props.record?.code,
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

async function loadResultParameters() {

  const response = await axios.get('/results/getDefaultResultsData?action=' + props.action + '&sample_id=' + props.record?.sample_id.value);
    console.log(response.data);

    form.results = response.data;
}

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            decimal_places: result.decimal_places,
            requires_calculation: result.requires_calculation,
            formula_expression: result.formula_expression,
            formula_id: result.formula_id,
            calculation_parameters: result.calculation_parameters,
            result_type: result.result_type,
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

        <div class="border-b border-gray-900/10 pb-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ actionText[props.action] }}
          <button @click="addResult" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <!-- <p class="mt-1 text-sm leading-6 text-gray-600">Parâmetros associados ao perfil: </p> -->

      </div>



      <ul role="list" class="grid grid-cols-10 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(result, index) in form.results" :key="result.value" class="rounded-xl border"
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
            
            <button v-if="props.action !== 'analyze' && !result.requested_counter_analysis" @click="requestCounterAnalysis(result.result_id)" type="button" class="nline-flex justify-center rounded-md bg-orange-900 px-3 py-1.8 text-xs font-semibold text-white shadow-sm hover:bg-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-800">Solicitar CA</button>
              
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

            <div class="gap-x-4 py-3" v-if="props.action== 'analyze'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="inserted_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado</label>
                  <div class="mt-2">
                    <input disabled v-model="result.inserted_value" type="number" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': Number(calculateMicrobialDispersion(result)) < result.min_ref_value || Number(calculateMicrobialDispersion(result)) > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.inserted_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.inserted_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'analyze'">
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

            <div class="gap-x-4 py-3" v-if="props.action== 'verify'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="inserted_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado Ver Micro</label>
                  <div class="mt-2">
                    <input v-model="result.inserted_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': Number(calculateMicrobialDispersion(result)) < result.min_ref_value || Number(calculateMicrobialDispersion(result)) > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.inserted_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.inserted_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'verify'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="verified_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado Ver</label>
                  <div class="mt-2">
                    <input v-model="result.verified_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': result.verified_value < result.min_ref_value || result.verified_value > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.verified_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.verified_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'verify'">
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

            <div class="gap-x-4 py-3" v-if="props.action== 'approve'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="inserted_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado Apro Micro</label>
                  <div class="mt-2">
                    <input v-model="result.inserted_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': Number(calculateMicrobialDispersion(result)) < result.min_ref_value || Number(calculateMicrobialDispersion(result)) > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.inserted_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.inserted_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'approve'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="approved_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado Apro</label>
                  <div class="mt-2">
                    <input v-model="result.approved_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': result.approved_value < result.min_ref_value || result.approved_value > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.approved_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.approved_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'approve'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="uncertainty_value" class="block text-sm font-medium leading-6 text-gray-900">Incerteza</label>
                  <div class="mt-2">
                    <input v-model="result.uncertainty_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="{ 'border-red-500 text-red-900 placeholder-red-500 focus:ring-red-500 focus:border-red-500': result.uncertainty_value < result.min_ref_value || result.uncertainty_value > result.max_ref_value }" />
                  </div>
                  <p v-if="form.errors[`results.${index}.uncertainty_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.uncertainty_value`] }}</p>
                </div>
              </dd>
            </div>

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

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="protocol_id" class="block text-sm font-medium leading-6 text-gray-900">Metodologia</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`results.${index}.protocol_id`]" v-model="result.protocol_id" :load-options="loadProtocols"/>
                  </div>
                  <p v-if="form.errors[`results.${index}.protocol_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.protocol_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="standard_id" class="block text-sm font-medium leading-6 text-gray-900">Normativa</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`results.${index}.standard_id`]" v-model="result.standard_id" :load-options="loadStandards"/>
                  </div>
                  <p v-if="form.errors[`results.${index}.standard_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.standard_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="nwp_id" class="block text-sm font-medium leading-6 text-gray-900">PNT</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`results.${index}.nwp_id`]" v-model="result.nwp_id" :load-options="loadNwps"/>
                  </div>
                  <p v-if="form.errors[`results.${index}.nwp_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.nwp_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="min_ref_value" class="block text-sm font-medium leading-6 text-gray-900">Valor Mínimo de Referência</label>
                  <div class="mt-2">
                    <input v-model="result.min_ref_value" type="number" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.min_ref_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.min_ref_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="max_ref_value" class="block text-sm font-medium leading-6 text-gray-900">Valor Máximo de Referência</label>
                  <div class="mt-2">
                    <input v-model="result.max_ref_value" type="number" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.max_ref_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.max_ref_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="ref_val_origin" class="block text-sm font-medium leading-6 text-gray-900">Origem do Valor de Referência</label>
                  <div class="mt-2">
                    <input v-model="result.ref_val_origin" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.ref_val_origin`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.ref_val_origin`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="type_id" class="block text-sm font-medium leading-6 text-gray-900">Tipo de Resultado</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`result.${index}.type_id`]" v-model="result.type_id" :load-options="loadResultCategories"/>
                  </div>
                  <p v-if="form.errors[`result.${index}.type_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`result.${index}.type_id`] }}</p>
                </div>
              </dd>
            </div>

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

</template>