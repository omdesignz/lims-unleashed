<template>
  <div class="mt-6">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <RectangleGroupIcon class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-sm font-bold text-[var(--ds-text)]">
          {{ $t('gestlab.quick_menu.title') }}
          </h3>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ $t('gestlab.quick_menu.description') }}
          </p>
        </div>
        <span class="ds-badge ds-badge-neutral">
          {{ actions.length }}
        </span>
      </div>

      <button
        @click="isShowing = !isShowing"
        type="button"
        class="ds-button ds-button-secondary min-h-0 self-start px-3 py-2 text-xs"
      >
        <EyeIcon v-if="!isShowing" class="h-3.5 w-3.5" />
        <EyeSlashIcon v-else class="h-3.5 w-3.5" />
        {{ isShowing ? $t('gestlab.quick_menu.hide') : $t('gestlab.quick_menu.show') }}
      </button>
    </div>

    <transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isShowing"
        class="grid overflow-hidden rounded-lg border-l border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
      >
        <Link
          v-for="action in actions"
          :key="action.title"
          prefetch
          :href="action.href"
          class="group flex min-h-28 items-start gap-3 border-b border-r border-[var(--ds-border)] p-4 transition-colors hover:bg-[var(--ds-panel-raised)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[rgb(var(--primary-500-rgb))]"
        >
          <div class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel)]">
            <component :is="action.icon" class="h-4 w-4 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
              <h4 class="text-sm font-bold text-[var(--ds-text)] group-hover:text-[rgb(var(--primary-700-rgb))] dark:group-hover:text-cyan-200">{{ $t(action.title) }}</h4>
              <ChevronRightIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
            </div>
            <p class="mt-1 line-clamp-2 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ $t(action.text) }}</p>
          </div>
        </Link>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  ClipboardIcon,
  BanknotesIcon,
  ArchiveBoxIcon,
  UserGroupIcon,
  DocumentTextIcon,
  CubeIcon,
  DocumentCheckIcon,
  QueueListIcon,
  EyeIcon,
  EyeSlashIcon,
  ClipboardDocumentIcon,
  RectangleStackIcon,
  EyeDropperIcon,
  RectangleGroupIcon,
  InboxStackIcon,
  VariableIcon,
  ChartBarSquareIcon,
  Square2StackIcon,
  BeakerIcon,
  CubeTransparentIcon,
  ShieldCheckIcon,
  TagIcon,
  BuildingOffice2Icon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  defaultOpen: {
    type: Boolean,
    default: true,
  },
})

const isShowing = ref(props.defaultOpen)

const safeRoute = (name, params = undefined, fallback = '#') => {
  if (typeof route === 'function') {
    return route(name, params)
  }

  return fallback
}

const actions = [
  { title: 'gestlab.quick_menu.boards.customers.title', href: safeRoute('customers.index', undefined, '/customers'), icon: UserGroupIcon, text: 'gestlab.quick_menu.boards.customers.description' },
  { title: 'gestlab.quick_menu.boards.collections.title', href: safeRoute('directcollections.index', undefined, '/directcollections'), icon: ArchiveBoxIcon, text: 'gestlab.quick_menu.boards.collections.description' },
  { title: 'gestlab.quick_menu.boards.standards.title', href: safeRoute('standards.index', undefined, '/standards'), icon: DocumentCheckIcon, text: 'gestlab.quick_menu.boards.standards.description' },
  { title: 'gestlab.quick_menu.boards.invoices.title', href: safeRoute('invoices.index', undefined, '/invoices'), icon: BanknotesIcon, text: 'gestlab.quick_menu.boards.invoices.description' },
  { title: 'gestlab.quick_menu.boards.protocols.title', href: safeRoute('protocols.index', undefined, '/protocols'), icon: ClipboardDocumentIcon, text: 'gestlab.quick_menu.boards.protocols.description' },
  { title: 'gestlab.quick_menu.boards.matrixes.title', href: safeRoute('matrixes.index', undefined, '/matrixes'), icon: CubeTransparentIcon, text: 'gestlab.quick_menu.boards.matrixes.description' },
  { title: 'gestlab.quick_menu.boards.analysis.title', href: safeRoute('analysis.index', undefined, '/analysis'), icon: BeakerIcon, text: 'gestlab.quick_menu.boards.analysis.description' },
  { title: 'gestlab.quick_menu.boards.products.title', href: safeRoute('products.index', undefined, '/products'), icon: CubeIcon, text: 'gestlab.quick_menu.boards.products.description' },
  { title: 'gestlab.quick_menu.boards.departments.title', href: safeRoute('departments.index', undefined, '/departments'), icon: RectangleStackIcon, text: 'gestlab.quick_menu.boards.departments.description' },
  { title: 'gestlab.quick_menu.boards.profiles.title', href: safeRoute('profiles.index', undefined, '/profiles'), icon: QueueListIcon, text: 'gestlab.quick_menu.boards.profiles.description' },
  { title: 'gestlab.quick_menu.boards.samples.title', href: safeRoute('vap_samples.index', undefined, '/vap-samples'), icon: EyeDropperIcon, text: 'gestlab.quick_menu.boards.samples.description' },
  { title: 'gestlab.quick_menu.boards.analysis_reports.title', href: safeRoute('qualitycertificates.index', undefined, '/qualitycertificates'), icon: DocumentTextIcon, text: 'gestlab.quick_menu.boards.analysis_reports.description' },
  { title: 'gestlab.quick_menu.boards.kanban.title', href: safeRoute('boards', undefined, '/boards'), icon: ClipboardIcon, text: 'gestlab.quick_menu.boards.kanban.description' },
  { title: 'gestlab.quick_menu.boards.media.title', href: safeRoute('file-manager', undefined, '/file-manager'), icon: RectangleGroupIcon, text: 'gestlab.quick_menu.boards.media.description' },
  { title: 'gestlab.quick_menu.boards.inventory.title', href: safeRoute('vap-inventory.analytics.index', undefined, '/vap-inventory/analytics'), icon: InboxStackIcon, text: 'gestlab.quick_menu.boards.inventory.description' },
  { title: 'gestlab.quick_menu.boards.equipments.title', href: safeRoute('vap-inventory.items.index', { category_id: 1 }, '/vap-inventory/items?category_id=1'), icon: BuildingOffice2Icon, text: 'gestlab.quick_menu.boards.equipments.description' },
  { title: 'gestlab.quick_menu.boards.formulas.title', href: safeRoute('formulas.index', undefined, '/formulas'), icon: VariableIcon, text: 'gestlab.quick_menu.boards.formulas.description' },
  { title: 'gestlab.quick_menu.boards.metrics.title', href: safeRoute('metrics.index', undefined, '/metrics'), icon: ChartBarSquareIcon, text: 'gestlab.quick_menu.boards.metrics.description' },
  { title: 'gestlab.quick_menu.boards.proposals.title', href: safeRoute('vap-proposals.index', undefined, '/vap-proposals'), icon: Square2StackIcon, text: 'gestlab.quick_menu.boards.proposals.description' },
  { title: 'gestlab.quick_menu.boards.qms.title', href: safeRoute('qms.index', undefined, '/qms'), icon: ShieldCheckIcon, text: 'gestlab.quick_menu.boards.qms.description' },
  { title: 'gestlab.quick_menu.boards.labels.title', href: safeRoute('vap_labels.labels.index', undefined, '/vap-labels/labels'), icon: TagIcon, text: 'gestlab.quick_menu.boards.labels.description' },
]
</script>
