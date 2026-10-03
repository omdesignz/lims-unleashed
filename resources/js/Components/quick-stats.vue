<script setup>
import {
  FlaskConical as BeakerIcon,
  Archive as ArchiveBoxIcon,
  Users as UserGroupIcon,
  FileText as DocumentTextIcon,
  Banknote as BanknotesIcon,
  Box as CubeIcon,
  FileCheck as DocumentCheckIcon,
  Rows3 as QueueListIcon,
} from '@lucide/vue'

const props = defineProps({
  stats: Object,
})

const items = [
  { name: 'gestlab.stats.boards.analysis.title', icon: BeakerIcon, members: props.stats.analysis, accent: 'bg-cyan-600' },
  { name: 'gestlab.stats.boards.collections.title', icon: ArchiveBoxIcon, members: props.stats.collections, accent: 'bg-amber-500' },
  { name: 'gestlab.stats.boards.customers.title', icon: UserGroupIcon, members: props.stats.customers, accent: 'bg-slate-500' },
  { name: 'gestlab.stats.boards.analysis_reports.title', icon: DocumentTextIcon, members: props.stats.certificates, accent: 'bg-emerald-600' },
  { name: 'gestlab.stats.boards.invoices.title', icon: BanknotesIcon, members: props.stats.invoices, accent: 'bg-indigo-600' },
  { name: 'gestlab.stats.boards.products.title', icon: CubeIcon, members: props.stats.products, accent: 'bg-sky-600' },
  { name: 'gestlab.stats.boards.standards.title', icon: DocumentCheckIcon, members: props.stats.standards, accent: 'bg-violet-600' },
  { name: 'gestlab.stats.boards.profiles.title', icon: QueueListIcon, members: props.stats.profiles, accent: 'bg-rose-600' },
]

const formatNumber = (num) => {
  if (!num) return '0'
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toLocaleString()
}
</script>

<template>
  <dl class="mt-4 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
    <div
      v-for="item in items"
      :key="item.name"
      class="relative border-b border-[var(--ds-border)] px-4 py-4 sm:nth-even:border-l xl:border-b-0 xl:border-l xl:first:border-l-0"
    >
      <span class="absolute inset-y-3 left-0 w-1 rounded-r" :class="item.accent" />
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
          <dt class="truncate text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t(item.name) }}</dt>
          <dd class="mt-2 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ formatNumber(item.members) }}</dd>
        </div>
        <component :is="item.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
      </div>
    </div>
  </dl>
</template>
