<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.results.page_title_insert') }}
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.results.code_id') }}
            <span v-if="record" class="font-semibold text-blue-900">
              {{ record.code }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ totalResults }} {{ $t('gestlab.general.labels.results.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CALCULATED PARAMETERS SECTION -->
        <div v-if="groupedResults.calculated.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CalculatorIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.results.calculated_params') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="mb-6">
              <p class="text-sm text-gray-600 mb-4">
                {{ groupedResults.calculated.length }} {{ $t('gestlab.general.labels.results.calculated_params_description') }}
              </p>
              <button 
                @click="$emit('open-calculation')"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <CalculatorIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.results.open_calculator') }}
              </button>
            </div>

            <!-- CALCULATED PARAMETERS LIST -->
            <div class="space-y-3">
              <div 
                v-for="(result, index) in groupedResults.calculated"
                :key="index"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200"
                v-motion
                :initial="{ opacity: 0, y: 10 }"
                :enter="{ opacity: 1, y: 0 }"
                :delay="index * 30"
              >
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-sm text-gray-900">
                      {{ result.parameter_id?.code }}
                    </span>
                    <span class="text-xs text-gray-500">
                      {{ result.parameter_id?.name }}
                    </span>
                  </div>
                </div>
                <div class="text-right">
                  <div v-if="result.inserted_value" class="space-y-1">
                    <div class="font-semibold text-green-600 text-sm">
                      {{ result.inserted_value }} {{ result.unit_id?.code }}
                    </div>
                    <div v-if="result.uncertainty_value" 
                         class="text-xs text-gray-500">
                      (± {{ result.uncertainty_value }})
                    </div>
                  </div>
                  <div v-else class="inline-flex items-center gap-1 text-xs text-yellow-700 bg-yellow-50 px-2 py-1 rounded-full">
                    <ClockIcon class="h-3 w-3" />
                    {{ $t('gestlab.general.labels.results.awaiting_calculation') }}
                  </div>
                  <div v-if="result.is_override" 
                       class="text-xs text-blue-600 mt-1">
                    {{ $t('gestlab.general.labels.results.manual_override') }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- INPUT VARIABLES SECTION -->
        <div v-if="groupedResults.inputVariables.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <VariableIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.results.input_variables') }}
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ groupedResults.inputVariables.length }} {{ $t('gestlab.general.labels.items') }})
              </span>
            </h2>
          </div>

          <div class="p-6 space-y-4">
            <ResultItem
              v-for="(result, index) in groupedResults.inputVariables"
              :key="`input-${index}`"
              :result="result"
              :index="index"
              :form="form"
              :record="record"
              :is-input-variable="true"
              @remove="removeResult"
            />
          </div>
        </div>

        <!-- MANUAL PARAMETERS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <PencilIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.results.manual_params') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ groupedResults.manual.length }} {{ $t('gestlab.general.buttons.items') }})
                </span>
              </h2>
              <button 
                @click="addResult"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.results.add_manual_param') }}
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="groupedResults.manual.length === 0" class="p-12 text-center">
            <DocumentIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.results.empty_state.title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.results.empty_state.manual_params') }}
            </p>
            <button 
              @click="addResult"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.add_first_param') }}  
            </button>
          </div>

          <!-- MANUAL PARAMETERS LIST -->
          <div v-else class="p-6 space-y-6">
            <ResultItem
              v-for="(result, index) in groupedResults.manual"
              :key="`manual-${index}`"
              :result="result"
              :index="form.results.findIndex(r => r.id === result.id || r.parameter_id?.code === result.parameter_id?.code)"
              :form="form"
              :record="record"
              :is-input-variable="false"
              @remove="removeResult"
            />
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.results.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="$emit('submit')"
              :disabled="form.processing || !hasResults"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !hasResults
                  ? 'bg-gray-100 text-gray-500 cursor-not-allowed border border-gray-200'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.buttons.processing') : actionText }}
            </button>

            <!-- Insert Individual Result Button -->
            <div class="mt-4 pt-4 border-t border-gray-200">
              <button @click="openIndividualEntry"
                      class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-blue-900 bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200">
                <PlusCircleIcon class="h-5 w-5" />
                Inserir Resultado Individual
              </button>
            </div>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">
                {{ $t('gestlab.general.labels.results.stats.title') }}
              </h4>
              <div class="space-y-2.5">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.results.stats.total_params') }}</span>
                  <span class="font-semibold text-blue-900">{{ totalResults }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.results.stats.calculated') }}</span>
                  <span class="font-semibold text-purple-600">{{ groupedResults.calculated.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.results.stats.input_vars') }}</span>
                  <span class="font-semibold text-blue-600">{{ groupedResults.inputVariables.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.results.stats.manual') }}</span>
                  <span class="font-semibold text-gray-700">{{ groupedResults.manual.length }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.results.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.results.status.calculation_ready') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                groupedResults.calculated.length > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ groupedResults.calculated.length > 0 ? $t('gestlab.general.labels.results.status.ready') : $t('gestlab.general.labels.results.status.not_required') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.results.status.params_complete') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                totalResults > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ totalResults > 0 ? $t('gestlab.general.labels.results.status.has_data') : $t('gestlab.general.labels.results.status.no_data') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.results.last_updated') }}: {{ new Date().toLocaleDateString() }}
      </div>
      <div class="flex items-center gap-3">
        <button 
          @click="$emit('open-calculation')"
          v-if="groupedResults.calculated.length > 0"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-blue-900 bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <CalculatorIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.results.recalculate') }}
        </button>
      </div>
    </div>
  </div>

  <IndividualResultEntry
    v-if="showIndividualEntry"
    :sample-id="record?.sample_id?.value"
    :parameters="form.results"
    :action="action"
    @close="showIndividualEntry = false"
    @saved="handleIndividualResultSaved"
  />

</template>

<script setup>
import { computed } from "vue";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  CalculatorIcon,
  BeakerIcon,
  VariableIcon,
  PencilIcon,
  DocumentIcon,
  CheckIcon,
  InformationCircleIcon,
  ClockIcon
} from "@heroicons/vue/24/outline";
import ResultItem from '@/Components/results/ResultItem.vue';
import IndividualResultEntry from '@/Components/results/IndividualResultEntry.vue'

const props = defineProps({
    form: Object,
    record: Object,
    action: String,
    separatedResults: Object
});

const emit = defineEmits(['open-calculation', 'submit', 'update-results']);

const showIndividualEntry = ref(false);

const openIndividualEntry = () => {
  showIndividualEntry.value = true
}

const handleIndividualResultSaved = (resultData) => {
  // Find and update the specific result
  const index = form.results.findIndex(r => 
    r.parameter_id?.value === resultData.parameter_id?.value ||
    r.result_id === resultData.result_id
  )
  
  if (index !== -1) {
    form.results[index] = {
      ...form.results[index],
      ...resultData
    }
  } else {
    // If it's a new parameter not in the list, add it
    form.results.push(resultData)
  }
  
  showIndividualEntry.value = false
}

// Group results by type for better UI organization
const groupedResults = computed(() => {
    return {
        calculated: props.separatedResults?.calculatedParams || [],
        inputVariables: props.separatedResults?.inputVariables || [],
        manual: props.separatedResults?.manualParams || []
    };
});

// Computed properties
const totalResults = computed(() => {
    return groupedResults.value.calculated.length + 
           groupedResults.value.inputVariables.length + 
           groupedResults.value.manual.length;
});

const hasResults = computed(() => totalResults.value > 0);

const actionText = computed(() => {
    switch (props.action) {
        case 'analyze': return 'Inserir Resultados';
        case 'verify': return 'Verificar Resultados';
        default: return 'Validar Resultados';
    }
});

const addResult = () => {
    props.form.results.push({
        parameter_id: '',
        unit_id: '',
        inserted_value: '',
        uncertainty_value: '',
        active: true,
        requires_calculation: false,
    });
};

const removeResult = (index) => {
    props.form.results.splice(index, 1);
};

const handleCalculatedResults = (payload) => {
    // The payload structure is assumed to be:
    // { 
    //   results: { 'CODE_A': value, 'CODE_A_uncertainty_value': uncertainty, ... }, 
    //   overrides: { 'CODE_A': true/false, ... }, 
    //   metadata: { 'CODE_A': { ... calculation_metadata } }, 
    // }
    
    // 1. Iterate over the results in the main form array
    props.form.results.forEach(result => {
        const code = result.parameter_id?.code;

        if (code && payload.results[code] !== undefined) {
            // 2. Update the value and uncertainty for calculated/input variables
            
            // Update the main value (calculated output or input variable)
            result.inserted_value = payload.results[code];

            // Update uncertainty if provided (only for calculated outputs typically)
            const uncertaintyKey = `${code}_uncertainty_value`;
            if (payload.results[uncertaintyKey] !== undefined) {
                result.uncertainty_value = payload.results[uncertaintyKey];
            }

            // 3. Update calculation-specific metadata fields
            
            // ✅ CRITICAL: Add calculation_metadata
            if (payload.metadata[code]) {
                result.calculation_metadata = payload.metadata[code];
            }
            
            // Update manual_override status
            result.is_override = payload.overrides[code] || false;
            
            // Note: This assumes the 'code' is unique and used as the key in the payload.
        }
    });

    console.log('Results updated with calculation metadata:', props.form.results);
    
    // If the parent component needs to know the form has changed, you can emit:
    emit('update-results'); 
};
</script>