import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';

const source = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Create.vue', import.meta.url), 'utf8');

test('composer prevents overlapping and invalid sends and preserves drafts on transport failure', () => {
  const body = source.match(/const submit = \(\) => \{([\s\S]*?)\n\}/)[1];
  const showValidation = { value: false };
  const isFormValid = { value: false };
  const calls = [];
  const form = {
    processing: false, title: 'Retained title', message: 'Retained message', recipients: [7], errors: {},
    clearErrors() { this.errors = {}; },
    setError(key, value) { this.errors[key] = value; },
    post(...args) { this.processing = true; calls.push(args); },
  };
  const submit = new Function('form', 'showValidation', 'isFormValid', 'route', body);
  const execute = () => submit(form, showValidation, isFormValid, (name) => name);
  execute();
  assert.equal(showValidation.value, true);
  assert.equal(calls.length, 0);
  isFormValid.value = true;
  execute(); execute();
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'admin.notifications.store');
  assert.equal(calls[0][1].preserveScroll, true);
  for (const callback of ['onNetworkError', 'onHttpException', 'onCancel']) {
    const result = calls[0][1][callback]();
    if (callback !== 'onCancel') assert.equal(result, false);
    assert.match(form.errors.request, /rascunho.*preservado/);
    assert.match(form.errors.request, /histórico/);
    assert.equal(form.title, 'Retained title');
    assert.equal(form.message, 'Retained message');
    assert.deepEqual(form.recipients, [7]);
  }
  form.processing = false;
  execute();
  assert.equal(calls.length, 2);
  assert.deepEqual(form.errors, {});
});

test('all audience validation errors are visible, including indexed recipients and group errors', () => {
  const expression = source.slice(source.indexOf('const audienceErrors ='), source.indexOf('\n\nconst applyTemplate'))
    .replace('const audienceErrors =', 'return');
  const errors = new Function('computed', 'form', expression)((callback) => callback(), { errors: {
    title: 'Unrelated', group: 'Invalid group', 'recipients.0': 'Invalid recipient',
    'recipients.1': 'Invalid recipient', recipients: 'No recipients', recipient_type: 'Invalid audience',
  } });
  assert.deepEqual(errors, ['Invalid group', 'Invalid recipient', 'No recipients', 'Invalid audience']);
  assert.match(source, /v-if="audienceErrors.length" role="alert"/);
});

test('empty groups and empty all-member audiences cannot submit', () => {
  const expression = source.slice(source.indexOf('const isFormValid ='), source.indexOf('\nconst audienceErrors ='))
    .replace('const isFormValid =', 'return');
  const evaluate = new Function('computed', 'form', 'estimatedRecipients', expression);
  for (const recipient_type of ['all', 'group', 'specific']) {
    const form = { title: 'Title', message: 'Message', recipient_type };
    assert.equal(evaluate((callback) => callback(), form, { value: 0 }), false);
    assert.equal(evaluate((callback) => callback(), form, { value: 2 }), true);
    form.message = '  ';
    assert.equal(evaluate((callback) => callback(), form, { value: 2 }), false);
  }
});

test('composer compiles with disabled pending controls, reduced motion and honest queue wording', () => {
  const { descriptor, errors } = parse(source);
  assert.deepEqual(errors, []);
  const script = compileScript(descriptor, { id: 'notification-composer' });
  const template = compileTemplate({ id: 'notification-composer', source: descriptor.template.content,
    compilerOptions: { bindingMetadata: script.bindings } });
  assert.deepEqual(template.errors, []);
  assert.match(source, /<fieldset[^>]*:disabled="form.processing"/);
  assert.match(source, /:aria-busy="form.processing"/);
  assert.match(source, /motion-reduce:animate-none/);
  assert.match(source, /Alcance estimado/);
  assert.match(source, /<Head title="Compor notificação"/);
  assert.match(source, /Em segundo plano/);
  assert.doesNotMatch(source, /> Imediata</);
});
