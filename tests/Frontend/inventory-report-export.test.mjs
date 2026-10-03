import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileTemplate, parse } from '@vue/compiler-sfc';

const component = readFileSync(new URL('../../resources/js/Components/vap-inventory/InventoryReportExportButton.vue', import.meta.url), 'utf8');
const reports = [
  ['StockMovement', 'stock_movement'],
  ['Consumption', 'consumption'],
  ['InventoryValue', 'inventory_value'],
  ['LowStock', 'low_stock'],
];

test('inventory reports use a native CSRF-protected download instead of an Inertia visit', () => {
  const { descriptor } = parse(component);
  const compiled = compileTemplate({ id: 'inventory-report-export', source: descriptor.template.content, filename: 'InventoryReportExportButton.vue' });
  assert.deepEqual(compiled.errors, []);
  assert.match(component, /<form :action="route\('vap-inventory\.reports\.export'\)" method="post">/);
  assert.match(component, /name="_token" :value="csrfToken"/);
  assert.match(component, /name="format" value="pdf"/);
  assert.match(component, /`filters\[\$\{key\}\]`/);

  for (const [page, reportType] of reports) {
    const source = readFileSync(new URL(`../../resources/js/Pages/VAPInventory/Reports/${page}.vue`, import.meta.url), 'utf8');
    assert.match(source, new RegExp(`<InventoryReportExportButton report-type="${reportType}" :filters="filters" \\/>`));
    assert.doesNotMatch(source, /router\.post\(route\('vap-inventory\.reports\.export'/);
  }
});
