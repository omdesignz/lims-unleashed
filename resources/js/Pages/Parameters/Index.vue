<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import combobox from '@/Components/combobox.vue';
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, watch } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    },
    formulas: Array, // Add formulas prop for dropdown
});

defineOptions({
  layout: Layout
});

let form = useForm({ 
    name: '',
    code: '',
    price: 0,
    description: '',
    exemption_id: '',
    tax_id: '',
    tax_percentage: '',
    exemption: '',
    charge_tax: true,
    withhold_tax: false,
    active: true,
    optimal_analysis_time: null,
    result_is_qualitative: false,
    
    // NEW: Calculation fields
    requires_calculation: false,
    formula_id: null,
    formula_expression: '',
    calculation_parameters: [],
    decimal_places: 4,
    result_type: 'quantitative',
    
    id: null,
});

const actionId = ref(null);

// NEW: Track formula selection
const selectedFormula = ref(null);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.description');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})

const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})

const openslideover = ref(false);

const resetParameterForm = () => {
    form.reset();
    form.clearErrors();
    selectedFormula.value = null;
    form.price = 0;
    form.tax_percentage = '';
    form.charge_tax = true;
    form.withhold_tax = false;
    form.active = true;
    form.optimal_analysis_time = null;
    form.result_is_qualitative = false;
    form.requires_calculation = false;
    form.formula_id = null;
    form.formula_expression = '';
    form.calculation_parameters = [];
    form.decimal_places = 4;
    form.result_type = 'quantitative';
};

const openCreateSlideover = () => {
    resetParameterForm();
    openslideover.value = true;
};

let actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text'
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete'
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore'
  },
];

const close = () => {
    openslideover.value = false;
    resetParameterForm();
}


const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const declaredCalculationParameters = computed(() => {
    return (Array.isArray(form.calculation_parameters) ? form.calculation_parameters : [])
        .map((parameter) => String(parameter).trim())
        .filter(Boolean);
});

const expressionVariables = computed(() => {
    if (!form.formula_expression) {
        return [];
    }

    return extractVariablesFromExpression(form.formula_expression);
});

const selectedFormulaVariables = computed(() => {
    return Array.isArray(selectedFormula.value?.variables)
        ? selectedFormula.value.variables
            .map((variable) => variable?.name)
            .filter(Boolean)
        : [];
});

const parameterGovernanceIssues = computed(() => {
    const issues = [];

    if (form.result_is_qualitative && form.requires_calculation) {
        issues.push('Parâmetros qualitativos não devem depender de cálculo automático.');
    }

    if (!form.requires_calculation) {
        return issues;
    }

    if (!selectedFormula.value && !form.formula_expression?.trim()) {
        issues.push('Selecione uma fórmula ativa ou defina uma expressão personalizada.');
    }

    if (declaredCalculationParameters.value.length === 0) {
        issues.push('Defina os parâmetros de entrada necessários para o cálculo.');
    }

    const authoritativeVariables = expressionVariables.value.length > 0
        ? expressionVariables.value
        : selectedFormulaVariables.value;

    if (authoritativeVariables.length > 0) {
        const missingVariables = authoritativeVariables.filter((variable) => !declaredCalculationParameters.value.includes(variable));
        const extraVariables = declaredCalculationParameters.value.filter((variable) => !authoritativeVariables.includes(variable));

        if (missingVariables.length > 0) {
            issues.push(`Faltam parâmetros declarados para a expressão: ${missingVariables.join(', ')}.`);
        }

        if (extraVariables.length > 0) {
            issues.push(`Existem parâmetros declarados que não aparecem na expressão: ${extraVariables.join(', ')}.`);
        }
    }

    return issues;
});

