 <script setup>
import { ref, computed, reactive, watch, onMounted } from 'vue'
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { useForm, router } from '@inertiajs/vue3'
import { 
  EyeIcon, 
  EyeSlashIcon, 
  FunnelIcon, 
  MagnifyingGlassIcon, 
  CalendarIcon,
  XMarkIcon,
  TrashIcon,
  ArrowPathIcon,
  DocumentTextIcon,
  UserIcon,
  ClockIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ChartBarIcon,
  DocumentMagnifyingGlassIcon,
  ServerIcon,
  TableCellsIcon,
  ArrowsPointingOutIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'
import DatePickerEnhanced from "@/Components/date-picker-enhanced.vue"
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import confirmDialog from "@/Components/confirm-dialog.vue"
import { trans } from 'laravel-vue-i18n';
import { TransitionRoot, Dialog, DialogPanel, DialogTitle, TransitionChild } from '@headlessui/vue'


const props = defineProps({
  record: Object,
  logNameOptions: Array,
  causerOptions: Array,
  subjectOptions: Array,
  eventOptions: Array,
  propertiesOptions: Array,
});

defineOptions({
  layout: Layout
});

// Reactive state
const showFilters = ref(false)
const showDeleteConfirmation = ref(false)
const selectedActivity = ref(null)
const isLoading = ref(false)
const viewMode = ref('table') // 'table' or 'card'
const expandedActivity = ref(null)

// Add these new reactive states
const detailedActivity = ref(null)
const detailedProperties = ref(null)
const showDetailsModal = ref(false)
const isLoadingDetails = ref(false)

// Date picker masks
const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  input: 'YYYY-MM-DD',
  model: 'YYYY-MM-DD',
});

// Filter form
const filters = useForm({
  log_name: null,
  causer_id: null,
  subject_id: null,
  subject_type: null,
  event: null,
  property: null,
  description: null,
  start_date: null,
  end_date: null,
  batch_uuid: null,
  per_page: 25,
}, {
  // Important: Don't reset on success for GET requests
  resetOnSuccess: false,
});

// Helper to get simple value
const getSimpleValue = (value) => {
  if (!value) return null;
  
  // Handle combobox object
  if (typeof value === 'object' && value !== null) {
    return value.value || value.id || null;
  }
  
  return value;
}

// Computed
// const hasActiveFilters = computed(() => {
//   return Object.values(filters.data()).some(value => value !== null && value !== '')
// })

const hasActiveFilters = computed(() => {
  return Object.keys(filters.data()).some(key => {
    if (key === 'per_page') return false;
    const value = filters[key];
    return value !== null && value !== '' && value !== undefined;
  });
})

const totalActivities = computed(() => {
  return props.record?.total || 0
})

const filteredActivities = computed(() => {
  return props.record?.data || []
})

const activityStats = computed(() => {
  const stats = {
    total: totalActivities.value,
    today: 0,
    yesterday: 0,
    this_week: 0,
    errors: 0,
    info: 0,
    warning: 0,
  }

  // Calculate stats from activities
  filteredActivities.value.forEach(activity => {
    // Check if activity is from today
    const activityDate = new Date(activity.created_at)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    
    if (activityDate.toDateString() === today.toDateString()) {
      stats.today++
    }
    
    if (activityDate.toDateString() === yesterday.toDateString()) {
      stats.yesterday++
    }
    
    // Check for error/warning/info
    const description = activity.description?.toLowerCase() || ''
    if (description.includes('error') || description.includes('failed')) {
      stats.errors++
    } else if (description.includes('warning')) {
      stats.warning++
    } else {
      stats.info++
    }
  })

  return stats
})

