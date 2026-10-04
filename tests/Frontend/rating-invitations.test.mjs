import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from '@vue/server-renderer';

const read = (path) => readFileSync(new URL(`../../resources/js/Pages/${path}.vue`, import.meta.url), 'utf8');
const formSource = read('RateForm');
const indexSource = read('Ratings/Index');
const body = (source, name) => source.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}`))[1];

test('rating submit uses server-issued route parameters and prevents repeat or empty submissions', () => {
  const calls = [];
  const props = { criteria: [{ id: 7 }], storeRoute: 'portal.rating.store', storeParameters: { invitation: 'issued-uuid' }, rateableId: 999 };
  const form = { processing: false, post: (...args) => { calls.push(args); form.processing = true; } };
  const submit = new Function('form', 'props', 'route', body(formSource, 'submit'));
  const execute = () => submit(form, props, (name, parameters) => ({ name, parameters }));
  execute();
  execute();
  assert.equal(calls.length, 1);
  assert.deepEqual(calls[0][0], { name: 'portal.rating.store', parameters: { invitation: 'issued-uuid' } });
  assert.equal(calls[0][1].preserveState, 'errors');
  form.processing = false;
  props.criteria = [];
  execute();
  assert.equal(calls.length, 1);
});

test('invitation issue and revocation are guarded POSTs with retained errors', () => {
  const calls = [];
  let reset = 0;
  const form = { processing: false, post: (...args) => { calls.push(args); form.processing = true; }, reset: () => reset++ };
  const issue = new Function('invitationForm', 'route', body(indexSource, 'issueInvitation'));
  issue(form, (name) => name);
  issue(form, (name) => name);
  assert.equal(calls.length, 1);
  assert.equal(calls[0][0], 'ratings.invitations.store');
  assert.equal(reset, 0);
  calls[0][1].onSuccess();
  assert.equal(reset, 1);
  assert.equal(calls[0][1].preserveState, 'errors');

  const revoke = new Function('revokeForm', 'window', 'route', 'invitation', body(indexSource, 'revokeInvitation'));
  form.processing = false;
  const route = (name, parameters) => ({ name, parameters });
  revoke(form, { confirm: () => false }, route, { invitation: 'local-uuid' });
  assert.equal(calls.length, 1);
  revoke(form, { confirm: () => true }, route, { invitation: 'local-uuid' });
  revoke(form, { confirm: () => true }, route, { invitation: 'local-uuid' });
  assert.equal(calls.length, 2);
  assert.deepEqual(calls[1][0], { name: 'ratings.invitations.revoke', parameters: { invitation: 'local-uuid' } });
});

test('rating form renders owner, score selection, associated errors, and locked busy controls', async () => {
  const { descriptor } = parse(formSource);
  const compiled = compileTemplate({ id: 'rating-runtime', source: descriptor.template.content, compilerOptions: { mode: 'function' } });
  assert.deepEqual(compiled.errors, []);
  const render = new Function('Vue', compiled.code)(Vue);
  for (const processing of [false, true]) {
    const app = Vue.createSSRApp({
      setup: () => ({
        commercialDocumentThemeClasses: [], laboratoryName: 'Owning lab', rateableLabel: 'Survey', ratingRequest: { status: 'pending' },
        criteria: [{ id: 7, name: 'Communication', description: 'Clear information' }],
        form: { processing, criteria: { 7: 5 }, review: '', errors: { 'criteria.7': 'Choose a score', review: 'Review too long' } },
        submit: () => {}, route: (name) => `/${name}`, returnRoute: 'portal.home', trans: (key) => key,
      }), render,
    });
    app.component('Link', { props: ['href'], render() { return Vue.h('a', { href: this.href }, this.$slots.default()); } });
    app.config.warnHandler = (message) => { throw new Error(message); };
    const html = await renderToString(app);
    assert.match(html, /Owning lab/);
    assert.match(html, /role="group" aria-labelledby="criterion-7"/);
    assert.equal((html.match(/aria-pressed="true"/g) || []).length, 1);
    assert.match(html, /role="alert"[^>]*>Choose a score/);
    assert.match(html, /aria-invalid="true" aria-describedby="rating-review-error"/);
    assert.match(html, /id="rating-review-error" role="alert"/);
    if (processing) {
      assert.equal((html.match(/<button[^>]*disabled/g) || []).length, 6);
      assert.match(html, /<textarea[^>]*disabled/);
    } else {
      assert.doesNotMatch(html, /<button[^>]*disabled/);
    }
  }
});

test('portal invitation list renders authenticated invitation links and a usable empty state', async () => {
  const { descriptor } = parse(read('ClientPortal/Ratings/Index'));
  const compiled = compileTemplate({ id: 'portal-rating-runtime', source: descriptor.template.content, compilerOptions: { mode: 'function' } });
  assert.deepEqual(compiled.errors, []);
  const render = new Function('Vue', compiled.code)(Vue);
  for (const data of [[], [{ invitation: 'issued-uuid', laboratory: 'VAP Lab', rateable_type: 'service', rateable_id: 0, expires_at: '2026-10-31T00:00:00Z' }]]) {
    const app = Vue.createSSRApp({ setup: () => ({ invitations: { data }, subjectLabel: () => 'Serviço geral', route: (name, params) => `/${name}/${params.invitation}` }), render });
    app.component('Link', { props: ['href'], render() { return Vue.h('a', { href: this.href }, this.$slots.default()); } });
    app.component('Pagination', { render: () => Vue.h('nav') });
    app.component('PageHeader', { props: ['title', 'lede'], render() { return Vue.h('header', [Vue.h('h1', this.title), Vue.h('p', this.lede), this.$slots.actions?.(), this.$slots.default?.()]); } });
    app.config.warnHandler = (message) => { throw new Error(message); };
    const html = await renderToString(app);
    if (data.length) {
      assert.match(html, /href="\/portal.rating.create\/issued-uuid"/);
      assert.match(html, /VAP Lab/);
    } else {
      assert.match(html, /Sem convites pendentes/);
      assert.doesNotMatch(html, /Responder/);
    }
  }
});

test('modified rating pages and portal navigation compile', () => {
  for (const name of ['RateForm', 'Ratings/Index', 'ClientPortal/Ratings/Index', 'ClientPortal/Dashboard']) {
    const { descriptor, errors } = parse(read(name), { filename: name });
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: name });
    const template = compileTemplate({ id: name, filename: name, source: descriptor.template.content, compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(template.errors, [], name);
  }
});
