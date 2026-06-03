<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import {throttle} from "lodash";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  ClipboardDocumentCheckIcon, 
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  TagIcon,
  UserGroupIcon,
  ArrowPathIcon,
  InformationCircleIcon
} from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import Fuse from 'fuse.js';
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object,
    permissions: Array
});

// Reactive state
const permInput = ref('');
const showDeleteConfirmation = ref(false);
const isLoading = ref(false);

// Initialize Fuse for search
const fuse = new Fuse(props?.permissions, {
  keys: ['label', 'name'],
  isCaseSensitive: false,
  threshold: 0.3,
  includeScore: true,
  includeMatches: true
});

// Form
const form = useForm({
    id: props.record?.id,
    name: props.record?.name,
    label: props.record?.label,
    guard_name: props.record?.guard_name || 'web',
    // FIX 1: Initialize form.permissions with an array of IDs
    permissions: props.record?.permissions?.map(p => p.value) || [],
});

// Computed
const results = computed(() => {
  if (permInput.value) {
    return fuse.search(permInput.value).map(result => ({
      item: result.item,
      matches: result.matches,
      score: result.score
    }));
  } else {
    return props?.permissions?.map(item => ({
      item: Object.assign(item, {}),
      matches: [],
      score: 1
    }));
  }
});

const filteredPermissions = computed(() => {
  return results.value.map(result => result.item);
});

const selectedPermissionsCount = computed(() => {
  return form.permissions.length;
});

const isFormValid = computed(() => {
  return form.name && form.label;
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
      label: 'changed',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else {
    return {
      label: 'unchanged',
      color: 'bg-blue-100 text-blue-800',
      icon: ShieldCheckIcon
    };
  }
});