const getActivityTypeColor = (activity) => {
  const description = activity.description?.toLowerCase() || ''
  if (description.includes('error') || description.includes('failed')) {
    return {
      bg: 'bg-red-100',
      text: 'text-red-800',
      icon: ExclamationTriangleIcon,
      border: 'border-red-200'
    }
  } else if (description.includes('warning')) {
    return {
      bg: 'bg-yellow-100',
      text: 'text-yellow-800',
      icon: ExclamationTriangleIcon,
      border: 'border-yellow-200'
    }
  } else if (description.includes('created')) {
    return {
      bg: 'bg-green-100',
      text: 'text-green-800',
      icon: CheckCircleIcon,
      border: 'border-green-200'
    }
  } else if (description.includes('updated')) {
    return {
      bg: 'bg-blue-100',
      text: 'text-blue-800',
      icon: InformationCircleIcon,
      border: 'border-blue-200'
    }
  } else if (description.includes('deleted')) {
    return {
      bg: 'bg-red-100',
      text: 'text-red-800',
      icon: TrashIcon,
      border: 'border-red-200'
    }
  } else {
    return {
      bg: 'bg-gray-100',
      text: 'text-gray-800',
      icon: DocumentTextIcon,
      border: 'border-gray-200'
    }
  }
}

const getPropertyValue = (properties, key) => {
  if (!properties) return null
  
  try {
    const propsObj = typeof properties === 'string' ? JSON.parse(properties) : properties
    return propsObj[key]
  } catch (e) {
    return null
  }
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  return date.toLocaleString('pt-PT', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const formatRelativeTime = (dateString) => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffMins < 1) return 'Agora'
  if (diffMins < 60) return `${diffMins}m atrás`
  if (diffHours < 24) return `${diffHours}h atrás`
  if (diffDays === 1) return 'Ontem'
  if (diffDays < 7) return `${diffDays}d atrás`
  
  return date.toLocaleDateString('pt-PT')
}

const formatProperties = (properties) => {
  if (!properties) return 'Sem propriedades'
  
  try {
    const propsObj = typeof properties === 'string' ? JSON.parse(properties) : properties
    return JSON.stringify(propsObj, null, 2)
  } catch (e) {
    return String(properties)
  }
}

// Methods
// const applyFilters = () => {
//   filters.get(route('systemactivity.index'), {
//     preserveState: true,
//     preserveScroll: true,
//     onStart: () => isLoading.value = true,
//     onFinish: () => isLoading.value = false,
//   })
// }

const applyFilters = () => {
  // Build query params object from form data
  const queryParams = {};
  
  // Convert form data to simple key-value pairs
  Object.keys(filters.data()).forEach(key => {
    const value = getSimpleValue(filters[key]);
    if (value !== null && value !== '' && value !== undefined) {
      queryParams[key] = value;
    }
  });
  
  // Use router.get() with the query params
  router.get(route('systemactivity.index'), queryParams, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => isLoading.value = true,
    onFinish: () => isLoading.value = false,
  });
}

// const resetFilters = () => {
//   filters.reset()
//   applyFilters()
// }

const resetFilters = () => {
  // Reset form to initial values
  Object.keys(filters.data()).forEach(key => {
    if (key === 'per_page') {
      filters[key] = 25;
    } else {
      filters[key] = null;
    }
  });
  
  applyFilters();
}

// const clearFilter = (key) => {
//   filters[key] = null
//   applyFilters()
// }

const clearFilter = (key) => {
  if (key === 'per_page') {
    filters[key] = 25;
  } else {
    filters[key] = null;
  }
  applyFilters();
}

const loadLogNames = (query, setOptions) => {
  const filtered = props.logNameOptions
    ?.filter(option => option.label.toLowerCase().includes(query.toLowerCase()))
    .slice(0, 10)
  setOptions(filtered)
}

const loadCausers = (query, setOptions) => {
  const filtered = props.causerOptions
    ?.filter(option => option.label.toLowerCase().includes(query.toLowerCase()))
    .slice(0, 10)
  setOptions(filtered)
}

