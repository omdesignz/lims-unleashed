<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ReceiptRefundIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.credit_notes.view_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.credit_notes.page_view_description') }}
            <span v-if="creditNote.customer" class="font-semibold text-blue-900">
              {{ creditNote.customer.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ creditNote.items?.length || 0 }} {{ $t('gestlab.general.labels.credit_notes.items') }}
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            creditNote.status === 'processed' ? 'bg-green-100 text-green-800 ring-green-700/10' :
            creditNote.status === 'pending' ? 'bg-yellow-100 text-yellow-800 ring-yellow-700/10' :
            creditNote.status === 'cancelled' ? 'bg-red-100 text-red-800 ring-red-700/10' :
            'bg-gray-100 text-gray-800 ring-gray-700/10'
          ]">
            {{ formatStatus(creditNote.status) }}
          </span>
        </div>
      </div>
      
      <!-- CREDIT NOTE META INFO -->
      <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-2 md:grid-cols-5 gap-4">
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.credit_notes.credit_note_number') }}</p>
          <p class="text-sm font-semibold text-blue-900">{{ creditNote.id }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.credit_notes.source_invoice') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ creditNote.invoice?.inv_no || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.credit_notes.created_at') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ formatDate(creditNote.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.credit_notes.reason') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ creditNote.reason || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.credit_notes.total_amount') }}</p>
          <p class="text-sm font-bold text-blue-900 flex items-center gap-1">
            <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

            {{ formatCurrency(creditNote.total) }}
          </p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CREDIT NOTE DETAILS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.credit_notes.credit_note_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- SOURCE INVOICE & CUSTOMER -->
              <div class="space-y-6">
                <!-- SOURCE INVOICE -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <CreditCardIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.credit_notes.source_invoice_info') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm font-semibold text-gray-900">{{ creditNote.invoice?.inv_no || 'N/A' }}</p>
                        <p v-if="creditNote.invoice" class="text-xs text-gray-500 mt-1">
                          {{ formatDate(creditNote.invoice.created_at) }}
                        </p>
                        <p v-if="creditNote.invoice?.total" class="text-xs text-gray-500">
                          {{ $t('gestlab.general.labels.invoice.total') }}: {{ formatCurrency(creditNote.invoice.total) }}
                        </p>
                      </div>
                      <div v-if="creditNote.invoice" class="text-right">
                        <span :class="[
                          'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                          creditNote.invoice.status === 'paid' ? 'bg-green-100 text-green-800' :
                          creditNote.invoice.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-gray-100 text-gray-800'
                        ]">
                          {{ formatInvoiceStatus(creditNote.invoice.status) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- CUSTOMER INFO -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.credit_notes.customer_info') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ creditNote.customer?.name }}</p>
                    <div v-if="creditNote.customer" class="mt-2 space-y-1">
                      <p v-if="creditNote.customer.email" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.email') }}: {{ creditNote.customer.email }}
                      </p>
                      <p v-if="creditNote.customer.phone" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.phone') }}: {{ creditNote.customer.phone }}
                      </p>
                      <p v-if="creditNote.customer.vat_number" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.vat') }}: {{ creditNote.customer.vat_number }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- CREDIT NOTE SETTINGS -->
              <div class="space-y-6">
                <!-- CREDIT NOTE INFO -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.credit_notes.credit_note_info') }}
                  </h3>
                  <div class="space-y-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.credit_notes.reason') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm font-medium text-gray-900">{{ creditNote.reason || $t('gestlab.general.labels.credit_notes.no_reason') }}</p>
                      </div>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.credit_notes.internal_ref') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-900">{{ creditNote.internal_ref || $t('gestlab.general.labels.credit_notes.no_reference') }}</p>
                      </div>
                    </div>

                    <div v-if="creditNote.warehouse">
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.credit_notes.warehouse') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-900">{{ creditNote.warehouse.address }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- PRICING MODE -->
                <div class="flex items-center gap-3">
                  <TagIcon class="h-5 w-5 text-gray-500" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.credit_notes.pricing_mode') }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ creditNote.use_matrix_price ? 
                         $t('gestlab.general.labels.credit_notes.matrix_pricing') : 
                         $t('gestlab.general.labels.credit_notes.parameter_pricing') }}
                    </p>
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
                {{ $t('gestlab.general.labels.credit_notes.credited_items') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ creditNote.items?.length || 0 }} {{ $t('gestlab.general.labels.credit_notes.items') }})
                </span>
              </h2>
            </div>
            <p class="mt-1 text-sm text-gray-600">
              {{ $t('gestlab.general.labels.credit_notes.credited_items_description') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!creditNote.items || creditNote.items.length === 0" class="p-12 text-center">
            <ReceiptRefundIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.buttons.no_items') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.credit_notes.no_items_description') }}
            </p>
          </div>

          <!-- CREDIT NOTE ITEMS TABLE -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.credit_notes.item_description') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.credit_notes.credited_qty') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.credit_notes.unit_price') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.credit_notes.discount') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.credit_notes.tax') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.credit_notes.line_total') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr 
                  v-for="(item, index) in creditNote.items" 
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
                          {{ $t('gestlab.general.labels.credit_notes.exemption') }}: {{ item.exemption_code }}
                        </p>
                      </div>
                      <p v-if="item.unit" class="text-xs text-gray-400 mt-1">
                        {{ item.unit.code }}
                      </p>
                    </div>
                  </td>

                  <!-- Quantity -->
                  <td class="px-3 py-4 text-sm text-gray-500 text-center">
                    <div>
                      <p class="font-medium">{{ item.qty }}</p>
                      <p v-if="item.original_qty" class="text-xs text-gray-400">
                        {{ $t('gestlab.general.labels.credit_notes.original') }}: {{ item.original_qty }}
                      </p>
                    </div>
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

              <!-- CREDIT NOTE SUMMARY -->
              <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.credit_notes.subtotal') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(creditNote.sub_total) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="creditNote.discount > 0">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.credit_notes.discount_total') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-red-600 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      -{{ formatCurrency(creditNote.discount) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="creditNote.tax > 0">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.credit_notes.tax_total') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(creditNote.tax) }}
                    </div>
                  </td>
                </tr>
                <tr class="bg-blue-50">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-lg font-bold text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.credit_notes.total_credited') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                      {{ formatCurrency(creditNote.total) }}
                    </div>
                  </td>
                </tr>
                <tr v-if="creditNote.invoice" class="bg-gradient-to-r from-gray-50 to-white">
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.credit_notes.original_invoice_total') }}
                  </td>
                  <td></td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(creditNote.invoice.total) }}
                    </div>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- OBSERVATIONS SECTION -->
        <div v-if="creditNote.obs" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2 mb-3">
              <InformationCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.credit_notes.observations') }}
            </label>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ creditNote.obs }}</p>
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
              {{ $t('gestlab.general.buttons.download_pdf') }}
            </button>
            
            <button 
              @click="applyToInvoice"
              :disabled="!canApply"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold transition-all duration-200',
                canApply
                  ? 'bg-gradient-to-r from-green-600 to-green-500 text-white hover:from-green-500 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2'
                  : 'bg-gray-200 text-gray-500 cursor-not-allowed'
              ]"
            >
              <CreditCardIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.apply_to_invoice') }}
            </button>

            <button 
              @click="sendEmail"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 text-white hover:from-indigo-500 hover:to-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition-all duration-200"
            >
              <EnvelopeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.send_email') }}
            </button>

            <button 
              @click="duplicateCreditNote"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-gray-700 to-gray-600 text-white hover:from-gray-600 hover:to-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.duplicate') }}
            </button>

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.items') }}</span>
                  <span class="font-semibold text-blue-900">{{ creditNote.items?.length || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.subtotal') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(creditNote.sub_total) }}</span>
                </div>
                <div v-if="creditNote.discount > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.discount') }}</span>
                  <span class="font-semibold text-red-600">-{{ formatCurrency(creditNote.discount) }}</span>
                </div>
                <div v-if="creditNote.tax > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.tax') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(creditNote.tax) }}</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-gray-200 pt-2">
                  <span class="text-gray-900">{{ $t('gestlab.general.labels.credit_notes.total') }}</span>
                  <span class="text-blue-900">{{ formatCurrency(creditNote.total) }}</span>
                </div>
                <div v-if="creditNote.invoice" class="flex justify-between text-sm pt-2">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.source_invoice_total') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(creditNote.invoice.total) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CREDIT STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.credit_notes.credit_status') }}
          </h3>
          <div class="space-y-4">
            <!-- Status Indicator -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.credit_notes.status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                creditNote.status === 'processed' ? 'bg-green-100 text-green-800' :
                creditNote.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                creditNote.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
              ]">
                {{ formatStatus(creditNote.status) }}
              </span>
            </div>

            <!-- Application Status -->
            <div v-if="creditNote.applied_to_invoice">
              <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.applied_to_invoice') }}</span>
                <span class="font-semibold text-green-600">{{ creditNote.applied_invoice?.inv_no || 'N/A' }}</span>
              </div>
              <p class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.credit_notes.applied_on') }}: {{ formatDate(creditNote.applied_at) }}
              </p>
            </div>
            <div v-else-if="canApply">
              <div class="flex items-center gap-2 text-sm">
                <ExclamationCircleIcon class="h-5 w-5 text-yellow-500" />
                <span class="text-yellow-700">{{ $t('gestlab.general.labels.credit_notes.ready_to_apply') }}</span>
              </div>
            </div>

            <!-- Credit Details -->
            <div class="space-y-2 pt-2 border-t border-gray-200">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.credit_amount') }}</span>
                <span class="font-semibold text-blue-900">{{ formatCurrency(creditNote.total) }}</span>
              </div>
              <div v-if="creditNote.invoice" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.credit_notes.invoice_balance') }}</span>
                <span class="font-semibold text-gray-900">{{ formatCurrency(creditNote.invoice.amount_due) }}</span>
              </div>
              <div v-if="creditNote.invoice && canApply" class="flex justify-between text-sm font-semibold">
                <span class="text-gray-900">{{ $t('gestlab.general.labels.credit_notes.new_balance') }}</span>
                <span class="text-green-600">{{ formatCurrency(creditNote.invoice.amount_due - creditNote.total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- AUDIT TRAIL CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.credit_notes.audit_trail') }}
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
                  {{ $t('gestlab.general.labels.credit_notes.created_by') }}
                </p>
                <p class="text-xs text-gray-500">{{ creditNote.user?.name || 'N/A' }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(creditNote.created_at) }}</p>
              </div>
            </div>
            
            <!-- Source Invoice -->
            <div v-if="creditNote.invoice" class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                  <CreditCardIcon class="h-4 w-4 text-gray-700" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.credit_notes.source_invoice') }}
                </p>
                <p class="text-xs text-gray-500">{{ creditNote.invoice.inv_no }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(creditNote.invoice.created_at) }}</p>
              </div>
            </div>

            <!-- Last Updated -->
            <div v-if="creditNote.updated_at !== creditNote.created_at" class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                  <PencilIcon class="h-4 w-4 text-gray-700" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.credit_notes.last_updated') }}
                </p>
                <p class="text-xs text-gray-500">{{ formatDateTime(creditNote.updated_at) }}</p>
              </div>
            </div>

            <!-- Application Record -->
            <div v-if="creditNote.applied_to_invoice" class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                  <CheckCircleIcon class="h-4 w-4 text-green-900" />
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.credit_notes.applied') }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.to_invoice') }}: {{ creditNote.applied_invoice?.inv_no || 'N/A' }}
                </p>
                <p class="text-xs text-gray-400">{{ formatDateTime(creditNote.applied_at) }}</p>
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
            <span>{{ creditNote.items?.length || 0 }} {{ $t('gestlab.general.labels.credit_notes.items') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

            <span class="font-semibold">{{ formatCurrency(creditNote.total) }} {{ $t('gestlab.general.labels.credit_notes.credited') }}</span>
          </div>
          <div v-if="creditNote.invoice" class="flex items-center gap-2">
            <CreditCardIcon class="h-4 w-4 text-gray-400" />
            <span class="text-sm">{{ $t('gestlab.general.labels.from_invoice') }}: {{ creditNote.invoice.inv_no }}</span>
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
          @click="editCreditNote"
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
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { 
  ReceiptRefundIcon,
  UserIcon,
  BuildingOfficeIcon,
  DocumentDuplicateIcon,
  CalculatorIcon,
  CurrencyEuroIcon,
  InformationCircleIcon,
  ClipboardDocumentCheckIcon,
  CreditCardIcon,
  DocumentArrowDownIcon,
  TagIcon,
  EnvelopeIcon,
  ExclamationCircleIcon,
  ArrowLeftIcon,
  PencilIcon,
  CheckCircleIcon
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout
});

const props = defineProps({
  creditNote: {
    type: Object,
    required: true
  }
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
    'processed': 'Processada',
    'applied': 'Aplicada',
    'cancelled': 'Cancelada'
  };
  return statusMap[status] || status;
};

const formatInvoiceStatus = (status) => {
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
  window.open(`/creditnotes/${props.creditNote.id}/pdf`, '_blank');
};

const sendEmail = () => {
  router.visit(`/creditnotes/${props.creditNote.id}/send-email`);
};

const applyToInvoice = () => {
  if (canApply.value) {
    router.visit(`/creditnotes/${props.creditNote.id}/apply`);
  }
};

const duplicateCreditNote = () => {
  router.visit(`/creditnotes/${props.creditNote.id}/duplicate`);
};

const editCreditNote = () => {
  router.visit(`/creditnotes/${props.creditNote.id}/edit`);
};

// Check if credit note can be edited
const canEdit = computed(() => {
  return ['draft', 'pending'].includes(props.creditNote.status);
});

// Check if credit note can be applied to invoice
const canApply = computed(() => {
  return props.creditNote.status === 'processed' && 
         props.creditNote.invoice && 
         props.creditNote.invoice.amount_due > 0 &&
         !props.creditNote.applied_to_invoice;
});
</script>