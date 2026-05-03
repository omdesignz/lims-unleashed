<template>
  
    <div class="space-y-8">
      <!-- HEADER CARD -->
      <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-white shadow-lg shadow-blue-950/20 dark:bg-blue-500 dark:text-slate-950">
              <ArrowsRightLeftIcon class="h-6 w-6" />
            </div>
            <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.labels.iso_revisions.compare.title') }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
              {{ $t('gestlab.general.labels.iso_revisions.compare.description') }}
              <span class="font-semibold text-blue-900 dark:text-blue-300">
                {{ certificate.code }}
              </span>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Link 
              :href="route('qualitycertificates.iso-revisions.compare-two', {
                certificate: certificate.id,
                revision_a: revisionB.id,
                revision_b: revisionA.id
              })"
              as="button"
              class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950"
            >
              <ArrowsRightLeftIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.iso_revisions.swap') }}
          </Link>
          </div>
        </div>
      </div>

      <!-- COMPARISON OVERVIEW -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- REVISION A -->
        <div class="overflow-hidden rounded-[26px] border border-blue-200 bg-gradient-to-b from-blue-50 to-white shadow-[0_18px_50px_-24px_rgba(15,23,42,0.18)] dark:border-blue-500/20 dark:from-blue-500/10 dark:to-slate-950/90">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-blue-900 font-bold">
                A
              </div>
              {{ $t('gestlab.general.labels.iso_revisions.compare.revision_a') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- REVISION INFO -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.version') }}</span>
                <span class="text-lg font-bold text-blue-900">v{{ revisionA?.version }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.revision') }}</span>
                <span class="text-sm font-medium text-slate-900 dark:text-white">#{{ revisionA?.revision_number }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.effective_date') }}</span>
                <span class="text-sm text-slate-900 dark:text-white">{{ formatDate(revisionA?.effective_date) }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.change_type') }}</span>
                <span :class="getRevisionBadgeClass(revisionA?.change_type)" class="text-xs">
                  {{ $t(`gestlab.general.labels.iso_revisions.change_types.${revisionA?.change_type}`) }}
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.created_by') }}</span>
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ revisionA?.created_by?.name }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.approved_by') }}</span>
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ revisionA?.approved_by?.name || $t('gestlab.general.labels.iso_revisions.not_approved') }}</span>
              </div>
            </div>
            
            <!-- CHANGE REASON -->
            <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
              <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ $t('gestlab.general.labels.iso_revisions.change_reason') }}
              </label>
              <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-700 dark:text-slate-200">
                  {{ revisionA?.change_reason }}
                </p>
              </div>
            </div>
            
            <!-- ISO METADATA -->
            <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
              <h4 class="mb-2 text-sm font-medium text-slate-900 dark:text-white">
                {{ $t('gestlab.general.labels.iso_revisions.compare.iso_metadata') }}
              </h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600 dark:text-slate-300">ISO Section</span>
                  <span class="font-medium text-blue-900">{{ revisionA?.compliance_metadata?.iso_section || 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600 dark:text-slate-300">Change Category</span>
                  <span class="font-medium" :class="getCategoryClass(revisionA?.compliance_metadata?.change_category)">
                    {{ revisionA?.compliance_metadata?.change_category || 'N/A' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600 dark:text-slate-300">Risk Assessment</span>
                  <span class="font-medium" :class="getRiskClass(revisionA?.compliance_metadata?.risk_assessment)">
                    {{ revisionA?.compliance_metadata?.risk_assessment || 'N/A' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- REVISION B -->
        <div class="overflow-hidden rounded-[26px] border border-green-200 bg-gradient-to-b from-green-50 to-white shadow-[0_18px_50px_-24px_rgba(15,23,42,0.18)] dark:border-green-500/20 dark:from-green-500/10 dark:to-slate-950/90">
          <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-green-600 font-bold">
                B
              </div>
              {{ $t('gestlab.general.labels.iso_revisions.compare.revision_b') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- REVISION INFO -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.version') }}</span>
                <span class="text-lg font-bold text-green-600">v{{ revisionB?.version }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.revision') }}</span>
                <span class="text-sm font-medium text-slate-900 dark:text-white">#{{ revisionB?.revision_number }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.effective_date') }}</span>
                <span class="text-sm text-slate-900 dark:text-white">{{ formatDate(revisionB?.effective_date) }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.change_type') }}</span>
                <span :class="getRevisionBadgeClass(revisionB?.change_type)" class="text-xs">
                  {{ $t(`gestlab.general.labels.iso_revisions.change_types.${revisionB?.change_type}`) }}
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.created_by') }}</span>
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ revisionB?.created_by?.name }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.iso_revisions.approved_by') }}</span>
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ revisionB?.approved_by?.name || $t('gestlab.general.labels.iso_revisions.not_approved') }}</span>
              </div>
            </div>
            
            <!-- CHANGE REASON -->
            <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
              <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ $t('gestlab.general.labels.iso_revisions.change_reason') }}
              </label>
              <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-700 dark:text-slate-200">
                  {{ revisionB?.change_reason }}
                </p>
              </div>
            </div>
            
            <!-- ISO METADATA -->
            <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
              <h4 class="mb-2 text-sm font-medium text-slate-900 dark:text-white">
                {{ $t('gestlab.general.labels.iso_revisions.compare.iso_metadata') }}
              </h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600 dark:text-slate-300">ISO Section</span>
                  <span class="font-medium text-green-600">{{ revisionB?.compliance_metadata?.iso_section || 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600 dark:text-slate-300">Change Category</span>
                  <span class="font-medium" :class="getCategoryClass(revisionB?.compliance_metadata?.change_category)">
                    {{ revisionB?.compliance_metadata?.change_category || 'N/A' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600 dark:text-slate-300">Risk Assessment</span>
                  <span class="font-medium" :class="getRiskClass(revisionB?.compliance_metadata?.risk_assessment)">
                    {{ revisionB?.compliance_metadata?.risk_assessment || 'N/A' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DIFFERENCES SECTION -->
      <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.labels.iso_revisions.compare.differences') }}
              <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                ({{ differences.length }} {{ $t('gestlab.general.labels.iso_revisions.compare.changes') }})
              </span>
            </h2>
            <div class="flex items-center gap-3">
              <button 
                @click="toggleAllDifferences"
                type="button"
                class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950"
              >
                {{ showAllDifferences ? $t('gestlab.general.labels.iso_revisions.collapse_all') : $t('gestlab.general.labels.iso_revisions.expand_all') }}
              </button>
              <button 
                @click="exportComparison"
                type="button"
                class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-950 to-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-blue-900 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.iso_revisions.export_comparison') }}
              </button>
            </div>
          </div>
        </div>

        <!-- DIFFERENCES CONTENT -->
        <div class="p-6">
          <!-- EMPTY STATE -->
          <div v-if="differences.length === 0" class="py-12 text-center">
            <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400" />
            <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.labels.iso_revisions.compare.no_differences') }}
            </h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.iso_revisions.compare.identical_revisions') }}
            </p>
          </div>

          <!-- DIFFERENCES LIST -->
          <div v-else class="space-y-4">
            <!-- GROUP BY CATEGORY -->
            <div v-for="category in groupedDifferences" :key="category.name" class="space-y-3">
              <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                {{ category.label }}
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-900">
                  {{ category.items.length }}
                </span>
              </h3>
              
              <div class="space-y-2">
                <div v-for="diff in category.items" :key="diff.field" 
                     class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                  <!-- DIFFERENCE HEADER -->
                  <button 
                    @click="toggleDifference(diff.field)"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-100 transition-colors duration-150"
                  >
                    <div class="flex items-center gap-3">
                      <ChevronRightIcon 
                        :class="[
                          'h-5 w-5 text-gray-400 transition-transform duration-200',
                          expandedDifferences[diff.field] ? 'rotate-90' : ''
                        ]" 
                      />
                      <div class="text-left">
                        <h4 class="text-sm font-medium text-gray-900">
                          {{ diff.label }}
                        </h4>
                        <p class="text-xs text-gray-500 mt-1">
                          {{ diff.description || 'Field difference detected' }}
                        </p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <span v-if="diff.change_type === 'ADDED'" 
                            class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                        {{ $t('gestlab.general.labels.iso_revisions.compare.added') }}
                      </span>
                      <span v-else-if="diff.change_type === 'REMOVED'"
                            class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                        {{ $t('gestlab.general.labels.iso_revisions.compare.removed') }}
                      </span>
                      <span v-else
                            class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
                        {{ $t('gestlab.general.labels.iso_revisions.compare.modified') }}
                      </span>
                    </div>
                  </button>
                  
                  <!-- EXPANDED DIFFERENCE DETAILS -->
                  <div v-if="expandedDifferences[diff.field]" class="border-t border-gray-200 p-4 bg-white">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- REVISION A VALUE -->
                      <div class="space-y-2">
                        <div class="flex items-center justify-between">
                          <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $t('gestlab.general.labels.iso_revisions.compare.revision_a_value') }}
                            <span class="text-blue-900">(v{{ revisionA?.version }})</span>
                          </label>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                          <p class="text-sm font-medium text-gray-900 break-words">
                            {{ formatValue(diff.valueA) }}
                          </p>
                          <p v-if="diff.original_value_a" class="text-xs text-gray-500 mt-1">
                            Original: {{ formatValue(diff.original_value_a) }}
                          </p>
                        </div>
                      </div>
                      
                      <!-- REVISION B VALUE -->
                      <div class="space-y-2">
                        <div class="flex items-center justify-between">
                          <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $t('gestlab.general.labels.iso_revisions.compare.revision_b_value') }}
                            <span class="text-green-600">(v{{ revisionB?.version }})</span>
                          </label>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                          <p class="text-sm font-medium text-gray-900 break-words">
                            {{ formatValue(diff.valueB) }}
                          </p>
                          <p v-if="diff.original_value_b" class="text-xs text-gray-500 mt-1">
                            Original: {{ formatValue(diff.original_value_b) }}
                          </p>
                        </div>
                      </div>
                    </div>
                    
                    <!-- CHANGE SUMMARY -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                      <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.compare.change_summary') }}</span>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                          diff.impact === 'HIGH' 
                            ? 'bg-red-100 text-red-800'
                            : diff.impact === 'MEDIUM'
                            ? 'bg-yellow-100 text-yellow-800'
                            : 'bg-blue-100 text-blue-800'
                        ]">
                          {{ $t(`gestlab.general.labels.iso_revisions.compare.impact.${diff.impact}`) }}
                        </span>
                      </div>
                      <p v-if="diff.notes" class="text-sm text-gray-700 mt-2">
                        {{ diff.notes }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SUMMARY STATISTICS -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.compare.total_differences') }}</p>
              <p class="mt-2 text-2xl font-bold text-blue-900">{{ differences.length }}</p>
            </div>
            <ArrowsRightLeftIcon class="h-8 w-8 text-blue-900" />
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.compare.high_impact_changes') }}</p>
              <p class="mt-2 text-2xl font-bold text-red-600">
                {{ highImpactChanges }}
              </p>
            </div>
            <ExclamationTriangleIcon class="h-8 w-8 text-red-600" />
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.compare.time_between') }}</p>
              <p class="mt-2 text-sm font-semibold text-gray-900">{{ timeBetweenRevisions }}</p>
            </div>
            <ClockIcon class="h-8 w-8 text-gray-900" />
          </div>
        </div>
      </div>

      <!-- FOOTER ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <Link
          :href="route('qualitycertificates.iso-revisions.index', certificate.id)"
          as="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <ArrowLeftIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.iso_revisions.back_to_revisions') }}
      </Link>
        
        <div class="flex items-center gap-4">
          <button 
            @click="printComparison"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PrinterIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.print') }}
          </button>
          
          <button 
            @click="saveComparison"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <BookmarkIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.save_comparison') }}
          </button>
        </div>
      </div>
    </div>
 
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import Layout from "@/Shared/Layouts/Layout.vue";


// Icons
import {
  ArrowsRightLeftIcon,
  ArrowDownTrayIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  ArrowLeftIcon,
  PrinterIcon,
  BookmarkIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  certificate: Object,
  revisionA: Object,
  revisionB: Object,
  differences: Array,
})

// Local state
const expandedDifferences = ref({})
const showAllDifferences = ref(false)

// Computed properties
const groupedDifferences = computed(() => {
  const groups = {
    certificate: {
      label: 'Certificate Data',
      items: props.differences.filter(diff => diff.category === 'certificate')
    },
    metadata: {
      label: 'Metadata',
      items: props.differences.filter(diff => diff.category === 'metadata')
    },
    related: {
      label: 'Related Data',
      items: props.differences.filter(diff => diff.category === 'related')
    },
    iso: {
      label: 'ISO Compliance',
      items: props.differences.filter(diff => diff.category === 'iso')
    }
  }
  
  // Only return groups that have items
  return Object.entries(groups)
    .filter(([_, group]) => group.items.length > 0)
    .map(([key, group]) => ({ name: key, ...group }))
})

const highImpactChanges = computed(() => {
  return props.differences.filter(diff => diff.impact === 'HIGH').length
})

const timeBetweenRevisions = computed(() => {
  if (!props.revisionA?.effective_date || !props.revisionB?.effective_date) {
    return 'N/A'
  }
  
  const dateA = new Date(props.revisionA.effective_date)
  const dateB = new Date(props.revisionB.effective_date)
  const diffTime = Math.abs(dateB - dateA)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    return 'Same day'
  } else if (diffDays === 1) {
    return '1 day'
  } else if (diffDays < 30) {
    return `${diffDays} days`
  } else if (diffDays < 365) {
    const months = Math.floor(diffDays / 30)
    return `${months} month${months > 1 ? 's' : ''}`
  } else {
    const years = Math.floor(diffDays / 365)
    return `${years} year${years > 1 ? 's' : ''}`
  }
})

// Methods
const getRevisionBadgeClass = (changeType) => {
  const classes = {
    CREATED: 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800',
    UPDATED: 'inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800',
    CORRECTED: 'inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800',
    REISSUED: 'inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800',
    WITHDRAWN: 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800',
  }
  return classes[changeType] || 'inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800'
}

const getCategoryClass = (category) => {
  const classes = {
    CRITICAL: 'text-red-700',
    HIGH: 'text-orange-700',
    MEDIUM: 'text-yellow-700',
    LOW: 'text-green-700',
    ROUTINE: 'text-blue-700',
  }
  return classes[category] || 'text-gray-700'
}

const getRiskClass = (risk) => {
  const classes = {
    CRITICAL: 'text-red-700',
    HIGH: 'text-orange-700',
    MEDIUM: 'text-yellow-700',
    LOW: 'text-green-700',
  }
  return classes[risk] || 'text-gray-700'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatValue = (value) => {
  if (value === null || value === undefined) return 'N/A'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (Array.isArray(value)) return value.length > 0 ? value.join(', ') : 'None'
  if (typeof value === 'object') return JSON.stringify(value, null, 2)
  if (typeof value === 'string' && value.length > 200) {
    return value.substring(0, 200) + '...'
  }
  return String(value)
}

const toggleDifference = (field) => {
  expandedDifferences.value[field] = !expandedDifferences.value[field]
}

const toggleAllDifferences = () => {
  showAllDifferences.value = !showAllDifferences.value
  
  if (showAllDifferences.value) {
    props.differences.forEach(diff => {
      expandedDifferences.value[diff.field] = true
    })
  } else {
    expandedDifferences.value = {}
  }
}

const swapRevisions = () => {
  router.get(route('qualitycertificates.iso-revisions.compare-two', {
    certificate: props.certificate.id,
    revision_a: props.revisionB.id,
    revision_b: props.revisionA.id
  }))
}

// const compareWithCurrent = (revision) => {
//   // Use the compare-two route instead
//   router.visit(route('qualitycertificates.iso-revisions.compare-two', {
//     certificate: props.certificate.id,
//     revision_a: revision.id,
//     revision_b: props.currentRevision?.id
//   }))
// }

const exportComparison = () => {
  const url = route('qualitycertificates.iso-revisions.export-comparison', {
    certificate: props.certificate.id,
    revision_a: props.revisionA.id,
    revision_b: props.revisionB.id
  })
  window.open(url, '_blank')
}

const printComparison = () => {
  window.print()
}

const saveComparison = () => {
  // Save comparison to user's saved comparisons
  console.log('Save comparison')
}
</script>
