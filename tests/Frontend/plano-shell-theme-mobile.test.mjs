import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import { THEME_CHROME, applyThemeToDocument, resolveInitialDark } from '../../resources/js/Composables/useTheme.js'
import { areaGlyphs, bottomBarAreas } from '../../resources/js/Support/navigationAreas.js'

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')

const layout = read('resources/js/Shared/Layouts/Layout.vue')
const portalLayout = read('resources/js/Shared/Layouts/PortalLayout.vue')
const areaBar = read('resources/js/Shared/Navigation/area-bar.vue')
const sidebar = read('resources/js/Shared/Navigation/app-sidebar.vue')
const appBlade = read('resources/views/app.blade.php')
const appCss = read('resources/css/app.css')

const areas = ['home', 'samples', 'analysis', 'certificates', 'commercial', 'inventory', 'quality', 'admin'].map((key) => ({ key }))

test('shell files compile', () => {
  for (const [filename, source] of [['Layout.vue', layout], ['PortalLayout.vue', portalLayout], ['area-bar.vue', areaBar], ['app-sidebar.vue', sidebar]]) {
    const { descriptor, errors } = parse(source, { filename })
    assert.deepEqual(errors, [])
    compileScript(descriptor, { id: filename })
    assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: filename }).errors, [])
  }
})

test('the phone bar keeps the first four areas when the current area is among them', () => {
  assert.deepEqual(bottomBarAreas(areas, 'samples').map((area) => area.key), ['home', 'samples', 'analysis', 'certificates'])
  assert.deepEqual(bottomBarAreas(areas, null).map((area) => area.key), ['home', 'samples', 'analysis', 'certificates'])
})

test('the phone bar gives its last slot to the current area when it is further along', () => {
  assert.deepEqual(bottomBarAreas(areas, 'inventory').map((area) => area.key), ['home', 'samples', 'analysis', 'inventory'])
  assert.deepEqual(bottomBarAreas(areas, 'unknown').map((area) => area.key), ['home', 'samples', 'analysis', 'certificates'])
  assert.deepEqual(bottomBarAreas(areas.slice(0, 2), 'samples').map((area) => area.key), ['home', 'samples'])
})

test('every area has a glyph and a short name that fits the phone bar', () => {
  for (const { key } of areas) {
    assert.ok(areaGlyphs[key]?.icon, `${key} has a glyph`)
    assert.ok(areaGlyphs[key].short.length <= 10, `${key} short name fits`)
  }
})

test('light stays the default; dark is only an explicit choice', () => {
  assert.equal(resolveInitialDark(null, null), false)
  assert.equal(resolveInitialDark(null, 'dark'), true)
  assert.equal(resolveInitialDark('light', 'dark'), false)
  assert.equal(resolveInitialDark('dark', null), true)
})

test('applying a theme also recolours the browser chrome to the shell canvas', () => {
  const meta = { content: '#ffffff', setAttribute(name, value) { this[name] = value } }
  const root = { dataset: {}, classes: new Set(), classList: { toggle(name, on) { on ? root.classes.add(name) : root.classes.delete(name) } } }
  globalThis.document = { documentElement: root, querySelector: (selector) => (selector === 'meta[name="theme-color"]' ? meta : null) }

  try {
    applyThemeToDocument(true)
    assert.equal(root.dataset.theme, 'dark')
    assert.ok(root.classes.has('dark'))
    assert.equal(meta.content, THEME_CHROME.dark)

    applyThemeToDocument(false)
    assert.equal(root.dataset.theme, 'light')
    assert.equal(meta.content, THEME_CHROME.light)
  } finally {
    delete globalThis.document
  }

  assert.match(appCss, new RegExp(`--pl-bg: ${THEME_CHROME.light};`))
  assert.match(appCss, new RegExp(`--pl-bg: ${THEME_CHROME.dark};`))
  assert.match(appBlade, new RegExp(`dark \\? '${THEME_CHROME.dark}' : '${THEME_CHROME.light}'`))
})

test('the theme toggle is one tap away in the staff bar and the portal header', () => {
  assert.match(areaBar, /class="ds-icon-button pl-theme"[\s\S]*?:aria-pressed="props.isDark"[\s\S]*?@click="emit\('toggle-theme'\)"/)
  assert.match(portalLayout, /:aria-pressed="isDark"[\s\S]*?@click="toggleTheme"/)
  assert.match(portalLayout, /isThemeShortcut\(event\)/)
})

