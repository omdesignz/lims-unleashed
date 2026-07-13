<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  ArchiveBoxIcon,
  BeakerIcon,
  ChartBarSquareIcon,
  ClipboardDocumentCheckIcon,
  DocumentCheckIcon,
  HomeIcon,
  ShieldCheckIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline'
import { usePermission } from '@/Composables/usePermissions'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
})

const emit = defineEmits(['open-command-palette', 'navigate'])
const page = usePage()
const { hasPermission } = usePermission()

const navigation = computed(() => [
  {
    label: 'Painel operacional',
    href: route('dashboard'),
    path: '/dashboard',
    icon: HomeIcon,
    show: true,
  },
  {
    label: 'Fila de amostras',
    href: route('vap_samples.index'),
    path: '/vap-samples',
    icon: BeakerIcon,
    show: hasPermission('view_samples'),
  },
  {
    label: 'Analises e resultados',
    href: route('analysis.index'),
    path: '/analysis',
    icon: ClipboardDocumentCheckIcon,
    show: hasPermission('view_analysis'),
  },
  {
    label: 'Certificados',
    href: route('qualitycertificates.index'),
    path: '/qualitycertificates',
    icon: DocumentCheckIcon,
    show: hasPermission('view_quality_certificates'),
  },
  {
    label: 'Inventario',
    href: route('vap-inventory.items.index'),
    path: '/vap-inventory',
    icon: ArchiveBoxIcon,
    show: hasPermission('view_inventory'),
  },
  {
    label: 'Qualidade',
    href: route('qms.index'),
    path: '/qms',
    icon: ShieldCheckIcon,
    show: hasPermission('view_activity_log'),
  },
  {
    label: 'Relatorios',
    href: route('report-studios.index'),
    path: '/report-studios',
    icon: ChartBarSquareIcon,
    show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings'),
  },
].filter((item) => item.show))

const isActive = (path) => page.url === path || page.url.startsWith(`${path}/`) || page.url.startsWith(`${path}?`)
</script>

<template>
  <nav class="flex min-h-0 flex-1 flex-col" aria-label="Navegacao principal">
    <div class="px-3 pb-2" :class="props.collapsed ? 'text-center' : ''">
      <p v-if="!props.collapsed" class="font-mono text-[0.65rem] font-semibold uppercase text-[var(--ds-text-soft)]">Trabalho</p>
      <span v-else class="mx-auto block h-px w-7 bg-[var(--ds-border)]" aria-hidden="true" />
    </div>

    <div class="space-y-1 px-2">
      <Link
        v-for="item in navigation"
        :key="item.href"
        :href="item.href"
        prefetch
        :title="props.collapsed ? item.label : undefined"
        :aria-current="isActive(item.path) ? 'page' : undefined"
        :class="[
          isActive(item.path)
            ? 'border-[rgb(var(--primary-200-rgb))] bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-900-rgb))] dark:border-[rgb(var(--primary-400-rgb)/0.2)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-white'
            : 'border-transparent text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]',
          props.collapsed ? 'justify-center px-2' : 'px-3',
          'group flex h-10 items-center gap-3 rounded-lg border text-sm font-semibold transition-colors',
        ]"
        @click="emit('navigate')"
      >
        <component
          :is="item.icon"
          :class="[
            isActive(item.path) ? 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-200-rgb))]' : 'text-[var(--ds-text-soft)] group-hover:text-[var(--ds-text-muted)]',
            'h-5 w-5 shrink-0',
          ]"
          aria-hidden="true"
        />
        <span v-if="!props.collapsed" class="truncate">{{ item.label }}</span>
      </Link>
    </div>

    <div class="mt-4 px-2">
      <button
        type="button"
        :title="props.collapsed ? 'Todos os modulos' : undefined"
        :class="[
          props.collapsed ? 'justify-center px-2' : 'px-3',
          'flex h-10 w-full items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-sm font-semibold text-[var(--ds-text-muted)] transition hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]',
        ]"
        @click="emit('open-command-palette')"
      >
        <Squares2X2Icon class="h-5 w-5 shrink-0" aria-hidden="true" />
        <span v-if="!props.collapsed" class="truncate">Todos os modulos</span>
        <kbd v-if="!props.collapsed" class="ml-auto rounded border border-[var(--ds-border)] px-1.5 py-0.5 font-mono text-[0.6rem] text-[var(--ds-text-soft)]">⌘K</kbd>
      </button>
    </div>
  </nav>
</template>
