import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc';
import { ref } from 'vue';

const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');
const body = (text, name) => text.match(new RegExp(`function ${name}\\([^)]*\\) \\{([\\s\\S]*?)\\n\\}`))[1];

const source = readFileSync(new URL('../../resources/js/Composables/useStaffAccountPayload.js', import.meta.url), 'utf8');
const { staffAccountPayload, submitStaffAccountMutation } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('activity cleanup refuses retained events and explains that bulk cleanup preserves canonical history', () => {
    const text = read('Pages/SystemActivity/Index.vue');
    const deleteMode = ref(null);
    const selectedActivity = ref(null);
    const showDeleteConfirmation = ref(false);
    const request = new Function('activity', 'deleteMode', 'selectedActivity', 'showDeleteConfirmation', body(text, 'requestDelete'));
    request({ id: 8, is_retained: true }, deleteMode, selectedActivity, showDeleteConfirmation);
    assert.equal(showDeleteConfirmation.value, false);
    assert.equal(selectedActivity.value, null);
    request({ id: 9, is_retained: false }, deleteMode, selectedActivity, showDeleteConfirmation);
    assert.equal(showDeleteConfirmation.value, true);
    assert.equal(deleteMode.value, 'single');
    assert.equal(selectedActivity.value.id, 9);
    assert.match(text, /hasPermission\('delete_activity_log'\) && !activity\.is_retained/);
    assert.match(text, /O histórico conservado de contas, qualificações e adesões permanece disponível/);
    const { descriptor, errors } = parse(text);
    assert.deepEqual(errors, []);
    const script = compileScript(descriptor, { id: 'staff-history' });
    const template = compileTemplate({ source: descriptor.template.content, filename: 'SystemActivity/Index.vue', id: 'staff-history', compilerOptions: { bindingMetadata: script.bindings } });
    assert.deepEqual(template.errors, []);
});

test('lab-only payload excludes shared identity, global access and server qualification metadata', () => {
    const input = { id: 4, email: 'shared@example.test', name: 'Shared Analyst', password: 'secret', is_active: false,
        roles: [1], permissions: [2], departments: [3], profile_photo_url: '/private',
        personnel_qualifications: [{ id: 50, lab_id: 99, user_id: 999, qualified_by_id: 123, capability: 'Verify',
            department_id: null, is_active: false, notes: '', monitoring_status: 'active' }] };
    const payload = staffAccountPayload(input, { qualifications: true });
    assert.deepEqual(Object.keys(payload), ['personnel_qualifications']);
    assert.equal(payload.personnel_qualifications[0].is_active, false);
    assert.equal(payload.personnel_qualifications[0].capability, 'Verify');
    for (const field of ['id', 'lab_id', 'user_id', 'qualified_by_id', 'monitoring_status']) assert.equal(field in payload.personnel_qualifications[0], false);
    assert.deepEqual(staffAccountPayload(input, {}), {});
});

test('owner payload cannot smuggle global grants and admin submits only explicit fields', () => {
    const data = { name: 'Owner', email: 'owner@example.test', roles: [1], permissions: [2], departments: [3], password: 'secret', personnel_qualifications: [] };
    const owner = staffAccountPayload(data, { profile: true });
    assert.equal(owner.name, 'Owner');
    assert.equal('roles' in owner, false);
    const admin = staffAccountPayload(data, { profile: true, roles: true, permissions: true, departments: true });
    assert.deepEqual(admin.roles, [1]);
    assert.equal('password' in admin, false);
});

test('dossier qualification-only changes do not resubmit unchanged account identity or global grants', () => {
    const original = { name: 'Original name', gender: 'O', roles: [{ value: 1 }], personnel_qualifications: [] };
    const data = { ...original, personnel_qualifications: [{ capability: 'Training', is_active: false }] };
    const capabilities = { profile: true, roles: true, qualifications: true };
    assert.deepEqual(Object.keys(staffAccountPayload(data, capabilities, original)), ['personnel_qualifications']);
    assert.deepEqual(staffAccountPayload(original, capabilities, original), {});
    assert.deepEqual(staffAccountPayload({ ...original, name: 'Changed name' }, capabilities, original), { name: 'Changed name' });
});

