<template>
    <div class="inline-flex justify-center items-center">
      <div class="hidden mr-2 text-sm text-white lg:block bg-blue-900 rounded-full m-2 px-2">{{ pagination.total }} {{ $t('gestlab.pagination.records') }}</div>
  
      <div class="flex space-x-1 items-top" v-if="pagination.last_page > 1">
        <button
            :disabled="noPreviousPage"
            :class="{'opacity-50': noPreviousPage}"
            @click="loadPage(1)"
            class="inline-flex justify-center items-center w-11 h-11 text-gray-700 bg-white rounded border border-gray-200 shadow-sm outline-none hover:bg-gray-50 lg:h-9 lg:w-9 lg:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:h-3 lg:w-3" fill="none" viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
          </svg>
        </button>
        <button
            :disabled="noPreviousPage"
            :class="{'opacity-50': noPreviousPage}"
            @click="loadPage(pagination.current_page - 1)"
            class="inline-flex justify-center items-center w-11 h-11 text-gray-700 bg-white rounded border border-gray-200 shadow-sm outline-none hover:bg-gray-50 lg:h-9 lg:w-9 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:h-3 lg:w-3" fill="none" viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
  
        <div class="flex flex-col space-y-2 md:flex-row md:space-y-0 md:items-center md:space-x-1">
          <input type="number" @keydown.enter="loadPage(data.page)" v-model="data.page" class="px-2 w-11 h-11 text-center rounded border border-gray-400 shadow-sm lg:h-9 lg:w-9 lg:text-xs focus:ring-blue-500 focus:border-blue-500"/>
          <div class="px-2 text-gray-600 lg:text-sm">{{ $t('gestlab.pagination.of') }} {{ pagination.last_page }}</div>
        </div>
  
        <button
            :disabled="noNextPage"
            :class="{'opacity-50': noNextPage}"
            @click="loadPage(pagination.current_page + 1)"
            class="inline-flex justify-center items-center w-11 h-11 text-gray-700 bg-white rounded border border-gray-300 shadow-sm outline-none hover:bg-gray-50 lg:h-9 lg:w-9 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:h-3 lg:w-3" fill="none" viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
  
        <button
            :disabled="noNextPage"
            :class="{'opacity-50': noNextPage}"
            @click="loadPage(pagination.last_page)"
            class="inline-flex justify-center items-center w-11 h-11 text-gray-700 bg-white rounded border border-gray-300 shadow-sm outline-none hover:bg-gray-50 lg:h-9 lg:w-9 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:h-3 lg:w-3" fill="none" viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>
  </template>
  
  <script setup>
import { router } from '@inertiajs/vue3';
import { reactive, watch, computed } from 'vue';


  const props = defineProps({
    pagination: Object
  });

  const page = props.pagination.current_page;

  let data = reactive({
    page: props.pagination.current_page
  })


  let pageChange = watch(() => props.pagination.current_page, (first, second) => {
    data.page = props.pagination.current_page;
  });

  let loadPage = (page) => {
    router.get(router.page.url, {page: page}, {
        preserveScroll: false,
        preserveState: true,
        replace: true,
        onSuccess: () => Promise.all([
            
        ]),
        // onFinish: () => (enabling = false),
    })
  }

  let noPreviousPage = computed(() => {
      return props.pagination.current_page - 1 <= 0;
  });
  
  let noNextPage = computed(() => {
      return props.pagination.current_page + 1 > props.pagination.last_page;
  });

  </script>