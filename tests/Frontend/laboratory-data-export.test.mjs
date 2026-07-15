import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const dataExportsSource = readFileSync(
    new URL('../../resources/js/Pages/Analysis/DataExports.vue', import.meta.url),
    'utf8',
)
const analysisIndexSource = readFileSync(
    new URL('../../resources/js/Pages/Analysis/Index.vue', import.meta.url),
    'utf8',
)
const layoutSource = readFileSync(
    new URL('../../resources/js/Shared/Layouts/Layout.vue', import.meta.url),
    'utf8',
)

test('laboratory data exports expose pending work and result audit views', () => {
    assert.match(dataExportsSource, /Folha de análises pendentes/)
    assert.match(dataExportsSource, /Auditoria de resultados/)
    assert.match(dataExportsSource, /analysis\.data-exports\.download/)
    assert.match(dataExportsSource, /canViewPending/)
    assert.match(dataExportsSource, /canViewAudit/)
    assert.match(dataExportsSource, /filterState\.department_id/)
    assert.match(dataExportsSource, /filterState\.date_from/)
    assert.match(dataExportsSource, /filterState\.date_to/)
    assert.match(dataExportsSource, /filterState\.stage/)
    assert.match(dataExportsSource, /Exportar XLSX/)
})

test('laboratory data exports are reachable from analysis and primary navigation', () => {
    assert.match(analysisIndexSource, /analysis\.data-exports\.index/)
    assert.match(analysisIndexSource, /Folha diária/)
    assert.match(layoutSource, /analysis\.data-exports\.index/)
    assert.match(layoutSource, /Dados laboratoriais/)
    assert.match(layoutSource, /view_analysis/)
    assert.match(layoutSource, /view_results/)
})
