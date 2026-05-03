<template>
  <div class="space-y-6">
    <!-- Disk Selector -->
    <div v-if="props?.disks?.length > 1" class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-gray-200 p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white shadow-sm">
            <CloudArrowDownIcon class="h-5 w-5 text-blue-900" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.backups.storage_disk') }}
            </h3>
            <p class="text-xs text-gray-500">
              {{ $t('gestlab.general.labels.backups.select_storage_location') }}
            </p>
          </div>
        </div>
        
        <select
          v-model="props.activeDisk"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
        >
          <option 
            v-for="disk in props.disks" 
            :key="disk" 
            :value="disk"
            class="text-gray-700"
          >
            {{ disk }}
          </option>
        </select>
      </div>
    </div>

    <!-- Backups Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- Table Header -->
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <CloudArrowDownIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.backups.stored_backups') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ props.backups?.length || 0 }} {{ $t('gestlab.general.labels.backups.items') }})
            </span>
          </h3>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                <div class="flex items-center gap-1">
                  {{ $t('gestlab.general.labels.backups.status.path') }}
                </div>
              </th>
              <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                <div class="flex items-center gap-1">
                  {{ $t('gestlab.general.labels.backups.status.created_at') }}
                </div>
              </th>
              <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                <div class="flex items-center gap-1">
                  {{ $t('gestlab.general.labels.backups.status.size') }}
                </div>
              </th>
              <th scope="col" class="relative px-6 py-3.5">
                <span class="sr-only">{{ $t('gestlab.general.labels.vap_filemanager.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <!-- Backup Rows -->
            <backup-row
              v-for="(backup, recordIdx) in props.backups"
              v-bind="backup"
              :disk="props.activeDisk"
              :deletable="props.backups?.length > 1"
              :deleting="!deleteModalOpen && deletingBackup && backup?.path === deletingBackup?.path"
              :key="backup.id"
              @delete="openDeleteModal(backup)"
              class="hover:bg-gray-50 transition-colors duration-200"
            />

            <!-- Empty State -->
            <tr v-if="props.backups?.length === 0">
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <CloudArrowDownIcon class="h-6 w-6 text-gray-400" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      {{ $t('gestlab.general.labels.backups.no_backups_found') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                      {{ $t('gestlab.general.labels.backups.create_backup_to_get_started') }}
                    </p>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Footer -->
      <div v-if="props.backups?.length > 0" class="border-t border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            {{ $t('gestlab.general.labels.backups.showing') }}
            <span class="font-semibold text-gray-700">{{ props.backups?.length }}</span>
            {{ $t('gestlab.general.labels.backups.backups') }}
          </div>
          <div class="text-xs text-gray-500">
            {{ $t('gestlab.general.labels.backups.disk') }}:
            <span class="font-semibold text-blue-900">{{ props.activeDisk }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <confirm-dialog
      :show="deleteModalOpen"
      @close="closeDeleteModal"
      @confirmed="confirmDelete"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :confirm="$t('gestlab.general.buttons.yes')"
      :cancel="$t('gestlab.general.buttons.no')"
    >
      <template v-if="deletingBackup" #icon>
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
          <TrashIcon class="h-6 w-6 text-red-600" />
        </div>
      </template>
      
      <template v-if="deletingBackup" #content>
        <div class="mt-3 bg-red-50 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <CloudArrowDownIcon class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
            <div class="text-sm">
              <p class="font-medium text-red-900">
                {{ $t('gestlab.general.labels.backups.backup_to_delete') }}:
              </p>
              <p class="mt-1 text-red-700 font-mono text-sm bg-red-100 px-3 py-1.5 rounded">
                {{ deletingBackup.path }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </confirm-dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import backupRow from '@/Components/backup-row.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import { CloudArrowDownIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
  disks: { required: true, type: Array },
  activeDisk: { required: true, type: String },
  backups: { required: true, type: Array },
});

const emit = defineEmits([
  'setModalVisibility',
  'delete',
  'update:activeDisk'
]);

const deletingBackup = ref(null);
const deleteModalOpen = ref(false);

const getDiscs = () => {
  return props.disks?.map(val => ({ value: val, label: val }));
}

const openDeleteModal = (backup) => {
  emit('setModalVisibility', true);
  deleteModalOpen.value = true;
  deletingBackup.value = backup;
}

const closeDeleteModal = (backup) => {
  emit('setModalVisibility', false);
  deleteModalOpen.value = false;
  deletingBackup.value = null;
}

const confirmDelete = () => {
  emit('setModalVisibility', false);
  deleteModalOpen.value = false;
  
  emit('delete', {
    disk: props.activeDisk,
    path: deletingBackup.value?.path,
  });
  
  deletingBackup.value = null;
}

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.delete');
})

const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.delete');
})
</script>

<style scoped>
/* Smooth hover transitions */
tbody tr {
  transition: background-color 0.2s ease;
}

/* Custom scrollbar for table */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>