import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const staff = readFileSync(new URL('../../resources/js/Pages/CustomerRequests/Index.vue', import.meta.url), 'utf8')
const portal = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Requests/Index.vue', import.meta.url), 'utf8')
const fields = readFileSync(new URL('../../resources/js/Components/portal/PortalRequestForm.vue', import.meta.url), 'utf8')

test('portal prefill handles wrapped resources and contact fields have linked labels', () => {
  const body = portal.match(/function initialFormData\([^\n]+\) \{([\s\S]*?)\n\}/)[1]
  for (const wrap of [false, true]) {
    const account = { email: 'demo@example.test', primary_phone: '900000000' }
    const props = { warehouse: wrap ? { data: account } : account, invitations: [{ token: 'invitation-token' }] }
    const data = new Function('props', 'type', 'buildDefaultDetails', body)(props, 'general_support', () => ({}))
    assert.equal(data.email, account.email)
    assert.equal(data.contact, account.primary_phone)
    assert.equal(data.invitation, 'invitation-token')
  }
  for (const field of ['request-type', 'category-id', 'title', 'priority', 'preferred-date', 'description', 'email', 'contact']) {
    assert.ok(fields.includes(`for="portal-request-${field}"`))
    assert.ok(fields.includes(`id="portal-request-${field}"`))
  }
})

test('invitation issuance prevents duplicate sends and resets only on success', () => {
  const body = staff.match(/function issueInvitation\(\) \{([\s\S]*?)\n\}/)[1]
  const calls = []
  const form = { processing: true, post(url, options) { calls.push(options) }, reset() { calls.push('reset') } }
  const issue = new Function('invitationForm', 'route', body).bind(null, form, value => value)
  issue()
  assert.deepEqual(calls, [])
  form.processing = false
  issue()
  assert.equal(calls.length, 1)
  calls[0].onSuccess()
  assert.equal(calls[1], 'reset')
})

test('portal requires an invitation and mutations use non-GET methods', () => {
  assert.match(portal, /if \(form.processing \|\| !form.invitation\) return/)
  assert.match(portal, /:disabled="form.processing \|\| !form.invitation \|\| !invitations.length"/)
  assert.match(portal, /Sem convites disponíveis/)
  assert.match(portal, /portal.request.markAsDone[^\n]+method="post" as="button"/)
  assert.match(portal, /portal.request.destroy[^\n]+method="delete" as="button"/)
  assert.match(staff, /:action-methods="\{ delete: 'delete', restore: 'post' \}"/)
})

for (const [name, source] of [['staff', staff], ['portal', portal], ['fields', fields]]) {
  test(`${name} service request page compiles with one root and accessible feedback`, () => {
    const { descriptor, errors } = parse(source)
    assert.deepEqual(errors, [])
    assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
    const script = compileScript(descriptor, { id: name })
    const template = compileTemplate({ source: descriptor.template.content, filename: `${name}.vue`, id: name, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
    if (name !== 'fields') assert.match(source, /role="alert"/)
    assert.match(source, /:aria-invalid=/)
  })
}
