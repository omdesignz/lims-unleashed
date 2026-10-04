import { useHttp } from '@inertiajs/vue3';
import { effectScope, onScopeDispose, ref, watch } from 'vue';
import { createCustomerSiteLoader } from './useCommercialDocumentOptions';

export function useCustomerSiteOptions(form) {
    const loadingWarehouses = ref(false);
    const createHttp = () => {
        const scope = effectScope(true);
        const http = scope.run(() => useHttp({ customer_id: null, q: '' }));
        return { http, dispose: () => scope.stop() };
    };
    const loader = createCustomerSiteLoader(createHttp, () => form.customer_id?.value,
        () => route('warehouses.getWarehouse'),
        (message) => message ? form.setError('warehouse_id', message) : form.clearErrors('warehouse_id'),
        (loading) => { loadingWarehouses.value = loading; });
    watch(() => form.customer_id?.value, () => {
        form.warehouse_id = null;
        loader.load('', (options) => { form.warehouse_id = options[0] ?? null; });
    });
    onScopeDispose(loader.dispose);

    return { loadWarehouses: loader.load, loadingWarehouses };
}
