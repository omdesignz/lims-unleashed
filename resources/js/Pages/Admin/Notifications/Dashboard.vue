<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ChartBarIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.subtitle') }}
            <span class="font-semibold text-blue-900">
              {{ stats.total }}
            </span>
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.total_notifications') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('admin.notifications.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.buttons.create') }}
          </Link>
          <Link
            :href="route('admin.notifications.analytics')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ChartBarIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.buttons.analytics') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- STATS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Total Notifications Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <BellIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.total.title') }}
            </h2>
            <div class="rounded-full bg-white/20 p-2">
              <BellIcon class="h-5 w-5 text-white" />
            </div>
          </div>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.total }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.total.description') }}
          </p>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.unread') }}</span>
              <span class="font-semibold text-yellow-600">{{ stats.unread }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Read Rate Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CheckCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.read_rate.title') }}
            </h2>
            <div class="rounded-full bg-white/20 p-2">
              <CheckCircleIcon class="h-5 w-5 text-white" />
            </div>
          </div>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.read_rate }}%</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.read_rate.description') }}
          </p>
          <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
              <div 
                class="h-full bg-green-500 rounded-full transition-all duration-500" 
                :style="{ width: Math.min(stats.read_rate, 100) + '%' }"
              ></div>
            </div>
            <div class="mt-2 flex justify-between text-xs text-gray-500">
              <span>0%</span>
              <span>100%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Today's Notifications Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-500 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CalendarIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.today.title') }}
            </h2>
            <div class="rounded-full bg-white/20 p-2">
              <CalendarIcon class="h-5 w-5 text-white" />
            </div>
          </div>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.today }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.today.description') }}
          </p>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center gap-2">
              <ArrowTrendingUpIcon v-if="stats.today > 0" class="h-4 w-4 text-green-500" />
              <ArrowTrendingDownIcon v-else class="h-4 w-4 text-red-500" />
              <span class="text-sm text-gray-600">
                {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.comparison') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- This Month Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CalendarDaysIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.this_month.title') }}
            </h2>
            <div class="rounded-full bg-white/20 p-2">
              <CalendarDaysIcon class="h-5 w-5 text-white" />
            </div>
          </div>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.this_month }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.this_month.description') }}
          </p>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="text-xs text-gray-500">
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.stats.month_to_date') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN -->
      <div class="lg:col-span-2 space-y-6">
        <!-- RECENT NOTIFICATIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <BellAlertIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.admin.notifications.dashboard.recent.title') }}
              </h2>
              <Link 
                :href="route('admin.notifications.index')" 
                class="text-sm font-medium text-blue-100 hover:text-white transition-colors duration-200"
              >
                {{ $t('gestlab.general.labels.admin.notifications.dashboard.buttons.view_all') }} →
              </Link>
            </div>
          </div>

          <!-- Notifications List -->
          <div class="divide-y divide-gray-200">
            <div 
              v-for="notification in recentNotifications" 
              :key="notification.id"
              class="group relative p-6 hover:bg-gray-50 transition-colors duration-150"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div 
                    :class="[
                      'flex h-10 w-10 items-center justify-center rounded-full',
                      getNotificationIconClass(notification)
                    ]"
                  >
                    <BellIcon class="h-5 w-5 text-white" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <h3 class="text-sm font-semibold text-gray-900 truncate">
                        {{ notification.title }}
                      </h3>
                      <span :class="[
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                        getTypeBadgeClass(notification.type)
                      ]">
                        {{ getTypeLabel(notification.type) }}
                      </span>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                      <span>{{ $t('gestlab.general.labels.admin.notifications.dashboard.recent.sent_to') }}</span>
                      <span class="font-medium text-gray-900">{{ notification.user_name }}</span>
                      <span>•</span>
                      <span>{{ notification.created_at }}</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    notification.read_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    {{ notification.read_at ? $t('gestlab.general.labels.admin.notifications.status.read') : $t('gestlab.general.labels.admin.notifications.status.unread') }}
                  </span>
                  <Link 
                    :href="route('admin.notifications.show', notification.id)"
                    class="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200 rounded-full hover:bg-blue-50"
                    :title="$t('gestlab.general.labels.admin.notifications.dashboard.buttons.view_details')"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </Link>
                </div>
              </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="recentNotifications.length === 0" class="p-12 text-center">
              <BellSlashIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.admin.notifications.dashboard.recent.empty.title') }}
              </h3>
              <p class="mt-2 text-sm text-gray-500">
                {{ $t('gestlab.general.labels.admin.notifications.dashboard.recent.empty.description') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6">
        <!-- TOP SENDERS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserGroupIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.top_senders.title') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div 
                v-for="(sender, index) in stats.top_senders" 
                :key="index"
                class="group flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-all duration-200"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-900">
                    {{ getSenderRankEmoji(index + 1) }}
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ sender.name }}</h3>
                    <p class="text-xs text-gray-500">
                      {{ sender.count }} {{ $t('gestlab.general.labels.admin.notifications.dashboard.top_senders.notifications') }}
                    </p>
                  </div>
                </div>
                <div class="text-sm font-semibold text-blue-900">
                  #{{ index + 1 }}
                </div>
              </div>
              
              <!-- Empty State -->
              <div v-if="stats.top_senders.length === 0" class="text-center py-8">
                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.admin.notifications.dashboard.top_senders.empty.title') }}
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.admin.notifications.dashboard.top_senders.empty.description') }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BoltIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.dashboard.quick_actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link
              :href="route('admin.notifications.create')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.quick_actions.create_notification') }}
            </Link>
            
            <Link
              :href="route('admin.notifications.index')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ListBulletIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.quick_actions.view_all') }}
            </Link>
            
            <Link
              :href="route('admin.notifications.analytics')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ChartBarIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.dashboard.quick_actions.view_analytics') }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.admin.notifications.dashboard.footer.last_updated') }}: 
        <span class="font-medium text-gray-700">{{ new Date().toLocaleString() }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link } from '@inertiajs/vue3'
import {
  ChartBarIcon,
  BellIcon,
  PlusIcon,
  CheckCircleIcon,
  CalendarIcon,
  CalendarDaysIcon,
  BellAlertIcon,
  BellSlashIcon,
  EyeIcon,
  UserGroupIcon,
  BoltIcon,
  ListBulletIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  stats: Object,
  recentNotifications: Array,
  notificationTypes: Object,
})

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
    success: 'bg-green-100 text-green-800',
    error: 'bg-red-100 text-red-800',
    warning: 'bg-yellow-100 text-yellow-800',
    alert: 'bg-orange-100 text-orange-800',
    info: 'bg-blue-100 text-blue-800'
  }
  return classMap[type] || 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (type) => {
  const labelMap = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    alert: 'Alert',
    info: 'Info'
  }
  return labelMap[type] || 'Notification'
}

const getSenderRankEmoji = (position) => {
  const emojis = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣']
  return emojis[position - 1] || `${position}`
}
</script>
