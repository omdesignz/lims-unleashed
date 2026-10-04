import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const source = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Index.vue', import.meta.url), 'utf8')
const body = source.match(/const executeMarkAsExecuted = async \(\) => \{([\s\S]*?)\n\}/)[1]

function fixture({ processing = false, failure = false, validation = false } = {}) {
  const events = []
  const selectedTaskIds = { value: [4, 8] }
  const completionError = { value: '' }
  const completionRequest = {
    processing, hasErrors: false, task_ids: [],
    async post(url, options) {
      events.push('post')
      if (failure) {
        this.hasErrors = validation
        if (validation) return
        throw new Error('Request failed')
      }
      options.onSuccess()
    },
  }
  const invoke = new Function('completionRequest', 'completionError', 'selectedTaskIds', 'route', 'clearSelection', 'router', `return async () => {${body}}`)(
    completionRequest, completionError, selectedTaskIds, value => value,
    () => { events.push('clear'); selectedTaskIds.value = [] },
    { reload: () => events.push('reload') },
  )
  return { invoke, events, selectedTaskIds, completionError, completionRequest }
}

test('bulk completion blocks repeated requests and copies the selected task ids', async () => {
  const state = fixture({ processing: true })
  await state.invoke()
  assert.deepEqual(state.events, [])
  state.completionRequest.processing = false
  const previousSelection = state.selectedTaskIds.value
  await state.invoke()
  assert.deepEqual(state.events, ['post', 'clear', 'reload'])
  assert.deepEqual(state.completionRequest.task_ids, [4, 8])
  assert.notEqual(state.completionRequest.task_ids, previousSelection)
})

test('failed completion retains selection and exposes validation or request errors', async () => {
  for (const validation of [false, true]) {
    const state = fixture({ failure: true, validation })
    await state.invoke()
    assert.deepEqual(state.events, ['post'])
    assert.deepEqual(state.selectedTaskIds.value, [4, 8])
    assert.equal(Boolean(state.completionError.value), !validation)
  }
})

