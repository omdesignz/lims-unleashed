const optionValue = (value) => value && typeof value === 'object' ? value.value || null : value || null;

export function quoteSiteLabel(quote) {
    return quote.warehouse?.trim() || quote.warehouse_id?.address?.trim() || quote.warehouse_id?.name?.trim()
        || quote.warehouse_id?.code || 'Local não disponível';
}

export function quoteBillingLabel(quote) {
    return quote.invoice_id || quote.converted_to_invoice ? 'Facturada' : 'Não facturada';
}

export function prepareQuoteLine(line = {}) {
    return {
        ...line,
        catalog_type: line.catalog_type ?? line.item_id?.catalog_type ?? '',
        agreed_unit_price: line.agreed_unit_price ?? line.item_id?.price ?? 0,
        discount_mode: line.discount_mode ?? (line.discount_id === 2 ? 'fixed' : 'percentage'),
        discount_value: line.discount_value ?? line.discount_amount ?? 0,
        qty: line.qty ?? 1,
    };
}

export function selectQuoteCatalog(line) {
    line.catalog_type = line.item_id?.catalog_type ?? '';
    line.agreed_unit_price = line.item_id?.price ?? 0;
}

export function quotePayload(data) {
    const payload = {};
    for (const key of ['customer_id', 'warehouse_id', 'date', 'due_date', 'internal_ref', 'description', 'obs', 'use_matrix_price', 'is_service']) {
        if (data[key] !== undefined) payload[key] = ['customer_id', 'warehouse_id'].includes(key) ? optionValue(data[key]) : data[key];
    }
    payload.items = data.items.map((line) => ({
        catalog_type: line.catalog_type,
        item_id: optionValue(line.item_id),
        unit_id: optionValue(line.unit_id),
        collection_product_id: line.itemable_type === 'collectionproduct' ? optionValue(line.itemable_id) : null,
        qty: line.qty,
        agreed_unit_price: line.agreed_unit_price,
        discount_mode: line.discount_mode,
        discount_value: line.discount_value,
        obs: line.obs || null,
    }));
    return payload;
}

function scaled(value) {
    const text = String(value ?? '');
    if (!/^\d{1,8}(\.\d{1,2})?$/.test(text)) throw new Error('Invalid decimal');
    const [integer, fraction = ''] = text.split('.');
    return BigInt(integer) * 100n + BigInt(fraction.padEnd(2, '0'));
}

const amount = (value) => Number(value) / 100;

export function quoteLinePreview(item) {
    try {
        const price = scaled(item.agreed_unit_price);
        const quantity = scaled(item.qty);
        const value = scaled(item.discount_value);
        if (quantity === 0n || (item.discount_mode === 'percentage' && value > 10000n)) throw new Error('Invalid quantity or discount');
        const discount = item.discount_mode === 'percentage' ? (price * value + 5000n) / 10000n : value;
        if (discount > price) throw new Error('Discount exceeds price');
        const subtotal = ((price - discount) * quantity + 50n) / 100n;
        const discountTotal = (discount * quantity + 50n) / 100n;
        const rate = item.item_id?.charge_tax ? scaled(item.item_id.tax_percentage ?? 0) : 0n;
        const tax = (subtotal * rate + 5000n) / 10000n;
        if (rate > 10000n || [subtotal, discountTotal, tax, subtotal + tax].some((value) => value > 9999999999n)) throw new Error('Amount exceeds precision');
        return { item, id: item.id, qty: item.qty, obs: item.obs, item_id: item.item_id, item_description: item.item_id?.label ?? item.item_description,
            unit_id: item.unit_id, product_price: amount(price), unit_price: amount(price - discount), total: amount(subtotal),
            discount_amount: amount(discount), discount_total: amount(discountTotal), tax_amount: amount(tax), tax: amount(rate), valid: true };
    } catch {
        return { item, id: item.id, qty: item.qty, item_id: item.item_id, item_description: item.item_id?.label ?? item.item_description,
            unit_price: 0, total: 0, discount_amount: 0, discount_total: 0, tax_amount: 0, valid: false };
    }
}

export function saveQuoteForm(form, url, method, onSuccess = () => {}) {
    if (form.processing) return;
    form.clearErrors('request');
    try {
        form.transform(quotePayload)[method](url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess,
            onNetworkError: () => {
                form.setError('request', 'Ligação interrompida. O rascunho foi preservado.');
                return false;
            },
            onHttpException: () => {
                form.setError('request', 'Não foi possível guardar. O rascunho foi preservado.');
                return false;
            },
        });
    } catch {
        form.setError('request', 'Não foi possível iniciar o pedido. O rascunho foi preservado.');
    }
}
