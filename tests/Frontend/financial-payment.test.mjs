import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { ref } from 'vue'

const source = readFileSync(new URL('../../resources/js/Pages/Invoices/Index.vue', import.meta.url), 'utf8')
const execute = new Function('form', 'route', 'paymentBusy', 'showPaymentConfirmation', source.match(/let submit = \(\) => \{([\s\S]*?)\n\}\n/)[1])

test('payment uses POST, blocks duplicate submissions, keeps failures open and closes only on success', () => {
  const calls = []
  const form = { processing: false, post: (...args) => calls.push(args), setError: (...args) => errors.push(args), reset: () => { resets++ } }
  const errors = []
  let resets = 0
  const busy = ref(false)
  const open = ref(true)
  execute(form, name => `/route/${name}`, busy, open)
  execute(form, name => `/route/${name}`, busy, open)
  assert.equal(calls.length, 1)
  assert.equal(calls[0][0], '/route/invoices.changeStatusToPaid')
  const options = calls[0][1]
  options.onNetworkError()
  assert.equal(open.value, true)
  assert.equal(errors.length, 1)
  options.onFinish()
  assert.equal(busy.value, false)
  execute(form, name => `/route/${name}`, busy, open)
  calls[1][1].onHttpException()
  assert.equal(open.value, true)
  calls[1][1].onFinish()
  execute(form, name => `/route/${name}`, busy, open)
  calls[2][1].onSuccess()
  calls[2][1].onFinish()
  assert.equal(open.value, false)
  assert.equal(resets, 1)
  form.post = () => { throw new Error('Cannot dispatch') }
  open.value = true
  execute(form, name => `/route/${name}`, busy, open)
  assert.equal(busy.value, false)
  assert.equal(open.value, true)
})

test('payment page compiles and exposes pending and validation states using the shared controlled dialog', () => {
  const { descriptor } = parse(source)
  const script = compileScript(descriptor, { id: 'finance-payment' })
  const template = compileTemplate({ id: 'finance-payment', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /<PaymentDialog :show="showPaymentConfirmation" :closeable="!paymentBusy"/)
  assert.match(source, /<p v-if="form.hasErrors" role="alert">/)
  assert.match(source, /:disabled="paymentBusy \|\| !form.payment_method\?\.value"/)
  assert.doesNotMatch(source, /form\.get\(route\('invoices.changeStatusToPaid'/)
  const routes = readFileSync(new URL('../../resources/js/ziggy.js', import.meta.url), 'utf8')
  const definitions = JSON.parse(routes.match(/const Ziggy = (\{[^\n]*\});/)[1]).routes
  assert.deepEqual(definitions['invoices.changeStatusToPaid'].methods, ['POST'])
})
