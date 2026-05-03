<template>
      <div class="bg-white shadow sm:rounded-lg mt-2 mb-2">
        <form @submit.prevent="submit" enctype="multipart/form-data">
          <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-start sm:justify-between">
              <div>
                <h3 class="text-base font-semibold text-gray-900">Carregar Dados de Equipamentos (CSV)</h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                  <!-- <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Recusandae voluptatibus corrupti atque repudiandae nam.</p> -->
                  <input
                    type="file"
                    ref="fileInput"
                    accept=".csv"
                    @change="onFileChange"
                    required
                  />

                <div v-if="errors.file" class="text-red-600 mt-1">{{ errors.file }}</div>

                </div>
              </div>
              <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center">
                <button type="submit" class="inline-flex items-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-800" :disabled="processing">Carregar & Importar</button>
              </div>
            </div>
          </div>
        </form>
       </div>

  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { useForm, router } from '@inertiajs/vue3'
  
  const form = useForm({
    file: null,
  })
  
  const errors = ref({})
  const processing = ref(false)
  
  function onFileChange(e) {
    form.file = e.target.files[0]
  }
  
  function submit() {
    processing.value = true
    errors.value = {}
  
    form.post('/equipments/import', {
      forceFormData: true,
      onSuccess: (page) => {
        processing.value = false
      },
      onError: (errs) => {
        processing.value = false
        errors.value = errs
      },
    })
  }
  </script>
  