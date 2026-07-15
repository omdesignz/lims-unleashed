import assert from 'node:assert/strict'
import { existsSync, readFileSync, readdirSync } from 'node:fs'
import test from 'node:test'

const appCss = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8')
const appBladeSource = readFileSync(new URL('../../resources/views/app.blade.php', import.meta.url), 'utf8')
const appBootstrapSource = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8')
const publicLandingSource = readFileSync(new URL('../../resources/js/Pages/Public/Landing.vue', import.meta.url), 'utf8')
const publicLandingHero = new URL('../../public/images/lims-laboratory-hero.webp', import.meta.url)
const layoutSource = readFileSync(new URL('../../resources/js/Shared/Layouts/Layout.vue', import.meta.url), 'utf8')
const portalLayoutSource = readFileSync(new URL('../../resources/js/Shared/Layouts/PortalLayout.vue', import.meta.url), 'utf8')
const portalDashboardSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Dashboard.vue', import.meta.url), 'utf8')
const portalServicesSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Services/Index.vue', import.meta.url), 'utf8')
const portalProfileSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Profile.vue', import.meta.url), 'utf8')
const portalDocumentLibrarySource = readFileSync(new URL('../../resources/js/Components/portal/PortalDocumentLibrary.vue', import.meta.url), 'utf8')
const portalInvoicesSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Invoices/Index.vue', import.meta.url), 'utf8')
const portalReceiptsSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Receipts/Index.vue', import.meta.url), 'utf8')
const portalCreditNotesSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/CreditNotes/Index.vue', import.meta.url), 'utf8')
const portalQuotesSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Quotes/Index.vue', import.meta.url), 'utf8')
const portalContractGuidesSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/ContractGuides/Index.vue', import.meta.url), 'utf8')
const portalQualityCertificatesSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/QualityCertificates/Index.vue', import.meta.url), 'utf8')
const portalCollectionsSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Collections/Index.vue', import.meta.url), 'utf8')
const portalFaqsSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/FAQs/Index.vue', import.meta.url), 'utf8')
const portalRequestsSource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Requests/Index.vue', import.meta.url), 'utf8')
const portalRequestFormSource = readFileSync(new URL('../../resources/js/Components/portal/PortalRequestForm.vue', import.meta.url), 'utf8')
const portalSecuritySource = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Security.vue', import.meta.url), 'utf8')
const passkeyManagementSource = readFileSync(new URL('../../resources/js/Pages/Profile/Partials/passkey-management-form.vue', import.meta.url), 'utf8')
const profileSettingsSource = readFileSync(new URL('../../resources/js/Pages/Profile/Show.vue', import.meta.url), 'utf8')
const userHelpSource = readFileSync(new URL('../../resources/js/Pages/User/Help.vue', import.meta.url), 'utf8')
const settingsSectionSource = readFileSync(new URL('../../resources/js/Components/settings/SettingsSection.vue', import.meta.url), 'utf8')
const profileInformationSource = readFileSync(new URL('../../resources/js/Pages/Profile/Partials/update-profile-information-form.vue', import.meta.url), 'utf8')
const profilePasswordSource = readFileSync(new URL('../../resources/js/Pages/Profile/Partials/update-password-form.vue', import.meta.url), 'utf8')
const profileTwoFactorSource = readFileSync(new URL('../../resources/js/Pages/Profile/Partials/two-factor-authentication-form.vue', import.meta.url), 'utf8')
const profileSessionsSource = readFileSync(new URL('../../resources/js/Pages/Profile/Partials/logout-other-browser-sessions-form.vue', import.meta.url), 'utf8')
const deleteUserSource = readFileSync(new URL('../../resources/js/Shared/delete-user-form.vue', import.meta.url), 'utf8')
const confirmsPasswordSource = readFileSync(new URL('../../resources/js/Components/confirms-password.vue', import.meta.url), 'utf8')
const signaturePadSource = readFileSync(new URL('../../resources/js/Components/signature-pad.vue', import.meta.url), 'utf8')
const authExperienceShellSource = readFileSync(new URL('../../resources/js/Components/auth/AuthExperienceShell.vue', import.meta.url), 'utf8')
const authRegisterSource = readFileSync(new URL('../../resources/js/Pages/Auth/Register.vue', import.meta.url), 'utf8')
const authForgotPasswordSource = readFileSync(new URL('../../resources/js/Pages/Auth/ForgotPassword.vue', import.meta.url), 'utf8')
const authResetPasswordSource = readFileSync(new URL('../../resources/js/Pages/Auth/ResetPassword.vue', import.meta.url), 'utf8')
const authVerifyEmailSource = readFileSync(new URL('../../resources/js/Pages/Auth/VerifyEmail.vue', import.meta.url), 'utf8')
const authConfirmPasswordSource = readFileSync(new URL('../../resources/js/Pages/Auth/ConfirmPassword.vue', import.meta.url), 'utf8')
const authTwoFactorChallengeSource = readFileSync(new URL('../../resources/js/Pages/Auth/TwoFactorChallenge.vue', import.meta.url), 'utf8')
const inputSource = readFileSync(new URL('../../resources/js/Components/base/BaseInput.vue', import.meta.url), 'utf8')
const dateTimePickerSource = readFileSync(new URL('../../resources/js/Components/base/DateTimePicker.vue', import.meta.url), 'utf8')
const selectSource = readFileSync(new URL('../../resources/js/Components/base/BaseSelect.vue', import.meta.url), 'utf8')
const componentSelectInputSource = readFileSync(new URL('../../resources/js/Components/select-input.vue', import.meta.url), 'utf8')
const textareaSource = readFileSync(new URL('../../resources/js/Components/base/BaseTextarea.vue', import.meta.url), 'utf8')
const moduleHeroSource = readFileSync(new URL('../../resources/js/Components/base/ModuleHero.vue', import.meta.url), 'utf8')
const sideNavSource = readFileSync(new URL('../../resources/js/Shared/Navigation/side-nav.vue', import.meta.url), 'utf8')
const premiumDocumentStyleSource = readFileSync(new URL('../../resources/views/PDFs/partials/premium-document-style.blade.php', import.meta.url), 'utf8')
const documentLetterheadSource = readFileSync(new URL('../../resources/views/PDFs/partials/document-letterhead.blade.php', import.meta.url), 'utf8')
const documentBrandLogoSource = readFileSync(new URL('../../resources/views/PDFs/partials/brand-logo.blade.php', import.meta.url), 'utf8')
const legacyAnalysisReportSource = readFileSync(new URL('../../resources/views/PDFs/analysisreport.blade.php', import.meta.url), 'utf8')
const legacyAnalysisReportNewModelSource = readFileSync(new URL('../../resources/views/PDFs/analysisreport_new_model.blade.php', import.meta.url), 'utf8')
const legacySharedLayoutSource = readFileSync(new URL('../../resources/js/Shared/Layout.vue', import.meta.url), 'utf8')
const navItemSource = readFileSync(new URL('../../resources/js/Shared/Navigation/nav-item.vue', import.meta.url), 'utf8')
const mainMenuSource = readFileSync(new URL('../../resources/js/Shared/Navigation/main-menu.vue', import.meta.url), 'utf8')
const profileDropdownSource = readFileSync(new URL('../../resources/js/Shared/Navigation/profile-dropdown.vue', import.meta.url), 'utf8')
const slideOverMenuSource = readFileSync(new URL('../../resources/js/Shared/Navigation/slide-over-menu.vue', import.meta.url), 'utf8')
const componentMenuItemSource = readFileSync(new URL('../../resources/js/Components/menu-item.vue', import.meta.url), 'utf8')
const quickStatsSource = readFileSync(new URL('../../resources/js/Components/quick-stats.vue', import.meta.url), 'utf8')
const quickMenuSource = readFileSync(new URL('../../resources/js/Components/quick-menu.vue', import.meta.url), 'utf8')
const dashboardSource = readFileSync(new URL('../../resources/js/Pages/Dashboard.vue', import.meta.url), 'utf8')
const metricsIndexSource = readFileSync(new URL('../../resources/js/Pages/Metrics/Index.vue', import.meta.url), 'utf8')
const systemActivitySource = readFileSync(new URL('../../resources/js/Pages/SystemActivity/Index.vue', import.meta.url), 'utf8')
const qmsIndexSource = readFileSync(new URL('../../resources/js/Pages/QMS/Index.vue', import.meta.url), 'utf8')
const responsibilitiesIndexSource = readFileSync(new URL('../../resources/js/Pages/Responsibilities/Index.vue', import.meta.url), 'utf8')
const uncertaintySourcesIndexSource = readFileSync(new URL('../../resources/js/Pages/UncertaintySources/Index.vue', import.meta.url), 'utf8')
const counterAnalysisIndexSource = readFileSync(new URL('../../resources/js/Pages/CounterAnalysis/Index.vue', import.meta.url), 'utf8')
const environmentalConditionsIndexSource = readFileSync(new URL('../../resources/js/Pages/EnvironmentalConditions/Index.vue', import.meta.url), 'utf8')
const inventoryIndexSource = readFileSync(new URL('../../resources/js/Pages/Inventory/Index.vue', import.meta.url), 'utf8')
const inventoryShowSource = readFileSync(new URL('../../resources/js/Pages/Inventory/Show.vue', import.meta.url), 'utf8')
const inventoryTransactionsIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryTransactions/Index.vue', import.meta.url), 'utf8')
const inventoryTransactionsShowSource = readFileSync(new URL('../../resources/js/Pages/InventoryTransactions/Show.vue', import.meta.url), 'utf8')
const inventoryItemSuppliersIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryItemSuppliers/Index.vue', import.meta.url), 'utf8')
const inventoryItemLocationsIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryItemLocations/Index.vue', import.meta.url), 'utf8')
const inventoryItemWarehousesIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryItemWarehouses/Index.vue', import.meta.url), 'utf8')
const inventoryDeliveriesIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryDeliveries/Index.vue', import.meta.url), 'utf8')
const inventoryDeliveriesCreateSource = readFileSync(new URL('../../resources/js/Pages/InventoryDeliveries/Create.vue', import.meta.url), 'utf8')
const inventoryDeliveriesEditSource = readFileSync(new URL('../../resources/js/Pages/InventoryDeliveries/Edit.vue', import.meta.url), 'utf8')
const inventoryDeliveryFormSource = readFileSync(new URL('../../resources/js/Pages/InventoryDeliveries/InventoryDeliveryForm.vue', import.meta.url), 'utf8')
const inventoryItemTypesIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryItemTypes/Index.vue', import.meta.url), 'utf8')
const resultCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/ResultCategories/Index.vue', import.meta.url), 'utf8')
const formulasIndexSource = readFileSync(new URL('../../resources/js/Pages/Formulas/Index.vue', import.meta.url), 'utf8')
const formulasCreateSource = readFileSync(new URL('../../resources/js/Pages/Formulas/Create.vue', import.meta.url), 'utf8')
const formulasEditSource = readFileSync(new URL('../../resources/js/Pages/Formulas/Edit.vue', import.meta.url), 'utf8')
const formulaEditorFormSource = readFileSync(new URL('../../resources/js/Components/formulas/FormulaEditorForm.vue', import.meta.url), 'utf8')
const variablesIndexSource = readFileSync(new URL('../../resources/js/Pages/Variables/Index.vue', import.meta.url), 'utf8')
const vehiclesIndexSource = readFileSync(new URL('../../resources/js/Pages/Vehicles/Index.vue', import.meta.url), 'utf8')
const faqsIndexSource = readFileSync(new URL('../../resources/js/Pages/FAQs/Index.vue', import.meta.url), 'utf8')
const faqAnswersIndexSource = readFileSync(new URL('../../resources/js/Pages/FAQAnswers/Index.vue', import.meta.url), 'utf8')
const knowledgeRegistrySource = readFileSync(new URL('../../resources/js/Pages/FAQs/KnowledgeRegistry.vue', import.meta.url), 'utf8')
const productsIndexSource = readFileSync(new URL('../../resources/js/Pages/Products/Index.vue', import.meta.url), 'utf8')
const productsCreateSource = readFileSync(new URL('../../resources/js/Pages/Products/Create.vue', import.meta.url), 'utf8')
const productsEditSource = readFileSync(new URL('../../resources/js/Pages/Products/Edit.vue', import.meta.url), 'utf8')
const productFormSource = readFileSync(new URL('../../resources/js/Pages/Products/ProductForm.vue', import.meta.url), 'utf8')
const phytosanitaryProductsIndexSource = readFileSync(new URL('../../resources/js/Pages/PhytosanitaryProducts/Index.vue', import.meta.url), 'utf8')
const proficiencyTestsIndexSource = readFileSync(new URL('../../resources/js/Pages/ProficiencyTest/Index.vue', import.meta.url), 'utf8')
const proficiencyTestsShowSource = readFileSync(new URL('../../resources/js/Pages/ProficiencyTest/Show.vue', import.meta.url), 'utf8')
const reportStudiosIndexSource = readFileSync(new URL('../../resources/js/Pages/ReportStudios/Index.vue', import.meta.url), 'utf8')
const reportStudioWorkbenchSource = readFileSync(new URL('../../resources/js/Components/report-studio/studio-workbench.vue', import.meta.url), 'utf8')
const fileManagerIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPFileManager/Index.vue', import.meta.url), 'utf8')
const fileManagerListSource = readFileSync(new URL('../../resources/js/Components/vap-filemanager/file-list.vue', import.meta.url), 'utf8')
const fileManagerStoreSource = readFileSync(new URL('../../resources/js/Stores/fileStore.ts', import.meta.url), 'utf8')
const importCertificatesIndexSource = readFileSync(new URL('../../resources/js/Pages/ImportCertificates/Index.vue', import.meta.url), 'utf8')
const exportCertificatesIndexSource = readFileSync(new URL('../../resources/js/Pages/ExportCertificates/Index.vue', import.meta.url), 'utf8')
const tradeCertificateRegisterSource = readFileSync(new URL('../../resources/js/Components/certificates/TradeCertificateRegister.vue', import.meta.url), 'utf8')
const importCertificatesShowSource = readFileSync(new URL('../../resources/js/Pages/ImportCertificates/Show.vue', import.meta.url), 'utf8')
const exportCertificatesShowSource = readFileSync(new URL('../../resources/js/Pages/ExportCertificates/Show.vue', import.meta.url), 'utf8')
const tradeCertificateDetailSource = readFileSync(new URL('../../resources/js/Components/certificates/TradeCertificateDetail.vue', import.meta.url), 'utf8')
const importCertificatesCreateSource = readFileSync(new URL('../../resources/js/Pages/ImportCertificates/Create.vue', import.meta.url), 'utf8')
const importCertificatesEditSource = readFileSync(new URL('../../resources/js/Pages/ImportCertificates/Edit.vue', import.meta.url), 'utf8')
const exportCertificatesCreateSource = readFileSync(new URL('../../resources/js/Pages/ExportCertificates/Create.vue', import.meta.url), 'utf8')
const exportCertificatesEditSource = readFileSync(new URL('../../resources/js/Pages/ExportCertificates/Edit.vue', import.meta.url), 'utf8')
const tradeCertificateFormSource = readFileSync(new URL('../../resources/js/Components/certificates/TradeCertificateForm.vue', import.meta.url), 'utf8')
const vapMaintenanceDashboardSource = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Dashboard.vue', import.meta.url), 'utf8')
const vapMaintenanceCategoriesSource = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Categories/Index.vue', import.meta.url), 'utf8')
const vapMaintenanceTasksIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Index.vue', import.meta.url), 'utf8')
const vapMaintenanceTasksCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Create.vue', import.meta.url), 'utf8')
const vapMaintenanceTasksShowSource = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Show.vue', import.meta.url), 'utf8')
const vapSamplesIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPSamples/Index.vue', import.meta.url), 'utf8')
const sampleRegistrySource = readFileSync(new URL('../../resources/js/Pages/Samples/Index.vue', import.meta.url), 'utf8')
const vapSamplesShowSource = readFileSync(new URL('../../resources/js/Pages/VAPSamples/Show.vue', import.meta.url), 'utf8')
const vapSamplesReportsSource = readFileSync(new URL('../../resources/js/Pages/VAPSamples/Reports.vue', import.meta.url), 'utf8')
const analysisIndexSource = readFileSync(new URL('../../resources/js/Pages/Analysis/Index.vue', import.meta.url), 'utf8')
const analysisResultsWorkflowSource = readFileSync(new URL('../../resources/js/Pages/Analysis/ResultsWorkflow.vue', import.meta.url), 'utf8')
const analysisInsertResultSource = readFileSync(new URL('../../resources/js/Components/results/InsertResultComponent.vue', import.meta.url), 'utf8')
const analysisVerifyResultSource = readFileSync(new URL('../../resources/js/Components/results/VerifyResultComponent.vue', import.meta.url), 'utf8')
const analysisApproveResultSource = readFileSync(new URL('../../resources/js/Components/results/ApproveResultComponent.vue', import.meta.url), 'utf8')
const analysisResultReviewSource = readFileSync(new URL('../../resources/js/Components/results/ResultReviewSurface.vue', import.meta.url), 'utf8')
const analysisResultItemSource = readFileSync(new URL('../../resources/js/Components/results/ResultItem.vue', import.meta.url), 'utf8')
const analysisIndividualResultSource = readFileSync(new URL('../../resources/js/Components/results/IndividualResultEntry.vue', import.meta.url), 'utf8')
const analysisCalculationEntrySource = readFileSync(new URL('../../resources/js/Components/results/CalculationResultEntry.vue', import.meta.url), 'utf8')
const calculationModalSource = readFileSync(new URL('../../resources/js/Components/results/CalculationModal.vue', import.meta.url), 'utf8')
const worksheetsIndexSource = readFileSync(new URL('../../resources/js/Pages/Worksheets/Index.vue', import.meta.url), 'utf8')
const worksheetsEditSource = readFileSync(new URL('../../resources/js/Pages/Worksheets/Edit.vue', import.meta.url), 'utf8')
const customerRequestsIndexSource = readFileSync(new URL('../../resources/js/Pages/CustomerRequests/Index.vue', import.meta.url), 'utf8')
const customerRequestsCreateSource = readFileSync(new URL('../../resources/js/Pages/CustomerRequests/Create.vue', import.meta.url), 'utf8')
const customerRequestsEditSource = readFileSync(new URL('../../resources/js/Pages/CustomerRequests/Edit.vue', import.meta.url), 'utf8')
const customerRequestFormSource = readFileSync(new URL('../../resources/js/Components/customer-requests/CustomerRequestForm.vue', import.meta.url), 'utf8')
const customersIndexSource = readFileSync(new URL('../../resources/js/Pages/Customers/Index.vue', import.meta.url), 'utf8')
const customersCreateSource = readFileSync(new URL('../../resources/js/Pages/Customers/Create.vue', import.meta.url), 'utf8')
const customersEditSource = readFileSync(new URL('../../resources/js/Pages/Customers/Edit.vue', import.meta.url), 'utf8')
const customersShowSource = readFileSync(new URL('../../resources/js/Pages/Customers/Show.vue', import.meta.url), 'utf8')
const customerTaxIdentificationSource = readFileSync(new URL('../../resources/js/Pages/Customers/TaxIdentification.vue', import.meta.url), 'utf8')
const customerFormSource = readFileSync(new URL('../../resources/js/Components/customers/CustomerForm.vue', import.meta.url), 'utf8')
const customerSiteEditorSource = readFileSync(new URL('../../resources/js/Pages/Warehouses/warehouse-component.vue', import.meta.url), 'utf8')
const warehousesIndexSource = readFileSync(new URL('../../resources/js/Pages/Warehouses/Index.vue', import.meta.url), 'utf8')
const warehousesShowSource = readFileSync(new URL('../../resources/js/Pages/Warehouses/Show.vue', import.meta.url), 'utf8')
const notificationAdminHeaderSource = readFileSync(new URL('../../resources/js/Components/notifications/NotificationAdminHeader.vue', import.meta.url), 'utf8')
const notificationPresentationSource = readFileSync(new URL('../../resources/js/Composables/useNotificationPresentation.js', import.meta.url), 'utf8')
const notificationDashboardSource = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Dashboard.vue', import.meta.url), 'utf8')
const notificationIndexSource = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Index.vue', import.meta.url), 'utf8')
const notificationCreateSource = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Create.vue', import.meta.url), 'utf8')
const notificationShowSource = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Show.vue', import.meta.url), 'utf8')
const notificationAnalyticsSource = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Analytics.vue', import.meta.url), 'utf8')
const personalNotificationInboxSource = readFileSync(new URL('../../resources/js/Pages/Notifications/Index.vue', import.meta.url), 'utf8')
const messagesIndexSource = readFileSync(new URL('../../resources/js/Pages/Messages/Index.vue', import.meta.url), 'utf8')
const messagesCreateSource = readFileSync(new URL('../../resources/js/Pages/Messages/Create.vue', import.meta.url), 'utf8')
const messagesEditSource = readFileSync(new URL('../../resources/js/Pages/Messages/Edit.vue', import.meta.url), 'utf8')
const paidServicesIndexSource = readFileSync(new URL('../../resources/js/Pages/PaidServices/Index.vue', import.meta.url), 'utf8')
const paidServicesCreateSource = readFileSync(new URL('../../resources/js/Pages/PaidServices/Create.vue', import.meta.url), 'utf8')
const paidServicesEditSource = readFileSync(new URL('../../resources/js/Pages/PaidServices/Edit.vue', import.meta.url), 'utf8')
const paidServiceFormSource = readFileSync(new URL('../../resources/js/Components/paid-services/PaidServiceForm.vue', import.meta.url), 'utf8')
const customerCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/CustomerCategories/Index.vue', import.meta.url), 'utf8')
const contactCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/ContactCategories/Index.vue', import.meta.url), 'utf8')
const customerRequestCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/CustomerRequestCategories/Index.vue', import.meta.url), 'utf8')
const standardsIndexSource = readFileSync(new URL('../../resources/js/Pages/Standards/Index.vue', import.meta.url), 'utf8')
const standardsCreateSource = readFileSync(new URL('../../resources/js/Pages/Standards/Create.vue', import.meta.url), 'utf8')
const standardsEditSource = readFileSync(new URL('../../resources/js/Pages/Standards/Edit.vue', import.meta.url), 'utf8')
const standardFormSource = readFileSync(new URL('../../resources/js/Components/standards/StandardForm.vue', import.meta.url), 'utf8')
const referenceCatalogManagerSource = readFileSync(new URL('../../resources/js/Components/catalogs/ReferenceCatalogManager.vue', import.meta.url), 'utf8')
const analysisCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/AnalysisCategories/Index.vue', import.meta.url), 'utf8')
const protocolsIndexSource = readFileSync(new URL('../../resources/js/Pages/Protocols/Index.vue', import.meta.url), 'utf8')
const nwpsIndexSource = readFileSync(new URL('../../resources/js/Pages/NormativeWorkProcedures/Index.vue', import.meta.url), 'utf8')
const unitsIndexSource = readFileSync(new URL('../../resources/js/Pages/Units/Index.vue', import.meta.url), 'utf8')
const occurrenceStatusesIndexSource = readFileSync(new URL('../../resources/js/Pages/OccurrenceStatuses/Index.vue', import.meta.url), 'utf8')
const temperaturesIndexSource = readFileSync(new URL('../../resources/js/Pages/Temperatures/Index.vue', import.meta.url), 'utf8')
const equipmentCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/EquipmentCategories/Index.vue', import.meta.url), 'utf8')
const inventoryTransactionTypesIndexSource = readFileSync(new URL('../../resources/js/Pages/InventoryTransactionTypes/Index.vue', import.meta.url), 'utf8')
const faqCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/FAQCategories/Index.vue', import.meta.url), 'utf8')
const discountCategoriesIndexSource = readFileSync(new URL('../../resources/js/Pages/DiscountCategories/Index.vue', import.meta.url), 'utf8')
const taxExemptionsIndexSource = readFileSync(new URL('../../resources/js/Pages/TaxExemptions/Index.vue', import.meta.url), 'utf8')
const operationalReferenceCatalogSources = [
  'InvoiceCategories',
  'ItemCategories',
  'CollectionReasons',
  'ItemStatuses',
  'TransportCategories',
  'CollectionEndResults',
  'InventoryUnits',
  'OccurrenceCategories',
  'PaymentCategories',
  'OccurrenceOrigins',
  'Countries',
  'PackagingCategories',
  'CollectionCollaborations',
  'TaxTypes',
  'Currencies',
].map((page) => readFileSync(new URL(`../../resources/js/Pages/${page}/Index.vue`, import.meta.url), 'utf8'))
const vapLabSources = ['Index', 'Create', 'Edit', 'Show', 'LabForm']
  .map((page) => readFileSync(new URL(`../../resources/js/Pages/VAPLabs/${page}.vue`, import.meta.url), 'utf8'))
