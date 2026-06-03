<!-- resources/js/Components/results/CalculationResultEntry.vue - Updated with additional inputs -->
<template>
  <div class="result-entry-modal space-y-8 text-[#17231f] dark:text-[#f7f1e7]">
    <!-- MODAL HEADER -->
    <div class="rounded-[1.75rem] border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.32)] dark:border-[#25443c] dark:bg-slate-950/85">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">Cálculo rastreável</p>
          <h1 class="mt-2 flex items-center gap-3 text-2xl font-bold text-[#17231f] dark:text-[#f7f1e7]">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-900 text-white shadow-lg shadow-primary-900/20 dark:bg-primary-400 dark:text-slate-950">
              <CalculatorIcon class="h-6 w-6" />
            </span>
            Resultados de Parâmetros Calculados
            <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-900 dark:bg-primary-500/15 dark:text-primary-100">{{ calculationMode === 'single' ? 'Individual' : 'Em Lote' }}</span>
          </h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <button @click="calculationMode = 'single'"
                  :class="[
                    'flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all',
                    calculationMode === 'single'
                      ? 'bg-primary-900 text-white shadow-lg shadow-primary-900/20 dark:bg-primary-400 dark:text-slate-950'
                      : 'border border-slate-200 bg-white text-slate-700 hover:bg-primary-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-primary-500/10'
                  ]">
            <DocumentPlusIcon class="h-4 w-4" />
            Individual
          </button>
          <button @click="calculationMode = 'batch'"
                  :class="[
                    'flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-all',
                    calculationMode === 'batch'
                      ? 'bg-primary-900 text-white shadow-lg shadow-primary-900/20 dark:bg-primary-400 dark:text-slate-950'
                      : 'border border-slate-200 bg-white text-slate-700 hover:bg-primary-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-primary-500/10'
                  ]">
            <DocumentTextIcon class="h-4 w-4" />
            Em Lote
          </button>
        </div>
      </div>
    </div>

    <!-- SINGLE PARAMETER CALCULATION MODE -->
    <div v-if="calculationMode === 'single'" class="space-y-6">
      <!-- PARAMETER SELECTION -->
      <div class="rounded-[1.75rem] border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
        <h3 class="mb-4 text-lg font-semibold text-[#17231f] dark:text-[#f7f1e7]">
          Calcular Parâmetro Individual
        </h3>
        
        <div class="space-y-4">
          <!-- Parameter Selector -->
          <div>
            <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
              Seleccione um parâmetro para calcular
            </label>
            <select v-model="selectedSingleParameter"
                    @change="onSingleParameterChange"
                    class="block w-full rounded-2xl border border-slate-300/90 bg-white/95 py-3 pl-4 pr-10 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-white/50 transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800/60">
              <option value="">Seleccione um parâmetro</option>
              <option v-for="param in availableSingleParameters" 
                      :key="param.parameter_id.code"
                      :value="param.parameter_id.code">
                {{ param.parameter_id.code }} - {{ param.parameter_id.name }}
                <span v-if="results[param.parameter_id.code]">(✓ {{ results[param.parameter_id.code] }})</span>
              </option>
            </select>
          </div>

          <!-- Selected Parameter Info -->
          <div v-if="selectedSingleParameterObj" class="rounded-2xl border border-primary-200 bg-primary-50/70 p-4 dark:border-primary-500/25 dark:bg-primary-500/10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <h4 class="font-semibold text-[#17231f] dark:text-[#f7f1e7]">{{ selectedSingleParameterObj.parameter_id.name }}</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ selectedSingleParameterObj.parameter_id.code }}</p>
                <div v-if="selectedSingleParameterObj.formula?.output_unit" class="mt-1 text-sm font-medium text-primary-700 dark:text-primary-300">
                  Unidade: {{ selectedSingleParameterObj.formula.output_unit }}
                </div>
              </div>
              <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-primary-900 shadow-sm ring-1 ring-primary-200 dark:bg-slate-900 dark:text-primary-100 dark:ring-primary-500/25">
                {{ getSingleCalculationStatus().complete ? 'Pronto para calcular' : 'A aguardar variáveis' }}
              </span>
            </div>
            
            <!-- Formula Display -->
            <div v-if="selectedSingleParameterObj.formula" class="mt-3 border-t border-primary-200 pt-3 dark:border-primary-500/25">
              <p class="mb-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600 dark:text-slate-400">Fórmula:</p>
              <code class="block rounded-xl border border-slate-200 bg-white/90 p-3 font-mono text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">
                {{ selectedSingleParameterObj.formula.expression }}
              </code>
            </div>
          </div>
        </div>
      </div>

      <!-- INPUT VARIABLES SECTION -->
      <div v-if="selectedSingleParameterObj && singleParameterInputs.length > 0" 
           class="overflow-hidden rounded-[1.75rem] border border-[#25443c] bg-slate-950/90 shadow-sm">
        <div class="bg-gradient-to-r from-primary-950 via-primary-900 to-primary-700 px-6 py-4">
          <h3 class="flex items-center gap-2 text-lg font-semibold text-white">
            <VariableIcon class="h-5 w-5" />
            Variáveis de Entrada
            <span class="text-sm font-normal">({{ getSingleCalculationStatus().complete ? `${singleParameterInputs.length}/${singleParameterInputs.length}` : `${filledInputCount}/${singleParameterInputs.length}` }})</span>
          </h3>
        </div>
        
        <div class="p-6 space-y-4">
          <div v-for="variableName in singleParameterInputs" :key="variableName"
              class="group rounded-2xl border border-slate-200 bg-white/95 p-4 transition-all duration-200 hover:border-primary-400 dark:border-slate-800 dark:bg-slate-900/80 dark:hover:border-primary-500/40">
            
            <label class="mb-2 flex items-center gap-1 text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
              <HashtagIcon class="h-4 w-4 text-primary-700 dark:text-primary-300" />
              {{ variableName }}
            </label>
            
            <div class="relative">
              <input
                v-model="singleCalcInputs[variableName]" 
                type="number" 
                step="0.0001"
                @input="onSingleInputChange"
                class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-950/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
                :class="{
                  'ring-amber-500 bg-amber-50 dark:bg-amber-500/10': !singleCalcInputs[variableName] || singleCalcInputs[variableName] === '',
                  'ring-emerald-500 bg-emerald-50 dark:bg-emerald-500/10': singleCalcInputs[variableName] && singleCalcInputs[variableName] !== ''
                }"
                placeholder="Introduza o valor..."
              />
              <div v-if="singleCalcInputs[variableName] && singleCalcInputs[variableName] !== ''"
                  class="absolute inset-y-0 right-0 flex items-center pr-3">
                <CheckCircleIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
              </div>
            </div>
            
            <!-- Show existing value from main results -->
            <div v-if="results[variableName] && singleCalcInputs[variableName] !== results[variableName]"
                 class="mt-2 flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
              <InformationCircleIcon class="h-3 w-3" />
              Valor pré-existente: {{ results[variableName] }}
            </div>
          </div>
        </div>
      </div>

      <!-- ADDITIONAL PARAMETER FIELDS -->
      <div v-if="selectedSingleParameterObj" class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white/90 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
        <div class="border-b border-slate-200 bg-gradient-to-r from-primary-50 to-white px-6 py-4 dark:border-slate-800 dark:from-primary-500/10 dark:to-transparent">
          <h3 class="flex items-center gap-2 text-lg font-semibold text-primary-900 dark:text-primary-100">
            <Cog6ToothIcon class="h-5 w-5" />
            Configurações do Parâmetro
          </h3>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Uncertainty Input -->
          <div>
            <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
              <ScaleIcon class="h-4 w-4 text-primary-700 dark:text-primary-300" />
              Incerteza do Resultado
              <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-slate-500 dark:text-slate-400">
                ({{ selectedSingleParameterObj.formula.output_unit }})
              </span>
            </label>
            <div class="relative">
              <input 
                v-model="singleParameterUncertainty"
                type="number"
                step="0.0001"
                class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
                placeholder="Ex.: 0,1"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <span class="text-slate-500 dark:text-slate-400">±</span>
              </div>
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              Incerteza padrão associada ao resultado calculado
            </p>
          </div>

          <!-- Reference Range Section -->
          <div class="space-y-4">
            <h4 class="flex items-center gap-2 text-sm font-semibold text-[#17231f] dark:text-[#f7f1e7]">
              <ChartBarIcon class="h-4 w-4 text-primary-700 dark:text-primary-300" />
              Valores de Referência
            </h4>
            
            <!-- Min Reference Value -->
            <div>
              <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                Valor Mínimo de Referência
                <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-slate-500 dark:text-slate-400">
                  ({{ selectedSingleParameterObj.formula.output_unit }})
                </span>
              </label>
              <div class="relative">
                <input 
                  v-model="singleParameterMinRef"
                  type="number"
                  step="0.0001"
                  class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
                  placeholder="Valor mínimo aceitável"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <span class="text-slate-500 dark:text-slate-400">≥</span>
                </div>
              </div>
            </div>

            <!-- Max Reference Value -->
            <div>
              <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                Valor Máximo de Referência
                <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-slate-500 dark:text-slate-400">
                  ({{ selectedSingleParameterObj.formula.output_unit }})
                </span>
              </label>
              <div class="relative">
                <input 
                  v-model="singleParameterMaxRef"
                  type="number"
                  step="0.0001"
                  class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
                  placeholder="Valor máximo aceitável"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <span class="text-slate-500 dark:text-slate-400">≤</span>
                </div>
              </div>
            </div>

            <!-- Reference Range Display -->
            <div v-if="singleParameterMinRef || singleParameterMaxRef" 
                 class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/75">
              <p class="mb-1 text-sm font-semibold text-slate-700 dark:text-slate-300">Intervalo de Referência:</p>
              <p class="text-lg font-bold text-primary-900 dark:text-primary-200">
                <span v-if="singleParameterMinRef && singleParameterMaxRef">
                  {{ singleParameterMinRef }} - {{ singleParameterMaxRef }}
                </span>
                <span v-else-if="singleParameterMinRef">
                  ≥ {{ singleParameterMinRef }}
                </span>
                <span v-else-if="singleParameterMaxRef">
                  ≤ {{ singleParameterMaxRef }}
                </span>
                <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-sm font-normal">
                  {{ selectedSingleParameterObj.formula.output_unit }}
                </span>
              </p>
            </div>
          </div>

          <!-- Notes Section -->
          <div>
            <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
              <ChatBubbleLeftRightIcon class="h-4 w-4 text-primary-700 dark:text-primary-300" />
              Observações
            </label>
            <textarea 
              v-model="singleParameterNotes"
              rows="3"
              class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
              :placeholder="`Observações sobre ${selectedSingleParameterObj.parameter_id.code}...`"
            ></textarea>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              Observações sobre o cálculo ou resultado
            </p>
          </div>
        </div>
      </div>

      <!-- CALCULATION RESULT -->
      <div v-if="selectedSingleParameterObj" class="rounded-[1.75rem] border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h3 class="text-lg font-semibold text-[#17231f] dark:text-[#f7f1e7]">Resultado Final</h3>
          
          <!-- Override Toggle -->
          <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
            <input 
              v-model="singleParameterOverride" 
              type="checkbox" 
              @change="onOverrideToggle"
              class="h-4 w-4 rounded border-slate-300 text-primary-900 focus:ring-primary-600 dark:border-slate-700 dark:bg-slate-900"
            >
            <span>Inserir valor manualmente</span>
          </label>
        </div>
        
        <!-- Manual Input when overridden -->
        <div v-if="singleParameterOverride" class="mb-6 space-y-4">
          <div>
            <label class="mb-2 block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
              Valor Manual
              <span v-if="selectedSingleParameterObj.formula?.output_unit" class="text-slate-500 dark:text-slate-400">
                ({{ selectedSingleParameterObj.formula.output_unit }})
              </span>
            </label>
            <input 
              v-model="singleCalcManualValue"
              type="text"
              class="block w-full rounded-2xl border-0 bg-white/95 px-3 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 transition focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500"
              placeholder="Introduza o valor manual"
            />
          </div>
        </div>
        
        <!-- Calculated Result Display -->
        <div v-else-if="singleCalculationResult !== null" 
             class="mb-6 rounded-[1.5rem] border border-emerald-200 bg-gradient-to-br from-emerald-50 to-primary-50 p-6 dark:border-emerald-500/25 dark:from-emerald-500/10 dark:to-primary-500/10">
          <div class="text-center">
            <p class="mb-2 text-sm font-semibold text-slate-600 dark:text-slate-300">Resultado Calculado</p>
            <p class="text-3xl font-bold text-emerald-900 dark:text-emerald-100">
              {{ singleCalculationResult }}
              <span v-if="selectedSingleParameterObj.formula?.output_unit" 
                    class="text-xl font-normal text-slate-600 dark:text-slate-300">
                {{ selectedSingleParameterObj.formula.output_unit }}
              </span>
            </p>
            
            <!-- Uncertainty Display -->
            <div v-if="singleParameterUncertainty" 
                 class="mt-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-100">
              <span class="text-sm">± {{ singleParameterUncertainty }}</span>
            </div>
            
            <!-- Reference Range Check -->
            <div v-if="singleParameterMinRef || singleParameterMaxRef" class="mt-4">
              <div v-if="isWithinReferenceRange()"
                   class="inline-flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-primary-900 dark:bg-primary-500/15 dark:text-primary-100">
                <CheckCircleIcon class="h-4 w-4" />
                <span class="text-sm">Dentro do intervalo de referência</span>
              </div>
              <div v-else
                   class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-red-900 dark:bg-red-500/15 dark:text-red-100">
                <ExclamationTriangleIcon class="h-4 w-4" />
                <span class="text-sm">Fora do intervalo de referência</span>
              </div>
            </div>
            
            <div v-if="selectedSingleParameterObj.formula" class="mt-4 text-xs text-slate-500 dark:text-slate-400">
              Usando fórmula: {{ selectedSingleParameterObj.formula.expression }}
            </div>
          </div>
        </div>

        <div v-if="singleCalculationError" class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-500/25 dark:bg-red-500/10">
          <p class="text-sm font-semibold text-red-900 dark:text-red-100">
            {{ singleCalculationError }}
          </p>
        </div>
        
        <!-- Missing Inputs Warning -->
        <div v-if="getSingleCalculationStatus().missing.length > 0 && !singleParameterOverride"
             class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/25 dark:bg-amber-500/10">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-amber-600 dark:text-amber-300" />
            <div>
              <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">A aguardar variáveis</p>
              <p class="mt-1 text-sm text-amber-700 dark:text-amber-200">
                Introduza valores para: {{ getSingleCalculationStatus().missing.join(', ') }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
          <button @click="clearSingleCalculation"
                  class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-950">
            Limpar
          </button>
          
          <button @click="applySingleCalculation"
                  :disabled="!canApplySingleCalculation"
                  :class="[
                    'inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    !canApplySingleCalculation
                      ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                      : 'bg-primary-900 text-white hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-slate-950'
                  ]">
            <CheckIcon class="h-5 w-5" />
            {{ singleParameterOverride ? 'Aplicar Valor Manual' : 'Aplicar Resultado Calculado' }}
          </button>
        </div>
      </div>

      <!-- NEXT PARAMETER SUGGESTION -->
      <div v-if="nextAvailableParameter && selectedSingleParameter" class="rounded-[1.5rem] border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h3 class="text-sm font-semibold text-[#17231f] dark:text-[#f7f1e7]">Próximo parâmetro sugerido</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ nextAvailableParameter.parameter_id.name }} ({{ nextAvailableParameter.parameter_id.code }})</p>
          </div>
          <button @click="selectNextParameter"
                  class="inline-flex items-center gap-2 rounded-full bg-primary-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-800">
            Calcular Agora
          </button>
        </div>
      </div>
    </div>

    <!-- BATCH CALCULATION MODE -->
    <div v-else class="space-y-6">
      <!-- Batch mode content remains the same -->
      <!-- ... -->
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex flex-col gap-4 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
      <div class="text-sm text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full" 
                 :class="canSave ? 'bg-emerald-500' : 'bg-amber-500'"></div>
            <span>
              {{ calculationMode === 'single' ? 'Parâmetro individual' : `${calculatedCount} de ${allCalculatedParameters.length} calculados` }}
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="closeModal"
          class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-950"
        >
          <XMarkIcon class="h-5 w-5" />
          Fechar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { 
  CalculatorIcon,
  VariableIcon,
  HashtagIcon,
  CheckCircleIcon,
  XMarkIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  DocumentPlusIcon,
  InformationCircleIcon,
  ScaleIcon,
  Cog6ToothIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
  parameters: Array,
  existingResults: Object,
  action: String
})

const emit = defineEmits(['calculatedResults', 'close'])

// State for calculation mode
const calculationMode = ref('single') // 'single' or 'batch'
const selectedSingleParameter = ref('')
const singleCalcInputs = ref({})
const singleCalculationResult = ref(null)
const singleCalculationError = ref('')
const singleParameterOverride = ref(false)
const singleCalcManualValue = ref('')

// Additional parameter fields
const singleParameterUncertainty = ref('')
const singleParameterMinRef = ref('')
const singleParameterMaxRef = ref('')
const singleParameterNotes = ref('')

// Existing state for batch mode
const results = ref({})
const overrides = ref({})
const uncertaintyValues = ref({})
const minRefValues = ref({})
const maxRefValues = ref({})
const notesValues = ref({})
const calculationInProgress = ref(false)

const allCalculatedParameters = computed(() => props.parameters.filter(p => p.parameter_id.active))

// Single parameter mode computed properties
const availableSingleParameters = computed(() => {
  return allCalculatedParameters.value
})

const selectedSingleParameterObj = computed(() => {
  return allCalculatedParameters.value.find(
    param => param.parameter_id.code === selectedSingleParameter.value
  )
})

const singleParameterInputs = computed(() => {
  if (!selectedSingleParameterObj.value) return []
  return getCalculationRequirements(selectedSingleParameterObj.value)
})

const filledInputCount = computed(() => {
  return Object.values(singleCalcInputs.value).filter(val => 
    val !== undefined && val !== null && val !== ''
  ).length
})

const getSingleCalculationStatus = () => {
  if (!selectedSingleParameterObj.value) return { complete: false, missing: [] }
  
  const required = getCalculationRequirements(selectedSingleParameterObj.value)
  const missing = required.filter(input => 
    !singleCalcInputs.value[input] || singleCalcInputs.value[input] === ''
  )
  
  return {
    complete: missing.length === 0,
    missing: missing
  }
}

const canApplySingleCalculation = computed(() => {
  if (singleParameterOverride.value) {
    return singleCalcManualValue.value && singleCalcManualValue.value.trim() !== ''
  }
  
  if (!selectedSingleParameter.value) return false
  if (!selectedSingleParameterObj.value) return false
  
  const missing = getSingleCalculationStatus().missing
  if (missing.length > 0) return false
  
  return singleCalculationResult.value !== null
})

const nextAvailableParameter = computed(() => {
  const currentIndex = availableSingleParameters.value.findIndex(
    p => p.parameter_id.code === selectedSingleParameter.value
  )
  
  if (currentIndex < availableSingleParameters.value.length - 1) {
    return availableSingleParameters.value[currentIndex + 1]
  }
  return null
})

const isWithinReferenceRange = () => {
  if (!singleCalculationResult.value) return false
  
  const result = parseFloat(singleCalculationResult.value)
  if (isNaN(result)) return false
  
  const min = singleParameterMinRef.value ? parseFloat(singleParameterMinRef.value) : null
  const max = singleParameterMaxRef.value ? parseFloat(singleParameterMaxRef.value) : null
  
  if (min !== null && max !== null) {
    return result >= min && result <= max
  } else if (min !== null) {
    return result >= min
  } else if (max !== null) {
    return result <= max
  }
  
  return true // No reference range defined
}

// Batch mode computed properties
const calculatedCount = computed(() =>
  allCalculatedParameters.value.filter(p => results.value[p.parameter_id.code]).length
)

const canSave = computed(() => {
  return calculationMode.value === 'batch' 
    ? Object.keys(results.value).filter(key => 
        results.value[key] && 
        allCalculatedParameters.value.some(p => p.parameter_id.code === key)
      ).length > 0
    : canApplySingleCalculation.value
})

// Single parameter calculation methods
const onSingleParameterChange = () => {
  if (!selectedSingleParameterObj.value) {
    resetSingleParameterFields()
    return
  }
  
  const param = selectedSingleParameterObj.value
  const paramCode = param.parameter_id.code
  const required = getCalculationRequirements(param)
  
  // Initialize inputs with existing values
  required.forEach(variable => {
    // First check if we have existing input from previous session
    if (singleCalcInputs.value[variable] !== undefined) {
      // Keep existing input value
      return
    }
    
    // Then check main results
    if (results.value[variable] !== undefined) {
      singleCalcInputs.value[variable] = results.value[variable]
    } else {
      // Initialize as empty
      singleCalcInputs.value[variable] = ''
    }
  })
  
  // Initialize result with existing value
  if (results.value[paramCode]) {
    singleCalculationResult.value = results.value[paramCode]
  } else {
    singleCalculationResult.value = null
  }
  
  // Initialize additional fields
  singleParameterUncertainty.value = uncertaintyValues.value[paramCode] || ''
  singleParameterMinRef.value = minRefValues.value[paramCode] || ''
  singleParameterMaxRef.value = maxRefValues.value[paramCode] || ''
  singleParameterNotes.value = notesValues.value[paramCode] || ''
  
  // Clear override
  singleParameterOverride.value = overrides.value[paramCode] || false
  singleCalcManualValue.value = ''
  
  // Try to calculate if all inputs are available
  if (required.every(v => singleCalcInputs.value[v] && singleCalcInputs.value[v] !== '')) {
    calculateSingleParameter()
  }
}

const onSingleInputChange = () => {
  calculateSingleParameter()
}

const onOverrideToggle = () => {
  if (singleParameterOverride.value) {
    singleCalcManualValue.value = results.value[selectedSingleParameter.value] || ''
  } else {
    singleCalcManualValue.value = ''
    calculateSingleParameter()
  }
}

const calculateSingleParameter = async () => {
  if (!selectedSingleParameterObj.value || singleParameterOverride.value) {
    singleCalculationResult.value = null
    singleCalculationError.value = ''
    return
  }
  
  const param = selectedSingleParameterObj.value
  
  // Check if all required inputs are available
  const required = getCalculationRequirements(param)
  const missing = required.filter(input => 
    !singleCalcInputs.value[input] || singleCalcInputs.value[input] === ''
  )
  
  if (missing.length > 0) {
    singleCalculationResult.value = null
    singleCalculationError.value = ''
    return
  }
  
  calculationInProgress.value = true
  singleCalculationError.value = ''
  
  try {
    const vars = {}
    required.forEach(input => {
      const value = parseFloat(singleCalcInputs.value[input])
      vars[input] = isNaN(value) ? 0 : value
    })

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
    }

    const functionBody = `return ${param.formula.expression}`
    const safeEval = new Function(...Object.keys(context), functionBody)
    const numericValue = safeEval(...Object.values(context))

    if (isNaN(numericValue) || !isFinite(numericValue)) {
      throw new Error('Invalid calculation result')
    }

    const decimalPlaces = param.formula.decimal_places || 2
    singleCalculationResult.value = parseFloat(numericValue).toFixed(decimalPlaces)
    
  } catch {
    singleCalculationError.value = `Não foi possível calcular ${param.parameter_id.code}. Reveja a fórmula e as variáveis de entrada.`
    singleCalculationResult.value = null
  } finally {
    calculationInProgress.value = false
  }
}

