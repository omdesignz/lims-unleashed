import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'
import { networkStockFilters, formatNetworkQuantity, networkAvailabilityLabel } from '../../resources/js/Support/networkStock.js'

const source = readFileSync(new URL('../../resources/js/Pages/LabNetwork/Index.vue', import.meta.url), 'utf8')

test('network quantity formatting preserves all four decimal places at database limits', () => {
  assert.equal(formatNetworkQuantity('99999999999999.9999').replace(/\s/g, ''), '99999999999999,9999')
  assert.equal(formatNetworkQuantity('0.0001'), '0,0001')
  assert.equal(formatNetworkQuantity('12.5000'), '12,5')
  assert.equal(formatNetworkQuantity('0.0000'), '0')
})

test('network filters normalize URL booleans and reset dependent warehouse choices', () => {
  assert.equal(networkStockFilters({ available: '0' }).available, false)
  assert.equal(networkStockFilters({ available: '1' }).available, true)
  assert.equal(networkStockFilters({ lot: '0' }).lot, '0')
  const form = { lab_id: 1, warehouse_id: 9 }
  const body = source.match(/function changeLab\(value\) \{([\s\S]*?)\n\}/)[1]
  new Function('form', 'value', body)(form, 2)
  assert.deepEqual(form, { lab_id: 2, warehouse_id: '' })
})

test('availability labels use server state and never infer expiry from browser clock', () => {
  assert.equal(networkAvailabilityLabel({ availability_state: 'expired', available_quantity: '0.0000' }), 'Expirado')
  assert.equal(networkAvailabilityLabel({ availability_state: 'available', available_quantity: '0.0001' }), 'Disponível')
  assert.equal(networkAvailabilityLabel({ availability_state: 'available', available_quantity: '0.0000' }), 'Sem saldo')
  assert.equal(networkAvailabilityLabel({ availability_state: 'inconsistent' }), 'Saldo por reconciliar')
  assert.doesNotMatch(source, /new Date\(\)\.toISOString/)
})

test('network search prevents duplicate submissions and resets stale details before navigation', () => {
  const calls = []
  const selectedId = { value: 'old-peer-row' }
  const form = { processing: true, transform: () => ({ get: (...args) => calls.push(args) }) }
  const body = source.match(/function search\(\) \{([\s\S]*?)\n\}/)[1]
  const invoke = () => new Function('form', 'selectedId', 'props', 'route', body)(form, selectedId, { network: { id: 3 } }, name => name)
  invoke()
  assert.equal(calls.length, 0)
  form.processing = false
  invoke()
  assert.equal(calls.length, 1)
  assert.equal(selectedId.value, null)
  assert.match(source, /props\.stock\.data\.find/)
  assert.match(source, /props\.filters, page: undefined/)
})

test('network page compiles as one root and exposes labeled filters and read-only detail', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
  const script = compileScript(descriptor, { id: 'network-stock' })
  const template = compileTemplate({ source: descriptor.template.content, filename: 'Index.vue', id: 'network-stock', compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  for (const label of ['Material ou código', 'Laboratório', 'Armazém', 'Lote', 'Validade desde', 'Validade até']) assert.ok(source.includes('label="' + label + '"'))
  assert.match(source, /aria-busy="form.processing"/)
  assert.match(source, /role="status"/)
  assert.doesNotMatch(source, /router\.(post|put|patch|delete)|form\.(post|put|patch|delete)|items\.show|attachments/)
})

test('material details move focus after rendering and restore it when closed', async () => {
  const events = []
  const selectedId = { value: null }
  const detailPanel = { value: { focus: () => events.push('detail') } }
  const trigger = { focus: () => events.push('trigger') }
  const toggle = source.match(/async function toggleDetails\(id, event\) \{([\s\S]*?)\n\}/)[1]
  const close = source.match(/function closeDetails\(\) \{([\s\S]*?)\n\}/)[1]
  const factory = new Function('selectedId', 'detailPanel', 'nextTick', `let detailTrigger; function closeDetails() {${close}}; return { closeDetails, toggleDetails: async function(id,event) {${toggle}} }`)
  const actions = factory(selectedId, detailPanel, async () => events.push('render'))
  await actions.toggleDetails('lot-1', { currentTarget: trigger })
  assert.deepEqual(events, ['render', 'detail'])
  actions.closeDetails()
  assert.equal(selectedId.value, null)
  assert.deepEqual(events, ['render', 'detail', 'trigger'])
  assert.match(source, /@keydown\.esc\.stop\.prevent="closeDetails"/)
})
