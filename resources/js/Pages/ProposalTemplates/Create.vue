<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import fancyTextarea from '@/Components/fancy-textarea.vue';


defineOptions({
  layout: Layout
});

const props = defineProps({
    
});

const form = useForm({
    name: '',
    content: '',
});


let submit = () => {

if(!form.id) {
  form.post(route('proposaltemplates.store'), {
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('proposaltemplates.update',{template: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
}

}

const insertPlaceholder = (placeholder) => {
      form.content += placeholder;
    };

</script>

<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_templates.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">A registrar o modelo de proposta: {{ form.name }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_templates.name') }}</label>
            <div class="mt-2">
                <input v-model="form.name" type="text" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                <p v-if="form.errors.name" class="mt-2 text-xs text-red-600" id="name-error">{{ form.errors.name }}</p>
            </div>
          </div>

        </div>

        <div class="sm:col-span-full">
          <label for="content" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_templates.content') }}</label>
          <div class="mt-2">
            <fancyTextarea v-model="form.content" />
            <p v-if="form.errors.content" class="mt-2 text-xs text-red-600" id="content-error">{{ form.errors.content }}</p>

          </div>
        </div>

      </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button @click.prevent="insertPlaceholder('{{customer_id}}')" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.labels.proposals.total') }}</button>
      <button @click.prevent="insertPlaceholder('{{warehouse_id}}')" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.labels.proposals.warehouse_id') }}</button>
      <button @click.prevent="insertPlaceholder('{{total}}')" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.labels.proposals.customer_id') }}</button>
      <button @click.prevent="insertPlaceholder('{{proposal_no}}')" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.labels.proposals.proposal_no') }}</button>
      <button @click="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button>
    </div>

  </form>

</template>