test('account mutations freeze IDs, name real routes and retain failure feedback', () => {
    let transform;
    let request;
    const errors = {};
    const form = { processing: false, clearErrors() {}, transform(callback) { transform = callback; },
        post(url, options) { this.processing = true; request = { url, options }; }, setError(key, value) { errors[key] = value; } };
    const ids = [4, 5];
    let completed = false;
    assert.equal(submitStaffAccountMutation(form, 'delete', ids, (name) => name, () => { completed = true; }), true);
    ids.splice(0, 2, 999);
    assert.equal(request.url, 'users.destroy');
    assert.deepEqual(transform(), { recordIds: [4, 5] });
    assert.equal(submitStaffAccountMutation(form, 'delete', ids, (name) => name, () => {}), false);
    assert.equal(request.options.onHttpException(), false);
    assert.equal(completed, false);
    assert.ok(errors.request);
    assert.equal(request.options.onNetworkError(), false);
    assert.match(errors.request, /interrompida/);
    request.options.onCancel();
    assert.match(errors.request, /não foi confirmada/);
    assert.equal(completed, false);
    request.options.onSuccess();
    assert.equal(completed, true);
});

test('membership removal uses the lab-only DELETE endpoint with no account-wide payload', () => {
    let payload;
    let request;
    const form = { processing: false, clearErrors() {}, transform(callback) { payload = callback(); }, delete(url, options) { request = { url, options }; }, setError() {} };
    const route = (name, parameters) => ({ name, parameters });
    assert.equal(submitStaffAccountMutation(form, 'removeMembership', [7], route, () => {}), true);
    assert.deepEqual(request.url, { name: 'users.membership.destroy', parameters: { user: 7 } });
    assert.deepEqual(payload, {});
});

test('activation requests freeze identity and submit explicit desired state rather than toggling', () => {
    for (const action of ['ban', 'unban']) {
        let transform;
        let request;
        const form = { processing: false, clearErrors() {}, transform(callback) { transform = callback; }, post(url, options) { request = { url, options }; }, setError() {} };
        const ids = [7];
        submitStaffAccountMutation(form, action, ids, (name, parameters) => ({ name, parameters }), () => {});
        ids[0] = 99;
        assert.deepEqual(request.url, { name: 'users.setActiveStatus', parameters: { id: 7 } });
        assert.deepEqual(transform(), { is_active: action === 'unban' });
        assert.deepEqual(transform(), { is_active: action === 'unban' });
    }
});

