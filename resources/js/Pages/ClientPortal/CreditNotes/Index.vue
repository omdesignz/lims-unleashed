<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white dark:from-slate-950 dark:to-slate-900" :class="commercialDocumentThemeClasses">
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
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_credit_notes.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <ReceiptRefundIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_credit_notes.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_credit_notes.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_credit_notes.notes_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <ReceiptRefundIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_credit_notes.notes') }}
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
        <!-- Total Credit Amount -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_credit_notes.stats.total_amount') }}</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ formatCurrency(totalCreditAmount) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <CurrencyEuroIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.stats.total_amount_description') }}</p>
          </div>
        </div>

        <!-- Recent Notes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_credit_notes.stats.recent') }}</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ recentNotesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <ClockIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.stats.recent_description') }}</p>
          </div>
        </div>

        <!-- Cancellation Notes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_credit_notes.stats.cancellations') }}</p>
              <p class="mt-2 text-3xl font-bold text-red-600">{{ cancellationNotesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
              <XCircleIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.stats.cancellations_description') }}</p>
          </div>
        </div>

        <!-- Rectification Notes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_credit_notes.stats.rectifications') }}</p>
              <p class="mt-2 text-3xl font-bold text-yellow-600">{{ rectificationNotesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
              <PencilIcon class="h-6 w-6 text-yellow-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.stats.rectifications_description') }}</p>
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
                :placeholder="$t('gestlab.pages.portal_credit_notes.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Type Filter -->
            <Menu as="div" class="relative">
              <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                <FunnelIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_credit_notes.filter') }}
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
                      {{ $t('gestlab.pages.portal_credit_notes.filter_by_type') }}
                    </p>
                  </div>
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.type_filter = ''"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          !query.type_filter ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_credit_notes.all_types') }}
                        <span v-if="!query.type_filter" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.type_filter = 'A'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.type_filter === 'A' ? 'font-semibold text-red-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_credit_notes.cancellation') }}
                        <span v-if="query.type_filter === 'A'" class="h-2 w-2 rounded-full bg-red-600"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.type_filter = 'R'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.type_filter === 'R' ? 'font-semibold text-yellow-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_credit_notes.rectification') }}
                        <span v-if="query.type_filter === 'R'" class="h-2 w-2 rounded-full bg-yellow-600"></span>
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- Export Button -->
            <button
              @click="exportCreditNotes"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_credit_notes.export') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Credit Notes Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ReceiptRefundIcon class="h-5 w-5" />
              {{ $t('gestlab.pages.portal_credit_notes.recent_notes') }}
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-sm text-blue-100">
                {{ formatCurrency(totalCreditAmount) }} {{ $t('gestlab.pages.portal_credit_notes.total_credit') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <ReceiptRefundIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_credit_notes.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_credit_notes.empty_state.description') }}
          </p>
        </div>

        <!-- Credit Notes List -->
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="(note, index) in props.record.data" 
            :key="note.id"
            class="group hover:bg-gray-50 transition-colors duration-200"
            v-motion
            :initial="{ opacity: 0, x: -20 }"
            :enter="{ opacity: 1, x: 0 }"
            :delay="index * 50"
          >
            <div class="px-6 py-4">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Credit Note Info -->
                <div class="flex-1">
                  <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                      <div :class="[
                        'h-12 w-12 rounded-lg flex items-center justify-center ring-1',
                        note.reason === 'A' 
                          ? 'bg-red-50 ring-red-100' 
                          : 'bg-yellow-50 ring-yellow-100'
                      ]">
                        <ReceiptRefundIcon :class="[
                          'h-5 w-5',
                          note.reason === 'A' ? 'text-red-600' : 'text-yellow-600'
                        ]" />
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-3 mb-2">
                        <div>
                          <p class="text-sm font-semibold text-gray-900">
                            Nota de Crédito #{{ note.note_no }}
                          </p>
                          <p class="text-xs text-gray-500">
                            {{ $t('gestlab.pages.portal_credit_notes.issue_date') }}: {{ formatDate(note.date) }}
                          </p>
                        </div>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                          note.reason === 'A' 
                            ? 'bg-red-100 text-red-800 ring-1 ring-red-600/20' 
                            : 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-600/20'
                        ]">
                          {{ note.reason === 'A' ? 'ANULAÇÃO' : 'RECTIFICAÇÃO' }}
                        </span>
                      </div>
                      
                      <!-- Amount and Details -->
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.amount') }}</p>
                          <p class="text-lg font-bold" :class="note.reason === 'A' ? 'text-red-600' : 'text-yellow-600'">
                            {{ formatCurrency(note.total) }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.status') }}</p>
                          <p class="text-sm font-medium text-green-600">
                            {{ $t('gestlab.pages.portal_credit_notes.processed') }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.related_invoice') }}</p>
                          <p class="text-sm font-medium text-gray-900">
                            {{ note.invoice_no || $t('gestlab.pages.portal_credit_notes.not_applicable') }}
                          </p>
                        </div>
                      </div>

                      <!-- Reason Badge -->
                      <div class="mt-2">
                        <div :class="[
                          'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium',
                          note.reason === 'A' 
                            ? 'bg-red-50 text-red-700 border border-red-200' 
                            : 'bg-yellow-50 text-yellow-700 border border-yellow-200'
                        ]">
                          <component 
                            :is="note.reason === 'A' ? XCircleIcon : PencilIcon" 
                            class="h-3 w-3" 
                          />
                          {{ note.reason === 'A' 
                            ? $t('gestlab.pages.portal_credit_notes.cancellation_description') 
                            : $t('gestlab.pages.portal_credit_notes.rectification_description') 
                          }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_credit_notes.available_until') }}</p>
                    <p class="text-sm font-medium text-gray-900">
                      {{ getValidityDate(note) }}
                    </p>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <a
                      :href="route('portal.creditnotes.getCreditNotePDF', { id: note.id })"
                      target="_blank"
                      class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 ring-1 ring-blue-100"
                    >
                      <EyeIcon class="h-4 w-4" />
                      {{ $t('gestlab.pages.portal_credit_notes.view') }}
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
                              :href="route('portal.creditnotes.getCreditNotePDF', { id: note.id })"
                              target="_blank"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                              @click.stop
                            >
                              <ArrowDownTrayIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_credit_notes.download') }}
                            </a>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="shareCreditNote(note)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <ShareIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_credit_notes.share') }}
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="viewRelatedInvoice(note)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                              :disabled="!note.invoice_no"
                            >
                              <DocumentTextIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_credit_notes.view_invoice') }}
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
              {{ $t('gestlab.pages.portal_credit_notes.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_credit_notes.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_credit_notes.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_credit_notes.results') }}
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

      <!-- Usage Information -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- How to Use -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.pages.portal_credit_notes.how_to_use.title') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center">
                  <span class="text-xs font-semibold text-blue-900">1</span>
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_credit_notes.how_to_use.step1.title') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_credit_notes.how_to_use.step1.description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center">
                  <span class="text-xs font-semibold text-blue-900">2</span>
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_credit_notes.how_to_use.step2.title') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_credit_notes.how_to_use.step2.description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-0.5">
                <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center">
                  <span class="text-xs font-semibold text-blue-900">3</span>
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_credit_notes.how_to_use.step3.title') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_credit_notes.how_to_use.step3.description') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Benefits -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CheckCircleIcon class="h-5 w-5 text-green-600" />
            {{ $t('gestlab.pages.portal_credit_notes.benefits.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <CheckCircleIcon class="h-4 w-4 text-green-500 flex-shrink-0" />
              <span class="text-sm text-gray-700">{{ $t('gestlab.pages.portal_credit_notes.benefits.benefit1') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <CheckCircleIcon class="h-4 w-4 text-green-500 flex-shrink-0" />
              <span class="text-sm text-gray-700">{{ $t('gestlab.pages.portal_credit_notes.benefits.benefit2') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <CheckCircleIcon class="h-4 w-4 text-green-500 flex-shrink-0" />
              <span class="text-sm text-gray-700">{{ $t('gestlab.pages.portal_credit_notes.benefits.benefit3') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <CheckCircleIcon class="h-4 w-4 text-green-500 flex-shrink-0" />
              <span class="text-sm text-gray-700">{{ $t('gestlab.pages.portal_credit_notes.benefits.benefit4') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import debounce from 'lodash/debounce'
import { useForm, router, Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import Pagination from '@/Components/pagination.vue'
import {
  HomeModernIcon,
  ReceiptRefundIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  CurrencyEuroIcon,
  ClockIcon,
  XCircleIcon,
  PencilIcon,
  PlusCircleIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  DocumentTextIcon,
  InformationCircleIcon,
  CheckCircleIcon,
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

const page = usePage();

const query = reactive({
  search: props.query?.search || '',
  type_filter: props.query?.type_filter || '',
  page: props.query?.page || 1
})

// Computed properties for stats
const totalCreditAmount = computed(() => {
  return props.record.data.reduce((sum, note) => sum + parseFloat(note.total || 0), 0)
})

const recentNotesCount = computed(() => {
  // Count notes from last 30 days
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return props.record.data.filter(note => new Date(note.date) > thirtyDaysAgo).length
})

const cancellationNotesCount = computed(() => {
  return props.record.data.filter(note => note.reason === 'A').length
})

const rectificationNotesCount = computed(() => {
  return props.record.data.filter(note => note.reason === 'R').length
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

const getValidityDate = (note) => {
  if (!note.date) return '-'
  const issueDate = new Date(note.date)
  const validUntil = new Date(issueDate)
  validUntil.setMonth(validUntil.getMonth() + 6) // Typically valid for 6 months
  return formatDate(validUntil)
}

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

const exportCreditNotes = () => {
  exportRowsToCsv(
    'portal-credit-notes.csv',
    ['Referência', 'Emissão', 'Cliente', 'Total'],
    props.record.data.map((note) => [
      note.note_no,
      formatDate(note.date),
      note.customer,
      note.total,
    ]),
  )
}

const shareCreditNote = (note) => {
  copyToClipboard(route('portal.creditnotes.getCreditNotePDF', { id: note.id }))
}

const viewRelatedInvoice = (note) => {
  openBillingSupportRequest(
    `Consultar fatura associada à nota ${note.note_no}`,
    `Pretendo consultar a fatura associada à nota de crédito ${note.note_no}.`,
    {
      document_reference: note.note_no,
      document_type: 'credit_note',
    },
  )
}
</script>
