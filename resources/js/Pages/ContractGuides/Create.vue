<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import {throttle} from "lodash";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  ClipboardDocumentCheckIcon,
  ChevronUpIcon,
  UserIcon,
  BuildingOfficeIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  QueueListIcon,
  DocumentTextIcon,
  GlobeAltIcon,
  CubeIcon,
  BuildingStorefrontIcon,
  IdentificationIcon,
  PhoneIcon,
  EnvelopeIcon,
  MapPinIcon,
  TagIcon
} from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
    items: {
      type: Array,
      default: () => []
    },
    analysis_categories: {
      type: Array,
      default: () => []
    }
});

// Reactive state
let customerWarehouses = reactive([]);
const showDeleteConfirmation = ref(false);
const isLoading = ref(false);

// Form
const form = useForm({
    obs: '',
    ref_no: '',
    entry_point: '',
    collection_point: '', 
    du_no: '',
    nif: '',
    contact: '',
    email: '',
    bl: '',
    customer_id: '',
    warehouse_id: '',
    collection_id: '',
    items: []
});

// Computed
const isFormValid = computed(() => {
  return form.customer_id && 
         form.warehouse_id && 
         form.ref_no &&
         form.items.length > 0 &&
         form.items.every(item => item.product_id && item.country_id);
});

