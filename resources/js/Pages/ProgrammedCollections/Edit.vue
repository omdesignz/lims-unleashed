<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import datePicker from '@/Components/date-picker.vue'
import combobox from '@/Components/combobox.vue';
import ComboboxMultiple from '@/Components/combobox-multiple.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import confirmDialog from "@/Components/confirm-dialog.vue";


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object
});

const form = useForm({
    id: props.record?.id,
    code: props.record?.code,
    collection_date: props.record?.collection_date,
    expiry_date: props.record?.expiry_date,
    production_date: props.record?.production_date,
    obs: props.record?.obs,
    lot: props.record?.lot,
    bl: props.record?.bl,
    temperature_value: props.record?.temperature_value,
    container_no: props.record?.container_no,
    du_no: props.record?.du_no,
    term_no: props.record?.term_no,
    comercial_brand: props.record?.comercial_brand,
    qty: props.record?.qty,
    origin: props.record?.origin,
    location: props.record?.location,
    collected_qty: props.record?.collected_qty,
    invoiced: props.record?.invoiced,
    status: props.record?.status,
    processed: props.record?.processed,
    collected_by_lab: props.record?.collected_by_lab,
    recollection: props.record?.recollection,
    collection_id: props.record?.collection_id,
    vehicle_id: props.record?.vehicle_id,
    temperature_id: props.record?.temperature_id,
    customer_id: props.record?.customer_id,
    warehouse_id: props.record?.warehouse_id,
    product_id: props.record?.product_id,
    result_id: props.record?.result_id,
    owner_id: props.record?.owner_id,
    pack_id: props.record?.pack_id,
    collaborations: props.record?.collaborations,
    collectionreasons: props.record?.collectionreasons,
});

onMounted(() => {

});

const updateDate = (e) => {
  form.collection_date = e;
}

const showDeleteConfirmation = ref(false);


