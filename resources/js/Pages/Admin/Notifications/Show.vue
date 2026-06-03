<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BellIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.show.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.show.subtitle') }}
            <span class="font-semibold text-blue-900">
              {{ notification.title }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            notification.read_at 
              ? 'bg-green-100 text-green-800 ring-green-600/20' 
              : 'bg-yellow-100 text-yellow-800 ring-yellow-600/20'
          ]">
            {{ notification.read_at ? $t('gestlab.general.labels.admin.notifications.status.read') : $t('gestlab.general.labels.admin.notifications.status.unread') }}
          </span>
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            getTypeBadgeClass(notification.type)
          ]">
            {{ getTypeLabel(notification.type) }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN -->
      <div class="lg:col-span-2 space-y-6">
        <!-- NOTIFICATION DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.show.details.title') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- NOTIFICATION CONTENT SECTION -->
            <div class="space-y-6">
              <!-- Title & Message -->
              <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0">
                    <div :class="[
                      'flex h-12 w-12 items-center justify-center rounded-full',
                      getNotificationIconClass(notification)
                    ]">
                      <component :is="getNotificationIcon(notification)" class="h-6 w-6 text-white" />
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                      {{ notification.title }}
                    </h3>
                    <p class="text-gray-700 whitespace-pre-wrap">
                      {{ notification.message }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                      <span :class="[
                        'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                        getTypeBadgeClass(notification.type)
                      ]">
                        {{ getTypeLabel(notification.type) }}
                      </span>
                      <span :class="[
                        'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                        getPriorityBadgeClass(notification.priority)
                      ]">
                        {{ getPriorityLabel(notification.priority) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- INFORMATION GRID -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sender Information -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.admin.notifications.show.fields.sender') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                        <UserIcon class="h-5 w-5 text-blue-900" />
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ notification.sender_name }}</p>
                        <p class="text-xs text-gray-500">{{ notification.sender_email }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Recipient Information -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <UsersIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.admin.notifications.show.fields.recipient') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                        <UserIcon class="h-5 w-5 text-green-900" />
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ notification.user_name }}</p>
                        <p class="text-xs text-gray-500">{{ notification.user_email }}</p>
                        <p v-if="notification.is_admin_notification" class="text-xs text-blue-600 mt-1">
                          {{ $t('gestlab.general.labels.admin.notifications.show.admin_notification') }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Created Date -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <CalendarIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.admin.notifications.show.fields.created_at') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3">
                    <div class="flex items-center gap-3">
                      <CalendarIcon class="h-5 w-5 text-gray-400" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ formatDateTime(notification.created_at) }}</p>
                        <p class="text-xs text-gray-500">{{ notification.created_at_human }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Read Status -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <EyeIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.admin.notifications.show.fields.read_status') }}
                  </label>
                  <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3">
                    <div class="flex items-center gap-3">
                      <EyeIcon :class="[
                        'h-5 w-5',
                        notification.read_at ? 'text-green-500' : 'text-yellow-500'
                      ]" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">
                          {{ notification.read_at ? $t('gestlab.general.labels.admin.notifications.status.read') : $t('gestlab.general.labels.admin.notifications.status.unread') }}
                        </p>
                        <p v-if="notification.read_by" class="text-xs text-gray-500">
                          {{ $t('gestlab.general.labels.admin.notifications.show.read_by') }}: {{ notification.read_by.user }} 
                          <span class="text-gray-400">•</span> 
                          {{ notification.read_by.read_at_human }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- RAW DATA SECTION -->
              <div v-if="showRawData" class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CodeBracketIcon class="h-4 w-4 text-blue-900" />
                  {{ $t('gestlab.general.labels.admin.notifications.show.fields.raw_data') }}
                </label>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                  <pre class="text-xs text-gray-300 overflow-x-auto">{{ JSON.stringify(notification, null, 2) }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.admin.notifications.show.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              v-if="!notification.read_at"
              @click="markAsRead"
              :disabled="isProcessing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                isProcessing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ isProcessing ? $t('gestlab.general.labels.admin.notifications.show.buttons.processing') : $t('gestlab.general.labels.admin.notifications.show.buttons.mark_as_read') }}
            </button>
            
            <button 
              v-if="notification.read_at"
              @click="markAsUnread"
              :disabled="isProcessing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                isProcessing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-yellow-600 to-yellow-500 text-white hover:from-yellow-500 hover:to-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2'
              ]"
            >
              <EyeSlashIcon class="h-5 w-5" />
              {{ isProcessing ? $t('gestlab.general.labels.admin.notifications.show.buttons.processing') : $t('gestlab.general.labels.admin.notifications.show.buttons.mark_as_unread') }}
            </button>

            <button 
              @click="deleteNotification"
              :disabled="isProcessing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium shadow-sm transition-all duration-200',
                isProcessing
                  ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                  : 'border-red-300 bg-white text-red-700 hover:border-red-600 hover:text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
              ]"
            >
              <TrashIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.show.buttons.delete') }}
            </button>

            <button 
              @click="toggleRawData"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200'
              ]"
            >
              <CodeBracketIcon class="h-5 w-5" />
              {{ showRawData ? $t('gestlab.general.labels.admin.notifications.show.buttons.hide_raw_data') : $t('gestlab.general.labels.admin.notifications.show.buttons.show_raw_data') }}
            </button>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.show.status.title') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.show.status.read_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                notification.read_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ notification.read_at ? $t('gestlab.general.labels.admin.notifications.status.read') : $t('gestlab.general.labels.admin.notifications.status.unread') }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.show.status.priority') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                getPriorityBadgeClass(notification.priority)
              ]">
                {{ getPriorityLabel(notification.priority) }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.show.status.type') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                getTypeBadgeClass(notification.type)
              ]">
                {{ getTypeLabel(notification.type) }}
              </span>
            </div>

            <div v-if="notification.is_admin_notification" class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.show.status.source') }}</span>
              <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                {{ $t('gestlab.general.labels.admin.notifications.show.status.admin_sent') }}
              </span>
            </div>
          </div>
        </div>

        <!-- QUICK LINKS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ArrowLeftIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.show.quick_links.title') }}
          </h3>
          <div class="space-y-3">
            <Link
              :href="route('admin.notifications.index')"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 group"
            >
              <ArrowUturnLeftIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-600" />
              <span>{{ $t('gestlab.general.labels.admin.notifications.show.quick_links.back_to_list') }}</span>
            </Link>
            
            <Link
              :href="route('admin.notifications.create')"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:border-green-500 hover:bg-green-50 hover:text-green-700 transition-all duration-200 group"
            >
              <PlusIcon class="h-4 w-4 text-gray-400 group-hover:text-green-600" />
              <span>{{ $t('gestlab.general.labels.admin.notifications.show.quick_links.create_new') }}</span>
            </Link>
            
            <Link
              :href="route('admin.notifications.dashboard')"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:border-purple-500 hover:bg-purple-50 hover:text-purple-700 transition-all duration-200 group"
            >
              <ChartBarIcon class="h-4 w-4 text-gray-400 group-hover:text-purple-600" />
              <span>{{ $t('gestlab.general.labels.admin.notifications.show.quick_links.go_to_dashboard') }}</span>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.admin.notifications.show.footer.notification_id') }}: 
        <code class="ml-2 rounded bg-gray-100 px-2 py-1 text-xs font-mono text-gray-800">
          {{ notification.id }}
        </code>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500">
          {{ $t('gestlab.general.labels.admin.notifications.show.footer.last_updated') }}: 
          <span class="font-medium text-gray-700">{{ formatDateTime(notification.created_at) }}</span>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import {
  BellIcon,
  InformationCircleIcon,
  UserIcon,
  UsersIcon,
  CalendarIcon,
  EyeIcon,
  CheckCircleIcon,
  EyeSlashIcon,
  TrashIcon,
  Cog6ToothIcon,
  ArrowLeftIcon,
  ArrowUturnLeftIcon,
  PlusIcon,
  ChartBarIcon,
  CodeBracketIcon,
  ExclamationTriangleIcon,
  CheckBadgeIcon,
  XCircleIcon,
  EnvelopeIcon,
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  notification: {
    type: Object,
    required: true,
    default: () => ({
      id: '',
      title: '',
      message: '',
      type: 'info',
      priority: 'normal',
      sender_name: '',
      sender_email: '',
      user_name: '',
      user_email: '',
      read_at: null,
      read_by: null,
      created_at: '',
      created_at_human: '',
      is_admin_notification: false,
    })
  }
})

