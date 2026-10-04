import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc';

const source = readFileSync(new URL('../../resources/js/Composables/useQuoteAuthoring.js', import.meta.url), 'utf8');
const { prepareQuoteLine, selectQuoteCatalog, quotePayload, quoteLinePreview, saveQuoteForm, quoteSiteLabel, quoteBillingLabel } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const optionsSource = readFileSync(new URL('../../resources/js/Composables/useCommercialDocumentOptions.js', import.meta.url), 'utf8');
const { optionRows, siteOption, createCustomerSiteLoader } = await import(`data:text/javascript;base64,${Buffer.from(optionsSource).toString('base64')}`);

test('quote detail uses retained site identity and actual billing links instead of a boolean status', () => {
    assert.equal(quoteSiteLabel({warehouse:' ',warehouse_id:{name:'Named site'}}), 'Named site');
    assert.equal(quoteSiteLabel({warehouse:'Address',warehouse_id:{name:'Site'}}), 'Address');
    assert.equal(quoteSiteLabel({warehouse_id:{code:'CODE'}}), 'CODE');
    assert.equal(quoteSiteLabel({}), 'Local não disponível');
    assert.equal(quoteBillingLabel({status:false}), 'Não facturada');
    assert.equal(quoteBillingLabel({status:true}), 'Não facturada');
    assert.equal(quoteBillingLabel({invoice_id:8,converted_to_invoice:false}), 'Facturada');
    assert.equal(quoteBillingLabel({converted_to_invoice:true}), 'Facturada');
    const component = readFileSync(new URL('../../resources/js/Pages/Quotes/Show.vue', import.meta.url), 'utf8');
    assert.equal((component.match(/quoteBillingLabel\(props.record.data \?\? \{\}\)/g) || []).length, 2);
    assert.match(component, /quoteSiteLabel\(props.record.data \?\? \{\}\)/);
    assert.match(component, /return hasPermission\('edit_quotes'\)/);
    assert.doesNotMatch(component, /\['pending', 'draft'\]\.includes/);
});

