<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { MenuItem, TransitionRoot } from '@headlessui/vue'
import { ref, computed, onBeforeMount, onMounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
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

    if (!activeDisk) {
      activeDisk.value = backupStatuses?.value?.data[0].disk;
    }

}

const updateActiveDiskBackups = () => {
    if (!activeDisk?.value) {
        return;
    }

    return axios.get(route('systembackups.index'), { params: { disk: activeDisk.value } }).then(backups => {
        activeDiskBackups.value = backups;
    });
}

// updateBackupStatuses();
// updateActiveDiskBackups();

const createBackup = () => {
    console.log('creating backup in the background...');

    form.post(route('systembackups.create'), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            form.reset()
          },
      });

    // return axios.post(route('systembackups.create'));
}

const createPartialBackup = (option) => {
  
    console.log('creating backup in the background...' + ' (' + option + ')');

    form.post(route('systembackups.create', { option: option}), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            form.reset()
          },
      });
}

const deleteBackup = (e) => {

  console.log(e.disk);
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
<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.backups.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<Head :title="trans('gestlab.menu.backups')" />

<div class="flex mb-6 items-center justify-between">
    <Heading>
        
    </Heading>

    <div class="flex items-center justify-end space-x-2">
        <dropdown>
          <template #icon>
            <button type="button" class="group mt-1 ml-4 -ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50 hover:bg-blue-900 hover:text-white focus:outline-none">
                <CircleStackIcon class="-ml-1 mr-2 h-5 w-5 text-gray-900 group-hover:text-white group-hover:animate-bounce" aria-hidden="true" />
                {{ $t('gestlab.general.buttons.new_record') }}
            </button>
            <!-- <CircleStackIcon class="-ml-1 mr-2 h-5 w-5 text-gray-900 group-hover:text-white group-hover:animate-bounce" aria-hidden="true" /> -->
          </template>

          <template #options>
            <MenuItem v-slot="{ active, close }">
              <button @click="createBackup" :class="[active ? 'bg-blue-900 text-white' : 'text-gray-900', 'w-full text-left px-4 py-2 text-sm']">{{ $t('gestlab.general.labels.backups.create_full_backup') }}</button>
            </MenuItem>
            <MenuItem v-slot="{ active, close }">
              <button @click="createPartialBackup('only-db')" :class="[active ? 'bg-blue-900 text-white' : 'text-gray-900', 'w-full text-left px-4 py-2 text-sm']">{{ $t('gestlab.general.labels.backups.create_partial_backup_db') }}</button>
            </MenuItem>
            <MenuItem v-slot="{ active, close }">
              <button @click="createPartialBackup('only-files')" :class="[active ? 'bg-blue-900 text-white' : 'text-gray-900', 'w-full text-left px-4 py-2 text-sm']">{{ $t('gestlab.general.labels.backups.create_partial_backup_files') }}</button>
            </MenuItem>
          </template>
        </dropdown>
        
    </div>
</div>

<div class="overflow-hidden overflow-x-auto relative rounded-lg">
    <backup-statuses-list :backup-statuses="backupStatuses" />
    <br>
    
    <backups
    v-if="activeDisk"
    :disks="disks"
    :backups="activeDiskBackups?.data"
    :active-disk.sync="activeDisk"
    @delete="deleteBackup"
    @setModalVisibility="setModalVisibility"
/>
</div>
<hr>

</template>