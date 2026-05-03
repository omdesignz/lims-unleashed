<script setup>
import { reactive, ref, computed, watch } from 'vue';
import Layout from "@/Shared/Layouts/Layout.vue";
import DatePickerEnhanced from '@/Components/date-picker-enhanced.vue';
import UserCard from '@/Pages/partials/user-card.vue';
import ComboboxMultipleEnhanced from '@/Components/combobox-multiple-enhanced.vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SignaturePad from '@/Components/signature-pad.vue';
import { usePermission } from '@/Composables/usePermissions';
import Fuse from 'fuse.js';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { 
  MagnifyingGlassIcon,
  UserIcon,
  EnvelopeIcon,
  PhoneIcon,
  IdentificationIcon,
  CalendarIcon,
  BuildingOfficeIcon,
  ShieldCheckIcon,
  KeyIcon,
  LockClosedIcon,
  PencilSquareIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  MinusCircleIcon,
  ClockIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  UsersIcon,
  TagIcon
} from '@heroicons/vue/24/outline';

const { hasRole, hasPermission } = usePermission();

defineOptions({
    layout: Layout
});
const props = defineProps({
    record: Object,
    permissions: Array,
    roles: Array,
    auth: Object,
    competenceSummary: Object
});

// Reactive state
const permInput = ref('');
const editUserInfo = ref(false);
const showUpdateConfirmation = ref(false);
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
const form = reactive(useForm({
  ...props.record,
  gender: props.record.gender || 'O'
}));

