<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon, MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import Fuse from 'fuse.js';


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object,
    permissions: Array
});

const fuse = new Fuse(props?.permissions, {
      keys: ['label'],
      isCaseSensitive: false
    });

const permInput = ref('');

const results = computed(() => {
  return permInput.value ? fuse.search(permInput.value) : props?.permissions?.map(item => ({
    item: Object.assign(item, {}),
    matches: [],
    score: 1
  }));
});

const form = useForm({
    id: props.record?.id,
    name: props.record?.name,
    label: props.record?.label,
    guard_name: props.record?.guard_name,
    permissions: props.record?.permissions,
});


let submit = () => {

if(!form.id) {
  form.post(route('roles.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('roles.update',{role: form.id}), {
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
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.roles.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.roles.page_update_description') }} {{ form?.name }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.roles.name') }}</label>
            <div class="mt-2">
              <input v-model="form.name" type="text" name="name" id="name" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6" placeholder="" />
            </div>
            <p v-if="form.errors.name" class="mt-2 text-xs text-red-600" id="name-error">{{ form.errors.name }}</p>
          </div>

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="label" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.roles.label') }}</label>
            <div class="mt-2">
              <input v-model="form.label" type="text" name="label" id="label" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6" placeholder="" />
            </div>
            <p v-if="form.errors.label" class="mt-2 text-xs text-red-600" id="label-error">{{ form.errors.label }}</p>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.permissions.length }} {{ $t('gestlab.general.labels.roles.items') }}
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.roles.items_tagline') }} {{ form.label }}</p>

        <div class="relative w-full text-gray-500 focus-within:text-blue-900">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
            <MagnifyingGlassIcon class="flex-shrink-0 h-5 w-5 animate-bounce" aria-hidden="true" />
          </div>
          <input v-model="permInput" name="mobile-search-field" id="mobile-search-field" class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:hidden" placeholder="Pesquisar por uma permissão" type="search" />
          <input v-model="permInput" name="desktop-search-field" id="desktop-search-field" class="hidden h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:block" placeholder="Pesquisar por uma permissão" type="search" />
        </div>

      </div>

      <!-- Checkboxes here -->
      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(permission, index) in results" :key="permission.value" class=""
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
        <fieldset>
          <legend class="sr-only">{{ $t('gestlab.general.labels.roles.permissions') }}</legend>
          <div class="space-y-5">
            <div class="relative flex items-start">
              <div class="flex h-6 items-center">
                <input :value="permission.item" v-model="form.permissions" :id="`permission-${index+1}`" :aria-describedby="`permission-${index+1}`" :name="`permission-${index+1}`" type="checkbox" class="h-4 w-4 rounded-full border-blue-900 text-blue-900 focus:ring-blue-900" />
              </div>
              <div class="ml-3 text-sm leading-6">
                <label :for="`permission-${index+1}`" class="font-medium text-gray-900">{{ permission.item.label }}</label>
                <p :id="`permission-${index+1}`" class="text-gray-500"></p>
              </div>
            </div>
            
          </div>
        </fieldset>

      </li>
      </ul>

    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button @click="submit" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.update') }}</button>
    </div>
  </form>

</template>