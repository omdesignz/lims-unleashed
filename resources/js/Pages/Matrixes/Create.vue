<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.matrixes.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.matrixes.page_create_description') }}
            <span v-if="form.code" class="font-semibold text-blue-900">
              {{ form.code }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.profiles.length }} {{ $t('gestlab.general.labels.matrixes.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- MATRIX DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.matrixes.matrix_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- CODE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.code') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.code"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.code ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.matrixes.code_placeholder')"
                />
                <p v-if="form.errors.code" class="text-xs text-red-600">
                  {{ form.errors.code }}
                </p>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.description') }}
                </label>
                <input
                  v-model="form.description"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.description ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.matrixes.description_placeholder')"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.price') }}
                </label>
                <div class="relative">
                  <input
                    :value="totalMatrixPrice"
                    disabled
                    type="text"
                    class="block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 bg-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 sm:leading-6"
                  />
                  <span class="absolute right-3.5 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">
                    AOA
                  </span>
                </div>
                <p v-if="form.errors.price" class="text-xs text-red-600">
                  {{ form.errors.price }}
                </p>
              </div>

              <!-- FIXED PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.fixed_price') }}
                </label>
                <div class="relative">
                  <input
                    v-model="form.fixed_price"
                    type="number"
                    step="0.01"
                    :class="[
                      'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                      form.errors.fixed_price ? 'ring-red-300 focus:ring-red-500' : ''
                    ]"
                    placeholder="0.00"
                  />
                  <span class="absolute right-3.5 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">
                    AOA
                  </span>
                </div>
                <p v-if="form.errors.fixed_price" class="text-xs text-red-600">
                  {{ form.errors.fixed_price }}
                </p>
              </div>

              <!-- TAX SECTION -->
              <div class="space-y-4 md:col-span-2 lg:col-span-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <!-- CHARGE TAX TOGGLE -->
                  <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <div class="relative">
                        <input
                          v-model="form.charge_tax"
                          type="checkbox"
                          class="sr-only"
                          :id="'charge_tax'"
                        />
                        <div :class="[
                          'h-6 w-11 rounded-full transition-colors duration-200',
                          form.charge_tax ? 'bg-blue-900' : 'bg-gray-300'
                        ]">
                          <div :class="[
                            'absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform duration-200',
                            form.charge_tax ? 'transform translate-x-5' : ''
                          ]"></div>
                        </div>
                      </div>
                      <span class="text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.matrixes.charge_tax') }}
                      </span>
                    </label>
                    <p v-if="form.errors.charge_tax" class="text-xs text-red-600">
                      {{ form.errors.charge_tax }}
                    </p>
                  </div>

                  <!-- EXEMPTION OR TAX TYPE -->
                  <div class="space-y-2" v-if="!form.charge_tax">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.matrixes.exemption_id') }}
                    </label>
                    <comboboxEnhanced
                      :hasError="form.errors.exemption_id"
                      v-model="form.exemption_id"
                      :load-options="loadExemptions"
                      placeholder="Select exemption"
                    />
                    <p v-if="form.errors.exemption_id" class="text-xs text-red-600">
                      {{ form.errors.exemption_id }}
                    </p>
                  </div>

                  <div class="space-y-2" v-else>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.matrixes.tax_id') }}
                    </label>
                    <comboboxEnhanced
                      :hasError="form.errors.tax_id"
                      v-model="form.tax_id"
                      :load-options="loadTaxTypes"
                      placeholder="Select tax type"
                    />
                    <p v-if="form.errors.tax_id" class="text-xs text-red-600">
                      {{ form.errors.tax_id }}
                    </p>
                  </div>

                  <!-- WITHHOLD TAX TOGGLE -->
                  <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <div class="relative">
                        <input
                          v-model="form.withhold_tax"
                          type="checkbox"
                          class="sr-only"
                          :id="'withhold_tax'"
                        />
                        <div :class="[
                          'h-6 w-11 rounded-full transition-colors duration-200',
                          form.withhold_tax ? 'bg-blue-900' : 'bg-gray-300'
                        ]">
                          <div :class="[
                            'absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform duration-200',
                            form.withhold_tax ? 'transform translate-x-5' : ''
                          ]"></div>
                        </div>
                      </div>
                      <span class="text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.matrixes.withhold_tax') }}
                      </span>
                    </label>
                    <p v-if="form.errors.withhold_tax" class="text-xs text-red-600">
                      {{ form.errors.withhold_tax }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PROFILES SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.matrixes.profiles') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.profiles.length }} {{ $t('gestlab.general.labels.matrixes.items') }})
                </span>
              </h2>
              <button 
                @click="addProfile"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.matrixes.add_profile') }}
              </button>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.matrixes.items_tagline') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.profiles.length === 0" class="p-12 text-center">
            <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.matrixes.empty_state.profiles_title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.matrixes.empty_state.profiles_description') }}
            </p>
            <button 
              @click="addProfile"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.matrixes.add_first_profile') }}
            </button>
          </div>

          <!-- PROFILES GRID -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
            <!-- PROFILE CARD -->
            <div 
              v-for="(profile, index) in form.profiles"
              :key="index"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- PROFILE HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ profile.profile_id?.label || $t('gestlab.general.labels.matrixes.unnamed_profile') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.labels.matrixes.profile') }} #{{ index + 1 }}
                        <span v-if="profile.price" class="ml-2 font-medium text-blue-900">
                          {{ parseFloat(profile.price).toFixed(2) }} AOA
                        </span>
                      </p>
                      <p class="mt-1 flex flex-wrap gap-2 text-xs">
                        <span v-if="profile.profile_id?.code" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-700">
                          {{ profile.profile_id.code }}
                        </span>
                        <span v-if="profile.profile_id?.department_name" class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700">
                          {{ profile.profile_id.department_name }}
                        </span>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2 py-0.5 font-medium',
                          Number(profile.profile_id?.active_parameter_count ?? 0) > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                        ]">
                          {{ Number(profile.profile_id?.active_parameter_count ?? 0) }} active parameters
                        </span>
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="removeProfile(index)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                    :title="$t('gestlab.general.labels.matrixes.remove_profile')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <!-- PROFILE CONTENT -->
              <div class="p-4">
                <div class="space-y-3">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.matrixes.profile_id') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced
                      :hasError="form.errors[`profiles.${index}.profile_id`]"
                      v-model="profile.profile_id"
                      :load-options="loadProfiles"
                      placeholder="Select profile"
                      @update:model-value="(e) => {
                        profile.price = e.parameters_price || 0;
                      }"
                    />
                    <p v-if="form.errors[`profiles.${index}.profile_id`]" class="text-xs text-red-600">
                      {{ form.errors[`profiles.${index}.profile_id`] }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.actions') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="showDeleteConfirmation = true"
              :disabled="!form.isDirty || form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                !form.isDirty || form.processing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.matrixes.processing') : $t('gestlab.general.buttons.submit') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.matrixes.total_profiles') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.profiles.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.matrixes.total_price') }}</span>
                  <span class="font-semibold text-blue-900">{{ totalMatrixPrice }} AOA</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.matrixes.fixed_price') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.fixed_price }} AOA</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Departments</span>
                  <span class="font-semibold text-blue-900">{{ matrixDepartmentNames.join(', ') || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.matrixes.tax_status') }}</span>
                  <span :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    form.charge_tax ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ form.charge_tax ? $t('gestlab.general.labels.matrixes.tax_charged') : $t('gestlab.general.labels.matrixes.tax_exempt') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Scope Control</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Department alignment</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                matrixDepartmentNames.length <= 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ matrixDepartmentNames.length <= 1 ? 'Controlled' : 'Mixed departments' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Unique profiles</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                duplicateProfileLabels.length ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ duplicateProfileLabels.length ? 'Duplicates found' : 'Controlled' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Active parameter coverage</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                profilesWithoutActiveParameters.length ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ profilesWithoutActiveParameters.length ? 'Profiles need review' : 'Covered' }}
              </span>
            </div>
            <div v-if="matrixScopeIssues.length" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Warnings</p>
              <ul class="mt-2 space-y-2 text-sm text-amber-900">
                <li v-for="issue in matrixScopeIssues" :key="issue">{{ issue }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.form_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.isDirty ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
              ]">
                {{ form.isDirty ? $t('gestlab.general.status.unsaved') : $t('gestlab.general.status.saved') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.validation_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasErrors ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ hasErrors ? $t('gestlab.general.status.invalid') : $t('gestlab.general.status.valid') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.profiles_count') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.profiles.length === 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ form.profiles.length === 0 ? $t('gestlab.general.status.empty') : $t('gestlab.general.status.configured') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CONFIRMATION DIALOG -->
  <confirm-dialog 
    size="sm:max-w-2xl" 
    alignment="sm:items-start" 
    @canceled="showDeleteConfirmation = false" 
    @close="showDeleteConfirmation = false" 
    @confirmed="submit" 
    v-if="showDeleteConfirmation" 
    :title="$t('gestlab.actions.confirmation_dialog_title.default')" 
    :description="$t('gestlab.actions.confirmation_dialog_description.default')" 
    confirm="Sim" 
    cancel="Não"
  >
    <div class="mt-4">
      <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-gradient-to-r from-blue-900 to-blue-800 mb-4">
        {{ $t('gestlab.general.labels.summary') }}
      </div>
      
      <div class="bg-gray-50 rounded-lg p-4">
        <dl class="space-y-3">
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.matrixes.code') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.code || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.matrixes.description') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.description || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.matrixes.price') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ totalMatrixPrice }} AOA</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.matrixes.fixed_price') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.fixed_price }} AOA</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.matrixes.tax_status') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">
              <span :class="[
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                form.charge_tax ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ form.charge_tax 
                  ? $t('gestlab.general.labels.matrixes.tax_charged') 
                  : $t('gestlab.general.labels.matrixes.tax_exempt') 
                }}
              </span>
            </dd>
          </div>
          <div v-if="form.profiles.length > 0" class="pt-3 border-t border-gray-200">
            <dt class="text-sm font-medium text-gray-600 mb-2">{{ $t('gestlab.general.labels.matrixes.profiles') }}</dt>
            <dd class="space-y-2">
              <div v-for="(profile, index) in form.profiles" :key="index" class="text-sm">
                <div class="flex justify-between items-center bg-white p-2 rounded border border-gray-200">
                  <span class="text-gray-700">{{ profile.profile_id?.label || $t('gestlab.general.labels.matrixes.unnamed_profile') }}</span>
                  <span class="text-blue-900 font-medium">{{ profile.price || 0 }} AOA</span>
                </div>
              </div>
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </confirm-dialog>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import { throttle } from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon, Cog6ToothIcon } from "@heroicons/vue/24/outline";
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({});

