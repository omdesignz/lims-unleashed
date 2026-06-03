<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import FormulaDisplay from "@/Components/formula-display.vue";


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object
});

const form = useForm({
    id: props.record?.id,
    name: props.record?.name,
    code: props.record?.code,
    description: props.record?.description,
    price: props.record?.price,
    category_id: props.record?.category_id,
    parameters: props.record?.parameters
});

const totalProfilePrice = computed(() => {
  return form.parameters.reduce((acc, item) => {
    return parseFloat(parseFloat(acc) + parseFloat(item.price)).toFixed(2);
  }, 0);
});

const duplicateParameterLabels = computed(() => {
  const counts = new Map();

  form.parameters.forEach((parameter) => {
    const parameterId = parameter.parameter_id?.value;

    if (!parameterId) {
      return;
    }

    counts.set(parameterId, (counts.get(parameterId) ?? 0) + 1);
  });

  return form.parameters
    .filter((parameter) => {
      const parameterId = parameter.parameter_id?.value;

      return parameterId && counts.get(parameterId) > 1;
    })
    .map((parameter) => parameter.parameter_id?.label)
    .filter((label, index, labels) => label && labels.indexOf(label) === index);
});

const referenceRangeIssues = computed(() => {
  return form.parameters
    .map((parameter, index) => ({ parameter, index }))
    .filter(({ parameter }) => {
      if (parameter.min_ref_value === null || parameter.min_ref_value === "" || parameter.max_ref_value === null || parameter.max_ref_value === "") {
        return false;
      }

      return Number(parameter.max_ref_value) < Number(parameter.min_ref_value);
    })
    .map(({ parameter, index }) => parameter.parameter_id?.label || `Parâmetro ${index + 1}`);
});

const profileScopeIssues = computed(() => {
  const issues = [];

  if (form.category_id && !form.category_id.department_id) {
    issues.push("A categoria analítica selecionada não está ligada a um departamento.");
  }

  if (duplicateParameterLabels.value.length) {
    issues.push(`Parâmetros repetidos: ${duplicateParameterLabels.value.join(", ")}.`);
  }

  if (referenceRangeIssues.value.length) {
    issues.push(`Faixas de referência inválidas: ${referenceRangeIssues.value.join(", ")}.`);
  }

  return issues;
});

const showDeleteConfirmation = ref(false);

onMounted(() => {

});

const addParameter = () => {
    form.parameters.push({
        parameter_id: '',
        unit_label: '',
        unit_id: '',
        protocol_label: '',
        protocol_id: '',
        standard_label: '',
        standard_id: '',
        nwp_label: '',
        nwp_id: '',
        count: true,
        min_ref_value: null,
        max_ref_value: null,
        ref_val_origin: null,
        dilutions: null,
        extra_data: {
          dilutions: [],
        },
        category_id: '',
        category_label: '',
        formula_id: '',
        optimal_analysis_time: null,
        price: 0,
    });
}

const removeParameter = (index) => {
    form.parameters.splice(index, 1);
}

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('profiles.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)


function loadCategories(query, setOptions) {
    fetch('/analysiscategories/getAnalysisCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            name: result.name,
            department_id: result.department_id,
            department_name: result.department_name,
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

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            code: result.code,
            active: !!result.active,
            optimal_analysis_time: result.optimal_analysis_time,
            price: result.price,
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

function loadFormulas(query, setOptions) {
  fetch("/formulas/getFormula?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
            expression: result.expression,
          };
        }),
      );
    });
}


let submit = () => {

if(!form.id) {
  form.post(route('profiles.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('profiles.update',{profile: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        // form.reset()
      },
  });
}

}

</script>

<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.profiles.page_update_description') }} {{ form.name }}</p>
</div>

