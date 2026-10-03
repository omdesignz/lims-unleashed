<template>
  <div class="lims-app-shell pl-shell" :data-theme-preset="themePreset">
    <backend-modal />
    <ToastList />

    <div v-if="impersonation" class="ds-impersonation-banner relative z-[60] flex flex-wrap items-center justify-center gap-3 px-4 py-2">
      <span>{{ trans('gestlab.general.labels.impersonation.title') }} · {{ trans('gestlab.general.labels.impersonation.description') }} {{ auth?.user?.name }}</span>
      <button type="button" class="ds-button ds-button-ghost h-7 min-h-7 text-[var(--pl-bg)]" @click="router.post(route('users.stopimpersonating'), {}, { preserveState: false, replace: true })">
        {{ trans('gestlab.general.buttons.leave_impersonation') }}
      </button>
    </div>

    <area-bar
      :areas="navAreas"
      :active-area-key="activeAreaKey"
      :unread-count="unreadNotificationCount"
      :is-dark="isDark"
      :can-manage-branding="Boolean(activeLab?.can_manage_branding)"
      @open-command-palette="openCommandPalette"
      @open-menu="sidebarOpen = true"
      @toggle-theme="toggleTheme"
      @switch-language="switchLanguage"
      @open-branding="openBranding"
    />

    <div class="pl-body">
      <aside id="area-column" class="pl-side" :aria-label="activeArea?.label || 'Navegação'">
        <app-sidebar :areas="navAreas" :active-area-key="activeAreaKey" />
      </aside>

      <main ref="stageContent" class="pl-main" :data-template="pageTemplate">
        <nav v-if="!planoPages.includes(page.component)" class="pl-crumbs pl-page-crumbs" aria-label="Localização">
          <template v-for="(crumb, index) in crumbs" :key="`${crumb.title}-${index}`">
            <span v-if="index" aria-hidden="true">/</span>
            <Link v-if="crumb.url && !crumb.current" :href="crumb.url">{{ crumb.title }}</Link>
            <span v-else :aria-current="crumb.current ? 'page' : undefined">{{ crumb.title }}</span>
          </template>
        </nav>
        <div :class="{ 'lims-backoffice-content': !planoPages.includes(page.component) }" :data-module-family="moduleFamily">
          <confirm-dialog v-if="showSessionModal" :open="showSessionModal" :title="$t('Session Expiring Soon')" :description="$t('You will be logged out due to inactivity')" variant="warning" :hide-buttons="true" size="sm:max-w-xl" @canceled="showSessionModal = false">
            <p class="mt-4 text-sm text-[var(--pl-muted)]">{{ $t('For your security, this session will end in :seconds seconds.', { seconds: remainingTime }) }} {{ $t('Move your mouse or press any key to continue working.') }}</p>
          </confirm-dialog>
          <slot />
        </div>
      </main>
    </div>

    <!-- Below 768px the areas move to a bottom bar of five: four areas and the menu. -->
    <nav class="pl-bottom" aria-label="Áreas">
      <Link v-for="area in navAreas.slice(0, 4)" :key="area.key" :href="area.href" class="pl-bottom-item" :aria-current="area.key === activeAreaKey ? 'page' : undefined">{{ area.label }}</Link>
      <button type="button" class="pl-bottom-item" aria-controls="area-drawer" @click="sidebarOpen = true">Menu</button>
    </nav>

    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog as="div" class="relative z-50" @close="sidebarOpen = false">
        <TransitionChild as="template" enter="transition-opacity duration-200 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity duration-150 ease-out" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 flex">
          <TransitionChild as="template" enter="transition-transform duration-[220ms] ease-[cubic-bezier(0.32,0.72,0,1)]" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition-transform duration-150 ease-[cubic-bezier(0.32,0.72,0,1)]" leave-from="translate-x-0" leave-to="-translate-x-full">
            <DialogPanel id="area-drawer" class="pl-drawer">
              <app-sidebar
                mobile
                :areas="navAreas"
                :active-area-key="activeAreaKey"
                @close="sidebarOpen = false"
                @navigate="sidebarOpen = false"
              />
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <Dialog :open="brandingOpen" class="relative z-[80]" @close="brandingOpen = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4"><DialogPanel class="ds-modal-panel w-full max-w-md p-6">
        <DialogTitle class="pl-d3">Selo do laboratório</DialogTitle>
        <p class="mt-2 text-sm leading-6 text-[var(--pl-muted)]">{{ activeLab?.name }}. A cor identifica o laboratório no seu selo da coluna; acções e estados mantêm as cores VAP.</p>
        <form class="mt-5 space-y-4" @submit.prevent="saveBranding">
          <div class="flex items-center justify-between gap-4 border border-[var(--pl-line)] px-4 py-3">
            <div>
              <p class="pl-k">Cor do selo</p>
              <p class="mt-1.5 font-mono text-xs uppercase text-[var(--pl-faint)]">{{ brandingForm.primary_color }}</p>
            </div>
            <ColorInput v-model="brandingForm.primary_color" aria-label="Cor do selo" />
          </div>
          <p v-if="brandingForm.errors.primary_color" class="ds-field-error" role="alert">{{ brandingForm.errors.primary_color }}</p>
          <p class="text-xs text-[var(--pl-faint)]">{{ activeLab?.inherited_color ? 'Actualmente herdada da rede.' : 'Cor própria deste laboratório.' }}</p>
          <div class="flex flex-wrap justify-end gap-2 pt-1"><button type="button" class="ds-button ds-button-ghost mr-auto" :disabled="brandingForm.processing" @click="saveBranding(true)">Herdar da rede</button><button type="button" class="ds-button ds-button-quiet" @click="brandingOpen = false">Cancelar</button><button type="submit" class="ds-button ds-button-primary" :disabled="brandingForm.processing">{{ brandingForm.processing ? 'A guardar…' : 'Guardar' }}</button></div>
        </form>
      </DialogPanel></div>
    </Dialog>

    <!-- Opened from the keyboard many times a day: it appears at once, without choreography. -->
    <Dialog :open="commandPaletteOpen" as="div" class="relative z-[70]" @close="commandPaletteOpen = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 z-[70] overflow-y-auto p-4 sm:p-8 md:p-[12vh]">
        <DialogPanel class="ds-command-palette mx-auto max-w-xl overflow-hidden">
          <div class="flex items-center gap-3 border-b border-[var(--pl-line)] px-4">
            <MagnifyingGlassIcon class="h-4 w-4 shrink-0 text-[var(--pl-faint)]" aria-hidden="true" />
            <BaseInput ref="commandPaletteInput" v-model="commandPaletteQuery" type="search" data-bare class="h-12 min-w-0 flex-1 border-0 bg-transparent px-0 text-sm text-[var(--pl-fg)] outline-none placeholder:text-[var(--pl-faint)] focus:ring-0" placeholder="Procurar módulos e registos…" aria-label="Procurar módulos e registos" role="combobox" aria-expanded="true" aria-controls="command-palette-results" :aria-activedescendant="activeCommand ? `command-${activeCommandIndex}` : undefined" @keydown.enter.prevent="activateCommandPaletteResult" @keydown.down.prevent="moveCommandSelection(1)" @keydown.up.prevent="moveCommandSelection(-1)" />
            <kbd class="pl-kbd">Esc</kbd>
          </div>
          <div id="command-palette-results" ref="commandPaletteResults" class="max-h-[60vh] overflow-y-auto p-1 sm:max-h-[26rem]" role="listbox" aria-label="Resultados">
            <template v-if="filteredCommandGroups.length">
              <section v-for="group in filteredCommandGroups" :key="group.label">
                <p class="pl-menu-heading">{{ group.label }}</p>
                <button v-for="command in group.items" :id="`command-${command.index}`" :key="`${group.label}-${command.href}-${command.label}`" type="button" role="option" class="ds-command-palette-item" :aria-selected="command.index === activeCommandIndex" @click="visitCommand(command)" @mousemove="activeCommandIndex = command.index">
                  <span class="min-w-0 flex-1 truncate text-left">{{ command.label }}</span>
                  <span class="hidden truncate font-mono text-[11px] text-[var(--pl-faint)] sm:block">{{ command.path }}</span>
                </button>
              </section>
            </template>
            <div v-else class="px-6 py-12 text-center">
              <p class="pl-k">Nenhum módulo encontrado</p>
              <p class="mt-2 text-sm text-[var(--pl-muted)]">Experimente outro termo ou verifique as suas permissões.</p>
            </div>
          </div>
          <div class="flex items-center gap-4 border-t border-[var(--pl-line)] px-4 py-2.5">
            <span class="pl-k flex items-center gap-1.5"><kbd class="pl-kbd">↑</kbd><kbd class="pl-kbd">↓</kbd>Navegar</span>
            <span class="pl-k flex items-center gap-1.5"><kbd class="pl-kbd">↵</kbd>Abrir</span>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch, onMounted, onUnmounted } from 'vue'
