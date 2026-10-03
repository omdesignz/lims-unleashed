import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');
const direct = read('Pages/DirectCollections/Index.vue');
const programmed = read('Pages/ProgrammedCollections/Index.vue');
const formSource = read('Components/collections/CollectionAccessionForm.vue');

function functionBody(source, name) {
  return source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}\\n`))[1];
}

test('direct collection row and bulk archival use their real POST route and suppress retries', () => {
  const calls = [];
  const pendingActionType = { value: 'bulk' };
  const pendingAction = { value: 'delete' };
  const pendingRow = { value: { id: 17 } };
  const selectedIds = { value: [7, 8] };
  const isSubmitting = { value: false };
  let closed = 0;
  const action = new Function('pendingActionType', 'pendingAction', 'pendingRow', 'selectedIds', 'isSubmitting', 'router', 'route', 'closeActionConfirmation', functionBody(direct, 'executeAction'));
  const execute = () => action(pendingActionType, pendingAction, pendingRow, selectedIds, isSubmitting, { post: (...args) => calls.push(args) }, (name) => name, () => closed++);

  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'directcollections.destroy');
  assert.deepEqual(calls[0][1], { recordIds: [7, 8] });
  calls[0][2].onFinish();
  assert.equal(isSubmitting.value, false);
  assert.equal(closed, 1);

  pendingActionType.value = 'single';
  pendingAction.value = 'restore';
  execute();
  assert.equal(calls[1][0], 'directcollections.restore');
  assert.deepEqual(calls[1][1], { recordIds: [17] });
  assert.match(direct, /:disabled="isSubmitting" title="Arquivar colheita"/);
  assert.match(direct, /role="status"/);
});

test('programmed collection bulk archival uses POST while row controls opt into the same boundary', () => {
  const calls = [];
  const selectedRecordIds = { value: [19] };
  const selectedAction = { value: 'delete' };
  const isSubmitting = { value: false };
  let closed = 0;
  const action = new Function('selectedRecordIds', 'selectedAction', 'isSubmitting', 'router', 'route', 'closeActionConfirmation', functionBody(programmed, 'executeBulkAction'));
  const execute = () => action(selectedRecordIds, selectedAction, isSubmitting, { post: (...args) => calls.push(args) }, (name) => name, () => closed++);

  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'programmedcollections.destroy');
  assert.deepEqual(calls[0][1], { recordIds: [19] });
  calls[0][2].onFinish();
  assert.equal(isSubmitting.value, false);
  assert.equal(closed, 1);
  selectedAction.value = 'restore';
  execute();
  assert.equal(calls[1][0], 'programmedcollections.restore');
  assert.match(programmed, /:action-methods="\{ delete: 'post', restore: 'post' \}"/);
  assert.match(programmed, /:action-processing="isSubmitting"/);
  assert.match(programmed, /:action-confirmation="actionConfirmation"/);
});

test('correction submission is single-record, dirty-only, and busy guarded', () => {
  const calls = [];
  const form = { id: 23, processing: false, isDirty: false, put: (...args) => { calls.push(args); form.processing = true; } };
  const submit = new Function('form', 'config', 'route', functionBody(formSource, 'submit'));
  const execute = () => submit(form, { value: { routePrefix: 'directcollections' } }, (name, params) => `${name}/${params.collection}`);

  execute();
  assert.equal(calls.length, 0);
  form.isDirty = true;
  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'directcollections.update/23');
  assert.equal(calls[0][1].preserveScroll, true);
  assert.equal(calls[0][1].preserveState, 'errors', 'Rejected edits must retain their draft and field errors; successful edits reload fresh defaults.');
  assert.doesNotMatch(formSource, /form\.post|specimens|form\.products|addProduct|removeProduct/);
});

test('correction identity is read-only and owner search uses only supplied local operators', () => {
  for (const id of ['collection-customer', 'collection-site', 'sample-product']) {
    assert.match(formSource, new RegExp(`<BaseInput id="${id}"[^>]*readonly`));
  }
  assert.doesNotMatch(formSource, /\/customers\/getCustomer|\/warehouses\/getWarehouse|\/products\/getProduct|\/users\/getUser/);
  const load = new Function('query', 'setOptions', 'props', functionBody(formSource, 'loadUsers'));
  let options;
  load('MANUEL', (value) => { options = value; }, { ownerOptions: [{ value: 1, label: 'Venerável Manuel' }, { value: 2, label: 'Maria' }] });
  assert.deepEqual(options, [{ value: 1, label: 'Venerável Manuel' }]);
  assert.match(formSource, /Consultar entrada de amostra/);
});

test('nested selector errors remain visible in the flat correction form', () => {
  const error = new Function('field', 'form', functionBody(formSource, 'selectionError'));
  assert.equal(error('collaborations', { errors: { 'collaborations.0.collaboration_id': 'Invalid collaborator' } }), 'Invalid collaborator');
  assert.equal(error('collectionreasons', { errors: { collectionreasons: 'Invalid reasons' } }), 'Invalid reasons');
});

test('both correction modes render every traceability field from the single-record form', async () => {
  const { descriptor } = parse(formSource);
  const template = compileTemplate({
    id: 'collection-runtime', source: descriptor.template.content,
    compilerOptions: { mode: 'function' },
  });
  assert.deepEqual(template.errors, []);
  const render = new Function('Vue', template.code)(Vue);
  const stub = { setup: (_, { attrs, slots }) => () => Vue.h('span', attrs, slots.default?.()) };
  const input = {
    props: ['modelValue'],
    setup: (props, { attrs }) => () => Vue.h('input', { ...attrs, value: props.modelValue }),
  };
  const defaults = new Function(functionBody(formSource, 'defaultAccessionFields'))();

  for (const kind of ['direct', 'programmed']) {
    const form = {
      ...defaults, errors: {}, isDirty: false, processing: false,
      lot: 'DEMO-LOT', bl: 'DEMO-BL', du_no: 'DEMO-DU', term_no: 'DEMO-TERM', container_no: 'DEMO-CONTAINER',
      customer_id: { label: 'Demo customer' }, warehouse_id: { label: 'Demo site' },
      product_id: { label: 'Demo product' }, qty: 1,
    };
    const app = Vue.createSSRApp({
      setup: () => ({
        form, source: { code: 'DEMO-01', sample_entry_url: '/samples/1' }, isScheduled: kind === 'programmed',
        config: { title: 'Colheita', routePrefix: `${kind}collections`, kicker: 'Demo', icon: stub },
        selectedCustomer: 'Demo customer', totalRequestedQuantity: 1,
        route: (name) => `/${name}`, formatNumber: (value) => String(value),
        fieldError: () => undefined, selectionError: () => undefined, submit: () => {},
        loadCollectionCollaborations: () => {}, loadCollectionReasons: () => {}, loadEndResults: () => {},
        loadPackagingCategories: () => {}, loadVehicles: () => {}, loadTemperatures: () => {}, loadUsers: () => {},
      }),
      render,
    });
    for (const name of new Set([...descriptor.template.content.matchAll(/<([A-Z]\w*)\b/g)].map((match) => match[1]))) {
      app.component(name, name === 'BaseInput' ? input : stub);
    }
    app.config.warnHandler = (message) => { throw new Error(message); };
    const html = await renderToString(app);
    for (const [field, value] of Object.entries({ lot: 'DEMO-LOT', bl: 'DEMO-BL', du_no: 'DEMO-DU', term_no: 'DEMO-TERM', container_no: 'DEMO-CONTAINER' })) {
      assert.match(html, new RegExp(`<input[^>]*id="sample-${field}"[^>]*value="${value}"`), kind);
    }
    assert.match(html, /id="collection-customer"[^>]*readonly[^>]*value="Demo customer"/);
    assert.match(html, /Guardar alterações/);
  }
});

test('all changed collection Vue scripts and templates compile', () => {
  for (const filename of [
    'Pages/DirectCollections/Index.vue', 'Pages/DirectCollections/Edit.vue',
    'Pages/ProgrammedCollections/Index.vue', 'Pages/ProgrammedCollections/Edit.vue',
    'Components/collections/CollectionAccessionForm.vue', 'Components/records-table.vue',
    'Components/vap-table/table.vue',
  ]) {
    const { descriptor, errors } = parse(read(filename), { filename });
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: filename });
    const template = compileTemplate({
      id: filename, filename, source: descriptor.template.content,
      compilerOptions: { bindingMetadata: script.bindings },
    });
    assert.deepEqual(template.errors, [], filename);
  }
});
