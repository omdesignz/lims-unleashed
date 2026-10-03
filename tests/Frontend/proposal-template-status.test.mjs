import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'
import { renderToString } from '@vue/server-renderer'

const source = readFileSync(new URL('../../resources/js/Pages/VAPProposalTemplates/Show.vue', import.meta.url), 'utf8')
const body = name => source.match(new RegExp(`(?:async )?function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}\\n`))[1]
const route = (name, id) => ({ name, id })
const trans = key => key

function state() {
  let resolve
  let reject
  let request
  const statusSubmitting = Vue.ref(false)
  const statusRequest = {
    processing: false,
    clearErrors() {},
    transform(callback) { this.payload = callback(); return this },
    put(url, options) { request = { url, options, payload: this.payload }; return new Promise((yes, no) => { resolve = yes; reject = no }) },
  }
  const values = {
    props: Vue.reactive({ template: { id: 17, is_active: true, proposals_count: 0 } }),
    statusProcessing: Vue.computed(() => statusSubmitting.value || statusRequest.processing),
    statusSubmitting, statusRequest, pendingStatus: Vue.ref(null), statusError: Vue.ref(''),
    statusRefreshError: Vue.ref(''), confirmedStatus: Vue.ref(null),
    showStatusModal: Vue.ref(false), showDeleteModal: Vue.ref(false), pendingDeleteId: Vue.ref(null),
    archive: { processing: Vue.ref(false), failed: Vue.ref(false), message: Vue.ref(''), submit: (...args) => values.archiveCalls.push(args) },
    toast: { success: message => values.toasts.push(message), warning() {} },
    router: { reload: options => values.reloads.push(options) },
    toasts: [], reloads: [], archiveCalls: [], route, trans,
  }
  values.templateActive = Vue.computed(() => values.confirmedStatus.value?.id === values.props.template.id ? values.confirmedStatus.value.is_active : values.props.template.is_active)
  function invoke(name, ...args) {
    const parameters = Object.keys(values)
    const callback = new (name === 'toggleTemplateStatus' ? Object.getPrototypeOf(async function () {}).constructor : Function)(...parameters, ...(name === 'syncConfirmedTemplateStatus' ? ['template'] : []), body(name))
    return callback(...Object.values(values), ...args)
  }
  values.reportStatusRefreshFailure = () => invoke('reportStatusRefreshFailure')
  return { values, invoke, request: () => request, resolve: () => resolve(), reject: () => reject(new Error('Request failed')) }
}

test('confirmation freezes template identity and desired state and blocks replacement', () => {
  const { values, invoke } = state()
  invoke('requestStatusToggle')
  assert.equal(values.showStatusModal.value, true)
  assert.deepEqual(values.pendingStatus.value, { id: 17, is_active: false })
  values.props.template = { id: 99, is_active: false }
  invoke('requestStatusToggle')
  assert.deepEqual(values.pendingStatus.value, { id: 17, is_active: false })
  values.statusSubmitting.value = true
  invoke('cancelStatusToggle')
  assert.equal(values.showStatusModal.value, true)
  values.statusSubmitting.value = false
  invoke('cancelStatusToggle')
  assert.equal(values.showStatusModal.value, false)
  assert.equal(values.pendingStatus.value, null)
})

test('actual status handler sends explicit frozen intent and allows one pending request', async () => {
  const harness = state()
  const { values, invoke } = harness
  invoke('requestStatusToggle')
  values.props.template = { id: 99, is_active: false }
  const pending = invoke('toggleTemplateStatus')
  assert.equal(values.statusSubmitting.value, true)
  assert.deepEqual(harness.request().url, { name: 'vap-proposals.templates.toggle-status', id: 17 })
  assert.deepEqual(harness.request().payload, { is_active: false })
  assert.equal(await invoke('toggleTemplateStatus'), undefined)
  harness.request().options.onSuccess({ success: true, is_active: false })
  harness.resolve()
  await pending
  assert.equal(values.statusSubmitting.value, false)
  assert.equal(values.showStatusModal.value, false)
  assert.equal(values.pendingStatus.value, null)
  assert.match(values.toasts[0], /deactivated$/)
  assert.deepEqual(values.reloads[0].only, ['template'])
  assert.deepEqual(values.confirmedStatus.value, { id: 17, is_active: false })
})

