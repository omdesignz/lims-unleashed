<template>
  <form class="min-w-0 space-y-6 overflow-x-clip" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Procurement workflow</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="statusDotClass(form.status)" />
              {{ formatStatus(form.status) }}
            </span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument" />
              {{ mode === 'create' ? 'Novo pedido' : 'Revisão controlada' }}
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">{{ pageTitle }}</h1>
          <p class="ds-copy mt-2 text-sm">{{ pageDescription }}</p>
        </div>

        <button type="button" class="ds-button ds-button-secondary shrink-0" @click="goBack">
          <ArrowLeftIcon class="h-4 w-4" />
          Voltar
        </button>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in summaryCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1fr_22rem]">
      <div class="space-y-4">
        <article class="ds-command-surface overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <div class="flex items-start gap-3">
              <ClipboardDocumentListIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Dados do pedido</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Fornecedor, datas, referência e estado de aprovação.</p>
              </div>
            </div>
          </div>

          <div class="grid gap-5 p-5 lg:grid-cols-2">
            <div>
              <label class="ds-field-label">Fornecedor <span class="text-rose-600">*</span></label>
              <comboboxEnhanced
                v-model="selectedSupplierOption"
                :disabled="!canEditSupplier"
                :has-error="form.errors.supplier_id"
                :options="supplierOptions"
                placeholder="Pesquisar fornecedor"
              />
              <p v-if="form.errors.supplier_id" class="ds-field-error mt-1">{{ form.errors.supplier_id }}</p>
            </div>

            <div>
              <label for="orderDate" class="ds-field-label">Data do pedido <span class="text-rose-600">*</span></label>
              <input
                id="orderDate"
                v-model="form.date"
                type="date"
                :max="maxDate"
                class="ds-field"
                :aria-invalid="Boolean(form.errors.date)"
                required
              />
              <p v-if="form.errors.date" class="ds-field-error mt-1">{{ form.errors.date }}</p>
            </div>

            <div>
              <label for="reference" class="ds-field-label">Número de referência</label>
              <input
                id="reference"
                v-model="form.reference"
                type="text"
                class="ds-field"
                placeholder="Ex.: PC-2026-001"
                :aria-invalid="Boolean(form.errors.reference)"
              />
              <p v-if="form.errors.reference" class="ds-field-error mt-1">{{ form.errors.reference }}</p>
            </div>

            <div>
              <label class="ds-field-label">Estado</label>
              <comboboxEnhanced
                v-model="selectedStatusOption"
                :disabled="!canEditStatus"
                :has-error="form.errors.status"
                :options="statusOptions"
                placeholder="Selecionar estado"
              />
              <p v-if="form.errors.status" class="ds-field-error mt-1">{{ form.errors.status }}</p>
            </div>

            <div class="lg:col-span-2">
              <label for="observations" class="ds-field-label">Observações</label>
              <textarea
                id="observations"
                v-model="form.obs"
                rows="3"
                class="ds-field"
                placeholder="Instruções especiais, condições de entrega ou notas internas..."
                :aria-invalid="Boolean(form.errors.obs)"
              />
              <p v-if="form.errors.obs" class="ds-field-error mt-1">{{ form.errors.obs }}</p>
            </div>
          </div>

          <div v-if="selectedSupplierAssessment || form.supplier_id" class="border-t border-[color:var(--ds-border)] px-5 py-4">
            <div v-if="selectedSupplierAssessment" class="ds-card border-l-4 p-4" :class="supplierAssessmentBorderClass">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <p class="text-sm font-bold text-[color:var(--ds-text)]">Avaliação ativa do fornecedor</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                    Score {{ selectedSupplierAssessment.total_score }}/100 · Revisão {{ supplierAssessmentReviewLabel }}
                  </p>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span class="ds-chip">
                    <span class="lims-status-dot" :class="supplierStatusDotClass(selectedSupplierAssessment.status)" />
                    {{ supplierAssessmentStatus }}
                  </span>
                  <span class="ds-chip">
                    <span class="lims-status-dot" :class="supplierRiskDotClass(selectedSupplierAssessment.risk_level)" />
                    {{ supplierAssessmentRisk }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="ds-card border-l-4 border-l-[color:var(--lims-hold)] p-4">
              <p class="text-sm font-bold text-[color:var(--ds-text)]">Fornecedor sem avaliação formal registada</p>
              <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Confirme a aprovação do fornecedor antes de avançar o pedido.</p>
            </div>
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div class="flex items-start gap-3">
                <CubeIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
                <div>
                  <h2 class="ds-heading text-base">Linhas do pedido</h2>
                  <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Itens, quantidades, preços, destino e data prevista.</p>
                </div>
              </div>
              <button v-if="canEditItems" type="button" class="ds-button ds-button-primary" @click="addOrderItem">
                <PlusCircleIcon class="h-4 w-4" />
                Adicionar item
              </button>
            </div>
          </div>

          <div v-if="form.order_items.length === 0" class="p-5">
            <div class="ds-empty-state p-6 text-center">
              <ShoppingBagIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <h3 class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">Nenhum item adicionado</h3>
              <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">Adicione itens para criar ou atualizar o pedido de compra.</p>
              <button v-if="canEditItems" type="button" class="ds-button ds-button-primary mt-4" @click="addOrderItem">
                <PlusCircleIcon class="h-4 w-4" />
                Adicionar primeiro item
              </button>
            </div>
          </div>

          <div v-else class="grid gap-3 p-5">
            <div v-for="(item, index) in form.order_items" :key="item.id || index" class="ds-card p-4">
              <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
                <div class="grid min-w-0 flex-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                  <div class="xl:col-span-2">
                    <label class="ds-field-label">Item <span class="text-rose-600">*</span></label>
                    <comboboxEnhanced
                      v-model="item.item_obj"
                      :disabled="!canEditItems || Number(item.received_qty || 0) > 0"
                      :has-error="itemErrors[index]?.item_id"
                      :options="itemOptions"
                      placeholder="Pesquisar item"
                      @update:modelValue="onItemChange(index)"
                    />
                    <p v-if="itemErrors[index]?.item_id" class="ds-field-error mt-1">{{ itemErrors[index].item_id }}</p>
                    <p v-if="Number(item.received_qty || 0) > 0" class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                      Recebido: {{ formatQuantity(item.received_qty) }}
                    </p>
                  </div>

                  <div>
                    <label class="ds-field-label">Quantidade <span class="text-rose-600">*</span></label>
                    <input
                      v-model.number="item.qty"
                      type="number"
                      :min="Math.max(1, Number(item.received_qty || 0))"
                      class="ds-field"
                      :disabled="!canEditItems"
                      placeholder="1"
                      @input="validateItemQuantity(index)"
                    />
                    <p v-if="itemErrors[index]?.qty" class="ds-field-error mt-1">{{ itemErrors[index].qty }}</p>
                  </div>

                  <div>
                    <label class="ds-field-label">Preço un. <span class="text-rose-600">*</span></label>
                    <input
                      v-model.number="item.unit_price"
                      type="number"
                      min="0"
                      step="0.01"
                      class="ds-field"
                      :disabled="!canEditItems"
                      placeholder="0.00"
                    />
                    <p v-if="itemErrors[index]?.unit_price" class="ds-field-error mt-1">{{ itemErrors[index].unit_price }}</p>
                  </div>

                  <div>
                    <label class="ds-field-label">Armazém <span class="text-rose-600">*</span></label>
                    <comboboxEnhanced
                      v-model="item.warehouse_obj"
                      :disabled="!canEditItems"
                      :has-error="itemErrors[index]?.warehouse_id"
                      :options="warehouseOptions"
                      placeholder="Pesquisar armazém"
                      @update:modelValue="onWarehouseChange(index)"
                    />
                    <p v-if="itemErrors[index]?.warehouse_id" class="ds-field-error mt-1">{{ itemErrors[index].warehouse_id }}</p>
                  </div>

                  <div>
                    <label class="ds-field-label">Data prevista</label>
                    <input
                      v-model="item.expected_date"
                      type="date"
                      :min="form.date || minDate"
                      class="ds-field"
                      :disabled="!canEditItems"
                    />
                    <p v-if="itemErrors[index]?.expected_date" class="ds-field-error mt-1">{{ itemErrors[index].expected_date }}</p>
                  </div>
                </div>

                <div class="flex shrink-0 items-start justify-between gap-3 border-t border-[color:var(--ds-border)] pt-3 xl:w-40 xl:flex-col xl:border-l xl:border-t-0 xl:pl-4 xl:pt-0">
                  <div>
                    <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Total</p>
                    <p class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatCurrency(lineTotal(item)) }}</p>
                  </div>
                  <button
                    v-if="canEditItems && Number(item.received_qty || 0) === 0"
                    type="button"
                    class="ds-icon-button"
                    title="Remover item"
                    @click="removeOrderItem(index)"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <div v-if="getSelectedItemDetails(item.item_id)" class="mt-4 grid gap-2 border-t border-[color:var(--ds-border)] pt-3 text-xs text-[color:var(--ds-text-soft)] md:grid-cols-4">
                <div class="flex items-center gap-1">
                  <TagIcon class="h-3 w-3" />
                  <span>Código: {{ getSelectedItemDetails(item.item_id).internal_code || getSelectedItemDetails(item.item_id).code || '-' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <RectangleStackIcon class="h-3 w-3" />
                  <span>Categoria: {{ getSelectedItemDetails(item.item_id).category?.name || 'Sem categoria' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <CubeIcon class="h-3 w-3" />
                  <span>Unidade: {{ getSelectedItemDetails(item.item_id).unit?.code || 'Sem unidade' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <BuildingStorefrontIcon class="h-3 w-3" />
                  <span>Stock: {{ getCurrentStock(item.item_id, item.warehouse_id) }}</span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>

      <aside class="space-y-4">
        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Resumo do pedido</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Validação operacional antes de gravar.</p>
          </div>
          <div class="grid gap-3 p-5">
            <div v-for="item in sidebarSummary" :key="item.label" class="ds-card p-4">
              <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ item.label }}</p>
              <p class="mt-2 text-lg font-bold text-[color:var(--ds-text)]">{{ item.value }}</p>
              <p v-if="item.caption" class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ item.caption }}</p>
            </div>
          </div>
        </article>

        <article class="ds-command-surface p-5">
          <h2 class="ds-heading text-base">Acções</h2>
          <div class="mt-4 grid gap-2">
            <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing || !isFormValid">
              <CheckCircleIcon class="h-4 w-4" />
              {{ submitLabel }}
            </button>
            <button v-if="mode === 'create'" type="button" class="ds-button ds-button-secondary w-full" :disabled="form.processing" @click="saveAsDraft">
              Salvar como rascunho
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="goBack">
              Cancelar
            </button>
          </div>
          <p v-if="!isFormValid" class="mt-3 text-xs font-semibold text-[color:var(--ds-text-soft)]">
            Selecione fornecedor, data e pelo menos uma linha válida para gravar.
          </p>
        </article>
      </aside>
    </section>
  </form>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  BuildingStorefrontIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  PlusCircleIcon,
  RectangleStackIcon,
  ShoppingBagIcon,
  TagIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import { computed, onMounted, ref, watch } from 'vue'

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
    : 'Atualize fornecedor, estado, itens e armazéns de destino preservando bloqueios de linhas já recebidas.'
))
const submitLabel = computed(() => {
  if (form.processing) {
    return 'Processando...'
  }

  return props.mode === 'create' ? 'Criar pedido' : 'Atualizar pedido'
})

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
  value: supplier.id,
  label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
})))

