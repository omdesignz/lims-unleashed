import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const appCss = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8')
const layoutSource = readFileSync(new URL('../../resources/js/Shared/Layouts/Layout.vue', import.meta.url), 'utf8')
const inputSource = readFileSync(new URL('../../resources/js/Components/base/BaseInput.vue', import.meta.url), 'utf8')
const moduleHeroSource = readFileSync(new URL('../../resources/js/Components/base/ModuleHero.vue', import.meta.url), 'utf8')
const sideNavSource = readFileSync(new URL('../../resources/js/Shared/Navigation/side-nav.vue', import.meta.url), 'utf8')
const recordsTableSource = readFileSync(new URL('../../resources/js/Components/records-table.vue', import.meta.url), 'utf8')
const tanstackTableSource = readFileSync(new URL('../../resources/js/Components/tanstack-table.vue', import.meta.url), 'utf8')
const comboboxSource = readFileSync(new URL('../../resources/js/Components/combobox-enhanced.vue', import.meta.url), 'utf8')
const multipleComboboxSource = readFileSync(new URL('../../resources/js/Components/combobox-multiple.vue', import.meta.url), 'utf8')
const tableMultipleComboboxSource = readFileSync(new URL('../../resources/js/Components/vap-table/combobox-multiple.vue', import.meta.url), 'utf8')
const simpleSelectSource = readFileSync(new URL('../../resources/js/Components/simple-select.vue', import.meta.url), 'utf8')
const breadcrumbsSource = readFileSync(new URL('../../resources/js/Components/breadcrumbs.vue', import.meta.url), 'utf8')
const datePickerSource = readFileSync(new URL('../../resources/js/Components/date-picker-enhanced.vue', import.meta.url), 'utf8')
const chartWrapperSource = readFileSync(new URL('../../resources/js/Components/apex-chart/ChartWrapper.vue', import.meta.url), 'utf8')
const inventoryAnalyticsSource = readFileSync(new URL('../../resources/js/Components/charts/inventory-analytics.vue', import.meta.url), 'utf8')
const labelPrintSettingsSource = readFileSync(new URL('../../resources/js/Components/LabelPrintSettings.vue', import.meta.url), 'utf8')
const backupStatusesSource = readFileSync(new URL('../../resources/js/Components/backup-statuses-list.vue', import.meta.url), 'utf8')
const backupsSource = readFileSync(new URL('../../resources/js/Components/backups.vue', import.meta.url), 'utf8')
const backupRowSource = readFileSync(new URL('../../resources/js/Components/backup-row.vue', import.meta.url), 'utf8')
const backupsPageSource = readFileSync(new URL('../../resources/js/Pages/Backups/Index.vue', import.meta.url), 'utf8')
const vapLabelsIndexSource = readFileSync(new URL('../../resources/js/Pages/VapLabels/Index.vue', import.meta.url), 'utf8')
const vapLabelsCreateSource = readFileSync(new URL('../../resources/js/Pages/VapLabels/Create.vue', import.meta.url), 'utf8')
const vapLabelsShowSource = readFileSync(new URL('../../resources/js/Pages/VapLabels/Show.vue', import.meta.url), 'utf8')
const vapLabelTemplatesIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPLabelTemplates/Index.vue', import.meta.url), 'utf8')
const vapProposalsCreateSource = readFileSync(new URL('../../resources/js/Pages/VAPProposals/Create.vue', import.meta.url), 'utf8')
const vapProposalsEditSource = readFileSync(new URL('../../resources/js/Pages/VAPProposals/Edit.vue', import.meta.url), 'utf8')
const vapProposalTemplatesIndexSource = readFileSync(new URL('../../resources/js/Pages/VAPProposalTemplates/Index.vue', import.meta.url), 'utf8')
const invoicesIndexSource = readFileSync(new URL('../../resources/js/Pages/Invoices/Index.vue', import.meta.url), 'utf8')
const quotesIndexSource = readFileSync(new URL('../../resources/js/Pages/Quotes/Index.vue', import.meta.url), 'utf8')
const creditNotesIndexSource = readFileSync(new URL('../../resources/js/Pages/CreditNotes/Index.vue', import.meta.url), 'utf8')
const receiptsIndexSource = readFileSync(new URL('../../resources/js/Pages/Receipts/Index.vue', import.meta.url), 'utf8')
const invoicesEditSource = readFileSync(new URL('../../resources/js/Pages/Invoices/Edit.vue', import.meta.url), 'utf8')
const quotesEditSource = readFileSync(new URL('../../resources/js/Pages/Quotes/Edit.vue', import.meta.url), 'utf8')
const creditNotesEditSource = readFileSync(new URL('../../resources/js/Pages/CreditNotes/Edit.vue', import.meta.url), 'utf8')
const receiptsEditSource = readFileSync(new URL('../../resources/js/Pages/Receipts/Edit.vue', import.meta.url), 'utf8')
const validationSignatureSource = readFileSync(new URL('../../resources/js/Components/document-validation-signature.vue', import.meta.url), 'utf8')
const validationModalSource = readFileSync(new URL('../../resources/js/Pages/QualityCertificates/validation-modal.vue', import.meta.url), 'utf8')
const progressTrackerSource = readFileSync(new URL('../../resources/js/Components/progress-tracker.vue', import.meta.url), 'utf8')
const staffLoginSource = readFileSync(new URL('../../resources/js/Pages/Auth/Login.vue', import.meta.url), 'utf8')
const portalLoginSource = readFileSync(new URL('../../resources/js/Pages/PortalAuth/Login.vue', import.meta.url), 'utf8')

