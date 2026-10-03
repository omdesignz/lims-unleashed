import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { approvedResultCounts, sampleDate, sampleText, sampleStatusTones, sampleTypeLabels } from '../../resources/js/Utils/samplePresentation.js'

const page = readFileSync(new URL('../../resources/js/Pages/VAPSamples/Show.vue', import.meta.url), 'utf8')
const queue = readFileSync(new URL('../../resources/js/Pages/VAPSamples/Queue.vue', import.meta.url), 'utf8')

test('queue quick view links to full record and shares safe date formatting', () => {
  assert.match(queue, /route\('vap_samples.show', selected.id\)/)
  assert.match(queue, /Abrir ficha completa/)
  assert.match(queue, /sampleDate as date/)
})

test('sample presentation handles absent values, literal text, arrays and zero', () => {
  assert.equal(sampleText(0), '0')
  assert.equal(sampleText(null), 'Não registado')
  assert.equal(sampleText({ internal: true }), 'Não registado')
  assert.equal(sampleText(['Microbiologia', null, {}, 'Química']), 'Microbiologia, Química')
  assert.equal(sampleText('<script>alert(1)</script>'), '<script>alert(1)</script>')
  assert.doesNotMatch(page, /v-html|JSON\.stringify|<pre/)
})

test('dates distinguish missing and malformed data and preserve date-only day', () => {
  assert.equal(sampleDate(null), 'Por registar')
  assert.equal(sampleDate('not-a-date'), 'Data inválida')
  assert.match(sampleDate('2026-09-27'), /27/)
  assert.match(sampleDate('2026-09-27T12:30:00', true), /12:30/)
})

test('result summary uses actual result counts, not analysis count', () => {
  assert.deepEqual(approvedResultCounts(), { approved: 0, total: 0 })
  assert.deepEqual(approvedResultCounts([{ results_summary: { approved: 2, total: 4 } }, {}, { results_summary: { approved: '1', total: '2' } }]), { approved: 3, total: 6 })
  assert.equal(sampleStatusTones.EN_PAUSA, 'review')
  assert.equal(sampleStatusTones.COMPLETADO, 'complete')
  assert.equal(sampleTypeLabels.MATERIA_PRIMA, 'Matéria-prima')
  assert.equal(sampleTypeLabels.RAW_MATERIAL, 'Matéria-prima')
  assert.match(page, /info.quality_control_purpose/)
  assert.match(page, /info.qc_decision/)
})

test('detail preserves approved geometry and keyboard accessible tabs', () => {
  for (const token of ['lab-page-head', 'lab-metrics', 'lab-record-grid', 'lab-record-rail', 'TabGroup', 'TabList', 'TabPanel']) assert.ok(page.includes(token), token)
  assert.doesNotMatch(page, /ds-card|ds-panel|font-black|transition-all|<Transition/)
  assert.match(page, /route\('vap_samples.queue'\)/)
  assert.match(page, /A análise começa aqui/)
  assert.match(page, /Nenhum descarte registado/)
})

test('quality decisions require explicit input and permission, with accessible feedback', () => {
  assert.match(page, /useForm\(\{ decision: '', notes: '' \}\)/)
  assert.match(page, /v-if="canEdit"/)
  assert.match(page, /hasPermission\('edit_samples'\)/)
  assert.match(page, /qcDecisionForm.processing \|\| !qcDecisionForm.decision \|\| releaseDecisionBlocked/)
  assert.match(page, /aria-describedby="sample-qc-decision-help"/)
  assert.match(page, /role="alert"/)
  assert.match(page, /role="status"/)
  assert.match(page, /onSuccess: \(\) => qcDecisionForm.reset\(\)/)
  assert.match(page, /target="_blank" rel="noopener"/)
})