<form :class="commercialDocumentThemeClasses" @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">
          

          <!-- <div class="col-span-full">
            <label for="street-address" class="block text-sm font-medium leading-6 text-gray-900">Nome</label>
            <div class="mt-2">
              <input type="text" name="street-address" id="street-address" autocomplete="street-address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
          </div> -->

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.name') }}</label>
            <div class="mt-2">
              <input v-model="form.name" type="text" name="name" id="name" autocomplete="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.name" class="mt-2 text-xs text-red-600" id="name-error">{{ form.errors.name }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="code" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.code') }}</label>
            <div class="mt-2">
              <input v-model="form.code" type="text" name="code" id="code" autocomplete="code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.code ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.code" class="mt-2 text-xs text-red-600" id="code-error">{{ form.errors.code }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.description') }}</label>
            <div class="mt-2">
              <input v-model="form.description" type="text" name="description" id="description" autocomplete="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.description" class="mt-2 text-xs text-red-600" id="description-error">{{ form.errors.description }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="price" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.price') }}</label>
            <div class="mt-2">
              <input disabled :value="totalProfilePrice" v-model="form.price" type="number" name="price" id="price" autocomplete="price" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.price ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.price" class="mt-2 text-xs text-red-600" id="price-error">{{ form.errors.price }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="category_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.category_id_1') }}</label>
            <div class="mt-2">
                <combobox v-model="form.category_id" :load-options="loadCategories"/>
            </div>
            <p v-if="form.errors.category_id" class="mt-2 text-xs text-red-600" id="category_id-error">{{ form.errors.category_id }}</p>
          </div>

        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 sm:col-span-10" v-if="form.category_id?.department_name || profileScopeIssues.length">
          <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-200">
              Departamento: {{ form.category_id?.department_name || 'não definido' }}
            </span>
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-200">
              {{ duplicateParameterLabels.length ? 'Parâmetros repetidos' : 'Sem duplicados' }}
            </span>
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-200">
              {{ referenceRangeIssues.length ? 'Rever faixas de referência' : 'Faixas coerentes' }}
            </span>
          </div>
          <ul v-if="profileScopeIssues.length" class="mt-3 space-y-1 text-sm text-amber-900">
            <li v-for="issue in profileScopeIssues" :key="issue">{{ issue }}</li>
          </ul>
        </div>

      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.parameters.length }} {{ $t('gestlab.general.labels.profiles.items') }}
          <button @click="addParameter" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.profiles.items_tagline') }} {{ form.name }}</p>

      </div>

      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(parameter, index) in form.parameters" :key="parameter.value" class="rounded-xl border border-gray-200"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-blue-900 text-white p-3">
            <div class="text-sm font-medium leading-6 text-white">

              <ClipboardDocumentCheckIcon class="h-5 w-5" />

              </div>
              {{ index + 1 }}º {{ $t('gestlab.general.labels.profiles.parameter') }}

            <button @click="removeParameter(index)" class="transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="parameter_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.parameter_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.parameter_id`]" v-model="parameter.parameter_id" :load-options="loadParameters" @update:model-value="(e) => {
                        parameter.optimal_analysis_time = e.optimal_analysis_time;
                        parameter.price = e.price;
                      }"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.parameter_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.parameter_id`] }}</p>
                  <div class="mt-2 flex flex-wrap gap-2 text-xs" v-if="parameter.parameter_id?.code || parameter.parameter_id?.active !== undefined">
                    <span v-if="parameter.parameter_id?.code" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-700">
                      {{ parameter.parameter_id.code }}
                    </span>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 font-medium',
                      parameter.parameter_id?.active === false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
                    ]">
                      {{ parameter.parameter_id?.active === false ? 'Inactive' : 'Active' }}
                    </span>
                  </div>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="unit_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.unit_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.unit_id`]" v-model="parameter.unit_id" :load-options="loadUnits"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.unit_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.unit_id`] }}</p>
                </div>
              </dd>
            </div>

            <!-- Optimal Analysis Time -->
             <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="optimal_analysis_time" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.optimal_analysis_time') }}</label>
                  <div class="mt-2">
                    <input disabled v-model="parameter.optimal_analysis_time" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`parameters.${index}.optimal_analysis_time`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.optimal_analysis_time`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="protocol_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.protocol_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.protocol_id`]" v-model="parameter.protocol_id" :load-options="loadProtocols"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.protocol_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.protocol_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="standard_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.standard_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.standard_id`]" v-model="parameter.standard_id" :load-options="loadStandards"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.standard_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.standard_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="nwp_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.nwp_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.nwp_id`]" v-model="parameter.nwp_id" :load-options="loadNwps"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.nwp_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.nwp_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="min_ref_value" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.min_ref_value') }}</label>
                  <div class="mt-2">
                    <input v-model="parameter.min_ref_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`parameters.${index}.min_ref_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.min_ref_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="max_ref_value" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.max_ref_value') }}</label>
                  <div class="mt-2">
                    <input v-model="parameter.max_ref_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`parameters.${index}.max_ref_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.max_ref_value`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="ref_val_origin" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.ref_val_origin') }}</label>
                  <div class="mt-2">
                    <input v-model="parameter.ref_val_origin" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`parameters.${index}.ref_val_origin`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.ref_val_origin`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="form?.category_id?.value === 2">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <div class="flex items-center">
                    {{ $t("gestlab.general.labels.profiles.add_dilutions") }}
                    <button @click="() => {
                      if ( ! Array.isArray(parameter?.extra_data?.dilutions) ) {
                        parameter.extra_data.dilutions = [];
                      }

                      parameter.extra_data.dilutions.push({
                        quantity: null,
                        ratio: null,
                      });
                    }"
                      class="hover:text-blue-800 transform transition-all duration-200 hover:scale-150 ml-auto"
                    >
                      <PlusCircleIcon class="h-5 w-5" />
                    </button>
                  </div>

                  <div
                    class="mt-2 pb-4"
                    v-for="(dilution, dilutionIndex) in parameter?.extra_data?.dilutions"
                    :key="dilutionIndex"
                  >
                    <input
                      v-model="dilution.quantity"
                      type="text"
                      placeholder="Quantidade"
                      :name="`item-${dilutionIndex}-quantity`"
                      :id="`item-${dilutionIndex}-quantity`"
                      class="block w-full mb-2 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    />
                    <input
                      v-model="dilution.ratio"
                      type="text"
                      placeholder="Proporção"
                      :name="`item-${dilutionIndex}-ratio`"
                      :id="`item-${dilutionIndex}-ratio`"
                      class="block w-full mb-2 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    />

                    <p
                      class="mt-2 text-xs flex justify-end"
                      v-if="parameter?.extra_data.dilutions.length"
                    >
                      <button
                        class="flex items-center justify-center py-2 text-sm font-semibold text-red-600 transform transition-all duration-200 hover:scale-150"
                        @click="
                          () => {
                            parameter?.extra_data?.dilutions.splice(
                              dilutionIndex,
                              1,
                            );
                          }
                        "
                      >
                        <TrashIcon class="h-5 w-5" />
                        <!-- {{ $t("gestlab.general.buttons.delete") }} -->
                      </button>
                    </p>
                  </div>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="form?.category_id?.value === 2">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="dilutions" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.dilutions') }}</label>
                  <div class="mt-2">
                    <input v-model="parameter.dilutions" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`parameters.${index}.dilutions`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.dilutions`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3" v-if="form?.category_id?.value === 1">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="dilutions" class="block text-sm font-medium leading-6 text-gray-900">Analitos</label>
                  <div class="mt-2">
                    <input v-model="parameter.dilutions" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`parameters.${index}.dilutions`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.dilutions`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="category_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.category_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.category_id`]" v-model="parameter.category_id" :load-options="loadResultCategories"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.category_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.category_id`] }}</p>
                </div>
              </dd>
            </div>

            <!-- <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="formula_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.formula_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`parameters.${index}.formula_id`]" v-model="parameter.formula_id" :load-options="loadFormulas"/>
                  </div>
                  <p v-if="form.errors[`parameters.${index}.formula_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`parameters.${index}.formula_id`] }}</p>

                  <p class="mt-2 text-blue-900">
                    <FormulaDisplay :formula="parameter?.formula_id?.expression" />
                  </p>

                </div>
              </dd>
            </div> -->

          </dl>
        </li>
      </ul>
      <p v-if="form.errors.parameters" class="mt-2 text-xs text-red-600">{{ form.errors.parameters }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <button v-if="form.isDirty" @click="showDeleteConfirmation = true" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Modificar</button>
    </div>
  </form>

  <confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="submit" v-if="showDeleteConfirmation" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.name') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.name }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.code') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.code }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.description') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.description }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.price') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.price }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.category_id_1') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.category_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">
              
              <div class="w-full pt-2">
                <div class="mx-auto w-full rounded-2xl bg-white">
                  <Disclosure v-slot="{ open }" v-for="(parameter, index) in form.parameters" :key="parameter.value" v-if="form.parameters.length">
                    <DisclosureButton
                      class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                    >
                      <span>{{ parameter.parameter_id?.label }}</span>
                      <ChevronUpIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-5 w-5 text-white"
                      />
                    </DisclosureButton>
                    <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                      <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.category_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.category_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.parameter_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.parameter_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.unit_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.unit_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.protocol_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.protocol_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.standard_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.standard_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.nwp_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.nwp_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.min_ref_value') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.min_ref_value }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.max_ref_value') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.max_ref_value }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.profiles.ref_val_origin') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.ref_val_origin }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ parameter.category_id == 2 ? $t('gestlab.general.labels.profiles.dilutions') : 'Analitos' }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parameter.dilutions }}</dd>
                          </div>
                          
                        </dl>
                      </div>
                    </DisclosurePanel>
                  </Disclosure>
                </div>
              </div>
            </div>
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>
