<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.profiles.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.profiles.page_create_description') }}
            <span v-if="form.name" class="font-semibold text-blue-900">
              {{ form.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.parameters.length }} {{ $t('gestlab.general.labels.profiles.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- PROFILE DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.profiles.profile_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- NAME FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.name ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.profiles.name_placeholder')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- CODE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.code') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.code"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.code ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.profiles.code_placeholder')"
                />
                <p v-if="form.errors.code" class="text-xs text-red-600">
                  {{ form.errors.code }}
                </p>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.description') }}
                </label>
                <input
                  v-model="form.description"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.description ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.profiles.description_placeholder')"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.price') }}
                </label>
                <div class="relative">
                  <input
                    :value="totalProfilePrice"
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

              <!-- CATEGORY FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.category_id_1') }}
                  <span class="text-red-500">*</span>
                </label>
                <combobox
                  :hasError="form.errors.category_id"
                  v-model="form.category_id"
                  :load-options="loadCategories"
                  placeholder="Select category"
                />
                <p v-if="form.errors.category_id" class="text-xs text-red-600">
                  {{ form.errors.category_id }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- PARAMETERS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
                  {{ $t('gestlab.general.labels.profiles.parameters') }}
                  <span class="text-sm font-normal text-gray-500 ml-2">
                    ({{ form.parameters.length }} {{ $t('gestlab.general.labels.profiles.items') }})
                  </span>
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.profiles.items_tagline') }}
                </p>
              </div>
              <button 
                @click="addParameter"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.profiles.add_parameter') }}
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.parameters.length === 0" class="p-12 text-center">
            <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.profiles.empty_state.parameters_title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.profiles.empty_state.parameters_description') }}
            </p>
            <button 
              @click="addParameter"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.profiles.add_first_parameter') }}
            </button>
          </div>

          <!-- PARAMETERS GRID -->
          <div v-else class="space-y-6 p-6">
            <!-- PARAMETER CARD -->
            <div 
              v-for="(parameter, index) in form.parameters"
              :key="index"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- PARAMETER HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ parameter.parameter_id?.label || $t('gestlab.general.labels.profiles.unnamed_parameter') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.labels.profiles.parameter') }} #{{ index + 1 }}
                        <span v-if="parameter.price" class="ml-2 font-medium text-blue-900">
                          {{ parseFloat(parameter.price).toFixed(2) }} AOA
                        </span>
                      </p>
                      <p class="mt-1 flex flex-wrap gap-2 text-xs">
                        <span v-if="parameter.parameter_id?.code" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-700">
                          {{ parameter.parameter_id.code }}
                        </span>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2 py-0.5 font-medium',
                          parameter.parameter_id?.active === false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
                        ]">
                          {{ parameter.parameter_id?.active === false ? 'Inactive' : 'Active' }}
                        </span>
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="removeParameter(index)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                    :title="$t('gestlab.general.labels.profiles.remove_parameter')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <!-- PARAMETER CONTENT -->
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <!-- PARAMETER SELECTION -->
                  <div class="space-y-2 md:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.profiles.parameter_id') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <combobox
                      :hasError="form.errors[`parameters.${index}.parameter_id`]"
                      v-model="parameter.parameter_id"
                      :load-options="loadParameters"
                      placeholder="Select parameter"
                      @update:model-value="(e) => {
                        parameter.optimal_analysis_time = e.optimal_analysis_time;
                        parameter.price = e.price;
                      }"
                    />
                    <p v-if="form.errors[`parameters.${index}.parameter_id`]" class="text-xs text-red-600">
                      {{ form.errors[`parameters.${index}.parameter_id`] }}
                    </p>
                  </div>

                  <!-- UNIT -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.profiles.unit_id') }}
                    </label>
                    <combobox
                      :hasError="form.errors[`parameters.${index}.unit_id`]"
                      v-model="parameter.unit_id"
                      :load-options="loadUnits"
                      placeholder="Select unit"
                    />
                    <p v-if="form.errors[`parameters.${index}.unit_id`]" class="text-xs text-red-600">
                      {{ form.errors[`parameters.${index}.unit_id`] }}
                    </p>
                  </div>

                  <!-- OPTIMAL ANALYSIS TIME -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.profiles.optimal_analysis_time') }}
                    </label>
                    <input
                      disabled
                      v-model="parameter.optimal_analysis_time"
                      type="text"
                      class="block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 bg-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 sm:leading-6"
                    />
                  </div>

                  <!-- REFERENCE VALUES SECTION -->
                  <div class="md:col-span-2 lg:col-span-3 pt-2 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <!-- MIN REF VALUE -->
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.profiles.min_ref_value') }}
                        </label>
                        <input
                          v-model="parameter.min_ref_value"
                          type="text"
                          :class="[
                            'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                            form.errors[`parameters.${index}.min_ref_value`] ? 'ring-red-300 focus:ring-red-500' : ''
                          ]"
                          placeholder="Min"
                        />
                        <p v-if="form.errors[`parameters.${index}.min_ref_value`]" class="text-xs text-red-600">
                          {{ form.errors[`parameters.${index}.min_ref_value`] }}
                        </p>
                      </div>

                      <!-- MAX REF VALUE -->
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.profiles.max_ref_value') }}
                        </label>
                        <input
                          v-model="parameter.max_ref_value"
                          type="text"
                          :class="[
                            'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                            form.errors[`parameters.${index}.max_ref_value`] ? 'ring-red-300 focus:ring-red-500' : ''
                          ]"
                          placeholder="Max"
                        />
                        <p v-if="form.errors[`parameters.${index}.max_ref_value`]" class="text-xs text-red-600">
                          {{ form.errors[`parameters.${index}.max_ref_value`] }}
                        </p>
                      </div>

                      <!-- REF VAL ORIGIN -->
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.profiles.ref_val_origin') }}
                        </label>
                        <input
                          v-model="parameter.ref_val_origin"
                          type="text"
                          :class="[
                            'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                            form.errors[`parameters.${index}.ref_val_origin`] ? 'ring-red-300 focus:ring-red-500' : ''
                          ]"
                          placeholder="Origin"
                        />
                        <p v-if="form.errors[`parameters.${index}.ref_val_origin`]" class="text-xs text-red-600">
                          {{ form.errors[`parameters.${index}.ref_val_origin`] }}
                        </p>
                      </div>
                    </div>

                    <!-- DILUTIONS / ANALYTES (Conditional) -->
                    <div v-if="form.category_id?.value === 2" class="space-y-3">
                      <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.profiles.dilutions') }}
                        </label>
                        <button 
                          @click="() => {
                            if (!Array.isArray(parameter?.extra_data?.dilutions)) {
                              parameter.extra_data.dilutions = [];
                            }
                            parameter.extra_data.dilutions.push({
                              quantity: null,
                              ratio: null,
                            });
                          }"
                          type="button"
                          class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-900 hover:bg-blue-100 transition-colors duration-200"
                        >
                          <PlusCircleIcon class="h-4 w-4" />
                          {{ $t('gestlab.general.labels.profiles.add_dilution') }}
                        </button>
                      </div>
                      
                      <div v-if="parameter?.extra_data?.dilutions?.length" class="space-y-2">
                        <div 
                          v-for="(dilution, dilutionIndex) in parameter.extra_data.dilutions"
                          :key="dilutionIndex"
                          class="flex items-center gap-3 bg-blue-50/50 p-3 rounded-lg border border-blue-100"
                        >
                          <div class="flex-1 grid grid-cols-2 gap-3">
                            <input
                              v-model="dilution.quantity"
                              type="text"
                              placeholder="Quantity"
                              class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-blue-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900"
                            />
                            <input
                              v-model="dilution.ratio"
                              type="text"
                              placeholder="Ratio"
                              class="block w-full rounded-lg border-0 py-2 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-blue-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900"
                            />
                          </div>
                          <button
                            @click="parameter.extra_data.dilutions.splice(dilutionIndex, 1)"
                            type="button"
                            class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition-colors duration-200"
                            :title="$t('gestlab.general.labels.profiles.delete')"
                          >
                            <TrashIcon class="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                      
                      <div v-else class="text-center py-4 text-sm text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                        {{ $t('gestlab.general.labels.profiles.empty_state.dilutions') }}
                      </div>
                    </div>

                    <div v-if="form.category_id?.value === 1" class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        Analytes
                      </label>
                      <input
                        v-model="parameter.dilutions"
                        type="text"
                        :class="[
                          'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                          form.errors[`parameters.${index}.dilutions`] ? 'ring-red-300 focus:ring-red-500' : ''
                        ]"
                        placeholder="Enter analytes"
                      />
                      <p v-if="form.errors[`parameters.${index}.dilutions`]" class="text-xs text-red-600">
                        {{ form.errors[`parameters.${index}.dilutions`] }}
                      </p>
                    </div>
                  </div>

                  <!-- ADDITIONAL FIELDS -->
                  <div class="md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- PROTOCOL -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.profiles.protocol_id') }}
                      </label>
                      <combobox
                        :hasError="form.errors[`parameters.${index}.protocol_id`]"
                        v-model="parameter.protocol_id"
                        :load-options="loadProtocols"
                        placeholder="Select protocol"
                      />
                    </div>

                    <!-- STANDARD -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.profiles.standard_id') }}
                      </label>
                      <combobox
                        :hasError="form.errors[`parameters.${index}.standard_id`]"
                        v-model="parameter.standard_id"
                        :load-options="loadStandards"
                        placeholder="Select standard"
                      />
                    </div>

                    <!-- NWP -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.profiles.nwp_id') }}
                      </label>
                      <combobox
                        :hasError="form.errors[`parameters.${index}.nwp_id`]"
                        v-model="parameter.nwp_id"
                        :load-options="loadNwps"
                        placeholder="Select NWP"
                      />
                    </div>

                    <!-- CATEGORY -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.profiles.category_id') }}
                      </label>
                      <combobox
                        :hasError="form.errors[`parameters.${index}.category_id`]"
                        v-model="parameter.category_id"
                        :load-options="loadResultCategories"
                        placeholder="Select result category"
                      />
                    </div>
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
              {{ form.processing ? $t('gestlab.general.labels.profiles.processing') : $t('gestlab.general.buttons.submit') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.total_parameters') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.parameters.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.total_price') }}</span>
                  <span class="font-semibold text-blue-900">{{ totalProfilePrice }} AOA</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.category') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.category_id?.label || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Department</span>
                  <span class="font-semibold text-blue-900">{{ form.category_id?.department_name || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.status') }}</span>
                  <span :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    form.isDirty ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
                  ]">
                    {{ form.isDirty ? $t('gestlab.general.status.unsaved') : $t('gestlab.general.status.saved') }}
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
              <span class="text-sm text-gray-600">Department bound</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.category_id?.department_id ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ form.category_id?.department_name || 'Missing department' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Unique parameters</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                duplicateParameterLabels.length ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ duplicateParameterLabels.length ? 'Duplicates found' : 'Controlled' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Reference ranges</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                referenceRangeIssues.length ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ referenceRangeIssues.length ? 'Review needed' : 'Valid' }}
              </span>
            </div>
            <div v-if="profileScopeIssues.length" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Warnings</p>
              <ul class="mt-2 space-y-2 text-sm text-amber-900">
                <li v-for="issue in profileScopeIssues" :key="issue">{{ issue }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- VALIDATION STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.profiles.validation') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.required_fields') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasRequiredErrors ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ hasRequiredErrors ? $t('gestlab.general.status.incomplete') : $t('gestlab.general.status.complete') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.parameters_count') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.parameters.length === 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ form.parameters.length === 0 ? $t('gestlab.general.status.empty') : $t('gestlab.general.status.configured') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.form_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                Object.keys(form.errors).length > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ Object.keys(form.errors).length > 0 ? $t('gestlab.general.labels.errors') : $t('gestlab.general.status.valid') }}
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
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.profiles.name') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.name || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.profiles.code') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.code || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.profiles.description') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.description || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.profiles.price') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ totalProfilePrice }} AOA</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.profiles.category_id_1') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.category_id?.label || '-' }}</dd>
          </div>
          <div v-if="form.parameters.length > 0" class="pt-3 border-t border-gray-200">
            <dt class="text-sm font-medium text-gray-600 mb-2">{{ $t('gestlab.general.labels.profiles.parameters') }}</dt>
            <dd class="space-y-2">
              <div v-for="(parameter, index) in form.parameters" :key="index" class="text-sm">
                <div class="flex justify-between items-center bg-white p-2 rounded border border-gray-200">
                  <span class="text-gray-700">{{ parameter.parameter_id?.label || $t('gestlab.general.labels.profiles.unnamed_parameter') }}</span>
                  <span class="text-blue-900 font-medium">{{ parameter.price || 0 }} AOA</span>
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
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from "@/Components/combobox.vue";
import { throttle } from "lodash";
import {
  TrashIcon,
  PlusCircleIcon,
  ClipboardDocumentCheckIcon,
} from "@heroicons/vue/24/outline";
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  parameters: {
    type: Array,
    default: () => [],
  },
  analysis_categories: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  name: "",
  code: "",
  description: "",
  price: 0,
  category_id: "",
  parameters: [],
});

