<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { usePermission } from "@/Composables/usePermissions";
import { ArrowLongRightIcon, ArrowPathRoundedSquareIcon, CheckIcon, PencilIcon, ChevronDownIcon, TrashIcon } from "@heroicons/vue/24/outline";
import progressTracker from '@/Components/progress-tracker.vue';
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { onMounted } from 'vue';
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from 'vue';
import { getEcho } from '@/lib/echo';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
    steps: {
        type: Array,
        default: () => []
    }
});

defineOptions({
  layout: Layout
});

onMounted(() => {
    const echo = getEcho()

    if (!echo) {
        return
    }

    echo.private(`orders.${usePage().props?.record?.data?.id}`)
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

    // Define steps
    const steps = props.steps;

    // Current order status
    const orderStatus = ref(props?.record?.data.status) // Change this value as the order progresses

    const selectedStatus = ref(steps.find(step => step.statusKey === orderStatus.value));

    const stepsWithStatus = computed(() => {
    const currentIndex = steps.findIndex((s) => s.statusKey === orderStatus.value);

        return steps.map((step, index) => {
            if (index === currentIndex) {
                // Current step matched with orderStatus
                return { ...step, status: 'complete' }; // Mark the matched step as complete
            } else if (index === currentIndex + 1) {
                // If the step is immediately after the current step, mark it as 'current'
                return { ...step, status: 'current' };
            } else if (index < currentIndex) {
                // Mark steps before the current index as complete
                return { ...step, status: 'complete' };
            } else {
                // Mark all steps after the "current" as upcoming
                return { ...step, status: 'upcoming' };
            }
        });
    });

    const changeOrderStatus = (status) => {
        console.log(status.statusKey);

        router.get(route('iorders.changeOrderStatus', { iorder: props?.record?.data?.id, status: status.statusKey }), {}, {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                console.log('reloaded successfully');
            },
        });
    };

</script>
<template>
<!-- <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.page_title') }}</h3>
</div> -->

<div :class="commercialDocumentThemeClasses">

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
    <progress-tracker :steps="stepsWithStatus" class="mt-2" />
    <div class="mt-6 border-t border-gray-100">
      <dl class="divide-y divide-gray-100">
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.reference') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
            {{ props.record.data?.reference }}
          </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.date') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.date }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.supplier_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.supplier }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.user_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.user }}</dd>
        </div>

        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.iorders.items') }}</dt>
          <dd class="mt-2 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
            <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
             <li v-for="item in props.record.data?.items" class="flex items-center justify-between py-4 pl-4 pr-5 text-sm leading-6">
                <div class="flex w-0 flex-1 items-center">
                  <ArrowLongRightIcon class="h-5 w-5 flex-shrink-0 text-gray-400" aria-hidden="true" />
                  <div class="ml-4 flex min-w-0 flex-1 gap-2">
                    <span class="truncate font-medium">{{ item.item }}</span>
                    <span class="flex-shrink-0 text-gray-400">{{ item.qty }}</span>
                  </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <a target="_blank" :href="props.record.data?.links?.pdf_path" class="font-medium text-blue-900 hover:text-blue-800">{{ item.warehouse }}</a>
                </div>
              </li>
            </ul>
          </dd>
        </div>

      </dl>
    </div>
    
</div>

</template>
