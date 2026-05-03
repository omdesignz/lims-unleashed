<template>
      <div class="bg-white shadow sm:rounded-lg mt-2 mb-2">
        <form @submit.prevent="exportItems">
          <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-start sm:justify-between">
              <div>
                <h3 class="text-base font-semibold text-gray-900">Exportar Itens (Excel)</h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">

                  <div class="mt-2 flex items-center justify-start">
                    <p class="text-sm text-gray-500">Período:</p>
                    <date-picker
                        v-model.range.string="exportParams.range"
                        locale="pt-PT"
                        color="blue"
                        mode="date"
                        range
                        :input-debounce="500"
                        @update:model-value=""
                        :masks="masks"
                      />
                  </div>

                </div>
              </div>
              <div class="mt-5 sm:ml-6 sm:mt-0 sm:flex sm:shrink-0 sm:items-center">
                <button type="submit" class="inline-flex items-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-800">Exportar</button>
              </div>
            </div>
          </div>
        </form>
       </div>

  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { useForm, router } from '@inertiajs/vue3'
  import datePicker from "@/Components/date-picker.vue";
  
  const masks = ref({
    modelValue: "YYYY-MM-DD",
    data: "YYYY-MM-DD",
  });

  const exportParams = ref({
    range: {
      start: null,
      end: null,
    },
  });

  const exportItems = () => {
    window.open(route('iequipments.export', {
      start: exportParams.value.range.start,
      end: exportParams.value.range.end,
    }), '_blank');
  }
  
  
  </script>
  