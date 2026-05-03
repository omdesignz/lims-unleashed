<!-- resources/js/Components/results/InsertResultComponent.vue -->
<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-white shadow-lg shadow-blue-950/20 dark:bg-blue-500 dark:text-slate-950">
            <BeakerIcon class="h-6 w-6" />
          </div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.results.page_title_insert') }}
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.results.code_id') }}
            <span v-if="record" class="font-semibold text-blue-900 dark:text-blue-300">
              {{ record.code }}
            </span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/15 dark:text-blue-200 dark:ring-blue-400/20">
            {{ totalResults }} {{ $t('gestlab.general.labels.results.items') }}
          </span>
          
          <!-- Individual Entry Badge -->
          <span v-if="hasIndividualEntries" 
                class="inline-flex items-center gap-1 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-900 ring-1 ring-inset ring-green-700/10 dark:bg-green-500/15 dark:text-green-200 dark:ring-green-400/20">
            <CheckCircleIcon class="h-4 w-4" />
            {{ individualEntryCount }} individuais
          </span>
        </div>
      </div>
      
      <!-- WORKFLOW MODE SELECTOR -->
      <div class="mt-6 border-t border-slate-200/80 pt-6 dark:border-slate-800">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="text-sm text-slate-600 dark:text-slate-300">
            Modo de trabalho: 
            <span class="font-medium text-blue-900 dark:text-blue-300">{{ workflowMode === 'batch' ? 'Em Lote' : 'Individual' }}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button @click="switchWorkflowMode('batch')"
                    :class="[
                      'rounded-xl px-4 py-2 text-sm font-medium transition-all',
                      workflowMode === 'batch'
                        ? 'bg-blue-900 text-white shadow-sm'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                    ]">
              <DocumentTextIcon class="h-4 w-4 inline mr-1" />
              Em Lote
            </button>
            <button @click="switchWorkflowMode('individual')"
                    :class="[
                      'rounded-xl px-4 py-2 text-sm font-medium transition-all',
                      workflowMode === 'individual'
                        ? 'bg-blue-900 text-white shadow-sm'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                    ]">
              <DocumentPlusIcon class="h-4 w-4 inline mr-1" />
              Individual
            </button>
          </div>
        </div>
        
        <!-- Instructions based on mode -->
        <div v-if="workflowMode === 'individual'" class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
          <div class="flex items-start gap-3">
            <InformationCircleIcon class="h-5 w-5 text-blue-900 mt-0.5" />
            <div>
              <p class="text-sm font-medium text-blue-900 dark:text-blue-200">Modo individual activo</p>
              <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                Clique em qualquer parâmetro para inserir ou editar o respectivo resultado.
                Pode alternar para o modo em lote a qualquer momento.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- INDIVIDUAL MODE CONTENT -->
    <div v-if="workflowMode === 'individual'" class="space-y-6">
      <!-- QUICK STATS CARD -->
      <div class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="rounded-2xl bg-blue-50 p-4 text-center dark:bg-blue-500/10">
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-300">{{ totalResults }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-300">Total de Parâmetros</div>
          </div>
          <div class="rounded-2xl bg-green-50 p-4 text-center dark:bg-green-500/10">
            <div class="text-2xl font-bold text-green-900 dark:text-green-300">{{ insertedCount }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-300">Inseridos</div>
          </div>
          <div class="rounded-2xl bg-yellow-50 p-4 text-center dark:bg-amber-500/10">
            <div class="text-2xl font-bold text-yellow-900 dark:text-amber-300">{{ pendingCount }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-300">Pendentes</div>
          </div>
          <div class="rounded-2xl bg-purple-50 p-4 text-center dark:bg-purple-500/10">
            <div class="text-2xl font-bold text-purple-900 dark:text-purple-300">{{ calculatedCount }}</div>
            <div class="text-sm text-slate-600 dark:text-slate-300">Calculados</div>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-6">
          <div class="mb-2 flex justify-between text-sm text-slate-600 dark:text-slate-300">
            <span>Progresso: {{ insertedCount }} de {{ totalResults }}</span>
            <span>{{ Math.round((insertedCount / totalResults) * 100) }}%</span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-full bg-gradient-to-r from-blue-900 to-blue-800 rounded-full transition-all duration-500"
                 :style="{ width: `${(insertedCount / totalResults) * 100}%` }"></div>
          </div>
        </div>
      </div>

      <!-- PARAMETERS GRID -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="(result, index) in form.results" 
             :key="getResultKey(result, index)"
             @click="openIndividualEntryForResult(result)"
             class="group cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition-all duration-200 hover:border-blue-900 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
             :class="{
               'border-green-200 bg-green-50 dark:border-green-500/30 dark:bg-green-500/10': getResultDisplayValue(result) && String(getResultDisplayValue(result)).trim() !== '',
               'border-purple-200 bg-purple-50 dark:border-purple-500/30 dark:bg-purple-500/10': result.requires_calculation,
               'border-slate-200 dark:border-slate-800': !getResultDisplayValue(result) || String(getResultDisplayValue(result)).trim() === ''
             }">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ result.parameter_id?.code || 'N/D' }}
                </span>
                <span v-if="result.requires_calculation" 
                      class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-800 dark:bg-purple-500/15 dark:text-purple-200">
                  <CalculatorIcon class="h-3 w-3" />
                  Calculado
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ result.parameter_id?.name }}</p>
              <div v-if="result.requires_calculation" class="mt-2 flex flex-wrap gap-2">
                <span :class="[
                  'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                  getCalculationReadiness(result).ready ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                ]">
                  {{ getCalculationReadiness(result).ready ? 'Pronto para cálculo' : 'Entradas em falta' }}
                </span>
              </div>
              <p v-if="result.requires_calculation && getCalculationReadiness(result).missingVariables.length"
                 class="mt-2 text-xs text-amber-800">
                Faltam: {{ getCalculationReadiness(result).missingVariables.join(', ') }}
              </p>
              
              <!-- Value Display -->
              <div v-if="getResultDisplayValue(result) && String(getResultDisplayValue(result)).trim() !== ''" class="mt-2">
                <div class="text-lg font-bold text-green-900">
                  {{ getResultDisplayValue(result) }}
                  <span v-if="result.unit_label" class="text-sm font-normal text-slate-600 dark:text-slate-400">
                    {{ result.unit_label }}
                  </span>
                </div>
                <div v-if="result.uncertainty_value" class="text-xs text-slate-500 dark:text-slate-400">
                  ± {{ result.uncertainty_value }}
                </div>
                <div v-if="result.insertion_method === 'individual'" 
                     class="text-xs text-blue-600 mt-1 flex items-center gap-1">
                  <DocumentPlusIcon class="h-3 w-3" />
                  Individual
                </div>
              </div>
              <div v-else class="mt-2">
                <div class="text-sm text-yellow-700 bg-yellow-50 px-2 py-1 rounded inline-flex items-center gap-1">
                  <ClockIcon class="h-3 w-3" />
                  Pendente
                </div>
              </div>
            </div>
            
            <!-- Status Icon -->
            <div class="ml-2">
              <CheckCircleIcon v-if="getResultDisplayValue(result) && String(getResultDisplayValue(result)).trim() !== ''" 
                               class="h-5 w-5 text-green-600 dark:text-green-300" />
              <ClockIcon v-else class="h-5 w-5 text-slate-400 dark:text-slate-500" />
            </div>
          </div>
          
          <!-- Reference Range -->
          <div v-if="result.min_ref_value || result.max_ref_value" class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
            <p class="mb-1 text-xs text-slate-500 dark:text-slate-400">Referência:</p>
            <p class="text-xs font-medium text-blue-900 dark:text-blue-300">
              <template v-if="result.min_ref_value && result.max_ref_value">
                {{ result.min_ref_value }} - {{ result.max_ref_value }}
              </template>
              <template v-else-if="result.min_ref_value">
                ≥ {{ result.min_ref_value }}
              </template>
              <template v-else-if="result.max_ref_value">
                ≤ {{ result.max_ref_value }}
              </template>
              <span v-if="result.unit_label">{{ result.unit_label }}</span>
            </p>
          </div>
          
          <!-- Action Button -->
          <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <button @click.stop="openIndividualEntryForResult(result)"
                    class="w-full rounded-xl bg-blue-50 py-1 text-center text-sm font-medium text-blue-900 hover:bg-blue-100 hover:text-blue-700 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/20">
              {{ getResultDisplayValue(result) && String(getResultDisplayValue(result)).trim() !== '' ? 'Editar Resultado' : 'Inserir Resultado' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BATCH MODE CONTENT -->
    <div v-else class="space-y-6">
      <!-- CALCULATED PARAMETERS SECTION -->
      <div v-if="groupedResults.calculated.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <CalculatorIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.results.calculated_params') }}
          </h2>
        </div>
        
        <div class="p-6">
          <div class="mb-6">
            <p class="text-sm text-gray-600 mb-4">
              {{ groupedResults.calculated.length }} {{ $t('gestlab.general.labels.results.calculated_params_description') }}
            </p>
            <button 
              @click="emit('open-calculation')"
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
                <div v-if="result.requires_calculation && !getCalculationReadiness(result).ready"
                     class="mb-2 inline-flex items-center gap-1 text-xs text-amber-700 bg-amber-50 px-2 py-1 rounded-full">
                  <ClockIcon class="h-3 w-3" />
                  Aguardando {{ getCalculationReadiness(result).missingVariables.join(', ') }}
                </div>
                <div v-if="getResultDisplayValue(result)" class="space-y-1">
                  <div class="font-semibold text-green-600 text-sm">
                    {{ getResultDisplayValue(result) }} {{ result.unit_id?.code }}
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

      <!-- INDIVIDUAL ENTRY OPTION -->
      <div class="bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-white px-6 py-4 border-b border-blue-200">
          <h3 class="text-lg font-semibold text-blue-900 flex items-center gap-2">
            <DocumentPlusIcon class="h-5 w-5" />
            Inserção Individual
          </h3>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600 mb-4">
            Precisa inserir apenas alguns resultados? Use o modo individual para maior flexibilidade.
          </p>
          <button @click="switchWorkflowMode('individual')"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-blue-900 bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
            <DocumentPlusIcon class="h-5 w-5" />
            Alternar para Modo Individual
          </button>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="sticky bottom-4 z-10 flex flex-col gap-4 rounded-[24px] border border-slate-200 bg-white/95 p-5 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/90 lg:flex-row lg:items-center lg:justify-between">
      <div class="text-sm text-slate-500 dark:text-slate-400">
        {{ $t('gestlab.general.labels.results.last_updated') }}: {{ new Date().toLocaleDateString() }}
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <!-- Individual mode actions -->
        <div v-if="workflowMode === 'individual'" class="flex items-center gap-3">
          <button @click="submitResults"
                  :disabled="form.processing || insertedCount === 0"
                  :class="[
                    'inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    form.processing || insertedCount === 0
                      ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                      : 'bg-gradient-to-r from-blue-950 via-blue-900 to-blue-800 text-white hover:from-blue-900 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950'
                  ]">
            <CheckIcon class="h-5 w-5" />
            {{ form.processing ? 'A processar...' : `Finalizar (${insertedCount}/${totalResults})` }}
          </button>
        </div>
        
        <!-- Batch mode actions -->
        <div v-else class="flex items-center gap-3">
          <button @click="emit('open-calculation')"
                  v-if="hasCalculatedParameters"
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-900 bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 dark:border-blue-400 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-blue-500/10 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950">
            <CalculatorIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.results.recalculate') }}
          </button>
          
          <button @click="submitResults"
                  :disabled="form.processing"
                  :class="[
                    'inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    form.processing
                      ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                      : 'bg-gradient-to-r from-blue-950 via-blue-900 to-blue-800 text-white hover:from-blue-900 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950'
                  ]">
            <CheckIcon class="h-5 w-5" />
            {{ form.processing ? 'A processar...' : 'Inserir resultados' }}
          </button>
        </div>
      </div>
    </div>

    <!-- INDIVIDUAL RESULT ENTRY MODAL -->
    <IndividualResultEntry
      v-if="showIndividualEntry"
      :sample-id="record?.sample_id?.value"
      :parameters="form.results"
      :action="action"
      :existing-results="selectedIndividualParameter"
      @close="showIndividualEntry = false"
      @saved="handleIndividualResultSaved"
      @open-calculation="handleOpenCalculationForParameter"
    />

    <!-- CALCULATION MODAL -->
    <CalculationModal
      v-if="showCalculationModal"
      :sample-id="record?.sample_id?.value"
      :parameters="calculationParameters"
      :existing-results="existingCalculationData"
      :action="action"
      @close="showCalculationModal = false"
      @calculated="handleCalculatedResults"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
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
  ClockIcon,
  DocumentTextIcon,
  DocumentPlusIcon,
  CheckCircleIcon
} from "@heroicons/vue/24/outline";
import ResultItem from '@/Components/results/ResultItem.vue';
import IndividualResultEntry from '@/Components/results/IndividualResultEntry.vue';
import CalculationModal from '@/Components/results/CalculationModal.vue';
import { ResultsDataService } from '@/Services/ResultsDataService.js';

