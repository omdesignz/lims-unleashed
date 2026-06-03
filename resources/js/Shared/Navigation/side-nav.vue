<script setup>
import { ref, computed } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import {
    BellIcon,
    Cog6ToothIcon,
    HomeIcon,
    ShieldCheckIcon,
    MegaphoneIcon,
    SwatchIcon,
    UsersIcon,
    PowerIcon,
    FolderOpenIcon,
    BanknotesIcon,
    Square3Stack3DIcon,
    DocumentTextIcon,
    RectangleStackIcon,
    UserGroupIcon,
    UserIcon,
    FingerPrintIcon,
    StopIcon,
    WrenchScrewdriverIcon,
    ServerIcon,
    InboxStackIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    ChevronRightIcon,
    BeakerIcon,
} from '@heroicons/vue/24/outline'
import { usePermission } from '@/Composables/usePermissions'
import { trans } from 'laravel-vue-i18n'

const { hasRole, hasPermission } = usePermission();

const searchQuery = ref('');

const navigation = [
  { title: 'gestlab.menu.dashboard', name: '/dashboard', href: route('dashboard'), icon: HomeIcon, show: true },
  { title: 'gestlab.menu.notifications', name: '/notifications', href: route('notifications.index'), icon: BellIcon, show: true, separator: true, separatorLabel: 'gestlab.menu.navigation' },
  {
    title: 'gestlab.menu.admin_processes',
    name: 'Processos ADM.',
    icon: FolderOpenIcon,
    show: true,
    current: false,
    section: 'admin',
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
    title: 'gestlab.menu.customers',
    name: 'customers',
    icon: UserGroupIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.customer_categories', name: '/customercategories', href: route('customercategories.index'), show: hasPermission('view_customer_categories') },
      { title: 'gestlab.menu.contact_categories', name: '/contactcategories', href: route('contactcategories.index'), show: hasPermission('view_contact_categories') },
      { title: 'gestlab.menu.customers', name: '/customers', href: route('customers.index'), show: hasPermission('view_customers') },
      { title: 'gestlab.menu.warehouses', name: '/warehouses', href: route('warehouses.index'), show: hasPermission('view_warehouses') },
    ],
  },
  {
    title: 'gestlab.menu.invoicing',
    name: 'Invoicing',
    icon: BanknotesIcon,
    current: false,
    show: true,
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
    title: 'gestlab.menu.tax_authority',
    name: 'AGT',
    icon: SwatchIcon,
    current: false,
    show: true,
    children: [
      { title: 'gestlab.menu.tax_exemptions', name: '/taxexemptions', href: route('taxexemptions.index'), show: hasPermission('view_tax_exemptions') },
      { title: 'gestlab.menu.tax_identification', name: '/customers/tax-identification', href: route('customers.taxIdentification'), show: hasPermission('view_tax_exemptions') },
    ],
  },
  {
    title: 'gestlab.menu.analytical_processes',
    name: 'Processos Analíticos',
    icon: Square3Stack3DIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.parameters', name: '/parameters', href: route('parameters.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.analysis', name: '/analysis', href: route('analysis.index'), show: hasPermission('view_analysis') },
      { title: 'gestlab.menu.analysis_categories', name: '/analysiscategories', href: route('analysiscategories.index'), show: hasPermission('view_analysis_categories') },
      { title: 'gestlab.menu.pending_samples', name: '/vap-samples', href: route('vap_samples.index'), show: hasPermission('view_samples') },
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
      { title: 'gestlab.menu.environmental_conditions', name: '/environmental-conditions', href: route('environmental-conditions.index'), show: hasPermission('view_temperatures') },
    ],
  },
  {
    title: 'gestlab.menu.analysis_reports',
    name: 'boletins',
    icon: DocumentTextIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.quality_certificates', name: '/quality-certificates', href: route('qualitycertificates.index'), show: hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.import_certificates', name: '/import-certificates', href: route('importcertificates.index'), show: hasPermission('view_import_certificates') },
      { title: 'gestlab.menu.export_certificates', name: '/export-certificates', href: route('exportcertificates.index'), show: hasPermission('view_export_certificates') },
      { title: 'gestlab.menu.report_studios', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings') },
    ],
  },
  {
    title: 'gestlab.menu.occurrences',
    name: 'Ocorrências',
    icon: ExclamationTriangleIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.occurrence_categories', name: '/occcurrencecategories', href: route('occurrencecategories.index'), show: hasPermission('view_occurrence_categories') },
      { title: 'gestlab.menu.occurrence_origins', name: '/occcurrenceorigins', href: route('occurrenceorigins.index'), show: hasPermission('view_occurrence_origins') },
      { title: 'gestlab.menu.occurrence_statuses', name: '/occurrencestatuses', href: route('occurrencestatuses.index'), show: hasPermission('view_occurrence_statuses') },
      { title: 'gestlab.menu.occurrences', name: '/occurrences', href: route('occurrences.index'), show: hasPermission('view_occurrences') },
      { title: 'gestlab.menu.lab_non_conformities', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') || hasPermission('view_activity_log') },
    ],
  },
  {
    title: 'gestlab.menu.inventory',
    name: 'inventario',
    icon: InboxStackIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.inventory', name: '/vap-inventory/items', href: route('vap-inventory.items.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.reagent_consumption', name: '/vap-inventory/reagents/consumption', href: route('vap-inventory.reagents.consumption.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.iequipments', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 1}), show: hasPermission('view_iequipments') },
      { title: 'gestlab.menu.iitems', name: '/vap-inventory/items', href: route('vap-inventory.items.index', { category_id: 2}), show: hasPermission('view_inventory') },
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
    title: 'gestlab.menu.maintenance_tasks',
    name: 'manutencao',
    icon: WrenchScrewdriverIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.maintenance_categories', name: '/maintenance/categories', href: route('vap-maintenance.categories'), show: hasPermission('view_maintenance_categories') },
      { title: 'gestlab.menu.maintenance_tasks', name: '/maintenance/tasks', href: route('vap-maintenance.tasks'), show: hasPermission('view_maintenance_tasks') },
    ],
  },
  {
    title: 'gestlab.menu.quality_compliance',
    name: 'qualidade',
    icon: ShieldCheckIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.qms', name: '/qms', href: route('qms.index'), show: hasPermission('view_activity_log') },
      { title: 'gestlab.menu.staff_competence', name: '/users', href: route('users.index'), show: hasPermission('view_users') },
      { title: 'gestlab.menu.supplier_assessments', name: '/supplier-assessments', href: route('supplier-assessments.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.lab_non_conformities', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') || hasPermission('view_activity_log') },
      { title: 'gestlab.menu.responsibility_matrix', name: '/responsibility-matrix', href: route('responsibility-matrix.index'), show: hasPermission('view_users') },
      { title: 'gestlab.menu.uncertainty_sources', name: '/uncertainty-sources', href: route('uncertainty-sources.index'), show: hasPermission('view_parameters') },
      { title: 'gestlab.menu.proficiency_tests', name: '/proficiency-tests', href: route('proficiency_tests.index'), show: hasPermission('view_analysis') },
      { title: 'gestlab.menu.report_studios', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') || hasPermission('view_proposal_templates') || hasPermission('view_settings') },
    ],
  },
  {
    title: 'gestlab.menu.lab_operations',
    name: 'operacoes-laboratoriais',
    icon: BeakerIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.labs', name: '/vap-labs/labs', href: route('vap-labs.labs.index'), show: hasPermission('view_departments') },
      { title: 'gestlab.menu.labels', name: '/vap-labels/labels', href: route('vap_labels.labels.index'), show: hasPermission('view_inventory') },
      { title: 'gestlab.menu.document_manager', name: '/file-manager', href: route('file-manager'), show: hasPermission('view_documents') || hasPermission('view_activity_log') },
    ],
  },
  { title: 'gestlab.menu.users', name: '/users', href: route('users.index'), icon: UsersIcon, show: hasPermission('view_users') },
  { title: 'gestlab.menu.departments', name: '/departments', href: route('departments.index'), icon: RectangleStackIcon, show: hasPermission('view_departments') },
  { title: 'gestlab.menu.adverts', name: '/announcements', href: '#', icon: MegaphoneIcon, show: hasPermission('view_announcements') },
  { title: 'gestlab.menu.settings', name: '/general-settings', href: route('generalsettings.index'), icon: Cog6ToothIcon, show: hasPermission('view_settings') },
  { title: 'gestlab.menu.roles', name: '/roles', href: route('roles.index'), icon: UserIcon, show: hasPermission('view_roles') },
  { title: 'gestlab.menu.permissions', name: '/permissions', href: route('permissions.index'), icon: FingerPrintIcon, show: hasPermission('view_permissions') },
  { title: 'gestlab.menu.security', name: '/security', href: route('security'), icon: ShieldCheckIcon, show: true, separator: true, separatorLabel: 'gestlab.menu.system' },
  { title: 'gestlab.menu.activity_log', name: '/system-activity', href: route('systemactivity.index'), icon: StopIcon, show: hasPermission('view_activity_log') },
  { title: 'gestlab.menu.backups', name: '/system-backups/backups', href: route('systembackups.backups'), icon: ServerIcon, show: hasPermission('view_backups') },
]

