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

            <button @click="showRequestForm = !showRequestForm" type="button" class="ml-auto flex items-center gap-x-1 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
              <PlusSmallIcon v-if="!showRequestForm" class="-ml-1.5 h-5 w-5" aria-hidden="true" />
              {{ showRequestForm ? 'Visualizar Solicitações' : 'Nova Solicitação' }}
            </button>
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
              <dt class="text-3xl font-medium leading-6 text-gray-500">Solicitações</dt>
              <!-- <dd class="text-xs font-medium">

              </dd> -->
              <!-- <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">{{ stat.value }}</dd> -->
            </div>
          </dl>
        </div>
  
        <div class="absolute left-0 top-full -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-ml-96 sm:-mt-10 sm:translate-y-0 sm:rotate-0 sm:transform-gpu sm:opacity-50" aria-hidden="true">
          <div class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-[#FF80B5] to-[#9089FC]" style="clip-path: polygon(100% 38.5%, 82.6% 100%, 60.2% 37.7%, 52.4% 32.1%, 47.5% 41.8%, 45.2% 65.6%, 27.5% 23.4%, 0.1% 35.3%, 17.9% 0%, 27.7% 23.4%, 76.2% 2.5%, 74.2% 56%, 100% 38.5%)" />
        </div>
      </div>
  
      <div class="space-y-16 py-16 xl:space-y-20">

        <form action="#" class="relative" v-if="showRequestForm">
    <div class="overflow-hidden rounded-lg border border-gray-300 max-w-2xl shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
      <label for="contact" class="sr-only">Contacto</label>
      <input type="number" v-model="request.contact" name="contact" id="contact" class="block w-full border-0 pt-2.5 text-lg font-medium placeholder:text-gray-400 focus:ring-0" placeholder="Contacto" />
      <label for="email" class="sr-only">Email</label>
      <input type="text" v-model="request.email" name="email" id="email" class="block w-full border-0 pt-2.5 text-lg font-medium placeholder:text-gray-400 focus:ring-0" placeholder="Email" />
      <label for="description" class="sr-only">Description</label>
      <textarea rows="2" v-model="request.description" name="description" id="description" class="block w-full resize-none border-0 py-0 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 mt-2" placeholder="Faça a sua solicitação..." />

      <!-- Spacer element to match the height of the toolbar -->
      <div aria-hidden="true">
        <div class="py-2">
          <div class="h-9" />
        </div>
        <div class="h-px" />
        <div class="py-2">
          <div class="py-px">
            <div class="h-9" />
          </div>
        </div>
      </div>
    </div>

    <div class="absolute inset-x-px bottom-0">
      <!-- Actions: These are just examples to demonstrate the concept, replace/wire these up however makes sense for your project. -->
      <div class="flex flex-nowrap justify-end space-x-2 px-2 py-2 sm:px-3 max-w-2xl">
        <Listbox as="div" class="flex-shrink-0" v-model="request.category_id">
          <ListboxLabel class="sr-only">Categoria</ListboxLabel>
          <div class="relative">
            <ListboxButton class="relative inline-flex items-center whitespace-nowrap rounded-full bg-gray-50 px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 sm:px-3">
              <UserCircleIcon v-if="request.category_id === null" class="h-5 w-5 flex-shrink-0 text-gray-300 sm:-ml-1" aria-hidden="true" />

              <span :class="[request.category_id === null ? '' : 'text-gray-900', 'hidden truncate sm:ml-2 sm:block']">{{ request.category_id === null ? 'Seleccione uma Categoria' : request.category_id.name }}</span>
            </ListboxButton>

            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
              <ListboxOptions class="absolute right-0 z-10 mt-1 max-h-56 w-52 overflow-auto rounded-lg bg-white py-3 text-base shadow ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                <ListboxOption as="template" v-for="category in props.request_categories" :key="category.id" :value="category" v-slot="{ active }">
                  <li :class="[active ? 'bg-gray-100' : 'bg-white', 'relative cursor-default select-none px-3 py-2']">
                    <div class="flex items-center">
                      <span class="ml-3 block truncate font-medium">{{ category.name }}</span>
                    </div>
                  </li>
                </ListboxOption>
              </ListboxOptions>
            </transition>
          </div>
        </Listbox>

        

        
      </div>
      <div class="flex items-center justify-between space-x-3 border-t border-gray-200 px-2 py-2 sm:px-3 max-w-2xl">
        <div class="flex">
          <button type="button" class="group -my-2 -ml-2 inline-flex items-center rounded-full px-3 py-2 text-left text-gray-400">
            <span class="text-sm italic text-gray-500 group-hover:text-gray-600"></span>
          </button>
        </div>
        <div class="flex-shrink-0">
          <button v-if="request.isDirty && request.category_id" @click="request.post(route('portal.requests'),{preserveState: false, preserveScroll: true, replace: true})" type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Submeter</button>
        </div>
      </div>
    </div>
  </form>


        <div v-if="!showRequestForm">

          <div class="mt-6 overflow-hidden border-t border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
            <h2 class="mx-auto max-w-2xl text-base font-semibold leading-6 text-gray-900 lg:mx-0 lg:max-w-none">Solicitações recentes</h2>
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

                <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
                    <li v-if="props.record.data.length" v-for="(record, recordIdx) in props.record.data" :key="record.id" class="overflow-hidden rounded-xl border border-gray-200">
                    <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-blue-900 p-6">
                        <div class="text-sm font-medium leading-6 text-white">{{ record?.category }}</div>
                        <Menu as="div" class="relative ml-auto">
                        <MenuButton class="-m-2.5 block p-2.5 text-white hover:text-gray-500">
                            <span class="sr-only">Open options</span>
                            <EllipsisHorizontalIcon class="h-5 w-5" aria-hidden="true" />
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="absolute right-0 z-10 mt-0.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                            <MenuItem v-slot="{ active }">
                                <Link :href="route('portal.request.markAsDone', {id: record?.id})" :class="[active ? 'bg-blue-900' : '', 'block px-3 py-1 text-sm leading-6 text-gray-900 hover:text-white']"
                                >Atendida<span class="sr-only">, {{ record?.category }}</span></Link
                                >
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <Link :href="route('portal.request.destroy', {id: record.id})" :class="[active ? 'bg-blue-900' : '', 'block px-3 py-1 text-sm leading-6 text-gray-900 hover:text-white']"
                                >Cancelar<span class="sr-only">, {{ record?.category }}</span></Link
                                >
                            </MenuItem>
                            </MenuItems>
                        </transition>
                        </Menu>
                    </div>
                    <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
                        <div class="flex justify-between gap-x-4 py-3">
                        <dt class="text-gray-500">Submissão</dt>
                        <dd class="text-gray-700">
                            <time>{{ record?.created_at }}</time>
                        </dd>
                        </div>
                        <div class="flex justify-between gap-x-4 py-3">
                        <dd class="">
                            <div class="font-medium text-gray-900">{{ record?.description }}</div>
                        </dd>
                        </div>
                    </dl>
                    </li>
                </ul>

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
import { Dialog, DialogPanel, Menu, MenuButton, MenuItem, MenuItems, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
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

    const request = useForm({
        email: '',
        contact: '',
        description: '',
        category_id: null
    });

 let showRequestForm = ref(false);

 const props = defineProps({
    record: Object,
    request_categories: Array,
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