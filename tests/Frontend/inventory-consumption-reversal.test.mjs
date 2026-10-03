import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { computed, ref } from 'vue'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
const source = read('Composables/useConsumptionReversal.js')

function state() {
  const calls = []
  let allowed = true
  const form = {
    processing: false,
    post: (url, options) => { calls.push({ url, options }) },
  }
  const body = source.replace(/^import .*\n/gm, '').replace('export function', 'function')
  const create = new Function('ref', 'computed', 'useForm', `${body}; return useConsumptionReversal`)(ref, computed, () => form)
  const reversal = create({ canReverse: () => allowed, reverseUrl: id => `/consumption/${id}/reverse` })
  return { reversal, calls, form, permission: value => { allowed = value } }
}

const record = () => ({ id: 7, reagent_name: 'Reagent A', quantity_used: '0.0001', warehouse: { name: 'Store A' } })

test('changing reagent resets dependent stock choices but preserves the entered technician', () => {
  const page = read('Pages/VAPInventory/Reagents/CreateConsumption.vue')
  const body = page.match(/function onReagentChange\(\) \{([\s\S]*?)\n\}/)[1]
  const form = { reagent_id: 3, warehouse_id: 8, quantity_used: '1.25', used_by: 'Actual technician' }
  new Function('form', body)(form)
  assert.equal(form.warehouse_id, '')
  assert.equal(form.quantity_used, 0.01)
  assert.equal(form.used_by, 'Actual technician')
  form.used_by = ''
  new Function('form', body)(form)
  assert.equal(form.used_by, '')
  const { descriptor, errors } = parse(page)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'create-consumption' })
  const template = compileTemplate({ id: 'create-consumption', filename: 'CreateConsumption.vue', source: descriptor.template.content,
    compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
})

test('intake defaults and resets to the signed-in operator without overriding the server redirect', () => {
  const page = read('Pages/VAPInventory/Reagents/CreateConsumption.vue')
  const initial = page.match(/const form = useForm\((\{[\s\S]*?\n\})\)/)[1]
  const props = { users: [{ id: 23, name: 'Signed-in operator' }], backUrl: '/dashboard' }
  const defaults = new Function('props', `return (${initial})`)(props)
  assert.equal(defaults.used_by, 'Signed-in operator')
  assert.equal(new Function('props', `return (${initial})`)({ users: [] }).used_by, '')

  const calls = []
  const visits = []
  const form = { ...defaults, used_by: 'Actual technician',
    post: (url, options) => calls.push({ url, options }),
    reset: () => Object.assign(form, defaults),
  }
  const confirmation = { value: true }
  const confirm = page.match(/function confirmSubmit\(\) \{([\s\S]*?)\n\}/)[1]
  new Function('form', 'showSubmitConfirmation', 'route', 'router', confirm)(form, confirmation, name => name, { visit: url => visits.push(url) })
  assert.equal(confirmation.value, false)
  assert.equal(calls.length, 1)
  assert.equal(calls[0].url, 'vap-inventory.reagents.consumption.store')
  assert.equal(form.used_by, 'Actual technician')
  assert.equal(calls[0].options.preserveScroll, true)
  calls[0].options.onSuccess()
  assert.equal(form.used_by, 'Signed-in operator')
  assert.deepEqual(visits, [])

  const back = page.match(/function goBack\(\) \{([\s\S]*?)\n\}/)[1]
  const goBack = new Function('props', 'route', 'router', back)
  goBack(props, name => name, { visit: url => visits.push(url) })
  goBack({ backUrl: '' }, name => name, { visit: url => visits.push(url) })
  assert.deepEqual(visits, ['/dashboard', 'dashboard'])
})

test('confirmation freezes identity, amount and location and rejects replacement intent', () => {
  const { reversal } = state()
  const current = record()
  assert.equal(reversal.open(current), true)
  current.id = 9
  current.quantity_used = '99.0000'
  current.warehouse.name = 'Changed'
  assert.equal(reversal.pending.value.id, 7)
  assert.equal(reversal.pending.value.quantity_used, '0.0001')
  assert.equal(reversal.pending.value.warehouse.name, 'Store A')
  assert.equal(reversal.open(record()), false)
})

test('POST dispatches only frozen route identity; pending confirmation survives until success', () => {
  const { reversal, calls } = state()
  reversal.open(record())
  assert.equal(reversal.confirm(), true)
  assert.equal(calls[0].url, '/consumption/7/reverse')
  assert.equal(reversal.processing.value, true)
  assert.equal(reversal.confirm(), false)
  assert.equal(reversal.close(), false)
  assert.equal(reversal.pending.value.id, 7)
  calls[0].options.onSuccess()
  calls[0].options.onFinish()
  assert.equal(reversal.pending.value, null)
  assert.equal(reversal.processing.value, false)
})

test('revoked permissions and already-reversed records cannot open or submit confirmation', () => {
  const { reversal, calls, permission } = state()
  assert.equal(reversal.open({ ...record(), reversal: { id: 4 } }), false)
  permission(false)
  assert.equal(reversal.open(record()), false)
  permission(true)
  reversal.open(record())
  permission(false)
  assert.equal(reversal.confirm(), false)
  assert.equal(calls.length, 0)
})