for (const failure of ['validation', 'forbidden', 'missing', 'conflict', 'server', 'network', 'cancel', 'malformed', 'wrong status']) {
  test(`${failure} keeps confirmation and intended state available for recovery`, async () => {
    const harness = state()
    const { values, invoke } = harness
    invoke('requestStatusToggle')
    const pending = invoke('toggleTemplateStatus')
    const callbacks = harness.request().options
    if (failure === 'validation') callbacks.onError({ is_active: 'Invalid status.' })
    else if (failure === 'network') callbacks.onNetworkError(new Error('Offline'))
    else if (failure === 'cancel') callbacks.onCancel()
    else if (failure === 'malformed') callbacks.onSuccess({})
    else if (failure === 'wrong status') callbacks.onSuccess({ success: true, is_active: true })
    else callbacks.onHttpException({ status: { forbidden: 403, missing: 404, conflict: 409, server: 500 }[failure] })
    harness.reject()
    await pending
    assert.equal(values.statusSubmitting.value, false)
    assert.equal(values.showStatusModal.value, true)
    assert.deepEqual(values.pendingStatus.value, { id: 17, is_active: false })
    assert.ok(values.statusError.value)
    assert.deepEqual(values.reloads, [])
    assert.deepEqual(values.toasts, [])
    values.props.template = { id: 99, is_active: false }
    const retry = invoke('toggleTemplateStatus')
    assert.deepEqual(harness.request().url, { name: 'vap-proposals.templates.toggle-status', id: 17 })
    assert.deepEqual(harness.request().payload, { is_active: false })
    harness.resolve()
    await retry
  })
}

test('synchronous dispatch failure leaves recoverable feedback and releases pending guard', async () => {
  const { values, invoke } = state()
  invoke('requestStatusToggle')
  values.statusRequest.put = () => { throw new Error('Cannot dispatch') }
  await invoke('toggleTemplateStatus')
  assert.equal(values.statusSubmitting.value, false)
  assert.equal(values.showStatusModal.value, true)
  assert.ok(values.statusError.value)
})

for (const failure of ['validation', 'http', 'network', 'cancel', 'dispatch']) {
  test(`successful write survives ${failure} refresh failure without reverting the displayed or next intended status`, async () => {
    const harness = state()
    const { values, invoke } = harness
    if (failure === 'dispatch') values.router.reload = () => { throw new Error('Cannot refresh') }
    invoke('requestStatusToggle')
    const pending = invoke('toggleTemplateStatus')
    harness.request().options.onSuccess({ success: true, is_active: false })
    harness.resolve()
    await pending
    if (failure !== 'dispatch') values.reloads[0][{ validation: 'onError', http: 'onHttpException', network: 'onNetworkError', cancel: 'onCancel' }[failure]]()
    assert.equal(values.props.template.is_active, true)
    assert.equal(values.templateActive.value, false)
    assert.equal(values.showStatusModal.value, false)
    assert.match(values.statusRefreshError.value, /Estado guardado/)
    assert.equal(values.statusError.value, '')
    invoke('requestStatusToggle')
    assert.deepEqual(values.pendingStatus.value, { id: 17, is_active: true })
    values.props.template = { id: 99, is_active: true }
    assert.equal(values.templateActive.value, true)
  })
}

test('delayed same-template reload cannot erase a newer confirmed status or reverse the next intended action', async () => {
  const harness = state()
  const { values, invoke } = harness
  for (const active of [false, true]) {
    invoke('requestStatusToggle')
    const pending = invoke('toggleTemplateStatus')
    harness.request().options.onSuccess({ success: true, is_active: active })
    harness.resolve()
    await pending
  }
  values.props.template = { id: 17, is_active: false }
  invoke('syncConfirmedTemplateStatus', values.props.template)
  values.reloads[0].onSuccess()
  values.reloads[1].onNetworkError()
  assert.deepEqual(values.confirmedStatus.value, { id: 17, is_active: true })
  assert.equal(values.templateActive.value, true)
  assert.match(values.statusRefreshError.value, /Estado guardado/)
  invoke('requestStatusToggle')
  assert.deepEqual(values.pendingStatus.value, { id: 17, is_active: false })
  values.props.template = { id: 17, is_active: true }
  invoke('syncConfirmedTemplateStatus', values.props.template)
  values.reloads[1].onSuccess()
  assert.equal(values.confirmedStatus.value, null)
  assert.equal(values.statusRefreshError.value, '')
})

