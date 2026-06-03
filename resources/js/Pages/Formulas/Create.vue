<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="h-7 w-7 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            {{ $t("gestlab.general.labels.formulas.page_title") }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t("gestlab.general.labels.formulas.page_create_description") }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.variables.length }} {{ $t("gestlab.general.labels.formulas.variables") }}
          </span>
          <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-700/10">
            {{ $t("gestlab.general.labels.formulas.categories." + form.category) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Quick Templates Section -->
    <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-semibold text-blue-900 flex items-center gap-2">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
            </svg>
            {{ $t("gestlab.general.labels.formulas.quick_templates") }}
          </h2>
          <p class="mt-1 text-sm text-blue-700">
            {{ $t("gestlab.general.labels.formulas.start_with_template") }}
          </p>
        </div>
        <span class="text-xs font-medium text-blue-900 bg-white/50 px-2 py-1 rounded">
          {{ templateFormulas.length }} {{ $t("gestlab.general.labels.formulas.templates_available") }}
        </span>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button
          v-for="(template, index) in templateFormulas"
          :key="index"
          @click="loadTemplate(template)"
          class="group relative overflow-hidden rounded-lg bg-white border border-blue-200 p-4 hover:border-blue-900 hover:shadow-md transition-all duration-200 text-left"
        >
          <div class="absolute inset-0 bg-gradient-to-r from-blue-900/5 via-transparent to-blue-900/5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
          
          <div class="relative">
            <div class="flex items-center gap-2 mb-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100 transition-colors duration-200">
                <svg class="h-4 w-4 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <h3 class="text-sm font-semibold text-gray-900 group-hover:text-blue-900 transition-colors duration-200">
                {{ template.name }}
              </h3>
            </div>
            
            <div class="space-y-2">
              <div class="text-xs font-mono text-gray-600 bg-gray-50 p-2 rounded border border-gray-100 group-hover:border-blue-200 transition-colors duration-200">
                {{ template.expression }}
              </div>
              
              <div class="flex items-center justify-between text-xs">
                <span class="text-gray-500">
                  {{ $t("gestlab.general.labels.formulas.variables") }}: {{ template.variables.map(v => v.name).join(', ') }}
                </span>
                <span class="font-medium text-blue-900">
                  {{ template.output_unit }}
                </span>
              </div>
              
              <p class="text-xs text-gray-500 line-clamp-2">
                {{ template.description }}
              </p>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Main Content Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Formula Preview & Result Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
              {{ $t("gestlab.general.labels.formulas.preview_and_result") }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="space-y-4">
              <!-- Formula Display -->
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <FormulaDisplay :formula="latexExpression" />
              </div>
              
              <div v-if="letters.length" class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $t("gestlab.general.labels.formulas.variables_detected") }}: 
                <span class="font-medium text-blue-900">{{ letters.join(', ') }}</span>
              </div>
              
              <!-- Result Display -->
              <div v-if="result !== null && result !== ''" class="mt-4">
                <div class="flex items-center justify-between bg-gradient-to-r from-gray-50 to-white rounded-lg p-4 border border-gray-200">
                  <div class="flex items-center gap-3">
                    <div :class="[
                      'flex h-10 w-10 items-center justify-center rounded-full',
                      result.includes('Error') ? 'bg-red-100' : 'bg-green-100'
                    ]">
                      <svg v-if="!result.includes('Error')" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <svg v-else class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ $t("gestlab.general.labels.formulas.result") }}
                      </div>
                      <div :class="[
                        'text-lg font-bold',
                        result.includes('Error') ? 'text-red-600' : 'text-green-600'
                      ]">
                        = {{ result }} 
                        <span v-if="!result.includes('Error') && form.output_unit" class="text-gray-600">
                          {{ form.output_unit }}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Test Actions -->
                  <div class="flex items-center gap-2">
                    <button
                      @click="quickTest"
                      type="button"
                      class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                    >
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ $t("gestlab.general.labels.formulas.quick_test") }}
                    </button>
                    <button
                      @click="evaluateFormula"
                      type="button"
                      class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
                    >
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                      {{ $t("gestlab.general.buttons.calculate") }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Formula Details Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              {{ $t("gestlab.general.labels.formulas.formula_details") }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Name -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  {{ $t("gestlab.general.labels.formulas.name") }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                  :class="form.errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
                  placeholder="Ex.: Cálculo do teor de humidade"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- Code -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  {{ $t("gestlab.general.labels.formulas.code") }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.code"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                  :class="form.errors.code ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
                  placeholder="Ex.: calculo_humidade"
                />
                <p v-if="form.errors.code" class="text-xs text-red-600">
                  {{ form.errors.code }}
                </p>
              </div>

              <!-- Category -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t("gestlab.general.labels.formulas.category") }}
                </label>
                <select
                  v-model="form.category"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                >
                  <option value="general">{{ $t("gestlab.general.labels.formulas.categories.general") }}</option>
                  <option value="microbiology">{{ $t("gestlab.general.labels.formulas.categories.microbiology") }}</option>
                  <option value="physicochemical">{{ $t("gestlab.general.labels.formulas.categories.physicochemical") }}</option>
                  <option value="custom">{{ $t("gestlab.general.labels.formulas.categories.custom") }}</option>
                </select>
              </div>

              <!-- Decimal Places -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t("gestlab.general.labels.formulas.decimal_places") }}
                </label>
                <input
                  v-model="form.decimal_places"
                  type="number"
                  min="0"
                  max="8"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                />
              </div>

              <!-- Output Unit -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t("gestlab.general.labels.formulas.output_unit") }}
                </label>
                <input
                  v-model="form.output_unit"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                  placeholder="Ex.: %, UFC/g, mg/L"
                />
              </div>
            </div>

            <!-- Formula Expression -->
            <div class="mt-6 space-y-4">
              <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t("gestlab.general.labels.formulas.expression") }}
                  <span class="text-red-500">*</span>
                </label>
                <button
                  @click="extractVariables"
                  type="button"
                  class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-900 hover:bg-blue-100 transition-colors duration-200"
                >
                  <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                  </svg>
                  {{ $t("gestlab.general.labels.formulas.extract_variables") }}
                </button>
              </div>
              
              <!-- <MathInput
                v-model:value="latexExpression"
                @update:expression="updateExpression"
                class="w-full"
                placeholder="Introduza a fórmula (ex.: a + b * c)"
              /> -->

              <input type="text" 
                    class="block w-full rounded-md border-0 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6" 
                    v-model="latexExpression" 
                    placeholder="Introduza a fórmula (ex.: a + b * c)"
                    @keyup="(e) => {
                      updateExpression(e.target.value),

                      evaluateFormula()
                    }"
              />
              
              <!-- User-friendly expression display -->
              <div v-if="form.formula_expression" class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="text-xs font-medium text-gray-700 mb-1">
                  {{ $t("gestlab.general.labels.formulas.formatted_expression") }}
                </div>
                <code class="text-sm text-blue-900 font-mono bg-white px-2 py-1 rounded border border-gray-300">
                  {{ form.formula_expression }}
                </code>
              </div>
            </div>

            <!-- Description -->
            <div class="mt-6 space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t("gestlab.general.labels.formulas.description") }}
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                placeholder="Descreva o que esta fórmula calcula e como deve ser utilizada..."
              />
            </div>
          </div>
        </div>

        <!-- Variables Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ $t("gestlab.general.labels.formulas.variables") }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.variables.length }} {{ $t("gestlab.general.labels.formulas.items") }})
                </span>
              </h2>
              <button 
                @click="addVariable"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t("gestlab.general.labels.formulas.add_variable") }}
              </button>
            </div>
          </div>

          <!-- Variables Grid -->
          <div v-if="form.variables.length > 0" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
              <div 
                v-for="(variable, index) in enhancedVariables"
                :key="index"
                class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
                v-motion
                :initial="{ opacity: 0, y: 20 }"
                :enter="{ opacity: 1, y: 0 }"
                :delay="index * 50"
              >
                <!-- Variable Header -->
                <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                        {{ String.fromCharCode(65 + index) }}
                      </div>
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                          {{ variable.name || $t("gestlab.general.labels.formulas.unnamed_variable") }}
                        </h3>
                        <p class="text-xs text-gray-500">
                          {{ $t("gestlab.general.labels.formulas.variable") }} #{{ index + 1 }}
                        </p>
                      </div>
                    </div>
                    <button 
                      @click="removeVariable(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                      :title="$t('gestlab.general.labels.formulas.remove_variable')"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </div>
                
                <!-- Variable Content -->
                <div class="p-4 space-y-4">
                  <!-- Variable Name -->
                  <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t("gestlab.general.labels.formulas.name") }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="variable.name"
                      type="text"
                      class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                      placeholder="Ex.: temperatura"
                    />
                  </div>

                  <!-- Variable Label -->
                  <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t("gestlab.general.labels.formulas.label") }}
                    </label>
                    <input
                      v-model="variable.label"
                      type="text"
                      class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                      placeholder="Ex.: Temperatura"
                    />
                  </div>

                  <!-- Type and Unit -->
                  <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                      <label class="block text-xs font-medium text-gray-700">
                        {{ $t("gestlab.general.labels.formulas.type") }}
                      </label>
                      <select
                        v-model="variable.type"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                      >
                        <option value="number">{{ $t("gestlab.general.labels.formulas.types.number") }}</option>
                        <option value="integer">{{ $t("gestlab.general.labels.formulas.types.integer") }}</option>
                        <option value="decimal">{{ $t("gestlab.general.labels.formulas.types.decimal") }}</option>
                      </select>
                    </div>
                    <div class="space-y-1">
                      <label class="block text-xs font-medium text-gray-700">
                        {{ $t("gestlab.general.labels.formulas.unit") }}
                      </label>
                      <input
                        v-model="variable.unit"
                        type="text"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                        placeholder="Ex.: °C, mg, mL"
                      />
                    </div>
                  </div>

                  <!-- Test Value -->
                  <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t("gestlab.general.labels.formulas.test_value") }}
                    </label>
                    <div class="relative">
                      <input
                        v-model="variable.value"
                        type="number"
                        step="any"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200 pr-20"
                        placeholder="Introduza um valor para teste"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <span class="text-xs text-gray-500">
                          {{ variable.unit }}
                        </span>
                      </div>
                    </div>
                    <div v-if="variable.value" class="flex items-center gap-2 text-xs text-gray-500">
                      <div class="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
                        <div 
                          class="h-full bg-blue-900 transition-all duration-500"
                          :style="{ width: `${Math.min(Math.abs(variable.value) / 100 * 100, 100)}%` }"
                        ></div>
                      </div>
                      <span>{{ variable.value }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t("gestlab.general.labels.formulas.no_variables") }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t("gestlab.general.labels.formulas.add_variables_description") }}
            </p>
          </div>
        </div>
      </div>

      <!-- Right Column (1/3 width) -->
      <div class="space-y-6">
        <!-- Actions Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t("gestlab.general.labels.formulas.actions") }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !form.isDirty"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !form.isDirty
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ form.processing ? $t("gestlab.general.buttons.processing") : $t("gestlab.general.buttons.submit") }}
            </button>
            
            <!-- Active Status -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center gap-2">
                <div :class="[
                  'h-2 w-2 rounded-full',
                  form.is_active ? 'bg-green-500' : 'bg-gray-400'
                ]"></div>
                <span class="text-sm font-medium text-gray-900">
                  {{ $t("gestlab.general.labels.formulas.active") }}
                </span>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-900"></div>
              </label>
            </div>
            
            <!-- Quick Stats -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t("gestlab.general.labels.formulas.quick_stats") }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t("gestlab.general.labels.formulas.total_variables") }}</span>
                  <span class="font-semibold text-blue-900">{{ form.variables.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t("gestlab.general.labels.formulas.decimal_places") }}</span>
                  <span class="font-semibold text-blue-900">{{ form.decimal_places }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t("gestlab.general.labels.formulas.output_unit") }}</span>
                  <span class="font-semibold text-blue-900">{{ form.output_unit || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t("gestlab.general.labels.formulas.category") }}</span>
                  <span class="font-semibold text-blue-900">{{ $t("gestlab.general.labels.formulas.categories." + form.category) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Help Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $t("gestlab.general.labels.formulas.help") }}
          </h3>
          <div class="space-y-3">
            <div class="bg-blue-50 rounded-lg p-3">
              <div class="flex items-start gap-2">
                <svg class="h-4 w-4 text-blue-900 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-blue-900">
                  {{ $t("gestlab.general.labels.formulas.supported_functions") }}: sqrt(), log(), exp(), abs(), round()
                </p>
              </div>
            </div>
            <div class="text-xs text-gray-600 space-y-2">
              <p>{{ $t("gestlab.general.labels.formulas.expression_tips") }}</p>
              <ul class="list-disc pl-4 space-y-1">
                <li>{{ $t("gestlab.general.labels.formulas.tip_1") }}</li>
                <li>{{ $t("gestlab.general.labels.formulas.tip_2") }}</li>
                <li>{{ $t("gestlab.general.labels.formulas.tip_3") }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { throttle } from "lodash";
import {
  TrashIcon,
  PlusCircleIcon,
  ClipboardDocumentCheckIcon,
} from "@heroicons/vue/24/outline";
import { trans } from "laravel-vue-i18n";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import FormulaDisplay from "@/Components/formula-display.vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({});

const form = useForm({
  name: "",
  code: "",
  category: "general",
  expression: null,
  formula_expression: "",
  variables: [],
  output_unit: "",
  decimal_places: 2,
  description: "",
  is_active: true,
});

const latexExpression = ref("");
const result = ref("");
const letters = ref([]);
const showDeleteConfirmation = ref(false);

// Template formulas
const templateFormulas = [
  {
    name: "Moisture Content Calculation",
    code: "moisture_content",
    category: "physicochemical",
    expression: "((mp + ma) - mp_a) * 100 / ma",
    formula_expression: "(({mp} + {ma}) - {mp_a}) * 100 / {ma}",
    variables: [
      { 
        name: "mp", 
        label: "Mass of Pan + Sample (before drying)", 
        unit: "g", 
        type: "number", 
        value: "44.35628"  // Exact value from Excel row 3
      },
      { 
        name: "ma", 
        label: "Mass of Sample", 
        unit: "g", 
        type: "number", 
        value: "5.00725"   // Exact value from Excel row 3
      },
      { 
        name: "mp_a", 
        label: "Mass of Pan + Sample (after drying)", 
        unit: "g", 
        type: "number", 
        value: "48.7779"   // Exact value from Excel row 3
      }
    ],
    output_unit: "%",
    decimal_places: 2,
    description: "Calculate moisture content percentage: ((mp + ma) - mpₐ) × 100 ÷ ma"
  },
  {
    name: "Microbiology Colony Count", 
    code: "colony_count",
    category: "microbiology",
    expression: "(colony1 + colony2) / 1.1 * dilution",
    formula_expression: "({colony1} + {colony2}) / 1.1 * {dilution}",
    variables: [
      { 
        name: "colony1", 
        label: "Colonies Dilution -2", 
        unit: "CFU", 
        type: "number", 
        value: "215" 
      },
      { 
        name: "colony2", 
        label: "Colonies Dilution -3", 
        unit: "CFU", 
        type: "number", 
        value: "14" 
      },
      { 
        name: "dilution", 
        label: "Dilution Factor", 
        unit: "ml/g",
        type: "number", 
        value: "100" 
      }
    ],
    output_unit: "CFU/g",
    decimal_places: 0,
    description: "Calculate total colony count from multiple dilution counts"
  }
];

// Enhanced variables with better structure
const enhancedVariables = computed(() => {
  return form.variables.map(variable => ({
    ...variable,
    label: variable.label || variable.name,
    unit: variable.unit || "",
    type: variable.type || "number"
  }));
});

onMounted(() => {
  latexExpression.value = form.expression;
});

// FIXED: Extract variables only when expression changes significantly
const extractVariablesFromExpression = (expression) => {
  if (!expression) return [];
  
  // Extract variable names (words that start with a letter)
  const variablePattern = /[a-zA-Z_][a-zA-Z0-9_]*/g;
  const matches = expression.match(variablePattern) || [];
  
  // Remove duplicates and Math functions
  const mathFunctions = ['sqrt', 'log', 'log10', 'exp', 'abs', 'round', 'ceil', 'floor', 'max', 'min', 'pow', 'PI', 'E'];
  const uniqueVars = [...new Set(matches)].filter(v => 
    !mathFunctions.includes(v) && 
    !v.match(/^[0-9]/) // Don't include things that start with numbers
  );
  
  return uniqueVars;
};

// NEW: Manual variable extraction button
const extractVariables = () => {
  if (form.expression) {
    const extractedVars = extractVariablesFromExpression(form.expression);
    letters.value = extractedVars;
    
    extractedVars.forEach((variableName) => {
      if (!form.variables.some(v => v.name === variableName)) {
        form.variables.push({
          name: variableName,
          label: variableName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
          value: "",
          unit: "",
          type: "number",
          description: "",
        });
      }
    });
    
    alert(`Foram encontradas ${extractedVars.length} variáveis: ${extractedVars.join(', ')}`);
  } else {
    alert("Introduza primeiro a expressão da fórmula.");
  }
};


// Safe JavaScript evaluation
const evaluateFormula = () => {
  // Build variables object with full precision
  const vars = {};
  form.variables.forEach((variable) => {
    if (variable.name && variable.value !== "" && variable.value !== null) {
      // Keep as string initially to preserve precision, convert to number only for calculation
      const numValue = parseFloat(variable.value);
      vars[variable.name] = isNaN(numValue) ? 0 : numValue;
    } else {
      vars[variable.name] = 0;
    }
  });

  try {
    // Validate expression
    if (!form.expression || form.expression.trim() === "") {
      throw new Error("Introduza uma expressão de fórmula.");
    }

    // Create safe evaluation context
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
    };

    // Create safe evaluation function
    const functionBody = `return ${form.expression}`;
    const safeEval = new Function(...Object.keys(context), functionBody);
    const numericValue = safeEval(...Object.values(context));
    
    if (isNaN(numericValue)) {
      throw new Error("Calculation resulted in NaN - check variable values");
    }
    
    if (!isFinite(numericValue)) {
      throw new Error("Calculation resulted in infinite value - check for division by zero");
    }
    
    // Format result with specified decimal places
    result.value = parseFloat(numericValue).toFixed(form.decimal_places);
  } catch (error) {
    result.value = "Error: " + error.message;
  }
};

