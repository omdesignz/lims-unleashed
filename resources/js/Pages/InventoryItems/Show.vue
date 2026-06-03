<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { usePermission } from "@/Composables/usePermissions";
import { ArrowLongRightIcon, ArrowPathRoundedSquareIcon, CheckIcon, PencilIcon, ChevronDownIcon, TrashIcon, EyeIcon } from "@heroicons/vue/24/outline";
import progressTracker from '@/Components/progress-tracker.vue';
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { onMounted } from 'vue';
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from 'vue';
import Pagination from "@/Components/pagination.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
    maintenanceTasks: Object
});

defineOptions({
  layout: Layout
});


onMounted(() => {

   
});

</script>
<template>
<!-- <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.page_title') }}</h3>
</div> -->

<div :class="commercialDocumentThemeClasses">
    
    <div class="bg-blue-900 rounded-md mt-2">
        <div class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-px sm:grid-cols-2 lg:grid-cols-3 rounded-lg overflow-hidden">

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.iitems.status_id') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.status }}</span>
                <!-- <span class="text-sm text-white">unit</span> -->
            </p>
            </div>

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.iitems.department_id') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.department }}</span>
                <!-- <span class="text-sm text-white">unit</span> -->
            </p>
            </div>

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.iitems.no_of_interventions') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.maintenanceTasks.data.length }}</span>
            </p>
            </div>

        </div>
        </div>
    </div>

    <div class="mt-6 border-t border-gray-100">
      <dl class="divide-y divide-gray-100">
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.internal_code') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
            {{ props.record.data?.internal_code }}
          </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.category_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.category }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id === 1">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.eq_cat_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.eq_cat }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.name') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.name }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.description') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.description }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.type_id') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.type }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.status_id') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.status }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.supplier_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.supplier }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id === 1">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.serial_number') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.serial_number }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id === 1">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.software') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.software }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id === 1">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.firmware') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.firmware }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.location') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.location }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.department_id') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.department }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.range') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.range }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id !== 1">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.lot') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.lot }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.acceptance_criteria') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.acceptance_criteria }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iitems.obs') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.obs }}</dd>
        </div>

      </dl>
    </div>

    <hr>

    <div class="border-b border-gray-200 pb-5 py-8">
        <h3 class="text-base font-semibold text-gray-900">Intervenções</h3>
        <p class="mt-2 max-w-4xl text-sm text-gray-500">Segue abaixo uma lista de intervenções realizadas ao longo do tempo.</p>
    </div>

    <ul role="list" class="divide-y divide-gray-100" v-if="props.maintenanceTasks.data.length">
    <li v-for="task in props.maintenanceTasks.data" :key="task.id" class="flex justify-between gap-x-6 py-5">
      <div class="flex min-w-0 gap-x-4">
        <!-- <img class="size-12 flex-none rounded-full bg-gray-50" :src="person.imageUrl" alt="" /> -->
        <div class="min-w-0 flex-auto">
          <p class="text-sm/6 font-semibold text-gray-900">{{ task.maintenance_task_no }}</p>
          <p class="mt-1 truncate text-xs/5 text-gray-500">{{ task.category }}</p>
        </div>
      </div>
      <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
         <Link :href="route('maintenancetasks.show', {maintenancetask: task.id})" class="text-xs/5 text-gray-500">
            <EyeIcon class="h-4 w-4" aria-hidden="true" />
        </Link>
        <div v-if="task.category_id === 4 || task.category_id === 5" class="mt-1 flex items-center gap-x-1.5">
          <div class="flex-none rounded-full bg-emerald-500/20 p-1">
            <div class="size-1.5 rounded-full bg-emerald-500" />
          </div>
          <p class="text-xs/5 text-gray-500">{{ task.calibration_status }}</p>
        </div>
        <p v-else class="mt-1 text-xs/5 text-gray-500">
          
        </p>
        
      </div>
    </li>
  </ul>
  <Pagination
    v-if="props.maintenanceTasks.data.length"
    :links="props.maintenanceTasks.meta.links"
    :from="props.maintenanceTasks.meta.from"
    :to="props.maintenanceTasks.meta.to"
    :total="props.maintenanceTasks.meta.total"
    :current_page="props.maintenanceTasks.meta.current_page"
    :last_page="props.maintenanceTasks.meta.last_page"
    class="mt-2"
  />
    
  </div>

</template>
