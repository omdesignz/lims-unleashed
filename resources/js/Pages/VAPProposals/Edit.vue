<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.title') }}
          </h1>
          <div class="mt-2 flex items-center gap-3">
            <span class="text-gray-600">
              {{ $t('gestlab.general.labels.vap_proposals.edit.editing_proposal') }}:
            </span>
            <span class="inline-flex items-center rounded-full bg-blue-900 px-3 py-1 text-sm font-medium text-white">
              {{ proposal.proposal_number }}
            </span>
            <span :class="[
              'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
              proposal.status_badge.class
            ]">
              {{ proposal.status_badge.text }}
            </span>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('vap-proposals.show', proposal.id)"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.back') }}
          </Link>
          <Link 
            :href="route('vap-proposals.index')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <ListBulletIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.title') }}
          </Link>
        </div>
      </div>
      
      <!-- REVISION INFO -->
      <div v-if="!proposal.is_original" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 mt-0.5 mr-2" />
          <div>
            <p class="text-sm text-yellow-800">
              {{ $t('gestlab.general.labels.vap_proposals.edit.revision_warning') }}
            </p>
            <p class="text-xs text-yellow-700 mt-1">
              {{ $t('gestlab.general.labels.vap_proposals.edit.original_proposal') }}: {{ proposal.proposal_number }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC INFORMATION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.edit.basic_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- CLIENT SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.customer') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                  <UserIcon class="h-4 w-4 text-gray-500" />
                  <span class="text-sm font-medium text-gray-900">
                    {{ proposal.customer?.name || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
                  </span>
                </div>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.customer_locked') }}
                </p>
              </div>

              <!-- WAREHOUSE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingStorefrontIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.warehouse') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                  <BuildingStorefrontIcon class="h-4 w-4 text-gray-500" />
                  <span class="text-sm font-medium text-gray-900">
                    {{ proposal.warehouse?.address || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
                  </span>
                </div>
              </div>

              <!-- DEPARTMENT SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.department') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-lg border border-gray-200">
                  <BuildingOfficeIcon class="h-4 w-4 text-gray-500" />
                  <span class="text-sm font-medium text-gray-900">
                    {{ proposal.department?.name || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
                  </span>
                </div>
              </div>

              <!-- TEMPLATE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.template') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                    :hasError="form.errors.template_id"
                    v-model="templateSelected"
                    :options="templates.map(template => ({
                    value: template.id,
                    label: template.name,
                    name: template.name,
                    user: template.user,
                    content: template.content,
                    }))"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.edit.select_template')"
                />
                <p v-if="form.errors.template_id" class="text-xs text-red-600">
                  {{ form.errors.template_id }}
                </p>
              </div>

              <!-- LAB CODE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.labcode') }}
                </label>
                <comboboxEnhanced 
                    v-model="labcode_id"
                    :load-options="loadLabCodes"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.edit.select_labcode')"
                />
                <div v-if="loadingParameters" class="text-xs text-blue-600">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.loading_parameters') }}
                </div>
              </div>

              <!-- SERVICE LOCATION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.service_location') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.service_location"
                  type="text"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                  :class="{ 'border-red-500': form.errors.service_location }"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.edit.service_location')"
                />
                <p v-if="form.errors.service_location" class="text-xs text-red-600">
                  {{ form.errors.service_location }}
                </p>
              </div>

              <!-- TOLERANCE DAYS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClockIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.tolerance_days') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.tolerance_days"
                  type="number"
                  min="1"
                  max="365"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                  :class="{ 'border-red-500': form.errors.tolerance_days }"
                  placeholder="7"
                />
                <p v-if="form.errors.tolerance_days" class="text-xs text-red-600">
                  {{ form.errors.tolerance_days }}
                </p>
              </div>

              <!-- USE MATRIX PRICE TOGGLE -->
              <div class="flex items-center space-x-2 col-span-1 md:col-span-2 lg:col-span-1">
                <input
                  v-model="form.use_matrix_price"
                  type="checkbox"
                  id="use_matrix_price"
                  class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                />
                <label for="use_matrix_price" class="text-sm text-gray-700">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.use_matrix_price') }}
                </label>
              </div>

              <!-- WITHHOLD TAX -->
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.withhold_tax"
                  type="checkbox"
                  id="withhold_tax"
                  class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                />
                <label for="withhold_tax" class="text-sm text-gray-700">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.withhold_tax') }}
                </label>
              </div>
            </div>

            <!-- REVISION REASON -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.edit.revision_reason') }}
                <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.revision_reason"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                :class="{ 'border-red-500': form.errors.revision_reason }"
                :placeholder="$t('gestlab.general.labels.vap_proposals.edit.revision_reason_placeholder')"
              ></textarea>
              <p v-if="form.errors.revision_reason" class="text-xs text-red-600">
                {{ form.errors.revision_reason }}
              </p>
            </div>

            <!-- OBSERVATIONS -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.edit.observations') }}
              </label>
              <textarea
                v-model="form.obs"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                :placeholder="$t('gestlab.general.labels.vap_proposals.edit.observations_placeholder')"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- SERVICE ITEMS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ListBulletIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.vap_proposals.edit.items.title') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.items.length }} {{ $t('gestlab.general.buttons.items') }})
                </span>
              </h2>
              <div class="flex items-center gap-2">
                <button 
                  @click="loadParametersBasedOnLabCode(labcode_id?.value)"
                  v-if="labcode_id && !form.items.length"
                  :disabled="!labcode_id || loadingParameters"
                  class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-colors duration-200"
                >
                  <DocumentTextIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.assign_lab_code') }}
                </button>
                <button 
                  @click="addItem"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                >
                  <PlusCircleIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.add_item') }}
                </button>
              </div>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_proposals.edit.items.description') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.items.length === 0" class="p-12 text-center">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.vap_proposals.edit.items.title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_proposals.edit.items.description') }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
              <button 
                v-if="labcode_id"
                @click="loadParametersBasedOnLabCode(labcode_id?.value)"
                :disabled="!labcode_id || loadingParameters"
                class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2"
              >
                <DocumentTextIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.assign_lab_code') }}
              </button>
              <button 
                @click="addItem"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.edit.items.add_item') }}
              </button>
            </div>
          </div>

          <!-- ITEMS GRID -->
          <div v-else class="space-y-6 p-6">
            <!-- ITEM CARD TEMPLATE -->
            <div 
              v-for="(item, index) in form.items"
              :key="index"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- ITEM HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ item.item_description || $t('gestlab.general.labels.vap_proposals.edit.items.item_description') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.buttons.item') }} #{{ index + 1 }}
                        <span v-if="item.item_id" class="ml-2 text-gray-400">
                          ID: {{ item.item_id }}
                        </span>
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button 
                      @click="duplicateItem(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-600 transition-all duration-200 p-1 rounded-full hover:bg-blue-50"
                      :title="$t('gestlab.general.labels.vap_proposals.edit.items.duplicate_item')"
                    >
                      <DocumentDuplicateIcon class="h-5 w-5" />
                    </button>
                    <button 
                      @click="removeItem(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                      :title="$t('gestlab.general.labels.vap_proposals.edit.items.remove_item')"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- ITEM CONTENT -->
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                  <!-- ITEM SELECTION (Matrix or Parameter) -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.item') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced 
                      v-model="item.item_selection"
                      :load-options="form.use_matrix_price ? loadMatrixes : loadParameters"
                      :placeholder="form.use_matrix_price 
                        ? $t('gestlab.general.labels.vap_proposals.edit.items.select_matrix')
                        : $t('gestlab.general.labels.vap_proposals.edit.items.select_parameter')"
                      @update:model-value="onSelectedItem(item, index)"
                    />
                  </div>

                  <!-- ITEM DESCRIPTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.item_description') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.item_description"
                      type="text"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.item_description_placeholder')"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- STANDARD SELECTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.standard') }}
                    </label>
                    <comboboxEnhanced 
                      :hasError="item.standard_id"
                      v-model="item.standard_display"
                      :options="standardOptions"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.select_standard')"
                      @update:model-value="onStandardChange(item, index)"
                    />
                  </div>

                  <!-- UNIT SELECTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.unit') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced 
                      :hasError="item.unit_id"
                      v-model="item.unit_display"
                      :options="unitOptions"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.select_unit')"
                      @update:model-value="onUnitChange(item, index)"
                    />
                  </div>

                  <!-- QUANTITY -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.quantity') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.qty"
                      type="number"
                      min="0.01"
                      step="0.01"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- UNIT PRICE -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.unit_price') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">R$</span>
                      <input
                        v-model="item.unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-gray-300 pl-10 pr-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                        @input="calculateItemTotal(index)"
                      />
                    </div>
                  </div>

                  <!-- DISCOUNT TYPE -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}
                    </label>
                    <div class="flex gap-2">
                      <select 
                        v-model="item.discount_id"
                        class="w-1/3 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                        @change="calculateItemTotal(index)"
                      >
                        <option v-for="type in discount_categories" :key="type.value" :value="type.value">
                          {{ type.label }}
                        </option>
                      </select>
                      <div class="relative flex-1">
                        <input
                          v-model="item.discount_amount"
                          v-if="item.discount_id == 2"
                          type="number"
                          min="0"
                          step="0.01"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                          @input="calculateItemTotal(index)"
                        />
                        <input
                          v-model="item.discount_percentage"
                          v-else
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                          @input="calculateItemTotal(index)"
                        />
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">
                          {{ item.discount_id == 2 ? 'R$' : '%' }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- TAX PERCENTAGE -->
                  <div class="space-y-2" v-if="item.charge_tax">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.tax') }}
                    </label>
                    <div class="relative">
                      <input
                        v-model="item.tax_percentage"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                        @input="calculateItemTotal(index)"
                      />
                      <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">%</span>
                    </div>
                  </div>

                  <!-- CHARGE TAX CHECKBOX -->
                  <div class="flex items-center space-x-2">
                    <input
                      v-model="item.charge_tax"
                      type="checkbox"
                      :id="`charge_tax_${index}`"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                      @change="calculateItemTotal(index)"
                    />
                    <label :for="`charge_tax_${index}`" class="text-xs text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.charge_tax') }}
                    </label>
                  </div>

                  <!-- TOTAL -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.total') }}
                    </label>
                    <div class="flex items-center">
                      <div class="flex-1 bg-gray-50 rounded-lg px-3 py-2 text-sm font-semibold text-gray-900">
                        AOA {{ formatNumber(itemsWithSubTotal[index]?.total || 0) }}
                      </div>
                    </div>
                  </div>

                  <!-- OBSERVATIONS -->
                  <div class="space-y-2 col-span-full">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.observations') }}
                    </label>
                    <textarea
                      v-model="item.obs"
                      rows="2"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.observations_placeholder')"
                    ></textarea>
                  </div>
                </div>

                <!-- ITEM BREAKDOWN -->
                <div v-if="item.qty && item.unit_price" class="mt-4 pt-4 border-t border-gray-100">
                  <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
                    <div class="bg-gray-50 rounded p-2">
                      <div class="text-gray-500">Quantidade</div>
                      <div class="font-medium">{{ item.qty }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2">
                      <div class="text-gray-500">Preço Unitário</div>
                      <div class="font-medium">AOA {{ formatNumber(item.unit_price) }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2" v-if="itemsWithSubTotal[index]?.discount_amount > 0">
                      <div class="text-gray-500">Desconto</div>
                      <div class="font-medium text-green-600">-AOA {{ formatNumber(itemsWithSubTotal[index]?.discount_amount || 0) }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2" v-if="itemsWithSubTotal[index]?.tax_amount > 0">
                      <div class="text-gray-500">Taxa ({{ item.tax_percentage }}%)</div>
                      <div class="font-medium text-blue-600">AOA {{ formatNumber(itemsWithSubTotal[index]?.tax_amount || 0) }}</div>
                    </div>
                    <div class="bg-blue-50 rounded p-2">
                      <div class="text-blue-700 font-medium">Total Item</div>
                      <div class="text-blue-900 font-bold">AOA {{ formatNumber(itemsWithSubTotal[index]?.total || 0) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TOTALS SUMMARY -->
          <div v-if="form.items.length > 0" class="border-t border-gray-200 px-6 py-4 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="text-sm text-gray-600">
                {{ form.items.length }} {{ $t('gestlab.general.buttons.items') }}
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.subtotal') }}
                </div>
                <div class="text-lg font-bold text-blue-900">
                  AOA {{ formatNumber(calculateSubtotal) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}
                </div>
                <div class="text-lg font-bold text-green-600">
                  -AOA {{ formatNumber(discountTotal) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.total') }}
                </div>
                <div class="text-2xl font-bold text-blue-900">
                  AOA {{ formatNumber(proposalTotal) }}
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
            {{ $t('gestlab.general.labels.vap_proposals.edit.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !isFormValid || !form.revision_reason"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid || !form.revision_reason
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.edit.submit') }}
            </button>
            
            <button 
              @click="resetForm"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.edit.reset_changes') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.edit.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.stats.total_items') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.items.subtotal') }}</span>
                  <span class="font-semibold text-blue-900">AOA {{ formatNumber(calculateSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}</span>
                  <span class="font-semibold text-green-600">-AOA {{ formatNumber(discountTotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.tolerance_days') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.tolerance_days || 7 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.items.total') }}</span>
                  <span class="font-semibold text-blue-900">AOA {{ formatNumber(proposalTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PROPOSAL STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.edit.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.status.current') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                proposal.status_badge.class
              ]">
                {{ proposal.status_badge.text }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.status.original') }}</span>
              <span class="text-sm font-medium text-blue-900">
                {{ proposal.is_original ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.status.created_by') }}</span>
              <span class="text-sm font-medium text-blue-900">
                {{ proposal.user?.name || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.edit.status.created_at') }}</span>
              <span class="text-sm font-medium text-blue-900">
                {{ formatDate(proposal.created_at) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Validade</span>
              <span class="text-sm font-medium text-blue-900">
                {{ calculateExpiryDate }}
              </span>
            </div>
          </div>
        </div>

        <!-- SELECTED TEMPLATE PREVIEW -->
        <div v-if="selectedTemplate" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <DocumentTextIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.edit.template_preview') }}
          </h3>
          <div class="space-y-3">
            <div>
              <h4 class="text-sm font-medium text-gray-700">{{ selectedTemplate.name }}</h4>
              <p class="text-xs text-gray-500 mt-1">
                {{ $t('gestlab.general.labels.vap_proposals.show.template.created_by') }} {{ selectedTemplate.user.name }}
              </p>
            </div>
            <div class="mt-4 text-sm text-gray-600 line-clamp-4 max-h-32 overflow-y-auto">
              {{ stripHtml(selectedTemplate.content) }}
            </div>
            <button 
              @click="showTemplatePreview = true"
              class="mt-2 text-xs text-blue-900 hover:text-blue-800 font-medium"
            >
              {{ $t('gestlab.general.labels.vap_proposals.edit.view_full_template') }}
            </button>
          </div>
        </div>

        <!-- QUICK ADD ITEMS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BoltIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.edit.quick_add') }}
          </h3>
          <div class="space-y-3">
            <button 
              v-for="quickItem in quickItems"
              :key="quickItem.name"
              @click="addQuickItem(quickItem)"
              class="w-full text-left p-3 border border-gray-200 rounded-lg hover:border-blue-900 hover:bg-blue-50 transition-all duration-200"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ quickItem.name }}</div>
                  <div class="text-xs text-gray-500">{{ quickItem.description }}</div>
                </div>
                <div class="text-sm font-semibold text-blue-900">AOA {{ formatNumber(quickItem.price) }}</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.vap_proposals.edit.footer_note') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="resetForm"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
        >
          <ArrowPathIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.edit.reset_changes') }}
        </button>
        <button 
          @click="submit"
          :disabled="form.processing || !isFormValid || !form.revision_reason"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing || !isFormValid || !form.revision_reason
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
          ]"
        >
          <CheckCircleIcon class="h-5 w-5" />
          {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.edit.submit') }}
        </button>
      </div>
    </div>

    <!-- TEMPLATE PREVIEW MODAL -->
    <Modal :show="showTemplatePreview" @close="showTemplatePreview = false" max-width="4xl">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ selectedTemplate?.name }}
          </h2>
          <button @click="showTemplatePreview = false" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        <div class="prose max-w-none border border-gray-200 rounded-lg p-6 max-h-[60vh] overflow-y-auto">
          <div v-html="selectedTemplate?.content"></div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { 
  DocumentTextIcon, ArrowLeftIcon, InformationCircleIcon,
  UserIcon, BuildingStorefrontIcon, BuildingOfficeIcon,
  MapPinIcon, ClockIcon, ListBulletIcon,
  PlusCircleIcon, DocumentDuplicateIcon, TrashIcon,
  CheckCircleIcon, Cog6ToothIcon, BoltIcon, ArrowPathIcon,
  XMarkIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/modal.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'

const props = defineProps({
  proposal: Object,
  customers: Array,
  warehouses: Array,
  departments: Array,
  templates: Array,
  units: Array,
  standards: Array,
  discount_categories: {
    type: Array,
    default: () => [
      { value: 1, label: '%' },
      { value: 2, label: 'R$' }
    ]
  }
})

const form = useForm({
  template_id: props.proposal.template_id,
  service_location: props.proposal.service_location,
  tolerance_days: props.proposal.tolerance_days,
  withhold_tax: props.proposal.withhold_tax,
  use_matrix_price: props.proposal.use_matrix_price ?? true,
  obs: props.proposal.obs || '',
  revision_reason: '',
  items: [],
})

// UI State
const showTemplatePreview = ref(false)
const loadingParameters = ref(false)

// Selection refs
const templateSelected = ref(null)
const labcode_id = ref(null)

// Create refs for combobox display values
const unitOptions = computed(() => 
  props.units.map(unit => ({
    value: unit.id,
    label: unit.code || unit.description,
    data: unit
  }))
)

const standardOptions = computed(() => 
  props.standards.map(standard => ({
    value: standard.id,
    label: standard.code || standard.description,
    data: standard
  }))
)

// Initialize form with proposal data
onMounted(() => {
  // Set template selected
  if (props.proposal.template_id) {
    const template = props.templates.find(t => t.id === props.proposal.template_id)
    if (template) {
      templateSelected.value = {
        value: template.id,
        label: template.name,
        name: template.name,
        user: template.user,
        content: template.content,
      }
    }
  }
  
  // Load proposal items
  if (props.proposal.items && props.proposal.items.length > 0) {
    form.items = props.proposal.items.map(item => {
      // Find unit display value
      const unitDisplay = unitOptions.value.find(u => u.value === item.unit_id) || null
      
      // Find standard display value
      const standardDisplay = standardOptions.value.find(s => s.value === item.standard_id) || null
      
      // Create item selection object if itemable data exists
      let itemSelection = null
      if (item.itemable_type && item.itemable_id) {
        const type = item.itemable_type.includes('Matrix') ? 'matrix' : 'parameter'
        itemSelection = {
          value: item.itemable_id,
          label: item.item_description || '',
          type: type,
          price: item.unit_price || 0,
          tax_percentage: item.tax_percentage || 0,
          charge_tax: item.charge_tax ?? true,
          tax_id: item.tax_id || null,
          exemption_id: item.exemption_id || null,
          exemption_code: item.exemption_code || '',
        }
      }
      
      return {
        item_id: item.item_id || item.itemable_id,
        itemable_type: item.itemable_type,
        itemable_id: item.itemable_id,
        item_selection: itemSelection,
        item_description: item.item_description,
        standard_id: item.standard_id,
        standard_display: standardDisplay,
        unit_id: item.unit_id,
        unit_display: unitDisplay,
        qty: item.qty,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage || 0,
        discount_id: item.discount_id || 1,
        discount_amount: item.discount_amount || 0,
        total: item.total || 0,
        tax_percentage: item.tax_percentage || 0,
        tax_amount: item.tax_amount || 0,
        tax_id: item.tax_id || null,
        charge_tax: item.charge_tax ?? true,
        withhold_tax: item.withhold_tax ?? false,
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        obs: item.obs || '',
      }
    })
  }
})

// Watch for lab code changes
watch(labcode_id, (newVal) => {
  if (newVal?.value) {
    loadParametersBasedOnLabCode(newVal.value)
  }
})

// Load parameters based on lab code
const loadParametersBasedOnLabCode = async (code_id) => {
  if (!code_id) return
  
  loadingParameters.value = true
  
  try {
    const response = await fetch(`/labcodes/getCodeParameters?code_id=${code_id}&use_matrix_price=${form.use_matrix_price}`)
    const results = await response.json()
    
    // Clear existing items and add new ones from lab code
    form.items = []
    
    results.forEach(item => {
      const itemableType = form.use_matrix_price ? 'matrix' : 'parameter'
      
      const newItem = {
        item_id: item.item_id,
        itemable_type: `App\\Models\\${itemableType.charAt(0).toUpperCase() + itemableType.slice(1)}`,
        itemable_id: item.item_id,
        item_selection: { 
          value: item.item_id,
          label: item.name || item.description,
          price: item.price,
          tax_id: item.tax_id,
          charge_tax: item.charge_tax,
          tax_percentage: item.tax_percentage,
          exemption_id: item.exemption_id,
          exemption_code: item.exemption_code,
          type: itemableType
        },
        item_description: item.item_description || item.name,
        standard_id: item.standard_id || null,
        standard_display: standardOptions.value.find(s => s.value === item.standard_id) || null,
        unit_id: item.unit_id || props.units[0]?.id || null,
        unit_display: unitOptions.value.find(u => u.value === (item.unit_id || props.units[0]?.id)) || null,
        qty: item.qty || 1,
        unit_price: item.unit_price || item.price || 0,
        discount_percentage: 0,
        discount_id: 1,
        discount_amount: 0,
        total: 0,
        tax_percentage: item.tax_percentage || 0,
        tax_amount: 0,
        tax_id: item.tax_id || null,
        charge_tax: item.charge_tax || true,
        withhold_tax: item.withhold_tax || false,
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        obs: item.obs || '',
      }
      
      // Calculate initial total
      newItem.total = lineSubTotalAmount(newItem)
      newItem.tax_amount = lineTaxAmount(newItem)
      
      form.items.push(newItem)
    })
  } catch (error) {
    console.error('Error loading lab code parameters:', error)
  } finally {
    loadingParameters.value = false
  }
}

watch(templateSelected, (newVal) => form.template_id = newVal?.value || '')

// Function to load items from matrix or parameter
function loadMatrixes(query, setOptions) {
  fetch('/matrixes/getMatrix?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.description,
          price: result.fixed_price,
          tax_id: result.tax_id,
          charge_tax: result.charge_tax,
          tax_percentage: result.tax_percentage,
          exemption_id: result.exemption_id,
          exemption_code: result.exemption_code,
          type: 'matrix'
        }))
      )
    })
}

function loadParameters(query, setOptions) {
  fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
          price: result.price,
          tax_id: result.tax_id,
          charge_tax: result.charge_tax,
          tax_percentage: result.tax_percentage,
          exemption_id: result.exemption_id,
          exemption_code: result.exemption_code,
          type: 'parameter'
        }))
      )
    })
}

function loadLabCodes(query, setOptions) {
  fetch('/labcodes/getCode?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.code,
        }))
      )
    })
}

// When an item is selected from matrix/parameter
const onSelectedItem = (item, index) => {
  if (item.item_selection) {
    const selection = item.item_selection
    
    // Set polymorphic relationship
    form.items[index].itemable_type = `App\\Models\\${selection.type.charAt(0).toUpperCase() + selection.type.slice(1)}`
    form.items[index].itemable_id = selection.value
    form.items[index].item_id = selection.value
    
    // Set price and other data
    form.items[index].unit_price = selection.price || 0
    form.items[index].tax_percentage = selection.tax_percentage || 0
    form.items[index].charge_tax = selection.charge_tax ?? true
    form.items[index].tax_id = selection.tax_id || null
    form.items[index].exemption_id = selection.exemption_id || null
    form.items[index].exemption_code = selection.exemption_code || ''
    
    // Set description if empty
    if (!form.items[index].item_description) {
      form.items[index].item_description = selection.label
    }
    
    calculateItemTotal(index)
  }
}

// Handle unit selection change
const onUnitChange = (item, index) => {
  if (item.unit_display) {
    form.items[index].unit_id = item.unit_display.value
    calculateItemTotal(index)
  }
}

// Handle standard selection change
const onStandardChange = (item, index) => {
  if (item.standard_display) {
    form.items[index].standard_id = item.standard_display.value
  }
}

// Quick items for easy addition
const quickItems = ref([
  {
    name: 'Análise Química Básica',
    description: 'Análise de pH, condutividade, sólidos totais',
    qty: 1,
    unit_price: 250.00,
    unit_id: 1,
    item_description: 'Análise Química Básica - pH, condutividade, sólidos totais'
  },
  {
    name: 'Teste Microbiológico',
    description: 'Contagem total de bactérias e fungos',
    qty: 1,
    unit_price: 350.00,
    unit_id: 1,
    item_description: 'Teste Microbiológico - Contagem total'
  },
  {
    name: 'Análise de Metais Pesados',
    description: 'Análise de Pb, Cd, Hg, As',
    qty: 1,
    unit_price: 450.00,
    unit_id: 1,
    item_description: 'Análise de Metais Pesados - Pb, Cd, Hg, As'
  },
  {
    name: 'Teste de Pureza',
    description: 'Análise de pureza por HPLC',
    qty: 1,
    unit_price: 550.00,
    unit_id: 1,
    item_description: 'Teste de Pureza - Análise por HPLC'
  }
])

// Computed Properties
const selectedTemplate = computed(() => {
  return props.templates.find(t => t.id === form.template_id)
})

const hasBasicInfo = computed(() => {
  return form.template_id && form.service_location && form.revision_reason
})

const hasItems = computed(() => {
  return form.items.length > 0
})

const isFormValid = computed(() => {
  return hasBasicInfo.value && hasItems.value && form.revision_reason
})

// CALCULATION LOGIC (same as create)
const itemsWithSubTotal = computed(() => {
  return form.items.map(item => ({
    item,
    tax: lineTaxPercentage(item),
    qty: parseFloat(item.qty) || 1,
    unit_id: item.unit_id,
    standard_id: item.standard_id,
    charge_tax: lineChargeTax(item),
    withhold_tax: lineWithholdTax(item),
    unit_price: lineUnitPrice(item),
    product_price: onSelectedItemPrice(item),
    total: lineSubTotalAmount(item),
    discount_amount: lineDiscountAmount(item),
    tax_amount: lineTaxAmount(item),
    discount_percentage: item.discount_percentage || 0,
  }))
})

// Line calculation methods
const lineChargeTax = (item) => {
  return item.charge_tax ?? true
}

const lineWithholdTax = (item) => {
  return item.withhold_tax ?? false
}

const lineTaxPercentage = (item) => {
  return parseFloat(item.tax_percentage) || 0
}

const lineUnitPrice = (item) => {
  const price = onSelectedItemPrice(item)
  const discount = lineDiscountAmount(item)
  return parseFloat((price - parseFloat(discount)).toFixed(2))
}

const lineSubTotalAmount = (item) => {
  const unitPrice = lineUnitPrice(item)
  const qty = parseFloat(item.qty) || 1
  const subtotal = unitPrice * qty
  return parseFloat(subtotal.toFixed(2))
}

const lineDiscountAmount = (item) => {
  const discountId = item.discount_id || 1
  const price = onSelectedItemPrice(item)
  const qty = parseFloat(item.qty) || 1
  const totalPrice = price * qty
  
  if (discountId == 2) {
    const discount = parseFloat(item.discount_amount) || 0
    return parseFloat((discount <= totalPrice ? discount : 0).toFixed(2))
  } else if (discountId == 1) {
    const discountPercent = parseFloat(item.discount_percentage) || 0
    return parseFloat(((discountPercent <= 100 ? (discountPercent / 100) * totalPrice : 0)).toFixed(2))
  } else {
    return parseFloat(0)
  }
}

const lineTaxAmount = (item) => {
  const subTotal = lineSubTotalAmount(item)
  const taxPercent = lineTaxPercentage(item)
  const shouldChargeTax = lineChargeTax(item)
  
  return shouldChargeTax ? parseFloat((subTotal * taxPercent / 100).toFixed(2)) : 0
}

const onSelectedItemPrice = (item) => {
  return parseFloat(item.unit_price) || 0
}

// Updated computed totals
const calculateSubtotal = computed(() => {
  return parseFloat(itemsWithSubTotal.value.map(item => item.total).reduce((prev, curr) => prev + curr, 0)).toFixed(2)
})

const taxTotal = computed(() => {
  return parseFloat(itemsWithSubTotal.value.map(item => (item.tax_amount ? item.tax_amount : 0)).reduce((prev, curr) => prev + curr, 0)).toFixed(2)
})

const discountTotal = computed(() => {
  return parseFloat(itemsWithSubTotal.value.map(item => (item.discount_amount ? item.discount_amount : 0)).reduce((prev, curr) => prev + curr, 0)).toFixed(2)
})

const proposalTotal = computed(() => {
  const subtotal = parseFloat(calculateSubtotal.value)
  const tax = parseFloat(taxTotal.value)
  return (subtotal + tax).toFixed(2)
})

const calculateExpiryDate = computed(() => {
  if (!form.tolerance_days) return '-'
  const days = parseInt(form.tolerance_days)
  const date = new Date()
  date.setDate(date.getDate() + days)
  return date.toLocaleDateString('pt-BR')
})

// Methods
const addItem = () => {
  const defaultUnitId = props.units[0]?.id || null
  const defaultUnitDisplay = unitOptions.value.find(u => u.value === defaultUnitId) || null
  
  form.items.push({
    item_id: null,
    itemable_type: null,
    itemable_id: null,
    item_selection: null,
    item_description: '',
    standard_id: null,
    standard_display: null,
    unit_id: defaultUnitId,
    unit_display: defaultUnitDisplay,
    qty: 1,
    unit_price: 0,
    discount_percentage: 0,
    discount_id: 1,
    discount_amount: 0,
    total: 0,
    tax_percentage: 0,
    tax_amount: 0,
    tax_id: null,
    charge_tax: true,
    withhold_tax: false,
    exemption_id: null,
    exemption_code: '',
    obs: '',
  })
}

const duplicateItem = (index) => {
  const itemToDuplicate = { ...form.items[index] }
  itemToDuplicate.item_description = `${itemToDuplicate.item_description} (Cópia)`
  form.items.splice(index + 1, 0, itemToDuplicate)
  
  // Recalculate the new item
  const newIndex = index + 1
  form.items[newIndex].total = lineSubTotalAmount(form.items[newIndex])
  form.items[newIndex].tax_amount = lineTaxAmount(form.items[newIndex])
}

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  } else {
    alert('A proposta deve ter pelo menos um item')
  }
}

const addQuickItem = (quickItem) => {
  const defaultUnitId = quickItem.unit_id || props.units[0]?.id || null
  const defaultUnitDisplay = unitOptions.value.find(u => u.value === defaultUnitId) || null
  
  const newItem = {
    item_id: null,
    itemable_type: null,
    itemable_id: null,
    item_selection: null,
    item_description: quickItem.item_description,
    standard_id: null,
    standard_display: null,
    unit_id: defaultUnitId,
    unit_display: defaultUnitDisplay,
    qty: quickItem.qty,
    unit_price: quickItem.unit_price,
    discount_percentage: 0,
    discount_id: 1,
    discount_amount: 0,
    total: quickItem.qty * quickItem.unit_price,
    tax_percentage: 0,
    tax_amount: 0,
    charge_tax: true,
    withhold_tax: false,
  }
  form.items.push(newItem)
}

const calculateItemTotal = (index) => {
  const item = form.items[index]
  
  if (!item) return
  
  // Update total using the new calculation logic
  form.items[index].total = lineSubTotalAmount(item)
  
  // Also update tax amount if needed
  form.items[index].tax_amount = lineTaxAmount(item)
}

const formatNumber = (number) => {
  return parseFloat(number).toFixed(2).replace('.', ',')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR')
}

const stripHtml = (html) => {
  if (!html) return ''
  return html.replace(/<[^>]*>/g, '').substring(0, 200) + '...'
}

const resetForm = () => {
  if (confirm('Tem certeza que deseja descartar todas as alterações? Os dados serão restaurados para os valores originais.')) {
    // Reload the form with original data
    form.template_id = props.proposal.template_id
    form.service_location = props.proposal.service_location
    form.tolerance_days = props.proposal.tolerance_days
    form.withhold_tax = props.proposal.withhold_tax
    form.use_matrix_price = props.proposal.use_matrix_price ?? true
    form.obs = props.proposal.obs || ''
    form.revision_reason = ''
    
    // Reload items
    if (props.proposal.items && props.proposal.items.length > 0) {
      form.items = props.proposal.items.map(item => {
        const unitDisplay = unitOptions.value.find(u => u.value === item.unit_id) || null
        const standardDisplay = standardOptions.value.find(s => s.value === item.standard_id) || null
        
        let itemSelection = null
        if (item.itemable_type && item.itemable_id) {
          const type = item.itemable_type.includes('Matrix') ? 'matrix' : 'parameter'
          itemSelection = {
            value: item.itemable_id,
            label: item.item_description || '',
            type: type,
            price: item.unit_price || 0,
            tax_percentage: item.tax_percentage || 0,
            charge_tax: item.charge_tax ?? true,
            tax_id: item.tax_id || null,
            exemption_id: item.exemption_id || null,
            exemption_code: item.exemption_code || '',
          }
        }
        
        return {
          item_id: item.item_id || item.itemable_id,
          itemable_type: item.itemable_type,
          itemable_id: item.itemable_id,
          item_selection: itemSelection,
          item_description: item.item_description,
          standard_id: item.standard_id,
          standard_display: standardDisplay,
          unit_id: item.unit_id,
          unit_display: unitDisplay,
          qty: item.qty,
          unit_price: item.unit_price,
          discount_percentage: item.discount_percentage || 0,
          discount_id: item.discount_id || 1,
          discount_amount: item.discount_amount || 0,
          total: item.total || 0,
          tax_percentage: item.tax_percentage || 0,
          tax_amount: item.tax_amount || 0,
          tax_id: item.tax_id || null,
          charge_tax: item.charge_tax ?? true,
          withhold_tax: item.withhold_tax ?? false,
          exemption_id: item.exemption_id || null,
          exemption_code: item.exemption_code || '',
          obs: item.obs || '',
        }
      })
    }
  }
}

const submit = () => {
  if (!isFormValid.value) {
    alert('Por favor, preencha todos os campos obrigatórios e adicione pelo menos um item')
    return
  }

  // Transform data for submission
  const transformedData = {
    ...form.data(),
    tax: taxTotal.value,
    total: proposalTotal.value,
    sub_total: calculateSubtotal.value,
    discount: discountTotal.value,
    use_matrix_price: form.use_matrix_price,
    items: form.items.map((item, index) => {
      const itemData = itemsWithSubTotal.value[index]
      
      return {
        item_id: item.item_id || item.itemable_id,
        itemable_type: item.itemable_type,
        itemable_id: item.itemable_id,
        item_description: item.item_description || item.item_selection?.label || '',
        discount_id: item.discount_id || 1,
        discount_percentage: item.discount_percentage || 0,
        discount_amount: itemData?.discount_amount || 0,
        qty: item.qty || 1,
        obs: item.obs || '',
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        tax_id: item.tax_id || null,
        tax_percentage: item.tax_percentage || item.item_selection?.tax_percentage || 0,
        charge_tax: item.charge_tax ?? true,
        withhold_tax: item.withhold_tax ?? false,
        unit_price: itemData?.unit_price || item.unit_price || 0,
        unit_id: item.unit_id,
        standard_id: item.standard_id,
        total: itemData?.total || 0,
        tax_amount: itemData?.tax_amount || 0,
      }
    })
  }

  console.log('Submitting revision:', transformedData)

  form.transform(() => transformedData)
    .put(route('vap-proposals.update', { proposal: props.proposal.id }), {
      preserveScroll: true,
      onSuccess: () => {
        // Redirecionamento tratado pelo controller
      },
      onError: (errors) => {
        console.error('Erros no formulário:', errors)
      }
    })
}
</script>

<style scoped>
.v-motion {
  transition: all 0.3s ease;
}

.line-clamp-4 {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.bg-gradient-to-r {
  background-size: 200% 200%;
  animation: gradient 3s ease infinite;
}

@keyframes gradient {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}
</style>