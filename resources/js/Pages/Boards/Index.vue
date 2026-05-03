<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, watch, reactive } from "vue";
import debounce from 'lodash/debounce'
import { useForm, router, usePage } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { MagnifyingGlassCircleIcon, PencilIcon, SquaresPlusIcon, TrashIcon, RectangleStackIcon, SparklesIcon } from "@heroicons/vue/24/outline";
import Pagination from '@/Components/pagination.vue'
import emptyState from '@/Components/empty-state.vue'
import { usePermission } from '@/Composables/usePermissions'
import { ColorPicker } from "vue3-colorpicker";
import IconPicker from "@/Components/icon-picker.vue";
import * as OutlinedIcons from '@heroicons/vue/24/outline'


const { hasRole, hasPermission } = usePermission();

const openIconPicker = ref(false);

const page = usePage();

const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

let form = useForm({
    name: '',
    description: '',
    bgcolor: '',
    iconcolor: '',
    icon: '',
});

const query = reactive({
  search: props.query?.search,
  filter: props.query?.filter,
  page: null
});

watch(query, debounce( function(value) {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300));

const actionId = ref(null);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.title');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})


const openslideover = ref(false);

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

const close = () => {
    openslideover.value = false;
    form.clearErrors();
    form.reset();
}

const showDeleteConfirmation = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.name = data.name;
    form.description = data.description;
    form.bgcolor = data.bgcolor;
    form.iconcolor = data.iconcolor;
    form.icon = data.icon;
    
}

