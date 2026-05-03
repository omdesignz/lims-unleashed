<script setup>
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { trans } from 'laravel-vue-i18n';


const props = defineProps({
    backupStatuses: { required: true, type: Array },
});
</script>

<template>
    <div class="flex flex-col">
      <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
          <div class="shadow overflow-hidden border-b border-t border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.name') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.disk') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.health') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.amount_of_backups') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.newest_backup') }}
                        </th>
                        <th class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900">
                            {{ $t('gestlab.general.labels.backups.status.used_storage') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="backupStatus in backupStatuses" :key="backupStatus.disk">
                        <td class="px-6 py-4 whitespace-nowrap">{{ backupStatus.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ backupStatus.disk }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            
                            <CheckCircleIcon class="w-4 h-4 text-green-500" v-if="backupStatus.healthy" />
                            <XCircleIcon class="w-4 h-4 text-red-500" v-else />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ backupStatus.amount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ backupStatus.newest }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ backupStatus.usedStorage }}</td>
                    </tr>
                </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
</template>