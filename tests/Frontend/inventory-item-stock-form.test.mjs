import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import { nextTick, reactive, watch } from 'vue';

const edit = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Edit.vue', import.meta.url), 'utf8');
const create = readFileSync(new URL('../../resources/js/Pages/VAPInventory/Items/Create.vue', import.meta.url), 'utf8');
const surface = readFileSync(new URL('../../resources/js/Components/vap-inventory/InventoryItemFormSurface.vue', import.meta.url), 'utf8');
const stockRegister = readFileSync(new URL('../../resources/js/Pages/Inventory/Index.vue', import.meta.url), 'utf8');
const stockDetail = readFileSync(new URL('../../resources/js/Pages/Inventory/Show.vue', import.meta.url), 'utf8');
const reagentModal = readFileSync(new URL('../../resources/js/Components/vap-inventory/ConsumeReagentModal.vue', import.meta.url), 'utf8');

test('canonical entry context opens once and an unchanged save response cannot reopen the editor', async () => {
  const body = stockRegister.match(/function applyEditorContext\(\) \{([\s\S]*?)\n\}/)[1];
  const watcher = stockRegister.match(/watch\(\[\(\) => props\.openCreate, \(\) => props\.initialRecord\?\.data\?\.id\], applyEditorContext, \{ immediate: true \}\);/)[0];
  const props = reactive({ openCreate: false, initialRecord: { data: { id: 9, item: 'Issued item' } } });
  const calls = [];
  const apply = new Function('props', 'openEdit', 'openCreate', body);
  const stop = new Function('watch', 'props', 'applyEditorContext', `return ${watcher}`)(watch, props,
    () => apply(props, data => calls.push(['edit', data.id]), () => calls.push(['create'])));
  try {
    assert.deepEqual(calls, [['edit', 9]]);
    props.initialRecord = { data: { id: 9, item: 'Saved metadata' } };
    await nextTick();
    assert.equal(calls.length, 1);
    props.initialRecord = { data: { id: 10 } };
    await nextTick();
    assert.deepEqual(calls.at(-1), ['edit', 10]);
    props.initialRecord = null;
    props.openCreate = true;
    await nextTick();
    assert.deepEqual(calls.at(-1), ['create']);
    props.initialRecord = null;
    await nextTick();
    assert.equal(calls.length, 3);
  } finally { stop(); }
});

test('stock editing presents issued identity read-only and blocks stale picker changes', () => {
  assert.match(stockRegister, /id="stock-position-item"[^>]*readonly[^>]*aria-describedby="stock-position-identity-help"/);
  assert.match(stockRegister, /id="stock-position-warehouse"[^>]*readonly[^>]*aria-describedby="stock-position-identity-help"/);
  assert.match(stockRegister, /id="stock-position-identity-help"/);
  const form = { id: 9, item_id: { value: 1 }, min_stock_level: 3 };
  const body = stockRegister.match(/function selectItem\(option\) \{([\s\S]*?)\n\}/)[1];
  new Function('form', 'option', body)(form, { value: 99, inventory_type: 'equipment' });
  assert.deepEqual(form.item_id, { value: 1 });
  assert.equal(form.min_stock_level, 3);
  assert.match(stockRegister, /<RecordsTable\s+v-if="canView"/);
  assert.match(stockRegister, /v-if="!form.id && !canSelectPosition"[^>]*role="status"/);
});

