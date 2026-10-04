/**
 * The Plano chart language. Every chart in the application is built from these
 * values, so a colour, a gridline or a number reads the same on every screen.
 *
 * Colour does exactly one job per chart:
 * - identity: the categorical palette, assigned in this fixed order, never cycled
 *   (a ninth series folds into "Outros");
 * - magnitude: one blue ramp, light to dark;
 * - state: the Plano status tokens, only when a series means good or bad.
 *
 * The categorical slots were validated (OKLab ΔE, Machado 2009 CVD simulation)
 * against the Plano surfaces: light #ffffff, worst adjacent CVD ΔE 9.1 and
 * normal-vision ΔE 19.6; dark #070f1c, 8.4 and 19.3, every slot ≥ 3:1. Three
 * light slots sit under 3:1 on white, so every chart ships its table view.
 */
export const categoricalPalette = {
    light: ['#087cf0', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'],
    dark: ['#087cf0', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'],
}

/** One hue, light to dark, for magnitude (heat cells, ordered tiers). */
export const sequentialBlue = ['#cde2fb', '#b7d3f6', '#9ec5f4', '#86b6ef', '#6da7ec', '#5598e7', '#3987e5', '#2a78d6', '#256abf', '#1c5cab', '#184f95', '#104281', '#0d366b']

/** Series that mean a state wear the Plano status tokens, never a categorical slot. */
export const statusTones = ['ok', 'warn', 'bad', 'neutral', 'accent']

/** The most slices a part-to-whole chart shows before folding the tail into "Outros". */
export const maxShareSlices = 6

export const chartFontFamily = "'Geist Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"
export const chartSansFamily = "'TASA Orbiter', 'Segoe UI', system-ui, sans-serif"

const fallbackTokens = {
    light: { bg: '#ffffff', layer: '#f3f6fa', line: '#d9e2ec', lineStrong: '#b4c2d2', fg: '#061f46', muted: '#52647a', faint: '#8595a9', ok: '#176b43', warn: '#8a5200', bad: '#b42332', accent: '#0757b5' },
    dark: { bg: '#070f1c', layer: '#0b1728', line: '#1a2a43', lineStrong: '#2c405f', fg: '#edf2f8', muted: '#8c9cb3', faint: '#5c6e88', ok: '#4cc38a', warn: '#f2b441', bad: '#ff6b6b', accent: '#70b5ff' },
}

export function isDarkTheme() {
    return typeof document !== 'undefined' && document.documentElement.dataset.theme === 'dark'
}

/** The live Plano tokens for the current theme (CSS custom properties win over the fallbacks). */
export function chartTokens(dark = isDarkTheme()) {
    const fallback = dark ? fallbackTokens.dark : fallbackTokens.light

    if (typeof document === 'undefined' || typeof getComputedStyle === 'undefined') {
        return { ...fallback }
    }

    const style = getComputedStyle(document.documentElement)
    const read = (name, value) => style.getPropertyValue(name).trim() || value

    return {
        bg: read('--pl-bg', fallback.bg),
        layer: read('--pl-layer', fallback.layer),
        line: read('--pl-line', fallback.line),
        lineStrong: read('--pl-line-strong', fallback.lineStrong),
        fg: read('--pl-fg', fallback.fg),
        muted: read('--pl-muted', fallback.muted),
        faint: read('--pl-faint', fallback.faint),
        ok: read('--pl-ok', fallback.ok),
        warn: read('--pl-warn', fallback.warn),
        bad: read('--pl-bad', fallback.bad),
        accent: read('--pl-accent-text', fallback.accent),
    }
}

/**
 * Colour for each series: the colour chosen for it, a status tone when the
 * series declares one, otherwise its categorical slot. `slot` pins an entity to its colour so a filter that
 * removes other series never repaints it.
 *
 * @param {Array<{ tone?: string, slot?: number }>} series
 */
export function seriesColors(series, dark = isDarkTheme(), tokens = chartTokens(dark)) {
    const palette = dark ? categoricalPalette.dark : categoricalPalette.light
    const toneColor = { ok: tokens.ok, warn: tokens.warn, bad: tokens.bad, neutral: tokens.faint, accent: palette[0] }

    return series.map((item, index) => {
        // A colour the person chose for this series wins over its slot.
        if (typeof item?.color === 'string' && /^#[0-9a-f]{6}$/i.test(item.color)) {
            return item.color
        }

        if (item?.tone && toneColor[item.tone]) {
            return toneColor[item.tone]
        }

        return palette[Math.min(item?.slot ?? index, palette.length - 1)]
    })
}

const numberFormats = new Map()
function numberFormat(options) {
    const key = JSON.stringify(options)

    if (!numberFormats.has(key)) {
        numberFormats.set(key, new Intl.NumberFormat('pt-PT', options))
    }

    return numberFormats.get(key)
}

/**
 * Formats a value for tooltips, labels and the table view.
 *
 * @param {number|null} value
 * @param {string|Function} format count | decimal | currency | percent | hours | days | a function
 * @param {string} unit appended after the number (e.g. a stock unit)
 */
export function formatChartValue(value, format = 'count', unit = '') {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—'
    }

    if (typeof format === 'function') {
        return format(value)
    }

    const number = Number(value)
    const text = {
        count: () => numberFormat({ maximumFractionDigits: 0 }).format(number),
        decimal: () => numberFormat({ maximumFractionDigits: 2 }).format(number),
        currency: () => `${numberFormat({ minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number)} AOA`,
        percent: () => `${numberFormat({ maximumFractionDigits: 1 }).format(number)} %`,
        hours: () => `${numberFormat({ maximumFractionDigits: 1 }).format(number)} h`,
        days: () => `${numberFormat({ maximumFractionDigits: 1 }).format(number)} d`,
    }[format]?.() ?? numberFormat({ maximumFractionDigits: 2 }).format(number)

    return unit ? `${text} ${unit}` : text
}

/** Axis ticks: clean, compact numbers (1,2 mil) so they never crowd the plot. */
export function formatAxisValue(value, format = 'count') {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return ''
    }

    const number = Number(value)
    const compact = Math.abs(number) >= 10000
        ? numberFormat({ notation: 'compact', maximumFractionDigits: 1 }).format(number)
        : numberFormat({ maximumFractionDigits: format === 'count' ? 0 : 1 }).format(number)

    return { percent: `${compact} %`, hours: `${compact} h`, days: `${compact} d` }[format] ?? compact
}

