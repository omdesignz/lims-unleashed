import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { ref } from 'vue'

const source = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Index.vue', import.meta.url), 'utf8')
const body = name => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}`))[1]

function state() {
  const calls = []
  const values = {
    archive: { processing: ref(false), failed: ref(false), message: ref(''), submit: (...args) => calls.push(args) },
    showDeleteModal: ref(false), itemToDelete: ref(null), pendingOperation: ref(null),
    itemRows: ref([{ id: 4, name: 'Material A', can_delete: true, can_restore: false }, { id: 5, name: 'Material B', can_delete: false, can_restore: true }]),
    router: { reload: () => calls.push('reload') },
  }
  function invoke(name, ...args) {
    const parameters = name === 'requestArchive' ? ['item', 'operation'] : []
    return new Function(...Object.keys(values), ...parameters, body(name))(...Object.values(values), ...args)
  }
  values.cancelArchive = () => invoke('cancelArchive')
  return { values, calls, invoke }
}

test('confirmation freezes identity and operation and rejects pending or forbidden replacement', () => {
  const { values, calls, invoke } = state()
  invoke('requestArchive', values.itemRows.value[0], 'delete')
  values.itemRows.value[0].name = 'Renamed after confirmation'
  assert.deepEqual(values.itemToDelete.value, { id: 4, name: 'Material A' })
  invoke('requestArchive', values.itemRows.value[1], 'restore')
  assert.equal(values.pendingOperation.value, 'delete')
  invoke('deleteItem')
  assert.deepEqual(calls, [['delete', [4]]])
  assert.equal(values.showDeleteModal.value, true)
  values.archive.processing.value = true
  invoke('cancelArchive')
  invoke('refreshArchive')
  invoke('deleteItem')
  assert.deepEqual(calls, [['delete', [4]]])
  assert.equal(values.showDeleteModal.value, true)
})

test('current permission is rechecked and refresh clears stale intent before navigation', () => {
  const { values, calls, invoke } = state()
  invoke('requestArchive', values.itemRows.value[0], 'restore')
  assert.equal(values.showDeleteModal.value, false)
  invoke('requestArchive', values.itemRows.value[1], 'restore')
  values.itemRows.value[1].can_restore = false
  invoke('deleteItem')
  assert.deepEqual(calls, [])
  assert.equal(values.archive.failed.value, true)
  assert.match(values.archive.message.value, /Actualize a lista/)
  values.itemRows.value[1].can_restore = true
  invoke('deleteItem')
  assert.deepEqual(calls, [['restore', [5]]])
  invoke('refreshArchive')
  assert.equal(values.showDeleteModal.value, false)
  assert.equal(values.itemToDelete.value, null)
  assert.equal(values.pendingOperation.value, null)
  assert.deepEqual(calls, [['restore', [5]], 'reload'])
})

test('dialog uses its actual props and events and preserves feedback until success', () => {
  assert.match(source, /v-if="showDeleteModal"[\s\S]*keep-open-on-confirm[\s\S]*@canceled="cancelArchive"[\s\S]*@confirmed="deleteItem"/)
  assert.match(source, /:disabled="archive\.processing\.value"/)
  assert.match(source, /<ArchiveMutationFeedback[^>]+:failed="archive\.failed\.value"/)
  assert.doesNotMatch(source, /@confirm="deleteItem"|@close="showDeleteModal|Esta acção não pode ser desfeita|router\.delete/)
  const success = source.match(/onSuccess: \(\) => \{([\s\S]*?)\n  \},/)[1]
  const { values, invoke } = state()
  invoke('requestArchive', values.itemRows.value[0], 'delete')
  new Function(...Object.keys(values), success)(...Object.values(values))
  assert.equal(values.showDeleteModal.value, false)
  assert.equal(values.itemToDelete.value, null)
  assert.equal(values.pendingOperation.value, null)
})

test('active/archived filtering preserves accessible labels and suppresses unsupported archived exports and links', () => {
  assert.match(source, /<select v-model="localFilters\.archive_state"/)
  assert.match(source, /<option value="archived">Itens arquivados<\/option>/)
  // One responsive register table: each guard appears once (export in the page header).
  assert.equal((source.match(/canExport && localFilters\.archive_state !== 'archived'/g) ?? []).length, 1)
  assert.equal((source.match(/v-if="!item\.is_archived"/g) ?? []).length, 1)
  assert.match(source, /<Link v-if="!item\.is_archived" :href="route\('vap-inventory\.items\.show', item\.id\)"/)
  assert.equal((source.match(/v-if="item\.can_restore"/g) ?? []).length, 1)
  assert.match(source, /v-if="item\.can_restore"[^>]*@click="requestArchive\(item, 'restore'\)">\s*Restaurar\s*<\/button>/)
  assert.match(source, /restoreUrl: ids => route\('vap-inventory\.items\.restore', ids\[0\]\)/)
})

test('catalogue script and template compile with the shared archive and confirmation components', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'catalogue-archive' })
  const template = compileTemplate({ id: 'catalogue-archive', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
})
