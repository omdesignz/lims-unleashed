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
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.page_title') }}</h3>
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
                <p class="text-sm font-semibold">{{ selectedStatus?.name }}</p>
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
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.occurrences.is_planned') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.is_planned ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</span>
                <!-- <span class="text-sm text-white">unit</span> -->
            </p>
            </div>

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.occurrences.is_executed') }}</p>
            <p class="mt-2 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ props.record.data?.is_executed ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</span>
                <!-- <span class="text-sm text-white">unit</span> -->
            </p>
            </div>

            <div class="bg-blue-900 px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-sm font-medium leading-6 text-white">{{ $t('gestlab.general.labels.occurrences.cost') }}</p>
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
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.date_reported') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
            {{ props.record.data?.date_reported }}
          </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.origin_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.origin }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.issue_description') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.issue_description }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.department_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.department }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.notification_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.notification_date }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id?.value === 3">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.client_process_open_notification_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.client_process_open_notification_date }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.analysis') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.analysis }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.has_risk_correction_budget') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.has_risk_correction_budget ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="!props.record.data?.has_risk_correction_budget">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.reason_for_no_risk_correction_budget }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.has_non_conformity_terms') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.has_non_conformity_terms ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
        </div>
        <!-- <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.corrective_action') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.corrective_action }}</dd>
        </div> -->
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.date_resolved') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.date_resolved }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.effect_corrective_actions') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.effect_corrective_actions }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.cause_corrective_actions') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.cause_corrective_actions }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.implementation_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0" :class="props.record.data?.implementation_date_overdue ? 'text-red-600' : ''">{{ props.record.data?.implementation_date }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.user_id') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.user }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.responsible_name') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.responsible_name }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.update_risk_matrix') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.update_risk_matrix ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id?.value === 3">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.client_process_close_notification_date') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.client_process_close_notification_date }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="props.record.data?.category_id?.value === 3">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.client_acceptance') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.client_acceptance }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.client_acceptance_comments') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.client_acceptance_comments }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.date_closed') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.date_closed }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.was_effective') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.was_effective ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
        </div>

        <!-- <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.occurrences.obs') }}</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.obs }}</dd>
        </div> -->

      </dl>
    </div>
    
  </div>

</template>