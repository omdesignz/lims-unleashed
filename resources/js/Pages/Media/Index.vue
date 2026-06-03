<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import {pickBy} from 'lodash';
import pagination from "@/Components/media-pagination.vue";
import selectAction from '@/Components/select-action.vue';
import emptyState from '@/Components/empty-state.vue';
import { SquaresPlusIcon } from '@heroicons/vue/24/outline'
import { usePermission } from '@/Composables/usePermissions'

const { hasRole, hasPermission } = usePermission();

const props = defineProps({
    record: Object,
    fields: Array,
    fileTypes: Array,
    months: Array,
    query: Object,
    model: String,
});

defineOptions({
  layout: Layout
});

const query = reactive({
    term: props?.query?.term,
    fileType: props?.query?.fileType,
    month: props?.query?.month,
});

function filter() {
    router.get(route('media.index'), pickBy(query), {
    preserveState: true
    });
}

const toggleSelectAll = (e) => {
      props.record.data.forEach(record => record.selected = e.target.checked);
}

const allFileTypes = computed(() => {
    return [
        {value: null, label: trans('gestlab.general.labels.files.any_type')},
        ...props.fileTypes,
      ];
});

const allMonths = computed(() => {
    return [
        {value: null, label: trans('gestlab.general.labels.files.any_date')},
        ...props.months,
      ];
});

const actionId = ref(null);


const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})


let actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text'
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete'
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore'
  },
];


const showDeleteConfirmation = ref(false);


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':

      router.get(route('media.destroy'), {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
        }
      });

    break;  

    case 'restore':
        router.get(`/media/restore`, {
          recordIds: recordIds
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
  }
}  
</script>
<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.files.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />

