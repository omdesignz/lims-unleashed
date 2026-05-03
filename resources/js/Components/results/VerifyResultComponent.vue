<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-white shadow-lg shadow-blue-950/20 dark:bg-blue-500 dark:text-slate-950">
            <DocumentMagnifyingGlassIcon class="h-6 w-6" />
          </div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
            Verificação de Resultados
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            Revisão e confirmação de resultados para 
            <span v-if="record || form.sample_id" class="font-semibold text-blue-900 dark:text-blue-300">
              {{ record?.sample_id?.label || record?.code || form.sample_id || 'Amostra sem referência' }}
            </span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/15 dark:text-blue-200 dark:ring-blue-400/20">
            {{ form.results?.length || 0 }} resultados
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            form.status === 'verified' ? 'bg-green-100 text-green-800 ring-green-700/10 dark:bg-green-500/15 dark:text-green-200 dark:ring-green-400/20' :
            form.status === 'rejected' ? 'bg-red-100 text-red-800 ring-red-700/10 dark:bg-red-500/15 dark:text-red-200 dark:ring-red-400/20' :
            'bg-yellow-100 text-yellow-800 ring-yellow-700/10 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-400/20'
          ]">
            {{ form.status === 'verified' ? 'Verificado' : 
               form.status === 'rejected' ? 'Rejeitado' : 'Pendente' }}
          </span>
        </div>
      </div>

      <!-- STATUS INFO -->
      <div class="mt-6 border-t border-slate-200/80 pt-6 dark:border-slate-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/70">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Amostra</p>
            <p class="mt-2 text-sm font-medium text-slate-700 dark:text-slate-200">
              {{ record?.sample_id?.label || record?.code || form.sample_id || 'N/D' }}
            </p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/70">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Resultados</p>
            <p class="mt-2 text-sm font-semibold text-blue-900 dark:text-blue-300">{{ form.results?.length || 0 }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/70">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Status</p>
            <p class="mt-2 text-sm font-medium capitalize text-slate-700 dark:text-slate-200">
              {{ form.status || 'pendente' }}
            </p>
          </div>
        </div>

        <!-- LOADING STATE -->
        <div v-if="isLoading" class="mt-4">
          <div class="flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-900"></div>
            <p class="text-sm text-blue-900 dark:text-blue-200">Carregando resultados...</p>
          </div>
        </div>

        <!-- ERROR STATE -->
        <div v-if="errorMessage && !isLoading" class="mt-4">
          <div class="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
              <ExclamationCircleIcon class="h-5 w-5 text-red-600" />
              <p class="text-sm text-red-900 dark:text-red-200">{{ errorMessage }}</p>
            </div>
            <button 
              @click="loadResultsManually"
              class="inline-flex items-center gap-2 rounded-lg bg-red-900 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-4 w-4" />
              Recarregar
            </button>
          </div>
        </div>

        <!-- EDIT MODE WARNING -->
        <div v-if="editMode" class="mt-4">
          <div class="flex items-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
            <PencilIcon class="h-5 w-5 text-blue-900" />
            <div>
              <p class="text-sm font-medium text-blue-900 dark:text-blue-200">Modo de edição activo</p>
              <p class="text-xs text-blue-700 dark:text-blue-300">(apenas um resultado pode ser editado por vez)</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LOADING STATE (FULL) -->
    <div v-if="isLoading" class="rounded-[28px] border border-slate-200 bg-white/95 p-12 text-center shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] dark:border-slate-800 dark:bg-slate-950/85">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-900"></div>
      <h3 class="mt-6 text-sm font-semibold text-slate-900 dark:text-white">Carregando resultados</h3>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Preparando dados para verificação...</p>
    </div>

    <!-- EMPTY STATE -->
    <div v-else-if="!form.results || form.results.length === 0" class="rounded-[28px] border border-slate-200 bg-white/95 p-12 text-center shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] dark:border-slate-800 dark:bg-slate-950/85">
      <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
      <h3 class="mt-6 text-sm font-semibold text-slate-900 dark:text-white">Nenhum resultado encontrado</h3>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Não há resultados para verificar nesta amostra.</p>
      <div class="mt-6">
        <button 
          @click="loadResultsManually"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <ArrowPathIcon class="h-5 w-5" />
          Tentar novamente
        </button>
      </div>
    </div>

    <!-- RESULTS SECTION -->
    <div v-else class="space-y-6">
      <!-- For each result group -->
      <template v-for="(resultGroup, groupName) in groupedResults" :key="groupName">
        <div v-if="resultGroup.length > 0" class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          
          <!-- GROUP HEADER -->
          <div class="bg-gradient-to-r px-6 py-4"
               :class="{
                 'from-purple-900 to-purple-800': groupName === 'calculated',
                 'from-blue-900 to-blue-800': groupName === 'manual',
                 'from-green-900 to-green-800': groupName === 'inputVariables'
               }">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CalculatorIcon v-if="groupName === 'calculated'" class="h-5 w-5" />
              <PencilIcon v-else-if="groupName === 'manual'" class="h-5 w-5" />
              <VariableIcon v-else class="h-5 w-5" />
              {{ 
                groupName === 'calculated' ? 'Parâmetros Calculados' :
                groupName === 'manual' ? 'Parâmetros Manuais' :
                'Variáveis de Entrada'
              }}
              <span class="text-sm font-normal ml-2">
                ({{ resultGroup.length }})
              </span>
            </h2>
          </div>
          
          <!-- RESULTS LIST -->
          <div class="divide-y divide-slate-200 dark:divide-slate-800">
            <div v-for="result in resultGroup" 
                 :key="getResultUniqueId(result)"
                 class="group p-6 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-900/60"
                 v-motion
                 :initial="{ opacity: 0, y: 10 }"
                 :enter="{ opacity: 1, y: 0 }">
              
              <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <!-- LEFT COLUMN: Parameter Info & Value -->
                <div class="flex-1 space-y-4">
                  <!-- Parameter Header -->
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">
                          {{ result.parameter_id?.code || 'N/D' }}
                        </span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">
                          {{ result.parameter_id?.name || '' }}
                        </span>
                      </div>
                      
                      <!-- Status Badges -->
                      <div class="flex items-center gap-2 mt-1">
                        <!-- Edited Badge -->
                        <span v-if="valueWasChanged(result)"
                              class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-200">
                          <PencilIcon class="h-3 w-3" />
                          Editado
                        </span>
                        
                        <!-- Verification Badges -->
                        <span v-if="result.verification_status === 'approved'"
                              class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-200">
                          <CheckIcon class="h-3 w-3" />
                          Aprovado
                        </span>
                        <span v-else-if="result.verification_status === 'rejected'"
                              class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-500/15 dark:text-red-200">
                          <XMarkIcon class="h-3 w-3" />
                          Rejeitado
                        </span>
                      </div>
                    </div>
                    
                    <!-- Edit/Calculation Button -->
                    <div>
                      <button v-if="groupName !== 'calculated'" 
                              @click="toggleEditMode(result)"
                              class="p-1.5 text-slate-400 transition-all duration-200 hover:rounded-lg hover:bg-blue-50 hover:text-blue-600 group-hover:opacity-100 dark:text-slate-500 dark:hover:bg-blue-500/10 dark:hover:text-blue-300 xl:opacity-0"
                              :title="`Editar ${result.parameter_id?.code}`">
                        <PencilIcon class="h-4 w-4" />
                      </button>
                      
                      <button v-if="groupName === 'calculated'" 
                              @click="$emit('open-calculation')"
                              class="p-1.5 text-slate-400 transition-all duration-200 hover:rounded-lg hover:bg-blue-50 hover:text-blue-600 group-hover:opacity-100 dark:text-slate-500 dark:hover:bg-blue-500/10 dark:hover:text-blue-300 xl:opacity-0"
                              :title="`Recalcular ${result.parameter_id?.code}`">
                        <CalculatorIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>

                  <!-- Value Display/Edit Section -->
                  <div class="space-y-3">
                    <!-- DISPLAY MODE -->
                    <div v-if="!isEditing(result)" class="space-y-2">
                      <!-- Current Value -->
                      <div class="flex items-baseline gap-2">
                        <span class="text-lg font-bold"
                              :class="{
                                'text-green-600 dark:text-green-300': result.verification_status === 'approved',
                                'text-red-600 dark:text-red-300': result.verification_status === 'rejected',
                                'text-blue-900 dark:text-blue-300': !result.verification_status || result.verification_status === 'pending'
                              }">
                          {{ result.verified_value || result.inserted_value || '-' }}
                        </span>
                        
                        <!-- Unit -->
                        <span v-if="result.unit_id?.code" 
                              class="text-sm font-medium text-slate-600 dark:text-slate-400">
                          {{ result.unit_id.code }}
                        </span>
                        
                        <!-- Uncertainty -->
                        <span v-if="result.uncertainty_value" 
                              class="text-sm text-slate-500 dark:text-slate-400">
                          (± {{ result.uncertainty_value }})
                        </span>
                      </div>
                      
                      <!-- Original Value (if changed) -->
                      <div v-if="valueWasChanged(result)" class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 dark:text-slate-400">Valor original:</span>
                        <span class="text-slate-600 line-through dark:text-slate-400">
                          {{ result.original_value || '-' }}
                          <span v-if="result.unit_id?.code">{{ result.unit_id.code }}</span>
                        </span>
                      </div>
                    </div>

                    <!-- EDIT MODE -->
                    <div v-if="isEditing(result)" class="space-y-3">
                      <!-- Edit Inputs -->
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Value Input -->
                        <div>
                          <label class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300">
                            Valor
                          </label>
                          <div class="relative">
                            <input 
                              :value="editedValues[getResultUniqueId(result)]?.value || result.verified_value || result.inserted_value || ''"
                              @input="handleInputChange(getResultUniqueId(result), 'value', $event.target.value)"
                              type="text"
                              class="block w-full rounded-xl border-0 bg-white py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-blue-400 sm:text-sm sm:leading-6"
                              :placeholder="`Editar ${result.parameter_id?.code}`"
                              autofocus
                            />
                            <div v-if="result.unit_id?.code" class="absolute inset-y-0 right-0 flex items-center pr-3">
                              <span class="text-sm text-slate-500 dark:text-slate-400">{{ result.unit_id.code }}</span>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Uncertainty Input -->
                        <div>
                          <label class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300">
                            Incerteza (±)
                          </label>
                          <input 
                            :value="editedValues[getResultUniqueId(result)]?.uncertainty || result.uncertainty_value || ''"
                            @input="handleInputChange(getResultUniqueId(result), 'uncertainty', $event.target.value)"
                            type="text"
                            class="block w-full rounded-xl border-0 bg-white py-2 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:placeholder:text-slate-500 dark:focus:ring-blue-400 sm:text-sm sm:leading-6"
                            placeholder="Ex.: 0,1"
                          />
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 md:col-span-2">
                          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                            <ChatBubbleBottomCenterTextIcon class="h-5 w-5 text-blue-900" />
                            Observações da Verificação
                          </h3>
                          <div class="space-y-2">
                            <textarea
                             v-model="result.verification_notes"
                              rows="3"
                              class="block w-full rounded-2xl border-slate-300 bg-white shadow-sm focus:border-blue-900 focus:ring-blue-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-400 sm:text-sm"
                              placeholder="Adicione observações gerais sobre a verificação, se necessário..."
                            />
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                              Estas observações serão registradas no histórico da amostra.
                            </p>
                          </div>
                        </div>

                      </div>
                      
                      <!-- Edit Action Buttons -->
                      <div class="flex items-center gap-3">
                        <button 
                          @click="saveEdit(getResultUniqueId(result))"
                          class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                        >
                          <CheckIcon class="h-4 w-4" />
                          Salvar
                        </button>
                        
                        <button 
                          @click="cancelEdit(getResultUniqueId(result))"
                          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition-colors duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950"
                        >
                          <XMarkIcon class="h-4 w-4" />
                          Cancelar
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Additional Information -->
                  <div class="space-y-2">
                    <!-- Reference Range -->
                    <div v-if="result.min_ref_value || result.max_ref_value"
                         class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                      <ScaleIcon class="h-3 w-3" />
                      <span>
                        Referência: 
                        <template v-if="result.min_ref_value && result.max_ref_value">
                          {{ result.min_ref_value }} - {{ result.max_ref_value }}
                        </template>
                        <template v-else-if="result.min_ref_value">
                          ≥ {{ result.min_ref_value }}
                        </template>
                        <template v-else-if="result.max_ref_value">
                          ≤ {{ result.max_ref_value }}
                        </template>
                        <span v-if="result.unit_id?.code">{{ result.unit_id.code }}</span>
                      </span>
                    </div>

                    <!-- Verification Notes -->
                    <div v-if="result.verification_status === 'rejected' && result.verification_notes"
                         class="rounded-2xl border border-red-200 bg-red-50 p-3 dark:border-red-500/20 dark:bg-red-500/10">
                      <div class="flex items-start gap-2">
                        <ExclamationCircleIcon class="h-4 w-4 text-red-600 mt-0.5" />
                        <div>
                          <p class="text-xs font-medium text-red-900 dark:text-red-200">Justificação da Rejeição</p>
                          <p class="mt-1 text-xs text-red-700 dark:text-red-300">{{ result.verification_notes }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- RIGHT COLUMN: Verification Actions -->
                <div class="flex flex-row gap-3 xl:ml-6 xl:min-w-[170px] xl:flex-col">
                  <!-- Approve Button -->
                  <button 
                    @click="toggleVerification(result, 'approved')"
                    :disabled="isEditing(result)"
                    :class="[
                      'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200',
                      result.verification_status === 'approved'
                        ? 'bg-green-100 text-green-800 ring-1 ring-green-600 hover:bg-green-200 dark:bg-green-500/15 dark:text-green-200 dark:ring-green-400/25 dark:hover:bg-green-500/20'
                        : 'bg-slate-100 text-slate-600 hover:bg-green-50 hover:text-green-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-green-500/10 dark:hover:text-green-200'
                    ]"
                    :title="`Aprovar ${result.parameter_id?.code}`"
                  >
                    <CheckIcon class="h-4 w-4" />
                    Aprovar
                  </button>
                  
                  <!-- Reject Button -->
                  <button 
                    @click="toggleVerification(result, 'rejected')"
                    :disabled="isEditing(result)"
                    :class="[
                      'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200',
                      result.verification_status === 'rejected'
                        ? 'bg-red-100 text-red-800 ring-1 ring-red-600 hover:bg-red-200 dark:bg-red-500/15 dark:text-red-200 dark:ring-red-400/25 dark:hover:bg-red-500/20'
                        : 'bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-red-500/10 dark:hover:text-red-200'
                    ]"
                    :title="`Rejeitar ${result.parameter_id?.code}`"
                  >
                    <XMarkIcon class="h-4 w-4" />
                    Rejeitar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- GLOBAL NOTES -->
      <!-- <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <ChatBubbleBottomCenterTextIcon class="h-5 w-5 text-blue-900" />
          Observações da Verificação
        </h3>
        <div class="space-y-2">
          <textarea 
            v-model="form.notes"
            rows="3"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
            placeholder="Adicione observações gerais sobre a verificação, se necessário..."
          />
          <p class="text-xs text-gray-500">
            Estas observações serão registradas no histórico da amostra.
          </p>
        </div>
      </div> -->
    </div>

    <!-- FOOTER ACTIONS -->
    <div v-if="!isLoading && form.results && form.results.length > 0" 
         class="sticky bottom-4 z-10 flex flex-col gap-4 rounded-[24px] border border-slate-200 bg-white/95 p-5 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/90 lg:flex-row lg:items-center lg:justify-between">
      <div class="text-sm text-slate-500 dark:text-slate-400">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <div :class="[
              'h-3 w-3 rounded-full',
              form.status === 'verified' ? 'bg-green-500' :
              form.status === 'rejected' ? 'bg-red-500' :
              'bg-yellow-500'
            ]"></div>
            <span class="capitalize">{{ form.status || 'pendente' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <DocumentMagnifyingGlassIcon class="h-4 w-4 text-slate-400 dark:text-slate-500" />
            <span>{{ form.results.length }} resultados</span>
          </div>
        </div>
        <div v-if="editMode" class="mt-2 flex items-center gap-1 text-xs text-blue-600 dark:text-blue-300">
          <ExclamationTriangleIcon class="h-3 w-3" />
          Finalize a edição antes de submeter
        </div>
      </div>
      
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <button 
          v-if="editMode"
          @click="closeAllEditModes"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950"
        >
          <XMarkIcon class="h-5 w-5" />
          Cancelar Edições
        </button>
        
        <button 
          @click="submitVerification"
          :disabled="form.processing || editMode"
          :class="[
            'inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing || editMode
              ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
              : 'bg-gradient-to-r from-blue-950 via-blue-900 to-blue-800 text-white hover:from-blue-900 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950'
          ]"
        >
          <CheckIcon class="h-5 w-5" />
          {{ form.processing ? 'A processar...' : 'Confirmar verificação' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { 
  CheckIcon, 
  XMarkIcon, 
  DocumentMagnifyingGlassIcon, 
  PencilIcon, 
  CalculatorIcon,
  ExclamationCircleIcon,
  ArrowPathIcon,
  VariableIcon,
  ScaleIcon,
  ExclamationTriangleIcon,
  ChatBubbleBottomCenterTextIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
    form: Object,
    record: Object,
    action: String,
    separatedResults: Object
});

const emit = defineEmits(['open-calculation', 'submit']);

// State management
const editingResults = ref({}); // Tracks which results are in edit mode: { resultId: true/false }
const editedValues = ref({}); // Stores the edited values temporarily
const editMode = ref(false); // Global edit mode flag
const isLoading = ref(true);
const errorMessage = ref('');

// Group results for better organization
const groupedResults = computed(() => {
    if (!props.separatedResults) return {};
    
    return {
        calculated: props.separatedResults.calculatedParams || [],
        inputVariables: props.separatedResults.inputVariables || [],
        manual: props.separatedResults.manualParams || []
    };
});

// Get a unique ID for each result (critical for proper state management)
const getResultUniqueId = (result) => {
    if (result.id) return result.id.toString();
    if (result.parameter_id?.id) return `param-${result.parameter_id.id}`;
    if (result.parameter_id?.value) return `param-${result.parameter_id.value}`;
    if (result.parameter_id?.code) return `code-${result.parameter_id.code}`;
    return `temp-${Math.random().toString(36).substr(2, 9)}`;
};

// Check if a specific result is in edit mode
const isEditing = (result) => {
    const resultId = getResultUniqueId(result);
    return !!editingResults.value[resultId];
};

// Initialize verification data
const initializeVerification = () => {
    if (!props.form.results || props.form.results.length === 0) {
        errorMessage.value = 'Nenhum resultado encontrado para verificação.';
        isLoading.value = false;
        return;
    }
    
    // Ensure each result has the proper structure
    props.form.results.forEach((result, index) => {
        // Ensure we have a unique ID
        if (!result._uniqueId) {
            result._uniqueId = getResultUniqueId(result);
        }
        
        // Ensure we have verification fields
        if (!result.verification_status) {
            result.verification_status = 'pending';
        }
        
        // Store original value for comparison
        if (!result.original_value) {
            result.original_value = result.inserted_value || '';
        }
        
        // Ensure verified_value is set
        if (!result.verified_value) {
            result.verified_value = result.inserted_value || '';
        }

        if (!result.uncertainty_value) {
            result.uncertainty_value = result.uncertainty_value || null;
        }
    });
    
    isLoading.value = false;
    errorMessage.value = '';
};

// Toggle edit mode for a SPECIFIC result
const toggleEditMode = (result) => {
    const resultId = getResultUniqueId(result);
    const currentlyEditing = editingResults.value[resultId];
    
    
    if (currentlyEditing) {
        // We're exiting edit mode - save changes
        saveEdit(resultId);
    } else {
        // FIRST: Close any other open edit modes
        closeAllEditModes();
        
        // NOW enter edit mode for this specific result
        editingResults.value[resultId] = true;
        
        // Store the current value for editing
        const currentValue = result.verified_value || result.inserted_value || '';
        const currentUncertainty = result.uncertainty_value || ''; // ✅ NEW: Capture uncertainty

        // Store both in editedValues
        editedValues.value[resultId] = {
            value: currentValue,
            uncertainty: currentUncertainty // ✅ NEW: Store uncertainty separately
        };
        
        // Update global edit mode flag
        editMode.value = true;
    }
};

// Close all edit modes (ensure only one result can be edited at a time)
const closeAllEditModes = () => {
    Object.keys(editingResults.value).forEach(resultId => {
        if (editingResults.value[resultId]) {
            // Save any pending changes before closing
            saveEdit(resultId);
        }
    });
    
    // Clear all edit states
    editingResults.value = {};
    editMode.value = false;
};

const saveEdit = (resultId) => {
    const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === resultId);
    
    if (resultIndex === -1) {
        console.error('Result not found for ID:', resultId);
        return;
    }

    // ✅ NEW: Read values from the structured editedValues
    const newValue = editedValues.value[resultId]?.value;
    const newUncertainty = editedValues.value[resultId]?.uncertainty;
    const result = props.form.results[resultIndex];
    
    
    // 🛑 CRITICAL FIX: ONLY update verified_value.
    // Update the result fields
    result.verified_value = newValue;
    result.uncertainty_value = newUncertainty === '' ? null : newUncertainty; // ✅ NEW: Update uncertainty
    
    // Mark as edited
    result.was_edited = true;
    result.edited_at = new Date().toISOString();
    result.edited_by = 'current_user_id'; // Replace with actual user ID
    
    // Exit edit mode for this result
    editingResults.value[resultId] = false;
    
    // Update global edit mode flag
    editMode.value = Object.values(editingResults.value).some(v => v);
};

// Handle input change in edit mode
const handleInputChange = (resultId, field, value) => {
    // Ensure the structure exists
    if (!editedValues.value[resultId]) {
        editedValues.value[resultId] = { value: '', uncertainty: '' };
    }

    if (field === 'value') {
        editedValues.value[resultId].value = value;
    } else if (field === 'uncertainty') {
        editedValues.value[resultId].uncertainty = value;
    }
};

const cancelEdit = (resultId) => {
    const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === resultId);
    
    if (resultIndex !== -1) {
        const result = props.form.results[resultIndex];
        const originalValue = result.original_value || '';
        const originalUncertainty = result.uncertainty_value || ''; // ✅ NEW: Get original uncertainty
        
        // Restore only the verified_value to the original value
        result.verified_value = originalValue;
        result.uncertainty_value = originalUncertainty; // ✅ NEW: Restore uncertainty
        
    }
    
    // Clear edited value
    delete editedValues.value[resultId];
    
    // Exit edit mode
    editingResults.value[resultId] = false;
    
    // Update global edit mode flag
    editMode.value = Object.values(editingResults.value).some(v => v);
};