test('enhanced pickers associate a field label and disable the whole interaction', () => {
    const component = readFileSync(new URL('../../resources/js/Components/combobox-enhanced.vue', import.meta.url), 'utf8');
    const { descriptor } = parse(component);
    const script = compileScript(descriptor, { id: 'quote-picker' });
    const template = compileTemplate({ source: descriptor.template.content, id: 'quote-picker', compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(template.errors, []);
    assert.match(component, /<Combobox\s+by="value"\s+:disabled="props.disableInput"/);
    assert.match(component, /<ComboboxLabel v-else-if="props.inputLabel" class="sr-only">\{\{ props.inputLabel \}\}<\/ComboboxLabel>/);
    assert.match(component, /<ComboboxLabel v-if="props.titleLabel"/);
});

test('site labels remain useful without an address and missing customers make no request', async () => {
    assert.deepEqual(siteOption({ id: 8, address: ' ', name: 'Named site' }), { value: 8, label: 'Named site' });
    assert.equal(siteOption({ id: 8, address: 'Address', name: 'Name' }).label, 'Address');
    assert.equal(siteOption({ id: 8, code: 'CODE' }).label, 'CODE');
    assert.equal(siteOption({ id: 8 }).label, 'Local 8');
    const rows = [];
    const loading = [];
    const loader = createCustomerSiteLoader(() => { throw new Error('No HTTP instance expected'); }, () => null, () => '/sites',
        () => {}, value => loading.push(value));
    assert.deepEqual(await loader.load('', options => rows.push(options)), []);
    assert.deepEqual(rows, [[]]);
    assert.deepEqual(loading, [false]);
});

test('site lookup retains a recoverable error through transport or validation failure and clears it on retry', async () => {
    const errors = [];
    const rows = [];
    const loading = [];
    const outcomes = [new Error('HTTP 503'), undefined, { data: [{ id: 4, name: 'Site' }] }];
    let disposed = 0;
    const loader = createCustomerSiteLoader(() => ({ http: { cancel() {}, async get(url) {
        assert.equal(url, '/sites');
        assert.equal(this.customer_id, 3);
        assert.equal(this.q, 'A&B');
        const outcome = outcomes.shift();
        if (outcome instanceof Error) throw outcome;
        return outcome;
    } }, dispose() { disposed++; } }), () => 3, () => '/sites', message => errors.push(message), value => loading.push(value));
    for (let attempt = 0; attempt < 3; attempt++) await loader.load('A&B', options => rows.push(options));
    assert.deepEqual(rows, [[], [], [{ value: 4, label: 'Site' }]]);
    assert.match(errors[0], /pesquisar novamente/);
    assert.match(errors[1], /pesquisar novamente/);
    assert.equal(errors[2], null);
    assert.equal(disposed, 3);
    assert.deepEqual(loading, [true, false, true, false, true, false]);
});

test('site requests cancel obsolete work and old completion cannot replace new customer options or loading state', async () => {
    let customer = 3;
    const pending = [];
    const rows = [];
    const loading = [];
    const loader = createCustomerSiteLoader(() => {
        const entry = { cancelled: 0, disposed: 0 };
        pending.push(entry);
        return { http: { cancel() { entry.cancelled++; }, get() { return new Promise(resolve => { entry.resolve = resolve; }); } },
            dispose() { entry.disposed++; } };
    }, () => customer, () => '/sites', () => {}, value => loading.push(value));
    const first = loader.load('old', options => rows.push(options));
    customer = 6;
    const second = loader.load('new', options => rows.push(options));
    assert.equal(pending[0].cancelled, 1);
    assert.equal(pending[0].disposed, 1);
    pending[0].resolve([{ id: 4, name: 'Obsolete' }]);
    await first;
    assert.deepEqual(rows, []);
    assert.deepEqual(loading, [true, true]);
    pending[1].resolve([{ id: 7, name: 'Current' }]);
    await second;
    assert.deepEqual(rows, [[{ value: 7, label: 'Current' }]]);
    assert.deepEqual(loading, [true, true, false]);
    const third = loader.load('closing', options => rows.push(options));
    loader.dispose();
    assert.equal(pending[2].cancelled, 1);
    pending[2].resolve([{ id: 9, name: 'After unmount' }]);
    await third;
    assert.equal(rows.length, 1);
});

test('both quote forms use guarded Inertia HTTP site options and canonical discount modes', () => {
    const composable = readFileSync(new URL('../../resources/js/Composables/useCustomerSiteOptions.js', import.meta.url), 'utf8');
    assert.match(composable, /useHttp\(\{ customer_id: null, q: '' \}\)/);
    assert.match(composable, /effectScope\(true\)/);
    assert.match(composable, /onScopeDispose\(loader.dispose\)/);
    assert.match(composable, /form.warehouse_id = null/);
    for (const name of ['Create', 'Edit']) {
        const component = readFileSync(new URL(`../../resources/js/Pages/Quotes/${name}.vue`, import.meta.url), 'utf8');
        assert.match(component, /useCustomerSiteOptions\(form\)/);
        assert.doesNotMatch(component, /fetch\(['"]\/warehouses\/getWarehouse/);
        assert.match(component, /<option value="percentage">/);
        assert.match(component, /<option value="fixed">/);
        assert.doesNotMatch(component, /props.discount_categories/);
        assert.match(component, /<Head/);
    }
});

test('catalog-derived quote lines normalize array and resource responses before preparing agreed prices', async () => {
    const component = readFileSync(new URL('../../resources/js/Pages/Quotes/Create.vue', import.meta.url), 'utf8');
    const rows = [{ item_id: { value: 8, catalog_type: 'parameter', price: '12.50' }, qty: '2.00', agreed_unit_price: '0.00' }];
    for (const name of ['loadParametersBasedOnLabCode', 'loadProductsBasedOnLabCode', 'loadUninvoiceProductsByWarehouse']) {
        const loader = component.match(new RegExp(`function ${name}\\([^)]*\\)\\s*\\{[\\s\\S]*?\\n\\}`))[0];
        for (const payload of [rows, { data: rows }, { items: rows }, null, { data: null }]) {
            const form = { items: [], use_matrix_price: false };
            const run = new Function('form', 'fetch', 'optionRows', 'prepareQuoteLine', `${loader}; return ${name}`)(
                form, async () => ({ json: async () => payload }), optionRows, prepareQuoteLine,
            );
            run(8);
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(form.items.length, payload === null || payload.data === null ? 0 : 1);
            if (form.items.length) {
                assert.equal(form.items[0].agreed_unit_price, '0.00');
                assert.equal(form.items[0].catalog_type, 'parameter');
            }
        }
    }
});

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
    assert.equal(calls[0].options.onNetworkError(), false);
    assert.match(form.errors.request, /rascunho foi preservado/);
    assert.equal(calls[0].options.onHttpException(), false);
    assert.equal(form.obs, 'Draft');
    form.processing = false;
    form.put = () => { throw new Error('Dispatch'); };
    saveQuoteForm(form, '/quote', 'put');
    assert.match(form.errors.request, /iniciar/);
    assert.equal(form.obs, 'Draft');
});

test('create confirmation is released before dispatch so failed saves can be confirmed again', () => {
    const component = readFileSync(new URL('../../resources/js/Pages/Quotes/Create.vue', import.meta.url), 'utf8');
    const handler = component.match(/const submit = \(\) => \{[\s\S]*?\n\};/)[0];
    const confirmation = { value: true };
    const form = { reset() {} };
    let calls = 0;
    const submit = new Function('showDeleteConfirmation', 'saveQuoteForm', 'form', 'route', `${handler}; return submit;`)(
        confirmation, (submittedForm, url, method) => {
            assert.equal(confirmation.value, false);
            assert.equal(submittedForm, form);
            assert.equal(url, '/quotes');
            assert.equal(method, 'post');
            calls++;
        }, form, () => '/quotes',
    );
    submit();
    confirmation.value = true;
    submit();
    assert.equal(calls, 2);
    assert.equal(confirmation.value, false);
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
