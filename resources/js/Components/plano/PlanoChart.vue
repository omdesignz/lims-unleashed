<script setup>
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import DataTable from '@/Components/tables/DataTable.vue'
import { foldShareSlices, formatChartValue, isDarkTheme, namedSeries, planoChartOptions, seriesColors, shareSlices } from '@/Support/charts'

const ApexChart = defineAsyncComponent(async () => (await import('vue3-apexcharts')).default)

/**
 * The one chart of the application. Pages pass data, never ApexCharts options:
 * the form, palette, grid, tooltip and number format come from the Plano chart
 * language (`Support/charts.js`). Every chart carries its data as a table too,
 * so a value never depends on colour or hover.
 *
 * Forms: column (vertical bars), bar (horizontal, ranked), line, area (one
 * filled trend), donut (part-to-whole, at most six slices).
 */
const props = defineProps({
  kind: { type: String, default: 'column', validator: (value) => ['column', 'bar', 'line', 'area', 'donut'].includes(value) },
  /** Accessible name of the chart, e.g. "Amostras recebidas por semana". */
  label: { type: String, required: true },
  categories: { type: Array, default: () => [] },
  /** [{ name, data: number[], tone?: 'ok'|'warn'|'bad'|'neutral', slot?: number }]; a donut takes one series. */
  series: { type: Array, default: () => [] },
  stacked: { type: Boolean, default: false },
  format: { type: [String, Function], default: 'count' },
  unit: { type: String, default: '' },
  height: { type: Number, default: 280 },
  loading: { type: Boolean, default: false },
  emptyText: { type: String, default: 'Sem dados para este recorte.' },
  /** Chosen colours: series (or donut slice) name => hex. A series' own `color` also works. */
  colors: { type: Object, default: () => ({}) },
  /** Status tones for series or slices that mean a state: name => 'ok' | 'warn' | 'bad' | 'neutral' | 'accent'. */
  tones: { type: Object, default: () => ({}) },
  /** Targets or limits drawn across the plot: { value, label, tone? } or a list of them. */
  reference: { type: [Object, Array], default: null },
  /** Line charts only: show every point (true), or show them and mark some with a tone: [{ index, tone, series? }]. */
  points: { type: [Boolean, Array], default: null },
  /** Hide the "Ver dados" table (only when the same values are listed beside the chart). */
  hideTable: { type: Boolean, default: false },
})

const root = ref(null)
const width = ref(640)
const dark = ref(isDarkTheme())
const renderKey = ref(0)
const tableId = `pl-chart-table-${useId()}`
let themeObserver = null
let resizeObserver = null

const shares = computed(() => (props.kind === 'donut'
  ? foldShareSlices(props.categories, props.series[0]?.data ?? [])
  : null))

const categories = computed(() => shares.value?.labels ?? props.categories)

const apexSeries = computed(() => (props.kind === 'donut'
  ? shares.value.values
  : props.series.map((item) => ({ name: item.name, data: (item.data ?? []).map((value) => (value === null || value === undefined ? null : Number(value))) }))))

const isEmpty = computed(() => {
  if (!categories.value.length) {
    return true
  }

  const values = props.kind === 'donut' ? apexSeries.value : apexSeries.value.flatMap((item) => item.data)

  return !values.some((value) => value !== null && Number(value) !== 0)
})

// Bands are bucketed so a resize re-plans the bar thickness without re-rendering on every pixel.
const widthBucket = computed(() => Math.round(width.value / 80) * 80)

const options = computed(() => planoChartOptions({
  kind: props.kind,
  stacked: props.stacked,
  categories: categories.value,
  series: props.kind === 'donut' ? [] : props.series,
  format: props.format,
  unit: props.unit,
  width: widthBucket.value,
  dark: dark.value,
  colors: props.colors,
  tones: props.tones,
  reference: props.reference,
  points: props.points,
}))

const chartHeight = computed(() => (props.kind === 'bar'
  ? Math.max(props.height, categories.value.length * 36 + 48)
  : props.height))

const value = (number) => formatChartValue(number, props.format, props.unit)
const showTable = computed(() => !props.hideTable && !isEmpty.value && props.kind !== 'donut')

const tableColumns = computed(() => (props.kind === 'donut'
  ? [{ name: props.series[0]?.name || 'Valor' }, { name: 'Parte' }]
  : props.series.map((item) => ({ name: item.name }))))

