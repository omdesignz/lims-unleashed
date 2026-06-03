<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object
});

let customerWarehouses = reactive([]);

const form = useForm({
  id: props.record?.id,
  obs: props.record?.obs,
  guide_no: props.record?.guide_no,
  ref_no: props.record?.ref_no,
  entry_point: props.record?.entry_point,
  collection_point: props.record?.collection_point,
  du_no: props.record?.du_no,
  nif: props.record?.nif,
  contact: props.record?.contact,
  email: props.record?.email,
  bl: props.record?.bl,
  customer_id: props.record?.customer_id,
  warehouse_id: props.record?.warehouse_id,
  collection_id: props.record?.collection_id,
  items: props.record?.items
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

onMounted(() => {

});

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
}

const removeProduct = (index) => {
    form.items.splice(index, 1);
}

const showDeleteConfirmation = ref(false);

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('contractguides.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)

function loadResultCategories(query, setOptions) {
    fetch('/resultcategories/getResultCategory?q=' + query)
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

function loadCountries(query, setOptions) {
    fetch('/countries/getCountry?q=' + query)
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

let submitItem = (item) => {
    useForm({
      id: item.id,
      obs: item.obs,
      guide_id: item.guide_id,
      product_id: item.product_id,
      country_id: item.country_id,
      origin: item.origin,
      manufacturer: item.manufacturer,
      brand: item.brand,
      lot: item.lot,
      du_no: item.du_no,
      bl: item.bl,
      collection_id: item.collection_id,
      obs: item.obs,
      date: item.date,
    })
    .put(route('contractguideitems.update', {item: item.id}), {
      preserveScroll: true,
      preserveState: false,
        onSuccess: () => {
          form.reset()
        },
    });
}

let submit = () => {

if(!form.id) {
  form.post(route('contractguides.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('contractguides.update',{guide: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        openslideover.value = false;
        form.reset()
      },
  });
}

}

</script>

<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.contract_guides.page_update_description') }} {{ form.guide_no }}</p>
</div>

<form :class="commercialDocumentThemeClasses" @submit.prevent>
    <div class="space-y-12">
      
      <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

      <div class="sm:col-span-2 sm:col-start-1">
        <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.customer_id') }}</label>
        <div class="mt-2">
          <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value=""/>
        </div>
        <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.warehouse_id') }}</label>
        <div class="mt-2">
          <combobox :disableInput="!form.customer_id" :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
        </div>
        <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="ref_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.ref_no') }}</label>
        <div class="mt-2">
          <input v-model="form.ref_no" type="text" name="ref_no" id="ref_no" autocomplete="ref_no" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.ref_no ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.ref_no" class="mt-2 text-xs text-red-600" id="ref_no-error">{{ form.errors.ref_no }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="du_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.du_no') }}</label>
        <div class="mt-2">
          <input v-model="form.du_no" type="text" name="du_no" id="du_no" autocomplete="du_no" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.du_no ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.du_no" class="mt-2 text-xs text-red-600" id="du_no-error">{{ form.errors.du_no }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="collection_point" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.entry_point') }}</label>
        <div class="mt-2">
          <input v-model="form.collection_point" type="text" name="collection_point" id="collection_point" autocomplete="collection_point" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.collection_point ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.collection_point" class="mt-2 text-xs text-red-600" id="collection_point-error">{{ form.errors.collection_point }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="nif" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.nif') }}</label>
        <div class="mt-2">
          <input v-model="form.nif" type="text" name="nif" id="nif" autocomplete="nif" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.nif ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.nif" class="mt-2 text-xs text-red-600" id="nif-error">{{ form.errors.nif }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="contact" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.contact') }}</label>
        <div class="mt-2">
          <input v-model="form.contact" type="text" name="contact" id="contact" autocomplete="contact" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.contact ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.contact" class="mt-2 text-xs text-red-600" id="contact-error">{{ form.errors.contact }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="email" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.email') }}</label>
        <div class="mt-2">
          <input v-model="form.email" type="email" name="email" id="email" autocomplete="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.email" class="mt-2 text-xs text-red-600" id="email-error">{{ form.errors.email }}</p>
      </div>

      <div class="sm:col-span-4">
        <label for="entry_point" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.entry_point') }}</label>
        <div class="mt-2">
          <input v-model="form.entry_point" type="text" name="entry_point" id="entry_point" autocomplete="entry_point" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.entry_point ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
        </div>
        <p v-if="form.errors.entry_point" class="mt-2 text-xs text-red-600" id="entry_point-error">{{ form.errors.entry_point }}</p>
      </div>

      </div>

      <div class="border-b border-gray-900/10 pb-12">
      <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
      {{ form.items.length }} {{ $t('gestlab.general.labels.contract_guides.products') }}
      <button @click="addProduct" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
        <PlusCircleIcon class="h-5 w-5" />
      </button>
      </h2>
      <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.contract_guides.products_tagline') }} {{ form.guide_no }}</p>

      </div>

      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(product, index) in form.items" :key="product.value" class="rounded-xl border border-gray-200"
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
              {{ index + 1 }}º {{ $t('gestlab.general.labels.contract_guides.product') }}

            <button @click="removeProduct(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="product_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.product_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`items.${index}.product_id`]" v-model="product.product_id" :load-options="loadProducts"/>
                  </div>
                  <p v-if="form.errors[`items.${index}.product_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.product_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="country_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.country_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`items.${index}.country_id`]" v-model="product.country_id" :load-options="loadCountries"/>
                  </div>
                  <p v-if="form.errors[`items.${index}.country_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.country_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="manufacturer" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.manufacturer') }}</label>
                  <div class="mt-2">
                    <input v-model="product.manufacturer" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.manufacturer`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.manufacturer`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="brand" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.brand') }}</label>
                  <div class="mt-2">
                    <input v-model="product.brand" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.brand`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.brand`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="lot" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.lot') }}</label>
                  <div class="mt-2">
                    <input v-model="product.lot" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.lot`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.lot`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="bl" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.bl') }}</label>
                  <div class="mt-2">
                    <input v-model="product.bl" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.bl`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.bl`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="du_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.du_no') }}</label>
                  <div class="mt-2">
                    <input v-model="product.du_no" type="text" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.du_no`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.du_no`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.obs') }}</label>
                  <div class="mt-2">
                    <textarea v-model="product.obs" type="text" name="obs" id="obs" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.obs`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.obs`] }}</p>
                </div>
              </dd>
            </div>

            <div class="mt-2 flex items-center justify-end">
              <button @click="submitItem(product)" class="inline-flex justify-center w-full rounded-md bg-orange-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-800">{{ $t('gestlab.general.buttons.update') }}</button>
            </div>

          </dl>
        </li>
      </ul>
      <p v-if="form.errors.parameters" class="mt-2 text-xs text-red-600">{{ form.errors.parameters }}</p>
    </div>

    <div class="sm:col-span-full">
      <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.obs') }}</label>
      <div class="mt-2">
        <textarea v-model="form.obs" type="text" name="obs" id="obs" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
      </div>
      <p v-if="form.errors.obs" class="mt-2 text-xs text-red-600" id="obs">{{ form.errors.obs }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <button @click="showDeleteConfirmation = true" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.update') }}</button>
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.customer_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.warehouse_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.ref_no') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.ref_no }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.du_no') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.du_no }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.collection_point') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collection_point }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.nif') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.nif }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.contact') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.contact }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.email') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.email }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.entry_point') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.entry_point }}</dd>
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
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.product_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.product_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.country_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.country_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.manufacturer') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.manufacturer }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.brand') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.brand }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.lot') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.lot }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.bl') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.bl }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.du_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.du_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.contract_guides.obs') }}</dt>
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
