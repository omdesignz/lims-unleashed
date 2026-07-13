<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--ds-text-soft)]">
            Nova movimentação interna
          </p>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <TruckIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Criar transferência</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Reserve stock na origem, defina o destino e registe o prazo de receção numa única operação rastreável.
              </p>
            </div>
          </div>
        </div>

        <button type="button" class="ds-button ds-button-secondary" @click="goBack">
          <ArrowLeftIcon class="h-4 w-4" />
          Voltar à fila
        </button>
      </div>
    </section>

    <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]" @submit.prevent="submit">
      <div class="space-y-6">
        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Etapa 1</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Item e rota de armazéns</h2>
          </div>

          <div class="grid gap-6 p-5 xl:grid-cols-[15rem_minmax(0,1fr)]">
            <div>
              <div class="flex items-center gap-2 text-[var(--ds-text)]">
                <CubeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
                <h3 class="font-black">Reserva na origem</h3>
              </div>
              <p class="mt-2 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Apenas armazéns com saldo disponível podem ser selecionados como origem. O destino deve ser diferente.
              </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div class="ds-field-group md:col-span-2">
                <label class="ds-field-label">Item <span class="ds-field-required">*</span></label>
                <comboboxEnhanced
                  v-model="selectedItemOption"
                  :has-error="Boolean(form.errors.item_id)"
                  :options="itemOptions"
                  placeholder="Pesquisar item com stock disponível"
                />
                <p v-if="form.errors.item_id" class="ds-field-error">{{ form.errors.item_id }}</p>
              </div>

              <div class="ds-field-group">
                <label class="ds-field-label">Armazém de origem <span class="ds-field-required">*</span></label>
                <comboboxEnhanced
                  v-model="selectedSourceWarehouseOption"
                  :disabled="!form.item_id || loadingStock"
                  :has-error="Boolean(form.errors.source_id)"
                  :options="sourceWarehouseOptions"
                  placeholder="Selecionar origem"
                />
                <p v-if="form.errors.source_id" class="ds-field-error">{{ form.errors.source_id }}</p>
              </div>

              <div class="ds-field-group">
                <label class="ds-field-label">Armazém de destino <span class="ds-field-required">*</span></label>
                <comboboxEnhanced
                  v-model="selectedDestinationWarehouseOption"
                  :disabled="!form.item_id"
                  :has-error="Boolean(form.errors.destination_id)"
                  :options="destinationWarehouseOptions"
                  placeholder="Selecionar destino"
                />
                <p v-if="form.errors.destination_id" class="ds-field-error">{{ form.errors.destination_id }}</p>
              </div>
            </div>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Etapa 2</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Quantidade e janela logística</h2>
          </div>

          <div class="grid gap-6 p-5 xl:grid-cols-[15rem_minmax(0,1fr)]">
            <div>
              <div class="flex items-center gap-2 text-[var(--ds-text)]">
                <CalendarDaysIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                <h3 class="font-black">Despacho planeado</h3>
              </div>
              <p class="mt-2 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                A quantidade não pode ultrapassar o saldo disponível. A data prevista sustenta alertas de atraso na fila.
              </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <label class="ds-field-group">
                <span class="ds-field-label">Quantidade <span class="ds-field-required">*</span></span>
                <span class="relative block">
                  <input
                    v-model="form.qty"
                    type="number"
                    min="1"
                    :max="maxQuantity"
                    class="ds-field pr-20"
                    :aria-invalid="Boolean(form.errors.qty)"
                    required
                  />
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-black text-[var(--ds-text-soft)]">
                    / {{ maxQuantity }}
                  </span>
                </span>
                <span class="ds-field-hint">Saldo disponível no armazém de origem.</span>
                <span v-if="form.errors.qty" class="ds-field-error">{{ form.errors.qty }}</span>
              </label>

              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Saldo após reserva</p>
                <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">{{ remainingSourceStock }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Unidades remanescentes na origem</p>
              </div>

              <label class="ds-field-group">
                <span class="ds-field-label">Data de envio</span>
                <input v-model="form.sent_date" type="date" :min="minDate" class="ds-field" :aria-invalid="Boolean(form.errors.sent_date)" />
                <span v-if="form.errors.sent_date" class="ds-field-error">{{ form.errors.sent_date }}</span>
              </label>

              <label class="ds-field-group">
                <span class="ds-field-label">Receção esperada</span>
                <input v-model="form.expected_date" type="date" :min="form.sent_date || minDate" class="ds-field" :aria-invalid="Boolean(form.errors.expected_date)" />
                <span v-if="form.errors.expected_date" class="ds-field-error">{{ form.errors.expected_date }}</span>
              </label>

              <label class="ds-field-group md:col-span-2">
                <span class="ds-field-label">Observações logísticas</span>
                <textarea
                  v-model="form.obs"
                  rows="4"
                  class="ds-field"
                  :aria-invalid="Boolean(form.errors.obs)"
                  placeholder="Condições de transporte, instruções de manuseamento ou cadeia de frio"
                ></textarea>
                <span v-if="form.errors.obs" class="ds-field-error">{{ form.errors.obs }}</span>
              </label>
            </div>
          </div>
        </section>

        <section class="ds-table-shell">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Disponibilidade</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Stock do item por armazém</h2>
            </div>
            <button type="button" class="ds-button ds-button-secondary" :disabled="!form.item_id || loadingStock" @click="fetchRealTimeStock">
              <ArrowPathIcon :class="['h-4 w-4', loadingStock ? 'animate-spin' : '']" />
              Atualizar
            </button>
          </div>

          <div v-if="selectedItem" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
              <thead class="bg-[var(--ds-panel-subtle)]">
                <tr>
                  <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                  <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Localização</th>
                  <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Disponível</th>
                  <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Papel na rota</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
                <tr v-for="warehouse in warehouseStockRows" :key="warehouse.id">
                  <td class="px-5 py-3 font-black text-[var(--ds-text)]">{{ warehouse.name }}</td>
                  <td class="px-5 py-3 font-semibold text-[var(--ds-text-muted)]">{{ warehouse.location?.name || 'Sem localização' }}</td>
                  <td class="px-5 py-3 text-right text-lg font-black text-[var(--ds-text)]">{{ warehouse.available_stock }}</td>
                  <td class="px-5 py-3 text-right">
                    <span v-if="String(warehouse.id) === String(form.source_id)" class="inline-flex items-center gap-2 text-xs font-black text-rose-700 dark:text-rose-300">
                      <span class="h-2 w-2 rounded-full bg-rose-600"></span> Origem
                    </span>
                    <span v-else-if="String(warehouse.id) === String(form.destination_id)" class="inline-flex items-center gap-2 text-xs font-black text-emerald-700 dark:text-emerald-300">
                      <span class="h-2 w-2 rounded-full bg-emerald-600"></span> Destino
                    </span>
                    <span v-else class="text-xs font-semibold text-[var(--ds-text-soft)]">Disponível</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="ds-empty-state p-8 text-center">
            <CubeIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-black text-[var(--ds-text)]">Selecione um item para consultar o stock distribuído.</p>
          </div>

          <div v-if="stockRefreshError" class="border-t border-[var(--ds-border)] px-5 py-3 text-sm font-bold text-rose-700 dark:text-rose-300">
            {{ stockRefreshError }}
          </div>
        </section>
      </div>

      <aside>
        <section class="ds-command-surface p-5 xl:sticky xl:top-20">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Resumo da transferência</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Prontidão operacional</h2>

          <ol class="mt-5 space-y-0">
            <li v-for="(step, index) in readinessSteps" :key="step.label" class="relative flex gap-3 pb-6 last:pb-0">
              <span v-if="index < readinessSteps.length - 1" class="absolute left-[0.4375rem] top-4 h-[calc(100%-0.5rem)] w-px bg-[var(--ds-border)]"></span>
              <span :class="['relative mt-1 h-4 w-4 shrink-0 rounded-full border-4 border-[var(--ds-panel-raised)]', step.complete ? 'bg-emerald-600' : 'bg-[var(--ds-border-strong)]']"></span>
              <div>
                <p class="text-sm font-black text-[var(--ds-text)]">{{ step.label }}</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ step.detail }}</p>
              </div>
            </li>
          </ol>

          <dl class="mt-6 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-start justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Item</dt>
              <dd class="max-w-[11rem] text-right text-sm font-black text-[var(--ds-text)]">{{ selectedItem?.name || 'Por selecionar' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Origem</dt>
              <dd class="max-w-[11rem] text-right text-sm font-black text-[var(--ds-text)]">{{ sourceWarehouse?.name || 'Por selecionar' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Destino</dt>
              <dd class="max-w-[11rem] text-right text-sm font-black text-[var(--ds-text)]">{{ destinationWarehouse?.name || 'Por selecionar' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Quantidade</dt>
              <dd class="text-right text-sm font-black text-[var(--ds-text)]">{{ Number(form.qty || 0) }} / {{ maxQuantity }}</dd>
            </div>
          </dl>

          <div v-if="form.hasErrors" class="mt-5 rounded-lg border border-rose-300/60 bg-rose-50 p-3 text-sm font-bold text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200">
            Reveja os campos assinalados antes de criar a transferência.
          </div>

          <button type="submit" class="ds-button ds-button-primary mt-5 w-full" :disabled="form.processing || !isFormValid">
            <PaperAirplaneIcon class="h-4 w-4" />
            {{ form.processing ? 'A criar...' : 'Criar transferência' }}
          </button>
          <button type="button" class="ds-button ds-button-secondary mt-2 w-full" @click="goBack">
            Cancelar
          </button>
        </section>
      </aside>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  CalendarDaysIcon,
  CubeIcon,
  PaperAirplaneIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'

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
  label: `${item.name}${item.internal_code || item.code ? ` (${item.internal_code || item.code})` : ''}${hasStockInAnyWarehouse(item) ? '' : ' - Sem stock disponível'}`,
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

const readinessSteps = computed(() => [
  {
    label: 'Item identificado',
    detail: selectedItem.value?.name || 'Selecione o material a movimentar.',
    complete: Boolean(form.item_id),
  },
  {
    label: 'Rota válida',
    detail: sourceWarehouse.value && destinationWarehouse.value
      ? `${sourceWarehouse.value.name} → ${destinationWarehouse.value.name}`
      : 'Defina origem e destino diferentes.',
    complete: Boolean(form.source_id && form.destination_id && String(form.source_id) !== String(form.destination_id)),
  },
  {
    label: 'Quantidade disponível',
    detail: Number(form.qty) > 0 ? `${form.qty} de ${maxQuantity.value} unidades` : 'Informe a quantidade.',
    complete: Number(form.qty) > 0 && Number(form.qty) <= maxQuantity.value,
  },
  {
    label: 'Pronta para registo',
    detail: isFormValid.value ? 'A reserva será aplicada ao confirmar.' : 'Complete os campos obrigatórios.',
    complete: isFormValid.value,
  },
])

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
      throw new Error('Stock request failed')
    }

    const data = await response.json()
    const updates = {}
    props.warehouses.forEach((warehouse) => {
      updates[`${form.item_id}_${warehouse.id}`] = Number(data.stocks?.[warehouse.id] || 0)
    })
    stockInfo.value = { ...stockInfo.value, ...updates }
  } catch (error) {
    stockRefreshError.value = 'Não foi possível atualizar o stock neste momento.'
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

function goBack() {
  router.visit(route('vap-inventory.transfers.index'))
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