test('defines a semantic product design contract for shared application surfaces', () => {
  for (const token of [
    '--ds-canvas',
    '--ds-panel',
    '--ds-panel-raised',
    '--ds-border',
    '--ds-text',
    '--ds-focus',
  ]) {
    assert.match(appCss, new RegExp(token))
  }

  for (const className of [
    '.ds-app-canvas',
    '.ds-panel',
    '.ds-card',
    '.ds-field',
    '.ds-button-primary',
    '.ds-modal-panel',
    '.ds-sidebar-panel',
    '.ds-nav-item',
    '.ds-combobox-control',
    '.ds-floating-panel',
    '.ds-table-shell',
    '.ds-pagination',
  ]) {
    assert.match(appCss, new RegExp(className.replace('.', '\\.')))
  }
})

test('shared shell and primitives consume semantic design classes', () => {
  assert.match(layoutSource, /class="lims-app-shell ds-app-canvas"/)
  assert.match(layoutSource, /class="ds-sidebar-panel/)
  assert.doesNotMatch(layoutSource, /bg-gradient-to-b from-primary-500\/12 to-transparent/)
  assert.match(inputSource, /class="ds-field/)
  assert.match(inputSource, /aria-describedby/)
  assert.match(moduleHeroSource, /class="ds-panel/)
  assert.match(sideNavSource, /class="ds-nav-shell"/)
  assert.match(sideNavSource, /'ds-nav-item group'/)
})

test('calendar and reduced-motion behavior are part of the visual contract', () => {
  assert.match(appCss, /\.vc-container,[\s\S]*var\(--ds-panel-raised\)/)
  assert.match(appCss, /@media \(prefers-reduced-motion: reduce\)/)
  assert.doesNotMatch(appCss, /\.form-select\s*\{\s*composes:/)
})

test('high-frequency data controls use the shared visual language', () => {
  assert.match(recordsTableSource, /class="ds-command-surface"/)
  assert.match(recordsTableSource, /class="ds-table-shell"/)
  assert.match(recordsTableSource, /class="ds-field pl-10"/)
  assert.doesNotMatch(recordsTableSource, /color="blue"/)

  assert.match(tanstackTableSource, /class="ds-command-surface/)
  assert.match(tanstackTableSource, /class="ds-table-shell/)
  assert.match(tanstackTableSource, /router\.get\(page\.url/)

  assert.match(comboboxSource, /class="ds-combobox-control/)
  assert.match(comboboxSource, /class="ds-floating-panel/)
  assert.match(multipleComboboxSource, /class="ds-chip"/)
  assert.match(multipleComboboxSource, /class="ds-floating-panel/)
  assert.match(tableMultipleComboboxSource, /class="ds-combobox-control/)
  assert.match(simpleSelectSource, /class="ds-combobox-control/)
  assert.match(simpleSelectSource, /class="ds-option ds-option-compact/)
  assert.match(breadcrumbsSource, /gestlab\.general\.navigation\.breadcrumb/)
  assert.doesNotMatch(breadcrumbsSource, /<span class="sr-only">Home<\/span>/)
  assert.match(datePickerSource, /'ds-field pl-10 pr-10'/)
  assert.doesNotMatch(datePickerSource, /<style>/)
})

test('analytics charts share product surfaces and tolerate partial API payloads', () => {
  assert.match(chartWrapperSource, /class="ds-card overflow-hidden p-3"/)
  assert.match(inventoryAnalyticsSource, /const panelClass = 'ds-panel/)
  assert.match(inventoryAnalyticsSource, /<SimpleSelect/)
  assert.match(inventoryAnalyticsSource, /<DatePickerEnhanced/)
  assert.match(inventoryAnalyticsSource, /const metrics = data\.metrics \?\? \{\}/)
  assert.match(inventoryAnalyticsSource, /Number\(metrics\.reorderAlerts \?\? 0\)/)
  assert.match(inventoryAnalyticsSource, /Array\.isArray\(data\.consumptionHistory\)/)
  assert.doesNotMatch(inventoryAnalyticsSource, /from-blue-900/)
  assert.doesNotMatch(inventoryAnalyticsSource, /from-purple-900/)
})

test('operational settings and backup monitoring use semantic product surfaces', () => {
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

  assert.match(backupsPageSource, /<ModuleHero/)
  assert.match(backupsPageSource, /v-model:active-disk="activeDisk"/)
  assert.match(backupsPageSource, /onBeforeUnmount/)
  assert.match(backupsPageSource, /window\.setInterval\(refreshBackupData, 30 \* 1000\)/)
  assert.match(backupsPageSource, /Array\.isArray\(response\.data\)/)
  assert.doesNotMatch(backupsPageSource, /setModalVisibility/)
  assert.doesNotMatch(backupsPageSource, /bg-gradient-to-r/)
})

test('VAP label index uses shared product components and translated copy', () => {
  assert.match(vapLabelsIndexSource, /<ModuleHero/)
  assert.match(vapLabelsIndexSource, /<BaseInput/)
  assert.match(vapLabelsIndexSource, /<BaseSelect/)
  assert.match(vapLabelsIndexSource, /class="ds-command-surface/)
  assert.match(vapLabelsIndexSource, /class="ds-table-shell"/)
  assert.match(vapLabelsIndexSource, /<confirm-dialog/)
  assert.match(vapLabelsIndexSource, /vap_labels\.index\.filters_title/)
  assert.match(vapLabelsIndexSource, /vap_labels\.index\.gallery_description/)
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
  assert.match(vapProposalTemplatesIndexSource, /router\.delete/)
  assert.doesNotMatch(vapProposalTemplatesIndexSource, /<style scoped>/)
  assert.doesNotMatch(vapProposalTemplatesIndexSource, /v-motion|\$refs|ConfirmationModal/)
  assert.doesNotMatch(vapProposalTemplatesIndexSource, /border-\[#|bg-\[#|text-\[#|bg-gradient-to-r/)
})

test('VAP proposal create screen uses semantic commercial form surfaces', () => {
  assert.match(vapProposalsCreateSource, /class="ds-panel overflow-hidden"/)
  assert.match(vapProposalsCreateSource, /class="ds-card bg-\[var\(--ds-panel-raised\)\] p-4"/)
  assert.match(vapProposalsCreateSource, /class="ds-field"/)
  assert.match(vapProposalsCreateSource, /class="ds-checkbox"/)
  assert.match(vapProposalsCreateSource, /class="ds-table-action/)
  assert.match(vapProposalsCreateSource, /vap_proposals\.form\.save_error/)
  assert.match(vapProposalsCreateSource, /import \{ trans \} from 'laravel-vue-i18n'/)
  assert.doesNotMatch(vapProposalsCreateSource, /<style scoped>|v-motion/)
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
  assert.match(quotesIndexSource, /class="ds-table-action"/)
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
  assert.match(staffLoginSource, /class="hidden flex-col justify-center lg:flex"/)
  assert.match(staffLoginSource, /gestlab\.pages\.login\.internal_area/)
  assert.match(staffLoginSource, /ds-field pl-10/)
  assert.match(staffLoginSource, /copyright', \{ year: currentYear \}/)
  assert.match(portalLoginSource, /class="hidden space-y-10 lg:block"/)
  assert.match(portalLoginSource, /gestlab\.pages\.portal_login\.portal_title/)
  assert.match(portalLoginSource, /class="space-y-4 sm:space-y-6"/)
  assert.match(portalLoginSource, /'ds-field pl-11'/)
  assert.doesNotMatch(portalLoginSource, /v\{\{ \$t\('gestlab\.app\.version'\) \}\}/)
})
