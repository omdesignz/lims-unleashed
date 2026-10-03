import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'
import { renderToString } from '@vue/server-renderer'

const read = path => readFileSync(new URL(`../../resources/js/Pages/${path}.vue`, import.meta.url), 'utf8')
const index = read('VAPProposals/Index')
const show = read('VAPProposals/Show')
const create = read('VAPProposals/Create')
const edit = read('VAPProposals/Edit')
const templateShow = read('VAPProposalTemplates/Show')

async function renderFragment(source, state) {
  const compiled = compileTemplate({ id: 'staff-payload', source, compilerOptions: { mode: 'function' } })
  assert.deepEqual(compiled.errors, [])
  const render = new Function('Vue', compiled.code)(Vue)
  const app = Vue.createSSRApp({ setup: () => state, render })
  for (const name of ['ClockIcon', 'UserIcon', 'ArrowRightIcon', 'ArrowDownTrayIcon', 'EyeIcon']) {
    app.component(name, { render: () => Vue.h('svg') })
  }
  app.component('Link', { render() { return Vue.h('a', this.$attrs, this.$slots.default?.()) } })
  app.config.globalProperties.$t = value => value
  app.config.globalProperties.route = (name, id) => `/${name}/${id}`
  app.config.warnHandler = message => { throw new Error(message) }
  return renderToString(app)
}

test('row mutations rely on explicit boolean server capabilities, not status alone', () => {
  for (const [name, field] of [['canRevise', 'can_revise'], ['canDelete', 'can_archive']]) {
    const expression = index.match(new RegExp(`const ${name} = \\(proposal\\) => ([^\\n]+)`))[1]
    const allowed = new Function('proposal', `return ${expression}`)
    for (const value of [undefined, null, false, 0, 1, 'true']) {
      assert.equal(allowed({ status: 'PENDING', [field]: value }), false)
    }
    assert.equal(allowed({ [field]: true }), true)
  }
})

test('PDF availability renders from a boolean without exposing or relying on storage paths', async () => {
  for (const source of [index, show]) {
    const fragment = source.match(/<a v-if="proposal\.has_document"[\s\S]*?<\/a>/)[0]
    const visible = await renderFragment(fragment, { proposal: { id: 12, has_document: true } })
    assert.match(visible, /href="\/vap-proposals.download.pdf\/12"/)
    assert.equal(await renderFragment(fragment, { proposal: { id: 12, has_document: false, file_path: 'ignored-private-path' } }), '<!--v-if-->')
  }
})

test('deleted authors render a stable fallback in all author previews', async () => {
  for (const source of [create, edit, templateShow]) {
    const fragments = [...source.matchAll(/<(p|span)\b[^>]*>[^<]*\{\{ (?:selectedTemplate|template)\.user[^<]*<\/\1>/g)]
    assert.ok(fragments.length > 0)
    for (const [fragment] of fragments) {
      const missing = await renderFragment(fragment, { selectedTemplate: { user: null }, template: { user: null } })
      assert.match(missing, /—/)
      const known = await renderFragment(fragment, { selectedTemplate: { user: { name: 'Lab author' } }, template: { user: { name: 'Lab author' } } })
      assert.match(known, /Lab author/)
    }
  }
})

test('revision history renders reason safely for incomplete and empty historical comparisons', async () => {
  const fragment = show.match(/<section v-if="revisions.length > 0"[\s\S]*?<\/section>/)[0]
  for (const properties of [{ reason: 'Earlier revision', old_values: { total: 10 } }, { old_values: {}, new_values: {} }, {}]) {
    const html = await renderFragment(fragment, {
      revisions: [{ id: 1, event: 'revised', description: 'revised', causer: null, properties }],
      formatDateTime: () => 'Today', formatCurrency: value => { assert.ok(value != null); return `AOA ${value}` },
    })
    assert.doesNotMatch(html, /NaN|undefined/)
    if (properties.reason) assert.match(html, /Earlier revision/)
  }
  const html = await renderFragment(fragment, {
    revisions: [{ id: 2, event: 'revised', description: 'revised', causer: { name: 'Lab author' }, properties: { old_values: { total: 10, items_count: 1 }, new_values: { total: 25, items_count: 2 } } }],
    formatDateTime: () => 'Today', formatCurrency: value => `AOA ${value}`,
  })
  assert.match(html, /AOA 10/)
  assert.match(html, /AOA 25/)
})

test('template activity links honor the existing proposal-view permission', async () => {
  const fragment = templateShow.match(/<Link\s+v-if="proposal.can_view"[\s\S]*?<\/Link>/)[0]
  assert.ok(fragment)
  assert.equal(await renderFragment(fragment, { proposal: { id: 12, can_view: false } }), '<!--v-if-->')
  assert.match(await renderFragment(fragment, { proposal: { id: 12, can_view: true } }), /href="\/vap-proposals.show\/12"/)
})

test('all changed staff pages compile with their actual script bindings', () => {
  for (const [filename, source] of Object.entries({ index, show, create, edit, templateShow })) {
    const { descriptor, errors } = parse(source, { filename })
    assert.deepEqual(errors, [])
    const script = compileScript(descriptor, { id: filename })
    const template = compileTemplate({ id: filename, filename, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
  }
})
