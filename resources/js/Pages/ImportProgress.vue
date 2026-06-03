<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Estado da Importação de Dados</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

  <div :class="[progress < 100 ? 'p-2 mt-2 border border-gray-200 rounded-md' : '']">
      <h4 class="sr-only">Data Import Status</h4>

      <div class="mt-6" aria-hidden="true" v-if="progress < 100">
        <p class="text-sm font-bold text-gray-900 mb-2">Importação em Curso ... {{ progress }}%</p>

          <div class="overflow-hidden rounded-full bg-gray-200">
            <div class="h-2 rounded-full bg-blue-900" :style="{ width: progressBarWidth }" />
          </div>
          <div class="mt-6 hidden grid-cols-4 text-sm font-medium text-gray-600 sm:grid">
            <div class="text-blue-900 font-bold text-lg">25%</div>
            <div class="text-center font-bold text-lg text-blue-900">50%</div>
            <div class="text-center font-bold text-lg text-blue-900">75%</div>
            <div class="text-right font-bold text-lg text-blue-900">100%</div>
          </div>
        </div>

      <div class="mt-6" aria-hidden="true" v-else>
        <nav aria-label="Progress">
          <ol role="list" class="divide-y divide-gray-300 rounded-md border border-gray-300 md:flex md:divide-y-0">
            <li class="relative md:flex md:flex-1">
              <a href="#" class="group flex items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                  <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-800 group-hover:bg-blue-900">
                    <CheckIcon class="size-6 text-white" aria-hidden="true" />
                  </span>
                  <span class="ml-4 text-sm font-bold text-gray-900">Importação Concluída.</span>
                </span>
              </a>
              
              <a href="#" class="flex items-center px-6 py-4 text-sm font-medium" aria-current="step">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-red-500">
                  <span class="text-red-600 font-bold">{{ failedJobs.length }}</span>
                </span>
                <span class="ml-4 text-sm font-bold text-red-600">Tarefas com falha.</span>
              </a>
              
            </li>
          </ol>
        </nav>
      </div>


    </div>
</div>

  </template>
  
  <script setup>
  import { ref, onMounted, computed } from 'vue'
  import Layout from "@/Shared/Layouts/Layout.vue";
  import { router } from '@inertiajs/vue3'
  import { CheckIcon } from '@heroicons/vue/24/solid'
  import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

  defineOptions({
    layout: Layout
  });
  
  const props = defineProps({ batchId: String })
  const progress = ref(0)
  const failedJobs = ref([])

  const progressBarWidth = computed(() => {
    return `${progress.value}%`
  });

  function fetchStatus() {
    axios.get(`/import-status/${props.batchId}`)
      .then(response => {
        progress.value = response.data.progress
        failedJobs.value = response.data.failedJobIds || []
        if (progress.value < 100) {
          setTimeout(fetchStatus, 1500)
        }
      })
  }

  onMounted(fetchStatus)
  </script>