<div class="mx-auto max-w-7xl">
      <section class="flex flex-col p-4 mb-4 space-y-4 bg-white shadow sm:rounded lg:flex-row lg:items-center lg:justify-between lg:space-y-0 lg:p-2">
        <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-2">

          <select v-model="query.fileType" aria-label="Media type" id="type" class="pr-10 pl-3 w-full h-11 rounded border-gray-300 shadow-sm lg:h-9 lg:text-sm sm:w-44 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <option v-for="type in allFileTypes" :value="type.value"> {{ type.label }} </option>
          </select>
 
          <select v-model="query.month" aria-label="Media date" id="date" class="pr-10 pl-3 w-full h-11 rounded border-gray-300 shadow-sm lg:h-9 lg:text-sm sm:w-44 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <option v-for="month in allMonths" :value="month.value">{{ month.label }}</option>
          </select>
 
          <button @click="filter()" type="button" class="inline-flex items-center px-4 h-11 font-medium text-gray-700 bg-white rounded border border-gray-300 shadow-sm lg:h-9 lg:text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            {{ $t('gestlab.filter.apply') }}
          </button>

          <button v-if="hasPermission('add_' + props.model)" type="button" @click="router.get(route('media.create'))" class="group mt-1 ml-4 -ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50 hover:bg-blue-900 hover:text-white focus:outline-none">
          <SquaresPlusIcon class="-ml-1 mr-2 h-5 w-5 text-gray-900 group-hover:text-white group-hover:animate-bounce" aria-hidden="true" />
          {{ $t('gestlab.general.buttons.new_record') }}
      </button>
        </div>
 
        <div class="flex flex-col">
          <label for="search" class="text-sm font-medium text-gray-700 sr-only">Search</label>
          <input v-model="query.term" @keydown.enter="filter()" type="search" id="search" class="w-full h-11 rounded border-gray-300 shadow-sm lg:h-9 lg:text-sm lg:w-64 focus:ring-blue-500 focus:border-blue-500" :placeholder="$t('gestlab.general.search_input_placeholder')" autocomplete="off"/>
        </div>
      </section>
 
      <section class="flex flex-col mb-4 lg:flex-row lg:justify-between">
        <div class="hidden space-x-2 lg:flex -ml-4">
        <select-action 
            :record-ids="props.record.data.filter(record => record.selected).map(record => record.id)" 
            :actions="actions" 
            @execute="(e) => {
                actionId = e;
                showDeleteConfirmation = true
            }"
        />

        </div>

        <pagination :pagination="props.record.meta"></pagination>
 
      </section>
 
      <section class="mb-4">
        <table class="min-w-full bg-white shadow table-fixed sm:rounded">
          <thead>
          <tr class="border-b border-gray-200">
            <th class="px-2 w-10 text-center">
              <input type="checkbox" @change="toggleSelectAll" class="w-6 h-6 text-blue-600 rounded-full border-gray-300 lg:w-4 lg:h-4 focus:ring-blue-500">
            </th>
            <th class="text-left">
              <Link
                  href="#"
                  class="flex relative z-10 items-center p-2 space-x-2 font-normal text-blue-600 group lg:text-sm focus:outline-none focus:ring-2 focus:ring-opacity-50 focus:ring-blue-500">
                  {{ $t('gestlab.general.labels.files.file') }}
              </Link>
            </th>
            <th class="hidden w-48 text-left lg:table-cell">
              <span class="inline-block p-2 font-normal text-blue-600 lg:text-sm">{{ $t('gestlab.general.labels.files.author_id') }}</span>
            </th>
            <th class="hidden w-28 text-left lg:table-cell">
              <Link
                  href="#"
                  class="flex relative z-10 items-center p-2 space-x-2 font-normal text-blue-600 group lg:text-sm focus:outline-none focus:ring-2 focus:ring-opacity-50 focus:ring-blue-500">
                  {{ $t('gestlab.general.labels.files.created_at') }}
              </Link>
            </th>
          </tr>
          </thead>
 
          <tbody class="divide-y divide-gray-100">
          <tr class="align-top group" v-for="(item, index) in props.record.data" :key="item.id">
            <td class="p-2 w-10 text-center">
              <input type="checkbox" v-model="item.selected" class="w-6 h-6 text-blue-600 rounded-full border-gray-300 lg:w-4 lg:h-4 focus:ring-blue-500">
            </td>
            <td class="p-2 text-left">
              <div class="flex space-x-4">
                <div class="overflow-hidden flex-shrink-0 w-12 h-12 bg-gray-100 rounded lg:w-16 lg:h-16">
                  <img :src="item.preview_url" class="object-cover">
                </div>
                <div>
                  <Link href="#" class="text-sm font-semibold text-blue-600 break-all rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ item.name }}
                  </Link>
                  <p class="text-xs text-gray-500 break-all">{{ item.file_name }}</p>
 
                  <div class="flex items-center mt-2 space-x-2 lg:invisible group-hover:visible">
                    <Link href="#" class="text-xs text-blue-600 rounded hover:text-blue-900 transform transition-all duration-200 hover:scale-150 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </Link>
                    <span class="text-xs text-gray-300">|</span>
                    <Link :href="item.links.delete_path" class="text-xs text-blue-600 rounded hover:text-red-600 transform transition-all duration-200 hover:scale-150 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </Link>
                    <span class="text-xs text-gray-300">|</span>
                    <a :href="item.url" target="_blank" class="text-xs text-blue-600 rounded hover:text-blue-900 transform transition-all duration-200 hover:scale-150 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </a>
                  </div>
                </div>
              </div>
            </td>
            <td class="hidden p-2 text-left lg:table-cell">
              <a href="#" class="text-blue-600 rounded lg:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                {{ item.author.name }}
              </a>
            </td>
            <td class="hidden p-2 text-left lg:table-cell">
              <span class="text-gray-600 lg:text-sm">{{ item.created_at }}</span>
            </td>
          </tr>
 
          <tr class="align-top" v-if="!props.record.data.length">
            <td colspan="4" class="p-2 text-sm text-gray-700">
                <empty-state class="pb-5" @create-record="() => router.get(route('media.create'))" name="Sem registros para apresentar." description="Comece por adicionar um novo clicando no botão abaixo." />
            </td>
          </tr>
          </tbody>
        </table>
      </section>
 
      <section class="flex flex-col mb-4 lg:flex-row lg:justify-between">
        <div class="hidden space-x-2 lg:flex -ml-4">
        <select-action 
            :record-ids="props.record.data.filter(record => record.selected).map(record => record.id)" 
            :actions="actions" 
            @execute="(e) => {
                actionId = e;
                showDeleteConfirmation = true
            }"
        />
        </div>

        <pagination :pagination="props.record.meta"></pagination>

      </section>
    </div>
</template>
