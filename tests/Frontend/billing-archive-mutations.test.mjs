import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { ref } from 'vue'

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
const body = (source, name) => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}`))[1]
const registers = ['Invoices', 'CreditNotes', 'Receipts', 'Quotes']

for (const page of registers) {
  const source = read(`Pages/${page}/Index.vue`)
  test(`${page} freezes confirmed IDs, checks permission, blocks pending work and dispatches archive rather than payment`, () => {
    const archive = { processing: ref(false), submit: (...args) => calls.push(args) }
    const calls = []
    const props = { record: { data: [{ id: 4, selected: true }, { id: 5, selected: false }] } }
    const pendingIDs = ref([])
    const actionId = ref(null)
    const showDeleteConfirmation = ref(false)
    const showPaymentConfirmation = ref(false)
    let allowed = true
    const hasPermission = () => allowed
    const request = new Function('archive', 'hasPermission', 'props', 'pendingIDs', 'actionId', 'showDeleteConfirmation', 'operation', body(source, 'requestArchive'))
    const requestAction = operation => request(archive, hasPermission, props, pendingIDs, actionId, showDeleteConfirmation, operation)
    requestAction('delete')
    assert.deepEqual(pendingIDs.value, [4])
    props.record.data[0].selected = false
    props.record.data[1].selected = true
    assert.deepEqual(pendingIDs.value, [4])
    archive.processing.value = true
    requestAction('restore')
    assert.equal(actionId.value, 'delete')
    archive.processing.value = false
    allowed = false
    requestAction('restore')
    assert.equal(actionId.value, 'delete')
    allowed = true
    const rowAction = new Function('archive', 'hasPermission', 'operation', 'ids', body(source, 'archiveRecord'))
    const archiveRecord = (operation, ids) => rowAction(archive, hasPermission, operation, ids)
    const confirm = new Function('archive', 'showDeleteConfirmation', 'actionId', 'pendingIDs', 'archiveRecord', 'showPaymentConfirmation', body(source, 'confirmAction'))
    confirm(archive, showDeleteConfirmation, actionId, pendingIDs, archiveRecord, showPaymentConfirmation)
    assert.deepEqual(calls, [['delete', [4]]])
    assert.equal(showDeleteConfirmation.value, false)
    assert.equal(showPaymentConfirmation.value, false)
    allowed = false
    archiveRecord('restore', [5])
    assert.equal(calls.length, 1)
    if (page === 'Invoices') {
      actionId.value = 'mark_as_paid'
      confirm(archive, showDeleteConfirmation, actionId, pendingIDs, archiveRecord, showPaymentConfirmation)
      assert.equal(showPaymentConfirmation.value, true)
      assert.equal(calls.length, 1)
    }
    assert.match(source, /:archive-handler="archiveRecord"/)
    assert.match(source, /:action-processing="archive.processing.value"/)
    assert.match(source, /<ArchiveMutationFeedback/)
    assert.doesNotMatch(source, /router\.get\(`\/(invoices|creditnotes|receipts|quotes)\/(destroy|restore)/)
  })
}

test('trade certificate confirmations freeze IDs and route delete to destroy for both kinds', () => {
  const source = read('Components/certificates/TradeCertificateRegister.vue')
  const routes = []
  for (const permissionKey of ['import_certificates', 'export_certificates']) {
    const config = ref({ permissionKey })
    const archive = { processing: ref(false), submit: (...args) => routes.push(args) }
    const hasPermission = () => true
    const selectedRecordIds = ref([9, 10])
    const selectedAction = ref(null)
    const pendingIDs = ref([])
    const showBulkConfirmation = ref(false)
    const prepare = new Function('archive', 'hasPermission', 'config', 'selectedRecordIds', 'pendingIDs', 'selectedAction', 'showBulkConfirmation', 'action', body(source, 'prepareBulkAction'))
    prepare(archive, hasPermission, config, selectedRecordIds, pendingIDs, selectedAction, showBulkConfirmation, 'delete')
    selectedRecordIds.value.push(11)
    const rowAction = new Function('archive', 'hasPermission', 'config', 'operation', 'ids', body(source, 'archiveRecord'))
    const archiveRecord = (operation, ids) => rowAction(archive, hasPermission, config, operation, ids)
    const confirm = new Function('archive', 'showBulkConfirmation', 'selectedAction', 'pendingIDs', 'archiveRecord', body(source, 'executeBulkAction'))
    archive.processing.value = true
    const before = routes.length
    confirm(archive, showBulkConfirmation, selectedAction, pendingIDs, archiveRecord)
    assert.equal(routes.length, before)
    archive.processing.value = false
    confirm(archive, showBulkConfirmation, selectedAction, pendingIDs, archiveRecord)
    assert.deepEqual(routes.at(-1), ['delete', [9, 10]])
    assert.equal(showBulkConfirmation.value, false)
  }
  assert.match(source, /destroyUrl: \(\) => route\(`\$\{config.value.routePrefix\}\.destroy`\)/)
  assert.match(source, /restoreUrl: \(\) => route\(`\$\{config.value.routePrefix\}\.restore`\)/)
})

test('single-row table mutations delegate to the same archive form and respect its pending state', () => {
  const source = read('Components/records-table.vue')
  const calls = []
  const props = { actionProcessing: false, archiveHandler: (...args) => calls.push(args) }
  const isProcessingAction = ref(false)
  const showDeleteConfirmation = ref(true)
  const recordId = ref(7)
  const dispatch = new Function('props', 'isProcessingAction', 'showDeleteConfirmation', 'recordId', 'currentActionId', body(source, 'processAction'))
  props.actionProcessing = true
  dispatch(props, isProcessingAction, showDeleteConfirmation, recordId, 'delete')
  assert.equal(calls.length, 0)
  props.actionProcessing = false
  dispatch(props, isProcessingAction, showDeleteConfirmation, recordId, 'restore')
  assert.deepEqual(calls, [['restore', [7]]])
  assert.equal(showDeleteConfirmation.value, false)
})

test('all affected Vue scripts/templates compile and generated mutation routes use DELETE/PATCH', () => {
  for (const path of [...registers.map(page => `Pages/${page}/Index.vue`), 'Components/certificates/TradeCertificateRegister.vue', 'Components/records-table.vue']) {
    const { descriptor, errors } = parse(read(path))
    assert.deepEqual(errors, [])
    const script = compileScript(descriptor, { id: path })
    const template = compileTemplate({ id: path, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
  }
  const definitions = JSON.parse(read('ziggy.js').match(/const Ziggy = (\{[^\n]*\});/)[1]).routes
  for (const prefix of ['invoices', 'creditnotes', 'receipts', 'quotes', 'importcertificates', 'exportcertificates']) {
    assert.deepEqual(definitions[`${prefix}.destroy`].methods, ['DELETE'])
    assert.deepEqual(definitions[`${prefix}.restore`].methods, ['PATCH'])
  }
})