const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

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

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('profiles.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)


function loadUnits(query, setOptions) {
    fetch('/units/getUnit?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadProtocols(query, setOptions) {
    fetch('/protocols/getProtocol?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadNwps(query, setOptions) {
    fetch('/nwps/getNwp?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadStandards(query, setOptions) {
    fetch('/standards/getStandard?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

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


let submit = () => {

if(!form.id) {
  form.post(route('directcollections.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('directcollections.update',{collection: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        // form.reset()
      },
  });
}

}

</script>

<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.programmed_collections.page_update_description') }} {{ form.code }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="collection_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collection_date') }}</label>
            <div class="mt-2">
              <date-picker class="py-1.5" v-model.string="form.collection_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
            </div>
            <p v-if="form.errors.collection_date" class="mt-2 text-xs text-red-600" id="collection_date-error">{{ form.errors.collection_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.customer_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value="loadWarehouses"/>
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.warehouse_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
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
            <label for="price" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collaborations') }}</label>
            <div class="mt-2">
              <ComboboxMultiple v-model="form.collaborations" :load-options="loadCollectionCollaboration" multiple/>
            </div>
            <p v-if="form.errors.collaborations" class="mt-2 text-xs text-red-600" id="collaborations-error">{{ form.errors.collaborations }}</p>
          </div>

          <div class="sm:col-span-4">
            <label for="collectionreasons" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collectionreasons') }}</label>
            <div class="mt-2">
              <ComboboxMultiple v-model="form.collectionreasons" :load-options="loadCollectionReason" multiple/>
            </div>
            <p v-if="form.errors.collectionreasons" class="mt-2 text-xs text-red-600" id="collectionreasons-error">{{ form.errors.collectionreasons }}</p>
          </div>

        </div>

        <hr>


        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="product_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.product_id') }}</label>
            <div class="mt-2">
                <combobox :hasError="form.errors.product_id" v-model="form.product_id" :load-options="loadProducts"/>
            </div>
            <p v-if="form.errors.product_id" class="mt-2 text-xs text-red-600" id="product_id-error">{{ form.errors.product_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="origin" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.origin') }}</label>
            <div class="mt-2">
              <input v-model="form.origin" type="text" name="origin-error" id="origin-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.origin" class="mt-2 text-xs text-red-600" id="origin-error">{{ form.errors.origin }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="production_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.production_date') }}</label>
            <div class="mt-2">
              <date-picker class="py-1.5" v-model.string="form.production_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
            </div>
            <p v-if="form.errors.production_date" class="mt-2 text-xs text-red-600" id="production_date-error">{{ form.errors.production_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="expiry_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.expiry_date') }}</label>
            <div class="mt-2">
              <date-picker class="py-1.5" v-model.string="form.expiry_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
            </div>
            <p v-if="form.errors.expiry_date" class="mt-2 text-xs text-red-600" id="expiry_date-error">{{ form.errors.expiry_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="comercial_brand" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.comercial_brand') }}</label>
            <div class="mt-2">
              <input v-model="form.comercial_brand" type="text" name="comercial_brand" id="comercial_brand" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.comercial_brand" class="mt-2 text-xs text-red-600" id="comercial_brand">{{ form.errors.comercial_brand }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="pack_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.pack_id') }}</label>
            <div class="mt-2">
                <combobox :hasError="form.errors.pack_id" v-model="form.pack_id" :load-options="loadPackagingCategories"/>
            </div>
            <p v-if="form.errors.pack_id" class="mt-2 text-xs text-red-600" id="pack_id-error">{{ form.errors.pack_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.qty') }}</label>
            <div class="mt-2">
              <input v-model="form.qty" type="text" name="qty-error" id="qty-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.qty" class="mt-2 text-xs text-red-600" id="qty-error">{{ form.errors.qty }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="collected_qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_qty') }}</label>
            <div class="mt-2">
              <input v-model="form.collected_qty" type="text" name="collected_qty-error" id="collected_qty-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.collected_qty" class="mt-2 text-xs text-red-600" id="collected_qty-error">{{ form.errors.collected_qty }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="location" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.location') }}</label>
            <div class="mt-2">
              <input v-model="form.location" type="text" name="location-error" id="location-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.location" class="mt-2 text-xs text-red-600" id="location-error">{{ form.errors.location }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="sample_status" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.sample_status') }}</label>
            <div class="mt-2">
              <input v-model="form.sample_status" type="text" name="sample_status-error" id="sample_status-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.sample_status" class="mt-2 text-xs text-red-600" id="sample_status-error">{{ form.errors.sample_status }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="sampling_plan_ref" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.sampling_plan_ref') }}</label>
            <div class="mt-2">
              <input v-model="form.sampling_plan_ref" type="text" name="sampling_plan_ref-error" id="sampling_plan_ref-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.sampling_plan_ref" class="mt-2 text-xs text-red-600" id="sampling_plan_ref-error">{{ form.errors.sampling_plan_ref }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_submitted_info" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.customer_submitted_info') }}</label>
            <div class="mt-2">
              <input v-model="form.customer_submitted_info" type="text" name="customer_submitted_info-error" id="customer_submitted_info-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.customer_submitted_info" class="mt-2 text-xs text-red-600" id="customer_submitted_info-error">{{ form.errors.customer_submitted_info }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="lot" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.lot') }}</label>
            <div class="mt-2">
              <input v-model="form.lot" type="text" name="lot-error" id="lot-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.lot" class="mt-2 text-xs text-red-600" id="lot-error">{{ form.errors.lot }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="bl" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.bl') }}</label>
            <div class="mt-2">
              <input v-model="form.bl" type="text" name="bl-error" id="bl-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.bl" class="mt-2 text-xs text-red-600" id="bl-error">{{ form.errors.bl }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="du_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.du_no') }}</label>
            <div class="mt-2">
              <input v-model="form.du_no" type="text" name="du_no-error" id="du_no-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.du_no" class="mt-2 text-xs text-red-600" id="du_no-error">{{ form.errors.du_no }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="term_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.term_no') }}</label>
            <div class="mt-2">
              <input v-model="form.term_no" type="text" name="term_no-error" id="term_no-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.term_no" class="mt-2 text-xs text-red-600" id="term_no-error">{{ form.errors.term_no }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="container_no" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.container_no') }}</label>
            <div class="mt-2">
              <input v-model="form.container_no" type="text" name="container_no-error" id="container_no-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.container_no" class="mt-2 text-xs text-red-600" id="container_no-error">{{ form.errors.container_no }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="temperature_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_id') }}</label>
            <div class="mt-2">
                <combobox :hasError="form.errors.temperature_id" v-model="form.temperature_id" :load-options="loadTemperatures"/>
            </div>
            <p v-if="form.errors.temperature_id" class="mt-2 text-xs text-red-600" id="temperature_id-error">{{ form.errors.temperature_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="temperature_value" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_value') }}</label>
            <div class="mt-2">
              <input v-model="form.temperature_value" type="text" name="temperature_value-error" id="temperature_value-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.temperature_value" class="mt-2 text-xs text-red-600" id="temperature_value-error">{{ form.errors.temperature_value }}</p>
          </div>

          <!-- Collected By Lab -->
           <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
            <div>
              <label for="collected_by_lab" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_by_lab') }}</label>
              <div class="mt-2">
                <input v-model="form.collected_by_lab" type="checkbox" name="collected_by_lab" id="collected_by_lab" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
              </div>
              <p v-if="form.errors.collected_by_lab" class="mt-2 text-xs text-red-600" id="collected_by_lab-error">{{ form.errors.collected_by_lab }}</p>
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="vehicle_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.vehicle_id') }}</label>
            <div class="mt-2">
                <combobox :hasError="form.errors.vehicle_id" v-model="form.vehicle_id" :load-options="loadVehicles"/>
            </div>
            <p v-if="form.errors.vehicle_id" class="mt-2 text-xs text-red-600" id="vehicle_id-error">{{ form.errors.vehicle_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="result_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.result_id') }}</label>
            <div class="mt-2">
                <combobox :hasError="form.errors.result_id" v-model="form.result_id" :load-options="loadEndResults"/>
            </div>
            <p v-if="form.errors.result_id" class="mt-2 text-xs text-red-600" id="result_id-error">{{ form.errors.result_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="owner_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.owner_id') }}</label>
            <div class="mt-2">
                <combobox :hasError="form.errors.owner_id" v-model="form.owner_id" :load-options="loadUsers"/>
            </div>
            <p v-if="form.errors.owner_id" class="mt-2 text-xs text-red-600" id="owner_id-error">{{ form.errors.owner_id }}</p>
          </div>

          <div class="sm:col-span-full">
            <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.obs') }}</label>
            <div class="mt-2">
              <textarea v-model="form.obs" type="text" name="obs-error" id="obs-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
            <p v-if="form.errors.obs" class="mt-2 text-xs text-red-600" id="obs-error">{{ form.errors.obs }}</p>
          </div>


          

        </div>

    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <button @click="showDeleteConfirmation = true" :disabled="form.processing" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.update') }}</button>
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
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.origin') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.origin }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.production_date') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.production_date }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.expiry_date') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.expiry_date }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.comercial_brand') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.comercial_brand }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.pack_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.pack_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.qty') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.qty }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_qty') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collected_qty }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.location') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.location }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.lot') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.lot }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.bl') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.bl }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.du_no') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.du_no }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.term_no') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.term_no }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.container_no') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.container_no }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.temperature_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.temperature_value') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.temperature_value }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.collected_by_lab') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.collected_by_lab ? 'SIM' : 'NÃO' }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.vehicle_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.vehicle_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.result_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.result_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.owner_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.owner_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.obs') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.obs }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>