<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Card Header -->
    <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
      <h2 class="text-lg font-semibold text-white flex items-center gap-2">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ $t('gestlab.general.labels.backups.status.overview') }}
      </h2>
      <p class="mt-1 text-sm text-blue-100">
        {{ $t('gestlab.general.labels.backups.status.monitor_backup_systems') }}
      </p>
    </div>

    <!-- Status Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.name') }}
              </div>
            </th>
            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.disk') }}
              </div>
            </th>
            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.health') }}
              </div>
            </th>
            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.amount_of_backups') }}
              </div>
            </th>
            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.newest_backup') }}
              </div>
            </th>
            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
              <div class="flex items-center gap-1">
                {{ $t('gestlab.general.labels.backups.status.used_storage') }}
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr 
            v-for="backupStatus in backupStatuses" 
            :key="backupStatus.disk"
            class="hover:bg-gray-50 transition-colors duration-200 group"
          >
            <!-- Name -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100 transition-colors duration-200">
                  <svg class="h-4 w-4 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-900">{{ backupStatus.name }}</span>
                </div>
              </div>
            </td>

            <!-- Disk -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
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
                      ? 'bg-green-100' 
                      : 'bg-red-100'
                  ]"
                >
                  <CheckCircleIcon 
                    v-if="backupStatus.healthy" 
                    class="h-4 w-4 text-green-600" 
                  />
                  <XCircleIcon 
                    v-else 
                    class="h-4 w-4 text-red-600" 
                  />
                </div>
                <span 
                  :class="[
                    'text-sm font-medium',
                    backupStatus.healthy 
                      ? 'text-green-700' 
                      : 'text-red-700'
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
                <div class="text-sm font-semibold text-blue-900">
                  {{ backupStatus.amount }}
                </div>
                <div 
                  :class="[
                    'h-2 flex-1 rounded-full',
                    backupStatus.amount > 0 
                      ? 'bg-green-200' 
                      : 'bg-gray-200'
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
                          : 'bg-blue-500'
                    ]"
                    :style="{ width: `${Math.min(backupStatus.amount * 10, 100)}%` }"
                  ></div>
                </div>
              </div>
            </td>

            <!-- Newest Backup -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm text-gray-700">
                  {{ backupStatus.newest }}
                </span>
              </div>
            </td>

            <!-- Used Storage -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ backupStatus.usedStorage }}
                  </div>
                  <div class="h-1.5 w-20 rounded-full bg-gray-200 overflow-hidden">
                    <div 
                      class="h-full bg-blue-600 transition-all duration-500"
                      :style="{ width: getStoragePercentage(backupStatus.usedStorage) }"
                    ></div>
                  </div>
                </div>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="backupStatuses.length === 0">
            <td colspan="6" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.backups.status.no_status_data') }}
                  </h3>
                  <p class="mt-1 text-sm text-gray-500">
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
    <div v-if="backupStatuses.length > 0" class="border-t border-gray-200 px-6 py-4 bg-gray-50">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-green-500"></div>
            <span class="text-xs text-gray-600">
              {{ healthyCount }} {{ $t('gestlab.general.labels.backups.status.healthy_systems') }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <div class="h-3 w-3 rounded-full bg-red-500"></div>
            <span class="text-xs text-gray-600">
              {{ unhealthyCount }} {{ $t('gestlab.general.labels.backups.status.requires_attention') }}
            </span>
          </div>
        </div>
        <div class="text-xs text-gray-500">
          {{ $t('gestlab.general.labels.backups.status.last_updated') }}: {{ currentTime }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { trans } from 'laravel-vue-i18n';

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
  // Update time every minute
  const interval = setInterval(updateTime, 60000);
  
  onUnmounted(() => {
    clearInterval(interval);
  });
});
</script>

<style scoped>
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

/* Smooth hover transitions */
tbody tr {
  transition: background-color 0.2s ease;
}

/* Progress bar animation */
@keyframes fillProgress {
  from {
    width: 0%;
  }
}

tbody tr td:last-child div div:last-child div {
  animation: fillProgress 1s ease-out;
}
</style>