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
  const form = { isDirty: true, put: (...args) => { calls.push(args); busy.value = true; } };
  let loads = 0;
  const save = new Function('selectedTemplate', 'busy', 'props', 'form', 'route', 'loadTemplate', body('save'));
  const execute = () => save(selectedTemplate, busy, props, form, (name, parameters) => ({ name, parameters }), () => loads++);
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
  const resetForm = { delete: (...args) => { calls.push(args); busy.value = true; } };
  let loads = 0;
  const restore = new Function('selectedTemplate', 'busy', 'props', 'window', 'resetForm', 'route', 'loadTemplate', body('restorePreset'));
  const execute = (confirm) => restore(selectedTemplate, busy, props, { confirm: () => confirm }, resetForm, (name, parameters) => ({ name, parameters }), () => loads++);
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
        form: { enabled: true, channels: ['database'], errors: { title_template: 'Title required', channels: 'Channel required' }, processing: state.busy, isDirty: true },
        resetForm: { processing: false, errors: {} },
        selectTemplate: () => {}, toggleChannel: () => {}, save: () => {}, restorePreset: () => {}, variableToken: (name) => `{{${name}}}`,
      }), render,
    });
    for (const name of ['BaseInput', 'BaseTextarea', 'BaseSelect', 'CheckboxInput']) {
      app.component(name, { props: ['error', 'label'], render() { return Vue.h('label', [this.label, this.error ? Vue.h('span', { role: 'alert' }, this.error) : null]); } });
    }
    app.component('NotificationAdminHeader', { props: ['description'], render() { return Vue.h('header', this.description); } });
    for (const name of ['ArrowPathIcon', 'CheckCircleIcon', 'EnvelopeIcon', 'MagnifyingGlassIcon', 'SignalIcon']) {
      app.component(name, { render: () => Vue.h('svg') });
    }
    app.config.warnHandler = (message) => { throw new Error(message); };
    const html = await renderToString(app);
    assert.match(html, /Owning Lab/);
    assert.match(html, /aria-pressed="true"/);
    assert.match(html, /Title required/);
    assert.match(html, /Channel required/);
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
});