test('pending saves preserve stock draft and failed saves expose recoverable feedback', () => {
  const editorOpen = { value: true };
  const errors = {};
  const form = { id: 9, processing: true, name: 'Unsaved thresholds', clearErrors() {}, setError(key, message) { errors[key] = message; } };
  const closeBody = stockRegister.match(/function closeEditor\(\) \{([\s\S]*?)\n\}/)[1];
  new Function('form', 'editorOpen', closeBody)(form, editorOpen);
  assert.equal(editorOpen.value, true);
  let request;
  form.transform = () => form;
  form.put = (url, options) => { request = options; };
  const submitBody = stockRegister.match(/function submit\(\) \{([\s\S]*?)\n\}/)[1];
  const submit = new Function('form', 'route', 'closeEditor', 'canSelectPosition', 'editorOpen', submitBody);
  submit(form, name => name, () => {}, { value: true }, editorOpen);
  assert.equal(request, undefined);
  form.processing = false;
  submit(form, name => name, () => {}, { value: true }, editorOpen);
  assert.equal(request.onHttpException(), false);
  assert.match(errors.request, /permissões/);
  assert.equal(request.onNetworkError(), false);
  assert.match(errors.request, /não foi confirmada/);
  assert.equal(form.name, 'Unsaved thresholds');
  assert.equal(editorOpen.value, true);
  form.processing = true;
  request.onSuccess();
  assert.equal(editorOpen.value, false, 'Successful save closes even before Inertia clears processing in onFinish.');
  assert.match(stockRegister, /:disabled="form.processing"[^>]*@close="closeEditor"/);
  assert.match(stockRegister, /v-if="form.errors.request"[^>]*role="alert"/);
});

test('stock thresholds depend on explicit type, never the incidental category ID', () => {
  const body = stockRegister.match(/function selectItem\(option\) \{([\s\S]*?)\n\}/)[1];
  const select = new Function('form', 'option', body);
  const form = { min_stock_level: 3, reorder_point: 5 };
  select(form, { value: 9, label: 'Material', category_id: 1, inventory_type: 'material' });
  assert.equal(form.min_stock_level, 3);
  assert.equal(form.reorder_point, 5);
  select(form, { value: 10, label: 'Equipment', category_id: 99, inventory_type: 'equipment' });
  assert.equal(form.min_stock_level, 0);
  assert.equal(form.reorder_point, 0);
  const expression = stockRegister.match(/const tracksThresholds = computed\(\(\) => (.*)\);/)[1];
  const tracks = new Function('form', `return ${expression}`);
  assert.equal(tracks(form), false);
  select(form, { value: 9, category_id: 1, inventory_type: 'material' });
  assert.equal(tracks(form), true);
  select(form, null);
  assert.equal(tracks(form), false);
});

test('opening stock editors retains classification and capabilities without leaking an old edit', () => {
  const form = { defaults(data) { this.initial = data; }, reset() { Object.assign(this, this.initial); }, clearErrors() {} };
  const editorOpen = { value: false };
  const editBody = stockRegister.match(/function openEdit\(data\) \{([\s\S]*?)\n\}/)[1];
  const openEdit = new Function('form', 'editorOpen', 'data', editBody);
  openEdit(form, editorOpen, { id: 12, item_id: 10, category_id: 99, inventory_type: 'equipment', can_open_item: true });
  assert.equal(form.item_id.inventory_type, 'equipment');
  assert.equal(form.item_id.can_open_item, true);
  assert.equal(form.id, 12);
  const createBody = stockRegister.match(/function openCreate\(\) \{([\s\S]*?)\n\}/)[1];
  new Function('form', 'editorOpen', createBody)(form, editorOpen);
  assert.equal(form.id, null);
  assert.equal(form.item_id, null);
  assert.equal(editorOpen.value, true);
});

test('stock item links honor server capabilities for each row and the retained editor item', () => {
  assert.match(stockRegister, /v-if="data\.can_open_item"[\s\S]*?:href="route\('vap-inventory.items.show'/);
  assert.match(stockRegister, /<Link v-if="form\.item_id\?\.can_open_item" :href="route\('vap-inventory.items.show'/);
  assert.doesNotMatch(stockRegister, /Number\(form\.category_id\) [!=]== 1/);
  const { descriptor } = parse(stockRegister);
  const script = compileScript(descriptor, { id: 'stock-register' });
  assert.equal(script.bindings.ArrowUpIcon, 'setup-maybe-ref');
});

test('stock detail uses the same item capability as the register', () => {
  assert.match(stockDetail, /v-if="position\.can_open_item"[\s\S]*?:href="route\('vap-inventory.items.show'/);
  assert.doesNotMatch(stockDetail, /hasPermission\('view_iitems'\)/);
  const { descriptor, errors } = parse(stockDetail);
  assert.deepEqual(errors, []);
  const script = compileScript(descriptor, { id: 'stock-detail' });
  assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: 'Inventory/Show.vue', id: 'stock-detail', compilerOptions: { bindingMetadata: script.bindings } }).errors, []);
});

