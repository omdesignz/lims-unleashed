<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BellIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.notifications.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.notifications.subtitle') }}
            <span class="font-semibold text-blue-900">{{ totalNotifications }}</span>
            {{ $t('gestlab.general.labels.notifications.notifications_total') }}
            <span v-if="unreadCount > 0" class="ml-2 font-semibold text-blue-900">
              ({{ unreadCount }} {{ $t('gestlab.general.labels.notifications.unread') }})
            </span>
          </p>
        </div>
        
        <div class="flex items-center gap-3">
          <!-- Mark All as Read Button -->
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            :disabled="isProcessing"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
              isProcessing
                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700' 
            ]"
          >
            <CheckCircleIcon v-if="!isProcessing" class="h-4 w-4" />
            <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
            {{ isProcessing ? $t('gestlab.general.labels.notifications.processing') : $t('gestlab.general.labels.notifications.mark_all_read') }}
          </button>
          
          <!-- Clear All Button -->
          <button
            v-if="notifications.length > 0"
            @click="clearAllNotifications"
            :disabled="isProcessing"
            :class="[
              'inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
              isProcessing
                ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                : 'border-gray-300 bg-white text-gray-700 hover:border-red-600 hover:text-red-600 hover:bg-red-50'
            ]"
          >
            <TrashIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.notifications.clear_all') }}
          </button>
        </div>
      </div>
    </div>

    <!-- NOTIFICATIONS CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN - Notifications List -->
      <div class="lg:col-span-2 space-y-6">
        <!-- FILTERS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex flex-wrap items-center gap-3">
            <!-- Filter Toggles -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="filter in filters"
                :key="filter.id"
                @click="activeFilter = filter.id"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                  activeFilter === filter.id
                    ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                <component :is="filter.icon" class="h-3 w-3" />
                {{ filter.label }}
                <span v-if="filter.count > 0" class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-white/20">
                  {{ filter.count }}
                </span>
              </button>
            </div>
            
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px]">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="search"
                :placeholder="$t('gestlab.general.labels.notifications.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
        </div>

        <!-- NOTIFICATIONS LIST CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- List Header -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <BellAlertIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.notifications.recent_notifications') }}
              </h2>
              <div class="text-sm text-blue-100">
                {{ filteredNotifications.length }} {{ $t('gestlab.general.labels.notifications.notifications_found') }}
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredNotifications.length === 0" class="p-12 text-center">
            <BellSlashIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ getEmptyStateTitle }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ getEmptyStateDescription }}
            </p>
          </div>

          <!-- Notifications List -->
          <div v-else class="divide-y divide-gray-200 max-h-[600px] overflow-y-auto">
            <div
              v-for="notification in filteredNotifications"
              :key="notification.id"
              :class="[
                'group relative p-6 hover:bg-gray-50 transition-colors duration-150 cursor-pointer',
                !notification.read_at ? 'bg-blue-50/30' : 'bg-white'
              ]"
              @click="markAsRead(notification)"
            >
              <div class="flex items-start gap-4">
                <!-- Notification Icon -->
                <div class="flex-shrink-0">
                  <div 
                    :class="[
                      'flex h-10 w-10 items-center justify-center rounded-full',
                      getNotificationIconClass(notification)
                    ]"
                  >
                    <component :is="getNotificationIcon(notification)" class="h-5 w-5 text-white" />
                  </div>
                </div>

                <!-- Notification Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ notification.data.title || $t('gestlab.general.labels.notifications.no_title') }}
                      </h3>
                      <p class="mt-1 text-xs text-gray-500 flex items-center gap-1">
                        <ClockIcon class="h-3 w-3" />
                        {{ formatRelativeTime(notification.created_at) }}
                      </p>
                    </div>
                    
                    <!-- Unread Badge -->
                    <div v-if="!notification.read_at" class="flex items-center">
                      <span class="inline-flex h-2 w-2 rounded-full bg-gradient-to-r from-blue-900 to-blue-800 animate-pulse">
                        <span class="sr-only">{{ $t('gestlab.general.labels.notifications.unread') }}</span>
                      </span>
                    </div>
                  </div>

                  <p class="mt-2 text-sm text-gray-700 line-clamp-2">
                    {{ notification.data.message || notification.data.body || '' }}
                  </p>

                  <!-- Notification Metadata -->
                  <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 font-medium',
                      getTypeBadgeClass(notification)
                    ]">
                      {{ getTypeText(notification) }}
                    </span>
                    
                    <span class="text-gray-500">
                      {{ formatDate(notification.created_at) }}
                    </span>
                    
                    <span v-if="notification.data.sender" class="text-gray-500 flex items-center gap-1">
                      <UserIcon class="h-3 w-3" />
                      {{ notification.data.sender }}
                    </span>
                  </div>

                  <!-- Actions -->
                  <div class="mt-4 flex items-center gap-3">
                    <button
                      v-if="!notification.read_at"
                      @click.stop="markAsRead(notification)"
                      class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-900 hover:bg-blue-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
                    >
                      <CheckCircleIcon class="h-3 w-3" />
                      {{ $t('gestlab.general.labels.notifications.mark_as_read') }}
                    </button>
                    
                    <button
                      @click.stop="markAsUnread(notification)"
                      v-if="notification.read_at"
                      class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
                    >
                      <EyeSlashIcon class="h-3 w-3" />
                      {{ $t('gestlab.general.labels.notifications.mark_as_unread') }}
                    </button>

                    <button
                      v-if="getNotificationTargetUrl(notification)"
                      @click.stop="openNotificationTarget(notification)"
                      class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                    >
                      <BellAlertIcon class="h-3 w-3" />
                      Abrir registo
                    </button>
                    
                    <button
                      @click.stop="deleteNotification(notification)"
                      class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 ml-auto"
                    >
                      <TrashIcon class="h-3 w-3" />
                      {{ $t('gestlab.general.labels.notifications.delete') }}
                    </button>
                  </div>
                </div>

                <!-- Quick Actions Dropdown -->
                <div class="absolute right-4 top-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <Menu as="div" class="relative">
                    <MenuButton class="p-1 rounded-full text-gray-400 hover:text-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2">
                      <EllipsisVerticalIcon class="h-5 w-5" />
                    </MenuButton>
                    <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2">
                      <MenuItem v-slot="{ active }">
                        <button
                          @click.stop="!notification.read_at ? markAsRead(notification) : markAsUnread(notification)"
                          :class="[
                            active ? 'bg-gray-50' : '',
                            'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2'
                          ]"
                        >
                          <component :is="!notification.read_at ? CheckCircleIcon : EyeSlashIcon" class="h-4 w-4" />
                          {{ !notification.read_at ? $t('gestlab.general.labels.notifications.mark_as_read') : $t('gestlab.general.labels.notifications.mark_as_unread') }}
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button
                          @click.stop="deleteNotification(notification)"
                          :class="[
                            active ? 'bg-gray-50' : '',
                            'w-full text-left px-4 py-2 text-sm text-red-600 flex items-center gap-2'
                          ]"
                        >
                          <TrashIcon class="h-4 w-4" />
                          {{ $t('gestlab.general.labels.notifications.delete') }}
                        </button>
                      </MenuItem>
                    </MenuItems>
                  </Menu>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <Pagination
          v-if="props.pagination.total > props.pagination.per_page"
          :links="props.pagination.links"
          :from="props.pagination.from"
          :to="props.pagination.to"
          :total="props.pagination.total"
          :current_page="props.pagination.current_page"
          :last_page="props.pagination.last_page"
          :per_page="props.pagination.per_page"
          class="mt-4"
        />
      </div>

      <!-- RIGHT COLUMN - Stats & Actions -->
      <div class="space-y-6">
        <!-- STATS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.notifications.stats_title') }}
          </h3>
          <div class="space-y-4">
            <!-- Total Notifications -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <BellIcon class="h-4 w-4 text-blue-900" />
                </div>
                <span class="text-sm text-gray-700">{{ $t('gestlab.general.labels.notifications.total') }}</span>
              </div>
              <span class="text-lg font-bold text-blue-900">{{ totalNotifications }}</span>
            </div>
            
            <!-- Unread Notifications -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                  <BellAlertIcon class="h-4 w-4 text-yellow-600" />
                </div>
                <span class="text-sm text-gray-700">{{ $t('gestlab.general.labels.notifications.unread') }}</span>
              </div>
              <span class="text-lg font-bold text-yellow-600">{{ unreadCount }}</span>
            </div>
            
            <!-- Today's Notifications -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                  <CalendarIcon class="h-4 w-4 text-green-600" />
                </div>
                <span class="text-sm text-gray-700">{{ $t('gestlab.general.labels.notifications.today') }}</span>
              </div>
              <span class="text-lg font-bold text-green-600">{{ todayCount }}</span>
            </div>
            
            <!-- This Week -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                  <CalendarDaysIcon class="h-4 w-4 text-purple-600" />
                </div>
                <span class="text-sm text-gray-700">{{ $t('gestlab.general.labels.notifications.this_week') }}</span>
              </div>
              <span class="text-lg font-bold text-purple-600">{{ thisWeekCount }}</span>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BoltIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.notifications.quick_actions') }}
          </h3>
          <div class="space-y-3">
            <button
              @click="markAllAsRead"
              :disabled="unreadCount === 0 || isProcessing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                unreadCount === 0 || isProcessing
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
              ]"
            >
              <CheckCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.notifications.mark_all_read') }}
            </button>
            
            <button
              @click="clearAllRead"
              :disabled="readCount === 0 || isProcessing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                readCount === 0 || isProcessing
                  ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                  : 'border-gray-300 bg-white text-gray-700 hover:border-red-600 hover:text-red-600 hover:bg-red-50'
              ]"
            >
              <TrashIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.notifications.clear_all_read') }}
            </button>
            
            <button
              @click="openNotificationSettings"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium shadow-sm hover:bg-gray-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <Cog6ToothIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.notifications.notification_settings') }}
            </button>
          </div>
        </div>

        <!-- NOTIFICATION TYPES CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <TagIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.notifications.types_title') }}
          </h3>
          <div class="space-y-2">
            <div
              v-for="type in notificationTypes"
              :key="type.id"
              class="flex items-center justify-between py-2"
            >
              <div class="flex items-center gap-2">
                <span :class="['inline-flex h-3 w-3 rounded-full', type.color]"></span>
                <span class="text-sm text-gray-700">{{ type.label }}</span>
              </div>
              <span class="text-sm font-semibold text-gray-900">{{ type.count }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { router } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import Pagination from '@/Components/pagination.vue'
import {
  BellIcon,
  BellAlertIcon,
  BellSlashIcon,
  CheckCircleIcon,
  TrashIcon,
  MagnifyingGlassIcon,
  ClockIcon,
  UserIcon,
  EyeSlashIcon,
  EllipsisVerticalIcon,
  ChartBarIcon,
  CalendarIcon,
  CalendarDaysIcon,
  BoltIcon,
  Cog6ToothIcon,
  TagIcon,
  ArrowPathIcon,
  EnvelopeIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  CheckBadgeIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  },
  pagination: {
    type: Object,
    default: () => ({
      links: [],
      from: 0,
      to: 0,
      total: 0,
      current_page: 1,
      last_page: 1,
      per_page: 20
    })
  }
})

