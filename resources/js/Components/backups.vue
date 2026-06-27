<template>
  <div class="space-y-6">
    <!-- Disk Selector -->
    <div v-if="props?.disks?.length > 1" class="ds-panel p-4 sm:p-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[rgb(var(--primary-700-rgb)/0.1)] text-[rgb(var(--primary-800-rgb)/1)] dark:text-[rgb(var(--accent-100-rgb)/1)]">
            <CloudArrowDownIcon class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-[color:var(--ds-text)]">
              {{ $t('gestlab.general.labels.backups.storage_disk') }}
            </h3>
            <p class="text-xs text-[color:var(--ds-text-muted)]">
              {{ $t('gestlab.general.labels.backups.select_storage_location') }}
            </p>
          </div>
        </div>
        
        <select
          v-model="selectedDisk"
          class="ds-field min-w-52"
        >
          <option 
            v-for="disk in props.disks" 
            :key="disk" 
            :value="disk"
          >
            {{ disk }}
          </option>
        </select>
      </div>
    </div>

    <!-- Backups Table -->
    <div class="ds-table-shell">
      <!-- Table Header -->
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <h3 class="flex flex-wrap items-center gap-2 text-lg font-semibold text-[color:var(--ds-text)]">
            <CloudArrowDownIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)] dark:text-[rgb(var(--accent-100-rgb)/1)]" />
            {{ $t('gestlab.general.labels.backups.stored_backups') }}
            <span class="ds-chip ml-1">
              ({{ props.backups?.length || 0 }} {{ $t('gestlab.general.labels.backups.items') }})
            </span>
          </h3>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="ds-table-head">
            <tr>
              <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
                <div class="flex items-center gap-1">
                  {{ $t('gestlab.general.labels.backups.status.path') }}
                </div>
              </th>
              <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
                <div class="flex items-center gap-1">
                  {{ $t('gestlab.general.labels.backups.status.created_at') }}
                </div>
              </th>
              <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
                <div class="flex items-center gap-1">
                  {{ $t('gestlab.general.labels.backups.status.size') }}
                </div>
              </th>
              <th scope="col" class="relative px-6 py-3.5">
                <span class="sr-only">{{ $t('gestlab.general.labels.vap_filemanager.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y">
            <!-- Backup Rows -->
            <backup-row
              v-for="backup in props.backups"
              v-bind="backup"
              :disk="props.activeDisk"
              :deletable="props.backups?.length > 1"
              :deleting="!deleteModalOpen && deletingBackup && backup?.path === deletingBackup?.path"
              :key="backup.id"
              @delete="openDeleteModal(backup)"
            />

            <!-- Empty State -->
            <tr v-if="props.backups?.length === 0">
              <td colspan="4" class="p-5 text-center">
                <div class="ds-empty-state flex flex-col items-center gap-3 px-6 py-10">
                  <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[color:var(--ds-panel-subtle)]">
                    <CloudArrowDownIcon class="h-6 w-6 text-[color:var(--ds-text-soft)]" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-[color:var(--ds-text)]">
                      {{ $t('gestlab.general.labels.backups.no_backups_found') }}
                    </h3>
                    <p class="mt-1 text-sm text-[color:var(--ds-text-muted)]">
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
      <div v-if="props.backups?.length > 0" class="border-t border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-sm text-[color:var(--ds-text-muted)]">
            {{ $t('gestlab.general.labels.backups.showing') }}
            <span class="font-semibold text-[color:var(--ds-text)]">{{ props.backups?.length }}</span>
            {{ $t('gestlab.general.labels.backups.backups') }}
          </div>
          <div class="text-xs text-[color:var(--ds-text-muted)]">
            {{ $t('gestlab.general.labels.backups.disk') }}:
            <span class="font-semibold text-[rgb(var(--primary-800-rgb)/1)] dark:text-[rgb(var(--accent-100-rgb)/1)]">{{ props.activeDisk }}</span>
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
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
          <TrashIcon class="h-6 w-6 text-red-600" />
        </div>
      </template>
      
      <template v-if="deletingBackup" #content>
        <div class="mt-3 rounded-2xl bg-red-50 p-4 dark:bg-red-500/10">
          <div class="flex items-start gap-3">
            <CloudArrowDownIcon class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
            <div class="text-sm">
              <p class="font-medium text-red-900 dark:text-red-100">
                {{ $t('gestlab.general.labels.backups.backup_to_delete') }}:
              </p>
              <p class="mt-1 rounded bg-red-100 px-3 py-1.5 font-mono text-sm text-red-700 dark:bg-red-500/10 dark:text-red-200">
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
  'delete',
  'update:activeDisk'
]);

const deletingBackup = ref(null);
const deleteModalOpen = ref(false);

const selectedDisk = computed({
  get() {
    return props.activeDisk
  },
  set(value) {
    emit('update:activeDisk', value)
  },
})

const openDeleteModal = (backup) => {
  deleteModalOpen.value = true;
  deletingBackup.value = backup;
}

const closeDeleteModal = () => {
  deleteModalOpen.value = false;
  deletingBackup.value = null;
}

const confirmDelete = () => {
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
