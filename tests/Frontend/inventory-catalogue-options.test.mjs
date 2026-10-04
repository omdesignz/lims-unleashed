import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { http as inertiaHttp, useHttp } from '@inertiajs/vue3'
import { effectScope } from 'vue'
import { createInventoryCatalogueLoader } from '../../resources/js/Composables/useInventoryCatalogueOptions.js'

const paths = [
  'Inventory/Index.vue', 'InventoryOrders/Create.vue', 'InventoryOrders/Edit.vue',
  'InventoryDeliveries/InventoryDeliveryForm.vue', 'VAPMaintenance/Tasks/Create.vue',
  'VAPMaintenance/Tasks/Show.vue', 'VAPMaintenance/Tasks/Index.vue',
]
const read = path => readFileSync(new URL(`../../resources/js/Pages/${path}`, import.meta.url), 'utf8')

for (const path of paths) {
  test(`${path} compiles with canonical catalogue loading`, () => {
    const { descriptor, errors } = parse(read(path))
    assert.deepEqual(errors, [])
    compileScript(descriptor, { id: path })
    const result = compileTemplate({ source: descriptor.template.content, filename: path, id: path })
    assert.deepEqual(result.errors, [])
    if (path.startsWith('VAPMaintenance/')) {
      assert.doesNotMatch(descriptor.scriptSetup.content, /maintenancetasks\.(store|update)/)
    } else {
      assert.match(descriptor.scriptSetup.content, /useInventoryCatalogueOptions/)
    }
    assert.doesNotMatch(descriptor.scriptSetup.content, /\/iitems\/get(?:Reagent)?InventoryItem/)
  })
}

test('canonical maintenance uses server-owned equipment choices while procurement uses the permitted union', () => {
  assert.match(read('VAPMaintenance/Tasks/Create.vue'), /equipment: Array/)
  assert.match(read('VAPMaintenance/Tasks/Index.vue'), /equipment: Array/)
  for (const path of paths.filter(path => path.startsWith('InventoryOrders/') || path === 'Inventory/Index.vue')) {
    assert.match(read(path), /const loadItems = useInventoryCatalogueOptions\(\)/)
  }
})

test('standalone JSON loading uses Inertia v3 and disposes on scope exit', () => {
  const source = readFileSync(new URL('../../resources/js/Composables/useInventoryCatalogueOptions.js', import.meta.url), 'utf8')
  assert.match(source, /import \{ useHttp \} from '@inertiajs\/vue3'/)
  assert.match(source, /onScopeDispose\(loader.dispose\)/)
  assert.match(source, /route\('vap-inventory\.items\.lookup'\)/)
  assert.doesNotMatch(source, /reagentsOnly|getReagentInventoryItem/)
  assert.match(source, /inventory_type: inventoryType/)
})

test('latest successful response retains only the selection contract and trims search', async () => {
  const calls = []
  const http = { cancel() {}, async get(url) { calls.push([url, this.q]); return [{ id: 3, name: 'Balance', code: 'EQ-3', category_id: 7, inventory_type: 'equipment', unit_id: 4, is_reagent: false, obs: 'Do not expose' }] } }
  const loader = createInventoryCatalogueLoader(() => ({ http }), () => '/canonical-lookup', item => `${item.code} · ${item.name}`)
  let options
  await loader.load('  Balance  ', value => { options = value })
  assert.deepEqual(calls, [['/canonical-lookup', 'Balance']])
  assert.deepEqual(options, [{ value: 3, label: 'EQ-3 · Balance', category_id: 7, inventory_type: 'equipment', unit_id: 4, is_reagent: false }])
})

test('empty search clears choices without sending a request', async () => {
  let requests = 0
  const loader = createInventoryCatalogueLoader(() => ({ http: { cancel() {}, get() { requests += 1 } } }), () => '/lookup')
  let options
  await loader.load('   ', value => { options = value })
  assert.deepEqual(options, [])
  assert.equal(requests, 0)
})

test('chooser labels include searched identifiers so local combobox filtering does not hide code matches', async () => {
  const loader = createInventoryCatalogueLoader(() => ({ http: { cancel() {}, async get() { return [{ id: 1, name: 'Balance', code: 'CAT-7', internal_code: 'EQ-3' }, { id: 2, name: 'Balance', code: 'Balance', internal_code: 'Balance' }] } } }), () => '/lookup')
  let options
  await loader.load('EQ-3', rows => { options = rows })
  assert.equal(options[0].label, 'Balance · CAT-7 · EQ-3')
  assert.equal(options[1].label, 'Balance')
})

test('a slower earlier request cannot replace results for the current query', async () => {
  const responses = []
  let cancellations = 0
  const loader = createInventoryCatalogueLoader(() => ({ http: { cancel() { cancellations += 1 }, get() { return new Promise(resolve => responses.push(resolve)) } } }), () => '/lookup')
  const applied = []
  const apply = rows => applied.push(rows)
  const old = loader.load('old', apply)
  const current = loader.load('current', apply)
  responses[1]([{ id: 2, name: 'Current' }])
  await current
  responses[0]([{ id: 1, name: 'Old' }])
  await old
  assert.equal(cancellations, 1)
  assert.equal(applied.length, 1)
  assert.equal(applied[0][0].label, 'Current')
})