// State
const isProcessing = ref(false)
const activeFilter = ref('all')
const searchQuery = ref('')
const notifications = ref(props.notifications)

// Watch for prop changes (when paginating)
watch(() => props.notifications, (newNotifications) => {
  notifications.value = newNotifications
}, { deep: true })

// Computed Properties
const totalNotifications = computed(() => props.pagination.total || notifications.value.length)
const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)
const readCount = computed(() => notifications.value.filter(n => n.read_at).length)

const todayCount = computed(() => {
  const today = new Date().toDateString()
  return notifications.value.filter(n => {
    const notificationDate = new Date(n.created_at).toDateString()
    return notificationDate === today
  }).length
})

const thisWeekCount = computed(() => {
  const oneWeekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
  return notifications.value.filter(n => new Date(n.created_at) > oneWeekAgo).length
})

const filteredNotifications = computed(() => {
  let filtered = notifications.value
  
  // Apply filter
  switch (activeFilter.value) {
    case 'unread':
      filtered = filtered.filter(n => !n.read_at)
      break
    case 'read':
      filtered = filtered.filter(n => n.read_at)
      break
    case 'today':
      const today = new Date().toDateString()
      filtered = filtered.filter(n => new Date(n.created_at).toDateString() === today)
      break
    case 'important':
      filtered = filtered.filter(n => (n.data && (n.data.priority === 'high' || n.data.type === 'alert')))
      break
  }
  
  // Apply search
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    filtered = filtered.filter(n => {
      // Safely access notification data
      const title = n.data?.title || ''
      const message = n.data?.message || ''
      const body = n.data?.body || ''
      const sender = n.data?.sender || ''
      
      return title.toLowerCase().includes(query) ||
             message.toLowerCase().includes(query) ||
             body.toLowerCase().includes(query) ||
             sender.toLowerCase().includes(query)
    })
  }
  
  return filtered
})

