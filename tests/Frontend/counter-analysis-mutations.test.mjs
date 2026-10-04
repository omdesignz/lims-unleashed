import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';

const page = readFileSync(new URL('../../resources/js/Pages/CounterAnalysis/Index.vue', import.meta.url), 'utf8');
const table = readFileSync(new URL('../../resources/js/Components/records-table.vue', import.meta.url), 'utf8');
const selector = readFileSync(new URL('../../resources/js/Components/select-action.vue', import.meta.url), 'utf8');

test('counter-analysis bulk mutations use POST and suppress repeat clicks until finish', () => {
  const body = page.match(/function executeBulkAction\(\) \{([\s\S]*?)\n\}\n/)[1];
  const calls = [];
  const router = { post: (...args) => calls.push(args) };
  const rows = { value: [{ id: 7, selected: true }, { id: 8, selected: false }] };
  const selectedAction = { value: 'delete' };
  const isSubmitting = { value: false };
  let confirmationsClosed = 0;
  const action = new Function('rows', 'selectedAction', 'isSubmitting', 'router', 'route', 'closeConfirmation', body);
  const execute = () => action(rows, selectedAction, isSubmitting, router, (name) => name, () => confirmationsClosed++);

  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'counteranalysis.delete');
  assert.deepEqual(calls[0][1], { recordIds: [7] });
  assert.equal(calls[0][2].preserveScroll, true);
  calls[0][2].onFinish();
  assert.equal(isSubmitting.value, false);
  assert.equal(confirmationsClosed, 1);
  selectedAction.value = 'restore';
  execute();
  assert.equal(calls[1][0], 'counteranalysis.restore');
});

test('counter-analysis row mutations explicitly opt into POST', () => {
  assert.match(page, /:action-methods="\{ delete: 'post', restore: 'post' \}"/);
  const body = table.match(/function processAction\(currentActionId\) \{([\s\S]*?)\n\}\n/)[1];
  const calls = [];
  const router = { visit: (...args) => calls.push(args) };
  const processing = { value: false };
  const state = [{ value: '/counteranalysis/destroy' }, { value: 7 }, { value: 'delete' }, { value: true }];
  const action = new Function('currentActionId', 'isProcessingAction', 'router', 'props', 'recordUrl', 'recordId', 'actionId', 'showDeleteConfirmation', body);
  const execute = (method = 'post') => action('delete', processing, router, { actionMethods: { delete: method } }, ...state);

  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][1].method, 'post');
  assert.deepEqual(calls[0][1].data, { recordIds: [7] });
  calls[0][1].onFinish();
  assert.equal(processing.value, false);
  calls[0][1].onSuccess();
  assert.equal(state[1].value, null);
});

test('legacy consumers retain their existing request method until separately migrated', () => {
  assert.match(table, /method: props\.actionMethods\[currentActionId\] \?\? "get"/);
});

test('pending actions expose disabled and busy states without changing layout or motion', () => {
  assert.match(page, /:action-processing="isSubmitting"/);
  // One stacked register serves every width, so each row action appears once.
  assert.equal(table.match(/:disabled="actionProcessing \|\| isProcessingAction"/g).length, 2);
  assert.match(table, /:processing="actionProcessing \|\| isProcessingAction"/);
  assert.match(selector, /:aria-busy="processing"/);
  // The selection bar offers each action as a button; all of them, and "clear", wait while pending.
  assert.equal(selector.match(/:disabled="processing"/g).length, 2);
});
