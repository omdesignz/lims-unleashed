import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import { runInNewContext } from 'node:vm';

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

function receiptHarness(price = 0) {
  const source = readFileSync(new URL(componentPaths[3], import.meta.url), 'utf8');
  const { descriptor } = parse(source);
  const script = compileScript(descriptor, { id: 'receipt-harness' });
  const declaration = script.scriptSetupAst.find(node => node.type === 'FunctionDeclaration' && node.id.name === 'submitReceipt');
  assert.ok(declaration, 'Receipt submission function exists');
  const body = descriptor.scriptSetup.content.slice(declaration.start, declaration.end);
  const ref = value => ({ value });
  const calls = [];
  const context = {
    isSubmitting: ref(false), receiptError: ref(''), isReceivingSingleItem: ref(true),
    receiptRequestId: ref('6d4d704d-9d60-4d2e-b907-d11233f1b35d'),
    receivingItem: ref({ id: 1, unit_price: 20 }), receivingQuantity: ref('0.1250'),
    receivingUnitPrice: ref(price), pendingItems: ref([]), receivingQuantities: {},
    isValidReceiptQuantity: () => true, registerNonConformity: ref(false),
    nonConformityDescription: ref(''), nonConformityTitle: ref(''), nonConformitySeverity: ref('medium'),
    receiveDate: ref('2026-10-04'), receivingReason: ref('Receipt'), receivingNotes: ref('Keep draft'),
    props: { order: { id: 2 }, receivingAbilities: {receive: true, register_non_conformity: true} }, route: () => '/receive', closed: false,
    router: { post: (...args) => calls.push(args) },
  };
  context.closeReceivingModal = () => { if (!context.isSubmitting.value) context.closed = true; };
  runInNewContext(`${body}; this.submit = submitReceipt`, context);
  return { context, calls };
}

test('receipt stays pending until Inertia finishes and blocks overlapping submission', () => {
  const { context, calls } = receiptHarness();
  context.submit();
  assert.equal(context.isSubmitting.value, true);
  context.submit();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][1].items[0].unit_price, 0);
  calls[0][2].onFinish();
  assert.equal(context.isSubmitting.value, false);
});

test('receipt controls and direct submission respect separate receiving and dossier abilities', () => {
  const { context, calls } = receiptHarness();
  context.props.receivingAbilities.receive = false;
  context.submit();
  assert.equal(calls.length, 0);
  context.props.receivingAbilities.receive = true;
  context.props.receivingAbilities.register_non_conformity = false;
  context.registerNonConformity.value = true;
  context.submit();
  assert.equal(calls.length, 0);
  assert.match(context.receiptError.value, /Sem permissão/);
  context.registerNonConformity.value = false;
  context.submit();
  assert.equal(calls.length, 1);
  const show = readFileSync(new URL(componentPaths[3], import.meta.url), 'utf8');
  const index = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Orders/Index.vue', import.meta.url), 'utf8');
  assert.match(show, /canReceiveOrder = computed\(\(\) => props\.receivingAbilities\.receive/);
  assert.match(show, /:disabled="!nonConformitiesAvailable \|\| !receivingAbilities\.register_non_conformity"/);
  assert.match(index, /return props\.receivingAbilities\.receive &&/);
});

test('receipt preserves zero price and falls back only for blank or absent input', () => {
  for (const [input, expected] of [[0, 0], ['0', '0'], ['', 20], [null, 20]]) {
    const { context, calls } = receiptHarness(input);
    context.submit();
    assert.equal(calls[0][1].items[0].unit_price, expected);
  }
});

test('receipt failure callbacks retain draft and expose actionable messages', () => {
  for (const failure of ['onError', 'onNetworkError', 'onHttpException', 'onCancel']) {
    const { context, calls } = receiptHarness();
    context.submit();
    calls[0][2][failure]({ 'items.0.received_qty': 'Too much' });
    calls[0][2].onFinish();
    assert.equal(context.closed, false);
    assert.equal(context.isSubmitting.value, false);
    assert.equal(context.receivingNotes.value, 'Keep draft');
    assert.ok(context.receiptError.value);
    if (failure === 'onError') assert.equal(context.receiptError.value, 'Too much');
    context.submit();
    assert.equal(calls[1][1].request_id, calls[0][1].request_id);
  }
});

test('receipt success releases the close guard without a redundant reload', () => {
  const { context, calls } = receiptHarness();
  context.submit();
  calls[0][2].onSuccess();
  calls[0][2].onFinish();
  assert.equal(context.closed, true);
  assert.equal(context.isSubmitting.value, false);
  assert.equal(calls.length, 1);
});