test('edit form renders stock read-only and directs adjustments to the item page', () => {
  for (const [name, source] of [['inventory-edit', edit], ['inventory-surface', surface]]) {
    const { descriptor } = parse(source);
    const compiled = compileTemplate({ id: name, source: descriptor.template.content, filename: `${name}.vue` });
    assert.deepEqual(compiled.errors, []);
  }

  assert.match(surface, /v-if="mode === 'create'"[^>]*@click="emit\('add-warehouse'\)"/);
  assert.match(surface, /v-if="mode === 'create'"[^>]*@click="emit\('remove-warehouse', index\)"/);
  assert.match(surface, /<dl v-else class="pl-facts">/);
  assert.match(surface, /<Link v-else :href="backHref" class="ds-button ds-button-secondary">Ver movimentos<\/Link>/);
  assert.doesNotMatch(edit, /@add-warehouse|@remove-warehouse|@update-warehouse-info/);
});

test('issued category and stock unit use read-only controls with persistent explanations', () => {
  assert.match(edit, /:identity-locks="identityLocks"/);
  for (const field of ['category', 'unit']) {
    assert.match(surface, new RegExp(`<input v-if="identityLocks\\.${field}"[^>]*readonly[^>]*aria-describedby="inventory-${field}-lock"`));
    assert.match(surface, new RegExp(`<p v-if="identityLocks\\.${field}" id="inventory-${field}-lock"`));
  }
  for (const [name, source] of [['inventory-edit', edit], ['inventory-surface', surface]]) {
    const { descriptor, errors } = parse(source);
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: name });
    const compiled = compileTemplate({ id: name, filename: `${name}.vue`, source: descriptor.template.content,
      compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(compiled.errors, []);
  }
});

test('edit selections initialize from server values before immediate watchers run', () => {
  const body = edit.match(/const selection = \(rows, id, label = row => row.name\) => \{([\s\S]*?)\n\}/)[1];
  const selection = new Function('rows', 'id', 'label', body);
  const label = row => row.name;
  assert.deepEqual(selection([{ id: 4, name: 'Retained archived category' }], '4', label), { value: 4, label: 'Retained archived category' });
  assert.equal(selection([], 4, label), null);
  for (const field of ['Category', 'Type', 'Status', 'Supplier', 'Unit', 'Department', 'EquipmentCategory', 'PackagingCategory']) {
    assert.match(edit, new RegExp(`const selected${field} = ref\\(selection\\(`));
  }
  assert.doesNotMatch(edit, /const selectedCategory = ref\(null\)/);
});

test('canonical create and edit forms preserve the complete catalogue metadata contract', () => {
  const fields = [
    ['department_id', 'Department', 'department', 'departments'],
    ['eq_cat_id', 'EquipmentCategory', 'equipment-category', 'equipmentCategories'],
    ['packaging_type_id', 'PackagingCategory', 'packaging-category', 'packagingCategories'],
  ];
  for (const [name, source] of [['inventory-create', create], ['inventory-edit', edit], ['inventory-surface', surface]]) {
    const { descriptor, errors } = parse(source);
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: name });
    const compiled = compileTemplate({ id: name, filename: `${name}.vue`, source: descriptor.template.content,
      compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(compiled.errors, []);
  }
  assert.match(create, /location: ''/);
  assert.match(edit, /location: props\.item\.location/);
  assert.match(surface, /id="inventory-item-location" v-model="form\.location"/);
  for (const [field, selection, model, prop] of fields) {
    assert.match(create, new RegExp(`${field}: null`));
    assert.match(edit, new RegExp(`${field}: props\\.item\\.${field}`));
    assert.match(edit, new RegExp(`selected${selection} = ref\\(selection\\(props\\.${prop}, props\\.item\\.${field}\\)\\)`));
    assert.match(surface, new RegExp(`v-model="selected${selection}" title-label="[^"]+"[^>]*errorFor\\('${field}'\\)`));
    for (const source of [create, edit]) {
      assert.match(source, new RegExp(`v-model:selected-${model}="selected${selection}"`));
      const callback = source.match(new RegExp(`watch\\(selected${selection}, \\(selection\\) => (form\\.${field} = selection\\?\\.value \\?\\? null)\\)`))[1];
      const form = {};
      const apply = new Function('form', 'selection', callback);
      apply(form, { value: 12, label: 'Retained choice' });
      assert.equal(form[field], 12);
      apply(form, null);
      assert.equal(form[field], null);
    }
  }
});