import { useIdle, useCounter } from '@vueuse/core'
import appSidebar from '../Navigation/app-sidebar.vue'
import areaBar from '../Navigation/area-bar.vue'
import ToastList from '@/Components/toast-list.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { Search as MagnifyingGlassIcon } from '@lucide/vue'
import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import { animate } from 'motion-v'
import { usePermission } from '@/Composables/usePermissions'
import { isThemeShortcut, useTheme } from '@/Composables/useTheme'
import { trans, loadLanguageAsync } from 'laravel-vue-i18n'
import backendModal from '@/Components/backend-modal.vue'
import { getEcho } from '@/lib/echo'
import toast from '@/Stores/toast'
import { durations, easeOut, prefersReducedMotion } from '@/Support/motion'

const { hasPermission } = usePermission()

const props = defineProps({
  auth: Object,
  impersonation: Boolean,
})

const { isDark, toggle: toggleTheme } = useTheme(props.auth?.user?.theme, Boolean(props.auth?.user))
const page = usePage()
const unreadNotificationCount = ref(props.auth?.user?.unread_notifications?.length ?? 0)
let realtimeNotificationChannel = null
const settings = computed(() => page.props?.settings ?? {})
const laboratory = computed(() => page.props.laboratory ?? { labs: [], active_lab: null })
const activeLab = computed(() => laboratory.value.active_lab)
const brandingOpen = ref(false)
const brandingForm = useForm({ primary_color: '#0757b5' })
function openBranding() {
  brandingForm.primary_color = activeLab.value.primary_color
  brandingForm.clearErrors()
  brandingOpen.value = true
}
function saveBranding(inherit = false) {
  brandingForm.transform((data) => ({ primary_color: inherit === true ? null : data.primary_color }))
    .put(route('lab-branding.update', activeLab.value.id), { preserveScroll: true, onSuccess: () => { brandingOpen.value = false } })
}
const themePreset = computed(() => settings.value.theme_preset || 'corporate')
const moduleFamily = computed(() => {
  const url = page.url || ''

  if (url.startsWith('/integration-hub')) {
    return 'integrations'
  }

  if (
    url.startsWith('/vap-inventory')
    || url.startsWith('/inventory')
    || url.startsWith('/itemcategories')
    || url.startsWith('/equipmentcategories')
    || url.startsWith('/itemstatuses')
    || url.startsWith('/iunits')
    || url.startsWith('/itypes')
    || url.startsWith('/ilocations')
    || url.startsWith('/ideliveries')
    || url.startsWith('/isuppliers')
    || url.startsWith('/supplier-assessments')
    || url.startsWith('/iwarehouses')
  ) {
    return 'inventory'
  }

  if (
    url.startsWith('/laboratory-workflow')
    || url.startsWith('/samples')
    || url.startsWith('/vap-samples')
    || url.startsWith('/directcollections')
    || url.startsWith('/programmedcollections')
    || url.startsWith('/analysis')
    || url.startsWith('/counter-analysis')
    || url.startsWith('/parameters')
    || url.startsWith('/analysiscategories')
    || url.startsWith('/profiles')
    || url.startsWith('/matrixes')
    || url.startsWith('/protocols')
    || url.startsWith('/standards')
    || url.startsWith('/nwps')
    || url.startsWith('/units')
    || url.startsWith('/temperatures')
    || url.startsWith('/environmental-conditions')
    || url.startsWith('/qualitycertificates')
    || url.startsWith('/import-certificates')
    || url.startsWith('/export-certificates')
    || url.startsWith('/occurrence')
    || url.startsWith('/vap-labs')
  ) {
    return 'sample-lifecycle'
  }

  if (
    url.startsWith('/invoices')
    || url.startsWith('/quotes')
    || url.startsWith('/creditnotes')
    || url.startsWith('/receipts')
    || url.startsWith('/currencies')
    || url.startsWith('/paymentcategories')
    || url.startsWith('/discountcategories')
    || url.startsWith('/taxtypes')
    || url.startsWith('/taxexemptions')
    || url.startsWith('/invoicecategories')
    || url.startsWith('/vap-proposals')
    || url.startsWith('/customers')
    || url.startsWith('/customercategories')
    || url.startsWith('/contactcategories')
    || url.startsWith('/warehouses')
  ) {
    return 'commercial'
  }

  if (
    url.startsWith('/products')
    || url.startsWith('/phytosanitary-products')
    || url.startsWith('/paid-services')
    || url.startsWith('/transportcategories')
    || url.startsWith('/vehicles')
    || url.startsWith('/faq')
    || url.startsWith('/contractguides')
    || url.startsWith('/collection')
    || url.startsWith('/packagingcategories')
    || url.startsWith('/customerrequest')
    || url.startsWith('/countries')
  ) {
    return 'operations'
  }

  if (
    url.startsWith('/file-manager')
    || url.startsWith('/users')
    || url.startsWith('/departments')
    || url.startsWith('/general-settings')
    || url.startsWith('/roles')
    || url.startsWith('/permissions')
    || url.startsWith('/security')
    || url.startsWith('/system-activity')
    || url.startsWith('/system-backups')
  ) {
    return 'admin'
  }

  return 'general'
})

