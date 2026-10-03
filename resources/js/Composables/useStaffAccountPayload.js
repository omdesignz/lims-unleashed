const profileFields = ['name', 'email', 'username', 'gender', 'dob', 'id_number', 'primary_phone', 'secondary_phone'];
const qualificationFields = ['capability', 'department_id', 'authorized_from', 'authorized_until', 'training_completed_at', 'training_reference', 'notes', 'is_active'];

export function staffAccountPayload(data, capabilities) {
    const payload = {};
    if (capabilities.profile) {
        for (const field of profileFields) payload[field] = data[field] ?? null;
    }
    for (const field of ['departments', 'roles', 'permissions']) {
        if (capabilities[field]) payload[field] = data[field];
    }
    if (capabilities.qualifications) {
        payload.personnel_qualifications = data.personnel_qualifications.map((qualification) =>
            Object.fromEntries(qualificationFields.map((field) => [field, qualification[field] ?? null])));
    }
    return payload;
}

export function submitStaffAccountMutation(form, action, recordIds, route, onSuccess) {
    if (form.processing || !recordIds.length) return false;
    const ids = [...recordIds];
    const routes = { delete: 'users.destroy', restore: 'users.restore', ban: 'users.setActiveStatus', unban: 'users.setActiveStatus', impersonate: 'users.impersonate', removeMembership: 'users.membership.destroy' };
    if (!routes[action]) return false;
    form.clearErrors();
    form.transform(() => ['delete', 'restore'].includes(action) ? { recordIds: ids } : ['ban', 'unban'].includes(action) ? { is_active: action === 'unban' } : action === 'impersonate' ? { id: ids[0] } : {});
    const url = action === 'removeMembership' ? route(routes[action], { user: ids[0] }) : ['ban', 'unban'].includes(action) ? route(routes[action], { id: ids[0] }) : route(routes[action]);
    form[action === 'removeMembership' ? 'delete' : 'post'](url, {
        preserveScroll: true,
        onSuccess,
        onNetworkError: () => form.setError('request', 'Ligação interrompida. A operação não foi confirmada.'),
        onHttpException: () => form.setError('request', 'Não foi possível alterar a conta. Actualize as permissões e tente novamente.'),
    });
    return true;
}