const loadSubjects = (query, setOptions) => {
  const filtered = props.subjectOptions
    ?.filter(option => option.label.toLowerCase().includes(query.toLowerCase()))
    .slice(0, 10)
  setOptions(filtered)
}

const loadEvents = (query, setOptions) => {
  const filtered = props.eventOptions
    ?.filter(option => option.label.toLowerCase().includes(query.toLowerCase()))
    .slice(0, 10)
  setOptions(filtered)
}

const loadProperties = (query, setOptions) => {
  const filtered = props.propertiesOptions
    ?.filter(option => option.label.toLowerCase().includes(query.toLowerCase()))
    .slice(0, 10)
  setOptions(filtered)
}

// const viewActivityDetails = (activity) => {
//   expandedActivity.value = expandedActivity.value === activity.id ? null : activity.id
// }

// const viewActivityDetails = async (activity) => {
//   try {
//     isLoading.value = true;
//     const response = await axios.get(route('systemactivity.show', { activity: activity.id }));
    
//     // Create a modal or expand with full details
//     expandedActivity.value = {
//       ...activity,
//       fullDetails: response.data
//     };
    
//   } catch (error) {
//     console.error('Failed to load activity details:', error);
//   } finally {
//     isLoading.value = false;
//   }
// }

const viewActivityDetails = async (activity) => {
  try {
    isLoadingDetails.value = true;
    
    // Fetch detailed activity from API
    const response = await axios.get(route('systemactivity.show', { activity: activity.id }));
    console.log('API Response:', response.data); // Debug log
    
    detailedActivity.value = response.data.activity;
    detailedProperties.value = response.data.properties_formatted;
    
    // Show modal
    showDetailsModal.value = true;
    
  } catch (error) {
    console.error('Failed to load activity details:', error);
    
    // Show error message based on error type
    if (error.response) {
      if (error.response.status === 403) {
        alert('You do not have permission to view activity details.');
      } else if (error.response.status === 404) {
        alert('Activity not found.');
      } else {
        alert('Failed to load activity details. Please try again.');
      }
    } else {
      alert('Network error. Please check your connection.');
    }
  } finally {
    isLoadingDetails.value = false;
  }
}

// Add helper function to format properties for display
const formatPropertiesForDisplay = (properties) => {
  if (!properties) return 'No properties available';
  
  // If properties is already a string, try to parse it
  if (typeof properties === 'string') {
    try {
      const parsed = JSON.parse(properties);
      return JSON.stringify(parsed, null, 2);
    } catch (e) {
      return properties; // Return as-is if not JSON
    }
  }
  
  // If properties is an object or array, stringify it
  if (typeof properties === 'object') {
    return JSON.stringify(properties, null, 2);
  }
  
  return String(properties);
}

const deleteActivity = (activity) => {
  selectedActivity.value = activity
  showDeleteConfirmation.value = true
}

const confirmDelete = () => {
  if (!selectedActivity.value) return
  
  router.delete(route('systemactivity.destroy', { activity: selectedActivity.value.id }), {
    preserveScroll: true,
    onStart: () => isLoading.value = true,
    onSuccess: () => {
      showDeleteConfirmation.value = false
      selectedActivity.value = null
      isLoading.value = false
    },
    onError: () => {
      isLoading.value = false
    }
  })
}

const deleteAllActivities = () => {
  if (!confirm('Tem a certeza que deseja eliminar todos os registos de atividade? Esta ação é irreversível.')) {
    return
  }
  
  router.delete(route('systemactivity.destroyAll'), {
    preserveScroll: true,
    onStart: () => isLoading.value = true,
    onSuccess: () => {
      isLoading.value = false
    },
    onError: () => {
      isLoading.value = false
    }
  })
}

// const exportActivities = () => {
//   const queryParams = new URLSearchParams(filters.data())
//   window.location.href = route('systemactivity.export') + '?' + queryParams.toString() 
// }