// Toggle verification status for a specific result
const toggleVerification = (result, status) => {
    const resultIndex = props.form.results.findIndex(r => getResultUniqueId(r) === getResultUniqueId(result));
    
    if (resultIndex === -1) return;
    
    props.form.results[resultIndex].verification_status = status;
    props.form.results[resultIndex].verified_at = new Date().toISOString(); 
    
    if (status === 'rejected') {
        props.form.results[resultIndex].verification_notes = 
            props.form.results[resultIndex].verification_notes || 'Resultado rejeitado na verificação.';
    }
    
};

// Check if value was changed from original
const valueWasChanged = (result) => {
    const currentValue = result.verified_value || result.inserted_value || '';
    const originalValue = result.original_value || '';
    return currentValue !== originalValue;
};

// Prepare data for submission
const prepareSubmissionData = () => {
    return props.form.results.map(result => ({
        approved_by: result.approved_by,
        result_id: result.result_id,
        approved_by_id: result.approved_by_id,
        verified_by_id: result.verified_by_id,
        approved_value: result.approved_value,
        approved_date: result.approved_date,
        approval_notes: result.approval_notes,
        collection_id: result.collection_id,
        count: result.count,
        inserted_by: result.inserted_by,
        inserted_by_id: result.inserted_by_id,
        inserted_date: result.inserted_date,
        inserted_value: result.inserted_value,
        insertion_notes: result.insertion_notes,
        verified_by: result.verified_by,
        verification_status: result.verification_status,
        verified_value: result.verified_value,
        verification_notes: result.verification_notes,
        matrix_id: result.matrix_id,
        max_ref_value: result.max_ref_value,
        min_ref_value: result.min_ref_value,
        parameter_id: result.parameter_id,
        product_id: result.product_id,
        protocol_id: result.protocol_id,
        profile_id: result.profile_id,
        unit_id: result.unit_id,
        unit_label: result.unit_label,
        standard_id: result.standard_id,
        code_id: result.code_id,
        nwp_id: result.nwp_id,
        requested_counter_analysis: result.requested_counter_analysis,
        sample_id: result.sample_id,
        status: result.status,
        type_id: result.type_id,
        category_label: result.category_label,
        uncertainty_value: result.uncertainty_value,
        sumC: result.sumC,
        volume: result.volume,
        n1: result.n1,
        n2: result.n2,
        dilution: result.dilution,
        d1: result.d1,
        d2: result.d2,
        cfu1: result.cfu1,
        cfu2: result.cfu2,
        is_calculated: result.is_calculated,
        calculation_metadata: result.calculation_metadata ?? null,
        is_override: result.manual_override ?? false,
    }));
};