const moduleFamilyLabels = {
  integrations: 'Integrações laboratoriais',
  inventory: 'Inventário',
  'sample-lifecycle': 'Ciclo de vida das amostras',
  commercial: 'Área comercial',
  operations: 'Operações de campo',
  admin: 'Administração',
  general: 'Laboratório',
}

const pageCrumbs = {
  LaboratoryWorkbench: 'Visão geral',
  'LabNetwork/Index': 'Rede de laboratórios',
  'VAPSamples/Queue': 'Amostras',
}

// The server describes where a page sits; pages without a trail fall back to their area.
const crumbs = computed(() => {
  const root = activeArea.value
    ? { title: activeArea.value.label, url: activeArea.value.href, current: false }
    : { title: activeLab.value?.name || settings.value.lab_name || 'Laboratório', url: route('dashboard'), current: false }

  if (pageCrumbs[page.component]) {
    return [root, { title: pageCrumbs[page.component], current: true }]
  }

  const trail = (page.props.breadcrumbs ?? []).filter((crumb) => crumb?.title)

  return trail.length
    ? [root, ...trail]
    : [root, { title: moduleFamilyLabels[moduleFamily.value] || moduleFamilyLabels.general, current: true }]
})

// --- Session timeout ---
const timerDuration = 1500
const { count: countdown, dec, reset } = useCounter(timerDuration, { step: -1 })
const { idle } = useIdle({ timeout: 1000, emitOnIdle: true })
const showSessionModal = ref(false)
const remainingTime = ref(0)
let countdownInterval = null
const lastActive = ref(Date.now())

const startCountdown = () => {
  reset()
  if (countdownInterval) clearInterval(countdownInterval)
  countdownInterval = setInterval(() => {
    if (countdown.value <= 0) {
      clearInterval(countdownInterval)
    } else if (countdown.value <= 15) {
      showSessionModal.value = true
      remainingTime.value = countdown.value
    } else {
      showSessionModal.value = false
    }
    dec()
  }, 1000)
}

const resetTimerOnActivity = () => {
  reset()
  startCountdown()
  lastActive.value = Date.now()
}

// --- Language ---
const switchLanguage = async (language) => {
  await loadLanguageAsync(language)
  router.post(route('language.store'), { language }, { preserveState: false, preserveScroll: true, replace: true })
}

// --- Navigation (shared with side-nav) ---
const exportHubPermissions = [
  'export_activity_log', 'export_customers', 'export_warehouses', 'export_products', 'export_parameters',
  'export_profiles', 'export_matrixes', 'export_invoices', 'export_quotes', 'export_credit_notes',
  'export_receipts', 'export_contract_guides', 'export_import_certificates', 'export_export_certificates',
  'export_quality_certificates', 'export_customer_requests', 'export_occurrences',
]
const canUseExportHub = exportHubPermissions.some((permission) => hasPermission(permission))
  || ['view_analysis', 'view_results', 'view_samples', 'view_inventory', 'view_maintenance_tasks'].some((permission) => hasPermission(permission))

