import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { recordTitle } from '../../resources/js/Utils/recordTitle.js';

test('mobile titles skip QR and image payloads and use a meaningful visible field', () => {
  const row = { id: 7, qr: 'data:image/svg+xml;base64,QR', image: '/photo.png', cl: '26/09/0002' };
  const columns = [
    { field: 'qr', type: 'qr', visible: true },
    { field: 'image', type: 'image', visible: true },
    { field: 'cl', type: 'text', visible: true },
  ];
  assert.equal(recordTitle(row, columns), '26/09/0002');
});

test('explicit record identity remains stable when table columns are hidden or reordered', () => {
  const row = { id: 7, cl: '26/09/0002', customer: 'Demo customer' };
  const columns = [{ field: 'customer', visible: true }, { field: 'cl', visible: false }];
  assert.equal(recordTitle(row, columns, 'cl'), '26/09/0002');
  assert.equal(recordTitle(row, [...columns].reverse(), 'cl'), '26/09/0002');
});

test('fallback titles reject data URLs, empty text, object and boolean values', () => {
  const row = { id: 9, qr: ' DATA:image/svg+xml;base64,QR ', blank: ' ', customer: { name: 'Demo' }, done: true };
  assert.equal(recordTitle(row, ['qr', 'blank', 'customer', 'done'].map((field) => ({ field })), 'qr'), '#9');
  assert.equal(recordTitle({ id: 0 }, []), '#0');
  assert.equal(recordTitle({}, []), '#—');
});

test('titles support nested fields, literal dotted keys, and valid zero values', () => {
  assert.equal(recordTitle({ id: 7, sample: { code: 'SMP-0001' } }, [], 'sample.code'), 'SMP-0001');
  assert.equal(recordTitle({ id: 7, 'sample.code': 'SMP-0002', sample: { code: 'SMP-0001' } }, [], 'sample.code'), 'SMP-0002');
  assert.equal(recordTitle({ id: 7, count: 0 }, [{ field: 'count' }]), '0');
  assert.equal(recordTitle({ id: 7, count: NaN }, [{ field: 'count' }]), '#7');
});

test('collection cards bind their semantic identifier through the safe table title helper', () => {
  const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');
  assert.match(read('Pages/DirectCollections/Index.vue'), /row-title-field="cl"/);
  const table = read('Components/vap-table/table.vue');
  assert.match(table, /recordTitle\(row, visibleColumns, props\.rowTitleField\)/);
  assert.doesNotMatch(table, /row\[visibleColumns\[0\]/);
});
