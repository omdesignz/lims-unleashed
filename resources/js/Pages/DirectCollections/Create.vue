<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import datePicker from '@/Components/date-picker.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import combobox from '@/Components/combobox.vue';
import comboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ComboboxMultiple from '@/Components/combobox-multiple.vue';
import ComboboxMultipleEnhanced from '@/Components/combobox-multiple-enhanced.vue';
import { has, throttle } from "lodash";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  ClipboardDocumentCheckIcon, 
  ChevronUpIcon,
  UserIcon,
  BuildingOfficeIcon,
  CalendarIcon,
  MapPinIcon,
  BeakerIcon,
  TruckIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  Cog6ToothIcon
} from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
    products: {
      type: Array,
      default: () => []
    },
    analysis_categories: {
      type: Array,
      default: () => []
    }
});

const showDeleteConfirmation = ref(false);
const activeProductSection = ref('basic');
const loadingWarehouses = ref(false);

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const form = useForm({
    customer_id: '',
    warehouse_id: '',
    collaborations: [],
    collectionreasons: [],
    collection_date: '',
    products: []
});

const updateDate = (e) => {
  form.collection_date = e;
}

watch(() => [form.customer_id.value], (currentValue, oldValue) => {
    if (!form.customer_id?.value) return;
    
    loadingWarehouses.value = true;
    fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.customer_id?.value)
    .then(response => response.json())
    .then(results => {
        const warehouses = results.map(result => ({
            value: result.id,
            label: result.address,
        }));
        
        form.warehouse_id = warehouses[0] || '';
        loadingWarehouses.value = false;
    })
    .catch(() => {
        loadingWarehouses.value = false;
    });
});

const addProduct = () => {
    form.products.push({
        product_id: '',
        temperature_id: '',
        collection_id: '',
        pack_id: '',
        result_id: '',
        owner_id: '',
        vehicle_id: '',
        comercial_brand: '',
        du_no: '',
        temperature_value: '',
        term_no: '',
        container_no: '',
        recollection: false,
        obs: null,
        sample_status: null,
        sampling_plan_ref: null,
        customer_submitted_info: null,
        analysis_start_date: null,
        analysis_end_date: null,
        processed: false,
        collected_by_lab: false,
        expiry_date: null,
        production_date: null,
        collection_date: form.collection_date,
        qty: '',
        origin: '',
        location: '',
        collected_qty: '',
        lot: '',
        bl: '',
        invoiced: false,
        status: false,
    });
}

const removeProduct = (index) => {
    if (confirm(trans('gestlab.actions.confirm_delete_product'))) {
        form.products.splice(index, 1);
    }
}

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('directcollections.create'), {term}, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}, 300)

// API loading functions (unchanged from original)
function loadCustomers(query, setOptions) {
    fetch('/customers/getCustomer?q=' + query)
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

function loadVehicles(query, setOptions) {
    fetch('/vehicles/getVehicle?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.number_plate,
        }))
        );
    });
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

