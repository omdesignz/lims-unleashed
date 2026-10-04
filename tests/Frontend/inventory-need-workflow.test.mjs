import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const source = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Needs/Show.vue', import.meta.url), 'utf8')

for (const name of ['approve', 'reject', 'convertToOrder']) {
  test(`${name} prevents repeated submissions while either form is pending`, () => {
    const calls = []
    const actionForm = { processing: true, post: (...args) => calls.push(args), clearErrors() {} }
    const conversionForm = { processing: false, post: (...args) => calls.push(args), clearErrors() {} }
    const body = source.match(new RegExp(`const ${name} = \\(\\) => \\{([\\s\\S]*?)\\n\\}`))[1]
    const invoke = () => new Function('actionForm', 'conversionForm', 'route', 'props', body)(actionForm, conversionForm, value => value, { need: { id: 7 } })
    invoke()
    assert.equal(calls.length, 0)
    actionForm.processing = false
    conversionForm.processing = true
    invoke()
    assert.equal(calls.length, 0)
    conversionForm.processing = false
    invoke()
    assert.equal(calls.length, 1)
  })
}

test('procurement decisions compile with one root, accessible errors and quantity names', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
  const script = compileScript(descriptor, { id: 'inventory-need-workflow' })
  const template = compileTemplate({ source: descriptor.template.content, filename: 'Show.vue', id: 'inventory-need-workflow', compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /role="alert"/)
  assert.match(source, /Object.values\(actionForm.errors\)/)
  assert.match(source, /Object.values\(conversionForm.errors\)/)
  assert.match(source, /:aria-label="`Quantidade aprovada:/)
})
