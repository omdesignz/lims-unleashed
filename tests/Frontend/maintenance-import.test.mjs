import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const source = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Import.vue', import.meta.url), 'utf8')
const submitBody = source.match(/function submit\(\) \{([\s\S]*?)\n\}/)[1]
const chooseBody = source.match(/function chooseFile\(event\) \{([\s\S]*?)\n\}/)[1]

function fixture({ synchronousFailure = false } = {}) {
  const requestError = { value: '' }
  const submitting = { value: false }
  const calls = []
  const form = {
    file: { name: 'maintenance.csv' }, request_key: 'original-key', processing: false,
    errors: {}, clearErrors() { this.errors = {} },
    post(url, options) {
      if (synchronousFailure) throw new Error('Unable to start')
      calls.push({ url, options })
    },
  }
  const submit = new Function('form', 'submitting', 'requestError', 'route', `return () => {${submitBody}}`)(form, submitting, requestError, value => value)
  const chooseFile = new Function('form', 'submitting', 'requestError', 'crypto', `return event => {${chooseBody}}`)(form, submitting, requestError, { randomUUID: () => 'new-key' })
  return { form, submitting, requestError, calls, submit, chooseFile }
}

test('import blocks immediate duplicate submits, pending file changes and empty submissions', () => {
  const state = fixture()
  const original = state.form.file
  state.submit()
  state.submit()
  state.chooseFile({ target: { files: [{ name: 'changed.csv' }] } })
  assert.equal(state.calls.length, 1)
  assert.equal(state.form.file, original)
  assert.equal(state.form.request_key, 'original-key')
  assert.equal(state.calls[0].url, 'maintenancetasks.import.upload')
  assert.equal(state.calls[0].options.forceFormData, true)
  state.calls[0].options.onFinish()
  assert.equal(state.submitting.value, false)
  state.form.file = null
  state.submit()
  assert.equal(state.calls.length, 1)
})

test('network, HTTP and cancellation failures retain the file and original operation key for retries', () => {
  for (const failure of ['onNetworkError', 'onHttpException', 'onCancel']) {
    const state = fixture()
    const original = state.form.file
    state.submit()
    state.calls[0].options[failure]({ status: 503 })
    state.calls[0].options.onFinish()
    assert.ok(state.requestError.value)
    assert.equal(state.form.file, original)
    assert.equal(state.form.request_key, 'original-key')
    state.submit()
    assert.equal(state.calls.length, 2)
    assert.equal(state.form.request_key, 'original-key')
  }
})

test('validation errors remain visible and editing the selected file explicitly starts a new operation', () => {
  const state = fixture()
  state.submit()
  state.form.errors = { file: 'Linha 3: inválida' }
  state.calls[0].options.onFinish()
  assert.equal(state.form.errors.file, 'Linha 3: inválida')
  assert.equal(state.form.request_key, 'original-key')
  const replacement = { name: 'corrected.csv' }
  state.chooseFile({ target: { files: [replacement] } })
  assert.equal(state.form.file, replacement)
  assert.equal(state.form.request_key, 'new-key')
  assert.deepEqual(state.form.errors, {})
})

test('synchronous and authorization failures expose a recoverable error without a stuck submit guard', () => {
  const state = fixture({ synchronousFailure: true })
  state.submit()
  assert.equal(state.submitting.value, false)
  assert.match(state.requestError.value, /mantido/)
  assert.equal(state.form.request_key, 'original-key')
  for (const status of [403, 404]) {
    const denied = fixture()
    denied.submit()
    assert.equal(denied.calls[0].options.onHttpException({ status }), false)
    denied.calls[0].options.onFinish()
    assert.match(denied.requestError.value, /autorização/)
    assert.equal(denied.submitting.value, false)
  }
})

test('canonical import compiles as one root with accessible requirements, errors and honest pending state', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
  const script = compileScript(descriptor, { id: 'maintenance-import' })
  const template = compileTemplate({ source: descriptor.template.content, filename: 'Import.vue', id: 'maintenance-import', compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /role="alert"/)
  assert.match(source, /<Head title="Importar tarefas"/)
  assert.match(source, /role="status"/)
  assert.match(source, /:aria-busy="submitting \|\| form.processing"/)
  assert.match(source, /:aria-describedby=/)
  assert.match(source, /maintenancetasks.import.template/)
  assert.match(source, /nenhuma linha é ignorada/)
  assert.doesNotMatch(source, /setInterval|axios|import-status|progressbar|router\.visit/)
})

test('import stays in the inventory navigation area without matching unrelated paths', () => {
  const layout = readFileSync(new URL('../../resources/js/Shared/Layouts/Layout.vue', import.meta.url), 'utf8')
  const expression = layout.match(/const canonicalNavigationPath = ([^\n]+)/)[1]
  const normalize = new Function(`return ${expression}`)()
  assert.equal(normalize('/maintenance-tasks/import'), '/maintenance/tasks/import')
  assert.equal(normalize('/maintenance/tasks'), '/maintenance/tasks')
  assert.equal(normalize('/maintenance-tasks-other/import'), '/maintenance-tasks-other/import')
  assert.match(layout, /pathMatches\(canonicalNavigationPath\(path\), prefix\)/)
})

test('narrow screens hide the desktop sidebar wrapper without hiding the mobile drawer', () => {
  const sidebar = readFileSync(new URL('../../resources/js/Shared/Navigation/app-sidebar.vue', import.meta.url), 'utf8')
  const css = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8')
  assert.match(sidebar, /<div class="app-sidebar">/)
  assert.match(css, /\.app-sidebar\s*\{\s*display: contents;/)
  assert.match(css, /@media \(max-width: 1023px\) \{[\s\S]*?\.app-sheet > \.app-sidebar\s*\{\s*display: none;/)
  assert.doesNotMatch(css, /\.app-drawer > \.app-sidebar\s*\{\s*display: none;/)
  const { descriptor, errors } = parse(sidebar)
  assert.deepEqual(errors, [])
  assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
  const script = compileScript(descriptor, { id: 'import-sidebar' })
  const template = compileTemplate({ source: descriptor.template.content, filename: 'app-sidebar.vue', id: 'import-sidebar', compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
})
