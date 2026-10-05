import { create as createQrCode } from 'qrcode'

/**
 * The machine-readable codes of a label, drawn in the browser so the Label
 * Studio shows what will be printed: Code 128 (set B) and QR codes, both as
 * SVG markup sized by their container.
 */

// Bar and space widths of each Code 128 symbol, 0–106 (106 is the stop symbol).
const CODE128_PATTERNS = [
  '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
  '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
  '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
  '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
  '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
  '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
  '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
  '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
  '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
  '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
  '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
]

const START_B = 104
const STOP = 106

/**
 * The symbols of a text in Code 128 set B, with its checksum, or null when
 * the text holds a character set B cannot encode (outside ASCII 32–126).
 *
 * @param {string} text
 * @returns {number[] | null}
 */
export function code128Symbols(text) {
  const value = String(text ?? '')

  if (value === '' || [...value].some((character) => character.charCodeAt(0) < 32 || character.charCodeAt(0) > 126)) {
    return null
  }

  const data = [...value].map((character) => character.charCodeAt(0) - 32)
  const checksum = data.reduce((sum, symbol, index) => sum + symbol * (index + 1), START_B) % 103

  return [START_B, ...data, checksum, STOP]
}

/**
 * Code 128 as SVG bars filling their box; null when the text cannot be encoded.
 *
 * @param {string} text
 * @param {{ color?: string }} options
 */
export function code128Svg(text, { color = '#000000' } = {}) {
  const symbols = code128Symbols(text)

  if (!symbols) {
    return null
  }

  const widths = symbols.flatMap((symbol) => [...CODE128_PATTERNS[symbol]].map(Number))
  const quiet = 10
  const total = widths.reduce((sum, width) => sum + width, 0) + quiet * 2
  let x = quiet
  const bars = []

  widths.forEach((width, index) => {
    if (index % 2 === 0) {
      bars.push(`<rect x="${x}" y="0" width="${width}" height="1"/>`)
    }
    x += width
  })

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${total} 1" preserveAspectRatio="none" fill="${escapeAttribute(color)}">${bars.join('')}</svg>`
}

/**
 * A QR code as SVG, one square per dark module; null for empty content.
 *
 * @param {string} text
 * @param {{ color?: string, background?: string }} options
 */
export function qrCodeSvg(text, { color = '#000000', background = 'transparent' } = {}) {
  const value = String(text ?? '')

  if (value === '') {
    return null
  }

  let modules

  try {
    modules = createQrCode(value, { errorCorrectionLevel: 'M' }).modules
  } catch {
    return null
  }

  const size = modules.size
  const quiet = 2
  const total = size + quiet * 2
  const cells = []

  for (let row = 0; row < size; row += 1) {
    for (let column = 0; column < size; column += 1) {
      if (modules.get(row, column)) {
        cells.push(`M${column + quiet} ${row + quiet}h1v1h-1z`)
      }
    }
  }

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${total} ${total}" shape-rendering="crispEdges">`
    + (background !== 'transparent' ? `<rect width="${total}" height="${total}" fill="${escapeAttribute(background)}"/>` : '')
    + `<path fill="${escapeAttribute(color)}" d="${cells.join('')}"/></svg>`
}

/**
 * A label's text with its placeholders filled; an unknown placeholder stays
 * as written, so a typing mistake is visible on the preview.
 *
 * @param {string} content
 * @param {Record<string, string | number | null | undefined>} values
 */
export function fillLabelPlaceholders(content, values = {}) {
  return String(content ?? '').replace(/\{([a-z_]+)\}/g, (match, key) => {
    const value = values[key]

    return value === null || value === undefined || value === '' ? match : String(value)
  })
}

/**
 * Recognisable values for every placeholder, used wherever a label is shown
 * without the record it will label.
 *
 * @returns {Record<string, string>}
 */
export function labelExampleValues() {
  return {
    name: 'Água de consumo',
    code: 'AM-2026-0042',
    type: 'Água',
    customer: 'Cliente de exemplo',
    department: 'Físico-química',
    warehouse: 'Câmara 2',
    lot: 'LT-2611',
    serial_number: 'SN-0042',
    expiry_date: '2026-12-31',
    received_at: '2026-10-05 09:30',
    date: new Date().toISOString().slice(0, 10),
    qr_content: 'AM-2026-0042',
    barcode_content: 'AM-2026-0042',
  }
}

function escapeAttribute(value) {
  return String(value).replace(/[^#a-zA-Z0-9(),.%\s-]/g, '')
}
