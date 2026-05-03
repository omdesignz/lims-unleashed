<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentPlusIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.create.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposals.create.description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ nextProposalNo }}
          </span>
          <Link 
            :href="route('vap-proposals.index')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.back') }}
          </Link>
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
              {{ $t('gestlab.general.labels.vap_proposals.create.basic_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- CLIENT SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.customer') }}
                  <span class="text-red-500">*</span>
                </label>
                <!-- <v-select
                  v-model="form.customer_id"
                  :options="customers"
                  label="name"
                  :reduce="customer => customer.id"
                  :clearable="false"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_customer')"
                  :searchable="true"
                  :class="{ 'border-red-500': form.errors.customer_id }"
                  class="v-select-custom"
                /> -->

                <comboboxEnhanced 
                    :hasError="form.errors.customer_id"
                    v-model="selectedCustomer"
                    :options="customers.map(customer => ({
                    value: customer.id,
                    label: customer.name,
                    }))"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_customer')"
                />
                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>

              <!-- WAREHOUSE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingStorefrontIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.warehouse') }}
                  <span class="text-red-500">*</span>
                </label>
                <!-- <v-select
                  v-model="form.warehouse_id"
                  :options="warehouses"
                  label="name"
                  :reduce="warehouse => warehouse.id"
                  :clearable="false"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_warehouse')"
                  :class="{ 'border-red-500': form.errors.warehouse_id }"
                  class="v-select-custom"
                /> -->

                <comboboxEnhanced 
                    :disableInput="!form.customer_id"
                    :hasError="form.errors.warehouse_id" 
                    v-model="selectedWarehouse" 
                    :load-options="loadWarehouses"
                    :loading="loadingWarehouses"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_warehouse')"
                />

                <!-- Loading indicator -->
                <div v-if="loadingWarehouses" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    <svg class="animate-spin h-5 w-5 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.warehouse_id }}
                </p>
              </div>

              <!-- DEPARTMENT SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.department') }}
                  <span class="text-red-500">*</span>
                </label>
                <!-- <v-select
                  v-model="form.department_id"
                  :options="departments"
                  label="name"
                  :reduce="department => department.id"
                  :clearable="false"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_department')"
                  :class="{ 'border-red-500': form.errors.department_id }"
                  class="v-select-custom"
                /> -->

                <comboboxEnhanced 
                    :hasError="form.errors.department_id"
                    v-model="selectedDepartment"
                    :options="departments.map(department => ({
                    value: department.id,
                    label: department.name,
                    }))"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_department')"
                />

                <p v-if="form.errors.department_id" class="text-xs text-red-600">
                  {{ form.errors.department_id }}
                </p>
              </div>

              <!-- TEMPLATE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.template') }}
                  <span class="text-red-500">*</span>
                </label>
                <!-- <v-select
                  v-model="form.template_id"
                  :options="templates"
                  label="name"
                  :reduce="template => template.id"
                  :clearable="false"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_template')"
                  :class="{ 'border-red-500': form.errors.template_id }"
                  class="v-select-custom"
                /> -->

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
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_template')"
                />

                <p v-if="form.errors.template_id" class="text-xs text-red-600">
                  {{ form.errors.template_id }}
                </p>
              </div>

              <!-- SERVICE LOCATION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.service_location') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.service_location"
                  type="text"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                  :class="{ 'border-red-500': form.errors.service_location }"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.service_location')"
                />
                <p v-if="form.errors.service_location" class="text-xs text-red-600">
                  {{ form.errors.service_location }}
                </p>
              </div>

              <!-- TOLERANCE DAYS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClockIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.tolerance_days') }}
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

              <!-- WITHHOLD TAX -->
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.withhold_tax"
                  type="checkbox"
                  id="withhold_tax"
                  class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                />
                <label for="withhold_tax" class="text-sm text-gray-700">
                  {{ $t('gestlab.general.labels.vap_proposals.create.withhold_tax') }}
                </label>
              </div>
            </div>

            <!-- OBSERVATIONS -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.create.observations') }}
              </label>
              <textarea
                v-model="form.obs"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                :placeholder="$t('gestlab.general.labels.vap_proposals.create.observations_placeholder')"
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
                {{ $t('gestlab.general.labels.vap_proposals.create.items.title') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.items.length }} {{ $t('gestlab.general.buttons.items') }})
                </span>
              </h2>
              <button 
                @click="addItem"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.create.items.add_item') }}
              </button>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_proposals.create.items.description') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.items.length === 0" class="p-12 text-center">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.vap_proposals.create.items.title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_proposals.create.items.description') }}
            </p>
            <button 
              @click="addItem"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.create.items.add_item') }}
            </button>
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
                        {{ item.item_description || $t('gestlab.general.labels.vap_proposals.create.items.item_description') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.buttons.item') }} #{{ index + 1 }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button 
                      @click="duplicateItem(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-600 transition-all duration-200 p-1 rounded-full hover:bg-blue-50"
                      :title="$t('gestlab.general.labels.vap_proposals.create.items.duplicate_item')"
                    >
                      <DocumentDuplicateIcon class="h-5 w-5" />
                    </button>
                    <button 
                      @click="removeItem(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                      :title="$t('gestlab.general.labels.vap_proposals.create.items.remove_item')"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- ITEM CONTENT -->
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                  <!-- ITEM DESCRIPTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.item_description') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.item_description"
                      type="text"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.item_description_placeholder')"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- STANDARD SELECTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.standard') }}
                    </label>
                    <!-- <v-select
                      v-model="item.standard_id"
                      :options="standards"
                      label="name"
                      :reduce="standard => standard.id"
                      :clearable="true"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.select_standard')"
                      class="v-select-custom-sm"
                    /> -->
                    <comboboxEnhanced 
                        :hasError="item.standard_id"
                        v-model="item.standard_id"
                        :options="standards.map(standard => ({
                        value: standard.id,
                        label: standard?.code,
                        }))"
                        :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.select_standard')"
                    />
                  </div>

                  <!-- UNIT SELECTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.unit') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <!-- <v-select
                      v-model="item.unit_id"
                      :options="units"
                      label="name"
                      :reduce="unit => unit.id"
                      :clearable="false"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.select_unit')"
                      class="v-select-custom-sm"
                      @input="calculateItemTotal(index)"
                    /> -->

                    <comboboxEnhanced 
                        :hasError="item.unit_id"
                        v-model="item.unit_id"
                        :options="units.map(unit => ({
                        value: unit.id,
                        label: unit?.code,
                        }))"
                        :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.select_unit')"
                    />
                  </div>

                  <!-- QUANTITY -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.quantity') }}
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
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.unit_price') }}
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

                  <!-- DISCOUNT PERCENTAGE -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.discount') }}
                    </label>
                    <div class="relative">
                      <input
                        v-model="item.discount_percentage"
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

                  <!-- TOTAL -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.total') }}
                    </label>
                    <div class="flex items-center">
                      <div class="flex-1 bg-gray-50 rounded-lg px-3 py-2 text-sm font-semibold text-gray-900">
                        AOA {{ formatNumber(item.total || 0) }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ITEM BREAKDOWN -->
                <div v-if="item.qty && item.unit_price" class="mt-4 pt-4 border-t border-gray-100">
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                    <div class="bg-gray-50 rounded p-2">
                      <div class="text-gray-500">Quantidade</div>
                      <div class="font-medium">{{ item.qty }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2">
                      <div class="text-gray-500">Preço Unitário</div>
                      <div class="font-medium">AOA {{ formatNumber(item.unit_price) }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2" v-if="item.discount_percentage > 0">
                      <div class="text-gray-500">Desconto ({{ item.discount_percentage }}%)</div>
                      <div class="font-medium text-green-600">-AOA {{ formatNumber(calculateDiscountAmount(item)) }}</div>
                    </div>
                    <div class="bg-blue-50 rounded p-2">
                      <div class="text-blue-700 font-medium">Total Item</div>
                      <div class="text-blue-900 font-bold">AOA {{ formatNumber(item.total || 0) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TOTALS SUMMARY -->
          <div v-if="form.items.length > 0" class="border-t border-gray-200 px-6 py-4 bg-gray-50">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-600">
                {{ form.items.length }} {{ $t('gestlab.general.buttons.items') }}
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.vap_proposals.create.items.subtotal') }}
                </div>
                <div class="text-2xl font-bold text-blue-900">
                  AOA {{ formatNumber(calculateSubtotal) }}
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
            {{ $t('gestlab.general.labels.vap_proposals.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.create.submit') }}
            </button>
            
            <button 
              @click="saveDraft"
              :disabled="form.processing"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.create.save_draft') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.stats.total_items') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.create.items.subtotal') }}</span>
                  <span class="font-semibold text-blue-900">AOA {{ formatNumber(calculateSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.create.tolerance_days') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.tolerance_days || 7 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.create.basic_info') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasBasicInfo ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ hasBasicInfo ? $t('gestlab.general.labels.vap_proposals.status.complete') : $t('gestlab.general.labels.vap_proposals.status.incomplete') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.create.items.title') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasItems ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ hasItems ? $t('gestlab.general.labels.vap_proposals.status.complete') : 'Obrigatório' }}
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
            {{ $t('gestlab.general.labels.vap_proposals.create.template_preview') }}
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
              {{ $t('gestlab.general.labels.vap_proposals.create.view_full_template') }}
            </button>
          </div>
        </div>

        <!-- QUICK ADD ITEMS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BoltIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.create.quick_add') }}
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
        {{ $t('gestlab.general.labels.vap_proposals.create.footer_note') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="resetForm"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
        >
          <ArrowPathIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.reset') }}
        </button>
        <button 
          @click="submit"
          :disabled="form.processing || !isFormValid"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing || !isFormValid
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
          ]"
        >
          <CheckCircleIcon class="h-5 w-5" />
          {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.create.submit') }}
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
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { 
  DocumentPlusIcon, ArrowLeftIcon, InformationCircleIcon,
  UserIcon, BuildingStorefrontIcon, BuildingOfficeIcon,
  DocumentTextIcon, MapPinIcon, ClockIcon, ListBulletIcon,
  PlusCircleIcon, DocumentDuplicateIcon, TrashIcon,
  CheckCircleIcon, Cog6ToothIcon, BoltIcon, ArrowPathIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/modal.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue' 
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'

const props = defineProps({
  customers: Array,
  warehouses: Array,
  departments: Array,
  templates: Array,
  nextProposalNo: String,
  units: Array,
  standards: Array,
})

const form = useForm({
  customer_id: null,
  warehouse_id: null,
  department_id: null,
  template_id: null,
  service_location: '',
  tolerance_days: 7,
  withhold_tax: false,
  obs: '',
  items: [],
})

// UI State
const showTemplatePreview = ref(false)
const loadingWarehouses = ref(false);

const selectedCustomer = ref(null)
const selectedWarehouse = ref(null)
const selectedDepartment = ref(null)

const templateSelected = ref(null)

watch(selectedCustomer, (newVal) => { 
    form.customer_id = newVal?.value || '';
    
    loadingWarehouses.value = true;
    fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + newVal?.value)
    .then(response => response.json())
    .then(results => {
        const warehouses = results.map(result => ({
            value: result.id,
            label: result.address,
        }));
        
        selectedWarehouse.value = warehouses[0] || '';
        
        loadingWarehouses.value = false;
    })
    .catch(() => {
        loadingWarehouses.value = false;
    });
})
watch(selectedWarehouse, (newVal) => form.warehouse_id = newVal?.value || '')
watch(templateSelected, (newVal) => form.template_id = newVal?.value || '')
watch(selectedDepartment, (newVal) => form.department_id = newVal?.value || '')

// Quick items for easy addition
const quickItems = ref([
  {
    name: 'Análise Química Básica',
    description: 'Análise de pH, condutividade, sólidos totais',
    qty: 1,
    unit_price: 250.00,
    unit_id: 1, // Assume que o primeiro unit é "Amostra"
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
  return form.customer_id && form.warehouse_id && form.department_id && 
         form.template_id && form.service_location
})

const hasItems = computed(() => {
  return form.items.length > 0
})

const isFormValid = computed(() => {
  return hasBasicInfo.value && hasItems.value
})

const calculateSubtotal = computed(() => {
  return form.items.reduce((sum, item) => {
    return sum + (parseFloat(item.total) || 0)
  }, 0)
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
  form.items.push({
    item_id: null,
    item_description: '',
    standard_id: null,
    unit_id: props.units[0]?.id || null,
    qty: 1,
    unit_price: 0,
    discount_percentage: 0,
    total: 0,
  })
}

const duplicateItem = (index) => {
  const itemToDuplicate = { ...form.items[index] }
  itemToDuplicate.item_description = `${itemToDuplicate.item_description} (Cópia)`
  form.items.splice(index + 1, 0, itemToDuplicate)
  calculateItemTotal(index + 1)
}

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  } else {
    alert('A proposta deve ter pelo menos um item')
  }
}

const addQuickItem = (quickItem) => {
  const newItem = {
    item_id: null,
    item_description: quickItem.item_description,
    standard_id: null,
    unit_id: quickItem.unit_id,
    qty: quickItem.qty,
    unit_price: quickItem.unit_price,
    discount_percentage: 0,
    total: quickItem.qty * quickItem.unit_price,
  }
  form.items.push(newItem)
}

const calculateDiscountAmount = (item) => {
  const quantity = parseFloat(item.qty) || 0
  const unitPrice = parseFloat(item.unit_price) || 0
  const discountPercent = parseFloat(item.discount_percentage) || 0
  
  const totalBeforeDiscount = quantity * unitPrice
  return totalBeforeDiscount * (discountPercent / 100)
}

const calculateItemTotal = (index) => {
  const item = form.items[index]
  
  if (!item) return
  
  const quantity = parseFloat(item.qty) || 0
  const unitPrice = parseFloat(item.unit_price) || 0
  const discountPercent = parseFloat(item.discount_percentage) || 0
  
  const totalBeforeDiscount = quantity * unitPrice
  const discountAmount = totalBeforeDiscount * (discountPercent / 100)
  const itemTotal = totalBeforeDiscount - discountAmount
  
  form.items[index].total = parseFloat(itemTotal.toFixed(2))
}

const formatNumber = (number) => {
  return parseFloat(number).toFixed(2).replace('.', ',')
}

const stripHtml = (html) => {
  if (!html) return ''
  return html.replace(/<[^>]*>/g, '').substring(0, 200) + '...'
}

const resetForm = () => {
  if (confirm('Tem certeza que deseja resetar o formulário? Todas as alterações serão perdidas.')) {
    form.reset()
    form.items = []
    form.tolerance_days = 7
  }
}

const saveDraft = () => {
  // Implementar lógica para salvar rascunho
  alert('Funcionalidade de rascunho em desenvolvimento...')
}

function loadWarehouses(query, setOptions) {
    fetch('/warehouses/getWarehouse?q=' + query + '&customer_id=' + form.customer_id?.value)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.address,
        }))
        );
    });
}

const submit = () => {
  if (!isFormValid.value) {
    alert('Por favor, preencha todos os campos obrigatórios e adicione pelo menos um item')
    return
  }

  form.post(route('vap-proposals.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Redirecionamento tratado pelo controller
    },
    onError: (errors) => {
      console.error('Erros no formulário:', errors)
    }
  })
}

// Watchers
watch(() => form.template_id, (newTemplateId) => {
  if (newTemplateId) {
    const template = props.templates.find(t => t.id === newTemplateId)
    if (template) {
      // Pode-se preencher automaticamente alguns campos baseados no template
      console.log('Template selecionado:', template.name)
    }
  }
})

// Initialize with one empty item
addItem()
</script>

<style>

</style>