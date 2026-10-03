const timestampFields = ['received_at', 'collected_at', 'analysis_start_date', 'analysis_end_date'];

export function sampleTimestampInput(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

export function sampleEntryPayload(data, original = null) {
    const payload = { ...data };

    if (original) delete payload.portal_request_id;

    if (payload.client_submitted_info) {
        payload.client_submitted_info = { ...payload.client_submitted_info };
        for (const field of ['quantity', 'collected_qty']) {
            const value = payload.client_submitted_info[field];
            if (typeof value === 'number') payload.client_submitted_info[field] = String(value);
        }
    }

    for (const field of timestampFields) {
        if (!(field in payload)) continue;
        const value = payload[field];
        if (original?.[field] && value === sampleTimestampInput(original[field])) {
            payload[field] = original[field];
        } else if (!value) {
            payload[field] = null;
        } else {
            const date = new Date(value);
            payload[field] = Number.isNaN(date.getTime()) ? value : date.toISOString();
        }
    }

    return payload;
}