const form = useForm({
  code: '',
  description: '',
  price: 0,
  fixed_price: 0,
  exemption_id: '',
  tax_id: '',
  tax_percentage: '',
  exemption: '',
  charge_tax: false,
  withhold_tax: false,
  profiles: []
});

const totalMatrixPrice = computed(() => {
  return form.profiles.reduce((acc, item) => {
    return parseFloat(parseFloat(acc) + parseFloat(item.price || 0)).toFixed(2);
  }, 0);
});

const hasErrors = computed(() => {
  return Object.keys(form.errors).length > 0;
});

const duplicateProfileLabels = computed(() => {
  const counts = new Map();

  form.profiles.forEach((profile) => {
    const profileId = profile.profile_id?.value;

    if (!profileId) {
      return;
    }

    counts.set(profileId, (counts.get(profileId) ?? 0) + 1);
  });

  return form.profiles
    .filter((profile) => {
      const profileId = profile.profile_id?.value;

      return profileId && counts.get(profileId) > 1;
    })
    .map((profile) => profile.profile_id?.label)
    .filter((label, index, labels) => label && labels.indexOf(label) === index);
});

const matrixDepartmentNames = computed(() => {
  return form.profiles
    .map((profile) => profile.profile_id?.department_name)
    .filter((name, index, names) => name && names.indexOf(name) === index);
});

