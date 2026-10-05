import test from 'node:test'
import assert from 'node:assert/strict'
import { code128Svg, code128Symbols, fillLabelPlaceholders, qrCodeSvg } from '../../resources/js/Support/label-codes.mjs'

test('Code 128 set B carries a start symbol, the data, its checksum and the stop symbol', () => {
  // Start B (104) + "PJJ123C" weighted by position, modulo 103.
  assert.deepEqual(code128Symbols('PJJ123C'), [104, 48, 42, 42, 17, 18, 19, 35, 55, 106])
  assert.equal(code128Symbols(''), null)
  assert.equal(code128Symbols('Água'), null, 'set B has no accented letters')
  assert.equal(code128Symbols('A\nB'), null)
})

test('the Code 128 drawing has the module count of its symbols and only bars', () => {
  const svg = code128Svg('AM-2026-0042', { color: '#111827' })
  const width = Number(/viewBox="0 0 (\d+) 1"/.exec(svg)[1])
  const symbols = code128Symbols('AM-2026-0042').length

  // Every symbol is 11 modules wide, the stop 13, plus a quiet zone of 10 on each side.
  assert.equal(width, (symbols - 1) * 11 + 13 + 20)
  // Three bars per symbol, four in the stop.
  assert.equal((svg.match(/<rect /g) || []).length, (symbols - 1) * 3 + 4)
  assert.match(svg, /fill="#111827"/)
  assert.equal(code128Svg('Ç'), null)
})

test('a QR code is drawn from its modules and empty content draws nothing', () => {
  const svg = qrCodeSvg('https://lims.test/v/AM-1', { color: '#000' })
  assert.match(svg, /^<svg[^>]+viewBox="0 0 (\d+) \1"/)
  assert.match(svg, /<path fill="#000" d="M/)
  assert.equal(qrCodeSvg(''), null)
})

test('placeholders are filled and an unknown or empty one stays visible', () => {
  assert.equal(fillLabelPlaceholders('{name}\n{code} · {lot}', { name: 'Farinha', code: 'AM-1', lot: '' }), 'Farinha\nAM-1 · {lot}')
  assert.equal(fillLabelPlaceholders('{nme}', { name: 'x' }), '{nme}')
})