const formStatus = computed(() => {
  if (isFormValid.value && form.items.every(item => item.manufacturer && item.brand && item.lot)) {
    return {
      label: 'complete',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else if (isFormValid.value) {
    return {
      label: 'partial',
      color: 'bg-yellow-100 text-yellow-800',
      icon: ExclamationTriangleIcon
    };
  } else {
    return {
      label: 'incomplete',
      color: 'bg-red-100 text-red-800',
      icon: ExclamationTriangleIcon
    };
  }
});

// Watchers
watch(() => form.customer_id.value, (customerId) => {
  if (customerId) {
    isLoading.value = true;
    fetch(`/warehouses/getWarehouse?q=&customer_id=${customerId}`)
      .then(response => response.json())
      .then(results => {
        customerWarehouses = results.map(result => ({
          value: result.id,
          label: result.address,
        }));
        
        if (customerWarehouses.length > 0) {
          form.warehouse_id = customerWarehouses[0];
        }
      })
      .finally(() => {
        isLoading.value = false;
      });
  }
});

// Methods
const addProduct = () => {
  form.items.push({
    guide_id: '',
    product_id: '',
    country_id: '',
    origin: '',
    manufacturer: '',
    brand: '',
    lot: '',
    du_no: '',
    bl: '',
    collection_id: '',
    obs: '',
    date: null,
  });
};

const removeProduct = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
};

const onSearchAnalysisCategoryChange = throttle(function (term) {
  router.get(route('contractguides.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, 300);

function loadResultCategories(query, setOptions) {
  if (!query) return;
  
  fetch(`/resultcategories/getResultCategory?q=${query}`)
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

function loadCustomers(query, setOptions) {
  if (!query) return;
  
  fetch(`/customers/getCustomer?q=${query}`)
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
  if (!query) return;
  
  fetch(`/countries/getCountry?q=${query}`)
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
  if (!form.customer_id?.value) {
    setOptions([]);
    return;
  }
  
  fetch(`/warehouses/getWarehouse?q=${query}&customer_id=${form.customer_id.value}`)
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

function loadProducts(query, setOptions) {
  if (!query) return;
  
  fetch(`/products/getProduct?q=${query}`)
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

const submit = () => {
  if (!isFormValid.value) return;
  
  showDeleteConfirmation.value = true;
};

const handleConfirmSubmit = () => {
  if(!form.id) {
    form.post(route('contractguides.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        showDeleteConfirmation.value = false;
      },
      onError: () => {
        showDeleteConfirmation.value = false;
      }
    });
  } else {
    form.put(route('contractguides.update',{guide: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        showDeleteConfirmation.value = false;
        form.reset();
      },
      onError: () => {
        showDeleteConfirmation.value = false;
      }
    });
  }
};
</script>

<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.contract_guides.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.contract_guides.page_create_description') }}
            <span v-if="form.customer_id?.label" class="font-semibold text-blue-900">
              {{ form.customer_id.label }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.items.length }} {{ $t('gestlab.general.labels.contract_guides.products') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CUSTOMER INFORMATION SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.contract_guides.customer_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- CUSTOMER FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.customer_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                  :hasError="form.errors.customer_id"
                  v-model="form.customer_id"
                  :load-options="loadCustomers"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.select_customer')"
                  class="w-full"
                />
                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>

              <!-- WAREHOUSE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.warehouse_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                  :disableInput="!form.customer_id"
                  :hasError="form.errors.warehouse_id"
                  v-model="form.warehouse_id"
                  :load-options="loadWarehouses"
                  :placeholder="form.customer_id ? $t('gestlab.general.labels.contract_guides.placeholders.select_warehouse') : $t('gestlab.general.labels.contract_guides.placeholders.select_customer_first')"
                  class="w-full"
                />
                <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.warehouse_id }}
                </p>
              </div>

              <!-- REFERENCE NUMBER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.ref_no') }}
                  <span class="text-red-500">*</span>
                </label>
                <input 
                  v-model="form.ref_no" 
                  type="text" 
                  name="ref_no" 
                  id="ref_no" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_reference')"
                />
                <p v-if="form.errors.ref_no" class="text-xs text-red-600">
                  {{ form.errors.ref_no }}
                </p>
              </div>

              <!-- DU NUMBER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <IdentificationIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.du_no') }}
                </label>
                <input 
                  v-model="form.du_no" 
                  type="text" 
                  name="du_no" 
                  id="du_no" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_du_number')"
                />
                <p v-if="form.errors.du_no" class="text-xs text-red-600">
                  {{ form.errors.du_no }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- CONTACT & SHIPPING SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <BuildingStorefrontIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.contract_guides.shipping_info') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- COLLECTION POINT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.collection_point') }}
                </label>
                <input 
                  v-model="form.collection_point" 
                  type="text" 
                  name="collection_point" 
                  id="collection_point" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_collection_point')"
                />
                <p v-if="form.errors.collection_point" class="text-xs text-red-600">
                  {{ form.errors.collection_point }}
                </p>
              </div>

              <!-- ENTRY POINT -->
              <div class="space-y-2 lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.entry_point') }}
                </label>
                <input 
                  v-model="form.entry_point" 
                  type="text" 
                  name="entry_point" 
                  id="entry_point" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_entry_point')"
                />
                <p v-if="form.errors.entry_point" class="text-xs text-red-600">
                  {{ form.errors.entry_point }}
                </p>
              </div>

              <!-- NIF -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <IdentificationIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.nif') }}
                </label>
                <input 
                  v-model="form.nif" 
                  type="text" 
                  name="nif" 
                  id="nif" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_nif')"
                />
                <p v-if="form.errors.nif" class="text-xs text-red-600">
                  {{ form.errors.nif }}
                </p>
              </div>

              <!-- CONTACT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <PhoneIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.contact') }}
                </label>
                <input 
                  v-model="form.contact" 
                  type="text" 
                  name="contact" 
                  id="contact" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_contact')"
                />
                <p v-if="form.errors.contact" class="text-xs text-red-600">
                  {{ form.errors.contact }}
                </p>
              </div>

              <!-- EMAIL -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <EnvelopeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.contract_guides.email') }}
                </label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  name="email" 
                  id="email" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_email')"
                />
                <p v-if="form.errors.email" class="text-xs text-red-600">
                  {{ form.errors.email }}
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
                <QueueListIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.contract_guides.products') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.items.length }} {{ $t('gestlab.general.labels.contract_guides.items') }})
                </span>
              </h2>
              <button 
                @click="addProduct"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.buttons.add_item') }}
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.items.length === 0" class="p-12 text-center">
            <CubeIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.messages.no_items') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.messages.add_items_description') }}
            </p>
            <button 
              @click="addProduct"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.add_first_item') }}
            </button>
          </div>

          <!-- PRODUCTS GRID -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-1 gap-6 p-6">
            <!-- PRODUCT CARD -->
            <div 
              v-for="(product, index) in form.items"
              :key="index"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- PRODUCT HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ product.product_id?.label || $t('gestlab.general.labels.contract_guides.product') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.labels.contract_guides.product') }} #{{ index + 1 }}
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="removeProduct(index)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                    :title="$t('gestlab.general.buttons.remove_product')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <!-- PRODUCT CONTENT -->
              <div class="p-4">
                <div class="space-y-4">
                  <!-- PRODUCT AND COUNTRY -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- PRODUCT SELECTION -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.product_id') }}
                        <span class="text-red-500">*</span>
                      </label>
                      <comboboxEnhanced
                        :hasError="form.errors[`items.${index}.product_id`]"
                        v-model="product.product_id"
                        :load-options="loadProducts"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.select_product')"
                        class="w-full"
                      />
                      <p v-if="form.errors[`items.${index}.product_id`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.product_id`] }}
                      </p>
                    </div>

                    <!-- COUNTRY SELECTION -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.country_id') }}
                        <span class="text-red-500">*</span>
                      </label>
                      <comboboxEnhanced
                        :hasError="form.errors[`items.${index}.country_id`]"
                        v-model="product.country_id"
                        :load-options="loadCountries"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.select_country')"
                        class="w-full"
                      />
                      <p v-if="form.errors[`items.${index}.country_id`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.country_id`] }}
                      </p>
                    </div>
                  </div>

                  <!-- MANUFACTURER AND BRAND -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- MANUFACTURER -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.manufacturer') }}
                      </label>
                      <input
                        v-model="product.manufacturer"
                        type="text"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_manufacturer')"
                      />
                      <p v-if="form.errors[`items.${index}.manufacturer`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.manufacturer`] }}
                      </p>
                    </div>

                    <!-- BRAND -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.brand') }}
                      </label>
                      <input
                        v-model="product.brand"
                        type="text"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_brand')"
                      />
                      <p v-if="form.errors[`items.${index}.brand`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.brand`] }}
                      </p>
                    </div>
                  </div>

                  <!-- LOT, BL, DU -->
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- LOT -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.lot') }}
                      </label>
                      <input
                        v-model="product.lot"
                        type="text"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_lot')"
                      />
                      <p v-if="form.errors[`items.${index}.lot`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.lot`] }}
                      </p>
                    </div>

                    <!-- BL -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.bl') }}
                      </label>
                      <input
                        v-model="product.bl"
                        type="text"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_bl')"
                      />
                      <p v-if="form.errors[`items.${index}.bl`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.bl`] }}
                      </p>
                    </div>

                    <!-- DU NO -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.contract_guides.du_no') }}
                      </label>
                      <input
                        v-model="product.du_no"
                        type="text"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                        :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_du_number')"
                      />
                      <p v-if="form.errors[`items.${index}.du_no`]" class="text-xs text-red-600">
                        {{ form.errors[`items.${index}.du_no`] }}
                      </p>
                    </div>
                  </div>

                  <!-- OBSERVATIONS -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.contract_guides.obs') }}
                    </label>
                    <textarea
                      v-model="product.obs"
                      rows="2"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                      :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_product_observations')"
                    />
                    <p v-if="form.errors[`items.${index}.obs`]" class="text-xs text-red-600">
                      {{ form.errors[`items.${index}.obs`] }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- GENERAL OBSERVATIONS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.contract_guides.general_observations') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.contract_guides.obs') }}
              </label>
              <textarea
                v-model="form.obs"
                rows="4"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                :placeholder="$t('gestlab.general.labels.contract_guides.placeholders.enter_general_observations')"
              />
              <p v-if="form.errors.obs" class="text-xs text-red-600">
                {{ form.errors.obs }}
              </p>
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
              @click="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.submit') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.total_products') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.complete_products') }}</span>
                  <span class="font-semibold text-green-600">
                    {{ form.items.filter(item => item.product_id && item.country_id).length }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.incomplete_products') }}</span>
                  <span class="font-semibold text-red-600">
                    {{ form.items.filter(item => !item.product_id || !item.country_id).length }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.contract_guides.form_status') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                formStatus.color
              ]">
                <component :is="formStatus.icon" class="h-3 w-3" />
                {{ $t(`gestlab.general.status.${formStatus.label}`) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.customer_selected') }}</span>
              <span :class="form.customer_id ? 'text-green-600' : 'text-red-600'">
                {{ form.customer_id ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.warehouse_selected') }}</span>
              <span :class="form.warehouse_id ? 'text-green-600' : 'text-red-600'">
                {{ form.warehouse_id ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.ref_no') }}</span>
              <span :class="form.ref_no ? 'text-green-600' : 'text-red-600'">
                {{ form.ref_no ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.products_added') }}</span>
              <span :class="form.items.length > 0 ? 'text-green-600' : 'text-red-600'">
                {{ form.items.length > 0 ? form.items.length : $t('gestlab.general.status.none') }}
              </span>
            </div>
          </div>
        </div>

        <!-- CUSTOMER INFO CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <UserIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.contract_guides.customer_info') }}
          </h3>
          <div class="space-y-3">
            <div v-if="form.customer_id" class="space-y-1">
              <div class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.contract_guides.customer_id') }}
              </div>
              <div class="text-sm font-medium text-gray-900">
                {{ form.customer_id.label }}
              </div>
            </div>
            <div v-if="form.warehouse_id" class="space-y-1">
              <div class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.contract_guides.warehouse_id') }}
              </div>
              <div class="text-sm font-medium text-gray-900">
                {{ form.warehouse_id.label }}
              </div>
            </div>
            <div v-if="form.ref_no" class="space-y-1">
              <div class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.contract_guides.reference') }}
              </div>
              <div class="text-sm font-medium text-gray-900">
                {{ form.ref_no }}
              </div>
            </div>
            <div v-if="!form.customer_id" class="text-sm text-gray-500 italic">
              {{ $t('gestlab.general.labels.contract_guides.select_customer_first') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.contract_guides.auto_save') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="addProduct"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <PlusCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.add_another_item') }}
        </button>
        <button 
          @click="submit"
          :disabled="!isFormValid"
          type="button"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200',
            !isFormValid
              ? 'bg-gray-400 cursor-not-allowed'
              : 'bg-blue-900 hover:bg-blue-800'
          ]"
        >
          <ClipboardDocumentCheckIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.finalize') }}
        </button>
      </div>
    </div>
  </div>

  <!-- Confirmation Dialog -->
  <confirm-dialog
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="handleConfirmSubmit"
    v-if="showDeleteConfirmation"
    :title="$t('gestlab.actions.confirmation_dialog_title.default')"
    :description="$t('gestlab.actions.confirmation_dialog_description.default')"
    :confirm="$t('gestlab.general.buttons.confirm')"
    :cancel="$t('gestlab.general.buttons.cancel')"
  >
    <div class="mt-4 space-y-4">
      <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900">
        <CheckCircleIcon class="h-4 w-4" />
        <p>{{ $t('gestlab.general.labels.contract_guides.guide_summary') }}</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.contract_guides.customer_info') }}
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.customer_id') }}</span>
                <span class="font-medium">{{ form.customer_id?.label }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.warehouse_id') }}</span>
                <span class="font-medium">{{ form.warehouse_id?.label }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.reference') }}</span>
                <span class="font-medium">{{ form.ref_no }}</span>
              </div>
            </div>
          </div>
          
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.contract_guides.totals') }}
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.total_products') }}</span>
                <span class="font-medium">{{ form.items.length }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.complete_products') }}</span>
                <span class="font-medium text-green-600">
                  {{ form.items.filter(item => item.product_id && item.country_id).length }}
                </span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.incomplete_products') }}</span>
                <span class="font-medium text-red-600">
                  {{ form.items.filter(item => !item.product_id || !item.country_id).length }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="space-y-4">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.contract_guides.contact_info') }}
            </h4>
            <div class="space-y-2">
              <div v-if="form.contact" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.contact') }}</span>
                <span class="font-medium">{{ form.contact }}</span>
              </div>
              <div v-if="form.email" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.email') }}</span>
                <span class="font-medium">{{ form.email }}</span>
              </div>
              <div v-if="form.nif" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.nif') }}</span>
                <span class="font-medium">{{ form.nif }}</span>
              </div>
            </div>
          </div>
          
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.contract_guides.shipping_info') }}
            </h4>
            <div class="space-y-2">
              <div v-if="form.collection_point" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.collection_point') }}</span>
                <span class="font-medium">{{ form.collection_point }}</span>
              </div>
              <div v-if="form.entry_point" class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.contract_guides.entry_point') }}</span>
                <span class="font-medium">{{ form.entry_point }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </confirm-dialog>
</template>

<style scoped>
/* Smooth transitions for better UX */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar styling */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Focus styles for accessibility */
:focus {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

:focus:not(:focus-visible) {
  outline: none;
}
</style>