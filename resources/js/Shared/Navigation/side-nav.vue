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

const { hasRole, hasPermission } = usePermission();

const searchQuery = ref('');

const navigation = [
  { title: 'gestlab.menu.dashboard', name: '/dashboard', href: route('dashboard'), icon: HomeIcon, show: true },
  { title: 'gestlab.menu.notifications', name: '/notifications', href: route('notifications.index'), icon: BellIcon, show: true, separator: true, separatorLabel: 'Navigation' },
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
      { title: 'gestlab.menu.proposal_templates', name: '/proposaltemplates', href: route('proposaltemplates.index'), show: hasPermission('view_proposal_templates') },
      { title: 'gestlab.menu.proposals', name: '/proposals', href: route('proposals.index'), show: hasPermission('view_proposals') },
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
      { title: 'Consulta de NIF', name: '/customers/tax-identification', href: route('customers.taxIdentification'), show: hasPermission('view_tax_exemptions') },
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
      { title: 'gestlab.menu.pending_samples', name: '/samples', href: route('samples.index'), show: hasPermission('view_samples') },
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
    title: 'gestlab.menu.analysis_reports',
    name: 'boletins',
    icon: DocumentTextIcon,
    show: true,
    current: false,
    children: [
      { title: 'gestlab.menu.quality_certificates', name: '/quality-certificates', href: route('qualitycertificates.index'), show: hasPermission('view_quality_certificates') },
      { title: 'gestlab.menu.import_certificates', name: '/import-certificates', href: route('importcertificates.index'), show: hasPermission('view_import_certificates') },
      { title: 'gestlab.menu.export_certificates', name: '/export-certificates', href: route('exportcertificates.index'), show: hasPermission('view_export_certificates') },
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
      { title: 'Não conformidades laboratoriais', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') || hasPermission('view_activity_log') },
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
      { title: 'Necessidades laboratoriais', name: '/vap-inventory/needs', href: route('vap-inventory.needs.index'), show: hasPermission('view_iorders') },
      { title: 'gestlab.menu.isuppliers', name: '/isuppliers', href: route('isuppliers.index'), show: hasPermission('view_isuppliers') },
      { title: 'gestlab.menu.itransfers', name: '/vap-inventory/transfers', href: route('vap-inventory.transfers.index'), show: hasPermission('view_itransfers') },
      { title: 'gestlab.menu.iwarehouses', name: '/iwarehouses', href: route('iwarehouses.index'), show: hasPermission('view_iwarehouses') },
      { title: 'Analytics de inventário', name: '/vap-inventory/analytics', href: route('vap-inventory.analytics.index'), show: hasPermission('view_inventory') },
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
    title: 'Qualidade e conformidade',
    name: 'qualidade',
    icon: ShieldCheckIcon,
    show: true,
    current: false,
    children: [
      { title: 'QMS', name: '/qms', href: route('qms.index'), show: hasPermission('view_activity_log') },
      { title: 'Competência do pessoal', name: '/users', href: route('users.index'), show: hasPermission('view_users') },
      { title: 'Avaliação de fornecedores', name: '/supplier-assessments', href: route('supplier-assessments.index'), show: hasPermission('view_isuppliers') },
      { title: 'Não conformidades laboratoriais', name: '/vap-non-conformities', href: route('vap_non_conformities.index'), show: hasPermission('view_occurrences') || hasPermission('view_activity_log') },
      { title: 'Matriz de responsabilidades', name: '/responsibility-matrix', href: route('responsibility-matrix.index'), show: hasPermission('view_users') },
      { title: 'Fontes de incerteza', name: '/uncertainty-sources', href: route('uncertainty-sources.index'), show: hasPermission('view_parameters') },
      { title: 'Ensaios de proficiência', name: '/proficiency-tests', href: route('proficiency_tests.index'), show: hasPermission('view_analysis') },
      { title: 'Studios de relatório', name: '/report-studios', href: route('report-studios.index'), show: hasPermission('view_quality_certificates') },
    ],
  },
  {
    title: 'Operações laboratoriais',
    name: 'operacoes-laboratoriais',
    icon: BeakerIcon,
    show: true,
    current: false,
    children: [
      { title: 'Laboratórios', name: '/vap-labs/labs', href: route('vap-labs.labs.index'), show: hasPermission('view_departments') },
      { title: 'Etiquetas', name: '/vap-labels/labels', href: route('vap_labels.labels.index'), show: hasPermission('view_inventory') },
      { title: 'Gestor documental', name: '/file-manager', href: route('file-manager'), show: hasPermission('view_documents') || hasPermission('view_activity_log') },
    ],
  },
  { title: 'gestlab.menu.users', name: '/users', href: route('users.index'), icon: UsersIcon, show: hasPermission('view_users') },
  { title: 'gestlab.menu.departments', name: '/departments', href: route('departments.index'), icon: RectangleStackIcon, show: hasPermission('view_departments') },
  { title: 'gestlab.menu.adverts', name: '/announcements', href: '#', icon: MegaphoneIcon, show: hasPermission('view_announcements') },
  { title: 'gestlab.menu.settings', name: '/general-settings', href: route('generalsettings.index'), icon: Cog6ToothIcon, show: hasPermission('view_settings') },
  { title: 'gestlab.menu.roles', name: '/roles', href: route('roles.index'), icon: UserIcon, show: hasPermission('view_roles') },
  { title: 'gestlab.menu.permissions', name: '/permissions', href: route('permissions.index'), icon: FingerPrintIcon, show: hasPermission('view_permissions') },
  { title: 'gestlab.menu.security', name: '/security', href: route('security'), icon: ShieldCheckIcon, show: true, separator: true, separatorLabel: 'Sistema' },
  { title: 'gestlab.menu.activity_log', name: '/system-activity', href: route('systemactivity.index'), icon: StopIcon, show: hasPermission('view_activity_log') },
  { title: 'gestlab.menu.backups', name: '/system-backups/backups', href: route('systembackups.backups'), icon: ServerIcon, show: hasPermission('view_backups') },
]

const teams = []

// Filter navigation based on search
const filteredNavigation = computed(() => {
  if (!searchQuery.value) return navigation;
  const q = searchQuery.value.toLowerCase();
  return navigation.filter(item => {
    if (!item.show) return false;
    const titleMatch = item.title.toLowerCase().includes(q);
    if (titleMatch) return true;
    if (item.children) {
      return item.children.some(child => child.show && child.title.toLowerCase().includes(q));
    }
    return false;
  });
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
    <nav class="flex flex-1 flex-col">
        <!-- Search -->
        <div class="px-2 mb-4">
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-gray-500" />
                <input
                    v-model="searchQuery"
                    type="search"
                    :placeholder="$t('gestlab.general.search_input_placeholder')"
                    class="block w-full rounded-xl border border-transparent bg-gray-100 py-2.5 pl-9 pr-3 text-sm text-gray-900 transition-all duration-200 placeholder:text-gray-400 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-transparent dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:bg-gray-700"
                />
            </div>
        </div>

        <ul role="list" class="flex flex-1 flex-col gap-y-6">
            <li>
                <ul role="list" class="-mx-2 space-y-0.5">
                    <li v-for="item in filteredNavigation" :key="item.title">
                        <!-- Section separator -->
                        <div
                          v-if="item.separator"
                          class="mt-5 mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        >
                          {{ $t(item.separatorLabel || 'gestlab.menu.navigation') }}
                        </div>

                        <!-- Single link item -->
                        <Link
                            prefetch
                            v-if="!item.children && item.show"
                            :href="item.href"
                            :class="[
                                isActive(item)
                                    ? 'bg-primary-900 text-white dark:bg-primary-800/60 dark:text-white shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
                                'group flex gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150'
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    isActive(item)
                                        ? 'text-white/90'
                                        : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300',
                                    'h-5 w-5 shrink-0 transition-colors duration-150'
                                ]"
                                aria-hidden="true"
                            />
                            {{ $t(item.title) }}
                        </Link>

                        <!-- Expandable group -->
                        <Disclosure
                            as="div"
                            v-if="item.children && item.show"
                            :default-open="hasActiveChild(item)"
                            v-slot="{ open }"
                        >
                            <DisclosureButton
                                :class="[
                                    hasActiveChild(item)
                                        ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200',
                                    'group flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-150'
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        hasActiveChild(item)
                                            ? 'text-primary-600 dark:text-primary-400'
                                            : 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300',
                                        'h-5 w-5 shrink-0 transition-colors duration-150'
                                    ]"
                                    aria-hidden="true"
                                />
                                <span class="flex-1 text-left">{{ $t(item.title) }}</span>
                                <ChevronRightIcon
                                    :class="[
                                        open ? 'rotate-90 text-gray-500' : 'text-gray-300 dark:text-gray-600',
                                        'h-4 w-4 shrink-0 transition-transform duration-200'
                                    ]"
                                />
                            </DisclosureButton>

                            <transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 max-h-0"
                                enter-to-class="opacity-100 max-h-[1000px]"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 max-h-[1000px]"
                                leave-to-class="opacity-0 max-h-0"
                            >
                                <DisclosurePanel as="ul" class="mt-0.5 space-y-0.5 overflow-hidden">
                                    <li v-for="subItem in item.children" :key="subItem.title">
                                        <Link
                                            prefetch
                                            v-if="subItem.show"
                                            :href="subItem.href"
                                            :class="[
                                                isActive(subItem)
                                                    ? 'text-primary-700 bg-primary-50 font-semibold dark:text-primary-300 dark:bg-primary-900/20 border-l-2 border-primary-600'
                                                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-800/30 border-l-2 border-transparent',
                                                'block rounded-r-lg py-1.5 pl-10 pr-3 text-sm transition-all duration-150'
                                            ]"
                                        >
                                            {{ $t(subItem.title) }}
                                        </Link>
                                    </li>
                                </DisclosurePanel>
                            </transition>
                        </Disclosure>
                    </li>
                </ul>
            </li>

            <!-- Logout -->
            <li class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-700">
                <Link
                    :href="route('logout')"
                    class="group flex gap-x-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/10 transition-all duration-150"
                    method="post"
                    as="button"
                >
                    <PowerIcon class="h-5 w-5 shrink-0 text-gray-400 group-hover:text-red-500 dark:group-hover:text-red-400 transition-colors duration-150" aria-hidden="true" />
                    {{ $t('gestlab.menu.logout') }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
