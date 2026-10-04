<template>
  <form class="pl-page" data-template="form" @submit.prevent="submit">
    <PageHeader :crumbs="crumbs" :title="pageTitle" :lede="pageDescription">
      <template #badges><StatusChip :tone="statusTone(form.status)">{{ formatStatus(form.status) }}</StatusChip></template>
    </PageHeader>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Fornecedor e estado</h2>
        <p>Fornecedor avaliado, data, referência e estado de aprovação do pedido.</p>
      </header>
      <div class="pl-form-grid">
        <div class="ds-field-group">
          <comboboxEnhanced
            v-model="selectedSupplierOption"
            title-label="Fornecedor"
            :disable-input="!canEditSupplier"
            :has-error="Boolean(form.errors.supplier_id)"
            :options="supplierOptions"
            placeholder="Pesquisar fornecedor"
          />
          <p v-if="form.errors.supplier_id" class="ds-field-error" role="alert">{{ form.errors.supplier_id }}</p>
        </div>

        <DateTimePicker
          id="orderDate"
          v-model="form.date"
          type="date"
          label="Data do pedido"
          :max="maxDate"
          class="ds-field"
          :error="form.errors.date"
          required
        />

        <BaseInput
          id="reference"
          v-model="form.reference"
          type="text"
          label="Número de referência"
          class="ds-field"
          placeholder="Ex.: PC-2026-001"
          :error="form.errors.reference"
        />

        <div class="ds-field-group">
          <comboboxEnhanced
            v-model="selectedStatusOption"
            title-label="Estado"
            :disable-input="!canEditStatus"
            :has-error="Boolean(form.errors.status)"
            :options="statusOptions"
            placeholder="Seleccionar estado"
          />
          <p v-if="form.errors.status" class="ds-field-error" role="alert">{{ form.errors.status }}</p>
        </div>

        <div class="ds-field-group pl-span-2">
          <label for="observations" class="ds-field-label">Observações</label>
          <textarea
            id="observations"
            v-model="form.obs"
            rows="3"
            class="ds-field"
            placeholder="Instruções especiais, condições de entrega ou notas internas…"
            :aria-invalid="Boolean(form.errors.obs)"
            :aria-describedby="form.errors.obs ? 'observations-error' : undefined"
          />
          <p v-if="form.errors.obs" id="observations-error" class="ds-field-error" role="alert">{{ form.errors.obs }}</p>
        </div>

        <div v-if="selectedSupplierAssessment" class="pl-banner pl-span-2 text-sm" :class="supplierAssessmentBannerClass">
          <div class="grid gap-1">
            <span class="pl-k">Avaliação activa do fornecedor</span>
            <span>{{ supplierAssessmentStatus }} · {{ supplierAssessmentRisk }} · Score {{ selectedSupplierAssessment.total_score }}/100 · Revisão {{ supplierAssessmentReviewLabel }}</span>
          </div>
        </div>
        <div v-else-if="form.supplier_id" class="pl-banner pl-banner-warn pl-span-2 text-sm">
          <div class="grid gap-1">
            <span class="pl-k">Fornecedor sem avaliação formal registada</span>
            <span>Confirme a aprovação do fornecedor antes de avançar o pedido.</span>
          </div>
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Linhas do pedido</h2>
        <p>Item, quantidade (até quatro casas decimais), preço unitário, armazém de destino e data prevista. Linhas já recebidas não podem ser removidas nem descer abaixo do recebido.</p>
      </header>
      <div class="grid min-w-0 gap-5">
        <div v-if="form.order_items.length === 0" class="ds-empty-state grid justify-items-start gap-2 p-6">
          <span class="pl-k">Nenhuma linha adicionada</span>
          <p class="text-sm text-[var(--pl-muted)]">Adicione itens para criar ou actualizar o pedido de compra.</p>
        </div>

        <article v-for="(item, index) in form.order_items" :key="item.id || index" class="pl-panel" :aria-labelledby="`order-line-title-${index}`">
          <div class="pl-panel-head">
            <h3 :id="`order-line-title-${index}`" class="pl-k min-w-0 truncate">Linha {{ index + 1 }} · {{ getSelectedItemDetails(item.item_id)?.name || 'item por seleccionar' }}</h3>
            <div class="flex items-center gap-3">
              <span class="pl-num text-sm">{{ formatCurrency(lineTotal(item)) }}</span>
              <button
                v-if="canEditItems && Number(item.received_qty || 0) === 0"
                type="button"
                class="ds-table-action ds-table-action-danger"
                :aria-label="`Remover linha ${index + 1}`"
                @click="removeOrderItem(index)"
              >
                <TrashIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
          </div>

          <div class="pl-form-grid p-4">
            <div class="ds-field-group pl-span-2">
              <comboboxEnhanced
                v-model="item.item_obj"
                title-label="Item"
                :disable-input="!canEditItems || Number(item.received_qty || 0) > 0"
                :has-error="Boolean(itemErrors[index]?.item_id)"
                :options="itemOptions"
                placeholder="Pesquisar item"
                @update:modelValue="onItemChange(index)"
              />
              <p v-if="itemErrors[index]?.item_id" class="ds-field-error" role="alert">{{ itemErrors[index].item_id }}</p>
              <p v-if="Number(item.received_qty || 0) > 0" class="text-[12.5px] text-[var(--pl-muted)]">
                Recebido: <span class="pl-num">{{ formatQuantity(item.received_qty) }}</span>
              </p>
            </div>

            <BaseInput
              :id="`order-line-qty-${index}`"
              v-model="item.qty"
              type="number"
              label="Quantidade"
              :min="Math.max(0.0001, Number(item.received_qty || 0))"
              step="0.0001"
              class="ds-field"
              :disabled="!canEditItems"
              placeholder="1"
              :error="itemErrors[index]?.qty"
              required
              @input="validateItemQuantity(index)"
            />

            <BaseInput
              :id="`order-line-price-${index}`"
              v-model.number="item.unit_price"
              type="number"
              label="Preço unitário"
              min="0"
              step="0.01"
              class="ds-field"
              :disabled="!canEditItems"
              placeholder="0.00"
              :error="itemErrors[index]?.unit_price"
              required
            />

            <div class="ds-field-group">
              <comboboxEnhanced
                v-model="item.warehouse_obj"
                title-label="Armazém"
                :disable-input="!canEditItems"
                :has-error="Boolean(itemErrors[index]?.warehouse_id)"
                :options="warehouseOptions"
                placeholder="Pesquisar armazém"
                @update:modelValue="onWarehouseChange(index)"
              />
              <p v-if="itemErrors[index]?.warehouse_id" class="ds-field-error" role="alert">{{ itemErrors[index].warehouse_id }}</p>
            </div>

            <DateTimePicker
              :id="`order-line-expected-${index}`"
              v-model="item.expected_date"
              type="date"
              label="Data prevista"
              :min="form.date || minDate"
              class="ds-field"
              :disabled="!canEditItems"
              :error="itemErrors[index]?.expected_date"
            />

            <p v-if="getSelectedItemDetails(item.item_id)" class="pl-span-2 text-[12.5px] text-[var(--pl-muted)]">
              Código <span class="pl-num">{{ getSelectedItemDetails(item.item_id).internal_code || getSelectedItemDetails(item.item_id).code || '—' }}</span>
              · {{ getSelectedItemDetails(item.item_id).category?.name || 'Sem categoria' }}
              · unidade {{ getSelectedItemDetails(item.item_id).unit?.code || 'por definir' }}
              · existências no armazém <span class="pl-num">{{ getCurrentStock(item.item_id, item.warehouse_id) }}</span>
            </p>
          </div>
        </article>

        <div v-if="canEditItems">
          <button type="button" class="ds-button ds-button-secondary" @click="addOrderItem">
            <PlusIcon class="h-4 w-4" aria-hidden="true" />
            {{ form.order_items.length ? 'Adicionar item' : 'Adicionar primeiro item' }}
          </button>
        </div>

        <dl class="pl-panel pl-facts pl-facts-2">
          <div v-for="fact in orderTotals" :key="fact.label" class="pl-fact"><dt>{{ fact.label }}</dt><dd class="pl-num">{{ fact.value }}</dd></div>
        </dl>
      </div>
    </section>

    <NextStepBar>
      <template v-if="isFormValid">{{ formatQuantity(form.order_items.length) }} {{ form.order_items.length === 1 ? 'linha' : 'linhas' }} · {{ formatCurrency(totalAmount) }} a {{ selectedSupplier?.name || 'fornecedor' }}. {{ mode === 'create' ? 'Gravar cria o pedido no estado escolhido.' : 'Gravar actualiza o pedido.' }}</template>
      <template v-else>Seleccione fornecedor, data e pelo menos uma linha válida para gravar.</template>
      <template #actions>
        <button type="button" class="ds-button ds-button-quiet" @click="goBack">Cancelar</button>
        <button v-if="mode === 'create'" type="button" class="ds-button ds-button-quiet" :disabled="form.processing" @click="saveAsDraft">Guardar como rascunho</button>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !isFormValid">{{ submitLabel }}</button>
      </template>
    </NextStepBar>
  </form>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { router, useForm } from '@inertiajs/vue3'