// FIXED: Enhanced data loading with proper calculation_parameters handling
const openSlideoverWithData = (data) => {
    // console.log('openSlideoverWithData', JSON.stringify(data));
    openslideover.value = true;
    form.id = data.id;
    form.name = data.name;
    form.code = data.code;
    form.price = data.price;
    form.tax_percentage = data.tax_percentage;
    form.description = data.description;
    form.charge_tax = data.charge_tax;
    form.withhold_tax = data.withhold_tax;
    form.active = data.active;
    form.optimal_analysis_time = data.optimal_analysis_time;
    form.result_is_qualitative = data.result_is_qualitative;
    form.exemption_id = { 
      value: data.exemption_id?.id,
      label: data.exemption
    };  
    form.tax_id = {
      value: data.tax_id?.id,
      label: data.tax_id?.name,
      percent: data.tax_percentage,
    };
    
    // Enhanced calculation data loading
    form.requires_calculation = data.requires_calculation || false;
    form.formula_id = data.formula_id;
    form.formula_expression = data.formula_expression || '';
    form.decimal_places = data.decimal_places || 4;
    form.result_type = data.result_type || 'quantitative';
    
    // FIXED: Properly handle calculation_parameters
    if (data.calculation_parameters) {
        if (Array.isArray(data.calculation_parameters)) {
            form.calculation_parameters = data.calculation_parameters;
        } else if (typeof data.calculation_parameters === 'string') {
            try {
                form.calculation_parameters = JSON.parse(data.calculation_parameters);
            } catch {
                form.calculation_parameters = [];
            }
        } else {
            form.calculation_parameters = [];
        }
    } else {
        form.calculation_parameters = [];
    }
    
    // Set selected formula for display
    if (data.formula_id && props.formulas) {
        selectedFormula.value = props.formulas.find(f => f.id === data.formula_id);
        
        // FIXED: If calculation_parameters is empty but we have a formula, populate it correctly
        if ((!form.calculation_parameters || form.calculation_parameters.length === 0) && 
            selectedFormula.value?.variables && 
            Array.isArray(selectedFormula.value.variables)) {
            
            form.calculation_parameters = selectedFormula.value.variables
                .map(variable => variable.name)
                .filter(name => name);
        }
    }
    
}

let submit = () => {
    // Prepare calculation data
    const submitData = {
        ...form.data(),
        calculation_parameters: Array.isArray(form.calculation_parameters) 
            ? form.calculation_parameters 
            : JSON.stringify(form.calculation_parameters),
    };

    if(!form.id) {
      form.post(route('parameters.store'), {
          data: submitData,
          preserveScroll: true,
          preserveState: false,
          onError: () => {
            showDeleteConfirmationSlideover.value = false
            openslideover.value = true
          },
          onSuccess: () => {
            openslideover.value = false;
            resetParameterForm()
          },
      });
    } else {
      form.put(route('parameters.update',{parameter: form.id}), {
          data: submitData,
          preserveScroll: true,
          preserveState: false,
          onError: () => {
            showDeleteConfirmationSlideover.value = false
            openslideover.value = true
          },
          onSuccess: () => {
            openslideover.value = false; 
            resetParameterForm()
          },
      });
    }
}

// NEW: Formula selection handler
// NEW: Auto-populate calculation_parameters when formula is selected
const onFormulaSelect = (formula) => {
    selectedFormula.value = formula;
    form.formula_id = formula?.id || null;
    form.formula_expression = formula?.formula_expression || '';
    
    // Auto-set decimal places from formula
    if (formula?.decimal_places) {
        form.decimal_places = formula.decimal_places;
    }
    
    // FIXED: Correctly extract variable names from variables array
    if (formula?.variables && Array.isArray(formula.variables)) {
        // Extract just the 'name' property from each variable object
        form.calculation_parameters = formula.variables
            .map(variable => variable.name)
            .filter(name => name); // Remove any empty names
    } else {
        form.calculation_parameters = [];
    }
    
}

// FIXED: Also handle custom formula expression changes
watch(() => form.formula_expression, (newExpression) => {
    if (newExpression && !selectedFormula.value) {
        // Extract variables from custom formula
        const variables = extractVariablesFromExpression(newExpression);
        form.calculation_parameters = variables;
    }
});

// NEW: Watch calculation toggle
watch(() => form.requires_calculation, (newVal) => {
    if (!newVal) {
        // Clear calculation fields when disabled
        form.formula_id = null;
        form.formula_expression = '';
        form.calculation_parameters = [];
        selectedFormula.value = null;
    }
});

