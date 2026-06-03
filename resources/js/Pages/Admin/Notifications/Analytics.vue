<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ChartBarIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.analytics.subtitle') }}
            <span class="font-semibold text-blue-900">
              {{ stats.total_sent }}
            </span>
            {{ $t('gestlab.general.labels.admin.notifications.analytics.notifications_sent') }}
            <span class="font-semibold text-blue-900 ml-2">
              {{ stats.read_rate }}%
            </span>
            {{ $t('gestlab.general.labels.admin.notifications.analytics.read_rate') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Period Selector -->
          <div class="relative">
            <select
              v-model="selectedPeriod"
              @change="updatePeriod"
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            >
              <option value="day">{{ $t('gestlab.general.labels.admin.notifications.analytics.periods.day') }}</option>
              <option value="week">{{ $t('gestlab.general.labels.admin.notifications.analytics.periods.week') }}</option>
              <option value="month">{{ $t('gestlab.general.labels.admin.notifications.analytics.periods.month') }}</option>
              <option value="quarter">{{ $t('gestlab.general.labels.admin.notifications.analytics.periods.quarter') }}</option>
              <option value="year">{{ $t('gestlab.general.labels.admin.notifications.analytics.periods.year') }}</option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
          </div>
          <Link
            :href="route('admin.notifications.dashboard')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.buttons.back_to_dashboard') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- KEY METRICS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Total Sent -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <BellIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.total_sent.title') }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.total_sent }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.total_sent.description') }}
          </p>
        </div>
      </div>

      <!-- Total Read -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <CheckCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.total_read.title') }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.total_read }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.total_read.description') }}
          </p>
        </div>
      </div>

      <!-- Read Rate -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-500 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.read_rate.title') }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.read_rate }}%</p>
          <div class="mt-4">
            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
              <div 
                class="h-full bg-purple-500 rounded-full transition-all duration-500" 
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

      <!-- Average Read Time -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <ClockIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.avg_read_time.title') }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-3xl font-bold text-gray-900 mb-2">{{ stats.avg_read_time }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.analytics.metrics.avg_read_time.description') }}
          </p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN -->
      <div class="lg:col-span-2 space-y-6">
        <!-- DELIVERY TREND CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <ArrowTrendingUpIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.admin.notifications.analytics.delivery_trend.title') }}
              </h2>
              <span class="text-sm font-medium text-blue-100">
                {{ formatDateRange }}
              </span>
            </div>
          </div>
          <div class="p-6">
            <!-- Simple Bar Chart (can be replaced with Chart.js) -->
            <div class="space-y-4">
              <div 
                v-for="(data, date) in stats.delivery_trend" 
                :key="date"
                class="group"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-900">{{ formatChartDate(date) }}</span>
                  <span class="text-sm text-gray-500">
                    {{ data.sent }} {{ $t('gestlab.general.labels.admin.notifications.analytics.delivery_trend.sent') }},
                    {{ data.read }} {{ $t('gestlab.general.labels.admin.notifications.analytics.delivery_trend.read') }}
                  </span>
                </div>
                <div class="h-8 w-full bg-gray-100 rounded-lg overflow-hidden flex">
                  <div 
                    :style="{ width: getBarWidth(data.sent, data.read) }" 
                    class="h-full bg-gradient-to-r from-blue-900 to-blue-800 transition-all duration-500"
                    :title="`Sent: ${data.sent}`"
                  ></div>
                  <div 
                    :style="{ width: getBarWidth(data.read, data.sent) }" 
                    class="h-full bg-gradient-to-r from-green-600 to-green-500 transition-all duration-500"
                    :title="`Read: ${data.read}`"
                  ></div>
                </div>
              </div>
              
              <!-- Empty State -->
              <div v-if="Object.keys(stats.delivery_trend).length === 0" class="text-center py-12">
                <ChartBarIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.admin.notifications.analytics.delivery_trend.empty.title') }}
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.admin.notifications.analytics.delivery_trend.empty.description') }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- NOTIFICATION TYPES CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <TagIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.analytics.notification_types.title') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div 
                v-for="(count, type) in stats.notification_types" 
                :key="type"
                class="group flex items-center justify-between p-4 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-all duration-200"
              >
                <div class="flex items-center gap-3">
                  <div 
                    :class="[
                      'flex h-10 w-10 items-center justify-center rounded-full',
                      getTypeIconClass(type)
                    ]"
                  >
                    <BellIcon class="h-5 w-5 text-white" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ getTypeLabel(type) }}</h3>
                    <p class="text-xs text-gray-500">{{ getTypeDescription(type) }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-gray-900">{{ count }}</p>
                  <p class="text-xs text-gray-500">
                    {{ calculatePercentage(count, stats.total_sent) }}% {{ $t('gestlab.general.labels.admin.notifications.analytics.of_total') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6">
        <!-- TOP USERS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserGroupIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.analytics.top_users.title') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div 
                v-for="(user, index) in stats.top_users" 
                :key="user.user_id"
                class="group flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-all duration-200"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-900">
                    {{ index + 1 }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ user.user_name }}</h3>
                    <p class="text-xs text-gray-500 truncate">{{ user.user_email }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-gray-900">{{ user.notification_count }}</p>
                  <p class="text-xs text-gray-500">{{ user.read_rate }}% {{ $t('gestlab.general.labels.admin.notifications.analytics.read') }}</p>
                </div>
              </div>
              
              <!-- Empty State -->
              <div v-if="stats.top_users.length === 0" class="text-center py-8">
                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.admin.notifications.analytics.top_users.empty.title') }}
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.admin.notifications.analytics.top_users.empty.description') }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- PERFORMANCE INSIGHTS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <LightBulbIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.insights.title') }}
          </h3>
          <div class="space-y-4">
            <!-- Read Rate Insight -->
            <div class="rounded-lg bg-blue-50 p-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                  <ChartBarIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.admin.notifications.analytics.insights.read_rate.title') }}</h4>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ getReadRateInsight(stats.read_rate) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Average Read Time Insight -->
            <div class="rounded-lg bg-green-50 p-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                  <ClockIcon class="h-5 w-5 text-green-900" />
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.admin.notifications.analytics.insights.avg_read_time.title') }}</h4>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ getReadTimeInsight(stats.avg_read_time) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Recommendation -->
            <div class="rounded-lg bg-yellow-50 p-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100">
                  <LightBulbIcon class="h-5 w-5 text-yellow-900" />
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.admin.notifications.analytics.insights.recommendation.title') }}</h4>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ getRecommendation(stats) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BoltIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.analytics.actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link
              :href="route('admin.notifications.export', { period: selectedPeriod })"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.analytics.actions.export_data') }}
            </Link>
            
            <Link
              :href="route('admin.notifications.create')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.analytics.actions.create_notification') }}
            </Link>
            
            <Link
              :href="route('admin.notifications.index')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ListBulletIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.analytics.actions.view_all') }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.admin.notifications.analytics.footer.data_range') }}: 
        <span class="font-medium text-gray-700">{{ formatDateRange }}</span>
        <span class="mx-4">•</span>
        {{ $t('gestlab.general.labels.admin.notifications.analytics.footer.last_updated') }}: 
        <span class="font-medium text-gray-700">{{ new Date().toLocaleString() }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import {
  ChartBarIcon,
  BellIcon,
  ArrowLeftIcon,
  ChevronDownIcon,
  CheckCircleIcon,
  ClockIcon,
  ArrowTrendingUpIcon,
  TagIcon,
  UserGroupIcon,
  LightBulbIcon,
  BoltIcon,
  ArrowDownTrayIcon,
  PlusIcon,
  ListBulletIcon,
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  stats: Object,
  period: String,
  dateRange: Array,
})

