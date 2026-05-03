<template>
    <div class="p-6 max-w-3xl mx-auto">
      <h1 class="text-2xl font-bold mb-4">Import Progress</h1>
      
      <!-- Progress Section -->
      <div v-if="progress < 100" class="mb-8">
        <div class="flex items-center gap-4">
          <progress 
            class="flex-1 h-4 bg-gray-200 rounded overflow-hidden" 
            :value="progress" 
            max="100"
          ></progress>
          <span class="text-gray-600">{{ progress }}%</span>
        </div>
        <p class="mt-2 text-sm text-gray-500">
          Processing {{ processedJobs }} of {{ totalJobs }} records...
        </p>
      </div>
  
      <!-- Completion State -->
      <div v-else class="bg-green-50 p-4 rounded-lg">
        <h2 class="text-green-700 font-semibold">
          ✅ Import Completed Successfully
        </h2>
        <p class="mt-2 text-green-600">
          {{ totalJobs }} records processed in {{ elapsedTime }} seconds
        </p>
      </div>
  
      <!-- Failed Jobs -->
      <div v-if="failedJobs.length" class="mt-8 bg-red-50 p-4 rounded-lg">
        <h3 class="text-red-600 font-semibold">
          ⚠️ {{ failedJobs.length }} Failed Jobs
        </h3>
        <ul class="mt-2 space-y-2">
          <li 
            v-for="jobId in failedJobs" 
            :key="jobId"
            class="text-sm text-red-500"
          >
            Job ID: {{ jobId }}
          </li>
        </ul>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, computed } from 'vue'
  import { router } from '@inertiajs/vue3'
  
  const props = defineProps({
    batchId: String
  })
  
  const progress = ref(0)
  const totalJobs = ref(0)
  const processedJobs = ref(0)
  const failedJobs = ref([])
  const startTime = ref(Date.now())
  const elapsedTime = computed(() => 
    Math.round((Date.now() - startTime.value) / 1000)
  )
  
  function fetchStatus() {
    router.get(`/import-status/${props.batchId}`, {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['progress', 'failedJobIds', 'totalJobs', 'processedJobs'],
      onSuccess: (page) => {
        progress.value = page.props.progress
        totalJobs.value = page.props.totalJobs
        processedJobs.value = page.props.processedJobs
        failedJobs.value = page.props.failedJobIds || []
        
        if (progress.value < 100) {
          setTimeout(fetchStatus, 1500)
        }
      }
    })
  }
  
  onMounted(fetchStatus)
  </script>
  