test('stock-register threshold edits cannot submit a balance change', () => {
  const { descriptor } = parse(stockRegister);
  const compiled = compileTemplate({ id: 'inventory-index', source: descriptor.template.content, filename: 'Inventory/Index.vue' });
  assert.deepEqual(compiled.errors, []);
  assert.match(stockRegister, /<div v-if="!form\.id">[\s\S]*?v-model="form\.qty_available"[^>]*step="0\.0001"/);
  assert.match(stockRegister, /<div v-else class="[^"]*">[\s\S]*?Ajustar existências<\/Link>/);

  const body = stockRegister.match(/function submit\(\) \{([\s\S]*?)\n\}/)[1];
  const calls = [];
  let transform;
  const form = {
    id: 13,
    clearErrors() {},
    transform(callback) { transform = callback; return this; },
    put: (...args) => calls.push(['put', ...args]),
    post: (...args) => calls.push(['post', ...args]),
  };
  const submit = new Function('form', 'route', 'closeEditor', 'canSelectPosition', 'editorOpen', body);
  submit(form, (name) => name, () => {}, { value: true }, { value: true });
  assert.equal(calls[0][0], 'put');
  assert.deepEqual(transform({ id: 13, qty_available: 999, min_stock_level: 3 }), { id: 13, min_stock_level: 3 });

  form.id = null;
  submit(form, (name) => name, () => {}, { value: true }, { value: true });
  assert.equal(calls[1][0], 'post');
  assert.deepEqual(transform({ qty_available: 5, min_stock_level: 3 }), { qty_available: 5, min_stock_level: 3 });
});

test('metadata submission excludes stock and spoofs PUT only for new document uploads', () => {
  const body = edit.match(/const submit = \(\) => \{([\s\S]*?)\n\}/)[1];
  class FileMock {}

  for (const hasUpload of [false, true]) {
    const uploads = hasUpload ? [new FileMock()] : [{ id: 17, name: 'Existing file' }];
    const calls = [];
    let transform;
    const form = {
      documents: uploads,
      clearErrors() {},
      setError() {},
      transform(callback) { transform = callback; return this; },
      post: (...args) => calls.push(['post', ...args]),
      put: (...args) => calls.push(['put', ...args]),
    };
    const submit = new Function('form', 'File', 'route', 'props', 'documentProcessing', body);
    submit(form, FileMock, (name, id) => `${name}/${id}`, { item: { id: 9 } }, { value: false });

    assert.equal(calls.length, 1);
    assert.equal(calls[0][0], hasUpload ? 'post' : 'put');
    assert.equal(calls[0][1], 'vap-inventory.items.update/9');
    assert.deepEqual(transform({ warehouses: [{ id: 2, qty_available: 900 }], name: 'Item', documents: uploads }), {
      name: 'Item', documents: hasUpload ? uploads : [],
      ...(hasUpload ? { _method: 'put' } : {}),
    });
  }
});