function loadProducts(query, setOptions) {
    fetch('/products/getProduct?q=' + query)
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

function loadPackagingCategories(query, setOptions) {
    fetch('/packagingcategories/getPackagingCategory?q=' + query)
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

function loadEndResults(query, setOptions) {
    fetch('/collectionendresults/getCollectionEndResult?q=' + query)
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

function loadCollectionCollaboration(query, setOptions) {
    fetch('/collectioncollaborations/getCollectionCollaboration?q=' + query)
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

function loadCollectionReason(query, setOptions) {
    fetch('/collectionreasons/getCollectionReason?q=' + query)
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

function loadUsers(query, setOptions) {
    fetch('/users/getUser?q=' + query)
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

function loadTemperatures(query, setOptions) {
    fetch('/temperatures/getTemperature?q=' + query)
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

let submit = () => {
    if(!form.id) {
        form.post(route('directcollections.store'), {
            onError: () => {
                showDeleteConfirmation.value = false
            },
            onSuccess: () => {
                form.reset()
            },
        });
    } else {
        form.put(route('directcollections.update',{collection: form.id}), {
            preserveScroll: true,
            onError: () => {
                showDeleteConfirmation.value = false
            },
            onSuccess: () => {
                form.reset()
            },
        });
    }
}
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
                        {{ $t('gestlab.general.labels.direct_collections.page_title') }}
                    </h1>
                    <p class="mt-2 text-gray-600">
                        {{ $t('gestlab.general.labels.direct_collections.page_create_description') }}
                        <span v-if="form.customer_id?.label" class="font-semibold text-blue-900">
                            {{ form.customer_id.label }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
                        {{ form.products.length }} {{ $t('gestlab.general.labels.direct_collections.products') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Collection Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Collection Information Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                            <CalendarIcon class="h-5 w-5" />
                            {{ $t('gestlab.general.labels.direct_collections.collection_information') }}
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Collection Date -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                                    <CalendarIcon class="h-4 w-4" />
                                    {{ $t('gestlab.general.labels.direct_collections.collection_date') }}
                                </label>
                                <date-picker-enhanced 
                                    class="w-full"
                                    v-model="form.collection_date"
                                    :has-error="form.errors.collection_date"
                                    :error-message="form.errors.collection_date"
                                    placeholder="Select a date"
                                />
                                <p v-if="form.errors.collection_date" class="text-xs text-red-600">
                                    {{ form.errors.collection_date }}
                                </p>
                            </div>

                            <!-- Customer -->
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                                    <UserIcon class="h-4 w-4" />
                                    {{ $t('gestlab.general.labels.direct_collections.customer_id') }}
                                </label>
                                <comboboxEnhanced 
                                    :hasError="form.errors.customer_id" 
                                    v-model="form.customer_id" 
                                    :load-options="loadCustomers"
                                    placeholder="Seleccionar cliente..."
                                />
                                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                                    {{ form.errors.customer_id }}
                                </p>
                            </div>

                            <!-- Warehouse -->
                            <div class="space-y-2">
                              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                                <BuildingOfficeIcon class="h-4 w-4" />
                                {{ $t('gestlab.general.labels.direct_collections.warehouse_id') }}
                              </label>
                              <div class="relative">
                                <comboboxEnhanced 
                                  :disableInput="!form.customer_id"
                                  :hasError="form.errors.warehouse_id" 
                                  v-model="form.warehouse_id" 
                                  :load-options="loadWarehouses"
                                  :loading="loadingWarehouses"
                                  placeholder="Seleccionar armazém..."
                                />
                                <!-- Loading indicator -->
                                <div v-if="loadingWarehouses" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                  <svg class="animate-spin h-5 w-5 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                  </svg>
                                </div>
                              </div>
                              <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                                {{ form.errors.warehouse_id }}
                              </p>
                            </div>

                            <!-- Collaborations -->
                            <div class="md:col-span-2 lg:col-span-1 space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ $t('gestlab.general.labels.direct_collections.collaborations') }}
                                </label>
                                <ComboboxMultipleEnhanced 
                                    v-model="form.collaborations"
                                    :load-options="loadCollectionCollaboration" 
                                    multiple
                                    placeholder="Select collaborations..."
                                />
                                <p v-if="form.errors.collaborations" class="text-xs text-red-600">
                                    {{ form.errors.collaborations }}
                                </p>
                            </div>

                            <!-- Collection Reasons -->
                            <div class="md:col-span-2 lg:col-span-1 space-y-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ $t('gestlab.general.labels.direct_collections.collectionreasons') }}
                                </label>
                                <ComboboxMultipleEnhanced 
                                    v-model="form.collectionreasons" 
                                    :load-options="loadCollectionReason" 
                                    :multiple="true"
                                    placeholder="Select reasons..."
                                />
                                <p v-if="form.errors.collectionreasons" class="text-xs text-red-600">
                                    {{ form.errors.collectionreasons }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <BeakerIcon class="h-5 w-5 text-blue-900" />
                                {{ $t('gestlab.general.labels.direct_collections.products') }}
                                <span class="text-sm font-normal text-gray-500 ml-2">
                                    ({{ form.products.length }} {{ $t('gestlab.general.labels.direct_collections.products') }})
                                </span>
                            </h2>
                            <button 
                                @click="addProduct" 
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                            >
                                <PlusCircleIcon class="h-5 w-5" />
                                {{ $t('gestlab.general.buttons.add') }}
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $t('gestlab.general.labels.direct_collections.products_tagline') }}
                        </p>
                    </div>

                    <div v-if="form.products.length === 0" class="p-12 text-center">
                        <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
                        <h3 class="mt-4 text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.direct_collections.no_products') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ $t('gestlab.general.buttons.add') }}
                        </p>
                        <button 
                            @click="addProduct" 
                            type="button"
                            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
                        >
                            <PlusCircleIcon class="h-5 w-5" />
                            {{ $t('gestlab.general.buttons.add') }}
                        </button>
                    </div>

                    <!-- Product Cards Grid -->
                    <div v-else class="grid grid-cols-1 lg:grid-cols-1 gap-6 p-6">
                        <div 
                            v-for="(product, index) in form.products" 
                            :key="index"
                            class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
                            v-motion
                            :initial="{ opacity: 0, y: 20 }"
                            :enter="{ opacity: 1, y: 0 }"
                            :delay="index * 50"
                        >
                            <!-- Product Header -->
                            <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                                            {{ index + 1 }}
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                {{ product.product_id?.label || $t('gestlab.general.labels.direct_collections.unnamed_product') }}
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                {{ $t('gestlab.general.labels.direct_collections.product') }} #{{ index + 1 }}
                                            </p>
                                        </div>
                                    </div>
                                    <button 
                                        @click="removeProduct(index)"
                                        type="button"
                                        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                                        :title="$t('gestlab.general.buttons.remove')"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Product Content -->
                            <div class="p-4">
                                <!-- Section Navigation -->
                                <div class="mb-4 border-b border-gray-200">
                                    <nav class="-mb-px flex space-x-4">
                                        <button
                                            @click="activeProductSection = 'basic'"
                                            :class="[
                                                'px-3 py-2 text-sm font-medium border-b-2',
                                                activeProductSection === 'basic'
                                                    ? 'border-blue-900 text-blue-900'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            ]"
                                        >
                                            {{ $t('gestlab.general.labels.direct_collections.basic_info') }}
                                        </button>
                                        <button
                                            @click="activeProductSection = 'shipping'"
                                            :class="[
                                                'px-3 py-2 text-sm font-medium border-b-2',
                                                activeProductSection === 'shipping'
                                                    ? 'border-blue-900 text-blue-900'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            ]"
                                        >
                                            {{ $t('gestlab.general.labels.direct_collections.shipping') }}
                                        </button>
                                        <button
                                            @click="activeProductSection = 'analysis'"
                                            :class="[
                                                'px-3 py-2 text-sm font-medium border-b-2',
                                                activeProductSection === 'analysis'
                                                    ? 'border-blue-900 text-blue-900'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            ]"
                                        >
                                            {{ $t('gestlab.general.labels.direct_collections.analysis') }}
                                        </button>
                                    </nav>
                                </div>

                                <!-- Basic Info Section -->
                                <div v-show="activeProductSection === 'basic'" class="space-y-4">
                                    <!-- Product Selection -->
                                    <div class="space-y-2">
                                      <label class="block text-sm font-medium text-gray-700">
                                        {{ $t('gestlab.general.labels.direct_collections.product_id') }}
                                      </label>
                                      <div class="relative">
                                        <comboboxEnhanced 
                                          :hasError="form.errors[`products.${index}.product_id`]" 
                                          v-model="product.product_id" 
                                          :load-options="loadProducts"
                                          placeholder="Seleccionar produto..."
                                        />
                                      </div>
                                      <p v-if="form.errors[`products.${index}.product_id`]" class="text-xs text-red-600">
                                        {{ form.errors[`products.${index}.product_id`] }}
                                      </p>
                                    </div>

                                    <!-- Basic Fields Grid -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Origin -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.origin') }}
                                            </label>
                                            <input 
                                                v-model="product.origin" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- Commercial Brand -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.comercial_brand') }}
                                            </label>
                                            <input 
                                                v-model="product.comercial_brand" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- Quantity -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.qty') }}
                                            </label>
                                            <input 
                                                v-model="product.qty" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- Collected Quantity -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.collected_qty') }}
                                            </label>
                                            <input 
                                                v-model="product.collected_qty" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- Pack Type -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.pack_id') }}
                                            </label>
                                            <comboboxEnhanced 
                                                :hasError="form.errors[`products.${index}.pack_id`]" 
                                                v-model="product.pack_id" 
                                                :load-options="loadPackagingCategories"
                                                placeholder="Seleccionar embalagem..."
                                            />
                                        </div>
                                    </div>

                                    <!-- Dates -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 relative">
                                      <!-- Production Date -->
                                      <div class="space-y-2">
                                          <label class="block text-sm font-medium text-gray-700">
                                              {{ $t('gestlab.general.labels.direct_collections.production_date') }}
                                          </label>
                                          <div class="relative isolate">
                                              <date-picker-enhanced

                                                  v-model="product.production_date" 
                                                  :has-error="form.errors.production_date"
                                                  :error-message="form.errors.production_date"
                                                  placeholder=""
                                              />
                                          </div>
                                      </div>

                                      <!-- Expiry Date -->
                                      <div class="space-y-2">
                                          <label class="block text-sm font-medium text-gray-700">
                                              {{ $t('gestlab.general.labels.direct_collections.expiry_date') }}
                                          </label>
                                          <div class="relative isolate">
                                              <date-picker-enhanced 
                                                  v-model="product.expiry_date"
                                                  :has-error="form.errors.expiry_date"
                                                  :error-message="form.errors.expiry_date"
                                                  placeholder=""
                                              />
                                          </div>
                                      </div>

                                      <!-- Location -->
                                      <div class="space-y-2">
                                          <label class="block text-sm font-medium text-gray-700">
                                              {{ $t('gestlab.general.labels.direct_collections.location') }}
                                          </label>
                                          <input 
                                              v-model="product.location" 
                                              type="text" 
                                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                          />
                                      </div>
                                  </div>
                                </div>

                                <!-- Shipping Section -->
                                <div v-show="activeProductSection === 'shipping'" class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Vehicle -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                                                <TruckIcon class="h-4 w-4" />
                                                {{ $t('gestlab.general.labels.direct_collections.vehicle_id') }}
                                            </label>
                                            <comboboxEnhanced 
                                                :hasError="form.errors[`products.${index}.vehicle_id`]" 
                                                v-model="product.vehicle_id" 
                                                :load-options="loadVehicles"
                                                placeholder="Select vehicle..."
                                            />
                                        </div>

                                        <!-- Temperature -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.temperature_id') }}
                                            </label>
                                            <comboboxEnhanced 
                                                :hasError="form.errors[`products.${index}.temperature_id`]" 
                                                v-model="product.temperature_id" 
                                                :load-options="loadTemperatures"
                                                placeholder="Select temperature..."
                                            />
                                        </div>

                                        <!-- Temperature Value -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.temperature_value') }}
                                            </label>
                                            <input 
                                                v-model="product.temperature_value" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                                placeholder="°C"
                                            />
                                        </div>

                                        <!-- Collected by Lab -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                {{ $t('gestlab.general.labels.direct_collections.collected_by_lab') }}
                                                <input 
                                                    v-model="product.collected_by_lab" 
                                                    type="checkbox" 
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                                                />
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Shipping References -->
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">

                                      <!-- DU -->
                                      <div class="space-y-2">
                                          <label class="block text-sm font-medium text-gray-700">
                                              {{ $t('gestlab.general.labels.direct_collections.du_no') }}
                                          </label>
                                          <input 
                                              v-model="product.du_no" 
                                              type="text" 
                                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                          />
                                      </div>

                                      <!-- Term Number -->
                                      <div class="space-y-2">
                                          <label class="block text-sm font-medium text-gray-700">
                                              {{ $t('gestlab.general.labels.direct_collections.term_no') }}
                                          </label>
                                          <input 
                                              v-model="product.term_no" 
                                              type="text" 
                                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                          />
                                      </div>
                                        <!-- Lot -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.lot') }}
                                            </label>
                                            <input 
                                                v-model="product.lot" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- BL -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.bl') }}
                                            </label>
                                            <input 
                                                v-model="product.bl" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- Container No -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.container_no') }}
                                            </label>
                                            <input 
                                                v-model="product.container_no" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Analysis Section -->
                                <div v-show="activeProductSection === 'analysis'" class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- End Result -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.result_id') }}
                                            </label>
                                            <comboboxEnhanced 
                                                :hasError="form.errors[`products.${index}.result_id`]" 
                                                v-model="product.result_id" 
                                                :load-options="loadEndResults"
                                                placeholder="Select result..."
                                            />
                                        </div>

                                        <!-- Owner -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.owner_id') }}
                                            </label>
                                            <comboboxEnhanced 
                                                :hasError="form.errors[`products.${index}.owner_id`]" 
                                                v-model="product.owner_id" 
                                                :load-options="loadUsers"
                                                placeholder="Select owner..."
                                            />
                                        </div>

                                        <!-- Sample Status -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.sample_status') }} 
                                            </label>
                                            <input 
                                                v-model="product.sample_status" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>

                                        <!-- Sampling Plan Ref -->
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ $t('gestlab.general.labels.direct_collections.sampling_plan_ref') }}
                                            </label>
                                            <input 
                                                v-model="product.sampling_plan_ref" 
                                                type="text" 
                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            />
                                        </div>
                                    </div>

                                    <!-- Observations -->
                                    <div class="space-y-2 pt-2">
                                        <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                                            <DocumentTextIcon class="h-4 w-4" />
                                            {{ $t('gestlab.general.labels.direct_collections.obs') }}
                                        </label>
                                        <textarea 
                                            v-model="product.obs" 
                                            rows="3"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                                            placeholder="Add observations..."
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Actions & Summary -->
            <div class="space-y-6">
                <!-- Submit Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        {{ $t('gestlab.general.labels.actions') }}
                    </h3>
                    <div class="space-y-4">
                        <button 
                            @click="showDeleteConfirmation = true" 
                            :disabled="form.processing || form.products.length === 0"
                            :class="[
                                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                                form.processing || form.products.length === 0
                                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                            ]"
                        >
                            <CheckCircleIcon class="h-5 w-5" />
                            {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.submit') }}
                        </button>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">
                                {{ $t('gestlab.general.labels.direct_collections.quick_stats') }}
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $t('gestlab.general.labels.direct_collections.total_products') }}</span>
                                    <span class="font-semibold text-blue-900">{{ form.products.length }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $t('gestlab.general.labels.direct_collections.collection_date') }}</span>
                                    <span class="font-semibold">{{ form.collection_date || '-' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $t('gestlab.general.labels.direct_collections.customer_id') }}</span>
                                    <span class="font-semibold truncate max-w-[120px]">{{ form.customer_id?.label || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Status -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
                        {{ $t('gestlab.general.labels.direct_collections.form_status') }}
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.direct_collections.collection_information') }}</span>
                            <span v-if="form.collection_date && form.customer_id?.value" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                {{ $t('gestlab.general.labels.direct_collections.complete') }}
                            </span>
                            <span v-else class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                {{ $t('gestlab.general.labels.direct_collections.incomplete') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.direct_collections.products') }}</span>
                            <span v-if="form.products.length > 0" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                {{ form.products.length }} {{ $t('gestlab.general.labels.direct_collections.products') }}
                            </span>
                            <span v-else class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                {{ $t('gestlab.general.labels.direct_collections.required') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <confirm-dialog 
        size="sm:max-w-2xl" 
        alignment="sm:items-start" 
        @canceled="showDeleteConfirmation=false" 
        @close="showDeleteConfirmation=false" 
        @confirmed="submit" 
        v-if="showDeleteConfirmation" 
        :title="$t('gestlab.actions.confirmation_dialog_title.default')" 
        :description="$t('gestlab.actions.confirmation_dialog_description.default')" 
        confirm="Sim" 
        cancel="Não"
    >
        

    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collection_date') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collection_date }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.customer_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.warehouse_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collaborations') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collaborations?.map((item) => item.label).join(", ") }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collectionreasons') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collectionreasons?.map((item) => item.label).join(", ") }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">
              
              <div class="w-full pt-2">
                <div class="mx-auto w-full rounded-2xl bg-white">
                  <Disclosure v-slot="{ open }" v-for="(product, index) in form.products" :key="product.value" v-if="form?.products.length">
                    <DisclosureButton
                      class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                    >
                      <span>{{ product.product_id?.label }}</span>
                      <ChevronUpIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-5 w-5 text-white"
                      />
                    </DisclosureButton>
                    <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                      <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.origin') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.origin }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.production_date') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.production_date }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.expiry_date') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.expiry_date }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.comercial_brand') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.comercial_brand }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.pack_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.pack_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.qty') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.qty }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collected_qty') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.collected_qty }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.location') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.location }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.lot') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.lot }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.bl') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.bl }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.du_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.du_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.term_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.term_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.container_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.container_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.temperature_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.temperature_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.temperature_value') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.temperature_value }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collected_by_lab') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.collected_by_lab ? 'SIM' : 'NÃO' }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.result_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.result_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.owner_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.owner_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.vehicle_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.vehicle_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.obs') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.obs }}</dd>
                          </div>
                        </dl>
                      </div>
                    </DisclosurePanel>
                  </Disclosure>
                </div>
              </div>
            </div>
          </dl>
        </div>
      </div>

    </div>

    </confirm-dialog>
</template>