const permissionsStatus = computed(() => {
  const total = props.permissions.length;
  const selected = selectedPermissionsCount.value;
  
  if (selected === 0) {
    return {
      label: 'none_selected',
      color: 'bg-red-100 text-red-800',
      icon: ExclamationTriangleIcon
    };
  } else if (selected === total) {
    return {
      label: 'all_selected',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else {
    return {
      label: 'partial',
      color: 'bg-yellow-100 text-yellow-800',
      icon: ShieldCheckIcon
    };
  }
});

// Methods
const toggleAllPermissions = () => {
  // FIX 4: Only add permission IDs when selecting all
  if (form.permissions.length === filteredPermissions.value.length) {
    form.permissions = [];
  } else {
    form.permissions = filteredPermissions.value.map(p => p.value);
  }
};

const togglePermission = (permission) => {
  // FIX 2: Find index based on ID
  const index = form.permissions.findIndex(id => id === permission.value); 
  if (index > -1) {
    // Deselect: remove the ID
    form.permissions.splice(index, 1);
  } else {
    // Select: add the ID
    form.permissions.push(permission.value);
  }
};

const isPermissionSelected = (permission) => {
  // FIX 3: Check if the permission ID is included in the array of IDs
  return form.permissions.includes(permission.value);
};

const submit = () => {
  if (!isFormValid.value) return;
  
  showDeleteConfirmation.value = true;
};

const handleConfirmSubmit = () => {
  if(!form.id) {
    form.post(route('roles.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        showDeleteConfirmation.value = false;
      },
      onError: () => {
        showDeleteConfirmation.value = false;
      }
    });
  } else {
    form.put(route('roles.update',{role: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showDeleteConfirmation.value = false;
      },
      onError: () => {
        showDeleteConfirmation.value = false;
      }
    });
  }
};

// Watch for search input changes
watch(permInput, throttle((newValue) => {
  // Search logic handled by computed property
}, 300));
</script>

<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ShieldCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.roles.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.roles.page_update_description') }}
            <span v-if="form.name" class="font-semibold text-blue-900">
              {{ form.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="[
            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset ring-blue-700/10',
            formStatus.color
          ]">
            <component :is="formStatus.icon" class="h-4 w-4" />
            {{ $t(`gestlab.general.status.${formStatus.label}`) }}
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserGroupIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.roles.role_info') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                  :placeholder="$t('gestlab.general.placeholders.enter_role_name')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

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
                  :placeholder="$t('gestlab.general.placeholders.enter_role_label')"
                />
                <p v-if="form.errors.label" class="text-xs text-red-600">
                  {{ form.errors.label }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <ShieldCheckIcon class="h-5 w-5 text-blue-900" />
                  {{ $t('gestlab.general.labels.roles.permissions') }}
                  <span class="text-sm font-normal text-gray-500 ml-2">
                    ({{ selectedPermissionsCount }}/{{ filteredPermissions.length }} {{ $t('gestlab.general.labels.roles.selected') }})
                  </span>
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.roles.permissions_description') }}
                </p>
              </div>
              <button 
                @click="toggleAllPermissions"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                {{ form.permissions.length === filteredPermissions.length 
                  ? $t('gestlab.general.buttons.deselect_all') 
                  : $t('gestlab.general.buttons.select_all') }}
              </button>
            </div>
          </div>

          <div class="border-b border-gray-200 px-6 py-4">
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input 
                v-model="permInput"
                type="search"
                name="permission-search"
                id="permission-search"
                class="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                :placeholder="$t('gestlab.general.labels.roles.placeholders.search_permissions')"
              />
              <div v-if="permInput" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <button 
                  @click="permInput = ''"
                  type="button"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <span class="sr-only">{{ $t('gestlab.general.buttons.clear_results') }}</span>
                  &times;
                </button>
              </div>
            </div>
            <div v-if="permInput" class="mt-2 text-xs text-gray-500">
              {{ $t('gestlab.general.labels.roles.searching_permissions', { count: filteredPermissions.length }) }}
            </div>
          </div>

          <div class="p-6">
            <div v-if="filteredPermissions.length === 0" class="text-center py-12">
              <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.roles.no_permissions_found') }}
              </h3>
              <p class="mt-2 text-sm text-gray-500">
                {{ $t('gestlab.general.labels.roles.try_different_search') }}
              </p>
              <button 
                @click="permInput = ''"
                type="button"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                {{ $t('gestlab.general.buttons.clear_results') }}
              </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div 
                v-for="(permission, index) in filteredPermissions"
                :key="permission.id"
                class="group relative"
              >
                <div 
                  v-motion 
                  :initial="{ opacity: 0, y: 20 }"
                  :enter="{ opacity: 1, y: 0 }"
                  :delay="index * 30"
                >
                  <div 
                    @click="togglePermission(permission)"
                    :class="[
                      'cursor-pointer rounded-lg border p-4 transition-all duration-200 hover:shadow-sm',
                      isPermissionSelected(permission)
                        ? 'border-blue-900 bg-blue-50 ring-1 ring-blue-900'
                        : 'border-gray-200 bg-white hover:border-blue-900 hover:bg-blue-50'
                    ]"
                  >
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <div class="flex items-center gap-3">
                          <div 
                            :class="[
                              'flex h-5 w-5 items-center justify-center rounded-full border transition-colors duration-200',
                              isPermissionSelected(permission)
                                ? 'border-blue-900 bg-blue-900 text-white'
                                : 'border-gray-300 bg-white group-hover:border-blue-900'
                            ]"
                          >
                            <CheckCircleIcon 
                              v-if="isPermissionSelected(permission)"
                              class="h-3 w-3"
                            />
                          </div>
                          <div>
                            <h3 class="text-sm font-medium text-gray-900">
                              {{ permission.label }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                              {{ permission.name }}
                            </p>
                          </div>
                        </div>
                        <div v-if="permission.description" class="mt-2 pl-8">
                          <p class="text-xs text-gray-600">
                            {{ permission.description }}
                          </p>
                        </div>
                      </div>
                      <div v-if="isPermissionSelected(permission)" class="ml-2">
                        <CheckCircleIcon class="h-5 w-5 text-green-600" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="selectedPermissionsCount > 0" class="mt-8 pt-6 border-t border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.roles.selected_permissions') }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    {{ $t('gestlab.general.labels.roles.permissions_summary', { count: selectedPermissionsCount }) }}
                  </p>
                </div>
                <span :class="[
                  'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                  permissionsStatus.color
                ]">
                  <component :is="permissionsStatus.icon" class="h-3 w-3" />
                  {{ $t(`gestlab.general.status.${permissionsStatus.label}`) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.actions') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <ArrowPathIcon v-if="form.processing" class="h-5 w-5 animate-spin" />
              <CheckCircleIcon v-else class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.update') }}
            </button>
            
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.total_permissions') }}</span>
                  <span class="font-semibold text-blue-900">{{ props.permissions.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.selected_permissions') }}</span>
                  <span class="font-semibold text-green-600">{{ selectedPermissionsCount }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.remaining_permissions') }}</span>
                  <span class="font-semibold text-gray-600">{{ props.permissions.length - selectedPermissionsCount }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.roles.role_status') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                formStatus.color
              ]">
                <component :is="formStatus.icon" class="h-3 w-3" />
                {{ $t(`gestlab.general.status.${formStatus.label}`) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.role_name_set') }}</span>
              <span :class="form.name ? 'text-green-600' : 'text-red-600'">
                {{ form.name ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.role_label_set') }}</span>
              <span :class="form.label ? 'text-green-600' : 'text-red-600'">
                {{ form.label ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.permissions_status') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                permissionsStatus.color
              ]">
                <component :is="permissionsStatus.icon" class="h-3 w-3" />
                {{ $t(`gestlab.general.status.${permissionsStatus.label}`) }}
              </span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.help') }}
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
              <ShieldCheckIcon class="h-5 w-5 text-blue-900 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-medium text-gray-900">{{ $t('gestlab.general.labels.roles.role_name_tip') }}</p>
                <p class="mt-1 text-gray-600">{{ $t('gestlab.general.labels.roles.use_meaningful_names') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <CheckCircleIcon class="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-medium text-gray-900">{{ $t('gestlab.general.labels.roles.permissions_tip') }}</p>
                <p class="mt-1 text-gray-600">{{ $t('gestlab.general.labels.roles.assign_only_necessary') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.roles.auto_save') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="toggleAllPermissions"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          {{ form.permissions.length === filteredPermissions.length 
            ? $t('gestlab.general.buttons.deselect_all') 
            : $t('gestlab.general.buttons.select_all') }}
        </button>
        <button 
          @click="submit"
          :disabled="!isFormValid"
          type="button"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200',
            !isFormValid
              ? 'bg-gray-400 cursor-not-allowed'
              : 'bg-blue-900 hover:bg-blue-800'
          ]"
        >
          <CheckCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.update') }}
        </button>
      </div>
    </div>
  </div>

  <confirm-dialog
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="handleConfirmSubmit"
    v-if="showDeleteConfirmation"
    :title="$t('gestlab.actions.confirmation_dialog_title.edit')"
    :description="$t('gestlab.actions.confirmation_dialog_description.edit')"
    :confirm="$t('gestlab.general.buttons.confirm')"
    :cancel="$t('gestlab.general.buttons.cancel')"
  >
    <div class="mt-4 space-y-4">
      <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900">
        <CheckCircleIcon class="h-4 w-4" />
        <p>{{ $t('gestlab.general.labels.roles.update_summary') }}</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.roles.role_details') }}
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.name') }}</span>
                <span class="font-medium text-gray-900">{{ form.name }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.label') }}</span>
                <span class="font-medium text-gray-900">{{ form.label }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="space-y-4">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.roles.permissions_summary') }}
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.total_permissions') }}</span>
                <span class="font-medium text-gray-900">{{ props.permissions.length }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.selected_permissions') }}</span>
                <span class="font-medium text-green-600">{{ selectedPermissionsCount }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.roles.changes') }}</span>
                <span :class="form.isDirty ? 'text-blue-900' : 'text-gray-500'">
                  {{ form.isDirty ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
                </span>
              </div>
            </div>
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

/* Custom checkbox styling */
input[type="checkbox"]:checked {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
}

/* Focus styles for accessibility */
:focus {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

:focus:not(:focus-visible) {
  outline: none;
}
</style>
