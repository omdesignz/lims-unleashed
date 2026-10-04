import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { computed } from 'vue'

const source = readFileSync(new URL('../../resources/js/Components/documents/FinancialObservationForm.vue', import.meta.url), 'utf8')
const script = source.match(/const source = props.record[\s\S]*?(?=<\/script>)/)[0]
const initialize = new Function('props', 'computed', 'useForm', 'route', `${script}\nreturn { source, config, form, submit }`)

for (const [kind, routes] of [['invoice', 'invoices'], ['credit_note', 'creditnotes'], ['receipt', 'receipts'],
  ['quote', 'quotes'], ['import_certificate', 'importcertificates'], ['export_certificate', 'exportcertificates']]) {
  test(`${kind} editor sends observations only and preserves drafts after failures`, () => {
    const calls = []
    let defaults = 0
    const state = initialize({ kind, record: { data: { id: 42, document_no: 'ISSUED-42', obs: 'Draft', total: 100, items: [1], customer_id: 9 } } }, computed, data => ({
      ...data, processing: false, errors: {},
      put(url, options) { this.processing = true; calls.push({ url, options }) },
      defaults() { defaults++ },
      setError(field, message) { this.errors[field] = message },
    }), (name, id) => `${name}/${id}`)
    assert.equal(state.source.document_no, 'ISSUED-42')
    assert.deepEqual(Object.keys(state.form).filter(key => typeof state.form[key] !== 'function' && !['processing', 'errors'].includes(key)), ['obs'])
    state.submit()
    assert.equal(calls[0].url, `${routes}.update/42`)
    assert.equal(calls[0].options.preserveScroll, true)
    state.submit()
    assert.equal(calls.length, 1)
    assert.equal(calls[0].options.onNetworkError(), false, 'The retained draft must suppress native error handling')
    assert.equal(state.form.obs, 'Draft')
    assert.match(state.form.errors.obs, /Ligação interrompida/)
    assert.equal(calls[0].options.onHttpException(), false, 'HTTP errors must not replace the observation editor')
    assert.match(state.form.errors.obs, /Não foi possível guardar/)
    calls[0].options.onSuccess()
    assert.equal(defaults, 1)
    state.form.processing = false
    state.submit()
    assert.equal(calls.length, 2, 'A failed save must be retryable with the same observations')
    assert.equal(state.form.obs, 'Draft')
    state.form.processing = false
    state.form.put = () => { throw new Error('Dispatch failure') }
    state.submit()
    assert.equal(state.form.obs, 'Draft')
    assert.match(state.form.errors.obs, /iniciar a correcção/)
  })
}

test('observation-only form and persistent-layout pages compile with accessible feedback', () => {
  for (const path of ['Components/documents/FinancialObservationForm.vue', 'Pages/Invoices/Edit.vue', 'Pages/CreditNotes/Edit.vue', 'Pages/Receipts/Edit.vue']) {
    const text = readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
    const { descriptor } = parse(text)
    const script = compileScript(descriptor, { id: path })
    const result = compileTemplate({ id: path, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(result.errors, [])
    if (path.startsWith('Pages/')) {
      assert.match(text, /defineOptions\(\{ layout: Layout \}\)/)
      assert.match(text, /<FinancialObservationForm/)
      assert.doesNotMatch(text, /v-model|items|total|customer_id/)
    }
  }
  assert.match(source, /class="ds-field-label"/)
  assert.match(source, /for="financial-observations"/)
  assert.match(source, /:aria-invalid="Boolean\(form.errors.obs\)"/)
  assert.match(source, /role="alert"/)
  assert.match(source, /role="status"/)
  assert.match(source, /:disabled="form.processing \|\| !form.isDirty"/)
  assert.match(source, /maxlength="5000"/)
  assert.match(source, /Apenas as observações/)
  assert.match(source, /<h1 class="[^"]*\[overflow-wrap:anywhere\]/)
  assert.doesNotMatch(source, /v-motion|transition-all|animate-/)
})
