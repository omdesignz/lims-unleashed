<template>
  <div class="ds-table-shell">
    <!-- Card Header -->
    <div class="ds-table-summary px-5 py-4 sm:px-6">
      <div>
        <h2 class="flex items-center gap-2 text-lg font-semibold text-[color:var(--ds-text)]">
          <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-[rgb(var(--primary-700-rgb)/0.1)] text-[rgb(var(--primary-800-rgb)/1)] dark:text-[rgb(var(--accent-100-rgb)/1)]">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </span>
          {{ $t('gestlab.general.labels.backups.status.overview') }}
        </h2>
        <p class="mt-1 text-sm text-[color:var(--ds-text-muted)]">
          {{ $t('gestlab.general.labels.backups.status.monitor_backup_systems') }}
        </p>
      </div>
      <span class="ds-chip">{{ backupStatuses.length }}</span>
    </div>

    <!-- Status Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="ds-table-head">
          <tr>
            <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.name') }}
              </div>
            </th>
            <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.disk') }}
              </div>
            </th>
            <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.health') }}
              </div>
            </th>
            <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.amount_of_backups') }}
              </div>
            </th>
            <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.newest_backup') }}
              </div>
            </th>
            <th scope="col" class="ds-table-heading px-6 py-3.5 text-left">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.used_storage') }}
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="ds-table-body divide-y">
          <tr 
            v-for="backupStatus in backupStatuses" 
            :key="backupStatus.disk"
            class="ds-table-row group"
          >
            <!-- Name -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-2xl bg-primary-50 transition-colors duration-200 group-hover:bg-primary-100 dark:bg-primary-500/10 dark:group-hover:bg-primary-500/15">
                  <svg class="h-4 w-4 text-primary-700 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div>
                  <span class="text-sm font-semibold text-[color:var(--ds-text)]">{{ backupStatus.name }}</span>
                </div>
              </div>
            </td>

            <!-- Disk -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="ds-chip">
                {{ backupStatus.disk }}
              </span>
            </td>

            <!-- Health Status -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <div 
                  :class="[
                    'flex h-8 w-8 items-center justify-center rounded-full',
                    backupStatus.healthy 
                        ? 'bg-green-100 dark:bg-green-500/10'
                        : 'bg-red-100 dark:bg-red-500/10'
                  ]"
                >
                  <CheckCircleIcon 
                    v-if="backupStatus.healthy" 
                    class="h-4 w-4 text-green-600 dark:text-green-300"
                  />
                  <XCircleIcon 
                    v-else 
                    class="h-4 w-4 text-red-600 dark:text-red-300"
                  />
                </div>
                <span 
                  :class="[
                    'text-sm font-medium',
                    backupStatus.healthy 
                      ? 'text-green-700 dark:text-green-300'
                      : 'text-red-700 dark:text-red-300'
                  ]"
                >
                  {{ backupStatus.healthy 
                    ? $t('gestlab.general.labels.backups.status.healthy') 
                    : $t('gestlab.general.labels.backups.status.unhealthy')
                  }}
                </span>
              </div>
            </td>

            <!-- Amount of Backups -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <div class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                  {{ backupStatus.amount }}
                </div>
                <div 
                  :class="[
                    'h-2 flex-1 rounded-full',
                    backupStatus.amount > 0 
                      ? 'bg-green-200 dark:bg-green-500/20'
                      : 'bg-[color:var(--ds-border)]'
                  ]"
                >
                  <div 
                    v-if="backupStatus.amount > 0"
                    :class="[
                      'h-full rounded-full',
                      backupStatus.amount >= 10 
                        ? 'bg-green-500' 
                        : backupStatus.amount >= 5 
                          ? 'bg-yellow-500' 
                          : 'bg-primary-600'
                    ]"
                    :style="{ width: `${Math.min(backupStatus.amount * 10, 100)}%` }"
                  ></div>
                </div>
              </div>
            </td>

            <!-- Newest Backup -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-[color:var(--ds-text-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="ds-table-cell">
                  {{ backupStatus.newest }}
                </span>
              </div>
            </td>

            <!-- Used Storage -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-[color:var(--ds-text-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <div>
                  <div class="text-sm font-semibold text-[color:var(--ds-text)]">
                    {{ backupStatus.usedStorage }}
                  </div>
                  <div class="h-1.5 w-20 overflow-hidden rounded-full bg-[color:var(--ds-border)]">
                    <div 
                      class="h-full bg-primary-600 transition-all duration-500"
                      :style="{ width: getStoragePercentage(backupStatus.usedStorage) }"
                    ></div>
                  </div>
                </div>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="backupStatuses.length === 0">
            <td colspan="6" class="p-5 text-center">
              <div class="ds-empty-state flex flex-col items-center gap-3 px-6 py-10">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[color:var(--ds-panel-subtle)]">
                  <svg class="h-6 w-6 text-[color:var(--ds-text-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-[color:var(--ds-text)]">
                    {{ $t('gestlab.general.labels.backups.status.no_status_data') }}
                  </h3>
                  <p class="mt-1 text-sm text-[color:var(--ds-text-muted)]">
                    {{ $t('gestlab.general.labels.backups.status.waiting_for_backup_data') }}
                  </p>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Summary Footer -->
    <div v-if="backupStatuses.length > 0" class="border-t border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-green-500"></div>
            <span class="text-xs text-[color:var(--ds-text-muted)]">
              {{ healthyCount }} {{ $t('gestlab.general.labels.backups.status.healthy_systems') }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-red-500"></div>
            <span class="text-xs text-[color:var(--ds-text-muted)]">
              {{ unhealthyCount }} {{ $t('gestlab.general.labels.backups.status.requires_attention') }}
            </span>
          </div>
        </div>
        <div class="text-xs text-[color:var(--ds-text-muted)]">
          {{ $t('gestlab.general.labels.backups.status.last_updated') }}: {{ currentTime }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  backupStatuses: { required: true, type: Array },
});

// Computed properties for summary
const healthyCount = computed(() => {
  return props.backupStatuses.filter(status => status.healthy).length;
});

const unhealthyCount = computed(() => {
  return props.backupStatuses.filter(status => !status.healthy).length;
});

const currentTime = ref('');
let timeInterval;

// Helper function to extract percentage from storage string
const getStoragePercentage = (storageString) => {
  if (!storageString) return '0%';
  
  // Extract numbers from string like "1.2 GB" or "500 MB"
  const match = storageString.match(/(\d+(\.\d+)?)\s*(GB|MB|KB)/i);
  if (!match) return '0%';
  
  const value = parseFloat(match[1]);
  const unit = match[3].toUpperCase();
  
  // Convert to MB for comparison
  let valueInMB = value;
  switch (unit) {
    case 'GB':
      valueInMB = value * 1024;
      break;
    case 'KB':
      valueInMB = value / 1024;
      break;
  }
  
  // Assuming 10GB as max for visualization (adjust as needed)
  const maxStorageMB = 10 * 1024; // 10GB in MB
  const percentage = Math.min((valueInMB / maxStorageMB) * 100, 100);
  
  return `${percentage}%`;
};

// Update current time
const updateTime = () => {
  currentTime.value = new Date().toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit',
    second: '2-digit'
  });
};

onMounted(() => {
  updateTime();
  timeInterval = window.setInterval(updateTime, 60000);
});

onBeforeUnmount(() => {
  window.clearInterval(timeInterval);
});
</script>
