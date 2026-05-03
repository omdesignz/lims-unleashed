<template>
  <div class="space-y-8">
    <!-- Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="h-7 w-7 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            {{ $t('gestlab.general.labels.formulas.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.formulas.page_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ props.record.data.length }} {{ $t('gestlab.general.labels.formulas.items') }}
          </span>
          <Link 
            :href="route('formulas.create')" 
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <SquaresPlusIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.create') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div class="flex-1 max-w-xl">
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <MagnifyingGlassCircleIcon class="h-5 w-5 text-blue-900" />
            </div>
            <input 
              v-model="query.search"
              type="search" 
              :placeholder="$t('gestlab.general.labels.formulas.search_formulas')"
              class="block w-full rounded-lg border border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Formulas Grid -->
    <div v-if="props.record.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div 
        v-for="record in props.record.data" 
        :key="record.id" 
        class="group relative bg-white rounded-xl shadow-sm border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden hover:shadow-md"
      >
        <!-- Formula Card -->
        <div class="p-6">
          <!-- Formula Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100 transition-colors duration-200">
                <svg class="h-6 w-6 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-blue-900 transition-colors duration-200 line-clamp-1">
                  {{ record.name }}
                </h3>
                <p class="text-xs text-gray-500">
                  {{ record.code }}
                </p>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <Link 
                :href="record.links.edit_path"
                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200"
                :title="$t('gestlab.general.labels.formulas.edit')"
              >
                <PencilIcon class="h-4 w-4" />
              </Link>
              <Link 
                :href="record.links.delete_path"
                as="button"
                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors duration-200"
                :title="$t('gestlab.general.labels.formulas.delete')"
              >
                <TrashIcon class="h-4 w-4" />
              </Link>
            </div>
          </div>

          <!-- Formula Display -->
          <div class="mb-4">
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
              <FormulaDisplay :formula="record.expression" />
            </div>
          </div>

          <!-- Formula Details -->
          <div class="space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-500">{{ $t('gestlab.general.labels.formulas.category') }}</span>
              <span class="font-medium text-blue-900">{{ $t("gestlab.general.labels.formulas.categories." + record.category) }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-500">{{ $t('gestlab.general.labels.formulas.variables') }}</span>
              <span class="font-medium text-blue-900">
                {{ record.variables_count || 0 }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-500">{{ $t('gestlab.general.labels.formulas.created') }}</span>
              <span class="text-gray-600">{{ formatDate(record.created_at) }}</span>
            </div>
          </div>

          <!-- View Button -->
          <div class="mt-4 pt-4 border-t border-gray-200">
            <!-- <Link 
              :href="route('boards.show', {board: record.id})"
              class="flex items-center justify-center gap-2 w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              {{ $t('gestlab.general.labels.formulas.view_details') }}
            </Link> -->
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
      <div class="flex flex-col items-center gap-4">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
          <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-gray-900">
            {{ $t('gestlab.general.labels.formulas.no_formulas_found') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.general.labels.formulas.create_first_formula') }}
          </p>
        </div>
        <Link 
          :href="route('formulas.create')" 
          class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
        >
          <SquaresPlusIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.create') }}
        </Link>
      </div>
    </div>

    <!-- Pagination -->
    <Pagination 
      v-if="props.record.data.length" 
      :links="props.record.meta.links" 
      :from="props.record.meta.from" 
      :to="props.record.meta.to" 
      :total="props.record.meta.total" 
      :current_page="props.record.meta.current_page" 
      :last_page="props.record.meta.last_page" 
      class="mt-6"
    />

    <!-- Calibration Tools Section (Optional) -->
    <!-- <div class="mt-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            {{ $t('gestlab.general.labels.formulas.calibration_tools') }}
          </h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link 
            href="/calibration/strength"
            class="group rounded-lg border border-gray-200 p-4 hover:border-blue-900 hover:shadow-sm transition-all duration-200"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100">
                <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                  {{ $t('gestlab.general.labels.formulas.strength_calibration') }}
                </h3>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.formulas.strength_calibration_desc') }}
                </p>
              </div>
            </div>
          </Link>
          
          <Link 
            href="/calibration/scale"
            class="group rounded-lg border border-gray-200 p-4 hover:border-blue-900 hover:shadow-sm transition-all duration-200"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100">
                <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                  {{ $t('gestlab.general.labels.formulas.scale_calibration') }}
                </h3>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.formulas.scale_calibration_desc') }}
                </p>
              </div>
            </div>
          </Link>
          
          <Link 
            href="/calibration/uncertainty"
            class="group rounded-lg border border-gray-200 p-4 hover:border-blue-900 hover:shadow-sm transition-all duration-200"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100">
                <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                  {{ $t('gestlab.general.labels.formulas.uncertainty_calculator') }}
                </h3>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.formulas.uncertainty_calculator_desc') }}
                </p>
              </div>
            </div>
          </Link>
          
          <Link 
            href="/calibration/eccentricity"
            class="group rounded-lg border border-gray-200 p-4 hover:border-blue-900 hover:shadow-sm transition-all duration-200"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100">
                <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                  {{ $t('gestlab.general.labels.formulas.eccentricity_diagram') }}
                </h3>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.formulas.eccentricity_diagram_desc') }}
                </p>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </div> -->
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, watch, reactive } from "vue";
import debounce from 'lodash/debounce'
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { MagnifyingGlassCircleIcon, PencilIcon, SquaresPlusIcon, TrashIcon } from "@heroicons/vue/24/outline";
import Pagination from '@/Components/pagination.vue'
import emptyState from '@/Components/empty-state.vue'
import { usePermission } from '@/Composables/usePermissions'
import { ColorPicker } from "vue3-colorpicker";
import IconPicker from "@/Components/icon-picker.vue";
import * as OutlinedIcons from '@heroicons/vue/24/outline'
import FormulaDisplay from "@/Components/formula-display.vue";
import { Link, usePage } from '@inertiajs/vue3'

const { hasRole, hasPermission } = usePermission();

const openIconPicker = ref(false);

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

const page = usePage();

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

// Helper function to format dates
const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(date);
  } catch (error) {
    return dateString;
  }
};
</script>

<style scoped>
.line-clamp-1 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}

/* Smooth transitions */
.group {
  transition: all 0.2s ease-in-out;
}

/* Custom scrollbar for formula cards */
.grid {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
}

.grid::-webkit-scrollbar {
  height: 8px;
}

.grid::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.grid::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.grid::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>