test('membership form uses the existing slide-over action slot, keeps input on failure and refuses pending close', () => {
    const text = read('Components/LaboratoryMembershipForm.vue');
    const calls = [];
    const errors = {};
    let payload;
    let request;
    const form = { email: 'shared@example.test', processing: true, clearErrors() {}, reset() { this.email = '' },
        setError(field, message) { errors[field] = message }, transform(callback) { payload = callback(this); return this },
        post(url, options) { request = { url, options }; this.processing = true } };
    const emit = event => calls.push(event);
    const close = new Function('form', 'emit', body(text, 'close'));
    const submit = new Function('form', 'emit', 'route', body(text, 'submit'));
    close(form, emit);
    submit(form, emit, name => name);
    assert.equal(request, undefined);
    assert.deepEqual(calls, []);
    form.processing = false;
    submit(form, emit, name => name);
    assert.deepEqual(payload, { email: 'shared@example.test' });
    assert.equal(request.url, 'users.membership.store');
    assert.equal(request.options.onNetworkError(), false);
    assert.equal(request.options.onHttpException(), false);
    request.options.onCancel();
    assert.equal(form.email, 'shared@example.test');
    assert.ok(errors.request);
    assert.deepEqual(calls, []);
    request.options.onSuccess();
    assert.equal(form.email, '');
    assert.deepEqual(calls, ['close']);
    assert.match(text, /#action_buttons/);
    assert.match(text, /role="alert"/);
    assert.match(text, /:disabled="form.processing"/);
    assert.match(text, /:aria-busy="form.processing"/);
    assert.match(text, /flex flex-wrap justify-end gap-3/);
});

test('shared slide-over cannot hide itself while pending and retains its default close behavior', () => {
    const text = read('Components/slide-over.vue');
    const handler = text.match(/const close = \(\) => \{([\s\S]*?)\n\}/)[1];
    const props = { disabled: true };
    const open = ref(true);
    const events = [];
    const close = new Function('props', 'open', 'emit', handler);
    close(props, open, event => events.push(event));
    assert.equal(open.value, true);
    assert.deepEqual(events, []);
    props.disabled = false;
    close(props, open, event => events.push(event));
    assert.equal(open.value, false);
    assert.deepEqual(events, ['close', 'closed']);
    assert.doesNotMatch(text, /ease-in|transition-all/);
    assert.match(text, /transition-\[translate,opacity\]/);
    assert.match(text, /motion-reduce:translate-x-0/);
});

test('confirmation blocks dismissal while pending and retains opt-in dialogs until success', () => {
    const text = read('Components/confirm-dialog.vue');
    const props = { disabled: true, keepOpenOnConfirm: true };
    const open = ref(true);
    const events = [];
    const emit = (...event) => events.push(event);
    const cancel = new Function('props', 'open', 'emit', body(text, 'cancelDialog'));
    const confirm = new Function('props', 'open', 'emit', body(text, 'confirmDialog'));
    cancel(props, open, emit);
    confirm(props, open, emit);
    assert.equal(open.value, true);
    assert.deepEqual(events, []);
    props.disabled = false;
    confirm(props, open, emit);
    assert.equal(open.value, true);
    assert.deepEqual(events, [['confirmed', true]]);
    props.keepOpenOnConfirm = false;
    confirm(props, open, emit);
    assert.equal(open.value, false);
    open.value = true;
    cancel(props, open, emit);
    assert.equal(open.value, false);
    assert.deepEqual(events.at(-1), ['canceled']);
});

test('staff bulk confirmation captures the selection and blocks unauthorized or pending replacement', () => {
    const text = read('Pages/Users/Index.vue');
    const mutationForm = { processing: false, clearErrors() {} };
    const props = { accountCapabilities: { delete: true, restore: false } };
    const selectedAction = ref(null);
    const selectedRecordId = ref(null);
    const pendingRecordIds = ref([]);
    const pageRecords = ref([{ id: 4, selected: true }, { id: 5, selected: false }]);
    const showActionConfirmation = ref(false);
    const request = new Function('mutationForm', 'props', 'selectedAction', 'selectedRecordId', 'pendingRecordIds', 'pageRecords', 'showActionConfirmation', 'action', body(text, 'requestBulkAction'));
    const invoke = action => request(mutationForm, props, selectedAction, selectedRecordId, pendingRecordIds, pageRecords, showActionConfirmation, action);
    invoke('delete');
    assert.equal(showActionConfirmation.value, true);
    assert.deepEqual(pendingRecordIds.value, [4]);
    pageRecords.value[0].selected = false;
    pageRecords.value[1].selected = true;
    assert.deepEqual(pendingRecordIds.value, [4]);
    invoke('restore');
    assert.equal(selectedAction.value, 'delete');
    assert.deepEqual(pendingRecordIds.value, [4]);
    mutationForm.processing = true;
    invoke('delete');
    assert.deepEqual(pendingRecordIds.value, [4]);
});

test('dossier success refreshes saved qualification evidence before any separate password operation', () => {
    const text = read('Pages/Users/Edit.vue');
    const form = { isDirty: true, processing: false, id: 4, clearErrors() {}, setError() {}, transform() { return this; }, put(url, options) { this.options = options; } };
    const passwordForm = { isDirty: true, processing: false };
    const isSaving = ref(false);
    const events = [];
    const save = new Function('form', 'passwordForm', 'isSaving', 'props', 'staffAccountPayload', 'route', 'syncSavedDossier', 'savePassword', 'finishSaving', body(text, 'saveDossier'));
    save(form, passwordForm, isSaving, { accountCapabilities: {} }, staffAccountPayload, name => name,
        record => events.push(['saved', record]), () => events.push(['password']), () => events.push(['finished']));
    const saved = { id: 4, personnel_qualifications: [{ id: 12, monitoring_status: 'inactive', qualified_by: 'Manager' }] };
    form.options.onSuccess({ props: { record: saved } });
    assert.deepEqual(events, [['saved', saved], ['password']]);
    assert.equal(form.options.onNetworkError(), false);
    assert.equal(form.options.onHttpException(), false);
});

test('dossier saved defaults come from a detached server snapshot, not stale draft metadata', () => {
    const text = read('Pages/Users/Edit.vue');
    const record = { id: 4, gender: null, roles: [{ value: 1 }], personnel_qualifications: [{ id: 15, department_id: { value: 3 }, monitoring_status: 'inactive' }] };
    const normalize = new Function('record', body(text, 'dossierFormData'));
    const normalized = normalize(record);
    normalized.roles[0].value = 99;
    normalized.personnel_qualifications[0].department_id.value = 99;
    assert.equal(record.roles[0].value, 1);
    assert.equal(record.personnel_qualifications[0].department_id.value, 3);
    assert.equal(normalized.gender, 'O');
    const calls = [];
    const form = { defaults(value) { calls.push(value); }, reset() { calls.push('reset'); }, clearErrors() { calls.push('clear'); } };
    new Function('record', 'form', 'dossierFormData', body(text, 'syncSavedDossier'))(record, form, normalize);
    assert.equal(calls[0].personnel_qualifications[0].id, 15);
    assert.deepEqual(calls.slice(1), ['reset', 'clear']);
    assert.doesNotMatch(body(text, 'finishSaving'), /form\.defaults/);
    assert.match(text, /<fieldset[^>]+:disabled="isSaving"/);
    assert.match(text, /<Head :title="`Dossier de pessoal/);
});

for (const name of ['loadDepartments', 'loadRoles']) {
    test(`${name} settles empty, successful and failed lookups without discarding the dossier`, async () => {
        const load = new Function('query', 'setOptions', 'fetch', 'route', 'lookupErrors', body(read('Pages/Users/Edit.vue'), name));
        const options = [];
        const lookupErrors = ref({});
        const key = name === 'loadRoles' ? 'roles' : 'departments';
        const route = (name, params) => `${name}?q=${params.q}`;
        let requests = 0;
        const fetch = async () => { requests++; return { ok: true, json: async () => [{ id: 5, name: 'Available option', label: null }] }; };
        await load('', value => options.push(value), fetch, route, lookupErrors);
        assert.equal(requests, 0);
        assert.deepEqual(options.pop(), []);
        await load('option', value => options.push(value), fetch, route, lookupErrors);
        assert.deepEqual(options.pop(), [{ value: 5, label: 'Available option' }]);
        for (const failed of [async () => ({ ok: false }), async () => { throw new Error('offline'); }]) {
            await load('option', value => options.push(value), failed, route, lookupErrors);
            assert.deepEqual(options.pop(), []);
            assert.match(lookupErrors.value[key], /alterações foram preservadas/);
        }
        await load('option', value => options.push(value), fetch, route, lookupErrors);
        assert.equal(lookupErrors.value[key], '');
    });
}

for (const path of ['Pages/Users/Index.vue', 'Pages/Users/Edit.vue', 'Pages/Roles/Index.vue', 'Pages/Permissions/Index.vue', 'Components/records-table.vue', 'Components/confirm-dialog.vue', 'Components/LaboratoryMembershipForm.vue', 'Components/slide-over.vue']) {
    test(`${path} compiles with capability/recovery controls`, () => {
        const text = readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');
        const { descriptor, errors } = parse(text);
        assert.deepEqual(errors, []);
        const script = compileScript(descriptor, { id: path });
        const template = compileTemplate({ source: descriptor.template.content, filename: path, id: path, compilerOptions: { bindingMetadata: script.bindings } });
        assert.deepEqual(template.errors, []);
        if (path === 'Pages/Users/Edit.vue') {
            assert.match(text, /staffAccountPayload\(data, props.accountCapabilities, dossierFormData\(props.record\)\)/);
            assert.match(text, /editUserInfo && accountCapabilities.profile/);
            assert.match(text, /role="alert"/);
            assert.match(text, /if \(isSaving.value \|\| form.processing \|\| passwordForm.processing\) return/);
        }
        if (path === 'Pages/Users/Index.vue') {
            assert.match(text, /<Head :title="\$t\('gestlab.general.labels.users.page_title'\)"/);
            assert.match(text, /keep-open-on-confirm/);
            assert.match(text, /mutationForm.hasErrors/);
            assert.match(text, /:archive-handler=/);
        }
        if (path === 'Components/confirm-dialog.vue') {
            assert.doesNotMatch(text, /ease-in|transition-all/);
            assert.match(text, /if \(props.disabled\) return/);
        }
    });
}
