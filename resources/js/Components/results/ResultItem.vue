<script setup>
import { computed } from "vue";
import Combobox from '@/Components/combobox.vue';
import { TrashIcon } from "@heroicons/vue/24/outline";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";

const props = defineProps({
    result: Object,
    index: Number,
    form: Object,
    record: Object,
    isInputVariable: Boolean,
    isReadOnly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['remove', 'update']);

function loadParameters(query, setOptions) {
    return loadSelectOptions('/parameters/getParameter', query, setOptions, result => ({
        value: result.id,
        label: result.code,
        name: result.name,
        code: result.code,
        requires_calculation: result.requires_calculation,
    }));
}

function loadUnits(query, setOptions) {
    return loadSelectOptions('/units/getUnit', query, setOptions, result => ({
        ...optionMappers.code(result),
        name: result.name,
    }));
}

// Determine if result is out of range
const isOutOfRange = computed(() => {
    const value = parseFloat(props.result.inserted_value);
    if (isNaN(value)) return false;
    
    if (props.result.min_ref_value !== null && value < props.result.min_ref_value) return true;
    if (props.result.max_ref_value !== null && value > props.result.max_ref_value) return true;
    
    return false;
});

// Handle value change
const handleValueChange = (value) => {
    props.result.inserted_value = value;
    emit('update', props.index, props.result);
};

// Handle parameter selection
const handleParameterSelect = (selected) => {
    props.result.parameter_id = selected;
    
    // You might want to auto-fill unit or other fields based on parameter
    if (selected && selected.unit_id) {
        props.result.unit_id = selected.unit_id;
    }
    
    emit('update', props.index, props.result);
};
</script>

<template>
<div class="rounded-[1.5rem] border p-4 shadow-sm transition-colors"
     :class="{
         'border-primary-200 bg-primary-50/70 dark:border-primary-500/25 dark:bg-primary-500/10': isInputVariable,
         'border-red-200 bg-red-50/80 dark:border-red-500/25 dark:bg-red-500/10': isOutOfRange && !isInputVariable,
         'border-slate-200 bg-white/95 hover:border-primary-300 dark:border-slate-800 dark:bg-slate-950/80 dark:hover:border-primary-500/40': !isInputVariable && !isOutOfRange,
         'bg-slate-50 dark:bg-slate-900/80': isReadOnly
     }">
    <div class="mb-4 flex items-start justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700">
                {{ index + 1 }}
            </div>
            <span v-if="isInputVariable" class="rounded-full bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-900 dark:bg-primary-500/15 dark:text-primary-100">
                Variável de Entrada
            </span>
            <span v-if="result.requires_calculation" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-500/15 dark:text-amber-100">
                Calculado
            </span>
        </div>
        
        <button v-if="!isReadOnly" 
                @click="$emit('remove', index)"
                class="rounded-full p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:text-slate-500 dark:hover:bg-red-500/10 dark:hover:text-red-300">
            <TrashIcon class="h-5 w-5" />
        </button>
    </div>

    <!-- Parameter Selection -->
    <div class="mb-4">
        <label class="mb-1.5 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
            Parâmetro
        </label>
        <Combobox v-if="!isReadOnly"
                  :hasError="form.errors[`results.${index}.parameter_id`]"
                  v-model="result.parameter_id"
                  :load-options="loadParameters"
                  @update:model-value="handleParameterSelect"
                  :disable-input="result.requires_calculation"
                  :placeholder="isInputVariable ? 'Seleccione a variável de entrada' : 'Seleccione o parâmetro'"
        />
        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            {{ result.parameter_id?.code }} - {{ result.parameter_id?.name }}
        </div>
        <p v-if="form.errors[`results.${index}.parameter_id`]" 
           class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-300">
            {{ form.errors[`results.${index}.parameter_id`] }}
        </p>
    </div>

    <!-- Unit Selection -->
    <div class="mb-4" v-if="!isInputVariable && !result.requires_calculation">
        <label class="mb-1.5 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
            Unidade
        </label>
        <Combobox v-if="!isReadOnly"
                  :hasError="form.errors[`results.${index}.unit_id`]"
                  v-model="result.unit_id"
                  :load-options="loadUnits"
                  placeholder="Seleccione a unidade"
        />
        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            {{ result.unit_id?.code || '-' }}
        </div>
    </div>

    <!-- Value Input -->
    <div class="mb-4">
        <label class="mb-1.5 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
            Resultado
            <span v-if="isInputVariable" class="ml-1 text-xs text-primary-700 dark:text-primary-300">
                (Variável para cálculo)
            </span>
            <span v-if="result.requires_calculation" class="ml-1 text-xs text-amber-700 dark:text-amber-300">
                (Calculado automaticamente)
            </span>
        </label>
        
        <div v-if="!isReadOnly">
            <input 
                v-model="result.inserted_value"
                @input="handleValueChange($event.target.value)"
                type="text"
                :disabled="result.requires_calculation"
                :class="[
                    'block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium shadow-sm ring-1 ring-inset transition focus:ring-2 focus:ring-inset dark:bg-slate-900/90 dark:text-slate-100 dark:placeholder:text-slate-500',
                    'disabled:bg-slate-100 disabled:text-slate-500 disabled:ring-slate-200 dark:disabled:bg-slate-800 dark:disabled:text-slate-500 dark:disabled:ring-slate-700',
                    isOutOfRange ? 'text-red-900 ring-red-300 placeholder-red-300 focus:ring-red-500 dark:text-red-200 dark:ring-red-500/40' :
                    isInputVariable ? 'ring-primary-300 focus:ring-primary-600 dark:ring-primary-500/35' :
                    'text-slate-900 ring-slate-300 focus:ring-primary-600 dark:ring-slate-700'
                ]"
                :placeholder="result.requires_calculation ? 'Será calculado automaticamente' : 'Introduza o resultado'"
            />
            
            <!-- Reference Range Warning -->
            <div v-if="isOutOfRange" class="mt-2 flex items-center text-xs font-semibold text-red-600 dark:text-red-300">
                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Fora do intervalo de referência
            </div>
            
            <!-- Reference Range Display -->
            <div v-if="!isInputVariable && (result.min_ref_value || result.max_ref_value)"
                 class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Referência: 
                <span v-if="result.min_ref_value && result.max_ref_value">
                    {{ result.min_ref_value }} - {{ result.max_ref_value }} {{ result.unit_id?.code }}
                </span>
                <span v-else-if="result.min_ref_value">
                    ≥ {{ result.min_ref_value }} {{ result.unit_id?.code }}
                </span>
                <span v-else-if="result.max_ref_value">
                    ≤ {{ result.max_ref_value }} {{ result.unit_id?.code }}
                </span>
            </div>
        </div>
        
        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold dark:border-slate-800 dark:bg-slate-900"
             :class="{ 'text-red-600 dark:text-red-300': isOutOfRange, 'text-slate-700 dark:text-slate-200': !isOutOfRange }">
            {{ result.inserted_value || '-' }} {{ result.unit_id?.code }} 
        </div>
    </div>

    <!-- Uncertainty Input -->

    <div class="mb-2">
        <label class="mb-1.5 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]" :for="`item-${index}-error`">Incerteza</label>

            <input
                v-model="result.uncertainty_value"
                type="text"
                :name="`item-${index}-error`"
                :id="`item-${index}-error`"
                class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500">
            
       
    </div>

    <!-- Error Display -->
    <div v-if="form.errors[`results.${index}.inserted_value`]" 
         class="mt-2 text-xs font-medium text-red-600 dark:text-red-300">
        {{ form.errors[`results.${index}.inserted_value`] }}
    </div>
</div>
</template>