const parametersIndexSource = readFileSync(new URL('../../resources/js/Pages/Parameters/Index.vue', import.meta.url), 'utf8')
const parametersCreateSource = readFileSync(new URL('../../resources/js/Pages/Parameters/Create.vue', import.meta.url), 'utf8')
const parametersEditSource = readFileSync(new URL('../../resources/js/Pages/Parameters/Edit.vue', import.meta.url), 'utf8')
const parameterFormSource = readFileSync(new URL('../../resources/js/Components/parameters/ParameterForm.vue', import.meta.url), 'utf8')
const parameterFormDataSource = readFileSync(new URL('../../resources/js/Components/parameters/parameterFormData.js', import.meta.url), 'utf8')
const profilesIndexSource = readFileSync(new URL('../../resources/js/Pages/Profiles/Index.vue', import.meta.url), 'utf8')
const usersIndexSource = readFileSync(new URL('../../resources/js/Pages/Users/Index.vue', import.meta.url), 'utf8')
const usersEditSource = readFileSync(new URL('../../resources/js/Pages/Users/Edit.vue', import.meta.url), 'utf8')
const departmentsIndexSource = readFileSync(new URL('../../resources/js/Pages/Departments/Index.vue', import.meta.url), 'utf8')
const permissionsIndexSource = readFileSync(new URL('../../resources/js/Pages/Permissions/Index.vue', import.meta.url), 'utf8')
const rolesIndexSource = readFileSync(new URL('../../resources/js/Pages/Roles/Index.vue', import.meta.url), 'utf8')
const rolesEditSource = readFileSync(new URL('../../resources/js/Pages/Roles/Edit.vue', import.meta.url), 'utf8')
const occurrencesIndexSource = readFileSync(new URL('../../resources/js/Pages/Occurrences/Index.vue', import.meta.url), 'utf8')
const occurrenceImportFormSource = readFileSync(new URL('../../resources/js/Pages/Occurrences/occurrences-import-form.vue', import.meta.url), 'utf8')
const occurrenceCreateSource = readFileSync(new URL('../../resources/js/Pages/Occurrences/Create.vue', import.meta.url), 'utf8')
const occurrenceEditSource = readFileSync(new URL('../../resources/js/Pages/Occurrences/Edit.vue', import.meta.url), 'utf8')
const occurrenceFormSource = readFileSync(new URL('../../resources/js/Pages/Occurrences/OccurrenceForm.vue', import.meta.url), 'utf8')
const occurrenceShowSource = readFileSync(new URL('../../resources/js/Pages/Occurrences/Show.vue', import.meta.url), 'utf8')
const complaintsIndexSource = readFileSync(new URL('../../resources/js/Pages/Complaints/Index.vue', import.meta.url), 'utf8')
const managementReviewsIndexSource = readFileSync(new URL('../../resources/js/Pages/ManagementReviews/Index.vue', import.meta.url), 'utf8')
const applicationEventsIndexSource = readFileSync(new URL('../../resources/js/Pages/ApplicationEvents/Index.vue', import.meta.url), 'utf8')
const archivedDocumentsIndexSource = readFileSync(new URL('../../resources/js/Pages/ArchivedDocument/Index.vue', import.meta.url), 'utf8')
const archivedDocumentsCreateSource = readFileSync(new URL('../../resources/js/Pages/ArchivedDocument/Create.vue', import.meta.url), 'utf8')
const archivedDocumentsEditSource = readFileSync(new URL('../../resources/js/Pages/ArchivedDocument/Edit.vue', import.meta.url), 'utf8')
const archivedDocumentFormSource = readFileSync(new URL('../../resources/js/Pages/ArchivedDocument/ArchivedDocumentForm.vue', import.meta.url), 'utf8')
const contractGuidesIndexSource = readFileSync(new URL('../../resources/js/Pages/ContractGuides/Index.vue', import.meta.url), 'utf8')
const contractGuidesCreateSource = readFileSync(new URL('../../resources/js/Pages/ContractGuides/Create.vue', import.meta.url), 'utf8')
const contractGuidesEditSource = readFileSync(new URL('../../resources/js/Pages/ContractGuides/Edit.vue', import.meta.url), 'utf8')
const contractGuideFormSource = readFileSync(new URL('../../resources/js/Pages/ContractGuides/ContractGuideForm.vue', import.meta.url), 'utf8')
const profilesCreateSource = readFileSync(new URL('../../resources/js/Pages/Profiles/Create.vue', import.meta.url), 'utf8')
const profilesEditSource = readFileSync(new URL('../../resources/js/Pages/Profiles/Edit.vue', import.meta.url), 'utf8')
const profilesShowSource = readFileSync(new URL('../../resources/js/Pages/Profiles/Show.vue', import.meta.url), 'utf8')
const profileFormSource = readFileSync(new URL('../../resources/js/Components/profiles/ProfileForm.vue', import.meta.url), 'utf8')
const profileFormDataSource = readFileSync(new URL('../../resources/js/Components/profiles/profileFormData.js', import.meta.url), 'utf8')
const matrixesIndexSource = readFileSync(new URL('../../resources/js/Pages/Matrixes/Index.vue', import.meta.url), 'utf8')
const matrixesCreateSource = readFileSync(new URL('../../resources/js/Pages/Matrixes/Create.vue', import.meta.url), 'utf8')
const matrixesEditSource = readFileSync(new URL('../../resources/js/Pages/Matrixes/Edit.vue', import.meta.url), 'utf8')
const matrixesShowSource = readFileSync(new URL('../../resources/js/Pages/Matrixes/Show.vue', import.meta.url), 'utf8')
const matrixFormSource = readFileSync(new URL('../../resources/js/Components/matrixes/MatrixForm.vue', import.meta.url), 'utf8')
const matrixFormDataSource = readFileSync(new URL('../../resources/js/Components/matrixes/matrixFormData.js', import.meta.url), 'utf8')
const toggleFieldSource = readFileSync(new URL('../../resources/js/Components/base/ToggleField.vue', import.meta.url), 'utf8')
const recordsTableSource = readFileSync(new URL('../../resources/js/Components/records-table.vue', import.meta.url), 'utf8')
const vapTableSource = readFileSync(new URL('../../resources/js/Components/vap-table/table.vue', import.meta.url), 'utf8')
const vapTableHeaderSource = readFileSync(new URL('../../resources/js/Components/vap-table/table-header.vue', import.meta.url), 'utf8')
const vapTableBodySource = readFileSync(new URL('../../resources/js/Components/vap-table/table-body.vue', import.meta.url), 'utf8')
const dataTableShellSource = readFileSync(new URL('../../resources/js/Components/tables/DataTableShell.vue', import.meta.url), 'utf8')
const dataTableSource = readFileSync(new URL('../../resources/js/Components/tables/DataTable.vue', import.meta.url), 'utf8')
const comboboxSource = readFileSync(new URL('../../resources/js/Components/combobox-enhanced.vue', import.meta.url), 'utf8')
const baseComboboxSource = readFileSync(new URL('../../resources/js/Components/combobox.vue', import.meta.url), 'utf8')
const multipleComboboxSource = readFileSync(new URL('../../resources/js/Components/combobox-multiple.vue', import.meta.url), 'utf8')
const tableMultipleComboboxSource = readFileSync(new URL('../../resources/js/Components/vap-table/combobox-multiple.vue', import.meta.url), 'utf8')
const breadcrumbsSource = readFileSync(new URL('../../resources/js/Components/breadcrumbs.vue', import.meta.url), 'utf8')
const datePickerSource = readFileSync(new URL('../../resources/js/Components/date-picker-enhanced.vue', import.meta.url), 'utf8')
const chartWrapperSource = readFileSync(new URL('../../resources/js/Components/apex-chart/ChartWrapper.vue', import.meta.url), 'utf8')
const inventoryAnalyticsSource = readFileSync(new URL('../../resources/js/Components/charts/inventory-analytics.vue', import.meta.url), 'utf8')
const vapInventoryAnalyticsIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Analytics/Index.vue', import.meta.url), 'utf8')
const vapInventoryItemsIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Index.vue', import.meta.url), 'utf8')
const vapInventoryItemsShowSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Show.vue', import.meta.url), 'utf8')
const inventoryItemFormSurfaceSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/InventoryItemFormSurface.vue', import.meta.url), 'utf8')
const inventoryOrderFormSurfaceSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/InventoryOrderFormSurface.vue', import.meta.url), 'utf8')
const adjustStockModalSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/AdjustStockModal.vue', import.meta.url), 'utf8')
const transferStockModalSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/TransferStockModal.vue', import.meta.url), 'utf8')
const consumeReagentModalSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/ConsumeReagentModal.vue', import.meta.url), 'utf8')
const recordCalibrationModalSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/RecordCalibrationModal.vue', import.meta.url), 'utf8')
const mobileScannerSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/mobile-scanner.vue', import.meta.url), 'utf8')
const dashSummarySource = readFileSync(new URL('../../resources/js/Components/vap-inventory/dash-summary.vue', import.meta.url), 'utf8')
const batchLabelGeneratorSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/batch-label-generator.vue', import.meta.url), 'utf8')
const batchSelectionSource = readFileSync(new URL('../../resources/js/Components/vap-inventory/batch-selection.vue', import.meta.url), 'utf8')
const vapInventoryItemsCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Create.vue', import.meta.url), 'utf8')
const vapInventoryItemsEditSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Edit.vue', import.meta.url), 'utf8')
const vapInventoryNeedsIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Needs/Index.vue', import.meta.url), 'utf8')
const vapInventoryNeedsCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Needs/Create.vue', import.meta.url), 'utf8')
const vapInventoryNeedsShowSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Needs/Show.vue', import.meta.url), 'utf8')
const vapInventoryOrdersIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Orders/Index.vue', import.meta.url), 'utf8')
const vapInventoryOrdersShowSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Orders/Show.vue', import.meta.url), 'utf8')
const vapInventoryOrdersCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Orders/Create.vue', import.meta.url), 'utf8')
const vapInventoryOrdersEditSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Orders/Edit.vue', import.meta.url), 'utf8')
const vapInventoryTransfersIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Transfers/Index.vue', import.meta.url), 'utf8')
const vapInventoryTransfersCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Transfers/Create.vue', import.meta.url), 'utf8')
const vapInventoryTransfersShowSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Transfers/Show.vue', import.meta.url), 'utf8')
const vapInventoryLowStockReportSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reports/LowStock.vue', import.meta.url), 'utf8')
const vapInventoryValueReportSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reports/InventoryValue.vue', import.meta.url), 'utf8')
const vapInventoryConsumptionReportSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reports/Consumption.vue', import.meta.url), 'utf8')
const vapInventoryStockMovementReportSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reports/StockMovement.vue', import.meta.url), 'utf8')
const vapInventoryExpiryReportSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reagents/ExpiryReport.vue', import.meta.url), 'utf8')
const vapInventoryCalibrationScheduleSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Calibration/Schedule.vue', import.meta.url), 'utf8')
const vapInventoryReagentConsumptionSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reagents/Consumption.vue', import.meta.url), 'utf8')
const vapInventoryReagentConsumptionCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reagents/CreateConsumption.vue', import.meta.url), 'utf8')
const vapInventoryReagentConsumptionShowSource = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reagents/ShowConsumption.vue', import.meta.url), 'utf8')
const vapNonConformitiesIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPNonConformities/Index.vue', import.meta.url), 'utf8')
const vapNonConformitiesCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPNonConformities/Create.vue', import.meta.url), 'utf8')
const vapNonConformitiesEditSource = readFileSync(new URL('../../resources/js/Pages/VAPNonConformities/Edit.vue', import.meta.url), 'utf8')
const vapNonConformitiesShowSource = readFileSync(new URL('../../resources/js/Pages/VAPNonConformities/Show.vue', import.meta.url), 'utf8')
const vapNonConformityFormSource = readFileSync(new URL('../../resources/js/Pages/VAPNonConformities/NonConformityForm.vue', import.meta.url), 'utf8')
const systemSettingsSource = readFileSync(new URL('../../resources/js/Pages/SystemSettings/Index.vue', import.meta.url), 'utf8')
const settingsFieldSource = readFileSync(new URL('../../resources/js/Components/settings/SettingsField.vue', import.meta.url), 'utf8')
const directCollectionsIndexSource = readFileSync(new URL('../../resources/js/Pages/DirectCollections/Index.vue', import.meta.url), 'utf8')
const directCollectionsShowSource = readFileSync(new URL('../../resources/js/Pages/DirectCollections/Show.vue', import.meta.url), 'utf8')
const programmedCollectionsIndexSource = readFileSync(new URL('../../resources/js/Pages/ProgrammedCollections/Index.vue', import.meta.url), 'utf8')
const directCollectionsCreateSource = readFileSync(new URL('../../resources/js/Pages/DirectCollections/Create.vue', import.meta.url), 'utf8')
const directCollectionsEditSource = readFileSync(new URL('../../resources/js/Pages/DirectCollections/Edit.vue', import.meta.url), 'utf8')
const programmedCollectionsCreateSource = readFileSync(new URL('../../resources/js/Pages/ProgrammedCollections/Create.vue', import.meta.url), 'utf8')
const programmedCollectionsEditSource = readFileSync(new URL('../../resources/js/Pages/ProgrammedCollections/Edit.vue', import.meta.url), 'utf8')
const collectionAccessionFormSource = readFileSync(new URL('../../resources/js/Components/collections/CollectionAccessionForm.vue', import.meta.url), 'utf8')
const supplierAssessmentsIndexSource = readFileSync(new URL('../../resources/js/Pages/SupplierAssessments/Index.vue', import.meta.url), 'utf8')
const labelPrintSettingsSource = readFileSync(new URL('../../resources/js/Components/LabelPrintSettings.vue', import.meta.url), 'utf8')
const backupStatusesSource = readFileSync(new URL('../../resources/js/Components/backup-statuses-list.vue', import.meta.url), 'utf8')
const backupsSource = readFileSync(new URL('../../resources/js/Components/backups.vue', import.meta.url), 'utf8')
const backupRowSource = readFileSync(new URL('../../resources/js/Components/backup-row.vue', import.meta.url), 'utf8')
const backupsPageSource = readFileSync(new URL('../../resources/js/Pages/Backups/Index.vue', import.meta.url), 'utf8')
const vapLabelsIndexSource = readFileSync(new URL('../../resources/js/Pages/VapLabels/Index.vue', import.meta.url), 'utf8')
const vapLabelsCreateSource = readFileSync(new URL('../../resources/js/Pages/VapLabels/Create.vue', import.meta.url), 'utf8')
const vapLabelsShowSource = readFileSync(new URL('../../resources/js/Pages/VapLabels/Show.vue', import.meta.url), 'utf8')
const vapLabelTemplatesIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPLabelTemplates/Index.vue', import.meta.url), 'utf8')
const vapLabelTemplateFormSource = readFileSync(new URL('../../resources/js/Pages/VAPLabelTemplates/LabelTemplateForm.vue', import.meta.url), 'utf8')
const vapProposalsIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPProposals/Index.vue', import.meta.url), 'utf8')
const vapProposalsCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPProposals/Create.vue', import.meta.url), 'utf8')
const vapProposalsEditSource = readFileSync(new URL('../../resources/js/Pages/VAPProposals/Edit.vue', import.meta.url), 'utf8')
const vapProposalsShowSource = readFileSync(new URL('../../resources/js/Pages/VAPProposals/Show.vue', import.meta.url), 'utf8')
const vapProposalTemplatesIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPProposalTemplates/Index.vue', import.meta.url), 'utf8')
const vapProposalTemplatesCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPProposalTemplates/Create.vue', import.meta.url), 'utf8')
const vapProposalTemplatesEditSource = readFileSync(new URL('../../resources/js/Pages/VAPProposalTemplates/Edit.vue', import.meta.url), 'utf8')
const vapProposalTemplatesShowSource = readFileSync(new URL('../../resources/js/Pages/VAPProposalTemplates/Show.vue', import.meta.url), 'utf8')
const proposalTemplateStudioSource = readFileSync(new URL('../../resources/js/Components/proposal-template/studio-workbench.vue', import.meta.url), 'utf8')
const invoicesIndexSource = readFileSync(new URL('../../resources/js/Pages/Invoices/Index.vue', import.meta.url), 'utf8')
const quotesIndexSource = readFileSync(new URL('../../resources/js/Pages/Quotes/Index.vue', import.meta.url), 'utf8')
const creditNotesIndexSource = readFileSync(new URL('../../resources/js/Pages/CreditNotes/Index.vue', import.meta.url), 'utf8')
const receiptsIndexSource = readFileSync(new URL('../../resources/js/Pages/Receipts/Index.vue', import.meta.url), 'utf8')
const invoicesCreateSource = readFileSync(new URL('../../resources/js/Pages/Invoices/Create.vue', import.meta.url), 'utf8')
const quotesCreateSource = readFileSync(new URL('../../resources/js/Pages/Quotes/Create.vue', import.meta.url), 'utf8')
const creditNotesCreateSource = readFileSync(new URL('../../resources/js/Pages/CreditNotes/Create.vue', import.meta.url), 'utf8')
const receiptsCreateSource = readFileSync(new URL('../../resources/js/Pages/Receipts/Create.vue', import.meta.url), 'utf8')
const commercialDocumentSurfaceSource = readFileSync(new URL('../../resources/js/Pages/CommercialDocumentSurface.css', import.meta.url), 'utf8')
const commercialDocumentOptionsSource = readFileSync(new URL('../../resources/js/Composables/useCommercialDocumentOptions.js', import.meta.url), 'utf8')
const invoicesEditSource = readFileSync(new URL('../../resources/js/Pages/Invoices/Edit.vue', import.meta.url), 'utf8')
const quotesEditSource = readFileSync(new URL('../../resources/js/Pages/Quotes/Edit.vue', import.meta.url), 'utf8')
const creditNotesEditSource = readFileSync(new URL('../../resources/js/Pages/CreditNotes/Edit.vue', import.meta.url), 'utf8')
const receiptsEditSource = readFileSync(new URL('../../resources/js/Pages/Receipts/Edit.vue', import.meta.url), 'utf8')
const validationSignatureSource = readFileSync(new URL('../../resources/js/Components/document-validation-signature.vue', import.meta.url), 'utf8')
const qualityCertificatesIndexSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/Index.vue', import.meta.url), 'utf8')
const qualityCertificatesShowSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/Show.vue', import.meta.url), 'utf8')
const qualityCertificatesEditSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/Edit.vue', import.meta.url), 'utf8')
const validationModalSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/validation-modal.vue', import.meta.url), 'utf8')
const isoRevisionIndexSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Index.vue', import.meta.url), 'utf8')
const isoRevisionCreateSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Create.vue', import.meta.url), 'utf8')
const isoRevisionShowSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Show.vue', import.meta.url), 'utf8')
const isoRevisionCompareSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Compare.vue', import.meta.url), 'utf8')
const isoRevisionAuditTrailSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/AuditTrail.vue', import.meta.url), 'utf8')
const isoRevisionManagerSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/ISORevisionManager.vue', import.meta.url), 'utf8')
const isoRevisionCreateModalSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Partials/CreateRevisionModal.vue', import.meta.url), 'utf8')
const isoRevisionComparisonModalSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Partials/ComparisonModal.vue', import.meta.url), 'utf8')
const isoRevisionRestoreModalSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/ISORevisions/Partials/RestoreRevisionModal.vue', import.meta.url), 'utf8')
const progressTrackerSource = readFileSync(new URL('../../resources/js/Components/progress-tracker.vue', import.meta.url), 'utf8')
const staffLoginSource = readFileSync(new URL('../../resources/js/Pages/Auth/Login.vue', import.meta.url), 'utf8')
const portalLoginSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/Login.vue', import.meta.url), 'utf8')
const portalRegisterSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/Register.vue', import.meta.url), 'utf8')
const portalForgotPasswordSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/ForgotPassword.vue', import.meta.url), 'utf8')
const portalResetPasswordSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/ResetPassword.vue', import.meta.url), 'utf8')
const portalVerifyEmailSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/VerifyEmail.vue', import.meta.url), 'utf8')
const portalConfirmPasswordSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/ConfirmPassword.vue', import.meta.url), 'utf8')
const portalTwoFactorChallengeSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/TwoFactorChallenge.vue', import.meta.url), 'utf8')
const boardsIndexSource = readFileSync(new URL('../../resources/js/Pages/Boards/Index.vue', import.meta.url), 'utf8')
const iconPickerSource = readFileSync(new URL('../../resources/js/Components/icon-picker.vue', import.meta.url), 'utf8')
const boardsShowSource = readFileSync(new URL('../../resources/js/Pages/Boards/Show.vue', import.meta.url), 'utf8')
const boardNameFormSource = readFileSync(new URL('../../resources/js/Pages/Boards/BoardNameForm.vue', import.meta.url), 'utf8')
const cardListSource = readFileSync(new URL('../../resources/js/Pages/Boards/CardList.vue', import.meta.url), 'utf8')
const cardListCreateFormSource = readFileSync(new URL('../../resources/js/Pages/Boards/CardListCreateForm.vue', import.meta.url), 'utf8')
const cardListItemSource = readFileSync(new URL('../../resources/js/Pages/Boards/CardListItem.vue', import.meta.url), 'utf8')
const cardListItemCreateFormSource = readFileSync(new URL('../../resources/js/Pages/Boards/CardListItemCreateForm.vue', import.meta.url), 'utf8')
const cardListItemModalSource = readFileSync(new URL('../../resources/js/Pages/Boards/CardListItemModal.vue', import.meta.url), 'utf8')

const legacyWarmPalettePattern = /#143d37|#d9b05f|#fffaf0|#ded3bf|#1f7a68|#07110f|#25443c|#15231f|#f7f1e7|bg-gradient-to-r/
const legacyExpressiveSurfacePattern = /#143d37|#d9b05f|#fffaf0|#ded3bf|#1f7a68|#07110f|#25443c|#15231f|#f7f1e7|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[/

function collectFiles(directoryUrl) {
  return readdirSync(directoryUrl, { withFileTypes: true }).flatMap((entry) => {
    const entryUrl = new URL(entry.name + (entry.isDirectory() ? '/' : ''), directoryUrl)

    return entry.isDirectory() ? collectFiles(entryUrl) : [entryUrl]
  })
}

const vueFiles = collectFiles(new URL('../../resources/js/', import.meta.url)).filter((file) => file.pathname.endsWith('.vue'))

