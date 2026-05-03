<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-white shadow-lg shadow-blue-950/20 dark:bg-blue-500 dark:text-slate-950">
            <DocumentDuplicateIcon class="h-6 w-6" />
          </div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.export_certificates.page_title') }}
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.export_certificates.page_view_description') }}
            <span v-if="record.data?.exporter" class="font-semibold text-blue-900 dark:text-blue-300">
              {{ record.data?.exporter }}
            </span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20">
            {{ record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.export_certificates.products') }}
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            record.data?.invoiced ? 'bg-green-100 text-green-800 ring-green-700/10 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20' : 'bg-gray-100 text-gray-800 ring-gray-700/10 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600/20'
          ]">
            {{ record.data?.invoiced ? $t('gestlab.general.labels.export_certificates.invoiced') : $t('gestlab.general.labels.export_certificates.not_invoiced') }}
          </span>
        </div>
      </div>
      
      <!-- CERTIFICATE META INFO -->
      <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-200 pt-6 md:grid-cols-4 dark:border-slate-800">
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.export_certificates.cert_no') }}</p>
          <p class="text-sm font-semibold text-blue-900 dark:text-blue-300">{{ record.data?.cert_no }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.export_certificates.expedition_date') }}</p>
          <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ formatDate(record.data?.expedition_date) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.export_certificates.date') }}</p>
          <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ formatDate(record.data?.date) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.export_certificates.total_quantity') }}</p>
          <p class="text-sm font-bold text-blue-900 dark:text-blue-300">{{ formatQuantity(totalQuantity) }}</p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CERTIFICATE DETAILS SECTION -->
        <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.export_certificates.certificate_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- EXPORTER & WAREHOUSE -->
              <div class="space-y-6">
                <!-- EXPORTER -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.export_certificates.exporter_id') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ record.data?.exporter }}</p>
                    <div v-if="record.data?.exporter" class="mt-2 space-y-1">
                      <p v-if="record.data?.exporter.email" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.email') }}: {{ record.data?.exporter.email }}
                      </p>
                      <p v-if="record.data?.exporter.phone" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.phone') }}: {{ record.data?.exporter.phone }}
                      </p>
                      <p v-if="record.data?.exporter.address" class="text-xs text-gray-600">
                        {{ $t('gestlab.general.labels.address') }}: {{ record.data?.exporter.address }}
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- WAREHOUSE -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <BuildingOfficeIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.export_certificates.exporter_warehouse_id') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">{{ record.data?.exporter_warehouse || $t('gestlab.general.labels.export_certificates.no_warehouse') }}</p>
                    <div v-if="record.data?.warehouse" class="mt-2 space-y-1">
                      <p v-if="record.data?.warehouse.city" class="text-xs text-gray-600">
                        {{ record.data?.warehouse.city }}{{ record.data?.warehouse.postal_code ? ', ' + record.data?.warehouse.postal_code : '' }}
                      </p>
                      <p v-if="record.data?.warehouse.country" class="text-xs text-gray-600">
                        {{ record.data?.warehouse.country }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- TRANSPORT & INVOICE -->
              <div class="space-y-6">
                <!-- TRANSPORT TYPE -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.export_certificates.transport_information') }}
                  </h3>
                  <div class="space-y-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.export_certificates.trans_type_id') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-900">{{ record.data?.trans_type || $t('gestlab.general.labels.export_certificates.not_specified') }}</p>
                      </div>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.export_certificates.expedition_location') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-900">{{ record.data?.expedition_location || $t('gestlab.general.labels.export_certificates.not_specified') }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- INVOICE -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.export_certificates.invoice_information') }}
                  </h3>
                  <div class="space-y-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.export_certificates.invoice_status') }}
                      </label>
                      <div :class="[
                        'rounded-lg p-3 border',
                        record.data?.invoiced ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'
                      ]">
                        <p class="text-sm font-medium" :class="record.data?.invoiced ? 'text-green-700' : 'text-gray-700'">
                          {{ record.data?.invoiced ? $t('gestlab.general.labels.export_certificates.invoiced') : $t('gestlab.general.labels.export_certificates.not_invoiced') }}
                        </p>
                      </div>
                    </div>
                    
                    <div v-if="record.data?.invoice">
                      <label class="block text-xs font-medium text-gray-700 mb-1">
                        {{ $t('gestlab.general.labels.export_certificates.linked_invoice') }}
                      </label>
                      <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm font-semibold text-blue-900">{{ record.data?.invoice.inv_no }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                          {{ $t('gestlab.general.labels.total') }}: {{ formatCurrency(record.data?.invoice.total) }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ORIGIN & DESTINATION -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="text-sm font-semibold text-gray-900 mb-4">
                {{ $t('gestlab.general.labels.export_certificates.route_information') }}
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ORIGIN -->
                <div class="space-y-4">
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                      <ArrowUpIcon class="h-4 w-4 text-green-900" />
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.export_certificates.origin') }}
                    </h4>
                  </div>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="space-y-2">
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.export_certificates.country_origin_id') }}</p>
                        <p class="text-sm font-semibold text-gray-900">{{ record.data?.country_origin || $t('gestlab.general.labels.export_certificates.not_specified') }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.export_certificates.origin_city') }}</p>
                        <p class="text-sm text-gray-900">{{ record.data?.origin_city || $t('gestlab.general.labels.export_certificates.not_specified') }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- DESTINATION -->
                <div class="space-y-4">
                  <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <ArrowDownIcon class="h-4 w-4 text-blue-900" />
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.export_certificates.destination') }}
                    </h4>
                  </div>
                  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="space-y-2">
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.export_certificates.country_destination_id') }}</p>
                        <p class="text-sm font-semibold text-gray-900">{{ record.data?.country_destination || $t('gestlab.general.labels.export_certificates.not_specified') }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.export_certificates.destination_city') }}</p>
                        <p class="text-sm text-gray-900">{{ record.data?.destination_city || $t('gestlab.general.labels.export_certificates.not_specified') }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PRODUCTS SECTION -->
        <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                <BeakerIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
                {{ $t('gestlab.general.labels.export_certificates.products') }}
                <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                  ({{ record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.export_certificates.items') }})
                </span>
              </h2>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!record.data?.items || record.data?.items.length === 0" class="p-12 text-center">
            <BeakerIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
            <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.buttons.no_items') }}
            </h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.export_certificates.no_products_description') }}
            </p>
          </div>

          <!-- PRODUCTS TABLE -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.export_certificates.product_id') }}
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.export_certificates.quantity') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr 
                  v-for="(item, index) in record.data?.items" 
                  :key="index"
                  class="hover:bg-gray-50 transition-colors duration-150"
                >

                  <!-- Product Name -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                    {{ item.product }}
                  </td>

                  <!-- Quantity -->
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">
                    <div class="flex items-center justify-center gap-2">
                      <span class="font-semibold">{{ formatQuantity(item.qty) }}</span>
                      <span class="text-sm text-gray-400">units</span>
                    </div>
                  </td>

                </tr>
              </tbody>

              <!-- SUMMARY -->
              <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                  <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.export_certificates.total_products') }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-blue-900 text-center">
                    {{ record.data?.items?.length || 0 }}
                  </td>
                  <td></td>
                </tr>
                <tr class="bg-blue-50">
                  <td class="whitespace-nowrap py-4 pl-6 pr-3 text-lg font-bold text-gray-900 text-right">
                    {{ $t('gestlab.general.labels.export_certificates.total_quantity') }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-center">
                    {{ formatQuantity(totalQuantity) }}
                  </td>
                  <td></td>
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
                {{ $t('gestlab.general.labels.export_certificates.obs') }}
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
                {{ $t('gestlab.general.labels.export_certificates.attached_file') }}
              </label>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-3">
                  <DocumentIcon class="h-8 w-8 text-blue-900" />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ getFileName(record.data?.file) }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ $t('gestlab.general.labels.export_certificates.certificate_document') }}
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
            {{ $t('gestlab.general.labels.export_certificates.actions') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="downloadPDF"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentArrowDownIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.export_certificates.download_pdf') }}
            </button>
            
            <!-- <button 
              @click="sendEmail"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-green-500 text-white hover:from-green-500 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-all duration-200"
            >
              <EnvelopeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.export_certificates.send_email') }}
            </button> -->

            <button 
              v-if="!record.data?.invoiced"
              @click="linkToInvoice"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 text-white hover:from-indigo-500 hover:to-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition-all duration-200"
            >
              <CreditCardIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.export_certificates.link_to_invoice') }}
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
              {{ $t('gestlab.general.labels.export_certificates.duplicate') }}
            </button> -->

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.export_certificates.products') }}</span>
                  <span class="font-semibold text-blue-900">{{ record.data?.items?.length || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.export_certificates.total_quantity') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatQuantity(totalQuantity) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.export_certificates.cert_no') }}</span>
                  <span class="font-semibold text-blue-900">{{ record.data?.cert_no }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.export_certificates.status') }}</span>
                  <span :class="[
                    'font-semibold',
                    record.data?.invoiced ? 'text-green-600' : 'text-yellow-600'
                  ]">
                    {{ record.data?.invoiced ? $t('gestlab.general.labels.export_certificates.invoiced') : $t('gestlab.general.labels.export_certificates.pending') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- AUTHORIZED PERSONNEL CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <UserCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.export_certificates.authorized_personnel') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.export_certificates.authorized_personnel') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ record.data?.authorized_personnel || $t('gestlab.general.labels.export_certificates.not_specified') }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.export_certificates.created_by') }}</span>
              <span class="text-sm text-gray-900">{{ record.data?.user || 'N/A' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.export_certificates.created_at') }}</span>
              <span class="text-sm text-gray-900">{{ formatDateTime(record.data?.created_at) }}</span>
            </div>
            <div v-if="record.data?.updated_at !== record.data?.created_at" class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.export_certificates.updated_at') }}</span>
              <span class="text-sm text-gray-900">{{ formatDateTime(record.data?.updated_at) }}</span>
            </div>
          </div>
        </div>

        <!-- EXTRA DATA CARD -->
        <div v-if="record.data?.extra_data && Object.keys(record.data?.extra_data).length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.export_certificates.additional_information') }}
          </h3>
          <div class="space-y-3">
            <div 
              v-for="(value, key) in record.data?.extra_data" 
              :key="key"
              class="flex items-start justify-between"
            >
              <span class="text-sm text-gray-600 capitalize">{{ formatKey(key) }}</span>
              <span class="text-sm text-gray-900 text-right max-w-[60%] break-words">{{ value }}</span>
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
            <span>{{ record.data?.items?.length || 0 }} {{ $t('gestlab.general.labels.export_certificates.products') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <CubeIcon class="h-4 w-4 text-gray-400" />
            <span class="font-semibold">{{ formatQuantity(totalQuantity) }} {{ $t('gestlab.general.labels.export_certificates.units') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <DocumentDuplicateIcon class="h-4 w-4 text-gray-400" />
            <span>{{ record.data?.cert_no }}</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <Link
        as="button"
        :href="route('exportcertificates.index')" 
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.back') }}
      </Link>
        
        <Link
          as="button"
          :href="route('exportcertificates.edit', { exportcertificate: record.data?.id })" 
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
import { ref, computed } from "vue";
import { router, Link } from "@inertiajs/vue3";
import { 
  DocumentDuplicateIcon,
  DocumentTextIcon,
  DocumentIcon,
  DocumentArrowDownIcon,
  UserIcon,
  BuildingOfficeIcon,
  BeakerIcon,
  InformationCircleIcon,
  CubeIcon,
  ArrowUpIcon,
  ArrowDownIcon,
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

const formatKey = (key) => {
  return key.replace(/_/g, ' ');
};

const getFileName = (filePath) => {
  if (!filePath) return '';
  const parts = filePath.split('/');
  return parts[parts.length - 1];
};

// Actions
const downloadPDF = () => {
  window.open(route('exportcertificates.getPDF', { id: props.record.data.id }), '_blank');
};

const downloadFile = () => {
  if (props.record.data?.file) {
    window.open(props.record.data?.file, '_blank');
  }
};

const sendEmail = () => {
  router.visit(`/export-certificates/${props.record.data?.id}/send-email`);
};

const linkToInvoice = () => {

  router.get(route('exportcertificates.getIssueInvoiceModal', { id: props.record.data?.id}));
};

const showInvoice = () => {

  router.get(route('invoices.show', { id: props.record.data?.invoice_id}));
};

const duplicateCertificate = () => {
  router.visit(`/export-certificates/${props.record.data?.id}/duplicate`);
};

// Check if user can edit (based on status)
const canEdit = computed(() => {
  return !props.record.data?.invoiced;
});
</script>