const applySingleCalculation = () => {
  if (!selectedSingleParameter.value) return
  
  const paramCode = selectedSingleParameter.value
  const paramObj = selectedSingleParameterObj.value
  
  // Get the final value
  const finalValue = singleParameterOverride.value 
    ? singleCalcManualValue.value 
    : singleCalculationResult.value
  
  // Create metadata for the calculation
  const metadata = {
    inputs: {},
    formula: {
      id: paramObj.formula?.id,
      expression: paramObj.formula?.expression,
      name: paramObj.formula?.name
    },
    calculated_at: new Date().toISOString(),
    calculation_method: singleParameterOverride.value ? 'manual' : 'automated',
    manual_override: singleParameterOverride.value || false
  }
  
  // Store input values in metadata
  const required = getCalculationRequirements(paramObj)
  required.forEach(variable => {
    metadata.inputs[variable] = singleCalcInputs.value[variable]
  })
  
  // Update the main results object
  results.value[paramCode] = finalValue
  
  // Update input variables in the main results
  Object.keys(singleCalcInputs.value).forEach(key => {
    if (singleCalcInputs.value[key] && singleCalcInputs.value[key] !== '') {
      results.value[key] = singleCalcInputs.value[key]
    }
  })
  
  // Update additional fields
  uncertaintyValues.value[paramCode] = singleParameterUncertainty.value || null
  minRefValues.value[paramCode] = singleParameterMinRef.value || null
  maxRefValues.value[paramCode] = singleParameterMaxRef.value || null
  notesValues.value[paramCode] = singleParameterNotes.value || null
  
  // Update overrides
  overrides.value[paramCode] = singleParameterOverride.value
  
  // Create comprehensive payload
  const comprehensivePayload = {
    results: {
      [paramCode]: finalValue,
      // Include uncertainty and reference values with specific keys
      [`${paramCode}_uncertainty_value`]: singleParameterUncertainty.value || null,
      [`${paramCode}_min_ref_value`]: singleParameterMinRef.value || null,
      [`${paramCode}_max_ref_value`]: singleParameterMaxRef.value || null,
      [`${paramCode}_insertion_notes`]: singleParameterNotes.value || null,
      // Also include input variables
      ...Object.keys(singleCalcInputs.value).reduce((acc, key) => {
        if (singleCalcInputs.value[key] && singleCalcInputs.value[key] !== '') {
          acc[key] = singleCalcInputs.value[key]
        }
        return acc
      }, {})
    },
    overrides: {
      [paramCode]: singleParameterOverride.value
    },
    metadata: {
      [paramCode]: metadata
    }
  }
  
  // Emit to parent
  emit('calculatedResults', comprehensivePayload)
  
  // Auto-select next parameter if available
  if (nextAvailableParameter.value) {
    setTimeout(() => {
      selectNextParameter()
    }, 500)
  } else {
    // Close modal after a short delay
    setTimeout(() => {
      closeModal()
    }, 1000)
  }
}

