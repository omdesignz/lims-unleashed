export function networkStockFilters(filters = {}) {
  return {
    search: filters.search ?? '',
    lab_id: filters.lab_id ?? '',
    warehouse_id: filters.warehouse_id ?? '',
    lot: filters.lot ?? '',
    expiry_from: filters.expiry_from ?? '',
    expiry_to: filters.expiry_to ?? '',
    available: Boolean(Number(filters.available ?? 0)),
  }
}

export function formatNetworkQuantity(value) {
  const [integer, fraction = ''] = String(value ?? '0').split('.')
  const formatted = new Intl.NumberFormat('pt-AO').format(BigInt(integer))
  const decimals = fraction.replace(/0+$/, '')
  return decimals ? `${formatted},${decimals}` : formatted
}

export function networkAvailabilityLabel(row) {
  if (row.availability_state === 'available' && !/[1-9]/.test(String(row.available_quantity))) return 'Sem saldo'
  return {
    available: 'Disponível', reserved: 'Reservado', blocked: 'Bloqueado',
    expired: 'Expirado', inconsistent: 'Saldo por reconciliar',
  }[row.availability_state] ?? 'Indisponível'
}
