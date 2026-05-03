<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import datePicker from '@/Components/date-picker.vue'
import combobox from '@/Components/combobox.vue';
import selectInput from '@/Components/select-input.vue';
import ComboboxMultiple from '@/Components/combobox-multiple.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon, ChevronUpIcon } from "@heroicons/vue/24/outline";
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

const updateDate = (e) => {
  form.collection_date = e;
}

const showDeleteConfirmation = ref(false);

let customerWarehouses = reactive([]);

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


    watch(() => [form.customer_id.value], (currentValue, oldValue) => {
        console.log(currentValue);

        fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.customer_id?.value)
        .then(response => response.json())
        .then(results => {

            customerWarehouses = results.map(result => {
                return {
                value: result.id,
                label: result.address,
                }
            });

            form.warehouse_id = customerWarehouses[0];

        });

        }
      );    

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
    form.products.splice(index, 1);
}

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('directcollections.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)


function loadCustomers(query, setOptions) {
    fetch('/customers/getCustomer?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadVehicles(query, setOptions) {
    fetch('/vehicles/getVehicle?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.number_plate,
            };
        })
        );
    });
}

let loadWarehouses = (query, setOptions) => {
    fetch('/warehouses/getWarehouse?q=' + query + '&customer_id=' + form.customer_id?.value)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.address,
            };
        })
        )
    });
}

