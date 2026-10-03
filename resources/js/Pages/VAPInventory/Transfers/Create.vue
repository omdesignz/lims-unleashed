<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { RefreshCw as ArrowPathIcon } from '@lucide/vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

/**
 * New transfer (Plano form). Creating it reserves the quantity at the source in
 * one operation; only warehouses holding the item can be the source and the
 * destination must differ from it.
 */
const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  defaultSource: {
    type: [String, Number],
    default: '',
  },
  defaultItem: {
    type: [String, Number],
    default: '',
  },
  initialStockInfo: {
    type: Object,
    default: () => ({}),
  },
})

const form = useForm({
  item_id: props.defaultItem || '',
  source_id: props.defaultSource || '',
  destination_id: '',
  qty: 1,
  sent_date: new Date().toISOString().split('T')[0],
  expected_date: '',
  obs: '',
})

const stockInfo = ref({ ...props.initialStockInfo })
const loadingStock = ref(false)
const stockRefreshError = ref('')
const minDate = new Date().toISOString().split('T')[0]

const selectedItemOption = ref(itemOptionFor(props.defaultItem))
const selectedSourceWarehouseOption = ref(warehouseOptionFor(props.defaultSource))
const selectedDestinationWarehouseOption = ref(null)

const selectedItem = computed(() => props.items.find((item) => String(item.id) === String(form.item_id)) || null)
const sourceWarehouse = computed(() => props.warehouses.find((warehouse) => String(warehouse.id) === String(form.source_id)) || null)
const destinationWarehouse = computed(() => props.warehouses.find((warehouse) => String(warehouse.id) === String(form.destination_id)) || null)

const currentStock = computed(() => stockForWarehouse(form.source_id))
const maxQuantity = computed(() => Number(currentStock.value || 0))
const remainingSourceStock = computed(() => Math.max(maxQuantity.value - Number(form.qty || 0), 0))

const warehouseStockRows = computed(() => props.warehouses.map((warehouse) => ({
  ...warehouse,
  available_stock: stockForWarehouse(warehouse.id),
})))

