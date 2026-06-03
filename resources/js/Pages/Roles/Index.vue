<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { 
  ShieldCheckIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  PlusCircleIcon,
  DocumentTextIcon,
  TagIcon,
  UserGroupIcon,
  ArrowPathIcon,
  TrashIcon,
  PencilSquareIcon
} from "@heroicons/vue/24/outline";

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

// Reactive state
const openslideover = ref(false);
const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);
const actionId = ref(null);
const isLoading = ref(false);

// Form
let form = useForm({
    name: '',
    label: '',
    guard_name: '',
    permissions: [],
    id: null,
});

// Computed
const slideOverDescription = computed(() => {
  return !form.id 
    ? trans('gestlab.slideover.creating.description') 
    : trans('gestlab.slideover.updating.description');
});

const slideOverTitle = computed(() => {
  return !form.id 
    ? trans('gestlab.slideover.creating.title')
    : trans('gestlab.slideover.updating.title');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
});

const formStatus = computed(() => {
  if (!form.name && !form.label) {
    return {
      label: 'empty',
      color: 'bg-gray-100 text-gray-800',
      icon: DocumentTextIcon
    };
  } else if (form.isDirty && form.name && form.label) {
    return {
      label: 'complete',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else if (form.isDirty) {
    return {
      label: 'partial',
      color: 'bg-yellow-100 text-yellow-800',
      icon: ExclamationTriangleIcon
    };
  } else {
    return {
      label: 'unchanged',
      color: 'bg-blue-100 text-blue-800',
      icon: PencilSquareIcon
    };
  }
});

// Actions
let actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text',
    icon: null
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete',
    icon: TrashIcon,
    color: 'text-red-600'
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore',
    icon: ArrowPathIcon,
    color: 'text-blue-600'
  },
];

// Methods
const close = () => {
    openslideover.value = false;
    form.clearErrors();
    // form.reset();
};

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.name = data.name;
    form.label = data.label;
    form.guard_name = data.guard_name || 'web';
    form.permissions = data.permissions || [];
};

const submit = () => {
    if(!form.id) {
      form.post(route('roles.store'), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            form.reset();
            showDeleteConfirmationSlideover.value = false;
          },
          onError: () => {
            showDeleteConfirmationSlideover.value = false;
          }
      });
    } else {
      form.put(route('roles.update',{role: form.id}), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            form.reset();
            showDeleteConfirmationSlideover.value = false;
          },
          onError: () => {
            showDeleteConfirmationSlideover.value = false;
          }
      });
    }
};

const confirmAction = () => {
    executeAction(actionId.value);
};

const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  isLoading.value = true;
  
  switch (actionId) {
    case 'delete':
      router.get(route('roles.destroy'), {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
            isLoading.value = false;
        },
        onError: () => {
          isLoading.value = false;
        }
      });
      break;  

    case 'restore':
        router.get(route('roles.restore'), {
          recordIds: recordIds
        }, {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
                isLoading.value = false;
            },
            onError: () => {
              isLoading.value = false;
            }
        });
        break;
  }
};
</script>