import { Plus as PlusIcon, Trash2 as TrashIcon } from '@lucide/vue'
import { computed, onMounted, ref, watch } from 'vue'

/**
 * The purchase order form shared by Orders/Create and Orders/Edit (Plano form). Lines
 * already received stay locked: their item cannot change, they cannot be removed and
 * their quantity cannot drop below what was received.
 */

const props = defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ['create', 'edit'].includes(value),
  },
  order: {
    type: Object,
    default: null,
  },
  items: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
})

const today = new Date().toISOString().split('T')[0]
const minDate = today
const maxDate = today

const form = useForm({
  supplier_id: props.order?.supplier_id || '',
  date: props.order?.date ? toInputDate(props.order.date) : today,
  reference: props.order?.reference || '',
  status: props.order?.status || 'PENDING',
  obs: props.order?.obs || '',
  order_items: props.order?.items ? props.order.items.map((item) => ({
    id: item.id,
    item_obj: item.item ? {
      value: item.item_id,
      label: itemLabel(item.item),
    } : null,
    item_id: item.item_id,
    qty: item.qty,
    unit_price: item.unit_price,
    warehouse_obj: item.warehouse ? {
      value: item.warehouse_id,
      label: item.warehouse.name,
    } : null,
    warehouse_id: item.warehouse_id,
    expected_date: item.expected_date ? toInputDate(item.expected_date) : '',
    received_qty: item.received_qty || 0,
    status: item.status || 'PENDING',
  })) : [],
})