/**
 * Keeps the largest slices of a part-to-whole chart and folds the rest into one
 * "Outros" slice, so the chart never needs a ninth colour or a sliver.
 *
 * @param {string[]} labels
 * @param {number[]} values
 * @returns {{ labels: string[], values: number[] }}
 */
export function foldShareSlices(labels, values, limit = maxShareSlices) {
    const pairs = labels.map((label, index) => ({ label, value: Number(values[index]) || 0 })).filter((pair) => pair.value > 0)

    if (pairs.length <= limit) {
        return { labels: pairs.map((pair) => pair.label), values: pairs.map((pair) => pair.value) }
    }

    const sorted = [...pairs].sort((first, second) => second.value - first.value)
    const kept = sorted.slice(0, limit - 1)
    const rest = sorted.slice(limit - 1).reduce((sum, pair) => sum + pair.value, 0)

    return { labels: [...kept.map((pair) => pair.label), 'Outros'], values: [...kept.map((pair) => pair.value), rest] }
}

/** Slices of a part-to-whole chart: slot by position, a declared status tone, "Outros" neutral; chosen colours win. */
export function shareSlices(categories, chosen = {}, tones = {}) {
    return categories.map((label, index) => ({ slot: index, tone: tones[label] ?? (label === 'Outros' ? 'neutral' : undefined), color: chosen[label] }))
}

/** Series with their chosen colour or status tone resolved by name. */
export function namedSeries(series, chosen = {}, tones = {}) {
    return series.map((item) => ({ ...item, color: item.color ?? chosen[item.name], tone: item.tone ?? tones[item.name] }))
}

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character])

/**
 * Tooltip markup: the value leads, the series follows, a short stroke of the
 * series colour keys each row. Labels are untrusted text and are escaped.
 */
export function tooltipMarkup({ title, rows }) {
    const body = rows.map((row) => `<div class="pl-chart-tip-row"><span class="pl-chart-tip-key" style="background:${escapeHtml(row.color)}"></span><strong>${escapeHtml(row.value)}</strong><span>${escapeHtml(row.label)}</span></div>`).join('')

    return `<div class="pl-chart-tip">${title ? `<p class="pl-chart-tip-title">${escapeHtml(title)}</p>` : ''}${body}</div>`
}

