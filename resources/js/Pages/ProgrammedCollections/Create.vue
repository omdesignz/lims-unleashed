<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, watch } from "vue";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { Link, router, useForm } from "@inertiajs/vue3";
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import ComboboxMultipleEnhanced from '@/Components/combobox-multiple-enhanced.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
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
    },
    entrypoint: { type: Object, default: () => ({}) },
});

const updateDate = (e) => {
  form.collection_date = e;
}

const showDeleteConfirmation = ref(false);

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const form = useForm({
    customer_id: '',
    warehouse_id: '',
    vehicle_id: '',
    vehicle_reference: '',
    collaborations: [],
    collectionreasons: [],
    collection_date: '',
    collection_location: '',
    products: []
});

watch(() => [form.customer_id?.value], () => {
  if (!form.customer_id?.value) return;

  loadSelectOptions(
    '/warehouses/getWarehouse',
    '',
    warehouses => {
      form.warehouse_id = warehouses[0] || '';
    },
    optionMappers.address,
    { customer_id: form.customer_id.value }
  );
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
        origin: '',
        location: '',
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
    router.get(route('programmedcollections.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)


function loadCustomers(query, setOptions) {
    return loadSelectOptions('/customers/getCustomer', query, setOptions, optionMappers.name);
}

function loadWarehouses(query, setOptions) {
    return loadSelectOptions('/warehouses/getWarehouse', query, setOptions, optionMappers.address, {
      customer_id: form.customer_id?.value,
    });
}

function loadProducts(query, setOptions) {
    return loadSelectOptions('/products/getProduct', query, setOptions, optionMappers.name);
}

function loadVehicles(query, setOptions) {
    return loadSelectOptions('/vehicles/getVehicle', query, setOptions, optionMappers.numberPlate);
}

function loadPackagingCategories(query, setOptions) {
    return loadSelectOptions('/packagingcategories/getPackagingCategory', query, setOptions, optionMappers.name);
}

function loadEndResults(query, setOptions) {
    return loadSelectOptions('/collectionendresults/getCollectionEndResult', query, setOptions, optionMappers.name);
}

function loadCollectionCollaboration(query, setOptions) {
    return loadSelectOptions('/collectioncollaborations/getCollectionCollaboration', query, setOptions, optionMappers.name);
}

function loadCollectionReason(query, setOptions) {
    return loadSelectOptions('/collectionreasons/getCollectionReason', query, setOptions, optionMappers.name);
}

function loadTemperatures(query, setOptions) {
    return loadSelectOptions('/temperatures/getTemperature', query, setOptions, optionMappers.name);
}

function loadUsers(query, setOptions) {
    return loadSelectOptions('/users/getUser', query, setOptions, optionMappers.name);
}

let submit = () => {

if(!form.id) {
  form.post(route('programmedcollections.store'), {
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('programmedcollections.update',{collection: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
}

}

</script>

<template>
<div class="programmed-collection-form space-y-8" :class="commercialDocumentThemeClasses">
<div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
    <h3 class="flex items-center gap-2 text-2xl font-semibold leading-7 text-slate-900 dark:text-white">
      <ClipboardDocumentCheckIcon class="h-7 w-7 text-primary-900 dark:text-primary-300" />
      {{ $t('gestlab.general.labels.programmed_collections.page_title') }}
    </h3>
    <p class="mt-2 max-w-4xl text-sm text-slate-600 dark:text-slate-300">
      {{ $t('gestlab.general.labels.programmed_collections.page_create_description') }}
      <span v-if="form.customer_id?.label" class="font-semibold text-primary-900 dark:text-primary-300">{{ form.customer_id.label }}</span>
    </p>
</div>

<section class="rounded-[28px] border border-primary-100 bg-gradient-to-br from-white via-primary-50/70 to-white p-5 shadow-[0_18px_50px_-26px_rgba(15,23,42,0.25)] dark:border-primary-500/20 dark:from-slate-950 dark:via-primary-500/10 dark:to-slate-900/80">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-700 dark:text-primary-300">
        Fluxo recomendado
      </p>
      <h2 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
        {{ props.entrypoint?.label || 'Use Sample Entry para novos fluxos' }}
      </h2>
      <p class="mt-1 max-w-4xl text-sm text-slate-600 dark:text-slate-300">
        {{ props.entrypoint?.description || 'Para novos processos programados, comece pela receção da amostra para manter produto, matriz, local, equipa, lab code e análises ligados.' }}
      </p>
    </div>

    <Link
      :href="props.entrypoint?.create_sample_url || route('vap_samples.index', { collection_type: 'programmed' })"
      class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
    >
      <ClipboardDocumentCheckIcon class="size-5" aria-hidden="true" />
      Criar via Sample Entry
    </Link>
  </div>
</section>

<form @submit.prevent class="rounded-[30px] border border-slate-200 bg-white/95 p-5 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85 sm:p-7">
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="collection_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collection_date') }}</label>
            <div class="mt-2">
              <date-picker-enhanced v-model="form.collection_date" :has-error="form.errors.collection_date" :error-message="form.errors.collection_date" :masks="masks" />
            </div>
            <p v-if="form.errors.collection_date" class="mt-2 text-xs text-red-600" id="collection_date-error">{{ form.errors.collection_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.customer_id') }}</label>
            <div class="mt-2">
              <comboboxEnhanced :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" placeholder="Selecionar cliente..." />
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.warehouse_id') }}</label>
            <div class="mt-2">
              <comboboxEnhanced :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses" placeholder="Selecionar armazém..." />
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="collection_location" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collection_location') }}</label>
            <div class="mt-2">
              <input v-model="form.collection_location" type="text" name="collection_location-error" id="collection_location-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.collection_location" class="mt-2 text-xs text-red-600" id="collection_location-error">{{ form.errors.collection_location }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="collaborations" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collaborations') }}</label>
            <div class="mt-2">
              <ComboboxMultipleEnhanced v-model="form.collaborations" :load-options="loadCollectionCollaboration" multiple placeholder="Selecionar colaborações..." />
            </div>
            <p v-if="form.errors.collaborations" class="mt-2 text-xs text-red-600" id="collaborations-error">{{ form.errors.collaborations }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="collectionreasons" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collectionreasons') }}</label>
            <div class="mt-2">
              <ComboboxMultipleEnhanced v-model="form.collectionreasons" :load-options="loadCollectionReason" multiple placeholder="Selecionar motivos..." />
            </div>
            <p v-if="form.errors.collectionreasons" class="mt-2 text-xs text-red-600" id="collectionreasons-error">{{ form.errors.collectionreasons }}</p>
          </div>

        </div>

      <div class="border-b border-slate-200 pb-12 dark:border-slate-800">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.products.length }} {{ $t('gestlab.general.labels.programmed_collections.products') }}
          <button @click="addProduct" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.programmed_collections.products_tagline') }} {{ form.customer_id.label }}</p>

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
              {{ index + 1 }}º {{ $t('gestlab.general.labels.programmed_collections.product') }}

            <button @click="removeProduct(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="product_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.product_id') }}</label>
                  <div class="mt-2">
                      <comboboxEnhanced :hasError="form.errors[`products.${index}.product_id`]" v-model="product.product_id" :load-options="loadProducts" placeholder="Selecionar produto..." />
                  </div>
                  <p v-if="form.errors[`products.${index}.product_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.product_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="origin" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.origin') }}</label>
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
                  <label for="production_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.production_date') }}</label>
                  <div class="mt-2">
                    <date-picker-enhanced v-model="product.production_date" :has-error="form.errors[`products.${index}.production_date`]" :error-message="form.errors[`products.${index}.production_date`]" :masks="masks" />
                  </div>
                  <p v-if="form.errors[`products.${index}.production_date`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.production_date`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="expiry_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.expiry_date') }}</label>
                  <div class="mt-2">
                    <date-picker-enhanced v-model="product.expiry_date" :has-error="form.errors[`products.${index}.expiry_date`]" :error-message="form.errors[`products.${index}.expiry_date`]" :masks="masks" />
                  </div>
                  <p v-if="form.errors[`products.${index}.expiry_date`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.expiry_date`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="comercial_brand" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.comercial_brand') }}</label>
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
                  <label for="pack_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.pack_id') }}</label>
                  <div class="mt-2">
                      <comboboxEnhanced :hasError="form.errors[`products.${index}.pack_id`]" v-model="product.pack_id" :load-options="loadPackagingCategories" placeholder="Selecionar embalagem..." />
                  </div>
                  <p v-if="form.errors[`products.${index}.pack_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.pack_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.qty') }}</label>
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
                  <label for="collected_qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_qty') }}</label>
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
                  <label for="location" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.location') }}</label>
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
                  <label for="sample_status" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.sample_status') }}</label>
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
                  <label for="sampling_plan_ref" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.sampling_plan_ref') }}</label>
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
                  <label for="customer_submitted_info" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.customer_submitted_info') }}</label>
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
                  <label for="lot" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.lot') }}</label>
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
                  <label for="bl" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.bl') }}</label>
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
                  <label for="du_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.du_no') }}</label>
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
                  <label for="term_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.term_no') }}</label>
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
                  <label for="container_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.container_no') }}</label>
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
                  <label for="temperature_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_id') }}</label>
                  <div class="mt-2">
                      <comboboxEnhanced :hasError="form.errors[`products.${index}.temperature_id`]" v-model="product.temperature_id" :load-options="loadTemperatures" placeholder="Selecionar temperatura..." />
                  </div>
                  <p v-if="form.errors[`products.${index}.temperature_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.temperature_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="temperature_value" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_value') }}</label>
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
                  <label for="collected_by_lab" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_by_lab') }}</label>
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
                  <label for="vehicle_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.vehicle_id') }}</label>
                  <div class="mt-2">
                      <comboboxEnhanced :hasError="form.errors[`products.${index}.vehicle_id`]" v-model="product.vehicle_id" :load-options="loadVehicles" placeholder="Selecionar viatura..." />
                  </div>
                  <p v-if="form.errors[`products.${index}.vehicle_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.vehicle_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="result_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.result_id') }}</label>
                  <div class="mt-2">
                      <comboboxEnhanced :hasError="form.errors[`products.${index}.result_id`]" v-model="product.result_id" :load-options="loadEndResults" placeholder="Selecionar resultado final..." />
                  </div>
                  <p v-if="form.errors[`products.${index}.result_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.result_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="owner_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.owner_id') }}</label>
                  <div class="mt-2">
                      <comboboxEnhanced :hasError="form.errors[`products.${index}.owner_id`]" v-model="product.owner_id" :load-options="loadUsers" placeholder="Selecionar responsável..." />
                  </div>
                  <p v-if="form.errors[`products.${index}.owner_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`products.${index}.owner_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.obs') }}</label>
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
      <button  @click="showDeleteConfirmation = true" :disabled="form.processing" class="inline-flex justify-center rounded-2xl bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-700/20 transition hover:bg-primary-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600 dark:bg-primary-500 dark:hover:bg-primary-400 dark:disabled:bg-slate-800 dark:disabled:text-slate-400">{{ $t('gestlab.general.buttons.submit') }}</button>
    </div>
  </form>

  <confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="submit" v-if="showDeleteConfirmation" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <div class="programmed-collection-form mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collection_date') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collection_date }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.customer_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.warehouse_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collaborations') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collaborations?.map((item) => item.label).join(", ") }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collectionreasons') }}</dt>
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
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.origin') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.origin }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.production_date') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.production_date }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.expiry_date') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.expiry_date }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.comercial_brand') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.comercial_brand }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.pack_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.pack_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.qty') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.qty }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_qty') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.collected_qty }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.location') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.location }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.lot') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.lot }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.bl') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.bl }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.du_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.du_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.term_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.term_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.container_no') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.container_no }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.temperature_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_value') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.temperature_value }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_by_lab') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.collected_by_lab ? 'SIM' : 'NÃO' }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.vehicle_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.vehicle_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.result_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.result_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.owner_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.owner_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.obs') }}</dt>
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

