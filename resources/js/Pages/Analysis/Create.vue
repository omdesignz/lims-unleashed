<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import datePicker from '@/Components/date-picker.vue'
import combobox from '@/Components/combobox.vue';
import ComboboxMultiple from '@/Components/combobox-multiple.vue'; 
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';


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

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const form = useForm({
    customer_id: '',
    warehouse_id: '',
    collaborations: [],
    collection_date: '',
    products: []
});

const addProduct = () => {
    form.products.push({
        product_id: '',
        temperature_id: '',
        collection_id: '',
        pack_id: '',
        result_id: '',
        vehicle_id: '',
        comercial_brand: '',
        du_no: '',
        term_no: '',
        container_no: '',
        recollection: false,
        obs: null,
        processed: false,
        expiry_date: null,
        collection_date: form.collection_date,
        qty: '',
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
    router.get(route('analysis.create'), {term}, {
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

function loadWarehouses(query, setOptions) {
    fetch('/warehouses/getWarehouse?q=' + query + (form.customer_id ? '&customer_id=' + form.customer_id.value : ''))
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.address,
            };
        })
        );
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
  form.post(route('analysis.store'), {
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('analysis.update',{analysis: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
}

}

</script>

<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Colheitas Directas</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">A iniciar o processo para o cliente: {{ form.customer_id.label }}</p>
</div>

<form :class="commercialDocumentThemeClasses" @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="collection_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.collection_date') }}</label>
            <div class="mt-2">
              <date-picker class="py-1.5" v-model.string="form.collection_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
            </div>
            <p v-if="form.errors.collection_date" class="mt-2 text-xs text-red-600" id="collection_date-error">{{ form.errors.collection_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.customer_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value="loadWarehouses"/>
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.warehouse_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="price" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.collaborations') }}</label>
            <div class="mt-2">
              <ComboboxMultiple v-model="form.collaborations" :load-options="loadCollectionCollaboration" multiple/>
            </div>
            <p v-if="form.errors.collaborations" class="mt-2 text-xs text-red-600" id="collaborations-error">{{ form.errors.collaborations }}</p>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.products.length }} {{ $t('gestlab.general.labels.analysis.products') }}
          <button @click="addProduct" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.analysis.products_tagline') }}: {{ form.customer_id.label }}</p>

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
              {{ index + 1 }}º {{ $t('gestlab.general.labels.analysis.product') }}

            <button @click="removeProduct(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="product_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.product_id') }}</label>
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
                  <label for="expiry_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.expiry_date') }}</label>
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
                  <label for="comercial_brand" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.comercial_brand') }}</label>
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
                  <label for="pack_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.pack_id') }}</label>
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
                  <label for="qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.qty') }}</label>
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
                  <label for="collected_qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.collected_qty') }}</label>
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
                  <label for="lot" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.lot') }}</label>
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
                  <label for="bl" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.bl') }}</label>
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
                  <label for="du_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.du_no') }}</label>
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
                  <label for="term_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.term_no') }}</label>
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
                  <label for="container_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.container_no') }}</label>
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
                  <label for="temperature_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.temperature_id') }}</label>
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
                  <label for="result_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.result_id') }}</label>
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
                  <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.analysis.obs') }}</label>
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
      <button @click="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button>
    </div>
  </form>

</template>