const props = defineProps({
    form: Object,
    record: Object,
    action: String,
    separatedResults: Object
});

const emit = defineEmits(['open-calculation', 'submit', 'update-results']);

// State for workflow mode
const workflowMode = ref('batch') // 'batch' or 'individual'
const showIndividualEntry = ref(false)
const selectedIndividualParameter = ref(null)
const showCalculationModal = ref(false)
const calculationParameters = ref([])
const existingCalculationData = ref({})

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
    return props.form.results?.length || 0;
});

const insertedCount = computed(() => {
    if (!props.form.results) return 0
    return props.form.results.filter((result) => {
        const value = getResultDisplayValue(result)
        return value !== null && value !== undefined && String(value).trim() !== ''
    }).length
});

const pendingCount = computed(() => {
    return totalResults.value - insertedCount.value
});

const calculatedCount = computed(() => {
    if (!props.form.results) return 0
    return props.form.results.filter(r => r.is_calculated).length
});

const individualEntryCount = computed(() => {
    if (!props.form.results) return 0
    return props.form.results.filter(r => r.insertion_method === 'individual').length
});

const hasIndividualEntries = computed(() => individualEntryCount.value > 0)

const hasCalculatedParameters = computed(() => {
    return props.form.results?.some(p => p.requires_calculation && p.active) || false
});

