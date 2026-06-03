<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentDuplicateIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.export_certificates.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.export_certificates.page_create_description') }}
            <span v-if="form.exporter_id" class="font-semibold text-blue-900">
              {{ form.exporter_id.label }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.items.length }} {{ $t('gestlab.general.labels.export_certificates.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- CERTIFICATE SETTINGS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <DocumentTextIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.export_certificates.certificate_settings') }}
        </h2>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          <!-- Certificate Issue Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <CalendarIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.export_certificates.date') }}
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

          <!-- Expedition Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <CalendarIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.export_certificates.expedition_date') }}
            </label>
            <date-picker-enhanced 
              v-model.string="form.expedition_date" 
              locale="pt" 
              color="blue" 
              mode="date" 
              :masks="masks"
              class="w-full"
              :popover-placement="'bottom-start'"
            />
            <p v-if="form.errors.expedition_date" class="text-xs text-red-600">
              {{ form.errors.expedition_date }}
            </p>
          </div>

          <!-- Exporter -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <UserIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.export_certificates.exporter_id') }}
              <span class="text-red-500">*</span>
            </label>
            <comboboxEnhanced 
              :hasError="form.errors.exporter_id" 
              v-model="form.exporter_id" 
              :load-options="loadExporters"
              :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.exporter')"
            />
            <p v-if="form.errors.exporter_id" class="text-xs text-red-600">
              {{ form.errors.exporter_id }}
            </p>
          </div>

          <!-- Exporter Warehouse -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <BuildingOfficeIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.export_certificates.exporter_warehouse_id') }}
            </label>
            <comboboxEnhanced 
              :disableInput="!form.exporter_id || loadingWarehouses"
              :loading="loadingWarehouses"
              :hasError="form.errors.exporter_warehouse_id" 
              v-model="form.exporter_warehouse_id" 
              :load-options="loadWarehouses"
              :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.warehouse')"
            />
            <p v-if="form.errors.exporter_warehouse_id" class="text-xs text-red-600">
              {{ form.errors.exporter_warehouse_id }}
            </p>
          </div>

          <!-- Transport Type -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.export_certificates.trans_type_id') }}
            </label>
            <comboboxEnhanced 
              :hasError="form.errors.trans_type_id" 
              v-model="form.trans_type_id" 
              :load-options="loadTransportTypes"
              :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.transport_type')"
            />
            <p v-if="form.errors.trans_type_id" class="text-xs text-red-600">
              {{ form.errors.trans_type_id }}
            </p>
          </div>
        </div>

        <!-- ORIGIN & DESTINATION SECTION -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.export_certificates.origin_destination') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Country of Origin -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.export_certificates.country_origin_id') }}
              </label>
              <comboboxEnhanced 
                :hasError="form.errors.country_origin_id" 
                v-model="form.country_origin_id" 
                :load-options="loadCountries"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.country')"
              />
              <p v-if="form.errors.country_origin_id" class="text-xs text-red-600">
                {{ form.errors.country_origin_id }}
              </p>
            </div>

            <!-- Origin City -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.export_certificates.origin_city') }}
              </label>
              <input 
                v-model="form.origin_city" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.enter_origin_city')"
              />
              <p v-if="form.errors.origin_city" class="text-xs text-red-600">
                {{ form.errors.origin_city }}
              </p>
            </div>

            <!-- Country of Destination -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.export_certificates.country_destination_id') }}
              </label>
              <comboboxEnhanced 
                :hasError="form.errors.country_destination_id" 
                v-model="form.country_destination_id" 
                :load-options="loadCountries"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.country')"
              />
              <p v-if="form.errors.country_destination_id" class="text-xs text-red-600">
                {{ form.errors.country_destination_id }}
              </p>
            </div>

            <!-- Destination City -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.export_certificates.destination_city') }}
              </label>
              <input 
                v-model="form.destination_city" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.enter_destination_city')"
              />
              <p v-if="form.errors.destination_city" class="text-xs text-red-600">
                {{ form.errors.destination_city }}
              </p>
            </div>
          </div>
        </div>

        <!-- ADDITIONAL INFORMATION -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Expedition Location -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.export_certificates.expedition_location') }}
              </label>
              <input 
                v-model="form.expedition_location" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.enter_expedition_location')"
              />
              <p v-if="form.errors.expedition_location" class="text-xs text-red-600">
                {{ form.errors.expedition_location }}
              </p>
            </div>

            <!-- Authorized Personnel -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.export_certificates.authorized_personnel') }}
              </label>
              <input 
                v-model="form.authorized_personnel" 
                type="text" 
                class="block w-full rounded-md border-0 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.authorized_personnel')"
              />
              <p v-if="form.errors.authorized_personnel" class="text-xs text-red-600">
                {{ form.errors.authorized_personnel }}
              </p>
            </div>
          </div>

          <!-- Invoice Selection -->
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              {{ $t('gestlab.general.labels.export_certificates.invoice_id') }}
            </label>
            <div class="flex items-center gap-4">
              <comboboxEnhanced 
                :hasError="form.errors.invoice_id" 
                v-model="form.invoice_id" 
                :load-options="loadInvoices"
                :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.invoice')"
                class="flex-1"
              />
              <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input 
                    v-model="form.invoiced" 
                    type="checkbox" 
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  {{ $t('gestlab.general.labels.export_certificates.invoiced') }}
                </label>
              </div>
            </div>
            <p v-if="form.errors.invoice_id" class="text-xs text-red-600">
              {{ form.errors.invoice_id }}
            </p>
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
            {{ $t('gestlab.general.labels.export_certificates.products') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ form.items.length }} {{ $t('gestlab.general.labels.export_certificates.items') }})
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
          {{ $t('gestlab.general.labels.export_certificates.items_tagline') }}
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
                {{ $t('gestlab.general.labels.export_certificates.product') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.export_certificates.quantity') }}
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
                  :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.product')"
                  class="min-w-[300px]"
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
                  <span class="text-sm text-gray-500">{{ $t('gestlab.general.labels.export_certificates.units') }}</span>
                </div>
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
                {{ $t('gestlab.general.labels.export_certificates.total_products') }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-blue-900 text-center">
                {{ form.items.length }}
              </td>
              <td></td>
            </tr>
            <tr>
              <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900">
                {{ $t('gestlab.general.labels.export_certificates.total_quantity') }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-blue-900 text-center">
                {{ totalQuantity }}
              </td>
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
            {{ $t('gestlab.general.labels.export_certificates.obs') }}
          </label>
          <textarea 
            v-model="form.obs" 
            rows="4"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
            :placeholder="$t('gestlab.general.labels.export_certificates.placeholders.observations')"
          />
          <p v-if="form.errors.obs" class="text-xs text-red-600">
            {{ form.errors.obs }}
          </p>
        </div>

        <!-- File Upload -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <DocumentArrowUpIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.export_certificates.file') }}
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
            <span>{{ form.items.length }} {{ $t('gestlab.general.labels.export_certificates.products') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <CubeIcon class="h-4 w-4 text-gray-400" />
            <span class="font-semibold">{{ totalQuantity }} {{ $t('gestlab.general.labels.export_certificates.total_units') }}</span>
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
          {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.submit') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, reactive, watch } from "vue";
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
  CubeIcon
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout
});

const props = defineProps({
  transportTypes: {
    type: Array,
    default: () => []
  }
});

let customerWarehouses = reactive([]);
const loadingWarehouses = ref(false);

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const form = useForm({
  exporter_id: '',
  exporter_warehouse_id: '',
  invoice_id: '',
  trans_type_id: '',
  country_origin_id: '',
  origin_city: '',
  country_destination_id: '',
  destination_city: '',
  expedition_date: null,
  expedition_location: '',
  date: null,
  authorized_personnel: '',
  invoiced: false,
  obs: '',
  file: null,
  items: []
});

const isFormValid = computed(() => {
  return form.exporter_id && form.items.length > 0;
});

const totalQuantity = computed(() => {
  return form.items.reduce((total, item) => {
    return total + (parseFloat(item.qty) || 0);
  }, 0).toFixed(2);
});

watch(() => [form.exporter_id.value], (currentValue, oldValue) => {
  if (!form.exporter_id?.value) return;
  
  loadingWarehouses.value = true;
  fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.exporter_id?.value)
    .then(response => response.json())
    .then(results => {
      customerWarehouses = results.map(result => ({
        value: result.id,
        label: result.address,
      }));
      form.exporter_warehouse_id = customerWarehouses[0] || '';
      loadingWarehouses.value = false;
    })
    .catch(() => {
      loadingWarehouses.value = false;
    });
});

const addItem = () => {
  form.items.push({
    product_id: '',
    qty: 0,
  });
}

const removeItem = (index) => {
  if (confirm('Are you sure you want to remove this item?')) {
    form.items.splice(index, 1);
  }
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

function loadWarehouses(query, setOptions) {
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

const submit = () => {
  form.post(route('exportcertificates.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>