const itemErrors = ref([])
const selectedSupplierOption = ref(null)
const selectedStatusOption = ref(null)

const pageTitle = computed(() => (props.mode === 'create' ? 'Criar pedido de compra' : `Modificar pedido #${props.order?.seq || props.order?.id}`))
const pageDescription = computed(() => (
  props.mode === 'create'
    ? 'Registe pedidos de compra com fornecedor avaliado, itens de inventário, destino de armazém e datas previstas de recepção.'
    : 'Actualize fornecedor, estado, itens e armazéns de destino preservando bloqueios de linhas já recebidas.'
))
const crumbs = computed(() => [
  { title: 'Inventário' },
  { title: 'Pedidos de compra', url: route('vap-inventory.orders.index') },
  ...(props.mode === 'edit'
    ? [{ title: `Pedido #${props.order?.seq || props.order?.id}`, url: route('vap-inventory.orders.show', props.order.id) }, { title: 'Modificar' }]
    : [{ title: 'Novo' }]),
])
const submitLabel = computed(() => {
  if (form.processing) {
    return 'A gravar…'
  }

  return props.mode === 'create' ? 'Criar pedido' : 'Actualizar pedido'
})

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
  value: supplier.id,
  label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
})))

const statusOptions = [
  { value: 'PENDING', label: 'Pendente' },
  { value: 'APPROVED', label: 'Aprovado' },
  { value: 'ORDERED', label: 'Encomendado' },
  { value: 'CANCELLED', label: 'Cancelado' },
]

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: itemLabel(item),
})))

const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
  value: warehouse.id,
  label: warehouse.location?.name ? `${warehouse.name} (${warehouse.location.name})` : warehouse.name,
})))

watch(selectedSupplierOption, (supplier) => {
  form.supplier_id = supplier?.value || ''
})

watch(selectedStatusOption, (status) => {
  form.status = status?.value || 'PENDING'
})