const filters = computed(() => [
  { id: 'all', label: 'Todas', icon: BellIcon, count: notifications.value.length },
  { id: 'unread', label: 'Não lidas', icon: BellAlertIcon, count: unreadCount.value },
  { id: 'read', label: 'Lidas', icon: CheckCircleIcon, count: readCount.value },
  { id: 'today', label: 'Hoje', icon: CalendarIcon, count: todayCount.value },
  { id: 'important', label: 'Importantes', icon: ExclamationTriangleIcon, count: notifications.value.filter(n => n.data?.priority === 'high').length }
])

const notificationTypes = computed(() => {
  const types = {
    info: { id: 'info', label: 'Informação', color: 'bg-blue-500', count: 0 },
    success: { id: 'success', label: 'Sucesso', color: 'bg-green-500', count: 0 },
    warning: { id: 'warning', label: 'Aviso', color: 'bg-yellow-500', count: 0 },
    error: { id: 'error', label: 'Erro', color: 'bg-red-500', count: 0 },
    alert: { id: 'alert', label: 'Alerta', color: 'bg-orange-500', count: 0 }
  }
  
  notifications.value.forEach(n => {
    const type = n.data?.type || 'info'
    if (types[type]) {
      types[type].count++
    } else {
      types.info.count++
    }
  })
  
  return Object.values(types).filter(t => t.count > 0)
})

