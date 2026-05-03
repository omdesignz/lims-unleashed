<template>
    
  
    <main>
      <div class="relative isolate overflow-hidden pt-16">
        <!-- Secondary navigation -->
        <header class="pb-4 pt-6 sm:pb-6">
          <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-6 px-4 sm:flex-nowrap sm:px-6 lg:px-8">
            <h1 class="text-base font-semibold leading-7 text-gray-900">{{ $page?.props?.auth?.user?.name }}</h1>
            <div class="order-last flex w-full gap-x-8 text-sm font-semibold leading-6 sm:order-none sm:w-auto sm:border-l sm:border-gray-200 sm:pl-6 sm:leading-7">
              {{ $page?.props?.auth?.user?.address }}
            </div>
            <!-- <a href="#" class="ml-auto flex items-center gap-x-1 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
              <PlusSmallIcon class="-ml-1.5 h-5 w-5" aria-hidden="true" />
              Nova Solicitação
            </a> -->
          </div>
        </header>
  
        <!-- Stats -->
        <div class="border-b border-b-gray-900/10 lg:border-t lg:border-t-gray-900/5"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <dl class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
            <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
              <dt class="text-3xl font-medium leading-6 text-gray-500">Recibos</dt>
              <!-- <dd :class="[stat.changeType === 'negative' ? 'text-rose-600' : 'text-gray-700', 'text-xs font-medium']">{{ stat.change }}</dd>
              <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">{{ stat.value }}</dd> -->
            </div>
          </dl>
        </div>
  
        <div class="absolute left-0 top-full -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-ml-96 sm:-mt-10 sm:translate-y-0 sm:rotate-0 sm:transform-gpu sm:opacity-50" aria-hidden="true">
          <div class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-[#FF80B5] to-[#9089FC]" style="clip-path: polygon(100% 38.5%, 82.6% 100%, 60.2% 37.7%, 52.4% 32.1%, 47.5% 41.8%, 45.2% 65.6%, 27.5% 23.4%, 0.1% 35.3%, 17.9% 0%, 27.7% 23.4%, 76.2% 2.5%, 74.2% 56%, 100% 38.5%)" />
        </div>
      </div>
  
      <div class="space-y-16 py-16 xl:space-y-20">
        <!-- Recent activity table -->
        <div>
          

          <div class="mt-6 overflow-hidden border-t border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
              <div class="mx-auto max-w-7xl">
            <h2 class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 lg:mx-0 lg:max-w-none">Recibos recentes</h2>
          </div>

          <div class="flex-1 flex">
            <form class="w-full flex md:ml-0" action="#" method="GET">
              <label for="mobile-search-field" class="sr-only">Pesquisar</label>
              <label for="desktop-search-field" class="sr-only">Pesquisar</label>
              <div class="relative w-full text-gray-500 focus-within:text-orange-800">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
                  <MagnifyingGlassMinusIcon class="flex-shrink-0 h-5 w-5 animate-bounce" aria-hidden="true" />
                </div>
                <input v-model="query.search" name="mobile-search-field" id="mobile-search-field" class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:hidden" placeholder="Pesquisar" type="search" />
                <input v-model="query.search" name="desktop-search-field" id="desktop-search-field" class="hidden h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:block" placeholder="Pesquisar" type="search" />
              </div>
            </form>
          </div>
              <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-none">
                <table class="w-full text-left" v-if="props.record.data.length">
                  <thead class="sr-only">
                    <tr>
                      <th v-for="field in props.fields">{{ field.name }}</th>
                      <!-- <th class="hidden sm:table-cell">Client</th> -->
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- <template> -->
                      <tr v-for="(record, recordIdx) in props.record.data" :key="record.id">
                        <td class="relative py-5 pr-6">
                          <div class="flex gap-x-6">
                            <div class="flex-auto">
                              <div class="flex items-start gap-x-3">
                                <div class="text-sm font-medium leading-6 text-gray-900">{{ record.date }}</div>
                                <!-- <div :class="[record.status, 'rounded-md py-1 px-2 text-xs font-bold bg-green-300', 'rounded-md py-1 px-2 text-xs font-bold bg-red-300']">{{ record.status ? 'PAGO' : 'NÃO PAGO' }}</div> -->
                              </div>
                              <div class="mt-1 text-xs leading-5 text-gray-500"></div>
                            </div>
                          </div>
                          <div class="absolute bottom-0 right-full h-px w-screen bg-gray-100" />
                          <div class="absolute bottom-0 left-0 h-px w-screen bg-gray-100" />
                        </td>
                        <td class="relative py-5 pr-6">
                          <div class="flex gap-x-6">
                            <div class="flex-auto">
                              <div class="flex items-start gap-x-3">
                                <div class="text-sm font-medium leading-6 text-gray-900">{{ record.rec_no }}</div>
                                <!-- <div class="rounded-md py-1 px-2 text-xs font-bold bg-green-300"></div> -->
                              </div>
                              <div class="mt-1 text-xs leading-5 font-bold text-gray-500">Total: {{ record.total }}</div>
                            </div>
                          </div>
                          <div class="absolute bottom-0 right-full h-px w-screen bg-gray-100" />
                          <div class="absolute bottom-0 left-0 h-px w-screen bg-gray-100" />
                        </td>
                        <!-- <td class="hidden py-5 pr-6 sm:table-cell">
                          <div class="text-sm leading-6 text-gray-900">{{ transaction.client }}</div>
                          <div class="mt-1 text-xs leading-5 text-gray-500">{{ transaction.description }}</div>
                        </td> -->
                        <td class="py-5 text-right">
                          <div class="flex justify-end">
                            <a target="_blank" :href="route('portal.receipts.getReceiptPDF', {id: record.id})" class="text-sm font-medium leading-6 text-blue-600 hover:text-blue-500"
                              >Visualizar<span class="hidden sm:inline"> recibo</span><span class="sr-only">, recibo #{{ record.rec_no }}</span></a
                            >
                          </div>
                          <div class="mt-1 text-xs leading-5 text-gray-500">
                            Recibo <span class="text-gray-900">#{{ record.rec_no }}</span>
                          </div>
                        </td>
                      </tr>
                    <!-- </template> -->
                  </tbody>
                </table>
                <Pagination v-if="props.record.data.length" :links="props.record.meta.links" :from="props.record.meta.from" :to="props.record.meta.to" :total="props.record.meta.total" :current_page="props.record.meta.current_page" :last_page="props.record.meta.last_page" class="mt-2" />

              </div>
            </div>
          </div>
        </div>
  
      </div>
    </main>
  </template>
  
  <script setup>
import debounce from 'lodash/debounce'
import { ref, watch, reactive } from 'vue'
import { Dialog, DialogPanel, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
  ArrowDownCircleIcon,
  ArrowPathIcon,
  ArrowUpCircleIcon,
  Bars3Icon,
  EllipsisHorizontalIcon,
  PlusSmallIcon,
} from '@heroicons/vue/20/solid'
  import { BellIcon, MagnifyingGlassMinusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
  import Layout from "@/Shared/Layouts/PortalLayout.vue";
  import {useForm, router} from "@inertiajs/vue3";
  import Pagination from '@/Components/pagination.vue'



  defineOptions({
    layout: Layout
    });
  
 const props = defineProps({
    record: Object,
    fields: Array,
    query: Object,
  });

  const query = reactive({
    search: props.query?.search,
    filter: props.query?.filter,
    page: null
  });

watch(query, debounce( function(value) {
  router.get(router.page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300));
 
  const stats = [
    { name: 'Revenue', value: '$405,091.00', change: '+4.75%', changeType: 'positive' },
    { name: 'Overdue invoices', value: '$12,787.00', change: '+54.02%', changeType: 'negative' },
    { name: 'Outstanding invoices', value: '$245,988.00', change: '-1.39%', changeType: 'positive' },
    { name: 'Expenses', value: '$30,156.00', change: '+10.18%', changeType: 'negative' },
  ]
  const statuses = {
    Paid: 'text-green-700 bg-green-50 ring-green-600/20',
    Withdraw: 'text-gray-600 bg-gray-50 ring-gray-500/10',
    Overdue: 'text-red-700 bg-red-50 ring-red-600/10',
  }
  const days = [
    {
      date: 'Today',
      dateTime: '2023-03-22',
      transactions: [
        {
          id: 1,
          invoiceNumber: '00012',
          href: '#',
          amount: '$7,600.00 USD',
          tax: '$500.00',
          status: 'Paid',
          client: 'Reform',
          description: 'Website redesign',
          icon: ArrowUpCircleIcon,
        },
        {
          id: 2,
          invoiceNumber: '00011',
          href: '#',
          amount: '$10,000.00 USD',
          status: 'Withdraw',
          client: 'Tom Cook',
          description: 'Salary',
          icon: ArrowDownCircleIcon,
        },
        {
          id: 3,
          invoiceNumber: '00009',
          href: '#',
          amount: '$2,000.00 USD',
          tax: '$130.00',
          status: 'Overdue',
          client: 'Tuple',
          description: 'Logo design',
          icon: ArrowPathIcon,
        },
      ],
    },
    {
      date: 'Yesterday',
      dateTime: '2023-03-21',
      transactions: [
        {
          id: 4,
          invoiceNumber: '00010',
          href: '#',
          amount: '$14,000.00 USD',
          tax: '$900.00',
          status: 'Paid',
          client: 'SavvyCal',
          description: 'Website redesign',
          icon: ArrowUpCircleIcon,
        },
      ],
    },
  ]
  
 
  </script>