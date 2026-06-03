<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';


defineOptions({
  layout: Layout
});

const props = defineProps({});

const form = useForm({
    name: '',
    code: '',
    description: '',
    category_id: '',
    warehouses: []
});

const addWarehouse = () => {
    form.warehouses.push({
        email: '',
        primary_phone: '',
        alternative_phone: '',
        nif: '',
        address: '',
        description: '',
        code: '',
    });
}


function loadCategories(query, setOptions) {
    fetch('/customercategories/getCustomerCategory?q=' + query)
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
  form.post(route('customers.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('customers.update',{customer: form.id}), {
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
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.customers.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.customers.page_create_description') }} {{ form.name }}</p>
</div>

<form :class="commercialDocumentThemeClasses" @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customers.name') }}</label>
            <div class="mt-2">
              <input v-model="form.name" type="text" name="name" id="name" autocomplete="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.name" class="mt-2 text-xs text-red-600" id="name-error">{{ form.errors.name }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customers.description') }}</label>
            <div class="mt-2">
              <input v-model="form.description" type="text" name="description" id="description" autocomplete="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.description" class="mt-2 text-xs text-red-600" id="description-error">{{ form.errors.description }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="category_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.customers.category_id') }}</label>
            <div class="mt-2">
                <combobox v-model="form.category_id" :hasError="form.errors.category_id" :load-options="loadCategories"/>
            </div>
            <p v-if="form.errors.category_id" class="mt-2 text-xs text-red-600" id="category_id-error">{{ form.errors.category_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <div class="mt-2">
            <!-- <label for="button" class="block text-sm font-medium leading-6 text-gray-900"></label> -->
              <button v-if="form.isDirty" @click="submit" :disabled="form.processing" class="inline-flex justify-center mt-6 rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button>
            </div>
          </div>

        </div>

    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <!-- <button @click="submit" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Registrar</button> -->
    </div>
  </form>

</template>