<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ShieldCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.roles.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.roles.page_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ record.total }} {{ $t('gestlab.general.labels.roles.total') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2">
        <!-- RECORDS TABLE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <UserGroupIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.roles.roles_list') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ record.total }} {{ $t('gestlab.general.labels.roles.items') }})
                </span>
              </h2>
              <button 
                @click="openslideover = true"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.buttons.submit') }}
              </button>
            </div>
          </div>
          
          <!-- TABLE CONTENT -->
          <div class="px-6 py-4">
            <records-table 
              :record="props.record" 
              :model="props.model" 
              :abilities="props.abilities" 
              :fields="props.fields" 
              :slideOverEdit="props.slideOverEdit" 
              :query="props.query" 
              :actions="actions"
              @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" 
              @create-record="openslideover=true" 
              @slideover-on="openSlideoverWithData"
            />
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.quick_actions') }}
          </h3>
          <div class="space-y-3">
            <button 
              @click="openslideover = true"
              type="button"
              class="w-full inline-flex items-center justify-between rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <div class="flex items-center gap-2">
                <PlusCircleIcon class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.submit') }}
              </div>
              <kbd class="inline-flex items-center rounded border border-gray-200 px-2 font-sans text-xs text-gray-400">
                ⌘N
              </kbd>
            </button>
            <button 
              v-if="record.data.some(record => record.selected)"
              @click="actionId = 'delete'; showDeleteConfirmation = true;"
              type="button"
              class="w-full inline-flex items-center justify-between rounded-lg border border-red-300 px-4 py-3 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
            >
              <div class="flex items-center gap-2">
                <TrashIcon class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.delete') }}
              </div>
              <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                {{ record.data.filter(record => record.selected).length }}
              </span>
            </button>
            <button 
              v-if="record.data.some(record => record.selected)"
              @click="actionId = 'restore'; showDeleteConfirmation = true;"
              type="button"
              class="w-full inline-flex items-center justify-between rounded-lg border border-blue-300 px-4 py-3 text-sm font-medium text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
            >
              <div class="flex items-center gap-2">
                <ArrowPathIcon class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.restore') }}
              </div>
              <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                {{ record.data.filter(record => record.selected).length }}
              </span>
            </button>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.roles.total_roles') }}</span>
              <span class="text-sm font-semibold text-blue-900">{{ record.total }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.selected_roles') }}</span>
              <span :class="record.data.some(record => record.selected) ? 'text-blue-600' : 'text-gray-500'">
                {{ record.data.filter(record => record.selected).length }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.slideover_open') }}</span>
              <span :class="openslideover ? 'text-green-600' : 'text-red-600'">
                {{ openslideover ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.roles.action_in_progress') }}</span>
              <span :class="isLoading ? 'text-blue-600' : 'text-gray-500'">
                {{ isLoading ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
          </div>
        </div>

        <!-- HELP CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ShieldCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.roles.role_management') }}
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="h-5 w-5 text-blue-900 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-medium text-gray-900">{{ $t('gestlab.general.labels.roles.what_are_roles') }}</p>
                <p class="mt-1 text-gray-600">{{ $t('gestlab.general.labels.roles.roles_description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="h-5 w-5 text-yellow-500 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-medium text-gray-900">{{ $t('gestlab.general.labels.roles.permissions_warning') }}</p>
                <p class="mt-1 text-gray-600">{{ $t('gestlab.general.labels.roles.assign_permissions_carefully') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.roles.auto_save') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="openslideover = true"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <PlusCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.add_another_item') }}
        </button>
      </div>
    </div>
  </div>

  <!-- SLIDE OVER COMPONENT -->
  <slide-over 
    v-if="openslideover" 
    :class="commercialDocumentThemeClasses"
    @close="close" 
    :title="slideOverTitle" 
    :description="slideOverDescription"
  >
    <template #header>
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <ShieldCheckIcon class="h-6 w-6 text-blue-900" />
          <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ slideOverTitle }}</h2>
            <p class="text-sm text-gray-500">{{ slideOverDescription }}</p>
          </div>
        </div>
        <span :class="[
          'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
          formStatus.color
        ]">
          <component :is="formStatus.icon" class="h-3 w-3" />
          {{ $t(`gestlab.general.status.${formStatus.label}`) }}
        </span>
      </div>
    </template>

    <template #content>
        <div class="space-y-6 px-4 sm:px-6 py-6">
          <!-- NAME FIELD -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <TagIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.roles.name') }}
              <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="form.name" 
              type="text" 
              name="name" 
              id="name" 
              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
              :placeholder="$t('gestlab.general.labels.roles.placeholders.enter_role_name')"
            />
            <p v-if="form.errors.name" class="text-xs text-red-600">
              {{ form.errors.name }}
            </p>
            <p class="text-xs text-gray-500">
              {{ $t('gestlab.general.labels.roles.role_name_description') }}
            </p>
          </div>

          <!-- LABEL FIELD -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <DocumentTextIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.roles.label') }}
              <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="form.label" 
              type="text" 
              name="label" 
              id="label" 
              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
              :placeholder="$t('gestlab.general.labels.roles.placeholders.enter_role_label')"
            />
            <p v-if="form.errors.label" class="text-xs text-red-600">
              {{ form.errors.label }}
            </p>
            <p class="text-xs text-gray-500">
              {{ $t('gestlab.general.labels.roles.role_label_description') }}
            </p>
          </div>

          <!-- GUARD NAME (Optional) -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <ShieldCheckIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.roles.guard_name') }}
            </label>
            <div class="flex items-center gap-2">
              <input 
                v-model="form.guard_name" 
                type="text" 
                name="guard_name" 
                id="guard_name" 
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                placeholder="web"
              />
              <button 
                type="button"
                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                @click="form.guard_name = 'web'"
              >
                {{ $t('gestlab.general.buttons.default') }}
              </button>
            </div>
            <p v-if="form.errors.guard_name" class="text-xs text-red-600">
              {{ form.errors.guard_name }}
            </p>
            <p class="text-xs text-gray-500">
              {{ $t('gestlab.general.labels.roles.guard_name_description') }}
            </p>
          </div>
        </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
          <button 
            type="button" 
            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            @click="close"
          >
            {{ $t('gestlab.general.buttons.cancel') }}
          </button>
          <button 
            v-if="form.isDirty && form.name && form.label"
            @click="showDeleteConfirmationSlideover = true"
            :disabled="form.processing"
            type="button" 
            :class="[
              'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200',
              form.processing
                ? 'bg-gray-400 cursor-not-allowed'
                : 'bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
            ]"
          >
            <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
            <CheckCircleIcon v-else class="h-4 w-4" />
            {{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}
          </button>
          <button 
            v-else-if="form.isDirty"
            disabled
            type="button" 
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-500 cursor-not-allowed"
          >
            <ExclamationTriangleIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.roles.complete_fields') }}
          </button>
        </div>
    </template>
  </slide-over>

  <!-- BULK ACTION CONFIRMATION DIALOG -->
  <confirm-dialog 
    @canceled="showDeleteConfirmation = false" 
    @close="showDeleteConfirmation = false" 
    @confirmed="confirmAction" 
    v-if="showDeleteConfirmation" 
    :title="confirmationDialogTitle" 
    :description="confirmationDialogDescription" 
    :confirm="$t('gestlab.general.buttons.confirm')" 
    :cancel="$t('gestlab.general.buttons.cancel')"
  />

  <!-- CREATE/UPDATE CONFIRMATION DIALOG -->
  <confirm-dialog 
    size="sm:max-w-2xl" 
    alignment="sm:items-start" 
    @canceled="showDeleteConfirmationSlideover = false" 
    @close="showDeleteConfirmationSlideover = false" 
    @confirmed="submit" 
    v-if="showDeleteConfirmationSlideover" 
    :title="$t('gestlab.actions.confirmation_dialog_title.default')"
    :description="$t('gestlab.actions.confirmation_dialog_description.default')"
    :confirm="$t('gestlab.general.buttons.confirm')" 
    :cancel="$t('gestlab.general.buttons.cancel')"
  >
    <div class="mt-4 space-y-4">
      <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900">
        <CheckCircleIcon class="h-4 w-4" />
        <p>{{ $t('gestlab.general.labels.roles.role_summary') }}</p>
      </div>
      
      <div class="grid grid-cols-1 gap-6">
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.roles.role_details') }}
          </h4>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.name') }}</span>
              <span class="font-medium text-gray-900">{{ form.name }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.label') }}</span>
              <span class="font-medium text-gray-900">{{ form.label }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.guard_name') }}</span>
              <span class="font-medium text-blue-900">{{ form.guard_name || 'web' }}</span>
            </div>
          </div>
        </div>
        
        <div v-if="form.id">
          <h4 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.roles.permissions_info') }}
          </h4>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-sm text-gray-700">
              {{ $t('gestlab.general.labels.roles.permissions_will_remain') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </confirm-dialog>
</template>

<style scoped>
/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Loading animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Keyboard key styling */
kbd {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  background-color: #f9fafb;
  border-color: #e5e7eb;
}
</style>
