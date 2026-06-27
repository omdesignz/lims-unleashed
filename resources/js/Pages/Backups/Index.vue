<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      :eyebrow="$t('gestlab.general.labels.backups.system_status')"
      :title="$t('gestlab.menu.backups')"
      :description="$t('gestlab.general.labels.backups.page_description')"
    >
      <template #actions>
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-extrabold text-emerald-800 dark:bg-emerald-500/12 dark:text-emerald-200">
          <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
          {{ $t('gestlab.general.labels.backups.system_running') }}
        </span>
      </template>
    </ModuleHero>

    <div
      v-if="loadError"
      role="alert"
      class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200"
    >
      {{ $t('gestlab.general.labels.backups.load_error') }}
    </div>

    <!-- Main Content Section -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
      <!-- Left Column (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <backup-statuses-list :backup-statuses="backupStatuses" />

        <!-- Backups List Section -->
        <backups
          v-if="activeDisk"
          v-model:active-disk="activeDisk"
          :disks="disks"
          :backups="activeDiskBackups"
          @delete="deleteBackup"
        />
      </div>

      <!-- Right Column (1/3 width) -->
      <div class="space-y-6">
        <!-- Actions Card -->
        <div class="ds-panel p-5 sm:p-6">
          <h3 class="mb-6 flex items-center gap-2 text-lg font-extrabold text-[color:var(--ds-text)]">
            <CircleStackIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)] dark:text-[rgb(var(--accent-100-rgb)/1)]" />
            {{ $t('gestlab.general.labels.backups.quick_actions') }}
          </h3>
          <div class="space-y-4">
            <!-- Full Backup Button -->
            <button 
              @click="createBackup"
              :disabled="form.processing"
              class="ds-button ds-button-primary w-full"
            >
              <CircleStackIcon class="h-5 w-5" />
              <span v-if="form.processing">{{ $t('gestlab.general.labels.backups.creating') }}...</span>
              <span v-else>{{ $t('gestlab.general.labels.backups.create_full_backup') }}</span>
            </button>

            <!-- Quick Actions Dropdown -->
            <dropdown class="w-full">
              <template #trigger>
                <button 
                  type="button"
                  class="ds-button ds-button-secondary w-full justify-between"
                >
                  <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-[color:var(--ds-text-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    {{ $t('gestlab.general.labels.backups.partial_backup_options') }}
                  </span>
                </button>
              </template>

              <template #options>
                <div class="py-1">
                  <MenuItem v-slot="{ active }">
                    <button 
                      @click="createPartialBackup('only-db')"
                      :class="[
                        active ? 'ds-option-active' : '',
                        'ds-option group flex w-full items-center gap-3 px-4 py-3 text-sm'
                      ]"
                    >
                      <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/10">
                        <svg class="h-4 w-4 text-primary-800 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                      </div>
                      <div class="text-left">
                        <div class="font-medium">{{ $t('gestlab.general.labels.backups.create_partial_backup_db') }}</div>
                        <div class="text-xs text-[color:var(--ds-text-muted)]">{{ $t('gestlab.general.labels.backups.database_only') }}</div>
                      </div>
                    </button>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <button 
                      @click="createPartialBackup('only-files')"
                      :class="[
                        active ? 'ds-option-active' : '',
                        'ds-option group flex w-full items-center gap-3 px-4 py-3 text-sm'
                      ]"
                    >
                      <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/10">
                        <svg class="h-4 w-4 text-primary-800 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      <div class="text-left">
                        <div class="font-medium">{{ $t('gestlab.general.labels.backups.create_partial_backup_files') }}</div>
                        <div class="text-xs text-[color:var(--ds-text-muted)]">{{ $t('gestlab.general.labels.backups.files_only') }}</div>
                      </div>
                    </button>
                  </MenuItem>
                </div>
              </template>
            </dropdown>

            <!-- Status Info -->
            <div class="mt-4 border-t border-[color:var(--ds-border)] pt-4">
              <h4 class="mb-3 text-sm font-extrabold text-[color:var(--ds-text)]">
                {{ $t('gestlab.general.labels.backups.system_status') }}
              </h4>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-[color:var(--ds-text-muted)]">{{ $t('gestlab.general.labels.backups.active_disk') }}</span>
                  <span class="font-semibold text-primary-700 dark:text-primary-300">{{ activeDisk || '-' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-[color:var(--ds-text-muted)]">{{ $t('gestlab.general.labels.backups.total_backups') }}</span>
                  <span class="font-semibold text-primary-700 dark:text-primary-300">{{ activeDiskBackups?.length || 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-[color:var(--ds-text-muted)]">{{ $t('gestlab.general.labels.backups.last_updated') }}</span>
                  <span class="text-[color:var(--ds-text-soft)]">{{ lastUpdated }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Information Card -->
        <div class="ds-panel p-5 sm:p-6">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-extrabold text-[color:var(--ds-text)]">
            <svg class="h-5 w-5 text-primary-700 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $t('gestlab.general.labels.backups.information') }}
          </h3>
          <div class="space-y-3">
            <div class="rounded-2xl bg-primary-50 p-3 dark:bg-primary-500/10">
              <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary-700 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <p class="text-sm text-primary-900 dark:text-primary-100">
                  {{ $t('gestlab.general.labels.backups.security_note') }}
                </p>
              </div>
            </div>
            <div class="space-y-2 text-sm text-[color:var(--ds-text-muted)]">
              <p>{{ $t('gestlab.general.labels.backups.backup_tips') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleHero from "@/Components/base/ModuleHero.vue";
import { MenuItem } from '@headlessui/vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import backups from '@/Components/backups.vue'
import backupStatusesList from '@/Components/backup-statuses-list.vue'
import dropdown from '@/Components/dropdown.vue'
import { CircleStackIcon } from "@heroicons/vue/24/outline";

defineProps({
    record: Object,
});

defineOptions({
  layout: Layout
});

const activeDisk = ref('');
const activeDiskBackups = ref([]);
const backupStatuses = ref([]);
const loadError = ref(false);
const lastUpdated = ref('-');

const disks = computed(() => {
  return backupStatuses?.value?.map(backupStatus => backupStatus.disk);
});

const form = useForm({
    option: '',
});

const poller = ref(null);

onBeforeUnmount(() => {
  if (poller.value) {
    window.clearInterval(poller.value);
  }
});

onMounted(async () => {
  await refreshBackupData();
  startPolling();
});

const updateBackupStatuses = async () => {
  const response = await axios.get(route('systembackups.statuses'));
  backupStatuses.value = Array.isArray(response.data) ? response.data : [];

  const availableDisks = backupStatuses.value.map(status => status.disk);

  if (!availableDisks.includes(activeDisk.value)) {
    activeDisk.value = availableDisks[0] || '';
  }
}

const updateActiveDiskBackups = async () => {
  if (!activeDisk?.value) {
    activeDiskBackups.value = [];

    return;
  }

  const response = await axios.get(route('systembackups.index'), {
    params: { disk: activeDisk.value },
  });

  activeDiskBackups.value = Array.isArray(response.data) ? response.data : [];
}

const refreshBackupData = async () => {
  try {
    await updateBackupStatuses();
    await updateActiveDiskBackups();
    loadError.value = false;
    lastUpdated.value = new Date().toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    loadError.value = true;
  }
}

watch(activeDisk, async (disk, previousDisk) => {
  if (!disk || !previousDisk || disk === previousDisk) {
    return;
  }

  try {
    await updateActiveDiskBackups();
    loadError.value = false;
  } catch {
    loadError.value = true;
  }
});

const createBackup = () => {
  form.post(route('systembackups.create'), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset();
      refreshBackupData();
    },
  });
}

const createPartialBackup = (option) => {
  form.post(route('systembackups.create', { option: option}), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset();
      refreshBackupData();
    },
  });
}

const deleteBackup = (e) => {
  form.delete(route('systembackups.destroy', { disk: e.disk, path: e.path}), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset();
      refreshBackupData();
    },
  });
}

const startPolling = () => {
  poller.value = window.setInterval(refreshBackupData, 30 * 1000);
}
</script>