test('metadata edit blocks repeated submissions and keeps input with explicit failure feedback', () => {
  const body = edit.match(/const submit = \(\) => \{([\s\S]*?)\n\}/)[1];
  const errors = {};
  let request;
  const form = { processing: true, name: 'Unsaved correction', documents: [], clearErrors() {},
    setError(field, value) { errors[field] = value }, transform() {},
    put(url, options) { request = { url, options } } };
  const submit = new Function('form', 'File', 'route', 'props', 'documentProcessing', body);
  submit(form, class {}, name => name, { item: { id: 9 } }, { value: false });
  assert.equal(request, undefined);
  form.processing = false;
  submit(form, class {}, name => name, { item: { id: 9 } }, { value: false });
  assert.equal(request.options.onHttpException(), false);
  assert.match(errors.request, /permissões/);
  assert.equal(request.options.onNetworkError(), false);
  assert.match(errors.request, /não foi confirmada/);
  assert.equal(form.name, 'Unsaved correction');
  assert.match(surface, /v-if="errorFor\('request'\)"[^>]*role="alert"/);
});

test('editor document archive dispatches the issued identity without touching unsaved metadata', () => {
  const body = edit.match(/function deleteAttachment\(model_id, id\) \{([\s\S]*?)\n\}/)[1];
  const calls = [];
  const form = { processing: false, name: 'Unsaved correction', documents: [{ id: 17 }] };
  const remove = new Function('form', 'documentProcessing', 'props', 'submitDocumentArchive', 'model_id', 'id', body);
  remove(form, { value: false }, { item: { id: 9 } }, (...args) => calls.push(args), 9, 17);
  assert.deepEqual(calls, [['delete', [17]]]);
  assert.equal(form.name, 'Unsaved correction');
  assert.deepEqual(form.documents, [{ id: 17 }]);
  assert.match(edit, /destroyUrl: ids => route\('vap-inventory.items.attachments.delete', \{ model_id: props.item.id, id: ids\[0\] \}\)/);
});

test('document success reconciles saved identities and preserves newly selected uploads', () => {
  const body = edit.match(/onSuccess: \(\) => \{ ([^\n]+) \},/)[1];
  class File {}
  const upload = new File();
  const form = { documents: [{ id: 18 }, { id: 17 }, upload] };
  new Function('form', 'props', 'File', body)(form, { documents: [{ id: 18 }] }, File);
  assert.deepEqual(form.documents, [{ id: 18 }, upload]);
});

test('pending metadata or document writes cannot dispatch a second archive or change its identity', () => {
  const body = edit.match(/function deleteAttachment\(model_id, id\) \{([\s\S]*?)\n\}/)[1];
  const remove = new Function('form', 'documentProcessing', 'props', 'submitDocumentArchive', 'model_id', 'id', body);
  for (const [metadataPending, documentPending, wrongItem] of [[true, false, false], [false, true, false], [false, false, true]]) {
    const calls = [];
    const form = { processing: metadataPending, documents: [{ id: 17 }] };
    remove(form, { value: documentPending }, { item: { id: 9 } }, (...args) => calls.push(args), wrongItem ? 10 : 9, 17);
    assert.deepEqual(calls, []);
    assert.deepEqual(form.documents, [{ id: 17 }]);
  }
});

test('quick reagent consumption submits the selected event time through Inertia form data', () => {
  const body = reagentModal.match(/const submit = \(\) => \{([\s\S]*?)\n\}/)[1];
  let transform;
  let submitted;
  const form = {
    transform(callback) { transform = callback; return this; },
    post(...args) { submitted = args; },
  };
  const submit = new Function('isFormValid', 'form', 'route', 'props', 'emit', 'close', body);
  submit({ value: true }, form, (name, id) => `${name}/${id}`, { item: { id: 7 } }, () => {}, () => {});

  assert.equal(submitted[0], 'vap-inventory.reagents.consume/7');
  assert.equal(submitted.length, 2);
  assert.deepEqual(transform({ date: '2026-09-30', used_at: '10:15', quantity_used: '0.0001' }), {
    date: '2026-09-30', used_at: '2026-09-30T10:15:00', quantity_used: '0.0001',
  });
});
