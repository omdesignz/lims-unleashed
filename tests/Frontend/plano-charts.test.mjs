import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'
import {
  categoricalPalette,
  foldShareSlices,
  formatAxisValue,
  formatChartValue,
  planoChartOptions,
  seriesColors,
  shareSlices,
  tooltipMarkup,
} from '../../resources/js/Support/charts.js'

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8')

const chartPages = [
  'resources/js/Components/plano/PlanoChart.vue',
  'resources/js/Pages/Analytics/Board.vue',
  'resources/js/Pages/VAPSamples/Reports.vue',
  'resources/js/Pages/VAPInventory/Reports/InventoryValue.vue',
  'resources/js/Pages/VAPInventory/Reports/LowStock.vue',
  'resources/js/Pages/VAPInventory/Reports/Consumption.vue',
  'resources/js/Pages/VAPInventory/Reports/StockMovement.vue',
  'resources/js/Components/charts/inventory-analytics.vue',
  'resources/js/Pages/VAPNonConformities/Index.vue',
  'resources/js/Pages/ProficiencyTest/Index.vue',
  'resources/js/Pages/ProficiencyTest/Show.vue',
  'resources/js/Pages/Ratings/Index.vue',
  'resources/js/Pages/VAPMaintenance/Dashboard.vue',
  'resources/js/Pages/Proposals/Show.vue',
  'resources/js/Pages/Admin/Notifications/Analytics.vue',
]

test('every chart screen compiles and draws through PlanoChart only', () => {
  for (const path of chartPages) {
    const source = read(path)
    const { descriptor, errors } = parse(source, { filename: path })
    assert.deepEqual(errors, [], path)
    const script = compileScript(descriptor, { id: path })
    assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: path, id: path, compilerOptions: { bindingMetadata: script.bindings } }).errors, [], path)

    if (!path.endsWith('PlanoChart.vue')) {
      assert.match(source, /<PlanoChart\b/, path)
      assert.doesNotMatch(source, /<apexchart|<ChartWrapper|<simpleChart|strokeDashArray/, path)
    }
  }
})

test('series keep their slot, a chosen colour or a status tone; slots are never cycled', () => {
  const light = categoricalPalette.light
  assert.deepEqual(seriesColors([{}, {}, {}], false).slice(0, 3), light.slice(0, 3))
  assert.equal(seriesColors([{ slot: 4 }], false)[0], light[4])
  assert.equal(seriesColors([{ color: '#123456' }], false)[0], '#123456')
  assert.equal(seriesColors([{ color: 'red' }], false)[0], light[0], 'a non-hex colour is ignored')
  assert.equal(seriesColors(Array.from({ length: 12 }, () => ({})), false)[11], light[7], 'a ninth series never wraps to slot 1')
  assert.notEqual(seriesColors([{ tone: 'bad' }], false)[0], light[0])
  assert.equal(shareSlices(['A', 'Outros'])[1].tone, 'neutral')
  assert.equal(shareSlices(['Crítica'], {}, { Crítica: 'bad' })[0].tone, 'bad')
})

