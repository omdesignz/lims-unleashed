import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'

const layout = readFileSync(new URL('../../resources/js/Shared/Layouts/PortalLayout.vue', import.meta.url), 'utf8')
const dashboard = readFileSync(new URL('../../resources/js/Pages/ClientPortal/Dashboard.vue', import.meta.url), 'utf8')

test('portal shell and overview compile after navigation repairs', () => {
  for (const [filename, source] of [['PortalLayout.vue', layout], ['Dashboard.vue', dashboard]]) {
    const { descriptor, errors } = parse(source, { filename })
    assert.deepEqual(errors, [])
    compileScript(descriptor, { id: filename })
    assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: filename }).errors, [])
  }
})

test('all document families are reachable from shared desktop and mobile navigation', () => {
  for (const name of ['invoices', 'receipts', 'quotes', 'creditnotes', 'contractguides', 'qualitycertificates']) {
    assert.ok(layout.includes(`route('portal.${name}')`))
  }
  assert.equal((layout.match(/v-for="item in navigation"/g) ?? []).length, 2)
  assert.match(layout, /<DialogTitle/)
  assert.match(layout, /:aria-label="labels.closeNavigation"/)
})

test('portal account chrome unwraps resources and provides route-specific document titles', () => {
  assert.match(layout, /auth\?\.user\?\.data \?\? page\.props\?\.auth\?\.user/)
  assert.doesNotMatch(layout, /page\.props\?\.auth\?\.user\?\.(name|email|customer)/)
  assert.match(layout, /<Head :title="pageTitle"/)
  assert.match(layout, /navigation\.find\(isActive\)/)
  assert.match(dashboard, /Novo pedido/)
  assert.doesNotMatch(dashboard, /Nova pedido/)
})

test('compact portal header retains accessible branding without forcing mobile overflow', () => {
  assert.match(layout, /gap-2 px-4 py-3 sm:gap-4/)
  assert.match(layout, /max-w-16 object-contain sm:max-w-40/)
  assert.match(layout, /class="sr-only sm:not-sr-only"/)
  assert.match(layout, /flex min-w-0 items-center gap-2 sm:gap-3/)
})