const isProcessing = ref(false)
const showRawData = ref(false)

// Helper Functions
const getNotificationIcon = (notification) => {
  const type = notification.type || 'info'
  const iconMap = {
    success: CheckBadgeIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    alert: BellIcon,
    info: InformationCircleIcon,
    email: EnvelopeIcon
  }
  return iconMap[type] || BellIcon
}

const getNotificationIconClass = (notification) => {
  const type = notification.type || 'info'
  const classMap = {
    success: 'bg-gradient-to-r from-green-600 to-green-500',
    error: 'bg-gradient-to-r from-red-600 to-red-500',
    warning: 'bg-gradient-to-r from-yellow-600 to-yellow-500',
    alert: 'bg-gradient-to-r from-orange-600 to-orange-500',
    info: 'bg-gradient-to-r from-blue-900 to-blue-800'
  }
  return classMap[type] || 'bg-gradient-to-r from-blue-900 to-blue-800'
}

const getTypeBadgeClass = (type) => {
  const classMap = {
    success: 'bg-green-100 text-green-800 ring-green-600/20',
    error: 'bg-red-100 text-red-800 ring-red-600/20',
    warning: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    alert: 'bg-orange-100 text-orange-800 ring-orange-600/20',
    info: 'bg-blue-100 text-blue-800 ring-blue-600/20'
  }
  return classMap[type] || 'bg-gray-100 text-gray-800 ring-gray-600/20'
}

