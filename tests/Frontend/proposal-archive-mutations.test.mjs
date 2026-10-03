import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import * as Vue from 'vue'
import { renderToString } from '@vue/server-renderer'
import { router } from '@inertiajs/vue3'
import { useRecordArchive } from '../../resources/js/Composables/useRecordArchive.js'

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
const proposals = read('Pages/Proposals/Index.vue')
const agreements = read('Pages/ProposalComplianceAgreements/Index.vue')
const templates = read('Pages/ProposalTemplates/Index.vue')
const canonical = read('Pages/VAPProposals/Index.vue')
const feedback = read('Components/archive-mutation-feedback.vue')
const body = (source, name) => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}\\n`))[1]

test('native Inertia archive form uses DELETE/PATCH and blocks immediate duplicate submissions', async () => {
  const originalDelete = router.delete
  const originalPatch = router.patch
  const calls = []
  let success = 0
  router.delete = (url, options) => calls.push({ method: 'delete', url, data: options.data, options })
  router.patch = (url, data, options) => calls.push({ method: 'patch', url, data, options })
  const scope = Vue.effectScope()
  try {
    const archive = scope.run(() => useRecordArchive({ destroyUrl: () => '/proposals/destroy', restoreUrl: () => '/proposals/restore', onSuccess: () => success++ }))
    const ids = [7, 9]
    assert.equal(archive.submit('delete', []), false)
    assert.equal(archive.submit('show', ids), false)
    assert.equal(archive.submit('delete', ids), true)
    ids.push(11)
    assert.equal(archive.submit('delete', ids), false)
    assert.equal(calls.length, 1)
    assert.equal(calls[0].method, 'delete')
    assert.deepEqual(calls[0].data, { recordIds: [7, 9] })
    assert.equal(calls[0].options.preserveState, true)
    calls[0].options.onStart({})
    calls[0].options.onError({ recordIds: 'Accepted proposal cannot be archived.' })
    calls[0].options.onFinish({})
    assert.equal(archive.processing.value, false)
    assert.equal(archive.failed.value, true)
    assert.equal(archive.message.value, 'Accepted proposal cannot be archived.')
    assert.deepEqual(archive.form.recordIds, [7, 9])
    assert.equal(success, 0)
    archive.submit('restore', [7, 9])
    assert.equal(calls[1].method, 'patch')
    assert.equal(calls[1].url, '/proposals/restore')
    assert.equal(archive.failed.value, false)
    await calls[1].options.onSuccess({})
    calls[1].options.onFinish({})
    assert.equal(success, 1)
    assert.deepEqual(archive.form.recordIds, [])
    assert.equal(archive.message.value, 'Operação concluída.')
  } finally {
    router.delete = originalDelete
    router.patch = originalPatch
    scope.stop()
  }
})

test('network, HTTP, cancellation and synchronous failures remain visible and preserve submitted identity', () => {
  const originalDelete = router.delete
  const calls = []
  router.delete = (url, options) => calls.push(options)
  const scope = Vue.effectScope()
  try {
    const archive = scope.run(() => useRecordArchive({ destroyUrl: ids => `/vap-proposals/${ids[0]}` }))
    for (const fail of [options => options.onNetworkError(new Error('Offline')), options => options.onHttpException({ status: 403 }), options => options.onHttpException({ status: 500 }), options => options.onCancel()]) {
      archive.submit('delete', [17])
      const options = calls.at(-1)
      options.onStart({})
      fail(options)
      options.onFinish({})
      assert.equal(archive.failed.value, true)
      assert.match(archive.message.value, /Actualize a lista/)
      assert.deepEqual(archive.form.recordIds, [17])
      assert.equal(archive.processing.value, false)
    }
    router.delete = () => { throw new Error('Cannot dispatch') }
    assert.equal(archive.submit('delete', [19]), false)
    assert.equal(archive.processing.value, false)
    assert.equal(archive.failed.value, true)
    assert.deepEqual(archive.form.recordIds, [19])
    assert.equal(archive.submit('restore', [19]), false)
  } finally {
    router.delete = originalDelete
    scope.stop()
  }
})

test('proposal confirmation snapshots selection and cannot submit while busy', () => {
  const archive = { processing: Vue.ref(false), submit: (...args) => calls.push(args) }
  const calls = []
  const action = Vue.ref(null)
  const pendingIDs = Vue.ref([])
  const showDeleteConfirmation = Vue.ref(false)
  const request = new Function('archive', 'action', 'pendingIDs', 'showDeleteConfirmation', 'operation', 'ids', body(proposals, 'requestArchive'))
  const ids = [31]
  request(archive, action, pendingIDs, showDeleteConfirmation, 'delete', ids)
  ids.push(32)
  assert.deepEqual(pendingIDs.value, [31])
  const confirm = new Function('archive', 'action', 'pendingIDs', 'showDeleteConfirmation', body(proposals, 'confirmAction'))
  archive.processing.value = true
  confirm(archive, action, pendingIDs, showDeleteConfirmation)
  assert.equal(calls.length, 0)
  archive.processing.value = false
  confirm(archive, action, pendingIDs, showDeleteConfirmation)
  assert.deepEqual(calls, [['delete', [31]]])
  assert.equal(showDeleteConfirmation.value, false)
  assert.doesNotMatch(proposals, /openslideover|openSlideover|form\.id|router\.get\(route\('proposals\.(destroy|restore)'/)
})

test('agreement archive confirmation snapshots selection and has no manual consent controls', () => {
  const calls = []
  const archive = { processing: Vue.ref(false), submit: (...args) => calls.push(args) }
  const action = Vue.ref(null)
  const pendingIDs = Vue.ref([])
  const showDeleteConfirmation = Vue.ref(false)
  const request = new Function('archive', 'action', 'pendingIDs', 'showDeleteConfirmation', 'operation', 'ids', body(agreements, 'requestArchive'))
  const ids = [41, 42]
  request(archive, action, pendingIDs, showDeleteConfirmation, 'restore', ids)
  ids.push(99)
  assert.deepEqual(pendingIDs.value, [41, 42])
  const confirm = new Function('archive', 'action', 'pendingIDs', 'showDeleteConfirmation', body(agreements, 'confirmAction'))
  archive.processing.value = true
  confirm(archive, action, pendingIDs, showDeleteConfirmation)
  assert.equal(calls.length, 0)
  archive.processing.value = false
  confirm(archive, action, pendingIDs, showDeleteConfirmation)
  assert.deepEqual(calls, [['restore', [41, 42]]])
  assert.match(agreements, /:create-action="false"/)
  assert.doesNotMatch(agreements, /useForm|edit_slide|slide-over|proposalcomplianceagreements\\.(store|update|edit|create)/)
})

test('canonical delete has wired footer buttons and guards missing or pending targets', () => {
  const calls = []
  const archive = { processing: Vue.ref(false), submit: (...args) => calls.push(args) }
  const selectedProposal = Vue.ref(null)
  const showDeleteModal = Vue.ref(true)
  const source = canonical.match(/const deleteProposal = \(\) => \{([\s\S]*?)\n\}/)[1]
  const execute = new Function('archive', 'selectedProposal', 'showDeleteModal', source)
  execute(archive, selectedProposal, showDeleteModal)
  selectedProposal.value = { id: 51 }
  archive.processing.value = true
  execute(archive, selectedProposal, showDeleteModal)
  assert.equal(calls.length, 0)
  archive.processing.value = false
  execute(archive, selectedProposal, showDeleteModal)
  assert.deepEqual(calls, [['delete', [51]]])
  assert.equal(showDeleteModal.value, false)
  assert.match(canonical, /<template #footer>/)
  assert.match(canonical, /@click="deleteProposal"/)
  assert.doesNotMatch(canonical, /@confirm="deleteProposal"/)
})

test('template confirmation freezes IDs, blocks repeats and uses the shared non-GET archive form', () => {
  const calls = []
  const archive = { processing: Vue.ref(false), submit: (...args) => calls.push(args) }
  const action = Vue.ref(null)
  const pendingIDs = Vue.ref([])
  const showDeleteConfirmation = Vue.ref(false)
  const request = new Function('archive', 'action', 'pendingIDs', 'showDeleteConfirmation', 'operation', 'ids', body(templates, 'requestArchive'))
  const ids = [71, 72]
  request(archive, action, pendingIDs, showDeleteConfirmation, 'delete', ids)
  ids.push(73)
  assert.deepEqual(pendingIDs.value, [71, 72])
  const confirm = new Function('archive', 'action', 'pendingIDs', 'showDeleteConfirmation', body(templates, 'confirmAction'))
  archive.processing.value = true
  confirm(archive, action, pendingIDs, showDeleteConfirmation)
  assert.equal(calls.length, 0)
  archive.processing.value = false
  confirm(archive, action, pendingIDs, showDeleteConfirmation)
  assert.deepEqual(calls, [['delete', [71, 72]]])
  assert.match(templates, /destroyUrl: \(\) => route\('proposaltemplates.destroy'\)/)
  assert.match(templates, /restoreUrl: \(\) => route\('proposaltemplates.restore'\)/)
  assert.doesNotMatch(templates, /useForm|slide-over|proposaltemplates\.(store|update)|router\.get\(route\('proposaltemplates\.(destroy|restore)'/)
})

test('archive feedback renders loading, success, retained error and empty states with static accessible cues', async () => {
  const { descriptor } = parse(feedback)
  const compiled = compileTemplate({ id: 'archive-feedback', source: descriptor.template.content, compilerOptions: { mode: 'function' } })
  assert.deepEqual(compiled.errors, [])
  const render = new Function('Vue', compiled.code)(Vue)
  for (const state of [
    { processing: false, message: '', failed: false },
    { processing: true, message: '', failed: false },
    { processing: false, message: 'Operação concluída.', failed: false },
    { processing: false, message: 'Local lab only.', failed: true },
  ]) {
    const app = Vue.createSSRApp({ setup: () => state, render })
    app.config.warnHandler = message => { throw new Error(message) }
    const html = await renderToString(app)
    if (state.processing) assert.match(html, /role="status"[^>]*>A actualizar o arquivo/)
    else if (state.failed) {
      assert.match(html, /role="alert"/)
      assert.match(html, /Local lab only/)
      assert.match(html, /<button[^>]*type="button"[^>]*>\s*Actualizar lista/)
    } else if (state.message) assert.match(html, /role="status"/)
    else assert.equal(html, '<!--v-if-->')
  }
})

test('all changed archive pages and feedback compile and expose busy row/bulk controls', () => {
  for (const [name, source] of Object.entries({ proposals, agreements, templates, canonical, feedback })) {
    const { descriptor, errors } = parse(source, { filename: name })
    assert.deepEqual(errors, [])
    const script = compileScript(descriptor, { id: name })
    const template = compileTemplate({ id: name, filename: name, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [], name)
  }
  for (const source of [proposals, agreements, templates]) {
    assert.match(source, /:action-processing="archive\.processing\.value/)
    assert.match(source, /:disabled="archive\.processing\.value/)
    assert.doesNotMatch(source, /hover:scale-150|router\.get\(route\('[^']*\.(destroy|restore)'/)
  }
})