const navigation = [
  { title: 'Hoje', name: '/dashboard', href: route('dashboard'), show: true },
  { title: 'gestlab.menu.notifications', name: '/notifications', href: route('notifications.index'), show: true },
  {
    title: 'gestlab.menu.admin_processes', name: 'Processos ADM.', show: true,
    children: [
      { title: 'gestlab.menu.products', name: '/products', href: route('products.index'), show: hasPermission('view_products') },
      { title: 'gestlab.menu.phytosanitary_products', name: '/phytosanitary-products', href: route('phytosanitary_products.index'), show: hasPermission('view_phytosanitary_products') },
      { title: 'gestlab.menu.paid_services', name: '/paid-services', href: route('paidservices.index'), show: hasPermission('view_paid_services') },
      { title: 'gestlab.menu.trans_types', name: '/transportcategories', href: route('transportcategories.index'), show: hasPermission('view_trans_types') },
      { title: 'gestlab.menu.vehicles', name: '/vehicles', href: route('vehicles.index'), show: hasPermission('view_vehicles') },
      { title: 'gestlab.menu.faq_categories', name: '/faqcategories', href: route('faqcategories.index'), show: hasPermission('view_faq_categories') },
      { title: 'gestlab.menu.faqs', name: '/faqs', href: route('faqs.index'), show: hasPermission('view_faqs') },
      { title: 'gestlab.menu.faq_answers', name: '/faqanswers', href: route('faqanswers.index'), show: hasPermission('view_faq_answers') },
      { title: 'gestlab.menu.contract_guides', name: '/contractguides', href: route('contractguides.index'), show: hasPermission('view_contract_guides') },
      { title: 'gestlab.menu.direct_collections', name: '/directcollections', href: route('directcollections.index'), show: hasPermission('view_direct_collections') },
      { title: 'gestlab.menu.programmed_collections', name: '/programmedcollections', href: route('programmedcollections.index'), show: hasPermission('view_programmed_collections') },
      { title: 'gestlab.menu.collection_reasons', name: '/collectionreasons', href: route('collectionreasons.index'), show: hasPermission('view_collection_reasons') },
      { title: 'gestlab.menu.result_categories', name: '/resultcategories', href: route('resultcategories.index'), show: hasPermission('view_result_categories') },
      { title: 'gestlab.menu.collaboration_categories', name: '/collectioncollaborations', href: route('collectioncollaborations.index'), show: hasPermission('view_collaboration_categories') },
      { title: 'gestlab.menu.packaging_types', name: '/packagingcategories', href: route('packagingcategories.index'), show: hasPermission('view_packaging_types') },
      { title: 'gestlab.menu.request_categories', name: '/customerrequestcategories', href: route('customerrequestcategories.index'), show: hasPermission('view_request_categories') },
      { title: 'gestlab.menu.customer_requests', name: '/customerrequests', href: route('customerrequests.index'), show: hasPermission('view_customer_requests') },
      { title: 'gestlab.menu.collection_end_results', name: '/collectionendresults', href: route('collectionendresults.index'), show: hasPermission('view_collection_end_results') },
      { title: 'gestlab.menu.countries', name: '/countries', href: route('countries.index'), show: hasPermission('view_countries') },
    ],
  },
  {
    title: 'gestlab.menu.customers', name: 'customers', show: true,
    children: [
      { title: 'gestlab.menu.customer_categories', name: '/customercategories', href: route('customercategories.index'), show: hasPermission('view_customer_categories') },
      { title: 'gestlab.menu.contact_categories', name: '/contactcategories', href: route('contactcategories.index'), show: hasPermission('view_contact_categories') },
      { title: 'gestlab.menu.customers', name: '/customers', href: route('customers.index'), show: hasPermission('view_customers') },
      { title: 'gestlab.menu.warehouses', name: '/warehouses', href: route('warehouses.index'), show: hasPermission('view_warehouses') },
    ],
  },
  {
    title: 'gestlab.menu.invoicing', name: 'Invoicing', show: true,
    children: [
      { title: 'gestlab.menu.invoice_categories', name: '/invoicecategories', href: route('invoicecategories.index'), show: hasPermission('view_invoice_categories') },
      { title: 'gestlab.menu.proposal_templates', name: '/vap-proposals/templates', href: route('vap-proposals.templates.index'), show: hasPermission('view_proposal_templates') },
      { title: 'gestlab.menu.proposals', name: '/vap-proposals', href: route('vap-proposals.index'), show: hasPermission('view_proposals') },
      { title: 'gestlab.menu.invoices', name: '/invoices', href: route('invoices.index'), show: hasPermission('view_invoices') },
      { title: 'gestlab.menu.quotes', name: '/quotes', href: route('quotes.index'), show: hasPermission('view_quotes') },
      { title: 'gestlab.menu.credit_notes', name: '/creditnotes', href: route('creditnotes.index'), show: hasPermission('view_credit_notes') },
      { title: 'gestlab.menu.receipts', name: '/receipts', href: route('receipts.index'), show: hasPermission('view_receipts') },
      { title: 'gestlab.menu.currencies', name: '/currencies', href: route('currencies.index'), show: hasPermission('view_currencies') },
      { title: 'gestlab.menu.payment_categories', name: '/paymentcategories', href: route('paymentcategories.index'), show: hasPermission('view_payment_categories') },
      { title: 'gestlab.menu.discount_categories', name: '/discountcategories', href: route('discountcategories.index'), show: hasPermission('view_discount_categories') },
      { title: 'gestlab.menu.tax_types', name: '/taxtypes', href: route('taxtypes.index'), show: hasPermission('view_tax_types') },
      { title: 'gestlab.menu.tax_exemptions', name: '/taxexemptions', href: route('taxexemptions.index'), show: hasPermission('view_tax_exemptions') },
    ],
  },
  {
    title: 'gestlab.menu.tax_authority', name: 'AGT', show: true,
    children: [
      { title: 'gestlab.menu.tax_exemptions', name: '/taxexemptions', href: route('taxexemptions.index'), show: hasPermission('view_tax_exemptions') },
      { title: 'Consulta de NIF', name: '/customers/tax-identification', href: route('customers.taxIdentification'), show: hasPermission('view_tax_exemptions') },
    ],
  },
  {
    title: 'gestlab.menu.analytical_processes', name: 'Processos Analíticos', show: true,
    children: [
      { title: 'Fluxo laboratorial', name: '/laboratory-workflow', href: route('laboratory-workflow.index'), show: hasPermission('view_proposals') || hasPermission('view_samples') || hasPermission('view_analysis') || hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.parameters', name: '/parameters', href: route('parameters.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.analysis', name: '/analysis', href: route('analysis.index'), show: hasPermission('view_analysis') },
      { title: 'Dados laboratoriais', name: '/analysis/data-exports', href: route('analysis.data-exports.index'), show: hasPermission('view_analysis') || hasPermission('view_results') },
      { title: 'gestlab.menu.analysis_categories', name: '/analysiscategories', href: route('analysiscategories.index'), show: hasPermission('view_analysis_categories') },
      { title: 'gestlab.menu.pending_samples', name: '/vap-samples', href: route('vap_samples.queue'), show: hasPermission('view_samples') },
      { title: 'gestlab.menu.sample_reports', name: '/vap-samples/reports', href: route('vap_samples.reports'), show: hasPermission('view_samples') },
      { title: 'gestlab.menu.internal_quality_control', name: '/vap-samples/reports', href: route('vap_samples.reports', { sample_scope: 'internal_qc' }), show: hasPermission('view_samples') },
      { title: 'gestlab.menu.counter_analysis', name: '/counter-analysis', href: route('counteranalysis.index'), show: hasPermission('view_counter_analysis') },
      { title: 'gestlab.menu.profiles', name: '/profiles', href: route('profiles.index'), show: hasPermission('view_profiles') },
      { title: 'gestlab.menu.matrixes', name: '/matrixes', href: route('matrixes.index'), show: hasPermission('view_matrixes') },
      { title: 'gestlab.menu.protocols', name: '/protocols', href: route('protocols.index'), show: hasPermission('view_protocols') },
      { title: 'gestlab.menu.standards', name: '/standards', href: route('standards.index'), show: hasPermission('view_standards') },
      { title: 'gestlab.menu.nwps', name: '/nwps', href: route('nwps.index'), show: hasPermission('view_nwps') },
      { title: 'gestlab.menu.units', name: '/units', href: route('units.index'), show: hasPermission('view_units') },
      { title: 'gestlab.menu.temperatures', name: '/temperatures', href: route('temperatures.index'), show: hasPermission('view_temperatures') },
      { title: 'Condições Ambientais', name: '/environmental-conditions', href: route('environmental-conditions.index'), show: hasPermission('view_temperatures') },
    ],
  },
  {
    title: 'gestlab.menu.analysis_reports', name: 'Boletins', show: true,
    children: [
      { title: 'gestlab.menu.quality_certificates', name: '/qualitycertificates', href: route('qualitycertificates.index'), show: hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.import_certificates', name: '/import-certificates', href: route('importcertificates.index'), show: hasPermission('view_import_certificates') },
      { title: 'gestlab.menu.export_certificates', name: '/export-certificates', href: route('exportcertificates.index'), show: hasPermission('view_export_certificates') },
      { title: 'gestlab.menu.report_studios', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings') },
    ],
  },
  {
    title: 'gestlab.menu.occurrences', name: 'Ocorrências', show: true,
    children: [
      { title: 'gestlab.menu.occurrence_categories', name: '/occcurrencecategories', href: route('occurrencecategories.index'), show: hasPermission('view_occurrence_categories') },
      { title: 'gestlab.menu.occurrence_origins', name: '/occcurrenceorigins', href: route('occurrenceorigins.index'), show: hasPermission('view_occurrence_origins') },
      { title: 'gestlab.menu.occurrence_statuses', name: '/occurrencestatuses', href: route('occurrencestatuses.index'), show: hasPermission('view_occurrence_statuses') },
      { title: 'gestlab.menu.occurrences', name: '/occurrences', href: route('occurrences.index'), show: hasPermission('view_occurrences') },
      { title: 'Não conformidades laboratoriais', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') },
    ],
  },
  {
    title: 'gestlab.menu.inventory', name: 'Inventário', show: true,
    children: [
      { title: 'gestlab.menu.inventory', name: '/vap-inventory/items', href: route('vap-inventory.items.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.reagent_consumption', name: '/vap-inventory/reagents/consumption', href: route('vap-inventory.reagents.consumption.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.iequipments', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 1 }), show: hasPermission('view_iequipments') },
      { title: 'Integration Hub', name: '/integration-hub', href: route('integration-hub.index'), show: hasPermission('view_iequipments') || hasPermission('view_settings') },
      { title: 'gestlab.menu.iitems', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 2 }), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.item_categories', name: '/itemcategories', href: route('itemcategories.index'), show: hasPermission('view_item_categories') },
      { title: 'gestlab.menu.equipment_categories', name: '/equipmentcategories', href: route('equipmentcategories.index'), show: hasPermission('view_equipment_categories') },
      { title: 'gestlab.menu.item_statuses', name: '/itemstatuses', href: route('itemstatuses.index'), show: hasPermission('view_item_statuses') },
      { title: 'gestlab.menu.iunits', name: '/iunits', href: route('iunits.index'), show: hasPermission('view_iunits') },
      { title: 'gestlab.menu.itypes', name: '/itypes', href: route('itypes.index'), show: hasPermission('view_itypes') },
      { title: 'gestlab.menu.ilocations', name: '/ilocations', href: route('ilocations.index'), show: hasPermission('view_ilocations') },
      { title: 'gestlab.menu.ideliveries', name: '/ideliveries', href: route('ideliveries.index'), show: hasPermission('view_ideliveries') },
      { title: 'gestlab.menu.iorders', name: '/vap-inventory/orders', href: route('vap-inventory.orders.index'), show: hasPermission('view_iorders') },
      { title: 'gestlab.menu.lab_needs', name: '/vap-inventory/needs', href: route('vap-inventory.needs.index'), show: hasPermission('view_iorders') },
      { title: 'gestlab.menu.isuppliers', name: '/isuppliers', href: route('isuppliers.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.itransfers', name: '/vap-inventory/transfers', href: route('vap-inventory.transfers.index'), show: hasPermission('view_itransfers') },
      { title: 'gestlab.menu.iwarehouses', name: '/iwarehouses', href: route('iwarehouses.index'), show: hasPermission('view_iwarehouses') },
      { title: 'gestlab.menu.inventory_analytics', name: '/vap-inventory/analytics', href: route('vap-inventory.analytics.index'), show: hasPermission('view_inventory') },
    ],
  },
  {
    title: 'gestlab.menu.maintenance_tasks', name: 'Manutenção', show: true,
    children: [
      { title: 'gestlab.menu.maintenance_categories', name: '/maintenance/categories', href: route('vap-maintenance.categories'), show: hasPermission('view_maintenance_categories') },
      { title: 'gestlab.menu.maintenance_tasks', name: '/maintenance/tasks', href: route('vap-maintenance.tasks'), show: hasPermission('view_maintenance_tasks') },
    ],
  },
  {
    title: 'gestlab.menu.quality_compliance', name: 'qualidade', show: true,
    children: [
      { title: 'gestlab.menu.qms', name: '/qms', href: route('qms.index'), show: hasPermission('view_activity_log') },
      { title: 'gestlab.menu.staff_competence', name: '/users', href: route('users.index'), show: hasPermission('view_users') },
      { title: 'gestlab.menu.supplier_assessments', name: '/supplier-assessments', href: route('supplier-assessments.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.lab_non_conformities', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') },
      { title: 'gestlab.menu.responsibility_matrix', name: '/responsibility-matrix', href: route('responsibility-matrix.index'), show: hasPermission('view_users') },
      { title: 'gestlab.menu.uncertainty_sources', name: '/uncertainty-sources', href: route('uncertainty-sources.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.proficiency_tests', name: '/proficiency-tests', href: route('proficiency_tests.index'), show: hasPermission('view_analysis') },
      { title: 'gestlab.menu.report_studios', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings') },
    ],
  },
  {
    title: 'gestlab.menu.lab_operations', name: 'operacoes-laboratoriais', show: true,
    children: [
      { title: 'gestlab.menu.labs', name: '/vap-labs/labs', href: route('vap-labs.labs.index'), show: hasPermission('view_departments') },
      { title: 'gestlab.menu.labels', name: '/vap-labels/labels', href: route('vap_labels.labels.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.document_manager', name: '/file-manager', href: route('file-manager'), show: hasPermission('view_documents') || hasPermission('view_activity_log') },
    ],
  },
  { title: 'gestlab.menu.users', name: '/users', href: route('users.index'), show: hasPermission('view_users') },
  { title: 'gestlab.menu.departments', name: '/departments', href: route('departments.index'), show: hasPermission('view_departments') },
  { title: 'gestlab.menu.adverts', name: '/announcements', href: '#', show: hasPermission('view_announcements') },
  { title: 'gestlab.menu.settings', name: '/general-settings', href: route('generalsettings.index'), show: hasPermission('view_settings') },
  { title: 'gestlab.menu.roles', name: '/roles', href: route('roles.index'), show: hasPermission('view_roles') },
  { title: 'gestlab.menu.permissions', name: '/permissions', href: route('permissions.index'), show: hasPermission('view_permissions') },
  { title: 'gestlab.menu.security', name: '/security', href: route('security'), show: true },
  { title: 'gestlab.menu.activity_log', name: '/system-activity', href: route('systemactivity.index'), show: hasPermission('view_activity_log') },
  { title: 'Central de exportações', name: '/exports', href: route('exports.index'), show: canUseExportHub },
  { title: 'gestlab.menu.backups', name: '/system-backups/backups', href: route('systembackups.backups'), show: hasPermission('view_backups') },
]

const sidebarOpen = ref(false)
const commandPaletteOpen = ref(false)
const commandPaletteQuery = ref('')
const commandPaletteInput = ref(null)
const commandPaletteResults = ref(null)
const activeCommandIndex = ref(0)
const stageContent = ref(null)

const navLabel = (item) => {
  if (!item?.title) {
    return ''
  }

  return item.title.startsWith('gestlab.') ? trans(item.title) : item.title
}

const visibleChildren = (item) => (item.children || []).filter((child) => child.show && child.href && child.href !== '#')

const commandGroups = computed(() => navigation
  .filter((item) => item.show)
  .map((item) => {
    const children = visibleChildren(item)
    const items = children.length
      ? children
      : item.href && item.href !== '#'
        ? [item]
        : []

    return {
      label: navLabel(item),
      items: items.map((command) => ({
        href: command.href,
        label: navLabel(command),
        path: command.name || item.name || '',
      })),
    }
  })
  .filter((group) => group.items.length > 0))

const normalizeSearchValue = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()

const filteredCommandGroups = computed(() => {
  const query = normalizeSearchValue(commandPaletteQuery.value.trim())
  let index = 0

  return commandGroups.value
    .map((group) => {
      const items = query
        ? group.items.filter((command) => [
          command.label,
          command.path,
          group.label,
        ].some((value) => normalizeSearchValue(value).includes(query)))
        : group.items

      return {
        ...group,
        items,
      }
    })
    .filter((group) => group.items.length > 0)
    .map((group) => ({ ...group, items: group.items.map((command) => ({ ...command, index: index++ })) }))
})

const flatCommands = computed(() => filteredCommandGroups.value.flatMap((group) => group.items))
const activeCommand = computed(() => flatCommands.value[activeCommandIndex.value] ?? flatCommands.value[0] ?? null)

// Eight areas across the top. Every menu entry belongs to exactly one area; anything
// unmatched lands in Admin, so no page is ever left without a place in the navigation.
// Each area lists its daily pages under named groups; catalogues and settings fold
// away under one heading.
const areaDefinitions = [
  { key: 'home', label: 'Início', groups: [{ label: 'O meu dia', paths: ['/dashboard', '/notifications', '/lab-networks'] }], match: ['/dashboard', '/notifications', '/lab-networks'] },
  { key: 'samples', label: 'Amostras', groups: [{ label: 'Fluxo', paths: ['/vap-samples', '/directcollections', '/programmedcollections'] }, { label: 'Indicadores', paths: ['/vap-samples/reports'] }], match: ['/vap-samples', '/samples', '/directcollections', '/programmedcollections', '/collectionreasons', '/collectioncollaborations', '/collectionendresults', '/packagingcategories'] },
  { key: 'analysis', label: 'Análise', groups: [{ label: 'Bancada', paths: ['/laboratory-workflow', '/analysis', '/counter-analysis', '/analysis/data-exports'] }], match: ['/laboratory-workflow', '/analysis', '/counter-analysis', '/counteranalysis', '/multiple-sample-analysis', '/parameters', '/analysiscategories', '/profiles', '/matrixes', '/protocols', '/standards', '/nwps', '/units', '/temperatures', '/environmental-conditions', '/resultcategories', '/worksheets', '/formulas', '/variables'] },
  { key: 'certificates', label: 'Certificados', groups: [{ label: 'Emissão', paths: ['/qualitycertificates', '/import-certificates', '/export-certificates'] }, { label: 'Modelos', paths: ['/report-studios'] }], match: ['/qualitycertificates', '/import-certificates', '/export-certificates', '/report-studios'] },
  { key: 'commercial', label: 'Comercial', groups: [{ label: 'Clientes', paths: ['/customers', '/warehouses', '/customerrequests'] }, { label: 'Vendas', paths: ['/vap-proposals', '/quotes', '/invoices', '/creditnotes', '/receipts', '/contractguides'] }], match: ['/vap-proposals', '/invoices', '/quotes', '/creditnotes', '/receipts', '/invoicecategories', '/currencies', '/paymentcategories', '/discountcategories', '/taxtypes', '/taxexemptions', '/customers/tax-identification', '/products', '/phytosanitary-products', '/paid-services', '/contractguides', '/customers', '/customercategories', '/contactcategories', '/warehouses', '/customerrequests', '/customerrequestcategories', '/faqs', '/faqcategories', '/faqanswers', '/countries', '/transportcategories', '/vehicles', '/ratings', '/complaints'] },
  { key: 'inventory', label: 'Inventário', groups: [{ label: 'Existências', paths: ['/vap-inventory/items', '/vap-inventory/reagents/consumption', '/vap-inventory/transfers', '/vap-inventory/analytics'] }, { label: 'Equipamento', paths: ['/integration-hub', '/maintenance/tasks', '/vap-labels/labels'] }, { label: 'Compras', paths: ['/vap-inventory/needs', '/vap-inventory/orders'] }], match: ['/vap-inventory', '/inventory', '/itemcategories', '/equipmentcategories', '/itemstatuses', '/iunits', '/itypes', '/ilocations', '/ideliveries', '/isuppliers', '/iwarehouses', '/integration-hub', '/maintenance', '/vap-labels'] },
  { key: 'quality', label: 'Qualidade', groups: [{ label: 'Sistema', paths: ['/qms', '/occurrences', '/vap-non-conformities', '/supplier-assessments'] }, { label: 'Controlo', paths: ['/proficiency-tests', '/responsibility-matrix', '/uncertainty-sources', '/users'] }], match: ['/qms', '/occurrences', '/occurrencecategories', '/occurrenceorigins', '/occurrencestatuses', '/occcurrencecategories', '/occcurrenceorigins', '/vap-non-conformities', '/supplier-assessments', '/responsibility-matrix', '/uncertainty-sources', '/proficiency-tests', '/management-reviews'] },
  { key: 'admin', label: 'Admin', groups: [{ label: 'Organização', paths: ['/vap-labs/labs', '/users', '/departments', '/roles', '/permissions'] }, { label: 'Sistema', paths: ['/general-settings', '/file-manager', '/exports', '/system-activity', '/system-backups/backups', '/security'] }], match: [] },
]

const catalogueLabel = 'Catálogos'

const pathMatches = (path, prefix) => path === prefix || path.startsWith(`${prefix}/`)
const areaKeyForPath = (path) => areaDefinitions
  .flatMap((area) => area.match.map((prefix) => ({ key: area.key, prefix })))
  .filter(({ prefix }) => pathMatches(path, prefix))
  .sort((first, second) => second.prefix.length - first.prefix.length)[0]?.key ?? null

const navAreas = computed(() => {
  const buckets = Object.fromEntries(areaDefinitions.map((area) => [area.key, new Map()]))
  const groupFor = (area, path) => area.groups.find((group) => group.paths.includes(path))?.label ?? catalogueLabel
  const place = (leaf) => {
    if (!leaf.show || !leaf.href || leaf.href === '#') {
      return
    }

    const area = areaDefinitions.find((definition) => definition.key === (areaKeyForPath(leaf.name) ?? 'admin'))
    const sections = buckets[area.key]
    const label = groupFor(area, leaf.name)
    const section = sections.get(label) ?? []

    if (![...sections.values()].flat().some((item) => item.href === leaf.href)) {
      section.push({
        label: navLabel(leaf),
        href: leaf.href,
        path: leaf.name,
        count: leaf.name === '/notifications' ? unreadNotificationCount.value : 0,
      })
    }

    sections.set(label, section)
  }

  navigation.filter((item) => item.show).forEach((item) => {
    (item.children?.length ? item.children : [item]).forEach(place)
  })

  if (activeLab.value?.network_id) {
    place({ show: true, title: 'Rede de laboratórios', name: '/lab-networks', href: route('lab-network.index', activeLab.value.network_id) })
  }

  return areaDefinitions
    .map((area) => {
      const order = [...area.groups.map((group) => group.label), catalogueLabel]
      const sections = [...buckets[area.key].entries()]
        .filter(([, items]) => items.length)
        .sort(([first], [second]) => order.indexOf(first) - order.indexOf(second))
        .map(([label, items]) => {
          const paths = area.groups.find((group) => group.label === label)?.paths ?? []

          return {
            key: `${area.key}-${label}`,
            label,
            collapsible: label === catalogueLabel,
            items: paths.length ? [...items].sort((first, second) => paths.indexOf(first.path) - paths.indexOf(second.path)) : items,
          }
        })

      return { ...area, sections, href: sections[0]?.items[0]?.href ?? null }
    })
    .filter((area) => area.href)
})

const activeAreaKey = computed(() => {
  const path = String(page.url || '').split(/[?#]/)[0]
  const leaf = navAreas.value
    .flatMap((area) => area.sections.flatMap((section) => section.items.map((item) => ({ key: area.key, path: item.path }))))
    .filter((item) => pathMatches(path, item.path))
    .sort((first, second) => second.path.length - first.path.length)[0]

  return areaKeyForPath(path) ?? leaf?.key ?? (navAreas.value.some((area) => area.key === 'admin') ? 'admin' : navAreas.value[0]?.key ?? null)
})

const activeArea = computed(() => navAreas.value.find((area) => area.key === activeAreaKey.value) ?? null)

// Rebuilt Plano screens own their whole canvas; older screens get the content gutter.
const planoTemplates = {
  LaboratoryWorkbench: 'today',
  'LabNetwork/Index': 'page',
  'VAPSamples/Index': 'form',
  'VAPSamples/Queue': 'queue',
  'VAPSamples/Show': 'dossier',
  'VAPProposals/Index': 'queue',
  'VAPProposals/Show': 'dossier',
  'VAPProposals/Create': 'form',
  'VAPProposals/Edit': 'form',
  'QualityCertificates/Index': 'queue',
  'QualityCertificates/Show': 'dossier',
  'QualityCertificates/Edit': 'form',
}
const planoPages = Object.keys(planoTemplates)
const pageTemplate = computed(() => planoTemplates[page.component] ?? 'page')

const openCommandPalette = () => {
  commandPaletteQuery.value = ''
  activeCommandIndex.value = 0
  commandPaletteOpen.value = true
  nextTick(() => commandPaletteInput.value?.focus?.())
}

const visitCommand = (command) => {
  commandPaletteOpen.value = false
  router.visit(command.href)
}

const activateCommandPaletteResult = () => {
  if (activeCommand.value) {
    visitCommand(activeCommand.value)
  }
}

const moveCommandSelection = (step) => {
  const total = flatCommands.value.length

  if (!total) {
    return
  }

  activeCommandIndex.value = (activeCommandIndex.value + step + total) % total
  nextTick(() => commandPaletteResults.value?.querySelector(`#command-${activeCommandIndex.value}`)?.scrollIntoView({ block: 'nearest' }))
}

watch(commandPaletteQuery, () => { activeCommandIndex.value = 0 })

// A quiet settle when the destination changes; filters and tabs that keep the same page do not replay it.
let renderedComponent = page.component
const removeNavigateListener = router.on('navigate', (event) => {
  const nextComponent = event.detail.page.component

  if (nextComponent === renderedComponent) {
    return
  }

  renderedComponent = nextComponent
  const element = stageContent.value

  if (!element || document.visibilityState !== 'visible') {
    return
  }

  const keyframes = prefersReducedMotion()
    ? { opacity: [0, 1] }
    : { opacity: [0, 1], transform: ['translateY(4px)', 'translateY(0px)'] }

  animate(element, keyframes, { duration: durations.page, ease: easeOut }).then(() => {
    element.style.opacity = ''
    element.style.transform = ''
  })
})

const handleCommandPaletteShortcut = (event) => {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    openCommandPalette()
    return
  }

  if (isThemeShortcut(event)) {
    event.preventDefault()
    toggleTheme()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleCommandPaletteShortcut)

  startCountdown()

  watch(idle, (newIdleState) => {
    if (!newIdleState) resetTimerOnActivity()
  })

  const echo = getEcho()
  if (!echo) return
  const userId = usePage().props?.auth?.user?.id
  if (!userId) return

  realtimeNotificationChannel = `users.${userId}`
  echo.private(realtimeNotificationChannel).notification((notification) => {
    unreadNotificationCount.value += 1
    toast.add({
      ...notification,
      dedupeKey: notification.id || `${notification.key || 'notification'}:${notification.action_url || ''}:${notification.message || ''}`,
    })
  })
})

watch(() => props.auth?.user?.unread_notifications?.length, (count) => {
  if (typeof count === 'number') unreadNotificationCount.value = count
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleCommandPaletteShortcut)
  removeNavigateListener()

  if (countdownInterval) clearInterval(countdownInterval)

  if (realtimeNotificationChannel) {
    getEcho()?.leave(realtimeNotificationChannel)
  }
})
</script>