const getTypeLabel = (type) => {
  const labelMap = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    alert: 'Alert',
    info: 'Information'
  }
  return labelMap[type] || 'Notification'
}

const getPriorityBadgeClass = (priority) => {
  const classMap = {
    low: 'bg-gray-100 text-gray-800 ring-gray-600/20',
    normal: 'bg-blue-100 text-blue-800 ring-blue-600/20',
    high: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    urgent: 'bg-red-100 text-red-800 ring-red-600/20'
  }
  return classMap[priority] || 'bg-blue-100 text-blue-800 ring-blue-600/20'
}

const getPriorityLabel = (priority) => {
  const labelMap = {
    low: 'Low Priority',
    normal: 'Normal Priority',
    high: 'High Priority',
    urgent: 'Urgent Priority'
  }
  return labelMap[priority] || 'Normal Priority'
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// Actions
const markAsRead = async () => {
  if (props.notification.read_at) return
  
  isProcessing.value = true
  try {
    await router.post(route('notifications.markAsRead', { notification: props.notification.id }), {}, {
      preserveScroll: true,
      onSuccess: () => {
        props.notification.read_at = new Date().toISOString()
        props.notification.read_by = {
          user: 'Admin',
          read_at_human: 'Just now'
        }
      },
      onFinish: () => {
        isProcessing.value = false
      }
    })
  } catch (error) {
    console.error('Error marking as read:', error)
    isProcessing.value = false
  }
}

const markAsUnread = async () => {
  if (!props.notification.read_at) return
  
  isProcessing.value = true
  try {
    await router.post(route('notifications.markAsUnread', { notification: props.notification.id }), {}, {
      preserveScroll: true,
      onSuccess: () => {
        props.notification.read_at = null
        props.notification.read_by = null
      },
      onFinish: () => {
        isProcessing.value = false
      }
    })
  } catch (error) {
    console.error('Error marking as unread:', error)
    isProcessing.value = false
  }
}

const deleteNotification = async () => {
  if (!confirm('Are you sure you want to delete this notification? This action cannot be undone.')) return
  
  isProcessing.value = true
  try {
    await router.delete(route('admin.notifications.destroy', { id: props.notification.id }), {
      preserveScroll: true,
      onSuccess: () => {
        router.visit(route('admin.notifications.index'))
      },
      onFinish: () => {
        isProcessing.value = false
      }
    })
  } catch (error) {
    console.error('Error deleting notification:', error)
    isProcessing.value = false
  }
}

const toggleRawData = () => {
  showRawData.value = !showRawData.value
}
</script>
