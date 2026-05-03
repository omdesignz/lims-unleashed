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
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_quality_certificates.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <DocumentCheckIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_quality_certificates.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_quality_certificates.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_quality_certificates.certificates_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <DocumentCheckIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_quality_certificates.certificates') }}
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
        <!-- Recent Certificates -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quality_certificates.stats.recent') }}</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ recentCertificatesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <ClockIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.stats.recent_description') }}</p>
          </div>
        </div>

        <!-- Valid Certificates -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quality_certificates.stats.valid') }}</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ validCertificatesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <CheckBadgeIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.stats.valid_description') }}</p>
          </div>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quality_certificates.stats.expiring') }}</p>
              <p class="mt-2 text-3xl font-bold text-yellow-600">{{ expiringSoonCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
              <ExclamationTriangleIcon class="h-6 w-6 text-yellow-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.stats.expiring_description') }}</p>
          </div>
        </div>

        <!-- Expired -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quality_certificates.stats.expired') }}</p>
              <p class="mt-2 text-3xl font-bold text-red-600">{{ expiredCertificatesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
              <XCircleIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.stats.expired_description') }}</p>
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
                :placeholder="$t('gestlab.pages.portal_quality_certificates.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Sort Dropdown -->
            <Menu as="div" class="relative">
              <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                <BarsArrowDownIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_quality_certificates.sort') }}
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
                      {{ $t('gestlab.pages.portal_quality_certificates.sort_by') }}
                    </p>
                  </div>
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="sortBy('issue_date')"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.sort === 'issue_date' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quality_certificates.sort_date') }}
                        <ArrowDownIcon v-if="query.sort === 'issue_date' && query.order === 'desc'" class="h-4 w-4" />
                        <ArrowUpIcon v-if="query.sort === 'issue_date' && query.order === 'asc'" class="h-4 w-4" />
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="sortBy('code')"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.sort === 'code' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quality_certificates.sort_code') }}
                        <ArrowDownIcon v-if="query.sort === 'code' && query.order === 'desc'" class="h-4 w-4" />
                        <ArrowUpIcon v-if="query.sort === 'code' && query.order === 'asc'" class="h-4 w-4" />
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="sortBy('valid_until')"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.sort === 'valid_until' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quality_certificates.sort_validity') }}
                        <ArrowDownIcon v-if="query.sort === 'valid_until' && query.order === 'desc'" class="h-4 w-4" />
                        <ArrowUpIcon v-if="query.sort === 'valid_until' && query.order === 'asc'" class="h-4 w-4" />
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- Request New Button -->
            <button
              @click="requestNewCertificate"
              class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_quality_certificates.request_new') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Certificates Grid -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <DocumentCheckIcon class="h-5 w-5" />
            {{ $t('gestlab.pages.portal_quality_certificates.recent_certificates') }}
          </h2>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <DocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_quality_certificates.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_quality_certificates.empty_state.description') }}
          </p>
          <button
            @click="requestNewCertificate"
            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-4 w-4" />
            {{ $t('gestlab.pages.portal_quality_certificates.request_first') }}
          </button>
        </div>

        <!-- Certificates List -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
          <div 
            v-for="(certificate, index) in props.record.data" 
            :key="certificate.id"
            class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0 }"
            :delay="index * 50"
          >
            <!-- Certificate Header -->
            <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                    {{ getStatusIcon(certificate) }}
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      Boletim #{{ certificate.code }}
                    </h3>
                    <p class="text-xs text-gray-500">
                      {{ certificate.lab_code }}
                    </p>
                  </div>
                </div>
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  getValidityClass(certificate)
                ]">
                  {{ getValidityText(certificate) }}
                </span>
              </div>
            </div>
            
            <!-- Certificate Content -->
            <div class="p-4">
              <div class="space-y-4">
                <!-- Basic Info -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.invoice') }}</p>
                    <p class="text-sm font-medium text-gray-900">{{ certificate.du_no || '-' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.issue_date') }}</p>
                    <p class="text-sm font-medium text-gray-900">{{ formatDate(certificate.issue_date) }}</p>
                  </div>
                </div>

                <!-- Validity Period -->
                <div>
                  <p class="text-xs text-gray-500 mb-2">{{ $t('gestlab.pages.portal_quality_certificates.validity_period') }}</p>
                  <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full rounded-full"
                      :class="getValidityProgressClass(certificate)"
                      :style="{ width: `${getValidityProgress(certificate)}%` }"
                    ></div>
                  </div>
                  <div class="flex justify-between mt-1 text-xs text-gray-500">
                    <span>{{ formatDate(certificate.issue_date) }}</span>
                    <span>{{ formatDate(certificate.valid_until) }}</span>
                  </div>
                </div>

                <!-- Observations -->
                <div v-if="certificate.obs">
                  <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quality_certificates.observations') }}</p>
                  <p class="text-sm text-gray-700 mt-1 line-clamp-2">{{ certificate.obs }}</p>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                  <button
                    @click="previewCertificate(certificate)"
                    class="inline-flex items-center gap-2 text-sm font-medium text-blue-900 hover:text-blue-800"
                  >
                    <EyeIcon class="h-4 w-4" />
                    {{ $t('gestlab.pages.portal_quality_certificates.preview') }}
                  </button>
                  <div class="flex items-center gap-2">
                    <a
                      :href="route('portal.qualitycertificates.getQualityCertificatePDF', { id: certificate.id })"
                      target="_blank"
                      class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 ring-1 ring-blue-100"
                    >
                      <ArrowDownTrayIcon class="h-3 w-3" />
                      {{ $t('gestlab.pages.portal_quality_certificates.download') }}
                    </a>
                    <button
                      @click="shareCertificate(certificate)"
                      class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                    >
                      <ShareIcon class="h-3 w-3" />
                      {{ $t('gestlab.pages.portal_quality_certificates.share') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Certificate Footer -->
            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100">
              <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ formatDate(certificate.created_at) }}</span>
                <span class="flex items-center gap-1">
                  <DocumentCheckIcon class="h-3 w-3" />
                  {{ $t('gestlab.pages.portal_quality_certificates.certificate') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.record.data.length" class="border-t border-gray-200 px-6 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-700">
              {{ $t('gestlab.pages.portal_quality_certificates.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_quality_certificates.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_quality_certificates.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_quality_certificates.results') }}
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
  DocumentCheckIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  ClockIcon,
  CheckBadgeIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  PlusCircleIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  BarsArrowDownIcon,
  ArrowDownIcon,
  ArrowUpIcon
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
  sort: props.query?.sort || 'issue_date',
  order: props.query?.order || 'desc',
  page: props.query?.page || 1
})

// Computed properties for stats (these would need real data from your backend)
const recentCertificatesCount = computed(() => {
  // Count certificates from last 30 days
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return props.record.data.filter(cert => new Date(cert.issue_date) > thirtyDaysAgo).length
})

const validCertificatesCount = computed(() => {
  return props.record.data.filter(cert => {
    if (!cert.valid_until) return true
    return new Date(cert.valid_until) > new Date()
  }).length
})

const expiringSoonCount = computed(() => {
  const thirtyDaysFromNow = new Date()
  thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30)
  return props.record.data.filter(cert => {
    if (!cert.valid_until) return false
    const validUntil = new Date(cert.valid_until)
    return validUntil > new Date() && validUntil <= thirtyDaysFromNow
  }).length
})

const expiredCertificatesCount = computed(() => {
  return props.record.data.filter(cert => {
    if (!cert.valid_until) return false
    return new Date(cert.valid_until) <= new Date()
  }).length
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
  return new Date(dateString).toLocaleDateString('pt-PT')
}

const getStatusIcon = (certificate) => {
  if (!certificate.valid_until) return '✓'
  const validUntil = new Date(certificate.valid_until)
  const today = new Date()
  
  if (validUntil < today) return '✗'
  if ((validUntil - today) / (1000 * 60 * 60 * 24) <= 30) return '⚠'
  return '✓'
}

const getValidityClass = (certificate) => {
  if (!certificate.valid_until) return 'bg-green-100 text-green-800'
  const validUntil = new Date(certificate.valid_until)
  const today = new Date()
  
  if (validUntil < today) return 'bg-red-100 text-red-800'
  if ((validUntil - today) / (1000 * 60 * 60 * 24) <= 30) return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
}

const getValidityText = (certificate) => {
  if (!certificate.valid_until) return 'VÁLIDO'
  const validUntil = new Date(certificate.valid_until)
  const today = new Date()
  
  if (validUntil < today) return 'EXPIRADO'
  if ((validUntil - today) / (1000 * 60 * 60 * 24) <= 30) return 'A EXPIRAR'
  return 'VÁLIDO'
}

const getValidityProgress = (certificate) => {
  if (!certificate.issue_date || !certificate.valid_until) return 0
  
  const issueDate = new Date(certificate.issue_date)
  const validUntil = new Date(certificate.valid_until)
  const today = new Date()
  
  const totalDuration = validUntil - issueDate
  const elapsed = today - issueDate
  
  return Math.min(100, Math.max(0, (elapsed / totalDuration) * 100))
}

const getValidityProgressClass = (certificate) => {
  const progress = getValidityProgress(certificate)
  if (progress > 90) return 'bg-red-500'
  if (progress > 75) return 'bg-yellow-500'
  return 'bg-green-500'
}

const sortBy = (field) => {
  if (query.sort === field) {
    query.order = query.order === 'asc' ? 'desc' : 'asc'
  } else {
    query.sort = field
    query.order = 'desc'
  }
}

const copyToClipboard = async (value) => {
  if (!value) return
  await navigator.clipboard.writeText(value)
}

const requestNewCertificate = () => {
  router.get(route('portal.requests.index'), {
    new: 1,
    request_type: 'certificate_support',
    title: 'Solicitar novo certificado',
  })
}

const previewCertificate = (certificate) => {
  window.open(route('portal.qualitycertificates.getQualityCertificatePDF', { id: certificate.id }), '_blank')
}

const shareCertificate = (certificate) => {
  copyToClipboard(route('portal.qualitycertificates.getQualityCertificatePDF', { id: certificate.id }))
}
</script>