const updatePass = useForm({
  id: null,
  password: null,
  password_confirmation: null,
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

const formStatus = computed(() => {
  if (!editUserInfo.value && !form.isDirty && !updatePass.isDirty) {
    return {
      label: 'viewing',
      color: 'bg-blue-100 text-blue-800',
      icon: UserIcon
    };
  } else if (form.isDirty || updatePass.isDirty) {
    return {
      label: 'changes_pending',
      color: 'bg-yellow-100 text-yellow-800',
      icon: PencilSquareIcon
    };
  } else if (editUserInfo.value) {
    return {
      label: 'editing',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else {
    return {
      label: 'unchanged',
      color: 'bg-gray-100 text-gray-800',
      icon: DocumentTextIcon
    };
  }
});

const canEditPassword = computed(() => {
  return editUserInfo.value && (hasPermission('reset-password_users') || form.id === props?.auth?.user?.id);
});

const canEditPermissions = computed(() => {
  return editUserInfo.value && hasPermission('edit_permissions');
});

const hasUnsavedChanges = computed(() => {
  return form.isDirty || updatePass.isDirty;
});

const isCurrentUser = computed(() => {
  return form.id === props?.auth?.user?.id;
});

const qualificationStatusConfig = {
  active: {
    label: 'Ativa',
    classes: 'bg-emerald-100 text-emerald-800 border-emerald-200',
  },
  expiring_soon: {
    label: 'Renovação próxima',
    classes: 'bg-amber-100 text-amber-800 border-amber-200',
  },
  expiring_critical: {
    label: 'Renovação urgente',
    classes: 'bg-orange-100 text-orange-800 border-orange-200',
  },
  expired: {
    label: 'Expirada',
    classes: 'bg-rose-100 text-rose-800 border-rose-200',
  },
  inactive: {
    label: 'Inativa',
    classes: 'bg-slate-100 text-slate-700 border-slate-200',
  },
  scheduled: {
    label: 'Programada',
    classes: 'bg-sky-100 text-sky-800 border-sky-200',
  },
};

const readinessConfig = {
  on_track: {
    label: 'Em conformidade',
    classes: 'bg-emerald-50 text-emerald-700',
  },
  ready_for_review: {
    label: 'Pronta para renovação',
    classes: 'bg-blue-50 text-blue-700',
  },
  training_pending: {
    label: 'Formação pendente',
    classes: 'bg-amber-50 text-amber-700',
  },
  missing_evidence: {
    label: 'Falta evidência',
    classes: 'bg-rose-50 text-rose-700',
  },
};

const followUpConfig = {
  scheduled: {
    label: 'Acompanhamento planeado',
    classes: 'bg-sky-50 text-sky-700',
  },
  due_soon: {
    label: 'Acompanhamento próximo',
    classes: 'bg-amber-50 text-amber-700',
  },
  overdue: {
    label: 'Acompanhamento em atraso',
    classes: 'bg-rose-50 text-rose-700',
  },
  unscheduled: {
    label: 'Sem plano definido',
    classes: 'bg-slate-100 text-slate-700',
  },
};

const competencyCards = computed(() => [
  { label: 'Qualificações ativas', value: props.competenceSummary?.active ?? 0, tone: 'text-emerald-700' },
  { label: 'Renovações próximas', value: props.competenceSummary?.expiring_soon ?? 0, tone: 'text-amber-700' },
  { label: 'Prontas para renovação', value: props.competenceSummary?.ready_for_renewal ?? 0, tone: 'text-blue-700' },
  { label: 'Sem evidência', value: props.competenceSummary?.missing_evidence ?? 0, tone: 'text-rose-700' },
]);

const sortedQualifications = computed(() => {
  const priority = {
    expired: 0,
    expiring_critical: 1,
    expiring_soon: 2,
    scheduled: 3,
    active: 4,
    inactive: 5,
  };

  return [...(form.personnel_qualifications ?? [])].sort((left, right) => {
    const leftRank = priority[left.monitoring_status] ?? 99;
    const rightRank = priority[right.monitoring_status] ?? 99;

    if (leftRank !== rightRank) {
      return leftRank - rightRank;
    }

    return (left.days_until_expiry ?? 99999) - (right.days_until_expiry ?? 99999);
  });
});

// Gender options
const genderOptions = ref([
  {
    value: 'O',
    label: 'Outro'
  },
  {
    value: 'M',
    label: 'Masculino'
  },
  {
    value: 'F',
    label: 'Feminino'
  }
]);

// Date picker masks
const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  input: 'YYYY-MM-DD',
  model: 'YYYY-MM-DD',
});

// Methods
const updateRange = (e) => {
  form.dob = e;
};

const qualificationDefaults = () => ({
  capability: '',
  department_id: null,
  authorized_from: null,
  authorized_until: null,
  training_completed_at: null,
  training_reference: '',
  notes: '',
  is_active: true,
  monitoring_status: 'scheduled',
  renewal_readiness: 'training_pending',
  follow_up_due_at: null,
  follow_up_state: 'unscheduled',
  days_until_expiry: null,
  qualified_by: null,
});

const addQualification = () => {
  form.personnel_qualifications.push(qualificationDefaults());
};

const removeQualification = (index) => {
  form.personnel_qualifications.splice(index, 1);
};

const statusBadge = (status) => qualificationStatusConfig[status] ?? qualificationStatusConfig.inactive;
const readinessBadge = (status) => readinessConfig[status] ?? readinessConfig.training_pending;
const followUpBadge = (status) => followUpConfig[status] ?? followUpConfig.unscheduled;

const formatDate = (value) => {
  return value ? new Date(value).toLocaleDateString('pt-PT') : '—';
};

const expiryHint = (qualification) => {
  if (qualification.days_until_expiry === null || qualification.days_until_expiry === undefined) {
    return 'Sem data final definida.';
  }

  if (qualification.days_until_expiry < 0) {
    return `Expirada há ${Math.abs(qualification.days_until_expiry)} dias.`;
  }

  if (qualification.days_until_expiry === 0) {
    return 'Expira hoje.';
  }

  return `Expira em ${qualification.days_until_expiry} dias.`;
};

function saveSignature(e) {
  useForm({
    signature: e
  }).post(route('users.setsignature',{user: form.id}), {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled by parent component
    },
  });
}

function deleteSignature(e) {
  useForm({}).get(route('users.unsetsignature',{user: form.id}), {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled by parent component
    },
  });
}

function loadDepartments(query, setOptions) {
  if (!query) return;
  
  fetch(`/departments/getDepartment?q=${query}`)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
        }))
      );
    });
}

function loadRoles(query, setOptions) {
  if (!query) return;
  
  fetch(`/roles/getRole?q=${query}`)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.label,
        }))
      );
    });
}

const toggleEditMode = () => {
  if (editUserInfo.value && hasUnsavedChanges.value) {
    showUpdateConfirmation.value = true;
  } else {
    editUserInfo.value = !editUserInfo.value;
    if (!editUserInfo.value) {
      form.reset();
      updatePass.reset();
    }
  }
};

const submit = () => {
  if (!hasUnsavedChanges.value) return;
  
  showUpdateConfirmation.value = true;
};