// Quick test with random values
const quickTest = () => {
  form.variables.forEach(variable => {
    if (variable.type === "number" && variable.name) {
      // Generate reasonable test values based on context
      let testValue;
      if (variable.name.includes('colony')) {
        testValue = Math.floor(Math.random() * 200) + 10; // 10-210 colonies
      } else if (variable.name.includes('mass') || variable.name.includes('mp') || variable.name.includes('ma')) {
        testValue = (Math.random() * 50 + 5).toFixed(3); // 5-55g with 3 decimals
      } else {
        testValue = (Math.random() * 100).toFixed(2); // Generic 0-100 with 2 decimals
      }
      variable.value = testValue;
    }
  });
  evaluateFormula();
};

// Load template
const loadTemplate = (template) => {
  form.reset();
  
  form.name = template.name;
  form.code = template.code;
  form.category = template.category;
  form.expression = template.expression;
  form.formula_expression = template.formula_expression;
  form.variables = JSON.parse(JSON.stringify(template.variables));
  form.output_unit = template.output_unit;
  form.decimal_places = template.decimal_places;
  form.description = template.description;
  
  latexExpression.value = template.expression;
  letters.value = template.variables.map(v => v.name);
  
  // Auto-evaluate with template values
  setTimeout(() => {
    evaluateFormula();
  }, 100);
};

