import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
const downloadSource = read('Composables/useFileDownload.js')
const dashboard = read('Pages/VAPMaintenance/Dashboard.vue')
const index = read('Pages/VAPMaintenance/Tasks/Index.vue')

function downloader(fetch) {
  const events = []
  const anchor = { click: () => events.push('click'), remove: () => events.push('remove') }
  const make = new Function('ref', 'fetch', 'URL', 'document', 'setTimeout', `${downloadSource.replace("import { ref } from 'vue'", '').replace('export function', 'function')}; return useFileDownload()`)
  const state = make(value => ({ value }), fetch,
    { createObjectURL: () => { events.push('blob'); return 'blob:download' }, revokeObjectURL: () => events.push('revoke') },
    { createElement: () => anchor, body: { appendChild: () => events.push('append') } }, callback => callback(),
  )
  return { ...state, events, anchor }
}

const response = (overrides = {}) => ({
  ok: true, headers: { get: () => 'attachment; filename="maintenance_tasks.xlsx"' },
  blob: async () => ({}), ...overrides,
})

test('downloads are guarded, use private authenticated GETs and release temporary DOM and URLs', async () => {
  let resolve
  let observed
  const state = downloader((url, options) => {
    observed = { url, options }
    return new Promise(done => { resolve = done })
  })
  const pending = state.download('/maintenance/export?format=excel')
  assert.equal(state.processing.value, true)
  assert.equal(await state.download('/duplicate'), false)
  assert.equal(observed.options.credentials, 'same-origin')
  assert.equal(observed.options.headers.Accept, 'application/json')
  resolve(response())
  assert.equal(await pending, true)
  assert.equal(state.processing.value, false)
  assert.equal(state.anchor.download, 'maintenance_tasks.xlsx')
  assert.deepEqual(state.events, ['blob', 'append', 'click', 'remove', 'revoke'])
})

test('validation, permission, expired-session and network failures expose errors instead of fake downloads', async () => {
  const failures = [
    async () => response({ ok: false, json: async () => ({ errors: { date_to: ['Reduza o intervalo.'] } }) }),
    async () => response({ ok: false, json: async () => ({}) }),
    async () => response({ headers: { get: () => null } }),
    async () => { throw new Error('offline') },
    async () => response({ blob: async () => { throw new Error('broken stream') } }),
  ]
  for (const fetch of failures) {
    const state = downloader(fetch)
    assert.equal(await state.download('/maintenance/export'), false)
    assert.ok(state.error.value)
    assert.equal(state.processing.value, false)
    assert.deepEqual(state.events, [])
  }
})

test('export modal retains controls on failure and closes only after a real download', async () => {
  const body = index.match(/const proceedExport = async \(\) => \{([\s\S]*?)\n\}/)[1]
  for (const success of [true, false]) {
    const events = []
    const modal = { value: true }
    const invoke = new Function('downloading', 'props', 'exportFormat', 'exportRange', 'download', 'route', 'showExportModal', `return async () => {${body}}`)(
      { value: false }, { filters: { status: 'planned', cost_max: 0, archived: false } }, { value: 'csv' }, { value: 'filtered' },
      async value => { events.push(value); return success }, (name, params) => ({ name, params }), modal,
    )
    await invoke()
    assert.equal(modal.value, !success)
    assert.equal(events[0].params.cost_max, 0)
    assert.equal(events[0].params.status, 'planned')
    assert.equal('fields' in events[0].params, false)
    assert.equal('archived' in events[0].params, false)
  }
})

test('dashboard completion requires authority and existing evidence, never fabricates a result', async () => {
  const body = dashboard.match(/const markAsExecuted = async \(task\) => \{([\s\S]*?)\n\}/)[1]
  assert.doesNotMatch(body, /result:|router.put/)
  const events = []
  const error = { value: '' }
  const request = { processing: false, task_ids: [], async post(url, options) { events.push(url); options.onSuccess() } }
  const props = { can: { edit: false } }
  const invoke = new Function('props', 'completionRequest', 'completionError', 'confirm', 'route', 'router', `return async task => {${body}}`)(props, request, error, () => true, value => value, { reload: () => events.push('reload') })
  await invoke({ id: 8 })
  assert.deepEqual(events, [])
  props.can.edit = true
  await invoke({ id: 8 })
  assert.deepEqual(request.task_ids, [8])
  assert.deepEqual(events, ['vap-maintenance.tasks.bulk-update', 'reload'])
  request.processing = true
  await invoke({ id: 9 })
  assert.equal(events.length, 2)
  request.processing = false
  request.post = async () => { throw new Error('offline') }
  await invoke({ id: 9 })
  assert.ok(error.value)
})

test('reporting UI uses honest summaries, canonical exports, date-only status and cancellable chart loads', () => {
  assert.match(index, /Vencem este mês/)
  assert.match(index, /stats\?\.total_cost/)
  assert.doesNotMatch(index, /monthly_average|exportFields|window.open/)
  assert.match(index, /task.days_until_due \?\? Infinity/)
  assert.match(dashboard, /task.days_until_due \?\? Infinity/)
  assert.match(dashboard, /type: 'calendar'/)
  assert.match(dashboard, /Descarregar agenda em Excel/)
  assert.doesNotMatch(dashboard, /report_type: 'schedule'|filters: JSON.stringify|Concluído através do painel/)
  assert.match(dashboard, /chartRequest\?\.abort\(\)/)
  assert.match(dashboard, /if \(chartRequest === request\) chartData.value = data/)
  assert.match(dashboard, /chartLoading \|\| chartError \? '—'/)
  assert.match(dashboard, /criadas no período/)
})

for (const [name, source] of [['Index', index], ['Dashboard', dashboard]]) {
  test(`${name} reporting controls compile with one root and persistent errors`, () => {
    const { descriptor, errors } = parse(source)
    assert.deepEqual(errors, [])
    assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
    const script = compileScript(descriptor, { id: name })
    const template = compileTemplate({ source: descriptor.template.content, filename: `${name}.vue`, id: name, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
    assert.match(source, /role="alert"/)
    assert.match(source, /role="status"/)
    assert.match(source, /:aria-busy="downloading"/)
    assert.match(source, /<Head title="[^"]+" \/>/)
  })
}

test('PDF reports are controlled documents built from renderer-compatible blocks', () => {
  for (const name of ['tasks', 'calendar']) {
    const source = readFileSync(new URL(`../../resources/views/exports/maintenance/${name}.blade.php`, import.meta.url), 'utf8')
    // The shared page gives them the letterhead, document number and page of total; they carry no styles of their own.
    assert.match(source, /@extends\('PDFs\.partials\.controlled-layout'\)/)
    assert.match(source, /\$documentTitle = '(?:Relatório|Calendário) de Manutenção'/)
    assert.match(source, /<table class="doc-results doc-plain"/)
    assert.doesNotMatch(source, /<style|hero|color: #/)
    assert.doesNotMatch(source, /<span class="(?:summary-label|summary-value|task-pill)">/)
  }
})