const sourceWarehouses = computed(() => warehouseStockRows.value.filter((warehouse) => warehouse.available_stock > 0))
const destinationWarehouses = computed(() => props.warehouses.filter((warehouse) => String(warehouse.id) !== String(form.source_id)))

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.internal_code || item.code ? ` (${item.internal_code || item.code})` : ''}${hasStockInAnyWarehouse(item) ? '' : ' - Sem existências disponíveis'}`,
  disabled: !hasStockInAnyWarehouse(item),
})))

const sourceWarehouseOptions = computed(() => sourceWarehouses.value.map((warehouse) => ({
  value: warehouse.id,
  label: `${warehouse.name} (Disponível: ${warehouse.available_stock})`,
})))

const destinationWarehouseOptions = computed(() => destinationWarehouses.value.map((warehouse) => ({
  value: warehouse.id,
  label: warehouse.location?.name ? `${warehouse.name} (${warehouse.location.name})` : warehouse.name,
})))

const isFormValid = computed(() => (
  Boolean(form.item_id)
  && Boolean(form.source_id)
  && Boolean(form.destination_id)
  && String(form.destination_id) !== String(form.source_id)
  && Number(form.qty) > 0
  && Number(form.qty) <= maxQuantity.value
))

/** The one thing still missing before the transfer can be created. */
const nextStep = computed(() => {
  if (!form.item_id) return 'Seleccione o item a movimentar.'
  if (!form.source_id) return 'Escolha o armazém de origem com saldo disponível.'
  if (!form.destination_id || String(form.destination_id) === String(form.source_id)) return 'Escolha um destino diferente da origem.'
  if (!(Number(form.qty) > 0) || Number(form.qty) > maxQuantity.value) return `Indique uma quantidade entre 0,0001 e ${maxQuantity.value}.`
  return `${form.qty} de ${selectedItem.value?.name} de ${sourceWarehouse.value?.name} para ${destinationWarehouse.value?.name}. A reserva é aplicada ao criar.`
})

function stockForWarehouse(warehouseId) {
  if (!form.item_id || !warehouseId) return 0
  return Number(stockInfo.value[`${form.item_id}_${warehouseId}`] || 0)
}

function hasStockInAnyWarehouse(item) {
  return props.warehouses.some((warehouse) => Number(stockInfo.value[`${item.id}_${warehouse.id}`] || 0) > 0)
}

function itemOptionFor(itemId) {
  const item = props.items.find((candidate) => String(candidate.id) === String(itemId))
  if (!item) return null
  return {
    value: item.id,
    label: `${item.name}${item.internal_code || item.code ? ` (${item.internal_code || item.code})` : ''}`,
  }
}

function warehouseOptionFor(warehouseId) {
  const warehouse = props.warehouses.find((candidate) => String(candidate.id) === String(warehouseId))
  if (!warehouse) return null
  return {
    value: warehouse.id,
    label: warehouse.location?.name ? `${warehouse.name} (${warehouse.location.name})` : warehouse.name,
  }
}

async function fetchRealTimeStock() {
  if (!form.item_id) return

  loadingStock.value = true
  stockRefreshError.value = ''

  try {
    const response = await fetch(route('vap-inventory.transfers.item-stock-all', { item_id: form.item_id }), {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('O pedido de existências falhou')
    }

    const data = await response.json()
    const updates = {}
    props.warehouses.forEach((warehouse) => {
      updates[`${form.item_id}_${warehouse.id}`] = Number(data.stocks?.[warehouse.id] || 0)
    })
    stockInfo.value = { ...stockInfo.value, ...updates }
  } catch (error) {
    stockRefreshError.value = 'Não foi possível actualizar as existências neste momento.'
  } finally {
    loadingStock.value = false
  }
}

function submit() {
  if (!isFormValid.value) return

  form.transform((data) => ({
    ...data,
    qty: Number(data.qty),
  })).post(route('vap-inventory.transfers.store'), {
    preserveScroll: true,
  })
}

watch(selectedItemOption, async (option) => {
  const nextItemId = option?.value || ''
  if (String(nextItemId) === String(form.item_id)) return

  form.item_id = nextItemId
  form.source_id = ''
  form.destination_id = ''
  form.qty = 1
  selectedSourceWarehouseOption.value = null
  selectedDestinationWarehouseOption.value = null
  form.clearErrors('item_id', 'source_id', 'destination_id', 'qty')

  if (form.item_id) {
    await fetchRealTimeStock()
  }
})

watch(selectedSourceWarehouseOption, (option) => {
  form.source_id = option?.value || ''
  form.clearErrors('source_id')

  if (String(selectedDestinationWarehouseOption.value?.value || '') === String(form.source_id)) {
    selectedDestinationWarehouseOption.value = null
  }

  if (Number(form.qty) > maxQuantity.value) {
    form.qty = Math.max(maxQuantity.value, 1)
  }
})

watch(selectedDestinationWarehouseOption, (option) => {
  form.destination_id = option?.value || ''
  form.clearErrors('destination_id')
})

onMounted(() => {
  if (props.defaultItem) {
    fetchRealTimeStock()
  }
})

</script>

<template>
  <form class="pl-page" data-template="form" @submit.prevent="submit">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Transferências', url: route('vap-inventory.transfers.index') }, { title: 'Nova' }]"
      title="Nova transferência"
      lede="Reserve existências na origem, indique o destino e o prazo de recepção numa única operação rastreável."
    />

    <p v-if="form.hasErrors" class="ds-field-error mb-6" role="alert">Reveja os campos assinalados antes de criar a transferência.</p>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Item e rota</h2>
        <p>Só armazéns com saldo do item podem ser origem. O destino tem de ser diferente da origem. Todos os campos desta secção são obrigatórios.</p>
      </header>
      <div class="pl-form-grid">
        <div class="ds-field-group pl-span-2">
          <comboboxEnhanced
            v-model="selectedItemOption"
            title-label="Item"
            :has-error="Boolean(form.errors.item_id)"
            :options="itemOptions"
            placeholder="Pesquisar item com existências disponíveis"
          />
          <p v-if="form.errors.item_id" class="ds-field-error" role="alert">{{ form.errors.item_id }}</p>
        </div>

        <div class="ds-field-group">
          <comboboxEnhanced
            v-model="selectedSourceWarehouseOption"
            title-label="Armazém de origem"
            :disabled="!form.item_id || loadingStock"
            :has-error="Boolean(form.errors.source_id)"
            :options="sourceWarehouseOptions"
            placeholder="Seleccionar origem"
          />
          <p v-if="form.errors.source_id" class="ds-field-error" role="alert">{{ form.errors.source_id }}</p>
        </div>

        <div class="ds-field-group">
          <comboboxEnhanced
            v-model="selectedDestinationWarehouseOption"
            title-label="Armazém de destino"
            :disabled="!form.item_id"
            :has-error="Boolean(form.errors.destination_id)"
            :options="destinationWarehouseOptions"
            placeholder="Seleccionar destino"
          />
          <p v-if="form.errors.destination_id" class="ds-field-error" role="alert">{{ form.errors.destination_id }}</p>
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Quantidade e prazos</h2>
        <p>A quantidade não pode ultrapassar o saldo da origem. A data prevista alimenta o alerta de atraso na fila.</p>
      </header>
      <div class="pl-form-grid">
        <div class="ds-field-group">
          <label for="transfer-qty" class="ds-field-label">Quantidade <span class="ds-field-required">*</span></label>
          <BaseInput
            id="transfer-qty"
            v-model="form.qty"
            type="number"
            min="0.0001"
            step="0.0001"
            :max="maxQuantity"
            class="ds-field"
            :aria-invalid="Boolean(form.errors.qty)"
            required
          />
          <span class="ds-field-hint">Saldo na origem: <span class="pl-num">{{ maxQuantity }}</span> · fica <span class="pl-num">{{ remainingSourceStock }}</span> após a reserva.</span>
          <span v-if="form.errors.qty" class="ds-field-error" role="alert">{{ form.errors.qty }}</span>
        </div>

        <DateTimePicker v-model="form.sent_date" type="date" label="Data de envio" :min="minDate" :error="form.errors.sent_date" />

        <DateTimePicker v-model="form.expected_date" type="date" label="Recepção esperada" :min="form.sent_date || minDate" :error="form.errors.expected_date" />

        <div class="ds-field-group pl-span-2">
          <label for="transfer-obs" class="ds-field-label">Observações logísticas</label>
          <textarea
            id="transfer-obs"
            v-model="form.obs"
            rows="4"
            class="ds-field"
            :aria-invalid="Boolean(form.errors.obs)"
            placeholder="Condições de transporte, instruções de manuseamento ou cadeia de frio"
          ></textarea>
          <span v-if="form.errors.obs" class="ds-field-error" role="alert">{{ form.errors.obs }}</span>
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Existências do item</h2>
        <p>Saldo disponível do item em cada armazém do laboratório.</p>
      </header>
      <div class="pl-panel">
        <div class="pl-panel-head">
          <h3 class="pl-k">Por armazém</h3>
          <button type="button" class="ds-button ds-button-quiet" :disabled="!form.item_id || loadingStock" @click="fetchRealTimeStock">
            <ArrowPathIcon :class="['h-4 w-4', loadingStock ? 'animate-spin' : '']" aria-hidden="true" />
            Actualizar
          </button>
        </div>
        <DataTable v-if="selectedItem">
          <thead>
            <tr>
              <th scope="col">Armazém</th>
              <th scope="col">Localização</th>
              <th scope="col" class="text-right">Disponível</th>
              <th scope="col">Papel na rota</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="warehouse in warehouseStockRows" :key="warehouse.id">
              <td>{{ warehouse.name }}</td>
              <td class="text-[var(--pl-muted)]">{{ warehouse.location?.name || 'Sem localização' }}</td>
              <td class="pl-num text-right">{{ warehouse.available_stock }}</td>
              <td>
                <StatusChip v-if="String(warehouse.id) === String(form.source_id)" tone="run">Origem</StatusChip>
                <StatusChip v-else-if="String(warehouse.id) === String(form.destination_id)" tone="ok">Destino</StatusChip>
                <span v-else class="text-[var(--pl-faint)]">—</span>
              </td>
            </tr>
          </tbody>
        </DataTable>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Nenhum item seleccionado</span>
          <p class="text-sm text-[var(--pl-muted)]">Seleccione um item para consultar as existências por armazém.</p>
        </div>
        <p v-if="stockRefreshError" class="ds-field-error border-t border-[var(--pl-line)] px-4 py-3" role="alert">{{ stockRefreshError }}</p>
      </div>
    </section>

    <NextStepBar>
      {{ nextStep }}
      <template #actions>
        <Link :href="route('vap-inventory.transfers.index')" class="ds-button ds-button-quiet">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !isFormValid">
          {{ form.processing ? 'A criar…' : 'Criar transferência' }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