test('archive confirmation captures identity and stays mounted during failed or pending writes', () => {
  const { values, invoke } = state()
  invoke('confirmDelete')
  assert.equal(values.showDeleteModal.value, true)
  assert.equal(values.pendingDeleteId.value, 17)
  values.props.template.id = 99
  invoke('confirmDelete')
  values.archive.processing.value = true
  invoke('deleteTemplate')
  invoke('cancelDelete')
  assert.deepEqual(values.archiveCalls, [])
  assert.equal(values.showDeleteModal.value, true)
  values.archive.processing.value = false
  invoke('deleteTemplate')
  assert.deepEqual(values.archiveCalls, [['delete', [17]]])
  assert.equal(values.showDeleteModal.value, true)
  invoke('cancelDelete')
  assert.equal(values.showDeleteModal.value, false)
  assert.equal(values.pendingDeleteId.value, null)
})

test('real Vue template renders only the requested confirmation with shared component props and visible feedback', async () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'template-status' })
  const compiled = compileTemplate({ source: descriptor.template.content, filename: 'Show.vue', id: 'template-status',
    compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(compiled.errors, [])
  const imports = compiled.code.match(/import \{([^}]+)\} from "vue"/)[1].split(',').map(item => item.trim().split(' as '))
  const render = new Function(...imports.map(([, alias]) => alias), compiled.code.replace(/^import .* from "vue"\n/, '').replace('export function render', 'function render') + '\nreturn render')(...imports.map(([name]) => Vue[name]))
  const Dialog = { props: ['title', 'disabled', 'confirm', 'keepOpenOnConfirm'], setup: (props, { slots }) => () => Vue.h('section', { role: 'dialog', 'data-disabled': props.disabled }, [Vue.h('h2', props.title), slots.default?.()]) }
  const renderState = async (showStatusModal, showDeleteModal, pending, statusRefreshError = '') => {
    const bindings = { template: { id: 17, is_active: true, name: 'Retained template', content: '<p>Keep</p>', category: 'general', proposals_count: 0 },
      recentProposals: [], availableVariables: {}, calculateAcceptanceRate: 0, variablesCount: 0, templateVariables: [], statusBadges: {}, templateActive: true,
      showRawView: false, showStatusModal, showDeleteModal, pendingStatus: { id: 17, is_active: false }, statusProcessing: pending, statusError: pending ? '' : 'Could not confirm.',
      archive: { processing: Vue.ref(pending), failed: Vue.ref(!pending), message: Vue.ref('Archive failed.') }, statusRefreshError,
      ConfirmationModal: Dialog, Link: { setup: (_, { slots }) => () => Vue.h('a', slots.default?.()) },
      getCategoryIcon: () => 'span', getCategoryLabel: value => value, getCategoryColor: () => ({ bg: '', text: '' }), formatDateTime: () => '—' }
    for (const [name, type] of Object.entries(script.bindings)) {
      if (type === 'setup-const' && /^[A-Z].*Icon$/.test(name)) bindings[name] = { render: () => Vue.h('svg', { 'aria-hidden': 'true' }) }
    }
    const app = Vue.createSSRApp({ render() { return render.call(this, this, [], bindings, bindings, {}, {}) } })
    app.config.globalProperties.$t = trans
    app.config.globalProperties.route = () => '#'
    return renderToString(app)
  }
  assert.doesNotMatch(await renderState(false, false, false), /role="dialog"/)
  const failedStatus = await renderState(true, false, false)
  assert.equal((failedStatus.match(/role="dialog"/g) || []).length, 1)
  assert.match(failedStatus, /status_modal_deactivate_title/)
  assert.match(failedStatus, /role="alert"[^>]*>Could not confirm/)
  const pendingStatus = await renderState(true, false, true)
  assert.match(pendingStatus, /data-disabled="true"/)
  assert.match(pendingStatus, /role="status"[^>]*>A actualizar o estado/)
  const failedArchive = await renderState(false, true, false)
  assert.equal((failedArchive.match(/role="dialog"/g) || []).length, 1)
  assert.match(failedArchive, /role="alert"[^>]*>Archive failed/)
  assert.match(await renderState(false, false, false, 'State saved; refresh failed.'), /role="alert"[^>]*>State saved; refresh failed/)
  assert.match(source, /@confirmed="toggleTemplateStatus"/)
  assert.match(source, /@canceled="cancelStatusToggle"/)
  assert.doesNotMatch(source, /@confirm=|@close=|#content|#title|:show="show(?:Status|Delete)Modal"/)
})