const clearSingleCalculation = () => {
  resetSingleParameterFields()
}

const resetSingleParameterFields = () => {
  selectedSingleParameter.value = ''
  singleCalcInputs.value = {}
  singleCalculationResult.value = null
  singleParameterOverride.value = false
  singleCalcManualValue.value = ''
  singleParameterUncertainty.value = ''
  singleParameterMinRef.value = ''
  singleParameterMaxRef.value = ''
  singleParameterNotes.value = ''
}

const selectNextParameter = () => {
  if (nextAvailableParameter.value) {
    selectedSingleParameter.value = nextAvailableParameter.value.parameter_id.code
    onSingleParameterChange()
  }
}

const closeModal = () => {
  emit('close')
}

// Helper methods
const getCalculationRequirements = (parameter) => {
  if (!parameter.formula) return []
  return parameter.formula.variables?.map(v => v.name) || []
}

// Initialize with existing data
watch(() => props.existingResults, (newResults) => {
  if (newResults) {
    results.value = { ...newResults }
    
    // Initialize additional fields from parameters
    props.parameters.forEach(param => {
      const code = param.parameter_id?.code
      if (code) {
        uncertaintyValues.value[code] = param.uncertainty_value || null
        minRefValues.value[code] = param.min_ref_value || null
        maxRefValues.value[code] = param.max_ref_value || null
        notesValues.value[code] = param.insertion_notes || null
        
        if (param.manual_override) { 
          overrides.value[code] = true
        }
      }
    })
  }
}, { immediate: true })

// Watch for changes in single calculation inputs
watch(singleCalcInputs, () => {
  if (selectedSingleParameter.value && !singleParameterOverride.value) {
    calculateSingleParameter()
  }
}, { deep: true })
</script>

<style scoped>
.result-entry-modal {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}
</style>
