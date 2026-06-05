export function normalizedHexColor(value, fallback = '#0f172a') {
  return /^#[0-9a-f]{6}$/i.test(String(value || '')) ? value : fallback
}

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
