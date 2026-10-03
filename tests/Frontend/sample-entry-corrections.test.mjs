import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc';
import * as Vue from 'vue';
import { renderToString } from 'vue/server-renderer';
import { sampleEntryPayload, sampleTimestampInput } from '../../resources/js/Utils/sampleEntryForm.js';

const source = readFileSync(new URL('../../resources/js/Pages/VAPSamples/Index.vue', import.meta.url), 'utf8');

function watcher(target) {
    const start = source.indexOf(`watch(() => ${target},`);
    const end = source.indexOf('\n})', start);
    assert.ok(start >= 0 && end > start);
    return source.slice(start, end + 3);
}

function runWatcher(target, issued, form, extra = {}) {
    const callback = new Function('watch', 'form', 'isIssuedIntake', 'products', 'availableProfiles', 'acceptedProposals', 'applyAcceptedProposalLineage', watcher(target));
    callback((getter, handler) => handler(getter()), form, { value: issued }, extra.products || { value: [] },
        extra.availableProfiles || { value: [] }, extra.acceptedProposals || { value: [] }, extra.applyAcceptedProposalLineage || (() => {}));
}

test('catalogue watchers never rewrite issued matrix or profile identifiers', () => {
    const form = { department_id: 4, client_submitted_info: { product_id: 3, matrix_id: 7, requested_profile_ids: [11] } };
    const before = JSON.parse(JSON.stringify(form));
    runWatcher('form.client_submitted_info?.product_id', true, form, { products: { value: [{ id: 3, matrix_id: 99, profiles: [{ id: 15 }] }] } });
    runWatcher('form.department_id', true, form, { availableProfiles: { value: [{ id: 15 }] } });
    assert.deepEqual(form, before);
});

test('new intakes still adapt matrix and profile selections to the current catalogue', () => {
    const form = { department_id: 4, client_submitted_info: { product_id: 3, matrix_id: null, requested_profile_ids: [11, 15] } };
    runWatcher('form.client_submitted_info?.product_id', false, form, { products: { value: [{ id: 3, matrix_id: 99, profiles: [{ id: 15 }] }] } });
    assert.equal(form.client_submitted_info.matrix_id, 99);
    assert.deepEqual(form.client_submitted_info.requested_profile_ids, [15]);
    runWatcher('form.department_id', false, form);
    assert.deepEqual(form.client_submitted_info.requested_profile_ids, []);
});

test('issued origin and proposal watchers preserve canonical source identity', () => {
    const form = { proposal_id: 8, portal_request_id: 9, customer_request_id: 9, client_submitted_info: { request_origin: 'internal' } };
    const before = JSON.parse(JSON.stringify(form));
    let applied = false;
    runWatcher('form.client_submitted_info?.request_origin', true, form);
    runWatcher('form.proposal_id', true, form, { acceptedProposals: { value: [{ id: 8 }] }, applyAcceptedProposalLineage: () => { applied = true; } });
    assert.deepEqual(form, before);
    assert.equal(applied, false);
});

test('edit drafts clone nested sample metadata instead of changing the displayed persisted record', () => {
    const match = source.match(/const editSample = \(sample\) => \{([\s\S]*?)\n\}\n/);
    assert.ok(match);
    const original = { id: 3, name: 'Sample', client_submitted_info: { requested_profile_ids: [11], lot: 'ORIGINAL' } };
    const form = { name: '', client_submitted_info: {}, reset() {}, clearErrors() {}, data: () => ({ name: '', client_submitted_info: {} }) };
    const editingSample = { value: null };
    new Function('sample', 'editingSample', 'form', 'sampleTimestampInput', 'defaultClientSubmittedInfo', match[1])(
        original, editingSample, form, sampleTimestampInput, (data = {}) => ({ ...data }),
    );
    form.client_submitted_info.lot = 'UNSAVED';
    form.client_submitted_info.requested_profile_ids.push(15);
    assert.deepEqual(original.client_submitted_info, { requested_profile_ids: [11], lot: 'ORIGINAL' });
});

test('unchanged timestamp inputs preserve the issued offset and seconds exactly', () => {
    const original = { received_at: '2026-09-28T12:30:37+01:00', collected_at: '2026-09-27T10:15:19.000000Z' };
    const data = { received_at: sampleTimestampInput(original.received_at), collected_at: sampleTimestampInput(original.collected_at), obs: 'Correction' };
    assert.deepEqual(sampleEntryPayload(data, original), { ...original, obs: 'Correction' });
    assert.deepEqual(data, { received_at: sampleTimestampInput(original.received_at), collected_at: sampleTimestampInput(original.collected_at), obs: 'Correction' });
});

test('edited datetime values send an explicit offset while empty and invalid inputs remain safely validatable', () => {
    const value = '2026-09-24T09:45';
    const payload = sampleEntryPayload({ collected_at: value, received_at: '', analysis_start_date: 'invalid', name: 'Sample' });
    assert.equal(payload.collected_at, new Date(value).toISOString());
    assert.equal(payload.received_at, null);
    assert.equal(payload.analysis_start_date, 'invalid');
    assert.equal(payload.name, 'Sample');
    assert.equal(sampleTimestampInput(null), '');
    assert.equal(sampleTimestampInput('invalid'), 'invalid');
});

