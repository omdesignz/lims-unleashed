import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc';

const source = readFileSync(new URL('../../resources/js/Composables/useQuoteAuthoring.js', import.meta.url), 'utf8');
const { prepareQuoteLine, selectQuoteCatalog, quotePayload, quoteLinePreview, saveQuoteForm } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('agreed price previews mirror server cent rounding and quantity-weighted discounts', () => {
    const item = prepareQuoteLine({ item_id: { value: 2, catalog_type: 'product', price: '999.00', charge_tax: true, tax_percentage: '14.00' },
        agreed_unit_price: '10.00', qty: '2.50', discount_mode: 'percentage', discount_value: '10.00' });
    const line = quoteLinePreview(item);
    assert.equal(line.unit_price, 9);
    assert.equal(line.total, 22.5);
    assert.equal(line.discount_total, 2.5);
    assert.equal(line.tax_amount, 3.15);
    item.agreed_unit_price = '12.00';
    assert.equal(quoteLinePreview(item).total, 27);
    Object.assign(item, { agreed_unit_price: '0.05', qty: '1.50', discount_mode: 'fixed', discount_value: '0.02' });
    item.item_id.charge_tax = false;
    assert.equal(quoteLinePreview(item).total, 0.05);
    assert.equal(quoteLinePreview(item).discount_total, 0.03);
    assert.equal(quoteLinePreview(item).tax_amount, 0);
    item.qty = '1.001';
    assert.equal(quoteLinePreview(item).valid, false);
});

test('catalog selection establishes editable price and explicit catalog identity', () => {
    const line = prepareQuoteLine();
    line.item_id = { value: 8, catalog_type: 'paid_service', price: '14.50' };
    selectQuoteCatalog(line);
    assert.equal(line.catalog_type, 'paid_service');
    assert.equal(line.agreed_unit_price, '14.50');
    line.agreed_unit_price = '11.00';
    assert.equal(quotePayload({ items: [line] }).items[0].agreed_unit_price, '11.00');
});

test('payload excludes identity, actor, billing fields, catalog tax and calculated totals', () => {
    const data = { id: 1, user_id: 99, total: 999, status: true, converted_to_invoice: true, quote_no: 'FORGED',
        customer_id: { value: 3 }, warehouse_id: { value: 4 }, use_matrix_price: false, is_service: false,
        items: [prepareQuoteLine({ item_id: { value: 2, catalog_type: 'parameter', price: '10.00', tax_percentage: 999 },
            unit_id: { value: 5 }, itemable_id: { value: 20, lab_code_id: 200 }, itemable_type: 'collectionproduct',
            qty: '2.50', discount_mode: 'fixed', discount_value: '1.00', total: 999, lab_id: 8 })] };
    assert.deepEqual(quotePayload(data), {
        customer_id: 3, warehouse_id: 4, use_matrix_price: false, is_service: false,
        items: [{ catalog_type: 'parameter', item_id: 2, unit_id: 5, collection_product_id: 20, qty: '2.50', agreed_unit_price: '10.00',
            discount_mode: 'fixed', discount_value: '1.00', obs: null }],
    });
});

test('submission suppresses duplicates and retains draft through network, HTTP and dispatch errors', () => {
    const calls = [];
    const form = { processing: false, obs: 'Draft', items: [], errors: {}, clearErrors() {},
        transform(callback) { this.payload = callback(this); return this; },
        put(url, options) { this.processing = true; calls.push({ url, options }); },
        setError(key, value) { this.errors[key] = value; } };
    saveQuoteForm(form, '/quote', 'put');
    saveQuoteForm(form, '/quote', 'put');
    assert.equal(calls.length, 1);
    assert.equal(calls[0].options.preserveState, true);
    calls[0].options.onNetworkError();
    assert.match(form.errors.request, /rascunho foi preservado/);
    calls[0].options.onHttpException();
    assert.equal(form.obs, 'Draft');
    form.processing = false;
    form.put = () => { throw new Error('Dispatch'); };
    saveQuoteForm(form, '/quote', 'put');
    assert.match(form.errors.request, /iniciar/);
    assert.equal(form.obs, 'Draft');
});

for (const name of ['Create', 'Edit']) {
    test(`${name} quote component compiles with bound agreed prices, explicit discounts and visible errors`, () => {
        const component = readFileSync(new URL(`../../resources/js/Pages/Quotes/${name}.vue`, import.meta.url), 'utf8');
        const { descriptor } = parse(component);
        const script = compileScript(descriptor, { id: `quote-${name}` });
        const template = compileTemplate({ source: descriptor.template.content, id: `quote-${name}`, compilerOptions: { bindingMetadata: script.bindings } });
        assert.deepEqual(template.errors, []);
        assert.match(component, /v-model="item.item.agreed_unit_price"/);
        const priceInput = component.match(/<BaseInput\s+v-model="item.item.agreed_unit_price"[\s\S]*?\/>/)[0];
        assert.doesNotMatch(priceInput, /\sdisabled(?:\s|\/)/);
        assert.match(component, /v-model="item.item.discount_mode"/);
        assert.match(component, /Object.values\(form.errors\)/);
        assert.doesNotMatch(component, /formatted_items:/);
    });
}

test('quote detail shows agreed unit prices, gross subtotal, included discount and tax consistently', () => {
    const component = readFileSync(new URL('../../resources/js/Pages/Quotes/Show.vue', import.meta.url), 'utf8');
    const { descriptor } = parse(component);
    const script = compileScript(descriptor, { id: 'quote-show' });
    const template = compileTemplate({ source: descriptor.template.content, id: 'quote-show', compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(template.errors, []);
    assert.match(component, /item.extra_data\?\.agreed_unit_price/);
    assert.match(component, /Number\(props.record.data\?\.sub_total \|\| 0\) \+ Number\(props.record.data\?\.discount \|\| 0\)/);
    assert.match(component, /formatCurrency\(props.record.data\?\.tax\)/);
});
