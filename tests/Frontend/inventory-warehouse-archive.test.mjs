import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { ref } from 'vue'

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
const source = read('Pages/InventoryItemWarehouses/Index.vue')
const body = name => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}`))[1]

function state() {
  let allowed = true
  const calls = []
  const values = {
    props: { record: { data: [{ id: 4, selected: true }, { id: 5, selected: false }] } },
    archive: { processing: ref(false), submit: (...args) => calls.push(args) },
    pendingIDs: ref([]), actionId: ref(null), showActionConfirmation: ref(false),
    hasPermission: () => allowed,
  }
  function invoke(name, ...args) {
    const parameters = { requestBulkAction: ['selectedActionId'], archiveRecord: ['operation', 'ids'] }[name] ?? []
    return new Function(...Object.keys(values), ...parameters, body(name))(...Object.values(values), ...args)
  }
  values.archiveRecord = (...args) => invoke('archiveRecord', ...args)
  return { values, calls, invoke, permission: value => { allowed = value } }
}

test('bulk confirmation freezes selection and rejects invalid, forbidden or pending replacements', () => {
  const { values, invoke, permission } = state()
  invoke('requestBulkAction', 'delete')
  assert.deepEqual(values.pendingIDs.value, [4])
  assert.equal(values.showActionConfirmation.value, true)
  values.props.record.data[0].selected = false
  values.props.record.data[1].selected = true
  invoke('requestBulkAction', 'restore')
  assert.deepEqual(values.pendingIDs.value, [4])
  assert.equal(values.actionId.value, 'delete')
  values.archive.processing.value = true
  invoke('requestBulkAction', 'restore')
  invoke('cancelAction')
  assert.equal(values.showActionConfirmation.value, true)
  assert.deepEqual(values.pendingIDs.value, [4])
  values.archive.processing.value = false
  permission(false)
  invoke('requestBulkAction', 'restore')
  permission(true)
  invoke('requestBulkAction', 'other')
  assert.equal(values.actionId.value, 'delete')
  assert.deepEqual(values.pendingIDs.value, [4])
})

test('confirmation dispatches frozen intent and remains available until success', () => {
  const { values, calls, invoke, permission } = state()
  invoke('requestBulkAction', 'restore')
  values.props.record.data[0].selected = false
  values.props.record.data[1].selected = true
  invoke('confirmAction')
  assert.deepEqual(calls, [['restore', [4]]])
  assert.equal(values.showActionConfirmation.value, true)
  assert.deepEqual(values.pendingIDs.value, [4])
  values.archive.processing.value = true
  invoke('confirmAction')
  assert.equal(calls.length, 1)
  values.archive.processing.value = false
  permission(false)
  invoke('confirmAction')
  assert.equal(calls.length, 1)
  permission(true)
  invoke('cancelAction')
  assert.equal(values.showActionConfirmation.value, false)
  assert.deepEqual(values.pendingIDs.value, [])
  assert.equal(values.actionId.value, null)
})

test('row actions use the same permissioned mutation path and successful batches clear selection', () => {
  const { values, calls, invoke, permission } = state()
  invoke('archiveRecord', 'delete', [5])
  assert.deepEqual(calls, [['delete', [5]]])
  permission(false)
  invoke('archiveRecord', 'restore', [5])
  assert.equal(calls.length, 1)
  const success = source.match(/onSuccess: \(\) => \{([\s\S]*?)\n  \},/)[1]
  new Function(...Object.keys(values), success)(...Object.values(values))
  assert.deepEqual(values.props.record.data.map(record => record.selected), [false, false])
  assert.match(source, /:archive-handler="archiveRecord"/)
  assert.match(source, /:action-processing="archive.processing.value"/)
})

test('warehouse scripts and templates compile with persistent disabled confirmation and failure feedback', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'warehouse' })
  const template = compileTemplate({ id: 'warehouse', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /keep-open-on-confirm/)
  assert.match(source, /:disabled="archive.processing.value"/)
  assert.match(source, /<ArchiveMutationFeedback[^>]+:failed="archive.failed.value"/)
  assert.doesNotMatch(source, /router\.get\(route\(routeName\)/)
  assert.doesNotMatch(source, /@close="showActionConfirmation/)
})

test('generated warehouse bulk and nested lifecycle routes use DELETE and PATCH', () => {
  const definitions = JSON.parse(read('ziggy.js').match(/const Ziggy = (\{[^\n]*\});/)[1]).routes
  for (const prefix of ['iwarehouses', 'vap-inventory.master.warehouses']) {
    assert.deepEqual(definitions[`${prefix}.destroy`].methods, ['DELETE'])
    assert.deepEqual(definitions[`${prefix}.restore`].methods, ['PATCH'])
  }
})