test('corrections omit the creation-only portal selector without discarding the issued source', () => {
    const data = { portal_request_id: '', customer_request_id: 9, obs: 'Correction' };
    assert.deepEqual(sampleEntryPayload(data, { id: 3, customer_request_id: 9 }), { customer_request_id: 9, obs: 'Correction' });
    assert.deepEqual(sampleEntryPayload({ portal_request_id: 9 }), { portal_request_id: 9 });
    assert.equal(data.portal_request_id, '');
});

test('numeric stored quantities serialize as text without changing the draft or issued scope', () => {
    const data = { client_submitted_info: { quantity: 1, collected_qty: 0, product_id: 3, lot: 'LOT' } };
    const payload = sampleEntryPayload(data);
    assert.deepEqual(payload.client_submitted_info, { quantity: '1', collected_qty: '0', product_id: 3, lot: 'LOT' });
    assert.equal(data.client_submitted_info.quantity, 1);
    assert.equal(data.client_submitted_info.collected_qty, 0);
    assert.equal(sampleEntryPayload({ client_submitted_info: { quantity: '2 kg' } }).client_submitted_info.quantity, '2 kg');
});

test('sample correction validation always has a visible error summary', () => {
    assert.match(source, /v-if="Object.keys\(form.errors\).length"[^>]*role="alert"/);
    assert.match(source, /v-for="\(message, field\) in form.errors"/);
    assert.match(source, /form.errors\[`client_submitted_info\.\$\{field.key\}`\]/);
    assert.match(source, /:error="form.errors\[`client_submitted_info\.\$\{field.key\}`\] \|\| ''"/);
    for (const field of ['name', 'code', 'sample_type', 'customer_id', 'lab_id', 'department_id', 'warehouse_id', 'received_at']) {
        assert.ok(source.includes(`:error="form.errors.${field} || ''"`), `${field} must use the shared control's actual error API.`);
    }
});

test('the actual shared input associates an invalid technical value with its visible error', async () => {
    const inputSource = readFileSync(new URL('../../resources/js/Components/base/BaseInput.vue', import.meta.url), 'utf8');
    const { descriptor } = parse(inputSource);
    const script = compileScript(descriptor, { id: 'sample-technical-input', inlineTemplate: true }).content
        .replace(/import DateTimePicker[^\n]+\n/, '')
        .replace(/import \{([^}]+)\} from ['"]vue['"];?/g, (_, imports) => `const {${imports.replace(/\bas\b/g, ':')}} = Vue;`)
        .replace('export default', 'return');
    const component = new Function('Vue', 'DateTimePicker', script)(Vue, { render: () => null });
    const html = await renderToString(Vue.createSSRApp(component, { id: 'technical-lot', modelValue: 'INVALID', error: 'Lote inválido' }));
    assert.match(html, /aria-invalid="true"/);
    assert.match(html, /aria-describedby="technical-lot-error"/);
    assert.match(html, /id="technical-lot-error"[^>]*role="alert"/);
    assert.match(html, /Lote inválido/);
});

test('issued identity controls are locked, metadata remains editable, and submission is busy guarded', () => {
    for (const id of ['sample-type', 'request-origin', 'collection-type', 'sample-customer', 'sample-product', 'sample-department', 'sample-warehouse', 'sample-profiles']) {
        assert.match(source, new RegExp(`<BaseSelect id="${id}"[^>]*:disabled="isIssuedIntake"`));
    }
    assert.match(source, /id="sample-code"[^>]*:readonly="Boolean\(editingSample.id\)"/);
    assert.match(source, /id="requested-services"[^>]*:readonly="isIssuedIntake"/);
    assert.doesNotMatch(source, /id="sample-observations"[^>]*(disabled|readonly)/);
    assert.match(source, /Identidade e âmbito emitidos estão fixos/);
    assert.match(source, /if \(form.processing \|\| !editingSample.value\?\.id\) return/);
    assert.match(source, /form\.transform\(\(data\) => sampleEntryPayload/);
});

test('locked scope preview uses stored profile and parameter snapshots', () => {
    assert.match(source, /if \(isIssuedIntake.value && form.client_submitted_info\?\.resolved_profiles\?\.length\) \{\s*return form.client_submitted_info.resolved_profiles/);
    assert.match(source, /if \(isIssuedIntake.value && Array.isArray\(form.client_submitted_info\?\.required_parameters\)\) \{\s*return form.client_submitted_info.required_parameters/);
});

test('sample intake script and template compile with the real Vue compiler', () => {
    const { descriptor, errors } = parse(source);
    assert.deepEqual(errors, []);
    assert.ok(compileScript(descriptor, { id: 'sample-intake-corrections' }).content);
    const template = compileTemplate({ id: 'sample-intake-corrections', source: descriptor.template.content });
    assert.deepEqual(template.errors, []);
});