const exportActivities = async () => {
  try {
    isLoading.value = true;
    const queryParams = new URLSearchParams();
    
    // Add filters to query params
    Object.entries(filters.data()).forEach(([key, value]) => {
      if (value) {
        if (typeof value === 'object' && value !== null && value.value) {
          queryParams.append(key, value.value);
        } else {
          queryParams.append(key, value);
        }
      }
    });
    
    // Create download link
    const url = route('systemactivity.export') + '?' + queryParams.toString();
    const link = document.createElement('a');
    link.href = url;
    link.download = 'activity-logs.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
  } catch (error) {
    console.error('Export failed:', error);
    // Show error message to user
  } finally {
    isLoading.value = false;
  }
}

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table'
}

// Watchers
watch(() => filters.data(), () => {
  // Debounced filter application could be implemented here
}, { deep: true })

onMounted(() => {
  // Initialize any default filters from URL
})
</script>

<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentMagnifyingGlassIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.system_activity.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.system_activity.page_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ totalActivities }} {{ $t('gestlab.general.labels.system_activity.records') }}
          </span>
        </div>
      </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
      <!-- TOTAL ACTIVITIES -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.total') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ activityStats.total }}
            </p>
          </div>
          <div class="rounded-lg bg-blue-100 p-2">
            <ServerIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>

      <!-- TODAY'S ACTIVITIES -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.today') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ activityStats.today }}
            </p>
          </div>
          <div class="rounded-lg bg-green-100 p-2">
            <CalendarIcon class="h-6 w-6 text-green-900" />
          </div>
        </div>
      </div>

      <!-- YESTERDAY'S ACTIVITIES -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.yesterday') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ activityStats.yesterday }}
            </p>
          </div>
          <div class="rounded-lg bg-yellow-100 p-2">
            <ClockIcon class="h-6 w-6 text-yellow-900" />
          </div>
        </div>
      </div>

      <!-- ERRORS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.errors') }}
            </p>
            <p class="text-2xl font-bold text-red-600 mt-1">
              {{ activityStats.errors }}
            </p>
          </div>
          <div class="rounded-lg bg-red-100 p-2">
            <ExclamationTriangleIcon class="h-6 w-6 text-red-900" />
          </div>
        </div>
      </div>

      <!-- WARNINGS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.warnings') }}
            </p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">
              {{ activityStats.warning }}
            </p>
          </div>
          <div class="rounded-lg bg-yellow-100 p-2">
            <ExclamationTriangleIcon class="h-6 w-6 text-yellow-900" />
          </div>
        </div>
      </div>

      <!-- INFO -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.info') }}
            </p>
            <p class="text-2xl font-bold text-blue-600 mt-1">
              {{ activityStats.info }}
            </p>
          </div>
          <div class="rounded-lg bg-blue-100 p-2">
            <InformationCircleIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>

      <!-- VIEW TOGGLE -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ $t('gestlab.general.labels.system_activity.view_mode') }}
            </p>
            <p class="text-sm font-semibold text-gray-900 mt-1">
              {{ viewMode === 'table' ? 'Tabela' : 'Cartões' }}
            </p>
          </div>
          <button
            @click="toggleViewMode"
            type="button"
            class="rounded-lg bg-blue-100 p-2 hover:bg-blue-200 transition-colors duration-200"
          >
            <ArrowsPointingOutIcon class="h-6 w-6 text-blue-900" />
          </button>
        </div>
      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button
              @click="showFilters = !showFilters"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <FunnelIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.filters') }}
              <span v-if="hasActiveFilters" class="inline-flex items-center rounded-full bg-blue-900 px-2 py-0.5 text-xs font-medium text-white">
                {{ Object.values(filters.data()).filter(v => v).length }}
              </span>
            </button>
            
            <!-- ACTIVE FILTERS -->
            <div v-if="hasActiveFilters" class="flex flex-wrap gap-2">
              <template v-for="(value, key) in filters.data()" :key="key">
                <span 
                  v-if="value"
                  class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800"
                >
                  {{ key }}: {{ value?.label || value }}
                  <button
                    @click="clearFilter(key)"
                    type="button"
                    class="ml-1 text-blue-600 hover:text-blue-900"
                  >
                    <XMarkIcon class="h-3 w-3" />
                  </button>
                </span>
              </template>
              <button
                @click="resetFilters"
                type="button"
                class="text-sm font-medium text-red-600 hover:text-red-800"
              >
                {{ $t('gestlab.general.buttons.clear_all') }}
              </button>
            </div>
          </div>
          
          <!-- ACTIONS -->
          <div class="flex items-center gap-2">
            <button
              @click="exportActivities"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.export') }}
            </button>
            <button
              @click="deleteAllActivities"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
            >
              <TrashIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.clear_log') }}
            </button>
          </div>
        </div>
      </div>

      <!-- FILTER FORM -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-[1000px]"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 max-h-[1000px]"
        leave-to-class="opacity-0 max-h-0"
      >
        <div v-if="showFilters" class="px-6 py-6 border-b border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- LOG NAME FILTER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.log_name') }}
              </label>
              <comboboxEnhanced
                v-model="filters.log_name"
                :load-options="loadLogNames"
                :placeholder="$t('gestlab.general.labels.system_activity.placeholders.select_log_name')"
                class="w-full"
              />
            </div>

            <!-- CAUSER FILTER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.causer') }}
              </label>
              <comboboxEnhanced
                v-model="filters.causer_id"
                :load-options="loadCausers"
                :placeholder="$t('gestlab.general.labels.system_activity.placeholders.select_causer')"
                class="w-full"
              />
            </div>

            <!-- SUBJECT FILTER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.subject') }}
              </label>
              <comboboxEnhanced
                v-model="filters.subject_id"
                :load-options="loadSubjects"
                :placeholder="$t('gestlab.general.labels.system_activity.placeholders.select_subject')"
                class="w-full"
              />
            </div>

            <!-- EVENT FILTER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.event') }}
              </label>
              <comboboxEnhanced
                v-model="filters.event"
                :load-options="loadEvents"
                :placeholder="$t('gestlab.general.labels.system_activity.placeholders.select_event')"
                class="w-full"
              />
            </div>

            <!-- PROPERTY FILTER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.property') }}
              </label>
              <comboboxEnhanced
                v-model="filters.property"
                :load-options="loadProperties"
                :placeholder="$t('gestlab.general.labels.system_activity.placeholders.select_property')"
                class="w-full"
              />
            </div>

            <!-- DESCRIPTION FILTER -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.description') }}
              </label>
              <input
                v-model="filters.description"
                type="text"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                :placeholder="$t('gestlab.general.labels.system_activity.placeholders.search_description')"
              />
            </div>

            <!-- DATE RANGE FILTERS -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.start_date') }}
              </label>
              <DatePickerEnhanced
                v-model="filters.start_date"
                mode="date"
                :masks="masks"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
              />
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.system_activity.end_date') }}
              </label>
              <DatePickerEnhanced
                v-model="filters.end_date"
                mode="date"
                :masks="masks"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
              />
            </div>
          </div>

          <!-- FILTER ACTIONS -->
          <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end gap-3">
            <button
              @click="resetFilters"
              type="button"
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              {{ $t('gestlab.general.buttons.reset') }}
            </button>
            <button
              @click="applyFilters"
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
              <MagnifyingGlassIcon v-else class="h-4 w-4" />
              {{ isLoading ? $t('gestlab.general.buttons.applying') : $t('gestlab.general.buttons.apply_filters') }}
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ACTIVITIES CONTENT -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- TABLE VIEW -->
      <div v-if="viewMode === 'table'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.system_activity.description') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.system_activity.causer') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.system_activity.subject') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.system_activity.event') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.system_activity.timestamp') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="activity in filteredActivities" 
              :key="activity.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div :class="['rounded-lg p-2 mr-3', getActivityTypeColor(activity).bg]">
                    <component :is="getActivityTypeColor(activity).icon" :class="['h-5 w-5', getActivityTypeColor(activity).text]" />
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ activity.description }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ activity.log_name.label }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="activity.causer" class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">
                      {{ activity.causer.name || 'N/A' }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ activity.causer.email || '' }}
                    </div>
                  </div>
                </div>
                <span v-else class="text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.system_activity.system') }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ activity.subject_type || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getActivityTypeColor(activity).bg, getActivityTypeColor(activity).text]">
                  {{ activity.event }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex flex-col">
                  <span>{{ formatDateTime(activity.created_at) }}</span>
                  <span class="text-xs text-gray-400">{{ formatRelativeTime(activity.created_at) }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    @click="viewActivityDetails(activity)"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                  >
                    <EyeIcon class="h-3 w-3" />
                    {{ $t('gestlab.general.buttons.view') }}
                  </button>
                  <button
                    @click="deleteActivity(activity)"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                  >
                    <TrashIcon class="h-3 w-3" />
                    {{ $t('gestlab.general.buttons.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- CARD VIEW -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
        <div
          v-for="activity in filteredActivities"
          :key="activity.id"
          :class="['rounded-xl border shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md', getActivityTypeColor(activity).border]"
        >
          <!-- CARD HEADER -->
          <div :class="['px-4 py-3', getActivityTypeColor(activity).bg]">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <component :is="getActivityTypeColor(activity).icon" :class="['h-5 w-5', getActivityTypeColor(activity).text]" />
                <span :class="['text-sm font-semibold', getActivityTypeColor(activity).text]">
                  {{ activity.event }}
                </span>
              </div>
              <span class="text-xs font-medium text-gray-500">
                {{ formatRelativeTime(activity.created_at) }}
              </span>
            </div>
          </div>

          <!-- CARD CONTENT -->
          <div class="p-4">
            <!-- DESCRIPTION -->
            <div class="mb-4">
              <h3 class="text-sm font-medium text-gray-900 mb-1">
                {{ activity.description }}
              </h3>
              <p class="text-xs text-gray-500">
                {{ activity.log_name.label }}
              </p>
            </div>

            <!-- CAUSER -->
            <div class="flex items-center gap-3 mb-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <UserIcon class="h-4 w-4 text-blue-900" />
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">
                  {{ activity.causer?.name || $t('gestlab.general.labels.system_activity.system') }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ activity.causer?.email || '' }}
                </p>
              </div>
            </div>

            <!-- SUBJECT -->
            <div class="mb-3">
              <p class="text-xs font-medium text-gray-500 mb-1">
                {{ $t('gestlab.general.labels.system_activity.subject') }}
              </p>
              <p class="text-sm text-gray-900">
                {{ activity.subject_type || 'N/A' }}
              </p>
            </div>

            <!-- PROPERTIES PREVIEW -->
            <div v-if="activity.properties" class="mb-4">
              <p class="text-xs font-medium text-gray-500 mb-1">
                {{ $t('gestlab.general.labels.system_activity.properties') }}
              </p>
              <div class="bg-gray-50 rounded-lg p-2 max-h-20 overflow-y-auto">
                <pre class="text-xs text-gray-600">{{ formatProperties(activity.properties).substring(0, 100) }}...</pre>
              </div>
            </div>

            <!-- ACTIONS -->
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
              <button
                @click="viewActivityDetails(activity)"
                type="button"
                class="text-xs font-medium text-blue-600 hover:text-blue-900"
              >
                {{ $t('gestlab.general.buttons.view_details') }}
              </button>
              <button
                @click="deleteActivity(activity)"
                type="button"
                class="text-xs font-medium text-red-600 hover:text-red-900"
              >
                {{ $t('gestlab.general.buttons.delete') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="filteredActivities.length === 0" class="p-12 text-center">
        <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.system_activity.no_activities_found') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.system_activity.adjust_filters_or_try_again') }}
        </p>
        <button
          v-if="hasActiveFilters"
          @click="resetFilters"
          type="button"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <XMarkIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.clear_filters') }}
        </button>
      </div>

      <!-- PAGINATION -->
      <div v-if="filteredActivities.length > 0 && props.record.links" class="px-6 py-4 border-t border-gray-200">
        <nav class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            {{ $t('gestlab.general.labels.system_activity.showing_results', {
              from: props.record?.meta?.from || 0,
              to: props.record?.meta?.to || 0,
              total: props.record?.meta?.total || 0
            }) }}
          </div>
          <div class="flex gap-2">
            <template v-for="link in props.record.links">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                  'px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200',
                  link.active
                    ? 'bg-blue-900 text-white'
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="px-3 py-2 text-sm text-gray-400"
                v-html="link.label"
              />
            </template>
          </div>
        </nav>
      </div>
    </div>

    <!-- ACTIVITY DETAILS MODAL -->
  <TransitionRoot 
    :show="showDetailsModal && detailedActivity" 
    as="template"
  >
    <Dialog 
      as="div" 
      class="relative z-50" 
      @close="showDetailsModal = false"
    >
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
              <!-- Modal Header -->
              <div class="bg-white px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                    Activity Details
                  </DialogTitle>
                  <button
                    @click="showDetailsModal = false"
                    type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <XMarkIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="isLoadingDetails" class="p-8 text-center">
                <div class="inline-flex items-center gap-2">
                  <ArrowPathIcon class="h-5 w-5 animate-spin text-blue-900" />
                  <span class="text-sm text-gray-600">Loading details...</span>
                </div>
              </div>

              <!-- Activity Details Content -->
              <div v-else-if="detailedActivity" class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                  <!-- Activity Info -->
                  <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">
                      Activity Information
                    </h4>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        ID
                      </label>
                      <p class="text-sm text-gray-900 font-mono bg-gray-50 p-2 rounded">
                        {{ detailedActivity.id }}
                      </p>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        Description
                      </label>
                      <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">
                        {{ detailedActivity.description }}
                      </p>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        Event
                      </label>
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        getActivityTypeColor(detailedActivity).bg,
                        getActivityTypeColor(detailedActivity).text
                      ]">
                        {{ detailedActivity.event || 'N/A' }}
                      </span>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        Log Name
                      </label>
                      <p class="text-sm text-gray-900">
                        {{ detailedActivity.log_name || 'default' }}
                      </p>
                    </div>
                  </div>

                  <!-- Timestamps -->
                  <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">
                      Timestamps
                    </h4>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        Created At
                      </label>
                      <p class="text-sm text-gray-900">
                        {{ formatDateTime(detailedActivity.created_at) }}
                      </p>
                      <p class="text-xs text-gray-500 mt-1">
                        {{ formatRelativeTime(detailedActivity.created_at) }}
                      </p>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        Updated At
                      </label>
                      <p class="text-sm text-gray-900">
                        {{ formatDateTime(detailedActivity.updated_at) }}
                      </p>
                    </div>
                    
                    <div v-if="detailedActivity.batch_uuid">
                      <label class="block text-xs font-medium text-gray-500 mb-1">
                        Batch UUID
                      </label>
                      <p class="text-sm text-gray-900 font-mono truncate">
                        {{ detailedActivity.batch_uuid }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Causer Information -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                    Causer Information
                  </h4>
                  
                  <div v-if="detailedActivity.causer" class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center space-x-4">
                      <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                          <UserIcon class="h-5 w-5 text-blue-900" />
                        </div>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                          {{ detailedActivity.causer.name }}
                        </p>
                        <p class="text-sm text-gray-500 truncate">
                          {{ detailedActivity.causer.email }}
                        </p>
                        <div class="flex items-center mt-1">
                          <span class="text-xs text-gray-400">
                            ID: {{ detailedActivity.causer.id }}
                          </span>
                          <span class="mx-2 text-gray-300">•</span>
                          <span class="text-xs text-gray-400">
                            Type: {{ detailedActivity.causer_type }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-500">
                      No causer (System-generated activity)
                    </p>
                  </div>
                </div>

                <!-- Subject Information -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                    Subject Information
                  </h4>
                  
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">
                          Subject Type
                        </label>
                        <p class="text-sm text-gray-900">
                          {{ detailedActivity.subject_type || 'N/A' }}
                        </p>
                      </div>
                      
                      <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">
                          Subject ID
                        </label>
                        <p class="text-sm text-gray-900">
                          {{ detailedActivity.subject_id || 'N/A' }}
                        </p>
                      </div>
                    </div>
                    
                    <!-- Display subject details if loaded -->
                    <div v-if="detailedActivity.subject" class="mt-4 pt-4 border-t border-gray-200">
                      <label class="block text-xs font-medium text-gray-500 mb-2">
                        Subject Details
                      </label>
                      <pre class="text-xs text-gray-700 bg-gray-100 p-3 rounded overflow-x-auto">
{{ JSON.stringify(detailedActivity.subject, null, 2) }}
                      </pre>
                    </div>
                  </div>
                </div>

                <!-- Properties -->
                <div>
                  <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                    Properties
                  </h4>
                  
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div v-if="detailedProperties && Object.keys(detailedProperties).length > 0">
                      <pre class="text-sm text-gray-900 whitespace-pre-wrap overflow-x-auto">
{{ formatPropertiesForDisplay(detailedProperties) }}
                      </pre>
                    </div>
                    <div v-else>
                      <p class="text-sm text-gray-500 text-center py-4">
                        No properties available for this activity
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex justify-end">
                  <button
                    type="button"
                    class="rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    @click="showDetailsModal = false"
                  >
                    Close
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

    <!-- DELETE CONFIRMATION DIALOG -->
    <confirm-dialog
      @canceled="showDeleteConfirmation = false"
      @close="showDeleteConfirmation = false"
      @confirmed="confirmDelete"
      v-if="showDeleteConfirmation"
      :title="$t('gestlab.actions.confirmation_dialog_title.delete')"
      :description="$t('gestlab.actions.confirmation_dialog_description.delete')"
      :confirm="$t('gestlab.general.buttons.confirm')"
      :cancel="$t('gestlab.general.buttons.cancel')"
    >
      <div class="mt-4 space-y-4">
        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-800">
          <ExclamationTriangleIcon class="h-4 w-4" />
          <p>{{ $t('gestlab.general.labels.system_activity.delete_warning') }}</p>
        </div>
        
        <div v-if="selectedActivity" class="space-y-3">
          <div class="flex items-center gap-3 text-sm">
            <InformationCircleIcon class="h-5 w-5 text-blue-900 flex-shrink-0" />
            <p class="text-gray-700">
              {{ selectedActivity.description }}
            </p>
          </div>
          <div class="flex items-center gap-3 text-sm">
            <ClockIcon class="h-5 w-5 text-gray-500 flex-shrink-0" />
            <p class="text-gray-700">
              {{ formatDateTime(selectedActivity.created_at) }}
            </p>
          </div>
        </div>
      </div>
    </confirm-dialog>
  </div>
</template>

<style scoped>
/* Smooth transitions */
* {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom scrollbar for properties panel */
pre::-webkit-scrollbar {
  width: 6px;
}

pre::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

pre::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

pre::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
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

/* Gradient backgrounds */
.bg-gradient-to-r {
  background-image: linear-gradient(to right, var(--tw-gradient-stops));
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
