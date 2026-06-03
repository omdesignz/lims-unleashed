<template>
  <div class="import-certificate-show space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentDuplicateIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.import_certificates.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.import_certificates.page_view_description') }}
            <span v-if="record.data?.importer" class="font-semibold text-blue-900">
              {{ record.data?.importer.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.import_certificates.items') }}
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            record.data?.invoiced ? 'bg-green-100 text-green-800 ring-green-700/10' : 'bg-gray-100 text-gray-800 ring-gray-700/10'
          ]">
            {{ record.data?.invoiced ? $t('gestlab.general.labels.import_certificates.invoiced') : $t('gestlab.general.labels.import_certificates.not_invoiced') }}
          </span>
        </div>
      </div>
      
      <!-- CERTIFICATE META INFO -->
      <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.import_certificates.cert_no') }}</p>
          <p class="text-sm font-semibold text-blue-900">{{ record.data?.cert_no }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.import_certificates.date') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ formatDate(record.data?.date) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.import_certificates.total_quantity') }}</p>
          <p class="text-sm font-bold text-blue-900">{{ formatQuantity(totalQuantity) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.import_certificates.total_cost') }}</p>
          <p class="text-sm font-bold text-blue-900">{{ formatCurrency(totalCost) }}</p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CERTIFICATE DETAILS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.import_certificates.certificate_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- LOGISTICS -->
              <div class="space-y-6">
                <!-- PORTS -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <TruckIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.import_certificates.logistics') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.trans_type_id') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ record.data?.trans || 'N/A' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.port_exit') }}</span>
                        <span class="text-sm text-gray-900">{{ record.data?.port_exit || 'N/A' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.port_entry') }}</span>
                        <span class="text-sm text-gray-900">{{ record.data?.port_entry || 'N/A' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.destination_country') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ record.data?.destination_country || 'N/A' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- PARTIES -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.import_certificates.parties_involved') }}
                  </h3>
                  <div class="grid grid-cols-1 gap-4">
                    <!-- Importer -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                      <div class="flex items-center gap-2 mb-2">
                        <UserIcon class="h-4 w-4 text-blue-900" />
                        <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.import_certificates.importer_id') }}</span>
                      </div>
                      <p class="text-sm font-semibold text-gray-900">{{ record.data?.importer }}</p>
                      <p v-if="record.data?.importer_warehouse" class="text-xs text-gray-500 mt-1">
                        {{ record.data?.importer_warehouse }}
                      </p>
                    </div>

                    <!-- Exporter -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                      <div class="flex items-center gap-2 mb-2">
                        <TruckIcon class="h-4 w-4 text-green-900" />
                        <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.import_certificates.exporter_id') }}</span>
                      </div>
                      <p class="text-sm font-semibold text-gray-900">{{ record.data?.exporter }}</p>
                      <p v-if="record.data?.exporter_warehouse" class="text-xs text-gray-500 mt-1">
                        {{ record.data?.exporter_warehouse }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- FINANCIAL -->
              <div class="space-y-6">
                <!-- COSTS -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.import_certificates.financial_details') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="space-y-3">
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.currency_id') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ record.data?.currency || 'N/A' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.cost_freight') }}</span>
                        <span class="text-sm text-gray-900">{{ formatCurrency(record.data?.cost_freight) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.cost_insurance') }}</span>
                        <span class="text-sm text-gray-900">{{ formatCurrency(record.data?.cost_insurance) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.cost_final') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ formatCurrency(record.data?.cost_final) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.vat') }} ({{ record.data?.vat }}%)</span>
                        <span class="text-sm text-gray-900">{{ formatCurrency(record.data?.vat_cost) }}</span>
                      </div>
                      <div class="flex items-center justify-between border-t border-gray-200 pt-2">
                        <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.import_certificates.total_cost') }}</span>
                        <span class="text-sm font-bold text-blue-900">{{ formatCurrency(totalCost) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- INVOICE -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.import_certificates.invoice_information') }}
                  </h3>
                  <div :class="[
                    'rounded-lg p-4 border',
                    record.data?.invoiced ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'
                  ]">
                    <div class="space-y-2">
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.invoiced') }}</span>
                        <span :class="[
                          'text-sm font-medium',
                          record.data?.invoiced ? 'text-green-700' : 'text-gray-700'
                        ]">
                          {{ record.data?.invoiced ? $t('gestlab.general.labels.import_certificates.yes') : $t('gestlab.general.labels.import_certificates.no') }}
                        </span>
                      </div>
                      <div v-if="record.data?.invoice" class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.invoice_number') }}</span>
                        <span class="text-sm font-medium text-blue-900">{{ record.data?.invoice.inv_no }}</span>
                      </div>
                      <div v-if="record.data?.invoice" class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.import_certificates.invoice_total') }}</span>
                        <span class="text-sm text-gray-900">{{ formatCurrency(record.data?.invoice.total) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- AUTHORIZED PERSONNEL -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex items-center gap-3">
                <UserCircleIcon class="h-5 w-5 text-gray-500" />
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ $t('gestlab.general.labels.import_certificates.authorized_personnel') }}
                  </p>
                  <p class="text-sm text-gray-700">{{ record.data?.authorized_personnel || $t('gestlab.general.labels.import_certificates.not_specified') }}</p>
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
                {{ $t('gestlab.general.labels.import_certificates.imported_products') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.import_certificates.items') }})
                </span>
              </h2>
            </div>
            <p class="mt-1 text-sm text-gray-600">
              {{ $t('gestlab.general.labels.import_certificates.imported_products_description') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!record.data?.items || record.data?.items.length === 0" class="p-12 text-center">
            <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.import_certificates.no_items') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.import_certificates.no_products_description') }}
            </p>
          </div>

          <!-- PRODUCTS TABLE -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.import_certificates.product_id') }}
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
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr 
                  v-for="(item, index) in record.data?.items" 
                  :key="index"
                  class="hover:bg-gray-50 transition-colors duration-150"
                >
                  <!-- Product -->
                  <td class="py-4 pl-6 pr-3">
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ item.product }}</p>
                      <p class="text-xs text-gray-500">{{ item.product?.code }}</p>
                    </div>
                  </td>

                  <!-- Quantity -->
                  <td class="px-3 py-4 text-sm text-gray-500 text-center">
                    <div class="flex items-center justify-center gap-2">
                      <span class="font-semibold">{{ formatQuantity(item.qty) }}</span>
                      <span class="text-sm text-gray-400">{{ $t('gestlab.general.labels.import_certificates.units') }}</span>
                    </div>
                  </td>

                  <!-- Origin -->
                  <td class="px-3 py-4 text-sm text-gray-900">
                    {{ item.origin || $t('gestlab.general.labels.import_certificates.not_specified') }}
                  </td>

                  <!-- Validity -->
                  <td class="px-3 py-4 text-sm text-gray-900">
                    {{ item.validity ? formatDate(item.validity) : $t('gestlab.general.labels.import_certificates.not_specified') }}
                  </td>

                  <!-- Lot -->
                  <td class="px-3 py-4 text-sm text-gray-900">
                    {{ item.lot || $t('gestlab.general.labels.import_certificates.not_specified') }}
                  </td>

                  <!-- BL Number -->
                  <td class="px-3 py-4 text-sm text-gray-900">
                    {{ item.bl_no || $t('gestlab.general.labels.import_certificates.not_specified') }}
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
                    {{ record.data?.items?.length || 0 }}
                  </td>
                  <td colspan="4"></td>
                </tr>
                <tr class="bg-blue-50">
                  <td class="whitespace-nowrap py-4 pl-6 pr-3 text-lg font-bold text-gray-900">
                    {{ $t('gestlab.general.labels.import_certificates.total_quantity') }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-center">
                    {{ formatQuantity(totalQuantity) }}
                  </td>
                  <td colspan="4"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- OBSERVATIONS & FILE -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- OBSERVATIONS -->
          <div v-if="record.data?.obs" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-2 mb-3">
                <InformationCircleIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.import_certificates.obs') }}
              </label>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ record.data?.obs }}</p>
              </div>
            </div>
          </div>

          <!-- FILE ATTACHMENT -->
          <div v-if="record.data?.file" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-2 mb-3">
                <DocumentIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.import_certificates.attached_file') }}
              </label>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-3">
                  <DocumentIcon class="h-8 w-8 text-blue-900" />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ getFileName(record.data?.file) }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ $t('gestlab.general.labels.import_certificates.certificate_document') }}
                    </p>
                  </div>
                  <button 
                    @click="downloadFile"
                    class="inline-flex items-center gap-1 rounded-md bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                  >
                    <DocumentArrowDownIcon class="h-3 w-3" />
                    {{ $t('gestlab.general.buttons.download') }}
                  </button>
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
              @click="downloadPDF"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentArrowDownIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.import_certificates.download_pdf') }}
            </button>
            
            <!-- <button 
              @click="sendEmail"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-green-500 text-white hover:from-green-500 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-all duration-200"
            >
              <EnvelopeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.import_certificates.send_email') }}
            </button> -->

            <button 
              v-if="!record.data?.invoiced"
              @click="linkToInvoice"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 text-white hover:from-indigo-500 hover:to-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition-all duration-200"
            >
              <CreditCardIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.import_certificates.link_to_invoice') }}
            </button>

            <button 
              v-if="record.data?.invoiced"
              @click="showInvoice"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 text-white hover:from-indigo-500 hover:to-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition-all duration-200"
            >
              <CreditCardIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.import_certificates.show_invoice') }}
            </button>

            <!-- <button 
              @click="duplicateCertificate"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-gray-700 to-gray-600 text-white hover:from-gray-600 hover:to-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.import_certificates.duplicate') }}
            </button> -->

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.import_certificates.products') }}</span>
                  <span class="font-semibold text-blue-900">{{ record.data?.items?.length || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.import_certificates.total_quantity') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatQuantity(totalQuantity) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.import_certificates.cost_freight') }}</span>
                  <span class="text-gray-900">{{ formatCurrency(record.data?.cost_freight) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.import_certificates.cost_insurance') }}</span>
                  <span class="text-gray-900">{{ formatCurrency(record.data?.cost_insurance) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.import_certificates.cost_final') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(record.data?.cost_final) }}</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-gray-200 pt-2">
                  <span class="text-gray-900">{{ $t('gestlab.general.labels.import_certificates.total_cost') }}</span>
                  <span class="text-blue-900">{{ formatCurrency(totalCost) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- AUDIT TRAIL CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.import_certificates.audit_trail') }}
          </h3>
          <div class="space-y-4">
            <!-- Created -->
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <UserIcon class="h-4 w-4 text-blue-900" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.import_certificates.created_by') }}
                </p>
                <p class="text-xs text-gray-500">{{ record.data?.user?.name || 'N/A' }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(record.data?.created_at) }}</p>
              </div>
            </div>
            
            <!-- Last Updated -->
            <div v-if="record.data?.updated_at !== record.data?.created_at" class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                  <PencilIcon class="h-4 w-4 text-gray-700" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.import_certificates.last_updated') }}
                </p>
                <p class="text-xs text-gray-500">{{ formatDateTime(record.data?.updated_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-green-500"></div>
            <span>{{ record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.import_certificates.products') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <CubeIcon class="h-4 w-4 text-gray-400" />
            <span class="font-semibold">{{ formatQuantity(totalQuantity) }} {{ $t('gestlab.general.labels.import_certificates.units') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <CurrencyDollarIcon class="h-4 w-4 text-gray-400" />
            <span class="font-semibold">{{ formatCurrency(totalCost) }} {{ $t('gestlab.general.labels.import_certificates.total') }}</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <Link 
          as="button"
          :href="route('importcertificates.index')"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.back') }}
      </Link>
        
        <Link 
          as="button"
          :href="route('importcertificates.edit', { importcertificate: record.data?.id })"
          v-if="canEdit"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <PencilIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.edit') }}
      </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { 
  DocumentDuplicateIcon,
  DocumentTextIcon,
  DocumentIcon,
  DocumentArrowDownIcon,
  UserIcon,
  BuildingOfficeIcon,
  TruckIcon,
  BeakerIcon,
  InformationCircleIcon,
  CubeIcon,
  CurrencyDollarIcon,
  UserCircleIcon,
  EnvelopeIcon,
  CreditCardIcon,
  ArrowLeftIcon,
  PencilIcon
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object,
});

// Computed properties
const totalQuantity = computed(() => {
  if (!props.record.data?.items) return 0;
  return props.record.data?.items.reduce((total, item) => {
    return total + (parseFloat(item.qty) || 0);
  }, 0);
});

const totalCost = computed(() => {
  return (parseFloat(props.record.data?.cost_freight || 0) + 
          parseFloat(props.record.data?.cost_insurance || 0) + 
          parseFloat(props.record.data?.cost_final || 0) + 
          parseFloat(props.record.data?.vat_cost || 0));
});

// Formatting functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatQuantity = (quantity) => {
  if (!quantity) return '0.00';
  return parseFloat(quantity).toLocaleString('pt-PT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('pt-PT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const getFileName = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('/');
  return parts[parts.length - 1];
};

// Actions
const downloadPDF = () => {
  window.open(route('importcertificates.getPDF', { id: props.record.data.id}), '_blank');
};

const downloadFile = () => {
  if (props.record.data?.file) {
    window.open(props.record.data?.file, '_blank');
  }
};

const sendEmail = () => {
  router.visit(`/import-certificates/${props.record.data?.id}/send-email`);
};

const linkToInvoice = () => {

  router.get(route('importcertificates.getIssueInvoiceModal', { id: props.record.data?.id}));
};

const showInvoice = () => {

  router.get(route('invoices.show', { id: props.record.data?.invoice_id}));
};

const duplicateCertificate = () => {
  router.visit(`/import-certificates/${props.record.data?.id}/duplicate`);
};

const editCertificate = () => {
  router.visit(`/import-certificates/${props.record.data?.id}/edit`);
};

// Check if user can edit (based on status)
const canEdit = computed(() => {
  return !props.record.data?.invoiced;
});
</script>

<style scoped>
.import-certificate-show :deep(.bg-white) {
  background-color: rgb(255 253 247 / 0.96);
}

.import-certificate-show :deep(.bg-gray-50) {
  background-color: rgb(247 241 231 / 0.76);
}

.import-certificate-show :deep(.border-gray-200),
.import-certificate-show :deep(.border-gray-100),
.import-certificate-show :deep(.divide-gray-200),
.import-certificate-show :deep(.divide-gray-300) {
  border-color: #ded3bf;
}

.import-certificate-show :deep(.text-blue-900) {
  color: rgb(var(--primary-800-rgb));
}

.import-certificate-show :deep(.bg-blue-900),
.import-certificate-show :deep(.bg-blue-950) {
  background-color: rgb(var(--primary-900-rgb));
}

.import-certificate-show :deep(.from-blue-900) {
  --tw-gradient-from: rgb(var(--primary-900-rgb)) var(--tw-gradient-from-position);
  --tw-gradient-to: rgb(var(--primary-900-rgb) / 0) var(--tw-gradient-to-position);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

.import-certificate-show :deep(.to-blue-800) {
  --tw-gradient-to: rgb(var(--primary-700-rgb)) var(--tw-gradient-to-position);
}

:global(.dark) .import-certificate-show :deep(.bg-white),
:global(.dark) .import-certificate-show :deep(.bg-gray-50),
:global(.dark) .import-certificate-show :deep(.bg-gray-100) {
  background-color: rgb(7 17 15 / 0.9);
}

:global(.dark) .import-certificate-show :deep(.border-gray-200),
:global(.dark) .import-certificate-show :deep(.border-gray-100),
:global(.dark) .import-certificate-show :deep(.divide-gray-200),
:global(.dark) .import-certificate-show :deep(.divide-gray-300) {
  border-color: #25443c;
}

:global(.dark) .import-certificate-show :deep(.text-gray-900) {
  color: #f7f1e7;
}

:global(.dark) .import-certificate-show :deep(.text-gray-800),
:global(.dark) .import-certificate-show :deep(.text-gray-700) {
  color: #d7e2dd;
}

:global(.dark) .import-certificate-show :deep(.text-gray-600),
:global(.dark) .import-certificate-show :deep(.text-gray-500),
:global(.dark) .import-certificate-show :deep(.text-gray-400) {
  color: #a9bbb4;
}

:global(.dark) .import-certificate-show :deep(.text-blue-900) {
  color: rgb(var(--primary-200-rgb));
}

:global(.dark) .import-certificate-show :deep(.bg-blue-50),
:global(.dark) .import-certificate-show :deep(.bg-blue-100) {
  background-color: rgb(var(--primary-500-rgb) / 0.12);
}
</style>
