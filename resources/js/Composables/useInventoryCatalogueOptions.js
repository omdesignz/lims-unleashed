import { useHttp } from '@inertiajs/vue3'
import { effectScope, onScopeDispose } from 'vue'

export function createInventoryCatalogueLoader(createHttp, url, label = item => [...new Set([item.name, item.code, item.internal_code].filter(Boolean))].join(' · ')) {
  const pending = new Map()
  let disposed = false

  function cancel(entry) {
    entry?.http.cancel()
    entry?.dispose?.()
  }

  async function load(query, setOptions) {
    if (disposed) return
    cancel(pending.get(setOptions))
    pending.delete(setOptions)
    const search = String(query ?? '').trim()
    if (!search) {
      setOptions([])
      return
    }
    const entry = createHttp()
    const { http } = entry
    pending.set(setOptions, entry)
    http.q = search
    try {
      const rows = await http.get(url())
      if (disposed || pending.get(setOptions) !== entry) return
      setOptions((Array.isArray(rows) ? rows : []).map(item => ({
        value: item.id,
        label: label(item),
        category_id: item.category_id,
        inventory_type: item.inventory_type,
        unit_id: item.unit_id,
        is_reagent: item.is_reagent,
      })))
    } catch {
      if (!disposed && pending.get(setOptions) === entry) setOptions([])
    } finally {
      entry.dispose?.()
      if (pending.get(setOptions) === entry) pending.delete(setOptions)
    }
  }

  function dispose() {
    disposed = true
    pending.forEach(cancel)
    pending.clear()
  }

  return { load, dispose }
}

export function useInventoryCatalogueOptions({ inventoryType = null, reagentsOnly = false, label } = {}) {
  const createHttp = () => {
    const scope = effectScope(true)
    const http = scope.run(() => useHttp({ q: '', ...(inventoryType ? { inventory_type: inventoryType } : {}) }))
    return { http, dispose: () => scope.stop() }
  }
  const loader = createInventoryCatalogueLoader(createHttp,
    () => route(reagentsOnly ? 'iitems.getReagentInventoryItem' : 'vap-inventory.items.lookup'), label)
  onScopeDispose(loader.dispose)

  return loader.load
}