let submit = () => {

    if(!form.id) {
      form.post(route('boards.store'), {
          preserveScroll: true,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.put(route('boards.update',{board: form.id}), {
          preserveScroll: true,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    }
    
  }


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':
      router.get(route('boards.destroy'), {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(route('boards.restore'), {
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
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-6 shadow-xl shadow-slate-900/10 dark:border-slate-800">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-100/90">
            <SparklesIcon class="h-3.5 w-3.5" />
            {{ $t('gestlab.general.labels.kanban.page_title') }}
          </span>
          <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">
            {{ $t('gestlab.general.labels.kanban.page_title') }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
            {{ $t('gestlab.general.labels.kanban.page_description') }}
          </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
              <div class="text-xs font-medium uppercase tracking-wide text-slate-300">
                {{ $t('gestlab.general.labels.records_found') }}
              </div>
              <div class="mt-1 text-2xl font-semibold text-white">
                {{ props.record.meta.total || 0 }}
              </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
              <div class="text-xs font-medium uppercase tracking-wide text-slate-300">
                {{ $t('gestlab.general.labels.kanban.boards') }}
              </div>
              <div class="mt-1 flex items-center gap-2 text-2xl font-semibold text-white">
                <RectangleStackIcon class="h-5 w-5 text-sky-300" />
                {{ props.record.data.length }}
              </div>
            </div>
          </div>
          <span v-if="hasPermission('add_' + props.model)" class="inline-flex items-center gap-2">
            <button 
              @click="openslideover = true"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-slate-900"
            >
              <SquaresPlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.new_record') }}
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- SEARCH SECTION -->
    <div class="rounded-[1.75rem] border border-slate-200 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
      <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <MagnifyingGlassCircleIcon class="h-5 w-5 text-gray-400" />
        </div>
        <input 
          v-model="query.search"
          type="search"
          :placeholder="$t('gestlab.general.search_input_placeholder')"
          class="block w-full rounded-lg border-0 py-3 pl-10 pr-4 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
        />
      </div>
    </div>

    <!-- BOARDS GRID -->
    <div v-if="props.record.data.length" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
      <div 
        v-for="(record, index) in props.record.data"
        :key="record.id"
        class="group relative overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-sky-300 hover:shadow-xl hover:shadow-sky-100 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-sky-700 dark:hover:shadow-sky-950/20"
        v-motion
        :initial="{ opacity: 0, y: 20 }"
        :enter="{ opacity: 1, y: 0 }"
        :delay="index * 50"
      >
        <!-- BOARD HEADER -->
        <div 
          class="h-4"
          :style="{ 'background': record.bgcolor }"
        ></div>
        
        <!-- BOARD CONTENT -->
        <div class="p-5 sm:p-6">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div 
                class="flex h-11 w-11 items-center justify-center rounded-2xl ring-1 ring-black/5"
                :style="{ 'background': record.bgcolor, 'color': record.iconcolor }"
              >
                <component 
                  :is="OutlinedIcons[record.icon]"
                  class="h-6 w-6"
                />
              </div>
              <div>
                <Link 
                  :href="route('boards.show', {board: record.id})"
                  class="text-base font-semibold text-slate-900 transition-colors duration-200 hover:text-sky-700 dark:text-white dark:hover:text-sky-300"
                >
                  {{ record.name }}
                </Link>
                <p class="mt-1 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ record.lists_count }} {{ $t('gestlab.general.labels.kanban.lists') }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <button 
                @click="openSlideoverWithData(record)"
                type="button"
                class="rounded-xl p-1.5 text-slate-400 transition-colors duration-200 hover:bg-sky-50 hover:text-sky-700 dark:hover:bg-slate-800 dark:hover:text-sky-300"
                :title="$t('gestlab.general.buttons.edit')"
              >
                <PencilIcon class="h-4 w-4" />
              </button>
              <Link 
                :href="record.links.delete_path"
                as="button"
                method="delete"
                class="rounded-xl p-1.5 text-slate-400 transition-colors duration-200 hover:bg-red-50 hover:text-red-600 dark:hover:bg-slate-800"
                :title="$t('gestlab.general.buttons.delete')"
              >
                <TrashIcon class="h-4 w-4" />
              </Link>
            </div>
          </div>
          
          <p v-if="record.description" class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ record.description }}
          </p>
          <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            <span>{{ $t('gestlab.general.labels.created_at') }}</span>
            <span>{{ record.created_at || '—' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- EMPTY STATE -->
    <div v-else class="rounded-[1.75rem] border border-dashed border-slate-300 bg-white/90 p-12 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
      <SquaresPlusIcon class="mx-auto h-12 w-12 text-gray-300" />
      <h3 class="mt-4 text-sm font-semibold text-gray-900">
        {{ $t('gestlab.general.labels.kanban.empty_state.title') }}
      </h3>
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('gestlab.general.labels.kanban.empty_state.description') }}
      </p>
      <button 
        v-if="hasPermission('add_' + props.model)"
        @click="openslideover = true"
        type="button"
        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
      >
        <SquaresPlusIcon class="h-5 w-5" />
        {{ $t('gestlab.general.buttons.new_record') }}
      </button>
    </div>

    <!-- PAGINATION -->
    <Pagination 
      v-if="props.record.data.length && props.record.meta.last_page > 1"
      :links="props.record.meta.links"
      class="mt-6"
    />
  </div>

  <!-- CREATE/EDIT SLIDE OVER -->
  <slide-over 
    v-if="openslideover" 
    @close="close" 
    :title="slideOverTitle" 
    :description="slideOverDescription"
  >
    <template #content>
      <div class="space-y-6 py-6">
        <!-- NAME FIELD -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.kanban.name') }}
            <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.name"
            type="text"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
            :class="{ 'border-red-300': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-xs text-red-600">
            {{ form.errors.name }}
          </p>
        </div>

        <!-- DESCRIPTION FIELD -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.kanban.description') }}
          </label>
          <textarea 
            v-model="form.description"
            rows="3"
            class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
            :class="{ 'border-red-300': form.errors.description }"
          ></textarea>
          <p v-if="form.errors.description" class="text-xs text-red-600">
            {{ form.errors.description }}
          </p>
        </div>

        <!-- COLOR PICKERS -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.kanban.bgcolor') }}
            </label>
            <color-picker 
              v-model:pure-color="form.bgcolor"
              format="hex"
              shape="circle"
              lang="En"
              class="w-full"
            />
            <p v-if="form.errors.bgcolor" class="text-xs text-red-600">
              {{ form.errors.bgcolor }}
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.kanban.iconcolor') }}
            </label>
            <color-picker 
              v-model:pure-color="form.iconcolor"
              format="hex"
              shape="circle"
              lang="En"
              class="w-full"
            />
            <p v-if="form.errors.iconcolor" class="text-xs text-red-600">
              {{ form.errors.iconcolor }}
            </p>
          </div>
        </div>

        <!-- ICON PICKER -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.kanban.icon') }}
          </label>
          <div class="flex items-center gap-3">
            <button 
              @click="openIconPicker = true"
              type="button"
              class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 hover:border-blue-900 transition-colors duration-200"
              :style="{ 'background': form.bgcolor, 'color': form.iconcolor }"
            >
              <component 
                :is="OutlinedIcons[form.icon || 'LightBulbIcon']"
                class="h-5 w-5"
              />
            </button>
            <span class="text-sm text-gray-600">
              {{ form.icon || 'Select an icon' }}
            </span>
          </div>
          <p v-if="form.errors.icon" class="text-xs text-red-600">
            {{ form.errors.icon }}
          </p>
        </div>
      </div>
    </template>

    <template #action_buttons>
      <div class="flex justify-end gap-3">
        <button 
          type="button"
          @click="close"
          class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-200"
        >
          {{ $t('gestlab.general.buttons.cancel') }}
        </button>
        <button 
          @click="submit"
          :disabled="form.processing"
          :class="[
            'rounded-lg px-4 py-2.5 text-sm font-semibold transition-all duration-200',
            form.processing 
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 shadow-sm'
          ]"
        >
          {{ form.processing ? $t('gestlab.general.buttons.processing') : (form.id ? $t('gestlab.general.buttons.update') : $t('gestlab.general.buttons.submit')) }}
        </button>
      </div>
    </template>
  </slide-over>

  <icon-picker 
    v-if="openIconPicker"
    @picker-closed="openIconPicker = false"
    v-model="form.icon"
    @icon-selected="(e) => form.icon = e"
  />

  <!-- CONFIRMATION DIALOG -->
  <confirm-dialog 
    v-if="showDeleteConfirmation"
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="confirmAction"
    :title="confirmationDialogTitle"
    :description="confirmationDialogDescription"
    :confirm="$t('gestlab.general.buttons.yes')"
    :cancel="$t('gestlab.general.buttons.no')"
  />
</template>