for (const failure of ['validation', 'http', 'network', 'cancel']) {
  test(`${failure} failure keeps intent and exposes persistent feedback`, () => {
    const { reversal, calls } = state()
    reversal.open(record())
    reversal.confirm()
    const { options } = calls[0]
    if (failure === 'validation') options.onError({ consumption: 'Ledger mismatch' })
    if (failure === 'http') assert.equal(options.onHttpException({ status: 403 }), false)
    if (failure === 'network') assert.equal(options.onNetworkError(), false)
    if (failure === 'cancel') options.onCancel()
    options.onFinish()
    assert.equal(reversal.pending.value.id, 7)
    assert.ok(reversal.error.value)
    assert.equal(reversal.processing.value, false)
  })
}

test('synchronous submission failure resets pending protection without losing intent', () => {
  const { reversal, form } = state()
  form.post = () => { throw new Error('Cannot start') }
  reversal.open(record())
  assert.equal(reversal.confirm(), false)
  assert.equal(reversal.processing.value, false)
  assert.equal(reversal.pending.value.id, 7)
  assert.ok(reversal.error.value)
})

for (const name of ['Consumption', 'ShowConsumption']) {
  test(`${name} compiles and exposes retained state, guarded confirmation and four-decimal quantities`, () => {
    const page = read(`Pages/VAPInventory/Reagents/${name}.vue`)
    const { descriptor, errors } = parse(page)
    assert.deepEqual(errors, [])
    const script = compileScript(descriptor, { id: name })
    const template = compileTemplate({ id: name, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
    assert.match(page, /keep-open-on-confirm/)
    assert.match(page, /:disabled="reversal.processing.value"/)
    assert.match(page, /role="alert"/)
    assert.match(page, /hasPermission\('delete_reagent_consumption'\) && !consumption.reversal/)
    assert.match(page, /Revertido/)
    assert.match(page, /maximumFractionDigits: 4/)
    if (name === 'ShowConsumption') {
      assert.match(page, /v-if="consumption.item && !consumption.item.is_archived"/)
      assert.match(page, /Reagente arquivado\. Identificação preservada neste registo\./)
    }
    assert.doesNotMatch(page, /router\.delete|consumption\.destroy|Eliminar registo/)
  })
}

test('item detail and intake preserve consumption permissions, reversal state and fractional quantities', () => {
  for (const path of ['Pages/VAPInventory/Items/Show.vue', 'Pages/VAPInventory/Reagents/CreateConsumption.vue']) {
    const page = read(path)
    const { descriptor, errors } = parse(page)
    assert.deepEqual(errors, [])
    const script = compileScript(descriptor, { id: path })
    const template = compileTemplate({ id: path, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
  }
  const detail = read('Pages/VAPInventory/Items/Show.vue')
  assert.match(detail, /hasPermission\('add_reagent_consumption'\) && \(item.is_reagent \|\| isReagent\)/)
  assert.match(detail, /consumption.reversal \? `Consumo revertido:/)
  assert.match(detail, /type === 'consumption_reversal'/)
  assert.match(read('Pages/VAPInventory/Reagents/CreateConsumption.vue'), /maximumFractionDigits: 4/)
  assert.match(read('Pages/VAPInventory/Reagents/ShowConsumption.vue'), /ArrowUturnLeftIcon/)
})

test('generated named route exposes POST reversal and no destructive consumption route', () => {
  const ziggy = JSON.parse(read('ziggy.js').match(/^const Ziggy = (.*);$/m)[1])
  assert.equal(ziggy.routes['vap-inventory.reagents.consumption.reverse'].uri, 'vap-inventory/reagents/consumption/{consumption}/reverse')
  assert.deepEqual(ziggy.routes['vap-inventory.reagents.consumption.reverse'].methods, ['POST'])
  assert.equal(ziggy.routes['vap-inventory.reagents.consumption.destroy'], undefined)
})

test('consumption pages use the existing permission composable export', () => {
  assert.match(read('Composables/usePermissions.js'), /export function usePermission\(\)/)
  for (const path of ['Pages/VAPInventory/Items/Show.vue', 'Pages/VAPInventory/Reagents/Consumption.vue', 'Pages/VAPInventory/Reagents/ShowConsumption.vue']) {
    const page = read(path)
    assert.match(page, /import \{ usePermission \} from '@\/Composables\/usePermissions'/)
    assert.match(page, /const \{ hasPermission \} = usePermission\(\)/)
    assert.doesNotMatch(page, /\busePermissions\(/)
  }
})

test('stock movement presents reversal as an inbound movement and consumption as outbound', () => {
  const page = read('Pages/VAPInventory/Reports/StockMovement.vue')
  const { descriptor, errors } = parse(page)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'stock-movement' })
  assert.deepEqual(compileTemplate({ id: 'stock-movement', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } }).errors, [])
  const inbound = page.match(/const inboundCodes = (\[[^\n]+\])/)[1]
  const outbound = page.match(/const outboundCodes = (\[[^\n]+\])/)[1]
  const label = page.match(/function quantityLabel\(transaction\) \{[\s\S]*?\n\}/)[0]
  const format = page.match(/function formatQuantity\(value\) \{[\s\S]*?\n\}/)[0]
  const render = new Function(`const inboundCodes = ${inbound}; const outboundCodes = ${outbound}; ${format}; ${label}; return quantityLabel`)()
  assert.equal(render({ type: { code: 'consumption_reversal' }, qty: '0.0001' }), '+0,0001')
  assert.equal(render({ type: { code: 'consumption' }, qty: '-0.0001' }), '-0,0001')
})
