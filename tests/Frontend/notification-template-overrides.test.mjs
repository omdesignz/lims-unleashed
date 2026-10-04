import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';

const source = readFileSync(new URL('../../resources/js/Pages/Admin/Notifications/Templates.vue', import.meta.url), 'utf8');
const body = (name) => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}`))[1];

test('save prevents duplicates, read-only and unchanged writes, and uses preset key rather than a global row id', () => {
  const calls = [];
  const selectedTemplate = { value: { key: 'quality.rating.requested', id: 999 } };
  const busy = { value: false };
  const props = { canEdit: true };
  const form = { isDirty: true, clearErrors() {}, put: (...args) => { calls.push(args); busy.value = true; } };
  const resetForm = { clearErrors() {} };
  let loads = 0;
  const save = new Function('selectedTemplate', 'busy', 'props', 'form', 'route', 'loadTemplate', 'resetForm', body('save'));
  const execute = () => save(selectedTemplate, busy, props, form, (name, parameters) => ({ name, parameters }), () => loads++, resetForm);
  execute(); execute();
  assert.equal(calls.length, 1);
  assert.deepEqual(calls[0][0], { name: 'admin.notification-templates.update', parameters: { key: 'quality.rating.requested' } });
  assert.equal(calls[0][1].preserveState, true);
  assert.equal(loads, 0);
  calls[0][1].onSuccess();
  assert.equal(loads, 1);
  busy.value = false;
  props.canEdit = false;
  execute();
  props.canEdit = true;
  form.isDirty = false;
  execute();
  assert.equal(calls.length, 1);
});

test('restore requires a saved local override, confirmation, and an idle mutation', () => {
  const calls = [];
  const busy = { value: false };
  const selectedTemplate = { value: { key: 'quality.rating.requested', is_overridden: true } };
  const props = { canEdit: true };
  const resetForm = { clearErrors() {}, delete: (...args) => { calls.push(args); busy.value = true; } };
  const form = { clearErrors() {} };
  let loads = 0;
  const restore = new Function('selectedTemplate', 'busy', 'props', 'window', 'resetForm', 'route', 'loadTemplate', 'form', body('restorePreset'));
  const execute = (confirm) => restore(selectedTemplate, busy, props, { confirm: () => confirm }, resetForm, (name, parameters) => ({ name, parameters }), () => loads++, form);
  execute(false);
  assert.equal(calls.length, 0);
  execute(true); execute(true);
  assert.equal(calls.length, 1);
  assert.deepEqual(calls[0][0], { name: 'admin.notification-templates.destroy', parameters: { key: 'quality.rating.requested' } });
  assert.equal(calls[0][1].preserveState, true);
  assert.equal(loads, 0);
  calls[0][1].onSuccess();
  assert.equal(loads, 1);
  busy.value = false;
  selectedTemplate.value.is_overridden = false;
  execute(true);
  props.canEdit = false;
  selectedTemplate.value.is_overridden = true;
  execute(true);
  assert.equal(calls.length, 1);
});

test('changing selection never discards a draft without confirmation and never interrupts a save', () => {
  const busy = { value: false };
  const selectedKey = { value: 'old.key' };
  const form = { isDirty: true };
  const select = new Function('busy', 'selectedKey', 'form', 'window', 'template', body('selectTemplate'));
  const execute = (confirm) => select(busy, selectedKey, form, { confirm: () => confirm }, { key: 'new.key' });
  execute(false);
  assert.equal(selectedKey.value, 'old.key');
  busy.value = true;
  execute(true);
  assert.equal(selectedKey.value, 'old.key');
  busy.value = false;
  execute(true);
  assert.equal(selectedKey.value, 'new.key');
  assert.doesNotMatch(source, /watch\(filteredTemplates/);
  assert.doesNotMatch(source, /useForm\(['"]/);
});

test('save and restore retain drafts on transport failure or cancellation and allow an explicit retry', () => {
  for (const operation of ['save', 'restorePreset']) {
    const calls = [];
    const busy = { value: false };
    const makeForm = () => ({
      title_template: 'Retained draft', channels: ['database'], isDirty: true, errors: {},
      clearErrors() { this.errors = {}; },
      setError(key, value) { this.errors[key] = value; },
      put(...args) { calls.push(args); busy.value = true; },
      delete(...args) { calls.push(args); busy.value = true; },
    });
    const form = makeForm();
    const resetForm = makeForm();
    let loads = 0;
    const execute = new Function('selectedTemplate', 'busy', 'props', 'window', 'form', 'resetForm', 'route', 'loadTemplate', body(operation));
    const run = () => execute({ value: { key: 'commercial.invoice.paid', is_overridden: true } }, busy, { canEdit: true }, { confirm: () => true }, form, resetForm, (name) => name, () => loads++);
    run();
    for (const callback of ['onNetworkError', 'onHttpException', 'onCancel']) {
      const result = calls.at(-1)[1][callback]();
      if (callback !== 'onCancel') assert.equal(result, false);
      const errors = operation === 'save' ? form.errors : resetForm.errors;
      assert.match(errors.request, /rascunho foi preservado/);
      assert.equal(form.title_template, 'Retained draft');
      assert.deepEqual(form.channels, ['database']);
      assert.equal(loads, 0);
      busy.value = false;
      run();
      assert.deepEqual(form.errors, {});
      assert.deepEqual(resetForm.errors, {});
    }
    assert.equal(calls.length, 4);
    calls.at(-1)[1].onSuccess();
    assert.equal(loads, 1);
  }
});

test('rejected response prop refresh does not overwrite drafts; changing owner or preset loads new defaults', async () => {
  const props = Vue.reactive({ laboratory: { id: 1 } });
  const selectedKey = Vue.ref('first.key');
  const selectedTemplate = Vue.ref({ title: 'saved copy' });
  const loadTemplate = (template) => loads.push(template.title);
  const loads = [];
  const watchSource = source.match(/^watch\(\[.*$/m)[0];
  const stop = new Function('watch', 'props', 'selectedKey', 'selectedTemplate', 'loadTemplate', `return ${watchSource}`)(Vue.watch, props, selectedKey, selectedTemplate, loadTemplate);
  assert.deepEqual(loads, ['saved copy']);
  props.laboratory = { id: 1 };
  selectedTemplate.value = { title: 'unchanged copy from rejected response' };
  await Vue.nextTick();
  assert.deepEqual(loads, ['saved copy']);
  selectedKey.value = 'second.key';
  await Vue.nextTick();
  assert.equal(loads.length, 2);
  props.laboratory = { id: 2 };
  await Vue.nextTick();
  assert.equal(loads.length, 3);
  stop();
});

test('editor renders ownership, inheritance, errors, busy and read-only states', async () => {
  const { descriptor } = parse(source);
  const compiled = compileTemplate({ id: 'template-runtime', source: descriptor.template.content, compilerOptions: { mode: 'function' } });
  assert.deepEqual(compiled.errors, []);
  const render = new Function('Vue', compiled.code)(Vue);
  for (const state of [
    { busy: false, canEdit: true, overridden: false },
    { busy: true, canEdit: true, overridden: true },
    { busy: false, canEdit: false, overridden: true },
  ]) {
    const selectedTemplate = { key: 'quality.rating.requested', name: 'Survey', category: 'quality', description: 'Invitation', enabled: true, is_overridden: state.overridden, variables: ['lab_name'] };
    const app = Vue.createSSRApp({
      setup: () => ({
        laboratory: { name: 'Owning Lab' }, search: '', category: '', categoryOptions: [], priorityOptions: [],
        selectedKey: selectedTemplate.key, selectedTemplate, filteredTemplates: [selectedTemplate], ...state,
        form: { enabled: true, channels: ['database'], errors: { title_template: 'Title required', channels: 'Channel required', request: 'Save failed; draft retained' }, processing: state.busy, isDirty: true },
        resetForm: { processing: false, errors: { request: 'Restore failed; draft retained' } },
        selectTemplate: () => {}, toggleChannel: () => {}, save: () => {}, restorePreset: () => {}, variableToken: (name) => `{{${name}}}`,
      }), render,
    });
    for (const name of ['BaseInput', 'BaseTextarea', 'BaseSelect', 'CheckboxInput']) {
      app.component(name, { props: ['error', 'label'], render() { return Vue.h('label', [this.label, this.error ? Vue.h('span', { role: 'alert' }, this.error) : null]); } });
    }
    app.component('NotificationAdminHeader', { props: ['description'], render() { return Vue.h('header', this.description); } });
    app.component('Head', { render: () => null });
    for (const name of ['ArrowPathIcon', 'CheckCircleIcon', 'EnvelopeIcon', 'MagnifyingGlassIcon', 'SignalIcon']) {
      app.component(name, { render: () => Vue.h('svg') });
    }
    app.config.warnHandler = (message) => { throw new Error(message); };
    const html = await renderToString(app);
    assert.match(html, /Owning Lab/);
    assert.match(html, /aria-pressed="true"/);
    assert.match(html, /Title required/);
    assert.match(html, /Channel required/);
    assert.match(html, /role="alert"[^>]*>Save failed; draft retained/);
    assert.match(html, /role="alert"[^>]*>Restore failed; draft retained/);
    assert.match(html, state.overridden ? /Personalização deste laboratório/ : /Modelo partilhado · sem personalização/);
    if (state.busy || !state.canEdit) assert.match(html, /<fieldset[^>]*disabled/);
    else assert.doesNotMatch(html, /<fieldset[^>]*disabled/);
    if (!state.canEdit) {
      assert.match(html, /Acesso de leitura/);
      assert.doesNotMatch(html, /Usar modelo partilhado/);
    }
    if (state.busy) assert.match(html, /A guardar…/);
  }
});

test('all administrative notification pages compile with meaningful document titles', () => {
  const titles = {
    Index: 'Registo de notificações', Dashboard: 'Visão geral de notificações',
    Analytics: 'Analítica de comunicação', Show: 'Detalhe da notificação',
    Templates: 'Modelos de comunicação', Create: 'Compor notificação',
  };
  for (const [page, title] of Object.entries(titles)) {
    const content = readFileSync(new URL(`../../resources/js/Pages/Admin/Notifications/${page}.vue`, import.meta.url), 'utf8');
    assert.ok(content.includes(`<Head title="${title}" />`));
    const { descriptor, errors } = parse(content);
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: `notification-${page}` });
    const result = compileTemplate({ id: `notification-${page}`, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(result.errors, []);
  }
});

test('horizontal notification navigation overrides sidebar widths and identifies the current page', async () => {
  const header = readFileSync(new URL('../../resources/js/Components/notifications/NotificationAdminHeader.vue', import.meta.url), 'utf8');
  const { descriptor } = parse(header);
  assert.match(header, /class="ds-settings-tab w-auto! min-w-0! items-center!"/);
  assert.doesNotMatch(header, /route\(\)\.current/);
  const compiled = compileTemplate({ id: 'notification-header', source: descriptor.template.content, compilerOptions: { mode: 'function' } });
  assert.deepEqual(compiled.errors, []);
  const render = new Function('Vue', compiled.code)(Vue);
  const navigation = ['dashboard', 'index', 'create', 'templates', 'analytics'].map((name) => ({ label: name, route: name, icon: 'span' }));
  const app = Vue.createSSRApp({
    setup: () => ({ title: 'Notifications', description: 'Lab', navigation, page: { url: '/templates?search=invoice' }, route: (name) => `/${name}` }), render,
  });
  app.component('Link', { props: ['href'], render() { return Vue.h('a', { href: this.href }, this.$slots.default()); } });
  app.component('BellAlertIcon', { render: () => Vue.h('svg') });
  const html = await renderToString(app);
  assert.equal((html.match(/aria-current="page"/g) ?? []).length, 1);
  assert.match(html, /href="\/templates"[^>]*aria-current="page"/);
  assert.equal((html.match(/class="ds-settings-tab w-auto!/g) ?? []).length, 5);
});

test('template editor compiles and exposes empty search results without switching the selected draft', () => {
  const { descriptor, errors } = parse(source);
  assert.deepEqual(errors, []);
  const script = compileScript(descriptor, { id: 'notification-template' });
  const compiled = compileTemplate({ id: 'notification-template', source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } });
  assert.deepEqual(compiled.errors, []);
  assert.match(source, /Nenhum modelo corresponde aos filtros/);
  assert.match(source, /transition-colors duration-150/);
  assert.doesNotMatch(source, /transition-all/);
  assert.match(source, /label="Destino definido pelo sistema" readonly/);
  assert.match(source, /id="template-priority"[^>]*:disabled="busy \|\| !canEdit"/);
  assert.match(source, /motion-reduce:animate-none/);
  assert.match(source, /class="flex min-w-0 flex-wrap gap-2"[\s\S]*Usar modelo partilhado[\s\S]*Guardar para este laboratório/);
});