const selectedSupplier = computed(() => props.suppliers.find((supplier) => supplier.id == form.supplier_id))
const selectedSupplierAssessment = computed(() => selectedSupplier.value?.latest_assessment ?? null)

const supplierAssessmentStatus = computed(() => ({
  approved: 'Aprovado',
  conditional: 'Condicional',
  suspended: 'Suspenso',
  rejected: 'Rejeitado',
}[selectedSupplierAssessment.value?.status] || 'Sem estado'))

const supplierAssessmentRisk = computed(() => ({
  low: 'Risco baixo',
  medium: 'Risco médio',
  high: 'Risco elevado',
  critical: 'Risco crítico',
}[selectedSupplierAssessment.value?.risk_level] || 'Risco não definido'))

const supplierAssessmentReviewLabel = computed(() => {
  if (!selectedSupplierAssessment.value?.next_review_at) {
    return 'sem data'
  }

  return formatDate(selectedSupplierAssessment.value.next_review_at)
})

const supplierAssessmentBannerClass = computed(() => {
  const assessment = selectedSupplierAssessment.value

  if (!assessment) {
    return 'pl-banner-warn'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'pl-banner-bad'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'pl-banner-warn'
  }

  return 'pl-banner-ok'
})

const canEditSupplier = computed(() => props.mode === 'create' || ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order?.status)))
const canEditStatus = computed(() => props.mode === 'create' || ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order?.status)))
const canEditItems = computed(() => props.mode === 'create' || ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order?.status)))

const totalAmount = computed(() => form.order_items.reduce((total, item) => total + lineTotal(item), 0))

const earliestExpectedDate = computed(() => {
  const dates = form.order_items
    .map((item) => item.expected_date)
    .filter(Boolean)
    .sort()

  return dates[0] || null
})

const uniqueWarehouses = computed(() => {
  const warehouseIds = form.order_items
    .map((item) => item.warehouse_id)
    .filter(Boolean)

  return new Set(warehouseIds).size
})

const orderTotals = computed(() => [
  { label: 'Linhas com quantidade válida', value: `${form.order_items.filter((item) => isValidQuantity(item.qty)).length}/${form.order_items.length}` },
  { label: 'Valor estimado', value: formatCurrency(totalAmount.value) },
  { label: 'Armazéns de destino', value: formatQuantity(uniqueWarehouses.value) },
  { label: 'Primeira entrega', value: earliestExpectedDate.value ? formatDate(earliestExpectedDate.value) : '—' },
])

const isFormValid = computed(() => (
  Boolean(form.supplier_id)
  && Boolean(form.date)
  && form.order_items.length > 0
  && form.order_items.every((item) => (
    item.item_id
    && isValidQuantity(item.qty)
    && item.warehouse_id
    && Number(item.qty || 0) >= Number(item.received_qty || 0)
    && Number(item.unit_price || 0) >= 0
  ))
  && !form.processing
))

function itemLabel(item) {
  return `${item.name}${item.internal_code || item.code ? ` (${item.internal_code || item.code})` : ''}${item.category?.name ? ` - ${item.category.name}` : ''}`
}

function toInputDate(dateString) {
  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return today
  }

  return date.toISOString().split('T')[0]
}

function normalizeStatus(status) {
  return String(status || '').toUpperCase()
}

function formatStatus(status) {
  const statusMap = {
    PENDING: 'Pendente',
    APPROVED: 'Aprovado',
    ORDERED: 'Encomendado',
    PARTIALLY_RECEIVED: 'Recebido parcialmente',
    RECEIVED: 'Recebido',
    CANCELLED: 'Cancelado',
  }

  return statusMap[normalizeStatus(status)] || status || 'Sem estado'
}

function statusTone(status) {
  return {
    PENDING: 'wait',
    APPROVED: 'ok',
    ORDERED: 'run',
    PARTIALLY_RECEIVED: 'run',
    RECEIVED: 'done',
    CANCELLED: 'bad',
  }[normalizeStatus(status)] || 'neutral'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: selectedSupplier.value?.currency || 'AOA',
  }).format(Number(value || 0))
}

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 4 }).format(numericValue)
}

function formatDate(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  }).format(date)
}

function lineTotal(item) {
  return Number(item.unit_price || 0) * Number(item.qty || 0)
}

