<script setup>
import {
  BeakerIcon,
  ArchiveBoxIcon,
  UserGroupIcon,
  DocumentTextIcon,
  BanknotesIcon,
  CubeIcon,
  DocumentCheckIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  stats: Object,
})

const items = [
  { name: 'gestlab.stats.boards.analysis.title', icon: BeakerIcon, members: props.stats.analysis, color: 'text-pink-600 dark:text-pink-400', bg: 'bg-pink-50 dark:bg-pink-500/10' },
  { name: 'gestlab.stats.boards.collections.title', icon: ArchiveBoxIcon, members: props.stats.collections, color: 'text-violet-600 dark:text-violet-400', bg: 'bg-violet-50 dark:bg-violet-500/10' },
  { name: 'gestlab.stats.boards.customers.title', icon: UserGroupIcon, members: props.stats.customers, color: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-500/10' },
  { name: 'gestlab.stats.boards.analysis_reports.title', icon: DocumentTextIcon, members: props.stats.certificates, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-500/10' },
  { name: 'gestlab.stats.boards.invoices.title', icon: BanknotesIcon, members: props.stats.invoices, color: 'text-sky-600 dark:text-sky-400', bg: 'bg-sky-50 dark:bg-sky-500/10' },
  { name: 'gestlab.stats.boards.products.title', icon: CubeIcon, members: props.stats.products, color: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-50 dark:bg-rose-500/10' },
  { name: 'gestlab.stats.boards.standards.title', icon: DocumentCheckIcon, members: props.stats.standards, color: 'text-teal-600 dark:text-teal-400', bg: 'bg-teal-50 dark:bg-teal-500/10' },
  { name: 'gestlab.stats.boards.profiles.title', icon: QueueListIcon, members: props.stats.profiles, color: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-500/10' },
]

const formatNumber = (num) => {
  if (!num) return '0'
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toLocaleString()
}
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
    <div
      v-for="(item, index) in items"
      :key="item.name"
      class="group card p-4 hover:shadow-md transition-shadow duration-200"
      :class="'animate-fade-in-up delay-' + (index + 1)"
    >
      <div class="flex items-start justify-between">
        <div class="rounded-lg p-2" :class="item.bg">
          <component :is="item.icon" class="h-5 w-5" :class="item.color" aria-hidden="true" />
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 tabular-nums">
          {{ formatNumber(item.members) }}
        </p>
      </div>
      <p class="mt-3 text-xs font-medium text-gray-500 dark:text-gray-400 truncate">
        {{ $t(item.name) }}
      </p>
    </div>
  </div>
</template>