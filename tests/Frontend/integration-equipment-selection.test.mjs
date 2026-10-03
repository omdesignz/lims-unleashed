import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { computed, ref } from 'vue'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'

const source = readFileSync(new URL('../../resources/js/Pages/Integrations/Index.vue', import.meta.url), 'utf8')

function editor() {
  const props = {
    equipmentOptions: [{ value: 10, label: 'Active instrument', meta: 'EQ-10' }],
    connectors: [
      { uuid: 'retained-a', name: 'Connector A', equipment: { id: 20, name: 'Retained A', code: 'EQ-20', serial_number: 'SER-20', is_archived: true } },
      { uuid: 'retained-b', name: 'Connector B', equipment: { id: 30, name: 'Retained B', code: 'EQ-30', is_archived: true } },
      { uuid: 'active', name: 'Connector active', equipment: { id: 10, name: 'Active instrument', code: 'EQ-10', is_archived: false } },
      { uuid: 'unavailable', name: 'Connector unavailable', equipment: null, equipment_link_unavailable: true },
    ],
  }
  const editingConnectorUuid = ref(null)
  const connectorModalOpen = ref(false)
  let defaults = {}
  let transform = data => data
  let submitted
  const connectorForm = {
    inventory_item_id: '',
    defaults: data => { defaults = structuredClone(data) },
    reset: () => Object.assign(connectorForm, structuredClone(defaults)),
    clearErrors() {},
    transform: callback => { transform = callback },
    put: () => { submitted = transform({ inventory_item_id: connectorForm.inventory_item_id, name: connectorForm.name }) },
    post: () => { submitted = transform({ inventory_item_id: connectorForm.inventory_item_id, name: connectorForm.name }) },
  }
  const selectionSource = source.slice(source.indexOf('const equipmentComboboxOptions = computed'), source.indexOf('const mappingForm = useForm'))
  const selection = new Function('props', 'computed', 'editingConnectorUuid', 'connectorForm', `${selectionSource}; return { equipmentComboboxOptions, selectedEquipmentOption, equipmentLinkUnavailable }`)(props, computed, editingConnectorUuid, connectorForm)
  const body = source.match(/function openEditConnector\(connector\) \{([\s\S]*?)\n\}/)[1]
  const openEdit = new Function('connector', 'editingConnectorUuid', 'connectorForm', 'connectorModalOpen', body)
  const submitBody = source.match(/function submitConnector\(\) \{([\s\S]*?)\n\}/)[1]
  const submit = new Function('connectorForm', 'equipmentLinkUnavailable', 'editingConnectorUuid', 'connectorModalOpen', 'route', submitBody)
  return { props, editingConnectorUuid, connectorForm, connectorModalOpen, ...selection,
    open: connector => openEdit(connector, editingConnectorUuid, connectorForm, connectorModalOpen),
    submit: () => { submit(connectorForm, selection.equipmentLinkUnavailable, editingConnectorUuid, connectorModalOpen, () => '/connector'); return submitted },
  }
}

test('new connectors have only active options; retained options belong only to the edited connector', () => {
  const state = editor()
  assert.deepEqual(state.equipmentComboboxOptions.value, [{ value: 10, label: 'Active instrument · EQ-10' }])
  state.editingConnectorUuid.value = 'retained-a'
  assert.deepEqual(state.equipmentComboboxOptions.value, [
    { value: 10, label: 'Active instrument · EQ-10' },
    { value: 20, label: 'Retained A · EQ-20 · SER-20 · Arquivado' },
  ])
  state.editingConnectorUuid.value = 'retained-b'
  assert.deepEqual(state.equipmentComboboxOptions.value.map(item => item.value), [10, 30])
  state.editingConnectorUuid.value = 'active'
  assert.deepEqual(state.equipmentComboboxOptions.value.map(item => item.value), [10])
  state.editingConnectorUuid.value = null
  assert.deepEqual(state.equipmentComboboxOptions.value.map(item => item.value), [10])
})

test('opening and resetting archived connector metadata keeps its equipment ID and visible label', () => {
  const state = editor()
  state.open(state.props.connectors[0])
  assert.equal(state.connectorModalOpen.value, true)
  assert.equal(state.connectorForm.inventory_item_id, 20)
  assert.equal(state.selectedEquipmentOption.value.value, 20)
  assert.match(state.selectedEquipmentOption.value.label, /Arquivado$/)
  state.connectorForm.inventory_item_id = 10
  state.connectorForm.reset()
  assert.equal(state.connectorForm.inventory_item_id, 20)
  assert.equal(state.selectedEquipmentOption.value.value, 20)
})

test('explicit selection and clearing update the exact equipment field', () => {
  const state = editor()
  state.open(state.props.connectors[0])
  state.selectedEquipmentOption.value = state.equipmentComboboxOptions.value[0]
  assert.equal(state.connectorForm.inventory_item_id, 10)
  state.selectedEquipmentOption.value = null
  assert.equal(state.connectorForm.inventory_item_id, '')
  assert.equal(state.selectedEquipmentOption.value, null)
})

test('unavailable links survive metadata edits without revealing or resubmitting their IDs', () => {
  const state = editor()
  state.open(state.props.connectors[3])
  assert.equal(state.equipmentLinkUnavailable.value, true)
  assert.equal(state.selectedEquipmentOption.value, null)
  assert.deepEqual(state.submit(), { name: 'Connector unavailable' })
  state.selectedEquipmentOption.value = state.equipmentComboboxOptions.value[0]
  assert.deepEqual(state.submit(), { name: 'Connector unavailable', inventory_item_id: 10 })
  state.open(state.props.connectors[2])
  state.selectedEquipmentOption.value = null
  assert.equal(state.equipmentLinkUnavailable.value, false)
  assert.deepEqual(state.submit(), { name: 'Connector active', inventory_item_id: '' })
  state.editingConnectorUuid.value = null
  assert.equal(state.equipmentLinkUnavailable.value, false)
  assert.ok(Object.hasOwn(state.submit(), 'inventory_item_id'))
  assert.match(source, /v-if="equipmentLinkUnavailable"[\s\S]*?Será preservada se não escolher outro equipamento/)
  assert.match(source, /selectedConnector\.equipment_link_unavailable \? 'Associação indisponível' : 'Não associado'/)
})

test('integration page script and template compile with the existing form and combobox contracts', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'integration-equipment' })
  const template = compileTemplate({ id: 'integration-equipment', filename: 'Index.vue', source: descriptor.template.content,
    compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
})
