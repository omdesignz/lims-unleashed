import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { ref } from 'vue'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
const source = read('Pages/SystemSettings/Index.vue')
const helper = read('Composables/useGeneralSettingsForm.js')
const { generalSettingsFormData, generalSettingsPayload } = await import(`data:text/javascript;base64,${Buffer.from(helper).toString('base64')}`)

test('saved secrets never populate form defaults and empty keys are omitted', () => {
  const settings = { app_name: 'Laboratory', app_private_key: 'must-not-enter-defaults' }
  assert.deepEqual(generalSettingsFormData(settings, 'snapshot-revision'), { app_name: 'Laboratory', app_private_key: '', settings_revision: 'snapshot-revision' })
  assert.equal(settings.app_private_key, 'must-not-enter-defaults')
  for (const app_private_key of ['', '  ', null, undefined]) {
    assert.deepEqual(generalSettingsPayload({ app_name: 'Laboratory', app_private_key }), { app_name: 'Laboratory' })
  }
  assert.equal(generalSettingsPayload({ app_private_key: 'new key' }).app_private_key, 'new key')
})

test('settings page exposes configured status without reading a private key prop', () => {
  assert.doesNotMatch(source, /settings\.app_private_key|maskedKey\(settings\.app_private_key\)/)
  assert.match(source, /securitySummary\.private_key_configured \? 'Configurada — conteúdo protegido'/)
  assert.match(source, /<button v-if="canEdit"/)
  assert.match(source, /<fieldset :disabled="form\.processing"/)
  assert.match(source, /<Head title="Configurações gerais"/)
  assert.match(source, /<ul v-if="Object\.keys\(form\.errors\)\.length" role="alert"/)
  assert.match(source, /v-for="\(error, field\) in form\.errors"/)
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'settings-security' })
  assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: 'SystemSettings/Index.vue', id: 'settings-security', compilerOptions: { bindingMetadata: script.bindings } }).errors, [])
})

function submission(canEdit = true, processing = false, conflict = false) {
  let request
  let defaults
  const form = { processing, app_private_key: 'new secret', settings_revision: 'draft-revision', errors: conflict ? { settings_revision: 'Stale draft' } : {},
    clearErrors() { this.errors = {} }, transform(callback) { this.payload = callback(this) },
    post(url, options) { request = { url, options } }, defaults(data) { defaults = data },
    reset() { Object.assign(this, defaults) }, setError(key, message) { this.errors[key] = message } }
  const editSettings = ref(true)
  const recoverBody = source.match(/function recoverSave\(message\) \{([\s\S]*?)\n\}/)[1]
  const recoverSave = new Function('form', `return message => {${recoverBody}}`)(form)
  const submitBody = source.match(/const submit = \(\) => \{([\s\S]*?)\n\}/)[1]
  new Function('props', 'form', 'generalSettingsPayload', 'route', 'generalSettingsFormData', 'editSettings', 'recoverSave', submitBody)(
    { canEdit, settingsRevision: 'newer-prop-revision' }, form, generalSettingsPayload, name => name, generalSettingsFormData, editSettings, recoverSave,
  )
  return { form, request, editSettings, get defaults() { return defaults } }
}

test('read-only and pending settings cannot submit', () => {
  assert.equal(submission(false).request, undefined)
  assert.equal(submission(true, true).request, undefined)
  assert.equal(submission(true, false, true).request, undefined)
})

test('successful saves refresh server defaults and erase the submitted secret', () => {
  const state = submission()
  assert.equal(state.request.url, 'generalsettings.update')
  state.request.options.onSuccess({ props: { settings: { app_name: 'Saved name' }, settingsRevision: 'saved-revision' } })
  assert.deepEqual(state.defaults, { app_name: 'Saved name', app_private_key: '', settings_revision: 'saved-revision' })
  assert.equal(state.form.app_private_key, '')
  assert.equal(state.editSettings.value, false)
})

for (const event of ['onError', 'onHttpException', 'onNetworkError', 'onCancel']) {
  test(`${event} clears the secret while retaining the settings draft`, () => {
    const state = submission()
    state.form.app_name = 'Unsaved name'
    const result = state.request.options[event]()
    assert.equal(state.form.app_private_key, '')
    assert.equal(state.form.app_name, 'Unsaved name')
    assert.equal(state.form.settings_revision, 'draft-revision')
    assert.equal(state.editSettings.value, true)
    if (event === 'onHttpException' || event === 'onNetworkError') assert.equal(result, false)
    if (event !== 'onError') assert.ok(state.form.errors.request)
  })
}

test('cancel resets from safe server defaults and cannot close during a save', () => {
  const body = source.match(/const toggleEdit = \(\) => \{([\s\S]*?)\n\}/)[1]
  const calls = []
  const form = { processing: false, defaults: value => calls.push(value), reset() {}, clearErrors() {} }
  const editSettings = ref(true)
  const toggle = new Function('props', 'form', 'editSettings', 'generalSettingsFormData', body)
  toggle({ canEdit: true, settings: { app_name: 'Saved' }, settingsRevision: 'current-revision' }, form, editSettings, generalSettingsFormData)
  assert.deepEqual(calls, [{ app_name: 'Saved', app_private_key: '', settings_revision: 'current-revision' }])
  form.processing = true
  toggle({ canEdit: true }, form, editSettings, generalSettingsFormData)
  assert.equal(editSettings.value, false)
})

test('conflict recovery requires explicit discard and never silently adopts a newer prop revision', () => {
  const state = submission()
  assert.equal(state.form.payload.settings_revision, 'draft-revision')
  state.request.options.onError({ settings_revision: 'Conflict' })
  assert.equal(state.form.settings_revision, 'draft-revision')
  assert.match(source, /Descartar rascunho/)
  assert.match(source, /class="flex min-w-0 flex-wrap gap-2"/)
  assert.match(source, /grid grid-cols-1 gap-4 xl:grid-cols-\[17rem_minmax\(0,1fr\)\]/)
  assert.match(source, /<aside class="min-w-0/)
  assert.equal((source.match(/:disabled="form\.processing \|\| Boolean\(form\.errors\.settings_revision\)"/g) || []).length, 2)
  assert.match(source, /generalSettingsFormData\(props\.settings, props\.settingsRevision\)/)
})