function loadProducts(query, setOptions) {
    fetch('/products/getProduct?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadPackagingCategories(query, setOptions) {
    fetch('/packagingcategories/getPackagingCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadEndResults(query, setOptions) {
    fetch('/collectionendresults/getCollectionEndResult?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadCollectionCollaboration(query, setOptions) {
    fetch('/collectioncollaborations/getCollectionCollaboration?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadCollectionReason(query, setOptions) {
    fetch('/collectionreasons/getCollectionReason?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadUsers(query, setOptions) {
    fetch('/users/getUser?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadTemperatures(query, setOptions) {
    fetch('/temperatures/getTemperature?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

let submit = () => {

if(!form.id) {
  form.post(route('directcollections.store'), {
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('directcollections.update',{collection: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
}

}

</script>

<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.direct_collections.page_create_description') }} {{ form.customer_id.label }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1"> 
            <label for="collection_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collection_date') }}</label>
            <div class="mt-2">
              <date-picker class="py-1.5" v-model.string="form.collection_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
            </div>
            <p v-if="form.errors.collection_date" class="mt-2 text-xs text-red-600" id="collection_date-error">{{ form.errors.collection_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.customer_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value="" />
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.warehouse_id') }}</label>
            <div class="mt-2">
              <combobox :disableInput="!form.customer_id" ref="warehouse_id" :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="collaborations" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collaborations') }}</label>
            <div class="mt-2">
              <ComboboxMultiple v-model="form.collaborations" :load-options="loadCollectionCollaboration" multiple/>
            </div>
            <p v-if="form.errors.collaborations" class="mt-2 text-xs text-red-600" id="collaborations-error">{{ form.errors.collaborations }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="collectionreason" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collectionreasons') }}</label>
            <div class="mt-2">
              <ComboboxMultiple v-model="form.collectionreasons" :load-options="loadCollectionReason" multiple/>
            </div>
            <p v-if="form.errors.collectionreasons" class="mt-2 text-xs text-red-600" id="collectionreasons-error">{{ form.errors.collectionreasons }}</p>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.products.length }} {{ $t('gestlab.general.labels.direct_collections.products') }}
          <button @click="addProduct" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.direct_collections.products_tagline') }} {{ form.customer_id.label }}</p>

      </div>

      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(product, index) in form.products" :key="product.value" class="rounded-xl border border-gray-200"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-3">
            <div class="text-sm font-medium leading-6 text-gray-900">

              <ClipboardDocumentCheckIcon class="h-5 w-5" />

              </div>
              {{ index + 1 }}º {{ $t('gestlab.general.labels.direct_collections.product') }}

            <button @click="removeProduct(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="product_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.product_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`products.${index}.product_id`]" v-model="product.product_id" :load-options="loadProducts"/>
                  </div>
                  <p v-if="form.errors[`products.${index}.product_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.product_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="origin" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.origin') }}</label>
                  <div class="mt-2">
                    <input v-model="product.origin" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.origin`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.origin`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="production_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.production_date') }}</label>
                  <div class="mt-2">
                    <date-picker class="py-1.5" v-model.string="product.production_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
                  </div>
                  <p v-if="form.errors[`products.${index}.production_date`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.production_date`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="expiry_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.expiry_date') }}</label>
                  <div class="mt-2">
                    <date-picker class="py-1.5" v-model.string="product.expiry_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
                  </div>
                  <p v-if="form.errors[`products.${index}.expiry_date`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.expiry_date`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="comercial_brand" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.comercial_brand') }}</label>
                  <div class="mt-2">
                    <input v-model="product.comercial_brand" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.comercial_brand`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.comercial_brand`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="pack_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.pack_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`products.${index}.pack_id`]" v-model="product.pack_id" :load-options="loadPackagingCategories"/>
                  </div>
                  <p v-if="form.errors[`products.${index}.pack_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.pack_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.qty') }}</label>
                  <div class="mt-2">
                    <input v-model="product.qty" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.qty`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.qty`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="collected_qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collected_qty') }}</label>
                  <div class="mt-2">
                    <input v-model="product.collected_qty" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.collected_qty`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.collected_qty`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="location" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.location') }}</label>
                  <div class="mt-2">
                    <input v-model="product.location" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.location`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.location`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="sample_status" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.sample_status') }}</label>
                  <div class="mt-2">
                    <input v-model="product.sample_status" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.sample_status`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.sample_status`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="sampling_plan_ref" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.sampling_plan_ref') }}</label>
                  <div class="mt-2">
                    <input v-model="product.sampling_plan_ref" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.sampling_plan_ref`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.sampling_plan_ref`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="customer_submitted_info" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.customer_submitted_info') }}</label>
                  <div class="mt-2">
                    <input v-model="product.customer_submitted_info" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.customer_submitted_info`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.customer_submitted_info`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="lot" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.lot') }}</label>
                  <div class="mt-2">
                    <input v-model="product.lot" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.lot`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.lot`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="bl" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.bl') }}</label>
                  <div class="mt-2">
                    <input v-model="product.bl" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.bl`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.bl`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="du_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.du_no') }}</label>
                  <div class="mt-2">
                    <input v-model="product.du_no" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.du_no`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.du_no`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="term_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.term_no') }}</label>
                  <div class="mt-2">
                    <input v-model="product.term_no" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.term_no`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.term_no`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="container_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.container_no') }}</label>
                  <div class="mt-2">
                    <input v-model="product.container_no" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.container_no`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.container_no`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="temperature_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.temperature_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`products.${index}.temperature_id`]" v-model="product.temperature_id" :load-options="loadTemperatures"/>
                  </div>
                  <p v-if="form.errors[`products.${index}.temperature_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.temperature_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="temperature_value" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.temperature_value') }}</label>
                  <div class="mt-2">
                    <input v-model="product.temperature_value" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.temperature_value`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.temperature_value`] }}</p>
                </div>
              </dd>
            </div>

            <!-- Collected By Lab -->
             <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="collected_by_lab" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collected_by_lab') }}</label>
                  <div class="mt-2">
                    <input v-model="product.collected_by_lab" type="checkbox" :name="`item-${index}-error`" :id="`item-${index}-error`" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.collected_by_lab`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.collected_by_lab`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="result_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.result_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`products.${index}.result_id`]" v-model="product.result_id" :load-options="loadEndResults"/>
                  </div>
                  <p v-if="form.errors[`products.${index}.result_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.result_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="vehicle_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.vehicle_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`products.${index}.vehicle_id`]" v-model="product.vehicle_id" :load-options="loadVehicles"/>
                  </div>
                  <p v-if="form.errors[`products.${index}.vehicle_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.vehicle_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="owner_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.owner_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`products.${index}.owner_id`]" v-model="product.owner_id" :load-options="loadUsers"/>
                  </div>
                  <p v-if="form.errors[`products.${index}.owner_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.owner_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.obs') }}</label>
                  <div class="mt-2">
                    <textarea v-model="product.obs" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`products.${index}.obs`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.obs`] }}</p>
                </div>
              </dd>
            </div>



          </dl>
        </li>
      </ul>
      <p v-if="form.errors.products" class="mt-2 text-xs text-red-600">{{ form.errors.products }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <!-- <button @click="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button> -->
      <button @click="showDeleteConfirmation = true" :disabled="form.processing" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button>
    </div>
  </form>

  <confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="submit" v-if="showDeleteConfirmation" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
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