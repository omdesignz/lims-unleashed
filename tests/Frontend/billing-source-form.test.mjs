import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { computed, ref } from 'vue'

const source = readFileSync(new URL('../../resources/js/Components/certificates/TradeCertificateForm.vue', import.meta.url), 'utf8')
const initialize = new Function('source', 'isImport', 'existingItems', 'option', 'emptyItem', 'useForm', `${source.match(/const form = useForm\([\s\S]*?\);/)[0]}\nreturn form`)

for (const kind of ['import', 'export']) {
  test(`${kind} certificate payload excludes billing fields for new and issued records`, () => {
    for (const record of [{}, { id: 1, invoice_id: 2, invoiced: true, obs: 'Keep this' }]) {
      const form = initialize(record, { value: kind === 'import' }, [], () => null, () => ({ qty: 1 }), data => data)
      assert.equal(Object.hasOwn(form, 'invoice_id'), false)
      assert.equal(Object.hasOwn(form, 'invoiced'), false)
      assert.equal(Object.hasOwn(form, 'cert_no'), false)
      assert.equal(form.obs, record.obs || '')
      assert.equal(form.items.length, 1)
    }
  })
}

test('certificate billing state is read-only and the actual Vue component compiles', () => {
  const { descriptor } = parse(source)
  const script = compileScript(descriptor, { id: 'billing-source-form' })
  const template = compileTemplate({ id: 'billing-source-form', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /Estado de facturação/)
  assert.match(source, /Factura associada #\$\{source.invoice_id\}/)
  assert.match(source, /Ainda não facturado/)
  assert.match(source, /vínculo histórico indisponível/)
  assert.match(source, /vínculo financeiro existente é preservado/)
  assert.doesNotMatch(source, /form\.(invoice_id|invoiced)|loadInvoices|CheckboxInput|form\.transform/)
  assert.match(source, /v-model="form.obs"/)
  assert.match(source, /<FinancialObservationForm v-if="observationsOnly"/)
  assert.match(source, /<form v-else/)
})

for (const kind of ['import', 'export']) {
  test(`${kind} certificate submission preserves input, blocks duplicates and never dispatches billed edits`, () => {
    const handler = source.match(/function submit\(\) \{[\s\S]*?\n\}/)[0]
    const initializeSubmit = new Function('form', 'observationsOnly', 'isEditing', 'config', 'source', 'route', `${handler}\nreturn submit`)
    const calls = []
    const form = { processing: false, obs: 'Draft', errors: {}, defaults() {}, reset() {},
      post(url, options) { this.processing = true; calls.push({ url, options }) },
      put(url, options) { this.processing = true; calls.push({ url, options }) },
      setError(field, message) { this.errors[field] = message },
    }
    const locked = { value: false }
    const submit = initializeSubmit(form, locked, { value: false }, { value: { routePrefix: `${kind}certificates` } }, {}, name => name)
    submit()
    submit()
    assert.equal(calls.length, 1)
    assert.equal(calls[0].options.preserveState, true)
    calls[0].options.onNetworkError()
    assert.equal(form.obs, 'Draft')
    assert.match(form.errors.request, /preservados/)
    calls[0].options.onHttpException()
    assert.match(form.errors.request, /Não foi possível guardar/)
    form.processing = false
    locked.value = true
    submit()
    assert.equal(calls.length, 1)
    locked.value = false
    form.post = () => { throw new Error('Dispatch failure') }
    submit()
    assert.match(form.errors.request, /iniciar o pedido/)
    assert.equal(form.obs, 'Draft')
  })
}

test('certificate detail offers only permitted, non-duplicate invoice actions', () => {
  const detail = readFileSync(new URL('../../resources/js/Components/certificates/TradeCertificateDetail.vue', import.meta.url), 'utf8')
  const bindings = detail.match(/const isBilled = computed\([\s\S]*?(?=const status =)/)[0]
  const handler = detail.match(/function openInvoice\(\) \{[\s\S]*?\n\}/)[0]
  const initializeDetail = new Function('certificate', 'config', 'hasPermission', 'computed', 'router', 'route', `${bindings}\n${handler}\nreturn { isBilled, canOpenInvoice, canIssueInvoice, openInvoice }`)
  const cases = [
    [{}, [], false, false],
    [{}, ['add_invoices', 'view_import_certificates'], false, true],
    [{ invoice_id: 7, invoiced: false }, ['add_invoices', 'view_import_certificates'], false, false],
    [{ invoice_id: 7 }, ['view_invoices'], true, false],
    [{ invoiced: true }, ['add_invoices', 'view_import_certificates'], false, false],
    [{ deleted: true }, ['add_invoices', 'view_import_certificates'], false, false],
  ]
  for (const [record, permissions, canOpen, canIssue] of cases) {
    const calls = []
    const state = initializeDetail({ value: { id: 1, ...record } }, { value: { permissionKey: 'import_certificates', routePrefix: 'importcertificates' } }, permission => permissions.includes(permission), computed, { get: (...args) => calls.push(args) }, name => name)
    assert.equal(state.canOpenInvoice.value, canOpen)
    assert.equal(state.canIssueInvoice.value, canIssue)
    state.openInvoice()
    assert.equal(calls.length, canOpen || canIssue ? 1 : 0)
    if (calls.length) assert.equal(calls[0][0], canOpen ? 'invoices.show' : 'importcertificates.getIssueInvoiceModal')
  }
  const { descriptor } = parse(detail)
  const script = compileScript(descriptor, { id: 'billing-source-detail' })
  const template = compileTemplate({ id: 'billing-source-detail', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(detail, /v-if="canOpenInvoice \|\| canIssueInvoice"/)
})

test('quote line correction sends an explicit CL selection, preserves untouched identity and does not submit the parent form', () => {
  const quote = readFileSync(new URL('../../resources/js/Pages/Quotes/Edit.vue', import.meta.url), 'utf8')
  const submit = new Function('item', 'correctingItemId', 'itemCorrectionErrors', 'useForm', 'route', quote.match(/let submitItem = \(item\) => \{([\s\S]*?)\n\}/)[1])
  const calls = []
  const busy = ref(null)
  const errors = ref({})
  const useForm = data => ({ put: (url, options) => calls.push({ data, options }) })
  const item = { item: { id: 4, obs: 'Latest observation', unit_id: null, itemable_type: 'collectionproduct', itemable_id: { value: 2, label: 'Existing CL' } } }
  submit(item, busy, errors, useForm, () => '/line')
  assert.equal(Object.hasOwn(calls[0].data, 'lab_code_id'), false)
  assert.equal(Object.hasOwn(calls[0].data, 'itemable_id'), false)
  assert.equal(calls[0].data.obs, 'Latest observation')
  assert.equal(calls[0].options.preserveState, true)
  submit(item, busy, errors, useForm, () => '/line')
  assert.equal(calls.length, 1)
  calls[0].options.onError({ lab_code_id: 'Unavailable' })
  assert.equal(errors.value.id, 4)
  calls[0].options.onFinish()
  item.item.itemable_id = { value: 2, label: 'Selected CL', lab_code_id: 800 }
  submit(item, busy, errors, useForm, () => '/line')
  assert.equal(calls[1].data.lab_code_id, 800)
  assert.equal(Object.hasOwn(calls[1].data, 'itemable_type'), false)
  calls[1].options.onSuccess({ props: { record: { items: [{ id: 4, itemable_id: { value: 2, label: 'Canonical CL' }, itemable_type: 'collectionproduct' }] } } })
  assert.deepEqual(item.item.itemable_id, { value: 2, label: 'Canonical CL' })
  assert.equal(item.item.itemable_type, 'collectionproduct')
  assert.equal(item.item.obs, 'Latest observation')
  assert.equal(Object.hasOwn(item.item.itemable_id, 'lab_code_id'), false)
  calls[1].options.onNetworkError()
  assert.match(errors.value.errors.request, /Ligação interrompida/)
  calls[1].options.onFinish()
  item.item.itemable_id = null
  submit(item, busy, errors, useForm, () => '/line')
  assert.equal(calls[2].data.lab_code_id, null)
  calls[2].options.onFinish()
  submit(item, busy, errors, () => { throw new Error('Dispatch failed') }, () => '/line')
  assert.equal(busy.value, null)
  assert.match(errors.value.errors.request, /iniciar a correcção/)
  assert.match(quote, /function loadLabCodes[\s\S]*?lab_code_id: result.id/)
  assert.match(quote, /function loadLabCodes[\s\S]*?value: result.collection_id/)
  assert.match(quote, /<button type="button" :disabled="correctingItemId !== null" @click="submitItem/)
  const { descriptor } = parse(quote)
  assert.match(quote, /<FinancialObservationForm v-if="record.invoice_id \|\| record.converted_to_invoice" kind="quote"/)
  assert.match(quote, /form.customer_id\?\.value/)
  const script = compileScript(descriptor, { id: 'quote-line-correction' })
  const template = compileTemplate({ id: 'quote-line-correction', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
})
