<template>
  <div class="transfer-create-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      :icon="TruckIcon"
      title="Criar Transferência"
      subtitle="Registe uma transferência de stock entre armazéns com rastreabilidade, datas e validação de disponibilidade."
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar
          </button>
        </div>
      </template>
    </ModuleHero>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- TRANSFER DETAILS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
          Detalhes da Transferência
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Item Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Item
              <span class="text-red-500">*</span>
            </label>
            <comboboxEnhanced
              v-model="selectedItemOption"
              :has-error="form.errors.item_id"
              :options="itemOptions"
              placeholder="Pesquisar item com stock disponível"
            />
            <p v-if="form.errors.item_id" class="text-xs text-red-600">
              {{ form.errors.item_id }}
            </p>
          </div>

          <!-- Source Warehouse -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Armazém de Origem
              <span class="text-red-500">*</span>
            </label>
            <comboboxEnhanced
              v-model="selectedSourceWarehouseOption"
              :disabled="!form.item_id"
              :has-error="form.errors.source_id"
              :options="sourceWarehouseOptions"
              placeholder="Pesquisar armazém de origem"
            />
            <p v-if="form.errors.source_id" class="text-xs text-red-600">
              {{ form.errors.source_id }}
            </p>
          </div>

          <!-- Destination Warehouse -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Armazém de Destino
              <span class="text-red-500">*</span>
            </label>
            <comboboxEnhanced
              v-model="selectedDestinationWarehouseOption"
              :has-error="form.errors.destination_id"
              :options="destinationWarehouseOptions"
              placeholder="Pesquisar armazém de destino"
            />
            <p v-if="form.errors.destination_id" class="text-xs text-red-600">
              {{ form.errors.destination_id }}
            </p>
          </div>

          <!-- Quantity -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Quantidade
              <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
              <input
                type="number"
                v-model="form.qty"
                :min="1"
                :max="maxQuantity"
                :class="[
                  'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                  form.errors.qty ? 'border-red-300' : 'border-gray-300'
                ]"
                placeholder="Quantidade a transferir"
                required
              />
              <span class="text-sm text-gray-500 whitespace-nowrap">
                Máx: {{ maxQuantity }}
              </span>
            </div>
            <p v-if="form.errors.qty" class="text-xs text-red-600">
              {{ form.errors.qty }}
            </p>
          </div>

          <!-- Sent Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Envio
            </label>
            <input
              type="date"
              v-model="form.sent_date"
              :min="minDate"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.sent_date ? 'border-red-300' : 'border-gray-300'
              ]"
            />
            <p v-if="form.errors.sent_date" class="text-xs text-red-600">
              {{ form.errors.sent_date }}
            </p>
          </div>

          <!-- Expected Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Recebimento Esperado
            </label>
            <input
              type="date"
              v-model="form.expected_date"
              :min="form.sent_date || minDate"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.expected_date ? 'border-red-300' : 'border-gray-300'
              ]"
            />
            <p v-if="form.errors.expected_date" class="text-xs text-red-600">
              {{ form.errors.expected_date }}
            </p>
          </div>
        </div>

        <!-- Observations -->
        <div class="mt-6 space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Observações
          </label>
          <textarea
            v-model="form.obs"
            rows="3"
            :class="[
              'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
              form.errors.obs ? 'border-red-300' : 'border-gray-300'
            ]"
            placeholder="Observações logísticas, condições de transporte ou instruções..."
          ></textarea>
          <p v-if="form.errors.obs" class="text-xs text-red-600">
            {{ form.errors.obs }}
          </p>
        </div>
      </div>

      <!-- STOCK INFORMATION -->
      <div v-if="selectedItem && form.source_id" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <CubeIcon class="h-5 w-5 text-blue-900" />
          Informações do Estoque
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Source Warehouse Stock -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Estoque do Armazém de Origem
            </label>
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ getWarehouseName(form.source_id) }}
                  </p>
                  <p class="text-xs text-gray-500">Estoque Disponível Actual</p>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-bold" :class="currentStock >= form.qty ? 'text-green-600' : 'text-red-600'">
                    {{ currentStock }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ form.qty }} solicitado
                  </p>
                </div>
              </div>
              <div v-if="currentStock < form.qty" class="mt-2 p-2 bg-red-50 rounded">
                <p class="text-xs text-red-600">
                  ⚠️ Estoque Insuficiente. Disponível: {{ currentStock }}, Solicitado: {{ form.qty }}
                </p>
              </div>
            </div>
          </div>

          <!-- Selected Item Details -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Detalhes do Item
            </label>
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Nome:</span>
                  <span class="font-medium text-gray-900">{{ selectedItem.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Código Interno:</span>
                  <span class="font-medium text-gray-900">{{ selectedItem.internal_code }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Categoria:</span>
                  <span class="font-medium text-gray-900">{{ selectedItem.category?.name || 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Unidade:</span>
                  <span class="font-medium text-gray-900">{{ selectedItem.unit?.code || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <div class="text-sm text-gray-500">
          <p v-if="form.source_id && form.destination_id">
            Transferindo de <span class="font-semibold">{{ getWarehouseName(form.source_id) }}</span>
            para <span class="font-semibold">{{ getWarehouseName(form.destination_id) }}</span>
          </p>
        </div>
        <div class="flex items-center gap-4">
          <button
            type="button"
            @click="goBack"
            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing || !isFormValid"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
              form.processing || !isFormValid
                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
            ]"
          >
            <PaperAirplaneIcon v-if="!form.processing" class="h-5 w-5" />
            <span v-if="form.processing">Processando...</span>
            <span v-else>Criar Transferência</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { useForm, router, usePage } from '@inertiajs/vue3'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import {
  TruckIcon,
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  PaperAirplaneIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  defaultSource: {
    type: [String, Number],
    default: ''
  },
  defaultItem: {
    type: [String, Number],
    default: ''
  },
  initialStockInfo: { // New prop
    type: Object,
    default: () => ({})
  } 
})

const page = usePage();

const form = useForm({
  item_id: props.defaultItem || '',
  source_id: props.defaultSource || '',
  destination_id: '',
  qty: 1,
  sent_date: new Date().toISOString().split('T')[0],
  expected_date: '',
  obs: ''
})

// const stockInfo = ref({})
const stockInfo = ref(props.initialStockInfo || {})
const loadingStock = ref(false)
const selectedItemOption = ref(null)
const selectedSourceWarehouseOption = ref(null)
const selectedDestinationWarehouseOption = ref(null)
const minDate = new Date().toISOString().split('T')[0]

const selectedItem = computed(() => {
  return props.items.find(item => item.id == form.item_id)
})

const currentStock = computed(() => {
  if (!form.item_id || !form.source_id) return 0
  const key = `${form.item_id}_${form.source_id}`
  return stockInfo.value[key] || 0
})

const maxQuantity = computed(() => {
  return currentStock.value
})

const sourceWarehouses = computed(() => {
  if (!form.item_id) return props.warehouses
  
  return props.warehouses.map(warehouse => {
    const key = `${form.item_id}_${warehouse.id}`
    return {
      ...warehouse,
      available_stock: stockInfo.value[key] || 0
    }
  }).filter(warehouse => warehouse.available_stock > 0)
})

const destinationWarehouses = computed(() => {
  return props.warehouses.filter(warehouse => warehouse.id !== form.source_id)
})

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.internal_code || item.code ? ` (${item.internal_code || item.code})` : ''}${!hasStockInAnyWarehouse(item) ? ' - Sem stock disponível' : ''}`,
  disabled: !hasStockInAnyWarehouse(item),
})))

const sourceWarehouseOptions = computed(() => sourceWarehouses.value.map((warehouse) => ({
  value: warehouse.id,
  label: `${warehouse.name}${warehouse.available_stock !== undefined ? ` (Disponível: ${warehouse.available_stock})` : ''}`,
})))

const destinationWarehouseOptions = computed(() => destinationWarehouses.value.map((warehouse) => ({
  value: warehouse.id,
  label: warehouse.location?.name ? `${warehouse.name} (${warehouse.location.name})` : warehouse.name,
})))

const isFormValid = computed(() => {
  return form.item_id && 
         form.source_id && 
         form.destination_id && 
         form.destination_id !== form.source_id &&
         form.qty > 0 && 
         form.qty <= maxQuantity.value
})

// function hasStockInAnyWarehouse(item) {
//   return props.warehouses.some(warehouse => {
//     const key = `${item.id}_${warehouse.id}`
//     return (stockInfo.value[key] || 0) > 0
//   })
// }

function hasStockInAnyWarehouse(item) {
  return props.warehouses.some(warehouse => {
    const key = `${item.id}_${warehouse.id}`
    return (stockInfo.value[key] || 0) > 0
  })
}

watch(selectedItemOption, async (item) => {
  form.item_id = item?.value || ''
  await onItemChange()
})

watch(selectedSourceWarehouseOption, async (warehouse) => {
  form.source_id = warehouse?.value || ''
  await onSourceChange()

  if (selectedDestinationWarehouseOption.value?.value === form.source_id) {
    selectedDestinationWarehouseOption.value = null
  }
})

watch(selectedDestinationWarehouseOption, (warehouse) => {
  form.destination_id = warehouse?.value || ''
})

function getWarehouseName(id) {
  const warehouse = props.warehouses.find(w => w.id == id)
  return warehouse ? warehouse.name : 'N/A'
}

// async function onItemChange() {
//   form.source_id = ''
//   form.qty = 1
  
//   if (form.item_id) {
//     await fetchStockInfo()
//   }
// }

async function onItemChange() {
  form.source_id = ''
  form.destination_id = ''
  form.qty = 1
  selectedSourceWarehouseOption.value = null
  selectedDestinationWarehouseOption.value = null
  
  // Optional: Fetch fresh data when item changes
  if (form.item_id) {
    await fetchRealTimeStock()
  }
}

// async function fetchRealTimeStock() {
//   if (!form.item_id) return
  
//   loadingStock.value = true
//   try {
//     // Batch request for all warehouses for this item
//     const response = await axios.get(route('vap-inventory.transfers.item-stock'), {
//       params: {
//         item_id: form.item_id
//       }
//     })
    
//     const data = response.data
    
//     // Update stock for all warehouses
//     props.warehouses.forEach(warehouse => {
//       const key = `${form.item_id}_${warehouse.id}`
//       stockInfo.value[key] = data.stocks?.[warehouse.id] || 0
//     })
//   } catch (error) {
//     console.error('Failed to fetch real-time stock:', error)
//   } finally {
//     loadingStock.value = false
//   }
// }

async function fetchRealTimeStock() {
  if (!form.item_id) return
  
  loadingStock.value = true
  try {
    const response = await axios.get(route('vap-inventory.transfers.item-stock-all'), {
      params: {
        item_id: form.item_id
      }
    })
    
    const data = response.data
    
    // Update stock for all warehouses
    props.warehouses.forEach(warehouse => {
      const key = `${form.item_id}_${warehouse.id}`
      stockInfo.value[key] = data.stocks[warehouse.id] || 0
    })
  } catch (error) {
    console.error('Failed to fetch real-time stock:', error)
  } finally {
    loadingStock.value = false
  }
}

async function onSourceChange() {
  if (form.source_id) {
    form.qty = Math.min(form.qty, maxQuantity.value)
  }
}

// async function fetchStockInfo() {
//   if (!form.item_id) return
  
//   loadingStock.value = true
//   try {
//     for (const warehouse of props.warehouses) {
//       const response = await fetch(route('vap-inventory.transfers.item-stock', {
//         item_id: form.item_id,
//         warehouse_id: warehouse.id
//       }))
//       const data = await response.json()
//       const key = `${form.item_id}_${warehouse.id}`
//       stockInfo.value[key] = data.available || 0
//     }
//   } catch (error) {
//     console.error('Failed to fetch stock info:', error)
//   } finally {
//     loadingStock.value = false
//   }
// }

async function fetchStockInfo() {
  if (!form.item_id) return
  
  loadingStock.value = true
  try {
    const response = await axios.get(route('vap-inventory.transfers.item-stock'), {
      params: {
        item_id: form.item_id,
        warehouse_id: form.source_id // Only fetch for selected source
      }
    })
    
    const data = response.data
    const key = `${form.item_id}_${form.source_id}`
    stockInfo.value[key] = data.available || 0
  } catch (error) {
    console.error('Failed to fetch stock info:', error)
  } finally {
    loadingStock.value = false
  }
}

async function fetchStockInfoForAllItems() {
  loadingStock.value = true
  try {
    // Fetch stock for all items across all warehouses
    for (const item of props.items) {
      for (const warehouse of props.warehouses) {
        const response = await fetch(route('vap-inventory.transfers.item-stock', {
          item_id: item.id,
          warehouse_id: warehouse.id
        }))
        const data = await response.json()
        const key = `${item.id}_${warehouse.id}`
        stockInfo.value[key] = data.available || 0
      }
    }
  } catch (error) {
    console.error('Failed to fetch stock info:', error)
  } finally {
    loadingStock.value = false
  }
}

function submit() {
  form.post(route('vap-inventory.transfers.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}

function goBack() {
  router.visit(route('vap-inventory.transfers.index'))
}

onMounted(() => {
  if (props.defaultItem) {
    const item = props.items.find((item) => item.id == props.defaultItem)

    if (item) {
      selectedItemOption.value = {
        value: item.id,
        label: `${item.name}${item.internal_code || item.code ? ` (${item.internal_code || item.code})` : ''}`,
      }
    }
  }

  if (props.defaultSource) {
    const warehouse = props.warehouses.find((warehouse) => warehouse.id == props.defaultSource)

    if (warehouse) {
      selectedSourceWarehouseOption.value = {
        value: warehouse.id,
        label: warehouse.location?.name ? `${warehouse.name} (${warehouse.location.name})` : warehouse.name,
      }
    }
  }

  if (props.defaultItem) {
    // You might still want to fetch fresh data for the default item
    fetchRealTimeStock()
  }
})

watch(() => form.source_id, () => {
  if (form.source_id && form.qty > maxQuantity.value) {
    form.qty = maxQuantity.value
  }
})
</script>

<style scoped>
.transfer-create-shell :deep(.bg-white.rounded-xl),
.transfer-create-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(226 232 240);
  border-radius: 1.5rem;
  background: rgb(255 255 255);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
}

.transfer-create-shell :deep(.bg-gray-50) {
  border-color: rgb(226 232 240);
  background: rgb(248 250 252 / 0.84);
}

.transfer-create-shell :deep(.bg-red-50) {
  background: rgb(254 242 242 / 0.86);
}

.transfer-create-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-900, 30 58 138));
}

.transfer-create-shell :deep(input),
.transfer-create-shell :deep(select),
.transfer-create-shell :deep(textarea) {
  border-color: rgb(203 213 225);
  border-radius: 0.875rem;
  background: rgb(255 255 255);
  color: rgb(15 23 42);
}

.transfer-create-shell :deep(input:focus),
.transfer-create-shell :deep(select:focus),
.transfer-create-shell :deep(textarea:focus) {
  border-color: rgb(var(--color-primary-500, 59 130 246));
  box-shadow: 0 0 0 3px rgb(var(--color-primary-500, 59 130 246) / 0.16);
}

.transfer-create-shell :deep(input::placeholder),
.transfer-create-shell :deep(textarea::placeholder) {
  color: rgb(148 163 184);
}

.transfer-create-shell :deep(.border-gray-200),
.transfer-create-shell :deep(.border-gray-300) {
  border-color: rgb(226 232 240);
}

.transfer-create-shell :deep(.hover\:bg-gray-50:hover) {
  background: rgb(var(--color-primary-50, 239 246 255) / 0.58);
}

:global(.dark) .transfer-create-shell :deep(.bg-white.rounded-xl),
:global(.dark) .transfer-create-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(30 41 59);
  background:
    radial-gradient(circle at top right, rgb(var(--color-primary-500, 59 130 246) / 0.1), transparent 30%),
    rgb(2 6 23);
}

:global(.dark) .transfer-create-shell :deep(.bg-white) {
  background: rgb(2 6 23);
}

:global(.dark) .transfer-create-shell :deep(.bg-gray-50),
:global(.dark) .transfer-create-shell :deep(.hover\:bg-gray-50:hover) {
  border-color: rgb(51 65 85);
  background: rgb(15 23 42 / 0.72);
}

:global(.dark) .transfer-create-shell :deep(.bg-red-50) {
  border-color: rgb(248 113 113 / 0.28);
  background: rgb(239 68 68 / 0.1);
}

:global(.dark) .transfer-create-shell :deep(.bg-gray-200) {
  background-color: rgb(30 41 59);
}

:global(.dark) .transfer-create-shell :deep(.text-gray-900),
:global(.dark) .transfer-create-shell :deep(.text-gray-800),
:global(.dark) .transfer-create-shell :deep(.text-gray-700) {
  color: rgb(226 232 240);
}

:global(.dark) .transfer-create-shell :deep(.text-gray-600),
:global(.dark) .transfer-create-shell :deep(.text-gray-500) {
  color: rgb(148 163 184);
}

:global(.dark) .transfer-create-shell :deep(.border-gray-200),
:global(.dark) .transfer-create-shell :deep(.border-gray-300) {
  border-color: rgb(30 41 59);
}

:global(.dark) .transfer-create-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-200, 191 219 254));
}

:global(.dark) .transfer-create-shell :deep(input),
:global(.dark) .transfer-create-shell :deep(select),
:global(.dark) .transfer-create-shell :deep(textarea) {
  border-color: rgb(51 65 85);
  background: rgb(2 6 23 / 0.78);
  color: rgb(241 245 249);
}

:global(.dark) .transfer-create-shell :deep(input[type='date']) {
  color-scheme: dark;
}

:global(.dark) .transfer-create-shell :deep(input::placeholder),
:global(.dark) .transfer-create-shell :deep(textarea::placeholder) {
  color: rgb(100 116 139);
}

:global(.dark) .transfer-create-shell :deep(.text-green-600) {
  color: rgb(110 231 183);
}

:global(.dark) .transfer-create-shell :deep(.text-red-600) {
  color: rgb(252 165 165);
}
</style>
