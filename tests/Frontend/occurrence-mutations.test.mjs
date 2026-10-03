import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';

const read = (name) => readFileSync(new URL(`../../resources/js/Pages/Occurrences/${name}.vue`, import.meta.url), 'utf8');
const index = read('Index');
const importer = read('occurrences-import-form');
const body = (source, name) => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}\\n`))[1];

test('bulk occurrence archive and restore use POST and reject repeat submissions', () => {
  const calls = [];
  const pageRecords = { value: [{ id: 7, selected: true }, { id: 8, selected: false }] };
  const selectedAction = { value: 'delete' };
  const isSubmitting = { value: false };
  let closed = 0;
  const action = new Function('pageRecords', 'selectedAction', 'isSubmitting', 'router', 'route', 'closeActionConfirmation', body(index, 'executeBulkAction'));
  const execute = () => action(pageRecords, selectedAction, isSubmitting, { post: (...args) => calls.push(args) }, (name) => name, () => closed++);
  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'occurrences.destroy');
  assert.deepEqual(calls[0][1], { recordIds: [7] });
  calls[0][2].onFinish();
  assert.equal(isSubmitting.value, false);
  assert.equal(closed, 1);
  selectedAction.value = 'restore';
  execute();
  assert.equal(calls[1][0], 'occurrences.restore');
  calls[1][2].onFinish();
  pageRecords.value[0].selected = false;
  execute();
  assert.equal(calls.length, 2);
  assert.match(index, /:action-methods="\{ delete: 'post', restore: 'post' \}"/);
  assert.match(index, /:action-processing="isSubmitting"/);
  assert.match(index, /role="status"/);
});

test('CSV import guards empty and busy submissions and clears only successful uploads', () => {
  const calls = [];
  const input = { value: { value: 'upload.csv' } };
  let reset = 0;
  const form = { processing: false, file: null, reset: () => reset++, post: (...args) => { calls.push(args); form.processing = true; } };
  const submit = new Function('form', 'fileInput', 'route', body(importer, 'submit'));
  const execute = () => submit(form, input, (name) => name);
  execute();
  assert.equal(calls.length, 0);
  form.file = { name: 'upload.csv' };
  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'occurrences.import.upload');
  assert.equal(calls[0][1].forceFormData, true);
  assert.equal(reset, 0);
  calls[0][1].onSuccess();
  assert.equal(reset, 1);
  assert.equal(input.value.value, '');
  assert.doesNotMatch(importer, /useForm\("OccurrenceImport"/);
});

test('occurrence edit preserves rejected drafts and sends the displayed record identity', () => {
  const calls = [];
  const form = { processing: false, isDirty: false, id: 999, put: (...args) => { calls.push(args); form.processing = true; } };
  const submit = new Function('form', 'canSubmit', 'occurrence', 'route', body(read('Edit'), 'submit'));
  const execute = () => submit(form, { value: true }, { id: 17 }, (name, params) => `${name}/${params.occurrence}`);
  execute();
  assert.equal(calls.length, 0);
  form.isDirty = true;
  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'occurrences.update/17');
  assert.equal(calls[0][1].preserveState, 'errors');
  assert.doesNotMatch(read('Create') + read('Edit'), /useForm\("Occurrence(Create|Edit)"/);
});

test('CSV upload renders a native file input with accessible errors and busy states', async () => {
  const { descriptor } = parse(importer);
  const compiled = compileTemplate({ id: 'occurrence-import-runtime', source: descriptor.template.content, compilerOptions: { mode: 'function' } });
  assert.deepEqual(compiled.errors, []);
  const render = new Function('Vue', compiled.code)(Vue);
  for (const processing of [false, true]) {
    const app = Vue.createSSRApp({
      setup: () => ({ form: { processing, file: { name: 'test.csv' }, errors: { file: 'Invalid row' } }, route: (name) => `/${name}`, submit: () => {}, onFileChange: () => {}, fileInput: null }),
      render,
    });
    for (const name of ['DocumentArrowUpIcon', 'ArrowUpTrayIcon']) app.component(name, { render: () => Vue.h('svg') });
    app.config.warnHandler = (message) => { throw new Error(message); };
    const html = await renderToString(app);
    assert.match(html, /<input[^>]*type="file"/);
    assert.match(html, /aria-invalid="true" aria-describedby="occurrence-import-error"/);
    assert.match(html, /id="occurrence-import-error" role="alert"/);
    assert.match(html, /href="\/occurrences.import.template"/);
    assert.match(html, /500 registos e 2 MB/);
    if (processing) assert.match(html, /<input[^>]*disabled/);
    else assert.doesNotMatch(html, /<input[^>]*disabled/);
  }
});

test('occurrence page scripts and templates compile', () => {
  for (const name of ['Index', 'occurrences-import-form', 'Create', 'Edit', 'OccurrenceForm', 'Show']) {
    const source = read(name);
    const { descriptor, errors } = parse(source, { filename: name });
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: name });
    const template = compileTemplate({ id: name, filename: name, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(template.errors, [], name);
  }
});