const tableRows = computed(() => {
  if (props.kind === 'donut') {
    const total = apexSeries.value.reduce((sum, item) => sum + item, 0) || 1

    return categories.value.map((category, index) => ({
      category,
      cells: [value(apexSeries.value[index]), formatChartValue((apexSeries.value[index] / total) * 100, 'percent')],
    }))
  }

  return categories.value.map((category, index) => ({
    category,
    cells: apexSeries.value.map((item) => value(item.data[index])),
  }))
})

const legendKeys = computed(() => (props.kind === 'donut'
  ? seriesColors(shareSlices(categories.value, props.colors, props.tones), dark.value)
  : seriesColors(namedSeries(props.series, props.colors, props.tones), dark.value)))

const summary = computed(() => {
  if (isEmpty.value) {
    return `${props.label}. ${props.emptyText}`
  }

  return `${props.label}. ${categories.value.length} ${categories.value.length === 1 ? 'categoria' : 'categorias'}; os valores estão na tabela de dados.`
})

function syncTheme() {
  const next = isDarkTheme()

  if (next !== dark.value) {
    dark.value = next
    // Apex keeps theme colours in its own state: redraw on a theme change.
    renderKey.value += 1
  }
}

onMounted(() => {
  themeObserver = new MutationObserver(syncTheme)
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class'] })

  if (typeof ResizeObserver !== 'undefined' && root.value) {
    resizeObserver = new ResizeObserver(([entry]) => { width.value = entry.contentRect.width || width.value })
    resizeObserver.observe(root.value)
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
  resizeObserver?.disconnect()
})

// A new chart form or series count changes the chart's structure; redraw rather than morph.
watch(() => [props.kind, props.stacked, props.series.length, JSON.stringify(props.colors)], () => { renderKey.value += 1 })
</script>

<template>
  <figure ref="root" class="pl-chart" :data-kind="kind" :aria-busy="loading">
    <div v-if="loading && isEmpty" class="pl-chart-skeleton" :style="{ height: `${chartHeight}px` }" role="status">
      <span class="sr-only">A carregar {{ label }}…</span>
      <span v-for="bar in 7" :key="bar" class="pl-skel" :style="{ height: `${24 + ((bar * 37) % 60)}%` }" />
    </div>

    <div v-else-if="isEmpty" class="pl-chart-empty" :style="{ minHeight: `${Math.min(chartHeight, 200)}px` }" role="status">
      <span class="pl-k">Sem dados</span>
      <p>{{ emptyText }}</p>
    </div>

    <template v-else>
      <ul v-if="kind !== 'donut' && series.length > 1" class="pl-chart-legend" aria-hidden="true">
        <li v-for="(item, index) in series" :key="item.name"><span class="pl-chart-key" :data-kind="kind" :style="{ background: legendKeys[index] }" />{{ item.name }}</li>
      </ul>
      <div class="pl-chart-body" :data-kind="kind">
        <div class="pl-chart-plot" :class="{ 'is-refreshing': loading }" role="img" :aria-label="summary" :aria-describedby="showTable ? tableId : undefined">
          <ApexChart :key="renderKey" :type="options.chart.type" :height="chartHeight" width="100%" :options="options" :series="apexSeries" />
        </div>
        <!-- A part-to-whole chart lists every slice with its value and share: the list is its table. -->
        <ol v-if="kind === 'donut'" class="pl-chart-shares">
          <li v-for="(row, index) in tableRows" :key="row.category">
            <span class="pl-chart-key" :style="{ background: legendKeys[index] }" aria-hidden="true" />
            <span class="pl-chart-share-label">{{ row.category }}</span>
            <strong class="pl-num">{{ row.cells[0] }}</strong>
            <span class="pl-num pl-muted">{{ row.cells[1] }}</span>
          </li>
        </ol>
      </div>
    </template>

    <details v-if="showTable" :id="tableId" class="pl-chart-data">
      <summary>Ver dados</summary>
      <div class="pl-chart-data-scroll">
        <DataTable :stack="false">
          <caption class="sr-only">{{ label }}</caption>
          <thead>
            <tr>
              <th scope="col">Categoria</th>
              <th v-for="column in tableColumns" :key="column.name" scope="col" class="text-right">{{ column.name }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableRows" :key="row.category">
              <th scope="row" class="font-normal">{{ row.category }}</th>
              <td v-for="(cell, index) in row.cells" :key="index" class="pl-num text-right">{{ cell }}</td>
            </tr>
          </tbody>
        </DataTable>
      </div>
    </details>
  </figure>
</template>
