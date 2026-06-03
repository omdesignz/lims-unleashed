<template>
  <div class="commercial-document-page space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CreditCardIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.invoices.page_view_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.invoices.page_view_description') }}
            <span v-if="props.record.data?.customer" class="font-semibold text-blue-900">
              {{ props.record.data?.customer }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ props.record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.invoices.items') }}
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            props.record.data?.status === 'paid' ? 'bg-green-100 text-green-800 ring-green-700/10' :
            props.record.data?.status === 'pending' ? 'bg-yellow-100 text-yellow-800 ring-yellow-700/10' :
            props.record.data?.status === 'overdue' ? 'bg-red-100 text-red-800 ring-red-700/10' :
            props.record.data?.status === 'draft' ? 'bg-gray-100 text-gray-800 ring-gray-700/10' :
            'bg-blue-100 text-blue-800 ring-blue-700/10'
          ]">
            {{ formatStatus(props.record.data?.status) }}
          </span>
        </div>
      </div>
      
      <!-- INVOICE META INFO -->
      <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-2 md:grid-cols-5 gap-4">
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.invoices.inv_no') }}</p>
          <p class="text-sm font-semibold text-blue-900">{{ props.record.data?.inv_no }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.invoices.type_id') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ props.record.data?.invoice_category || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.invoices.created_at') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ formatDate(props.record.data?.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.invoices.amount_due') }}</p>
          <p class="text-sm font-semibold text-blue-900 flex items-center gap-1">
            <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

            {{ formatCurrency(props.record.data?.amount_due) }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.invoices.total_amount') }}</p>
          <p class="text-sm font-bold text-blue-900 flex items-center gap-1">
            <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

            {{ formatCurrency(props.record.data?.total) }}
          </p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CUSTOMER & INVOICE DETAILS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.invoices.invoice_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CUSTOMER & WAREHOUSE INFO -->
              <div class="space-y-6">
                <!-- CUSTOMER INFO -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.invoices.customer_info') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ props.record.data?.customer }}</p>
                    <div v-if="props.record.data?.customer" class="mt-2 space-y-1">
                      <p v-if="props.record.data?.customer.email" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.email') }}: {{ props.record.data?.customer.email }}
                      </p>
                      <p v-if="props.record.data?.customer.phone" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.phone') }}: {{ props.record.data?.customer.phone }}
                      </p>
                      <p v-if="props.record.data?.customer.vat_number" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.vat') }}: {{ props.record.data?.customer.vat_number }}
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- WAREHOUSE INFO -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <BuildingOfficeIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.invoices.delivery_address') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ props.record.data?.warehouse }}</p>
                    <div v-if="props.record.data?.warehouse" class="mt-2 space-y-1">
                      <p v-if="props.record.data?.warehouse.city || props.record.data?.warehouse.postal_code" class="text-xs text-gray-600">
                        {{ props.record.data?.warehouse.city }}{{ props.record.data?.warehouse.postal_code ? ', ' + props.record.data?.warehouse.postal_code : '' }}
                      </p>
                      <p v-if="props.record.data?.warehouse.country" class="text-xs text-gray-600">
                        {{ props.record.data?.warehouse.country }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- INVOICE SETTINGS -->
              <div class="space-y-6">
                <!-- INVOICE TYPE & REFERENCE -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.invoices.invoice_settings') }}
                  </h3>
                  <div class="space-y-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.invoices.type_id') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-900">{{ props.record.data?.invoice_category || 'N/A' }}</p>
                      </div>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.invoices.internal_ref') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-900">{{ props.record.data?.internal_ref || $t('gestlab.general.labels.invoices.no_reference') }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- LAB CODE & PRICING -->
                <div>
                  <div v-if="props.record.data?.lab_code" class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                      {{ $t('gestlab.general.labels.invoices.labcode_id') }}
                    </label>
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                      <p class="text-sm font-semibold text-blue-900">{{ props.record.data?.lab_code.code }}</p>
                      <p v-if="props.record.data?.assign_lab_code" class="text-xs text-gray-500 mt-1">
                        {{ $t('gestlab.general.labels.invoices.assigned_to_collection') }}
                      </p>
                    </div>
                  </div>

                  <!-- PRICING MODE -->
                  <div class="flex items-center gap-3">
                    <TagIcon class="h-5 w-5 text-gray-500" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">
                        {{ $t('gestlab.general.labels.invoices.pricing_mode') }}
                      </p>
                      <p class="text-xs text-gray-500">
                        {{ props.record.data?.use_matrix_price ? 
                           $t('gestlab.general.labels.invoices.matrix_pricing') : 
                           $t('gestlab.general.labels.invoices.parameter_pricing') }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ITEMS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <CalculatorIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.invoices.items') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ props.record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.invoices.items') }})
                </span>
              </h2>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!props.record.data?.items || props.record.data?.items.length === 0" class="p-12 text-center">
            <CreditCardIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.invoices.no_items') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.invoices.no_items_description') }}
            </p>
          </div>

          <!-- INVOICE ITEMS TABLE -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.item_description') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.qty') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.unit_price') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.discount') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.tax') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.line_total') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr 
                  v-for="(item, index) in props.record.data?.items" 
                  :key="index"
                  class="hover:bg-gray-50 transition-colors duration-150"
                >
                  <!-- Item Description -->
                  <td class="py-4 pl-6 pr-3">
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ item.item_description }}</p>
                      <div v-if="item.obs || item.exemption_code" class="mt-1 space-y-1">
                        <p v-if="item.obs" class="text-xs text-gray-500">{{ item.obs }}</p>
                        <p v-if="item.exemption_code" class="text-xs text-blue-600">
                          {{ $t('gestlab.general.labels.invoices.exemption') }}: {{ item.exemption_code }}
                        </p>
                      </div>
                      <p v-if="item.unit" class="text-xs text-gray-400 mt-1">
                        {{ item.unit.code }}
                      </p>
                    </div>
                  </td>

                  <!-- Quantity -->
                  <td class="px-3 py-4 text-sm text-gray-500 text-center">
                    <p class="font-medium">{{ item.qty }}</p>
                  </td>

                  <!-- Unit Price -->
                  <td class="px-3 py-4 text-sm text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(item.unit_price) }}
                    </div>
                  </td>

                  <!-- Discount -->
                  <td class="px-3 py-4 text-sm text-gray-900 text-right">
                    <div v-if="item.discount_amount > 0">
                      <p class="text-red-600">
                        -{{ formatCurrency(item.discount_amount) }}
                      </p>
                      <p class="text-xs text-gray-400">
                        {{ item.discount_percentage ? '(' + item.discount_percentage + '%)' : '' }}
                      </p>
                    </div>
                    <p v-else class="text-gray-400">-</p>
                  </td>

                  <!-- Tax -->
                  <td class="px-3 py-4 text-sm text-gray-900 text-right">
                    <div v-if="item.tax_amount > 0">
                      <p>{{ formatCurrency(item.tax_amount) }}</p>
                      <p class="text-xs text-gray-400">
                        ({{ item.tax_percentage }}%)
                      </p>
                    </div>
                    <p v-else class="text-gray-400">-</p>
                  </td>

                  <!-- Line Total -->
                  <td class="px-3 py-4 text-sm font-medium text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(item.total) }}
                    </div>
                  </td>
                </tr>
              </tbody>

              <!-- INVOICE SUMMARY -->
              <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.invoices.subtotal') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(props.record.data?.sub_total) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="props.record.data?.discount > 0">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.invoices.discount_total') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-red-600 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="h-4 w-4 mr-2">AOA</p>

                      -{{ formatCurrency(props.record.data?.discount) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="props.record.data?.tax > 0">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.invoices.tax_total') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(props.record.data?.tax) }}
                    </div>
                  </td>
                </tr>
                <tr class="bg-blue-50">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-lg font-bold text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.invoices.total') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                                    <p class="h-4 w-4 mr-2">AOA</p>

                      {{ formatCurrency(props.record.data?.total) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="props.record.data?.amount_due < props.record.data?.total">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.invoices.amount_paid') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-green-600 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="h-4 w-4 mr-2">AOA</p>

                      {{ formatCurrency(props.record.data?.total - props.record.data?.amount_due) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="props.record.data?.amount_due > 0" class="bg-gradient-to-r from-blue-50 to-white">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-lg font-bold text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.invoices.amount_due') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                                    <p class="mr-2">AOA</p>

                      {{ formatCurrency(props.record.data?.amount_due) }}
                    </div>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- OBSERVATIONS SECTION -->
        <div v-if="props.record.data?.obs" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2 mb-3">
              <InformationCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.invoices.obs') }}
            </label>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ props.record.data?.obs }}</p>
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
              {{ $t('gestlab.general.labels.invoices.download_pdf') }}
            </button>
            
            <button 
              v-if="props.record.data?.amount_due > 0"
              @click="recordPayment"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-green-500 text-white hover:from-green-500 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-all duration-200"
            >
              <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                                    <p class="mr-2">AOA</p>

              {{ $t('gestlab.general.labels.invoices.record_payment') }}
            </button>

            <button 
              @click="sendEmail"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 text-white hover:from-indigo-500 hover:to-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition-all duration-200"
            >
              <EnvelopeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.invoices.send_email') }}
            </button>

            <button 
              @click="duplicateInvoice"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-gray-700 to-gray-600 text-white hover:from-gray-600 hover:to-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.invoices.duplicate') }}
            </button>

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.items') }}</span>
                  <span class="font-semibold text-blue-900">{{ props.record.data?.items?.length || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.subtotal') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(props.record.data?.sub_total) }}</span>
                </div>
                <div v-if="props.record.data?.discount > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.discount') }}</span>
                  <span class="font-semibold text-red-600">-{{ formatCurrency(props.record.data?.discount) }}</span>
                </div>
                <div v-if="props.record.data?.tax > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.tax') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(props.record.data?.tax) }}</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-gray-200 pt-2">
                  <span class="text-gray-900">{{ $t('gestlab.general.labels.invoices.total') }}</span>
                  <span class="text-blue-900">{{ formatCurrency(props.record.data?.total) }}</span>
                </div>
                <div v-if="props.record.data?.amount_due < props.record.data?.total" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.amount_paid') }}</span>
                  <span class="font-semibold text-green-600">{{ formatCurrency(props.record.data?.total - props.record.data?.amount_due) }}</span>
                </div>
                <div v-if="props.record.data?.amount_due > 0" class="flex justify-between text-sm font-semibold border-t border-gray-200 pt-2">
                  <span class="text-gray-900">{{ $t('gestlab.general.labels.invoices.amount_due') }}</span>
                  <span class="text-blue-900">{{ formatCurrency(props.record.data?.amount_due) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PAYMENT STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.invoices.payment_status') }}
          </h3>
          <div class="space-y-4">
            <!-- Status Indicator -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.invoices.status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                props.record.data?.status === 'paid' ? 'bg-green-100 text-green-800' :
                props.record.data?.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                props.record.data?.status === 'overdue' ? 'bg-red-100 text-red-800' :
                'bg-blue-100 text-blue-800'
              ]">
                {{ formatStatus(props.record.data?.status) }}
              </span>
            </div>

            <!-- Payment Progress -->
            <div v-if="props.record.data?.total > 0">
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.payment_progress') }}</span>
                <span class="font-semibold text-gray-900">
                  {{ Math.round((props.record.data?.total - props.record.data?.amount_due) / props.record.data?.total * 100) }}%
                </span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  :class="[
                    'h-full rounded-full',
                    props.record.data?.amount_due === 0 ? 'bg-green-500' :
                    props.record.data?.amount_due < props.record.data?.total ? 'bg-yellow-500' :
                    'bg-blue-500'
                  ]"
                  :style="{ width: ((props.record.data?.total - props.record.data?.amount_due) / props.record.data?.total * 100) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Payment Details -->
            <div class="space-y-2 pt-2 border-t border-gray-200">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.total_amount') }}</span>
                <span class="font-semibold text-gray-900">{{ formatCurrency(props.record.data?.total) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.invoices.amount_paid') }}</span>
                <span class="font-semibold text-green-600">{{ formatCurrency(props.record.data?.total - props.record.data?.amount_due) }}</span>
              </div>
              <div class="flex justify-between text-sm font-semibold">
                <span class="text-gray-900">{{ $t('gestlab.general.labels.invoices.amount_due') }}</span>
                <span class="text-blue-900">{{ formatCurrency(props.record.data?.amount_due) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- AUDIT TRAIL CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.invoices.audit_trail') }}
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
                  {{ $t('gestlab.general.labels.invoices.created_by') }}
                </p>
                <p class="text-xs text-gray-500">{{ props.record.data?.user || 'N/A' }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(props.record.data?.created_at) }}</p>
              </div>
            </div>
            
            <!-- Last Updated -->
            <div v-if="props.record.data?.updated_at !== props.record.data?.created_at" class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                  <PencilIcon class="h-4 w-4 text-gray-700" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.invoices.last_updated') }}
                </p>
                <p class="text-xs text-gray-500">{{ formatDateTime(props.record.data?.updated_at) }}</p>
              </div>
            </div>

            <!-- Payments -->
            <div v-if="props.record.data?.payments && props.record.data?.payments.length > 0" class="space-y-3">
              <div 
                v-for="payment in props.record.data?.payments" 
                :key="payment.id"
                class="flex items-start gap-3"
              >
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-green-900" /> -->
                                    <p class="h-4 w-4 text-green-900">AOA</p>

                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900">
                    {{ $t('gestlab.general.labels.invoices.payment_received') }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ formatCurrency(payment.amount) }} - {{ payment.method || 'N/A' }}
                  </p>
                  <p class="text-xs text-gray-400">{{ formatDateTime(payment.created_at) }}</p>
                </div>
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
            <span>{{ props.record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.invoices.items') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

            <span class="font-semibold">{{ formatCurrency(props.record.data?.total) }} {{ $t('gestlab.general.labels.invoices.total') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <div :class="[
              'h-3 w-3 rounded-full',
              props.record.data?.amount_due === 0 ? 'bg-green-500' :
              props.record.data?.amount_due < props.record.data?.total ? 'bg-yellow-500' :
              'bg-blue-500'
            ]"></div>
            <span>{{ formatCurrency(props.record.data?.amount_due) }} {{ $t('gestlab.general.labels.invoices.due') }}</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="$router.back()"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.back') }}
        </button>
        
        <button 
          v-if="canEdit"
          @click="editInvoice"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <PencilIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.edit') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import '../CommercialDocumentSurface.css';
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { 
  CreditCardIcon,
  UserIcon,
  BuildingOfficeIcon,
  DocumentTextIcon,
  CalculatorIcon,
  CurrencyEuroIcon,
  InformationCircleIcon,
  ClipboardDocumentCheckIcon,
  DocumentDuplicateIcon,
  DocumentArrowDownIcon,
  TagIcon,
  EnvelopeIcon,
  ArrowLeftIcon,
  PencilIcon
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object
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

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('pt-PT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatStatus = (status) => {
  const statusMap = {
    'draft': 'Rascunho',
    'pending': 'Pendente',
    'paid': 'Pago',
    'overdue': 'Vencido',
    'cancelled': 'Cancelado'
  };
  return statusMap[status] || status;
};

// Actions
const downloadPDF = () => {
  // window.open(`/invoices/${props.record.data?.id}/pdf`, '_blank');
  window.open(route('invoices.getPDF', { id: props.record.data?.id}), '_blank');
};

const sendEmail = () => {
  router.visit(`/invoices/${props.record.data?.id}/send-email`);
};

const recordPayment = () => {
  router.visit(`/invoices/${props.record.data?.id}/payments/create`);
};

const duplicateInvoice = () => {
  router.visit(`/invoices/${props.record.data?.id}/duplicate`);
};

const editInvoice = () => {
  router.visit(`/invoices/${props.record.data?.id}/edit`);
};

// Check if user can edit (based on status)
const canEdit = computed(() => {
  return ['draft', 'pending'].includes(props.record.data?.status);
});
</script>
