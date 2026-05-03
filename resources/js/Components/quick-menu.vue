<template>
  <div class="mt-6">
    <!-- Header -->
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 shadow-sm dark:bg-primary-900/20 dark:text-primary-300">
          <RectangleGroupIcon class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
          {{ $t('gestlab.quick_menu.title') }}
          </h3>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ $t('Atalhos operacionais para as áreas mais usadas da aplicação.') }}
          </p>
        </div>
        <span class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-900/30 dark:text-primary-200">
          {{ actions.length }}
        </span>
      </div>

      <button
        @click="isShowing = !isShowing"
        class="inline-flex items-center gap-1.5 self-start rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
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
          class="group block overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-primary-700"
          :class="'animate-fade-in-up delay-' + Math.min(index + 1, 8)"
        >
          <div class="flex items-start justify-between gap-3">
            <div
              class="inline-flex items-center justify-center rounded-2xl bg-primary-50 p-2.5 transition-colors duration-200 group-hover:bg-primary-100 dark:bg-primary-900/20 dark:group-hover:bg-primary-800/30"
            >
              <component
                :is="action.icon"
                class="h-5 w-5 text-primary-700 transition-transform duration-200 group-hover:scale-110 dark:text-primary-400"
                aria-hidden="true"
              />
            </div>
            <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
              {{ index + 1 }}
            </span>
          </div>

          <h4
            class="mt-4 text-sm font-semibold text-gray-900 transition-colors duration-150 group-hover:text-primary-700 dark:text-gray-100 dark:group-hover:text-primary-400"
          >
            {{ $t(action.title) }}
          </h4>
          <p
            class="mt-2 line-clamp-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
          >
            {{ $t(action.text) }}
          </p>

          <div
            class="mt-4 flex items-center text-xs font-medium text-primary-600 opacity-0 transition-opacity duration-200 group-hover:opacity-100 dark:text-primary-400"
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

const isShowing = ref(true)

const actions = [
  { title: 'gestlab.quick_menu.boards.customers.title', href: route('customers.index'), icon: UserGroupIcon, text: 'gestlab.quick_menu.boards.customers.description' },
  { title: 'gestlab.quick_menu.boards.collections.title', href: route('directcollections.index'), icon: ArchiveBoxIcon, text: 'gestlab.quick_menu.boards.collections.description' },
  { title: 'gestlab.quick_menu.boards.standards.title', href: route('standards.index'), icon: DocumentCheckIcon, text: 'gestlab.quick_menu.boards.standards.description' },
  { title: 'gestlab.quick_menu.boards.invoices.title', href: route('invoices.index'), icon: BanknotesIcon, text: 'gestlab.quick_menu.boards.invoices.description' },
  { title: 'gestlab.quick_menu.boards.protocols.title', href: route('protocols.index'), icon: ClipboardDocumentIcon, text: 'gestlab.quick_menu.boards.protocols.description' },
  { title: 'gestlab.quick_menu.boards.matrixes.title', href: route('matrixes.index'), icon: CubeTransparentIcon, text: 'gestlab.quick_menu.boards.matrixes.description' },
  { title: 'gestlab.quick_menu.boards.analysis.title', href: route('analysis.index'), icon: BeakerIcon, text: 'gestlab.quick_menu.boards.analysis.description' },
  { title: 'gestlab.quick_menu.boards.products.title', href: route('products.index'), icon: CubeIcon, text: 'gestlab.quick_menu.boards.products.description' },
  { title: 'gestlab.quick_menu.boards.departments.title', href: route('departments.index'), icon: RectangleStackIcon, text: 'gestlab.quick_menu.boards.departments.description' },
  { title: 'gestlab.quick_menu.boards.profiles.title', href: route('profiles.index'), icon: QueueListIcon, text: 'gestlab.quick_menu.boards.profiles.description' },
  { title: 'gestlab.quick_menu.boards.samples.title', href: route('samples.index'), icon: EyeDropperIcon, text: 'gestlab.quick_menu.boards.samples.description' },
  { title: 'gestlab.quick_menu.boards.analysis_reports.title', href: route('qualitycertificates.index'), icon: DocumentTextIcon, text: 'gestlab.quick_menu.boards.analysis_reports.description' },
  { title: 'gestlab.quick_menu.boards.kanban.title', href: route('boards'), icon: ClipboardIcon, text: 'gestlab.quick_menu.boards.kanban.description' },
  { title: 'gestlab.quick_menu.boards.media.title', href: route('file-manager'), icon: RectangleGroupIcon, text: 'gestlab.quick_menu.boards.media.description' },
  { title: 'gestlab.quick_menu.boards.inventory.title', href: route('vap-inventory.analytics.index'), icon: InboxStackIcon, text: 'gestlab.quick_menu.boards.inventory.description' },
  { title: 'Equipamentos', href: route('vap-inventory.items.index', { category_id: 1 }), icon: BuildingOffice2Icon, text: 'Gestão de equipamentos, calibração e manutenção.' },
  { title: 'gestlab.quick_menu.boards.formulas.title', href: route('formulas.index'), icon: VariableIcon, text: 'gestlab.quick_menu.boards.formulas.description' },
  { title: 'gestlab.quick_menu.boards.metrics.title', href: route('metrics.index'), icon: ChartBarSquareIcon, text: 'gestlab.quick_menu.boards.metrics.description' },
  { title: 'gestlab.quick_menu.boards.proposals.title', href: route('proposals.index'), icon: Square2StackIcon, text: 'gestlab.quick_menu.boards.proposals.description' },
  { title: 'QMS', href: route('qms.index'), icon: ShieldCheckIcon, text: 'Governança ISO 17025, responsabilidades e conformidade.' },
  { title: 'Etiquetas', href: route('vap_labels.labels.index'), icon: TagIcon, text: 'Templates, impressão e geração automática de etiquetas.' },
]
</script>
