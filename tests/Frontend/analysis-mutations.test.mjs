import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');
const page = read('Pages/Analysis/Index.vue');
const table = read('Components/vap-table/table.vue');
const bulk = read('Components/vap-table/bulk-actions.vue');
const workflow = read('Pages/Analysis/ResultsWorkflow.vue');
const functionBody = (name) => page.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}\\n`))[1];

function harness() {
  const calls = [];
  const form = {
    recordIds: [], processing: false, errors: {},
    post(destination, options) {
      this.processing = true;
      calls.push({ destination, options, ids: [...this.recordIds] });
    },
  };
  const state = {
    pendingActionType: { value: 'bulk' }, pendingAction: { value: 'delete' },
    selectedIDs: { value: [7, 8] }, pendingRecord: { value: { id: 9 } }, closed: 0,
  };
  const action = new Function('archivalForm', 'pendingActionType', 'pendingAction', 'selectedIDs', 'pendingRecord', 'resetConfirmation', 'route', functionBody('confirmAction'));
  return {
    form, state, calls,
    execute: () => action(form, state.pendingActionType, state.pendingAction, state.selectedIDs, state.pendingRecord, () => state.closed++, (name) => name),
  };
}

test('analysis bulk archive uses POST, freezes selected IDs, and suppresses repeated submissions', () => {
  const { form, state, calls, execute } = harness();
  execute();
  execute();
  state.selectedIDs.value.push(10);
  assert.equal(calls.length, 1);
  assert.equal(calls[0].destination, 'analysis.destroy');
  assert.deepEqual(form.recordIds, [7, 8]);
  assert.equal(calls[0].options.preserveState, 'errors');
  assert.equal(calls[0].options.preserveScroll, true);
  calls[0].options.onFinish();
  assert.equal(state.closed, 1);
});

test('single-row restoration uses the named POST route with no IDs left in its query string', () => {
  const { state, calls, execute } = harness();
  state.pendingAction.value = 'restore';
  state.pendingActionType.value = 'single';
  execute();
  assert.equal(calls[0].destination, 'analysis.restore');
  assert.deepEqual(calls[0].ids, [9]);
  assert.doesNotMatch(functionBody('confirmAction'), /router\.get|pendingUrl/);
});

test('unknown actions and empty selections do not submit', () => {
  const { state, calls, execute } = harness();
  state.pendingAction.value = null;
  execute();
  state.pendingAction.value = 'erase';
  execute();
  state.pendingAction.value = 'delete';
  state.selectedIDs.value = [];
  execute();
  assert.equal(calls.length, 0);
  assert.equal(state.closed, 1);
});

test('confirmation is suppressed while pending and clears errors only for an intentional retry', () => {
  const action = new Function('actionName', 'actionType', 'row', 'archivalForm', 'pendingAction', 'pendingActionType', 'pendingRecord', 'showConfirmation', functionBody('requestConfirmation'));
  let cleared = 0;
  const form = { processing: true, clearErrors: () => cleared++ };
  const state = [{ value: null }, { value: null }, { value: null }, { value: false }];
  action('delete', 'single', { id: 9 }, form, ...state);
  assert.equal(state[3].value, false);
  assert.equal(cleared, 0);
  form.processing = false;
  action('erase', 'single', { id: 9 }, form, ...state);
  assert.equal(cleared, 0);
  action('restore', 'single', { id: 9 }, form, ...state);
  assert.equal(state[0].value, 'restore');
  assert.equal(state[3].value, true);
  assert.equal(cleared, 1);
});

test('validation feedback remains after finish and busy state uses the native form helper', () => {
  const { form, calls, execute } = harness();
  execute();
  form.errors = { recordIds: 'Seleccione análises deste laboratório.' };
  calls[0].options.onFinish();
  assert.deepEqual(form.errors, { recordIds: 'Seleccione análises deste laboratório.' });
  assert.match(page, /const archivalForm = useForm\(\{ recordIds: \[\] \}\)/);
  assert.match(page, /v-if="archivalForm.hasErrors" role="alert"/);
  assert.match(page, /v-for="\(message, field\) in archivalForm.errors"/);
  assert.match(page, /role="status"/);
  assert.match(page, /A actualizar o arquivo/);
  assert.equal(page.match(/:disabled="archivalForm.processing"/g).length, 2);
  assert.match(page, /:action-processing="archivalForm.processing"/);
  assert.match(table, /:processing="props.actionProcessing"/);
});

test('the shared bulk controls really render disabled and busy without affecting idle consumers', async () => {
  const { descriptor } = parse(bulk);
  const compiled = compileTemplate({ id: 'analysis-bulk-runtime', source: descriptor.template.content, compilerOptions: { mode: 'function' } });
  assert.deepEqual(compiled.errors, []);
  const render = new Function('Vue', compiled.code)(Vue);
  for (const processing of [false, true]) {
    const app = Vue.createSSRApp({ setup: () => ({ props: { processing, actions: [{ id: 'delete', label: 'Arquivar' }] }, bulkAction: () => {} }), render });
    app.config.globalProperties.$t = (text) => text;
    app.directive('motion', {});
    const html = await renderToString(app);
    assert.equal(/\sdisabled(?:[\s=>])/.test(html), processing);
    assert.match(html, new RegExp(`aria-busy="${processing}"`));
    assert.match(html, /Arquivar/);
  }
  const body = bulk.match(/const bulkAction = \(action\) => \{([\s\S]*?)\n  \};/)[1];
  const emits = [];
  const emitAction = new Function('action', 'props', 'emit', body);
  emitAction('delete', { processing: true }, (...args) => emits.push(args));
  assert.equal(emits.length, 0);
  emitAction('delete', { processing: false }, (...args) => emits.push(args));
  assert.deepEqual(emits, [['bulk-action', 'delete']]);
});

test('all changed Vue components compile and unused manual analysis forms are absent', () => {
  for (const [id, source] of [['analysis', page], ['workflow', workflow], ['table', table], ['bulk', bulk]]) {
    const { descriptor, errors } = parse(source);
    assert.deepEqual(errors, []);
    assert.doesNotThrow(() => compileScript(descriptor, { id }));
    assert.deepEqual(compileTemplate({ id, source: descriptor.template.content }).errors, []);
  }
  for (const path of ['Analysis/Create', 'Analysis/Edit', 'CounterAnalysis/Create', 'CounterAnalysis/Edit']) {
    assert.equal(existsSync(new URL(`../../resources/js/Pages/${path}.vue`, import.meta.url)), false);
  }
});

test('completed and unknown workflow states do not fetch an invalid result stage or expose an insertion fallback', async () => {
  const body = workflow.match(/async function loadResultParameters\(\) \{([\s\S]*?)\n\}\n/)[1];
  const AsyncFunction = Object.getPrototypeOf(async () => {}).constructor;
  const load = new AsyncFunction('CurrentComponent', 'props', 'resultsLoadError', 'workflowNotice', 'form', 'fetch', body);

  for (const action of ['completed', 'unknown']) {
    let requested = false;
    const error = { value: 'Old failed request' };
    const notice = { value: '' };
    const form = { results: ['Old parameters'] };
    await load({ value: null }, { action }, error, notice, form, () => { requested = true; });
    assert.equal(requested, false);
    assert.equal(error.value, '');
    assert.deepEqual(form.results, []);
    assert.ok(notice.value);
  }
  assert.match(workflow, /return workflowComponents\[props.action\] \|\| null/);
  assert.match(workflow, /<component\s+v-if="CurrentComponent"/);
});

test('completed workflow submission cannot dispatch a write even if triggered directly', () => {
  const body = workflow.match(/function submitResults\(\) \{([\s\S]*?)\n\}\n/)[1];
  const submit = new Function('form', 'CurrentComponent', body);
  let wrote = false;
  submit({ processing: false, transform: () => { wrote = true; } }, { value: null });
  assert.equal(wrote, false);
});