/**
 * ApexCharts options for one Plano chart.
 *
 * @param {object} config
 * @param {'column'|'bar'|'line'|'area'|'donut'} config.kind
 * @param {boolean} [config.stacked]
 * @param {string[]} config.categories
 * @param {Array<{ name: string, tone?: string, slot?: number }>} config.series
 * @param {string|Function} [config.format]
 * @param {string} [config.unit]
 * @param {number} [config.width] rendered width, used to cap bar thickness at 24px
 * @param {boolean} [config.dark]
 */
export function planoChartOptions({ kind, stacked = false, categories = [], series = [], format = 'count', unit = '', width = 640, dark = isDarkTheme(), colors: chosen = {}, tones = {}, reference = null }) {
    const tokens = chartTokens(dark)
    const horizontal = kind === 'bar'
    const isBar = kind === 'bar' || kind === 'column'
    const isShare = kind === 'donut'
    const colors = isShare
        ? seriesColors(shareSlices(categories, chosen, tones), dark, tokens)
        : seriesColors(namedSeries(series, chosen, tones), dark, tokens)
    const multiSeries = series.length > 1

    // Bars stay thin: at most 24px thick, the rest of each band is air.
    const bandCount = Math.max(1, categories.length)
    const groupSize = isBar && multiSeries && !stacked ? series.length : 1
    const plotLength = Math.max(120, horizontal ? (bandCount * 36) : width - 64)
    const thickness = Math.min(70, Math.max(8, Math.round(((24 * groupSize) / (plotLength / bandCount)) * 100)))

    const value = (number) => formatChartValue(number, format, unit)

    // Whole-number measures never get fractional ticks: small maxima get one tick per unit.
    const peak = stacked
        ? Math.max(0, ...categories.map((_, index) => series.reduce((sum, item) => sum + (Number(item.data?.[index]) || 0), 0)))
        : Math.max(0, ...series.flatMap((item) => (item.data ?? []).map((entry) => Number(entry) || 0)))
    const wholeTicks = format === 'count' ? Math.max(1, Math.min(4, Math.ceil(peak))) : 4
    const hasNegative = series.some((item) => (item.data ?? []).some((entry) => Number(entry) < 0))
    const showBarLabels = horizontal && !multiSeries && categories.length <= 12

    const options = {
        chart: {
            type: isShare ? 'donut' : horizontal ? 'bar' : kind === 'column' ? 'bar' : kind,
            stacked,
            background: 'transparent',
            fontFamily: chartFontFamily,
            foreColor: tokens.muted,
            parentHeightOffset: 0,
            redrawOnParentResize: true,
            toolbar: { show: false },
            zoom: { enabled: false },
            selection: { enabled: false },
            animations: { enabled: true, speed: 280, animateGradually: { enabled: false }, dynamicAnimation: { enabled: true, speed: 200 } },
        },
        theme: { mode: dark ? 'dark' : 'light' },
        colors,
        labels: isShare ? categories : undefined,
        grid: isShare ? { show: false } : {
            borderColor: tokens.line,
            strokeDashArray: 0,
            xaxis: { lines: { show: horizontal } },
            yaxis: { lines: { show: !horizontal } },
            padding: { top: 0, right: showBarLabels ? 48 : 12, bottom: 0, left: 4 },
        },
        stroke: isShare
            ? { show: true, width: 2, colors: [tokens.bg] }
            : isBar
                ? { show: true, width: 2, colors: [tokens.bg] }
                : { show: true, width: 2, curve: 'straight', lineCap: 'round' },
        fill: { type: 'solid', opacity: kind === 'area' ? 0.1 : 1 },
        markers: { size: 0, strokeWidth: 2, strokeColors: tokens.bg, hover: { size: 5 } },
        plotOptions: {
            bar: {
                horizontal,
                borderRadius: 0,
                columnWidth: `${thickness}%`,
                barHeight: `${thickness}%`,
                dataLabels: { position: 'top' },
            },
            pie: {
                expandOnClick: false,
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: { show: true, fontFamily: chartFontFamily, fontSize: '10px', color: tokens.muted, offsetY: 18 },
                        value: { show: true, fontFamily: chartSansFamily, fontSize: '24px', fontWeight: 700, color: tokens.fg, offsetY: -12, formatter: (raw) => value(raw) },
                        total: { show: true, showAlways: true, label: 'TOTAL', fontFamily: chartFontFamily, fontSize: '10px', color: tokens.muted, formatter: (context) => value(context.globals.seriesTotals.reduce((sum, item) => sum + item, 0)) },
                    },
                },
            },
        },
        dataLabels: showBarLabels ? {
            enabled: true,
            formatter: (raw) => value(raw),
            offsetX: 28,
            textAnchor: 'start',
            style: { fontFamily: chartFontFamily, fontSize: '10px', fontWeight: 500, colors: [tokens.muted] },
            background: { enabled: false },
            dropShadow: { enabled: false },
        } : { enabled: false },
        xaxis: isShare ? undefined : {
            categories,
            type: 'category',
            axisBorder: { show: true, color: tokens.lineStrong },
            axisTicks: { show: false },
            crosshairs: { show: !isBar, stroke: { color: tokens.lineStrong, width: 1, dashArray: 0 } },
            tooltip: { enabled: false },
            tickAmount: !horizontal && categories.length > 12 ? 8 : undefined,
            labels: {
                rotate: 0,
                hideOverlappingLabels: true,
                trim: false,
                style: { colors: tokens.muted, fontSize: '10px', fontFamily: chartFontFamily },
                formatter: horizontal ? (raw) => formatAxisValue(raw, format) : undefined,
            },
        },
        yaxis: isShare ? undefined : {
            // Bars grow from zero unless the data goes below it (e.g. z-scores).
            min: (isBar || kind === 'area') && !hasNegative ? 0 : undefined,
            forceNiceScale: true,
            tickAmount: horizontal ? undefined : wholeTicks,
            decimalsInFloat: format === 'count' ? 0 : undefined,
            labels: {
                maxWidth: horizontal ? 180 : 72,
                style: { colors: horizontal ? tokens.fg : tokens.muted, fontSize: horizontal ? '12px' : '10px', fontFamily: horizontal ? chartSansFamily : chartFontFamily },
                formatter: horizontal ? undefined : (raw) => formatAxisValue(raw, format),
            },
        },
        // The legend is drawn in HTML by PlanoChart (type, values, line or square keys).
        legend: { show: false },
        states: {
            hover: { filter: { type: isShare ? 'none' : 'lighten' } },
            active: { filter: { type: 'none' } },
        },
        tooltip: {
            shared: !isBar && !isShare,
            intersect: false,
            followCursor: false,
            custom: ({ series: values, seriesIndex, dataPointIndex, w }) => {
                if (isShare) {
                    const total = values.reduce((sum, item) => sum + item, 0) || 1
                    const share = formatChartValue((values[seriesIndex] / total) * 100, 'percent')

                    return tooltipMarkup({ title: w.globals.labels[seriesIndex], rows: [{ color: colors[seriesIndex], value: value(values[seriesIndex]), label: share }] })
                }

                const indexes = isBar && !stacked ? [seriesIndex] : values.map((_, index) => index)

                return tooltipMarkup({
                    title: categories[dataPointIndex],
                    rows: indexes
                        .filter((index) => values[index]?.[dataPointIndex] !== undefined && values[index]?.[dataPointIndex] !== null)
                        .map((index) => ({ color: colors[index], value: value(values[index][dataPointIndex]), label: w.globals.seriesNames[index] })),
                })
            },
        },
    }

    // A target or limit is a dashed rule with its label: dashing marks it as a threshold, not grid.
    // Limits that mean a state (warning, action) take the status tone.
    const references = (Array.isArray(reference) ? reference : [reference]).filter((item) => item && Number.isFinite(Number(item.value)))
    if (references.length && !isShare) {
        const toneColor = { ok: tokens.ok, warn: tokens.warn, bad: tokens.bad, neutral: tokens.faint }
        const rule = (item) => ({
            borderColor: toneColor[item.tone] ?? tokens.fg,
            strokeDashArray: 4,
            borderWidth: 1,
            label: {
                text: item.label ?? '',
                borderWidth: 0,
                orientation: 'horizontal',
                position: horizontal ? 'top' : 'left',
                textAnchor: horizontal ? 'middle' : 'start',
                offsetY: horizontal ? -6 : -2,
                style: { background: tokens.bg, color: tokens.fg, fontFamily: chartFontFamily, fontSize: '10px', padding: { left: 4, right: 4, top: 1, bottom: 1 } },
            },
        })
        options.annotations = horizontal
            ? { xaxis: references.map((item) => ({ x: Number(item.value), ...rule(item) })) }
            : { yaxis: references.map((item) => ({ y: Number(item.value), ...rule(item) })) }
    }

    // ApexCharts reads an explicit `undefined` as a value (e.g. labels.length): drop them.
    Object.keys(options).forEach((key) => options[key] === undefined && delete options[key])

    return options
}
