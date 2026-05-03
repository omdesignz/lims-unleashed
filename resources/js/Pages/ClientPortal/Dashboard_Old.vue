<template>
    
  
    <main>
      <div class="space-y-2 py-2 xl:space-y-2">
        <div>
          
          <swiper-container :slides-per-view="1" :speed="500" :loop="true" :navigation="false" :autoplay="true" :pagination="false" effect="flip">
          <swiper-slide>
            <div class="overflow-hidden">
              <!-- Hero Content -->
              <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-16 text-center lg:text-left container xl:max-w-7xl mx-auto px-4 py-16 lg:px-8 lg:py-32">
                <div class="lg:flex lg:items-center">
                  <div>
                    <div class="text-sm rounded text-emerald-800 bg-emerald-100 border border-emerald-200 font-medium inline-flex px-2 py-1 leading-4 mb-2 dark:text-emerald-200 dark:bg-emerald-900/50 dark:border-emerald-800/50">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.first.caption') }}
                    </div>
                    <h1 class="text-4xl font-black text-black mb-4 dark:text-white">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.first.title') }}
                    </h1>
                    <h2 class="text-xl leading-relaxed font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.first.description') }}
                    </h2>
                    
                  </div>
                </div>
                <div class="lg:flex lg:justify-center lg:items-center">
                  <div class="lg:w-96 relative">
                    <div class="absolute top-0 right-0 w-40 h-40 lg:w-96 lg:h-96 bg-tranparent border border-teal-200 rounded-full -m-6 dark:border-teal-900"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 lg:w-96 lg:h-96 bg-tranparent border border-indigo-200 rounded-full -m-6 dark:border-indigo-900"></div>
                    <div class="absolute rounded-full bg-gradient-to-br from-purple-200 top-0 left-0 w-36 h-36 -mt-10 -ml-10 dark:from-purple-800"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-tl from-amber-200 bottom-0 right-0 w-24 h-24 -mb-8 -mr-8 rotate-12 dark:from-orange-900"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-bl from-teal-200 top-0 right-0 w-32 h-40 -mt-10 -mr-8 -rotate-6 dark:from-teal-800"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-tr from-indigo-200 bottom-0 left-0 w-32 h-40 -mb-8 -ml-8 rotate-12 dark:from-indigo-800"></div>
                    <img src="../../../images/1.jpg" class="relative rounded-lg mx-auto shadow-lg" alt="Hero Image">
                  </div>
                </div>
              </div>
              <!-- END Hero Content -->
            </div>
          </swiper-slide>
          <swiper-slide>
            <div class="overflow-hidden">
              <!-- Hero Content -->
              <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-16 text-center lg:text-left container xl:max-w-7xl mx-auto px-4 py-16 lg:px-8 lg:py-32">
                <div class="lg:flex lg:items-center">
                  <div>
                    <div class="text-sm rounded text-blue-800 bg-blue-100 border border-blue-200 font-medium inline-flex px-2 py-1 leading-4 mb-2 dark:text-blue-200 dark:bg-blue-900/50 dark:border-blue-800/50">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.second.caption') }}
                    </div>
                    <h1 class="text-4xl font-black text-black mb-4 dark:text-white">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.second.title') }}
                    </h1>
                    <h2 class="text-xl leading-relaxed font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.second.description') }}
                    </h2>
                    
                  </div>
                </div>
                <div class="lg:flex lg:justify-center lg:items-center">
                  <div class="lg:w-96 relative">
                    <div class="absolute top-0 right-0 w-40 h-40 lg:w-96 lg:h-96 bg-tranparent border border-teal-200 rounded-full -m-6 dark:border-teal-900"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 lg:w-96 lg:h-96 bg-tranparent border border-indigo-200 rounded-full -m-6 dark:border-indigo-900"></div>
                    <div class="absolute rounded-full bg-gradient-to-br from-purple-200 top-0 left-0 w-36 h-36 -mt-10 -ml-10 dark:from-purple-800"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-tl from-amber-200 bottom-0 right-0 w-24 h-24 -mb-8 -mr-8 rotate-12 dark:from-orange-900"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-bl from-teal-200 top-0 right-0 w-32 h-40 -mt-10 -mr-8 -rotate-6 dark:from-teal-800"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-tr from-indigo-200 bottom-0 left-0 w-32 h-40 -mb-8 -ml-8 rotate-12 dark:from-indigo-800"></div>
                    <img src="../../../images/2.jpg" class="relative rounded-lg mx-auto shadow-lg" alt="Hero Image">
                  </div>
                </div>
              </div>
              <!-- END Hero Content -->
            </div>
          </swiper-slide>
          <swiper-slide>
            <div class="overflow-hidden">
              <!-- Hero Content -->
              <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-16 text-center lg:text-left container xl:max-w-7xl mx-auto px-4 py-16 lg:px-8 lg:py-32">
                <div class="lg:flex lg:items-center">
                  <div>
                    <div class="text-sm rounded text-red-800 bg-red-100 border border-red-200 font-medium inline-flex px-2 py-1 leading-4 mb-2 dark:text-red-200 dark:bg-red-900/50 dark:border-red-800/50">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.third.caption') }}
                    </div>
                    <h1 class="text-4xl font-black text-black mb-4 dark:text-white">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.third.title') }}
                    </h1>
                    <h2 class="text-xl leading-relaxed font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('gestlab.pages.portal_dashboard.slider.slides.third.description') }}
                    </h2>
                    
                  </div>
                </div>
                <div class="lg:flex lg:justify-center lg:items-center">
                  <div class="lg:w-96 relative">
                    <div class="absolute top-0 right-0 w-40 h-40 lg:w-96 lg:h-96 bg-tranparent border border-teal-200 rounded-full -m-6 dark:border-teal-900"></div>
                    <div class="absolute bottom-0 left-0 w-40 h-40 lg:w-96 lg:h-96 bg-tranparent border border-indigo-200 rounded-full -m-6 dark:border-indigo-900"></div>
                    <div class="absolute rounded-full bg-gradient-to-br from-purple-200 top-0 left-0 w-36 h-36 -mt-10 -ml-10 dark:from-purple-800"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-tl from-amber-200 bottom-0 right-0 w-24 h-24 -mb-8 -mr-8 rotate-12 dark:from-orange-900"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-bl from-teal-200 top-0 right-0 w-32 h-40 -mt-10 -mr-8 -rotate-6 dark:from-teal-800"></div>
                    <div class="absolute rounded-3xl bg-gradient-to-tr from-indigo-200 bottom-0 left-0 w-32 h-40 -mb-8 -ml-8 rotate-12 dark:from-indigo-800"></div>
                    <img src="../../../images/3.jpg" class="relative rounded-lg mx-auto shadow-lg" alt="Hero Image">
                  </div>
                </div>
              </div>
              <!-- END Hero Content -->
            </div>
          </swiper-slide>
        </swiper-container>
          
        </div>
  
      </div>

      <div class="relative isolate overflow-hidden pt-2">
        <!-- Secondary navigation -->
        <header class="pb-4 pt-2 sm:pb-6">
          <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-6 px-4 sm:flex-nowrap sm:px-6 lg:px-8">
            <h1 class="text-base font-semibold leading-7 text-gray-900">{{ auth?.user?.name }}</h1>
            <div class="order-last flex w-full gap-x-8 text-sm font-semibold leading-6 sm:order-none sm:w-auto sm:border-l sm:border-gray-200 sm:pl-6 sm:leading-7">
              {{ auth?.user?.address }}
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
            <div v-for="(stat, statIdx) in stats" :key="stat.name" :class="[statIdx % 2 === 1 ? 'sm:border-l' : statIdx === 2 ? 'lg:border-l' : '', 'flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8']">
              <dt class="text-sm font-medium leading-6 text-gray-500">{{ $t(stat.name) }}</dt>
              <dd :class="[stat.changeType === 'negative' ? 'text-rose-600' : 'text-gray-700', 'text-xs font-medium']">{{ stat.change }}</dd>
              <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">{{ stat.value }}</dd>
            </div>
          </dl>
        </div>
  
        <div class="absolute left-0 top-full -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-ml-96 sm:-mt-10 sm:translate-y-0 sm:rotate-0 sm:transform-gpu sm:opacity-50" aria-hidden="true">
          <div class="aspect-[1154/678] w-[72.125rem] bg-gradient-to-br from-[#FF80B5] to-[#9089FC]" style="clip-path: polygon(100% 38.5%, 82.6% 100%, 60.2% 37.7%, 52.4% 32.1%, 47.5% 41.8%, 45.2% 65.6%, 27.5% 23.4%, 0.1% 35.3%, 17.9% 0%, 27.7% 23.4%, 76.2% 2.5%, 74.2% 56%, 100% 38.5%)" />
        </div>
      </div>
  
      
    </main>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { Dialog, DialogPanel, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
  import {
    ArrowDownCircleIcon,
    ArrowPathIcon,
    ArrowUpCircleIcon,
    Bars3Icon,
    EllipsisHorizontalIcon,
    PlusSmallIcon,
  } from '@heroicons/vue/20/solid'
  import { BellIcon, XMarkIcon } from '@heroicons/vue/24/outline'
  import Layout from "@/Shared/Layouts/PortalLayout.vue";
  import { usePage } from '@inertiajs/vue3';

  defineOptions({
    layout: Layout
    });
  
  defineProps({
    auth: Object,
    stats: Object
  });
 
  const stats = [
    { name: 'gestlab.pages.portal_dashboard.stats.debt', value: usePage().props.stats.overdue, change: '', changeType: 'positive' },
    { name: 'gestlab.pages.portal_dashboard.stats.invoices', value: usePage().props.stats.invoices, change: '', changeType: 'positive' },
    { name: 'gestlab.pages.portal_dashboard.stats.quality_certificates', value: usePage().props.stats.qualitycertificates, change: '', changeType: 'negative' },
    { name: 'gestlab.pages.portal_dashboard.stats.contract_guides', value: usePage().props.stats.contractguides, change: '', changeType: 'positive' },
    { name: 'gestlab.pages.portal_dashboard.stats.collections', value: usePage().props.stats.collections, change: '', changeType: 'negative' },
    { name: 'gestlab.pages.portal_dashboard.stats.credit_notes', value: usePage().props.stats.creditnotes, change: '', changeType: 'negative' },
    { name: 'gestlab.pages.portal_dashboard.stats.receipts', value: usePage().props.stats.receipts, change: '', changeType: 'negative' },
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