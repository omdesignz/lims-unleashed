<template>
  <div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900">
        Histórico de revisões ISO 17025
        <span class="text-sm font-normal text-gray-500">
          (Taxa de conformidade: {{ stats.compliance_rate }}%)
        </span>
      </h3>
    </div>
    
    <div class="p-6">
      <!-- Revision List -->
      <div class="space-y-4">
        <div v-for="revision in revisions.data" :key="revision.id"
             class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
          <div class="flex justify-between items-start">
            <div>
              <div class="flex items-center space-x-2">
                <span :class="revisionBadgeClass(revision)"
                      class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ revisionTypeLabel(revision.change_type) }}
                </span>
                <span class="text-sm font-medium text-gray-900">
                  v{{ revision.version }}
                </span>
                <span class="text-sm text-gray-500">
                  (Rev. {{ revision.revision_number }})
                </span>
              </div>
              
              <p class="mt-1 text-sm text-gray-700">
                {{ revision.change_reason }}
              </p>
              
              <div class="mt-2 text-xs text-gray-500">
                <span>Por: {{ revision.created_by?.name }}</span>
                <span class="mx-2">•</span>
                <span>Aprovado por: {{ revision.approved_by?.name || 'N/D' }}</span>
                <span class="mx-2">•</span>
                <span>{{ formatDate(revision.effective_date) }}</span>
              </div>
            </div>
            
            <div class="flex space-x-2">
              <button @click="viewRevision(revision)"
                      class="text-sm text-blue-600 hover:text-blue-800">
                Ver
              </button>
              <button @click="compareWithCurrent(revision)"
                      v-if="!revision.is_current"
                      class="text-sm text-purple-600 hover:text-purple-800">
                Comparar
              </button>
              <button @click="showRestoreModal(revision)"
                      v-if="canRestore && !revision.is_current"
                      class="text-sm text-green-600 hover:text-green-800">
                Restaurar
              </button>
            </div>
          </div>
          
          <!-- Activity Logs for this revision -->
          <div v-if="expandedRevision === revision.id" class="mt-4 border-t pt-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Actividades relacionadas</h4>
            <div class="space-y-2">
              <div v-for="logId in revision.activity_log_ids || []" :key="logId"
                   class="text-xs text-gray-600">
                <span class="font-medium">{{ getActivityDescription(logId) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Pagination -->
      <Pagination :links="revisions.links" class="mt-6" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  revisions: Object,
  certificate: Object,
  activityLogs: Object,
  stats: Object,
  canRestore: Boolean,
})

const expandedRevision = ref(null)

const revisionTypeLabels = {
  CREATED: 'Criado',
  UPDATED: 'Actualizado',
  CORRECTED: 'Corrigido',
  REISSUED: 'Reemitido',
  WITHDRAWN: 'Retirado',
}

const revisionTypeLabel = (changeType) => revisionTypeLabels[changeType] || changeType

const revisionBadgeClass = (revision) => {
  const classes = {
    CREATED: 'bg-green-100 text-green-800',
    UPDATED: 'bg-blue-100 text-blue-800',
    CORRECTED: 'bg-yellow-100 text-yellow-800',
    REISSUED: 'bg-purple-100 text-purple-800',
    WITHDRAWN: 'bg-red-100 text-red-800',
  }
  return classes[revision.change_type] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('pt-AO')
}

const viewRevision = (revision) => {
  router.visit(route('certificates.iso-revisions.show', {
    certificate: props.certificate.id,
    revision: revision.id
  }))
}

const compareWithCurrent = (revision) => {
  router.visit(route('certificates.iso-revisions.compare', {
    certificate: props.certificate.id,
    revision_a: revision.id,
    revision_b: props.certificate.current_revision?.id
  }))
}

const showRestoreModal = (revision) => {
  // Implement restore modal logic
  emit('restore', revision)
}

const getActivityDescription = (logId) => {
  const log = props.activityLogs.data?.find(l => l.id === logId)
  return log?.description || `Registo #${logId}`
}
</script>
