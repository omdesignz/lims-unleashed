<script setup>
import { computed, ref } from 'vue'
import backupRow from '@/Components/backup-row.vue'
import modal from '@/Components/modal.vue'
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
    'delete'
]);

// onMounted(() => console.log('Hello from the backups component'));

const deletingBackup = ref(null);
const deleteModalOpen = ref(false);

const getDiscs = () => {
    return props?.disks?.map(val => ({ value: val, label: val }));
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

const confirmDelete = (backup) => {
    emit('setModalVisibility', false);
    deleteModalOpen.value = false;
    
    emit('delete', {
        disk: props?.activeDisk,
        path: deletingBackup?.value?.path,
    });
}

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.delete');
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.delete');
})
</script>

<template>
    <div>
        <div class="p-3 flex items-center"
            v-if="props?.disks?.length > 1">

            <select id="disk" name="disk" class="mt-1 md:w-1/5 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm rounded-md">
                <option :value="activeDisk" v-for="(disk, index) in getDiscs()" :key="index">{{ disk.label }}</option>
            </select>
        </div>

        <div class="overflow-hidden overflow-x-auto relative rounded-lg">

            <div class="flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-t border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.path') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.created_at') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.size') }}
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-0"></th>
                    </tr>
                </thead>
                <tbody>
                    <backup-row
                        v-for="(backup, recordIdx) in props?.backups"
                        v-bind="backup"
                        :disk="props?.activeDisk"
                        :deletable="props?.backups?.length > 1"
                        :deleting="
                            !deleteModalOpen && deletingBackup && backup?.path === deletingBackup?.path
                        "
                        :key="backup.id"
                        @delete="openDeleteModal(backup)"
                    />
                    <tr v-if="props?.backups?.length === 0">
                        <td class="px-6 py-3.5 text-center text-sm font-semibold text-gray-900" colspan="4">
                            ---
                        </td>
                    </tr>
                </tbody>
            </table>
                    </div>
                </div>
            </div>
        </div>
        </div>
        


        <confirm-dialog
            :show="deleteModalOpen"
            @close="closeDeleteModal"
            @confirmed="confirmDelete"
            :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não"
        ></confirm-dialog>
    </div>
</template>