const getResultDisplayValue = (result) => {
    return ResultsDataService.getResultValue(result, props.action)
}

const getCalculationReadiness = (result) => {
    return ResultsDataService.getCalculationReadiness(result, props.form.results, props.action)
}

// Methods
const getResultKey = (result, index) => {
    return result.result_id || result.id || `temp-${index}`
}

const switchWorkflowMode = (mode) => {
    workflowMode.value = mode
}

const openIndividualEntryForResult = (result) => {
    selectedIndividualParameter.value = result
    showIndividualEntry.value = true
}

const handleIndividualResultSaved = (resultData) => {
    // Find the existing result
    const index = props.form.results.findIndex(r => 
        r.result_id === resultData.result_id ||
        (r.parameter_id?.value === resultData.parameter_id?.value && !r.result_id)
    )
    
    if (index !== -1) {
        // Update existing result
        props.form.results[index] = {
            ...props.form.results[index],
            ...resultData,
            insertion_method: 'individual'
        }
    } else {
        // Add as new result
        props.form.results.push({
            ...resultData,
            insertion_method: 'individual'
        })
    }
    
    showIndividualEntry.value = false
    selectedIndividualParameter.value = null
    
    // Emit update if parent component needs to know
    emit('update-results')
}

const handleOpenCalculationForParameter = (parameter) => {
    // Open calculation modal for this specific parameter
    calculationParameters.value = [parameter]
    existingCalculationData.value = ResultsDataService.prepareForSingleCalculation(
        parameter,
        props.form.results
    )
    showCalculationModal.value = true
    showIndividualEntry.value = false
}

const handleCalculatedResults = (calculationPayload) => {
    props.form.results = ResultsDataService.mergeCalculationResults(
        props.form.results,
        calculationPayload,
        props.action
    )
    showCalculationModal.value = false

    // Force UI update
    props.form.results = [...props.form.results]

    // If we were in individual entry mode, reopen it
    if (selectedIndividualParameter.value) {
        showIndividualEntry.value = true
    }
}

const submitResults = () => {
    emit('submit')
}

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
</script>
