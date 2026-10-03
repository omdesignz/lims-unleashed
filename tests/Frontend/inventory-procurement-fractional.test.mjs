import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileTemplate, parse } from '@vue/compiler-sfc';

const componentPaths = [
  '../../resources/js/Pages/VAPInventory/Needs/Create.vue',
  '../../resources/js/Pages/VAPInventory/Needs/Show.vue',
  '../../resources/js/Components/vap-inventory/InventoryOrderFormSurface.vue',
  '../../resources/js/Pages/VAPInventory/Orders/Show.vue',
  '../../resources/js/Pages/InventoryDeliveries/InventoryDeliveryForm.vue',
];

test('procurement forms compile and accept four-decimal quantities', () => {
  for (const componentPath of componentPaths) {
    const source = readFileSync(new URL(componentPath, import.meta.url), 'utf8');
    const { descriptor } = parse(source);
    const compiled = compileTemplate({ id: componentPath, source: descriptor.template.content, filename: componentPath });

    assert.deepEqual(compiled.errors, [], componentPath);
    assert.match(source, /step="0\.0001"/, componentPath);
    assert.doesNotMatch(source, /Number\.parseInt\(item\.(?:qty|received_qty)/, componentPath);
  }
});

test('procurement summaries count lines without adding unlike units', () => {
  const needCreate = readFileSync(new URL(componentPaths[0], import.meta.url), 'utf8');
  const needShow = readFileSync(new URL(componentPaths[1], import.meta.url), 'utf8');
  const orderForm = readFileSync(new URL(componentPaths[2], import.meta.url), 'utf8');
  const orderShow = readFileSync(new URL(componentPaths[3], import.meta.url), 'utf8');
  const deliveryForm = readFileSync(new URL(componentPaths[4], import.meta.url), 'utf8');

  assert.match(needCreate, /validQuantityCount/);
  assert.match(needShow, /approvedLineCount/);
  assert.match(orderForm, /Linhas com quantidade válida/);
  assert.match(orderShow, /Progressão média por linha/);
  assert.match(deliveryForm, /linhas válidas/);
  assert.doesNotMatch(needShow, /totalRequestedQuantity|totalApprovedQuantity/);
  assert.doesNotMatch(orderShow, /totalQuantity|receivedQuantity|pendingQuantity/);
});
