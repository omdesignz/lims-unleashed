<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import datePicker from '@/Components/date-picker.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object
});

const form = useForm({
    id: props.record?.id,
    date: props.record?.date,
    supplier_id: props.record?.supplier_id,
    obs: props.record?.obs,
    items: props.record?.items
});

onMounted(() => {

});

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const addItem = () => {
    form.items.push({
        item_id: '',
        qty: 1,
        expected_date: '',
        actual_date: '',
        warehouse_id: '',
    });
}

const removeItem = (index) => {
    form.items.splice(index, 1);
}


function loadSuppliers(query, setOptions) {
    fetch('/isuppliers/getInventoryItemSupplier?q=' + query)
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
    fetch('/iwarehouses/getInventoryItemWarehouse?q=' + query)
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

function loadItems(query, setOptions) {
    fetch('/iitems/getInventoryItem?q=' + query)
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
  form.post(route('iorders.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('iorders.update',{iorder: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset()
      },
  });
}

}

</script>

<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.iorders.page_update_description') }} {{ form?.name }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-12">
      
      <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

      <div class="sm:col-span-2 sm:col-start-1">
        <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.date') }}</label>
        <div class="mt-2">
          <date-picker class="py-1.5" v-model.string="form.date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="(e) => form.date = e" :masks="masks" />
        </div>
        <p v-if="form.errors.name" class="mt-2 text-xs text-red-600" id="name-error">{{ form.errors.name }}</p>
      </div>

      <div class="sm:col-span-2">
        <label for="supplier_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.supplier_id') }}</label>
        <div class="mt-2">
          <combobox v-model="form.supplier_id" :load-options="loadSuppliers"/>
        </div>
        <p v-if="form.errors.supplier_id" class="mt-2 text-xs text-red-600" id="supplier_id-error">{{ form.errors.supplier_id }}</p>
      </div>
      </div>

      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.items.length }} {{ $t('gestlab.general.labels.iorders.items') }}
          <button @click="addItem" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.iorders.items_tagline') }} {{ form?.name }}</p>

      </div>

      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(item, index) in form.items" :key="item.value" class="rounded-xl border border-gray-200"
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
              {{ index + 1 }}º {{ $t('gestlab.general.labels.iorderdetails.item') }}

            <button @click="removeItem(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="item_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorderdetails.item_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`items.${index}.item_id`]" v-model="item.item_id" :load-options="loadItems"/>
                  </div>
                  <p v-if="form.errors[`items.${index}.item_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.item_id`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="qty" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorderdetails.qty') }}</label>
                  <div class="mt-2">
                    <input v-model="item.qty" type="number" :name="`item-${index}-error`" :id="`item-${index}-error`" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors[`items.${index}.qty`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.qty`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="expected_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorderdetails.expected_date') }}</label>
                  <div class="mt-2">
                    <date-picker class="py-1.5" v-model.string="item.expected_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="(e) => form.expected_date = e" :masks="masks" />
                  </div>
                  <p v-if="form.errors[`items.${index}.expected_date`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.expected_date`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="actual_date" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorderdetails.actual_date') }}</label>
                  <div class="mt-2">
                    <date-picker class="py-1.5" v-model.string="item.actual_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="(e) => form.actual_date = e" :masks="masks" />
                  </div>
                  <p v-if="form.errors[`items.${index}.actual_date`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.actual_date`] }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorderdetails.warehouse_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`items.${index}.warehouse_id`]" v-model="item.warehouse_id" :load-options="loadWarehouses"/>
                  </div>
                  <p v-if="form.errors[`items.${index}.warehouse_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`items.${index}.warehouse_id`] }}</p>
                </div>
              </dd>
            </div>

          </dl>
        </li>
      </ul>
      <p v-if="form.errors.items" class="mt-2 text-xs text-red-600">{{ form.errors.items }}</p>
    </div>

    <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

    <!-- Observações -->
    <div class="sm:col-span-full">
      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-700">{{ $t('gestlab.general.labels.iorders.obs') }}</label>
        <div class="mt-1">
          <textarea v-model="form.obs" id="obs" name="obs" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus-visible:outline-none sm:text-sm sm:leading-6" placeholder=""></textarea>
        </div>
      </div>
      <p v-if="form.errors.obs" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors.obs }}</p>
    </div>

    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <button v-if="form.isDirty" @click="submit" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Modificar</button>
    </div>
  </form>

</div>
</template>