// NEW: Helper function to extract variables from expression
const extractVariablesFromExpression = (expression) => {
    if (!expression) return [];
    
    // Extract variable names from {variable} format
    const variablePattern = /\{([^}]+)\}/g;
    const matches = expression.match(variablePattern) || [];
    
    // Remove the {} and return unique variable names
    const variables = matches.map(match => match.slice(1, -1));
    return [...new Set(variables)];
};

// NEW: Load formulas for combobox
function loadFormulas(query, setOptions) {
    if (!query) {
        setOptions(props.formulas?.map(formula => ({
            value: formula.id,
            label: formula.name,
            ...formula
        })) || []);
        return;
    }
    
    const filtered = props.formulas?.filter(formula => 
        formula.name.toLowerCase().includes(query.toLowerCase()) ||
        formula.code?.toLowerCase().includes(query.toLowerCase())
    );
    
    setOptions(filtered?.map(formula => ({
        value: formula.id,
        label: formula.name,
        ...formula
    })) || []);
}

// REMOVED: The testFormula and validateFormula functions - they don't belong here
// These functions are for the Formula component, not the Parameter component

// Your existing functions remain the same...
const confirmAction = () => {
    executeAction(actionId.value);
}

const executeAction = (actionId) => {
    const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

    if(!recordIds.length) return;

    switch (actionId) {
        case 'delete':
            router.get(route('parameters.destroy'), {
                recordIds: recordIds
            }, {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteConfirmation.value = false;
                    actionId = null;
                }
            });
            showDeleteConfirmation.value = false;
        break;  

        case 'restore':
            router.get(route('parameters.restore'), {
                recordIds: recordIds
            }, {
                preserveState:false,
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteConfirmation.value = false;
                    actionId = null;
                }
            });
            showDeleteConfirmation.value = false;
    }
}

function loadExemptions(query, setOptions) {
    fetch('/taxexemptions/getExemption?q=' + query)
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

function loadTaxTypes(query, setOptions) {
    fetch('/taxtypes/getTaxType?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            percent: result.percent,
            };
        })
        );
    });
}
</script>

<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.parameters.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="openCreateSlideover" @slideover-on="openSlideoverWithData"/> <br>

