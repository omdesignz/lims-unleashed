import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const source = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Categories/Index.vue', import.meta.url), 'utf8')

test('category page compiles with one root, title and persistent validation/pending feedback', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
  const script = compileScript(descriptor, { id: 'maintenance-categories' })
  const template = compileTemplate({ source: descriptor.template.content, filename: 'Index.vue', id: 'maintenance-categories', compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /<Head title="Categorias de manutenção"/)
  assert.match(source, /role="alert"/)
  assert.match(source, /role="status"/)
})

test('presets are read-only and owned archive/restore uses the guarded shared workflow', () => {
  assert.match(source, /can.edit && !category.is_preset && !category.deleted/)
  assert.match(source, /can.archive && !category.is_preset && !category.deleted/)
  assert.match(source, /can.restore && !category.is_preset && category.deleted/)
  assert.match(source, /useRecordArchive/)
  assert.match(source, /archive.submit\(restore \? 'restore' : 'delete'/)
  assert.doesNotMatch(source, /não pode ser revertida|router.get\(route\('.*destroy/)
  assert.match(source, /:readonly="Boolean\(editingCategory\?\.code_locked\)"/)
})

test('category summaries use full server totals and search has one cancellable debounce', () => {
  assert.match(source, /props.stats\?\.presets/)
  assert.match(source, /props.stats\?\.owned/)
  assert.doesNotMatch(source, /codedCategoryCount|@input="applySearch"/)
  assert.match(source, /onUnmounted\(\(\) => applySearch.cancel\(\)\)/)
  assert.match(source, /archived: archived.value \? 1 : undefined/)
  for (const field of ['from', 'to', 'total', 'current_page', 'last_page']) {
    assert.ok(source.includes(`:${field}="categories.${field}"`))
  }
})

test('pending category form cannot be dismissed manually but successful submission closes it', () => {
  const body = source.match(/const closeModal = \(saved = false\) => \{([\s\S]*?)\n\}/)[1]
  const shown = { value: true }
  const editing = { value: { id: 4 } }
  const form = { processing: true, reset() {}, clearErrors() {} }
  const close = new Function('form', 'showCreateModal', 'editingCategory', `return (saved = false) => {${body}}`)(form, shown, editing)
  close()
  assert.equal(shown.value, true)
  close({ type: 'click' })
  assert.equal(shown.value, true)
  close(true)
  assert.equal(shown.value, false)
  assert.equal(editing.value, null)
  assert.match(source, /if \(form.processing\) return/)
  assert.match(source, /onHttpException:/)
  assert.match(source, /onNetworkError:/)
  assert.match(source, /onCancel:/)
  assert.match(source, /form.setError\('request'/)
})
