import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';

const component = readFileSync(new URL('../../resources/js/Components/vap-inventory/InventoryReportExportButton.vue', import.meta.url), 'utf8');
const downloadSource = readFileSync(new URL('../../resources/js/Composables/useFileDownload.js', import.meta.url), 'utf8');
const reports = [
  ['StockMovement', 'stock_movement'],
  ['Consumption', 'consumption'],
  ['InventoryValue', 'inventory_value'],
  ['LowStock', 'low_stock'],
];

test('inventory reports use a guarded CSRF-protected download instead of an Inertia visit', () => {
  const { descriptor } = parse(component);
  const script = compileScript(descriptor, { id: 'inventory-report-export' });
  const compiled = compileTemplate({ id: 'inventory-report-export', source: descriptor.template.content, filename: 'InventoryReportExportButton.vue', compilerOptions: { bindingMetadata: script.bindings } });
  assert.deepEqual(compiled.errors, []);
  assert.match(component, /useFileDownload/);
  assert.match(component, /:disabled="processing \|\| !csrfToken"/);
  assert.match(component, /:aria-busy="processing"/);
  assert.match(component, /role="alert"/);
  assert.match(component, /role="status"/);
  assert.doesNotMatch(component, /router\.|<form/);

  for (const [page, reportType] of reports) {
    const source = readFileSync(new URL(`../../resources/js/Pages/VAPInventory/Reports/${page}.vue`, import.meta.url), 'utf8');
    assert.match(source, new RegExp(`<InventoryReportExportButton report-type="${reportType}" :filters="filters" \\/>`));
    assert.doesNotMatch(source, /router\.post\(route\('vap-inventory\.reports\.export'/);
  }
  const consumption = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Reagents/Consumption.vue', import.meta.url), 'utf8');
  assert.match(consumption, /<Head title="Consumo de reagentes"/);
  assert.match(consumption, /<InventoryReportExportButton report-type="consumption" :filters="filters.data\(\)"/);
  assert.doesNotMatch(consumption, /router\.post\(route\('vap-inventory\.reports\.export'/);
});

test('export payload preserves current filters and zero, omits empty values and requires CSRF and idle state', async () => {
  const body = component.match(/function exportReport\(\) \{([\s\S]*?)\n\}/)[1];
  const calls = [];
  const processing = { value: false };
  const csrfToken = { value: '' };
  const props = { reportType: 'consumption', filters: { item_id: 8, warehouse_id: 0, active: false, search: '', date_from: null, date_to: undefined } };
  const run = new Function('props', 'csrfToken', 'processing', 'download', 'route', `return () => {${body}}`)(
    props, csrfToken, processing, async (...args) => { calls.push(args); return true; }, name => `/${name}`,
  );
  await run();
  assert.equal(calls.length, 0);
  csrfToken.value = 'test-csrf';
  processing.value = true;
  await run();
  assert.equal(calls.length, 0);
  processing.value = false;
  assert.equal(await run(), true);
  const [url, options] = calls[0];
  assert.equal(url, '/vap-inventory.reports.export');
  assert.equal(options.method, 'POST');
  assert.deepEqual(options.headers, { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': 'test-csrf' });
  assert.deepEqual(JSON.parse(options.body), { report_type: 'consumption', format: 'pdf', filters: { item_id: 8, warehouse_id: 0, active: false } });
  assert.equal(props.filters.search, '');
  props.filters.item_id = 9;
  await run();
  assert.equal(JSON.parse(calls[1][1].body).filters.item_id, 9);
});

function downloader(fetch) {
  const events = [];
  const anchor = { click: () => events.push('click'), remove: () => events.push('remove') };
  const make = new Function('ref', 'fetch', 'URL', 'document', 'setTimeout', `${downloadSource.replace("import { ref } from 'vue'", '').replace('export function', 'function')}; return useFileDownload()`);
  return { ...make(value => ({ value }), fetch,
    { createObjectURL: () => 'blob:report', revokeObjectURL: () => events.push('revoke') },
    { createElement: () => anchor, body: { appendChild: () => events.push('append') } }, callback => callback()), events, anchor };
}

const response = () => ({ ok: true, headers: { get: () => 'attachment; filename="consumption.pdf"' }, blob: async () => ({}) });

test('POST downloads keep credentials and CSRF, block duplicate submissions and recover from validation errors', async () => {
  let settle;
  const calls = [];
  const state = downloader((...args) => { calls.push(args); return new Promise(resolve => { settle = resolve; }); });
  const options = { method: 'POST', body: '{"filters":{"search":"batch"}}', headers: { 'X-CSRF-TOKEN': 'test-csrf', 'Content-Type': 'application/json' } };
  const pending = state.download('/export', options);
  assert.equal(state.processing.value, true);
  assert.equal(await state.download('/export', options), false);
  assert.equal(calls.length, 1);
  assert.deepEqual(calls[0][1], { ...options, credentials: 'same-origin', headers: { ...options.headers, Accept: 'application/json' } });
  settle({ ok: false, json: async () => ({ errors: { 'filters.date_to': ['Intervalo inválido.'] } }) });
  assert.equal(await pending, false);
  assert.equal(state.error.value, 'Intervalo inválido.');
  assert.equal(state.processing.value, false);
  assert.deepEqual(state.events, []);
  const retry = state.download('/export', options);
  assert.equal(state.error.value, '');
  settle(response());
  assert.equal(await retry, true);
  assert.equal(state.anchor.download, 'consumption.pdf');
  assert.deepEqual(state.events, ['append', 'click', 'remove', 'revoke']);
  assert.equal(state.processing.value, false);
  assert.equal(options.headers.Accept, undefined);
});

test('POST download network failure preserves request data and permits a successful retry', async () => {
  let calls = 0;
  const state = downloader(async () => { if (++calls === 1) throw new Error('offline'); return response(); });
  const options = { method: 'POST', body: '{"filters":{"item_id":8}}' };
  assert.equal(await state.download('/export', options), false);
  assert.ok(state.error.value);
  assert.equal(state.processing.value, false);
  assert.deepEqual(state.events, []);
  assert.equal(await state.download('/export', options), true);
  assert.equal(state.error.value, '');
  assert.equal(options.body, '{"filters":{"item_id":8}}');
});