<slide-over v-if="openslideover" :class="commercialDocumentThemeClasses" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">
              <!-- Existing fields (Name, Code, Price, etc.) -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.name') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.name" type="text" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
              </div>

              <!-- Code -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="code" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.code') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.code" type="text" name="code" id="code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.code ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.code" class="mt-2 text-sm text-red-600" id="code-error">{{ form.errors.code }}</p>
                </div>
              </div>

              <!-- Price -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="price" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.price') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.price" type="number" name="price" id="price" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.price ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.price" class="mt-2 text-sm text-red-600" id="price-error">{{ form.errors.price }}</p>
                </div>
              </div>
              
              <!-- Active -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="active" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.active') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.active" type="checkbox" name="active" id="active" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.active ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.active" class="mt-2 text-sm text-red-600" id="active-error">{{ form.errors.active }}</p>
                </div>
              </div>

              <!-- Charge Tax -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="charge_tax" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.charge_tax') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.charge_tax" type="checkbox" name="charge_tax" id="charge_tax" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.charge_tax ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.charge_tax" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.charge_tax }}</p>
                </div>
              </div>

              <!-- Withhold Tax -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="withhold_tax" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.withhold_tax') }}</label>
                </div>
                <div class="mt-2 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                  <div class="mt-2 flex items-center gap-x-4 sm:mt-0 sm:flex-auto">
                    <input v-model="form.withhold_tax" type="checkbox" name="withhold_tax" id="withhold_tax" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.withhold_tax ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                    <p v-if="form.errors.withhold_tax" class="mt-2 text-sm text-red-600" id="withhold_tax-error">{{ form.errors.withhold_tax }}</p>
                  </div>
                </div>
              </div>


              <!-- Exemption -->
              <div v-if="!form.charge_tax" class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="exemption_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.exemption_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.exemption_id" v-model="form.exemption_id" :load-options="loadExemptions"/>
                  <p v-if="form.errors.exemption_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.exemption_id }}</p>
                </div>
              </div>

              <!-- Tax Category -->
              <div v-else class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="tax_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.tax_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.tax_id" v-model="form.tax_id" :load-options="loadTaxTypes"/>
                  <p v-if="form.errors.tax_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.tax_id }}</p>
                </div>
              </div>

              <!-- Description -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="description" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.description') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <textarea v-model="form.description" name="description" id="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.description" class="mt-2 text-sm text-red-600" id="description-error">{{ form.errors.description }}</p>
                </div>
              </div>

              <!-- Optimal Analysis Time -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="optimal_analysis_time" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.optimal_analysis_time') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.optimal_analysis_time" type="text" name="optimal_analysis_time" id="optimal_analysis_time" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.optimal_analysis_time ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.optimal_analysis_time" class="mt-2 text-sm text-red-600" id="optimal_analysis_time-error">{{ form.errors.optimal_analysis_time }}</p>
                </div>
              </div>

              <!-- NEW: Calculation Section -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5 border-t border-gray-200">
                <div>
                  <label for="requires_calculation" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">Calculado</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.requires_calculation" type="checkbox" name="requires_calculation" id="requires_calculation" class="h-5 w-5 rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
                  <p v-if="form.errors.requires_calculation" class="mt-2 text-sm text-red-600">{{ form.errors.requires_calculation }}</p>
                </div>
              </div>

              <!-- Formula Selection (only show if requires calculation) -->
              <div v-if="form.requires_calculation" class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="formula_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">Fórmula</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.formula_id" v-model="selectedFormula" :load-options="loadFormulas" @update:modelValue="onFormulaSelect"/>
                  <p v-if="form.errors.formula_id" class="mt-2 text-sm text-red-600">{{ form.errors.formula_id }}</p>
                  
                  <!-- Formula preview -->
                  <div v-if="selectedFormula" class="mt-2 p-2 bg-gray-50 rounded text-sm">
                    <div class="font-medium">{{ selectedFormula.name }}</div>
                    <div class="text-gray-600">{{ selectedFormula.formula_expression }}</div>
                    <!-- <div v-if="selectedFormula.variables" class="text-xs text-gray-500 mt-1">
                      Variáveis: {{ Object.keys(selectedFormula.variables).join(', ') }}
                    </div> -->
                  </div>
                </div>
              </div>

              <!-- Custom Formula (alternative to template) -->
              <div v-if="form.requires_calculation" class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="formula_expression" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">Fórmula Personalizada</label>
                  <p class="text-xs text-gray-500 mt-1">Use {variavel} para parâmetros</p>
                </div>
                <div class="sm:col-span-2">
                  <textarea v-model="form.formula_expression" name="formula_expression" id="formula_expression" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" placeholder="Ex: ({temp} * 1.8) + 32"></textarea>
                  <p v-if="form.errors.formula_expression" class="mt-2 text-sm text-red-600">{{ form.errors.formula_expression }}</p>
                  
                  <!-- REMOVED: Test/Validate buttons - they don't belong here -->
                </div>
              </div>

              <!-- Decimal Places -->
              <div v-if="form.requires_calculation" class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="decimal_places" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">Casas Decimais</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.decimal_places" type="number" min="0" max="8" name="decimal_places" id="decimal_places" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  <p v-if="form.errors.decimal_places" class="mt-2 text-sm text-red-600">{{ form.errors.decimal_places }}</p>
                </div>
              </div>

                <!-- In your slide-over template, add a display for calculation_parameters -->
                  <div v-if="form.requires_calculation" 
                    class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                    <div>
                    <label class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">
                        Parâmetros de Entrada
                    </label>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ form.calculation_parameters.length }} parâmetro(s) necessário(s)
                    </p>
                    </div>
                    <div class="sm:col-span-2">
                    <div class="bg-gray-50 p-3 rounded border" 
                        :class="form.calculation_parameters.length === 0 ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200'">
                        
                        <div v-if="form.calculation_parameters.length > 0" class="flex flex-wrap gap-2">
                        <span v-for="param in form.calculation_parameters" :key="param"
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ param }}
                        </span>

                        <p class="text-xs text-gray-500 mt-2">
                        Estes parâmetros devem ser recolhidos antes do cálculo
                        </p>
                        </div>
                        
                        <div v-else class="text-center py-2">
                        <p class="text-sm text-yellow-700">
                            ⚠️ Nenhum parâmetro de entrada definido
                        </p>
                        <p class="text-xs text-yellow-600 mt-1">
                            Selecione uma fórmula ou defina uma expressão personalizada
                        </p>
                        </div>
                        
                        
                </div>
                    </div>
                </div>

              <div v-if="form.requires_calculation || form.result_is_qualitative"
                class="space-y-2 px-4 sm:px-6 sm:py-5">
                <div class="rounded-lg border p-4" :class="parameterGovernanceIssues.length ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50'">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="parameterGovernanceIssues.length ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'">
                      {{ parameterGovernanceIssues.length ? 'Review required' : 'Calculation scope controlled' }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                      Result type: {{ form.result_type }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                      Inputs: {{ declaredCalculationParameters.length }}
                    </span>
                  </div>

                  <ul v-if="parameterGovernanceIssues.length" class="mt-3 space-y-1 text-sm text-amber-900">
                    <li v-for="issue in parameterGovernanceIssues" :key="issue">{{ issue }}</li>
                  </ul>
                  <p v-else class="mt-3 text-sm text-green-900">
                    A definição do parâmetro está coerente com o cálculo e com o tipo de resultado.
                  </p>
                </div>
              </div>


              <!-- Your existing fields continue... -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="result_is_qualitative" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.parameters.result_is_qualitative') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.result_is_qualitative" type="checkbox" name="result_is_qualitative" id="result_is_qualitative" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  <p v-if="form.errors.result_is_qualitative" class="mt-2 text-sm text-red-600">{{ form.errors.result_is_qualitative }}</p>
                </div>
              </div>
        </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="openslideover = false; resetParameterForm()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <button v-if="form.isDirty" @click="showDeleteConfirmationSlideover = true" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<!-- Your existing confirmation dialogs remain the same -->
<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />

<confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmationSlideover=false" @close="showDeleteConfirmationSlideover=false" @confirmed="submit" v-if="showDeleteConfirmationSlideover" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <!-- Your existing summary content, enhanced with calculation fields -->
    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div class="mt-6 border-t border-gray-100">
        <dl class="divide-y divide-gray-100">
          <!-- Your existing summary fields... -->
          
          <!-- NEW: Calculation summary -->
          <div v-if="form.requires_calculation" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0 bg-blue-50">
            <dt class="text-sm font-medium leading-6 text-gray-900">Calculado</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">Sim</dd>
          </div>
          <div v-if="form.requires_calculation && selectedFormula" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0 bg-blue-50">
            <dt class="text-sm font-medium leading-6 text-gray-900">Fórmula</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ selectedFormula.name }}</dd>
          </div>
          <div v-if="form.requires_calculation && form.formula_expression" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0 bg-blue-50">
            <dt class="text-sm font-medium leading-6 text-gray-900">Expressão</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 font-mono text-sm">{{ form.formula_expression }}</dd>
          </div>
          <div v-if="form.requires_calculation" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0 bg-blue-50">
            <dt class="text-sm font-medium leading-6 text-gray-900">Casas Decimais</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.decimal_places }}</dd>
          </div>

            <div v-if="form.requires_calculation" 
                class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0 bg-blue-50">
                <dt class="text-sm font-medium leading-6 text-gray-900">Parâmetros de Entrada</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                <div v-if="form.calculation_parameters.length > 0">
                    <div class="flex flex-wrap gap-1">
                    <span v-for="param in form.calculation_parameters" :key="param"
                            class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                        {{ param }}
                    </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                    Total: {{ form.calculation_parameters.length }} parâmetro(s)
                    </p>
                </div>
                <div v-else class="text-yellow-700 text-sm">
                    ⚠️ Nenhum parâmetro definido
                </div>
                </dd>
            </div>
        </dl>
      </div>
    </div>
</confirm-dialog>
</template>
