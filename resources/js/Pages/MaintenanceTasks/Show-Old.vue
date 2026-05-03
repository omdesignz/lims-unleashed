<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { usePermission } from "@/Composables/usePermissions";
import { ArrowLongRightIcon, ArrowPathRoundedSquareIcon, CheckIcon, PencilIcon, ChevronDownIcon, TrashIcon } from "@heroicons/vue/24/outline";
import progressTracker from '@/Components/progress-tracker.vue';
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { onMounted } from 'vue';
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from 'vue';


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
});

defineOptions({
  layout: Layout
});

onMounted(() => {

    Echo.private(`orders.${usePage().props?.record?.data?.id}`)
      .listen('InventoryOrderUpdatedEvent', (e) => {
          console.log(e);

          orderStatus.value = e.order.status;

          // addToast(e.message, 'Test Event');
        })
      .listen('OrderDeliveredEvent', (e) => {
        console.log(e);

        // addToast(e.message, 'Test Event');
      })  
    });

</script>
<template>
<!-- <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.page_title') }}</h3>
</div> -->

<div>

    <div class="px-4 sm:px-0 flex justify-end items-center space-x-2">
      <button type="button" class="rounded-full bg-blue-900 p-2 text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
            <PencilIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button type="button" class="rounded-full bg-blue-900 p-2 text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
            <ArrowPathRoundedSquareIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button type="button" class="rounded-full bg-blue-900 p-2 text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
            <TrashIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <Listbox as="div" v-model="selectedStatus" @update:model-value="changeOrderStatus">
            <ListboxLabel class="sr-only">Change order status</ListboxLabel>
            <div class="relative">
            <div class="inline-flex divide-x divide-white rounded-md outline-none">
                <div class="inline-flex items-center gap-x-1.5 rounded-l-md bg-blue-900 px-3 py-1.5 text-white">
                <CheckIcon class="-ml-0.5 size-5" aria-hidden="true" />
                <p class="text-sm font-semibold">{{ selectedStatus.name }}</p>
                </div>
                <ListboxButton class="inline-flex items-center rounded-l-none rounded-r-md bg-blue-900 p-2 outline-none hover:bg-blue-900 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-900">
                <span class="sr-only">Change order status</span>
                <ChevronDownIcon class="size-5 text-white forced-colors:text-[Highlight]" aria-hidden="true" />
                </ListboxButton>
            </div>

            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <ListboxOptions class="absolute right-0 z-10 mt-2 w-72 origin-top-right divide-y divide-gray-200 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
                <ListboxOption as="template" v-for="option in props.steps" :key="option.name" :value="option" v-slot="{ active, selected }">
                    <li :class="[active ? 'bg-blue-900 text-white' : 'text-gray-900', 'cursor-default select-none p-4 text-sm']">
                    <div class="flex flex-col">
                        <div class="flex justify-between">
                        <p :class="selected ? 'font-semibold' : 'font-normal'">{{ option.name }}</p>
                        <span v-if="selected" :class="active ? 'text-white' : 'text-blue-900'">
                            <CheckIcon class="size-5" aria-hidden="true" />
                        </span>
                        </div>
                        <p :class="[active ? 'text-white' : 'text-gray-500', 'mt-2']">{{ option.description }}</p>
                    </div>
                    </li>
                </ListboxOption>
                </ListboxOptions>
            </transition>
            </div>
        </Listbox>

    </div>
    <!-- <progress-tracker :steps="stepsWithStatus" class="mt-2" /> -->
    <div class="bg-blue-900 rounded-md mt-2">
        <div class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-px sm:grid-cols-2 lg:grid-cols-3 rounded-lg overflow-hidden">

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.maintenance_tasks.is_planned') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.is_planned ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</span>
                <!-- <span class="text-sm text-white">unit</span> -->
            </p>
            </div>

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.maintenance_tasks.is_executed') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.is_executed ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</span>
                <!-- <span class="text-sm text-white">unit</span> -->
            </p>
            </div>

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.maintenance_tasks.cost') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.cost }}</span>
                <span class="text-sm text-white">AKZ</span>
            </p>
            </div>

        </div>
        </div>
    </div>

    <div class="mt-6 border-t border-gray-100">
      <dl class="divide-y divide-gray-100">
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.maintenance_task_no') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
            {{ props.record.data?.maintenance_task_no }}
          </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.equipment_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.equipment }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.name') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.name }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.description') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.description }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.category_id') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.category }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.periodicity') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.periodicity }} {{ $t('gestlab.general.labels.maintenance_tasks.periodicity_unit_options.' + props.record.data?.periodicity_unit) }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.executed_by_supplier') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.executed_by_supplier ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.executed_by_supplier">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.supplier_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.supplier }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.acceptance_criteria') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.acceptance_criteria }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.due_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.due_date }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.previous_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.previous_date }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.next_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.next_date }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.result') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.result }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.obs') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.obs }}</dd>
        </div>

      </dl>
    </div>
    
  </div>

</template>