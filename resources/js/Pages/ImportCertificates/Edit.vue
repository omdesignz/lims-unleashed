<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentDuplicateIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.import_certificates.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.import_certificates.page_update_description') }}
            <span v-if="form.importer?.name" class="font-semibold text-blue-900">
              {{ form.importer.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.items.length }} {{ $t('gestlab.general.labels.import_certificates.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- CERTIFICATE SETTINGS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <DocumentTextIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.import_certificates.certificate_settings') }}
        </h2>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Certificate Number -->
          <!-- <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.import_certificates.cert_no') }}
              <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="form.cert_no" 
              type="text" 
              class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.cert_no')"
            />
            <p v-if="form.errors.cert_no" class="text-xs text-red-600">
              {{ form.errors.cert_no }}
            </p>
          </div> -->

          <!-- Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <CalendarIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.import_certificates.date') }}
            </label>
            <date-picker-enhanced 
              v-model.string="form.date" 
              locale="pt" 
              color="blue" 
              mode="date" 
              :masks="masks"
              class="w-full"
              :popover-placement="'bottom-start'"
            />
            <p v-if="form.errors.date" class="text-xs text-red-600">
              {{ form.errors.date }}
            </p>
          </div>

          <!-- Transport Type -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.import_certificates.trans_type_id') }}
            </label>
            <comboboxEnhanced 
              :hasError="form.errors.trans_type_id" 
              v-model="form.trans_type_id" 
              :load-options="loadTransportTypes"
              :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.transport_type')"
            />
            <p v-if="form.errors.trans_type_id" class="text-xs text-red-600">
              {{ form.errors.trans_type_id }}
            </p>
          </div>
        </div>

        <!-- PORTS SECTION -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.import_certificates.ports_information') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Port of Exit -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.port_exit') }}
              </label>
              <input 
                v-model="form.port_exit" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.port_exit')"
              />
              <p v-if="form.errors.port_exit" class="text-xs text-red-600">
                {{ form.errors.port_exit }}
              </p>
            </div>

            <!-- Port of Entry -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.port_entry') }}
              </label>
              <input 
                v-model="form.port_entry" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.port_entry')"
              />
              <p v-if="form.errors.port_entry" class="text-xs text-red-600">
                {{ form.errors.port_entry }}
              </p>
            </div>

            <!-- Destination Country -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.destination_country_id') }}
              </label>
              <comboboxEnhanced 
                :hasError="form.errors.destination_country_id" 
                v-model="form.destination_country_id" 
                :load-options="loadCountries"
                :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.destination_country')"
              />
              <p v-if="form.errors.destination_country_id" class="text-xs text-red-600">
                {{ form.errors.destination_country_id }}
              </p>
            </div>
          </div>
        </div>

        <!-- PARTIES SECTION -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.import_certificates.parties_involved') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Importer -->
            <div class="space-y-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.import_certificates.importer_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                  :hasError="form.errors.importer_id" 
                  v-model="form.importer_id" 
                  :load-options="loadImporters"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.importer')"
                />
                <p v-if="form.errors.importer_id" class="text-xs text-red-600">
                  {{ form.errors.importer_id }}
                </p>
              </div>

              <!-- Importer Warehouse -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.import_certificates.importer_warehouse_id') }}
                </label>
                <comboboxEnhanced 
                  :disableInput="!form.importer_id || loadingImporterWarehouses"
                  :loading="loadingImporterWarehouses"
                  :hasError="form.errors.importer_warehouse_id" 
                  v-model="form.importer_warehouse_id" 
                  :load-options="loadImporterWarehouses"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.importer_warehouse')"
                />
                <p v-if="form.errors.importer_warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.importer_warehouse_id }}
                </p>
              </div>
            </div>

            <!-- Exporter -->
            <div class="space-y-6">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TruckIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.import_certificates.exporter_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                  :hasError="form.errors.exporter_id" 
                  v-model="form.exporter_id" 
                  :load-options="loadExporters"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.exporter')"
                />
                <p v-if="form.errors.exporter_id" class="text-xs text-red-600">
                  {{ form.errors.exporter_id }}
                </p>
              </div>

              <!-- Exporter Warehouse -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.import_certificates.exporter_warehouse_id') }}
                </label>
                <comboboxEnhanced 
                  :disableInput="!form.exporter_id || loadingExporterWarehouses"
                  :loading="loadingExporterWarehouses"
                  :hasError="form.errors.exporter_warehouse_id" 
                  v-model="form.exporter_warehouse_id" 
                  :load-options="loadExporterWarehouses"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.exporter_warehouse')"
                />
                <p v-if="form.errors.exporter_warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.exporter_warehouse_id }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- FINANCIAL SECTION -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.import_certificates.financial_information') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Currency -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.currency_id') }}
              </label>
              <comboboxEnhanced 
                :hasError="form.errors.currency_id" 
                v-model="form.currency_id" 
                :load-options="loadCurrencies"
                :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.currency')"
              />
              <p v-if="form.errors.currency_id" class="text-xs text-red-600">
                {{ form.errors.currency_id }}
              </p>
            </div>

            <!-- Freight Cost -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.cost_freight') }}
              </label>
              <div class="relative rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input 
                  v-model="form.cost_freight" 
                  type="number" 
                  step="0.01"
                  min="0"
                  class="block w-full rounded-md border-0 py-2 pl-7 pr-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="0.00"
                />
              </div>
              <p v-if="form.errors.cost_freight" class="text-xs text-red-600">
                {{ form.errors.cost_freight }}
              </p>
            </div>

            <!-- Insurance Cost -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.cost_insurance') }}
              </label>
              <div class="relative rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input 
                  v-model="form.cost_insurance" 
                  type="number" 
                  step="0.01"
                  min="0"
                  class="block w-full rounded-md border-0 py-2 pl-7 pr-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="0.00"
                />
              </div>
              <p v-if="form.errors.cost_insurance" class="text-xs text-red-600">
                {{ form.errors.cost_insurance }}
              </p>
            </div>

            <!-- Final Cost -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.cost_final') }}
              </label>
              <div class="relative rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input 
                  v-model="form.cost_final" 
                  type="number" 
                  step="0.01"
                  min="0"
                  class="block w-full rounded-md border-0 py-2 pl-7 pr-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="0.00"
                />
              </div>
              <p v-if="form.errors.cost_final" class="text-xs text-red-600">
                {{ form.errors.cost_final }}
              </p>
            </div>
          </div>

          <!-- VAT SECTION -->
          <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- VAT Percentage -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.vat') }} (%)
              </label>
              <div class="relative rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                  <span class="text-gray-500 sm:text-sm">%</span>
                </div>
                <input 
                  v-model="form.vat" 
                  type="number" 
                  step="0.01"
                  min="0"
                  max="100"
                  class="block w-full rounded-md border-0 py-2 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="0.00"
                />
              </div>
              <p v-if="form.errors.vat" class="text-xs text-red-600">
                {{ form.errors.vat }}
              </p>
            </div>

            <!-- VAT Cost -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.vat_cost') }}
              </label>
              <div class="relative rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input 
                  v-model="form.vat_cost" 
                  type="number" 
                  step="0.01"
                  min="0"
                  class="block w-full rounded-md border-0 py-2 pl-7 pr-2 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  placeholder="0.00"
                  readonly
                />
              </div>
              <p v-if="form.errors.vat_cost" class="text-xs text-red-600">
                {{ form.errors.vat_cost }}
              </p>
            </div>
          </div>

          <!-- INVOICE LINK -->
          <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.import_certificates.invoice_id') }}
                </label>
                <comboboxEnhanced 
                  :hasError="form.errors.invoice_id" 
                  v-model="form.invoice_id" 
                  :load-options="loadInvoices"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.invoice')"
                  class="w-64"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input 
                    v-model="form.invoiced" 
                    type="checkbox" 
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  {{ $t('gestlab.general.labels.import_certificates.invoiced') }}
                </label>
              </div>
            </div>
            <p v-if="form.errors.invoice_id" class="text-xs text-red-600">
              {{ form.errors.invoice_id }}
            </p>
          </div>
        </div>

        <!-- AUTHORIZED PERSONNEL -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Authorized Personnel -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.import_certificates.authorized_personnel') }}
              </label>
              <input 
                v-model="form.authorized_personnel" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.authorized_personnel')"
              />
              <p v-if="form.errors.authorized_personnel" class="text-xs text-red-600">
                {{ form.errors.authorized_personnel }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PRODUCTS SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.import_certificates.products') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ form.items.length }} {{ $t('gestlab.general.labels.import_certificates.items') }})
            </span>
          </h2>
          <button 
            @click="addItem" 
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.add_item') }}
          </button>
        </div>
        <p class="mt-1 text-sm text-gray-600">
          {{ $t('gestlab.general.labels.import_certificates.items_tagline') }}
        </p>
      </div>

      <div v-if="form.items.length === 0" class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.buttons.no_items') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.buttons.add_first_item') }}
        </p>
        <button 
          @click="addItem" 
          type="button"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.add_first_item') }}
        </button>
      </div>

      <!-- PRODUCTS TABLE -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.product') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.quantity') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.origin') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.validity') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.lot') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.bl_no') }}
              </th>
              <th scope="col" class="relative py-3.5 pl-3 pr-6">
                <span class="sr-only">{{ $t('gestlab.general.labels.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr 
              v-for="(item, index) in form.items" 
              :key="index"
              v-motion
              :initial="{ opacity: 0, y: 10 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <!-- Product Selection -->
              <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm">
                <comboboxEnhanced 
                  v-model="item.product_id" 
                  :load-options="loadProducts"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.product')"
                  class="min-w-[200px]"
                />
              </td>

              <!-- Quantity -->
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                <div class="flex items-center justify-center gap-2">
                  <input 
                    v-model="item.qty" 
                    type="number" 
                    step="0.01"
                    min="0"
                    class="w-32 rounded-md border border-gray-300 px-3 py-1.5 text-center text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="0.00"
                  />
                  <span class="text-sm text-gray-500">{{ $t('gestlab.general.labels.import_certificates.units') }}</span>
                </div>
              </td>

              <!-- Origin -->
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <input 
                  v-model="item.origin" 
                  type="text" 
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.origin')"
                />
              </td>

              <!-- Validity -->
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <date-picker-enhanced 
                  v-model.string="item.validity" 
                  locale="pt" 
                  color="blue" 
                  mode="date" 
                  :masks="masks"
                  class="w-32"
                  :popover-placement="'bottom-start'"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.validity')"
                />
              </td>

              <!-- Lot -->
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <input 
                  v-model="item.lot" 
                  type="text" 
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.lot')"
                />
              </td>

              <!-- BL Number -->
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <input 
                  v-model="item.bl_no" 
                  type="text" 
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.bl_no')"
                />
              </td>

              <!-- Actions -->
              <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                <button 
                  @click="removeItem(index)"
                  type="button"
                  class="text-red-600 hover:text-red-900 transition-colors duration-200 p-1 rounded-md hover:bg-red-50"
                  :title="$t('gestlab.general.buttons.remove_item')"
                >
                  <TrashIcon class="h-5 w-5" />
                </button>
              </td>
            </tr>
          </tbody>

          <!-- SUMMARY -->
          <tfoot class="bg-gray-50 border-t-2 border-gray-200">
            <tr>
              <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.total_products') }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-blue-900 text-center">
                {{ form.items.length }}
              </td>
              <td colspan="4"></td>
              <td></td>
            </tr>
            <tr>
              <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900">
                {{ $t('gestlab.general.labels.import_certificates.total_quantity') }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-blue-900 text-center">
                {{ totalQuantity }}
              </td>
              <td colspan="4"></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- OBSERVATIONS & FILE UPLOAD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Observations -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <InformationCircleIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.import_certificates.obs') }}
          </label>
          <textarea 
            v-model="form.obs" 
            rows="4"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
            :placeholder="$t('gestlab.general.labels.import_certificates.placeholders.observations')"
          />
          <p v-if="form.errors.obs" class="text-xs text-red-600">
            {{ form.errors.obs }}
          </p>
        </div>

        <!-- File Upload -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <DocumentArrowUpIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.import_certificates.file') }}
          </label>
          <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
            <div class="space-y-1 text-center">
              <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
              <div class="flex text-sm text-gray-600">
                <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-medium text-blue-900 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-900 focus-within:ring-offset-2 hover:text-blue-700">
                  <span>{{ $t('gestlab.general.buttons.upload_file') }}</span>
                  <input 
                    id="file-upload" 
                    name="file-upload" 
                    type="file" 
                    class="sr-only"
                    @change="handleFileUpload"
                  />
                </label>
                <p class="pl-1">or drag and drop</p>
              </div>
              <p class="text-xs text-gray-500">
                PDF, DOC, DOCX up to 10MB
              </p>
            </div>
          </div>
          <p v-if="form.errors.file" class="text-xs text-red-600">
            {{ form.errors.file }}
          </p>
          <div v-if="form.file" class="mt-2">
            <div class="flex items-center gap-2 text-sm text-gray-700">
              <DocumentIcon class="h-5 w-5 text-green-500" />
              <span>{{ form.file.name }}</span>
              <button 
                @click="removeFile"
                type="button"
                class="ml-2 text-red-600 hover:text-red-900"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SUBMIT SECTION -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-green-500"></div>
            <span>{{ form.items.length }} {{ $t('gestlab.general.labels.import_certificates.products') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <CubeIcon class="h-4 w-4 text-gray-400" />
            <span class="font-semibold">{{ totalQuantity }} {{ $t('gestlab.general.labels.import_certificates.total_units') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <CurrencyDollarIcon class="h-4 w-4 text-gray-400" />
            <span class="font-semibold">{{ formatCurrency(totalCost) }} {{ $t('gestlab.general.labels.import_certificates.total_cost') }}</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <button 
          type="button" 
          @click="submit"
          :disabled="form.processing || !isFormValid"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing || !isFormValid
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
          ]"
        >
          <DocumentDuplicateIcon class="h-5 w-5" />
          {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.update') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import { 
  TrashIcon, 
  PlusCircleIcon, 
  DocumentDuplicateIcon,
  DocumentTextIcon,
  DocumentIcon,
  DocumentArrowUpIcon,
  UserIcon,
  BuildingOfficeIcon,
  CalendarIcon,
  BeakerIcon,
  InformationCircleIcon,
  CubeIcon,
  TruckIcon,
  CurrencyDollarIcon
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout
});

const loadingImporterWarehouses = ref(false);
const loadingExporterWarehouses = ref(false);

const props = defineProps({
    record: Object
});

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const form = useForm({
  id: props.record.data?.id,  
  cert_no: props.record.data?.cert_no,
  port_exit: props.record.data?.port_exit,
  port_entry: props.record.data?.port_entry,
  destination_country_id: {
    value: props.record.data?.destination_country_id,
    label: props.record.data?.destination_country
  },
  cost_freight: props.record.data?.cost_freight,
  cost_insurance: props.record.data?.cost_insurance,
  cost_final: props.record.data?.cost_final,
  authorized_personnel: props.record.data?.authorized_personnel,
  date: props.record.data?.date,
  file: null,
  obs: props.record.data?.obs,
  trans_type_id: {
    value: props.record.data?.trans_type_id,
    label: props.record.data?.trans_type
  },
  importer_id: {
    value: props.record.data?.importer_id,
    label: props.record.data?.importer
  },
  importer_warehouse_id: {
    value: props.record.data?.importer_warehouse_id,
    label: props.record.data?.importer_warehouse
  },
  exporter_id: {
    value: props.record.data?.exporter_id,
    label: props.record.data?.exporter
  },
  exporter_warehouse_id: {
    value: props.record.data?.exporter_warehouse_id,
    label: props.record.data?.exporter_warehouse
  },
  currency_id: {
    value: props.record.data?.currency_id,
    label: props.record.data?.currency
  },
  vat: props.record.data?.vat || 0,
  vat_cost: props.record.data?.vat_cost,
  invoiced: false,
  invoice_id: props.record.data?.invoice_id,
  items: props.record.data?.items?.map(item => {
        return {
            ...item, // Keep all other properties (qty, price, etc.)
            product_id: {
                value: item.product_id,
                label: item.product || `Product #${item.product_id}` 
            }
        };
    })
});

const isFormValid = computed(() => {
  return form.importer_id && form.exporter_id && form.items.length > 0;
});

const totalQuantity = computed(() => {
  return form.items.reduce((total, item) => {
    return total + (parseFloat(item.qty) || 0);
  }, 0).toFixed(2);
});

const totalCost = computed(() => {
  return (parseFloat(form.cost_freight || 0) + 
          parseFloat(form.cost_insurance || 0) + 
          parseFloat(form.cost_final || 0) + 
          parseFloat(form.vat_cost || 0)).toFixed(2);
});

// Calculate VAT cost when cost_final or vat changes
watch(() => [form.cost_final, form.vat], () => {
  const cost = parseFloat(form.cost_final || 0);
  const vatPercentage = parseFloat(form.vat || 0);
  form.vat_cost = (cost * vatPercentage / 100).toFixed(2);
});

watch(() => [form.importer_id.value], () => {
  if (!form.importer_id?.value) return;
  
  loadingImporterWarehouses.value = true;
  fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.importer_id?.value)
    .then(response => response.json())
    .then(results => {
      const warehouses = results.map(result => ({
        value: result.id,
        label: result.address,
      }));
      form.importer_warehouse_id = warehouses[0] || '';
      loadingImporterWarehouses.value = false;
    })
    .catch(() => {
      loadingImporterWarehouses.value = false;
    });
});

watch(() => [form.exporter_id.value], () => {
  if (!form.exporter_id?.value) return;
  
  loadingExporterWarehouses.value = true;
  fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.exporter_id?.value)
    .then(response => response.json())
    .then(results => {
      const warehouses = results.map(result => ({
        value: result.id,
        label: result.address,
      }));
      form.exporter_warehouse_id = warehouses[0] || '';
      loadingExporterWarehouses.value = false;
    })
    .catch(() => {
      loadingExporterWarehouses.value = false;
    });
});

const addItem = () => {
  form.items.push({
    product_id: '',
    qty: 0,
    origin: '',
    validity: null,
    lot: '',
    bl_no: ''
  });
}

const removeItem = (index) => {
  if (confirm('Are you sure you want to remove this item?')) {
    form.items.splice(index, 1);
  }
}

function loadImporters(query, setOptions) {
  fetch('/customers/getCustomer?q=' + query + '&type=importer')
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
        }))
      );
    });
}