const profilesWithoutActiveParameters = computed(() => {
  return form.profiles
    .filter((profile) => profile.profile_id && Number(profile.profile_id.active_parameter_count ?? 0) === 0)
    .map((profile) => profile.profile_id?.label)
    .filter(Boolean);
});

const matrixScopeIssues = computed(() => {
  const issues = [];

  if (duplicateProfileLabels.value.length) {
    issues.push(`Perfis repetidos: ${duplicateProfileLabels.value.join(", ")}.`);
  }

  if (matrixDepartmentNames.value.length > 1) {
    issues.push(`Departamentos misturados: ${matrixDepartmentNames.value.join(", ")}.`);
  }

  if (profilesWithoutActiveParameters.value.length) {
    issues.push(`Perfis sem parâmetros ativos: ${profilesWithoutActiveParameters.value.join(", ")}.`);
  }

  if (form.profiles.some((profile) => profile.profile_id && !profile.profile_id.department_id)) {
    issues.push("Todos os perfis selecionados devem estar vinculados a um departamento.");
  }

  return issues;
});

const showDeleteConfirmation = ref(false);

const addProfile = () => {
  form.profiles.push({
    profile_id: '',
    profile: '',
    price: 0,
  });
}

const removeProfile = (index) => {
  form.profiles.splice(index, 1);
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

function loadProfiles(query, setOptions) {
  fetch('/profiles/getProfile?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => {
          return {
            value: result.id,
            label: result.name,
            price: result.price,
            parameters_price: result.parameters_price,
            code: result.code,
            department_id: result.department_id,
            department_name: result.department_name,
            category_name: result.category_name,
            active_parameter_count: result.active_parameter_count,
            total_parameter_count: result.total_parameter_count,
          };
        })
      );
    });
}

let submit = () => {
  if (!form.id) {
    form.post(route('matrixes.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
    });
  } else {
    form.put(route('matrixes.update', { matrix: form.id }), {
      preserveScroll: true,
      onSuccess: () => {
        openslideover.value = false;
        form.reset()
      },
    });
  }
}
</script>