test('defines a semantic product design contract for shared application surfaces', () => {
  for (const token of [
    '--ds-canvas',
    '--ds-panel',
    '--ds-panel-raised',
    '--ds-border',
    '--ds-text',
    '--ds-focus',
    '--lims-critical',
    '--lims-hold',
    '--lims-release',
    '--lims-instrument',
  ]) {
    assert.match(appCss, new RegExp(token))
  }

  for (const className of [
    '.ds-app-canvas',
    '.ds-panel',
    '.ds-card',
    '.ds-field',
    '.ds-badge',
    '.ds-button-primary',
    '.ds-modal-panel',
    '.ds-sidebar-panel',
    '.ds-nav-item',
    '.ds-settings-tab',
    '.ds-command-palette',
    '.ds-command-palette-item',
    '.ds-combobox-control',
    '.ds-floating-panel',
    '.ds-table-shell',
    '.ds-pagination',
    '.lims-status-strip',
    '.lims-dashboard-hero',
    '.lims-brand-mark',
  ]) {
    assert.match(appCss, new RegExp(className.replace('.', '\\.')))
  }

  assert.match(appCss, /family=ibm-plex-sans:400,500,600,700/)
  assert.match(appCss, /--font-sans: 'IBM Plex Sans'/)
  assert.doesNotMatch(appCss, /Manrope/)
  assert.doesNotMatch(appCss, /#143d37|#d9b05f|#fffaf0|#ded3bf|#1f7a68/)
  assert.doesNotMatch(appCss, /radial-gradient\(circle/)
})

test('semantic badges expose consistent operational states', () => {
  for (const className of [
    '.ds-badge-neutral',
    '.ds-badge-info',
    '.ds-badge-success',
    '.ds-badge-warning',
    '.ds-badge-danger',
  ]) {
    assert.match(appCss, new RegExp(className.replace('.', '\\.')))
  }
})

test('shared form controls expose explicit selection and picker states', () => {
  assert.match(appCss, /\.ds-checkbox:checked/)
  assert.match(appCss, /\.ds-checkbox:indeterminate/)
  assert.match(appCss, /background-image: url\("data:image\/svg\+xml/)
  assert.match(appCss, /input\[type="checkbox"\]:not\(\.peer\):not\(\.sr-only\):checked/)

  assert.match(dateTimePickerSource, /DatePicker as VDatePicker/)
  assert.match(dateTimePickerSource, /\['date', 'datetime-local', 'time'\]/)
  assert.match(dateTimePickerSource, /:is24hr="true"/)
  assert.match(dateTimePickerSource, /return props\.type === 'datetime-local' \? `\$\{date\}T\$\{time\}` : date/)
  assert.match(inputSource, /const isDateTimeField = computed/)
  assert.match(inputSource, /<DateTimePicker/)
  assert.match(appBootstrapSource, /\.component\("DateTimePicker", DateTimePicker\)/)

  assert.match(componentSelectInputSource, /ds-combobox-control/)
  assert.match(componentSelectInputSource, /ds-floating-panel/)
  assert.match(componentSelectInputSource, /aria-describedby/)
  assert.match(selectSource, /<Listbox/)
  assert.match(selectSource, /extractOptions\(slots\.default/)
  assert.match(selectSource, /:multiple="multiple"/)
  assert.match(appBootstrapSource, /\.component\("BaseSelect", BaseSelect\)/)

  for (const componentName of ['BaseInput', 'CheckboxInput', 'ColorInput', 'FileInput', 'RadioInput', 'RangeInput']) {
    assert.match(appBootstrapSource, new RegExp(`\\.component\\("${componentName}", ${componentName}\\)`))
  }

  const nativeInputPrimitivePaths = [
    '/Components/base/BaseInput.vue',
    '/Components/base/BaseSelect.vue',
    '/Components/base/CheckboxInput.vue',
    '/Components/base/DateTimePicker.vue',
    '/Components/base/FileInput.vue',
    '/Components/base/RadioInput.vue',
    '/Components/base/RangeInput.vue',
    '/Components/base/ToggleField.vue',
  ]

  for (const file of vueFiles) {
    const source = readFileSync(file, 'utf8')

    assert.doesNotMatch(source, /<select(?=[\s>])/i, `Native select remains in ${file.pathname}`)

    if (!nativeInputPrimitivePaths.some((path) => file.pathname.endsWith(path))) {
      for (const match of source.matchAll(/<input\b[^>]*>/gi)) {
        assert.match(match[0], /\btype=["']hidden["']/i, `Native user-facing input remains in ${file.pathname}`)
      }
    }
  }
})

test('analysis tables use semantic selection and data-density controls', () => {
  for (const source of [vapTableSource, vapTableHeaderSource, vapTableBodySource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /#ded3bf|#d8cbb8|#25443c|#315149|#fffdf7|#f7f1e7|#07110f|#10231f|#15231f|#143d37|rounded-\[/)
  }

  assert.match(dataTableShellSource, /class="ds-table-shell"/)
  assert.match(dataTableSource, /<table class="ds-data-table">/)
  assert.match(appBootstrapSource, /\.component\("DataTable", DataTable\)/)
  assert.match(vapTableSource, /<DataTableShell/)
  assert.match(recordsTableSource, /<DataTableShell/)
  assert.match(vapTableHeaderSource, /class="ds-checkbox"/)
  assert.match(vapTableBodySource, /class="ds-checkbox"/)
  assert.match(analysisIndexSource, /<VapTable/)

  for (const file of vueFiles) {
    if (file.pathname.endsWith('/Components/tables/DataTable.vue')) {
      continue
    }

    assert.doesNotMatch(readFileSync(file, 'utf8'), /<table(?=[\s>])/i, `Raw table remains in ${file.pathname}`)
  }
})

test('report studio editor chrome no longer uses the legacy warm workspace palette', () => {
  const editorTemplate = reportStudioWorkbenchSource.split('<template>')[1]?.split('<style>')[0] ?? ''
  const editorCss = reportStudioWorkbenchSource.split('<style>')[1] ?? ''

  assert.match(editorCss, /--studio-border: var\(--ds-border\)/)
  assert.match(editorCss, /--studio-surface: var\(--ds-panel-raised\)/)
  assert.doesNotMatch(editorCss, /#ded3bf|#eadfca|#d8cbb8|#25443c|#29483f|#315149|#15231f|#475a53|#6b7b74|#f8f4ea|#f7f1e7|#fffdf7|#fffaf0|#07110f|#10231f|#143d37|#d9b05f|#b98a34|#9a6e23|#efc76f|#f1d89e|#e9e0d1|#e7dece/)
  assert.doesNotMatch(editorTemplate, /(?:text|bg|border|ring|shadow)-\[?#(?:ded3bf|eadfca|25443c|29483f|15231f|475a53|6b7b74|f7f1e7|fffdf7|fffaf0|07110f|10231f|143d37|d9b05f)/)
  assert.match(reportStudioWorkbenchSource, /v-model="props\.form\.is_default" type="checkbox" class="ds-checkbox"/)
  assert.match(reportStudiosIndexSource, /focus-visible:ring-\[rgb\(var\(--primary-500-rgb\)\)\]/)
  assert.match(reportStudiosIndexSource, /<nav class="grid grid-cols-2 px-3 sm:flex sm:px-6" aria-label="Áreas do estúdio documental">/)
})

test('shared shell and primitives consume semantic design classes', () => {
  assert.match(layoutSource, /class="lims-app-shell min-h-dvh/)
  assert.match(layoutSource, /class="fixed inset-y-0 left-0 z-40 hidden flex-col/)
  assert.match(layoutSource, /class="ds-command-palette/)
  assert.match(layoutSource, /@click="openCommandPalette"/)
  assert.match(layoutSource, /@keydown\.enter\.prevent="activateFirstCommandPaletteResult"/)
  assert.match(layoutSource, /const filteredCommandGroups = computed/)
  assert.match(layoutSource, /window\.addEventListener\('keydown', handleCommandPaletteShortcut\)/)
  assert.match(layoutSource, /gestlab\.menu\.quality_compliance/)
  assert.match(layoutSource, /gestlab\.menu\.lab_operations/)
  assert.match(layoutSource, /gestlab\.menu\.inventory_analytics/)
  assert.match(layoutSource, /:collapsed="!desktopSidebarOpen"/)
  assert.match(layoutSource, /@open-command-palette="openCommandPaletteFromMobile"/)
  assert.doesNotMatch(layoutSource, /items\.slice\(0, 8\)|\.slice\(0, 8\)/)
  assert.match(layoutSource, /const normalizeSearchValue = \(value\)/)
  assert.match(layoutSource, /\.normalize\('NFD'\)/)
  assert.doesNotMatch(layoutSource, /clockTime|operationalStatus|lims-status-strip/)
  assert.match(sideNavSource, /path: '\/qualitycertificates'/)
  assert.doesNotMatch(layoutSource, /bg-gradient-to-b from-primary-500\/12 to-transparent/)
  assert.match(inputSource, /class="ds-field/)
  assert.match(inputSource, /aria-describedby/)
  assert.match(inputSource, /const generatedId = useId\(\)/)
  assert.match(selectSource, /const generatedId = useId\(\)/)
  assert.match(textareaSource, /const generatedId = useId\(\)/)
  assert.match(selectSource, /`field-\$\{generatedId\}`/)
  assert.match(textareaSource, /`field-\$\{generatedId\}`/)
  assert.match(moduleHeroSource, /class="ds-panel/)
  assert.match(sideNavSource, /defineEmits\(\['open-command-palette', 'navigate'\]\)/)
  assert.match(sideNavSource, /props\.collapsed \? 'justify-center px-2'/)
  assert.match(sideNavSource, /emit\('open-command-palette'\)/)
})

test('shared navigation uses the LIMS application contract', () => {
  assert.match(legacySharedLayoutSource, /<AppLayout :auth="page\.props\.auth"/)
  assert.match(legacySharedLayoutSource, /import AppLayout from '@\/Shared\/Layouts\/Layout\.vue'/)
  assert.doesNotMatch(legacySharedLayoutSource, /Popover|radial-gradient|linear-gradient/)

  for (const source of [
    navItemSource,
    mainMenuSource,
    profileDropdownSource,
    slideOverMenuSource,
    componentMenuItemSource,
  ]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, legacyExpressiveSurfacePattern)
  }

  assert.match(navItemSource, /ds-nav-item-active/)
  assert.match(mainMenuSource, /class="ds-command-palette overflow-hidden p-2"/)
  assert.match(profileDropdownSource, /class="ds-floating-panel/)
  assert.match(slideOverMenuSource, /class="ds-sidebar-panel/)
  assert.match(componentMenuItemSource, /class="ds-command-palette-item"/)
})

test('calendar and reduced-motion behavior are part of the visual contract', () => {
  assert.match(appCss, /\.vc-container,[\s\S]*var\(--ds-panel-raised\)/)
  assert.match(appCss, /@media \(prefers-reduced-motion: reduce\)/)
  assert.doesNotMatch(appCss, /\.form-select\s*\{\s*composes:/)
})

test('high-frequency data controls use the shared visual language', () => {
  assert.match(recordsTableSource, /class="ds-command-surface"/)
  assert.match(recordsTableSource, /<DataTableShell>/)
  assert.match(recordsTableSource, /class="ds-field pl-10"/)
  assert.doesNotMatch(recordsTableSource, /color="blue"/)

  assert.match(comboboxSource, /class="ds-combobox-control/)
  assert.match(comboboxSource, /class="ds-floating-panel/)
  assert.match(baseComboboxSource, /defineOptions\(\{ inheritAttrs: false \}\)/)
  assert.match(baseComboboxSource, /v-bind="\$attrs" class="relative mt-0"/)
  assert.match(multipleComboboxSource, /class="ds-chip"/)
  assert.match(multipleComboboxSource, /class="ds-floating-panel/)
  assert.match(tableMultipleComboboxSource, /class="ds-combobox-control/)
  assert.match(breadcrumbsSource, /gestlab\.general\.navigation\.breadcrumb/)
  assert.doesNotMatch(breadcrumbsSource, /<span class="sr-only">Home<\/span>/)
  assert.match(datePickerSource, /'ds-field pl-10 pr-10'/)
  assert.doesNotMatch(datePickerSource, /<style>/)
})

test('analytics charts share product surfaces and tolerate partial API payloads', () => {
  assert.match(chartWrapperSource, /class="ds-card overflow-hidden p-3"/)
  assert.match(inventoryAnalyticsSource, /class="ds-command-surface p-5/)
  assert.match(inventoryAnalyticsSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(inventoryAnalyticsSource, /class="ds-table-shell"/)
  assert.match(inventoryAnalyticsSource, /<BaseSelect/)
  assert.match(inventoryAnalyticsSource, /<apexchart type="line"/)
  assert.match(inventoryAnalyticsSource, /<apexchart type="donut"/)
  assert.match(inventoryAnalyticsSource, /<apexchart type="bar"/)
  assert.match(inventoryAnalyticsSource, /function normalizeData/)
  assert.match(inventoryAnalyticsSource, /metrics: data\.metrics \?\? \{\}/)
  assert.match(inventoryAnalyticsSource, /return Array\.isArray\(value\) \? value : \[\]/)
  assert.match(inventoryAnalyticsSource, /fetch\(`\$\{route\('vap-inventory\.analytics\.data'\)\}/)
  assert.match(inventoryAnalyticsSource, /router\.post\(route\('vap-inventory\.analytics\.restock'/)
  assert.doesNotMatch(inventoryAnalyticsSource, /SimpleSelect|DatePickerEnhanced|ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP inventory analytics hub uses decision-oriented assurance surfaces', () => {
  assert.match(vapInventoryAnalyticsIndexSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryAnalyticsIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryAnalyticsIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryAnalyticsIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryAnalyticsIndexSource, /<InventoryAnalytics/)
  assert.match(vapInventoryAnalyticsIndexSource, /class="ds-modal-panel/)
  assert.match(vapInventoryAnalyticsIndexSource, /const summaryCards = computed/)
  assert.match(vapInventoryAnalyticsIndexSource, /const assuranceQueues = computed/)
  assert.match(vapInventoryAnalyticsIndexSource, /function openReportModal/)
  assert.match(vapInventoryAnalyticsIndexSource, /fetch\(route\('vap-inventory\.analytics\.report'/)
  assert.match(vapInventoryAnalyticsIndexSource, /response\.blob\(\)/)
  assert.doesNotMatch(vapInventoryAnalyticsIndexSource, /ConfirmationModal|ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP inventory item index uses operational stock-control surfaces', () => {
  assert.match(vapInventoryItemsIndexSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-command-toolbar mt-5/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-table-shell"/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-card p-5"/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-field pl-10"/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-button ds-button-primary"/)
  assert.match(vapInventoryItemsIndexSource, /class="ds-table-action ds-table-action-danger"/)
  assert.match(vapInventoryItemsIndexSource, /const itemRows = computed/)
  assert.match(vapInventoryItemsIndexSource, /const statsCards = computed/)
  assert.match(vapInventoryItemsIndexSource, /const quickActions = computed/)
  assert.match(vapInventoryItemsIndexSource, /window\.location\.assign/)
  assert.doesNotMatch(vapInventoryItemsIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|window\.open/)
})

test('VAP inventory item dossier uses stock and compliance surfaces', () => {
  assert.match(vapInventoryItemsShowSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryItemsShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapInventoryItemsShowSource, /class="ds-command-surface p-5"/)
  assert.match(vapInventoryItemsShowSource, /class="ds-table-shell"/)
  assert.match(vapInventoryItemsShowSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryItemsShowSource, /class="ds-button ds-button-primary/)
  assert.match(vapInventoryItemsShowSource, /class="ds-table-action ds-table-action-danger"/)
  assert.match(vapInventoryItemsShowSource, /const overviewFields = computed/)
  assert.match(vapInventoryItemsShowSource, /const identificationFields = computed/)
  assert.match(vapInventoryItemsShowSource, /const technicalSpecFields = computed/)
  assert.match(vapInventoryItemsShowSource, /const statusChipClasses =/)
  assert.match(vapInventoryItemsShowSource, /window\.location\.assign\(route\('vap-inventory\.items\.attachments\.download-single'/)
  assert.doesNotMatch(vapInventoryItemsShowSource, /commercialDocumentThemeClasses|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|window\.open/)
})

test('VAP inventory item forms share a sectioned operational workflow', () => {
  for (const source of [
    inventoryItemFormSurfaceSource,
    vapInventoryItemsCreateSource,
    vapInventoryItemsEditSource,
  ]) {
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)
    assert.doesNotMatch(source, /console\.error|console\.log/)
  }

  assert.match(inventoryItemFormSurfaceSource, /class="ds-panel overflow-hidden"/)
  assert.match(inventoryItemFormSurfaceSource, /class="ds-command-surface p-5"/)
  assert.match(inventoryItemFormSurfaceSource, /class="ds-card p-5"/)
  assert.match(inventoryItemFormSurfaceSource, /class="ds-button ds-button-primary mt-5 w-full"/)
  assert.match(inventoryItemFormSurfaceSource, /class="ds-table-action ds-table-action-danger"/)
  assert.match(inventoryItemFormSurfaceSource, /Existências por armazém/)
  assert.match(inventoryItemFormSurfaceSource, /Documentação técnica/)
  assert.match(inventoryItemFormSurfaceSource, /Calibração e metrologia/)
  assert.match(inventoryItemFormSurfaceSource, /const selectedCategory = defineModel\('selectedCategory'\)/)
  assert.match(inventoryItemFormSurfaceSource, /emit\('delete-attachment'/)
  assert.match(inventoryItemFormSurfaceSource, /:has-error="Boolean\(errorFor\('category_id'\)\)"/)
  assert.match(inventoryItemFormSurfaceSource, /:has-error="Boolean\(warehouseErrors\[index\]\?\.id\)"/)

  assert.match(vapInventoryItemsCreateSource, /<InventoryItemFormSurface/)
  assert.match(vapInventoryItemsCreateSource, /mode="create"/)
  assert.match(vapInventoryItemsCreateSource, /v-model:selected-category="selectedCategory"/)
  assert.match(vapInventoryItemsCreateSource, /form\.post\(route\('vap-inventory\.items\.store'\)/)
  assert.match(vapInventoryItemsEditSource, /<InventoryItemFormSurface/)
  assert.match(vapInventoryItemsEditSource, /mode="edit"/)
  assert.match(vapInventoryItemsEditSource, /@delete-attachment="deleteAttachment"/)
  assert.match(vapInventoryItemsEditSource, /form\.put\(route\('vap-inventory\.items\.update'/)
  assert.match(vapInventoryItemsEditSource, /const category = props\.categories\.find/)
})

test('VAP inventory item action widgets use operational modal and scanner surfaces', () => {
  for (const source of [
    adjustStockModalSource,
    transferStockModalSource,
    consumeReagentModalSource,
    recordCalibrationModalSource,
    mobileScannerSource,
    dashSummarySource,
    batchLabelGeneratorSource,
    batchSelectionSource,
  ]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(adjustStockModalSource, /route\('vap-inventory\.items\.adjust-stock'/)
  assert.match(transferStockModalSource, /route\('vap-inventory\.transfers\.store'/)
  assert.match(consumeReagentModalSource, /route\('vap-inventory\.reagents\.consume'/)
  assert.match(recordCalibrationModalSource, /route\('vap-inventory\.calibration\.record'/)
  assert.match(mobileScannerSource, /async function requestJson/)
  assert.match(mobileScannerSource, /fetch\(url/)
  assert.match(dashSummarySource, /const cards = computed/)
  assert.match(batchLabelGeneratorSource, /window\.location\.assign\(route\('printBatchLabels'/)
  assert.match(batchSelectionSource, /class="ds-card p-5"/)
})

test('VAP inventory needs index uses procurement queue surfaces', () => {
  assert.match(vapInventoryNeedsIndexSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryNeedsIndexSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapInventoryNeedsIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryNeedsIndexSource, /class="ds-card border-l-4 p-4"/)
  assert.match(vapInventoryNeedsIndexSource, /class="ds-table-shell overflow-x-auto"/)
  assert.match(vapInventoryNeedsIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryNeedsIndexSource, /<apexchart type="bar"/)
  assert.match(vapInventoryNeedsIndexSource, /<apexchart type="donut"/)
  assert.match(vapInventoryNeedsIndexSource, /const statsCards = computed/)
  assert.match(vapInventoryNeedsIndexSource, /const statusDotClass =/)
  assert.match(vapInventoryNeedsIndexSource, /const queueCardBorderClass =/)
  assert.match(vapInventoryNeedsIndexSource, /const readinessDotClass =/)
  assert.match(vapInventoryNeedsIndexSource, /router\.get\(route\('vap-inventory\.needs\.index'\)/)
  assert.doesNotMatch(vapInventoryNeedsIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|statusClass|queueCardClass|readinessClass|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)
})

test('VAP inventory needs detail uses procurement evidence surfaces', () => {
  assert.match(vapInventoryNeedsShowSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryNeedsShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapInventoryNeedsShowSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryNeedsShowSource, /class="ds-table-shell overflow-x-auto"/)
  assert.match(vapInventoryNeedsShowSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryNeedsShowSource, /<apexchart type="bar"/)
  assert.match(vapInventoryNeedsShowSource, /<apexchart type="donut"/)
  assert.match(vapInventoryNeedsShowSource, /const summaryCards = computed/)
  assert.match(vapInventoryNeedsShowSource, /const traceabilityFields = computed/)
  assert.match(vapInventoryNeedsShowSource, /const statusDotClass = computed/)
  assert.match(vapInventoryNeedsShowSource, /const supplierAssessmentPanelClass = computed/)
  assert.match(vapInventoryNeedsShowSource, /actionForm\.post\(route\('vap-inventory\.needs\.approve'/)
  assert.match(vapInventoryNeedsShowSource, /conversionForm\.post\(route\('vap-inventory\.needs\.convert-to-order'/)
  assert.doesNotMatch(vapInventoryNeedsShowSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|statusClass|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style/)
})

test('VAP inventory need intake uses a sectioned procurement form', () => {
  assert.match(vapInventoryNeedsCreateSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryNeedsCreateSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryNeedsCreateSource, /class="ds-card overflow-hidden"/)
  assert.match(vapInventoryNeedsCreateSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryNeedsCreateSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryNeedsCreateSource, /class="ds-table-action ds-table-action-danger"/)
  assert.match(vapInventoryNeedsCreateSource, /class="ds-button ds-button-primary mt-5 w-full"/)
  assert.match(vapInventoryNeedsCreateSource, /<comboboxEnhanced/)
  assert.match(vapInventoryNeedsCreateSource, /const itemOptions = computed/)
  assert.match(vapInventoryNeedsCreateSource, /const readinessSteps = computed/)
  assert.match(vapInventoryNeedsCreateSource, /const estimatedValue = computed/)
  assert.match(vapInventoryNeedsCreateSource, /function newNeedItem/)
  assert.match(vapInventoryNeedsCreateSource, /form\.transform\(\(data\) =>/)
  assert.match(vapInventoryNeedsCreateSource, /\.post\(route\('vap-inventory\.needs\.store'/)
  assert.doesNotMatch(vapInventoryNeedsCreateSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP inventory orders index uses procurement control surfaces', () => {
  assert.match(vapInventoryOrdersIndexSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryOrdersIndexSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapInventoryOrdersIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryOrdersIndexSource, /class="ds-table-shell overflow-x-auto"/)
  assert.match(vapInventoryOrdersIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryOrdersIndexSource, /const statsCards = computed/)
  assert.match(vapInventoryOrdersIndexSource, /const procurementSignals = computed/)
  assert.match(vapInventoryOrdersIndexSource, /function statusDotClass/)
  assert.match(vapInventoryOrdersIndexSource, /supplierRiskDotClass/)
  assert.match(vapInventoryOrdersIndexSource, /filters\.get\(route\('vap-inventory\.orders\.index'\)/)
  assert.doesNotMatch(vapInventoryOrdersIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style/)
})

test('VAP inventory order detail uses procurement dossier surfaces', () => {
  assert.match(vapInventoryOrdersShowSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryOrdersShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapInventoryOrdersShowSource, /class="ds-command-surface/)
  assert.match(vapInventoryOrdersShowSource, /class="ds-table-shell overflow-x-auto"/)
  assert.match(vapInventoryOrdersShowSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryOrdersShowSource, /class="ds-modal-panel/)
  assert.match(vapInventoryOrdersShowSource, /<apexchart type="bar"/)
  assert.match(vapInventoryOrdersShowSource, /<apexchart type="donut"/)
  assert.match(vapInventoryOrdersShowSource, /const summaryCards = computed/)
  assert.match(vapInventoryOrdersShowSource, /const orderDetailFields = computed/)
  assert.match(vapInventoryOrdersShowSource, /const receptionBreakdown = computed/)
  assert.match(vapInventoryOrdersShowSource, /function itemStatusDotClass/)
  assert.match(vapInventoryOrdersShowSource, /function openCancelModal/)
  assert.match(vapInventoryOrdersShowSource, /receiptError\.value/)
  assert.match(vapInventoryOrdersShowSource, /router\.post\(route\('vap-inventory\.orders\.receive'/)
  assert.match(vapInventoryOrdersShowSource, /window\.location\.assign\(route\('vap-inventory\.orders\.export-pdf'/)
  assert.doesNotMatch(vapInventoryOrdersShowSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|console\.error|console\.log|window\.open/)
})

test('VAP inventory order forms share a procurement workflow surface', () => {
  for (const source of [
    inventoryOrderFormSurfaceSource,
    vapInventoryOrdersCreateSource,
    vapInventoryOrdersEditSource,
  ]) {
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|console\.error|console\.log/)
  }

  assert.match(inventoryOrderFormSurfaceSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(inventoryOrderFormSurfaceSource, /class="ds-panel overflow-hidden"/)
  assert.match(inventoryOrderFormSurfaceSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(inventoryOrderFormSurfaceSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(inventoryOrderFormSurfaceSource, /class="ds-card p-4"/)
  assert.match(inventoryOrderFormSurfaceSource, /class="ds-field"/)
  assert.match(inventoryOrderFormSurfaceSource, /class="ds-button ds-button-primary"/)
  assert.match(inventoryOrderFormSurfaceSource, /const summaryCards = computed/)
  assert.match(inventoryOrderFormSurfaceSource, /const sidebarSummary = computed/)
  assert.match(inventoryOrderFormSurfaceSource, /const supplierAssessmentBorderClass = computed/)
  assert.match(inventoryOrderFormSurfaceSource, /function validateItems/)
  assert.match(inventoryOrderFormSurfaceSource, /form\.post\(route\('vap-inventory\.orders\.store'\)/)
  assert.match(inventoryOrderFormSurfaceSource, /form\.put\(route\('vap-inventory\.orders\.update'/)

  assert.match(vapInventoryOrdersCreateSource, /<InventoryOrderFormSurface/)
  assert.match(vapInventoryOrdersCreateSource, /mode="create"/)
  assert.match(vapInventoryOrdersEditSource, /<InventoryOrderFormSurface/)
  assert.match(vapInventoryOrdersEditSource, /mode="edit"/)
})

test('VAP inventory transfer queue uses logistics control surfaces', () => {
  assert.match(vapInventoryTransfersIndexSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryTransfersIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryTransfersIndexSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryTransfersIndexSource, /class="ds-command-toolbar mt-5/)
  assert.match(vapInventoryTransfersIndexSource, /class="ds-table-shell"/)
  assert.match(vapInventoryTransfersIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryTransfersIndexSource, /class="ds-modal-panel/)
  assert.match(vapInventoryTransfersIndexSource, /const statCards = computed/)
  assert.match(vapInventoryTransfersIndexSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryTransfersIndexSource, /function statusDotClass/)
  assert.match(vapInventoryTransfersIndexSource, /function openReceiveModal/)
  assert.match(vapInventoryTransfersIndexSource, /function openCancelModal/)
  assert.match(vapInventoryTransfersIndexSource, /receiveForm\.post\(route\('vap-inventory\.transfers\.receive'/)
  assert.match(vapInventoryTransfersIndexSource, /cancelForm\.post\(route\('vap-inventory\.transfers\.cancel'/)
  assert.doesNotMatch(vapInventoryTransfersIndexSource, /ModuleHero|ModuleCard|ConfirmationModal|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP inventory transfer form uses sectioned dispatch controls', () => {
  assert.match(vapInventoryTransfersCreateSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryTransfersCreateSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryTransfersCreateSource, /class="ds-card overflow-hidden"/)
  assert.match(vapInventoryTransfersCreateSource, /class="ds-table-shell"/)
  assert.match(vapInventoryTransfersCreateSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryTransfersCreateSource, /class="ds-field/)
  assert.match(vapInventoryTransfersCreateSource, /class="ds-button ds-button-primary mt-5 w-full"/)
  assert.match(vapInventoryTransfersCreateSource, /const readinessSteps = computed/)
  assert.match(vapInventoryTransfersCreateSource, /const warehouseStockRows = computed/)
  assert.match(vapInventoryTransfersCreateSource, /async function fetchRealTimeStock/)
  assert.match(vapInventoryTransfersCreateSource, /fetch\(route\('vap-inventory\.transfers\.item-stock-all'/)
  assert.match(vapInventoryTransfersCreateSource, /form\.transform\(\(data\) =>/)
  assert.match(vapInventoryTransfersCreateSource, /\.post\(route\('vap-inventory\.transfers\.store'/)
  assert.doesNotMatch(vapInventoryTransfersCreateSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP inventory transfer dossier uses traceability and receipt dialogs', () => {
  assert.match(vapInventoryTransfersShowSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryTransfersShowSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryTransfersShowSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryTransfersShowSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryTransfersShowSource, /class="ds-modal-panel/)
  assert.match(vapInventoryTransfersShowSource, /<apexchart type="bar"/)
  assert.match(vapInventoryTransfersShowSource, /<apexchart type="donut"/)
  assert.match(vapInventoryTransfersShowSource, /const summaryCards = computed/)
  assert.match(vapInventoryTransfersShowSource, /const detailFields = computed/)
  assert.match(vapInventoryTransfersShowSource, /const workflowSteps = computed/)
  assert.match(vapInventoryTransfersShowSource, /function openReceiveModal/)
  assert.match(vapInventoryTransfersShowSource, /function openCancelModal/)
  assert.match(vapInventoryTransfersShowSource, /receiveForm\.transform\(\(data\) =>/)
  assert.match(vapInventoryTransfersShowSource, /cancelForm\.post\(route\('vap-inventory\.transfers\.cancel'/)
  assert.doesNotMatch(vapInventoryTransfersShowSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP low-stock report uses replenishment assurance surfaces', () => {
  assert.match(vapInventoryLowStockReportSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryLowStockReportSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryLowStockReportSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryLowStockReportSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryLowStockReportSource, /class="ds-table-shell"/)
  assert.match(vapInventoryLowStockReportSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryLowStockReportSource, /class="ds-button ds-button-primary/)
  assert.match(vapInventoryLowStockReportSource, /<apexchart type="bar"/)
  assert.match(vapInventoryLowStockReportSource, /<apexchart type="donut"/)
  assert.match(vapInventoryLowStockReportSource, /const summaryCards = computed/)
  assert.match(vapInventoryLowStockReportSource, /const recommendedOrders = computed/)
  assert.match(vapInventoryLowStockReportSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryLowStockReportSource, /function statusDotClass/)
  assert.match(vapInventoryLowStockReportSource, /function stockPercentage/)
  assert.match(vapInventoryLowStockReportSource, /router\.get\(route\('vap-inventory\.reports\.low-stock'/)
  assert.match(vapInventoryLowStockReportSource, /router\.visit\(route\('vap-inventory\.orders\.create'/)
  assert.match(vapInventoryLowStockReportSource, /router\.post\(route\('vap-inventory\.reports\.export'/)
  assert.doesNotMatch(vapInventoryLowStockReportSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
})

test('VAP inventory-value report uses reconciliation and financial-control surfaces', () => {
  assert.match(vapInventoryValueReportSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryValueReportSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryValueReportSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryValueReportSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryValueReportSource, /class="ds-table-shell"/)
  assert.match(vapInventoryValueReportSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryValueReportSource, /<apexchart type="bar"/)
  assert.match(vapInventoryValueReportSource, /<apexchart type="donut"/)
  assert.match(vapInventoryValueReportSource, /const summaryCards = computed/)
  assert.match(vapInventoryValueReportSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryValueReportSource, /const valuationUnitPrice = 100/)
  assert.match(vapInventoryValueReportSource, /sort_by: 'qty_available'/)
  assert.match(vapInventoryValueReportSource, /router\.get\(route\('vap-inventory\.reports\.inventory-value'/)
  assert.match(vapInventoryValueReportSource, /router\.post\(route\('vap-inventory\.reports\.export'/)
  assert.doesNotMatch(vapInventoryValueReportSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP consumption report uses reagent-stewardship and traceability surfaces', () => {
  assert.match(vapInventoryConsumptionReportSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryConsumptionReportSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryConsumptionReportSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryConsumptionReportSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryConsumptionReportSource, /class="ds-table-shell"/)
  assert.match(vapInventoryConsumptionReportSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryConsumptionReportSource, /<ComboboxEnhanced/)
  assert.match(vapInventoryConsumptionReportSource, /<apexchart type="bar"/)
  assert.match(vapInventoryConsumptionReportSource, /<apexchart type="donut"/)
  assert.match(vapInventoryConsumptionReportSource, /<apexchart type="line"/)
  assert.match(vapInventoryConsumptionReportSource, /const summaryCards = computed/)
  assert.match(vapInventoryConsumptionReportSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryConsumptionReportSource, /function selectItem/)
  assert.match(vapInventoryConsumptionReportSource, /router\.get\(route\('vap-inventory\.reports\.consumption'/)
  assert.match(vapInventoryConsumptionReportSource, /router\.post\(route\('vap-inventory\.reports\.export'/)
  assert.doesNotMatch(vapInventoryConsumptionReportSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP stock-movement report uses audit-trail and reconciliation surfaces', () => {
  assert.match(vapInventoryStockMovementReportSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryStockMovementReportSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryStockMovementReportSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryStockMovementReportSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryStockMovementReportSource, /class="ds-table-shell"/)
  assert.match(vapInventoryStockMovementReportSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryStockMovementReportSource, /<ComboboxEnhanced/)
  assert.match(vapInventoryStockMovementReportSource, /<apexchart type="bar"/)
  assert.match(vapInventoryStockMovementReportSource, /<apexchart type="donut"/)
  assert.match(vapInventoryStockMovementReportSource, /<apexchart type="line"/)
  assert.match(vapInventoryStockMovementReportSource, /const summaryCards = computed/)
  assert.match(vapInventoryStockMovementReportSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryStockMovementReportSource, /function setView/)
  assert.match(vapInventoryStockMovementReportSource, /function quantityLabel/)
  assert.match(vapInventoryStockMovementReportSource, /router\.get\(route\('vap-inventory\.reports\.stock-movement'/)
  assert.match(vapInventoryStockMovementReportSource, /router\.post\(route\('vap-inventory\.reports\.export'/)
  assert.doesNotMatch(vapInventoryStockMovementReportSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP reagent expiry report uses FEFO and quarantine-control surfaces', () => {
  assert.match(vapInventoryExpiryReportSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryExpiryReportSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryExpiryReportSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryExpiryReportSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryExpiryReportSource, /class="ds-table-shell"/)
  assert.match(vapInventoryExpiryReportSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryExpiryReportSource, /const summaryCards = computed/)
  assert.match(vapInventoryExpiryReportSource, /const expiryWindows = computed/)
  assert.match(vapInventoryExpiryReportSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryExpiryReportSource, /function daysToExpiry/)
  assert.match(vapInventoryExpiryReportSource, /function shelfLifePercentage/)
  assert.match(vapInventoryExpiryReportSource, /router\.get\(route\('vap-inventory\.items\.reagents\.expiry'/)
  assert.match(vapInventoryExpiryReportSource, /fetch\(route\('vap-inventory\.analytics\.report'/)
  assert.doesNotMatch(vapInventoryExpiryReportSource, /markAllExpired|markDisposed|sendExpiryAlerts|ConfirmationModal|ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP calibration schedule uses metrology assurance surfaces', () => {
  assert.match(vapInventoryCalibrationScheduleSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryCalibrationScheduleSource, /class="ds-panel overflow-hidden/)
  assert.match(vapInventoryCalibrationScheduleSource, /class="ds-command-surface p-5/)
  assert.match(vapInventoryCalibrationScheduleSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryCalibrationScheduleSource, /class="ds-table-shell"/)
  assert.match(vapInventoryCalibrationScheduleSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryCalibrationScheduleSource, /const summaryCards = computed/)
  assert.match(vapInventoryCalibrationScheduleSource, /const calibrationWindows = computed/)
  assert.match(vapInventoryCalibrationScheduleSource, /const activeFilterPills = computed/)
  assert.match(vapInventoryCalibrationScheduleSource, /function daysToCalibration/)
  assert.match(vapInventoryCalibrationScheduleSource, /function calibrationUtilization/)
  assert.match(vapInventoryCalibrationScheduleSource, /route\('vap-inventory\.items\.calibration\.schedule'/)
  assert.match(vapInventoryCalibrationScheduleSource, /route\('vap-maintenance\.tasks\.create'/)
  assert.doesNotMatch(vapInventoryCalibrationScheduleSource, /scheduleCalibrations|recordCalibration|rescheduleCalibration|sendCalibrationAlerts|generateCalibrationReport|prompt\(|window\.open|alert\(|ConfirmationModal|ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|axios|console\.error|console\.log/)
})

test('VAP inventory reagent consumption uses audit-ready operating surfaces', () => {
  assert.match(vapInventoryReagentConsumptionSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-card p-5"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-table-shell overflow-x-auto"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-button ds-button-primary"/)
  assert.match(vapInventoryReagentConsumptionSource, /class="ds-table-action ds-table-action-danger"/)
  assert.match(vapInventoryReagentConsumptionSource, /const summaryCards = computed/)
  assert.match(vapInventoryReagentConsumptionSource, /const activeFilterCount = computed/)
  assert.match(vapInventoryReagentConsumptionSource, /filters\.get\(route\('vap-inventory\.reagents\.consumption\.index'\)/)
  assert.match(vapInventoryReagentConsumptionSource, /router\.post\(route\('vap-inventory\.reports\.export'\)/)
  assert.match(vapInventoryReagentConsumptionSource, /router\.delete\(route\('vap-inventory\.reagents\.consumption\.destroy'/)
  assert.match(vapInventoryReagentConsumptionSource, /<confirm-dialog/)
  assert.doesNotMatch(vapInventoryReagentConsumptionSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('VAP inventory reagent consumption form and detail share the audit workflow language', () => {
  for (const source of [
    vapInventoryReagentConsumptionCreateSource,
    vapInventoryReagentConsumptionShowSource,
  ]) {
    assert.match(source, /class="min-w-0 space-y-6 overflow-x-clip"/)
    assert.match(source, /class="ds-panel overflow-hidden"/)
    assert.match(source, /class="ds-command-surface/)
    assert.match(source, /class="ds-card/)
    assert.match(source, /<confirm-dialog/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(vapInventoryReagentConsumptionCreateSource, /const workspaceMetrics = computed/)
  assert.match(vapInventoryReagentConsumptionCreateSource, /const stockReview = computed/)
  assert.match(vapInventoryReagentConsumptionCreateSource, /const reagentFields = computed/)
  assert.match(vapInventoryReagentConsumptionCreateSource, /form\.post\(route\('vap-inventory\.reagents\.consumption\.store'\)/)
  assert.match(vapInventoryReagentConsumptionCreateSource, /router\.visit\(route\('vap-inventory\.reagents\.consumption\.index'\)/)

  assert.match(vapInventoryReagentConsumptionShowSource, /const summaryCards = computed/)
  assert.match(vapInventoryReagentConsumptionShowSource, /const primaryFields = computed/)
  assert.match(vapInventoryReagentConsumptionShowSource, /const timelineItems = computed/)
  assert.match(vapInventoryReagentConsumptionShowSource, /const stockImpactCards = computed/)
  assert.match(vapInventoryReagentConsumptionShowSource, /class="ds-button ds-button-danger"/)
  assert.match(vapInventoryReagentConsumptionShowSource, /router\.delete\(route\('vap-inventory\.reagents\.consumption\.destroy'/)
})

test('VAP nonconformity CAPA workflow uses quality dossier surfaces', () => {
  const capaWorkflowSources = [
    vapNonConformitiesIndexSource,
    vapNonConformitiesCreateSource,
    vapNonConformitiesEditSource,
    vapNonConformitiesShowSource,
    vapNonConformityFormSource,
  ]

  for (const source of capaWorkflowSources) {
    assert.match(source, /class="min-w-0 space-y-6 overflow-x-clip"/)
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(vapNonConformitiesIndexSource, /class="ds-panel overflow-hidden p-5/)
  assert.match(vapNonConformitiesIndexSource, /class="ds-command-surface p-5/)
  assert.match(vapNonConformitiesIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapNonConformitiesIndexSource, /class="ds-table-shell"/)
  assert.match(vapNonConformitiesIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapNonConformitiesIndexSource, /<ChartWrapper/)
  assert.match(vapNonConformitiesIndexSource, /<confirm-dialog/)
  assert.match(vapNonConformitiesIndexSource, /const summaryCards = computed/)
  assert.match(vapNonConformitiesIndexSource, /const activeFilterPills = computed/)
  assert.match(vapNonConformitiesIndexSource, /const workspaceView = ref\('register'\)/)
  assert.match(vapNonConformitiesIndexSource, /v-show="workspaceView === 'analytics'"/)
  assert.match(vapNonConformitiesIndexSource, /router\.get\(route\('vap_non_conformities\.index'/)
  assert.match(vapNonConformitiesIndexSource, /router\.delete\(route\('vap_non_conformities\.destroy'/)
  assert.match(vapNonConformitiesIndexSource, /window\.location\.assign/)

  assert.match(vapNonConformitiesCreateSource, /<NonConformityForm/)
  assert.match(vapNonConformitiesCreateSource, /:is-editing="false"/)
  assert.match(vapNonConformitiesEditSource, /<NonConformityForm/)
  assert.match(vapNonConformitiesEditSource, /:is-editing="true"/)

  assert.match(vapNonConformityFormSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapNonConformityFormSource, /const workflowSections = \[/)
  assert.match(vapNonConformityFormSource, /aria-label="Etapas do dossier CAPA"/)
  assert.match(vapNonConformityFormSource, /v-show="activeWorkflowSection === 'evidence'"/)
  assert.match(vapNonConformityFormSource, /function focusFirstErrorSection/)
  assert.match(vapNonConformityFormSource, /class="ds-card overflow-hidden"/)
  assert.match(vapNonConformityFormSource, /class="ds-field"/)
  assert.match(vapNonConformityFormSource, /class="ds-button ds-button-primary"/)
  assert.match(vapNonConformityFormSource, /const workflowMetrics = computed/)
  assert.match(vapNonConformityFormSource, /function cloneInitialActions/)
  assert.match(vapNonConformityFormSource, /form\.post\(route\('vap_non_conformities\.store'/)
  assert.match(vapNonConformityFormSource, /route\('vap_non_conformities\.update'/)

  assert.match(vapNonConformitiesShowSource, /class="ds-table-shell"/)
  assert.match(vapNonConformitiesShowSource, /class="ds-command-surface p-5"/)
  assert.match(vapNonConformitiesShowSource, /<confirm-dialog/)
  assert.match(vapNonConformitiesShowSource, /const summaryCards = computed/)
  assert.match(vapNonConformitiesShowSource, /const additionalNarratives = computed/)
  assert.match(vapNonConformitiesShowSource, /const dossierSections = \[/)
  assert.match(vapNonConformitiesShowSource, /aria-label="Secções do dossier CAPA"/)
  assert.match(vapNonConformitiesShowSource, /route\('vap_non_conformities\.export\.details\.pdf'/)
  assert.match(vapNonConformitiesShowSource, /router\.delete\(route\('vap_non_conformities\.destroy'/)
})

test('operational settings and backup monitoring use semantic product surfaces', () => {
  assert.match(systemSettingsSource, /class="ds-panel overflow-hidden"/)
  assert.match(systemSettingsSource, /class="ds-settings-tab group/)
  assert.match(systemSettingsSource, /ds-settings-tab-active/)
  assert.match(systemSettingsSource, /class="ds-command-surface sticky bottom-4/)
  assert.match(systemSettingsSource, /<SettingsField/)
  assert.match(systemSettingsSource, /form\.post\(route\('generalsettings\.update'\)/)
  assert.match(systemSettingsSource, /Assinatura e validação documental/)
  assert.match(systemSettingsSource, /Emails e notificações geridos/)
  assert.doesNotMatch(systemSettingsSource, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)

  assert.match(settingsFieldSource, /class="ds-field"/)
  assert.match(settingsFieldSource, /class="ds-field-error"/)
  assert.match(settingsFieldSource, /aria-invalid="Boolean\(error\)"/)
  assert.doesNotMatch(settingsFieldSource, /rounded-3xl|rounded-2xl|bg-slate|border-slate|text-slate|shadow-sm/)

  assert.match(labelPrintSettingsSource, /class="ds-panel/)
  assert.match(labelPrintSettingsSource, /class="ds-checkbox"/)
  assert.match(labelPrintSettingsSource, /class="ds-field"/)

  assert.match(backupStatusesSource, /class="ds-table-shell"/)
  assert.match(backupStatusesSource, /class="ds-table-summary/)
  assert.match(backupStatusesSource, /onBeforeUnmount/)
  assert.match(backupStatusesSource, /window\.clearInterval\(timeInterval\)/)
  assert.doesNotMatch(backupStatusesSource, /onUnmounted/)
  assert.doesNotMatch(backupStatusesSource, /from-primary-950/)

  assert.match(backupsSource, /class="ds-table-shell"/)
  assert.match(backupsSource, /class="ds-field min-w-52"/)
  assert.doesNotMatch(backupsSource, /bg-gradient-to-r/)
  assert.match(backupRowSource, /class="ds-table-row"/)
  assert.match(backupRowSource, /gestlab\.general\.buttons\.download/)
  assert.match(backupRowSource, /gestlab\.general\.buttons\.delete/)

  assert.match(backupsPageSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(backupsPageSource, /v-model:active-disk="activeDisk"/)
  assert.match(backupsPageSource, /onBeforeUnmount/)
  assert.match(backupsPageSource, /window\.setInterval\(refreshBackupData, 30 \* 1000\)/)
  assert.match(backupsPageSource, /Array\.isArray\(data\)/)
  assert.match(backupsPageSource, /fetch\(url/)
  assert.match(backupsPageSource, /createPartialBackup\('only-db'\)/)
  assert.match(backupsPageSource, /createPartialBackup\('only-files'\)/)
  assert.doesNotMatch(backupsPageSource, /ModuleHero|commercialDocumentThemeClasses|setModalVisibility|axios|<svg|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-white|bg-slate|border-slate|text-slate|shadow-sm|console\.log|alert\(|confirm\(/)
})

test('VAP label index uses shared product components and translated copy', () => {
  assert.match(vapLabelsIndexSource, /<ModuleHero/)
  assert.match(vapLabelsIndexSource, /<BaseInput/)
  assert.match(vapLabelsIndexSource, /<BaseSelect/)
  assert.match(vapLabelsIndexSource, /class="ds-command-surface/)
  assert.match(vapLabelsIndexSource, /class="ds-table-shell"/)
  assert.match(vapLabelsIndexSource, /<confirm-dialog/)
  assert.match(vapLabelsIndexSource, /vap_labels\.index\.filters_title/)
  assert.match(vapLabelsIndexSource, /route\('vap_labels\.label-templates\.index'\)/)
  assert.doesNotMatch(vapLabelsIndexSource, /visibleGalleryLabels\.length/)
  assert.doesNotMatch(vapLabelsIndexSource, /confirm\(/)
  assert.doesNotMatch(vapLabelsIndexSource, /border-\[#ded3bf\]|bg-\[#fffdf7\]|text-\[#15231f\]/)
})

test('VAP label editor uses shared form primitives and semantic surfaces', () => {
  assert.match(vapLabelsCreateSource, /<ModuleHero/)
  assert.match(vapLabelsCreateSource, /<BaseInput/)
  assert.match(vapLabelsCreateSource, /<BaseSelect/)
  assert.match(vapLabelsCreateSource, /<BaseTextarea/)
  assert.match(vapLabelsCreateSource, /class="ds-panel/)
  assert.match(vapLabelsCreateSource, /vap_labels\.editor\.traceability_title/)
  assert.match(vapLabelsCreateSource, /const previewStyle = computed/)
  assert.doesNotMatch(vapLabelsCreateSource, /<style scoped>/)
  assert.doesNotMatch(vapLabelsCreateSource, /border-\[#ded3bf\]|bg-\[#fffdf7\]|text-\[#15231f\]/)
})

test('VAP label show and print workflow use semantic surfaces and modal confirmations', () => {
  assert.match(vapLabelsShowSource, /<ModuleHero/)
  assert.match(vapLabelsShowSource, /<BaseInput/)
  assert.match(vapLabelsShowSource, /<BaseTextarea/)
  assert.match(vapLabelsShowSource, /<LabelPrintSettings/)
  assert.match(vapLabelsShowSource, /<confirm-dialog/)
  assert.match(vapLabelsShowSource, /class="ds-panel/)
  assert.match(vapLabelsShowSource, /vap_labels\.show\.print_title/)
  assert.match(vapLabelsShowSource, /const openPdfResponse = async/)
  assert.doesNotMatch(vapLabelsShowSource, /confirm\(/)
  assert.doesNotMatch(vapLabelsShowSource, /<style scoped>/)
  assert.doesNotMatch(vapLabelsShowSource, /border-\[#ded3bf\]|bg-\[#fffdf7\]|text-\[#15231f\]/)
})

test('VAP label template index uses shared surfaces and safe destructive actions', () => {
  assert.match(vapLabelTemplatesIndexSource, /<ModuleHero/)
  assert.match(vapLabelTemplatesIndexSource, /<BaseInput/)
  assert.match(vapLabelTemplatesIndexSource, /<BaseSelect/)
  assert.match(vapLabelTemplatesIndexSource, /<confirm-dialog/)
  assert.match(vapLabelTemplatesIndexSource, /class="ds-command-surface/)
  assert.match(vapLabelTemplatesIndexSource, /class="ds-table-shell"/)
  assert.match(vapLabelTemplatesIndexSource, /vap_labels\.templates\.usage_note/)
  assert.match(vapLabelTemplatesIndexSource, /router\.delete/)
  assert.doesNotMatch(vapLabelTemplatesIndexSource, /confirm\(/)
  assert.doesNotMatch(vapLabelTemplatesIndexSource, /<style scoped>/)
  assert.doesNotMatch(vapLabelTemplatesIndexSource, /border-\[#ded3bf\]|bg-\[#fffdf7\]|text-\[#15231f\]|bg-gradient-to-r/)

  assert.match(vapLabelTemplateFormSource, /const sections = \[/)
  assert.match(vapLabelTemplateFormSource, /aria-label="Etapas do modelo de etiqueta"/)
  assert.match(vapLabelTemplateFormSource, /<BaseInput/)
  assert.match(vapLabelTemplateFormSource, /<BaseSelect/)
  assert.match(vapLabelTemplateFormSource, /<BaseTextarea/)
  assert.match(vapLabelTemplateFormSource, /form\.post\(route\('vap_labels\.label-templates\.store'\)/)
  assert.match(vapLabelTemplateFormSource, /form\.put\(route\('vap_labels\.label-templates\.update'/)
  assert.doesNotMatch(vapLabelTemplateFormSource, /commercialDocumentThemeClasses|bg-gradient-to|confirm\(|alert\(/)
})

test('VAP proposal template library uses shared studio surfaces', () => {
  assert.match(vapProposalTemplatesIndexSource, /<ModuleHero/)
  assert.match(vapProposalTemplatesIndexSource, /<BaseInput/)
  assert.match(vapProposalTemplatesIndexSource, /<BaseSelect/)
  assert.match(vapProposalTemplatesIndexSource, /<confirm-dialog/)
  assert.match(vapProposalTemplatesIndexSource, /<Modal/)
  assert.match(vapProposalTemplatesIndexSource, /class="ds-command-surface/)
  assert.match(vapProposalTemplatesIndexSource, /class="ds-table-shell"/)
  assert.match(vapProposalTemplatesIndexSource, /vap_proposal_templates\.list\.summary/)
  assert.match(vapProposalTemplatesIndexSource, /return trans\(translationKeys\[category\] \|\| translationKeys\.general\)/)
  assert.match(vapProposalTemplatesIndexSource, /router\.delete/)
  assert.doesNotMatch(vapProposalTemplatesIndexSource, /<style scoped>/)
  assert.doesNotMatch(vapProposalTemplatesIndexSource, /v-motion|\$refs|ConfirmationModal/)
  assert.doesNotMatch(vapProposalTemplatesIndexSource, /commercialDocumentThemeClasses|categories\.\$\{key\}|border-\[#|bg-\[#|text-\[#|bg-gradient-to-r/)
})

test('VAP proposal workspace uses the operational LIMS design system', () => {
  assert.match(vapProposalsIndexSource, /class="ds-command-surface/)
  assert.match(vapProposalsIndexSource, /class="ds-table-shell/)
  assert.match(vapProposalsIndexSource, /class="ds-data-table/)
  assert.match(vapProposalsShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapProposalsShowSource, /class="ds-table-shell"/)
  assert.match(vapProposalsShowSource, /laboratoryDossier/)

  for (const source of [vapProposalsIndexSource, vapProposalsCreateSource, vapProposalsEditSource, vapProposalsShowSource]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses/)
  }

  for (const source of [vapProposalsIndexSource, vapProposalsShowSource]) {
    assert.doesNotMatch(source, /#143d37|#c79a43|#ded2bb|#fbfaf6|#f7f1e6|bg-\[radial-gradient|rounded-\[(?:2|3)\dpx\]/)
  }
})

test('VAP proposal template routes keep application chrome on semantic surfaces', () => {
  assert.match(vapProposalTemplatesShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapProposalTemplatesShowSource, /gestlab\.general\.buttons\.copied/)
  assert.match(proposalTemplateStudioSource, /class="ds-panel overflow-hidden"/)
  assert.match(proposalTemplateStudioSource, /class="ds-button ds-button-primary"/)

  for (const source of [vapProposalTemplatesIndexSource, vapProposalTemplatesCreateSource, vapProposalTemplatesEditSource, vapProposalTemplatesShowSource]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses/)
  }

  assert.doesNotMatch(vapProposalTemplatesShowSource, /#143d37|#c79a43|#ded2bb|#fbfaf6|#f7f1e6|bg-\[radial-gradient|rounded-\[(?:2|3)\dpx\]/)
  assert.doesNotMatch(proposalTemplateStudioSource, /class="overflow-hidden rounded-\[2rem\]|class="rounded-\[30px\] border border-\[#ded2bb\]/)
})

test('VAP proposal create screen uses semantic commercial form surfaces', () => {
  assert.match(vapProposalsCreateSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapProposalsCreateSource, /class="ds-card bg-\[var\(--ds-panel-raised\)\] p-4"/)
  assert.match(vapProposalsCreateSource, /class="ds-field"/)
  assert.match(vapProposalsCreateSource, /class="ds-checkbox"/)
  assert.match(vapProposalsCreateSource, /class="ds-table-action/)
  assert.match(vapProposalsCreateSource, /vap_proposals\.form\.save_error/)
  assert.match(vapProposalsCreateSource, /import \{ trans \} from 'laravel-vue-i18n'/)
  assert.match(vapProposalsCreateSource, /const proposalOptionFromApi =/)
  assert.match(vapProposalsCreateSource, /results\.map\(result => proposalOptionFromApi\(result, 'Armazém'\)\)/)
  assert.match(vapProposalsCreateSource, /\.\.\.proposalOptionFromApi\(result, 'Matriz'\)/)
  assert.match(vapProposalsCreateSource, /\.\.\.proposalOptionFromApi\(result, 'Parâmetro'\)/)
  assert.doesNotMatch(vapProposalsCreateSource, /proposal-editor-shell|<style scoped>|v-motion/)
  assert.doesNotMatch(vapProposalsCreateSource, /border-\[#|bg-\[#|text-\[#|focus:ring-\[#|focus:border-\[#|bg-gradient-to-r/)
  assert.doesNotMatch(vapProposalsCreateSource, /Resposta inválida|Não foi possível|Cópia|Clique novamente|Preencha os campos/)
})

test('VAP proposal edit screen keeps revision controls on semantic surfaces', () => {
  assert.match(vapProposalsEditSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapProposalsEditSource, /class="ds-card bg-\[var\(--ds-panel-raised\)\] p-4"/)
  assert.match(vapProposalsEditSource, /class="ds-field"/)
  assert.match(vapProposalsEditSource, /class="ds-checkbox"/)
  assert.match(vapProposalsEditSource, /class="ds-table-action/)
  assert.match(vapProposalsEditSource, /vap_proposals\.form\.update_error/)
  assert.match(vapProposalsEditSource, /import \{ trans \} from 'laravel-vue-i18n'/)
  assert.match(vapProposalsEditSource, /const proposalOptionFromApi =/)
  assert.match(vapProposalsEditSource, /\.\.\.proposalOptionFromApi\(result, 'Matriz'\)/)
  assert.match(vapProposalsEditSource, /\.\.\.proposalOptionFromApi\(result, 'Parâmetro'\)/)
  assert.doesNotMatch(vapProposalsEditSource, /proposal-editor-shell|<style scoped>|v-motion/)
  assert.doesNotMatch(vapProposalsEditSource, /border-\[#|bg-\[#|text-\[#|focus:ring-\[#|focus:border-\[#|bg-gradient-to-r/)
  assert.doesNotMatch(vapProposalsEditSource, /Resposta inválida|Não foi possível|Cópia|Clique novamente|A proposta deve|Preencha os campos/)
})

test('commercial document indexes use shared hero surfaces and localized copy', () => {
  const commercialIndexes = [
    invoicesIndexSource,
    quotesIndexSource,
    creditNotesIndexSource,
    receiptsIndexSource,
  ]

  for (const source of commercialIndexes) {
    assert.match(source, /<ModuleHero/)
    assert.match(source, /class="ds-card bg-\[var\(--ds-panel-raised\)\] p-4"/)
    assert.match(source, /commercial_documents\.records/)
    assert.match(source, /commercial_documents\.flow/)
    assert.doesNotMatch(source, /bg-\[radial-gradient|bg-gradient-to-r|border-slate|bg-slate|text-slate/)
    assert.doesNotMatch(source, />Comercial<|>Tesouraria<|>Registos<|>Fluxo<|>Facturação<|>Propostas<|>Crédito<|>Cobrança</)
    assert.doesNotMatch(source, /<br>/)
  }

  assert.match(invoicesIndexSource, /combobox-enhanced/)
  assert.match(invoicesIndexSource, /class="ds-field-label"/)
  assert.match(invoicesIndexSource, /class="ds-table-action"/)
  assert.match(invoicesIndexSource, /const invoiceFilterOptions =/)
  assert.match(invoicesIndexSource, /id: 'unpaid'/)
  assert.match(invoicesIndexSource, /id: 'paid'/)
  assert.match(invoicesIndexSource, /:filter-options="invoiceFilterOptions"/)
  assert.match(recordsTableSource, /filterOptions:/)
  assert.match(recordsTableSource, /:model-value="query\.filter"/)
  assert.match(quotesIndexSource, /class="ds-table-action"/)
})

test('commercial document create screens use the compact LIMS form language', () => {
  const commercialCreateScreens = [
    invoicesCreateSource,
    quotesCreateSource,
    creditNotesCreateSource,
    receiptsCreateSource,
  ]

  for (const source of commercialCreateScreens) {
    assert.match(source, /commercial-document-create min-w-0 space-y-5 overflow-x-clip/)
    assert.match(source, /class="commercial-document-header/)
    assert.match(source, /class="ds-panel commercial-document-section/)
    assert.match(source, /class="commercial-document-command/)
    assert.match(source, /class="ds-field/)
    assert.match(source, /class="ds-button ds-button-primary/)
    assert.match(source, /useCommercialDocumentOptions/)
    assert.doesNotMatch(source, /results\.map/)
    assert.doesNotMatch(source, /bg-white rounded-xl shadow-sm|rounded-2xl|space-y-8/)
  }

  assert.match(commercialDocumentSurfaceSource, /background: var\(--ds-panel-raised\)/)
  assert.match(commercialDocumentSurfaceSource, /border-radius: 0\.5rem/)
  assert.match(commercialDocumentSurfaceSource, /border-radius: 0\.375rem/)
  assert.doesNotMatch(commercialDocumentSurfaceSource, /#fffdf7|#ded3bf|0 18px 55px|border-radius: 1rem/)
  assert.match(commercialDocumentOptionsSource, /Array\.isArray\(payload\?\.data\)/)
  assert.match(commercialDocumentOptionsSource, /Array\.isArray\(payload\?\.items\)/)
})

test('commercial document edit screens avoid legacy motion and native field styling', () => {
  const commercialEditScreens = [
    invoicesEditSource,
    quotesEditSource,
    creditNotesEditSource,
    receiptsEditSource,
  ]

  for (const source of commercialEditScreens) {
    assert.match(source, /class="ds-field/)
    assert.match(source, /class="ds-field-label"/)
    assert.match(source, /class="ds-button ds-button-primary/)
    assert.match(source, /class="ds-table-action/)
    assert.doesNotMatch(source, /v-motion|focus:ring-ft-orange|focus:border-ft-orange|focus:border-indigo|focus:ring-orange|focus:ring-blue/)
    assert.doesNotMatch(source, /Registrar Item|>Registrar<|observações sobre o artigo|Itens associados à factura:/)
    assert.doesNotMatch(source, /class="block w-full rounded-md border-0 py-1\.5|class="block w-full border-0/)
  }
})

test('quality certificate release workflow uses controlled dossier surfaces', () => {
  const certificateWorkflowSources = [
    qualityCertificatesIndexSource,
    qualityCertificatesShowSource,
    qualityCertificatesEditSource,
    validationModalSource,
  ]

  for (const source of certificateWorkflowSources) {
    assert.match(source, /min-w-0 space-y-/)
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(qualityCertificatesIndexSource, /class="ds-panel overflow-hidden"/)
  assert.match(qualityCertificatesIndexSource, />Certificados de qualidade</)
  assert.match(qualityCertificatesIndexSource, /<RecordsTable/)
  assert.match(qualityCertificatesIndexSource, /<slide-over/)
  assert.match(qualityCertificatesIndexSource, /<confirm-dialog/)
  assert.match(qualityCertificatesIndexSource, /const registryMetrics = computed/)
  assert.match(qualityCertificatesIndexSource, /routeName = selectedActionId === "restore"/)
  assert.match(qualityCertificatesIndexSource, /router\.get\(/)

  assert.match(qualityCertificatesShowSource, /const certificateMetrics = computed/)
  assert.match(qualityCertificatesShowSource, /const laboratoryReference = computed/)
  assert.match(qualityCertificatesShowSource, /const certificateDetails = computed/)
  assert.match(qualityCertificatesShowSource, /const releaseChecks = computed/)
  assert.match(qualityCertificatesShowSource, /const activityHistory = computed/)
  assert.match(qualityCertificatesShowSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(qualityCertificatesShowSource, /:href="pdfUrl"/)
  assert.match(qualityCertificatesShowSource, /route\("qualitycertificates\.getApprove"/)
  assert.match(qualityCertificatesShowSource, /route\("qualitycertificates\.iso-revisions\.index"/)

  assert.match(qualityCertificatesEditSource, /const editMetrics = computed/)
  assert.match(qualityCertificatesEditSource, /Registo laboratorial/)
  assert.match(qualityCertificatesEditSource, /const releaseContext = computed/)
  assert.match(qualityCertificatesEditSource, /role="switch"/)
  assert.match(qualityCertificatesEditSource, /class="ds-field"/)
  assert.match(qualityCertificatesEditSource, /form\.put\(/)

  assert.match(validationModalSource, /class="ds-command-surface p-5"/)
  assert.match(validationModalSource, /class="ds-panel overflow-hidden"/)
  assert.match(validationModalSource, /role="switch"/)
  assert.match(validationModalSource, /class="ds-button ds-button-primary"/)
})

test('quality certificate ISO revision workflow uses controlled audit surfaces', () => {
  const revisionPageSources = [
    isoRevisionIndexSource,
    isoRevisionCreateSource,
    isoRevisionShowSource,
    isoRevisionCompareSource,
    isoRevisionAuditTrailSource,
  ]
  const revisionWorkflowSources = [
    ...revisionPageSources,
    isoRevisionCreateModalSource,
    isoRevisionComparisonModalSource,
    isoRevisionRestoreModalSource,
  ]

  for (const source of revisionPageSources) {
    assert.match(source, /min-w-0 space-y-6 overflow-x-clip/)
  }

  for (const source of revisionWorkflowSources) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.doesNotMatch(isoRevisionManagerSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|v-motion|<style/)

  assert.match(isoRevisionIndexSource, /const activeRevision = computed/)
  assert.match(isoRevisionIndexSource, /const revisionMetrics = computed/)
  assert.match(isoRevisionIndexSource, /RestoreRevisionModal/)
  assert.match(isoRevisionIndexSource, /qualitycertificates\.iso-revisions\.compare-two/)

  assert.match(isoRevisionCreateSource, /const fieldDefinitions = computed/)
  assert.match(isoRevisionCreateSource, /const workflowSteps = computed/)
  assert.match(isoRevisionCreateSource, /qualitycertificates\.iso-revisions\.store/)

  assert.match(isoRevisionShowSource, /const snapshotTabs = computed/)
  assert.match(isoRevisionShowSource, /const activeObjectEntries = computed/)
  assert.match(isoRevisionShowSource, /RestoreRevisionModal/)

  assert.match(isoRevisionCompareSource, /const differenceGroups = computed/)
  assert.match(isoRevisionCompareSource, /const flattenedDifferences = computed/)
  assert.match(isoRevisionCompareSource, /iso-revisions\.export-comparison/)

  assert.match(isoRevisionAuditTrailSource, /const filteredLogs = computed/)
  assert.match(isoRevisionAuditTrailSource, /const auditMetrics = computed/)
  assert.match(isoRevisionAuditTrailSource, /<Pagination :links="logs\.links"/)

  assert.match(isoRevisionManagerSource, /import RevisionIndex from "\.\/Index\.vue"/)
  assert.match(isoRevisionManagerSource, /<RevisionIndex/)
  assert.match(isoRevisionCreateModalSource, /qualitycertificates\.iso-revisions\.store/)
  assert.match(isoRevisionComparisonModalSource, /router\.visit/)
  assert.match(isoRevisionComparisonModalSource, /qualitycertificates\.iso-revisions\.compare-two/)
  assert.match(isoRevisionRestoreModalSource, /const restorableFields/)
  assert.match(isoRevisionRestoreModalSource, /value="CORRECTION"/)
  assert.match(isoRevisionRestoreModalSource, /value="REGULATORY"/)
  assert.match(isoRevisionRestoreModalSource, /change_category: form\.change_category/)
  assert.match(isoRevisionRestoreModalSource, /approval_data: approvalData/)
})

test('validation and process progress components follow the shared contract', () => {
  assert.match(validationSignatureSource, /class="ds-panel overflow-hidden"/)
  assert.match(validationSignatureSource, /gestlab\.general\.labels\.signature\.empty_error/)
  assert.match(validationSignatureSource, /class="ds-button ds-button-primary"/)
  assert.doesNotMatch(validationSignatureSource, /console\.log|alert\(/)
  assert.doesNotMatch(validationSignatureSource, /bg-white sm:rounded-lg/)

  assert.match(validationModalSource, /form\.transform\(\(\) => payload\)\.post/)
  assert.match(validationModalSource, /quality_certificates\.verify_description/)
  assert.doesNotMatch(validationModalSource, /Por favor, assine digitalmente/)
  assert.doesNotMatch(validationModalSource, /bg-gradient-to-r/)

  assert.match(progressTrackerSource, /class="ds-panel"/)
  assert.match(progressTrackerSource, /gestlab\.general\.navigation\.progress/)
  assert.doesNotMatch(progressTrackerSource, /border-\[#ded3bf\]/)
})

test('authentication keeps the login action above marketing content on mobile', () => {
  assert.match(staffLoginSource, /<AuthExperienceShell/)
  assert.match(staffLoginSource, /eyebrow="Área interna"/)
  assert.match(staffLoginSource, /class="ds-field"/)
  assert.match(staffLoginSource, /ds-button ds-button-primary/)
  assert.match(staffLoginSource, /brandLoginHeadline/)
  assert.match(staffLoginSource, /route\('passkeys\.login'\)/)
  assert.doesNotMatch(staffLoginSource, legacyWarmPalettePattern)
  assert.doesNotMatch(staffLoginSource, /<style scoped>|rounded-\[2rem\]/)

  assert.match(portalLoginSource, /<AuthExperienceShell/)
  assert.match(portalLoginSource, /mode="portal"/)
  assert.match(portalLoginSource, /class="mt-7 space-y-5"/)
  assert.match(portalLoginSource, /class="ds-field"/)
  assert.match(portalLoginSource, /ds-button ds-button-primary/)
  assert.match(portalLoginSource, /startAuthentication/)
  assert.match(portalLoginSource, /Portal do cliente/)
  assert.doesNotMatch(portalLoginSource, /gestlab\.pages\.portal_login|commercialDocumentThemeClasses/)
  assert.doesNotMatch(portalLoginSource, /bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-slate|border-slate|text-slate|shadow-sm/)
  assert.doesNotMatch(portalLoginSource, legacyWarmPalettePattern)

  assert.match(authExperienceShellSource, /class="min-h-dvh bg-\[var\(--ds-panel\)\]/)
  assert.match(authExperienceShellSource, /buildBrandingCssVariables/)
  assert.match(authExperienceShellSource, /brandInitials/)
  assert.doesNotMatch(authExperienceShellSource, legacyWarmPalettePattern)
  assert.doesNotMatch(authExperienceShellSource, /rounded-\[2rem\]|radial-gradient/)
})

test('portal and dashboard shortcuts share the LIMS product surfaces', () => {
  assert.match(portalLayoutSource, /class="ds-floating-panel/)
  assert.match(portalLayoutSource, /class="ds-sidebar-panel/)
  assert.match(portalLayoutSource, /class="ds-button ds-button-secondary/)
  assert.doesNotMatch(portalLayoutSource, legacyWarmPalettePattern)

  assert.match(quickStatsSource, /class="mt-4 grid overflow-hidden rounded-lg border/)
  assert.match(quickStatsSource, /bg-cyan-600/)
  assert.match(quickStatsSource, /bg-amber-500/)
  assert.doesNotMatch(quickStatsSource, /ds-card|hover:-translate-y/)
  assert.doesNotMatch(quickStatsSource, legacyWarmPalettePattern)

  assert.match(quickMenuSource, /class="grid overflow-hidden rounded-lg border-l border-t/)
  assert.match(quickMenuSource, /class="ds-button ds-button-secondary/)
  assert.match(quickMenuSource, /ChevronRightIcon/)
  assert.doesNotMatch(quickMenuSource, /ds-card|<svg|hover:-translate-y/)
  assert.doesNotMatch(quickMenuSource, legacyWarmPalettePattern)
})

test('executive dashboard uses dense decision and register surfaces', () => {
  assert.match(dashboardSource, /Visão geral do laboratório/)
  assert.match(dashboardSource, /class="ds-panel overflow-hidden"/)
  assert.match(dashboardSource, /class="ds-table-shell"/)
  assert.match(dashboardSource, /Fornecedores sob observação/)
  assert.match(dashboardSource, /Necessidades à espera de compra/)
  assert.match(dashboardSource, /Recepções com desvio formal/)
  assert.match(dashboardSource, /ds-badge ds-badge-danger/)
  assert.doesNotMatch(dashboardSource, /commercialDocumentThemeClasses|class="card|rounded-3xl|rounded-2xl|rounded-\[/)
})

test('laboratory metrics use a period-filtered throughput control surface', () => {
  assert.match(metricsIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(metricsIndexSource, /class="ds-panel overflow-hidden"/)
  assert.match(metricsIndexSource, /<date-picker/)
  assert.match(metricsIndexSource, /route\('metrics\.index'\)/)
  assert.match(metricsIndexSource, /route\('analysis\.index'\)/)
  assert.match(metricsIndexSource, /const completionRate = computed/)
  assert.match(metricsIndexSource, /currency: 'AOA'/)
  assert.match(metricsIndexSource, /Math\.max\(0, total\.value - finalized\.value - pending\.value\)/)
  assert.doesNotMatch(metricsIndexSource, /commercialDocumentThemeClasses|ModuleHero|ModuleCard|href="#"|<svg|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|v-motion|<style|console\.log|alert\(|confirm\(/)
})

test('system activity uses a filterable audit register and controlled evidence dialog', () => {
  assert.match(systemActivitySource, /Registo de actividade/)
  assert.match(systemActivitySource, /class="ds-table-shell"/)
  assert.match(systemActivitySource, /class="ds-command-surface/)
  assert.match(systemActivitySource, /Trilho de auditoria/)
  assert.match(systemActivitySource, /Propriedades registadas/)
  assert.match(systemActivitySource, /type="date"/)
  assert.match(systemActivitySource, /fetch\(route\("systemactivity\.show"/)
  assert.doesNotMatch(systemActivitySource, /axios|console\.log|alert\(|confirm\(|commercialDocumentThemeClasses|class="card|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[/)
})

test('customer portal overview and document libraries use compact traceable application UI', () => {
  for (const source of [
    portalDashboardSource,
    portalServicesSource,
    portalProfileSource,
    portalDocumentLibrarySource,
    portalCollectionsSource,
    portalFaqsSource,
    portalRequestsSource,
    portalRequestFormSource,
    portalSecuritySource,
    passkeyManagementSource,
  ]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  for (const source of [portalInvoicesSource, portalReceiptsSource, portalCreditNotesSource, portalQuotesSource, portalContractGuidesSource, portalQualityCertificatesSource]) {
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(portalDashboardSource, /const metricCards = computed/)
  assert.match(portalDashboardSource, /Pedidos recentes/)
  assert.match(portalDashboardSource, /Documentos da conta/)
  assert.match(portalServicesSource, /function serviceIcon/)
  assert.match(portalProfileSource, /Contacto e local/)
  assert.match(portalCollectionsSource, /const metrics = computed/)
  assert.match(portalCollectionsSource, /trackingClass/)
  assert.match(portalCollectionsSource, /portal\.collections\.export/)
  assert.match(portalCollectionsSource, /<Pagination/)
  assert.match(portalFaqsSource, /<Disclosure/)
  assert.match(portalFaqsSource, /function applyFilters/)
  assert.doesNotMatch(portalFaqsSource, /markAsHelpful|markAsUnhelpful|shareQuestion|href="#"/)

  assert.match(portalRequestsSource, /<SlideOver/)
  assert.match(portalRequestsSource, /<PortalRequestForm/)
  assert.match(portalRequestsSource, /function submitRequest/)
  assert.match(portalRequestsSource, /portal\.request\.export/)
  assert.match(portalRequestsSource, /<Pagination/)
  assert.match(portalRequestFormSource, /function importBatchSamples/)
  assert.match(portalRequestFormSource, /form\.details\.requested_profiles/)
  assert.match(portalRequestFormSource, /form\.details\.collection_location/)
  assert.match(portalRequestFormSource, /sample\.packaging/)
  assert.match(portalRequestFormSource, /sample\.notes/)

  assert.match(portalSecuritySource, /<PasskeyManagementForm/)
  assert.match(portalSecuritySource, /async function requestPasswordConfirmation/)
  assert.match(portalSecuritySource, /async function loadTwoFactorDetails/)
  assert.match(portalSecuritySource, /fetch\(route\(routeName\)/)
  assert.match(portalSecuritySource, /portal\.two-factor\.qr-code/)
  assert.match(portalSecuritySource, /portal\.other-browser-sessions\.destroy/)
  assert.match(passkeyManagementSource, /startRegistration/)
  assert.match(passkeyManagementSource, /ds-button ds-button-primary/)
  assert.match(passkeyManagementSource, /fetch\(route\(props\.routes\.registrationOptions\)/)
  assert.match(passkeyManagementSource, /FingerPrintIcon/)

  assert.match(portalDocumentLibrarySource, /const documents = computed/)
  assert.match(portalDocumentLibrarySource, /function submitSearch/)
  assert.match(portalDocumentLibrarySource, /<Pagination/)
  assert.match(portalDocumentLibrarySource, /target="_blank" rel="noopener"/)
  assert.match(portalDocumentLibrarySource, /portal\.requests\.index/)
  assert.doesNotMatch(portalDocumentLibrarySource, /navigator\.clipboard|createElement\(|setAttribute\('download'/)

  assert.match(portalInvoicesSource, /download-route="portal\.invoices\.getInvoicePDF"/)
  assert.match(portalReceiptsSource, /download-route="portal\.receipts\.getReceiptPDF"/)
  assert.match(portalCreditNotesSource, /download-route="portal\.creditnotes\.getCreditNotePDF"/)
  assert.match(portalQuotesSource, /download-route="portal\.quotes\.getQuotePDF"/)
  assert.match(portalContractGuidesSource, /download-route="portal\.contractguides\.getContractGuidePDF"/)
  assert.match(portalQualityCertificatesSource, /download-route="portal\.qualitycertificates\.getQualityCertificatePDF"/)
})

test('maintenance dashboard uses Tailkit-style operational surfaces', () => {
  assert.match(vapMaintenanceDashboardSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapMaintenanceDashboardSource, /class="ds-command-surface p-4"/)
  assert.match(vapMaintenanceDashboardSource, /class="ds-table-shell"/)
  assert.match(vapMaintenanceDashboardSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapMaintenanceDashboardSource, /class="ds-field"/)
  assert.match(vapMaintenanceDashboardSource, /class="ds-button ds-button-primary"/)
  assert.match(vapMaintenanceDashboardSource, /const filterState = reactive/)
  assert.match(vapMaintenanceDashboardSource, /fetch\(url\.toString\(\)/)
  assert.match(vapMaintenanceDashboardSource, /JSON\.stringify\(\{ \.\.\.filterState \}\)/)
  assert.doesNotMatch(vapMaintenanceDashboardSource, /axios/)
  assert.doesNotMatch(vapMaintenanceDashboardSource, /bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-900|from-red-600|from-orange-600|from-green-600/)
})

test('maintenance task index uses dense LIMS operational surfaces', () => {
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-command-surface p-4"/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-command-toolbar px-5 py-4"/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-table-shell"/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-field"/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-checkbox/)
  assert.match(vapMaintenanceTasksIndexSource, /class="ds-button ds-button-primary"/)
  assert.match(vapMaintenanceTasksIndexSource, /const taskItems = computed/)
  assert.match(vapMaintenanceTasksIndexSource, /const statsCards = computed/)
  assert.match(vapMaintenanceTasksIndexSource, /const hasActiveFilters = computed/)
  assert.doesNotMatch(vapMaintenanceTasksIndexSource, /bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-900|from-gray-50|to-gray-100|border-gray-300|focus:ring-blue|focus:border-blue|shadow-sm/)
})

test('maintenance category library uses compact LIMS management surfaces', () => {
  assert.match(vapMaintenanceCategoriesSource, /class="ds-panel overflow-hidden/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-command-surface p-5/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-card group flex min-h-full/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-empty-state/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-table-summary flex/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-field/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-button ds-button-primary/)
  assert.match(vapMaintenanceCategoriesSource, /class="ds-table-action/)
  assert.match(vapMaintenanceCategoriesSource, /const categoryItems = computed/)
  assert.match(vapMaintenanceCategoriesSource, /const statsCards = computed/)
  assert.doesNotMatch(vapMaintenanceCategoriesSource, /bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-900|from-gray-50|to-gray-100|border-gray-300|focus:ring-blue|focus:border-blue|shadow-sm/)
})

test('maintenance task create and show screens use operational form/detail surfaces', () => {
  assert.match(vapMaintenanceTasksCreateSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapMaintenanceTasksCreateSource, /class="ds-card overflow-hidden"/)
  assert.match(vapMaintenanceTasksCreateSource, /class="ds-command-surface p-5"/)
  assert.match(vapMaintenanceTasksCreateSource, /class="ds-checkbox"/)
  assert.match(vapMaintenanceTasksCreateSource, /const fieldClass = \(field\) =>/)
  assert.match(vapMaintenanceTasksCreateSource, /useForm\(/)

  assert.match(vapMaintenanceTasksShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapMaintenanceTasksShowSource, /class="ds-command-surface p-5"/)
  assert.match(vapMaintenanceTasksShowSource, /class="ds-table-shell"/)
  assert.match(vapMaintenanceTasksShowSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapMaintenanceTasksShowSource, /class="ds-field/)
  assert.match(vapMaintenanceTasksShowSource, /equipmentHistory: Object/)
  assert.match(vapMaintenanceTasksShowSource, /const equipmentHistoryCards = computed/)
  assert.match(vapMaintenanceTasksShowSource, /const recentEquipmentTasks = computed/)

  for (const source of [vapMaintenanceTasksCreateSource, vapMaintenanceTasksShowSource]) {
    assert.doesNotMatch(source, /bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-900|from-gray-50|to-gray-100|border-gray-300|focus:ring-blue|focus:border-blue|shadow-sm/)
  }
})

test('sample detail page uses traceability-focused LIMS surfaces', () => {
  assert.match(vapSamplesShowSource, /class="ds-panel overflow-hidden/)
  assert.match(vapSamplesShowSource, /class="ds-card p-5"/)
  assert.match(vapSamplesShowSource, /class="ds-command-surface mt-5 p-4"/)
  assert.match(vapSamplesShowSource, /class="ds-table-shell"/)
  assert.match(vapSamplesShowSource, /class="ds-table-summary px-5 py-4"/)
  assert.match(vapSamplesShowSource, /class="ds-field/)
  assert.match(vapSamplesShowSource, /const summaryCards = computed/)
  assert.match(vapSamplesShowSource, /const receptionFields = computed/)
  assert.match(vapSamplesShowSource, /const releaseGateMetrics = computed/)
  assert.match(vapSamplesShowSource, /const qcFields = computed/)
  assert.doesNotMatch(vapSamplesShowSource, /ModuleHero|ModuleCard|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)
})

test('sample intake console uses accessioning and destruction-control surfaces', () => {
  assert.match(vapSamplesIndexSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapSamplesIndexSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapSamplesIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapSamplesIndexSource, /class="ds-table-shell"/)
  assert.match(vapSamplesIndexSource, /class="ds-table-summary/)
  assert.match(vapSamplesIndexSource, /class="ds-field/)
  assert.match(vapSamplesIndexSource, /class="ds-button ds-button-primary/)
  assert.match(vapSamplesIndexSource, /class="ds-table-action ds-table-action-danger"/)
  assert.match(vapSamplesIndexSource, /const technicalIdentityFields =/)
  assert.match(vapSamplesIndexSource, /const flash = computed\(\(\) => page\.props\.flash \|\| \{\}\)/)
  assert.match(vapSamplesIndexSource, /const form = useForm\(/)
  assert.match(vapSamplesIndexSource, /const discardForm = useForm\(/)
  assert.match(vapSamplesIndexSource, /@submit\.prevent="editingSample\.id \? updateSample\(\) : submitSample\(\)"/)
  assert.match(vapSamplesIndexSource, /@submit\.prevent="submitDiscard"/)
  assert.doesNotMatch(vapSamplesIndexSource, /ModuleHero|ModuleCard|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)
})

test('sample registry uses a compact read-only worklist surface', () => {
  assert.match(sampleRegistrySource, /class="ds-command-surface overflow-hidden"/)
  assert.match(sampleRegistrySource, /<vap-table/)
  assert.match(sampleRegistrySource, /route\("samples\.index"\)/)
  assert.match(sampleRegistrySource, /route\("directcollections\.getMultipleParametersToAnalyzePDF"/)
  assert.match(sampleRegistrySource, /class="ds-button ds-button-primary/)
  assert.match(sampleRegistrySource, /watch\(selectedParameters, applyParameterFilter/)
  assert.doesNotMatch(sampleRegistrySource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
})

test('sample reports workspace uses operational analytics surfaces', () => {
  assert.match(vapSamplesReportsSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(vapSamplesReportsSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapSamplesReportsSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(vapSamplesReportsSource, /class="ds-card border-l-4 p-4"/)
  assert.match(vapSamplesReportsSource, /class="ds-table-shell overflow-x-auto"/)
  assert.match(vapSamplesReportsSource, /class="ds-table-summary flex/)
  assert.match(vapSamplesReportsSource, /<apexchart type="donut"/)
  assert.match(vapSamplesReportsSource, /<apexchart type="area"/)
  assert.match(vapSamplesReportsSource, /<apexchart type="bar"/)
  assert.match(vapSamplesReportsSource, /const summaryCards = computed/)
  assert.match(vapSamplesReportsSource, /const breakdownGroups = computed/)
  assert.match(vapSamplesReportsSource, /function statusDotClass/)
  assert.match(vapSamplesReportsSource, /function qcReleaseDotClass/)
  assert.match(vapSamplesReportsSource, /CQ interno \/ matéria-prima/)
  assert.match(vapSamplesReportsSource, /router\.get\(route\('vap_samples\.reports'\)/)
  assert.doesNotMatch(vapSamplesReportsSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|statusBadgeClass|qcReleaseBadgeClass|bg-gradient-to|rounded-3xl|rounded-2xl|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)
})

test('analysis queue and result bench use controlled worklist surfaces', () => {
  const analysisWorkflowSources = [analysisIndexSource, analysisResultsWorkflowSource]

  for (const source of analysisWorkflowSources) {
    assert.match(source, /min-w-0 space-y-6 overflow-x-clip/)
    assert.match(source, /ds-panel/)
    assert.match(source, /lims-status-dot/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  for (const source of [
    analysisInsertResultSource,
    analysisResultReviewSource,
    analysisResultItemSource,
    analysisIndividualResultSource,
    analysisCalculationEntrySource,
    calculationModalSource,
  ]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|<style|alert\(|console\.error|console\.log/)
  }

  for (const source of [analysisVerifyResultSource, analysisApproveResultSource]) {
    assert.match(source, /<ResultReviewSurface/)
    assert.doesNotMatch(source, /alert\(|confirm\(|console\.|<style|bg-gradient-to|rounded-\[/)
  }

  assert.match(analysisIndexSource, /const queueMetrics = computed/)
  assert.match(analysisIndexSource, /const resultActions = \[/)
  assert.match(analysisIndexSource, /<VapTable/)
  assert.match(analysisIndexSource, /function requestConfirmation/)
  assert.match(analysisIndexSource, /function openAnalysis/)

  assert.match(analysisResultsWorkflowSource, /const workflowSteps = computed/)
  assert.match(analysisResultsWorkflowSource, /const calculationReadiness = computed/)
  assert.match(analysisResultsWorkflowSource, /const scopeDriftSummary = computed/)
  assert.match(analysisResultsWorkflowSource, /form\.transform\(\(\) => submissionData\)\.post/)
  assert.match(analysisResultsWorkflowSource, /ds-command-surface/)
  assert.match(analysisResultsWorkflowSource, /<CalculationModal/)

  assert.match(analysisInsertResultSource, /const insertionMetrics = computed/)
  assert.match(analysisInsertResultSource, /const workflowMode = ref\("batch"\)/)
  assert.match(analysisInsertResultSource, /<ResultItem/)
  assert.match(analysisInsertResultSource, /<IndividualResultEntry/)
  assert.match(analysisVerifyResultSource, /mode="verify"/)
  assert.match(analysisApproveResultSource, /mode="approve"/)
  assert.match(analysisResultReviewSource, /const reviewMetrics = computed/)
  assert.match(analysisResultReviewSource, /function submitReview/)
  assert.match(analysisResultReviewSource, /verification_status/)
  assert.match(analysisResultReviewSource, /ds-command-toolbar sticky/)
  assert.match(analysisResultItemSource, /const isOutOfRange = computed/)
  assert.match(analysisResultItemSource, /<Combobox/)
  assert.match(analysisResultItemSource, /ds-field/)
  assert.match(analysisIndividualResultSource, /props\.existingResults/)
  assert.match(analysisIndividualResultSource, /selectedParameterId\.value = getParameterUniqueId\(result\)/)
  assert.match(analysisIndividualResultSource, /<Modal/)
  assert.match(analysisIndividualResultSource, /ds-field/)
  assert.match(analysisCalculationEntrySource, /const missingVariables = computed/)
  assert.match(analysisCalculationEntrySource, /function applyCalculation/)
  assert.match(analysisCalculationEntrySource, /calculatedResults/)
  assert.doesNotMatch(analysisCalculationEntrySource, /calculationMode|batch mode/i)
  assert.match(calculationModalSource, /max-width="6xl"/)
  assert.match(calculationModalSource, /<CalculationResultEntry/)
})

test('worksheet queue and editor use dense operational application UI patterns', () => {
  for (const source of [worksheetsIndexSource, worksheetsEditSource]) {
    assert.match(source, /ds-panel/)
    assert.match(source, /ds-table-shell/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(worksheetsIndexSource, /const searchTerm = ref\(""\)/)
  assert.match(worksheetsIndexSource, /const statusFilter = ref\("all"\)/)
  assert.match(worksheetsIndexSource, /const filteredWorksheets = computed/)
  assert.match(worksheetsIndexSource, /<DataTable class="min-w-full/)
  assert.match(worksheetsIndexSource, /class="ds-empty-state/)

  assert.match(worksheetsEditSource, /<Link :href="route\('worksheets\.index'\)"/)
  assert.match(worksheetsEditSource, /const columnCount = computed/)
  assert.match(worksheetsEditSource, /function columnLabel/)
  assert.match(worksheetsEditSource, /function removeLastRow/)
  assert.match(worksheetsEditSource, /function removeLastColumn/)
  assert.match(worksheetsEditSource, /ds-settings-tab-active/)
  assert.match(worksheetsEditSource, /form\.isDirty/)
})

test('customer request intake uses a shared triage form and operational list surface', () => {
  for (const source of [
    customerRequestsIndexSource,
    customerRequestsCreateSource,
    customerRequestsEditSource,
    customerRequestFormSource,
  ]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(customerRequestsIndexSource, /<RecordsTable/)
  assert.match(customerRequestsIndexSource, /<SlideOver/)
  assert.match(customerRequestsIndexSource, /<CustomerRequestForm :form="form"/)
  assert.match(customerRequestsIndexSource, /function requestBulkAction/)
  assert.match(customerRequestsIndexSource, /form\.put\(route\("customerrequests\.update"/)
  assert.doesNotMatch(customerRequestsIndexSource, /showDeleteConfirmationSlideover|TransitionRoot/)

  assert.match(customerRequestFormSource, /loadSelectOptions/)
  assert.match(customerRequestFormSource, /:disable-input="!form\.customer_id"/)
  assert.match(customerRequestFormSource, /class="ds-field min-h-36 resize-y"/)
  assert.match(customerRequestsCreateSource, /<CustomerRequestForm :form="form"/)
  assert.match(customerRequestsEditSource, /const request = props\.record\?\.data \?\? props\.record/)
})

test('staff directory uses a competence-first Tailwind application table and focused create panel', () => {
  assert.match(usersIndexSource, /ds-/)
  assert.doesNotMatch(usersIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|showDeleteConfirmationSlideover/)
  assert.match(usersIndexSource, /const metrics = computed/)
  assert.match(usersIndexSource, /<RecordsTable/)
  assert.match(usersIndexSource, /<SlideOver/)
  assert.match(usersIndexSource, /form\.post\(route\("users\.store"/)
  assert.match(usersIndexSource, /requestRecordAction\('impersonate'/)
  assert.match(usersIndexSource, /requestRecordAction\(data\.is_active \? 'ban' : 'unban'/)
  assert.match(usersIndexSource, /Competência e autorização/)
  assert.match(usersIndexSource, /Evidência em falta/)
})

test('staff dossier uses controlled identity, access, and competence sections', () => {
  assert.match(usersEditSource, /ds-/)
  assert.match(usersEditSource, /Dossier de pessoal/)
  assert.match(usersEditSource, /Vínculo organizacional e acesso/)
  assert.match(usersEditSource, /Competência técnica e certificação/)
  assert.match(usersEditSource, /<ToggleField/)
  assert.match(usersEditSource, /<ConfirmDialog/)
  assert.match(usersEditSource, /confirmationAction\.value = 'discard'/)
  assert.match(usersEditSource, /form\.put\(route\('users\.update', \{ user: form\.id \}\)/)
  assert.match(usersEditSource, /passwordForm\.put\(route\('users\.setpass', \{ user: form\.id \}\)/)
  assert.match(usersEditSource, /route\('users\.setsignature'\)/)
  assert.match(usersEditSource, /route\('users\.unsetsignature'\)/)
  assert.doesNotMatch(usersEditSource, /UserCard|ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|Fuse/)
})

test('department registry uses a supervised organizational table and direct editor workflow', () => {
  assert.match(departmentsIndexSource, /ds-/)
  assert.doesNotMatch(departmentsIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|showDeleteConfirmationSlideover/)
  assert.match(departmentsIndexSource, /const metrics = computed/)
  assert.match(departmentsIndexSource, /<RecordsTable/)
  assert.match(departmentsIndexSource, /<SlideOver/)
  assert.match(departmentsIndexSource, /@slideover-on="openEditPanel"/)
  assert.match(departmentsIndexSource, /form\.put\(route\("departments\.update"/)
  assert.match(departmentsIndexSource, /form\.post\(route\("departments\.store"/)
  assert.match(departmentsIndexSource, /type="tel"/)
  assert.match(departmentsIndexSource, /inputmode="numeric"/)
  assert.match(recordsTableSource, /function editRecord\(record\)/)
  assert.match(recordsTableSource, /router\.visit\(record\.links\.edit_path/)
  assert.doesNotMatch(recordsTableSource, /actionId = 'edit'|actionId = 'edit_slide'/)
})

test('permission registry uses a compact access-control table and guarded editor', () => {
  assert.match(permissionsIndexSource, /ds-/)
  assert.doesNotMatch(permissionsIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|showDeleteConfirmationSlideover/)
  assert.match(permissionsIndexSource, /const metrics = computed/)
  assert.match(permissionsIndexSource, /<RecordsTable/)
  assert.match(permissionsIndexSource, /<SlideOver/)
  assert.match(permissionsIndexSource, /@slideover-on="openEditPanel"/)
  assert.match(permissionsIndexSource, /form\.put\(route\("permissions\.update"/)
  assert.match(permissionsIndexSource, /form\.post\(route\("permissions\.store"/)
  assert.match(permissionsIndexSource, /Chave técnica estável/)
  assert.match(permissionsIndexSource, /class="ds-field mt-2 font-mono"/)
})

test('role registry separates role identity from permission assignment', () => {
  assert.match(rolesIndexSource, /ds-/)
  assert.doesNotMatch(rolesIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|showDeleteConfirmationSlideover/)
  assert.match(rolesIndexSource, /const metrics = computed/)
  assert.match(rolesIndexSource, /<RecordsTable/)
  assert.match(rolesIndexSource, /<SlideOver/)
  assert.match(rolesIndexSource, /:slide-over-edit="false"/)
  assert.match(rolesIndexSource, /form\.post\(route\("roles\.store"/)
  assert.match(rolesIndexSource, /será encaminhado para associar permissões/)
  assert.doesNotMatch(rolesIndexSource, /@slideover-on=/)
})

test('role permission editor uses a least-privilege assignment workflow', () => {
  assert.match(rolesEditSource, /ds-/)
  assert.doesNotMatch(rolesEditSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|confirm-dialog|Fuse|throttle/)
  assert.match(rolesEditSource, /type="checkbox"/)
  assert.match(rolesEditSource, /function toggleVisiblePermissions/)
  assert.match(rolesEditSource, /new Set\(\[\.\.\.form\.permissions, \.\.\.visiblePermissionIds\]\)/)
  assert.match(rolesEditSource, /form\.permissions\.length > 0/)
  assert.match(rolesEditSource, /form\.put\(route\("roles\.update"/)
  assert.match(rolesEditSource, /Princípio do menor privilégio/)
  assert.match(rolesEditSource, /id="role-guard"/)
})

test('occurrence register uses a triage-first table and compact batch import', () => {
  for (const source of [occurrencesIndexSource, occurrenceImportFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|showDeleteConfirmationSlideover/)
  }

  assert.match(occurrencesIndexSource, /const metrics = computed/)
  assert.match(occurrencesIndexSource, /<RecordsTable/)
  assert.match(occurrencesIndexSource, /:slide-over-edit="false"/)
  assert.match(occurrencesIndexSource, /route\('occurrences\.show'/)
  assert.match(occurrencesIndexSource, /implementation_date_overdue/)
  assert.match(occurrenceImportFormSource, /useForm\("OccurrenceImport"/)
  assert.match(occurrenceImportFormSource, /accept="\.csv,text\/csv"/)
  assert.match(occurrenceImportFormSource, /forceFormData: true/)
})

test('occurrence create and edit screens share one controlled CAPA workflow', () => {
  for (const source of [occurrenceCreateSource, occurrenceEditSource, occurrenceFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|confirm-dialog/)
  }

  assert.match(occurrenceCreateSource, /<OccurrenceForm :form="form"/)
  assert.match(occurrenceEditSource, /<OccurrenceForm :form="form"/)
  assert.match(occurrenceCreateSource, /form\.post\(route\("occurrences\.store"/)
  assert.match(occurrenceEditSource, /form\.put\(route\("occurrences\.update"/)
  assert.match(occurrenceFormSource, /<ToggleField/)
  assert.match(occurrenceFormSource, /v-model="form\.user_id"/)
  assert.match(occurrenceFormSource, /type="date"/)
  assert.match(occurrenceFormSource, /Risco e conformidade/)
  assert.match(occurrenceFormSource, /Acção correctiva e eficácia/)
})

test('occurrence dossier presents traceable evidence without unsafe quick mutations', () => {
  assert.match(occurrenceShowSource, /ds-/)
  assert.doesNotMatch(occurrenceShowSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|router\.(patch|delete)|status_id:\s*3/)
  assert.match(occurrenceShowSource, /const timeline = computed/)
  assert.match(occurrenceShowSource, /implementation_date_overdue/)
  assert.match(occurrenceShowSource, /Acção correctiva fora do prazo/)
  assert.match(occurrenceShowSource, /Análise de causa e efeito/)
  assert.match(occurrenceShowSource, /Processo com o cliente/)
  assert.match(occurrenceShowSource, /Controlos associados/)
  assert.match(occurrenceShowSource, /hasPermission\('edit_occurrences'\)/)
})

test('quality governance registers use compact traceable tables', () => {
  for (const source of [complaintsIndexSource, managementReviewsIndexSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot/)
    assert.match(source, /<Pagination v-bind=/)
  }

  assert.match(complaintsIndexSource, /route\("complaints\.index"/)
  assert.match(complaintsIndexSource, /severityClass/)
  assert.match(managementReviewsIndexSource, /route\("management-reviews\.index"/)
  assert.match(managementReviewsIndexSource, /review\.approved_by/)
})

test('application event automation uses an explicit event-template mapping table', () => {
  assert.match(applicationEventsIndexSource, /ds-/)
  assert.doesNotMatch(applicationEventsIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot|btn-primary|table-auto/)
  assert.match(applicationEventsIndexSource, /route\("app-events\.sync"/)
  assert.match(applicationEventsIndexSource, /route\("app-events\.associate"/)
  assert.match(applicationEventsIndexSource, /selectedTemplates/)
  assert.match(applicationEventsIndexSource, /Mapa evento-modelo/)
})

test('archived document workflow uses a shared evidence-retention form and register', () => {
  for (const source of [archivedDocumentsIndexSource, archivedDocumentsCreateSource, archivedDocumentsEditSource, archivedDocumentFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open|TransitionRoot/)
  }

  assert.match(archivedDocumentsIndexSource, /<RecordsTable/)
  assert.match(archivedDocumentsIndexSource, /:slide-over-edit="false"/)
  assert.match(archivedDocumentsCreateSource, /<ArchivedDocumentForm :form="form"/)
  assert.match(archivedDocumentsEditSource, /<ArchivedDocumentForm :form="form"/)
  assert.match(archivedDocumentsCreateSource, /forceFormData: true/)
  assert.match(archivedDocumentsEditSource, /_method: "put"/)
  assert.match(archivedDocumentFormSource, /type="file"/)
  assert.match(archivedDocumentFormSource, /Retenção documental/)
})

test('contract guide workflow uses one traceable shipping and product form', () => {
  for (const source of [contractGuidesIndexSource, contractGuidesCreateSource, contractGuidesEditSource, contractGuideFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|v-motion/)
  }

  assert.match(contractGuidesIndexSource, /<RecordsTable/)
  assert.match(contractGuidesIndexSource, /:slide-over-edit="false"/)
  assert.match(contractGuidesCreateSource, /<ContractGuideForm :form="form"/)
  assert.match(contractGuidesEditSource, /<ContractGuideForm :form="form"/)
  assert.match(contractGuideFormSource, /Produtos transportados/)
  assert.match(contractGuideFormSource, /Rastreabilidade mínima/)
  assert.match(contractGuideFormSource, /type="date"/)
  assert.doesNotMatch(contractGuideFormSource, /confirm-dialog|console\.log|<svg/)
})

test('customer portfolio uses a traceability-first directory, dossier, and shared CRM catalogs', () => {
  for (const source of [
    customersIndexSource,
    customersCreateSource,
    customersEditSource,
    customersShowSource,
    customerTaxIdentificationSource,
    customerFormSource,
    customerSiteEditorSource,
  ]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  for (const source of [customerCategoriesIndexSource, contactCategoriesIndexSource, customerRequestCategoriesIndexSource]) {
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(customersIndexSource, /const metrics = computed/)
  assert.match(customersIndexSource, /<RecordsTable/)
  assert.match(customersIndexSource, /hasPermission\('add_customers'\)/)
  assert.match(customersCreateSource, /<CustomerForm :form="form"/)
  assert.match(customersCreateSource, /form\.post\(route\("customers\.store"/)
  assert.match(customersEditSource, /<CustomerForm :form="form"/)
  assert.match(customersEditSource, /<WarehouseComponent/)
  assert.match(customersEditSource, /form\.put\(route\("customers\.update"/)
  assert.match(customerFormSource, /loadSelectOptions/)
  assert.match(customerFormSource, /class="ds-field min-h-36 resize-y"/)
  assert.match(customerSiteEditorSource, /route\("customers\.changePrimaryWarehouse"/)
  assert.match(customerSiteEditorSource, /Correio electrónico de facturação/)
  assert.match(customersShowSource, /Execução laboratorial recente/)
  assert.match(customersShowSource, /Pedidos do portal/)
  assert.match(customersShowSource, /Evidência documental/)
  assert.match(customersShowSource, /hasPermission\('edit_customers'\)/)
  assert.doesNotMatch(customersShowSource, /<apexchart|created_by|updated_by|last_synced/)
  assert.match(customerTaxIdentificationSource, /await fetch\(`/)
  assert.match(customerTaxIdentificationSource, /Identidade fiscal encontrada/)
  assert.doesNotMatch(customerTaxIdentificationSource, /axios|use_this_data/)

  assert.match(customerCategoriesIndexSource, /route-prefix="customercategories"/)
  assert.match(contactCategoriesIndexSource, /route-prefix="contactcategories"/)
  assert.match(contactCategoriesIndexSource, /:code-required="false"/)
  assert.match(customerRequestCategoriesIndexSource, /route-prefix="customerrequestcategories"/)
  assert.match(customerRequestCategoriesIndexSource, /:supports-code="false"/)
})

test('customer site registry uses controlled operational directory and dossier surfaces', () => {
  for (const source of [warehousesIndexSource, warehousesShowSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(warehousesIndexSource, /<RecordsTable/)
  assert.match(warehousesIndexSource, /<WarehouseComponent/)
  assert.match(warehousesIndexSource, /show-customer-selector/)
  assert.match(warehousesIndexSource, /hasPermission\('add_warehouses'\)/)
  assert.match(warehousesIndexSource, /function executeBulkAction/)
  assert.match(warehousesShowSource, /Saúde da conta/)
  assert.match(warehousesShowSource, /Execução operacional/)
  assert.match(warehousesShowSource, /Acesso ao portal/)
  assert.match(warehousesShowSource, /function sendPasswordResetEmail/)
  assert.match(warehousesShowSource, /function resolveActivityIcon/)
  assert.match(customerSiteEditorSource, /async function loadCustomers/)
  assert.match(customerSiteEditorSource, /emit\("saved"\)/)
})

test('standards library uses a controlled normative catalog and shared editor', () => {
  for (const source of [standardsIndexSource, standardsCreateSource, standardsEditSource, standardFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(standardsIndexSource, /<RecordsTable/)
  assert.match(standardsIndexSource, /<SlideOver/)
  assert.match(standardsIndexSource, /<StandardForm :form="form"/)
  assert.match(standardsIndexSource, /const describedRecords = computed/)
  assert.match(standardsIndexSource, /function executeBulkAction/)
  assert.doesNotMatch(standardsIndexSource, /showDeleteConfirmationSlideover|TransitionRoot/)
  assert.match(standardFormSource, /id="standard-code"/)
  assert.match(standardFormSource, /class="ds-field min-h-40 resize-y"/)
  assert.match(standardsCreateSource, /form\.post\(route\("standards\.store"/)
  assert.match(standardsEditSource, /form\.put\(route\("standards\.update"/)
})

test('analytical reference catalogs share a compact governed management surface', () => {
  for (const source of [referenceCatalogManagerSource, analysisCategoriesIndexSource, protocolsIndexSource, nwpsIndexSource, unitsIndexSource, occurrenceStatusesIndexSource, temperaturesIndexSource, equipmentCategoriesIndexSource, inventoryTransactionTypesIndexSource, faqCategoriesIndexSource, discountCategoriesIndexSource, taxExemptionsIndexSource]) {
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(referenceCatalogManagerSource, /<RecordsTable/)
  assert.match(referenceCatalogManagerSource, /<SlideOver v-if="isPanelOpen"/)
  assert.match(referenceCatalogManagerSource, /const metrics = computed/)
  assert.match(referenceCatalogManagerSource, /loadSelectOptions/)
  assert.match(referenceCatalogManagerSource, /hasPermission\(`add_\$\{permissionKey\}`\)/)
  assert.match(referenceCatalogManagerSource, /form\.put\(route\(`\$\{props\.routePrefix\}\.update`/)
  assert.match(referenceCatalogManagerSource, /class="ds-field mt-2 min-h-40 resize-y"/)
  assert.match(referenceCatalogManagerSource, /form="reference-catalog-form"/)
  assert.match(referenceCatalogManagerSource, /v-for="field in extraFields"/)
  assert.match(referenceCatalogManagerSource, /Object\.fromEntries\(props\.extraFields/)

  assert.match(analysisCategoriesIndexSource, /route-prefix="analysiscategories"/)
  assert.match(analysisCategoriesIndexSource, /:supports-department="true"/)
  assert.match(protocolsIndexSource, /route-prefix="protocols"/)
  assert.match(nwpsIndexSource, /route-prefix="nwps"/)
  assert.match(unitsIndexSource, /route-prefix="units"/)
  assert.match(occurrenceStatusesIndexSource, /route-prefix="occurrencestatuses"/)
  assert.match(temperaturesIndexSource, /route-prefix="temperatures"/)
  assert.match(equipmentCategoriesIndexSource, /route-prefix="equipmentcategories"/)
  assert.match(inventoryTransactionTypesIndexSource, /route-prefix="itransactiontypes"/)
  assert.match(faqCategoriesIndexSource, /route-prefix="faqcategories"/)
  assert.match(discountCategoriesIndexSource, /key: 'symbol'/)
  assert.match(taxExemptionsIndexSource, /key: 'reason'/)
  assert.match(taxExemptionsIndexSource, /key: 'law'/)
})

test('parameter registry uses sectioned analytical configuration and calculation governance', () => {
  for (const source of [parametersIndexSource, parametersCreateSource, parametersEditSource, parameterFormSource, toggleFieldSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(parametersIndexSource, /<RecordsTable/)
  assert.match(parametersIndexSource, /<SlideOver/)
  assert.match(parametersIndexSource, /<ParameterForm :form="form" :formulas="props\.formulas"/)
  assert.match(parametersIndexSource, /createParameterDataFromRecord/)
  assert.match(parametersIndexSource, /form\.post\(route\("parameters\.store"/)
  assert.doesNotMatch(parametersIndexSource, /showDeleteConfirmationSlideover|TransitionRoot|data: submitData/)

  assert.match(parameterFormSource, /function setResultType/)
  assert.match(parameterFormSource, /function extractVariables/)
  assert.match(parameterFormSource, /const governanceIssues = computed/)
  assert.match(parameterFormSource, /<ToggleField v-model="form\.requires_calculation"/)
  assert.match(parameterFormSource, /Âmbito de cálculo controlado/)
  assert.match(parameterFormDataSource, /withhold_tax: Boolean\(record\.withhold_tax\)/)
  assert.match(parameterFormDataSource, /calculation_parameters: normalizeCalculationParameters/)
  assert.match(toggleFieldSource, /class="peer sr-only"/)
  assert.match(toggleFieldSource, /peer-checked:before:translate-x-4/)
  assert.match(parametersCreateSource, /<ParameterForm :form="form" :formulas="formulas"/)
  assert.match(parametersEditSource, /<ParameterForm :form="form" :formulas="props\.formulas"/)
})

test('profile catalog uses a controlled analytical composition workflow and dossier', () => {
  for (const source of [profilesIndexSource, profilesCreateSource, profilesEditSource, profilesShowSource, profileFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(profilesIndexSource, /<RecordsTable/)
  assert.match(profilesIndexSource, /hasPermission\('add_profiles'\)/)
  assert.match(profilesCreateSource, /<ProfileForm :form="form"/)
  assert.match(profilesEditSource, /createProfileDataFromRecord/)
  assert.match(profilesEditSource, /form\.put\(route\("profiles\.update"/)

  assert.match(profileFormSource, /const governanceIssues = computed/)
  assert.match(profileFormSource, /loadSelectOptions/)
  assert.match(profileFormSource, /<ToggleField v-model="parameter\.count"/)
  assert.match(profileFormSource, /parameter\.extra_data\.dilutions/)
  assert.match(profileFormSource, /protocols\/getProtocol/)
  assert.match(profileFormSource, /standards\/getStandard/)
  assert.match(profileFormSource, /resultcategories\/getResultCategory/)
  assert.match(profileFormSource, /formulas\/getFormula/)
  assert.match(profileFormDataSource, /dilutions: normalizeTextCollection\(parameter\.dilutions\)/)

  assert.match(profilesShowSource, /Composição analítica/)
  assert.match(profilesShowSource, /const configuredMethods = computed/)
  assert.match(profilesShowSource, /function dilutionSteps/)
  assert.match(profilesShowSource, /hasPermission\('edit_profiles'\)/)
  assert.doesNotMatch(profilesShowSource, /created_at|updated_at|matrixes|analysis_category/)
})

test('matrix catalog uses a governed commercial scope workflow and dossier', () => {
  for (const source of [matrixesIndexSource, matrixesCreateSource, matrixesEditSource, matrixesShowSource, matrixFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(matrixesIndexSource, /<RecordsTable/)
  assert.match(matrixesIndexSource, /hasPermission\('add_matrixes'\)/)
  assert.match(matrixesCreateSource, /<MatrixForm :form="form"/)
  assert.match(matrixesCreateSource, /form\.post\(route\("matrixes\.store"/)
  assert.match(matrixesEditSource, /createMatrixDataFromRecord/)
  assert.match(matrixesEditSource, /form\.put\(route\("matrixes\.update"/)

  assert.match(matrixFormSource, /const governanceIssues = computed/)
  assert.match(matrixFormSource, /loadSelectOptions/)
  assert.match(matrixFormSource, /<ToggleField v-model="form\.charge_tax"/)
  assert.match(matrixFormSource, /<ToggleField v-model="form\.withhold_tax"/)
  assert.match(matrixFormSource, /profiles\/getProfile/)
  assert.match(matrixFormSource, /taxexemptions\/getExemption/)
  assert.match(matrixFormSource, /taxtypes\/getTaxType/)
  assert.match(matrixFormSource, /props\.form\.price = price/)
  assert.match(matrixFormSource, /commercialVariance/)
  assert.match(matrixFormDataSource, /withhold_tax: Boolean\(record\.withhold_tax\)/)

  assert.match(matrixesShowSource, /Perfis analíticos/)
  assert.match(matrixesShowSource, /const departmentNames = computed/)
  assert.match(matrixesShowSource, /hasPermission\('edit_matrixes'\)/)
  assert.doesNotMatch(matrixesShowSource, /<main/)
})

test('direct collection dossier uses traceability-first operational surfaces', () => {
  assert.match(directCollectionsShowSource, /class="ds-panel overflow-hidden"/)
  assert.match(directCollectionsShowSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(directCollectionsShowSource, /class="ds-command-surface p-5"/)
  assert.match(directCollectionsShowSource, /class="ds-card p-5"/)
  assert.match(directCollectionsShowSource, /class="ds-command-palette-item group"/)
  assert.match(directCollectionsShowSource, /const workflowSteps = computed/)
  assert.match(directCollectionsShowSource, /const collectionDetailFields = computed/)
  assert.match(directCollectionsShowSource, /const documentLinks = computed/)
  assert.match(directCollectionsShowSource, /Âmbito controlado da recepção/)
  assert.match(directCollectionsShowSource, /Abrir entrada de amostra/)
  assert.doesNotMatch(directCollectionsShowSource, /commercialDocumentThemeClasses|StatusToggle|<style scoped>|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-gray-50|to-gray-100|border-gray-300|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf/)
})

test('collection queues use accessioning-first operational worklists', () => {
  for (const source of [directCollectionsIndexSource, programmedCollectionsIndexSource]) {
    assert.match(source, /class="ds-panel overflow-hidden/)
    assert.match(source, /entrada de amostra/i)
    assert.match(source, /Exportar XLSX/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|ModuleHero|ModuleCard|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(directCollectionsIndexSource, /<VapTable/)
  assert.match(directCollectionsIndexSource, /const scopeDashboard = computed/)
  assert.match(directCollectionsIndexSource, /route\("directcollections\.exportParametersToAnalyzeSheet"/)
  assert.match(directCollectionsIndexSource, /<ConfirmDialog/)

  assert.match(programmedCollectionsIndexSource, /<RecordsTable/)
  assert.match(programmedCollectionsIndexSource, /route\("programmedcollections\.exportParametersToAnalyzeSheet"/)
  assert.match(programmedCollectionsIndexSource, /route\('programmedcollections\.show'/)
  assert.doesNotMatch(programmedCollectionsIndexSource, /onMounted|changeCollectionCategory\(\)/)
})

test('collection create and edit routes share one chain-of-custody intake form', () => {
  assert.match(directCollectionsCreateSource, /<CollectionAccessionForm kind="direct"/)
  assert.match(directCollectionsEditSource, /<CollectionAccessionForm kind="direct" :record="record"/)
  assert.match(programmedCollectionsCreateSource, /<CollectionAccessionForm kind="programmed"/)
  assert.match(programmedCollectionsEditSource, /<CollectionAccessionForm kind="programmed" :record="record"/)

  for (const source of [
    directCollectionsCreateSource,
    directCollectionsEditSource,
    programmedCollectionsCreateSource,
    programmedCollectionsEditSource,
    collectionAccessionFormSource,
  ]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|tracking-\[|bg-white|bg-slate|border-slate|text-slate|shadow-(lg|xl|2xl)|console\.log|alert\(|confirm\(|<style/)
  }

  assert.match(collectionAccessionFormSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(collectionAccessionFormSource, /Contexto da colheita/)
  assert.match(collectionAccessionFormSource, /Amostras e cadeia de custódia/)
  assert.match(collectionAccessionFormSource, /Rastreabilidade do lote/)
  assert.match(collectionAccessionFormSource, /form\.products\.length < 5/)
  assert.match(collectionAccessionFormSource, /form\.put\(route/)
  assert.match(collectionAccessionFormSource, /form\.post\(route/)
  assert.doesNotMatch(collectionAccessionFormSource, /<ConfirmDialog|<confirm-dialog/)
})

test('supplier qualification uses scored risk and review controls', () => {
  assert.match(supplierAssessmentsIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(supplierAssessmentsIndexSource, /class="ds-field"/)
  assert.match(supplierAssessmentsIndexSource, /class="ds-checkbox"/)
  assert.match(supplierAssessmentsIndexSource, /const scoreFields =/)
  assert.match(supplierAssessmentsIndexSource, /hasPermission\("edit_isuppliers"\)/)
  assert.match(supplierAssessmentsIndexSource, /<ConfirmDialog/)
  assert.doesNotMatch(supplierAssessmentsIndexSource, /commercialDocumentThemeClasses|ModuleHero|ModuleCard|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('counter-analysis queue preserves source-result traceability', () => {
  assert.match(counterAnalysisIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(counterAnalysisIndexSource, /<RecordsTable/)
  assert.match(counterAnalysisIndexSource, /entrypoint\.analysis_url/)
  assert.match(counterAnalysisIndexSource, /Solicitar nos resultados/)
  assert.match(counterAnalysisIndexSource, /<ConfirmDialog/)
  assert.doesNotMatch(counterAnalysisIndexSource, /commercialDocumentThemeClasses|ModuleHero|ModuleCard|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('environmental monitoring uses controlled readings, limits, and pagination', () => {
  assert.match(environmentalConditionsIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(environmentalConditionsIndexSource, /const measurementFields =/)
  assert.match(environmentalConditionsIndexSource, /const limitFields =/)
  assert.match(environmentalConditionsIndexSource, /class="ds-field"/)
  assert.match(environmentalConditionsIndexSource, /<Pagination/)
  assert.match(environmentalConditionsIndexSource, /<ConfirmDialog/)
  assert.match(environmentalConditionsIndexSource, /hasPermission\("edit_temperatures"\)/)
  assert.doesNotMatch(environmentalConditionsIndexSource, /commercialDocumentThemeClasses|ModuleHero|ModuleCard|window\.confirm|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('inventory support registries use governed LIMS register and drawer patterns', () => {
  const supportRegistrySources = [
    inventoryItemSuppliersIndexSource,
    inventoryItemLocationsIndexSource,
    inventoryItemWarehousesIndexSource,
    inventoryDeliveriesIndexSource,
  ]

  for (const source of supportRegistrySources) {
    assert.match(source, /class="ds-panel overflow-hidden/)
    assert.match(source, /<RecordsTable/)
    assert.match(source, /<ConfirmDialog/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|ModuleHero|ModuleCard|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  for (const source of [inventoryItemSuppliersIndexSource, inventoryItemLocationsIndexSource, inventoryItemWarehousesIndexSource]) {
    assert.match(source, /<SlideOver/)
    assert.match(source, /form\.processing \|\| !form\.isDirty/)
    assert.match(source, /onSuccess: closeDrawer/)
  }

  assert.match(inventoryItemSuppliersIndexSource, /route\('supplier-assessments\.index'\)/)
  assert.match(inventoryItemLocationsIndexSource, /:load-options="loadDepartments"/)
  assert.match(inventoryItemWarehousesIndexSource, /const storageConditions =/)
  assert.match(inventoryItemWarehousesIndexSource, /class="ds-checkbox/)
  assert.match(inventoryDeliveriesIndexSource, /route\("ideliveries\.create"\)/)
})

test('inventory delivery create and edit routes share one traceable dispatch form', () => {
  for (const source of [inventoryDeliveriesCreateSource, inventoryDeliveriesEditSource]) {
    assert.match(source, /<InventoryDeliveryForm/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(inventoryDeliveriesCreateSource, /form\.post\(route\("ideliveries\.store"\)/)
  assert.match(inventoryDeliveriesEditSource, /form\.put\(route\("ideliveries\.update"/)
  assert.match(inventoryDeliveryFormSource, /class="ds-table-shell overflow-hidden"/)
  assert.match(inventoryDeliveryFormSource, /const totalQuantity = computed/)
  assert.match(inventoryDeliveryFormSource, /:load-options="loadCustomers"/)
  assert.match(inventoryDeliveryFormSource, /:load-options="loadItems"/)
  assert.match(inventoryDeliveryFormSource, /:load-options="loadWarehouses"/)
  assert.match(inventoryDeliveryFormSource, /type="number" min="1" step="1"/)
  assert.match(inventoryDeliveryFormSource, /form\.processing \|\| !form\.isDirty/)
  assert.doesNotMatch(inventoryDeliveryFormSource, /commercialDocumentThemeClasses|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('inventory item types use the governed reference catalog manager', () => {
  assert.match(inventoryItemTypesIndexSource, /<ReferenceCatalogManager/)
  assert.match(inventoryItemTypesIndexSource, /route-prefix="itypes"/)
  assert.match(inventoryItemTypesIndexSource, /permission-key="itypes"/)
  assert.match(inventoryItemTypesIndexSource, /:supports-name="true"/)
  assert.match(inventoryItemTypesIndexSource, /:supports-code="false"/)
  assert.doesNotMatch(inventoryItemTypesIndexSource, /commercialDocumentThemeClasses|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('result categories use the governed analytical catalog manager', () => {
  assert.match(resultCategoriesIndexSource, /<ReferenceCatalogManager/)
  assert.match(resultCategoriesIndexSource, /route-prefix="resultcategories"/)
  assert.match(resultCategoriesIndexSource, /route-parameter="category"/)
  assert.match(resultCategoriesIndexSource, /permission-key="result_categories"/)
  assert.match(resultCategoriesIndexSource, /:supports-name="true"/)
  assert.match(resultCategoriesIndexSource, /:supports-code="false"/)
  assert.doesNotMatch(resultCategoriesIndexSource, /commercialDocumentThemeClasses|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('formula library uses a governed analytical calculation register', () => {
  assert.match(formulasIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(formulasIndexSource, /<FormulaDisplay :formula="formula\.expression"/)
  assert.match(formulasIndexSource, /<ConfirmDialog/)
  assert.match(formulasIndexSource, /<Pagination v-if="record\.meta\?\.last_page > 1"/)
  assert.match(formulasIndexSource, /route\('variables\.index'\)/)
  assert.match(formulasIndexSource, /route\('formulas\.create'\)/)
  assert.match(formulasIndexSource, /route\('formulas\.index'\)/)
  assert.match(formulasIndexSource, /formula\.links\.restore_path/)
  assert.match(formulasIndexSource, /formula\.links\.delete_path/)
  assert.doesNotMatch(formulasIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|boards\.|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|v-motion|<style|console\.log|alert\(|confirm\(/)
})

test('formula create and edit routes share one controlled calculation worksheet', () => {
  for (const source of [formulasCreateSource, formulasEditSource]) {
    assert.match(source, /<FormulaEditorForm/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|<style|console\.log|alert\(|confirm\(/)
  }

  assert.match(formulasCreateSource, /form\.transform\(/)
  assert.match(formulasCreateSource, /post\(route\('formulas\.store'\)/)
  assert.match(formulasEditSource, /const formula = props\.record\?\.data \?\? props\.record/)
  assert.match(formulasEditSource, /put\(route\('formulas\.update', \{ formula: formula\.id \}\)/)
  assert.match(formulaEditorFormSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(formulaEditorFormSource, /<FormulaDisplay :formula="form\.expression \|\| ''"/)
  assert.match(formulaEditorFormSource, /<ToggleField/)
  assert.match(formulaEditorFormSource, /function synchronizeVariables\(\)/)
  assert.match(formulaEditorFormSource, /function evaluateFormula\(\)/)
  assert.match(formulaEditorFormSource, /if \(!\/\^\[0-9a-zA-Z_/)
  assert.match(formulaEditorFormSource, /class="ds-table-head"/)
  assert.match(formulaEditorFormSource, /const formulaTemplates = \[/)
  assert.doesNotMatch(formulaEditorFormSource, /commercialDocumentThemeClasses|MathInput|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|<style|console\.log|alert\(|confirm\(/)
})

test('formula variables use a compact governed registry and editor', () => {
  assert.match(variablesIndexSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(variablesIndexSource, /<RecordsTable/)
  assert.match(variablesIndexSource, /<SlideOver/)
  assert.match(variablesIndexSource, /<ConfirmDialog/)
  assert.match(variablesIndexSource, /v-model="form\.formula_id"/)
  assert.match(variablesIndexSource, /form\.put\(route\('variables\.update', \{ variableVariable: form\.id \}\)/)
  assert.match(variablesIndexSource, /form\.post\(route\('variables\.store'\)/)
  assert.match(variablesIndexSource, /form\.processing \|\| !form\.isDirty/)
  assert.doesNotMatch(variablesIndexSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|v-motion|<style|console\.log|alert\(|confirm\(/)
})

test('phytosanitary products use the governed reference catalog manager', () => {
  assert.match(phytosanitaryProductsIndexSource, /<ReferenceCatalogManager/)
  assert.match(phytosanitaryProductsIndexSource, /route-prefix="phytosanitary_products"/)
  assert.match(phytosanitaryProductsIndexSource, /permission-key="phytosanitary_products"/)
  assert.match(phytosanitaryProductsIndexSource, /:supports-name="true"/)
  assert.match(phytosanitaryProductsIndexSource, /:supports-code="false"/)
  assert.match(phytosanitaryProductsIndexSource, /openCreate/)
  assert.doesNotMatch(phytosanitaryProductsIndexSource, /commercialDocumentThemeClasses|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('proficiency workflows use compact LIMS monitoring and evidence surfaces', () => {
  for (const source of [proficiencyTestsIndexSource, proficiencyTestsShowSource]) {
    assert.match(source, /class="min-w-0 space-y-6 overflow-x-clip"/)
    assert.match(source, /class="ds-panel overflow-hidden/)
    assert.match(source, /<ChartWrapper/)
    assert.match(source, /class="ds-badge/)
    assert.match(source, /class="ds-field/)
    assert.match(source, /class="ds-button ds-button-primary/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|tracking-\[|bg-white|bg-slate|border-slate|text-slate|shadow-(sm|lg|xl|2xl)|form-field|action-button|<style|console\.log|alert\(|confirm\(/)
  }

  assert.match(proficiencyTestsIndexSource, /class="ds-modal-panel/)
  assert.match(proficiencyTestsIndexSource, /class="ds-table-row"/)
  assert.match(proficiencyTestsIndexSource, /form\.post\(route\('proficiency_tests\.store'/)
  assert.match(proficiencyTestsShowSource, /class="ds-table-shell mt-4 overflow-x-auto"/)
  assert.match(proficiencyTestsShowSource, /form\.put\(route\('proficiency_tests\.results\.update'/)
  assert.match(proficiencyTestsShowSource, /importForm\.post\(route\('proficiency_tests\.results\.import'/)
})

test('report studio and document manager use focused specialist workspaces', () => {
  for (const source of [reportStudiosIndexSource, fileManagerIndexSource]) {
    assert.match(source, /class="min-w-0 space-y-6 overflow-x-clip"/)
    assert.match(source, /class="ds-panel overflow-hidden/)
    assert.match(source, /class="ds-kicker/)
    assert.match(source, /class="ds-heading/)
    assert.match(source, /class="ds-button ds-button-primary|class="ds-button ds-button-secondary/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|tracking-\[|bg-white|bg-slate|border-slate|text-slate|shadow-(lg|xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(reportStudiosIndexSource, /<report-studio-workbench/)
  assert.match(reportStudiosIndexSource, /const studioWorkspaceView = ref/)
  assert.match(reportStudiosIndexSource, /const filteredTemplates = computed/)
  assert.match(reportStudiosIndexSource, /v-if="studioWorkspaceView === 'library'"/)
  assert.match(reportStudiosIndexSource, /aria-label="Áreas do estúdio documental"/)
  assert.match(reportStudiosIndexSource, /form\.delete\(route\('report-studios\.destroy'/)
  assert.match(fileManagerIndexSource, /<FileList/)
  assert.match(fileManagerIndexSource, /<DocumentCompliancePanel/)
  assert.match(fileManagerIndexSource, /<WorkflowPanel/)
  assert.match(fileManagerIndexSource, /class="ds-slideover-panel/)
  assert.match(fileManagerIndexSource, /data-testid="document-manager-overview"/)
  assert.match(fileManagerListSource, /data-testid="document-library-toolbar"/)
  assert.match(fileManagerListSource, /data-testid="document-search"/)
  assert.match(fileManagerListSource, /data-testid="upload-files-button"/)
  assert.match(fileManagerListSource, /data-testid="document-register"/)
  assert.match(fileManagerListSource, /class="ds-data-table min-w-full"/)
  assert.match(fileManagerListSource, /role="tablist" aria-label="Estado documental"/)
  assert.doesNotMatch(fileManagerListSource, /bg-gradient-to-r from-blue-900 to-blue-800|rounded-\[2rem\]/)
  assert.match(fileManagerStoreSource, /files\.value = loadedFiles\.map\(\(file\) => mapFileRecord\(file\)\)\s+filesLoaded\.value = true/)
  assert.match(fileManagerStoreSource, /notifications\.folder_created/)
})

test('trade certificate indexes share one controlled border-workflow register', () => {
  assert.match(importCertificatesIndexSource, /<TradeCertificateRegister v-bind="props" kind="import"/)
  assert.match(exportCertificatesIndexSource, /<TradeCertificateRegister v-bind="props" kind="export"/)

  for (const source of [importCertificatesIndexSource, exportCertificatesIndexSource, tradeCertificateRegisterSource]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|tracking-\[|bg-white|bg-slate|border-slate|text-slate|shadow-(lg|xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(tradeCertificateRegisterSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(tradeCertificateRegisterSource, /class="ds-panel overflow-hidden/)
  assert.match(tradeCertificateRegisterSource, /<RecordsTable/)
  assert.match(tradeCertificateRegisterSource, /<ConfirmDialog/)
  assert.match(tradeCertificateRegisterSource, /hasPermission\(`add_\$\{config\.permissionKey\}`\)/)
  assert.match(tradeCertificateRegisterSource, /route\(`\$\{config\.value\.routePrefix\}\.\$\{selectedAction\.value\}`\)/)
})

test('trade certificate details share one governed lifecycle surface', () => {
  assert.match(importCertificatesShowSource, /<TradeCertificateDetail kind="import" :record="record"/)
  assert.match(exportCertificatesShowSource, /<TradeCertificateDetail kind="export" :record="record"/)

  for (const source of [importCertificatesShowSource, exportCertificatesShowSource, tradeCertificateDetailSource]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|tracking-\[|bg-white|bg-slate|border-slate|text-slate|shadow-(lg|xl|2xl)|console\.log|alert\(|confirm\(|send-email|duplicate/)
  }

  assert.match(tradeCertificateDetailSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(tradeCertificateDetailSource, /Partes e percurso/)
  assert.match(tradeCertificateDetailSource, /Produtos certificados/)
  assert.match(tradeCertificateDetailSource, /Ciclo documental/)
  assert.match(tradeCertificateDetailSource, /hasPermission\(`edit_\$\{config\.value\.permissionKey\}`\)/)
  assert.match(tradeCertificateDetailSource, /route\(`\$\{config\.value\.routePrefix\}\.getPDF`/)
  assert.match(tradeCertificateDetailSource, /route\(`\$\{config\.value\.routePrefix\}\.getIssueInvoiceModal`/)
})

test('trade certificate editors share one sectioned border-control workflow', () => {
  assert.match(importCertificatesCreateSource, /<TradeCertificateForm kind="import"/)
  assert.match(importCertificatesEditSource, /<TradeCertificateForm kind="import" :record="record"/)
  assert.match(exportCertificatesCreateSource, /<TradeCertificateForm kind="export"/)
  assert.match(exportCertificatesEditSource, /<TradeCertificateForm kind="export" :record="record"/)

  for (const source of [
    importCertificatesCreateSource,
    importCertificatesEditSource,
    exportCertificatesCreateSource,
    exportCertificatesEditSource,
    tradeCertificateFormSource,
  ]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|tracking-\[|bg-white|bg-slate|border-slate|text-slate|shadow-(lg|xl|2xl)|console\.log|alert\(|confirm\(|<style/)
  }

  assert.match(tradeCertificateFormSource, /class="min-w-0 space-y-6 overflow-x-clip"/)
  assert.match(tradeCertificateFormSource, /Identificação e responsabilidade/)
  assert.match(tradeCertificateFormSource, /Partes comerciais/)
  assert.match(tradeCertificateFormSource, /Percurso logístico/)
  assert.match(tradeCertificateFormSource, /Produtos certificados/)
  assert.match(tradeCertificateFormSource, /form\.transform/)
  assert.match(tradeCertificateFormSource, /form\.put\(route/)
  assert.match(tradeCertificateFormSource, /form\.post\(route/)
  assert.doesNotMatch(tradeCertificateFormSource, /type="file"/)
})

test('vehicle registry uses a fleet-focused operational drawer', () => {
  assert.match(vehiclesIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(vehiclesIndexSource, /<RecordsTable/)
  assert.match(vehiclesIndexSource, /<SlideOver/)
  assert.match(vehiclesIndexSource, /:load-options="loadCategories"/)
  assert.match(vehiclesIndexSource, /:load-options="loadDepartments"/)
  assert.match(vehiclesIndexSource, /form\.processing \|\| !form\.isDirty/)
  assert.match(vehiclesIndexSource, /<ConfirmDialog/)
  assert.doesNotMatch(vehiclesIndexSource, /commercialDocumentThemeClasses|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
})

test('knowledge-base questions and answers share one governed content register', () => {
  assert.match(faqsIndexSource, /<KnowledgeRegistry kind="question"/)
  assert.match(faqAnswersIndexSource, /<KnowledgeRegistry kind="answer"/)

  for (const source of [faqsIndexSource, faqAnswersIndexSource, knowledgeRegistrySource]) {
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|TransitionRoot|showDeleteConfirmationSlideover|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(knowledgeRegistrySource, /class="ds-panel overflow-hidden/)
  assert.match(knowledgeRegistrySource, /<RecordsTable/)
  assert.match(knowledgeRegistrySource, /<SlideOver/)
  assert.match(knowledgeRegistrySource, /v-model="form\[config\.parentField\]"/)
  assert.match(knowledgeRegistrySource, /:load-options="loadParents"/)
  assert.match(knowledgeRegistrySource, /form\.processing \|\| !form\.isDirty/)
  assert.match(knowledgeRegistrySource, /<ConfirmDialog/)
})

test('analytical products share one matrix, pricing, and tax workflow', () => {
  for (const source of [productsIndexSource, productsCreateSource, productsEditSource, productFormSource]) {
    assert.match(source, /ds-|<ProductForm/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|v-motion|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(productsIndexSource, /<RecordsTable/)
  assert.match(productsIndexSource, /<ConfirmDialog/)
  assert.match(productsCreateSource, /form\.post\(route\("products\.store"\)/)
  assert.match(productsEditSource, /form\.put\(route\("products\.update"/)
  assert.match(productFormSource, /<ToggleField id="product-charge-tax"/)
  assert.match(productFormSource, /<ToggleField id="product-withhold-tax"/)
  assert.match(productFormSource, /:load-options="loadMatrices"/)
  assert.match(productFormSource, /:load-options="loadExemptions"/)
  assert.match(productFormSource, /:load-options="loadTaxTypes"/)
  assert.match(productFormSource, /form\.processing \|\| !form\.isDirty/)
})

test('QMS compliance pages use application UI surfaces instead of legacy cards', () => {
  assert.match(qmsIndexSource, /class="ds-panel overflow-hidden/)
  assert.match(qmsIndexSource, /const priorityCards = computed/)
  assert.match(qmsIndexSource, /class="ds-card p-5/)
  assert.match(qmsIndexSource, /class="ds-button ds-button-primary/)
  assert.doesNotMatch(qmsIndexSource, /bg-gradient-to|rounded-3xl|rounded-\[2rem\]|bg-white\/10/)

  assert.match(responsibilitiesIndexSource, /class="ds-panel px-5 py-5/)
  assert.match(responsibilitiesIndexSource, /class="ds-card space-y-4 p-5/)
  assert.match(responsibilitiesIndexSource, /class="ds-field"/)
  assert.match(responsibilitiesIndexSource, /class="ds-checkbox"/)
  assert.match(responsibilitiesIndexSource, /class="ds-button ds-button-danger/)
  assert.doesNotMatch(responsibilitiesIndexSource, /commercialDocumentThemeClasses|rounded-3xl|rounded-2xl|bg-white p-6|bg-cyan-700/)

  assert.match(uncertaintySourcesIndexSource, /class="ds-panel px-5 py-5/)
  assert.match(uncertaintySourcesIndexSource, /class="ds-card space-y-4 p-5/)
  assert.match(uncertaintySourcesIndexSource, /class="ds-field"/)
  assert.match(uncertaintySourcesIndexSource, /class="ds-checkbox"/)
  assert.match(uncertaintySourcesIndexSource, /class="ds-button ds-button-danger/)
  assert.doesNotMatch(uncertaintySourcesIndexSource, /commercialDocumentThemeClasses|rounded-3xl|rounded-2xl|bg-white p-6|bg-cyan-700/)
})

test('inventory balance and movement pages use controlled LIMS register patterns', () => {
  for (const source of [inventoryIndexSource, inventoryShowSource, inventoryTransactionsIndexSource, inventoryTransactionsShowSource]) {
    assert.match(source, /ds-panel/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-blue-900|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-(xl|2xl)|console\.log|alert\(|confirm\(/)
  }

  assert.match(inventoryIndexSource, /<RecordsTable/)
  assert.match(inventoryIndexSource, /<SlideOver/)
  assert.match(inventoryIndexSource, /route\('vap-inventory\.items\.show'/)
  assert.match(inventoryIndexSource, /class="ds-field"/)
  assert.match(inventoryIndexSource, /class="ds-button ds-button-primary"/)

  assert.match(inventoryShowSource, /const stockStatus = computed/)
  assert.match(inventoryShowSource, /route\('vap-inventory\.items\.show'/)
  assert.doesNotMatch(inventoryShowSource, /inventory\.(increment|decrement)/)

  assert.match(inventoryTransactionsIndexSource, /model="immutable_itransactions"/)
  assert.match(inventoryTransactionsIndexSource, /:create-action="false"/)
  assert.match(inventoryTransactionsIndexSource, /route\('vap-inventory\.reports\.stock-movement'/)
  assert.doesNotMatch(inventoryTransactionsIndexSource, /useForm|form\.(post|put)|itransactions\.(store|update|destroy|restore)/)

  assert.match(inventoryTransactionsShowSource, /Cadeia de custódia/)
  assert.match(inventoryTransactionsShowSource, /formatDate/)
})

test('notification administration uses one traceable operational workflow', () => {
  const notificationSources = [
    notificationAdminHeaderSource,
    notificationDashboardSource,
    notificationIndexSource,
    notificationCreateSource,
    notificationShowSource,
    notificationAnalyticsSource,
  ]

  for (const source of notificationSources) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|from-gray-50|to-gray-100|border-gray-300|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
  }

  assert.match(notificationAdminHeaderSource, /ds-settings-tab/)
  assert.match(notificationDashboardSource, /Últimas mensagens emitidas/)
  assert.match(notificationIndexSource, /route\('notifications\.read'/)
  assert.match(notificationIndexSource, /route\('notifications\.unread'/)
  assert.match(notificationIndexSource, /<Pagination v-bind="notifications"/)
  assert.match(notificationCreateSource, /form\.post\(route\('admin\.notifications\.store'/)
  assert.match(notificationCreateSource, /Pré-visualização/)
  assert.doesNotMatch(notificationCreateSource, /schedule_send = !form\.schedule_send/)
  assert.match(notificationShowSource, /<ConfirmDialog/)
  assert.match(notificationShowSource, /route\('notifications\.delete'/)
  assert.match(notificationAnalyticsSource, /Emissão e leitura por dia/)
  assert.match(notificationPresentationSource, /normalizeNotificationType/)
})

test('personal notifications use a compact operational inbox', () => {
  assert.match(personalNotificationInboxSource, /ds-/)
  assert.match(personalNotificationInboxSource, /<ConfirmDialog/)
  assert.match(personalNotificationInboxSource, /route\('notifications\.read-all'/)
  assert.match(personalNotificationInboxSource, /route\('notifications\.clear-read'/)
  assert.match(personalNotificationInboxSource, /route\('notifications\.clear-all'/)
  assert.match(personalNotificationInboxSource, /<Pagination v-if="pagination\.last_page > 1" v-bind="pagination"/)
  assert.doesNotMatch(personalNotificationInboxSource, /notification-settings|ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.open/)
})

test('Fortify authentication flows share a focused application shell', () => {
  const authenticationSources = [
    authExperienceShellSource,
    authRegisterSource,
    authForgotPasswordSource,
    authResetPasswordSource,
    authVerifyEmailSource,
    authConfirmPasswordSource,
    authTwoFactorChallengeSource,
  ]

  for (const source of authenticationSources) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(authExperienceShellSource, /buildBrandingCssVariables/)
  assert.match(authExperienceShellSource, /brandLabName/)
  assert.match(authRegisterSource, /form\.post\('\/register'/)
  assert.match(authForgotPasswordSource, /form\.post\('\/forgot-password'/)
  assert.match(authResetPasswordSource, /form\.post\('\/reset-password'/)
  assert.match(authVerifyEmailSource, /form\.post\('\/email\/verification-notification'/)
  assert.match(authConfirmPasswordSource, /form\.post\('\/user\/confirm-password'/)
  assert.match(authTwoFactorChallengeSource, /form\.post\('\/two-factor-challenge'/)
  assert.match(authTwoFactorChallengeSource, /autocomplete="one-time-code"/)
})

test('portal authentication flows preserve the portal guard and shared auth language', () => {
  const portalAuthenticationSources = [portalLoginSource, portalRegisterSource, portalForgotPasswordSource, portalResetPasswordSource, portalVerifyEmailSource, portalConfirmPasswordSource, portalTwoFactorChallengeSource]

  for (const source of portalAuthenticationSources) {
    assert.match(source, /<AuthExperienceShell[\s\S]*mode="portal"/)
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(portalForgotPasswordSource, /route\('portal\.password\.email'/)
  assert.match(portalLoginSource, /route\('portal\.login\.store'/)
  assert.match(portalLoginSource, /route\('portal\.passkeys\.login'/)
  assert.match(portalResetPasswordSource, /route\('portal\.password\.update'/)
  assert.match(portalVerifyEmailSource, /route\('portal\.verification\.send'/)
  assert.match(portalVerifyEmailSource, /route\('portal\.logout'/)
  assert.doesNotMatch(portalVerifyEmailSource, /route\('logout'/)
  assert.match(portalConfirmPasswordSource, /route\('portal\.password\.confirm\.store'/)
  assert.match(portalTwoFactorChallengeSource, /route\('portal\.two-factor\.login\.store'/)
})

test('staff messaging uses a searchable communication register and sectioned forms', () => {
  for (const source of [messagesIndexSource, messagesCreateSource, messagesEditSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(messagesIndexSource, /<Pagination v-bind="record\.meta"/)
  assert.match(messagesIndexSource, /router\.get\(route\('messages\.index'/)
  assert.match(messagesCreateSource, /form\.post\(route\('messages\.store'/)
  assert.match(messagesCreateSource, /accept="\.jpg,\.jpeg,\.png,\.pdf,\.docx,\.mp3,\.wav"/)
  assert.match(messagesEditSource, /_method: 'put'/)
  assert.match(messagesEditSource, /route\('messages\.update'/)
})

test('paid-service catalog shares one tax-aware commercial workflow', () => {
  for (const source of [paidServicesIndexSource, paidServicesCreateSource, paidServicesEditSource, paidServiceFormSource]) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(paidServicesIndexSource, /<RecordsTable/)
  assert.match(paidServicesIndexSource, /<ConfirmDialog/)
  assert.match(paidServicesCreateSource, /<PaidServiceForm :form="form"/)
  assert.match(paidServicesCreateSource, /form\.post\(route\('paidservices\.store'/)
  assert.match(paidServicesEditSource, /form\.put\(route\('paidservices\.update'/)
  assert.match(paidServiceFormSource, /<ToggleField id="paid-service-charge-tax"/)
  assert.match(paidServiceFormSource, /loadExemptions/)
  assert.match(paidServiceFormSource, /loadTaxTypes/)
})

test('boards use a compact operational work-management surface', () => {
  const boardSources = [
    boardsIndexSource,
    boardsShowSource,
    boardNameFormSource,
    cardListSource,
    cardListCreateFormSource,
    cardListItemSource,
    cardListItemCreateFormSource,
    cardListItemModalSource,
  ]

  for (const source of boardSources) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(boardsIndexSource, /router\.get\(route\('boards\.destroy'/)
  assert.match(boardsIndexSource, /<confirm-dialog/)
  assert.match(boardsIndexSource, /id="board-form" class="mx-auto w-full max-w-3xl space-y-6 px-6 py-6 sm:px-8"/)
  assert.match(boardsIndexSource, /<IconPicker[\s\S]*v-model="form\.icon"[\s\S]*@picker-closed="isIconPickerOpen = false"/)
  assert.match(iconPickerSource, /emit\('update:modelValue', iconName\)/)
  assert.match(iconPickerSource, /<Teleport to="body">/)
  assert.match(iconPickerSource, /class="ds-modal-panel/)
  assert.match(iconPickerSource, /@close="closePicker"/)
  assert.match(iconPickerSource, /role="listbox"/)
  assert.doesNotMatch(iconPickerSource, /props\.open|open = false/)
  assert.match(boardsShowSource, /class="ds-command-surface overflow-hidden"/)
  assert.match(boardsShowSource, /<CardList\s+v-for="list in board\.lists"/)
  assert.match(boardsShowSource, /w-\[20\.5rem\]/)
  assert.match(cardListSource, /<VueDraggableNext/)
  assert.match(cardListSource, /handle="\.kanban-card-handle"/)
  assert.match(cardListSource, /ghost-class="opacity-30"/)
  assert.match(cardListSource, /route\('cards\.move'/)
  assert.match(cardListItemSource, /members\.slice\(0, 4\)/)
  assert.match(cardListItemSource, /UserGroupIcon/)
  assert.doesNotMatch(cardListSource, /rotate/)
  assert.match(cardListItemModalSource, /<combobox/)
  assert.match(cardListItemModalSource, /class="ds-checkbox"/)
  assert.match(cardListItemModalSource, /router\.delete\(route\('cards\.destroy'/)
  assert.match(cardListItemModalSource, /<confirm-dialog/)
})

test('legacy source variants and playground artifacts cannot return', () => {
  const legacyFilePattern = /(?:^|[ _-])(?:old|oldest|original|simplified|copy)(?:[ ._-]|$)|(?:Index22|Index_2|Create_|ProposalShow_)\.vue$/i

  for (const directory of ['resources/js/', 'resources/images/', 'resources/views/']) {
    for (const file of collectFiles(new URL(`../../${directory}`, import.meta.url))) {
      const fileName = decodeURIComponent(file.pathname.split('/').at(-1))

      assert.doesNotMatch(fileName, legacyFilePattern, `Legacy source file remains: ${fileName}`)
    }
  }

  assert.equal(existsSync(new URL('../../config/backup_old.php', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Components/tanstack-table.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Components/text-input.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Components/simple-select.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Shared/select-input.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Shared/combo-box.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Shared/multiple-select-input.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Shared/date-picker.vue', import.meta.url)), false)
  assert.equal(existsSync(new URL('../../resources/js/Pages/Welcome.vue', import.meta.url)), false)
})

test('operational reference registries share the governed catalog manager', () => {
  assert.equal(operationalReferenceCatalogSources.length, 15)

  for (const source of operationalReferenceCatalogSources) {
    assert.match(source, /<ReferenceCatalogManager/)
    assert.match(source, /route-prefix=/)
    assert.match(source, /route-parameter=/)
    assert.match(source, /permission-key=/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  }

  assert.match(operationalReferenceCatalogSources[10], /key: 'phone_code'/)
  assert.match(operationalReferenceCatalogSources[13], /key: 'compound_tax'.*type: 'toggle'/)
  assert.match(operationalReferenceCatalogSources[14], /key: 'swap_currency_symbol'.*type: 'toggle'/)
  assert.match(referenceCatalogManagerSource, /props\.extraFields\.map/)
  assert.match(referenceCatalogManagerSource, /<ToggleField/)
})

test('VAP laboratory directory uses organizational registry and dossier surfaces', () => {
  assert.equal(vapLabSources.length, 5)

  for (const [index, source] of vapLabSources.entries()) {
    if (![1, 2].includes(index)) {
      assert.match(source, /ds-/)
    }
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log|window\.location/)
  }

  assert.match(vapLabSources[0], /<Pagination v-if="labs\.data\.length" v-bind="labs"/)
  assert.match(vapLabSources[0], /<ConfirmDialog/)
  assert.match(vapLabSources[1], /<LabForm/)
  assert.match(vapLabSources[2], /<LabForm/)
  assert.match(vapLabSources[3], /lab\.technical_head/)
  assert.match(vapLabSources[4], /<comboboxEnhanced/)
  assert.match(vapLabSources[4], /router\.visit\(route\('vap-labs\.labs\.index'/)
})

test('staff manual uses a compact Tailwind detail-screen structure', () => {
  assert.match(userHelpSource, /ds-/)
  assert.doesNotMatch(userHelpSource, /ModuleHero|ModuleCard|commercialDocumentThemeClasses|bg-gradient-to|radial-gradient|rounded-3xl|rounded-2xl|rounded-\[|from-blue-|from-purple-|bg-white|bg-slate|border-slate|text-slate|shadow-sm|#143d37|#d9b05f|#fffdf7|#ded3bf|v-motion|<style|confirm\(|alert\(|axios|console\.error|console\.log/)
  assert.match(userHelpSource, /lg:grid-cols-\[14rem_minmax\(0,1fr\)\]/)
  assert.match(userHelpSource, /aria-label="Secções do manual"/)
  assert.match(userHelpSource, /divide-y divide-\[var\(--ds-border\)\]/)
  assert.match(userHelpSource, /route\('users\.manual\.pdf'\)/)
  assert.match(userHelpSource, /route\('security'\)/)
  assert.doesNotMatch(userHelpSource, /<main/)
})

test('account settings use the Tailwind Plus divided settings-screen structure', () => {
  const accountSettingsSources = [
    profileSettingsSource,
    settingsSectionSource,
    profileInformationSource,
    profilePasswordSource,
    profileTwoFactorSource,
    passkeyManagementSource,
    profileSessionsSource,
    deleteUserSource,
    confirmsPasswordSource,
    signaturePadSource,
  ]

  for (const source of accountSettingsSources) {
    assert.match(source, /ds-/)
    assert.doesNotMatch(source, /commercialDocumentThemeClasses|bg-gradient-to|rounded-3xl|rounded-2xl|v-motion|<svg|axios|console\.error|console\.log/)
  }

  for (const source of [profileInformationSource, profilePasswordSource, profileTwoFactorSource, profileSessionsSource, deleteUserSource]) {
    assert.doesNotMatch(source, /FormSection|ActionSection/)
  }

  assert.match(profileSettingsSource, /lg:grid-cols-\[13rem_minmax\(0,1fr\)\]/)
  assert.match(profileSettingsSource, /divide-y divide-\[var\(--ds-border\)\]/)
  assert.match(profileSettingsSource, /<SettingsSection/)
  assert.match(settingsSectionSource, /lg:grid-cols-\[minmax\(0,14rem\)_minmax\(0,1fr\)\]/)
  assert.match(profileInformationSource, /router\.post\(route\('verification\.send'/)
  assert.match(profileInformationSource, /route\('users\.setsignature'/)
  assert.match(profilePasswordSource, /route\('user-password\.update'/)
  assert.match(profileTwoFactorSource, /route\('two-factor\.confirm'/)
  assert.match(profileTwoFactorSource, /route\('two-factor\.recovery-codes'/)
  assert.match(passkeyManagementSource, /<ConfirmDialog/)
  assert.match(profileSessionsSource, /route\('other-browser-sessions\.destroy'/)
  assert.match(deleteUserSource, /route\('current-user\.destroy'/)
  assert.match(confirmsPasswordSource, /route\('password\.confirm\.store'/)
  assert.match(signaturePadSource, /<ConfirmDialog/)
  assert.match(signaturePadSource, /<VPerfectSignature/)
  assert.doesNotMatch(signaturePadSource, /alert\(|setTimeout|bg-gradient-to|<style/)
  assert.match(appBladeSource, /meta name="csrf-token"/)
  assert.doesNotMatch(appBladeSource, /family=manrope/)
})

test('public landing presents the LIMS as a professional laboratory control surface', () => {
  assert.equal(existsSync(publicLandingHero), true)
  assert.match(publicLandingSource, /<picture class="[^"]*absolute[^"]*inset-0[^"]*">/)
  assert.match(publicLandingSource, /\/images\/lims-laboratory-hero\.webp/)
  assert.match(publicLandingSource, /O LIMS para operações laboratoriais rastreáveis/)
  assert.match(publicLandingSource, /class="[^"]*operations-readout[^"]*"/)
  assert.match(publicLandingSource, /id="workflow"/)
  assert.match(publicLandingSource, /id="control"/)
  assert.match(publicLandingSource, /id="quality"/)
  assert.match(publicLandingSource, /id="documents"/)
  assert.match(publicLandingSource, /mobileNavigationOpen/)
  assert.match(publicLandingSource, /publicMetrics/)
  assert.match(publicLandingSource, /@heroicons\/vue\/24\/outline/)
  assert.match(publicLandingSource, /@media \(prefers-reduced-motion: reduce\)/)
  assert.doesNotMatch(publicLandingSource, /rounded-\[3rem\]|rounded-\[2\.5rem\]|blur-3xl|laboratory-grid|mask-line|chartPoints/)
})

test('application UI consistently inherits the laboratory workspace typeface', () => {
  assert.match(appCss, /--font-sans: 'IBM Plex Sans'/)
  assert.doesNotMatch(appBladeSource, /family=manrope/)
  assert.match(publicLandingSource, /font-family: var\(--font-sans\)/)
  assert.doesNotMatch(publicLandingSource, /fonts\.bunny\.net\/css\?family=manrope|font-family: "Manrope"/)
  assert.match(appCss, /\.vc-container,[\s\S]*font-family: var\(--font-sans\)/)
})

test('white-label fallbacks remain neutral across the application and generated documents', () => {
  assert.doesNotMatch(appBladeSource, /sncqa_logo/)
  assert.doesNotMatch(appBootstrapSource, /Gestlab V3/)
  assert.doesNotMatch(portalLayoutSource, /sncqa_logo/)
  assert.doesNotMatch(documentBrandLogoSource, /sncqa_logo/)
  assert.match(documentBrandLogoSource, /brandLogoInitials/)

  assert.match(premiumDocumentStyleSource, /app_primary_color/)
  assert.match(premiumDocumentStyleSource, /app_secondary_color/)
  assert.match(premiumDocumentStyleSource, /app_accent_color/)
  assert.match(documentLetterheadSource, /app_client_lab_name/)
  assert.match(documentLetterheadSource, /PDFs\.partials\.brand-logo/)

  assert.doesNotMatch(legacyAnalysisReportSource, /result_id == 4 \|\| 3/)
  assert.doesNotMatch(legacyAnalysisReportNewModelSource, /result_id == 4 \|\| 3/)
  assert.doesNotMatch(legacyAnalysisReportNewModelSource, /sncqa|ao_crest|governo_novo|LABORATÓRIO CENTRAL/)
  assert.match(legacyAnalysisReportNewModelSource, /PDFs\.partials\.document-letterhead/)
  assert.match(legacyAnalysisReportSource, /in_array\(\(int\) \$model->collection->result_id, \[3, 4\], true\)/)
})