const getEmptyStateTitle = computed(() => {
  if (searchQuery.value.trim()) return 'Nenhuma notificação encontrada'
  if (activeFilter.value !== 'all') {
    const filterLabels = {
      'unread': 'não lidas',
      'read': 'lidas',
      'today': 'de hoje',
      'important': 'importantes'
    }
    return `Nenhuma notificação ${filterLabels[activeFilter.value] || activeFilter.value}`
  }
  return 'Nenhuma notificação'
})

const getEmptyStateDescription = computed(() => {
  if (searchQuery.value.trim()) return 'Tente buscar com outros termos'
  if (activeFilter.value !== 'all') return 'Mude o filtro para ver outras notificações'
  return 'As notificações aparecerão aqui quando você tiver alguma'
})

// Helper Functions
const getNotificationIcon = (notification) => {
  const type = notification.data?.type || 'info'
  const iconMap = {
    success: CheckBadgeIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    alert: BellAlertIcon,
    info: InformationCircleIcon,
    email: EnvelopeIcon
  }
  return iconMap[type] || BellIcon
}

const getNotificationIconClass = (notification) => {
  const type = notification.data?.type || 'info'
  const classMap = {
    success: 'bg-gradient-to-r from-green-600 to-green-500',
    error: 'bg-gradient-to-r from-red-600 to-red-500',
    warning: 'bg-gradient-to-r from-yellow-600 to-yellow-500',
    alert: 'bg-gradient-to-r from-orange-600 to-orange-500',
    info: 'bg-gradient-to-r from-blue-900 to-blue-800'
  }
  return classMap[type] || 'bg-gradient-to-r from-blue-900 to-blue-800'
}

const getTypeBadgeClass = (notification) => {
  const type = notification.data?.type || 'info'
  const classMap = {
    success: 'bg-green-100 text-green-800',
    error: 'bg-red-100 text-red-800',
    warning: 'bg-yellow-100 text-yellow-800',
    alert: 'bg-orange-100 text-orange-800',
    info: 'bg-blue-100 text-blue-800'
  }
  return classMap[type] || 'bg-gray-100 text-gray-800'
}

const getTypeText = (notification) => {
  const type = notification.data?.type || 'info'
  const textMap = {
    success: 'Sucesso',
    error: 'Erro',
    warning: 'Aviso',
    alert: 'Alerta',
    info: 'Informação'
  }
  return textMap[type] || 'Notificação'
}

const formatRelativeTime = (dateString) => {
  const now = new Date()
  const date = new Date(dateString)
  const diffTime = Math.abs(now - date)
  const diffMinutes = Math.floor(diffTime / (1000 * 60))
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffMinutes < 1) return 'Agora'
  if (diffMinutes < 60) return `${diffMinutes} min atrás`
  if (diffHours < 24) return `${diffHours} h atrás`
  if (diffDays === 1) return 'Ontem'
  if (diffDays < 7) return `${diffDays} dias atrás`
  return formatDate(dateString)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

