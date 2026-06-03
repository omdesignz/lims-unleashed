<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ListBulletIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.index.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.index.subtitle') }}
            <span class="font-semibold text-blue-900">
              {{ notifications.total }}
            </span>
            {{ $t('gestlab.general.labels.admin.notifications.index.total_notifications') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('admin.notifications.export')"
            :data="{ ...filters }"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowDownTrayIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.index.buttons.export') }}
          </Link>
          <Link
            :href="route('admin.notifications.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.index.buttons.create') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 -mx-6 -mt-6 px-6 py-4 mb-6">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <FunnelIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.admin.notifications.index.filters.title') }}
        </h2>
      </div>

      <form @submit.prevent="applyFilters" class="space-y-6">
        <!-- MAIN FILTERS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Search -->
          <div class="space-y-2">
            <label for="search" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <MagnifyingGlassIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.search.label') }}
            </label>
            <input
              v-model="filterForm.search"
              type="text"
              id="search"
              :placeholder="$t('gestlab.general.labels.admin.notifications.index.filters.search.placeholder')"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            />
          </div>

          <!-- Type -->
          <div class="space-y-2">
            <label for="type" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <TagIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.type.label') }}
            </label>
            <select
              v-model="filterForm.type"
              id="type"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            >
              <option value="">{{ $t('gestlab.general.labels.admin.notifications.index.filters.type.all') }}</option>
              <option v-for="(typeData, typeKey) in notificationTypes" 
                      :key="typeKey" 
                      :value="typeKey"
                      class="py-2">
                {{ typeData.label }}
              </option>
            </select>
          </div>

          <!-- Status -->
          <div class="space-y-2">
            <label for="read_status" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <EyeIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.status.label') }}
            </label>
            <select
              v-model="filterForm.read_status"
              id="read_status"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            >
              <option value="">{{ $t('gestlab.general.labels.admin.notifications.index.filters.status.all') }}</option>
              <option value="read">{{ $t('gestlab.general.labels.admin.notifications.status.read') }}</option>
              <option value="unread">{{ $t('gestlab.general.labels.admin.notifications.status.unread') }}</option>
            </select>
          </div>

          <!-- User -->
          <div class="space-y-2">
            <label for="user_id" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <UserIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.user.label') }}
            </label>
            <select
              v-model="filterForm.user_id"
              id="user_id"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            >
              <option value="">{{ $t('gestlab.general.labels.admin.notifications.index.filters.user.all') }}</option>
              <option v-for="user in users" :key="user.id" :value="user.id" class="py-2">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>
        </div>

        <!-- DATE FILTERS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Date From -->
          <div class="space-y-2">
            <label for="date_from" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <CalendarIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.date_from.label') }}
            </label>
            <input
              v-model="filterForm.date_from"
              type="date"
              id="date_from"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            />
          </div>

          <!-- Date To -->
          <div class="space-y-2">
            <label for="date_to" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <CalendarIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.date_to.label') }}
            </label>
            <input
              v-model="filterForm.date_to"
              type="date"
              id="date_to"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            />
          </div>

          <!-- Actions -->
          <div class="flex items-end gap-3">
            <button
              type="submit"
              class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <MagnifyingGlassIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.buttons.apply') }}
            </button>
            <button
              type="button"
              @click="resetFilters"
              class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              {{ $t('gestlab.general.labels.admin.notifications.index.filters.buttons.reset') }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- NOTIFICATIONS TABLE CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <BellIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.index.table.title') }}
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ notifications.total }} {{ $t('gestlab.general.labels.admin.notifications.index.table.items') }})
              </span>
            </h2>
            <p class="text-sm text-gray-600 mt-1">
              {{ $t('gestlab.general.labels.admin.notifications.index.table.subtitle') }}
              <span class="font-semibold text-blue-900">{{ unreadCount }}</span>
              {{ $t('gestlab.general.labels.admin.notifications.index.table.unread_notifications') }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">
              {{ notifications.from || 0 }} - {{ notifications.to || 0 }} {{ $t('gestlab.general.labels.admin.notifications.index.table.of') }} {{ notifications.total }}
            </span>
          </div>
        </div>
      </div>

      <!-- TABLE -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.admin.notifications.index.table.headers.user') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.admin.notifications.index.table.headers.notification') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.admin.notifications.index.table.headers.type_priority') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.admin.notifications.index.table.headers.status') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.admin.notifications.index.table.headers.date') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.admin.notifications.index.table.headers.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="notification in notifications.data" 
              :key="notification.id" 
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <!-- User -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                      <UserIcon class="h-5 w-5 text-blue-900" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ notification.user_name }}</div>
                    <div class="text-sm text-gray-500">{{ notification.user_email }}</div>
                  </div>
                </div>
              </td>

              <!-- Notification -->
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900 mb-1">{{ notification.title }}</div>
                <div class="text-sm text-gray-600 line-clamp-2">{{ notification.message }}</div>
              </td>

              <!-- Type & Priority -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="space-y-2">
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    getTypeBadgeClass(notification.type)
                  ]">
                    {{ getTypeLabel(notification.type) }}
                  </span>
                  <div class="text-xs text-gray-500">
                    {{ getPriorityLabel(notification.priority) }}
                  </div>
                </div>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="space-y-1">
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    notification.read_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    {{ notification.read_at ? $t('gestlab.general.labels.admin.notifications.status.read') : $t('gestlab.general.labels.admin.notifications.status.unread') }}
                  </span>
                  <div v-if="notification.read_at" class="text-xs text-gray-500">
                    {{ formatRelativeTime(notification.read_at) }}
                  </div>
                </div>
              </td>

              <!-- Date -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ formatDate(notification.created_at) }}</div>
                <div class="text-xs text-gray-500">{{ notification.created_at_human }}</div>
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('admin.notifications.show', notification.id)"
                    class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                    :title="$t('gestlab.general.labels.admin.notifications.index.table.actions.view')"
                  >
                    <EyeIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.admin.notifications.index.table.actions.view') }}
                  </Link>
                  <button
                    @click="markAsRead(notification)"
                    v-if="!notification.read_at"
                    class="inline-flex items-center gap-1 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-medium text-green-900 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-colors duration-200"
                    :title="$t('gestlab.general.labels.admin.notifications.index.table.actions.mark_read')"
                  >
                    <CheckCircleIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.admin.notifications.index.table.actions.mark_read') }}
                  </button>
                  <button
                    @click="markAsUnread(notification)"
                    v-if="notification.read_at"
                    class="inline-flex items-center gap-1 rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-medium text-yellow-900 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:ring-offset-2 transition-colors duration-200"
                    :title="$t('gestlab.general.labels.admin.notifications.index.table.actions.mark_unread')"
                  >
                    <EyeSlashIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.admin.notifications.index.table.actions.mark_unread') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="notifications.data.length === 0" class="p-12 text-center">
        <BellSlashIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ hasFilters ? $t('gestlab.general.labels.admin.notifications.index.empty.filtered.title') : $t('gestlab.general.labels.admin.notifications.index.empty.title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ hasFilters ? $t('gestlab.general.labels.admin.notifications.index.empty.filtered.description') : $t('gestlab.general.labels.admin.notifications.index.empty.description') }}
        </p>
        <div class="mt-6">
          <button
            v-if="hasFilters"
            @click="resetFilters"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowPathIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.index.empty.filtered.reset_filters') }}
          </button>
          <Link
            v-else
            :href="route('admin.notifications.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.index.empty.create_first') }}
          </Link>
        </div>
      </div>

      <!-- PAGINATION -->
      <div v-if="notifications.links.length > 3" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            {{ $t('gestlab.general.labels.admin.notifications.index.pagination.showing') }}
            <span class="font-semibold">{{ notifications.from || 0 }}</span>
            {{ $t('gestlab.general.labels.admin.notifications.index.pagination.to') }}
            <span class="font-semibold">{{ notifications.to || 0 }}</span>
            {{ $t('gestlab.general.labels.admin.notifications.index.pagination.of') }}
            <span class="font-semibold">{{ notifications.total }}</span>
            {{ $t('gestlab.general.labels.admin.notifications.index.pagination.results') }}
          </div>
          <div class="flex space-x-2">
            <template v-for="(link, index) in notifications.links" :key="index">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                  'inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-200',
                  link.active
                    ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white ring-2 ring-blue-900 ring-offset-2'
                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-gray-500 bg-gray-100"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
  ListBulletIcon,
  ArrowDownTrayIcon,
  PlusIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  TagIcon,
  EyeIcon,
  UserIcon,
  CalendarIcon,
  BellIcon,
  BellSlashIcon,
  CheckCircleIcon,
  EyeSlashIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  notifications: Object,
  users: Array,
  filters: Object,
  notificationTypes: Object,
})

