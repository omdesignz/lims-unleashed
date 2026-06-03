<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white" :class="commercialDocumentThemeClasses">
    <!-- Header Section -->
    <div class="border-b border-gray-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb and Stats -->
        <div class="py-6">
          <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
              <li class="inline-flex items-center">
                <Link :href="route('portal.home')" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-900">
                  <HomeModernIcon class="h-4 w-4 mr-2" />
                  {{ $t('gestlab.portal_menu.dashboard') }}
                </Link>
              </li>
              <li aria-current="page">
                <div class="flex items-center">
                  <ChevronRightIcon class="h-4 w-4 text-gray-400 mx-2" />
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_contract_guides.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_contract_guides.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_contract_guides.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_contract_guides.guides_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <ClipboardDocumentCheckIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_contract_guides.guides') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"
           v-motion
           :initial="{ opacity: 0, y: 20 }"
           :enter="{ opacity: 1, y: 0 }"
      >
        <!-- Active Guides -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_contract_guides.stats.active') }}</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ activeGuidesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <CheckCircleIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.stats.active_description') }}</p>
          </div>
        </div>

        <!-- Recent Guides -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_contract_guides.stats.recent') }}</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ recentGuidesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <ClockIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.stats.recent_description') }}</p>
          </div>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_contract_guides.stats.expiring') }}</p>
              <p class="mt-2 text-3xl font-bold text-yellow-600">{{ expiringSoonCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
              <ExclamationTriangleIcon class="h-6 w-6 text-yellow-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.stats.expiring_description') }}</p>
          </div>
        </div>
      </div>

      <!-- Search and Filter Bar -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex-1">
            <div class="relative max-w-md">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="query.search"
                type="search"
                :placeholder="$t('gestlab.pages.portal_contract_guides.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Status Filter -->
            <Menu as="div" class="relative">
              <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                <FunnelIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_contract_guides.filter') }}
                <ChevronDownIcon class="h-4 w-4" />
              </MenuButton>
              <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2 z-10">
                  <div class="px-4 py-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.pages.portal_contract_guides.filter_by_status') }}
                    </p>
                  </div>
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = ''"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          !query.status_filter ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_contract_guides.all_guides') }}
                        <span v-if="!query.status_filter" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = 'active'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.status_filter === 'active' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_contract_guides.active') }}
                        <span v-if="query.status_filter === 'active'" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = 'completed'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.status_filter === 'completed' ? 'font-semibold text-green-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_contract_guides.completed') }}
                        <span v-if="query.status_filter === 'completed'" class="h-2 w-2 rounded-full bg-green-600"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = 'expiring'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.status_filter === 'expiring' ? 'font-semibold text-yellow-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_contract_guides.expiring_soon') }}
                        <span v-if="query.status_filter === 'expiring'" class="h-2 w-2 rounded-full bg-yellow-600"></span>
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- New Guide Button -->
            <button
              @click="requestNewGuide"
              class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_contract_guides.new_guide') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Guides Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5" />
            {{ $t('gestlab.pages.portal_contract_guides.recent_guides') }}
          </h2>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_contract_guides.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_contract_guides.empty_state.description') }}
          </p>
          <button
            @click="requestNewGuide"
            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-4 w-4" />
            {{ $t('gestlab.pages.portal_contract_guides.request_first') }}
          </button>
        </div>

        <!-- Guides List -->
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="(guide, index) in props.record.data" 
            :key="guide.id"
            class="group hover:bg-gray-50 transition-colors duration-200"
            v-motion
            :initial="{ opacity: 0, x: -20 }"
            :enter="{ opacity: 1, x: 0 }"
            :delay="index * 50"
          >
            <div class="px-6 py-4">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Guide Info -->
                <div class="flex-1">
                  <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                      <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-blue-50 to-white flex items-center justify-center ring-1 ring-blue-100">
                        <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-3 mb-2">
                        <div>
                          <p class="text-sm font-semibold text-gray-900">
                            Guia #{{ guide.guide_no }}
                          </p>
                          <p class="text-xs text-gray-500">
                            {{ $t('gestlab.pages.portal_contract_guides.du_number') }}: {{ guide.du_no }}
                          </p>
                        </div>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                          getStatusClass(guide)
                        ]">
                          {{ getStatusText(guide) }}
                        </span>
                      </div>
                      
                      <!-- Guide Details -->
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.issue_date') }}</p>
                          <p class="text-sm font-medium text-gray-900">{{ formatDate(guide.date) }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.validity') }}</p>
                          <p class="text-sm font-medium" :class="getValidityClass(guide)">
                            {{ getValidityText(guide) }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.document_type') }}</p>
                          <p class="text-sm font-medium text-gray-900">
                            {{ $t('gestlab.pages.portal_contract_guides.contract_guide') }}
                          </p>
                        </div>
                      </div>

                      <!-- Additional Info -->
                      <div v-if="guide.customer || guide.description" class="mt-2">
                        <div class="flex items-start gap-2">
                          <div v-if="guide.customer" class="flex items-center gap-1">
                            <UserIcon class="h-3 w-3 text-gray-400" />
                            <span class="text-xs text-gray-600">{{ guide.customer }}</span>
                          </div>
                          <div v-if="guide.description" class="flex items-center gap-1">
                            <DocumentTextIcon class="h-3 w-3 text-gray-400" />
                            <span class="text-xs text-gray-600 truncate">{{ guide.description }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_contract_guides.last_updated') }}</p>
                    <p class="text-sm font-medium text-gray-900">
                      {{ formatRelativeTime(guide.updated_at) }}
                    </p>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <a
                      :href="route('portal.contractguides.getContractGuidePDF', { id: guide.id })"
                      target="_blank"
                      class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 ring-1 ring-blue-100"
                    >
                      <EyeIcon class="h-4 w-4" />
                      {{ $t('gestlab.pages.portal_contract_guides.view') }}
                    </a>
                    
                    <Menu as="div" class="relative">
                      <MenuButton class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon class="h-5 w-5" />
                      </MenuButton>
                      <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                      >
                        <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2 z-10">
                          <MenuItem v-slot="{ active }">
                            <a
                              :href="route('portal.contractguides.getContractGuidePDF', { id: guide.id })"
                              target="_blank"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                              @click.stop
                            >
                              <ArrowDownTrayIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_contract_guides.download') }}
                            </a>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="shareGuide(guide)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <ShareIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_contract_guides.share') }}
                            </button>
                          </MenuItem>
                          <!-- <MenuItem v-slot="{ active }">
                            <button
                              @click="duplicateGuide(guide)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <DocumentDuplicateIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_contract_guides.duplicate') }}
                            </button>
                          </MenuItem> -->
                          <hr class="my-2 border-gray-200">
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="requestExtension(guide)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <CalendarIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_contract_guides.request_extension') }}
                            </button>
                          </MenuItem>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.record.data.length" class="border-t border-gray-200 px-6 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-700">
              {{ $t('gestlab.pages.portal_contract_guides.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_contract_guides.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_contract_guides.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_contract_guides.results') }}
            </div>
            <Pagination 
              :links="props.record.meta.links" 
              :from="props.record.meta.from" 
              :to="props.record.meta.to" 
              :total="props.record.meta.total" 
              :current_page="props.record.meta.current_page" 
              :last_page="props.record.meta.last_page" 
              class="mt-2"
            />
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.pages.portal_contract_guides.quick_stats.title') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.pages.portal_contract_guides.quick_stats.monthly_average') }}</span>
              <span class="font-semibold text-blue-900">{{ monthlyAverage }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.pages.portal_contract_guides.quick_stats.yearly_total') }}</span>
              <span class="font-semibold text-blue-900">{{ yearlyTotal }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.pages.portal_contract_guides.quick_stats.expiring_this_month') }}</span>
              <span class="font-semibold text-yellow-600">{{ expiringThisMonthCount }}</span>
            </div>
          </div>
        </div>

        <!-- Help & Support -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <QuestionMarkCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.pages.portal_contract_guides.help.title') }}
          </h3>
          <div class="space-y-3">
            <p class="text-sm text-gray-600">
              {{ $t('gestlab.pages.portal_contract_guides.help.description') }}
            </p>
            <div class="flex items-center gap-2">
              <button
                @click="openHelp"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <QuestionMarkCircleIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_contract_guides.help.button') }}
              </button>
              <button
                @click="contactSupport"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <ChatBubbleLeftRightIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_contract_guides.help.contact') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import debounce from 'lodash/debounce'
import { useForm, router, Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import Pagination from '@/Components/pagination.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import {
  HomeModernIcon,
  ClipboardDocumentCheckIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PlusCircleIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  DocumentDuplicateIcon,
  CalendarIcon,
  UserIcon,
  DocumentTextIcon,
  ChartBarIcon,
  QuestionMarkCircleIcon,
  ChatBubbleLeftRightIcon,
  FunnelIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
  import Layout from "@/Shared/Layouts/PortalLayout.vue";

  defineOptions({
    layout: Layout
    });

const props = defineProps({
  record: Object,
  fields: Array,
  query: Object,
})

const page = usePage()

const query = reactive({
  search: props.query?.search || '',
  status_filter: props.query?.status_filter || '',
  page: props.query?.page || 1
})

// Computed properties for stats (these would need real data from your backend)
const activeGuidesCount = computed(() => {
  // Count guides from last 6 months
  const sixMonthsAgo = new Date()
  sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6)
  return props.record.data.filter(guide => new Date(guide.date) > sixMonthsAgo).length
})

const recentGuidesCount = computed(() => {
  // Count guides from last 30 days
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return props.record.data.filter(guide => new Date(guide.date) > thirtyDaysAgo).length
})

const expiringSoonCount = computed(() => {
  // Count guides expiring in next 30 days (you would need an expiration_date field)
  return 0 // Placeholder
})

const monthlyAverage = computed(() => {
  if (props.record.meta.total === 0) return 0
  // Calculate average guides per month
  return Math.round(props.record.meta.total / 12)
})

const yearlyTotal = computed(() => {
  // Count guides from current year
  const currentYear = new Date().getFullYear()
  return props.record.data.filter(guide => {
    const guideYear = new Date(guide.date).getFullYear()
    return guideYear === currentYear
  }).length
})

const expiringThisMonthCount = computed(() => {
  // Count guides expiring this month
  const now = new Date()
  const nextMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1)
  return 0 // Placeholder - would need expiration_date field
})

// Watch for query changes with debounce
watch(query, debounce(function(value) {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, 300))

// Helper functions
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const formatRelativeTime = (dateString) => {
  if (!dateString) return '-'
  const now = new Date()
  const date = new Date(dateString)
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Hoje'
  if (diffDays === 1) return 'Ontem'
  if (diffDays < 7) return `${diffDays} dias atrás`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} semanas atrás`
  return formatDate(dateString)
}

const getStatusClass = (guide) => {
  // Based on your data structure, you might need to add status fields
  const now = new Date()
  const guideDate = new Date(guide.date)
  const sixMonthsAgo = new Date()
  sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6)
  
  if (guideDate > sixMonthsAgo) return 'bg-green-100 text-green-800 ring-1 ring-green-600/20'
  return 'bg-gray-100 text-gray-800 ring-1 ring-gray-600/20'
}

const getStatusText = (guide) => {
  const now = new Date()
  const guideDate = new Date(guide.date)
  const sixMonthsAgo = new Date()
  sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6)
  
  if (guideDate > sixMonthsAgo) return 'ACTIVO'
  return 'ARQUIVADO'
}

const getValidityClass = (guide) => {
  // This would depend on your data structure
  return 'text-gray-900'
}

const getValidityText = (guide) => {
  // This would depend on your data structure
  return 'VÁLIDO'
}

const copyToClipboard = async (value) => {
  if (!value) return
  await navigator.clipboard.writeText(value)
}

const openDocumentRequest = (title, description, details = {}) => {
  router.get(route('portal.requests.index'), {
    new: 1,
    request_type: 'document_request',
    title,
    description,
    details,
  })
}

const requestNewGuide = () => {
  openDocumentRequest(
    'Solicitar nova guia contratual',
    'Pretendo solicitar a emissão de uma nova guia contratual.',
    { document_type: 'contract_guide' },
  )
}

const shareGuide = (guide) => {
  copyToClipboard(route('portal.contractguides.getContractGuidePDF', { id: guide.id }))
}

const duplicateGuide = (guide) => {
  openDocumentRequest(
    `Solicitar 2ª via da guia ${guide.guide_no}`,
    `Solicito uma segunda via da guia contratual ${guide.guide_no}.`,
    {
      document_reference: guide.guide_no,
      document_type: 'contract_guide',
    },
  )
}

const requestExtension = (guide) => {
  openDocumentRequest(
    `Solicitar extensão da guia ${guide.guide_no}`,
    `Solicito a extensão ou atualização da validade da guia ${guide.guide_no}.`,
    {
      document_reference: guide.guide_no,
      document_type: 'contract_guide',
    },
  )
}

const openHelp = () => {
  router.get(route('portal.faqs'))
}

const contactSupport = () => {
  router.get(route('portal.requests.index'), {
    new: 1,
    request_type: 'general_support',
    title: 'Apoio sobre guias contratuais',
  })
}
</script>
