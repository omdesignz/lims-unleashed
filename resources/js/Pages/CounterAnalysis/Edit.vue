<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import combobox from '@/Components/combobox-enhanced.vue';
import ResultsDataService from '@/Services/ResultsDataService';
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";


defineOptions({
  layout: Layout
});

const props = defineProps({
    action: String,
    record: Object,
    parameters: [],
    report_studio: {
      type: Object,
      default: null,
    },
});

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

const counterAnalysisRequestForm = useForm({
  result_id: null,
});

onMounted(() => {
  loadResultParameters()
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

const executionControl = computed(() => {
  const summary = {
    calculated: 0,
    inputVariables: 0,
    manual: 0,
    blocked: [],
  };

  form.results.forEach((result) => {
    const readiness = ResultsDataService.getCalculationReadiness(result, form.results, props.action);

    if (readiness.isCalculated) {
      summary.calculated += 1;

      if (!readiness.ready) {
        summary.blocked.push({
          code: result.parameter_id?.code || result.parameter_label || 'N/D',
          missingInputs: readiness.missingInputs,
        });
      }

      return;
    }

    if (readiness.isInputVariable) {
      summary.inputVariables += 1;
      return;
    }

    summary.manual += 1;
  });

  return summary;
});

async function loadResultParameters() {
  const searchParams = new URLSearchParams({
    action: props.action ?? '',
    sample_id: props.record?.sample_id?.value ?? '',
  });

  try {
    const response = await fetch(`/results/getCounterAnalysisDefaultResultsData?${searchParams.toString()}`, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
      form.results = [];

      return;
    }

    form.results = await response.json();
  } catch {
    form.results = [];
  }
}

function loadParameters(query, setOptions) {
    return loadSelectOptions('/parameters/getParameter', query, setOptions, optionMappers.code);
}

function loadResultCategories(query, setOptions) {
    return loadSelectOptions('/resultcategories/getResultCategory', query, setOptions, optionMappers.name);
}

function loadDepartments(query, setOptions) {
    return loadSelectOptions('/departments/getDepartment', query, setOptions, optionMappers.name);
}

function loadProfiles(query, setOptions) {
    return loadSelectOptions('/profiles/getProfile', query, setOptions, optionMappers.name);
}

function loadSamples(query, setOptions) {
    return loadSelectOptions('/samples/getCode', query, setOptions, optionMappers.code);
}

function loadLabCodes(query, setOptions) {
    return loadSelectOptions('/labcodes/getCode', query, setOptions, optionMappers.code);
}

function loadAnalysisCategories(query, setOptions) {
    return loadSelectOptions('/analysiscategories/getAnalysisCategory', query, setOptions, optionMappers.name);
}

function loadUnits(query, setOptions) {
    return loadSelectOptions('/units/getUnit', query, setOptions, optionMappers.code);
}

function loadProtocols(query, setOptions) {
    return loadSelectOptions('/protocols/getProtocol', query, setOptions, optionMappers.code);
}

function loadNwps(query, setOptions) {
    return loadSelectOptions('/nwps/getNwp', query, setOptions, optionMappers.code);
}

function loadStandards(query, setOptions) {
    return loadSelectOptions('/standards/getStandard', query, setOptions, optionMappers.code);
}

let requestCounterAnalysis = (data) =>{
  if (counterAnalysisRequestForm.processing) {
    return;
  }

  counterAnalysisRequestForm.result_id = data;
  counterAnalysisRequestForm.post(route('counteranalysis.store'), {
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
  form.put(route('counteranalysis.update',{analysis: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        // form.reset()
      },
  });
}

}

let submitResults = () => {
  if (form.processing) {
    return;
  }

  form.post(route('results.storeCounterAnalysisResults'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
}

</script>

<template>
<div class="counter-analysis-editor space-y-6" :class="commercialDocumentThemeClasses">
  <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.16),_transparent_28%),linear-gradient(135deg,_#fffaf0,_#ffffff)] shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-700 dark:bg-[radial-gradient(circle_at_top_left,_rgba(20,184,166,0.18),_transparent_28%),linear-gradient(135deg,_#0f172a,_#111827)]">
    <div class="flex flex-col gap-5 px-6 py-6 md:flex-row md:items-end md:justify-between">
      <div class="max-w-3xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-primary-700 dark:text-primary-300">Fluxo controlado</p>
        <h3 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Contra-análises</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
          Ajuste o escopo da contra-análise, lance os resultados e mantenha a rastreabilidade desta repetição analítica.
        </p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-right shadow-sm dark:border-slate-600 dark:bg-slate-900/80">
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código em edição</p>
        <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ form.cl || 'Sem referência' }}</p>
      </div>
    </div>
  </section>

<form @submit.prevent>
    <div class="space-y-6">
      
        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="cl_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Código</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.cl_id" v-model="form.cl_id" :load-options="loadLabCodes"/>
            </div>
            <p v-if="form.errors.cl_id" class="mt-2 text-xs text-red-600" id="cl_id-error">{{ form.errors.cl_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="department_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Departamento</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.department_id" v-model="form.department_id" :load-options="loadDepartments"/>
            </div>
            <p v-if="form.errors.department_id" class="mt-2 text-xs text-red-600" id="department_id-error">{{ form.errors.department_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="type_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Tipo de Análise</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.type_id" v-model="form.type_id" :load-options="loadAnalysisCategories"/>
            </div>
            <p v-if="form.errors.type_id" class="mt-2 text-xs text-red-600" id="type_id-error">{{ form.errors.type_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="sample_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Amostra</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.sample_id" v-model="form.sample_id" :load-options="loadSamples"/>
            </div>
            <p v-if="form.errors.sample_id" class="mt-2 text-xs text-red-600" id="sample_id-error">{{ form.errors.sample_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="profile_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Perfil</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.profile_id" v-model="form.profile_id" :load-options="loadProfiles"/>
            </div>
            <p v-if="form.errors.profile_id" class="mt-2 text-xs text-red-600" id="profile_id-error">{{ form.errors.profile_id }}</p>
          </div>

          <div class="sm:col-span-full inline-flex items-center justify-end">
            <button v-if="form.isDirty" @click="submit" class="inline-flex justify-center rounded-2xl bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-700/20 transition hover:bg-primary-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400">Atualizar contra-análise</button>
          </div>
        </div>
        </section>

      <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="border-b border-gray-900/10 pb-6 dark:border-slate-700">
        <h2 class="flex items-center text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">
          {{ actionText[props.action] }}
          <button @click="addResult" class="ml-auto rounded-full bg-primary-50 p-2 text-primary-700 transition hover:scale-105 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
      </div>

      <div v-if="form.results.length" class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Parâmetros manuais</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ executionControl.manual }}</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
          <div class="text-xs font-medium uppercase tracking-wide text-emerald-700">Variáveis de entrada</div>
          <div class="mt-2 text-2xl font-semibold text-emerald-900">{{ executionControl.inputVariables }}</div>
        </div>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-500/30 dark:bg-primary-500/10">
          <div class="text-xs font-medium uppercase tracking-wide text-primary-700 dark:text-primary-300">Parâmetros calculados</div>
          <div class="mt-2 text-2xl font-semibold text-primary-900 dark:text-primary-100">{{ executionControl.calculated }}</div>
        </div>
        <div class="rounded-xl border p-4" :class="executionControl.blocked.length ? 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/30' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/30'">
          <div class="text-xs font-medium uppercase tracking-wide" :class="executionControl.blocked.length ? 'text-amber-700 dark:text-amber-200' : 'text-emerald-700 dark:text-emerald-200'">
            Cálculos bloqueados
          </div>
          <div class="mt-2 text-2xl font-semibold" :class="executionControl.blocked.length ? 'text-amber-900 dark:text-amber-100' : 'text-emerald-900 dark:text-emerald-100'">
            {{ executionControl.blocked.length }}
          </div>
        </div>
      </div>

      <div v-if="executionControl.blocked.length" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
        <p class="font-semibold">Existem parâmetros calculados sem dados de entrada suficientes.</p>
        <ul class="mt-3 space-y-2">
          <li v-for="blockedParameter in executionControl.blocked" :key="blockedParameter.code">
            <span class="font-medium">{{ blockedParameter.code }}</span>
            <span class="text-amber-800"> requer: {{ blockedParameter.missingInputs.join(', ') }}</span>
          </li>
        </ul>
      </div>



      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(result, index) in form.results" :key="result.value" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
            <div class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">

              <ClipboardDocumentCheckIcon class="h-5 w-5" />

              </div>
              {{ index + 1 }}º Resultado
                          
            <button @click="removeResult(index)" class="ml-auto rounded-full bg-white p-2 text-slate-500 shadow-sm transition hover:scale-105 hover:text-rose-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-rose-300">
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
                    <input v-model="result.inserted_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-xl border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-800 dark:text-gray-100 dark:ring-slate-600 dark:placeholder:text-slate-400 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.inserted_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.inserted_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'verify'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="verified_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado</label>
                  <div class="mt-2">
                    <input v-model="result.verified_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-xl border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-800 dark:text-gray-100 dark:ring-slate-600 dark:placeholder:text-slate-400 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.verified_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.verified_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="props.action== 'approve'">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="approved_value" class="block text-sm font-medium leading-6 text-gray-900">Resultado</label>
                  <div class="mt-2">
                    <input v-model="result.approved_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-xl border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-800 dark:text-gray-100 dark:ring-slate-600 dark:placeholder:text-slate-400 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.approved_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.approved_value`] }}</p>
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
                    <input v-model="result.min_ref_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-xl border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-800 dark:text-gray-100 dark:ring-slate-600 dark:placeholder:text-slate-400 sm:text-sm sm:leading-6" />
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
                    <input v-model="result.max_ref_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-xl border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-800 dark:text-gray-100 dark:ring-slate-600 dark:placeholder:text-slate-400 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`results.${index}.max_ref_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`results.${index}.max_ref_value`] }}</p>
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
    
      </section>
    </div>
    
    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button @click="submitResults" type="button" class="inline-flex justify-center rounded-2xl bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-700/20 transition hover:bg-primary-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400">{{ props.action == 'analyze' ? 'Inserir Resultados' : props.action == 'verify' ? 'Verificar Resultados' : 'Validar Resultados' }}</button>
    </div>
  </form>
</div>
</template>

<style scoped>
.counter-analysis-editor {
  --analysis-ink: #17231f;
  --analysis-muted: #647067;
  --analysis-border: #d9d1bf;
  --analysis-primary: #123f38;
}

.counter-analysis-editor :is(label, dt) {
  color: var(--analysis-ink);
  font-weight: 700;
}

.counter-analysis-editor :is(input:not([type="checkbox"]), textarea, select) {
  width: 100%;
  border: 1px solid var(--analysis-border);
  border-radius: 1rem;
  background: rgba(255, 250, 240, 0.94);
  color: var(--analysis-ink);
  box-shadow: 0 12px 32px -24px rgba(23, 35, 31, 0.35);
  transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.counter-analysis-editor :is(input:not([type="checkbox"]), textarea, select):focus {
  border-color: var(--analysis-primary);
  box-shadow: 0 0 0 4px rgba(18, 63, 56, 0.14);
  outline: none;
}

.counter-analysis-editor :is([class~="text-gray-900"], [class~="text-gray-800"], [class~="text-gray-700"]) {
  color: var(--analysis-ink) !important;
}

.counter-analysis-editor :is([class~="text-gray-600"], [class~="text-gray-500"], [class~="text-gray-400"]) {
  color: var(--analysis-muted) !important;
}

.counter-analysis-editor :is([class~="text-blue-900"], [class~="text-blue-800"]) {
  color: var(--analysis-primary) !important;
}

.counter-analysis-editor :is([class~="bg-blue-900"], [class~="bg-blue-800"]) {
  background-color: var(--analysis-primary) !important;
}

.counter-analysis-editor :is([class~="border-gray-100"], [class~="border-gray-200"], [class~="border-gray-300"], [class~="ring-gray-300"]) {
  border-color: var(--analysis-border) !important;
  --tw-ring-color: var(--analysis-border) !important;
}

:global(.dark) .counter-analysis-editor {
  --analysis-ink: #f8fafc;
  --analysis-muted: #cbd5e1;
  --analysis-border: rgba(148, 163, 184, 0.28);
  --analysis-primary: #7dd3c7;
}

:global(.dark) .counter-analysis-editor :is(input:not([type="checkbox"]), textarea, select) {
  background: rgba(15, 23, 42, 0.86);
  color: #f8fafc;
  border-color: rgba(148, 163, 184, 0.28);
  box-shadow: 0 18px 42px -28px rgba(0, 0, 0, 0.85);
}

:global(.dark) .counter-analysis-editor :is(input:not([type="checkbox"]), textarea, select)::placeholder {
  color: #94a3b8;
}

:global(.dark) .counter-analysis-editor :is([class~="bg-white"], [class~="bg-gray-50"]) {
  background-color: rgba(15, 23, 42, 0.88) !important;
}

:global(.dark) .counter-analysis-editor :is([class~="bg-blue-900"], [class~="bg-blue-800"]) {
  background-color: #0f766e !important;
}
</style>