const statusOptions = [
  { value: 'PENDING', label: 'Pendente' },
  { value: 'APPROVED', label: 'Aprovado' },
  { value: 'ORDERED', label: 'Pedido' },
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

const supplierAssessmentBorderClass = computed(() => {
  const assessment = selectedSupplierAssessment.value

  if (!assessment) {
    return 'border-l-[color:var(--lims-hold)]'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'border-l-[color:var(--lims-critical)]'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'border-l-[color:var(--lims-hold)]'
  }

  return 'border-l-[color:var(--lims-release)]'
})

const canEditSupplier = computed(() => props.mode === 'create' || ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order?.status)))
const canEditStatus = computed(() => props.mode === 'create' || ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order?.status)))
const canEditItems = computed(() => props.mode === 'create' || ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order?.status)))

const totalQuantity = computed(() => form.order_items.reduce((total, item) => total + Number(item.qty || 0), 0))
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

const summaryCards = computed(() => [
  {
    label: 'Linhas',
    value: formatQuantity(form.order_items.length),
    caption: 'Itens no pedido',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Quantidade',
    value: formatQuantity(totalQuantity.value),
    caption: 'Unidades totais',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Valor',
    value: formatCurrency(totalAmount.value),
    caption: 'Total estimado',
    dotClass: 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Armazéns',
    value: formatQuantity(uniqueWarehouses.value),
    caption: 'Destinos únicos',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Previsão',
    value: earliestExpectedDate.value ? formatDate(earliestExpectedDate.value) : '-',
    caption: 'Primeira entrega',
    dotClass: earliestExpectedDate.value ? 'lims-status-dot-release' : 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Fornecedor',
    value: selectedSupplier.value ? 'Selecionado' : 'Pendente',
    caption: selectedSupplier.value?.name || 'Sem fornecedor',
    dotClass: selectedSupplier.value ? 'lims-status-dot-release' : 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

const sidebarSummary = computed(() => [
  {
    label: 'Fornecedor',
    value: selectedSupplier.value?.name || 'Sem fornecedor',
    caption: selectedSupplier.value?.currency ? `Moeda ${selectedSupplier.value.currency}` : null,
  },
  {
    label: 'Data do pedido',
    value: formatDate(form.date),
    caption: `Estado: ${formatStatus(form.status)}`,
  },
  {
    label: 'Referência',
    value: form.reference || 'Auto-gerada',
    caption: earliestExpectedDate.value ? `Primeira previsão ${formatDate(earliestExpectedDate.value)}` : 'Sem previsão de entrega',
  },
  {
    label: 'Valor estimado',
    value: formatCurrency(totalAmount.value),
    caption: `${formatQuantity(totalQuantity.value)} unidade(s) em ${formatQuantity(form.order_items.length)} linha(s)`,
  },
])

const isFormValid = computed(() => (
  Boolean(form.supplier_id)
  && Boolean(form.date)
  && form.order_items.length > 0
  && form.order_items.every((item) => (
    item.item_id
    && Number(item.qty || 0) > 0
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
    ORDERED: 'Pedido',
    PARTIALLY_RECEIVED: 'Recebido parcialmente',
    RECEIVED: 'Recebido',
    CANCELLED: 'Cancelado',
  }

  return statusMap[normalizeStatus(status)] || status || 'Sem estado'
}

function statusDotClass(status) {
  const classMap = {
    PENDING: 'lims-status-dot-hold',
    APPROVED: 'lims-status-dot-instrument',
    ORDERED: 'lims-status-dot-instrument',
    PARTIALLY_RECEIVED: 'lims-status-dot-hold',
    RECEIVED: 'lims-status-dot-release',
    CANCELLED: 'lims-status-dot-critical',
  }

  return classMap[normalizeStatus(status)] || 'lims-status-dot-instrument'
}

function supplierStatusDotClass(status) {
  const map = {
    approved: 'lims-status-dot-release',
    conditional: 'lims-status-dot-hold',
    suspended: 'lims-status-dot-critical',
    rejected: 'lims-status-dot-critical',
  }

  return map[status] || 'lims-status-dot-hold'
}

function supplierRiskDotClass(risk) {
  const map = {
    low: 'lims-status-dot-release',
    medium: 'lims-status-dot-instrument',
    high: 'lims-status-dot-hold',
    critical: 'lims-status-dot-critical',
  }

  return map[risk] || 'lims-status-dot-hold'
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

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 2 }).format(numericValue)
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
    errors.item_id = 'Selecione o item.'
  }

  if (!orderItem.qty || Number(orderItem.qty) <= 0) {
    errors.qty = 'Quantidade deve ser maior que 0.'
  } else if (Number(orderItem.qty) < Number(orderItem.received_qty || 0)) {
    errors.qty = `Quantidade não pode ser menor que a já recebida (${orderItem.received_qty}).`
  }

  if (!orderItem.warehouse_id) {
    errors.warehouse_id = 'Selecione o armazém.'
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