// FIXED: Only update variables when expression meaningfully changes
const updateExpression = (expression) => {
  form.expression = expression;
  // Auto-generate user-friendly expression
  form.formula_expression = expression.replace(/([a-zA-Z_][a-zA-Z0-9_]*)/g, '{$1}');
  
  // Only extract variables when we have a substantial expression
  if (expression && expression.length > 2) {
    const extractedVars = extractVariablesFromExpression(expression);
    letters.value = extractedVars;
    
    // Only add new variables that don't exist
    extractedVars.forEach((variableName) => {
      if (!form.variables.some(v => v.name === variableName)) {
        form.variables.push({
          name: variableName,
          label: variableName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()), // Better default label
          value: "",
          unit: "",
          type: "number",
          description: "",
        });
      }
    });
    
    // Remove variables that are no longer in the expression
    form.variables = form.variables.filter(v => 
      extractedVars.includes(v.name) || v.name === "" // Keep empty ones for manual entry
    );
  }
};

// Handle detected letters from MathInput
const handleLetters = (extractedLetters) => {
  letters.value = extractedLetters;
  
  extractedLetters.forEach((letter) => {
    if(letter && !form.variables.some(item => item.name === letter)){
      form.variables.push({
        name: letter,
        label: letter.toUpperCase(),
        value: "",
        unit: "",
        type: "number",
        description: "",
      });
    }
  });
};