const filterForm = useForm({
  search: props.filters.search || '',
  type: props.filters.type || '',
  read_status: props.filters.read_status || '',
  user_id: props.filters.user_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
})

const hasFilters = computed(() => {
  return Object.values(props.filters).some(value => value !== '')
})

const unreadCount = computed(() => {
  return props.notifications.data.filter(n => !n.read_at).length
})

const getTypeBadgeClass = (type) => {
  const classMap = {
    info: 'bg-blue-100 text-blue-800',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    error: 'bg-red-100 text-red-800',
    alert: 'bg-orange-100 text-orange-800',
  }
  return classMap[type] || 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (type) => {
  const labelMap = {
    info: 'Info',
    success: 'Success',
    warning: 'Warning',
    error: 'Error',
    alert: 'Alert',
  }
  return labelMap[type] || 'Notification'
}

const getPriorityLabel = (priority) => {
  const labels = {
    low: 'Low Priority',
    normal: 'Normal Priority',
    high: 'High Priority',
    urgent: 'Urgent Priority',
  }
  return labels[priority] || 'Normal Priority'
}

const formatRelativeTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffMinutes = Math.floor(diffTime / (1000 * 60))
  
  if (diffMinutes < 60) return `${diffMinutes} min ago`
  if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)} hours ago`
  return `${Math.floor(diffMinutes / 1440)} days ago`
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const applyFilters = () => {
  filterForm.get(route('admin.notifications.index'))
}

const resetFilters = () => {
  filterForm.search = ''
  filterForm.type = ''
  filterForm.read_status = ''
  filterForm.user_id = ''
  filterForm.date_from = ''
  filterForm.date_to = ''
  applyFilters()
}

const markAsRead = (notification) => {
  router.post(route('notifications.markAsRead', { notification: notification.id }), {
    preserveScroll: true,
    onSuccess: () => {
      notification.read_at = new Date().toISOString()
    }
  })
}

const markAsUnread = (notification) => {
  router.post(route('notifications.markAsUnread', { notification: notification.id }), {
    preserveScroll: true,
    onSuccess: () => {
      notification.read_at = null
    }
  })
}
</script>
