<template>
  <div class="commercial-document-page space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.quotes.page_view_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.quotes.page_view_description') }}
            <span v-if="props.record.data?.customer" class="font-semibold text-blue-900">
              {{ props.record.data?.customer }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ props.record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.quotes.items') }}
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            props.record.data?.status === 'approved' ? 'bg-green-100 text-green-800 ring-green-700/10' :
            props.record.data?.status === 'pending' ? 'bg-yellow-100 text-yellow-800 ring-yellow-700/10' :
            props.record.data?.status === 'rejected' ? 'bg-red-100 text-red-800 ring-red-700/10' :
            'bg-gray-100 text-gray-800 ring-gray-700/10'
          ]">
            {{ formatStatus(props.record.data?.status) }}
          </span>
        </div>
      </div>
      
      <!-- QUOTE META INFO -->
      <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.quotes.quote_no') }}</p>
          <p class="text-sm font-semibold text-blue-900">{{ props.record.data?.quote_no }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.quotes.created_at') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ formatDate(props.record.data?.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.quotes.due_date') }}</p>
          <p class="text-sm font-medium text-gray-700">{{ formatDate(props.record.data?.due_date) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.quotes.total_amount') }}</p>
          <p class="text-sm font-bold text-blue-900 flex items-center gap-1">
            <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="h-4 w-4 mr-2">AOA</p>

            {{ formatCurrency(props.record.data?.total) }}
          </p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CUSTOMER & WAREHOUSE SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.quotes.customer_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CUSTOMER INFO -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1 mb-2">
                    <UserIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.quotes.customer_id') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ props.record.data?.customer }}</p>
                    <p v-if="props.record.data?.customer?.email" class="text-xs text-gray-500 mt-1">{{ props.record.data?.customer.email }}</p>
                    <p v-if="props.record.data?.customer?.phone" class="text-xs text-gray-500">{{ props.record.data?.customer.phone }}</p>
                  </div>
                </div>
                
                <!-- INTERNAL REFERENCE -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $t('gestlab.general.labels.quotes.internal_ref') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <p class="text-sm text-gray-900">{{ props.record.data?.internal_ref || $t('gestlab.general.labels.quotes.no_reference') }}</p>
                  </div>
                </div>
              </div>

              <!-- WAREHOUSE INFO -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1 mb-2">
                    <BuildingOfficeIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.quotes.warehouse_id') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ props.record.data?.warehouse }}</p>
                    <p v-if="props.record.data?.warehouse?.city" class="text-xs text-gray-500 mt-1">
                      {{ props.record.data?.warehouse.city }}{{ props.record.data?.warehouse?.postal_code ? ', ' + props.record.data?.warehouse.postal_code : '' }}
                    </p>
                    <p v-if="props.record.data?.warehouse?.country" class="text-xs text-gray-500">{{ props.record.data?.warehouse.country }}</p>
                  </div>
                </div>

                <!-- LAB CODE -->
                <div v-if="props.record.data?.lab_code">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $t('gestlab.general.labels.quotes.labcode_id') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <p class="text-sm font-semibold text-blue-900">{{ props.record.data?.lab_code.code }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- PRICING MODE -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex items-center gap-3">
                <TagIcon class="h-5 w-5 text-gray-500" />
                <div>
                  <h3 class="text-sm font-medium text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.pricing_mode') }}
                  </h3>
                  <p class="text-xs text-gray-500">
                    {{ props.record.data?.use_matrix_price ? 
                       $t('gestlab.general.labels.quotes.matrix_pricing') : 
                       $t('gestlab.general.labels.quotes.parameter_pricing') }}
                  </p>
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
                {{ $t('gestlab.general.labels.quotes.items') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ props.record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.quotes.items') }})
                </span>
              </h2>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!props.record.data?.items || props.record.data?.items.length === 0" class="p-12 text-center">
            <CalculatorIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.quotes.no_items') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.quotes.no_items_description') }}
            </p>
          </div>

          <!-- QUOTE ITEMS TABLE -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.item_id') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.qty') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.unit_price') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.discount') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.total') }}
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
                  <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm">
                    <div>
                      <p class="font-medium text-gray-900">{{ item.item_description }}</p>
                      <p v-if="item.obs" class="text-xs text-gray-500 mt-1">{{ item.obs }}</p>
                      <p class="text-xs text-gray-400 mt-1">
                        {{ item.exemption_code ? 'Isenção: ' + item.exemption_code : '' }}
                      </p>
                    </div>
                  </td>

                  <!-- Quantity & Unit -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">
                    <div>
                      <p class="font-medium">{{ item.qty }}</p>
                      <p v-if="item.unit" class="text-xs text-gray-400">{{ item.unit.code }}</p>
                    </div>
                  </td>

                  <!-- Unit Price -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(item.unit_price) }}
                    </div>
                  </td>

                  <!-- Discount -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
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

                  <!-- Total -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                      {{ formatCurrency(item.total) }}
                    </div>
                  </td>
                </tr>
              </tbody>

              <!-- QUOTE SUMMARY -->
              <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                  <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.quotes.subtotal') }}
                  </td>
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
                    {{ $t('gestlab.general.labels.quotes.discount_total') }}
                  </td>
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
                    {{ $t('gestlab.general.labels.quotes.tax_total') }}
                  </td>
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
                    {{ $t('gestlab.general.labels.quotes.total') }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                                    <p class="mr-2">AOA</p>

                      {{ formatCurrency(props.record.data?.total) }}
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
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
              <InformationCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.quotes.obs') }}
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
              {{ $t('gestlab.general.labels.quotes.download_pdf') }}
            </button>

            <button
              v-if="!props.record.data?.converted_to_invoice" 
              @click="convertToInvoice"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 transition-all duration-200"
            >
              <ArrowsRightLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.quotes.convert_to_invoice') }}
            </button>

            <button
              v-if="props.record.data?.converted_to_invoice" 
              @click="viewInvoice"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 transition-all duration-200"
            >
              <ArrowsRightLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.quotes.view_invoice') }}
            </button>
            
            <button 
              @click="sendEmail"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-green-500 text-white hover:from-green-500 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-all duration-200"
            >
              <EnvelopeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.quotes.send_email') }}
            </button>

            <button 
              @click="duplicateQuote"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-gray-700 to-gray-600 text-white hover:from-gray-600 hover:to-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.quotes.duplicate') }}
            </button>

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.quotes.items') }}</span>
                  <span class="font-semibold text-blue-900">{{ props.record.data?.items?.length || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.quotes.subtotal') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(props.record.data?.sub_total) }}</span>
                </div>
                <div v-if="props.record.data?.discount > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.quotes.discount') }}</span>
                  <span class="font-semibold text-red-600">-{{ formatCurrency(props.record.data?.discount) }}</span>
                </div>
                <div v-if="props.record.data?.tax > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.quotes.tax') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatCurrency(props.record.data?.tax) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.quotes.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.quotes.current_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                props.record.data?.status === 'approved' ? 'bg-green-100 text-green-800' :
                props.record.data?.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                props.record.data?.status === 'rejected' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
              ]">
                {{ formatStatus(props.record.data?.status) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.quotes.created_by') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ props.record.data?.user || 'N/A' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.quotes.created_on') }}</span>
              <span class="text-sm text-gray-900">{{ formatDate(props.record.data?.created_at) }}</span>
            </div>
            <div v-if="props.record.data?.updated_at !== props.record.data?.created_at" class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.quotes.last_updated') }}</span>
              <span class="text-sm text-gray-900">{{ formatDate(props.record.data?.updated_at) }}</span>
            </div>
          </div>
        </div>

        <!-- TIMELINE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.quotes.timeline') }}
          </h3>
          <div class="space-y-4">
            <div class="relative">
              <!-- Created -->
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <CalendarIcon class="h-4 w-4 text-blue-900" />
                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900">
                    {{ $t('gestlab.general.labels.quotes.created') }}
                  </p>
                  <p class="text-xs text-gray-500">{{ formatDate(props.record.data?.created_at) }}</p>
                </div>
              </div>
              
              <!-- Status updates -->
              <div v-if="props.record.data?.status_history" class="mt-4 space-y-4">
                <div 
                  v-for="(history, index) in props.record.data?.status_history" 
                  :key="index"
                  class="flex items-start gap-3"
                >
                  <div class="flex-shrink-0">
                    <div :class="[
                      'h-8 w-8 rounded-full flex items-center justify-center',
                      history.status === 'approved' ? 'bg-green-100' :
                      history.status === 'rejected' ? 'bg-red-100' :
                      'bg-yellow-100'
                    ]">
                      <ClipboardDocumentCheckIcon :class="[
                        'h-4 w-4',
                        history.status === 'approved' ? 'text-green-900' :
                        history.status === 'rejected' ? 'text-red-900' :
                        'text-yellow-900'
                      ]" />
                    </div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                      {{ formatStatus(history.status) }}
                    </p>
                    <p v-if="history.notes" class="text-xs text-gray-600">{{ history.notes }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(history.created_at) }}</p>
                  </div>
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
            <span>{{ props.record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.quotes.items') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

            <span class="font-semibold">{{ formatCurrency(props.record.data?.total) }} {{ $t('gestlab.general.labels.quotes.total') }}</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <Link
          :href="route('quotes.index')" 
          as="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.back') }}
      </Link>
        
        <Link
          as="button" 
          v-if="canEdit"
          :href="route('quotes.edit', { quote: props.record.data?.id })"
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
import '../CommercialDocumentSurface.css';
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { 
  DocumentTextIcon,
  UserIcon,
  BuildingOfficeIcon,
  CalculatorIcon,
  CurrencyEuroIcon,
  InformationCircleIcon,
  ClipboardDocumentCheckIcon,
  DocumentDuplicateIcon,
  DocumentArrowDownIcon,
  CalendarIcon,
  ArrowLeftIcon,
  PencilIcon,
  TagIcon,
  EnvelopeIcon,
  ArrowsRightLeftIcon
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

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
};

const formatStatus = (status) => {
  const statusMap = {
    'pending': 'Pending',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'draft': 'Draft'
  };
  return statusMap[status] || status;
};

// Actions
const downloadPDF = () => {
  // Implement PDF download logic
  window.open(route('quotes.getPDF', { id: props.record.data?.id}), '_blank');
};

const sendEmail = () => {
  // Implement email sending logic
  router.visit(`/quotes/${props.record.data?.id}/send-email`);
};

const duplicateQuote = () => {
  // Implement duplicate logic
  router.visit(`/quotes/${props.record.data?.id}/duplicate`);
};

const editQuote = () => {
  router.visit(`/quotes/${props.record.data?.id}/edit`);
};

const convertToInvoice = () => {
  router.get(route('quotes.getConvertToInvoiceModal', { id: props.record.data?.id }));
}

const viewInvoice = () => {
  router.get(route('invoices.show', { id: props.record.data?.invoice_id }));
}

// Check if user can edit (based on status)
const canEdit = computed(() => {
  return ['pending', 'draft'].includes(props.record.data?.status);
});
</script>
