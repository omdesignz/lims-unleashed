<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { usePermission } from "@/Composables/usePermissions";
import { ArrowPathRoundedSquareIcon, DocumentIcon, DocumentTextIcon, PencilIcon, TagIcon, TrashIcon, EnvelopeIcon, CheckBadgeIcon, DocumentMagnifyingGlassIcon, ChevronDownIcon, CheckIcon } from "@heroicons/vue/24/outline";
import sampleStatus from '@/Components/sample-status.vue';
import { router, useForm, usePage } from "@inertiajs/vue3";
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'

const page = usePage();

const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
});

defineOptions({
  layout: Layout
});

const verify = () => {
  router.get(route('qualitycertificates.getVerify', {id: props.record.data.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
      },
  });
}

const approve = () => {
  router.get(route('qualitycertificates.getApprove', {id: props.record.data.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
      },
  });
}

const viewPDF = () => {

    window.open(route('qualitycertificates.getPDF', {id: props.record.data.id}), '_blank');
}

</script>
<template>
<!-- <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-400">{{ $t('gestlab.general.labels.direct_collections.page_title') }}</h3>
</div> -->

<div>
    <div class="px-4 sm:px-0 flex justify-end items-center space-x-2 mt-2">
        
        <button v-if="!props.record.data?.approved_at" @click="approve" type="button" class="flex items-center text-sm dark:border dark:border-gray-400 rounded-md bg-orange dark:bg-gray-950/25 p-2 text-black dark:text-gray-400 shadow-xs hover:bg-orange-600 dark:hover:text-white cursor-pointer focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange dark:focus-visible:outline-gray-400">
          <CheckBadgeIcon class="h-4 w-4 mr-2" aria-hidden="true" /> Validar Certificado
        </button>

    </div>

    <div class="mt-6 border-t border-gray-100 dark:border-t-gray-800">
      <dl class="divide-y divide-gray-100 dark:divide-gray-800">
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">{{ $t('gestlab.general.labels.quality_certificates.code') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">{{ props.record.data?.certificate_no }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">{{ $t('gestlab.general.labels.quality_certificates.customer_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">{{ props.record.data?.customer }} - {{ props.record.data?.warehouse }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">{{ $t('gestlab.general.labels.quality_certificates.user_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-300">{{ props.record.data?.user?.name }}</dd>
        </div>
       
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-400">Documentos</dt>
          <dd class="mt-2 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
            <ul role="list" class="divide-y divide-gray-100 dark:divide-gray-800 rounded-md border border-gray-200">
             <li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm leading-6">
                <div class="flex w-0 flex-1 items-center">
                  <DocumentIcon class="h-5 w-5 shrink-0 text-gray-400" aria-hidden="true" />
                  <div class="ml-4 flex min-w-0 flex-1 gap-2">
                    <span class="truncate font-medium">Documento</span>
                    <span class="shrink-0 text-gray-400"></span>
                  </div>
                </div>
                <div class="ml-4 shrink-0">
                  <a target="_blank" :href="props.record.data?.links?.pdf_path" class="font-medium text-orange hover:text-blue-800">Visualizar</a>
                </div>
              </li>
              
            </ul>
          </dd>
        </div>

      </dl>
    </div>
  </div>

</template>