// Add variable manually
const addVariable = () => {
  form.variables.push({
    name: "",
    label: "",
    value: "",
    unit: "",
    type: "number",
    description: "",
  });
};

// Remove variable
const removeVariable = (index) => {
  form.variables.splice(index, 1);
};

// Auto-generate code from name
watch(() => form.name, (newName) => {
  if (newName && !form.code) {
    form.code = newName
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/(^_|_$)/g, '');
  }
});

// Validate before submission
const validateFormula = () => {
  if (!form.name || !form.code || !form.expression) {
    alert("Preencha todos os campos obrigatórios: nome, código e expressão.");
    return false;
  }

  if (form.variables.length === 0) {
    alert("Defina pelo menos uma variável para a fórmula.");
    return false;
  }

  // Test evaluation
  try {
    const testVars = {};
    form.variables.forEach(variable => {
      testVars[variable.name] = 1;
    });

    const context = { ...testVars, Math };
    const functionBody = `return ${form.expression}`;
    new Function(...Object.keys(context), functionBody)(...Object.values(context));
    
    return true;
  } catch (error) {
    alert("A validação da fórmula falhou: " + error.message);
    return false;
  }
};

// Submit form
let submit = async () => {
  const isValid = await validateFormula();
  if (!isValid) return;

  const submissionData = {
    ...form.data(),
    variables: enhancedVariables.value,
  };

  if (!form.id) {
    form.post(route("formulas.store"), {
      data: submissionData,
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        result.value = "";
      },
    });
  } else {
    form.put(route("formulas.update", { formula: form.id }), {
      data: submissionData,
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        result.value = "";
      },
    });
  }
};
</script>