const totalProfilePrice = computed(() => {
  return form.parameters.reduce((acc, item) => {
    return parseFloat(parseFloat(acc) + parseFloat(item.price || 0)).toFixed(2);
  }, 0);
});

const hasRequiredErrors = computed(() => {
  return !form.name || !form.code || !form.category_id;
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

const addParameter = () => {
  form.parameters.push({
    parameter_id: "",
    unit_label: "",
    unit_id: "",
    protocol_label: "",
    protocol_id: "",
    standard_label: "",
    standard_id: "",
    nwp_label: "",
    nwp_id: "",
    count: true,
    min_ref_value: null,
    max_ref_value: null,
    ref_val_origin: null,
    dilutions: null,
    extra_data: {
      dilutions: [],
    },
    category_id: "",
    formula_id_id: "",
    category_label: "",
    optimal_analysis_time: null,
    price: 0,
  });
};

const removeParameter = (index) => {
  form.parameters.splice(index, 1);
};

function loadCategories(query, setOptions) {
  fetch("/analysiscategories/getAnalysisCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.code,
            name: result.name,
            department_id: result.department_id,
            department_name: result.department_name,
          };
        }),
      );
    });
}

function loadUnits(query, setOptions) {
  fetch("/units/getUnit?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.code,
          };
        }),
      );
    });
}

function loadProtocols(query, setOptions) {
  fetch("/protocols/getProtocol?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.code,
          };
        }),
      );
    });
}

function loadNwps(query, setOptions) {
  fetch("/nwps/getNwp?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.code,
          };
        }),
      );
    });
}

function loadStandards(query, setOptions) {
  fetch("/standards/getStandard?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.code,
          };
        }),
      );
    });
}

function loadParameters(query, setOptions) {
  fetch("/parameters/getParameter?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
            code: result.code,
            active: !!result.active,
            optimal_analysis_time: result.optimal_analysis_time,
            price: result.price,
          };
        }),
      );
    });
}

function loadResultCategories(query, setOptions) {
  fetch("/resultcategories/getResultCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

let submit = () => {
  if (!form.id) {
    form.post(route("profiles.store"), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
      },
    });
  } else {
    form.put(route("profiles.update", { profile: form.id }), {
      preserveScroll: true,
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  }
};
</script>