const handleConfirmSubmit = () => {
  isLoading.value = true;
  
  // First update user info if changed
  if (form.isDirty) {
    form.put(route('users.update', {id: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        // Then update password if changed
        if (updatePass.isDirty) {
          updatePass.put(route('users.setpass',{user: form.id}), {
            preserveScroll: true,
            onSuccess: () => {
              updatePass.reset();
              form.reset();
              editUserInfo.value = false;
              showUpdateConfirmation.value = false;
              isLoading.value = false;
            },
            onError: () => {
              showUpdateConfirmation.value = false;
              isLoading.value = false;
            }
          });
        } else {
          form.reset();
          editUserInfo.value = false;
          showUpdateConfirmation.value = false;
          isLoading.value = false;
        }
      },
      onError: () => {
        showUpdateConfirmation.value = false;
        isLoading.value = false;
      }
    });
  } else if (updatePass.isDirty) {
    // Only password changed
    updatePass.put(route('users.setpass',{user: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        updatePass.reset();
        editUserInfo.value = false;
        showUpdateConfirmation.value = false;
        isLoading.value = false;
      },
      onError: () => {
        showUpdateConfirmation.value = false;
        isLoading.value = false;
      }
    });
  }
};

const togglePermission = (permission) => {
  const index = form.permissions.findIndex(p => p.value === permission.value);
  if (index > -1) {
    form.permissions.splice(index, 1);
  } else {
    form.permissions.push(permission);
  }
};

const isPermissionSelected = (permission) => {
  return form.permissions.some(p => p.value === permission.value);
};

// Watch for search input changes
watch(permInput, (newValue) => {
  // Search logic handled by computed property
});
</script>

