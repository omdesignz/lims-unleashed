import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'
import { ref } from 'vue'

const read = path => readFileSync(new URL('../../resources/js/' + path, import.meta.url), 'utf8')
const stock = read('Pages/Inventory/Index.vue')
const body = name => stock.match(new RegExp('function ' + name + '\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}'))[1]

test('stock confirmation freezes selection, prevents replacement and submits only that intent', () => {
  const calls = []
  const state = { archiveProcessing: ref(false), form: { processing: false }, rows: ref([{ id: 7, selected: true }, { id: 8, selected: false }]),
    bulkRecordIds: ref([]), selectedAction: ref(null), showActionConfirmation: ref(false),
    submitArchive: (...args) => calls.push(args), closeActionConfirmation: () => {} }
  const invoke = (name, ...args) => new Function(...Object.keys(state), ...(name === 'requestBulkAction' ? ['action'] : []), body(name))(...Object.values(state), ...args)
  invoke('requestBulkAction', 'delete')
  state.rows.value[0].selected = false
  state.rows.value[1].selected = true
  invoke('requestBulkAction', 'restore')
  invoke('executeBulkAction')
  assert.deepEqual(calls, [['delete', [7]]])
  assert.equal(state.showActionConfirmation.value, true)
  state.archiveProcessing.value = true
  invoke('executeBulkAction')
  assert.equal(calls.length, 1)
  assert.match(stock, /:archive-handler="submitArchive"/)
  assert.match(stock, /destroyUrl: \(\) => route\('inventory.destroy'\)/)
  assert.match(stock, /restoreUrl: \(\) => route\('inventory.restore'\)/)
  assert.doesNotMatch(stock, /router\.get\(route\(`inventory/)
})

test('document actions are capability gated, reversible, and share persistent failure/pending protection', () => {
  const show = read('Pages/VAPInventory/Items/Show.vue')
  assert.match(show, /v-if="canEdit && !document.archived"/)
  assert.match(show, /v-if="canEdit && document.archived"/)
  assert.match(show, /:disabled="documentProcessing"/)
  assert.match(show, /restoreUrl: ids => route\('vap-inventory.items.attachments.restore'/)
  assert.match(show, /documentFailed \? 'alert' : 'status'/)
  assert.doesNotMatch(show, /<TrashIcon/)
  const media = read('Pages/Media/Index.vue')
  assert.doesNotMatch(media, /media\.destroy|media\.restore|select-action/)
})

test('changed private-file and stock surfaces compile with single roots', () => {
  for (const path of ['Pages/Inventory/Index.vue', 'Pages/VAPInventory/Items/Show.vue', 'Pages/VAPInventory/Items/Edit.vue', 'Components/vap-inventory/InventoryItemFormSurface.vue', 'Pages/Media/Index.vue']) {
    const source = read(path)
    const { descriptor, errors } = parse(source)
    assert.deepEqual(errors, [], path)
    assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1, path)
    const script = compileScript(descriptor, { id: path })
    const template = compileTemplate({ source: descriptor.template.content, filename: path, id: path,
      compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [], path)
  }
})