function isValidQuantity(value) {
  return /^(?:0|[1-9]\d*)(?:\.\d{1,4})?$/.test(String(value)) && Number(value) >= 0.0001
}

function getSelectedItemDetails(itemId) {
  return props.items.find((item) => item.id == itemId)
}

function getCurrentStock(itemId, warehouseId) {
  if (!itemId || !warehouseId) {
    return 'N/A'
  }

  const item = getSelectedItemDetails(itemId)

  if (!item || !Array.isArray(item.inventory)) {
    return 'N/A'
  }

  const inventory = item.inventory.find((entry) => entry.warehouse_id == warehouseId)

  return inventory ? inventory.qty_available : 0
}

function addOrderItem() {
  form.order_items.push({
    item_obj: null,
    item_id: '',
    qty: 1,
    warehouse_obj: null,
    warehouse_id: '',
    expected_date: '',
    status: 'PENDING',
    unit_price: 0,
    total_price: 0,
    received_qty: 0,
  })
  itemErrors.value.push({})
}

function removeOrderItem(index) {
  form.order_items.splice(index, 1)
  itemErrors.value.splice(index, 1)
}

function onItemChange(index) {
  const orderItem = form.order_items[index]
  orderItem.item_id = orderItem.item_obj?.value || ''

  const selectedItem = getSelectedItemDetails(orderItem.item_id)

  if (selectedItem && Number(orderItem.unit_price || 0) === 0) {
    orderItem.unit_price = selectedItem.last_purchase_price || selectedItem.standard_cost || 0
  }
}

function onWarehouseChange(index) {
  const orderItem = form.order_items[index]
  orderItem.warehouse_id = orderItem.warehouse_obj?.value || ''
}

function validateItemQuantity(index) {
  const orderItem = form.order_items[index]
  const errors = {}

  if (!orderItem.item_id) {
    errors.item_id = 'Seleccione o item.'
  }

  if (!isValidQuantity(orderItem.qty)) {
    errors.qty = 'Introduza uma quantidade positiva com até quatro casas decimais.'
  } else if (Number(orderItem.qty) < Number(orderItem.received_qty || 0)) {
    errors.qty = `Quantidade não pode ser menor que a já recebida (${orderItem.received_qty}).`
  }

  if (!orderItem.warehouse_id) {
    errors.warehouse_id = 'Seleccione o armazém.'
  }

  if (Number(orderItem.unit_price || 0) < 0) {
    errors.unit_price = 'Preço unitário não pode ser negativo.'
  }

  itemErrors.value[index] = errors
}

function validateItems() {
  let isValid = true

  form.order_items.forEach((item, index) => {
    validateItemQuantity(index)

    if (Object.keys(itemErrors.value[index] || {}).length > 0) {
      isValid = false
    }
  })

  return isValid
}

function submit() {
  if (!validateItems()) {
    return
  }

  form.transform(payload)

  if (props.mode === 'create') {
    form.post(route('vap-inventory.orders.store'), { preserveScroll: true })

    return
  }

  form.put(route('vap-inventory.orders.update', props.order.id), { preserveScroll: true })
}

function saveAsDraft() {
  form.status = 'PENDING'
  selectedStatusOption.value = statusOptions.find((status) => status.value === 'PENDING') || statusOptions[0]
  submit()
}

function payload(data) {
  return {
    ...data,
    order_items: data.order_items.map(({ item_obj, warehouse_obj, ...orderItem }) => ({
      ...orderItem,
      total_price: lineTotal(orderItem),
    })),
  }
}

function goBack() {
  router.visit(props.mode === 'create'
    ? route('vap-inventory.orders.index')
    : route('vap-inventory.orders.show', props.order.id))
}

onMounted(() => {
  const supplier = props.suppliers.find((supplierItem) => supplierItem.id == form.supplier_id)

  if (supplier) {
    selectedSupplierOption.value = {
      value: supplier.id,
      label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
    }
  }

  selectedStatusOption.value = statusOptions.find((status) => status.value === normalizeStatus(form.status)) || statusOptions[0]

  if (form.order_items.length === 0 && props.mode === 'create') {
    addOrderItem()
  }

  itemErrors.value = Array.from({ length: form.order_items.length }, () => ({}))
})
</script>