test('phones keep pinch-zoom and get 16px fields so focusing a field does not zoom the page', () => {
  assert.doesNotMatch(appBlade, /maximum-scale|user-scalable/)
  assert.match(appCss, /@media \(pointer: coarse\) \{\n  :root \{[^}]*--ds-field-font-size: 16px;/)
  assert.match(appCss, /font-size: var\(--ds-field-font-size\) !important;/)
})

test('phone navigation carries glyphs, the primary action and room for the safe area', () => {
  assert.match(layout, /v-for="area in phoneAreas"/)
  assert.match(layout, /<component :is="areaGlyphs\[area\.key\]\?\.icon"/)
  assert.match(sidebar, /v-if="canReceiveSamples"[^>]*class="ds-button ds-button-primary pl-side-receive"/)
  assert.match(portalLayout, /<nav class="pl-bottom"/)
  assert.match(portalLayout, /ds-button ds-button-primary mx-4 mb-2 justify-between/)
  assert.match(appCss, /--bottombar-height: calc\(60px \+ env\(safe-area-inset-bottom\)\);/)
  assert.match(appCss, /\.pl-main \{ padding-bottom: var\(--bottombar-height\); \}/)
})

test('the area strip keeps the current area in view and marks hidden areas with a fade', () => {
  assert.match(areaBar, /strip\.toggleAttribute\('data-more-after', /)
  assert.match(areaBar, /<nav class="pl-areas" aria-label="Áreas">/)
  assert.match(areaBar, /watch\(\(\) => props\.activeAreaKey/)
  assert.match(appCss, /\.pl-areas\[data-more-after\] \{ mask-image/)
})

const cell = (text = '', { colSpan = 1, rowSpan = 1, hidden = '' } = {}) => ({
  textContent: `${text}${hidden}`,
  colSpan,
  rowSpan,
  dataset: {},
  querySelectorAll: (selector) => (selector === '.sr-only' && hidden ? [{ textContent: hidden }] : []),
})
const fakeTable = (headers, rows, classes = []) => ({
  classList: { contains: (name) => classes.includes(name) },
  tHead: { rows: headers ? [{ cells: headers }] : [] },
  tBodies: [{ rows: rows.map((cells) => ({ cells })) }],
})

test('a phone table labels each cell with its column and makes the first named column the title', async () => {
  const { labelStackedCells } = await import('../../resources/js/Support/stackedTable.js')
  const row = [cell(), cell('SMP-1'), cell('Água'), cell()]
  const table = fakeTable([cell('', { hidden: 'Seleccionar' }), cell('Código'), cell('Amostra'), cell('', { hidden: 'Consulta' })], [row])

  assert.equal(labelStackedCells(table), true)
  assert.deepEqual(row.map((item) => item.dataset.cell), ['action', 'title', 'field', 'action'])
  assert.deepEqual(row.map((item) => item.dataset.label), ['', 'Código', 'Amostra', ''])
})

test('a row spanning the table reads as a note, not a record', async () => {
  const { labelStackedCells } = await import('../../resources/js/Support/stackedTable.js')
  const empty = cell('Sem registos', { colSpan: 3 })

  assert.equal(labelStackedCells(fakeTable([cell('A'), cell('B'), cell('C')], [[empty]])), true)
  assert.equal(empty.dataset.cell, 'note')
})

test('matrices, self-stacking registers and headless tables keep their grid', async () => {
  const { labelStackedCells } = await import('../../resources/js/Support/stackedTable.js')
  const untouched = [cell('1'), cell('2')]

  assert.equal(labelStackedCells(fakeTable([cell('Leitura', { colSpan: 2 })], [untouched])), false)
  assert.equal(labelStackedCells(fakeTable([cell('A'), cell('B')], [untouched], ['pl-stack-table'])), false)
  assert.equal(labelStackedCells(fakeTable(null, [untouched])), false)
  assert.equal(labelStackedCells(null), false)
  assert.deepEqual(untouched.map((item) => item.dataset.cell), [undefined, undefined])

  for (const path of ['resources/js/Components/Calibration/scale-calibration-table.vue', 'resources/js/Components/Chemistry/microbial-count.vue', 'resources/js/Pages/Worksheets/Edit.vue']) {
    assert.match(read(path), /<DataTable[^>]*:stack="false"/, path)
  }
})

test('stacked rows release the layered table and utility rules that would keep them in a grid', () => {
  assert.match(appCss, /@layer base \{\n  @media \(max-width: 639px\) \{\n    \.ds-data-table\[data-stack\] > tbody > tr > td \{ max-width: none !important;[^}]*padding: 0 !important;[^}]*white-space: normal !important; \}/)
  assert.match(appCss, /@media \(hover: none\) \{\n  \.pl-row-go \{ opacity: 1; \}/)
  assert.match(read('resources/js/Components/tables/DataTable.vue'), /:data-stack="stackable \|\| undefined"/)
})

test('filter cells become one swipeable strip on phones and keep the chosen cell in view', () => {
  assert.match(appCss, /\.pl-cells:is\(\[role="group"\], :has\(> button\.pl-cell, > a\.pl-cell\)\) \{\n    display: flex;\n    overflow-x: auto;/)
  assert.match(read('resources/js/Components/plano/StateCells.vue'), /watch\(\(\) => props\.modelValue, \(\) => nextTick\(revealChosen\)\)/)
})

test('the chart palette keeps neighbouring series apart; the builder flags confusable choices', async () => {
  const { categoricalPalette } = await import('../../resources/js/Support/charts.js')
  const { confusablePairs, colourDistance } = await import('../../resources/js/Support/colourDistance.js')

  for (const palette of [categoricalPalette.light, categoricalPalette.dark]) {
    assert.deepEqual(confusablePairs(palette.map((color, index) => ({ name: `s${index}`, color }))), [])
  }

  assert.deepEqual(confusablePairs([{ name: 'Entradas', color: '#008300' }, { name: 'Saídas', color: '#eb6834' }]), [{ first: 'Entradas', second: 'Saídas', reason: 'cvd' }])
  assert.deepEqual(confusablePairs([{ name: 'A', color: '#087cf0' }, { name: 'B', color: '#0a7ef2' }]), [{ first: 'A', second: 'B', reason: 'normal' }])
  assert.equal(Math.round(colourDistance('#000000', '#ffffff')), 100)
})
