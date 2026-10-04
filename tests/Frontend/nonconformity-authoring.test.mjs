import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc'

const read = name => readFileSync(new URL(`../../resources/js/Pages/VAPNonConformities/${name}.vue`, import.meta.url), 'utf8')

for (const name of ['Index', 'Show', 'NonConformityForm', 'Create', 'Edit', 'NonConformityLifecycle', 'NonConformityObservations']) {
  test(`${name} compiles with one root and persistent error feedback`, () => {
    const source = read(name)
    const { descriptor, errors } = parse(source)
    assert.deepEqual(errors, [])
    assert.equal(descriptor.template.ast.children.filter(node => node.type === 1).length, 1)
    const script = compileScript(descriptor, { id: name })
    assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: name, id: name, compilerOptions: { bindingMetadata: script.bindings } }).errors, [])
    if (['Create', 'Edit'].includes(name)) {
      assert.match(source, /<Head title=/)
    } else {
      assert.match(source, /role="alert"/)
    }
  })
}

test('lifecycle has explicit stages, request identity, retained errors and closed observations-only editing', () => {
  const source = read('NonConformityLifecycle')
  assert.match(source, /crypto.randomUUID\(\)/)
  assert.match(source, /workflow_revision: props.record.workflow_revision/)
  assert.match(source, /if \(form.processing \|\| !selected.value\) return/)
  assert.match(source, /props.can\[action\]/)
  assert.match(source, /if \(props.record.deleted_at\) return \[\]/)
  assert.match(source, /onNetworkError:/)
  assert.match(source, /onHttpException:/)
  assert.match(source, /aria-describedby="transition-errors"/)
  assert.match(read('Edit'), /NonConformityObservations v-if="nonConformity.status === 'closed'"/)
  assert.match(read('NonConformityObservations'), /useForm\(\{ comments:/)
  assert.doesNotMatch(read('NonConformityForm'), /value: 'closed', label:/)
})

test('form submits only editable action fields and explicitly represents an empty multipart list', () => {
  const source = read('NonConformityForm')
  const body = source.match(/function cloneInitialActions\(\) \{([\s\S]*?)\n\}/)[1]
  const clone = new Function('props', 'formatDateForInput', `return () => {${body}}`)({
    nonConformity: { actions: [
      { id: 1, correction: 'Keep', due_at: '2026-10-10', lab_id: 99, approved_at: 'forged' },
      { id: 2, correction: 'Archived', deleted_at: '2026-10-01' },
    ] },
  }, value => value || '')
  assert.deepEqual(clone(), [{ id: 1, correction: 'Keep', corrective_action: '', due_at: '2026-10-10' }])
  assert.match(source, /actions_present: true/)
  assert.match(source, /if \(form.processing\) return/)
})

test('archive controls use permission gates, retained confirmation and shared error handling', () => {
  for (const name of ['Index', 'Show']) {
    const source = read(name)
    assert.match(source, /useRecordArchive/)
    assert.match(source, /can.archive && !/)
    assert.match(source, /can.edit && !/)
    assert.match(source, /:keep-open-on-confirm="true"/)
    assert.match(source, /:disabled="archive.processing.value"/)
    assert.doesNotMatch(source, /router.delete\(/)
  }
  assert.match(read('Show'), /can.restore && nonConformity.deleted_at/)
  assert.match(read('Show'), /Arquivada · Histórico preservado/)
  assert.match(read('Index'), /archived: props.filters.archived \|\| undefined/)
})

test('evidence failures retain the form and selected files with explicit retry guidance', () => {
  const source = read('NonConformityForm')
  assert.match(source, /onHttpException: response =>/)
  assert.match(source, /response.status === 409/)
  assert.match(source, /onNetworkError:/)
  assert.match(source, /onCancel:/)
  assert.match(source, /form.setError\('request'/)
  assert.match(source, /<FileInput[^>]*:disabled="form.processing"/)
  const submission = source.slice(source.indexOf('function submit()'), source.indexOf('function focusFirstErrorSection'))
  assert.doesNotMatch(submission, /form.reset\(|selectedAttachmentFiles.value = \[\]/)
  assert.match(submission, /\.\.\.options/)
})

test('report exports use accepted filters including archive scope and do not navigate away on failure', async () => {
  const source = read('Index')
  const body = source.match(/function exportReport\(type\) \{([\s\S]*?)\n\}/)[1]
  const requests = []
  const filtering = { value: false }
  const report = new Function('props', 'filtering', 'downloads', 'route', 'URLSearchParams', `return type => {${body}}`)(
    { filters: { archived: 1, search: '100%_match', severity: 'high', start_date: '2026-10-01', end_date: null } },
    filtering, { download: url => requests.push(url) }, name => `/${name}`, URLSearchParams,
  )
  report('excel')
  const url = new URL(requests[0], 'https://example.test')
  assert.equal(url.searchParams.get('archived'), '1')
  assert.equal(url.searchParams.get('search'), '100%_match')
  assert.equal(url.searchParams.get('start_date'), '2026-10-01')
  assert.equal(url.searchParams.has('end_date'), false)
  filtering.value = true
  report('pdf')
  assert.equal(requests.length, 1)
  for (const name of ['Index', 'Show']) {
    assert.match(read(name), /useFileDownload/)
    assert.match(read(name), /role="status">A preparar exportação/)
    assert.match(read(name), /:disabled="downloads.processing.value/)
    assert.doesNotMatch(read(name), /window.location.assign/)
  }
})

test('filter errors persist and summary scope is explicit', () => {
  const source = read('Index')
  assert.match(source, /onError: errors =>/)
  assert.match(source, /onNetworkError:/)
  assert.match(source, /onBeforeUnmount\(\(\) => window.clearTimeout\(filterTimeout\)\)/)
  assert.match(source, /Indicadores e exportações:/)
  assert.match(source, /stats\?\.attention/)
  assert.match(source, /Datas de relato dos registos filtrados/)
  assert.doesNotMatch(source, /Registos e resoluções dos últimos meses/)
})
