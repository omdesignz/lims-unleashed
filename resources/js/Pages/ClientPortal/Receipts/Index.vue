<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
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
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_receipts.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <DocumentIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_receipts.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_receipts.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_receipts.receipts_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <DocumentIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_receipts.receipts') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
           v-motion
           :initial="{ opacity: 0, y: 20 }"
           :enter="{ opacity: 1, y: 0 }"
      >
        <!-- Total Received -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_receipts.stats.total_received') }}</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ formatCurrency(totalReceivedAmount) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <BanknotesIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.stats.total_received_description') }}</p>
          </div>
        </div>

        <!-- Recent Receipts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_receipts.stats.recent') }}</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ recentReceiptsCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <ClockIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.stats.recent_description') }}</p>
          </div>
        </div>

        <!-- This Month -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_receipts.stats.this_month') }}</p>
              <p class="mt-2 text-3xl font-bold text-purple-600">{{ formatCurrency(thisMonthAmount) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
              <CalendarDaysIcon class="h-6 w-6 text-purple-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.stats.this_month_description') }}</p>
          </div>
        </div>

        <!-- Average Receipt -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_receipts.stats.average') }}</p>
              <p class="mt-2 text-3xl font-bold text-yellow-600">{{ formatCurrency(averageReceiptAmount) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
              <ChartBarIcon class="h-6 w-6 text-yellow-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.stats.average_description') }}</p>
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
                :placeholder="$t('gestlab.pages.portal_receipts.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Date Filter -->
            <Menu as="div" class="relative">
              <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                <CalendarIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_receipts.date_filter') }}
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
                      {{ $t('gestlab.pages.portal_receipts.filter_by_date') }}
                    </p>
                  </div>
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.date_filter = 'this_month'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.date_filter === 'this_month' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_receipts.this_month') }}
                        <span v-if="query.date_filter === 'this_month'" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.date_filter = 'last_month'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.date_filter === 'last_month' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_receipts.last_month') }}
                        <span v-if="query.date_filter === 'last_month'" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.date_filter = 'this_year'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.date_filter === 'this_year' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_receipts.this_year') }}
                        <span v-if="query.date_filter === 'this_year'" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.date_filter = 'all'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          !query.date_filter || query.date_filter === 'all' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_receipts.all_time') }}
                        <span v-if="!query.date_filter || query.date_filter === 'all'" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- Export Button -->
            <button
              @click="exportReceipts"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_receipts.export') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Receipts Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentIcon class="h-5 w-5" />
              {{ $t('gestlab.pages.portal_receipts.recent_receipts') }}
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-sm text-blue-100">
                {{ formatDateRange() }}
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <DocumentIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_receipts.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_receipts.empty_state.description') }}
          </p>
        </div>

        <!-- Receipts List -->
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="(receipt, index) in props.record.data" 
            :key="receipt.id"
            class="group hover:bg-gray-50 transition-colors duration-200"
            v-motion
            :initial="{ opacity: 0, x: -20 }"
            :enter="{ opacity: 1, x: 0 }"
            :delay="index * 50"
          >
            <div class="px-6 py-4">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Receipt Info -->
                <div class="flex-1">
                  <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                      <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-green-50 to-white flex items-center justify-center ring-1 ring-green-100">
                        <div class="text-center">
                          <div class="text-xs font-semibold text-green-900">
                            {{ formatDay(receipt.date) }}
                          </div>
                          <div class="text-xs text-green-700">
                            {{ formatMonth(receipt.date) }}
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-3 mb-2">
                        <div>
                          <p class="text-sm font-semibold text-gray-900">
                            Recibo #{{ receipt.rec_no }}
                          </p>
                          <p class="text-xs text-gray-500">
                            {{ $t('gestlab.pages.portal_receipts.issue_date') }}: {{ formatDate(receipt.date) }}
                          </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-green-600/20">
                          {{ $t('gestlab.pages.portal_receipts.paid') }}
                        </span>
                      </div>
                      
                      <!-- Amount and Details -->
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.amount') }}</p>
                          <p class="text-lg font-bold text-green-600">
                            {{ formatCurrency(receipt.total) }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.payment_method') }}</p>
                          <p class="text-sm font-medium text-gray-900">
                            {{ getPaymentMethod(receipt) }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.related_invoice') }}</p>
                          <p class="text-sm font-medium text-gray-900">
                            {{ receipt.invoice_no || $t('gestlab.pages.portal_receipts.not_applicable') }}
                          </p>
                        </div>
                      </div>

                      <!-- Additional Info -->
                      <div v-if="receipt.description || receipt.customer" class="mt-2">
                        <div class="flex items-start gap-2">
                          <div v-if="receipt.customer" class="flex items-center gap-1">
                            <UserIcon class="h-3 w-3 text-gray-400" />
                            <span class="text-xs text-gray-600">{{ receipt.customer }}</span>
                          </div>
                          <div v-if="receipt.description" class="flex items-center gap-1">
                            <DocumentTextIcon class="h-3 w-3 text-gray-400" />
                            <span class="text-xs text-gray-600 truncate">{{ receipt.description }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_receipts.issued_by') }}</p>
                    <p class="text-sm font-medium text-gray-900">
                      {{ receipt.issued_by || $t('gestlab.pages.portal_receipts.system') }}
                    </p>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <a
                      :href="route('portal.receipts.getReceiptPDF', { id: receipt.id })"
                      target="_blank"
                      class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 ring-1 ring-blue-100"
                    >
                      <EyeIcon class="h-4 w-4" />
                      {{ $t('gestlab.pages.portal_receipts.view') }}
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
                              :href="route('portal.receipts.getReceiptPDF', { id: receipt.id })"
                              target="_blank"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                              @click.stop
                            >
                              <ArrowDownTrayIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_receipts.download') }}
                            </a>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="shareReceipt(receipt)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <ShareIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_receipts.share') }}
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="viewRelatedInvoice(receipt)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                              :disabled="!receipt.invoice_no"
                            >
                              <DocumentTextIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_receipts.view_invoice') }}
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="duplicateReceipt(receipt)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <DocumentDuplicateIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_receipts.duplicate') }}
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
              {{ $t('gestlab.pages.portal_receipts.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_receipts.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_receipts.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_receipts.results') }}
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

      <!-- Financial Summary -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Monthly Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.pages.portal_receipts.monthly_summary.title') }}
          </h3>
          <div class="space-y-4">
            <div v-for="month in monthlySummary" :key="month.name" class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
                  <span class="text-xs font-semibold text-blue-900">{{ month.name }}</span>
                </div>
                <div>
                  <p class="text-sm text-gray-900">{{ month.year }}</p>
                  <p class="text-xs text-gray-500">{{ month.count }} {{ $t('gestlab.pages.portal_receipts.receipts') }}</p>
                </div>
              </div>
              <span class="font-semibold text-green-600">{{ formatCurrency(month.amount) }}</span>
            </div>
          </div>
          <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_receipts.monthly_summary.average') }}</span>
              <span class="font-bold text-blue-900">{{ formatCurrency(monthlyAverageAmount) }}</span>
            </div>
          </div>
        </div>

        <!-- Receipt Benefits -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CheckCircleIcon class="h-5 w-5 text-green-600" />
            {{ $t('gestlab.pages.portal_receipts.benefits.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <CheckCircleIcon class="h-4 w-4 text-green-500" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_receipts.benefits.proof_of_payment') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_receipts.benefits.proof_of_payment_description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <CheckCircleIcon class="h-4 w-4 text-green-500" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_receipts.benefits.tax_deductions') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_receipts.benefits.tax_deductions_description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <CheckCircleIcon class="h-4 w-4 text-green-500" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_receipts.benefits.accounting') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_receipts.benefits.accounting_description') }}</p>
              </div>
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
import {
  HomeModernIcon,
  DocumentIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  BanknotesIcon,
  ClockIcon,
  CalendarDaysIcon,
  ChartBarIcon,
  CalendarIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  DocumentTextIcon,
  DocumentDuplicateIcon,
  UserIcon,
  CheckCircleIcon
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
  date_filter: props.query?.date_filter || 'all',
  page: props.query?.page || 1
})

// Computed properties for stats
const totalReceivedAmount = computed(() => {
  return props.record.data.reduce((sum, receipt) => sum + parseFloat(receipt.total || 0), 0)
})

const recentReceiptsCount = computed(() => {
  // Count receipts from last 30 days
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return props.record.data.filter(receipt => new Date(receipt.date) > thirtyDaysAgo).length
})

const thisMonthAmount = computed(() => {
  const now = new Date()
  const currentMonth = now.getMonth()
  const currentYear = now.getFullYear()
  
  return props.record.data.reduce((sum, receipt) => {
    const receiptDate = new Date(receipt.date)
    if (receiptDate.getMonth() === currentMonth && receiptDate.getFullYear() === currentYear) {
      return sum + parseFloat(receipt.total || 0)
    }
    return sum
  }, 0)
})

const averageReceiptAmount = computed(() => {
  if (props.record.data.length === 0) return 0
  return totalReceivedAmount.value / props.record.data.length
})

const monthlySummary = computed(() => {
  // Group receipts by month
  const months = {}
  props.record.data.forEach(receipt => {
    const date = new Date(receipt.date)
    const monthKey = `${date.getFullYear()}-${date.getMonth()}`
    
    if (!months[monthKey]) {
      months[monthKey] = {
        name: date.toLocaleDateString('pt-PT', { month: 'short' }),
        year: date.getFullYear(),
        count: 0,
        amount: 0
      }
    }
    
    months[monthKey].count++
    months[monthKey].amount += parseFloat(receipt.total || 0)
  })
  
  // Convert to array and sort by date (newest first)
  return Object.values(months)
    .sort((a, b) => b.year - a.year || b.month - a.month)
    .slice(0, 6) // Last 6 months
})

const monthlyAverageAmount = computed(() => {
  if (monthlySummary.value.length === 0) return 0
  const total = monthlySummary.value.reduce((sum, month) => sum + month.amount, 0)
  return total / monthlySummary.value.length
})

// Watch for query changes with debounce
watch(query, debounce(function(value) {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, 300))

const exportRowsToCsv = (filename, columns, rows) => {
  const header = columns.join(',')
  const content = rows.map((row) => row.map((value) => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
  const blob = new Blob([[header, ...content].join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const copyToClipboard = async (value) => {
  if (!value) return
  await navigator.clipboard.writeText(value)
}

const openBillingSupportRequest = (title, description, details = {}) => {
  router.get(route('portal.requests.index'), {
    new: 1,
    request_type: 'billing_support',
    title,
    description,
    details,
  })
}

// Helper functions
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const formatDay = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).getDate()
}

const formatMonth = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', { month: 'short' }).toUpperCase()
}

const formatDateRange = () => {
  if (!query.date_filter || query.date_filter === 'all') {
    return 'Todos os recibos'
  }
  
  const dateRanges = {
    this_month: 'Este mês',
    last_month: 'Mês anterior',
    this_year: 'Este ano'
  }
  
  return dateRanges[query.date_filter] || ''
}

const getPaymentMethod = (receipt) => {
  // This would come from your data
  return 'Transferência Bancária'
}

const exportReceipts = () => {
  exportRowsToCsv(
    'portal-receipts.csv',
    ['Referência', 'Emissão', 'Cliente', 'Total'],
    props.record.data.map((receipt) => [
      receipt.rec_no,
      formatDate(receipt.date),
      receipt.customer,
      receipt.total,
    ]),
  )
}

const shareReceipt = (receipt) => {
  copyToClipboard(route('portal.receipts.getReceiptPDF', { id: receipt.id }))
}

const viewRelatedInvoice = (receipt) => {
  openBillingSupportRequest(
    `Solicitar fatura relacionada ao recibo ${receipt.rec_no}`,
    `Pretendo consultar a fatura relacionada ao recibo ${receipt.rec_no}.`,
    {
      document_reference: receipt.rec_no,
      document_type: 'receipt',
    },
  )
}

const duplicateReceipt = (receipt) => {
  openBillingSupportRequest(
    `Solicitar 2ª via do recibo ${receipt.rec_no}`,
    `Solicito uma segunda via ou reemissão do recibo ${receipt.rec_no}.`,
    {
      document_reference: receipt.rec_no,
      document_type: 'receipt',
    },
  )
}
</script>
