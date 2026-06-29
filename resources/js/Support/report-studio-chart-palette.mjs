export function normalizedHexColor(value, fallback = '#0f172a') {
  return /^#[0-9a-f]{6}$/i.test(String(value || '')) ? value : fallback
}

export const defaultStudioChartPalette = ['#143d37', '#d9b05f', '#0f766e', '#475569', '#7c2d12', '#3f6f58']

export function splitStudioChartList(value) {
  const source = String(value || '')
  const items = []
  let current = ''

  for (let index = 0; index < source.length; index += 1) {
    const character = source[index]
    const previousCharacter = source[index - 1] || ''
    const nextCharacter = source[index + 1] || ''
    const isHardSeparator = character === ';' || character === '\n' || character === '\r'
    const isListComma = character === ','
      && !(/\d/.test(previousCharacter) && /\d/.test(nextCharacter))

    if (isHardSeparator || isListComma) {
      const item = current.trim()

      if (item) {
        items.push(item)
      }

      current = ''

      continue
    }

    current += character
  }

  const item = current.trim()

  if (item) {
    items.push(item)
  }

  return items
}

export function normalizeStudioChartList(value) {
  if (Array.isArray(value)) {
    return value
      .flatMap((item) => splitStudioChartList(item))
      .filter(Boolean)
  }

  return splitStudioChartList(value)
}

