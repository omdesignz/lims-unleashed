<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- Header Card -->
    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/80">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
            <CircleStackIcon class="h-7 w-7 text-primary-700 dark:text-primary-300" />
            {{ $t('gestlab.menu.backups') }}
          </h1>
          <p class="mt-2 text-slate-600 dark:text-slate-400">
            {{ $t('gestlab.general.labels.backups.page_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-800 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-400/20">
            <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
            {{ $t('gestlab.general.labels.backups.system_running') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Section -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
      <!-- Left Column (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Status Section -->
        <div class="space-y-4">
          <!-- Gradient Header -->
          <backup-statuses-list
            :backup-statuses="backupStatuses"
            class="space-y-4"
          />
        </div>

        <!-- Backups List Section -->
        <div class="space-y-4">
          <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/80 sm:flex-row sm:items-center sm:justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-slate-100">
                <CircleStackIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                {{ $t('gestlab.general.labels.backups.stored_backups') }}
                <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                  ({{ activeDiskBackups?.length || 0 }} {{ $t('gestlab.general.labels.backups.items') }})
                </span>
              </h2>
              
              <!-- Disk Selector -->
              <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                  {{ $t('gestlab.general.labels.backups.storage_disk') }}:
                </span>
                <select 
                  v-model="activeDisk"
                  @change="updateActiveDiskBackups"
                  class="rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                >
                  <option 
                    v-for="disk in disks" 
                    :key="disk" 
                    :value="disk"
                  >
                    {{ disk }}
                  </option>
                </select>
              </div>
          </div>

            <backups
              v-if="activeDisk"
              :disks="disks"
              :backups="activeDiskBackups"
              :active-disk.sync="activeDisk"
              @delete="deleteBackup"
              @setModalVisibility="setModalVisibility"
              class="space-y-4"
            />
            
            <!-- Empty State -->
            <div 
              v-if="activeDiskBackups?.length === 0" 
              class="rounded-3xl border-2 border-dashed border-slate-300 p-12 text-center dark:border-slate-700"
            >
              <CircleStackIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
              <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.backups.no_backups_found') }}
              </h3>
              <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.backups.create_your_first_backup') }}
              </p>
            </div>
        </div>
      </div>

      <!-- Right Column (1/3 width) -->
      <div class="space-y-6">
        <!-- Actions Card -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/80">
          <h3 class="mb-6 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-slate-100">
            <CircleStackIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            {{ $t('gestlab.general.labels.backups.quick_actions') }}
          </h3>
          <div class="space-y-4">
            <!-- Full Backup Button -->
            <button 
              @click="createBackup"
              :disabled="form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-3 rounded-lg px-4 py-3.5 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing
                  ? 'bg-slate-200 text-slate-500 cursor-not-allowed dark:bg-slate-800 dark:text-slate-500'
                  : 'bg-gradient-to-r from-primary-900 to-primary-700 text-white hover:from-primary-800 hover:to-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950'
              ]"
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
                  class="inline-flex w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900 dark:focus:ring-offset-slate-950"
                >
                  <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-200' : 'text-slate-900 dark:text-slate-100',
                        'group flex w-full items-center gap-3 px-4 py-3 text-sm hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors duration-200'
                      ]"
                    >
                      <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/10">
                        <svg class="h-4 w-4 text-primary-800 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                      </div>
                      <div class="text-left">
                        <div class="font-medium">{{ $t('gestlab.general.labels.backups.create_partial_backup_db') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.backups.database_only') }}</div>
                      </div>
                    </button>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <button 
                      @click="createPartialBackup('only-files')"
                      :class="[
                        active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-200' : 'text-slate-900 dark:text-slate-100',
                        'group flex w-full items-center gap-3 px-4 py-3 text-sm hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors duration-200'
                      ]"
                    >
                      <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/10">
                        <svg class="h-4 w-4 text-primary-800 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                      <div class="text-left">
                        <div class="font-medium">{{ $t('gestlab.general.labels.backups.create_partial_backup_files') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.backups.files_only') }}</div>
                      </div>
                    </button>
                  </MenuItem>
                </div>
              </template>
            </dropdown>

            <!-- Status Info -->
            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
              <h4 class="mb-3 text-sm font-medium text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.backups.system_status') }}
              </h4>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.backups.active_disk') }}</span>
                  <span class="font-semibold text-primary-700 dark:text-primary-300">{{ activeDisk || '-' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.backups.total_backups') }}</span>
                  <span class="font-semibold text-primary-700 dark:text-primary-300">{{ activeDiskBackups?.length || 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.backups.last_updated') }}</span>
                  <span class="text-slate-500 dark:text-slate-400">{{ new Date().toLocaleTimeString() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Information Card -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/80">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-slate-100">
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
            <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
              <p>{{ $t('gestlab.general.labels.backups.backup_tips') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Dialog -->
    <confirmDialog
      :show="showDeleteConfirmation"
      :title="confirmationDialogTitle"
      :message="confirmationDialogDescription"
      @cancel="showDeleteConfirmation = false"
      @confirm="confirmAction"
    />
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { MenuItem } from '@headlessui/vue'
import { ref, computed, onBeforeMount, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import axios from "axios";
import backups from '@/Components/backups.vue'
import backupStatusesList from '@/Components/backup-statuses-list.vue'
import dropdown from '@/Components/dropdown.vue'
import { CircleStackIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    record: Object,
});

defineOptions({
  layout: Layout
});

const activeDisk = ref('local');
const activeDiskBackups = ref([]);
const backupStatuses = ref([]);
const initialLoading = ref(true);
const modalVisibility = ref(false);
const loading = ref(true);

const disks = computed(() => {
  return backupStatuses?.value?.map(backupStatus => backupStatus.disk);
});

let form = useForm({
    option: '',
});

let deleteForm = useForm({});

const actionId = ref(null);

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})

const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})

const showDeleteConfirmation = ref(false);

const confirmAction = () => {
  executeAction(actionId.value);
}

const poller = ref(null);

onBeforeMount(() => {
  if (poller.value) {
    window.clearInterval(poller.value);
  }
});

onMounted(() => {
  updateBackupStatuses();
  updateActiveDiskBackups();
});

const updateBackupStatuses = async () => {
  const data = await axios.get(route('systembackups.statuses'));
  backupStatuses.value = data.data;

  if (!activeDisk.value && backupStatuses.value.length > 0) {
    activeDisk.value = backupStatuses.value[0].disk;
  }
}

const updateActiveDiskBackups = () => {
  if (!activeDisk?.value) {
    return;
  }

  return axios.get(route('systembackups.index'), { params: { disk: activeDisk.value } }).then(backups => {
    activeDiskBackups.value = backups.data;
  });
}

const createBackup = () => {
  form.post(route('systembackups.create'), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset()
    },
  });
}

const createPartialBackup = (option) => {
  form.post(route('systembackups.create', { option: option}), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset()
    },
  });
}

const deleteBackup = (e) => {
  form.delete(route('systembackups.destroy', { disk: e.disk, path: e.path}), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      form.reset()
    },
  });
}

const startPolling = () => {
  if (true) {
    poller.value = window.setInterval(() => {
      if (true) {
        updateBackupStatuses();
        updateActiveDiskBackups();
      }
    }, 5 * 1000);
  }
}
</script>