// Actions
const markAsRead = async (notification) => {
  if (notification.read_at) return
  
  isProcessing.value = true
  try {
    await router.post(route('notifications.read', { notification: notification.id }), {}, {
      preserveScroll: true,
      onSuccess: () => {
        notification.read_at = new Date().toISOString()
      },
      onError: () => {
        // Handle error if needed
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

const markAsUnread = async (notification) => {
  if (!notification.read_at) return
  
  isProcessing.value = true
  try {
    await router.post(route('notifications.unread', { notification: notification.id }), {}, {
      preserveScroll: true,
      onSuccess: () => {
        notification.read_at = null
      },
      onError: () => {
        // Handle error if needed
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

const markAllAsRead = async () => {
  if (unreadCount.value === 0) return
  
  if (!confirm('Tem certeza que deseja marcar todas as notificações como lidas?')) return
  
  isProcessing.value = true
  try {
    await router.post(route('notifications.read-all'), {}, {
      preserveScroll: true,
      onSuccess: () => {
        notifications.value.forEach(n => {
          if (!n.read_at) {
            n.read_at = new Date().toISOString()
          }
        })
      },
      onError: () => {
        // Handle error if needed
      },
      onFinish: () => {
        isProcessing.value = false
      }
    })
  } catch (error) {
    console.error('Error marking all as read:', error)
    isProcessing.value = false
  }
}

const deleteNotification = async (notification) => {
  if (!confirm('Tem certeza que deseja apagar esta notificação?')) return
  
  isProcessing.value = true
  try {
    await router.delete(route('notifications.delete', { id: notification.id }), {
      preserveScroll: true,
      onSuccess: () => {
        const index = notifications.value.findIndex(n => n.id === notification.id)
        if (index !== -1) {
          notifications.value.splice(index, 1)
        }
      },
      onError: () => {
        // Handle error if needed
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

const clearAllNotifications = async () => {
  if (notifications.value.length === 0) return
  
  if (!confirm('Tem certeza que deseja apagar todas as notificações?')) return
  
  isProcessing.value = true
  try {
    await router.delete(route('notifications.clear-all'), {
      preserveScroll: true,
      onSuccess: () => {
        notifications.value = []
      },
      onError: () => {
        // Handle error if needed
      },
      onFinish: () => {
        isProcessing.value = false
      }
    })
  } catch (error) {
    console.error('Error clearing all notifications:', error)
    isProcessing.value = false
  }
}

const getNotificationTargetUrl = (notification) => {
  return notification?.data?.need_url
    || notification?.data?.analysis_url
    || notification?.data?.collection_url
    || notification?.data?.worksheet_url
    || null
}

const openNotificationTarget = async (notification) => {
  const targetUrl = getNotificationTargetUrl(notification)

  if (!targetUrl) {
    return
  }

  if (!notification.read_at) {
    await markAsRead(notification)
  }

  router.visit(targetUrl)
}

const clearAllRead = async () => {
  const readNotifications = notifications.value.filter(n => n.read_at)
  if (readNotifications.length === 0) return
  
  if (!confirm('Tem certeza que deseja apagar todas as notificações lidas?')) return
  
  isProcessing.value = true
  try {
    await router.delete(route('notifications.clear-read'), {
      preserveScroll: true,
      onSuccess: () => {
        notifications.value = notifications.value.filter(n => !n.read_at)
      },
      onError: () => {
        // Handle error if needed
      },
      onFinish: () => {
        isProcessing.value = false
      }
    })
  } catch (error) {
    console.error('Error clearing read notifications:', error)
    isProcessing.value = false
  }
}

const openNotificationSettings = () => {
  router.get(route('notification-settings'))
}

// Lifecycle
onMounted(() => {
  // Initialize from props
  notifications.value = props.notifications
})
</script>

<style scoped>
/* Custom scrollbar for notifications list */
.max-h-\[600px\]::-webkit-scrollbar {
  width: 6px;
}

.max-h-\[600px\]::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.max-h-\[600px\]::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.max-h-\[600px\]::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Smooth transitions */
button, div, input {
  transition: all 0.2s ease-in-out;
}

/* Line clamp for notification content */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Pulse animation for unread badge */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Focus states for accessibility */
button:focus-visible {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

/* Loading spinner */
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

/* Ensure proper spacing for pagination */
.mt-4 {
  margin-top: 1rem;
}
</style>
