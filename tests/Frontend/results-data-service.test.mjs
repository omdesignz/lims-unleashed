import assert from 'node:assert/strict'
import test from 'node:test'
import { ResultsDataService } from '../../resources/js/Services/ResultsDataService.js'

test('normalizes qualitative result options from parameter metadata', () => {
  const result = ResultsDataService.normalizeResults([
    {
      parameter_id: {
        value: 10,
        code: 'SAL',
        result_is_qualitative: true,
      },
    },
  ])[0]

  assert.equal(ResultsDataService.isQualitativeResult(result), true)
  assert.deepEqual(ResultsDataService.getQualitativeOptions(result), ['Presença', 'Ausência'])
})

test('formats numeric result values in scientific notation without mutating raw values', () => {
  const result = {
    inserted_value: '0.000012',
    decimal_places: 2,
    extra_data: {
      display_format: 'scientific',
    },
  }

  assert.equal(ResultsDataService.formatResultValue(result.inserted_value, result), '1.20 × 10^-5')
  assert.equal(result.inserted_value, '0.000012')
})

test('keeps non-numeric qualitative values unchanged under scientific display mode', () => {
  const result = {
    inserted_value: 'Presença',
    result_is_qualitative: true,
    display_format: 'scientific',
  }

  assert.equal(ResultsDataService.formatResultValue(result.inserted_value, result), 'Presença')
})

test('treats zero as a valid result value', () => {
  const result = {
    inserted_value: 0,
    decimal_places: 2,
    display_format: 'scientific',
  }

  assert.equal(ResultsDataService.hasResultValue(result.inserted_value), true)
  assert.equal(ResultsDataService.formatResultValue(result.inserted_value, result), '0.00 × 10^+0')
})
