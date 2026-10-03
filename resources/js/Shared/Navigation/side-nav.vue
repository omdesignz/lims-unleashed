<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  ArchiveBoxIcon,
  ArrowsRightLeftIcon,
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
    label: 'Rede de laboratórios',
    href: page.props.laboratory?.active_lab?.network_id ? route('lab-network.index', page.props.laboratory.active_lab.network_id) : '#',
    path: '/lab-networks',
    icon: Squares2X2Icon,
    show: Boolean(page.props.laboratory?.active_lab?.network_id),
  },
  {
    label: 'Visão geral',
    href: route('dashboard'),
    path: '/dashboard',
    icon: HomeIcon,
    show: true,
  },
  {
    label: 'Amostras',
    href: route('vap_samples.queue'),
    path: '/vap-samples',
    icon: BeakerIcon,
    show: hasPermission('view_samples'),
  },
  {
    label: 'Análises',
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
    label: 'Inventário',
    href: route('vap-inventory.items.index'),
    path: '/vap-inventory',
    icon: ArchiveBoxIcon,
    show: hasPermission('view_inventory'),
  },
  {
    label: 'Integration Hub',
    href: route('integration-hub.index'),
    path: '/integration-hub',
    icon: ArrowsRightLeftIcon,
    show: hasPermission('view_iequipments') || hasPermission('view_settings'),
  },
  {
    label: 'Qualidade',
    href: route('qms.index'),
    path: '/qms',
    icon: ShieldCheckIcon,
    show: hasPermission('view_activity_log'),
  },
  {
    label: 'Relatórios',
    href: route('report-studios.index'),
    path: '/report-studios',
    icon: ChartBarSquareIcon,
    show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings'),
  },
].filter((item) => item.show))

const isActive = (path) => page.url === path || page.url.startsWith(`${path}/`) || page.url.startsWith(`${path}?`)
</script>

<template>
  <nav class="lab-navigation" aria-label="Navegação principal">
    <section v-for="group in [{ label: 'O seu espaço', items: navigation.filter(item => ['/dashboard', '/vap-samples', '/analysis'].includes(item.path)) }, { label: 'Gestão', items: navigation.filter(item => !['/dashboard', '/vap-samples', '/analysis'].includes(item.path)) }]" :key="group.label">
      <p v-if="!props.collapsed" class="lab-kicker">{{ group.label }}</p>
      <div class="lab-nav">
        <Link v-for="item in group.items" :key="item.href" :href="item.href" :title="props.collapsed ? item.label : undefined" :aria-current="isActive(item.path) ? 'page' : undefined" @click="emit('navigate')">
          <component :is="item.icon" aria-hidden="true" />
          <span :class="props.collapsed ? 'sr-only' : ''">{{ item.label }}</span>
        </Link>
      </div>
    </section>
    <div class="lab-nav">
      <button type="button" :title="props.collapsed ? 'Todos os módulos' : undefined" @click="emit('open-command-palette')"><Squares2X2Icon aria-hidden="true" /><span :class="props.collapsed ? 'sr-only' : ''">Todos os módulos</span></button>
    </div>
  </nav>
</template>