function loadExporters(query, setOptions) {
  fetch('/customers/getCustomer?q=' + query + '&type=exporter')
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
        }))
      );
    });
}

function loadImporterWarehouses(query, setOptions) {
  fetch('/warehouses/getWarehouse?q=' + query + '&customer_id=' + form.importer_id?.value)
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

function loadExporterWarehouses(query, setOptions) {
  fetch('/warehouses/getWarehouse?q=' + query + '&customer_id=' + form.exporter_id?.value)
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

function loadInvoices(query, setOptions) {
  fetch('/invoices/getInvoice?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: `${result.inv_no} - ${result.customer?.name || 'N/A'}`,
        }))
      );
    });
}

function loadTransportTypes(query, setOptions) {
  fetch('/transportcategories/getTransportCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
        }))
      );
    });
}

function loadCountries(query, setOptions) {
  fetch('/countries/getCountry?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
        }))
      );
    });
}

function loadCurrencies(query, setOptions) {
  fetch('/currencies/getCurrency?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: `${result.code} - ${result.name}`,
          symbol: result.symbol,
        }))
      );
    });
}

function loadProducts(query, setOptions) {
  fetch('/phytosanitary-products/getPhytosanitaryProduct?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: `${result.name}`,
          description: result.description,
        }))
      );
    });
}

const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.file = file;
  }
};

const removeFile = () => {
  form.file = null;
};

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('pt-PT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const submit = () => {
  form.put(route('importcertificates.update', {importcertificate: props.record.data?.id}), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>
