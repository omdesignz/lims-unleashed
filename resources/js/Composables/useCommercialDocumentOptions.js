export function optionRows(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    if (Array.isArray(payload?.items)) {
        return payload.items;
    }

    return [];
}

export function siteOption(site) {
    return { value: site.id, label: site.address?.trim() || site.name?.trim() || site.code || `Local ${site.id}` };
}

export function createCustomerSiteLoader(createHttp, customerId, url, onError = () => {}, onLoading = () => {}) {
    let current = null;
    let disposed = false;

    function cancel(entry) {
        entry?.http.cancel();
        entry?.dispose?.();
    }

    async function load(query, setOptions) {
        if (disposed) return;
        cancel(current);
        current = null;
        const selectedCustomer = customerId();
        if (!selectedCustomer) {
            onLoading(false);
            setOptions([]);
            onError(null);
            return [];
        }
        const entry = createHttp();
        current = entry;
        entry.http.customer_id = selectedCustomer;
        entry.http.q = String(query ?? '');
        onLoading(true);
        try {
            const response = await entry.http.get(url());
            if (disposed || current !== entry || selectedCustomer !== customerId()) return;
            if (response === undefined) throw new Error('Site lookup validation failed');
            const options = optionRows(response).map(siteOption);
            setOptions(options);
            onError(null);
            return options;
        } catch {
            if (disposed || current !== entry || selectedCustomer !== customerId()) return;
            setOptions([]);
            onError('Não foi possível carregar os locais. Tente pesquisar novamente.');
            return [];
        } finally {
            entry.dispose?.();
            if (current === entry) {
                current = null;
                onLoading(false);
            }
        }
    }

    function dispose() {
        disposed = true;
        cancel(current);
        current = null;
        onLoading(false);
    }

    return { load, dispose };
}