test('maintenance page compiles with accessible errors and completion pending state', () => {
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
  const script = compileScript(descriptor, { id: 'maintenance-completion' })
  const template = compileTemplate({ source: descriptor.template.content, filename: 'Index.vue', id: 'maintenance-completion', compilerOptions: { bindingMetadata: script.bindings } })
  assert.deepEqual(template.errors, [])
  assert.match(source, /role="alert"/)
  assert.match(source, /:aria-busy="completionRequest.processing"/)
  assert.match(source, /completionRequest = useHttp/)
  assert.doesNotMatch(body, /axios|alert\(/)
})

test('single-row completion uses recorded evidence, rejects repeat clicks and retains failures', async () => {
  const rowBody = source.match(/const markAsExecuted = async \(task\) => \{([\s\S]*?)\n\}/)[1]
  assert.doesNotMatch(rowBody, /result:|Concluído via lista|axios/)
  const completionError = { value: '' }
  const events = []
  const request = { processing: false, task_ids: [], async post(url, options) { events.push(this.task_ids); options.onSuccess() } }
  const invoke = new Function('props', 'completionRequest', 'completionError', 'confirm', 'route', 'router', `return async (task) => {${rowBody}}`)(
    { can: { edit: true } }, request, completionError, () => true, value => value, { reload: () => events.push('reload') },
  )
  await invoke({ id: 9 })
  assert.deepEqual(events, [[9], 'reload'])
  request.processing = true
  await invoke({ id: 10 })
  assert.deepEqual(events, [[9], 'reload'])
  request.processing = false
  request.post = async () => { throw new Error('Unavailable') }
  await invoke({ id: 10 })
  assert.match(completionError.value, /Não foi possível/)
})

test('task authoring omits server-owned identity and derives edit defaults from an explicit field list', () => {
  const create = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Create.vue', import.meta.url), 'utf8')
  const defaults = create.match(/const defaults = \{([\s\S]*?)\n\}/)[1]
  assert.doesNotMatch(defaults, /maintenance_task_no|maintenance_task_year|seq|previous_date|next_date|is_executed|calibration_status|result:/)
  assert.match(defaults, /due_date: props.today/)
  assert.doesNotMatch(create, /toISOString/)
  assert.match(create, /Object\.entries\(defaults\)/)
  assert.match(create, /if \(form.processing\) return/)
  assert.match(create, /form\.put\(route\('vap-maintenance.tasks.update'/)
  assert.match(create, /:disabled="Boolean\(task\)"/)
  assert.doesNotMatch(create, /router\.visit/)
})

test('result entry is available before completion and print names a supported export type', () => {
  const detail = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Show.vue', import.meta.url), 'utf8')
  assert.match(detail, /<button v-if="can.edit"[^>]+@click="recordResult"/)
  assert.match(detail, /completionForm = useForm\(\{ is_executed: true \}\)/)
  assert.match(detail, /type: 'tasks'/)
  assert.match(detail, /task_id: props.task.id/)
  assert.match(detail, /onSuccess: \(\) => \{\s+completionForm.clearErrors\(\)/)
  assert.match(detail, /equipmentHistory = computed\(\(\) => props.equipmentHistory \?\? null\)/)
  assert.doesNotMatch(detail, /resultForm.next_date|axios|onMounted|Duplicar/)
})

for (const filename of ['Create.vue', 'Show.vue']) {
  test(`${filename} compiles with one root and persistent validation feedback`, () => {
    const content = readFileSync(new URL(`../../resources/js/Pages/VAPMaintenance/Tasks/${filename}`, import.meta.url), 'utf8')
    const { descriptor, errors } = parse(content)
    assert.deepEqual(errors, [])
    assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
    const script = compileScript(descriptor, { id: filename })
    const template = compileTemplate({ source: descriptor.template.content, filename, id: filename, compilerOptions: { bindingMetadata: script.bindings } })
    assert.deepEqual(template.errors, [])
    assert.match(content, /role="alert"/)
  })
}

test('lifecycle requests retain selection and the modal on failure and reset stale action state', async () => {
  const lifecycleBody = source.match(/const executeLifecycle = async \(action, newDate = null\) => \{([\s\S]*?)\n\}/)[1]
  for (const failure of ['validation', 'network', false]) {
    const events = []
    const selectedTaskIds = { value: [4, 8] }
    const showRescheduleModal = { value: true }
    const completionError = { value: '' }
    const completionRequest = {
      processing: false, hasErrors: failure === 'validation',
      async post(url, options) {
        events.push('post')
        if (failure === 'validation') return
        if (failure === 'network') throw new Error('Unavailable')
        options.onSuccess()
      },
    }
    const invoke = new Function('completionRequest', 'completionError', 'selectedTaskIds', 'showRescheduleModal', 'clearSelection', 'route', 'router', `return async (action, newDate = null) => {${lifecycleBody}}`)(
      completionRequest, completionError, selectedTaskIds, showRescheduleModal,
      () => { selectedTaskIds.value = []; events.push('clear') }, value => value,
      { reload: () => events.push('reload') },
    )
    await invoke('reschedule', '2026-12-02')
    assert.equal(completionRequest.action, 'reschedule')
    assert.equal(completionRequest.new_date, '2026-12-02')
    assert.deepEqual(completionRequest.task_ids, [4, 8])
    assert.deepEqual(selectedTaskIds.value, failure ? [4, 8] : [])
    assert.equal(showRescheduleModal.value, Boolean(failure))
    assert.equal(Boolean(completionError.value), failure === 'network')
    completionRequest.processing = true
    await invoke('restore')
    assert.equal(events.filter(event => event === 'post').length, 1)
  }
  assert.match(body, /completionRequest.action = 'mark_executed'/)
  assert.match(body, /completionRequest.new_date = null/)
})

test('archive UI offers restoration without false deletion or notification promises', () => {
  assert.match(source, /can.restore && archived/)
  assert.match(source, /value="restore">Restaurar/)
  assert.match(source, /Arquivo de tarefas/)
  assert.match(source, /can.export && !archived/)
  assert.match(source, /aria-label="Seleccionar todas as tarefas desta página"/)
  assert.match(source, /!task.deleted_at/)
  assert.match(source, /rescheduleDate = ref\(props.today\)/)
  assert.match(source, /watch\(\(\) => props.tasks, clearSelection\)/)
  assert.doesNotMatch(source, /axios|sendRescheduleNotification|send_notification|não pode ser revertida|toISOString/)
  const detail = readFileSync(new URL('../../resources/js/Pages/VAPMaintenance/Tasks/Show.vue', import.meta.url), 'utf8')
  assert.match(detail, /useRecordArchive/)
  assert.match(detail, /if \(!props.can.delete \|\| archiving.value\) return/)
  assert.match(detail, /:aria-busy="archiving"/)
  assert.match(detail, /archiveMessage/)
})