test('part-to-whole charts show at most six slices, folding the tail into Outros', () => {
  const folded = foldShareSlices(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'], [8, 7, 6, 5, 4, 3, 2, 0])
  assert.deepEqual(folded.labels, ['a', 'b', 'c', 'd', 'e', 'Outros'])
  assert.deepEqual(folded.values, [8, 7, 6, 5, 4, 5])
  assert.deepEqual(foldShareSlices(['a', 'b'], [0, 3]), { labels: ['b'], values: [3] })
})

test('values read in Portuguese, axis ticks stay compact and whole counts never show fractions', () => {
  assert.equal(formatChartValue(1234.5, 'decimal'), '1234,5')
  assert.match(formatChartValue(12000, 'currency'), /^12\s000,00 AOA$/u)
  assert.equal(formatChartValue(12.25, 'percent'), '12,3 %')
  assert.equal(formatChartValue(null), '—')
  assert.equal(formatChartValue(3, 'count', 'mL'), '3 mL')
  assert.equal(formatAxisValue(1.5, 'count'), '2')
  assert.match(formatAxisValue(25000, 'currency'), /^25\smil$/u)

  const counts = planoChartOptions({ kind: 'column', categories: ['a', 'b'], series: [{ name: 'Amostras', data: [0, 2] }] })
  assert.equal(counts.yaxis.tickAmount, 2)
  assert.equal(counts.yaxis.decimalsInFloat, 0)
})

test('the chart chrome follows the Plano language and never needs a second axis', () => {
  const options = planoChartOptions({ kind: 'column', categories: ['a', 'b', 'c'], series: [{ name: 'A', data: [1, 2, 3] }, { name: 'B', data: [3, 2, 1] }], width: 640, dark: false })

  assert.equal(options.grid.strokeDashArray, 0, 'gridlines are solid hairlines')
  assert.equal(options.legend.show, false, 'the legend is HTML, drawn by PlanoChart')
  assert.equal(options.plotOptions.bar.borderRadius, 0)
  assert.ok(!Array.isArray(options.yaxis), 'one value axis')
  assert.equal(options.chart.toolbar.show, false)
  assert.equal(options.stroke.width, 2)
  assert.ok(Number.parseInt(options.plotOptions.bar.columnWidth, 10) <= 70)
  assert.ok(!('labels' in options), 'no explicit undefined keys reach ApexCharts')

  const thin = planoChartOptions({ kind: 'column', categories: ['a', 'b'], series: [{ name: 'A', data: [1, 2] }], width: 1200 })
  assert.ok(Number.parseInt(thin.plotOptions.bar.columnWidth, 10) < 12, 'bars stay about 24px thick on wide plots')

  const ranked = planoChartOptions({ kind: 'bar', categories: ['a', 'b'], series: [{ name: 'A', data: [1, 2] }] })
  assert.equal(ranked.plotOptions.bar.horizontal, true)
  assert.equal(ranked.dataLabels.enabled, true, 'a ranked single series shows its values at the bar ends')
  assert.equal(ranked.grid.xaxis.lines.show, true)
})

test('limits are drawn as labelled rules and bars below zero keep their sign', () => {
  const zScores = planoChartOptions({
    kind: 'column',
    categories: ['Lab A', 'Lab B'],
    series: [{ name: 'z', data: [-2.5, 1.2] }],
    format: 'decimal',
    reference: [{ value: 3, label: '+3', tone: 'bad' }, { value: -3, label: '−3', tone: 'bad' }],
  })
  // The axis spans both limits even though every bar sits inside them.
  assert.ok(zScores.yaxis.min <= -3 && zScores.yaxis.max >= 3)
  assert.equal(zScores.annotations.yaxis.length, 2)
  assert.equal(zScores.annotations.yaxis[0].label.text, '+3')

  const target = planoChartOptions({ kind: 'bar', categories: ['A'], series: [{ name: 'No prazo', data: [80] }], reference: { value: 90, label: 'Meta' } })
  assert.equal(target.annotations.xaxis[0].x, 90)

  const counts = planoChartOptions({ kind: 'column', categories: ['A'], series: [{ name: 'Amostras', data: [4] }], reference: { value: 10, label: 'Meta' } })
  assert.equal(counts.yaxis.min, 0, 'bars of counts still grow from zero')
  assert.ok(counts.yaxis.max >= 10)
})

test('a control chart shows every point and marks those that break a rule', () => {
  const chart = planoChartOptions({
    kind: 'line',
    categories: ['1', '2', '3'],
    series: [{ name: 'Valor', data: [10.1, 9.8, 13.4] }],
    format: 'decimal',
    reference: [{ value: 10, label: 'LC' }, { value: 12.4, label: 'LSA', tone: 'bad' }, { value: 7.6, label: 'LIA', tone: 'bad' }],
    points: [{ index: 2, tone: 'bad' }, { index: 1, tone: 'nonsense' }],
  })
  assert.equal(chart.markers.size, 4)
  assert.equal(chart.markers.discrete.length, 1)
  assert.equal(chart.markers.discrete[0].dataPointIndex, 2)
  assert.ok(chart.yaxis.min < 7.6 && chart.yaxis.max > 13.4)

  const plain = planoChartOptions({ kind: 'line', categories: ['1'], series: [{ name: 'Valor', data: [1] }] })
  assert.equal(plain.markers.size, 0, 'other line charts keep their clean stroke')
})

test('tooltips lead with the value and escape every label', () => {
  const markup = tooltipMarkup({ title: '<b>Semana</b>', rows: [{ color: '#087cf0', value: '2', label: '<img src=x onerror=alert(1)>' }] })
  assert.doesNotMatch(markup, /<img|<b>/)
  assert.match(markup, /<strong>2<\/strong><span>&lt;img/)
})

test('PlanoChart is the only way a chart is drawn: no global chart tag, no global defaults', () => {
  const bootstrap = read('resources/js/app.js')
  assert.doesNotMatch(bootstrap, /apexchart|applyChartDefaults/i)
  assert.doesNotMatch(read('resources/js/Support/charts.js'), /window\.Apex/)
  assert.match(read('resources/js/Components/plano/PlanoChart.vue'), /await import\('vue3-apexcharts'\)/)
})

test('every chart offers its data as a table or a value list', () => {
  const chart = read('resources/js/Components/plano/PlanoChart.vue')
  assert.match(chart, /<details v-if="showTable" :id="tableId" class="pl-chart-data">/)
  assert.match(chart, /<ol v-if="kind === 'donut'" class="pl-chart-shares">/)
  assert.match(chart, /role="img" :aria-label="summary"/)
  assert.match(chart, /attributeFilter: \['data-theme', 'class'\]/)
})

test('the builder preview leads and stays pinned on narrow screens and sits beside the fields on wide ones', () => {
  const css = read('resources/css/app.css')
  const board = read('resources/js/Pages/Analytics/Board.vue')
  const narrow = css.match(/@media \(max-width: 899\.98px\) \{\s*\.pl-builder-preview \{([^}]*)\}/)[1]
  assert.match(css, /\.pl-builder-fields > \* \{ scroll-margin-top: 48dvh; \}/)
  for (const rule of ['position: sticky', 'top: 0', 'order: -1', 'max-height: 46dvh', 'overflow-y: auto', 'background: var(--pl-layer)']) {
    assert.ok(narrow.includes(rule), rule)
  }
  // The pinned preview is drawn shorter, on the same breakpoint as the stylesheet.
  assert.match(board, /window\.matchMedia\('\(max-width: 899\.98px\)'\)/)
  assert.match(board, /:height="singleColumn \? 150 : 240"/)
  assert.match(board, /columnQuery\?\.removeEventListener\('change', followColumns\)/)
  assert.match(css, /@media \(min-width: 900px\) \{\s*\.pl-builder \{ grid-template-columns: minmax\(0, 1fr\) minmax\(0, 1\.1fr\); align-items: start; \}\s*\.pl-builder-preview \{ position: sticky; top: 0; \}\s*\}/)
})
