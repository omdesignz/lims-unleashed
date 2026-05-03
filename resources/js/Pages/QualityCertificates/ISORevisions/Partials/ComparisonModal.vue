<template>
  <Modal :show="show" @close="closeModal" max-width="7xl">
    <div class="p-6">
      <!-- HEADER -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
            <ArrowsRightLeftIcon class="h-6 w-6 text-blue-900" />
            {{ $t('gestlab.general.labels.iso_revisions.compare.title') }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.iso_revisions.compare.description') }}
          </p>
        </div>
        <button 
          @click="closeModal"
          type="button"
          class="rounded-full p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-900"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- COMPARISON GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- REVISION A -->
        <div class="bg-gradient-to-b from-blue-50 to-white rounded-xl border border-blue-200 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
              A
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">
                {{ revisionA?.version || $t('gestlab.general.labels.iso_revisions.select_revision') }}
              </h3>
              <p v-if="revisionA" class="text-xs text-gray-500">
                {{ formatDate(revisionA.effective_date) }}
              </p>
            </div>
          </div>
          
          <div v-if="revisionA" class="space-y-2">
            <p class="text-sm text-gray-700">{{ revisionA.change_reason }}</p>
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <UserIcon class="h-3 w-3" />
              <span>{{ revisionA.created_by?.name }}</span>
            </div>
          </div>
        </div>

        <!-- REVISION B -->
        <div class="bg-gradient-to-b from-green-50 to-white rounded-xl border border-green-200 p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-white font-semibold">
              B
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">
                {{ revisionB?.version || $t('gestlab.general.labels.iso_revisions.select_revision') }}
              </h3>
              <p v-if="revisionB" class="text-xs text-gray-500">
                {{ formatDate(revisionB.effective_date) }}
              </p>
            </div>
          </div>
          
          <div v-if="revisionB" class="space-y-2">
            <p class="text-sm text-gray-700">{{ revisionB.change_reason }}</p>
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <UserIcon class="h-3 w-3" />
              <span>{{ revisionB.created_by?.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- DIFFERENCES TABLE -->
      <div v-if="differences.length > 0" class="mt-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ $t('gestlab.general.labels.iso_revisions.compare.differences') }}
          <span class="text-sm font-normal text-gray-500">
            ({{ differences.length }} {{ $t('gestlab.general.labels.iso_revisions.compare.changes') }})
          </span>
        </h3>
        
        <div class="overflow-hidden rounded-xl border border-gray-200">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                  {{ $t('gestlab.general.labels.iso_revisions.compare.field') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ revisionA?.version || 'A' }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ revisionB?.version || 'B' }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.compare.status') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="diff in differences" :key="diff.field">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                  {{ diff.label }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  <div class="max-w-xs truncate">{{ diff.valueA || '-' }}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  <div class="max-w-xs truncate">{{ diff.valueB || '-' }}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    diff.changed ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
                  ]">
                    {{ diff.changed ? $t('gestlab.general.labels.iso_revisions.compare.changed') : $t('gestlab.general.labels.iso_revisions.compare.unchanged') }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-else class="mt-8 text-center py-12">
        <ArrowsRightLeftIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.iso_revisions.compare.no_differences') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.iso_revisions.compare.select_different_revisions') }}
        </p>
      </div>

      <!-- FOOTER -->
      <div class="mt-8 flex items-center justify-end gap-4">
        <button 
          @click="closeModal"
          type="button"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          {{ $t('gestlab.general.labels.iso_revisions.close') }}
        </button>
        <button 
          @click="exportComparison"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <ArrowDownTrayIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.iso_revisions.export_comparison') }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '@/Components/Modal.vue'
import {
  ArrowsRightLeftIcon,
  XMarkIcon,
  UserIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  certificate: Object,
  revisionIds: Array,
})

const emit = defineEmits(['close', 'compared'])

// Local state
const differences = ref([])

// Fetch revision data when IDs change
watch(() => props.revisionIds, async (ids) => {
  if (ids.length === 2) {
    await fetchComparisonData()
  }
}, { immediate: true })

// Methods
const closeModal = () => {
  emit('close')
}

// const fetchComparisonData = async () => {
//   try {
//     const response = await fetch(route('qualitycertificates.iso-revisions.compareTwo', { 
//       certificate: props.certificate.id,
//       revision_a: props.revisionIds[0],
//       revision_b: props.revisionIds[1]
//     }))
    
//     if (response.ok) {
//       const data = await response.json()
//       differences.value = data.differences || []
//     }
//   } catch (error) {
//     console.error('Failed to fetch comparison:', error)
//   }
// }

const fetchComparisonData = async () => {
  try {
    // Use the compare-two route
    const url = route('qualitycertificates.iso-revisions.compare-two', { 
      certificate: props.certificate.id,
      revision_a: props.revisionIds[0],
      revision_b: props.revisionIds[1]
    })
    
    const response = await fetch(url)
    
    if (response.ok) {
      const data = await response.json()
      differences.value = data.differences || []
    }
  } catch (error) {
    console.error('Failed to fetch comparison:', error)
  }
}

// Or use query parameters approach:
const fetchComparisonDataQuery = async () => {
  try {
    const url = route('qualitycertificates.iso-revisions.compare', {
      certificate: props.certificate.id,
    }) + `?revision_a=${props.revisionIds[0]}&revision_b=${props.revisionIds[1]}`
    
    const response = await fetch(url)
    
    if (response.ok) {
      const data = await response.json()
      differences.value = data.differences || []
    }
  } catch (error) {
    console.error('Failed to fetch comparison:', error)
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

const exportComparison = async () => {
  // Use the export-comparison route
  const url = route('qualitycertificates.iso-revisions.export-comparison', {
    certificate: props.certificate.id,
    revision_a: props.revisionIds[0],
    revision_b: props.revisionIds[1]
  })
  window.open(url, '_blank')
}
</script>