const teams = []

const navLabel = (item) => {
  if (!item?.title) {
    return ''
  }

  return item.title.startsWith('gestlab.') ? trans(item.title) : item.title
}

const visibleChildren = (item) => (item.children || []).filter((child) => child.show)

const visibleNavigation = computed(() => navigation.filter((item) => {
  if (!item.show) {
    return false
  }

  if (!item.children) {
    return true
  }

  return visibleChildren(item).length > 0
}))

// Filter navigation based on search
const filteredNavigation = computed(() => {
  if (!searchQuery.value) return visibleNavigation.value;
  const q = searchQuery.value.toLowerCase();
  return visibleNavigation.value
    .map((item) => {
    const titleMatch = navLabel(item).toLowerCase().includes(q);
      if (titleMatch) {
        return item
      }

    if (item.children) {
        const children = visibleChildren(item).filter(child => navLabel(child).toLowerCase().includes(q));

        if (children.length > 0) {
          return { ...item, children }
        }
    }
      return null;
    })
    .filter(Boolean);
});

const isActive = (item) => {
  if (item.name && item.name.startsWith('/')) {
    return window.location.pathname === item.name || window.location.pathname.startsWith(item.name + '/');
  }
  return false;
};

const hasActiveChild = (item) => {
  if (!item.children) return false;
  return item.children.some(child => isActive(child));
};