// Submit verification
const submitVerification = () => {
    if (isLoading.value) {
        alert('Aguarde pelo carregamento dos resultados.');
        return;
    }
    
    if (!props.form.results || props.form.results.length === 0) {
        alert('Nenhum resultado encontrado para verificar.');
        return;
    }
    
    // Save any pending edits
    closeAllEditModes();
    
    // Check for empty values
    const emptyResults = props.form.results.filter(r => 
        !r.inserted_value && r.inserted_value !== 0
    );
    
    if (emptyResults.length > 0) {
        const paramNames = emptyResults.map(r => 
            r.parameter_id?.code || r.parameter_id?.name || 'Parâmetro desconhecido'
        ).join(', ');
        
        const proceed = confirm(
            `${emptyResults.length} resultado(s) estão vazios: ${paramNames}\n` +
            'Deseja prosseguir mesmo assim?'
        );
        
        if (!proceed) return;
    }
    
    // Prepare submission data
    const submissionData = prepareSubmissionData();
    
    // Update form data
    props.form.results = submissionData;
    props.form.status = 'verified';
    props.form.performed_at = new Date().toISOString();
    
    // Emit submit event to parent
    emit('submit');
};

// Load results manually if needed
const loadResultsManually = () => {
    errorMessage.value = 'O recarregamento automático ainda não está disponível nesta vista.';
    // Implement your API call here if needed
};

// Initialize on mount
onMounted(() => {
    setTimeout(() => {
        initializeVerification();
    }, 100);
});

// Watch for changes in form results
watch(() => props.form.results, (newResults) => {
    if (newResults && newResults.length > 0) {
        initializeVerification();
    }
}, { deep: true });

// Watch for edit mode changes to update UI
watch(editMode, () => {});
</script>

<style scoped>
/* Custom scrollbar styling */
.result-group::-webkit-scrollbar {
  width: 6px;
}

.result-group::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.result-group::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.result-group::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Smooth transitions */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
</style>
