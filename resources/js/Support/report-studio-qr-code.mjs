import { create as createQrCode } from 'qrcode'
import { normalizedHexColor } from './report-studio-chart-palette.mjs'

function defaultInterpolate(value) {
  return String(value ?? '')
}

function defaultColorInputValue(value, fallback) {
  return normalizedHexColor(value, fallback)
}

export function studioQrCodeErrorCorrectionLevel(value) {
  return {
    high: 'H',
    low: 'L',
    medium: 'M',
    quartile: 'Q',
  }[value] || 'L'
}

export function studioQrCodeMargin(value, fallback = 8) {
  const parsed = Number(value)
  const margin = Number.isFinite(parsed) ? parsed : fallback

  return Math.max(0, Math.min(32, Math.round(margin)))
}

export function generatedStudioQrCodeDataUri(block = {}, options = {}) {
  const interpolate = typeof options.interpolate === 'function' ? options.interpolate : defaultInterpolate
  const colorInputValue = typeof options.colorInputValue === 'function' ? options.colorInputValue : defaultColorInputValue
  const fallbackContent = options.fallbackContent || '{document_code}'
  const qrContent = String(interpolate(block.qr_content || block.content_html || fallbackContent) ?? '').trim()

  if (!qrContent) {
    return ''
  }

  try {
    const qrCode = createQrCode(qrContent, {
      errorCorrectionLevel: studioQrCodeErrorCorrectionLevel(block.qr_error_correction),
    })
    const quietZone = studioQrCodeMargin(block.qr_margin)
    const moduleCount = qrCode.modules.size
    const viewBoxSize = moduleCount + (quietZone * 2)
    const foregroundColor = colorInputValue(interpolate(block.qr_foreground_color || '#0f172a'), '#0f172a')
    const backgroundColor = colorInputValue(interpolate(block.qr_background_color || '#ffffff'), '#ffffff')
    const modulePath = []

    for (let row = 0; row < moduleCount; row += 1) {
      for (let column = 0; column < moduleCount; column += 1) {
        if (qrCode.modules.get(row, column)) {
          modulePath.push(`M${column + quietZone} ${row + quietZone}h1v1h-1z`)
        }
      }
    }

    const svg = [
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${viewBoxSize} ${viewBoxSize}" shape-rendering="crispEdges">`,
      `<rect width="100%" height="100%" fill="${backgroundColor}"/>`,
      `<path d="${modulePath.join('')}" fill="${foregroundColor}"/>`,
      '</svg>',
    ].join('')

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`
  } catch {
    return ''
  }
}
