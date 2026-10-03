import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';
import { useForm as useInertiaForm } from '@inertiajs/vue3';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');
const editor = read('Pages/Worksheets/Edit.vue');
const workflow = read('Pages/Analysis/ResultsWorkflow.vue');
const index = read('Pages/Worksheets/Index.vue');
const body = (source, name) => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}\\n`))[1];

test('worksheet saves are permission, dirty-state, and processing guarded, with successful defaults updated', () => {
  const save = new Function('canMutate', 'form', 'props', 'route', body(editor, 'saveWorksheet'));
  const calls = [];
  let defaults = 0;
  const form = { isDirty: true, put: (...args) => calls.push(args), defaults: () => defaults++ };
  const props = { worksheet: { id: 7 } };
  save({ value: false }, form, props, (name, id) => `${name}:${id}`);
  form.isDirty = false;
  save({ value: true }, form, props, (name, id) => `${name}:${id}`);
  assert.equal(calls.length, 0);
  form.isDirty = true;
  save({ value: true }, form, props, (name, id) => `${name}:${id}`);
  assert.equal(calls[0][0], 'worksheets.update:7');
  assert.equal(calls[0][1].preserveScroll, true);
  calls[0][1].onSuccess();
  assert.equal(defaults, 1);
  assert.doesNotMatch(body(editor, 'saveWorksheet'), /reset|clearErrors/);
});

test('every grid mutation is suppressed for readonly and pending workbooks', () => {
  for (const name of ['addSheet', 'removeActiveSheet', 'addRow', 'removeLastRow', 'addColumn', 'removeLastColumn']) {
    const state = { worksheets: { sheets: [{ id: 'original', name: 'Original', data: [['unchanged']] }] } };
    const before = structuredClone(state);
    const execute = new Function('canMutate', 'form', 'activeSheet', 'activeSheetIndex', 'columnCount', 'rowCount', body(editor, name));
    execute({ value: false }, state, { value: state.worksheets.sheets[0] }, { value: 0 }, { value: 1 }, { value: 1 });
    assert.deepEqual(state, before, name);
  }
});

test('new sheets receive distinct identifiers even within the same clock millisecond', () => {
  const add = new Function('canMutate', 'form', 'activeSheetIndex', body(editor, 'addSheet'));
  const form = { worksheets: { sheets: [] } };
  const index = { value: 0 };
  add({ value: true }, form, index);
  add({ value: true }, form, index);
  assert.notEqual(form.worksheets.sheets[0].id, form.worksheets.sheets[1].id);
  assert.equal(index.value, 1);
});

test('draft generation uses native form feedback and rejects repeat, unauthorized, and missing-record calls', () => {
  const create = new Function('props', 'worksheetForm', 'route', body(workflow, 'createWorksheetDraft'));
  const calls = [];
  const form = { processing: false, post(...args) { this.processing = true; calls.push(args); } };
  create({ allow_worksheet_draft: false, record: { id: 7 } }, form, (name) => name);
  create({ allow_worksheet_draft: true, record: null }, form, (name) => name);
  assert.equal(calls.length, 0);
  create({ allow_worksheet_draft: true, record: { id: 7 } }, form, (name, id) => `${name}:${id}`);
  create({ allow_worksheet_draft: true, record: { id: 7 } }, form, (name) => name);
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'analysis.worksheet-draft:7');
  assert.equal(calls[0][1].preserveScroll, true);
  assert.match(workflow, /:disabled="worksheetForm.processing"/);
  assert.match(workflow, /worksheetForm.errors.worksheet[^>]*class="ds-field-error" role="alert"/);
});

test('archive restore uses POST, frozen identifiers, permission and processing guards', () => {
  const restore = new Function('props', 'restoreForm', 'route', 'worksheet', body(index, 'restoreWorksheet'));
  const calls = [];
  let resets = 0;
  const form = { processing: false, recordIds: [], post(...args) { this.processing = true; calls.push(args); }, reset: () => resets++ };
  const archived = { id: 7, deleted_at: '2026-09-28' };
  const route = (name) => name;
  restore({ can_restore: false }, form, route, archived);
  restore({ can_restore: true }, form, route, { id: 7, deleted_at: null });
  restore({ can_restore: true }, form, route, { deleted_at: '2026-09-28' });
  assert.equal(calls.length, 0);
  restore({ can_restore: true }, form, route, archived);
  archived.id = 99;
  restore({ can_restore: true }, form, route, archived);
  assert.deepEqual(form.recordIds, [7]);
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'worksheets.restore');
  assert.equal(calls[0][1].preserveScroll, true);
  calls[0][1].onSuccess();
  assert.equal(resets, 1);
  assert.doesNotMatch(body(index, 'restoreWorksheet'), /clearErrors|\.get\(/);
});

function inputComponent() {
  const { descriptor } = parse(read('Components/base/BaseInput.vue'));
  const code = compileScript(descriptor, { id: 'worksheet-input', inlineTemplate: true }).content
    .replace(/import DateTimePicker[^\n]+\n/, '')
    .replace(/import \{([^}]+)\} from ['"]vue['"];?/g, (_, imports) => `const {${imports.replace(/\bas\b/g, ':')}} = Vue;`)
    .replace('export default', 'return');
  return new Function('Vue', 'DateTimePicker', code)(Vue, { render: () => null });
}

async function renderEditor({ editable = false, processing = false, errors = {}, empty = false } = {}) {
  const { descriptor } = parse(editor);
  const code = compileScript(descriptor, { id: 'worksheet-editor', inlineTemplate: true }).content
    .replace(/import Layout[^\n]+\n/, '')
    .replace(/import \{ Link, useForm \} from [^;]+;/, '')
    .replace(/import \{([^}]+)\} from "@heroicons\/vue\/24\/outline";/, (_, icons) => `const {${icons}} = iconStubs;`)
    .replace(/import \{([^}]+)\} from ['"]vue['"];?/g, (_, imports) => `const {${imports.replace(/\bas\b/g, ':')}} = Vue;`)
    .replace('export default', 'return');
  const iconStubs = new Proxy({}, { get: () => ({ render: () => null }) });
  let form;
  const useForm = (data) => {
    form = useInertiaForm(data);
    form.processing = processing;
    form.isDirty = true;
    form.setError(errors);
    return form;
  };
  const Link = { props: ['href'], setup: (props, { slots }) => () => Vue.h('a', { href: props.href }, slots.default?.()) };
  const component = new Function('Vue', 'Layout', 'Link', 'useForm', 'iconStubs', 'route', code)(
    Vue, {}, Link, useForm, iconStubs, (name) => name,
  );
  const worksheet = {
      id: 7, name: 'Folha de bancada', updated_at: null,
      worksheets: { analysis_id: 9, scope_control: { expected_count: 3, status_label: 'Pendente' },
        sheets: empty ? [] : [{ id: 'original', name: 'Bancada', data: [['original', 0]] }] },
    };
  const app = Vue.createSSRApp(component, { can_edit: editable, worksheet });
  app.component('BaseInput', inputComponent());
  app.config.globalProperties.route = (name) => name;
  app.component('DataTable', { setup: (_, { slots }) => () => Vue.h('table', slots.default?.()) });
  return { html: await renderToString(app), form, worksheet };
}

test('real compiled editor renders readonly, pending, validation, and empty-grid states', async () => {
  const readonly = await renderEditor();
  assert.match(readonly.html, /Só leitura/);
  assert.ok((readonly.html.match(/ disabled/g) || []).length >= 9);
  const pending = await renderEditor({ editable: true, processing: true });
  assert.match(pending.html, /aria-busy="true"/);
  assert.match(pending.html, /A guardar/);
  assert.ok((pending.html.match(/ disabled/g) || []).length >= 9);
  const invalid = await renderEditor({ editable: true, errors: { name: 'Nome demasiado longo' } });
  assert.match(invalid.html, /role="alert"/);
  assert.match(invalid.html, /Nome demasiado longo/);
  assert.match(invalid.html, /aria-describedby="worksheet-name-error"/);
  assert.match(invalid.html, /aria-invalid="true"/);
  const empty = await renderEditor({ editable: true, empty: true });
  assert.match(empty.html, /Sheet 1/);
  assert.equal(empty.form.worksheets.sheets.length, 1);
});

test('editor sends only editable cells and names, not identity or scientific scope metadata', async () => {
  const { form } = await renderEditor({ editable: true });
  assert.deepEqual(Object.keys(form.worksheets), ['sheets']);
  assert.match(editor, /const scopeControl = computed\(\(\) => props.worksheet.worksheets\?\.scope_control/);
  assert.doesNotMatch(editor, /\.\.\.\(props.worksheet.worksheets/);
});

test('native Inertia form keeps unsaved workbook cells separate from persisted worksheet props', async () => {
  const { form, worksheet } = await renderEditor({ editable: true });
  const before = structuredClone(worksheet);
  form.worksheets.sheets[0].data[0][0] = 'Unsaved replacement';
  form.worksheets.sheets.push({ id: 'new', name: 'Unsaved sheet', data: [['new cell']] });
  assert.deepEqual(worksheet, before);
  form.reset();
  assert.equal(form.worksheets.sheets[0].data[0][0], 'original');
  assert.equal(form.worksheets.sheets.length, 1);
});

async function renderIndex({ canRestore = true, processing = false, errors = {}, empty = false } = {}) {
  const { descriptor } = parse(index);
  const code = compileScript(descriptor, { id: 'worksheet-index', inlineTemplate: true }).content
    .replace(/import Layout[^\n]+\n/, '')
    .replace(/import \{ Link, useForm \} from [^;]+;/, '')
    .replace(/import \{([^}]+)\} from "@heroicons\/vue\/24\/outline";/, (_, icons) => `const {${icons}} = iconStubs;`)
    .replace(/import \{([^}]+)\} from ['"]vue['"];?/g, (_, imports) => `const {${imports.replace(/\bas\b/g, ':')}} = Vue;`)
    .replace('export default', 'return');
  const iconStubs = new Proxy({}, { get: () => ({ render: () => null }) });
  const route = (name, params) => params?.trashed ? `${name}?trashed=${params.trashed}` : name;
  const useForm = (data) => {
    const form = useInertiaForm(data);
    form.processing = processing;
    form.recordIds = [7];
    form.setError(errors);
    return form;
  };
  const Link = { props: ['href'], setup: (props, { slots }) => () => Vue.h('a', { href: props.href }, slots.default?.()) };
  const component = new Function('Vue', 'Layout', 'Link', 'useForm', 'iconStubs', 'route', code)(Vue, {}, Link, useForm, iconStubs, route);
  const app = Vue.createSSRApp(component, {
    trashed: 'only', can_restore: canRestore,
    worksheets: empty ? [] : [{ id: 7, name: 'Folha arquivada', deleted_at: '2026-09-28', worksheets: { sheets: [] } }],
  });
  app.component('BaseInput', inputComponent());
  app.component('DataTable', { setup: (_, { slots }) => () => Vue.h('table', slots.default?.()) });
  app.config.globalProperties.route = route;
  return renderToString(app);
}

test('actual archive list renders recovery, readonly, busy, error and empty states', async () => {
  const idle = await renderIndex();
  assert.match(idle, /href="worksheets.index\?trashed=only"/);
  assert.match(idle, /aria-current="page"/);
  assert.match(idle, /Restaurar Folha arquivada/);
  assert.doesNotMatch(idle, /href="worksheets.show"/);
  const readonly = await renderIndex({ canRestore: false });
  assert.doesNotMatch(readonly, /Restaurar/);
  const pending = await renderIndex({ processing: true });
  assert.match(pending, /A restaurar/);
  assert.match(pending, /disabled/);
  assert.match(pending, /aria-busy="true"/);
  const rejected = await renderIndex({ errors: { recordIds: 'Seleccione uma folha válida' } });
  assert.match(rejected, /role="alert"/);
  assert.match(rejected, /Seleccione uma folha válida/);
  const empty = await renderIndex({ empty: true });
  assert.match(empty, /Não há folhas arquivadas/);
});

test('worksheet editor and related analysis workflow compile with the actual Vue compiler', () => {
  for (const [id, source] of [['worksheet', editor], ['worksheet-index', index], ['analysis-workflow', workflow]]) {
    const { descriptor, errors } = parse(source);
    assert.deepEqual(errors, []);
    assert.doesNotThrow(() => compileScript(descriptor, { id }));
    assert.deepEqual(compileTemplate({ id, source: descriptor.template.content }).errors, []);
  }
});