</div>
</template>

<style scoped>
.programmed-collection-form {
  --collection-ink: #17231f;
  --collection-muted: #647067;
  --collection-border: #d9d1bf;
  --collection-primary: #123f38;
}

.programmed-collection-form form {
  background-image:
    radial-gradient(circle at top left, rgba(20, 184, 166, 0.12), transparent 34rem),
    linear-gradient(135deg, rgba(255, 250, 240, 0.98), rgba(255, 255, 255, 0.94));
}

.programmed-collection-form :is(label, dt) {
  color: var(--collection-ink);
  font-weight: 700;
}

.programmed-collection-form :is(input:not([type="checkbox"]), textarea, select) {
  width: 100%;
  border: 1px solid var(--collection-border);
  border-radius: 1rem;
  background: rgba(255, 250, 240, 0.94);
  color: var(--collection-ink);
  box-shadow: 0 12px 32px -24px rgba(23, 35, 31, 0.35);
  transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.programmed-collection-form :is(input:not([type="checkbox"]), textarea, select):focus {
  border-color: var(--collection-primary);
  box-shadow: 0 0 0 4px rgba(18, 63, 56, 0.14);
  outline: none;
}

.programmed-collection-form textarea {
  min-height: 7rem;
}

.programmed-collection-form input[type="checkbox"] {
  border-color: var(--collection-primary);
  color: var(--collection-primary);
}

.programmed-collection-form :is([class~="text-gray-900"], [class~="text-gray-800"], [class~="text-gray-700"]) {
  color: var(--collection-ink) !important;
}

.programmed-collection-form :is([class~="text-gray-600"], [class~="text-gray-500"], [class~="text-gray-400"]) {
  color: var(--collection-muted) !important;
}

.programmed-collection-form :is([class~="text-blue-900"], [class~="text-blue-800"]) {
  color: var(--collection-primary) !important;
}

.programmed-collection-form :is([class~="bg-blue-900"], [class~="bg-blue-800"]) {
  background-color: var(--collection-primary) !important;
}

.programmed-collection-form :is([class~="border-gray-100"], [class~="border-gray-200"], [class~="border-gray-300"], [class~="ring-gray-300"]) {
  border-color: var(--collection-border) !important;
  --tw-ring-color: var(--collection-border) !important;
}

.programmed-collection-form :is([class~="bg-white"], [class~="bg-gray-50"]) {
  background-color: rgba(255, 250, 240, 0.92) !important;
}

:global(.dark) .programmed-collection-form {
  --collection-ink: #f8fafc;
  --collection-muted: #cbd5e1;
  --collection-border: rgba(148, 163, 184, 0.28);
  --collection-primary: #7dd3c7;
}

:global(.dark) .programmed-collection-form form {
  background-image:
    radial-gradient(circle at top left, rgba(20, 184, 166, 0.16), transparent 34rem),
    linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(2, 6, 23, 0.9));
}

:global(.dark) .programmed-collection-form :is(input:not([type="checkbox"]), textarea, select) {
  background: rgba(15, 23, 42, 0.86);
  color: #f8fafc;
  border-color: rgba(148, 163, 184, 0.28);
  box-shadow: 0 18px 42px -28px rgba(0, 0, 0, 0.85);
}

:global(.dark) .programmed-collection-form :is(input:not([type="checkbox"]), textarea, select)::placeholder {
  color: #94a3b8;
}

:global(.dark) .programmed-collection-form :is([class~="bg-white"], [class~="bg-gray-50"]) {
  background-color: rgba(15, 23, 42, 0.88) !important;
}

:global(.dark) .programmed-collection-form :is([class~="bg-blue-900"], [class~="bg-blue-800"]) {
  background-color: #0f766e !important;
}
</style>