const selectedPeriod = ref(props.period || 'week')

const formatDateRange = computed(() => {
  const [start, end] = props.dateRange
  const startDate = new Date(start).toLocaleDateString()
  const endDate = new Date(end).toLocaleDateString()
  return `${startDate} - ${endDate}`
})

const formatChartDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const getBarWidth = (value, max) => {
  const total = value + max
  if (total === 0) return '0%'
  return `${(value / total) * 100}%`
}

const getTypeIconClass = (type) => {
  const classMap = {
    info: 'bg-gradient-to-r from-blue-900 to-blue-800',
    success: 'bg-gradient-to-r from-green-600 to-green-500',
    warning: 'bg-gradient-to-r from-yellow-600 to-yellow-500',
    error: 'bg-gradient-to-r from-red-600 to-red-500',
    alert: 'bg-gradient-to-r from-orange-600 to-orange-500',
  }
  return classMap[type] || 'bg-gradient-to-r from-blue-900 to-blue-800'
}

const getTypeLabel = (type) => {
  const labelMap = {
    info: 'Information',
    success: 'Success',
    warning: 'Warning',
    error: 'Error',
    alert: 'Alert',
  }
  return labelMap[type] || 'Notification'
}

const getTypeDescription = (type) => {
  const descriptions = {
    info: 'General information notifications',
    success: 'Success and completion notifications',
    warning: 'Warning and caution notifications',
    error: 'Error and failure notifications',
    alert: 'Important alert notifications',
  }
  return descriptions[type] || 'System notifications'
}

const calculatePercentage = (value, total) => {
  if (total === 0) return 0
  return Math.round((value / total) * 100)
}

const getReadRateInsight = (readRate) => {
  if (readRate >= 80) return 'Excellent read rate! Users are highly engaged with notifications.'
  if (readRate >= 60) return 'Good read rate. Consider optimizing notification timing.'
  if (readRate >= 40) return 'Average read rate. Try improving notification relevance.'
  return 'Low read rate. Review notification content and targeting.'
}

const getReadTimeInsight = (readTime) => {
  if (readTime.includes('seconds')) return 'Very quick response time. Users are actively checking notifications.'
  if (readTime.includes('minutes')) return 'Good response time. Notifications are being read promptly.'
  return 'Consider sending notifications at more optimal times.'
}

const getRecommendation = (stats) => {
  if (stats.read_rate < 50) {
    return 'Focus on improving notification relevance and timing to increase read rates.'
  }
  if (stats.top_users.length > 0 && stats.top_users[0].read_rate < 60) {
    return 'Target high-volume users with more personalized notifications.'
  }
  return 'Continue current strategy. Consider A/B testing different notification types.'
}

const updatePeriod = () => {
  router.get(route('admin.notifications.analytics', { period: selectedPeriod.value }))
}
</script>