</script>

<template>
    <nav class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden rounded-[2rem] border border-[#ded3bf]/80 bg-[#f8f4ea]/92 p-3 shadow-[0_24px_70px_rgba(15,23,42,0.08)] backdrop-blur-xl dark:border-[#23443c] dark:bg-[#07110f]/92 dark:shadow-[0_24px_70px_rgba(0,0,0,0.35)]">
        <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-primary-600 dark:text-accent-200" />
            <input
                v-model="searchQuery"
                type="search"
                :placeholder="$t('gestlab.general.search_input_placeholder')"
                class="block w-full rounded-2xl border border-[#ded3bf]/90 bg-[#fffdf7]/92 py-3 pl-10 pr-3 text-sm font-bold text-[#15231f] shadow-inner shadow-[#143d37]/5 transition-all duration-200 placeholder:text-slate-400 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-[#25443c] dark:bg-[#07110f]/80 dark:text-[#f7f1e7] dark:placeholder:text-slate-500 dark:focus:bg-[#10231f]"
            />
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto pr-1">
            <ul role="list" class="space-y-2">
                <li v-for="item in filteredNavigation" :key="item.title">
                    <div
                        v-if="item.separator"
                        class="mb-2 mt-4 px-3 text-[10px] font-black uppercase tracking-[0.24em] text-primary-700/65 dark:text-accent-200/70"
                    >
                        {{ navLabel({ title: item.separatorLabel || 'gestlab.menu.navigation' }) }}
                    </div>

                    <Link
                        v-if="!item.children && item.show"
                        :href="item.href"
                        prefetch
                        :class="[
                            isActive(item)
                                ? 'bg-[#0f3d37] text-white shadow-lg shadow-[#0f3d37]/20 dark:bg-primary-600'
                                : 'text-slate-700 hover:bg-white hover:text-[#15231f] hover:shadow-sm dark:text-slate-300 dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]',
                            'group flex items-center gap-3 rounded-[1.35rem] px-3 py-2.5 text-sm font-black transition-all duration-150'
                        ]"
                    >
                        <span :class="[
                            isActive(item) ? 'bg-white/15 text-white' : 'bg-white text-primary-700 ring-1 ring-[#ded3bf] group-hover:ring-primary-200 dark:bg-[#07110f] dark:text-accent-200 dark:ring-[#25443c]',
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl transition'
                        ]">
                            <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span class="truncate">{{ navLabel(item) }}</span>
                    </Link>

                    <Disclosure
                        v-if="item.children && item.show"
                        as="div"
                        :default-open="hasActiveChild(item)"
                        v-slot="{ open }"
                    >
                        <DisclosureButton
                            :class="[
                                hasActiveChild(item)
                                    ? 'bg-white text-[#15231f] shadow-sm ring-1 ring-[#ded3bf]/90 dark:bg-[#10231f] dark:text-[#f7f1e7] dark:ring-[#25443c]'
                                    : 'text-slate-700 hover:bg-white hover:text-[#15231f] hover:shadow-sm dark:text-slate-300 dark:hover:bg-[#10231f]/90 dark:hover:text-[#f7f1e7]',
                                'group flex w-full items-center gap-3 rounded-[1.35rem] px-3 py-2.5 text-sm font-black transition-all duration-150'
                            ]"
                        >
                            <span :class="[
                                hasActiveChild(item) ? 'bg-primary-700 text-white dark:bg-primary-500' : 'bg-white text-primary-700 ring-1 ring-[#ded3bf] group-hover:ring-primary-200 dark:bg-[#07110f] dark:text-accent-200 dark:ring-[#25443c]',
                                'flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl transition'
                            ]">
                                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span class="min-w-0 flex-1 truncate text-left">{{ navLabel(item) }}</span>
                            <span class="rounded-full bg-[#f8f4ea] px-2 py-0.5 text-[10px] font-black text-slate-500 dark:bg-[#07110f] dark:text-slate-400">
                                {{ visibleChildren(item).length }}
                            </span>
                            <ChevronRightIcon
                                :class="[
                                    open ? 'rotate-90 text-primary-700 dark:text-accent-200' : 'text-slate-400 dark:text-slate-600',
                                    'h-4 w-4 shrink-0 transition-transform duration-200'
                                ]"
                            />
                        </DisclosureButton>

                        <transition
                            enter-active-class="transition-all duration-200 ease-out"
                            enter-from-class="opacity-0 max-h-0"
                            enter-to-class="opacity-100 max-h-[1200px]"
                            leave-active-class="transition-all duration-150 ease-in"
                            leave-from-class="opacity-100 max-h-[1200px]"
                            leave-to-class="opacity-0 max-h-0"
                        >
                            <DisclosurePanel as="ul" class="ml-5 mt-1 space-y-1 overflow-hidden border-l border-[#ded3bf] pl-3 dark:border-[#25443c]">
                                <li v-for="subItem in visibleChildren(item)" :key="subItem.title">
                                    <Link
                                        :href="subItem.href"
                                        prefetch
                                        :class="[
                                            isActive(subItem)
                                                ? 'bg-primary-50 text-primary-900 ring-1 ring-primary-100 dark:bg-primary-400/10 dark:text-accent-100 dark:ring-primary-300/20'
                                                : 'text-slate-500 hover:bg-white hover:text-[#15231f] dark:text-slate-400 dark:hover:bg-[#10231f]/80 dark:hover:text-[#f7f1e7]',
                                            'block rounded-2xl px-3 py-2 text-sm font-bold transition-all duration-150'
                                        ]"
                                    >
                                        {{ navLabel(subItem) }}
                                    </Link>
                                </li>
                            </DisclosurePanel>
                        </transition>
                    </Disclosure>
                </li>
            </ul>
        </div>

        <div class="border-t border-[#ded3bf] pt-3 dark:border-[#25443c]">
            <Link
                :href="route('logout')"
                class="group flex w-full items-center gap-3 rounded-[1.35rem] px-3 py-2.5 text-sm font-bold text-slate-500 transition-all duration-150 hover:bg-red-50 hover:text-red-600 dark:text-slate-400 dark:hover:bg-red-500/10 dark:hover:text-red-300"
                method="post"
                as="button"
            >
                <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white ring-1 ring-[#ded3bf] transition group-hover:text-red-600 dark:bg-[#07110f] dark:ring-[#25443c]">
                    <PowerIcon class="h-5 w-5" aria-hidden="true" />
                </span>
                {{ $t('gestlab.menu.logout') }}
            </Link>
        </div>
    </nav>
</template>