test('an old failure cannot clear newer successful choices', async () => {
  const pending = []
  const loader = createInventoryCatalogueLoader(() => ({ http: { cancel() {}, get() { return new Promise((resolve, reject) => pending.push({ resolve, reject })) } } }), () => '/lookup')
  const applied = []
  const apply = rows => applied.push(rows)
  const old = loader.load('old', apply)
  const current = loader.load('current', apply)
  pending[1].resolve([{ id: 2, name: 'Current' }])
  await current
  pending[0].reject(new Error('Cancelled old request'))
  await old
  assert.equal(applied.length, 1)
  assert.equal(applied[0][0].value, 2)
})

test('current failure or invalid payload clears previous choices', async () => {
  for (const get of [async () => { throw new Error('Forbidden') }, async () => ({ unexpected: true })]) {
    const loader = createInventoryCatalogueLoader(() => ({ http: { cancel() {}, get } }), () => '/lookup')
    let options = [{ value: 'stale' }]
    await loader.load('query', rows => { options = rows })
    assert.deepEqual(options, [])
  }
})

test('disposing cancels pending work and prevents callbacks or further requests', async () => {
  let resolve
  let requests = 0
  let cancellations = 0
  const loader = createInventoryCatalogueLoader(() => ({ http: { cancel() { cancellations += 1 }, get() { requests += 1; return new Promise(done => { resolve = done }) } } }), () => '/lookup')
  let applied = 0
  const pending = loader.load('first', () => { applied += 1 })
  loader.dispose()
  resolve([{ id: 1, name: 'Too late' }])
  await pending
  await loader.load('after dispose', () => { applied += 1 })
  assert.equal(applied, 0)
  assert.equal(requests, 1)
  assert.equal(cancellations, 1)
})

test('independent row selectors keep independent requests and disposal stops every pending scope', async () => {
  const entries = []
  const loader = createInventoryCatalogueLoader(() => {
    const entry = { cancelled: false, stopped: false }
    entry.http = { cancel() { entry.cancelled = true }, get() { return new Promise(resolve => { entry.resolve = resolve }) } }
    entry.dispose = () => { entry.stopped = true }
    entries.push(entry)
    return entry
  }, () => '/lookup')
  const rows = [null, null]
  const first = loader.load('first row', options => { rows[0] = options })
  const second = loader.load('second row', options => { rows[1] = options })
  assert.deepEqual(entries.map(entry => entry.cancelled), [false, false])
  entries[1].resolve([{ id: 2, name: 'Second' }])
  await second
  entries[0].resolve([{ id: 1, name: 'First' }])
  await first
  assert.deepEqual(rows.map(options => options[0].value), [1, 2])
  assert.deepEqual(entries.map(entry => entry.stopped), [true, true])
  const pending = loader.load('third row', () => assert.fail('Disposed callback must not run'))
  loader.dispose()
  assert.equal(entries[2].cancelled, true)
  assert.equal(entries[2].stopped, true)
  entries[2].resolve([])
  await pending
})

test('installed Inertia useHttp encodes search and preserves cancellation for a newer request', async () => {
  const client = inertiaHttp.getClient()
  const original = client.request
  const requests = []
  client.request = config => new Promise((resolve, reject) => {
    requests.push(config)
    config.signal.addEventListener('abort', () => {
      const error = new Error('Cancelled test request')
      error.name = 'AbortError'
      reject(error)
    }, { once: true })
  })
  const scopes = []
  const loader = createInventoryCatalogueLoader(() => {
    const scope = effectScope(true)
    scopes.push(scope)
    const http = scope.run(() => useHttp({ q: '', inventory_type: 'equipment' }))
    return { http, dispose: () => scope.stop() }
  }, () => '/vap-inventory/items/lookup')
  let applied = 0
  const apply = () => { applied += 1 }
  try {
    const first = loader.load('old', apply)
    const second = loader.load('  a&b/#  ', apply)
    await first
    assert.equal(requests.length, 2)
    assert.equal(requests[0].signal.aborted, true)
    assert.equal(requests[1].signal.aborted, false)
    const query = new URL(requests[1].url, 'https://example.test').searchParams
    assert.equal(query.get('q'), 'a&b/#')
    assert.equal(query.get('inventory_type'), 'equipment')
    assert.equal(requests[1].method, 'get')
    loader.dispose()
    await second
    assert.equal(requests[1].signal.aborted, true)
    assert.deepEqual(scopes.map(scope => scope.active), [false, false])
    assert.equal(applied, 0)
  } finally {
    loader.dispose()
    client.request = original
  }
})
