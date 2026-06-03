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
  { name: 'gestlab.stats.boards.analysis.title', icon: BeakerIcon, members: props.stats.analysis, accent: 'bg-[#143d37]' },
  { name: 'gestlab.stats.boards.collections.title', icon: ArchiveBoxIcon, members: props.stats.collections, accent: 'bg-[#d9b05f]' },
  { name: 'gestlab.stats.boards.customers.title', icon: UserGroupIcon, members: props.stats.customers, accent: 'bg-[#5f6f68]' },
  { name: 'gestlab.stats.boards.analysis_reports.title', icon: DocumentTextIcon, members: props.stats.certificates, accent: 'bg-[#0f766e]' },
  { name: 'gestlab.stats.boards.invoices.title', icon: BanknotesIcon, members: props.stats.invoices, accent: 'bg-[#b9852c]' },
  { name: 'gestlab.stats.boards.products.title', icon: CubeIcon, members: props.stats.products, accent: 'bg-[#315149]' },
  { name: 'gestlab.stats.boards.standards.title', icon: DocumentCheckIcon, members: props.stats.standards, accent: 'bg-[#809a8d]' },
  { name: 'gestlab.stats.boards.profiles.title', icon: QueueListIcon, members: props.stats.profiles, accent: 'bg-[#25443c]' },
]

const formatNumber = (num) => {
  if (!num) return '0'
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toLocaleString()
}
</script>

<template>
  <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
    <div
      v-for="(item, index) in items"
      :key="item.name"
      class="group relative overflow-hidden rounded-[1.5rem] border border-[#ded3bf] bg-[#fffdf7] p-4 shadow-[0_16px_45px_rgb(20_61_55/0.07)] ring-1 ring-white/70 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_24px_65px_rgb(20_61_55/0.14)] dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10"
      :class="'animate-fade-in-up delay-' + (index + 1)"
    >
      <span class="absolute inset-x-4 top-0 h-1 rounded-b-full" :class="item.accent" />
      <div class="flex items-start justify-between">
        <div class="rounded-2xl bg-[rgb(var(--primary-50-rgb))] p-2.5 text-[rgb(var(--primary-800-rgb))] ring-1 ring-[rgb(var(--primary-200-rgb)/0.65)] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:text-[rgb(var(--primary-100-rgb))] dark:ring-[rgb(var(--primary-300-rgb)/0.18)]">
          <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
        </div>
        <p class="text-2xl font-semibold text-[#15231f] tabular-nums dark:text-[#f7f1e7]">
          {{ formatNumber(item.members) }}
        </p>
      </div>
      <p class="mt-3 truncate text-xs font-semibold uppercase tracking-[0.14em] text-[#5f6f68] dark:text-[#a9bbb4]">
        {{ $t(item.name) }}
      </p>
    </div>
  </div>
</template>