<template>
  <div class="space-y-8">
    <!-- USER CARD -->
    <UserCard :auth="props.record" :greeting="$t('gestlab.general.labels.users.modifying_user')" />

    <!-- MAIN CONTENT -->
    <div class="space-y-6">
      <!-- USER INFORMATION CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- CARD HEADER -->
        <div class="border-b border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-blue-100 p-2">
                <UserIcon class="h-6 w-6 text-blue-900" />
              </div>
              <div>
                <h2 class="text-lg font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.users.general_info_title') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.users.general_info_description') }}
                  <span class="font-semibold text-blue-900">{{ form.name }}</span>
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                formStatus.color
              ]">
                <component :is="formStatus.icon" class="h-3 w-3" />
                {{ $t(`gestlab.general.status.${formStatus.label}`) }}
              </span>
              <button 
                @click="toggleEditMode"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PencilSquareIcon class="h-4 w-4" />
                {{ editUserInfo ? $t('gestlab.general.buttons.cancel') : $t('gestlab.general.buttons.edit') }}
              </button>
            </div>
          </div>
        </div>

        <!-- CARD CONTENT -->
        <div class="p-6">
          <!-- FORM GRID -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- USERNAME -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <TagIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.username') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.username || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <input 
                  v-model="form.username" 
                  type="text"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.users.placeholders.enter_username')"
                />
                <p v-if="form.errors.username" class="text-xs text-red-600">
                  {{ form.errors.username }}
                </p>
              </div>
            </div>

            <!-- FULL NAME -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <UserIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.name') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.name || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <input 
                  v-model="form.name" 
                  type="text"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.users.placeholders.enter_name')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>
            </div>

            <!-- GENDER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <UsersIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.gender') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ genderOptions.find(g => g.value === form.gender)?.label || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <select 
                  v-model="form.gender"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                >
                  <option v-for="gender in genderOptions" :key="gender.value" :value="gender.value">
                    {{ gender.label }}
                  </option>
                </select>
                <p v-if="form.errors.gender" class="text-xs text-red-600">
                  {{ form.errors.gender }}
                </p>
              </div>
            </div>

            <!-- EMAIL -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <EnvelopeIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.email') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.email || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <input 
                  v-model="form.email" 
                  type="email"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.placeholders.enter_email')"
                />
                <p v-if="form.errors.email" class="text-xs text-red-600">
                  {{ form.errors.email }}
                </p>
              </div>
            </div>

            <!-- ID NUMBER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <IdentificationIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.id_number') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.id_number || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <input 
                  v-model="form.id_number" 
                  type="text"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.users.placeholders.enter_id_number')"
                />
                <p v-if="form.errors.id_number" class="text-xs text-red-600">
                  {{ form.errors.id_number }}
                </p>
              </div>
            </div>

            <!-- PRIMARY PHONE -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <PhoneIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.primary_phone') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.primary_phone || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <input 
                  v-model="form.primary_phone" 
                  type="tel"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.users.placeholders.enter_phone')"
                />
                <p v-if="form.errors.primary_phone" class="text-xs text-red-600">
                  {{ form.errors.primary_phone }}
                </p>
              </div>
            </div>

            <!-- SECONDARY PHONE -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <PhoneIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.secondary_phone') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.secondary_phone || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <input 
                  v-model="form.secondary_phone" 
                  type="tel"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.users.placeholders.enter_secondary_phone')"
                />
                <p v-if="form.errors.secondary_phone" class="text-xs text-red-600">
                  {{ form.errors.secondary_phone }}
                </p>
              </div>
            </div>

            <!-- DATE OF BIRTH -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <CalendarIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.dob') }}
              </label>
              <div v-if="!editUserInfo" class="text-sm text-gray-900 bg-gray-50 rounded-lg px-3 py-2">
                {{ form.dob || $t('gestlab.general.labels.users.not_set') }}
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <DatePickerEnhanced 
                  v-model.string="form.dob" 
                  mode="date" 
                  locale="pt" 
                  :masks="masks" 
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  @update:model-value="updateRange"
                />
                <p v-if="form.errors.dob" class="text-xs text-red-600">
                  {{ form.errors.dob }}
                </p>
              </div>
            </div>

            <!-- DEPARTMENTS -->
            <div class="space-y-2 md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <BuildingOfficeIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.departments') }}
              </label>
              <div v-if="!editUserInfo" class="flex flex-wrap gap-2">
                <span 
                  v-for="department in form.departments" 
                  :key="department.id" 
                  class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800"
                >
                  {{ department.label }}
                </span>
                <span v-if="form.departments.length === 0" class="text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.users.no_departments') }}
                </span>
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <ComboboxMultipleEnhanced 
                  v-model="form.departments" 
                  :load-options="loadDepartments"
                  multiple
                  :placeholder="$t('gestlab.general.labels.users.placeholders.select_departments')"
                />
                <p v-if="form.errors.departments" class="text-xs text-red-600">
                  {{ form.errors.departments }}
                </p>
              </div>
            </div>

            <!-- ROLES -->
            <div class="space-y-2 md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <ShieldCheckIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.users.roles') }}
              </label>
              <div v-if="!editUserInfo" class="flex flex-wrap gap-2">
                <span 
                  v-for="role in form.roles" 
                  :key="role.id" 
                  class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800"
                >
                  {{ role.label }}
                </span>
                <span v-if="form.roles.length === 0" class="text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.users.no_roles') }}
                </span>
              </div>
              <div v-if="editUserInfo" class="space-y-1">
                <ComboboxMultipleEnhanced 
                  v-model="form.roles" 
                  :load-options="loadRoles"
                  multiple
                  :placeholder="$t('gestlab.general.labels.users.placeholders.select_roles')"
                />
                <p v-if="form.errors.roles" class="text-xs text-red-600">
                  {{ form.errors.roles }}
                </p>
              </div>
            </div>

            <!-- PASSWORD UPDATE (Edit Mode Only) -->
            <template v-if="editUserInfo && canEditPassword">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <LockClosedIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.users.password') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="space-y-1">
                  <input 
                    v-model="updatePass.password" 
                    type="password"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                    :placeholder="$t('gestlab.general.labels.users.placeholders.enter_new_password')"
                  />
                  <p v-if="updatePass.errors.password" class="text-xs text-red-600">
                    {{ updatePass.errors.password }}
                  </p>
                </div>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <KeyIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.users.password_confirmation') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="space-y-1">
                  <input 
                    v-model="updatePass.password_confirmation" 
                    type="password"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                    :placeholder="$t('gestlab.general.labels.users.placeholders.confirm_new_password')"
                  />
                  <p v-if="updatePass.errors.password_confirmation" class="text-xs text-red-600">
                    {{ updatePass.errors.password_confirmation }}
                  </p>
                </div>
              </div>
            </template>
          </div>

          <!-- PERMISSIONS SECTION (Edit Mode Only) -->
          <div v-if="editUserInfo && canEditPermissions" class="mt-8 pt-6 border-t border-gray-200">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-2">
                <ShieldCheckIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.users.permissions') }}
              </h3>
              <p class="text-sm text-gray-500">
                {{ $t('gestlab.general.labels.users.manage_user_permissions') }}
              </p>
            </div>

            <!-- PERMISSIONS SEARCH -->
            <div class="mb-6">
              <label class="sr-only">{{ $t('gestlab.general.labels.users.placeholders.search_permissions') }}</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input 
                  v-model="permInput"
                  type="search"
                  class="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  :placeholder="$t('gestlab.general.labels.users.placeholders.search_permissions')"
                />
              </div>
            </div>

            <!-- PERMISSIONS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="(permission, index) in filteredPermissions"
                :key="permission.id"
                class="group relative"
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
                    </div>
                    <div v-if="isPermissionSelected(permission)" class="ml-2">
                      <CheckCircleIcon class="h-5 w-5 text-green-600" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- EMPTY SEARCH STATE -->
            <div v-if="filteredPermissions.length === 0" class="text-center py-8">
              <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.users.no_permissions_found') }}
              </h3>
              <p class="mt-2 text-sm text-gray-500">
                {{ $t('gestlab.general.labels.users.try_different_search') }}
              </p>
            </div>

            <!-- PERMISSIONS SUMMARY -->
            <div v-if="form.permissions.length > 0" class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.users.selected_permissions') }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    {{ $t('gestlab.general.labels.users.total_permissions_selected', { count: form.permissions.length }) }}
                  </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                  {{ form.permissions.length }} {{ $t('gestlab.general.labels.users.selected') }}
                </span>
              </div>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <ShieldCheckIcon class="h-5 w-5 text-blue-900" />
                  Competência técnica e certificação
                </h3>
                <p class="mt-2 max-w-3xl text-sm text-gray-500">
                  Monitoriza autorizações, evidência de formação e prontidão para renovação de acordo com os controlos ISO 17025.
                </p>
              </div>
              <button
                v-if="editUserInfo"
                type="button"
                @click="addQualification"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2 text-sm font-semibold text-blue-900 hover:bg-blue-50"
              >
                <PlusIcon class="h-4 w-4" />
                Adicionar qualificação
              </button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
              <div
                v-for="card in competencyCards"
                :key="card.label"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
              >
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                <p :class="['mt-2 text-2xl font-semibold', card.tone]">{{ card.value }}</p>
              </div>
            </div>

            <div v-if="sortedQualifications.length" class="mt-6 space-y-4">
              <article
                v-for="qualification in sortedQualifications"
                :key="qualification.id ?? qualification.capability"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
              >
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                  <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <h4 class="text-base font-semibold text-slate-900">
                        {{ qualification.capability || 'Nova qualificação' }}
                      </h4>
                      <span :class="['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold', statusBadge(qualification.monitoring_status).classes]">
                        {{ statusBadge(qualification.monitoring_status).label }}
                      </span>
                      <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', readinessBadge(qualification.renewal_readiness).classes]">
                        {{ readinessBadge(qualification.renewal_readiness).label }}
                      </span>
                      <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', followUpBadge(qualification.follow_up_state).classes]">
                        {{ followUpBadge(qualification.follow_up_state).label }}
                      </span>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                      <div class="rounded-xl bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Departamento</p>
                        <p class="mt-1 text-sm text-slate-900">{{ qualification.department_id?.label || 'Transversal' }}</p>
                      </div>
                      <div class="rounded-xl bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Validade</p>
                        <p class="mt-1 text-sm text-slate-900">{{ qualification.authorized_until ? formatDate(qualification.authorized_until) : 'Sem limite' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ expiryHint(qualification) }}</p>
                      </div>
                      <div class="rounded-xl bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Evidência de formação</p>
                        <p class="mt-1 text-sm text-slate-900">{{ qualification.training_reference || 'Não registada' }}</p>
                      </div>
                      <div class="rounded-xl bg-white p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Próximo follow-up</p>
                        <p class="mt-1 text-sm text-slate-900">{{ qualification.follow_up_due_at ? formatDate(qualification.follow_up_due_at) : 'Definir plano' }}</p>
                        <p class="mt-1 text-xs text-slate-500">Qualificado por {{ qualification.qualified_by || '—' }}</p>
                      </div>
                    </div>
                  </div>

                  <button
                    v-if="editUserInfo"
                    type="button"
                    @click="removeQualification(form.personnel_qualifications.findIndex(item => item === qualification))"
                    class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
                  >
                    <MinusCircleIcon class="h-4 w-4" />
                    Remover
                  </button>
                </div>

                <div v-if="editUserInfo" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Capacidade / ensaio</label>
                    <input v-model="qualification.capability" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm" placeholder="Ex.: Verificação de resultados" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Departamento</label>
                    <ComboboxMultipleEnhanced v-model="qualification.department_id" :load-options="loadDepartments" :multiple="false" placeholder="Selecionar departamento" class="mt-1" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Referência da formação/certificado</label>
                    <input v-model="qualification.training_reference" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm" placeholder="Ex.: CERT-2026-014" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Autorizada desde</label>
                    <input v-model="qualification.authorized_from" type="date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Autorizada até</label>
                    <input v-model="qualification.authorized_until" type="date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Formação concluída em</label>
                    <input v-model="qualification.training_completed_at" type="date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm" />
                  </div>
                  <div class="xl:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Plano de acompanhamento / notas</label>
                    <textarea v-model="qualification.notes" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm" placeholder="Registe ações de follow-up, reciclagem, observações de auditoria ou dependências para renovação." />
                  </div>
                </div>

                <div v-else-if="qualification.notes" class="mt-4 rounded-xl bg-white p-3 text-sm text-slate-600">
                  {{ qualification.notes }}
                </div>
              </article>
            </div>

            <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
              <ClockIcon class="mx-auto h-8 w-8 text-slate-400" />
              <h4 class="mt-3 text-sm font-semibold text-slate-900">Sem qualificações registadas</h4>
              <p class="mt-2 text-sm text-slate-500">
                Registe competências, evidências e janelas de renovação para controlar autorização técnica e follow-up.
              </p>
            </div>
          </div>

          <!-- ACTION BUTTONS -->
          <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
              {{ $t('gestlab.general.labels.users.changes_auto_save') }}
            </div>
            <div class="flex items-center gap-3">
              <button 
                v-if="editUserInfo && hasUnsavedChanges"
                @click="submit"
                :disabled="isLoading"
                type="button"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200',
                  isLoading
                    ? 'bg-gray-400 cursor-not-allowed'
                    : 'bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
              >
                <ArrowPathIcon v-if="isLoading" class="h-4 w-4 animate-spin" />
                <CheckCircleIcon v-else class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.submit') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- SIGNATURE SECTION (Current User Only) -->
      <div v-if="isCurrentUser" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.users.signature') }}
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ $t('gestlab.general.labels.users.signature_description') }}
          </p>
        </div>
        <div class="p-6">
          <SignaturePad 
            :current-signature="props.record?.signature_url" 
            @save="saveSignature" 
            @delete="deleteSignature" 
          />
        </div>
      </div>
    </div>
  </div>

  <!-- UPDATE CONFIRMATION DIALOG -->
  <confirm-dialog
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    @canceled="showUpdateConfirmation = false"
    @close="showUpdateConfirmation = false"
    @confirmed="handleConfirmSubmit"
    v-if="showUpdateConfirmation"
    :title="$t('gestlab.actions.confirmation_dialog_title.edit')"
    :description="$t('gestlab.actions.confirmation_dialog_description.edit')"
    :confirm="$t('gestlab.general.buttons.confirm')"
    :cancel="$t('gestlab.general.buttons.cancel')"
  >
    <div class="mt-4 space-y-4">
      <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900">
        <CheckCircleIcon class="h-4 w-4" />
        <p>{{ $t('gestlab.general.labels.users.update_summary') }}</p>
      </div>
      
      <div class="space-y-4">
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.users.changes_to_be_made') }}
          </h4>
          <ul class="space-y-2">
            <li v-if="form.isDirty" class="flex items-center gap-2 text-sm">
              <CheckCircleIcon class="h-4 w-4 text-green-600" />
              <span class="text-gray-700">{{ $t('gestlab.general.labels.users.update_user_info') }}</span>
            </li>
            <li v-if="updatePass.isDirty" class="flex items-center gap-2 text-sm">
              <KeyIcon class="h-4 w-4 text-blue-600" />
              <span class="text-gray-700">{{ $t('gestlab.general.labels.users.update_user_password') }}</span>
            </li>
          </ul>
        </div>
        
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.users.user_summary') }}
          </h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.users.name') }}</span>
              <span class="font-medium text-gray-900">{{ form.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.users.email') }}</span>
              <span class="font-medium text-gray-900">{{ form.email }}</span>
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

/* Focus styles */
:focus {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

:focus:not(:focus-visible) {
  outline: none;
}
</style>
