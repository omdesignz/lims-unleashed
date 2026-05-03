<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div v-if="progress < 100">
        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">A importar... {{ progress }}%</p>
        <progress :value="progress" max="100" class="mt-3 h-2 w-full overflow-hidden rounded-full"></progress>
      </div>
      <div v-else>
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Importação concluída.</p>
      </div>
      <div v-if="failedJobs.length">
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">Algumas tarefas falharam: {{ failedJobs.length }}</p>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { router } from '@inertiajs/vue3'
  
  const props = defineProps({ batchId: String })
  const progress = ref(0)
  const failedJobs = ref([])
  
  function fetchStatus() {
    router.get(`/import-status/${props.batchId}`, {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['progress', 'failedJobIds', 'finished'],
      onSuccess: (page) => {
        progress.value = page.props.progress
        failedJobs.value = page.props.failedJobIds || []
        if (progress.value < 100) {
          setTimeout(fetchStatus, 2000)
        }
      }
    })
  }
  
  onMounted(fetchStatus)
  </script>
  