export function sanitizeStudioChartSvg(value) {
  const source = String(value || '').trim()

  if (!/^\s*<svg(?:\s|>)/i.test(source)) {
    return ''
  }

  const sanitized = source
    .replace(/<\s*(script|iframe|object|embed|foreignObject|link|meta)\b[^>]*>.*?<\s*\/\s*\1\s*>/gis, '')
    .replace(/<\s*(script|iframe|object|embed|foreignObject|link|meta)\b[^>]*\/?\s*>/gis, '')
    .replace(/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/g, '')
    .replace(/\s+(?:xlink:href|href)\s*=\s*("[']?)\s*javascript:[^"'>\s]*\1/gi, '')
    .replace(/url\(\s*(['"]?)\s*javascript:[^)]+\)/gi, '')
  const svgDocument = sanitized.match(/^[\s\S]*?<\s*\/\s*svg\s*>/i)?.[0]?.trim() || ''

  return svgDocument
}

function hexToRgb(hexColor) {
  const hex = normalizedHexColor(hexColor, '#f8f4ea').slice(1)

  return {
    red: Number.parseInt(hex.slice(0, 2), 16),
    green: Number.parseInt(hex.slice(2, 4), 16),
    blue: Number.parseInt(hex.slice(4, 6), 16),
  }
}

function relativeLuminance({ red, green, blue }) {
  const channel = (value) => {
    const normalized = value / 255

    return normalized <= 0.03928
      ? normalized / 12.92
      : ((normalized + 0.055) / 1.055) ** 2.4
  }

  return (0.2126 * channel(red)) + (0.7152 * channel(green)) + (0.0722 * channel(blue))
}

export function chartSvgPalette(backgroundColor = '#f8f4ea') {
  const luminance = relativeLuminance(hexToRgb(backgroundColor))

  if (luminance < 0.42) {
    return {
      ink: '#fffdf7',
      muted: '#d7e3dc',
      grid: '#48615a',
      markerStroke: '#07110f',
    }
  }

  return {
    ink: '#0f172a',
    muted: '#64748b',
    grid: '#cbd5e1',
    markerStroke: '#fffaf0',
  }
}

function defaultInterpolate(value) {
  return String(value ?? '')
}

function chartListValue(value, interpolate = defaultInterpolate) {
  if (Array.isArray(value)) {
    return normalizeStudioChartList(value.map((item) => interpolate(item)))
  }

  return normalizeStudioChartList(interpolate(value))
}

function chartNumericValues(block, interpolate) {
  return chartListValue(block.chart_values ?? block.chart_series, interpolate)
    .map((value) => Number(String(value).replace(',', '.')))
    .filter((value) => Number.isFinite(value))
    .slice(0, 12)
}

function chartLabelValues(block, values, interpolate) {
  const labels = chartListValue(block.chart_labels, interpolate).slice(0, 12)

  if (labels.length) {
    return labels
  }

  return values.map((_, index) => `S${index + 1}`)
}

function chartColorValues(block, interpolate) {
  const configuredColors = chartListValue(block.chart_colors, interpolate)
    .map((value) => normalizedHexColor(value, ''))
    .filter(Boolean)

  return configuredColors.length ? configuredColors : defaultStudioChartPalette
}

function escapeStudioChartSvgText(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}

function formatStudioChartValue(value) {
  return Number.isInteger(value)
    ? String(value)
    : value.toFixed(2).replace(/\.?0+$/, '')
}

function colorValue(value, fallback) {
  return normalizedHexColor(value, fallback)
}

export function generatedStudioChartSvg(block = {}, options = {}) {
  const interpolate = typeof options.interpolate === 'function' ? options.interpolate : defaultInterpolate
  const colorInputValue = typeof options.colorInputValue === 'function' ? options.colorInputValue : colorValue
  const values = chartNumericValues(block, interpolate)

  if (!values.length) {
    return ''
  }

  const labels = chartLabelValues(block, values, interpolate)
  const colors = chartColorValues(block, interpolate)
  const maxValue = Math.max(...values, 1)
  const type = ['bar', 'line', 'doughnut'].includes(block.chart_type) ? block.chart_type : 'bar'
  const title = escapeStudioChartSvgText(interpolate(block.chart_title || block.title || 'Gráfico'))
  const backgroundColor = colorInputValue(interpolate(block.chart_background_color || '#f8f4ea'), '#f8f4ea')
  const primaryColor = colorInputValue(interpolate(block.chart_primary_color || colors[0] || '#143d37'), colors[0] || '#143d37')
  const showValues = block.chart_show_values !== false
  const palette = chartSvgPalette(backgroundColor)

  if (type === 'doughnut') {
    const total = values.reduce((sum, value) => sum + Math.max(value, 0), 0) || 1
    const circumference = 251.2
    let offset = 0
    const rings = values.map((value, index) => {
      const segment = (Math.max(value, 0) / total) * circumference
      const ring = `<circle cx="130" cy="128" r="40" fill="none" stroke="${colors[index % colors.length]}" stroke-width="18" stroke-dasharray="${segment.toFixed(2)} ${(circumference - segment).toFixed(2)}" stroke-dashoffset="${(-offset).toFixed(2)}" transform="rotate(-90 130 128)" />`
      offset += segment

      return ring
    }).join('')
    const legend = labels.map((label, index) => {
      const y = 70 + (index * 26)

      return `<g><rect x="250" y="${y - 10}" width="12" height="12" rx="3" fill="${colors[index % colors.length]}"/><text x="272" y="${y}" font-size="12" fill="${palette.muted}">${escapeStudioChartSvgText(interpolate(label))}</text><text x="520" y="${y}" font-size="12" font-weight="700" fill="${palette.ink}" text-anchor="end">${formatStudioChartValue(values[index] ?? 0)}</text></g>`
    }).join('')
    const totalText = escapeStudioChartSvgText(formatStudioChartValue(total))

    return `<svg class="report-chart-svg" data-chart-type="${type}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="${title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="${backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="${palette.ink}">${title}</text><circle cx="130" cy="128" r="40" fill="none" stroke="${palette.grid}" stroke-width="18"/>${rings}<text x="130" y="126" text-anchor="middle" font-size="18" font-weight="800" fill="${palette.ink}">${totalText}</text><text x="130" y="144" text-anchor="middle" font-size="10" fill="${palette.muted}">total</text>${legend}</svg>`
  }

  if (type === 'line') {
    const points = values.map((value, index) => {
      const x = 58 + (index * (470 / Math.max(values.length - 1, 1)))
      const y = 202 - ((value / maxValue) * 132)

      return `${x.toFixed(1)},${y.toFixed(1)}`
    }).join(' ')
    const markers = values.map((value, index) => {
      const x = 58 + (index * (470 / Math.max(values.length - 1, 1)))
      const y = 202 - ((value / maxValue) * 132)
      const valueText = showValues
        ? `<text x="${x.toFixed(1)}" y="${(y - 12).toFixed(1)}" text-anchor="middle" font-size="10" font-weight="700" fill="${palette.ink}">${formatStudioChartValue(value)}</text>`
        : ''

      return `<g><circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="5" fill="${colors[index % colors.length]}" stroke="${palette.markerStroke}" stroke-width="2"/>${valueText}<text x="${x.toFixed(1)}" y="230" text-anchor="middle" font-size="10" fill="${palette.muted}">${escapeStudioChartSvgText(interpolate(labels[index] || `S${index + 1}`))}</text></g>`
    }).join('')

    return `<svg class="report-chart-svg" data-chart-type="${type}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="${title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="${backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="${palette.ink}">${title}</text><line x1="52" y1="202" x2="532" y2="202" stroke="${palette.grid}"/><line x1="52" y1="70" x2="52" y2="202" stroke="${palette.grid}"/><polyline points="${points}" fill="none" stroke="${primaryColor}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>${markers}</svg>`
  }

  const slot = 470 / Math.max(values.length, 1)
  const bars = values.map((value, index) => {
    const height = Math.max(6, (value / maxValue) * 132)
    const x = 58 + (index * slot) + Math.max(6, slot * 0.15)
    const y = 202 - height
    const width = Math.max(18, slot * 0.7)
    const valueText = showValues
      ? `<text x="${(x + (width / 2)).toFixed(1)}" y="${(y - 10).toFixed(1)}" text-anchor="middle" font-size="11" font-weight="700" fill="${palette.ink}">${formatStudioChartValue(value)}</text>`
      : ''

    return `<g><rect x="${x.toFixed(1)}" y="${y.toFixed(1)}" width="${width.toFixed(1)}" height="${height.toFixed(1)}" rx="10" fill="${colors[index % colors.length]}"/>${valueText}<text x="${(x + (width / 2)).toFixed(1)}" y="230" text-anchor="middle" font-size="10" fill="${palette.muted}">${escapeStudioChartSvgText(interpolate(labels[index] || `S${index + 1}`))}</text></g>`
  }).join('')

  return `<svg class="report-chart-svg" data-chart-type="${type}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 260" role="img" aria-label="${title}" style="font-family:inherit;"><rect width="560" height="260" rx="24" fill="${backgroundColor}"/><text x="28" y="38" font-size="16" font-weight="700" fill="${palette.ink}">${title}</text><line x1="52" y1="202" x2="532" y2="202" stroke="${palette.grid}"/><line x1="52" y1="70" x2="52" y2="202" stroke="${palette.grid}"/>${bars}</svg>`
}
