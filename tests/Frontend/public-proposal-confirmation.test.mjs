import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'
import { renderToString } from '@vue/server-renderer'

const source = readFileSync(new URL('../../resources/js/Pages/Public/ThankYou.vue', import.meta.url), 'utf8')
const confirmation = new Function('props', source.match(/const confirmation = computed\(\(\) => \{([\s\S]*?)\n\}\)/)[1])
const { descriptor } = parse(source)
const compiled = compileTemplate({ id: 'proposal-confirmation', source: descriptor.template.content, compilerOptions: { mode: 'function' } })
const render = new Function('Vue', compiled.code)(Vue)

test('confirmation page compiles with native Inertia navigation and shared tokens', () => {
  const script = compileScript(descriptor, { id: 'proposal-confirmation' })
  assert.match(script.content, /layout:\s*false/)
  const template = compileTemplate({ id: 'proposal-confirmation', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /<main /)
  assert.match(source, /aria-labelledby="confirmation-title"/)
  assert.match(source, /<Link :href="proposalUrl"/)
  assert.match(source, /ds-button ds-button-primary[^\"]*min-h-11/)
  assert.doesNotMatch(source, /<style|prefetch|animate-|v-html|transition-all/)
})

test('accepted and rejected confirmations describe the recorded decision with a recovery link', async () => {
  for (const [status, title] of [['ACCEPTED', 'Aceitação registada'], ['REJECTED', 'Rejeição registada']]) {
    const proposal = { proposal_number: 'PROP 2026/001', status }
    const app = Vue.createSSRApp({ setup: () => ({ proposal, proposalUrl: '/proposal/token', confirmation: confirmation({ proposal }) }), render })
    app.component('Head', { render: () => null })
    app.component('Link', { props: ['href'], render() { return Vue.h('a', { href: this.href }, this.$slots.default()) } })
    app.config.warnHandler = message => { throw new Error(message) }
    const html = await renderToString(app)
    assert.match(html, new RegExp(`<h1[^>]*>${title}</h1>`))
    assert.match(html, /PROP 2026\/001/)
    assert.match(html, /<a[^>]*href="\/proposal\/token"[^>]*>\s*Voltar à proposta/)
  }
})

test('other statuses never claim acceptance or rejection; proposal text is escaped', async () => {
  for (const status of ['PENDING', 'SENT', 'VIEWED', 'REVISED', 'EXPIRED', 'unknown']) {
    const proposal = { proposal_number: '<script>private</script>', status }
    const app = Vue.createSSRApp({ setup: () => ({ proposal, proposalUrl: '/proposal/token', confirmation: confirmation({ proposal }) }), render })
    app.component('Head', { render: () => null })
    app.component('Link', { props: ['href'], render() { return Vue.h('a', { href: this.href }, this.$slots.default()) } })
    const html = await renderToString(app)
    assert.match(html, /Estado da proposta/)
    assert.doesNotMatch(html, /Aceitação registada|Rejeição registada|<script>/)
    assert.match(html, /&lt;script&gt;private&lt;\/script&gt;/)
  }
})

test('duplicate proposal authoring pages and write routes are absent', () => {
  for (const name of ['Create', 'Edit']) {
    assert.equal(existsSync(new URL(`../../resources/js/Pages/Proposals/${name}.vue`, import.meta.url)), false)
  }
  const routes = readFileSync(new URL('../../resources/js/ziggy.js', import.meta.url), 'utf8')
  assert.doesNotMatch(routes, /"proposals\.(store|update)"/)
  assert.match(routes, /"vap-proposals.store"/)
  assert.match(routes, /"vap-proposals.update"/)
})

test('duplicate template authoring is retired and archive routes require DELETE/PATCH', () => {
  for (const name of ['Create', 'Edit']) {
    assert.equal(existsSync(new URL(`../../resources/js/Pages/ProposalTemplates/${name}.vue`, import.meta.url)), false)
  }
  assert.equal(existsSync(new URL('../../app/Http/Requests/ProposalTemplateRequest.php', import.meta.url)), false)
  const routes = readFileSync(new URL('../../resources/js/ziggy.js', import.meta.url), 'utf8')
  assert.doesNotMatch(routes, /"proposaltemplates\.(store|update)"/)
  const definitions = JSON.parse(routes.match(/const Ziggy = (\{[^\n]*\});/)[1]).routes
  assert.deepEqual(definitions['proposaltemplates.destroy'].methods, ['DELETE'])
  assert.deepEqual(definitions['proposaltemplates.restore'].methods, ['PATCH'])
})
