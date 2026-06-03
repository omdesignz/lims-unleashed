<template>
  <div class="mt-6">
    <!-- Header -->
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] shadow-sm ring-1 ring-[rgb(var(--primary-200-rgb)/0.65)] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:text-[rgb(var(--primary-100-rgb))] dark:ring-[rgb(var(--primary-300-rgb)/0.18)]">
          <RectangleGroupIcon class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
          {{ $t('gestlab.quick_menu.title') }}
          </h3>
          <p class="mt-1 text-xs text-[#5f6f68] dark:text-[#a9bbb4]">
            {{ $t('gestlab.quick_menu.description') }}
          </p>
        </div>
        <span class="inline-flex items-center rounded-full bg-[rgb(var(--primary-100-rgb))] px-3 py-1 text-xs font-semibold text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.16)] dark:text-[rgb(var(--primary-100-rgb))]">
          {{ actions.length }}
        </span>
      </div>

      <button
        @click="isShowing = !isShowing"
        type="button"
        class="inline-flex items-center gap-1.5 self-start rounded-2xl border border-[#d8cbb8] bg-[#fffdf7] px-3 py-2 text-xs font-semibold text-[#31413b] shadow-sm transition-colors duration-150 hover:bg-[#f7f1e7] hover:text-[rgb(var(--primary-800-rgb))] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#d7e2dd] dark:hover:bg-[#16342e]"
      >
        <EyeIcon v-if="!isShowing" class="h-3.5 w-3.5" />
        <EyeSlashIcon v-else class="h-3.5 w-3.5" />
        {{ isShowing ? $t('gestlab.quick_menu.hide') : $t('gestlab.quick_menu.show') }}
      </button>
    </div>

    <!-- Grid -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="isShowing"
        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
      >
        <Link
          v-for="(action, index) in actions"
          :key="action.title"
          prefetch
          :href="action.href"
          class="group block overflow-hidden rounded-[1.5rem] border border-[#ded3bf] bg-[#fffdf7] p-4 shadow-[0_16px_45px_rgb(20_61_55/0.07)] ring-1 ring-white/70 transition duration-200 hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb))] hover:shadow-[0_24px_65px_rgb(20_61_55/0.14)] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.26)] focus:ring-offset-2 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 dark:hover:border-[rgb(var(--primary-500-rgb)/0.55)] dark:focus:ring-offset-[#07110f]"
          :class="'animate-fade-in-up delay-' + Math.min(index + 1, 8)"
        >
          <div class="flex items-start justify-between gap-3">
            <div
              class="inline-flex items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] p-2.5 transition-colors duration-200 group-hover:bg-[rgb(var(--primary-100-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:group-hover:bg-[rgb(var(--primary-500-rgb)/0.22)]"
            >
              <component
                :is="action.icon"
                class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] transition-transform duration-200 group-hover:scale-110 dark:text-[rgb(var(--primary-200-rgb))]"
                aria-hidden="true"
              />
            </div>
            <span class="rounded-full bg-[#f7f1e7] px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-[#5f6f68] dark:bg-[#10231f] dark:text-[#a9bbb4]">
              {{ index + 1 }}
            </span>
          </div>

          <h4
            class="mt-4 text-sm font-semibold text-[#15231f] transition-colors duration-150 group-hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#f7f1e7] dark:group-hover:text-[rgb(var(--primary-200-rgb))]"
          >
            {{ $t(action.title) }}
          </h4>
          <p
            class="mt-2 line-clamp-2 text-xs leading-relaxed text-[#5f6f68] dark:text-[#a9bbb4]"
          >
            {{ $t(action.text) }}
          </p>

          <div
            class="mt-4 flex items-center text-xs font-semibold text-[rgb(var(--primary-700-rgb))] opacity-0 transition-opacity duration-200 group-hover:opacity-100 dark:text-[rgb(var(--primary-200-rgb))]"
          >
            <span>{{ $t('gestlab.quick_menu.access') }}</span>
            <svg
              class="ml-1 h-3.5 w-3.5 group-hover:translate-x-1 transition-transform duration-200"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M14 5l7 7m0 0l-7 7m7-7H3"
              />
            </svg>
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
